<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/rss/rss.php
** | Begin :	18/06/2007
** | Last :		13/08/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Classe permettant la generation de fils RSS au format RSS 2.0 ou ATOM
*/
abstract class Rss extends Fsb_model
{
	// Objet XML
	protected $xml;

	abstract protected function _open($title, $description, $language, $link, $updated);
	abstract protected function _add_entry($title, $description, $author, $link, $updated);
	abstract protected function _close();

	/*
	** Retourne une instance d'un generateur de fil RSS en fonction du type de specificiation choisi
	** -----
	** $method ::	Type de specification (rss2 ou atom)
	*/
	public static function factory($method)
	{
		switch ($method)
		{
			case 'atom' :
				return (new Rss_atom());
			break;

			case 'rss2' :
			default :
				return (new Rss_rss2());
			break;
		}
	}

	/*
	** Creation du feed
	** -----
	** $title ::		Titre du fil
	** $description ::	Description du fil
	** $language ::		Langue du fil
	** $link ::			URL du site correspondant au canal
	** $updated ::		Timestamp de la derniere generation de ce fil
	*/
	public function open($title, $description, $language, $link, $updated)
	{
		// Instance d'un objet XML
		$this->xml = new Xml;

		// Generation du document
		$this->_open($title, $description, $language, $link, $updated);
	}

	/*
	** Ajout d'une entree au fil
	** -----
	** $title ::		Titre du fil
	** $description ::	Description du fil
	** $author ::		Auteur de l'entree
	** $link ::			Lien permettant de consulter l'entree
	** $updated ::		Timestamp de la derniere generation de cette entree
	*/
	public function add_entry($title, $description, $author, $link, $updated)
	{
		$this->_add_entry($title, $description, $author, $link, $updated);
	}

	/*
	** Fermeture et affichage du fil RSS
	*/
	public function close($print = TRUE)
	{
		$this->_close();
		$string = $this->xml->document->asValidXML();
		if ($print)
		{
			Http::no_cache();
			Http::header('Last-Modified', gmdate( 'D, d M Y H:i:s' ) . ' GMT');
			Http::header('Cache-Control', 'no-store, no-cache, must-revalidate');
			Http::header('Content-Type', 'text/xml; charset=' . Fsb::$session->lang('charset'));

			echo $string;
			exit;
		}

		return ($string);
	}
}

/* EOF */