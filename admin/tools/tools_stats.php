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
 * Page affichant les statistiques du forum
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Mode de la page
	 *
	 * @var string
	 */
	public $mode = '';

	/**
	 * Constructeur
	 */
	public function main()
	{
		$this->mode = Http::request('mode');

		$call = new Call($this);
		$call->module(array(
			'list' =>		array('text', 'gd'),
			'url' =>		'index.' . PHPEXT . '?p=tools_stats',
			'lang' =>		'adm_stats_',
			'default' =>	'text',
		));

		$call->functions(array(
			'mode' => array(
				'phpinfo' =>	'show_phpinfo',
				'default' => array(
					'module' => array(
						'text' =>	'show_text_stats',
						'gd' =>		'show_gd_stats',
					),
				),
			),
		));
	}

	/**
	 * Affiche les statistiques textuelles du forum
	 */
	public function show_text_stats()
	{
		$stats_users =	$this->get_simple_stats('total_users');
		$stats_topics =	$this->get_simple_stats('total_topics');
		$stats_posts =	$this->get_simple_stats('total_posts');
		$stats_smiley =	$this->get_memory_stats(SMILEY_PATH);
		$stats_avatar =	$this->get_memory_stats(AVATAR_PATH);
		$stats_rank =	$this->get_memory_stats(RANK_PATH);
		$stats_upload =	$this->get_memory_stats(ROOT . 'upload/');
		$stats_sql =	$this->get_sql_stats();

		Fsb::$tpl->set_switch('stats_forum');
		Fsb::$tpl->set_vars(array(
			'OS_TYPE' =>			PHP_OS,
			'PHP_VERSION' =>		phpversion(),
			'SGBD_TYPE' =>			$stats_sql['sgbd_type'],
			'SGBD_VERSION' =>		$stats_sql['sgbd_version'],
			'TOTAL_USERS' =>		$stats_users['total'],
			'AVERAGE_USERS' =>		$stats_users['average'],
			'TOTAL_TOPICS' =>		$stats_topics['total'],
			'AVERAGE_TOPICS' =>		$stats_topics['average'],
			'TOTAL_POSTS' =>		$stats_posts['total'],
			'AVERAGE_POSTS' =>		$stats_posts['average'],
			'TOTAL_SQL' =>			$stats_sql['total'],
			'SIZE_SQL' =>			convert_size($stats_sql['size']),
			'TOTAL_SMILEY' =>		$stats_smiley['total'],
			'SIZE_SMILEY' =>		convert_size($stats_smiley['size']),
			'TOTAL_AVATAR' =>		$stats_avatar['total'],
			'SIZE_AVATAR' =>		convert_size($stats_avatar['size']),
			'TOTAL_RANK' =>			$stats_rank['total'],
			'SIZE_RANK' =>			convert_size($stats_rank['size']),
			'TOTAL_UPLOAD' =>		$stats_upload['total'],
			'SIZE_UPLOAD' =>		convert_size($stats_upload['size']),
			'FSB_CREATE' =>			Fsb::$session->print_date(Fsb::$cfg->get('register_time')),
			'CACHE_SYSTEM' =>		Fsb::$db->cache->cache_type,

			'U_PHP_VERSION' =>		sid('index.' . PHPEXT . '?p=tools_stats&amp;mode=phpinfo'),
		));
	}

	/**
	 * Affiche le phpinfo()
	 */
	public function show_phpinfo()
	{
		// Fonction temporaire de callback
		function phpinfo_legend_name($match)
		{
			$title = ($match[2]) ? $match[2] : '???';
			return ('<fieldset><legend>' . $title . '</legend><div style="overflow: auto; background-color: transparent; border: none; text-align: left;"><table id="tab" width="100%">' . $match[3] . '</table></div></fieldset>');
		}

		// On recupere le buffer de phpinfo()
		ob_start();
		phpinfo();
		$buffer = ob_get_contents();
		ob_end_clean();

		// On parse le buffer pour mieux afficher le style
		$buffer = preg_replace('#<html>.*?</head>#si', '', $buffer);
		$buffer = preg_replace('#</body>.*?</html>#', '', $buffer);
		$buffer = preg_replace_callback('#(<h2>(.*?)</h2>)?\s*<table border="0" cellpadding="3" width="600">(.*?)</table>#si', 'phpinfo_legend_name', $buffer);
		$buffer = preg_replace('#<tr>#', '<tr id="highlight">', $buffer);
		$buffer = preg_replace('#<tr class="h">#si', '<tr>', $buffer);
		$buffer = preg_replace('#<td class="e">(.*?)</td>#si', '<td width="300"><b>\\1</b></td>', $buffer);
		$buffer = preg_replace('#<td class="v">#si', '<td>', $buffer);
		$buffer = preg_replace('#<img #si', '<img style="float: right;" ', $buffer);

		// On affiche le buffer
		Fsb::$tpl->set_file('phpinfo.html');
		Fsb::$tpl->set_vars(array(
			'PHPINFO' =>	$buffer,
		));
	}

	/**
	 * Renvoie des statistiques (nombre de sujets, moyenne par jour, etc ...)
	 * 
	 * @param string $key Cle dans la table de configuration
	 * @result array Statistique de FSB
	 */
	public function get_simple_stats($key)
	{
		$stat = array();
		$stat['total'] = Fsb::$cfg->get($key);
		$nb_day = ceil((CURRENT_TIME - Fsb::$cfg->get('register_time')) / (24 * 3600));
		$stat['average'] = substr(($nb_day > 0) ? $stat['total'] / $nb_day : 0, 0, 4);
		return ($stat);
	}

	/**
	 * Renvoie des statistiques sur les images contenue dans un repertoire : leur nombre
	 * et la place totale prise.
	 * 
	 * @param string $dir Repertoire dont on veut les donnees
	 * @result array Statistique de l'espace disque utilise par fsb2
	 */
	public function get_memory_stats($dir)
	{
		$stat = array('total' => 0, 'size' => 0);
		$fd = opendir($dir);
		while ($file = readdir($fd))
		{
			if ($file[0] != '.' && $file != 'index.html')
			{
				$stat['total']++;
				$stat['size'] += filesize($dir . $file);
			}
		}
		closedir($fd);
		return ($stat);
	}

	/**
	 * Renvoie le type de base de donnee, sa version, sa taille et son nombre de tables
	 *
	 * @return array Statistique de la base de donnee
	 */
	public function get_sql_stats()
	{		
		$db_size = 0;
		$db_total_table = 0;
		$sgbd_type = '';
		$sgbd_version = '';

		switch (SQL_DBAL)
		{
			case "mysql" :
			case "mysqli" :
				$sql = 'SHOW TABLE STATUS';
				$result = Fsb::$db->query($sql);
				while ($stat = Fsb::$db->row($result))
				{
					if ((SQL_PREFIX == '' || strstr($stat['Name'], SQL_PREFIX)) && ((isset($stat['Type']) && $stat['Type'] != 'MRG_MyISAM') || (isset($stat['Engine']) && $stat['Engine'] == 'MyISAM')))
					{
						$db_size += $stat['Data_length'] + $stat['Index_length'];
						$db_total_table++;
					}
				}

				// On recupere la version MySQL
				$sql = 'SELECT VERSION() AS version';
				$sgbd_version = Fsb::$db->get($sql, 'version');

				$sgbd_type = 'MySQL';
				if (SQL_DBAL == 'mysqli')
				{
					$sgbd_type .= ' (mysqli)';
				}
			break;

			case "pgsql" :
				$sql = 'SELECT COUNT(tablename) AS total FROM pg_tables
							WHERE schemaname = \'public\'';
				$result = Fsb::$db->query($sql);
				$stat = Fsb::$db->row($result);
				$db_size = 0;
				$db_total_table = $stat['total'];

				// On recupere la version PostgreSQL
				$sql = 'SELECT VERSION()';
				$result = Fsb::$db->query($sql);
				$row = Fsb::$db->row($result, 'row');
				preg_match('#^postgresql ([a-zA-Z0-9.]*?) #i', $row[0], $match);
				$sgbd_version = $match[1];
				$sgbd_type = 'PostgreSQL';
			break;

			case "sqlite" :
				$sql = 'SELECT COUNT(name) AS total
							FROM (SELECT * FROM sqlite_master UNION SELECT * FROM sqlite_temp_master) 
							WHERE type=\'table\' ORDER BY name';
				$result = Fsb::$db->query($sql);
				$stat = Fsb::$db->row($result);
				$db_size = (file_exists(ROOT . 'main/dbal/sqlite/' . SQL_DB . '.sqlite')) ? filesize(ROOT . 'main/dbal/sqlite/' . SQL_DB . '.sqlite') : '';
				$db_total_table = $stat['total'];

				// On recupere la version SQLite
				$sgbd_version = sqlite_libversion();
				$sgbd_type = 'SQLite';
			break;
		}

		return (array('total' => $db_total_table, 'size' => $db_size, 'sgbd_type' => $sgbd_type, 'sgbd_version' => $sgbd_version));
	}


	/**
	 * Affiche les statistiques graphiques du forum
	 */
	public function show_gd_stats()
	{
		Fsb::$tpl->set_switch('stats_gd');
		$this->show_stat_image('posts', Fsb::$session->lang('adm_stats_forums_posts'));
		$this->show_stat_image('topics', Fsb::$session->lang('adm_stats_forums_topics'));
		$this->show_stat_image('users', Fsb::$session->lang('adm_stats_forums_users'));
	}

	/**
	 * Affiche une image de statistiques
	 *
	 * @param string $img_type Type de l'image
	 * @param string $legend Legende de l'image
	 */
	public function show_stat_image($img_type, $legend)
	{
		// Calcul du timestamp dans une annee
		$min_timestamp = (CURRENT_TIME - Fsb::$cfg->get('register_time') > ONE_YEAR) ? CURRENT_TIME - ONE_YEAR : Fsb::$cfg->get('register_time');

		$month_begin = intval(Http::request($img_type . '_month_begin', 'post'));
		if (!$month_begin)
		{
			$month_begin = date('n', CURRENT_TIME);
		}

		$year_begin = intval(Http::request($img_type . '_year_begin', 'post'));
		if (!$year_begin)
		{
			$year_begin = date('Y', CURRENT_TIME);
		}

		$month_end = intval(Http::request($img_type . '_month_end', 'post'));
		if (!$month_end)
		{
			$month_end = date('n', CURRENT_TIME);
		}

		$year_end = intval(Http::request($img_type . '_year_end', 'post'));
		if (!$year_end)
		{
			$year_end = date('Y', CURRENT_TIME);
		}

		$current = ($month_begin == $month_end && $year_begin == $year_end) ? true : false;

		// Liste des mois
		$list_month = array();
		for ($i = 1; $i <= 12; $i++)
		{
			$list_month[$i] = Fsb::$session->lang('month_' . $i);
		}

		// Liste des annees
		$list_year = array();
		for ($i = date('Y', Fsb::$cfg->get('register_time')); $i <= date('Y', CURRENT_TIME); $i++)
		{
			$list_year[$i] = $i;
		}

		// Date de debut et de fin a passer aux images
		$begin = mktime(0, 0, 0, $month_begin, 1, $year_begin);
		$end = mktime(0, 0, 0, $month_end, date('t', mktime(0, 0, 0, $month_end, 1, $year_end)), $year_end);

		// Titre de l'image
		$title = ($current) ? sprintf(Fsb::$session->lang('adm_stats_current_month'), Fsb::$session->lang('month_' . date('n', $begin)), date('Y', $begin)) : Fsb::$session->lang('month_' . date('n', $begin)) . ' ' . date('Y', $begin) . ' &nbsp; &#187; &nbsp; ' . Fsb::$session->lang('month_' . date('n', $end)) . ' ' . date('Y', $end);

		// ID unique
		$uniqid = md5(rand(0, CURRENT_TIME));

		Fsb::$tpl->set_blocks('gd', array(
			'LEGEND' =>				$legend,
			'IMG' =>				sid(ROOT . 'main/gd_stats.' . PHPEXT . '?img=' . $img_type . '&amp;begin=' . $begin . '&amp;end=' . $end . '&amp;current=' . $current . '&amp;uniqid=' . $uniqid),
			'LIST_MONTH_BEGIN' =>	Html::make_list($img_type . '_month_begin', $month_begin, $list_month),
			'LIST_YEAR_BEGIN' =>	Html::make_list($img_type . '_year_begin', $year_begin, $list_year),
			'LIST_MONTH_END' =>		Html::make_list($img_type . '_month_end', $month_end, $list_month),
			'LIST_YEAR_END' =>		Html::make_list($img_type . '_year_end', $year_end, $list_year),
			'CURRENT' =>				$current,
			'IMG_TITLE' =>			$title,
			'TYPE' =>				$img_type,
		));
	}
}

/* EOF */
