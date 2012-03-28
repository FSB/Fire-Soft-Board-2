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
 * Gestion du cache avec APC
 */
class Cache_apc extends Cache
{
	/**
	 * Contient la liste des clefs en cache
	 *
	 * @var array
	 */
	private $stack = array();

	/**
	 * TTL des informations en cache
	 *
	 * @var int
	 */
	private $ttl = 86400;

	/**
	 * Identifiant unique pour differencier les types de cache
	 *
	 * @var int
	 */
	private static $key_id = 0;
	
	/**
	 * Identifiant unique
	 *
	 * @var int
	 */
	private $id = 0;

	/**
	 * Nom du cache
	 *
	 * @var unknown_type
	 */
	public $cache_type = 'Alternative PHP Cache';

	/**
	 * Hash unique pour differencier les clefs
	 *
	 * @var string
	 */
	private $uniq_hash = '';

	/**
	 * Constructeur
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

	/**
	 * @see Cache::exists()
	 */
	public function exists($hash)
	{
		return ((apc_fetch($hash . $this->uniq_hash) !== false) ? true : false);
	}

	/**
	 * @see Cache::get()
	 */
	public function get($hash)
	{
		return (unserialize(apc_fetch($hash . $this->uniq_hash)));
	}

	/**
	 * @see Cache::put()
	 */
	public function put($hash, $value, $comments = '', $timestamp = null)
	{
		apc_store($hash . $this->uniq_hash, serialize($value), $this->ttl);

		// On garde en memoire la clef mise en cache
		$this->stack[$hash] = ($timestamp) ? $timestamp : CURRENT_TIME;
		apc_store('_apc_keys_' . $this->id . $this->uniq_hash, serialize($this->stack), ONE_MONTH);
	}

	/**
	 * @see Cache::get_time()
	 */
	public function get_time($hash)
	{
		return ((isset($this->stack[$hash])) ? $this->stack[$hash] : 0);
	}

	/**
	 * @see Cache::delete()
	 */
	public function delete($hash)
	{
		apc_delete($hash . $this->uniq_hash);
		unset($this->stack[$hash]);
		apc_store('_apc_keys_' . $this->id . $this->uniq_hash, serialize($this->stack), ONE_MONTH);
	}

	/**
	 * @see Cache::destroy()
	 */
	public function destroy($prefix = null)
	{
		foreach ($this->stack AS $key => $bool)
		{
			if (is_null($prefix) || substr($key, 0, strlen($prefix)) == $prefix)
			{
				apc_delete($key . $this->uniq_hash);
				unset($this->stack[$key]);
			}
		}
	}

	/**
	 * @see Cache::garbage_colector()
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

	/**
	 * @see Cache::list_keys()
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