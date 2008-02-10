<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/user/user_viewprofile.php
** | Begin :	29/09/2005
** | Last :		20/06/2007
** | User :		Genva
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

// On affiche le module
$show_this_module = TRUE;

/*
** Module d'utilisateur redirigeant vers son propre profil public
*/
class Page_user_viewprofile extends Fsb_model
{
	/*
	** Constructeur
	*/
	public function __construct()
	{
		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=userprofile&id=' . Fsb::$session->id());
	}
}

/* EOF */