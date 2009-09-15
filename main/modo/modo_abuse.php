<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On affiche ce module si la fonction est activee
if (Fsb::$mods->is_active('abuse'))
{
	$show_this_module = true;
}

/**
 * Module d'utilisateur permettant au moderateur de voir les messages etant signales comme abusif, et de moderer (editer / supprimer) ce message
 *
 */
class Page_modo_abuse extends Fsb_model
{
	/**
	 * ID du message abusif a valider
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Mode de la page
	 *
	 * @var string
	 */
	public $mode;

	/**
	 * Sujets par page
	 *
	 * @var int
	 */
	public $per_page = 30;
	
	/**
	 * Page
	 *
	 * @var int
	 */
	public $page = 1;

	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		// Doit etre moderateur
		if (Fsb::$session->auth() < MODO || !Fsb::$mods->is_active('abuse'))
		{
			Display::message('not_allowed');
		}

		$this->id =		intval(Http::request('id'));
		$this->mode =	Http::request('mode');
		$this->page =	intval(Http::request('page'));
		if (!$this->page || $this->page <= 0)
		{
			$this->page = 1;
		}

		$call = new Call($this);
		$call->post(array(
			'submit_comment' =>		':submit_comment',
		));

		$call->functions(array(
			'mode' => array(
				'show' =>			'show_abuse',
				'validate' =>		'validate_abuse',
				'default' =>		'show_list_abuse',
			),
		));
	}

	/**
	 * Affiche la liste des messages abusifs
	 *
	 */
	public function show_list_abuse()
	{
		Fsb::$tpl->set_file('modo/modo_abuse.html');
		Fsb::$tpl->set_switch('list_abuse');

		// Pagination
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'posts_abuse
				WHERE pa_parent = 0';
		$total = Fsb::$db->get($sql, 'total');

		Fsb::$tpl->set_vars(array(
			'PAGINATION' =>		Html::pagination($this->page, $total / $this->per_page, sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=abuse')),
		));

		if ($total / $this->per_page > 1)
		{
			Fsb::$tpl->set_switch('pagination');
		}

		// Liste des abus
		$sql = 'SELECT pa.pa_id, pa.u_id, pa.pa_time, pa.pa_status, pa.pa_mp_id, COUNT(pa2.pa_id) + 1 AS total, t.t_title, f.f_id, f.f_name, f.f_color, u.u_nickname, u.u_color, mp.mp_title
				FROM ' . SQL_PREFIX . 'posts_abuse pa
				LEFT JOIN ' . SQL_PREFIX . 'posts_abuse pa2
					ON pa.pa_id = pa2.pa_parent
				LEFT JOIN ' . SQL_PREFIX . 'topics t
					ON pa.t_id = t.t_id
				LEFT JOIN ' . SQL_PREFIX . 'forums f
					ON t.f_id = f.f_id
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON pa.u_id = u.u_id
				LEFT JOIN ' . SQL_PREFIX . 'mp mp
					ON pa.pa_mp_id = mp.mp_id
				WHERE pa.pa_parent = 0
					AND (t.f_id IN (' . Fsb::$session->moderated_forums() . ') OR pa.pa_mp_id <> 0)
				GROUP BY pa.pa_id, pa.u_id, pa.pa_time, pa.pa_status, pa.pa_mp_id, t.t_title, f.f_id, f.f_name, f.f_color, u.u_nickname, u.u_color, mp.mp_title
				ORDER BY pa.pa_time DESC
				LIMIT ' . (($this->page - 1) * $this->per_page) . ', ' . $this->per_page;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$title = ($row['pa_mp_id']) ? Parser::title($row['mp_title']) : Parser::title($row['t_title']);

			Fsb::$tpl->set_blocks('abuse', array(
				'TOPIC_TITLE' =>	$title,
				'NICKNAME' =>		Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
				'DATE' =>			Fsb::$session->print_date($row['pa_time']),
				'IS_CLOSED' =>		($row['pa_status'] == IS_APPROVED) ? true : false,
				'FORUM' =>			Html::forumname($row['f_name'], $row['f_id'], $row['f_color']),
				'TOTAL' =>			$row['total'],
				'IS_MP' =>			($row['pa_mp_id']) ? true : false,

				'U_ABUSE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=abuse&amp;mode=show&amp;id=' . $row['pa_id']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Affiche les messages abusifs
	 *
	 */
	public function show_abuse()
	{
		Fsb::$tpl->set_file('modo/modo_abuse.html');
		Fsb::$tpl->set_switch('show_abuse');

		$parser = new Parser();

		// Informations sur le message abusif parent, pour simplifier la requete suivant si on est dans un message abusif
		// de message prive, ou pas.
		$sql = 'SELECT pa_mp_id
				FROM ' . SQL_PREFIX . 'posts_abuse
				WHERE pa_id = ' . $this->id;
		$mp_id = Fsb::$db->get($sql, 'pa_mp_id');

		// On recupere et on affiche les messages abusifs
		if (!$mp_id)
		{
			$sql = 'SELECT pa.pa_id, pa.pa_text, pa.pa_time, pa.pa_status, t.t_id, t.f_id, t.t_title AS title, t.t_status, f.f_name, p.p_id, p.p_text AS content, p.p_map AS map, p.p_time AS time, p.u_ip, u.u_auth, u.u_id AS poster_id, u.u_nickname AS poster_nickname, u.u_color AS poster_color, u.u_avatar AS poster_avatar, u.u_avatar_method AS poster_avatar_method, u.u_can_use_avatar AS poster_can_use_avatar, u2.u_id AS poster_comment_id, u2.u_nickname AS poster_comment_nickname, u2.u_color AS poster_comment_color, u2.u_avatar AS poster_comment_avatar, u2.u_avatar_method AS poster_comment_avatar_method, u2.u_can_use_avatar AS poster_comment_can_use_avatar
					FROM ' . SQL_PREFIX . 'posts_abuse pa
					INNER JOIN ' . SQL_PREFIX . 'topics t
						ON t.t_id = pa.t_id
					INNER JOIN ' . SQL_PREFIX . 'forums f
						ON f.f_id = t.f_id
					INNER JOIN ' . SQL_PREFIX . 'posts p
						ON p.p_id = pa.p_id
					INNER JOIN ' . SQL_PREFIX . 'users u
						ON p.u_id = u.u_id
					INNER JOIN ' . SQL_PREFIX . 'users u2
						ON pa.u_id = u2.u_id
					WHERE pa.pa_id = ' . $this->id . '
						OR (pa.pa_parent <> 0 AND pa.pa_parent = (
							SELECT pa_id 
							FROM ' . SQL_PREFIX . 'posts_abuse
							WHERE pa_id = ' . $this->id . ')
						)
					AND t.f_id IN (' . Fsb::$session->moderated_forums() . ')
					ORDER BY pa.pa_time ASC';
		}
		else
		{
			$sql = 'SELECT pa.pa_id, pa.pa_text, pa.pa_time, pa.pa_status, mp.mp_title AS title, mp.mp_content AS content, \'classic\' AS map, mp.mp_time AS time, mp.u_ip, u.u_auth, u.u_id AS poster_id, u.u_nickname AS poster_nickname, u.u_color AS poster_color, u.u_avatar AS poster_avatar, u.u_avatar_method AS poster_avatar_method, u.u_can_use_avatar AS poster_can_use_avatar, u2.u_id AS poster_comment_id, u2.u_nickname AS poster_comment_nickname, u2.u_color AS poster_comment_color, u2.u_avatar AS poster_comment_avatar, u2.u_avatar_method AS poster_comment_avatar_method, u2.u_can_use_avatar AS poster_comment_can_use_avatar
					FROM ' . SQL_PREFIX . 'posts_abuse pa
					INNER JOIN ' . SQL_PREFIX . 'mp mp
						ON mp.mp_id = pa.pa_mp_id
					INNER JOIN ' . SQL_PREFIX . 'users u
						ON mp.mp_from = u.u_id
					INNER JOIN ' . SQL_PREFIX . 'users u2
						ON pa.u_id = u2.u_id
					WHERE pa.pa_id = ' . $this->id . '
						OR (pa.pa_parent <> 0 AND pa.pa_parent = (
							SELECT pa_id 
							FROM ' . SQL_PREFIX . 'posts_abuse
							WHERE pa_id = ' . $this->id . ')
						)
					ORDER BY pa.pa_time ASC';
		}

		$result = Fsb::$db->query($sql);
		if ($row = Fsb::$db->row($result))
		{
			$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;

			// Informations passees au parseur de message
			$parser_info = array(
				'u_id' =>			$row['poster_id'],
				'p_nickname' =>		$row['poster_nickname'],
				'u_auth' =>			$row['u_auth'],
				'f_id' =>			$row['f_id'],
				't_id' =>			$row['t_id'],
			);

			$avatar = User::get_avatar($row['poster_avatar'], $row['poster_avatar_method'], $row['poster_can_use_avatar']);
			Fsb::$tpl->set_vars(array(
				'MESSAGE_NICKNAME' =>	Html::nickname($row['poster_nickname'], $row['poster_id'], $row['poster_color']),
				'MESSAGE_CONTENT' =>	$parser->mapped_message($row['content'], $row['map'], $parser_info),
				'MESSAGE_FORUM' =>		(!$mp_id) ? htmlspecialchars($row['f_name']) : '',
				'MESSAGE_TOPIC' =>		Parser::title($row['title']),
				'MESSAGE_DATE' =>		Fsb::$session->print_date($row['time']),
				'MESSAGE_IP' =>			(Fsb::$session->is_authorized('auth_ip')) ? $row['u_ip'] : null,
				'MESSAGE_AVATAR' =>		$avatar,
				'IS_VALIDATE' =>		($row['pa_status'] == IS_APPROVED) ? true : false,
				'IS_LOCKED' =>			(!$mp_id && $row['t_status'] == LOCK) ? true : false,
				'IS_MP' =>				($mp_id) ? true : false,

				'U_FORUM' =>			(!$mp_id) ? sid(ROOT . 'index.' . PHPEXT . '?p=forum&amp;f_id=' . $row['f_id']) : '',
				'U_TOPIC' =>			(!$mp_id) ? sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $row['t_id']) : '',
				'U_DELETE_TOPIC' =>		(!$mp_id) ? sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=delete_topic&amp;id=' . $row['t_id']) : '',
				'U_SPLIT_TOPIC' =>		(!$mp_id) ? sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=split&amp;id=' . $row['t_id']) : '',
				'U_MERGE_TOPIC' =>		(!$mp_id) ? sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=merge&amp;id=' . $row['t_id']) : '',
				'U_MOVE_TOPIC' =>		(!$mp_id) ? sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=move&amp;id=' . $row['t_id']) : '',
				'U_LOCK_TOPIC' =>		(!$mp_id) ? sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=lock&amp;mode=lock&amp;id=' . $row['t_id']) : '',
				'U_UNLOCK_TOPIC' =>		(!$mp_id) ? sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=lock&amp;mode=unlock&amp;id=' . $row['t_id']) : '',
				'U_EDIT' =>				(!$mp_id) ? sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=edit&amp;id=' . $row['p_id']) : '',
				'U_DELETE' =>			(!$mp_id) ? sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=delete&amp;id=' . $row['p_id']) : '',
				'U_ACTION' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=abuse&amp;mode=show&amp;id=' . $this->id),
				'U_IP' =>				sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=ip&amp;ip=' . $row['u_ip']),
				'U_VALIDATE' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=abuse&amp;mode=validate&amp;id=' . $this->id),
			));

			do
			{
				$parser->parse_html = false;

				// Informations passees au parseur de message
				$parser_info = array(
					'u_id' =>			$row['poster_comment_id'],
					'p_nickname' =>		$row['poster_comment_nickname'],
					'u_auth' =>			$row['u_auth'],
					'f_id' =>			$row['f_id'],
					't_id' =>			$row['t_id'],
				);

				$avatar = User::get_avatar($row['poster_comment_avatar'], $row['poster_comment_avatar_method'], $row['poster_comment_can_use_avatar']);
				Fsb::$tpl->set_blocks('post', array(
					'NICKNAME' =>		Html::nickname($row['poster_comment_nickname'], $row['poster_comment_id'], $row['poster_comment_color']),
					'CONTENT' =>		$parser->message($row['pa_text'], $parser_info),
					'DATE' =>			Fsb::$session->print_date($row['pa_time']),

					'U_AVATAR' =>		$avatar,
				));
			}
			while ($row = Fsb::$db->row($result));
		}
		else
		{
			Display::message('no_result');
		}
		Fsb::$db->free($result);
	}

	/**
	 * Ajoute un commentaire sur l'abus
	 *
	 */
	public function submit_comment()
	{
		$comment = trim(Http::request('comment', 'post'));

		// Information sur l'abus
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'posts_abuse
				WHERE pa_id = ' . $this->id;
		$data = Fsb::$db->request($sql);
		if (!$data)
		{
			Display::message('no_result');
		}

		Fsb::$db->insert('posts_abuse', array(
			'u_id' =>		Fsb::$session->id(),
			'p_id' =>		$data['p_id'],
			't_id' =>		$data['t_id'],
			'pa_parent' =>	$data['pa_id'],
			'pa_status' =>	$data['pa_status'],
			'pa_text' =>	$comment,
			'pa_time' =>	CURRENT_TIME,
			'pa_mp_id' =>	$data['pa_mp_id'],
		));

		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=abuse&mode=show&id=' . $this->id);
	}

	/**
	 * Considere le message abusif comme valide
	 *
	 */
	public function validate_abuse()
	{
		// Verification de l'existance du message abusif
		$sql = 'SELECT pa_id
				FROM ' . SQL_PREFIX . 'posts_abuse
				WHERE pa_id = ' . $this->id;
		$this->id = Fsb::$db->get($sql, 'pa_id');
		if ($this->id)
		{
			Fsb::$db->update('posts_abuse', array(
				'pa_status' =>	IS_APPROVED,
			), 'WHERE pa_id = ' . $this->id . ' OR pa_parent = ' . $this->id);

			Sync::signal(Sync::ABUSE);
			Log::add(Log::MODO, 'log_abuse');
		}

		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=abuse');
	}
}

/* EOF */