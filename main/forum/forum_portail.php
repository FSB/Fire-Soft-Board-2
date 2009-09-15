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
 * Generation de la page d'accueil avec ses differents modules
 *
 */
class Fsb_frame_child extends Fsb_frame
{
	/**
	 * Affichage de la barre de navigation du header
	 *
	 * @var bool
	 */
	public $_show_page_header_nav = true;
	
	/**
	 * Affichage de la barre de navigation du footer
	 *
	 * @var bool
	 */
	public $_show_page_footer_nav = false;
	
	/**
	 * Affichage de la boite des stats
	 *
	 * @var bool
	 */
	public $_show_page_stats = false;


	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		// Portail pas active ?
		if (!Fsb::$mods->is_active('portail'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		$portail = new Portail();
		$portail->output_all();
	}
}

/* EOF */