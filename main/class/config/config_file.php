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
 * Gere des fichiers de configuration de type INI (version simplifie).
 * Exemple de fichier :
 * <code>
 *   [categorie]
 *   clef = valeur
 *   clef2 = une autre valeur
 *
 *   [seconde_categorie]
 *   clef = ma_valeur
 * </code>
 */
class Config_file extends Fsb_model
{
	/**
	 * Lit un fichier de configuration
	 *
	 * @param string $filename Nom du fichier
	 * @param bool $use_cache Activation ou non de la mise en cache du fichier
	 * @return array Tableau de configuration
	 */
	public static function read($filename, $use_cache = true)
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
			$cat = null;
			foreach ($file AS $line)
			{
				if (preg_match('#^\[([a-z0-9_]*?)\]$#i', trim($line), $m))
				{
					$cat = $m[1];
					$return[$cat] = array();
				}
				else if (!is_null($cat) && preg_match('#^([a-z0-9_]*?) ?= ?(.*?)$#i', trim($line, "\r\n"), $m))
				{
					$return[$cat][$m[1]] = $m[2];
				}
			}

			$cache->put($hash, $return, $filename, filemtime($filename));
		}

		return ($return);
	}

	/**
	 * Ecrit le contenu d'un tableau dans un fichier de configuration
	 *
	 * @param string $filename Fichier de destination
	 * @param array $ary Tableau a ecrire
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