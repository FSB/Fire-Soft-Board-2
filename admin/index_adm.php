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
 * Affiche l'index de l'administration contenant la liste des membres en ligne, si le forum est a jour, les derniers logs,
 * les comptes en attente d'activation.
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Mode de la frame
	 *
	 * @var string
	 */
	public $mode;

	/**
	 * Constructeur
	 */
	public function main()
	{
		$this->mode = Http::request('mode');
		$call = new Call($this);
		$call->functions(array(
			'mode' => array(
				'refresh' =>	'refresh_menu',
				'version' =>	'page_check_version',
				'validate' =>	'page_activate_users',
				'default' =>	'page_default_administration',
			),
		));
	}

	/**
	 * Page par defaut sur l'administration du forum
	 */
	public function page_default_administration()
	{
		Fsb::$tpl->set_file('adm_index.html');

		// Les 5 derniers logs administratifs
		$logs = Log::read(Log::ADMIN, 5);
		foreach ($logs['rows'] AS $log)
		{
			Fsb::$tpl->set_blocks('log', array(
				'STR' =>	$log['errstr'],
				'INFO' =>	sprintf(Fsb::$session->lang('adm_list_log_info'), htmlspecialchars($log['u_nickname']), Fsb::$session->print_date($log['log_time'])),
			));
		}

		// On affiche tous les comptes ?
		$show_all = (Http::request('show_all')) ? true : false;

		// Liste des comptes en attente de validation
		$sql = 'SELECT u_id, u_nickname, u_joined, u_register_ip
				FROM ' . SQL_PREFIX . 'users
				WHERE u_activated = 0
					AND u_confirm_hash = \'.\'
					AND u_id <> ' . VISITOR_ID . '
				ORDER BY u_joined DESC
				' . (($show_all) ? '' : 'LIMIT 5');
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('wait', array(
				'NICKNAME' =>		htmlspecialchars($row['u_nickname']),
				'JOINED_IP' =>		sprintf(Fsb::$session->lang('adm_joined_ip'), Fsb::$session->print_date($row['u_joined']), $row['u_register_ip']),

				'U_EDIT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=user&amp;id=' . $row['u_id']),
				'U_VALIDATE' =>		sid('index.' . PHPEXT . '?mode=validate&amp;id=' . $row['u_id']),
			));
		}
		Fsb::$db->free($result);

		// Liste des membres en ligne
		$sql = 'SELECT s.s_id, s.s_ip, s.s_page, s.s_user_agent, u.u_nickname, u.u_color, u.u_activate_hidden, b.bot_id, b.bot_name
			FROM ' . SQL_PREFIX . 'sessions s
			LEFT JOIN ' . SQL_PREFIX . 'users u
				ON u.u_id = s.s_id
			LEFT JOIN ' . SQL_PREFIX . 'bots b
				ON s.s_bot = b.bot_id
			WHERE s.s_time > ' . intval(CURRENT_TIME - 300) . '
			ORDER BY u.u_auth DESC, u.u_nickname, s.s_id';
		$result = Fsb::$db->query($sql);
		$id_array = array();
		$ip_array = array();
		$f_idx = $t_idx = $p_idx = array();
		$logged = array('users' => array(), 'visitors' => array());
		while ($row = Fsb::$db->row($result))
		{
			if (!is_null($row['bot_id']) || $row['s_id'] == VISITOR_ID)
			{
				if (in_array($row['s_ip'], $ip_array))
				{
					continue ;
				}
				$ip_array[] = $row['s_ip'];
				$type = 'visitors';

				// Les bots ont leur propre couleur
				if (!is_null($row['bot_id']))
				{
					$row['u_color'] = 'class="bot"';
					$row['u_nickname'] = $row['bot_name'];
				}
			}
			else
			{
				if (in_array($row['s_id'], $id_array))
				{
					continue ;
				}
				$id_array[] = $row['s_id'];
				$type = 'users';
			}

			// Position du membre sur le forum
			$id = null;
			$method = 'forums';
			if (strpos($row['s_page'], 'admin/') !== false)
			{
				$location = Fsb::$session->lang('adm_location_adm');
				$url = 'admin/index.' . PHPEXT;

			}
			else
			{
				$page = basename($row['s_page']);
				$url = '';
				$location = '';
				if (preg_match('#^index\.' . PHPEXT . '\?p=([a-z0-9_]+)(&.*?)*$#', $page, $match) || preg_match('#^(forum|topic|sujet)-([0-9]+)-([0-9]+)\.html$#i', $page, $match))
				{
					$url = $match[0];
					switch ($match[1])
					{
						case 'forum' :
							$location = Fsb::$session->lang('adm_location_forum');
							if (count($match) == 4)
							{
								$id = $match[2];
							}
							else
							{
								preg_match('#f_id=([0-9]+)#', $page, $match);
								$id = (isset($match[1])) ? $match[1] : null;
							}
							if ($id) $f_idx[] = $id;
						break;

						case 'topic' :
						case 'sujet' :
							$location = Fsb::$session->lang('adm_location_topic');
							if (count($match) == 4)
							{
								$method = 'topics';
								$id = $match[2];
								$t_idx[] = $id;
							}
							else if (preg_match('#t_id=([0-9]+)#', $page, $match))
							{
								$method = 'topics';
								$id = (isset($match[1])) ? $match[1] : null;
								if ($id) $t_idx[] = $id;
							}
							else
							{
								$method = 'posts';
								preg_match('#p_id=([0-9]+)#', $page, $match);
								$id = (isset($match[1])) ? $match[1] : null;
								if ($id) $p_idx[] = $id;
							}
						break;

						case 'post' :
							preg_match('#mode=([a-z_]+)#', $page, $mode);
							preg_match('#id=([0-9]+)#', $page, $match);
							$id = (isset($match[1])) ? $match[1] : null;
							switch ($mode[1])
							{
								case 'topic' :
									$location = Fsb::$session->lang('adm_location_post_new');
									if ($id) $f_idx[] = $id;
								break;

								case 'reply' :
									$location = Fsb::$session->lang('adm_location_post_reply');
									if ($id) $t_idx[] = $id;
									$method = 'topics';
								break;

								case 'edit' :
									$location = Fsb::$session->lang('adm_location_post_edit');
									if ($id) $p_idx[] = $id;
									$method = 'posts';
								break;

								case 'mp' :
									$location = Fsb::$session->lang('adm_location_mp_write');
								break;

								case 'calendar_add' :
									$location = Fsb::$session->lang('adm_location_calendar_event');
								break;

								case 'calendar_edit' :
									$location = Fsb::$session->lang('adm_location_calendar_event_edit');
								break;
							}
						break;

						default :
							if (Fsb::$session->lang('adm_location_' . $match[1]))
							{
								$location = Fsb::$session->lang('adm_location_' . $match[1]);
							}
						break;
					}
				}

				if (!$location)
				{
					$location = Fsb::$session->lang('adm_location_index');
					$url = 'index.' . PHPEXT;
				}
			}

			$logged[$type][] = array(
				'nickname' =>		Html::nickname($row['u_nickname'], $row['s_id'], $row['u_color']),
				'agent' =>			$row['s_user_agent'],
				'ip' =>				$row['s_ip'],
				'location' =>		$location,
				'url' =>			sid(ROOT . $url),
				'method' =>			$method,
				'id' =>				$id,
			);


		}
		Fsb::$db->free($result);

		// On recupere une liste des forums pour connaitre la position du membre sur le forum
		$forums = array();
		if ($f_idx)
		{
			$sql = 'SELECT f.f_id, f.f_name, cat.f_id AS cat_id, cat.f_name AS cat_name
					FROM ' . SQL_PREFIX . 'forums f
					LEFT JOIN ' . SQL_PREFIX . 'forums cat
						ON f.f_cat_id = cat.f_id
					WHERE f.f_id IN (' . implode(', ', $f_idx) . ')';
			$result = Fsb::$db->query($sql, 'forums_');
			$forums = Fsb::$db->rows($result, 'assoc', 'f_id');
		}

		// On recupere une liste des sujets pour connaitre la position du membre sur le forum
		$topics = array();
		if ($t_idx)
		{
			$sql = 'SELECT f.f_id, f.f_name, cat.f_id AS cat_id, cat.f_name AS cat_name, t.t_id, t.t_title
					FROM ' . SQL_PREFIX . 'topics t
					LEFT JOIN ' . SQL_PREFIX . 'forums f
						ON f.f_id = t.f_id
					LEFT JOIN ' . SQL_PREFIX . 'forums cat
						ON f.f_cat_id = cat.f_id
					WHERE t.t_id IN (' . implode(', ', $t_idx) . ')';
			$result = Fsb::$db->query($sql, 'forums_');
			$topics = Fsb::$db->rows($result, 'assoc', 't_id');
		}

		// On recupere une liste des messages pour connaitre la position du membre sur le forum
		$posts = array();
		if ($p_idx)
		{
			$sql = 'SELECT f.f_id, f.f_name, cat.f_id AS cat_id, cat.f_name AS cat_name, p.p_id, t.t_id, t.t_title
					FROM ' . SQL_PREFIX . 'posts p
					LEFT JOIN ' . SQL_PREFIX . 'topics t
						ON p.t_id = t.t_id
					LEFT JOIN ' . SQL_PREFIX . 'forums f
						ON f.f_id = p.f_id
					LEFT JOIN ' . SQL_PREFIX . 'forums cat
						ON f.f_cat_id = cat.f_id
					WHERE p.p_id IN (' . implode(', ', $p_idx) . ')';
			$result = Fsb::$db->query($sql, 'forums_');
			$posts = Fsb::$db->rows($result, 'assoc', 'p_id');
		}

		// On affiche les membres en ligne
		foreach ($logged AS $type => $list)
		{
			if ($list)
			{
				Fsb::$tpl->set_blocks('logged', array(
					'TITLE' =>		Fsb::$session->lang('adm_list_logged_' . $type),
				));

				foreach ($list AS $u)
				{
					// On definit si on cherche la liste des forums dans $forums ou $topics
					$m = $u['method'];
					$data_exists = ($u['id'] && isset(${$m}[$u['id']])) ? true : false;
					$topic_exists = ($data_exists && isset(${$m}[$u['id']]['t_title'])) ? true : false;

					Fsb::$tpl->set_blocks('logged.u', array(
						'NICKNAME' =>		$u['nickname'],
						'AGENT' =>			$u['agent'],
						'IP' =>				$u['ip'],
						'LOCATION' =>		$u['location'],
						'URL' =>			$u['url'],
						'CAT_NAME' =>		($data_exists) ? ${$m}[$u['id']]['cat_name'] : null,
						'FORUM_NAME' =>		($data_exists) ? ${$m}[$u['id']]['f_name'] : null,
						'TOPIC_NAME' =>		($topic_exists) ? Parser::title(${$m}[$u['id']]['t_title']) : '',
						'U_CAT' =>			($data_exists) ? sid(ROOT . 'index.' . PHPEXT . '?p=index&amp;cat=' . ${$m}[$u['id']]['cat_id']) : null,
						'U_FORUM' =>		($data_exists) ? sid(ROOT . 'index.' . PHPEXT . '?p=forum&amp;f_id=' . ${$m}[$u['id']]['f_id']) : null,
						'U_TOPIC' =>		($topic_exists) ? sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . ${$m}[$u['id']]['t_id']) : null,
						'U_IP' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=ip&amp;ip=' . $u['ip']),
					));
				}
			}
		}

		// On verifie si le SDK n'a pas ete desactive
		if(intval(Fsb::$cfg->get('disable_sdk')))
		{
			Fsb::$tpl->set_switch('sdk_disabled');
		}

		Fsb::$tpl->set_vars(array(
			'FSB_SUPPORT' =>		'http://www.fire-soft-board.com',
			'FSB_LANG_SUPPORT' =>	Fsb::$session->lang('fsb_lang_support'),
			'NEW_VERSION' =>		(!is_last_version(Fsb::$cfg->get('fsb_version'), Fsb::$cfg->get('fsb_last_version'))) ? sprintf(Fsb::$session->lang('adm_fsb_new_version'), Fsb::$cfg->get('fsb_version'), Fsb::$cfg->get('fsb_last_version')) : null,
			'SHOW_ALL' =>			$show_all,
			'ROOT_SUPPORT' => 		sprintf(Fsb::$session->lang('adm_root_support_active_explain'), 'index.' . PHPEXT . '?p=mods_manager'),

			'U_SHOW_ALL' =>			sid('index.' . PHPEXT . '?show_all=true'),
			'U_CHECK_VERSION' =>	sid('index.' . PHPEXT . '?mode=version'),
		));
	}

	/**
	 * Page de verification de la version
	 */
	public function page_check_version()
	{
		if (!$content = Http::get_file_on_server(FSB_REQUEST_SERVER, FSB_REQUEST_VERSION, 10))
		{
			Display::message('adm_unable_check_version');
		}
		@list($last_version, $url, $level) = explode("\n", $content);

		// Aucune redirection
		Fsb::$session->data['u_activate_redirection'] = 2;

		if (!is_last_version(Fsb::$cfg->get('fsb_version'), $last_version))
		{
			Display::message(sprintf(Fsb::$session->lang('adm_old_version'), $last_version, Fsb::$cfg->get('fsb_version'), $url, $url, Fsb::$session->lang('adm_version_' . $level)) . '<br /><br />' . sprintf(Fsb::$session->lang('adm_click_view_newer'), $url));
		}
		else
		{
			Display::message('adm_version_ok', 'index.' . PHPEXT, 'index_adm');
		}
	}

	/**
	 * Page de verification de la version
	 */
	public function refresh_menu()
	{
		Fsb::$menu->refresh_menu();
		Display::message('adm_well_refresh', 'index.' . PHPEXT, 'index_adm');
	}

	/**
	 * Page de verification de la version
	 */
	public function page_activate_users()
	{
		$id = intval(Http::request('id'));
		if ($id)
		{
			 User::confirm_account($id);
		}

		Http::redirect('index.' . PHPEXT);
	}
}

/* EOF */
