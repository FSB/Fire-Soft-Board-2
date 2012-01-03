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
 * Affiche les membres en ligne sur le forum, ainsi que quelques statistiques
 */

// Protection de la page
if (strpos($_SERVER['PHP_SELF'], 'online.') !== false)
{
	exit;
}

//
// On genere la legende si l'affichage des membres en ligne est active
//
if (Fsb::$session->is_authorized('online_box'))
{
	if (Fsb::$mods->is_active('online_show_current') || Fsb::$mods->is_active('online_show_today'))
	{
		$is_empty = array('join' => '', 'having' => '');
		if (Fsb::$cfg->get('hide_empty_groups'))
		{
			$is_empty = array(
				'join'	=> 'LEFT JOIN ' . SQL_PREFIX . 'groups_users gu ON g.g_id = gu.g_id',
				'having' => 'HAVING COUNT(gu.g_id) > 0',
			);
		}

		$sql = 'SELECT g.g_id, g.g_name, g.g_color
				FROM ' . SQL_PREFIX . 'groups g
				' . $is_empty['join'] . '
				WHERE g.g_online = 1
					AND g.g_type <> ' . GROUP_SINGLE . '
					AND g.g_id <> ' . GROUP_SPECIAL_VISITOR . '
				GROUP BY g.g_id, g.g_name, g.g_type, g.g_desc, g.g_color
				' . $is_empty['having'] . '
				ORDER BY g.g_order, g.g_name';
		$result = Fsb::$db->query($sql, 'groups_');
		$legend = array();
		while ($row = Fsb::$db->row($result))
		{
			$legend[] = $row;
		}
		Fsb::$db->free($result);

		foreach ($legend AS $row)
		{
			Fsb::$tpl->set_blocks('online_legend', array(
				'NAME' =>		(Fsb::$session->lang($row['g_name'])) ? Fsb::$session->lang($row['g_name']) : $row['g_name'],
				'URL' =>		sid(ROOT . 'index.' . PHPEXT . '?p=userlist&amp;g_id=' . $row['g_id']),
				'CLASS' =>		$row['g_color'],
			));
		}
	}

	//
	// On affiche les membres en ligne
	//
	if (Fsb::$mods->is_active('online_show_current'))
	{
		$sql = 'SELECT s.s_id, s.s_ip, u.u_nickname, u.u_color, u.u_activate_hidden, b.bot_id, b.bot_name
				FROM ' . SQL_PREFIX . 'sessions s
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = s.s_id
				LEFT JOIN ' . SQL_PREFIX . 'bots b
					ON s.s_bot = b.bot_id
				WHERE s.s_time > ' . intval(CURRENT_TIME - ONLINE_LENGTH) . '
				ORDER BY u.u_auth DESC, u.u_nickname, s.s_id';
		$result = Fsb::$db->query($sql);
		
		$total_visitor = 0;
		$total_user = 0;
		$total_hidden = 0;
		$ip_array = array();
		$bot_array = array();
		$id_array = array();
		while ($row = Fsb::$db->row($result))
		{
			// Bot ?
			if (Fsb::$mods->is_active('bot_list') && !is_null($row['bot_id']))
			{
				if (in_array($row['bot_id'], $bot_array))
				{
					continue;
				}
				$bot_array[] = $row['bot_id'];
				$total_visitor++;

				Fsb::$tpl->set_blocks('online', array(
					'IS_HIDDEN' =>	false,
					'NICKNAME' =>	sprintf(Fsb::$session->getStyle('other', 'nickname'), 'class="bot"', $row['bot_name'] . ' (bot)'),
				));
			}
			// Visiteur ?
			else if ($row['s_id'] == VISITOR_ID)
			{
				if (in_array($row['s_ip'], $ip_array))
				{
					continue;
				}
				$ip_array[] = $row['s_ip'];
				$total_visitor++;
			}
			else
			{
				if (in_array($row['s_id'], $id_array))
				{
					continue;
				}
				$id_array[] = $row['s_id'];
				
				if ($row['u_activate_hidden'])
				{
					$total_hidden++;
				}
				else
				{
					$total_user++;
				}

				// Autorisation de voir les invites ?
				if (!$row['u_activate_hidden'] || (Fsb::$session->auth() >= MODOSUP || Fsb::$session->id() == $row['s_id']))
				{
					Fsb::$tpl->set_blocks('online', array(
						'IS_HIDDEN' =>	$row['u_activate_hidden'],
						'NICKNAME' =>	Html::nickname($row['u_nickname'], $row['s_id'], $row['u_color']),
					));
				}
			}
		}
		Fsb::$db->free($result);
		unset($ip_array, $id_array);

		// Mise a jour du nombre max de visiteurs sur le forum
		if (($total_visitor + $total_user + $total_hidden) > Fsb::$cfg->get('max_visitors_total'))
		{
			Fsb::$cfg->update('max_visitors_total', $total_visitor + $total_user + $total_hidden);
			Fsb::$cfg->update('max_visitors_timestamp', CURRENT_TIME);
		}

		Fsb::$tpl->set_vars(array(
			'CURRENT_USER_ONLINE' =>		sprintf(Fsb::$session->lang('current_user_online'), ($total_visitor + $total_user + $total_hidden), $total_user + $total_hidden, $total_hidden, $total_visitor),
			'MAX_VISITORS' =>			sprintf(Fsb::$session->lang('stats_max_visitors'), intval(Fsb::$cfg->get('max_visitors_total')), Fsb::$session->print_date(Fsb::$cfg->get('max_visitors_timestamp'))),
		));
	}

	//
	// On affiche les membres ayant visites le forum aujourd'hui
	//
	if (Fsb::$mods->is_active('online_show_today'))
	{
		$sql = 'SELECT u_id, u_nickname, u_color, u_activate_hidden
				FROM ' . SQL_PREFIX . 'users
				WHERE u_last_visit > ' . mktime(0, 0, 0, date('m', CURRENT_TIME), date('d', CURRENT_TIME), date('Y', CURRENT_TIME)) . '
					AND u_joined <> u_last_visit
					AND u_id <> ' . VISITOR_ID . '
				ORDER BY u_auth DESC, u_nickname, u_id';
		$result = Fsb::$db->query($sql);

		$total_user_today = 0;
		$total_hidden_today = 0;
		while ($row = Fsb::$db->row($result))
		{
			if ($row['u_activate_hidden'])
			{
				$total_hidden_today++;
				if (Fsb::$session->auth() < MODOSUP && Fsb::$session->id() != $row['u_id'])
				{
					continue;
				}
			}
			else
			{
				$total_user_today++;
			}

			Fsb::$tpl->set_blocks('online_today', array(
				'NICKNAME' =>	Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
				'IS_HIDDEN' =>	$row['u_activate_hidden'],
			));
		}
		Fsb::$db->free($result);

		$lang_today_user_online = ($total_user_today + $total_hidden_today > 1) ? 'today_user_onlines' : 'today_user_online';
		Fsb::$tpl->set_vars(array(
			'TODAY_USER_ONLINE' =>		sprintf(Fsb::$session->lang($lang_today_user_online), ($total_user_today + $total_hidden_today), $total_hidden_today),
		));
	}

	//
	// On recupere les membres dont l'anniversaire est aujourd'hui
	//
	if (Fsb::$mods->is_active('online_show_birthday'))
	{
		$current_day = strval(date('d', CURRENT_TIME));
		if (strlen($current_day) == 1)
		{
			$current_day = '0' . $current_day;
		}
		
		$current_month = strval(date('m', CURRENT_TIME));
		if (strlen($current_month) == 1)
		{
			$current_month = '0' . $current_month;
		}

		// Mise en cache des anniversaires des membres une fois par jour
		if (Fsb::$cfg->get('cache_birthday') != $current_day)
		{
			Fsb::$cfg->update('cache_birthday', $current_day);
			Fsb::$db->destroy_cache('users_birthday_');
		}
		
		// Liste des anniversaires des membres
		$sql = 'SELECT u_id, u_nickname, u_birthday, u_color
				FROM ' . SQL_PREFIX . 'users
				WHERE u_birthday ' . Fsb::$db->like() . ' \'' . $current_day . '/' . $current_month . '/%\'
					AND u_id <> ' . VISITOR_ID;
		$result = Fsb::$db->query($sql, 'users_birthday_');
		$total_birthday = 0;
		while ($row = Fsb::$db->row($result))
		{
			$total_birthday++;
			$year = substr($row['u_birthday'], 6);
			Fsb::$tpl->set_blocks('online_birthday', array(
				'NICKNAME' =>	Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
				'AGE' =>		($year != '0000') ? intval(date('Y', CURRENT_TIME) - substr($row['u_birthday'], 6)) : null,
			));
		}
		Fsb::$db->free($result);

		Fsb::$tpl->set_vars(array(
			'USERS_BIRTHDAYS' =>		sprintf(String::plural('users_birthday', $total_birthday, true), $total_birthday),
		));
	}

	//
	// Affichage des evenements de la journee
	//
	if (Fsb::$mods->is_active('calendar_stats') && Fsb::$session->is_authorized('calendar_read'))
	{
		$calendar_days = Fsb::$cfg->get('calendar_date_events');
		$calendar_next = Fsb::$cfg->get('calendar_next_events');
		if ($calendar_days && $calendar_next)
		{
			$begin_timestamp =	intval(mktime(0, 0, 0, date('m', CURRENT_TIME), date('d', CURRENT_TIME), date('Y', CURRENT_TIME)));
			$end_timestamp =	intval(mktime(23, 59, 59, date('m', CURRENT_TIME + $calendar_days * ONE_DAY), date('d', CURRENT_TIME + $calendar_days * ONE_DAY), date('Y', CURRENT_TIME + $calendar_days * ONE_DAY)));
			$sql = 'SELECT c_begin, c_end, c_title, c_approve, u_id, c_view
					FROM ' . SQL_PREFIX . 'calendar
					WHERE c_end >= ' . $begin_timestamp . ' 
						AND c_begin <= ' . $end_timestamp . '
						AND (c_view = -1 OR c_view > 0)
						AND c_approve = 1
						ORDER BY c_begin, c_id'
					. (($calendar_next > 0) ? ' LIMIT ' . $calendar_next : '');
			$result = Fsb::$db->query($sql, 'calendar_' . date('d_m_Y') . '_');
			$total_event = 0;
			while ($row = Fsb::$db->row($result))
			{
				if ($row['c_view'] == -1 || in_array($row['c_view'], Fsb::$session->data['groups']))
				{
					Fsb::$tpl->set_blocks('calendar_stats', array(
						'URL' =>	sid(ROOT . 'index.' . PHPEXT . '?p=calendar&amp;mode=event&amp;time=' . $row['c_begin']),
						'NAME' =>	htmlspecialchars($row['c_title']),
						'DATE' =>	($row['c_end'] - ONE_DAY > $row['c_begin']) ? date('d/m/Y', $row['c_begin']) . ' - ' . date('d/m/Y', $row['c_end']) : date('d/m/Y', $row['c_begin']),
					));
					$total_event++;
				}
			}
			Fsb::$db->free($result);

			Fsb::$tpl->set_vars(array(
				'CALENDAR_STATS' =>		($total_event > 0) ? sprintf(Fsb::$session->lang('calendar_stats'), $total_event, $calendar_days) : sprintf(Fsb::$session->lang('calendar_stats_none'), $calendar_days),
			));

			Fsb::$tpl->set_switch('show_calendar_stats');
		}
	}
}

//
// Statistiques
//
if (Fsb::$session->is_authorized('stats_box'))
{
	Fsb::$tpl->set_vars(array(
		'FORUM_STATS' =>			sprintf(String::plural('forum_stat', Fsb::$cfg->get('total_users')), String::number_format(Fsb::$cfg->get('total_users')), String::number_format(Fsb::$cfg->get('total_posts')), String::number_format(Fsb::$cfg->get('total_topics'))),
		'LAST_USER' =>				Html::nickname(Fsb::$cfg->get('last_user_login'), Fsb::$cfg->get('last_user_id'), Fsb::$cfg->get('last_user_color')),
	));
}

/* EOF */
