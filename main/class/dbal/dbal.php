<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/dbal/dbal.php
** | Begin :	26/10/2005
** | Last :		28/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Couche d'abstraction pour base de donnée.
*/
abstract class Dbal extends Fsb_model
{
	// ID de ressource de la connexion
	protected $id = NULL;
	
	// Nombres de requètes
	public $count = 0;
	
	// Chaîne de caractère contenant les informations de débugage des requètes
	private $debug_str = '';

	// Peut utiliser les requètes explain
	public $can_use_explain = FALSE;

	// Peut utiliser les requètes REPLACE
	public $can_use_replace = FALSE;

	// Peut utiliser les multi-insertions
	public $can_use_multi_insert = FALSE;

	// Peut utiliser des requetes TRUNCATE
	public $can_use_truncate = FALSE;

	// Tableau contenant les requètes mises en cache
	private $cache_query;

	// Dernier résultat de requète
	private $result_query = 0;

	// Itérateur pour les requètes en cache
	private $iterator_query = array();

	// Tableau de multi insertion
	protected $multi_insert = array();

	// Tableau de mise ne cache pour pouvoir accéder à l'offset d'un champ
	// à partir de son nom en chaîne de caractère
	private $cache_field_type = array();

	// Définit si on est dans une transaction
	protected $in_transaction = FALSE;

	// Objet cache
	public $cache = NULL;

	abstract public function _query($sql, $buffer = TRUE);
	abstract public function simple_query($sql);
	abstract public function _row($result, $function = 'assoc');
	abstract public function _free($result);
	abstract public function last_id();
	abstract public function _count($result);
	abstract public function escape($str);
	abstract public function affected_rows($result);
	abstract public function field_type($result, $field, $table = NULL);
	abstract public function get_field_type($result, $field, $table = NULL);
	abstract public function list_tables();
	abstract public function query_multi_insert();
	abstract public function sql_error();
	abstract public function _close();
	abstract public function transaction($type);
	abstract public function delete_tables($default_table, $default_where, $delete_join);
	abstract public function like();

	/*
	** Destructeur
	*/
	public function __destruct()
	{
		if ($this->_get_id())
		{
			$this->close();
		}
	}

	/*
	** Retourne une instance de la classe Sql sur le bon type de base de donnée
	*/
	public static function factory($sql_server = NULL, $sql_login = NULL, $sql_pass = NULL, $sql_db = NULL, $sql_port = NULL, $use_cache = TRUE)
	{
		$sql_server =	($sql_server === NULL) ? SQL_SERVER : $sql_server;
		$sql_login =	($sql_login === NULL) ? SQL_LOGIN : $sql_login;
		$sql_pass =		($sql_pass === NULL) ? SQL_PASS : $sql_pass;
		$sql_db =		($sql_db === NULL) ? SQL_DB : $sql_db;
		$sql_port =		($sql_port === NULL) ? SQL_PORT : $sql_port;

		$classname = 'Dbal_' . SQL_DBAL;
		return (new $classname($sql_server, $sql_login, $sql_pass, $sql_db, $sql_port, $use_cache));
	}

	/*
	** Execute une requète SQL
	** -----
	** $sql ::			Requète SQL
	** $cache_prefix ::	Chaine de caractère utilisée pour identifier une série de requètes à mettre en cache
	*/
	public function query($sql, $cache_prefix = NULL)
	{
		if (!$this->use_cache)
		{
			$cache_prefix = NULL;
		}
		// Instance du cache
		else if ($this->cache === NULL)
		{
			$this->cache = Cache::factory('sql');
		}

		// On calcul un hash MD5 de la requète
		$hash_query = md5($sql);

		// DEBUG
		if (Fsb::$debug->debug_query)
		{
			$result_explain = NULL;
			$start_query = 0;
			$this->debug_query($sql, $result_explain, $start_query);
		}

		// On regarde si un cache de la requète existe
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
			$buffer = (preg_match('#^(SELECT|SHOW)#i', trim($sql))) ? TRUE : FALSE;
			$result = $this->_query($sql, $buffer);
			$this->count++;
			if ($cache_prefix)
			{
				// On met la requète en cache
				$data = $this->rows($result, 'array');
				$this->cache->put($cache_prefix . $hash_query, $data, $sql);

				// On récupère tout de même le résultat de la requète
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

	/*
	** Construit une requète INSERT / REPLACE à partir d'un tableau.
	** -----
	** $table ::		Nom de la table
	** $ary ::			Tableau contenant en clef les champs de la requète et en
	**						valeur les valeurs pour la requète
	** $insert ::		INSERT ou REPLACE
	** $multi_insert ::	TRUE pour une insertion multiple, à utiliser avec la méthode finale
	**						Dbal::query_multi_insert()
	*/
	public function insert($table, $ary, $insert = 'INSERT', $multi_insert = FALSE)
	{
		if ($insert == 'REPLACE' && !$this->can_use_replace)
		{
			$where_str = 'WHERE ';
			foreach ($ary AS $key => $value)
			{
				// Si le champ est un index ($value[1] vaut TRUE)
				if (is_array($value) && $value[1] == TRUE)
				{
					$index_field = $key;
					$value = $value[0];
					$where_str .= "$key = '$value' AND ";
				}
			}
			$where_str = substr($where_str, 0, -4);

			$select_table = SQL_PREFIX . $table;
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
				$sql = $insert . ' INTO ' . SQL_PREFIX . $table . "
								($fields)
							VALUES ($values)";
				return ($this->query($sql));
			}
		}
	}

	/*
	** Construit une requète UPDATE à partir d'un tableau
	** -----
	** $table ::	Nom de la table
	** $ary ::		Tableau contenant en clef les champs de la requète et en
	**				valeur les valeurs pour la requète
	** $where ::	Clause where de la requète
	*/
	public function update($table, $ary, $where = '')
	{
		$sql = 'UPDATE ' . SQL_PREFIX . $table . ' SET ';
		foreach ($ary AS $key => $value)
		{
			$is_field = FALSE;
			if (is_array($value))
			{
				$is_field = (isset($value['is_field']) && $value['is_field']) ? TRUE : FALSE;
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

	/*
	** Vide une table
	** -----
	** $table ::		Nom de la table
	*/
	public function query_truncate($table)
	{
		if ($this->can_use_truncate)
		{
			$this->query('TRUNCATE ' . SQL_PREFIX . $table);
		}
		else
		{
			$this->query('DELETE FROM ' . SQL_PREFIX . $table);
		}
	}

	/*
	** Retourne une ligne du résultat et déplace le pointeur
	** vers la ligne suivante.
	** -----
	** $result ::		Résultat d'une requète
	** $function ::		Fonction à appeler par défaut il s'agira de "assoc" qui
	**					appellera mysql_fetch_assoc(). Il existe aussi "row" et
	**					"array" qui retournent respectivement un tableau sous forme d'indice
	**					et un mélange de "assoc" et "row".
	*/
	public function row($result, $function = 'assoc')
	{
		if (is_int($result) && isset($this->cache_query[$result]))
		{
			if (isset($this->cache_query[$result][$this->iterator_query[$result]]))
			{
				return ($this->cache_query[$result][$this->iterator_query[$result]++]);
			}
			return (NULL);
		}
		else
		{
			return ($this->_row($result, $function));
		}
	}

	/*
	** Renvoie un tableau contenant chaque ligne du résultat
	** -----
	** $result ::		Résultat d'une requète
	** $function ::		Voir la méthode row()
	** $field_name ::	Si le nom d'un champ est passé en paramètre, le tableau sera associé aux valeurs de ce champ. Ce champ
	**					doit être unique.
	*/
	public function rows($result, $function = 'assoc', $field_name = NULL)
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

	/*
	** Libère le résultat d'une requète
	** -----
	** $result ::		Résultat d'une requète
	*/
	public function free($result)
	{
		$this->_free($result);

		if (is_int($result) && isset($this->cache_query[$result]))
		{
			unset($this->cache_query[$result], $this->iterator_query[$result]);
		}
	}

	/*
	** Retourne un ou plusieurs champs d'une requète, par exemple :
	**	$sql = 'SELECT field FROM table WHERE field = xxx';
	**	$field = Fsb::$db->get($sql, 'field');
	**
	** ou bien pour plusieurs champs :
	**	$sql = 'SELECT field1, field2 FROM table WHERE field = xxx';
	**	list($field1, $field2) = Fsb::$db->get($sql, array('field1', 'field2'));
	*/
	public function get($query, $fields)
	{
		$result = $this->query($query);
		$row = $this->row($result);
		$this->free($result);

		if (!$row)
		{
			return (NULL);
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

	/*
	** Retourne le résultat d'une requête
	** -----
	** $query ::	Requête SQL
	*/
	public function request($query)
	{
		$result = $this->query($query);
		$row = $this->row($result);
		$this->free($result);

		return ($row);
	}

	/*
	** Détruit les requètes mises en cache
	** -----
	** $prefix ::	Préfixe des requètesà détruire
	*/
	public function destroy_cache($prefix = NULL)
	{
		if ($this->cache)
		{
			$this->cache->destroy($prefix);
		}
	}

	/*
	** Renvoie le nombre de ligne retournée par une requète SELECT
	** -----
	** $result ::		Résultat d'une requète
	*/
	public function count($result)
	{
		return ((is_int($result) && isset($this->cache_query[$result])) ? count($this->cache_query[$result]) : $this->_count($result));
	}

	/*
	** Ferme la connexion à la base de donnée
	*/
	public function close()
	{		
		if (Fsb::$debug->_get_debug_query())
		{
			echo $this->debug_str;
		}

		unset($this->cache_field_type, $this->cache_query, $this->iterator_query);
		$this->_close();
		$this->id = NULL;
	}

	/*
	** Permet d'afficher le débugage d'une requète
	** -----
	** $sql ::					Requète SQL
	** $result_explain ::		Résultat de la requète EXPLAIN
	** $start_time ::			Temps de début de la requète
	** $result ::				Résultat de la requète
	*/
	private function debug_query($sql, &$result_explain, &$start_query, $result = 0)
	{
		if (!$result)
		{
			/*
			** EXPLAIN semble n'être supporté que par MySQL actuellement
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
				$result = TRUE;
			}

			$start_query = Fsb::$debug->get_time();
		}
		else
		{
			$is_cache = (is_int($result) && isset($this->cache_query[$result])) ? TRUE : FALSE;
			$total_time = substr(Fsb::$debug->get_time() - $start_query, 0, 10);
			$this->debug_str .= '<table cellspacing="0" cellpadding="3" style="width: 100%; border: 1px ' . (($is_cache) ? 'dashed' : 'solid') . ' #000000;"><tr><th style="background-color: #EEEEEE; border: 1px ' . (($is_cache) ? 'dashed' : 'solid') . ' #000000; border-width: 0px 0px 1px 0px">Requète numéro ' . ($this->count) . '</th></tr><tr><td style="background-color: #EEEEFF; border: 1px ' . (($is_cache) ? 'dashed' : 'solid') . ' #000000; border-width: 0px 0px 1px 0px">' . htmlspecialchars($sql) . '</td></tr>';
			if ($result_explain && $this->can_use_explain)
			{
				$print_fields = FALSE;
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
						$print_fields = TRUE;
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
			$this->debug_str .= '<tr><td style="background-color: #EEEEFF; border: 1px ' . (($is_cache) ? 'dashed' : 'solid') . ' #000000; border-width: ' . (($result_explain && $this->can_use_explain) ? '1' : '0') . 'px 0px 0px 0px">Lignes affectées : ' . ((preg_match('/^SELECT/i', $sql)) ? $this->count($result) : $this->affected_rows($result_explain)) . (($is_cache) ? ' | Requète en cache' : ' | Requète éxécutée en ' . $total_time . ' Secondes') . '</td></tr></table><br />';
		}
	}
}

/*
** Créations de requètes SQL SELECT dynamiques.
** A n'utiliser que sur des requètes dynamiques / difficiles de lectures.
*/
class Sql_select extends Fsb_model
{
	private $query = '';
	private $fields = '';
	private $join = '';
	private $where = '';
	private $order = '';
	private $group = '';
	private $limit = '';

	/*
	** Constructeur
	** -----
	** $select_state ::		Clause de selection de la requète (SELECT DISTINCT par exemple)
	*/
	public function __construct($select_state = 'SELECT')
	{
		$this->query = $select_state . ' ';
	}

	/*
	** Ajoute une table à la requète
	** -----
	** $join_state ::		Liaison de la table dans la requète (FROM, LEFT JOIN, INNER JOIN, etc...)
	** $tablename ::		Nom de la table
	** $fields ::			Champs à selectionner
	** $on ::				Jointure de la table
	*/
	public function join_table($join_state, $tablename, $fields = '', $on = '')
	{
		$this->fields .= ($this->fields && $fields) ? ', ' . $fields : $fields;
		$this->join .= (($this->join) ? "\n" : '') . $join_state . ' ' . SQL_PREFIX . $tablename . (($on) ? "\n" . $on : '');
	}

	/*
	** Rempli la clause WHERE
	** -----
	** $str ::		Chaine de caractère
	*/
	public function where($str)
	{
		$this->where .= ((!$this->where) ? 'WHERE ' . $str : $str) . ' ';
	}

	/*
	** Rempli la clause GROUP BY
	** -----
	** $str ::		Chaine de caractère
	*/
	public function group_by($str)
	{
		$this->group .= ((!$this->group) ? 'GROUP BY ' . $str : $str) . ' ';
	}

	/*
	** Rempli la clause ORDER BY
	** -----
	** $str ::		Chaine de caractère
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
	** Execute la requète
	** -----
	** $get ::			Utilisation de Sql_dbal::get() 
	** $cache_query ::	Prefixe de la requète si on souhaite la mettre en cache
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