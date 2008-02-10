<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/config/config_file.php
** | Begin :	29/11/2007
** | Last :		24/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Permet de gérer des fichiers de configuration ayant ce format :
**
**   [categorie]
**   clef = valeur
**   clef2 = une autre valeur
**
**   [seconde_categorie]
**   clef = ma_valeur
*/
class Config_file extends Fsb_model
{
	/*
	** Lit un fichier de configuration d'un thème
	** -----
	** $filename ::		Nom du fichier
	** $use_cache ::	Activation ou non de la mise en cache du fichier
	*/
	public static function read($filename, $use_cache = TRUE)
	{
		$hash = 'cfg_' . md5($filename);
		$cache = Cache::factory('tpl');
		if ($cache->exists($hash) && filemtime($filename) == $cache->get_time($hash))
		{
			$return = $cache->get($hash);
		}
		else
		{
			$file = file($filename);
			$return = array();
			$cat = NULL;
			foreach ($file AS $line)
			{
				if (preg_match('#^\[([a-z0-9_]*?)\]$#i', trim($line), $m))
				{
					$cat = $m[1];
					$return[$cat] = array();
				}
				else if ($cat !== NULL && preg_match('#^([a-z0-9_]*?) ?= ?(.*?)$#i', trim($line, "\r\n"), $m))
				{
					$return[$cat][$m[1]] = $m[2];
				}
			}

			$cache->put($hash, $return, $filename, filemtime($filename));
		}

		return ($return);
	}

	/*
	** Ecrit le contenu d'un tableau dans un fichier de configuration
	** -----
	** $filename ::	Fichier cible
	** $ary ::		Tableau à écrire
	*/
	public static function write($filename, $ary)
	{
		if (!$fd = @fopen($filename, 'w'))
		{
			Display::message(sprintf(Fsb::$session->lang('fopen_error'), $filename));
		}

		foreach ($ary AS $cat => $data)
		{
			fwrite($fd, "[$cat]\n");
			foreach ($data AS $key => $value)
			{
				fwrite($fd, "$key = $value\n");
			}
			fwrite($fd, "\n");
		}
		fclose($fd);
	}
}

/* EOF */