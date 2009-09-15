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
 * Execute les procedures programmees du forum et envoie les messages en attente. Cette page doit etre appelee sous forme d'image, pour que les fonctions s'executent en background.
 *
 */

define('PHPEXT', substr(strrchr(__FILE__,'.'), 1));
define('ROOT', '../../');
define('FORUM', true);
include(ROOT . 'main/start.' . PHPEXT);

$register_shutdown = (function_exists('register_shutdown_function')) ? true : false;

$sql = 'SELECT *
		FROM ' . SQL_PREFIX . 'process';
$result = Fsb::$db->query($sql, 'process_');
$update_id = array();
while ($row = Fsb::$db->row($result))
{
	if ($row['process_step_timestamp'] > 0 && $row['process_last_timestamp'] < CURRENT_TIME - $row['process_step_timestamp'])
	{
		$function = $row['process_function'];
		$update_id[] = $row['process_id'];

		// On execute la fonction
		$use_register_shutdown = true;
		fsb_import('process_' . $function);
		if ($register_shutdown && $use_register_shutdown)
		{
			register_shutdown_function($function);
		}
		else
		{
			call_user_func($function);
		}
	}
}
Fsb::$db->free($result);

// Mise a jour des taches effectuees
if ($update_id)
{
	Fsb::$db->update('process', array(
		'process_last_timestamp' =>	CURRENT_TIME,
	), 'WHERE process_id IN (' . implode(', ', $update_id) . ')');
	Fsb::$db->destroy_cache('process_');
}

// Envoie des messages en attente
$notify = new Notify();
$notify->send_queue();

// On affiche un pixel transparent, code repris des commentaires de la fonction
// register_shutdown_function sur www.php.net
Http::header('Content-length', '85');
Http::header('Content-type', 'image/gif');
print base64_decode(
	'R0lGODlhAQABALMAAAAAAIAAAACAA'.
	'ICAAAAAgIAAgACAgMDAwICAgP8AAA'.
	'D/AP//AAAA//8A/wD//wBiZCH5BAE'.
	'AAA8ALAAAAAABAAEAAAQC8EUAOw=='
);
flush();
exit;

/* EOF */