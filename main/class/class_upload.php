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
 * Permet l'upload de fichiers
 */
class Upload extends Fsb_model
{
	/**
	 * Nom du champ de formulaire d'upload
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Chemin temporaire du fichier
	 *
	 * @var string
	 */
	protected $tmp_path;
	
	/**
	 * Taille du fichier
	 *
	 * @var int
	 */
	public $filesize;
	
	/**
	 * Nom du fichier
	 *
	 * @var string
	 */
	public $filename;

	/**
	 * Nom du fichier sans le repertoire
	 *
	 * @var string
	 */
	public $basename;
	
	/**
	 * Extension du fichier
	 *
	 * @var string
	 */
	public $extension;
	
	/**
	 * Type mime du fichier
	 *
	 * @var string
	 */
	public $mimetype;

	/**
	 * Si le fichier doit etre renomme (preciser son nom)
	 *
	 * @var string
	 */
	public $rename_basename = null;
	
	/**
	 * Si l'extension du fichier doit etre renommee (preciser son nom)
	 *
	 * @var string
	 */
	public $rename_extension = null;

	/**
	 * Largeur de l'image
	 *
	 * @var int
	 */
	public $width;
	
	/**
	 * Hauteur de l'image
	 *
	 * @var int
	 */
	public $height;
	
	/**
	 * Type de l'image
	 *
	 * @var int
	 */
	public $type;

	/**
	 * Si seules les images sont autorisees
	 *
	 * @var bool
	 */
	protected $only_img = false;
	
	/**
	 * S'il s'agit d'une image
	 *
	 * @var bool
	 */
	public $is_img = false;
	
	/**
	 * Extensions des images supportees
	 *
	 * @var array
	 */
	public static $img = array(
		'gif',
		'jpg',
		'jpeg',
		'png',
		'bmp'
	);
	
	/**
	 * Liste des types d'image supportes
	 *
	 * @var array
	 */
	public static $imgtype = array(
		IMAGETYPE_GIF,
		IMAGETYPE_JPEG,
		IMAGETYPE_JPEG,
		IMAGETYPE_BMP,
		IMAGETYPE_PNG,
	);

	/**
	 * Extensions autorisees
	 *
	 * @var array
	 */
	protected $allowed_ext = array();

	/**
	 * Constructeur, verifie le fichier uploade
	 *
	 * @param string $name Nom du champ du formulaire d'upload
	 */
	public function __construct($name)
	{
		$this->name = $name;

		if (!isset($_FILES[$this->name]))
		{
			trigger_error("La variable \$_FILES['$this->name'] n'existe pas", FSB_ERROR);
		}
		
		// Verifie les erreurs d'upload
		if ($_FILES[$this->name]['error'])
		{
			switch ($_FILES[$this->name]['error'])
			{
				case UPLOAD_ERR_INI_SIZE :
					$ini_size = ini_get_bytes(ini_get('upload_max_filesize'));
					Display::message(sprintf(Fsb::$session->lang('upload_err_ini_size'), convert_size($ini_size)));
				break;
				
				case UPLOAD_ERR_FORM_SIZE :
					Display::message('upload_err_form_size');
				break;
				
				case UPLOAD_ERR_PARTIAL :
					Display::message('upload_err_partial');
				break;
				
				case UPLOAD_ERR_NO_FILE :
					Display::message('upload_err_no_file');
				break;
				
				case UPLOAD_ERR_NO_TMP_DIR :
					Display::message('upload_err_no_tmp_dir');
				break;
				
				case UPLOAD_ERR_CANT_WRITE :
					Display::message('upload_err_cant_write');
				break;

				case UPLOAD_ERR_EXTENSION :
					Display::message('upload_err_extension');
				break;
			}
		}

		$this->tmp_path =	$_FILES[$this->name]['tmp_name'];
		$this->filename =	$_FILES[$this->name]['name'];
		$this->mimetype =	$_FILES[$this->name]['type'];
		$this->filesize =	filesize($this->tmp_path);
		$this->basename =	get_file_data($this->filename, 'filename');
		$this->extension =	get_file_data($this->filename, 'extension');
	}

	/**
	 * Ajoute une ou plusieurs extensions autorisees
	 *
	 * @param string|array|bool $ext Liste des extensions a autoriser. True pour tout autoriser
	 */
	public function allow_ext($ext)
	{
		if ($ext === true)
		{
			$this->allowed_ext = true;
		}
		else if (is_array($ext))
		{
			$this->allowed_ext = array_merge($this->allowed_ext, $ext);
		}
		else
		{
			$this->allowed_ext[] = $ext;
		}
	}

	/**
	 * N'autorise que les images
	 *
	 * @param bool $bool True pour n'autoriser que les images
	 */
	public function only_img($bool = true)
	{
		$this->allow_ext(self::$img);
		$this->only_img = $bool;

		// Recuperation des informations sur l'image, et verification de l'integrite de l'image avec un getimagesize()
		list($this->width, $this->height, $this->type) = @getimagesize($this->tmp_path);
		if (!in_array($this->type, self::$imgtype))
		{
			Display::message('L\'image ' . $this->filename . ' contient des erreurs et ne peut pas etre uploadee sur le forum');
		}
	}

	/**
	 * Verification des dimensions de l'image
	 *
	 * @param int $width Largeur max de l'image
	 * @param int $height Hauteur max de l'image
	 * @param int $filesize Taille max de l'image (en octet)
	 * @return bool
	 */
	public function check_img_size($width, $height, $filesize)
	{
		if (($width > 0 && $this->width > $width) || ($height > 0 && $this->height > $height) || ($filesize > 0 && $this->filesize > $filesize))
		{
			return (false);
		}
		return (true);
	}

	/**
	 * Renomme la partie avant l'extension du fichier
	 *
	 * @param string $new Nouveau nom
	 */
	public function rename_filename($new)
	{
		$this->rename_basename = $new;
	}

	/**
	 * Renomme l'extension
	 *
	 * @param string $new Nouveau nom
	 */
	public function rename_extension($new)
	{
		$this->rename_extension = $new;
	}

	/**
	 * Upload et sauve l'image sur le forum
	 *
	 * @param string $path Dossier de destination
	 * @param bool $erase Si  true  ecrase le fichier, si false renomme le fichier s'il existe
	 * @return string Nom du fichier
	 */
	public function store($path, $erase = false)
	{
		// Verification de l'extension
		if ($this->allowed_ext !== true && !in_array(strtolower($this->extension), $this->allowed_ext))
		{
			Display::message(sprintf(Fsb::$session->lang('bad_extension'), $this->extension, implode(', ', $this->allowed_ext)));
		}

		// On verifie si le fichier n'existe pas deja, si c'est le cas on le renomme
		$extension =	($this->rename_extension) ? $this->rename_extension : $this->extension;
		$basename =		($this->rename_basename) ? $this->rename_basename : $this->basename;
		if (!$erase)
		{
			$i = 1;
			$base = $basename;
			while (file_exists($path . $base . '.' . $extension))
			{
				$base = $basename . $i;
				$i++;
			}
			$this->filename = $base . '.' . $extension;
		}

		// Upload du fichier
		if (!(is_uploaded_file($this->tmp_path) && is_writable($path) && move_uploaded_file($this->tmp_path, $path . $this->filename)))
		{
			Display::message(sprintf(Fsb::$session->lang('unable_upload_file'), $this->tmp_path, $path . $this->filename));
		}
		@chmod($path . $this->filename, 0644);

		// S'il s'agit d'une image, on verifie qu'elle ne contient pas de PHP
		if ($this->only_img)
		{
			if (strpos(file_get_contents($path . $this->filename), '<?php'))
			{
				Display::message('Tentative de hack, l\'image ne peut etre uploadee sur le forum');
			}
		}

		return ($this->filename);
	}

	/**
	 * Upload de fichier joint pour le forum
	 *
	 * @param int $user_id ID du membre
	 * @param int $upload_auth Droit sur le fichier
	 * @return int ID du fichier joint
	 */
	public function attach_file($user_id, $upload_auth)
	{
		// On recupere la taille prise par tous les fichiers uploade du membre, afin de determine le quota restant
		$sql = 'SELECT SUM(upload_filesize) AS total_filesize
				FROM ' . SQL_PREFIX . 'upload
				WHERE u_id = ' . $user_id;
		$result = Fsb::$db->query($sql);
		$row = Fsb::$db->row($result);
		Fsb::$db->free($result);
		$total_filesize = $row['total_filesize'];
		
		// Liste des extensions autorisees
		$this->allow_ext(explode(',', Fsb::$cfg->get('upload_extensions')));

		// Quota depasse ?
		if (!Fsb::$session->is_authorized('upload_quota_unlimited') && ($this->filesize + $total_filesize) > Fsb::$cfg->get('upload_quota'))
		{
			Display::message(sprintf(Fsb::$session->lang('post_upload_quota'), convert_size($total_filesize), convert_size(Fsb::$cfg->get('upload_quota'))));
		}

		// Taille maximale possible pour le fichier
		if (Fsb::$session->is_authorized('upload_quota_unlimited'))
		{
			$max_size = Fsb::$cfg->get('upload_max_filesize');
		}
		else
		{
			$max_size = min(Fsb::$cfg->get('upload_quota') - $total_filesize, Fsb::$cfg->get('upload_max_filesize'));
		}

		if (!$this->check_img_size(0, 0,  $max_size))
		{
			Display::message(sprintf(Fsb::$session->lang('size_too_big'), convert_size($this->filesize), convert_size($max_size)));
		}

		// Upload du fichier sur le serveur
		$this->rename_filename(md5($this->filename));
		$this->rename_extension('file');
		$filename = $this->store(ROOT . 'upload/');

		// On verifie si le fichier est une image
		$img_data = @getimagesize(ROOT . 'upload/' . $filename);
		if ($img_data && in_array($img_data[2], self::$imgtype))
		{
			$this->is_img = true;
			$this->mimetype = image_type_to_mime_type($img_data[2]);
		}
		else
		{
			$this->mimetype = 'application/octetstream';
		}

		// Ajout du fichier uploade dans la base de donnee
		Fsb::$db->insert('upload', array(
			'u_id' =>				$user_id,
			'upload_filename' =>	$filename,
			'upload_realname' =>	$_FILES[$this->name]['name'],
			'upload_filesize' =>	filesize(ROOT . 'upload/' . $filename),
			'upload_mimetype' =>	$this->mimetype,
			'upload_time' =>		CURRENT_TIME,
			'upload_auth' =>		$upload_auth,
		));

		return (Fsb::$db->last_id());
	}
}

/* EOF */