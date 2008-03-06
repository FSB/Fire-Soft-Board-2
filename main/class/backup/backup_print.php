<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/backup/backup_print.php
** | Begin :	11/10/2007
** | Last :		11/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/**
 * Affiche le backup
 */
class Backup_print extends Backup
{
	/**
	 * @see Backup::open()
	 */
	public function open($filename)
	{
		header('Content-Type: text/plain');
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