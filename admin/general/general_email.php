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
 * Permet d'envoyer un Email de masse a un groupe
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Erreurs rencontrees lors de l'envoie
	 *
	 * @var array
	 */
	public $errstr = array();
	
	/**
	 * Donnee du mail
	 *
	 * @var array
	 */
	public $data = array();

	/**
	 * Maximum de membres par Email
	 *
	 * @var int
	 */
	public $max_user_per_email = 100;
	
	/**
	 * Nombre de mail envoye
	 * 
	 * @var int
	 */
	private $total = 0;

	/**
	 * Constructeur
	 */
	public function main()
	{
		$this->data = array(
			'content' =>	trim(Http::request('email_content')),
			'subject' =>	trim(Http::request('email_subject')),
			'users' =>		trim(Http::request('email_users')),
			'groups' =>		(array) Http::request('email_groups'),
			'idx' =>		array(),
		);

		if (Http::request('submit', 'post'))
		{
			$this->check_form();
			if (!$this->errstr)
			{
				$this->send_email();
			}
		}
		$this->form_email();
	}

	/**
	 * Formulaire d'envoie de l'Email
	 */
	public function form_email()
	{
		if ($this->errstr)
		{
			Fsb::$tpl->set_switch('error_handler');
		}

		// Liste des groupes
		$list_groups = Html::list_groups('email_groups[]', GROUP_SPECIAL|GROUP_NORMAL, $this->data['groups'], true, array(GROUP_SPECIAL_VISITOR));

		Fsb::$tpl->set_switch('email_mass');
		Fsb::$tpl->set_vars(array(
			'LIST_GROUPS' =>		$list_groups,
			'VALUE_SUBJECT' =>		htmlspecialchars($this->data['subject']),
			'VALUE_USERS' =>		htmlspecialchars($this->data['users']),
			'VALUE_CONTENT' =>		htmlspecialchars($this->data['content']),
			'CONTENT' =>			Html::make_errstr($this->errstr),

			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=general_email'),
		));
	}

	/**
	 * Verifie les donnees envoyees par le formulaire
	 */
	public function check_form()
	{
		if (empty($this->data['content']))
		{
			$this->errstr[] = Fsb::$session->lang('adm_email_need_content');
		}

		if (empty($this->data['subject']))
		{
			$this->data['subject'] = Fsb::$session->lang('no_subject');
		}

		//On prepare la liste des membres fournie
		$sql_nickname = array();
		foreach (explode("\n", $this->data['users']) AS $nickname)
		{
			$nickname = trim($nickname);
			if (!empty($nickname))
			{
				$sql_nickname[$nickname] = $nickname;
			}
		}
		
		// On verifie si les membres envoyes existent
		if ($sql_nickname)
		{
			$sql = 'SELECT u_id, u_nickname, u_language, u_email
					FROM ' . SQL_PREFIX . 'users
					WHERE u_nickname IN (\'' . implode('\', \'', $sql_nickname) . '\')
						AND u_id <> 0';
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				if (!isset($this->data['idx'][$row['u_language']]))
				{
					$this->data['idx'][$row['u_language']] = array();
				}
				$this->data['idx'][$row['u_language']][$row['u_id']] = $row['u_email'];
				$this->total++;
				unset($sql_nickname[$row['u_nickname']]);
			}
			Fsb::$db->free($result);

			// Les logins encore dans la liste sont des logins inexistants ...
			foreach ($sql_nickname AS $nickname)
			{
				$this->errstr[] = sprintf(Fsb::$session->lang('adm_email_login_not_exists'), htmlspecialchars($nickname));
			}
		}
		
		// Netoyage de la liste des groupes
		$this->data['groups'] = array_map('intval', $this->data['groups']);
		
		// On recupere les membres du groupe
		if($this->data['groups'])
		{
			$sql = 'SELECT u.u_id, u.u_language, u.u_email
					FROM ' . SQL_PREFIX . 'groups g
					LEFT JOIN ' . SQL_PREFIX . 'groups_users gu
						ON gu.g_id = g.g_id
					INNER JOIN ' . SQL_PREFIX . 'users u
						ON gu.u_id = u.u_id
					WHERE g.g_id IN (' . implode(', ', $this->data['groups']) . ')
						AND g.g_id <> ' . GROUP_SPECIAL_VISITOR . '
					GROUP BY u.u_id, u.u_language, u.u_email';
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				if (!isset($this->data['idx'][$row['u_language']]))
				{
					$this->data['idx'][$row['u_language']] = array();
				}
				$this->data['idx'][$row['u_language']][$row['u_id']] = $row['u_email'];
				$this->total++;
			}
			Fsb::$db->free($result);
		}
		
		//On verifie qu'on ait au moins une personne a mailer
		if (!$this->data['idx'])
		{
			$this->errstr[] = Fsb::$session->lang('adm_email_no_dest');
		}
	}

	/**
	 * Envoie d'Email aux membres / groupes concernes
	 */
	public function send_email()
	{
		@set_time_limit(0);
		
		$result_email = true;
		
		//On verifie si on fait des envoi multiples ou pas
		if($this->total < $this->max_user_per_email)
		{
			$this->send_email_part($result_email, $this->data['idx']);
		}
		else
		{
			$mail_array = array();
			$cmpt = 0;
			foreach($this->data['idx'] as $lang => $mails)
			{
				foreach($mails AS $v)
				{
					$mail_array[$lang][] = $v;
					$cmpt++;
					// On limite le nombre de destinataires (100 par defaut) par Email en BCC
					if (($cmpt == $this->max_user_per_email) && $result_email)
					{
						$this->send_email_part($result_email, $mail_array);
						$mail_array = array();
						$cmpt = 0;
					}
				}
			}
			
			//Si il reste des mail à envoyer on le fait
			if($result_email && $mail_array)
			{
				$this->send_email_part($result_email, $mail_array);
				printr($mail_array);
			}
		}

		Log::add(Log::EMAIL, 'mass', $this->data['content']);
		Display::message(($result_email) ? 'adm_email_send_well' : 'adm_email_send_bad', 'index.' . PHPEXT . '?p=general_email', 'general_email');
	}
	
	/**
	 * Envoie de l'Email en plusieurs parties
	 *
	 * @param bool $result_email Succes d'envoie de l'Email
	 * @param array $mail_array Tableau des mails tries par langue
	 */
	public function send_email_part(&$result_email, $mail_array)
	{
		foreach ($mail_array AS $mail_lang => $mail_list)
		{
			$mail = new Notify_mail();
			foreach ($mail_list AS $bcc)
			{
				$mail->AddBCC($bcc);
			}

			$mail->Subject = htmlspecialchars($this->data['subject']);
			$mail->set_file(ROOT . 'lang/' . $mail_lang . '/mail/mass.txt');
			$mail->set_vars(array(
				'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
				'CONTENT' =>		array($this->data['content']),

				'U_FORUM' =>		Fsb::$cfg->get('fsb_path'),
			));
			$result_email = $mail->Send();
			$mail->SmtpClose();
			unset($mail);
			if (!$result_email)
			{
				return ;
			}
		}
	}
}

/* EOF */