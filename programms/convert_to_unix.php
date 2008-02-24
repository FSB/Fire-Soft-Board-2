<?php
/*
** +---------------------------------------------------+
** | Name :			~/programms/convert_to_unix.php
** | Begin :		08/07/2005
** | Last :			18/06/2007
** | User :			Genova
** | Project :		Fire-Soft-Board 2 - Copyright FSB group
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

die('Pour pouvoir utiliser ce fichier veuillez decommenter cette ligne. <b>Cefichier est une faille potentielle de securite</b>, ne l\'utilisez qu\'en local, ou si vous etes certain de ce que vous faites');

/*
** Ce fichier permet de convertir les fichiers en mode UNIX 
** (en remplacant les retours chariots windows \r\n par \n)
*/

function convert_to_unix($dir)
{

	$fd = opendir($dir);
	while ($file = readdir($fd))
	{
		if ($file[0] != '.')
		{
			if (is_dir($dir . $file))
			{
				convert_to_unix($dir . $file . '/');
			}
			else if (preg_match('/\.(html|htm|php|txt|sql)/i', $file))
			{
				$content = implode("", file($dir . $file));
				if (strpos($content, "\r\n") !== false)
				{
					$fd_file = fopen($dir . $file, 'w');
					$content = str_replace("\r\n", "\n", $content);
					fwrite($fd_file, $content);
					fclose($fd_file);
					echo "Fichier $dir$file mis au format UNIX<br />";
				}
			}
		}
	}
	closedir($fd);
}

convert_to_unix('../');
?>