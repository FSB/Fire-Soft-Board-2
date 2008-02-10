<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/process/process_prune_rsa_keys.php
** | Begin :	11/07/2007
** | Last :		13/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

$GLOBALS['use_register_shutdown'] = FALSE;

/*
** Régénère les clefs RSA
*/
function prune_rsa_keys()
{
	$rsa = new Rsa();
	$rsa->regenerate_keys();
}
/* EOF */