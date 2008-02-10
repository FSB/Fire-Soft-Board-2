<?php
/*
** +---------------------------------------------------+
** | Name :			~/main/class/class_send.php
** | Begin :		04/10/2005
** | Last :			10/12/2007
** | User :			Genova
** | Project :		Fire-Soft-Board 2 - Copyright FSB group
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Classe permettant l'insertion de messages, sujets, messages privés dans la base
** de donnée du forum
*/
class Send extends Fsb_model
{
	//
	// Méthodes liées à l'envoie de messages privés
	//

	/*
	** Envoie un message privé
	** -----
	** $from_id ::			ID de l'expéditeur
	** $to_id ::			ID du recepteur (possibilité de passer un tableau d'ID)
	** $title ::			Titre du message privé
	** $content ::			Contenu du message privé
	** $parent ::			ID du parent de ce MP s'il s'agit d'une réponse
	** $is_xml ::			Si le message est déjà au format XML
	*/
	public static function send_mp($from_id, $to_id, $title, $content, $parent = 0, $is_xml = FALSE)
	{
		// On met au format XML ?
		if (!$is_xml)
		{
			$message = new Xml();
			$message->document->setTagName('root');
			$message_line = $message->document->createElement('line');
			$message_line->setAttribute('name', 'description');
			$message_line->setData(htmlspecialchars($content));
			$message->document->appendChild($message_line);
			$content = $message->document->asValidXML();
			unset($message);
		}

		if (!$to_id)
		{
			return (FALSE);
		}

		// Liste des recepteurs
		if (!is_array($to_id))
		{
			$to_id = array($to_id);
		}
		$list_id = implode(', ', $to_id);
		
		// On regarde si le recepteur a blacklisté l'expéditeur
		$is_blacklist = array();
		$sql = 'SELECT blacklist_to_id
				FROM ' . SQL_PREFIX .'mp_blacklist
				WHERE blacklist_from_id = ' . $from_id . '
					AND blacklist_to_id IN (' . $list_id . ')';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$is_blacklist[$row['blacklist_to_id']] = TRUE;
		}
		Fsb::$db->free($result);

		// On récupère les données des recepteurs, pour savoir qui va recevoir un Email de notification,
		// et qui va envoyer un joli message de répondeur.
		$sql = 'SELECT u_id, u_nickname, u_email, u_language, u_total_mp, u_activate_mp_notification, u_mp_auto_answer_activ, u_mp_auto_answer_message
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id IN (' . $list_id . ')
					AND u_id <> ' . VISITOR_ID . '
					' . (($is_blacklist) ? 'AND u_id NOT IN (' . implode(', ', array_keys($is_blacklist)) . ')' : '');
		$result = Fsb::$db->query($sql);
		$notify_list = array();
		$total_auto_answer = 0;
		while ($row = Fsb::$db->row($result))
		{
			if ($row['u_activate_mp_notification'])
			{
				if (!isset($notify_list[$row['u_language']]))
				{
					$notify_list[$row['u_language']] = array();
				}
				$notify_list[$row['u_language']][] = $row['u_email'];
				$sql_notification[] = $row['u_id'];
			}

			if ($row['u_mp_auto_answer_activ'] && $from_id <> VISITOR_ID)
			{
				// Génération XML du message du répondeur
				$message = new Xml();
				$message->document->setTagName('root');
				$message_line = $message->document->createElement('line');
				$message_line->setAttribute('name', 'description');
				$message_line->setData(htmlspecialchars($row['u_mp_auto_answer_message']));
				$message->document->appendChild($message_line);
				$auto_answer = $message->document->asValidXML();
				unset($message);

				Fsb::$db->insert('mp', array(
					'mp_from' =>		(int) $row['u_id'],
					'mp_to' =>			(int) $from_id,
					'mp_title' =>		Fsb::$session->lang('auto_answer_reply') . ': ' . $title,
					'mp_content' =>		$auto_answer,
					'mp_type' =>		MP_INBOX,
					'mp_read' =>		MP_UNREAD,
					'mp_time' =>		CURRENT_TIME,
					'mp_parent' =>		$parent,
					'u_ip' =>			Fsb::$session->ip,
					'is_auto_answer' =>	TRUE,
				), 'INSERT', TRUE);
				$total_auto_answer++;
			}
		}
		Fsb::$db->free($result);

		// Transaction SQL
		Fsb::$db->transaction('begin');
		
		// On effectue deux insertions du message privé dans la base de donnée, la première pour la personne
		// qui va le recevoir, la seconde pour la boite d'envoie de l'expéditeur.
		// Si le recepteur a blacklisté l'expéditeur, on ne poste pas de message pour le recepteur, mais on poste celui
		// pour la boite de reception de l'expéditeur.
		foreach ($to_id AS $id)
		{
			foreach (array(MP_INBOX, MP_OUTBOX) AS $mp_type)
			{
				if (!isset($is_blacklist[$id]) || !$is_blacklist[$id] || ($is_blacklist[$id] && $mp_type == MP_OUTBOX))
				{
					Fsb::$db->insert('mp', array(
						'mp_from' =>		(int) $from_id,
						'mp_to' =>			(int) $id,
						'mp_title' =>		$title,
						'mp_content' =>		$content,
						'mp_type' =>		(int) $mp_type,
						'mp_read' =>		MP_UNREAD,
						'mp_time' =>		CURRENT_TIME,
						'mp_parent' =>		$parent,
						'u_ip' =>			Fsb::$session->ip,
						'is_auto_answer' =>	FALSE,
					), 'INSERT', TRUE);
				}
			}
		}

		// Allez hop on insère la floppée de messages dans la base de donnée
		Fsb::$db->query_multi_insert();

		// On ajoute un message privé au nombre de messages du membre, sauf si blacklisté
		Fsb::$db->update('users', array(
			'u_total_mp' =>		array('u_total_mp + 1', 'is_field' => TRUE),
			'u_new_mp' =>		TRUE,
		), 'WHERE u_id IN (' . $list_id . ')' . (($is_blacklist) ? ' AND u_id NOT IN (' . implode(', ', array_keys($is_blacklist)) . ')' : ''));

		// Si des messages de répondeurs ont été envoyés, on le signale a l'expéditeur
		if ($total_auto_answer)
		{
			Fsb::$db->update('users', array(
				'u_total_mp' =>		array('u_total_mp + ' . $total_auto_answer, 'is_field' => TRUE),
				'u_new_mp' =>		TRUE,
			), 'WHERE u_id = ' . $from_id);
		}

		// Fin de transaction SQL
		Fsb::$db->transaction('commit');

		// On regarde qui doit être notifié par Email, et on leur envoie l'Email
		if ($notify_list)
		{
			$sql = 'SELECT u_nickname
					FROM ' . SQL_PREFIX . 'users
					WHERE u_id = ' . $from_id;
			$sender_nickname = Fsb::$db->get($sql, 'u_nickname');

			foreach ($notify_list AS $mail_lang => $mail_list)
			{
				$notify = new Notify(NOTIFY_MAIL);
				foreach ($mail_list AS $bcc)
				{
					$notify->add_bcc($bcc);
				}

				$notify->set_subject(Fsb::$session->lang('subject_notify_mp'));
				$notify->set_template(ROOT . 'lang/' . $mail_lang . '/mail/notify_mp.txt');
				$notify->set_vars(array(
					'LOGIN' =>			$sender_nickname,
					'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
					'FORUM_URL' =>		Fsb::$cfg->get('fsb_path'),
					'FORUM_SIG' =>		Fsb::$cfg->get('forum_sig'),
					'URL_INBOX' =>		Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=mp&box=inbox',
				));
				$notify->put();
				unset($notify);
			}
		}
	}

	/*
	** Edite un message privé
	** -----
	** $id ::		ID du message
	** $title ::	Titre du mesage
	** $content ::	Contenu du message
	*/
	public static function edit_mp($id, $title, $content)
	{
		Fsb::$db->update('mp', array(
			'mp_title' =>		$title,
			'mp_content' =>		$content,
		), 'WHERE mp_id = ' . $id);
	}

	//
	// Méthodes liées à l'envoie de messages / sujets sur le forum
	//
	
	/*
	** Créer un nouveau sujet, retourne l'ID de celui ci
	** -----
	** $forum_id ::		ID du forum où est posté le sujet
	** $user_id ::		ID du posteur du topic
	** $title ::		Titre du sujet
	** $map_name ::		Nom de la MAP pour le sujet
	** $type ::			Type du sujet
	** $args ::			Tableau d'argument suplémentaire
	*/
	public static function send_topic($forum_id, $user_id, $title, $map_name, $type, $args = array())
	{
		Fsb::$db->insert('topics', array_merge(array(
			'f_id' =>			(int) $forum_id,
			'u_id' =>			(int) $user_id,
			't_title' =>		$title,
			't_time' =>			CURRENT_TIME,
			't_total_view' =>	0,
			't_total_post' =>	0,
			't_map' =>			$map_name,
			't_type' =>			(int) $type,
			't_status' =>		UNLOCK,
		), $args));
		return (Fsb::$db->last_id());
	}
	
	/*
	** Poste un message et retourne l'ID du message créé
	** -----
	** $user_id ::			ID du posteur du message
	** $topic_id ::			ID du sujet
	** $forum_id ::			ID du forum
	** $content ::			Message
	** $nickname ::			Pseudonyme du posteur
	** $approve ::			TRUE si le message doit être approuvé
	** $post_map ::			MAP du message
	** $ary_content ::		Données suplémentaires
	** $is_first_post ::	Défini s'il s'agit du premier message du sujet ou non
	*/
	public static function send_post($user_id, $topic_id, $forum_id, $content, $nickname, $approve, $post_map, $ary_content, $is_first_post = FALSE)
	{
		// Données du forum
		$sql = 'SELECT f_left, f_right
			FROM ' . SQL_PREFIX . 'forums
			WHERE f_id = ' . $forum_id;
		$result = Fsb::$db->query($sql);
		$select = Fsb::$db->row($result);
		Fsb::$db->free($result);

		Fsb::$db->transaction('begin');

		// On insère le message dans la base de donnée
		Fsb::$db->insert('posts', array(
			'f_id' =>		(int) $forum_id,
			't_id' =>		(int) $topic_id,
			'p_text' =>		$content,
			'p_time' =>		CURRENT_TIME,
			'p_nickname' =>	$nickname,
			'u_id' =>		(int) $user_id,
			'u_ip' =>		Fsb::$session->ip,
			'p_approve' =>	$approve,
			'p_map' =>		$post_map,
		));
		$post_id = Fsb::$db->last_id();

		if ($approve == IS_APPROVED)
		{
			// On met à jour le sujet du message
			$update_array = array(
				't_last_p_id' =>		(int) $post_id,
				't_last_p_time' =>		CURRENT_TIME,
				't_last_u_id' =>		(int) $user_id,
				't_last_p_nickname' =>	$nickname,
				't_total_post' =>		array('(t_total_post + 1)', 'is_field' => TRUE),
			);

			// Mise à jour des données du forum
			$forum_update_array = array(
				'f_total_post' =>		array('(f_total_post + 1)', 'is_field' => TRUE),
				'f_last_p_id' =>		(int) $post_id,
				'f_last_t_title' =>		$ary_content['t_title'],
				'f_last_t_id' =>		$topic_id,
				'f_last_u_id' =>		$user_id,
				'f_last_p_nickname' =>	$nickname,
				'f_last_p_time' =>		CURRENT_TIME,
			);

			// mise à jour des données du membre
			$user_update_array = array(
				'u_total_post' =>	array('(u_total_post + 1)', 'is_field' => TRUE),
			);
		
			// S'il s'agit du premier message on lie le topic à celui ci, et on ajoute + 1 au total de sujets sur le forum
			if ($is_first_post)
			{
				$update_array['t_first_p_id'] = $post_id;
				$forum_update_array['f_total_topic'] = array('(f_total_topic + 1)', 'is_field' => TRUE);
				$user_update_array['u_total_topic'] = array('(u_total_topic + 1)', 'is_field' => TRUE);

				// Mise à jour du nombre total de sujets
				Fsb::$cfg->update('total_topics', Fsb::$cfg->get('total_topics') + 1);

				// Mise à jour du nombre d'annonce globale ?
				if (isset($ary_content['t_type']) && $ary_content['t_type'] == GLOBAL_ANNOUNCE)
				{
					Fsb::$cfg->update('total_global_announce', Fsb::$cfg->get('total_global_announce') + 1);
				}
			}

			// Si le membre est connecté, on à jour sa limite de flood
			if ($user_id <> VISITOR_ID)
			{
				$user_update_array['u_flood_post'] = CURRENT_TIME;
			}
		
			Fsb::$db->update('topics', $update_array, 'WHERE t_id = ' . intval($topic_id));
			Fsb::$db->update('forums', $forum_update_array, 'WHERE f_left <= ' . $select['f_left'] . ' AND f_right >= ' . $select['f_right']);
			Fsb::$db->update('users', $user_update_array, 'WHERE u_id = ' . $user_id);
			Fsb::$cfg->update('total_posts', Fsb::$cfg->get('total_posts') + 1);

			// Le message est mis comme lu pour le membre.
			Fsb::$db->insert('topics_read', array(
				'u_id' =>			array((int) $user_id, TRUE),
				't_id' =>			array((int) $topic_id, TRUE),
				'p_id' =>			(int) $post_id,
				'tr_last_time' =>	CURRENT_TIME,
			), 'REPLACE');
		}
		else
		{
			if ($is_first_post)
			{
				Fsb::$db->update('topics', array(
					't_first_p_id'	=>		$post_id,
					't_last_p_id' =>		(int) $post_id,
					't_last_p_time' =>		CURRENT_TIME,
					't_last_u_id' =>		(int) $user_id,
					't_last_p_nickname' =>	$nickname,
					't_total_post' =>		array('(t_total_post + 1)', 'is_field' => TRUE),
				), 'WHERE t_id = ' . intval($topic_id));
			}

			Sync::signal(Sync::APPROVE);
		}

		Fsb::$db->destroy_cache('forums_');
		Fsb::$db->transaction('commit');

		// Ajout des mots du message dans la recherche
		if (Fsb::$cfg->get('search_method') == 'fulltext_fsb')
		{
			$search = new Search_fulltext_fsb();
			$content = str_replace('<![CDATA[', '', $content);
			$search->index($post_id, preg_replace('#<[^!][^>]*?>#si', ' ', $content), FALSE);

			// Indexation du titre
			if ($ary_content['t_title'])
			{
				$search->index($post_id, $ary_content['t_title'], TRUE);
			}
		}

		// Envoie d'E-mail aux membres surveillant le sujet
		if (Fsb::$mods->is_active('topic_notification') && $approve == IS_APPROVED)
		{
			self::topic_notification($topic_id, $post_id, Fsb::$session->id(), $ary_content['t_title']);
		}

		return ($post_id);
	}

	/*
	** Met à jour le contenu d'un message
	** -----
	** $post_id ::		ID du message
	** $content ::		Contenu du message
	** $user_id ::		ID du membre ayant mis à jour le message
	** $args ::			Arguments aditionels
	*/
	public static function edit_post($post_id, $content, $user_id, $args = array())
	{
		Fsb::$db->update('posts', array(
			'p_text' =>			$content,
			'p_edit_user_id' =>	(int) $user_id,
			'p_edit_time' =>	CURRENT_TIME,
			'p_edit_total' =>	array('(p_edit_total + 1)', 'is_field' => TRUE),
		), "WHERE p_id = $post_id");

		if (isset($args['update_topic']) && $args['update_topic'])
		{
			Fsb::$db->update('topics', array(
				't_title' =>		$args['t_title'],
				't_type' =>			(int) $args['t_type'],
				't_description' =>	$args['t_description'],
			), 'WHERE t_id = ' . $args['t_id']);

			Fsb::$db->update('forums', array(
				'f_last_t_title' =>	$args['t_title'],
			), 'WHERE f_last_t_id = ' . $args['t_id']);
		}

		// Suppression des anciens index du message, puis ajout des nouveaux pour la recherche
		if (Fsb::$cfg->get('search_method') == 'fulltext_fsb')
		{
			$search = new Search_fulltext_fsb();
			$search->delete_index($post_id);
			$search->index($post_id, preg_replace('#<[^!][^>]*?>#si', ' ', $content), FALSE);
			$search->index($post_id, $args['t_title'], TRUE);
		}
	}

	/*
	** Envoie une notification aux membres surveillant le sujet.
	** L'algorithme de cette fonction est inspiré de celui du forum phpBB 2.0.x
	** -----
	** $t_id ::			ID du sujet
	** $p_id ::			ID du message
	** $u_id ::			ID du membre
	** $t_title ::		Titre du sujet
	*/
	public static function topic_notification($t_id, $p_id, $u_id, $t_title)
	{
		$sql_notification = array();
		$notify_list =		array();

		// On récupère la liste des membres à notifier, classés par langue
		$sql = 'SELECT u.u_id, u.u_email, u.u_language
				FROM ' . SQL_PREFIX . 'topics_notification tn
				INNER JOIN ' . SQL_PREFIX . 'users u
					ON tn.u_id = u.u_id
				WHERE u.u_activate_auto_notification & ' . NOTIFICATION_EMAIL . ' = ' . NOTIFICATION_EMAIL . '
					AND tn.t_id = ' . $t_id . '
					AND tn.u_id <> ' . $u_id . '
					AND tn.tn_status = ' . IS_NOT_NOTIFIED;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			if (!isset($notify_list[$row['u_language']]))
			{
				$notify_list[$row['u_language']] = array();
			}
			$notify_list[$row['u_language']][] = $row['u_email'];
			$sql_notification[] = $row['u_id'];
		}
		Fsb::$db->free($result);

		// On envoie autant de notifications qu'il y a de langues différentes au sein des membres à notifier.
		// Pour chaque langue on envoie le mail en copie caché afin de tout envoyer en une fois, donc sur
		// un forum avec une unique langue d'installée (car c'est la plupart du temps le cas), un seul mail
		// sera envoyé.
		foreach ($notify_list AS $mail_lang => $mail_list)
		{
			$notify = new Notify(NOTIFY_MAIL);
			foreach ($mail_list AS $bcc)
			{
				$notify->add_bcc($bcc);
			}

			$notify->set_subject(sprintf(Fsb::$session->lang('subject_notify_post'), $t_title));
			$notify->set_template(ROOT . 'lang/' . $mail_lang . '/mail/notify_post.txt');
			$notify->set_vars(array(
				'TOPIC_NAME' =>		htmlspecialchars($t_title),
				'TOPIC_URL' =>		Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=topic&t_id=' . $t_id,
				'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
				'FORUM_URL' =>		Fsb::$cfg->get('fsb_path'),
				'FORUM_SIG' =>		Fsb::$cfg->get('forum_sig'),

				'U_STOP_NOTIFY' =>	Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=topic&t_id=' . $t_id . '&notification=off',
				'U_REPLY' =>		Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=topic&p_id=' . $p_id . '#p' . $p_id,
			));
			$notify->put();
			unset($notify);
		}

		// On met à jour les membres qui viennent d'être notifiés
		if ($sql_notification)
		{
			Fsb::$db->update('topics_notification', array(
				'tn_status' =>	IS_NOTIFIED,
			), 'WHERE u_id IN(' . implode(', ', $sql_notification) . ') AND t_id = ' . $t_id);
		}
	}

	//
	// Méthodes liées à l'envoie d'évènement pour le calendrier
	//

	/*
	** Ajoute un évênement dans le calendrier
	** -----
	** $title ::			Titre
	** $content ::			Message
	** $timestamp_begin ::	Timestamp unix de départ
	** $timestamp_end ::	Timestamp unix de fin
	** $print ::			Afficher pour tous ou bien juste pour le membre
	*/
	public static function calendar_add_event($title, $content, $timestamp_begin, $timestamp_end, $print)
	{
		Fsb::$db->insert('calendar', array(
			'c_begin' =>		(int) $timestamp_begin,
			'c_end' =>			(int) $timestamp_end,
			'u_id' =>			(int) Fsb::$session->id(),
			'c_title' =>		$title,
			'c_content' =>		$content,
			'c_view' =>			(int) $print,
			'c_approve' =>		(Fsb::$session->is_authorized('approve_event') || !$print) ? TRUE : FALSE,
		));
		Fsb::$db->destroy_cache('calendar_');

		return (Fsb::$db->last_id());
	}

	/*
	** Edit un évênement dans le calendrier
	** -----
	** $id ::				ID de l'évênement
	** $title ::			Titre
	** $content ::			Message
	** $timestamp_begin ::	Timestamp unix de départ
	** $timestamp_end ::	Timestamp unix de fin
	** $print ::			Afficher pour tous ou bien juste pour le membre
	*/
	public static function calendar_edit_event($id, $title, $content, $timestamp_begin, $timestamp_end, $print)
	{
		Fsb::$db->update('calendar', array(
			'c_begin' =>		(int) $timestamp_begin,
			'c_end' =>			(int) $timestamp_end,
			'c_view' =>			(int) $print,
			'c_title' =>		$title,
			'c_content' =>		$content,
		), 'WHERE c_id = ' . $id);
		Fsb::$db->destroy_cache('calendar_');
	}
}

/* EOF */