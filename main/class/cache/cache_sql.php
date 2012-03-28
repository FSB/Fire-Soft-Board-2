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
 * Gestion du cache en base de donnee
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
	 * Contient les informations sauvees dans le cache SQL
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
		Fsb::$db->cache = true;
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
				'serialized' =>		true,
			);
		}
		Fsb::$db->free($result);
		Fsb::$db->cache = null;
	}

	/**
	 * @see Cache::exists()
	 */
	public function exists($hash)
	{
			return ((isset($this->data[$hash])) ? true : false);
	}

	/**
	 * @see Cache::get()
	 */
	public function get($hash)
	{
		if (!$this->exists($hash))
		{
			return (null);
		}

		if ($this->data[$hash]['serialized'])
		{
			$this->data[$hash]['content'] = @unserialize($this->data[$hash]['content']);
			$this->data[$hash]['serialized'] = false;
		}
		return ($this->data[$hash]['content']);
	}

	/**
	 * @see Cache::put()
	 */
	public function put($hash, $value, $comments = '', $timestamp = null)
	{
		Fsb::$db->insert('cache', array(
			'cache_type' =>		array($this->id, true),
			'cache_hash' =>		array($hash, true),
			'cache_content' =>	serialize($value),
			'cache_time' =>		(is_null($timestamp)) ? CURRENT_TIME : $timestamp,
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
	public function delete($hash, $delete_sql = true)
	{
		if ($delete_sql)
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'cache
					WHERE cache_type = \'' . $this->id . '\'
						AND cache_hash = \'' . Fsb::$db->escape($hash) . '\'';
			Fsb::$db->query($sql, false);
		}
		unset($this->data[$hash]);
	}

	/**
	 * @see Cache::destroy()
	 */
	public function destroy($prefix = null)
	{
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'cache
				WHERE cache_type = \'' . $this->id . '\''
				. ((!is_null($prefix)) ? ' AND cache_hash ' . Fsb::$db->like() . ' \'' . $prefix . '%\'' : '');
		Fsb::$db->query($sql, false);

		foreach ($this->data AS $key => $value)
		{
			if (is_null($prefix) || substr($key, 0, strlen($prefix)) == $prefix)
			{
				$this->delete($key, false);
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
		Fsb::$db->query($sql, false);
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