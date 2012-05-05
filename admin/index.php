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
define('ROOT', '../');
define('IN_ADM', true);
include(ROOT . 'main/start.' . PHPEXT);

/**
 * Gestion de la pseudo frame pour l'administration
 */
class Fsb_admin_frame extends Fsb_model
{
	/**
	 * Page de la frame
	 * 
	 * @var string
	 */
	public $page = 'index_adm';

	/**
	 * Autorisation pour voir la page
	 * 
	 * @var int
	 */
	public $auth = FONDATOR;

	/**
	 * Categorie de la page
	 * 
	 * @var int
	 */
	public $cat = null;

	/**
	 * Recupere la page de la pseudo frame
	 */
	public static function frame_request_page()
	{
		// Construction du menu administratif
		$page = Http::request('p');
		Fsb::$menu = new Adm_menu($page);

		// Si la page n'existe pas il s'agit d'une page built-in de l'administration
		if (Fsb::$menu->include == null)
		{
			switch ($page)
			{
				case "menu_adm" :
					$inc_auth = FONDATOR;
					$inc_page = 'menu_adm';
					$inc_cat = null;
				break;

				default :
					$inc_auth = MODOSUP;
					$inc_page = 'index_adm';
					$inc_cat = null;
				break;
			}
		}
		else
		{
			$inc_auth = Fsb::$menu->include['auth'];
			$inc_page = Fsb::$menu->include['page'];
			$inc_cat = Fsb::$menu->include['cat'];
		}

		if ($inc_cat)
		{
			include(ROOT . 'admin/' . $inc_cat . '/' . $inc_page . '.' . PHPEXT);
		}
		else
		{
			include(ROOT . 'admin/' . $inc_page . '.' . PHPEXT);
		}

		return (array($inc_page, $inc_auth, $inc_cat));
	}

	/**
	 * Constructeur
	 * 
	 * @param string $page page de la frame
	 * @param int $auth autorisation pour voir la page
	 * @param int $cat categorie de la page
	 */
	public function __construct($page, $auth, $cat)
	{
		$this->page = $page;
		$this->auth = $auth;
		$this->cat = $cat;

		Fsb::$frame = &$this;
		$this->frame_header();
		$this->main();
		$this->frame_footer();
	}

	/**
	 * Cree le header (entete) de l'administration
	 */
	public function frame_header()
	{
		// Gestion UTF-8 pour les serveurs qui font n'importe quoi
		Http::header('Content-Type', 'text/html; charset=UTF-8');

		// Compression GZIP (sauf sur la page d'export SQL) ?
		if ($this->page != 'tools_sql')
		{
			Http::check_gzip();
		}

		// Session du membre
		Fsb::$session->start('admin/lg_' . $this->page);
		if ($this->cat)
		{
			Fsb::$tpl->set_file($this->cat . '/adm_' . $this->page . '.html');
		}

		// On verifie si le membre peut acceder a cette page de l'administration
		// L'editeur CSS est accesible pour tout le monde
		if (!Fsb::$session->data['s_admin_logged'])
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=login&adm_log=true');
		}
		else if (Fsb::$session->auth() < $this->auth || Fsb::$session->auth() < MODOSUP)
		{
			if ($this->page !== 'general_tpl' && Http::request('mode') !== 'css_generator')
			{
				Http::redirect(ROOT . 'index.' . PHPEXT);
			}
		}

		// On empeche la mise en cache des pages.
		Http::no_cache();
		
		Fsb::$menu->except = array('adm_tpl');
		Fsb::$menu->get_adm_menu($this->page);

		// Est fondateur ?
		if (Fsb::$session->is_fondator())
		{
			Fsb::$tpl->set_switch('is_fondator');
		}

		Fsb::$tpl->set_vars(array(
			'ROOT' =>			ROOT,
			'SID' =>			Fsb::$session->sid,
			'PHPEXT' =>			PHPEXT,
            'USER_LG' =>        Fsb::$session->data['u_language'],
			'EXPLAIN_TITLE' =>	Fsb::$session->lang($this->page . '_explain_title'),
			'EXPLAIN_DESC' =>	Fsb::$session->lang($this->page . '_explain_desc'),
			'U_INDEX' =>		sid(ROOT . 'admin/index.' . PHPEXT),
			'U_INDEX_FORUM' =>	sid(ROOT . 'index.' . PHPEXT),
			'U_EDIT_MENU' =>	sid('index.' . PHPEXT . '?p=menu_adm'),
			'U_REFRESH' =>		sid('index.' . PHPEXT . '?mode=refresh'),
		));
	}

	/**
	 * Cree le footer (pied de page) de l'administration
	 */
	public function frame_footer()
	{
		Fsb::$debug->end = microtime( true );

		// Navigation ?
		if (isset($this->nav) && $this->nav)
		{
			Fsb::$tpl->set_switch('nav_exists');
			foreach ($this->nav AS $value)
			{
				Fsb::$tpl->set_blocks('nav', array(
					'URL' =>	(isset($value['url'])) ? sid($value['url']) : false,
					'NAME' =>	htmlspecialchars($value['name']),
				));
			}
		}

		// Affichage du debugage de requetes
		if (Fsb::$session->auth() >= ADMIN && Fsb::$debug->can_debug)
		{
			Fsb::$tpl->set_switch('show_debug_query');
		}

		Fsb::$tpl->set_vars(array(
			'U_DEBUG_QUERY' =>	Fsb::$debug->debug_url(),
			'FSB_VERSION' =>	Fsb::$cfg->get('fsb_version'),
			'EXEC_QUERY' =>		sprintf(Fsb::$session->lang('exec_query'), Fsb::$db->count),
			'EXEC_TIME' =>		sprintf(Fsb::$session->lang('exec_time'), substr(Fsb::$debug->end - Fsb::$debug->start, 0, 4)),
			'CURRENT_YEAR' =>	date('Y', CURRENT_TIME),
		));

		Fsb::$tpl->parse();
		@ob_end_flush();
	}
}

// Instance de la frame
list($page, $need_auth, $cat) = Fsb_admin_frame::frame_request_page();
new Fsb_frame_child($page, $need_auth, $cat);

/* EOF */
