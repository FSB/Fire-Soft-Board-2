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
 * Gestion d'evenements AJAX
 */
class Ajax extends Fsb_model
{
	/**
	 * Indique que l'on envoie des donnees text/plain au navigateur
	 */
	const TXT = 1;

	/**
	 * Indique que l'on envoie des donnees text/xml au navigateur
	 */
	const XML = 2;

	/**
	 * Liste des evenements
	 *
	 * @var array
	 */
	protected $events = array();

	/**
	 * Ajoute un evenement a surveiller
	 *
	 * @param int $type constante Ajax::TXT ou Ajax::XML
	 * @param string $name nom de l'evenement
	 * @param string $callback fonction de callback appellee pour l'evenement
	 * @param mixed $v,... arguments de la fonction de callback
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

	/**
	 * Supprime un evenement
	 *
	 * @param string $name Nom de l'evenement
	 */
	public function drop_event($name)
	{
		if (isset($this->events[$name]))
		{
			unset($this->events[$name]);
		}
	}

	/**
	 * Declenche un evenement
	 *
	 * @param string $name Nom de l'evenement
	 */
	public function trigger($name)
	{
		if (isset($this->events[$name]))
		{
			// Appel du callback pour l'evenement
			if (function_exists($this->events[$name]['callback']))
			{
				$return = call_user_func_array($this->events[$name]['callback'], $this->events[$name]['argv']);
				if (!is_null($return))
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