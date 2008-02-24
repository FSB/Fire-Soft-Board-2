<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/cache/cache_apc.php
** | Begin :	21/10/2006
** | Last :		30/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion du cache APC
*/
class Cache_apc extends Cache
{
	// Contient la liste des clefs en cache
	private $stack = array();

	// Time To Live de la mise en cache des donnees
	private $ttl = 86400;

	// Identifiant unique pour differencier les types de cache
	private static $key_id = 0;
	private $id = 0;

	// Type de cache
	public $cache_type = 'Alternative PHP Cache';

	// Hash unique
	private $uniq_hash = '';

	/*
	** Constructeur
	*/
	public function __construct()
	{
		self::$key_id++;
		$this->id = self::$key_id;

		$this->uniq_hash = (file_exists(ROOT . 'config/config.' . PHPEXT)) ? md5_file(ROOT . 'config/config.' . PHPEXT) : md5(dirname($_SERVER['PHP_SELF']));
	
		$this->stack = ($this->exists('_apc_keys_' . $this->id)) ? $this->get('_apc_keys_' . $this->id) : array();
		if (!is_array($this->stack))
		{
			$this->stack = array();
		}
	}
	/*
	** Retourne TRUE s'il y a un cache pour le hash, sinon FALSE
	** -----
	** $hash ::			Clef pour les donnees cherchees
	*/
	public function exists($hash)
	{
		return ((apc_fetch($hash . $this->uniq_hash) !== FALSE) ? TRUE : FALSE);
	}

	/*
	** Retourne le tableau de donnees mises en cache
	** -----
	** $hash ::			Clef pour les donnees cherchees
	*/
	public function get($hash)
	{
		return (unserialize(apc_fetch($hash . $this->uniq_hash)));
	}

	/*
	** Ajoute des donnees dans le cache
	** -----
	** $hash ::			Clef pour les donnees cherchees
	** $array ::		Tableau de donnees a mettre en cache
	** $comments ::		Commentaire pour le fichier du cache
	** $timestamp ::	Date de creation du cache
	*/
	public function put($hash, $array, $comments = '', $timestamp = NULL)
	{
		apc_store($hash . $this->uniq_hash, serialize($array), $this->ttl);

		// On garde en memoire la clef mise en cache
		$this->stack[$hash] = ($timestamp) ? $timestamp : CURRENT_TIME;
		apc_store('_apc_keys_' . $this->id . $this->uniq_hash, serialize($this->stack), ONE_MONTH);
	}

	/*
	** Renvoie le timestamp de creation du cache
	** -----
	** $hash ::			Clef pour les donnees cherchees
	*/
	public function get_time($hash)
	{
		return ((isset($this->stack[$hash])) ? $this->stack[$hash] : 0);
	}

	/*
	** Supprime une clef
	** -----
	** $hash ::		Clef a supprimer
	*/
	public function delete($hash)
	{
		apc_delete($hash . $this->uniq_hash);
		unset($this->stack[$hash]);
		apc_store('_apc_keys_' . $this->id . $this->uniq_hash, serialize($this->stack), ONE_MONTH);
	}

	/*
	** Destruction du cache
	** -----
	** $prefix ::		Si un prefixe est specifie, on supprime uniquement les hash commencant par ce prefixe
	*/
	public function destroy($prefix = NULL)
	{
		foreach ($this->stack AS $key => $bool)
		{
			if ($prefix === NULL || substr($key, 0, strlen($prefix)) == $prefix)
			{
				apc_delete($key . $this->uniq_hash);
				unset($this->stack[$key]);
			}
		}
	}

	/*
	** Supprime les donnees du cache exedant un certain temps
	** -----
	** $time ::		Duree apres laquelle les donnees du cache sont videes
	*/
	public function garbage_colector($time)
	{
		foreach ($this->stack AS $key => $timestamp)
		{
			if ($timestamp < (CURRENT_TIME - $time))
			{
				apc_delete($key . $this->uniq_hash);
				unset($this->stack[$key]);
			}
		}
		apc_store('_apc_keys_' . $this->id . $this->uniq_hash, serialize($this->stack), ONE_MONTH);
	}

	/*
	** Retourne la liste des clefs mises en cache
	*/
	public function list_keys()
	{
		$return = array();
		foreach ($this->stack AS $key => $timeout)
		{
			$return[] = $key;
		}
		sort($return);
		return ($return);
	}
}

?>