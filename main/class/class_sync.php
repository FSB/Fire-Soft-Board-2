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
 * Permet de syncroniser des informations mises en cache sur le forum
 */
class Sync extends Fsb_model
{
	/**
	 * Signal pour rafraichir toutes les sessions
	 */
	const SESSION = 2;
	
	/**
	 * Signal pour rafraichir la session d'un membre
	 */
	const USER = 4;
	
	/**
	 * Signal pour rafraichir le total de messages abusifs
	 */
	const ABUSE = 8;
	
	/**
	 * Signal pour rafraichir le total de messages a approuver
	 */
	const APPROVE = 16;

	/**
	 * Met a jour les informations sur le dernier message des forums
	 *
	 * @param array $forums Liste des forums. Si vide, on prend tous les forums.
	 */
	public static function forums($forums = array())
	{
		$tree = new Tree_forum();
		$tree->update_stats($forums);

		Fsb::$db->destroy_cache('forums_');
	}

	/**
	 * Met a jour les informations sur le dernier message dans les sujets, et sur le total de messages du sujet
	 *
	 * @param array $topics Liste des topics. Si vide, on prend tous les topics.
	 */
	public static function topics($topics = array())
	{
		// On met a jour le dernier posteur du sujet, et on decremente le nombre total de message du sujet si besoin
		$sql = 'SELECT t.t_id, p.p_id, p.p_time, p.u_id, p.p_nickname
				FROM ' . SQL_PREFIX . 'topics t
				LEFT JOIN ' . SQL_PREFIX . 'posts p
					ON t.t_id = p.t_id
				WHERE ' . (($topics) ? 't.t_id IN (' . implode(', ', array_keys($topics)) . ') AND ' : '') . '
					p.p_time = (
						SELECT MAX(p2.p_time)
						FROM ' . SQL_PREFIX . 'posts p2
						WHERE p2.t_id = t.t_id)';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$db->update('topics', array(
				't_last_p_id' =>		$row['p_id'],
				't_last_p_time' =>		$row['p_time'],
				't_last_p_nickname' =>	$row['p_nickname'],
				't_last_u_id' =>		$row['u_id'],
				't_total_post' =>		array('t_total_post' . ((isset($topics[$row['t_id']])) ? ' - ' . $topics[$row['t_id']] : ''), 'is_field' => true),
			), 'WHERE t_id = ' . $row['t_id']);
		}
		Fsb::$db->free($result);
	}

	/**
	 * Met a jour les informations sur les derniers messages lus
	 *
	 * @param array $topics Liste des topics. Si vide, on prend tous les topics.
	 */
	public static function topics_read($topics = array())
	{
		$sql = 'UPDATE ' . SQL_PREFIX . 'topics_read tr
				SET tr.p_id = (
					SELECT MAX(p.p_id)
					FROM ' . SQL_PREFIX . 'posts p
					WHERE p.t_id = tr.t_id
						AND p.p_id <= tr.p_id
				) ' . (($topics) ? 'WHERE tr.t_id IN (' . implode(', ', $topics) . ')' : '');
		Fsb::$db->query($sql);
	}

	/**
	 * Met a jour le total de messages sur le forum
	 */
	public static function total_posts()
	{
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'posts';
		$total = Fsb::$db->get($sql, 'total');

		Fsb::$cfg->update('total_posts', $total);
	}

	/**
	 * Met a jour le total de sujets sur le forum
	 */
	public static function total_topics()
	{
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'topics';
		$total = Fsb::$db->get($sql, 'total');

		Fsb::$cfg->update('total_topics', $total);
	}

	/**
	 * Met a jour un champ signal pour mettre a jour le cache
	 *
	 * @param int $signal_id Il est possible d'envoyers plusieurs signaux en utilisant le "OR" binaire.
	 * @param mixed $arg
	 */
	function signal($signal_id, $arg = null)
	{
		// Mise a jour de la session
		if ($signal_id & self::SESSION)
		{
			Fsb::$cfg->update('signal_session', CURRENT_TIME);
		}

		// Mise a jour de la session d'un utilisateur particulier
		if ($signal_id & self::USER)
		{
			Fsb::$db->update('sessions', array(
				's_signal_user' => CURRENT_TIME,
			), 'WHERE s_id = ' . (($arg) ? $arg : Fsb::$session->id()));
		}

		// Mise a jour des messages abusifs pour les moderateurs
		$update_modo = array();
		if ($signal_id & self::ABUSE)
		{
			$update_modo['u_total_abuse'] = -1;
		}

		// Mise a jour des messages approuves pour les moderateurs
		if ($signal_id & self::APPROVE)
		{
			$update_modo['u_total_unapproved'] = -1;
		}

		if ($update_modo)
		{
			Fsb::$db->update('users', $update_modo, 'WHERE u_auth >= ' . MODO);
		}
	}
}

/* EOF */