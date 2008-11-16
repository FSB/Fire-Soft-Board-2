<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

/*
** Specification RSS 2.0
*/
class Rss_rss2 extends Rss
{
	private $document;

	/*
	** Creation du feed
	** -----
	** $title ::		Titre du fil
	** $description ::	Description du fil
	** $language ::		Langue du fil
	** $link ::			URL du site correspondant au canal
	** $updated ::		Timestamp de la derniere generation de ce fil
	*/
	protected function _open($title, $description, $language, $link, $updated)
	{
		$this->xml->document->setTagName('rss');
		$this->xml->document->setAttribute('version', '2.0');
		$this->document = $this->xml->document->createElement('channel');

		// Ajout des informations au fil
		$item = $this->document->createElement('title');
		$item->setData($title);
		$this->document->appendChild($item);

		$item = $this->document->createElement('description');
		$item->setData(String::unhtmlspecialchars($description));
		$this->document->appendChild($item);

		$item = $this->document->createElement('language');
		$item->setData($language);
		$this->document->appendChild($item);

		$item = $this->document->createElement('link');
		$item->setData($link);
		$this->document->appendChild($item);

		$item = $this->document->createElement('lastBuildDate');
		$item->setData($this->toRFC822($updated));
		$this->document->appendChild($item);

		$item = $this->document->createElement('generator');
		$item->setData('FSB ' . intval(Fsb::$cfg->get('fsb_version')));
		$this->document->appendChild($item);
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
	protected function _add_entry($title, $description, $author, $link, $updated)
	{
		// Creation de l'entree
		$entry = $this->document->createElement('item');

		// Titre
		$item = $entry->createElement('title');
		$item->setData($title);
		$entry->appendChild($item);

		// Description
		$item = $entry->createElement('description');
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
		$item = $entry->createElement('pubDate');
		$item->setData($this->toRFC822($updated));
		$entry->appendChild($item);

		// ID unique
		$item = $entry->createElement('guid');
		$item->setData($link);
		$item->setAttribute('isPermaLink', 'false');
		$entry->appendChild($item);

		// Ajout de l'entree a l'arbre XML
		$this->document->appendChild($entry);
	}

	/*
	** Fin du fil RSS
	*/
	protected function _close()
	{
		$this->xml->document->appendChild($this->document);
	}

	/*
	** Converti un timestamp en specification RFC822
	** -----
	** $timestamp ::	Timestamp
	*/
	private function toRFC822($timestamp)
	{
		return (date("D, d M Y H:i:s", $timestamp));
	}
}

/* EOF */