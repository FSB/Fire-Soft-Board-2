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
 * Installeur de modules a partir de fichier de definition XML
 */
class Module extends Fsb_model
{
	/**
	 * @var Xml
	 */
	public $xml;

	/**
	 * Pointe sur la ligne courante durant l'installation d'un module
	 *
	 * @var Xml
	 */
	private $handler = null;

	/**
	 * Configuration de la classe
	 *
	 * @var array
	 */
	private $config = array();

	/**
	 * Erreurs rencontrees
	 *
	 * @var array
	 */
	public $log_error = array();

	/**
	 * Contenu du fichier actuellement ouvert
	 *
	 * @var string
	 */
	private $file_content = '';

	/**
	 * Chaine a chercher dans le fichier
	 *
	 * @var string
	 */
	private $find_code = '';

	/**
	 * Descripteur du fichier ouvert
	 *
	 * @var resource
	 */
	private $file_open = null;

	/**
	 * @var File
	 */
	public $file;

	/**
	 * Ce fichier n'existe pas
	 */
	const MOD_ERROR_FILE_NOT_FOUND = 1;

	/**
	 * Code source non trouve dans le fichier
	 */
	const MOD_ERROR_CODE_NOT_FOUND = 2;

	/**
	 * Fichier non accessible en ecriture
	 */
	const MOD_ERROR_PERMISSION_DENIED = 3;

	/**
	 * Erreur SQL
	 */
	const MOD_ERROR_SQL = 4;

	/**
	 * Repertoire non accessible en ecriture
	 */
	const MOD_ERROR_DIR_NOT_WRITABLE = 5;

	/**
	 * Instruction inconnue
	 */
	const MOD_ERROR_UNKNOWN_INSTRUCTION = 6;

	/**
	 * Constructeur, met la configuration par defaut
	 */
	public function __construct()
	{
		$this->set_config(array(
			'install_sql' => 		true,
			'install_duplicat' =>	true,
			'install_file' =>		true,
			'install' =>			false,
			'mod_path' =>			ROOT,
		));
	}

	/**
	 * Change la configuration de la classe
	 *
	 * @param string|array $data
	 * @param mixed $value
	 */
	public function set_config($data, $value = '')
	{
		if (is_array($data))
		{
			foreach ($data AS $k => $v)
			{
				$this->config[$k] = $v;
			}
		}
		else
		{
			$this->config[$data] = $value;
		}
	}

	/**
	 * Lit une configuration de la classe
	 *
	 * @param string $key Clef de configuration
	 * @return string Valeur de la configuration
	 */
	public function get_config($key)
	{
		return ((isset($this->config[$key])) ? $this->config[$key] : null);
	}

	/**
	 * Cree l'objet de manipulation des fichiers
	 *
	 * @param bool $use_ftp Definit si on utilise une connexion FTP
	 */
	public function file_system($use_ftp)
	{
		$this->file = File::factory($use_ftp);
	}

	/**
	 * Charge un fichier template
	 *
	 * @param string $template_name Chemin vers le fichier template a charger. Il peut s'agir d'un code XML
	 * @param string $load_type 'file' si $template_name est un fichier, 'code' si c'est du code XML
	 */
	public function load_template($template_name, $load_type = 'file')
	{
		$this->xml = new Xml();
		switch ($load_type)
		{
			case 'file' :
				$method = 'load_file';
			break;

			case 'code' :
				$method = 'load_content';
			break;
		}
		$this->xml->$method($template_name);

		// Gestion de la duplication pour la commande "ouvrir"
		if ($this->get_config('install_duplicat'))
		{
			$duplicat = false;
			$add_node = array();
			foreach ($this->xml->document->instruction[0]->line AS $handler)
			{
				$method = $this->convert_to_valid_function($handler->getAttribute('name'));
				if ($method == 'open' && $handler->childExists('duplicat'))
				{
					if ($duplicat)
					{
						$this->duplicat_content($add_node);
					}
					$duplicat = true;
				}
				else if (!in_array($method, array('find', 'replace', 'after', 'before', 'delete')) || ($method == 'open' && !$handler->childExists('duplicat')))
				{
					if ($duplicat)
					{
						$this->duplicat_content($add_node);
					}
					$duplicat = false;
				}

				// Si duplication, on ajoute des nodes a l'arbre XML
				if ($duplicat)
				{
					$add_node[] = $handler;
				}
			}
		}
	}

	/**
	 * Duplique un contenu autant de fois qu'il y a de dossier dans le repertoire concerne
	 *
	 * @param array $list_node Liste des nodes a dupliquer
	 */
	private function duplicat_content(&$list_node)
	{
		// On recupere tout d'abord le dossier a dupliquer
		$dir = $list_node[0]->duplicat[0]->getData();

		// On recupere ensuite le fichier qui va etre modifie, pour savoir le repertoire par defaut a ne pas dupliquer
		$default_file = $list_node[0]->file[0]->getData();
		$split_file = explode('/', $default_file);
		$default_dir = $split_file[1];

		// On parcourt maintenant la liste des dossiers du duplicat
		$fd = opendir(ROOT . $dir);
		while ($file = readdir($fd))
		{
			if ($file != '.' && $file != '..' && $file != '.svn' && is_dir(ROOT . $dir . $file) && $file != $default_dir)
			{
				// On peut desormais dupliquer les nodes
				foreach ($list_node AS $node)
				{
					// On met a jour l'element <file> si on est sur une instruction open
					$pos = count($this->xml->document->instruction[0]->line) - 1;
					$new_node = clone($node);
					if ($this->convert_to_valid_function($node->getAttribute('name')) == 'open')
					{
						$new_file = preg_replace('#' . $dir . $default_dir . '/#', $dir . $file . '/', $node->file[0]->getData());
						$this->xml->document->instruction[0]->appendXmlChild('<line name="open"><file>' . $new_file . '</file></line>', $pos);
					}
					else
					{
						$this->xml->document->instruction[0]->appendChild($new_node, $pos);
					}
				}
			}
		}
		closedir($fd);

		// On vide la liste de nodes
		$list_node = array();
	}

	/**
	 * Lance la procedure d'installation du MOD
	 */
	public function install()
	{
		foreach ($this->xml->document->instruction[0]->line AS $this->handler)
		{
			$method = $this->convert_to_valid_function($this->handler->getAttribute('name'));
			$call_method = 'subparse_' . $method;
			if (method_exists($this, $call_method))
			{
				$this->$call_method();
			}
		}
		$this->subparse_end();

		// remise a 0 des variables
		$this->file_open = $this->file_content = $this->find_code = null;
	}

	//
	// ========== METHODES D'INSTALLATION DU MOD ==========
	//

	/**
	 * Instruction d'ouverture de fichier
	 */
	private function subparse_open()
	{
		if ($this->file_open)
		{
			$this->close_file();
		}

		$this->file_open = ROOT . $this->handler->file[0]->getData();
		if (!$this->get_config('install') && (!file_exists($this->file_open) || !is_readable($this->file_open)))
		{
			$this->error(self::MOD_ERROR_FILE_NOT_FOUND, $this->file_open);
			$this->file_open = null;
		}
		else
		{
			// Contenu du fichier
			$this->file_content = str_replace(array("\r\n", "\r"), array("\n", "\n"), file_get_contents($this->file_open));
		}
	}

	/**
	 * Instruction de recherche de code dans le fichier
	 */
	private function subparse_find()
	{
		if ($this->file_open)
		{
			$this->find_code = str_replace(array("\r\n", "\r"), array("\n", "\n"), $this->handler->code[0]->getData());
			if (!preg_match('#' . preg_quote($this->find_code, '#') . '#', $this->file_content))
			{
				$this->error(self::MOD_ERROR_CODE_NOT_FOUND, '<pre style="overflow: auto; width: 100%">' . htmlspecialchars($this->find_code) . '</pre>', str_replace('//', '/', $this->file_open));
			}
		}
	}

	/**
	 * Instruction de remplacement de code
	 */
	private function subparse_replace()
	{
		if ($this->file_open)
		{
			$code = str_replace(array("\r\n", "\r"), array("\n", "\n"), $this->handler->code[0]->getData());
			$this->file_content = $this->replace_code($this->file_content, $this->find_code, $code);
		}
	}

	/**
	 * Instruction d'ajout de code (avant)
	 */
	private function subparse_before()
	{
		if ($this->file_open)
		{
			$code = str_replace(array("\r\n", "\r"), array("\n", "\n"), $this->handler->code[0]->getData());
			$this->file_content = $this->replace_code($this->file_content, $this->find_code, $code . "\n" . $this->find_code);
		}
	}

	/**
	 * Instruction d'ajout de code (apres)
	 */
	private function subparse_after()
	{
		if ($this->file_open)
		{
			$code = str_replace(array("\r\n", "\r"), array("\n", "\n"), $this->handler->code[0]->getData());
			$this->file_content = $this->replace_code($this->file_content, $this->find_code, $this->find_code . "\n" . $code);
		}
	}

	/**
	 * Instruction d'ajout de code (dans la ligne apres)
	 */
	private function subparse_afterline()
	{
		if ($this->file_open)
		{
			$code = str_replace(array("\r\n", "\r"), array("\n", "\n"), $this->handler->code[0]->getData());
			$this->file_content = $this->replace_code($this->file_content, $this->find_code, $this->find_code . $code);
		}
	}

	/**
	 * Instruction de suppression du code
	 */
	private function subparse_delete()
	{
		if ($this->file_open)
		{
			$this->file_content = preg_replace('#' . preg_quote($this->find_code, '#') . '#', '', $this->file_content, 1);
		}
	}

	/**
	 * Instruction d'execution de requetes SQL
	 */
	private function subparse_sql()
	{
		if ($this->get_config('install'))
		{
			foreach ($this->handler->query AS $sql_handler)
			{
				$query = $sql_handler->getData();
				$query = str_replace('fsb2_', SQL_PREFIX, $query);

				if ($this->get_config('install_sql'))
				{
					if (!Fsb::$db->simple_query($query))
					{
						$this->error(self::MOD_ERROR_SQL, Fsb::$db->sql_error(), $query);
					}
				}
			}
		}
	}

	/**
	 * Instruction de copie de fichiers du module vers le forum
	 */
	private function subparse_copy()
	{
		if ($this->handler->childExists('file'))
		{
			foreach ($this->handler->file AS $file_handler)
			{
				$filename = $file_handler->filename[0]->getData();
				$duplicat = ($file_handler->childExists('duplicat')) ? $file_handler->duplicat[0]->getData() : null;
				$directory = $file_handler->childExists('directory');

				if ($duplicat && $duplicat[strlen($duplicat) - 1] != '/')
				{
					$duplicat .= '/';
				}

				// Duplication de fichier = copier un fichier dans plusieurs repertoires similaires, par
				// exemple les langues, themes, etc ...
				if ($duplicat)
				{
					// On recupere le chemin du fichier a editer
					$file_name = substr($filename, strlen($duplicat));
					$exp = explode('/', $file_name);
					unset($exp[0]);
					$file_name = implode('/', $exp);

					// On parcourt le repertoire de duplicat et on copie le fichier autant de fois qu'il y a de repertoires
					$fd = opendir(ROOT . $duplicat);
					while ($file = readdir($fd))
					{
						if ($file[0] != '.' && is_dir(ROOT . $duplicat . '/' . $file))
						{
							$this->copy_file(substr($this->get_config('mod_path'), strlen(ROOT)) . '/root/' . $filename, $duplicat . '/' . $file . '/' . $file_name);
						}
					}
					closedir($fd);
				}
				else
				{
					if ($directory)
					{
						$this->copy_dir(substr($this->get_config('mod_path'), strlen(ROOT)) . '/root/' . $filename, $filename);
					}
					else
					{
						$this->copy_file(substr($this->get_config('mod_path'), strlen(ROOT)) . '/root/' . $filename, $filename);
					}
				}
			}
		}
	}

	/**
	 * Instruction d'execution de code PHP
	 */
	private function subparse_exec()
	{
		if ($this->get_config('install'))
		{
			$code = str_replace(array("\r\n", "\r"), array("\n", "\n"), $this->handler->code[0]->getData());
			$filename = ($this->handler->childExists('file')) ? $this->handler->file[0]->getData() : null;

			if ($this->get_config('install_file'))
			{
				// Si on execute un fichier on l'ouvre et on parse le code, pour ensuite l'evaluer
				if ($filename)
				{
					$content = trim(file_get_contents(ROOT . $filename));
					$content = preg_replace('#^<\?(php)?#', '', $content);
					$content = preg_replace('#\?>$#', '', $content);
					$content = preg_replace('#/\*\s*begin include\s*\*/.*?/\*\s*end include\s*\*/#si', '', $content);
					$code = $content;
				}

				eval($code);
			}
		}
	}

	/**
	 * Instruction signalant la fin de l'installation
	 */
	private function subparse_end()
	{
		// Un dernier fichier a ecrire ?
		if ($this->file_open)
		{
			$this->close_file();
		}
	}

	//
	// ========== METHODES ANEXES ==========
	//

	/**
	 * Log une erreur rencontree durant l'installation
	 *
	 * @param int $errno Code de l'erreur
	 * @param string $errstr Description de l'erreur
	 * @param string $errstr2 Argument suplémentaire de desvription de l'erreur
	 */
	private function error($errno, $errstr, $errstr2 = null)
	{
		$this->log_error[] = array(
			'errno' =>		$errno,
			'errstr' =>		$errstr,
			'errstr2' =>	$errstr2,
			'action' =>		(is_object($this->handler)) ? $this->handler->getAttribute('name') : null,
		);
	}

	/**
	 * Copie le contenu d'un repertoire vers un autre
	 *
	 * @param string $dir_from Repertoire de provenance
	 * @param string $dir_to Repertoire de destination
	 */
	private function copy_dir($dir_from, $dir_to)
	{
		$fd = opendir($dir_from);
		while ($file = readdir($fd))
		{
			if ($file[0] != '.')
			{
				if (is_dir($dir_from . '/' . $file))
				{
					$this->copy_dir($dir_from . '/' . $file, $dir_to . '/' . $file);
				}
				else if (is_file($dir_from . '/' . $file))
				{
					$this->copy_file($dir_from . '/' . $file, $dir_to . '/' . $file);
				}
			}
		}
		closedir($fd);
	}

	/**
	 * Copie un fichier vers sa destination, en creant les repertoire non existants
	 *
	 * @param string $from Fichier de provenance
	 * @param string $to Fichier de destination
	 * @return bool
	 */
	private function copy_file($from, $to)
	{
		$exp_to = explode('/', dirname($to));
		$path = '';
		foreach ($exp_to AS $dir)
		{
			$path .= $dir . '/';
			if (!is_dir($path))
			{
				$this->file->chmod(substr(dirname($path), 0, strlen(ROOT)), 0777, false);
				if ($this->get_config('install'))
				{
					$this->file->mkdir($path);
				}
				else if (!is_writable(dirname(ROOT . $path)))
				{
					$this->error(self::MOD_ERROR_DIR_NOT_WRITABLE, dirname(ROOT . $path));
				}
				$this->file->chmod(substr(dirname($path), 0, strlen(ROOT)), 0755, false);
			}
		}

		if ($this->get_config('install'))
		{
			return ($this->file->write($to, file_get_contents(ROOT . $from)));
		}
		else if (!file_exists(ROOT . $from))
		{
			$this->error(self::MOD_ERROR_FILE_NOT_FOUND, $from);
		}
	}

	/**
	 * Ecrit le code dans le fichier
	 *
	 */
	private function close_file()
	{
		$filename = substr($this->file_open, strlen(ROOT));
		if ($this->get_config('install'))
		{
			if (!is_writable(ROOT . $filename))
			{
				$this->file->chmod($filename, 0666);
			}

			$this->file->write($filename, $this->file_content);
			$this->file_open = null;

			$this->file->chmod($filename, 0644, false);
		}
		else
		{
			if (!is_writable($this->file_open))
			{
				$this->file->chmod($filename, 0666, false);
				if (!is_writable($this->file_open))
				{
					$this->error(self::MOD_ERROR_PERMISSION_DENIED, $this->file_open);
				}
				$this->file->chmod($filename, 0644, false);
			}
		}
	}

	/**
	 * Renvoie la liste des fichiers qui vont etre modifies lors de l'installation du MOD
	 *
	 * @return array
	 */
	public function get_updated_files()
	{
		$files = array();
		foreach ($this->xml->document->instruction[0]->line AS $handler)
		{
			$method = $this->convert_to_valid_function($handler->getAttribute('name'));
			if ($method == 'open')
			{
				$files[] = $handler->file[0]->getData();
			}
		}
		return ($files);
	}

	/**
	 * Sauvegarde les fichiers que le MOD va modifier dans un dossier
	 *
	 * @param string $dir Repertoire de destination
	 * @param string $ext Type de compression pour les fichiers sauves (.tar, .tar.gz, .tar.bz2 ou .zip)
	 * @param array $files Liste des fichiers a sauver
	 */
	public function save_files($dir, $ext = 'zip', $files = null)
	{
		if ($files == null)
		{
			$files = $this->get_updated_files();
		}

		// Creation d'un fichier log.mod pour stoquer les informations sur le backup
		$this->file->write('mod.log', $this->get_config('mod_name') . "\n");
		$files[] = 'mod.log';

		$filename = 'save_' . date("d_m_y_H_i_s", CURRENT_TIME);
		switch ($ext)
		{
			case 'tar' :
			case 'tar.gz' :
			case 'zip' :
				$compress = new Compress($dir . '/' . $filename . '.' . $ext, $this->file);
				foreach ($files AS $file)
				{
					if (file_exists(ROOT . $file))
					{
						$compress->add_file($file);
					}
				}
				$compress->write();
			break;

			default :
				$this->set_config('install', true);
				$save_dir = $dir . '/' . $filename . '/';
				foreach ($files AS $file)
				{
					if (file_exists(ROOT . $file))
					{
						$this->copy_file($file, $save_dir . preg_replace('#^' . ROOT . '#', '', $file));
					}
				}
				$this->set_config('install', false);
			break;
		}
		$this->file->unlink('mod.log');
	}

	/**
	 * Remplace le code cherché en protégeant les variables de remplacements dans les regexp
	 *
	 * @param string $content Contenu du fichier
	 * @param string $find Code cherché
	 * @param string $replace Code de remplacement
	 * @return string
	 */
	private function replace_code($content, $find, $replace)
	{
		$replace = preg_replace('#\$([0-9]+)#', '__FSB_MOD_INSTALLER1__\\1', $replace);
		$replace = preg_replace('#\\\\\\\([0-9]+)#', '\\\\\\__FSB_MOD_INSTALLER2__\\1', $replace);
		$replace = preg_replace('#\\\\([0-9]+)#', '__FSB_MOD_INSTALLER2__\\1', $replace);
		$content = preg_replace('#' . preg_quote($find, '#') . '#', $replace, $content, 1);
		$content = str_replace(array('__FSB_MOD_INSTALLER1__', '__FSB_MOD_INSTALLER2__'), array('$', '\\'), $content);

		return ($content);
	}

	/**
	 * Convertit un mot clef en un mot comprehensible pour la classe
	 *
	 * @param string $keyword Mot clef a convertir
	 * @return string
	 */
	public function convert_to_valid_function($keyword)
	{
		switch (strtolower($keyword))
		{
			case "ouvrir" :
			case "open" :
				return ('open');

			case "chercher" :
			case "trouver" :
			case "dans la ligne chercher" :
			case "dans la ligne trouver" :
			case "search" :
			case "find" :
			case "in line search" :
			case "in line find" :
				return ('find');

			case "remplacer par" :
			case "remplacer" :
			case "replace by" :
			case "replace" :
				return ('replace');

			case "ajouter apres" :
			case "apres ajouter" :
			case "after add" :
			case "after" :
				return ('after');

			case "dans la ligne ajouter" :
			case "ajouter dans la ligne" :
			case "in line add" :
				return ('afterline');

			case "ajouter avant" :
			case "avant ajouter" :
			case "before add" :
			case "before" :
				return ('before');

			case "supprimer" :
			case "delete" :
				return ('delete');

			case "sql" :
			case "requete sql" :
			case "sql query" :
				return ('sql');

			case "copier" :
			case "copy" :
				return ('copy');

			case "fin" :
			case "fin du mod" :
			case "end" :
			case "end of mod" :
				return ('end');
			
			case 'php' :
			case 'executer' :
			case 'exec' :
				return ('exec');

			default :
				$this->error(self::MOD_ERROR_UNKNOWN_INSTRUCTION, $keyword);
				return (null);
		}
	}
}

/* EOF */