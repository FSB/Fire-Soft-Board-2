<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On affiche ce module si le membre a le droit d'editer des utilisateurs
if (Fsb::$session->is_authorized('auth_edit_user'))
{
	$show_this_module = true;
}

/**
 * Module de moderation pour le profil d'un membre
 *
 */
class Page_modo_user extends Fsb_model
{
	/**
	 * ID du membre a moderer
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Pseudonyme du membre
	 *
	 * @var string
	 */
	public $nickname;

	/**
	 * Donnees du membre
	 *
	 * @var array
	 */
	public $data = array();
	
	/**
	 * Donnees du membre
	 *
	 * @var array
	 */
	public $userdata = array();

	/**
	 * Peut faire de la grosse moderation ?
	 *
	 * @var bool
	 */
	public $edit_extra = false;

	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		// L'utilisateur peut faire de la moderation avancee sur le membre ?
		$this->edit_extra = Fsb::$session->is_authorized('auth_extra_edit_user');

		$this->get_user_data();
		if (Http::request('submit_act_email'))
		{
			$this->send_act_email();
		}
		else if (Http::request('submit_user', 'post'))
		{
			$this->update_user();
		}

		$this->show_form();
		if ($this->nickname)
		{
			$this->show_profile();
		}
	}
	
	/**
	 * Récupère les informations sur un membre
	 *
	 */
	public function get_user_data()
	{
		$this->id =			intval(Http::request('id'));
		$this->nickname =	trim(Http::request('nickname'));

		$sql = 'SELECT u.*, up.*
				FROM ' . SQL_PREFIX . 'users u
				INNER JOIN ' . SQL_PREFIX . 'users_password up
					ON u.u_id = up.u_id
				WHERE u.u_id <> ' . VISITOR_ID . '
					AND ' . (($this->id) ? 'u.u_id = ' . $this->id : 'u.u_nickname = \'' . Fsb::$db->escape($this->nickname) . '\'');
		$row = Fsb::$db->request($sql);

		$this->id = $row['u_id'];
		$this->nickname = $row['u_nickname'];
		$this->userdata = $row;
	}

	/**
	 * Affiche le formulaire de base pour entrer l'ID
	 *
	 */
	public function show_form()
	{
		Fsb::$tpl->set_file('modo/modo_user.html');
		Fsb::$tpl->set_vars(array(
			'THIS_NICKNAME' =>	htmlspecialchars($this->nickname),
			'U_ACTION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=user'),
		));
	}

	/**
	 * Affiche les donnees du profil du membre a moderer
	 *
	 */
	public function show_profile()
	{
		// On affiche uniquement ce que le moderateur peut moderer
		if (!Fsb::$session->is_authorized('auth_edit_user'))
		{
			Display::message('not_allowed');
		}

		if (!$this->id)
		{
			return ;
		}

		// Liste des rangs
		$sql = 'SELECT rank_id, rank_name
				FROM ' . SQL_PREFIX . 'ranks
				WHERE rank_special = 1
				ORDER BY rank_name';
		$result = Fsb::$db->query($sql, 'ranks_');
		$list_rank = array(0 => Fsb::$session->lang('none'));
		while ($row = Fsb::$db->row($result))
		{
			$list_rank[$row['rank_id']] = '- ' . htmlspecialchars($row['rank_name']);
		}
		Fsb::$db->free($result);

		// Liste des groupes par defaut
		$sql = 'SELECT g.g_id, g.g_name
				FROM ' . SQL_PREFIX . 'groups_users gu
				INNER JOIN ' . SQL_PREFIX . 'groups g
					ON gu.g_id = g.g_id
				WHERE gu.u_id = ' . $this->id . '
					AND g.g_type <> ' . GROUP_SINGLE;
		$result = Fsb::$db->query($sql);
		$list_groups = array();
		while ($row = Fsb::$db->row($result))
		{
			$list_groups[$row['g_id']] = (Fsb::$session->lang($row['g_name'])) ? Fsb::$session->lang($row['g_name']) : htmlspecialchars($row['g_name']);
		}
		Fsb::$db->free($result);

		// Liste de supression du membre
		$list_user_delete = array(
			'none' =>		Fsb::$session->lang('modo_user_delete_none'),
			'visitor' =>	Fsb::$session->lang('modo_user_delete_visitor'),
			'topics' =>		Fsb::$session->lang('modo_user_delete_topics'),
		);

		$list_ban_length = Html::make_list('ban_length_unit', ONE_HOUR, array(
			ONE_HOUR =>		Fsb::$session->lang('hour'),
			ONE_DAY =>		Fsb::$session->lang('day'),
			ONE_WEEK =>		Fsb::$session->lang('week'),
			ONE_MONTH =>	Fsb::$session->lang('month'),
			ONE_YEAR =>		Fsb::$session->lang('year'),
		));

		Fsb::$tpl->set_switch('modo_user');
		Fsb::$tpl->set_vars(array(
			'CAN_USE_AVATAR_YES' =>	($this->userdata['u_can_use_avatar']) ? 'checked="checked"' : '',
			'CAN_USE_AVATAR_NO' =>	(!$this->userdata['u_can_use_avatar']) ? 'checked="checked"' : '',
			'CAN_USE_SIG_YES' =>	($this->userdata['u_can_use_sig']) ? 'checked="checked"' : '',
			'CAN_USE_SIG_NO' =>		(!$this->userdata['u_can_use_sig']) ? 'checked="checked"' : '',
			'NEW_LOGIN' =>			htmlspecialchars($this->userdata['u_login']),
			'NEW_NICKNAME' =>		htmlspecialchars($this->userdata['u_nickname']),
			'USER_EMAIL' =>			htmlspecialchars($this->userdata['u_email']),
			'NEW_PASSWORD' =>		'',
			'NEW_SIG' =>			htmlspecialchars($this->userdata['u_signature']),
			'NEW_AVATAR' =>			htmlspecialchars($this->userdata['u_avatar']),
			'COMMENT' =>			htmlspecialchars($this->userdata['u_comment']),
			'LIST_USER_DELETE' =>	Html::make_list('delete_type', 'none', $list_user_delete),
			'LIST_BAN_LENGTH' =>	$list_ban_length,
			'USER_ACTIVATED' =>		$this->userdata['u_activated'],
			'USER_APPROVED' =>		$this->userdata['u_approve'],

			'LIST_RANK' =>			Html::make_list('u_rank_id', $this->userdata['u_rank_id'], $list_rank),
			'LIST_DEFAULT' =>		Html::make_list('u_default_group_id', $this->userdata['u_default_group_id'], $list_groups),
		));

		// Si le compte n'est ni active, ni confirme par Email, on peut lui renvoyer un Email
		if (!$this->userdata['u_activated'] && $this->userdata['u_confirm_hash'] && $this->userdata['u_confirm_hash'] != '.')
		{
			Fsb::$tpl->set_switch('can_send_act_email');
		}
		
		if ($this->edit_extra)
		{
			Fsb::$tpl->set_switch('edit_extra');
		}

		if ($this->userdata['u_auth'] < ADMIN)
		{
			Fsb::$tpl->set_switch('is_not_administrator');
		}

		Profil_fields_forum::form(PROFIL_FIELDS_PERSONAL, 'personal', $this->id);
	}

	/**
	 * Modifie le profil du membre
	 *
	 */
	public function update_user()
	{
		// Membre inexistant ?
		if (!$this->id)
		{
			Display::message('modo_user_user_dont_exist');
		}

		// Suppression de l'utilisateur ?
		if (Http::request('delete_type', 'post') && Http::request('delete_type', 'post') != 'none' && $this->edit_extra)
		{
			$this->confirm_and_delete();
		}

		Profil_fields_forum::validate(PROFIL_FIELDS_PERSONAL, 'personal', $errstr, $this->id);

		// On recupere les donnees du formulaire
		foreach ($_POST AS $key => $value)
		{
			if (is_string($value))
			{
				$this->data[$key] = trim($value);
			}
		}
		
		$update_array = array();
		$update_pwd_array = array();

		// Nouveau pseudo ? On verifie si il n'est pas deja pris
		if (isset($this->data['new_nickname']) && $this->data['new_nickname'] && $this->edit_extra)
		{
			if (strtolower($this->data['new_nickname']) != strtolower($this->userdata['u_nickname']) && User::nickname_exists($this->data['new_nickname']))
			{
				Display::message('modo_user_nickname_exists');
			}
			$update_array['u_nickname'] = $this->data['new_nickname'];

			// On renomme le membre
			User::rename($this->id, $update_array['u_nickname'], false);
		}

		if ($this->edit_extra)
		{
			// On verifie si le login n'est pas deja pris
			if (strtolower($this->userdata['u_login']) != strtolower($this->data['new_login']) && User::login_exists($this->data['new_login']))
			{
				Display::message('modo_user_login_exists');
			}

			$update_pwd_array['u_login'] =	$this->data['new_login'];
			$update_array['u_signature'] =	$this->data['new_sig'];
			$update_array['u_rank_id'] =	intval($this->data['u_rank_id']);
			$update_array['u_approve'] =	intval($this->data['u_approve']);

			if ($this->data['u_email'])
			{
				$update_array['u_email'] = $this->data['u_email'];
			}

			// Mise a jour de l'avatar
			if ($this->data['u_avatar'])
			{
				if ($this->data['u_avatar'] != $this->userdata['u_avatar'])
				{
					$update_array['u_avatar'] = $this->data['u_avatar'];
					$update_array['u_avatar_method'] = AVATAR_METHOD_LINK;
				}
			}
			else
			{
				$update_array['u_avatar'] = '';
				$update_array['u_avatar_method'] = 0;
			}

			// Mise a jour du groupe par defaut
			$sql = 'SELECT g.g_color
					FROM ' . SQL_PREFIX . 'groups_users gu
					INNER JOIN ' . SQL_PREFIX . 'groups g
						ON g.g_id = gu.g_id
					WHERE gu.g_id = ' . intval($this->data['u_default_group_id']) . '
						AND gu.u_id = ' . $this->id;
			if ($group = Fsb::$db->request($sql))
			{
				$update_array['u_default_group_id'] = intval($this->data['u_default_group_id']);
				$update_array['u_color'] = $group['g_color'];
			}

			// Activation du membre
			if (!is_null(Http::request('u_activated', 'post')) && $this->userdata['u_auth'] < ADMIN)
			{
				$update_array['u_activated'] = intval(Http::request('u_activated', 'post'));

				// En cas de desactivation on supprime la session du membre
				if (!$update_array['u_activated'])
				{
					$sql = 'DELETE FROM ' . SQL_PREFIX . 'sessions
							WHERE s_id = ' . $this->id;
					Fsb::$db->query($sql);
				}
			}

			// Verification du bannissement
			$this->check_ban_user();

			// Mise a jour du rang du groupe unique du membre
			Fsb::$db->update('groups', array(
				'g_rank' =>		$update_array['u_rank_id'],
			), 'WHERE g_id = ' . $this->userdata['u_single_group_id'] . ' AND g_type = ' . GROUP_SINGLE);
		}
		$update_array['u_can_use_avatar'] = intval($this->data['u_can_use_avatar']);
		$update_array['u_can_use_sig'] = intval($this->data['u_can_use_sig']);
		$update_array['u_comment'] = $this->data['u_comment'];

		// Nouveau mot de passe ?
		if (!empty($this->data['new_password']) && $this->edit_extra)
		{
			$update_pwd_array['u_password'] = Password::hash($this->data['new_password'], $this->userdata['u_algorithm'], $this->userdata['u_use_salt']);
		}

		// Mise a jour des donnees du membre
		Fsb::$db->update('users', $update_array, 'WHERE u_id = ' . $this->id);

		if ($update_pwd_array)
		{
			Fsb::$db->update('users_password', $update_pwd_array, 'WHERE u_id = ' . $this->id);
		}

		Log::add(Log::MODO, 'log_user', $this->userdata['u_nickname']);
		Log::user($this->id, 'moderate_profile');
		Display::message('modo_user_well', ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=user&amp;id=' . $this->id, 'modo_user');
	}
	
	/**
	 * Confirmation de la suppression du membre + suppression
	 *
	 */
	public function confirm_and_delete()
	{
		$delete_type = Http::request('delete_type', 'post');
		if (check_confirm())
		{
			// On verifie que le membre ne soit pas un administrateur
			$sql = 'SELECT u_auth
					FROM ' . SQL_PREFIX . 'users
					WHERE u_id = ' . $this->id;
			$u_auth = Fsb::$db->get($sql, 'u_auth');
			if ($u_auth >= MODOSUP)
			{
				Display::message('modo_user_delete_admin');
			}

			// Verification du bannissement
			$this->check_ban_user();

			// Suppression du membre
			User::delete($this->id, $delete_type);
			Log::add(Log::MODO, 'delete_user_log', $this->userdata['u_nickname']);
			Display::message('modo_user_delete_well', 'index.' . PHPEXT . '?p=modo&amp;module=user', 'modo_user');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=modo&module=user&id=' . $this->id);
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('modo_user_delete_confirm'), 'index.' . PHPEXT . '?p=modo&amp;module=user', array(
				'id' =>				$this->id,
				'submit_user' =>	true,
				'delete_type' =>	$delete_type,
				'ban_username' =>	Http::request('ban_username'),
				'ban_email' =>		Http::request('ban_email'),
			));
		}
	}

	/**
	 * Bannissement du membre
	 *
	 */
	public function check_ban_user()
	{
		$length =	intval(Http::request('ban_length', 'post'));
		$unit =		intval(Http::request('ban_length_unit', 'post'));
		$total_length = ($length > 0) ? CURRENT_TIME + ($length * $unit) : 0;
		if (Http::request('ban_username'))
		{
			Moderation::ban('login', $this->userdata['u_nickname'], '', $total_length, false);
			Log::add(Log::ADMIN, 'ban_log_add_login', $this->userdata['u_nickname']);
		}

		if (Http::request('ban_email'))
		{
			Moderation::ban('mail', $this->userdata['u_email'], '', $total_length, false);
			Log::add(Log::ADMIN, 'ban_log_add_mail', $this->userdata['u_email']);
		}
	}

	/**
	 * Renvoie de l'Email d'activation
	 *
	 */
	public function send_act_email()
	{
		if ($this->edit_extra && !$this->userdata['u_activated'] && $this->userdata['u_confirm_hash'] && $this->userdata['u_confirm_hash'] != '.')
		{
			$mail = new Notify_mail();
			$mail->AddAddress($this->userdata['u_email']);
			$mail->Subject = sprintf(Fsb::$session->lang('subject_register'), Fsb::$cfg->get('forum_name'));
			$mail->set_file(ROOT . 'lang/' . Fsb::$cfg->get('default_lang') . '/mail/register_reconfirm.txt');
			$mail->set_vars(array(
				'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
				'LOGIN' =>			$this->userdata['u_login'],
				'U_CONFIRM' =>		Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=login&id=' . $this->id . '&confirm=' . urlencode($this->userdata['u_confirm_hash']),
				'U_FORUM' =>		Fsb::$cfg->get('fsb_path'),
			));
			$mail->Send();
			$mail->SmtpClose();
		}

		Log::user($this->id, 'send_act_email');
		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=user&amp;id=' . $this->id);
	}
}

/* EOF */
