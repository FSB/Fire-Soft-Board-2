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
 * Page permettant de deplacer, cacher / afficher les modules du portail
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Activation/Désactivation du module
	 *
	 * @var string
	 */
	public $activ;

	/**
	 * Module du portail
	 *
	 * @var int
	 */
	public $module;

	/**
	 * Déplacement du module
	 *
	 * @var string
	 */
	public $move;

	/**
	 * Constructeur
	 */
	public function main()
	{
		Fsb::$session->load_lang('lg_forum_portail');

		$this->activ =	Http::request('activ');
		$this->module = Http::request('module');
		$this->move =	Http::request('move');

		// Deplacement du module
		if ($this->move && $this->module)
		{
			$this->move_module();
		}
		// Activation / desactivation du module
		else if ($this->activ)
		{
			$this->activ_module();
		}
		// Mise a jour de la configuration
		else if (Http::request('submit', 'post'))
		{
			$this->update_config();
		}

		$this->show_config();
		$this->show_module();
	}

	/**
	 * Affiche les modules
	 */
	public function show_module()
	{
		Fsb::$tpl->set_switch('portail_list');

		// On affiche les modules
		$sql = 'SELECT pm.pm_name, pm.pm_position, pm.pm_activ, COUNT(pc.portail_name) AS config_exists
				FROM ' . SQL_PREFIX . 'portail_module pm
				LEFT JOIN ' . SQL_PREFIX . 'portail_config pc
					ON pm.pm_name = pc.portail_module
				GROUP BY pm.pm_name, pm.pm_position, pm.pm_activ, pm.pm_order
				ORDER BY pm.pm_position, pm.pm_order';
		$result = Fsb::$db->query($sql);

		$print_pos = array();
		while ($row = Fsb::$db->row($result))
		{
			// On gere les colones sans block (c'est a dire s'il y a deux blocks a droite, deux blocks a gauche, et
			// rien au centre on affiche tout de meme la colone centrale)
			if (!isset($print_pos[$row['pm_position']]))
			{
				if (($row['pm_position'] == 'middle' && !isset($print_pos['left'])) || ($row['pm_position'] == 'right' && !isset($print_pos['left'])))
				{
					$print_pos['left'] = true;
					Fsb::$tpl->set_blocks('pos', array());
				}

				if ($row['pm_position'] == 'right' && !isset($print_pos['middle']))
				{
					$print_pos['middle'] = true;
					Fsb::$tpl->set_blocks('pos', array());
				}

				$print_pos[$row['pm_position']] = true;
				Fsb::$tpl->set_blocks('pos', array());
			}

			Fsb::$tpl->set_blocks('pos.portail', array(
				'NAME' =>			Fsb::$session->lang('pm_' . $row['pm_name']),
				'ACTIV_IMG' =>		'adm_tpl/img/' . (($row['pm_activ']) ? 'on' : 'off') . '.gif',
				'HAVE_CONFIG' =>	($row['config_exists']) ? true : false,

				'U_UP' =>			sid('index.' . PHPEXT . '?p=general_portail&amp;module=' . $row['pm_name'] . '&amp;move=up'),
				'U_LEFT' =>			sid('index.' . PHPEXT . '?p=general_portail&amp;module=' . $row['pm_name'] . '&amp;move=left'),
				'U_RIGHT' =>		sid('index.' . PHPEXT . '?p=general_portail&amp;module=' . $row['pm_name'] . '&amp;move=right'),
				'U_DOWN' =>			sid('index.' . PHPEXT . '?p=general_portail&amp;module=' . $row['pm_name'] . '&amp;move=down'),
				'U_ACTIV' =>		sid('index.' . PHPEXT . '?p=general_portail&amp;module=' . $row['pm_name'] . '&amp;activ=' . (($row['pm_activ']) ? 'off' : 'on')),
				'U_CONFIG' =>		sid('index.' . PHPEXT . '?p=general_portail&amp;module=' . $row['pm_name'] . '&amp;config=true'),
			));
		}
		Fsb::$db->free($result);

		if (!isset($print_pos['right']))
		{
			Fsb::$tpl->set_blocks('pos', array());
		}

		Fsb::$tpl->set_vars(array(
			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=general_portail&amp;module=' . $this->module),
		));
	}

	/**
	 * Deplacement d'un module
	 */
	public function move_module()
	{
		// Donnees courantes du module
		$sql = 'SELECT pm_position, pm_order
					FROM ' . SQL_PREFIX . 'portail_module
					WHERE pm_name = \'' . Fsb::$db->escape($this->module) . '\'';
		$current = Fsb::$db->request($sql);
		if ($current)
		{
			// Suivant si on deplace verticalement ou horizontalement l'algorithme change
			switch ($this->move)
			{
				case 'up' :
				case 'down' :
					// On recupere le module dont la position est avant / apres le module actuel
					$sql = 'SELECT pm_name, pm_order
								FROM ' . SQL_PREFIX . 'portail_module
								WHERE pm_order = ' . intval($current['pm_order'] + (($this->move == 'down') ? 1 : -1)) . '
									AND pm_position = \'' . Fsb::$db->escape($current['pm_position']) . '\'';
					$result = Fsb::$db->query($sql);
					$swap = Fsb::$db->row($result);
					Fsb::$db->free($result);

					if ($swap)
					{
						// On met a jour l'ordre des deux modules
						Fsb::$db->update('portail_module', array(
							'pm_order' =>		$swap['pm_order'],
						), 'WHERE pm_name = \'' . Fsb::$db->escape($this->module) . '\'');

						Fsb::$db->update('portail_module', array(
							'pm_order' =>		$current['pm_order'],
						), 'WHERE pm_name = \'' . Fsb::$db->escape($swap['pm_name']) . '\'');
					}
				break;

				case 'left' :
				case 'right' :
					// Deplacement normal
					if (($this->move == 'left' && $current['pm_position'] != 'left') || ($this->move == 'right' && $current['pm_position'] != 'right'))
					{
						$position_table = array('left', 'middle', 'right');
						$flip_position_table = array_flip($position_table);
						$next_position = $position_table[$flip_position_table[$current['pm_position']] + (($this->move == 'right') ? 1 : -1)];
					}
					else
					{
						// Deplacement d'une extremite vers une autre (par un exemple si on deplace un block qui est a la base
						// a droite, encore a droite, on le met cette fois a gauche)
						$next_position = ($this->move == 'right') ? 'left' : 'right';
					}

					// On recupere l'ordre maximale pour la nouvelle position
					$sql = 'SELECT MAX(pm_order) AS max
								FROM ' . SQL_PREFIX . 'portail_module
								WHERE pm_position = \'' . Fsb::$db->escape($next_position) . '\'';
					$max = Fsb::$db->get($sql, 'max');

					// On met a jour le module deplace
					Fsb::$db->update('portail_module', array(
						'pm_position' =>	$next_position,
						'pm_order' =>		(isset($max['max'])) ? $max['max'] + 1 : 0,
					), 'WHERE pm_name = \'' . Fsb::$db->escape($this->module) . '\'');

					// On met a jour les ordres des modules qui etaient sous le module deplace
					Fsb::$db->update('portail_module', array(
						'pm_order' =>		array('(pm_order - 1)', 'is_field' => true),
					), 'WHERE pm_position = \'' . Fsb::$db->escape($current['pm_position']) . '\' AND pm_order > ' . intval($current['pm_order']));
				break;
			}

			Fsb::$db->destroy_cache('portail_module_');
			Http::redirect('index.' . PHPEXT . '?p=general_portail');
		}
	}

	/**
	 * Activation / Desactivation d'un module
	 */
	public function activ_module()
	{
		// Donnees courantes du module
		$sql = 'SELECT pm_position
					FROM ' . SQL_PREFIX . 'portail_module
					WHERE pm_name = \'' . Fsb::$db->escape($this->module) . '\'';
		$result = Fsb::$db->query($sql);
		$current = Fsb::$db->row($result);
		Fsb::$db->free($result);

		if ($current)
		{
			Fsb::$db->update('portail_module', array(
				'pm_activ' =>	($this->activ == 'on') ? 1 : 0,
			), 'WHERE pm_name = \'' . Fsb::$db->escape($this->module) . '\'');

			Fsb::$db->destroy_cache('portail_module_');
			Http::redirect('index.' . PHPEXT . '?p=general_portail');
		}
	}

	/**
	 * Configuration du portail
	 */
	public function show_config()
	{
		if (Http::request('config'))
		{
			// On instancie la classe de configuration dynamique
			$tmp = array();
			$config = new Config_edit($tmp, 'adm_pm_config_');

			// On affiche ligne par ligne la configuration
			$sql = 'SELECT *
					FROM ' . SQL_PREFIX . 'portail_config
					WHERE portail_module = \'' . Fsb::$db->escape($this->module) . '\'
					ORDER BY portail_name';
			$result = Fsb::$db->query($sql, 'portail_config_');

			$config_exists = false;
			while ($row = Fsb::$db->row($result))
			{
				if (!$config_exists)
				{
					Fsb::$tpl->set_switch('show_config');
					$config->set_cat(Fsb::$session->lang('pm_' . $this->module));
					$config_exists = true;
				}

				$config->cfg[$row['portail_name']] = $row['portail_value'];
				$config->set_line($row['portail_name'], $row['portail_functions'], $row['portail_args']);
			}
			Fsb::$db->free($result);
		}
	}

	/**
	 * Traitement des information du formulaire de configuration soumis
	 */
	public function update_config()
	{
		$tmp = array();
		$config = new Config_edit($tmp, 'adm_pm_config_');
		$data = $config->validate($_POST, 'portail_config', 'portail_name', 'portail_type');

		// Mise a jour de la configuration
		foreach ($data AS $key => $value)
		{
			Fsb::$db->update('portail_config', array(
				'portail_value' =>	Fsb::$db->escape($value),
			), 'WHERE portail_name = \'' . Fsb::$db->escape($key) . '\'');
		}
		Fsb::$db->destroy_cache('portail_config_');

		Log::add(Log::ADMIN, 'portail_config', $this->module);
		Display::message('adm_portail_config_well_submit', 'index.' . PHPEXT . '?p=general_portail', 'general_portail');
	}
}

/* EOF */
