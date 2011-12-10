<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

/**
 * Gestion des actions de moderation
 */
class Moderation extends Fsb_model
{
	/**
	 * Supprime un ou plusieurs messages
	 *
	 * @param string $where Condition des messages a supprimer
	 */
	public static function delete_posts($where)
	{
		// On recupere les donnees pour chaque message
		$sql = 'SELECT p_id, t_id, f_id, u_id, p_approve
				FROM ' . SQL_PREFIX . 'posts
				WHERE ' . $where;
		$result = Fsb::$db->query($sql);
		$posts = $topics = $forums = $users = $users_topics = array();
		$total_posts = 0;
		while ($row = Fsb::$db->row($result))
		{
			$posts[$row['p_id']] = true;

			// Seul les messages approuves ont des statistiques changeantes, on ne prend donc pas en compte
			// les messages non approuves
			if ($row['p_approve'] == IS_APPROVED)
			{
				$forums[] = $row['f_id'];
				$topics[$row['t_id']] = (!isset($topics[$row['t_id']])) ? 1 : $topics[$row['t_id']] + 1;
				$users[$row['u_id']] = (!isset($users[$row['u_id']])) ? 1 : $users[$row['u_id']] + 1;
				$total_posts++;
			}
			else if (!isset($topics[$row['t_id']]))
			{
				$topics[$row['t_id']] = 0;
			}
		}
		Fsb::$db->free($result);

		if (!$posts)
		{
			return ;
		}

		// Maintenant on verifie les sujets qui sont affectes par cette suppression de message,
		// si le premier message du sujet est supprime, alors on supprime le sujet.
		$delete_topics = array();
		$total_topics = 0;
		if ($topics)
		{
			$sql = 'SELECT t.t_id, t.t_first_p_id, t.t_total_post, t.t_approve, p.u_id
					FROM ' . SQL_PREFIX . 'topics t
					LEFT JOIN ' . SQL_PREFIX . 'posts p
						ON t.t_first_p_id = p.p_id
					WHERE t.t_id IN (' . implode(', ', array_unique(array_keys($topics))) . ')';
			$result = Fsb::$db->query($sql);
			$get_posts = array();
			while ($row = Fsb::$db->row($result))
			{
				if (isset($posts[$row['t_first_p_id']]) || $topics[$row['t_id']] == $row['t_total_post'])
				{
					if (isset($posts[$row['t_first_p_id']]))
					{
						if ($topics[$row['t_id']] == $row['t_total_post'])
						{
							$users_topics[$row['u_id']] = (!isset($users_topics[$row['u_id']])) ? 1 : $users_topics[$row['u_id']] + 1;
							$get_posts[] = $row['t_id'];
						}
					}
					$delete_topics[] = $row['t_id'];
					unset($topics[$row['t_id']]);

					if ($row['t_approve'] == IS_APPROVED)
					{
						$total_topics++;
					}
				}
			}
			Fsb::$db->free($result);

			if ($get_posts)
			{
				// Si des messages suplementaires ont ete supprimes dans les sujets, on recupere leurs donnees
				$sql = 'SELECT p_id, f_id, u_id
						FROM ' . SQL_PREFIX . 'posts
						WHERE t_id IN (' . implode(', ', $get_posts) . ')
							AND p_id NOT IN (' . implode(', ', array_keys($posts)) . ')';
				$result = Fsb::$db->query($sql);
				while ($row = Fsb::$db->row($result))
				{
					$posts[$row['p_id']] = true;
					$users[$row['u_id']] = (!isset($users[$row['u_id']])) ? 1 : $users[$row['u_id']] + 1;
				}
				Fsb::$db->free($result);
			}

			// On supprime les sujets
			Moderation::_delete_topics($delete_topics);
		}

		// On supprime les messages
		Moderation::_delete_posts(array_keys($posts));

		// On met a jour le compteur de messages des membres
		foreach ($users AS $u_id => $total)
		{
			Fsb::$db->update('users', array(
				'u_total_post' =>	array('u_total_post - ' . $total, 'is_field' => true),
				'u_total_topic' =>	array('u_total_topic ' . ((isset($users_topics[$u_id])) ? '- ' . $users_topics[$u_id] : ''), 'is_field' => true),
			), 'WHERE u_id = ' . $u_id);
		}

		// Maintenant que les messages sont supprimes, on met a jour les forums et les sujets qui ont ete concernes
		// par tout ce mouvement.
		if ($topics)
		{
			Sync::topics($topics);
		}
		Sync::forums($forums);
		Sync::signal(Sync::APPROVE | Sync::ABUSE);

		// On met a jour les derniers messages des sujets lus
		if ($topics)
		{
			$sql = 'UPDATE ' . SQL_PREFIX . 'topics_read tr
					SET tr.p_id = (SELECT t.t_last_p_id
						FROM ' . SQL_PREFIX . 'topics t
						WHERE t.t_id = tr.t_id)
					WHERE tr.t_id IN (' . implode(', ', array_keys($topics)) . ')';
			Fsb::$db->query($sql);
		}

		Fsb::$cfg->update('total_posts', Fsb::$cfg->get('total_posts') - $total_posts);
		Fsb::$cfg->update('total_topics', Fsb::$cfg->get('total_topics') - $total_topics);
	}

	/**
	 * Supprime les messages
	 *
	 * @param array $posts ID des messages a supprimer
	 */
	public static function _delete_posts($posts)
	{
		if (!$posts)
		{
			return ;
		}

		$list_posts = implode(', ', $posts);
		$delete_ary = array('posts', 'posts_abuse');
		foreach ($delete_ary AS $table)
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . $table . '
					WHERE p_id IN (' . $list_posts . ')';
			Fsb::$db->query($sql);
		}

		if (Fsb::$cfg->get('search_method') == 'fulltext_fsb')
		{
			Search_fulltext_fsb::delete_index($posts);
		}
	}

	/**
	 * Supprime un ou plusieurs sujets
	 *
	 * @param string $where Condition de supression des sujets
	 */
	public static function delete_topics($where, $f_id = null)
	{
		// On recupere la liste des sujets a supprimer
		$sql = 'SELECT t_id
				FROM ' . SQL_PREFIX . 'topics
				WHERE ' . $where . ($f_id ? ' AND t_trace <> ' . $f_id : '');
		$result = Fsb::$db->query($sql);
		$topics = array();
		while ($row = Fsb::$db->row($result))
		{
			$topics[] = $row['t_id'];
		}
		Fsb::$db->free($result);

		if ($topics)
		{
			Moderation::delete_posts('t_id IN (' . implode(', ', $topics) . ')');
		}
        
        if($f_id)
        {
            Moderation::delete_traces($where);
        }
	}
    
	/**
	 * Supprime les traces des sujets
	 *
	 * @param string $where Condition de supression des traces
	 */
    public static function delete_traces($where)
    {
        Fsb::$db->update('topics', array(
            't_trace' => 0
        ), 'WHERE ' . $where);
    }

	/**
	 * Suppression des sujets
	 *
	 * @param array $topics ID des sujets a supprimer
	 */
	public static function _delete_topics($topics)
	{
		if (!$topics)
		{
			return ;
		}

		$list_topics = implode(', ', $topics);
		$delete_ary = array('topics', 'topics_read', 'topics_notification');
		foreach ($delete_ary AS $table)
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . $table . '
					WHERE t_id IN (' . $list_topics . ')';
			Fsb::$db->query($sql);
		}

		Moderation::delete_poll($topics);
	}

	/**
	 * Suppression de messages prives
	 *
	 * @param int|array $idx ID des messages a supprimer
	 */
	public static function delete_mp($idx)
	{
		if (!is_array($idx))
		{
			$idx = array($idx);
		}

		if (!$idx)
		{
			return ;
		}

		$sql = 'DELETE FROM ' . SQL_PREFIX . 'mp
				WHERE mp_id IN (' . implode(', ', $idx). ')';
		Fsb::$db->query($sql);

		$sql = 'DELETE FROM ' . SQL_PREFIX . 'posts_abuse 
				WHERE pa_mp_id IN (' . implode(', ', $idx). ')';
		Fsb::$db->query($sql);

		Sync::signal(Sync::ABUSE);
	}

	/**
	 * Suppression d'un sondage
	 *
	 * @param int|array $topics ID du / des sujets
	 */
	public static function delete_poll($topics)
	{
		if (!is_array($topics))
		{
			$topics = array($topics);
		}

		$list_topics = implode(', ', $topics);
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'poll
				WHERE t_id IN (' . $list_topics . ')';
		Fsb::$db->query($sql);

		$sql = 'DELETE FROM ' . SQL_PREFIX . 'poll_options
				WHERE t_id IN (' . $list_topics . ')';
		Fsb::$db->query($sql);

		$sql = 'DELETE FROM ' . SQL_PREFIX . 'poll_result
				WHERE t_id IN (' . $list_topics . ')';
		Fsb::$db->query($sql);
	}

	/**
	 * Divise un sujet.
	 *
	 * @param int $topic_id ID du sujet a diviser
	 * @param int $forum_id ID du forum cible
	 * @param string $title Titre du sujet de destination
	 * @param array $action ID des messages selectiones pour le split
	 * @return array array('new_topic_id', 'old_topic_name')
	 */
	public static function split_topic($topic_id, $forum_id, $title, $action)
	{
		$count_action = count($action);

		// Donnees du forum cible
		$sql = 'SELECT f_type
				FROM ' . SQL_PREFIX . 'forums
				WHERE f_id = ' . $forum_id;
		$f_type = Fsb::$db->get($sql, 'f_type');

		// On verifie le type de forum
		if ($f_type != FORUM_TYPE_NORMAL)
		{
			Display::message('modo_move_bad_type');
		}

		// On verifie les droits d'ecriture du forum
		if (!Fsb::$session->is_authorized($forum_id, 'ga_create_' . $GLOBALS['_topic_type'][count($GLOBALS['_topic_type']) - 1]))
		{
			Display::message('modo_move_no_write');
		}

		// Donnees sur le sujet
		$sql = 'SELECT t.f_id, t.t_total_post, t.t_title, t.t_map, t.t_map_first_post
				FROM ' . SQL_PREFIX . 'topics t
				INNER JOIN ' . SQL_PREFIX . 'forums f
					ON t.f_id = f.f_id
				WHERE t.t_id = ' . $topic_id;
		$result = Fsb::$db->query($sql);
		if (!$topic_data = Fsb::$db->row($result))
		{
			Display::message('topic_not_exists');
		}
		Fsb::$db->free($result);

		// On verifie que le nombre de messages selectionnes ne corespond pas
		// au total de messages du sujet initial
		if ($count_action >= $topic_data['t_total_post'])
		{
			Display::message('modo_split_cant_all');
		}

		// Donnees sur le premier message selectionne
		$sql = 'SELECT p_id, u_id
				FROM ' . SQL_PREFIX . 'posts
				WHERE p_id = ' . $action[0] . '
					AND t_id = ' . $topic_id;
		$result = Fsb::$db->query($sql);
		if (!$first_post = Fsb::$db->row($result))
		{
			trigger_error('Impossible de verifier les donnees du premier message', FSB_ERROR);
		}
		Fsb::$db->free($result);

		// Donnees sur le dernier message selectionne
		$sql = 'SELECT p_id, u_id, p_nickname, p_time
				FROM ' . SQL_PREFIX . 'posts
				WHERE p_id = ' . $action[$count_action - 1] . '
					AND t_id = ' . $topic_id;
		$result = Fsb::$db->query($sql);
		if (!$last_post = Fsb::$db->row($result))
		{
			trigger_error('Impossible de verifier les donnees du dernier message', FSB_ERROR);
		}
		Fsb::$db->free($result);

		Fsb::$db->transaction('begin');

		// On tronque le titre s'il est trop grand
		$title = Send::truncate_title($title);

		// Creation du nouveau sujet
		$new_topic_id = Send::send_topic($forum_id, $first_post['u_id'], $title, $topic_data['t_map'], count($GLOBALS['_topic_type']) - 1, array(
			't_total_post' =>		$count_action,
			't_first_p_id' =>		$first_post['p_id'],
			't_map_first_post' =>	$topic_data['t_map_first_post'],
			't_last_p_id' =>		$last_post['p_id'],
			't_last_u_id' =>		$last_post['u_id'],
			't_last_p_nickname' =>	$last_post['p_nickname'],
			't_last_p_time' =>		$last_post['p_time'],
		));

		// Ajout des messages coches dans le nouveau sujet
		Fsb::$db->update('posts', array(
			't_id' =>	$new_topic_id,
			'f_id' =>	$forum_id,
		), 'WHERE p_id IN (' . implode(', ', $action) . ')');

		// Donnees sur le nouveau dernier message du dernier sujet
		$sql = 'SELECT p_id, p_time, p_nickname, u_id
					FROM ' . SQL_PREFIX . 'posts
					WHERE t_id = ' . $topic_id . '
					ORDER BY p_time DESC
					LIMIT 1';
		$result = Fsb::$db->query($sql);
		$new_last_post = Fsb::$db->row($result);

		// Mise a jour de l'ancien sujet
		Fsb::$db->update('topics', array(
			't_total_post' =>		array('(t_total_post - ' . $count_action . ')', 'is_field' => true),
			't_last_p_id' =>		$new_last_post['p_id'],
			't_last_p_time' =>		$new_last_post['p_time'],
			't_last_p_nickname' =>	$new_last_post['p_nickname'],
			't_last_u_id' =>		$new_last_post['u_id'],
		), 'WHERE t_id = ' . $topic_id);

		// Syncronisation des forums et sujets lus
		Sync::forums(array_unique(array($forum_id, $topic_data['f_id'])));
		Sync::topics_read(array($topic_id, $new_topic_id));

		// +1 sujet sur le total de sujet du forum
		Fsb::$cfg->update('total_topics', Fsb::$cfg->get('total_topics') + 1);
		Fsb::$db->destroy_cache('forums_');

		Fsb::$db->transaction('commit');

		return (array($new_topic_id, $topic_data['t_title']));
	}

	/**
	 * Permet de fusioner un ou plusieurs sujets avec un autre sujet
	 *
	 * @param int $t_id ID du sujet initial
	 * @param int $f_id ID du forum du sujet initial
         * @param int $u_id ID du membre qui a posté le sujet initial
	 * @param array $idx ID des sujets a fusioner avec le sujet original
	 */
	public static function merge_topics($t_id, $f_id, $u_id, $idx)
	{
		// On exclu $t_id du tableau $idx
		if (in_array($t_id, $idx))
		{
			foreach ($idx AS $k => $v)
			{
				if ($v == $t_id)
				{
					unset($idx[$k]);
					break;
				}
			}
		}

		if (!$idx)
		{
			return ;
		}

                /* $nb_topics[$id] contiendra le nombre de
                   sujets - 1 du membre ayant l'id $id parmi
                   ceux qui seront fusionés */
                $nb_topics = array();
                $nb_topics[$u_id] = 1;

		Fsb::$db->transaction('begin');

		// On recupere les donnees des sujets qui vont etre fusione
		$sql = 'SELECT t_id, f_id, t_total_post, u_id
				FROM ' . SQL_PREFIX . 'topics
				WHERE t_id IN (' . implode(', ', $idx) . ')';
		$result = Fsb::$db->query($sql);
		$forums = array($f_id);
		$total_posts = 0;
		while ($row = Fsb::$db->row($result))
		{
			$forums[$row['f_id']] = true;
			$total_posts += $row['t_total_post'];
                        
                        if (isset($nb_topics[$row['u_id']]))
                                $nb_topics[$row['u_id']]++;
                        else
                                $nb_topics[$row['u_id']] = 1;    
		}
               
		Fsb::$db->free($result);

		// Mise a jour du sujet des messages
		Fsb::$db->update('posts', array(
			't_id' =>	$t_id,
		), 'WHERE t_id IN (' . implode(', ', $idx) . ')');

		$sql = 'DELETE FROM ' . SQL_PREFIX . 'topics_read
				WHERE t_id IN (' . implode(', ', $idx) . ')';
		Fsb::$db->query($sql);

		// Calcul du dernier message du sujet
		$sql = 'SELECT p_id, p_time, p_nickname, u_id
				FROM ' . SQL_PREFIX . 'posts
				WHERE t_id = ' . $t_id . '
				ORDER BY p_time DESC
				LIMIT 1';
		$result = Fsb::$db->query($sql);
		$last = Fsb::$db->row($result);
		Fsb::$db->free($result);

		// Calcul du premier message du sujet
		$sql = 'SELECT p_id, u_id
				FROM ' . SQL_PREFIX . 'posts
				WHERE t_id = ' . $t_id . '
				ORDER BY p_time
				LIMIT 1';
		$first_post = Fsb::$db->request($sql);

		// Mise a jour du compteur de message du sujet, et du dernier message
		Fsb::$db->update('topics', array(
			't_total_post' =>		array('t_total_post + ' . $total_posts, 'is_field' => true),
			't_last_p_id' =>		$last['p_id'],
			't_last_u_id' =>		$last['u_id'],
			't_last_p_time' =>		$last['p_time'],
			't_last_p_nickname' =>	$last['p_nickname'],
			't_first_p_id' =>		$first_post['p_id'],
			'u_id' =>				$first_post['u_id'],
		), 'WHERE t_id = ' . $t_id);

                // Mise à jour du compteur de sujets des membres
                $sum = 0;
                $nb_topics[$first_post['u_id']]--;
                foreach ($nb_topics as $m_id => $nb_t)
                {
                        $sum += $nb_t;
                        Fsb::$db->update('users', array(
                                 'u_total_topic' => array('u_total_topic - ' . $nb_t, 'is_field' => true),
                        ), 'WHERE u_id = ' . $m_id);
                }

                // Mise à jour du total des topics du forum
                Fsb::$cfg->update('total_topics', Fsb::$cfg->get('total_topics') - $sum);

		Moderation::_delete_topics($idx);
		Fsb::$db->transaction('commit');

		// Resyncronisation des informations apres la fusion
		Sync::forums($forums);
		Sync::topics_read(array($t_id));
	}

	/**
	 * Deplace un ou plusieurs sujets vers un autre forum
	 *
	 * @param int|array $id ID des sujets
	 * @param int $from_f_id ID du forum de provenance
	 * @param int $to_f_id ID du forum de destination
	 * @param bool $trace Si les sujets doivent etre traces ou pas
	 */
	public static function move_topics($id, $from_f_id, $to_f_id, $trace)
	{
		if (!is_array($id))
		{
			$id = array($id);
		}
		
		if (!$id)
		{
			return;
		}

		// On filtre les messages pour qu'ils ne viennent que d'un seul forum
		$sql = 'SELECT t_id
				FROM ' . SQL_PREFIX . 'topics
				WHERE t_id IN (' . implode(', ', $id) . ')
					AND f_id = ' . $from_f_id;
		$result = Fsb::$db->query($sql);
		$idx = array();
		while ($row = Fsb::$db->row($result))
		{
			$idx[] = $row['t_id'];
		}
		Fsb::$db->free($result);

		if ($idx && $from_f_id != $to_f_id)
		{
			$list_id = implode(', ', $idx);

			// On deplace le sujet vers son forum de destination
			Fsb::$db->update('topics', array(
				'f_id' =>		$to_f_id,
				't_trace' =>	($trace) ? $from_f_id : 0,
			), 'WHERE t_id IN (' . $list_id . ')');

			// On deplace les messages
			Fsb::$db->update('posts', array(
				'f_id' =>		$to_f_id,
			), 'WHERE t_id IN (' . $list_id . ')');

			Sync::forums(array($from_f_id, $to_f_id));
		}
	}

	/**
	 * Modifie le status du sujet (verrouille ou non verrouille)
	 *
	 * @param int $id ID du sujet
	 * @param int $status Status du sujet
	 * @param int $f_id ID du forum (protection facultative)
	 */
	public static function lock_topic($id, $status, $f_id = null)
	{
		if (is_array($id))
		{
			$id = implode(', ', $id);
		}
		
		if (!$id)
		{
			return;
		}

		Fsb::$db->update('topics', array(
			't_status' =>		$status,
		), 'WHERE t_id IN (' . $id . ') ' . ((!is_null($f_id)) ? 'AND f_id = ' . $f_id : ''));
	}

	/**
	 * Approuve un message
	 *
	 * @param int $p_id ID du message a approuver
	 */
	public static function approve_post($p_id)
	{
		// Donnees du sujet du message
		$sql = 'SELECT t.t_id, t.f_id, t.t_type, t.t_title, t.t_first_p_id, p.u_id
				FROM ' . SQL_PREFIX . 'posts p
				LEFT JOIN ' . SQL_PREFIX . 'topics t
					ON p.t_id = t.t_id
				WHERE p.p_id = ' . $p_id . '
					AND p.p_approve = ' . IS_NOT_APPROVED;
		if (!$data = Fsb::$db->request($sql))
		{
			return ;
		}

		Fsb::$db->transaction('begin');

		// Nouveau sujet ?
		$is_new_topic = ($data['t_first_p_id'] == $p_id) ? true : false;

		if ($is_new_topic)
		{
			Fsb::$cfg->update('total_topics', Fsb::$cfg->get('total_topics') + 1);

			// Mise a jour du nombre d'annonce globale ?
			if ($data['t_type'] == GLOBAL_ANNOUNCE)
			{
				Fsb::$cfg->update('total_global_announce', Fsb::$cfg->get('total_global_announce') + 1);
			}
		}

		// Mise a jour du nombre total de messages du forum
		Fsb::$cfg->update('total_posts', Fsb::$cfg->get('total_posts') + 1);

		// Mise a jour du sujet
		Fsb::$db->update('topics', array(
			't_total_post' =>		array('t_total_post + ' . ((!$is_new_topic) ? '1' : '0'), 'is_field' => true),
			't_approve' =>			IS_APPROVED,
		), 'WHERE t_id = ' . $data['t_id']);

		// Mise a jour du message
		Fsb::$db->update('posts', array(
			'p_approve' =>			IS_APPROVED,
		), 'WHERE p_id = ' . $p_id);

		// Mise a jour du membre
		Fsb::$db->update('users', array(
			'u_total_post' =>		array('u_total_post + 1', 'is_field' => true),
			'u_total_topic' =>		array('u_total_topic + ' . (($is_new_topic) ? '1' : '0'), 'is_field' => true),
		), 'WHERE u_id = ' . $data['u_id']);

		Sync::signal(Sync::APPROVE);

		// Mise a jour du dernier message dans le sujet
		Sync::topics(array($data['t_id'] => 0));
		Sync::forums(array($data['f_id']));

		Fsb::$db->transaction('commit');

		// Envoie d'E-mail aux membres surveillant le sujet
		if (Fsb::$mods->is_active('topic_notification'))
		{
			Send::topic_notification($data['t_id'], $p_id, $data['u_id'], $data['t_title']);
		}
	}

	/**
	 * Banissement d'un pseudonyme / email / IP
	 *
	 * @param string $type Type de banissement
	 * @param string $content Element a banir
	 * @param string $reason Raison
	 * @param int $total_length Duree du banissement
	 * @param bool $cookie Banissement par cookie
	 */
	public static function ban($type, $content, $reason, $total_length, $cookie)
	{
		switch ($type)
		{
			case 'login' :
				$sql = 'SELECT u_id 
						FROM ' . SQL_PREFIX . 'users 
						WHERE u_nickname ' . Fsb::$db->like() . ' \'' . str_replace('*', '%', Fsb::$db->escape($content)) . '\'
							AND u_auth < ' . ADMIN;
				$result = Fsb::$db->query($sql);
			
				$list_idx = '';
				while ($row = Fsb::$db->row($result))
				{
					$list_idx .= $row['u_id'] . ', ';
				}
				$list_idx = substr($list_idx, 0, -2);
				Fsb::$db->free($result);
			break;

			case 'ip' :
				$sql = 'SELECT s.s_id, s.s_ip
						FROM ' . SQL_PREFIX . 'sessions s
						INNER JOIN ' . SQL_PREFIX . 'users u
							ON u.u_id = s.s_id
						WHERE u.u_auth < ' . ADMIN;
				$result = Fsb::$db->query($sql);
				
				$list_idx = '';
				while ($row = Fsb::$db->row($result))
				{
					$ip = $row['s_ip'];
					if (String::is_matching($content, $ip))
					{
						$list_idx .= $row['s_id'] . ', ';
					}
				}
				$list_idx = substr($list_idx, 0, -2);
				Fsb::$db->free($result);
			break;

			case 'mail' :
				$sql = 'SELECT u_id
						FROM ' . SQL_PREFIX . 'users 
						WHERE u_email ' . Fsb::$db->like() . ' \'' . str_replace('*', '%', Fsb::$db->escape($content)) . '\'
							AND u_auth < ' . ADMIN;
				$result = Fsb::$db->query($sql);
			
				$list_idx = '';
				while ($row = Fsb::$db->row($result))
				{
					$list_idx .= $row['u_id'] . ', ';
				}
				$list_idx = substr($list_idx, 0, -2);
				Fsb::$db->free($result);
			break;

			default :
				die('Mode error');
			break;
		}

		if ($list_idx)
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'sessions 
					WHERE s_id IN (' . $list_idx . ')';
			Fsb::$db->query($sql);
		}

		Fsb::$db->insert('ban', array(
			'ban_content' =>		$content,
			'ban_type' =>			$type,
			'ban_length' =>			$total_length,
			'ban_reason' => 		$reason,
			'ban_cookie' =>			$cookie,
		));
		Fsb::$db->destroy_cache('ban_');
	}

	/**
	 * Donne un avertissement a un membre
	 *
	 * @param string $mode Type d'avertissement (more pour ajouter, less pour supprimer)
	 * @param int $modo_id ID de la personne donnant l'avertissement
	 * @param int $user_id ID du membre cible
	 * @param string $reason Raison de l'avertissement
	 * @param int $u_warn_post Restriction d'ecriture
	 * @param int $u_warn_read Restriction de lecture
	 * @param array $disable Contient les donnees a desactiver
	 */
	public static function warn_user($mode, $modo_id, $user_id, $reason, $u_warn_post, $u_warn_read, $disable)
	{
		// On determine une nouvelle restriction
		foreach (array('post', 'read') AS $restriction)
		{
			${'user_warn_' . $restriction} = ${'u_warn_' . $restriction};
			${'insert_' . $restriction} = '';
			if ($disable[$restriction . '_check'])
			{
				$disable_foo = $disable[$restriction];
				$disable_foo_time = $disable[$restriction . '_time'];

				if ($disable_foo_time == 0)
				{
					${'user_warn_' . $restriction} = ($mode == 'more') ? 1 : 0;
					${'insert_' . $restriction} = 'unlimited';
				}
				else
				{
					$time = intval($disable_foo * $disable_foo_time);
					if ($time < 0)
					{
						$time = 0;
					}
					$operator = ($mode == 'more') ? '+' : '-';

					${'user_warn_' . $restriction} = (${'u_warn_' . $restriction} >= CURRENT_TIME) ? array('u_warn_' . $restriction . ' ' . $operator . ' ' . $time, 'is_field' => true) : CURRENT_TIME + $time;
					${'insert_' . $restriction} = $operator . strval($time);
				}
			}
		}

		// Ajout de l'avertissement dans la base de donnee
		Fsb::$db->insert('warn', array(
			'u_id' =>					$user_id,
			'modo_id' =>				$modo_id,
			'warn_time' =>				CURRENT_TIME,
			'warn_type' =>				($mode == 'more') ? WARN_MORE : WARN_LESS,
			'warn_reason' =>			$reason,
			'warn_restriction_post' =>	$insert_post,
			'warn_restriction_read' =>	$insert_read,
		));

		// Mise a jour du profil du membre
		Fsb::$db->update('users', array(
			'u_warn_post' =>		$user_warn_post,
			'u_warn_read' =>		$user_warn_read,
			'u_total_warning' =>	array('u_total_warning ' . (($mode == 'more') ? '+' : '-') . ' 1', 'is_field' => true),
		), 'WHERE u_id = ' . $user_id);
	}
}

/* EOF */