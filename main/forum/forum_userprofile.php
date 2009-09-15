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
 * Affiche le profil public d'un membre
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
	public $_show_page_footer_nav = false;
	
	/**
	 * Affichage de la boite des stats
	 *
	 * @var bool
	 */
	public $_show_page_stats = false;

	/**
	 * Module courant
	 *
	 * @var string
	 */
	public $module;

	/**
	 * Donnees personnelles du membre
	 *
	 * @var array
	 */
	public $data = array();
	
	/**
	 * ID du membre
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Navigation
	 *
	 * @var array
	 */
	public $nav = array();

	/**
	 * Tag titre
	 *
	 * @var string
	 */
	public $tag_title = '';
	
	/**
	 * Titre page
	 *
	 * @var string
	 */
	public $page_title = '';
	
	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		$this->module = Http::request('module');
		$this->id = intval(Http::request('id'));

		// Informations sur le membre
		$sql = 'SELECT u.*, uc.*, up.*
				FROM ' . SQL_PREFIX . 'users u
				LEFT JOIN ' . SQL_PREFIX . 'users_contact uc
					ON u.u_id = uc.u_id
				LEFT JOIN ' . SQL_PREFIX . 'users_personal up
					ON u.u_id = up.u_id
				WHERE u.u_id = ' . $this->id . '
					AND u.u_id <> ' . VISITOR_ID;
		$this->data = Fsb::$db->request($sql);
		if (!$this->data)
		{
			Display::message('userprofile_not_exists');
		}

		$this->page_title = sprintf(Fsb::$session->lang('userprofile_nickname'), htmlspecialchars($this->data['u_nickname']));
		$this->nav[] = array(
			'url' =>	'',
			'name' =>	$this->page_title,
		);
		$this->tag_title = htmlspecialchars($this->data['u_nickname']) . Fsb::$session->getStyle('other', 'title_separator') . Fsb::$cfg->get('forum_name');

		// Liste des modules disponibles
		$module_list = array('view' => 'userprofile&amp;id=' . $this->id);

		// Droit d'acces aux logs du membre ?
		if ($this->id == Fsb::$session->id() || Fsb::$session->is_authorized('auth_edit_user'))
		{
			$module_list['logs'] = 'userprofile&amp;module=logs&amp;id=' . $this->id;
		}

		// Raccourci vers l'edition du membre ?
		if (Fsb::$session->is_authorized('auth_edit_user'))
		{
			$module_list['edit'] = 'modo&amp;module=user&amp;id=' . $this->id;
		}

		// Modifier mon profil ?
		if (Fsb::$session->id() == $this->id)
		{
			$module_list['profile'] = 'profile';
		}

		if (!in_array($this->module, array_keys($module_list)))
		{
			$this->module = 'view';
		}

		// Affichage de la liste des modules (sauf si seul le module par defaut est visible)
		if (count($module_list) > 1)
		{
			Fsb::$tpl->set_switch('use_module');
			Fsb::$tpl->set_vars(array(
				'MENU_HEADER_TITLE' =>	$this->page_title,
			));

			foreach ($module_list AS $m => $url)
			{
				Fsb::$tpl->set_blocks('module', array(
					'IS_SELECT' =>	($this->module == $m) ? true : false,
					'URL' =>		sid(ROOT . 'index.' . PHPEXT . '?p=' . $url),
					'NAME' =>		Fsb::$session->lang('userprofile_module_' . $m),
				));
			}
		}

		// Appel dynamique de la methode liee au module de la page
		$this->{'userprofile_' . $this->module}();
	}

	/**
	 * Affiche le profil personel d'un membre
	 *
	 */
	public function userprofile_view()
	{
		// Droit d'acces ?
		if (!Fsb::$session->is_authorized('can_see_profile'))
		{
			Display::message('not_allowed');
		}

		$parser = new Parser();

		$this->get_user_data();
		
		// E-mail actives ?
		if (!($this->data['u_activate_email'] & 8))
		{
			Fsb::$tpl->set_switch('can_email');
		}

		// On regarde si on a le droit d'editer ce membre
		if (Fsb::$session->is_authorized('auth_edit_user'))
		{
			Fsb::$tpl->set_switch('can_edit_user');
		}

		// On regarde si on peut voir les commentaire sur ce membre
		if (Fsb::$session->auth() >= MODO)
		{
			Fsb::$tpl->set_switch('show_comments');
		}

		// Peut voir la derniere visite ?
		if (!$this->data['u_activate_hidden'] || Fsb::$session->auth() >= MODO || Fsb::$session->id() == $this->id)
		{
			Fsb::$tpl->set_switch('show_last_visit');
		}

		// Balise META pour la syndications RSS sur les derniers messages du membre
		Http::add_meta('link', array(
			'rel' =>		'alternate',
			'type' =>		'application/rss+xml',
			'title' =>		Fsb::$session->lang('rss'),
			'href' =>		sid(ROOT . 'index.' . PHPEXT . '?p=rss&amp;mode=user&amp;id=' . $this->id),
		));

		// Informations passees au parseur de message
		$parser_info = array(
			'u_id' =>			$this->data['u_id'],
			'p_nickname' =>		$this->data['u_nickname'],
			'u_auth' =>			$this->data['u_auth'],
			'is_sig' =>			true,
		);

		Fsb::$tpl->set_file('forum/forum_userprofile.html');
		Fsb::$tpl->set_vars(array(
			'USER_NICKNAME' =>			$this->page_title,
			'USER_AVATAR' =>			$this->data['url_avatar'],
			'USER_EMAIL' =>				$this->data['url_email'],
			'USER_MP' =>				sid('index.' . PHPEXT . '?p=post&amp;mode=mp&amp;u_id=' . $this->id),
			'USER_POST_TOTAL' =>		$this->data['u_total_post'],
			'USER_POST_RATE' =>			$this->data['post_rate'],
			'USER_POST_TOTAL_RATE' =>	$this->data['post_total_rate'],
			'USER_TOPIC_TOTAL' =>		$this->data['u_total_topic'],
			'USER_TOPIC_RATE' =>		$this->data['topic_rate'],
			'USER_TOPIC_TOTAL_RATE' =>	$this->data['topic_total_rate'],
			'USER_SIG' =>				(Fsb::$cfg->get('activate_sig') && $this->data['u_can_use_sig']) ? $parser->sig($this->data['u_signature'], $parser_info) : '',
			'USER_AGE' =>				($this->data['u_age']) ? sprintf(Fsb::$session->lang('age_format'), $this->data['u_age']) : Fsb::$session->lang('userprofile_age_none'),
			'USER_SEXE' =>				($this->data['sexe'] != '') ? $this->data['sexe'] : Fsb::$session->lang('userprofile_sexe_none'),
			'USER_JOINED' =>			Fsb::$session->print_date($this->data['u_joined']),
			'USER_LAST_VISIT' =>		Fsb::$session->print_date($this->data['u_last_visit']),
			'USER_COMMENT' =>			($this->data['u_comment']) ? $parser->message($this->data['u_comment']) : '',
			'RANK_NAME' =>				$this->data['rank']['name'],
			'RANK_IMG' =>				$this->data['rank']['img'],
			'RANK_STYLE' =>				$this->data['rank']['style'],
			'LIST_GROUPS' =>			$this->show_groups(),
			'LIST_GROUPS_MODO' =>		$this->show_groups_modo(),
			'HIDDEN_ADD_LOGIN' =>		Html::hidden('add_login', $this->data['u_nickname']),
			'ACTIV_FORUM' =>			($this->data['activ_forum']) ? $this->data['activ_forum']['f_name'] : '',
			'ACTIV_FORUM_POST' =>		($this->data['activ_forum']) ? sprintf(String::plural('forum_total_post', $this->data['activ_forum']['total']), $this->data['activ_forum']['total']) : '',
			'ACTIV_TOPIC' =>			($this->data['activ_topic']) ? $this->data['activ_topic']['t_title'] : '',
			'ACTIV_TOPIC_POST' =>		($this->data['activ_topic']) ? sprintf(String::plural('forum_total_post', $this->data['activ_topic']['total']), $this->data['activ_topic']['total']) : '',
			'IS_ONLINE' =>				($this->data['u_last_visit'] > (CURRENT_TIME - ONLINE_LENGTH) && !$this->data['u_activate_hidden']) ? true : false,

			'U_SEARCH_POSTS' =>			sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=author&amp;id=' . $this->id),
			'U_SEARCH_TOPICS' =>		sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=author_topic&amp;id=' . $this->id),
			'U_MODO_LINK' =>			sid('index.' . PHPEXT . '?p=modo&amp;module=user&amp;id=' . $this->id),
			'U_MODO_WARN' =>			sid('index.' . PHPEXT . '?p=modo&amp;module=warn&amp;mode=show&amp;id=' . $this->id),
			'U_ADD_GROUP' =>			sid('index.' . PHPEXT . '?p=userlist'),
			'U_ACTIV_FORUM' =>			($this->data['activ_forum']) ? sid('index.' . PHPEXT . '?p=forum&amp;f_id=' . $this->data['activ_forum']['f_id']) : '',
			'U_ACTIV_TOPIC' =>			($this->data['activ_topic']) ? sid('index.' . PHPEXT . '?p=topic&amp;t_id=' . $this->data['activ_topic']['t_id']) : '',
		));
		
		// On affiche les moyens de contacter le membre
		Profil_fields_forum::show_fields(PROFIL_FIELDS_CONTACT, 'contact', $this->data);
		Profil_fields_forum::show_fields(PROFIL_FIELDS_PERSONAL, 'personal', $this->data);
	}
	
	/**
	 * Recuperation des informations personelles du membre (contact, groupes, etc ...)
	 *
	 */
	public function get_user_data()
	{
		// Avatar du membre
		$this->data['url_avatar'] = User::get_avatar($this->data['u_avatar'], $this->data['u_avatar_method'], $this->data['u_can_use_avatar']);
		
		// URL pour envoyer un E-mail au membre
		if ($this->data['u_activate_email'] & 2)
		{
			$this->data['url_email'] = 'mailto:' . $this->data['u_email'];
		}
		else if ($this->data['u_activate_email'] & 4)
		{
			$this->data['url_email'] = sid('index.' . PHPEXT . '?p=email&amp;id=' . $this->id);
		}
		else
		{
			$this->data['url_email'] = '';
		}
		
		// Age du membre
		$this->data['u_age'] = User::get_age($this->data['u_birthday']);

		// Sexe du membre
		$this->data['sexe'] = User::get_sexe($this->data['u_sexe']);

		// Rang du membre
		if ($this->data['rank'] = User::get_rank($this->data['u_total_post'], $this->data['u_rank_id']))
		{
			Fsb::$tpl->set_switch('have_rank');
		}

		// Statistiques sur les sujets et les messages
		$nb_day_since_register =			ceil((CURRENT_TIME - $this->data['u_joined']) / ONE_DAY);
		$this->data['post_rate'] =			substr($this->data['u_total_post'] / $nb_day_since_register, 0, 4);
		$this->data['post_total_rate'] =	($this->data['u_total_post'] && Fsb::$cfg->get('total_posts') > 0) ? substr($this->data['u_total_post'] / Fsb::$cfg->get('total_posts') * 100, 0, 4) : 0;
		$this->data['topic_rate'] =			substr($this->data['u_total_topic'] / $nb_day_since_register, 0, 4);
		$this->data['topic_total_rate'] =	($this->data['u_total_topic'] && Fsb::$cfg->get('total_topics') > 0) ? substr($this->data['u_total_topic'] / Fsb::$cfg->get('total_topics') * 100, 0, 4) : 0;

		// Forum dans lequel le membre est le plus actif
		$forums_idx = Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read'));
		$sql = 'SELECT f_id, COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'posts
				WHERE u_id = ' . $this->id
				. (($forums_idx) ? ' AND f_id IN (' . implode(', ', $forums_idx) . ')' : '')
				. ' GROUP BY f_id
				ORDER BY total DESC
				LIMIT 1';
		$f = Fsb::$db->request($sql);
		
                if ($f['f_id'] != '')
                {
                        $this->data['activ_forum']['f_id'] = $f['f_id'];
                        $this->data['activ_forum']['total'] = $f['total'];
                        
                        $sql = 'SELECT f_name
				FROM ' . SQL_PREFIX . 'forums
				WHERE f_id = ' . $this->data['activ_forum']['f_id'];
                        $this->data['activ_forum']['f_name'] = Fsb::$db->get($sql, 'f_name');
                }
                else
                        $this->data['activ_forum'] = '';

		// Sujet dans lequel le membre est le plus actif
		$sql = 'SELECT t_id, COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'posts
				WHERE u_id = ' . $this->id
				. (($forums_idx) ? ' AND f_id IN (' . implode(', ', $forums_idx) . ')' : '')
				. ' GROUP BY t_id
				ORDER BY total DESC
				LIMIT 1';
		$f = Fsb::$db->request($sql);

                if ($f['t_id'] != '')
                {
                        $this->data['activ_topic']['t_id'] = $f['t_id'];
                        $this->data['activ_topic']['total'] = $f['total'];
		
                        $sql = 'SELECT t_title
				FROM ' . SQL_PREFIX . 'topics
				WHERE t_id = ' . $this->data['activ_topic']['t_id'];
                        $this->data['activ_topic']['t_title'] = Fsb::$db->get($sql, 't_title');
                }
                else
                        $this->data['activ_topic'] = '';
        }
	
	/**
	 * Liste les groupes auquel le membre appartient
	 *
	 * @return string
	 */
	public function show_groups()
	{		
		$sql = 'SELECT g.g_id, g.g_name, g.g_type, g.g_hidden
				FROM ' . SQL_PREFIX . 'groups g
				INNER JOIN ' . SQL_PREFIX . 'groups_users gu
					ON g.g_id = gu.g_id
				WHERE gu.u_id = ' . $this->id . '
					AND g.g_type <> ' . GROUP_SINGLE . '
					AND gu.gu_status <> ' . GROUP_WAIT . '
					' . ((Fsb::$session->auth() < MODOSUP) ? 'AND g.g_hidden = 0' : '') . '
				ORDER BY g_type, g_name';
		$javascript = 'onchange="if (this.selectedIndex > 0) location.href=\'' . sid(ROOT . 'index.' . PHPEXT . '?p=userlist&amp;g_id=') . '\' + this.value"';
		return (Html::list_groups('list_groups', GROUP_NORMAL|GROUP_SPECIAL, '', false, array(), $sql, $javascript, '<option>' . Fsb::$session->lang('userprofile_choose_group') . '</option>'));
	}

	/**
	 * Liste des groupes que le visiteur modere
	 *
	 * @return string
	 */
	public function show_groups_modo()
	{
		$sql = 'SELECT g_id
				FROM ' . SQL_PREFIX . 'groups_users
				WHERE u_id = ' . $this->id;
		$groups = array();
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$groups[] = $row['g_id'];
		}
		Fsb::$db->free($result);

		$sql = 'SELECT g.g_id, g.g_name, g.g_type, g.g_hidden
				FROM ' . SQL_PREFIX . 'groups g
				LEFT JOIN ' . SQL_PREFIX . 'groups_users gu
					ON g.g_id = gu.g_id
					AND gu.u_id = ' . Fsb::$session->id() . '
				WHERE g.g_type = ' . GROUP_NORMAL . '
					' . (($groups) ? 'AND g.g_id NOT IN (' . implode(', ', $groups) . ')' : '') . '
					' . ((Fsb::$session->auth() < MODOSUP) ? 'AND gu.gu_status = ' . GROUP_MODO : '') . '
				ORDER BY g.g_type, g.g_name';
		$html = Html::list_groups('g_id', GROUP_NORMAL, '', false, array(), $sql);
		if ($html)
		{
			Fsb::$tpl->set_switch('show_groups_modo');
		}
		return ($html);
	}

	/**
	 * Affiche les logs du membre
	 *
	 */
	public function userprofile_logs()
	{
		Fsb::$tpl->set_file('forum/forum_userprofile_logs.html');

		$logs = Log::read(Log::USER, 0, 0, 'AND l.log_user = ' . $this->id);
		foreach ($logs['rows'] AS $log)
		{
			Fsb::$tpl->set_blocks('log', array(
				'INFO' =>		$log['errstr'],
				'BY' =>			Html::nickname($log['u_nickname'], $log['u_id'], $log['u_color']),
				'IP' =>			$log['u_ip'],
				'DATE' =>		Fsb::$session->print_date($log['log_time']),

				'U_IP' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=ip&amp;ip=' . $log['u_ip']),
			));
		}

		if (Fsb::$session->is_authorized('auth_ip'))
		{
			Fsb::$tpl->set_switch('can_see_ip');
		}
	}

	/**
	 * Redirige vers l'edition du profile du membre
	 *
	 */
	public function userprofile_edit()
	{
		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=user&id=' . $this->id);
	}
}

/* EOF */
