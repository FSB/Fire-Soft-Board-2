<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/process/process_prune_forums.php
** | Begin :	11/07/2007
** | Last :		13/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

$GLOBALS['use_register_shutdown'] = FALSE;

/*
** Effectue un délestage sur les forums concernés
*/
function prune_forums()
{
	Forum::auto_prune();
}
/* EOF */