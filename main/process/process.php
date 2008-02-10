<?php
/*
** +---------------------------------------------------+
** | Name :			~/main/process/process.php
** | Begin :		27/09/2005
** | Last :			10/02/2008
** | User :			Genova
** | Project :		Fire-Soft-Board 2 - Copyright FSB group
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Execute les procédures programmées du forum et envoie les messages en attente. Cette page doit être appelée sous forme
** d'image, pour que les fonctions s'executent en background.
*/

define('PHPEXT', substr(strrchr(__FILE__,'.'), 1));
define('ROOT', '../../');
define('FORUM', TRUE);
include(ROOT . 'main/start.' . PHPEXT);

$register_shutdown = (function_exists('register_shutdown_function')) ? TRUE : FALSE;

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
		$use_register_shutdown = TRUE;
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

// Mise à jour des taches effectuées
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