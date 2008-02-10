<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/user/user_sig.php
** | Begin :	09/11/2005
** | Last :		12/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

// On affiche le module si les signatures sont activées
if (Fsb::$cfg->get('activate_sig'))
{
	$show_this_module = TRUE;
}

/*
** Module d'utilisateur permettant au membre de modifier sa signature
*/
class Page_user_sig extends Fsb_model
{
	// Erreurs
	public $errstr = array();

	// Signature
	public $sig;

	/*
	** Constructeur
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
		else if (Http::request('set_wysiwyg_on', 'post') !== NULL || Http::request('set_wysiwyg_off', 'post') !== NULL)
		{
			$this->check_form();
			if (!$this->errstr)
			{
				$this->preview_sig();
			}
			$this->update_wysiwyg();
		}
		else if (Http::request('preview', 'post'))
		{
			$this->check_form();
			if (!$this->errstr)
			{
				$this->preview_sig();
			}
		}

		Display::fsbcode(TRUE);
		Display::smilies();
		$this->sig_form();
	}

	/*
	** Affiche le formulaire de modification de la signature
	*/
	public function sig_form()
	{
		if ($this->errstr)
		{
			Fsb::$tpl->set_switch('error');
		}

		// Valeur de la signature
		$value = htmlspecialchars(($this->sig) ? $this->sig : Fsb::$session->data['u_signature']);

		// Si l'éditeur WYSIWYG est activé
		if (Fsb::$session->data['u_activate_wysiwyg'])
		{
			Fsb::$tpl->set_switch('use_wysiwyg');
			Fsb::$tpl->set_blocks('onload', array(
				'CODE' =>		'init_wysiwyg(\'map_textarea_sig\')',
			));

			$value = Parser_wysiwyg::decode($value);
		}

		Fsb::$tpl->set_file('user/user_sig.html');
		Fsb::$tpl->set_vars(array(
			'MAX_CHARS' =>			Fsb::$cfg->get('sig_max_chars'),
			'MAX_LINE' =>			Fsb::$cfg->get('sig_max_line'),
			'CONTENT' =>			Html::make_errstr($this->errstr),
			'USER_SIG' =>			$value,
			'USE_WYSIWYG' =>		(Fsb::$mods->is_active('wysiwyg') && Fsb::$session->data['u_activate_wysiwyg']) ? TRUE : FALSE,

			'U_ACTION' =>			sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=sig'),
		));

		Fsb::$tpl->set_blocks('map', array(
			'NAME' =>			'sig',
			'U_BOX_COLOR' =>	sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=color&amp;map_name=sig&amp;color_type=color'),
			'U_BOX_BGCOLOR' =>	sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=color&amp;map_name=sig&amp;color_type=bgcolor'),
			'U_BOX_SMILIES' =>	sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=smilies&amp;map_name=sig'),
			'POS_ITERATOR' =>	0,
			'USE_WYSIWYG' =>	(Fsb::$session->data['u_activate_wysiwyg']) ? TRUE : FALSE,
		));
	}
	
	/*
	** Affiche un cadre montrant le résultat réel de la signature
	*/
	public function preview_sig()
	{
		$parser = new Parser();

		$preview_sig = $this->sig;
		if (Fsb::$session->data['u_activate_wysiwyg'])
		{
			$preview_sig = Parser_wysiwyg::encode($preview_sig);
		}

		Fsb::$tpl->set_switch('preview');
		Fsb::$tpl->set_vars(array(
			'PREVIEW_SIG' =>	$parser->sig($preview_sig),
		));
	}

	/*
	** Vérifie la signature (nombre de lignes, caractères, etc ...)
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

	/*
	** Enregistre la signature
	*/
	public function submit_form()
	{
		Fsb::$db->update('users', array(
			'u_signature' =>	$this->sig,
		), 'WHERE u_id = ' . Fsb::$session->id());

		Log::user(Fsb::$session->id(), 'update_sig');
		Display::message('user_profil_submit', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=sig', 'forum_profil');
	}

	/*
	** Mise à jour du WYSIWYG
	*/
	public function update_wysiwyg()
	{
		if (Fsb::$session->is_logged())
		{
			Fsb::$session->data['u_activate_wysiwyg'] = (Http::request('set_wysiwyg_on', 'post') !== NULL) ? TRUE : FALSE;
			Fsb::$db->update('users', array(
				'u_activate_wysiwyg' =>		Fsb::$session->data['u_activate_wysiwyg'],
			), 'WHERE u_id = ' . Fsb::$session->id());
		}
	}
}

/* EOF */