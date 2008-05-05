<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/Artichow/img/gd_stats.php
** | Begin :	22/09/2006
** | Last :		14/02/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Affiche la progression du nombre de messages par periode
** Utilisation du framework Artichow (www.artichow.org)
*/

define('PHPEXT', substr(strrchr(__FILE__,'.'), 1));
define('ROOT', '../');
define('FORUM', TRUE);
include(ROOT . 'main/start.' . PHPEXT);

Fsb::$session->start('', FALSE);

// On recupere les variables dans l'URL
$img_type =		Http::request('img');
$begin_time =	intval(Http::request('begin'));
$end_time = 	intval(Http::request('end'));
$use_current =	intval(Http::request('current'));

if (Fsb::$session->auth() < MODOSUP)
{
	trigger_error(Fsb::$session->lang('not_allowed'));
}

$args = array();
switch ($img_type)
{
	case 'posts' :
		$state = ($use_current) ? ONE_DAY : ONE_MONTH;

		// On recupere le nombre de messages par jour
		$sql = 'SELECT p_time, COUNT(p_id) AS total_post
				FROM ' . SQL_PREFIX . 'posts
				WHERE p_time >= ' . $begin_time . '
					AND p_time <= ' . $end_time . '
				GROUP BY p_time / ' . $state . '
				ORDER BY p_time';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$y = date('Y', $row['p_time']);
			$n = date(($use_current) ? 'd' : 'n', $row['p_time']);
			if (!isset($args[$y]))
			{
				$args[$y] = array();
			}

			if (!isset($args[$y][$n]))
			{
				$args[$y][$n] = 0;
			}

			$args[$y][$n] += $row['total_post'];
		}
		Fsb::$db->free($result);
	break;

	case 'topics' :
		$state = ($use_current) ? ONE_DAY : ONE_MONTH;

		// On recupere le nombre de messages par jour
		$sql = 'SELECT p.p_time, COUNT(p.p_id) AS total_topic
				FROM ' . SQL_PREFIX . 'topics t
				LEFT JOIN ' . SQL_PREFIX . 'posts p
					ON p.p_id = t.t_first_p_id
				WHERE p.p_time >= ' . $begin_time . '
					AND p.p_time <= ' . $end_time . '
				GROUP BY p.p_time / ' . $state . '
				ORDER BY p.p_time';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$y = date('Y', $row['p_time']);
			$n = date(($use_current) ? 'd' : 'n', $row['p_time']);
			if (!isset($args[$y]))
			{
				$args[$y] = array();
			}

			if (!isset($args[$y][$n]))
			{
				$args[$y][$n] = 0;
			}

			$args[$y][$n] += $row['total_topic'];
		}
		Fsb::$db->free($result);
	break;

	case 'users' :
		$state = ($use_current) ? ONE_DAY : ONE_MONTH;

		// On recupere le nombre de messages par jour
		$sql = 'SELECT u_joined, COUNT(u_id) AS total_user
				FROM ' . SQL_PREFIX . 'users
				WHERE u_joined >= ' . $begin_time . '
					AND u_joined <= ' . $end_time . '
				GROUP BY u_joined / ' . $state . '
				ORDER BY u_joined';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$y = date('Y', $row['u_joined']);
			$n = date(($use_current) ? 'd' : 'n', $row['u_joined']);
			if (!isset($args[$y]))
			{
				$args[$y] = array();
			}

			if (!isset($args[$y][$n]))
			{
				$args[$y][$n] = 0;
			}

			$args[$y][$n] += $row['total_user'];
		}
		Fsb::$db->free($result);
	break;

	default :
		exit;
	break;
}

// Creation des valeurs pour le graphique
$values = array();
foreach ($args AS $y => $list_y)
{
	foreach ($list_y AS $m => $total)
	{
		$values[] = array(
			'lg' =>	($use_current) ? $m : utf8_decode(String::substr(Fsb::$session->lang('month_' . $m), 0, 3)),
			'v' =>	$total,
		);
	}
}

$gd_stats = new Gd_stats(600, 300);
$gd_stats->values($values);
$gd_stats->output();

/* EOF */