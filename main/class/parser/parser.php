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
 * Parser de messages
 */
class Parser extends Fsb_model
{
	/**
	 * Si les FSBcode doivent etre parses
	 *
	 * @var bool
	 */
	public $parse_fsbcode = true;

	/**
	 * Si les images doivent etre parsees
	 *
	 * @var bool
	 */
	public $parse_img = true;

	/**
	 * Si le HTML doit etre parses
	 *
	 * @var bool
	 */
	public $parse_html = false;

	/**
	 * Si les signatures doivent etre parsees
	 *
	 * @var bool
	 */
	public $is_signature = false;

	/**
	 * Constructeur, verifie si les FSBcode / images doivent etre parses en fonction de la configuration
	 */
	public function __construct()
	{
		$this->parse_fsbcode =	(Fsb::$session->data['u_activate_fscode'] & 2) ? true : false;
		$this->parse_img =		(Fsb::$session->data['u_activate_img'] & 2) ? true : false;
	}

	/**
	 * Parse un message
	 *
	 * @param string $str Contenu du message
	 * @param array $info Variables d'environement pour le message
	 * @return string Message parse
	 */
	public function message($str, $info = array())
	{
		// Prise en compte du HTML ?
		if ($this->parse_html)
		{
			$str = String::unhtmlspecialchars($str);
		}
		// Protection contre le Javascript
		else
		{
			$str = str_replace('javascript:', 'javascript&#58;', $str);
		}

		// On parse la censure ?
		if (Fsb::$cfg->get('activate_censor'))
		{
			$str = $this->censor($str);
		}

		// On parse les FSBcode ?
		if ($this->parse_fsbcode)
		{
			$fsbcode = new Parser_fsbcode();
			$fsbcode->parse_img = $this->parse_img;
			$fsbcode->is_signature = $this->is_signature;
			$fsbcode->parse_eof = false;
			$str = $fsbcode->parse($str, $info);
		}
		else
		{
			// Si on ne les parse pas, on se contente de les supprimer du texte. On remplace tout de meme
			// les puces [*] par une virgule pour la coherence des informations
			$str = preg_replace('#\[/?[a-zA-Z0-9]*?((:|=)([^\]]*?))?\]#i', '', $str);
			$str = str_replace('[*]', ', ', $str);
		}

		// On parse les smilies ?
		if ($this->parse_img)
		{
			$str = $this->smilies($str);
		}

		// Parse des URL
		$str = $this->auto_url($str);

		// Sauts de ligne
		$str = nl2br($str);
		$str = str_replace("\0", "\n", $str);
		$str = str_replace(chr(194) . chr(160), ' ', $str);
		
		return ($str);
	}

	/**
	 * Parse un message qui depend d'une MAP XML
	 *
	 * @param string $str Contenu du message
	 * @param string $map_name Nom de la MAP a utiliser
	 * @param array $info Variables d'environement pour le message
	 * @return string Message parse
	 */
	public function mapped_message($str, $map_name, $info = array())
	{
		$str = Map::parse_message($str, $map_name);

		return ($this->message($str, $info));
	}

	/**
	 * Parse le titre d'un sujet
	 *
	 * @param string $str
	 * @return string
	 */
	public function title($str)
	{
		$str = htmlspecialchars(self::censor($str));
		$str = preg_replace('#&amp;\#x([0-9a-f]{4})#i', '&#x\\1', $str);
		return ($str);
	}

	/**
	 * Parse une signature
	 *
	 * @param string $str
	 * @param array $info Variables d'environement pour le message
	 * @return string
	 */
	public function sig($str, $info = array())
	{
		$old_parse_fsbcode = $this->parse_fsbcode;
		$old_parse_img = $this->parse_img;
		$this->parse_fsbcode = (Fsb::$session->data['u_activate_fscode'] & 4) ? true : false;
		$this->parse_img = (Fsb::$session->data['u_activate_img'] & 4) ? true : false;
		$this->is_signature = true;

		$str = htmlspecialchars($str);
		$str = $this->message($str, $info);

		$this->is_signature = false;
		$this->parse_fsbcode = $old_parse_fsbcode;
		$this->parse_img = $old_parse_img;

		return ($str);
	}

	/**
	 * Remplace les raccourcis de smilies par leur equivalent en image.
	 *
	 * @param string $str Chaine a parser
	 * @param bool $set_path Si on utilise le chemin absolu
	 * @return string
	 */
	public static function smilies($str, $set_path = false)
	{
		static $origin = array(0 => array(), 1 => array()), $replace = array(0 => array(), 1 => array()), $flag = array(0 => false, 1 => false), $smilies = null;

		// Chargement unique des smilies
		if (is_null($smilies))
		{
			$sql = 'SELECT smiley_id, smiley_tag, smiley_name
						FROM ' . SQL_PREFIX . 'smilies
						ORDER BY LENGTH(smiley_tag) DESC';
			$result = Fsb::$db->query($sql, 'smilies_');
			$smilies = Fsb::$db->rows($result);
			$load_flag = true;
		}

		$set_path = intval($set_path);
		if (!$flag[$set_path])
		{
			$smiley_dir = ($set_path && strpos('http://', SMILEY_PATH) === false) ? Fsb::$cfg->get('fsb_path') . '/' . substr(SMILEY_PATH, strlen(ROOT)) : SMILEY_PATH;
			foreach ($smilies AS $smiley)
			{
				if ($smiley['smiley_tag'])
				{
					$origin[$set_path][] = "#(?<= |\t|\n|^)" . preg_quote(htmlspecialchars($smiley['smiley_tag']), '#') . '#s';
					$replace[$set_path][] = '<img src="' . $smiley_dir . $smiley['smiley_name'] . '" title="' . htmlspecialchars($smiley['smiley_tag']) . '" alt="' . htmlspecialchars($smiley['smiley_tag']) . '" />';
				}
			}
			$flag[$set_path] = 1;
		}

		$str = preg_replace($origin[$set_path], $replace[$set_path], ' ' . $str . ' ');
		return (substr($str, 1, -1));
	}

	/**
	 * Remplace les mots censures
	 *
	 * @param string $str Chaine a parser
	 * @return string
	 */
	public static function censor($str)
	{
		static $origin = array(), $replace = array(), $flag = false;

		if (!$flag)
		{
			// On charge la censure
			$sql = 'SELECT censor_word, censor_replace, censor_regexp
						FROM ' . SQL_PREFIX . 'censor';
			$result = Fsb::$db->query($sql, 'censor_');

			$origin = $replace = array();
			while ($censor = Fsb::$db->row($result))
			{
				$censor['censor_word'] = utf8_decode($censor['censor_word']);
				if ($censor['censor_regexp'])
				{
					$origin[] = '#\b' . str_replace('#', '\#', $censor['censor_word']) . '\b#si';
				}
				else
				{
					$origin[] = '#\b' . preg_quote($censor['censor_word'], '#') . '\b#si';
				}
				$replace[] = $censor['censor_replace'];
			}
			Fsb::$db->free($result);

			$flag = true;
		}

		$str = preg_replace($origin, $replace, $str);
		return ($str);	
	}

	/**
	 * Parse les URL et les emails de facon a les rendre clickable
	 *
	 * @param string $str Chaine a parser
	 * @return string
	 */
	public static function auto_url($str)
	{
		$fsbcode = new Parser_fsbcode();
		$str = ' ' . $str . ' ';
		$str = preg_replace_callback('/(?<=^|[\s])((((http:\/\/|https:\/\/|ftp:\/\/|ftps:\/\/|www\.)([^ \"\t\n\r\0<]{3,}))))/i', array($fsbcode, 'generate_url'), $str);
		$str = preg_replace_callback('/(?<=^|[\s])()()([a-z0-9\-_\.]+?@[a-z0-9\-_\.]+?\.[a-z]{2,4}(?![a-z0-9\-_\.]+))/i', array($fsbcode, 'generate_mail'), $str);
		return (substr($str, 1, -1));
	}

	/**
	 * Filtre applique sur chaque champ avant l'envoie d'un message
	 *
	 * @param string $str Chaine a parser
	 * @return string
	 */
	public function prefilter($str)
	{
		foreach (get_class_methods('Parser_prefilter') AS $method)
		{
			if (substr($method, 0, 6) == 'filter')
			{
				$str = call_user_func(array('Parser_prefilter', $method), $str);
			}
		}

		return ($str);
	}
}

/* EOF */