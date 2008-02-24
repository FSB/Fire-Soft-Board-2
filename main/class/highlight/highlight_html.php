<?php
/*
** +---------------------------------------------------+
** | Name :			~/main/class/highlight/highlight_html.php
** | Begin :		17/07/2007
** | Last :			17/07/2007
** | User :			Genova
** | Project :		Fire-Soft-Board 2 - Copyright FSB group
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Colorateur syntaxique HTML
*/
class Highlight_html extends Highlight
{
	private static $html_tags = array();
	private static $init = array();

	public function __construct()
	{
		if (self::$init)
		{
			return ;
		}
		self::$init = TRUE;

		// Configuration
		$file_content = file_get_contents(ROOT . 'main/class/highlight/keywords/highlight_html.txt');
		self::$html_tags = $this->get_conf($file_content, 'TEMPLATE');
	}

	/*
	** Parse une chaine de caractere HTML
	*/
	protected function _parse($str)
	{
		$str = str_replace('"', '&quot;', $str);
		$str = preg_replace_callback('/&lt;(\/?)([a-zA-Z]+?)( |&gt;)/si', array($this, '_parse_html_tags'), $str);
		$str = preg_replace('/(\/?&gt;)/si', '<span class="sc_html_tag">\\1</span>', $str);
		$str = preg_replace('/ ([a-zA-Z]+?=)(&quot;|&39;)(.*?)(&quot;|&39;)/si', ' <span class="sc_html_atrib">\\1</span><span class="sc_html_quote">\\2\\3\\4</span>', $str);
		$str = preg_replace_callback('/&lt;(!--.+?--)/si', array($this, 'up_coloration'), $str);
		$str = preg_replace('/(^|[^\$]){(([0-9a-z_]+\.)*)([0-9A-Z_]+)}/si', '\\1{<span class="sc_html_block">\\2</span><span class="sc_html_var">\\4</span>}', $str);
		$str = preg_replace('/\${([0-9a-zA-Z_\[\]\'&;#]+)}/si', '${<span class="sc_html_global">\\1</span>}', $str);
		$str = str_replace("<br />", '', $str);
		return ($str);
	}

	/*
	** Verifie si le tag HTML n'est pas un tag special
	*/
	private function _parse_html_tags($match)
	{
		if (in_array(strtolower($match[2]), self::$html_tags))
		{
			$match[2] = $this->open_style('sc_html_tpl_tag') . $match[2] . $this->close_style();
		}
		return ('<span class="sc_html_tag">&lt;' . $match[1] . $match[2] . '</span>' . $match[3]);
	}

}

/* EOF */