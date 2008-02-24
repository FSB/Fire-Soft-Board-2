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

/*
** Classe pour postgreSQL 8
*/
class Dbal_pgsql extends Dbal
{
	// Dernieres requete pgsql executee
	private $last_query = '';

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
	public function  __construct($server, $login, $pass, $db, $port = NULL, $use_cache = TRUE)
	{
		$this->use_cache = $use_cache;
		$this->id = NULL;
		$str = "user=$login password=$pass dbname=$db ";
		if ($port)
		{
			$str .= "port=$port ";
		}

		if (!$this->id = @pg_connect($str))
		{
			return (FALSE);
		}

		if (!$this->id)
		{
			return ;
		}

		// Ce que peut faire PostgreSQL
		$this->can_use_explain = FALSE;
		$this->can_use_replace = FALSE;
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
	
	/*
	** Simple requete n'affichant pas directement l'erreur
	** -----
	** $sql ::		Requete a executer
	*/
	public function simple_query($sql)
	{
		$this->last_query = $sql;
		return (@pg_exec($this->id, $sql));
	}

	/*
	** Voir parent::row()
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
		return (pg_fetch_array($result, NULL, $flag));
	}

	/*
	** Voir parent::free()
	*/
	public function _free($result)
	{
		if (!isset($this->cache_query[$result])	&& is_resource($result))
		{
			pg_freeresult($result);
		}
	}

	/*
	** Retourne la derniere ID apres un INSERT en cas d'incrementation automatique.
	** Pour postgresql nous allons piocher ce numero directement dans la sequence.
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

	/*
	** Voir parent::count()
	*/
	public function _count($result)
	{
		return (pg_numrows($result));
	}

	/*
	** Protege un champ de la requete
	** -----
	** $str :: Chaine a proteger
	*/
	public function escape($str)
	{
		return (pg_escape_string($str));
	}

	/*
	** Renvoie le nombre de lignes affectees par une requete
	** -----
	** $result ::		Resultat d'une requete
	*/
	public function affected_rows($result)
	{
		return (pg_affected_rows($result));
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
		return (pg_field_type($result, (is_int($field)) ? $field : pg_field_num($result, $field)));
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

	/*
	** Renvoie un tableau contenant la liste des tables
	** -----
	** $limit ::	Si TRUE, ne recupere que les tables ayant le meme prefixe que le forum
	*/
	public function list_tables($limit = TRUE)
	{
		$tables = array();
		$sql = 'SELECT tablename FROM pg_tables
					WHERE schemaname = \'public\'';
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
		return (@pg_last_error());
	}

	/*
	** Ferme la connexion a la base de donnee
	*/
	public function _close()
	{
		pg_close($this->id);
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
	** (PostgreSQL ne supporte pas les multi suppressions)
	** -----
	** $default_table ::		Table par defaut dont on va recuperer les champs
	** $default_where ::		Clause WHERE pour la recuperation des champs
	** $delete_join ::			Tableau associatif contenant en clef les champs et en valeur des tableaux de tables SQL
	*/
	public function delete_tables($default_table, $default_where, $delete_join)
	{
		foreach ($delete_join AS $field => $tables)
		{
			$sql = 'SELECT ' . $field . '
					FROM ' . SQL_PREFIX . $default_table
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
					$sql = 'DELETE FROM ' . SQL_PREFIX . $table . ' WHERE ' . $field . ' IN(' . $list_idx . ')';
					$this->query($sql);
				}
			}
		}
	}

	/*
	** Retourne l'operateur LIKE
	*/
	public function like()
	{
		return ('ILIKE');
	}
}

/* EOF */