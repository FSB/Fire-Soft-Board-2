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
 * Affiche le formulaire permettant de signaler aux moderateurs un message abusif
 *
 */
class Fsb_frame_child extends Fsb_frame
{
	/**
	 * Affichage de la barre de navigation du header
	 *
	 * @var bool
	 */
	public $_show_page_header_nav = true;
	
	/**
	 * Affichage de la barre de navigation du footer
	 *
	 * @var bool
	 */
	public $_show_page_footer_nav = false;
	
	/**
	 * Affichage de la boite des stats
	 *
	 * @var bool
	 */
	public $_show_page_stats = false;

	/**
	 * ID du message
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Mode (MP ou non)
	 *
	 * @var string
	 */
	public $mode;

	/**
	 * Navigation
	 *
	 * @var array
	 */
	public $nav = array();

	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		$this->id =		intval(Http::request('id'));
		$this->mode =	Http::request('mode');

		$this->check_post();

		$call = new Call($this);
		$call->post(array(
			'submit' =>	':submit_form',
		));

		$this->show_form();
	}

	/**
	 * Verifie si le message existe
	 *
	 */
	public function check_post()
	{
		if (!Fsb::$mods->is_active('abuse') || !Fsb::$session->is_logged())
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		if ($this->mode == 'mp')
		{
			$sql = 'SELECT mp_id
					FROM ' . SQL_PREFIX . 'mp
					WHERE mp_id = ' . $this->id . '
						AND ((mp_to = ' . Fsb::$session->id() . ' AND mp_type IN (' . MP_INBOX . ', ' . MP_SAVE_INBOX . '))
						OR (mp_from = ' . Fsb::$session->id() . ' AND mp_type IN (' . MP_OUTBOX . ', ' . MP_SAVE_OUTBOX . ')))';
			if (!Fsb::$db->request($sql))
			{
				Display::message('post_not_exists');
			}
		}
		else
		{
			$sql = 'SELECT p_id, f_id
					FROM ' . SQL_PREFIX . 'posts
					WHERE p_id = ' . intval($this->id);
			if (!$row = Fsb::$db->request($sql))
			{
				Display::message('post_not_exists');
			}
			$this->nav = Forum::nav($row['f_id'], array(), $this);
		}
	}

	/**
	 * Affiche le formulaire pour signaler le message abusif
	 *
	 */
	public function show_form()
	{
		Fsb::$tpl->set_file('forum/forum_abuse.html');
		Fsb::$tpl->set_vars(array(
			'U_ACTION' =>	sid(ROOT . 'index.' . PHPEXT . '?p=abuse&amp;mode=' . $this->mode . '&amp;id=' . $this->id),
		));
	}

	/**
	 * Enregistre dans la base de donnee le signalement de message abusif
	 *
	 */
	public function submit_form()
	{
		$content = Http::request('content_abuse', 'post');
		if ($this->mode == 'mp')
		{
			Fsb::$db->insert('posts_abuse', array(
				'u_id' =>		Fsb::$session->id(),
				'pa_time' =>	CURRENT_TIME,
				'pa_text' =>	$content,
				'pa_status' =>	IS_NOT_APPROVED,
				'pa_mp_id' =>	$this->id,
			));
		}
		else
		{
			$sql = 'SELECT t_id
						FROM ' . SQL_PREFIX . 'posts
						WHERE p_id = ' . $this->id;
			$post_data = Fsb::$db->request($sql);

			Fsb::$db->insert('posts_abuse', array(
				'p_id' =>		$this->id,
				'u_id' =>		Fsb::$session->id(),
				't_id' =>		$post_data['t_id'],
				'pa_time' =>	CURRENT_TIME,
				'pa_text' =>	$content,
				'pa_status' =>	IS_NOT_APPROVED,
			));
		}

		Sync::signal(Sync::ABUSE);

		$url = ($this->mode == 'mp') ? 'mp&amp;id=' . $this->id : 'topic&amp;p_id=' . $this->id . '#' . $this->id;
		Display::message('abuse_submit', ROOT . 'index.' . PHPEXT . '?p=' . $url, 'forum_abuse');
	}
}

/* EOF */
