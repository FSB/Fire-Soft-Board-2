<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/process/process_prune_bad_data.php
** | Begin :	11/07/2007
** | Last :		11/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Supprime les informations inutiles dans la base de donnée (messages sans sujets, sujets sans messages, etc ..)
*/
function prune_bad_data()
{
	// Supprime les commentaires des messages abusifs, sans parents
	$sql = 'SELECT pa_id
			FROM ' . SQL_PREFIX . 'posts_abuse
			WHERE pa_parent > 0
				AND pa_parent NOT IN (
					SELECT pa_id
					FROM ' . SQL_PREFIX . 'posts_abuse
					WHERE pa_parent = 0
				)';
	$result = Fsb::$db->query($sql);
	$idx = array();
	while ($row = Fsb::$db->row($result))
	{
		$idx[] = $row['pa_id'];
	}
	Fsb::$db->free($result);

	if ($idx)
	{
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'posts_abuse
				WHERE pa_id IN (' . implode(', ', $idx) . ')';
		Fsb::$db->query($sql);
	}

	// Supprime les sujets sans messages
	$sql = 'SELECT t.t_id
			FROM ' . SQL_PREFIX . 'topics t
			LEFT JOIN ' . SQL_PREFIX . 'posts p
				ON t.t_first_p_id = p.p_id
			WHERE p.p_id IS NULL';
	$result = Fsb::$db->query($sql);
	$idx = array();
	while ($row = Fsb::$db->row($result))
	{
		$idx[] = $row['t_id'];
	}
	Fsb::$db->free($result);

	if ($idx)
	{
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'topics
				WHERE t_id IN (' . implode(', ', $idx) . ')';
		Fsb::$db->query($sql);
	}

	// Supprime les messages sans sujets
	$sql = 'SELECT p.p_id
			FROM ' . SQL_PREFIX . 'posts p
			LEFT JOIN ' . SQL_PREFIX . 'topics t
				ON p.t_id = t.t_id
			WHERE t.t_id IS NULL';
	$result = Fsb::$db->query($sql);
	$idx = array();
	while ($row = Fsb::$db->row($result))
	{
		$idx[] = $row['p_id'];
	}
	Fsb::$db->free($result);

	if ($idx)
	{
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'posts
				WHERE p_id IN (' . implode(', ', $idx) . ')';
		Fsb::$db->query($sql);
	}
}
/* EOF */