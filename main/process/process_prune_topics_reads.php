<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

/*
** Nettoie la table des messages lus
*/
function prune_topics_reads()
{
	Fsb::$db->update('users', array(
		'u_last_read' =>	MAX_UNREAD_TOPIC_TIME,
	), 'WHERE u_last_read < ' . MAX_UNREAD_TOPIC_TIME);

	$sql = 'SELECT u_id, u_last_read
			FROM ' . SQL_PREFIX . 'users
			WHERE u_id <> ' . VISITOR_ID . '
			ORDER BY u_id ASC';
	$result = Fsb::$db->query($sql);
	while ($row = Fsb::$db->row($result))
	{
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'topics_read
				WHERE tr_last_time < ' . ($row['u_last_read'] - (3 * ONE_MONTH)) . '
					AND u_id = ' . $row['u_id'];
		Fsb::$db->query($sql);
	}
	Fsb::$db->free($result);
}
/* EOF */