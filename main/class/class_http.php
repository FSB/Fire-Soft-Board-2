<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_http.php
** | Begin :	19/06/2007
** | Last :		02/03/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/**
 * Gestion des headers, meta, redirections, etc ..
 */
class Http extends Fsb_model
{
	/**
	 * Acces a la page via la methode GET
	 */
	
	/**
	 * Acces a la page via la methode POST
	 */
	const GET = 'get';
	const POST = 'post';

	/**
	 * Nettoie les superglobales $_GET, $_POST et $_COOKIE
	 */
	public static function clean_gpc()
	{
		// On supprime toutes les variables crees par la directive register_globals
		// On stripslashes() toutes les variables GPC pour la compatibilite DBAL
		$gpc = array('_GET', '_POST', '_COOKIE');
		$magic_quote = (get_magic_quotes_gpc()) ? TRUE : FALSE;
		$register_globals = TRUE;//(ini_get('register_globals')) ? TRUE : FALSE;

		if ($register_globals || $magic_quote)
		{
			foreach ($gpc AS $value)
			{
				if ($register_globals)
				{
					foreach ($GLOBALS[$value] AS $k => $v)
					{
						if ($k != 'debug')
						{
							unset($GLOBALS[$k]);
						}
					}
				}
				
				if ($magic_quote && isset($GLOBALS[$value]))
				{
					$GLOBALS[$value] = array_map_recursive('stripslashes', $GLOBALS[$value]);
				}
			}
		}
	}

	/**
	 * Envoie un header HTTP
	 *
	 * @param string $key Clef a envoyer
	 * @param string $value Valeur
	 * @param bool $replace Ecraser les precedentes valeurs
	 */
	public static function header($key, $value, $replace = NULL)
	{
		if ($replace === NULL)
		{
			header($key . ': ' . $value);
		}
		else
		{
			header($key . ': ' . $value, $replace);
		}
	}

	/**
	 * Recupere la methode d'acces a la page
	 *
	 * @return string self::GET ou self::POST
	 */
	public static function method()
	{
		if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post')
		{
			return (self::POST);
		}
		return (self::GET);
	}

	/**
	 * Recupere une variable transmise a la page via les super globales
	 * Exemple :
	 * <code>
	 *   $var = Http::request('var', 'post');
	 *   $var2 = Http::request('var2', 'post|get|cookie');
	 * </code>
	 *
	 * @param string $key Clef de la variable
	 * @param string $mode Liste de super globales dans lesquelles on va rechercher si la clef existe.
	 * @return mixed
	 */
	public static function request($key, $mode = 'get|post')
	{
		$split = explode('|', $mode);
		foreach ($split AS $gl)
		{
			$gl = '_' . strtoupper($gl);
			if (isset($GLOBALS[$gl][$key]))
			{
				return ($GLOBALS[$gl][$key]);
			}
		}
		return (NULL);
	}

	/**
	 * Fait une redirection automatique
	 *
	 * @param string $url URL de destination
	 * @param int $time Duree avant la redirection. Si la duree est inferieure a 0 on ne redirige pas. Si la duree est superieure a 0 on fait une direction a retardement avec un META refresh
	 */
	public static function redirect($url, $time = 0)
	{
		if ($time < 0)
		{
			return ;
		}
		else if ($time == 0)
		{
			self::header('location', str_replace('&amp;', '&', sid($url)));
			exit;
		}
		else
		{
			self::add_meta('meta', array(
				'http-equiv' =>	'refresh',
				'content' =>	$time . ';url=' . sid($url),
			));
		}
	}

	/**
	 * Redirige a partir d'une information precise
	 *
	 * @param string $redirect Information pour la redirection (vers une page du forum, ou locale au site web)
	 */
	public static function redirect_to($redirect)
	{
		if ($redirect)
		{
			// Redirection forum
			if (file_exists(ROOT . 'main/forum/forum_' . $redirect . '.' . PHPEXT))
			{
				$url = 'index.' . PHPEXT . '?p=' . urlencode($redirect);
				foreach ($_GET AS $key => $value)
				{
					if ($key != 'p' && $key != 'redirect' && $key != 'sid')
					{
						$url .= '&' . $key . '=' . urlencode($value);
					}
				}
			}
			// Redirection locale au site web
			else
			{
				if (preg_match('#^\s*[a-zA-Z0-9]+://#i', $redirect))
				{
					// URL externe interdites pour des raisons de securite
					Http::redirect('index.' . PHPEXT);
				}
				Http::redirect($redirect);
			}
			Http::redirect($url);
		}
		else
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}
	}

	/**
	 * Ajoute un tag dans le header template
	 *
	 * @param string $name Nom de la balise
	 * @param array $attr Attributs de la balise
	 */
	public static function add_meta($name, $attr)
	{
		Fsb::$tpl->set_blocks('meta', array(
			'NAME' =>	$name
		));

		foreach ($attr AS $key => $value)
		{
			Fsb::$tpl->set_blocks('meta.attr', array(
				'KEY' =>	$key,
				'VALUE' =>	str_replace('"', '&quot;', $value),
			));
		}
	}

	/**
	 * Ajoute des relations suivants / precedent / premiere ou derniere page au navigateur
	 *
	 * @param int $current Page actuelle
	 * @param int $total Nombre total de page
	 * @param string $url URL vers laquelle pointer la navigation
	 */
	public static function add_relation($current, $total, $url)
	{
		if ($current > 1)
		{
			self::add_meta('link', array('rel' => 'prev', 'href' => sid($url . '&amp;page=' . ($current - 1))));
			self::add_meta('link', array('rel' => 'first', 'href' => sid($url . '&amp;page=1')));
		}

		if ($current < $total)
		{
			self::add_meta('link', array('rel' => 'next', 'href' => sid($url . '&amp;page=' . ($current + 1))));
			self::add_meta('link', array('rel' => 'last', 'href' => sid($url . '&amp;page=' . $total)));
		}
	}

	/**
	 * Envoie un cookie au navigateur
	 *
	 * @param string $name Nom du cookie
	 * @param string $value Valeur du cookie
	 * @param int $time Temps d'expiration
	 */
	public static function cookie($name, $value, $time)
	{
		setcookie(Fsb::$cfg->get('cookie_name') . $name, $value, $time, Fsb::$cfg->get('cookie_path'), Fsb::$cfg->get('cookie_dommain'), Fsb::$cfg->get('cookie_secure'));
	}

	/**
	 * Recupere la valeur d'un cookie
	 *
	 * @param string $name Nom du cookie
	 * @return string
	 */
	public static function getcookie($name)
	{
		$cookie_name = Fsb::$cfg->get('cookie_name') . $name;
		return (isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : NULL);
	}

	/**
	 * Recupere le contenu d'un fichier sur un serveur distant
	 *
	 * @param string $server Adresse du serveur
	 * @param string $filename Fichier dont on veut le contenu
	 * @return string
	 */
	public static function get_file_on_server($server, $filename, $timeout = 5, $port = 80)
	{
		if (Fsb::$cfg->get('use_fsockopen') && $content = @file_get_contents($server . $filename))
		{
			return ($content);
		}
		return (FALSE);
	}

	/**
	 * Verifie si la compression GZIP est supportee, et active la bufferisation
	 */
	public static function check_gzip()
	{
		if (Fsb::$cfg->get('use_gzip') && extension_loaded('zlib') && isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false || strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'deflate') !== false) && ini_get('zlib.output_compression') == 'Off')
		{
			ob_start('ob_gzhandler');
		}
		else
		{
			ob_start();
		}
	}

	/**
	 * Desactive la mise en cache des pages par le navigateur
	 */
	public static function no_cache()
	{
		self::header('Cache-Control', 'post-check=0, pre-check=0', FALSE);
		self::header('Expires', '0');
		self::header('Pragma', 'no-cache');
	}

	/**
	 * Lance le telechargement d'un fichier
	 *
	 * @param string $filename Nom du fichier
	 * @param string $content Contenu du fichier
	 * @param string $type Type mime du fichier
	 */
	public static function download($filename, &$content, $type = 'text/x-delimtext')
	{
		self::header('Pragma', 'no-cache');
		self::header('Content-Type', $type . '; name="' . $filename . '"');
		self::header('Content-disposition', 'inline; filename="' . $filename . '"');

		echo $content;
		exit;
	}
}

/* EOF */