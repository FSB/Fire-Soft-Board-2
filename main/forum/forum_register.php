<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/forum/forum_register.php
** | Begin :	01/06/2005
** | Last :		25/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Page d'inscription au forum
*/
class Fsb_frame_child extends Fsb_frame
{
	// Paramètres d'affichage de la page (barre de navigation, boite de stats)
	public $_show_page_header_nav = TRUE;
	public $_show_page_footer_nav = FALSE;
	public $_show_page_stats = FALSE;

	// Module de la page
	public $module;

	// Contient les données passées en formulaire
	public $data = array();
	
	// Contient les champs des données à traiter
	public $post_data = array();

	// Contient les informations personelles
	public $fields = array();
	public $insert_fields = array();

	// Contient la liste des erreurs
	public $errstr = array();
	
	// Mode de l'image de confirmation visuelle
	public $img_mode = 'generate';

	// Peut utiliser la confirmation visuelle
	public $use_visual_confirmation = FALSE;

	// Utilisation du chiffrage RSA ?
	public $use_rsa = FALSE;

	// Longueur max du pseudonyme
	public $max_nickname_length = 20;
	
	/*
	** Constructeur
	*/
	public function main()
	{
		// Chiffrage RSA ?
		$this->use_rsa = (Fsb::$mods->is_active('rsa') && Rsa::can_use()) ? TRUE : FALSE;

		// On vérifie si l'extension GD2 est utilisable pour la confirmation visuelle
		if (Fsb::$mods->is_active('visual_confirmation'))
		{
			$this->use_visual_confirmation = TRUE;
		}

		// Les membres connectés ne sont pas admis sur cette page
		if (Fsb::$session->is_logged())
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		// Inscriptions désactivées
		if (Fsb::$cfg->get('register_type') == 'disabled')
		{
			Display::message('register_are_disabled');
		}

		// Interdiction d'accéder à la page à cause d'un nombre trop grand d'essai ?
		if (Fsb::$session->data['s_visual_try'] > 5)
		{
			Display::message('register_too_much_try');
		}

		// Gestion des onglets
		$this->check_register_module();

		$this->post_data();
		if (Http::request('submit', 'post'))
		{
			$this->check_form();
			if (!count($this->errstr))
			{
				$this->submit_user();
			}
		}
		else if (Http::request('submit_fsbcard', 'post'))
		{
			$this->check_fsbcard_form();
			if (!count($this->errstr))
			{
				$this->import_user();
			}
		}
		else if (Http::request('test_password', 'post'))
		{
			$this->test_password();
		}
		else if (Http::request('generate_password', 'post'))
		{
			$this->generate_password();
		}

		if ($this->module == 'fsbcard')
		{
			$this->register_fsbcard();
		}
		else
		{
			$this->register_page();
		}
	}

	/*
	** Vérifie le module d'enregistrement chargé
	*/
	public function check_register_module()
	{
		// Récupération du module
		$register_array = array('new', 'fsbcard');
		$this->module = Http::request('module');
		if (!$this->module || !in_array($this->module, $register_array ))
		{
			$this->module = 'new';
		}

		// Affichage des onglets
		foreach ($register_array AS $module)
		{
			Fsb::$tpl->set_blocks('module', array(
				'IS_SELECT' =>	($this->module == $module) ? TRUE : FALSE,
				'URL' =>		sid(ROOT . 'index.' . PHPEXT . '?p=register&amp;module=' . $module),
				'NAME' =>		Fsb::$session->lang('register_menu_' . $module),
			));
		}

		Fsb::$tpl->set_vars(array(
			'MENU_HEADER_TITLE' =>	Fsb::$session->lang('register_data'),
		));
	}
	
	/*
	** Ajoute dans le tableau $this->data les variables passées en formulaires, traitées et
	** sécurisées.
	*/
	public function post_data()
	{
		$this->post_data = array(
			array('str' => 'accept_rules', 'insert' => FALSE),
			array('str' => 'u_login', 'insert' => TRUE),
			array('str' => 'u_login_rsa', 'insert' => FALSE),
			array('str' => 'u_nickname', 'insert' => TRUE),
			array('str' => 'u_password', 'insert' => TRUE),
			array('str' => 'u_password_rsa', 'insert' => FALSE),
			array('str' => 'u_password_confirm', 'insert' => FALSE),
			array('str' => 'u_password_confirm_rsa', 'insert' => FALSE),
			array('str' => 'u_email', 'insert' => TRUE),
			array('str' => 'u_email_rsa', 'insert' => FALSE),
			array('str' => 'u_visual_code', 'insert' => FALSE),
		);
		
		foreach ($this->post_data AS $value)
		{
			$this->data[$value['str']] = trim(Http::request($value['str'], 'post'));
		}

		// En cas de chiffrage RSA, on decrypte les informations
		if ($this->use_rsa && Http::request('hidden_rsa', 'post'))
		{
			$rsa = new Rsa();
			$rsa->private_key = Rsa_key::from_string(Fsb::$cfg->get('rsa_private_key'));
			if ($rsa->private_key === NULL)
			{
				$rsa->regenerate_keys();
			}

			$this->data['u_login'] =			$rsa->decrypt($this->data['u_login_rsa']);
			$this->data['u_password'] =			$rsa->decrypt($this->data['u_password_rsa']);
			$this->data['u_password_confirm'] =	$rsa->decrypt($this->data['u_password_confirm_rsa']);
			$this->data['u_email'] =			$rsa->decrypt($this->data['u_email_rsa']);
		}
	}
	
	/*
	** Affiche le formulaire d'enregistrement
	*/
	public function register_page()
	{
		Fsb::$tpl->set_file('forum/forum_register.html');
		Fsb::$tpl->set_switch('form_new');

		if (count($this->errstr))
		{
			Fsb::$tpl->set_switch('error');
			Fsb::$tpl->set_vars(array(
				'CONTENT' =>	Html::make_errstr($this->errstr),
			));
		}
		else
		{
			$this->img_mode = (!empty(Fsb::$session->data['s_visual_code'])) ? 'keep' : 'generate';
		}

		if ($this->use_rsa)
		{
			$rsa = new Rsa();
			$rsa->public_key = Rsa_key::from_string(Fsb::$cfg->get('rsa_public_key'));
			if ($rsa->public_key === NULL)
			{
				$rsa->regenerate_keys();
			}

			Fsb::$tpl->set_switch('use_rsa');
			Fsb::$tpl->set_vars(array(
				'RSA_MOD' =>	$rsa->public_key->_get('mod'),
				'RSA_EXP' =>	$rsa->public_key->_get('exp'),
			));
		}

		if ($this->use_visual_confirmation)
		{
			Fsb::$tpl->set_switch('can_use_visual_confirmation');
		}

		$uniqid = md5(rand(1, time()));
		Fsb::$tpl->set_vars( array(
			'U_ACTION' =>			sid(ROOT . 'index.' . PHPEXT .'?p=register#test'),
			
			'ACCEPT_RULES' =>		(($this->data['accept_rules']) ? 'checked="checked"' : ''),
			'LOGIN' =>				$this->data['u_login'],
			'NICKNAME' =>			$this->data['u_nickname'],
			'PASSWORD' =>			$this->data['u_password'],
			'PASSWORD_CONFIRM' =>	$this->data['u_password_confirm'],
			'EMAIL' =>				$this->data['u_email'],
			'CODE_CONFIRMATION' =>	($this->img_mode == 'keep') ? $this->data['u_visual_code'] : '',
			
			// Merci à Fladnag (http://www.developpez.net/forums/profile.php?mode=viewprofile&u=33459) pour son aide sur
			// le cache des images.
			'CONFIRMATION_IMG' =>	sid(ROOT . 'main/visual_confirmation.' . PHPEXT . '?mode=' . $this->img_mode . '&amp;frame=true&amp;uniqid=' . $uniqid),
			'REFRESH_IMG' =>		sid(ROOT . 'main/visual_confirmation.' . PHPEXT . '?mode=refresh&amp;frame=true&amp;uniqid=' . $uniqid),
		));

		// Champs personals créés par l'administrateur
		Profil_fields_forum::form(PROFIL_FIELDS_PERSONAL, 'personal', Fsb::$session->id(), TRUE);

		Fsb::$tpl->set_switch('form');
	}

	/*
	** Evalue la robustesse du mot de passe
	*/
	public function test_password()
	{
		$password = new Password();
		$result = $password->grade($this->data['u_password']);
		
		$result_errstr = array();
		if ($result >= 3)
		{
			$result_str = Fsb::$session->lang('register_test_high');
		}
		else
		{
			// Mot de passe faible (ou moyennement faible), on évalue les besoins du mot de passe en fonction
			// des paramètres calculé par la classe Password() (taille, caractères spéciaux, moyenne).
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

	/*
	** Génère un mot de passe aléatoirement
	*/
	public function generate_password()
	{
		$random_password = Password::generate(10);

		Fsb::$tpl->set_switch('test_password');
		Fsb::$tpl->set_vars(array(
			'PASSWORD_RESULT' =>	sprintf(Fsb::$session->lang('password_generate_result'), $random_password),
		));
	}
	
	/*
	** Vérifie les données envoyées par le formulaire
	*/
	public function check_form()
	{		
		$this->errstr = array();

		// Règles du forum acceptées ?
		if (!$this->data['accept_rules'])
		{
			$this->errstr[] = Fsb::$session->lang('register_need_accept');
		}

		// Necessite un login
		if (!$this->data['u_login'])
		{
			$this->errstr[] = Fsb::$session->lang('register_need_login');
		}

		// Si le pseudo est vide, on lui donne comme valeur celle du login
		if (empty($this->data['u_nickname']))
		{
			$this->data['u_nickname'] = $this->data['u_login'];
		}
		// Vérification de la taille du pseudonyme
		else if (String::strlen($this->data['u_nickname']) < 3)
		{
			$this->errstr[] = Fsb::$session->lang('register_short_nickname');
		}
		else if (String::strlen($this->data['u_nickname']) > $this->max_nickname_length)
		{
			$this->errstr[] = sprintf(Fsb::$session->lang('register_long_nickname'), $this->max_nickname_length);
		}

		// Pseudonyme valide ?
		if (($valid_nickname = User::nickname_valid($this->data['u_nickname'])) !== TRUE)
		{
			$this->errstr[] = Fsb::$session->lang('nickname_chars_' . $valid_nickname);
		}

		// Necessite l'entrée des deux mots de passe
		if (!$this->data['u_password'] || !$this->data['u_password_confirm'])
		{
			$this->errstr[] = Fsb::$session->lang('register_need_password');
		}

		// Comparaison des mots de passe
		if ($this->data['u_password'] != $this->data['u_password_confirm'])
		{
			$this->errstr[] = Fsb::$session->lang('register_password_dif');
		}

		// Mot de passe différent du login ?
		if ($this->data['u_password'] && strtolower($this->data['u_password']) == strtolower($this->data['u_nickname']))
		{
			$this->errstr[] = Fsb::$session->lang('register_password_diff_nickname');
		}

		// Validité de l'adresse Email
		if (!User::email_valid($this->data['u_email']))
		{
			$this->errstr[] = Fsb::$session->lang('register_email_format');
		}

		// Vérification de l'existance du login
		if (User::login_exists($this->data['u_login']))
		{
			$this->errstr[] = Fsb::$session->lang('register_login_exists');
		}

		// Vérification de l'existance du pseudonyme
		if ($this->data['u_nickname'] && User::nickname_exists($this->data['u_nickname']))
		{
			$this->errstr[] = Fsb::$session->lang('register_nickname_exists');
		}

		// Vérification de l'existance de l'email
		if ($this->data['u_email'] && User::email_exists($this->data['u_email']))
		{
			$this->errstr[] = Fsb::$session->lang('register_email_exists');
		}
		
		// On vérifie si le login ou l'adresse E-mail ont été bannis
		if ($ban_type = Fsb::$session->is_ban(-1, $this->data['u_nickname'], Fsb::$session->ip, $this->data['u_email']))
		{
			$this->errstr[] = Fsb::$session->lang('register_ban_' . $ban_type['type']);
		}
		
		// On vérifie le code de confirmation visuelle
		if ($this->use_visual_confirmation && !check_captcha($this->data['u_visual_code']))
		{
			$this->img_mode = 'generate';
			$this->errstr[] = sprintf(Fsb::$session->lang('register_bad_visual_code'), intval(5 - Fsb::$session->data['s_visual_try']));
		}
		else
		{
			$this->img_mode = 'keep';
		}

		$this->insert_fields = Profil_fields_forum::validate(PROFIL_FIELDS_PERSONAL, 'personal', $this->errstr, NULL, TRUE);
	}
	
	/*
	** Ajoute l'utilisateur fraichement inscrit dans la base de donnée, et tout le tralala qui va
	** avec :=)
	*/
	public function submit_user()
	{
		$last_id = $this->create_user();
		$message = $this->send_mail($last_id);

		Display::message($message . return_to('index.' . PHPEXT, 'forum_index'));
	}

	/*
	** Insertion d'utilisateur
	*/
	public function create_user()
	{
		Fsb::$db->transaction('begin');

		// On créé la requète pour l'enregistrement du membre
		$insert_ary = array();
		foreach ($this->post_data AS $value)
		{
			if ($value['insert'])
			{
				$insert_ary[$value['str']] = $this->data[$value['str']];
			}
		}

		// Création du membre
		$last_id = User::add($insert_ary['u_login'], $insert_ary['u_nickname'], $insert_ary['u_password'], $insert_ary['u_email'], $insert_ary);

		// Insertion dans le profil personel
		if ($this->insert_fields)
		{
			$this->insert_fields['u_id'] = $last_id;
			Fsb::$db->insert('users_personal', $this->insert_fields);
		}
		Fsb::$db->transaction('commit');

		Log::user($last_id, 'register');
		return ($last_id);
	}

	/*
	** Envoie de l'Email d'inscription
	** -----
	** $u_id ::			ID du membre
	*/
	public function send_mail($u_id)
	{
		$mail = new Notify_mail();
		$mail->AddAddress($this->data['u_email']);
		$mail->Subject = sprintf(Fsb::$session->lang('subject_register'), Fsb::$cfg->get('forum_name'));

		switch (Fsb::$cfg->get('register_type'))
		{
			case 'normal' :
				$mail->set_file(ROOT . 'lang/' . Fsb::$cfg->get('default_lang') . '/mail/register.txt');
				$mail->set_vars(array(
					'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
					'LOGIN' =>			$this->data['u_login'],
					'PASSWORD' =>		$this->data['u_password'],
					'U_FORUM' =>		Fsb::$cfg->get('fsb_path'),
				));
				$result = $mail->Send();
				$mail->SmtpClose();
			break;

			case 'both' :
			case 'confirm' :
				$confirm_hash = md5(rand(0, time()));

				$mail->set_file(ROOT . 'lang/' . Fsb::$cfg->get('default_lang') . '/mail/register_confirm.txt');
				$mail->set_vars(array(
					'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
					'LOGIN' =>			$this->data['u_login'],
					'PASSWORD' =>		$this->data['u_password'],
					'U_CONFIRM' =>		Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=login&id=' . $u_id . '&confirm=' . urlencode($confirm_hash),
					'U_FORUM' =>		Fsb::$cfg->get('fsb_path'),
				));
				$result = $mail->Send();
				$mail->SmtpClose();

				// On ne fait le controle de validation que si l'Email a pu être envoyé
				if ($result)
				{
					Fsb::$db->update('users', array(
						'u_activated' =>	FALSE,
						'u_confirm_hash' =>	$confirm_hash,
					), 'WHERE u_id = ' . $u_id);
				}
			break;

			case 'admin' :
				Fsb::$db->update('users', array(
					'u_activated' =>	FALSE,
					'u_confirm_hash' =>	'.',
				), 'WHERE u_id = ' . $u_id);

				unset($mail);
				User::confirm_administrator($u_id, $this->data['u_nickname'], $this->data['u_email'], Fsb::$session->ip);
				$result = TRUE;
			break;
		}

		$message = ($result) ? Fsb::$session->lang('register_ok_' . Fsb::$cfg->get('register_type')) : Fsb::$session->lang('register_ko');
		return ($message);
	}

	/*
	** Formulaire d'import de profil
	*/
	public function register_fsbcard()
	{
		Fsb::$tpl->set_file('forum/forum_register.html');
		Fsb::$tpl->set_switch('form_fsbcard');

		if (count($this->errstr))
		{
			Fsb::$tpl->set_switch('error');
			Fsb::$tpl->set_vars(array(
				'CONTENT' =>	Html::make_errstr($this->errstr),
			));
		}
		else
		{
			$this->img_mode = (!empty(Fsb::$session->data['s_visual_code'])) ? 'keep' : 'generate';
		}

		if ($this->use_visual_confirmation)
		{
			Fsb::$tpl->set_switch('can_use_visual_confirmation');
		}

		$uniqid = md5(rand(1, time()));
		Fsb::$tpl->set_vars( array(
			'U_ACTION' =>			sid(ROOT . 'index.' . PHPEXT . '?p=register&amp;module=fsbcard'),
			'ACCEPT_RULES' =>		(($this->data['accept_rules']) ? 'checked="checked"' : ''),
			'CODE_CONFIRMATION' =>	($this->img_mode == 'keep') ? $this->data['u_visual_code'] : '',
			'CONFIRMATION_IMG' =>	sid(ROOT . 'main/visual_confirmation.' . PHPEXT . '?mode=' . $this->img_mode . '&amp;frame=true&amp;uniqid=' . $uniqid),
			'REFRESH_IMG' =>		sid(ROOT . 'main/visual_confirmation.' . PHPEXT . '?mode=refresh&amp;frame=true&amp;uniqid=' . $uniqid),
		));

		// Champs personals créés par l'administrateur
		Profil_fields_forum::form(PROFIL_FIELDS_PERSONAL, 'personal', Fsb::$session->id(), TRUE);
	}

	/*
	** Vérifie les données envoyées par le formulaire d'import de FSBcard
	*/
	public function check_fsbcard_form()
	{		
		$this->errstr = array();

		// Règles du forum acceptées ?
		if (!$this->data['accept_rules'])
		{
			$this->errstr[] = Fsb::$session->lang('register_need_accept');
		}

		// On vérifie le code de confirmation visuelle
		if ($this->use_visual_confirmation && !check_captcha($this->data['u_visual_code']))
		{
			$this->img_mode = 'generate';
			$this->errstr[] = sprintf(Fsb::$session->lang('register_bad_visual_code'), intval(5 - Fsb::$session->data['s_visual_try']));
		}
		else
		{
			$this->img_mode = 'keep';
		}

		// On vérifie si une FSBcard a été uploadée
		if (empty($_FILES['upload_fsbcard']['name']))
		{
			$this->errstr[] = Fsb::$session->lang('register_need_fsbcard');
		}

		$this->insert_fields = Profil_fields_forum::validate(PROFIL_FIELDS_PERSONAL, 'personal', $this->errstr, NULL, TRUE);
	}

	/*
	** Inscription via FSBcard
	*/
	public function import_user()
	{
		// Upload de la FSBcard
		$upload = new Upload('upload_fsbcard');
		$upload->allow_ext(array('xml'));
		$card_path = ROOT . 'upload/' . $upload->store(ROOT . 'upload/');
		$content = file_get_contents($card_path);
		unlink($card_path);

		// Lecture de la FSBcard de façon à renseigner les champs par défaut
		if (!$this->read_fsbcard($content))
		{
			return ;
		}

		// Création de l'utilisateur
		$last_id = $this->create_user();
		$message = $this->send_mail($last_id);

		// Ajout des préférences du membre, stoquées dans la FSBcard
		fsb_import('user_fsbcard');
		$update_array = Page_user_fsbcard::import_fsbcard($last_id, $content);
		Fsb::$db->update('users', $update_array, 'WHERE u_id = ' . $last_id);

		Display::message($message . return_to('index.' . PHPEXT, 'forum_index'));
	}

	/*
	** Informations de connexion contenues dans la FSBcard
	** -----
	** $content ::	Contenu de la FSBcard
	*/
	public function read_fsbcard($content)
	{
		// Instance Fsbcard
		$fsbcard = new Fsbcard();
		$fsbcard->load_content($content);

		// Informations de connexion
		if ($fsbcard->xml->document->hasChildren('register'))
		{
			$this->data['u_login'] =					$fsbcard->get_login();
			$this->data['u_nickname'] =					$fsbcard->get_nickname();
			$this->data['u_email'] =					$fsbcard->get_email();
			list($this->data['u_password'], $hash) =	$fsbcard->get_password();

			// Vérification de l'intégrité de la FSBcard
			if ($this->data['u_login'] === NULL || $this->data['u_nickname'] === NULL || $this->data['u_password'] === NULL || $this->data['u_email'] === NULL)
			{
				$this->errstr[] = Fsb::$session->lang('register_fsbcard_invalid');
				return (FALSE);
			}

			// Vérification de la taille du pseudonyme
			if (strlen($this->data['u_nickname']) < 3)
			{
				$this->errstr[] = Fsb::$session->lang('register_short_nickname');
			}
			else if (String::strlen($this->data['u_nickname']) > $this->max_nickname_length)
			{
				$this->errstr[] = sprintf(Fsb::$session->lang('register_long_nickname'), $this->max_nickname_length);
			}

			// Pseudonyme valide ?
			if (($valid_nickname = User::nickname_valid($this->data['u_nickname'])) !== TRUE)
			{
				$this->errstr[] = Fsb::$session->lang('nickname_chars_' . $valid_nickname);
			}

			// Mot de passe différent du login ?
			if (strtolower($this->data['u_password']) == strtolower($this->data['u_nickname']))
			{
				$this->errstr[] = Fsb::$session->lang('register_password_diff_nickname');
			}

			// Validité de l'adresse Email
			if (!User::email_valid($this->data['u_email']))
			{
				$this->errstr[] = Fsb::$session->lang('register_email_format');
			}

			// Vérification de l'existance du login
			if (User::login_exists($this->data['u_login']))
			{
				$this->errstr[] = Fsb::$session->lang('register_login_exists');
			}

			// Vérification de l'existance du pseudonyme
			if ($this->data['u_nickname'] && User::nickname_exists($this->data['u_nickname']))
			{
				$this->errstr[] = Fsb::$session->lang('register_nickname_exists');
			}

			// Vérification de l'existance de l'email
			if ($this->data['u_email'] && User::email_exists($this->data['u_email']))
			{
				$this->errstr[] = Fsb::$session->lang('register_email_exists');
			}
			
			// On vérifie si le login ou l'adresse E-mail ont été bannis
			if ($ban_type = Fsb::$session->is_ban(-1, $this->data['u_nickname'], Fsb::$session->ip, $this->data['u_email']))
			{
				$this->errstr[] = Fsb::$session->lang('register_ban_' . $ban_type['type']);
			}
		}
		else
		{
			$this->errstr[] = Fsb::$session->lang('register_fsbcard_invalid');
		}

		return (($this->errstr) ? FALSE : TRUE);
	}
}

/* EOF */