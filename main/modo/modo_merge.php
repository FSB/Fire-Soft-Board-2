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
 * Module de moderation pour la fusion de sujets
 *
 */
class Page_modo_merge extends Fsb_model
{
	/**
	 * ID du sujet a fusioner
	 *
	 * @var int
	 */
	public $id;

	/**
	 * ID du forum du sujet
	 *
	 * @var int
	 */
	public $forum_id;

	/**
	 * Maximum de sujets a afficher pour le filtre de fusion
	 *
	 * @var int
	 */
	public $merge_limit = 200;

	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		$this->id = intval(Http::request('id', 'post|get'));

		$this->check_topic_data();
		if (Http::request('submit_merge', 'post'))
		{
			$this->merge_topics();
		}
		$this->form_merge_id();
		$this->form_merge_topics();
	}

	/**
	 * Verifie si le sujet existe et si on peut le modifier
	 *
	 */
	public function check_topic_data()
	{
		if (!$this->id)
		{
			return ;
		}

		// Donnees du sujet sellectionne
		$sql = 'SELECT f_id, t_title, u_id
				FROM ' . SQL_PREFIX . 'topics
				WHERE t_id = ' . $this->id;
		$result = Fsb::$db->query($sql);
		$this->data = Fsb::$db->row($result);
		if (!$this->data)
		{
			Display::message('topic_not_exists');
		}
		Fsb::$db->free($result);

		// Droit de moderation ?
		if (!Fsb::$session->is_authorized($this->data['f_id'], 'ga_moderator'))
		{
			Display::message('not_allowed');
		}
	}

	/**
	 * Affiche le formulaire de choix d'ID du sujet
	 *
	 */
	public function form_merge_id()
	{
		Fsb::$tpl->set_file('modo/modo_merge.html');
		Fsb::$tpl->set_vars(array(
			'THIS_ID' =>	$this->id,
		));
	}

	/**
	 * Affiche le formulaire pour entrer les ID des sujets a fusioner
	 *
	 */
	public function form_merge_topics()
	{
		if (!$this->id)
		{
			return ;
		}

		// Filtres
		$title =	trim(Http::request('find_title'));
		$forums =	(array) Http::request('find_forums');
		$forums =	array_map('intval', $forums);

		// Liste des sujets trouves
		$sql = 'SELECT t.t_id, t.t_title, t.t_description, f.f_name
				FROM ' . SQL_PREFIX . 'topics t
				LEFT JOIN ' . SQL_PREFIX . 'forums f
					ON t.f_id = f.f_id
				WHERE t.t_id <> ' . $this->id . '
					' . (($forum_idx = Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read'))) ? 'AND t.f_id IN (' . implode(', ', $forum_idx) . ')' : '') . '
					' . (($forums) ? 'AND t.f_id IN (' . implode(', ', $forums) . ')' : '') . '
					' . (($title) ? 'AND t.t_title LIKE \'%' . Fsb::$db->escape($title) . '%\'' : '') . '
				ORDER BY t.t_last_p_time DESC
				LIMIT ' . $this->merge_limit;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('topic', array(
				'ID' =>			$row['t_id'],
				'TITLE' =>		Parser::title($row['t_title']),

				'U_TOPIC' =>	sid(ROOT . 'index.' . PHPEXT . '?p=topics&amp;t_id=' . $row['t_id']),
			));
		}
		Fsb::$db->free($result);

		Fsb::$tpl->set_switch('show_merge');
		Fsb::$tpl->set_vars(array(
			'FIND_TITLE' =>				htmlspecialchars($title),
			'LIST_MERGE_FORUMS' =>		Html::list_forums(get_forums(), $forums, 'find_forums[]', false, 'multiple="multiple" size="5"'),

			'U_ACTION' =>				sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=merge&amp;id=' . $this->id),
		));
	}

	/**
	 * Fusion des sujets
	 *
	 */
	public function merge_topics()
	{
		if (!$this->id)
		{
			return ;
		}

		// ID des sujets a fusioner avec le sujet initial
		$idx = trim(Http::request('merge_idx', 'post'));
		$idx = explode("\n", $idx);
		$idx = array_map('intval', $idx);
		if ($idx)
		{
			// On filtre les sujets autorises
			$sql = 'SELECT t_id, f_id
					FROM ' . SQL_PREFIX . 'topics
					WHERE t_id IN (' . implode(', ', $idx) . ')';
			$result = Fsb::$db->query($sql);
			$origin = array();
			$destination = $this->id;
			while ($row = Fsb::$db->row($result))
			{
				if (Fsb::$session->is_authorized($row['f_id'], 'ga_moderator'))
				{
					$origin[] = $row['t_id'];
				}
			}
			Fsb::$db->free($result);

			// Si on fusionne avec ...
			if (Http::request('merge_to_check', 'post'))
			{
				$merge_to = intval(Http::request('merge_to', 'post'));
				$origin[] = $this->id;
				$destination = $merge_to;

				$sql = 'SELECT t_id
						FROM ' . SQL_PREFIX . 'topics
						WHERE t_id = ' . $destination;
				if (!Fsb::$db->get($sql, 't_id'))
				{
					Display::message('topic_not_exists');
				}
			}

			Moderation::merge_topics($destination, $this->data['f_id'], $this->data['u_id'], $origin);

			Log::add(Log::MODO, 'log_merge', $this->data['t_title']);

			Display::message('modo_merge_well', ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $destination, 'forum_topic');
		}
	}
}

/* EOF */