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
** Specification ATOM
*/
class Rss_atom extends Rss
{
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

		// Ajout de l'entree a l'arbre XML
		$this->xml->document->appendChild($entry);
	}

	/*
	** Fin du fil RSS
	*/
	protected function _close()
	{
	}

	/*
	** Converti un timestamp en specification ISO8601
	** -----
	** $timestamp ::	Timestamp
	*/
	private function toISO8601($timestamp)
	{
		return (date("Y-m-d\TH:i:sO", $timestamp));
	}
}

/* EOF */