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
			$this->data[$row['f_id']] = $row;
		}
		Fsb::$db->free($result);
	}

	/*
	** Rempli les informations sur un forum
	** -----
	** $f_id ::		ID du forum
	** $data ::		Donnees du forum
	*/
	public function fill($f_id, $data)
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
		$this->_parse($this->document);
		foreach ($this->data AS $f_id => $data)
		{
			if (isset($data['is_filled']))
			{
				unset($data['f_id'], $data['is_filled']);
				Fsb::$db->update('forums', $data, 'WHERE f_id = ' . $f_id);
			}
		}
	}

	/*
	** Parse recursif des donnees des forums
	** -----
	** $node ::		Noeud actuelle dans l'arbre des forums
	** $level ::	Niveau de profondeur
	*/
	private function _parse(&$node, $level = 0)
	{
		$data = array(
			'f_total_topic' =>		0,
			'f_total_post' =>		0,
			'f_last_p_id' =>		0,
			'f_last_t_id' =>		0,
			'f_last_t_title' =>		'',
			'f_last_p_nickname' =>	'',
			'f_last_u_id' =>		0,
			'f_last_p_time' =>		0,
		);

		foreach ($node AS $f_id => $childs)
		{
			if ($level > 0 && !isset($this->data[$f_id]['is_filled']))
			{
				continue ;
			}

			if (!isset($this->data[$f_id]))
			{
				$this->fill($f_id, array());
			}

			if ($childs)
			{
				$return = $this->_parse($childs->children(), $level + 1);
				$if =										($this->data[$f_id]['f_last_p_time'] < $return['f_last_p_time']) ? TRUE : FALSE;
				$this->data[$f_id]['f_total_topic'] +=		$return['f_total_topic'];
				$this->data[$f_id]['f_total_post'] +=		$return['f_total_post'];
				$this->data[$f_id]['f_last_p_id'] =			intval(($if) ? $return['f_last_p_id'] : $this->data[$f_id]['f_last_p_id']);
				$this->data[$f_id]['f_last_t_id'] =			intval(($if) ? $return['f_last_t_id'] : $this->data[$f_id]['f_last_t_id']);
				$this->data[$f_id]['f_last_t_title'] =		($if) ? $return['f_last_t_title'] : $this->data[$f_id]['f_last_t_title'];
				$this->data[$f_id]['f_last_p_nickname'] =	($if) ? $return['f_last_p_nickname'] : $this->data[$f_id]['f_last_p_nickname'];
				$this->data[$f_id]['f_last_u_id'] =			intval(($if) ? $return['f_last_u_id'] : $this->data[$f_id]['f_last_u_id']);
				$this->data[$f_id]['f_last_p_time'] =		max($this->data[$f_id]['f_last_p_time'], $return['f_last_p_time']);
			}

			$if =						($data['f_last_p_time'] < $this->data[$f_id]['f_last_p_time']) ? TRUE : FALSE;
			$data['f_total_topic'] +=	$this->data[$f_id]['f_total_topic'];
			$data['f_total_post'] +=	$this->data[$f_id]['f_total_post'];
			$data['f_last_p_id'] =		($if) ? @$this->data[$f_id]['f_last_p_id'] : $data['f_last_p_id'];
			$data['f_last_t_id'] =		($if) ? @$this->data[$f_id]['f_last_t_id'] : $data['f_last_t_id'];
			$data['f_last_t_title'] =	($if) ? @$this->data[$f_id]['f_last_t_title'] : $data['f_last_t_title'];
			$data['f_last_p_nickname'] =($if) ? @$this->data[$f_id]['f_last_p_nickname'] : $data['f_last_p_nickname'];
			$data['f_last_u_id'] =		($if) ? @$this->data[$f_id]['f_last_u_id'] : $data['f_last_u_id'];
			$data['f_last_p_time'] =	max($data['f_last_p_time'], $this->data[$f_id]['f_last_p_time']);
		}
		return ($data);
	}
}

/* EOF */