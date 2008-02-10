<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_mods.php
** | Begin :	21/06/2007
** | Last :		13/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion des modules installés sur le forum
*/
class Mods extends Fsb_model
{
	private $data = array();

	// MOD fourni de base avec le forum
	const INTERN = 0;

	// MOD installé
	const EXTERN = 1;

	/*
	** Constructeur
	*/
	public function __construct()
	{
		$this->load();
	}

	/*
	** Chargement de la liste des MODS
	*/
	private function load()
	{
		$tpl_exists = (is_object(Fsb::$tpl)) ? TRUE : FALSE;

		$sql = 'SELECT mod_name, mod_status, mod_type
				FROM ' . SQL_PREFIX . 'mods';
		$result = Fsb::$db->query($sql, 'mods_');
		while ($row = Fsb::$db->row($result))
		{
			// Création du switch de template
			if ($row['mod_status'] && $tpl_exists)
			{
				Fsb::$tpl->set_switch('ac_mods_' . $row['mod_name']);
			}

			// Chargement de la langue
			if ($row['mod_type'] == self::EXTERN)
			{
				Fsb::$session->load_lang('mods/lg_' . $row['mod_name']);
			}
			$this->data[$row['mod_name']] = $row['mod_status'];
		}
		Fsb::$db->free($result);
	}

	/*
	** Vérifie si un MOD existe
	** -----
	** $modname ::	Nom du MOD
	*/
	public function exists($modname)
	{
		return ((isset($this->data[$modname])) ? TRUE : FALSE);
	}

	/*
	** Vérifie si un MOD est activé
	** -----
	** $modname ::	Nom du MOD
	*/
	public function is_active($modname)
	{
		return (($this->exists($modname) && $this->data[$modname]) ? TRUE : FALSE);
	}

	/*
	** Change le status d'un MOD
	** -----
	** $modname ::	Nom du MOD
	** $bool ::		TRUE pour activé, FALSE pour désactivé
	*/
	public function change_status($modname, $bool)
	{
		$this->data[$modname] = (bool) $bool;
	}
}

/* EOF */