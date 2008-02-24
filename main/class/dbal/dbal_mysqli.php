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

class Dbal_mysqli extends Dbal
{
	// Objet MySQLI (on utilise le style objet pour MySQLI)
	private $mysqli;

	/*
	** Constructeur de la classe Sql
	** Etablit une connexion et une transaction a MySQL
	** -----
	** $server ::		Adresse du serveur MySQL
	** $login ::		Login d'acces MySQL
	** $pass ::			Mot de passe associe au login
	** $db ::			Nom de la base de donnee a selectionner
	** $use_cache ::	Utilisation du cache SQL ?
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
		if (!$result = $this->mysqli->query($sql))
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
		return ($this->mysqli->query($sql));
	}

	/*
	** Voir parent::row()
	*/
	public function _row($result, $function = 'assoc')
	{
		$pointer = 'fetch_' . $function;
		return ($result->{$pointer}());
	}

	/*
	** Voir parent::free()
	*/
	public function _free($result)
	{
		if (is_object($result))
		{
			$result->free();
		}
	}

	/*
	** Retourne la derniere ID apres un INSERT en cas d'incrementation automatique
	*/
	public function last_id()
	{
		return ($this->mysqli->insert_id);
	}

	/*
	** Voir parent::count()
	*/
	public function _count($result)
	{
		return ($result->num_rows);
	}

	/*
	** Protege un champ de la requete
	** -----
	** $str :: Chaine a proteger
	*/
	public function escape($str)
	{
		return ($this->mysqli->real_escape_string($str));
	}

	/*
	** Renvoie le nombre de lignes affectees par une requete
	** -----
	** $result ::		Resultat d'une requete
	*/
	public function affected_rows($result)
	{
		return ($this->mysqli->affected_rows);
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
			case 1 :
			case 2 :
			case 3 :
				return ('int');

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
		return ($this->mysqli->error);
	}

	/*
	** Voir parent::close()
	*/
	public function _close()
	{
		$this->mysqli->close();
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
}

/* EOF */