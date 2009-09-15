<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

die('Pour pouvoir utiliser ce fichier veuillez decommenter cette ligne. <b>Ce fichier est une faille potentielle de securite</b>, ne l\'utilisez qu\'en local, ou si vous etes certain de ce que vous faites');

/**
 * Ce fichier affiche la liste des fonctions dans le dossier ~/main/fcts/
 * 
 */

foreach (array('../main/fcts/', '../main/class/') AS $dir)
{
	$fd = opendir($dir);
	while ($file = readdir($fd))
	{
		if ($file[0] != '.' && preg_match('#\.php([0-5])?$#i', $file) && is_readable($dir . $file) && $file != 'class_mail.php' && $file != 'class_smtp.php')
		{
			echo '<ul><li><h3>' . $dir . $file . '</h3></li>';
			// Contenu du fichier
			$content = file_get_contents($dir . $file);

			// On supprime le header du fichier
			$content = preg_replace('#/\*\s*\*\* \+-{51}\+(.*?)\*\* \+-{51}\+\s*\*/#si', '', $content);

			// On parse les fonctions
			echo '<ul>';
			preg_match_all('#(/\*(.*?)\*/)\s*class\s+([_a-zA-Z0-9]*?\s*)?\s+{#si', $content, $match);
			$count = count($match[0]);
			for ($i = 0; $i < $count; $i++)
			{
				// On parse le commentaire de la fonction
				$content = str_replace($match[0][$i], '', $content);
				$comment = str_replace("**", "", trim($match[2][$i]));
				echo '<li>class <strong>' . $match[3][$i] . '</strong><br />';
				echo '<span style="font-size: 12px; padding-left: 15px"><i>' . nl2br($comment) . '</i></span><br /><br />';
				echo '</li>';
			}

			preg_match_all('#(/\*(.*?)\*/)\s*function ([_a-zA-Z0-9]*?\s*\([^)]*\))?#si', $content, $match);
			$count = count($match[0]);
			for ($i = 0; $i < $count; $i++)
			{
				// On parse le commentaire de la fonction
				$comment = explode('-----', str_replace('** ', '', trim($match[2][$i])));
				$comment = array_map('trim', $comment);
				echo '<li>' . preg_replace('#([_a-zA-Z0-9]*?)\(#i', 'function <strong>\\1</strong>(', $match[3][$i], 1) . '<br />';
				echo '<span style="font-size: 12px; padding-left: 15px"><i>' . $comment[0] . '</pre></i></span><br /><br />';
				echo '</li>';
			}
			echo '</ul></ul>';
		}
	}
}
?>