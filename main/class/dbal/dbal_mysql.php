<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/dbal/dbal_mysql.php
** | Begin :	03/04/2005
** | Last :		10/02/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

class Dbal_mysql extends Dbal
{
	/*
	** Constructeur de la classe Sql
	** Etablit une connexion
	** -----
	** $server ::		Adresse du serveur MySQL
	** $login ::		Login d'acces MySQL
	** $pass ::			Mot de passe associe au login
	** $db ::			Nom de la base de donnee a selectionner
	** $port ::			Port de connexion
	** $use_cache ::	Utilisation du cache SQL ?
	*/
	public function __construct($server, $login, $pass, $db, $port = NULL, $use_cache = TRUE)
	{
		if ($port)
		{
			$server .= ':' . $port;
		}

		$this->use_cache = $use_cache;
		$this->id = NULL;
		if (!$this->id = @mysql_connect($server, $login, $pass))
		{
			$this->id = NULL;
		}

		if ($this->id && !@mysql_select_db($db, $this->id))
		{
			$this->id = NULL;
		}

		if (!$this->id)
		{
			return ;
		}

		// Ce que peut faire MySQL
		$this->can_use_explain = TRUE;
		$this->can_use_replace = TRUE;
		$this->can_use_multi_insert = TRUE;
		$this->can_use_truncate = TRUE;
	}

	/*
	** Execute la requete SQL et renvoie le resultat
	** -----
	** $sql ::		Requete a executer
	** $buffer ::	Si TRUE, le resultat est bufferise. Utiliser FALSE pour les
	**				requetes ne renvoyant pas explicitement de resultat (UPDATE, DELETE,
	**				INSERT, etc ...)
	*/
	public function _query($sql, $buffer = TRUE)
	{
		if (!$result = (($buffer) ? @mysql_query($sql) : @mysql_unbuffered_query($sql)))
		{
			$errstr = $this->sql_error();
			$this->transaction('rollback');
			trigger_error('error_sql :: ' . $errstr . '<br />-----<br />' . htmlspecialchars($sql), FSB_ERROR);
		}
		return ($result);
	}
	
	/*
	** Simple requete n'affichant pas directement l'erreur
	** -----
	** $sql ::		Requete a executer
	*/
	public function simple_query($sql)
	{
		return (@mysql_query($sql));
	}

	/*
	** Voir parent::row()
	*/
	public function _row($result, $function = 'assoc')
	{
		$pointer = 'mysql_fetch_' . $function;
		return ($pointer($result));
	}

	/*
	** Voir parent::free()
	*/
	public function _free($result)
	{
		if (!isset($this->cache_query[$result])	&& is_resource($result))
		{
			mysql_free_result($result);
		}
	}

	/*
	** Retourne la derniere ID apres un INSERT en cas d'incrementation automatique
	*/
	public function last_id()
	{
		return (mysql_insert_id());
	}

	/*
	** Voir parent::count()
	*/
	public function _count($result)
	{
		return (mysql_num_rows($result));
	}

	/*
	** Protege un champ de la requete
	** -----
	** $str :: Chaine a proteger
	*/
	public function escape($str)
	{
		return (mysql_real_escape_string($str));
	}

	/*
	** Renvoie le nombre de lignes affectees par une requete
	** -----
	** $result ::		Resultat d'une requete
	*/
	public function affected_rows($result)
	{
		return (mysql_affected_rows());
	}

	/*
	** Renvoie le type d'un champ
	** -----
	** $result ::	Resultat de la requete
	** $field ::	Champ a verifier
	** $table ::	Nom de la table concernee
	*/
	public function field_type($result, $field, $table = NULL)
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

	/*
	** Renvoie simplement 'string' ou bien 'int' suivant si le champ est un entier
	** ou une chaine de caracteres.
	** -----
	** $result ::	Resultat de la requete
	** $field ::	Champ a verifier
	** $table ::	Nom de la table concernee
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
			case 'int' :
				return ('int');

			case 'string' :
			case 'blob' :
			case 'datetime' :
			default :
				return ('string');
		}
	}

	/*
	** Renvoie un tableau contenant la liste des tables
	** -----
	** $limit ::	Si TRUE, ne recupere que les tables ayant le meme prefixe que le forum
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

	/*
	** Execute une multi insertion
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
	
	/*
	** Renvoie la derniere erreur MySQL
	*/
	public function sql_error()
	{
		return (mysql_error());
	}

	/*
	** Voir parent::close()
	*/
	public function _close()
	{
		mysql_close($this->id);
	}

	/*
	** Transactions
	** -----
	** $type ::		Etat de la transaction (begin, commit ou rollback)
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
				$this->in_transaction = TRUE;
			break;

			case 'commit' :
				if ($this->in_transaction)
				{
					$this->simple_query('COMMIT');
				}
				$this->in_transaction = FALSE;
			break;

			case 'rollback' :
				if ($this->in_transaction)
				{
					$this->simple_query('ROLLBACK');
				}
				$this->in_transaction = FALSE;
			break;
		}
	}

	/*
	** Supprime des elements de plusieurs tables
	** (MySQL supporte les multi suppressions)
	** -----
	** $default_table ::		Table par defaut dont on va recuperer les champs
	** $default_where ::		Clause WHERE pour la recuperation des champs
	** $delete_join ::			Tableau associatif contenant en clef les champs et en valeur des tableaux de tables SQL
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

	/*
	** Retourne l'operateur LIKE
	*/
	public function like()
	{
		return ('LIKE');
	}

	/*
	** Retourne la version de MySQL
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