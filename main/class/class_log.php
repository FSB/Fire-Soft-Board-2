<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_log.php
** | Begin :	14/08/2007
** | Last :		22/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion des logs du forum
*/
class Log extends Fsb_model
{
	// Types de logs
	const ERROR = 0;
	const USER = 1;
	const MODO = 2;
	const ADMIN = 3;
	const EMAIL = 4;

	// Association pour les clefs de langue
	private static $lg_assoc = array(
		self::ERROR =>	'error',
		self::USER =>	'user',
		self::MODO =>	'modo',
		self::ADMIN =>	'admin',
		self::EMAIL =>	'email',
	);

	/*
	** Ajout d'un log dans la base de donnée
	** Le nombre d'argument de cette fonction est variable, néanmoins il faut respecter cet ordre :
	**	- Le premier argument doit être une des constante de log
	**	- Le second argument doit être le nom du log
	**	- Les autres arguments sont des paramètres additionels
	*/
	public static function add()
	{
		$count = func_num_args();
		if ($count < 2)
		{
			trigger_error('Au moins 2 paramètres doivent être passés à la méthode Log::add()', FSB_ERROR);
		}

		// On récupère les arguments de la fonction
		$type =	func_get_arg(0);
		$key =	func_get_arg(1);
		$argv =	array();
		for ($i = 2; $i < $count; $i++)
		{
			$argv[] = func_get_arg($i);
		}

		self::add_custom($type, $key, $argv);
	}

	/*
	** Ajout d'un log concernant un membre
	** Le nombre d'argument de cette fonction est variable, néanmoins il faut respecter cet ordre :
	**	- Le premier argument doit être l'ID du membre ciblé
	**	- Le second argument doit être le nom du log
	**	- Les autres arguments sont des paramètres additionels
	*/
	public static function user()
	{
		$count = func_num_args();
		if ($count < 2)
		{
			trigger_error('Au moins 2 paramètres doivent être passés à la méthode Log::add()', FSB_ERROR);
		}

		// On récupère les arguments de la fonction
		$user =	func_get_arg(0);
		$key =	func_get_arg(1);
		$argv =	array();
		for ($i = 2; $i < $count; $i++)
		{
			$argv[] = func_get_arg($i);
		}

		self::add_custom(self::USER, $key, $argv, NULL, NULL, $user);
	}

	/*
	** Ajout d'un log dans la base de donnée
	** -----
	** $type ::		Type de log
	** $key ::		Nom du log
	** $argv ::		Tableau d'arguments
	** $line ::		Ligne où se déroule le log
	** $file ::		Fichier où se déroule le log
	** $user ::		ID du membre si le log concerne un membre particulier
	*/
	public static function add_custom($type, $key, $argv = array(), $line = NULL, $file = NULL, $user = NULL)
	{
		$user_id = (Fsb::$session && isset(Fsb::$session->data['u_id'])) ? Fsb::$session->id() : VISITOR_ID;
		$user_ip = (Fsb::$session) ? Fsb::$session->_get('ip') : @$_SERVER['REMOTE_ADDR'];

		// les arguments addionels seront sérializés dans la base de donnée
		if (Fsb::$db->_get_id())
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

	/*
	** Récupère un certain type de logs
	** -----
	** $type ::		Type de log
	** $limit ::	Nombre de lignes du logs à récupérer
	** $offset ::	A partir de l'enregistrement
	** $and ::		Condition suplémentaire pour la requête
	** $get_user ::	Récupère en plus les informations sur le membre loggué
	*/
	public static function read($type, $limit = 100, $offset = 0, $and = '', $get_user = FALSE)
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