<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/forum/forum_info.php
** | Begin :	26/06/2007
** | Last :		23/02/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Affiche une liste d'informations sur le forum
*/
class Fsb_frame_child extends Fsb_frame
{
	// Paramètres d'affichage de la page (barre de navigation, boite de stats)
	public $_show_page_header_nav = TRUE;
	public $_show_page_footer_nav = FALSE;
	public $_show_page_stats = FALSE;

	public function main()
	{
		// On récupère le module du panneau à afficher
		$this->module = Http::request('module');

		// Liste des informations disponibles
		$list = array('fsb', 'admin', 'rank', 'tpl', 'lang', 'mod');
		if (!in_array($this->module, $list))
		{
			$this->module = 'fsb';
		}

		Fsb::$tpl->set_file('forum/forum_info.html');
		foreach ($list AS $item)
		{
			Fsb::$tpl->set_blocks('module', array(
				'IS_SELECT' =>	($this->module == $item) ? TRUE : FALSE,
				'URL' =>		sid(ROOT . 'index.' . PHPEXT . '?p=info&amp;module=' . $item),
				'NAME' =>		Fsb::$session->lang('info_module_' . $item),
			));

			if ($this->module == $item)
			{
				Fsb::$tpl->set_switch('show_info_' . $item);
				$this->{'show_info_' . $item}();
			}
		}

		Fsb::$tpl->set_vars(array(
			'MENU_HEADER_TITLE' =>	Fsb::$session->lang('info_panel'),
		));
	}

	/*
	** Informations sur FSB (engagez vous qu'ils disaient :=))
	*/
	public function show_info_fsb()
	{
	}

	/*
	** Liste des rangs sur le forum
	*/
	public function show_info_rank()
	{
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'ranks
				ORDER BY rank_special, rank_quota, rank_name';
		$result = Fsb::$db->query($sql, 'ranks_');
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('rank', array(
				'NAME' =>		htmlspecialchars($row['rank_name']),
				'STYLE' =>		$row['rank_color'],
				'IMG' =>		($row['rank_img']) ? RANK_PATH . $row['rank_img'] : '',
				'QUOTA' =>		($row['rank_special']) ? Fsb::$session->lang('info_rank_special') : sprintf(Fsb::$session->lang('info_rank_quota'), $row['rank_quota']),
			));
		}
		Fsb::$db->free($result);
	}

	/*
	** Liste des thèmes sur le forum
	*/
	public function show_info_tpl()
	{
		$fd = opendir(ROOT . 'tpl/');
		while ($file = readdir($fd))
		{
			if ($file[0] != '.' && is_dir(ROOT . 'tpl/' . $file))
			{
				$config_tpl = Config_file::read(ROOT . 'tpl/' . $file . '/config_tpl.cfg');
				Fsb::$tpl->set_blocks('tpl', array(
					'NAME' =>		$file,
					'AUTHOR' =>		$config_tpl['copyright']['author'],
					'WEB' =>		String::parse_website($config_tpl['copyright']['web']),
					'EMAIL' =>		String::parse_email($config_tpl['copyright']['email']),
					'LICENSE' =>	$config_tpl['copyright']['license'],
				));
			}
		}
		closedir($fd);
	}

	/*
	** Liste des MODS sur le forum
	*/
	public function show_info_mod()
	{
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'mods
				WHERE mod_type = ' . Mods::EXTERN . '
				ORDER BY mod_real_name';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('mod', array(
				'NAME' =>			htmlspecialchars($row['mod_real_name']),
				'DESCRIPTION' =>	nl2br(htmlspecialchars($row['mod_description'])),
				'AUTHOR' =>			htmlspecialchars($row['mod_author']),
				'EMAIL' =>			htmlspecialchars($row['mod_email']),
				'WEBSITE' =>		htmlspecialchars($row['mod_website']),
			));
		}
		Fsb::$db->free($result);
	}

	/*
	** Liste des administrateurs / modérateurs
	*/
	public function show_info_admin()
	{
		$sql = 'SELECT u_id, u_nickname, u_color, u_auth
				FROM ' . SQL_PREFIX . 'users
				WHERE u_auth >= ' . MODO . '
					AND u_id <> ' . VISITOR_ID . '
				ORDER BY u_auth DESC, u_nickname';
		$result = Fsb::$db->query($sql);
		$a = NULL;
		while ($row = Fsb::$db->row($result))
		{
			if ($a !== $row['u_auth'])
			{
				Fsb::$tpl->set_blocks('auth', array(
					'LANG' =>	Fsb::$session->lang($GLOBALS['_auth_level'][$row['u_auth']]),
				));
				$a = $row['u_auth'];
			}

			Fsb::$tpl->set_blocks('auth.user', array(
				'NAME' =>		Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
			));
		}
		Fsb::$db->free($result);
	}

	/*
	** Liste des langues sur le forum
	*/
	public function show_info_lang()
	{
		$fd = opendir(ROOT . 'lang/');
		while ($file = readdir($fd))
		{
			if ($file[0] != '.' && is_dir(ROOT . 'lang/' . $file))
			{
				if (file_exists(ROOT . 'lang/' . $file . '/language.txt'))
				{
					$data = file(ROOT . 'lang/' . $file . '/language.txt');
					list($name, $author, $website) = $data;
					$email = (isset($data[3])) ? $data[3] : '';
				}
				else
				{
					$name = $file;
					$author = Fsb::$session->lang('info_lang_unknown');
					$website = '';
					$email = '';
				}

				Fsb::$tpl->set_blocks('lang', array(
					'NAME' =>		$name,
					'AUTHOR' =>		$author,
					'WEBSITE' =>	String::parse_website($website),
					'EMAIL' =>		String::parse_email($email),
				));
			}
		}
		closedir($fd);
	}
}


/* EOF */