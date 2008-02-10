<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/config/config.php
** | Begin :	09/08/2007
** | Last :		03/11/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Configuration du forum
*/
class Config extends Fsb_model
{
	public $data = array();

	// Table de configuration
	private $table = 'config';

	/*
	** Constructeur
	** Chargement de la configuration du forum
	** -----
	** $table ::		Table de la configuration
	** $default ::		En renseignant ce champ, on entre un tableau de configuration à la main (le forum 
	**					n'ira donc pas lire la table)
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

	/*
	** Vérifie l'existance d'une clef de configuration
	** -----
	** $key ::		Clef de configuration
	*/
	public function exists($key)
	{
		return (isset($this->data[$key]));
	}

	/*
	** Récupère une valeur de la configuration
	** -----
	** $key ::		Clef de configuration
	*/
	public function get($key)
	{
		return ((isset($this->data[$key])) ? $this->data[$key] : NULL);
	}

	/*
	** Modifie la valeur d'une clef (pas dans la base de donnée)
	** -----
	** $key ::		Clef de configuration
	** $value ::	Nouvelle valeur
	*/
	public function set($key, $value)
	{
		$this->data[$key] = $value;
	}

	/*
	** Modifie une valeur de la configuration dans la base de donnée
	** -----
	** $key ::		Clef de configuration
	** $value ::	Nouvelle valeur
	** $cache ::	Mise à jour du cache ?
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

	/*
	** Destruction du cache de la configuration
	*/
	public function destroy_cache()
	{
		Fsb::$db->destroy_cache('config_');
	}
}

/* EOF */