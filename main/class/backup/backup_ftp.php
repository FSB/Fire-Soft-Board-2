<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/backup/backup_ftp.php
** | Begin :	11/10/2007
** | Last :		11/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/**
 * Sauve le backup dans un fichier
 */
class Backup_ftp extends Backup
{
	/**
	 * Descripteur de fichier
	 *
	 * @var resource
	 */
	private $fd;

	/**
	 * @see Backup::open()
	 */
	public function open($filename)
	{
		$dir = ROOT . 'cache/sql_backup/';
		if (!is_writable($dir) && !@chmod($dir, 0777))
		{
			trigger_error('Le dossier ' . $dir . ' doit etre chmode en 777', FSB_ERROR);
		}

		$this->fd = fopen($dir . $filename, 'w');
	}

	/**
	 * @see Backup::write()
	 */
	public function write($str)
	{
		fwrite($this->fd, $str);
	}

	/**
	 * @see Backup::close()
	 */
	public function close()
	{
		fclose($this->fd);
	}
}

/* EOF */