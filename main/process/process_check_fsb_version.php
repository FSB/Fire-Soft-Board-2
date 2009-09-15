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
 * Verifie la derniere version du forum en date, et la stoque dans la table de configuration
 *
 */
function check_fsb_version()
{
	if ($content = Http::get_file_on_server(FSB_REQUEST_SERVER, FSB_REQUEST_VERSION, 10))
	{
		@list($last_version, $url, $level) = explode("\n", $content);
		if (Fsb::$cfg->get('fsb_last_version') != $last_version)
		{
			Fsb::$cfg->update('fsb_last_version', $last_version);
		}
	}
}
/* EOF */