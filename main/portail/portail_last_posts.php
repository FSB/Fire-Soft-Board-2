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
 * Module de portail affichant les X derniers sujets du forum
 */
class Page_portail_last_posts extends Fsb_model
{
	/**
	 * Constructeur
	 */
	public function main()
	{
		// On recupere les forums que le membre peut lire
		$f_idx = Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read'));

		// On recupere les dernier messages dans les forums que le membre peut lire
		if ($f_idx)
		{
			$f_idx = implode(', ', $f_idx);
			$sql = 'SELECT t.t_title, t.t_last_p_id, t.t_last_p_time, u.u_id, u.u_nickname, u.u_color
					FROM ' . SQL_PREFIX . 'topics t
					LEFT JOIN ' . SQL_PREFIX . 'users u
						ON t.t_last_u_id = u.u_id
					WHERE t.f_id IN (' . $f_idx . ')
						AND t.t_approve = ' . IS_APPROVED . '
					ORDER BY t.t_last_p_time DESC
					LIMIT ' . intval($this->portail_config['nb_messages']);
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				Fsb::$tpl->set_blocks('pm_last_posts', array(
					'TITLE' =>			Parser::title($row['t_title']),
					'NICKNAME' =>		sprintf(Fsb::$session->lang('pm_post_by'), Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color'])),
					'DATE' =>			Fsb::$session->print_date($row['t_last_p_time']),
					'URL' =>			sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;p_id=' . $row['t_last_p_id']) . '#p' . $row['t_last_p_id'],
				));
			}
			Fsb::$db->free($result);
		}
	}
}

/* EOF */