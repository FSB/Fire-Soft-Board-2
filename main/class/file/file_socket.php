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
 * Gestion des fichiers via une connexion socket
 */
class File_socket extends File
{
	/**
	 * Socket de connexion
	 *
	 * @var resource
	 */
	private $sock;

	/**
	 * Methode de gestion des fichiers
	 *
	 * @var string
	 */
	public $method = 'socket';

	/**
	 * @see File::connexion()
	 */
	protected function _connexion($server, $login, $password, $port, $path)
	{
		$list = explode(',', @ini_get('disable_functions'));
		if (in_array('fsockopen', $list))
		{
			return (File::FILE_FSOCKOPEN_DISABLED);
		}

		// Connexion socket au serveur
		$errno = 0;
		$errstr = '';
		$this->sock = @fsockopen($server, $port, $errno, $errstr, 15);

		if (!$this->sock || !$this->_read())
		{
			return (File::FILE_CANT_CONNECT_SERVER);
		}

		// Login
		if (!$this->_send("USER $login"))
		{
			return (File::FILE_CANT_AUTHENTIFICATE);
		}

		// Mot de passe
		if (!$this->_send("PASS $password"))
		{
			return (File::FILE_CANT_AUTHENTIFICATE);
		}

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
		return ($this->_send("CWD $path"));
	}

	/**
	 * @see File::rename()
	 */
	protected function _rename($from, $to)
	{
		$this->_send("RNFR $from");
		$this->_send("RNTO $to");
	}

	/**
	 * @see File::chmod()
	 */
	protected function _chmod($file, $mode)
	{
		return ($this->_send("SITE CHMOD $mode $file"));
	}

	/**
	 * @see File::_put()
	 */
	protected function _put($src, $dst)
	{
		$this->_send("TYPE I");
		$socket = $this->_open_connexion();
		$this->_send("STOR $dst", false);

		// On envoie le fichier sur le reseau
		if ($socket)
		{
			$fd = fopen($this->local_path . $src, 'rb');
			while (!feof($fd))
			{
				fwrite($socket, fread($fd, 4096));
			}
			fclose($fd);
		}
		else
		{
			return (File::FILE_CANT_CONNECT_SERVER);
		}

		$this->_close_connexion($socket);
		return (true);
	}

	/**
	 * @see File::unlink()
	 */
	protected function _unlink($filename)
	{
		$this->_send("DELE $filename");
	}

	/**
	 * @see File::mkdir()
	 */
	protected function _mkdir($dir)
	{
		return ($this->_send("MKD $dir"));
	}

	/**
	 * @see File::rmdir()
	 */
	protected function _rmdir($dir)
	{
		return ($this->_send("RMD $dir"));
	}

	/**
	 * Lit la reponse du serveur sur la socket
	 */
	protected function _read()
	{
		$str = '';
		do
		{
			$str .= @fgets($this->sock, 512);
		}
		while (substr($str, 3, 1) != ' ');

		if (!preg_match('#^[1-3]#', $str))
		{
			return (false);
		}
		return ($str);
	}

	/**
	 * Ecrit sur la socket
	 */
	protected function _send($command, $check = true)
	{
		fwrite($this->sock, $command . "\r\n");
		if ($check && !$this->_read())
		{
			return (false);
		}
		return (true);
	}

	/**
	 * Ouvre une connexion pour l'envoie de donnees sur le socket, renvoie le socket de connexion
	 */
	protected function _open_connexion()
	{
		$this->_send("PASV", false);
		if (!$read = $this->_read())
		{
			return (File::FILE_CANT_CONNECT_SERVER);
		}

		// On lit la reponse (qui doit contenir une IP et un port)
		if (!preg_match('#[0-9]{1,3},[0-9]{1,3},[0-9]{1,3},[0-9]{1,3},[0-9]+,[0-9]+#', $read, $match))
		{
			return (File::FILE_CANT_CONNECT_SERVER);
		}

		// On recupere l'IP et le port du serveur
		$split = explode(',', $match[0]);
		$ip = $split[0] . '.' . $split[1] . '.' . $split[2] . '.' . $split[3];
		$port = $split[4] * 256 + $split[5];

		// Connexion socket
		$errno = 0;
		$errstr = '';
		if (!$socket = fsockopen($ip, $port, $errno, $errstr, 15))
		{
			return (false);
		}
		return ($socket);
	}

	/**
	 * Ferme la connexion ouverte par la methode _open_connexion()
	 *
	 * @param resource $socket
	 * @return bool
	 */
	protected function _close_connexion($socket)
	{
		return (fclose($socket));
	}

	/**
	 * @see File::_close()
	 */
	protected function _close()
	{
		$this->_send("QUIT", false);
		@fclose($this->sock);
	}
}

/* EOF */