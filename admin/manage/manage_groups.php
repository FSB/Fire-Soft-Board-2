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
 * Gestion, ajout, edition, suppression de groupes
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Identifiant du groupe
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Mode
	 *
	 * @var string
	 */
	public $mode;

	/**
	 * Erreurs
	 *
	 * @var array
	 */
	public $errstr = array();

	/**
	 * Modules
	 *
	 * @var string
	 */
	public $module;

	/**
	 * Donnees du formulaire
	 *
	 * @var array
	 */
	public $data = array();

	/**
	 * Constructeur
	 */
	public function main()
	{
		$this->id =		intval(Http::request('id'));
		$this->mode =	Http::request('mode');

		$call = new Call($this);
		$call->module(array(
			'list' =>		array('list', 'users'),
			'default' =>	'list',
			'url' =>		'index.' . PHPEXT . '?p=manage_groups',
			'lang' =>		'adm_group_module_',
		));

		$call->post(array(
			'submit' =>	':query_add_edit_groups',
		));

		$call->functions(array(
			'module' => array(
				'list' => array(
					'mode' => array(
						'add' =>		'page_add_edit_groups',
						'edit' =>		'page_add_edit_groups',
						'delete' =>		'page_delete_groups',
						'up' =>			'move',
						'down' =>		'move',
						'default' =>	'page_default_groups',
					),
				),
				'users' => array(
					'mode' => array(
						'default' =>	'page_default_groups_users',
					),
				),
			),
		));
	}

	/**
	 * Affiche la page par defaut de gestion des groupes
	 */
	public function page_default_groups()
	{
		Fsb::$tpl->set_switch('groups_management');
		Fsb::$tpl->set_vars(array(
			'U_ADD' =>	sid('index.' . PHPEXT . '?p=manage_groups&amp;module=list&amp;mode=add'),
		));

		$sql = 'SELECT g.g_id, g.g_name, g.g_type, g.g_desc, g.g_color, COUNT(gu.g_id) AS g_count, g_online
				FROM ' . SQL_PREFIX . 'groups g
				LEFT JOIN ' . SQL_PREFIX . 'groups_users gu
					ON g.g_id = gu.g_id
				WHERE g.g_type <> ' . GROUP_SINGLE . '
				GROUP BY g.g_id, g.g_name, g.g_type, g.g_desc, g.g_color
				ORDER BY g.g_online DESC, g.g_order, g.g_name';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$separator = false;
			if (!$separator && $row['g_online'] == 0)
			{
				$separator = true;
			}

			Fsb::$tpl->set_blocks('group', array(
				'NAME' =>			(Fsb::$session->lang($row['g_name'])) ? Fsb::$session->lang($row['g_name']) : $row['g_name'],
				'DESC' =>			($row['g_type'] == GROUP_SPECIAL) ? Fsb::$session->lang('adm_group_is_special') : $row['g_desc'],
				'COUNT' =>			sprintf(String::plural('adm_group_count', $row['g_count']), $row['g_count']),
				'URL' =>            sid(ROOT . 'index.' . PHPEXT . '?p=userlist&amp;g_id=' . $row['g_id']),
				'STYLE' =>			$row['g_color'],
				'SEPARATOR' =>		$separator,

				'U_EDIT' =>			sid('index.' . PHPEXT . '?p=manage_groups&amp;mode=edit&amp;id=' . $row['g_id']),
				'U_DELETE' =>		($row['g_type'] != GROUP_SPECIAL) ? sid('index.' . PHPEXT . '?p=manage_groups&amp;mode=delete&amp;id=' . $row['g_id']) : null,
				'U_UP_GROUP' =>		sid('index.' . PHPEXT . '?p=manage_groups&amp;mode=up&amp;id=' . $row['g_id']),
				'U_DOWN_GROUP' =>	sid('index.' . PHPEXT . '?p=manage_groups&amp;mode=down&amp;id=' . $row['g_id']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Affiche la page permettant d'ajouter / editer des groupes
	 */
	public function page_add_edit_groups()
	{
		$lg_add_edit = ($this->mode == 'edit') ? Fsb::$session->lang('adm_group_edit') : Fsb::$session->lang('adm_group_add');
		if ($this->mode == 'edit' && !$this->errstr)
		{
			$sql = 'SELECT g.*, u.u_nickname
					FROM ' . SQL_PREFIX . 'groups g
					LEFT JOIN ' . SQL_PREFIX . 'groups_users gu
						ON g.g_id = gu.g_id
					LEFT JOIN ' . SQL_PREFIX . 'users u
						ON gu.u_id = u.u_id
							AND gu.gu_status = ' . GROUP_MODO . '
					WHERE g.g_id = ' . $this->id . '
						AND g.g_type <> ' . GROUP_SINGLE;
			$result = Fsb::$db->query($sql);
			if (!$data = Fsb::$db->row($result))
			{
				Display::message('no_result');
			}
			$this->data = $data;

			// On recupere la liste des moderateurs
			$modo = array();
			do
			{
				if ($data['u_nickname'])
				{
					$modo[] = $data['u_nickname'];
				}
			}
			while ($data = Fsb::$db->row($result));
			Fsb::$db->free($result);

			$this->data['g_modo'] = implode("\n", $modo);
		}
		else if (!$this->errstr)
		{
			$this->data['g_name'] = '';
			$this->data['g_desc'] = '';
			$this->data['g_modo'] = '';
			$this->data['g_color'] = '';
			$this->data['g_hidden'] = false;
			$this->data['g_open'] = false;
			$this->data['g_online'] = true;
			$this->data['g_rank'] = 0;
			$this->data['g_type'] = GROUP_NORMAL;
		}

		// Style
		$style_type = $style_content = '';
		if ($getstyle = Html::get_style($this->data['g_color']))
		{
			list($style_type, $style_content) = $getstyle;
		}

		// Type du groupe ?
		if ($this->data['g_type'] != GROUP_SPECIAL)
		{
			Fsb::$tpl->set_switch('is_not_special');
		}

		// Liste des rangs
		$sql = 'SELECT rank_id, rank_name
				FROM ' . SQL_PREFIX . 'ranks
				WHERE rank_special = 1
				ORDER BY rank_name';
		$result = Fsb::$db->query($sql, 'ranks_');
		$list_rank = array(0 => Fsb::$session->lang('none'));
		while ($row = Fsb::$db->row($result))
		{
			$list_rank[$row['rank_id']] = '- ' . htmlspecialchars($row['rank_name']);
		}
		Fsb::$db->free($result);

		Fsb::$tpl->set_switch('groups_add');
		Fsb::$tpl->set_vars(array(
			'L_ADD_EDIT' =>			$lg_add_edit,

			'NAME' =>				$this->data['g_name'],
			'DESC' =>				$this->data['g_desc'],
			'MODO' =>				$this->data['g_modo'],
			'STYLE' =>				htmlspecialchars($style_content),
			'STYLE_TYPE_NONE' =>	(!$getstyle) ? 'checked="checked"' : '',
			'STYLE_TYPE_COLOR' =>	($style_type == 'style') ? 'checked="checked"' : '',
			'STYLE_TYPE_CLASS' =>	($style_type == 'class') ? 'checked="checked"' : '',
			'GROUP_VISIBLE' =>		($this->data['g_hidden'] != GROUP_HIDDEN) ? true : false,
			'GROUP_OPEN' =>			$this->data['g_open'],
			'GROUP_ONLINE' =>		$this->data['g_online'],
			'ERRSTR' =>				Html::make_errstr($this->errstr),
			'LIST_RANKS' =>			Html::make_list('g_rank', $this->data['g_rank'], $list_rank),

			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=manage_groups&amp;mode=' . $this->mode . '&amp;id=' . $this->id)
		));
	}

	/**
	 * Valide le formulaire d'ajout / edition de groupes
	 */
	public function query_add_edit_groups()
	{
		$this->data['g_name'] =			Http::request('g_name', 'post');
		$this->data['g_desc'] =			Http::request('g_desc', 'post');
		$this->data['g_hidden'] =		intval(Http::request('g_hidden', 'post'));
		$this->data['g_open'] =			intval(Http::request('g_open', 'post'));
		$this->data['g_online'] =		intval(Http::request('g_online', 'post'));
		$this->data['g_rank'] =			intval(Http::request('g_rank', 'post'));
		$this->data['g_modo'] =			trim(Http::request('g_modo', 'post'));
		$this->data['g_color'] =		Html::set_style(Http::request('g_style_type', 'post'), trim(Http::request('g_style', 'post')), 'class="user"');

        $sql = 'SELECT MAX(g_order) AS max_order FROM ' . SQL_PREFIX . 'groups';
		$this->data['g_order'] = Fsb::$db->get($sql, 'max_order') + 1;
        
		if (empty($this->data['g_name']))
		{
			$this->errstr[] = Fsb::$session->lang('fields_empty');
		}

		// Verification du type de groupe (pour eviter les failles de securite sur l'edition de groupes speciaux)
		if ($this->mode == 'edit')
		{
			$sql = 'SELECT g_type, g_color
					FROM ' . SQL_PREFIX . 'groups
					WHERE g_id = ' . $this->id . '
						AND g_type <> ' . GROUP_SINGLE;
			if (!$row = Fsb::$db->request($sql))
			{
				Display::message('no_result');
			}
			$this->data['g_type'] = $row['g_type'];
			$old_color = $row['g_color'];
		}
		else
		{
			$this->data['g_type'] = GROUP_NORMAL;
		}

		$modo_idx = array();
		if ($this->data['g_type'] != GROUP_SPECIAL)
		{
			// Verification des logins de moderateurs
			// Suppression de doubles logins
			$ary_modo = array_flip(array_flip(explode("\n", $this->data['g_modo'])));
			$ary_modo = array_map('trim', $ary_modo);
			$ary_modo = array_map('strtolower', $ary_modo);
			$search_modo = '';
			foreach ($ary_modo AS $login)
			{
				if ($login)
				{
					$search_modo .= '\'' . Fsb::$db->escape($login) . '\', ';
				}
			}
			$search_modo = substr($search_modo, 0, -2);

			// Verification des logins
			if ($search_modo)
			{
				$sql = 'SELECT u_id, u_nickname
						FROM ' . SQL_PREFIX . 'users
						WHERE LOWER(u_nickname) IN (' . $search_modo . ')';
				$result = Fsb::$db->query($sql);
				$flip_ary_modo = array_flip($ary_modo);
				while ($row = Fsb::$db->row($result))
				{
					unset($flip_ary_modo[strtolower($row['u_nickname'])]);
					$modo_idx[] = $row['u_id'];
				}
				Fsb::$db->free($result);

				foreach (array_flip($flip_ary_modo) AS $bad_modo)
				{
					$this->errstr[] = sprintf(Fsb::$session->lang('adm_groups_bad_nickname'), htmlspecialchars($bad_modo));
				}
			}
		}

		if ($this->errstr)
		{
			return ;
		}

		// Plus besoin de la clef g_modo
		unset($this->data['g_modo']);

		// Ajout / Edition du groupe dans la base de donnee
		if ($this->mode == 'edit')
		{
			Group::edit($this->id, $this->data, $modo_idx);
			Log::add(Log::ADMIN, 'group_log_edit', $this->data['g_name']);
		}
		else
		{
			$this->id = Group::add($this->data, $modo_idx);
			Log::add(Log::ADMIN, 'group_log_add', $this->data['g_name']);
		}

		Display::message('adm_group_submit_' . $this->mode, 'index.' . PHPEXT . '?p=manage_groups', 'manage_groups');
	}

	/**
	 * Page de suppression d'un groupe
	 */
	public function page_delete_groups()
	{
		if (check_confirm())
		{
			$sql = 'SELECT g_name, g_type
					FROM ' . SQL_PREFIX . 'groups
					WHERE g_id = ' . $this->id;
			$result = Fsb::$db->query($sql);
			$data = Fsb::$db->row($result);
			Fsb::$db->free($result);

			if ($data['g_type'] == GROUP_NORMAL)
			{
				Group::delete($this->id);

				Log::add(Log::ADMIN, 'group_log_delete', $data['g_name']);
			}

			Display::message('adm_group_delete_well', 'index.' . PHPEXT . '?p=manage_groups', 'manage_groups');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=manage_groups');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('adm_group_delete_confirm'), 'index.' . PHPEXT . '?p=manage_groups', array('module' => $this->module, 'mode' => $this->mode, 'id' => $this->id));
		}
	}

	/**
	 * Affiche la page listant les groupes du forum avec leur caracteristiques
	 */
	public function page_default_groups_users()
	{
		// Recherche d'un membre ?
		$errstr = '';
		$search_data = null;
		if (($nickname = Http::request('search_user', 'post')) && Http::request('submit_search_user', 'post'))
		{
			$sql = 'SELECT u_id
					FROM ' . SQL_PREFIX . 'users
					WHERE u_nickname = \'' . Fsb::$db->escape($nickname) . '\'';
			$result = Fsb::$db->query($sql);
			$search_data = Fsb::$db->row($result);
			Fsb::$db->free($result);
			if (!$search_data)
			{
				$errstr = sprintf(Fsb::$session->lang('adm_group_search_not_found'), htmlspecialchars($nickname));
			}
		}

		Fsb::$tpl->set_switch('groups_users');

		// Liste des groupes
		$sql = 'SELECT g.g_id, g.g_name, g.g_hidden, g.g_open, g.g_color, COUNT(gu.g_id) AS g_count' . (($search_data) ? ', gu2.u_id' : '') . '
				FROM ' . SQL_PREFIX . 'groups g
				LEFT JOIN ' . SQL_PREFIX . 'groups_users gu
					ON g.g_id = gu.g_id
				' . (($search_data) ? 'LEFT JOIN ' . SQL_PREFIX . 'groups_users gu2 ON g.g_id = gu2.g_id AND gu2.u_id = ' . $search_data['u_id'] : '') . '
				WHERE g.g_type <> ' . GROUP_SPECIAL . '
					AND g.g_type <> ' . GROUP_SINGLE . '
				GROUP BY g.g_id, g.g_name, g.g_type, g.g_desc, g.g_color';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			if (!$search_data || $search_data['u_id'] == $row['u_id'])
			{
				Fsb::$tpl->set_blocks('group', array(
					'NAME' =>			$row['g_name'],
					'STYLE' =>			$row['g_color'],
					'OPEN' =>			$row['g_open'],
					'VISIBLE' =>		!$row['g_hidden'],
					'COUNT' =>			sprintf(String::plural('adm_group_count', $row['g_count']), $row['g_count']),

					'U_MANAGE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=userlist&amp;g_id=' . $row['g_id']),
				));
			}
		}
		Fsb::$db->free($result);

		Fsb::$tpl->set_vars(array(
			'NICKNAME' =>		htmlspecialchars($nickname),
			'ERRSTR' =>			$errstr,

			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=manage_groups&amp;module=users'),
		));
	}

	public function move()
	{
		$sql = 'SELECT g_id, g_order
			FROM ' . SQL_PREFIX . 'groups
			WHERE g_id = ' . $this->id;
		$group = Fsb::$db->request($sql);

		if ($group)
		{
			$offset = 1;
			if ($this->mode === 'up')
			{
				$offset = -1;
			}

			$sql = 'SELECT g_id, g_order
				FROM ' . SQL_PREFIX . 'groups
				WHERE g_order = ' . (intval($group['g_order']) + $offset);
			$result = Fsb::$db->query($sql);
			$swap = Fsb::$db->row($result);
			Fsb::$db->free($result);

			if ($swap)
			{
				Fsb::$db->update('groups', array(
					'g_order' => $swap['g_order'],
				), 'WHERE g_id = ' . $group['g_id']);

				Fsb::$db->update('groups', array(
					'g_order' => $group['g_order'],
				), 'WHERE g_id = ' . $swap['g_id']);

				Fsb::$db->destroy_cache('groups_');
			}
		}

		Http::redirect('index.' . PHPEXT . '?p=manage_groups');
	}
}

/* EOF */
