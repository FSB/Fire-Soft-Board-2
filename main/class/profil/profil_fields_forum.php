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
 * Gestion des champs de profil dynamique sur le forum
 */
class Profil_fields_forum extends Profil_fields
{
	/**
	 * Informations sur les champs de profil mises en cache
	 *
	 * @var array
	 */
	private static $fields_data = array();

	/**
	 * Affiche le formulaire pour remplir les champs de profil
	 *
	 * @param int $field_type Type du champ de profil
	 * @param string $field_name Nom du champ
	 * @param int $u_id ID du membre, si elle est renseignee on recupere les informations dans sa table
	 * @param bool $register_page true si on est sur la page d'inscription
	 */
	public static function form($field_type, $field_name, $u_id = null, $register_page = false)
	{
		$data = array();
		if (!is_null($u_id))
		{
			// Donnees du membre
			$sql = 'SELECT *
					FROM ' . SQL_PREFIX . 'users_' . $field_name . '
					WHERE u_id = ' . $u_id;
			$data = Fsb::$db->request($sql);
		}

		// Les donnees emises par formulaires ecrasent les valeurs par defaut du membre
		$new = array();
		foreach ($_POST AS $key => $value)
		{
			if (substr($key, 0, strlen($field_name)) == $field_name)
			{
				$new[$key] = $value;
			}
		}

		if ($new)
		{
			$data = $new;
		}

		// On recupere la liste des champs personels
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'profil_fields
				WHERE pf_type = ' . $field_type . '
				' . (($register_page) ? 'AND pf_register = 1' : '') . '
				ORDER BY pf_order';
		$result = Fsb::$db->query($sql, 'profil_fields_');
		while ($row = Fsb::$db->row($result))
		{
			// Valeur par defaut
			$name = $field_name . '_' . $row['pf_id'];
			$default_value = (isset($data[$name])) ? $data[$name] : '';
			if ($row['pf_html_type'] == self::MULTIPLE && !is_array($default_value))
			{
				$default_value = ($default_value) ? explode(",", $default_value) : array();
			}

			Fsb::$tpl->set_switch('have_profil_fields');
			Fsb::$tpl->set_blocks('field', array(
				'TYPE' =>		self::$type[$row['pf_html_type']]['name'],
				'NAME' =>		$name,
				'VALUE' =>		(is_string($default_value)) ? htmlspecialchars($default_value) : '',
				'LANG' =>		String::parse_lang($row['pf_lang']),
				'DESC' =>		String::parse_lang($row['pf_lang_desc']),
				'MAXLENGTH' =>	$row['pf_maxlength'],
				'SIZELIST' =>	$row['pf_sizelist'],
			));

			// En fonction du type HTML de la ligne on genere differement les arguments
			switch ($row['pf_html_type'])
			{
				case self::TEXT :
				case self::TEXTAREA :
				break;

				case self::MULTIPLE :
				case self::RADIO :
				case self::SELECT :
					$list = unserialize($row['pf_list']);
					foreach ($list AS $key => $value)
					{
						Fsb::$tpl->set_blocks('field.list', array(
							'VALUE' =>		htmlspecialchars($key),
							'LANG' =>		$value,
							'IS_SELECT' =>	($row['pf_html_type'] == self::MULTIPLE && in_array($key, $default_value)) ? true : false,
						));
					}
				break;
			}
		}
		Fsb::$db->free($result);
	}

	/*
	** Valide le formulaire pour les champs personels
	** -----
	** $field_type ::		
	** $field_name ::		
	** $errstr ::			
	** $u_id ::				
	** $register_page ::	Page d'inscription ?
	*/
	/**
	 * Valide les champs de profil
	 *
	 * @param int $field_type Type de champ de profil
	 * @param string $field_name Nom du champ de profil
	 * @param array $errstr Logs d'erreurs
	 * @param int $u_id Si l'ID du membre est renseignee, on insere les donnees dans la base
	 * @param bool $register_page true si on est sur la page d'inscription
	 * @return array Informations sur les champs 
	 */
	public static function validate($field_type, $field_name, &$errstr, $u_id = null, $register_page = false)
	{
		// Liste des champs personalises
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'profil_fields
				WHERE pf_type = ' . $field_type . '
				' . (($register_page) ? 'AND pf_register = 1' : '') . '
				ORDER BY pf_order';
		$result = Fsb::$db->query($sql, 'profil_fields_');
		$post_data = array();
		while ($row = Fsb::$db->row($result))
		{
			$key = $field_name . '_' . $row['pf_id'];
			$info = self::$type[$row['pf_html_type']];
			$post_data[$key] = Http::request($key, 'post');

			if ($row['pf_html_type'] == self::MULTIPLE)
			{
				$post_data[$key] = implode(',', (array) $post_data[$key]);
			}

			// Verification de longueur
			if (is_array($errstr) && isset($info['maxlength']) && strlen($post_data[$key]) > $info['maxlength'])
			{
				$errstr[] = sprintf(Fsb::$session->lang('user_error_personal_textarea'), $info['maxlength'], String::parse_lang($row['pf_lang']), strlen($post_data[$key]));
			}

			if (is_array($errstr) && isset($info['regexp']) && trim($row['pf_regexp']) && !preg_match(Regexp::pattern($row['pf_regexp'], true, 'i'), $post_data[$key]) && trim($post_data[$key]))
			{
				$errstr[] = sprintf(Fsb::$session->lang('user_error_personal_field'), $row['pf_lang']);
			}
		}
		Fsb::$db->free($result);

		// Insertion dans la base de donnee, si aucune erreur
		if (!is_null($u_id) && !$errstr && $post_data)
		{
			$post_data['u_id'] = array($u_id, true);
			Fsb::$db->insert('users_' . $field_name, $post_data, 'REPLACE');
		}

		return ($post_data);
	}

	/**
	 * Affiche les valeurs des champs de profils d'un membre
	 *
	 * @param int $field_type Type de champ de profil
	 * @param string $field_name Nom du champ profil
	 * @param array $user_data Donnees du membre
	 */
	public static function show_fields($field_type, $field_name, &$user_data)
	{
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'profil_fields
				WHERE pf_type = ' . $field_type . '
					ORDER BY pf_order';
		$result = Fsb::$db->query($sql, 'profil_fields_');
		while ($row = Fsb::$db->row($result))
		{
			// On verifie si le membre appartient a un groupe pouvant voir le champ
			$pf_groups = ($row['pf_groups']) ? explode(',', $row['pf_groups']) : array();
			if ($pf_groups && !array_intersect(Fsb::$session->data['groups'], $pf_groups))
			{
				continue;
			}

			// On recupere la valeur du champ
			$value = null;
			if (isset($user_data[$field_name . '_' . $row['pf_id']]))
			{
				if ($user_data[$field_name . '_' . $row['pf_id']] || in_array($row['pf_html_type'], array(self::RADIO, self::SELECT, self::MULTIPLE)))
				{
					$value = $user_data[$field_name . '_' . $row['pf_id']];
				}
			}

			if (!is_null($value))
			{
				switch ($row['pf_html_type'])
				{
					case self::RADIO :
					case self::SELECT :
						$ary = unserialize($row['pf_list']);
						$value = (isset($ary[$value])) ? self::parse_value($ary[$value], $row['pf_output']) : '';
					break;

					case self::MULTIPLE :
						$ary = unserialize($row['pf_list']);
						$value = explode(',', $value);
						$exp_value = array();
						foreach ($value AS $subvalue)
						{
							if (isset($ary[$subvalue]))
							{
								$exp_value[] = self::parse_value($ary[$subvalue], $row['pf_output']);
							}
						}

						$value = implode('<br />', $exp_value);
					break;

					case self::TEXT :
						$value = self::parse_value(htmlspecialchars($value), $row['pf_output']);
					break;

					case self::TEXTAREA :
						$value = nl2br(self::parse_value(htmlspecialchars($value), $row['pf_output']));
					break;
				}
			}

			// On affiche la valeur
			Fsb::$tpl->set_blocks($field_name, array(
				'LANG' =>		String::parse_lang($row['pf_lang']),
				'VALUE' =>		($row['pf_html_type'] == 'textarea') ? nl2br($value) : $value,
			));
		}
		Fsb::$db->free($result);
	}


	/**
	 * Recupere les donnees des champs de profil, dans les sujets
	 *
	 * @param string $sql_fields_personal Liste des champs a recuperer dans la table fsb2_users_personal
	 */
	public static function topic_info(&$sql_fields_personal)
	{
		$sql = 'SELECT pf_id, pf_lang, pf_groups, pf_html_type, pf_list, pf_output
				FROM ' . SQL_PREFIX . 'profil_fields
				WHERE pf_topic = 1
					AND pf_type = ' . PROFIL_FIELDS_PERSONAL;
		$result = Fsb::$db->query($sql, 'profil_fields_');
		while ($row = Fsb::$db->row($result))
		{
			$sql_fields_personal .= (($sql_fields_personal) ? ', ' : '') . 'up.personal_' . $row['pf_id'];

			// On verifie si le membre peut voir le champ
			$groups = explode(',', $row['pf_groups']);
			$profil_list = unserialize($row['pf_list']);
			if (!$groups || !$groups[0] || ($groups && $groups[0] && array_intersect(Fsb::$session->data['groups'], $groups)))
			{
				self::$fields_data[$row['pf_id']] = array(
					'lg' =>		$row['pf_lang'],
					'type' =>	$row['pf_html_type'],
					'list' =>	$profil_list,
					'out' =>	$row['pf_output'],
				);
			}
		}
		Fsb::$db->free($result);
	}

	/**
	 * Affiche les champs de profil dans le sujet
	 *
	 * @param array $fields_value Valeur des champs
	 */
	public static function topic_show($fields_value)
	{
		foreach (self::$fields_data AS $pf_id => $pf)
		{
			if (isset($fields_value['personal_' . $pf_id]) && trim($fields_value['personal_' . $pf_id]))
			{
				switch ($pf['type'])
				{
					case self::TEXT :
					case self::TEXTAREA :
						$value = self::parse_value(htmlspecialchars($fields_value['personal_' . $pf_id]), $pf['out']);
					break;

					case self::RADIO :
					case self::SELECT :
						$value = self::parse_value(htmlspecialchars($pf['list'][$fields_value['personal_' . $pf_id]]), $pf['out']);
					break;

					case self::MULTIPLE :
						$user_value = explode(',', $fields_value['personal_' . $pf_id]);
						$value = '<br />';
						if ($fields_value['personal_' . $pf_id])
						{
							foreach ($user_value AS $v)
							{
								$value .= self::parse_value($pf['list'][$v], $pf['out']) . '<br />';
							}
						}
					break;
				}
			}
			else
			{
				$value = '--';
			}

			Fsb::$tpl->set_blocks('post.pf', array(
				'LANG' =>	String::parse_lang($pf['lg']),
				'VALUE' =>	$value,
			));
		}
	}
}

/* EOF */
