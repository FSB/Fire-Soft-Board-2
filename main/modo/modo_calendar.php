<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/modo/modo_calendar.php
** | Begin :	17/09/2006
** | Last :		12/10/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

// On affiche ce module si la fonction est activée, et que l'utilisateur peut valider les évènements
if (Fsb::$mods->is_active('calendar') && Fsb::$session->is_authorized('approve_event'))
{
	$show_this_module = TRUE;
}

/*
** Module de modération listant les évènements de calendriers non validés.
*/
class Page_modo_calendar extends Fsb_model
{
	public $mode;

	/*
	** Constructeur
	*/
	public function __construct()
	{
		$this->mode = Http::request('mode');
		if (!Fsb::$session->is_authorized('approve_event'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=index');
		}

		if ($this->mode == 'approve')
		{
			$this->approve_event();
		}
		$this->list_events();
	}

	/*
	** Affiche la liste des évènements non validés
	*/
	public function list_events()
	{
		Fsb::$tpl->set_file('modo/modo_calendar.html');

		$parser = new Parser();

		// Liste des évènements
		$sql = 'SELECT c.c_id, c.c_begin, c.c_end, c.c_title, c.c_content, u.u_auth, u.u_id, u.u_nickname, u.u_color
				FROM ' . SQL_PREFIX . 'calendar c
				INNER JOIN ' . SQL_PREFIX . 'users u
					ON c.u_id = u.u_id
				WHERE c_approve = 0
					AND c_view <> 0
				ORDER BY c_begin DESC';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? TRUE : FALSE;
			Fsb::$tpl->set_blocks('event', array(
				'TITLE' =>		htmlspecialchars($row['c_title']),
				'BEGIN' =>		Fsb::$session->print_date($row['c_begin']),
				'END' =>		($row['c_end'] > $row['c_begin']) ? Fsb::$session->print_date($row['c_end']) : '',
				'NICKNAME' =>	Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
				'CONTENT' =>	$parser->mapped_message($row['c_content'], 'classic'),

				'U_EVENT' =>	sid(ROOT . 'index.' . PHPEXT . '?p=calendar&amp;mode=event&amp;time=' . $row['c_begin']),
				'U_APPROVE' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=calendar&amp;mode=approve&amp;id=' . $row['c_id']),
			));
		}
		Fsb::$db->free($result);
	}

	/*
	** Approuve un évènement
	*/
	public function approve_event()
	{
		$id = intval(Http::request('id'));
		if ($id)
		{
			$sql = 'SELECT c_title
					FROM ' . SQL_PREFIX . 'calendar
					WHERE c_id = ' . $id;
			$c_title = Fsb::$db->get($sql, 'c_title');

			if ($c_title)
			{
				Fsb::$db->update('calendar', array(
					'c_approve' =>	1,
				), 'WHERE c_id = ' . $id);

				Log::add(Log::MODO, 'log_approve_event', $c_title);
			}
		}

		Display::message('modo_calendar_well_approve', ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=calendar', 'modo_calendar');
	}
}

/* EOF */