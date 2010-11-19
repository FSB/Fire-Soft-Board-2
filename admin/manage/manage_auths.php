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
 * Page de gestion des droits des forums, groupes et membres
 * Tous les droits sont geres a l'aide de groupes, la seule difference entre la gestion
 * des droits des forums et des groupes est que l'affichage se fait en fonction d'un des
 * deux parametres. Les droits des membres se raprochent de droits d'un groupe unique en
 * fonction de forums.
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Model d'autorisation
	 *
	 * @var unknown_type
	 */
	public $auth_model;
	
	/**
	 * Mode de la page
	 *
	 * @var string
	 */
	public $mode;
	
	/**
	 * Identifiant du forum/groupe/membre
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Type d'edition des autorisations
	 *
	 * @var string
	 */
	public $mode_type;
	
	/**
	 * Identifiant
	 *
	 * @var int
	 */
	public $this_id;
	
	/**
	 * Module de la page
	 *
	 * @var string
	 */
	public $module;

	/**
	 * Status du membre (droits des membres)
	 *
	 * @var int
	 */
	public $users_status = VISITOR;
	
	/**
	 * Pseudonyme du membre
	 *
	 * @var string
	 */
	public $nickname;
	
	/**
	 * Identifiant du membre
	 *
	 * @var int
	 */
	public $u_id;

	/**
	 * Erreurs
	 *
	 * @var array
	 */
	public $errstr = array();

	/**
	 * Constructeur
	 */
	public function main()
	{
		$this->mode =		Http::request('mode');
		$this->id =			Http::request('id');
		$this->this_id =	intval(Http::request('this_id'));
		$this->mode_type =	Http::request('mode_type');

		$call = new Call($this);
		$call->module(array(
			'list' =>		array('forums', 'groups', 'users', 'others', 'check'),
			'url' =>		'index.' . PHPEXT . '?p=manage_auths',
			'lang' =>		'adm_auths_module_',
			'default' =>	'forums',
		));

		// On recupere le type de mode en fonction du cookie
		if (is_null($this->mode_type))
		{
			$this->mode_type = Http::getcookie('mode_type');
			if (!in_array($this->mode_type, array(MODE_TYPE_EASY, MODE_TYPE_SIMPLE, MODE_TYPE_ADVANCED)))
			{
				$this->mode_type = MODE_TYPE_SIMPLE;
			}

			if ($this->module != 'forums' && $this->mode_type == MODE_TYPE_EASY)
			{
				$this->mode_type = MODE_TYPE_SIMPLE;
			}
		}

		// Model choisi ?
		if (Http::request('submit_auth_model', 'post'))
		{
			$this->auth_model = Http::request('auth_model', 'post');
		}

		switch ($this->module)
		{
			case 'users' :
				$this->get_auths_users_group();
			break;

			default :
				$this->mode = (!$this->this_id) ? '' : 'view';
			break;
		}

		if (Http::request('submit_users_status', 'post'))
		{
			$this->get_auths_users_group();
			$this->submit_users_status();
		}
		else if (Http::request('change_type', 'post') || $this->auth_model)
		{
			$this->mode = 'view';
			$this->this_id = $this->id;
			Http::cookie('mode_type', $this->mode_type, CURRENT_TIME + ONE_YEAR);
		}
		else if (Http::request('submit', 'post'))
		{
			$this->mode = 'change';
		}

		$call->functions(array(
			'module' => array(
				'check' =>	'show_check_auths',
				'others' =>	'show_others_auths',
			),
			'mode' => array(
				'view' => array(
					'module' => array(
						'forums' =>		'page_view_forums_auths',
						'groups' =>		'page_view_groups_auths',
						'users' =>		'page_view_users_auths',
					),
				),
				'change' =>	'page_change_auths',
				'default' => array(
					'module' => array(
						'forums' =>		'page_default_forums_auths',
						'groups' =>		'page_default_groups_auths',
						'users' =>		'page_default_users_auths',
					),
				),
			),
		));
	}
	
	/**
	 * Recupere le groupe unique du membre selectione
	 */
	public function get_auths_users_group()
	{
		if (($nickname = trim(Http::request('auth_nickname', 'post'))) || $this->this_id)
		{
			$sql = 'SELECT u_id, u_nickname, u_auth, u_single_group_id
					FROM ' . SQL_PREFIX . 'users
					WHERE ' . ((!$this->this_id) ? 'u_nickname = \'' . Fsb::$db->escape($nickname) . '\'' : 'u_id = ' . $this->this_id) . '
						AND u_id <> ' . VISITOR_ID;
			$result = Fsb::$db->query($sql);
			$row = Fsb::$db->row($result);
			Fsb::$db->free($result);

			if ($row)
			{
				$this->this_id = $row['u_single_group_id'];
				$this->mode = 'view';
				$this->users_status = ($row['u_auth'] == FONDATOR) ? ADMIN : $row['u_auth'];
				$this->nickname = $row['u_nickname'];
				$this->u_id = $row['u_id'];
			}
			else
			{
				$this->this_id = null;
				$this->mode = '';
				$this->nickname = trim(Http::request('auth_nickname', 'post'));
				$this->u_id = null;
				$this->errstr[] = Fsb::$session->lang('user_not_exists');
			}
		}
	}

	/**
	 * Affiche la page par defaut de la gestion des autorisations des forums
	 */
	public function page_default_forums_auths()
	{
		Fsb::$tpl->set_switch('auths_select');
		Fsb::$tpl->set_vars(array(
			'L_DEFAULT_TITLE' =>	Fsb::$session->lang('adm_auths_module_forums'),
			'L_DEFAULT_CHOOSE' =>	Fsb::$session->lang('adm_auths_choose_forum'),
			'LIST_DEFAULT' =>		Html::list_forums(get_forums(), '', 'this_id', false),

			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=manage_auths&amp;module=forums'),
		));
	}

	/**
	 * Affiche la page par defaut de la gestion des autorisations des groupes
	 */
	public function page_default_groups_auths()
	{
		Fsb::$tpl->set_switch('auths_select');
		Fsb::$tpl->set_vars(array(
			'L_DEFAULT_TITLE' =>	Fsb::$session->lang('adm_auths_module_groups'),
			'L_DEFAULT_CHOOSE' =>	Fsb::$session->lang('adm_auths_choose_group'),
			'LIST_DEFAULT' =>		Html::list_groups('this_id', GROUP_SPECIAL | GROUP_NORMAL, $this->this_id),

			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=manage_auths&amp;module=groups'),
		));
	}

	/**
	 * Affiche la page par defaut de gestion des autorisations du membre
	 */
	public function page_default_users_auths()
	{
		Fsb::$tpl->set_switch('auths_select_user');
		Fsb::$tpl->set_vars(array(
			'ERRSTR' =>				($this->errstr) ? Html::make_errstr($this->errstr) : null,
			'CURRENT_NICKNAME' =>	$this->nickname,

			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=manage_auths&amp;module=users'),
		));
	}

	/**
	 * Page de gestion des droits des forums
	 */
	public function page_view_forums_auths()
	{
		// ID du forum a charger (peut etre issu d'un model)
		$sql_id = ($this->auth_model) ? $this->auth_model : $this->this_id;

		$sql = 'SELECT g.g_name, g.g_id AS real_this_id, g.g_color, g.g_type, ga.*
					FROM ' . SQL_PREFIX . 'groups g
					LEFT JOIN ' . SQL_PREFIX . 'groups_auth ga
						ON g.g_id = ga.g_id
							AND ga.f_id = ' . $sql_id . '
					WHERE g.g_type IN ('.GROUP_SPECIAL.','.GROUP_NORMAL.')
					ORDER BY g.g_type, g.g_id';
		$result = Fsb::$db->query($sql);

		$list_mode_ary = array(
			MODE_TYPE_EASY =>			Fsb::$session->lang('adm_auths_type_easy'),
			MODE_TYPE_SIMPLE =>			Fsb::$session->lang('adm_auths_type_simple'),
			MODE_TYPE_ADVANCED =>		Fsb::$session->lang('adm_auths_type_advanced'),
		);
		$get_forums = get_forums();

		Fsb::$tpl->set_switch('show_model');
		Fsb::$tpl->set_switch('show_list');
		Fsb::$tpl->set_vars(array(
			'L_LIST_NAME' =>		Fsb::$session->lang('adm_auths_choose_forum'),
			'L_ADM_AUTHS_MODEL' =>	Fsb::$session->lang('adm_auths_model_forum'),

			'LIST_CHOOSE' =>		Html::list_forums($get_forums, $this->this_id, 'this_id', false),
			'LIST_MODEL' =>			Html::list_forums($get_forums, $this->auth_model, 'auth_model', false),
		));

		$this->page_view_auths($result, 'forums', $list_mode_ary);
	}

	/**
	 * Page de gestion des droits des groupes
	 */
	public function page_view_groups_auths()
	{
		// ID du groupe a charger (peut etre issu d'un model)
		$sql_id = ($this->auth_model) ? $this->auth_model : $this->this_id;

		$sql = 'SELECT f.f_id AS real_this_id, f.f_level, f.f_name, ga.*
					FROM ' . SQL_PREFIX . 'forums f
					LEFT JOIN ' . SQL_PREFIX . 'groups_auth ga
						ON f.f_id = ga.f_id
							AND ga.g_id = ' . $sql_id . '
					ORDER BY f.f_left';
		$result = Fsb::$db->query($sql);

		$list_mode_ary = array(
			MODE_TYPE_SIMPLE =>			Fsb::$session->lang('adm_auths_type_simple'),
			MODE_TYPE_ADVANCED =>		Fsb::$session->lang('adm_auths_type_advanced'),
		);

		Fsb::$tpl->set_switch('show_model');
		Fsb::$tpl->set_switch('show_list');
		Fsb::$tpl->set_vars(array(
			'L_LIST_NAME' =>		Fsb::$session->lang('adm_auths_choose_group'),
			'L_ADM_AUTHS_MODEL' =>	Fsb::$session->lang('adm_auths_model_groups'),

			'LIST_CHOOSE' =>		Html::list_groups('this_id', GROUP_SPECIAL | GROUP_NORMAL, $this->this_id),
			'LIST_MODEL' =>			Html::list_groups('auth_model', GROUP_SPECIAL | GROUP_NORMAL, $this->auth_model),
		));

		$this->page_view_auths($result, 'groups', $list_mode_ary);
	}

	/**
	 * Page de gestion des droits des membres
	 */
	public function page_view_users_auths()
	{
		// Requete de selection des droits
		$sql = 'SELECT f.f_id AS real_this_id, f.f_level, f.f_name, ga.*
				FROM ' . SQL_PREFIX . 'forums f
				LEFT JOIN ' . SQL_PREFIX . 'groups_auth ga
					ON f.f_id = ga.f_id
						AND ga.g_id = ' . $this->this_id . '
				ORDER BY f.f_left';
		$result = Fsb::$db->query($sql);

		$list_mode_ary = array(
			MODE_TYPE_SIMPLE =>			Fsb::$session->lang('adm_auths_type_simple'),
			MODE_TYPE_ADVANCED =>		Fsb::$session->lang('adm_auths_type_advanced'),
		);

		Fsb::$tpl->set_switch('show_users_status');
		Fsb::$tpl->set_vars(array(
			'CURRENT_NICKNAME' =>		$this->nickname,
		));
		$this->page_view_auths($result, 'users', $list_mode_ary);
	}

	/**
	 * Affichage du formulaire avec les listes deroulantes (les trois types)
	 *
	 * @param resource $result Resultat de la requete SQL
	 * @param string $type 
	 * @param array $list_mode_ary Liste des modes
	 */
	public function page_view_auths($result, $type, $list_mode_ary)
	{
		// Liste des modes pour la page
		$list_mode = Html::make_list('mode_type', $this->mode_type, $list_mode_ary);

		// Liste des status de membre
		$list_users_status = Html::make_list('users_status', $this->users_status, array(
			VISITOR =>		'----------',
			MODOSUP =>		Fsb::$session->lang('modosup'),
			ADMIN =>		Fsb::$session->lang('admin'),
		));

		switch ($this->mode_type)
		{
			case MODE_TYPE_ADVANCED :
				//
				// Affichage de la page pour le mode avance
				//

				Fsb::$tpl->set_switch('auths_advanced');
				Fsb::$tpl->set_switch('list_mode');
			
				Fsb::$tpl->set_vars(array(
					'LIST_MODE' =>			$list_mode,
					'LIST_USERS_STATUS' =>	$list_users_status,
					'COLSPAN' =>			count($GLOBALS['_auth_type']) + 2,
					'TABLE_WIDTH' =>		170 + (count($GLOBALS['_auth_type']) * 100),
					'HIDDEN' =>				Html::hidden('id', $this->this_id),
					'HIDDEN_MODE_TYPE' =>	Html::hidden('hidden_mode_type', $this->mode_type),
			
					'U_ACTION' =>			sid('index.' . PHPEXT . '?p=manage_auths&amp;module=' . $this->module),
				));

				foreach ($GLOBALS['_auth_type'] AS $value)
				{
					Fsb::$tpl->set_blocks('auth', array(
						'KEY' =>			$value,
						'AUTH_NAME' =>		Fsb::$session->lang('auth_' . $value),
					));
				}
			
				$id_group = array();
				$last_handler_id = null;
				while ($row = Fsb::$db->row($result))
				{
					if (!in_array($row['real_this_id'], $id_group))
					{
						$id_group[] = $row['real_this_id'];   
						switch ($type)
						{
							case 'forums' :
								if ($last_handler_id !== $row['g_type'])
								{
									$last_handler_id = $row['g_type'];
									Fsb::$tpl->set_blocks('group_cat', array(
										'NAME' =>	($row['g_type'] == GROUP_SPECIAL) ? Fsb::$session->lang('list_group_special') : Fsb::$session->lang('list_group_normal'),
									));
								}

								Fsb::$tpl->set_blocks('group_cat.group', array(
									'ID' =>				$row['real_this_id'],
									'GROUP_NAME' =>		($row['g_type'] == GROUP_SPECIAL && Fsb::$session->lang($row['g_name'])) ? Fsb::$session->lang($row['g_name']) : $row['g_name'],
									'GROUP_STYLE' =>	$row['g_color'],
								));
							break;

							case 'groups' :
							case 'users' :
								if ($row['f_level'] == 0)
								{
									Fsb::$tpl->set_blocks('group_cat', array(
										'NAME' =>	$row['f_name'],
									));
									continue 2;
								}

								Fsb::$tpl->set_blocks('group_cat.group', array(
									'ID' =>				$row['real_this_id'],
									'GROUP_NAME' =>		$row['f_name'],
									'GROUP_STYLE' =>	'',
								));
							break;
						}

						foreach ($GLOBALS['_auth_type'] AS $value)
						{
							$id =  $value . '_' . $row['real_this_id'];
							$list = Html::make_list($id, $row[$value], array(
								false =>	Fsb::$session->lang('no'),
								true =>		Fsb::$session->lang('yes'),
							),
							array(
								'id' =>	$id,
							));

							Fsb::$tpl->set_blocks('group_cat.group.g_auth', array(
								'LIST' =>	$list,
							));
							unset($list);
						}
					}
				}
				Fsb::$db->free($result);
			break;

			case MODE_TYPE_SIMPLE :
				//
				// Affichage des droits en mode normal
				//
				Fsb::$tpl->set_switch('auths_normal');
				Fsb::$tpl->set_vars(array(
					'LIST_MODE' =>			$list_mode,
					'LIST_USERS_STATUS' =>	$list_users_status,
					'COLSPAN' =>			1 + count($GLOBALS['_auth_type_format']),
					'HIDDEN' =>				Html::hidden('id', $this->this_id),
					'HIDDEN_MODE_TYPE' =>	Html::hidden('hidden_mode_type', $this->mode_type),
			
					'U_ACTION' =>			sid('index.' . PHPEXT . '?p=manage_auths&amp;module=' . $this->module),
				));

				// On affiche les colones
				foreach ($GLOBALS['_auth_type_format'] AS $cat_auth => $v)
				{
					Fsb::$tpl->set_blocks('auth', array(
						'AUTH_NAME' =>		Fsb::$session->lang('auth_format_' . $cat_auth),
					));
				}

				$last_handler_id = null;
				while ($row = Fsb::$db->row($result))
				{
					switch ($type)
					{
						case 'forums' :
							if ($last_handler_id !== $row['g_type'])
							{
								$last_handler_id = $row['g_type'];
								Fsb::$tpl->set_blocks('group_cat', array(
									'NAME' =>	($row['g_type'] == GROUP_SPECIAL) ? Fsb::$session->lang('list_group_special') : Fsb::$session->lang('list_group_normal'),
								));
							}

							Fsb::$tpl->set_blocks('group_cat.group', array(
								'GROUP_NAME' =>		($row['g_type'] == GROUP_SPECIAL && Fsb::$session->lang($row['g_name'])) ? Fsb::$session->lang($row['g_name']) : $row['g_name'],
								'GROUP_STYLE' =>	$row['g_color'],
							));
						break;

						case 'groups' :
						case 'users' :
							if ($row['f_level'] == 0)
							{
								Fsb::$tpl->set_blocks('group_cat', array(
									'NAME' =>	$row['f_name'],
								));
								continue 2;
							}

							Fsb::$tpl->set_blocks('group_cat.group', array(
								'GROUP_NAME' =>		$row['f_name'],
								'GROUP_STYLE' =>	'',
							));
						break;
					}

					foreach ($GLOBALS['_auth_type_format'] AS $cat_auth => $v)
					{
						$list = array();
						$list_value = 'ga_nothing';
						foreach ($v AS $auth)
						{
							$list[$auth] = Fsb::$session->lang('auth_' . $auth);
							if ($auth != 'ga_nothing' && $row[$auth])
							{
								$list_value = $auth;
							}
						}

						Fsb::$tpl->set_blocks('group_cat.group.g_auth', array(
							'LIST' =>		Html::make_list($row['real_this_id'] . '_' . $cat_auth, $list_value, $list),
						));
					}
				}
				Fsb::$db->free($result);
			break;

			case MODE_TYPE_EASY :
				//
				// Affichage de la page pour l'interface tres simplifiee
				//
				Fsb::$tpl->set_switch('auths_easy');
				
				$rows = Fsb::$db->rows($result);
				$count_rows = count($rows);
				
				Fsb::$tpl->set_vars(array(
					'LIST_MODE' =>		$list_mode,
					'COLSPAN' =>		count($rows[0]),
					'HIDDEN' =>			Html::hidden('id', $this->this_id),
					
					'U_ACTION' =>		sid('index.' . PHPEXT . '?p=manage_auths&amp;module=' . $this->module),
				));

				foreach ($GLOBALS['_auth_type'] AS $value)
				{
					if (preg_match('/^ga_/', $value))
					{
						$break = VISITOR;
						for ($i = ($count_rows - 1); $i >= 0; $i--)
						{
							if (!$rows[$i][$value])
							{
								switch ($rows[$i]['real_this_id'])
								{
									case GROUP_SPECIAL_VISITOR :
										$break = USER;
									break;
									
									case GROUP_SPECIAL_USER :
										$break = MODO;
									break;
									
									case GROUP_SPECIAL_MODO :
										$break = MODOSUP;
									break;
									
									case GROUP_SPECIAL_MODOSUP :
										$break = ADMIN;
									break;

									default :
										$break = -1;
									break;
								}
								break;
							}
						}

						$list_auth = Html::make_list($value, $break, array(
							-1 =>		Fsb::$session->lang('adm_auths_no_level'),
							VISITOR =>	Fsb::$session->lang('visitor'),
							USER =>		Fsb::$session->lang('user'),
							MODO =>		Fsb::$session->lang('modo'),
							MODOSUP =>	Fsb::$session->lang('modosup'),
							ADMIN =>	Fsb::$session->lang('admin'),
						));

						Fsb::$tpl->set_blocks('auth', array(
							'L_AUTH' =>		Fsb::$session->lang('auth_' . $value),
									
							'AUTH' =>		$list_auth,
						));
					}
				}
			break;
		}
	}

	/**
	 * Lance la routine de modification de droit
	 */
	public function page_change_auths()
	{
		$ary_group = array(
			VISITOR =>	GROUP_SPECIAL_VISITOR,
			USER =>		GROUP_SPECIAL_USER,
			MODO =>		GROUP_SPECIAL_MODO,
			MODOSUP =>	GROUP_SPECIAL_MODOSUP,
			ADMIN =>	GROUP_SPECIAL_ADMIN,
		);

		// Verification de l'ID
		switch ($this->module)
		{
			case 'forums' :
				$sql = 'SELECT f_name
						FROM ' . SQL_PREFIX . 'forums
						WHERE f_id = ' . $this->id;
				$log_name = Fsb::$db->get($sql, 'f_name');
			break;

			case 'groups' :
				$sql = 'SELECT g_name
						FROM ' . SQL_PREFIX . 'groups
						WHERE g_id = ' . $this->id;
				$log_name = Fsb::$db->get($sql, 'g_name');
			break;

			case 'users' :
				$sql = 'SELECT u.u_nickname
						FROM ' . SQL_PREFIX . 'groups_users gu
						LEFT JOIN ' . SQL_PREFIX . 'users u
							ON u.u_single_group_id = gu.g_id
						WHERE gu.g_id = ' . $this->id;
				$log_name = Fsb::$db->get($sql, 'u_nickname');
			break;
		}

		// Si on est sur un module 'users' et que la variable vaut true, on changera peut etre a la
		// fin son status en tant que "moderateur"
		$user_moderator = false;

		switch ($this->mode_type)
		{
			case MODE_TYPE_ADVANCED :
				//
				// Soumission des droits pour l'interface avancee
				//
				$save_id = 0;
				$insert_array = array();
				$_POST['ga_nothing_0'] = false;
				foreach ($_POST AS $key => $value)
				{
					if (preg_match('/^(ga_[a-zA-Z0-9_]+)_([0-9]+)$/', $key, $match))
					{
						$current_id = intval($match[2]);
						if ($current_id != $save_id)
						{
							if ($save_id > 0)
							{
								switch ($this->module)
								{
									case 'forums' :
										$group_id = $save_id;
										$forum_id = $this->id;
									break;

									case 'groups' :
									case 'users' :
										$group_id = $this->id;
										$forum_id = $save_id;
									break;
								}

								$insert_array['g_id'] = array($group_id, true);
								$insert_array['f_id'] = array($forum_id, true);
								if ($yes_exists)
								{
									Fsb::$db->insert('groups_auth', $insert_array, 'REPLACE');
								}
								else
								{
									$sql = 'DELETE FROM ' . SQL_PREFIX . "groups_auth
												WHERE g_id = $group_id
													AND f_id = $forum_id";
									Fsb::$db->query($sql);
								}
							}
							$yes_exists = false;
							$insert_array = array();
							$save_id = $current_id;
						}

						// On regarde si le groupe / membre peut etre considere comme moderateur
						if (($this->module == 'users' || $this->module == 'groups') && $match[1] == 'ga_moderator' && $value == 1)
						{
							$user_moderator = true;
						}
			
						$insert_array[$match[1]] = $value;
						if ($value)
						{
							$yes_exists = true;
						}
					}
			
					if ($key == 'ga_nothing_0')
					{
						break;
					}
				}
			break;

			case MODE_TYPE_SIMPLE :
				//
				// Soumission des droits pour l'interface normale
				//

				// On recupere les droits sous forme de tableau formate
				$final_auth = array();
				foreach ($_POST AS $key => $value)
				{
					if (preg_match('/^([0-9]+)_([a-zA-Z0-9_]+)$/', $key, $match))
					{
						if (!isset($final_auth[$match[1]]))
						{
							$final_auth[$match[1]] = array();
						}
						$final_auth[$match[1]][$match[2]] = $value;
					}
				}

				// On parcourt le tableau formate pour construire les requetes pour les droits
				foreach ($final_auth AS $current_id => $auth_selected)
				{
					$query_ary = array();
					$delete = true;
					foreach ($GLOBALS['_auth_type_format'] AS $cat_auth => $v)
					{
						$is_selected = 1;
						foreach ($v AS $auth)
						{
							if ($auth != 'ga_nothing')
							{
								// Des qu'on a une valeur a 1 on ne peut que faire une requete REPLACE, sinon si on
								// a uniquement des 0 on lance une requete DELETE a la fin
								if ($is_selected == 1)
								{
									$delete = false;
								}
								$query_ary[$auth] = $is_selected;

								// On regarde si le groupe / membre peut etre considere comme moderateur
								if (($this->module == 'users' || $this->module == 'groups') && $auth == 'ga_moderator' && $is_selected)
								{
									$user_moderator = true;
								}
							}

							if ($auth == $auth_selected[$cat_auth])
							{
								$is_selected = 0;
							}
						}
					}

					// Lancemement de la requete
					if ($delete)
					{
						switch ($this->module)
						{
							case 'forums' :
								$sql = 'DELETE FROM ' . SQL_PREFIX . "groups_auth
											WHERE g_id = $current_id
												AND f_id = $this->id";
							break;

							case 'groups' :
							case 'users' :
								$sql = 'DELETE FROM ' . SQL_PREFIX . "groups_auth
											WHERE f_id = $current_id
												AND g_id = $this->id";
							break;
						}
						Fsb::$db->query($sql);
					}
					else
					{
						switch ($this->module)
						{
							case 'forums' :
								$query_ary['g_id'] = array($current_id, true);
								$query_ary['f_id'] = array($this->id, true);
							break;

							case 'groups' :
							case 'users' :
								$query_ary['g_id'] = array($this->id, true);
								$query_ary['f_id'] = array($current_id, true);
							break;
						}
						Fsb::$db->insert('groups_auth', $query_ary, 'REPLACE');
					}
				}
			break;

			case MODE_TYPE_EASY :
				//
				// Soumission des droits pour l'interface tres simplifiee
				//
				$auth_ary = array();
				foreach ($_POST AS $key => $value)
				{
					if (preg_match('/^ga_([a-zA-Z0-9_]+)$/', $key, $match))
					{
						foreach ($ary_group AS $g_auth => $g_id)
						{
							$auth_ary[$key][$g_auth] = ($value != -1 && $value <= $g_auth) ? true : false;
						}
					}
				}

				foreach ($ary_group AS $g_auth => $g_id)
				{
					$insert_array = array();
					$insert_array['g_id'] = array($g_id, true);
					$insert_array['f_id'] = array($this->id, true);
					$delete_line = true;
					foreach ($auth_ary AS $auth_name => $ary)
					{
						$insert_array[$auth_name] = ($ary[$g_auth]) ? 1 : 0;
						if ($ary[$g_auth])
						{
							$delete_line = false;
						}
					}
					
					if ($delete_line)
					{
						// Theoriquement cette requete ne sera jamais executee, elle est la
						// pour le cas ou la theorie se fait depassee :=)
						$sql = 'DELETE FROM ' . SQL_PREFIX . "groups_auth
								WHERE g_id = $g_id
									AND f_id = $this->id";
						Fsb::$db->query($sql);
					}
					else
					{
						Fsb::$db->insert('groups_auth', $insert_array, 'REPLACE');
					}
				}
			break;

			default :
				trigger_error('Mauvaise valeur dans un switch() :: ' . $this->mode_type, FSB_ERROR);
			break;
		}

		// Mise a jour des groupes de moderation
		Group::update_auths();

		// On met a jour les sessions des membres
		Sync::signal(Sync::SESSION);

		Log::add(Log::ADMIN, 'auth_log_change_' . $this->module, $log_name);
		Display::message('adm_auth_well_add', 'index.' . PHPEXT . '?p=manage_auths&amp;module=' . $this->module . '&amp;this_id=' . (($this->module == 'users') ? $this->u_id : $this->this_id), 'manage_auths');
	}

	/**
	 * Modification du status du membre
	 */
	public function submit_users_status()
	{
		// ID du membre en fonction de son groupe unique
		$sql = 'SELECT u_id
				FROM ' . SQL_PREFIX . 'users
				WHERE u_single_group_id = ' . Fsb::$db->escape($this->id);
		$user_id = Fsb::$db->get($sql, 'u_id');

		if (!Fsb::$session->is_fondator($user_id))
		{
			switch (Http::request('users_status', 'post'))
			{
				case MODOSUP :
					Group::delete_users($user_id, GROUP_SPECIAL_ADMIN, false);
					Group::add_users($user_id, GROUP_SPECIAL_MODOSUP, GROUP_USER);
				break;

				case ADMIN :
					Group::delete_users($user_id, GROUP_SPECIAL_MODOSUP, false);
					Group::add_users($user_id, GROUP_SPECIAL_ADMIN, GROUP_USER);
				break;

				default :
					Group::delete_users($user_id, GROUP_SPECIAL_MODOSUP, false);
					Group::delete_users($user_id, GROUP_SPECIAL_ADMIN);
				break;
			}
			Fsb::$db->destroy_cache('groups_auth_');
		}

		Http::redirect('index.' . PHPEXT . '?p=manage_auths&module=users&this_id=' . $user_id);
	}

	/**
	 * Page affichant les autres droits (non lies aux forums)
	 */
	public function show_others_auths()
	{
		if (Http::request('submit', 'post'))
		{
			$this->submit_others_auths();
			return ;
		}

		Fsb::$tpl->set_switch('auths_others');

		$get_list_level = array(
			VISITOR =>	Fsb::$session->lang('visitor'),
			USER =>		Fsb::$session->lang('user'),
			MODO =>		Fsb::$session->lang('modo'),
			MODOSUP =>	Fsb::$session->lang('modosup'),
			ADMIN =>	Fsb::$session->lang('admin'),
			FONDATOR =>	Fsb::$session->lang('fondator'),
		);

		// On affiche les droits en fonction de la table fsb2_auths
		$sql = 'SELECT auth_name, auth_level, auth_begin
				FROM ' . SQL_PREFIX . 'auths';
		$result = Fsb::$db->query($sql, 'auths_');
		while ($row = Fsb::$db->row($result))
		{
			$list_level = array();
			foreach ($get_list_level AS $level_auth => $level_lang)
			{
				if ($level_auth >= $row['auth_begin'])
				{
					$list_level[$level_auth] = $level_lang;
				}
			}

			Fsb::$tpl->set_blocks('auth', array(
				'L_AUTH' =>			Fsb::$session->lang('adm_auths_o_' . $row['auth_name']),
				'L_AUTH_EXPLAIN' =>	Fsb::$session->lang('adm_auths_e_' . $row['auth_name']),
				'AUTH' =>			Html::make_list($row['auth_name'], $row['auth_level'], $list_level),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Soumission des autres droits
	 */
	public function submit_others_auths()
	{
		// On recupere les droits
		$sql = 'SELECT auth_name, auth_level
				FROM ' . SQL_PREFIX . 'auths';
		$result = Fsb::$db->query($sql, 'auths_');
		while ($row = Fsb::$db->row($result))
		{
			if (!is_null(Http::request($row['auth_name'], 'post')) && Http::request($row['auth_name'], 'post') != $row['auth_level'])
			{
				Fsb::$db->update('auths', array(
					'auth_level' =>		intval(Http::request($row['auth_name'], 'post')),
				), 'WHERE auth_name = \'' . $row['auth_name'] . '\'');
			}
		}
		Fsb::$db->free($result);
		Fsb::$db->destroy_cache('auths_');

		// On met a jour les sessions des membres
		Sync::signal(Sync::SESSION);

		Display::message('adm_auths_submit_others', 'index.' . PHPEXT . '?p=manage_auths&amp;module=others', 'manage_auths');
	}

	/**
	 * Affiche la page par defaut de verification des droits
	 */
	public function show_check_auths()
	{
		// Si un groupe a ete selectionne
		$user_id = null;
		$nickname = Http::request('check_nickname', 'post');
		$group_id = intval(Http::request('g_id', 'post'));

		$title = '';
		if (Http::request('submit_check_groups', 'post'))
		{
			// Nom du groupe
			$sql = 'SELECT g_name
					FROM ' . SQL_PREFIX . 'groups
					WHERE g_id = ' . $group_id;
			if (!$g_name = Fsb::$db->get($sql, 'g_name'))
			{
				Display::message('no_result');
			}

			$title = sprintf(Fsb::$session->lang('adm_auths_check_groups_title'), htmlspecialchars($g_name));
		}
		// Si un membre a ete selectionne
		else if (Http::request('submit_check_user', 'post'))
		{
			// Si le membre existse
			$sql = 'SELECT u_id
					FROM ' . SQL_PREFIX . 'users
					WHERE u_nickname = \'' . Fsb::$db->escape($nickname) . '\'';
			if (!$user_id = Fsb::$db->get($sql, 'u_id'))
			{
				Display::message('user_not_exists');
			}

			$title = sprintf(Fsb::$session->lang('adm_auths_check_user_title'), htmlspecialchars($nickname));
		}

		Fsb::$tpl->set_switch('auths_check');
		Fsb::$tpl->set_vars(array(
			'LIST_GROUPS' =>		Html::list_groups('g_id', GROUP_SPECIAL | GROUP_NORMAL, $group_id),
			'USER_NICKNAME' =>		$nickname,
			'TITLE' =>				$title,
			'TABLE_WIDTH' =>		200 + (count($GLOBALS['_auth_type']) * 75),

			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=manage_auths&amp;module=check'),
		));

		// On lance la verification des droits pour un groupe
		$auth = array();
		if (Http::request('submit_check_groups', 'post'))
		{
			$sql = 'SELECT f.f_id AS real_f_id, ga.*
					FROM ' . SQL_PREFIX . 'forums f
					LEFT JOIN ' . SQL_PREFIX . 'groups_auth ga
						ON f.f_id = ga.f_id
							AND ga.g_id = ' . $group_id;
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				$id = $row['real_f_id'];
				unset($row['real_f_id'], $row['f_id'], $row['g_id']);
				$auth[$id] = $row;
			}
		}
		// On lance la verification des droits d'un membre
		else if (Http::request('submit_check_user', 'post'))
		{
			// Groupe auquel appartient le membre
			$sql = 'SELECT g.g_id, g.g_name, g.g_type, gu.gu_status
					FROM ' . SQL_PREFIX . 'groups_users gu
					LEFT JOIN ' . SQL_PREFIX . 'groups g
						ON gu.g_id = g.g_id
					WHERE gu.u_id = ' . $user_id;
			$result = Fsb::$db->query($sql);
			$groups = array();
			while ($row = Fsb::$db->row($result))
			{
				$groups[] = (int)$row['g_id'];
			}
			Fsb::$db->free($result);

			// Permissions sur les forums
			$sql = 'SELECT f.f_id AS real_f_id, ga.*
					FROM ' . SQL_PREFIX . 'forums f
					LEFT JOIN ' . SQL_PREFIX . 'groups_auth ga
						ON f.f_id = ga.f_id
							' . (($groups) ? 'AND g_id IN (' . implode(', ', $groups) . ')' : '');
			$result = Fsb::$db->query($sql);
			while ($tmp = Fsb::$db->row($result))
			{
				$in_group = (in_array($tmp['g_id'], $groups)) ? true : false;
				foreach ($tmp AS $key => $value)
				{
					if (preg_match('/^ga_/', $key))
					{
						if (!$in_group)
						{
							$value = 0;
						}

						if (!isset($auth[$tmp['real_f_id']][$key]))
						{
							$auth[$tmp['real_f_id']][$key] = 0;
						}
						$auth[$tmp['real_f_id']][$key] = $auth[$tmp['real_f_id']][$key] | $value;
					}
				}
			}
			Fsb::$db->free($result);
		}

		// Si $auth existe on affiche les droits
		if ($auth)
		{
			Fsb::$tpl->set_switch('check_auths');

			// Liste des droits
			foreach ($GLOBALS['_auth_type'] AS $name)
			{
				Fsb::$tpl->set_blocks('auth_name', array(
					'NAME' =>	Fsb::$session->lang('auth_' . $name),
				));
			}

			// Liste des forums
			$sql = 'SELECT f_id, f_level, f_name
					FROM ' . SQL_PREFIX . 'forums
					ORDER BY f_left';
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				Fsb::$tpl->set_blocks('forum', array(
					'NAME' =>	$row['f_name'],
					'IS_CAT' =>	($row['f_level'] == 0) ? true : false,
				));

				foreach ($GLOBALS['_auth_type'] AS $name)
				{
					Fsb::$tpl->set_blocks('forum.auth', array(
						'VALUE' =>		intval($auth[$row['f_id']][$name]),
					));
				}
			}
			Fsb::$db->free($result);
		}
	}
}

/* EOF */
