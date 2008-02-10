<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_html.php
** | Begin :	19/06/2007
** | Last :		11/01/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Ensemble de méthodes retournant du code HTML généré
*/
class Html extends Fsb_model
{
	/*
	** Créé un champ caché
	** -----
	** $name ::		Nom du champ caché
	** $valeur ::		Valeur du champ caché
	*/
	public static function hidden($name, $value = '')
	{
		if (is_array($name))
		{
			$return = '';
			foreach ($name AS $k => $v)
			{
				$return .= '<input type="hidden" name="' . $k . '" value="' . $v . '" />';
			}
			return ($return);
		}
		else
		{
			return ('<input type="hidden" name="' . $name . '" value="' . $value . '" />');
		}
	}

	/*
	** Gestion d'une pagination
	** -----
	** $cur ::			Valeur de la page courante
	** $total ::		Nombre de page
	** $url ::			URL de redirection de la pagination, sans sid()
	** $page_info ::	Ajoute des informations à la pagination : page suivante, précédente, première page, etc ...
	*/
	public static function pagination($cur, $total, $url, $page_info = PAGINATION_ALL, $simple_style = FALSE)
	{
		// Style de la pagination
		$default_style = ($simple_style) ? 'topic' : 'url';
		$s = Fsb::$session->style['pagination'][$default_style . '_separator'];

		// Initialisation des variables
		$total = ceil($total);
		$url .= (strstr($url, '?') ? '&amp;' : '?' ) . 'page=';
		$str = '';
		$begin = ($cur < 3) ? 1 : $cur - 2;
		$end = ($cur > ($total - 2)) ? $total : $cur + 2;

		// Création de la pagination
		if ($cur)
		{
			for ($i = $begin; $i <= $end; $i++)
			{
				$str .= (($i == $begin) ? '' : $s) . (($i == $cur) ? sprintf(Fsb::$session->style['pagination'][$default_style . '_cur'], $i) : sprintf(Fsb::$session->style['pagination'][$default_style], sid($url . $i), $i));
			}
		}
		else
		{
			$str .= Fsb::$session->lang('page') . ' : ';
			if ($total <= 4)
			{
				for ($i = 1; $i <= $total; $i++)
				{
					$str .= (($i == 1) ? '' : $s) . sprintf(Fsb::$session->style['pagination'][$default_style], sid($url . $i), $i);
				}
			}
			else
			{
				$str .= sprintf(Fsb::$session->style['pagination'][$default_style], sid($url . 1), 1);
				$str .= $s . sprintf(Fsb::$session->style['pagination'][$default_style], sid($url . 2), 2);
				$str .= $s . ' ...';
				$str .= $s . sprintf(Fsb::$session->style['pagination'][$default_style], sid($url . ($total - 1)), ($total - 1));
				$str .= $s . sprintf(Fsb::$session->style['pagination'][$default_style], sid($url . $total), $total);
			}
		}

		// Liens suivants, précédents, première page et dernière page
		if ($page_info & PAGINATION_PREV)
		{
			$str = (($cur > 1) ? sprintf(Fsb::$session->style['pagination'][$default_style], sid($url . ($cur - 1)), '&#171;') : sprintf(Fsb::$session->style['pagination'][$default_style . '_cur'], '&#171;')) . $s . $str;
		}

		if ($page_info & PAGINATION_FIRST)
		{
			$str = sprintf(Fsb::$session->style['pagination'][$default_style], sid($url . 1), Fsb::$session->lang('first_page')) . $s . $str;
		}

		if ($page_info & PAGINATION_NEXT)
		{
			$str .= $s . (($cur < $total) ? sprintf(Fsb::$session->style['pagination'][$default_style], sid($url . ($cur + 1)), '&#187;') : sprintf(Fsb::$session->style['pagination'][$default_style . '_cur'], '&#187;'));
		}

		if ($page_info & PAGINATION_LAST)
		{
			$last_str = ($simple_style) ? Fsb::$session->lang('last_page') : sprintf(Fsb::$session->lang('last_page_total'), $total);
			$str .= $s . sprintf(Fsb::$session->style['pagination'][$default_style], sid($url . $total), $last_str);
		}

		return (sprintf(Fsb::$session->style['pagination'][$default_style . '_global'], $str, $url, Fsb::$session->lang('go')));
	}

	/*
	** Retourne sous forme de liste HTML les catégories, forums et sous forums
	** -----
	** $forums ::		Tableau contenant la liste des forums
	** $value ::		Valeur par défaut à donner à la liste
	** $name ::			Nom de la liste
	** $choose_cat ::	Définit si on peut sellectionner la catégorie
	** $script ::		Atributs ajoutés dans la balise <select>
	** $all_select ::	Définit si on selectionne toutes les valeurs par défaut ou pas
	** $option_html ::	Options facultatives
	*/
	public static function list_forums($forums, $value, $name, $choose_cat = TRUE, $script = '', $all_select = FALSE, $option_html = '')
	{
		// Calcul des forums ?
		if (!$forums)
		{
			$forums = get_forums();
		}

		$first_cat = TRUE;
		$list = '<select name="' . $name . '" ' . $script . '>' . $option_html;
		$code_cat = '';
		$have_forum = FALSE;
		foreach ($forums AS $forum)
		{
			if ($forum['f_parent'] == 0)
			{
				// Catégorie
				$have_forum = FALSE;
				if ($choose_cat)
				{
					$add = '';
					if ($code_cat && defined('IN_ADM'))
					{
						$add = $code_cat;
						$first_cat = FALSE;
					}

					$select = ($all_select || $value == $forum['f_id'] || (is_array($value) && in_array($forum['f_id'], $value))) ? 'selected="selected"' : '';
					$code_cat = $add . (($first_cat) ? '' : '<optgroup label="&nbsp;"></optgroup>') . '<option value="' . $forum['f_id'] . '" style="font-weight: bold;" ' . $select . '>' . $forum['f_name'] . '</option>';
				}
				else
				{
					$code_cat = (($first_cat) ? '' : '</optgroup>') . '<optgroup label="' . $forum['f_name'] . '">';
				}
			}
			else
			{
				if (Fsb::$session->is_authorized($forum['f_id'], 'ga_view') || defined('IN_ADM'))
				{
					$have_forum = TRUE;
					$first_cat = FALSE;
					if ($code_cat)
					{
						$list .= $code_cat;
						$code_cat = '';
					}

					$repeat = str_repeat('&nbsp;&nbsp;&nbsp;|', $forum['f_level']) . '-- ';
					$select = ($all_select || $value == $forum['f_id'] || (is_array($value) && in_array($forum['f_id'], $value))) ? 'selected="selected"' : '';
					$list .= '<option value="' . $forum['f_id'] . '" ' . $select . '>' . $repeat . $forum['f_name'] . '</option>';
				}
			}
		}

		if ($code_cat && ($have_forum || defined('IN_ADM')))
		{
			$list .= $code_cat;
		}

		if (!$choose_cat)
		{
			$list .= '</optgroup>';
		}
		$list .= '</select>';
		return ($list);
	}

	/*
	** La jumpbox est une liste déroulante permettant d'accéder rapidement à n'importe quel forum,
	** ou aux sections importantes du forum.
	** -----
	** $redirect ::		Si on est en mode redirection
	*/
	public static function jumpbox($redirect = FALSE)
	{
		if ($redirect)
		{
			$value = Http::request('jumpbox', 'post');
			if (is_numeric($value))
			{
				Http::redirect(ROOT . 'index.' . PHPEXT . '?p=forum&f_id=' . intval($value));
			}
			else
			{
				Http::redirect(ROOT . 'index.' . PHPEXT . $value);
			}
		}
		else
		{
			$html = '<optgroup label="' . Fsb::$session->lang('jumpbox_label') . '">';
			$html .= '<option value="?p=index">' . Fsb::$session->lang('forum_index') . '</option>';
			$html .= '<option value="?p=faq">' . Fsb::$session->lang('jumpbox_faq') . '</option>';
			$html .= (Fsb::$cfg->get('mp_activated')) ? '<option value="?p=mp">' . Fsb::$session->lang('jumpox_mp') . '</option>' : '';
			$html .= '<option value="?p=userlist&amp;g_id=' . GROUP_SPECIAL_USER . '">' . Fsb::$session->lang('jumpbox_userlist') . '</option>';
			$html .= '<option value="?p=profile&amp;module=personal">' . Fsb::$session->lang('jumpbox_profile') . '</option>';
			$html .= '</optgroup>';
			return (Html::list_forums(get_forums(), '', 'jumpbox', FALSE, '', FALSE, $html));
		}
	}

	/*
	** Créé une liste HTML
	** -----
	** $name ::			Nom de la liste
	** $value ::		Valeur par défaut de la liste
	** $ary ::			Tableau contenant en clef les valeurs des options et
	**					en valeur les éléments.
	** $multilist ::	S'il s'agit d'une multiliste on précise le code ici, $value devra 
	**					être un tableau
	** $code ::			Code HTML (ou javascript) à rajouter à la liste
	*/
	public static function create_list($name, $value, $ary, $multilist = '', $code = '')
	{
		$list = '<select name="' . $name . '" ' . $multilist . ' ' . $code . '>';
		foreach ($ary AS $k => $v)
		{
			$list .= '<option value="' . $k . '" ' . (((!$multilist && $k == $value) || ($multilist && is_array($value) && in_array($k, $value))) ? 'selected="selected"' : '') . '>' . $v . '</option>';
		}
		$list .= '</select>';
		return ($list);
	}

	/*
	** Créé une liste HTML en fonction des éléments dans un dossier
	** -----
	** $name ::		Nom de la liste
	** $value ::	Valeur par défaut de la liste
	** $dir ::		Chemin du répertoire à lister
	** $allowed_ext ::	Contient les extensions autorisées.
	**					Laisser vide pour autoriser tous les fichiers.
	** $only_dir ::	Autorise uniquement les dossiers si TRUE
	** $first ::	Rajouter un élément en début de liste
	** $code ::		Pour rajouter des atributs ou du code javascript dans le <select>
	*/
	public static function list_dir($name, $value, $dir, $allowed_ext = array(), $only_dir = FALSE, $first = '', $code = '')
	{
		$count = count($allowed_ext);
		if (!$fd = @opendir($dir))
		{
			trigger_error('Impossible d\'ouvrir le dossier ' . $dir, FSB_ERROR);
		}

		$list = '<select name="' . $name . '" ' . $code . '>' . $first;
		while ($file = readdir($fd))
		{
			$ary = explode('.', $file);
			$ext = $ary[count($ary) - 1];
			if ($file[0] != '.' && (!$count || ($count && in_array($ext, $allowed_ext))))
			{
				if (!$only_dir || ($only_dir && is_dir($dir . '/' . $file)))
				{
					$list .= '<option value="' . $file . '" ' . (($file == $value) ? 'selected="selected" style="font-weight: bold;"' : '') . '>' . str_replace('_', ' ', $file) . '</option>';
				}
			}
		}
		closedir($fd);
		$list .= '</select>';
		return ($list);
	}

	/*
	** Liste les langues installées sur le forum
	** -----
	** $name ::		Nom de la liste
	** $value ::	Valeur par défaut de la liste
	*/
	public static function list_langs($name, $value)
	{
		$list = array();
		$path = ROOT . 'lang/';
		$fd = opendir($path);
		while ($file = readdir($fd))
		{
			if ($file[0] != '.' && is_dir($path . $file))
			{
				$language = $file;
				if (file_exists($path . $file . '/language.txt'))
				{
					$content = file($path . $file . '/language.txt');
					$language = trim($content[0]);
				}
				$list[$file] = $language;
			}
		}
		closedir($fd);

		return (Html::create_list($name, $value, $list));
	}

	/*
	** Liste des fuseaux horaires
	** -----
	** $name ::		Nom de la liste
	** $default ::	Valeur par défaut de la liste
	** $type ::		Type de la liste : utc pour les fuseaux horaires, dst pour les décalages
	** $multiple ::	Selection multiple si TRUE
	*/
	public static function list_utc($name, $default, $type = 'utc', $multiple = '')
	{
		switch ($type)
		{
			case 'utc' :
				$list = array();
				foreach ($GLOBALS['_utc'] AS $diff => $print)
				{
					$list[$diff] = '[UTC ' . $print . '] ' . Fsb::$session->lang('utc' . $diff);
				}
			break;

			case 'dst' :
				$list = array(
					0 =>	Fsb::$session->lang('dst_0'),
					1 =>	Fsb::$session->lang('dst_1'),
				);
			break;
		}
		return (Html::create_list($name, $default, $list, $multiple));
	}

	/*
	** Génère la liste des groupes
	** -----
	** $list_name ::		Nom de la liste
	** $type_groupe ::		GROUP_SPECIAL ou bien GROUP_NORMAL, possibilité 
	**						d'utiliser les deux avec un ou binaire
	** $value ::			Valeur de la liste
	** $multiple ::			Liste multiple
	** $exept ::			ID des groupes qu'on ne veut pas voir dans la liste
	** $erase_sql ::		Requête personalisée
	** $add_html ::			Code HTML ajoutable dans la liste
	** $add_option ::		Options HTML à ajouter
	*/
	public static function list_groups($list_name, $type_group, $value, $multiple = FALSE, $exept = array(), $erase_sql = NULL, $add_html = '', $add_option = '')
	{
		static $groups = NULL;

		if ($groups === NULL || $erase_sql !== NULL)
		{
			// Construction de la requète en fonction des flags
			$sql_and = '';
			if (!($type_group & GROUP_SPECIAL))
			{
				$sql_and .= ' AND g_type <> ' . GROUP_SPECIAL . ' ';
			}

			if (!($type_group & GROUP_NORMAL))
			{
				$sql_and .= ' AND g_type <> ' . GROUP_NORMAL . ' ';
			}

			// Exeptions de groupes
			if ($exept)
			{
				$sql_and .= ' AND g_id NOT IN (' . implode(',', $exept) . ') ';
			}

			// Liste des groupes
			if ($erase_sql === NULL)
			{
				$sql = 'SELECT g_id, g_name, g_type, g_hidden
						FROM ' . SQL_PREFIX . 'groups
						WHERE g_type <> ' . GROUP_SINGLE . '
						' . $sql_and . '
						ORDER BY g_type, g_name';
				$result = Fsb::$db->query($sql, 'groups_');
			}
			else
			{
				$result = Fsb::$db->query($erase_sql);
			}
			$groups = Fsb::$db->rows($result);
		}

		$list = '<select name="' . $list_name . '" ' . (($multiple) ? 'multiple="multiple" size="5"' : '') . ' ' . $add_html . '>' . $add_option;
		$show_special = $show_normal = FALSE;
		foreach ($groups AS $row)
		{
			if ($type_group & GROUP_SPECIAL && !$show_special && $row['g_type'] == GROUP_SPECIAL)
			{
				$list .= '<optgroup label="' . Fsb::$session->lang('list_group_special') . '">';
				$show_special = TRUE;
			}
			else if ($type_group & GROUP_NORMAL && !$show_normal && $row['g_type'] == GROUP_NORMAL)
			{
				if ($row['g_hidden'] == GROUP_HIDDEN && !in_array($row['g_id'], Fsb::$session->data['groups']) && Fsb::$session->auth() < MODOSUP)
				{
					continue;
				}

				if ($show_special)
				{
					$list .= '</optgroup>';
				}
				
				$list .= '<optgroup label="' . Fsb::$session->lang('list_group_normal') . '">';
				$show_normal = TRUE;
			}

			$selected = ((!$multiple && $value == $row['g_id']) || ($multiple && in_array($row['g_id'], $value))) ? 'selected="selected"' : '';
			$list .= '<option value="' . $row['g_id'] . '" ' . $selected . '>' . (($row['g_type'] == GROUP_SPECIAL && Fsb::$session->lang($row['g_name'])) ? Fsb::$session->lang($row['g_name']) : htmlspecialchars($row['g_name'])) . '</option>' . "\n";
		}
		$list .= '</optgroup></select>';

		return (($groups) ? $list : null);
	}

	/*
	** Affiche le pseudonyme d'un membre dans le bon format
	** -----
	** $nickname ::		Pseudonyme du membre
	** $u_id ::			ID du membre
	** $color ::		Couleur du membre
	*/
	public static function nickname($nickname, $u_id = VISITOR_ID, $color = '')
	{
		if (!$color)
		{
			$color = 'class="user"';
		}

		if (!$u_id || $u_id == VISITOR_ID)
		{
			$nickname = (strtolower($nickname) == 'visitor') ? Fsb::$session->lang('visitor') : $nickname;
			return (sprintf(Fsb::$session->style['other']['nickname'], $color, htmlspecialchars($nickname)));
		}
		else
		{
			return (sprintf(Fsb::$session->style['other']['nickname_link'], sid(FSB_PATH . 'index.' . PHPEXT . '?p=userprofile&amp;id=' . $u_id), $color, htmlspecialchars($nickname)));
		}
	}

	/*
	** Affiche le nom d'un forum avec lien et style
	** -----
	** $forum ::		Nom du forum
	** $id ::			ID du forum
	** $color ::		style
	** $location ::		URL en dur
	*/
	public static function forumname($forum, $id, $color = '', $location = '')
	{
		if (!$color)
		{
			$color = 'class="forum"';
		}

		$url = ($location) ? $location : sid(FSB_PATH . 'index.' . PHPEXT . '?p=forum&amp;f_id=' . $id);
		return (sprintf(Fsb::$session->style['other']['forum_link'], $url, $color, $forum));
	}

	/*
	** Génère une chaîne qui sera applicable en tant que style sur une balise, à partir de l'information style / class et de son contenu
	** -----
	** $type ::		style ou class
	** $content ::	Contenu de la balise
	** $default ::	Valeur par défaut au cas où le style est vide
	*/
	public static function set_style($type, $content, $default = '')
	{
		if (!$type || $type == 'none' || !$content)
		{
			return ($default);
		}

		if ($type != 'style' && $type != 'class')
		{
			$type = 'style';
		}
		$content = htmlspecialchars($content);

		return ($type . '="' . $content . '"');
	}

	/*
	** Retourne les éléments type et content d'un style
	** -----
	** $style ::	Style à parser
	*/
	public static function get_style($style)
	{
		if (preg_match('#^(class|style)="(.*?)"$#i', $style, $m))
		{
			return (array($m[1], String::unhtmlspecialchars($m[2])));
		}
		return (NULL);
	}

	/*
	** Génère une liste d'erreur à partir d'un tableau PHP en prenant en compte
	** le style définit pour le thème
	** -----
	** $errstr ::	Tableau d'erreurs
	*/
	public static function make_errstr(&$errstr)
	{
		if (!$errstr)
		{
			return (NULL);
		}

		if (!is_array($errstr))
		{
			return ($errstr);
		}
		else
		{
			$result = Fsb::$session->style['errstr']['open'];
			foreach ($errstr AS $str)
			{
				$result .= sprintf(Fsb::$session->style['errstr']['item'], $str);
			}
			$result .= Fsb::$session->style['errstr']['close'];
			return ($result);
		}
	}
}
/* EOF */