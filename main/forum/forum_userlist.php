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
 * Affiche la liste des membres d'un groupe
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
	 * Ordre par defaut
	 *
	 * @var string
	 */
	public $default_order = 'u_total_post';
	
	/**
	 * Asc/Desc
	 *
	 * @var string
	 */
	public $default_direction = 'DESC';
	
	/**
	 * Nombre de membres par defaut
	 *
	 * @var int
	 */
	public $default_limit = 30;
	
	/**
	 * Ordre
	 *
	 * @var string
	 */
	public $order;
	
	/**
	 * Asc/Desc
	 *
	 * @var string
	 */
	public $direction;
	
	/**
	 * Nombre de membres max
	 *
	 * @var int
	 */
	public $limit;
	
	/**
	 * ID
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Utilisateur cherché
	 *
	 * @var string
	 */
	public $search_user;
	
	/**
	 * Groupe
	 *
	 * @var array
	 */
	public $group_data;
	
	/**
	 * Modérateur du groupe ?
	 *
	 * @var bool
	 */
	public $is_group_moderator = false;
	
	/**
	 * Type de recherche
	 *
	 * @var string
	 */
	public $like = 'in';
	
	/**
	 * Module par defaut
	 *
	 * @var int
	 */
	public $module = USERLIST_SIMPLE;
	
	/**
	 * Nombre max
	 *
	 * @var int
	 */
	public $max_size = 60;
	
	/**
	 * Liste des ordres
	 *
	 * @var array
	 */
	public $order_array = array('u_joined', 'u_nickname', 'u_total_post', 'u_total_topic', 'u_last_visit');

	/**
	 * Liste des colones a afficher
	 *
	 * @var array
	 */
	public $columns = array(
		'u_joined' =>		'date',
		'u_total_post' =>	'int',
		'u_total_topic' =>	'int',
	);

	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		// Droit d'acces ?
		if (!Fsb::$session->is_authorized('can_see_memberlist'))
		{
			Display::message('not_allowed');
		}

		$this->search_user =	Http::request('search_user', 'post|get');
		$this->like =			Http::request('like', 'post|get');
		$this->order =			Http::request('order', 'post|get');
		$this->module =			Http::request('module');
		$this->direction =		strtoupper(Http::request('direction', 'post|get'));
		$this->id =				intval(Http::request('g_id', 'post|get'));
		$this->limit =			intval(Http::request('limit', 'post|get'));
		$this->page =			intval(Http::request('page', 'post|get'));

		if (!$this->id)
		{
			$this->id = GROUP_SPECIAL_USER;
		}

		// On verifie quel type de formatage de la liste des membres on utilise. Si ce formatage est different de celui stoque
		// dans le profil utilisateur, on met a jour celui ci
		if ($this->module != USERLIST_SIMPLE && $this->module != USERLIST_ADVANCED)
		{
			$this->module = Fsb::$session->data['u_activate_userlist'];
		}
		else if ($this->module != Fsb::$session->data['u_activate_userlist'])
		{
			Fsb::$db->update('users', array(
				'u_activate_userlist' =>	$this->module,
			), 'WHERE u_id = ' . Fsb::$session->id());
		}

		if (!$this->order || !in_array($this->order, $this->order_array))
		{
			$this->order = $this->default_order;
		}

		if ($this->direction != 'ASC' && $this->direction != 'DESC')
		{
			$this->direction = $this->default_direction;
		}

		if (!$this->limit || $this->limit <= 0)
		{
			$this->limit = $this->default_limit;
		}
		
		if (!$this->page || $this->page <= 0)
		{
			$this->page = 1;
		}

		$this->get_group_data();

		if (Http::request('submit_delete', 'post') && $this->is_group_moderator)
		{
			$this->delete_users();
		}
		else if (Http::request('submit_add', 'post') && $this->is_group_moderator)
		{
			$this->valid_users();
		}
		else if (Http::request('submit_add_user', 'post') && $this->is_group_moderator)
		{
			// Ajout de membre par un moderateur de groupe
			$this->add_user(true);
		}
		else if (Http::request('register_submit', 'post') && is_null($this->group_data['gu_status']))
		{
			// Ajout de membre
			$this->add_user(false);
		}
		else
		{
			// Liste des membres du groupe
			$this->show_userlist();
		}
	}

	/**
	 * Donnees du groupe
	 *
	 */
	public function get_group_data()
	{
		$sql = 'SELECT g.*, gu.gu_status
				FROM ' . SQL_PREFIX . 'groups g
				LEFT JOIN ' . SQL_PREFIX . 'groups_users gu
					ON gu.g_id = g.g_id
						AND gu.u_id = ' . Fsb::$session->id() . '
				WHERE g.g_id = ' . $this->id . '
					AND g.g_id <> ' . GROUP_SPECIAL_VISITOR . '
					AND g.g_type <> ' . GROUP_SINGLE;
		$result = Fsb::$db->query($sql);
		$this->group_data = Fsb::$db->row($result);
		Fsb::$db->free($result);

		if (!$this->group_data)
		{
			Display::message('userlist_group_not_exists');
		}

		// Le membre parcourant la page est il moderateur ?
		$this->is_group_moderator = (($this->group_data['gu_status'] == GROUP_MODO || Fsb::$session->auth() >= MODOSUP) && $this->group_data['g_type'] != GROUP_SPECIAL) ? true : false;

		// Le membre peut il voir ce groupe ?
		if ($this->group_data['g_hidden'] == GROUP_HIDDEN && !$this->is_group_moderator && is_null($this->group_data['gu_status']))
		{
			Display::message('not_allowed');
		}
	}
	
	/**
	 * Affiche la liste des membres en fonction du resultat de la recherche
	 *
	 */
	public function show_userlist()
	{
		Fsb::$tpl->set_file('forum/forum_userlist.html');

		// On affiche les noms des colones dynamiques
		$this->show_columns();

		$where_count = '';
		if (!$this->is_group_moderator)
		{
			$where_count .= ' AND gu.gu_status <> ' . GROUP_WAIT;
		}

		if ($this->search_user)
		{
			$like_begin = ($this->like == 'in' || $this->like == 'end') ? '%' : '';
			$like_end = ($this->like == 'in' || $this->like == 'begin') ? '%' : '';
			$where_count .= ' AND u.u_nickname ' . Fsb::$db->like() . ' \'' . $like_begin . Fsb::$db->escape($this->search_user) . $like_end . '\'';
		}
		
		// On recupere le nombre de membre du groupe et le nombre de page
		$sql = 'SELECT COUNT(gu.u_id) AS total
				FROM ' . SQL_PREFIX . 'groups_users gu
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = gu.u_id
				WHERE gu.g_id = ' . $this->id . '
					' . $where_count;
		$total = Fsb::$db->get($sql, 'total');
		$total_page = ceil($total / $this->limit);

		if ($this->is_group_moderator)
		{
			Fsb::$tpl->set_switch('is_group_moderator');
		}

		// Pagination ?
		if ($total_page > 1)
		{
			Fsb::$tpl->set_switch('pagination');
		}

		// On affiche les informations du groupe ?
		if ($this->group_data['g_type'] == GROUP_NORMAL)
		{
			Fsb::$tpl->set_switch('group_information');
			if ($this->group_data['g_open'] && is_null($this->group_data['gu_status']))
			{
				Fsb::$tpl->set_switch('group_register');
			}
		}

		// Nom du groupe
		$group_lang = ($this->group_data['g_type'] == GROUP_SPECIAL && Fsb::$session->lang($this->group_data['g_name'])) ? Fsb::$session->lang($this->group_data['g_name']) : $this->group_data['g_name'];

		// Type de recherche sur le pseudonyme
		$list_like = array(
			'begin' =>	Fsb::$session->lang('userlist_nickname_begin'),
			'in' =>		Fsb::$session->lang('userlist_nickname_in'),
			'end' =>	Fsb::$session->lang('userlist_nickname_end'),
		);

		// Listes pour trier les membres
		foreach ($this->order_array AS $key)
		{
			$order_list[$key] = Fsb::$session->lang('userlist_order_' . $key);
		}

		$direction_list = array(
			'ASC' =>	Fsb::$session->lang('asc'),
			'DESC' =>	Fsb::$session->lang('desc'),
		);

		// Type d'affichage
		if ($this->module == USERLIST_SIMPLE)
		{
			$userlist_module = Fsb::$session->lang('userlist_module_advanced');
			$userlist_url = '&amp;module=' . USERLIST_ADVANCED;
			Fsb::$tpl->set_switch('interface_simple');
		}
		else
		{
			$userlist_module = Fsb::$session->lang('userlist_module_simple');
			$userlist_url = '&amp;module=' . USERLIST_SIMPLE;
			Fsb::$tpl->set_switch('interface_advanced');
		}

		Fsb::$tpl->set_vars(array(
			'SEARCH_USER' =>			htmlspecialchars($this->search_user),
			'USERLIST_LIMIT' =>			$this->limit,
			'USERLIST_COLSPAN' =>		count($this->columns) + 2 + (($this->is_group_moderator) ? 1 : 0),
			'GROUP_LIST' =>				sprintf(Fsb::$session->lang('userlist_group_list'), $group_lang),
			'PAGINATION' =>				Html::pagination($this->page, $total_page, $this->generate_url()),
			'GROUP_STATUS_HIDDEN' =>	($this->group_data['g_hidden'] == GROUP_HIDDEN) ? Fsb::$session->lang('userlist_status_invisible') : Fsb::$session->lang('userlist_status_visible'),
			'GROUP_STATUS_OPEN' =>		($this->group_data['g_open']) ? Fsb::$session->lang('userlist_status_open') : Fsb::$session->lang('userlist_status_close'),
			'GROUP_DESCRIPTION' =>		$this->group_data['g_desc'],
			'LIST_SEARCH' =>			Html::make_list('like', $this->like, $list_like),
			'LIST_ORDER' =>				Html::make_list('order', $this->order, $order_list),
			'LIST_DIRECTION' =>			Html::make_list('direction', $this->direction, $direction_list),
			'LIST_GROUP' =>				Html::list_groups('g_id', GROUP_NORMAL|GROUP_SPECIAL, $this->id, false, array(GROUP_SPECIAL_VISITOR)),
			'USERLIST_MODULE' =>		$userlist_module,
			'AVATAR_MAX_SIZE' =>		$this->max_size,

			'U_ACTION' =>				$this->generate_url(),
			'U_ORDER_NICKNAME' =>		$this->generate_url('u_nickname', (($this->direction == 'DESC' || $this->order != 'u_nickname') ? 'ASC' : 'DESC')),
			'U_USERLIST_MODULE' =>		$this->generate_url($this->order, $this->direction) . $userlist_url . '&amp;page=' . $this->page,
		));

		// Recherche d'un membre ?
		$sql_search_user = '';
		if ($this->search_user)
		{
			$like_begin = ($this->like == 'in' || $this->like == 'end') ? '%' : '';
			$like_end = ($this->like == 'in' || $this->like == 'begin') ? '%' : '';
			$sql_search_user = ' AND u.u_nickname ' . Fsb::$db->like() . ' \'' . $like_begin . Fsb::$db->escape($this->search_user) . $like_end . '\'';
		}
		
		// On recupere les ID des membres pour optimiser la requete generale
		$sql = 'SELECT gu.u_id
				FROM ' . SQL_PREFIX . 'groups_users gu
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON gu.u_id = u.u_id
				WHERE gu.g_id = ' . $this->id
					. ((!$this->is_group_moderator) ? ' AND (gu.gu_status <> ' . GROUP_WAIT . ' OR gu.u_id = ' . Fsb::$session->id() . ')' : '')
					. $sql_search_user
			. ' ORDER BY ' . Fsb::$db->escape($this->order) . ' ' . Fsb::$db->escape($this->direction) . ', gu.gu_status DESC
			LIMIT ' . ($this->page - 1) * $this->limit . ', ' . $this->limit;
		$result = Fsb::$db->query($sql);
		$idx = array();
		while ($row = Fsb::$db->row($result))
		{
			$idx[] = $row['u_id'];
		}
		Fsb::$db->free($result);
		
		if (!$idx)
		{
			return ;
		}

		// On affiche les membres
		$sql = 'SELECT gu.gu_status, g.g_id, g.g_name, g.g_color, g.g_type, u.u_id, u.u_nickname, u.u_color, u.u_avatar, u.u_avatar_method, u.u_can_use_avatar, u.u_sexe, u.u_birthday, u.u_activate_hidden, u.u_last_visit, u.' . implode(', u.', array_keys($this->columns)) . '
				FROM ' . SQL_PREFIX . 'groups_users gu
				INNER JOIN ' . SQL_PREFIX . 'users u
					ON gu.u_id = u.u_id
				LEFT JOIN ' . SQL_PREFIX . 'groups g
					ON g.g_id = u.u_default_group_id
				WHERE gu.g_id = ' . $this->id . '
					AND gu.u_id IN (' . implode(', ', $idx) . ')
				ORDER BY ' . Fsb::$db->escape($this->order) . ' ' . Fsb::$db->escape($this->direction) . ', gu.gu_status DESC';
		$i = 1;
		$indent = 0;
		$type_array = array();
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			if (!isset($type_array[$row['gu_status']]))
			{
				$type_array[$row['gu_status']] = true;
				$indent = 0;
			}

			// Affichage de l'avatar
			$avatar = User::get_avatar($row['u_avatar'], $row['u_avatar_method'], $row['u_can_use_avatar']);
			if ($row['u_avatar_method'] == AVATAR_METHOD_UPLOAD || $row['u_avatar_method'] == AVATAR_METHOD_GALLERY || $avatar == ROOT . 'images/avatars/noavatar.gif')
			{
				list($width, $height) = @getimagesize($avatar);
				if ($width > $this->max_size || $height > $this->max_size)
				{
					$max = max($width, $height);
					$width = $width / $max * $this->max_size;
					$height = $height / $max * $this->max_size;
				}
			}
			else
			{
				$width = $height = null;
			}

			// Age du membre
			$age = User::get_age($row['u_birthday']);
			if ($age)
			{
				$age = sprintf(Fsb::$session->lang('age_format'), $age);
			}

			// Date de derniere visite
			$last_visit = '';
			if (!$row['u_activate_hidden'] || Fsb::$session->auth() >= MODO || Fsb::$session->id() == $row['u_id'])
			{
				$last_visit = Fsb::$session->print_date($row['u_last_visit']);
			}

			Fsb::$tpl->set_blocks('user', array(
				'ID' =>				$row['u_id'],
				'INDENT' =>			$indent,
				'RESULT' =>			(($this->page - 1) * $this->limit) + $i,
				'CAT_SEPARATOR' =>	($indent == 0) ? Fsb::$session->lang('userlist_group_type_' . $row['gu_status']) : null,
				'NICKNAME' =>		Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
				'DISABLED' =>		($row['gu_status'] == GROUP_MODO) ? true : false,
				'AVATAR' =>			$avatar,
				'AVATAR_WIDTH' =>	round($width),
				'AVATAR_HEIGHT' =>	round($height),
				'SEXE' =>			User::get_sexe($row['u_sexe']),
				'AGE' =>			$age,
				'JOINED' =>			Fsb::$session->print_date($row['u_joined']),
				'TOTAL_POSTS' =>	$row['u_total_post'],
				'TOTAL_TOPICS' =>	$row['u_total_topic'],
				'DISPLAY_ONLINE' => (Fsb::$mods->is_active('update_last_visit')) ? true : false,
				'IS_ONLINE' =>		($row['u_last_visit'] > (CURRENT_TIME - ONLINE_LENGTH) && !$row['u_activate_hidden']) ? true : false,
				'LAST_VISIT' =>		$last_visit,
				'GROUP_NAME' =>		($row['g_type'] == GROUP_SPECIAL && Fsb::$session->lang($row['g_name'])) ? Fsb::$session->lang($row['g_name']) : $row['g_name'],
				'GROUP_COLOR' =>	$row['g_color'],

				'U_PROFILE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=userprofile&amp;id=' . $row['u_id']),
				'U_GROUP' =>		sid(ROOT . 'index.' . PHPEXT . '?p=userlist&amp;g_id=' . $row['g_id']),
				'U_SEARCH_POSTS' =>	sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=author&amp;id=' . $row['u_id']),
				'U_SEARCH_TOPICS' =>sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=author_topic&amp;id=' . $row['u_id']),
			));

			// On affiche les valeurs des colones dynamiques
			$this->show_columns_value($row);
			$i++;
			$indent++;
		}
		Fsb::$db->free($result);
	}
	
	/**
	 * Cree l'URL a passee pour les pages de la liste des membres
	 *
	 * @param unknown_type $order Ordre a passer dans l'url
	 * @param unknown_type $direction Sens du tri a passer dans l'url
	 * @return string
	 */
	public function generate_url($order = '', $direction = '')
	{
		return (sid(ROOT . 'index.' . PHPEXT . '?p=userlist&amp;g_id=' . $this->id . '&amp;order=' . (($order) ? $order : $this->order) . '&amp;direction=' . (($direction) ? $direction : $this->direction) . '&amp;search_user=' . $this->search_user . '&amp;limit=' . $this->limit . '&amp;like=' . $this->like));
	}

	/**
	 * Affiche les noms des colones dynamiques
	 *
	 */
	public function show_columns()
	{
		foreach ($this->columns AS $key => $value)
		{
			Fsb::$tpl->set_blocks('column', array(
				'NAME' =>		(Fsb::$session->lang('userlist_column_' . $key)) ? Fsb::$session->lang('userlist_column_' . $key) : $key,

				'U_ORDER' =>	$this->generate_url($key, ($this->direction == 'DESC' || $this->order != $key) ? 'ASC' : 'DESC'),
			));
		}
	}

	/**
	 * Affiche les valeurs des colones dynamiques
	 *
	 * @param unknown_type $row Valeur pour le membre
	 */
	public function show_columns_value(&$row)
	{
		foreach ($this->columns AS $key => $value)
		{
			$name = $row[$key];
			switch ($value)
			{
				case 'int' :
					$name = intval($name);
				break;

				case 'string' :
					$name = htmlspecialchars($name);
				break;

				case 'date' :
					$name = Fsb::$session->print_date($name);
				break;
			}

			Fsb::$tpl->set_blocks('user.column', array(
				'NAME' =>		$name,
			));
		}
	}

	/**
	 * Suppression de membres du groupe
	 *
	 */
	public function delete_users()
	{
		$action = (array) Http::request('action', 'post');
		$action = array_map('intval', $action);

		if ($action)
		{
			// On verifie si le groupe est bien normal
			$sql = 'SELECT g_id
					FROM ' . SQL_PREFIX . 'groups
					WHERE g_id = ' . $this->id . '
						AND g_type = ' . GROUP_NORMAL;
			if ($row = Fsb::$db->request($sql))
			{
				// Suppression des membres du groupe
				Group::delete_users($action, $this->id, true, true);

				// On supprime le rang des membres si le groupe en avait un
				Fsb::$db->update('users', array(
					'u_rank_id' =>		0,
				), 'WHERE u_id IN (' . implode(', ', $action) . ') AND u_rank_id = ' . $this->group_data['g_rank']);

				// On redonne un rang correct aux membres
				$sql = 'SELECT gu.u_id, g.g_rank
						FROM ' . SQL_PREFIX . 'groups_users gu
						INNER JOIN ' . SQL_PREFIX . 'groups g
							ON gu.g_id = g.g_id
						WHERE gu.u_id IN (' . implode(', ', $action) . ')
						GROUP BY gu.u_id, g.g_rank';
				$result = Fsb::$db->query($sql);
				while ($row = Fsb::$db->row($result))
				{
					Fsb::$db->update('users', array(
						'u_rank_id' =>		(int) $row['g_rank'],
					), 'WHERE u_id = ' . $row['u_id'] . ' AND u_rank_id = 0');
				}
				Fsb::$db->free($result);
			}
		}

		Display::message('userlist_submit_delete', ROOT . 'index.' . PHPEXT . '?p=userlist&amp;g_id=' . $this->id, 'forum_userlist');
	}

	/**
	 * Validation de membres du groupe
	 *
	 */
	public function valid_users()
	{
		$action = (array) Http::request('action', 'post');
		$action = array_map('intval', $action);

		if ($action)
		{
			// On verifie que les membres sont bien dans le groupe
			$sql = 'SELECT u_id
					FROM ' . SQL_PREFIX . 'groups_users
					WHERE g_id = ' . $this->id . '
						AND gu_status = ' . GROUP_WAIT;
			$result = Fsb::$db->query($sql);
			$idx = array();
			while ($row = Fsb::$db->row($result))
			{
				if (in_array($row['u_id'], $action))
				{
					$idx[] = $row['u_id'];
				}
			}
			Fsb::$db->free($result);

			if ($idx)
			{
				// On verifie si le groupe est bien normal
				$sql = 'SELECT g_id
						FROM ' . SQL_PREFIX . 'groups
						WHERE g_id = ' . $this->id . '
							AND g_type = ' . GROUP_NORMAL . '';
				$row = Fsb::$db->request($sql);

				// Validation des membres selectionnes
				if ($row)
				{
					Group::delete_users($idx, $this->id, false);
					Group::add_users($idx, $this->id, GROUP_USER);
				}
			}
		}

		Display::message('userlist_submit_add', ROOT . 'index.' . PHPEXT . '?p=userlist&amp;g_id=' . $this->id, 'forum_userlist');
	}

	/**
	 * Ajout d'un membre au groupe
	 *
	 * @param bool $admin true si le membre est ajoute au groupe par un moderateur du groupe
	 */
	public function add_user($admin)
	{
		$add_login = ($admin) ? trim(Http::request('add_login', 'post')) : Fsb::$session->data['u_nickname'];
		if ($add_login)
		{
			$sql = 'SELECT u.u_id, gu.gu_status, g.g_hidden
					FROM ' . SQL_PREFIX . 'users u
					LEFT JOIN ' . SQL_PREFIX . 'groups_users gu
						ON u.u_id = gu.u_id
							AND gu.g_id = ' . $this->id . '
                                        LEFT JOIN ' . SQL_PREFIX . 'groups g
                                                ON g.g_id = ' . $this->id . '
					WHERE LOWER(u.u_nickname) = \'' . Fsb::$db->escape(String::strtolower($add_login)) . '\'
						AND u.u_id <> ' . VISITOR_ID;
			if (!$row = Fsb::$db->request($sql))
			{
				Display::message('user_not_exists');
			}

			if (!is_null($row['gu_status']))
			{
				Display::message('userlist_user_in_group');
			}

			// Ajout de l'utilisateur
                        /* On ne met pas à jour le groupe par défaut si
                           c'est un groupe invisible */
			Group::add_users($row['u_id'], $this->id, ($admin) ? GROUP_USER : GROUP_WAIT, true, false, ($admin && $row['g_hidden'] != GROUP_HIDDEN) ? true : false);

			// On donne un rang au membre s'il n'en avait pas
			Fsb::$db->update('users', array(
				'u_rank_id' =>		$this->group_data['g_rank'],
			), 'WHERE u_id = ' . $row['u_id'] . ' AND u_rank_id = 0');
		}
		

		Display::message('userlist_submit_add_user', ROOT . 'index.' . PHPEXT . '?p=userlist&amp;g_id=' . $this->id, 'forum_userlist');
	}
}

/* EOF */
