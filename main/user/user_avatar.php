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
 * On affiche le module
 * 
 * @var bool
 */
$show_this_module = true;

/**
 * Module d'utilisateur permettant d'avoir un avatar de differentes facons :
 *	- Par upload depuis le PC
 *	- Par upload depuis une image distante (necessite la librairie GD)
 *	- Par lien depuis une URL distante
 *	- En choisissant un avatar depuis la gallerie du forum
 */
class Page_user_avatar extends Fsb_model
{
	/**
	 * Definit si l'utilisateur peut utiliser ou non un avatar
	 *
	 * @var bool
	 */
	public $can_use_avatar = false;
	
	/**
	 * Définit si l'utilisateur peut uploader un avatar
	 *
	 * @var bool
	 */
	public $can_upload_avatar = false;
	
	/**
	 * Définit si la gallerie d'avatar est activé
	 *
	 * @var bool
	 */
	public $can_use_gallery = false;
	
	/**
	 * Définit si deux utilisateurs peuvent avoir le même avatar
	 *
	 * @var bool
	 */
	public $can_have_same_avatar = false;

	/**
	 * Doit redimensionner ?
	 *
	 * @var bool
	 */
	public $need_resize = false;

	/**
	 * Gallerie d'avatar sellectionnee
	 *
	 * @var string
	 */
	public $gallery = '';
	
	/**
	 * Nombre d'avatar par ligne
	 *
	 * @var int
	 */
	public $avatar_per_line = 3;

	/**
	 * Lien vers l'avatar
	 *
	 * @var string
	 */
	public $value_avatar_link = '';
	
	/**
	 * Avatar uploader
	 *
	 * @var string
	 */
	public $value_avatar_upload = '';
	
	/**
	 * Lien de l'avatar à uploader
	 *
	 * @var string
	 */
	public $value_avatar_link_upload = '';
	
	/**
	 * Avater sélectionner dans la gallerie
	 *
	 * @var string
	 */
	public $value_avatar_gallery = '';

	/**
	 * Erreurs
	 * 
	 * @var array
	 */
	public $errstr = array();

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		// On verifie si le membre peut utiliser un avatar
		$this->can_use_avatar =			(Fsb::$session->data['u_can_use_avatar'] && Fsb::$cfg->get('avatar_can_use')) ? true : false;
		$this->can_upload_avatar =		($this->can_use_avatar && Fsb::$cfg->get('avatar_can_upload')) ? true : false;
		$this->can_use_gallery =		($this->can_use_avatar && Fsb::$cfg->get('avatar_can_use_gallery')) ? true : false;
		$this->can_have_same_avatar =	($this->can_use_avatar && Fsb::$cfg->get('avatar_can_same')) ? true : false;
	
		if (!$this->can_use_gallery)
		{
			$this->gallery = null;
		}
		else if (is_dir(AVATAR_PATH . 'gallery'))
		{
			$this->gallery = htmlspecialchars(Http::request('gallery'));
		}

		if (Http::request('submit', 'post'))
		{
			$this->check_form();
			if (!count($this->errstr))
			{
				$this->submit_form();
			}
		}
		else if (Http::request('delete', 'post'))
		{
			$this->delete_avatar();
		}
		$this->avatar_form();
	}

	/**
	 * Affiche le formulaire permettant de choisir son avatar
	 */
	public function avatar_form()
	{
		if (count($this->errstr))
		{
			Fsb::$tpl->set_switch('error');
		}

		if ($this->can_use_avatar)
		{
			Fsb::$tpl->set_switch('can_use_avatar');
		}

		if ($this->can_upload_avatar)
		{
			Fsb::$tpl->set_switch('can_upload_avatar');
		}

		$u_avatar = '';
		if (!empty(Fsb::$session->data['u_avatar']))
		{
			$u_avatar = User::get_avatar(Fsb::$session->data['u_avatar'], Fsb::$session->data['u_avatar_method'], Fsb::$session->data['u_can_use_avatar']);
			Fsb::$tpl->set_switch('delete_avatar');
		}

		// Affiche la gallerie d'avatar
		if (!empty($this->gallery) && is_dir(AVATAR_PATH . 'gallery/' . $this->gallery))
		{
			$this->show_gallery($this->gallery);
		}

		Fsb::$tpl->set_file('user/user_avatar.html');

		Fsb::$tpl->set_vars(array(
			'U_AVATAR' =>			$u_avatar,
			'AVATAR_EXPLAIN' =>		sprintf(Fsb::$session->lang('user_avatar_explain'), Fsb::$cfg->get('avatar_width'), Fsb::$cfg->get('avatar_height'), convert_size(Fsb::$cfg->get('avatar_weight'))),
			'CONTENT' =>			Html::make_errstr($this->errstr),
			'LIST_GALLERY' =>		Html::list_dir('gallery', $this->gallery, AVATAR_PATH . 'gallery/', array(), true, '<option value="0">---</option>', 'onchange="location.href=\'' . sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=avatar&amp;gallery=\' + this.value') . '"'),
			'PER_LINE' =>			$this->avatar_per_line,
		));

		// On regarde s'il y a au moins un dossier dans la gallerie d'avatar pour l'afficher
		if ($this->can_use_gallery)
		{
			$fd = opendir(AVATAR_PATH . 'gallery/');
			while ($file = readdir($fd))
			{
				if ($file[0] != '.' && is_dir(AVATAR_PATH . 'gallery/' . $file))
				{
					Fsb::$tpl->set_switch('can_use_gallery');
					break;
				}
			}
			closedir($fd);
		}
	}
	
	/**
	 * Affiche la gallerie d'avatar
	 *
	 * @param string $name Nom de la gallerie a afficher
	 */
	public function show_gallery($name)
	{
		// Si deux membres ne peuvent pas avoir le meme avatar dans la gallerie, alors
		// on recupere les avatars indisponibles
		$block_avatar = array();
		if (!$this->can_have_same_avatar)
		{
			$sql = 'SELECT u_avatar FROM ' . SQL_PREFIX . 'users
						WHERE u_avatar_method = ' . AVATAR_METHOD_GALLERY . '
							AND u_id <> ' . Fsb::$session->id();
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				$block_avatar[] = $row['u_avatar'];
			}
			Fsb::$db->free($result);
		}

		Fsb::$tpl->set_switch('show_gallery');

		$allowed_ext = Upload::$img;

		$fd = opendir(AVATAR_PATH . 'gallery/' . $name);
		while ($file = readdir($fd))
		{
			$img_data = explode('.', $file);
			$ext = strtolower($img_data[count($img_data) - 1]);
			if ($file[0] != '.' && in_array($ext, $allowed_ext))
			{
				Fsb::$tpl->set_blocks('avatar', array(
					'IS_SELECTED' =>	(in_array($this->gallery . '/' . $file, $block_avatar)) ? true : false,
					'IMG_NAME' =>		$this->gallery . '/' . $file,
					'IMG' =>			AVATAR_PATH . 'gallery/' . $this->gallery . '/' . $file,
				));
			}
		}
		closedir($fd);
	}

	/**
	 * Verifie les donnees envoyees par le formulaire
	 */
	public function check_form()
	{
		if (!$this->can_use_avatar)
		{
			$this->errstr[] = Fsb::$session->lang('cant_use_avatar');
			return ;
		}

		if ((isset($_FILES['upload_avatar']) || Http::request('link_upload_avatar', 'post')) && !$this->can_upload_avatar)
		{
			$this->errstr[] = Fsb::$session->lang('cant_upload_avatar');
			return ;
		}

		// On verifie si le membre peut utiliser la gallerie, et si l'avatar n'a pas deja
		// ete selelctionne
		if (Http::request('avatar_from_gallery', 'post'))
		{
			if (!$this->can_use_gallery)
			{
				$this->errstr[] = Fsb::$session->lang('cant_use_gallery');
			}
			else if (!$this->can_have_same_avatar)
			{
				$sql = 'SELECT u_avatar
						FROM ' . SQL_PREFIX . 'users
						WHERE u_avatar_method = ' . AVATAR_METHOD_GALLERY . '
							AND u_avatar = \'' . Fsb::$db->escape(Http::request('avatar_from_gallery', 'post')) . '\'
							AND u_id <> ' . Fsb::$session->id() . '
						LIMIT 1';
				$result = Fsb::$db->query($sql);
				$row = Fsb::$db->row($result);
				Fsb::$db->free($result);

				if (!empty($row['u_avatar']))
				{
					$this->errstr[] = Fsb::$session->lang('cant_use_same_avatar');
					return ;
				}
			}
		}
		
		// Upload depuis le PC
		$method = '';
		if (isset($_FILES['upload_avatar']) && !empty($_FILES['upload_avatar']['name']))
		{
			$file_path = $_FILES['upload_avatar']['tmp_name'];
			$method = 'upload';
		}
		// Upload depuis une URL
		else if ($link_upload_avatar = Http::request('link_upload_avatar', 'post'))
		{
			$file_path = $link_upload_avatar;
			$method = 'upload';
		}
		// Lier un avatar depuis une URL
		else if ($link_avatar = Http::request('link_avatar', 'post'))
		{
			if (!preg_match('#^(http|https|ftp)://(.*?\.)*?[a-z0-9\-]+?\.[a-z]{2,4}:?([0-9]*?).*?\.(gif|jpg|jpeg|png)$#i', $link_avatar))
			{
				$this->errstr[] = Fsb::$session->lang('avatar_invalid_url');
				return ;
			}
			$file_path = $link_avatar;
			$method = 'link';
		}

		if ($method)
		{
			$gd = new Gd();
			if ($method == 'upload' && $gd->need_resize($file_path, Fsb::$cfg->get('avatar_width'), Fsb::$cfg->get('avatar_height')))
			{
				if ($gd->loaded)
				{
					$this->need_resize = true;
					return ;
				}
				else
				{
					$this->errstr[] = sprintf(Fsb::$session->lang('avatar_height_width_error'), Fsb::$cfg->get('avatar_width'), Fsb::$cfg->get('avatar_height'));
					return ;
				}
			}

			// Taille maximale depassee ?
			$file_size = @filesize($file_path);
			if ($file_size > Fsb::$cfg->get('avatar_weight'))
			{
				$this->errstr[] = sprintf(Fsb::$session->lang('avatar_max_size_error'), Fsb::$cfg->get('avatar_weight'));
				return ;
			}
		}
	}
	
	/**
	 * Traite et soumet les donnees envoyees par le formulaire
	 */
	public function submit_form()
	{
		$this->delete_matches_file(AVATAR_PATH, md5(Fsb::$session->id()));

		// Upload depuis le PC
		if (isset($_FILES['upload_avatar']) && !empty($_FILES['upload_avatar']['name']))
		{
			$upload = new Upload('upload_avatar');
			$upload->only_img();

			// On renomme l'avatar en prenant un hash MD5 de l'ID du membre, et en ajoutant le timestamp
			// pour rafraichir le cache des navigateurs un peu lent
			$upload->rename_filename(md5(Fsb::$session->id()) . CURRENT_TIME);

			$avatar_name = $upload->store(AVATAR_PATH);
			$method = AVATAR_METHOD_UPLOAD;
		}
		// Upload depuis une URL
		else if (Http::request('link_upload_avatar', 'post'))
		{
			$name =			Http::request('link_upload_avatar', 'post');
			$img_content =	@file_get_contents($name);
			$ext =			get_file_data($name, 'extension');

			$avatar_name = md5(Fsb::$session->id()) . CURRENT_TIME . '.' . $ext;
			if (!$img_content || !fsb_write(AVATAR_PATH . $avatar_name, $img_content))
			{
				Display::message('user_avatar_unable_upload');
			}

			$method = AVATAR_METHOD_UPLOAD;
		}
		// Avatar depuis la gallerie
		else if (Http::request('avatar_from_gallery', 'post'))
		{
			$avatar_name = Http::request('avatar_from_gallery', 'post');
			$method = AVATAR_METHOD_GALLERY;
		}
		// Lier un avatar depuis une URL
		else if (Http::request('link_avatar', 'post'))
		{
			$avatar_name = Http::request('link_avatar', 'post');
			$method = AVATAR_METHOD_LINK;
		}
		else
		{
			$avatar_name = Fsb::$session->data['u_avatar'];
			$method = Fsb::$session->data['u_avatar_method'];
		}

		// On tente de redimensionner l'image si besoin
		if ($this->need_resize)
		{
			$avatar_path = AVATAR_PATH . $avatar_name;
			$gd = new Gd();
			if ($content = $gd->resize($avatar_path, Fsb::$cfg->get('avatar_width'), Fsb::$cfg->get('avatar_height')))
			{
				fsb_write($avatar_path, $content);
			}
			else
			{
				@unlink($avatar_path);
				Display::message(sprintf(Fsb::$session->lang('avatar_height_width_error'), Fsb::$cfg->get('avatar_width'), Fsb::$cfg->get('avatar_height')));
			}

			// On verifie a nouveau la taille de l'avatar
			$file_size = @filesize($file_path);
			if ($file_size > Fsb::$cfg->get('avatar_weight'))
			{
				@unlink($avatar_path);
				Display::message(sprintf(Fsb::$session->lang('avatar_max_size_error'), Fsb::$cfg->get('avatar_weight')));
			}
		}

		// On met a jour le profil du membre
		Fsb::$db->update('users', array(
			'u_avatar' =>			$avatar_name,
			'u_avatar_method' =>	$method
		), 'WHERE u_id = ' . Fsb::$session->id());

		Log::user(Fsb::$session->id(), 'update_avatar');
		Display::message('user_profil_submit', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=avatar', 'forum_profil');
	}

	/**
	 * Supprime l'avatar courant du membre
	 */
	public function delete_avatar()
	{		
		if (Fsb::$session->data['u_avatar_method'] == AVATAR_METHOD_UPLOAD)
		{
			$this->delete_matches_file(AVATAR_PATH, md5(Fsb::$session->id()));
		}
		// On met a jour le profil du membre
		Fsb::$db->update('users', array(
			'u_avatar' =>			'',
			'u_avatar_method' =>		'',
		), 'WHERE u_id = ' . Fsb::$session->id());

		Log::user(Fsb::$session->id(), 'delete_avatar');
		Display::message('user_profil_submit', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=avatar', 'forum_profil');
	}
	
	/**
	 * Supprime tous les fichiers dont le nom est $match (quelque soit l'extension)
	 * dans le dossier $dir.
	 *
	 * @param string $dir Repetoire a traiter
	 * @param string $match Nom de fichier
	 */
	public function delete_matches_file($dir, $match)
	{
		$fd = opendir($dir);
		while ($file = readdir($fd))
		{
			if ($file[0] != '.' && preg_match('#^' . preg_quote($match, '#') . '#i', $file))
			{
				if (is_writable($dir))
				{
					@unlink($dir . $file);
				}
			}
		}
		closedir($fd);
	}
	
}

/* EOF */