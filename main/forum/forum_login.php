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
 * Page de connexion du membre
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
	 * Contient les erreurs
	 *
	 * @var array
	 */
	public $errstr = array();
	
	/**
	 * Contient les donnees envoyees par formulaire
	 *
	 * @var array
	 */
	public $data = array();
	
	/**
	 * Donnees de connexion
	 *
	 * @var array
	 */
	public $login_data = array();

	/**
	 * Connexion administration ?
	 *
	 * @var bool
	 */
	public $adm_log = false;

	/**
	 * Utilisation du chiffrage RSA ?
	 *
	 * @var bool
	 */
	public $use_rsa = false;
	
	/**
	 * Délai max d'utilisation de l'ancienne clef RSA
	 *
	 * @var int
	 */
	public $rsa_old_key_ttl = 1800;
	
	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		// Pas de redirection automatique sur cette page
		$this->frame_get_url = false;

		$this->data = array(
			'auto_connexion' =>	intval(Http::request('auto_connexion', 'post')),
			'u_login' =>		trim(Http::request('u_login', 'post')),
			'u_login_rsa' =>	trim(Http::request('u_login_rsa', 'post')),
			'u_password' =>		trim(Http::request('u_password', 'post')),
			'u_password_rsa' =>	trim(Http::request('u_password_rsa', 'post')),
			'u_hidden' =>		trim(Http::request('u_hidden', 'post')),
		);

		// Connexion a l'administration ?
		$this->adm_log = (Http::request('adm_log')) ? true : false;

		// Chiffrage RSA ?
		$this->use_rsa = (Fsb::$mods->is_active('rsa') && Rsa::can_use()) ? true : false;

		if (Http::request('submit', 'post'))
		{
			$this->check_form();
		}
		else if (Http::request('submit_forgot', 'post'))
		{
			$this->send_new_password();
		}

		if (Http::request('activate'))
		{
			$this->activate_password();
		}
		else if ($confirm = Http::request('confirm'))
		{
			$this->confirm_account($confirm);
		}
		else if ($id = Http::request('confirm_account'))
		{
			$this->confirm_admin($id);
		}
		else if (Http::request('forgot'))
		{
			$this->forgot_form();
		}
		else
		{
			$this->login_form();
		}
	}
	
	/**
	 * Affiche le formulaire de connexion
	 *
	 */
	public function login_form()
	{
		Fsb::$tpl->set_file('forum/forum_login.html');

		// Si on est deja connecte, l'identification est inutile
		if (Fsb::$session->is_logged() && !$this->adm_log)
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		if (count($this->errstr))
		{
			Fsb::$tpl->set_switch('error');
		}

		if ($this->use_rsa)
		{
			$rsa = new Rsa();
			$rsa->public_key = Rsa_key::from_string(Fsb::$cfg->get('rsa_public_key'));
			if (is_null($rsa->public_key))
			{
				$rsa->regenerate_keys();
			}

			Fsb::$tpl->set_switch('use_rsa');
			Fsb::$tpl->set_vars(array(
				'RSA_MOD' =>	$rsa->public_key->_get('mod'),
				'RSA_EXP' =>	$rsa->public_key->_get('exp'),
				'RSA_LAST_REGEN' =>	Fsb::$cfg->get('rsa_last_regen'),
			));
		}

		Fsb::$tpl->set_vars(array(
			'L_LOGIN_CONNEXION' =>	($this->adm_log) ? Fsb::$session->lang('login_connexion_admin') : Fsb::$session->lang('login_connexion'),
			'CONTENT' =>			Html::make_errstr($this->errstr),
			'LOGIN' =>				htmlspecialchars($this->data['u_login']),
			'PASSWORD' =>			htmlspecialchars($this->data['u_password']),
			'LOGIN_AUTO' =>			($this->data['auto_connexion']) ? 'checked="checked"' : '',
			'LOGIN_VISIBILITY' =>	($this->data['u_hidden']) ? 'checked="checked"' : '',
			'ADM_LOG' =>			$this->adm_log,
			'HIDDEN' =>				Html::hidden('adm_log', $this->adm_log),
			
			'U_ACTION' =>			sid(ROOT . 'index.' . PHPEXT . '?' . htmlspecialchars($_SERVER['QUERY_STRING'])),
			'U_FORGOT_PASSWORD' =>	sid(ROOT . 'index.' . PHPEXT . '?p=login&amp;forgot=true'),
		));
	}
	
	/**
	 * Verifie les donnees envoyees par le formulaire
	 *
	 */
	public function check_form()
	{
		// En cas de chiffrage RSA, on decrypte les informations
		if ($this->use_rsa && Http::request('hidden_rsa', 'post'))
		{
			$rsa = new Rsa();
			
			$form_rsa_last_regen = Http::request('hidden_rsa_last_regen');
			
			//Si la clé a changé depuis, on utilise l'ancienne
			if($form_rsa_last_regen != Fsb::$cfg->get('rsa_last_regen') && ($form_rsa_last_regen + $this->rsa_old_key_ttl > CURRENT_TIME))
			{
				$rsa->private_key = Rsa_key::from_string(Fsb::$cfg->get('rsa_old_private_key'));
			}
			else
			{
				$rsa->private_key = Rsa_key::from_string(Fsb::$cfg->get('rsa_private_key'));
			}

			if (is_null($rsa->private_key))
			{
				$rsa->regenerate_keys();
			}

			$this->data['u_login'] = $rsa->decrypt($this->data['u_login_rsa']);
			$this->data['u_password'] = $rsa->decrypt($this->data['u_password_rsa']);
		}

		if (empty($this->data['u_login']) || empty($this->data['u_password']))
		{
			$this->errstr[] = Fsb::$session->lang('login_need_data');
		}
		else
		{
			// $result vaut false si tout s'est bien passe, sinon il vaut la chaine de caractere de l'erreur
			if ($this->adm_log)
			{
				$result = Fsb::$session->log_admin($this->data['u_login'], $this->data['u_password']);
			}
			else
			{
				$result = Fsb::$session->log_user($this->data['u_login'], $this->data['u_password'], $this->data['u_hidden'], $this->data['auto_connexion']);
			}

			if ($result)
			{
				// En cas de chiffrage RSA, on ne renvoie pas le mot de passe en clair
				if ($this->use_rsa && Http::request('hidden_rsa', 'post'))
				{
					$this->data['u_password'] = '';
				}
				$this->errstr[] = $result;
			}
			else
			{
				if ($this->adm_log)
				{
					Http::redirect(ROOT . 'admin/index.' . PHPEXT);
				}
				else
				{
					Log::user(Fsb::$session->id(), 'login');
					Http::redirect_to(Http::request('redirect'));
				}
			}
		}
	}

	/**
	 * Affiche le formulaire permettant de recevoir un nouveau mot de passe par Email
	 *
	 */
	public function forgot_form()
	{
		Fsb::$tpl->set_file('forum/forum_login_forgot.html');
	}

	/**
	 * Envoie un nouveau mot de passe, a activer par Email
	 *
	 */
	public function send_new_password()
	{
		$email = trim(Http::request('forgot_email', 'post'));

		if (!$email)
		{
			Display::message('login_forgot_fields');
		}

		// On verifie si l'adresse existe bien
		$sql = 'SELECT u.u_id, u.u_nickname, u.u_language, up.*
				FROM ' . SQL_PREFIX . 'users u
				INNER JOIN ' . SQL_PREFIX . 'users_password up
					ON u.u_id = up.u_id
				WHERE u.u_email = \'' . Fsb::$db->escape($email) . '\'';
		if (!$row = Fsb::$db->request($sql))
		{
			Display::message('login_forgot_not_exists');
		}

		// On envoie par Email un nouveau mot de passe a activer
		$new_password = Password::generate(10);

		$mail = new Notify_mail();
		$mail->AddBCC($email);
		$mail->Subject = Fsb::$session->lang('subject_new_password');
		$mail->set_file(ROOT . 'lang/' . $row['u_language'] . '/mail/new_password.txt');
		$mail->set_vars(array(
			'NICKNAME' =>		htmlspecialchars($row['u_nickname']),
			'LOGIN' =>			htmlspecialchars($row['u_login']),
			'NEW_PASSWORD' =>	$new_password,

			'U_NEW_PASSWORD' =>	Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=login&activate= true &id=' . $row['u_id'] . '&new_password=' . Password::hash($new_password, $row['u_algorithm'], $row['u_use_salt']) . '&hash_old_password=' . $row['u_password'],
		));
		$mail->Send();
		$mail->SmtpClose();
		unset($mail);

		Display::message('login_submit_forgot', ROOT . 'index.' . PHPEXT, 'forum_index');
	}

	/**
	 * Active le mot de passe
	 *
	 */
	public function activate_password()
	{
		$id =					intval(Http::request('id'));
		$new_password =			trim(Http::request('new_password'));
		$hash_old_password =	trim(Http::request('hash_old_password'));

		// Desactivation du lien sur la page de connexion
		$this->frame_get_url = false;

		// On verifie les parametres
		if (!$id || !$hash_old_password)
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		// On verifie que l'ID et l'ancien hash du password existent bien
		$sql = 'SELECT u_id
				FROM ' . SQL_PREFIX . 'users_password
				WHERE u_id = ' . $id . '
					AND u_password = \'' . Fsb::$db->escape($hash_old_password) . '\'';
		$result = Fsb::$db->query($sql);
		if (!Fsb::$db->row($result))
		{
			Display::message('login_bad_password');
		}
		Fsb::$db->free($result);

		// Mise a jour du mot de passe
		Fsb::$db->update('users_password', array(
			'u_password' =>		$new_password,
		), 'WHERE u_id = ' . $id);

		Display::message('login_new_password', ROOT . 'index.' . PHPEXT, 'forum_index');
	}

	/**
	 * Confirmation d'un compte en cas d'inscription avec confirmation par Email
	 *
	 * @param string $confirm Hash de confirmation
	 */
	public function confirm_account($confirm)
	{
		$id = intval(Http::request('id'));
		$sql = 'SELECT u_id, u_nickname, u_email, u_register_ip
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id = ' . $id . '
					AND u_activated = 0
					AND u_confirm_hash = \'' . Fsb::$db->escape($confirm) . '\'';
		$result = Fsb::$db->query($sql);
		if ($userdata = Fsb::$db->row($result))
		{
			Fsb::$db->update('users', array(
				'u_activated' =>	(Fsb::$cfg->get('register_type') == 'both') ? false : true,
				'u_confirm_hash' =>	(Fsb::$cfg->get('register_type') == 'both') ? '.' : '',
			), 'WHERE u_id = ' . $id);

			// On envoie un mail a l'utilisateur signalant que son compte est en attente d'activation par un administrateur
			if (Fsb::$cfg->get('register_type') == 'both')
			{
				User::confirm_administrator($userdata['u_id'], $userdata['u_nickname'], $userdata['u_email'], $userdata['u_register_ip']);
			}

			Log::user($userdata['u_id'], 'confirm');
			Display::message('login_is_activated' . ((Fsb::$cfg->get('register_type') == 'both') ? '_both' : ''), 'index.' . PHPEXT, 'forum_index');
		}
		else
		{
			Http::redirect('index.' . PHPEXT);
		}
	}

	/**
	 * Confirmation de l'inscription pour les administrateurs
	 *
	 * @param int $id ID du membre a confirmer
	 */
	public function confirm_admin($id)
	{
		if (!Fsb::$session->is_authorized('confirm_account') || !User::confirm_account($id))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		Log::user($id, 'confirm_admin');
		Display::message('login_confirm_account', 'index.' . PHPEXT, 'forum_index');
	}
}


/* EOF */
