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
 * Permet d'importer / exporter des langues en XML.
 * Necessite la classe Xml() pour la gestion des donnees en XML.
 */
class Lang_xml extends Fsb_model
{
	/**
	 * @var array
	 */
	private $custom = array('lg' => array(), 'faq' => array());
	
	/**
	 * @var Xml
	 */
	private $xml;

	/**
	 * Exporte une langue sous forme de fichier XML
	 *
	 * @param string $path Dossier de la langue
	 * @return string
	 */
	public function export($path)
	{
		if (!is_dir($path))
		{
			trigger_error('Le repertoire ' . $path . ' n\'existe pas', FSB_ERROR);
		}

		// On inclu le fichier lg_common.php pour recuperer le charset
		$lang = include($path . 'lg_common.' . PHPEXT);
		$charset = $lang['charset'];

		// On recupere les donnees de la langue entrees via le panneau d'administration
		$sql = 'SELECT lang_key, lang_value
				FROM ' . SQL_PREFIX . 'langs
				WHERE lang_name = \'' . Fsb::$db->escape(basename($path)) . '\'';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			if (preg_match('#^_fsb_faq_\[([a-zA-Z0-9_]+?)\]\[([a-zA-Z0-9_]+?)\]\[(question|answer)\]$#', $row['lang_key'], $match))
			{
				$this->custom['faq'][$match[1]][$match[2]][$match[3]] = $row['lang_value'];
			}
			else
			{
				$this->custom['lg'][$row['lang_key']] = $row['lang_value'];
			}
		}
		Fsb::$db->free($result);

		// Instance de la classe XML
		$this->xml = new Xml;

		// Creation d'un nouveau document
		$this->xml->document->setTagName('language');

		// Ajout du nom du language
		$name = $this->xml->document->createElement('name');
		$name->setData(basename($path));
		$this->xml->document->appendChild($name);

		// Exportation
		$this->export_dir($path);

		return ($this->xml->document->asValidXml());
	}

	/**
	 * Exporte les fichiers langue d'un dossier
	 *
	 * @param string $path Dossier contenant les fichiers langues
	 * @param string $current_dir
	 */
	private function export_dir($path, $current_dir = '')
	{
		// Type de fichier du repertoire courant
		$type = ($current_dir == 'mail/') ? 'txt' : 'php';

		// Parcourt du dossier de langue
		$fd = opendir($path);
		while ($file = readdir($fd))
		{
			if ($file != '.' && $file != '..' && $file != '.svn' && $file != 'index.html')
			{
				if (is_dir($path . $file))
				{
					// En cas de dossier on relance recursivement la fonction
					$this->export_dir($path . $file . '/', $file . '/');
				}
				else
				{
					// Sinon on va gerer le contenu du fichier
					$filename = get_file_data($file, 'filename');
					$extension = get_file_data($file, 'extension');

					// Creation d'un nouvel element <file>
					$f = $this->xml->document->createElement('file');
					$f->setAttribute('name', $current_dir . $filename);
					$f->setAttribute('type', $type);

					// Suivant le type de fichier on gere differement les contenus
					switch ($extension)
					{
						case 'txt' :
							$this->export_txt_file($f, $path . $file);
						break;

						case 'php' :
							$this->export_php_file($f, $path . $file, $filename);
						break;
					}
					$this->xml->document->appendChild($f);
				}
			}
		}
		closedir($fd);
	}

	/**
	 * Exporte le contenu d'un fichier PHP
	 *
	 * @param XML_document $f Represente le fichier en cours
	 * @param string $path Chemin du fichier
	 * @param string $filename Nom du fichier
	 */
	private function export_php_file(&$f, $path, $filename)
	{
		$current_lang = include($path);
		foreach ($current_lang AS $key => $value)
		{
			$lang = $f->createElement('lg');
			$lang->setAttribute('key', htmlspecialchars($key));
			$lang->setData((isset($this->custom['lg'][$key])) ? $this->custom['lg'][$key] : $value);
			$f->appendChild($lang);
		}

		// Pour la FAQ on utilise un traitement special pour le tableau de donnees
		if ($filename == 'lg_forum_faq')
		{
			foreach ($GLOBALS['faq_data'] AS $section => $data)
			{
				$lang = $f->createElement('lg');
				$lang->setAttribute('key', '_fsb_faq_data');
				$lang->setAttribute('section', $section);
				foreach ($data AS $faq_name => $faq_data)
				{
					// Ajout d'un enfant <faq>
					$faq = $lang->createElement('faq');
					$faq->setAttribute('key', $faq_name);

					// Ajout d'un enfant <value> pour la question
					$value = $faq->createElement('value');
					$value->setAttribute('key', 'question');
					$value->setData((isset($this->custom['faq'][$section][$faq_name]['question'])) ? $this->custom['faq'][$section][$faq_name]['question'] : $faq_data['question']);
					$faq->appendChild($value);

					// Ajout d'un enfant <value> pour la reponse
					$value = $faq->createElement('value');
					$value->setAttribute('key', 'answer');
					$value->setData((isset($this->custom['faq'][$section][$faq_name]['answer'])) ? $this->custom['faq'][$section][$faq_name]['answer'] : $faq_data['answer']);
					$faq->appendChild($value);

					$lang->appendChild($faq);
				}
				$f->appendChild($lang);
			}
		}
	}

	/**
	 * Exporte le contenu d'un fichier TXT
	 *
	 * @param XML_document $f Objet Represente le fichier en cours
	 * @param string $path Chemin du fichier
	 */
	private function export_txt_file(&$f, $path)
	{
		$dir = dirname($path);
		$filename = get_file_data(basename($path), 'filename');
		$path = (file_exists($dir . '/' . $filename . '.updated')) ? $dir . '/' . $filename . '.updated' : $path;

		$content = $f->createElement('content');
		$content->setData(file_get_contents($path));
		$f->appendChild($content);
	}

	/**
	 * Importe un fichier XML sous forme de dossier de langue
	 *
	 * @param string $path Chemin du fichier XML
	 */
	public function import($path)
	{
		// Instance de la classe File
		$file_builder = File::factory(false);
		$file_builder->connexion('', '', '', '', ROOT);

		// On recupere le charset
		$fd = fopen(ROOT . $path, 'r');
		$content = fread($fd, 64);
		preg_match('#encoding="(.*?)"#i', $content, $match);
		$charset = $match[1];
		fclose($fd);

		// Instance de la classe Xml
		$xml = new Xml();
		$xml->load_file(ROOT . $path, false);

		// On recupere le nom de la langue
		$language = $xml->document->name[0]->getData();

		// Repertoire de la langue
		$root = 'lang/' . $language . '/';
		$file_builder->mkdir($root);

		// On genere les fichiers langue
		foreach ($xml->document->file AS $file)
		{
			// On recupere le nom et le type de fichier
			$filename = $file->getAttribute('name');
			$type = $file->getAttribute('type');

			// On cree le repertoire
			$dirname = (dirname($filename) != '.') ? dirname($filename) : '';
			$file_builder->mkdir($root . $dirname);

			switch ($type)
			{
				case 'php' :
					$lg_data = array();
					$faq_data = array();

					if ($file->childExists('lg'))
					{
						foreach ($file->lg AS $lang)
						{
							$key = $lang->getAttribute('key');

							// On gere les clefs speciales _fsb_lang_faq et _fsb_stopword
							switch ($key)
							{
								case '_fsb_faq_data' :
									// Section de la FAQ
									$section = $lang->getAttribute('section');
									$faq_data[$section] = array();

									// On recupere le contenu de la FAQ
									foreach ($lang->faq AS $faq)
									{
										$faq_key = $faq->getAttribute('key');
										foreach ($faq->value AS $value)
										{
											$key = $value->getAttribute('key');
											$data = $value->getData();
											$faq_data[$section][$faq_key][$key] = $data;
										}
									}
								break;

								case '_fsb_stopword' :
									$content = '';
									foreach ($lang->word AS $word)
									{
										$content .= trim($word->getData()) . "\n";
									}
									$file_builder->write($root . 'stopword.txt', $content);

								// On veut sortir en dehors du for et du switch ($type)
								break 3;

								default :
									$lg_data[$key] = trim($lang->getData());
								break;
							}
						}
					}

					// On genere le fichier langue
					$content = "<?php\n";
					$content .= "/**\n";
					$content .= " * Fire-Soft-Board version 2\n";
					$content .= " * \n";
					$content .= " * @package FSB2\n";
					$content .= " * @author Genova <genova@fire-soft-board.com>\n";
					$content .= " * @version \$Id: class_lang_xml.php 51 2008-11-16 20:43:34Z genova \$\n";
					$content .= " * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2\n";
					$content .= " */\n\n";

					// Pour la FAQ on ajoute un bout de code
					if ($faq_data)
					{
						$content .= '$GLOBALS[\'faq_data\'] = ' . var_export($faq_data, true) . ";\n\n";
						$faq_data = array();
					}

					$content .= 'return (' . var_export($lg_data, true) . ");\n\n";
					$content .= "\n/* EOF */";
					$file_builder->write($root . $filename . '.' . PHPEXT, $content);

					unset($lg_data);
				break;

				case 'txt' :
					$content = $file->content[0]->getData();
					$file_builder->write($root . $filename . '.txt', $content);
				break;
			}
		}

		// On libere la memoire
		unset($xml);
	}
}

/* EOF */