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
 * On affiche le module
 * 
 * @var bool
 */
$show_this_module = true;

/**
 * Module d'utilisateur permettant l'import / export de FSBcards
 */
class Page_user_fsbcard extends Fsb_model
{
	/**
	 * Constructeur
	 */
	public function __construct()
	{
		if (Http::request('submit_export', 'post'))
		{
			$this->submit_export();
		}
		else if (Http::request('submit_import', 'post'))
		{
			$this->submit_import();
		}
		$this->show_form();
	}

	/**
	 * Affiche le formulaire d'import / export de FSBcard
	 */
	public function show_form()
	{
		Fsb::$tpl->set_file('user/user_fsbcard.html');
		Fsb::$tpl->set_vars(array(
			'U_ACTION' =>	sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=fsbcard'),
		));
	}

	/**
	 * Export de la FSBcard
	 */
	public function submit_export()
	{
		$fsbcard = new Fsbcard();

		// Informations personelles
		$fsbcard->set_template(Fsb::$session->data['u_tpl']);
		$fsbcard->set_lang(Fsb::$session->data['u_language']);
		$fsbcard->set_date(Fsb::$session->data['u_utc'], Fsb::$session->data['u_utc_dst']);
		$fsbcard->set_birthday(substr(Fsb::$session->data['u_birthday'], 0, 2), substr(Fsb::$session->data['u_birthday'], 3, 2), substr(Fsb::$session->data['u_birthday'], 6, 4));

		if (Fsb::$session->data['u_sexe'] == SEXE_MALE)
		{
			$fsbcard->set_sexe('male');
		}
		else if (Fsb::$session->data['u_sexe'] == SEXE_FEMALE)
		{
			$fsbcard->set_sexe('female');
		}
		else
		{
			$fsbcard->set_sexe('none');
		}

		// Preferences
		if (Http::request('export_options'))
		{
			// Affichage de l'Email
			if (Fsb::$session->data['u_activate_email'] & 2)
			{
				$fsbcard->set_option('displayEmail', 'extern');
			}
			else if (Fsb::$session->data['u_activate_email'] & 4)
			{
				$fsbcard->set_option('displayEmail', 'intern');
			}
			else
			{
				$fsbcard->set_option('displayEmail', 'hide');
			}

			// Auto notification
			if (Fsb::$session->data['u_activate_auto_notification'] & NOTIFICATION_EMAIL && Fsb::$session->data['u_activate_auto_notification'] & NOTIFICATION_AUTO)
			{
				$fsbcard->set_option('notifyPost', 'auto_email');
			}
			else if (Fsb::$session->data['u_activate_auto_notification'] & NOTIFICATION_EMAIL)
			{
				$fsbcard->set_option('notifyPost', 'none_email');
			}
			else if (Fsb::$session->data['u_activate_auto_notification'] & NOTIFICATION_AUTO)
			{
				$fsbcard->set_option('notifyPost', 'auto');
			}
			else
			{
				$fsbcard->set_option('notifyPost', 'none');
			}

			// Redirection automatique
			if (Fsb::$session->data['u_activate_redirection'] & 2)
			{
				$fsbcard->set_option('redirect', 'none');
			}
			else if (Fsb::$session->data['u_activate_redirection'] & 4)
			{
				$fsbcard->set_option('redirect', 'direct');
			}
			else
			{
				$fsbcard->set_option('redirect', 'indirect');
			}

			// Notification MP
			$fsbcard->set_option('notifyMp', (Fsb::$session->data['u_activate_mp_notification']) ? true : false);

			// Connexion invisible
			$fsbcard->set_option('sessionHidden', (Fsb::$session->data['u_activate_hidden']) ? true : false);

			// Affichage de l'avatar dans les sujets
			$fsbcard->set_option('displayAvatar', (Fsb::$session->data['u_activate_avatar']) ? true : false);

			// Affichage de la signature dans les sujets
			$fsbcard->set_option('displaySig', (Fsb::$session->data['u_activate_sig']) ? true : false);

			// WYSIWYG
			$fsbcard->set_option('wysiwyg', (Fsb::$session->data['u_activate_wysiwyg']) ? true : false);

			// Ajax
			$fsbcard->set_option('ajax', (Fsb::$session->data['u_activate_ajax']) ? true : false);

			// Affichage FSBcode
			$fsbcard->set_option('displayFsbcode', array(
				'posts' =>	(Fsb::$session->data['u_activate_fscode'] & 2) ? true : false,
				'sigs' =>	(Fsb::$session->data['u_activate_fscode'] & 4) ? true : false,
			));

			// Affichage images
			$fsbcard->set_option('displayImg', array(
				'posts' =>	(Fsb::$session->data['u_activate_img'] & 2) ? true : false,
				'sigs' =>	(Fsb::$session->data['u_activate_img'] & 4) ? true : false,
			));
		}

		// Informations de connexion
		if (Http::request('export_register', 'post'))
		{
			$sql = 'SELECT u_password, u_algorithm, u_use_salt
					FROM ' . SQL_PREFIX . 'users_password
					WHERE u_id = ' . Fsb::$session->id();
			$pwd_data = Fsb::$db->request($sql);

			$password = Http::request('export_password', 'post');
			if (Password::hash($password, $pwd_data['u_algorithm'], $pwd_data['u_use_salt']) !== $pwd_data['u_password'])
			{
				Display::message('user_fsbcard_bad_password');
			}

			$fsbcard->set_login(Fsb::$session->data['u_login']);
			$fsbcard->set_nickname(Fsb::$session->data['u_nickname']);
			$fsbcard->set_password($password);
			$fsbcard->set_email(Fsb::$session->data['u_email']);
		}

		// Avatars
		if (Http::request('export_avatar', 'post'))
		{
			if (Fsb::$session->data['u_avatar_method'] == AVATAR_METHOD_UPLOAD)
			{
				$fsbcard->set_avatar(AVATAR_PATH . Fsb::$session->data['u_avatar'], 'content');
			}
			else if (Fsb::$session->data['u_avatar_method'] == AVATAR_METHOD_LINK)
			{
				$fsbcard->set_avatar(Fsb::$session->data['u_avatar'], 'link');
			}
		}

		// Signature
		if (Http::request('export_sig', 'post'))
		{
			$fsbcard->set_sig(htmlspecialchars(Fsb::$session->data['u_signature']));
		}
		
		Http::download('fsbcard.xml', $fsbcard->generate());
		exit;
	}

	/**
	 * Import du profil
	 */
	public function submit_import()
	{
		if (empty($_FILES['upload_fsbcard']['name']))
		{
			Display::message('user_fsbcard_bad_import');
		}

		// Upload et lecture de la FSBcard
		$upload = new Upload('upload_fsbcard');
		$upload->allow_ext(array('xml'));
		$card_path = ROOT . 'upload/' . $upload->store(ROOT . 'upload/');
		$content = file_get_contents($card_path);
		unlink($card_path);

		// Import de la FSBcard
		$update_array = Page_user_fsbcard::import_fsbcard(Fsb::$session->id(), $content);
		Fsb::$db->update('users', $update_array, 'WHERE u_id = ' . Fsb::$session->id());

		Log::user(Fsb::$session->id(), 'import_fsbcard');
		Display::message('user_fsbcard_import_ok', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=fsbcard', 'forum_profil');
	}
	
	/**
	 * Import de la FSBcard
	 *
	 * @param int $u_id ID de l'utilisateur
	 * @param string $content Contenu de la FSBcard
	 * @return array
	 */
	public static function import_fsbcard($u_id, $content)
	{
		$fsbcard = new Fsbcard();
		$fsbcard->load_content($content);
		
		$update_array = array();

		// Affichage de l'Email
		$displayEmail = $fsbcard->get_option('displayEmail');
		if ($displayEmail == 'extern')
		{
			$update_array['u_activate_email'] = 2;
		}
		else if ($displayEmail == 'intern')
		{
			$update_array['u_activate_email'] = 4;
		}
		else
		{
			$update_array['u_activate_email'] = 8;
		}

		// Auto notification
		$notifyPost = $fsbcard->get_option('notifyPost');
		if ($notifyPost == 'auto_email')
		{
			$update_array['u_activate_auto_notification'] = NOTIFICATION_EMAIL | NOTIFICATION_AUTO;
		}
		else if ($notifyPost == 'none_email')
		{
			$update_array['u_activate_auto_notification'] = NOTIFICATION_EMAIL;
		}
		else if ($notifyPost == 'auto')
		{
			$update_array['u_activate_auto_notification'] = NOTIFICATION_AUTO;
		}
		else
		{
			$update_array['u_activate_auto_notification'] = 0;
		}

		// Redirection automatique
		$redirect = $fsbcard->get_option('redirect');
		if ($redirect == 'none')
		{
			$update_array['u_activate_redirection'] = 2;
		}
		else if ($redirect == 'direct')
		{
			$update_array['u_activate_redirection'] = 4;
		}
		else
		{
			$update_array['u_activate_redirection'] = 8;
		}

		// Notification MP
		$update_array['u_activate_mp_notification'] = $fsbcard->get_option('notifyMp');

		// Connexion invisible
		$update_array['u_activate_hidden'] = $fsbcard->get_option('sessionHidden');

		// Affichage de l'avatar dans les sujets
		$update_array['u_activate_avatar'] = $fsbcard->get_option('displayAvatar');

		// Affichage de la signature dans les sujets
		$update_array['u_activate_sig'] = $fsbcard->get_option('displaySig');

		// WYSIWYG
		$update_array['u_activate_wysiwyg'] = $fsbcard->get_option('wysiwyg');

		// Ajax
		$update_array['u_activate_ajax'] = $fsbcard->get_option('ajax');

		// Affichage FSBcode
		if ($displayFsbcode = $fsbcard->get_option('displayFsbcode'))
		{
			$update_array['u_activate_fscode'] = (($displayFsbcode['posts']) ? 2 : 0) | (($displayFsbcode['sigs']) ? 4 : 0);
		}

		// Affichage images
		if ($displayImg = $fsbcard->get_option('displayImg'))
		{
			$update_array['u_activate_img'] = (($displayImg['posts']) ? 2 : 0) | (($displayImg['sigs']) ? 4 : 0);
		}

		// Theme
		if ($template = $fsbcard->get_template())
		{
			if (is_dir(ROOT . 'tpl/' . $template) && (!Fsb::$cfg->get('override_tpl') || Fsb::$cfg->get('default_tpl') == $template))
			{
				$update_array['u_tpl'] = $template;
			}
		}

		// Langue
		if ($language = $fsbcard->get_lang())
		{
			if (is_dir(ROOT . 'lang/' . $template) && (!Fsb::$cfg->get('override_language') || Fsb::$cfg->get('default_language') == $language))
			{
				$update_array['u_language'] = $language;
			}
		}

		// Decalage horaire
		list($update_array['u_utc'], $update_array['u_utc_dst']) = $fsbcard->get_date();

		// Date de naissance
		$update_array['u_birthday'] = implode('/', $fsbcard->get_birthday());

		// Sexe
		$sexe = $fsbcard->get_sexe();
		if ($sexe == 'male')
		{
			$update_array['u_sexe'] = SEXE_MALE;
		}
		else if ($sexe == 'female')
		{
			$update_array['u_sexe'] = SEXE_FEMALE;
		}
		else
		{
			$update_array['u_sexe'] = SEXE_NONE;
		}

		// Signature
		if ($sig = $fsbcard->get_sig())
		{
			$update_array['u_signature'] = $sig;
		}

		// Avatar
		if ($avatar = $fsbcard->get_avatar())
		{
			list($link, $content) = $avatar;

			if ($link)
			{
				$update_array['u_avatar'] = $link;
				$update_array['u_avatar_method'] = AVATAR_METHOD_LINK;
			}

			// Si l'avatar a un contenu
			if (strlen(trim($content)) > 0)
			{
				if (is_writable(ROOT . 'upload/'))
				{
					// Creation de l'avatar dans le repertoire upload/
					$avatar_name = md5($u_id);
					$fd = fopen(ROOT . 'upload/' . $avatar_name, 'w');
					fwrite($fd, $content);
					fclose($fd);

					// Verification du fichier, pour etre sur qu'il s'agit d'une image
					$info = @getimagesize(ROOT . 'upload/' . $avatar_name);
					if ($info)
					{
						list($width, $height, $type) = $info;
						$type_info = array(IMAGETYPE_GIF => 'gif', IMAGETYPE_PNG => 'png', IMAGETYPE_JPEG => 'jpg', IMAGETYPE_BMP => 'bmp');
						if (in_array($type, array_keys($type_info)))
						{
							// Verification de la taille et du poid de l'image
							if ($width <= Fsb::$cfg->get('avatar_width') && $height <= Fsb::$cfg->get('avatar_height') && filesize(ROOT . 'upload/' . $avatar_name) <= Fsb::$cfg->get('avatar_weight'))
							{
								// Deplacement de l'avatar
								$avatar_realname = $avatar_name . '.' . $type_info[$type];
								@copy(ROOT . 'upload/' . $avatar_name, AVATAR_PATH . $avatar_realname);

								$update_array['u_avatar'] = $avatar_realname;
								$update_array['u_avatar_method'] = AVATAR_METHOD_UPLOAD;
							}
						}
						else
						{
							$update_array['u_avatar'] = $link;
							$update_array['u_avatar_method'] = AVATAR_METHOD_LINK;
						}
					}
					unlink(ROOT . 'upload/' . $avatar_name);
				}
			}
		}

		return ($update_array);
	}
}

/* EOF */