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
 * Permet de manipuler des fichiers sur le serveur de plusieurs fa�on (f***(), ftp_***() ou fsockopen())
 */
abstract class File extends Fsb_model
{
	/**
	 * Chemin vers la racine du forum
	 *
	 * @var string
	 */
	public $root_path = './';

	/**
	 * Chemin local vers la racine du forum
	 *
	 * @var string
	 */
	public $local_path = './';

	/**
	 * Impossible de se connecter au serveur
	 */
	const FILE_CANT_CONNECT_SERVER = 1;
	
	/**
	 * Impossible de s'authentifier
	 */
	const FILE_CANT_AUTHENTIFICATE = 2;
	
	/**
	 * Impossible de changer de dossier
	 */
	const FILE_CANT_CHDIR = 3;
	
	/**
	 * La fonction fsockopen() est desactivee
	 */
	const FILE_FSOCKOPEN_DISABLED = 4;
	
	/**
	 * L'extension FTP est desactivee
	 */
	const FILE_FTP_EXTENSION_DISABLED = 5;
	
	/**
	 * @see File::connexion()
	 */
	abstract protected function _connexion($server, $login, $password, $port, $path);
	
	/**
	 * @see File::chdir()
	 */
	abstract protected function _chdir($path);
	
	/**
	 * @see File::rename()
	 */
	abstract protected function _rename($from, $to);
	
	/**
	 * @see File::chmod()
	 */
	abstract protected function _chmod($file, $mode);
	
	/**
	 * Copie un fichier vers une destination
	 *
	 * @param string $src Fichier source
	 * @param string $dst Destination
	 */
	abstract protected function _put($src, $dst);
	
	/**
	 * @see File::unlink()
	 */
	abstract protected function _unlink($filename);
	
	/**
	 * @see File::mkdir()
	 */
	abstract protected function _mkdir($dir);

	/**
	 * @see File::rmdir()
	 */
	abstract protected function _rmdir($dir);
	/**
	 * Ferme la connexion
	 */
	abstract protected function _close();

	/**
	 * Design pattern factory, retourne une instance de la classe File
	 *
	 * @param bool $use_ftp True si on utilise une connexion FTP
	 * @return File
	 */
	public static function factory($use_ftp)
	{
		if ($use_ftp)
		{
			$identifier = Display::check_ftp();
			if (extension_loaded('ftp'))
			{
				$file = new File_ftp();
			}
			else
			{
				$file = new File_socket();
			}
			$file->connexion($identifier['host'], $identifier['login'], $identifier['password'], $identifier['port'], $identifier['path']);
		}
		else
		{
			$file = new File_local();
			$file->connexion('', '', '', '', ROOT);
		}
		return ($file);
	}

	/**
	 * Connnexion au serveur
	 *
	 * @param string $server Adresse du serveur
	 * @param string $login Login
	 * @param string $password Mot de passe
	 * @param int $port Port
	 * @param string $path Chemin vers la racine du forum
	 */
	public function connexion($server, $login, $password, $port, $path)
	{
		$result = $this->_connexion($server, $login, $password, $port, $path);
		if ($result === true)
		{
			return ;
		}

		switch ($result)
		{
			case File::FILE_CANT_CONNECT_SERVER :
				Display::message(sprintf(Fsb::$session->lang('file_cant_connect_server'), $server, $port));
			break;

			case File::FILE_CANT_AUTHENTIFICATE :
				Display::message(sprintf(Fsb::$session->lang('file_cant_authentificate'), $login));
			break;

			case File::FILE_CANT_CHDIR :
				Display::message(sprintf(Fsb::$session->lang('file_cant_chdir'), $path));
			break;

			case File::FILE_FSOCKOPEN_DISABLED :
				Display::message('file_fsockopen_disabled');
			break;

			case File::FILE_FTP_EXTENSION_DISABLED :
				Display::message('file_ftp_extension_disabled');
			break;
		}
	}

	/**
	 * Change les droits d'un fichier
	 *
	 * @param string $file Nom du fichier
	 * @param int $mode Mode du chmod
	 * @param bool $debug Debugage du CHMOD ?
	 */
	public function chmod($file, $mode, $debug = true)
	{
		$result = $this->_chmod($this->root_path . $file, $mode);
		if (!$result && $debug)
		{
			Display::message(sprintf(Fsb::$session->lang('file_cant_chmod'), $this->root_path . $file));
		}
	}

	/**
	 * Ecrit des donnees dans un fichier
	 *
	 * @param string $filename Nom du fichier
	 * @param string $content Contenu a ecrire
	 * @return bool
	 */
	public function write($filename, $content)
	{
		// Nom du fichier
		$tmp = $this->uniq_name('upload/', 'class_file_');

		// On ecrit le fichier
		if (!is_writable($this->local_path . 'upload'))
		{
			$this->chmod('upload/', 0777, false);
		}

		$fd = fopen($this->local_path . $tmp, 'w');
		if (!$fd)
		{
			Display::message('file_cant_create_tmp');
		}
		fwrite($fd, $content);
		fclose($fd);
		@chmod($this->local_path . $tmp, 0666);

		// On remplace le fichier de destination par le fichier temporaire qu'on vient de faire
		$this->mkdir(dirname($filename));
		$this->copy($tmp, $filename);

		// On supprime le fichier cree une fois le transfert effectue
		@unlink($this->local_path . $tmp);

		return (true);
	}

	/**
	 * Copie un fichier ailleurs
	 *
	 * @param unknown_type $src Fichier source
	 * @param unknown_type $dst Fichier destination
	 */
	public function copy($src, $dst)
	{
		//$this->_unlink($dst);
		if (is_dir($this->local_path . $src))
		{
			if ($src[strlen($src) - 1] != '/')
			{
				$src .= '/';
			}

			if ($dst[strlen($dst) - 1] != '/')
			{
				$dst .= '/';
			}

			$fd = opendir($this->local_path . $src);
			while ($file = readdir($fd))
			{
				if ($file[0] != '.')
				{
					$this->copy($src . $file, $dst . $file);
				}
			}
			closedir($fd);
		}
		else
		{
			if (!$this->_put($this->root_path . $src, $this->root_path . $dst))
			{
				Display::message(sprintf(Fsb::$session->lang('file_cant_put_file'), $src, $dst));
			}

			if (!is_writable($this->local_path . $dst))
			{
				$this->chmod($dst, 0666);
			}
		}
	}

	/**
	 * Cree un repertoire et ses repertoires parents s'ils n'existent pas
	 *
	 * @param string $dir Nom du repertoire
	 */
	public function mkdir($dir)
	{
		if (is_dir($dir))
		{
			return ;
		}

		$dirs = explode('/', $dir);
		$path = './';
		foreach ($dirs AS $cur)
		{
			$path .= $cur . '/';
			if (!file_exists($this->local_path . $path) && $cur != '.' && $cur != '..')
			{
				$result = $this->_mkdir($this->root_path . $path);
				if (!$result)
				{
					Display::message(sprintf(Fsb::$session->lang('file_cant_mkdir'), $this->local_root . $path));
				}
				$this->chmod($path, 0777);
			}
		}
	}

	/**
	 * Supprime un repertoire
	 *
	 * @param string $dir Nom du repertoire
	 */
	public function rmdir($dir)
	{
		if (!$this->_rmdir($this->root_path . $dir))
		{
			Display::message(sprintf(Fsb::$session->lang('file_cant_remove_dir'), $this->local_path . $dir));
		}
	}

	/**
	 * Cree un nom de fichier temporaire
	 *
	 * @param string $dir Nom du dossier
	 * @param string $prefix Prefixe du fichier
	 * @return string
	 */
	protected function uniq_name($dir, $prefix)
	{
		do
		{
			$filename = $dir . $prefix . md5(rand(0, time()));
		}
		while (file_exists($this->local_path . $filename));
		return ($filename);
	}

	/**
	 * Change le repertoire courant
	 *
	 * @param string $path Repertoire de destination
	 * @return bool
	 */
	public function chdir($path)
	{
		return ($this->_chdir($this->root_path . $path));
	}

	/**
	 * Renomme un fichier
	 *
	 * @param string $from Nom du fichier d'origine
	 * @param string $to Nom du nouveau fichier
	 * @return bool
	 */
	public function rename($from, $to)
	{
		return ($this->_rename($this->root_path . $from, $this->root_path . $to));
	}

	/**
	 * Supprime un fichier
	 *
	 * @param string $filename Nom du fichier a supprimer
	 * @return bool
	 */
	public function unlink($filename)
	{
		if (is_dir($this->local_path . $filename))
		{
			$fd = opendir($this->local_path . $filename);
			while ($file = readdir($fd))
			{
				if ($file != '.' && $file != '..')
				{
					$this->unlink($filename . '/' . $file);
				}
			}
			closedir($fd);

			return ($this->_rmdir($this->root_path . $filename));
		}
		else
		{
			return ($this->_unlink($this->root_path . $filename));
		}
	}

	/**
	 * Destructeur, ferme la connexion
	 *
	 */
	public function __destruct()
	{
		$this->_close();
	}
}

/* EOF */