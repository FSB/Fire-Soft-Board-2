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
 * Methodes permettants de gerer les groupes du forum (ajout, validation, suppression, etc..)
 */
class Group extends Fsb_model
{
	/**
	 * Ajoute un groupe
	 *
	 * @param array $data Tableau d'informations sur le groupe (g_name, g_description, etc..)
	 * @param array $modo_idx Tableau d'ID des moderateurs
	 */
	public static function add($data, $modo_idx)
	{
		Fsb::$db->insert('groups', $data);
		$group_id = Fsb::$db->last_id();
		self::update_moderators($group_id, $data['g_type'], $modo_idx, $data['g_rank']);
		Fsb::$db->destroy_cache('groups_');
	}

	/**
	 * Edite un groupe
	 *
	 * @param int $group_id ID du groupe
	 * @param array $data Tableau d'informations sur le groupe (g_name, g_description, etc..)
	 * @param array $modo_idx Tableau d'ID des moderateurs
	 */
	public static function edit($group_id, $data, $modo_idx)
	{
		Fsb::$db->update('groups', $data, 'WHERE g_id = ' . $group_id);
		self::update_moderators($group_id, $data['g_type'], $modo_idx, $data['g_rank']);
		Fsb::$db->destroy_cache('groups_');

		// Mise a jour de la couleur
		$sql = 'SELECT u_id
				FROM ' . SQL_PREFIX . 'groups_users
				WHERE g_id = ' . $group_id;
		$result = Fsb::$db->query($sql);
		$idx = array();
		while ($row = Fsb::$db->row($result))
		{
			$idx[] = $row['u_id'];
		}
		Fsb::$db->free($result);

		if ($idx)
		{
			self::update_colors($idx);
		}
	}

	/**
	 * Mise a jour des moderateurs et des rangs du groupe
	 *
	 * @param int $group_id ID du groupe
	 * @param int $group_type Type du groupe
	 * @param array $modo_idx Tableau d'ID des moderateurs
	 * @param int $group_rank ID du rang du groupe
	 */
	public static function update_moderators($group_id, $group_type, $modo_idx, $group_rank)
	{
		// Ajout / Suppression de moderateurs dans le grouoe
		if ($group_type != GROUP_SPECIAL && $modo_idx)
		{
			// Ajout des moderateurs dans le groupe
			self::add_users($modo_idx, $group_id, GROUP_MODO);

			// On passe en non moderateurs les autres membres du groupe
			Fsb::$db->update('groups_users', array(
				'gu_status' =>		GROUP_USER,
			), 'WHERE u_id NOT IN (' . implode(', ', $modo_idx) . ') AND gu_status = ' . GROUP_MODO . ' AND g_id = ' . $group_id);
		}
		else if ($group_type != GROUP_SPECIAL)
		{
			// Aucun moderateur de groupe
			Fsb::$db->update('groups_users', array(
				'gu_status' =>		GROUP_USER,
			), 'WHERE gu_status = ' . GROUP_MODO . ' AND g_id = ' . $group_id);
		}

		// Si un rang a ete cree, on assigne le rang aux membres du groupe sans rang
		if ($group_rank)
		{
			$sql = 'UPDATE ' . SQL_PREFIX . 'users
						SET u_rank_id = ' . $group_rank . '
					WHERE u_id IN (
						SELECT u_id
						FROM ' . SQL_PREFIX . 'groups_users
						WHERE g_id = ' . $group_id . '
					) AND u_rank_id = 0';
			Fsb::$db->query($sql);
		}
	}

	/**
	 * Supprime un groupe
	 *
	 * @param int $group_id ID du groupe
	 */
	public static function delete($group_id)
	{
		// Membres du groupe
		$sql = 'SELECT u_id
				FROM ' . SQL_PREFIX . 'groups_users
				WHERE g_id = ' . $group_id;
		$result = Fsb::$db->query($sql);
		$idx = array();
		while ($row = Fsb::$db->row($result))
		{
			$idx[] = $row['u_id'];
		}
		Fsb::$db->free($result);

		// Suppressions des donnees du groupe
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'groups
				WHERE g_id = ' . $group_id;
		Fsb::$db->query($sql);

		$sql = 'DELETE FROM ' . SQL_PREFIX . 'groups_users
				WHERE g_id = ' . $group_id ;
		Fsb::$db->query($sql);

		$sql = 'DELETE FROM ' . SQL_PREFIX . 'groups_auth
				WHERE g_id = ' . $group_id;
		Fsb::$db->query($sql);
		Fsb::$db->destroy_cache('groups_');

		// On regarde si les membres changent de status (moderateur ou non)
		self::update_auths($idx);
	}

	/**
	 * Ajoute un ou plusieurs utilisateurs a un groupe
	 *
	 * @param array $idx ID ou tableau d'ID de utilisateurs
	 * @param int $group_id ID du groupe
	 * @param int $state Status du membre dans le groupe (GROUP_MODO | GROUP_USER | GROUP_WAIT)
	 * @param bool $update Mise a jour ou non des autorisations des membres
	 * @param bool $is_single_groupe true s'il s'agit d'un membre unique, dans ce cas on ne met pas a jour le groupe par defaut
	 * @param bool $update_default true si on souhaite mettre a jour le groupe par defaut
	 */
	public static function add_users($idx, $group_id, $state, $update = true, $is_single_groupe = false, $update_default = true)
	{
		if (!is_array($idx))
		{
			$idx = array($idx);
		}

		if (!$idx)
		{
			return ;
		}

		// Ajout des utilisateurs
		foreach ($idx AS $id)
		{
			if ($id == VISITOR_ID)
			{
				continue;
			}

			Fsb::$db->insert('groups_users', array(
				'g_id' =>		array($group_id, true),
				'u_id' =>		array($id, true),
				'gu_status' =>	$state,
			), 'REPLACE', true);
		}
		Fsb::$db->query_multi_insert();

		// Mise a jour du groupe par defaut de ces utilisateurs, s'ils etaient sans groupe.
		if (!self::is_special_group($group_id) && !$is_single_groupe && $update_default)
		{
			Fsb::$db->update('users', array(
				'u_default_group_id' =>		$group_id,
			), 'WHERE u_id IN (' . implode(', ', $idx) . ') AND u_default_group_id = ' . GROUP_SPECIAL_USER);
		}

		// Si les membres ajoutes sont en attentes, inutile de recalculer leurs autorisations
		if ($update && $state != GROUP_WAIT)
		{
			self::update_auths($idx);
		}
	}

	/**
	 * Supprime une ou plusieurs utilisateurs d'un groupe
	 *
	 * @param array $idx ID ou tableau d'ID de utilisateurs
	 * @param int $group_id ID du groupe
	 * @param bool $update Mise a jour ou non des autorisations des membres
	 * @param bool $delete_modo Suppression des moderateurs du groupe ?
	 */
	public static function delete_users($idx, $group_id, $update = true, $delete_modo = true)
	{
		if (!is_array($idx))
		{
			$idx = array($idx);
		}

		$sql = 'DELETE FROM ' . SQL_PREFIX . 'groups_users
				WHERE g_id = ' . $group_id
					. (($idx) ? ' AND u_id IN (' . implode(', ', $idx) . ')' : '')
					. ((!$delete_modo) ? ' AND gu_status <> ' . GROUP_MODO : '');
		Fsb::$db->query($sql);

		if ($update)
		{
			self::update_auths($idx);
		}
	}

	/**
	 * Met a jour le niveau d'autorisation des membres, leur groupe par defaut et leur couleur
	 *
	 * @param array $idx ID ou tableau d'ID d'utilisateurs. Si $idx est vide on met l'ensemble des utilisateurs du forum a jour.
	 */
	public static function update_auths($idx = array())
	{
		if (!is_array($idx))
		{
			$idx = array($idx);
		}

		// On recupere la liste des groupes, leurs membres et si ce sont des groupes moderateurs
		$sql = 'SELECT g.g_id, gu.u_id, g.g_name, ga.f_id, ga.ga_moderator
				FROM ' . SQL_PREFIX . 'groups g
				INNER JOIN ' . SQL_PREFIX . 'groups_users gu
					ON g.g_id = gu.g_id
				LEFT JOIN ' . SQL_PREFIX . 'groups_auth ga
					ON g.g_id = ga.g_id
				WHERE g.g_id <> ' . GROUP_SPECIAL_USER . '
					AND gu.gu_status <> ' . GROUP_WAIT
					. (($idx) ? ' AND gu.u_id IN (' . implode(', ', $idx) . ')' : '');
		$result = Fsb::$db->query($sql);
		$list = array();
		while ($row = Fsb::$db->row($result))
		{
			if ($row['g_id'] == GROUP_SPECIAL_ADMIN)
			{
				$list[$row['u_id']] = ADMIN;
			}
			else if ($row['g_id'] == GROUP_SPECIAL_MODOSUP && (!isset($list[$row['u_id']]) || $list[$row['u_id']] < MODOSUP))
			{
				$list[$row['u_id']] = MODOSUP;
			}
			else if ($row['ga_moderator'] && (!isset($list[$row['u_id']]) || $list[$row['u_id']] < MODO))
			{
				$list[$row['u_id']] = MODO;
			}
		}
		Fsb::$db->free($result);

		// On regroupe les membres par type de groupes
		$newlist = array(ADMIN => array(), MODOSUP => array(), MODO => array());
		foreach ($list AS $user_id => $auth)
		{
			$newlist[$auth][] = $user_id;
		}
		$list_users = array_keys($list);

		// On recupere la liste des moderateurs et de leurs groupes speciaux
		$sql = 'SELECT u_id, u_auth, u_default_group_id
				FROM ' . SQL_PREFIX . 'users
				WHERE u_auth >= ' . MODO;
		$result = Fsb::$db->query($sql);
		$modo_groups = array();
		while ($row = Fsb::$db->row($result))
		{
			$modo_groups[$row['u_id']] = array(
				'group' =>	$row['u_default_group_id'],
				'auth' =>	$row['u_auth'],
			);
		}
		Fsb::$db->free($result);

		// Tous les membres concernes repassent en status membre (sauf le fondateur)
		Fsb::$db->update('users', array(
			'u_auth' =>		USER,
		), 'WHERE u_id <> ' . VISITOR_ID . ' AND u_auth < ' . FONDATOR . (($idx) ? ' AND u_id IN (' . implode(',', $idx) . ')' : ''));

                // Le visiteur redevient à son tour visiteur, au cas où il
                // aurait reçu des droits de modération avant
                Fsb::$db->update('users', array(
                                         'u_auth' => VISITOR,
                                         ), 'WHERE u_id = ' . VISITOR_ID);

		// Mise a jour des administrateurs, moderateurs globaux et moderateurs
		foreach (array(ADMIN => GROUP_SPECIAL_ADMIN, MODOSUP => GROUP_SPECIAL_MODOSUP, MODO => GROUP_SPECIAL_MODO) AS $auth_level => $auth_group)
		{
			self::delete_users($idx, $auth_group, false);
			if ($newlist[$auth_level])
			{
				Fsb::$db->update('users', array(
					'u_auth' =>				$auth_level,
				), 'WHERE u_id IN (' . implode(', ', $newlist[$auth_level]) . ') AND u_auth < ' . $auth_level);

				// Mise a jour des couleurs des membres
				$list = array();
				foreach ($modo_groups AS $def_id => $def_data)
				{
					if ($def_data['auth'] == $auth_level && $def_data['group'] != $auth_group)
					{
						$list[] = $def_id;
					}
				}
				$modo_groups_sql = ($list) ? ' AND u_id NOT IN (' . implode(', ', $list) . ') ' : '';

				Fsb::$db->update('users', array(
					'u_default_group_id' =>	$auth_group,
				), 'WHERE u_id IN (' . implode(', ', $newlist[$auth_level]) . ') ' . $modo_groups_sql . ' AND u_auth = ' . $auth_level);

				self::add_users($newlist[$auth_level], $auth_group, GROUP_USER, false);
			}
		}

		self::update_default($idx);
		self::update_colors($idx);

		Sync::signal(Sync::SESSION);
		Fsb::$db->destroy_cache('groups_auth_');
	}

	/**
	 * Enter description here...
	 *
	 * @param array $idx ID ou tableau d'ID d'utilisateurs. Si $idx est vide on met l'ensemble des utilisateurs du forum a jour.
	 */
	public static function update_default($idx = array())
	{
		if (!is_array($idx))
		{
			$idx = array($idx);
		}

		$sql = 'UPDATE ' . SQL_PREFIX . 'users
					SET u_default_group_id = ' . GROUP_SPECIAL_USER . '
				WHERE u_default_group_id NOT IN (
					SELECT g_id
					FROM ' . SQL_PREFIX . 'groups_users gu
					WHERE ' . SQL_PREFIX . 'users.u_id = gu.u_id
				) ' . (($idx) ? ' AND u_id IN (' . implode(', ', $idx) . ')' : '');
		Fsb::$db->query($sql);
	}

	/**
	 * Met a jour la couleur des utilisateurs en fonction de leur groupe par defaut
	 *
	 * @param array $idx ID ou tableau d'ID d'utilisateurs. Si $idx est vide on met l'ensemble des utilisateurs du forum a jour.
	 */
	public static function update_colors($idx = array())
	{
		if (!is_array($idx))
		{
			$idx = array($idx);
		}

		switch (SQL_DBAL)
		{
			case 'pgsql' :
				$sql = 'UPDATE ' . SQL_PREFIX . 'users
							SET u_color = g_color
						FROM ' . SQL_PREFIX . 'groups
						WHERE u_default_group_id = g_id
							AND u_id <> ' . VISITOR_ID
							. (($idx) ? ' AND u_id IN (' . implode(', ', $idx) . ')' : '');
			break;

			default :
				$sql = 'UPDATE ' . SQL_PREFIX . 'users u, ' . SQL_PREFIX . 'groups g
							SET u.u_color = g.g_color
						WHERE u.u_default_group_id = g.g_id
							AND u.u_id <> ' . VISITOR_ID
							. (($idx) ? ' AND u.u_id IN (' . implode(', ', $idx) . ')' : '');
			break;
		}
		Fsb::$db->query($sql);

		// Mise a jour de la couleur du dernier membre inscrit
		if (!$idx || in_array(Fsb::$cfg->get('last_user_id'), $idx))
		{
			$sql = 'UPDATE ' . SQL_PREFIX . 'config
					SET cfg_value = (
						SELECT u_color
						FROM ' . SQL_PREFIX . 'users
						WHERE u_id = ' . Fsb::$cfg->get('last_user_id') . '
					) WHERE cfg_name = \'last_user_color\'';
			Fsb::$db->query($sql);
			Fsb::$cfg->destroy_cache();
		}
	}

	/**
	 * Verifie si un groupe est special
	 *
	 * @param int $group_id ID du groupe
	 * @return bool
	 */
	public static function is_special_group($group_id)
	{
		return (in_array($group_id, array(GROUP_SPECIAL_USER, GROUP_SPECIAL_MODO, GROUP_SPECIAL_MODOSUP, GROUP_SPECIAL_ADMIN)) ? true : false);
	}
}
/* EOF */