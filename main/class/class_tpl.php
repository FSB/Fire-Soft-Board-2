<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_tpl.php
** | Begin :	13/06/2005
** | Last :		29/11/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Classe de gestion de template
** La documentation sur cette classe et ses possibilites se trouve dans ~/doc/template.html
*/
class Tpl extends Fsb_model
{
	// Repertoire du template
	public $tpl_dir = '';

	// Boolean pour savoir si on utilise le cache ou non
	public $use_cache = TRUE;

	// Tableau contenant toutes les variables du template, ainsi que les switchs et les blocks
	public $data = array();

	// Tableau contenant les alias, empiles par ordre d'aparition
	private $stack = array();

	// Contient le numero de l'alias courant, dans la pile
	private $current_stack = -1;

	// Alias courant
	public $alias = '';
	
	// Met en cache les calculs des blocks
	private $cache_block = array();
	
	// Variables pour le parse des blocks
	public $count = array();
	public $i = array();

	/*
	** Constructeur de la classe Tpl()
	** -----
	** $tpl_dir ::			Repertoire contenant les fichiers templates
	*/
	public function __construct($tpl_dir)
	{
		$this->set_template($tpl_dir);
		$this->current_stack = -1;
		$this->stack = array();
	}

	/*
	** Annonce qu'un fichier template va etre cree plus tard dans le script.
	** Cette fonction est a utiliser si vous souhaitez declarer des variables avant
	** de declarer un nom pour le fichier template.
	** -----
	** $alias ::	Nom de l'alias du futur template
	*/
	public function prepare_file($alias = 'main')
	{
		$this->data[$alias] = array(
			'file' =>		NULL,
			'var' =>		array(),
			'block' =>		array(),
			'switch' =>		array(),
		);
		
		$this->current_stack++;
		$this->stack[$this->current_stack] = $alias;
	}

	/*
	** Cree un alias ayant comme modele le template passe en parametre. L'alias est ajoute
	** dans la pile d'alias afin de permettre un imbriquement simple de templates.
	** -----
	** $alias ::		Alias du modele
	** $template ::		Modele de template a charger
	*/
	public function set_file($template, $alias = 'main')
	{
		$file = $this->tpl_dir . $template;
		if (!is_file($file))
		{
			trigger_error('Tpl->set_file :: Impossible de trouver le template : ' . $file, FSB_ERROR);
		}

		if (!isset($this->data[$alias]))
		{
			$this->data[$alias] = array(
				'file' =>		$file,
				'var' =>		array(),
				'block' =>		array(),
				'switch' =>		array(),
			);

			$this->current_stack++;
			$this->stack[$this->current_stack] = $alias;
		}
		else
		{
			$this->data[$alias]['file'] = $file;
		}
	}

	/*
	** Assigne un dossier pour le theme
	** -----
	** $tpl_dir ::			Repertoire contenant les fichiers templates
	*/
	public function set_template($tpl_dir)
	{
		// Chargement du config_tpl s'il existe
		$config_tpl_dir = substr($tpl_dir, 0, -6);
		if (file_exists($config_tpl_dir . 'config_tpl.cfg'))
		{
			Fsb::$session->style = Config_file::read($config_tpl_dir . 'config_tpl.cfg');
		}

		$this->tpl_dir = $tpl_dir;
		if (!is_dir($this->tpl_dir))
		{
			trigger_error('Tpl->Tpl :: ' . $this->tpl_dir . ' n\'est pas un repertoire', FSB_ERROR);
		}
	}

	/*
	** Ajoute un tableau de variables de templates au modele actuel
	** -----
	** $ary ::		Tableau contenant en clef les variables de templates et en valeurs ce par quoi elles
	**			seront remplacees
	** $alias ::		Alias a specifier pour assigner ces variables a un modele particulier
	*/
	public function set_vars($ary, $alias = NULL)
	{
		if (!is_array($ary))
		{
			trigger_error('Tpl->set_vars :: Le premier argument doit etre un tableau', FSB_ERROR);
		}

		$current_alias = (($alias == NULL) ? $this->stack[$this->current_stack] : $alias);
		$this->data[$current_alias]['var'] = array_merge($this->data[$current_alias]['var'], $ary);
	}

	/*
	** Ajoute un block de variables pour le modele de template
	** -----
	** $block ::	Nom du block. Si ce block est ratache a des precedents blocks, utiliser . comme separateur
	**				de block ; par exemple block1.block2.blockN
	** $ary ::		Tableau contenant en clef les variables de templates et en valeurs ce par quoi elles
	**				seront remplacees
	** $alias ::	Alias a specifier pour assigner ces variables a un modele particulier
	*/
	public function set_blocks($block, $ary = array(), $alias = NULL)
	{
		$current_alias = ($alias == NULL) ? $this->stack[$this->current_stack] : $alias;
		$explode = explode('.', $block);
		$count = count($explode) - 1;

		$tmp = &$this->data[$current_alias]['block'];
		for ($i = 0; $i < $count; $i++)
		{
			if (!isset($tmp[$explode[$i]]))
			{
				trigger_error('Tpl->set_blocks :: Le block ' . $explode[$i] . ' n\'existe pas', FSB_ERROR);
			}
			$tmp = &$tmp[$explode[$i]];
			$tmp = &$tmp[count($tmp) - 1];
		}

		if (!isset($tmp[$explode[$i]]))
		{
			$tmp[$explode[$i]] = array();
		}

		//
		// ITERATOR est une variable de template donnant l'iteration actuelle dans un block
		// SIZEOF est une variable de template donnant le nombre de cycles pour le block
		// FIRST_ROW est une variable de template definissant s'il s'agit de la premiere ligne
		// LAST_ROW est une variable definissant s'il s'agit de la derniere ligne
		//
		$ary['ITERATOR'] = count($tmp[$explode[$i]]);
		$ary['LAST_ROW'] = TRUE;
		if ($total = count($tmp[$explode[$i]]))
		{
			$ary['SIZEOF'] = &$tmp[$explode[$i]][0]['SIZEOF'];
			$ary['FIRST_ROW'] = FALSE;
			$tmp[$explode[$i]][$total - 1]['LAST_ROW'] = FALSE;
		}
		else
		{
			$ary['FIRST_ROW'] = TRUE;
		}
		$tmp[$explode[$i]][] = $ary;
		$tmp[$explode[$i]][0]['SIZEOF'] = $total + 1;
	}

	/*
	** Modifie un block de variable deja defini. Il est donc possible de creer un block avec la methode Tpl::set_blocks() puis
	** de modifier par la suite des variables propres a ce block.
	** -----
	** $block ::	Nom du block. Si ce block est ratache a des precedents blocks, utiliser . comme separateur
	**				de block ; par exemple block1.block2.blockN
	** $pos ::		Position du block a modifier par rapport a la valeur courante. Par exemple si vous souhaitez modifier
	**				les valeurs des variables du precedent block declare, il faut passer -1 a la position. Par exemple :
	**					Fsb::$tpl->set_blocks('test', array('KEY' => 'VALUE'), 'alias');
	**					Fsb::$tpl->update_blocks('test', -1, array('KEY' => 'NEW_VALUE'), 'alias');
	** $ary ::		Tableau contenant en clef les variables de templates et en valeurs ce par quoi elles
	**				seront remplacees
	** $alias ::	Alias a specifier pour assigner ces variables a un modele particulier
	*/
	public function update_blocks($block, $pos, $ary, $alias = NULL)
	{
		$current_alias = ($alias == NULL) ? $this->stack[$this->current_stack] : $alias;
		$explode = explode('.', $block);
		$count = count($explode) - 1;

		$tmp = &$this->data[$current_alias]['block'];
		for ($i = 0; $i < $count - 1; $i++)
		{
			if (!isset($tmp[$explode[$i]]))
			{
				trigger_error('Tpl->update_blocks :: Le block ' . $explode[$i] . ' n\'existe pas', FSB_ERROR);
			}
			$tmp = &$tmp[$explode[$i]];
			$tmp = &$tmp[count($tmp) - 1];
		}

		// Recuperation du dernier block
		if (!isset($tmp[$explode[$i]]))
		{
			trigger_error('Tpl->update_blocks :: Le block ' . $explode[$i] . ' n\'existe pas', FSB_ERROR);
		}
		$tmp = &$tmp[$explode[$i]];
		$count_current = count($tmp);

		// Mise a jour des variables
		if (!isset($tmp[$count_current + $pos]))
		{
			trigger_error('Tpl->update_blocks :: La position indiquee n\'existe pas : ' . $pos, FSB_ERROR);
		}

		foreach ($ary AS $key => $value)
		{
			$tmp[$count_current + $pos][$key] = $value;
		}
	}

	/*
	** Cree un switch
	** -----
	** $name ::		Nom du switch
	*/
	public function set_switch($name, $alias = NULL)
	{
		$this->data[(($alias == NULL) ? $this->stack[$this->current_stack] : $alias)]['switch'][$name] = TRUE;
	}

	/*
	** Supprime un switch
	** -----
	** $name ::		Nom du switch
	*/
	public function unset_switch($name, $alias = NULL)
	{
		unset($this->data[(($alias == NULL) ? $this->stack[$this->current_stack] : $alias)]['switch'][$name]);
	}

	/*
	** Parse le fichier modele donne
	** -----
	** $alias ::		Alias du fichier a parser
	** $keep_alias ::	Definit si on doit changer l'alias ou non
	*/
	public function parse($alias = 'main', $keep_alias = FALSE)
	{
		if (!isset($this->data[$alias]))
		{
			trigger_error('Tpl->parse :: L\'alias ' . $alias . ' n\'existe pas', FSB_ERROR);
		}

		// L'alias est depile
		if (!$keep_alias)
		{
			$this->alias = $alias;
		}
		array_pop($this->stack);
		$this->current_stack--;

		// Verification de la mise en cache du template
		if ($this->use_cache)
		{
			$cache = Cache::factory('tpl');
			$hash = md5($this->data[$alias]['file']);

			if ($cache->exists($hash) && filemtime($this->data[$alias]['file']) == $cache->get_time($hash))
			{
				$tpl_code = $cache->get($hash);
			}
			else
			{
				$tpl_code = $this->compile($alias);
				$cache->put($hash, $tpl_code, $this->data[$alias]['file'], filemtime($this->data[$alias]['file']));
			}
		}
		else
		{
			$tpl_code = $this->compile($alias);
		}

		// Affichage du template parse
		if (Fsb::$debug->show_output)
		{
			eval($tpl_code);
			unset($tpl_code);
		}
	}

	/*
	** Retourne la valeur d'une variable de template courante
	*/
	public function get_current_var($str)
	{
		$str_block = '';
		eval('$value = ' . $this->block2code($str, $str_block, TRUE) . ';');
		return ($value);
	}

	/*
	** Compile le code du template avec les variables de template de celui
	** ci pour former un code PHP executable.
	** -----
	** $alias ::	Alias du fichier a compiler
	** $content ::	Contenu par defaut
	*/
	public function compile($alias = 'main', $content = NULL)
	{
		if ($content == NULL)
		{
			// Lecture du contenu du fichier
			$filename = $this->data[$alias]['file'];
			if (empty($filename))
			{
				trigger_error('Tpl::compile() :: Aucun fichier template n\'a ete renseigne, vous devez utiliser la methode Tpl::set_file()', FSB_ERROR);
			}
			else if (!file_exists($filename))
			{
				trigger_error("Tpl::compile() :: Le fichier $filename n'existe pas", FSB_ERROR);
			}
			$content = file_get_contents($filename);
		}

		// Supression des commentaires de template
		$content = preg_replace('#<!--\#(.*?)\#-->#si', '', $content);
		
		// Parse des inclusions
		$content = preg_replace('#<include name="([a-zA-Z0-9/\._]+)"( */)?>#si', "<?php \$this->include_tpl('\\1'); ?>", $content);

		// Parse des variables de langues / FAQ / IMG / configuration
		$content = preg_replace('#\{LG_([0-9A-Z_]*?)\}#i', "<?php echo Fsb::\$session->lang(strtolower('\\1')); ?>", $content);
		$content = preg_replace('#\{FAQ_([0-9A-Z]*?)_([0-9A-Z_]*?)\}#i', "<?php echo sid(ROOT . 'index.' . PHPEXT . '?p=faq&amp;section=' . strtolower('\\1') . '&amp;area=' . strtolower('\\2')); ?>", $content);
		$content = preg_replace('#\{IMG_([0-9A-Z_]*?)\}#i', "<?php echo Fsb::\$session->img(strtolower('\\1')); ?>", $content);
		$content = preg_replace('#\{CFG_([0-9A-Z_]*?)\}#i', "<?php echo Fsb::\$cfg->get(strtolower('\\1')); ?>", $content);
		$content = preg_replace('#\{__ARG([0-9]+)\}#i', "<?php echo @func_get_arg(\\1); ?>", $content);
		
		// Parse des simples variables
		$content = preg_replace('#\{([0-9A-Z_]+)\}#i', "<?php echo @Fsb::\$tpl->data[Fsb::\$tpl->alias]['var']['\\1']; ?>", $content);

		// Parse des variables de blocks
		$content = preg_replace_callback('#\{(([0-9a-z_]+\.)+)([0-9A-Z_]+)\}#i', array(&$this, 'compile_block_vars'), $content);

		// Parse des blocks
		$content = preg_replace_callback('#<block(else)? name="([a-z0-9_.-]+)">#si', array(&$this, 'compile_block'), $content);
		$content = preg_replace('#<\/(block|blockelse|if|switch)>#si', "<?php } ?>", $content);

		// Parse des instructions de controle
		$this->compile_scripting($content);

		// Parse des fonctions
		$content = preg_replace_callback('#\#([a-zA-Z0-9_]+) *\{([^}]*?)\}#si', array(&$this, 'compile_functions'), $content);

		// Parse des fonctions de template
		$content = preg_replace_callback('#<function name="([a-zA-Z0-9_]*?)">(.*?)</function>#si', array(&$this, 'compile_functions_declaration'), $content);

		// Parse des appels de fonctions de template
		$content = preg_replace_callback('#<call name="([a-zA-Z0-9_]*?)"(.*)( */)>#i', array(&$this, 'compile_functions_call'), $content);

		// Nettoyage du code
		$content = str_replace('?><?php', '', $content);

		//echo "<xmp>$content</xmp>";exit;
		return (" ?>$content<?php ");
	}

	/*
	** Compile les instructions de controle propre au template (<if>, <switch>, etc ...)
	** -----
	** $content ::		Contenu du template
	*/
	private function compile_scripting(&$content)
	{
		$content = preg_replace_callback("/<(else)?if content=\"([a-zA-Z0-9 \n\t\-\+\*\/%=_\(\)'\"\.\$!<>:\\\]*?)\">/si", array(&$this, 'compile_scripting_vars'), $content);
		$content = preg_replace_callback('/<switch name="([a-z0-9_&|! \(\)]+)">/si', array(&$this, 'compile_switch'), $content);
		$content = preg_replace('/<else>/si', "<?php } else { ?>", $content);
		$content = preg_replace('/<variable name="([A-Z0-9_]+)" value="(.*?)"( *\/)?>/si', "<?php Fsb::\$tpl->set_vars(array('\\1' => '\\2'), Fsb::\$tpl->alias) ?>", $content);
	}

	/*
	** Compile les instructions de controle "if" et "else if", tout en gerant
	** les variables de templates qui doivent cette fois etre precedees d'un $. Par
	** exemple : <if content="$block1.block2.VAR == 51">
	*/
	private function compile_scripting_vars($match)
	{
		$else = $match[1];
		$control = $match[2];

		$control = preg_replace('#\$CFG_([0-9A-Z_]*?)( |$)#i', "Fsb::\$cfg->get(strtolower('\\1'))", $control);
		$control = preg_replace('/\$([0-9A-Z_]+)( |$)/si', "@Fsb::\$tpl->data[Fsb::\$tpl->alias]['var']['\\1']", $control);
		$control = preg_replace_callback('/\$(([0-9a-z_]+\.)+)([0-9A-Z_]+)($| )/si', array(&$this, 'compile_block_scripting_vars'), $control);
		$control = stripslashes($control);
		return ('<?php ' . ((strtolower($else) == 'else') ? '} else ' : '') .  "if ($control) { ?>");
	}

	/*
	** Remplace un block <!-- BEGIN --> par une boucle PHP for () executable
	*/
	private function compile_block($match)
	{
		$else = $match[1];
		$block = $match[2];

		$str_block = '';
		$str = $this->block2code($block, $str_block, TRUE);
		
		if ($else == 'else')
		{
			$code = "<?php if (!Fsb::\$tpl->count['$str_block']) { ?>";
		}
		else
		{
			$code = "<?php Fsb::\$tpl->count['$str_block'] = (isset($str)) ? count($str) : 0; for (Fsb::\$tpl->i['$str_block'] = 0; Fsb::\$tpl->i['$str_block'] < Fsb::\$tpl->count['$str_block']; Fsb::\$tpl->i['$str_block']++) { ?>";
		}
		return ($code);
	}

	/*
	** Compile les variables de blocks conditionelles ($FOO, $foo.BAR)
	*/
	private function compile_block_scripting_vars($match)
	{
		$match[4] = TRUE;
		return ($this->compile_block_vars($match));
	}

	/*
	** Remplace une variable de block par le tableau PHP qui lui corespond
	*/
	private function compile_block_vars($match)
	{
		$block = substr($match[1], 0, -1);
		$var = $match[3];
		$scripting = (isset($match[4])) ? TRUE : FALSE;

		$str_block = '';
		$str = $this->block2code($block, $str_block, FALSE) . "['$var']";
		if (!$scripting)
		{
			$code = "<?php echo @$str; ?>";
		}
		else
		{
			$code = " @$str ";
		}
		return ($code);
	}

	/*
	** Permet d'utiliser une fonction PHP dans le template
	*/
	private function compile_functions($match)
	{
		$name = $match[1];
		$args = $match[2];

		$args = stripslashes($args);
		$args = preg_replace('#\$LG_([0-9A-Z_]*?)(,| |$)#i', "@Fsb::\$session->lang(strtolower('\\1'))\\2", $args);
		$args = preg_replace('#\$IMG_([0-9A-Z_]*?)#i', "Fsb::\$session->img(strtolower('\\1'))", $args);
		$args = preg_replace('/\$([0-9A-Z_]+)(,| |$)/si', "@Fsb::\$tpl->data[Fsb::\$tpl->alias]['var']['\\1']\\2", $args);
		$args = preg_replace_callback('/\$(([0-9a-z_]+\.)+)([0-9A-Z_]+)($| )/si', array(&$this, 'compile_block_scripting_vars'), $args);
		return ("<?php echo $name($args); ?>");
	}

	/*
	** Compile les switch. Il est possible d'utiliser les operateurs & et | pour les switch, par exemple
	** <switch name="switch1 | switch2">
	*/
	private function compile_switch($match)
	{
		$if = $match[1];
		$if = preg_replace('#(?<=\s|\||&|\(|!|^)([a-zA-Z0-9_]+?)(?=\W|\s|\||&|\)|$)#i', "@Fsb::\$tpl->data[Fsb::\$tpl->alias]['switch']['\\1']", $if);
		$if = str_replace('&', ' AND ', $if);
		$if = str_replace('|', ' OR ', $if);
		return ("<?php if ($if) { ?>");
	}
	
	/*
	** Permet d'inclure un template dans un autre
	** -----
	** $tpl_name ::	Nom du template
	*/
	public function include_tpl($tpl_name)
	{
		$this->set_file($tpl_name, "tmp_$tpl_name");
		$this->parse("tmp_$tpl_name", TRUE);
	}

	/*
	** Parse des declarations de fonctions template
	*/
	private function compile_functions_declaration($match)
	{
		return ('<?php function __fsb_template_' . $match[1] . "(){ ?>" . $match[2] . '<?php } ?>');
	}

	/*
	** Parse des appels de fonctions templates
	*/
	private function compile_functions_call($match)
	{
		preg_match_all('#arg([0-9]+)="([^"]*)"#i', $match[2], $m);
		$count = count($m[0]);
		$args = array("'" . $match[1] . "'");
		for ($i = 0; $i < $count; $i++)
		{
			$key = $m[1][$i];
			$value = $m[2][$i];
			if (!is_numeric($value))
			{
				$value = "'" . preg_replace('#<\?php echo(.*?)(; )?\?>#si', "' . \\1 . '", $value) . "'";
			}
			$args[$key] = $value;
		}

		return ('<?php __fsb_template_' . $match[1] . '(' . implode(', ', $args) . ') ?>');
	}

	/*
	** Transforme un block du type block1.block2.blockN en chaine de caractere
	** evaluable durant la compilation comme une variable de template
	** -----
	** $block ::		Nom du block
	** $alias ::		Alias du template courant
	** $number ::		Nombre de blocks en finalite
	** $type ::			Type de compilation de block
	*/
	private function block2code($block, &$str_block, $type)
	{
		if (isset($this->cache_block[$block]))
		{
			$str_block = $this->cache_block[$block]['str_block'];
			return ($this->cache_block[$block]['str'] . ((!$type) ? "[Fsb::\$tpl->i['$str_block']]" : ''));
		}
		
		$explode = explode('.', $block);
		$count = count($explode) - 1;
		$str_block = $explode[$count];
		$str = "Fsb::\$tpl->data[Fsb::\$tpl->alias]['block']";
		for ($i = 0; $i < $count; $i++)
		{
			$str .= "['${explode[$i]}'][Fsb::\$tpl->i['" . $explode[$i] . "']]";
		}

		$str .= "['${explode[$i]}']";
		
		// Mise en cache des blocks calcules pour eviter de repasser cette etape pour les meme blocks
		$this->cache_block[$block] = array('str_block' => $str_block, 'str' => $str);
		
		if (!$type)
		{
			$str .= "[Fsb::\$tpl->i['" . $explode[$i] . "']]";
		}
		
		return ($str);
	}
}

/*
** Fonction annexe testant l'existance d'un switch
*/
function tpl_switch_exists($switch_name)
{
	return ((@Fsb::$tpl->data[Fsb::$tpl->alias]['switch'][$switch_name]) ? TRUE : FALSE);
}

/* EOF */