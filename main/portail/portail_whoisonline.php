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
 * Module de portail permettant d'afficher qui est en ligne
 */
class Page_portail_whoisonline extends Fsb_model
{
	/**
	 * Constructeur
	 */
	public function main()
	{
		fsb_import('online');
	}
}

/* EOF */