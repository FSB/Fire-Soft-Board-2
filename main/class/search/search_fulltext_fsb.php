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
 * Methode FULLTEXT FSB, dont le principe s'inspire fortement de celui de phpBB, qui indexe chaque mot du forum.
 * Cette methode est utilisee pour les SGBD autre que MySQL, qui ne supportent pas le FULLTEXT MYSQL.
 *	+ Avantage : Rapide, fonctionne sur chaque SGBD
 * 	- Inconvenient : Un peu lourd, place prise dans la base de donnee assez importante
 */
class Search_fulltext_fsb extends Search
{
	/**
	 * Liste des mots a ne pas indexer
	 *
	 * @var array
	 */
	private $stopwords = array();

	/**
	 * Constructeur, recupere les mots a ne pas indexer
	 */
	public function __construct()
	{
		$this->min_len = $GLOBALS['_search_min_len'];
		$this->max_len = $GLOBALS['_search_max_len'];

		// Chargement des mots a ne pas indexer
		$this->stopwords = array();
		if (file_exists(ROOT . 'lang/' . Fsb::$session->data['u_language'] . '/stopword.txt'))
		{
			$this->stopwords = array_map('trim', file(ROOT . 'lang/' . Fsb::$session->data['u_language'] . '/stopword.txt'));
		}
	}

	/**
	 * @see Search::_search()
	 */
	public function _search($keywords_array, $author_nickname, $forum_idx, $topic_id, $date)
	{
		// Suivant si on doit chercher dans les messages / titres, on construit la clause $sql_is_title
		$sql_is_title = '';
		if ($this->search_in_post && !$this->search_in_title)
		{
			$sql_is_title = 'AND sm.is_title = 0';
		}
		else if (!$this->search_in_post && $this->search_in_title)
		{
			$sql_is_title = 'AND sm.is_title = 1';
		}

		$return = array();
		if ($total_keywords = count($keywords_array))
		{
			// Recuperation des sujets indexes sur les mots clefs
			$select = new Sql_select();
			$select->join_table('FROM', 'search_word sw');
			$select->join_table('INNER JOIN', 'search_match sm', 'COUNT(sw.word_content) AS total', 'ON sw.word_id = sm.word_id');
			$select->join_table('LEFT JOIN', 'posts p', 'p.p_id', 'ON sm.p_id = p.p_id ' . $sql_is_title);

			// Clauses WHERE
			$select->where('LOWER(sw.word_content) IN (\'' . implode('\', \'', $keywords_array) . '\')');
			$select->where('AND p.f_id IN (' . implode(', ', $forum_idx) . ')');
			if ($date > 0)
			{
				$select->where('AND p.p_time > ' . CURRENT_TIME . ' - ' . $date);
			}

			if ($author_nickname)
			{
				$select->where('AND p.p_nickname = \'' . Fsb::$db->escape($author_nickname) . '\'');
			}

			if ($topic_id)
			{
				$select->where('AND p.t_id = ' . $topic_id);
			}
			$select->group_by('p.p_id');

			// Resultats
			$result = $select->execute();
			while ($row = Fsb::$db->row($result))
			{
				if ($this->search_link == 'or' || $row['total'] == $total_keywords)
				{
					$return[] = $row['p_id'];
				}
			}
			Fsb::$db->free($result);
		}

		return ($return);
	}

	/**
	 * Parse du message pour indexer les mots dans les tables de recherche
	 *
	 * @param int $post_id ID du message
	 * @param string $content Contenu du message
	 * @param bool $is_title Definit la valeur du champ is_title dans la table fsb2_search_match
	 */
	public function index($post_id, $content, $is_title = false)
	{
		// On recupere chaque mot du message
		$split = preg_split('#[^\w]+#si', $content);

		if ($split)
		{
			// On recupere tous les mots de la base de donnee definis dans ce message
			$words = array();
			$word_exist = array();
			foreach ($split AS $key => $word)
			{
				$word = strtolower($word);
				if ($word && !isset($word_exist[$word]) && strlen($word) >= $this->min_len && strlen($word) <= $this->max_len && !is_numeric($word))
				{
					$words[] = $word;
					$word_exist[$word] = true;
				}
				else
				{
					unset($split[$key]);
				}
			}

			// On supprime les mots interdits
			$words = array_diff($words, $this->stopwords);
			$flip = array_flip($words);
			unset($word_exist);

			if ($words)
			{
				// On ajoute dans la table search_word tous les mots qui n'y sont pas
				Fsb::$db->transaction('begin');
				$sql = 'SELECT word_content
						FROM ' . SQL_PREFIX . 'search_word
						WHERE word_content IN (\'' . implode('\', \'', $words) . '\')';
				$result = Fsb::$db->query($sql);
				while ($row = Fsb::$db->row($result))
				{
					if (isset($flip[$row['word_content']]))
					{
						unset($flip[$row['word_content']]);
					}
				}
				Fsb::$db->free($result);

				foreach ($flip AS $word => $bool)
				{
					if ($word)
					{
						Fsb::$db->insert('search_word', array(
							'word_content' =>	$word,
						),' INSERT', true);
					}
				}
				Fsb::$db->query_multi_insert();
				unset($flip);

				// On cree une ocurence de chaque mot avec l'id du message dans la table search_match
				$sql = 'INSERT INTO ' . SQL_PREFIX . 'search_match (word_id, p_id, is_title)
							SELECT word_id, ' . $post_id . ', ' . (int) $is_title . '
							FROM ' . SQL_PREFIX . 'search_word
							WHERE word_content IN (\'' . implode('\', \'', $words) . '\')';
				Fsb::$db->query($sql);
				Fsb::$db->transaction('commit');
			}
		}
	}

	/**
	 * Supprime les index de recherche fulltext_fsb d'un message
	 *
	 * @param int $p_id ID du message
	 */
	public function delete_index($p_id)
	{
		if (!is_array($p_id))
		{
			$p_id = array($p_id);
		}
		$list_id = implode(', ', $p_id);

		// On selectionne tous les mots du message
		$sql = 'SELECT word_id
				FROM ' . SQL_PREFIX . 'search_match
				WHERE p_id IN (' . $list_id . ')';
		$result = Fsb::$db->query($sql);
		$idx = '';
		while ($row = Fsb::$db->row($result))
		{
			$idx .= $row['word_id'] . ', ';
		}
		$idx = substr($idx, 0, -2);
		Fsb::$db->free($result);

		if ($idx)
		{
			// On selectionne tous les mots du message existant en un seul exemplaire dans l'index
			$sql = 'SELECT word_id
					FROM ' . SQL_PREFIX . 'search_match
					WHERE word_id IN (' . $idx . ')
					GROUP BY word_id
					HAVING COUNT(word_id) = 1';
			$result = Fsb::$db->query($sql);
			$idx = '';
			while ($row = Fsb::$db->row($result))
			{
				$idx .= $row['word_id'] . ', ';
			}
			$idx = substr($idx, 0, -2);
			Fsb::$db->free($result);

			// Suppression des index des mots
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'search_match
					WHERE p_id IN (' . $list_id . ')';
			Fsb::$db->query($sql);

			if ($idx)
			{
				$sql = 'DELETE FROM ' . SQL_PREFIX . 'search_word
						WHERE word_id IN (' . $idx . ')';
				Fsb::$db->query($sql);
			}
		}
	}
}

/* EOF */