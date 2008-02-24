<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/dbal/dbal_sqlite.php
** | Begin :	15/08/2005
** | Last :		10/02/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Classe pour SQLite (necessite PHP5)
** Attention, SQLite est sensible a la casse. Des LOWER ont ete ajoutes dans les requetes
** ou la casse risquerait de jouer de mauvais tours, mais pas partout (question de logique).
** Si vous developpez un MOD utilisant des chaines de caracteres dans les requetes, veillez a
** bien tester le fonctionement de la requete sous SQLite
*/

class Dbal_sqlite extends Dbal
{
	/*
	** Constructeur de la classe Sql
	** Etablit la connexion a la base de donnee
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
		$this->use_cache = $use_cache;
		$this->id = NULL;
		$errstr = '';
		if (!$this->id = @sqlite_open(ROOT . 'main/class/dbal/sqlite/' . $db . '.sqlite' . ((trim($port)) ? ':' . $port : ''), 0666, $errstr))
		{
			return ;
		}

		// Cette requete evite aux alias de se retrouver dans le nom des champs.
		$this->simple_query('PRAGMA short_column_names = 1');

		// Ce que peut faire SQLite
		$this->can_use_explain = FALSE;
		$this->can_use_replace = FALSE;
		$this->can_use_multi_insert = FALSE;
		$this->can_use_truncate = FALSE;
		return ($this->id);
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
		$result = (($buffer) ? @sqlite_query($this->id, $sql) : @sqlite_unbuffered_query($this->id, $sql));
		if (!$result && sqlite_last_error($this->id) != 0)
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
		return (sqlite_query($this->id, $sql));
	}

	/*
	** Voir parent::row()
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

	/*
	** Voir parent::free()
	*/
	public function _free($result)
	{
		return (TRUE);
	}

	/*
	** Retourne la derniere ID apres un INSERT en cas d'incrementation automatique
	*/
	public function last_id()
	{
		return (sqlite_last_insert_rowid($this->id));
	}

	/*
	** Voir parent::count()
	*/
	public function _count($result)
	{
		return (sqlite_num_rows($result));
	}

	/*
	** Protege un champ de la requete
	** -----
	** $str :: Chaine a proteger
	*/
	public function escape($str)
	{
		return (sqlite_escape_string($str));
	}

	/*
	** Renvoie le nombre de lignes affectees par une requete
	** -----
	** $result ::		Resultat d'une requete
	*/
	public function affected_rows($result)
	{
		return (sqlite_changes($this->id));
	}

	/*
	** Recupere le type d'une colone
	** -----
	** $result ::	Resultat de la requete
	** $field ::	Champ a verifier
	** $table ::	Nom de la table concernee
	*/
	public function field_type($result, $field, $table = NULL)
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

	/*
	** On retourne dans tous les cas 'string', car les types n'ont pas reellement d'importance
	** avec SQLite
	** -----
	** $result ::	Resultat de la requete
	** $field ::	Champ a verifier
	** $table ::	Nom de la table concernee
	*/
	public function get_field_type($result, $field, $table = NULL)
	{
		return ('string');
	}

	/*
	** Renvoie un tableau contenant la liste des tables
	** -----
	** $limit ::	Si TRUE, ne recupere que les tables ayant le meme prefixe que le forum
	*/
	public function list_tables($limit = TRUE)
	{
		$tables = array();
		$sql = 'SELECT name AS tablename
					FROM (SELECT * FROM sqlite_master UNION SELECT * FROM sqlite_temp_master) 
					WHERE type=\'table\' ORDER BY name';
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
			return ($this->query($sql));
		}

		$this->multi_insert = array();
	}

	/*
	** Renvoie la derniere erreur
	*/
	public function sql_error()
	{
		return (sqlite_error_string(sqlite_last_error($this->id)));
	}

	/*
	** Voir parent::close()
	*/
	public function _close()
	{
		sqlite_close($this->id);
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
					$this->simple_query('BEGIN TRANSACTION');
				}
				$this->in_transaction = TRUE;
			break;

			case 'commit' :
				if ($this->in_transaction)
				{
					$this->simple_query('COMMIT TRANSACTION');
				}
				$this->in_transaction = FALSE;
			break;

			case 'rollback' :
				if ($this->in_transaction)
				{
					$this->simple_query('ROLLBACK TRANSACTION');
				}
				$this->in_transaction = FALSE;
			break;
		}
	}

	/*
	** Supprime des elements de plusieurs tables
	** (SQLITE ne supporte pas les multi suppressions)
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
		return ('LIKE');
	}

	/*
	** SQLite ne supportant pas l'operateur ALTER dans certaines de ses versions, cette methode va simuler
	** le comportement d'un ALTER en permettant d'ajouter / supprimer un champ, ou de renommer la table.
	** -----
	** $tablename ::	Nom de la table
	** $action ::		Action a effectuer : ADD, DROP ou RENAME
	** $arg ::			Argument passe a l'action.
	**						Lors d'un ajout ou d'une suppression, on passe le nom du champ.
	**						Lors du renomage de la table, on passe le nom de la nouvelle table.
	*/
	public function alter($tablename, $action, $arg)
	{
		// On recupere les champs de la table originale
		$cols = array_keys(sqlite_fetch_column_types($tablename, $this->id, SQLITE_ASSOC));

		switch ($action)
		{
			case 'ADD' :
				$is_temporary = TRUE;
				$name = $tablename . '__tmp';
				$fields = implode(', ', $cols) . ', \'\' AS ' . $arg . '';
			break;

			case 'DROP' :
				$is_temporary = TRUE;
				$name = $tablename . '__tmp';

				// Suppression du champ
				$tmp = array_flip($cols);
				unset($tmp[$arg]);
				$cols = array_keys($tmp);
				$fields = implode(', ', $cols);
			break;

			case 'RENAME' :
				$is_temporary = FALSE;
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