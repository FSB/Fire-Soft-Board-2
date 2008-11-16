<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/tree/tree_forum.php
** | Begin :	04/10/2006
** | Last :		11/09/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Permet de manipuler les forums / sous forums comme un arbre
*/
class Tree_forum extends Tree
{
	public function __construct()
	{
		foreach (get_forums() AS $f)
		{
			$this->add_item($f['f_id'], $f['f_parent'], array(
				'f_total_topic' =>		$f['f_total_topic'],
				'f_total_post' =>		$f['f_total_post'],
				'f_last_p_id' =>		$f['f_last_p_id'],
				'f_last_t_id' =>		$f['f_last_t_id'],
				'f_last_p_time' =>		$f['f_last_p_time'],
				'f_last_u_id' =>		$f['f_last_u_id'],
				'f_last_p_nickname' =>	$f['f_last_p_nickname'],
				'f_last_t_title' =>		$f['f_last_t_title'],
			));
		}
	}
	
	public function update_stats($forums)
	{
		// Le forum, ainsi que ses parents, doivent être resynchronisés
		$update = $forums;
		foreach ($forums AS $f_id)
		{
			$update = array_merge($update, $this->getByID($f_id)->parents);
		}

		// On recupere les dernieres informations des forums
		$sql = 'SELECT f.f_id, t.t_id, t.t_title, t.t_last_p_id, t.t_last_p_time, t.t_last_u_id, t.t_last_p_nickname
				FROM ' . SQL_PREFIX . 'forums f
				LEFT JOIN ' . SQL_PREFIX . 'topics t
					ON f.f_id = t.f_id
					AND t.t_id = (
						SELECT t2.t_id
						FROM ' . SQL_PREFIX . 'topics t2
						WHERE t2.f_id = f.f_id
						ORDER BY t2.t_last_p_time DESC
						LIMIT 1
					)
				WHERE 1 = 1
					' . (($update) ? ' AND f.f_id IN (' . implode(', ', $update) . ') ' : '');
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$this->merge_item($row['f_id'], array(
				'f_last_p_id' =>		$row['t_last_p_id'],
				'f_last_t_id' =>		$row['t_id'],
				'f_last_p_time' =>		$row['t_last_p_time'],
				'f_last_u_id' =>		$row['t_last_u_id'],
				'f_last_p_nickname' =>	$row['t_last_p_nickname'],
				'f_last_t_title' =>		$row['t_title'],
			));
		}
		Fsb::$db->free($result);
		
		// On recupere le total de sujets / messages des forums
		$sql = 'SELECT f.f_id, COUNT(t.t_id) AS f_total_topic, SUM(t.t_total_post) AS f_total_post
				FROM ' . SQL_PREFIX . 'forums f
				LEFT JOIN ' . SQL_PREFIX . 'topics t
					ON f.f_id = t.f_id
				GROUP BY f.f_id';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$this->merge_item($row['f_id'], array(
				'f_total_topic' =>	$row['f_total_topic'],
				'f_total_post' =>	$row['f_total_post'],
			));
		}
		Fsb::$db->free($result);

		$this->resync_stats($this->document);

		// Mise a jour des donnees
		if (!$update)
		{
			foreach (get_forums() AS $f)
			{
				$update[] = $f['f_id'];
			}
		}
		
		foreach ($update AS $f_id)
		{
			if ($f_id != 0 && $data = $this->getByID($f_id)->data)
			{
				Fsb::$db->update('forums', $data, 'WHERE f_id = ' . $f_id);
			}
		}
	}
	
	public function merge_item($id, $data)
	{
		$data = array_merge($this->getByID($id)->data, $data);
		$this->update_item($id, $data);
	}
	
	public function resync_stats(&$node)
	{
		foreach ($node->children AS $id => $child)
		{
			$this->resync_stats($child);
			if ($node->id == 0)
			{
				continue;
			}

			$node->data['f_total_post'] += $child->data['f_total_post'];
			$node->data['f_total_topic'] += $child->data['f_total_topic'];
			if ($node->data['f_last_p_time'] < $child->data['f_last_p_time'])
			{
				$node->data['f_last_t_id'] = $child->data['f_last_t_id'];
				$node->data['f_last_p_id'] = $child->data['f_last_p_id'];
				$node->data['f_last_p_time'] = $child->data['f_last_p_time'];
				$node->data['f_last_p_nickname'] = $child->data['f_last_p_nickname'];
				$node->data['f_last_t_title'] = $child->data['f_last_t_title'];
				$node->data['f_last_u_id'] = $child->data['f_last_u_id'];
			}
		}
	}
}

/* EOF */