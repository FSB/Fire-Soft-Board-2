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
 * Affiche le profil public d'un membre
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
	 * ID du membre
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		$this->id = intval(Http::request('id'));

		// Si pas connecte on le redirige
		if (!Fsb::$session->is_logged())
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=login&redirect=email&id=' . $this->id);
		}

		$this->check_data();

		$call = new Call($this);
		$call->post(array(
			'submit' =>	':send_email',
		));

		$this->form_email();
	}

	/**
	 * Donnees de l'utilisateur
	 *
	 */
	public function check_data()
	{
		// On verifie si l'utilisateur accepte bien les Emails
		$sql = 'SELECT u_id, u_nickname, u_email, u_language, u_activate_email
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id = ' . $this->id . '
					AND u_id <> ' . VISITOR_ID;
		$result = Fsb::$db->query($sql);
		$this->data = Fsb::$db->row($result);
		Fsb::$db->free($result);

		if (!$this->data)
		{
			Display::message('user_not_exists');
		}
		else if ($this->data['u_activate_email'] & 8)
		{
			Display::message('email_not_accept');
		}
	}

	/**
	 * Affiche le formulaire d'envoie d'Email
	 *
	 */
	public function form_email()
	{
		Fsb::$tpl->set_file('forum/forum_email.html');
		Fsb::$tpl->set_vars(array(
			'EMAIL_TO' =>	sprintf(Fsb::$session->lang('email_send_to'), htmlspecialchars($this->data['u_nickname'])),

			'U_ACTION' =>	sid(ROOT . 'index.' . PHPEXT . '?p=email&amp;id=' . $this->id),
		));
	}

	/**
	 * Envoie un Email
	 *
	 */
	public function send_email()
	{
		$title =	trim(Http::request('title'));
		$content =	trim(Http::request('content'));

		if (empty($content))
		{
			Display::message('email_is_empty');
		}

		if (empty($title))
		{
			$title = Fsb::$session->lang('no_subject');
		}

		$mail = new Notify_mail(Fsb::$session->data['u_email']);
		$mail->AddAddress($this->data['u_email']);
		$mail->Subject = $title;
		$mail->set_file(ROOT . 'lang/' . $this->data['u_language'] . '/mail/user_to_user.txt');
		$mail->set_vars(array(
			'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
			'TO_NICKNAME' =>	htmlspecialchars($this->data['u_nickname']),
			'FROM_NICKNAME' =>	htmlspecialchars(Fsb::$session->data['u_nickname']),
			'CONTENT' =>		htmlspecialchars($content),

			'U_FORUM' =>		Fsb::$cfg->get('fsb_path'),
		));
		$return = $mail->Send();
		$mail->SmtpClose();

		Log::add(Log::EMAIL, 'profile', $this->data['u_nickname'], $content);
		Display::message(($return) ? 'email_sent' : 'email_not_sent', ROOT . 'index.' . PHPEXT . '?p=userprofile&amp;id=' . $this->data['u_id'], 'modo_user');
	}
}

/* EOF */
