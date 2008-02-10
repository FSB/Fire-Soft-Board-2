<?php
/*
** +---------------------------------------------------+
** | Name :		~/programms/stats.php
** | Begin :	08/07/2005
** | Last :		12/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

die('Pour pouvoir utiliser ce fichier veuillez decommenter cette ligne. <b>Cefichier est une faille potentielle de sécurité</b>, ne l\'utilisez qu\'en local, ou si vous êtes certain de ce que vous faites');

/*
** Ce fichier donne quelques statistiques sur le projet
*/

function php_line($dir, $exept_dir = array())
{
	$ary = array('line' => 0, 'size' => 0, 'nb' => 0, 'file' => '');
	$fd = opendir($dir);
	while ($file = readdir($fd))
	{
		if ($file[0] != '.')
		{
			if (is_dir($dir . $file) && !in_array($file, $exept_dir))
			{
				$total = php_line($dir . $file . '/', $exept_dir);
				$ary['line'] += $total['line'];
				$ary['size'] += $total['size'];
				$ary['nb'] += $total['nb'];
				$ary['file'] .= $total['file'];
			}
			else if (preg_match('/.*\.php$/i', $file))
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
echo 'Nombre total de lignes PHP : ' . $stat['line'] . '<br />Nombre de fichiers PHP : ' . $stat['nb'] . '<br />Taille totale des fichiers PHP : ' . $stat['size'] . ' octets<br />Liste des fichiers PHP :<br />' . $stat['file'];
?>