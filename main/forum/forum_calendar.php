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
 * Affiche un calendrier avec planification possible d'evenements
 *
 */
class Fsb_frame_child extends Fsb_frame
{
	/**
	 * Affichage de la barre de navigation du header
	 *
	 * @var bool
	 */
	public $_show_page_header_nav = true;
	
	/**
	 * Affichage de la barre de navigation du footer
	 *
	 * @var bool
	 */
	public $_show_page_footer_nav = false;
	
	/**
	 * Affichage de la boite des stats
	 *
	 * @var bool
	 */
	public $_show_page_stats = false;

	/**
	 * Timestamp
	 *
	 * @var int
	 */
	public $current;

	/**
	 * Jour actuel
	 *
	 * @var int
	 */
	public $current_day;
	
	/**
	 * Mois actuel
	 *
	 * @var int
	 */
	public $current_month;
	
	/**
	 * Annee actuel
	 *
	 * @var int
	 */
	public $current_year;

	/**
	 * Mode
	 *
	 * @var string
	 */
	public $mode;
	
	/**
	 * Id
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Navigation
	 *
	 * @var array
	 */
	public $nav;

	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		if (!Fsb::$mods->is_active('calendar'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		if (!Fsb::$session->is_authorized('calendar_read'))
		{
			Display::message('not_allowed');
		}

		$this->mode =			Http::request('mode');
		$this->id =				intval(Http::request('id'));
		$this->current =			intval(Http::request('time'));

		if (!$this->current)
		{
			$this->current = CURRENT_TIME;
		}

		$this->current_day =		date('d', $this->current);
		$this->current_month =	date('n', $this->current);
		$this->current_year =	date('Y', $this->current);

		$call = new Call($this);
		$call->post(array(
			'goto_date' =>	':change_date',
		));

		$call->functions(array(
			'mode' => array(
				'event' =>		'show_events',
				'approve' =>	'approve_event',
				'unapprove' =>	'approve_event',
				'delete' =>		'delete_event',
				'default' =>	'show_calendar',
			),
		));
	}

	/**
	 * Affiche le calendrier courant, ainsi que les calendriers des mois suivants et precedents
	 *
	 */
	public function show_calendar()
	{
		Fsb::$tpl->set_file('forum/forum_calendar.html');

		// Mois precedent
		$month_previous = $this->current_month;
		$year_previous = $this->current_year;
		if ($month_previous == 1)
		{
			$month_previous = 12;
			$year_previous--;
		}
		else
		{
			$month_previous--;
		}

		// Mois suivant
		$month_next = $this->current_month;
		$year_next = $this->current_year;
		if ($month_next == 12)
		{
			$month_next = 1;
			$year_next++;
		}
		else
		{
			$month_next++;
		}

		// On recupere les evenements pour cette periode
		$begin_timestamp =	intval(mktime(0, 0, 0, $month_previous, 1, $year_previous));
		$end_timestamp =	intval(mktime(0, 0, 0, $month_next, date('t', mktime(23, 59, 59, $month_next, 1, $year_next)), $year_next));
		$sql_in_group =		(Fsb::$session->auth() < ADMIN && Fsb::$session->data['groups']) ? ' AND c_view IN (' . implode(', ', Fsb::$session->data['groups']) . ')' : '';

		$sql = 'SELECT c_begin, c_end, c_title, c_approve, u_id
				FROM ' . SQL_PREFIX . 'calendar
				WHERE c_end > ' . $begin_timestamp . ' 
					AND c_begin < ' . $end_timestamp . '
					AND (c_view = -1 OR (c_view = 0 AND u_id = ' . Fsb::$session->id() . ') OR (c_view > 0 ' . $sql_in_group . '))
					ORDER BY c_begin, c_id';
		$result = Fsb::$db->query($sql);
		$events = array();
		while ($row = Fsb::$db->row($result))
		{
			if ($row['c_approve'] || Fsb::$session->is_authorized('approve_event') || Fsb::$session->id() == $row['u_id'])
			{
				// On ajoute les evenements au tableau $event, avec en clef le mois et le jour, par exemple
				// pour la liste des evenements du 24 decembre 2006 : $events[12][24]
				$timestamp_begin =	($row['c_begin'] < $begin_timestamp) ? $begin_timestamp : $row['c_begin'];
				$timestamp_end =	($row['c_end'] > $end_timestamp) ? $end_timestamp : $row['c_end'];
				for ($timestamp =	$timestamp_begin; $timestamp <= $timestamp_end; $timestamp += ONE_DAY)
				{
					$events[date('n', $timestamp)][date('j', $timestamp)][] = array(
						'type' =>	'event',
						'lang' =>	$row['c_title'],
					);
				}
			}
		}
		Fsb::$db->free($result);

		// On recupere les anniversaires pour le calendrier
		if (Fsb::$cfg->get('calendar_birthday_activate'))
		{
			$sql = 'SELECT u_nickname, u_birthday
					FROM ' . SQL_PREFIX . 'users
					WHERE u_total_post > ' . intval(Fsb::$cfg->get('calendar_birthday_required_posts')) . '
						AND (u_birthday LIKE \'%/' . String::add_zero($month_previous, 2) . '/%\'
							OR u_birthday LIKE \'%/' . String::add_zero($this->current_month, 2) . '/%\'
							OR u_birthday LIKE \'%/' . String::add_zero($month_next, 2) . '/%\')';
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				$birth_day =	intval(substr($row['u_birthday'], 0, 2));
				$birth_month =	intval(substr($row['u_birthday'], 3, 2));
				$events[$birth_month][$birth_day][] = array(
					'type' =>	'birthday',
					'year' =>	intval(substr($row['u_birthday'], 6, 4)),
					'lang' =>	sprintf(Fsb::$session->lang('calendar_birthday'), htmlspecialchars($row['u_nickname'])),
				);
			}
			Fsb::$db->free($result);
		}

		// Grand calendrier (mois en cours de selection)
		$this->generate_calendar('complex', $this->current_month, $this->current_year, $events);

		$previous_time =	$this->generate_calendar('simple', $month_previous, $year_previous, $events);
		$next_time =		$this->generate_calendar('simple', $month_next, $year_next, $events);

		// Liste des mois, annees
		$list_month = array();
		for ($i = 1; $i <= 12; $i++)
		{
			$list_month[$i] = Fsb::$session->lang('month_' . $i);
		}

		$list_year = array();
		for ($i = (($this->current_year < 1920) ? 1910 : $this->current_year - 10); $i <= (($this->current_year > 2020) ? 2030 : $this->current_year + 10); $i++)
		{
			$list_year[$i] = $i;
		}

		Fsb::$tpl->set_vars(array(
			'CURRENT_MONTH' =>		Fsb::$session->lang('month_' . date('n', $this->current)) . ' ' . $this->current_year,
			'LIST_MONTH' =>			Html::make_list('month', $this->current_month, $list_month, array('id' => 'list_month_id')),
			'LIST_YEAR' =>			Html::make_list('year', $this->current_year, $list_year),

			'U_PREVIOUS' =>			sid(ROOT . 'index.' . PHPEXT . '?p=calendar&amp;time=' . $previous_time),
			'U_NEXT' =>				sid(ROOT . 'index.' . PHPEXT . '?p=calendar&amp;time=' . $next_time),
			'U_CALENDAR' =>			sid(ROOT . 'index.' . PHPEXT . '?p=calendar'),
			'U_ADD_EVENT' =>		sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=calendar_add'),
			'U_ACTION' =>			sid(ROOT . 'index.' . PHPEXT . '?p=calendar&amp;mode=' . $this->mode),
		));

		// Peut creer un evenement ?
		if (Fsb::$session->is_authorized('calendar_write'))
		{
			Fsb::$tpl->set_switch('can_add_event');
		}
	}

	/**
	 * Genere un calendrier simple ou complexe
	 *
	 * @param string $type Type de calendrier (simple ou complex)
	 * @param int $month Mois
	 * @param int $year Annee 
	 * @param array $events Liste des evenements
	 * @return int 
	 */
	public function generate_calendar($type, $month, $year, &$events)
	{
		// On recupere le jour du premier du mois
		$first_day_time =		mktime(0, 0, 0, $month, 1, $year);
		$first_day_week_day =	date('w', $first_day_time);
		$first_day_week_day =	($first_day_week_day == 0) ? 6 : $first_day_week_day - 1;

		// Nombre de jours dans le mois
		$total_day = date('t', $first_day_time);

		// En mode simple on cree un sous block
		if ($type == 'simple')
		{
			Fsb::$tpl->set_blocks('sub', array(
				'MONTH_NAME' =>	Fsb::$session->lang('month_' . date('n', $first_day_time)) . ' ' . $year,
			));
		}

		// Affiche les jours de la semaine
		foreach (array('mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun') AS $week_day)
		{
			Fsb::$tpl->set_blocks((($type == 'simple') ? 'sub.' : '') . 'week_day', array(
				'NAME' =>	($type == 'simple') ? substr(Fsb::$session->lang('week_day_' . $week_day), 0, 3) : Fsb::$session->lang('week_day_' . $week_day),
			));
		}

		// On rempli de vide les jours avant le jour de depart
		for ($i = 0; $i < $first_day_week_day; $i++)
		{
			Fsb::$tpl->set_blocks((($type == 'simple') ? 'sub.' : '') . 'day', array(
				'FILL' =>	false,
			));
		}

		// Affiche la liste des jours du mois
		$todays_day = date('d', CURRENT_TIME);
		$todays_month = date('n', CURRENT_TIME);
		$todays_year = date('Y', CURRENT_TIME);
		for ($i = 1; $i <= $total_day; $i++)
		{
			// Suppression des anniversaires anterieurs a la date de naissance
			$total_events = 0;
			if (isset($events[$month][$i]))
			{
				foreach ($events[$month][$i] AS $k => $e)
				{
					if ($e['type'] == 'birthday' && $e['year'] > $year)
					{
						unset($events[$month][$i][$k]);
					}
				}
				$total_events = count($events[$month][$i]);
			}

			Fsb::$tpl->set_blocks((($type == 'simple') ? 'sub.' : '') . 'day', array(
				'NB' =>			$i,
				'FILL' =>		true,
				'CURRENT' =>	($month == $todays_month && $year == $todays_year && $i == $todays_day) ? true : false,
				'HAVE_EVENT' =>	($total_events) ? true : false,
				'U_EVENT' =>	($total_events) ? sid(ROOT . 'index.' . PHPEXT . '?p=calendar&amp;mode=event&amp;time=' . mktime(0, 0, 0, $month, $i, $year)) : '',
				//'EVENT' =>		($event_exists > 0) ? (($event_exists > 1) ? sprintf(Fsb::$session->lang('calendar_total_events'), $event_exists) : htmlspecialchars($events[$month][$i][0]['lang'])) : '',
			));

			if ($total_events && $type == 'complex')
			{
				if ($total_events <= 3)
				{
					foreach ($events[$month][$i] AS $data)
					{
						$realname = $name = $data['lang'];
						if ($total_events > 1 && strlen($name) > 20)
						{
							$name = substr($name, 0, 20) . '..';
						}

						Fsb::$tpl->set_blocks('day.event', array(
							'IS_BIRTHDAY' =>	($data['type'] == 'birthday') ? true : false,
							'NAME' =>			htmlspecialchars($name),
							'TITLE' =>			htmlspecialchars($realname),
						));
					}
				}
				else
				{
					$is_birthday = true;
					foreach ($events[$month][$i] AS $data)
					{
						if ($data['type'] != 'birthday')
						{
							$is_birthday = false;
							break;
						}
					}

					$name = sprintf(Fsb::$session->lang('calendar_total_events'), $total_events);
					Fsb::$tpl->set_blocks('day.event', array(
						'IS_BIRTHDAY' =>	$is_birthday,
						'NAME' =>			$name,
						'TITLE' =>			$name,
					));
				}
			}
		}

		// On rempli de vide les jours apres le jour de fin de mois
		if (($first_day_week_day + $total_day) % 7)
		{
			for ($i = ($first_day_week_day + $total_day) % 7; $i < 7; $i++)
			{
				Fsb::$tpl->set_blocks((($type == 'simple') ? 'sub.' : '') . 'day', array(
					'FILL' =>		false,
				));
			}
		}

		return ($first_day_time);
	}

	/**
	 * Affiche les evenements de la journee
	 *
	 */
	public function show_events()
	{
		Fsb::$tpl->set_file('forum/forum_calendar_event.html');

		$parser = new Parser();

		// On recupere les evenements de la journee
		$begin_timestamp =	mktime(0, 0, 0, date('n', $this->current), date('d', $this->current), date('Y', $this->current));
		$end_timestamp =	mktime(23, 59, 59, date('n', $this->current), date('d', $this->current), date('Y', $this->current));
		$sql_in_group =		(Fsb::$session->auth() < ADMIN && Fsb::$session->data['groups']) ? ' AND c.c_view IN (' . implode(', ', Fsb::$session->data['groups']) . ')' : '';
		$sql = 'SELECT c.c_id, c.c_title, c.c_begin, c.c_end, c.c_content, c.c_approve, u.u_id, u.u_nickname, u.u_color, u.u_auth
				FROM ' . SQL_PREFIX . 'calendar c
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON c.u_id = u.u_id
				WHERE c.c_end >= ' . $begin_timestamp . ' 
					AND c.c_begin <= ' . $end_timestamp . '
					AND (c.c_view = -1 OR (c.c_view = 0 AND c.u_id = ' . Fsb::$session->id() . ') OR (c.c_view > 0 ' . $sql_in_group . '))
					ORDER BY c.c_begin, c.c_id';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			if ($row['c_approve'] || Fsb::$session->is_authorized('approve_event') || Fsb::$session->id() == $row['u_id'])
			{
				list($day, $month, $year, $hour, $min) = explode(' ', date('d n Y H i', $row['c_begin']));
				$begin = ($min == '00' && $hour == '00') ? sprintf(Fsb::$session->lang('calendar_event_date'), sprintf(Fsb::$session->lang('format_date'), $day, Fsb::$session->lang('month_' . $month), $year)) : sprintf(Fsb::$session->lang('calendar_event_date2'), sprintf(Fsb::$session->lang('format_date'), $day, Fsb::$session->lang('month_' . $month), $year), $hour, $min);
				list($day, $month, $year, $hour, $min) = explode(' ', date('d n Y H i', $row['c_end']));
				$end = ($min == '00' && $hour == '00') ? sprintf(Fsb::$session->lang('calendar_event_date'), sprintf(Fsb::$session->lang('format_date'), $day, Fsb::$session->lang('month_' . $month), $year)) : sprintf(Fsb::$session->lang('calendar_event_date2'), sprintf(Fsb::$session->lang('format_date'), $day, Fsb::$session->lang('month_' . $month), $year), $hour, $min);

				// Informations passees au parseur de message
				$parser_info = array(
					'u_id' =>			$row['u_id'],
					'p_nickname' =>		$row['u_nickname'],
					'u_auth' =>			$row['u_auth'],
					'c_id' =>			$row['c_id'],
				);

				$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;
				Fsb::$tpl->set_blocks('event', array(
					'TITLE' =>			htmlspecialchars($row['c_title']),
					'CONTENT' =>		$parser->mapped_message($row['c_content'], 'classic', $parser_info),
					'BEGIN' =>			$begin,
					'END' =>			$end,
					'CAN_EDIT' =>		(($row['u_id'] == Fsb::$session->id() && Fsb::$session->is_logged()) || Fsb::$session->is_authorized('approve_event')) ? true : false,
					'IS_APPROVED' =>	$row['c_approve'],
					'BY' =>				Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),

					'U_EDIT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=calendar_edit&amp;id=' . $row['c_id']),
					'U_DELETE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=calendar&amp;mode=delete&amp;id=' . $row['c_id']),
					'U_APPROVE' =>		(!$row['c_approve']) ? sid(ROOT . 'index.' . PHPEXT . '?p=calendar&amp;mode=approve&amp;id=' . $row['c_id']) : sid(ROOT . 'index.' . PHPEXT . '?p=calendar&amp;mode=unapprove&amp;id=' . $row['c_id']),
				));
			}
		}
		Fsb::$db->free($result);

		// Anniversaires du jour
		if (Fsb::$cfg->get('calendar_birthday_activate'))
		{
			$current_day =	date('d', $this->current);
			$current_month = date('n', $this->current);
			$current_year =	date('Y', $this->current);

			$sql = 'SELECT u_id, u_nickname, u_color, u_birthday
					FROM ' . SQL_PREFIX . 'users
					WHERE u_total_post > ' . intval(Fsb::$cfg->get('calendar_birthday_required_posts')) . '
						AND u_birthday LIKE \'' . $current_day . '/' . String::add_zero($current_month, 2) . '/%\'';
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				$begin =	sprintf(Fsb::$session->lang('calendar_event_date'), sprintf(Fsb::$session->lang('format_date'), $current_day, Fsb::$session->lang('month_' . $current_month), $current_year));
				$end =		sprintf(Fsb::$session->lang('calendar_event_date'), sprintf(Fsb::$session->lang('format_date'), $current_day, Fsb::$session->lang('month_' . $current_month), $current_year));
				$age =		intval($current_year - substr($row['u_birthday'], 6));

				if ($age >= 0)
				{
					Fsb::$tpl->set_blocks('event', array(
						'TITLE' =>			sprintf(Fsb::$session->lang('calendar_birthday'), htmlspecialchars($row['u_nickname'])),
						'CONTENT' =>		sprintf(Fsb::$session->lang('calendar_birthday'), htmlspecialchars($row['u_nickname'])) . ' (' . $age . ')',
						'BEGIN' =>			$begin,
						'END' =>			$end,
						'CAN_EDIT' =>		false,
						'APPROVE' =>		false,
						'IS_APPROVED' =>	true,
						'BY' =>				Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
					));
				}
			}
			Fsb::$db->free($result);
		}

		// Liste des jours, mois, annees
		$list_day = array();
		for ($i = 1; $i <= 31; $i++)
		{
			$list_day[$i] = $i;
		}

		$list_month = array();
		for ($i = 1; $i <= 12; $i++)
		{
			$list_month[$i] = Fsb::$session->lang('month_' . $i);
		}

		$list_year = array();
		for ($i = (($this->current_year < 1920) ? 1910 : $this->current_year - 10); $i <= (($this->current_year > 2020) ? 2030 : $this->current_year + 10); $i++)
		{
			$list_year[$i] = $i;
		}

		// Modere le calendrier
		if (Fsb::$session->is_authorized('approve_event'))
		{
			Fsb::$tpl->set_switch('can_approve');
		}

		Fsb::$tpl->set_vars(array(
			'LIST_DAY' =>			Html::make_list('day', $this->current_day, $list_day),
			'LIST_MONTH' =>			Html::make_list('month', $this->current_month, $list_month),
			'LIST_YEAR' =>			Html::make_list('year', $this->current_year, $list_year),

			'U_ACTION' =>			sid(ROOT . 'index.' . PHPEXT . '?p=calendar&amp;mode=' . $this->mode),
		));

		// Navigation
		$this->nav[] = array(
			'name' =>	sprintf(Fsb::$session->lang('calendar_nav_date'), sprintf(Fsb::$session->lang('format_date'), date('d', $this->current), Fsb::$session->lang('month_' . date('n', $this->current)), date('Y', $this->current))),
			'url' =>	sid(ROOT . 'index.' . PHPEXT . '?p=calendar&amp;time=' . $this->current),
		);
	}

	/**
	 * Approuve un evenement
	 *
	 */
	public function approve_event()
	{
		if (Fsb::$session->is_authorized('approve_event'))
		{
			$sql = 'SELECT c_begin
					FROM ' . SQL_PREFIX . 'calendar
					WHERE c_id = ' . $this->id;
			$c_begin = Fsb::$db->get($sql, 'c_begin');
			if ($c_begin && Fsb::$session->is_authorized('approve_event'))
			{
				Fsb::$db->update('calendar', array(
					'c_approve' =>	($this->mode == 'approve') ? 1 : 0,
				), 'WHERE c_id = ' . $this->id);
				Fsb::$db->destroy_cache('calendar_');

				Display::message('calendar_well_' . $this->mode, ROOT . 'index.' . PHPEXT . '?p=calendar&mode=event&time=' . $c_begin, 'forum_calendar');
			}
		}
		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=calendar');
	}

	/**
	 * Supprime un evenement
	 *
	 */
	public function delete_event()
	{
		$sql = 'SELECT c_begin, u_id
				FROM ' . SQL_PREFIX . 'calendar
				WHERE c_id = ' . $this->id;
		$result = Fsb::$db->query($sql);
		$data = Fsb::$db->row($result);
		Fsb::$db->free($result);

		if (check_confirm() && $data)
		{
			if ($data['u_id'] == Fsb::$session->id() || Fsb::$session->is_authorized('approve_event'))
			{
				$sql = 'DELETE FROM ' . SQL_PREFIX . 'calendar
						WHERE c_id = ' . $this->id;
				Fsb::$db->query($sql);
				Fsb::$db->destroy_cache('calendar_');

				Display::message('calendar_well_delete', ROOT . 'index.' . PHPEXT . '?p=calendar', 'forum_calendar');
			}
			else
			{
				Display::message('not_allowed');
			}
		}
		else if (Http::request('confirm_no', 'post') || !$data)
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=calendar&mode=event&time=' . $data['c_begin']);
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('calendar_confirm_delete'), ROOT . 'index.' . PHPEXT . '?p=calendar&amp;mode=delete&amp;id=' . $this->id, array('mode' => $this->mode, 'id' => $this->id));
		}
	}

	/**
	 * Change la date du jour courant
	 *
	 */
	public function change_date()
	{
		$this->current_day =		intval(Http::request('day'));
		$this->current_month =	intval(Http::request('month'));
		$this->current_year =	intval(Http::request('year'));
		$this->current = mktime(0, 0, 0, $this->current_month, ($this->current_day) ? $this->current_day : 1, $this->current_year);
	}
}

/* EOF */
