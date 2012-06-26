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
 * Version bas debit du forum
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
	public $_show_page_footer_nav = true;
	
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
	 * Page en cours
	 *
	 * @var int
	 */
	public $page;
	
	/**
	 * Sujets par page
	 *
	 * @var int
	 */
	public $topic_per_page = 100;
	
	/**
	 * Messages par page
	 *
	 * @var int
	 */
	public $post_per_page = 30;
	
	/**
	 * Navigation
	 *
	 * @var array
	 */
	public $nav = array();
	
	/**
	 * Page bas débit ?
	 *
	 * @var bool
	 */
	public $is_low_page = true;

	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		$this->mode =	Http::request('mode');
		$this->id =		intval(Http::request('id'));
		$this->page =	intval(Http::request('page'));

		if ($this->page <= 0)
		{
			$this->page = 1;
		}

		Fsb::$tpl->set_file('forum/forum_low.html');
		Fsb::$tpl->set_vars(array(
			'HIGH_VERSION' =>	sprintf(Fsb::$session->lang('low_high'), Fsb::$cfg->get('forum_name')),
			'U_LOW_INDEX' =>	sid(ROOT . 'index.' . PHPEXT . '?p=low'),
		));

		$call = new Call($this);
		$call->functions(array(
			'mode' => array(
				'forum' =>		'low_forum',
				'topic' =>		'low_topic',
				'index' =>		'low_index',
				'default' =>	'low_index',
			),
		));
	}

	/**
	 * Affichage des forums sur l'index
	 *
	 */
	public function low_index()
	{
		Fsb::$tpl->set_switch('low_index');
		Fsb::$tpl->set_vars(array(
			'U_HIGH_VERSION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=index'),
		));

		// Liste des forums autorises
		$forums = Forum::get_authorized(array('ga_view'));

		// Affichage d'une categorie
		$sql_cat = '';
		if ($this->id)
		{
			$sql = 'SELECT f_left, f_right
					FROM ' . SQL_PREFIX . 'forums
					WHERE f_parent = 0
						AND f_id = ' . $this->id;
			if ($data = Fsb::$db->request($sql))
			{
				$this->nav = Forum::nav($this->id, array(), $this);
				$sql_cat = 'AND f_left >= ' . $data['f_left'] . ' AND f_right <= ' . $data['f_right'];
			}
		}

		// Affichage des forums
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'forums
				WHERE (f_parent = 0 OR f_id IN (' . implode(', ', $forums) . '))
					' . $sql_cat . '
				ORDER BY f_left';
		$result = Fsb::$db->query($sql, 'forums_');
		while ($row = Fsb::$db->row($result))
		{
			if ($row['f_parent'] == 0)
			{
				$last_cat = $row;
			}
			else
			{
				if ($last_cat)
				{
					Fsb::$tpl->set_blocks('cat', array(
						'NAME' =>	$last_cat['f_name'],

						'U_CAT' =>	sid(ROOT . 'index.' . PHPEXT . '?p=low&amp;mode=index&amp;id=' . $last_cat['f_id']),
					));
					$last_cat = null;
				}

				Fsb::$tpl->set_blocks('cat.forum', array(
					'NAME' =>		$row['f_name'],
					'MARGIN' =>		40 * $row['f_level'],
					'TOTAL' =>		sprintf(Fsb::$session->lang('low_index_total'), $row['f_total_topic'], $row['f_total_post']),

					'U_FORUM' =>	($row['f_type'] == FORUM_TYPE_DIRECT_URL) ? $row['f_location'] : sid(ROOT . 'index.' . PHPEXT . '?p=low&amp;mode=forum&amp;id=' . $row['f_id']),
				));
			}
		}
		Fsb::$db->free($result);
	}

	/**
	 * Affichage des sujets
	 *
	 */
	public function low_forum()
	{
		// Informations sur le forum
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'forums
				WHERE f_id = ' . $this->id;
		$result = Fsb::$db->query($sql, 'forums_' . $this->id . '_');
		$data = Fsb::$db->row($result);
		Fsb::$db->free($result);

		if (!$data || $data['f_parent'] == 0 || $data['f_password'])
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=low');
		}

		// Verification des droits d'acces
		if (!Fsb::$session->is_authorized($this->id, 'ga_view') || !Fsb::$session->is_authorized($this->id, 'ga_view_topics'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=low');
		}

		Fsb::$tpl->set_switch('low_forum');
		Fsb::$tpl->set_vars(array(
			'FORUM_TITLE' =>		$data['f_name'],
			'U_HIGH_VERSION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=forum&amp;f_id=' . $this->id),
		));

		// Navigation de la page
		$this->nav = Forum::nav($this->id, array(), $this);

		// Ce qu'on va afficher, en fonction du type de forum
		switch ($data['f_type'])
		{
			case FORUM_TYPE_NORMAL :
				Fsb::$tpl->set_switch('show_topics');
			break;

			case FORUM_TYPE_SUBCAT :
				Fsb::$tpl->unset_switch('show_topics');
			break;

			case FORUM_TYPE_INDIRECT_URL :
				Fsb::$db->update('forums', array(
					'f_location_view' =>	array('(f_location_view + 1)', 'is_field' => true),
				), 'WHERE f_id = ' . $this->id);
				Http::redirect($data['f_location']);
			break;

			case FORUM_TYPE_DIRECT_URL :
				Http::redirect($data['f_location']);
			break;
		}

		// Affichage des sous forums
		if ($data['f_right'] - $data['f_left'] > 1)
		{
			$sql = 'SELECT f_id, f_name, f_type, f_total_topic, f_total_post
					FROM ' . SQL_PREFIX . 'forums
					WHERE f_left > ' . $data['f_left'] . '
						AND f_right < ' . $data['f_right'] . '
						AND f_level = ' . $data['f_level'] . ' + 1
						AND f_id IN (' . implode(', ', Forum::get_authorized(array('ga_view'))) . ')';
			$result = Fsb::$db->query($sql, 'forums_' . $this->id . '_');
			while ($row = Fsb::$db->row($result))
			{
				Fsb::$tpl->set_blocks('sub', array(
					'NAME' =>		$row['f_name'],
					'TOTAL' =>		sprintf(Fsb::$session->lang('low_index_total'), $row['f_total_topic'], $row['f_total_post']),

					'U_FORUM' =>	($row['f_type'] == FORUM_TYPE_DIRECT_URL) ? $row['f_location'] : sid(ROOT . 'index.' . PHPEXT . '?p=low&amp;mode=forum&amp;id=' . $row['f_id']),
				));
			}
			Fsb::$db->free($result);
		}

		// Total de sujets du forum
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'topics
				WHERE (f_id = ' . $this->id . ' OR t_trace = ' . $this->id . ')
					AND t_approve = 0';
		$total = Fsb::$db->get($sql, 'total');

		if ($data['f_global_announce'])
		{
			$total += Fsb::$cfg->get('total_global_announce');
			$sql_announce = ' OR t_type = ' . GLOBAL_ANNOUNCE;
		}

		// Pagination
		$total_page = ceil($total / $this->topic_per_page);
		if ($total_page > 1)
		{
			Fsb::$tpl->set_vars(array(
				'PAGINATION' =>		Html::pagination($this->page, $total_page, ROOT . 'index.' . PHPEXT . '?p=low&amp;mode=forum&amp;id=' . $this->id),
			));
		}

		// Affichage des sujets
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'topics
				WHERE f_id = ' . $this->id
					. $sql_announce . '
				ORDER BY t_type, t_last_p_time DESC
				LIMIT ' . (($this->page - 1) * $this->topic_per_page) . ', ' . $this->topic_per_page;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('topic', array(
				'TITLE' =>			Parser::title($row['t_title']),
				'IS_ANNOUNCE' =>	($row['t_type'] <= 1) ? true : false,
				'TOTAL' =>			sprintf(String::plural('low_answer', $row['t_total_post'] - 1), $row['t_total_post'] - 1),

				'U_TOPIC' =>	sid(ROOT . 'index.' . PHPEXT . '?p=low&amp;mode=topic&amp;id=' . $row['t_id']),
			));
		}
		Fsb::$db->free($result);
	}

	
	/**
	 * Affichage des sujets
	 *
	 */
	public function low_topic()
	{
		// Si la fonction de notification est activee on ajoute une jointure a la requete
		$sql_select = $sql_join = '';
		if (Fsb::$mods->is_active('topic_notification'))
		{
			$sql_select = ', tn.u_id AS can_notify, tn.tn_status';
			$sql_join = 'LEFT JOIN ' . SQL_PREFIX . 'topics_notification tn ON tn.t_id = t.t_id AND tn.u_id = ' . Fsb::$session->id();
		}

		// Informations sur le sujet
		$sql = 'SELECT t.*, tr.p_id AS last_unread_id' . $sql_select . '
				FROM ' . SQL_PREFIX . 'topics t
				LEFT JOIN ' . SQL_PREFIX . 'topics_read tr
					ON t.t_id = tr.t_id
						AND tr.u_id = ' . Fsb::$session->id() . '
				' . $sql_join . '
				WHERE t.t_id = ' . $this->id;
		$data = Fsb::$db->request($sql);

		// Verification des droits d'acces
		if (!$data || !Fsb::$session->is_authorized($data['f_id'], 'ga_view') || !Fsb::$session->is_authorized($data['f_id'], 'ga_view_topics') || !Fsb::$session->is_authorized($data['f_id'], 'ga_read'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=low');
		}

		// Navigation de la page
		$this->nav = Forum::nav($data['f_id'], array(array(
			'url' =>		sid(ROOT . 'index.' . PHPEXT . '?p=low&amp;mode=topic&amp;id=' . $data['t_id']),
			'name' =>		Parser::title($data['t_title']),
		)), $this);

		Fsb::$tpl->set_switch('low_topic');
		Fsb::$tpl->set_vars(array(
			'TOPIC_TITLE' =>		Parser::title($data['t_title']),
			'U_HIGH_VERSION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $this->id),
		));

		// Pagination
		$total_page = ceil($data['t_total_post'] / $this->post_per_page);
		if ($total_page > 1)
		{
			Fsb::$tpl->set_vars(array(
				'PAGINATION' =>		Html::pagination($this->page, $total_page, ROOT . 'index.' . PHPEXT . '?p=low&amp;mode=topic&amp;id=' . $this->id),
			));
		}

		// On commence par recuperer les ID des messages qui seront affiches
		$sql = 'SELECT p_id
				FROM ' . SQL_PREFIX . 'posts
				WHERE t_id = ' . $data['t_id'] . '
					AND p_approve = 0
				ORDER BY p_time
				LIMIT ' . (($this->page - 1) * $this->post_per_page) . ', ' . ($this->post_per_page);
		$result = Fsb::$db->query($sql);
		$idx = array();
		while ($row = Fsb::$db->row($result))
		{
			$idx[] = $row['p_id'];
		}
		Fsb::$db->free($result);

		if (!$idx)
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=low');
		}

		// Affichage des messages
		$parser = new Parser();

		$sql = 'SELECT p.u_id, p.f_id, p.t_id, p.p_nickname, p.p_time, p.p_text, p.p_map, u.u_auth
				FROM ' . SQL_PREFIX . 'posts p
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = p.u_id
				WHERE p.p_id IN (' . implode(', ', $idx) . ')
				ORDER BY p.p_time';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
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
			Fsb::$tpl->set_blocks('post', array(
				'CONTENT' =>		$parser->mapped_message($row['p_text'], $row['p_map'], $parser_info),
				'NICKNAME' =>		htmlspecialchars($row['p_nickname']),
				'DATE' =>			Fsb::$session->print_date($row['p_time']),

				'U_NICKNAME' =>		sid(ROOT . 'index.' . PHPEXT . '?p=userprofile&amp;id=' . $row['u_id']),
			));
		}
		Fsb::$db->free($result);
		
		// Marquer le sujet lu
		if (Fsb::$session->is_logged() && $data['t_last_p_time'] > MAX_UNREAD_TOPIC_TIME)
		{
			if (!$data['last_unread_id'] || $data['last_unread_id'] < $data['t_last_p_id'])
			{
				Fsb::$db->insert('topics_read', array(
					'u_id' =>			array(Fsb::$session->id(), true),
					't_id' =>			array($data['t_id'], true),
					'p_id' =>			$data['t_last_p_id'],
				), 'REPLACE');
			}
			
			// Remise a jour de la notification du membre
			if (Fsb::$mods->is_active('topic_notification') && Fsb::$session->data['u_activate_auto_notification'] & NOTIFICATION_EMAIL && $data['can_notify'] && $data['tn_status'] == IS_NOTIFIED)
			{
				Fsb::$db->update('topics_notification', array(
					'tn_status' =>	IS_NOT_NOTIFIED,
				), 'WHERE u_id = ' . Fsb::$session->id() . ' AND t_id = ' . $data['t_id']);
			}
		}
		
		// Visions du sujet
		$update_total_view = true;
		if (Fsb::$mods->is_active('cookie_view'))
		{
			// Page deja visitee durant la session ?
			$cookie_view = Http::getcookie('cookie_view');
			if ($cookie_view)
			{
				$cookie_view = @unserialize($cookie_view);
				$update_total_view = (is_array($cookie_view) && in_array($data['t_id'], $cookie_view)) ? false : true;
			}

			if ($update_total_view)
			{
				if (!is_array($cookie_view))
				{
					$cookie_view = array();
				}
				$cookie_view[] = $data['t_id'];
				Http::cookie('cookie_view', serialize($cookie_view), 0);
			}
		}

		// +1 visite au sujet si pas deja visite
		if ($update_total_view)
		{
			Fsb::$db->update('topics', array(
				't_total_view' =>	array('(t_total_view + 1)', 'is_field' => true),
			), 'WHERE t_id = ' . $data['t_id']);
		}
	}
}

/* EOF */
