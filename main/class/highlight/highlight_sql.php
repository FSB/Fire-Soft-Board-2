<?php
/*
** +---------------------------------------------------+
** | Name :			~/main/class/highlight/highlight_sql.php
** | Begin :		17/07/2007
** | Last :			13/08/2007
** | User :			Genova
** | Project :		Fire-Soft-Board 2 - Copyright FSB group
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Colorateur syntaxique SQL
*/
class Highlight_sql extends Highlight
{
	private static $sql_keywords = array();
	private static $sql_functions = array();
	private static $sql_operator = array();
	private static $init = array();

	public function __construct()
	{
		if (self::$init)
		{
			return ;
		}
		self::$init = TRUE;

		// Configuration
		$file_content = file_get_contents(ROOT . 'main/class/highlight/keywords/highlight_sql.txt');
		self::$sql_keywords = $this->get_conf($file_content, 'KEYWORDS');
		self::$sql_functions = $this->get_conf($file_content, 'FUNCTIONS');
		self::$sql_operator = $this->get_conf($file_content, 'OPERATORS');
	}

	/*
	** Parse une chaine de caractere SQL
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

	private function _sql_string($c, &$tmp)
	{
		$result = '';
		$show_style = TRUE;
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
			$show_style = FALSE;
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