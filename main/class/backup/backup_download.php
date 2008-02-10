<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/backup/backup_download.php
** | Begin :	11/10/2007
** | Last :		11/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Permet de télécharger le backup
*/
class Backup_download extends Backup
{
	/*
	** Ouverture de la sortie
	*/
	public function open($filename)
	{
		header('Content-Type: text/x-sql');
		header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
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