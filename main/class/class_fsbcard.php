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
 * Gestion des FSBcard pour importer / exporter son profil
 * Pour les specifications consulter ~/doc/fsbcard.txt ou bien lancer l'application ~/programms/xml_explain.php?id=fsbcard
 */
class Fsbcard extends Fsb_model
{
	/**
	 * @var Xml
	 */
	public $xml;

	/**
	 * Version du systeme de FSBcards
	 *
	 * @var string
	 */
	public $version = '1.0';

	/**
	 * Generateur de la FSBcard
	 *
	 * @var unknown_type
	 */
	public $generator = 'fsb2';

	/**
	 * Liste des sexes disponibles pour la FSBcard
	 *
	 * @var array
	 */
	private $sexe = array('none', 'male', 'female');

	/**
	 * Liste des hash disponibles
	 *
	 * @var array
	 */
	private $hash = array('none', 'md5', 'sha1');

	/**
	 * Liste des methodes d'avatar disponibles
	 *
	 * @var array
	 */
	private $avatar = array('link', 'content');

	/**
	 * Options disponibles
	 *
	 * @var array
	 */
	private $options = array(
		'notifyMp' =>			array('true', 'false'),
		'sessionHidden' =>		array('true', 'false'),
		'displayAvatar' =>		array('true', 'false'),
		'wysiwyg' =>			array('true', 'false'),
		'ajax' =>				array('true', 'false'),
		'displaySig' =>			array('true', 'false'),
		'displayEmail' =>		array('extern', 'intern', 'hide'),
		'notifyPost' =>			array('none', 'none_email', 'auto', 'auto_email'),
		'redirect' =>			array('none', 'direct', 'indirect'),
		'displayFsbcode' =>		array('posts' => array('true', 'false'), 'sigs' => array('true', 'false')),
		'displayImg' =>			array('posts' => array('true', 'false'), 'sigs' => array('true', 'false')),
	);

	/**
	 * Constructeur, instancie un objet XML
	 */
	public function __construct()
	{
		// Instance d'un objet XML et creation de la FSBcard
		$this->xml = new Xml();
		$this->xml->document->setTagName('fsbcard');
		$this->xml->document->setAttribute('version', $this->version);
		$this->xml->document->setAttribute('generator', $this->generator);

		// Ajout de la balise pour les informations personelles
		$item = $this->xml->document->createElement('personal');
		$this->xml->document->appendChild($item);

		// Ajout de la balise pour les informations d'inscription
		$item = $this->xml->document->createElement('register');
		$this->xml->document->appendChild($item);

		// Ajout de la balise pour les options
		$item = $this->xml->document->createElement('options');
		$this->xml->document->appendChild($item);
	}

	/**
	 * Charge une FSBcard
	 *
	 * @param string $filename Chemin vers le fichier FSBcard
	 */
	public function load_file($filename)
	{
		if (!file_exists($filename))
		{
			trigger_error('Le fichier ' . $filename . ' n\'existe pas', FSB_ERROR);
		}

		$this->load_content(file_get_contents($filename));
	}

	/**
	 * Charge du code XML contenu dans une FSBcard
	 *
	 * @param string $content Contenu de la FSBcard
	 */
	public function load_content($content)
	{
		$this->xml->load_content($content);

		// Verification des informations de la FSBcard
		if ($this->xml->document->getTagName() != 'fsbcard')
		{
			trigger_error('FSBcard : format incorect du fichier XML', FSB_ERROR);
		}

		if (!$this->xml->document->childExists('personal'))
		{
			$item = $this->xml->document->createElement('personal');
			$this->xml->document->appendChild($item);
		}

		if (!$this->xml->document->childExists('register'))
		{
			$item = $this->xml->document->createElement('register');
			$this->xml->document->appendChild($item);
		}

		if (!$this->xml->document->childExists('options'))
		{
			$item = $this->xml->document->createElement('options');
			$this->xml->document->appendChild($item);
		}
	}

	/**
	 * Retourne le code XML de la FSBcard
	 *
	 * @return string
	 */
	public function generate()
	{
		return ($this->xml->document->asValidXML());
	}

	/**
	 * Precise le theme
	 *
	 * @param string $string Nom du theme
	 */
	public function set_template($string)
	{
		$item = $this->xml->document->personal[0]->createElement('template');
		$item->setData($string);
		$this->xml->document->personal[0]->appendChild($item);
	}

	/**
	 * Recupere le theme
	 *
	 * @return string
	 */
	public function get_template()
	{
		if ($this->xml->document->personal[0]->childExists('template'))
		{
			return ($this->xml->document->personal[0]->template[0]->getData());
		}
		return (null);
	}

	/**
	 * Precise la langue
	 *
	 * @param string $string Nom de la langue
	 */
	public function set_lang($string)
	{
		$item = $this->xml->document->personal[0]->createElement('lang');
		$item->setData($string);
		$this->xml->document->personal[0]->appendChild($item);
	}

	/**
	 * Recupere la langue
	 *
	 * @return string
	 */
	public function get_lang()
	{
		if ($this->xml->document->personal[0]->childExists('lang'))
		{
			return ($this->xml->document->personal[0]->lang[0]->getData());
		}
		return (null);
	}

	/**
	 * Precise la date de naissance
	 *
	 * @param int $day Jour
	 * @param int $month Mois
	 * @param int $year Annee
	 */
	public function set_birthday($day, $month, $year)
	{
		if (!$day)
		{
			$day = '00';
		}
		else if (!$month)
		{
			$month = '00';
		}
		else if (!$year)
		{
			$year = '0000';
		}

		$birthday = String::add_zero($day, 2) . '-' . String::add_zero($month, 2) . '-' . String::add_zero($year, 4);
		$item = $this->xml->document->personal[0]->createElement('birthday');
		$item->setData($birthday);
		$this->xml->document->personal[0]->appendChild($item);
	}

	/**
	 * Recupere la date de naissance
	 *
	 * @return array day, month, year
	 */
	public function get_birthday()
	{
		if ($this->xml->document->personal[0]->childExists('birthday') && !$this->xml->document->personal[0]->birthday[0]->hasChildren())
		{
			$birthday = $this->xml->document->personal[0]->birthday[0]->getData();
			$split = explode('-', $birthday);
			if (count($split) != 3)
			{
				return (array(null, null, null));
			}
			return (array(String::add_zero($split[0], 2), String::add_zero($split[1], 2), String::add_zero($split[2], 4)));
		}
		return (array(null, null, null));
	}

	/**
	 * Precise le sexe
	 *
	 * @param string $string none, male ou female
	 */
	public function set_sexe($string)
	{
		if (!in_array($string, $this->sexe))
		{
			$string = $this->sexe[0];
		}

		$item = $this->xml->document->personal[0]->createElement('sexe');
		$item->setData($string);
		$this->xml->document->personal[0]->appendChild($item);
	}

	/**
	 * Recupere le sexe
	 *
	 * @return string
	 */
	public function get_sexe()
	{
		if ($this->xml->document->personal[0]->childExists('sexe'))
		{
			$sexe = $this->xml->document->personal[0]->sexe[0]->getData();
			if (!in_array($sexe, $this->sexe))
			{
				$sexe = $this->sexe[0];
			}
			return ($sexe);
		}
		return (null);
	}

	/**
	 * Precise le fuseau horaire
	 *
	 * @param string $utc Fuseau horaire
	 * @param int $dst Heure d'hiver ou d'ete
	 */
	public function set_date($utc, $dst)
	{
		if (!isset($GLOBALS['_utc'][$utc]))
		{
			$utc = 0;
		}

		if ($dst != 0 && $dst != 1)
		{
			$dst = 0;
		}

		$item = $this->xml->document->personal[0]->createElement('date');
		$this->xml->document->personal[0]->appendChild($item);

		$item = $this->xml->document->personal[0]->date[0]->createElement('utc');
		$item->setData($utc);
		$this->xml->document->personal[0]->date[0]->appendChild($item);

		$item = $this->xml->document->personal[0]->date[0]->createElement('dst');
		$item->setData($dst);
		$this->xml->document->personal[0]->date[0]->appendChild($item);
	}

	/**
	 * Recupere le fuseau horaire
	 *
	 * @return array utc, dst
	 */
	public function get_date()
	{
		if ($this->xml->document->personal[0]->childExists('date'))
		{
			$utc = $dst = 0;
			if ($this->xml->document->personal[0]->date[0]->childExists('utc'))
			{
				$utc = $this->xml->document->personal[0]->date[0]->utc[0]->getData();
			}

			if ($this->xml->document->personal[0]->date[0]->childExists('dst'))
			{
				$dst = $this->xml->document->personal[0]->date[0]->dst[0]->getData();
			}

			if (!isset($GLOBALS['_utc'][$utc]))
			{
				$utc = 0;
			}

			if ($dst != 0 && $dst != 1)
			{
				$dst = 0;
			}

			return (array($utc, $dst));
		}
		return (array(null, null));
	}

	/**
	 * Precise la signature
	 *
	 * @param string $string
	 */
	public function set_sig($string)
	{
		$item = $this->xml->document->createElement('sig');
		$item->setData($string);
		$this->xml->document->appendChild($item);
	}

	/**
	 * Recupere la signature
	 *
	 * @return string
	 */
	public function get_sig()
	{
		if ($this->xml->document->childExists('sig'))
		{
			return (String::unhtmlspecialchars($this->xml->document->sig[0]->getData()));
		}
		return (null);
	}

	/**
	 * Precise le login
	 *
	 * @param string $string
	 */
	public function set_login($string)
	{
		$item = $this->xml->document->register[0]->createElement('login');
		$item->setData($string);
		$this->xml->document->register[0]->appendChild($item);
	}

	/**
	 * Recupere le login
	 *
	 * @return string
	 */
	public function get_login()
	{
		if ($this->xml->document->register[0]->childExists('login'))
		{
			return ($this->xml->document->register[0]->login[0]->getData());
		}
		return (null);
	}

	/**
	 * Precise le pseudonyme
	 *
	 * @param string $string
	 */
	public function set_nickname($string)
	{
		$item = $this->xml->document->register[0]->createElement('nickname');
		$item->setData($string);
		$this->xml->document->register[0]->appendChild($item);
	}

	/**
	 * Recupere le pseudonyme
	 *
	 * @return string
	 */
	public function get_nickname()
	{
		if ($this->xml->document->register[0]->childExists('nickname'))
		{
			return ($this->xml->document->register[0]->nickname[0]->getData());
		}
		return (null);
	}

	/**
	 * Mot de passe de connexion
	 *
	 * @param string $string
	 * @param string $hash Hash sur le mot de passe a appliquer
	 */
	public function set_password($string, $hash = 'none')
	{
		if ($hash == 'md5' || $hash == 'sha1')
		{
			$string = $hash($string);
		}
		else
		{
			$hash = 'none';
		}

		$item = $this->xml->document->register[0]->createElement('password');
		$item->setData($string);
		$item->setAttribute('hash', $hash);
		$this->xml->document->register[0]->appendChild($item);
	}

	/**
	 * Recupere le mot de passe
	 *
	 * @return array mot de passe, hash
	 */
	public function get_password()
	{
		if ($this->xml->document->register[0]->childExists('password'))
		{
			$password = $this->xml->document->register[0]->password[0]->getData();
			$hash = $this->xml->document->register[0]->password[0]->getAttribute('hash');

			if (!in_array($hash, $this->hash))
			{
				$hash = $this->hash[0];
			}
			return (array($password, $hash));
		}
		return (array(null, 'none'));
	}

	/**
	 * Precise l'adresse mail
	 *
	 * @param string $string
	 */
	public function set_email($string)
	{
		$item = $this->xml->document->register[0]->createElement('email');
		$item->setData($string);
		$this->xml->document->register[0]->appendChild($item);
	}

	/**
	 * Recupere l'adresse mail
	 *
	 * @return string
	 */
	public function get_email()
	{
		if ($this->xml->document->register[0]->childExists('email'))
		{
			return ($this->xml->document->register[0]->email[0]->getData());
		}
		return (null);
	}

	/**
	 * Precise l'avatar
	 *
	 * @param string $string
	 * @param string $method content ou link
	 */
	public function set_avatar($string, $method)
	{
		if (!in_array($method, $this->avatar))
		{
			$method = $this->avatar[0];
		}

		$link = $string;
		$content = null;
		if ($method == 'content')
		{
			$link = Fsb::$cfg->get('fsb_path') . '/' . $link;
			if (@getimagesize($link))
			{
				$content = base64_encode(file_get_contents($string));
			}
		}

		$item = $this->xml->document->createElement('avatar');
		$this->xml->document->appendChild($item);

		$item = $this->xml->document->avatar[0]->createElement('link');
		$item->setData($link);
		$this->xml->document->avatar[0]->appendChild($item);

		$item = $this->xml->document->avatar[0]->createElement('content');
		$item->setData($content);
		$this->xml->document->avatar[0]->appendChild($item);
	}

	/**
	 * Recupere l'avatar
	 *
	 * @return array avatar, methode
	 */
	public function get_avatar()
	{
		if ($this->xml->document->childExists('avatar'))
		{
			$link = $content = null;
			if ($this->xml->document->avatar[0]->childExists('link'))
			{
				$link = $this->xml->document->avatar[0]->link[0]->getData();
			}

			if ($this->xml->document->avatar[0]->childExists('content'))
			{
				$content = $this->xml->document->avatar[0]->content[0]->getData();
			}

			if ($content)
			{
				$content = base64_decode($content);
			}
			return (array($link, $content));
		}
		return (array(null, null));
	}

	/**
	 * Gestion des options utilisateur
	 *
	 * @param string $key Nom de l'option
	 * @param mixed $value Valeur de l'option
	 */
	public function set_option($key, $value)
	{
		if (!isset($this->options[$key]))
		{
			return (null);
		}

		$item = $this->xml->document->options[0]->createElement($key);

		// Valeurs et attributs pour cette option
		$attributes = $values = array();
		foreach ($this->options[$key] AS $k => $v)
		{
			if (is_array($v))
			{
				$attributes[$k] = $v;
			}
			else
			{
				$values[] = $v;
			}
		}

		// $value est une valeur ou un attribut ?
		if (is_array($value))
		{
			foreach ($value AS $k => $v)
			{
				if (!isset($attributes[$k]))
				{
					continue ;
				}

				if (is_bool($v))
				{
					$v = ($v === true) ? 'true' : 'false';
				}

				if (!in_array($v, $attributes[$k]))
				{
					$value[$k] = $attributes[$k][0];
				}
				$item->setAttribute($k, $v);
			}
		}
		else
		{
			if (is_bool($value))
			{
				$value = ($value === true) ? 'true' : 'false';
			}

			if (!in_array($value, $values))
			{
				$value = $values[0];
			}
			$item->setData($value);
		}

		$this->xml->document->options[0]->appendChild($item);
	}

	/**
	 * Recupere une option
	 *
	 * @param string $key Nom de l'option
	 * @return mixed
	 */
	public function get_option($key)
	{
		if (!isset($this->options[$key]) || !$this->xml->document->options[0]->childExists($key))
		{
			return (null);
		}

		// Valeurs et attributs pour cette option
		list($attributes, $values) = $this->options_info($key);

		if ($attributes)
		{
			$return = array();
			foreach ($attributes AS $k => $v)
			{
				$attr = $this->xml->document->options[0]->{$key}[0]->getAttribute($k);
				if (!in_array($attr, $v))
				{
					$attr = null;
				}

				if ($attr == 'true')
				{
					$attr = true;
				}
				else if ($attr == 'false')
				{
					$attr = false;
				}
				$return[$k] = $attr;
			}
			return ($return);
		}
		else
		{
			$return = $this->xml->document->options[0]->{$key}[0]->getData();
			if (!in_array($return, $values))
			{
				return (null);
			}

			if ($return == 'true')
			{
				$return = true;
			}
			else if ($return == 'false')
			{
				$return = false;
			}
			return ($return);
		}
	}

	/**
	 * Retourne les informations sur une option
	 *
	 * @param string $key Nom de l'option
	 * @return array attributs, valeurs
	 */
	private function options_info($key)
	{
		$attributes = $values = array();
		foreach ($this->options[$key] AS $k => $v)
		{
			if (is_array($v))
			{
				$attributes[$k] = $v;
			}
			else
			{
				$values[] = $v;
			}
		}
		return (array($attributes, $values));
	}
}
/* EOF */