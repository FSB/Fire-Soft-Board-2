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
 * Classe de gestion des forums.
 * La modelisation des forums dans la base de donnee passe par la classe Sql_interval
 *
 */
class Forum extends Fsb_model
{
	/**
	 * Affiche un forum
	 *
	 * @param array $forum Donnees du forum a afficher
	 * @param string $type forum ou subforum
	 * @param int $current_level Determine le niveau actuel au niveau de la hierarchie
	 * @param bool $is_read Determine si le forum comporte des sujets non lus ou pas
	 */
	public static function display(&$forum, $type, $current_level, $is_read = true)
	{
		// Decalage en cas d'affichage des forums en arbre
		$width = (Fsb::$cfg->get('display_subforums')) ? 'padding-left: ' . ((20 * $forum['f_level']) - 13) . 'px' : '';

		if ($type == 'subforum')
		{
			$markread = (Fsb::$frame->_get_frame_page() == 'forum') ? 'forum&amp;markread= true &amp;f_id=' . $forum['f_id'] . '&amp;return=' . $forum['f_parent'] : 'index&amp;markread= true &amp;forum=' . $forum['f_id'];

			Fsb::$tpl->set_blocks('cat.forum.subforum', array(
				'NAME' =>		Html::forumname($forum['f_name'], $forum['f_id'], $forum['f_color'], $forum['f_location']),
				'IS_READ' =>	$is_read,

				'U_MARKREAD' =>		sid(ROOT . 'index.' . PHPEXT . '?p=' . $markread),
			));
		}
		else if ($forum['f_type'] == FORUM_TYPE_INDIRECT_URL || $forum['f_type'] == FORUM_TYPE_DIRECT_URL)
		{
			Fsb::$tpl->set_blocks('cat.forum', array(
				'TOTAL_CLICK' =>	sprintf(Fsb::$session->lang('forum_total_click'), $forum['f_location_view']),
				'TYPE' =>			$forum['f_type'],
				'NAME' =>			Html::forumname($forum['f_name'], $forum['f_id'], $forum['f_color'], $forum['f_location']),
				'DESCRIPTION' =>	nl2br($forum['f_text']),
				'WIDTH' =>			$width,

				'U_FORUM' =>		($forum['f_type'] == FORUM_TYPE_INDIRECT_URL) ? sid(ROOT . 'index.' . PHPEXT . '?p=forum&amp;f_id=' . $forum['f_id']) : $forum['f_location'],
			));
		}
		else
		{
			// Donnees du dernier message
			list($is_last_read, $last_url) = check_read_post($forum['f_last_p_id'], $forum['f_last_p_time'], $forum['f_last_t_id'], $forum['last_unread_id']);

			// On tronque le titre du sujet ?
			$topic_title = Parser::title($forum['f_last_t_title']);
			$substr_topic_title = (strlen($topic_title) <= 20) ? $topic_title : Parser::title(String::substr($forum['f_last_t_title'], 0, 20)) . '...';

			$markread = (Fsb::$frame->_get_frame_page() == 'forum') ? 'forum&amp;markread= true &amp;f_id=' . $forum['f_id'] . '&amp;return=' . $forum['f_parent'] : 'index&amp;markread= true &amp;forum=' . $forum['f_id'];

			Fsb::$tpl->set_blocks('cat.forum', array(
				'TYPE' =>			$forum['f_type'],
				'NAME' =>			Html::forumname($forum['f_name'], $forum['f_id'], $forum['f_color'], $forum['f_location']),
				'DESCRIPTION' =>	nl2br($forum['f_text']),
				'WIDTH' =>			$width,
				'NICKNAME' =>		Html::nickname($forum['f_last_p_nickname'], $forum['f_last_u_id'], $forum['u_color']),
				'DATE' =>			Fsb::$session->print_date($forum['f_last_p_time']),
				'HAVE_LAST' =>		($forum['f_last_p_id']) ? true : false,
				'TOTAL_POST' =>		sprintf(Fsb::$session->lang('forum_total_post' . (($forum['f_total_post'] > 1) ? 's' : '')), $forum['f_total_post']),
				'TOTAL_TOPIC' =>	sprintf(Fsb::$session->lang('forum_total_topic' . (($forum['f_total_topic'] > 1) ? 's' : '')), $forum['f_total_topic']),
				'TOPIC_TITLE' =>	$substr_topic_title,
				'REAL_TITLE' =>		$topic_title,
				'IS_READ' =>		$is_read,
				'IS_LAST_READ' =>	$is_last_read,
				'IS_LOCKED' =>		($forum['f_status'] == LOCK) ? true : false,
				'DISPLAY_MODERATORS' => ($forum['f_display_moderators']) ? true : false,
			    'DISPLAY_SUBFORUMS' => ($forum['f_display_subforums']) ? true : false,

				'U_FORUM' =>		sid(ROOT . 'index.' . PHPEXT . '?p=forum&amp;f_id=' . $forum['f_id']),
				'U_TOPIC' =>		sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $forum['f_last_t_id']),
				'U_LAST_POST' =>	sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;' . $last_url),
				'U_MARKREAD' =>		sid(ROOT . 'index.' . PHPEXT . '?p=' . $markread),
			));

			// Moderateurs du forum ?
			if (Fsb::$mods->is_active('forums_moderators'))
			{
				Forum::get_moderators($forum['f_id']);
			}
		}
	}

	/**
	 * Marque les sujets d'un / plusieurs forums comme lu
	 *
	 * @param string $type Peut prendre les valeurs all, cat, forum ou topic
	 * @param int $id ID du forum, de la categorie ou du sujet
	 */
	public static function markread($type, $id = null)
	{
		if (!Fsb::$session->is_logged())
		{
			return ;
		}

		// On recupere tous les sujets non lu de ce forum
		$select = new Sql_select();
		$select->join_table('FROM', 'topics t', 't.t_id, t.f_id, t.t_last_p_time, t.t_last_p_id');
		$select->join_table('LEFT JOIN', 'topics_read tr', 'tr.p_id', 'ON t.t_id = tr.t_id AND tr.u_id = ' . Fsb::$session->id());

		// On cree la clause WHERE
		switch ($type)
		{
			case 'cat' :
				$sql = 'SELECT f_id
						FROM ' . SQL_PREFIX . 'forums
						WHERE f_cat_id = ' . $id;
				$result = Fsb::$db->query($sql, 'forums_');
				$list_id = '';
				while ($row = Fsb::$db->row($result))
				{
					$list_id .= $row['f_id'] . ', ';
				}
				$list_id = substr($list_id, 0, -2);
				Fsb::$db->free($result);

				if (!$list_id)
				{
					return ;
				}
				$select->where('t.f_id IN (' . $list_id . ') AND');
			break;

			case 'forum' :
				if (!$id)
				{
					return;
				}

				if (is_array($id))
				{
					$id = implode(', ', $id);
				}

				$sql = 'SELECT childs.f_id
						FROM ' . SQL_PREFIX . 'forums f
						LEFT JOIN ' . SQL_PREFIX . 'forums childs
							ON f.f_left <= childs.f_left
								AND f.f_right >= childs.f_right
						WHERE f.f_id IN (' . $id . ')';
				$idx = array();
				$result = Fsb::$db->query($sql);
				while ($row = Fsb::$db->row($result))
				{
					$idx[] = $row['f_id'];
				}
				Fsb::$db->free($result);

				if (!$idx)
				{
					return ;
				}

				$id = implode(', ', $idx);
				$select->where('t.f_id IN (' . $id . ') AND');
			break;

			case 'topic' :
				if (!$id)
				{
					return;
				}

				if (is_array($id))
				{
					$id = implode(', ', $id);
				}
				$select->where('t.t_id IN (' . $id . ') AND');
			break;
		}
		$select->where('(tr.p_id IS null OR tr.p_id < t.t_last_p_id) AND t.t_last_p_time > ' . MAX_UNREAD_TOPIC_TIME);

		// On met a jour la table fsb2_topics_read
		$result = $select->execute();
		while ($row = Fsb::$db->row($result))
		{
			if (!$row['p_id'] || $row['p_id'] < $row['t_last_p_id'])
			{
				Fsb::$db->insert('topics_read', array(
					'u_id' =>			array(Fsb::$session->id(), true),
					't_id' =>			array($row['t_id'], true),
					'p_id' =>			$row['t_last_p_id'],
				), 'REPLACE', true);
			}
		}
		Fsb::$db->free($result);

		// Si la SGBD le suporte on lance la procedure de multi insertion
		Fsb::$db->query_multi_insert();
	}

	/**
	 * Recupere les forums avec une jointure sur le dernier message
	 *
	 * @param string $where Condition sur la requete
	 * @return resource Resultat SQL
	 */
	public static function query($where = '')
	{
		$sql = 'SELECT f.*, tr.p_id AS last_unread_id, u.u_color
				FROM ' . SQL_PREFIX . 'forums f
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON f.f_last_u_id = u.u_id
				LEFT JOIN ' . SQL_PREFIX . 'topics_read tr
					ON f.f_last_t_id = tr.t_id
						AND tr.u_id = ' . Fsb::$session->id() . '
				' . $where . '
				ORDER BY f.f_left';
		return (Fsb::$db->query($sql));
	}

	/**
	 * Recupere les sujets non lus
	 *
	 * @return array Tableau avec en clef l'ID du forum et en valeur le nombre de sujets non lus
	 */
	public static function get_topics_read()
	{
		// Cette requete recupere pour chaque forum, le nombre de messages non lus.
		$link = (SQL_DBAL != 'sqlite') ? 'f.' : '';
		$sql = 'SELECT f.f_id, f.f_parent, (
					SELECT COUNT(*)
					FROM ' . SQL_PREFIX . 'topics t
					LEFT JOIN ' . SQL_PREFIX . 'topics_read tr
						ON t.t_id = tr.t_id
							AND tr.u_id = ' . Fsb::$session->id() . '
					WHERE t.f_id = ' . $link . 'f_id
						AND (tr.p_id IS null OR tr.p_id < t.t_last_p_id)
						AND t.t_approve = ' . IS_APPROVED . '
						AND t.t_last_p_time > ' . MAX_UNREAD_TOPIC_TIME . '
				) AS total
				FROM ' . SQL_PREFIX . 'forums f
				ORDER BY f.f_left';
		$result = Fsb::$db->query($sql);
		$forum_topic_read = array();
		$parents = array();
		while ($row = Fsb::$db->row($result))
		{
			$f_id = $row['f_id'];
			$parents[$f_id] = $row['f_parent'];
			$forum_topic_read[$f_id] = 0;
			if (Fsb::$session->is_authorized($row['f_id'], 'ga_view') && Fsb::$session->is_authorized($row['f_id'], 'ga_view_topics'))
			{
				$forum_topic_read[$f_id] = intval($row['total']);
				$break = 0;
				while ($parents[$f_id] && $break++ < 1000)
				{
					$forum_topic_read[$parents[$f_id]] += $forum_topic_read[$f_id];
					$f_id = $parents[$f_id];
				}

				if ($break == 1000)
				{
					trigger_error('Erreur dans l\'arbre des forums', FSB_ERROR);
				}
			}
		}
		Fsb::$db->free($result);

		// On regarde si un des forums visible est en non lu
		$have_unread = false;
		foreach (Forum::get_authorized(array('ga_view', 'ga_view_topics')) AS $f_access_id)
		{
			if (isset($forum_topic_read[$f_access_id]) && $forum_topic_read[$f_access_id] > 0)
			{
				$have_unread = true;
				break;
			}
		}

		return ($forum_topic_read);
	}

	/**
	 * Recupere un tableaude navigation pour le forum
	 *
	 * @param unknown_type $f_id ID du forum
	 * @param array $args_ary Tableau aditionel pour ajouter des liens en fin de navigation
	 * @param mixed $obj Objet de la page courante, afin d'ajouter a la propriete $obj->tab_title le dernier element ajoute a la navigation
	 * @return array
	 */
	public static function nav($f_id, $args_ary, &$obj)
	{
		$nav = array();
		$sql = 'SELECT parent.f_id, parent.f_name, parent.f_color
			FROM ' . SQL_PREFIX . 'forums f
			INNER JOIN ' . SQL_PREFIX . 'forums parent
				ON f.f_left >= parent.f_left
					AND f.f_right <= parent.f_right
			WHERE f.f_id = ' . $f_id . '
			ORDER BY parent.f_level';
		$result = Fsb::$db->query($sql, 'forums_');
		$u = (isset($obj->is_low_page) && $obj->is_low_page) ? 'low&amp;mode=forum&amp;id=' : 'forum&amp;f_id=';
		while ($row = Fsb::$db->row($result))
		{
			$nav[] = array(
				'url' =>	sid(ROOT . 'index.' . PHPEXT . '?p=' . $u . $row['f_id']),
				'name' =>	$row['f_name'],
				'style' =>	($row['f_color']) ? $row['f_color'] : 'class="forum"',
			);
		}
		Fsb::$db->free($result);

		$nav = array_merge($nav, $args_ary);
		if ($obj && isset($obj->tag_title) && $nav)
		{
			$obj->tag_title = $nav[count($nav) - 1]['name'] . ' :: ' . Fsb::$cfg->get('forum_name');
		}

		return ($nav);
	}

	/**
	 * Obtiens les ID des forums autorises.
	 * Cette fonction se base sur le cache de la session.
	 *
	 * @param array $auths Contient dans un tableau la liste des droits necessaires pour que le forum soit ajoute a la liste
	 * @return array Tableau d'IDs de forums
	 */
	public static function get_authorized($auths)
	{
		$access = (isset(Fsb::$session->data['s_forum_access'])) ? array_flip(explode(',', Fsb::$session->data['s_forum_access'])) : array();
		$return = array(9999999);
		foreach (Fsb::$session->data['auth'] AS $f_id => $value)
		{
			if (is_int($f_id))
			{
				// Droits sufisants ?
				foreach ($auths AS $auth)
				{
					if (!Fsb::$session->is_authorized($f_id, $auth))
					{
						continue 2;
					}
				}

				// Forum protege par un mot de passe ?
				if (isset($value['f_password']) && !isset($access[$f_id]))
				{
					continue;
				}

				// On ajoute l'ID du forum aux ID autorisees
				$return[] = $f_id;
			}
		}
		return ($return);
	}

	/**
	 * Ajoute un forum dans la base de donnee
	 *
	 * @param int $parent Parent du forum
	 * @param array $var Tableau de donnees a inserer dans le forum
	 * @return int ID du nouveau forum
	 */
	public static function add($parent, $var)
	{
		$last_id = Sql_interval::put($parent, $var);
		Fsb::$db->destroy_cache('forums_');
		return ($last_id);
	}

	/**
	 * Met a jour un forum.
	 *
	 * @param int $id ID du forum
	 * @param int $parent Nouveau parent du forum
	 * @param array $var Tableau de donnees du forum, a mettre a jour
	 */
	public static function update($id, $parent, $var)
	{
		$sql = 'SELECT f_parent
				FROM ' . SQL_PREFIX . 'forums
				WHERE f_id = ' . $id;
		$f_parent = Fsb::$db->get($sql, 'f_parent');

		$update_parent = Sql_interval::update($id, $parent, $var);
		Fsb::$db->destroy_cache('forums_');

		if ($update_parent)
		{
			Sync::forums(array($f_parent, $id));
		}
	}

	/**
	 * Permet de deplacer un forum vers le haut ou vers le bas
	 *
	 * @param int $id ID du forum
	 * @param int $direction Direction du mouvement, 1 pour le bas et -1 pour le haut
	 * @return string Nom du forum deplace
	 */
	public static function move($id, $direction)
	{
		Sql_interval::move($id, $direction);
		$sql = 'SELECT f_name
			FROM ' . SQL_PREFIX . 'forums
			WHERE f_id = ' . $id;
		$f_name = Fsb::$db->get($sql, 'f_name');

		return ($f_name);
	}

	/**
	 * Supprime un forum, ses sous forums, ainsi que l'ensemble des donnees liees a ce forum
	 *
	 * @param int $id ID du forum a supprimer
	 */
	public static function delete($id)
	{
		// On recupere les enfants du forum
		$childs = Sql_interval::get_childs($id);
		$list_childs = implode(', ', $childs);

		if ($childs)
		{
			// Debut de transaction SQL
			Fsb::$db->transaction('begin');

			// Suppression des tables ne contenant pas explicitement de champs f_id
			Fsb::$db->delete_tables('posts', 'f_id IN (' . $list_childs . ')', array(
				'p_id' =>	array('posts_abuse', 'search_match'),
				't_id' =>	array('poll', 'poll_options', 'poll_result', 'topics_notification', 'topics_read'),
			));

			// Mise a jour des couleurs et des droits de moderation
			// Liste des utilisateurs dont les droits seront affectes par la suppression de ce forum
			$sql = 'SELECT DISTINCT gu.u_id
					FROM ' . SQL_PREFIX . 'groups_users gu
					LEFT JOIN ' . SQL_PREFIX . 'groups g
						ON g.g_id = gu.g_id
					LEFT JOIN ' . SQL_PREFIX . 'groups_auth ga
						ON ga.g_id = g.g_id
					WHERE g.g_type <> ' . GROUP_SPECIAL . '
						AND ga.f_id IN (' . $list_childs . ')';
			$result = Fsb::$db->query($sql);
			$gu_idx = array();
			while ($row = Fsb::$db->row($result))
			{
				$gu_idx[] = $row['u_id'];
			}
			Fsb::$db->free($result);

			// Suppression des entrees dans les tables disposant d'un champ f_id
			$delete_entry = array('groups_auth', 'posts', 'topics');
			foreach ($delete_entry AS $table)
			{
				$sql = 'DELETE FROM ' . SQL_PREFIX . $table . ' WHERE f_id IN(' . $list_childs . ')';
				Fsb::$db->query($sql);
			}

			// Mise a jour des couleurs et des droits de moderation
			if ($gu_idx)
			{
				Group::update_auths($gu_idx);
			}

			// Suppression des forums dans l'interval
			Sql_interval::delete($id);

			Sync::forums();
			Sync::total_posts();
			Sync::total_topics();
			Sync::signal(Sync::ABUSE);

			// Fin de la transaction
			Fsb::$db->transaction('commit');
		}
	}

	/**
	 * Delestage automatique des sujets des forums
	 *
	 * @param int $f_id ID de forum si on souhaite delester un forum particulier
	 */
	public static function auto_prune($f_id = null)
	{
		// On recupere les ID des sujets a delester
		$sql_f_id = ($f_id) ? "f_id = $f_id AND " : '';
		$sql = 'SELECT t.t_id, t.t_type, f.f_id, f.f_prune_topic_type
				FROM ' . SQL_PREFIX . 'topics t
				INNER JOIN ' . SQL_PREFIX . 'forums f
					ON f.f_id = t.f_id
				WHERE ' . $sql_f_id . ' f.f_prune_time > 0
					AND t.t_last_p_time < ' . CURRENT_TIME . ' - f.f_prune_time';
		$result = Fsb::$db->query($sql);
		$list_idx = '';
		$cache_forums = array();
		$updated_forums = array();
		while ($row = Fsb::$db->row($result))
		{
			if (!isset($cache_forums[$row['f_id']]))
			{
				$cache_forums[$row['f_id']] = array_flip(explode(',', $row['f_prune_topic_type']));
				$updated_forums[] = $row['f_id'];
			}

			if (isset($cache_forums[$row['f_id']][$row['t_type']]))
			{
				$list_idx .= $row['t_id'] . ',';
			}
		}
		$list_idx = substr($list_idx, 0, -1);
		Fsb::$db->free($result);

		if ($list_idx)
		{
			// Suppression des tables ne contenant pas explicitement de champs t_id
			Fsb::$db->delete_tables('posts', 't_id IN (' . $list_idx . ')', array(
				'p_id' =>	array('posts_abuse', 'search_match'),
			));

			// On supprime les sujets delestes des tables contenant un champ t_id
			$delete_tables = array('poll', 'poll_options', 'poll_result', 'topics', 'topics_notification', 'topics_read');
			foreach ($delete_tables AS $table)
			{
				$sql = 'DELETE FROM ' . SQL_PREFIX . $table . ' WHERE t_id IN (' . $list_idx . ')';
				Fsb::$db->query($sql);
			}

			// On reconstruit les donnees des forums (dernier message, etc ...)
			if ($updated_forums)
			{
				Sync::forums($updated_forums);
			}
		}
	}

	/**
	 * Ajoute des permissions par defaut a un forum
	 *
	 * @param int $f_id ID du forum
	 * @param int $auth_id ID du forum dont on veut les permissions
	 */
	public static function set_default_auth($f_id, $auth_id)
	{
		// On recupere les permissions
		$sql = 'SELECT *
			FROM ' . SQL_PREFIX . 'groups_auth
			WHERE f_id = ' . $auth_id;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$insert_array = $row;
			$insert_array['f_id'] = $f_id;
			Fsb::$db->insert('groups_auth', $insert_array, 'INSERT', true);
		}
		Fsb::$db->free($result);

		// Insertion des nouveaux droits
		Fsb::$db->query_multi_insert();
		Fsb::$db->destroy_cache('groups_auth_');
	}

	/**
	 * Recupere une liste des moderateurs pour chaque forum et les affiche
	 *
	 * @param int $f_id ID du forum en question
	 * @param bool $limit Si $limit vaut true il s'agira d'une recherche de moderateur sur ce forum la uniquement
	 */
	public static function get_moderators($f_id, $limit = false)
	{
		static $modo_list = null;

		if (is_null($modo_list))
		{
			$modo_list = array();

			// Requete recuperant la liste des groupes moderants un forum
			$sql = 'SELECT ga.f_id, ga.g_id, g.g_name, g.g_type, g.g_color, u.u_id, u.u_nickname, u.u_color
					FROM ' . SQL_PREFIX . 'groups_auth ga
					LEFT JOIN ' . SQL_PREFIX . 'groups g
						ON g.g_id = ga.g_id
					LEFT JOIN ' . SQL_PREFIX . 'users u
						ON u.u_single_group_id = g.g_id
					WHERE (g.g_type = ' . GROUP_SINGLE . '
						OR g.g_type = ' . GROUP_NORMAL . ') AND ga.ga_moderator = 1 ' .
					((Fsb::$session->auth() >= MODOSUP) ? 'AND g.g_hidden = 0' : '') .
					(($limit) ? ' AND ga.f_id = ' . $f_id : '') .
					' GROUP BY ga.f_id, ga.g_id, g.g_name, g.g_type, g.g_color, u.u_id, u.u_nickname, u.u_color
					ORDER BY ga.f_id, ga.g_id, g.g_type';
			$result = Fsb::$db->query($sql, 'groups_auth_');
			while ($row = Fsb::$db->row($result))
			{
				if (!isset($modo_list[$row['f_id']]))
				{
					$modo_list[$row['f_id']] = array();
				}

				if ($row['g_type'] == GROUP_SINGLE)
				{
					$url_moderator =	'userprofile&amp;id=' . $row['u_id'];
					$group_name =		$row['u_nickname'];
					$style =			$row['u_color'];
				}
				else
				{
					$url_moderator =	'userlist&amp;g_id=' . $row['g_id'];
					$group_name =		$row['g_name'];
					$style =			$row['g_color'];
				}

				$modo_list[$row['f_id']][] = array(
					'name' =>		$group_name,
					'url' =>		sid(ROOT . 'index.' . PHPEXT . '?p=' . $url_moderator),
					'style' =>		$style,
				);
			}
			Fsb::$db->free($result);
			unset($select);
		}

		// Affichage des moderateurs du forum
		if (isset($modo_list[$f_id]))
		{
			foreach ($modo_list[$f_id] AS $moderator)
			{
				Fsb::$tpl->set_blocks('cat.forum.moderator', array(
					'NAME' =>		htmlspecialchars($moderator['name']),
					'URL' =>		$moderator['url'],
					'STYLE' =>		$moderator['style'],
				));
			}
		}
	}
}

/* EOF */
