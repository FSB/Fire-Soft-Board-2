<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

$GLOBALS['use_register_shutdown'] = false;

/**
 * Recalcul des donnees en cache dans la configuration
 *
 */
function prune_config()
{
	$list_config = array('total_posts', 'total_topics', 'total_users', 'last_user');
	foreach ($list_config AS $value)
	{
		if ($value == 'last_user')
		{
			// Pour le calcul du dernier membre on doit calcul son nickname et son ID
			$sql = 'SELECT u_id, u_nickname, u_color
					FROM ' . SQL_PREFIX . 'users
					ORDER BY u_joined DESC
					LIMIT 1';
			$data = Fsb::$db->request($sql);

			Fsb::$cfg->update('last_user_id', $data['u_id'], false);
			Fsb::$cfg->update('last_user_login', $data['u_nickname'], false);
			Fsb::$cfg->update('last_user_color', $data['u_color'], false);
		}
		else
		{
			$sql = 'SELECT COUNT(*) AS total
					FROM ' . SQL_PREFIX . substr($value, 6);
			$total = Fsb::$db->get($sql, 'total');

			// Pour total_users on supprime 1 pour l'invite
			if ($value == 'total_users')
			{
				$total--;
			}

			Fsb::$cfg->update($value, $total, false);
		}
	}
	Fsb::$cfg->destroy_cache();
}
/* EOF */