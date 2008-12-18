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
 * Page de gestion des langues
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Langue
	 *
	 * @var string
	 */
	public $language;
	
	/**
	 * Interval definissant le nombre de variables a afficher par page
	 *
	 * @var int
	 */
	public $interval;
	
	/**
	 * Page courante
	 *
	 * @var string
	 */
	public $page;
	
	/**
	 * Mot clé de la recherche
	 *
	 * @var string
	 */
	public $search;
	
	/**
	 * Type de la recherche
	 *
	 * @var string
	 */
	public $search_type;
	
	/**
	 * Module de la page
	 *
	 * @var string
	 */
	public $module;

	/**
	 * Constructeur
	 */
	public function main()
	{
		// On recupere le language
		$this->language = Http::request('language');
		if ($this->language == null || !is_dir(ROOT . 'lang/' . $this->language))
		{
			$this->language = Fsb::$cfg->get('default_lang');
		}

		if ($this->language{strlen($this->language) - 1} == '/')
		{
			$this->language = substr($this->language, 0, -1);
		}

		// Interval definissant le nombre de variables a afficher par page
		$this->interval = intval(Http::request('interval'));
		if ($this->interval <= 0)
		{
			$this->interval = '30';
		}

		// Page courante
		$this->page = intval(Http::request('page'));
		if ($this->page <= 0)
		{
			$this->page = 1;
		}

		// Donnees de la recherche
		$this->search =			Http::request('search');
		$this->search_type =	Http::request('search_type');
		if ($this->search_type != 'key' && $this->search_type != 'value')
		{
			$this->search_type = 'value';
		}

		$call = new Call($this);
		$call->module(array(
			'list' =>		array('default', 'faq', 'mail'),
			'url' =>		'index.' . PHPEXT . '?p=general_lang',
			'lang' =>		'adm_lang_',
			'default' =>	'default',
		));
	
		$return = $call->post(array(
			'submit_refresh' =>		':page_refresh_lang',
			'submit_install' =>		':page_install_lang',
			'submit_lang' =>		':page_submit_lang',
			'export_lang' =>		':page_export_lang',
			'submit_faq' =>			':submit_faq',
			'submit_mail' =>		':submit_mail',
			'submit_add' =>			':submit_add',
			'submit_faq_add' =>		':submit_faq_add',
		));

		$call->functions(array(
			'module' => array(
				'faq' =>		'page_list_faq',
				'mail' =>		'page_list_mail',
				'default' =>	'page_default_lang',
			),
		));
	}

	/**
	 * Page de defaut de gestion des langues
	 */
	public function page_default_lang()
	{
		Fsb::$tpl->set_switch('lang_list');

		Fsb::$tpl->set_vars(array(
			'LIST_LANG' =>		Html::list_langs('language', $this->language),
			'INTERVAL' =>		$this->interval,
			'SEARCH' =>			$this->search,
			'SEARCH_TYPE_K' =>	($this->search_type == 'key') ? 'checked="checked"' : '',
			'SEARCH_TYPE_V' =>	($this->search_type == 'value') ? 'checked="checked"' : '',
			'USE_FTP' =>		(Fsb::$cfg->get('ftp_default')) ? true : false,

			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=general_lang'),
		));

		$this->page_print_lang();
	}

	/**
	 * Affiche toutes les clefs de langues disponible pour ce fichier
	 */
	public function page_print_lang()
	{
		// On charge les langues
		$tmp_lg = $this->page_load_lang($this->language);
		$tmp_lg = array_merge($tmp_lg, $this->get_cache_lang());

		if (!empty($this->search))
		{
			$this->page_search_word($tmp_lg);
		}

		// On affiche les clefs
		if ($count_lg = count($tmp_lg))
		{
			$i = 0;
			foreach ($tmp_lg AS $k => $v)
			{
				if ($i == ((($this->page + 1) * $this->interval)) - $this->interval)
				{
					break;
				}

				if ($i >= ($this->page * $this->interval) - $this->interval)
				{
					Fsb::$tpl->set_blocks('lang', array(
						'KEY' =>				$k,
						'VALUE' =>				htmlspecialchars($v),
						'IS_TEXTAREA' =>		((strlen($v) > 80) ? '1' : '0'),
					));
				}
				$i++;
			}
		}

		// Pagination ?
		if ($count_lg / $this->interval > 1)
		{
			Fsb::$tpl->set_switch('pagination');
		}

		Fsb::$tpl->set_vars(array(
			'PAGINATION' =>		Html::pagination($this->page, $count_lg / $this->interval, sid('index.' . PHPEXT .  '?p=general_lang&amp;interval=' . $this->interval . '&amp;language=' . $this->language . '&amp;search=' . $this->search . '&amp;search_type=' . $this->search_type)),
		));
	}

	/**
	 * Retourne les clefs de langues de la base de donnee
	 *
	 * @return array Clefs de langues
	 */
	public function get_cache_lang()
	{
		$sql = 'SELECT lang_key, lang_value
				FROM ' . SQL_PREFIX . 'langs
				WHERE lang_name = \'' . Fsb::$db->escape($this->language) . '\'';
		$result = Fsb::$db->query($sql, 'langs_');
		$data = array();
		while ($row = Fsb::$db->row($result))
		{
			$data[$row['lang_key']] = $row['lang_value'];
		}
		Fsb::$db->free($result);
		return ($data);
	}

	/**
	 * Filtre le tableau de recherche avec le mot recherche
	 *
	 * @param array $lang Tableau de langue
	 */
	public function page_search_word(&$lang)
	{
		foreach ($lang AS $key => $value)
		{
			if (!String::is_matching('*' . $this->search . '*', ${$this->search_type}))
			{
				unset($lang[$key]);
			}
		}
	}

	/**
	 * Recupere les variables de langue de la langue donnee
	 *
	 * @return array Variable de langue
	 */
	public function page_load_lang()
	{
		$lang = array();
		$fd = opendir(ROOT . 'lang/' . $this->language);
		while ($file = readdir($fd))
		{
			if (preg_match('/^lg_.+\.' . PHPEXT . '/si', $file))
			{
				$lang += include(ROOT . 'lang/' . $this->language . '/' . $file);
			}
		}
		closedir($fd);

		$fd = opendir(ROOT . 'lang/' . $this->language . '/admin/');
		while ($file = readdir($fd))
		{
			if (preg_match('/^lg_.+\.' . PHPEXT . '/si', $file))
			{
				$lang += include(ROOT . 'lang/' . $this->language . '/admin/' . $file);
			}
		}
		closedir($fd);

		return ($lang);
	}

	/**
	 * Page de soumission des langues, on les ajoute dans le cache
	 */
	public function page_submit_lang()
	{
		$tmp_lg = $this->page_load_lang($this->language);
		$lang = $this->get_cache_lang();
		$delete_keys = array();
		foreach ($_POST AS $key => $value)
		{
			if (preg_match('/^lg_(.+)$/si', $key, $match))
			{
				if (isset($lang[$match[1]]) && $tmp_lg[$match[1]] == stripslashes($value))
				{
					$delete_keys[] = $match[1];
				}
				else if (!isset($tmp_lg[$match[1]]) || $tmp_lg[$match[1]] != stripslashes($value))
				{
					Fsb::$db->insert('langs', array(
						'lang_name' =>		array($this->language, true),
						'lang_key' =>		array($match[1], true),
						'lang_value' =>		$value,
					), 'REPLACE', true);
				}
			}
		}
		Fsb::$db->query_multi_insert();

		// Suppression de la base de donnee des clefs de langues identiques a leur valeur dans le fichier de langue
		if ($delete_keys)
		{
			$delete_keys = array_map(array(Fsb::$db, 'escape'), $delete_keys);
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'langs
						WHERE lang_key IN (\'' . implode('\', \'', $delete_keys) . '\')';
			Fsb::$db->query($sql);
		}
		Fsb::$db->destroy_cache('langs_');

		Log::add(Log::ADMIN, 'lang_log_submit', $this->language);
		Display::message('adm_lang_well_submit', 'index.' . PHPEXT . '?p=general_lang', 'general_lang');
	}

	/**
	 * Ajoute une clef de langue
	 */
	public function submit_add()
	{
		$key = Http::request('add_key', 'post');
		$value = Http::request('add_value', 'post');
		if ($key)
		{
			Fsb::$db->insert('langs', array(
				'lang_name' =>		array($this->language, true),
				'lang_key' =>		array($key, true),
				'lang_value' =>		Http::request('add_value', 'post'),
			), 'REPLACE');
			Fsb::$db->destroy_cache('langs_');

			Log::add(Log::ADMIN, 'lang_log_add_key', $key);
			Display::message('adm_lang_well_add', 'index.' . PHPEXT . '?p=general_lang', 'general_lang');
		}
		else
		{
			Http::redirect('index.' . PHPEXT . '?p=general_lang');
		}
	}

	/**
	 * Retabli les variables de langue par defaut en vidant le cache
	 */
	public function page_refresh_lang()
	{
		if (check_confirm())
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'langs
					WHERE lang_name = \'' . Fsb::$db->escape($this->language) . '\'';
			Fsb::$db->query($sql);
			Fsb::$db->destroy_cache('langs_');

			Log::add(Log::ADMIN, 'lang_log_refresh', $this->language);
			Display::message('adm_lang_well_refresh', 'index.' . PHPEXT . '?p=general_lang', 'general_lang');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=general_lang');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('adm_lang_refresh_confirm'), 'index.' . PHPEXT . '?p=general_lang', array('submit_refresh' => true));
		}
	}

	/**
	 * Upload une archive de langue et tente de la decompresser
	 */
	public function page_install_lang()
	{
		if (!$lang_name = Http::request('upload_lang', 'post'))
		{
			// Upload de la langue sur le serveur
			$upload = new Upload('upload_lang');
			$upload->allow_ext(array('zip', 'tar', 'gz', 'xml'));
			$lang_name = $upload->store(ROOT . 'lang/');

			// Cette ligne permettra de mettre en champ cache la langue si on utilise une connexion FTP
			$_POST['upload_lang'] = $lang_name;
		}

		// Import d'une langue sous format XML
		if ($upload->extension == 'xml')
		{
			$lang_xml = new Lang_xml();
			$lang_xml->import('lang/' . $lang_name);
		}
		// Import d'une langue compressee
		else
		{
			// Instance de l'un objet File() pour la decompression
			$file = File::factory(Http::request('use_ftp'));

			// Decompression des fichiers
			$compress = new Compress('lang/' . $lang_name, $file);
			$compress->extract('lang/');
		}
		@unlink(ROOT . 'lang/' . $lang_name);

		Display::message('adm_lang_install_success', 'index.' . PHPEXT . '?p=general_lang', 'general_lang');
	}

	/**
	 * Exporte une langue et lance le telechargement
	 */
	public function page_export_lang()
	{
		$ext = trim(Http::request('export_lang_ext'));

		switch ($ext)
		{
			case 'zip' :
			case 'tar' :
			case 'tar.gz' :
				// On recupere le fichier compresse
				$compress = new Compress('.' . $ext);
				$compress->add_file('lang/' . $this->language . '/', 'lang/');
				$content = $compress->write(true);
			break;

			case 'xml' :
			default :
				// On recupere une exportation XML de la langue
				$lang_xml = new Lang_xml();
				$content = $lang_xml->export(ROOT . 'lang/' . $this->language . '/');
				$filename = $this->language . '.xml';
			break;
		}

		// On lance le telechargement sur le navigateur
		Http::download('fsb_lang_' . $this->language . '.' . $ext, $content);
	}

	/**
	 * Affiche la liste des Emails pour la langue
	 */
	public function page_list_mail()
	{
		Fsb::$tpl->set_switch('lang_mail');
		Fsb::$tpl->set_vars(array(
			'LIST_LANG' =>		Html::list_langs('language', $this->language),
			'USE_FTP' =>		(Fsb::$cfg->get('ftp_default')) ? true : false,
		));

		// Liste des Emails pour la langue
		$path = ROOT . 'lang/' . $this->language . '/mail/';
		$fd = opendir($path);
		while ($file = readdir($fd))
		{
			if ($file[0] != '.')
			{
				$ext = get_file_data($file, 'extension');
				if ($ext == 'txt')
				{
					// On regarde si une modification du fichier a ete faite
					$filename = get_file_data($file, 'filename');
					$content = (file_exists($path . $filename . '.updated')) ? file_get_contents($path . $filename . '.updated') : file_get_contents($path . $file);

					Fsb::$tpl->set_blocks('mail', array(
						'NAME' =>		$file,
						'CONTENT' =>	htmlspecialchars($content),
						'UPDATED' =>	file_exists($path . $filename . '.updated'),
					));
				}
			}
		}
		closedir($fd);
	}

	/**
	 * Modification du texte des Emails
	 */
	public function submit_mail()
	{
		$email_default = (array) Http::request('email_default', 'post');
		$email = (array) Http::request('email', 'post');

		// Objet File pour la modification des fichiers
		$file = File::factory(Http::request('use_ftp'));

		// Liste des Emails supprimes
		$mail_path = ROOT . 'lang/' . $this->language . '/mail/';
		foreach ($email_default AS $f => $v)
		{
			if ($v)
			{
				$updated = get_file_data(basename($f), 'filename') . '.updated';
				if (file_exists($mail_path . $updated))
				{
					$file->unlink(substr($mail_path, strlen(ROOT)) . $updated);
				}

				if (isset($email[$f]))
				{
					unset($email[$f]);
				}
			}
		}

		// Modification
		foreach ($email AS $f => $content)
		{
			$f = basename($f);
			if (file_exists($mail_path . $f) && $this->ln(file_get_contents($mail_path . $f)) != $this->ln($content))
			{
				$updated = get_file_data($f, 'filename') . '.updated';
				$file->write(substr($mail_path, strlen(ROOT)) . $updated, $content);
			}
		}

		Log::add(Log::ADMIN, 'lang_log_submit', $this->language);
		Display::message('adm_lang_well_submit', 'index.' . PHPEXT . '?p=general_lang&amp;module=mail&amp;language=' . $this->language, 'general_lang');
	}

	/**
	 * Uniformise les retours a la ligne
	 *
	 * @param string $str Chaine de caractere
	 * @return string Chaine de caractere
	 */
	public function ln($str)
	{
		return (str_replace(array("\r\n", "\r"), array("\n", "\n"), $str));
	}

	/**
	 * Affichage des clefs de langue de la FAQ
	 */
	public function page_list_faq()
	{
		// Chargement de la FAQ
		Fsb::$session->load_lang('lg_forum_faq');

		$list_section = array();
		foreach ($GLOBALS['faq_data'] AS $section_name => $section)
		{
			$list_section[$section_name] = Fsb::$session->lang('faq_section_' . $section_name);
		}

		Fsb::$tpl->set_switch('lang_faq');
		Fsb::$tpl->set_vars(array(
			'LIST_LANG' =>		Html::list_langs('language', $this->language),
			'LIST_SECTION' =>	Html::make_list('faq_section', '', $list_section),
		));

		// Chargement des clefs pour la FAQ
		$sql = 'SELECT lang_key, lang_value
				FROM ' . SQL_PREFIX . 'langs
				WHERE lang_name = \'' . $this->language . '\'
					AND lang_key LIKE \'_fsb_faq_%\'';
		$result = Fsb::$db->query($sql, 'langs_');
		while ($row = Fsb::$db->row($result))
		{
			if (preg_match('#^_fsb_faq_\[([a-zA-Z0-9_]+?)\]\[([a-zA-Z0-9_]+?)\]\[(question|answer)\]$#', $row['lang_key'], $match))
			{
				$GLOBALS['faq_data'][$match[1]][$match[2]][$match[3]] = $row['lang_value'];
			}
		}
		Fsb::$db->free($result);

		// Affichage des donnees de la FAQ
		foreach ($GLOBALS['faq_data'] AS $section_name => $section)
		{
			Fsb::$tpl->set_blocks('section', array(
				'NAME' =>		$section_name,
				'TITLE' =>		Fsb::$session->lang('faq_section_' . $section_name),
			));

			// Affichage des questions / reponses
			foreach ($section AS $name => $item)
			{
				Fsb::$tpl->set_blocks('section.item', array(
					'NAME' =>			$name,
					'QUESTION' =>		htmlspecialchars($item['question']),
					'ANSWER' =>			htmlspecialchars($item['answer']),
				));
			}
		}
	}

	/**
	 * Enregistre les modifications de la FAQ
	 */
	public function submit_faq()
	{
		// Chargement de la FAQ
		include_once(ROOT . 'lang/' . $this->language . '/lg_forum_faq.' . PHPEXT);

		// Suppression des anciennes valeurs
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'langs
				WHERE lang_name = \'' . Fsb::$db->escape($this->language) . '\'
					AND lang_key LIKE \'_fsb_faq_%\'';
		Fsb::$db->query($sql);

		// Ajout / suppression des modifications
		$section = (array) Http::request('section', 'post');
		foreach ($section AS $section_name => $data)
		{
			foreach ($data AS $item_name => $item)
			{
				if (!$item['answer'])
				{
					$sql = 'DELETE FROM ' . SQL_PREFIX . 'langs
							WHERE lang_name = \'' . Fsb::$db->escape($this->language) . '\'
								AND lang_key = \'' . Fsb::$db->escape('_fsb_faq_[' . $section_name . '][' . $item_name . '][answer]') . '\'';
					Fsb::$db->query($sql);
					continue ;
				}

				if (!isset($GLOBALS['faq_data'][$section_name][$item_name]['question']) || $item['question'] != $GLOBALS['faq_data'][$section_name][$item_name]['question'])
				{
					$key = '_fsb_faq_[' . $section_name . '][' . $item_name . '][question]';
					Fsb::$db->insert('langs', array(
						'lang_name' =>		array($this->language, true),
						'lang_key' =>		array($key, true),
						'lang_value' =>		$item['question'],
					), 'REPLACE', true);
				}

				if (!isset($GLOBALS['faq_data'][$section_name][$item_name]['answer']) || $this->ln($item['answer']) != $this->ln($GLOBALS['faq_data'][$section_name][$item_name]['answer']))
				{
					$key = '_fsb_faq_[' . $section_name . '][' . $item_name . '][answer]';
					Fsb::$db->insert('langs', array(
						'lang_name' =>		array($this->language, true),
						'lang_key' =>		array($key, true),
						'lang_value' =>		$item['answer'],
					), 'REPLACE', true);
				}
			}
		}
		Fsb::$db->query_multi_insert();
		Fsb::$db->destroy_cache('langs_');

		Log::add(Log::ADMIN, 'lang_log_submit', $this->language);
		Display::message('adm_lang_well_submit', 'index.' . PHPEXT . '?p=general_lang&amp;module=faq&amp;language=' . $this->language, 'general_lang');
	}

	/**
	 * Ajoute une FAQ
	 */
	public function submit_faq_add()
	{
		$section =	Http::request('faq_section', 'post');
		$question = Http::request('faq_question', 'post');
		$answer =	Http::request('faq_answer', 'post');
		$hash =		md5(time());

		if ($question)
		{
			Fsb::$db->insert('langs', array(
				'lang_name' =>		array($this->language, true),
				'lang_key' =>		array('_fsb_faq_[' . $section . '][' . $hash . '][question]', true),
				'lang_value' =>		$question,
			), 'REPLACE');

			Fsb::$db->insert('langs', array(
				'lang_name' =>		array($this->language, true),
				'lang_key' =>		array('_fsb_faq_[' . $section . '][' . $hash . '][answer]', true),
				'lang_value' =>		$answer,
			), 'REPLACE');
			Fsb::$db->destroy_cache('langs_');

			Log::add(Log::ADMIN, 'lang_log_add_faq');
			Display::message('adm_lang_well_add', 'index.' . PHPEXT . '?p=general_lang&amp;module=faq&amp;language=' . $this->language, 'general_lang');
		}
		else
		{
			Http::redirect('index.' . PHPEXT . '?p=general_lang&module=faq');
		}
	}
}

/* EOF */