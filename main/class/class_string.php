<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

/*
** Gestion des chaines de caractere
*/
class String extends Fsb_model
{
	/**
	 * Correspond aux offset generes par les fonctions preg_*() avec le flag PREG_OFFSET_CAPTURE
	 */
	const CHAR = 0;
	const OFFSET = 1;

	/**
	 * Ajoute un espace dans les mots trop long
	 *
	 * @param string $str Chaine a traiter
	 * @param int $length Longueur maximale de chaque mot
	 * @return string
	 */
	public static function truncate($str, $length)
	{
		$merge = array();
		foreach (explode(' ', $str) AS $word)
		{
			if (strlen($str) > $length)
			{
				$word = wordwrap($word, $length, ' ', 1);
			}
			$merge[] = $word;
		}
		return (implode(' ', $merge));
	}

	/**
	 * Convertit une chaine UTF8 en minuscule
	 *
	 * @param string $str Chaine a convertir
	 * @return string
	 */
	public static function strtolower($str)
	{
		return (strtr($str, $GLOBALS['UTF8_UPPER_TO_LOWER']));
	}

	/**
	 * Convertit une chaine UTF8 en majuscule
	 *
	 * @param string $str Chaine a convertir
	 * @return string
	 */
	public static function strtoupper($str)
	{
		return (strtr($str, $GLOBALS['UTF8_LOWER_TO_UPPER']));
	}

	/**
	 * Substr gerant l'UTF-8, fonction reprise de SPIP
	 * @link http://www.spip.net
	 * 
	 * @param string $str Chaine de caractere
	 * @param int $start Offset de depart pour la tronquature
	 * @param int $length Longueur du texte a tronquer
	 * @return string
	 */
	public static function substr($str, $start = 0, $length = null)
	{
		if (Fsb::$session->lang('charset') != 'UTF-8')
		{
			$fct = 'substr';
		}
		else if (PHP_EXTENSION_MBSTRING)
		{
			$fct = 'mb_substr';
		}
		else
		{
			if ($length)
			{
				return (self::substr_manual($str, $start, $length));
			}
			return (self::substr_manual($str, $start));
		}

		if ($length)
		{
			return ($fct($str, $start, $length));
		}
		return ($fct($str, $start));
	}

	/**
	 * Substr gerant l'UTF-8, fonction reprise de SPIP
	 * @link http://www.spip.net
	 * 
	 * @param string $str Chaine de caractere
	 * @param int $start Offset de depart pour la tronquature
	 * @param int $length Longueur du texte a tronquer
	 * @return string
	 */
	public static function substr_manual($str, $start, $length = 0)
	{
		if ($length === 0)
		{
			return ('');
		}

		if ($start > 0)
		{
			$d = self::substr($str, 0, $start);
			$str = substr($str, strlen($d));
		}

		if ($start < 0)
		{
			$d = self::substr($str, 0, $start);
			$str = substr($str, -strlen($d));
		}

		if (!$length)
		{
			return ($str);
		}

		if ($length > 0)
		{
			$str = substr($str, 0, 5 * $length);
			while (($l = self::strlen($str)) > $length)
			{
				$str = substr($str, 0, $length - $l);
			}
			return ($str);
		}

		if ($length < 0)
		{
			$fin = substr($str, 5 * $length);
			while (($l = self::strlen($fin)) > -$length)
			{
				$fin = substr($fin, $length+$l);
				$fin = preg_replace(',^[\x80-\xBF],S', 'x', $fin);
			}
			return (substr($str, -strlen($fin)));
		}
	}

	/**
	 * Strlen gerant l'UTF-8, fonction reprise de SPIP
	 * @link http://www.spip.net
	 * 
	 * @param string $str Chaine de caractere
	 * @return string
	 */
	public static function strlen($str)
	{
		if (Fsb::$session->lang('charset') != 'UTF-8')
		{
			return (strlen($str));
		}

		if (PHP_EXTENSION_MBSTRING)
		{
			return (mb_strlen($str));
		}

		return (strlen(preg_replace('#[\x80-\xBF]#S', '', $str))); 
	}

	/**
	 * Converti en entites HTML des donnees qui ont ete encodees par escape() en javascript.
	 * Cette fonction est indispensable dans votre traitement AJAX cote PHP, car les fonctions
	 * PHP ne reconnaissent pas les caracteres unicode encodees de la forme %uxxxx.
	 *
	 * @param string $str Chaine de caractere a decoder
	 * @return string
	 */
	public static function fsb_utf8_decode($str)
	{
		return (preg_replace('#%u([[:alnum:]]{4})#i', '&#x\\1;', $str));
	}

	/**
	 * Ajoute des zeros manquants en debut de chaine, tant que $str est inferieur a $total
	 *
	 * @param string $str Chaine initiale
	 * @param int $total Nombre de caracteres totaux que devra comporter la chaine a la fin
	 * @return string
	 */
	public static function add_zero($str, $total)
	{
		while (strlen($str) < $total)
		{
			$str = '0' . $str;
		}
		return ($str);
	}

	/**
	 * Inverse d'htmlspecialchars()
	 *
	 * @param string $str Chaine de caractere
	 * @return string
	 */
	public static function unhtmlspecialchars($str)
	{
		return (str_replace(array('&lt;', '&gt;', '&amp;', '&quot;'), array('<', '>', '&', '"'), $str));
	}

	/**
	 * Encode une chaine de caractere en caracteres hexadecimaux visibles par
	 * le navigateur, afin d'offrir une protection contre la lecture de donnees dans
	 * la source de la page (anti spam)
	 *
	 * @param string $str Chaine de caractere a encoder
	 * @return string
	 */
	public static function no_spam($str)
	{
		$new = '';
		$len = strlen($str);
		for ($i = 0; $i < $len; $i++)
		{
			$new .= '&#x' . bin2hex($str[$i]) . ';';
		}
		return ($new);
	}

	/**
	 * Verifie si un mot match un pattern simple avec des *
	 *
	 * @param string $pattern Chaine de caractere acceptant * comme caractere special
	 * @param string $word Chaine verifiant le pattern
	 * @return bool
	 */
	public static function is_matching($pattern, $word)
	{
		return ((preg_match('/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/i', $word)) ? true : false);
	}

	/**
	 * Fonction recursive qui verifie si un caractere est echappe
	 *
	 * @param unknown_type $pos Position du caractere dans la chaine
	 * @param unknown_type $str Chaine de caractere
	 * @return bool
	 */
	public static function is_escaped($pos, &$str)
	{
		if (($pos - 1) >= 0 && $str[$pos - 1] != '\\')
		{
			return (false);
		}
		else if (($pos - 1) >= 0 && ($pos - 2) >= 0 && $str[$pos - 1] == '\\' && $str[$pos - 2] != '\\')
		{
			return (true);
		}
		else if (($pos - 1) >= 0 && ($pos - 2) >= 0 && $str[$pos - 1] == '\\' && $str[$pos - 2] == '\\')
		{
			return (self::is_escaped($pos - 2, $str));
		}
		return (false);
	}

	/**
	 * Decoupe une chaine de caractere en plusieurs sous chaine dans un tableau, en fonction de delimiteurs.
	 * Les chaines comprises dans des quote ne sont pas decoupees.
	 *
	 * @param string[array $del Delimiteur (ou tableau de delimiteur)
	 * @param string $str Chaine a decouper
	 * @return array
	 */
	public static function split($del, $str)
	{
		if (!is_array($del))
		{
			$del = array($del);
		}

		// Decoupage de la chaine en fonction des delimiteurs et des quotes ' et "
		$del[] = '\'';
		$del[] = '"';
		preg_match_all('/(\\\)*(' . str_replace('/', '\/', implode('|', $del)) . ')/', $str, $m, PREG_OFFSET_CAPTURE);
		$count = count($m[0]);

		$return = array();
		$tmp = '';
		$last_offset = 0;
		$current_quote = '';
		for ($i = 0; $i < $count; $i++)
		{
			// En cas de quote ou de simple quote, on ne prendra pas en compte les delimiteurs
			if ($m[2][$i][self::CHAR] == '\'' || $m[2][$i][self::CHAR] == '"')
			{
				if (!self::is_escaped(strlen($m[0][$i][self::CHAR]) - 1, $m[0][$i][self::CHAR]))
				{
					$tmp .= substr($str, $last_offset, $m[0][$i][self::OFFSET] - $last_offset);
					if ($m[2][$i][self::CHAR] == $current_quote)
					{
						$tmp .= $current_quote;
						$current_quote = '';
					}
					else if (!$current_quote)
					{
						$current_quote = $m[2][$i][self::CHAR];
						$tmp .= $current_quote;
					}
					else
					{
						$tmp .= $m[2][$i][self::CHAR];
					}
				}
				else
				{
					// Les quote echapes par des \ ne sont pas gardes
					$tmp .= $m[0][$i][self::CHAR];
				}
			}
			else
			{
				// On sauve en memoire la chaine entre deux delimiteurs. Si on est dans un quote, on garde le tout
				// sous forme de chaine unique, sinon on ajoute de nouveaux elements au tableau
				$tmp .= substr($str, $last_offset, $m[0][$i][self::OFFSET] - $last_offset);
				if ($current_quote)
				{
					$tmp .= $m[0][$i][self::CHAR];
				}
				else if ($tmp)
				{
					$return[] = $tmp;
					$tmp = '';
				}
			}
			$last_offset = $m[0][$i][self::OFFSET] + strlen($m[0][$i][self::CHAR]);
		}

		$tmp .= substr($str, $last_offset, strlen($str) - $last_offset);
		if (trim($tmp))
		{
			$return[] = $tmp;
		}

		return ($return);
	}

	/**
	 * Utilise ou non un pluriel si une valeur est superieur a 1
	 *
	 * @param string $str Clef de langue, une version avec un 's' final doit exister
	 * @param int $int Valeur numerique
	 * @param bool $distinct Distingue 1 et 0 en ajoutant ou non un sufixe _none
	 * @return string
	 */
	public static function plural($str, $int, $distinct = false)
	{
		return (Fsb::$session->lang($str . (($int > 1) ? 's' : (($distinct && $int == 0) ? '_none' : ''))));
	}

	/**
	 * Affiche correctement une clef de langue ({LG_KEY} va utiliser une clef de langue "key")
	 *
	 * @param string $str Texte a parser
	 * @return string
	 */
	public static function parse_lang($str)
	{
		$str = preg_replace('#\{LG_([a-zA-Z0-9_]*?)\}#e', 'Fsb::$session->lang(strtolower(\'$1\'))', $str);

		return ($str);
	}

	/**
	 * Formatage des grands nombre suivant la langue
	 *
	 * @param int $nb Nombre a formater
	 * @param int $dec Nombre de decimales
	 * @return string
	 */
	public static function number_format($nb, $dec = 0)
	{
		return (number_format($nb, $dec, Fsb::$session->lang('nb_format_dec'), Fsb::$session->lang('nb_format_thousands')));
	}

	/**
	 * Rend les sites webs clickables
	 *
	 * @param string $str Chaine a parser
	 * @return string
	 */
	public static function parse_website($str)
	{
		$str = preg_replace('/(?<=^|[\s])((((http:\/\/|https:\/\/|ftp:\/\/|ftps:\/\/)([^ \"\t\n\r<]{3,}))))/i', '<a href="\\1">\\1</a>', $str);

		return ($str);
	}

	/**
	 * Rend les Emails clickables
	 *
	 * @param string $str Chaine a parser
	 * @return string
	 */
	public static function parse_email($str)
	{
		$fsbcode = new Parser_fsbcode();

		$str = preg_replace_callback('/(?<=^|\W)()()([a-z0-9\-_\.]+?@[a-z0-9\-_]+?\.[a-z0-9]{2,4})/i', array($fsbcode, 'generate_mail'), $str);

		return ($str);
	}
}

/* EOF */