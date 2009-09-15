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
 * Genere des flux RSS pour les messages ou pour les forums
 *
 */
class Fsb_frame_child extends Fsb_frame
{
	/**
	 * Affichage de la barre de navigation du header
	 *
	 * @var bool
	 */
	public $_show_page_header_nav = false;
	
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
	 * Mode
	 *
	 * @var string
	 */
	public $mode;
	
	/**
	 * ID
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Type
	 *
	 * @var int
	 */
	public $type;

	/**
	 * Objet RSS
	 *
	 * @var Rss
	 */
	public $rss;

	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		// Acces au flux RSS ?
		if (!Fsb::$mods->is_active('rss'))
		{
			Display::message('not_allowed');
		}

		$this->mode =	Http::request('mode');
		$this->type =	Http::request('type');
		$this->id =		intval(Http::request('id'));

		if (!in_array($this->type, array('rss2', 'atom')))
		{
			$this->type = 'rss2';
		}

		// Instance d'un objet RSS
		$this->rss = Rss::factory($this->type);

		switch ($this->mode)
		{
			case 'forum' :
				$this->rss_forum();
			break;

			case 'topic' :
				$this->rss_topic();
			break;

			case 'user' :
				$this->rss_user();
			break;

			case 'index' :
			default :
				$this->rss_index();
			break;
		}

		// Affichage du flux
		$this->rss->close();
	}

	/**
	 * Affiche un flux RSS du sujet, chaque message etant un item different
	 *
	 */
	public function rss_topic()
	{
		if (!$this->id)
		{
			Display::message('not_allowed');
		}

		// Liste des messages
		$sql = 'SELECT p.p_id, p.p_text, p.p_time, p.u_id, p.p_nickname, p.p_map, t.t_title, t.t_description, t.f_id, t.t_id, u.u_activate_email, u.u_email, u.u_auth
				FROM ' . SQL_PREFIX . 'posts p
				INNER JOIN ' . SQL_PREFIX . 'topics t
					ON p.t_id = t.t_id
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = p.u_id
				WHERE t.t_id = ' . $this->id . '
					AND p.p_approve = 0
				ORDER BY p.p_time DESC';
		$result = Fsb::$db->query($sql);
		if ($row = Fsb::$db->row($result))
		{
			if (!Fsb::$session->is_authorized($row['f_id'], 'ga_read') || !Fsb::$session->is_authorized($row['f_id'], 'ga_view') || !Fsb::$session->is_authorized($row['f_id'], 'ga_view_topics'))
			{
				Display::message('not_allowed');
			}

			$parser = new Parser();
			$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;

			$this->rss->open(
				Parser::title($row['t_title']),
				htmlspecialchars(($row['t_description']) ? $row['t_description'] : $parser->mapped_message($row['p_text'], $row['p_map'])),
				Fsb::$session->data['u_language'],
				sid(Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=rss&amp;mode=topic&amp;id=' . $this->id),
				$row['p_time']
			);

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
				$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;

				$this->rss->add_entry(
					Parser::title($row['t_title']),
					htmlspecialchars($parser->mapped_message($row['p_text'], $row['p_map'], $parser_info)),
					(($row['u_activate_email'] & 2) ? 'mailto:' . $row['u_email'] : Fsb::$cfg->get('forum_mail')) . ' ' . htmlspecialchars($row['p_nickname']),
					sid(Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=topic&p_id=' . $row['p_id'] . '#p' . $row['p_id']),
					$row['p_time']
				);
			}
			while ($row = Fsb::$db->row($result));
		}
		// Aucun message, on pioche donc directement les informations dans le sujet
		else 
		{
			$sql = 'SELECT t_id, t_title, t_description, t_time
					FROM ' . SQL_PREFIX . 'topics
					WHERE t_id = ' . $this->id;
			$row = Fsb::$db->request($sql);
			$this->rss->open(
				Parser::title($row['t_title']),
				htmlspecialchars($row['t_description']),
				Fsb::$session->data['u_language'],
				sid(Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=rss&amp;mode=topic&amp;id=' . $this->id),
				$row['t_time']
			);
		}
	}

	/**
	 * Affiche un flux RSS des sujets du forum, chaque sujet etant un item different
	 *
	 */
	public function rss_forum()
	{
		if (!$this->id || !Fsb::$session->is_authorized($this->id, 'ga_view') || !Fsb::$session->is_authorized($this->id, 'ga_view_topics'))
		{
			Display::message('not_allowed');
		}

		// Liste des messages
		$sql = 'SELECT f.f_name, p.p_id, p.u_id, p.f_id, p.p_text, p.p_time, p.p_nickname, p.p_map, t.t_id, t.t_title, t.t_description, u.u_activate_email, u.u_email, u.u_auth
				FROM ' . SQL_PREFIX . 'forums f
				LEFT JOIN ' . SQL_PREFIX . 'topics t
					ON f.f_id = t.f_id
				LEFT JOIN ' . SQL_PREFIX . 'posts p
					ON p.p_id = t.t_first_p_id
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = p.u_id
				WHERE t.f_id = ' . $this->id . '
					AND p.p_approve = 0
				ORDER BY t.t_last_p_time DESC
				LIMIT 100';
		$this->check_caching($sql, 'rss_' . $this->id . '_');
		$result = Fsb::$db->query($sql, 'rss_' . $this->id . '_');
		if ($row = Fsb::$db->row($result))
		{
			$this->rss->open(
				htmlspecialchars(Fsb::$cfg->get('forum_name') . Fsb::$session->getStyle('other', 'title_separator') . $row['f_name']),
				htmlspecialchars(sprintf(Fsb::$session->lang('rss_forum_name'), $row['f_name'])),
				Fsb::$session->data['u_language'],
				sid(Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=rss&amp;mode=forum&amp;id=' . $this->id),
				$row['p_time']
			);

			$parser = new Parser();
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
				$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;

				$this->rss->add_entry(
					Parser::title($row['t_title']),
					htmlspecialchars(($row['t_description']) ? $row['t_description'] : $parser->mapped_message($row['p_text'], $row['p_map'], $parser_info)),
					(($row['u_activate_email'] & 2) ? 'mailto:' . $row['u_email'] : Fsb::$cfg->get('forum_mail')) . ' ' . htmlspecialchars($row['p_nickname']),
					sid(Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=topic&t_id=' . $row['t_id']),
					$row['p_time']
				);
			}
			while ($row = Fsb::$db->row($result));
		}
		// Aucun sujet, on pioche donc directement les informations dans le forum
		else 
		{
			$sql = 'SELECT f_id, f_name, f_text
					FROM ' . SQL_PREFIX . 'forums
					WHERE f_id = ' . $this->id;
			$row = Fsb::$db->request($sql);

			$this->rss->open(
				Parser::title($row['f_name']),
				htmlspecialchars($row['f_text']),
				Fsb::$session->data['u_language'],
				sid(Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=rss&amp;mode=forum&amp;id=' . $this->id),
				CURRENT_TIME
			);
		}
	}

	/**
	 * Affiche un flux RSS des sujets de l'ensemble du forum
	 *
	 */
	public function rss_index()
	{
		// Liste des messages
		$sql = 'SELECT f.f_name, p.p_id, p.f_id, p.u_id, p.p_text, p.p_time, p.p_nickname, p.p_map, t.t_id, t.t_title, t.t_description, u.u_activate_email, u.u_email, u.u_auth
				FROM ' . SQL_PREFIX . 'forums f
				LEFT JOIN ' . SQL_PREFIX . 'topics t
					ON f.f_id = t.f_id
				INNER JOIN ' . SQL_PREFIX . 'posts p
					ON p.p_id = t.t_first_p_id
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = p.u_id
				WHERE p.p_approve = 0'
					. (($cat_id = intval(Http::request('cat'))) ? ' AND f.f_cat_id = ' . $cat_id : '')
					. (($forums_idx = Forum::get_authorized(array('ga_view', 'ga_view_topics'))) ? ' AND t.f_id IN (' . implode(', ', $forums_idx) . ')' : '') . '
				ORDER BY t.t_last_p_time DESC
				LIMIT 100';
		$result = Fsb::$db->query($sql);
		if ($row = Fsb::$db->row($result))
		{
			$this->rss->open(
				htmlspecialchars(Fsb::$cfg->get('forum_name') . Fsb::$session->getStyle('other', 'title_separator') . Fsb::$session->lang('rss_index')),
				htmlspecialchars(Fsb::$session->lang('rss_index')),
				Fsb::$session->data['u_language'],
				sid(Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=rss&amp;mode=index&amp;cat=' . $cat_id),
				$row['p_time']
			);

			$parser = new Parser();
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

				$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;
				$this->rss->add_entry(
					Parser::title($row['t_title']),
					htmlspecialchars(($row['t_description']) ? $row['t_description'] : $parser->mapped_message($row['p_text'], $row['p_map'], $parser_info)),
					(($row['u_activate_email'] & 2) ? 'mailto:' . $row['u_email'] : Fsb::$cfg->get('forum_mail')) . ' ' . htmlspecialchars($row['p_nickname']),
					sid(Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=topic&t_id=' . $row['t_id']),
					$row['p_time']
				);
			}
			while ($row = Fsb::$db->row($result));
		}
		// Aucun sujet ...
		else 
		{
			$this->rss->open(
				htmlspecialchars(Fsb::$cfg->get('forum_name') . Fsb::$session->getStyle('other', 'title_separator') . Fsb::$session->lang('rss_index')),
				htmlspecialchars(Fsb::$session->lang('rss_index')),
				Fsb::$session->data['u_language'],
				sid(Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=rss&amp;mode=index&amp;cat=' . $cat_id),
				CURRENT_TIME
			);
		}
	}

	/**
	 * Affiche un flux RSS des 10 derniers messages d'un membre
	 *
	 */
	public function rss_user()
	{
		if (!$this->id)
		{
			Display::message('not_allowed');
		}

		// Liste des messages
		$sql = 'SELECT p.p_id, p.p_text, p.p_time, p.u_id, p.p_nickname, p.p_map, t.t_title, t.t_description, t.f_id, u.u_nickname, u.u_activate_email, u.u_email, u.u_auth
				FROM ' . SQL_PREFIX . 'posts p
				INNER JOIN ' . SQL_PREFIX . 'topics t
					ON p.t_id = t.t_id
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = p.u_id
				WHERE p.u_id = ' . $this->id . '
				ORDER BY p.p_time DESC
				LIMIT 10';
		$result = Fsb::$db->query($sql);
		if ($row = Fsb::$db->row($result))
		{
			$parser = new Parser();
			$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;

			$this->rss->open(
				htmlspecialchars($row['u_nickname']),
				'',
				Fsb::$session->data['u_language'],
				sid(Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=rss&amp;mode=user&amp;id=' . $this->id),
				$row['p_time']
			);

			do
			{
				$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;
				$this->rss->add_entry(
					Parser::title($row['t_title']),
					htmlspecialchars($parser->mapped_message($row['p_text'], $row['p_map'])),
					(($row['u_activate_email'] & 2) ? 'mailto:' . $row['u_email'] : Fsb::$cfg->get('forum_mail')) . ' ' . htmlspecialchars($row['p_nickname']),
					sid(Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=topic&p_id=' . $row['p_id'] . '#p' . $row['p_id']),
					$row['p_time']
				);
			}
			while ($row = Fsb::$db->row($result));
		}
		// Aucun message pour ce membre ...
		else 
		{
			$sql = 'SELECT u_nickname
					FROM ' . SQL_PREFIX . 'users
					WHERE u_id = ' . $this->id;
			$row = Fsb::$db->request($sql);
			
			$this->rss->open(
				htmlspecialchars($row['u_nickname']),
				'',
				Fsb::$session->data['u_language'],
				sid(Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=rss&amp;mode=user&amp;id=' . $this->id),
				CURRENT_TIME
			);
		}
	}

	/**
	 * Supprimes les RSS mis en cache dont le temps est expire
	 *
	 * @param string $sql
	 * @param string $prefix
	 */
	public function check_caching($sql, $prefix)
	{
		$hash = md5($sql);
		if (Fsb::$db->cache->exists($hash))
		{
			$time = Fsb::$db->cache->get_time($hash);
			if ($time < CURRENT_TIME - (Fsb::$cfg->get('rss_caching') * ONE_HOUR))
			{
				Fsb::$db->destroy_cache($prefix);
			}
		}
	}
}

/* EOF */