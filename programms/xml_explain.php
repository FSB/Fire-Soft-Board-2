<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

die('Pour pouvoir utiliser ce fichier veuillez commenter cette ligne. <b>Ce fichier est une faille potentielle de sécurité</b>, ne l\'utilisez qu\'en local, ou si vous êtes certain de ce que vous faites');

/**
 * Ce fichier parse et affiche correctement une documentation XML
 */

error_reporting(E_ALL);

$id = (isset($_GET['id'])) ? $_GET['id'] : null;
$data = array(
	'map' =>		'../doc/maps.txt',
	'mod' =>		'../doc/mods.txt',
	'procedure' =>	'../doc/procedure.txt',
	'fsbcard' =>	'../doc/fsbcard.txt',
);

$filename = (isset($data[$id])) ? $data[$id] : $data['map'];

$xml_explain = new Xml_explain;
$xml_explain->load_file($filename);

?>
<html>
<head>
	<title><?php echo $xml_explain->title ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<link type="text/css" rel="stylesheet" href="style/design-mod.css" />
</head>
<body>
<?php $xml_explain->output(); ?>
<br />
<div id="copyright">Forum <a href="http://www.fire-soft-board.com">Fire Soft Board</a></div>
</body>
</html>
<?php

class Xml_explain
{
	// Contenu a parser
	private $code;

	// Titre, description
	public $title, $description = '';

	// Charge un fichier descripteur
	public function load_file($filename)
	{
		if (!file_exists($filename))
		{
			die("Le fichier $filename n'existe pas");
		}
		$this->load_content(file_get_contents($filename));
	}

	// Charge un code descripteur
	public function load_content($code)
	{
		// parse titre et description
		if (preg_match("#\n\s*@title=(.*?)\n#si", $code, $t))
		{
			$this->title = $t[1];
		}

		if (preg_match("#\n\s*@description=(.*?)\n#si", $code, $d))
		{
			$this->description = $d[1];
		}

		// Parse des commentaires et sauts de ligne
		$code = preg_replace('#<\!--.*?-->#si', '', $code);
		$code = str_replace(array("\r\n", "\r"), array("\n", "\n"), $code);

		$this->code = $code;
	}

	// Parse et affichage
	public function output()
	{
		// Parcours ligne par ligne
		$level = 0;
		$last_level = -1;
		$right = $left = '';
		$open = false;
		$attr = array();
		foreach (explode("\n", $this->code) AS $i => $line)
		{
			if (!trim($line))
			{
				continue ;
			}

			// Gestion du niveau de profondeur (tabulations) avec ouverture / fermeture des DIV
			if (preg_match("#^(\t*)[^\t]#", $line, $m))
			{
				$level = (strlen($m[1])) ? count(str_split($m[1])) : 0;
			}
			else
			{
				$level = 0;
			}
			$line = trim($line);

			if ($line[0] == '*')
			{
				$item = 'tag';
			}
			else if ($line[0] == '-')
			{
				$item = 'attr';
			}
			else
			{
				continue ;
			}

			if ($item == 'tag')
			{
				if ($level > $last_level)
				{
					// Ouverture
					if ($level > $last_level + 1)
					{
						die("Trop de tabulations a la ligne $i");
					}

					$left .= '<ul style="list-style-type: disc">';
					$right .= '<div id="div_' . $i . '">';
				}
				else if ($level < $last_level)
				{
					// Fermeture
					for ($j = $level; $j < $last_level; $j++)
					{
						$left .= '</ul>';
						$right .= '</div>';
					}
				}
				$last_level = $level;
			}

			// Parse de la ligne
			if (preg_match('#^([^:]*?):(.*?)$#i', $line, $m))
			{
				$info = trim(substr($m[1], 1));
				$description = $m[2];

				if ($item == 'tag')
				{
					// Tag de la balise
					if (!preg_match('#^<([a-zA-Z0-9\-_]*?)(\s*/)?>#i', $info, $m))
					{
						die("Il manque une balise a la ligne $i");
					}
					$tag = $m[1];
					$end = @$m[2];

					// Contenu de la balise
					$contain = '';
					if (preg_match('#\(\.\.\.\)#', $info))
					{
						$contain = 'cette balise contient du texte.';
					}
					else if (preg_match('#<\.\.\.>#', $info))
					{
						$contain = 'cette balise contient d\'autres balises.';
					}
					else if (preg_match('#\((([a-zA-Z0-9_\- ]*?)(\|[a-zA-Z0-9_\- ]*?)+)\)#', $info, $n))
					{
						$split = explode('|', $n[1]);
						$contain = 'cette balise peut prendre la valeur <i>' . implode('</i> ou <i>', $split) . '</i>.';
					}

					$repeat = '';
					if (preg_match('#\#\.\.\.\##', $info))
					{
						$repeat = '<div class="contain">Cette balise peut se repeter indefiniment.</div>';;
					}

					$not_implemented = false;
					if (strpos($info, '(!)') !== false)
					{
						$not_implemented =  true ;
					}

					$left .= '<li><a href="#row_' . $i . '">' . (($not_implemented) ? '! ' : '') . $tag . '</a></li>';

					$open =  true ;
					$right .= '<div class="container"><div id="row_' . $i . '" style="margin-left: ' . ($level * 30) . 'px" class="explain"><h2>&lt;' . $tag . (($end) ? ' /' : '') . '&gt;' . (($not_implemented) ? ' <span class="not_implemented">Non implemente</span>' : '') . '</h2>';
					if ($contain)
					{
						$right .= '<div class="contain">Contenu : ' . $contain . '</div>';
					}
					$right .= $repeat;
					$right .= '<span class="description">' . htmlspecialchars($description) . '</span>{ATTR_' . $tag . '}';
					$right .=  '</div></div>';
				}
				else
				{
					if (!isset($attr[$tag]))
					{
						$attr[$tag] = array();
					}
					$attr[$tag][$info] = $description;
				}
			}
		}

		// Attributs
		foreach ($attr AS $tag => $info)
		{
			$str = '<p><b>Attributs :</b><ul>';
			foreach ($info AS $attr => $desc)
			{
				$value = '';
				$attr = preg_replace('#\(\.\.\.\)#', '', $attr);
				if (preg_match('#\((([a-zA-Z0-9\- ]*?)(\|[a-zA-Z0-9\- ]*?)+)\)#', $attr, $n))
				{
					$split = explode('|', $n[1]);
					$value = '(Valeur : <i>' . implode('</i> ou <i>', $split) . '</i>)';
					$attr = preg_replace('#\((([a-zA-Z0-9\-]*?)(\|[a-zA-Z0-9\-]*?)+)\)#', '', $attr);
				}

				$not_implemented = false;
				if (strpos($attr, '(!)') !== false)
				{
					$not_implemented =  true ;
					$attr = str_replace('(!)', '', $attr);
				}
				$str .= '<li><b>' . (($not_implemented) ? '(<span class="not_implemented">Non implemente</span>) ' : '') . $attr . ' ' . $value . '</b> : ' . htmlspecialchars($desc) . '</li>';
			}
			$str .= '</ul></p>';

			$right = str_replace('{ATTR_' . $tag . '}', $str, $right);
		}
		$right = preg_replace('#\{ATTR_[a-z0-9A-Z\-_]*?\}#', '', $right);

		// Fermeture des DIV
		for ($j = 0; $j <= $last_level; $j++)
		{
			$left .= '</ul>';
			$right .= '</div>';
		}

		// Affichage
		echo '<div id="header"><h1>' . htmlspecialchars($this->title) . '</h1><h4>' . htmlspecialchars($this->description) . '</h4></div><div id="left">' . $left . '</div><div id="right">' . $right . '</div>';
	}
}

/* EOF */
