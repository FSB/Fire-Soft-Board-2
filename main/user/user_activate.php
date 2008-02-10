<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/user/user_activate.php
** | Begin :	27/09/2005
** | Last :		06/09/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

// On affiche le module
$show_this_module = TRUE;

/*
** Module d'utilisateur permettant au membre d'activer / désactiver certaines donées du forum (avatar, signature,
** images, E-mails, etc ...). Tous les champs sont précédés du prefixe u_activate_ y compris dans la table
** fsb2_users.
**
** Rapide information, sur cette page on utilise soit des valeurs boolean quand il n y a que des choix yes / no
** comme par exemple TRUE ou FALSE, mais lorsqu'il y a davantage de choix on utilise des masques binaires, par exemple :
**	$var = 2 | 4;
**	if ($var & 2) echo 'ok2';
**	if ($var & 4) echo 'ok4';
**	if ($var & 8) echo 'ok8';
** ne va afficher que "ok2ok4", si on veut afficher uniquement "ok2ok8" par exemple on fait :
**	$var = 2 | 8;
*/
class Page_user_activate extends Fsb_model
{
	// Tableau contenant toutes les données utiles pour les activations
	public $data = array();
	
	/*
	** Constructeur
	*/
	public function __construct()
	{	
		// On déclare tous les champs d'activation
		$this->data = array(
			'email' => array(
				'br' =>		TRUE,
				'type' =>	'radio',
				'value' =>	'binary', 
				'args' =>	array(
					2 =>	Fsb::$session->lang('user_ac_email_extern'),
					4 =>	Fsb::$session->lang('user_ac_email_intern'),
					8 =>	Fsb::$session->lang('user_ac_email_hide')
				)
			),

			'auto_notification' => array(
				'br' =>		TRUE,
				'type' =>	'radio',
				'value' =>	'boolean',
				'args' =>	array(
					0 =>										Fsb::$session->lang('user_ac_notification_none'),
					NOTIFICATION_EMAIL =>						Fsb::$session->lang('user_ac_notification_none_email'),
					NOTIFICATION_AUTO =>						Fsb::$session->lang('user_ac_notification_normal'),
					NOTIFICATION_EMAIL|NOTIFICATION_AUTO =>		Fsb::$session->lang('user_ac_notification_email')
				)
			),

			'mp_notification' => array(
				'br' =>		FALSE,
				'type' =>	'radio',
				'value' => 'boolean',
				'args' =>	array(
					1 =>	Fsb::$session->lang('yes'),
					0 =>	Fsb::$session->lang('no')
				)
			),

			'hidden' =>	array(
				'br' =>		FALSE,
				'type' =>	'radio',
				'value' =>	'boolean',
				'args' =>	array(
					1 =>	Fsb::$session->lang('yes'),
					0 =>	Fsb::$session->lang('no')
				)
			),

			'avatar' =>	array(
				'br' =>		FALSE,
				'type' =>	'radio',
				'value' => 'boolean',
				'args' =>	array(
					1 =>	Fsb::$session->lang('yes'),
					0 =>	Fsb::$session->lang('no')
				)
			),

			'sig' => array(
				'br' =>		FALSE,
				'type' =>	'radio',
				'value' => 'boolean',
				'args' =>	array(
					1 =>	Fsb::$session->lang('yes'),
					0 =>	Fsb::$session->lang('no')
				)
			),

			'fscode' => array(
				'br' => TRUE,
				'type' => 'checkbox',
				'value' => 'binary',
				'args' => array(
					2 => Fsb::$session->lang('user_ac_posts'),
					4 => Fsb::$session->lang('user_ac_sig')
				)
			),

			'img' => array(
				'br' =>		TRUE,
				'type' =>	'checkbox',
				'value' =>	'binary',
				'args' =>	array(
					2 =>	Fsb::$session->lang('user_ac_posts'),
					4 =>	Fsb::$session->lang('user_ac_sig')
				)
			),

			'wysiwyg' => array(
				'br' =>		FALSE,
				'type' =>	'radio',
				'value' =>	'boolean',
				'args' =>	array(
					1 =>	Fsb::$session->lang('yes'),
					0 => Fsb::$session->lang('no')
				)
			),

			'ajax' => array(
				'br' =>		FALSE,
				'type' =>	'radio',
				'value' =>	'boolean',
				'args' =>	array(
					1 =>	Fsb::$session->lang('yes'),
					0 =>	Fsb::$session->lang('no')
				)
			),

			'redirection' => array(
				'br' =>		TRUE,
				'type' =>	'radio',
				'value' =>	'binary',
				'args' =>	array(
					2 =>	Fsb::$session->lang('user_ac_redirection_null'), 
					4 =>	Fsb::$session->lang('user_ac_redirection_header'), 
					8 =>	Fsb::$session->lang('user_ac_redirection_meta')
				)
			),

			'userlist' =>	array(
				'br' =>		TRUE,
				'type' =>	'radio',
				'value' =>	'boolean',
				'args' =>	array(
					USERLIST_SIMPLE =>		Fsb::$session->lang('user_ac_userlist_simple'),
					USERLIST_ADVANCED =>	Fsb::$session->lang('user_ac_userlist_advanced')
				)
			),
		);

		// Editeur WYSIWYG désactivé ?
		if (!Fsb::$mods->is_active('wysiwyg'))
		{
			unset($this->data['wysiwyg']);
		}

		// Connexion invisible autorisée ?
		if (!Fsb::$session->is_authorized('log_hidden'))
		{
			unset($this->data['hidden']);
		}

		if (Http::request('submit', 'post'))
		{
			$this->submit_form();
		}
		$this->activate_form();
	}
	
	/*
	** Affiche le formulaire d'activation
	*/
	public function activate_form()
	{		
		Fsb::$tpl->set_file('user/user_activate.html');
		Fsb::$tpl->set_vars(array(
			'U_ACTION' =>	sid('index.' . PHPEXT . '?p=profile&amp;module=activate'),
		));
		
		foreach ($this->data AS $key => $value)
		{
			Fsb::$tpl->set_blocks('activate', array(
				'NAME' =>		'u_activate_' . $key,
				'LANG' =>		(Fsb::$session->lang('user_activate_' . $key)) ? Fsb::$session->lang('user_activate_' . $key) : $key,
				'EXPLAIN' =>	(Fsb::$session->lang('user_activate_' . $key . '_explain')) ? Fsb::$session->lang('user_activate_' . $key . '_explain') : NULL,
				'BR' =>			$value['br'],
				'TYPE' =>		$value['type'],
			));
			
			foreach ($value['args'] AS $form_value => $form_lang)
			{
				Fsb::$tpl->set_blocks('activate.form', array(
					'VALUE' =>		$form_value,
					'LANG' =>		$form_lang,
					'CHECKED' =>	(($value['value'] == 'boolean' && Fsb::$session->data['u_activate_' . $key] == $form_value) || ($value['value'] == 'binary' && (Fsb::$session->data['u_activate_' . $key] & $form_value))) ? 'checked="checked"' : '',
				));
			}
		}
	}
	
	/*
	** Soumet le formulaire
	*/
	public function submit_form()
	{
		$update = array();
		foreach ($this->data AS $key => $value)
		{
			$tmp = Http::request('u_activate_' . $key, 'post');
			if ($value['type'] == 'checkbox')
			{
				if (!is_array($tmp))
				{
					$tmp = array();
				}
				
				$set = 0;
				foreach ($tmp AS $value_set)
				{
					$set |= intval($value_set);
				}
			}
			else
			{
				$set = intval($tmp);
			}
			
			$update['u_activate_' . $key] = $set;
		}
		
		Fsb::$db->update('users', $update, 'WHERE u_id = ' . Fsb::$session->id());
		Log::user(Fsb::$session->id(), 'update_activate');
		Display::message('user_profil_submit', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=activate', 'forum_profil');
	}
}

/* EOF */