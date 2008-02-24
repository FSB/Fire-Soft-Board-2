<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_call.php
** | Begin :	20/06/2007
** | Last :		20/06/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Permet de gerer les appels aux methodes des classes des pages en fonction des proprietes de la page.
** Le but etant de permettre une genericite des appels sur la plupart des pages.
*/
class Call extends Fsb_model
{
	private $obj;
	private $break = FALSE;

	/*
	** CONSTRUCTEUR
	** -----
	** $obj ::		Objet dont on va modifier les propriete et appeler les methodes
	*/
	public function __construct(&$obj)
	{
		$this->obj = &$obj;
	}

	/*
	** Gestion des modules de la page
	** -----
	** $tree ::		Arbre d'instruction pour les modules
	*/
	public function module($module)
	{
		// Module de la page
		$this->obj->module = Http::request('module');
		if (!in_array($this->obj->module, $module['list']))
		{
			$this->obj->module = $module['default'];
		}
		Display::header_module($module['list'], $this->obj->module, $module['url'], $module['lang']);
	}

	/*
	** Gestion des elements POST pour executer des methodes ou changer la valeur de proprietes
	** -----
	** $tree ::		Arbre d'instruction pour les elements POST
	*/
	public function post($posts)
	{
		foreach ($posts AS $key => $value)
		{
			if (Http::request($key, 'post'))
			{
				// Appel de fonction ?
				if (!is_array($value) && $value[0] == ':')
				{
					$function = substr($value, 1);
					$this->obj->$function();
					return (TRUE);
				}
				// Valeur de variable
				else
				{
					// Si on precise le nom
					if (is_array($value))
					{
						list($name, $content) = each($value);
					}
					else
					{
						$name = 'mode';
						$content = $value;
					}
					$this->obj->$name = $value;
				}
			}
		}
		return (FALSE);
	}

	/*
	** Gestion recursives des variables
	** -----
	** $vars ::		Arbre des variables
	*/
	public function functions($vars)
	{
		if ($this->break)
		{
			return ;
		}

		// Parcourt des proprietes
		foreach ($vars AS $name => $list_values)
		{
			// Parcourt des valeurs que peuvent prendre les proprietes
			foreach ($list_values AS $key => $value)
			{
				// Si la propriete de l'objet vaut une de ces valeurs, ou bien qu'on tombe sur la valeur par defaut
				if ($this->obj->$name == $key || $key == 'default')
				{
					// Si cette valeur est elle meme un tableau de variables on relance la routine
					if (is_array($value))
					{
						$this->functions($value);
						if ($this->break)
						{
							return ;
						}
					}
					// Sinon on execute la methode
					else
					{
						$this->obj->$value();
						$this->break = TRUE;
						return ;
					}
				}
			}
		}
	}
}
/* EOF */