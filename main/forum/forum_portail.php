<?php
/*
** +---------------------------------------------------+
** | Name :			~/main/forum/forum_portail.php
** | Begin :		08/11/2005
** | Last :			20/06/2007
** | User :			Genova
** | Project :		Fire-Soft-Board 2 - Copyright FSB group
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Generation de la page d'accueil avec ses differents modules
*/
class Fsb_frame_child extends Fsb_frame
{
	// Parametres d'affichage de la page (barre de navigation, boite de stats)
	public $_show_page_header_nav = TRUE;
	public $_show_page_footer_nav = FALSE;
	public $_show_page_stats = FALSE;

	/*
	** Constructeur
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