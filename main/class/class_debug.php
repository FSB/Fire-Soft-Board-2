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
 * Gestion du debugage du forum
 */
class Debug extends Fsb_model
{
	/**
	 * Debugage actif
	 *
	 * @var bool
	 */
	public $can_debug = true;
	
	/**
	 * Debugage des requetes actif
	 *
	 * @var unknown_type
	 */
	public $debug_query = false;
	
	/**
	 * Donnees POST
	 *
	 * @var array
	 */
	private $post_ary = array();

	/**
	 * Definit si on affiche le resultat des templates
	 *
	 * @var bool
	 */
	public $show_output = true;

	/**
	 * Cache des URL a generer
	 *
	 * @var string
	 */
	private $url_begin = '';
	
	/**
	 * Cache des URL a generer
	 *
	 * @var string
	 */
	private $url_end = '';

	/**
	 * Contient les differents temps les benchmarks
	 *
	 * @var array
	 */
	private $data = array();

	/**
	 * Temps final
	 *
	 * @var int
	 */
	public $end = 0;

	/**
	 * Temps de depart
	 *
	 * @var int
	 */
	public $start = 0;
	
	/**
	 * Constructeur, initialise le benchmark et les informations sur la page
	 */
	public function __construct()
	{
		// Benchmark de depart
		$this->start = $this->get_time();

		$this->can_debug = (!(error_reporting() ^ E_ALL)) ? true : false;
		$this->debug_query = ($this->can_debug && isset($_GET['debug_query'])) ? true : false;

		$this->show_output = !$this->debug_query;
	}

	/**
	 * Initialise la page en recuperant la methode d'acces, ainsi que le tableau $_POST si la methode est post
	 */
	public function request_vars()
	{
		if (!$this->can_debug)
		{
			return ;
		}
		
		$this->post_ary = (Http::request('post_ary') && Http::method() == Http::POST) ? unserialize(urldecode(Http::request('post_ary'))) : array();
		if (!is_array($this->post_ary))
		{
			$this->post_ary = array();
		}
		$_POST = array_merge($_POST, $this->post_ary);
	}

	/**
	 * Cree une URL pour acceder a la page de debugage
	 *
	 * @return string
	 */
	public function debug_url()
	{
		if ($this->url_begin == '')
		{
			$request_method = (isset($_SERVER['REQUEST_METHOD'])) ? $_SERVER['REQUEST_METHOD'] : 'GET';
			$request_uri = (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : getenv('PHP_SELF');
			if (!empty($_SERVER['QUERY_STRING']))
			{
				$request_uri .= '?' . $_SERVER['QUERY_STRING'];
			}

			$this->url_begin = str_replace('&', '&amp;', $request_uri) . ((strpos($request_uri, '?') != false) ? '&amp;' : '?');
			$this->url_end = '&amp;method=' . $request_method . (($request_method == 'POST') ? '&amp;post_ary=' . urlencode(serialize($_POST)) : '');
		}

		return ($this->url_begin . 'debug_query=true' . $this->url_end);
	}

	/**
	 * Cree un marqueur pour le benchmark
	 *
	 * @param string $name Nom du marqueur
	 */
	public function mark($name)
	{
		$this->data[] = array('name' => $name, 'time' => $this->get_time());
	}

	/**
	 * Affiche le resultat du benchmark
	 */
	public function finish()
	{
		$this->end = microtime( true );
		$total = $this->end - $this->start;

		echo '
			<table width="800" align="center" style="border: solid 1px #000000;">
				<tr>
					<td style="background-color: #cccccc; font-weight: bold; text-align: center;" colspan="4">Benchmark</td>
				</tr>
				<tr>
					<td style="background-color: #dddddd; width: 400px; text-align: center;" colspan="2">Temps d\'execution total :</td>
					<td style="background-color: #eeeeee; text-align: center;" colspan="2">' . ($total) . '</td>
				</tr>
				<tr>
					<td style="background-color: #cccccc; font-weight: bold; text-align: center;" colspan="4">Marqueurs</td>
				</tr>
				<tr>
					<td style="background-color: #dddddd; width: 200px; text-align: center; font-weight: bold;">Nom du marqueur</td>
					<td style="background-color: #eeeeee; width: 200px; text-align: center; font-weight: bold;">Temps du marqueur</td>
					<td style="background-color: #eeeeee; width: 200px; text-align: center; font-weight: bold;">Temps passe</td>
					<td style="background-color: #dddddd; width: 200px; text-align: center; font-weight: bold;">Pourcentage d\'execution</td>
				</tr>
				<tr>
					<td style="background-color: #dddddd; width: 200px; text-align: center;">Temps de depart :</td>
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
					<td style="background-color: #dddddd; width: 200px; text-align: center;">Temps d\'arrive :</td>
					<td style="background-color: #eeeeee; width: 200px; text-align: center;">' . ($this->end) . '</td>
					<td style="background-color: #eeeeee; width: 200px; text-align: center;">' . ($total) . '</td>
					<td style="background-color: #dddddd; width: 200px; text-align: center;">100%</td>
				</tr>
			</table>
		';
	}

	/**
	 * Retourne la memoire utilisee actuellement par le script
	 *
	 * @return int
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

		return (null);
	}

	public function get_time()
	{
		return microtime(true);
	}
}

/* EOF */