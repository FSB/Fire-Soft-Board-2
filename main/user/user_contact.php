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
 * Module d'utilisateur permettant de mettre au membre ses donnees pour le contacter
 * Les champs de contacts sont crees dynamiquement dans l'administration a l'aide des tables
 * `fsb2_profil_fields` et `fsb2_users_contact`
 * Cette page permet aussi d'entrer les adresses de notifications
 */
class Page_user_contact extends Fsb_model
{
	/**
	 * Variable d'erreurs
	 *
	 * @var array
	 */
	public $errstr = array();
	
	/**
	 * Donnees de contact du membre
	 *
	 * @var array
	 */
	public $user_contact = array();

	/**
	 * Donnees recuperees dans le formulaire
	 *
	 * @var array
	 */
	public $post_data = array();

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		if (Http::request('submit', 'post'))
		{
			$this->check_form();
			if (!count($this->errstr))
			{
				$this->submit_form();
			}
		}
		$this->contact_form();
	}

	/**
	 * Genere la page de formulaire permettant au membre de remplir ses
	 * donnees de contact
	 */
	public function contact_form()
	{
		if ($this->errstr)
		{
			Fsb::$tpl->set_switch('error');
		}

		Fsb::$tpl->set_file('user/user_contact.html');
		Fsb::$tpl->set_vars(array(
			'ERRSTR' =>			Html::make_errstr($this->errstr),
		));
		
		// Champs contacts crees par l'administrateur
		Profil_fields_forum::form(PROFIL_FIELDS_CONTACT, 'contact', Fsb::$session->id());
	}

	/**
	 * Verifie la validite des donnees du formulaire en fonction des expressions regulieres definies
	 * pour les champs de contact
	 */
	public function check_form()
	{
		Profil_fields_forum::validate(PROFIL_FIELDS_CONTACT, 'contact', $this->errstr, Fsb::$session->id());
	}

	/**
	 * Traite et soumet le formulaire
	 */
	public function submit_form()
	{
		Display::message('user_profil_submit', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=contact', 'forum_profil');
	}
}

/* EOF */