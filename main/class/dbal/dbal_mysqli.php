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
	public function __construct($server, $login, $pass, $db, $port = null, $use_cache = true)
	{
		$this->use_cache = $use_cache;
		$this->mysqli = @(new mysqli($server, $login, $pass, $db, $port));
		$port = (!trim($port)) ? null : $port;

		if (mysqli_connect_errno())
		{
			$this->id = null;
			return ;
		}
		$this->id = true;

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
	public function field_type($result, $field, $table = null)
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
	public function get_field_type($result, $field, $table = null)
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
		return $this->id ? ($this->mysqli->error) : mysqli_connect_error();
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
					$this->mysqli->autocommit(false);
				}
				$this->in_transaction = true;
			break;

			case 'commit' :
				if ($this->in_transaction)
				{
					$this->mysqli->commit();
				}
				$this->in_transaction = false;
			break;

			case 'rollback' :
				if ($this->in_transaction)
				{
					$this->mysqli->rollback();
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
}

/* EOF */
