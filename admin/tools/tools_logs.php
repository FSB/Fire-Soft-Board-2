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
 * Affiche les logs
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Module
	 *
	 * @var string
	 */
	public $module;
	
	/**
	 * Action
	 *
	 * @var string
	 */
	public $action;
	
	/**
	 * Page courante
	 *
	 * @var int
	 */
	public $page;

	/**
	 * Logs par page
	 *
	 * @var int
	 */
	public $per_page = 50;

	/**
	 * Equivalence des logs
	 *
	 * @var array
	 */
	var $logs = array(
		'admin' =>	Log::ADMIN,
		'modo' =>	Log::MODO,
		'error' =>	Log::ERROR,
		'email' =>	Log::EMAIL,
		'user' =>	Log::USER,
	);

	/**
	 * Constructeur
	 */
	public function main()
	{
		$this->action = Http::request('action');
		$this->page =	intval(Http::request('page'));
		if ($this->page < 1)
		{
			$this->page = 1;
		}

		$call = new Call($this);
		$call->module(array(
			'list' =>		array_keys($this->logs),
			'url' =>		'index.' . PHPEXT . '?p=tools_logs',
			'lang' =>		'adm_logs_',
			'default' =>	'admin',
		));

		$call->post(array(
			'submit_delete' =>		':page_delete_log_error',
			'submit_delete_all' =>	':page_delete_all_log_error',
		));

		$this->page_default_logs();
	}

	/**
	 * Liste les donnees recensees dans le log
	 */
	public function page_default_logs()
	{
		// Si on regarde les logs d'erreur, on affiche en plus les lignes / fichier
		if ($this->module == 'error')
		{
			Fsb::$tpl->set_switch('log_error');
		}

		Fsb::$tpl->set_switch('logs_list');

		// Lecture des logs de la page
		$logs = Log::read($this->logs[$this->module], $this->per_page, ($this->page - 1) * $this->per_page, '', ($this->module == 'user') ? true : false);

		// Afficher la pagination ?
		if ($logs['total'] / $this->per_page > 1)
		{
			Fsb::$tpl->set_switch('show_pagination');
		}

		Fsb::$tpl->set_vars(array(
			'LOG_NAME' =>		Fsb::$session->lang('adm_logs_' . $this->module),
			'PAGINATION' =>		Html::pagination($this->page, $logs['total'] / $this->per_page, 'index.' . PHPEXT . '?p=tools_logs&amp;module=' . $this->module),
			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=tools_logs&amp;module=' . $this->module),
		));

		// Liste des logs
		foreach ($logs['rows'] AS $log)
		{
			Fsb::$tpl->set_blocks('log', array(
				'ID' =>			$log['log_id'],
				'STR' =>		$log['errstr'] . (($this->module == 'user') ? ' (' . Html::nickname($log['log_user_nickname'], $log['log_user_id'], $log['log_user_color']) . ')' : ''),
				'LINE' =>		$log['log_line'],
				'FILE' =>		fsb_basename($log['log_file'], Fsb::$cfg->get('fsb_path')),
				'TIME' =>		Fsb::$session->print_date($log['log_time']),
				'USER' =>		Html::nickname($log['u_nickname'], $log['u_id'], $log['u_color']),
			));
		}
	}

	/**
	 * Supprime des lignes du fichier log. Un log "suppression" sera automatiquement
	 * rajoute dans l'administration.
	 */
	public function page_delete_log_error()
	{
		if (count($this->action))
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'logs
					WHERE log_type = ' . $this->logs[$this->module] . '
						AND log_id IN (' . implode(', ', $this->action) . ')';
			Fsb::$db->query($sql);
	
			Log::add(Log::ADMIN, 'log_delete', Fsb::$session->lang('adm_logs_' . $this->module));
		}

		Display::message('adm_log_well_delete', 'index.' . PHPEXT . '?p=tools_logs&amp;module=' . $this->module, 'logs');
	}

	/**
	 * Supprime toutes les lignes du fichier log. Un log "suppression" sera automatiquement
	 * rajoute dans l'administration.
	 */
	public function page_delete_all_log_error()
	{
		$sql = 'DELETE FROM ' . SQL_PREFIX . 'logs
				WHERE log_type = ' . $this->logs[$this->module];
		Fsb::$db->query($sql);

		Log::add(Log::ADMIN, 'log_delete_all', Fsb::$session->lang('adm_logs_' . $this->module));

		Display::message('adm_log_well_delete', 'index.' . PHPEXT . '?p=tools_logs&amp;module=' . $this->module, 'logs');
	}
}

/* EOF */
