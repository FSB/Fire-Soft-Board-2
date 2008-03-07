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
	private $data = array();

	/*
	** Constructeur
	*/
	public function __construct()
	{
		// On cree un arbre des forums
		$sql = 'SELECT f_id, f_parent
				FROM ' . SQL_PREFIX . 'forums
				ORDER BY f_left';
		$result = Fsb::$db->query($sql, 'forums_');
		$current_id = NULL;
		while ($row = Fsb::$db->row($result))
		{
			$this->add_item($row['f_id'], (!$row['f_parent']) ? NULL : $row['f_parent'], array());
		}
		Fsb::$db->free($result);

		if (!isset($tmp[$current_id]))
		{
			$tmp[$current_id] = array();
		}

		// On recupere les donnees des forums
		$sql = 'SELECT f_id, COUNT(*) AS f_total_topic, SUM(t_total_post) AS f_total_post, MAX(t_last_p_time) AS f_last_p_time, MAX(t_last_p_id) AS f_last_p_id
				FROM ' . SQL_PREFIX . 'topics
				GROUP BY f_id';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$this->data[$row['f_id']] = array(
				'f_total_topic' =>		0,
				'f_total_post' =>		0,
				'f_last_p_id' =>		0,
				'f_last_t_id' =>		0,
				'f_last_t_title' =>		'',
				'f_last_p_nickname' =>	'',
				'f_last_u_id' =>		0,
				'f_last_p_time' =>		0,
			);
			$this->data[$row['f_id']] = array_merge($this->data[$row['f_id']], $row);
		}
		Fsb::$db->free($result);
	}

	/*
	** Rempli les informations sur un forum
	** -----
	** $f_id ::		ID du forum
	** $data ::		Donnees du forum
	*/
	public function fill($f_id, $data = array())
	{
		if (!isset($this->data[$f_id]))
		{
			$this->data[$f_id] = array(
				'f_total_topic' =>		0,
				'f_total_post' =>		0,
				'f_last_p_id' =>		0,
				'f_last_t_id' =>		0,
				'f_last_t_title' =>		'',
				'f_last_p_nickname' =>	'',
				'f_last_u_id' =>		0,
				'f_last_p_time' =>		0,
			);
		}
		$this->data[$f_id]['is_filled'] = TRUE;
		$this->data[$f_id] = array_merge($this->data[$f_id], $data);
	}

	/*
	** Parse l'arbre des forums de facon a regrouper les informations en amont du premier forum.
	** Ainsi si par exemple un forum A a 10 messages a son actif, et que son fils B a 15 messages,
	** une fois cette methode appelee le forum A aura 25 messages.
	*/
	public function parse()
	{
		foreach ($this->document AS $f_id => $childs)
		{
			$this->_parse($this->document, $f_id);
		}

		foreach ($this->data AS $f_id => $data)
		{
			if (isset($data['is_filled']))
			{
				unset($data['f_id'], $data['is_filled']);
				Fsb::$db->update('forums', $data, 'WHERE f_id = ' . $f_id);
			}
		}
	}

	private function _parse(&$node, $id)
	{
		foreach ($node AS $f_id => $childs)
		{
			$this->_parse($childs->children(), $f_id);

			if (!isset($this->data[$f_id]))
			{
				$this->data[$f_id] = array(
					'f_total_topic' =>		0,
					'f_total_post' =>		0,
					'f_last_p_id' =>		0,
					'f_last_t_id' =>		0,
					'f_last_t_title' =>		'',
					'f_last_p_nickname' =>	'',
					'f_last_u_id' =>		0,
					'f_last_p_time' =>		0,
				);
			}

			if (!isset($this->data[$id]))
			{
				$this->data[$id] = array(
					'f_total_topic' =>		0,
					'f_total_post' =>		0,
					'f_last_p_id' =>		0,
					'f_last_t_id' =>		0,
					'f_last_t_title' =>		'',
					'f_last_p_nickname' =>	'',
					'f_last_u_id' =>		0,
					'f_last_p_time' =>		0,
				);
			}

			$this->data[$id]['f_total_topic'] +=	$this->data[$f_id]['f_total_topic'];
			$this->data[$id]['f_total_post'] +=		$this->data[$f_id]['f_total_post'];
			if ($this->data[$id]['f_last_p_time'] < $this->data[$f_id]['f_last_p_time'])
			{
				$this->data[$id]['f_last_p_id'] =		$this->data[$f_id]['f_last_p_id'];
				$this->data[$id]['f_last_t_id'] =		$this->data[$f_id]['f_last_t_id'];
				$this->data[$id]['f_last_t_title'] =	$this->data[$f_id]['f_last_t_title'];
				$this->data[$id]['f_last_p_nickname'] =	$this->data[$f_id]['f_last_p_nickname'];
				$this->data[$id]['f_last_u_id'] =		$this->data[$f_id]['f_last_u_id'];
				$this->data[$id]['f_last_p_time'] =		max($this->data[$id]['f_last_p_time'], $this->data[$f_id]['f_last_p_time']);
			}
		}
	}
}

/* EOF */