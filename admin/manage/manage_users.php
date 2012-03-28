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
 * Gestion des membres
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Module
	 */
	public $module;
	
	/**
	 * Mode de la page
	 *
	 * @var string
	 */
	public $mode;
	
	/**
	 * Trie des membres
	 *
	 * @var string
	 */
	public $order = 'u_joined';
	
	/**
	 * Sens
	 *
	 * @var string
	 */
	public $direction = 'desc';
	
	/**
	 * Nombre de membre par page
	 *
	 * @var int
	 */
	public $limit = 50;
	
	/**
	 * Page
	 *
	 * @var int
	 */
	public $page = 1;
	
	/**
	 * Condition de la recherche
	 *
	 * @var string
	 */
	public $like = '';
	
	/**
	 * Utilisateur recherche
	 *
	 * @var string
	 */
	public $search_user = '';

	/**
	 * Liste des operateurs
	 *
	 * @var array
	 */
	public $operators = array('<', '<=', '=', '>', '>=');

	/**
	 * Constructeur
	 */
	public function main()
	{
		$this->mode =			Http::request('mode');
		$this->search_user =	Http::request('search_user', 'post|get');
		$this->page =			intval(Http::request('page'));
		if (!$this->page)
		{
			$this->page = 1;
		}

		$this->order = Http::request('order');
		if (!in_array($this->order, array('u_joined', 'u_nickname', 'u_total_post')))
		{
			$this->order = 'u_joined';
		}

		$this->direction = strtolower(Http::request('direction'));
		if (!in_array($this->direction, array('asc', 'desc')))
		{
			$this->direction = 'asc';
		}

		$call = new Call($this);
		$call->module(array(
			'list' =>		array('list', 'add', 'prune', 'auths', 'gallery'),
			'url' =>		'index.' . PHPEXT . '?p=manage_users',
			'lang' =>		'adm_users_',
			'default' =>	'list',
		));

		$call->post(array(
			'submit_prune' =>	':submit_prune',
			'submit_gallery' =>	':submit_gallery',
			'submit_avatar' =>	':submit_avatar',
			'submit_add' =>		':submit_add',
		));

		$call->functions(array(
			'module' => array(
				'gallery' =>	array(
					'mode' => array(
						'show' =>			'page_show_gallery',
						'add' =>			'page_add_gallery',
						'edit' =>			'page_add_gallery',
						'delete' =>			'page_delete_gallery',
						'add_avatar' =>		'page_add_avatar',
						'delete_avatar' =>	'page_delete_avatar',
						'default' =>		'page_gallery',
					),
				),
				'prune' =>		'page_prune_users',
				'add' =>		'page_add_users',
				'auths' =>		'page_auths_users',
				'default' =>	'page_list_users'
			),
		));
	}

	/**
	 * Redirige vers manage_auths::page_default_users_auths())
	 */
	public function page_auths_users()
	{
		Http::redirect('index.' . PHPEXT . '?p=manage_auths&amp;module=users', 0);
	}

	/**
	 * Affiche une liste des membres
	 */
	public function page_list_users()
	{
		Fsb::$tpl->set_switch('users_list');

		// Nombre de resultats
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id <> ' . VISITOR_ID . '
					' . (($this->search_user) ? 'AND u_nickname LIKE \'%' . Fsb::$db->escape($this->search_user) . '%\'' : '');
		$total = Fsb::$db->get($sql, 'total');
		$total_page = ceil($total / $this->limit);

		// On recupere la liste des membres
		$sql = 'SELECT u_id, u_nickname, u_total_post, u_total_topic, u_color, u_joined
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id <> ' . VISITOR_ID . '
					' . (($this->search_user) ? 'AND u_nickname LIKE \'%' . Fsb::$db->escape($this->search_user) . '%\'' : '') . '
				ORDER BY ' . $this->order . ' ' . $this->direction . '
				LIMIT ' . ($this->page - 1) * $this->limit . ', ' . $this->limit;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('user', array(
				'ID' =>				$row['u_id'],
				'NICKNAME' =>		Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
				'JOINED' =>			Fsb::$session->print_date($row['u_joined'], false),
				'POSTS' =>			sprintf(String::plural('adm_users_post', $row['u_total_post']), $row['u_total_post']),
				'TOPICS' =>			sprintf(String::plural('adm_users_topic', $row['u_total_topic']), $row['u_total_topic']),
				'U_EDIT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=user&amp;id=' . $row['u_id']),
			));
		}
		Fsb::$db->free($result);

		// Pagination ?
		if ($total_page > 1)
		{
			Fsb::$tpl->set_switch('pagination');
		}

		Fsb::$tpl->set_vars(array(
			'PAGINATION' =>			Html::pagination($this->page, $total_page, $this->user_list_url($this->order, $this->direction)),
			'SEARCH_USER' =>		htmlspecialchars($this->search_user),
			'U_SORT_NICKNAME' =>	$this->user_list_url('u_nickname', ($this->direction == 'desc' || $this->order != 'u_nickname') ? 'asc' : 'desc'),
			'U_SORT_JOINED' =>		$this->user_list_url('u_joined', ($this->direction == 'desc' || $this->order != 'u_joined') ? 'asc' : 'desc'),
			'U_SORT_STATS' =>		$this->user_list_url('u_total_post', ($this->direction == 'desc' || $this->order != 'u_total_post') ? 'asc' : 'desc'),
			'U_ACTION' =>			$this->user_list_url($this->order, $this->direction),
		));
	}
	
	/**
	 * Url pour la page
	 *
	 * @param int $order Ordre
	 * @param string $direction Classement (asc / desc)
	 * @return string
	 */
	public function user_list_url($order, $direction)
	{
		$query = '&amp;order=' . $order . '&amp;direction=' . $direction . '&amp;page=' . $this->page . '&amp;search_user=' . $this->search_user;
		return (sid('index.' . PHPEXT . '?p=manage_users&amp;module=list' . $query));
	}

	/**
	 * Formulaire d'ajout de membres
	 */
	public function page_add_users()
	{
		Fsb::$tpl->set_switch('users_add');
		Fsb::$tpl->set_vars(array(
			'U_ACTION' =>				sid('index.' . PHPEXT . '?p=manage_users&amp;module=add'),
		));
	}

	/**
	 * Ajout d'un utilisateur
	 */
	public function submit_add()
	{
		$data = array(
			'u_login' =>		trim(Http::request('u_login', 'post')),
			'u_nickname' =>		trim(Http::request('u_nickname', 'post')),
			'u_password' =>		trim(Http::request('u_password', 'post')),
			'u_confirmation' => trim(Http::request('u_confirmation', 'post')),
			'u_email' =>		trim(Http::request('u_email', 'post')),
		);

		if (!$data['u_nickname'])
		{
			$data['u_nickname'] = $data['u_login'];
		}

		// Verification du login
		if (!$data['u_login'])
		{
			Display::message('adm_users_add_error_login');
		}

		if (User::login_exists($data['u_login']))
		{
			Display::message('adm_users_add_error_login_exists');
		}

		// Verification du mot de passe
		if (!$data['u_password'])
		{
			Display::message('adm_users_add_error_password');
		}
		
		if ($data['u_password'] != $data['u_confirmation'])
		{
			Display::message('adm_users_add_error_confirmation');
		}		

		// Verification du pseudonyme
		if (User::nickname_exists($data['u_nickname']))
		{
			Display::message('adm_users_add_error_nickname_exists');
		}

		// Verification de l'email
		if (!User::email_valid($data['u_email'], false))
		{
			Display::message('adm_users_add_error_email');
		}

		if (User::email_exists($data['u_email']))
		{
			Display::message('adm_users_add_error_email_exists');
		}

		Fsb::$db->transaction('begin');
		$user_id = User::add($data['u_login'], $data['u_nickname'], $data['u_password'], $data['u_email']);
		Fsb::$db->transaction('commit');

		Log::add(Log::ADMIN, 'users_add', $data['u_nickname']);

		if (!User::confirm_register($user_id, $data))
		{
			Display::message('adm_users_add_error_send_email');
		}
		else
		{
			Display::message('adm_users_well_add', 'index.' . PHPEXT . '?p=manage_users&amp;module=add', 'manage_users');
		}
	}

	/**
	 * Formulaire de suppression de membres
	 */
	public function page_prune_users()
	{
		// Liste des jours, mois et annees
		$list_pos = array(
			'before' =>	Fsb::$session->lang('adm_users_before'),
			'after' =>	Fsb::$session->lang('adm_users_after'),
		);

		$list_day = array();
		for ($i = 1; $i <= 31; $i++)
		{
			$list_day[$i] = String::add_zero($i, 2);
		}

		$list_month = array();
		for ($i = 1; $i <= 12; $i++)
		{
			$list_month[$i] = Fsb::$session->lang('month_' . $i);
		}

		$list_year = array();
		for ($i = date('Y', Fsb::$cfg->get('register_time')); $i <= date('Y', CURRENT_TIME); $i++)
		{
			$list_year[$i] = $i;
		}

		// Liste des operateurs
		$list_operators = array();
		foreach ($this->operators AS $operator)
		{
			$list_operators[$operator] = Fsb::$session->lang('adm_users_operator_' . $operator);
		}

		// Liste des suppressions
		$list_delete = array(
			'desactivate' =>	Fsb::$session->lang('adm_users_prune_delete_desactivate'),
			'visitor' =>		Fsb::$session->lang('adm_users_prune_delete_visitor'),
			'topics' =>			Fsb::$session->lang('adm_users_prune_delete_topic'),
		);

		Fsb::$tpl->set_switch('users_prune');
		Fsb::$tpl->set_vars(array(
			'LIST_JOINED_POS' =>		Html::make_list('joined_pos', 'before', $list_pos),
			'LIST_JOINED_DAY' =>		Html::make_list('joined_day', date('d'), $list_day),
			'LIST_JOINED_MONTH' =>		Html::make_list('joined_month', date('m'), $list_month),
			'LIST_JOINED_YEAR' =>		Html::make_list('joined_year', date('Y'), $list_year),
			'LIST_VISIT_POS' =>			Html::make_list('visit_pos', 'before', $list_pos),
			'LIST_VISIT_DAY' =>			Html::make_list('visit_day', date('d'), $list_day),
			'LIST_VISIT_MONTH' =>		Html::make_list('visit_month', date('m'), $list_month),
			'LIST_VISIT_YEAR' =>		Html::make_list('visit_year', date('Y'), $list_year),
			'LIST_POST_OPERATOR' =>		Html::make_list('post_operator', '==', $list_operators),
			'LIST_TOPIC_OPERATOR' =>	Html::make_list('topic_operator', '==', $list_operators),
			'LIST_DELETE' =>			Html::make_list('delete_type', 'desactivate', $list_delete),
			'U_ACTION' =>				sid('index.' . PHPEXT . '?p=manage_users&amp;module=prune'),
		));
	}

	/**
	 * Soumet le formulaire de delestage des membres
	 */
	public function submit_prune()
	{
		if (check_confirm())
		{
			// Delestage des membres
			$delete_type = Http::request('delete_type', 'post');
			if (!in_array($delete_type, array('desactivate', 'visitor', 'topics')))
			{
				$delete_type = 'desactivate';
			}
			$action = (array) Http::request('action', 'post');
			$action = array_unique(array_map('intval', $action));

			switch ($delete_type)
			{
				case 'desactivate' :
					// Desactivation des membres
					Fsb::$db->update('users', array(
						'u_activated' =>	false,
					), 'WHERE u_id IN (' . implode(', ', $action) . ')');

					$sql = 'DELETE FROM ' . SQL_PREFIX . 'sessions
							WHERE s_id IN (' . implode(', ', $action) . ')';
					Fsb::$db->query($sql);
				break;

				case 'visitor' :
				case 'topics' :
					// Suppression des membres (et peut etre de ses messages)
					User::delete($action, $delete_type);
				break;
			}

			Log::add(Log::ADMIN, 'users_log_prune');
			Display::message('adm_users_prune_well', 'index.' . PHPEXT . '?p=manage_users', 'manage_users');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=manage_users&amp;module=prune');
		}
		else
		{
			// On recupere les informations passees a la page
			$topic_operator =	Http::request('topic_operator', 'post');
			$post_operator =	Http::request('post_operator', 'post');
			$joined =			Http::request('prune_joined', 'post');
			$joined_pos =		Http::request('joined_pos', 'post');
			$visit =			Http::request('prune_visit', 'post');
			$visit_pos =		Http::request('visit_pos', 'post');
			$delete_type =		Http::request('delete_type', 'post');
			$nicknames =		trim(Http::request('prune_nickname', 'post'));
			$email =			trim(Http::request('prune_email', 'post'));
			$ip =				trim(Http::request('prune_ip', 'post'));
			$post =				trim(Http::request('prune_post', 'post'));
			$topic =			trim(Http::request('prune_topic', 'post'));
			$joined_day =		intval(Http::request('joined_day', 'post'));
			$joined_month =		intval(Http::request('joined_month', 'post'));
			$joined_year =		intval(Http::request('joined_year', 'post'));
			$visit_day =		intval(Http::request('visit_day', 'post'));
			$visit_month =		intval(Http::request('visit_month', 'post'));
			$visit_year =		intval(Http::request('visit_year', 'post'));

			// Maintenant on construit petit a petit la requete qui va recuperer les membres a supprimer
			$build_sql = '';
			$main_sql = 'SELECT u_id, u_nickname
					FROM ' . SQL_PREFIX . 'users
					WHERE u_id <> ' . VISITOR_ID . '
						AND u_auth < ' . MODOSUP . "\n";

			// On verifie l'existance des pseudonymes passes en parametres
			if ($nicknames)
			{
				$nicknames = explode("\n", $nicknames);
				$nicknames = array_map('trim', $nicknames);
				$nicknames = array_map('strtolower', $nicknames);
				$nicknames = array_map(array(Fsb::$db, 'escape'), $nicknames);
				$sql = 'SELECT u_id, LOWER(u_nickname) AS u_nickname
						FROM ' . SQL_PREFIX . 'users
						WHERE u_id <> ' . VISITOR_ID . '
							AND u_nickname IN (\'' . implode('\', \'', $nicknames) . '\')';
				$nicknames = array_flip($nicknames);
				$result = Fsb::$db->query($sql);
				$idx = array();
				while ($row = Fsb::$db->row($result))
				{
					unset($nicknames[$row['u_nickname']]);
					$idx[] = $row['u_id'];
				}
				Fsb::$db->free($result);

				// Erreur pour les pseudonymes n'existants pas
				$errstr = '';
				foreach (array_keys($nicknames) AS $nickname)
				{
					$errstr[] = sprintf(Fsb::$session->lang('adm_users_bad_nickname'), htmlspecialchars($nickname));
				}

				if ($errstr)
				{
					Display::message(Html::make_errstr($errstr));
				}

				// Ajoute la clause a la requete
				if ($idx)
				{
					$build_sql .= ' AND u_id IN (' . implode(', ', $idx) . ")\n";
				}
			}

			// Verification des adresses Emails
			if ($email)
			{
				$build_sql .= ' AND u_email LIKE \'' . Fsb::$db->escape(str_replace('*', '%', $email)) . "'\n";
			}
	
			// Verification des adresses IP d'inscriptions
			if ($ip)
			{
				$build_sql .= ' AND u_register_ip LIKE \'' . Fsb::$db->escape(str_replace('*', '%', $ip)) . "'\n";
			}

			// En fonction de la date d'inscription
			if ($joined)
			{
				$timestamp = mktime(0, 0, 0, $joined_month, $joined_day, $joined_year);
				$operator = ($joined_pos == 'after') ? '>' : '<';
				$build_sql .= ' AND u_joined ' . $operator . ' ' . $timestamp . "\n";
			}

			// En fonction de la date de derniere visite
			if ($visit)
			{
				$timestamp = mktime(0, 0, 0, $visit_month, $visit_day, $visit_year);
				$operator = ($visit_pos == 'after') ? '>' : '<';
				$build_sql .= ' AND u_last_visit ' . $operator . ' ' . $timestamp . "\n";
			}

			// En fonction du nombre de messages
			if (strlen($post))
			{
				if (!in_array($post_operator, $this->operators))
				{
					$post_operator = '=';
				}
				$build_sql .= ' AND u_total_post ' . $post_operator . ' ' . intval($post) . "\n";
			}

			// En fonction du nombre de sujets
			if (strlen($topic))
			{
				if (!in_array($topic_operator, $this->operators))
				{
					$topic_operator = '=';
				}
				$build_sql .= ' AND u_total_topic ' . $topic_operator . ' ' . intval($topic) . "\n";
			}

			// Aucun parametre entre
			if (!$build_sql)
			{
				Display::message('adm_users_prune_no_args');
			}

			// On execute la requete et on recupere les ID des membres a supprimer
			$action = array();
			$nicks = '';
			$exists = array();
			$result = Fsb::$db->query($main_sql . $build_sql);
			while ($row = Fsb::$db->row($result))
			{
				if (!isset($exists[$row['u_id']]))
				{
					$action[] = $row['u_id'];
					$nicks .= (($nicks) ? ', ' : '') . htmlspecialchars($row['u_nickname']);
					$exists[$row['u_id']] = true;
				}
			}
			Fsb::$db->free($result);

			// Aucun resultat ?
			if (!$exists)
			{
				Display::message('no_result');
			}

			Display::confirmation(sprintf(Fsb::$session->lang('adm_users_prune_confirm'), $nicks), 'index.' . PHPEXT . '?p=manage_users', array(
				'module' =>			$this->module,
				'submit_prune' =>	true,
				'delete_type' =>	$delete_type,
				'action' =>			$action
			));
		}
	}

	/**
	 * Affiche la page de gestion des galleries d'avatars
	 */
	public function page_gallery()
	{
		Fsb::$tpl->set_switch('users_gallery');
		Fsb::$tpl->set_vars(array(
			'U_ADD' =>		sid('index.' . PHPEXT . '?p=manage_users&amp;module=gallery&amp;mode=add'),
		));

		// Liste des galleries
		$fd = opendir(AVATAR_PATH . 'gallery/');
		while ($file = readdir($fd))
		{
			if ($file != '.' && $file != '..' && is_dir(AVATAR_PATH . 'gallery/' . $file))
			{
				Fsb::$tpl->set_blocks('gallery', array(
					'NAME' =>		str_replace('_', ' ', $file),
					'URL' =>		sid('index.' . PHPEXT . '?p=manage_users&amp;module=gallery&amp;mode=show&amp;dir=' . urlencode($file)),
					'U_EDIT' =>		sid('index.' . PHPEXT . '?p=manage_users&amp;module=gallery&amp;mode=edit&amp;dir=' . urlencode($file)),
					'U_DELETE' =>	sid('index.' . PHPEXT . '?p=manage_users&amp;module=gallery&amp;mode=delete&amp;dir=' . urlencode($file)),
				));
			}
		}
		closedir($fd);
	}

	/**
	 * Formulaire d'ajout de gallerie
	 */
	public function page_add_gallery()
	{
		Fsb::$tpl->set_switch('users_gallery_add');
		Fsb::$tpl->set_vars(array(
			'ADD_GALLERY' =>	Fsb::$session->lang('adm_users_gallery_' . $this->mode),
			'GALLERY_NAME' =>	htmlspecialchars(urldecode(Http::request('dir'))),
			'USE_FTP' =>		(Fsb::$cfg->get('ftp_default')) ? true : false,
			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=manage_users&amp;module=gallery&amp;mode=' . $this->mode . '&amp;dir=' . Http::request('dir')),
		));
	}

	/**
	 * Soumet le formulaire d'ajout de galleries
	 */
	public function submit_gallery()
	{
		$gallery_name = trim(Http::request('gallery_name', 'post'));
		if (!$gallery_name)
		{
			Display::message('adm_users_gallery_need_name');
		}
		$gallery_name = preg_replace('#[^a-zA-Z0-9_]#i', '_', $gallery_name);

		if ($gallery_name != Http::request('dir') && file_exists(AVATAR_PATH . 'gallery/' . $gallery_name))
		{
			Display::message('adm_users_gallery_exists');
		}

		// Instance d'un objet File
		$file = File::factory(Http::request('use_ftp'));

		if ($this->mode == 'add')
		{
			// Creation de la gallerie
			$file->mkdir('images/avatars/gallery/' . $gallery_name);
			$file->chmod('images/avatars/gallery/' . $gallery_name, 0777);
		}
		else
		{
			// Renommage de la gallerie
			$file->rename('images/avatars/gallery/' . urldecode(Http::request('dir')), 'images/avatars/gallery/' . $gallery_name);
		}

		Display::message('adm_users_gallery_well_' . $this->mode, 'index.' . PHPEXT . '?p=manage_users&amp;module=gallery', 'manage_users');
	}

	/**
	 * Suppression d'une gallerie
	 */
	public function page_delete_gallery()
	{
		$dir = urldecode(Http::request('dir'));
		$dir = ROOT . 'images/avatars/gallery/' . str_replace('../', '', $dir);
		if (is_dir($dir))
		{
			$fd = opendir($dir);
			while ($file = readdir($fd))
			{
				if ($file != '.' && $file != '..' && is_writable($dir . '/' . $file))
				{
					@unlink($dir . '/' . $file);
				}
			}
			closedir($fd);
			@rmdir($dir);
		}
		Http::redirect('index.' . PHPEXT . '?p=manage_users&module=gallery');
	}

	/**
	 * Affiche le contenu d'une gallerie
	 */
	public function page_show_gallery()
	{
		$dir = urldecode(Http::request('dir'));
		$path = AVATAR_PATH . 'gallery/' . $dir . '/';
		if (!is_dir($path))
		{
			Display::message('no_result');
		}

		Fsb::$tpl->set_switch('users_gallery_show');
		Fsb::$tpl->set_vars(array(
			'GALLERY_NAME' =>	str_replace('_', ' ', $dir),
			'U_ADD' =>			sid('index.' . PHPEXT . '?p=manage_users&amp;module=gallery&amp;mode=add_avatar&amp;file=' . urlencode($dir)),
		));

		// Liste des avatars
		$fd = opendir($path);
		while ($file = readdir($fd))
		{
			if ($file != '.' && $file != '..' && is_file($path . $file) && preg_match('#\.(' . implode('|', Upload::$img) . ')$#i', $file))
			{
				Fsb::$tpl->set_blocks('avatar', array(
					'IMG' =>	$path . $file,
					'U_DELETE' =>	sid('index.' . PHPEXT . '?p=manage_users&amp;module=gallery&amp;mode=delete_avatar&amp;file=' . urlencode($dir . '/' . $file)),
				));
			}
		}
		closedir($fd);
	}

	/**
	 * Affiche le formulaire d'ajout d'avatar
	 */
	public function page_add_avatar()
	{
		$file = urldecode(Http::request('file'));
		$file = str_replace('../', '', $file);
		Fsb::$tpl->set_switch('users_gallery_avatar');
		Fsb::$tpl->set_vars(array(
			'U_ACTION' =>	sid('index.' . PHPEXT . '?p=manage_users&amp;module=gallery&amp;mode=' . $this->mode . '&amp;file=' . urlencode($file)),
		));
	}

	/**
	 * Soumet le formulaire d'ajout d'avatar
	 */
	public function submit_avatar()
	{
		$file = urldecode(Http::request('file'));
		$file = str_replace('../', '', $file);

		$upload = new Upload('upload_avatar');
		$upload->only_img();
		$upload->store(ROOT . 'images/avatars/gallery/' . $file . '/');

		Http::redirect('index.' . PHPEXT . '?p=manage_users&module=gallery&mode=show&dir=' . urlencode($file));
	}

	/**
	 * Supprime l'avatar
	 */
	public function page_delete_avatar()
	{
		$file = urldecode(Http::request('file'));
		$file = str_replace('../', '', $file);
		$filename = ROOT . 'images/avatars/gallery/' . $file;

		if ($file && file_exists($filename) && is_writable($filename))
		{
			@unlink($filename);
		}
		Http::redirect('index.' . PHPEXT . '?p=manage_users&module=gallery&mode=show&dir=' . dirname($file));
	}
}

/* EOF */
