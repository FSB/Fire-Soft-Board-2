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
 * Couche d'abstraction pour la recherche
 * FSB supporte trois methodes de recherche : fulltext_mysql, fulltext_fsb, like.
 * Dans tous les cas, en cas de recherche classique, quelque soit la methode, le resultat est mis en cache pour
 * etre appele instantanement en cas de changement de page sur une recherche avec plusieurs pages de resultats.
 */
abstract class Search extends Fsb_model
{
	/**
	 * Recherche dans les messages
	 *
	 * @var bool
	 */
	public $search_in_post = true;
	
	/**
	 * Recherche dans les titres
	 *
	 * @var bool
	 */
	public $search_in_title = true;

	/**
	 * Un seul doit matcher, ou tous les mots doivent matcher ?
	 *
	 * @var string 'and' ou 'or'
	 */
	public $search_link = 'and';

	/**
	 * Taille minimale du mot clef
	 *
	 * @var int
	 */
	public $min_len = 2;
	
	/**
	 * Taille maximale du mot clef
	 *
	 * @var int
	 */
	public $max_len = 40;

	/**
	 * Lance la recherche
	 *
	 * @param array $keywords_array Liste des mots clefs
	 * @param string $author_nickname Pseudonyme de l'auteur
	 * @param array $forum_idx ID des forums concernes par la recherche
	 * @param int $topic_id ID du topic concerne par la recherche
	 * @param int $date Timestamp pour la recherche
	 * @return array ID des messages trouves
	 */
	abstract public function _search($keywords_array, $author_nickname, $forum_idx, $topic_id, $date);

	/*
	** Retourne le type de recherche utilisee.
	** A appeler en statique pour avoir facilement le nom de la
	** couche a instancier.
	*/
	/**
	 * Retourne une instance de la classe de recherche avec la bonne couche
	 *
	 * @return Search
	 */
	public static function factory()
	{
		$method = Fsb::$cfg->get('search_method');
		if (!in_array($method, array('fulltext_fsb', 'fulltext_mysql', 'like')))
		{
			$method = 'fulltext_fsb';
		}

		if ($method == 'fulltext_mysql' && !preg_match('#^mysql#i', SQL_DBAL))
		{
			$method = 'fulltext_fsb';
		}

		$method = 'Search_' . $method;
		return (new $method());
	}

	/**
	 * Explications pour la recherche
	 *
	 * @return string
	 */
	public function explain()
	{
		return (sprintf(Fsb::$session->lang('search_explain_len'), $this->min_len - 1, $this->max_len));
	}

	/**
	 * Lance la recherche
	 *
	 * @param array $keywords_array Liste des mots clefs
	 * @param string $author_nickname Pseudonyme de l'auteur
	 * @param array $forum_idx ID des forums concernes par la recherche
	 * @param int $topic_id ID du topic concerne par la recherche
	 * @param int $date Timestamp pour la recherche
	 * @return array ID des messages trouves
	 */
	public function launch($keywords, $author_nickname, $list_forums, $topic_id = null, $date = 0)
	{
		// Liens
		$this->search_link = ($this->search_link == 'or') ? 'or' : 'and';

		// On tri les mots clefs
		$words = preg_split('#[^\S]+#si', $keywords);
		$keyword_array = array();
		$word_exist = array();
		foreach ($words AS $key => $word)
		{
			$word = strtolower($word);
			if ($word && !isset($word_exist[$word]) && strlen($word) >= $this->min_len && strlen($word) <= $this->max_len)
			{
				$keyword_array[] = Fsb::$db->escape($word);
				$word_exist[$word] = true;
			}
		}
		unset($word_exist);

		if ((!$keyword_array && !$author_nickname))
		{
			return (array());
		}
		return ($this->_search($keyword_array, $author_nickname, $list_forums, $topic_id, $date));
	}
}

/* EOF */