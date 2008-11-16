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