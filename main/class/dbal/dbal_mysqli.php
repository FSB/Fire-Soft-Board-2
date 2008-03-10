<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/dbal/dbal_mysqli.php
** | Begin :	03/04/2005
** | Last :		10/02/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/**
 * Abstraction pour MySQL via MySQLi
 *
 */
class Dbal_mysqli extends Dbal
{
	/**
	 * @var mysqli
	 */
	private $mysqli;

	/**
	 * @see Dbal::factory()
	 */
	public function __construct($server, $login, $pass, $db, $port = NULL, $use_cache = TRUE)
	{
		$this->use_cache = $use_cache;
		$this->mysqli = new mysqli($server, $login, $pass, $db, $port);
		$port = (!trim($port)) ? NULL : $port;

		if (mysqli_connect_errno())
		{
			$this->id = NULL;
			return ;
		}
		$this->id = TRUE;

		// Ce que peut faire MySQL
		$this->can_use_explain = TRUE;
		$this->can_use_replace = TRUE;
		$this->can_use_multi_insert = TRUE;
		$this->can_use_truncate = TRUE;
	}

	/**
	 * @see Dbal::_query()
	 */
	public function _query($sql, $buffer = TRUE)
	{
		if (!$result = $this->mysqli->query($sql))
		{
			$errstr = $this->sql_error();
			$this->transaction('rollback');
			trigger_error('error_sql :: ' . $errstr . '<br />-----<br />' . htmlspecialchars($sql), FSB_ERROR);
		}
		return ($result);
	}
	
	/**
	 * @see Dbal::simple_query()
	 */
	public function simple_query($sql)
	{
		return ($this->mysqli->query($sql));
	}

	/**
	 * @see Dbal::_row()
	 */
	public function _row($result, $function = 'assoc')
	{
		$pointer = 'fetch_' . $function;
		return ($result->{$pointer}());
	}

	/**
	 * @see Dbal::_free()
	 */
	public function _free($result)
	{
		if (is_object($result))
		{
			$result->free();
		}
	}

	/**
	 * @see Dbal::last_id()
	 */
	public function last_id()
	{
		return ($this->mysqli->insert_id);
	}

	/**
	 * @see Dbal::_count()
	 */
	public function _count($result)
	{
		return ($result->num_rows);
	}

	/**
	 * @see Dbal::escape()
	 */
	public function escape($str)
	{
		return ($this->mysqli->real_escape_string($str));
	}

	/**
	 * @see Dbal::affected_rows()
	 */
	public function affected_rows($result)
	{
		return ($this->mysqli->affected_rows);
	}

	/**
	 * @see Dbal::field_type()
	 */
	public function field_type($result, $field, $table = NULL)
	{
		if (!isset($this->cache_field_type[$table]))
		{
			$this->cache_field_type[$table] = array();
			while ($row = mysqli_fetch_field($result))
			{
				$this->cache_field_type[$table][$row->name] = $row->type;
			}
		}
		return ($this->cache_field_type[$table][$field]);
	}

	/**
	 * @see Dbal::get_field_type()
	 */
	public function get_field_type($result, $field, $table = NULL)
	{
		$field_type = $this->field_type($result, $field, $table);
		if (!$field_type)
		{
			$field_type = 'string';
		}

		switch (strtolower($field_type))
		{
			case 1 :
			case 2 :
			case 3 :
				return ('int');

			default :
				return ('string');
		}
	}

	/**
	 * @see Dbal::list_tables()
	 */
	public function list_tables($limit = TRUE)
	{
		$tables = array();
		$sql = 'SHOW TABLES';
		$result = $this->query($sql);
		while ($row = $this->row($result, 'row'))
		{
			if ($limit && substr($row[0], 0, strlen(SQL_PREFIX)) != SQL_PREFIX)
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
			$sql = $this->multi_insert['insert'] . ' INTO ' . SQL_PREFIX . $this->multi_insert['table']
						. ' (' . $this->multi_insert['fields'] . ')
						VALUES (' . implode('), (', $this->multi_insert['values']) . ')';
			$this->multi_insert = array();
			return ($this->query($sql));
		}
	}
	
	/**
	 * @see Dbal::sql_error()
	 */
	public function sql_error()
	{
		return ($this->mysqli->error);
	}

	/**
	 * @see Dbal::_close()
	 */
	public function _close()
	{
		$this->mysqli->close();
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
					$this->mysqli->autocommit(FALSE);
				}
				$this->in_transaction = TRUE;
			break;

			case 'commit' :
				if ($this->in_transaction)
				{
					$this->mysqli->commit();
				}
				$this->in_transaction = FALSE;
			break;

			case 'rollback' :
				if ($this->in_transaction)
				{
					$this->mysqli->rollback();
				}
				$this->in_transaction = FALSE;
			break;
		}
	}

	/**
	 * @see Dbal::delete_tables()
	 */
	public function delete_tables($default_table, $default_where, $delete_join)
	{
		$sql_delete = 'DELETE ' . SQL_PREFIX . $default_table;
		$sql_table = ' FROM ' . SQL_PREFIX . $default_table;
		$sql_where = ' WHERE ' . SQL_PREFIX . $default_table . '.' . $default_where;
		foreach ($delete_join AS $field => $tables)
		{
			foreach ($tables AS $table)
			{
				$sql_delete .= ', ' . SQL_PREFIX . $table;
				$sql_table .= ', ' . SQL_PREFIX . $table;
				$sql_where .= ' AND ' . SQL_PREFIX . $table . '.' . $field . ' = ' . SQL_PREFIX . $default_table . '.' . $field;
			}
		}

		$this->query($sql_delete . $sql_table . $sql_where);
	}

	/**
	 * @see Dbal::like()
	 */
	public function like()
	{
		return ('LIKE');
	}
}

/* EOF */