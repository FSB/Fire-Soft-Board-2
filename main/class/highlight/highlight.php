<?php
/*
** +---------------------------------------------------+
** | Name :			~/main/class/highlight/highlight.php
** | Begin :		27/09/2005
** | Last :			07/01/2008
** | User :			Genova
** | Project :		Fire-Soft-Board 2 - Copyright FSB group
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Colorateur syntaxique
*/
abstract class Highlight extends Fsb_model
{
	abstract protected function _parse($str);

	/*
	** Retourne une instance de la classe Highlight suivant le language utilise
	*/
	public static function &factory($language)
	{
		if (!in_array($language, array('php', 'sql', 'css', 'html')))
		{
			$language = 'html';
		}
		$classname = 'Highlight_' . $language;

		$obj = & new $classname();
		return ($obj);
	}

	/*
	** Parse un fichier
	** -----
	** $filename ::	Nom de fichier
	*/
	public function parse_file($filename)
	{
		if (!file_exists($filename))
		{
			trigger_error('Le fichier ' . $filename . ' n\'existe pas', FSB_ERROR);
		}
		$str = file_get_contents($filename);

		return ($this->_parse($str));
	}

	/*
	** Parse la chaine de caractere
	** -----
	** $str ::		Chaine de caractere contenant du PHP
	*/
	public function parse_code($str)
	{
		$str = str_replace("\r\n", "\n", $str);
		return ($this->_parse($str));
	}

	/*
	** Parse les commentaires
	** -----
	** $str ::		Chaine de caractere
	** $i ::		Iteration actuelle
	** $len ::		Longueur de la chaine
	** $color ::	Couleur de la syntaxe
	*/
	protected function _comment_string(&$str, &$i, &$len, $color)
	{
		$result = $this->open_style($color);
		while ($i < $len)
		{
			$result .= $this->escape_special_char($str[$i]);
			if ($str[$i] == "*" && $str[$i + 1] == '/')
			{
				$result .= '/';
				$i++;
				break;
			}
			$i++;
		}
		$result .= $this->close_style();
		return ($result);
	}
	
	/*
	** Colorie la chaine comprise entre deux caracteres identiques (", ', etc ...)
	** -----
	** $str ::		Chaine de caractere
	** $i ::		Iteration actuelle
	** $len ::		Longueur de la chaine
	** $c ::		Caractere unique
	** $color ::	Couleur de la syntaxe
	*/
	protected function _quote_string(&$str, &$i, $len, &$c, $color)
	{
		$result = $c . $this->open_style($color);
		$i++;
		$close = FALSE;
		while ($i < $len)
		{
			if ($str[$i] == $c && !String::is_escaped($i, $str))
			{
				$result .= $this->close_style() . $this->escape_special_char($str[$i]);
				$close = TRUE;
				break;
			}
			$result .= $this->escape_special_char($str[$i]);
			$i++;
		}

		if (!$close)
		{
			$result .= $this->close_style();
		}
		return ($result);
	}

	protected function open_style($style)
	{
		return ('<span class="' . $style . '">');
	}

	protected function close_style()
	{
		return ('</span>');
	}

	/*
	** Echape des caracteres speciaux pour le forum
	*/
	protected function escape_special_char($c)
	{
		return (str_replace(array(':', '[', ']', ')', '('), array('&#58;', '&#91;', '&#93;', '&#41;', '&#40;'), htmlspecialchars($c)));
	}

	/*
	** Supprime les anciens tags de coloration dans la chaine passee en argument
	** et renvoie la chaine encadree d'un nouveau tag.
	** -----
	** $str ::		Chaine de caractere a parser.
	*/
	protected function up_coloration($match)
	{
		$match[1] = str_replace('\"', '"', $match[1]);
		$match[1] = preg_replace('/<span class="sc_[a-zA-Z_]+?">(.+?)(<\/span>)?/si', '\\1', $match[1]);
		$match[1] = preg_replace('/<\/span>/si', '', $match[1]);
		return ('<span class="sc_html_tag">&lt;</span><span class="sc_html_comment">' . $match[1] . '</span>');
	}

	/*
	** Retourne un tableau contenant les donnees ligne par ligne d'une variable de configuration
	** du colorateur
	** -----
	** $file_content ::		Contenu du ficheir de conf
	** $var_name ::			Nom de la variable a parser
	*/
	protected function get_conf($file_content, $var_name)
	{
		if (preg_match('#\$' . $var_name . '\(([^\)]*?)\);#si', $file_content, $match))
		{
			$result = explode("\n", trim($match[1]));
			return (array_map('trim', $result));
		}
		return (array());
	}
}


/* EOF */