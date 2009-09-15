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
 * Deconnexion d'un membre
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
		// On verifie si la SID a ete passee par l'URL, pour des raisons de securite
		if (Fsb::$session->sid !== Http::request('sid', 'get'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		if (Fsb::$session->is_logged())
		{
			Fsb::$session->logout(Fsb::$session->id());
		}

		// Redirection dynamique
		Http::redirect_to(Http::request('redirect'));
	}
}

/* EOF */
