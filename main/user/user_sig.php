<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On affiche le module si les signatures sont activees
if (Fsb::$cfg->get('activate_sig'))
{
	/**
	 * On affiche le module ?
	 * 
	 * @var bool
	 */
	$show_this_module = true;
}

/**
 * Module d'utilisateur permettant au membre de modifier sa signature
 */
class Page_user_sig extends Fsb_model
{
	/**
	 *  Erreurs
	 *
	 * @var array
	 */
	public $errstr = array();

	/**
	 * Signature
	 *
	 * @var string
	 */
	public $sig;

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		if (Http::request('submit', 'post'))
		{
			$this->check_form();
			if (!$this->errstr)
			{
				$this->submit_form();
			}
		}
		else if (Http::request('preview', 'post'))
		{
			$this->check_form();
			if (!$this->errstr)
			{
				$this->preview_sig();
			}
		}

		Display::fsbcode(true);
		Display::smilies();
		$this->sig_form();
	}

	/**
	 * Affiche le formulaire de modification de la signature
	 */
	public function sig_form()
	{
		if ($this->errstr)
		{
			Fsb::$tpl->set_switch('error');
		}

		// Valeur de la signature
		$value = htmlspecialchars(($this->sig) ? $this->sig : Fsb::$session->data['u_signature']);

		if (Http::request('sig_hidden', 'post') || Fsb::$session->data['u_activate_wysiwyg'])
		{
			$value = Parser_wysiwyg::decode($value);
		}

		Fsb::$tpl->set_switch('use_wysiwyg');
		Fsb::$tpl->set_blocks('onload', array(
			'CODE' =>	'textEditor[\'map_textarea_sig\'] = new FSB_editor_interface(\'map_textarea_sig\', \'' . ((Fsb::$session->data['u_activate_wysiwyg']) ? 'wysiwyg' : 'text') . '\', ' . intval(Fsb::$mods->is_active('wysiwyg')) . ')',
		));

		

		Fsb::$tpl->set_file('user/user_sig.html');
		Fsb::$tpl->set_vars(array(
			'MAX_CHARS' =>			Fsb::$cfg->get('sig_max_chars'),
			'MAX_LINE' =>			Fsb::$cfg->get('sig_max_line'),
			'CONTENT' =>			Html::make_errstr($this->errstr),
			'USER_SIG' =>			$value,
			'USE_WYSIWYG' =>		(Fsb::$mods->is_active('wysiwyg') && Fsb::$session->data['u_activate_wysiwyg']) ? true : false,

			'U_ACTION' =>			sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=sig'),
		));

		Fsb::$tpl->set_blocks('map', array(
			'NAME' =>			'sig',
			'U_BOX_COLOR' =>	sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=color&amp;map_name=sig&amp;color_type=color'),
			'U_BOX_BGCOLOR' =>	sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=color&amp;map_name=sig&amp;color_type=bgcolor'),
			'U_BOX_SMILIES' =>	sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=smilies&amp;map_name=sig'),
			'POS_ITERATOR' =>	0,
			'USE_WYSIWYG' =>	(Fsb::$session->data['u_activate_wysiwyg']) ? true : false,
		));
	}
	
	/**
	 * Affiche un cadre montrant le resultat reel de la signature
	 */
	public function preview_sig()
	{
		$parser = new Parser();

		$preview_sig = $this->sig;
		if (Http::request('sig_hidden', 'post'))
		{
			$preview_sig = Parser_wysiwyg::encode(htmlspecialchars($preview_sig));
		}

		// Informations passees au parseur de message
		$parser_info = array(
			'u_id' =>			Fsb::$session->id(),
			'p_nickname' =>		Fsb::$session->data['u_nickname'],
			'u_auth' =>			Fsb::$session->auth(),
			'is_sig' =>			true,
		);

		Fsb::$tpl->set_switch('preview');
		Fsb::$tpl->set_vars(array(
			'PREVIEW_SIG' =>	$parser->sig($preview_sig, $parser_info),
		));
	}

	/**
	 * Verifie la signature (nombre de lignes, caracteres, etc ...)
	 */
	public function check_form()
	{
		$this->sig = trim(Http::request('sig', 'post'));
		if (Fsb::$session->data['u_activate_wysiwyg'])
		{
			$this->sig = trim(Parser_wysiwyg::encode($this->sig));
		}

		if (Fsb::$cfg->get('sig_max_chars') > 0 && strlen($this->sig) > Fsb::$cfg->get('sig_max_chars'))
		{
			$this->errstr[] = sprintf(Fsb::$session->lang('user_sig_max_chars'), Fsb::$cfg->get('sig_max_chars'));
		}

		if (Fsb::$cfg->get('sig_max_line') > 0 && count(explode("\n", $this->sig)) > Fsb::$cfg->get('sig_max_line'))
		{
			$this->errstr[] = sprintf(Fsb::$session->lang('user_sig_max_line'), Fsb::$cfg->get('sig_max_line'));
		}
	}

	/**
	 * Enregistre la signature
	 */
	public function submit_form()
	{
		Fsb::$db->update('users', array(
			'u_signature' =>	$this->sig,
		), 'WHERE u_id = ' . Fsb::$session->id());

		Log::user(Fsb::$session->id(), 'update_sig');
		Display::message('user_profil_submit', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=sig', 'forum_profil');
	}
}

/* EOF */