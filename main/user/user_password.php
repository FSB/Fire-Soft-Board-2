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

// Necessite le fichier de langue lg_forum_register.php pour le test du mot de passe
Fsb::$session->load_lang('lg_forum_register');

/**
 * Module d'utilisateur permettant au membre de modifier son login et son mot de passe
 * Notez qu'il faut connaitre obligatoirement le login et le mot de passe pour avoir le droit de
 * changer un des deux (ou meme les deux).
 */
class Page_user_password extends Fsb_model
{
	/**
	 * Ancien pseudonyme
	 *
	 * @var string
	 */
	public $old_login;
	
	/**
	 * Ancien mot de passe
	 *
	 * @var string
	 */
	public $old_password;
	
	/**
	 * Nouveau Pseudonyme
	 *
	 * @var string
	 */
	public $new_login;
	
	/**
	 * Nouveau mot de passe
	 *
	 * @var string
	 */
	public $new_password;
	
	/**
	 * Confirmation du mot de passe
	 *
	 * @var string
	 */
	public $new_password_confirm;
	
	/**
	 * Nouvelle adresse Email
	 *
	 * @var string
	 */
	public $new_email;

	/**
	 * Donnée du mot de passe
	 *
	 * @var string
	 */
	public $pwd_data;

	/**
	 * Mise a jour de l'adresse Email ?
	 *
	 * @var bool
	 */
	public $update_email = false;
	
	/**
	 * Peut-yon changer l'adresse Email ?
	 *
	 * @var bool
	 */
	public $can_update_email = false;
	
	/**
	 * Erreurs
	 *
	 * @var array
	 */
	public $errstr = array();
	
	/**
	 * Constructeur
	 */
	public function __construct()
	{
		$this->old_login =				trim(Http::request('old_login', 'post'));
		$this->old_password =			trim(Http::request('old_password', 'post'));
		$this->new_login =				trim(Http::request('new_login', 'post'));
		$this->new_password =			trim(Http::request('new_password', 'post'));
		$this->new_password_confirm =	trim(Http::request('new_password_confirm', 'post'));
		$this->new_email =				trim(Http::request('new_email', 'post'));

		if ((Fsb::$cfg->get('register_type') == 'confirm' || Fsb::$cfg->get('register_type') == 'admin' || Fsb::$cfg->get('register_type') == 'both') && Fsb::$session->auth() < MODOSUP)
		{
			$this->can_update_email = true;
		}

		if (Http::request('test_password', 'post'))
		{
			$this->test_password();
		}
		else if (Http::request('submit', 'post'))
		{
			$this->check_form();
			if (!count($this->errstr))
			{
				$this->submit_form();
			}
		}
		$this->password_form();
	}
	
	/**
	 * Affiche le formulaire de modification de mot de passe
	 */
	public function password_form()
	{		
		if (count($this->errstr))
		{
			Fsb::$tpl->set_switch('error');
		}

		if ($this->can_update_email)
		{
			Fsb::$tpl->set_switch('email_explain');
		}
		
		Fsb::$tpl->set_file('user/user_password.html');
		Fsb::$tpl->set_vars(array(
			'CONTENT' =>				Html::make_errstr($this->errstr),
			'OLD_LOGIN' =>				$this->old_login,
			'OLD_PASSWORD' =>			$this->old_password,
			'NEW_LOGIN' =>				$this->new_login,
			'NEW_PASSWORD' =>			$this->new_password,
			'NEW_PASSWORD_CONFIRM' =>	$this->new_password_confirm,
			'NEW_EMAIL' =>				($this->new_email) ? $this->new_email : Fsb::$session->data['u_email'],
		));
	}
	
	/**
	 * Verifie les donnees envoyees par le formulaire
	 */
	public function check_form()
	{
		$this->update_email = ($this->new_email && strtolower($this->new_email) != strtolower(Fsb::$session->data['u_email'])) ? true : false;

		// Il faut entrer son login et son mot de passe actuel
		if (!$this->old_login)
		{
			$this->errstr[] = Fsb::$session->lang('user_password_need_login');
		}
		
		if (!$this->old_password)
		{
			$this->errstr[] = Fsb::$session->lang('user_password_need_password');
		}
		
		// On verifie si la clef login / mot de passe est correct
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'users_password
				WHERE u_id = ' . Fsb::$session->id();
		$this->pwd_data = Fsb::$db->request($sql);

		if (strtolower($this->old_login) !== strtolower($this->pwd_data['u_login']) || Password::hash($this->old_password, $this->pwd_data['u_algorithm'], $this->pwd_data['u_use_salt']) !== $this->pwd_data['u_password'])
		{
			$this->errstr[] = Fsb::$session->lang('user_password_bad_login');
		}
		
		// On verifie si les deux nouveaux mots de passe sont les meme
		if (($this->new_password || $this->new_password_confirm) && $this->new_password !== $this->new_password_confirm)
		{
			$this->errstr[] = Fsb::$session->lang('user_password_dif');
		}

		// Validite de l'adresse Email
		if ($this->update_email && !User::email_valid($this->new_email))
		{
			$this->errstr[] = Fsb::$session->lang('user_email_format');
		}

		// Existance du login
		if ($this->new_login && strtolower($this->new_login) != strtolower($this->pwd_data['u_login']) && User::login_exists($this->new_login))
		{
			$this->errstr[] = Fsb::$session->lang('user_login_exists');
		}

		// Existance de l'adresse Email
		if ($this->update_email && User::email_exists($this->new_email))
		{
			$this->errstr[] = Fsb::$session->lang('user_email_exists');
		}
	}
	
	/**
	 * Soumet les donnees envoyees par le formulaire
	 */
	public function submit_form()
	{		
		// Nouveau login
		$update_array = array();
		$update_pwd_array = array();
		if ($this->new_login)
		{
			$update_pwd_array['u_login'] = $this->new_login;
		}
		
		// Nouveau mot de passe
		if ($this->new_password && $this->new_password_confirm)
		{
			$update_pwd_array['u_password'] = Password::hash($this->new_password, $this->pwd_data['u_algorithm'], $this->pwd_data['u_use_salt']);
		}

		// Si une nouvelle adresse Email est entree, et que la configuration necessite une validation, on desactive le compte et on envoie
		// un Email avec un nouveau code de validation.
		$logout = false;
		if ($this->update_email && $this->can_update_email)
		{
			$confirm_hash = md5(rand(0, time()));

			$mail = new Notify_mail();
			$mail->AddAddress($this->new_email);
			$mail->Subject = Fsb::$session->lang('user_email_change');
			$mail->set_file(ROOT . 'lang/' . Fsb::$session->data['u_language'] . '/mail/update_email.txt');
			$mail->set_vars(array(
				'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
				'NICKNAME' =>		Fsb::$session->data['u_nickname'],
				'U_CONFIRM' =>		Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=login&id=' . Fsb::$session->id() . '&confirm=' . urlencode($confirm_hash),
				'U_FORUM' =>		Fsb::$cfg->get('fsb_path'),
			));
			$result = $mail->Send();
			$mail->SmtpClose();

			// On ne fait le controle de validation que si l'Email a pu etre envoye
			if ($result)
			{
				$update_array['u_activated'] =		false;
				$update_array['u_confirm_hash'] =	$confirm_hash;
				$update_array['u_email'] =			$this->new_email;
				$logout =							true;

				Log::user(Fsb::$session->id(), 'update_email');
			}
		}
		else if ($this->update_email)
		{
			$update_array['u_email'] = $this->new_email;
		}

		// Mise a jour
		if ($update_array)
		{
			Fsb::$db->update('users', $update_array, 'WHERE u_id = ' . Fsb::$session->id());
		}

		if ($update_pwd_array)
		{
			// Regeneration de la clef d'auto connexion
			$prefix = '';
			foreach ($update_pwd_array AS $v)
			{
				$prefix .= $v;
			}
			$update_pwd_array['u_autologin_key'] = Password::generate_autologin_key($v . Fsb::$session->id());

			Fsb::$db->update('users_password', $update_pwd_array, 'WHERE u_id = ' . Fsb::$session->id());
		}

		Log::user(Fsb::$session->id(), 'update_password_info');

		// Deconnexion ?
		if ($logout)
		{
			Fsb::$session->logout();
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		Display::message('user_profil_submit', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=password', 'forum_profil');
	}

	/**
	 * Evalue la robustesse du mot de passe
	 */
	public function test_password()
	{
		if (!$this->new_password)
		{
			return ;
		}

		$password = new Password();
		$result = $password->grade($this->new_password);
		
		$result_errstr = array();
		if ($result >= 3)
		{
			$result_str = Fsb::$session->lang('register_test_high');
		}
		else
		{
			// Mot de passe faible (ou moyennement faible), on evalue les besoins du mot de passe en fonction
			// des parametres calcule par la classe Password() (taille, caracteres speciaux, moyenne).
			$result_str = ($result <= 1) ? Fsb::$session->lang('register_test_low') : Fsb::$session->lang('register_test_middle');
			if ($password->grade_data['len'] < 3)
			{
				$result_errstr[] = Fsb::$session->lang('register_test_len');
			}

			if ($password->grade_data['char_type'] < 3)
			{
				$result_errstr[] = Fsb::$session->lang('register_test_type');
			}

			if ($password->grade_data['average'] < 3)
			{
				$result_errstr[] = Fsb::$session->lang('register_test_average');
			}
		}

		Fsb::$tpl->set_switch('test_password');
		Fsb::$tpl->set_vars(array(
			'PASSWORD_RESULT' =>	$result_str . Html::make_errstr($result_errstr),
		));
	}
}

/* EOF */