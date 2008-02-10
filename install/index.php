<?php
/*
** +---------------------------------------------------+
** | Name :		~/install/index.php
** | Begin :	03/09/2007
** | Last :		03/09/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

error_reporting(E_ALL);

define('PHPEXT', substr(strrchr($_SERVER['PHP_SELF'],'.'), 1));
define('ROOT', '../');
define('DEBUG', TRUE);

if (version_compare(phpversion(), '5.0.0', '<'))
{
	die('Your PHP\'s version isn\'t compatible with FSB2, only PHP5 and more are supported.<hr />Votre version de PHP est incompatible avec FSB2, seuls PHP5 et plus sont supportés.');
}

if (!file_exists(ROOT . 'install/install.' . PHPEXT))
{
	die('~/install/install.php file is missing.<hr />Le fichier ~/install/install.php est introuvable.');
}

include(ROOT . 'install/install.' . PHPEXT);

/* EOF */