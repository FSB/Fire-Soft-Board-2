<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

define('PHPEXT', substr(strrchr(__FILE__,'.'), 1));
define('ROOT', '');
define('FORUM', true);
include(ROOT . 'main/start.' . PHPEXT);

/**
 * Gestion de la pseudo frame du forum
 *
 */
class Fsb_frame extends Fsb_model
{
	/**
	 * Page du fils
	 *
	 * @var string
	 */
	protected $frame_page = 'index';

	/**
	 * Activation du GET automatique dans le lien de connexion
	 *
	 * @var bool
	 */
	protected $frame_get_url = true;

	/**
	 * Recupere la page demandee pour la pseudo frame
	 *
	 * @return string
	 */
	public static function frame_request_page()
	{
		$page = Http::request('p');
		if (!preg_match('#^[a-z0-9_]*?$#i', $page) || !file_exists(ROOT . 'main/forum/forum_' . $page . '.' . PHPEXT))
		{
			$page = 'index';
		}

		// Si on est sur la page de connexion, on ne peut pas desactiver le forum
		if (in_array($page, array('login', 'logout')))
		{
			define('CANT_DISABLE_BOARD', true);
		}
		return ($page);
	}

	/**
	 * Constructeur
	 *
	 * @param string $page
	 */
	public function __construct($page)
	{
		$this->frame_page = $page;

		Fsb::$frame = &$this;
		$this->frame_header();

		$this->main();
		$this->frame_footer();
	}

	/**
	 * Affiche le header du forum, avec le logo, menu, etc ...
	 *
	 */
	public function frame_header()
	{
		if (defined('HEADER_EXISTS'))
		{
			return ;
		}
		define('HEADER_EXISTS', true);

		// Gestion UTF-8 pour les serveurs qui font n'importe quoi
		Http::header('Content-Type', 'text/html; charset=UTF-8');

		// Compression GZIP ?
		Http::check_gzip();

		// Session du membre
		Fsb::$session->start('lg_forum_' . $this->frame_page, Http::request('frame') ? false : true);

		// Support du forum
		if (Fsb::$mods->is_active('root_support') && $root_support = Http::request('root_support'))
		{
			Fsb::$session->log_root_support($root_support);
		}

		// Acces a la page de debugage interdite au membre
		if (Fsb::$session->auth() < MODOSUP)
		{
			Fsb::$debug->debug_query = false;
			Fsb::$debug->show_output = true;
		}

		// On empeche la mise en cache des pages.
		Http::no_cache();

		// Ajoute les relations vers les pages du forum
		Http::add_meta('link', array('rel' => 'index',		'href' => sid(ROOT . 'index.' . PHPEXT)));
		Http::add_meta('link', array('rel' => 'help',		'href' => sid(ROOT . 'index.' . PHPEXT . '?p=faq')));
		Http::add_meta('link', array('rel' => 'search',		'href' => sid(ROOT . 'index.' . PHPEXT . '?p=search')));
		Http::add_meta('link', array('rel' => 'copyright',	'href' => 'http://www.fire-soft-board.com'));

		// On empeche le prefetch des pages (extension Fasterfox pour le navigateur Firefox notament) pour la survie du serveur :=) Sauf pour les flux RSS
		if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch' && $this->frame_page != 'rss')
		{
			Display::message('cant_prefetch_page');
		}

		// Si le membre a recu un nouveau message prive on repasse le flag a false
		if (Fsb::$session->data['u_new_mp'])
		{
			Fsb::$db->update('users', array(
				'u_new_mp' =>	false,
			), 'WHERE u_id = ' . Fsb::$session->id());

			Fsb::$tpl->set_vars(array(
				'HAVE_NEW_MP' =>		true,
				'POPUP_CONTENT' =>		addslashes(sprintf(Fsb::$session->lang('mp_new_popup'), Fsb::$session->data['u_total_mp'])),
				'U_REDIRECT_INBOX' =>	sid(ROOT . 'index.' . PHPEXT . '?p=mp&amp;box=inbox'),
			));
		}

		// Affichage de la navigation et des statistiques en fonction de la page
		if ($this->_get('_show_page_header_nav'))
		{
			Fsb::$tpl->set_switch('forum_link_header');
		}
		
		if ($this->_get('_show_page_footer_nav'))
		{
			Fsb::$tpl->set_switch('forum_link_footer');
		}
		
		if ($this->_get('_show_page_stats'))
		{
			fsb_import('online');
			Fsb::$tpl->set_switch('forum_stat');
		}

		Fsb::$tpl->set_vars(array(
			'QUICKSEARCH_LANG' =>	Fsb::$session->lang('quicksearch'),

			'U_INDEX' =>			sid(ROOT . 'index.' . PHPEXT),
			'U_ADMIN' =>			sid(ROOT . 'admin/index.' . PHPEXT),
			'U_MODO' =>				sid(ROOT . 'index.' . PHPEXT . '?p=modo'),
			'U_PROFILE' =>			sid(ROOT . 'index.' . PHPEXT . '?p=profile'),
			'U_CONTACT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=contact'),
			'U_MP' =>				sid(ROOT . 'index.' . PHPEXT . '?p=mp'),
			'U_NOTIFICATION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=notification'),
			'U_NOT_READ' =>			sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=newposts'),
			'U_OWN_POSTS' =>		sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=ownposts'),
			'U_OWN_NEW_POSTS' =>	sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=ownnewposts'),
			'U_FAQ' =>				sid(ROOT . 'index.' . PHPEXT . '?p=faq'),
			'U_PORTAIL' =>			sid(ROOT . 'index.' . PHPEXT . '?p=portail'),
			'U_SEARCH' =>			sid(ROOT . 'index.' . PHPEXT . '?p=search'),
			'U_USERLIST' =>			sid(ROOT . 'index.' . PHPEXT . '?p=userlist&amp;g_id=' . GROUP_SPECIAL_USER),
			'U_CALENDAR' =>			sid(ROOT . 'index.' . PHPEXT . '?p=calendar'),
			'U_MP_POPUP' =>			sid(ROOT . 'index.' . PHPEXT . '?p=mp&amp;box=popup'),
			'U_GROUPS_MODO' =>		sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=groups'),
			'U_FORUMINFO' =>		sid(ROOT . 'index.' . PHPEXT . '?p=info'),
			'U_LOW_FORUM' =>		sid(ROOT . 'index.' . PHPEXT . '?p=low'),
			'U_QUICKSEARCH' =>		sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;in[]=post&amp;in%5B%5D=title&amp;print=topic'),
		));
	}

	/**
	 * Affiche le bas du forum (fermeture des connexions, copyright, etc ...)
	 *
	 */
	public function frame_footer()
	{
		// META description (sauf pour les sujets, car il y en a deja)
		if ($this->frame_page != 'topic')
		{
			Http::add_meta('meta', array(
				'name' =>		'Description',
				'content' =>	htmlspecialchars(Fsb::$cfg->get('forum_name') . ', ' . Fsb::$cfg->get('forum_description')),
			));
		}

		// Est connecte ?
		Fsb::$tpl->set_switch((!Fsb::$session->is_logged()) ? 'is_not_logged' : 'is_logged');

		// Peut acceder au panneau de moderation / d'administration ?
		if (Fsb::$session->auth() >= MODO)
		{
			Fsb::$tpl->set_switch('modo_panel');
			if (Fsb::$session->auth() >= MODOSUP)
			{
				Fsb::$tpl->set_switch('is_admin');
			}
		}

		// Petit raccourci vers la liste des groupes du membre, s'il est moderateur
		if (Fsb::$session->data['groups_modo'])
		{
			Fsb::$tpl->set_switch('show_group_modo');
		}

		// Generation des liens de navigation
		if (isset($this->nav) && is_array($this->nav) && $this->nav)
		{
			foreach ($this->nav AS $ary)
			{
				Fsb::$tpl->set_blocks('nav_link', array(
					'NAME' =>	$ary['name'],
					'URL' =>	$ary['url'],
					'STYLE' =>	(isset($ary['style'])) ? $ary['style'] : '',
				));
			}
		}
		else if (Fsb::$session->lang('nav_' . $this->frame_page))
		{
			Fsb::$tpl->set_blocks('nav_link', array(
				'NAME' =>	Fsb::$session->lang('nav_' . $this->frame_page),
			));
		}

		// Petite phrase d'accueil
		if (!Fsb::$session->is_logged())
		{
			$home_text = Fsb::$session->lang('home_not_logged');
		}
		else
		{
			$home_nickname = Html::nickname(Fsb::$session->data['u_nickname'], Fsb::$session->id(), Fsb::$session->data['u_color']);
			if (Fsb::$session->data['u_total_mp'] == 1)
			{
				$home_text = sprintf(Fsb::$session->lang('home_new_mp'), $home_nickname);
			}
			else if (Fsb::$session->data['u_total_mp'] > 1)
			{
				$home_text = sprintf(Fsb::$session->lang('home_new_mps'), $home_nickname, Fsb::$session->data['u_total_mp']);
			}
			else
			{
				$home_text = sprintf(Fsb::$session->lang('home_no_new_mp'), $home_nickname);
			}
		}

		// On peut desactiver la recuperation automatique des donnees GET, avec la propriete $frame_get_url = false
		if (!$this->frame_get_url)
		{
			$get_url = '';
		}
		else
		{
			// On recupere les donnees GET de la page
			$get_url = '&amp;redirect=' . $this->frame_page;
			foreach ($_GET AS $key => $value)
			{
				if (!in_array($key, array('p', 'sid', 'redirect')))
				{
					if (is_array($value))
					{
						foreach ($value AS $subvalue)
						{
							if (preg_match('#^[a-z0-9_\-]*?$#i', $subvalue))
							{
								$get_url .= '&amp;' . $key . '[]=' . $subvalue;
							}
						}
					}
					else if (preg_match('#^[a-z0-9_\-]*?$#i', $value))
					{
						$get_url .= '&amp;' . $key . '=' . $value;
					}
				}
			}
		}

		// Affichage du debugage de requetes
		if (Fsb::$session->auth() >= ADMIN && Fsb::$debug->can_debug)
		{
			Fsb::$tpl->set_switch('show_debug_query');
		}

		// Ajout du tag <title> pour la page
		$tag_title = Fsb::$cfg->get('forum_name');
		if (isset($this->tag_title) && $this->tag_title)
		{
			$tag_title = $this->tag_title;
		}
		else if (Fsb::$session->lang('nav_' . $this->frame_page))
		{
			$tag_title = Fsb::$session->lang('nav_' . $this->frame_page) . Fsb::$session->getStyle('other', 'title_separator') . Fsb::$cfg->get('forum_name');
		}

		// Messages abusifs / a approuver
		$modo_have_message = '';
		if (Fsb::$session->auth() >= MODO)
		{
			// Calcul des messages abusifs
			if (Fsb::$session->data['u_total_abuse'] == -1)
			{
				$sql = 'SELECT COUNT(pa.pa_id) AS total
						FROM ' . SQL_PREFIX . 'posts_abuse pa
						LEFT JOIN ' . SQL_PREFIX . 'topics t
							ON t.t_id = pa.t_id
						WHERE pa.pa_status = ' . IS_NOT_APPROVED . '
							AND pa.pa_parent = 0
							AND (pa_mp_id <> 0
							OR t.f_id IN (' . Fsb::$session->moderated_forums() . '))';
				Fsb::$session->data['u_total_abuse'] = Fsb::$db->get($sql, 'total');

				Fsb::$db->update('users', array(
					'u_total_abuse' =>		Fsb::$session->data['u_total_abuse'],
				), 'WHERE u_id = ' . Fsb::$session->id());
			}

			if (Fsb::$session->data['u_total_abuse'] > 0)
			{
				$modo_have_message = sprintf(Fsb::$session->lang('modo_have_abuse'), Fsb::$session->data['u_total_abuse']);
			}

			// Calcul des messages non approuves
			if (Fsb::$session->data['u_total_unapproved'] == -1)
			{
				$sql = 'SELECT COUNT(*) AS total
						FROM ' . SQL_PREFIX . 'posts
						WHERE p_approve = ' . IS_NOT_APPROVED . '
							AND f_id IN (' . Fsb::$session->moderated_forums() . ')';
				Fsb::$session->data['u_total_unapproved'] = Fsb::$db->get($sql, 'total');

				Fsb::$db->update('users', array(
					'u_total_unapproved' =>		Fsb::$session->data['u_total_unapproved'],
				), 'WHERE u_id = ' . Fsb::$session->id());
			}

			if (Fsb::$session->data['u_total_unapproved'] > 0)
			{
				$modo_have_message = ($modo_have_message) ? sprintf(Fsb::$session->lang('modo_have_abuse_aprove'), Fsb::$session->data['u_total_abuse'], Fsb::$session->data['u_total_unapproved']) : sprintf(Fsb::$session->lang('modo_have_aprove'), Fsb::$session->data['u_total_unapproved']);
			}
		}
		
		//Affichage du warning comme quoi le root support est activé
		if(Fsb::$mods->is_active('root_support') && Fsb::$session->auth() >= ADMIN)
		{
			Fsb::$tpl->set_switch('root_support_active');
		}

		// Mise a jour heure d'ete / hiver ?
		$dst = date('I');
		if (Fsb::$cfg->get('current_utc_dst') != $dst)
		{
			Fsb::$cfg->update('current_utc_dst', $dst, false);
			Fsb::$cfg->update('default_utc_dst', $dst);
			Fsb::$db->update('users', array(
				'u_utc_dst' =>	$dst,
			), 'WHERE u_utc_dst <> ' . $dst);
		}

		Fsb::$debug->end = microtime( true );
		Fsb::$tpl->set_vars( array(
			'U_LOGIN' =>			sid(ROOT . 'index.' . PHPEXT . '?p=login' . $get_url),
			'U_LOGOUT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=logout', true),
			'U_REGISTER' =>			sid(ROOT . 'index.' . PHPEXT . '?p=register' . $get_url),
			'SITE_NAME' => 			htmlspecialchars(Fsb::$cfg->get('forum_name')),
			'SITE_DESCRIPTION' =>	Fsb::$cfg->get('forum_description'),
			'TAG_TITLE' =>			strip_tags($tag_title),
			'HOME_TEXT' =>			$home_text,
			'ROOT' =>				ROOT,
			'SID' =>				Fsb::$session->sid,
			'PHPEXT' =>				PHPEXT,
			'USER_TPL' =>			Fsb::$session->data['u_tpl'],
            'USER_LG' =>            Fsb::$session->data['u_language'],
			'MODO_HAVE_MESSAGE' =>	$modo_have_message,
			'U_DEBUG_QUERY' =>		Fsb::$debug->debug_url(),
			'FSB_VERSION' =>		Fsb::$cfg->get('fsb_version'),
			'EXEC_QUERY' =>			sprintf(Fsb::$session->lang('exec_query'), Fsb::$db->count),
			'EXEC_TIME' =>			sprintf(Fsb::$session->lang('exec_time'), substr(Fsb::$debug->end - Fsb::$debug->start, 0, 4)),
			'CURRENT_YEAR' =>		date('Y', CURRENT_TIME),
			'PROCESS_IMG' =>		ROOT . 'main/process/process.' . PHPEXT . '?t=' . time(),
		));
		
		Fsb::$tpl->parse();

		@ob_end_flush();
	}
}

// On recupere les donnees de la page prinpale pour la pseudo frame
$page = Fsb_frame::frame_request_page();

// Inclusion de la page fille, et instance de la classe
fsb_import('forum_' . $page);
new Fsb_frame_child($page);

/* EOF */
