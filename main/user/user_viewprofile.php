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
 * On affiche le module
 * 
 * @var bool
 */
$show_this_module = true;

/**
 * Module d'utilisateur redirigeant vers son propre profil public
 */
class Page_user_viewprofile extends Fsb_model
{
	/**
	 * Constructeur
	 */
	public function __construct()
	{
		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=userprofile&id=' . Fsb::$session->id());
	}
}

/* EOF */