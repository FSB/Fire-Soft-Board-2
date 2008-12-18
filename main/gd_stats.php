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
 * Affiche des statistiques du forum
 */

define('PHPEXT', substr(strrchr(__FILE__,'.'), 1));
define('ROOT', '../');
define('FORUM', true);
include(ROOT . 'main/start.' . PHPEXT);

Fsb::$session->start('', false);

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

// Rempli les jours / mois / annees vides dans l'interval
if ($state == ONE_DAY)
{
	$y = date('Y', $begin_time);
	$new = array();
	for ($i = 1; $i <= date('t', $begin_time); $i++)
	{
		$i = String::add_zero($i, 2);
		$new[$i] = (isset($args[$y][$i])) ? $args[$y][$i] : 0;
	}
	$args[$y] = $new;
}
else if ($state == ONE_MONTH)
{
	$start_year = date('Y', $begin_time);
	$end_year = date('Y', $end_time);
	$start_month = date('n', $begin_time);
	$end_month = date('n', $end_time);
	
	// Calcul du nombre de mois total
	$interval = ($end_month - $start_month) + (12 * ($end_year - $start_year));
	if ($interval < 0)
	{
		$interval = 0;
	}

	$debug = 0;
	$month = $start_month;
	$year = $start_year;
	$new = array();
	$i = 0;
	while ($i <= $interval)
	{
		if (!isset($new[$year]))
		{
			$new[$year] = array();
		}
		
		$m = String::add_zero($month, 2);
		$new[$year][$month] = (isset($args[$year][$m])) ? $args[$year][$m] : 0;
		
		$month++;
		if ($month == 13)
		{
			$year++;
			$month = 1;
		}
		$i++;
	}
	$args = $new;
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

$gd_stats = new Gd_stats(650, 300);
$gd_stats->values($values);
$gd_stats->output();

/* EOF */