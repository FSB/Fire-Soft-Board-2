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