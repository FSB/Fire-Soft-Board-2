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
 * Logs des informations sur le forum
 */
class Log extends Fsb_model
{
	/**
	 * Log des erreurs
	 */
	const ERROR = 0;
	
	/**
	 * Log des actions utilisateur
	 */
	const USER = 1;
	
	/**
	 * Log des actions des moderateurs
	 */
	const MODO = 2;
	
	/**
	 * Log des actions des administrateurs
	 */
	const ADMIN = 3;
	
	/**
	 * Log des emails
	 */
	const EMAIL = 4;

	/**
	 * Association entre le type d'erreur et les clefs de langues
	 *
	 * @var array
	 */
	private static $lg_assoc = array(
		self::ERROR =>	'error',
		self::USER =>	'user',
		self::MODO =>	'modo',
		self::ADMIN =>	'admin',
		self::EMAIL =>	'email',
	);

	/**
	 * Ajout d'une information en log
	 * 
	 * @param int $type Type du log
	 * @param string $key Nom du log
	 * @param mixed ... Arguments additionels en fonction du type de log
	 *
	 */
	public static function add($type, $key)
	{
		// On recupere les arguments de la fonction
		$argv =	array();
		$count = func_num_args();
		for ($i = 2; $i < $count; $i++)
		{
			$argv[] = func_get_arg($i);
		}

		self::add_custom($type, $key, $argv);
	}

	/**
	 * Ajout d'un log d'action utilisateur
	 * 
	 * @param int $user ID du membre implique dans l'action
	 * @param string $key Nom du log
	 * @param mixed ... Arguments additionels en fonction du type de log
	 *
	 */
	public static function user($user, $key)
	{
		// On recupere les arguments de la fonction
		$argv =	array();
		$count = func_num_args();
		for ($i = 2; $i < $count; $i++)
		{
			$argv[] = func_get_arg($i);
		}

		self::add_custom(self::USER, $key, $argv, null, null, $user);
	}

	/**
	 * Ajout du log dans la base
	 *
	 * @param int $type Type de log
	 * @param string $key Nom du log
	 * @param array $argv Arguments additionels
	 * @param int $line Ligne ou se deroule le log
	 * @param string $file Fichier ou se deroule le log
	 * @param int $user ID du membre implique
	 */
	public static function add_custom($type, $key, $argv = array(), $line = null, $file = null, $user = null)
	{
		$user_id = (Fsb::$session && isset(Fsb::$session->data['u_id'])) ? Fsb::$session->id() : VISITOR_ID;
		$user_ip = (Fsb::$session) ? Fsb::$session->_get('ip') : @$_SERVER['REMOTE_ADDR'];

		// les arguments addionels seront serializes dans la base de donnee
		if (Fsb::$db && Fsb::$db->_get_id())
		{
			Fsb::$db->insert('logs', array(
				'log_type' =>	$type,
				'log_key' =>	$key,
				'log_argv' =>	serialize($argv),
				'log_time' =>	CURRENT_TIME,
				'log_line' =>	(int) $line,
				'log_file' =>	$file,
				'log_user' =>	(int) $user,
				'u_id' =>		(int) $user_id,
				'u_ip' =>		$user_ip,
			));
		}
	}

	/**
	 * Recupere un certain type de logs
	 *
	 * @param int $type Type de log
	 * @param int $limit Nombre de lignes du logs a recuperer
	 * @param int $offset A partir de l'enregistrement
	 * @param string $and Condition suplementaire pour la requete
	 * @param bool $get_user Recupere en plus les informations sur le membre
	 * @return array
	 */
	public static function read($type, $limit = 100, $offset = 0, $and = '', $get_user = false)
	{
		Fsb::$session->load_lang('lg_logs');

		$sql = 'SELECT COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'logs
				WHERE log_type = ' . $type;
		$total = Fsb::$db->get($sql, 'total');
		$return = array('total' => $total, 'rows' => array());

		$sql_log_user = $sql_join_log_user = '';
		if ($get_user)
		{
			$sql_log_user = ', u2.u_id AS log_user_id, u2.u_nickname AS log_user_nickname, u2.u_color AS log_user_color';
			$sql_join_log_user = ' LEFT JOIN ' . SQL_PREFIX . 'users u2 ON l.log_user = u2.u_id ';
		}

		$sql = 'SELECT l.*, u.u_nickname, u.u_color' . $sql_log_user . '
				FROM ' . SQL_PREFIX . 'logs l
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = l.u_id
				' . $sql_join_log_user . '
				WHERE l.log_type = ' . $type . '
				' . $and . '
				ORDER BY l.log_time DESC'
				. (($limit > 0) ? ' LIMIT ' . $offset . ', ' . $limit : '');
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$row['log_argv'] = unserialize($row['log_argv']);
			$row['errstr'] = $row['log_key'];
			$key = 'log_' . self::$lg_assoc[$row['log_type']] . '_' . $row['log_key'];
			if (Fsb::$session->lang($key))
			{
				$sprintf = array(Fsb::$session->lang($key));
				foreach ($row['log_argv'] AS $argv)
				{
					$sprintf[] = nl2br(htmlspecialchars($argv));
				}
				$row['errstr'] = call_user_func_array('sprintf', $sprintf);
			}
			$return['rows'][] = $row;
		}
		Fsb::$db->free($result);

		return ($return);
	}
}

/* EOF */