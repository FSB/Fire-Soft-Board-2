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
 * On affiche le module ?
 *
 * @var bool
 */
$show_this_module = true;

/**
 * Module d'utilisateur permettant de modifier ses donnees personelles
 */
class Page_user_personal extends Fsb_model
{
	/**
	 * Erreurs
	 *
	 * @var array
	 */
	public $errstr = array();

	/**
	 * Les champs qu'on peut recuperer dans le formulaire
	 *
	 * @var array
	 */
	public $post_data = array();

	/**
	 * Donnees du profil personel
	 *
	 * @var array
	 */
	public $data = array();

	/**
	 * Defini si l'utilisateur peut modifier son profil personel
	 *
	 * @var bool
	 */
	public $can_edit_nickname = false;

	/**
	 * Champs du profil
	 *
	 * @var Profil_fields
	 */
	public $profil_fields;

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		// l'utilisateur peut il editer son pseudonyme ?
		$this->can_edit_nickname = Fsb::$cfg->get('user_edit_nickname');

		$this->get_data();
		if (Http::request('submit', 'post'))
		{
			$this->check_form();
			if (!count($this->errstr))
			{
				$this->submit_form();
			}
		}
		$this->personal_form();
	}

	/**
	 * Recupere dans $this->data les donnees du membre, si le formulaire a ete
	 * soumis on prend les valeurs postees
	 */
	public function get_data()
	{
		$this->post_data = array(
			array('field' => 'u_nickname', 'insert' => true),
			array('field' => 'u_tpl', 'insert' => true),
			array('field' => 'u_language', 'insert' => true),
			array('field' => 'u_birthday', 'insert' => true),
			array('field' => 'u_birthday_day', 'insert' => false),
			array('field' => 'u_birthday_month', 'insert' => false),
			array('field' => 'u_birthday_year', 'insert' => false),
			array('field' => 'u_sexe', 'insert' => true),
			array('field' => 'u_rank_id', 'insert' => true),
			array('field' => 'u_utc', 'insert' => true),
			array('field' => 'u_utc_dst', 'insert' => true),
		);

		foreach ($this->post_data AS $value)
		{
			$this->data[$value['field']] = Http::request($value['field'], 'post');
			if (is_null($this->data[$value['field']]) && $value['insert'])
			{
				$this->data[$value['field']] = Fsb::$session->data[$value['field']];
			}
			$this->data[$value['field']] = trim($this->data[$value['field']]);
		}
	}

	/**
	 * Affiche le formulaire d'editer les donnees personnelles
	 */
	public function personal_form()
	{
		if ($this->can_edit_nickname)
		{
			Fsb::$tpl->set_switch('edit_nickname');
		}

		if ($this->errstr)
		{
			Fsb::$tpl->set_switch('error');
		}

		// Peut editer la langue ?
		if (!Fsb::$cfg->get('override_lang'))
		{
			Fsb::$tpl->set_switch('can_change_lang');
		}

		// Peut editer le theme ?
		if (!Fsb::$cfg->get('override_tpl'))
		{
			Fsb::$tpl->set_switch('can_change_tpl');
		}

		// On recupere la date de naissance du membre
		if (Fsb::$mods->is_active('user_birthday') && preg_match('#^([0-9]{1,2})/([0-9]{1,2})/([0-9]{4})$#i', $this->data['u_birthday'], $match))
		{
			$u_birthday_day = intval($match[1]);
			$u_birthday_month = intval($match[2]);
			$u_birthday_year = intval($match[3]);
		}
		else
		{
			$u_birthday_day = $u_birthday_month = $u_birthday_year = 0;
		}

		// On genere les listes de jours, mois, annees
		if (Fsb::$mods->is_active('user_birthday'))
		{
			$build_list = array('day' => array(1, 31), 'month' => array(1, 12), 'year' => array(1920, (date('Y', CURRENT_TIME) - 8)));
			foreach ($build_list AS $type => $values)
			{
				${'list_' . $type} = array(0 => '---');
				for ($i = $values[0]; $i <= $values[1]; $i++)
				{
					${'list_' . $type}[$i] = ($type == 'month') ? Fsb::$session->lang('month_' . $i) : $i;
				}
			}
		}
		else
		{
			$list_day = $list_month = $list_year = array();
		}

		// Liste des rangs du membre
		$result = $this->get_user_ranks();
		$list_ranks = array();
		while ($row = Fsb::$db->row($result))
		{
			$list_ranks[$row['rank_id']] = $row['rank_name'];
		}
		Fsb::$db->free($result);

		// On affiche la liste des rangs que s'il y a plus de deux rangs pour le membre
		if (count($list_ranks) > 1)
		{
			Fsb::$tpl->set_switch('show_ranks');
		}

		Fsb::$tpl->set_file('user/user_personal.html');
		Fsb::$tpl->set_vars(array(
			'USER_NICKNAME' =>		htmlspecialchars($this->data['u_nickname']),
			'LIST_RANKS' =>			Html::make_list('u_rank_id', Fsb::$session->data['u_rank_id'], $list_ranks, array('id' => 'u_rank_id')),
			'LIST_TPL' =>			Html::list_dir('u_tpl', $this->data['u_tpl'], ROOT . 'tpl/', array(), true, '', 'id="u_tpl_id"'),
			'LIST_LANG' =>			Html::list_langs('u_language', $this->data['u_language']),
			'LIST_UTC' =>			Html::list_utc('u_utc', Fsb::$session->data['u_utc'], 'utc'),
			'LIST_UTC_DST' =>		Html::list_utc('u_utc_dst', Fsb::$session->data['u_utc_dst'], 'dst'),
			'LIST_DAY' =>			Html::make_list('u_birthday_day', $u_birthday_day, $list_day, array('id' => 'u_birthday_day')),
			'LIST_MONTH' =>			Html::make_list('u_birthday_month', $u_birthday_month, $list_month),
			'LIST_YEAR' =>			Html::make_list('u_birthday_year', $u_birthday_year, $list_year),
			'SEXE_MALE' =>			($this->data['u_sexe'] == SEXE_MALE) ? 'checked="checked"' : '',
			'SEXE_FEMALE' =>		($this->data['u_sexe'] == SEXE_FEMALE) ? 'checked="checked"' : '',
			'SEXE_NONE' =>			($this->data['u_sexe'] == SEXE_NONE) ? 'checked="checked"' : '',
			'CONTENT' =>			Html::make_errstr($this->errstr),
		));

		// Champs personals crees par l'administrateur
		Profil_fields_forum::form(PROFIL_FIELDS_PERSONAL, 'personal', Fsb::$session->id());
	}

	/**
	 * Retourne un identifiant de ressource sur la requete de recuperation des rangs du membre
	 *
	 * @return Ressource
	 */
	public function get_user_ranks()
	{
		$sql = 'SELECT r.rank_id, r.rank_name
				FROM ' . SQL_PREFIX . 'groups_users gu
				INNER JOIN ' . SQL_PREFIX . 'groups g
					ON gu.g_id = g.g_id
				INNER JOIN ' . SQL_PREFIX . 'ranks r
					ON r.rank_id = g.g_rank
				WHERE gu.u_id = ' . Fsb::$session->id() . '
					AND g.g_rank <> 0';
		return (Fsb::$db->query($sql));
	}

	/**
	 * Verifie les donnees envoyees par le formulaire
	 */
	public function check_form()
	{
		$this->errstr = array();
		if (!is_dir(ROOT . 'tpl/' . $this->data['u_tpl']))
		{
			$this->errstr[] = Fsb::$session->lang('user_tpl_not_exists');
		}

		if (!is_dir(ROOT . 'lang/' . $this->data['u_language']))
		{
			$this->errstr[] = Fsb::$session->lang('user_lang_not_exists');
		}

		// Changement de pseudonyme
		if (strtolower($this->data['u_nickname']) != strtolower(Fsb::$session->data['u_nickname']) && $this->can_edit_nickname)
		{
			// Pseudonyme valide ?
			if (($valid_nickname = User::nickname_valid($this->data['u_nickname'])) !== true)
			{
				$this->errstr[] = Fsb::$session->lang('nickname_chars_' . $valid_nickname);
			}

			// Verification de l'existance du pseudonyme
			if (User::nickname_exists($this->data['u_nickname']))
			{
				$this->errstr[] = Fsb::$session->lang('user_nickname_exists');
			}
		}

		// On verifie la langue choisie
		if (Fsb::$cfg->get('override_lang') || !file_exists(ROOT . 'lang/' . $this->data['u_language']))
		{
			$this->data['u_language'] = Fsb::$session->data['u_language'];
		}

		// On verifie le theme choisi
		if (Fsb::$cfg->get('override_tpl') || !file_exists(ROOT . 'tpl/' . $this->data['u_tpl']))
		{
			$this->data['u_tpl'] = Fsb::$session->data['u_tpl'];
		}

		// On met en forme la date de naissance
		$this->data['u_birthday'] = '';
		foreach (array('day' => 2, 'month' => 2, 'year' => 4) AS $key => $value)
		{
			$item = strval($this->data['u_birthday_' . $key]);
			for ($i = strlen($item); $i < $value; $i++)
			{
				$this->data['u_birthday'] .= '0';
			}
			$this->data['u_birthday'] .= $item . '/';
		}
		$this->data['u_birthday'] = substr($this->data['u_birthday'], 0, -1);

		// On verifie les champs personels
		if (!$this->errstr)
		{
			Profil_fields_forum::validate(PROFIL_FIELDS_PERSONAL, 'personal', $this->errstr, Fsb::$session->id());
		}

		// On verifie les rangs du membre
		if ($this->data['u_rank_id'])
		{
			$result = $this->get_user_ranks();
			$exists = false;
			$rank_exists = false;
			while ($row = Fsb::$db->row($result))
			{
				$rank_exists = true;
				if ($row['rank_id'] == $this->data['u_rank_id'])
				{
					$exists = true;
					break;
				}
			}
			Fsb::$db->free($result);

			if (!$rank_exists || !$exists)
			{
				$this->data['u_rank_id'] = '';
			}
		}
	}

	/**
	 * Enregistre les donnees du formulaire dans la base de donnee
	 */
	public function submit_form()
	{
		// Mise a jour de la table fsb2_users
		$update_array = array();
		foreach ($this->post_data AS $value)
		{
			if ($value['insert'])
			{
				$update_array[$value['field']] = $this->data[$value['field']];
			}
		}

		// Si la date de naissance est desactivee ...
		if (!Fsb::$mods->is_active('user_birthday'))
		{
			unset($update_array['u_birthday']);
		}

		if ($this->data['u_nickname'] != Fsb::$session->data['u_nickname'] && $this->can_edit_nickname)
		{
			$update_array['u_nickname'] = $this->data['u_nickname'];
			User::rename(Fsb::$session->id(), $update_array['u_nickname'], false);
			Log::user(Fsb::$session->id(), 'update_nickname', Fsb::$session->data['u_nickname'], $update_array['u_nickname']);
		}
		else
		{
			unset($update_array['u_nickname']);
		}

		//Si le rank_id est vide on ne le met pas à jour
		if ($this->data['u_rank_id'] == '')
		{
			unset($update_array['u_rank_id']);
		}

		// Mise a jour des donnees du membre
		Fsb::$db->update('users', $update_array, 'WHERE u_id = ' . Fsb::$session->id());
		Fsb::$db->destroy_cache('users_birthday_');

		Log::user(Fsb::$session->id(), 'update_personal');
		Display::message('user_profil_submit', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=personal', 'forum_profil');
	}
}


/* EOF */
