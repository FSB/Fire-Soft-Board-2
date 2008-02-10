<?php
/*
** +---------------------------------------------------+
** | Name :			~/main/portail/portail.php
** | Begin :		20/06/2007
** | Last :			22/10/2007
** | User :			Genova
** | Project :		Fire-Soft-Board 2 - Copyright FSB group
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Fonction appelée dans les templates pour inclure dynamiquement des fichiers templates
*/
function include_portail_module($filename)
{
	Fsb::$tpl->include_tpl($filename);
}

/*
** Module de portail permettant d'afficher quelques suggestions pour l'utilisateurs
*/
class Portail extends Fsb_model
{
	public $portail_config = array();

	/*
	** Constructeur
	*/
	public function __construct()
	{
		Fsb::$session->load_lang('lg_forum_portail');

		// Charge la configuration du portail
		$sql = 'SELECT portail_name, portail_value
				FROM ' . SQL_PREFIX . 'portail_config';
		$result = Fsb::$db->query($sql, 'portail_config_');
		$this->portail_config = array();
		while ($row = Fsb::$db->row($result))
		{
			$this->portail_config[$row['portail_name']] = $row['portail_value'];
		}
		Fsb::$db->free($result);
	}

	/*
	** Affiche un module
	** -----
	** $name ::		Nom du module
	*/
	public function output_module($name)
	{
		if (!file_exists(ROOT . 'main/portail/portail_' . $name . '.' . PHPEXT))
		{
			return (FALSE);
		}

		include(ROOT . 'main/portail/portail_' . $name . '.' . PHPEXT);
		$class_name = 'Page_portail_' . $name;
		$tmp = new $class_name();
		$tmp->_set('portail_config', $this->portail_config);
		$tmp->main();
		return (TRUE);
	}

	/*
	** Affiche tous les modules
	*/
	public function output_all()
	{
		Fsb::$tpl->set_file('forum/forum_portail.html');

		// On affiche les modules
		$sql = 'SELECT pm_name, pm_position
				FROM ' . SQL_PREFIX . 'portail_module
				WHERE pm_activ = 1
				ORDER BY pm_order';
		$result = Fsb::$db->query($sql, 'portail_module_');
		while ($row = Fsb::$db->row($result))
		{
			if ($this->output_module($row['pm_name']))
			{
				// On affiche le template du block dynamiquement
				Fsb::$tpl->set_blocks('portail_' . $row['pm_position'], array(
					'FILENAME' =>	'portail_' . $row['pm_name'] . '.html',
				));
			}
		}
		Fsb::$db->free($result);
	}
}

/* EOF */