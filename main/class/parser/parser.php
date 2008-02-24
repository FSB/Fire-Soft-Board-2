<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/parser/parser.php
** | Begin :	13/03/2005
** | Last :		10/02/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

class Parser extends Fsb_model
{
	// Parse des FSBcode ?
	public $parse_fsbcode = TRUE;

	// Parse des images ?
	public $parse_img = TRUE;

	// Parse du HTML ?
	public $parse_html = FALSE;

	// Parse d'une signature ?
	public $is_signature = FALSE;

	/*
	** Constructeur
	*/
	public function __construct()
	{
		$this->parse_fsbcode =	(Fsb::$session->data['u_activate_fscode'] & 2) ? TRUE : FALSE;
		$this->parse_img =		(Fsb::$session->data['u_activate_img'] & 2) ? TRUE : FALSE;
	}

	/*
	** Parse du texte
	** -----
	** $str ::			Chaine de caractere a parser.
	** $info ::			Tableau d'informations (variables predefinies)
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
			$fsbcode->parse_eof = FALSE;
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

	/*
	** Parse un message qui depend d'une MAP XML
	** -----
	** $str ::			Chaine du message
	** $map_name ::		Nom de la MAP
	** $info ::			Tableau d'informations (variables predefinies)
	*/
	public function mapped_message($str, $map_name, $info = array())
	{
		$str = Map::parse_message($str, $map_name);

		return ($this->message($str, $info));
	}

	/*
	** Parse un titre de sujet
	** -----
	** $str ::		Chaine a parser
	*/
	public function title($str)
	{
		$str = htmlspecialchars(self::censor($str));
		$str = preg_replace('#&amp;\#x([0-9a-f]{4})#i', '&#x\\1', $str);
		return ($str);
	}

	/*
	** Parse des signatures
	** -----
	** $str ::		Texte de la signature
	*/
	public function sig($str, $info = array())
	{
		$old_parse_fsbcode = $this->parse_fsbcode;
		$old_parse_img = $this->parse_img;
		$this->parse_fsbcode = (Fsb::$session->data['u_activate_fscode'] & 4) ? TRUE : FALSE;
		$this->parse_img = (Fsb::$session->data['u_activate_img'] & 4) ? TRUE : FALSE;
		$this->is_signature = TRUE;

		$str = htmlspecialchars($str);
		$str = $this->message($str, $info);

		$this->is_signature = FALSE;
		$this->parse_fsbcode = $old_parse_fsbcode;
		$this->parse_img = $old_parse_img;

		return ($str);
	}

	/*
	** Remplace les raccourcis dans smileys par leur equivalent en image.
	** -----
	** $str ::		Chaine de caractere a parser.
	** $set_path :: Chemin absolue ?
	*/
	public static function smilies($str, $set_path = FALSE)
	{
		static $origin = array(0 => array(), 1 => array()), $replace = array(0 => array(), 1 => array()), $flag = array(0 => FALSE, 1 => FALSE), $smilies = NULL;

		// Chargement unique des smilies
		if ($smilies === NULL)
		{
			$sql = 'SELECT smiley_id, smiley_tag, smiley_name
						FROM ' . SQL_PREFIX . 'smilies
						ORDER BY LENGTH(smiley_tag) DESC';
			$result = Fsb::$db->query($sql, 'smilies_');
			$smilies = Fsb::$db->rows($result);
			$load_flag = TRUE;
		}

		$set_path = intval($set_path);
		if (!$flag[$set_path])
		{
			$smiley_dir = ($set_path && strpos('http://', SMILEY_PATH) === FALSE) ? Fsb::$cfg->get('fsb_path') . '/' . substr(SMILEY_PATH, strlen(ROOT)) : SMILEY_PATH;
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

	/*
	** Remplace les mots censures par leurs equivalents
	** -----
	** $str ::		Chaine de caractere a parser.
	*/
	public static function censor($str)
	{
		static $origin = array(), $replace = array(), $flag = FALSE;

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

			$flag = TRUE;
		}

		$str = preg_replace($origin, $replace, $str);
		return ($str);	
	}

	/*
	** Parse les URL et les mails automatiquement.
	** -----
	** $str ::		Chaine de caractere a parser.
	*/
	public static function auto_url($str)
	{
		$fsbcode = new Parser_fsbcode();
		$str = ' ' . $str . ' ';
		$str = preg_replace_callback('/(?<=^|[\s])((((http:\/\/|https:\/\/|ftp:\/\/|ftps:\/\/|www\.)([^ \"\t\n\r<]{3,}))))/i', array($fsbcode, 'generate_url'), $str);
		$str = preg_replace_callback('/(?<=^|[\s])()()([a-z0-9\-_\.]+?@[a-z0-9\-_]+?\.[a-z0-9]{2,4})/i', array($fsbcode, 'generate_mail'), $str);
		return (substr($str, 1, -1));
	}

	/*
	** Filtre applique sur chaque champ avant l'envoie d'un message
	** -----
	** $str ::		Contenu du champ
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