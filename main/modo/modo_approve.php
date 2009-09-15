<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On affiche ce module
$show_this_module = true;

/**
 *  Module d'utilisateur permettant au moderateur de voir les messages non approuves des forums qu'il modere.
 *
 */
class Page_modo_approve extends Fsb_model
{
	/**
	 * Mode
	 *
	 * @var string
	 */
	public $mode;
	
	/**
	 * Id du forum
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		$this->mode = Http::request('mode');
		$this->id = intval(Http::request('id'));

		if ($this->mode == 'approve')
		{
			$this->approve_post();
		}
		else if ($this->mode == 'topic')
		{
			$this->show_unapproved_posts();
		}
		else
		{
			$this->show_unapproved_topics();
		}
	}

	/**
	 * Liste les messages non approuves
	 *
	 */
	public function show_unapproved_topics()
	{
		Fsb::$tpl->set_file('modo/modo_approve.html');
		Fsb::$tpl->set_switch('list_unapproved');

		// Liste des sujets contenant des messages non valides
		$sql = 'SELECT t.t_id, t.t_title, t.t_approve, t.t_time, f.f_id, f.f_name, f.f_color, u.u_id, u.u_nickname, u.u_color, COUNT(p.p_id) AS total_unapproved
				FROM ' . SQL_PREFIX . 'topics t
				INNER JOIN ' . SQL_PREFIX . 'posts p
					ON t.t_id = p.t_id
						AND p.p_approve = ' . IS_NOT_APPROVED . '
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = t.u_id
				LEFT JOIN ' . SQL_PREFIX . 'forums f
					ON f.f_id = p.f_id
				WHERE t.f_id IN (' . Fsb::$session->moderated_forums() . ')
				GROUP BY t.t_id
				ORDER BY t.t_last_p_time DESC';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('topic', array(
				'TITLE' =>		Parser::title($row['t_title']),
				'TOTAL' =>		$row['total_unapproved'],
				'OWNER' =>		Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
				'FORUM' =>		Html::forumname($row['f_name'], $row['f_id'], $row['f_color']),
				'DATE' =>		Fsb::$session->print_date($row['t_time']),
				'IS_NEW' =>		($row['t_approve'] == IS_NOT_APPROVED) ? true : false,

				'U_TOPIC' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=approve&amp;mode=topic&amp;id=' . $row['t_id']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Affiche les messages non approuves d'un sujet
	 *
	 */
	public function show_unapproved_posts()
	{
		Fsb::$tpl->set_file('modo/modo_approve.html');
		Fsb::$tpl->set_switch('list_unapproved_posts');

		$parser = new Parser();

		$sql = 'SELECT t.t_id, t.f_id, t.t_title, t.t_approve, p.p_id, p.p_text, p.p_map, p.p_nickname, p.p_time, p.u_ip, u.u_id, u.u_color, u.u_avatar, u.u_avatar_method, u.u_can_use_avatar, u.u_auth
				FROM ' . SQL_PREFIX . 'topics t
				LEFT JOIN ' . SQL_PREFIX . 'posts p
					ON t.t_id = p.t_id
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = p.u_id
				WHERE p.t_id = ' . $this->id . '
					AND p.p_approve = ' . IS_NOT_APPROVED . '
					AND p.f_id IN (' . Fsb::$session->moderated_forums() . ')';
		$result = Fsb::$db->query($sql);
		if ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_vars(array(
				'TOPIC_TITLE' =>	Parser::title($row['t_title']),
				'IS_NEW' =>			($row['t_approve'] == IS_NOT_APPROVED) ? true : false,

				'U_TOPIC' =>		sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $row['t_id']),
			));

			do
			{
				// Informations passees au parseur de message
				$parser_info = array(
					'u_id' =>			$row['u_id'],
					'p_nickname' =>		$row['p_nickname'],
					'u_auth' =>			$row['u_auth'],
					'f_id' =>			$row['f_id'],
					't_id' =>			$row['t_id'],
				);

				$avatar = User::get_avatar($row['u_avatar'], $row['u_avatar_method'], $row['u_can_use_avatar']);
				Fsb::$tpl->set_blocks('post', array(
					'NICKNAME' =>		Html::nickname($row['p_nickname'], $row['u_id'], $row['u_color']),
					'DATE' =>			Fsb::$session->print_date($row['p_time']),
					'CONTENT' =>		$parser->mapped_message($row['p_text'], $row['p_map'], $parser_info),
					'AVATAR' =>			$avatar,
					'IP' =>				(Fsb::$session->is_authorized('auth_ip')) ? $row['u_ip'] : null,

					'U_IP' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=ip&amp;ip=' . $row['u_ip']),
					'U_DELETE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=delete&amp;id=' . $row['p_id']),
					'U_EDIT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=edit&amp;id=' . $row['p_id']),
					'U_APPROVE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=approve&amp;mode=approve&amp;id=' . $row['p_id']),
				));
			}
			while ($row = Fsb::$db->row($result));
		}
		Fsb::$db->free($result);
				
	}

	/**
	 * Approuve le message
	 *
	 */
	public function approve_post()
	{
		// On verifie que le message soit dans un des forums qu'on modere
		$sql = 'SELECT p_id
				FROM ' . SQL_PREFIX . 'posts
				WHERE p_id = ' . $this->id . '
					AND f_id IN (' . Fsb::$session->moderated_forums() . ')
					AND p_approve = ' . IS_NOT_APPROVED;
		if (!Fsb::$db->get($sql, 'p_id'))
		{
			Display::message('not_allowed');
		}
		Moderation::approve_post($this->id);

		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=approve');
	}
}

/* EOF */