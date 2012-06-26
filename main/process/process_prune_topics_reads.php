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
 * Nettoie la table des messages lus
 *
 */
function prune_topics_reads()
{
	$sql = 'DELETE FROM ' . SQL_PREFIX . 'topics_read
			LEFT JOIN ' . SQL_PREFIX . 'topics ON topics_read.t_id = topics.t_id
			WHERE topics.t_last_p_time < ' . MAX_UNREAD_TOPIC_TIME . ';';

	Fsb::$db->query($sql);
}
/* EOF */