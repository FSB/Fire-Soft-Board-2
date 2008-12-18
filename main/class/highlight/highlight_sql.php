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
 * Colorateur syntaxique SQL
 */
class Highlight_sql extends Highlight
{
	/**
	 * Mots clefs importants SQL
	 *
	 * @var array
	 */
	private static $sql_keywords = array();
	
	/**
	 * Fonctions SQL
	 *
	 * @var array
	 */
	private static $sql_functions = array();
	
	/**
	 * Operateurs SQL
	 *
	 * @var array
	 */
	private static $sql_operator = array();
	
	/**
	 * Classe deja initialisee
	 *
	 * @var bool
	 */
	private static $init = array();

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

		// Configuration
		$file_content = file_get_contents(ROOT . 'main/class/highlight/keywords/highlight_sql.txt');
		self::$sql_keywords = $this->get_conf($file_content, 'KEYWORDS');
		self::$sql_functions = $this->get_conf($file_content, 'FUNCTIONS');
		self::$sql_operator = $this->get_conf($file_content, 'OPERATORS');
	}

	/**
	 * @see Highlight::_parse()
	 */
	protected function _parse($str)
	{
		$len = strlen($str);

		$result = '';
		$tmp = '';
		for ($i = 0; $i < $len; $i++)
		{
			$c = $str[$i];

			if (($c == '\'' || $c == '"') && !String::is_escaped($i, $str))
			{
				// Gestion des quotes ' " et `
				$result .= $this->_quote_string($str, $i, $len, $c, 'sc_sql_text');
			}
			else
			{
				if (preg_match('#[a-zA-Z0-9_\-]#i', $c))
				{
					$tmp .= $c;
				}
				else
				{
					$result .= $this->_sql_string($c, $tmp);
				}
			}
		}

		if ($tmp)
		{
			$result .= $this->_sql_string('', $tmp);
		}
		return ($result);
	}

	/**
	 * Parse une chaine de caractere SQL
	 *
	 * @param string $c Caractere courant
	 * @param string $tmp
	 * @return string
	 */
	private function _sql_string($c, &$tmp)
	{
		$result = '';
		$show_style = true;
		if (is_numeric($tmp))
		{
			$result .= $this->open_style('sc_sql_numeric');
		}
		else if (in_array(strtolower($tmp), self::$sql_operator))
		{
			$result .= $this->open_style('sc_sql_operator');
		}
		else if (in_array(strtolower($tmp), self::$sql_keywords))
		{
			$result .= $this->open_style('sc_sql_keyword');
		}
		else if (in_array(strtolower($tmp), self::$sql_functions))
		{
			$result .= $this->open_style('sc_sql_function');
		}
		else
		{
			$show_style = false;
		}

		$result .= $tmp;
		$tmp = '';

		if ($show_style)
		{
			$result .= $this->close_style();
		}
		$result .= $c;
		return ($result);
	}
}


/* EOF */