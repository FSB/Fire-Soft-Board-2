<?php
/*
** +---------------------------------------------------+
** | Name :			~/main/forum/forum_contact.php
** | Begin :		31/10/2006
** | Last :			11/01/2008
** | User :			Genova
** | Project :		Fire-Soft-Board 2 - Copyright FSB group
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Contacte un administrateur
*/
class Fsb_frame_child extends Fsb_frame
{
	// Paramètres d'affichage de la page (barre de navigation, boite de stats)
	public $_show_page_header_nav = TRUE;
	public $_show_page_footer_nav = FALSE;
	public $_show_page_stats = FALSE;

	public $id;
	public $use_captcha = TRUE;

	/*
	** Constructeur
	*/
	public function main()
	{
		if (!Fsb::$mods->is_active('contact_form'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		$this->use_captcha = TRUE;

		// Uniquement accessible aux visiteurs
		if (Fsb::$session->is_logged())
		{
			Http::redirect(ROOT . 'index.' . PHPEXT);
		}

		if (Http::request('submit', 'post'))
		{
			$this->send_contact();
		}
		$this->form_contact();
	}

	/*
	** Affiche le formulaire d'envoie d'Email
	*/
	public function form_contact()
	{
		// Liste des méthodes de contact
		$list_method = array(
			'mp' =>		Fsb::$session->lang('contact_mp'),
			'email' =>	Fsb::$session->lang('contact_email'),
		);

		// MP désactivés ?
		if (!Fsb::$cfg->get('mp_activated'))
		{
			unset($list_method['mp']);
		}

		// Liste des administrateurs
		$sql = 'SELECT u_id, u_nickname, u_auth
				FROM ' . SQL_PREFIX . 'users
				WHERE u_auth >= ' . ADMIN . '
				ORDER BY u_auth DESC, u_nickname';
		$result = Fsb::$db->query($sql);
		$list_admin = array();
		$selected = NULL;
		while ($row = Fsb::$db->row($result))
		{
			if ($selected === NULL || $row['u_auth'] == FONDATOR)
			{
				$selected = array($row['u_id']);
			}
			$list_admin[$row['u_id']] = htmlspecialchars($row['u_nickname']) . (($row['u_auth'] == FONDATOR) ? ' (' . Fsb::$session->lang('fondator') . ')' : '');
		}
		Fsb::$db->free($result);

		// Captcha ?
		if ($this->use_captcha)
		{
			Fsb::$tpl->set_switch('contact_captcha');
		}

		Fsb::$tpl->set_file('forum/forum_email.html');
		Fsb::$tpl->set_switch('page_contact');
		Fsb::$tpl->set_vars(array(
			'EMAIL_TO' =>		Fsb::$session->lang('nav_contact'),
			'LIST_METHOD' =>	Html::create_list('contact_method', 'mp', $list_method),
			'LIST_ADMIN' =>		Html::create_list('contact_admin[]', $selected, $list_admin, 'multiple="multiple" size="5"'),
			'CAPTCHA' =>		sid(ROOT . 'main/visual_confirmation.' . PHPEXT . '?mode=contact_captcha&amp;uniqd=' . md5(rand(1, time()))),

			'U_ACTION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=contact'),
		));
	}

	/*
	** Envoie un Email
	*/
	public function send_contact()
	{
		$title =		trim(Http::request('title', 'post'));
		$content =		trim(Http::request('content', 'post'));
		$method =		Http::request('contact_method', 'post');
		$list_admin =	(array) Http::request('contact_admin', 'post');
		$list_admin =	array_map('intval', $list_admin);
		$email =		trim(Http::request('email', 'post'));

		// Vérification du captcha
		if ($this->use_captcha && !check_captcha(Http::request('captcha_code', 'post')))
		{
			Display::message('contact_bad_captcha');
		}

		if (!$list_admin)
		{
			Display::message('contact_need_admin');
		}

		if (empty($content))
		{
			Display::message('email_is_empty');
		}

		if (empty($title))
		{
			$title = Fsb::$session->lang('no_subject');
		}

		if (empty($email) || !User::email_valid($email))
		{
			Display::message('contact_need_email');
		}

		// On vérifie que les ID sont bien des ID d'administrateurs
		$sql = 'SELECT u_id, u_language, u_email
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id IN (' . implode(', ', $list_admin) . ')
					AND u_auth >= ' . ADMIN;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			if (!isset($bcc[$row['u_language']]))
			{
				$bcc[$row['u_language']] = array();
			}
			$bcc[$row['u_language']][$row['u_id']] = $row['u_email'];
		}
		Fsb::$db->free($result);

		switch ($method)
		{
			case 'email' :
				foreach ($bcc AS $mail_lang => $mail_list)
				{
					$mail = new Notify_mail();
					foreach ($mail_list AS $item)
					{
						$mail->AddBCC($item);
					}

					$mail->Subject = $title;
					$mail->set_file(ROOT . 'lang/' . $mail_lang . '/mail/contact_admin.txt');
					$mail->set_vars(array(
						'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
						'FORUM_URL' =>		Fsb::$cfg->get('fsb_path'),
						'IP' =>				Fsb::$session->ip,
						'CONTENT' =>		$content,
						'EMAIL' =>			$email,
					));
					$mail->Send();
					$mail->SmtpClose();
					unset($mail);
				}

				Log::add(Log::EMAIL, 'contact', $content);
				Display::message('email_sent', ROOT . 'index.' . PHPEXT, 'forum_index');
			break;

			case 'mp' :
				if (Fsb::$cfg->get('mp_activated'))
				{
					$to_id = array();
					foreach ($bcc AS $data)
					{
						$to_id += array_keys($data);
					}
					Send::send_mp(VISITOR_ID, $to_id, $title, sprintf(Fsb::$session->lang('contact_content_mp'), Fsb::$session->ip, $email, $content));
				}

				Display::message('contact_well_send', ROOT . 'index.' . PHPEXT, 'forum_index');
			break;
		}
	}
}

/* EOF */
