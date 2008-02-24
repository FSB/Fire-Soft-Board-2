<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_ajax.php
** | Begin :	03/09/2007
** | Last :		26/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion d'evenements AJAX
*/
class Ajax extends Fsb_model
{
	// Constante indiquant qu'on envoie des donnees text/plain au navigateur
	const TXT = 1;

	// Constante indiquant qu'on envoie des donnees text/xml au navigateur
	const XML = 2;

	// Liste des evenements
	protected $events = array();

	/*
	** Ajoute un evenement
	** Le nombre d'arguments de cette fonction est variable, cependant les trois premiers arguments sont
	** indispensables :
	**	Premier argument ::		constante Ajax::TXT ou Ajax::XML
	**	Second argument ::		nom de l'evenement
	**	Troisieme argument ::	fonction de callback appellee pour l'evenement
	**	Autres arguments ::		Arguments additionels pour la fonction de callback
	*/
	public function add_event($type, $name, $callback)
	{
		$count = func_num_args();
		if ($count < 3)
		{
			trigger_error('Au moins 3 parametres doivent etre passes a la methode Ajax::add_event()', FSB_ERROR);
		}

		// On recupere les arguments de la fonction
		$argv =		array();
		for ($i = 3; $i < $count; $i++)
		{
			$argv[] = func_get_arg($i);
		}

		$this->events[$name] = array(
			'type' =>		$type,
			'callback' =>	$callback,
			'argv' =>		$argv,
		);
	}

	/*
	** Supprime un evenement
	** -----
	** $name ::		Nom de l'evenement
	*/
	public function drop_event($name)
	{
		if (isset($this->events[$name]))
		{
			unset($this->events[$name]);
		}
	}

	/*
	** Declenche un evenement
	** -----
	** $name ::		Nom de l'evenement
	*/
	public function trigger($name)
	{
		if (isset($this->events[$name]))
		{
			// Appel du callback pour l'evenement
			if (function_exists($this->events[$name]['callback']))
			{
				$return = call_user_func_array($this->events[$name]['callback'], $this->events[$name]['argv']);
				if ($return !== NULL)
				{
					// Generation du Content-type
					switch ($this->events[$name]['type'])
					{
						case self::XML :
							Http::header('Content-type', 'text/xml');
						break;

						case self::TXT :
						default :
							Http::header('Content-type', 'text/plain');
						break;
					}

					echo $return;
				}
			}
		}
		exit;
	}
}

/* EOF */