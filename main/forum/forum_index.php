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
 * Affiche la liste des categories et des forums
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
	public $_show_page_stats = true;

	/**
	 * Navigation
	 *
	 * @var array
	 */
	public $nav = array();

	/**
	 * Titre de la page
	 *
	 * @var string
	 */
	public $tag_title = '';

	/**
	 * Categorie affiche
	 *
	 * @var unknown_type
	 */
	public $cat;
	
	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		$this->cat =	intval(Http::request('cat'));
		$this->forum =	intval(Http::request('forum'));

		// Marquer lu ?
		if (Http::request('markread'))
		{
			$this->markread_forums();
		}
		$this->show_forums();
	}

	/**
	 * Marquer les forums comme lu
	 *
	 */
	public function markread_forums()
	{
		if ($this->cat)
		{
			Forum::markread('cat', $this->cat);
		}
		else if ($this->forum)
		{
			Forum::markread('forum', $this->forum);
		}
		else
		{
			Forum::markread('all');
		}

		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=index' . (($this->cat) ? '&cat=' . $this->cat : ''));
	}
	
	/**
	 * Affiche les categories et les forums
	 *
	 */
	public function show_forums()
	{
		// Navigation de la page
		if ($this->cat)
		{
			$this->nav = Forum::nav($this->cat, array(), $this);
			Fsb::$tpl->set_vars(array(
				'U_LOW_FORUM' =>		sid(ROOT . 'index.' . PHPEXT . '?p=low&amp;id=' . $this->cat),
			));
		}

		// Derniere visite sur l'index ?
		if (Fsb::$mods->is_active('update_last_visit') && Fsb::$mods->is_active('last_visit_index') && Fsb::$session->id() <> VISITOR_ID && $last_visit = Http::getcookie('last_visit'))
		{
			Fsb::$tpl->set_vars(array(
				'L_LAST_VISIT_INDEX' =>		sprintf(Fsb::$session->lang('last_visit_index'), Fsb::$session->print_date($last_visit)),
			));
		}

		// Balise META pour la syndications RSS
		Http::add_meta('link', array(
			'rel' =>		'alternate',
			'type' =>		'application/rss+xml',
			'title' =>		Fsb::$session->lang('rss'),
			'href' =>		sid(ROOT . 'index.' . PHPEXT . '?p=rss&amp;mode=index&amp;cat=' . $this->cat),
		));

		Fsb::$tpl->set_file('forum/forum_index.html');
		Fsb::$tpl->set_vars(array(
			'IS_CAT' =>					($this->cat) ? true : false,

			'U_MARKREAD_FORUMS' =>		sid(ROOT . 'index.' . PHPEXT . '?markread=true' . (($this->cat) ? '&amp;cat=' . $this->cat : '')),
		));

		// Sujets lus
		$forum_topic_read = (!Fsb::$session->is_logged()) ? array() : Forum::get_topics_read(1);

		// On recupere les forums, avec une jointure sur les messages lus pour voir si
		// le dernier message a ete lu ou non
		$result = Forum::query(($this->cat == null) ? '' : 'WHERE f.f_cat_id = ' . $this->cat);

		$can_display_subforum = false;
		while ($forum = Fsb::$db->row($result))
		{
			if ($forum['f_parent'] == 0)
			{
				$parent_id = $forum['f_id'];
				$last_cat = $forum;
				$can_display_subforum = false;
			}
			else if (Fsb::$session->is_authorized($forum['f_id'], 'ga_view') && (Fsb::$cfg->get('display_subforums') || $forum['f_parent'] == $parent_id))
			{
				// On affiche la categorie
				if ($last_cat)
				{
					Fsb::$tpl->set_blocks('cat', array(
						'CAT_ID' =>		$forum['f_id'],
						'NAME' =>		htmlspecialchars($last_cat['f_name']),
						'U_CAT' =>		sid(ROOT . 'index.' . PHPEXT . '?cat=' . $last_cat['f_id']),
					));
					$last_cat = null;
				}

				// Forum lu ou non lu ?
				$is_read = (Fsb::$session->is_logged() && isset($forum_topic_read[$forum['f_id']]) && $forum_topic_read[$forum['f_id']] > 0) ? false : true;

				// On affiche le forum
				Forum::display($forum, 'forum', 0, $is_read);
				
				$can_display_subforum = true;
				$sub_parent_id = $forum['f_id'];
			}
			else if ($can_display_subforum && Fsb::$session->is_authorized($forum['f_id'], 'ga_view') && !Fsb::$cfg->get('display_subforums') && $forum['f_parent'] == $sub_parent_id)
			{
				// Forum lu ou non lu ?
				$is_read = (Fsb::$session->is_logged() && isset($forum_topic_read[$forum['f_id']]) && $forum_topic_read[$forum['f_id']] > 0) ? false : true;

				// On affiche le sous forum
				Forum::display($forum, 'subforum', 0, $is_read);
			}
		}
		Fsb::$db->free($result);
	}
}

/* EOF */