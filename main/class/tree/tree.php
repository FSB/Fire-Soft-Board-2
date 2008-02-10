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
** Génère un arbre à partir d'une liste d'ID et de parents
*/
class Tree extends Fsb_model
{
	public $document = array();
	protected $stack = array();
	protected $tmp;

	/*
	** Ajoute un item à l'arbre
	*/
	public function add_item($id, $parent, $data)
	{
		if ($parent === NULL)
		{
			$this->tmp = &$this->document;
		}
		else
		{
			$this->tmp = &$this->stack[$parent]->children;
		}

		$this->tmp[$id] = new Tree_node($data, $parent);
		$this->stack[$id] = &$this->tmp[$id];

		// On récupère les parents
		if (isset($this->tmp[$id]->parent) && $this->tmp[$id]->parent)
		{
			$p = $this->tmp[$id]->parent;
			$parents = array($p);
			while ($p && isset($this->stack[$p]))
			{
				$p = $this->stack[$p]->parent;
				if ($p)
				{
					$parents[] = $p;
				}
			}

			$this->stack[$id]->parents = $parents;
		}
	}
}

/*
** Feuilles pour l'arbre
*/
class Tree_node extends Fsb_model
{
	public $children = array();
	public $parent = NULL;
	public $parents = array();
	public $data = array();

	public function __construct($data, $parent)
	{
		$this->data = $data;
		$this->parent = $parent;
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
	** Ajoute une information
	** -----
	** $key ::		Clef de l'information
	** $value ::	Valeur de l'information
	*/
	public function set($key, $value)
	{
		$this->data[$key] = $value;
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

	/*
	** Liste des ID des parents
	*/
	public function allParents()
	{
		return ($this->parents);
	}
}

/* EOF */