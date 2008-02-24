<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/process/process_check_fsb_version.php
** | Begin :	11/07/2007
** | Last :		09/08/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Verifie la derniere version du forum en date, et la stoque dans la table de configuration
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