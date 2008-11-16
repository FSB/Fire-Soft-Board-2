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
 * Recupere les donnees du backup
 */
class Backup_get extends Backup
{
	/**
	 * Contenu a retourner
	 *
	 * @var string
	 */
	private $_return = '';

	/**
	 * @see Backup::open()
	 */
	public function open($filename)
	{
		$this->_return = '';
	}

	/**
	 * @see Backup::write()
	 */
	public function write($str)
	{
		$this->_return .= $str;
	}

	/**
	 * @see Backup::close()
	 */
	public function close()
	{
		return ($this->_return);
	}
}

/* EOF */