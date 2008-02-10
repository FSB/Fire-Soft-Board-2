<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/process/process_prune_cache.php
** | Begin :	11/07/2007
** | Last :		11/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

$GLOBALS['use_register_shutdown'] = FALSE;

/*
** Supression des fichiers caches SQL datant de plus d'un mois
*/
function prune_cache()
{
	Fsb::$db->cache->garbage_colector(ONE_MONTH);
}
/* EOF */