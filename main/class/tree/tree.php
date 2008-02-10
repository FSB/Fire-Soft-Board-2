<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/tree/tree.php
** | Begin :	17/08/2007
** | Last :		20/08/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Génère un arbre à partir d'une liste d'ID et de parents
*/
class Tree extends Fsb_model
{
	public $document = array();
	private $stack = array();
	private $tmp;

	/*
	** Ajoute un item à l'arbre
	*/
	public final function add_item($id, $parent, $data)
	{
		if ($parent === NULL)
		{
			$this->tmp = &$this->document;
		}
		else
		{
			$this->tmp = &$this->stack[$parent];
		}

		$this->tmp[$id] = new Tree_node($data);
		$this->stack[$id] = &$this->tmp[$id]->children;
	}
}

/*
** Feuilles pour l'arbre
*/
class Tree_node extends Fsb_model
{
	public $children = array();
	public $data = array();

	public function __construct($data)
	{
		$this->data = $data;
	}

	/*
	** Retourne une information
	** -----
	** $key ::	Clef de l'information
	*/
	public function get($key)
	{
		return ((isset($this->data[$key])) ? $this->data[$key] : NULL);
	}

	/*
	** Liste des enfants
	*/
	public function children()
	{
		return ($this->children);
	}

	/*
	** Liste des ID de tous les enfants
	*/
	public function allChildren()
	{
		$list = $this->children;
		$return = array_keys($list);
		foreach ($list AS $child)
		{
			$return = array_merge($return, $child->allChildren());
		}
		return ($return);
	}
}

/* EOF */