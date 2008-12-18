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
 * Abstraction pour MySQL
 */
class Dbal_mysql extends Dbal
{
	/**
	 * @see Dbal::factory()
	 */
	public function __construct($server, $login, $pass, $db, $port = null, $use_cache = true)
	{
		if ($port)
		{
			$server .= ':' . $port;
		}

		$this->use_cache = $use_cache;
		$this->id = null;
		if (!$this->id = @mysql_connect($server, $login, $pass))
		{
			$this->id = null;
		}

		if ($this->id && !@mysql_select_db($db, $this->id))
		{
			$this->id = null;
		}

		if (!$this->id)
		{
			return ;
		}

		// Ce que peut faire MySQL
		$this->can_use_explain = true;
		$this->can_use_replace = true;
		$this->can_use_multi_insert = true;
		$this->can_use_truncate = true;
	}

	/**
	 * @see Dbal::_query()
	 */
	public function _query($sql, $buffer = true)
	{
		if (!$result = (($buffer) ? @mysql_query($sql, $this->id) : @mysql_unbuffered_query($sql, $this->id)))
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
		return (@mysql_query($sql, $this->id));
	}

	/**
	 * @see Dbal::_row()
	 */
	public function _row($result, $function = 'assoc')
	{
		$pointer = 'mysql_fetch_' . $function;
		return ($pointer($result));
	}

	/**
	 * @see Dbal::_free()
	 */
	public function _free($result)
	{
		if (!isset($this->cache_query[$result])	&& is_resource($result))
		{
			mysql_free_result($result);
		}
	}

	/**
	 * @see Dbal::last_id()
	 */
	public function last_id()
	{
		return (mysql_insert_id($this->id));
	}

	/**
	 * @see Dbal::_count()
	 */
	public function _count($result)
	{
		return (mysql_num_rows($result));
	}

	/**
	 * @see Dbal::escape()
	 */
	public function escape($str)
	{
		return (mysql_real_escape_string($str, $this->id));
	}

	/**
	 * @see Dbal::affected_row()
	 */
	public function affected_rows($result)
	{
		return (mysql_affected_rows($this->id));
	}

	/**
	 * @see Dbal::field_type()
	 */
	public function field_type($result, $field, $table = null)
	{
		if (is_int($field) || !$table)
		{
			return (mysql_field_type($result, $field));
		}
		else
		{
			if (!isset($this->cache_field_type[$table]))
			{
				$this->cache_field_type[$table] = array();

				$sql = "SHOW COLUMNS FROM $table";
				$result_tmp = $this->simple_query($sql);

				$i = 0;
				while ($row = $this->row($result_tmp))
				{
					$this->cache_field_type[$table][$row['Field']] = $i++;
				}
			}

			return (mysql_field_type($result, $this->cache_field_type[$table][$field]));
		}
	}

	/**
	 * @see Dbal::get_field_type()
	 */
	public function get_field_type($result, $field, $table = null)
	{
		$field_type = $this->field_type($result, $field, $table);
		if (!$field_type)
		{
			$field_type = 'string';
		}

		switch (strtolower($field_type))
		{
			case 'int' :
				return ('int');

			case 'string' :
			case 'blob' :
			case 'datetime' :
			default :
				return ('string');
		}
	}

	/**
	 * @see Dbal::list_tables()
	 */
	public function list_tables($limit = true)
	{
		$tables = array();
		$sql = 'SHOW TABLES';
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
			$this->multi_insert = array();
			return ($this->query($sql));
		}
	}
	
	/**
	 * @see Dbal::sql_error()
	 */
	public function sql_error()
	{
		if (!$this->id)
		{
			return (mysql_error());
		}
		return (mysql_error($this->id));
	}

	/**
	 * @see Dbal::close()
	 */
	public function _close()
	{
		mysql_close($this->id);
	}

	/**
	 * @see Dbal::trasaction()
	 */
	public function transaction($type)
	{
		switch ($type)
		{
			case 'begin' :
				if (!$this->in_transaction)
				{
					$this->simple_query('BEGIN');
				}
				$this->in_transaction = true;
			break;

			case 'commit' :
				if ($this->in_transaction)
				{
					$this->simple_query('COMMIT');
				}
				$this->in_transaction = false;
			break;

			case 'rollback' :
				if ($this->in_transaction)
				{
					$this->simple_query('ROLLBACK');
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
		$sql_delete = 'DELETE ' . $this->sql_prefix . $default_table;
		$sql_table = ' FROM ' . $this->sql_prefix . $default_table;
		$sql_where = ' WHERE ' . $this->sql_prefix . $default_table . '.' . $default_where;
		foreach ($delete_join AS $field => $tables)
		{
			foreach ($tables AS $table)
			{
				$sql_delete .= ', ' . $this->sql_prefix . $table;
				$sql_table .= ', ' . $this->sql_prefix . $table;
				$sql_where .= ' AND ' . $this->sql_prefix . $table . '.' . $field . ' = ' . $this->sql_prefix . $default_table . '.' . $field;
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

	/**
	 * Recupere la version de MySQL
	 *
	 * @return string
	 */
	public function mysql_version()
	{
		$sql = 'SELECT VERSION() AS mysql_version';
		$result = $this->query($sql, 'mysql_version_');
		$row = $this->row($result);
		$mysql_version = $row['mysql_version'];
		$this->free($result);

		return ($mysql_version);
	}
}

/* EOF */