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
 * Methode LIKE, basique, basee sur les LIKE dans les requetes
 * 	+ Avantage : Facile a mettre en place, ne necessite aucun index fulltext ni aucun ralentissement lors de
 * 		l'ecriture des messages
 * 	- Inconvenient : Tres lent ... Plus on a de messages plus la recherche sera lente, cette methode est a conseiller
 *		sur les forums avec peu de messages, ne supportant pas FULLTEXT MYSQL, et dont on ne souhaite pas allourdir 
 * 		la base avec le FULLTEXT FSB
 */
class Search_like extends Search
{
	/**
	 * Constructeur
	 */
	public function __construct()
	{
		$this->min_len = $GLOBALS['_search_min_len'];
		$this->max_len = $GLOBALS['_search_max_len'];
	}

	/**
	 * @see Search::_search()
	 */
	public function _search($keywords_array, $author_nickname, $forum_idx, $topic_id, $date)
	{
		// Recherche dans les messages
		$iterator = 0;
		$return = array();
		if ($this->search_in_post)
		{
			$select = new Sql_select();
			$select->join_table('FROM', 'posts', 'p_id');
			$select->where('f_id IN (' . implode(', ', $forum_idx) . ')');
			if ($author_nickname)
			{
				$select->where('AND p_nickname = \'' . Fsb::$db->escape($author_nickname) . '\'');
			}

			if ($topic_id)
			{
				$select->where('AND p.t_id = ' . $topic_id);
			}

			if ($date > 0)
			{
				$select->where('AND p.p_time > ' . CURRENT_TIME . ' - ' . $date);
			}

			$select->where('AND (');
			$flag = false;
			foreach ($keywords_array AS $word)
			{
				if ($word)
				{
					$select->where((($flag) ? $this->search_link : '') . ' p_text ' . Fsb::$db->like() . ' \'%' . Fsb::$db->escape($word) . '%\'');
					$flag = true;
				}
			}
			$select->where(')');

			// Resultats
			$result = $select->execute();
			while ($row = Fsb::$db->row($result))
			{
				$return[$row['p_id']] = $iterator++;
			}
			Fsb::$db->free($result);
		}

		// Recherche dans les titres
		if ($keywords_array && $this->search_in_title)
		{
			$select = new Sql_select();
			$select->join_table('FROM', 'topics t');
			$select->join_table('LEFT JOIN', 'posts p', 'p.p_id', 'ON t.t_id = p.t_id');
			$select->where('t.f_id IN (' . implode(', ', $forum_idx) . ')');
			if ($author_nickname)
			{
				$select->where('AND p.p_nickname = \'' . Fsb::$db->escape($author_nickname) . '\'');
			}

			if ($topic_id)
			{
				$select->where('AND p.t_id = ' . $topic_id);
			}

			if ($date > 0)
			{
				$select->where('AND p.p_time > ' . CURRENT_TIME . ' - ' . $date);
			}

			$select->where('AND (');
			$flag = false;
			foreach ($keywords_array AS $word)
			{
				if ($word)
				{
					$select->where((($flag) ? $this->search_link : '') . ' t.t_title ' . Fsb::$db->like() . ' \'%' . Fsb::$db->escape($word) . '%\'');
					$flag = true;
				}
			}
			$select->where(')');

			// Resultats
			$result = $select->execute();
			while ($row = Fsb::$db->row($result))
			{
				$return[$row['p_id']] = $iterator++;
			}
			Fsb::$db->free($result);
		}

		return (array_flip($return));
	}
}

/* EOF */