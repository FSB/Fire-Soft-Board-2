<?php
/*
** +---------------------------------------------------+
** | Name :			~/main/class/highlight/highlight_css.php
** | Begin :		17/07/2007
** | Last :			17/07/2007
** | User :			Genova
** | Project :		Fire-Soft-Board 2 - Copyright FSB group
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Colorateur syntaxique CSS
*/
class Highlight_css extends Highlight
{
	private static $init = array();

	public function __construct()
	{
		if (self::$init)
		{
			return ;
		}
		self::$init = TRUE;
	}

	/*
	** Parse une chaîne de caractère CSS
	*/
	protected function _parse($str)
	{
		$len = strlen($str);

		$result = '';
		for ($i = 0; $i < $len; $i++)
		{
			$c = $str[$i];

			if ($c == '{')
			{
				// Atributs
				$result .= '{';
				$i++;
				$step = 0;
				while ($i < $len)
				{
					$c = $str[$i];
					if ($c == '/' && $str[$i + 1] == '*')
					{
						// Gestion des commentaires
						$result .= $this->_comment_string($str, $i, $len, 'sc_css_comment');
					}
					else if ($c == '}')
					{
						$result .= '}';
						$i++;
						break;
					}
					else if ($step == 0 && preg_match('#[a-zA-Z_\-]#i', $c))
					{
						$step = 1;
						$tmp = '';
						while ($i < $len)
						{
							if (!preg_match('#[a-zA-Z_\-]#i', $str[$i]))
							{
								break;
							}
							$tmp .= $this->escape_special_char($str[$i]);
							$i++;
						}
						$result .= '<a href="http://wiki.media-box.net/documentation/css/' . strtolower($tmp) . '" target="_blank" class="sc_css_propertie">' . $tmp . '</a>' . $this->escape_special_char($str[$i]);
					}
					else if ($step == 1)
					{
						while ($i < $len)
						{
							$c = $str[$i];
							if ($c == '/' && $str[$i + 1] == '*')
							{
								// Gestion des commentaires
								$result .= $this->_comment_string($str, $i, $len, 'sc_css_comment');
							}
							else if (($c == '\'' || $c == '"') && !String::is_escaped($i, $str))
							{
								$result .= $this->_quote_string($str, $i, $len, $c, 'sc_css_quote');
							}
							else if ($c == '#')
							{
								$tmp = '';
								$i++;
								while ($i < $len)
								{
									if (!preg_match('#[a-fA-F0-9]#i', $str[$i]))
									{
										break;
									}
									$tmp .= $this->escape_special_char($str[$i]);
									$i++;
								}
								$result .= '<span class="sc_css_color" onmouseover="AffBulle(\'#' . $tmp . '\')" onmouseout="HideBulle()">' . $c . $tmp . '</span>';
								$i--;
							}
							else if ($c == ';' || $c == '}')
							{
								break;
							}
							else
							{
								$result .= $this->escape_special_char($str[$i]);
							}
							$i++;
						}
						$i--;
						$step = 0;
					}
					else
					{
						$result .= $this->escape_special_char($c);
					}
					$i++;
				}
				$i--;
			}
			else if ($c == '/' && $str[$i + 1] == '*')
			{
				// Gestion des commentaires
				$result .= $this->_comment_string($str, $i, $len, 'sc_css_comment');
			}
			else if ($c == '@')
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
			else if ($c == '.' || $c == ':')
			{
				$result .= $this->open_style(($c == '.') ? 'sc_css_class' : 'sc_css_element');
				while ($i < $len)
				{
					if (!preg_match('#[a-zA-Z0-9_\\' . $c . '-]#i', $str[$i]))
					{
						break;
					}
					$result .= $this->escape_special_char($str[$i]);
					$i++;
				}
				$result .= $this->close_style();
				$i--;
			}
			else
			{
				$result .= $this->open_style('sc_css_normal');
				$in_block = FALSE;
				while ($i < $len)
				{
					if ($str[$i] == '(')
					{
						$in_block = TRUE;
					}
					else if (($in_block && $str[$i] == ')') || (!preg_match('#[a-zA-Z0-9_]#i', $str[$i]) && !$in_block))
					{
						break;
					}
					$result .= $this->escape_special_char($str[$i]);
					$i++;
				}
				$result .= $this->close_style();

				if (isset($str[$i]) && ($str[$i] == '.' || $str[$i] == ':'))
				{
					$i--;
				}
				else if (isset($str[$i]))
				{
					$result .= $str[$i];
				}
			}
		}

		return ($result);
	}
}


/* EOF */