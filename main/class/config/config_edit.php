<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/config/config_edit.php
** | Begin :	20/01/2006
** | Last :		17/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion d'une configuration dynamique
*/
class Config_edit extends Fsb_model
{
	// Tableau de configuration
	public $cfg = array();

	// Nom de la configuration courante pour la ligne
	public $name;

	// Arguments de la ligne courante
	private $args;

	// Prefixe de langue à utiliser
	private $lang_prefix;

	/*
	** CONSTRUCTEUR
	** -----
	** $current_cfg ::		Tableau de valeur de configuration à utiliser
	*/
	public function __construct(&$cfg, $lang_prefix)
	{
		$this->cfg = $cfg;
		$this->lang_prefix = $lang_prefix;
	}

	/*
	** Ajoute une catégorie de configuration
	** -----
	* $cat_name ::		Nom de la catégorie
	** $explain ::		Explication de la categorie
	*/
	public function set_cat($cat_name, $explain = NULL)
	{
		Fsb::$tpl->set_blocks('cat', array(
			'NAME' =>		$cat_name,
			'EXPLAIN' =>	$explain,
		));
	}

	/*
	** Affiche une line de configuration
	** -----
	** $name ::		Nom de la configuration
	** $method ::	Méthode de la classe à utiliser
	** $args ::		Arguments de la méthode, sous forme de chaîne de caractère (qui sera évaluée)
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
				$arg = (!is_array($value)) ? '\'' . str_replace("'", "\'", $value) . '\'' : var_export($value, TRUE);
			}

			// Arguments
			eval("\$this->args = $arg;");

			// Nom de la configuration courante
			$this->name = $name;

			// Appel de la méthode
			$this->$method();
		}
	}

	/*
	** Valide des données de configuration, à partir d'un champ "type" dans la base de donnée
	** qui peut prendre par exemple comme valeurs : int, unsigned int, regexp ma_regexp
	** ------
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
					// Si la regexp n'est pas validée on vide le champ
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

	//
	// ========== METHODES D'AFFICHAGE DE CONFIGURATION DYNAMIQUE ==========
	//

	/*
	** Affiche une ligne avec un oui / non
	*/
	private function put_boolean()
	{
		Fsb::$tpl->set_blocks('cat.line.put_boolean', array(
			'L_ACTION' =>		Fsb::$session->lang($this->lang_prefix . $this->name),
			'NAME' =>			$this->name,
			'EXPLAIN' =>		(Fsb::$session->lang($this->lang_prefix . $this->name . '_explain')) ? Fsb::$session->lang($this->lang_prefix . $this->name . '_explain') : NULL,
			'SEPARATOR' =>		(count((array) $this->args) > 2) ? '<br />' : '&nbsp;'
		));

		foreach ((array)$this->args AS $lang => $value)
		{
			Fsb::$tpl->set_blocks('cat.line.put_boolean.row', array(
				'LANG' =>		((Fsb::$session->lang($lang)) ? Fsb::$session->lang($lang) : $lang),
				'VALUE' =>		$value,
				'CHECKED' =>	($this->cfg[$this->name] == $value) ? TRUE : FALSE,
			));
		}
	}

	/*
	** Affiche une ligne avec le code HTML spécifié en paramètre
	*/
	private function put_html_code()
	{
		Fsb::$tpl->set_blocks('cat.line.put_html_code', array(
			'L_ACTION' =>		Fsb::$session->lang($this->lang_prefix . $this->name),
			'CODE' =>			$this->args,
			'EXPLAIN' =>		(Fsb::$session->lang($this->lang_prefix . $this->name . '_explain')) ? Fsb::$session->lang($this->lang_prefix . $this->name . '_explain') : NULL,
		));
	}

	/*
	** Affiche une ligne avec un champ de type "text"
	*/
	private function put_text()
	{
		Fsb::$tpl->set_blocks('cat.line.put_text', array(
			'L_ACTION' =>		Fsb::$session->lang($this->lang_prefix . $this->name),
			'EXPLAIN' =>		(Fsb::$session->lang($this->lang_prefix . $this->name . '_explain')) ? Fsb::$session->lang($this->lang_prefix . $this->name . '_explain') : NULL,
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

	/*
	** Affiche une ligne avec un champ de type "textarea"
	*/
	private function put_textarea()
	{
		Fsb::$tpl->set_blocks('cat.line.put_textarea', array(
			'L_ACTION' =>		Fsb::$session->lang($this->lang_prefix . $this->name),
			'EXPLAIN' =>		(Fsb::$session->lang($this->lang_prefix . $this->name . '_explain')) ? Fsb::$session->lang($this->lang_prefix . $this->name . '_explain') : NULL,
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