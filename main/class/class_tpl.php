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
 * Classe de gestion des templates
 */
class Tpl extends Fsb_model
{
	/**
	 * Repertoire du template
	 *
	 * @var string
	 */
	public $tpl_dir = '';

	/**
	 * Si on utilise le cache
	 *
	 * @var bool
	 */
	public $use_cache = true;

	/**
	 * Contient toutes les informations dynamiques du template : variables, switchs et blocks
	 *
	 * @var array
	 */
	public $data = array();

	/**
	 * Contient les alias, empiles par ordre d'aparition
	 *
	 * @var array
	 */
	private $stack = array();

	/**
	 * Contient le numero de l'alias courant, dans la pile
	 *
	 * @var int
	 */
	private $current_stack = -1;

	/**
	 * Alias courant
	 *
	 * @var string
	 */
	public $alias = '';
	
	/**
	 * Met en cache les calculs des blocks
	 *
	 * @var array
	 */
	private $cache_block = array();
	
	/**
	 * Nombre d'iteration dans les blocks
	 *
	 * @var array
	 */
	public $count = array();
	
	/**
	 * Iteration actuelle dans un block
	 *
	 * @var array
	 */
	public $i = array();

	/**
	 * Constructeur
	 *
	 * @param string $tpl_dir Repertoire contenant les fichiers templates
	 */
	public function __construct($tpl_dir)
	{
		$this->set_template($tpl_dir);
		$this->current_stack = -1;
		$this->stack = array();
	}

	/**
	 * Annonce qu'un fichier template va etre cree plus tard dans le script.
	 * Cette fonction est a utiliser si vous souhaitez declarer des variables avant
	 * de declarer un nom pour le fichier template.
	 *
	 * @param string $alias Nom de l'alias du futur template
	 */
	public function prepare_file($alias = 'main')
	{
		$this->data[$alias] = array(
			'file' =>		null,
			'var' =>		array(),
			'block' =>		array(),
			'switch' =>		array(),
		);
		
		$this->current_stack++;
		$this->stack[$this->current_stack] = $alias;
	}

	/**
	 * Definit un fichier template
	 *
	 * @param string $template Nom du fichier template a charger
	 * @param string $alias Alias du template
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

	/**
	 * Definit un dossier contenant des templates
	 *
	 * @param string $tpl_dir Repertoire contenant les fichiers templates
	 */
	public function set_template($tpl_dir)
	{
		// Chargement du config_tpl s'il existe
		$config_tpl_dir = substr($tpl_dir, 0, -6);
		if (file_exists($config_tpl_dir . 'config_tpl.cfg'))
		{
			Fsb::$session->style = Config_file::read($config_tpl_dir . 'config_tpl.cfg');
			
			// Chargement de la langue du theme si besoin
			if (Fsb::$session->getStyle('config', 'extra_lang') == 'true')
			{
				if (file_exists($config_tpl_dir . 'lang/' . Fsb::$session->data['u_language'] . '.php'))
				{
					Fsb::$session->load_lang($config_tpl_dir . 'lang/' . Fsb::$session->data['u_language'] . '.php',  true );
				}
				else if (file_exists($config_tpl_dir . 'lang/' . Fsb::$cfg->get('default_lang') . '.php'))
				{
					Fsb::$session->load_lang($config_tpl_dir . 'lang/' . Fsb::$cfg->get('default_lang') . '.php',  true );
				}
			}
		}

		$this->tpl_dir = $tpl_dir;
		if (!is_dir($this->tpl_dir))
		{
			trigger_error('Tpl->Tpl :: ' . $this->tpl_dir . ' n\'est pas un repertoire', FSB_ERROR);
		}
	}

	/**
	 * Ajoute un tableau de variables de templates
	 *
	 * @param array $ary Liste des variables de templates avec en clef leur noms
	 * @param unknown_type $alias Alias tu template
	 */
	public function set_vars($ary, $alias = null)
	{
		if (!is_array($ary))
		{
			trigger_error('Tpl->set_vars :: Le premier argument doit etre un tableau', FSB_ERROR);
		}

		$current_alias = (($alias == null) ? $this->stack[$this->current_stack] : $alias);
		$this->data[$current_alias]['var'] = array_merge($this->data[$current_alias]['var'], $ary);
	}

	/**
	 * Ajoute un block de variables pour le modele de template
	 *
	 * @param string $block Nom du block. Utiliser . comme délimiteur de blocks
	 * @param array $ary Variables pour ce block
	 * @param string $alias Alias du template
	 */
	public function set_blocks($block, $ary = array(), $alias = null)
	{
		$current_alias = ($alias == null) ? $this->stack[$this->current_stack] : $alias;
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
		$ary['LAST_ROW'] = true;
		if ($total = count($tmp[$explode[$i]]))
		{
			$ary['SIZEOF'] = &$tmp[$explode[$i]][0]['SIZEOF'];
			$ary['FIRST_ROW'] = false;
			$tmp[$explode[$i]][$total - 1]['LAST_ROW'] = false;
		}
		else
		{
			$ary['FIRST_ROW'] = true;
		}
		$tmp[$explode[$i]][] = $ary;
		$tmp[$explode[$i]][0]['SIZEOF'] = $total + 1;
	}

	/**
	 * Modifie un block de variable deja defini. Il est donc possible de creer un block avec la methode Tpl::set_blocks()
	 * puis de modifier par la suite des variables propres a ce block.
	 *
	 * @param string $block Nom du block. Utiliser . comme délimiteur de blocks
	 * @param int $pos Position du block a modifier par rapport a la valeur courante.
	 * 							Par exemple si vous souhaitez modifier les valeurs des variables du precedent block declare,
	 * 							il faut passer -1 a la position. Par exemple :
	 * 								Fsb::$tpl->set_blocks('test', array('KEY' => 'VALUE'), 'alias');
	 * 								Fsb::$tpl->update_blocks('test', -1, array('KEY' => 'NEW_VALUE'), 'alias');
	 * @param array $ary Variables pour le block
	 * @param string $alias Alias du template
	 */
	public function update_blocks($block, $pos, $ary, $alias = null)
	{
		$current_alias = ($alias == null) ? $this->stack[$this->current_stack] : $alias;
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

	/**
	 * Cree un switch
	 *
	 * @param string $name Nom du switch
	 * @param string $alias Alias du template
	 */
	public function set_switch($name, $alias = null)
	{
		$this->data[(($alias == null) ? $this->stack[$this->current_stack] : $alias)]['switch'][$name] = true;
	}

	/**
	 * Supprime un switch
	 *
	 * @param string $name Nom du switch
	 * @param string $alias Alias du template
	 */
	public function unset_switch($name, $alias = null)
	{
		unset($this->data[(($alias == null) ? $this->stack[$this->current_stack] : $alias)]['switch'][$name]);
	}


	/**
	 * Parse et affiche le template
	 *
	 * @param string $alias Alias du template
	 * @param string $keep_alias Si l'alias doit etre conserve ou change
	 */
	public function parse($alias = 'main', $keep_alias = false)
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

	/**
	 * Retourne la valeur d'une variable de template
	 *
	 * @param string $str Nom de la variable
	 * @return mixed
	 */
	public function get_current_var($str)
	{
		$str_block = '';
		eval('$value = ' . $this->block2code($str, $str_block, true) . ';');
		return ($value);
	}

	/**
	 * Compile le code du template avec les variables de template de celui ci pour former un code PHP executable.
	 * 
	 * @param string $alias Alias du template a compiler
	 * @param string $content Contenu par defaut
	 */
	public function compile($alias = 'main', $content = null)
	{
		if ($content == null)
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

	/**
	 * Compile les instructions de controle propre au template (<if>, <switch>, etc ...)
	 *
	 * @param string $content Contenu du template
	 */
	private function compile_scripting(&$content)
	{
		$content = preg_replace_callback("/<(else)?if content=\"([a-zA-Z0-9 \n\t\-\+\*\/%=_\(\)'\"\.\$!<>:\\\]*?)\">/si", array(&$this, 'compile_scripting_vars'), $content);
		$content = preg_replace_callback('/<switch name="([a-z0-9_&|! \(\)]+)">/si', array(&$this, 'compile_switch'), $content);
		$content = preg_replace('/<else>/si', "<?php } else { ?>", $content);
		$content = preg_replace('/<variable name="([A-Z0-9_]+)" value="(.*?)"( *\/)?>/si', "<?php Fsb::\$tpl->set_vars(array('\\1' => '\\2'), Fsb::\$tpl->alias) ?>", $content);
	}

	/**
	 * Compile les instructions de controle "if" et "else if", tout en gerant les variables
	 * de templates qui doivent cette fois etre precedees d'un $. Par exemple :
	 * 	<if content="$block1.block2.VAR == 51">
	 *
	 * @param array $match
	 * @return string
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

	/**
	 * Remplace un block par une boucle PHP for () executable
	 *
	 * @param array $match
	 * @return string
	 */
	private function compile_block($match)
	{
		$else = $match[1];
		$block = $match[2];

		$str_block = '';
		$str = $this->block2code($block, $str_block, true);
		
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

	/**
	 * Compile les variables de blocks conditionelles ($FOO, $foo.BAR)
	 *
	 * @param array $match
	 * @return string
	 */
	private function compile_block_scripting_vars($match)
	{
		$match[4] = true;
		return ($this->compile_block_vars($match));
	}

	/**
	 * Remplace une variable de block par le tableau PHP qui lui corespond
	 *
	 * @param array $match
	 * @return string
	 */
	private function compile_block_vars($match)
	{
		$block = substr($match[1], 0, -1);
		$var = $match[3];
		$scripting = (isset($match[4])) ? true : false;

		$str_block = '';
		$str = $this->block2code($block, $str_block, false) . "['$var']";
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

	/**
	 * Permet d'utiliser une fonction PHP dans le template
	 *
	 * @param array $match
	 * @return string
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

	/**
	 * Compile les switch. Il est possible d'utiliser les operateurs & et | pour les switch,
	 * par exemple <switch name="switch1 | switch2">
	 *
	 * @param array $match
	 * @return string
	 */
	private function compile_switch($match)
	{
		$if = $match[1];
		$if = preg_replace('#(?<=\s|\||&|\(|!|^)([a-zA-Z0-9_]+?)(?=\W|\s|\||&|\)|$)#i', "@Fsb::\$tpl->data[Fsb::\$tpl->alias]['switch']['\\1']", $if);
		$if = str_replace('&', ' AND ', $if);
		$if = str_replace('|', ' OR ', $if);
		return ("<?php if ($if) { ?>");
	}

	/**
	 * Permet d'inclure un template dans un autre
	 *
	 * @param string $tpl_name Nom du template
	 */
	public function include_tpl($tpl_name)
	{
		$this->set_file($tpl_name, "tmp_$tpl_name");
		$this->parse("tmp_$tpl_name", true);
	}

	/**
	 * Parse des declarations de fonctions template
	 *
	 * @param array $match
	 * @return string
	 */
	private function compile_functions_declaration($match)
	{
		return ('<?php function __fsb_template_' . $match[1] . "(){ ?>" . $match[2] . '<?php } ?>');
	}

	/**
	 * Parse des appels de fonctions templates
	 *
	 * @param array $match
	 * @return string
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

	/**
	 * Transforme un block du type block1.block2.blockN en chaine de caractere
	 * evaluable durant la compilation comme une variable de template
	 *
	 * @param string $block Nom du block
	 * @param string $str_block
	 * @param string $type Type de compilation de block
	 * @return string
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

/**
 * Fonction annexe testant l'existance d'un switch
 *
 * @param string $switch_name Nom du switch
 * @return bool
 */
function tpl_switch_exists($switch_name)
{
	return ((@Fsb::$tpl->data[Fsb::$tpl->alias]['switch'][$switch_name]) ? true : false);
}

/* EOF */