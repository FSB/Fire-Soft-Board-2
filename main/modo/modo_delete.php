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
 * Module de moderation pour la suppression d'un message
 *
 */
class Page_modo_delete extends Fsb_model
{
	/**
	 * ID du message a supprimer
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Donnees du sujet
	 *
	 * @var array
	 */
	public $topic_data;

	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		$this->id = intval(Http::request('id'));
		if (!$this->id)
		{
			Display::message('post_not_exists');
		}

		// Informations sur le message en question, ainsi que sur son sujet
		$sql = 'SELECT p.u_id, t.t_id, t.f_id, t.t_title, t.t_total_post, t.t_first_p_id, t.t_last_p_id, t.t_status
				FROM ' . SQL_PREFIX . 'posts p
				INNER JOIN ' . SQL_PREFIX . 'topics t
					ON p.t_id = t.t_id
				WHERE p.p_id = ' . $this->id;
		$result = Fsb::$db->query($sql);
		if (!$this->topic_data = Fsb::$db->row($result))
		{
			Display::message('post_not_exists');
		}
		Fsb::$db->free($result);

		// Droits de suppression du message ?
		if (!Fsb::$session->can_delete_post($this->topic_data['u_id'], $this->id, $this->topic_data))
		{
			Fsb::$tpl->unset_switch('show_menu_panel');
			Display::message('not_allowed');
		}

		// S'il s'agit du premier message et que le sujet comporte des reponses on
		// empeche de supprimer le sujet
		if ($this->topic_data['t_first_p_id'] == $this->id && $this->topic_data['t_total_post'] > 1)
		{
			Display::message('modo_delete_first_post');
		}

		// Boite de confirmation
		if (check_confirm())
		{
			Moderation::delete_posts('p_id = ' . $this->id);
			Log::add(Log::MODO, 'log_delete_post', $this->topic_data['t_title']);

			if ($this->topic_data['t_total_post'] == 1)
			{
				Display::message('modo_delete_post_well', ROOT . 'index.' . PHPEXT . '?p=forum&amp;f_id=' . $this->topic_data['f_id'], 'modo_delete_topic');
			}
			else
			{
				Display::message('modo_delete_post_well', ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $this->topic_data['t_id'], 'modo_delete');
			}
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=topic&p_id=' . $this->id . '#' . $this->id);
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('modo_delete_confirm_post'), ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=delete&amp;id=' . $this->id, array('id' => $this->id));
		}
	}
}

/* EOF */