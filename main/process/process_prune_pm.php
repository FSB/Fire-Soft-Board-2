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
 * Supprime les messages prives trop "vieux" (plus de 6 mois)
 *
 */
function prune_pm()
{
	$sql = 'DELETE FROM ' . SQL_PREFIX . 'mp
			WHERE mp_time < ' . (CURRENT_TIME - (ONE_MONTH * 6)) . '
				AND mp_read = 1 AND (mp_type = ' . MP_INBOX . ' OR mp_type = ' . MP_OUTBOX . ')
				AND mp_to NOT IN(SELECT u_id FROM ' . SQL_PREFIX . 'users WHERE u_auth > ' . MODO . ')';
	Fsb::$db->query($sql);
}
/* EOF */