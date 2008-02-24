<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/file/file_ftp.php
** | Begin :	12/07/2007
** | Last :		13/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Methode FTP
*/
class File_ftp extends File
{
	// Ressource de connexion au serveur FTP
	private $stream;

	// Methode
	public $method = 'ftp';

	/*
	** Connnexion au serveur
	** -----
	** $server ::		Adresse du serveur
	** $login ::		Login
	** $password ::		Mot de passe
	** $port ::			Port
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
		if ($this->stream === FALSE)
		{
			return (File::FILE_CANT_CONNECT_SERVER);
		}

		// Authentification
		if (!ftp_login($this->stream, $login, $password))
		{
			return (File::FILE_CANT_AUTHENTIFICATE);
		}

		// On passe en mode passif (le client ecoute la connexion)
		ftp_pasv($this->stream, TRUE);

		$this->root_path = './';
		$this->local_path = ROOT;

		// On se deplace a la racine du forum
		if (!$this->_chdir($path))
		{
			return (File::FILE_CANT_CHDIR);
		}
		return (TRUE);
	}

	/*
	** Change de repertoire courant
	** -----
	** $path ::		Nouveau repertoire courant
	*/
	protected function _chdir($path)
	{
		return (@ftp_chdir($this->stream, $path));
	}

	/*
	** Renomme un fichier
	** -----
	** $from ::		Nom du fichier d'origine
	** $to ::		Nom du fichier de destination
	*/
	protected function _rename($from, $to)
	{
		return (@ftp_rename($this->stream, $from, $to));
	}

	/*
	** Change les droits d'un fichier
	** -----
	** $file ::		Nom du fichier
	** $mode ::		Mode du chmod
	*/
	protected function _chmod($file, $mode)
	{
		return (@ftp_chmod($this->stream, $mode, $file));
	}

	/*
	** Copie un fichier vers une destination
	** -----
	** $src ::		Fichier source
	** $dst ::		Fichier destination
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

	/*
	** Supprime un fichier
	** -----
	** $filename ::		Nom du fichier a supprimer
	*/
	protected function _unlink($filename)
	{
		ftp_delete($this->stream, $filename);
	}

	/*
	** Cree un repertoire
	** -----
	** $dir ::		Nom du repertoire
	*/
	protected function _mkdir($dir)
	{
		return (ftp_mkdir($this->stream, $dir));
	}

	/*
	** Supprime un repertoire
	** -----
	** $dir ::		Nom du repertoire
	*/
	protected function _rmdir($dir)
	{
		return (ftp_rmdir($this->stream, $dir));
	}

	/*
	** Ferme la connexion
	*/
	protected function _close()
	{
		ftp_close($this->stream);
	}
}

/* EOF */