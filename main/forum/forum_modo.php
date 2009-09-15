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
 * Page affichant le panneau de controle de moderation
 * Chaque module sera pioche dans le repertoire ~/main/modo/
 *
 * Chaque module doit contenir une variable globale a la page nommee "show_this_module" avec comme
 * valeur true ou false suivant si l'ont souhaite afficher le module dans le menu
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
	 * Module sellectione pour la page
	 *
	 * @var string
	 */
	public $module;

	/**
	 * Navigation
	 *
	 * @var array
	 */
	public $nav = array();

	/**
	 * Liste des modules par defaut du forum, uniquement afin de les placer dans un certain ordre
	 *
	 * @var array
	 */
	public $list = array('index', 'abuse', 'approve', 'calendar', 'ip', 'merge', 'move', 'procedure', 'split', 'upload', 'user', 'warn');

	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		// On recupere le module du panneau a afficher
		$this->module = Http::request('module');

		if (!$this->module || !file_exists(ROOT . 'main/modo/modo_' . $this->module . '.' . PHPEXT))
		{
			$this->module = 'index';
		}

		// Le membre a le droit d'acceder a la moderation ?
		if ($this->module != 'delete' && $this->module != 'procedure_exec' && Fsb::$session->auth() < MODO)
		{
			Display::message('not_allowed');
		}
		else if ($this->module != 'delete' && $this->module != 'procedure_exec')
		{
			Fsb::$tpl->set_switch('show_menu_panel');
		}

		// Cette liste sera utilisee pour afficher des informations (numero) a cote du nom de certaines pages, histoire
		// de faciliter la lecture aux moderateurs. Par exemple : messages abusifs (xx).
		$info_list = array(
			'abuse' =>		Fsb::$session->data['u_total_abuse'],
			'approve' =>	Fsb::$session->data['u_total_unapproved'],
			'calendar' =>	Fsb::$db->get('SELECT COUNT(*) AS total FROM ' . SQL_PREFIX . 'calendar WHERE c_approve = 0 AND c_view <> 0', 'total'),
		);

		// On liste les modules du panneau de moderation
		$getlist = array();
		$fd = opendir(ROOT . 'main/modo/');
		$max = 1000;
		$list = array_flip($this->list);
		while ($file = readdir($fd))
		{
			if ($file[0] != '.' && preg_match('#^modo_(.*?)\.' . PHPEXT . '$#i', $file, $match))
			{
				$getlist[$match[1]] = (isset($list[$match[1]])) ? $list[$match[1]] : $max++;
			}
		}
		closedir($fd);

		// Tri de la liste
		$getlist = array_flip($getlist);
		ksort($getlist);

		foreach ($getlist AS $m)
		{
			$show_this_module = false;
			include(ROOT . 'main/modo/modo_' . $m . '.' . PHPEXT);
			if (isset($show_this_module) && $show_this_module)
			{
				Fsb::$tpl->set_blocks('module', array(
					'IS_SELECT' =>	($this->module == $m) ? true : false,
					'URL' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=' . $m),
					'NAME' =>		Fsb::$session->lang('modo_module_' . $m),
					'INFO' =>		(isset($info_list[$m])) ? $info_list[$m] : null,
				));

				if ($this->module == $m)
				{
					$this->nav[] = array(
						'url' =>	'',
						'name' =>	Fsb::$session->lang('modo_module_' . $m),
					);
				}
			}
		}

		Fsb::$tpl->set_vars(array(
			'MENU_HEADER_TITLE' =>	Fsb::$session->lang('modo_panel'),
		));

		// On inclu la classe du module d'utilisateur, puis on instancie
		$class_modo_module = 'Page_modo_' . $this->module;
		$modo_module = new $class_modo_module();
	}
}

/* EOF */