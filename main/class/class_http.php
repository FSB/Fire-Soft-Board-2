<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_http.php
** | Begin :	19/06/2007
** | Last :		03/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion des headers, meta, redirections, etc ..
*/
class Http extends Fsb_model
{
	// Methode d'acces a la page
	const GET = 'get';
	const POST = 'post';

	/*
	** Nettoie les variables GET, POST et COOKIE
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

	/*
	** Envoie un header HTTP
	** -----
	** $key ::		Clef a envoyer
	** $value ::	Valeur
	** $replace ::	Ecraser les precedentes valeurs
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

	/*
	** Methode d'acces a la page
	*/
	public static function method()
	{
		if (isset($_SERVER['REQUEST_METHOD']) && strtolower($_SERVER['REQUEST_METHOD']) == 'post')
		{
			return (self::POST);
		}
		return (self::GET);
	}

	/*
	** Recupere une variable transmise a la page via les super globales
	** -----
	** $key ::		Clef de la variable
	** $mode ::		Liste de super globales dans lesquelles on va rechercher si la clef existe.
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

	/*
	** Redirige automatiquement la page.
	** -----
	** $url ::	URL de destination
	** $time ::	Duree avant la redirection, si inferieur a 0 on ne redirige pas, si vaut 0 on redirige
	**			instantanement via un header, sinon on redirige via une balise META refresh.
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

	/*
	** Redirige a partir d'une information precise
	** -----
	** $redirect ::	Information pour la redirection (vers une page du forum, ou locale au site web)
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
					Http::redirect('index.php');
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

	/*
	** Ajoute un tag META HTML sur le page.
	** -----
	** $name ::		Nom de la balise
	** $attr ::		Attributs de la balise META
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

	/*
	** Ajoute des relation suivants / precedents / premiere page
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

	/*
	** Envoie un cookie au client
	** -----
	** $name ::		Nom du cookie
	** $value ::	Valeur du cookie
	** $time ::		Temps d'expiration
	*/
	public static function cookie($name, $value, $time)
	{
		setcookie(Fsb::$cfg->get('cookie_name') . $name, $value, $time, Fsb::$cfg->get('cookie_path'), Fsb::$cfg->get('cookie_dommain'), Fsb::$cfg->get('cookie_secure'));
	}

	/*
	** Renvoie la valeur d'un cookie du forum
	** -----
	** $name ::		Nom du cookie
	*/
	public static function getcookie($name)
	{
		$cookie_name = Fsb::$cfg->get('cookie_name') . $name;
		return (isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : NULL);
	}

	/*
	** Renvoie le contenu d'un fichier sur le serveur distant
	** -----
	** $server ::		Adresse du serveur
	** $filename ::		Fichier dont on veut le contenu
	** $timeout ::		Temps de connexion maximal
	*/
	public static function get_file_on_server($server, $filename, $timeout = 5, $port = 80)
	{
		if ($content = @file_get_contents($server . $filename))
		{
			return ($content);
		}
		return (FALSE);
	}

	/*
	** Utilisation de la compression GZIP ?
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

	/*
	** Les pages ne doivent pas etres mises en cache
	*/
	public static function no_cache()
	{
		self::header('Cache-Control', 'post-check=0, pre-check=0', FALSE);
		self::header('Expires', '0');
		self::header('Pragma', 'no-cache');
	}

	/*
	** Lance le telechargement d'un fichier
	** -----
	** $filename ::		Nom du fichier
	** $content ::		Contenu du fichier
	** $type ::			Type mime du fichier
	*/
	public static function download($filename, &$content, $type = 'text/x-delimtext')
	{
		self::header('Pragma', 'no-cache');
		self::header('Content-Type', $type . '; name="' . $filename . '"');
		self::header('Content-disposition', 'inline; filename=' . $filename);

		echo $content;
		exit;
	}
}

/* EOF */