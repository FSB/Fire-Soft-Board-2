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
 * Gestionaire de base de donnee
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Module du gestionaire sur lequel on se trouve
	 *
	 * @var string
	 */
	public $module;

	/**
	 * Table selectionnee
	 *
	 * @var string
	 */
	public $table;

	/**
	 * Page courante
	 *
	 * @var int
	 */
	public $page;

	/**
	 * Constructeur
	 */
	public function main()
	{
		$this->module =		htmlspecialchars(Http::request('module'));
		$this->page =		intval(Http::request('page'));
		$this->table =		Http::request('table', 'post');
		if (!$this->page)
		{
			$this->page = 1;
		}

		if ($this->table && !in_array($this->table, Fsb::$db->list_tables()))
		{
			Display::message(sprintf(Fsb::$session->lang('adm_sql_unknown_table'), $this->table));
		}

		$call = new Call($this);
		$call->module(array(
			'list' =>		array('index', 'sql', 'data', 'struct', 'export', 'cache'),
			'url' =>		'index.' . PHPEXT . '?p=tools_sql&amp;table=' . $this->table,
			'lang' =>		'adm_sql_module_',
			'default' =>	'index',
		));

		$call->post(array(
			'submit_optimise' =>	':optimise_table',
			'submit_truncate' =>	':truncate_table',
			'submit_delete' =>		':delete_table',
		));

		// Variables templates globales
		Fsb::$tpl->set_vars(array(
			'MODULE' =>		$this->module,
			'TABLE' =>		htmlspecialchars($this->table),
		));

		$call->functions(array(
			'module' => array(
				'index' =>		'show_index',
				'sql' =>		'show_sql',
				'data' =>		'show_data',
				'struct' =>		'show_struct',
				'export' =>		'show_export',
				'cache' =>		'show_cache',
			),
		));
	}

	/**
	 * Liste les tables du forum
	 */
	public function show_index()
	{
		foreach (Fsb::$db->list_tables() AS $table)
		{
			Fsb::$tpl->set_blocks('table', array(
				'NAME' =>		$table,
				'U_DATA' =>		sid('index.' . PHPEXT . '?p=tools_sql&amp;module=data&amp;table=' . $table),
				'U_STRUCT' =>	sid('index.' . PHPEXT . '?p=tools_sql&amp;module=struct&amp;table=' . $table),
			));
		}
	}

	/**
	 * Formulaire d'execution de requete
	 */
	public function show_sql()
	{
		$query =		Http::request('query');
		$parse_prefix = intval(Http::request('parse_prefix', 'post|get'));

		// Execution de requete
		if (isset($query) && !empty($query))
		{
			// Log
			if (Http::request('submit_query', 'post'))
			{
				Log::add(Log::ADMIN, 'tools_sql_log_query', $query);
			}

			$queries = String::split(';', $query);
			$begin_time = Fsb::$debug->get_time();
			foreach ($queries AS $sql)
			{
				$sql = trim($sql);
				if (!empty($sql))
				{
					if ($parse_prefix)
					{
						$sql = str_replace('fsb2_', SQL_PREFIX, $sql);
					}
					$result = Fsb::$db->query($sql);
				}
			}

			Fsb::$tpl->set_switch('exec_in');
			Fsb::$tpl->set_vars(array(
				'EXEC_IN' =>	sprintf(Fsb::$session->lang('adm_sql_exec_ok'), count($queries), substr(Fsb::$debug->get_time() - $begin_time, 0, 8)),
			));
			
			// Destruction du cache SQL a chaque requete
			Fsb::$db->destroy_cache();

			if (count($queries) === 1 && preg_match('#^(select|show)\s#si', $queries[0]) && $result)
			{
				// Pagination
				$select = Fsb::$db->rows($result);
				$count = count($select);
				if (ceil($count / 30) > 1)
				{
					Fsb::$tpl->set_switch('show_pagination');
				}

				Fsb::$tpl->set_vars(array(
					'PAGINATION' =>		Html::pagination($this->page, ceil($count / 30), sid('index.' . PHPEXT . '?p=tools_sql&amp;module=sql&amp;query=' . urlencode($query) . '&amp;parse_prefix=' . $parse_prefix)),
				));

				Fsb::$tpl->set_switch('show_result');
				$this->show_query_array($select, ($this->page - 1) * 30, 30);
			}
		}

		Fsb::$tpl->set_vars(array(
			'QUERY' =>		htmlspecialchars($query),
		));
	}

	/**
	 * Affiche les donnees de la table
	 */
	public function show_data()
	{
		if ($this->table)
		{
			// Nombre d'entrees dans la base de donnee
			$sql = 'SELECT COUNT(*) AS total
					FROM ' . Fsb::$db->escape($this->table);
			$count = Fsb::$db->get($sql, 'total');

			if (ceil($count / 30) > 1)
			{
				Fsb::$tpl->set_switch('show_pagination');
			}

			// Pagination
			Fsb::$tpl->set_vars(array(
				'PAGINATION' =>		Html::pagination($this->page, ceil($count / 30), sid('index.' . PHPEXT . '?p=tools_sql&amp;module=data&amp;table=' . $this->table)),
			));

			// Requete pour recuperer les donnees de la table
			$sql = 'SELECT *
						FROM ' . Fsb::$db->escape($this->table) . '
						LIMIT ' . (($this->page - 1) * 30) . ', 30';
			$result = Fsb::$db->query($sql);
			Fsb::$tpl->set_switch('show_data');
			$select = Fsb::$db->rows($result);
			$this->show_query_array($select);
		}
	}

	/**
	 * Affiche la structure de la table
	 */
	public function show_struct()
	{
		if ($this->table)
		{
			$sql = 'SHOW FULL FIELDS
						FROM ' . Fsb::$db->escape($this->table);
			$result = Fsb::$db->query($sql);
			if ($result && $select = Fsb::$db->rows($result))
			{
				Fsb::$tpl->set_switch('show_struct');
				$this->show_query_array($select);
			}
		}
	}

	/**
	 * Affiche le gestionaire d'export de table
	 */
	public function show_export()
	{
		// Backup lance
		if (Http::request('submit_export', 'post'))
		{
			$backup_type = intval(Http::request('backup_type', 'post'));

			$backup = Backup::factory(SQL_DBAL, $backup_type);
			$backup->multi_insert = intval(Http::request('backup_multi_insert', 'post'));
			$backup->save(intval(Http::request('backup_what', 'post')), (array) Http::request('backup_tables', 'post'));

			if ($backup_type == Backup::FTP)
			{
				Http::redirect('index.' . PHPEXT . '?p=tools_sql&module=export');
			}
		}

		// On recupere les tables pour le backup
		foreach (Fsb::$db->list_tables() AS $table)
		{
			$list_table[$table] = $table;
		}

		Fsb::$tpl->set_vars(array(
			'BACKUP_LIST_TABLE' =>		Html::make_list('backup_tables[]', $list_table, $list_table, array(
				'multiple' =>	'multiple',
				'size' =>		10,
			)),
		));
	}

	/**
	 * Affiche le gestionaire de cache SQL
	 */
	public function show_cache()
	{
		// Suppression de fichiers du cache
		if (Http::request('submit_cache_delete', 'post'))
		{
			$action = (array) Http::request('action', 'post');
			foreach ($action AS $hash)
			{
				// Petite securite au cas ou ...
				$file = str_replace('/', '', $hash);

				// Suppression du hash en cache
				Fsb::$db->cache->delete($hash);
			}

			Display::message('adm_sql_cache_deleted', 'index.' . PHPEXT . '?p=tools_sql&module=cache', 'tools_sql');

			Http::redirect();
		}

		// Liste des fichiers mis en cache SQL
		foreach (Fsb::$db->cache->list_keys() AS $hash)
		{
			// On recupere la requete en cas de cache FTP
			$query = '';
			if (Fsb::$db->cache->cache_type == 'FSB FTP cache')
			{
				$content = file_get_contents(ROOT . 'cache/sql/' . $hash . '.' . PHPEXT);

				// Parse de la requete
				$query = '';
				if (preg_match('#^<\?php\s*/\*(.*?)\*/.*?\?>$#si', $content, $match))
				{
					$query = implode("\n", array_map('trim', explode("\n", trim($match[1]))));
				}
			}

			Fsb::$tpl->set_blocks('cache', array(
				'HASH' =>		$hash,
				'QUERY' =>		nl2br(htmlspecialchars(trim($query))),
			));
		}
	}

	/**
	 * Optimisation des tables
	 */
	public function optimise_table()
	{
		$action = (array) Http::request('action', 'post');
		switch (SQL_DBAL)
		{
			case 'mysql' :
			case 'mysqli' :
				if ($action && is_array($action))
				{
					$sql = 'OPTIMIZE TABLE ' . implode(', ', $action);
					Fsb::$db->query($sql);
				}
			break;
		}

		Http::redirect('index.' . PHPEXT . '?p=tools_sql');
	}

	/**
	 * Suppression de tables
	 */
	public function delete_table()
	{
		$action = (array) Http::request('action', 'post');
		if (check_confirm())
		{
			if ($action && is_array($action))
			{
				$sql = 'DROP TABLE ' . implode(', ', $action);
				Fsb::$db->query($sql);
			}

			Http::redirect('index.' . PHPEXT . '?p=tools_sql');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=tools_sql');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('adm_sql_confirm_delete'), 'index.' . PHPEXT . '?p=tools_sql', array('action' => $action, 'submit_delete' => true));
		}
	}

	/**
	 * Vidage de tables
	 */
	public function truncate_table()
	{
		$action = (array) Http::request('action', 'post');
		if (check_confirm())
		{
			if ($action && is_array($action))
			{
				$sql = 'TRUNCATE TABLE ' . implode(', ', $action);
				Fsb::$db->query($sql);
			}

			Http::redirect('index.' . PHPEXT . '?p=tools_sql');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=tools_sql');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('adm_sql_confirm_truncate'), 'index.' . PHPEXT . '?p=tools_sql', array('action' => $action, 'submit_truncate' => true));
		}
	}
	
	/**
	 * Affiche le resultat d'une requete renvcoyant un resultat dans un tableau HTML
	 *
	 * @param array $ary Tableau contenant toutes les lignes de resultat d'une requete
	 * @param int $limit_begin Definit un point de depart pour l'affichage
	 * @param int $limit_end Definit une limite pour l'affichage
	 */
	public function show_query_array($ary, $limit_begin = null, $limit_end = null)
	{
		$colspan = 0;
		$count = count($ary);
		for ($i = 0; $i < $count; $i++)
		{
			if ($i == 0)
			{
				Fsb::$tpl->set_blocks('line', array());
				foreach ($ary[$i] AS $key => $value)
				{
					Fsb::$tpl->set_blocks('line.field', array(
						'STR' =>	$key,
					));
					$colspan++;
				}
			}

			if (is_null($limit_begin) || ($i >= $limit_begin && $i < ($limit_begin + $limit_end)))
			{
				Fsb::$tpl->set_blocks('line', array());
				foreach ($ary[$i] AS $key => $value)
				{
					Fsb::$tpl->set_blocks('line.field', array(
						'STR' =>	htmlspecialchars($value),
					));
				}
			}
		}

		Fsb::$tpl->set_vars(array(
			'COLSPAN' =>	$colspan,
		));
		
		Fsb::$tpl->set_switch('show_query');
	}
}

/* EOF */
