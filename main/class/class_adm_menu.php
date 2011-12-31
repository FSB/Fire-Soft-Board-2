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
 * Gestion du menu administratif avec une mise en cache des liens afin de pouvoir modifier leur ordre / permission.
 * @todo revoir cette classe ...
 */
class Adm_menu extends Fsb_model
{
	/**
	 * Donnees du menu
	 *
	 * @var array
	 */
	public $data = array();

	// 
	/**
	 * Dossiers a ne pas prendre en compte dans l'administration (adm_tpl/ par exemple)
	 *
	 * @var array
	 */
	public $except = array();

	/**
	 * Informations sur la page actuelle
	 *
	 * @var array
	 */
	public $include = null;

	/**
	 * Constructeur, charge le menu en memoire
	 *
	 * @param string $page Nom de la page actuelle
	 */
	public function __construct($page)
	{
		$sql = 'SELECT page, auth, cat, cat_order, page_order, page_icon, module_name
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

	/**
	 * Cree le template du menu administratif
	 *
	 * @param string $current_page Nom de la page actuelle
	 */
	public function get_adm_menu($current_page)
	{
		$cat_menu = array();
		foreach ($this->data AS $ary)
		{
			if (Fsb::$session->auth() >= $ary['auth'])
			{
				if ($ary['module_name'] && !Fsb::$mods->is_active($ary['module_name']))
				{
					continue;
				}

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
					'IS_CURRENT' =>	(($subary['page'] == $current_page) ? true : false),
					'ICON' =>		($subary['page_icon'] && file_exists(ROOT . 'admin/adm_tpl/img/icon/' . $subary['page_icon'])) ? ROOT . 'admin/adm_tpl/img/icon/' . $subary['page_icon'] : ROOT . 'admin/adm_tpl/img/icon/menu.png',
				));
			}
		}
	}

	/**
	 * Rafraichi le menu administratif en fonction des ajouts / suppression de page
	 */
	public function refresh_menu()
	{
		// Suppression de tous les liens de la base de donnee
		Fsb::$db->query_truncate('menu_admin');

		if (!$fd = @opendir(ROOT . 'admin/'))
		{
			trigger_error('Ouverture du dossier ~/admin/ impossible', FSB_ERROR);
		}

		$ary = array();
		$cat_order = 0;
		while ($dir = readdir($fd))
		{
			if ($dir[0] != '.' && !in_array($dir, $this->except) && is_dir(ROOT . 'admin/' . $dir))
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

	/**
	 * Rafraichi le menu administratif en gardant l'ordre des pages actuelles
	 *
	 * @param array $ary Contient les donnees du menu
	 */
	private function keep_order(&$ary)
	{
		// Supression des pages qui ont ete supprimees
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
				Fsb::$db->insert('menu_admin', $value, 'INSERT', true);
			}
		}

		// Ajout des nouvelles pages dans la base de donnee
		foreach ($ary AS $value)
		{
			if (!array_select($this->data, 'page', $value['page']))
			{
				$value['page_icon'] = $value['page'] . '.png';
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
				Fsb::$db->insert('menu_admin', $value, 'INSERT', true);
			}
		}
		Fsb::$db->query_multi_insert();
		Fsb::$db->destroy_cache('menu_admin_');
	}
	
	/**
	 * Deplace une categorie du menu
	 *
	 * @param int $move -1 pour bouger vers le haut ou 1 vers le bas
	 * @param string $name Nom de la categorie
	 * @return bool true si la categorie a pu bouger
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
				'cat_order' =>	array("(cat_order - $move)", 'is_field' => true),
			), 'WHERE cat = \'' . Fsb::$db->escape($cat) . '\'');

			Fsb::$db->update('menu_admin', array(
				'cat_order' =>	array("(cat_order + $move)", 'is_field' => true),
			), 'WHERE cat = \'' . Fsb::$db->escape($name) . '\'');
			Fsb::$db->destroy_cache('menu_admin_');
			return (true);
		}
		return (false);
	}

	/**
	 * Deplace un lien du menu du menu
	 *
	 * @param int $move -1 pour bouger vers le haut ou 1 vers le bas
	 * @param string $name Nom du lien
	 * @return bool true si le lien a pu bouger
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
				'page_order' =>	array("(page_order - $move)", 'is_field' => true),
			), 'WHERE page = \'' . Fsb::$db->escape($page) . '\'');

			Fsb::$db->update('menu_admin', array(
				'page_order' =>	array("(page_order + $move)", 'is_field' => true),
			), 'WHERE page = \'' . Fsb::$db->escape($name) . '\'');
			Fsb::$db->destroy_cache('menu_admin_');
			return (true);
		}
		return (false);
	}
}

/* EOF */
