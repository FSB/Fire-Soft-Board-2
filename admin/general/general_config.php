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
 * Fichier permetant de gerer la configuration generale du forum.
 * Chaque ligne du tableau de configuration est geree dans la table
 * fsb2_config_handler
 *
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Arguments de la page
	 *
	 * @var string
	 */
	public $module;

	/**
	 * Tableau de configuration
	 *
	 * @var array
	 */
	public $config_data = array();

	/**
	 * Objet de configuration dynamique
	 *
	 * @var Config_edit
	 */
	public $config;

	/**
	 * Constructeur
	 */
	public function main()
	{
		// On inclu la classe de configuration dynamique
		$this->config = new Config_edit(Fsb::$cfg->data, 'adm_config_');

		$this->module = Http::request('module');
		if (!$this->module)
		{
			$this->module = 'general';
		}

		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'config_handler
				ORDER BY cfg_cat, cfg_subcat';
		$result = Fsb::$db->query($sql, 'config_handler_');
		$this->config_data = array();
		$cat_exists = false;
		$module_list = array();
		while ($row = Fsb::$db->row($result))
		{
			if (!in_array($row['cfg_cat'], $module_list))
			{
				$module_list[] = $row['cfg_cat'];
			}

			if ($row['cfg_cat'] == $this->module)
			{
				$cat_exists = true;
			}

			$subcat = ($row['cfg_subcat']) ? $row['cfg_subcat'] : $row['cfg_cat'];
			$this->config_data[$row['cfg_cat']][$subcat][] = $row;
		}
		Fsb::$db->free($result);

		if (!$cat_exists)
		{
			$this->module = 'general';
		}

		// Module de la page
		Display::header_module($module_list, $this->module, 'index.' . PHPEXT . '?p=general_config', 'adm_config_');

		if (Http::request('submit', 'post'))
		{
			$this->page_submit_config();
		}
		else
		{
			$this->page_default_config();
		}
	}

	/**
	 * Affiche la page par defaut de la gestion des autorisations sur le FORUM
	 */
	public function page_default_config()
	{
		Fsb::$tpl->set_vars(array(
			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=general_config&amp;module=' . $this->module),
		));

		foreach ($this->config_data[$this->module] AS $key_cat => $value_cat)
		{
			// Ajout d'une categorie
			$this->config->set_cat(Fsb::$session->lang('adm_config_' . $key_cat), (Fsb::$session->lang('adm_config_' . $key_cat . '_explain')) ? Fsb::$session->lang('adm_config_' . $key_cat . '_explain') : null);

			if (is_array($value_cat))
			{
				foreach ($value_cat AS $line)
				{
					if (is_array($line))
					{
						$this->config->set_line($line['cfg_name'], $line['cfg_function'], $line['cfg_args']);
					}
				}
			}
		}
	}

	/**
	 * Traitement des information du formulaire de configuration soumis
	 */
	public function page_submit_config()
	{
		$data = array();
		foreach ($_POST AS $key => $value)
		{
			if (Fsb::$cfg->exists($key) && $value != Fsb::$cfg->get($key))
			{
				$data[$key] = $value;
				if (method_exists($this, 'get_' . $key))
				{
					$data[$key] = $this->{'get_' . $key}();
				}
			}
		}

		// Validation des informations
		$data = $this->config->validate($data, 'config_handler', 'cfg_name', 'cfg_type');

		// Mise a jour dans la base de donnee
		foreach ($data AS $k => $v)
		{
			Fsb::$cfg->update($k, $v, false);
		}
		Fsb::$cfg->destroy_cache();

		Log::add(Log::ADMIN, 'config_log_change');
		Display::message('adm_config_well_submit', 'index.' . PHPEXT . '?p=general_config&amp;module=' . $this->module, 'general_config');
	}

	/**
	 * Recupere le quota d'upload par membre
	 *
	 * @return int quota d'upload par membre
	 */
	public function get_upload_quota()
	{
		return (Http::request('upload_quota', 'post') * Http::request('upload_quota_list', 'post'));
	}

	/**
	 * Recupere le quota d'upload par fichier
	 *
	 * @return int quota d'upload par fichier
	 */
	public function get_upload_max_filesize()
	{
		return (Http::request('upload_max_filesize', 'post') * Http::request('upload_max_filesize_list', 'post'));
	}

	/**
	 * Recupere la taille maximale de l'avatar
	 *
	 * @return int Taille maximal de l'avatar
	 */
	public function get_avatar_weight()
	{
		return (Http::request('avatar_weight', 'post') * Http::request('avatar_weight_list', 'post'));
	}
}

/**
 * Affiche un champ texte et une liste de taille pour entrer la taille d'un fichier
 * 
 * @param string $name Nom des champs
 * @param int $value Valeur par defaut
 * @return string
 */
function input_filesize($name, $value)
{
	if ($value >= 1048576)
	{
		$v1 = substr($value / 1048576, 0, 5);
		$v2 = 1048576;
	}
	else if ($value >= 1024)
	{
		$v1 = substr($value / 1024, 0, 5);
		$v2 = 1024;
	}
	else
	{
		$v1 = substr($value, 0, 5);
		$v2 = 1;
	}
	$html = '<input type="text" name="' . $name . '" value="' . $v1 . '" size="10" /> ';
	$html .= Html::make_list($name . '_list', $v2, array(
		1 =>		'O',
		1024 =>		'KO',
		1048576 =>	'MO',
	));
	return ($html);
}

/* EOF */
