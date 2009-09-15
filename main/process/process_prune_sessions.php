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
 * Supprime les sessions perimees
 *
 */
function prune_sessions()
{
	$sql = 'DELETE FROM ' . SQL_PREFIX . 'sessions
				WHERE s_time < ' . (CURRENT_TIME - ONE_HOUR);
	Fsb::$db->query($sql);

	// Optimisation de la table session
	fsb_import('process_prune_database');
	prune_database(array(SQL_PREFIX . 'sessions'));
}
/* EOF */