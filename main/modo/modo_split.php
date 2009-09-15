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
 * Module de moderation pour la division de sujet
 *
 */
class Page_modo_split extends Fsb_model
{
	/**
	 * ID du sujet a diviser
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
		$this->id = intval(Http::request('id', 'post|get'));

		if (Http::request('submit_split', 'post'))
		{
			$this->split_topic();
		}

		$this->show_form();
		if ($this->id)
		{
			$this->show_topic();
		}
	}

	/**
	 * Affiche le formulaire de base pour entrer l'ID
	 *
	 */
	public function show_form()
	{
		Fsb::$tpl->set_file('modo/modo_split.html');
		Fsb::$tpl->set_vars(array(
			'THIS_ID' =>		$this->id,
			'U_ACTION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=split'),
		));
	}

	/**
	 * Affiche les messages du sujet
	 *
	 */
	public function show_topic()
	{
		$parser = new Parser();

		$sql = 'SELECT p.p_id, p.t_id, p.p_text, p.u_id, p.p_nickname, p.p_time, p.p_map, t.f_id, t.t_title, u.u_color, u.u_auth
				FROM ' . SQL_PREFIX . 'posts p
				LEFT JOIN ' . SQL_PREFIX . 'topics t
					ON p.t_id = t.t_id
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = p.u_id
				WHERE p.t_id = ' . $this->id . '
				ORDER BY p.p_time';
		$result = Fsb::$db->query($sql);
		if ($row = Fsb::$db->row($result))
		{
			if (!Fsb::$session->is_authorized($row['f_id'], 'ga_moderator'))
			{
				Display::message('not_allowed');
			}

			// Donnees du sujet
			Fsb::$tpl->set_switch('show_topic');
			Fsb::$tpl->set_vars(array(
				'TOPIC_NAME' =>		Parser::title($row['t_title']),
				'LIST_FORUM' =>		Html::list_forums(get_forums(), $row['f_id'], 'split_forum', false),
			));

			// Messages
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
				Fsb::$tpl->set_blocks('post', array(
					'ID' =>			$row['p_id'],
					'CONTENT' =>	$parser->mapped_message($row['p_text'], $row['p_map'], $parser_info),
					'NICKNAME' =>	Html::nickname($row['p_nickname'], $row['u_id'], $row['u_color']),
					'DATE' =>		Fsb::$session->print_date($row['p_time']),

					'U_LOGIN' =>	sid(ROOT . 'index.' . PHPEXT . '?p=userprofile&amp;id=' . $row['u_id']),
				));
			}
			while ($row = Fsb::$db->row($result));
			Fsb::$db->free($result);
		}
	}

	/**
	 * Divise le sujet en reportant les messages selectionnes dans un nouveau sujet
	 *
	 */
	public function split_topic()
	{
		// Messages selectiones
		$action = (array) Http::request('action', 'post');
		if (!$action)
		{
			Display::message('modo_split_need_action');
		}
		$action = array_map('intval', $action);

		// Titre du nouveau sujet
		$title = trim(Http::request('new_title', 'post'));
		if (empty($title))
		{
			Display::message('modo_split_need_title');
		}

		// Forum de destination
		$forum_id = Http::request('split_forum', 'post');
		if (!$forum_id)
		{
			Display::message('modo_split_need_forum');
		}

		$moderation = new Moderation();
		list($new_topic_id, $t_title) = Moderation::split_topic($this->id, $forum_id, $title, $action);

		Log::add(Log::MODO, 'log_split', $t_title);
		Display::message('modo_split_well', ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $new_topic_id, 'forum_topic');
	}
}

/* EOF */