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
 * Affiche les options d'optimisation du forum
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Liste des dossiers a chmod
	 *
	 * @var array
	 */
	public $chmod = array(
		'cache_sql' =>	array('path' => 'cache/sql/', 'chmod' => 0777),
		'cache_tpl' =>	array('path' => 'cache/tpl/', 'chmod' => 0777),
		'cache_xml' =>	array('path' => 'cache/xml/', 'chmod' => 0777),
		'avatars' =>	array('path' => 'images/avatars/', 'chmod' => 0777),
		'ranks' =>		array('path' => 'images/ranks/', 'chmod' => 0777),
		'smilies' =>	array('path' => 'images/smileys/', 'chmod' => 0777),
		'save' =>		array('path' => 'mods/save/', 'chmod' => 0777),
		'upload' =>		array('path' => 'upload/', 'chmod' => 0777),
	);

	/**
	 * Nombre de messages a indexer par appel de la procedure d'indexation de la recherche
	 *
	 * @var int
	 */
	public $index_posts = 500;

	/**
	 * Module
	 *
	 * @var string
	 */
	public $module;

	/**
	 * Constructeur
	 */
	public function main()
	{
		$call = new Call($this);
		$call->module(array(
			'list' =>		array('chmod', 'process', 'search', 'replace'),
			'url' =>		'index.' . PHPEXT . '?p=tools_optimize',
			'lang' =>		'optimize_menu_',
			'default' =>	'chmod',
		));

		$call->post(array(
			'submit_chmod' =>	':chmod_forum',
			'submit_search' =>	':rebuild_search_table',
			'submit_process' =>	':submit_process',
			'submit_replace' =>	':submit_replace',
		));

		$call->functions(array(
			'module' => array(
				'search' =>		'show_search',
				'chmod' =>		'show_chmod',
				'process' =>	'show_process',
				'replace' =>	'show_replace',
			),
		));
	}

	/**
	 * Affiche la procedure pour le chmod
	 */
	public function show_chmod()
	{
		Fsb::$tpl->set_switch('optimize_chmod');

		// Affichage de l'optimisation des CHMOD
		foreach ($this->chmod AS $key => $value)
		{
			Fsb::$tpl->set_blocks('chmod', array(
				'CHMOD' =>		'0' . decoct($value['chmod']),
				'WRITE' =>		is_writable(ROOT . $value['path']),
				'PATH' =>		$value['path'],
				'EXPLAIN' =>	Fsb::$session->lang('optimize_chmod_' . $key),
				'KEY' =>		$key,
			));
		}

		// Variables
		Fsb::$tpl->set_vars(array(
			'USE_FTP' =>		(Fsb::$cfg->get('ftp_default')) ? true : false,

			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=tools_optimize&amp;module=chmod'),
		));
	}

	/**
	 * Affiche le formulaire pour la recherche
	 */
	public function show_search()
	{
		Fsb::$tpl->set_switch('optimize_search');

		// Methode de recherche
		if (Fsb::$cfg->get('search_method') == 'fulltext_fsb')
		{
			Fsb::$tpl->set_switch('fulltext_fsb');
		}

		// Variables
		Fsb::$tpl->set_vars(array(
			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=tools_optimize&amp;module=search'),
		));
	}

	/**
	 * Procedure de CHMOD du forum
	 */
	public function chmod_forum()
	{
		// Instance de la classe File
		$file = File::factory(Http::request('use_ftp', 'post'));

		$list = (array) Http::request('optimize_chmod');
		if ($list)
		{
			foreach ($list AS $key => $value)
			{
				if ($value && isset($this->chmod[$key]))
				{
					$file->chmod($this->chmod[$key]['path'], $this->chmod[$key]['chmod']);
				}
			}
		}

		Display::message('optimize_chmod_well', 'index.' . PHPEXT . '?p=tools_optimize&amp;module=chmod', 'tools_optimize');
	}

	/**
	 * Reconstruction des index pour la recherche fulltext_fsb
	 */
	public function rebuild_search_table()
	{
		$search = new Search_fulltext_fsb();

		$current_post = intval(Http::request('current_post'));
		if (!$current_post || $current_post < 0)
		{
			$current_post = 0;
		}

		if (Fsb::$cfg->get('search_method') == 'fulltext_fsb')
		{
			// On vide les tables au debut de la procedure
			if ($current_post == 0)
			{
				Fsb::$db->query_truncate('cache');
				Fsb::$db->query_truncate('search_match');
				Fsb::$db->query_truncate('search_word');
			}

			// Chargement des mots a ne pas indexer, en prenant les mots de chaque langue
			$stopwords = array();
			$fd = opendir(ROOT . 'lang/');
			while ($file = readdir($fd))
			{
				if ($file[0] != '.' && file_exists(ROOT . 'lang/' . $file . '/stopword.txt'))
				{
					$stopwords = array_merge($stopwords, file(ROOT . 'lang/' . $file . '/stopword.txt'));
				}
			}
			closedir($fd);

			$search->stopwords = array_map('trim', $stopwords);
			unset($stopwords);

			// On recupere les messages et titres
			$sql = 'SELECT p.p_id, p.p_text, t.t_title
					FROM  ' . SQL_PREFIX . 'posts p
					LEFT JOIN ' . SQL_PREFIX . 'topics t
						ON p.t_id = t.t_id
					ORDER BY p.p_id
					LIMIT ' . $current_post . ', ' . $this->index_posts;
			$result = Fsb::$db->query($sql);
			$insert_ary = array();
			while ($row = Fsb::$db->row($result))
			{
				// Indexation des messages
				$search->index($row['p_id'], preg_replace('#<[^>]*?>#si', ' ', $row['p_text']), false);

				// Indexation des titres
				$search->index($row['p_id'], $row['t_title'], true);
			}
			Fsb::$db->free($result);

			// Pourcentage d'avancement
			$percent = round(($current_post + $this->index_posts) / Fsb::$cfg->get('total_posts') * 100);
			if ($percent > 100)
			{
				$percent = 100;
			}

			// Pour cette procedure faite en plusieurs appel on active la redirection automatique
			Fsb::$session->data['u_activate_redirection'] = 8;

			if (($current_post + $this->index_posts) < Fsb::$cfg->get('total_posts'))
			{
				Display::message(sprintf(Fsb::$session->lang('optimize_search_percent'), $percent), 'index.' . PHPEXT . '?p=tools_optimize&amp;module=search&amp;submit_search= true &amp;current_post=' . ($current_post + $this->index_posts), 'optimize_search');
			}
			else
			{
				Display::message('optimize_search_well', 'index.' . PHPEXT . '?p=tools_optimize&amp;module=search', 'tools_optimize');
			}
		}
		else
		{
			Display::message('optimize_search_bad');
		}
	}

	/**
	 * Affiche les procedures programmees du forum
	 */
	public function show_process()
	{
		Fsb::$tpl->set_switch('optimize_process');

		// Liste des procedures
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'process';
		$result = Fsb::$db->query($sql, 'process_');
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('process', array(
				'ID' =>			$row['process_id'],
				'NAME' =>		Fsb::$session->lang('optimize_process_' . $row['process_function']),
				'EXPLAIN' =>	(Fsb::$session->lang('optimize_process_' . $row['process_function'] . '_explain')) ? Fsb::$session->lang('optimize_process_' . $row['process_function'] . '_explain') : null,
				'VALUE' =>		$row['process_step_timestamp'] / ONE_DAY,
				'LAST_DATE' =>	Fsb::$session->print_date($row['process_last_timestamp']),
				'NEXT_DATE' =>	Fsb::$session->print_date($row['process_last_timestamp'] + $row['process_step_timestamp']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Met a jour, execute les procedures
	 */
	public function submit_process()
	{
		$process_step =		array_map('floatval', (array) Http::request('process_step', 'post'));
		$process_launch =	array_map('intval', (array) Http::request('process_launch', 'post'));

		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'process';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			// Execution de la procedure
			$update_array = array();
			if (isset($process_launch[$row['process_id']]))
			{
				$function = $row['process_function'];
				fsb_import('process_' . $function);
				call_user_func($function);
				$update_array['process_last_timestamp'] = CURRENT_TIME;
			}

			// Mise a jour de la procedure
			if (isset($process_launch[$row['process_id']]) || $row['process_step_timestamp'] != $process_step[$row['process_id']] * ONE_DAY)
			{
				//On impose la valeur minimum si la valeur souhaitée est différente de 0 (0 = process jamais lancé, donc permis)
				if($process_step[$row['process_id']] > 0 && ($process_step[$row['process_id']] * ONE_DAY) < $row['process_step_minimum'])
				{
					$update_array['process_step_timestamp'] = $row['process_step_minimum'];
				}
				else
				{
					$update_array['process_step_timestamp'] = $process_step[$row['process_id']] * ONE_DAY;
				}
				Fsb::$db->update('process', $update_array, 'WHERE process_id = ' . $row['process_id']);
			}
		}
		Fsb::$db->free($result);
		Fsb::$db->destroy_cache('process_');

		Display::message('optimize_process_submit', 'index.' . PHPEXT . '?p=tools_optimize&amp;module=process', 'tools_optimize');
	}

	/**
	 * Formulaire de remplacement de mots
	 */
	public function show_replace()
	{
		Fsb::$tpl->set_switch('optimize_replace');
	}

	/**
	 * Remplacement des mots
	 */
	public function submit_replace()
	{
		$from = Http::request('replace_from', 'post');
		$to = Http::request('replace_to', 'post');

		$sql = 'UPDATE ' . SQL_PREFIX . 'posts
				SET p_text = REPLACE(p_text, \'' . Fsb::$db->escape(htmlspecialchars($from)) . '\', \'' . Fsb::$db->escape(htmlspecialchars($to)) . '\')';
		Fsb::$db->query($sql);

		$sql = 'UPDATE ' . SQL_PREFIX . 'topics
				SET t_title = REPLACE(t_title, \'' . Fsb::$db->escape(htmlspecialchars($from)) . '\', \'' . Fsb::$db->escape(htmlspecialchars($to)) . '\')';
		Fsb::$db->query($sql);

		$sql = 'UPDATE ' . SQL_PREFIX . 'topics
				SET t_description = REPLACE(t_description, \'' . Fsb::$db->escape(htmlspecialchars($from)) . '\', \'' . Fsb::$db->escape(htmlspecialchars($to)) . '\')';
		Fsb::$db->query($sql);

		Display::message('optimize_replace_submit', 'index.' . PHPEXT . '?p=tools_optimize&amp;module=replace', 'tools_optimize');
	}
}

/* EOF */
