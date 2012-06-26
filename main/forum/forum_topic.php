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
 * Affiche la liste des messages d'un sujet
 *
 */
class Fsb_frame_child extends Fsb_frame
{
	/**
	 * Affichage de la barre de navigation du header
	 *
	 * @var bool
	 */
	public $_show_page_header_nav = true;
	
	/**
	 * Affichage de la barre de navigation du footer
	 *
	 * @var bool
	 */
	public $_show_page_footer_nav = true;
	
	/**
	 * Affichage de la boite des stats
	 *
	 * @var bool
	 */
	public $_show_page_stats = false;

	/**
	 * Navigation
	 *
	 * @var array
	 */
	public $nav = array();

	/**
	 * Titre de la page
	 *
	 * @var string
	 */
	public $tag_title = '';

	/**
	 * ID du sujet
	 *
	 * @var int
	 */
	public $topic_id;
	
	/**
	 * ID du message
	 *
	 * @var int
	 */
	public $post_id;
	
	/**
	 * Donnees du sujet courant
	 *
	 * @var array
	 */
	public $topic_data;

	/**
	 * Page courante
	 *
	 * @var int
	 */
	public $page;

	/**
	 * Surveiller ou on le sujet
	 *
	 * @var string
	 */
	public $notification = 'off';

	/**
	 * Imprimer le sujet
	 *
	 * @var string
	 */
	public $print = '';

	/**
	 * Peut utiliser la reponse rapide
	 *
	 * @var bool
	 */
	public $can_quick_reply = false;

	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		$this->notification =		Http::request('notification');
		$this->print =				Http::request('print');
		$this->topic_id =			intval(Http::request('t_id'));
		$this->post_id =			intval(Http::request('p_id'));
		$this->page =				intval(Http::request('page'));

		if ($this->page <= 0)
		{
			$this->page = 1;
		}

		// Redirection de la jumpbox
		if (Http::request('noscript_list_forums', 'post'))
		{
			Html::jumpbox(true);
		}

		// Donnees du sujet
		if (!$this->get_topic_data())
		{
			return ;
		}

		// Si le membre est invite on desactive la notification
		if (!Fsb::$session->is_logged())
		{
			Fsb::$mods->change_status('topic_notification', false);
		}

		// Notification
		if ($this->notification && Fsb::$mods->is_active('topic_notification'))
		{
			$this->change_notification();
		}
	
		// Mise a jour du sujet en lu et du nombre de vu
		$this->update_topic();

		// Le sujet comporte un sondage ?
		if ($this->topic_data['t_poll'] == TOPIC_POLL)
		{
			// Soumission d'un vote ?
			if (Http::request('submit_poll', 'post'))
			{
				Poll::submit_vote($this->topic_data['t_id']);
			}

			// Affichage du sondage
			Poll::show($this->topic_data['t_id'], $this->topic_data);
		}

		// Affichage des messages
		$this->show_posts();
	}

	/**
	 * Recupere les donnees du sujet courant
	 *
	 * @return bool
	 */
	public function get_topic_data()
	{
		// Creation de la requete SELECT de recuperations des donnees du sujet
		$select = new Sql_select();

		// Donnees du sujet, si l'ID du topic est passee en parametre on a directement acces a ses donnees
		if ($this->topic_id)
		{
			$select->join_table('FROM', 'topics t', 't.*');
			$select->where('t.t_id = ' . $this->topic_id);
		}
		else
		{
			$select->join_table('FROM', 'posts p');
			$select->join_table('INNER JOIN', 'topics t', 't.*', 'ON p.t_id = t.t_id');
			$select->where('p.p_id = ' . intval($this->post_id));
		}

		// Si la fonction de notification est activee on ajoute une jointure a la requete
		if (Fsb::$mods->is_active('topic_notification'))
		{
			$select->join_table('LEFT JOIN', 'topics_notification tn', 'tn.u_id AS can_notify, tn.tn_status', 'ON tn.t_id = t.t_id AND tn.u_id = ' . Fsb::$session->id());
		}

		// Autres jointures (donnees du forum, et posteur du premier message)
		$select->join_table('LEFT JOIN', 'topics_read tr', 'tr.p_id AS last_unread_id', 'ON t.t_id = tr.t_id AND tr.u_id = ' . Fsb::$session->id());
		$select->join_table('LEFT JOIN', 'forums f', 'f.f_password, f.f_tpl, f.f_status, f.f_rules', 'ON t.f_id = f.f_id');

		// Resultat de la requete
		$result = $select->execute();
		if (!$this->topic_data = Fsb::$db->row($result))
		{
			Display::message('topic_not_exists');
		}
		Fsb::$db->free($result);
		unset($select);

		// Message non approuve ?
		if ($this->topic_data['t_approve'] == IS_NOT_APPROVED)
		{
			Display::message('topic_not_approved');
		}

		// On verifie si le membre peut acceder a ce sujet (droit de lecture du forum + droit de lecture des sujets)
		if (!Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_view') || !Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_view_topics') || !Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_read'))
		{
			if (!Fsb::$session->is_logged())
			{
				Http::redirect(ROOT . 'index.' . PHPEXT . '?p=login&redirect=topic&t_id=' . $this->topic_data['t_id']);
			}
			else
			{
				Display::message('not_allowed');
			}
		}

		// Forum avec mot de passe ?
		if ($this->topic_data['f_password'] && !Display::forum_password($this->topic_data['f_id'], $this->topic_data['f_password'], ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $this->topic_data['t_id']))
		{
			// L'acces est refuse, on affiche le formulaire du mot de passe et on doit donc quitter la classe
			return (false);
		}

		// Theme pour le forum ?
		if ($this->topic_data['f_tpl'])
		{
			$set_tpl = ROOT . 'tpl/' . $this->topic_data['f_tpl'];
			Fsb::$session->data['u_tpl'] = $this->topic_data['f_tpl'];
			Fsb::$tpl->set_template($set_tpl . '/files/', $set_tpl . '/cache/');
		}

		// Navigation de la page
		$this->nav = Forum::nav($this->topic_data['f_id'], array(array(
			'url' =>		sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $this->topic_data['t_id']),
			'name' =>		Parser::title($this->topic_data['t_title']),
		)), $this);

		// Peut utiliser la reponse rapide ?
		if (Fsb::$mods->is_active('quick_reply') && Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_answer_' . $GLOBALS['_topic_type'][$this->topic_data['t_type']])
			&& ($this->topic_data['t_map'] == 'classic' || $this->topic_data['t_map_first_post'] != MAP_ALL_POST) && Fsb::$session->is_logged()
			&& $this->topic_data['f_status'] != LOCK && $this->topic_data['t_status'] != LOCK)
		{
			$this->can_quick_reply = true;
			Fsb::$tpl->set_switch('can_use_quick_reply');
		}

		if (!$this->topic_id)
		{
			// En passant l'ID du message en argument on doit se synchroniser sur la page du
			// message en question. Pour ce faire on recupere le nombre de messages avant celui ci
			$sql = 'SELECT COUNT(*) ' . ((Fsb::$mods->is_active('previous_post')) ? '+ 1' : '') . ' AS total_before
						FROM ' . SQL_PREFIX . 'posts
						WHERE t_id = ' . $this->topic_data['t_id'] . '
							AND p_id < ' . intval($this->post_id);
			$result = Fsb::$db->query($sql);
			$row = Fsb::$db->row($result);
			Fsb::$db->free($result);
			if ($row['total_before'] < $this->topic_data['t_total_post'])
			{
				$row['total_before']++;
			}
			$this->page = ($row['total_before'] > 0) ? ceil($row['total_before'] / Fsb::$cfg->get('post_per_page')) : 1;
		}

		return (true);
	}

	/**
	 * Affiche les messages
	 *
	 */
	public function show_posts()
	{
		Fsb::$tpl->set_file('forum/forum_topic.html');

		// En mode impression on change de modele de template
		$extra_format = true;
		if ($this->print && Fsb::$mods->is_active('print_topic'))
		{
			Fsb::$tpl->set_file('forum/forum_topic_print.html');

			// La coloration syntaxique est inutile pour l'impression
			Fsb::$mods->change_status('highlight_code', false);

			// On n'affiche pas le format extra pour les dates
			$extra_format = false;
		}

		// Pagination
		$total_page = ceil($this->topic_data['t_total_post'] / Fsb::$cfg->get('post_per_page'));

		$parser = new Parser();

		// Affichage du dernier message de la page precedente ?
		$show_last_post = ($this->page > 1 && Fsb::$mods->is_active('previous_post')) ? true : false;
		$offset = ($show_last_post) ? 1 : 0;

		// On commence par recuperer les ID des messages qui seront affiches
		$sql = 'SELECT p_id
				FROM ' . SQL_PREFIX . 'posts
				WHERE t_id = ' . $this->topic_data['t_id'] . '
					AND p_approve = 0
				ORDER BY p_time
				LIMIT ' . (($this->page - 1) * Fsb::$cfg->get('post_per_page') - $offset) . ', ' . (Fsb::$cfg->get('post_per_page') + $offset);
		$result = Fsb::$db->query($sql);
		$idx = array();
		while ($row = Fsb::$db->row($result))
		{
			$idx[] = $row['p_id'];
		}
		Fsb::$db->free($result);

		if (!$idx)
		{
			Display::message('no_result');
		}

		// Si la fonction affichant les champs personels du membre sur le sujet est activee ...
		$sql_fields_personal = $sql_join_personal = '';
		if (Fsb::$mods->is_active('profile_fields_topic'))
		{
			// Champs personalises
			Profil_fields_forum::topic_info($sql_fields_personal);
			$sql_fields_personal = ($sql_fields_personal) ? ', ' . $sql_fields_personal : '';
			$sql_join_personal = ' LEFT JOIN ' . SQL_PREFIX . 'users_personal up ON p.u_id = up.u_id ';
		}

		// Affichage des messages
		$sql = 'SELECT p.*, u.u_id, u.u_auth, u.u_avatar, u.u_avatar_method, u.u_can_use_avatar, u.u_signature, u.u_can_use_sig, u.u_rank_id, u.u_total_post, u.u_sexe, u.u_birthday, u.u_joined, u.u_last_visit, u.u_color, u.u_total_warning, u.u_activate_hidden, u.u_id, u_edit.u_nickname AS u_edit_nickname, u_edit.u_color AS u_edit_color' . $sql_fields_personal . '
				FROM ' . SQL_PREFIX . 'posts p
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON p.u_id = u.u_id
				LEFT JOIN ' . SQL_PREFIX . 'users u_edit
					ON p.p_edit_user_id = u_edit.u_id
				' . $sql_join_personal . '
				WHERE p.p_id IN (' . implode(', ', $idx) . ')
				ORDER BY p.p_time';
		$result = Fsb::$db->query($sql);

		$cache_post_array = array();
		$iterator = 0;
		while ($row = Fsb::$db->row($result))
		{
			// On recupere les donnees statiques du posteur
			if (isset($cache_post_array[$row['u_id']]))
			{
				$post_array = $cache_post_array[$row['u_id']];
			}
			else
			{
				$post_array = $this->get_post_array($row);
				$cache_post_array[$row['u_id']] = $post_array;
			}

			// Les invites ont un pseudo propre a chacuns
			if ($row['u_id'] == VISITOR_ID)
			{
				$post_array['data']['NICKNAME'] = Html::nickname($row['p_nickname'], $row['u_id'], $row['u_color']);
			}

			// Message edite ?
			$edit_str = '';
			if ($row['p_edit_time'] > 0)
			{
				$edit_nickname = Html::nickname($row['u_edit_nickname'], $row['p_edit_user_id'], $row['u_edit_color']);
				$edit_str = sprintf(Fsb::$session->lang('topic_edit_data'), $row['p_edit_total'], $edit_nickname, Fsb::$session->print_date($row['p_edit_time']));
			}

			// Informations passees au parseur de message
			$parser_info = array(
				'u_id' =>			$row['u_id'],
				'p_nickname' =>		$row['p_nickname'],
				'u_auth' =>			$row['u_auth'],
				'f_id' =>			$row['f_id'],
				't_id' =>			$row['t_id'],
			);

			// On peut parser le HTML ?
			$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;
			$content = $parser->mapped_message($row['p_text'], $row['p_map'], $parser_info);

			Fsb::$tpl->set_blocks('post', $post_array['data'] += array(
				'CONTENT' =>		$content,
				'DATE' =>			Fsb::$session->print_date($row['p_time'], true, null, $extra_format),
				'IP' =>				(Fsb::$session->is_authorized('auth_ip')) ? $row['u_ip'] : null,
				'ID' =>				$row['p_id'],
				'HAVE_EDIT' =>		($row['p_edit_time'] > 0) ? true : false,
				'EDIT_DATA' =>		$edit_str,
				'CAN_EDIT' =>		(Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_moderator') || (Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_edit') && $row['u_id'] == Fsb::$session->id() && $this->topic_data['t_status'] == UNLOCK && $this->topic_data['f_status'] == UNLOCK)) ? true : false,
				'CAN_QUICK_QUOTE' =>($this->can_quick_reply) ? true : false,
				'CAN_QUICK_EDIT' =>	($row['p_map'] == 'classic') ? true : false,
				'CAN_DELETE' =>		(Fsb::$session->can_delete_post($row['u_id'], $row['p_id'], $this->topic_data)) ? true : false,
				'IS_FIRST_POST' =>	($row['p_id'] == $this->topic_data['t_first_p_id']) ? true : false,
				'IS_READ' =>		(!$this->topic_data['last_unread_id'] || $row['p_id'] <= $this->topic_data['last_unread_id']) ? true : false,

				'U_LAST' =>			sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;p_id=' . $row['p_id']) . '#p' . $row['p_id'],
				'U_EDIT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=edit&amp;id=' . $row['p_id']),
				'U_DELETE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=delete&amp;id=' . $row['p_id']),
				'U_ABUSE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=abuse&amp;id=' . $row['p_id']),
				'U_QUOTE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=reply&amp;quote=' . $row['p_id'] . '&amp;id=' . $this->topic_data['t_id']),
				'U_IP' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=ip&amp;id=' . $row['p_id']),
			));

			// Balise META pour la description du sujet
			if ($iterator == 0)
			{
				$desc = String::substr(preg_replace('#<.*?>#si', ' ', htmlspecialchars($this->topic_data['t_description']) . ' ' . $content), 0, 150);
				$desc = str_replace(array("\r\n", "\n", "\r"), array(' ', ' ', ' '), $desc);
				$desc = preg_replace('#\s{2,}#', ' ', $desc);
				Http::add_meta('meta', array(
					'name' =>		'Description',
					'content' =>	$desc,
				));
			}

			// Affichage des avertissements ?
			if ($post_array['data']['CAN_WARN'])
			{
				for ($w = 4; $w >= 0; $w--)
				{
					Fsb::$tpl->set_blocks('post.warn', array(
						'IMG' =>	Fsb::$session->img('warn_' . (($row['u_total_warning'] > $w) ? 'on' : 'off')),
					));
				}
			}

			// Champs personels ?
			if (Fsb::$mods->is_active('profile_fields_topic'))
			{
				Profil_fields_forum::topic_show($post_array['fields']);
			}

			$iterator++;
		}
		Fsb::$db->free($result);
		unset($select);

		// Jumpbox
		$jumpbox = Html::jumpbox();
		

		// Notification ?
		$notify = (Fsb::$mods->is_active('topic_notification') && $this->topic_data['can_notify']) ? 'off' : 'on';

		// Pagination, seconde partie
		$pagination = Html::pagination($this->page, $total_page, ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $this->topic_data['t_id']);
		if ($total_page > 1)
		{
			Fsb::$tpl->set_switch('topic_pagination');
            $this->tag_title = Parser::title($this->topic_data['t_title']) . Fsb::$session->getStyle('other', 'title_separator') . Fsb::$session->lang('page') . ' ' . $this->page . Fsb::$session->getStyle('other', 'title_separator') . Fsb::$cfg->get('forum_name');
		}

		// On regarde si le membre peut creer des messages
		$can_create_post = false;
		foreach ($GLOBALS['_topic_type'] AS $value)
		{
			if (Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_create_' . $value))
			{
				$can_create_post = true;
				break;
			}
		}

		// Balises META pour les syndications RSS
		Http::add_meta('link', array(
			'rel' =>		'alternate',
			'type' =>		'application/rss+xml',
			'title' =>		Fsb::$session->lang('rss'),
			'href' =>		sid(ROOT . 'index.' . PHPEXT . '?p=rss&amp;mode=topic&amp;id=' . $this->topic_data['t_id']),
		));

		// Relation
		Http::add_relation($this->page, $total_page, ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $this->topic_data['t_id']);

		// Peut repondre au sujet ?
		$can_reply = false;
		if (Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_answer_' . $GLOBALS['_topic_type'][$this->topic_data['t_type']])
			&& (($this->topic_data['t_status'] != LOCK && $this->topic_data['f_status'] != LOCK) || Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_moderator')))
		{
			$can_reply = true;
			Fsb::$tpl->set_switch('can_reply');
		}

		// Redirection vers la page de connexion ?
		$redirect_login = (!Fsb::$session->is_logged()) ? 'login&amp;redirect=' : '';

		// Donnees du sujet
		$parser = new Parser();
		Fsb::$tpl->set_vars(array(
			'NOTIFY' =>					$notify,
			'PAGINATION' =>				$pagination,
			'FORUM_RULES' =>			$parser->message($this->topic_data['f_rules']),
			'IS_LOCKED' =>				($this->topic_data['t_status'] == LOCK) ? true : false,
			'JUMPBOX' =>				$jumpbox,
			'TOPIC_NAME' =>				Parser::title($this->topic_data['t_title']),
			'CAN_SEE_AVATAR' =>			(Fsb::$session->data['u_activate_avatar']) ? true : false,
			'CAN_POST_NEW' =>			(!$can_create_post || ($this->topic_data['f_status'] == LOCK && !Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_moderator'))) ? false : true,
			'USE_AJAX' =>				(Fsb::$session->data['u_activate_ajax']) ? true : false,
			'MAX_SIG_HEIGHT' =>			Fsb::$cfg->get('sig_max_height'),
			'SHOW_LAST_POST' =>			$show_last_post,
			'TOPIC_DESCRIPTION' =>		htmlspecialchars(String::truncate($this->topic_data['t_description'], 50)),
			'AVATAR_WIDTH' =>			Fsb::$cfg->get('avatar_width'),
			'AVATAR_HEIGHT' =>			Fsb::$cfg->get('avatar_height'),
			'QUICKSEARCH_LANG' =>		Fsb::$session->lang('topic_search_in'),

			'U_TOPIC_NEW' =>			sid(ROOT . 'index.' . PHPEXT . '?p=' . $redirect_login . 'post&amp;mode=topic&amp;id=' . $this->topic_data['f_id']),
			'U_TOPIC_REPLY' =>			sid(ROOT . 'index.' . PHPEXT . '?p=' . ((!$can_reply) ? $redirect_login : '') . 'post&amp;mode=reply&amp;id=' . $this->topic_data['t_id']),
			'U_TOPIC_NOTIFICATION' =>	sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;notification=' . $notify . '&amp;t_id=' . $this->topic_data['t_id'] . '&amp;page=' . $this->page),
			'U_DELETE_TOPIC' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=delete_topic&amp;id=' . $this->topic_data['t_id']),
			'U_SPLIT_TOPIC' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=split&amp;id=' . $this->topic_data['t_id']),
			'U_MERGE_TOPIC' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=merge&amp;id=' . $this->topic_data['t_id']),
			'U_MOVE_TOPIC' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=move&amp;id=' . $this->topic_data['t_id']),
			'U_LOCK_TOPIC' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=lock&amp;mode=lock&amp;id=' . $this->topic_data['t_id']),
			'U_UNLOCK_TOPIC' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=lock&amp;mode=unlock&amp;id=' . $this->topic_data['t_id']),
			'U_QUICK_REPLY_ACTION' =>	sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=reply&amp;id=' . $this->topic_data['t_id']),
			'U_TOPIC_PRINT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;print= true &amp;t_id=' . $this->topic_data['t_id'] . '&amp;page=' . $this->page),
			'U_RSS' =>					sid(ROOT . 'index.' . PHPEXT . '?p=rss&amp;mode=topic&amp;id=' . $this->topic_data['t_id']),
			'U_QUICKSEARCH' =>			sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;in=post&amp;print=post&amp;topic=' . $this->topic_data['t_id']),
			'U_LOW_FORUM' =>			sid(ROOT . 'index.' . PHPEXT . '?p=low&amp;mode=topic&amp;id=' . $this->topic_data['t_id']),
		));

		// Regles du forums ?
		if (!empty($this->topic_data['f_rules']))
		{
			Fsb::$tpl->set_switch('forum_rules');
		}

		// Interdiction de poster ?
		if (Fsb::$session->data['u_warn_post'] == 1 || Fsb::$session->data['u_warn_post'] >= CURRENT_TIME)
		{
			Fsb::$tpl->set_vars(array(
				'WARN_INFO' =>	(Fsb::$session->data['u_warn_post'] >= CURRENT_TIME) ? sprintf(Fsb::$session->lang('not_allowed_to_post_until'), Fsb::$session->print_date(Fsb::$session->data['u_warn_post'])) : Fsb::$session->lang('not_allowed_to_post'),
			));
		}

		// Forum verrouille ?
		if ($this->topic_data['f_status'] == LOCK)
		{
			Fsb::$tpl->set_switch('forum_locked');
		}

		// Sujet verrouille ?
		if ($this->topic_data['f_status'] == LOCK || $this->topic_data['t_status'] == LOCK)
		{
			Fsb::$tpl->set_switch('topic_locked');
		}

		// Peut moderer le forum ?
		if (Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_moderator'))
		{
			Fsb::$tpl->set_switch('modo_topic');
		}

		// Peut donner / supprimer des avertissements ?
		if (Fsb::$session->is_authorized('warn_user'))
		{
			Fsb::$tpl->set_switch('warn_user');
		}

		// Liste des procedures
		$this->print_procedure();
	}
	
	/**
	 * Recupere les informations statiques du message (donnees sur le membre, rangs, etc ...)
	 *
	 * @param array $row
	 * @return array
	 */
	public function get_post_array(&$row)
	{
		$avatar = User::get_avatar($row['u_avatar'], $row['u_avatar_method'], $row['u_can_use_avatar']);
		$rank =	User::get_rank($row['u_total_post'], $row['u_rank_id']);
		$age =	User::get_age($row['u_birthday']);
		$sexe =	User::get_sexe($row['u_sexe']);

		// Informations passees au parseur de message
		$parser_info = array(
			'u_id' =>			$row['u_id'],
			'p_nickname' =>		$row['p_nickname'],
			'u_auth' =>			$row['u_auth'],
			'is_sig' =>			true,
		);

		$parser = new Parser();
		$ary = array();
		$ary['data'] = array(
			'IS_VISITOR' =>			($row['u_id'] == VISITOR_ID) ? true : false,
			'IS_ONLINE' =>			($row['u_last_visit'] > (CURRENT_TIME - ONLINE_LENGTH) && !$row['u_activate_hidden']) ? true : false,
			'NICKNAME' =>			Html::nickname($row['p_nickname'], $row['u_id'], $row['u_color']),
			'SIG' =>				$parser->sig($row['u_signature'], $parser_info),
			'RANK_NAME' =>			$rank['name'],
			'RANK_IMG' =>			$rank['img'],
			'RANK_STYLE' =>			$rank['style'],
			'HAVE_RANK' =>			($rank) ? true : false,
			'HAVE_SIG' =>			(Fsb::$cfg->get('activate_sig') && $row['u_can_use_sig'] && Fsb::$session->data['u_activate_sig'] && !empty($row['u_signature'])) ? true : false,
			'AGE' =>				($age) ? sprintf(Fsb::$session->lang('topic_age_format'), $age) : null,
			'SEXE' =>				$sexe,
			'JOINED' =>				Fsb::$session->print_date($row['u_joined'], false),
			'POSTS' =>				$row['u_total_post'],
			'WARN_LENGTH1' =>		(!$row['u_total_warning']) ? 100 : 100 - ($row['u_total_warning'] * 20),
			'WARN_LENGTH2' =>		(!$row['u_total_warning']) ? 0 : $row['u_total_warning'] * 20,
			'USER_AVATAR' =>		sprintf(Fsb::$session->lang('user_avatar'), htmlspecialchars($row['p_nickname'])),
			'USER_PROFIL' =>		sprintf(Fsb::$session->lang('profil_of'), htmlspecialchars($row['p_nickname'])),
			'CAN_WARN' =>			($row['u_auth'] >= MODOSUP) ? false : true,
			
			'U_AVATAR' =>			$avatar,
			'U_PROFILE' =>			sid(ROOT . 'index.' . PHPEXT . '?p=userprofile&amp;id=' . $row['u_id']),
			'U_WARN' =>				sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=warn&amp;mode=show&amp;id=' . $row['u_id']),
			'U_WARN_LESS' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=warn&amp;mode=less&amp;id=' . $row['u_id']),
			'U_WARN_MORE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=warn&amp;mode=more&amp;id=' . $row['u_id']),
			'U_POSTS' =>			sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=author&amp;id=' . $row['u_id']),
		);

		// Donnees des champs personalises
		$ary['fields'] = array();
		foreach ($row AS $key => $value)
		{
			if (preg_match('#^personal_#', $key))
			{
				$ary['fields'][$key] = $value;
			}
		}

		return ($ary);
	}

	/**
	 * Modifie la notification
	 *
	 */
	public function change_notification()
	{
		if ($this->notification == 'off')
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'topics_notification
					WHERE t_id = ' . $this->topic_data['t_id'] . '
						AND u_id = ' . Fsb::$session->id();
			Fsb::$db->query($sql);
		}
		else
		{
			Fsb::$db->insert('topics_notification', array(
				't_id' =>		array($this->topic_data['t_id'], true),
				'u_id' =>		array(Fsb::$session->id(), true),
				'tn_status' =>	IS_NOT_NOTIFIED,
			), 'REPLACE');
		}

		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=topic&t_id=' . $this->topic_data['t_id'] . '&page=' . $this->page);
	}

	/**
	 * Met a jour la date de visite du sujet, et le nombre de vue
	 *
	 */
	public function update_topic()
	{
		// Marquer le sujet lu
		if (Fsb::$session->is_logged() && $this->topic_data['t_last_p_time'] > MAX_UNREAD_TOPIC_TIME)
		{
			if (!$this->topic_data['last_unread_id'] || $this->topic_data['last_unread_id'] < $this->topic_data['t_last_p_id'])
			{
				Fsb::$db->insert('topics_read', array(
					'u_id' =>			array(Fsb::$session->id(), true),
					't_id' =>			array($this->topic_data['t_id'], true),
					'p_id' =>			$this->topic_data['t_last_p_id'],
				), 'REPLACE');
			}

			// Remise a jour de la notification du membre
			if (Fsb::$mods->is_active('topic_notification') && Fsb::$session->data['u_activate_auto_notification'] & NOTIFICATION_EMAIL && $this->topic_data['can_notify'] && $this->topic_data['tn_status'] == IS_NOTIFIED)
			{
				Fsb::$db->update('topics_notification', array(
					'tn_status' =>	IS_NOT_NOTIFIED,
				), 'WHERE u_id = ' . Fsb::$session->id() . ' AND t_id = ' . $this->topic_data['t_id']);
			}
		}

		$update_total_view = true;
		if (Fsb::$mods->is_active('cookie_view'))
		{
			// Page deja visitee durant la session ?
			$cookie_view = Http::getcookie('cookie_view');
			if ($cookie_view)
			{
				$cookie_view = @unserialize($cookie_view);
				$update_total_view = (is_array($cookie_view) && in_array($this->topic_data['t_id'], $cookie_view)) ? false : true;
			}

			if ($update_total_view)
			{
				if (!is_array($cookie_view))
				{
					$cookie_view = array();
				}
				$cookie_view[] = $this->topic_data['t_id'];
				Http::cookie('cookie_view', serialize($cookie_view), 0);
			}
		}

		// +1 visite au sujet si pas deja visite
		if ($update_total_view)
		{
			Fsb::$db->update('topics', array(
				't_total_view' =>	array('(t_total_view + 1)', 'is_field' => true),
			), 'WHERE t_id = ' . $this->topic_data['t_id']);
		}
	}

	/**
	 * Affiche la liste des procedures
	 *
	 */
	public function print_procedure()
	{
		$sql = 'SELECT procedure_id, procedure_name, procedure_auth
				FROM ' . SQL_PREFIX . 'sub_procedure
				ORDER BY procedure_name';
		$result = Fsb::$db->query($sql, 'procedure_');
		$list_procedure = array();
		while ($row = Fsb::$db->row($result))
		{
			if (($row['procedure_auth'] == USER && (Fsb::$session->auth() >= MODOSUP || Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_moderator')
				|| $this->topic_data['u_id'] == Fsb::$session->id()))
				|| ($row['procedure_auth'] == MODO && (Fsb::$session->auth() >= MODOSUP || Fsb::$session->is_authorized($this->topic_data['f_id'], 'ga_moderator')))
				|| ($row['procedure_auth'] > MODO && Fsb::$session->auth() >= $row['procedure_auth']))
			{
				Fsb::$tpl->set_switch('list_procedure');
				$list_procedure[$row['procedure_id']] = $row['procedure_name'];
			}
		}
		Fsb::$db->free($result);

		if ($list_procedure)
		{
			Fsb::$tpl->set_vars(array(
				'LIST_PROCEDURE' =>		Html::make_list('procedure', '', $list_procedure),

				'U_PROCEDURE_ACTION' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure_exec&amp;id=' . $this->topic_data['t_id']),
			));
		}
	}
}

/* EOF */
