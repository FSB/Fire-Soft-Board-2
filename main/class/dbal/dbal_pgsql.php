<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/dbal/dbal_pgsql.php
** | Begin :	18/07/2005
** | Last :		25/12/2007
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
	** $login ::		Login d'accès MySQL
	** $pass ::			Mot de passe associé au login
	** $db ::			Nom de la base de donnée à selectionner
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
	** Execute la requète SQL et renvoie le résultat
	** -----
	** $sql ::		Requète à éxécuter
	** $buffer ::	Si TRUE, le résultat est bufferisé. Utiliser FALSE pour les
	**				requètes ne renvoyant pas explicitement de résultat (UPDATE, DELETE,
	**				INSERT, etc ...)
	*/
	public function _query($sql, $buffer = TRUE)
	{
		// Gestion des limites de requètes
		preg_match('/^(.*?)\sLIMIT ([0-9]+)(,\s?([0-9]+))?\s*$/si', $sql, $match);
		if (isset($match[2]))
		{
			$sql = $match[1] . ' LIMIT ' . ((isset($match[4])) ? $match[4] : $match[2]). ((!empty($match[3])) ? ' OFFSET ' . $match[2] : '');
		}

		// Exécution de la requète
		$this->last_query = $sql;
		if (!($result = @pg_query($this->id, $sql)) && pg_last_error())
		{
			trigger_error('error_sql :: ' . $this->sql_error() . '<br />-----<br />' . htmlspecialchars($sql), FSB_ERROR);
		}
		return ($result);
	}
	
	/*
	** Simple requète n'affichant pas directement l'erreur
	** -----
	** $sql ::		Requète à éxécuter
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
	** Retourne la dernière ID après un INSERT en cas d'incrementation automatique.
	** Pour postgresql nous allons piocher ce numéro directement dans la séquence.
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
	** Protège un champ de la requète
	** -----
	** $str :: Chaîne à protéger
	*/
	public function escape($str)
	{
		return (pg_escape_string($str));
	}

	/*
	** Renvoie le nombre de lignes affectées par une requète
	** -----
	** $result ::		Résultat d'une requète
	*/
	public function affected_rows($result)
	{
		return (pg_affected_rows($result));
	}

	/*
	** Renvoie le type d'un champ
	** -----
	** $result ::	Résultat de la requète
	** $field ::	Champ à vérifier
	** $table ::	Nom de la table concernée
	*/
	public function field_type($result, $field, $table = NULL)
	{
		return (pg_field_type($result, (is_int($field)) ? $field : pg_field_num($result, $field)));
	}

	/*
	** Renvoie simplement 'string' ou bien 'int' suivant si le champ est un entier
	** ou une chaîne de caractères.
	** -----
	** $result ::	Résultat de la requète
	** $field ::	Champ à vérifier
	** $table ::	Nom de la table concernée
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
	*/
	public function list_tables()
	{
		$sql = 'SELECT tablename FROM pg_tables
					WHERE schemaname = \'public\'';
		return ($this->query($sql));
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
	** Renvoie la dernière erreur MySQL
	*/
	public function sql_error()
	{
		return (@pg_last_error());
	}

	/*
	** Ferme la connexion à la base de donnée
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
	** Supprime des éléments de plusieurs tables
	** (PostgreSQL ne supporte pas les multi suppressions)
	** -----
	** $default_table ::		Table par défaut dont on va récupérer les champs
	** $default_where ::		Clause WHERE pour la récupération des champs
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