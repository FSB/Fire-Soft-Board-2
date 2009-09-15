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
 * Module de moderation pour le deplacement de sujets
 *
 */
class Page_modo_move extends Fsb_model
{
	/**
	 * ID du sujet a deplacer
	 *
	 * @var int
	 */
	public $id;

	/**
	 * ID des sujets a deplacer s'il y en a plusieurs
	 *
	 * @var array
	 */
	public $idx;
	
	/**
	 * ID du forum
	 *
	 * @var int
	 */
	public $f_id;

	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		$this->id =		intval(Http::request('id'));
		$this->idx =	urldecode(Http::request('topics'));
		$this->f_id =	intval(Http::request('f_id'));

		// On protege $this->idx qui est une liste d'ID
		if ($this->idx)
		{
			$this->idx = explode(',', strval($this->idx));
			$this->idx = array_map('intval', $this->idx);
			$this->idx = implode(',', $this->idx);
		}

		if (Http::request('submit', 'post'))
		{
			$this->move_topic();
		}
		$this->show_form();
	}

	/**
	 * Affiche le formulaire de deplacement du sujet
	 *
	 */
	public function show_form()
	{
		Fsb::$tpl->set_switch('show_choose_id');
		if ($this->id)
		{
			$sql = 'SELECT f_id
					FROM ' . SQL_PREFIX . 'topics
					WHERE t_id = ' . $this->id
					. ' AND f_id IN (' . Fsb::$session->moderated_forums() . ')';
			$result = Fsb::$db->query($sql);
			if ($topic_data = Fsb::$db->row($result))
			{
				Fsb::$tpl->set_switch('show_move');
				Fsb::$tpl->set_vars(array(
					'LIST_FORUM' =>		Html::list_forums(get_forums(), $topic_data['f_id'], 'move_forum', false),
				));
			}
			Fsb::$db->free($result);
		}
		else if ($this->idx)
		{
			Fsb::$tpl->set_switch('show_move');
			Fsb::$tpl->unset_switch('show_choose_id');
			Fsb::$tpl->set_vars(array(
				'LIST_FORUM' =>		Html::list_forums(get_forums(), $this->f_id, 'move_forum', false),
			));
		}

		Fsb::$tpl->set_file('modo/modo_move.html');
		Fsb::$tpl->set_vars(array(
			'THIS_ID' =>		$this->id,
			'U_ACTION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=move' . (($this->idx) ? '&amp;f_id=' . $this->f_id . '&amp;topics=' . urlencode($this->idx) : '')),
		));
	}

	/**
	 * Deplace le sujet vers le forum indique
	 *
	 */
	public function move_topic()
	{
		$forum_id = intval(Http::request('move_forum', 'post'));
		$trace =	intval(Http::request('trace_topic', 'post'));

		// Donnees du forum cible
		$sql = 'SELECT f_type
				FROM ' . SQL_PREFIX . 'forums
				WHERE f_id = ' . $forum_id;
		$f_type = Fsb::$db->get($sql, 'f_type');

		// On verifie le type de forum
		if ($f_type != FORUM_TYPE_NORMAL)
		{
			Display::message('modo_move_bad_type');
		}

		// On verifie les droits d'ecriture du forum
		if (!Fsb::$session->is_authorized($forum_id, 'ga_create_' . $GLOBALS['_topic_type'][count($GLOBALS['_topic_type']) - 1]))
		{
			Display::message('modo_move_no_write');
		}

		if ($this->id)
		{
			// Deplacement d'un unique sujet
			$sql = 'SELECT f_id, t_title
					FROM ' . SQL_PREFIX . 'topics
					WHERE t_id = ' . $this->id
					. ' AND f_id IN (' . Fsb::$session->moderated_forums() . ')';
			$result = Fsb::$db->query($sql);
			if ($topic_data = Fsb::$db->row($result))
			{
				Fsb::$db->free($result);
				Moderation::move_topics($this->id, $topic_data['f_id'], $forum_id, $trace);

				Log::add(Log::MODO, 'log_move', $topic_data['t_title']);
			}

			Display::message('modo_move_well', ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $this->id, 'forum_topic');
		}
		else if ($this->idx)
		{
			// Protection des sujets a deplacer
			$sql = 'SELECT t_id
					FROM ' . SQL_PREFIX . 'topics
					WHERE t_id IN (' . $this->idx . ')'
					. ' AND f_id IN (' . Fsb::$session->moderated_forums() . ')';
			$result = Fsb::$db->query($sql);
			$this->idx = array();
			while ($row = Fsb::$db->row($result))
			{
				$this->idx[] = $row['t_id'];
			}
			Fsb::$db->free($result);

			// Deplacement de plusieurs sujets
			if ($this->idx)
			{
				Moderation::move_topics($this->idx, $this->f_id, $forum_id, $trace);
				Log::add(Log::MODO, 'log_topics');
			}

			Display::message('modo_move_well', ROOT . 'index.' . PHPEXT . '?p=forum&amp;f_id=' . $this->f_id, 'forum_forum');
		}
	}
}

/* EOF */