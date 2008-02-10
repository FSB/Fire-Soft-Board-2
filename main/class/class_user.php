<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/class_user.php
** | Begin :	13/07/2007
** | Last :		11/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Méthodes permettants de gérer les utilisateurs du forum
*/
class User extends Fsb_model
{
	/*
	** Ajoute un utilisateur sur le forum
	*/
	public static function add($login, $nickname, $password, $email, $data = array())
	{
		unset($data['u_login'], $data['u_password']);

		// Valeurs des champs
		$insert_ary = array(
			'u_auth' =>				USER,
			'u_joined' =>			CURRENT_TIME,
			'u_last_read' =>		CURRENT_TIME,
			'u_last_visit' =>		CURRENT_TIME,
			'u_total_post' =>		0,
			'u_total_topic' =>		0,
			'u_tpl' =>				Fsb::$cfg->get('default_tpl'),
			'u_language' =>			Fsb::$cfg->get('default_lang'),
			'u_birthday' =>			'00/00/0000',
			'u_register_ip' =>		Fsb::$session->ip,
			'u_activated' =>		TRUE,
			'u_utc' =>				Fsb::$cfg->get('default_utc'),
			'u_utc_dst' =>			Fsb::$cfg->get('default_utc_dst'),
			'u_default_group_id' =>	GROUP_SPECIAL_USER,
		);

		// Fusion avec les informations
		$insert_ary = array_merge($insert_ary, $data);
		$insert_ary['u_nickname'] =	$nickname;
		$insert_ary['u_email'] =	$email;

		// Insertion dans la base de donnée
		Fsb::$db->insert('users', $insert_ary);
		$last_id = Fsb::$db->last_id();

		// Insertion du mot de passe
		$autologin_key = sha1($login . $password . $last_id);
		Fsb::$db->insert('users_password', array(
			'u_id' =>			$last_id,
			'u_login' =>		$login,
			'u_password' =>		Password::hash($password, 'sha1', TRUE),
			'u_algorithm' =>	'sha1',
			'u_use_salt' =>		TRUE,
			'u_autologin_key' =>$autologin_key,
		));

		// Création du groupe unique pour le membre (pour ses droits uniques)
		Fsb::$db->insert('groups', array(
			'g_type' =>		GROUP_SINGLE,
		));
		$u_single_group_id = Fsb::$db->last_id();
		Group::add_users($last_id, $u_single_group_id, GROUP_USER, TRUE, TRUE);

		// On met à jour la table user pour signaler le groupe unique sur ce membre
		Fsb::$db->update('users', array(
			'u_single_group_id' =>		$u_single_group_id,
		), 'WHERE u_id = ' . $last_id);

		// On ajoute dans le groupe des membres
		Group::add_users($last_id, GROUP_SPECIAL_USER, GROUP_USER);

		// On met à jour le dernier membre inscrit dans la configuration
		Fsb::$cfg->update('last_user_id', $last_id, FALSE);
		Fsb::$cfg->update('last_user_login', $nickname, FALSE);
		Fsb::$cfg->update('last_user_color', 'class="user"', FALSE);
		Fsb::$cfg->update('total_users', Fsb::$cfg->get('total_users') + 1, FALSE);
		Fsb::$cfg->destroy_cache();
		Fsb::$db->destroy_cache('users_birthday_');

		return ($last_id);
	}

	/*
	** Suppression d'un utilisateur
	** -----
	** $idx ::		ID des membres
	** $type ::		Type de suppression
	**					visitor :	passe tous les messages en invite
	**					topics :	supprime tous les messages du membre ainsi que les sujets dont il est l'auteur
	*/
	public static function delete($idx, $type)
	{
		if (!$idx)
		{
			return ;
		}

		if (!is_array($idx))
		{
			$idx = array($idx);
		}
		$list_idx = implode(', ', $idx);

		// On recupere le groupe unique du membre
		$sql = 'SELECT u_single_group_id
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id IN (' . $list_idx . ')';
		$result = Fsb::$db->query($sql);
		$groups = array();
		while ($row = Fsb::$db->row($result))
		{
			$groups[] = $row['u_single_group_id'];
		}
		Fsb::$db->free($result);
		$list_groups = implode(', ', $groups);

		Fsb::$db->transaction('begin');

		switch ($type)
		{
			case 'visitor' :
				// On passe les messages en invite
				Fsb::$db->update('posts', array(
					'u_id' =>	VISITOR_ID,
				), 'WHERE u_id IN (' . $list_idx . ')');

				Fsb::$db->update('topics', array(
					't_last_u_id' =>	VISITOR_ID,
				), 'WHERE t_last_u_id IN (' . $list_idx . ')');

				Fsb::$db->update('calendar', array(
					'u_id' =>	VISITOR_ID,
				), 'WHERE u_id IN (' . $list_idx . ') AND c_view = 1');
			break;

			case 'topics' :
				// On supprime les messages du membre, ainsi que les sujets dont il est l'auteur
				Moderation::delete_posts('u_id IN (' . $list_idx . ')');
			break;
		}

		// Suppression des tables avec un champ explicite u_id
		$delete_table = array('groups_users', 'users', 'users_password', 'users_personal', 'users_contact', 'topics_read', 'topics_notification', 'posts_abuse', 'warn');
		foreach ($delete_table AS $table)
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . $table . '
					WHERE u_id IN (' . $list_idx . ')
						AND u_id <> ' . VISITOR_ID;
			Fsb::$db->query($sql);
		}

		// Suppression des tables avec des champs un peu moins explicite :p
		$delete_others_table = array(
			'groups' =>			array('g_id' => $list_groups, 'g_type' => GROUP_SINGLE),
			'groups_auth' =>	array('g_id' => $list_groups),
			'logs' =>			array('log_user' => $list_idx),
			'sessions' =>		array('s_id' => $list_idx),
			'calendar' =>		array('u_id' => $list_idx, 'c_view' => 0),
			'poll_result' =>	array('poll_result_u_id' => $list_idx),
		);

		foreach ($delete_others_table AS $table => $where)
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . $table . ' ';
			$first_row = TRUE;
			foreach ($where AS $field => $value)
			{
				$sql .= (($first_row) ? 'WHERE ' : ' AND ') . $field . ' IN (' . $value . ') ';
				$first_row = FALSE;
			}
			Fsb::$db->query($sql);
		}

		// Mise a jour des logs
		Fsb::$db->update('logs', array(
			'u_id' =>		VISITOR_ID,
		), 'WHERE u_id IN (' . $list_idx . ')');

		// Dernier membre inscrit
		if (in_array(Fsb::$cfg->get('last_user_id'), $idx))
		{
			$sql = 'SELECT u_id, u_nickname, u_color
					FROM ' . SQL_PREFIX . 'users
					ORDER BY u_joined DESC
					LIMIT 1';
			$result = Fsb::$db->query($sql);
			$tmp = Fsb::$db->row($result);
			Fsb::$db->free($result);

			Fsb::$cfg->update('last_user_id', $tmp['u_id']);
			Fsb::$cfg->update('last_user_login', $tmp['u_nickname']);
			Fsb::$cfg->update('last_user_color', $tmp['u_color']);
		}
		Fsb::$cfg->update('total_users', Fsb::$cfg->get('total_users') - count($groups));

		Sync::signal(Sync::ABUSE);
		Fsb::$db->transaction('commit');
	}

	/*
	** Permet de renommer un membre
	** -----
	** $u_id ::			ID du membre
	** $new_nickname ::	Nouveau pseudonyme
	** $update_users ::	Flag définissant si la table users doit être mise à jour (simple 
	**					économie de requète dans certains cas)
	*/
	public static function rename($u_id, $new_nickname, $update_users = TRUE)
	{
		// Ne pas renommer les visiteurs
		if ($u_id == VISITOR_ID)
		{
			return ;
		}

		// Mise à jour des tables où le pseudonyme du membre est présent
		if ($update_users)
		{
			Fsb::$db->update('users', array(
				'u_nickname' =>		$new_nickname,
			), 'WHERE u_id = ' . $u_id);
		}

		Fsb::$db->update('posts', array(
			'p_nickname' =>		$new_nickname,
		), 'WHERE u_id = ' . $u_id);

		Fsb::$db->update('topics', array(
			't_last_p_nickname' =>	$new_nickname,
		), 'WHERE t_last_u_id = ' . $u_id);

		Fsb::$db->update('forums', array(
			'f_last_p_nickname' =>	$new_nickname,
		), 'WHERE f_last_u_id = ' . $u_id);

		// Mise à jour du cache de configuration ?
		if (Fsb::$cfg->get('last_user_id') == $u_id)
		{
			Fsb::$cfg->update('last_user_login', $new_nickname);
		}
	}

	/*
	** Retourne TRUE si le login est déjà utilisé dans la base de donnée
	** -----
	** $login ::		Login de connexion
	*/
	public static function login_exists($login)
	{
		$sql = 'SELECT u_login
				FROM ' . SQL_PREFIX . 'users_password
				WHERE LOWER(u_login) = \'' . Fsb::$db->escape(strtolower($login)) . '\'';
		$return = Fsb::$db->get($sql, 'u_login');

		return ($return);
	}

	/*
	** Retourne TRUE si le pseudonyme est déjà utilisé dans la base de donnée
	** -----
	** $nickname ::		Pseudonyme
	*/
	public static function nickname_exists($nickname)
	{
		$sql = 'SELECT u_nickname
				FROM ' . SQL_PREFIX . 'users
				WHERE LOWER(u_nickname) = \'' . Fsb::$db->escape(String::strtolower($nickname)) . '\'';
		$return = Fsb::$db->get($sql, 'u_nickname');

		return ($return);
	}

	/*
	** Retourne TRUE si les caractères du pseudonyme sont autorisés.
	** ATTENTION : Si le pseudonyme n'est pas autorisé, la fonction renvoie le niveau de caractère
	** non autorisé (middle ou high).
	** -----
	** $nickname ::		Pseudonyme
	*/
	public static function nickname_valid($nickname)
	{
		$check = array(
			'low' =>	'.',
			'middle' =>	'[a-zA-Z0-9_\- ]',
			'high' =>	'[a-zA-Z ]',
		);

		if (preg_match('#^' . $check[Fsb::$cfg->get('nickname_chars')] . '+$#', $nickname))
		{
			return (TRUE);
		}
		return (Fsb::$cfg->get('nickname_chars'));
	}

	/*
	** Retourne TRUE si l'Email est déjà utilisé dans la base de donnée
	** -----
	** $email ::		Adresse Email
	*/
	public static function email_exists($email)
	{
		$sql = 'SELECT u_email
				FROM ' . SQL_PREFIX . 'users
				WHERE LOWER(u_email) = \'' . Fsb::$db->escape(strtolower($email)) . '\'';
		$return = Fsb::$db->get($sql, 'u_email');

		return ($return);
	}

	/*
	** Retourne TRUE si l'Email passé match l'expression régulière, et si le dommaine
	** de l'Email existe.
	** Inspiré du commentaire http://fr.php.net/manual/fr/function.checkdnsrr.php#74809
	** -----
	** $email ::	Adresse Email
	*/
	public static function email_valid($email, $check_server = TRUE)
	{
		if (IS_LOCALHOST && OS_SERVER == 'windows')
		{
			$check_server = FALSE;
		}

		if (preg_match('/^\w[-.\w]*@(\w[-._\w]*\.[a-zA-Z]{2,}.*)$/', $email, $match))
		{
			// Les fonctions de vérification de DNS ne tournent pas sous Windows
			if (!$check_server || !Fsb::$cfg->get('check_email_dns'))
			{
				return (TRUE);
			}

			$check = FALSE;
			if (function_exists('checkdnsrr'))
			{
				$check = checkdnsrr($match[1] . '.', 'MX');
				if (!$check)
				{
					$check = checkdnsrr($match[1] . '.', 'A');
				}
			}
			else if (function_exists('exec'))
			{
				$result = array();
				@exec('nslookup -type=MX ' . $match[1], $result);
				foreach ($result as $line)
				{
					if (substr($line, 0, strlen($match[1])) == $match[1])
					{
						$check = TRUE;
						break;
					}
				}
			}

			// En cas d'échec on vérifie l'existance du serveur avec fsockopen()
			if (!$check)
			{
				$errno = 0;
				$errstr = '';
				$check = @fsockopen($match[1], 25, $errno, $errstr, 5);
			}

			return ($check);
		}
		return (FALSE);
	}

	/*
	** Retourne le rang d'un membre
	** -----
	** $total_post ::	Nombre de messages du membre
	** $rank_id ::		ID du rang spécial du membre s'il en a un
	*/
	public static function get_rank($total_post, $rank_id)
	{
		static $ranks = NULL;

		// On récupère les rangs par ordre décroissant (pour pouvoir récupérer le palier du membre)
		// et on les gardes de façon statique pour éviter la requète aux prochains appels.
		if (!$ranks)
		{
			$sql = 'SELECT *
					FROM ' . SQL_PREFIX . 'ranks
					ORDER BY rank_quota DESC';
			$result = Fsb::$db->query($sql, 'ranks_');
			$ranks = Fsb::$db->rows($result, 'assoc', 'rank_id');
			Fsb::$db->free($result);
		}

		if ($rank_id && isset($ranks[$rank_id]))
		{
			// Rang spécial
			$rank = $ranks[$rank_id];
		}
		else
		{
			// Rang par quota
			$rank = NULL;
			foreach ($ranks AS $value)
			{
				if ($total_post >= $value['rank_quota'] && !$value['rank_special'])
				{
					$rank = $value;
					break;
				}
			}
		}

		// Si aucun rang n'a été trouvé ...
		if (!$rank)
		{
			return (NULL);
		}

		// Sinon on retourne directement les informations du rang (nom, image et style)
		$return['name'] =	$rank['rank_name'];
		$return['img'] =	($rank['rank_img']) ? RANK_PATH . $rank['rank_img'] : '';
		$return['style'] =	$rank['rank_color'];

		return ($return);
	}

	/*
	** Calcul l'age en fonction de la date de naissance
	** Repris d'un commentaire sur cette page : http://fr3.php.net/manual/fr/function.date.php
	** -----
	** $birthday ::		Jour de la naissance du membre, sous le format day/month/year (xx/yy/zzzz)
	** $check_mod ::	Mettre à TRUE si vous voullez que la fonction soit indépendante de la configuration
	**					des fonctions du forum
	*/
	public static function get_age($birthday, $check_mod = TRUE)
	{
		if (!$birthday)
		{
			return (NULL);
		}

		if (!$check_mod || Fsb::$mods->is_active('user_birthday'))
		{
			list($split_day, $split_month, $split_year) = explode('/', $birthday);
			if (intval($split_day) == 0 || intval($split_month) == 0 || intval($split_year) == 0)
			{
				$age = NULL;
			}
			else
			{
				$tmonth = date('n');
				$tday = date('j');
				$tyear = date('Y');
				$age = $tyear - $split_year;
				if ($tmonth <= $split_month)
				{
					if ($split_month == $tmonth)
					{
						if ($split_day > $tday)
						{
							$age--;
						}
					}
					else
					{
						$age--;
					}
				}
			}
		}
		else
		{
			$age = NULL;
		}

		return ($age);
	}

	/*
	** Récupère le sexe du membre sous forme d'image
	** -----
	** $sexe ::			Sexe du membre
	** $check_mod ::	Mettre à TRUE si vous voullez que la fonction soit indépendante de la configuration
	**					des fonctions du forum
	*/
	public static function get_sexe($sexe, $check_mod = TRUE)
	{
		$return = NULL;
		if (!$check_mod || Fsb::$mods->is_active('user_sexe'))
		{
			switch ($sexe)
			{
				case SEXE_MALE :
					$return = '<img src="' . ROOT . 'images/man.gif" alt="' . Fsb::$session->lang('sexe_male') . '" title="' . Fsb::$session->lang('sexe_male') . '" />';
				break;

				case SEXE_FEMALE :
					$return = '<img src="' . ROOT . 'images/woman.gif" alt="' . Fsb::$session->lang('sexe_female') . '" title="' . Fsb::$session->lang('sexe_female') . '" />';
				break;
			}
		}

		return ($return);
	}

	/*
	** Retourne un tableau contenant en premier indice si le membre peut utiliser un avatar, et ensuite
	** l'URL de l'avatar.
	** -----
	** $avatar_name ::		Nom de l'avatar
	** $avatar_method ::	Méthode d'affichage de l'avatar
	** $can_use ::			Si le membre peut utiliser un avatar
	*/
	public static function get_avatar($avatar_name, $avatar_method, $can_use)
	{
		if (Fsb::$cfg->get('avatar_can_use') && $can_use && $avatar_name && $avatar_method)
		{
			switch ($avatar_method)
			{
				case AVATAR_METHOD_UPLOAD :
					$u_avatar = AVATAR_PATH . $avatar_name;
				break;

				case AVATAR_METHOD_GALLERY :
					$u_avatar = AVATAR_PATH . 'gallery/' . $avatar_name;
				break;
				
				default :
					$u_avatar = $avatar_name;
				break;
			}
		}
		else
		{
			$u_avatar = (Fsb::$mods->is_active('no_avatar')) ? AVATAR_PATH . 'noavatar.gif' : '';
		}

		return ($u_avatar);
	}

	/*
	** Confirmation par administrateur
	** -----
	** $user_id ::			ID du membre
	** $user_nickname ::	Pseudonyme du membre
	** $user_email ::		Email du membre
	** $user_ip ::			IP d'inscription du membre
	*/
	public static function confirm_administrator($user_id, $user_nickname, $user_email, $user_ip)
	{
		// Envoie de l'Email à l'utilisateur
		$mail = new Notify_mail();
		$mail->AddAddress($user_email);
		$mail->Subject = sprintf(Fsb::$session->lang('subject_register'), Fsb::$cfg->get('forum_name'));
		$mail->set_file(ROOT . 'lang/' . Fsb::$cfg->get('default_lang') . '/mail/register_admin.txt');
		$mail->set_vars(array(
			'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
			'U_FORUM' =>		Fsb::$cfg->get('fsb_path'),
		));
		$mail->Send();
		$mail->SmtpClose();

		// Niveau d'autorisation des personnes pouvant confirmer les inscriptions
		$sql = 'SELECT auth_level
				FROM ' . SQL_PREFIX . 'auths
				WHERE auth_name = \'confirm_account\'';
		$result = Fsb::$db->query($sql, 'auths_');
		if (!$level = Fsb::$db->row($result))
		{
			$level = array('auth_level' => FONDATOR);
		}
		Fsb::$db->free($result);

		// On récupère les informations pour notifier les administrateurs
		$sql = 'SELECT u_email, u_language, u_activate_auto_notification
				FROM ' . SQL_PREFIX . 'users
				WHERE u_auth > ' . $level['auth_level'];
		$result = Fsb::$db->query($sql);
		$notify_list = array();
		while ($row = Fsb::$db->row($result))
		{
			if (!isset($notify_list[$row['u_language']]))
			{
				$notify_list[$row['u_language']] = array();
			}
			$notify_list[$row['u_language']][] = $row[$row['u_email']];
		}
		Fsb::$db->free($result);

		foreach ($notify_list AS $mail_lang => $mail_list)
		{
			$notify = new Notify(NOTIFY_MAIL);
			foreach ($mail_list AS $bcc)
			{
				$notify->add_bcc($bcc);
			}

			$notify->set_subject(Fsb::$session->lang('subject_confirm_account'));
			$notify->set_template(ROOT . 'lang/' . $mail_lang . '/mail/confirm_account.txt');
			$notify->set_vars(array(
				'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
				'FORUM_URL' =>		Fsb::$cfg->get('fsb_path'),
				'CONFIRM_URL' =>	Fsb::$cfg->get('fsb_path') . '/index.php?p=login&confirm_account=' . $user_id,
				'NICKNAME' =>		htmlspecialchars($user_nickname),
				'EMAIL' =>			$user_email,
				'IP' =>				$user_ip,
			));
			$notify->put();
			unset($notify);
		}
	}

	/*
	** Confirme et active le compte
	** -----
	** $user_id ::		ID du membre
	*/
	public static function confirm_account($user_id)
	{
		$sql = 'SELECT u_nickname, u_email, u_language
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id = ' . $user_id . '
					AND u_activated = 0
					AND u_confirm_hash = \'.\'
					AND u_id <> ' . VISITOR_ID;
		$result = Fsb::$db->query($sql);
		if ($data = Fsb::$db->row($result))
		{
			Fsb::$db->update('users', array(
				'u_activated' =>	TRUE,
				'u_confirm_hash' =>	'',
			), 'WHERE u_id = ' . $user_id . ' AND u_confirm_hash = \'.\' AND u_activated = 0');

			// Envoie de l'Email à l'utilisateur
			$mail = new Notify_mail();
			$mail->AddAddress($data['u_email']);
			$mail->Subject = Fsb::$session->lang('subject_confirm_account');
			$mail->set_file(ROOT . 'lang/' . Fsb::$cfg->get('default_lang') . '/mail/confirm_message.txt');
			$mail->set_vars(array(
				'NICKNAME' =>		htmlspecialchars($data['u_nickname']),
				'FORUM_NAME' =>		Fsb::$cfg->get('forum_name'),
				'U_FORUM' =>		Fsb::$cfg->get('fsb_path'),
			));
			$mail->Send();
			$mail->SmtpClose();

			return (TRUE);
		}

		return (FALSE);
	}
}
/* EOF */