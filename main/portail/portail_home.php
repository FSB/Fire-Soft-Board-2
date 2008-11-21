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
 * Module de portail permettant d'afficher quelques suggestions pour l'utilisateurs
 */
class Page_portail_home extends Fsb_model
{
	/**
	 * Constructeur
	 */
	public function main()
	{
		$suggestion = '';
		if (!Fsb::$session->is_logged())
		{
			$u_avatar = '';
			$u_nickname = '';
			$u_total_post = 0;
			$u_total_topic = 0;
			$mp_text = '';
			$suggestion = Fsb::$session->lang('pm_user_not_logged');
		}
		else
		{
			$u_nickname = Fsb::$session->data['u_nickname'];
			$u_total_post = Fsb::$session->data['u_total_post'];
			$u_total_topic = Fsb::$session->data['u_total_topic'];

			// Avatar du membre
			$u_avatar = User::get_avatar(Fsb::$session->data['u_avatar'], Fsb::$session->data['u_avatar_method'], Fsb::$session->data['u_can_use_avatar']);

			// Suggestions
			if (!$u_avatar)
			{
				$suggestion = Fsb::$session->lang('pm_choose_avatar');
				$u_avatar = (Fsb::$mods->is_active('no_avatar')) ? AVATAR_PATH . 'noavatar.gif' : '';
			}

			if (Fsb::$session->data['u_total_mp'] == 1)
			{
				$mp_text = sprintf(Fsb::$session->lang('pm_new_mp'), sid(ROOT . 'index.' . PHPEXT . '?p=mp&amp;box=inbox'));
			}
			else if (Fsb::$session->data['u_total_mp'] > 1)
			{
				$mp_text = sprintf(Fsb::$session->lang('pm_new_mps'), sid(ROOT . 'index.' . PHPEXT . '?p=mp&amp;box=inbox'));
			}
			else
			{
				$mp_text = Fsb::$session->lang('pm_no_new_mp');
			}
		}

		$u_url = sid(ROOT . 'index.' . PHPEXT . '?p=search&amp;mode=author&amp;id=' . Fsb::$session->id());
		Fsb::$tpl->set_vars(array(
			'HOME_USER_NICKNAME' =>	htmlspecialchars($u_nickname),
			'HOME_USER_AVATAR' =>	$u_avatar,
			'HOME_AVATAR_ALT' =>	sprintf(Fsb::$session->lang('user_avatar'), htmlspecialchars($u_nickname)),
			'HOME_USER_STATS' =>	($u_total_post > 0) ? sprintf(Fsb::$session->lang('pm_user_stats'), $u_total_post, $u_total_topic, $u_url) : Fsb::$session->lang('pm_no_post'),
			'HOME_MP' =>			$mp_text,
			'HOME_SUGGESTION' =>	(!$suggestion) ? sprintf(Fsb::$session->lang('pm_no_conseil')) : $suggestion,
			'HOME_REGISTER' =>		sid(ROOT . 'index.' . PHPEXT . '?p=register'),
			'HOME_LOGIN' =>			sid(ROOT . 'index.' . PHPEXT . '?p=login'),
		));
	}
}

/* EOF */