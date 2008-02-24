<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/process/process_prune_moved_topics.php
** | Begin :	11/07/2007
** | Last :		11/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Supprime les marqueurs des sujets deplaces dont le dernier message remonte a plus de 2 semaines
*/
function prune_moved_topics()
{
	Fsb::$db->update('topics', array(
		't_trace' =>	0,
	), 'WHERE t_trace <> 0 AND t_last_p_time < ' . (CURRENT_TIME - (2 * ONE_WEEK)));
}
/* EOF */