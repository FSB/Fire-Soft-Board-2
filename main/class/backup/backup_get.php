<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/backup/backup_get.php
** | Begin :	11/10/2007
** | Last :		11/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
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