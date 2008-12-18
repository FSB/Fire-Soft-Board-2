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
 * Abstraction pour SQLite
 * Attention, SQLite est sensible a la casse. Des LOWER ont ete ajoutes dans les requetes
 * ou la casse risquerait de jouer de mauvais tours, mais pas partout (question de logique).
 * Si vous developpez un MOD utilisant des chaines de caracteres dans les requetes, veillez a
 * bien tester le fonctionement de la requete sous SQLite
 */
class Dbal_sqlite extends Dbal
{
	/**
	 * @see Dbal::factory()
	 */
	public function __construct($server, $login, $pass, $db, $port = null, $use_cache = true)
	{
		$this->use_cache = $use_cache;
		$this->id = null;
		$errstr = '';
		if (!$this->id = @sqlite_open(ROOT . 'main/class/dbal/sqlite/' . $db . '.sqlite' . ((trim($port)) ? ':' . $port : ''), 0666, $errstr))
		{
			return ;
		}

		// Cette requete evite aux alias de se retrouver dans le nom des champs.
		$this->simple_query('PRAGMA short_column_names = 1');

		// Ce que peut faire SQLite
		$this->can_use_explain = false;
		$this->can_use_replace = false;
		$this->can_use_multi_insert = false;
		$this->can_use_truncate = false;
		return ($this->id);
	}

	/**
	 * @see Dbal::_query()
	 */
	public function _query($sql, $buffer = true)
	{
		$result = (($buffer) ? @sqlite_query($this->id, $sql) : @sqlite_unbuffered_query($this->id, $sql));
		if (!$result && sqlite_last_error($this->id) != 0)
		{
			trigger_error('error_sql :: ' . $this->sql_error() . '<br />-----<br />' . htmlspecialchars($sql), FSB_ERROR);
		}
		return ($result);
	}
	
	/**
	 * @see Dbal::simple_query()
	 */
	public function simple_query($sql)
	{
		return (sqlite_query($this->id, $sql));
	}

	/**
	 * @see Dbal::_row()
	 */
	public function _row($result, $function = 'assoc')
	{
		$flags = array(
			'assoc' =>	SQLITE_ASSOC,
			'row' =>	SQLITE_NUM,
			'array' =>	SQLITE_BOTH,
		);
		return (sqlite_fetch_array($result, $flags[$function]));
	}

	/**
	 * @see Dbal::_free()
	 */
	public function _free($result)
	{
		return (true);
	}

	/**
	 * @see Dbal::last_id()
	 */
	public function last_id()
	{
		return (sqlite_last_insert_rowid($this->id));
	}

	/**
	 * @see Dbal::_count()
	 */
	public function _count($result)
	{
		return (sqlite_num_rows($result));
	}

	/**
	 * @see Dbal::escape()
	 */
	public function escape($str)
	{
		return (sqlite_escape_string($str));
	}

	/**
	 * @see Dbal::affected_rows()
	 */
	public function affected_rows($result)
	{
		return (sqlite_changes($this->id));
	}

	/**
	 * @see Dbal::field_type()
	 */
	public function field_type($result, $field, $table = null)
	{
		if (!isset($this->cache_field_type[$table]))
		{
			$fields = sqlite_fetch_column_types($table, $this->id);
			$this->cache_field_type[$table] = $fields;
		}

		if (is_int($field))
		{
			$i = 0;
			foreach ($this->cache_field_type[$table] AS $type)
			{
				if ($i == $field)
				{
					return ($type);
				}
				$i++;
			}
		}
		else
		{
			return ($this->cache_field_type[$table][$field]);
		}
	}

	/**
	 * @see Dbal::get_field_type()
	 */
	public function get_field_type($result, $field, $table = null)
	{
		return ('string');
	}

	/**
	 * @see Dbal::list_tables()
	 */
	public function list_tables($limit = true)
	{
		$tables = array();
		$sql = 'SELECT name AS tablename
					FROM (SELECT * FROM sqlite_master UNION SELECT * FROM sqlite_temp_master) 
					WHERE type=\'table\' ORDER BY name';
		$result = $this->query($sql);
		while ($row = $this->row($result, 'row'))
		{
			if ($limit && substr($row[0], 0, strlen($this->sql_prefix)) != $this->sql_prefix)
			{
				continue;
			}
			$tables[] = $row[0];
		}

		return ($tables);
	}

	/**
	 * @see Dbal::query_multi_insert()
	 */
	public function query_multi_insert()
	{
		if ($this->multi_insert)
		{
			$sql = $this->multi_insert['insert'] . ' INTO ' . $this->sql_prefix . $this->multi_insert['table']
						. ' (' . $this->multi_insert['fields'] . ')
						VALUES (' . implode('), (', $this->multi_insert['values']) . ')';
			return ($this->query($sql));
		}

		$this->multi_insert = array();
	}

	/**
	 * @see Dbal::sql_error()
	 */
	public function sql_error()
	{
		return (sqlite_error_string(sqlite_last_error($this->id)));
	}

	/**
	 * @see Dbal::_close()
	 */
	public function _close()
	{
		sqlite_close($this->id);
	}

	/**
	 * @see Dbal::transaction()
	 */
	public function transaction($type)
	{
		switch ($type)
		{
			case 'begin' :
				if (!$this->in_transaction)
				{
					$this->simple_query('BEGIN TRANSACTION');
				}
				$this->in_transaction = true;
			break;

			case 'commit' :
				if ($this->in_transaction)
				{
					$this->simple_query('COMMIT TRANSACTION');
				}
				$this->in_transaction = false;
			break;

			case 'rollback' :
				if ($this->in_transaction)
				{
					$this->simple_query('ROLLBACK TRANSACTION');
				}
				$this->in_transaction = false;
			break;
		}
	}

	/**
	 * @see Dbal::delete_tables()
	 */
	public function delete_tables($default_table, $default_where, $delete_join)
	{
		foreach ($delete_join AS $field => $tables)
		{
			$sql = 'SELECT ' . $field . '
					FROM ' . $this->sql_prefix . $default_table
					. ' ' . $default_where;
			$result = $this->query($sql);
			$list_idx = '';
			while ($row = Fsb::$db->row($result))
			{
				$list_idx .= $row[$field] . ',';
			}
			$list_idx = substr($list_idx, 0, -1);
			Fsb::$db->free($result);

			if ($list_idx)
			{
				foreach ($tables AS $table)
				{
					$sql = 'DELETE FROM ' . $this->sql_prefix . $table . ' WHERE ' . $field . ' IN(' . $list_idx . ')';
					$this->query($sql);
				}
			}
		}
	}

	/**
	 * @see Dbal::like()
	 */
	public function like()
	{
		return ('LIKE');
	}

	/**
	 * Simule le comportement d'un ALTER pour SQLite
	 *
	 * @param string $tablename Nom de la table
	 * @param string $action ADD, DROP ou RENAME
	 * @param string $arg Nom du champ pour ajout /suppression de champ, nom de la table pour le renomage de la table
	 */
	public function alter($tablename, $action, $arg)
	{
		// On recupere les champs de la table originale
		$cols = array_keys(sqlite_fetch_column_types($tablename, $this->id, SQLITE_ASSOC));

		switch ($action)
		{
			case 'ADD' :
				$is_temporary = true;
				$name = $tablename . '__tmp';
				$fields = implode(', ', $cols) . ', \'\' AS ' . $arg . '';
			break;

			case 'DROP' :
				$is_temporary = true;
				$name = $tablename . '__tmp';

				// Suppression du champ
				$tmp = array_flip($cols);
				unset($tmp[$arg]);
				$cols = array_keys($tmp);
				$fields = implode(', ', $cols);
			break;

			case 'RENAME' :
				$is_temporary = false;
				$name = $arg;
				$fields = '*';
			break;
		}

		// On cree une nouvelle table, basee sur l'ancienne
		$sql = 'CREATE ' . (($is_temporary) ? 'TEMPORARY' : '') . ' TABLE ' . $name . ' AS SELECT ' . $fields . ' FROM ' . $tablename;
		$this->query($sql);

		// Suppression de l'ancienne table
		$sql = 'DROP TABLE ' . $tablename;
		$this->query($sql);

		if ($action == 'RENAME')
		{
			return ;
		}

		// Recreation de la table d'origine a partir de la nouvelle table
		$sql = 'CREATE TABLE ' . $tablename . ' AS SELECT * FROM ' . $name;
		$this->query($sql);

		// Suppression de la table temporaire
		$sql = 'DROP TABLE ' . $name;
		$this->query($sql);
	}
}

/* EOF */