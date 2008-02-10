<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/process/process_prune_pm.php
** | Begin :	11/07/2007
** | Last :		11/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Supprime les messages privés trop "vieux" (plus de 6 mois)
*/
function prune_pm()
{
	$sql = 'DELETE FROM ' . SQL_PREFIX . 'mp
			WHERE mp_time < ' . (CURRENT_TIME - (ONE_MONTH * 6)) . '
				AND (mp_type = ' . MP_INBOX . ' OR mp_type = ' . MP_OUTBOX . ')';
	Fsb::$db->query($sql);
}
/* EOF */