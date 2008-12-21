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
 * Parseur XML
 */
class Xml extends Fsb_model
{
	/**
	 * Contenu du code XML a parser
	 * 
	 * @var string
	 */
	private $content;

	/**
	 * Pointe sur la racine
	 *
	 * @var Xml_element
	 */
	public $document;

	/**
	 * Pile utilisee par le parseur
	 *
	 * @var array
	 */
	private $stack = array();

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		$this->document = new Xml_element();
	}

	/**
	 * Charge le contenu d'un fichier XML
	 *
	 * @param string $filename Fichier XML a charger
	 * @param bool $use_cache Utilisation du cache
	 */
	public function load_file($filename, $use_cache = true)
	{
		if (!file_exists($filename))
		{
			trigger_error('Le fichier XML ' . $filename . ' n\'existe pas', FSB_ERROR);
		}

		if ($use_cache)
		{
			$hash = md5($filename);
			$cache = Cache::factory('xml');
			if ($cache->exists($hash) && $cache->get_time($hash) == filemtime($filename))
			{
				$this->document = $cache->get($hash);
				return ;
			}
		}

		$this->content = file_get_contents($filename);
		$this->parse();

		if ($use_cache)
		{
			$cache->put($hash, $this->document, $filename, filemtime($filename));
		}
	}

	/**
	 * Charge du code XML
	 *
	 * @param string $content Contenu XML
	 */
	public function load_content($content)
	{
		$this->document = new Xml_element();
		$this->content = $content;
		$this->parse();
	}

	/**
	 * Parse du code XML
	 */
	public function parse()
	{
		$xml = new Xml_regexp_parser();
		$xml->obj =& $this;
		$xml->open_handler = 'open_tag';
		$xml->value_handler = 'value_tag';
		$xml->close_handler = 'close_tag';
		$result = $xml->parse($this->content);
		if (!$result)
		{
			trigger_error($xml->errstr, FSB_ERROR);
		}
	}

	/**
	 * Callback appele lors de l'ouverture d'un tag
	 *
	 * @param string $tag Nom du tag
	 * @param array $attr Liste des attributs
	 */
	public function open_tag($tag, $attr)
	{
		// Deplacement vers la reference de l'element
		$ref = &$this->document;
		foreach ($this->stack AS $i => $item)
		{
			if ($i > 0)
			{
				$ref = &$ref->$item;
				$ref = &$ref[count($ref) - 1];
			}
		}

		// Creation du nouvel element
		if (count($this->stack))
		{
			$new = $ref->createElement($tag);
			foreach ($attr AS $k => $v)
			{
				$new->setAttribute($k, $v);
			}
			$new->__data['depth'] = count($this->stack);
			$ref->appendChild($new);
		}
		else
		{
			$ref->setTagName($tag);
			foreach ($attr AS $k => $v)
			{
				$ref->setAttribute($k, $v);
			}
		}

		// Ajout du tag a la pile
		array_push($this->stack, $tag);
	}

	/**
	 * Callback appele lors de la fermeture d'un tag
	 *
	 * @param string $tag Nom du tag
	 */
	public function close_tag($tag)
	{
		array_pop($this->stack);
	}

	/**
	 * Callback appele lors de la capture de texte entre les tags
	 *
	 * @param string $text Texte capture
	 */
	public function value_tag($text)
	{
		$ref = &$this->document;
		foreach ($this->stack AS $i => $item)
		{
			if ($i > 0)
			{
				$ref = &$ref->$item;
				$ref = &$ref[count($ref) - 1];
			}
		}

		$ref->setData($text);
	}
}

/**
 * Represente une node de l'arbre XML
 */
class Xml_element extends Fsb_model
{
	/**
	 * Informations sur la node
	 *
	 * @var array
	 */
	public $__data = array();

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		$this->__data['name'] = 'newElement';
		$this->__data['value'] = null;
		$this->__data['attr'] = array();
		$this->__data['depth'] = 0;
	}

	/**
	 * Cree un nouvel element XML
	 *
	 * @param string $name Nom du tag du nouvel element
	 * @return Xml_element
	 */
	public function createElement($name = 'newElement')
	{
		$new = new Xml_element();
		$new->setTagName($name);
		$new->__data['depth'] = $this->__data['depth'] + 1;

		return ($new);
	}

	/**
	 * Retourne la liste des attributs
	 *
	 * @return array
	 */
	public function attribute()
	{
		return ($this->__data['attr']);
	}

	/**
	 * Cree ou met a jour un attribut
	 *
	 * @param string $name Nom de l'attribut
	 * @param string $value Valeur de l'attribut
	 */
	public function setAttribute($name, $value)
	{
		$this->__data['attr'][$name] = $value;
	}

	/**
	 * Verifie si un attribut existe
	 *
	 * @param string $name Nom de l'attribute
	 * @return bool
	 */
	public function attributeExists($name)
	{
		return (isset($this->__data['attr'][$name]));
	}

	/**
	 * Retourne la valeur d'un attribut
	 *
	 * @param string $name Nom de l'attribut
	 * @return string
	 */
	public function getAttribute($name)
	{
		return (($this->attributeExists($name)) ? $this->__data['attr'][$name] : null);
	}

	/**
	 * Supprime un attribut
	 *
	 * @param string $name Nom de l'attribut
	 */
	public function deleteAttribute($name)
	{
		unset($this->__data['attr'][$name]);
	}

	/**
	 * Modifie le nom du tag
	 *
	 * @param string $name Nom du tag
	 */
	public function setTagName($name)
	{
		$this->__data['name'] = $name;
	}

	/**
	 * Retourne le nom du tag
	 *
	 * @return string
	 */
	public function getTagName()
	{
		return ($this->__data['name']);
	}

	/**
	 * Modifie le texte contenu dans le tag
	 *
	 * @param string $value
	 * @param bool $htmlspecialchars Transforme les entites HTML
	 */
	public function setData($value, $htmlspecialchars = true)
	{
		if ($htmlspecialchars)
		{
			$value = htmlspecialchars($value);
		}
		$this->__data['value'] = $value;
	}

	/**
	 * Retourne la valeur du tag
	 *
	 * @return string
	 */
	public function getData()
	{
		return (String::unhtmlspecialchars($this->__data['value']));
	}

	/**
	 * Ajoute un enfant
	 *
	 * @param Xml_element $node
	 * @param int $pos Position ou ajouter la node
	 */
	public function appendChild($node, $pos = 0)
	{
		$name = $node->getTagName();
		if (!isset($this->$name))
		{
			$this->$name = array();
		}
		$ref = &$this->$name;

		if ($pos == 0)
		{
			$ref[] = $node;
		}
		else
		{
			$ref = array_merge(array_slice($ref, 0, $pos), array($node), array_slice($ref, $pos));
		}
	}

	/**
	 * Ajoute un enfant a partir de XML
	 *
	 * @param string $string Chaine XML
	 * @param int $pos Position ou ajouter la node
	 */
	public function AppendXmlChild($string, $pos = 0)
	{
		$xml = new Xml();
		$xml->load_content($string);
		$this->appendChild($xml->document, $pos);
	}

	/**
	 * Retourne la liste des enfants organisee de cette facon :
	 *		array(
	 *			'enfant1' => array(Xml_element, Xml_element, ...),
	 *			'enfant2' => array(Xml_element, Xml_element, ...)
	 *		)
	 *
	 * @return array
	 */
	public function children()
	{
		$children = array();
		foreach ($this AS $property_name => $property_value)
		{
			if ($property_name != '__data')
			{
				$children[] = &$this->$property_name;
			}
		}

		return ($children);
	}

	/**
	 * Retourne la liste des enfants organisee de cette facon :
	 *		array(Xml_element, Xml_element, Xml_element, Xml_element, ...)
	 *
	 * @return array
	 */
	public function listChildren()
	{
		$children = array();
		foreach ($this AS $property_name => $property_value)
		{
			if ($property_name != '__data')
			{
				foreach ($this->$property_name AS $child)
				{
					$children[] = &$child;
				}
			}
		}

		return ($children);
	}

	/**
	 * Verifie si l'enfant existe
	 *
	 * @param string $name Nom de l'enfant
	 * @return bool
	 */
	public function childExists($name)
	{
		return (isset($this->$name));
	}

	/**
	 * Verifie si l'element a des enfants
	 *
	 * @return bool
	 */
	public function hasChildren()
	{
		return ((count($this->children())) ? true : false);
	}

	/**
	 * Supprime les enfants
	 *
	 * @param string $name Nom de l'enfant a supprimer
	 */
	public function deleteChildren($name)
	{
		if ($this->childExists($name))
		{
			unset($this->$name);
		}
	}

	/**
	 * Supprime un enfant particulier
	 *
	 * @param string $name Nom de l'enfant
	 * @param int $pos Position de l'enfant
	 */
	public function deleteChild($name, $pos = 0)
	{
		if (!$this->childExists($name))
		{
			return ;
		}

		$new = array();
		foreach ($this->$name AS $index => $child)
		{
			if ($index != $pos)
			{
				$new[] = $child;
			}
		}
		$this->$name = $new;
	}

	/**
	 * Deplace un enfant en fonction de ses pairs
	 *
	 * @param string $name Nom de l'enfant
	 * @param int $pos Position de l'enfant
	 * @param int $move Determine de combien de "case" on deplace l'enfant dans l'arbre
	 */
	public function moveChild($name, $pos, $move = 0)
	{
		if ($move == 0 || !$this->childExists($name))
		{
			return ;
		}

		$ref = &$this->$name;
		if (isset($ref[$pos + $move]))
		{
			$tmp = $ref[$pos];
			$ref[$pos] = $ref[$pos + $move];
			$ref[$pos + $move] = $tmp;
		}
	}

	/**
	 * Retourne le dernier enfant
	 *
	 * @param string $name Nom de l'enfant
	 * @return Xml_element 
	 */
	public function &lastChild($name)
	{
		if (!$this->childExists($name))
		{
			return (null);
		}
		$ref = &$this->$name;
		$ref = &$ref[count($ref) - 1];
		return ($ref);
	}

	/**
	 * Recherche une node a partir de son chemin
	 *
	 * @param string $path Chemin vers la node
	 * @return Xml_element
	 */
	public function &getElementByPath($path)
	{
		$split = explode('/', trim($path, '/'));
		$ref = &$this;
		foreach ($split AS $item)
		{
			if (!$ref->childExists($item))
			{
				$null = null;
				$ref = &$null;
				return ($ref);
			}
			$ref = &$ref->$item;
			$ref =& $ref[0];
		}

		return ($ref);
	}

	/**
	 * Retourne l'arbre sous forme de chaine de caractere XML
	 *
	 * @return string
	 */
	public function asXML()
	{
		$xml = str_repeat("\t", $this->__data['depth']) . '<' . $this->getTagName();
		foreach ($this->attribute() AS $key => $value)
		{
			$xml .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
		}
		$xml .= '>';

		if ($this->hasChildren())
		{
			foreach ($this->children() AS $childs)
			{
				foreach ($childs AS $child)
				{
					$xml .= "\n" . $child->asXML();
				}
			}
			$xml .= "\n" . str_repeat("\t", $this->__data['depth']) . '</' . $this->getTagName() . '>';
		}
		else
		{
			$xml .= '<![CDATA[' . $this->getData() . ']]></' . $this->getTagName() . '>';
		}

		return ($xml);
	}

	/**
	 * Retourne l'arbre sous forme de chaine de caractere XML avec l'entete
	 *
	 * @param string $charset
	 * @return string
	 */
	public function asValidXML($charset = 'UTF-8')
	{
		$xml = '<?xml version="1.0" encoding="' . $charset . '" standalone="no"?>' . "\n";
		$xml .= $this->asXml();
		return ($xml);
	}

	/**
	 * Si un enfant n'existe pas, on retourne un array() vide
	 *
	 * @param string $property
	 * @return array
	 */
	public function __get($property)
	{
		return (array());
	}
}

/**
 * Analyseur XML fait maison, afin d'eviter certains problemes lies a l'encodage des caracteres
 */
class Xml_regexp_parser extends Fsb_model
{
	/**
	 * En faisant pointer cette propriete sur un objet, les handler seront appeles en tant que methode de cet objet
	 */
	public $obj = null;

	/**
	 * Fonction / methode appelee lors de l'ouverture d'un tag
	 *
	 * @var string
	 */
	public $open_handler = null;

	/**
	 * Fonction / methode appelee lors de la fermeture d'un tag
	 *
	 * @var string
	 */
	public $close_handler = null;

	/**
	 * Fonction / methode appelee lors de la fermeture d'un tag, avec la valeur de celui ci
	 *
	 * @var string
	 */
	public $value_handler = null;

	/**
	 * Erreur lors du parsing
	 *
	 * @var string
	 */
	public $errstr = null;

	/**
	 * Parse la chaine de caractere XML
	 *
	 * @param string $str Chaine a parser
	 * @return bool
	 */
	public function parse($str)
	{
		// Parse du header XML
		if (preg_match('#^\s*<\?xml.*?\?>#si', $str, $m))
		{
			$str = preg_replace('#^\s*<\?xml.*?\?>#si', '', $str);
		}

		$stack = array();
		$in_cdata = false;
		$last_offset = 0;
		$value = '';

		// Parse des differentes balises
		preg_match_all('#<(/)?\s*([a-zA-Z0-9_\-]*?)(\s+(.*?))?\s*(/?)>\s*(<\!\[CDATA\[)?#si', $str, $m, PREG_OFFSET_CAPTURE);
		$count = count($m[0]);
		for ($i = 0; $i < $count; $i++)
		{
			// Longueur de la balise, offset de debut et offset de fin de la chaine
			$length = strlen($m[0][$i][0]);
			$current_offset = $m[0][$i][1] + $length;

			// Tag de la balise
			$tag = $m[2][$i][0];

			// Ouverture de balise ?
			if (!$m[1][$i][0] || $m[5][$i][0])
			{
				// On ne prend pas en compte les tags dans un CDATA
				if ($in_cdata)
				{
					continue ;
				}

				array_push($stack, $tag);

				// Appel du handler d'ouverture du tag
				if ($this->open_handler)
				{
					// Parse des attributs
					preg_match_all('#([a-zA-Z0-9_\-]*?)="(.*?)"#si', $m[3][$i][0], $a, PREG_SET_ORDER);
					$attrs = array();
					foreach ($a AS $attr)
					{
						$attrs[$attr[1]] = $attr[2];
					}

					if ($this->obj)
					{
						$this->obj->{$this->open_handler}($tag, $attrs);
					}
					else
					{
						call_user_func($this->open_handler, $tag, $attrs);
					}
				}

				// Debut de CDATA ?
				if ($m[6][$i] && $m[6][$i][0])
				{
					$in_cdata = true;
				}
			}

			// Fermeture de balise ?
			if ($m[1][$i][0] || $m[5][$i][0])
			{
				$value = substr($str, $last_offset, $m[0][$i][1] - $last_offset);

				// Fin de CDATA ?
				if (substr($value, -2) == ']>')
				{
					$value = substr($value, 0, -3);
					$in_cdata = false;
				}

				// On ne prend pas en compte les tags dans un CDATA
				if ($in_cdata)
				{
					continue ;
				}

				// Verification de la fermeture du tag
				$check = array_pop($stack);
				if ($check != $tag)
				{
					$this->errstr = 'XML error : tag &lt;' . $check . '&gt; is different of &lt;' . $tag . '&gt;';
					return (false);
				}

				// Appel des handlers pour la fermeture des tags, et leur valeur
				if ($this->obj && $this->value_handler)
				{
					$this->obj->{$this->value_handler}($value);
				}
				else if ($this->value_handler)
				{
					call_user_func($this->value_handler, $value);
				}

				if ($this->obj && $this->close_handler)
				{
					$this->obj->{$this->close_handler}($tag);
				}
				else if ($this->close_handler)
				{
					call_user_func($this->close_handler, $tag);
				}
			}

			$last_offset = $current_offset;
		}

		return (true);
	}
}
/* EOF */