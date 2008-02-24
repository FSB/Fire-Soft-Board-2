<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/search/search.php
** | Begin :	20/07/2006
** | Last :		13/11/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Couche d'abstraction pour la recherche
** FSB supporte trois methodes de recherche : fulltext_mysql, fulltext_fsb, like
** Dans tous les cas, en cas de recherche classique, quelque soit la methode, le resultat est mis en cache pour etre appele instantanement
** en cas de changement de page sur une recherche avec plusieurs pages de resultats.
*/
abstract class Search extends Fsb_model
{
	// Recherche dans les messages et les titres ?
	public $search_in_post = TRUE;
	public $search_in_title = TRUE;

	// Recherche AND ou OR ?
	public $search_link = 'and';

	// Taille minimale et maximale des mots clefs
	public $min_len = 2;
	public $max_len = 40;

	abstract public function _search($keywords_array, $author_nickname, $forum_idx, $topic_id, $date);

	/*
	** Retourne le type de recherche utilisee.
	** A appeler en statique pour avoir facilement le nom de la
	** couche a instancier.
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

	/*
	** Explication
	*/
	public function explain()
	{
		return (sprintf(Fsb::$session->lang('search_explain_len'), $this->min_len - 1, $this->max_len));
	}

	/*
	** Lance la procedure de recherche
	** -----
	** $keywords ::			Mots clefs
	** $author_nickname ::	Pseudonyme
	** $list_forums ::		Liste des forums
	** $topic ::			ID d'un topic si on cherche uniquement dans celui ci
	** $date ::				Date (en nombre de secondes) pour la recherche de messages
	*/
	public function launch($keywords, $author_nickname, $list_forums, $topic_id = NULL, $date = 0)
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
				$word_exist[$word] = TRUE;
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