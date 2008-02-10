<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_string.php
** | Begin :	19/06/2007
** | Last :		17/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion des chaînes de caractère
*/
class String extends Fsb_model
{
	// Correspond aux offset générés par les fonctions preg_*() avec le flag PREG_OFFSET_CAPTURE
	const CHAR = 0;
	const OFFSET = 1;

	/*
	** Ajoute un espace dans les mots trop long
	** -----
	** $str ::		Chaîne à traiter
	** $length ::	Longueur maximale de chaque mot
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

	/*
	** Convertit une chaîne UTF8 en minuscule
	** -----
	** $str ::		Chaîne à convertir
	*/
	public static function strtolower($str)
	{
		return (strtr($str, $GLOBALS['UTF8_UPPER_TO_LOWER']));
	}

	/*
	** Convertit une chaîne UTF8 en majuscule
	** -----
	** $str ::		Chaîne à convertir
	*/
	public static function strtoupper($str)
	{
		return (strtr($str, $GLOBALS['UTF8_LOWER_TO_UPPER']));
	}

	/*
	** Substr gérant l'UTF-8, fonction reprise de SPIP (http://www.spip.net)
	** -----
	** $str ::		Chaîne de caractère
	** $start ::	Offset de départ pour la tronquature
	** $length ::	Longueur du texte à tronquer
	*/
	public static function substr($str, $start = 0, $length = NULL)
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

	/*
	** Substr gérant l'UTF-8, fonction reprise de SPIP (http://www.spip.net)
	** -----
	** $str ::		Chaîne de caractère
	** $start ::	Offset de départ pour la tronquature
	** $length ::	Longueur du texte à tronquer
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

	/*
	** Strlen gérant l'UTF-8, fonction reprise de SPIP (http://www.spip.net)
	** -----
	** $str ::		Chaîne de caractère
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

	/*
	** Converti en entités HTML des données qui ont été encodées par escape() en javascript.
	** Cette fonction est indispensable dans votre traitement AJAX côté PHP, car les fonctions
	** PHP ne reconnaissent pas les caractères unicode encodées de la forme %uxxxx.
	** -----
	** $str ::		Chaîne de caractère à décoder
	*/
	public static function fsb_utf8_decode($str)
	{
		return (preg_replace('#%u([[:alnum:]]{4})#i', '&#x\\1;', $str));
	}

	/*
	** Ajoute des zeros manquants en début de chaine, tant que $str est inférieur à $total
	** -----
	** $str ::		Chaine initiale
	** $total ::	Nombre de caractères totaux que devra comporter la chaine à la fin
	*/
	public static function add_zero($str, $total)
	{
		while (strlen($str) < $total)
		{
			$str = '0' . $str;
		}
		return ($str);
	}

	/*
	** Inverse d'htmlspecialchars()
	** -----
	** $str ::		Chaîne de caractère
	*/
	public static function unhtmlspecialchars($str)
	{
		return (str_replace(array('&lt;', '&gt;', '&amp;', '&quot;'), array('<', '>', '&', '"'), $str));
	}

	/*
	** Encode une chaîne de caractère en caractères héxadécimaux visibles par
	** le navigateur, afin d'offrir une protection contre la lecture de données dans
	** la source de la page (anti spam)
	** ----
	** $str ::		Chaîne de caractère à encoder
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

	/*
	** Renvoie TRUE si $word match $pattern
	** -----
	** $pattern ::		Chaîne de caractère acceptant * comme caractère spécial
	** $word ::			Chaîne vérifiant le pattern
	*/
	public static function is_matching($pattern, $word)
	{
		return ((preg_match('/^' . str_replace('\*', '.*', preg_quote($pattern, '/')) . '$/i', $word)) ? TRUE : FALSE);
	}


	/*
	** Fonction récursive qui vérifie si un caractère est échappé par un \
	** -----
	** $pos ::		Position du caractère dans la chaîne
	** $str ::		Chaîne de caractère
	*/
	public static function is_escaped($pos, &$str)
	{
		if (($pos - 1) >= 0 && $str[$pos - 1] != '\\')
		{
			return (FALSE);
		}
		else if (($pos - 1) >= 0 && ($pos - 2) >= 0 && $str[$pos - 1] == '\\' && $str[$pos - 2] != '\\')
		{
			return (TRUE);
		}
		else if (($pos - 1) >= 0 && ($pos - 2) >= 0 && $str[$pos - 1] == '\\' && $str[$pos - 2] == '\\')
		{
			return (self::is_escaped($pos - 2, $str));
		}
		return (FALSE);
	}

	/*
	** Découpe une chaîne de caractère en plusieurs sous chaîne dans un tableau, en fonction de délimiteurs.
	** Les chaînes comprises dans des quote ne sont pas découpées.
	** -----
	** $del ::	Délimiteur (ou tableau de délimiteur)
	** $str ::	Chaîne à découper
	*/
	public static function split($del, $str)
	{
		if (!is_array($del))
		{
			$del = array($del);
		}

		// Découpage de la chaîne en fonction des délimiteurs et des quotes ' et "
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
			// En cas de quote ou de simple quote, on ne prendra pas en compte les délimiteurs
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
					// Les quote echapés par des \ ne sont pas gardés
					$tmp .= $m[0][$i][self::CHAR];
				}
			}
			else
			{
				// On sauve en mémoire la chaîne entre deux délimiteurs. Si on est dans un quote, on garde le tout
				// sous forme de chaîne unique, sinon on ajoute de nouveaux éléments au tableau
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

	/*
	** Utilise ou non un pluriel si une valeur est supérieur à 1
	** -----
	** $str ::		Chaine de langue, une version avec un 's' final doit exister
	** $int ::		Valeur numérique
	** $distinct ::	Distingue 1 et 0 en ajoutant ou non un sufixe _none
	*/
	public static function plural($str, $int, $distinct = FALSE)
	{
		return (Fsb::$session->lang($str . (($int > 1) ? 's' : (($distinct && $int == 0) ? '_none' : ''))));
	}

	/*
	** Affiche correctement une clef de langue ({LG_KEY} va utiliser une clef de langue "key")
	** -----
	** $str ::		Texte à parser
	*/
	public static function parse_lang($str)
	{
		$str = preg_replace('#\{LG_([a-zA-Z0-9_]*?)\}#e', 'Fsb::$session->lang(strtolower(\'$1\'))', $str);

		return ($str);
	}
}

/* EOF */