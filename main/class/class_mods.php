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
 * Informations sur les modules installes sur le forum
 */
class Mods extends Fsb_model
{
	/**
	 * Donnees des modules
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * MOD fourni de base avec le forum
	 */
	const INTERN = 0;

	/**
	 * MOD installe
	 */
	const EXTERN = 1;

	/**
	 * Constructeur, charge les MODS
	 */
	public function __construct()
	{
		$this->load();
	}

	/**
	 * Chargement de la liste des MODS
	 */
	private function load()
	{
		$tpl_exists = (is_object(Fsb::$tpl)) ? true : false;

		$sql = 'SELECT mod_name, mod_status, mod_type
				FROM ' . SQL_PREFIX . 'mods';
		$result = Fsb::$db->query($sql, 'mods_');
		while ($row = Fsb::$db->row($result))
		{
			// Creation du switch de template
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

	/**
	 * Verifie si un MOD existe
	 *
	 * @param string $modname Nom du MOD
	 * @return bool
	 */
	public function exists($modname)
	{
		return ((isset($this->data[$modname])) ? true : false);
	}

	/**
	 * Verifie si un MOD est active
	 *
	 * @param string $modname Nom du MOD
	 * @return bool
	 */
	public function is_active($modname)
	{
		return (($this->exists($modname) && $this->data[$modname]) ? true : false);
	}

	/**
	 * Change le status d'un MOD localement (pas sauve en base)
	 *
	 * @param string $modname Nom du MOD
	 * @param bool $bool true pour active, false pour desactive
	 */
	public function change_status($modname, $state)
	{
		$this->data[$modname] = (bool) $state;
	}
}

/* EOF */