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
 * Gestion des champs de profil dynamique dans l'admnistration
 */
class Profil_fields_admin extends Profil_fields
{
	/**
	 * Genere les switch utilises pour la creation de ce champ de profil
	 *
	 * @param int $type Type du champ de profil
	 */
	public static function form(&$type)
	{
		if (!isset(self::$type[$type]))
		{
			$type = self::TEXT;
		}

		foreach (array_keys(self::$type[$type]) AS $key)
		{
			Fsb::$tpl->set_switch('profile_field_' . $key);
		}
	}

	/**
	 * Validation de la creation du champ
	 *
	 * @param int $type
	 * @param array $errstr Erreurs rencontrees pendant la validation
	 * @return array Informations sur le champ
	 */
	public static function validate($type, &$errstr)
	{
		if (!isset(self::$type[$type]))
		{
			$type = self::TEXT;
		}

		$return = array();
		$return['pf_html_type'] =	$type;
		$return['pf_regexp'] =		Http::request('pf_regexp', 'post');
		$return['pf_lang'] =		trim(Http::request('pf_lang', 'post'));
		$return['pf_lang_desc'] =	trim(Http::request('pf_desc', 'post'));
		$return['pf_output'] =		trim(Http::request('pf_output', 'post'));
		$return['pf_topic'] =		intval(Http::request('pf_topic', 'post'));
		$return['pf_register'] =	intval(Http::request('pf_register', 'post'));
		$return['pf_maxlength'] =	intval(Http::request('pf_maxlength', 'post'));
		$return['pf_sizelist'] =	intval(Http::request('pf_sizelist', 'post'));
		$return['pf_list'] =		array();
		
		// Groupes visibles
		$return['pf_groups'] = (array) Http::request('pf_groups', 'post');
		$return['pf_groups'] = array_map('intval', $return['pf_groups']);
		$return['pf_groups'] = implode(',', $return['pf_groups']);

		// Verification des erreurs
		if (!$return['pf_lang'])
		{
			$errstr[] = Fsb::$session->lang('adm_pf_need_lang');
		}
		
		if (isset(self::$type[$type]['maxlength']) && ($return['pf_maxlength'] <= self::$type[$type]['maxlength']['min'] || $return['pf_maxlength'] > self::$type[$type]['maxlength']['max']))
		{
			$errstr[] = sprintf(Fsb::$session->lang('adm_pf_bad_maxlength'), self::$type[$type]['maxlength']['min'], self::$type[$type]['maxlength']['max']);
		}

		if (isset(self::$type[$type]['regexp']) && @preg_match('#' . str_replace('#', '\#', $return['pf_regexp']) . '#i', 'foo') === false)
		{
			$errstr[] = Fsb::$session->lang('adm_pf_bad_regexp');
		}

		if (isset(self::$type[$type]['list']))
		{
			$return['pf_list'] = trim(Http::request('pf_list', 'post'));
			if (!$return['pf_list'])
			{
				$errstr[] = Fsb::$session->lang('adm_pf_need_list');
			}
			else
			{
				// Suppression des lignes vides
				$return['pf_list'] = array_map('trim', explode("\n", $return['pf_list']));
				$new = array();
				foreach ($return['pf_list'] AS $key => $value)
				{
					if ($value)
					{
						$new[] = $value;
					}
				}
				$return['pf_list'] = $new;
			}
		}

		return ($return);
	}

	/**
	 * Ajoute un nouveau champ de profil dynamique
	 *
	 * @param int $field_type Constante definissant la table ciblee (PROFIL_FIELDS_CONTACT ou PROFIL_FIELDS_PERSONAL)
	 * @param array $data Informations sur le champ de profil
	 */
	public static function add($field_type, $data)
	{
		switch (SQL_DBAL)
		{
			case 'mysql' :
			case 'mysqli' :
				$method = 'add_column_mysql';
			break;

			case 'pgsql' :
				$method = 'add_column_pgsql';
			break;

			case 'sqlite' :
				$method = 'add_column_sqlite';
			break;
		}
		
		// on recupere l'ordre maximale pour placer le nouveau champ
		$sql = 'SELECT MAX(pf_order) AS max_order
				FROM ' . SQL_PREFIX . 'profil_fields
				WHERE pf_type = ' . $field_type . '
				LIMIT 1';
		$data['pf_order'] = Fsb::$db->get($sql, 'max_order') + 1;
		$data['pf_type'] = $field_type;
		$data['pf_list'] = serialize($data['pf_list']);

		Fsb::$db->insert('profil_fields', $data);

		switch ($field_type)
		{
			case PROFIL_FIELDS_CONTACT :
				$tablename = 'users_contact';
				$sql_field_name = 'contact_';
			break;
			
			case PROFIL_FIELDS_PERSONAL :
				$tablename = 'users_personal';
				$sql_field_name = 'personal_';
			break;
		}
		Profil_fields_admin::$method(SQL_PREFIX . $tablename, $sql_field_name, $data['pf_html_type'], Fsb::$db->last_id());
	}

	/**
	 * Ajoute une colonne dans une table mysql
	 *
	 * @param string $tablename Nom de la table
	 * @param string $sql_field_name Prefixe du nom du champ
	 * @param int $type Type du champ
	 * @param int $last_id ID pour le nom du champ
	 */
	private static function add_column_mysql($tablename, $sql_field_name, $type, $last_id)
	{
		$sql_alter = 'ALTER TABLE ' . $tablename . ' ADD ' . Fsb::$db->escape($sql_field_name . $last_id);

		switch ($type)
		{
			case self::TEXT :
				$sql_alter .= ' VARCHAR(255) NOT null';
			break;
			
			case self::TEXTAREA :
				$sql_alter .= ' TEXT NOT null';
			break;
			
			case self::RADIO :
			case self::SELECT :
				$sql_alter .= ' TINYINT NOT null';
			break;
			
			case self::MULTIPLE :
				$sql_alter .= ' VARCHAR(255)';
			break;
		}
		Fsb::$db->query($sql_alter);
	}

	/**
	 * Ajoute une colonne dans une table PostgreSQL
	 *
	 * @param string $tablename Nom de la table
	 * @param string $sql_field_name Prefixe du nom du champ
	 * @param int $type Type du champ
	 * @param int $last_id ID pour le nom du champ
	 */
	private static function add_column_pgsql($tablename, $sql_field_name, $type, $last_id)
	{
		// On construit le debut de la requete ALTER
		$sql_alter = 'ALTER TABLE ' . $tablename;
		
		// On recupere le nom du champ a creer, a partir de la derniere ID cree
		$sql_alter .= ' ADD ' . Fsb::$db->escape($sql_field_name . $last_id);
		
		// On cree le type du champ dans la requete
		switch ($type)
		{
			case self::TEXT :
				$sql_alter .= ' VARCHAR(255)';
			break;
			
			case self::TEXTAREA :
				$sql_alter .= ' TEXT';
			break;
			
			case self::RADIO :
			case self::SELECT :
				$sql_alter .= ' INT2';
			break;
			
			case self::MULTIPLE :
				$sql_alter .= ' VARCHAR(255)';
			break;
		}

		// On lance la requete ALTER
		Fsb::$db->query($sql_alter);
	}

	/**
	 * Ajoute une colonne dans une table SQLITE
	 *
	 * @param string $tablename Nom de la table
	 * @param string $sql_field_name Prefixe du nom du champ
	 * @param int $type Type du champ
	 * @param int $last_id ID pour le nom du champ
	 */
	private static function add_column_sqlite($tablename, $sql_field_name, $type, $last_id)
	{
		Fsb::$db->alter($tablename, 'ADD', $sql_field_name . $last_id);
	}

	/**
	 * Met a jour un champ du profil dynamique
	 *
	 * @param int $field_id ID du champ de profil
	 * @param array $data Informations sur la mise a jour
	 */
	public static function update($field_id, $data)
	{
		unset($data['pf_type']);
		$data['pf_list'] = serialize($data['pf_list']);
		Fsb::$db->update('profil_fields', $data, 'WHERE pf_id = ' . $field_id);
	}

	/**
	 * Supprime un champ de profil dynamique
	 *
	 * @param int $field_id ID du champ a supprimer
	 * @param int $field_type Constante definissant la table ciblee (PROFIL_FIELDS_CONTACT ou PROFIL_FIELDS_PERSONAL)
	 */
	public static function delete($field_id, $field_type)
	{
		switch (SQL_DBAL)
		{
			case 'mysql' :
			case 'mysqli' :
				$method = 'drop_column_mysql';
			break;

			case 'pgsql' :
				$method = 'drop_column_pgsql';
			break;

			case 'sqlite' :
				$method = 'drop_column_sqlite';
			break;
		}

		// Nom de la table
		switch ($field_type)
		{
			case PROFIL_FIELDS_CONTACT :
				$tablename =  'users_contact';
				$sql_field_name = 'contact_';
			break;
			
			case PROFIL_FIELDS_PERSONAL :
				$tablename = 'users_personal';
				$sql_field_name = 'personal_';
			break;
			
			default :
				trigger_error('Profil_fields->create() :: Mauvais parametre pour le type de profil : '  . $field_type, FSB_ERROR);
			break;
		}
		self::$method(SQL_PREFIX . $tablename, $sql_field_name . $field_id);
		
		// On supprime le champ de la table profil_fields
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'profil_fields
				WHERE pf_id = ' . $field_id;
		Fsb::$db->query($sql);
	}

	/**
	 * Supprime une colone dans une table mysql
	 *
	 * @param string $tablename Nom de la table
	 * @param string $sql_field_name Nom du champ
	 */
	private static function drop_column_mysql($tablename, $sql_field_name)
	{
		$sql_alter = "ALTER TABLE $tablename DROP $sql_field_name";
		Fsb::$db->query($sql_alter);
	}

	/**
	 * Supprime une colone dans une table PostgreSQL
	 *
	 * @param string $tablename Nom de la table
	 * @param string $sql_field_name Nom du champ
	 */
	private static function drop_column_pgsql($tablename, $sql_field_name)
	{
		$sql_alter = "ALTER TABLE $tablename DROP $sql_field_name";
		Fsb::$db->query($sql_alter);
	}

	/**
	 * Supprime une colone dans une table SQLITE
	 *
	 * @param string $tablename Nom de la table
	 * @param string $sql_field_name Nom du champ
	 */
	private static function drop_column_sqlite($tablename, $sql_field_name)
	{
		Fsb::$db->alter($tablename, 'DROP', $sql_field_name);
	}

	/**
	 * Deplace un champ de profil dynamique
	 *
	 * @param int $field_id ID du champ
	 * @param int $field_move 1 pour deplacer vers le bas, -1 pour deplacer vers le haut
	 * @param int $field_type Constante definissant la table ciblee (PROFIL_FIELDS_CONTACT ou PROFIL_FIELDS_PERSONAL)
	 */
	public static function move($field_id, $field_move, $field_type)
	{		
		$sql = 'SELECT pf_order
				FROM ' . SQL_PREFIX . 'profil_fields
				WHERE pf_id = ' . $field_id . '
					AND pf_type = ' . $field_type;
		$pf_order = Fsb::$db->get($sql, 'pf_order');
		
		$move = intval($pf_order) + $field_move;
		$sql = 'SELECT pf_order, pf_id
				FROM ' . SQL_PREFIX . 'profil_fields
				WHERE pf_order = ' . $move . '
					AND pf_type = ' . $field_type;
		$result = Fsb::$db->query($sql);
		$dest = Fsb::$db->row($result);
		Fsb::$db->free($result);
		
		if ($dest)
		{
			Fsb::$db->update('profil_fields', array(
				'pf_order' =>	$dest['pf_order'],
			), 'WHERE pf_id = ' . $field_id);
			
			Fsb::$db->update('profil_fields', array(
				'pf_order' =>	$pf_order,
			), 'WHERE pf_id = ' . $dest['pf_id']);
		}
	}
}

/* EOF */
