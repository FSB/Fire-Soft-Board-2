<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/search/search_like.php
** | Begin :	20/07/2006
** | Last :		13/11/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Méthode LIKE, basique, basée sur les LIKE dans les requètes
**	+ Avantage : Facile à mettre en place, ne necessite aucun index fulltext ni aucun ralentissement lors de
**		l'écriture des messages
**	+ Inconvénient : Très lent ... Plus on a de messages plus la recherche sera lente, cette méthode est à conseiller sur les
**		forums pas trop gros, ne supportant pas FULLTEXT MYSQL, et dont on ne souhaite pas allourdir la base avec le FULLTEXT FSB
*/
class Search_like extends Search
{
	/*
	** CONSTRUCTEUR
	*/
	public function __construct()
	{
		$this->min_len = $GLOBALS['_search_min_len'];
		$this->max_len = $GLOBALS['_search_max_len'];
	}

	/*
	** Procédure de recherche
	** -----
	** $keywords_array ::		Tableau des mots clefs
	** $author_nickname ::		Nom de l'auteur
	** $forum_idx ::			Tableau des IDX de forums autorisés
	** $topic ::				ID d'un topic si on cherche uniquement dans celui ci
	** $date ::					Date (en nombre de secondes) pour la recherche de messages
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
			$flag = FALSE;
			foreach ($keywords_array AS $word)
			{
				if ($word)
				{
					$select->where((($flag) ? $this->search_link : '') . ' p_text ' . Fsb::$db->like() . ' \'%' . Fsb::$db->escape($word) . '%\'');
					$flag = TRUE;
				}
			}
			$select->where(')');

			// Résultats
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
			$flag = FALSE;
			foreach ($keywords_array AS $word)
			{
				if ($word)
				{
					$select->where((($flag) ? $this->search_link : '') . ' t.t_title ' . Fsb::$db->like() . ' \'%' . Fsb::$db->escape($word) . '%\'');
					$flag = TRUE;
				}
			}
			$select->where(')');

			// Résultats
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