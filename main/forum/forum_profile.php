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
 * Page affichant le panneau de controle de l'utilisateur
 * Chaque module sera pioche dans le repertoire ~/main/user/
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
	public $list = array('personal', 'activate', 'password', 'avatar', 'sig', 'contact', 'groups', 'notepad', 'upload', 'fsbcard', 'viewprofile');

	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		// Le membre peut acceder a cette page ?
		if (!Fsb::$session->is_logged())
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=login&redirect=profile');
		}

		// On recupere le module du panneau a afficher
		$this->module = Http::request('module');

		// Ne pas supprimer ou renommer le fichier ~/main/user/user_personal.php
		if (!$this->module || !file_exists(ROOT . 'main/user/user_' . $this->module . '.' . PHPEXT))
		{
			$this->module = Http::getcookie('profil_module');
			if (!$this->module)
			{
				$this->module = 'personal';
			}
		}

		// On envoie un cookie pour qu'a la prochaine ocassion le dernier onglet ou le membre etait
		// s'ouvre, sauf pour le module viewprofile
		if ($this->module != 'viewprofile')
		{
			Http::cookie('profil_module', $this->module, CURRENT_TIME + ONE_WEEK);
		}

		// On liste les modules du panneau de controle d'utilisateur
		$getlist = array();
		$fd = opendir(ROOT . 'main/user/');
		$max = 1000;
		$list = array_flip($this->list);
		while ($file = readdir($fd))
		{
			if ($file[0] != '.' && preg_match('#^user_(.*?)\.' . PHPEXT . '$#i', $file, $match))
			{
				$getlist[$match[1]] = (isset($list[$match[1]])) ? $list[$match[1]] : $max++;
			}
		}
		closedir($fd);

		// Tri de la liste
		$getlist = array_flip($getlist);
		ksort($getlist);

		$print = false;
		foreach ($getlist AS $m)
		{
			$show_this_module = false;
			include(ROOT . 'main/user/user_' . $m . '.' . PHPEXT);
			if (isset($show_this_module) && $show_this_module)
			{
				Fsb::$tpl->set_switch('show_menu_panel');
				Fsb::$tpl->set_blocks('module', array(
					'IS_SELECT' =>	($this->module == $m) ? true : false,
					'URL' =>		sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=' . $m),
					'NAME' =>		Fsb::$session->lang('user_module_' . $m),
				));

				if ($this->module == $m)
				{
					$print = true;
					$this->nav[] = array(
						'url' =>	'',
						'name' =>	Fsb::$session->lang('user_module_' . $m),
					);
				}
			}
		}
		
		Fsb::$tpl->set_vars(array(
			'MENU_HEADER_TITLE' =>	Fsb::$session->lang('user_panel'),
		));

		// Si le module demande n'est pas accessible
		if (!$print)
		{
			Http::redirect('index.' . PHPEXT . '?p=profile&module=personal');
		}

		$class_user_module = 'Page_user_' . $this->module;
		$user_module = new $class_user_module();
	}
}


/* EOF */