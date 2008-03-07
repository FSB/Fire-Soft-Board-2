<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/cache/cache_ftp.php
** | Begin :	21/10/2006
** | Last :		06/01/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion du cache FTP
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
			if ($get === FALSE)
			{
				return (FALSE);
			}

			$this->store[$hash] = $get;
			return (TRUE);
		}

		return (FALSE);
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
	public function put($hash, $value, $comments = '', $timestamp = NULL)
	{
		$export = str_replace(array('\\', "'"), array('\\\\', "\'"), serialize($value));
		$fd = @fopen($this->path . $hash . '.' . PHPEXT, 'w');
		fwrite($fd, "<?php\n/*\n$comments\n*/\n\$_cache_data = '" . $export . "';\nreturn(\$_cache_data);\n?>");
		fclose($fd);
		@touch($this->path . $hash . '.' . PHPEXT, ($timestamp === NULL) ? CURRENT_TIME : $timestamp);
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
	public function destroy($prefix = NULL)
	{
		$fd = opendir($this->path);
		while ($file = readdir($fd))
		{
			if ($file{0} != '.' && ($prefix === NULL || substr($file, 0, strlen($prefix)) == $prefix))
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
			if ($file{0} != '.' && filemtime($this->path . $file) < (CURRENT_TIME - $time))
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