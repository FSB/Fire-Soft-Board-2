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

/*
** Permet d'afficher le backup à l'écran
*/
class Backup_get extends Backup
{
	private $_return = '';

	/*
	** Ouverture de la sortie
	*/
	public function open($filename)
	{
		$this->_return = '';
	}

	/*
	** Ecriture dans la sortie
	*/
	public function write($str)
	{
		$this->_return .= $str;
	}

	/*
	** Fermeture de la sortie
	*/
	public function close()
	{
		return ($this->_return);
	}
}

/* EOF */