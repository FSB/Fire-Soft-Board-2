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
 * Gestion des champs personels / contact du profil
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Mode de la frame
	 *
	 * @var string
	 */
	public $mode;
	
	/**
	 * Identifiant du champ personnel
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Module chargé dans la frame
	 *
	 * @var string
	 */
	public $module;
	
	/**
	 * Donnees du formulaire
	 * 
	 * @var array
	 */
	public $data = array();

	/**
	 * Type HTML choisi
	 * 
	 * @var 
	 */
	public $type;

	/**
	 * Type de page
	 *
	 * @var int
	 */
	public $fields_type = PROFIL_FIELDS_PERSONAL;

	/**
	 * Erreurs
	 *
	 * @var array
	 */
	public $errstr = array();
	
	/**
	 * Constructeur
	 */
	public function main()
	{
		$this->mode =	Http::request('mode');
		$this->id =		intval(Http::request('id'));

		// On recupere le type HTML
		$this->type = intval(Http::request('type', 'post|get'));

		$call = new Call($this);
		$call->module(array(
			'list' =>		array('personal', 'contact'),
			'url' =>		'index.' . PHPEXT . '?p=users_profile_fields',
			'lang' =>		'adm_profile_fields_',
			'default' =>	'personal',
		));

		$this->fields_type = ($this->module == 'contact') ? PROFIL_FIELDS_CONTACT : PROFIL_FIELDS_PERSONAL;
		if ($this->module == 'contact')
		{
			$this->type = Profil_fields::TEXT;
		}
		
		$call->post(array(
			'submit_check' =>	':check_form_pf',
		));

		$call->functions(array(
			'mode' => array(
				'delete' =>		'delete_pf',
				'add' =>		'add_edit_form_pf',
				'edit' =>		'add_edit_form_pf',
				'up' =>			'move_pf',
				'down' =>		'move_pf',
				'default' =>	'main_form_pf',
			),
		));
	}
	
	/**
	 * Affiche le listing des champs existants, avec les boutons ajouter, editer, etc ...
	 */
	public function main_form_pf()
	{		
		Fsb::$tpl->set_switch('fields_list');
		Fsb::$tpl->set_vars(array(
			'PF_LIST' =>	Fsb::$session->lang('adm_pf_' . $this->module),

			'U_ADD' =>		sid('index.' . PHPEXT . '?p=users_profile_fields&amp;mode=add&amp;module=' . $this->module),
		));
		
		$sql = 'SELECT pf_id, pf_lang, pf_lang_desc
				FROM ' . SQL_PREFIX . 'profil_fields
				WHERE pf_type = ' . $this->fields_type . '
				ORDER BY pf_order';
		$result = Fsb::$db->query($sql, 'profil_fields_');
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('pf', array(
				'NAME' =>		String::parse_lang($row['pf_lang']),
				'DESC' =>		String::parse_lang($row['pf_lang_desc']),
				
				'U_EDIT' =>		sid('index.' . PHPEXT . '?p=users_profile_fields&amp;mode=edit&amp;id=' . $row['pf_id'] . '&amp;module=' . $this->module),
				'U_DELETE' =>	sid('index.' . PHPEXT . '?p=users_profile_fields&amp;mode=delete&amp;id=' . $row['pf_id'] . '&amp;module=' . $this->module),
				'U_UP' =>		sid('index.' . PHPEXT . '?p=users_profile_fields&amp;mode=up&amp;id=' . $row['pf_id'] . '&amp;module=' . $this->module),
				'U_DOWN' =>	sid('index.' . PHPEXT . '?p=users_profile_fields&amp;mode=down&amp;id=' . $row['pf_id'] . '&amp;module=' . $this->module),
			));
		}
		Fsb::$db->free($result);
	}
	
	/**
	 * Formulaire d'ajout / edition de champs personels
	 */
	public function add_edit_form_pf()
	{		
		if ($this->errstr)
		{
			Fsb::$tpl->set_switch('error');
			$this->data['pf_list'] = (!empty($this->data['pf_list'])) ? implode("\n", $this->data['pf_list']) : '';
			$this->data['pf_groups'] = explode(',', $this->data['pf_groups']);
		}
		else if ($this->mode == 'edit')
		{
			$sql = 'SELECT *
					FROM ' . SQL_PREFIX . 'profil_fields
					WHERE pf_id = ' . $this->id;
			$this->data = Fsb::$db->request($sql);
			if (!$this->data)
			{
				Display::message('no_result');
			}
			$this->data['pf_list'] = (!empty($this->data['pf_list'])) ? implode("\n", unserialize($this->data['pf_list'])) : '';
			$this->data['pf_groups'] = explode(',', $this->data['pf_groups']);
			$this->type = $this->data['pf_html_type'];
		}
		else
		{
			$this->data = array(
				'pf_lang' =>		'',
				'pf_lang_desc' =>	'',
				'pf_regexp' =>		'',
				'pf_groups' =>		array(),
				'pf_topic' =>		false,
				'pf_register' =>	false,
				'pf_maxlength' =>	50,
				'pf_sizelist' =>	5,
				'pf_output' =>		'',
				'pf_list' =>		'',
			);
		}
		
		if ($this->mode != 'edit' && $this->module == 'personal')
		{
			Fsb::$tpl->set_switch('html_type');
		}
		
		// On genere la liste des types HTML
		$list_type = array();
		foreach (Profil_fields::$type AS $key => $data)
		{
			$list_type[$key] = Fsb::$session->lang('adm_pf_type_' . $data['name']);
		}
		$list_personal_type = Html::make_list('type', $this->type, $list_type, array(
			'onchange' =>	'location.href=\'' . sid('index.' . PHPEXT . '?p=users_profile_fields&amp;module=personal&amp;mode=add&amp;type=') . '\' + this.value',
		));

		Profil_fields_admin::form($this->type);
		$info = Profil_fields::$type[$this->type];

		Fsb::$tpl->set_switch('fields_add');
		Fsb::$tpl->set_vars(array(
			'CONTENT' =>			Html::make_errstr($this->errstr),
			'PF_ADD' =>				Fsb::$session->lang('adm_pf_add_' . $this->module),
			'PF_LANG' =>			htmlspecialchars($this->data['pf_lang']),
			'PF_DESC' =>			htmlspecialchars($this->data['pf_lang_desc']),
			'PF_REGEXP' =>			htmlspecialchars($this->data['pf_regexp']),
			'PF_MAXLENGTH' =>		$this->data['pf_maxlength'],
			'PF_LIST' =>			$this->data['pf_list'],
			'PF_SIZELIST' =>		$this->data['pf_sizelist'],
			'PF_OUTPUT' =>			htmlspecialchars($this->data['pf_output']),
			'PF_TOPIC' =>			($this->data['pf_topic']) ? true : false,
			'PF_REGISTER' =>		($this->data['pf_register']) ? true : false,
			'MAXLENGTH_EXPLAIN' =>	(isset($info['maxlength'])) ? sprintf(Fsb::$session->lang('adm_pf_maxlength_explain'), $info['maxlength']['min'] + 1, $info['maxlength']['max']) : '',
			
			'LIST_PERSONAL_TYPE' =>	$list_personal_type,
			'LIST_GROUPS' =>		Html::list_groups('pf_groups[]', GROUP_SPECIAL |GROUP_NORMAL, $this->data['pf_groups'], true),
			
			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=users_profile_fields&amp;mode=' . $this->mode . '&amp;id=' . $this->id . '&amp;type=' . $this->type . '&amp;module=' . $this->module),
		));
	}
	
	/**
	 * Verification des donnees du formulaire
	 */
	public function check_form_pf()
	{
		$this->data = Profil_fields_admin::validate($this->type, $this->errstr);
		if (!$this->errstr)
		{
			$this->submit_form_pf();
		}
	}
	
	/**
	 * Soumet le formulaire
	 */
	public function submit_form_pf()
	{
		if ($this->mode == 'edit')
		{
			Profil_fields_admin::update($this->id, $this->data);
		}
		else
		{
			Profil_fields_admin::add($this->fields_type, $this->data);
		}
		Fsb::$db->destroy_cache('profil_fields_');
		
		Log::add(Log::ADMIN, 'pf_log_' . $this->mode, $this->data['pf_lang']);
		Display::message('adm_pf_well_' . $this->mode, 'index.' . PHPEXT . '?p=users_profile_fields&amp;module=' . $this->module, 'users_' . $this->module);
	}
	
	/**
	 * Supprime un champ personel
	 */
	public function delete_pf()
	{
		// On recupere le nom du champ, pour le log
		$sql = 'SELECT pf_lang
				FROM ' . SQL_PREFIX . 'profil_fields
				WHERE pf_id = ' . $this->id;
		if ($pf_lang = Fsb::$db->get($sql, 'pf_lang'))
		{
			// Boite de confirmation
			if (check_confirm())
			{
				Profil_fields_admin::delete($this->id, $this->fields_type);
				Fsb::$db->destroy_cache('profil_fields_');
			
				Log::add(Log::ADMIN, 'pf_log_delete', $pf_lang);

				Display::message('adm_pf_well_delete', 'index.' . PHPEXT . '?p=users_profile_fields&amp;module=' . $this->module, 'users_' . $this->module);
			}
			else if (Http::request('confirm_no', 'post'))
			{
				Http::redirect('index.' . PHPEXT . '?p=users_profile_fields&module=' . $this->module);
			}
			else
			{
				Display::confirmation(Fsb::$session->lang('adm_pf_confirm_delete'), 'index.' . PHPEXT . '?p=users_profile_fields&module=' . $this->module, array('mode' => 'delete', 'id' => $this->id));
				return ;
			}
		}
		Http::redirect('index.' . PHPEXT . '?p=users_profile_fields&module=' . $this->module);
	}
	
	/**
	 * Deplace un champ personel
	 */
	public function move_pf()
	{
		Profil_fields_admin::move($this->id, (($this->mode == 'up') ? -1 : 1), $this->fields_type);
		Fsb::$db->destroy_cache('profil_fields_');
		Http::redirect('index.' . PHPEXT . '?p=users_profile_fields&module=' . $this->module);
	}
}

/* EOF */
