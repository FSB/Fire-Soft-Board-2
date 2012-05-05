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

/*
 ** Ce fichier permet de creer un fichier MOD a partir d'un diff
 */

// Han han ça va mettre trois plombes c'est normal ;)
set_time_limit(0);
error_reporting(E_ALL);

define('FILE_ADDED', 1);
define('FILE_DELETED', 2);
define('FILE_UPDATED', 3);

define('ROOT', '../');
define('PHPEXT', 'php');

// Activer ou non le debugage, utile pour le developpement
define('DEBUG', TRUE);

define('IN_CLI', !empty($argc));

// Protection de la page
if (strpos($_SERVER['PHP_SELF'], 'start.') !== FALSE)
{
	exit;
}

/*
 ** Methode magique permettant le chargement dynamique de classes
 */
function __autoload($classname)
{
	$classname = strtolower($classname);
	fsb_import($classname);
}

/*
 ** Permet d'acceder partout aux variables globales necessaires au fonctionement du forum
 */
class Fsb extends Fsb_model
{
	public static $cfg;
	public static $db;
	public static $debug;
	public static $frame;
	public static $mods;
	public static $session;
	public static $tpl;
}

/*
 ** Inclus un fichier dans le dossier main/ de façon intelligente
 ** -----
 ** $file ::		Nom du fichier
 */
function fsb_import($filename)
{
	static $store;

	if (!isset($store[$filename]))
	{
		$split = explode('_', $filename);
		if (file_exists(ROOT . 'main/class/class_' . $filename . '.' . PHPEXT))
		{
			include_once(ROOT . 'main/class/class_' . $filename . '.' . PHPEXT);
		}
		else if (file_exists(ROOT . 'main/class/' . $split[0] . '/' . $filename . '.' . PHPEXT))
		{
			include_once(ROOT . 'main/class/' . $split[0] . '/' . $filename . '.' . PHPEXT);
		}
		else if (file_exists(ROOT . 'main/' . $split[0] . '/' . $filename . '.' . PHPEXT))
		{
			include_once(ROOT . 'main/' . $split[0] . '/' . $filename . '.' . PHPEXT);
		}
		else if (file_exists(ROOT . 'main/' . $filename . '.' . PHPEXT))
		{
			include_once(ROOT . 'main/' . $filename . '.' . PHPEXT);
		}
		$store[$filename] = TRUE;
	}
}

// Instance de la classe Debug
Fsb::$debug = new Debug();

// Inclusion des fonctions / classes communes a toutes les pages
@include_once(ROOT . 'config/config.' . PHPEXT);

fsb_import('csts');
fsb_import('globals');
fsb_import('fcts_common');

// Recupere une liste des fichiers ajoutes / supprimes / modifies
function analyse_directories($from, $to, $del)
{
	$list = array();
	// main/lib/highlight/ => main/class/highlight/ ?
	$forbidden = array('cache/sql/', 'cache/xml/', 'cache/sql_backup/', 'cache/diff/', 'tpl/WhiteSummer/cache/', 'admin/adm_tpl/cache/', 'upload/', 'main/lib/highlight/', 'programms/', 'doc/', 'config/', 'install/', 'tpl/WhiteSummer/img/', 'admin/adm_tpl/img/', 'img/');
	foreach ($forbidden AS $f)
	{
		if (preg_match('#' . $f . '$#', $from) || preg_match('#' . $f . '$#', $to))
		{
			return (array());
		}
	}

	if (is_dir($from))
	{
		$fd = opendir($from);
		while ($file = readdir($fd))
		{
			if ($file == '.' || $file == '..' || $file == 'Thumbs.db')
			{
				continue;
			}

			if (is_dir($from . $file))
			{
				array_push($list, analyse_directories($from . $file . '/', $to . $file . '/', $del));
			}
			else if (file_exists($to . $file))
			{
				$list[] = array(
					'filename' =>	substr($to, strlen($del)) . $file,
					'status' =>		FILE_UPDATED,
				);
			}
			else
			{
				$list[] = array(
					'filename' =>	substr($to, strlen($del)) . $file,
					'status' =>		FILE_DELETED,
				);
			}
		}
		closedir($fd);
	}

	if (is_dir($to))
	{
		$fd = opendir($to);
		while ($file = readdir($fd))
		{
			if ($file == '.' || $file == '..' || $file == 'Thumbs.db')
			{
				continue;
			}

			if (!file_exists($from . $file) && !is_dir($to . $file))
			{
				$list[] = array(
					'filename' =>	substr($to, strlen($del)) . $file,
					'status' =>		FILE_ADDED,
				);
			}
			else if (!file_exists($from . $file))
			{
				array_push($list, analyse_directories($from . $file . '/', $to . $file . '/', $del));
			}
		}
		closedir($fd);
	}

	return ($list);
}

// Trie les ajouts / suppressions et mises à jours de fichiers dans un tableau
function sort_directories($analyse)
{
	$return = array('add' => array(), 'delete' => array(), 'update' => array());
	$dynamic = array(FILE_ADDED => 'add', FILE_DELETED => 'delete', FILE_UPDATED => 'update');

	foreach ($analyse AS $item)
	{
		$varname = $dynamic[$item['status']];
		$return[$varname][] = $item['filename'];
	}

	return ($return);
}

// Vérification des fichiers mis à jour
function check_updated_files($update, $from, $to)
{
	fsb_import('class_diff');

	//$update = array('main/class/class_sql_interval.php');

	$code = '';
	foreach ($update AS $k => $file)
	{
		$diff = new Diff();
		$diff->load_file($from . $file, $to . $file, true);

		$exists = false;
		foreach ($diff->entries AS $data)
		{
			if ($data['state'] != Diff::EQUAL)
			{
				$exists = true;
				break;
			}
		}

		unset($update[$k]);
		if ($exists)
		{
			$code .= create_code($file, $diff, $from);
		}
		unset($diff);
	}

	return ($code);
}

// Création du code XML
function create_code($file, &$diff, $from)
{
	$content = str_replace(array("\r\n", "\r"), array("\n", "\n"), file_get_contents($from . $file));

	$code = "\t\t<line name=\"Ouvrir\">\n\t\t\t<file>$file</file>\n";
	$s = explode('/', $file);
	if ($s[0] == 'lang' || $s[0] == 'tpl')
	{
		$code .= "\t\t\t<duplicat>" . $s[0] . "</duplicat>\n";
	}
	$code .= "\t\t</line>\n";

	foreach ($diff->entries AS $i => $item)
	{
		if ($item['state'] == Diff::EQUAL)
		{
			continue;
		}

		if ($item['state'] == Diff::CHANGE && preg_match('#\*\* \| Last :#si', $item['file1']))
		{
			continue;
		}

		switch ($item['state'])
		{
			case Diff::ADD :
				$code .= "\t\t<line name=\"Trouver\">\n\t\t\t<code><![CDATA[";

				$content_cut = $content;

				$change_find = '';
				$before = false;
				$l = 0;
				$before = ($i > 0) ? true : false;
				$modif = 1;
				$type = 'file1';
				while (1)
				{
					//echo '<xmp>' . $diff->entries[($i > 0) ? $i - $modif : $i + $modif][$type] . '</xmp><hr />';
					if ($before)
					{
						preg_match_all('#' . preg_quote($change_find . $item['file1'], '#') . '#si', $content_cut, $m);
					}
					else
					{
						preg_match_all('#' . preg_quote($item['file1'] . $change_find, '#') . '#si', $content_cut, $m);
					}

					if (count($m[0]) < 2)
					{
						break;
					}

					$split = explode("\n", str_replace("\r", "", $diff->entries[($i > 0) ? $i - $modif : $i + $modif][$type]));
					$c = count($split);
					$nb = $c;
					if ($l >= $nb)
					{
						$modif++;
						$type = ($diff->entries[($i > 0) ? $i - $modif : $i + $modif]['state'] == Diff::ADD || $diff->entries[($i > 0) ? $i - $modif : $i + $modif]['state'] == Diff::CHANGE) ? 'file2' : 'file1';
						$l = 0;
						continue;
					}

					$change_find = ($i > 0) ? $split[$c - ++$l] . "\n" . $change_find : $change_find . "\n" . $split[$l++];
				}

				if ($before)
				{
					$f1 = $change_find;
					$f2 = $item['file2'];
					$text = 'apres ajouter';
				}
				else
				{
					$f1 = $change_find;
					$f2 = $item['file2'];
					$text = 'avant ajouter';
				}

				$code .= "$f1]]></code>\n\t\t</line>\n\t\t<line name=\"$text\">\n\t\t\t<code><![CDATA[$f2]]></code>\n";
			break;

			case Diff::DROP :
				$content_cut = $content;

				$change_find = '';
				$before = false;
				$l = 0;
				$before = ($i > 0) ? true : false;
				$modif = 1;
				$type = 'file1';
				while (1)
				{
					//echo '<xmp>' . $diff->entries[($i > 0) ? $i - $modif : $i + $modif][$type] . '</xmp><hr />';
					if ($before)
					{
						preg_match_all('#' . preg_quote($change_find . $item['file1'], '#') . '#si', $content_cut, $m);
					}
					else
					{
						preg_match_all('#' . preg_quote($item['file1'] . $change_find, '#') . '#si', $content_cut, $m);
					}

					if (count($m[0]) < 2)
					{
						break;
					}

					$split = explode("\n", str_replace("\r", "", $diff->entries[($i > 0) ? $i - $modif : $i + $modif][$type]));
					$c = count($split);
					$nb = $c;
					if ($l >= $nb)
					{
						$modif++;
						$type = ($diff->entries[($i > 0) ? $i - $modif : $i + $modif]['state'] == Diff::ADD || $diff->entries[($i > 0) ? $i - $modif : $i + $modif]['state'] == Diff::CHANGE) ? 'file2' : 'file1';
						$l = 0;
						continue;
					}

					$change_find = ($i > 0) ? $split[$c - ++$l] . "\n" . $change_find : $change_find . "\n" . $split[$l++];
				}

				if ($before)
				{
					$f1 = $change_find . $item['file1'];
					$f2 = $change_find;
				}
				else
				{
					$f1 = $item['file1'] . $change_find;
					$f2 = $change_find;
				}

				$code .= "\t\t\t<line name=\"Trouver\">\n\t\t\t<code><![CDATA[" . $f1 . "]]></code>\n\t\t</line>\n";
				$code .= "\t\t<line name=\"Remplacer par\">\n\t\t\t<code><![CDATA[" . $f2 . "]]></code>\n";
			break;

			case Diff::CHANGE :
				$content_cut = $content;

				$change_find = '';
				$before = false;
				$l = 0;
				$before = ($i > 0) ? true : false;
				$modif = 1;
				$type = 'file1';
				while (1)
				{
					//echo '<xmp>' . $diff->entries[($i > 0) ? $i - $modif : $i + $modif][$type] . '</xmp><hr />';
					if ($before)
					{
						preg_match_all('#' . preg_quote($change_find . $item['file1'], '#') . '#si', $content_cut, $m);
					}
					else
					{
						preg_match_all('#' . preg_quote($item['file1'] . $change_find, '#') . '#si', $content_cut, $m);
					}

					if (count($m[0]) < 2)
					{
						break;
					}

					$split = explode("\n", str_replace("\r", "", $diff->entries[($i > 0) ? $i - $modif : $i + $modif][$type]));
					$c = count($split);
					$nb = $c;
					if ($l >= $nb)
					{
						$modif++;
						$type = ($diff->entries[($i > 0) ? $i - $modif : $i + $modif]['state'] == Diff::ADD || $diff->entries[($i > 0) ? $i - $modif : $i + $modif]['state'] == Diff::CHANGE) ? 'file2' : 'file1';
						$l = 0;
						continue;
					}

					$change_find = ($i > 0) ? $split[$c - ++$l] . "\n" . $change_find : $change_find . "\n" . $split[$l++];
				}

				if ($before)
				{
					$f1 = $change_find . $item['file1'];
					$f2 = $change_find . $item['file2'];
				}
				else
				{
					$f1 = $item['file1'] . $change_find;
					$f2 = $item['file2'] . $change_find;
				}

				//echo "<hr /><hr /><xmp>$f1</xmp>";
				//exit;

				$code .= "\t\t\t<line name=\"Trouver\">\n\t\t\t<code><![CDATA[" . $f1 . "]]></code>\n\t\t</line>\n";
				$code .= "\t\t<line name=\"Remplacer par\">\n\t\t\t<code><![CDATA[" . $f2 . "]]></code>\n";
			break;
		}
		$code .= "\t\t</line>\n";
	}

	return ($code);
}

// Liste des fichiers à copier
function check_added_files($add)
{
	$code = "\t\t<line name=\"Copier\">\n";
	foreach ($add AS $file)
	{
		$code .= "\t\t\t<file>\n\t\t\t\t<filename>$file</filename>\n";
		$split = explode('/', $file);
		if ($split[0] == 'lang' || $split[0] == 'tpl')
		{
			$code .= "\t\t\t\t<duplicat>" . $split[0] . "/</duplicat>\n";
		}
		$code .= "\t\t\t</file>\n";
	}
	$code .= "\t\t</line>\n";

	return ($code);
}

// Header XML
function add_header($from, $to)
{
	$code = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	$code .= '<?xml-stylesheet href="style/design-mod.xsl" type="text/xsl" ?>' . "\n";
	$code .= "<mod>\n";
	$code .= "\t<header>\n";
	$code .= "\t\t<name>Mise à jour $from - $to</name>\n";
	$code .= "\t\t<version>1.0.0</version>\n";
	$code .= "\t\t<autor>\n";
	$code .= "\t\t\t<name>Genova</name>\n";
	$code .= "\t\t\t<website>http://www.fire-soft-board.com</website>\n";
	$code .= "\t\t\t<email>genovakiller@yahoo.fr</email>\n";
	$code .= "\t\t</autor>\n";
	$code .= "\t\t<description><![CDATA[Met à jour votre forum depuis la version $from à la version $to]]></description>\n";
	$code .= "\t</header>\n";
	$code .= "\t<instruction>\n";

	return ($code);
}

// Footer XML
function add_footer()
{
	$code = "\t\t<line name=\"end\">\n";
	$code = "\t\t</line>\n";
	$code = "\t</instruction>\n";
	$code .= "</mod>";

	return ($code);
}

// Créé le dossier de maj
function create_dir($code, $add, $from)
{
	if (!is_dir('maj'))
	{
		mkdir('maj');
	}

	if  (!is_dir('maj/root'))
	{
		mkdir('maj/root');
	}

	foreach ($add AS $file)
	{
		$split = explode('/', $file);
		$c = count($split);
		$p = 'maj/root/';
		for ($i = 0; $i < $c - 1; $i++)
		{
			$p .= $split[$i] . '/';
			if (!is_dir($p))
			{
				mkdir($p);
			}
		}
		copy($from . $file, 'maj/root/' . $file);
	}

	$fd = fopen('maj/install.xml', 'w');
	fwrite($fd, $code);
	fclose($fd);
}

// Création du fichier de mise à jour
function create_update($from, $to, $from_version, $to_version)
{
	$analyse = analyse_directories($from, $to, $to);
	$analyse = sort_directories($analyse);

	$code = add_header($from_version, $to_version);
	$code .= check_added_files($analyse['add']);
	$code .= check_updated_files($analyse['update'], $from, $to);
	$code .= add_footer();

	create_dir($code, $analyse['add'], $to);

	return ($code);
}

// Main
if (IN_CLI)
{
	if ($argc != 5)
	{
		echo 'version_source version_destination path_soruce path_destination';
	}
	else
	{
		$from = $argv[3];
		if ($from[strlen($from) - 1] != '/') $from .= '/';
		$to = $argv[4];
		if ($to[strlen($to) - 1] != '/') $to .= '/';
		echo create_update($from, $to, $argv[1], $argv[2]);
	}
}
else
{
	if (isset($_POST['submit']))
	{
		$from_version = $_POST['version_from'];
		$to_version = $_POST['version_to'];

		$from = $_POST['from'];
		if ($from[strlen($from) - 1] != '/') $from .= '/';
		$to = $_POST['to'];
		if ($to[strlen($to) - 1] != '/') $to .= '/';
		$code = htmlspecialchars(create_update($from, $to, $from_version, $to_version));
	}
	echo <<<LIH
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
</head>
<body>
	<pre>{$code}</pre>
	<form action="create_update.php" method="post">
		Chemin vers dossier source : <input type="text" name="from" /><br />
		Chemin vers dossier a comparer : <input type="text" name="to" /><br /><br />
		Version d'origine : <input type="text" name="version_from" /><br />
		Version de la MAJ : <input type="text" name="version_to" /><br />
		<input type="submit" name="submit" value="Soumettre" />
	</form>
</body>
</html>
LIH;
}

/* EOF */
