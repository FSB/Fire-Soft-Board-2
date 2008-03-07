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
	/**
	 * Identifiant pour le cache en cours
	 *
	 * @var string
	 */
	private $id = '';

	/**
	 * Contient les informations sauvées dans le cache SQL
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Nom du cache
	 *
	 * @var unknown_type
	 */
	public $cache_type = 'FSB SQL cache';

	/**
	 * Constructeur
	 *
	 * @param string $id Identifiant pour le cache en cours
	 * @param string $where Clause WHERE SQL
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

	/**
	 * @see Cache::exists()
	 */
	public function exists($hash)
	{
			return ((isset($this->data[$hash])) ? TRUE : FALSE);
	}

	/**
	 * @see Cache::get()
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

	/**
	 * @see Cache::put()
	 */
	public function put($hash, $value, $comments = '', $timestamp = NULL)
	{
		Fsb::$db->insert('cache', array(
			'cache_type' =>		array($this->id, TRUE),
			'cache_hash' =>		array($hash, TRUE),
			'cache_content' =>	serialize($value),
			'cache_time' =>		($timestamp === NULL) ? CURRENT_TIME : $timestamp,
		), 'REPLACE');
	}

	/**
	 * @see Cache::get_time()
	 */
	public function get_time($hash)
	{
		return ($this->data[$hash]['time']);
	}

	/**
	 * @see Cache::delete()
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

	/**
	 * @see Cache::destroy()
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

	/**
	 * @see Cache::garbage_colector()
	 */
	public function garbage_colector($time)
	{
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'cache
					WHERE cache_type = \'' . $this->id . '\'
						AND cache_time < ' . (CURRENT_TIME - $time);
		Fsb::$db->query($sql, FALSE);
	}

	/**
	 * @see Cache::list_keys()
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