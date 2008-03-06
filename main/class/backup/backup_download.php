<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/backup/backup_download.php
** | Begin :	11/10/2007
** | Last :		11/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/**
 * Telechargement du backup
 */
class Backup_download extends Backup
{
	/**
	 * @see Backup::open()
	 */
	public function open($filename)
	{
		header('Content-Type: text/x-sql');
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
	}

	/**
	 * @see Backup::write()
	 */
	public function write($str)
	{
		echo $str;
	}

	/**
	 * @see Backup::close()
	 */
	public function close()
	{
		exit;
	}
}

/* EOF */