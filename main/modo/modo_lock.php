<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On cache ce module
$show_this_module = false;

/**
 * Module de moderation pour le verrouillage / deverrouillage d'un sujet
 *
 */
class Page_modo_lock extends Fsb_model
{
	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		$id =	intval(Http::request('id'));
		$mode = Http::request('mode');

		// On verifie si le sujet exists
		$sql = 'SELECT t_title, f_id
				FROM ' . SQL_PREFIX . 'topics
				WHERE t_id = ' . intval($id);
		$result = Fsb::$db->query($sql);
		if (!$topic_data = Fsb::$db->row($result))
		{
			Display::message('topic_not_exists');
		}
		Fsb::$db->free($result);

		// On verifie s'il a le droit de verrouiller le sujet
		if (!Fsb::$session->is_authorized($topic_data['f_id'], 'ga_moderator'))
		{
			Display::message('not_allowed');
		}

		// Mise a jour du status du sujet
		Moderation::lock_topic($id, ($mode == 'lock') ? LOCK : UNLOCK);

		// Log
		Log::add(Log::MODO, 'log_' . ((Http::request('mode') == 'lock') ? 'lock' : 'unlock'), $topic_data['t_title']);

		Display::message(($mode == 'lock') ? 'modo_lock_well' : 'modo_unlock_well', ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $id, 'modo_' . (($mode == 'lock') ? 'lock' : 'unlock'));
	}
}

/* EOF */