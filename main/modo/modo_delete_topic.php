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
 * Module de moderation pour la suppression d'un sujet
 *
 */
class Page_modo_delete_topic extends Fsb_model
{
	/**
	 * ID du message a supprimer
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
		$this->id = intval(Http::request('id'));
		if (!$this->id)
		{
			Display::message('topic_not_exists');
		}

		// Informations sur le message en question, ainsi que sur son sujet
		$sql = 'SELECT f_id, t_title
				FROM ' . SQL_PREFIX . 'topics
				WHERE t_id = ' . $this->id;
		$result = Fsb::$db->query($sql);
		if (!$data = Fsb::$db->row($result))
		{
			Display::message('topic_not_exists');
		}
		Fsb::$db->free($result);

		if (!Fsb::$session->is_authorized($data['f_id'], 'ga_moderator'))
		{
			Display::message('not_allowed');
		}

		// Boite de confirmation
		if (check_confirm())
		{
			Moderation::delete_topics('t_id = ' . $this->id);

			Log::add(Log::MODO, 'log_delete_topic', $data['t_title']);
			Display::message('modo_delete_topic_well', ROOT . 'index.' . PHPEXT . '?p=forum&amp;f_id=' . $data['f_id'], 'modo_delete_topic');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=topic&t_id=' . $this->id);
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('modo_delete_confirm_topic'), ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=delete_topic&amp;id=' . $this->id, array('id' => $this->id));
		}
	}
}

/* EOF */