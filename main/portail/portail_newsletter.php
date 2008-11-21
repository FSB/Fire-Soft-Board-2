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
 * Module de portail permettant de gerer une newsletter
 */
class Page_portail_newsletter extends Fsb_model
{
	/**
	 * Constructeur
	 */
	public function main()
	{
		$mode = Http::request('mode');

		// Envoie de la newsletter
		if (Http::request('submit_newsletter_send', 'post'))
		{
			$this->send_email();
		}
		// Ajout / suppression d'un membre dans la newsletter
		else if (Http::request('submit_newsletter', 'post'))
		{
			Fsb::$db->update('users', array(
				'u_newsletter' =>		(Fsb::$session->data['u_newsletter']) ? 0 : 1,
			), 'WHERE u_id = ' . Fsb::$session->id());
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=portail');
		}
		// Affichage du template d'envoie de newsletter
		else if ($mode == 'newsletter' && Fsb::$session->auth() >= ADMIN)
		{
			Fsb::$tpl->set_file('portail/portail_newsletter.html');
			Fsb::$tpl->set_vars(array(
				'DEFAULT_SUBJECT' =>	sprintf(Fsb::$session->lang('pm_newsletter_default_subject'), Fsb::$cfg->get('forum_name')),
			));
		}
		else
		{
			Fsb::$tpl->set_switch('show_newsletter');
		}

		Fsb::$tpl->set_vars(array(
			'L_NEWSLETTER_SUBMIT' =>	(Fsb::$session->data['u_newsletter']) ? Fsb::$session->lang('pm_newsletter_off') : Fsb::$session->lang('pm_newsletter_on'),

			'U_PM_NEWSLETTER_ACTION' =>	sid(ROOT . 'index.' . PHPEXT . '?p=portail'),
			'U_PM_NEWSLETTER_ADMIN' =>	sid(ROOT . 'index.' . PHPEXT . '?p=portail&amp;mode=newsletter'),
		));
	}

	/**
	 * Envoie l'email
	 */
	public function send_email()
	{
		// On recupere la liste des membres souscrits a la newsletter
		$sql = 'SELECT u_nickname, u_email, u_language
				FROM ' . SQL_PREFIX . 'users
				WHERE u_newsletter = 1';
		$result = Fsb::$db->query($sql);
		$newsletter = array();
		while ($row = Fsb::$db->row($result))
		{
			if (!isset($newsletter[$row['u_language']]))
			{
				$newsletter[$row['u_language']] = array();
			}
			$newsletter[$row['u_language']][] = $row['u_email'];
		}
		Fsb::$db->free($result);

		// Envoie de la newsletter
		foreach ($newsletter AS $language => $mail_list)
		{
			$mail = new Notify_mail();
			foreach ($mail_list AS $bcc)
			{
				$mail->AddBCC($bcc);
			}

			$mail->Subject = (Http::request('pm_newsletter_subject', 'post')) ? Http::request('pm_newsletter_subject', 'post') : Fsb::$session->lang('no_subject');
			$mail->set_file(ROOT . 'lang/' . $language . '/mail/newsletter.txt');
			$mail->set_vars(array(
				'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
				'CONTENT' =>		htmlspecialchars(Http::request('pm_newsletter_content', 'post')),

				'U_FORUM' =>		Fsb::$cfg->get('fsb_path'),
				'U_UNSUBSCRIBE' =>	Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=portail',
			));
			$mail->Send();
			$mail->SmtpClose();
		}
		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=portail');
	}
}

/* EOF */