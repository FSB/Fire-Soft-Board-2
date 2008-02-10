<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/process/process_prune_topics_reads.php
** | Begin :	11/07/2007
** | Last :		03/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
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