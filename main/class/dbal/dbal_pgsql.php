<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/dbal/dbal_pgsql.php
** | Begin :	18/07/2005
** | Last :		10/02/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/**
 * Abstraction pour PostGreSQL
 */
class Dbal_pgsql extends Dbal
{
	/**
	 * Derniere requete executee
	 *
	 * @var string
	 */
	private $last_query = '';

	/**
	 * @see Dbal::factory()
	 */
	public function  __construct($server, $login, $pass, $db, $port = null, $use_cache = true)
	{
		$this->use_cache = $use_cache;
		$this->id = null;
		$str = "user=$login password=$pass dbname=$db ";
		if ($port)
		{
			$str .= "port=$port ";
		}

		if (!$this->id = @pg_connect($str))
		{
			return (false);
		}

		if (!$this->id)
		{
			return ;
		}

		// Ce que peut faire PostgreSQL
		$this->can_use_explain = false;
		$this->can_use_replace = false;
		$this->can_use_multi_insert = true;
		$this->can_use_truncate = true;
	}

	/**
	 * @see Dbal::_query()
	 */
	public function _query($sql, $buffer = true)
	{
		// Gestion des limites de requetes
		preg_match('/^(.*?)\sLIMIT ([0-9]+)(,\s?([0-9]+))?\s*$/si', $sql, $match);
		if (isset($match[2]))
		{
			$sql = $match[1] . ' LIMIT ' . ((isset($match[4])) ? $match[4] : $match[2]). ((!empty($match[3])) ? ' OFFSET ' . $match[2] : '');
		}

		// Execution de la requete
		$this->last_query = $sql;
		if (!($result = @pg_query($this->id, $sql)) && pg_last_error())
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
		$this->last_query = $sql;
		return (@pg_exec($this->id, $sql));
	}

	/**
	 * @see Dbal::_row()
	 */
	public function _row($result, $function = 'assoc')
	{
		switch ($function)
		{
			case "assoc" :
				$flag = PGSQL_ASSOC;
			break;

			case "row" :
				$flag = PGSQL_NUM;
			break;

			default :
				$flag = PGSQL_BOTH;
			break;
		}
		return (pg_fetch_array($result, null, $flag));
	}

	/**
	 * @see Dbal::_free()
	 */
	public function _free($result)
	{
		if (!isset($this->cache_query[$result])	&& is_resource($result))
		{
			pg_freeresult($result);
		}
	}

	/**
	 * @see Dbal::last_id()
	 */
	public function last_id()
	{
		$last_id = 0;
		if (preg_match('/^INSERT INTO\s+([a-zA-Z0-9_]*?)\s+/si', $this->last_query, $match))
		{
			$sql = 'SELECT currval(\'' . $match[1] . '_seq\') AS last_id';
			$result = $this->simple_query($sql);
			$data = $this->_row($result);
			$this->free($result);
			$last_id = (isset($data['last_id'])) ? intval($data['last_id']) : 1;
		}
		return ($last_id);
	}

	/**
	 * @see Dbal::_count()
	 */
	public function _count($result)
	{
		return (pg_numrows($result));
	}

	/**
	 * @see Dbal::escape()
	 */
	public function escape($str)
	{
		return (pg_escape_string($str));
	}

	/**
	 * @see Dbal::affected_rows()
	 */
	public function affected_rows($result)
	{
		return (pg_affected_rows($result));
	}

	/**
	 * @see Dbal::field_type()
	 */
	public function field_type($result, $field, $table = null)
	{
		return (pg_field_type($result, (is_int($field)) ? $field : pg_field_num($result, $field)));
	}

	/**
	 * @see Dbal::get_field_type()
	 */
	public function get_field_type($result, $field, $table = null)
	{
		$field_type = $this->field_type($result, $field);
		if (!$field_type)
		{
			$field_type = 'string';
		}

		switch (strtolower($field_type))
		{
			case 'int4' :
			case 'int2' :
				return ('int');

			case 'varchar' :
			case 'text' :
			case 'string' :
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
		$sql = 'SELECT tablename FROM pg_tables
					WHERE schemaname = \'public\'';
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
		return (@pg_last_error());
	}

	/**
	 * @see Dbal::_close()
	 */
	public function _close()
	{
		pg_close($this->id);
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
		foreach ($delete_join AS $field => $tables)
		{
			$sql = 'SELECT ' . $field . '
					FROM ' . $this->sql_prefix . $default_table
					. ' WHERE ' . $default_where;
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
		return ('ILIKE');
	}
}

/* EOF */