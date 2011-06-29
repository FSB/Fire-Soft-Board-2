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
 * Parseur de FSBcode
 */
class Parser_fsbcode extends Fsb_model
{
	/**
	 * Garde en cache les donnees sur les FSBcode
	 *
	 * @var array
	 */
	private static $cache_fsbcode = array();

	/**
	 * Determine si on affiche ou non uniquement les FSBcode visibles dans le WYSIWYG
	 *
	 * @var bool
	 */
	public $only_wysiwyg = false;

	/**
	 * Si on doit parser les images
	 *
	 * @var bool
	 */
	public $parse_img = true;

	/**
	 * Si on doit parser les caracteres \0 en \n
	 * 
	 * @var bool
	 */
	public $parse_eof = true;

	/**
	 * S'il s'agit d'une signature
	 *
	 * @var bool
	 */
	public $is_signature = false;
	
	/**
	 * Tableau d'informations (variables predefinies)
	 *
	 * @var array
	 */
	public $info;

	/**
	 * Variables predefinies qu'on peut potentiellement parser
	 * 
	 * @var array
	 */
	private $static_vars = array(
		'{USER_NICKNAME}' =>	'p_nickname',
		'{USER_ID}' =>			'u_id',
		'{TOPIC_ID}' =>			't_id',
		'{FORUM_ID}' =>			'f_id',
	);

	/**
	 * Parse les FSBcode a partir des informations de la table fsb2_fsbcode
	 *
	 * @param string $str Chaine a parser
	 * @param array $info Variables d'environement du message
	 * @return string
	 */
	public function parse($str, $info = array())
	{
		$this->info = $info;

		// On recupere les informations sur les FSBcode
		if (!isset(self::$cache_fsbcode[$this->is_signature]))
		{
			$sql = 'SELECT *
					FROM ' . SQL_PREFIX . 'fsbcode
					WHERE fsbcode_activated' . (($this->is_signature) ? '_sig' : '') . ' = 1
					ORDER BY fsbcode_priority DESC';
			$result = Fsb::$db->query($sql, 'fsbcode_');
			$list = Fsb::$db->rows($result);
			self::$cache_fsbcode[$this->is_signature] = $list;
		}
		else
		{
			$list = self::$cache_fsbcode[$this->is_signature];
		}

		// On parcourt la liste des FSBcode pour parser le message
		foreach ($list AS $data)
		{
			// Si on encode du WYSIWYG, on ne prend pas en compte toutes les balises
			if ($this->only_wysiwyg && !$data['fsbcode_wysiwyg'])
			{
				continue ;
			}

			// Certaines balises ne sont pas parsees dans les signatures
			if ($this->is_signature && !$data['fsbcode_activated_sig'])
			{
				continue ;
			}

			// FSBcode gere par un appel de fonction
			if ($data['fsbcode_fct'])
			{
				$callback = '';
				if (method_exists($this, $data['fsbcode_fct']))
				{
					$callback = array(&$this, $data['fsbcode_fct']);
				}
				else if (function_exists($data['fsbcode_fct']))
				{
					$callback = $data['fsbcode_fct'];
				}

				if ($callback)
				{
					$pattern = '#\[' . $data['fsbcode_tag'] . '([=:]([^\]]*?))?\](.*?)\[/' . $data['fsbcode_tag'] . '\]#i' . ((!$data['fsbcode_inline']) ? 's' : '');
					while (preg_match($pattern, $str))
					{
						$str = preg_replace_callback($pattern, $callback, $str);
					}
				}
			}
			// FSBcode gere par un remplacement du code HTML
			else
			{
				$str = $this->parse_fsbcode_patterns($str, $data['fsbcode_search'], $data['fsbcode_replace'], $info);
			}
		}

		// Remplacement de [BR] par un saut de ligne
		$str = str_ireplace('[br]', '<br />', $str);

		if ($this->parse_eof)
		{
			$str = str_replace("\0", "\n", $str);
		}

		return ($str);
	}

	/**
	 * Parse les FSBcode a partir d'une information de recherche et de remplacement.
	 *
	 * @param string $str Chaine a parser
	 * @param string $search Recherche de FSBcode
	 * @param string $replace Remplacement du FSBcode
	 * @param array $info Variables d'environement
	 * @return string
	 */
	private function parse_fsbcode_patterns($str, $search, $replace, $info = array())
	{
		// Creation d'un pattern a partir de la chaine de recherche
		$vars = array();
		$search = preg_quote($search, '#');
		preg_match_all('#\\\{(([a-zA-Z_]*?)([0-9]*))\\\}#i', $search, $m);
		$count = count($m[0]);
		for ($i = 0; $i < $count; $i++)
		{
			// On garde en memoire la position de la variable, pour le remplacement
			$vars[] = $m[1][$i];

			// Remplacement de la variable par un pattern
			$pattern = Regexp::pattern($m[2][$i]);
			$search = str_replace('\{' . $m[1][$i] . '\}', $pattern, $search);
		}

		// Chaine de remplacement
		foreach ($vars AS $position => $varname)
		{
			$replace = str_replace('{' . $varname . '}', '$' . ($position + 1), $replace);
		}

		while (preg_match('#' . $search . '#si', $str))
		{
			$str = preg_replace('#' . $search . '#si', str_replace(array("\r\n", "\n"), array(" ", " "), $replace), $str);
		}

		// Parse des variables predefinies
		foreach ($this->static_vars AS $varname => $key)
		{
			$str = str_replace($varname, (isset($info[$key])) ? $info[$key] : '', $str);
		}
		return ($str);
	}

	/**
	 * Parse du FSBcode QUOTE
	 *
	 * @param array $m Informations du FSBcode
	 * @return string
	 */
	private function generate_quote($m)
	{
		$arg = str_replace('&amp;#93;', '&#93;', $m[2]);
		$content = $m[3];

		// Pour l'editeur WYSIWYG, on parse l'affichage differement
		if ($this->only_wysiwyg)
		{
			return ('<blockquote args="' . htmlspecialchars($arg) . '" style="border: 1px dashed #000000; margin: 3px; padding: 3px">' . $content . '</blockquote>');
		}

		// Sans arguments
		if (!$arg)
		{
			return (sprintf(Fsb::$session->getStyle('fsbcode', 'quote'), Fsb::$session->lang('quote'), $content));
		}
		// Avec arguments
		else
		{
			if (strpos($arg, ',t=') !== false || strpos($arg, ',id=') !== false)
			{
				$split = explode(',', $arg);
				$timestamp = '';
				$link = '%s';
				foreach ($split AS $data)
				{
					if (strpos($data, '=') !== false)
					{
						list($k, $v) = explode('=', $data);
						switch ($k)
						{
							case 't' :
								$timestamp = Fsb::$session->print_date($v);
								$arg = str_replace(',t=' . $v, '', $arg);
							break;

							case 'id' :
								$link = '<a href="' . sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;p_id=' . $v) . '#p' . $v . '">%s</a>';
								$arg = str_replace(',id=' . $v, '', $arg);
							break;
						}
					}
				}

				if ($timestamp)
				{
					$arg .= ', ' . $timestamp;
				}
				$arg = sprintf($link, $arg);
			}
			return (sprintf(Fsb::$session->getStyle('fsbcode', 'quote_user'), Fsb::$session->lang('quote'), $arg, $content));
		}
	}

	/**
	 * Parse du FSBcode CODE
	 *
	 * @param array $m Informations du FSBcode
	 * @return string
	 */
	public function generate_code($m)
	{
		$arg = $m[2];
		$content = $m[3];
		$content = str_replace(array("\r\n", "\r", "\n"), array("\0", "\0", "\0"), $content);

		// Pour l'editeur WYSIWYG, on parse l'affichage differement
		if ($this->only_wysiwyg)
		{
			return ('<code args="' . htmlspecialchars($arg) . '" style="display: block; border: 1px dashed #000000; margin: 3px; padding: 3px">' . $this->block_fsbcode($content) . '</code>');
		}

		// Sans arguments
		if (!$arg || !Fsb::$mods->is_active('highlight_code'))
		{
			return (sprintf(Fsb::$session->getStyle('fsbcode', 'code'), 'Code', $this->block_fsbcode($content), Fsb::$session->lang('select_code')));
		}
		// Avec arguments
		else
		{
			switch ($arg)
			{
				case 'css' :
				case 'php' :
				case 'sql' :
					$highlight = Highlight::factory($arg);
					return (sprintf(Fsb::$session->getStyle('fsbcode', 'code'), strtoupper($arg), $this->block_fsbcode($highlight->parse_code(String::unhtmlspecialchars($content))), Fsb::$session->lang('select_code')));
				break;

				case 'html' :
					$highlight = Highlight::factory($arg);
					return (sprintf(Fsb::$session->getStyle('fsbcode', 'code'), strtoupper($arg), $this->block_fsbcode($highlight->parse_code($content)), Fsb::$session->lang('select_code')));
				break;

				default :
					return (sprintf(Fsb::$session->getStyle('fsbcode', 'code'), 'Code', $this->block_fsbcode($content), Fsb::$session->lang('select_code')));
				break;
			}
		}
	}

	/**
	 * Parse du FSBcode URL
	 *
	 * @param array $m Informations du FSBcode
	 * @return string
	 */
	public function generate_url($m)
	{
		$arg = $m[2];
		$content = $m[3];

		if (!$arg || isset($m[5]))
		{
			// On tronque l'URL si elle est trop imposante
			$arg =		$content;
			$content = (strlen($content) > 60) ? substr($content, 0, 30) . '...' . substr($content, -15) : $content;
		}

		$arg = trim($arg);

		// URL interne ou externe ?
		if (preg_match('#^\w+?://.*?#', $arg))
		{
			return (sprintf(Fsb::$session->getStyle('fsbcode', 'url'), trim($arg), $arg, $content));
		}
		else if (preg_match('#^(www|ftp).*?#', $arg))
		{
			return (sprintf(Fsb::$session->getStyle('fsbcode', 'url'), 'http://' . $arg, $arg, $content));
		}
		else
		{
			return (sprintf(Fsb::$session->getStyle('fsbcode', 'url'), Fsb::$cfg->get('fsb_path') . '/' . $arg, $arg, $content));
		}
	}

	/**
	 * Parse du FSBcode MAIL
	 *
	 * @param array $m Informations du FSBcode
	 * @return string
	 */
	public function generate_mail($m)
	{
		$arg = $m[2];
		$content = $m[3];

		$is_content_mail = false;
		if (!$arg)
		{
			$arg = $content;
			$is_content_mail =  true ;
		}

		if (!preg_match('#^[a-z0-9\-_\.]+?@[a-z0-9\-_\.]+?\.[a-z]{2,4}(?![a-z0-9\-_\.]+)$#i', $arg))
		{
			return ($arg);
		}
		
		if ($is_content_mail)
		{
			$content = String::no_spam($content);
		}

		$arg = String::no_spam($arg);
		return (sprintf(Fsb::$session->getStyle('fsbcode', 'url'), 'mailto:' . trim($arg), '', $content));
	}

	/**
	 * Parse du FSBcode ATTACH
	 *
	 * @param array $m Informations du FSBcode
	 * @return string
	 */
	public function generate_attach($m)
	{
		$arg = $m[2];
		$content = $m[3];

		// Pour l'editeur WYSIWYG, on parse l'affichage differement
		if ($this->only_wysiwyg)
		{
			return ('<div type="attach" args="' . intval($arg) . '" style="border: 1px dashed #000000; margin: 3px; padding: 3px">' . $content . '</div>');
		}

		// Informations sur le fichier
		$sql = 'SELECT upload_total, upload_auth, upload_filesize, upload_realname, upload_time
				FROM ' . SQL_PREFIX . 'upload
				WHERE upload_id = ' . intval($arg);
		$row = Fsb::$db->request($sql);

		// Droit de telechargement ?
		if (!$row)
		{
			return (sprintf(Fsb::$session->getStyle('fsbcode', 'cant_attach'), Fsb::$session->lang('attached_file'), Fsb::$session->lang('attached_file_not_exists'), $content));
		}
		else if (Fsb::$session->auth() >= $row['upload_auth'])
		{
			$url = sid(ROOT . 'index.' . PHPEXT . '?p=download&amp;id=' . $arg);
			$explain = sprintf(Fsb::$session->lang('download_total'), $row['upload_total'], $row['upload_realname'], convert_size($row['upload_filesize']), Fsb::$session->print_date($row['upload_time']));
			return (sprintf(Fsb::$session->getStyle('fsbcode', 'attach'), Fsb::$session->lang('attached_file'), $url, Fsb::$session->lang('download'), $explain, $content));
		}
		else
		{
			return (sprintf(Fsb::$session->getStyle('fsbcode', 'cant_attach'), Fsb::$session->lang('attached_file'), Fsb::$session->lang('not_allowed_to_download'), $content));
		}
	}

	/**
	 * Parse du FSBcode LIST
	 *
	 * @param array $m Informations du FSBcode
	 * @return string
	 */
	public function generate_list($m)
	{
		$arg = $m[2];
		$content = $m[3];

		$content = preg_replace('/\[\*\]/', '</li><li>', $content);
		$content = preg_replace('/^\s*<\/?li>/', '', $content);
		$content = str_replace( "\n</li>", '</li>', $content . '</li>');
        
		if ($arg)
		{
			return ('<ul style="list-style-type: '.$arg.'">' . $content . '</ul>');
		}
		else
		{
			return ('<ul style="list-style-type: disc">' . $content . '</ul>');
		}
	}

	/**
	 * Parse du FSBcode IMG
	 *
	 * @param array $m Informations du FSBcode
	 * @return string
	 */
	public function generate_img($m)
	{
		$arg = $m[2];
		$content = trim($m[3]);

		if (!$this->parse_img)
		{
			return ('[url]' . $content . '[/url]');
		}

		$attr_ary = array('alt', 'height', 'title', 'width', 'float');

		$alt_exists = false;
		$attr_str = '';

		// On recupere les atributs
		$exp = explode(',', $arg);
		foreach ($exp AS $attr)
		{
			if ($attr && strpos($attr, '='))
			{
				list($name, $value) = explode('=', $attr);
				if ($name == 'float' && ($value == 'left' || $value == 'right'))
				{
					$attr_str .= 'style="float: ' . $value . '; padding: 3px"';
				}
				else if (in_array($name, $attr_ary) && $value)
				{
					if ($name == 'alt')
					{
						$alt_exists = true;
					}
					$attr_str .= $name . '="' . $value . '" ';
				}
			}
		}

		if (!$alt_exists)
		{
			$attr_str .= 'alt="' . $content . '" ';
		}

		return ('<img class="image" src="' . trim($content) . '" ' . $attr_str . ' />');
	}

	/**
	 * Inhibe les FSBcode en remplacant leurs balises par un equivalent HTML.
	 * Remplace les balises des smileys courament utilisees par un equivalent.
	 *
	 * @param string $str Chaine a parser
	 * @return string
	 */
	private function block_fsbcode($str)
	{
		$str = str_replace(array(':', '[', ']', ')', '('), array('&#58;', '&#91;', '&#93;', '&#41;', '&#40;'), $str);
		return ($str);
	}
}
/* EOF */
