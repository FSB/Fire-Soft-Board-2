<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/backup/backup.php
** | Begin :	20/05/2005
** | Last :		03/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Classe permettant de réaliser des backups de diverses données (mysql, pgsql, etc ...).
**
** ----
**
** La méthode de dump de la base de donnée MySQL a été inspirée par cette source :
**		http://www.developpez.net/forums/viewtopic.php?p=1405354#1405354
**
** Les méthodes de dump PostgreSQL ont été réalisées à partir de celles de phpBB3
** (http://www.phpbb.com) et de phpPgAdmin (http://phppgadmin.sourceforge.net/).
*/
abstract class Backup extends Fsb_model
{
	// DBMS utilisée
	private $dbal = '';

	// Multi insertion ?
	public $multi_insert = FALSE;

	// Méthode pour le dump
	protected $dump_method;

	const OUTPUT = 1;
	const DOWNLOAD = 2;
	const FTP = 3;
	const GET = 4;

	const STRUCT = 1;
	const DATA = 2;
	const ALL = 255;

	abstract public function open($filename);
	abstract public function write($str);
	abstract public function close();

	/*
	** Retourne une instance de la classe Backup, étendue par la classe gérant le buffer de sortie, et contenant
	** une propriété pointant sur la méthode avec laquelle on souhaite faire un backup.
	*/
	public static function &factory($sgbd, $output)
	{
		// Gestion du buffer
		switch ($output)
		{
			case self::OUTPUT :
				$obj =& new Backup_print();
			break;

			case self::DOWNLOAD :
				$obj =& new Backup_download();
			break;

			case self::FTP :
				$obj =& new Backup_ftp();
			break;

			case self::GET :
			default :
				$obj =& new Backup_get();
			break;
		}

		if (!method_exists($obj, 'dump_' . $sgbd))
		{
			trigger_error('Base de donnée incorecte dans la classe Backup() : ' . $sgbd, FSB_ERROR);
		}
		$obj->dump_method = $sgbd;

		return ($obj);
	}

	/*
	** Lance le backup
	*/
	public function save($type, $tables)
	{
		$filename = $this->generate_filename();

		$this->open($filename);
		$this->create_header();
		$this->{'dump_' . $this->dump_method}($type, $tables);
		return ($this->close());
	}

	/*
	** Créé un header pour les backups
	** -----
	** $data_type ::	Type de données à sauver (mysql, etc ..)
	*/
	private function create_header()
	{
		$header = sprintf("#\n# FSB version %s :: `%s` dump\n# Créé le %s\n#\n\n", Fsb::$cfg->get('fsb_version'), $this->dump_method, date("d/m/y H:i", CURRENT_TIME));

		return ($header);
	}

	/*
	** Génère un nom pour le backup
	** -----
	** $data_type ::	Type de données à sauver (mysql, etc ..)
	*/
	private function generate_filename()
	{
		return ('backup_' . $this->dump_method . '_' . date('d_m_y_H_i', CURRENT_TIME) . '.sql');
	}

	/*
	** Effectue un dump des tables MySQL du forum
	** -----
	** $type ::			Le type de données qu'on veut sauvegarder (structure, contenu ou les deux)
	** $save_table ::	Les tables a sauvegarder
	** $comment ::		Ajoute un commentaire en début de table
	*/
	public function dump_mysql($type, $save_table, $comment = TRUE)
	{
		if (is_array($save_table))
		{
			$sql = "SHOW TABLES";
			$result = Fsb::$db->query($sql);
			$content = '';
			while ($table = Fsb::$db->row($result, 'row'))
			{
				if (in_array($table[0], $save_table))
				{
					$struct = '';
					$data = '';
					if ($type & self::STRUCT)
					{
						if ($comment)
						{
							$this->write("#\n# Structure de la table MySQL `${table[0]}`\n#\n");
						}

						$sql = 'SHOW CREATE TABLE ' . $table[0];
						$create_result = Fsb::$db->query($sql);
						while ($create = Fsb::$db->row($create_result, 'row'))
						{
							$this->write($create[1] . ";\n");
						}
						$this->write("\n");
					}

					if ($type & self::DATA)
					{
						$this->dump_database($table[0], "MySQL", $comment, $this->multi_insert);
					}
				}
			}
		}
		else
		{
			trigger_error('La variable $save_table doit être un tableau dans la classe backup() : ' . $save_table, FSB_ERROR);
		}
		return ($content);
	}

	/*
	** Effectue un backup des tables PostgreSQL du forum.
	** -----
	** $type ::			Type de backup
	** $save_table ::	Tables pour le backup
	** $comment ::		Ajoute un commentaire en début de table
	*/
	public function dump_pgsql($type, $save_table, $comment = TRUE)
	{
		$content = '';
		if ($type & self::STRUCT)
		{
			$this->write($this->pgsql_get_sequence());
		}

		foreach ($save_table AS $tablename)
		{
			if ($type & self::STRUCT)
			{
				if ($comment)
				{
					$this->write("\n#\n# Structure de la table PostgreSQL `$tablename`\n#\n");
				}

				$this->write($this->pgsql_get_create_table($tablename, "\n", FALSE));
			}

			if ($type & self::DATA)
			{
				$this->write($this->dump_database($tablename, "PostgreSQL", $comment, $this->multi_insert));
			}
		}
		return ($content);
	}

	/*
	** Créer le shéma CREATE d'une table PostgreSQL.
	** -----
	** $table ::	Nom de la table pour la création du shéma
	** $crlf ::		Caractère de retour à la ligne
	** $drop ::		TRUE pour insérer des ennoncés DROP vaant la création des tables
	*/
	private function pgsql_get_create_table($table, $crlf, $drop)
	{
		$schema_create = '';

		// Récupère les champs de la table et leur type
		$field_query = "SELECT a.attnum, a.attname AS field, t.typname as type, a.attlen AS length, a.atttypmod as lengthvar, a.attnotnull as notnull
			FROM pg_class c, pg_attribute a, pg_type t
			WHERE c.relname = '$table'
				AND a.attnum > 0
				AND a.attrelid = c.oid
				AND a.atttypid = t.oid
			ORDER BY a.attnum";
		$result = Fsb::$db->query($field_query);

		if ($drop)
		{
			$schema_create .= "DROP TABLE $table;$crlf";
		}

		$schema_create .= "CREATE TABLE $table($crlf";
		while ($row = Fsb::$db->row($result))
		{
			// On récupère la valeur par défaut
			$sql_get_default = "SELECT d.adsrc AS rowdefault
				FROM pg_attrdef d, pg_class c
				WHERE (c.relname = '$table')
					AND (c.oid = d.adrelid)
					AND d.adnum = " . $row['attnum'];
			$def_res = Fsb::$db->simple_query($sql_get_default);

			if (!$def_res)
			{
				unset($row['rowdefault']);
			}
			else
			{
				$row_default = Fsb::$db->row($def_res);
				$row['rowdefault'] = $row_default['rowdefault'];
			}

			if ($row['type'] == 'bpchar')
			{
				$row['type'] = 'char';
			}

			$schema_create .= '	' . $row['field'] . ' ' . $row['type'];

			if (eregi('char', $row['type']))
			{
				if ($row['lengthvar'] > 0)
				{
					$schema_create .= '(' . ($row['lengthvar'] -4) . ')';
				}
			}

			if (eregi('numeric', $row['type']))
			{
				$schema_create .= '(';
				$schema_create .= sprintf("%s,%s", (($row['lengthvar'] >> 16) & 0xffff), (($row['lengthvar'] - 4) & 0xffff));
				$schema_create .= ')';
			}

			if (!empty($row['rowdefault']))
			{
				$schema_create .= ' DEFAULT ' . $row['rowdefault'];
			}

			if ($row['notnull'] == 't')
			{
				$schema_create .= ' NOT NULL';
			}

			$schema_create .= ",$crlf";
		}

		// Liste des clefs primaires
		$sql_pri_keys = "SELECT ic.relname AS index_name, bc.relname AS tab_name, ta.attname AS column_name, i.indisunique AS unique_key, i.indisprimary AS primary_key
				FROM pg_class bc, pg_class ic, pg_index i, pg_attribute ta, pg_attribute ia
				WHERE (bc.oid = i.indrelid)
					AND (ic.oid = i.indexrelid)
					AND (ia.attrelid = i.indexrelid)
					AND	(ta.attrelid = bc.oid)
					AND (bc.relname = '$table')
					AND (ta.attrelid = i.indrelid)
					AND (ta.attnum = i.indkey[ia.attnum-1])
				ORDER BY index_name, tab_name, column_name ";
		$result = Fsb::$db->query($sql_pri_keys);

		$primary_key = '';
		while ($row = Fsb::$db->row($result))
		{
			if ($row['primary_key'] == 't')
			{
				if (!empty($primary_key))
				{
					$primary_key .= ', ';
				}

				$primary_key .= $row['column_name'];
				$primary_key_name = $row['index_name'];
			}
			else
			{
				$index_rows[$row['index_name']]['table'] = $table;
				$index_rows[$row['index_name']]['unique'] = ($row['unique_key'] == 't') ? ' UNIQUE ' : '';

				if (!isset($index_rows[$row['index_name']]['column_names']))
				{
					$index_rows[$row['index_name']]['column_names'] = '';
				}
				$index_rows[$row['index_name']]['column_names'] .= $row['column_name'] . ', ';
			}
		}

		$index_create = '';
		if (!empty($index_rows))
		{
			foreach ($index_rows AS $idx_name => $props)
			{
				$props['column_names'] = ereg_replace(", $", "" , $props['column_names']);
				$index_create .= 'CREATE ' . $props['unique'] . " INDEX $idx_name ON $table (" . $props['column_names'] . ");$crlf";
			}
		}

		if (!empty($primary_key))
		{
			$schema_create .= "	CONSTRAINT $primary_key_name PRIMARY KEY ($primary_key),$crlf";
		}

		$schema_create = ereg_replace(',' . $crlf . '$', '', $schema_create);
		$index_create = ereg_replace(',' . $crlf . '$', '', $index_create);

		$schema_create .= "$crlf);$crlf";

		if (!empty($index_create))
		{
			$schema_create .= $index_create;
		}

		return (stripslashes($schema_create));
	}

	/*
	** Permet de récupérer les séquences Postgresql
	*/
	private function pgsql_get_sequence()
	{
		$content = '';
		$sql = 'SELECT c.relname AS seqname FROM pg_catalog.pg_class c, pg_catalog.pg_user u, pg_catalog.pg_namespace n
				WHERE c.relowner = u.usesysid
					AND c.relnamespace = n.oid
					AND c.relkind = \'S\'
					AND n.nspname = \'public\'
				ORDER BY seqname';
		$result = Fsb::$db->query($sql);

		if (!$row = Fsb::$db->row($result))
		{
			return ($content);
		}
		else
		{
			$content .= "# Sequences \n";
			do
			{
				$row = Fsb::$db->row($result);
				$sequence_name = $row['seqname'];

				$sql_sequence = "SELECT * FROM $sequence_name";
				$result_sequence = Fsb::$db->query($sql_sequence);

				if ($row_sequence = Fsb::$db->row($result_sequence))
				{
					$content .= "CREATE SEQUENCE $sequence_name start " . $row_sequence['last_value'] . ' increment ' . $row_sequence['increment_by'] . ' maxvalue ' . $row_sequence['max_value'] . ' minvalue ' . $row_sequence['min_value'] . ' cache ' . $row_sequence['cache_value'] . ";\n";
				}

				if ($row_sequence['last_value'] > 1)
				{
					$content .= "SELECT NEXTVALE('$sequence_name'); \n";
				}

				Fsb::$db->free($result_sequence);
			}
			while ($row = Fsb::$db->row($result));
		}
		Fsb::$db->free($result);

		return ($content);
	}

	/*
	** Effectue un backup des tables SQLite du forum.
	** -----
	** $type ::			Type de backup
	** $save_table ::	Tables pour le backup
	** $comment ::		Ajoute un commentaire en début de table
	*/
	public function dump_sqlite($type, $save_table, $comment = TRUE)
	{
		$content = '';
		foreach ($save_table AS $tablename)
		{
			if ($type & self::STRUCT)
			{
				$sql = "SELECT sql
						FROM sqlite_master
						WHERE tbl_name='$tablename'";
				$result = Fsb::$db->query($sql);

				if ($comment)
				{
					$content .= "\n#\n# Structure de la table SQLite `$tablename`\n#\n";
				}

				while ($row = Fsb::$db->row($result))
				{
					if ($row['sql'])
					{
						$content .= $row['sql'] . ";\n";
					}
				}
			}

			if ($type & self::DATA)
			{
				$content .= $this->dump_database($tablename, "SQLite", $comment, $this->multi_insert);
			}
		}
		return ($content);
	}

	/*
	** Créé les requètes d'insertion, commun pour chaque des bases de donnée.
	** -----
	** $tablename ::	Nom de la table
	** $dbms_name ::	Nom de la SGBD
	** $comment ::		Ajoute un commentaire en début de table
	** $multi_insert ::	Gérer les requètes sous forme de multi insertion
	** $exept ::		Contient la liste des champs à ne pas prendre en compte
	*/
	public function dump_database($tablename, $sgbd_name, $comment = TRUE, $multi_insert = FALSE, $exept = array())
	{
		// Si la SGBD ne supporte pas les multi insertions on force le paramètre à FALSE
		if (!Fsb::$db->can_use_multi_insert)
		{
			$multi_insert = FALSE;
		}

		$get_fields = FALSE;
		$fields_type = array();
		$content = '';
		if ($comment)
		{
			$this->write("\n#\n# Contenu de la table $sgbd_name `$tablename`\n#\n");
		}

		// Données de la table
		$sql = "SELECT *
				FROM $tablename";
		$result = Fsb::$db->query($sql);
		$multi_values = '';
		$k = 0;
		while ($row = Fsb::$db->row($result))
		{
			$values = '';
			foreach ($row AS $field => $value)
			{
				// Si on ne prend pas en compte le champ
				if ($exept && in_array($field, $exept))
				{
					continue;
				}

				if (!$get_fields)
				{
					// On récupère les champs si cela n'a pas déjà été fait
					$fields_type[$field] = Fsb::$db->get_field_type($result, $field, $tablename);
				}

				// On récupère les valeurs de la ligne courante
				$values .= (($values) ? ', ' : '') . (($fields_type[$field] == 'string') ? '\'' . Fsb::$db->escape($value) . '\'' : $value);
			}

			if (!$get_fields)
			{
				$fields = implode(', ', array_keys($fields_type));
			}

			if (!$multi_insert || !$get_fields)
			{
				$this->write("INSERT INTO $tablename ($fields) VALUES ");
			}

			if ($multi_insert && $get_fields)
			{
				$this->write(",\n");
			}

			$this->write("($values)");

			if (!$multi_insert)
			{
				$this->write(";\n");
			}

			$get_fields = TRUE;
		}

		if ($multi_insert)
		{
			$this->write(";\n");
		}
	}
}

/* EOF */