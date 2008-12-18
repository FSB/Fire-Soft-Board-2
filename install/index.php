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
define('ROOT', '../');
define('DEBUG', true);

if (version_compare(phpversion(), '5.0.0', '<'))
{
	die(utf8_decode('Your PHP\'s version isn\'t compatible with FSB2, only PHP5 and more are supported.<hr />Votre version de PHP est incompatible avec FSB2, seuls PHP5 et plus sont supportes.'));
}

if (!file_exists(ROOT . 'install/install.' . PHPEXT))
{
	die(utf8_decode('~/install/install.php file is missing.<hr />Le fichier ~/install/install.php est introuvable.'));
}

include(ROOT . 'install/install.' . PHPEXT);

/* EOF */