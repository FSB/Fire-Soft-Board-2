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
 * Couche d'abstraction pour base de donnee
 */
abstract class Dbal extends Fsb_model
{
	/**
	 * ID de ressource de la connexion
	 *
	 * @var resource
	 */
	protected $id = null;
	
	/**
	 * Nombre de requetes executees sur la page
	 *
	 * @var int
	 */
	public $count = 0;

	/**
	 * Chaine de caractere contenant les informations de debugage des requetes
	 *
	 * @var string
	 */
	private $debug_str = '';

	/**
	 * Peut utiliser les requetes explain
	 *
	 * @var bool
	 */
	public $can_use_explain = false;

	/**
	 * Peut utiliser les requetes REPLACE
	 *
	 * @var bool
	 */
	public $can_use_replace = false;

	/**
	 * Peut utiliser les multi-insertions
	 *
	 * @var bool
	 */
	public $can_use_multi_insert = false;

	/**
	 * Peut utiliser des requetes TRUNCATE
	 *
	 * @var bool
	 */
	public $can_use_truncate = false;

	/**
	 * Contient les requetes en cache
	 *
	 * @var array
	 */
	private $cache_query;

	/**
	 * Dernier resultat de requete
	 *
	 * @var int
	 */
	private $result_query = 0;

	/**
	 * Iterateur pour les requetes en cache
	 *
	 * @var array
	 */
	private $iterator_query = array();

	/**
	 * Contient les informations pour la multi-insertion
	 *
	 * @var array
	 */
	protected $multi_insert = array();

	/**
	 * Mise en cache pour acceder a l'offset d'un champ a partir de son nom
	 *
	 * @var array
	 */
	private $cache_field_type = array();

	/**
	 * Definit si on est dans une transaction
	 *
	 * @var bool
	 */
	protected $in_transaction = false;

	/**
	 * @var Cache
	 */
	public $cache = null;
	
	/**
	 * Prefixe SQL pour les noms de table
	 *
	 * @var string
	 */
	public $sql_prefix = '';

	/**
	 * Execute une requete SQL
	 *
	 * @param string $sql Requete SQL
	 * @param bool $buffer Mise en bufferisation de la requete
	 * @return resource Resultat de la requete
	 */
	abstract public function _query($sql, $buffer = true);
	
	/**
	 * Execute une simple requete directement
	 *
	 * @param string $sql Requete SQL
	 * @return Resultat de la requete
	 */
	abstract public function simple_query($sql);
	
	/**
	 * @see Dbal::row()
	 */
	abstract public function _row($result, $function = 'assoc');
	
	/**
	 * @see Dbal::free()
	 */
	abstract public function _free($result);
	
	/**
	 * Retourne l'ID de la derni�re auto incr�mentation
	 */
	abstract public function last_id();
	
	/**
	 * @see Dbal::count()
	 */
	abstract public function _count($result);
	
	/**
	 * Protege la chaine de caractere contre les injections SQL
	 *
	 * @param string $str Chaine a proteger
	 * @return string
	 */
	abstract public function escape($str);
	
	/**
	 * Recupere le nombre de lignes affectees par une requete de mise a jour de la table (UPDATE par exemple)
	 *
	 * @param resource $result Resultat de la requete
	 * @return int
	 */
	abstract public function affected_rows($result);
	
	/**
	 * Renvoie le type d'un champ
	 *
	 * @param resource $result Resultat de la requete
	 * @param string $field Nom du champ
	 * @param string $table Nom de la table du champ
	 * @return string Type du champ
	 */
	abstract public function field_type($result, $field, $table = null);
	
	/**
	 * Recupere si le champ est une chaine ou un entier
	 *
	 * @param resource $result Resultat de la requete
	 * @param string $field Nom du champ
	 * @param string $table Nom de la table du champ
	 * @return string int ou string
	 */
	abstract public function get_field_type($result, $field, $table = null);

	/**
	 * Recupere la liste des tables du forum
	 *
	 * @param bool $limit si false, recupere l'ensemble des tables de la base de donnee
	 * @return array
	 */
	abstract public function list_tables($limit = true);
	
	/**
	 * Lance la requete de multi-insertion
	 */
	abstract public function query_multi_insert();

	/**
	 * Retourne la derniere erreur SQL
	 *
	 * @return string
	 */
	abstract public function sql_error();
	
	/**
	 * @see Dbal::close()
	 */
	abstract public function _close();
	
	/**
	 * Gestion des transactions
	 *
	 * @param string $type begin, commit ou rollback
	 */
	abstract public function transaction($type);
	
	/**
	 * Supprime plusieurs lignes sur plusieurs tables, liees par un champ
	 *
	 * @param string $default_table Table principale
	 * @param string $default_where Clause WHERE sur la table par defaut pour limiter la suppression
	 * @param unknown_type $delete_join Tableau associatif contenant en clef les champs et en valeur des tableaux de tables SQL
	 */
	abstract public function delete_tables($default_table, $default_where, $delete_join);
	
	/**
	 * Abstraction sur le mot clef LIKE suivant la base
	 * 
	 * @return string
	 */
	abstract public function like();
	
	/**
	 * Destructeur, ferme la connexion a la base de donnee
	 */
	public function __destruct()
	{
		if ($this->_get_id())
		{
			$this->close();
		}
	}

	/**
	 * Design pattern factory, retourne une instance de la classe Dbal en fonction des bons parametres
	 *
	 * @param string $sql_server Adresse du serveur
	 * @param string $sql_login Login de connexion
	 * @param string $sql_pass Mot de passe de connexion
	 * @param string $sql_db Nom de la base de donnee
	 * @param int $sql_port Port de connexion
	 * @param bool $use_cache Utilisation du cache
	 * @return Dbal
	 */
	public static function factory($sql_server = null, $sql_login = null, $sql_pass = null, $sql_db = null, $sql_port = null, $use_cache = true)
	{
		$sql_server =	(is_null($sql_server)) ? SQL_SERVER : $sql_server;
		$sql_login =	(is_null($sql_login)) ? SQL_LOGIN : $sql_login;
		$sql_pass =		(is_null($sql_pass)) ? SQL_PASS : $sql_pass;
		$sql_db =		(is_null($sql_db)) ? SQL_DB : $sql_db;
		$sql_port =		(is_null($sql_port)) ? SQL_PORT : $sql_port;

		$classname = 'Dbal_' . SQL_DBAL;
		$instance = new $classname($sql_server, $sql_login, $sql_pass, $sql_db, $sql_port, $use_cache);
		$instance->sql_prefix = SQL_PREFIX;
		return ($instance);
	}

	/**
	 * Execute une requete SQL avec gestion du cache et du debugage
	 *
	 * @param string $sql Requete SQL
	 * @param string $cache_prefix Si cet argument est passe la requete est mise en cache avec comme prefixe le chaine donnee
	 * @return resource Pointe sur le resultat de la requete
	 */
	public function query($sql, $cache_prefix = null)
	{
		if (!$this->use_cache)
		{
			$cache_prefix = null;
		}
		// Instance du cache
		else if (is_null($this->cache))
		{
			$this->cache = Cache::factory('sql');
		}

		// On calcul un hash MD5 de la requete
		$hash_query = md5($sql);

		// DEBUG
		if (Fsb::$debug->debug_query)
		{
			$result_explain = null;
			$start_query = 0;
			$this->debug_query($sql, $result_explain, $start_query);
		}

		// On regarde si un cache de la requete existe
		if ($cache_prefix && $this->cache->exists($cache_prefix . $hash_query))
		{
			$this->result_query++;
			$this->cache_query[$this->result_query] = $this->cache->get($cache_prefix . $hash_query);
			$this->iterator_query[$this->result_query] = 0;
			if (Fsb::$debug->debug_query)
			{
				$this->count++;
			}

			$result = $this->result_query;
		}
		else
		{
			$buffer = (preg_match('#^(SELECT|SHOW)#i', trim($sql))) ? true : false;
			$result = $this->_query($sql, $buffer);
			$this->count++;
			if ($cache_prefix)
			{
				// On met la requete en cache
				$data = $this->rows($result, 'array');
				$this->cache->put($cache_prefix . $hash_query, $data, $sql);

				// On recupere tout de meme le resultat de la requete
				$this->result_query++;
				$this->cache_query[$this->result_query] = $data;
				$this->iterator_query[$this->result_query] = 0;
				$result = $this->result_query;
			}
		}

		// DEBUG
		if (Fsb::$debug->debug_query)
		{
			$this->debug_query($sql, $result_explain, $start_query, $result);
		}

		return ($result);
	}

	/**
	 * Construit une requete INSERT / REPLACE a partir d'un tableau.
	 *
	 * @param string $table Nom de la table
	 * @param array $ary Tableau contenant en clef les champs de la requete et en valeur les valeurs pour la requete
	 * @param string $insert INSERT ou REPLACE
	 * @param bool $multi_insert true pour une insertion multiple, a utiliser avec la methode Dbal::query_multi_insert()
	 * @return resource
	 */
	public function insert($table, $ary, $insert = 'INSERT', $multi_insert = false)
	{
		if ($insert == 'REPLACE' && !$this->can_use_replace)
		{
			$where_str = 'WHERE ';
			foreach ($ary AS $key => $value)
			{
				// Si le champ est un index ($value[1] vaut true)
				if (is_array($value) && $value[1] == true)
				{
					$index_field = $key;
					$value = $value[0];
					$where_str .= "$key = '$value' AND ";
				}
			}
			$where_str = substr($where_str, 0, -4);

			$select_table = $this->sql_prefix . $table;
			$sql = "SELECT $index_field FROM $select_table $where_str";
			$result = $this->query($sql);
			$data = $this->row($result);
			if (empty($data[$index_field]))
			{
				return ($this->insert($table, $ary, 'INSERT'));
			}
			else
			{
				return ($this->update($table, $ary, $where_str));
			}
		}
		else
		{
			$fields = '';
			$values = '';
			foreach ($ary AS $key => $value)
			{
				$fields .= $key . ', ';

				if (is_array($value))
				{
					$value = $value[0];
				}
				$values .= ((is_int($value)) ? $value . ', ' : '\'' . $this->escape($value) . '\', ');
			}
			$fields = substr($fields, 0, -2);
			$values = substr($values, 0, -2);

			if ($multi_insert && $this->can_use_multi_insert)
			{
				if (!$this->multi_insert)
				{
					$this->multi_insert = array(
						'insert' => $insert,
						'table' =>	$table,
						'fields' =>	$fields,
						'values' =>	array(),
					);
				}

				$this->multi_insert['values'][] = $values;
			}
			else
			{
				$sql = $insert . ' INTO ' . $this->sql_prefix . $table . "
								($fields)
							VALUES ($values)";
				return ($this->query($sql));
			}
		}
	}

	/**
	 * Construit une requete UPDATE a partir d'un tableau
	 *
	 * @param string $table Nom de la table
	 * @param array $ary Tableau contenant en clef les champs de la requete et en valeur les valeurs pour la requete
	 * @param string $where Clause where de la requete
	 * @return resource
	 */
	public function update($table, $ary, $where = '')
	{
		$sql = 'UPDATE ' . $this->sql_prefix . $table . ' SET ';
		foreach ($ary AS $key => $value)
		{
			$is_field = false;
			if (is_array($value))
			{
				$is_field = (isset($value['is_field']) && $value['is_field']) ? true : false;
				$value = $value[0];
			}
			
			if ($is_field)
			{
				$sql .= ' ' . $key . ' = ' . $value . ', ';
			}
			else
			{
				$sql .= (is_int($value) || is_bool($value)) ? ' ' . $key . ' = ' . (int) $value . ', ' : ' ' . $key . ' = \'' . $this->escape($value) . '\', ';
			}
		}
		$sql = substr($sql, 0, -2);
		$sql .= ' ' . $where;
		return ($this->query($sql));
	}

	/**
	 * Vide une table
	 *
	 * @param string $table Nom de la table
	 */
	public function query_truncate($table)
	{
		if ($this->can_use_truncate)
		{
			$this->query('TRUNCATE ' . $this->sql_prefix . $table);
		}
		else
		{
			$this->query('DELETE FROM ' . $this->sql_prefix . $table);
		}
	}

	/**
	 * Retourne une ligne du resultat et deplace le pointeur vers la ligne suivante.
	 *
	 * @param resource $result Resultat d'une requete
	 * @param string $function Formation des index du tableau de retour (assoc | row | array)
	 * @return array
	 */
	public function row($result, $function = 'assoc')
	{
		if (is_int($result) && isset($this->cache_query[$result]))
		{
			if (isset($this->cache_query[$result][$this->iterator_query[$result]]))
			{
				return ($this->cache_query[$result][$this->iterator_query[$result]++]);
			}
			return (null);
		}
		else
		{
			return ($this->_row($result, $function));
		}
	}

	/**
	 * Recupere toutes les lignes du resultat
	 *
	 * @param resource $result Resultat d'une requete
	 * @param string $function Formation des index du tableau de retour (assoc | row | array)
	 * @param string $field_name Le tableau sera associe aux valeurs de ce champ si le parametre est passe
	 * @return array
	 */
	public function rows($result, $function = 'assoc', $field_name = null)
	{
		$data = array();
		while ($tmp = $this->row($result, $function))
		{
			if ($field_name)
			{
				$data[$tmp[$field_name]] = $tmp;
			}
			else
			{
				$data[] = $tmp;
			}

			unset($tmp);
		}
		$this->free($result);
		return ($data);
	}

	/**
	 * Libere la memoire allouee pour le resultat d'une requete
	 *
	 * @param resource $result Resultat d'une requete
	 */
	public function free($result)
	{
		$this->_free($result);

		if (is_int($result) && isset($this->cache_query[$result]))
		{
			unset($this->cache_query[$result], $this->iterator_query[$result]);
		}
	}

	/**
	 * Retourne un ou plusieurs champs d'une requete.
	 * Par exemple :
	 * <code>
	 *  $sql = 'SELECT field FROM table WHERE field = xxx';
	 *  $field = Fsb::$db->get($sql, 'field');
	 * </code>
	 * ou
	 * <code>
	 *  $sql = 'SELECT field1, field2 FROM table WHERE field = xxx';
	 *  list($field1, $field2) = Fsb::$db->get($sql, array('field1', 'field2'));
	 * </code>
	 * 
	 * @param string $query Requete SQL
	 * @param array|string $fields
	 * @return mixed
	 */
	public function get($query, $fields)
	{
		$result = $this->query($query);
		$row = $this->row($result);
		$this->free($result);

		if (!$row)
		{
			return (null);
		}

		if (is_array($fields))
		{
			$return = array();
			foreach ($fields AS $field)
			{
				$return[] = $row[$field];
			}
		}
		else
		{
			$return = $row[$fields];
		}
		return ($return);
	}

	/**
	 * Retourne la premiere ligne d'une requete
	 *
	 * @param string $query Requete SQL
	 * @return array
	 */
	public function request($query)
	{
		$result = $this->query($query);
		$row = $this->row($result);
		$this->free($result);

		return ($row);
	}

	/**
	 * Detruit les requetes mises en cache
	 *
	 * @param string $prefix Prefixe des requetes a detruire
	 */
	public function destroy_cache($prefix = null)
	{
		if ($this->cache)
		{
			$this->cache->destroy($prefix);
		}
	}

	/**
	 * Renvoie le nombre de ligne retournee par une requete SELECT
	 *
	 * @param resource $result Resultat d'une requete
	 * @return int
	 */
	public function count($result)
	{
		return ((is_int($result) && isset($this->cache_query[$result])) ? count($this->cache_query[$result]) : $this->_count($result));
	}

	/**
	 * Ferme la connexion a la base de donnee
	 */
	public function close()
	{		
		if (Fsb::$debug->_get_debug_query())
		{
			echo $this->debug_str;
		}

		unset($this->cache_field_type, $this->cache_query, $this->iterator_query);
		$this->_close();
		$this->id = null;
	}

	/**
	 * Permet d'afficher le debugage d'une requete
	 *
	 * @param string $sql Requete SQL
	 * @param resource $result_explain Resultat de la requete EXPLAIN
	 * @param int $start_query Temps de debut de la requete<b></b>
	 * @param resource $result Resultat de la requete
	 */
	private function debug_query($sql, &$result_explain, &$start_query, $result = 0)
	{
		if (!$result)
		{
			/*
			** EXPLAIN semble n'etre supporte que par MySQL actuellement
			*/
			if ($this->can_use_explain)
			{
				if (preg_match('/(UPDATE|DELETE FROM)\s+([a-zA-Z0-9_]*?)\s+WHERE\s+(.*?)$/si', $sql, $match))
				{
					$sql_explain = 'SELECT * FROM ' . $match[2] . ' WHERE ' . $match[3];
				}
				else
				{
					$sql_explain = $sql;
				}

				if (preg_match('/^SELECT /si', $sql_explain))
				{
					$result_explain = $this->simple_query('EXPLAIN ' . $sql_explain);
				}
			}
			else
			{
				$result = true;
			}

			$start_query = microtime( true );
		}
		else
		{
			$is_cache = (is_int($result) && isset($this->cache_query[$result])) ? true : false;
			$total_time = substr(microtime( true ) - $start_query, 0, 10);
			$this->debug_str .= '<table cellspacing="0" cellpadding="3" style="width: 100%; border: 1px ' . (($is_cache) ? 'dashed' : 'solid') . ' #000000;"><tr><th style="background-color: #EEEEEE; border: 1px ' . (($is_cache) ? 'dashed' : 'solid') . ' #000000; border-width: 0px 0px 1px 0px">Requete numero ' . ($this->count) . '</th></tr><tr><td style="background-color: #EEEEFF; border: 1px ' . (($is_cache) ? 'dashed' : 'solid') . ' #000000; border-width: 0px 0px 1px 0px">' . htmlspecialchars($sql) . '</td></tr>';
			if ($result_explain && $this->can_use_explain)
			{
				$print_fields = false;
				while ($row = $this->row($result_explain))
				{
					if (!$print_fields)
					{
						$this->debug_str .= '<tr><td style="background-color: #EEFFFF"><table width="100%"><tr>';
						foreach ($row AS $field => $v)
						{
							$this->debug_str .= '<td align="center">' . $field . '</td>';
						}
						$this->debug_str .= '</tr>';
						$print_fields = true;
					}

					$this->debug_str .= '<tr>';
					foreach ($row AS $value)
					{
						$this->debug_str .= '<td align="center">' . $value . '</td>';
					}
					$this->debug_str .= '</tr>';
				}
				$this->debug_str .= '</table></td></tr>';
			}
			$this->debug_str .= '<tr><td style="background-color: #EEEEFF; border: 1px ' . (($is_cache) ? 'dashed' : 'solid') . ' #000000; border-width: ' . (($result_explain && $this->can_use_explain) ? '1' : '0') . 'px 0px 0px 0px">Lignes affectees : ' . ((preg_match('/^SELECT/i', $sql)) ? $this->count($result) : $this->affected_rows($result_explain)) . (($is_cache) ? ' | Requete en cache' : ' | Requete executee en ' . $total_time . ' Secondes') . '</td></tr></table><br />';
		}
	}
}

/*
** Creations de requetes SQL SELECT dynamiques.
** A n'utiliser que sur des requetes dynamiques / difficiles de lectures.
*/
class Sql_select extends Fsb_model
{
	public $query = '';
	public $fields = '';
	public $join = '';
	public $where = '';
	public $order = '';
	public $group = '';
	public $limit = '';
	public $sql_prefix = '';

	/*
	** Constructeur
	** -----
	** $select_state ::		Clause de selection de la requete (SELECT DISTINCT par exemple)
	*/
	public function __construct($select_state = 'SELECT')
	{
		$this->query = $select_state . ' ';
		$this->sql_prefix = Fsb::$db->sql_prefix;
	}

	/*
	** Ajoute une table a la requete
	** -----
	** $join_state ::		Liaison de la table dans la requete (FROM, LEFT JOIN, INNER JOIN, etc...)
	** $tablename ::		Nom de la table
	** $fields ::			Champs a selectionner
	** $on ::				Jointure de la table
	*/
	public function join_table($join_state, $tablename, $fields = '', $on = '')
	{
		$this->fields .= ($this->fields && $fields) ? ', ' . $fields : $fields;
		$this->join .= (($this->join) ? "\n" : '') . $join_state . ' ' . $this->sql_prefix . $tablename . (($on) ? "\n" . $on : '');
	}

	/*
	** Rempli la clause WHERE
	** -----
	** $str ::		Chaine de caractere
	*/
	public function where($str)
	{
		$this->where .= ((!$this->where) ? 'WHERE ' . $str : $str) . ' ';
	}

	/*
	** Rempli la clause GROUP BY
	** -----
	** $str ::		Chaine de caractere
	*/
	public function group_by($str)
	{
		$this->group .= ((!$this->group) ? 'GROUP BY ' . $str : $str) . ' ';
	}

	/*
	** Rempli la clause ORDER BY
	** -----
	** $str ::		Chaine de caractere
	*/
	public function order_by($str)
	{
		$this->order .= ((!$this->order) ? 'ORDER BY ' . $str : $str) . ' ';
	}

	/*
	** Rempli la clause GROUP BY
	** -----
	** $first ::	Premier offset pour la limite
	** $second ::	Second offset pour la limite
	*/
	public function limit($first, $second)
	{
		$this->limit .= 'LIMIT ' . $first . (($second) ? ', ' . $second : '');
	}

	/*
	** Execute la requete
	** -----
	** $get ::			Utilisation de Sql_dbal::get() 
	** $cache_query ::	Prefixe de la requete si on souhaite la mettre en cache
	*/
	public function execute($get = '', $cache_query = '')
	{
		foreach (array('fields', 'join', 'where', 'group', 'order', 'limit') AS $property)
		{
			if ($this->$property)
			{
				$this->query .= $this->$property . "\n";
			}
		}

		if ($get)
		{
			return (Fsb::$db->get($this->query, $get));
		}
		return (Fsb::$db->query($this->query, $cache_query));
	}
}

/* EOF */