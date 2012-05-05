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
 * Ce fichier affiche un arbre des fichiers / dossiers de FSB2
 */

function sort_by_type($a, $b)
{
	if (strcmp($a['type'], $b['type']))
	{
		return (strcmp($a['type'], $b['type']));
	}
	else
	{
		return (strcmp($a['file'], $b['file']));
	}
}

function get_tree_data($dir)
{
	$data = array();
	$fd = opendir($dir);
	while ($file = readdir($fd))
	{
		if ($file != '.' && $file != '..')
		{
			if (is_dir($dir . $file))
			{
				$new_data = get_tree_data($dir . $file . '/');
				usort($new_data, 'sort_by_type');
				$data[] = array(
					'type' => 'dir',
					'sub' => $new_data,
					'file' => $file,
				);
			}
			else
			{
				$data[] = array(
					'type' => 'file',
					'file' => $file,
				);
			}
		}
	}
	return ($data);
}

function print_tree_data(&$data, $level = 0)
{
	foreach ($data AS $value)
	{
		if ($value['type'] == 'dir')
		{
			if ($level == 0)
			{
				echo '</span><span style="background-color: ' . check_color($value['file']) . '">';
			}

			for ($i = 0; $i < $level + 1; $i++)
			{
				echo str_repeat("\t", 1);
				if ($i < $level)
				{
					echo '|';
				}
			}
			echo '<b>+ ' . $value['file'] . "</b>\n";
		
			$level++;
			print_tree_data($value['sub'], $level);
			$level--;
		}
		else
		{
			echo str_repeat(str_repeat("\t", 1) . '|', $level) . ' - ' . $value['file'] . "\n";
		}
	}
}

function check_color($dir, $ret = false)
{
	$assoc = array(
		'admin' =>	'#d6f1fb',
		'tpl' =>	'#fbedd6',
		'main' =>	'#fbd6d6',
		'lang' =>	'#d6fbd6',
		'images' =>	'#fbd6ec',
	);

	if ($ret)
	{
		ksort($assoc);
		return ($assoc);
	}

	if (isset($assoc[$dir]))
	{
		return ($assoc[$dir]);
	}
	return ('#ffffff');
}

$c = check_color('',  true );
foreach ($c AS $dir => $color)
{
	echo '<span style="padding: 5px 10px 5px 10px; font-weight: bold; background-color: ' . $color . '; border: 1px solid #000000">' . $dir . '</span> &nbsp; ';
}
echo '<br /><br />';

$data = get_tree_data('../');
usort($data, 'sort_by_type');
echo '<pre>';
print_tree_data($data);
echo '</pre>';

/* EOF */
