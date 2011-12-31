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
 * Classe permettant de realiser des backups de la base de donnee
 */
abstract class Backup extends Fsb_model
{
	/**
	 * Type de base de donnee utilisee
	 *
	 * @var string
	 */
	private $dbal = '';

	/**
	 * Gestion des multi insertions
	 *
	 * @var bool
	 */
	public $multi_insert = false;

	/**
	 * Methode utilisee pour le dump des donnees
	 *
	 * @var string
	 */
	protected $dump_method;

	/**
	 * Affiche le backup
	 */
	const OUTPUT = 1;
	
	/**
	 * Lance le telechargement du backup
	 */
	const DOWNLOAD = 2;
	
	/**
	 * Sauve le backup dans un fichier
	 */
	const FTP = 3;
	
	/**
	 * Retourne le contenu du backup
	 */
	const GET = 4;

	/**
	 * Fait un backup de la structure
	 */
	const STRUCT = 1;
	
	/**
	 * Fait un backup des donnees
	 *
	 */
	const DATA = 2;
	
	/**
	 * Fait un backup des donnees et de la structure
	 */
	const ALL = 255;

	/**
	 * Ouvre le gestionaire de sortie pour le backup
	 *
	 * @param string $filename Nom du fichier
	 */
	abstract public function open($filename);
	
	/**
	 * Ecrit des donnees dans le gestionaire de sortie
	 *
	 * @param string $str Donnees a ecrire
	 */
	abstract public function write($str);
	
	/**
	 * Ferme le gestionaire de sortie
	 */
	abstract public function close();

	/**
	 * Design pattern factory, retourne une instance de la classe backup en fonction du gestionaire de sortie utilise
	 *
	 * @param string $sgbd Type de base de donnee utilisee
	 * @param int $output Type de gestionaire de sortie
	 * @return Backup Objet backup
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
			trigger_error('Base de donnee incorecte dans la classe Backup() : ' . $sgbd, FSB_ERROR);
		}
		$obj->dump_method = $sgbd;

		return ($obj);
	}

	/**
	 * Demarre le backup
	 *
	 * @param int $type Type des donnees a sauver (structure, donnees, tout)
	 * @param array $tables Liste des tables sur lesquelles effectuer le backup
	 * @return string Donnees du backup en cas de methode Backup::GET
	 */
	public function save($type, $tables)
	{
		$filename = $this->generate_filename();

		$this->open($filename);
		$this->create_header();
		$this->{'dump_' . $this->dump_method}($type, $tables);
		return ($this->close());
	}

	/**
	 * Cree le header pour les backups
	 *
	 * @return string
	 */
	private function create_header()
	{
		$header = sprintf("#\n# FSB version %s :: `%s` dump\n# Cree le %s\n#\n\n", Fsb::$cfg->get('fsb_version'), $this->dump_method, date("d/m/y H:i", CURRENT_TIME));

		return ($header);
	}

	/**
	 * Genere un nom pour le fichier du backup
	 *
	 * @return string
	 */
	private function generate_filename()
	{
		return ('backup_' . $this->dump_method . '_' . date('d_m_y_H_i', CURRENT_TIME) . '.sql');
	}

	/**
	 * Lance un dump des tables MySQL du forum
	 * La methode de dump de la base de donnee MySQL a ete inspiree par cette source :
	 * @link http://www.developpez.net/forums/viewtopic.php?p=1405354#1405354
	 *
	 * @param int $type Le type de donnees qu'on veut sauvegarder (structure, contenu ou les deux)
	 * @param array $save_table Les tables a sauvegarder
	 * @param bool $comment Ajoute un commentaire en debut de table
	 */
	public function dump_mysql($type, $save_table, $comment = true)
	{
		if (is_array($save_table))
		{
			$sql = "SHOW TABLES";
			$result = Fsb::$db->query($sql);
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
			trigger_error('La variable $save_table doit etre un tableau dans la classe backup() : ' . $save_table, FSB_ERROR);
		}
	}

	/**
	 * Lance un dump des tables PostGreSQL du forum
	 * Les methodes de dump PostgreSQL ont ete realisees a partir de celles de phpBB3 et de phpPgAdmin
	 * @link http://www.phpbb.com
	 * @link http://phppgadmin.sourceforge.net/
	 *
	 * @param int $type Le type de donnees qu'on veut sauvegarder (structure, contenu ou les deux)
	 * @param array $save_table Les tables a sauvegarder
	 * @param bool $comment Ajoute un commentaire en debut de table
	 */
	public function dump_pgsql($type, $save_table, $comment = true)
	{
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

				$this->write($this->pgsql_get_create_table($tablename, "\n", false));
			}

			if ($type & self::DATA)
			{
				$this->write($this->dump_database($tablename, "PostgreSQL", $comment, $this->multi_insert));
			}
		}
	}

	/**
	 * Cree le schema CREATE d'une table PostGreSQL
	 *
	 * @param string $table Nom de la table pour la creation du schema
	 * @param string $crlf Caractere de retour a la ligne
	 * @param bool $drop true pour inserer des ennonces DROP avant la creation des tables
	 * @return string Schema de la table
	 */
	private function pgsql_get_create_table($table, $crlf, $drop)
	{
		$schema_create = '';

		// Recupere les champs de la table et leur type
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
			// On recupere la valeur par defaut
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
				$schema_create .= ' NOT null';
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

	/**
	 * Permet de recuperer les sequences Postgresql
	 *
	 * @return string
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

	/**
	 * Lance un dump des tables SQLite du forum
	 *
	 * @param int $type Le type de donnees qu'on veut sauvegarder (structure, contenu ou les deux)
	 * @param array $save_table Les tables a sauvegarder
	 * @param bool $comment Ajoute un commentaire en debut de table
	 */
	public function dump_sqlite($type, $save_table, $comment = true)
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
					$this->write("\n#\n# Structure de la table SQLite `$tablename`\n#\n");
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
				$this->write($this->dump_database($tablename, "SQLite", $comment, $this->multi_insert));
			}
		}
	}

	/**
	 * Cree un dump du contenu d'une table
	 *
	 * @param string $tablename Nom de la table
	 * @param string $sgbd_name Type de base de donnee
	 * @param bool $comment Commentaire pour le dump
	 * @param bool $multi_insert Gestion des multi insertions
	 * @param array $except Contient la liste des champs a ne pas prendre en compte
	 */
	public function dump_database($tablename, $sgbd_name, $comment = true, $multi_insert = false, $except = array())
	{
		// Si la SGBD ne supporte pas les multi insertions on force le parametre a false
		if (!Fsb::$db->can_use_multi_insert)
		{
			$multi_insert = false;
		}

		$get_fields = false;
		$fields_type = array();
		$content = '';
		if ($comment)
		{
			$this->write("\n#\n# Contenu de la table $sgbd_name `$tablename`\n#\n");
		}

		// Donnees de la table
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
				if ($except && in_array($field, $except))
				{
					continue;
				}

				if (!$get_fields)
				{
					// On recupere les champs si cela n'a pas deja ete fait
					$fields_type[$field] = Fsb::$db->get_field_type($result, $field, $tablename);
				}

				// On recupere les valeurs de la ligne courante
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

			$get_fields = true;
		}

		if ($multi_insert)
		{
			$this->write(";\n");
		}
	}
}

/* EOF */
