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
 * Gestion de la mise en cache de donnees
 */
abstract class Cache extends Fsb_model
{
	/**
	 * Verifie si le cache existe
	 *
	 * @param string $hash Clef identifiant la donnee mise en cache
	 * @return bool
	 */
	abstract public function exists($hash);
	
	/**
	 * Recupere une information mise en cache
	 *
	 * @param string $hash Clef identifiant la donnee mise en cache
	 * @return mixed
	 */
	abstract public function get($hash);
	
	/**
	 * Sauve une donne en cache
	 *
	 * @param string $hash clef identifiant la donnee mise en cache
	 * @param mixed $value Donnees a mettre en cache
	 * @param string $comments Commentaires sur la donnee en cache
	 * @param int $timestamp Timestamp de la mise en cache de l'information (par defaut la date actuelle)
	 */
	abstract public function put($hash, $value, $comments = '', $timestamp = null);
	
	/**
	 * Recupere la date de mise en cache
	 *
	 * @param string $hash Clef identifiant la donnee mise en cache
	 * @return int Timestamp
	 */
	abstract public function get_time($hash);
	
	/**
	 * Supprime une donnee en cache
	 *
	 * @param string $hash Clef identifiant la donnee mise en cache
	 */
	abstract public function delete($hash);
	
	/**
	 * Detruit un ensemble de donnees en cache
	 *
	 * @param string $prefix Supprime les clefs commencant par ce prefixe
	 */
	abstract public function destroy($prefix = null);
	
	/**
	 * Nettoie les donnees trop vielles en cache
	 *
	 * @param int $time Duree apres laquelle les fichiers les plus donnees en cache les plus vielles sont supprimes
	 */
	abstract public function garbage_colector($time);
	
	/**
	 * Retourne la liste des clefs mises en cache
	 * 
	 * @return array
	 */
	abstract public function list_keys();

	/**
	 * Verifie si un clearstatcache() a ete execute
	 *
	 * @var bool
	 */
	private static $clearstatcache = false;

	/*
	** Retourne une instance de la classe cache en fonction des donnees passees
	** -----
	** $path ::		Nom du cache (servira pour le chemin du Cache_ftp ou le nom de la table pour le cache Sql)
	** $type ::		Type de cache (ftp | db | eaccelerator | auto)
	** $where ::	Clause WHERE pour la requete de recuperation des donnees du cache
	*/
	/**
	 * Design pattern factory, retourne une instance du cache en fonction des parametres
	 *
	 * @param string $path Nom du cache (servira pour le chemin du Cache_ftp ou le nom de la table pour le cache Sql)
	 * @param string $type Type de cache (ftp | db | eaccelerator | mmcache | apc | auto)
	 * @param string $where Classe WHERE pour le cache SQL pour limiter les informations recherchees
	 * @return Cache
	 */
	public static function factory($path, $type = 'auto', $where = '')
	{
		if (!self::$clearstatcache)
		{
			clearstatcache();
			self::$clearstatcache = true;
		}

		if ($type == 'auto')
		{
			if (extension_loaded('eaccelerator') && function_exists('eaccelerator_put'))
			{
				$type = 'eaccelerator';
			}
			else if (extension_loaded('apc') && function_exists('apc_store'))
			{
				$type = 'apc';
			}
			else if (extension_loaded('mmcache') && function_exists('mmcache_put'))
			{
				$type = 'mmcache';
			}
			else if (!is_writable(ROOT . 'cache/' . $path) || (OS_SERVER == 'windows' && ini_get('safe_mode')))
			{
				$type = 'sql';
			}
			else
			{
				$type = 'ftp';
			}
		}
		$classname = 'Cache_' . $type;

		switch ($classname)
		{
			case 'Cache_ftp' :
				return (new Cache_ftp(ROOT . 'cache/' . $path . '/'));
			break;

			case 'Cache_sql' :
				return (new Cache_sql($path, $where));
			break;

			default :
				return (new $classname());
			break;
		}
	}
}

?>