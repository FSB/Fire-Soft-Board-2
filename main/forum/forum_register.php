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
 * Page d'inscription au forum
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
	 * Module de la page
	 *
	 * @var string
	 */
	public $module;

	/**
	 * Contient les donnees passees en formulaire
	 *
	 * @var array
	 */
	public $data = array();
	
	/**
	 * Contient les champs des donnees a traiter
	 *
	 * @var array
	 */
	public $post_data = array();

	/**
	 * Contient les informations personelles
	 *
	 * @var array
	 */
	public $fields = array();
	
	/**
	 * Contient les informations personelles (insertion)
	 *
	 * @var array
	 */
	public $insert_fields = array();

	/**
	 * Contient la liste des erreurs
	 *
	 * @var array
	 */
	public $errstr = array();
	
	/**
	 * Mode de l'image de confirmation visuelle
	 *
	 * @var string
	 */
	public $img_mode = 'generate';

	/**
	 * Peut utiliser la confirmation visuelle
	 *
	 * @var bool
	 */
	public $use_visual_confirmation = false;

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
	 * Longueur max du pseudonyme
	 *
	 * @var int
	 */
	public $max_nickname_length = 20;
	
	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		// Chiffrage RSA ?
		$this->use_rsa = (Fsb::$mods->is_active('rsa') && Rsa::can_use()) ? true : false;

		// On verifie si l'extension GD2 est utilisable pour la confirmation visuelle
		if (Fsb::$mods->is_active('visual_confirmation'))
		{
			$this->use_visual_confirmation = true;
		}

		// Les membres connectes ne sont pas admis sur cette page
		if (Fsb::$session->is_logged())
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		// Inscriptions desactivees
		if (Fsb::$cfg->get('register_type') == 'disabled')
		{
			Display::message('register_are_disabled');
		}

		// Interdiction d'acceder a la page a cause d'un nombre trop grand d'essai ?
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

	/**
	 * Verifie le module d'enregistrement charge
	 *
	 */
	public function check_register_module()
	{
		// Recuperation du module
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
				'IS_SELECT' =>	($this->module == $module) ? true : false,
				'URL' =>		sid(ROOT . 'index.' . PHPEXT . '?p=register&amp;module=' . $module),
				'NAME' =>		Fsb::$session->lang('register_menu_' . $module),
			));
		}

		Fsb::$tpl->set_vars(array(
			'MENU_HEADER_TITLE' =>	Fsb::$session->lang('register_data'),
		));
	}
	
	/**
	 * Ajoute dans le tableau $this->data les variables passees en formulaires, traitees et securisees.
	 *
	 */
	public function post_data()
	{
		$this->post_data = array(
			array('str' => 'accept_rules', 'insert' => false),
			array('str' => 'u_login', 'insert' => true),
			array('str' => 'u_login_rsa', 'insert' => false),
			array('str' => 'u_nickname', 'insert' => true),
			array('str' => 'u_password', 'insert' => true),
			array('str' => 'u_password_rsa', 'insert' => false),
			array('str' => 'u_password_confirm', 'insert' => false),
			array('str' => 'u_password_confirm_rsa', 'insert' => false),
			array('str' => 'u_email', 'insert' => true),
			array('str' => 'u_email_rsa', 'insert' => false),
			array('str' => 'u_visual_code', 'insert' => false),
		);
		
		foreach ($this->post_data AS $value)
		{
			$this->data[$value['str']] = trim(Http::request($value['str'], 'post'));
		}

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

			$this->data['u_login'] =			$rsa->decrypt($this->data['u_login_rsa']);
			$this->data['u_password'] =			$rsa->decrypt($this->data['u_password_rsa']);
			$this->data['u_password_confirm'] =	$rsa->decrypt($this->data['u_password_confirm_rsa']);
			$this->data['u_email'] =			$rsa->decrypt($this->data['u_email_rsa']);
		}
	}
	
	/**
	 * Affiche le formulaire d'enregistrement
	 *
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
			
			// Merci a Fladnag (http://www.developpez.net/forums/profile.php?mode=viewprofile&u=33459) pour son aide sur
			// le cache des images.
			'CONFIRMATION_IMG' =>	sid(ROOT . 'main/visual_confirmation.' . PHPEXT . '?mode=' . $this->img_mode . '&amp;frame= true &amp;uniqid=' . $uniqid),
			'REFRESH_IMG' =>		sid(ROOT . 'main/visual_confirmation.' . PHPEXT . '?mode=refresh&amp;frame= true &amp;uniqid=' . $uniqid),
		));

		// Champs personals crees par l'administrateur
		Profil_fields_forum::form(PROFIL_FIELDS_PERSONAL, 'personal', Fsb::$session->id(), true);

		Fsb::$tpl->set_switch('form');
	}

	/**
	 * Evalue la robustesse du mot de passe
	 *
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

	/**
	 * Genere un mot de passe aleatoirement
	 *
	 */
	public function generate_password()
	{
		$random_password = Password::generate(10);

		Fsb::$tpl->set_switch('test_password');
		Fsb::$tpl->set_vars(array(
			'PASSWORD_RESULT' =>	sprintf(Fsb::$session->lang('password_generate_result'), $random_password),
		));
	}
	
	/**
	 * Verifie les donnees envoyees par le formulaire
	 *
	 */
	public function check_form()
	{		
		$this->errstr = array();

		// Regles du forum acceptees ?
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
		// Verification de la taille du pseudonyme
		else if (String::strlen($this->data['u_nickname']) < 3)
		{
			$this->errstr[] = Fsb::$session->lang('register_short_nickname');
		}
		else if (String::strlen($this->data['u_nickname']) > $this->max_nickname_length)
		{
			$this->errstr[] = sprintf(Fsb::$session->lang('register_long_nickname'), $this->max_nickname_length);
		}

		// Pseudonyme valide ?
		if (($valid_nickname = User::nickname_valid($this->data['u_nickname'])) !== true)
		{
			//On n'affiche pas le message d'erreur si le pseudonyme est vide (une condition précédente le fait)
			if($valid_nickname != 'low')
			{
				$this->errstr[] = Fsb::$session->lang('nickname_chars_' . $valid_nickname);
			}
		}

		// Necessite l'entree des deux mots de passe
		if (!$this->data['u_password'] || !$this->data['u_password_confirm'])
		{
			$this->errstr[] = Fsb::$session->lang('register_need_password');
		}

		// Comparaison des mots de passe
		if ($this->data['u_password'] != $this->data['u_password_confirm'])
		{
			$this->errstr[] = Fsb::$session->lang('register_password_dif');
		}

		// Mot de passe different du login ?
		if ($this->data['u_password'] && strtolower($this->data['u_password']) == strtolower($this->data['u_nickname']))
		{
			$this->errstr[] = Fsb::$session->lang('register_password_diff_nickname');
		}

		// Validite de l'adresse Email
		if (!User::email_valid($this->data['u_email']))
		{
			$this->errstr[] = Fsb::$session->lang('register_email_format');
		}

		// Verification de l'existance du login
		if (User::login_exists($this->data['u_login']))
		{
			$this->errstr[] = Fsb::$session->lang('register_login_exists');
		}

		// Verification de l'existance du pseudonyme
		if ($this->data['u_nickname'] && User::nickname_exists($this->data['u_nickname']))
		{
			$this->errstr[] = Fsb::$session->lang('register_nickname_exists');
		}

		// Verification de l'existance de l'email
		if ($this->data['u_email'] && User::email_exists($this->data['u_email']))
		{
			$this->errstr[] = Fsb::$session->lang('register_email_exists');
		}
		
		// On verifie si le login ou l'adresse E-mail ont ete bannis
		if ($ban_type = Fsb::$session->is_ban(-1, $this->data['u_nickname'], Fsb::$session->ip, $this->data['u_email']))
		{
			$this->errstr[] = Fsb::$session->lang('register_ban_' . $ban_type['type']);
		}
		
		// On verifie le code de confirmation visuelle
		if ($this->use_visual_confirmation && !check_captcha($this->data['u_visual_code']))
		{
			$this->img_mode = 'generate';
			$this->errstr[] = sprintf(Fsb::$session->lang('register_bad_visual_code'), intval(5 - Fsb::$session->data['s_visual_try']));
		}
		else
		{
			$this->img_mode = 'keep';
		}
		
		//Si le module RSA est activé et qu'il y a une erreur on ne renvoie pas le password
		if ($this->use_rsa && Http::request('hidden_rsa', 'post') && count($this->errstr))
		{
			$this->data['u_password'] =			'';
			$this->data['u_password_confirm'] =	'';
		}

		$this->insert_fields = Profil_fields_forum::validate(PROFIL_FIELDS_PERSONAL, 'personal', $this->errstr, null, true);
	}
	
	/**
	 * Ajoute l'utilisateur fraichement inscrit dans la base de donnee, et tout le tralala qui va avec :=)
	 *
	 */
	public function submit_user()
	{
		$last_id = $this->create_user();
		$message = $this->send_mail($last_id);

		Display::message($message . return_to(Http::redirect_to(Http::request('redirect'), true), 'previous_page'));
	}

	/**
	 * Insertion d'utilisateur
	 *
	 * @return int
	 */
	public function create_user()
	{
		Fsb::$db->transaction('begin');

		// On cree la requete pour l'enregistrement du membre
		$insert_ary = array();
		foreach ($this->post_data AS $value)
		{
			if ($value['insert'])
			{
				$insert_ary[$value['str']] = $this->data[$value['str']];
			}
		}

		// Creation du membre
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

	/**
	 * Envoie de l'Email d'inscription
	 *
	 * @param int $u_id ID du membre
	 * @return string
	 */
	public function send_mail($u_id)
	{
		if (User::confirm_register($u_id, $this->data))
		{
			return (Fsb::$session->lang('register_ok_' . Fsb::$cfg->get('register_type')));
		}
		else
		{
			return (Fsb::$session->lang('register_ko'));
		}
	}

	/**
	 * Formulaire d'import de profil
	 *
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
			'CONFIRMATION_IMG' =>	sid(ROOT . 'main/visual_confirmation.' . PHPEXT . '?mode=' . $this->img_mode . '&amp;frame= true &amp;uniqid=' . $uniqid),
			'REFRESH_IMG' =>		sid(ROOT . 'main/visual_confirmation.' . PHPEXT . '?mode=refresh&amp;frame= true &amp;uniqid=' . $uniqid),
		));

		// Champs personals crees par l'administrateur
		Profil_fields_forum::form(PROFIL_FIELDS_PERSONAL, 'personal', Fsb::$session->id(), true);
	}

	/**
	 * Verifie les donnees envoyees par le formulaire d'import de FSBcard
	 *
	 */
	public function check_fsbcard_form()
	{		
		$this->errstr = array();

		// Regles du forum acceptees ?
		if (!$this->data['accept_rules'])
		{
			$this->errstr[] = Fsb::$session->lang('register_need_accept');
		}

		// On verifie le code de confirmation visuelle
		if ($this->use_visual_confirmation && !check_captcha($this->data['u_visual_code']))
		{
			$this->img_mode = 'generate';
			$this->errstr[] = sprintf(Fsb::$session->lang('register_bad_visual_code'), intval(5 - Fsb::$session->data['s_visual_try']));
		}
		else
		{
			$this->img_mode = 'keep';
		}

		// On verifie si une FSBcard a ete uploadee
		if (empty($_FILES['upload_fsbcard']['name']))
		{
			$this->errstr[] = Fsb::$session->lang('register_need_fsbcard');
		}

		$this->insert_fields = Profil_fields_forum::validate(PROFIL_FIELDS_PERSONAL, 'personal', $this->errstr, null, true);
	}

	/**
	 * Inscription via FSBcard
	 *
	 */
	public function import_user()
	{
		// Upload de la FSBcard
		$upload = new Upload('upload_fsbcard');
		$upload->allow_ext(array('xml'));
		$card_path = ROOT . 'upload/' . $upload->store(ROOT . 'upload/');
		$content = file_get_contents($card_path);
		unlink($card_path);

		// Lecture de la FSBcard de facon a renseigner les champs par defaut
		if (!$this->read_fsbcard($content))
		{
			return ;
		}

		// Creation de l'utilisateur
		$last_id = $this->create_user();
		$message = $this->send_mail($last_id);

		// Ajout des preferences du membre, stoquees dans la FSBcard
		fsb_import('user_fsbcard');
		$update_array = Page_user_fsbcard::import_fsbcard($last_id, $content);
		Fsb::$db->update('users', $update_array, 'WHERE u_id = ' . $last_id);

		Display::message($message . return_to('index.' . PHPEXT, 'forum_index'));
	}

	/**
	 * Informations de connexion contenues dans la FSBcard
	 *
	 * @param string $content Contenu de la FSBCard
	 * @return bool
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

			// Verification de l'integrite de la FSBcard
			if (!$this->data['u_login'] || !$this->data['u_nickname'] || !$this->data['u_password'] || !$this->data['u_email'])
			{
				$this->errstr[] = Fsb::$session->lang('register_fsbcard_invalid');
				return (false);
			}

			// Verification de la taille du pseudonyme
			if (strlen($this->data['u_nickname']) < 3)
			{
				$this->errstr[] = Fsb::$session->lang('register_short_nickname');
			}
			else if (String::strlen($this->data['u_nickname']) > $this->max_nickname_length)
			{
				$this->errstr[] = sprintf(Fsb::$session->lang('register_long_nickname'), $this->max_nickname_length);
			}

			// Pseudonyme valide ?
			if (($valid_nickname = User::nickname_valid($this->data['u_nickname'])) !== true)
			{
				$this->errstr[] = Fsb::$session->lang('nickname_chars_' . $valid_nickname);
			}

			// Mot de passe different du login ?
			if (strtolower($this->data['u_password']) == strtolower($this->data['u_nickname']))
			{
				$this->errstr[] = Fsb::$session->lang('register_password_diff_nickname');
			}

			// Validite de l'adresse Email
			if (!User::email_valid($this->data['u_email']))
			{
				$this->errstr[] = Fsb::$session->lang('register_email_format');
			}

			// Verification de l'existance du login
			if (User::login_exists($this->data['u_login']))
			{
				$this->errstr[] = Fsb::$session->lang('register_login_exists');
			}

			// Verification de l'existance du pseudonyme
			if ($this->data['u_nickname'] && User::nickname_exists($this->data['u_nickname']))
			{
				$this->errstr[] = Fsb::$session->lang('register_nickname_exists');
			}

			// Verification de l'existance de l'email
			if ($this->data['u_email'] && User::email_exists($this->data['u_email']))
			{
				$this->errstr[] = Fsb::$session->lang('register_email_exists');
			}
			
			// On verifie si le login ou l'adresse E-mail ont ete bannis
			if ($ban_type = Fsb::$session->is_ban(-1, $this->data['u_nickname'], Fsb::$session->ip, $this->data['u_email']))
			{
				$this->errstr[] = Fsb::$session->lang('register_ban_' . $ban_type['type']);
			}
		}
		else
		{
			$this->errstr[] = Fsb::$session->lang('register_fsbcard_invalid');
		}

		return (($this->errstr) ? false : true);
	}
}

/* EOF */
