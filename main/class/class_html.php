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
 * Ensemble de methodes retournant du code HTML genere
 */
class Html extends Fsb_model
{
	/**
	 * Cree un champ cache
	 *
	 * @param string|array $name Nom du champ
	 * @param string $value Valeur du champ
	 * @return string
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

	/**
	 * Gestion d'une pagination
	 *
	 * @param int $cur Valeur de la page courante
	 * @param int $total Nombre de page
	 * @param string $url URL de redirection de la pagination, sans sid()
	 * @param int $page_info Ajoute des informations a la pagination : page suivante, precedente, premiere page, etc ...
	 * @param bool $simple_style Si true, utilise du style simple
	 * @return string
	 */
	public static function pagination($cur, $total, $url, $page_info = PAGINATION_ALL, $simple_style = false)
	{
		// Style de la pagination
		$default_style = ($simple_style) ? 'topic' : 'url';
		$s = Fsb::$session->getStyle('pagination', $default_style . '_separator');

		// Initialisation des variables
		$total = ceil($total);
		$url .= (strstr($url, '?') ? '&amp;' : '?' ) . 'page=';
		$str = '';
		$begin = ($cur < 3) ? 1 : $cur - 2;
		$end = ($cur > ($total - 2)) ? $total : $cur + 2;

		// Creation de la pagination
		if ($cur)
		{
			for ($i = $begin; $i <= $end; $i++)
			{
				$str .= (($i == $begin) ? '' : $s) . (($i == $cur) ? sprintf(Fsb::$session->getStyle('pagination', $default_style . '_cur'), $i) : sprintf(Fsb::$session->getStyle('pagination', $default_style), sid($url . $i), $i));
			}
		}
		else
		{
			$str .= Fsb::$session->lang('page') . ' : ';
			if ($total <= 4)
			{
				for ($i = 1; $i <= $total; $i++)
				{
					$str .= (($i == 1) ? '' : $s) . sprintf(Fsb::$session->getStyle('pagination', $default_style), sid($url . $i), $i);
				}
			}
			else
			{
				$str .= sprintf(Fsb::$session->getStyle('pagination', $default_style), sid($url . 1), 1);
				$str .= $s . sprintf(Fsb::$session->getStyle('pagination', $default_style), sid($url . 2), 2);
				$str .= $s . ' ...';
				$str .= $s . sprintf(Fsb::$session->getStyle('pagination', $default_style), sid($url . ($total - 1)), ($total - 1));
				$str .= $s . sprintf(Fsb::$session->getStyle('pagination', $default_style), sid($url . $total), $total);
			}
		}

		// Liens suivants, precedents, premiere page et derniere page
		if ($page_info & PAGINATION_PREV)
		{
			$str = (($cur > 1) ? sprintf(Fsb::$session->getStyle('pagination', $default_style), sid($url . ($cur - 1)), '&#171;') : sprintf(Fsb::$session->getStyle('pagination', $default_style . '_cur'), '&#171;')) . $s . $str;
		}

		if ($page_info & PAGINATION_FIRST)
		{
			$str = sprintf(Fsb::$session->getStyle('pagination', $default_style), sid($url . 1), Fsb::$session->lang('first_page')) . $s . $str;
		}

		if ($page_info & PAGINATION_NEXT)
		{
			$str .= $s . (($cur < $total) ? sprintf(Fsb::$session->getStyle('pagination', $default_style), sid($url . ($cur + 1)), '&#187;') : sprintf(Fsb::$session->getStyle('pagination', $default_style . '_cur'), '&#187;'));
		}

		if ($page_info & PAGINATION_LAST)
		{
			$last_str = ($simple_style) ? Fsb::$session->lang('last_page') : sprintf(Fsb::$session->lang('last_page_total'), $total);
			$str .= $s . sprintf(Fsb::$session->getStyle('pagination', $default_style), sid($url . $total), $last_str);
		}

		return (sprintf(Fsb::$session->getStyle('pagination', $default_style . '_global'), $str, $url, Fsb::$session->lang('go')));
	}

	/**
	 * Retourne sous forme de liste HTML les categories, forums et sous forums
	 *
	 * @param array $forums Tableau contenant la liste des forums
	 * @param string $value Valeur par defaut a donner a la liste
	 * @param string $name Nom de la liste
	 * @param bool $choose_cat Definit si on peut selectionner la categorie
	 * @param string $script Atributs ajoutes dans la balise <select>
	 * @param bool $all_select Definit si on selectionne toutes les valeurs par defaut ou pas
	 * @param string $option_html Options facultatives
	 * @return string
	 */
	public static function list_forums($forums, $value, $name, $choose_cat = true, $script = '', $all_select = false, $option_html = '')
	{
		// Calcul des forums ?
		if (!$forums)
		{
			$forums = get_forums();
		}

		$first_cat = true;
		$list = '<select name="' . $name . '" ' . $script . '>' . $option_html;
		$code_cat = '';
		$have_forum = false;
		foreach ($forums AS $forum)
		{
			if ($forum['f_parent'] == 0)
			{
				// Categorie
				$have_forum = false;
				if ($choose_cat)
				{
					$add = '';
					if ($code_cat && defined('IN_ADM'))
					{
						$add = $code_cat;
						$first_cat = false;
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
					$have_forum = true;
					$first_cat = false;
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

	/**
	 * Cree une liste deroulante permettant d'acceder rapidement aux sections principales et forums
	 *
	 * @param unknown_type $redirect Si on est en mode redirection
	 * @return string
	 */
	public static function jumpbox($redirect = false)
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
			$html .= (Fsb::$mods->is_active('mp')) ? '<option value="?p=mp">' . Fsb::$session->lang('jumpox_mp') . '</option>' : '';
			$html .= '<option value="?p=userlist&amp;g_id=' . GROUP_SPECIAL_USER . '">' . Fsb::$session->lang('jumpbox_userlist') . '</option>';
			$html .= '<option value="?p=profile&amp;module=personal">' . Fsb::$session->lang('jumpbox_profile') . '</option>';
			$html .= '</optgroup>';
			return (Html::list_forums(get_forums(), '', 'jumpbox', false, 'onchange="location.href = (isNaN(this.value)) ? this.value : \'index.php?p=forum&amp;f_id=\' + this.value"', false, $html));
		}
	}
	
	/**
	 * Cree une liste HTML
	 *
	 * @param string $name Nom de la liste
	 * @param string|array $default Valeur par defaut (si l'attribut multiple est passe, doit etre un tableau)
	 * @param array $values Contient en clef la valeur de l'option, et en valeur la langue
	 * @param array $attributes Liste d'attributs a ajouter a la balise select ouvrante
	 * @return string
	 */
	public static function make_list($name, $default, $values, $attributes = array())
	{
		// Ajout des attributs
		$attrs = '';
		foreach ($attributes AS $key => $value)
		{
			$attrs .= ' ' . $key . '="' . $value . '"';
		}

		$list = '<select name="' . $name . '"' . $attrs . '>';
		if ($values)
		{
			// Gestion des optgroup ?
			if (is_array(current($values)))
			{
				foreach ($values AS $optgroup)
				{
					$list .= '<optgroup label="' . $optgroup['lang'] . '">';
					foreach ($optgroup['options'] AS $option_value => $option_lang)
					{
						$selected = ((is_array($default) && in_array($option_value, $default)) || $option_value == $default) ? true : false;
						
						$list .= '<option value="' . $option_value . '" ' . (($selected) ? 'selected="selected"' : '') . '>' . $option_lang . '</option>';
					}
					$list .= '</optgroup>';
				}
			}
			// Pas d'optgroup
			else
			{
				foreach ($values AS $option_value => $option_lang)
				{
					$selected = ((is_array($default) && in_array($option_value, $default)) || $option_value == $default) ? true : false;
					
					$list .= '<option value="' . $option_value . '" ' . (($selected) ? 'selected="selected"' : '') . '>' . $option_lang . '</option>';
				}
			}
		}
		$list .= '</select>';
		
		return ($list);
	}

	/**
	 * Cree une liste a partir du contenu d'un dossier
	 *
	 * @param string $name Nom de la liste
	 * @param string $value Valeur par defaut de la liste
	 * @param string $dir Chemin du repertoire a lister
	 * @param array $allowed_ext Contient les extensions autorisees. Laisser vide pour tout autoriser
	 * @param bool $only_dir Autorise uniquement les dossiers si true
	 * @param string $first Rajouter un element en debut de liste
	 * @param string $code Pour rajouter des atributs ou du code javascript dans le <select>
	 * @return string
	 */
	public static function list_dir($name, $value, $dir, $allowed_ext = array(), $only_dir = false, $first = '', $code = '')
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

	/**
	 * Liste les langues installees sur le forum
	 *
	 * @param string $name Nom de la liste
	 * @param string $value Valeur par defaut de la liste
	 * @return string
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

		return (Html::make_list($name, $value, $list, array('id' => 'list_lang_id')));
	}

	/**
	 * Liste des fuseaux horaires
	 *
	 * @param string $name Nom de la liste
	 * @param string $default Valeur par defaut de la liste
	 * @param string $type Type de la liste : utc pour les fuseaux horaires, dst pour les decalages
	 * @return string
	 */
	public static function list_utc($name, $default, $type = 'utc')
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
		return (Html::make_list($name, $default, $list, array('id' => 'list_' . $type . '_id')));
	}

	/**
	 * Genere la liste des groupes
	 *
	 * @param string $list_name Nom de la liste
	 * @param int $type_group GROUP_SPECIAL ou GROUP_NORMAL (possibilite d'utilise un OU binaire)
	 * @param string $value Valeur de la liste
	 * @param bool $multiple Liste multiple
	 * @param array $except ID des groupes qu'on ne veut pas voir dans la liste
	 * @param string $erase_sql Requete personalisee
	 * @param string $add_html Code HTML ajoutable dans la liste
	 * @param string $add_option Options HTML a ajouter
	 * @return string
	 */
	public static function list_groups($list_name, $type_group, $value, $multiple = false, $except = array(), $erase_sql = null, $add_html = '', $add_option = '')
	{
		static $groups = null;

		if (is_null($groups) || !is_null($erase_sql))
		{
			// Construction de la requete en fonction des flags
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
			if ($except)
			{
				$sql_and .= ' AND g_id NOT IN (' . implode(',', $except) . ') ';
			}

			// Liste des groupes
			if (is_null($erase_sql))
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
		$show_special = $show_normal = false;
		foreach ($groups AS $row)
		{
			if ($type_group & GROUP_SPECIAL && !$show_special && $row['g_type'] == GROUP_SPECIAL)
			{
				$list .= '<optgroup label="' . Fsb::$session->lang('list_group_special') . '">';
				$show_special = true;
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
				$show_normal = true;
			}

			$selected = ((!$multiple && $value == $row['g_id']) || ($multiple && in_array($row['g_id'], $value))) ? 'selected="selected"' : '';
			$list .= '<option value="' . $row['g_id'] . '" ' . $selected . '>' . (($row['g_type'] == GROUP_SPECIAL && Fsb::$session->lang($row['g_name'])) ? Fsb::$session->lang($row['g_name']) : htmlspecialchars($row['g_name'])) . '</option>' . "\n";
		}
		$list .= '</optgroup></select>';

		return (($groups) ? $list : null);
	}

	/**
	 * Affiche le pseudonyme d'un membre avec un lien vers son profil et sa couleur
	 *
	 * @param string $nickname Pseudonyme du membre
	 * @param int $u_id ID du membre
	 * @param string $color Couleur du membre
	 * @return string
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
			return (sprintf(Fsb::$session->getStyle('other', 'nickname'), $color, htmlspecialchars($nickname)));
		}
		else
		{
			return (sprintf(Fsb::$session->getStyle('other', 'nickname_link'), sid(FSB_PATH . 'index.' . PHPEXT . '?p=userprofile&amp;id=' . $u_id), $color, htmlspecialchars($nickname)));
		}
	}

	/**
	 * Affiche le nom d'un forum avec un lien et sa couleur
	 *
	 * @param string $forum Nom du forum
	 * @param int $id ID du forum
	 * @param string $color style
	 * @param string $location URL en dur si on ne souhaite pas qu'elle soit generee automatiquement
	 * @return string
	 */
	public static function forumname($forum, $id, $color = '', $location = '')
	{
		if (!$color)
		{
			$color = 'class="forum"';
		}

		$url = ($location) ? $location : sid(FSB_PATH . 'index.' . PHPEXT . '?p=forum&amp;f_id=' . $id);
		return (sprintf(Fsb::$session->getStyle('other', 'forum_link'), $url, $color, $forum));
	}

	/**
	 * Genere une chaine qui sera applicable en tant que style sur une balise
	 *
	 * @param string $type style ou class
	 * @param string $content Contenu de la balise
	 * @param string $default Valeur par defaut au cas ou le style est vide
	 * @return string
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

	/**
	 * Retourne le type (style ou class) et le contenu d'un style
	 *
	 * @param string $style Style a parser
	 * @return array type, content
	 */
	public static function get_style($style)
	{
		if (preg_match('#^(class|style)="(.*?)"$#i', $style, $m))
		{
			return (array($m[1], String::unhtmlspecialchars($m[2])));
		}
		return (null);
	}

	/**
	 * Genere une liste d'erreur a partir d'un tableau PHP
	 *
	 * @param array $errstr Liste d'erreurs
	 * @return string
	 */
	public static function make_errstr(&$errstr)
	{
		if (!$errstr)
		{
			return (null);
		}

		if (!is_array($errstr))
		{
			return ($errstr);
		}
		else
		{
			$result = Fsb::$session->getStyle('errstr', 'open');
			foreach ($errstr AS $str)
			{
				$result .= sprintf(Fsb::$session->getStyle('errstr', 'item'), $str);
			}
			$result .= Fsb::$session->getStyle('errstr', 'close');
			return ($result);
		}
	}
}
/* EOF */
