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
 * Gestion du cache avec le systeme de fichier
 */
class Cache_ftp extends Cache
{
	/**
	 * Dossiers ou sont stockes les fichiers cache
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Nom du cache
	 *
	 * @var unknown_type
	 */
	public $cache_type = 'FSB FTP cache';

	/**
	 * Sauve certaines informations deja chargees pour optimiser en memoire
	 */
	private $store = array();

	/**
	 * Constructeur
	 *
	 * @param string $ftp_path Chemin vers le dossier du cache
	 */
	public function __construct($ftp_path)
	{
		$this->path = $ftp_path;
	}

	/**
	 * @see Cache::exists()
	 */
	public function exists($hash)
	{
		if (file_exists($this->path . $hash . '.' . PHPEXT))
		{
			$get = $this->get($hash);
			if (is_null($get))
			{
				return (false);
			}

			$this->store[$hash] = $get;
			return (true);
		}

		return (false);
	}

	/**
	 * @see Cache::get()
	 */
	public function get($hash)
	{
		if (isset($this->store[$hash]))
		{
			return ($this->store[$hash]);
		}
		$include = @include($this->path . $hash . '.' . PHPEXT);
		return (@unserialize($include));
	}

	/**
	 * @see Cache::put()
	 */
	public function put($hash, $value, $comments = '', $timestamp = null)
	{
		$export = str_replace(array('\\', "'"), array('\\\\', "\'"), serialize($value));
		$fd = @fopen($this->path . $hash . '.' . PHPEXT, 'w');
		fwrite($fd, "<?php\n/*\n$comments\n*/\n\$_cache_data = '" . $export . "';\nreturn(\$_cache_data);\n?>");
		fclose($fd);
		@touch($this->path . $hash . '.' . PHPEXT, (is_null($timestamp)) ? CURRENT_TIME : $timestamp);
		@chmod($this->path . $hash . '.' . PHPEXT, 0666);
	}

	/**
	 * @see Cache::get_time()
	 */
	public function get_time($hash)
	{
		return (filemtime($this->path . $hash . '.' . PHPEXT));
	}

	/**
	 * @see Cache::delete()
	 */
	public function delete($hash)
	{
		if (is_writable($this->path) && is_writable($this->path . $hash . '.' . PHPEXT))
		{
			@unlink($this->path . $hash . '.' . PHPEXT);
		}
	}

	/**
	 * @see Cache::destroy()
	 */
	public function destroy($prefix = null)
	{
		$fd = opendir($this->path);
		while ($file = readdir($fd))
		{
			if ($file{0} != '.' && (is_null($prefix) || substr($file, 0, strlen($prefix)) == $prefix))
			{
				$this->delete(substr($file, 0, -4));
			}
		}
		closedir($fd);
	}

	/**
	 * @see Cache::garbage_colector()
	 */
	public function garbage_colector($time)
	{
		$fd = opendir($this->path);
		while ($file = readdir($fd))
		{
			if ($file{0} != '.' && $file != 'index.html' && filemtime($this->path . $file) < (CURRENT_TIME - $time))
			{
				@unlink($this->path . $file);
			}
		}
		closedir($fd);
	}

	/**
	 * @see Cache::list_keys()
	 */
	public function list_keys()
	{
		$return = array();
		$fd = opendir($this->path);
		while ($file = readdir($fd))
		{
			if ($file{0} != '.' && preg_match('#^(.*?)\.' . PHPEXT . '$#', $file, $m) && is_readable($this->path . $file))
			{
				$return[] = $m[1];
			}
		}
		closedir($fd);
		sort($return);
		return ($return);
	}
}

?>