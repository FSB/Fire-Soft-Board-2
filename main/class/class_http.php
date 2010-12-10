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
 * Gestion des headers, meta, redirections, etc ..
 */
class Http extends Fsb_model
{
	/**
	 * Acces a la page via la methode GET
	 */
	const GET = 'get';
	
	/**
	 * Acces a la page via la methode POST
	 */
	const POST = 'post';

	/**
	 * Nettoie les superglobales $_GET, $_POST et $_COOKIE
	 */
	public static function clean_gpc()
	{
		// On supprime toutes les variables crees par la directive register_globals
		// On stripslashes() toutes les variables GPC pour la compatibilite DBAL
		$gpc = array('_GET', '_POST', '_COOKIE');
		$keep_globals = array('_GET', '_POST', '_COOKIE', '_REQUEST', 'GLOBALS', '_SERVER', '_COOKIE', '_ENV', 'debug');
		$magic_quote = (function_exists('get_magic_quotes_gpc'))?(get_magic_quotes_gpc() || get_magic_quotes_runtime()):false;
		$register_globals = ini_get('register_globals');

		if ($register_globals || $magic_quote)
		{
			foreach ($gpc AS $value)
			{
				if ($register_globals)
				{
					foreach ($GLOBALS[$value] AS $k => $v)
					{
						if (!in_array($k, $keep_globals))
						{
							unset($GLOBALS[$k]);
						}
					}
				}
			}
		}

		if ($magic_quote)
		{
			foreach ($gpc AS $value)
			{
				$GLOBALS[$value] = array_map_recursive('stripslashes', $GLOBALS[$value]);
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
	public static function header($key, $value, $replace = null)
	{
		if (is_null($replace))
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
		return (null);
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
	 * @param bool $get_url Si true, ne redirige pas et retourne l'url de redirection
	 * @return string
	 */
	public static function redirect_to($redirect, $get_url = false)
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
					if ($get_url)
					{
						return ('index.' . PHPEXT);
					}
					else
					{
						Http::redirect('index.' . PHPEXT);
					}
				}
				
				if ($get_url)
				{
					return ($redirect);
				}
				else
				{
					Http::redirect($redirect);
				}
			}

			if ($get_url)
			{
				return ($url);
			}
			else
			{
				Http::redirect($url);
			}
		}
		else
		{
			if ($get_url)
			{
				return (ROOT . 'index.' . PHPEXT);
			}
			else
			{
				Http::redirect(ROOT . 'index.' . PHPEXT);
			}
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
		return (isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : null);
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
		if (!Fsb::$cfg->get('use_fsockopen') && $content = @file_get_contents($server . $filename))
		{
			return ($content);
		}
		else
		{
			$tmp = explode('://', $server);
			if (($tmp[0] === 'http' || $tmp[0] === 'https') && count($tmp) === 2)
			{
				$fp = fsockopen($tmp[1], $port, $errno, $errstr, $timeout);
				$out = 'GET ' . $filename . " HTTP/1.1\r\n";
				$out .= 'Host: ' . $tmp[1] . "\r\n";
				$out .= "Connection: Close\r\n\r\n";

				$content = '';
				$header_ended = false;
				fwrite($fp, $out);
				while (!feof($fp))
				{
					$line = stream_get_line($fp, 4096);
					// rem :: header separation = \r\n\r\n
					if (!$header_ended && ($pos = strpos($line, "\r\n\r\n")))
					{
						// strip header
						$header_ended = true;
						$content = substr($line, $pos + 4);
						$content = (false === $content)?'':$content;
					}
					else
					{
						$content .= $line;
					}
				}
				fclose($fp);

				return ($content);
			}
		}
		return (false);
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
		self::header('Cache-Control', 'post-check=0, pre-check=0', false);
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
