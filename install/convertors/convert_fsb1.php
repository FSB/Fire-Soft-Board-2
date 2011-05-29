<?php
/*
** +---------------------------------------------------+
** | Name :		~/install/convertors/convert_fsb1.php
** | Begin :	28/12/2007
** | Last :		07/01/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Convertisseur FSB1 -> FSB2
**
** Mods supportes :
**	- Messagerie privee
**	- Groupes et autorisations des groupes
**	- Sondages
**	- Login / pseudo
**	- Rangs
*/

class Convert_fsb1 extends Convert
{
	// Nom du forum
	public static $static_forum_type = 'FSB (Fire Soft Board) 1.0.X';
	public $forum_type = 'FSB (Fire Soft Board) 1.0.X';
	public static function forum_type(){return (self::$static_forum_type);}

	// UTF-8 active sur le forum ?
	protected $use_utf8 = false;

	// Configuration additionelle
	protected $additional_conf = array(
		'forum_path' => '<input type="text" name="forum_path" value="{VALUE}" size="35" />',
	);

	// Les mods installes sur FSB1
	private $fsb1_mods = array();

	/*
	** Methode permettant de recuperer sur chaque page des informations sur le forum
	*/
	protected function forum_information()
	{
		// Recherche des MOS FSB1 installes
		$sql = 'SHOW TABLES LIKE \'' . $this->config('sql_prefix') . '%\'';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result, 'row'))
		{
			$table = substr($row[0], strlen($this->config('sql_prefix')));
			switch ($table)
			{
				case 'groupes' :
					$this->fsb1_mods['groups'] = true;
				break;

				case 'mps' :
					$this->fsb1_mods['mps'] = true;
				break;

				case 'sondage' :
					$this->fsb1_mods['polls'] = true;
				break;
			}
		}
		Fsb::$db->free($result);

		if (file_exists(ROOT . $this->config('forum_path') . 'cache/fichier_rang.php'))
		{
			$this->fsb1_mods['ranks'] = true;
		}
	}

	/*
	** Retourne la liste des conversions implementees
	*/
	protected function _get_implement()
	{
		$implement = array(
			'config',
			'users',
			'forums',
			'auths',
			'topics',
			'posts',
			'bans',
			'copy',
		);

		if (isset($this->fsb1_mods['groups']))
		{
			$implement[] = 'groups';
		}

		if (isset($this->fsb1_mods['mps']))
		{
			$implement[] = 'mp';
		}

		if (isset($this->fsb1_mods['polls']))
		{
			$implement[] = 'polls';
		}

		if (isset($this->fsb1_mods['ranks']))
		{
			$implement[] = 'ranks';
		}

		return ($implement);
	}

	/*
	** Retourne la configuration du forum sous la forme array('key' => 'value')
	*/
	protected function convert_config()
	{
		$return = array();

		// Configuration de FSB1
		$config = $this->fsb1_load_cache('config');
		$cfg = array();
		foreach ($config AS $line)
		{
			$cfg[$line['name']] = $line['value'];
		}

		// Calcul du dernier membre inscrit
		$sql = 'SELECT membre_id, membre_login
				FROM ' . $this->config('sql_prefix') . 'membres
				ORDER BY date_enregistrement DESC';
		$last_user = Fsb::$db->request($sql);

		$return = array(
			'register_time' =>			$cfg['forum_creation'],
			'forum_mail' =>				$cfg['forum_mail'],
			'forum_name' =>				$cfg['nom_site'],
			'forum_description' =>		$cfg['description_site'],
			'override_lang' =>			$cfg['annuler_langue'],
			'override_tpl' =>			$cfg['annuler_theme'],
			'user_edit_nickname' =>		$cfg['editer_login'],
			'topic_per_page' =>			$cfg['sujet_par_page'],
			'post_per_page' =>			$cfg['messages_par_page'],
			'avatar_can_use' =>			$cfg['aut_avatar'],
			'avatar_can_upload' =>		$cfg['upload_avatar'],
			'avatar_weight' =>			$cfg['taille_avatar'],
			'avatar_height' =>			$cfg['height_avatar'],
			'avatar_width' =>			$cfg['width_avatar'],
			'last_user_id' =>			$this->fsb1_user_id($last_user['membre_id']),
			'last_user_login' =>		$last_user['membre_login'],
		);

		return ($return);
	}

	/*
	** Retourne le nombre de membres qu'on va convertir
	*/
	protected function count_convert_users()
	{
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . $this->config('sql_prefix') . 'membres';
		return (Fsb::$db->get($sql, 'total'));
	}

	/*
	** Retourne un tableau contenant a chaque ligne les informations sur un membre
	*/
	protected function convert_users($offset, $step, $state)
	{
		$return = array();
		$fondator_exists = false;

		$sql = 'SELECT m.*, COUNT(s.sujet_id) AS total_sujet
				FROM ' . $this->config('sql_prefix') . 'membres m
				LEFT JOIN ' . $this->config('sql_prefix') . 'sujets s
					ON s.membre_id = m.membre_id
				WHERE m.membre_id <> 0
				GROUP BY m.membre_id
				ORDER BY m.membre_id
				LIMIT ' . $offset . ',' . $step;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			// Informations basiques sur le membre
			$user = array();
			$user['u_id'] =				$this->fsb1_user_id($row['membre_id']);
			$user['u_nickname'] =		(isset($row['membre_pseudo'])) ? $row['membre_pseudo'] : $row['membre_login'];
			$user['u_email'] =			$row['membre_email'];
			$user['u_auth'] =			$row['membre_aut'] + 1;
			$user['u_joined'] =			$row['date_enregistrement'];
			$user['u_total_post'] =		$row['membre_nb_message'];
			$user['u_total_topic'] =	$row['total_sujet'];
			$user['u_last_visit'] =		$row['derniere_visite'];

			// Le membre avec l'ID 1 est fondateur s'il est admin. Sinon on prendra le premier admin.
			if ($row['membre_aut'] >= 3 && ($row['membre_id'] == 1 || !$fondator_exists))
			{
				$fondator_exists = true;
				$row['u_auth'] = FONDATOR;
			}

			// Avatar du membre
			$user['u_avatar'] =			$row['membre_avatar'];
			$user['u_avatar_method'] =	(preg_match('#^http://|https://|ftp://#i', $user['u_avatar'])) ? AVATAR_METHOD_LINK : AVATAR_METHOD_UPLOAD;
			$user['u_can_use_avatar'] =	$row['aut_avatar'];
			if (preg_match('#^(\./)*images/avatars/(.*?)$#i', $user['u_avatar'], $match))
			{
				$user['u_avatar'] = $match[2];
			}

			// Signature du membre
			$user['u_signature'] =		String::unhtmlspecialchars($this->fsb1_parse_fsbcode($row['membre_signature']));
			$user['u_can_use_sig'] =	$row['aut_signature'];

			// Les informations sur le mot de passe + login
			$user['password'] = array(
				'u_login' =>		$row['membre_login'],
				'u_password' =>		$row['membre_mdp'],
				'u_algorithm' =>	'md5',
				'u_use_salt' =>		0,
			);

			$return[] = $user;
		}
		Fsb::$db->free($result);

		return ($return);
	}

	/*
	** Retourne les informations sur les groupes du forum
	*/
	protected function convert_groups()
	{
		$return = array('groups' => array(), 'groups_users' => array());

		// ID max des membres
		$sql = 'SELECT MAX(membre_id) AS total
				FROM ' . $this->config('sql_prefix') . 'membres';
		$max_user_id = Fsb::$db->get($sql, 'total') + 20;

		// Creation des groupes
		$sql = 'SELECT *
				FROM ' . $this->config('sql_prefix') . 'groupes
				ORDER BY g_id';
		$result = Fsb::$db->query($sql);
		$groups_id = array();
		while ($row = Fsb::$db->row($result))
		{
			$return['groups'][] = array(
				'g_id' =>		$max_user_id,
				'g_name' =>		$row['g_nom'],
				'g_desc' =>		$row['g_description'],
				'g_type' =>		GROUP_NORMAL,
				'g_hidden' =>	!$row['g_visible'],
				'g_color' =>	'',
				'g_open' =>		$row['g_ouvert'],
				'g_rank' =>		0,
				'g_online' =>	$row['g_visible'],
			);

			$groups_id[$row['g_id']] = $max_user_id++;
		}
		Fsb::$db->free($result);

		// Generations des membres des groupes
		$sql = 'SELECT *
				FROM ' . $this->config('sql_prefix') . 'groupes_membres
				ORDER BY g_id';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$return['groups_users'][] = array(
				'g_id' =>		$groups_id[$row['g_id']],
				'u_id' =>		$this->fsb1_user_id($row['u_id']),
				'gu_status' =>	($row['gm_status'] == 1) ? GROUP_MODO : GROUP_USER,
			);
		}
		Fsb::$db->free($result);

		return ($return);
	}

	/*
	** Fonction devant retourner un arbre de forums (objet Convert_tree_forums)
	*/
	protected function convert_forums()
	{
		// Chargement des forums
		$forums = $this->fsb1_load_cache('forum');
		$cat = $this->fsb1_load_cache('categorie');

		// On recupere l'ID max des forums, afin de pouvoir creer des ID de categories correctes puisque
		// dans FSB1 categories et forums sont separes
		$max_forum_id = 0;
		foreach ($forums AS $f)
		{
			$max_forum_id = max($max_forum_id, $f['forum_id']);
		}

		// A partir de cette ID on cree les vrais ID des categories
		$tree = new Convert_tree_forums();
		$cat_id = array();
		foreach ($cat AS $c)
		{
			$cat_id[$c['cat_id']] = ++$max_forum_id;

			// Ajout des categories dans l'arbre
			$tree->add_item($cat_id[$c['cat_id']], 0, array(
				'f_id' =>		$cat_id[$c['cat_id']],
				'f_name' =>		$c['cat_nom'],
				'f_cat_id' =>	$cat_id[$c['cat_id']],
				'f_level' =>	0,
				'f_type' =>		FORUM_TYPE_NORMAL,
				'f_parent' =>	0,
			));
		}

		// Creation de l'arbre des forums
		$index_cat_id = array();
		$store = $forums;

		// On parcourt plusieurs fois cette zone afin de pouvoir ajouter les forums qui eventuellement n'auraient pas encore ete ajoutes
		while (count($store))
		{
			$last_count = count($store);
			foreach ($store AS $key => $f)
			{
				if (isset($f['forum_lien_id']) && $f['forum_lien_id'])
				{
					$parent = $f['forum_lien_id'];
				}
				else if (isset($cat_id[$f['cat_id']]))
				{
					$parent = $cat_id[$f['cat_id']];
				}
				else
				{
					unset($store[$key]);
					continue;
				}

				if ($f['cat_id'])
				{
					if (!isset($cat_id[$f['cat_id']]))
					{
						continue;
					}
					$index_cat_id[$f['forum_id']] = $cat_id[$f['cat_id']];
				}
				else
				{
					if (!isset($index_cat_id[$parent]))
					{
						continue;
					}
					$index_cat_id[$f['forum_id']] = $index_cat_id[$parent];
				}

				if (isset($f['type']) && $f['type'] == 'lien')
				{
					$tree->add_item($f['forum_id'], $parent, array(
						'f_id' =>				$f['forum_id'],
						'f_name' =>				$f['forum_nom'],
						'f_text' =>				(isset($f['forum_description'])) ?$f['forum_description'] : '   ',
						'f_total_post' =>		0,
						'f_total_topic' =>		0,
						'f_last_p_id' =>		(isset($f['dernier_message_id'])) ? $f['dernier_message_id'] : '',
						'f_global_announce' =>	true,
						'f_cat_id' =>			$index_cat_id[$f['forum_id']],
						'f_map_default' =>		'classic',
						'f_map_first_post' =>	true,
						'f_type' =>				FORUM_TYPE_DIRECT_URL,
						'f_parent' =>			$parent,
						'f_location' =>			$f['lien'],
						'f_location_view' =>	$f['clics'],
					));
				}
				else
				{
					$tree->add_item($f['forum_id'], $parent, array(
						'f_id' =>				$f['forum_id'],
						'f_name' =>				$f['forum_nom'],
						'f_text' =>				(isset($f['forum_description'])) ?$f['forum_description'] : '   ',
						'f_total_post' =>		$f['forum_nb_message'],
						'f_total_topic' =>		$f['forum_nb_sujet'],
						'f_last_p_id' =>		(isset($f['dernier_message_id'])) ? $f['dernier_message_id'] : '',
						'f_global_announce' =>	true,
						'f_cat_id' =>			$index_cat_id[$f['forum_id']],
						'f_map_default' =>		'classic',
						'f_map_first_post' =>	true,
						'f_type' =>				FORUM_TYPE_NORMAL,
						'f_parent' =>			$parent,
					));
				}

				unset($store[$key]);
			}

			// Securite pour eviter les boucles infinies
			if ($last_count == count($store))
			{
				break;
			}
		}

		return ($tree);
	}

	/*
	** Doit retourner un tableau multidimensionel contenant :
	** - Au premier niveau en clef, l'ID d'un forum
	** - Au second niveau en clef, l'ID d'un groupe
	** - Au troisieme niveau, les clefs des droits avec true / false
	** En clair, ce tableau permet de determiner les droits pour chaque groupe pour chaque forum.
	** Une abscence de forum ou de groupe signifie aucun droit.
	*/
	protected function convert_auths()
	{
		$return = array('data' => array(), 'sql' => array());

		// Equivalent des droits FSB1 -> FSB2
		$index_auths = array(
			'ga_view' =>					'droit_voir',
			'ga_view_topics' =>				'droit_lire',
			'ga_read' =>					'droit_lire',
			'ga_create_post' =>				'droit_poster',
			'ga_answer_post' =>				'droit_repondre',
			'ga_create_announce' =>			'droit_annonce',
			'ga_answer_announce' =>			'droit_annonce',
			'ga_edit' =>					'droit_editer',
			'ga_delete' =>					'droit_supprimer',
			'ga_create_global_announce' =>	'droit_annonce',
			'ga_answer_global_announce' =>	'droit_annonce',
		);

		// Gestion des droits simples (droits d'un groupe special sur un forum)
		$forums = $this->fsb1_load_cache('forum');
		foreach ($forums AS $f)
		{
			$return['data'][$f['forum_id']] = array();
			foreach (array(GROUP_SPECIAL_VISITOR => VISITOR, GROUP_SPECIAL_USER => USER, GROUP_SPECIAL_MODO => MODO, GROUP_SPECIAL_MODOSUP => MODOSUP, GROUP_SPECIAL_ADMIN => ADMIN) AS $group => $level)
			{
				$return['data'][$f['forum_id']][$group] = array();
				$return['data'][$f['forum_id']][$group]['ga_moderator'] = 0;
				foreach ($index_auths AS $fsb2_auth => $fsb1_auth)
				{
					$return['data'][$f['forum_id']][$group][$fsb2_auth] = $this->fsb1_get_auth($f, $level, $fsb1_auth);
				}
			}
		}

		// Gestion des drois avec le MOD groupes
		if (isset($this->fsb1_mods['groups']))
		{
			$groups_id = $this->fsb1_get_groups_id();

			// Droits des groupes
			$sql = 'SELECT *
					FROM ' . $this->config('sql_prefix') . 'groupes_droits';
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				if (!isset($return['data'][$row['f_id']]))
				{
					$return['data'][$row['f_id']] = array();
				}

				if (!isset($return['data'][$row['f_id']][$groups_id[$row['g_id']]]))
				{
					$return['data'][$row['f_id']][$groups_id[$row['g_id']]] = array();
				}

				$return['data'][$row['f_id']][$groups_id[$row['g_id']]]['ga_moderator'] = $row['droit_modo'];
				foreach ($index_auths AS $fsb2_auth => $fsb1_auth)
				{
					$return['data'][$row['f_id']][$groups_id[$row['g_id']]][$fsb2_auth] = intval($row[$fsb1_auth]);
				}
			}
			Fsb::$db->free($result);

			// Membres passant moderateurs

			// On recupere les membres passants au stade de moderateurs
			$sql = 'SELECT u.u_id
					FROM ' . $this->config('sql_prefix') . 'groupes_droits gd
					LEFT JOIN ' . $this->config('sql_prefix') . 'groupes_membres u
						ON gd.g_id = u.g_id
					WHERE gd.droit_modo = 1';
			$result = Fsb::$db->query($sql);
			$idx = array();
			while ($row = Fsb::$db->row($result))
			{
				$idx[] = $this->fsb1_user_id($row['u_id']);
			}
			Fsb::$db->free($result);
			$idx = array_flip(array_flip($idx));

			if ($idx)
			{
				$return['sql'][] = 'UPDATE ' . SQL_PREFIX . 'users SET u_auth = ' . MODO . ' WHERE u_id IN (' . implode(', ', $idx) . ') AND u_auth < ' . MODO;
				foreach ($idx AS $item)
				{
					$return['sql'][] = 'REPLACE INTO ' . SQL_PREFIX . 'groups_users (g_id, u_id, gu_status) VALUES (' . GROUP_SPECIAL_MODO . ', ' . $item . ', ' . GROUP_USER . ')';
				}
			}
		}

		// Liste des moderateurs en dur
		$moderation = $this->fsb1_load_cache('moderation');
		$idx = array();
		foreach ($moderation AS $modo)
		{
			if ($modo)
			{
				$data = array();
				$data['ga_moderator'] = 1;
				foreach (array_keys($index_auths) AS $fsb2_auth)
				{
					$data[$fsb2_auth] = 1;
				}
				$forum_id = $modo['forum_id'];
				$group_id = $modo['membre_id'] + 11;
				$idx[] = $this->fsb1_user_id($modo['membre_id']);

				if (!isset($return['data'][$forum_id]))
				{
					$return['data'][$forum_id] = array();
				}

				if (!isset($return['data'][$forum_id][$group_id]))
				{
					$return['data'][$forum_id][$group_id] = array();
				}

				$return['data'][$forum_id][$group_id] = $data;
			}
		}

		if ($idx)
		{
			$idx = array_flip(array_flip($idx));
			$return['sql'][] = 'UPDATE ' . SQL_PREFIX . 'users SET u_auth = ' . MODO . ' WHERE u_id IN (' . implode(', ', $idx) . ') AND u_auth < ' . MODO;
			foreach ($idx AS $item)
			{
				$return['sql'][] = 'REPLACE INTO ' . SQL_PREFIX . 'groups_users (g_id, u_id, gu_status) VALUES (' . GROUP_SPECIAL_MODO . ', ' . $item . ', ' . GROUP_USER . ')';
			}
		}

		// Mise a jour des couleurs
		$return['sql'][] = 'UPDATE ' . SQL_PREFIX . 'users SET u_color = \'class="user"\' WHERE u_auth = ' . USER;
		$return['sql'][] = 'UPDATE ' . SQL_PREFIX . 'users SET u_color = \'class="modo"\' WHERE u_auth = ' . MODO;
		$return['sql'][] = 'UPDATE ' . SQL_PREFIX . 'users SET u_color = \'class="modosup"\' WHERE u_auth = ' . MODOSUP;
		$return['sql'][] = 'UPDATE ' . SQL_PREFIX . 'users SET u_color = \'class="admin"\' WHERE u_auth >= ' . ADMIN;

		return ($return);
	}

	/*
	** Retourne le nombre de sujets qu'on va convertir
	*/
	protected function count_convert_topics()
	{
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . $this->config('sql_prefix') . 'sujets';
		return (Fsb::$db->get($sql, 'total'));
	}

	/*
	** Retourne un tableau contenant a chaque ligne les informations sur un sujet
	*/
	protected function convert_topics($offset, $step, $state)
	{
		$return = array();

		$sql = 'SELECT s.*, m.membre_id AS u_id, m.pseudo_posteur AS p_nickname, m.message_temps
				FROM ' . $this->config('sql_prefix') . 'sujets s
				LEFT JOIN ' . $this->config('sql_prefix') . 'messages m
					ON s.premier_message_id = m.message_id
				ORDER BY s.sujet_id
				LIMIT ' . $offset . ',' . $step;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$data = array(
				't_id' =>				$row['sujet_id'],
				'f_id' =>				$row['forum_id'],
				'u_id' =>				$this->fsb1_user_id($row['u_id']),
				't_title' =>			String::unhtmlspecialchars($row['sujet_nom']),
				't_time' =>				$row['message_temps'],
				't_total_view' =>		$row['nb_vu'],
				't_total_post' =>		$row['nb_reponse'] + 1,
				't_last_p_id' =>		$row['dernier_message_id'],
				't_last_p_time' =>		$row['dernier_message_temps'],
				't_last_u_id' =>		$this->fsb1_user_id($row['u_id']),
				't_last_p_nickname' =>	$row['p_nickname'],
				't_first_p_id' =>		$row['premier_message_id'],
				't_type' =>				($row['sujet_type'] == 0) ? 1 : 2,
				't_status' =>			($row['sujet_status']) ? UNLOCK : LOCK,
				't_map' =>				'classic',
				't_map_first_post' =>	true,
			);

			$return[] = $data;
		}
		Fsb::$db->free($result);

		return ($return);
	}

	/*
	** Retourne le nombre de messages qu'on va convertir
	*/
	protected function count_convert_posts()
	{
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . $this->config('sql_prefix') . 'messages';
		return (Fsb::$db->get($sql, 'total'));
	}

	/*
	** Retourne un tableau contenant a chaque ligne les informations sur un message
	*/
	protected function convert_posts($offset, $step, $state)
	{
		$return = array();

		$idx = array();
		$sql = 'SELECT message_id
				FROM ' . $this->config('sql_prefix') . 'messages
				ORDER BY message_id
				LIMIT ' . $offset . ',' . $step;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$idx[] = $row['message_id'];
		}
		Fsb::$db->free($result);
		
		if ($idx)
		{
			$sql = 'SELECT *
					FROM ' . $this->config('sql_prefix') . 'messages
					WHERE message_id IN (' . implode(',', $idx) . ')';
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				$data = array(
					'p_id' =>		$row['message_id'],
					't_id' =>		$row['sujet_id'],
					'f_id' =>		$row['forum_id'],
					'u_id' =>		$this->fsb1_user_id($row['membre_id']),
					'p_nickname' => $row['pseudo_posteur'],
					'p_text' =>		$this->fsb1_parse_message($row['message_texte']),
					'p_time' =>		$row['message_temps'],
					'u_ip' =>		long2ip($row['message_ip']),
					'p_map' =>		'classic',
					'p_approve' =>	IS_APPROVED,
				);
	
				$return[] = $data;
			}
			Fsb::$db->free($result);
		}

		return ($return);
	}

	/*
	** Retourne le nombre de messages prives qu'on va convertir
	*/
	protected function count_convert_mp()
	{
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . $this->config('sql_prefix') . 'mps';
		return (Fsb::$db->get($sql, 'total'));
	}

	/*
	** Retourne un tableau contenant a chaque ligne les informations sur un message prive
	*/
	protected function convert_mp($offset, $step, $state)
	{
		$return = array();

		$sql = 'SELECT *
				FROM ' . $this->config('sql_prefix') . 'mps
				ORDER BY mp_id
				LIMIT ' . $offset . ',' . $step;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$data = array(
				'mp_id' =>			$row['mp_id'],
				'mp_from' =>		$this->fsb1_user_id($row['mp_posteur_id']),
				'mp_to' =>			$this->fsb1_user_id($row['mp_recepteur_id']),
				'mp_title' =>		String::unhtmlspecialchars($row['mp_sujet']),
				'mp_content' =>		$this->fsb1_parse_message($row['mp_texte']),
				'mp_time' =>		$row['mp_temps'],
				'mp_read' =>		($row['mp_status']) ? MP_READ : MP_UNREAD,
				'mp_type' =>		($row['mp_type'] == 0) ? MP_INBOX : MP_OUTBOX,
				'mp_parent' =>		0,
				'is_auto_answer' =>	0,
			);

			$return[] = $data;
		}
		Fsb::$db->free($result);

		return ($return);
	}

	/*
	** Retourne un tableau contenant les sondages, avec un sondage par ligne. Chaque ligne de sondage
	** doit contenir un sous tableau "options" avec le tableau d'options, ainsi qu'un tableau "voters"
	** contenant les ID des membres qui ont vote.
	*/
	protected function convert_polls()
	{
		$return = array('data' => array(), 'sql' => array());

		// Recuperation des sondages
		$sql = 'SELECT t.sujet_nom, s.sujet_id, s.sondage_nbre_reponse, s.sondage_nombre_vote, sv.membre_id AS voter_id, so.sondage_reponse_num, so.sondage_reponse, so.sondage_votes
				FROM ' . $this->config('sql_prefix') . 'sondage s
				INNER JOIN ' . $this->config('sql_prefix') . 'sujets t
					ON s.sujet_id = t.sujet_id
				LEFT JOIN ' . $this->config('sql_prefix') . 'sondage_votant sv
					ON s.sujet_id = sv.sujet_id
				LEFT JOIN ' . $this->config('sql_prefix') . 'sondage_votes so
					ON s.sujet_id = so.sujet_id
				ORDER BY s.sujet_id';
		$result = Fsb::$db->query($sql);
		$options_id = array();
		$options_id_iterator = 1;
		while ($row = Fsb::$db->row($result))
		{
			$topic_id = $row['sujet_id'];

			if (!isset($return['data'][$topic_id]))
			{
				$return['data'][$topic_id] = array('options' => array(), 'voters' => array());
				$options_id[$topic_id] = array();
			}

			// Informations sur le sondage
			if (!isset($return['data'][$topic_id]['t_id']))
			{
				$return['data'][$topic_id]['t_id'] =				$topic_id;
				$return['data'][$topic_id]['poll_name'] =			$row['sujet_nom'];
				$return['data'][$topic_id]['poll_total_vote'] =		$row['sondage_nombre_vote'];
				$return['data'][$topic_id]['poll_max_vote'] =		$row['sondage_nbre_reponse'];
			}

			// Gestion des options
			if (!isset($options_id[$topic_id][$row['sondage_reponse_num']]))
			{
				$options_id[$topic_id][$row['sondage_reponse_num']] = $options_id_iterator;

				$return['data'][$topic_id]['options'][$options_id_iterator] = array(
					'poll_opt_name' =>	$row['sondage_reponse'],
					'poll_opt_total' =>	$row['sondage_votes'],
				);

				$options_id_iterator++;
			}

			// Gestions des membres qui ont votes
			$voter_id = $this->fsb1_user_id($row['voter_id']);
			if (!in_array($voter_id, $return['data'][$topic_id]['voters']))
			{
				$return['data'][$topic_id]['voters'][] = $voter_id;
			}
		}
		Fsb::$db->free($result);

		return ($return);
	}

	/*
	** Retourne les informations sur le bannissement
	*/
	protected function convert_bans()
	{
		$return = array('data' => array(), 'sql' => array());

		$sql = 'SELECT *
				FROM ' . $this->config('sql_prefix') . 'bannis';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			switch ($row['bannis_type'])
			{
				case 0 :
					$type = 'login';
				break;

				case 1 :
					$type = 'mail';
				break;

				case 2 :
					$type = 'ip';
				break;
			}

			$return['data'][] = array(
				'ban_type' =>		$type,
				'ban_content' =>	$row['bannis_objet'],
				'ban_length' =>		0,
				'ban_reason' =>		'',
				'ban_cookie' =>		0,
			);
		}
		Fsb::$db->free($result);

		return ($return);
	}

	/*
	** Retourne les informations sur les rangs
	*/
	protected function convert_ranks()
	{
		$return = array('data' => array(), 'sql' => array());

		$ranks = $this->fsb1_load_cache('rang');
		foreach ($ranks AS $rank)
		{
			$return['data'][] = array(
				'rank_id' =>			$rank['rang_id'],
				'rank_name' =>			$rank['rang_titre'],
				'rank_img' =>			$rank['rang_img'],
				'rank_special' =>		($rank['rang_quota'] == -1) ? 1 : 0,
				'rank_quota' =>			($rank['rang_quota'] == -1) ? 0 : $rank['rang_quota'],
			);
		}

		// Ajout des rangs aux membres
		$sql = 'SELECT membre_id, membre_rang
				FROM ' . $this->config('sql_prefix') . 'membres
				WHERE membre_rang > 0';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$return['sql'][] = 'UPDATE ' . SQL_PREFIX . 'users SET u_rank_id = ' . $row['membre_rang'] . ' WHERE u_id = ' . $this->fsb1_user_id($row['membre_id']);
		}
		Fsb::$db->free($result);

		return ($return);
	}

	/*
	** Retourne les dossiers pour la copie d'image
	*/
	protected function convert_copy()
	{
		$return = array();

		// Avatars
		$return['avatars'] = array(
			ROOT . $this->config('forum_path') . 'images/avatars/',
		);

		// Rangs
		if (isset($this->fsb1_mods['ranks']))
		{
			$return['ranks'] = array(
				ROOT . $this->config('forum_path') . 'images/rangs/',
			);
		}

		return ($return);
	}

	/*
	** Charge un fichier cache de FSB1
	*/
	private function fsb1_load_cache($key)
	{
		if ($key == 'forum')
		{
			$sql = 'SELECT cache_content
					FROM ' . $this->config('sql_prefix') . 'cache
					WHERE cache_table = \'' . $key . '\'';
			$content = Fsb::$db->get($sql, 'cache_content');
			$data = unserialize($content);
			return ($data);
		}
		else
		{
			$filename = ROOT . $this->config('forum_path') . 'cache/fichier_' . $key . '.php';
			if (!file_exists($filename))
			{
				$this->error(sprintf(Fsb::$session->lang('convert_file_not_exists'), $filename));
			}
			include($filename);

			return ($$key);
		}
	}

	/*
	** On ajoute 1 aux ID de FSB1 (car sous FSB1 les ID commencent a 0 pour l'invite, et donc l'admin a une ID de 1, la ou dans FSB2 l'administrateur
	** a une ID de 2 par defaut).
	*/
	private function fsb1_user_id($user_id)
	{
		return ($user_id + 1);
	}

	/*
	** Converti une autorisation FSB1 en autorisation FSB2
	*/
	private function fsb1_get_auth($f, $level, $auth)
	{
		if (!isset($f[$auth]))
		{
			$f[$auth] = 1;
		}

		if ($f[$auth] > 2)
		{
			$f[$auth]--;
		}

		return (($f[$auth] > $level) ? 0 : 1);
	}

	/*
	** Parse un message FSB1 vers FSB2
	*/
	private function fsb1_parse_message($str)
	{
		$str = $this->fsb1_parse_fsbcode($str);

		$message = new Xml();
		$message->document->setTagName('root');
		$message_line = $message->document->createElement('line');
		$message_line->setAttribute('name', 'description');
		$message_line->setData($str);
		$message->document->appendChild($message_line);
		$str = $message->document->asValidXML();

		return ($str);
	}

	/*
	** Parse les FSBcode FSB1 dans du texte
	*/
	private function fsb1_parse_fsbcode($str)
	{
		$str = preg_replace_callback('#\[taille=([0-9]+?)\](.*?)\[/taille\]#si', array($this, 'fsb1_fsbcode_size'), $str);
		$str = preg_replace('#\[couleur=([0-9]+?)\](.*?)\[/couleur\]#si', '[color=\\1]\\2[/color]', $str);
		$str = preg_replace('#\[php\](.*?)\[/php\]#si', '[code=php]\\1[/code]', $str);
		$str = preg_replace('#\[html\](.*?)\[/html\]#si', '[code=html]\\1[/code]', $str);
		$str = str_replace(array('[quot=', '[/quot]'), array('[quote=', '[/quote]'),  $str);

		return ($str);
	}

	/*
	** Parse correctement le FSBcode SIZE
	*/
	public function fsb1_fsbcode_size($m)
	{
		$size = $m[1];
		$d = array(8, 10, 16, 20, 24);
		if (!in_array($size, $d))
		{
			foreach ($d AS $v)
			{
				if ($v < $size)
				{
					$size = $v;
					break;
				}
			}
		}

		return ('[size=' . $size . ']' . $m[2] . '[/size]');
	}

	/*
	** Retourne la tableau associatif array('ancienne_id_de_groupe' => 'nouvelle_id')
	*/
	private function fsb1_get_groups_id()
	{
		$return = array();

		// ID max des membres
		$sql = 'SELECT MAX(membre_id) AS total
				FROM ' . $this->config('sql_prefix') . 'membres';
		$max_user_id = Fsb::$db->get($sql, 'total') + 20;

		// Creation des groupes
		$sql = 'SELECT g_id
				FROM ' . $this->config('sql_prefix') . 'groupes
				ORDER BY g_id';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$return[$row['g_id']] = $max_user_id++;
		}
		Fsb::$db->free($result);

		return ($return);
	}
}


/* EOF */
