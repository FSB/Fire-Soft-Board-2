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
 * Page generant un webftp.
 * Il est possible d'acceder directement aux fichiers du forum afin de les supprimer / editer, ou bien d'en uploader
 * de nouveaux. On peut trier les fichiers, changer les droits, etc ...
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Fichiers recuperes dans un tableau
	 *
	 * @var array
	 */
	public $data = array();
	
	/**
	 * Dossier courant
	 *
	 * @var string
	 */
	public $dir;
	
	/**
	 * Nom du fichier courant
	 *
	 * @var string
	 */
	public $filename;
	
	/**
	 * Mode
	 *
	 * @var string
	 */
	public $mode;
	
	/**
	 * Classement des fichiers
	 *
	 * @var string
	 */
	public $order;
	
	/**
	 * Sens du classement des fichiers
	 *
	 * @var string
	 */
	public $order_direction;
	
	/**
	 * Les fichiers editables
	 *
	 * @var array
	 */
	public $edit_file = array('php', 'php3', 'php4', 'php5', 'htm', 'html', 'tpl', 'txt', 'css', 'js', 'xml', 'rss', 'htaccess');
	
	/**
	 * Constructeur
	 */
	public function main()
	{
		// On recupere les arguments de la page
		$this->dir = htmlspecialchars(Http::request('dir'));
		if ($this->dir == null)
		{
			$this->dir = '';
		}
		$this->dir = str_replace('../', '', $this->dir);
		
		$this->filename = htmlspecialchars(Http::request('file'));
		if ($this->filename != null)
		{
			$ary = explode('.', $this->filename);
			if (!file_exists(ROOT . $this->dir . $this->filename) || ($this->mode == 'edit' && !in_array($ary[count($ary) - 1], $this->edit_file)))
			{
				Display::message('adm_webftp_file_not_exists');
			}
			unset($ary);
		}
		
		$this->order = htmlspecialchars(Http::request('order'));
		if ($this->order == null)
		{
			$this->order = 'type';
		}
		
		$this->order_direction = htmlspecialchars(Http::request('order_direction'));
		if ($this->order_direction == null)
		{
			$this->order_direction = 'asc';
		}
		
		$this->mode = Http::request('mode');
		
		$call = new Call($this);
		$call->post(array(
			'submit_upload' =>		':upload_file',
			'submit_chmod' =>		':chmod_file',
			'submit_edit' =>		':submit_edit_file',
		));

		$call->functions(array(
			'mode' => array(
				'highlight_php' =>	'page_php_highlight',
				'codepress' =>		'page_codepress',
				'delete' =>			'page_delete_file',
				'edit' =>			'edit_file',
				'default' =>		'webftp_list_file',
			),
		));
	}
	
	/**
	 * Affiche les fichiers du repertoire courant
	 */
	public function webftp_list_file()
	{		
		Fsb::$tpl->set_switch('webftp_list');
		Fsb::$tpl->set_vars(array(
			'CURRENT_DIR' =>			stripslashes($this->dir),
			'USE_FTP' =>			(Fsb::$cfg->get('ftp_default')) ? true : false,

			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=tools_webftp&amp;dir=' . $this->dir),
			'U_WEBFTP_NAME' =>		sid('index.' . PHPEXT . '?p=tools_webftp&amp;dir=' . $this->dir . '&amp;order=name&amp;order_direction=' . (($this->order == 'name') ? (($this->order_direction == 'asc') ? 'desc' : 'asc') : 'asc')),
			'U_WEBFTP_TYPE' =>		sid('index.' . PHPEXT . '?p=tools_webftp&amp;dir=' . $this->dir . '&amp;order=type&amp;order_direction=' . (($this->order == 'type') ? (($this->order_direction == 'asc') ? 'desc' : 'asc') : 'asc')),
			'U_WEBFTP_SIZE' =>		sid('index.' . PHPEXT . '?p=tools_webftp&amp;dir=' . $this->dir . '&amp;order=size&amp;order_direction=' . (($this->order == 'size') ? (($this->order_direction == 'asc') ? 'desc' : 'asc') : 'asc')),
			'U_WEBFTP_PERMS' =>		sid('index.' . PHPEXT . '?p=tools_webftp&amp;dir=' . $this->dir . '&amp;order=perms&amp;order_direction=' . (($this->order == 'perms') ? (($this->order_direction == 'asc') ? 'desc' : 'asc') : 'asc')),
			'U_WEBFTP_DATE' =>		sid('index.' . PHPEXT . '?p=tools_webftp&amp;dir=' . $this->dir . '&amp;order=date&amp;order_direction=' . (($this->order == 'date') ? (($this->order_direction == 'asc') ? 'desc' : 'asc') : 'asc')),
		));

		// On recupere les fichiers du repertoire dans un tableau avec leurs donnees (date, permissiones, extension, etc ...)
		$fd = opendir(ROOT . $this->dir);
		while ($file = readdir($fd))
		{
			if ($file != '.' && $file != '..')
			{
				$ary = explode('.', $file);
				$ext = $ary[count($ary) - 1];
				unset($ary);

				$is_dir = is_dir(ROOT . $this->dir . $file);
				$this->data[] = array(
					'name' =>		$file,
					'type' =>		($is_dir) ? null : $ext,
					'size' =>		($is_dir) ? (($this->order_direction == 'asc') ? (pow(2, 32) - 1) : 0) : filesize(ROOT . $this->dir . $file),
					'perms' =>		$this->get_perms(fileperms(ROOT . $this->dir . $file)),
					'date' =>		filemtime(ROOT . $this->dir . $file),
					'is_dir' =>		$is_dir,
				);
			}
		}

		// On trie les donnees si besoin
		usort($this->data, array($this, 'order_file'));

		// Retour vers dossier precedent, et retour vers la racine du forum
		foreach (array('root' => '~/', 'back' => '../') AS $key => $value)
		{
			Fsb::$tpl->set_blocks('file', array(
				'NAME' =>		$value,
				'TYPE' =>		Fsb::$session->lang('adm_webftp_dir'),
				'SIZE' =>		'---',
				'DATE' =>		'---',
				'PERMS' =>		'---',
				'IMG_TYPE' =>	'adm_tpl/img/dir.gif',
				'CAN_EDIT' =>	false,
				'CAN_DELETE' =>	false,
				
				'U_DIR' =>		sid('index.' . PHPEXT . '?p=tools_webftp&amp;dir=' . (($key == 'back') ? dirname($this->dir) : '') . '/&amp;order=' . $this->order . '&amp;order_direction=' . $this->order_direction),
			));
		}

		// On affiche les fichiers ligne par ligne
		foreach ($this->data AS $value)
		{
			// Lien vers le fichier
			if ($value['is_dir'])
			{
				$u_dir = sid('index.' . PHPEXT . '?p=tools_webftp&amp;dir=' . $this->dir . $value['name'] . '/&amp;order=' . $this->order . '&amp;order_direction=' . $this->order_direction);
			}
			else if (preg_match('#^php[0-9]?$#i', $value['type']) && function_exists('highlight_file'))
			{
				$u_dir = sid('index.' . PHPEXT . '?p=tools_webftp&amp;mode=highlight_php&amp;dir=' . $this->dir . '&amp;phpfile=' . $value['name']);
			}
			else
			{
				$u_dir = ROOT . $this->dir . $value['name'];
			}

			Fsb::$tpl->set_blocks('file', array(
				'ACTION_NAME' =>	$this->dir . $value['name'],
				'NAME' =>			$value['name'],
				'TYPE' =>			($value['type'] == null) ? Fsb::$session->lang('adm_webftp_dir') : sprintf(Fsb::$session->lang('adm_webftp_file'), strtoupper($value['type'])),
				'SIZE' =>			($value['is_dir']) ? '---' : convert_size($value['size']),
				'PERMS' =>			$value['perms'],
				'DATE' =>			date('d/m/Y H:i:s', $value['date']),
				'IMG_TYPE' =>		(($value['is_dir']) ? 'adm_tpl/img/dir.gif' : 'adm_tpl/img/' . $this->get_type_img($value['type']) . '.gif'),
				'CAN_EDIT' =>		(in_array($value['type'], $this->edit_file)) ? true : false,
				'CAN_DELETE' =>		true,
				
				'U_DIR' =>			$u_dir,
				'U_EDIT' =>			sid('index.' . PHPEXT . '?p=tools_webftp&amp;mode=edit&amp;dir=' . $this->dir . '&amp;file=' . $value['name']),
				'U_DELETE' =>		sid('index.' . PHPEXT . '?p=tools_webftp&amp;mode=delete&amp;dir=' . $this->dir . '&amp;file=' . $value['name']),
			));
		}
	}
	
	/**
	 * Converti des permissions (octal) en permissions symboliques (rwx)
	 * http://fr.php.net/manual/fr/function.fileperms.php
	 *
	 * @param int $perms permissions en octal
	 * @return string permission symbolique
	 */
	public function get_perms($perms)
	{
		if (($perms & 0xC000) == 0xC000)
		{
			// Socket
			$info = 's';
		}
		elseif (($perms & 0xA000) == 0xA000)
		{
			// Lien symbolique
			$info = 'l';
		}
		elseif (($perms & 0x8000) == 0x8000)
		{
			// Regulier
			$info = '-';
		}
		elseif (($perms & 0x6000) == 0x6000)
		{
			// Block special
			$info = 'b';
		}
		elseif (($perms & 0x4000) == 0x4000)
		{
			// Dossier
			$info = 'd';
		}
		elseif (($perms & 0x2000) == 0x2000)
		{
			// Caractere special
			$info = 'c';
		}
		elseif (($perms & 0x1000) == 0x1000)
		{
			// FIFO pipe
			$info = 'p';
		}
		else
		{
			// Inconnu
			$info = 'u';
		}
		
		// Proprietaire
		$info .= (($perms & 0x0100) ? 'r' : '-');
		$info .= (($perms & 0x0080) ? 'w' : '-');
		$info .= (($perms & 0x0040) ?
			   (($perms & 0x0800) ? 's' : 'x' ) :
			   (($perms & 0x0800) ? 'S' : '-'));
		
		// Groupe
		$info .= (($perms & 0x0020) ? 'r' : '-');
		$info .= (($perms & 0x0010) ? 'w' : '-');
		$info .= (($perms & 0x0008) ?
			   (($perms & 0x0400) ? 's' : 'x' ) :
			   (($perms & 0x0400) ? 'S' : '-'));
		
		// Tous
		$info .= (($perms & 0x0004) ? 'r' : '-');
		$info .= (($perms & 0x0002) ? 'w' : '-');
		$info .= (($perms & 0x0001) ?
			   (($perms & 0x0200) ? 't' : 'x' ) :
			   (($perms & 0x0200) ? 'T' : '-'));
			   
		return ($info);
	}
	
	/**
	 * Callback usort()
	 * Tri le tableau de fichiers
	 *
	 * @param array $a Fichier A
	 * @param array $b Fichier B
	 * @return int 
	 */
	public function order_file($a, $b)
	{
		if ($this->order_direction == 'desc')
		{
			$result = strcmp($a[$this->order], $b[$this->order]);
			if ($result == 0)
			{
				return (($a['name'] > $b['name']) ? 1 : -1);
			}
			else
			{
				return (($a[$this->order] < $b[$this->order]) ? 1 : -1);
			}
		}
		else
		{
			$result = strcmp($a[$this->order], $b[$this->order]);
			if ($result == 0)
			{
				return (($a['name'] > $b['name']) ? 1 : -1);
			}
			else
			{
				return (($a[$this->order] > $b[$this->order]) ? 1 : -1);
			}
		}
	}
	
	/*
	** Upload un fichier sur le serveur
	*/
	public function upload_file()
	{
		$upload = new Upload('upload_file');
		$upload->allow_ext(true);
		$upload->store(ROOT . $this->dir);
	}
	
	/**
	 * Chmod un ou plusieurs fichiers
	 */
	public function chmod_file()
	{
		// Instance de la classe File
		$file = File::factory(Http::request('use_ftp', 'post'));

		$action = Http::request('action', 'post');
		if (!is_array($action))
		{
			$action = array();
		}

		$chmod = Http::request('chmod_files', 'post');
		if (strlen($chmod) == 3)
		{
			$chmod = '0' . $chmod;
		}

		foreach ($action AS $f)
		{
			if (file_exists(ROOT . $f))
			{
				$file->chmod($f, octdec($chmod));
			}
		}

		Http::redirect('index.' . PHPEXT . '?p=tools_webftp&dir=' . $this->dir);
	}
	
	/**
	 * Page d'edition d'un fichier
	 */
	public function edit_file()
	{
		$ext = get_file_data($this->filename, 'extension');
		if (!in_array($ext, $this->edit_file))
		{
			Display::message('adm_webftp_bad_ext');
		}
		
		Fsb::$tpl->set_switch('webftp_edit');
		Fsb::$tpl->set_vars(array(
			'FILENAME' =>		sprintf(Fsb::$session->lang('adm_webftp_edit_file'), ROOT . $this->dir . $this->filename),
			'CONTENT_FILE' =>	htmlspecialchars(file_get_contents(ROOT . $this->dir . $this->filename)),
			'USE_FTP' =>		(Fsb::$cfg->get('ftp_default')) ? true : false,
			'U_CODEPRESS' =>	sid('index.' . PHPEXT . '?p=tools_webftp&amp;mode=codepress&amp;dir=' . $this->dir . '&amp;file=' . $this->filename),

			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=tools_webftp&amp;dir=' . $this->dir . '&amp;file=' . $this->filename),
		));
	}

	/**
	 * Sauvegarde les modifications dans le fichier lors de l'edition
	 */
	public function submit_edit_file()
	{
		// Instance de la classe File
		$file = File::factory(Http::request('use_ftp', 'post'));
		
		$content = Http::request('content_file', 'post');
		$file->write($this->dir . $this->filename, $content);
		
		Log::add(Log::ADMIN, 'webftp_log_edit', $this->dir . $this->filename);
		Display::message('adm_webftp_well_edit', 'index.' . PHPEXT . '?p=tools_webftp&amp;dir=' . $this->dir, 'tools_webftp');
	}
	
	/**
	 * Page de suppression d'un fichier (ou d'un dossier)
	 */
	public function page_delete_file()
	{
		if (check_confirm())
		{
			$this->delete_file(ROOT . $this->dir . $this->filename);

			Log::add(Log::ADMIN, 'webftp_log_delete', ROOT . $this->dir . $this->filename);
			Display::message('adm_webftp_well_delete', 'index.' . PHPEXT . '?p=tools_webftp&amp;dir=' . $this->dir, 'tools_webftp');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=tools_webftp&amp;dir=' . $this->dir);
		}
		else
		{
			Display::confirmation(sprintf(Fsb::$session->lang('adm_webftp_confirm_delete'), $this->dir . $this->filename), 'index.' . PHPEXT . '?p=tools_webftp&amp;mode=delete&amp;dir=' . $this->dir . '&amp;file=' . $this->filename);
		}
	}
	
	/**
	 * Supprimer recursivement un dossier si besoin
	 *
	 * @param string $file Fichier à supprimer
	 */
	public function delete_file($file)
	{
		$tmp = basename($file);
		if ($tmp[0] == '.')
		{
			return ;
		}
		
		if (is_dir($file))
		{
			$fd = opendir($file);
			while ($current_file = readdir($fd))
			{
				$this->delete_file($file . '/' . $current_file);
			}
		}
		else
		{
			if (is_writable($file))
			{
				@unlink($file);
			}
		}
	}
	
	/**
	 * Renvoie le type de fichier pour la petite icone le symbolisant
	 *
	 * @param string $ext Extension du fichier
	 * @return string Type du fichier
	 */
	public function get_type_img($ext)
	{
		switch (strtolower($ext))
		{
			case 'gif' :
			case 'jpg' :
			case 'png' :
			case 'bmp' :
				return ('img');

			case 'html' :
			case 'htm' :
				return ('html');
			
			case 'zip' :
			case 'rar' :
			case 'gz' :
			case 'tgz' :
			case 'bz2' :
				return ('zip');
			
			case 'pdf' :
				return ('pdf');
				
			default :
				return ('txt');
		}
	}

	/**
	 * Affiche le contenu d'un fichier PHP
	 */
	public function page_php_highlight()
	{
		$phpfile = Http::request('phpfile');
		if (file_exists(ROOT . $this->dir . $phpfile))
		{
			highlight_file(ROOT . $this->dir . $phpfile);
		}
		exit;
	}

	/**
	 * Charge le contenu d'un fichier pour l'afficher d'editeur Codepress
	 */
	public function page_codepress()
	{
		$ext = get_file_data($this->filename, 'extension');
		if (!in_array($ext, $this->edit_file))
		{
			Display::message('adm_webftp_bad_ext');
		}

		// Language d'affichage pour CodePress
		switch ($ext)
		{
			case 'php' :
			case 'php3' :
			case 'php4' :
			case 'php5' :
			case 'php6' :
				$language = 'php';
			break;

			case 'js' :
				$language = 'javascript';
			break;

			case 'css' :
				$language = 'css';
			break;

			case 'html' :
			case 'tpl' :
			case 'xml' :
				$language = 'html';
			break;

			default :
				$language = 'text';
			break;
		}

		Fsb::$tpl->set_file('codepress.html');
		Fsb::$tpl->set_vars(array(
			'CODEPRESS_CONTENT' =>		htmlspecialchars(file_get_contents(ROOT . $this->dir . $this->filename)),
			'CODEPRESS_LANGUAGE' =>		$language,
		));
	}
}

/* EOF */
