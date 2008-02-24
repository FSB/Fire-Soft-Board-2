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

/*
** Permet d'afficher le backup a l'ecran
*/
class Backup_ftp extends Backup
{
	private $fd;

	/*
	** Ouverture de la sortie
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

	/*
	** Ecriture dans la sortie
	*/
	public function write($str)
	{
		fwrite($this->fd, $str);
	}

	/*
	** Fermeture de la sortie
	*/
	public function close()
	{
		fclose($this->fd);
	}
}

/* EOF */