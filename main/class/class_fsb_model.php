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
 * Classe generale devant etre etendue par toutes les autres
 */
class Fsb_model
{
	/**
	 * Methode magique affichant inteligement un objet.
	 * 
	 * Par exemple :
	 * <code>
	 * echo $obj;
	 * </code>
	 *
	 * @return string
	 */
	public function __toString()
	{
		$str = '<b>Classname :</b> ' . get_class($this) . '<br />';
		$str .= '<b>Properties :</b><ul style="margin: 0">';
		foreach ($this AS $property => $value)
		{
			$str .= '<li><b>' . $property . '</b> = <pre style="display: inline">' . var_export($value, true) . '</pre></li>';
		}
		$str .= '</ul>';
		
		return ($str);
	}

	/**
	 * Capture les methodes inexistantes et gere la surcharge de methode _set_*() et _get_*()
	 *
	 * @param string $method Nom de la methode
	 * @param array $attr Arguments de la methode
	 * @return mixed Valeur de retour de la methode
	 */
	public function __call($method, $attr)
	{
		$before = substr($method, 0, 5);
		$after = substr($method, 5);
		if ($before == '_set_')
		{
			$this->_set($after, $attr[0]);
			return ;
		}
		else if ($before == '_get_')
		{
			return ($this->_get($after));
		}

		// Pour la methode magique __sleep() lors de la serialization
		if ($method == '__sleep')
		{
			return (array_keys(get_object_vars($this)));
		}

		trigger_error('Call to undefined method ' . $method . ' in class ' . get_class(), FSB_ERROR);
	}

	/**
	 * Affecte une valeur a une propriete
	 *
	 * @param string $property Nom de la propriete
	 * @param mixed $value Valeur de la propriete
	 */
	public function _set($property, $value)
	{
		$this->$property = $value;
	}

	/**
	 * Recupere la valeur d'une propriete
	 *
	 * @param string $property Nom de la propriete
	 * @return mixed Valeur de la propriete
	 */
	public function _get($property)
	{
		if (isset($this->$property))
		{
			return ($this->$property);
		}
		return (null);
	}
}
/* EOF */