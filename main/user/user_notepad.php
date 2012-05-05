<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On affiche le module si il est active
if (Fsb::$mods->is_active('notepad'))
{
	/**
	 * On affiche le module ?
	 * 
	 * @var bool
	 */
	$show_this_module = true;
}

/**
 * Module d'utilisateur permettant au membre de conserver des notes
 */
class Page_user_notepad extends Fsb_model
{
	/**
	 * Contenu du bloc-notes
	 *
	 * @var string
	 */
	public $content;

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		if (Http::request('submit', 'post'))
		{
    		$this->submit_form();
		}

		$this->display_form();
	}

	/**
	 * Affiche le formulaire de modification du bloc-notes
	 */
	public function display_form()
	{
        $content = htmlspecialchars(($this->content) ? $this->content : Fsb::$session->data['u_notepad']);
        Fsb::$tpl->set_file('user/user_notepad.html');
		Fsb::$tpl->set_vars(array(
			'CONTENT' => $content,
            
			'U_ACTION' => sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=notepad'),
		));
	}

	/**
	 * Enregistre le bloc-notes
	 */
	public function submit_form()
	{
        $this->content = trim(Http::request('notepad', 'post'));
		Fsb::$db->update('users', array(
			'u_notepad' =>	$this->content,
		), 'WHERE u_id = ' . Fsb::$session->id());

		Log::user(Fsb::$session->id(), 'update_notepad');
		Display::message('user_profil_submit', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=notepad', 'forum_profil');
	}
}

/* EOF */