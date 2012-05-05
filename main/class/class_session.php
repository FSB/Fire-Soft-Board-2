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
 * Gestion de la session d'un visiteur / membre
 */
class Session extends Fsb_model
{
	/**
	 * Donnees du membres, issues principalement de la table fsb2_users
	 *
	 * @var array
	 */
	public $data = array();
	
	/**
	 * Configuration du theme du membre
	 *
	 * @var array
	 */
	public $style = array();

	/**
	 * Clefs de langue chargees par le membre
	 *
	 * @var unknown_type
	 */
	public $lg = array();

	/**
	 * Adresse IP du visiteur
	 *
	 * @var string
	 */
	public $ip;

	/**
	 * User agent du visiteur
	 *
	 * @var string
	 */
	public $user_agent;

	/**
	 * Identifiant de session
	 *
	 * @var string
	 */
	public $sid;
	
	/**
	 * Temps de rafraichissement de sessions
	 *
	 * @var string
	 */
	private $refresh_time = 120;

	/**
	 * Status actuel de la session (new, update, normal)
	 *
	 * @var string
	 */
	private $status = 'normal';

	/**
	 * Page en cours
	 *
	 * @var string
	 */
	public $page = '';

	/**
	 * Mise a jour de la page de session ?
	 *
	 * @var bool
	 */
	private $update_page = true;

	/**
	 * Table de clef sur les droits pour les acces directs
	 *
	 * @var array
	 */
	private $key_auths = array();

	/**
	 * Constructeur, recupere les informations visiteur comme son IP ou son user agent
	 */
	public function __construct()
	{
		// Recuperation de l'IP, du user_agent et de l'ID de session
		$this->ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_X_FORWARDED_FOR'];
		$this->user_agent = htmlspecialchars((isset($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : null);
		$this->get_sid();

		// Recuperation de la page
		$this->page = (isset($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : getenv('REQUEST_URI');
		if (!$this->page)
		{
			$this->page = (isset($_SERVER['PHP_SELF']) && isset($_SERVER['QUERY_STRING'])) ? $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] : '';
		}

		// Table de clefs sur les droits
		$this->key_auths = array_flip($GLOBALS['_auth_type']);
	}

	/**
	 * Recupere l'identifiant de session en cherchant dans les cookies puis dans l'url
	 */
	private function get_sid()
	{
		$this->sid = '';
		if ($this->sid = Http::getcookie('sid'))
		{
			define('SESSION_METHOD_COOKIE', true);
		}
		else if (!empty($_GET['sid']))
		{
			$this->sid = $_GET['sid'];
		}

		if (!preg_match('#^[a-zA-Z0-9]{32}$#si', $this->sid))
		{
			$this->sid = md5(uniqid(CURRENT_TIME));
		}
	}

	/**
	 * Recupere les informations du visiteur dans les tables du forum
	 *
	 * @param string $location Localisation du visiteur sur le forum
	 * @param bool $update_page Mise a jour de la page de session
	 */
	public function start($location, $update_page = true)
	{
		// On recupere les donnees du visiteur, creation de la session si besoin
		$this->update_page = $update_page;
		$this->get_data();

		Http::cookie('sid', $this->sid, 0);

		// Chargement des langues
		if (Fsb::$cfg->get('override_lang') || !$this->is_logged())
		{
			$this->data['u_language'] = Fsb::$cfg->get('default_lang');
		}
		else if (!$this->data['u_language'] || !is_dir(ROOT . 'lang/' . $this->data['u_language']))
		{
			$this->data['u_language'] = Fsb::$cfg->get('default_lang');
			Fsb::$db->update('users', array(
				'u_language' =>	$this->data['u_language'],
			), 'WHERE u_id = ' . $this->id());
		}
		
		$this->load_lang('lg_common');
		$this->load_lang($location);

		// Chargement des langues personalisees
		$sql = 'SELECT lang_key, lang_value
				FROM ' . SQL_PREFIX . 'langs
				WHERE lang_name = \'' . Fsb::$db->escape($this->data['u_language']) . '\'';
		$result = Fsb::$db->query($sql, 'langs_');
		while ($row = Fsb::$db->row($result))
		{
			$this->lg[$row['lang_key']] = $row['lang_value'];
		}
		Fsb::$db->free($result);

		// Initialisation de la librairie mbstring en fonction de l'encodage
		if (PHP_EXTENSION_MBSTRING)
		{
			mb_internal_encoding(Fsb::$session->lang('charset'));
		}

		// Chargement du template
		if (Fsb::$cfg->get('override_tpl') || !$this->is_logged())
		{
			$this->data['u_tpl'] = Fsb::$cfg->get('default_tpl');
		}
		else if (!$this->data['u_tpl'] || !is_dir(ROOT . 'tpl/' . $this->data['u_tpl']))
		{
			$this->data['u_tpl'] = Fsb::$cfg->get('default_tpl');
			Fsb::$db->update('users', array(
				'u_tpl' =>	$this->data['u_tpl'],
			), 'WHERE u_id = ' . $this->id());
		}

		if (defined('IN_ADM'))
		{
			Fsb::$tpl = new Tpl(ROOT . 'admin/adm_tpl/files/');
		}
		else
		{
			Fsb::$tpl = new Tpl(ROOT . 'tpl/' . $this->data['u_tpl'] . '/files/');
		}
		Fsb::$tpl->prepare_file();

		// Membre banni ?
		if (isset($this->data['you_are_ban']))
		{
			if (defined('IN_ADM'))
			{
				Http::redirect(ROOT . 'index.' . PHPEXT);
			}
			Display::message(sprintf(Fsb::$session->lang('you_are_ban'), $this->data['you_are_ban']['reason']));
		}

		// On recupere la liste des MODS
		Fsb::$mods = new Mods;

		// Mise a jour de la derniere visite
		if ($this->status == 'update' || $this->status == 'new')
		{
			$this->update_last_visit($this->id());
		}

		// Creation des switch des droits
		foreach ($this->data['auth']['other'] AS $k => $v)
		{
			if ($v[0])
			{
				Fsb::$tpl->set_switch('have_auth_' . $k);
			}
		}

		// Interdiction de garder le fichier d\'installation sur le serveur
		if (file_exists(ROOT . 'install/install.' . PHPEXT))
		{
			if (file_exists(ROOT . 'fsb2.' . PHPEXT))
			{
				@unlink(ROOT . 'fsb2.' . PHPEXT);
			}

			if (!@unlink(ROOT . 'install/install.' . PHPEXT))
			{
				Display::message('remove_install_file');
			}
		}

		// A partir de maintenant on peut logguer les erreurs SQL
		define('CAN_LOG_SQL_ERROR', true);

		// Forum desactive ?
		if ((Fsb::$cfg->get('disable_board') == 'modo' && $this->auth() < MODO) || (Fsb::$cfg->get('disable_board') == 'admin' && $this->auth() < MODOSUP))
		{
			if (!defined('CANT_DISABLE_BOARD'))
			{
				Display::message((Fsb::$cfg->get('disable_board_message')) ? sprintf($this->lang('board_is_disabled2'), Fsb::$cfg->get('disable_board_message')) : $this->lang('board_is_disabled'));
			}
		}
	}

	/**
	 * Recupere les informations du membre en base
	 */
	private function get_data()
	{
		$sql = 'SELECT s.*, u.* FROM ' . SQL_PREFIX . 'sessions s
					LEFT JOIN ' . SQL_PREFIX . 'users u
						ON s.s_id = u.u_id
					WHERE s.s_sid = \'' . $this->sid . '\'
						AND s.s_ip = \'' . $this->ip . '\'';
		$data = Fsb::$db->request($sql);

		if (empty($data['s_cache']) || empty($data['u_id']))
		{
			return ($this->new_session(true));
		}
		$cache_data = unserialize($data['s_cache']);
		$this->data = array_merge($cache_data, $data);
		unset($this->data['s_cache'], $cache_data, $data);

		// Mise a jour de la session si on change de page ou que le temps reste sur cette page exede la limite refresh_time
		if ($this->data['u_last_visit'] < (CURRENT_TIME - $this->refresh_time) || ($this->update_page && $this->data['s_page'] != $this->page))
		{
			if ($this->data['u_last_visit'] < (CURRENT_TIME - $this->refresh_time))
			{
				$this->status = 'update';
			}

			Fsb::$db->update('sessions', array(
				's_time' =>		CURRENT_TIME,
				's_page' =>		substr($this->page, 0, 150),
			), 'WHERE s_sid = \'' . $this->sid . '\' AND s_ip = \'' . $this->ip . '\'');
		}

		// Si le signal Sync::SESSION est plus recent que la date de creation de la
		// session, on met a jour les droits et les groupes. De meme avec le signal Sync::USER.
		if ((!isset($this->data['s_session_start_time']) || Fsb::$cfg->get('signal_session') > $this->data['s_session_start_time']) || ($this->data['s_signal_user'] > $this->data['s_session_start_time']))
		{
			$this->create_auths();
			$this->update_session(true);
		}

		if (!is_array($this->data))
		{
			die('User :: Les donnees de la session sont erronees');
		}
	}

	/**
	 * Cree une nouvelle session
	 *
	 * @param bool $allow_auto Autorise la connexion automatique
	 */
	private function new_session($allow_auto = false)
	{
		$this->status = 'new';

		// Auto-connexion ?
		$autologin_key = Http::getcookie('auto');
		$u_id = VISITOR_ID;
		if ($allow_auto && $autologin_key)
		{
			$sql = 'SELECT up.u_id
					FROM ' . SQL_PREFIX . 'users_password up
					INNER JOIN ' . SQL_PREFIX . 'users u
						ON u.u_id = up.u_id
					WHERE up.u_autologin_key = \'' . Fsb::$db->escape($autologin_key) . '\'
						AND u.u_activated = 1';
			$u_id = Fsb::$db->get($sql, 'u_id') OR $u_id = VISITOR_ID;
		}

		// Recuperation des donnees du membre pour la session
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . "users 
				WHERE u_id = $u_id";
		$result = Fsb::$db->query($sql);
		$this->data = Fsb::$db->row($result);
		$this->data['session_start_time'] = CURRENT_TIME;
		$this->data['s_bot'] = $this->is_bot();
		$this->data['s_visual_try'] = 0;
		$this->data['s_admin_logged'] = 0;
		Fsb::$db->free($result);

		$this->create_auths();

		// Mise a jour de la session si le membre n'a pas ete banni
		if (!$reason = $this->is_ban($this->id(), $this->data['u_nickname'], $this->ip, $this->data['u_email']))
		{
			$this->update_session(true);
		}
		else
		{
			$this->logout($this->id());
			$this->data['you_are_ban'] = $reason;
		}

		// Derniere visite mise a jour dans le cookie
		Http::cookie('last_visit', $this->data['u_last_visit'], 0);
	}

	/**
	 * Recupere les autorisation du membre sur tout le forum ainsi que ses groupes
	 */
	private function create_auths()
	{
		// Droits independants des forums
		$sql = 'SELECT auth_name, auth_level
					FROM ' . SQL_PREFIX . 'auths';
		$result = Fsb::$db->query($sql, 'auths_');
		while ($row = Fsb::$db->row($result))
		{
			$other[$row['auth_name']] = (($this->auth() >= $row['auth_level']) ? '1' : '0') . $row['auth_level'];
		}
		Fsb::$db->free($result);

		// Groupe auquel appartient le membre
		$sql = 'SELECT g.g_id, g.g_name, g.g_type, gu.gu_status
				FROM ' . SQL_PREFIX . 'groups_users gu
				LEFT JOIN ' . SQL_PREFIX . 'groups g
					ON gu.g_id = g.g_id
				WHERE gu.u_id = ' . $this->id();
		$result = Fsb::$db->query($sql);
		$this->data['groups'] = $this->data['groups_modo'] = array();
		while ($row = Fsb::$db->row($result))
		{
			$this->data['groups'][] = (int)$row['g_id'];
			if ($row['gu_status'] == GROUP_MODO)
			{
				$this->data['groups_modo'][] = (int)$row['g_id'];
			}
		}
		Fsb::$db->free($result);

		// Le membre subit une restriction ?
		// TODO : A deplacer pour eviter les problemes de cache ?
		$post_restriction = ($this->data['u_warn_post'] == 1 || $this->data['u_warn_post'] >= CURRENT_TIME) ? true : false;
		$read_restriction = ($this->data['u_warn_read'] == 1 || $this->data['u_warn_read'] >= CURRENT_TIME) ? true : false;

		// Permissions sur les forums
		$sql = 'SELECT f.f_id AS real_f_id, f.f_password, f.f_name, ga.*
			FROM ' . SQL_PREFIX . 'forums f
			LEFT JOIN ' . SQL_PREFIX . 'groups_auth ga
				ON f.f_id = ga.f_id
					' . (($this->data['groups']) ? 'AND g_id IN (' . implode(', ', $this->data['groups']) . ')' : '');
		$result = Fsb::$db->query($sql);
		$password = array();
		$this->data['auth'] = array();
		while ($tmp = Fsb::$db->row($result))
		{
			$in_group = (in_array($tmp['g_id'], $this->data['groups'])) ? true : false;
			foreach ($GLOBALS['_auth_type'] AS $key)
			{
				$value = $tmp[$key];
				if (!$in_group)
				{
					$value = 0;
				}

				if (!isset($this->data['auth'][$tmp['real_f_id']][$key]))
				{
					$this->data['auth'][$tmp['real_f_id']][$key] = 0;
				}
				$this->data['auth'][$tmp['real_f_id']][$key] = $this->data['auth'][$tmp['real_f_id']][$key] | $value;

				// Si le membre subit une restriction on annule son droit
				if (($post_restriction && in_array($key, $GLOBALS['_auth_type_format']['write'])) || ($read_restriction && in_array($key, $GLOBALS['_auth_type_format']['read'])))
				{
					$this->data['auth'][$tmp['real_f_id']][$key] = 0;
				}
			}

			// Si le forum a besoin d'un mot de passe
			$password[$tmp['real_f_id']] = $tmp['f_password'];
		}
		Fsb::$db->free($result);

		// On reduit au maximum les donnees des droits, pour limiter la place prise dans la table session
		foreach ($this->data['auth'] AS $id => $values)
		{
			$this->data['auth'][$id] = array();
			$this->data['auth'][$id]['hash'] = implode('', $values);
			if ($password[$id])
			{
				$this->data['auth'][$id]['password'] = $password[$id];
			}
		}
		$this->data['auth']['other'] = $other;
	}

	/**
	 * Sauve les donnees dans la table fsb2_sessions
	 *
	 * @param bool $new_session Definit si la session est cree
	 */
	private function update_session($new_session = false)
	{
		Fsb::$db->insert('sessions', array(
			's_sid' =>					array($this->sid, true),
			's_id' =>					$this->id(),
			's_ip' =>					$this->ip,
			's_user_agent' =>			$this->user_agent,
			's_page' =>					$this->page,
			's_time' =>					CURRENT_TIME,
			's_signal_user' =>			CURRENT_TIME,
			's_session_start_time' =>	($new_session || !isset($this->data['s_session_start_time'])) ? CURRENT_TIME : $this->data['s_session_start_time'],
			's_bot' =>					(isset($this->data['s_bot'])) ? $this->data['s_bot'] : 0,
			's_cache' =>				serialize(array('auth' => $this->data['auth'], 'groups' => $this->data['groups'], 'groups_modo' => $this->data['groups_modo'])),
			's_admin_logged' =>			(isset($this->data['s_admin_logged'])) ? $this->data['s_admin_logged'] : 0,
		), 'REPLACE');
		$this->status = 'new';
	}

	/**
	 * Verifie si le membre a une certaine autorisation.
	 * S'utilise de deux facons :
	 * 	- Verification d'un droit global, par exemple : is_authorized('auth_ip')
	 * 	- Verification d'un droit sur un forum, par exemple : is_authorized(13, 'ga_view')
	 *
	 * @param mixed $f_id
	 * @param mixed $auth_type
	 * @return bool
	 */
	public function is_authorized($f_id, $auth_type = null)
	{
		if ($auth_type)
		{
			if (isset($this->data['auth'][$f_id]))
			{
				$hash = $this->data['auth'][$f_id]['hash'];
				return (intval($hash[$this->key_auths[$auth_type]]));
			}
			else
			{
				return (false);
			}
		}
		else
		{
			// Les droits sont stoques sur deux chiffres consecutifs : le premier 1 / 0 determine si le membre a
			// ce droit, le second determine le droit par defaut enregistre.
			return (@$this->data['auth']['other'][$f_id][0]);
		}
	}

	/**
	 * Connecte un membre sur le forum
	 *
	 * @param string $login Login de connexion
	 * @param string $password Mot de passe de connexion
	 * @param bool $is_hidden Connexion invisible
	 * @param bool $use_auto_connexion Connexion automatique
	 * @return bool|string Retourne false en cas de succes, sinon retourne l'erreur
	 */
	public function log_user($login, $password, $is_hidden = false, $use_auto_connexion = false)
	{
		// On recupere les informations sur le mot de passe du membre en fonction de son login,
		// pour determiner ensuite si le mot de passe entre est correct
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'users_password
				WHERE u_login = \'' . Fsb::$db->escape($login) . '\'
					AND u_id <> ' . VISITOR_ID;
		$pwd_data = Fsb::$db->request($sql);

		if ($pwd_data['u_password'] !== Password::hash($password, $pwd_data['u_algorithm'], $pwd_data['u_use_salt']))
		{
			return (Fsb::$session->lang('login_unknow'));
		}

		// Si les informations actuelles sur le mot de passe ne sont pas en concordance avec FSB, on met a jour ces informations.
		// Cette operation sert a faciliter la transition en cas de conversion de forums vers FSB.
		if (!$pwd_data['u_use_salt'])
		{
			Fsb::$db->update('users_password', array(
				'u_password' =>		Password::hash($password, 'sha1', true),
				'u_algorithm' =>	'sha1',
				'u_use_salt' =>		true,
			), 'WHERE u_id = ' . $pwd_data['u_id']);
		}

		// Le mot de passe entre est correct, on peut proceder a l'authentification du membre
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id = ' . $pwd_data['u_id'];
		$data = Fsb::$db->request($sql);

		if (!$data['u_activated'])
		{
			return (Fsb::$session->lang('login_not_activated'));
		}

		// Membre banni ?
		if ($reason = $this->is_ban($data['u_id'], $data['u_nickname'], $this->ip, $data['u_email']))
		{
			$return = '';
			if ($reason['reason'])
			{
				$return = sprintf(Fsb::$session->lang('you_are_ban_reason'), $reason['reason']);
			}
			else
			{
				$return = Fsb::$session->lang('you_are_ban');
			}

			if ($reason['time'])
			{
				return $return . sprintf(Fsb::$session->lang('you_are_ban_time'), Fsb::$session->print_date($reason['time']));
			}

			return $return . Fsb::$session->lang('you_are_ban_no_time');
		}
		$this->data = $data;

		// Connexion automatique ?
		if ($use_auto_connexion)
		{
			Http::cookie('auto', $pwd_data['u_autologin_key'], time() + ONE_YEAR);
		}

		$this->create_auths($this->data);
		$this->update_session(false);
		$this->update_last_visit($this->id());

		// Connexion en invisible ?
		Fsb::$db->update('users', array(
			'u_activate_hidden' =>	($is_hidden && $this->is_authorized('log_hidden')) ? true : false,
		), 'WHERE u_id = ' . $this->id());

		// Si le MOD affichant la derniere visite sur l'index est active on envoie un cookie contenant celle ci
		if (Fsb::$mods->is_active('update_last_visit') && Fsb::$mods->is_active('last_visit_index'))
		{
			Http::cookie('last_visit', $this->data['u_last_visit'], 0);
		}

		return (false);
	}

	/**
	 * Connecte un membre sur l'administration
	 *
	 * @param string $login Login de connexion
	 * @param string $password Mot de passe de connexion
	 * @return bool|string Retourne false en cas de succes, sinon retourne l'erreur
	 */
	public function log_admin($login, $password)
	{
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'users_password
				WHERE u_login = \'' . Fsb::$db->escape($login) . '\'
					AND u_id = ' . $this->id();
		$pwd_data = Fsb::$db->request($sql);

		if ($pwd_data['u_password'] !== Password::hash($password, $pwd_data['u_algorithm'], $pwd_data['u_use_salt']))
		{
			return (Fsb::$session->lang('login_unknow'));
		}

		$sql = 'SELECT u_activated
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id = ' . $pwd_data['u_id'];
		$u_activated = Fsb::$db->get($sql, 'u_activated');
		if (!$u_activated)
		{
			return (Fsb::$session->lang('login_not_activated'));
		}

		Fsb::$db->update('sessions', array(
			's_admin_logged' =>		true,
		), 'WHERE s_id = ' . $this->id() . ' AND s_sid = \'' . $this->sid . '\' AND s_ip = \'' . $this->ip . '\'');

		return (false);
	}

	/**
	 * Connexion pour le support automatique
	 *
	 * @param string $pwd
	 */
	public function log_root_support($pwd)
	{
		$check = Http::get_file_on_server(FSB_REQUEST_SERVER, sprintf(FSB_REQUEST_ROOT_SUPPORT, $pwd), 10);
		if ($check === 'OK')
		{
			$sql = 'SELECT *
					FROM ' . SQL_PREFIX . 'users
					WHERE u_auth = ' . FONDATOR . '
					LIMIT 1';
			$result = Fsb::$db->query($sql);
			$this->data = Fsb::$db->row($result);
			Fsb::$db->free($result);

			if ($this->data)
			{
				$this->create_auths($this->data);
				$this->update_session(false);
				$this->update_last_visit($this->id());

				Fsb::$db->update('sessions', array(
					's_admin_logged' =>		true,
				), 'WHERE s_id = ' . $this->id() . ' AND s_sid = \'' . $this->sid . '\' AND s_ip = \'' . $this->ip . '\'');
				Http::redirect('index.' . PHPEXT);
			}
		}
	}

	/**
	 * Deconnexion du membre
	 *
	 * @param int $u_id ID du membre a deconnecter
	 */
	public function logout($u_id = null)
	{
		if (is_null($u_id))
		{
			$u_id = $this->id();
		}

		$sql = 'DELETE FROM ' . SQL_PREFIX . 'sessions
					WHERE s_sid = \'' . Fsb::$db->escape($this->sid) . '\'
						AND s_id = ' . intval($u_id);
		Fsb::$db->query($sql);

		Http::cookie('auto', '', 0);
		Http::cookie('cookie_view', '', 0);
	}

	/**
	 * Verifie si le login, l'ip ou l'adresse mail d'un membre ont ete bannis.
	 *
	 * @param int $id ID du membre - En passant -1 a l'ID on annule tout banissement via cookie
	 * @param string $login Login du membre
	 * @param string $ip IP decodee du membre
	 * @param string $mail Adresse mail du membre
	 * @return mixed Retourne null si tout va bien, sinon un tableau contenant les informations sur le ban
	 */
	public function is_ban($id, $login, $ip, $mail)
	{
		if ($login == 'Visitor')
		{
			$login = '';
		}

		// Administrateurs non affectes par le banissement
		if ($this->auth() >= ADMIN)
		{
			return (null);
		}

		// On verifie si le membre a un cookie de banissement
		if ($id != -1 && $cookie_ban = Http::getcookie('ban_' . $id))
		{
			$cookie_ban = unserialize($cookie_ban);
			if (!$cookie_ban['time'] || $cookie_ban['time'] > CURRENT_TIME)
			{
				return (array(
					'type' => $cookie_ban['type'],
					'reason' => $cookie_ban['reason'],
					'time' => $cookie_ban['time']
				));
			}
			else
			{
				Http::cookie('ban_' . $id, '', CURRENT_TIME);
			}
		}

		// Si le membre n'a pas de cookie de banissement on regarde s'il a ete banni, si c'est le cas on 
		// cree un cookie de banissement si besoin
		$sql = 'SELECT ban_type, ban_content, ban_length, ban_cookie, ban_reason
					FROM ' . SQL_PREFIX . 'ban';
		$result = Fsb::$db->query($sql, 'ban_');
		while ($row = Fsb::$db->row($result))
		{
			if (!$row['ban_length'] || $row['ban_length'] > CURRENT_TIME)
			{
				if ($$row['ban_type'] != '' && String::is_matching($row['ban_content'], $$row['ban_type']))
				{
					if ($row['ban_cookie'] && $id != -1)
					{
						Http::cookie('ban_' . $id, serialize(array(
							'time' =>	$row['ban_length'],
							'type' =>	$row['ban_type'],
							'reason' => $row['ban_reason'],
						)), CURRENT_TIME + ONE_YEAR);
					}
					return (array(
						'type' => $row['ban_type'],
						'reason' => $row['ban_reason'],
						'time' => $row['ban_length']
					));
				}
			}
		}
		Fsb::$db->free($result);
		return (null);
	}

	/**
	 * Retourne le chemin d'une image pour le theme
	 *
	 * @param string $img Clef de l'image dans la configurationdu theme
	 * @return string
	 */
	public function img($img)
	{
		if (is_null($this->getStyle('img', $img)))
		{
			return (null);
		}

		if (preg_match('#^https?://#i', $this->getStyle('img', $img)))
		{
			return ($this->getStyle('img', $img));
		}

		$cur_style = $this->getStyle('img', $img);
		if (preg_match('#USER_LANGUAGE#', $cur_style))
		{
			if (file_exists(ROOT . 'tpl/' . $this->data['u_tpl'] . '/img/' . $this->data['u_language'] . '/' . $cur_style))
			{
				$cur_style = str_replace('USER_LANGUAGE', $this->data['u_language'], $cur_style);
			}
			else
			{
				$cur_style = str_replace('USER_LANGUAGE', Fsb::$cfg->get('default_lang'), $cur_style);
			}
		}
		return (ROOT . 'tpl/' . $this->data['u_tpl'] . '/img/' . $cur_style);
	}

	/**
	 * Mise a jour de la derniere visite
	 *
	 * @param int $u_id ID du membre
	 */
	private function update_last_visit($u_id)
	{
		if (Fsb::$mods->is_active('update_last_visit') && $u_id <> VISITOR_ID)
		{
			Fsb::$db->update('users', array(
				'u_last_visit' =>	CURRENT_TIME,
			), 'WHERE u_id = ' . $u_id);
			$this->data['u_last_visit'] = CURRENT_TIME;
		}
	}

	/**
	 * Verifie si le visiteur actuel est un robot de referencement ou non. 
	 *
	 * @return int Si c'est le cas on renvoie son ID, sinon on renvoie 0
	 */
	public function is_bot()
	{
		$sql = 'SELECT bot_id, bot_ip, bot_agent
				FROM ' . SQL_PREFIX . 'bots';
		$result = Fsb::$db->query($sql, 'bot_');
		while ($row = Fsb::$db->row($result))
		{
			if (strpos(strtolower($this->user_agent), strtolower($row['bot_agent'])) !== false)
			{
				Fsb::$db->free($result);
				return ($row['bot_id']);
			}
			else if ($row['bot_ip'])
			{
				$split_ip = explode('|', $row['bot_ip']);
				foreach ($split_ip AS $ip)
				{
					if (strpos($this->ip, $ip) === 0)
					{
						Fsb::$db->free($result);
						return ($row['bot_id']);
					}
				}
			}
		}
		Fsb::$db->free($result);
		return (0);
	}

	/**
	 * Verifie si le membre est connecte
	 *
	 * @return bool
	 */
	public function is_logged()
	{
		return (($this->id() != VISITOR_ID) ? true : false);
	}

	/**
	 * Recupere l'ID du visiteur
	 *
	 * @return int
	 */
	public function id()
	{
		return ($this->data['u_id']);
	}

	/**
	 * Recupere le niveau de droits du visiteur
	 *
	 * @return int
	 */
	public function auth()
	{
		return ($this->data['u_auth']);
	}

	/**
	 * Verifie si le membre est fondateur
	 *
	 * @param int $user_id ID du membre
	 * @return bool
	 */
	public function is_fondator($user_id = null)
	{
		if (is_null($user_id))
		{
			return ((Fsb::$session->auth() == FONDATOR) ? true : false);
		}
		else
		{
			$sql = 'SELECT u_auth
					FROM ' . SQL_PREFIX . 'users
					WHERE u_id = ' . $user_id;
			return ((Fsb::$db->get($sql, 'u_auth') == FONDATOR) ? true : false);
		}
	}

	/**
	 * Verifie si le membre peut supprimer un message
	 *
	 * @param int $user_id ID du membre
	 * @param int $post_id ID du message
	 * @param array $topic_data Informations sur le sujet
	 * @return bool
	 */
	public function can_delete_post($user_id, $post_id, &$topic_data)
	{
		if (($this->is_authorized($topic_data['f_id'], 'ga_moderator')
				|| ($this->is_authorized($topic_data['f_id'], 'ga_delete')
					&& $user_id == $this->id()
					&& $topic_data['t_status'] == UNLOCK
					&& (!Fsb::$cfg->get('delete_only_last_post') || $post_id == $topic_data['t_last_p_id'])))
			&& ($post_id != $topic_data['t_first_p_id']
				|| ($post_id == $topic_data['t_first_p_id']
				&& $topic_data['t_total_post'] == 1)))
		{
			return (true);
		}
		return (false);
	}

	/*
	** 
	** -----
	** $format ::		
	*/
	/**
	 * Retourne la liste des forums moderes
	 *
	 * @param bool $format Si false on retourne un tableau, si true on utilise implode()
	 * @return string|array
	 */
	public function moderated_forums($format = true)
	{
		$list = array(0);
		foreach ($this->data['auth'] AS $k => $v)
		{
			if (is_int($k))
			{
				if ($this->is_authorized($k, 'ga_moderator'))
				{
					$list[] = $k;
				}
			}
		}
		return (($format) ? implode(', ', $list) : $list);
	}

	/**
	 * Charge un fichier de langue
	 *
	 * @param string $lg_name Nom du fichier de langue
	 * @param bool $full_path Si  true , utilise $lg_name comme chemin complet vers le fichier de langue
	 */
	public function load_lang($lg_name, $full_path = false)
	{
		$path = ($full_path) ? $lg_name : ROOT . 'lang/' . $this->data['u_language'] . '/' . $lg_name . '.' . PHPEXT;
		if (file_exists($path))
		{
			$this->lg += include($path);
		}
	}

	/**
	 * Retourne la valeur d'une clef de langue
	 *
	 * @param string $key Clef de langue
	 * @return string
	 */
	public function lang($key)
	{
		return ((isset($this->lg[$key])) ? $this->lg[$key] : null);
	}

	/**
	 * Retourne le style dans la configuration du theme
	 *
	 * @param string $cat Categorie de configuration
	 * @param string $key Clef de configuration
	 * @return string
	 */
	public function getStyle($cat, $key)
	{
		return ((isset($this->style[$cat], $this->style[$cat][$key])) ? $this->style[$cat][$key] : null);
	}

	/**
	 * Retourne une date a partir d'un timestamp.
	 * Si la date corespond a aujourd'hui on affiche le texte aujourd'hui, idem pour hier.
	 * De plus la date a un format fixe definit dans le fichier de langue, permettant de traduire le mois
	 *
	 * @param int $timestamp Timestamp pour la date. S'il n'est pas fourni, le timestamp actuel sera utilise.
	 * @param bool $show_hour Ajoute ou non l'heure a la suite de la date
	 * @param string $format Format de la date si on doit le mettre manuellement
	 * @param bool $extra_format Peut utiliser un format extra (Aujourd'hui a [...], Hier a [...])
	 * @return string
	 */
	public function print_date($timestamp, $show_hour = true, $format = null, $extra_format = true)
	{
		// Pour eviter un bug sous windows + PHP4
		if (!$timestamp && OS_SERVER == 'windows')
		{
			$timestamp = ONE_DAY;
		}

		// En fonction du fuseau horaire on converti le timestamp
		$dst = (($this->is_logged()) ? ($this->data['u_utc'] + $this->data['u_utc_dst']) : (Fsb::$cfg->get('default_utc') + Fsb::$cfg->get('default_utc_dst'))) * ONE_HOUR;
		$timestamp = $timestamp + $dst;

		// Formatage impose ?
		if ($format)
		{
			return (gmdate($format, $timestamp));
		}

		// Affichage de l'heure en format extra
		$this_day = gmmktime(0, 0, 0, gmdate('m', CURRENT_TIME + $dst), gmdate('j', CURRENT_TIME + $dst), gmdate('Y', CURRENT_TIME + $dst));
		$time_day = gmmktime(0, 0, 0, gmdate('m', $timestamp), gmdate('j', $timestamp), gmdate('Y', $timestamp));
		$diff_time = (CURRENT_TIME + $dst) - $timestamp;
		if ($extra_format && $show_hour && $diff_time < ONE_HOUR && $diff_time >= 0)
		{
			$min = floor((CURRENT_TIME + $dst - $timestamp) / 60);
			if ($min <= 0)
			{
				$min = 1;
			}
			$date = sprintf(String::plural('few_minute', $min), $min) . ', ' . gmdate('G:i', $timestamp);
		}
		else if ($extra_format && $time_day == $this_day)
		{
			$date = Fsb::$session->lang('today') . (($show_hour) ? ', ' . gmdate('G:i', $timestamp) : '');
		}
		else if ($extra_format && $time_day == ($this_day - ONE_DAY))
		{
			$date = Fsb::$session->lang('yesterday') . (($show_hour) ? ', ' . gmdate('G:i', $timestamp) : '');
		}
		else
		{
			$date = sprintf(Fsb::$session->lang('format_date'), gmdate('d', $timestamp), Fsb::$session->lang('month_' . gmdate('n', $timestamp)), gmdate('Y', $timestamp)) . (($show_hour) ? ', ' . gmdate('G:i', $timestamp) : '');
		}

		return ($date);
	}
}

/* EOF */
