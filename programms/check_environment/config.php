<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */
if (!(in_array('index.php',get_included_files()))) 
{
    Header("Location: index.php" );
}

$requirements = array(
    'files' => array(
        'config/config.php', 'cache/sql/', 'cache/sql_backup/', 'cache/tpl/', 'cache/xml/', 'cache/diff/',
        'images/avatars/', 'images/ranks/', 'images/smileys/', 'mods/save/', 'upload/', 'lang/', 'tpl/', 'admin/adm_tpl/'
    ),
    'databases' => array(
        'mysql' => 'MySQL 4.1+',
        'mysqli' => 'MySQLi 4.1+',
        'sqlite' => 'SQLite',
        'pgsql' => 'PostgreSQL 8',
    ),
    'versions' => array(
        'php' => '5.0.0',
    ),
    'extensions_optionnal' => array(
        'bcmath' 
    ),
    'extensions_recommended' => array(
	     'gd' 
	),
	'extensions_required' => array (
	)
);
extract($requirements);
?>