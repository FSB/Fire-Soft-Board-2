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
 * Page de gestion des themes (fichiers templates, CSS, images ...)
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Mode de la page
	 *
	 * @var string
	 */
	public $mode;
	
	/**
	 * Nom du TPL
	 *
	 * @var string
	 */
	public $tpl_name;
	
	/**
	 * Fichier TPL
	 *
	 * @var string
	 */
	public $file;
	
	/**
	 * Nom de la class CSS
	 *
	 * @var string
	 */
	public $class_name;
	
	/**
	 * Enter description here...
	 *
	 * @var string
	 */
	public $img_name;
	
	/**
	 * Propriete du style
	 *
	 * @var array
	 */
	public $style = array(
		'border_color' =>			'',
		'border_type' =>			'',
		'border_width_unit' =>		'px',
		'border_width_up' =>		0,
		'border_width_right' =>		0,
		'border_width_down' =>		0,
		'border_width_left' =>		0,
		'background_color' =>		'',
		'background_img' =>			'',
		'repeat_img' =>				'no-repeat',
		'bold' =>					'',
		'underline' =>				'',
		'italic' =>					'',
		'font_color' =>				'',
		'font_size' =>				'',
		'font_size_unit' =>			'px',
	);

	/**
	 * Declaration des styles existants
	 *
	 * @var array
	 */
	public $style_exists = array(
		'border-color',
		'border-style',
		'border-width',
		'background-color',
		'background-image',
		'background-repeat',
		'font-weight',
		'text-decoration',
		'font-style',
		'color',
		'font-size',
	);

	/**
	 * Navigation
	 *
	 * @var array
	 */
	public $nav = array();

	/**
	 * Constructeur
	 */
	public function main()
	{
		$this->mode =		Http::request('mode');
		$this->tpl_name =	str_replace('../', './', htmlspecialchars(Http::request('tpl_name')));
		$this->file =		str_replace('../', './', htmlspecialchars(Http::request('file')));
		$this->class_name = htmlspecialchars(Http::request('class_name', 'post|get'));
		$this->img_name =	htmlspecialchars(Http::request('img_name', 'post|get'));

		if (Http::request('choose_class', 'post'))
		{
			$this->class_name = htmlspecialchars(Http::request('choose_class_name', 'post'));
		}

		$call = new Call($this);
		$call->module(array(
			'list' =>		array('tpl', 'extern', 'diff'),
			'url' =>		'index.' . PHPEXT . '?p=general_tpl',
			'lang' =>		'adm_tpl_',
			'default' =>	'tpl',
		));

		$call->post(array(
			'submit_install' =>		'install_tpl',
			'submit_html' =>		'preview_html',
			'submit_php' =>			'preview_php',
			'submit_edit' =>		'submit_edit',
			'change_css_mode' =>	'edit_css',
			'submit_edit_css' =>	'submit_edit_css',
			'submit_edit_img' =>	'submit_edit_img',
			'export_tpl' =>			':page_export_tpl',
			'install_news_tpl' =>	':page_install_from_news',
		));

		$call->functions(array(
			'module' => array(
				'tpl' => array(
					'mode' => array(
						'install_tpl' =>		'page_install_tpl',
						'uncache_tpl' =>		'page_cache_tpl',
						'cache_tpl' =>			'page_cache_tpl',
						'edit_tpl' =>			'page_show_tpl',
						'preview_html' =>		'page_show_tpl',
						'preview_php' =>		'page_show_tpl',
						'edit_css' =>			'page_show_tpl_css',
						'submit_edit' =>		'page_submit_edit',
						'submit_edit_css' =>	'page_submit_edit_css',
						'edit_img' =>			'page_show_tpl_img',
						'submit_edit_img' =>	'page_submit_edit_img',
						'codepress' =>			'page_codepress',
						'css_generator' =>		'page_css_generator',
						'default' =>			'page_default_tpl',
					),
				),
				'extern' => array(
					'mode' => array(
						'default' =>			'page_tpl_news',
					),
				),
				'diff' =>						'page_show_diff',
			),
		));
	}

	/**
	 * Page par defaut de la gestion des themes
	 */
	public function page_default_tpl()
	{
		Fsb::$tpl->set_switch('tpl_list');

		Fsb::$tpl->set_vars(array(
			'USE_FTP' =>		(Fsb::$cfg->get('ftp_default')) ? true : false,
			'LIST_TPL' =>		Html::list_dir('export_tpl_name', '', ROOT . 'tpl/', array(), true),

			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=general_tpl'),
		));

		$this->page_put_tpl();
	}

	/**
	 * Affiche les themes disponibles
	 */
	public function page_put_tpl()
	{
		// Utilisation du theme
		$sql = 'SELECT u_tpl, COUNT(u_tpl) AS total
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id <> ' . VISITOR_ID . '
				GROUP BY u_tpl';
		$result = Fsb::$db->query($sql);
		$used_by = array();
		while ($row = Fsb::$db->row($result))
		{
			$used_by[$row['u_tpl']] = $row['total'];
		}
		Fsb::$db->free($result);

		if ($fd = opendir(ROOT . 'tpl'))
		{
			while ($file = readdir($fd))
			{
				if ($file[0] != '.' && is_dir(ROOT . 'tpl/' . $file) && file_exists(ROOT . 'tpl/' . $file . '/config_tpl.cfg'))
				{
					$config_tpl = Config_file::read(ROOT . 'tpl/' . $file . '/config_tpl.cfg');
					Fsb::$tpl->set_blocks('tpl', array(
						'NAME' =>		$file,
						'AUTHOR' =>		$config_tpl['copyright']['author'],
						'SCREENSHOT' => ROOT . 'tpl/'.Fsb::$session->data['u_tpl'].'/img/' . $config_tpl['img']['screenshot'],
						'WEB' =>		String::parse_website($config_tpl['copyright']['web']),
						'LICENSE' =>	$config_tpl['copyright']['license'],
						'USED_BY' =>	(isset($used_by[$file])) ? $used_by[$file] : 0,

						'U_EDIT_TPL' =>	sid('index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_tpl&amp;tpl_name=' . $file),
						'U_EDIT_CSS' =>	sid('index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_css&amp;tpl_name=' . $file),
						'U_EDIT_IMG' =>	sid('index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_img&amp;tpl_name=' . $file),
					));
					unset($config_tpl);
				}
			}
		}
	}

	/**
	 * Affiche la liste des fichiers templates du theme
	 */
	public function page_show_tpl()
	{
		if ($this->tpl_name == null || !is_dir(ROOT . 'tpl/' . $this->tpl_name))
		{
			Display::message('adm_tpl_not_exists');
		}

		if ($this->file)
		{
			$this->page_edit_tpl();
			return ;
		}

		// Navigation
		$this->nav = array(
			array(
				'name' =>	$this->tpl_name,
				'url' =>	'index.' . PHPEXT . '?p=general_tpl',
			),
			array(
				'name' =>	Fsb::$session->lang('adm_tpl_edit_list'),
			),
		);

		Fsb::$tpl->set_switch('tpl_list_templates');
		$cache = Cache::factory('tpl');

		// On recupere la liste des fichiers template du theme (_root pour placer cette clef au debut)
		$fd = opendir(ROOT . 'tpl/' . $this->tpl_name . '/files');
		$list_tpl = array('_root' => array());
		while ($file = readdir($fd))
		{
			if ($file[0] != '.' && $file != 'index.html')
			{
				if (preg_match('/\.html$/si', $file))
				{
					$list_tpl['_root'][] = array(
						'cache' =>			($cache->exists(md5('tpl/' . $this->tpl_name . '/files/' . $file))) ? true : false,
						'filename' =>		$file,
						'filesize' =>		convert_size(filesize(ROOT . 'tpl/' . $this->tpl_name . '/files/' . $file)),
					);
				}
				else if (is_dir(ROOT . 'tpl/' . $this->tpl_name . '/files/' . $file))
				{
					$fd2 = opendir(ROOT . 'tpl/' . $this->tpl_name . '/files/' . $file);
					while ($file2 = readdir($fd2))
					{
						if ($file2[0] != '.' && $file2 != 'index.html' && preg_match('/\.html$/si', $file2))
						{
							$list_tpl[$file][] = array(
								'cache' =>			($cache->exists(md5('tpl/' . $this->tpl_name . '/files/' . $file . '/' . $file2))) ? true : false,
								'filename' =>		$file . '/' . $file2,
								'filesize' =>		convert_size(filesize(ROOT . 'tpl/' . $this->tpl_name . '/files/' . $file . '/' . $file2)),
							);
						}
					}
					closedir($fd2);
				}
			}
		}
		closedir($fd);

		// On affiche la liste des templates
		ksort($list_tpl);
		foreach ($list_tpl AS $dir => $list)
		{
			Fsb::$tpl->set_blocks('tpl', array(
				'NAME' =>		($dir == '_root') ? $this->tpl_name : $this->tpl_name . '/' . $dir,
			));

			foreach ($list AS $f)
			{
				Fsb::$tpl->set_blocks('tpl.f', array(
					'CACHE' =>		($f['cache']) ? Fsb::$session->lang('adm_tpl_file_uncache') : Fsb::$session->lang('adm_tpl_file_cache'),
					'NAME' =>		$f['filename'],
					'SIZE' =>		$f['filesize'],

					'U_CACHE' =>	sid('index.' . PHPEXT . '?p=general_tpl&amp;mode=' . (($f['cache']) ? 'un' : '') . 'cache_tpl&amp;tpl_name=' . $this->tpl_name . '&amp;file=' . urlencode($f['filename'])),
					'U_EDIT' =>		sid('index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_tpl&amp;tpl_name=' . $this->tpl_name . '&amp;file=' . urlencode($f['filename'])),
				));
			}
		}
	}

	/**
	 * Edite un fichier du theme
	 */
	public function page_edit_tpl()
	{
		// Navigation
		$this->nav = array(
			array(
				'name' =>	$this->tpl_name,
				'url' =>	'index.' . PHPEXT . '?p=general_tpl',
			),
			array(
				'name' =>	Fsb::$session->lang('adm_tpl_edit_list'),
				'url' =>	'index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_tpl&amp;tpl_name=' . $this->tpl_name,
			),
			array(
				'name' =>	sprintf(Fsb::$session->lang('adm_tpl_edit_title'), $this->file),
			),
		);

		$content = Http::request('content');

		Fsb::$tpl->set_switch('tpl_edit_template');

		Fsb::$tpl->set_vars(array(
			'L_EDIT_TITLE' =>		sprintf(Fsb::$session->lang('adm_tpl_edit_title'), $this->file),
			'CONTENT' =>			htmlspecialchars(($content == null) ? file_get_contents(ROOT . 'tpl/' .$this->tpl_name . '/files/' . $this->file) : $content),
			'USE_FTP' =>			(Fsb::$cfg->get('ftp_default')) ? true : false,

			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=general_tpl&amp;tpl_name=' . $this->tpl_name . '&amp;file=' . $this->file),
			'U_CODEPRESS' =>		sid('index.' . PHPEXT . '?p=general_tpl&amp;mode=codepress&amp;tpl_name=' . $this->tpl_name . '&amp;file=' . $this->file),
		));
	}

	/**
	 * Charge le contenu d'un fichier pour l'afficher d'editeur Codepress
	 */
	public function page_codepress()
	{
		if ($this->tpl_name == null || !is_dir(ROOT . 'tpl/' . $this->tpl_name))
		{
			Display::message('adm_tpl_not_exists');
		}

		// Language d'affichage pour CodePress
		$language = 'php';

		Fsb::$tpl->set_file('codepress.html');
		Fsb::$tpl->set_vars(array(
			'CODEPRESS_LANGUAGE' =>		$language,
		));
	}

	/**
	 * Sauvegarde mes modifications effectuees sur un fichier template
	 */
	public function page_submit_edit()
	{
		$file = File::factory(Http::request('use_ftp', 'post'));

		$content = Http::request('content', 'post');
		$file->write('tpl/' . $this->tpl_name . '/files/' . $this->file, $content);

		Log::add(Log::ADMIN, 'tpl_log_edit', ROOT . 'tpl/' . $this->tpl_name . '/files/' . $this->file);
		Display::message('adm_tpl_well_edit', 'index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_tpl&amp;tpl_name=' . $this->tpl_name, 'general_tpl2');
	}

	/**
	 * Met en cache un fichier template
	 */
	public function page_cache_tpl()
	{
		if ($this->tpl_name == null || !is_dir(ROOT . 'tpl/' . $this->tpl_name))
		{
			Display::message('adm_tpl_not_exists');
		}

		if ($this->file == null || !is_file(ROOT . 'tpl/' . $this->tpl_name . '/files/' . $this->file))
		{
			Display::message('adm_tpl_file_not_exists');
		}

		$cache = Cache::factory('tpl');
		$hash = md5('tpl/' . $this->tpl_name . '/files/' . $this->file);
		if ($this->mode == 'cache_tpl')
		{
			$new_tpl = new Tpl(ROOT . 'tpl/' . $this->tpl_name . '/files/');
			$new_tpl->set_file($this->file);
			$code = $new_tpl->compile();
			$cache->put($hash, $code, '', filemtime($new_tpl->data['main']['file']));
			unset($code);
		}
		else
		{
			$cache->delete($hash);
		}
		$this->file = null;

		Display::message('adm_tpl_well_' . $this->mode, 'index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_tpl&amp;tpl_name=' . $this->tpl_name, 'general_tpl2');
	}

	/**
	 * Affiche les classes disponibles dans la css main.css du theme
	 */
	public function page_show_tpl_css()
	{
		if ($this->tpl_name == null || !is_dir(ROOT . 'tpl/' . $this->tpl_name))
		{
			Display::message('adm_tpl_not_exists');
		}

		if ($this->class_name)
		{
			$this->page_edit_tpl_css();
			return;
		}

		// Navigation
		$this->nav = array(
			array(
				'name' =>	$this->tpl_name,
				'url' =>	'index.' . PHPEXT . '?p=general_tpl',
			),
			array(
				'name' =>	Fsb::$session->lang('adm_tpl_css_list'),
			),
		);

		Fsb::$tpl->set_switch('tpl_list_css');

		$css = new Css();
		$css->load_file(ROOT . 'tpl/' . $this->tpl_name . '/main.css');
		foreach ($css->data AS $filename => $filedata)
		{
			Fsb::$tpl->set_blocks('file', array(
				'NAME' =>	$filename,

				'U_EDIT' =>	sid('index.' . PHPEXT . '?p=tools_webftp&amp;mode=edit&amp;dir=tpl/' . $this->tpl_name . '/&amp;file=' . $filename),
			));

			foreach ($filedata AS $i => $classinfo)
			{
				Fsb::$tpl->set_blocks('file.css', array(
					'NAME' =>		$classinfo['name'],
					'DESC' =>		htmlspecialchars($classinfo['comments']),

					'U_EDIT' =>		sid('index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_css&amp;tpl_name=' . $this->tpl_name . '&amp;class_name=' . $filename . '&amp;id=' . $i),
				));
			}
		}
	}

	/**
	 * Affiche la page d'edition d'une classe de la feuille de style
	 */
	public function page_edit_tpl_css()
	{
		$id = intval(Http::request('id'));

		// On recupere la classe CSS
		$css = new Css();
		$css->load_file(ROOT . 'tpl/' . $this->tpl_name . '/' . $this->class_name);

		if (!isset($css->data[$this->class_name], $css->data[$this->class_name][$id]))
		{
			Display::message('adm_tpl_not_exists');
		}
		$class_data = $css->data[$this->class_name][$id];

		// Navigation
		$this->nav = array(
			array(
				'name' =>	$this->tpl_name,
				'url' =>	'index.' . PHPEXT . '?p=general_tpl',
			),
			array(
				'name' =>	Fsb::$session->lang('adm_tpl_css_list'),
				'url' =>	'index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_css&amp;tpl_name=' . $this->tpl_name,
			),
			array(
				'name' =>	sprintf(Fsb::$session->lang('adm_css_edit_class'), $class_data['name']),
			),
		);

		Fsb::$tpl->set_switch('tpl_edit_css');

		$preview_css =		Http::request('preview_css', 'post');
		$edit_css_type =	Http::request('edit_css_type');
		if ($edit_css_type == null)
		{
			$edit_css_type = 'simple';
		}

		// On recupere les classes de la CSS pour la liste
		$list_class_ary = array();
		foreach ($css->data[$this->class_name] AS $class)
		{
			$list_class_ary[] = $class['name'];
		}
		
		// Previsualisation de la CSS ?
		if ($preview_css)
		{
			if ($edit_css_type == 'complex')
			{
				$preview_style = htmlspecialchars(Http::request('content', 'post'));
			}
			else
			{
				$preview_style = $this->page_get_css_content();
			}
		
			Fsb::$tpl->set_switch('preview');
			Fsb::$tpl->set_vars(array(
				'PREVIEW_STYLE' =>	str_replace(array('\r\n', '\n'), array(' ', ' '), $preview_style),
			));
		}

		$list_css_mode = array(
			'simple' =>			Fsb::$session->lang('adm_css_simple_mode'),
			'complex' =>		Fsb::$session->lang('adm_css_complex_mode'),
		);

		$list_change_mode = Html::make_list('edit_css_type', $edit_css_type, $list_css_mode, array(
			'onchange' => 'location.href=\'' . sid('index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_css&amp;tpl_name=' . $this->tpl_name . '&amp;class_name=' . $this->class_name . '&amp;id=' . $id) . '&amp;edit_css_type=\' + this.value;',
		));

		$list_class = Html::make_list('choose_class_name', $id, $list_class_ary, array(
			'onchange' => 'location.href=\'' . sid('index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_css&amp;tpl_name=' . $this->tpl_name . '&amp;edit_css_type=' . $edit_css_type . '&amp;class_name=' . $this->class_name) . '&amp;id=\' + this.value;',
		));

		// Champs caches
		$hidden = Html::hidden('edit_css_type_submit', $edit_css_type) . Html::hidden('tpl_name', $this->tpl_name) . Html::hidden('class_name', $this->class_name);

		Fsb::$tpl->set_vars(array(
			'LIST_MODE' =>		$list_change_mode,
			'LIST_CSS' =>		$list_class,
			'HIDDEN' =>			$hidden,
			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_css&amp;tpl_name=' . $this->tpl_name . '&amp;class_name=' . $this->class_name . '&amp;id=' . $id),
		));

		if ($edit_css_type == 'complex')
		{
			$content = (Http::request('preview_css', 'post')) ? $preview_style : $css->get_properties($class_data);
			Fsb::$tpl->set_vars(array(
				'CONTENT' =>		$content,
			));
		}
		else
		{
			// Affiche l'editeur de CSS
			$list_size = array('px' => 'px', 'pt' => 'pt', 'em' => 'em', '%' => '%');
			$list_border_type = array(
				'solid' =>		Fsb::$session->lang('adm_css_border_type_solid'),
				'dashed' =>		Fsb::$session->lang('adm_css_border_type_dashed'),
				'dotted' =>		Fsb::$session->lang('adm_css_border_type_dotted'),
				'double' =>		Fsb::$session->lang('adm_css_border_type_double'),
			);

			$list_repeat = array(
				'no-repeat' =>		Fsb::$session->lang('adm_css_no_repeat'),
				'repeat' =>			Fsb::$session->lang('adm_css_repeat'),
				'repeat-x' =>		Fsb::$session->lang('adm_css_repeat_x'),
				'repeat-y' =>		Fsb::$session->lang('adm_css_repeat_y'),
			);

			$parse_style = $this->page_check_css_style($class_data, (Http::request('preview_css', 'post')) ? $preview_style : null);

			Fsb::$tpl->set_switch('simple_mode');
			Fsb::$tpl->set_vars(array(
				'BACKGROUND_COLOR' =>		$this->style['background_color'],
				'BORDER_COLOR' =>			$this->style['border_color'],
				'BORDER_WIDTH_UP' =>		$this->style['border_width_up'],
				'BORDER_WIDTH_DOWN' =>		$this->style['border_width_down'],
				'BORDER_WIDTH_LEFT' =>		$this->style['border_width_left'],
				'BORDER_WIDTH_RIGHT' =>		$this->style['border_width_right'],
				'FONT_STYLE_BOLD' =>		(($this->style['bold']) ? 'checked="checked"' : ''),
				'FONT_STYLE_UNDERLINE' =>	(($this->style['underline']) ? 'checked="checked"' : ''),
				'FONT_STYLE_ITALIC' =>		(($this->style['italic']) ? 'checked="checked"' : ''),
				'FONT_COLOR' =>				$this->style['font_color'],
				'FONT_SIZE' =>				$this->style['font_size'],

				'LIST_BACKGROUND_IMG' =>	Html::list_dir('background_img', $this->style['background_img'], ROOT . 'tpl/' . $this->tpl_name . '/img/', array('gif', 'jpg', 'jpeg'), false, '<option value="0">' . Fsb::$session->lang('adm_css_no_img') . '</option>'),
				'LIST_REPEAT_IMG' =>		Html::make_list('repeat_img', $this->style['repeat_img'], $list_repeat),
				'LIST_FONT_SIZE' =>			Html::make_list('font_size_unit', $this->style['font_size_unit'], $list_size),
				'LIST_BORDER_WIDTH' =>		Html::make_list('border_width_unit', $this->style['border_width_unit'], $list_size),
				'LIST_BORDER_TYPE' =>		Html::make_list('border_type', $this->style['border_type'], $list_border_type),
			));

			foreach ($parse_style AS $key => $value)
			{
				if (!in_array($key, $this->style_exists))
				{
					Fsb::$tpl->set_switch('other_style');
					Fsb::$tpl->set_blocks('other', array(
						'L_NAME' =>	$key,
						
						'NAME' =>		'other_' . $key,
						'VALUE' =>		$value,
					));
				}
			}
		}
	}

	/**
	 * Recupere dans un tableau de donnees valide les proprietes de la classe
	 *
	 * @param mixed $class_data Donnees de la class CSS
	 * @param string $content Contenu du fichier CSS
	 * @return array Propriete CSS
	 */
	public function page_check_css_style($class_data, $content = null)
	{
		$css = new Css();
		$css->load_file(ROOT . 'tpl/' . $this->tpl_name . '/' . $this->class_name);
		if ($content != null)
		{
			$p = $css->parse_properties($content);
		}
		else
		{
			$p = $class_data['properties'];
		}

		// Couleur de la bordure
		if (isset($p['border-color']))
		{
			$this->style['border_color'] = $p['border-color'];
		}

		// Style de la bordure
		if (isset($p['border-style']))
		{
			$this->style['border_type'] = $p['border-style'];
		}

		// Utilisation de la balise border
		if (isset($p['border']))
		{
			$tmp = explode(' ', $p['border']);
			$border_style = array('none', 'solid', 'dashed', 'dotted', 'groove', 'double', 'ridge', 'inset', 'outset', 'hidden');

			foreach ($tmp AS $value)
			{
				if (in_array($value, $border_style))
				{
					$this->style['border_type'] = $value;
				}
				else if (preg_match('#([0-9]+)(px|em|%|pt)#i', $value, $match))
				{
					$this->style['border_width_unit'] = $match[2];
					$this->style['border_width_up'] = intval($match[1]);
					$this->style['border_width_right'] = intval($match[1]);
					$this->style['border_width_down'] = intval($match[1]);
					$this->style['border_width_left'] = intval($match[1]);
				}
				else
				{
					$this->style['border_color'] = $value;
				}
			}
			unset($tmp, $p['border']);
		}

		// Largeur de la bordure
		if (isset($p['border-width']))
		{
			$tmp = String::split(' ', $p['border-width']);
			preg_match('/([0-9]+)([a-zA-Z%]+)/i', $tmp[0], $match);
			$this->style['border_width_unit'] = $match[2];
			$this->style['border_width_up'] = intval($tmp[0]);
			$this->style['border_width_right'] = intval($tmp[1]);
			$this->style['border_width_down'] = intval($tmp[2]);
			$this->style['border_width_left'] = intval($tmp[3]);
			unset($tmp);
		}

		// Couleur de l'ariere plan
		if (isset($p['background-color']))
		{
			$this->style['background_color'] = $p['background-color'];
		}

		// Image d'arriere plan
		if (isset($p['background-image']))
		{
			$this->style['background_img'] = preg_replace('#url\((\'|")?(.*?)(\'|")?\)#i', '$2', $p['background-image']);
			$this->style['background_img'] = preg_replace('#^img/#', '', $this->style['background_img']);
		}

		// Repetition de l'image en arriere plan
		if (isset($p['background-repeat']))
		{
			$this->style['background_repeat'] = $p['background-repeat'];
		}

		// Texte gras
		if (isset($p['font-weight']))
		{
			$this->style['bold'] = true;
		}

		// Texte souligne
		if (isset($p['text-decoration']) && $p['text-decoration'] == 'underline')
		{
			$this->style['underline'] = true;
		}

		// Texte italique
		if (isset($p['font-style']))
		{
			$this->style['italic'] = true;
		}

		// Couleur du texte
		if (isset($p['color']))
		{
			$this->style['font_color'] = $p['color'];
		}

		// Taille du texte
		if (isset($p['font-size']))
		{
			preg_match('/([0-9]+)([a-zA-Z%]+)/i', $p['font-size'], $match);
			$this->style['font_size'] = intval($match[1]);
			$this->style['font_size_unit'] = $match[2];
		}
		
		return ($p);
	}

	/**
	 * Soumet les modifications de la CSS
	 */
	public function page_submit_edit_css()
	{
		$edit_css_type = Http::request('edit_css_type_submit', 'post');
		if ($edit_css_type == null)
		{
			$edit_css_type = 'simple';
		}

		$css = new Css();
		$css->load_file(ROOT . 'tpl/' . $this->tpl_name . '/' . $this->class_name);

		$id = intval(Http::request('id'));
		if (!isset($css->data[$this->class_name], $css->data[$this->class_name][$id]))
		{
			Display::message('adm_tpl_not_exists');
		}

		if ($edit_css_type == 'complex')
		{
			$content = htmlspecialchars(Http::request('content', 'post'));
		}
		else
		{
			$content = $this->page_get_css_content();
		}

		$css->data[$this->class_name][$id]['properties'] = $css->parse_properties($content);
		$css->write(ROOT . 'tpl/' . $this->tpl_name . '/', $this->class_name);

		Log::add(Log::ADMIN, 'css_log_edit', ROOT . 'tpl/' . $this->tpl_name . '/' . $this->class_name . ' :: ' . $css->data[$this->class_name][$id]['name']);
		Display::message('adm_css_well_edit', 'index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_css&amp;tpl_name=' . $this->tpl_name, 'general_tpl2');
	}

	/**
	 * Renvoie le contenu de la classe en fonction des choix du mode simple
	 *
	 * @return string Contenue du fichier CSS
	 */
	public function page_get_css_content()
	{
		foreach ($this->style AS $key => $value)
		{
			$$key = Http::request($key, 'post');
			$$key = (gettype($value) == 'int' || gettype($value) == 'bool') ? intval($$key) : htmlspecialchars($$key);
		}

		$content = '';
		$border_style_exists = false;
		if ($border_width_up > 0 || $border_width_right > 0 || $border_width_down > 0 || $border_width_left > 0)
		{
			$border_style_exists = true;
			$content .= "border-width: ${border_width_up}${border_width_unit} ${border_width_right}${border_width_unit} ${border_width_down}${border_width_unit} ${border_width_right}${border_width_unit};" . EOF;
		}

		if (!empty($border_color) && $border_style_exists)
		{
			$content .= "border-color: $border_color;" . EOF;
		}

		if (!empty($border_type) && $border_style_exists)
		{
			$content .= "border-style: $border_type;" . EOF;
		}

		if (!empty($background_color))
		{
			$content .= "background-color: $background_color;" . EOF;
		}

		if (!empty($background_img))
		{
			$content .= "background-image: url(img/$background_img);" . EOF;
		}

		if (!empty($repeat_img) && !empty($background_img))
		{
			$content .= "background-repeat: $repeat_img;" . EOF;
		}

		if ($bold)
		{
			$content .= "font-weight: bold;" . EOF;
		}

		if ($underline)
		{
			$content .= "text-decoration: underline;" . EOF;
		}

		if ($italic)
		{
			$content .= "font-style: italic;" . EOF;
		}

		if (!empty($font_color))
		{
			$content .= "color: $font_color;" . EOF;
		}

		if (!empty($font_size))
		{
			$content .= "font-size: ${font_size}${font_size_unit};" . EOF;
		}
		
		// Ajout des autres styles
		foreach ($_POST AS $key => $value)
		{
			if (preg_match('/^other_(.*?)$/i', $key, $match) && strlen($value))
			{
				$content .= $match[1] . ': ' . $value . ';';
			}
		}

		return ($content);
	}

	/**
	 * Upload un template et tente de le decompresser
	 */
	public function page_install_tpl()
	{
		// Upload du theme sur le serveur
		if (!$this->tpl_name = Http::request('upload_tpl', 'post'))
		{
			$upload = new Upload('upload_tpl');
			$upload->allow_ext(array('zip', 'tar', 'gz', 'xml'));
			$this->tpl_name = $upload->store(ROOT . 'tpl/');

			// Cette ligne permettra de mettre en champ cache le template si on utilise une connexion FTP
			$_POST['upload_tpl'] = $this->tpl_name;
		}

		// Instance de l'un objet File() pour la decompression
		$file = File::factory(Http::request('use_ftp'));

		// Decompression des fichiers
		$compress = new Compress('tpl/' . $this->tpl_name, $file);
		$compress->extract('tpl/');
		@unlink(ROOT . 'tpl/' . $this->tpl_name);

		Display::message('adm_tpl_install_success', 'index.' . PHPEXT . '?p=general_tpl', 'general_tpl');
	}

	/**
	 * Exporte un theme et lance le telechargement
	 */
	public function page_export_tpl()
	{
		$ext =		trim(Http::request('export_tpl_ext'));
		$tpl_name = trim(Http::request('export_tpl_name'));

		// On recupere le fichier compresse
		$compress = new Compress('.' . $ext);
		$compress->add_file('tpl/' . $tpl_name . '/', 'tpl/');
		$content = $compress->write(true);

		// On lance le telechargement sur le navigateur
		Http::download($tpl_name . '.' . $ext, $content);
	}

	/**
	 * Affiche la liste des images du theme
	 */
	public function page_show_tpl_img()
	{
		if ($this->tpl_name == null || !is_dir(ROOT . 'tpl/' . $this->tpl_name))
		{
			Display::message('adm_tpl_not_exists');
		}

		if ($this->img_name)
		{
			$this->page_edit_tpl_img();
			return ;
		}

		// Navigation
		$this->nav = array(
			array(
				'name' =>	$this->tpl_name,
				'url' =>	'index.' . PHPEXT . '?p=general_tpl',
			),
			array(
				'name' =>	Fsb::$session->lang('adm_img_list'),
			),
		);
		
		Fsb::$tpl->set_switch('tpl_img_list');
		
		$config_tpl = Config_file::read(ROOT . 'tpl/' . $this->tpl_name. '/config_tpl.cfg');
		foreach ($config_tpl['img'] AS $key => $value)
		{
			if (!preg_match('#^USER_LANGUAGE/#', $value))
			{
				Fsb::$tpl->set_blocks('img', array(
					'NAME' =>		$key,
					
					'U_EDIT' =>		sid('index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_img&amp;tpl_name=' . $this->tpl_name . '&amp;img_name=' . $key),
				));
			}
		}
	}

	/**
	 * Affiche la page d'edition d'une image
	 */
	public function page_edit_tpl_img()
	{
		// Navigation
		$this->nav = array(
			array(
				'name' =>	$this->tpl_name,
				'url' =>	'index.' . PHPEXT . '?p=general_tpl',
			),
			array(
				'name' =>	Fsb::$session->lang('adm_img_list'),
				'url' =>	'index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_img&amp;tpl_name=' . $this->tpl_name,
			),
			array(
				'name' =>	Fsb::$session->lang('adm_img_edit'),
			),
		);
		
		Fsb::$tpl->set_switch('tpl_edit_img');
		
		$config_tpl = Config_file::read(ROOT . 'tpl/' . $this->tpl_name. '/config_tpl.cfg');
		
		if (!isset($config_tpl['img'][$this->img_name]) || preg_match('#^USER_LANGUAGE/#', $config_tpl['img'][$this->img_name]))
		{
			Display::message('adm_img_not_exists');
		}
		
		Fsb::$tpl->set_vars(array(
			'LINK' =>			$config_tpl['img'][$this->img_name],
			'LINK_SHOW' =>		ROOT . 'tpl/' . $this->tpl_name . '/img/' . $config_tpl['img'][$this->img_name],
			'KEY_NAME' =>		$this->img_name,
			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_img&amp;tpl_name=' . $this->tpl_name . '&amp;img_name=' . $this->img_name),
		));
	}

	/**
	 * Modifie les donnees d'une image
	 */
	public function page_submit_edit_img()
	{
		$link_img = Http::request('link_img', 'post');

		// Upload d'une image depuis le PC
		if (!empty($_FILES['upload_img']['name']))
		{
			$upload = new Upload('upload_img');
			$upload->only_img();
			$link_img = $upload->store(ROOT . 'tpl/' . $this->tpl_name. '/img/');
		}
		// Upload d'une image depuis une URL distante
		else if (preg_match('#(http|https|ftp)://(.*?\.(' . implode('|', Upload::$img) . '))#i', $link_img, $match))
		{
			$link_img = basename($match[2]);
			if (!$img_content = @file_get_contents($match[0]))
			{
				Display::message('adm_tpl_unable_upload_img');
			}

			if (!$fd = @fopen(ROOT . 'tpl/' . $this->tpl_name . '/img/' . $link_img, 'w'))
			{
				Display::message(sprintf(Fsb::$session->lang('fopen_error'), ROOT . 'tpl/' . $this->tpl_name . '/img/' . $link_img));
			}
			fwrite($fd, $img_content);
			fclose($fd);
		}

		$config_tpl = Config_file::read(ROOT . 'tpl/' . $this->tpl_name. '/config_tpl.cfg');
		if (!isset($config_tpl['img'][$this->img_name]))
		{
			Display::message('adm_img_not_exists');
		}
		
		$config_tpl['img'][$this->img_name] = $link_img;
		Config_file::write(ROOT . 'tpl/' . $this->tpl_name . '/config_tpl.cfg', $config_tpl);
		
		Log::add(Log::ADMIN, 'img_log_edit', $this->img_name);
		Display::message('adm_img_well_edit', 'index.' . PHPEXT . '?p=general_tpl&amp;mode=edit_img&amp;tpl_name=' . $this->tpl_name, 'general_tpl2');
	}

	/**
	 * Affiche la liste des derniers themes
	 */
	public function page_tpl_news()
	{
		Fsb::$tpl->set_switch('tpl_streaming');

		// On recupere les news
		$news = Http::get_file_on_server(FSB_REQUEST_SERVER, FSB_REQUEST_TPL_NEWS, 10);
		if (!$news)
		{
			Display::message('adm_tpl_failed_open_stream');
		}

		// On parse le fichier XML des news et on les affiche
		$xml = new Xml();
		$xml->load_content($news);

		if ($xml->document->childExists('tpl'))
		{
			foreach ($xml->document->tpl AS $item)
			{
				$tpl_name = $xml->get_attr('name', $item);
				Fsb::$tpl->set_blocks('news', array(
					'TPL_NAME' =>		$tpl_name,
					'THUMB' =>			$item->screenshot[0]->thumb[0]->getData(),
					'IMG' =>			$item->screenshot[0]->full[0]->getData(),
					'DESC' =>			String::unhtmlspecialchars($item->description[0]->getData()),
					'TITLE' =>			$item->title[0]->getData(),
					'AUTHOR' =>			$item->author[0]->name[0]->getData(),
					'WEBSITE' =>		$item->author[0]->website[0]->getData(),
					'EMAIL' =>			$item->author[0]->email[0]->getData(),
					'COPYRIGHT' =>		$item->author[0]->copyright[0]->getData(),
					'TPL_EXISTS' =>		(is_dir(ROOT . 'tpl/' . $tpl_name)) ? true : false,

					'U_DOWNLOAD' =>		$item->download[0]->direct[0]->getData(),
					'U_INSTALL' =>		$item->download[0]->indirect[0]->getData(),
				));
			}
		}

		Fsb::$tpl->set_vars(array(
			'USE_FTP' =>		(Fsb::$cfg->get('ftp_default')) ? true : false,

			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=general_tpl&amp;module=extern'),
		));
	}

	/**
	 * Installe le theme a partir du site officiel
	 */
	public function page_install_from_news()
	{
		$url = key(Http::request('install_news_tpl', 'post'));
		if ($url)
		{
			// Instance d'un objet File() pour la decompression
			$file = File::factory(Http::request('use_ftp'));

			$tpl_name = basename($url);
			$content = Http::get_file_on_server(FSB_REQUEST_SERVER, $url, 10);
			if (!$content)
			{
				Display::message('adm_tpl_stream_not_exists');
			}

			// On copie le contenu
			$file->write('tpl/' . $tpl_name, $content);

			// Decompression du theme
			$compress = new Compress('tpl/' . $tpl_name, $file);
			$compress->extract('tpl/');
			@unlink(ROOT . 'tpl/' . $tpl_name);

			Display::message('adm_tpl_news_well_install', 'index.' . PHPEXT . '?p=general_tpl&amp;module=extern', 'general_tpl');
		}
	}

	/**
	 * Afficher les differences entre deux themes
	 */
	public function page_show_diff()
	{
		Fsb::$tpl->set_switch('tpl_diff');

		$wrap = intval(Http::request('wrap', 'post'));
		if (!$wrap)
		{
			$wrap = true;
		}

		$filter = intval(Http::request('filter', 'post'));
		if (!$filter)
		{
			$filter = false;
		}

		// Theme a "gauche"
		$tpl_src = Http::request('tpl_src', 'post');
		if (!$tpl_src)
		{
			$tpl_src = Fsb::$session->data['u_tpl'];
		}

		if ($tpl_src[strlen($tpl_src) - 1] != '/')
		{
			$tpl_src .= '/';
		}

		if (!file_exists(ROOT . 'tpl/' . $tpl_src))
		{
			Display::message(sprintf(Fsb::$session->lang('adm_tpl_diff_not_exists'), $tpl_src));
		}

		// Theme a "droite"
		$tpl_dst = Http::request('tpl_dst', 'post');
		if (!$tpl_dst)
		{
			$tpl_dst = Fsb::$session->data['u_tpl'];
		}

		if ($tpl_dst[strlen($tpl_dst) - 1] != '/')
		{
			$tpl_dst .= '/';
		}

		if (!file_exists(ROOT . 'tpl/' . $tpl_dst))
		{
			Display::message(sprintf(Fsb::$session->lang('adm_tpl_diff_not_exists'), $tpl_dst));
		}

		// Fichiers a comparer
		$list_file = (array) Http::request('list_file', 'post');

		// Lancement du diff
		if (Http::request('submit_diff', 'post'))
		{
			foreach ($list_file AS $filename)
			{
				$filename = str_replace('../', './', $filename);
				$diff = new Diff();
				$diff->load_file(ROOT . 'tpl/' . $tpl_src . '/' . $filename, ROOT . 'tpl/' . $tpl_dst . '/' . $filename, true);

				// Afficher les fichiers uniquement si modification ?
				if ($filter)
				{
					$diff_exists = false;
					foreach ($diff->entries AS $data)
					{
						if ($data['state'] != Diff::EQUAL)
						{
							$diff_exists = true;
							break;
						}
					}

					if (!$diff_exists)
					{
						continue;
					}
				}

				Fsb::$tpl->set_blocks('file', array(
					'FILENAME' =>	$filename,
				));

				// Affichage du diff
				foreach ($diff->entries AS $data)
				{
					if (!$data['file1'] && !$data['file2'])
					{
						continue;
					}
					$file1 = $diff->format($data['file1'], $wrap);
					$file2 = $diff->format($data['file2'], $wrap);

					switch ($data['state'])
					{
						case Diff::EQUAL :
							$class1 = 'diff_equal';
							$class2 = 'diff_equal';
						break;

						case Diff::CHANGE :
							$class1 = 'diff_change';
							$class2 = 'diff_change';
						break;

						case Diff::DROP :
							$class1 = 'diff_drop';
							$class2 = 'diff_equal';
						break;

						case Diff::ADD :
							$class1 = 'diff_equal';
							$class2 = 'diff_add';
						break;
					}

					Fsb::$tpl->set_blocks('file.diff', array(
						'FILE1' =>		$file1,
						'FILE2' =>		$file2,
						'CLASS1' =>		$class1,
						'CLASS2' =>		$class2,
					));
				}
				unset($diff);
			}
		}

		// Generation des listes des templates
		$path = ROOT . 'tpl/' . $tpl_src . '/';
		$fd = opendir($path);
		$list = '<select name="list_file[]" multiple="multiple" size="10"><optgroup label="' . Fsb::$session->lang('adm_tpl_diff_name') . '">';
		while ($file = readdir($fd))
		{
			if ($file[0] != '.')
			{
				if (is_dir($path . $file) && $file == 'files')
				{
					$path_file = $path . 'files/';
					$fd_file = opendir($path_file);
					$list .= '<optgroup label=" &nbsp; files/">';
					while ($file2 = readdir($fd_file))
					{
						if ($file2[0] != '.')
						{
							if (is_dir($path_file . $file2))
							{
								$path_subfile = $path . 'files/' . $file2 . '/';
								$fd_subfile = opendir($path_subfile);
								$list .= '<optgroup label=" &nbsp; &nbsp; files/' . $file2 . '/">';
								while ($file3 = readdir($fd_subfile))
								{
									if ($file3[0] != '.')
									{
										$ext = get_file_data($file3, 'extension');
										if (in_array($ext, array('css', 'php', 'html')))
										{
											$selected = (in_array('files/' . $file2 . '/' . $file3, $list_file)) ? 'selected="selected"' : '';
											$list .= '<option value="files/' . $file2 . '/' . $file3 . '" ' . $selected . '> &nbsp; &nbsp; &nbsp; ' . $file3 . '</option>';
										}
									}
								}
								$list .= '</optgroup>';
								closedir($fd_subfile);
							}
							else
							{
								$ext = get_file_data($file2, 'extension');
								if (in_array($ext, array('css', 'php', 'html')))
								{
									$selected = (in_array('files/' . $file2, $list_file)) ? 'selected="selected"' : '';
									$list .= '<option value="files/' . $file2 . '" ' . $selected . '> &nbsp; ' . $file2 . '</option>';
								}
							}
						}
					}
					$list .= '</optgroup>';
					closedir($fd_file);
				}
				else
				{
					$ext = get_file_data($file, 'extension');
					if (in_array($ext, array('css', 'php', 'html')))
					{
						$selected = (in_array($file, $list_file)) ? 'selected="selected"' : '';
						$list .= '<option value="' . $file . '" ' . $selected . '>' . $file . '</option>';
					}
				}
			}
		}
		$list .= '</optgroup></select>';
		closedir($fd);

		// Variables de templates
		Fsb::$tpl->set_vars(array(
			'LIST_TPL1' =>		Html::list_dir('tpl_src', substr($tpl_src, 0, -1), ROOT . 'tpl/', array(), true),
			'LIST_TPL2' =>		Html::list_dir('tpl_dst', substr($tpl_dst, 0, -1), ROOT . 'tpl/', array(), true),
			'LIST_FILES' =>		$list,
			'AUTO_WRAP' =>		$wrap,
			'FILTER' =>			$filter,
		));
	}

	/**
	 * Generateur de CSS simplifie
	 */
	public function page_css_generator()
	{
		if (Http::request('submit_css_generator', 'post'))
		{
			// Generation du style
			$style = $this->page_get_css_content();
			echo preg_replace("#[\r\n]#i", ' ', htmlspecialchars($style));
			exit;
		}

		// Affichage de l'editeur
		$list_size = array('px' => 'px', 'pt' => 'pt', 'em' => 'em', '%' => '%');

		Fsb::$tpl->set_file('css_generator.html');
		Fsb::$tpl->set_vars(array(
			'OPENER_ID' =>			htmlspecialchars(Http::request('id')),
			'RADIO_ID' =>			htmlspecialchars(Http::request('radio')),
			'LIST_FONT_SIZE' =>		Html::make_list('font_size_unit', 'px', $list_size),
		));
	}
}

/* EOF */