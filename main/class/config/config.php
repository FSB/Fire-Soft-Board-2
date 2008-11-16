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
 * Gestion de la configuration du forum
 */
class Config extends Fsb_model
{
	/**
	 * Contenu de la configuration
	 *
	 * @var array
	 */
	public $data = array();

	/**
	 * Table de la base de donnee gerant la configuration
	 *
	 * @var string
	 */
	private $table = 'config';

	/**
	 * Constructeur, charge la configuration du forum
	 *
	 * @param string $table Table de la base de donnee gerant la configuration
	 * @param array $default Configuration par defaut, si passe ne va pas lire la base de donnee
	 */
	public function __construct($table = 'config', $default = array())
	{
		$this->table = $table;

		if (!$default)
		{
			$sql = 'SELECT cfg_name, cfg_value
					FROM ' . SQL_PREFIX . $this->table;
			$result = Fsb::$db->query($sql, 'config_');
			while ($row = Fsb::$db->row($result))
			{
				$this->data[$row['cfg_name']] = $row['cfg_value'];
			}
			Fsb::$db->free($result);
		}
		else
		{
			$this->data = $default;
		}
	}

	/**
	 * Verifie l'existence d'une clef de configuration
	 *
	 * @param string $key Clef de configuration
	 * @return bool
	 */
	public function exists($key)
	{
		return (isset($this->data[$key]));
	}

	/**
	 * Recupere une valeur de la configuration
	 *
	 * @param string $key Clef de configuration
	 * @return string
	 */
	public function get($key)
	{
		return ((isset($this->data[$key])) ? $this->data[$key] : NULL);
	}

	/**
	 * Modifie localement une valeur de configuration (modification non sauvee en base de donnee)
	 *
	 * @param string $key Clef de configuration
	 * @param string $value Nouvelle valeur
	 */
	public function set($key, $value)
	{
		$this->data[$key] = $value;
	}

	/**
	 * Modifie une valeur de la configuration dans la base de donnee
	 *
	 * @param string $key Clef de configuration
	 * @param  string $value Nouvelle valeur
	 * @param bool $cache Si TRUE, rafraichi a jour le cache
	 */
	public function update($key, $value, $cache = TRUE)
	{
		$this->set($key, $value);
		Fsb::$db->update($this->table, array(
			'cfg_value' =>	$this->get($key),
		), 'WHERE cfg_name = \'' . $key . '\'');

		if ($cache)
		{
			$this->destroy_cache();
		}
	}

	/**
	 * Rafraichi le cache de configuration
	 */
	public function destroy_cache()
	{
		Fsb::$db->destroy_cache('config_');
	}
}

/* EOF */