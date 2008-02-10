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
	// Chemin du cache FTP
	private $path;

	// Type de cache
	public $cache_type = 'FSB FTP cache';

	// Mise en cache des informations du cache
	private $store = array();

	/*
	** Constructeur
	** -----
	** $ftp_path ::			Chemin vers le dossier du cache
	*/
	public function __construct($ftp_path)
	{
		$this->path = $ftp_path;
	}

	/*
	** Retourne TRUE s'il y a un cache pour le hash, sinon FALSE
	** -----
	** $hash ::			Clef pour les données cherchées
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

	/*
	** Retourne le tableau de données mises en cache
	** -----
	** $hash ::			Clef pour les données cherchées
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

	/*
	** Retourne le tableau de données mises en cache
	** -----
	** $hash ::			Clef pour les données cherchées
	** $array ::		Tableau de données à mettre en cache
	** $comments ::		Commentaire pour le fichier du cache
	** $timestamp ::	Date de création du cache
	*/
	public function put($hash, $array, $comments = '', $timestamp = NULL)
	{
		$export = str_replace(array('\\', "'"), array('\\\\', "\'"), serialize($array));
		$fd = @fopen($this->path . $hash . '.' . PHPEXT, 'w');
		fwrite($fd, "<?php\n/*\n$comments\n*/\n\$_cache_data = '" . $export . "';\nreturn(\$_cache_data);\n?>");
		fclose($fd);
		@touch($this->path . $hash . '.' . PHPEXT, ($timestamp === NULL) ? CURRENT_TIME : $timestamp);
		@chmod($this->path . $hash . '.' . PHPEXT, 0666);
	}

	/*
	** Renvoie le timestamp de création du cache
	** -----
	** $hash ::			Clef pour les données cherchées
	*/
	public function get_time($hash)
	{
		return (filemtime($this->path . $hash . '.' . PHPEXT));
	}

	/*
	** Supprime une clef
	** -----
	** $hash ::		Clef à supprimer
	*/
	public function delete($hash)
	{
		if (is_writable($this->path) && is_writable($this->path . $hash . '.' . PHPEXT))
		{
			@unlink($this->path . $hash . '.' . PHPEXT);
		}
	}

	/*
	** Destruction du cache
	** -----
	** $prefix ::		Si un préfixe est spécifié, on supprime uniquement les hash commençant par ce préfixe
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

	/*
	** Supprime les données du cache exedant un certain temps
	** -----
	** $time ::		Durée après laquelle les données du cache sont vidées
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

	/*
	** Retourne la liste des clefs mises en cache
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