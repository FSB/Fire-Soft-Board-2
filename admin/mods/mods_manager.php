<?php
/*
** +---------------------------------------------------+
** | Name :		~/admin/mods/mods_manager.php
** | Begin :	10/08/2005
** | Last :		13/01/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Liste les modules à installer, et permet de les installer directement sur le forum via
** la classe Module
*/
class Fsb_frame_child extends Fsb_admin_frame
{
	// Arguments de la page
	public $mode, $mod_path, $module;

	/*
	** Constructeur
	*/
	public function main()
	{
		$this->mode =		Http::request('mode');
		$this->mod_path =	Http::request('mod_path');

		$call = new Call($this);
		$call->module(array(
			'list' =>		array('functions', 'mods', 'install', 'streaming', 'backup'),
			'url' =>		'index.' . PHPEXT . '?p=mods_manager',
			'lang' =>		'adm_mods_menu_',
			'default' =>	'functions',
		));

		$return = $call->post(array(
			'submit_activation' =>	':page_submit_activation',
			'submit_mods' =>		':page_submit_mods',
			'submit_install' =>		':page_submit_install',
			'install_stream' =>		':install_mods_stream',
			'submit_upload_mod' =>	':submit_upload_mod',
		));

		if (!$return)
		{
			$call->functions(array(
				'mode' => array(
					'install' =>		'page_install_mod',
					'restore' =>		'restore_backup',
				),
				'module' => array(
					'functions' =>		'page_mods_functions',
					'mods' =>			'page_mods_mods',
					'install' =>		'page_mods_install',
					'streaming' =>		'page_mods_streaming',
					'backup' =>			'page_mods_backup',
				),
			));
		}
	}

	/*
	** Affiche la liste des modules pour l'installation
	*/
	public function page_mods_install()
	{
		// On récupère les MODS déjà installés pour ne pas les afficher dans la liste
		$sql = 'SELECT mod_name, mod_version
				FROM ' . SQL_PREFIX . 'mods
				WHERE mod_type = ' . Mods::EXTERN;
		$result = Fsb::$db->query($sql);
		$install_mod = array();
		while ($row = Fsb::$db->row($result))
		{
			$install_mod[$row['mod_name']] = $row['mod_version'];
		}
		Fsb::$db->free($result);

		Fsb::$tpl->set_switch('mods_list');
		Fsb::$tpl->set_switch('page_install');
		$dir = ROOT . 'mods/';
		Fsb::$tpl->set_vars(array(
			'L_TITLE' =>		sprintf(Fsb::$session->lang('adm_mods_list'), $dir),
			'USE_FTP' =>		(Fsb::$cfg->get('ftp_default')) ? TRUE : FALSE,
		));

		// On parcourt le dossier ~/mods/ pour lister les MODS à installer
		$fd = opendir($dir);
		while ($file = readdir($fd))
		{
			if ($file[0] != '.' && is_dir($dir . $file) && $file != 'save' && file_exists($dir . $file . '/install.xml') && !isset($install_mod[$file]))
			{
				$module = new Module();
				$module->set_config('mod_name', $file);
				$module->set_config('mod_path', $dir . $file . '/');
				$module->load_template($module->get_config('mod_path') . 'install.xml');

				$is_update =	$module->xml->document->header[0]->childExists('isUpdate');
				$parent =		($is_update) ? $module->xml->document->header[0]->isUpdate[0]->getAttribute('parent') : '';
				$version =		$module->xml->document->header[0]->version[0]->getData();

				if ($parent && fsb_version_compare($install_mod[$parent], $version) >= 0)
				{
					continue ;
				}

				Fsb::$tpl->set_blocks('mod', array(
					'NAME' =>			$module->xml->document->header[0]->name[0]->getData(),
					'VERSION' =>		$version,
					'DESCRIPTION' =>	$module->xml->document->header[0]->description[0]->getData(),
					'UPDATE_EXPLAIN' =>	($is_update) ? sprintf(Fsb::$session->lang('adm_mods_is_update_explain'), $parent) : '',

					'U_TEMPLATE' =>		$dir . $file . '/install.xml',
					'U_INSTALL' =>		sid('index.' . PHPEXT . '?p=mods_manager&amp;mode=install&amp;mod_path=' . $file . '&amp;module=install'),
				));
				unset($module);
			}
		}
		closedir($fd);
	}

	/*
	** Affiche la liste des modules installés
	*/
	public function page_mods_mods()
	{
		// On récupère sur le serveur www.fire-soft-board.com la liste des MODS et leur version pour vérifier les mises à jour
		$xml_data = Http::get_file_on_server(FSB_REQUEST_SERVER, FSB_REQUEST_MODS_VERSION, 10);

		// On parse les données reçue pour mettre en forme le tableau php
		$mods_version = array();
		if ($xml_data)
		{
			$xml = new Xml();
			$xml->load_content($xml_data);
			if ($xml->document->childExists('mod'))
			{
				foreach ($xml->document->mod AS $mod_handler)
				{
					$mods_version[$mod_handler->name[0]->getData()] = $mod_handler->version[0]->getData();
				}
			}
		}

		Fsb::$tpl->set_switch('mods_list');
		Fsb::$tpl->set_vars(array(
			'L_TITLE' =>		Fsb::$session->lang('adm_mods_mods'),

			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=mods_manager&amp;module=mods'),
		));

		// On récupère les MODS installés
		$sql = 'SELECT mod_name, mod_real_name, mod_version, mod_description, mod_status
					FROM ' . SQL_PREFIX . 'mods
					WHERE mod_type = ' . Mods::EXTERN;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('mod', array(
				'NAME' =>			$row['mod_real_name'],
				'VERSION' =>		$row['mod_version'],
				'DESCRIPTION' =>	$row['mod_description'],
				'CHECK_NAME' =>		$row['mod_name'],
				'NEW_VERSION' =>	(isset($mods_version[$row['mod_name']]) && fsb_version_compare($row['mod_version'], $mods_version[$row['mod_name']]) === -1) ? $mods_version[$row['mod_name']] : FALSE,
				'CHECKED' =>		($row['mod_status']) ? TRUE : FALSE,

				'U_TEMPLATE' =>		'http://www.fire-soft-board.com/mods.php?mod_name=' . urlencode($row['mod_name']),
			));
			unset($module);
		}
		Fsb::$db->free($result);
	}

	/*
	** Affiche la page du formulaire d'éxécution de modules
	*/
	public function page_mods_functions()
	{
		Fsb::$tpl->set_switch('mods_activation');

		Fsb::$tpl->set_vars(array(
			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=mods_manager')
		));
		
		$sql = 'SELECT mod_name, mod_status
					FROM ' . SQL_PREFIX . 'mods
					WHERE mod_type = ' . Mods::INTERN;
		$result = Fsb::$db->query($sql, 'mods_');
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('mod', array(
				'MOD_NAME' =>		(Fsb::$session->lang('adm_activation_mod_' . $row['mod_name'])) ? Fsb::$session->lang('adm_activation_mod_' . $row['mod_name']) : $row['mod_name'],
				'MOD_EXPLAIN' =>	(Fsb::$session->lang('adm_activation_mod_' . $row['mod_name'] . '_explain')) ? Fsb::$session->lang('adm_activation_mod_' . $row['mod_name'] . '_explain') : NULL,
				'NAME' =>			$row['mod_name'],
				'ACTIVATED' =>		($row['mod_status']) ? TRUE : FALSE,
			));
		}
		Fsb::$db->free($result);
	}

	/*
	** Affiche la liste des backups du répertoire ~/mods/save/
	*/
	public function page_mods_backup()
	{
		Fsb::$tpl->set_switch('mods_backup');

		$fd = opendir(ROOT . 'mods/save/');
		while ($file = readdir($fd))
		{
			if ($file[0] != '.' && preg_match('#^save_([0-9]{1,2})_([0-9]{1,2})_([0-9]{1,2})_([0-9]{1,2})_([0-9]{1,2})_([0-9]{1,2})(\.(tar|tar\.gz|tgz|zip))?$#i', $file, $match))
			{
				if (is_dir(ROOT . 'mods/save/' . $file))
				{
					$compress_type = 'dir';
				}
				else if (isset($match[7]))
				{
					$compress_type = $match[8];
				}

				Fsb::$tpl->set_blocks('file', array(
					'FILENAME' =>		$file,
					'COMPRESS' =>		(Fsb::$session->lang('adm_mods_' . $compress_type)) ? Fsb::$session->lang('adm_mods_' . $compress_type) : $compress_type,
					'DATE' =>			$match[1] . ' ' . Fsb::$session->lang('month_' . intval($match[2])) . ' 20' . $match[3] . ', ' . $match[4] . ' ' . $match[5],

					'U_FILENAME' =>		($compress_type == 'dir') ? '' : sid(ROOT . 'mods/save/' . $file),
					'U_RESTORE' =>		sid('index.' . PHPEXT . '?p=mods_manager&amp;module=backup&amp;mode=restore&amp;restore=' . $file),
				));
			}
		}
		closedir($fd);
	}

	/*
	** Soumission du formulaire d'activation des fonctions
	*/
	public function page_submit_activation()
	{		
		foreach ($_POST AS $key => $value)
		{
			if (preg_match('/^ac_(.*?)$/i', $key, $match) && Fsb::$mods->exists($match[1]) && Fsb::$mods->is_active($match[1]) != $value)
			{
				Fsb::$db->update('mods', array(
					'mod_status' =>		intval($value),
				), 'WHERE mod_name = \'' . $match[1] . '\'');
			}
		}
		Fsb::$db->destroy_cache('mods_');

		Display::message('adm_activation_well_submit', 'index.' . PHPEXT . '?p=mods_manager', 'modules_activation');
	}

	/*
	** Page permettant d'installer le MOD
	*/
	public function page_install_mod()
	{
		if (!is_dir(ROOT . 'mods/' . $this->mod_path))
		{
			Display::message('adm_mods_not_exists');
		}

		$module = new Module();
		$module->set_config('mod_name', $this->mod_path);
		$module->set_config('mod_path', ROOT . 'mods/' . $this->mod_path . '/');
		$module->load_template($module->get_config('mod_path') . 'install.xml');
		
		// Nombre de fichiers à modifier
		$file_open = count($module->get_updated_files());
		
		// Nombre de fichiers joints
		$file_joined = 0;
		if (file_exists($module->get_config('mod_path') . '/root'))
		{
			$file_joined = $this->count_file_in_directory($module->get_config('mod_path') . '/root/');
		}

		// Requètes SQL ?
		$have_query = FALSE;
		foreach ($module->xml->document->instruction[0]->line AS $hd)
		{
			$method = $module->convert_to_valid_function($hd->getAttribute('name'));
			if ($method == 'sql')
			{
				$have_query = TRUE;
				break;
			}
		}

		// MAJ ?
		$is_update =	$module->xml->document->header[0]->childExists('isUpdate');
		$parent =		($is_update) ? $module->xml->document->header[0]->isUpdate[0]->getAttribute('parent') : '';

		Fsb::$tpl->set_switch('mods_install');
		Fsb::$tpl->set_vars(array(
			'NAME' =>				$module->xml->document->header[0]->name[0]->getData(),
			'VERSION' =>			$module->xml->document->header[0]->version[0]->getData(),
			'DESCRIPTION' =>		$module->xml->document->header[0]->description[0]->getData(),
			'MOD_NOTE' =>			($module->xml->document->header[0]->childExists('note')) ? nl2br($module->xml->document->header[0]->note[0]->getData()) : '',
			'FILE_OPEN' =>			$file_open,
			'FILE_JOINED' =>		$file_joined,
			'SQL' =>				($have_query) ? Fsb::$session->lang('yes') : Fsb::$session->lang('no'),
			'USE_FTP' =>			(Fsb::$cfg->get('ftp_default')) ? TRUE : FALSE,
			'UPDATE_EXPLAIN' =>		($is_update) ? sprintf(Fsb::$session->lang('adm_mods_is_update_explain'), $parent) : '',
			
			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=mods_manager&amp;mod_path=' . $this->mod_path),
		));

		// Auteur(s)
		foreach ($module->xml->document->header[0]->author AS $author_handler)
		{
			$email = $author_handler->email[0]->getData();
			$website = ($author_handler->childExists('website')) ? $author_handler->website[0]->getData() : '';
			if ($website && !preg_match('#^(http|https|ftp)://#', $website))
			{
				$website = 'http://' . $website;
			}

			Fsb::$tpl->set_blocks('author', array(
				'NAME' =>		$author_handler->name[0]->getData(),
				'WEBSITE' =>	$website,
				'EMAIL' =>		($email) ? '&lt;<a href="mailto:' . $email . '">' . String::no_spam($email) . '</a>&gt;' : '',
			));
		}
	}

	/*
	** Lance l'installation du module
	*/
	public function page_submit_install()
	{
		$module = new Module();
		$module->file_system(Http::request('use_ftp'));
		$module->set_config('mod_name', $this->mod_path);
		$module->set_config('mod_path',			ROOT . 'mods/' . $this->mod_path . '/');
		$module->set_config('install_duplicat', (Http::request('install_duplicat', 'post')) ? TRUE : FALSE);
		$module->set_config('install_sql',		(Http::request('install_sql', 'post')) ? TRUE : FALSE);
		$module->set_config('install_file',		(Http::request('install_file', 'post')) ? TRUE : FALSE);

		// Préinstallation du MOD
		$module->load_template($module->get_config('mod_path') . 'install.xml');
		$module->save_files('mods/save', Http::request('format_backup', 'post'));
		$module->set_config('install', FALSE);
		$module->install();

		// Erreurs lors de la préinstallation ?
		$this->module_log_error($module);

		// Installation finale
		if (!count($module->log_error))
		{
			$module->set_config('install', TRUE);
			$module->install();
			$this->module_log_error($module);

			// Pas d'erreur d'installation ?
			if (!count($module->log_error))
			{
				if (!$module->xml->document->header[0]->childExists('isUpdate'))
				{
					Fsb::$db->insert('mods', array(
						'mod_name' =>			$this->mod_path,
						'mod_real_name' =>		$module->xml->document->header[0]->name[0]->getData(),
						'mod_version' =>		$module->xml->document->header[0]->version[0]->getData(),
						'mod_description' =>	$module->xml->document->header[0]->description[0]->getData(),
						'mod_author' =>			$module->xml->document->header[0]->author[0]->name[0]->getData(),
						'mod_email' =>			$module->xml->document->header[0]->author[0]->email[0]->getData(),
						'mod_website' =>		$module->xml->document->header[0]->author[0]->website[0]->getData(),
						'mod_type' =>			Mods::EXTERN,
						'mod_status' =>			TRUE,
					));
				}
				else if ($parent = $module->xml->document->header[0]->isUpdate[0]->getAttribute('parent'))
				{
					Fsb::$db->update('mods', array(
						'mod_version' =>	$module->xml->document->header[0]->version[0]->getData(),
					), 'WHERE mod_name = \'' . Fsb::$db->escape($parent) . '\'');
				}
			}
		}

		Fsb::$tpl->set_vars(array(
			'L_RETURN_MODS' =>		sprintf(Fsb::$session->lang('adm_mods_return'), sid('index.' . PHPEXT . '?p=mods_manager&amp;module=install')),
		));

		// On rafraichi le menu administratif
		Fsb::$menu->refresh_menu();

		// On vide le cache SQL
		Fsb::$db->cache->garbage_colector(0);
	}

	/*
	** Affiche les erreurs du module
	** -----
	** $module ::		Objet module
	*/
	public function module_log_error(&$module)
	{
		Fsb::$tpl->set_switch('mods_error');
		Fsb::$tpl->set_vars(array(
			'L_TITLE' =>	($module->get_config('install')) ? Fsb::$session->lang('module_log_error') : Fsb::$session->lang('module_log_error_pre'),
		));

		$error_exists = FALSE;
		foreach ($module->log_error AS $value)
		{
			$error_exists = TRUE;
			Fsb::$tpl->set_blocks('row', array(
				'ACTION' =>		$value['action'],
				'ERROR' =>		sprintf(Fsb::$session->lang('module_error_' . $value['errno']), $value['errstr'], $value['errstr2']),
			));
		}
		
		$report = (!$error_exists) ? Fsb::$session->lang('module_no_report' . (($module->get_config('install')) ? '' : '_pre')) : Fsb::$session->lang('module_report' . (($module->get_config('install')) ? '' : '_pre'));
		Fsb::$tpl->set_vars(array(
			'L_REPORT' =>	$report,
		));
	}

	/*
	** Soumission du formulaire d'activation des mods
	*/
	public function page_submit_mods()
	{		
		$action = (array) Http::request('action', 'post');

		$sql = 'SELECT mod_name
				FROM ' . SQL_PREFIX . 'mods
				WHERE mod_type = ' . Mods::EXTERN;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$db->update('mods', array(
				'mod_status' =>		(in_array($row['mod_name'], $action)) ? 1 : 0,
			), 'WHERE mod_name = \'' . Fsb::$db->escape($row['mod_name']) . '\'');
		}
		Fsb::$db->free($result);
		Fsb::$db->destroy_cache('mods_');

		Display::message('adm_activation_well_submit', 'index.' . PHPEXT . '?p=mods_manager&amp;module=mods', 'modules_activation');
	}

	/*
	** Restauration d'un backup
	*/
	public function restore_backup()
	{
		$restore = trim(Http::request('restore'));

		if (check_confirm())
		{
			// On protège le nom du fichier
			$restore = str_replace(array('\\', '/'), array('_', '_'), $restore);

			// Instance d'un objet File()
			$file = File::factory((Fsb::$cfg->get('ftp_default') || Http::getcookie('ftp')) ? TRUE : FALSE);

			// S'il s'agit d'un dossier on recopie les fichiers
			if ($restore[0] != '.' && is_dir(ROOT . 'mods/save/' . $restore))
			{
				$file->copy('mods/save/' . $restore, './');
			}
			else if (preg_match('#\.(tar\.gz|tar|zip)$#i', $restore, $match))
			{
				// Decompression du backup
				$compress = new Compress('mods/save/' . $restore);
				$compress->extract('./');
			}

			// Lecture et suppression du mod.log
			if (file_exists(ROOT . 'mod.log'))
			{
				$mod_log = file(ROOT . 'mod.log');
				if (isset($mod_log[0]))
				{
					$sql = 'DELETE FROM ' . SQL_PREFIX . 'mods
							WHERE mod_name = \'' . Fsb::$db->escape(trim($mod_log[0])) . '\'
								AND mod_type = ' . Mods::EXTERN;
					Fsb::$db->query($sql);
				}
				@unlink(ROOT . 'mod.log');
			}

			Display::message('adm_mods_backup_success', 'index.' . PHPEXT . '?p=mods_manager&amp;module=backup', 'modules_activation');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=mods_manager&module=backup');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('adm_mods_backup_confirm'), 'index.' . PHPEXT . '?p=mods_manager&amp;module=backup', array('module' => $this->module, 'mode' => $this->mode, 'restore' => $restore));
		}
	}

	/*
	** Affiche les MODS disponible sur le serveur FSB
	*/
	public function page_mods_streaming()
	{
		Fsb::$tpl->set_switch('mods_streaming');

		// Liste des derniers MODS
		$last_mods = Http::get_file_on_server(FSB_REQUEST_SERVER, FSB_REQUEST_MODS_LAST, 10);
		if ($last_mods)
		{
			$xml = new Xml();
			$xml->load_content($last_mods);
			if ($xml->document->childExists('mod'))
			{
				foreach ($xml->document->mod AS $last_mod)
				{
					Fsb::$tpl->set_blocks('last_mod', array(
						'NAME' =>		$last_mod->name[0]->getData(),
						'DESC' =>		String::unhtmlspecialchars($last_mod->description[0]->getData()),
						'URL' =>		sid('index.' . PHPEXT . '?p=mods_manager&amp;module=streaming&amp;mod_id=' . $last_mod->getAttribute('id')),
					));
				}
			}
			unset($xml);
		}

		// Affichage des données d'un MOD ?
		if ($mod_id = Http::request('mod_id'))
		{
			Fsb::$tpl->set_switch('show_mod_content');

			$mod_content = Http::get_file_on_server(FSB_REQUEST_SERVER, sprintf(FSB_REQUEST_MODS_CONTENT, $mod_id), 10);
			if ($mod_content)
			{
				$xml = new Xml();
				$xml->load_content($mod_content);
				$mod_name = preg_replace('#[^\w]#', '_', strtolower($xml->document->name[0]->getData()));

				Fsb::$tpl->set_vars(array(
					'USE_FTP' =>				Fsb::$cfg->get('ftp_default'),
					'MOD_CONTENT_TITLE' =>		sprintf(Fsb::$session->lang('adm_mods_content_title'), $xml->document->name[0]->getData()),
					'MOD_NAME' =>				$xml->document->name[0]->getData(),
					'MOD_VERSION' =>			$xml->document->version[0]->getData(),
					'MOD_AUTHOR' =>				$xml->document->author[0]->name[0]->getData(),
					'MOD_CONTACT' =>			'mailto:' . $xml->document->author[0]->contact[0]->getData(),
					'MOD_WEBSITE' =>			$xml->document->author[0]->website[0]->getData(),
					'MOD_DESCRIPTION' =>		String::unhtmlspecialchars($xml->document->description[0]->getData()),
					'MOD_EXISTS' =>				(Fsb::$mods->exists($xml->document->realname[0]->getData())) ? TRUE : FALSE,

					'U_DOWNLOAD_MOD' =>			$xml->document->download[0]->full[0]->getData(),
					'U_ACTION' =>				sid('index.' . PHPEXT . '?p=mods_manager&amp;module=streaming&amp;url=' . urlencode($xml->document->download[0]->short[0]->getData()) . '&amp;mod_name=' . $mod_name),
				));
			}
		}
		// Affichage du contenu d'une catégorie de MODS ?
		else if ($cat_id = Http::request('cat_id'))
		{
			Fsb::$tpl->set_switch('show_mod_list');

			$mod_list = Http::get_file_on_server(FSB_REQUEST_SERVER, sprintf(FSB_REQUEST_MODS_CAT, $cat_id), 10);
			if ($mod_list)
			{
				$xml = new Xml();
				$xml->load_content($mod_list);

				if ($xml->document->childExists('mod'))
				{
					foreach ($xml->document->mod AS $mod_list)
					{
						Fsb::$tpl->set_blocks('mod', array(
							'NAME' =>		$mod_list->name[0]->getData(),
							'DESC' =>		String::unhtmlspecialchars($mod_list->description[0]->getData()),
							'AUTHOR' =>		$mod_list->author[0]->name[0]->getData(),
							'U_AUTHOR' =>	$mod_list->author[0]->contact[0]->getData(),
							'URL' =>		sid('index.' . PHPEXT . '?p=mods_manager&amp;module=streaming&amp;mod_id=' . $mod_list->getAttribute('id')),
						));
					}
				}

				Fsb::$tpl->set_vars(array(
					'CAT_MODS_TITLE' =>		sprintf(Fsb::$session->lang('adm_mods_cat_list_mod'), $xml->document->cat[0]->getData()),
				));
			}
		}
		// Affichage des catégories de MODS
		else
		{
			Fsb::$tpl->set_switch('show_cat_list');

			$cat_mods = Http::get_file_on_server(FSB_REQUEST_SERVER, FSB_REQUEST_MODS_CAT_LIST, 10);
			if ($cat_mods)
			{
				$xml = new Xml();
				$xml->load_content($cat_mods);

				if ($xml->document->childExists('cat'))
				{
					foreach ($xml->document->cat AS $cat)
					{
						Fsb::$tpl->set_blocks('mod_cat', array(
							'NAME' =>		$cat->name[0]->getData(),
							'DESC' =>		String::unhtmlspecialchars($cat->description[0]->getData()),
							'TOTAL' =>		sprintf(Fsb::$session->lang('adm_mods_stream_total_cat'), intval($cat->total[0]->getData())),
							'URL' =>		sid('index.' . PHPEXT . '?p=mods_manager&amp;module=streaming&amp;cat_id=' . $cat->getAttribute('id')),
						));
					}
				}
			}
		}
	}

	/*
	** Upload le MOD et le decompresse
	*/
	public function install_mods_stream()
	{
		$url = urldecode(Http::request('url'));
		$mod_name = Http::request('mod_name');
		if ($url && $mod_name)
		{
			// Instance d'un objet File() pour la décompression
			$file = File::factory(Http::request('use_ftp'));

			$mod_name = preg_replace('#[^\w]#', '_', strtolower($mod_name)) . '.zip';
			$url = str_replace('&amp;', '&', $url);
			$content = Http::get_file_on_server(FSB_REQUEST_SERVER, $url, 10);
			
			if (!$content)
			{
				Display::message('adm_mods_stream_not_exists');
			}

			// On copie le contenu
			$file->write('mods/' . $mod_name, $content);


			// Decompression du thème
			$compress = new Compress('mods/' . $mod_name, $file);
			$compress->extract('mods/');
			@unlink(ROOT . 'mods/' . $mod_name);

			Http::redirect('index.' . PHPEXT . '?p=mods_manager&module=install&mode=install&mod_path=' . get_file_data($mod_name, 'filename'));
		}
	}

	/*
	** Upload et décompresse un MOD
	*/
	public function submit_upload_mod()
	{
		// Upload du MOD sur le serveur
		if (!$mod_name = Http::request('upload_mod', 'post'))
		{
			$upload = new Upload('upload_mod');
			$upload->allow_ext(array('zip', 'tar', 'gz'));
			$mod_name = $upload->store(ROOT . 'mods/', TRUE);

			// Cette ligne permettra de mettre en champ caché le MOD si on utilise une connexion FTP
			$_POST['upload_mod'] = $mod_name;
		}

		// Instance de l'un objet File() pour la décompression
		$file = File::factory(Http::request('use_ftp'));

		// Decompression des fichiers
		$compress = new Compress('mods/' . $mod_name, $file);
		$compress->extract('mods/');
		@unlink(ROOT . 'mods/' . $mod_name);

		if (preg_match('#\.tar\.gz$#i', $mod_name))
		{
			$mod_name = substr($mod_name, 0, -4);
		}

		Http::redirect('index.' . PHPEXT . '?p=mods_manager&module=install&mode=install&mod_path=' . get_file_data($mod_name, 'filename'));
	}

	/*
	** Compte le nombre de fichiers dans un répertoire
	** -----
	** $dir ::		Répertoire à vérifier
	*/
	public function count_file_in_directory($dir)
	{
		$count = 0;
		$fd = opendir($dir);
		while ($file = readdir($fd))
		{
			if ($file[0] != '.')
			{
				if (is_dir($dir . $file))
				{
					$count += $this->count_file_in_directory($dir . $file . '/');
				}
				else
				{
					$count++;
				}
			}
		}
		closedir($fd);
		return ($count);
	}
}

/* EOF */