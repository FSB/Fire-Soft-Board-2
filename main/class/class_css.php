<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_css.php
** | Begin :	05/07/2005
** | Last :		06/08/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

class Css extends Fsb_model
{
	public $data = array();
	private $basename = NULL, $dirname = NULL;

	/*
	** Charge un fichier CSS
	** -----
	** $filename ::	Nom du fichier à charger
	*/
	public function load_file($filename)
	{
		if (!file_exists($filename))
		{
			trigger_error('Le fichier ' . $filename . ' n\'existe pas', FSB_ERROR);
		}

		$this->load_content(file_get_contents($filename), $filename);
	}

	/*
	** Charge du code CSS
	** -----
	** $content ::	Contenu CSS à charger
	** $filename ::	Chemin du fichier, qui servira d'identifiant
	*/
	public function load_content($content, $filename)
	{
		$path = dirname($filename) . '/';
		$basename = basename($filename);
		$this->data[$basename] = array();

		// On charge récursivement les @import
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
				$this->parse($path . $url, file_get_contents($path . $url));
			}
		}

		// Chargement du fichier actuel
		$this->parse($filename, $content);
	}

	/*
	** Parse un fichier
	** -----
	** $filename ::		Nom du fichier (servira d'identifiant)
	** $content ::		Contenu à parser
	*/
	private function parse($filename, $content)
	{
		// Suppression des commentaires inutiles
		$content = preg_replace("#/\*.*?\*/(\r\n|\n)(\r\n|\n)#si", '', $content);

		// Préparation du stockage des informations
		$basename = basename($filename);
		$this->data[$basename] = array();
		$p = &$this->data[$basename];

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
	}

	/*
	** Parse des propriétés
	** -----
	** $str ::	Chaîne de caractère (propriétés)
	*/
	public function parse_properties($str)
	{
		// Suppression des commentaires dans les propriétés
		$list_properties = preg_replace("#/\*.*?\*/#si", '', trim($str));

		// Parse des propriétés
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

	/*
	** Regénère les fichiers CSS
	** -----
	** $path ::		Chemin où regénérer les fichiers
	** $file ::		Nom du fichier à regénérer, si aucun fichier précisé on les regenère tous
	*/
	public function write($path, $file = NULL)
	{
		if (!is_dir($path))
		{
			trigger_error('Le dossier ' . $path . ' n\'existe pas', FSB_ERROR);
		}

		foreach ($this->data AS $filename => $data)
		{
			if ($file !== NULL && $file != $filename)
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

			$header = "/*\n";
			$header .= "** +---------------------------------------------------+\n";
			$header .= "** | Name :	~/tpl/${path}${filename}\n";
			$header .= "** | Project :	Fire-Soft-Board 2 - Copyright FSB group\n";
			$header .= "** | License :	GPL v2.0\n";
			$header .= "** |\n";
			$header .= "** | Vous pouvez modifier, réutiliser et redistribuer\n";
			$header .= "** | ce fichier à condition de laisser cet entète.\n";
			$header .= "** +---------------------------------------------------+\n";
			$header .= "*/\n\n";

			fsb_write($path . '/' . $file, $header . $content);
		}
	}

	/*
	** Retourne les propriétés d'une classe sous forme de chaîne
	** -----
	** $data ::		Données de la classe
	** $prefix ::	Chaîne à afficher avant les propriétés
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

	/*
	** Retourne la valeur d'une propriété
	** -----
	** $data ::		Données de la classe
	** $key ::		Nom de la propriété
	*/
	public function get_property($data, $key)
	{
		return ((isset($data['properties'][$key])) ? $data['properties'][$key] : NULL);
	}

	/*
	** Assigne une propriété à la classe
	** -----
	** $data ::		Données de la classe
	** $key ::		Nom de la propriété
	** $value ::	Valeur de la propriété
	*/
	public function set_property(&$data, $key, $value)
	{
		$data['properties'][$key] = $value;
	}
}

/* EOF */