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
 * Classe permettant la generation de fils RSS au format RSS 2.0 ou ATOM
 */
abstract class Rss extends Fsb_model
{
	/**
	 * @var Xml
	 */
	protected $xml;

	/**
	 * Methode appelee lors de l'ouverture du flux RSS
	 *
	 * @param string $title Titre
	 * @param string $description Description
	 * @param string $language Langue
	 * @param string $link Lien
	 * @param string $updated Derniere mise a jour
	 */
	abstract protected function _open($title, $description, $language, $link, $updated);
	
	/**
	 * Ajoute une entree au flux
	 *
	 * @param string $title Titre
	 * @param string $description Description
	 * @param string $author Auteur de l'entree
	 * @param string $link Lien
	 * @param string $updated Derniere mise a jour
	 */
	abstract protected function _add_entry($title, $description, $author, $link, $updated);
	
	/**
	 * Methode appelee lors de la fermeture du flux RSS
	 */
	abstract protected function _close();

	/**
	 * Retourne une instance d'un generateur de fil RSS en fonction du type de specificiation choisi
	 *
	 * @param string $method Type de specification (rss2 ou atom)
	 * @return Rss
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

	/**
	 * Ouvre le flux RSS
	 *
	 * @param string $title Titre
	 * @param string $description Description
	 * @param string $language Langue
	 * @param string $link Lien
	 * @param string $updated Derniere mise a jour
	 */
	public function open($title, $description, $language, $link, $updated)
	{
		// Instance d'un objet XML
		$this->xml = new Xml;

		// Generation du document
		$this->_open($title, $description, $language, $link, $updated);
	}

	/**
	 * Ajoute une entree au flux
	 *
	 * @param string $title Titre
	 * @param string $description Description
	 * @param string $author Auteur de l'entree
	 * @param string $link Lien
	 * @param string $updated Derniere mise a jour
	 */
	public function add_entry($title, $description, $author, $link, $updated)
	{
		$this->_add_entry($title, $description, $author, $link, $updated);
	}

	/**
	 * Ferme et affiche le flux
	 *
	 * @param bool $print Si on affiche le flux ou si on le recupere
	 */
	public function close($print = true)
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