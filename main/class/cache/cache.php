<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/cache/cache.php
** | Begin :	20/06/2006
** | Last :		11/01/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Classe de mise en cache de données. La classe supporte en natif plusieurs types de cache :
** - Si vous installez Eaccelerator la classe se servira des fonctions fournies par Eaccelerator
** - Si vous installez APC la classe se servira des fonctions fournies par APC
** - Si vous installez Turck MMCache la classe se servira des fonctions fournies par Turck MMCache
** - Si vous possédez les droits d'écriture sur le dossier cache/ la classe se servira d'un cache de fichier
** - Si vous ne possédez rien de tout ce qui est cité ci dessus une mise en cache SQL sera faite
*/
abstract class Cache extends Fsb_model
{
	abstract public function exists($hash);
	abstract public function get($hash);
	abstract public function put($hash, $array, $comments = '', $timestamp = NULL);
	abstract public function get_time($hash);
	abstract public function delete($hash);
	abstract public function destroy($prefix = NULL);
	abstract public function garbage_colector($time);
	abstract public function list_keys();

	private static $clearstatcache = FALSE;

	/*
	** Retourne une instance de la classe cache en fonction des données passées
	** -----
	** $path ::		Nom du cache (servira pour le chemin du Cache_ftp ou le nom de la table pour le cache Sql)
	** $type ::		Type de cache (ftp | db | eaccelerator | auto)
	** $where ::	Clause WHERE pour la requète de récupération des données du cache
	*/
	public static function factory($path, $type = 'auto', $where = '')
	{
		if (!self::$clearstatcache)
		{
			clearstatcache();
			self::$clearstatcache = TRUE;
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