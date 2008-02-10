<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/modo/modo_index.php
** | Begin :	20/10/2005
** | Last :		11/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

// On affiche ce module
$show_this_module = TRUE;

/*
** Affiche l'index du panneau de modération
*/
class Page_modo_index extends Fsb_model
{
	/*
	** CONSTRUCTEUR
	*/
	public function __construct()
	{
		Fsb::$tpl->set_file('modo/modo_index.html');

		// On récupère les 5 derniers logs de modération
		$logs = Log::read(Log::MODO, 5);
		foreach ($logs['rows'] AS $log)
		{
			Fsb::$tpl->set_blocks('last_log', array(
				'EVENT' =>		$log['errstr'],
				'NICKNAME' =>	Html::nickname($log['u_nickname'], $log['u_id'], $log['u_color']),
			));
		}

		// On récupère les 5 derniers avertissements
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