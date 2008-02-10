<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_adm_menu.php
** | Begin :	02/04/2005
** | Last :		12/01/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion du menu administratif avec une mise en cache des liens afin
** de pouvoir modifier leur ordre / permission.
*/
class Adm_menu extends Fsb_model
{
	// Données du fichier cache `adm_menu`
	public $data = array();

	// Dossiers a ne pas prendre en compte (adm_tpl/ par exemple)
	public $exept = array();

	// Information sur la page actuelle
	public $include = NULL;

	public function __construct($page)
	{
		$sql = 'SELECT page, auth, cat, cat_order, page_order, page_icon
					FROM ' . SQL_PREFIX . 'menu_admin
					ORDER BY cat_order, page_order';
		$result = Fsb::$db->query($sql, 'menu_admin_');
		while ($row = Fsb::$db->row($result))
		{
			$this->data[] = $row;
			if ($page == $row['page'])
			{
				$this->include = $row;
			}
		}
		Fsb::$db->free($result);
	}

	/*
	** Renvoie le code HTML du menu administratif
	*/
	public function get_adm_menu($current_page)
	{
		$cat_menu = array();
		foreach ($this->data AS $ary)
		{
			if (Fsb::$session->auth() >= $ary['auth'])
			{
				$cat_menu[$ary['cat']][] = $ary;
			}
		}

		foreach ($cat_menu AS $cat => $ary)
		{
			Fsb::$tpl->set_blocks('cat_menu', array(
				'CAT' =>	((Fsb::$session->lang('cat_menu_' . $cat)) ? Fsb::$session->lang('cat_menu_' . $cat) : $cat)
			));

			foreach ($ary AS $subary)
			{
				if ($cat == 'mods' && $subary['page'] != 'mods_manager' && !Fsb::$mods->is_active(substr($subary['page'], 5)))
				{
					continue;
				}

				$lg_page = (Fsb::$session->lang('menu_' . $subary['page'])) ? Fsb::$session->lang('menu_' . $subary['page']) : $subary['page'];
				Fsb::$tpl->set_blocks('cat_menu.menu', array(
					'U_MENU' =>		sid('index.' . PHPEXT . '?p=' . $subary['page']),
					'MENU' =>		$lg_page,
					'IS_CURRENT' =>	(($subary['page'] == $current_page) ? TRUE : FALSE),
					'ICON' =>		($subary['page_icon']) ? $subary['page_icon'] : 'menu.png',
				));
			}
		}
	}

	/*
	** Resource la table du menu administratif
	*/
	public function refresh_menu()
	{
		// Suppression de tous les liens de la base de donnée
		Fsb::$db->query_truncate('menu_admin');

		if (!$fd = @opendir(ROOT . 'admin/'))
		{
			trigger_error('Ouverture du dossier ~/admin/ impossible', FSB_ERROR);
		}

		$ary = array();
		$cat_order = 0;
		while ($dir = readdir($fd))
		{
			if ($dir[0] != '.' && !in_array($dir, $this->exept) && is_dir(ROOT . 'admin/' . $dir))
			{
				$cat_order++;
				$page_order = 0;
				$subfd = opendir(ROOT . 'admin/' . $dir);
				while ($subdir = readdir($subfd))
				{
					if ($subdir[0] != '.' && $subdir != 'index.html')
					{
						$ary[] = array(
							'page' =>		substr($subdir, 0, -(1 + strlen(PHPEXT))),
							'auth' =>		ADMIN,
							'cat' =>		$dir,
							'cat_order' =>	$cat_order,
							'page_order' =>	++$page_order,
						);
					}
				}
				closedir($subfd);
			}
		}
		closedir($fd);
		$this->data = $this->keep_order($ary);
	}

	/*
	** Permet de gérer les ajouts / suppressions du au rafrachissement dans le menu, en gardant
	** l'ordre du menu dans l'administration
	** -----
	** $ary ::		Tableau contenant les données du menu
	*/
	private function keep_order(&$ary)
	{
		// Supression des pages qui ont été supprimées
		$cats = array();
		$max_order = 0;
		foreach ($this->data AS $value)
		{
			if (array_select($ary, 'page', $value['page']))
			{
				$cats[$value['cat']] = $value['cat_order'];
				$max_order = max($max_order, $value['cat_order']);
				foreach ($value AS $k => $v)
				{
					if (is_int($k))
					{
						unset($value[$k]);
					}
				}
				Fsb::$db->insert('menu_admin', $value, 'INSERT', TRUE);
			}
		}

		// Ajout des nouvelles pages dans la base de donnée
		foreach ($ary AS $value)
		{
			if (!array_select($this->data, 'page', $value['page']))
			{
				$value['page_icon'] = '';
				if (isset($cats[$value['cat']]))
				{
					$value['cat_order'] = $cats[$value['cat']];
				}
				else
				{
					$max_order++;
					$value['cat_order'] = $max_order;
					$cats[$value['cat']] = $value['cat_order'];
				}
				Fsb::$db->insert('menu_admin', $value, 'INSERT', TRUE);
			}
		}
		Fsb::$db->query_multi_insert();
		Fsb::$db->destroy_cache('menu_admin_');
	}
	
	/*
	** Déplace une catégorie du menu vers le haut ou vers le bas
	** -----
	** $move ::		-1 pour déplacer vers le haut, 1 pour déplacer vers le bas
	** $name ::		Nom de la catégorie
	*/
	public function move_cat($move, $name)
	{
		$sql = 'SELECT cat_order
				FROM ' . SQL_PREFIX . 'menu_admin
				WHERE cat = \'' . Fsb::$db->escape($name) . '\'
				LIMIT 1';
		$cat_order = Fsb::$db->get($sql, 'cat_order');

		$sql = 'SELECT cat
				FROM ' . SQL_PREFIX . 'menu_admin
				WHERE cat_order = ' . ($cat_order + $move) . '
				LIMIT 1';
		$cat = Fsb::$db->get($sql, 'cat');
		if ($cat)
		{
			Fsb::$db->update('menu_admin', array(
				'cat_order' =>	array("(cat_order - $move)", 'is_field' => TRUE),
			), 'WHERE cat = \'' . Fsb::$db->escape($cat) . '\'');

			Fsb::$db->update('menu_admin', array(
				'cat_order' =>	array("(cat_order + $move)", 'is_field' => TRUE),
			), 'WHERE cat = \'' . Fsb::$db->escape($name) . '\'');
			Fsb::$db->destroy_cache('menu_admin_');
			return (TRUE);
		}
		return (FALSE);
	}

	/*
	** Déplace un lien du menu vers le haut ou vers le bas
	** -----
	** $move ::		-1 pour déplacer vers le haut, 1 pour déplacer vers le bas
	** $name ::		Nom du lien
	*/
	public function move_link($move, $name)
	{
		$sql = 'SELECT cat, page_order
				FROM ' . SQL_PREFIX . 'menu_admin
				WHERE page = \'' . Fsb::$db->escape($name) . '\'
				LIMIT 1';
		$result = Fsb::$db->query($sql);
		$current = Fsb::$db->row($result);
		Fsb::$db->free($result);

		$sql = 'SELECT page
				FROM ' . SQL_PREFIX . 'menu_admin
				WHERE page_order = ' . ($current['page_order'] + $move) . '
					AND cat = \'' . Fsb::$db->escape($current['cat']) . '\'
				LIMIT 1';
		$page = Fsb::$db->get($sql, 'page');
		if ($page)
		{
			Fsb::$db->update('menu_admin', array(
				'page_order' =>	array("(page_order - $move)", 'is_field' => TRUE),
			), 'WHERE page = \'' . Fsb::$db->escape($page) . '\'');

			Fsb::$db->update('menu_admin', array(
				'page_order' =>	array("(page_order + $move)", 'is_field' => TRUE),
			), 'WHERE page = \'' . Fsb::$db->escape($name) . '\'');
			Fsb::$db->destroy_cache('menu_admin_');
			return (TRUE);
		}
		return (FALSE);
	}
}

/* EOF */