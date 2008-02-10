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

/*
** Permet d'afficher le backup à l'écran
*/
class Backup_print extends Backup
{
	/*
	** Ouverture de la sortie
	*/
	public function open($filename)
	{
		header('Content-Type: text/plain');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
	}

	/*
	** Ecriture dans la sortie
	*/
	public function write($str)
	{
		echo $str;
	}

	/*
	** Fermeture de la sortie
	*/
	public function close()
	{
		exit;
	}
}

/* EOF */