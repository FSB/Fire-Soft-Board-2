<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/cache/cache_sql.php
** | Begin :	21/10/2006
** | Last :		03/11/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion du cache SQL
*/
class Cache_sql extends Cache
{
	// Identifiant pour le type de cache actuel
	private $id = '';

	// Contient les donnees en cache
	private $data = array();

	// Type de cache
	public $cache_type = 'FSB SQL cache';

	/*
	** Constructeur
	** -----
	** $type ::				Identifiant pour le type de cache actuel
	** $where ::			Clause WHERE pour la requete
	*/
	public function __construct($id, $where = '')
	{
		$this->id = $id;
		Fsb::$db->cache = TRUE;
		$sql = 'SELECT cache_hash, cache_content, cache_time
				FROM ' . SQL_PREFIX . 'cache
				WHERE cache_type = \'' . $this->id . '\' '
				. (($where) ? ' AND ' . $where : '');
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$this->data[$row['cache_hash']] = array(
				'content' =>		$row['cache_content'],
				'time' =>			$row['cache_time'],
				'serialized' =>		TRUE,
			);
		}
		Fsb::$db->free($result);
		Fsb::$db->cache = NULL;
	}

	/*
	** Retourne TRUE s'il y a un cache pour le hash, sinon FALSE
	** -----
	** $hash ::			Clef pour les donnees cherchees
	*/
	public function exists($hash)
	{
			return ((isset($this->data[$hash])) ? TRUE : FALSE);
	}

	/*
	** Retourne le tableau de donnees mises en cache
	** -----
	** $hash ::			Clef pour les donnees cherchees
	*/
	public function get($hash)
	{
		if (!$this->exists($hash))
		{
			return (NULL);
		}

		if ($this->data[$hash]['serialized'])
		{
			$this->data[$hash]['content'] = @unserialize($this->data[$hash]['content']);
			$this->data[$hash]['serialized'] = FALSE;
		}
		return ($this->data[$hash]['content']);
	}

	/*
	** Retourne le tableau de donnees mises en cache
	** -----
	** $hash ::			Clef pour les donnees cherchees
	** $array ::		Tableau de donnees a mettre en cache
	** $comments ::		Commentaire pour le fichier du cache
	** $timestamp ::	Date de creation du cache
	*/
	public function put($hash, $array, $comments = '', $timestamp = NULL)
	{
		Fsb::$db->insert('cache', array(
			'cache_type' =>		array($this->id, TRUE),
			'cache_hash' =>		array($hash, TRUE),
			'cache_content' =>	serialize($array),
			'cache_time' =>		($timestamp === NULL) ? CURRENT_TIME : $timestamp,
		), 'REPLACE');
	}

	/*
	** Renvoie le timestamp de creation du cache
	** -----
	** $hash ::			Clef pour les donnees cherchees
	*/
	public function get_time($hash)
	{
		return ($this->data[$hash]['time']);
	}

	/*
	** Supprime une clef
	** -----
	** $hash ::		Clef a supprimer
	*/
	public function delete($hash, $delete_sql = TRUE)
	{
		if ($delete_sql)
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'cache
					WHERE cache_type = \'' . $this->id . '\'
						AND cache_hash = \'' . Fsb::$db->escape($hash) . '\'';
			Fsb::$db->query($sql, FALSE);
		}
		unset($this->data[$hash]);
	}

	/*
	** Destruction du cache
	** -----
	** $prefix ::		Si un prefixe est specifie, on supprime uniquement les hash commencant par ce prefixe
	*/
	public function destroy($prefix = NULL)
	{
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'cache
				WHERE cache_type = \'' . $this->id . '\''
				. (($prefix !== NULL) ? ' AND cache_hash ' . Fsb::$db->like() . ' \'' . $prefix . '%\'' : '');
		Fsb::$db->query($sql, FALSE);

		foreach ($this->data AS $key => $value)
		{
			if ($prefix === NULL || substr($key, 0, strlen($prefix)) == $prefix)
			{
				$this->delete($key, FALSE);
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
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'cache
					WHERE cache_type = \'' . $this->id . '\'
						AND cache_time < ' . (CURRENT_TIME - $time);
		Fsb::$db->query($sql, FALSE);
	}

	/*
	** Retourne la liste des clefs mises en cache
	*/
	public function list_keys()
	{
		$return = array();
		foreach ($this->data AS $key => $content)
		{
			$return[] = $key;
		}
		sort($return);
		return ($return);
	}
}

?>