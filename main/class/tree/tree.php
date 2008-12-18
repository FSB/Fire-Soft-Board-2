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
 * Generation et gestion d'un arbre de donnees
 */
class Tree extends Fsb_model
{
	/**
	 * Pointe sur l'element parent de l'arbre
	 *
	 * @var Tree_node
	 */
	public $document = null;
	
	/**
	 * Contient les differentes branches de l'arbre
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Ajoute un element a l'arbre
	 *
	 * @param int $id ID de l'element
	 * @param int $parent ID du parent de l'element
	 * @param mixed $data Informations sur l'element
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
		
		if (is_null($this->document))
		{
			$this->document = &$this->data[$parent];
		}
	}
	
	/**
	 * Ecrase les informations de l'element par des nouvelles
	 *
	 * @param int $id ID de l'element
	 * @param mixed $data Nouvelles informations
	 */
	public function update_item($id, $data = null)
	{
		$this->data[$id]->data = $data;
	}
	
	/**
	 * Ajoute des informations au forum en gardant les anciennes
	 *
	 * @param int $id ID du forum
	 * @param array $data Informations a ajouter
	 */
	public function merge_item($id, $data)
	{
		$data = array_merge($this->getByID($id)->data, $data);
		$this->update_item($id, $data);
	}
	
	/**
	 * Retourne un element dont l'ID est connue
	 *
	 * @param int $id ID de l'element
	 * @return Tree_node
	 */
	public function getByID($id)
	{
		return (isset($this->data[$id]) ? $this->data[$id] : null);
	}
	
	/**
	 * Affiche une representation de l'arbre, pour le debug
	 *
	 * @param Tree_node $node
	 * @param int $level
	 */
	public function debug($node = null, $level = 0, $data = array())
	{
		if (is_null($node))
		{
			$node = $this->document;
		}
		
		echo str_repeat('---', $level) . ' [' . $node->id . ']';
		foreach ((array) $data AS $k)
		{
			echo '[' . $k . '=' . $node->get($k) . ']';
		}
		echo '<br />';

		foreach ($node->children AS $child)
		{
			$this->debug($child, $level + 1, $data);
		}
	}
}

/**
 * Feuille de l'arbre
 */
class Tree_node extends Fsb_model
{
	/**
	 * Informations sur la feuille
	 *
	 * @var mixed
	 */
	public $data;
	
	/**
	 * ID de la feuille
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Liste des enfants
	 *
	 * @var array
	 */
	public $children = array();
	
	/**
	 * Liste d'ID des parents
	 *
	 * @var array
	 */
	public $parents = array();
	
	/**
	 * Pointe sur le parent
	 *
	 * @var Tree_node
	 */
	public $parent;

	/**
	 * Constructeur, assigne les informations a la feuille
	 *
	 * @param mixed $data
	 */
	public function __construct($data = null)
	{
		$this->data = $data;
	}
	
	/**
	 * Calcul les parents de la feuille
	 *
	 * @return array
	 */
	public function getParents()
	{
		$parents = array();
		if ($this->parent)
		{
			$p = $this->parent;
			while ( true )
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
	
	/**
	 * Assigne une information
	 *
	 * @param string $key
	 */
	public function set($key, $value)
	{
		$this->data[$key] = $value;
	}
	
	/**
	 * Recupere une information
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return ((isset($this->data[$key])) ? $this->data[$key] : null);
	}
	
	/**
	 * Recupere toutes les ID des enfants
	 *
	 * @return array
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