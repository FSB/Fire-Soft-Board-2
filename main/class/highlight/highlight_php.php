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
 * Colorateur syntaxique PHP
 */
class Highlight_php extends Highlight
{
	/**
	 * Liste des fonctions speciales en PHP
	 *
	 * @var array
	 */
	private static $php_special_functions = array();
	
	/**
	 * Liste des fonctions internes a PHP
	 *
	 * @var array
	 */
	public static $php_internal_functions = array();
	
	/**
	 * Liste des variables speciales en PHP
	 *
	 * @var array
	 */
	private static $php_special_vars = array();
	
	/**
	 * Liste des prototypes de fonctions en PHP
	 *
	 * @var array
	 */
	private static $lib_php = array();
	
	/**
	 * Classe deja initialisee
	 *
	 * @var bool
	 */
	private static $init = false;

	/**
	 * Constructeur, initialise une seule fois la classe
	 */
	public function __construct()
	{
		if (self::$init)
		{
			return ;
		}
		self::$init = true;

		// Fichier de configuration
		$file_content = file_get_contents(ROOT . 'main/class/highlight/keywords/highlight_php.txt');
		self::$php_special_functions = $this->get_conf($file_content, 'FUNCTIONS');
		self::$php_special_vars = $this->get_conf($file_content, 'VARS');

		// built-in php
		$defined_functions = get_defined_functions();
		self::$php_internal_functions = $defined_functions['internal'];
		unset($defined_functions);

		// On charge la librairie des fonctions PHP
		$prototype_path = ROOT . 'main/class/highlight/keywords/lib_php_prototype.txt';
		if (file_exists($prototype_path))
		{
			$lib_php = (array) @explode("\n", gzuncompress(file_get_contents($prototype_path)));
			self::$lib_php = array();
			foreach ($lib_php AS $line)
			{
				$split = explode("\t", $line);
				if (count($split) == 2)
				{
					list($name, $prototype) = $split;
					self::$lib_php[trim($name)] = trim($prototype);
				}
			}
		}
	}

	/**
	 * @see Highlight::_parse()
	 */
	protected function _parse($str)
	{
		$len = strlen($str);

		$result = '';
		$tmp = '';
		$word_open = false;
		for ($i = 0; $i < $len; $i++)
		{
			$c = $str[$i];

			if (($c == '\'' || $c == '"' || $c == '`') && !String::is_escaped($i, $str))
			{
				// Gestion des quotes ' " et `
				$result .= $this->_quote_string($str, $i, $len, $c, 'sc_php_text');
			}
			else if ($c == '/')
			{
				// Gestion des commentaires
				if ($str[$i + 1] == '/')
				{
					$result .= $this->open_style('sc_php_comment');
					while ($i < $len && $str[$i] != "\n" && $str[$i] != "\0")
					{
						$result .= $this->escape_special_char($str[$i]);
						$i++;
					}
					$result .= $this->close_style();
					$i--;
				}
				else if ($str[$i + 1] == '*')
				{
					$result .= $this->open_style('sc_php_comment');
					while ($i < $len)
					{
						$result .= $this->escape_special_char($str[$i]);
						if ($str[$i] == "*" && $str[$i + 1] == '/')
						{
							$result .= '/';
							$i++;
							break;
						}
						$i++;
					}
					$result .= $this->close_style();
				}
				else
				{
					$result .= $this->escape_special_char($c);
				}
			}
			else if ($c == '$' && !String::is_escaped($i, $str))
			{
				// Gestion des variables
				$aco_open = false;
				$i++;
				$tmp = '';
				while ($i < $len)
				{
					$c = $str[$i];
					if ($c == '{' && !$aco_open)
					{
						$aco_open = true;
					}
					else if (($c == '}' && $aco_open) || (!$aco_open && !preg_match('#[a-zA-Z0-9_]#i', $c)))
					{
						$begin = (in_array($tmp, self::$php_special_vars)) ? $this->open_style('sc_php_special_var') : $this->open_style('sc_php_var');
						$result .= $begin . '$' . $tmp;
						if ($aco_open)
						{
							$result .= '}';
							$i++;
						}
						$result .= $this->close_style();
						$aco_open = false;
						break;
					}
					$tmp .= $this->escape_special_char($c);
					$i++;
				}
				$i--;
			}
			// Fonctions ?
			else if (preg_match('#[a-zA-Z0-9_]#i', $c))
			{
				$tmp = '';
				while ($i < $len)
				{
					$c = $str[$i];
					if (!preg_match('#[a-zA-Z0-9_]#i', $c))
					{
						if (in_array(strtolower($tmp), self::$php_special_functions))
						{
							$result .= $this->open_style('sc_php_keyword') . $tmp . $this->close_style();
						}
						else if (in_array(strtolower($tmp), self::$php_internal_functions))
						{
							$helper = (isset(self::$lib_php[$tmp])) ? self::$lib_php[$tmp] : '';
							$result .= '<a href="http://www.php.net/manual/function.' . str_replace('_', '-', $tmp) . '.php" class="sc_php_function" target="_blank" title="' . $helper . '">' . $tmp . '</a>';
						}
						else
						{
							$result .= $this->open_style('sc_php_normal') . $tmp . $this->close_style();
						}
						$tmp = '';
						break;
					}
					$tmp .= $this->escape_special_char($c);
					$i++;
				}
				$i--;
			}
			// Autre
			else
			{
				$result .= $this->escape_special_char($c);
			}
		}
		
		if ($tmp)
		{
			$result .= $this->escape_special_char($tmp);
		}

		return ($result);
	}

}

/* EOF */
