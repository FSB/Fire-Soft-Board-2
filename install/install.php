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
 * Procedure d'installation du forum
 */

if (!defined('ROOT'))
{
	die(utf8_decode('This file must be included.<hr />Ce fichier doit etre inclus'));
}

if (!ini_get('date.timezone'))
{
	date_default_timezone_set('Europe/Brussels');
}

/**
 * Methode magique permettant le chargement dynamique de classes
 *
 * @param string $classname
 */
function __autoload($classname)
{
	$classname = strtolower($classname);
	fsb_import($classname);
}

/**
 * Permet d'acceder partout aux variables globales necessaires au fonctionement du forum
 *
 */
class Fsb extends Fsb_model
{
	/**
	 * @var Config
	 */
	public static $cfg;
	
	/**
	 * @var Dbal
	 */
	public static $db;

	/**
	 * @var Debug
	 */
	public static $debug;

	/**
	 * @var Fsb_frame
	 */
	public static $frame;
	
	/**
	 * @var Adm_menu
	 */
	public static $menu;

	/**
	 * @var Mods
	 */
	public static $mods;
	
	/**
	 * @var Session
	 */
	public static $session;
	
	/**
	 * @var Tpl
	 */
	public static $tpl;
}

/**
 * Inclus un fichier dans le dossier main/ de facon inteligente
 *
 * @param string $filename
 */
function fsb_import($filename)
{
	static $store;

	if (!isset($store[$filename]))
	{
		$split = explode('_', $filename);
		if (file_exists(ROOT . 'main/class/class_' . $filename . '.' . PHPEXT))
		{
			include_once(ROOT . 'main/class/class_' . $filename . '.' . PHPEXT);
		}
		else if (file_exists(ROOT . 'main/class/' . $split[0] . '/' . $filename . '.' . PHPEXT))
		{
			include_once(ROOT . 'main/class/' . $split[0] . '/' . $filename . '.' . PHPEXT);
		}
		else if (file_exists(ROOT . 'main/' . $split[0] . '/' . $filename . '.' . PHPEXT))
		{
			include_once(ROOT . 'main/' . $split[0] . '/' . $filename . '.' . PHPEXT);
		}
		else if (file_exists(ROOT . 'main/' . $filename . '.' . PHPEXT))
		{
			include_once(ROOT . 'main/' . $filename . '.' . PHPEXT);
		}
		$store[$filename] = true;
	}
}

// Instance de la classe Debug
Fsb::$debug = new Debug();

// Inclusion des fonctions / classes communes a toutes les pages
fsb_import('csts');
fsb_import('globals');
fsb_import('fcts_common');

// Inclusion des fichiers utiles pour l'installation
if (file_exists(ROOT . 'config/config.' . PHPEXT))
{
	include(ROOT . 'config/config.' . PHPEXT);
}

// Gestionaire d'erreur
set_error_handler(array('Display', 'error_handler'));

// Netoyage des variables GET, POST et COOKIE
Http::clean_gpc();

// Configuration de base pour les classes
$config = array('cache_tpl_type' => 'ftp');

// Instance de la classe template
Fsb::$tpl = new Tpl('./');
Fsb::$tpl->use_cache = false;
Fsb::$tpl->set_file('install.html');

// Langue d'installation
$GLOBALS['lg'] = array();
$GLOBALS['lg'] += include(ROOT . 'lang/fr/lg_common.' . PHPEXT);
$GLOBALS['lg'] += include(ROOT . 'lang/fr/lg_install.' . PHPEXT);

/**
 * Pour simuler Fsb::$session->lang()
 *
 */
class Session extends Fsb_model
{
	public function lang($key)
	{
		return (@$GLOBALS['lg'][$key]);
	}
}
Fsb::$session = new Session();

Http::header('Content-Type', 'text/html; charset=UTF-8');

// Convertisseur ?
if ($convert = Http::request('convert'))
{
	include(ROOT . 'install/convert.' . PHPEXT);
	if (file_exists(ROOT . 'install/converters/convert_' . $convert . '.' . PHPEXT))
	{
		include(ROOT . 'install/converters/convert_' . $convert . '.' . PHPEXT);
		$classname = 'Convert_' . $convert;
		new $classname($convert);
	}
	exit;
}

// Liste des DBMS supportees par FSB
$dbms = array(
	'mysql' =>		'MySQL 4.1+',
	'mysqli' =>		'MySQLi 4.1+',
	'sqlite' =>		'SQLite',
	'pgsql' =>		'PostgreSQL 8',
);

// Fichiers a chmoder
$chmod_files = array(
	'config' =>		array('path' => 'config/config.' . PHPEXT, 'chmod' => 0666),
	'cache_sql' =>	array('path' => 'cache/sql/', 'chmod' => 0777),
	'sql_back' =>	array('path' => 'cache/sql_backup/', 'chmod' => 0777),
	'cache_tpl' =>	array('path' => 'cache/tpl/', 'chmod' => 0777),
	'cache_xml' =>	array('path' => 'cache/xml/', 'chmod' => 0777),
	'cache_diff' =>	array('path' => 'cache/diff/', 'chmod' => 0777),
	'avatars' =>	array('path' => 'images/avatars/', 'chmod' => 0777),
	'ranks' =>		array('path' => 'images/ranks/', 'chmod' => 0777),
	'smilies' =>	array('path' => 'images/smileys/', 'chmod' => 0777),
	'save' =>		array('path' => 'mods/save/', 'chmod' => 0777),
	'upload' =>		array('path' => 'upload/', 'chmod' => 0777),
	'langs' =>		array('path' => 'lang/', 'chmod' => 0777),
	'tpl' =>		array('path' => 'tpl/', 'chmod' => 0777),
	'adm_tpl' =>	array('path' => 'admin/adm_tpl/', 'chmod' => 0777),
);

// Etapes
$steps = array(
	'home' =>	Fsb::$session->lang('step1'),
	'chmod' =>	Fsb::$session->lang('step2'),
	'db' =>		Fsb::$session->lang('step3'),
	'admin' =>	Fsb::$session->lang('step4'),
	'config' =>	Fsb::$session->lang('step5'),
	'end' =>	Fsb::$session->lang('step6'),
);

if (Http::request('go_to_step_chmod', 'post'))
{
	$current_step = 'chmod';
}
else if (Http::request('go_to_step_db', 'post'))
{
	$current_step = 'db';
}
else
{
	$current_step = null;
}

// Chmod ?
if (Http::request('submit_chmod', 'post'))
{
	$ftp_host =			trim(Http::request('ftp_host', 'post'));
	$ftp_login =		trim(Http::request('ftp_login', 'post'));
	$ftp_password =		trim(Http::request('ftp_password', 'post'));
	$ftp_port =			intval(Http::request('ftp_port', 'post'));
	$ftp_path =			trim(Http::request('ftp_path', 'post'));

	// Connexion FTP ?
	if ($ftp_host && (extension_loaded('ftp') || function_exists('fsockopen')))
	{
		$class = (extension_loaded('ftp')) ? 'File_ftp' : 'File_socket';
		$file = new $class();
		$file->connexion($ftp_host, $ftp_login, $ftp_password, $ftp_port, $ftp_path);
	}
	else
	{
		$file = new File_local();
		$file->connexion('', '', '', '', ROOT);
	}

	// Chmod des fichiers
	foreach ($_POST AS $k => $v)
	{
		if (isset($chmod_files[$k]) && $v)
		{
			$file->chmod($chmod_files[$k]['path'], $chmod_files[$k]['chmod'], false);
		}
	}

	$current_step = 'chmod';
}

/**
 * Configuration
 *
 * @param bool $quick
 */
function install_config($quick = false)
{
	if (!$quick)
	{
		$config_mail =		trim(Http::request('config_email', 'post'));
		$config_path =		trim(Http::request('config_path', 'post'));
		$config_cookie =	trim(Http::request('config_cookie', 'post'));
		$config_search =	trim(Http::request('config_search', 'post'));
		$default_utc =		intval(Http::request('default_utc', 'post'));
		$default_utc_dst =	intval(Http::request('default_utc_dst', 'post'));
		$config_rewriting =	intval(Http::request('config_rewriting', 'post'));
		$menu_webftp =		(Http::request('menu_webftp', 'post') == 'fondator') ? FONDATOR : ADMIN;
		$menu_sql =			(Http::request('menu_sql', 'post') == 'fondator') ? FONDATOR : ADMIN;
	}
	else
	{
		$config_mail =		'root@localhost.com';
		$config_path =		'http://' . dirname(dirname($_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME']));
		$config_cookie =	'fsb2_';
		$config_search =	'fulltext_mysql';
		$default_utc =		1;
		$default_utc_dst =	0;
		$config_rewriting =	false;
		$menu_webftp =		ADMIN;
		$menu_sql =			ADMIN;
	}

	Fsb::$db->update('config', array(
		'cfg_value' =>	$config_mail,
	), 'WHERE cfg_name = \'forum_mail\'');

	Fsb::$db->update('config', array(
		'cfg_value' =>	$config_path,
	), 'WHERE cfg_name = \'fsb_path\'');

	Fsb::$db->update('config', array(
		'cfg_value' =>	$config_cookie,
	), 'WHERE cfg_name = \'cookie_name\'');

	Fsb::$db->update('config', array(
		'cfg_value' =>	$config_search,
	), 'WHERE cfg_name = \'search_method\'');

	Fsb::$db->update('config', array(
		'cfg_value' =>	$default_utc,
	), 'WHERE cfg_name = \'default_utc\'');

	Fsb::$db->update('config', array(
		'cfg_value' =>	$default_utc_dst,
	), 'WHERE cfg_name = \'default_utc_dst\'');

	Fsb::$db->update('mods', array(
		'mod_status' =>		$config_rewriting,
	), 'WHERE mod_name = \'url_rewriting\'');

	Fsb::$db->update('menu_admin', array(
		'auth' =>			$menu_webftp,
	), 'WHERE page = \'tools_webftp\'');

	Fsb::$db->update('menu_admin', array(
		'auth' =>			$menu_sql,
	), 'WHERE page = \'tools_sql\'');

	Fsb::$db->destroy_cache();
}

/**
 * Compte administrateur
 *
 * @param bool $quick
 */
function install_admin($quick = false)
{
	global $email;

	if ($quick)
	{
		$login = 'admin';
		$nickname = 'admin';
		$password = 'admin';
		$password_confirm = 'admin';
		$email = 'root@localhost.com';
	}
	else
	{
		// Donnees pour l'inscription
		$user_data = array('login', 'nickname', 'password', 'password_confirm', 'email');
		foreach ($user_data AS $user_var)
		{
			$$user_var = trim(Http::request($user_var, 'post'));
		}

		// Nickname par defaut ?
		if (!$nickname)
		{
			$nickname = $login;
		}

		// Mots de passe identiques ?
		if ($password != $password_confirm)
		{
			die(Fsb::$session->lang('install_bad_password'));
		}
	}

	// Creation d'un grain pour les mots de passe
	Fsb::$cfg = new Config('config', array('fsb_hash' => substr(md5(rand(0, CURRENT_TIME) . rand(0, CURRENT_TIME)), 0, 10)));

	Fsb::$db->update('users', array(
		'u_nickname' =>		$nickname,
		'u_email' =>		$email,
		'u_joined' =>		CURRENT_TIME,
		'u_register_ip' =>	$_SERVER['REMOTE_ADDR'],
	), 'WHERE u_id = 2');

	Fsb::$db->insert('users_password', array(
		'u_id' =>				array(2, true),
		'u_login' =>			$login,
		'u_password' =>			Password::hash($password, 'sha1', true),
		'u_algorithm' =>		'sha1',
		'u_use_salt' =>			true,
		'u_autologin_key' =>	sha1($login . $password . 2),
	), 'REPLACE');

	Fsb::$cfg->update('last_user_login', $nickname, false);
	Fsb::$cfg->update('fsb_path', 'http://' . dirname(dirname($_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'])), false);
	Fsb::$cfg->update('fsb_hash', Fsb::$cfg->get('fsb_hash'), false);
	Fsb::$cfg->update('register_time', CURRENT_TIME);

	// Mise a jour du message et du sujet
	Fsb::$db->update('topics', array(
		't_time' =>					CURRENT_TIME,
		't_last_p_time' =>			CURRENT_TIME,
		't_last_p_nickname' =>		$nickname,
	), 'WHERE t_id = 1');

	Fsb::$db->update('posts', array(
		'p_time' =>			CURRENT_TIME,
		'p_nickname' =>		$nickname,
		'u_ip' =>			$_SERVER['REMOTE_ADDR'],
	), 'WHERE p_id = 1');

	Fsb::$db->update('forums', array(
		'f_last_p_time' =>			CURRENT_TIME,
		'f_last_p_nickname' =>		$nickname,
	), 'WHERE f_id = 2');

	// Mise a jour du timestamp pour les procedures
	Fsb::$db->update('process', array(
		'process_last_timestamp' =>	CURRENT_TIME,
	));

	Log::add(Log::ADMIN, 'install_fsb');
	
	// Mise a jour de l'utilisateur ayant installe le forum
	Fsb::$db->update('logs', array(
		'u_id' =>			2,
	), 'WHERE log_key = \'install_fsb\'');
		
}

/**
 * Installation de la base de donnee
 *
 * @param string $sql_dbms
 * @param string $sql_server
 * @param string $sql_login
 * @param string $sql_password
 * @param string $sql_dbname
 * @param string $sql_prefix
 * @param string $sql_port
 * @return unknown
 */
function install_database($sql_dbms, $sql_server, $sql_login, $sql_password, $sql_dbname, $sql_prefix, $sql_port)
{
	global $config_code;

	// On commence une transaction
	Fsb::$db->transaction('begin');

	// Execution des requetes d'installations pour la base de donnee
	@set_time_limit(0);
	$queries = String::split(';', file_get_contents('db_schemas/' . $sql_dbms . '_schemas.sql'));
	foreach ($queries AS $query)
	{
		$query = preg_replace('#fsb2_#', $sql_prefix, $query);
		Fsb::$db->query($query);
	}
	unset($queries);

	$queries = String::split(';', file_get_contents('db_schemas/data.sql'));
	foreach ($queries AS $query)
	{
		$query = preg_replace('#fsb2_#', $sql_prefix, $query, 1);
		$query = str_replace(array('\n', '\r'), array("\n", "\r"), $query);
		Fsb::$db->query($query);
	}
	unset($queries);

	// Requetes apres les requetes de donnees ?
	if (file_exists('db_schemas/' . $sql_dbms . '_end.sql'))
	{
		$queries = String::split(';', file_get_contents('db_schemas/' . $sql_dbms . '_end.sql'));
		foreach ($queries AS $query)
		{
			$query = preg_replace('#fsb2_#', $sql_prefix, $query);
			Fsb::$db->query($query);
		}
		unset($queries);
	}

	// On fini la transaction
	Fsb::$db->transaction('commit');

	/*
	** Ecriture du fichier config
	*/
	@chmod(ROOT . 'config/config.' . PHPEXT, 0666);
	$config_code = "<?php\n";
	$config_code .= "define('SQL_LOGIN', '$sql_login');\n";
	$config_code .= "define('SQL_PASS', '$sql_password');\n";
	$config_code .= "define('SQL_SERVER', '$sql_server');\n";
	$config_code .= "define('SQL_DB', '$sql_dbname');\n";
	$config_code .= "define('SQL_PORT', '$sql_port');\n";
	$config_code .= "define('SQL_PREFIX', '$sql_prefix');\n";
	$config_code .= "define('SQL_DBAL', '$sql_dbms');\n\n";
	$config_code .= "define('FSB_INSTALL', 'true');\n";
	
	if (!ini_get('date.timezone'))
	{
		$config_code .= "ini_set('date.timezone', 'Europe/Brussels');\n";
	}
	
	$config_code .= "/* EOF */";

	if (is_writable(ROOT . 'config/config.' . PHPEXT))
	{
		if ($fd = @fopen(ROOT . 'config/config.' . PHPEXT, 'w+'))
		{
			fwrite($fd, $config_code);
			fclose($fd);

			return true;
		}
	}

	return ($config_code);
}

// Soumission des formulaires
if (Http::request('quick_install', 'post'))
{
	if (defined('FSB_INSTALL'))
	{
		trigger_error('Forum deja installe', FSB_ERROR);
	}

	$sql_dbms =			'mysql';
	$sql_server =		'localhost';
	$sql_login =		'root';
	$sql_password =		htmlspecialchars(Http::request('sql_password', 'post'));
	$sql_port =			null;
	$sql_dbname =		'fsb2_' . date('d_m_Y_H\Hi');
	$sql_prefix =		'fsb2_';

	mysql_connect($sql_server, $sql_login, $sql_password);
	$sql = 'CREATE DATABASE ' . $sql_dbname;
	mysql_query($sql) OR die(mysql_error() . '<hr />Impossible de creer la base de donnee ' . $sql_dbname);
	mysql_close();

	define('SQL_DBAL', $sql_dbms);
	define('SQL_LOGIN', $sql_login);
	define('SQL_PASS', $sql_password);
	define('SQL_SERVER', $sql_server);
	define('SQL_DB', $sql_dbname);
	define('SQL_PORT', $sql_port);
	define('SQL_PREFIX', $sql_prefix);
	define('FSB_INSTALL', 'true');
	Fsb::$db = Dbal::factory($sql_server, $sql_login, $sql_password, $sql_dbname, $sql_port, false);

	$result = install_database($sql_dbms, $sql_server, $sql_login, $sql_password, $sql_dbname, $sql_prefix, $sql_port);
	install_admin(true);
	install_config(true);

	if ($result === true)
	{
		Http::header('location', '../index.php');
		exit;
	}
	else
	{
		Fsb::$tpl->set_switch('step_end');
		Fsb::$tpl->set_switch('config_download');
		Fsb::$tpl->set_vars(array(
			'CONFIG_CODE'		=> nl2br(htmlspecialchars($result)),
		));
		Fsb::$tpl->parse();
		exit;
	}
}
else if (Http::request('go_to_step_config', 'post') && defined('FSB_INSTALL'))
{
	Fsb::$db = Dbal::factory();
	if (!Fsb::$db->_get_id())
	{
		trigger_error('Impossible de se connecter a la base de donnee : ' . Fsb::$db->sql_error(), FSB_ERROR);
	}

	install_config();

	$current_step = 'end';
}
else if (Http::request('go_to_step_end', 'post') && defined('FSB_INSTALL'))
{
	Fsb::$db = Dbal::factory();
	if (!Fsb::$db->_get_id())
	{
		trigger_error('Impossible de se connecter a la base de donnee : ' . Fsb::$db->sql_error(), FSB_ERROR);
	}

	install_admin();

	$current_step = 'config';
}
else if (Http::request('go_to_step_admin', 'post') && !defined('FSB_INSTALL'))
{
	/*
	** Connexion a la base de donnee
	*/
	$sql_dbms =			trim(Http::request('sql_dbms', 'post'));
	$sql_server =		trim(Http::request('sql_server', 'post'));
	$sql_login =		trim(Http::request('sql_login', 'post'));
	$sql_password =		trim(Http::request('sql_password', 'post'));
	$sql_dbname =		trim(Http::request('sql_dbname', 'post'));
	$sql_prefix =		trim(Http::request('sql_prefix', 'post'));
	$sql_port =			intval(Http::request('sql_port', 'post'));

	// Si on utilise SQLite on met la base de donnee dans ~/main/dbal/sqlite/
	if ($sql_dbms == 'sqlite')
	{
		$sql_dbname = md5(uniqid(rand(), true)) . '.sqlite';
	}

	define('SQL_DBAL', $sql_dbms);
	define('SQL_PREFIX', $sql_prefix);
	Fsb::$db = Dbal::factory($sql_server, $sql_login, $sql_password, $sql_dbname, $sql_port, false);
	if (!Fsb::$db->_get_id())
	{
		$current_step = 'db';
	}
	// Si MySQL < 4.1, on redirige vers une erreur
	else if ($sql_dbms == 'mysql' && version_compare(Fsb::$db->mysql_version(), '4.1', '<') == 1)
	{
		$sql_error = sprintf(Fsb::$session->lang('install_bad_mysql_version'), Fsb::$db->mysql_version());
		$current_step = 'db';
	}
	else
	{
		// Verification de l'existance de tables ayant le meme prefixe
		$sql = 'SHOW TABLES LIKE \'' . $sql_prefix . '%\'';
		$result = Fsb::$db->query($sql);
		if (Fsb::$db->count($result) > 0)
		{
			$current_step = 'db';
			// ... error
			// TODO message de confirmation Display::confirmation($str, $url, $hidden = array())
			die('Table with prefix already exist. If table used by FSB already exist all of they data will be trashed! To allow this operation, remove this die on line ' . __LINE__ . ' in file ' . __FILE__);
		}
		else
		{
			$write_config = install_database($sql_dbms, $sql_server, $sql_login, $sql_password, $sql_dbname, $sql_prefix, $sql_port);

			$current_step = 'admin';
			unset($db);
		}
	}
}
else if (Http::request('go_to_step_admin', 'post'))
{
	$current_step = 'admin';
	$write_config = true;
}

// Aucun $current_step ?
if (!$current_step)
{
	$current_step = 'home';
}

foreach ($steps AS $k => $step_name)
{
	Fsb::$tpl->set_blocks('step', array(
		'NAME' =>			$step_name,
		'CURRENT_STEP' =>	($current_step == $k) ? true : false,
	));
}

// Affichage des pages
switch ($current_step)
{
	case 'end' :
		// Fin de l'installation du forum
		Fsb::$tpl->set_switch('step_end');

		// Liste des convertisseurs
		if (file_exists(ROOT . 'install/converters/'))
		{
			include(ROOT . 'install/convert.' . PHPEXT);

			$fd = opendir(ROOT . 'install/converters/');
			while ($file = readdir($fd))
			{
				if (preg_match('#^convert_(.*?)\.' . PHPEXT . '$#i', $file, $m))
				{
					include(ROOT . 'install/converters/' . $file);
					$info = call_user_func(array('Convert_' . $m[1], 'forum_type'));
					Fsb::$tpl->set_blocks('convert', array(
						'NAME' =>	$info,
						'URL' =>	'index.' . PHPEXT . '?convert=' . $m[1],
					));
				}
			}
		}
		closedir($fd);
	break;

	case 'config' :
		// Configuration du forum
		Fsb::$tpl->set_switch('step_config');
		Fsb::$tpl->set_vars(array(
			'CONFIG_PATH' =>			'http://' . dirname(dirname($_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'])),
            'CONFIG_COOKIE' =>          'fsb2_' . substr(md5(rand(0, time())), 0, 6),
			'CONFIG_EMAIL' =>			$email,
			'LIST_UTC' =>				Html::list_utc('default_utc', 1, 'utc'),
			'LIST_UTC_DST' =>			Html::list_utc('default_utc_dst', date("I"), 'dst'),
			'MENU_WEBFTP' =>			'admin',
			'MENU_SQL' =>				'admin',
			'USE_FULLTEXT_MYSQL' =>		true,
		));
	break;

	case 'admin' :
		// Troisieme etape, creation du compte administrateur
		Fsb::$tpl->set_switch('step_admin');
		if ($write_config)
		{
			// Si le fichier config a ete ecrit on gere la creation du compte admin
			Fsb::$tpl->set_vars(array(
				'LOGIN' =>				(isset($adm_login)) ? $adm_login : '',
				'EMAIL' =>				(isset($adm_email)) ? $adm_email : '',
			));
		}
		else
		{
			// Si le fichier config n'a pu etre ecrit ...
			Fsb::$tpl->set_switch('config_mode');
			Fsb::$tpl->set_vars(array(
				'CONFIG_CODE' =>		htmlspecialchars($config_code),
			));
		}
	break;

	case 'db' :
		// Seconde etape, la base de donnee
		Fsb::$tpl->set_switch('step_db');

		// Creation de la liste des bases de donnees
		$list_db = '<select name="sql_dbms" id="sql_dbms_id" onchange="db_change(this.value);">';
		$at_least_one_dbms_extension = FALSE;
		foreach ($dbms AS $extension => $db_name)
		{
			if (extension_loaded($extension))
			{
				$list_db .= '<option value="' . $extension . '" ' . (((isset($sql_dbms) && $sql_dbms == $extension) || $extension == 'mysql') ? 'selected="selected"' : '') . '>' . $db_name . '</option>';
				$at_least_one_dbms_extension = TRUE;
			}
		}
		if(!$at_least_one_dbms_extension)
			$list_db = Fsb::$session->lang('install_sql_nosgbd');

		Fsb::$tpl->set_vars(array(
			'LIST_DBMS' =>			$list_db,
			'SQL_PREFIX' =>			(isset($sql_prefix)) ? $sql_prefix : 'fsb2_',
			'SQL_SERVER' =>			(isset($sql_server)) ? $sql_server : 'localhost',
			'SQL_LOGIN' =>			(isset($sql_login)) ? $sql_login : '',
			'SQL_PASSWORD' =>		(isset($sql_password)) ? $sql_password : '',
			'SQL_DBNAME' =>			(isset($sql_dbname)) ? $sql_dbname : '',
			'SQL_PORT' =>			(isset($sql_port)) ? $sql_port : '',
		));

		if (Http::request('go_to_step_admin', 'post'))
		{
			Fsb::$tpl->set_switch('sql_error');
			Fsb::$tpl->set_vars(array(
				'SQL_ERROR' =>		(isset($sql_error)) ? $sql_error : sprintf(Fsb::$session->lang('install_sql_error'), Fsb::$db->sql_error()),
			));
		}
	break;

	case 'chmod' :
		// Gestions des CHMOD sur les fichiers
		Fsb::$tpl->set_switch('step_chmod');

		foreach ($chmod_files AS $k => $f)
		{
			$is_writable = is_writable(ROOT . $f['path']);
			if (!$is_writable && !tpl_switch_exists('chmod_recheck'))
			{
				Fsb::$tpl->set_switch('chmod_recheck');
			}

			Fsb::$tpl->set_blocks('chmod', array(
				'PATH' =>		$f['path'],
				'NAME' =>		$k,
				'CHMOD' =>		'0' . decoct($f['chmod']),
				'WRITE' =>		$is_writable,
				'EXPLAIN' =>	Fsb::$session->lang('install_chmod_' . $k),
			));
		}

		// Un bon informaticien est un informaticien feneant ...
		foreach (array('host', 'login', 'password', 'port', 'path') AS $v)
		{
			Fsb::$tpl->set_vars(array(
				'FTP_' . strtoupper($v) =>		(isset(${'ftp_' . $v})) ? ${'ftp_' . $v} : (($v == 'port') ? '21' : ''), 
			));
		}
	break;

	default :
		// Premiere page de l'installation
		Fsb::$tpl->set_switch('step_home');

		if (IS_LOCALHOST)
		{
			Fsb::$tpl->set_vars(array(
				'INSTALL_QUICK_EXPLAIN' =>	sprintf(Fsb::$session->lang('install_quick_explain'), ('<a href="#db_login_fast" onclick="document.getElementById(\'db_login_fast\').style.display = \'block\'">' . Fsb::$session->lang('install_quick_explain_link') . '</a>'))
			));

			Fsb::$tpl->set_switch('localhost');
		}
	break;
}

Fsb::$tpl->set_vars(array(
	'U_ACTION' =>			'index.' . PHPEXT,
));
Fsb::$tpl->parse();

/* EOF */
