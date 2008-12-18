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
 * Fonction appelee dans les templates pour inclure dynamiquement des fichiers templates
 *
 * @param string $filename Nom du fichier tpl à inclure
 */
function include_portail_module($filename)
{
	Fsb::$tpl->include_tpl($filename);
}

/**
 * Module de portail permettant d'afficher quelques suggestions pour l'utilisateurs
 */
class Portail extends Fsb_model
{
	/**
	 * Configuration du portail
	 *
	 * @var array
	 */
	public $portail_config = array();

	/**
	 * Constructeur
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

	/**
	 * Affiche un module
	 *
	 * @param string $name Nom du module
	 * @return bool Retourne true si le module a bien ete afficher, false sinon
	 */
	public function output_module($name)
	{
		if (!file_exists(ROOT . 'main/portail/portail_' . $name . '.' . PHPEXT))
		{
			return (false);
		}

		include(ROOT . 'main/portail/portail_' . $name . '.' . PHPEXT);
		$class_name = 'Page_portail_' . $name;
		$tmp = new $class_name();
		$tmp->_set('portail_config', $this->portail_config);
		$tmp->main();
		return (true);
	}

	/**
	 * Affiche tous les modules
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