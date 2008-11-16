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
** Un print_r() directement formate
** -----
** $array ::	Tableau
*/
function printr($array)
{
	echo '<xmp>';
	print_r($array);
	echo '</xmp>';
}

/*
** Ajoute l'ID de session a l'url
** -----
** $url ::		URL a modifier
** $force ::	Si TRUE, on force l'ajout de la SID dans l'URL
*/
function sid($url, $force = FALSE)
{
	$use_rewriting = (Fsb::$mods) ? Fsb::$mods->is_active('url_rewriting') : FALSE;
	if ($force || (!defined('SESSION_METHOD_COOKIE') && Fsb::$session->sid && (!$use_rewriting || Fsb::$session->is_logged())))
	{
		$add_end = '';
		if (preg_match('/#([a-z0-9_]+)$/i', $url, $match))
		{
			$add_end = '#' . $match[1];
			$url = preg_replace('/#([a-z0-9_]+)$/i', '', $url);
		}
		$url .= (strstr($url, '?') ? '&amp;' : '?') . 'sid=' . Fsb::$session->sid . $add_end;
	}

	if ($use_rewriting)
	{
		$url = preg_replace(array_keys($GLOBALS['_rewrite']), array_values($GLOBALS['_rewrite']), $url);
	}
	return ($url);
}

/*
** Verification de la confirmation. Retourne TRUE si tout est ok.
*/
function check_confirm()
{
	$fsb_check_sid = Http::request('fsb_check_sid', 'post');
	if ($fsb_check_sid !== Fsb::$session->sid || !Http::request('confirm_yes', 'post'))
	{
		return (FALSE);
	}
	return (TRUE);
}

/*
** Verifie la validite d'un code entre pour une image de confirmation visuelle
** -----
** $code ::		Code entre
*/
function check_captcha($code)
{
	$current_code = Fsb::$session->data['s_visual_code'];
	if (!$current_code || strpos($current_code, ':') === FALSE)
	{
		return (FALSE);
	}
	$current_code = substr($current_code, 2);

	return ((strtolower($code) === strtolower($current_code)) ? TRUE : FALSE);
}

/*
** Renvoie le chemin depuis la racine du forum
** -----
** $path ::		Chemin complet (avec __FILE__ par exemple)
** $dir_name ::	Nom du repertoire a la racine
*/
function fsb_basename($path, $dir_name)
{
	// On recupere le bon repertoire si le chemin total a ete passe en argument
	$dir_name = basename($dir_name);

	$delimiter = (preg_match('/^WIN/', PHP_OS) && strpos($path, '\\')) ? '\\' : '/';
	$ary = explode($delimiter, $path);
	$count = count($ary);
	$new = array();
	for ($i = 0, $begin = FALSE; $i < $count; $i++)
	{
		if (!$begin && $ary[$i] == $dir_name)
		{
			$begin = TRUE;
		}
		else if ($begin)
		{
			$new[] = $ary[$i];
		}
	}
	return (($begin) ? implode($delimiter, $new) : basename($path));
}

/*
** Retourne la chaine de caractere a afficher pour retourner sur une page via un clic
** -----
** $url ::		URL sans la SID
** $name ::		Nom de la page (pour la variable de langue)
*/
function return_to($url, $name)
{
	return ('<br /><br />' . sprintf(Fsb::$session->lang('return_to_' . $name), sid($url)));
}

/*
** Converti une taille en octet dans une unite plus grande si possible
** -----
** $size ::		Taille en octet
*/
function convert_size($size)
{
	if ($size >= 1048576)
	{
		return (substr($size / 1048576, 0, 5) . ' MO');
	}
	else if ($size >= 1024)
	{
		return (substr($size / 1024, 0, 5) . ' KO');
	}
	else
	{
		return (substr($size, 0, 5) . ' O');
	}
}

/*
** Converti un format de taille des variables de configuration de PHP en octet
**    http://fr2.php.net/manual/fr/function.ini-get.php
** -----
** $ini_size ::		Taille
*/
function ini_get_bytes($ini_size)
{
	$ini_size = trim($ini_size);
	$last = strtolower($ini_size[strlen($ini_size) - 1]);
	switch($last)
	{
		case 'g':
			$ini_size *= 1024;
		case 'm':
			$ini_size *= 1024;
		case 'k':
			$ini_size *= 1024;
	}

	return ($ini_size);
}

/*
** Selectionne une ligne de tableau multi dimensionel en fonction de la valeur d'une clef
** -----
** $ary ::		Tableau a fouiller
** $field ::	Nom de la clef
** $value ::	Valeur de la clef
*/
function array_select(&$ary, $field, $value)
{
	foreach ($ary AS $v)
	{
		if (isset($v[$field]) && $v[$field] == $value)
		{
			return ($v);
		}
	}
	return (NULL);
}

/*
** Ecrit des donnees dans un fichier.
** -----
** $filename ::		Nom du fichier
** $code ::			Donnees a ecrire
*/
function fsb_write($filename, $code)
{
	$fd = @fopen($filename, 'w');
	if (!$fd)
	{
		return (FALSE);
	}
	flock($fd, LOCK_EX);
	fwrite($fd, $code);
	flock($fd, LOCK_UN);
	fclose($fd);
	@chmod($filename, 0666);
	return (TRUE);
}

/*
** Agit comme la fonction array_map() de PHP, mais recusivement sur tous les
** sous tableaux.
*/
function array_map_recursive($callback, $ary)
{
	foreach ($ary AS $key => $value)
	{
		if (is_array($value))
		{
			$ary[$key] = array_map_recursive($callback, $value);
		}
		else
		{
			$ary[$key] = $callback($value);
		}
	}
	return ($ary);
}

/*
** Renvoie tous les forums dans un tableau
** -----
** $where ::		Clause WHERE de selection des forums
** $use_cache ::	Definit si on utilise le cache pour la requete
*/
function get_forums($where = '', $use_cache = TRUE)
{
	$sql = 'SELECT *
		FROM ' . SQL_PREFIX . 'forums 
		' . $where . ' 
		ORDER BY f_left';
	$result = Fsb::$db->query($sql, ($use_cache) ? 'forums_' : NULL);
	$data = Fsb::$db->rows($result);
	return ($data);
}

/*
** Retourne TRUE s'il s'agit de la derniere version comparee
** -----
** $current_version ::	Version actuelle de ce script
** $last_version ::		Derniere version connue pour le script
*/
function is_last_version($current_version, $last_version)
{
	if (!preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)(\.(RC([0-9]+)))?([a-z])?$#', $current_version, $m_current))
	{
		return (FALSE);
	}

	if (!preg_match('#^([0-9]+)\.([0-9]+)\.([0-9]+)(\.(RC([0-9]+)))?([a-z])?$#', $last_version, $m_last))
	{
		return (FALSE);
	}

	// Comparaison des versions majeures, mineures et des revisions
	for ($i = 1; $i < 4; $i++)
	{
		if ($m_current[$i] != $m_last[$i])
		{
			return (($m_current[$i] < $m_last[$i]) ? FALSE : TRUE);
		}
	}

	// Comparaison du numero de RC et des revisions mineures
	$rc_current = (isset($m_current[6])) ? $m_current[6] : '';
	$rc_last = (isset($m_last[6])) ? $m_last[6] : '';
	if (($rc_current || $rc_last) && $rc_current != $rc_last)
	{
		return ((!$rc_current || ($rc_last && $rc_current >= $rc_last)) ? TRUE : FALSE);
	}

	$minor_current = (isset($m_current[7])) ? $m_current[7] : '';
	$minor_last = (isset($m_last[7])) ? $m_last[7] : '';
	if ($minor_current || $minor_last)
	{
		return ((!$minor_current || $minor_last > $minor_current) ? FALSE : TRUE);
	}

	return (TRUE);
}

/*
** Retourne les donnees d'un fichier (extension ou bien corps)
*/
function get_file_data($filename, $type)
{
	$tmp = explode('.', basename($filename));
	switch ($type)
	{
		case 'extension' :
			return (strtolower($tmp[count($tmp) - 1]));
		break;

		case 'filename' :
			unset($tmp[count($tmp) - 1]);
			return (implode('.', $tmp));
		break;
	}
}

/*
** Un preg_replace() qui remplace tous les elements de la chaine, meme les elements imbriques
** -----
** $args :	Voir les arguments de preg_replace sur php.net
*/
function preg_replace_multiple($pattern, $replace, $str, $limit = -1)
{
	while (preg_match($pattern, $str))
	{
		$str = preg_replace($pattern, $replace, $str, $limit);
	}
	return ($str);
}

/*
** Retourne un tableau contenant un boolean signalant si un sujet est lu, et une chaine de caractere
** de type t_id=xx ou p_id=yy#yy a place a la fin de l'URL pointant vers le dernier message d'un sujet
** -----
** $p_id ::			ID du dernier message
** $p_time ::		Date du dernier message
** $t_id ::			ID du sujet
** $last_time ::	Date du dernier message lu du sujet
** $last_id ::		ID du dernier message lu du sujet
*/
function check_read_post($p_id, $p_time, $t_id, $last_time, $last_id)
{
	if ((Fsb::$session->is_logged() && (!$last_time || $last_time < $p_time) && $p_time > Fsb::$session->data['u_last_read']))
	{
		$is_read = FALSE;
		$last_url = ($last_time) ? 'p_id=' . $last_id . '#p' . $last_id : 't_id=' . $t_id;
	}
	else
	{
		$is_read = TRUE;
		$last_url = 'p_id=' . $p_id . '#p' . $p_id;
	}

	return (array($is_read, $last_url));
}

/* EOF */