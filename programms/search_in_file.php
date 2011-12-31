<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

die('Pour pouvoir utiliser ce fichier veuillez commenter cette ligne. <b>Ce fichier est une faille potentielle de sécurité</b>, ne l\'utilisez qu\'en local, ou si vous êtes certain de ce que vous faites.');

/**
 * Ce fichier permet de rechercher des donnees dans des fichiers.
 * Tres utile quand vous souhaitez retrouver tous les fichiers comportant une certaine ligne
 * de code par exemple.
 */

set_time_limit(0);

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

function clean_gpc()
{
	// On supprime toutes les variables crees par la directive register_globals
	// On stripslashes() toutes les variables GPC pour la compatibilite DBAL
	$gpc = array('_GET', '_POST', '_COOKIE');
	$magic_quote = (get_magic_quotes_gpc()) ? true : false;
	$register_globals = (ini_get('register_globals')) ? true : false;

	if ($register_globals || $magic_quote)
	{
		foreach ($gpc AS $value)
		{
			if ($register_globals)
			{
				foreach ($GLOBALS[$value] AS $k => $v)
				{
					unset($GLOBALS[$k]);
				}
			}
			
			if ($magic_quote && isset($GLOBALS[$value]))
			{
				$GLOBALS[$value] = array_map_recursive('stripslashes', $GLOBALS[$value]);
			}
		}
	}
}
clean_gpc();

$word = (isset($_POST['word'])) ? $_POST['word'] : '';
$dir = (isset($_POST['dir'])) ? $_POST['dir'] : '';
$ext = (isset($_POST['ext'])) ? $_POST['ext'] : '';
$replace = (isset($_POST['replace'])) ? $_POST['replace'] : '';
$casse = (isset($_POST['casse'])) ? $_POST['casse'] : 1;
$regexp = (isset($_POST['regexp'])) ? $_POST['regexp'] : 1;

function search_in_file($word, $dir, $ext, $casse, $regexp, $replace)
{
	$fd = opendir($dir);
	while ($file = readdir($fd))
	{
		if ($file[0] != '.' && $file != 'config.php')
		{
			if (is_dir($dir . $file))
			{
				search_in_file($word, $dir . $file . '/', $ext, $casse, $regexp, $replace);
			}
			else
			{
				if (!strlen(trim($ext)) || preg_match('#(' . implode('|', explode(',', $ext)) . ')$#i', $file))
				{
					$content_file = file_get_contents($dir . $file);
					$content = file($dir . $file);
					$count = count($content);
					$print = false;
					for ($i = 0; $i < $count; $i++)
					{
						if (preg_match('#' . (($regexp) ? str_replace('#', '\#', $word) : preg_quote($word, '#')) . '#' . (($casse) ? 'i' : ''), $content[$i]))
						{
							if (!$print)
							{
								$print = true;
								echo '<b><u>Fichier : ' . $dir . $file . '</u></b><br />';
							}
							echo '<pre>&nbsp;&nbsp;<b>' . $i . '</b>&nbsp;&nbsp;' . trim(preg_replace('#(' . (($regexp) ? str_replace('#', '\#', htmlspecialchars($word)) : preg_quote(htmlspecialchars($word), '#')) . ')#' . (($casse) ? 'i' : ''), '<b>\\1</b>', htmlspecialchars($content[$i]))) . '</pre>';
						}
					}

					// Remplacement ?
					if ($replace)
					{
						$content_file = preg_replace('#' . (($regexp) ? str_replace('#', '\#', $word) : preg_quote($word, '#')) . '#' . (($casse) ? 'i' : ''), $replace, $content_file);
						$fd2 = fopen($dir . $file, 'w');
						fwrite($fd2, $content_file);
						fclose($fd2);
					}
					
					if ($print)
					{
						echo '<br />';
					}
				}
			}
		}
	}
	closedir($fd);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
<head>
	<title>Recherche multi fichiers</title>
	
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
</head>
<body>
<form method="post" action="search_in_file.php">
Texte a chercher : <input type="text" name="word" value="<?php echo htmlspecialchars($word) ?>" size="60" /><br />
Dossier de recherche : <input type="text" name="dir" value="<?php echo $dir ?>" size="60" /><br />
Extensions : <input type="text" name="ext" value="<?php echo $ext ?>" size="60" /><br />
Remplacer par : <input type="text" name="replace" value="<?php echo $replace ?>" size="60" /><br />
Sensible a la casse : <input type="radio" name="casse" value="1" <?php echo (($casse) ? 'checked="checked"' : '') ?>/> Non&nbsp;&nbsp;&nbsp;<input type="radio" name="casse" value="0" <?php echo ((!$casse) ? 'checked="checked"' : '') ?>/> Oui<br />
Expression reguliere : <input type="radio" name="regexp" value="0" <?php echo ((!$regexp) ? 'checked="checked"' : '') ?>/> Non&nbsp;&nbsp;&nbsp;<input type="radio" name="regexp" value="1" <?php echo (($regexp) ? 'checked="checked"' : '') ?>/> Oui<br />
<input type="submit" name="submit" value="Rechercher" />
</form>
<?php
	if ($word)
	{
		search_in_file($word, '../' . $dir, $ext, $casse, $regexp, $replace);
	}
?>
</body>
</html>
