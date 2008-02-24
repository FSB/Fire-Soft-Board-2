<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/process/process_prune_sessions.php
** | Begin :	11/07/2007
** | Last :		11/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

$GLOBALS['use_register_shutdown'] = FALSE;

/*
** Supprime les sessions perimees
*/
function prune_sessions()
{
	$sql = 'DELETE FROM ' . SQL_PREFIX . 'sessions
				WHERE s_time < ' . (CURRENT_TIME - ONE_HOUR);
	Fsb::$db->query($sql);

	// Optimisation de la table session
	fsb_import('process_prune_database');
	prune_database(array(SQL_PREFIX . 'sessions'));
}
/* EOF */