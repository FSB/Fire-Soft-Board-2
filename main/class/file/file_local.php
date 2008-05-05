<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/file/file_local.php
** | Begin :	12/07/2007
** | Last :		12/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/**
 * Gestion des fichiers via les fonction f***()
 */
class File_local extends File
{
	/**
	 * Methode de gestion des fichiers
	 *
	 * @var string
	 */
	public $method = 'local';

	/**
	 * @see File::connexion()
	 */
	protected function _connexion($server, $login, $password, $port, $path)
	{
		$this->root_path = $path;
		$this->local_path = $path;
		return (TRUE);
	}

	/**
	 * @see File::chdir()
	 */
	protected function _chdir($path)
	{
		return (@chdir($path));
	}

	/**
	 * @see File::rename()
	 */
	protected function _rename($from, $to)
	{
		return (@rename($from, $to));
	}

	/**
	 * @see File::chmod()
	 */
	protected function _chmod($file, $mode)
	{
		return (@chmod($file, $mode));
	}

	/**
	 * @see File::_put()
	 */
	protected function _put($src, $dst)
	{
		$result = @rename($src, $dst);
		if (!$result && ((file_exists($dst) && is_writable($dst)) || is_writable(dirname($dst))))
		{
			$fd = fopen($dst, 'w');
			if (!$fd)
			{
				return (FALSE);
			}
			fwrite($fd, file_get_contents($src));
			fclose($fd);
			return (TRUE);
		}
		return ($result);
	}

	/**
	 * @see File::unlink()
	 */
	protected function _unlink($filename)
	{
		return (@unlink($filename));
	}

	/**
	 * @see File::mkdir()
	 */
	protected function _mkdir($dir)
	{
		return (@mkdir($dir));
	}

	/**
	 * @see File::rmdir()
	 */
	protected function _rmdir($dir)
	{
		return (@rmdir($dir));
	}

	/**
	 * @see File::_close()
	 */
	protected function _close()
	{

	}
}

/* EOF */