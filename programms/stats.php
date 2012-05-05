<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

die('Pour pouvoir utiliser ce fichier veuillez commenter cette ligne. <b>Ce fichier est une faille potentielle de sécurité</b>, ne l\'utilisez qu\'en local, ou si vous êtes certain de ce que vous faites');

/**
 * Converti une taille en octet dans une unite plus grande si possible
 *
 * @param int $size Taille en octet
 * @return string
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

/**
 * Cette fonction donne quelques statistiques sur le projet
 *
 * @param unknown_type $dir
 * @param unknown_type $except_dir
 * @return unknown
 */
function php_line($dir, $except_dir = array())
{
	$ary = array('line' => 0, 'size' => 0, 'nb' => 0, 'file' => '');
	$fd = opendir($dir);
	while ($file = readdir($fd))
	{
		if ($file[0] != '.')
		{
			if (is_dir($dir . $file) && !in_array($file, $except_dir))
			{
				$total = php_line($dir . $file . '/', $except_dir);
				$ary['line'] += $total['line'];
				$ary['size'] += $total['size'];
				$ary['nb'] += $total['nb'];
				$ary['file'] .= $total['file'];
			}
			else if (preg_match('/\.php$/i', $file))
			{
				$ary['line'] += count(file($dir . $file));
				$ary['size'] += filesize($dir . $file);
				$ary['nb']++;
				$ary['file'] .= $dir . $file . '<br />';
			}
		}
	}
	return ($ary);
}

$stat = php_line('../', array('cache'));
echo 'Nombre total de lignes PHP : ' . number_format($stat['line'], 0, '', ' ') . '<br />';
echo 'Nombre de fichiers PHP : ' . number_format($stat['nb'], 0, '', ' ') . '<br />';
echo 'Taille totale des fichiers PHP : ' . convert_size($stat['size']) . ' (' . $stat['size'] . ' octets)<br />';
echo 'Liste des fichiers PHP :<br />' . $stat['file'];

/* EOF */
