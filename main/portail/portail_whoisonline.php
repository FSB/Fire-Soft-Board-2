<?php
/*
** +---------------------------------------------------+
** | Name :			~/main/portail/portail_whoisonline.php
** | Begin :		28/11/2005
** | Last :			20/06/2007
** | User :			Genova
** | Project :		Fire-Soft-Board 2 - Copyright FSB group
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Module de portail permettant d'afficher qui est en ligne
*/
class Page_portail_whoisonline extends Fsb_model
{
	/*
	** Constructeur
	*/
	public function main()
	{
		fsb_import('online');
	}
}

/* EOF */