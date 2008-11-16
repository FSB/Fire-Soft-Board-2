<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/tree/tree.php
** | Begin :	17/08/2007
** | Last :		28/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Genere un arbre a partir d'une liste d'ID et de parents
*/
class Tree extends Fsb_model
{
	public $document = null;
	protected $data = array();

	/*
	** Ajoute un item a l'arbre
	*/
	public function add_item($id, $parent, $data = null)
	{
		if (!isset($this->data[$id]))
		{
			$this->data[$id] = new Tree_node($data);
			$this->data[$id]->id = $id;
		}
		else 
		{
			$this->data[$id]->data = $data;
		}

		if (!isset($this->data[$parent]))
		{
			$this->data[$parent] = new Tree_node(array());
			$this->data[$parent]->id = $parent;
		}
		
		$this->data[$parent]->children[$id] = &$this->data[$id];
		$this->data[$id]->parent = &$this->data[$parent];
		
		$this->data[$id]->parents = $this->data[$id]->getParents();
		
		if ($this->document === null)
		{
			$this->document = &$this->data[$parent];
		}
	}
	
	public function update_item($id, $data = null)
	{
		$this->data[$id]->data = $data;
	}
	
	public function getByID($id)
	{
		return (isset($this->data[$id]) ? $this->data[$id] : null);
	}
	
	public function debug($node = null, $level = 0)
	{
		if ($node === null)
		{
			$node = $this->document;
		}
		
		echo str_repeat('---', $level) . ' [' . $node->id . ']<br />';
		foreach ($node->children AS $child)
		{
			$this->debug($child, $level + 1);
		}
	}
}

class Tree_node extends Fsb_model
{
	public $data;
	public $id;
	public $children = array();
	public $parents = array();
	public $parent;

	public function __construct($data)
	{
		$this->data = $data;
	}
	
	public function getParents()
	{
		$parents = array();
		if ($this->parent)
		{
			$p = $this->parent;
			while (true)
			{
				$parents[] = $p->id;
				if (!$p->parent)
				{
					break;
				}
				$p = $p->parent;
			}
		}

		return ($parents);
	}
}
	

/* EOF */