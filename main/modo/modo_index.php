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
 * Affiche l'index du panneau de moderation
 *
 */
class Page_modo_index extends Fsb_model
{
	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		Fsb::$tpl->set_file('modo/modo_index.html');

		// On recupere les 5 derniers logs de moderation
		$logs = Log::read(Log::MODO, 5);
		foreach ($logs['rows'] AS $log)
		{
			Fsb::$tpl->set_blocks('last_log', array(
				'EVENT' =>		$log['errstr'],
				'NICKNAME' =>	Html::nickname($log['u_nickname'], $log['u_id'], $log['u_color']),
			));
		}

		// On recupere les 5 derniers avertissements
		$sql = 'SELECT u.u_id, u.u_nickname, w.warn_type
				FROM ' . SQL_PREFIX . 'warn w
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON w.u_id = u.u_id
				ORDER BY w.warn_time DESC
				LIMIT 5';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('last_warn', array(
				'EVENT' =>		sprintf(Fsb::$session->lang('modo_index_warn_' . (($row['warn_type'] == WARN_MORE) ? 'more' : 'less')), htmlspecialchars($row['u_nickname'])),
				'U_EVENT' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=warn&amp;mode=show&amp;id=' . $row['u_id']),
			));
		}
		Fsb::$db->free($result);
	}
}

/* EOF */