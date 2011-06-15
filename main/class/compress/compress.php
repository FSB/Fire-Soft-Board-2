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
 * Classe de compression / decompression d'archives. Les formats supportes sont ZIP, TAR et GZIP.
 * L'utilisation de la classe est simple, pour creer une archive :
 * <code>
 *	$compress = new Compress('archive.zip');
 *	$compress->add_file('dir/file.txt');
 *	$compress->add_file('dir/file2.txt');
 *	$compress->write();
 * </code>
 * 
 * Pour decompresser une archive :
 * <code>
 *	$compress = new Compress('archive.zip');
 *	$compress->extract($dst_path);
 * </code>
 * 
 * Important : il est recommander d'utiliser un objet File() en second parametre du constructeur. Cet objet
 * permettra de gerer automatiquement les droits des dossiers et veillera au bon deroulement de l'ecriture
 * des fichiers. Si vous ne passez pas cet objet en second argument, la classe instanciera elle meme
 * un objet File() pour les fichiers locaux. (Il est donc necessaire d'avoir le fichier ~/main/class/class_file.php)
 *
 */
class Compress extends Fsb_model
{
	/**
	 * Methode de compression
	 *
	 * @var string
	 */
	public $method = 'zip';

	/**
	 * Objet courant pour la compression
	 *
	 * @var mixed
	 */
	private $obj;

	/**
	 * Objet File
	 *
	 * @var File
	 */
	public $file;

	/**
	 * Fichier pour l'archive
	 *
	 * @var string
	 */
	private $filename = '';

	/**
	 * Buffer a ecrire
	 *
	 * @var string
	 */
	private $buffer = '';

	/**
	 * Constructeur, Recupere les donnees sur le fichier qu'on veut creer / extraire
	 *
	 * @param string $filename Fichier archive
	 * @param File $file
	 */
	public function __construct($filename, $file = null)
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
		else if (preg_match('#\.tar.gz$#i', $this->filename) || preg_match('#\.tgz$#i', $this->filename))
		{
			$this->method = 'tar.gz';
			$this->obj = new Compress_tar($this->filename, '.tar.gz');
		}
	}

	/**
	 * Ajoute un fichier a l'archive
	 *
	 * @param string $filename Nom du fichier a ajouter a l'archive
	 * @param string $remove Supprime un repertoire du chemin
	 * @param string $add Ajoute un repertoire devant le chemin
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

	/**
	 * Genere l'archive
	 *
	 * @param bool $return Si true, ecrit le fichier, si false, retourne le contenu
	 * @return mixed Contenu du fichier si $return vaut true
	 */
	public function write($return = false)
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

	/**
	 * Extraction de l'archive
	 *
	 * @param string $path Dossier de destination
	 * @param string $remove Supprime un repertoire du chemin
	 */
	public function extract($path = './', $remove = '')
	{
		// Extraction des fichiers
		$toclean = explode(',', FSB_TOCLEAN_FILES);
		switch ($this->method)
		{
			case 'zip' :
				$unzip = Compress_zip::factory('unzip', ROOT . $this->filename);
				foreach ($unzip->Entries as $info)
				{
					if (in_array($info->Name, $toclean))
					{
						continue;
					}

					$filename = $info->Path . '/' . $info->Name;
					$falsepath = ($remove) ? preg_replace('#^' . preg_quote($remove, '#') . '#i', '', $filename) : $filename;
					$this->file->write($path . $falsepath, $info->Data);
				}
			break;

			case 'tar' :
			case 'tar.gz' :
				$this->obj->extract($this->filename);
				foreach ($this->obj->Entries as $info)
				{
					// XXX tocleanup this is ugly!
					$name = explode('/', $info['filename']);
					$name = $name[ count($name) -1 ];
					if (in_array($name, $toclean))
					{
						continue;
					}

					$filename = $info['filename'];
					$falsepath = ($remove) ? preg_replace('#^' . preg_quote($remove, '#') . '#i', '', $filename) : $filename;
					$this->file->write($path . $falsepath, $info['data']);
				}
			break;
		}
	}
}

/* EOF */