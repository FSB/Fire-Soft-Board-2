<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

/**
 * Supprime les marqueurs des sujets deplaces dont le dernier message remonte a plus de 2 semaines
 *
 */
function prune_moved_topics()
{
	Fsb::$db->update('topics', array(
		't_trace' =>	0,
	), 'WHERE t_trace <> 0 AND t_last_p_time < ' . (CURRENT_TIME - (2 * ONE_WEEK)));
}
/* EOF */