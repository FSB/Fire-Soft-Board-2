<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_fsb_model.php
** | Begin :	20/06/2007
** | Last :		17/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Toutes les classes doivent étendre cette classe
*/
class Fsb_model
{
	/*
	** Affichage intéligent d'un objet
	*/
	public function __toString()
	{
		$str = '<b>Classname :</b> ' . get_class($this) . '<br />';
		$str .= '<b>Properties :</b><ul style="margin: 0">';
		foreach ($this AS $property => $value)
		{
			$str .= '<li><b>' . $property . '</b> = <pre style="display: inline">' . var_export($value, TRUE) . '</pre></li>';
		}
		$str .= '</ul>';
		
		return ($str);
	}

	/*
	** Surcharge de méthodes
	** Les fonctions peuvent désormais faire $this->set_my_attribute('value') ce qui aura comme effet $this->_set('my_attribute', 'value')
	** et $this->_get_my_attribute() ce qui donnera $this->_get('my_attribute')
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

		// Pour la méthode magique __sleep() lors de la sérialization
		if ($method == '__sleep')
		{
			return (array_keys(get_object_vars($this)));
		}

		trigger_error('Call to undefined method ' . $method . ' in class ' . get_class(), FSB_ERROR);
	}

	/*
	** Affectation de propriété
	*/
	public function _set($property, $value)
	{
		$this->$property = $value;
	}

	/*
	** Valeur d'une propriété
	*/
	public function _get($property)
	{
		if (isset($this->$property))
		{
			return ($this->$property);
		}
		return (NULL);
	}
}
/* EOF */