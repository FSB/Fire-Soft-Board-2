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
 * Permet de gerer l'acces aux pages de l'administration, ainsi que 
 * leur positionement
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Mode de la frame
	 *
	 * @var string
	 */
	public $mode;
	
	
	public $name;

	/**
	 * Constructeur
	 */
	public function main()
	{
		$this->mode = Http::request('mode');
		$this->name = Http::request('name');

		$call = new Call($this);
		$call->post(array(
			'submit' =>	'submit',
		));

		$call->functions(array(
			'mode' => array(
				'up_cat' =>		'page_menu_move_cat',
				'down_cat' =>	'page_menu_move_cat',
				'up_link' =>	'page_menu_move_link',
				'down_link' =>	'page_menu_move_link',
				'submit' =>		'page_menu_submit',
				'default' =>	'page_default_menu',
			),
		));
	}

	/**
	 * Affiche le menu et les options disponibles
	 */
	public function page_default_menu()
	{
		Fsb::$tpl->set_file('adm_menu_edit.html');

		Fsb::$tpl->set_vars(array(
			'U_ACTION' =>	sid('index.' . PHPEXT . '?p=menu_adm'),
		));

		$list_auth = array(
			MODOSUP =>		Fsb::$session->lang('modosup'),
			ADMIN =>		Fsb::$session->lang('admin'),
			FONDATOR =>		Fsb::$session->lang('fondator'),
			(FONDATOR + 1) =>	Fsb::$session->lang('adm_menu_hide_page'),
		);

		$cat_menu = array();
		foreach (Fsb::$menu->data AS $ary)
		{
			$cat_menu[$ary['cat']][] = $ary;
		}

		foreach ($cat_menu AS $cat => $ary)
		{
			Fsb::$tpl->set_blocks('cat', array(
				'NAME' =>	((Fsb::$session->lang('cat_menu_' . $cat)) ? Fsb::$session->lang('cat_menu_' . $cat) : $cat),

				'U_UP' =>	sid('index.' . PHPEXT . '?p=menu_adm&amp;mode=up_cat&amp;name=' . $cat),
				'U_DOWN' =>	sid('index.' . PHPEXT . '?p=menu_adm&amp;mode=down_cat&amp;name=' . $cat),
			));

			foreach ($ary AS $subary)
			{
				$lg_page = (Fsb::$session->lang('menu_' . $subary['page'])) ? Fsb::$session->lang('menu_' . $subary['page']) : $subary['page'];
				Fsb::$tpl->set_blocks('cat.link', array(
					'NAME' =>		$lg_page,
					'LIST_AUTH' =>	Html::make_list('auth_menu_' . $subary['page'], $subary['auth'], $list_auth),

					'U_UP' =>		sid('index.' . PHPEXT . '?p=menu_adm&amp;mode=up_link&amp;name=' . $subary['page']),
					'U_DOWN' =>		sid('index.' . PHPEXT . '?p=menu_adm&amp;mode=down_link&amp;name=' . $subary['page']),
				));
			}
		}
	}

	/**
	 * Deplace une categorie dans le menu
	 */
	public function page_menu_move_cat()
	{
		$move = ($this->mode == 'up_cat') ? -1 : 1;
		Fsb::$menu->move_cat($move, $this->name);
		Http::redirect('index.' . PHPEXT . '?p=menu_adm');
	}

	/**
	 * Deplace un lien dans le menu
	 */
	public function page_menu_move_link()
	{
		$move = ($this->mode == 'up_link') ? -1 : 1;
		Fsb::$menu->move_link($move, $this->name);
		Http::redirect('index.' . PHPEXT . '?p=menu_adm');
	}

	/**
	 * Sauvegarde les modifications des donnees du menu
	 */
	public function page_menu_submit()
	{
		foreach (Fsb::$menu->data AS $key => $value)
		{
			if (Http::request('auth_menu_' . Fsb::$menu->data[$key]['page'], 'post'))
			{
				Fsb::$menu->data[$key]['auth'] = Http::request('auth_menu_' . Fsb::$menu->data[$key]['page'], 'post');
			}
		}
		Fsb::$menu->refresh_menu();

		Log::add(Log::ADMIN, 'menu_log');
		Display::message('adm_menu_well', 'index.' . PHPEXT, 'index_adm');
	}
}

/* EOF */