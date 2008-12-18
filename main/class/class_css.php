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
 * Parse et gestion de fichier CSS
 */
class Css extends Fsb_model
{
	/**
	 * Informations sur les fichiers CSS charges
	 *
	 * @var array
	 */
	public $data = array();

	/**
	 * Gestion des fichiers CSS importes
	 *
	 * @var array
	 */
	private $import = array();

	/**
	 * Charge et parse un fichier CSS
	 *
	 * @param string $filename Fichier CSS
	 */
	public function load_file($filename)
	{
		if (!file_exists($filename))
		{
			trigger_error('Le fichier ' . $filename . ' n\'existe pas', FSB_ERROR);
		}

		$this->load_content(file_get_contents($filename), $filename);
	}

	/**
	 * Charge et parse du code CSS
	 * 
	 * @param string $content Contenu CSS a charger
	 * @param string $filename Nom du fichier, qui servira d'identifiant
	 * @param string $parent Parent si le fichier actuel est importe
	 */
	public function load_content($content, $filename, $parent = null)
	{
		// Suppression des commentaires inutiles
		$content = preg_replace("#/\*.*?\*/(\r\n|\n)(\r\n|\n)#si", '', $content);

		// Preparation du stockage des informations
		$path = dirname($filename) . '/';
		$basename = basename($filename);
		$this->data[$basename] = array();
		$p = &$this->data[$basename];

		// Gestion des fichiers importes
		if (!is_null($parent))
		{
			if (!isset($this->import[$parent]))
			{
				$this->import[$parent] = array();
			}
			$this->import[$parent][] = $basename;
		}
		else
		{
			$this->import = array();
		}

		// Parse du fichier
		preg_match_all("`(\/\*(.*?)\*\/)?\s*([a-zA-Z0-9_\-#<>\.: \*,\"\[\]=]*?)\s*\{(.*?)\}`si", $content, $m);
		$count = count($m[0]);
		for ($i = 0; $i < $count; $i++)
		{
			$properties = $this->parse_properties($m[4][$i]);

			$p[] = array(
				'name' =>		$m[3][$i],
				'comments' =>	trim($m[2][$i]),
				'properties' =>	$properties,
			);
		}

		// On charge recursivement les @import
		preg_match_all('#@import\s+(url\([^)]*?\)|.*?);#', $content, $m);
		$count = count($m[0]);
		for ($i = 0; $i < $count; $i++)
		{
			$url = $m[1][$i];
			if ($url[0] == "'" || $url[0] == '"')
			{
				$url = substr($url, 1);
			}

			if ($url[strlen($url) - 1] == "'" || $url[strlen($url) - 1] == '"')
			{
				$url = substr($url, 0, -1);
			}

			if (file_exists($path . $url))
			{
				$this->load_content(file_get_contents($path . $url), $path . $url, $basename);
			}
		}
	}

	/**
	 * Parse les proprietes d'une classe CSS
	 * 
	 * @param string $str Les proprietes a parser
	 */
	public function parse_properties($str)
	{
		// Suppression des commentaires dans les proprietes
		$list_properties = preg_replace("#/\*.*?\*/#si", '', trim($str));

		// Parse des proprietes
		$properties = array();
		foreach (explode(';', $list_properties) AS $property)
		{
			$property = trim($property);
			if ($property)
			{
				list($key, $value) = explode(':', $property);
				$properties[trim($key)] = trim($value);
			}
		}

		return ($properties);
	}

	/**
	 * Regenere et ecrit les fichiers CSS
	 *
	 * @param string $path Chemin ou regenerer les fichiers
	 * @param string $file Nom du fichier a regenerer, si aucun fichier precise on les regenere tous
	 */
	public function write($path, $file = null)
	{
		if (!is_dir($path))
		{
			trigger_error('Le dossier ' . $path . ' n\'existe pas', FSB_ERROR);
		}

		foreach ($this->data AS $filename => $data)
		{
			if (!is_null($file) && $file != $filename)
			{
				continue ;
			}

			$content = '';
			foreach ($data AS $subdata)
			{
				if (isset($subdata['comments']) && $subdata['comments'])
				{
					$content .= '/* ' . $subdata['comments'] . " */\n";
				}

				$content .= $subdata['name'] . "\n{\n";
				$content .= $this->get_properties($subdata, "\t");
				$content .= "}\n\n";
			}

			// Header du fichier CSS
			$header = "/**\n";
			$header .= " * Fire-Soft-Board version 2\n";
			$header .= " * \n";
			$header .= " * @package FSB2\n";
			$header .= " * @author Genova <genova@fire-soft-board.com>\n";
			$header .= " * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2\n";
			$header .= " */\n\n";

			// Import des autres fichiers
			if (isset($this->import[$filename]))
			{
				foreach ($this->import[$filename] AS $import)
				{
					$header .= "@import \"$import\";\n";
				}
				$header .= "\n";
			}

			fsb_write($path . '/' . $file, $header . $content);
		}
	}

	/**
	 * Retourne les proprietes d'une classe sous forme de chaine
	 *
	 * @param array $data Donnees de la classe
	 * @param string $prefix Chaine a afficher avant les proprietes
	 * @return string
	 */
	public function get_properties($data, $prefix = '')
	{
		$content = '';
		foreach ($data['properties'] AS $key => $value)
		{
			$content .= $prefix . $key . ': ' . $value . ";\n";
		}
		return ($content);
	}

	/**
	 * Retourne la valeur d'une propriete
	 *
	 * @param array $data Donnees de la classe
	 * @param string $key Nom de la propriete
	 * @return string
	 */
	public function get_property($data, $key)
	{
		return ((isset($data['properties'][$key])) ? $data['properties'][$key] : null);
	}

	/**
	 * Assigne une propriete a la classe
	 *
	 * @param array $data Donnees de la classe
	 * @param string $key Nom de la propriete
	 * @param string $value Valeur de la propriete
	 */
	public function set_property(&$data, $key, $value)
	{
		$data['properties'][$key] = $value;
	}
}

/* EOF */