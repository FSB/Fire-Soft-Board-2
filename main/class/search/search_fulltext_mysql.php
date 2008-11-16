<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

/*
** Methode FULLTEXT de MySQL 4
**	+ Avantage : Rapide, facile a mettre en oeuvre
**	+ Inconvenient : Compatible uniquement MySQL >= 4, ne prend pas en compte les mots de moins de
**		4 lettres, ne prend pas en compte les mots contenu dans au moins 50% des resultats
*/
class Search_fulltext_mysql extends Search
{
	/*
	** CONSTRUCTEUR
	*/
	public function __construct()
	{
		$sql = 'SHOW VARIABLES LIKE \'ft_%\'';
		$result = Fsb::$db->query($sql);
		$rows = Fsb::$db->rows($result, 'assoc', 'Variable_name');
		$this->min_len = $rows['ft_min_word_len']['Value'];
		$this->max_len = $rows['ft_max_word_len']['Value'];
	}

	/*
	** Procedure de recherche
	** -----
	** $keywords_array ::		Tableau des mots clefs
	** $author_nickname ::		Nom de l'auteur
	** $forum_idx ::			Tableau des IDX de forums autorises
	** $topic ::				ID d'un topic si on cherche uniquement dans celui ci
	** $date ::					Date (en nombre de secondes) pour la recherche de messages
	*/
	public function _search($keywords_array, $author_nickname, $forum_idx, $topic_id, $date)
	{
		// Mots clefs
		if ($this->search_link == 'and')
		{
			foreach ($keywords_array AS $key => $word)
			{
				$keywords_array[$key] = '+' . $word;
			}
		}

		$iterator = 0;
		$return = array();
		if ($this->search_in_post)
		{
			$select = new Sql_select();
			$select->join_table('FROM', 'posts', 'p_id');
			$select->where('f_id IN (' . implode(', ', $forum_idx) . ')');

			// Recherche de mots clefs
			if ($keywords_array)
			{
				$select->where('AND MATCH (p_text) AGAINST (\'' . implode(' ', $keywords_array) . '\' IN BOOLEAN MODE)');
			}

			// Recherche d'auteur
			if ($author_nickname)
			{
				$select->where('AND p_nickname = \'' . Fsb::$db->escape($author_nickname) . '\'');
			}

			if ($topic_id)
			{
				$select->where('AND t_id = ' . $topic_id);
			}

			if ($date > 0)
			{
				$select->where('AND p_time > ' . CURRENT_TIME . ' - ' . $date);
			}

			// Resultats
			$result = $select->execute();
			while ($row = Fsb::$db->row($result))
			{
				$return[$row['p_id']] = $iterator++;
			}
			Fsb::$db->free($result);
			unset($select);
		}

		// Recherche dans les titres
		if ($this->search_in_title && $keywords_array)
		{
			$sql_author = '';
			if ($author_nickname)
			{
				$sql_author = 'AND p.p_nickname = \'' . Fsb::$db->escape($author_nickname) . '\'';
			}

			$select = new Sql_select();
			$select->join_table('FROM', 'topics t');
			$select->join_table('INNER JOIN', 'posts p', 'p.p_id', 'ON t.t_id = p.t_id ' . $sql_author);
			$select->where('t.f_id IN (' . implode(', ', $forum_idx) . ')');
			if ($date > 0)
			{
				$select->where('AND p.p_time > ' . CURRENT_TIME . ' - ' . $date);
			}

			if ($topic_id)
			{
				$select->where('AND p.t_id = ' . $topic_id);
			}
			$select->where('AND MATCH (t.t_title) AGAINST (\'' . implode(' ', $keywords_array) . '\' IN BOOLEAN MODE)');

			// Resultats
			$result = $select->execute();
			while ($row = Fsb::$db->row($result))
			{
				$return[$row['p_id']] = $iterator++;
			}
			Fsb::$db->free($result);
			unset($select);
		}

		return (array_flip($return));
	}
}

/* EOF */