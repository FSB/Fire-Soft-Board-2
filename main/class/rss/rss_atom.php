<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/rss/rss_atom.php
** | Begin :	18/06/2007
** | Last :		12/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Spécification ATOM
*/
class Rss_atom extends Rss
{
	/*
	** Création du feed
	** -----
	** $title ::		Titre du fil
	** $description ::	Description du fil
	** $language ::		Langue du fil
	** $link ::			URL du site correspondant au canal
	** $updated ::		Timestamp de la dernière génération de ce fil
	*/
	protected function _open($title, $description, $language, $link, $updated)
	{
		$this->xml->document->setTagName('feed');
		$this->xml->document->setAttribute('version', '0.3');
		$this->xml->document->setAttribute('xmlns', 'http://purl.org/atom/ns#');
		$this->xml->document->setAttribute('xml:lang', $language);

		// Ajout des informations au fil
		$item = $this->xml->document->createElement('title');
		$item->setData($title);
		$this->xml->document->appendChild($item);

		$item = $this->xml->document->createElement('tagline');
		$item->setData(String::unhtmlspecialchars($description));
		$this->xml->document->appendChild($item);

		$item = $this->xml->document->createElement('link');
		$item->setAttribute('rel', 'alternate');
		$item->setAttribute('type', 'text/xml');
		$item->setAttribute('href', $link);
		$this->xml->document->appendChild($item);

		$item = $this->xml->document->createElement('modified');
		$item->setData($this->toISO8601($updated));
		$this->xml->document->appendChild($item);

		$item = $this->xml->document->createElement('id');
		$item->setData(Fsb::$cfg->get('fsb_path'));
		$this->xml->document->appendChild($item);

		$item = $this->xml->document->createElement('generator');
		$item->setData('FSB ' . intval(Fsb::$cfg->get('fsb_version')));
		$this->xml->document->appendChild($item);
	}

	/*
	** Ajout d'une entrée au fil
	** -----
	** $title ::		Titre du fil
	** $description ::	Description du fil
	** $author ::		Auteur de l'entrée
	** $link ::			Lien permettant de consulter l'entrée
	** $updated ::		Timestamp de la dernière génération de cette entrée
	*/
	protected function _add_entry($title, $description, $author, $link, $updated)
	{
		// Création de l'entrée
		$entry = $this->xml->document->createElement('entry');

		// Titre
		$item = $entry->createElement('title');
		$item->setData($title);
		$entry->appendChild($item);

		// Description
		$item = $entry->createElement('summary');
		$item->setData(String::unhtmlspecialchars($description));
		$entry->appendChild($item);

		// Auteur
		$item = $entry->createElement('author');
		$item->setData($author);
		$entry->appendChild($item);

		// Lien
		$item = $entry->createElement('link');
		$item->setData($link);
		$entry->appendChild($item);

		// Publication / MAJ
		$item = $entry->createElement('created');
		$item->setData($this->toISO8601($updated));
		$entry->appendChild($item);

		$item = $entry->createElement('issued');
		$item->setData($this->toISO8601($updated));
		$entry->appendChild($item);

		$item = $entry->createElement('modified');
		$item->setData($this->toISO8601($updated));
		$entry->appendChild($item);

		// ID unique
		$item = $entry->createElement('id');
		$item->setData($link);
		$entry->appendChild($item);

		// Ajout de l'entrée à l'arbre XML
		$this->xml->document->appendChild($entry);
	}

	/*
	** Fin du fil RSS
	*/
	protected function _close()
	{
	}

	/*
	** Converti un timestamp en spécification ISO8601
	** -----
	** $timestamp ::	Timestamp
	*/
	private function toISO8601($timestamp)
	{
		return (date("Y-m-d\TH:i:sO", $timestamp));
	}
}

/* EOF */