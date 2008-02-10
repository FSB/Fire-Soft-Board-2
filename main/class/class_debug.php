<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_debug.php
** | Begin :	27/08/2005
** | Last :		07/01/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Classe développée dans le but d'aider à débuguer le forum
*/
class Debug extends Fsb_model
{
	public $can_debug = TRUE;
	public $debug_query = FALSE;
	public $debug_vars = FALSE;
	private $method = 'get';
	private $post_ary = array();

	// Définit si on affiche le résultat des templates
	public $show_output = TRUE;

	// Cache des URL de débugage
	private $url_begin = '';
	private $url_end = '';

	// Contient les différents temps pour les étapes
	private $data = array();

	// Temps final
	public $end = 0;

	// Temps de départ
	public $start = 0;
	
	/*
	** Constructeur de la classe Debug()
	*/
	public function __construct()
	{
		// Benchmark de départ
		$this->start = $this->get_time();

		$this->can_debug = (!(error_reporting() ^ E_ALL)) ? TRUE : FALSE;
		$this->debug_query = ($this->can_debug && isset($_GET['debug_query'])) ? TRUE : FALSE;
		$this->debug_vars = ($this->can_debug && isset($_GET['debug_vars'])) ? TRUE : FALSE;

		if ($this->debug_query || $this->debug_vars)
		{
			$this->show_output = FALSE;
		}
		else
		{
			$this->show_output = TRUE;
		}
	}
	
	/*
	** Initialise la page en récupérant la méthode d'accès, ainsi que le tableau $_POST si la méthode est post
	*/
	public function request_vars()
	{
		if (!$this->can_debug)
		{
			return ;
		}

		$this->method = (Http::request('method')) ? strtolower(Http::request('method')) : 'get';
		
		$this->post_ary = (Http::request('post_ary') && $this->method == 'post') ? unserialize(urldecode(Http::request('post_ary'))) : array();
		if (!is_array($this->post_ary))
		{
			$this->post_ary = array();
		}
		$_POST = array_merge($_POST, $this->post_ary);
	}
	
	/*
	** Créé une URL pour accéder à la page de débugage
	*/
	public function debug_url($mode)
	{
		if ($this->url_begin == '')
		{
			$request_method = (isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : 'GET';
			$request_uri = (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : getenv('PHP_SELF');
			$request_uri .= '?' . $_SERVER['QUERY_STRING'];

			$this->url_begin = str_replace('&', '&amp;', $request_uri) . ((strpos($request_uri, '?') != FALSE) ? '&amp;' : '?');
			$this->url_end = '&amp;method=' . $request_method . (($request_method == 'POST') ? '&amp;post_ary=' . urlencode(serialize($_POST)) : '');
		}

		switch ($mode)
		{
			case 'query' :
				return ($this->url_begin . 'debug_query=true' . $this->url_end);

			case 'vars' :
				return ($this->url_begin . 'debug_vars=true' . $this->url_end);
		}
	}

	/*
	** Créé un marqueur qui retient le temps écoulé.
	** -----
	** $name :: Nom du marqueur.
	*/
	public function mark($name)
	{
		$this->data[] = array('name' => $name, 'time' => $this->get_time());
	}

	/*
	** Sauvegarde le temps final et affiche tous les temps des marqueurs,
	** avec un certain nombre de statistique.
	*/
	public function finish()
	{
		$this->end = $this->get_time();
		$total = $this->end - $this->start;

		echo '
			<table width="800" align="center" style="border: solid 1px #000000;">
				<tr>
					<td style="background-color: #cccccc; font-weight: bold; text-align: center;" colspan="4">Benchmark</td>
				</tr>
				<tr>
					<td style="background-color: #dddddd; width: 400px; text-align: center;" colspan="2">Temps d\'éxécution total :</td>
					<td style="background-color: #eeeeee; text-align: center;" colspan="2">' . ($total) . '</td>
				</tr>
				<tr>
					<td style="background-color: #cccccc; font-weight: bold; text-align: center;" colspan="4">Marqueurs</td>
				</tr>
				<tr>
					<td style="background-color: #dddddd; width: 200px; text-align: center; font-weight: bold;">Nom du marqueur</td>
					<td style="background-color: #eeeeee; width: 200px; text-align: center; font-weight: bold;">Temps du marqueur</td>
					<td style="background-color: #eeeeee; width: 200px; text-align: center; font-weight: bold;">Temps passé</td>
					<td style="background-color: #dddddd; width: 200px; text-align: center; font-weight: bold;">Pourcentage d\'éxécution</td>
				</tr>
				<tr>
					<td style="background-color: #dddddd; width: 200px; text-align: center;">Temps de départ :</td>
					<td style="background-color: #eeeeee; width: 200px; text-align: center;">' . ($this->start) . '</td>
					<td style="background-color: #eeeeee; width: 200px; text-align: center;">' . (0) . '</td>
					<td style="background-color: #dddddd; width: 200px; text-align: center;">0%</td>
				</tr>
		';
		foreach ($this->data AS $v)
		{
			$time_added = $v['time'] - $this->start;
			$percent = ($time_added / $total) * 100;
			echo '
				<tr>
					<td style="background-color: #dddddd; width: 200px; text-align: center;">Marqueur ' . $v['name'] . ' :</td>
					<td style="background-color: #eeeeee; width: 200px; text-align: center;">' . ($v['time']) . '</td>
					<td style="background-color: #eeeeee; width: 200px; text-align: center;">' . ($time_added) . '</td>
					<td style="background-color: #dddddd; width: 200px; text-align: center;">' . round($percent) . '%</td>
				</tr>
			';
		}
		echo '
				<tr>
					<td style="background-color: #dddddd; width: 200px; text-align: center;">Temps d\'arrivé :</td>
					<td style="background-color: #eeeeee; width: 200px; text-align: center;">' . ($this->end) . '</td>
					<td style="background-color: #eeeeee; width: 200px; text-align: center;">' . ($total) . '</td>
					<td style="background-color: #dddddd; width: 200px; text-align: center;">100%</td>
				</tr>
			</table>
		';
	}

	/*
	** Renvoie un temps pour le benchmark.
	*/
	public function get_time()
	{
		$ary = explode(' ', microtime());
		return ($ary[0] + $ary[1]);
	}

	/*
	** Retourne la mémoire utilisée par le script
	*/
	public function memory()
	{
		if (function_exists('memory_get_usage'))
		{
			return (memory_get_usage());
		}
		else if (OS_SERVER == 'windows')
		{
			$current_pid = getmypid();
			exec('tasklist /FO CSV', $out);
			foreach ($out AS $line)
			{
				$split = explode(',', $line);
				if (count($split) > 1)
				{
					list($cmd, $pid, $sess, $nb, $memory) = $split;
					$pid = intval(substr($pid, 1, -1));
					if ($pid == $current_pid)
					{
						return (intval(preg_replace('#[^0-9]#', '', $memory)));
						break;
					}
				}
			}
		}

		return (NULL);
	}
}

/* EOF */