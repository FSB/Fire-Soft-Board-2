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