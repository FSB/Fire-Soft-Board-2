<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_sync.php
** | Begin :	21/09/2007
** | Last :		04/03/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/



/*
** Permet de syncroniser des informations mises en cache sur le forum
*/
class Sync extends Fsb_model
{
	// Signaux
	const SESSION = 2;
	const USER = 4;
	const ABUSE = 8;
	const APPROVE = 16;

	/*
	** Met a jour les informations sur le dernier message des forums
	** -----
	** $forums ::	Tableau d'ID de forums dont on veut recalculer ces informations
	**				Si le tableau est vide, on le calcul pour l'ensemble des forums.
	*/
	public static function forums($forums = array())
	{
		$tree = new Tree_forum();

		// On met a jour les forums en recalculant le nombre de messages / sujets ainsi
		// que l'ID de leur dernier message. La tache est plutot ardue a cause du systeme de sous
		// forums, ainsi le forum parent pourra avoir comme derniere ID celle d'un de ses
		// fils (quelque soit le niveau de profondeur du sous fils).
		$sql = 'SELECT f2.f_id, t.t_id, t.t_title, t.t_last_p_nickname, t.t_last_p_id, t.t_last_p_time, t.t_last_u_id
				FROM ' . SQL_PREFIX . 'forums f
				LEFT JOIN ' . SQL_PREFIX . 'forums f2
					ON (f2.f_left >= f.f_left AND f2.f_right <= f.f_right)
						OR (f2.f_left <= f.f_left AND f2.f_right >= f.f_right)
				LEFT JOIN ' . SQL_PREFIX . 'topics t
					ON f2.f_id = t.f_id
						AND t.t_id = (
							SELECT t2.t_id
							FROM ' . SQL_PREFIX . 'topics t2
							WHERE t2.f_id = f2.f_id
							ORDER BY t2.t_last_p_time DESC
							LIMIT 1
						)'
				/*. (($forums) ? ' WHERE f.f_id IN (' . implode(', ', $forums) . ')' : '')*/ .
				' GROUP BY f2.f_id, t.t_id, t.t_title, t.t_last_p_nickname, t.t_last_p_id, t.t_last_p_time, t.t_last_u_id';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$tree->fill($row['f_id'], array(
				'f_last_p_id' =>		$row['t_last_p_id'],
				'f_last_t_id' =>		$row['t_id'],
				'f_last_t_title' =>		$row['t_title'],
				'f_last_p_nickname' =>	$row['t_last_p_nickname'],
				'f_last_u_id' =>		$row['t_last_u_id'],
			));
		}
		Fsb::$db->free($result);

		// Mise a jour des informations
		$tree->parse();

		Fsb::$db->destroy_cache('forums_');
	}

	/*
	** Met a jour les informations sur le dernier message dans les sujets, et sur le total de messages du sujet
	** -----
	** $topics ::		Tableau prenant en clef les ID de sujets, et en valeurs le nombres 
	**					de messages a decrementer du total.
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
				't_total_post' =>		array('t_total_post' . ((isset($topics[$row['t_id']])) ? ' - ' . $topics[$row['t_id']] : ''), 'is_field' => TRUE),
			), 'WHERE t_id = ' . $row['t_id']);
		}
		Fsb::$db->free($result);
	}

	/*
	** Met a jour les informations sur les derniers messages lus
	** -----
	** $topics ::		Liste des sujets qu'on veut mettre a jour.
	**					Si le tableau est vide, on met a jour tous les sujets.
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

	/*
	** Met a jour le total de messages sur le forum
	*/
	public static function total_posts()
	{
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'posts';
		$total = Fsb::$db->get($sql, 'total');

		Fsb::$cfg->update('total_posts', $total);
	}

	/*
	** Met a jour le total de sujets sur le forum
	*/
	public static function total_topics()
	{
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'topics';
		$total = Fsb::$db->get($sql, 'total');

		Fsb::$cfg->update('total_topics', $total);
	}

	/*
	** Met a jour un champ signal pour mettre a jour le cache
	** -----
	** $signal_id ::		Contante du signal. Il est possible d'envoyers plusieurs signaux en utilisant le "OR" binaire.
	** $arg ::				Un argument potentiel a la fonction (ID de membre, etc ...)
	*/
	function signal($signal_id, $arg = NULL)
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