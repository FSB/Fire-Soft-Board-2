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
 * Banissement des utilisateurs
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Mode de la frame
	 *
	 * @var string
	 */
	public $mode;
	
	/**
	 * Champ utilisé pour trier les bans
	 *
	 * @var string
	 */
	public $order;
	
	/**
	 * Sens dans lequel les bans sont affichés (ASC ou DESC)
	 *
	 * @var unknown_type
	 */
	public $direction;

	/**
	 * Constructeur
	 */
	public function main()
	{
		$this->mode =	Http::request('mode');
		$this->order =	Http::request('order');
		if ($this->order == null)
		{
			$this->order = 'ban_length';
		}

		$this->direction = strtolower(Http::request('direction'));
		if ($this->direction == null)
		{
			$this->direction = 'asc';
		}

		$call = new Call($this);
		$return = $call->post(array(
			'submit_delete' =>	':page_delete_ban',
			'submit' =>			':page_add_ban',
		));

		if (!$return)
		{
			$this->page_ban_default();
		}
	}

	/**
	 * Page par defaut pour le bannissement
	 */
	public function page_ban_default()
	{
		// suppression des bans expires
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'ban
				WHERE ban_length < \'' . CURRENT_TIME . '\'
					AND ban_length <> 0';
		Fsb::$db->query($sql);

		// Generation des listes et de la page
		$list_ban_type = Html::make_list('ban_type', 'login', array(
			'login' =>		Fsb::$session->lang('adm_ban_username'),
			'ip' =>			Fsb::$session->lang('adm_ban_ip'),
			'mail' =>		Fsb::$session->lang('adm_ban_mail'),
		));

		$list_ban_length = Html::make_list('ban_length_unit', ONE_HOUR, array(
			ONE_HOUR =>		Fsb::$session->lang('hour'),
			ONE_DAY =>		Fsb::$session->lang('day'),
			ONE_WEEK =>		Fsb::$session->lang('week'),
			ONE_MONTH =>	Fsb::$session->lang('month'),
			ONE_YEAR =>		Fsb::$session->lang('year'),
		));

		Fsb::$tpl->set_switch('ban_list');

		Fsb::$tpl->set_vars(array(
			'LIST_BAN_TYPE' =>		$list_ban_type,
			'LIST_BAN_LENGTH' =>	$list_ban_length,

			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=users_ban'),
			'U_TYPE' =>				sid('index.' . PHPEXT . '?p=users_ban&amp;order=ban_type&amp;direction=' . (($this->order == 'ban_type') ? (($this->direction == 'asc') ? 'desc' : 'asc') : 'asc')),
			'U_CONTENT' =>			sid('index.' . PHPEXT . '?p=users_ban&amp;order=ban_content&amp;direction=' . (($this->order == 'ban_content') ? (($this->direction == 'asc') ? 'desc' : 'asc') : 'asc')),
			'U_EXPIRE' =>			sid('index.' . PHPEXT . '?p=users_ban&amp;order=ban_length&amp;direction=' . (($this->order == 'ban_length') ? (($this->direction == 'asc') ? 'desc' : 'asc') : 'asc')),
			'U_REASON' =>			sid('index.' . PHPEXT . '?p=users_ban&amp;order=ban_reason&amp;direction=' . (($this->order == 'ban_reason') ? (($this->direction == 'asc') ? 'desc' : 'asc') : 'asc')),
		));

		$this->page_show_banish();
	}

	/**
	 * Affiche ligne par les ligne tout ce qui a ete banni
	 */
	public function page_show_banish()
	{
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'ban
				ORDER BY ' . $this->order . ' ' . $this->direction;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_switch('ban_exists');
			Fsb::$tpl->set_blocks('ban', array(
				'BAN_ID' =>		$row['ban_id'],
				'BAN_TYPE' =>		$row['ban_type'],
				'BAN_CONTENT' =>	$row['ban_content'],
				'BAN_REASON' =>		$row['ban_reason'],
				'BAN_EXPIRATION' =>	($row['ban_length'] > 0) ? Fsb::$session->print_date($row['ban_length']) : Fsb::$session->lang('unlimited'),
				'HAS_EXPIRED' =>	($row['ban_length'] > 0 && $row['ban_length'] < CURRENT_TIME) ? true : false,
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Banni un login, une IP ou une adresse email. Delogue tous les membres
	 * qui corresponedent au bannissement.
	 */
	public function page_add_ban()
	{
		$type =			Http::request('ban_type', 'post');
		$content =		Http::request('ban_content', 'post');
		$reason =		trim(Http::request('ban_reason', 'post'));
		$length =		intval(Http::request('ban_length', 'post'));
		$unit =			intval(Http::request('ban_length_unit', 'post'));
		$cookie =		intval(Http::request('ban_cookie', 'post'));
		$total_length = ($length > 0) ? CURRENT_TIME + ($length * $unit) : 0;

		Moderation::ban($type, $content, $reason, $total_length, $cookie);

		Log::add(Log::ADMIN, 'ban_log_add_' . $type, $content);
		Display::message('adm_ban_well_add','index.' . PHPEXT . '?p=users_ban', 'users_ban');
	}

	/**
	 * Supprime des bannissements
	 */
	public function page_delete_ban()
	{
		$action = (array) Http::request('action', 'post');
		$action = array_map('intval', $action);

		if ($action)
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'ban
						WHERE ban_id IN (' . implode(', ', $action) . ')';
			Fsb::$db->query($sql);
			Fsb::$db->destroy_cache('ban_');
			Log::add(Log::ADMIN, 'ban_delete');
		}

		Display::message(((count($action) > 1) ? 'adm_ban_wells_delete' : 'adm_ban_well_delete'), 'index.' . PHPEXT . '?p=users_ban', 'users_ban');
	}
}

/* EOF */
