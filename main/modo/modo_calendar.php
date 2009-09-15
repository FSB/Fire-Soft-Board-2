<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On affiche ce module si la fonction est activee, et que l'utilisateur peut valider les evenements
if (Fsb::$mods->is_active('calendar') && Fsb::$session->is_authorized('approve_event'))
{
	$show_this_module = true;
}

/**
 * Module de moderation listant les evenements de calendriers non valides.
 *
 */
class Page_modo_calendar extends Fsb_model
{
	/**
	 * Action en cours
	 *
	 * @var unknown_type
	 */
	public $mode;

	/**
	 * Constructeur
	 *
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

	/**
	 * Affiche la liste des evenements non valides
	 *
	 */
	public function list_events()
	{
		Fsb::$tpl->set_file('modo/modo_calendar.html');

		$parser = new Parser();

		// Liste des evenements
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
			// Informations passees au parseur de message
			$parser_info = array(
				'u_id' =>			$row['u_id'],
				'p_nickname' =>		$row['u_nickname'],
				'u_auth' =>			$row['u_auth'],
				'c_id' =>			$row['c_id'],
			);

			$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;
			Fsb::$tpl->set_blocks('event', array(
				'TITLE' =>		htmlspecialchars($row['c_title']),
				'BEGIN' =>		Fsb::$session->print_date($row['c_begin']),
				'END' =>		($row['c_end'] > $row['c_begin']) ? Fsb::$session->print_date($row['c_end']) : '',
				'NICKNAME' =>	Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
				'CONTENT' =>	$parser->mapped_message($row['c_content'], 'classic', $parser_info),

				'U_EVENT' =>	sid(ROOT . 'index.' . PHPEXT . '?p=calendar&amp;mode=event&amp;time=' . $row['c_begin']),
				'U_APPROVE' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=calendar&amp;mode=approve&amp;id=' . $row['c_id']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Approuve un evenement
	 *
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