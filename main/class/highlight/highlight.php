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
 * Colorateur syntaxique
 *
 */
abstract class Highlight extends Fsb_model
{
	/**
	 * Parse et colorie la syntaxe d'une chaine de code
	 *
	 * @param string $str Chaine de code a traiter
	 * @return string Chaine de code traitee
	 */
	abstract protected function _parse($str);

	/**
	 * Design pattern factory, Retourne une instance de la classe Highlight suivant le langage utilise
	 *
	 * @param string $language Langage utilise (php, css, etc.)
	 * @return Highlight
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

	/**
	 * Charge le contenu d'un fichier et colorie la syntaxe
	 *
	 * @param string $filename Nom de fichier
	 * @return string
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

	/**
	 * Charge le une chaine de caractere et colorie la syntaxe
	 *
	 * @param string $str Chaine a colorier
	 * @return string
	 */
	public function parse_code($str)
	{
		$str = str_replace("\r\n", "\n", $str);
		return ($this->_parse($str));
	}

	/**
	 * Parse les commentaires dans le code
	 *
	 * @param string $str Chaine a analyser
	 * @param int $i Position actuelle dans la chaine
	 * @param int $len Longueur de la chaine
	 * @param string $color Couleur de la syntaxe
	 * @return string
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
	
	/**
	 * Colorie la chaine comprise entre deux caracteres identiques
	 *
	 * @param string $str Chaine a analyser
	 * @param int $i Iteration actuelle
	 * @param int $len Longueur de la chaine
	 * @param string $c Delimiteur
	 * @param string $color Couleur de la syntaxe
	 * @return unknown
	 */
	protected function _quote_string(&$str, &$i, $len, &$c, $color)
	{
		$result = $c . $this->open_style($color);
		$i++;
		$close = false;
		while ($i < $len)
		{
			if ($str[$i] == $c && !String::is_escaped($i, $str))
			{
				$result .= $this->close_style() . $this->escape_special_char($str[$i]);
				$close = true;
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

	/**
	 * Ajoute une balise d'ouverture de style
	 *
	 * @param string $style Proprietes CSS a appliquer
	 * @return string
	 */
	protected function open_style($style)
	{
		return ('<span class="' . $style . '">');
	}

	/**
	 * Ajoute une balise de fermeture de style
	 *
	 * @return string
	 */
	protected function close_style()
	{
		return ('</span>');
	}

	/**
	 * Echappe les caracteres speciaux importants dans le parseur du forum
	 *
	 * @param string $c Chaine a echapper
	 * @return string
	 */
	protected function escape_special_char($c)
	{
		return (str_replace(array(':', '[', ']', ')', '('), array('&#58;', '&#91;', '&#93;', '&#41;', '&#40;'), htmlspecialchars($c)));
	}

	/**
	 * Supprime les anciens tags de coloration et renvoie la chaine encadree d'un nouveau tag
	 *
	 * @param array $match Tableau cree par la fonction preg_replace_callback()
	 * @return string
	 */
	protected function up_coloration($match)
	{
		$match[1] = str_replace('\"', '"', $match[1]);
		$match[1] = preg_replace('/<span class="sc_[a-zA-Z_]+?">(.+?)(<\/span>)?/si', '\\1', $match[1]);
		$match[1] = preg_replace('/<\/span>/si', '', $match[1]);
		return ('<span class="sc_html_tag">&lt;</span><span class="sc_html_comment">' . $match[1] . '</span>');
	}

	/**
	 * Retourne un tableau contenant les donnees ligne par ligne d'une variable de configuration du colorateur
	 *
	 * @param string $file_content Contenu du fichier de conf
	 * @param string $var_name Nom de la variable a parser
	 * @return array
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