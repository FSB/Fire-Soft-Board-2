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
 * Permet une recherche dans les messages du forum en fonction de divers parametres.
 * Le fonctionement global de la page : on recupere une liste de message concerne par la recherche via les methodes
 * search_*() dans la propriete $this->idx, puis on appel la methode print_result() pour afficher le resultat en
 * fonction des messages recuperes.
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
	 * Mode de la page
	 *
	 * @var string
	 */
	public $mode;

	/**
	 * Argument $id
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Donnees du formulaire pour la recherche Mots clés
	 * 
	 */
	public $keywords;
	
	/**
	 * Donnees du formulaire pour la recherche Auteur
	 * 
	 */
	public $author;
	
	/**
	 * Donnees du formulaire pour la recherche Recherche dans messages et/ou sujets
	 * 
	 */
	public $in;
	
	/**
	 * Donnees du formulaire pour la recherche Forme d'affichage
	 * 
	 */
	public $print;
	
	/**
	 * Donnees du formulaire pour la recherche Forums
	 * 
	 */
	public $forums;
	
	/**
	 * Donnees du formulaire pour la recherche Sujets
	 * 
	 */
	public $topic;
	
	/**
	 * Donnees du formulaire pour la recherche Date
	 * 
	 */
	public $date;
	
	/**
	 * Donnees du formulaire pour la recherche Ordre
	 * 
	 */
	public $order;
	
	/**
	 * Donnees du formulaire pour la recherche Asc/Desc
	 * 
	 */
	public $direction;

	/**
	 * Objet de recherche
	 *
	 * @var Search
	 */
	public $search;

	/**
	 * ID des messages pour le resultat de la recherche
	 *
	 * @var array
	 */
	public $idx = array();

	/**
	 * Navigation
	 *
	 * @var array
	 */
	public $nav = array();

	/**
	 * Page courante
	 *
	 * @var string
	 */
	public $page;

	/**
	 * Module de menu
	 *
	 * @var string
	 */
	public $module;

	/**
	 * Recherche independante (condition)
	 *
	 * @var string
	 */
	public $where = null;
	
	/**
	 * Recherche independante (nombre)
	 *
	 * @var int
	 */
	public $count = null;

	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		// Type de recherche (fulltext_mysql, fulltext_fsb ou like)
		$this->search = Search::factory();

		$this->mode =		Http::request('mode');
		$this->print =		Http::request('print');
		$this->author =		Http::request('author');
		$this->in =			Http::request('in');
		$this->order =		Http::request('order');
		$this->direction =	strtolower(Http::request('direction'));
		$this->date =		intval(Http::request('date'));
		$this->page =		intval(Http::request('page'));
		$this->id =			intval(Http::request('id'));
		$this->topic =		intval(Http::request('topic'));
		$this->keywords =	trim(Http::request('keywords'));
		$this->forums =		(array) Http::request('forums');

		if (!is_array($this->in) && !isset($this->in))
		{
			$this->in = array();
		}
		else if (!is_array($this->in))
		{
			$this->in = array($this->in);
		}
		$this->in = array_flip($this->in);

		$this->forums = array_map('intval', $this->forums);
		if (!$this->forums)
		{
			$this->forums = array_keys(Fsb::$session->data['auth']);
		}
		$this->forums = array_intersect(Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read')), $this->forums);
                /* Si le visiteur n'a aucun droit sur le forum, on le redirige
                 * vers l'index, pas la peine d'effectuer la recherche */
                if (!$this->forums)
                        Display::message('search_no_right', ROOT . 'index.' . PHPEXT, 'forum_index');
                
		if ($this->direction != 'asc' && $this->direction != 'desc')
		{
			$this->direction = 'desc';
		}

		if (!in_array($this->order, array('t_last_p_time', 't_title', 't_total_view', 't_total_post')))
		{
			$this->order = 't_last_p_time';
		}

		$call = new Call($this);
		$call->post(array(
			'submit' =>				'result',
			'submit_check' =>		':check_topics',
		));

		$call->functions(array(
			'mode' => array(
				'result' =>			'search_result',
				'author' =>			'search_author',
				'author_topic' =>	'search_author_topic',
				'ownposts' =>		'search_ownposts',
				'ownnewposts' =>	'search_ownnewposts',
				'newposts' =>		'search_newposts',
				'myself' =>			'search_myself',
				'notification' =>	'search_notification',
				'default' =>		'search_form',
			),
		));
	}

	/**
	 * Formulaire de recherche
	 *
	 */
	public function search_form()
	{
		// Suivant le type de recherche l'explication change
		$search_explain = $this->search->explain();

		// Liste des dates et des tris
		$list_date = array(
			0 =>			Fsb::$session->lang('search_date_all'),
			ONE_DAY =>		Fsb::$session->lang('search_date_one_day'),
			ONE_WEEK =>		Fsb::$session->lang('search_date_one_week'),
			2 * ONE_WEEK =>	Fsb::$session->lang('search_date_two_weeks'),
			ONE_MONTH =>	Fsb::$session->lang('search_date_one_month'),
			2 * ONE_MONTH =>Fsb::$session->lang('search_date_two_months'),
			6 * ONE_MONTH =>Fsb::$session->lang('search_date_six_months'),
			ONE_YEAR =>		Fsb::$session->lang('search_date_one_year'),
			2 * ONE_YEAR =>	Fsb::$session->lang('search_date_two_years'),
		);

		$list_order = array(
			't_last_p_time' =>	Fsb::$session->lang('search_order_time'),
			't_title' =>		Fsb::$session->lang('search_order_title'),
			't_total_view' =>	Fsb::$session->lang('search_order_view'),
			't_total_post' =>	Fsb::$session->lang('search_order_answer'),
		);

		$list_direction = array(
			'asc' =>	Fsb::$session->lang('asc'),
			'desc' =>	Fsb::$session->lang('desc'),
		);

		Fsb::$tpl->set_file('forum/forum_search.html');
		Fsb::$tpl->set_vars(array(
			'SEARCH_EXPLAIN' =>	$search_explain,
			'LIST_DATE' =>		Html::make_list('date', 0, $list_date, array('id' => 'list_date_id')),
			'LIST_ORDER' =>		Html::make_list('order', 't_last_p_time', $list_order, array('id' => 'list_order_id')),
			'LIST_DIRECTION' =>	Html::make_list('direction', 'desc', $list_direction),

			'U_ACTION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=result'),
		));

		$this->generate_forum_list();
	}

	/**
	 * Fonction classique de recherche, via le formulaire de recherche.
	 * A chaque soumission du formulaire de recherche un cache pour la recherche est
	 * cree dans la table search_result. Le cache est recupere quand le mode result est passe
	 * en parametre.
	 *
	 */
	public function search_result()
	{
		// Instance du cache
		$cache = Cache::factory('search', 'sql', 'cache_hash = \'' . Fsb::$db->escape(Fsb::$session->sid) . '\'');

		// Cache de recherche ?
		$cache_result = false;
		$this->mode = 'result';
		if (!Http::request('submit') && $cache->exists(Fsb::$session->sid))
		{
			$cache_result = $cache->get(Fsb::$session->sid);
			if ($cache_result)
			{
				$this->idx = $cache_result['idx'];
				$this->print = $cache_result['print'];
				$this->print_result();
				return ;
			}
		}

		// Aucun cache de recherche trouve ...
		if (!$cache_result)
		{
			// Verification du formulaire
			if (empty($this->keywords) && empty($this->author))
			{
				Display::message('search_keywords_empty');
			}

			if (!isset($this->in['post']) && !isset($this->in['title']))
			{
				Display::message('search_in_empty');
			}

			$this->search->search_in_post =		(isset($this->in['post'])) ? true : false;
			$this->search->search_in_title =	(isset($this->in['title'])) ? true : false;
			$this->search->search_link =		strtolower(Http::request('keywords_link', 'post'));

			$this->idx = $this->search->launch($this->keywords, $this->author, $this->forums, $this->topic, $this->date);
			$this->print_result();

			// Suppression des anciens cache
			$cache->garbage_colector(ONE_DAY);

			// Creation du cache de recherche
			$cache->put(Fsb::$session->sid, array('print' => $this->print, 'idx' => $this->idx));
		}
	}

	/**
	 * Cherche tous les messages d'un membre
	 *
	 * @param string $nickname Pseudonyme de l'auteur
	 */
	public function search_author($nickname = null)
	{
		if ($this->id == VISITOR_ID)
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		if ($nickname)
		{
			$sql = 'SELECT u_id
					FROM ' . SQL_PREFIX . 'users
					WHERE u_nickname = \'' . Fsb::$db->escape($nickname) . '\'';
			$this->id = intval(Fsb::$db->get($sql, 'u_id'));
		}
		else
		{
			$sql = 'SELECT u_nickname
					FROM ' . SQL_PREFIX . 'users
					WHERE u_id = ' . $this->id;
			$nickname = Fsb::$db->get($sql, 'u_nickname');
		}
		$this->tag_title = sprintf(Fsb::$session->lang('search_author_title'), $nickname) . Fsb::$session->getStyle('other', 'title_separator') . Fsb::$cfg->get('forum_name');

		if (!$this->id)
		{
			Display::message('user_not_exists');
		}

		// Balise META pour la syndications RSS sur les derniers messages du membre
		Http::add_meta('link', array(
			'rel' =>		'alternate',
			'type' =>		'application/rss+xml',
			'title' =>		Fsb::$session->lang('rss'),
			'href' =>		sid(ROOT . 'index.' . PHPEXT . '?p=rss&amp;mode=user&amp;id=' . $this->id),
		));

		$sql = 'SELECT COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'posts
				WHERE u_id = ' . $this->id . '
					AND u_id <> ' . VISITOR_ID . '
					AND f_id IN (' . implode(', ', $this->forums) . ')';
		$this->count = Fsb::$db->get($sql, 'total');
		$this->where = 'p.u_id = ' . intval($this->id);

		// Affichage sous forme de messages
		$this->print = 'post';

		$this->print_result();
	}

	/**
	 * Cherche tous les sujets d'un membre
	 *
	 * @param string $nickname Pseudonyme de l'auteur
	 */
	public function search_author_topic($nickname = null)
	{
		if ($this->id == VISITOR_ID)
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		if ($nickname)
		{
			$sql = 'SELECT u_id
					FROM ' . SQL_PREFIX . 'users
					WHERE u_nickname = \'' . Fsb::$db->escape($nickname) . '\'';
			$this->id = Fsb::$db->get($sql, 'u_id');
		}
		else
		{
			$sql = 'SELECT u_nickname
					FROM ' . SQL_PREFIX . 'users
					WHERE u_id = ' . $this->id;
			$nickname = Fsb::$db->get($sql, 'u_nickname');
		}
		$this->tag_title = sprintf(Fsb::$session->lang('search_author_topics_title'), $nickname) . Fsb::$session->getStyle('other', 'title_separator') . Fsb::$cfg->get('forum_name');

		if (!$this->id)
		{
			Display::message('user_not_exists');
		}

		$sql = 'SELECT COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'topics
				WHERE u_id = ' . $this->id . '
					AND f_id IN (' . implode(', ', $this->forums) . ')';
		$this->count = Fsb::$db->get($sql, 'total');

		$sql = 'SELECT t_first_p_id
				FROM ' . SQL_PREFIX . 'topics 
				WHERE u_id = ' . intval($this->id);
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$this->idx[] = $row['t_first_p_id'];
		}
		Fsb::$db->free($result);

		// Affichage sous forme de messages
		$this->print = 'topic';

		$this->print_result();
	}

	/**
	 * Cherche les sujets dans lesquels on a poste
	 *
	 */
	public function search_ownposts()
	{
		if (!Fsb::$session->is_logged())
		{
			Display::message('not_allowed');
		}

		$this->where = 'p.u_id = ' . intval(Fsb::$session->id());

		// Affichage sous forme de sujets
		$this->print = 'topic';

		$this->print_result();
	}

	/**
	 * Cherche tous les sujets non lus auxquels on a participe
	 *
	 */
	public function search_ownnewposts()
	{
		$this->search_newposts(true);
	}

	/**
	 * Cherche tous les messages non lus
	 *
	 */
	public function search_newposts($self = false)
	{
		// L'invite ne peut acceder a cette page
		if (!Fsb::$session->is_logged())
		{
			Display::message('not_allowed');
		}

		$this->nav[] = array(
			'name' =>		Fsb::$session->lang('search_nav_not_read'),
			'url' =>		'',
		);

		// Recuperation du module
		$unread_array = array('list', 'forums');
		$this->module = Http::request('module');
		if (!$this->module || !in_array($this->module, $unread_array))
		{
			if (!$this->module = Http::getcookie('unread_module'))
			{
				$this->module = 'list';
			}
		}
		Http::cookie('unread_module', $this->module, CURRENT_TIME + ONE_MONTH);

		// Creation de la liste des modules
		foreach ($unread_array AS $m)
		{
			Fsb::$tpl->set_blocks('module', array(
				'IS_SELECT' =>	($this->module == $m) ? true : false,
				'URL' =>		sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=' . $this->mode . '&amp;module=' . $m),
				'NAME' =>		Fsb::$session->lang('search_unread_module_' . $m),
			));
		}

		Fsb::$tpl->set_vars(array(
			'MENU_HEADER_TITLE' =>	Fsb::$session->lang('search_title_not_read'),
		));

		$post_query = '';
		$where = '';
		if ($self)
		{
			$post_query = ' LEFT JOIN ' . SQL_PREFIX . 'posts p
				ON t.t_id = p.t_id';
			$where = ' AND p.u_id = tr.u_id';
		}

		// Generation des messages
		$sql = 'SELECT t.t_last_p_id
				FROM ' . SQL_PREFIX . 'topics t
				LEFT JOIN ' . SQL_PREFIX . 'topics_read tr
					ON t.t_id = tr.t_id
						AND tr.u_id = ' . intval(Fsb::$session->id()) . 
				$post_query . '
				WHERE (tr.p_id IS null OR tr.p_id < t.t_last_p_id)
					AND t.t_last_p_time > ' . MAX_UNREAD_TOPIC_TIME .
					$where;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$this->idx[] = $row['t_last_p_id'];
		}
		Fsb::$db->free($result);

		switch ($this->module)
		{
			case 'list' :
				$this->print = 'topic';
			break;

			case 'forums' :
				$this->print = 'forum';
			break;
		}

		Fsb::$tpl->set_switch('use_module');
		Fsb::$tpl->set_switch('can_check');
		Fsb::$tpl->set_vars(array(
			'CHECK_LANG' =>		Fsb::$session->lang('search_markread'),
		));

		$this->print_result();
	}

	/**
	 * Cherche tous les sujets qu'on surveille
	 *
	 */
	public function search_notification()
	{
		// L'invite ne peut acceder a cette page
		if (!Fsb::$session->is_logged())
		{
			Display::message('not_allowed');
		}

		$sql = 'SELECT t.t_first_p_id
				FROM ' . SQL_PREFIX . 'topics_notification tn
				INNER JOIN ' . SQL_PREFIX . 'topics t
					ON tn.t_id = t.t_id
				WHERE tn.u_id = ' . intval(Fsb::$session->id());
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$this->idx[] = $row['t_first_p_id'];
		}
		Fsb::$db->free($result);

		// Affichage sous forme de sujet
		$this->print = 'topic';

		// On peut cocher les sujets pour arreter de les surveiller
		Fsb::$tpl->set_switch('can_check');
		Fsb::$tpl->set_vars(array(
			'CHECK_LANG' =>		Fsb::$session->lang('search_notification'),
		));

		$this->nav[] = array(
			'name' =>		Fsb::$session->lang('search_nav_notification'),
			'url' =>		'',
		);

		$this->print_result();
	}

	/**
	 * Traite les sujets coches
	 *
	 */
	public function check_topics()
	{
		switch ($this->mode)
		{
			case 'newposts' :
			case 'ownnewposts' :
				$action_f = (array) Http::request('action_f', 'post');
				if ($action_f)
				{
					$action_f = array_map('intval', $action_f);
					Forum::markread('forum', $action_f);
				}

				$action = Http::request('action', 'post');
				if ($action)
				{
					$action = array_map('intval', $action);
					Forum::markread('topic', $action);
				}
			break;

			case 'notification' :
				$action = Http::request('action', 'post');

				if ($action)
				{
					$action = array_map('intval', $action);

					$sql = 'DELETE FROM ' . SQL_PREFIX . 'topics_notification
							WHERE t_id IN (' . implode(', ', $action) . ')
								AND u_id = ' . Fsb::$session->id();
					Fsb::$db->query($sql);
				}
			break;
		}

		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=search&mode=' . $this->mode);
	}

	/**
	 * Affiche le resultat de la recherche, en se basant sur la propriete
	 * $this->idx qui a recu le tableau des messages trouves pour la recherche
	 *
	 */
	public function print_result()
	{
		if ($this->page < 1)
		{
			$this->page = 1;
		}

		$implode_idx = implode(', ', $this->idx);
		$total = 0;
		$total_page = 0;

		// Affichage des resultats en messages ou en sujets
		switch ($this->print)
		{
			case 'post' :
				//
				// Gestion de l'affichage des resultats de la recherche sous forme de messages
				//
				Fsb::$tpl->set_file('forum/forum_search_post.html');

				if ($this->idx || $this->where)
				{
					$parser = new Parser();

					// Nombre de message
					if (is_null($this->count))
					{
						$sql = 'SELECT COUNT(*) AS total
								FROM ' . SQL_PREFIX . 'posts p
								WHERE ' . (($this->where) ? $this->where : 'p.p_id IN (' . $implode_idx . ')') . '
									AND p.p_approve = ' . IS_APPROVED
									. (($this->forums) ? ' AND f_id IN (' . implode(', ', $this->forums) . ')' : '')
									. (($forums_idx = Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read'))) ? ' AND f_id IN (' . implode(', ', $forums_idx) . ')' : '');
						$result = Fsb::$db->query($sql);
						$tmp = Fsb::$db->row($result);
						Fsb::$db->free($result);
						$total = $tmp['total'];
					}
					else
					{
						$total = $this->count;
					}
					$total_page = ceil($total / Fsb::$cfg->get('post_per_page'));

					// Recuperation des mots clefs
					$split_words = array();
					if ($this->keywords)
					{
						foreach (preg_split('#[^\S]+#si', utf8_decode($this->keywords)) AS $word)
						{
							if ($word)
							{
								$split_words[] = utf8_encode($word);
							}
						}
					}

					// Pour l'affichage par message, on treansforme l'ordre de la date du dernier message par la date du message
					if ($this->order == 't_last_p_time')
					{
						$order = 'p.p_time ' . $this->direction;
					}
					else
					{
						$order = 't.' . $this->order . ' ' . $this->direction . ', p.p_time';
					}

					// Liste des messages
					$sql = 'SELECT p.p_id, p.p_text, p.p_nickname, p.u_id, p.p_time, p.p_map, t.t_id, t.t_title, t.t_first_p_id, f.f_id, f.f_name, f.f_color, cat.f_id AS cat_id, cat.f_name AS cat_name, u.u_color, u.u_auth
							FROM ' . SQL_PREFIX . 'posts p
							INNER JOIN ' . SQL_PREFIX . 'topics t
								ON p.t_id = t.t_id
							LEFT JOIN ' . SQL_PREFIX . 'forums f
								ON f.f_id = p.f_id
							LEFT JOIN ' . SQL_PREFIX . 'forums cat
								ON cat.f_id = f.f_cat_id
							LEFT JOIN ' . SQL_PREFIX . 'users u
								ON p.u_id = u.u_id
							WHERE ' . (($this->where) ? $this->where : 'p.p_id IN (' . $implode_idx . ')') . '
								AND p.p_approve = ' . IS_APPROVED
							. (($this->forums) ? ' AND f.f_id IN (' . implode(', ', $this->forums) . ')' : '')
							. (($forums_idx = Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read'))) ? ' AND f.f_id IN (' . implode(', ', $forums_idx) . ')' : '') . '
							ORDER BY ' . $order . '
							LIMIT ' . (($this->page - 1) * Fsb::$cfg->get('post_per_page')) . ', ' . Fsb::$cfg->get('post_per_page');
					$result = Fsb::$db->query($sql);
					while ($row = Fsb::$db->row($result))
					{
						// Informations passees au parseur de message
						$parser_info = array(
							'u_id' =>			$row['u_id'],
							'p_nickname' =>		$row['p_nickname'],
							'u_auth' =>			$row['u_auth'],
							'f_id' =>			$row['f_id'],
							't_id' =>			$row['t_id'],
						);

						// parse du message
						$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;
						$text = $parser->mapped_message($row['p_text'], $row['p_map'], $parser_info);
						$post_title = Parser::title($row['t_title']);

						// Highlight des mots clefs
						foreach ($split_words AS $word)
						{
							$text = preg_replace('#(?!<.*)(?<!\w)(' . preg_quote($word, '#') . ')(?!\w|[^<>]*(?:</s(?:cript|tyle))?>)#is', '<span class="highlight_search">\\1</span>', $text);
							$post_title = preg_replace('#(^|\s)(' . preg_quote($word, '#') . ')(\s|$)#i', '\\1<b class="highlight_search">\\2</b>\\3', $post_title);
						}

						Fsb::$tpl->set_blocks('result', array(
							'NICKNAME' =>		Html::nickname($row['p_nickname'], $row['u_id'], $row['u_color']),
							'DATE' =>			Fsb::$session->print_date($row['p_time']),
							'CONTENT' =>		$text,
							'CAT_NAME' =>		$row['cat_name'],
							'FORUM_NAME' =>		Html::forumname($row['f_name'], $row['f_id'], $row['f_color']),
							'TOPIC_NAME' =>		$post_title,

							'U_TOPIC' =>		sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;p_id=' . $row['p_id']) . '#p' . $row['p_id'],
							'U_CAT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=index&amp;cat=' . $row['cat_id']),
						));
					}
					Fsb::$db->free($result);
				}
			break;

			case 'forum' :
				//
				// Gestion de l'affichage des resultats de la recherche sous forme de sujets, regroupes par forums
				//
				Fsb::$tpl->set_file('forum/forum_search_forum.html');

				if ($this->idx)
				{
					// Calcul du nombre de sujets
					switch (SQL_DBAL)
					{
						case 'sqlite' :
							$sql = 'SELECT COUNT(*) AS total
									FROM (
										SELECT DISTINCT t_id
										FROM ' . SQL_PREFIX . 'posts
										WHERE p_id IN (' . $implode_idx . ')
											AND p_approve = ' . IS_APPROVED
											. (($this->forums) ? ' AND f_id IN (' . implode(', ', $this->forums) . ')' : '')
											. (($forums_idx = Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read'))) ? ' AND f_id IN (' . implode(', ', $forums_idx) . ')' : '') . '
									)';
						break;

						default :
							$sql = 'SELECT COUNT(DISTINCT t_id) AS total
									FROM ' . SQL_PREFIX . 'posts
									WHERE p_id IN (' . $implode_idx . ')
										AND p_approve = ' . IS_APPROVED
										. (($this->forums) ? ' AND f_id IN (' . implode(', ', $this->forums) . ')' : '')
										. (($forums_idx = Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read'))) ? ' AND f_id IN (' . implode(', ', $forums_idx) . ')' : '');
						break;
					}
					$total = Fsb::$db->get($sql, 'total');
					$total_page = ceil($total / Fsb::$cfg->get('topic_per_page'));

					// Liste des sujets
					$sql = 'SELECT t.t_id, t.t_title, t.t_time, t.t_last_p_time, t.t_total_view, t.t_total_post, t.t_last_p_nickname, t.t_last_u_id, t.t_last_p_nickname, t.t_last_p_id, t.t_description, t.t_type, t.t_status, f.f_id, f.f_name, cat.f_id AS cat_id, cat.f_name AS cat_name, tr.p_id AS last_unread_id, u.u_color, owner.u_id AS owner_id, owner.u_nickname AS owner_nickname, owner.u_color AS owner_color
							FROM ' . SQL_PREFIX . 'posts p
							INNER JOIN ' . SQL_PREFIX . 'topics t
								ON p.t_id = t.t_id
							LEFT JOIN ' . SQL_PREFIX . 'forums f
								ON p.f_id = f.f_id
							LEFT JOIN ' . SQL_PREFIX . 'forums cat
								ON cat.f_id = f.f_cat_id
							LEFT JOIN ' . SQL_PREFIX . 'users u
								ON u.u_id = t.t_last_u_id
							LEFT JOIN ' . SQL_PREFIX . 'users owner
								ON t.u_id = owner.u_id
							LEFT JOIN ' . SQL_PREFIX . 'topics_read tr
								ON p.t_id = tr.t_id
									AND tr.u_id = ' . intval(Fsb::$session->id()) . '
							WHERE p.p_id IN (' . implode(', ', $this->idx) . ')
								AND p.p_approve = ' . IS_APPROVED
							. (($this->forums) ? ' AND f.f_id IN (' . implode(', ', $this->forums) . ')' : '')
							. (($forums_idx = Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read'))) ? ' AND f.f_id IN (' . implode(', ', $forums_idx) . ')' : '') . '
							GROUP BY t.t_id, t.t_title, t.t_time, t.t_last_p_time, t.t_total_view, t.t_total_post, t.t_last_p_nickname, t.t_last_u_id, t.t_last_p_nickname, t.t_last_p_id, t.t_description, t.t_type, t.t_status, f.f_id, f.f_name, cat_id, cat_name, last_unread_id, u.u_color, owner_id, owner_nickname, owner_color, f.f_left
							ORDER BY f.f_left, t.' . $this->order . ' ' . $this->direction . '
							LIMIT ' . (($this->page - 1) * Fsb::$cfg->get('topic_per_page')) . ', ' . Fsb::$cfg->get('topic_per_page');
					$result = Fsb::$db->query($sql);
					$forum_id = null;
					while ($row = Fsb::$db->row($result))
					{
						if ($forum_id != $row['f_id'])
						{
							Fsb::$tpl->set_blocks('f', array(
								'ID' =>				$row['f_id'],
								'FORUM' =>			$row['f_name'],
								'CAT' =>			$row['cat_name'],
								'U_FORUM' =>		sid(ROOT . 'index.' . PHPEXT . '?p=forum&amp;f_id=' . $row['f_id']),
								'U_CAT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=index&amp;cat=' . $row['cat_id']),
							));
							$forum_id = $row['f_id'];
						}

						// Pagination du sujet
						$total_topic_page = $row['t_total_post'] / Fsb::$cfg->get('post_per_page');
						$topic_pagination = ($total_topic_page > 1) ? Html::pagination(0, $total_topic_page, 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $row['t_id'], null, true) : false;

						// Sujet lu ?
						list($is_read, $last_url) = check_read_post($row['t_last_p_id'], $row['t_last_p_time'], $row['t_id'], $row['last_unread_id']);

						// Image du sujet
						if ($GLOBALS['_topic_type'][$row['t_type']] == 'post')
						{
							$topic_img = (($is_read) ? '' : 'new_') . 'post' . (($row['t_status'] == LOCK) ? '_locked' : '');
						}
						else
						{
							$topic_img = (($is_read) ? '' : 'new_') . 'announce';
						}

						Fsb::$tpl->set_blocks('f.result', array(
							'ID' =>				$row['t_id'],
							'TITLE' =>			Parser::title($row['t_title']),
							'DESC' =>			htmlspecialchars(String::truncate($row['t_description'], 50)),
							'IMG' =>			Fsb::$session->img($topic_img),
							'VIEWS' =>			$row['t_total_view'],
							'ANSWERS' =>		$row['t_total_post'] - 1,
							'NICKNAME' =>		Html::nickname($row['t_last_p_nickname'], $row['t_last_u_id'], $row['u_color']),
							'OWNER' =>			Html::nickname($row['owner_nickname'], $row['owner_id'], $row['owner_color']),
							'FIRST_DATE' =>		Fsb::$session->print_date($row['t_time']),
							'DATE' =>			Fsb::$session->print_date($row['t_last_p_time']),
							'PAGINATION' =>		$topic_pagination,
							'UNREAD' =>			($is_read) ? false : true,

							'U_TOPIC' =>		sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $row['t_id']),
							'U_LOGIN' =>		sid(ROOT . 'index.' . PHPEXT . '?p=userprofile&amp;id=' . $row['t_last_u_id']),
							'U_LAST' =>			sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;' . $last_url),
						));
					}
					Fsb::$db->free($result);
				}
			break;

			case 'topic' :
			default :
				//
				// Gestion de l'affichage des resultats de la recherche sous forme de sujets
				//
				Fsb::$tpl->set_file('forum/forum_search_topic.html');

				if ($this->idx || $this->where)
				{
					// Calcul du nombre de sujets
					if (is_null($this->count))
					{
						switch (SQL_DBAL)
						{
							case 'sqlite' :
								$sql = 'SELECT COUNT(*) AS total
										FROM (
											SELECT DISTINCT t_id
											FROM ' . SQL_PREFIX . 'posts p
											WHERE ' . (($this->where) ? $this->where : 'p.p_id IN (' . $implode_idx . ')') . '
												AND p.p_approve = ' . IS_APPROVED
												. (($this->forums) ? ' AND f_id IN (' . implode(', ', $this->forums) . ')' : '')
												. (($forums_idx = Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read'))) ? ' AND f_id IN (' . implode(', ', $forums_idx) . ')' : '') . '
										)';
							break;

							default :
								$sql = 'SELECT COUNT(DISTINCT p.t_id) AS total
										FROM ' . SQL_PREFIX . 'posts p
										WHERE ' . (($this->where) ? $this->where : 'p.p_id IN (' . $implode_idx . ')') . '
											AND p.p_approve = ' . IS_APPROVED
											. (($this->forums) ? ' AND f_id IN (' . implode(', ', $this->forums) . ')' : '')
											. (($forums_idx = Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read'))) ? ' AND f_id IN (' . implode(', ', $forums_idx) . ')' : '');
							break;
						}
						$total = Fsb::$db->get($sql, 'total');
					}
					else
					{
						$total = $this->count;
					}
					$total_page = ceil($total / Fsb::$cfg->get('topic_per_page'));

					// Liste des sujets
					$sql = 'SELECT t.t_id, t.t_title, t.t_time, t.t_last_p_time, t.t_total_view, t.t_total_post, t.t_last_p_nickname, t.t_last_u_id, t.t_last_p_nickname, t.t_last_p_id, t.t_description, t.t_type, t.t_status, f.f_id, f.f_name, f.f_color, cat.f_id AS cat_id, cat.f_name AS cat_name, tr.p_id AS last_unread_id, u.u_color, owner.u_id AS owner_id, owner.u_nickname AS owner_nickname, owner.u_color AS owner_color
							FROM ' . SQL_PREFIX . 'posts p
							INNER JOIN ' . SQL_PREFIX . 'topics t
								ON p.t_id = t.t_id
							LEFT JOIN ' . SQL_PREFIX . 'forums f
								ON p.f_id = f.f_id
							LEFT JOIN ' . SQL_PREFIX . 'forums cat
								ON cat.f_id = f.f_cat_id
							LEFT JOIN ' . SQL_PREFIX . 'users u
								ON u.u_id = t.t_last_u_id
							LEFT JOIN ' . SQL_PREFIX . 'users owner
								ON t.u_id = owner.u_id
							LEFT JOIN ' . SQL_PREFIX . 'topics_read tr
								ON p.t_id = tr.t_id
									AND tr.u_id = ' . intval(Fsb::$session->id()) . '
							WHERE ' . (($this->where) ? $this->where : 'p.p_id IN (' . $implode_idx . ')') . '
								AND p.p_approve = ' . IS_APPROVED
							. (($this->forums) ? ' AND f.f_id IN (' . implode(', ', $this->forums) . ')' : '')
							. (($forums_idx = Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read'))) ? ' AND f.f_id IN (' . implode(', ', $forums_idx) . ')' : '') . '
							GROUP BY t.t_id, t.t_title, t.t_time, t.t_last_p_time, t.t_total_view, t.t_total_post, t.t_last_p_nickname, t.t_last_u_id, t.t_last_p_nickname, t.t_last_p_id, t.t_description, t.t_type, t.t_status, f.f_id, f.f_name, f.f_color, cat_id, cat_name, last_unread_id, u.u_color, owner_id, owner_nickname, owner_color
							ORDER BY t.' . $this->order . ' ' . $this->direction . '
							LIMIT ' . (($this->page - 1) * Fsb::$cfg->get('topic_per_page')) . ', ' . Fsb::$cfg->get('topic_per_page');
					$result = Fsb::$db->query($sql);
					while ($row = Fsb::$db->row($result))
					{
						// Sujet lu ?
						list($is_read, $last_url) = check_read_post($row['t_last_p_id'], $row['t_last_p_time'], $row['t_id'], $row['last_unread_id']);

						// Pagination du sujet
						$total_page_topic = $row['t_total_post'] / Fsb::$cfg->get('post_per_page');
						$topic_pagination = ($total_page_topic > 1) ? Html::pagination(0, $total_page_topic, 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $row['t_id'], null, true) : false;
					
						// Image du sujet
						if ($GLOBALS['_topic_type'][$row['t_type']] == 'post')
						{
							$topic_img = (($is_read) ? '' : 'new_') . 'post' . (($row['t_status'] == LOCK) ? '_locked' : '');
						}
						else
						{
							$topic_img = (($is_read) ? '' : 'new_') . 'announce';
						}

						Fsb::$tpl->set_blocks('result', array(
							'ID' =>				$row['t_id'],
							'TITLE' =>			Parser::title($row['t_title']),
							'DESC' =>			htmlspecialchars(String::truncate($row['t_description'], 50)),
							'FORUM' =>			Html::forumname($row['f_name'], $row['f_id'], $row['f_color']),
							'CAT' =>			$row['cat_name'],
							'IMG' =>			Fsb::$session->img($topic_img),
							'VIEWS' =>			$row['t_total_view'],
							'ANSWERS' =>		$row['t_total_post'] - 1,
							'NICKNAME' =>		Html::nickname($row['t_last_p_nickname'], $row['t_last_u_id'], $row['u_color']),
							'OWNER' =>			Html::nickname($row['owner_nickname'], $row['owner_id'], $row['owner_color']),
							'FIRST_DATE' =>		Fsb::$session->print_date($row['t_time']),
							'DATE' =>			Fsb::$session->print_date($row['t_last_p_time']),
							'PAGINATION' =>		$topic_pagination,
							'UNREAD' =>			($is_read) ? false : true,

							'U_TOPIC' =>		sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $row['t_id']),
							'U_CAT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=index&amp;cat=' . $row['cat_id']),
							'U_LOGIN' =>		sid(ROOT . 'index.' . PHPEXT . '?p=userprofile&amp;id=' . $row['t_last_u_id']),
							'U_LAST' =>			sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;' . $last_url),
						));
					}
					Fsb::$db->free($result);
				}
			break;
		}

		// Pagination
		if ($total_page > 1)
		{
			Fsb::$tpl->set_switch('pagination');
		}

		// Suppression des parametres inutiles order et direction s'ils valent leur valeur par defaut
		$pagination = '';
		if ($this->order != 't_last_p_time')
		{
			$pagination .= '&amp;order=' . $this->order;
		}

		if ($this->direction == 'desc')
		{
			$this->direction = '&amp;direction=' . $this->direction;
		}

		Fsb::$tpl->set_vars(array(
			'PAGINATION' =>		Html::pagination($this->page, $total_page, ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=' . $this->mode . '&amp;id=' . $this->id . '&amp;keywords=' . htmlspecialchars($this->keywords) . $pagination),
			'TOTAL_RESULT' =>	sprintf(String::plural('search_total_result', $total), $total),
			'U_ACTION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=' . $this->mode . '&amp;module=' . $this->module),
		));
	}

	/**
	 * Liste des forums autorises
	 *
	 * @param array $forums
	 * @return array
	 */
	public function get_auths_forums($forums)
	{
		$sql = 'SELECT child.f_id
				FROM ' . SQL_PREFIX . 'forums parent
				LEFT JOIN ' . SQL_PREFIX . 'forums child
					ON child.f_left >= parent.f_left
						AND child.f_right <= parent.f_right
				' . (($forums) ? 'WHERE parent.f_id IN (' . implode(', ', $forums) . ')' : '');
		$result = Fsb::$db->query($sql);
		$return = array();
		while ($row = Fsb::$db->row($result))
		{
			if (Fsb::$session->is_authorized($row['f_id'], 'ga_view') && Fsb::$session->is_authorized($row['f_id'], 'ga_view_topics') && Fsb::$session->is_authorized($row['f_id'], 'ga_read'))
			{
				$return[] = $row['f_id'];
			}
		}
		Fsb::$db->free($result);

		return ($return);
	}

	/**
	 * Genere la liste des forums pour la recherche
	 *
	 */
	public function generate_forum_list()
	{
		// Construction un arbre des forums
		$tree = new Tree();
		$tree->add_item(0, null, array(
			'f_id' =>		0,
			'f_level' =>	-1,
			'f_name' =>		Fsb::$session->lang('search_all'),
			'f_parent' =>	0,
		));

		$sql = 'SELECT f_id, f_name, f_level, f_parent, f_color
				FROM ' . SQL_PREFIX . 'forums
				WHERE f_type < ' . FORUM_TYPE_DIRECT_URL . '
				ORDER BY f_left';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			if (!$row['f_parent'] || Fsb::$session->is_authorized($row['f_id'], 'ga_view'))
			{
				$tree->add_item($row['f_id'], $row['f_parent'], $row);
			}
		}
		Fsb::$db->free($result);

		// On genere la liste des forums
		$this->generate_forum_list_by_tree($tree->document->children);
	}

	/**
	 * Generation recursive de la liste des forums
	 *
	 * @param unknown_type $children
	 */
	public function generate_forum_list_by_tree(&$children)
	{
		foreach ($children AS $child)
		{
			if ($child->get('f_parent') > 0 || $child->children)
			{
				Fsb::$tpl->set_blocks('f', array(
					'ID' =>			$child->get('f_id'),
					'NAME' =>		$child->get('f_name'),
					'STYLE' =>		($child->get('f_color') != 'class="forum"') ? $child->get('f_color') : '',
					'PADDING' =>	str_repeat('&nbsp;&nbsp; &nbsp; &nbsp; ', $child->get('f_level') + 1),
					'IS_CAT' =>		($child->get('f_parent') == 0) ? true : false,
					'CHILDREN' =>	implode(', ', $child->allChildren()),
				));

				$this->generate_forum_list_by_tree($child->children);
			}
		}
	}
}

/* EOF */
