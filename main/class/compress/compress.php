<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/compress/compress.php
** | Begin :	08/09/2006
** | Last :		21/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Classe de compression / decompression de donnees. Les algorithmes supportes sont .zip, .tar, .tar.gz
** L'utilisation est tres simple. Pour une creation d'archive ZIP :
**		$compress = new Compress('archive.zip');
**		$compress->add_file('dir/file.txt');
**		$compress->add_file('dir/file2.txt');
**		$compress->write();
**
** Pour une decompression d'archive :
**		$compress = new Compress('archive.zip');
**		$compress->extract($dst_path);
**
** Veuillez noter qu'il est recommander d'utiliser un objet File() en second parametre du constructeur. Cet objet
** permettra de gerer automatiquement les droits des dossiers et veillera au bon deroulement de l'ecriture
** des fichiers. Si vous ne passez pas cet objet en second argument, la classe instanciera elle meme
** un objet File() pour les fichiers locaux. (Il est donc necessaire d'avoir le fichier ~/main/class/class_file.php)
**
** La classe compress_tar() a ete reprise du logiciel libre phpBB3 (www.phpbb.com) et a ete fortement modifiee pour les
** besoins du forum.
** Les classes zipfile() et SimpleUnzip() ont ete reprises telles quelles du logiciel libre phpMyAdmin (www.phpmyadmin.net)
**
*/
class Compress extends Fsb_model
{
	// Methode de compression
	public $method = 'zip';

	// Objet courant pour la compression
	private $obj;

	// Objet d'ecriture de fichier File()
	public $file;

	// Fichier pour l'archive
	private $filename = '';

	// Buffer a ecrire
	private $buffer = '';

	/*
	** CONSTRUCTEUR
	** Recupere les donnees sur le fichier qu'on veut creer / extraire
	** -----
	** $filenamme ::	Fichier archive
	** $file ::			Objet d'ecriture de fichier
	*/
	public function __construct($filename, $file = NULL)
	{
		$this->file = $file;
		if (!$this->file)
		{
			$this->file = new File_local();
			$this->file->connexion('', '', '', '', ROOT);
		}

		// Informations sur l'archive (methode de compression)
		$this->filename = $filename;
		if (preg_match('#\.zip$#i', $this->filename))
		{
			$this->method = 'zip';
			$this->obj = Compress_zip::factory('zip');
		}
		else if (preg_match('#\.tar$#i', $this->filename))
		{
			$this->method = 'tar';
			$this->obj = new Compress_tar($this->filename, '.tar');
		}
		else if (preg_match('#\.tar.gz$#i', $this->filename))
		{
			$this->method = 'tar.gz';
			$this->obj = new Compress_tar($this->filename, '.tar.gz');
		}
	}

	/*
	** Ajoute un fichier a l'archive
	** -----
	** $filename ::		Nom du fichier a ajouter a l'archive
	** $remove ::		Supprime un repertoire du chemin
	** $add ::			Ajoute un repertoire devant le chemin
	*/
	public function add_file($filename, $remove = '', $add = '')
	{
		if (is_dir(ROOT . $filename))
		{
			// On ajoute - si necessaire - un / a la fin du nom de fichier
			if ($filename[strlen($filename) - 1] != '/')
			{
				$filename .= '/';
			}

			$fd = opendir(ROOT . $filename);
			while ($file = readdir($fd))
			{
				if ($file[0] != '.')
				{
					$this->add_file($filename . $file, $remove);
				}
			}
			closedir($fd);
		}
		else
		{
			$falsepath = ($remove) ? preg_replace('#^' . preg_quote($remove, '#') . '#i', '', $filename) : $filename;
			$falsepath = ($add) ? $add . $falsepath : $falsepath;
			switch ($this->method)
			{
				case 'zip' :
					$this->obj->addFile($filename, $falsepath);
				break;

				case 'tar' :
				case 'tar.gz' :
					$this->buffer .= $this->obj->data($filename, $falsepath);
				break;
			}
		}
	}

	/*
	** Ecrit l'archive
	*/
	public function write($return = FALSE)
	{
		// Contenu de l'archive
		switch ($this->method)
		{
			case 'zip' :
				$content = $this->obj->file();
			break;

			case 'tar' :
			case 'tar.gz' :
				$content = $this->buffer;
			break;
		}

		if (!$return)
		{
			// Ecriture du fichier
			$this->file->write($this->filename, $content);
		}
		else
		{
			return ($content);
		}
	}

	/*
	** Extraction de l'archive
	** -----
	** $path ::		Dossier de destination
	** $remove ::	Supprime un repertoire du chemin
	*/
	public function extract($path = './', $remove = '')
	{
		// Extraction des fichiers
		switch ($this->method)
		{
			case 'zip' :
				$unzip = Compress_zip::factory('unzip', ROOT . $this->filename);
				foreach ($unzip->Entries AS $info)
				{
					$filename = $info->Path . '/' . $info->Name;
					$falsepath = ($remove) ? preg_replace('#^' . preg_quote($remove, '#') . '#i', '', $filename) : $filename;
					$this->file->write($path . $falsepath, $info->Data);
				}
			break;

			case 'tar' :
			case 'tar.gz' :
				$this->obj->extract($this->filename);
				foreach ($this->obj->Entries AS $info)
				{
					$filename = $info['filename'];
					$falsepath = ($remove) ? preg_replace('#^' . preg_quote($remove, '#') . '#i', '', $filename) : $filename;
					$this->file->write($path . $falsepath, $info['data']);
				}
			break;
		}
	}
}

/* EOF */