<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

error_reporting(E_ALL);

define('PHPEXT', substr(strrchr($_SERVER['PHP_SELF'],'.'), 1));
define('ROOT', '../../');
define('DEBUG', true);

if (!file_exists(ROOT . 'programms/check_environment/config.' . PHPEXT))
{
	die(utf8_decode('~/programms/check_environment/config.php file is missing.<hr />Le fichier ~/programms/check_environment/config.php est introuvable.'));
}

if (!file_exists(ROOT . 'programms/check_environment/check_environment.' . PHPEXT))
{
	die(utf8_decode('~/programms/check_environment/check_environment.php file is missing.<hr />Le fichier ~/programms/check_environment/check_environment.php est introuvable.'));
}

include(ROOT . 'programms/check_environment/config.' . PHPEXT);
include(ROOT . 'programms/check_environment/check_environment.' . PHPEXT);

/* EOF */