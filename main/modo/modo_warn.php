<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On affiche ce module si le membre a l'autorisation de donner des avertissements
if (Fsb::$session->is_authorized('warn_user'))
{
	$show_this_module = true;
}

/**
 * Module de moderation pour donner / supprimer un avertissement a un membre
 *
 */
class Page_modo_warn extends Fsb_model
{
	// Parametres de la page
	public $mode;
	public $id;

	// Pseudonyme et donnees du membre
	public $nickname;
	public $data = array();

	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		$this->nickname =	Http::request('nickname', 'post');
		$this->mode =		Http::request('mode', 'post|get');
		$this->id =			intval(Http::request('id'));

		Fsb::$tpl->set_file('modo/modo_warn.html');

		if ($this->check_login())
		{
			if (Http::request('submit_warn', 'post'))
			{
				$this->submit_warn_form();
			}

			switch ($this->mode)
			{
				case 'less' :
				case 'more' :
					$this->show_warn_form();
				break;

				case 'show' :
					$this->show_warn_list();
				break;
			}
		}

		$list_mode = array(
			'show' =>	Fsb::$session->lang('modo_warn_mode_show'),
			'more' =>	Fsb::$session->lang('modo_warn_mode_more'),
			'less' =>	Fsb::$session->lang('modo_warn_mode_less'),
		);

		Fsb::$tpl->set_vars(array(
			'LIST_MODE' =>		Html::make_list('mode', $this->mode, $list_mode),
			'U_ACTION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=warn&amp;mode=' . $this->mode . '&amp;id=' . $this->id),
			'THIS_NICKNAME' =>	htmlspecialchars($this->nickname),
		));
	}

	/**
	 * Verification du login du membre
	 *
	 * @return bool
	 */
	public function check_login()
	{
		if ($this->id || $this->nickname)
		{
			$sql = 'SELECT u_id, u_auth, u_nickname, u_email, u_language, u_total_warning, u_warn_read, u_warn_post
					FROM ' . SQL_PREFIX . 'users
					WHERE ' . (($this->nickname) ? 'u_nickname = \'' . Fsb::$db->escape($this->nickname) . '\'' : 'u_id = ' . $this->id);
			$result = Fsb::$db->query($sql);
			if (!$this->data = Fsb::$db->row($result))
			{
				Fsb::$tpl->set_switch('nickname_error');
				return (false);
			}

			$this->id = $this->data['u_id'];
			$this->nickname = $this->data['u_nickname'];
			return (true);
		}
		return (false);
	}

	/**
	 * Affiche le formulaire d'avertissement
	 *
	 */
	public function show_warn_form()
	{
		// Les administrateurs ne peuvent pas se prendre d'avertissements
		if ($this->data['u_auth'] >= MODOSUP)
		{
			Display::message('modo_warn_cant_admin');
		}

		// On verifie si on peut ajouter / supprimer un avertissement
		if (!$this->can_warn())
		{
			Fsb::$tpl->set_switch('warn_error');
			Fsb::$tpl->set_vars(array(
				'WARN_ERROR' =>		Fsb::$session->lang('modo_warn_error_' . $this->mode),
			));
			return ;
		}

		// Modes d'envoie de messages
		$list_mode_message = array(
			'pm' =>		Fsb::$session->lang('modo_warn_message_pm'),
			'email' =>	Fsb::$session->lang('modo_warn_message_email'),
		);

		// Liste des etapes temporelles
		$list_time = array(
			0 =>			Fsb::$session->lang('unlimited'),
			ONE_HOUR =>		Fsb::$session->lang('hour'),
			ONE_DAY =>		Fsb::$session->lang('day'),
			ONE_WEEK =>		Fsb::$session->lang('week'),
			ONE_MONTH =>	Fsb::$session->lang('month'),
			ONE_YEAR =>		Fsb::$session->lang('year'),
		);

		// On regarde le type de restriction du membre
		foreach (array('post', 'read') AS $restriction)
		{
			if ($this->data['u_warn_' . $restriction] == 1)
			{
				${$restriction . '_state'} = Fsb::$session->lang('modo_warn_disable_' . $restriction . '_ustate');
			}
			else if ($this->data['u_warn_' . $restriction] == 0 || $this->data['u_warn_' . $restriction] < CURRENT_TIME)
			{
				${$restriction . '_state'} = Fsb::$session->lang('modo_warn_disable_' . $restriction . '_nostate');
			}
			else
			{
				${$restriction . '_state'} = sprintf(Fsb::$session->lang('modo_warn_disable_' . $restriction . '_state'), Fsb::$session->print_date($this->data['u_warn_' . $restriction]));
			}
		}

		Fsb::$tpl->set_switch('warn_form');
		Fsb::$tpl->set_vars(array(
			'L_WARN_USER' =>					Fsb::$session->lang('modo_warn_mode_' . $this->mode),
			'L_MODO_WARN_DISABLE_POST' =>		($this->mode == 'more') ? Fsb::$session->lang('modo_warn_disable_post')		: Fsb::$session->lang('modo_warn_disable_post_less'),
			'L_MODO_WARN_DISABLE_POST_EXP' =>	($this->mode == 'more') ? Fsb::$session->lang('modo_warn_disable_post_exp')	: Fsb::$session->lang('modo_warn_disable_post_exp_less'),
			'L_MODO_WARN_DISABLE_READ' =>		($this->mode == 'more') ? Fsb::$session->lang('modo_warn_disable_read')		: Fsb::$session->lang('modo_warn_disable_read_less'),
			'L_MODO_WARN_DISABLE_READ_EXP' =>	($this->mode == 'more') ? Fsb::$session->lang('modo_warn_disable_read_exp')	: Fsb::$session->lang('modo_warn_disable_read_exp_less'),
			'POST_STATE' =>						$post_state,
			'READ_STATE' =>						$read_state,
			'LIST_MODE_MESSAGE' =>				Html::make_list('mode_message', '', $list_mode_message),
			'LIST_POST_STEP' =>					Html::make_list('disable_post_time', 0, $list_time, array(
													'onfocus' => 'document.getElementById(\'disable_post_id\').checked = true',
												)),
			'LIST_READ_STEP' =>					Html::make_list('disable_read_time', 0, $list_time, array(
													'onfocus' => 'document.getElementById(\'disable_read_id\').checked = true',
												)),
		));
	}

	/**
	 * Soumission de l'avertissement
	 *
	 */
	public function submit_warn_form()
	{
		// On verifie si on peut ajouter / supprimer un avertissement
		if (!$this->can_warn())
		{
			Display::message('modo_warn_error_' . $this->mode);
		}

		Moderation::warn_user($this->mode, Fsb::$session->id(), $this->id, Http::request('warn_reason', 'post'), $this->data['u_warn_post'], $this->data['u_warn_read'], array(
			'post' =>		intval(Http::request('disable_post', 'post')),
			'post_check' =>	intval(Http::request('disable_post_check', 'post')),
			'post_time' =>	intval(Http::request('disable_post_time', 'post')),
			'read' =>		intval(Http::request('disable_read', 'post')),
			'read_check' =>	intval(Http::request('disable_read_check', 'post')),
			'read_time' =>	intval(Http::request('disable_read_time', 'post')),
		));

		// On verifie si on doit lui envoyer un message (email ou MP)
		if ($send_message = trim(Http::request('warn_message', 'post')))
		{
			$send_subject = trim(Http::request('warn_subject', 'post'));
			$send_subject = (!$send_subject) ? Fsb::$session->lang('no_subject') : $send_subject;
			$send_method =	Http::request('mode_message', 'post');

			switch ($send_method)
			{
				case 'pm' :
					Send::send_mp(Fsb::$session->id(), $this->id, $send_subject, $send_message);
				break;

				case 'email' :
					$mail = new Notify_mail();
					$mail->AddAddress($this->data['u_email']);
					$mail->Subject = $send_subject;
					$mail->set_file(ROOT . 'lang/' . $this->data['u_language'] . '/mail/user_to_user.txt');
					$mail->set_vars(array(
						'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
						'TO_NICKNAME' =>	htmlspecialchars($this->data['u_nickname']),
						'FROM_NICKNAME' =>	htmlspecialchars(Fsb::$session->data['u_nickname']),
						'CONTENT' =>		htmlspecialchars($send_message),

						'U_FORUM' =>		Fsb::$cfg->get('fsb_path'),
					));
					$mail->Send();
					$mail->SmtpClose();
				break;
			}
		}

		// Envoie d'un signal au membre pour mettre a jour ses droits
		Sync::signal(Sync::USER, $this->id);

		Log::add(Log::MODO, 'log_warn_' . $this->mode, $this->nickname);
		Log::user($this->id, 'warn_' . $this->mode);
		Display::message('modo_warn_well_' . $this->mode, 'index.' . PHPEXT . '?p=modo&amp;module=warn&amp;mode=show&amp;id=' . $this->id, 'modo_warn');
	}

	/**
	 * Affiche la liste des avertissements du membre
	 *
	 */
	public function show_warn_list()
	{
		Fsb::$tpl->set_switch('warn_list');

		// Liste des avertissements du membre
		$sql = 'SELECT w.*, u.u_nickname, u.u_color
				FROM ' . SQL_PREFIX . 'warn w
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = w.modo_id
				WHERE w.u_id = ' . $this->id . '
				ORDER BY w.warn_time DESC';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			// Restriction de l'avertissement
			$warn_state = '';
			foreach (array('post', 'read') AS $restriction)
			{
				if ($row['warn_restriction_' . $restriction])
				{
					if ($row['warn_restriction_' . $restriction] == 'unlimited')
					{
						$mode = ($row['warn_type'] == WARN_MORE) ? 'more' : 'less';
						$warn_state .= Fsb::$session->lang('modo_warn_state_' . $restriction . '_' . $mode) . '<br />';
					}
					else
					{
						$str = $row['warn_restriction_' . $restriction];
						$mode = ($str[0] == '+') ? 'more' : 'less';
						$time = intval(substr($str, 1));
						if ($time >= ONE_DAY)
						{
							$time = round($time / ONE_DAY);
							$warn_state .= sprintf(Fsb::$session->lang('modo_warn_state_' . $restriction . '_dtime_' . $mode), $time) . '<br />';
						}
						else
						{
							$time = round($time / ONE_HOUR);
							$warn_state .= sprintf(Fsb::$session->lang('modo_warn_state_' . $restriction . '_htime_' . $mode), $time) . '<br />';
						}
					}
				}
			}
			$warn_state = substr($warn_state, 0, -6);

			Fsb::$tpl->set_blocks('warn', array(
				'TITLE' =>		($row['warn_type'] == WARN_MORE) ? Fsb::$session->lang('modo_warn_add_warn') : Fsb::$session->lang('modo_warn_remove_warn'),
				'REASON' =>		nl2br(htmlspecialchars($row['warn_reason'])),
				'DATE' =>		Fsb::$session->print_date($row['warn_time']),
				'WARN_STATE' =>	$warn_state,
				'NICKNAME' =>	Html::nickname($row['u_nickname'], $row['modo_id'], $row['u_color']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Retourne true si on peut donner / retirer un avertissement suivant le mode
	 *
	 * @return bool
	 */
	public function can_warn()
	{
		return (($this->mode == 'more' && $this->data['u_total_warning'] == 5) || ($this->mode == 'less' && $this->data['u_total_warning'] == 0) ? false : true);
	}
}

/* EOF */