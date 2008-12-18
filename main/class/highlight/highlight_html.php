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
 * Colorateur syntaxique HTML
 */
class Highlight_html extends Highlight
{
	/**
	 * Tags HTML speciaux
	 *
	 * @var unknown_type
	 */
	private static $html_tags = array();
	
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

		// Configuration
		$file_content = file_get_contents(ROOT . 'main/class/highlight/keywords/highlight_html.txt');
		self::$html_tags = $this->get_conf($file_content, 'TEMPLATE');
	}

	/**
	 * @see Highlight::_parse()
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

	/**
	 * Verifie si le tag HTML n'est pas un tag special
	 *
	 * @param array $match Argument cree par preg_replace_callback()
	 * @return string
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