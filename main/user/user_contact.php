<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/user/user_contact.php
** | Begin :	19/09/2005
** | Last :		29/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

// On affiche le module
$show_this_module = TRUE;

/*
** Module d'utilisateur permettant de mettre au membre ses données pour le contacter
** Les champs de contacts sont créés dynamiquement dans l'administration à l'aide des tables
** `fsb2_profil_fields` et `fsb2_users_contact`
** Cette page permet aussi d'entrer les adresses de notifications
*/
class Page_user_contact extends Fsb_model
{
	// Variable d'erreurs
	public $errstr = array();
	
	// Données de contact du membre
	public $user_contact = array();

	// Données récupérées dans le formulaire
	public $post_data = array();

	/*
	** Constructeur
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

	/*
	** Génère la page de formulaire permettant au membre de remplir ses
	** données de contact
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
		
		// Champs contacts créés par l'administrateur
		Profil_fields_forum::form(PROFIL_FIELDS_CONTACT, 'contact', Fsb::$session->id());
	}

	/*
	** Vérifie la validité des données du formulaire en fonction des expressions régulières définies
	** pour les champs de contact
	*/
	public function check_form()
	{
		Profil_fields_forum::validate(PROFIL_FIELDS_CONTACT, 'contact', $this->errstr, Fsb::$session->id());
	}

	/*
	** Traite et soumet le formulaire
	*/
	public function submit_form()
	{
		Display::message('user_profil_submit', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=contact', 'forum_profil');
	}
}

/* EOF */