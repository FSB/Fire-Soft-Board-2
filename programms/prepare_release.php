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
 * Supprimes les fichiers mis en cache, ainsi que ces foutus Thumbs.db, vide le fichier config, prepare les index.html, etc ...
 * Fait pas le cafe par contre :(
 */

set_time_limit(0);

function delete_like($path, $end)
{
	$fd = opendir($path);
	while ($file = readdir($fd))
	{
		if ($file != '.' && $file != '..' && is_file($path . $file))
		{
			if (preg_match('#' . preg_quote($end, '#') . '$#i', $file))
			{
				unlink($path . $file);
			}
		}
	}
	closedir($fd);
}

// J'avais limite envie d'appeler cette fonction delete_fucking_thumbs() ^^
function delete_thumbs($path)
{
	$fd = opendir($path);
	while ($file = readdir($fd))
	{
		if ($file != '.' && $file != '..' && $file != '.git')
		{
			if (is_dir($path . $file))
			{
				delete_thumbs($path . $file . '/');
			}
			else if ($file == 'Thumbs.db')
			{
				unlink($path . 'Thumbs.db');
			}
		}
	}
	closedir($fd);
}

function truncate_config()
{
	$fd = fopen('../config/config.php', 'w');
	fclose($fd);
}

function set_index_html($path)
{
	if (!file_exists($path . 'index.php'))
	{
		$content = file_get_contents('index.html');
		$fd2 = fopen($path . 'index.html', 'w');
		fwrite($fd2, $content);
		fclose($fd2);
	}

	$fd = opendir($path);
	while ($file = readdir($fd))
	{
		if ($file != '.' && $file != '..' && $file != '.git')
		{
			if (is_dir($path . $file))
			{
				set_index_html($path . $file . '/');
			}
		}
	}
	closedir($fd);
}

function copy_dir($path, $to, $clean_path)
{
	if (!is_dir($to . substr($path, strlen($clean_path))))
	{
		//echo 'MKDIR = ' . $to . substr($path, strlen($clean_path)) . '<br />';
		$fullpath = $to . substr($path, strlen($clean_path));
		mkdir($fullpath);
		@chmod($fullpath, 0777);
	}

	$fd = opendir($path);
	while ($file = readdir($fd))
	{
		if ($file != '.' && $file != '..' && $file != '.git' && $file != '.gitignore')
		{
			if (is_dir($path . $file))
			{
				copy_dir($path . $file . '/', $to, $clean_path);
			}
			else
			{
				$fullpath = $to . substr($path, strlen($clean_path)) . $file;
				@touch($fullpath);
				@chmod($fullpath, 0777);
				copy($path . $file, $fullpath);
			}
		}
	}
	closedir($fd);
}

// Supprimes les fichiers en cache
delete_like('../cache/sql/', '.php');
delete_like('../cache/tpl/', '.php');
delete_like('../cache/xml/', '.php');
delete_like('../cache/diff/', '.php');
delete_like('../upload/', '.file');
delete_like('../mods/save/', '.tar.gz');
delete_like('../mods/save/', '.zip');

// Supprime les Thumbs.db
delete_thumbs('../');

// Vide le fichier de configuration
truncate_config();

// Met un index.html dans tous les repertoires en ayant besoin
set_index_html('../');

$path = '../';
$to = '../../package_fsb2/fsb2/';
$clean_path = '../';

echo 'If all of this failed, try to create manually ', realpath('../../'), '/package_fsb2/fsb2/ with appropriated chmod and owner', "<br />\n";

echo 'Copy file for package in : ', realpath($to . substr($path, strlen($clean_path))), "<br />\n";

copy_dir($path, $to, $clean_path);

echo 'Copied file for package in : ', realpath($to . substr($path, strlen($clean_path))), "<br />\n";

/* EOF */
