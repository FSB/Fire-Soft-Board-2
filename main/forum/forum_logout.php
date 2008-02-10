<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/forum/forum_logout.php
** | Begin :	13/06/2005
** | Last :		09/08/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Deconnexion d'un membre
*/
class Fsb_frame_child extends Fsb_frame
{
	// Paramètres d'affichage de la page (barre de navigation, boite de stats)
	public $_show_page_header_nav = TRUE;
	public $_show_page_footer_nav = FALSE;
	public $_show_page_stats = FALSE;

	/*
	** Constructeur
	*/
	public function main()
	{
		// On vérifie si la SID a été passée par l'URL, pour des raisons de sécurité
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
