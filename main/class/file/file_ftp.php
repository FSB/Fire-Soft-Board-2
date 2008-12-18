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
 * Gestion des fichiers via les fonctions FTP ftp_***()
 */
class File_ftp extends File
{
	/**
	 * Connexion au serveur FTP
	 *
	 * @var resource
	 */
	private $stream;

	/**
	 * Methode de gestion des fichiers
	 *
	 * @var string
	 */
	public $method = 'ftp';

	/**
	 * @see File::connexion()
	 */
	protected function _connexion($server, $login, $password, $port, $path)
	{
		// Verification de la gestion de l'extension FTP
		if (!extension_loaded('ftp'))
		{
			return (File::FILE_FTP_EXTENSION_DISABLED);
		}

		// Connexion au serveur
		$this->stream = ftp_connect($server, $port, 15);
		if ($this->stream === false)
		{
			return (File::FILE_CANT_CONNECT_SERVER);
		}

		// Authentification
		if (!ftp_login($this->stream, $login, $password))
		{
			return (File::FILE_CANT_AUTHENTIFICATE);
		}

		// On passe en mode passif (le client ecoute la connexion)
		ftp_pasv($this->stream, true);

		$this->root_path = './';
		$this->local_path = ROOT;

		// On se deplace a la racine du forum
		if (!$this->_chdir($path))
		{
			return (File::FILE_CANT_CHDIR);
		}
		return (true);
	}

	/**
	 * @see File::chdir()
	 */
	protected function _chdir($path)
	{
		return (@ftp_chdir($this->stream, $path));
	}

	/**
	 * @see File::rename()
	 */
	protected function _rename($from, $to)
	{
		return (@ftp_rename($this->stream, $from, $to));
	}
	/**
	 * @see File::chmod()
	 */
	protected function _chmod($file, $mode)
	{
		return (@ftp_chmod($this->stream, $mode, $file));
	}

	/**
	 * @see File::_put()
	 */
	protected function _put($src, $dst)
	{
		// Apparament ftp_put() a certains problemes, probablement lies au safe mode.
		// On utilise donc ftp_fput() qui fonctionne sans problemes.
		//		$result = ftp_put($this->stream, $dst, $src, FTP_BINARY);
		$fd = fopen($this->local_path . $src, 'r');
		$result = ftp_fput($this->stream, $dst, $fd, FTP_BINARY);
		fclose($fd);
		return ($result);
	}

	/**
	 * @see File::unlink()
	 */
	protected function _unlink($filename)
	{
		ftp_delete($this->stream, $filename);
	}

	/**
	 * @see File::mkdir()
	 */
	protected function _mkdir($dir)
	{
		return (ftp_mkdir($this->stream, $dir));
	}

	/**
	 * @see File::rmdir()
	 */
	protected function _rmdir($dir)
	{
		return (ftp_rmdir($this->stream, $dir));
	}

	/**
	 * @see File::_close()
	 */
	protected function _close()
	{
		ftp_close($this->stream);
	}
}

/* EOF */