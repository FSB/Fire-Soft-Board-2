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
 * Module de portail permettant d'afficher les premies messages de votre forum news
 */
class Page_portail_news extends Fsb_model
{
	/**
	 * Constructeur
	 */
	public function main()
	{
		// Instance de la classe Post() pour parser les messages
		$parser = new Parser();

		// On verifie que le membre peut voir les news, sinon, on affiche rien
		$id_news = $this->portail_config['id_forum_news'];
		if (Fsb::$session->is_authorized($id_news, 'ga_view') && Fsb::$session->is_authorized($id_news, 'ga_view_topics') && Fsb::$session->is_authorized($id_news, 'ga_read'))
		{
			$sql = 'SELECT t.t_id, t.u_id, t.t_title, t.t_total_post, t.t_type, t.t_status, t.f_id, p.p_nickname, p.p_text, p.p_time, p.p_map, u.u_color, u.u_auth, f.f_status
					FROM ' . SQL_PREFIX . 'posts p
					INNER JOIN ' . SQL_PREFIX . 'topics t
						ON p.p_id = t.t_first_p_id
					LEFT JOIN ' . SQL_PREFIX . 'users u
						ON u.u_id = p.u_id
					LEFT JOIN ' . SQL_PREFIX . 'forums f
						ON t.f_id = f.f_id
					WHERE p.f_id = ' . intval($id_news) . '
						AND p.p_approve = ' . IS_APPROVED . '
					ORDER BY p.p_time DESC
					LIMIT ' . intval($this->portail_config['nb_news']);
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

				// Parse du message
				$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;
				$text = $parser->mapped_message($row['p_text'], $row['p_map'], $parser_info);

				Fsb::$tpl->set_blocks('news', array(
					'NEW_NAME' =>			Parser::title($row['t_title']),
					'NEW_TEXT' =>			$text,
					'NEW_AUTHOR' =>			sprintf(Fsb::$session->lang('pm_post_by'), Html::nickname($row['p_nickname'], $row['u_id'], $row['u_color'])),
					'NB_COMMENTS' =>		sprintf(String::plural('pm_total_comment', $row['t_total_post'] - 1), $row['t_total_post'] - 1), 
					'POST_AT' =>			Fsb::$session->print_date($row['p_time']),
					'CAN_REPLY' =>			((Fsb::$session->is_authorized($row['f_id'], 'ga_answer_' . $GLOBALS['_topic_type'][$row['t_type']])
			&& (($row['t_status'] != LOCK && $row['f_status'] != LOCK) || Fsb::$session->is_authorized($row['f_id'], 'ga_moderator')))) ? true : false,

					'U_COMMENT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=reply&amp;id=' . $row['t_id']),
				));
			}
		}
		else
		{
			// N'est pas autorise a regarder les news
			Fsb::$tpl->set_switch('dont_show_news');
		}
	}
}

/* EOF */