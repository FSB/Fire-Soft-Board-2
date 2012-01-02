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
 * Gestion des champs de configuration dynamiques
 */
class Config_edit extends Fsb_model
{
	/**
	 * Tableau de configuration
	 *
	 * @var array
	 */
	public $cfg = array();

	/**
	 * Nom de la configuration courante lors de l'affichage d'une ligne
	 *
	 * @var string
	 */
	public $name;

	/**
	 * Arguments passes a la fonction d'affichage de la ligne courante
	 *
	 * @var mixed
	 */
	private $args;

	/**
	 * Prefixe de langue
	 *
	 * @var string
	 */
	private $lang_prefix;

	/**
	 * Constructeur
	 *
	 * @param array $cfg Tableau de valeur de configuration a utiliser
	 * @param string $lang_prefix Prefixe de langue a utiliser
	 */
	public function __construct(&$cfg, $lang_prefix)
	{
		$this->cfg = $cfg;
		$this->lang_prefix = $lang_prefix;
	}

	/**
	 * Ajoute une categorie de configuration
	 *
	 * @param string $cat_name Nom de la categorie
	 * @param string $explain Explication de la categorie
	 */
	public function set_cat($cat_name, $explain = null)
	{
		Fsb::$tpl->set_blocks('cat', array(
			'NAME' =>		$cat_name,
			'EXPLAIN' =>	$explain,
		));
	}

	/**
	 * Affiche une ligne de configuration (nom, explication et champ d'entree de valeur)
	 *
	 * @param string $name Nom de la configuration
	 * @param string $method Methode de la classe a utiliser
	 * @param string $args Arguments de la methode, sous forme de chaine de caractere (qui sera evaluee)
	 */
	public function set_line($name, $method, $args)
	{
		Fsb::$tpl->set_blocks('cat.line', array());
		if (method_exists($this, $method))
		{
			$arg = "''";
			if (!empty($args))
			{
				eval('$value = ' . $args . ';');
				$arg = (!is_array($value)) ? '\'' . str_replace("'", "\'", $value) . '\'' : var_export($value, true);
			}

			// Arguments
			eval("\$this->args = $arg;");

			// Nom de la configuration courante
			$this->name = $name;

			// Appel de la methode
			$this->$method();
		}
	}

	/**
	 * Valide des donnees de configuration a partir d'un champ $type dans la base de donnee.
	 * Champ pouvant prendre comme valeurs : int, unsigned int, regexp ma_regexp
	 *
	 * @param array $ary Tableau de configuration a valider
	 * @param string $table Nom de la table
	 * @param string $name Nom de champ contenant la clef de configuration
	 * @param string $type Nom du champ de typage
	 * @return array Tableau de configuration traite
	 */
	public function validate(&$ary, $table, $name, $type)
	{
		$return = array();
		$sql = 'SELECT ' . $name . ', ' . $type . '
				FROM ' . SQL_PREFIX . $table;
		$result = Fsb::$db->query($sql, 'config_handler_');
		while ($row = Fsb::$db->row($result))
		{
			if (isset($ary[$row[$name]]))
			{
				$return[$row[$name]] = $ary[$row[$name]];
				if ($row[$type] == 'int')
				{
					$return[$row[$name]] = intval($return[$row[$name]]);
				}
				else if ($row[$type] == 'unsigned int')
				{
					$return[$row[$name]] = intval($return[$row[$name]]);
					if ($return[$row[$name]] < 0)
					{
						$return[$row[$name]] = 0;
					}
				}
				else if (preg_match('#^regexp (.*?)$#', $row[$type], $m))
				{
					// Si la regexp n'est pas validee on vide le champ
					if (!@preg_match('#' . $m[1] . '#', $return[$row[$name]]))
					{
						$return[$row[$name]] = '';
					}
				}
			}
		}
		Fsb::$db->free($result);

		return ($return);
	}

	/**
	 * Affiche une ligne avec des bouttons radios
	 */
	private function put_boolean()
	{
		Fsb::$tpl->set_blocks('cat.line.put_boolean', array(
			'L_ACTION' =>		Fsb::$session->lang($this->lang_prefix . $this->name),
			'NAME' =>			$this->name,
			'EXPLAIN' =>		(Fsb::$session->lang($this->lang_prefix . $this->name . '_explain')) ? Fsb::$session->lang($this->lang_prefix . $this->name . '_explain') : null,
			'SEPARATOR' =>		(count((array) $this->args) > 2) ? '<br />' : '&nbsp;'
		));

		foreach ((array)$this->args AS $lang => $value)
		{
			Fsb::$tpl->set_blocks('cat.line.put_boolean.row', array(
				'LANG' =>		((Fsb::$session->lang($lang)) ? Fsb::$session->lang($lang) : $lang),
				'VALUE' =>		$value,
				'CHECKED' =>	($this->cfg[$this->name] == $value) ? true : false,
			));
		}
	}

	/**
	 * Affiche une ligne le code HTML passe en parametre
	 */
	private function put_html_code()
	{
		Fsb::$tpl->set_blocks('cat.line.put_html_code', array(
			'L_ACTION' =>		Fsb::$session->lang($this->lang_prefix . $this->name),
			'CODE' =>			$this->args,
			'EXPLAIN' =>		(Fsb::$session->lang($this->lang_prefix . $this->name . '_explain')) ? Fsb::$session->lang($this->lang_prefix . $this->name . '_explain') : null,
		));
	}

	/**
	 * Affiche une ligne avec un champ texte
	 */
	private function put_text()
	{
		Fsb::$tpl->set_blocks('cat.line.put_text', array(
			'L_ACTION' =>		Fsb::$session->lang($this->lang_prefix . $this->name),
			'EXPLAIN' =>		(Fsb::$session->lang($this->lang_prefix . $this->name . '_explain')) ? Fsb::$session->lang($this->lang_prefix . $this->name . '_explain') : null,
			'NAME' =>			$this->name,
			'VALUE' =>			htmlspecialchars($this->cfg[$this->name]),
			'TYPE' =>			($this->args && isset($this->args['password'])) ? 'password' : 'text',
		));

		foreach ((array)$this->args AS $name => $value)
		{
			if ($name != 'password')
			{
				Fsb::$tpl->set_blocks('cat.line.put_text.option', array(
					'OPT_NAME' =>	$name,
					'OPT_VALUE' =>	$value,
				));
			}
		}
	}

	/**
	 * Affiche une ligne avec un champ textarea
	 */
	private function put_textarea()
	{
		Fsb::$tpl->set_blocks('cat.line.put_textarea', array(
			'L_ACTION' =>		Fsb::$session->lang($this->lang_prefix . $this->name),
			'EXPLAIN' =>		(Fsb::$session->lang($this->lang_prefix . $this->name . '_explain')) ? Fsb::$session->lang($this->lang_prefix . $this->name . '_explain') : null,
			'NAME' =>			$this->name,
			'VALUE' =>			htmlspecialchars($this->cfg[$this->name]),
		));

		foreach ((array)$this->args AS $name => $value)
		{
			Fsb::$tpl->set_blocks('cat.line.put_textarea.option', array(
				'OPT_NAME' =>	$name,
				'OPT_VALUE' =>	$value,
			));
		}
	}
}

/* EOF */
