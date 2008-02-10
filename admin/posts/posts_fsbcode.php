<?php
/*
** +---------------------------------------------------+
** | Name :		~/admin/posts/posts_fsbcode.php
** | Begin :	17/07/2007
** | Last :		13/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion des FSBcode sur le forum
*/
class Fsb_frame_child extends Fsb_admin_frame
{
	// Arguments de la page
	public $mode;
	public $id;

	/*
	** Constructeurs
	*/
	public function main()
	{
		$this->mode =	Http::request('mode');
		$this->id =		intval(Http::request('id'));

		$call = new Call($this);
		$call->post(array(
			'submit' => ':query_add_edit_fsbcode',
		));

		$call->functions(array(
			'mode' => array(
				'add' =>		'page_add_edit_fsbcode',
				'edit' =>		'page_add_edit_fsbcode',
				'delete' =>		'page_delete_fsbcode',
				'default' =>	'page_default_fsbcode',
			),
		));
	}

	/*
	** Affiche la page de gestion des FSBcodes
	*/
	public function page_default_fsbcode()
	{
		Fsb::$tpl->set_switch('fsbcode_list');
		Fsb::$tpl->set_vars(array(
			'U_ADD' =>				sid('index.' . PHPEXT . '?p=posts_fsbcode&amp;mode=add')
		));

		// On récupère les FSBcodes
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'fsbcode
				ORDER BY fsbcode_order';
		$result = Fsb::$db->query($sql, 'fsbcode_');
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('fsbcode', array(
				'TAG' =>			'[' . $row['fsbcode_tag'] . ']',
				'ACTIVATED' =>		($row['fsbcode_activated']) ? TRUE : FALSE,
				'SIG_ACTIVATED' =>	($row['fsbcode_activated_sig']) ? TRUE : FALSE,

				'U_EDIT' =>			sid('index.' . PHPEXT . '?p=posts_fsbcode&amp;mode=edit&amp;id=' . $row['fsbcode_id']),
				'U_DELETE' =>		sid('index.' . PHPEXT . '?p=posts_fsbcode&amp;mode=delete&amp;id=' . $row['fsbcode_id'])
			));
		}
		Fsb::$db->free($result);
	}

	/*
	** Affiche la page permettant d'ajouter / éditer des FSBcode
	*/
	public function page_add_edit_fsbcode()
	{
		if ($this->mode == 'edit')
		{
			$sql = 'SELECT *
					FROM ' . SQL_PREFIX . 'fsbcode
					WHERE fsbcode_id = ' . $this->id;
			$data = Fsb::$db->request($sql);

			$lg_add_edit = Fsb::$session->lang('adm_fsbcode_edit');
		}
		else
		{
			$lg_add_edit = Fsb::$session->lang('adm_fsbcode_add');
			$data = array(
				'fsbcode_tag' =>			'',
				'fsbcode_search' =>			'',
				'fsbcode_replace' =>		'',
				'fsbcode_img' =>			'',
				'fsbcode_list' =>			'',
				'fsbcode_description' =>	'',
				'fsbcode_fct' =>			'',
				'fsbcode_activated' =>		TRUE,
				'fsbcode_activated_sig' =>	TRUE,
			);
		}

		if (!$data['fsbcode_fct'])
		{
			Fsb::$tpl->set_switch('fsbcode_search');
		}

		Fsb::$tpl->set_switch('fsbcode_add');
		Fsb::$tpl->set_vars(array(
			'L_ADD_EDIT' =>				$lg_add_edit,
			'FSBCODE_TAG' =>			$data['fsbcode_tag'],
			'FSBCODE_SEARCH' =>			htmlspecialchars($data['fsbcode_search']),
			'FSBCODE_REPLACE' =>		htmlspecialchars($data['fsbcode_replace']),
			'FSBCODE_LIST' =>			htmlspecialchars($data['fsbcode_list']),
			'FSBCODE_IMG' =>			htmlspecialchars($data['fsbcode_img']),
			'FSBCODE_DESCRIPTION' =>	htmlspecialchars($data['fsbcode_description']),
			'FSBCODE_ACTIVATED' =>		($data['fsbcode_activated']) ? TRUE : FALSE,
			'FSBCODE_ACTIVATED_SIG' =>	($data['fsbcode_activated_sig']) ? TRUE : FALSE,
			'FSBCODE_FCT' =>			$data['fsbcode_fct'],

			'U_ACTION' =>				sid('index.' . PHPEXT . '?p=posts_fsbcode&amp;mode=' . $this->mode . '&amp;id=' . $this->id)
		));
	}

	/*
	** Valide le formulaire d'ajout / édition des mots à censurer
	*/
	public function query_add_edit_fsbcode()
	{
		$data = array(
			'fsbcode_tag' =>			trim(Http::request('fsbcode_tag', 'post')),
			'fsbcode_search' =>			trim(Http::request('fsbcode_search', 'post')),
			'fsbcode_replace' =>		trim(Http::request('fsbcode_replace', 'post')),
			'fsbcode_list' =>			trim(Http::request('fsbcode_list', 'post')),
			'fsbcode_img' =>			trim(Http::request('fsbcode_img', 'post')),
			'fsbcode_description' =>	trim(Http::request('fsbcode_description', 'post')),
			'fsbcode_activated' =>		intval(Http::request('fsbcode_activated', 'post')),
			'fsbcode_activated_sig' =>	intval(Http::request('fsbcode_activated_sig', 'post')),
		);

		$errstr = array();

		if (!$data['fsbcode_tag'] || !$data['fsbcode_search'])
		{
			$errstr[] = Fsb::$session->lang('fields_empty');
		}

		if (!preg_match('#^[a-zA-Z]+$#i', $data['fsbcode_tag']))
		{
			$errstr[] = Fsb::$session->lang('adm_fsbcode_bad_tag');
		}

		if ($errstr)
		{
			Display::message(Html::make_errstr($errstr));
		}

		if ($this->mode == 'add')
		{
			// Ordre du nouveau FSBcode
			$sql = 'SELECT MAX(fsbcode_order) AS max
					FROM ' . SQL_PREFIX . 'fsbcode';
			$data['fsbcode_order'] = Fsb::$db->get($sql, 'max') + 1;

			Fsb::$db->insert('fsbcode', $data);
		}
		else
		{
			Fsb::$db->update('fsbcode', $data, 'WHERE fsbcode_id = ' . $this->id);
		}

		Fsb::$db->destroy_cache('fsbcode_');
		Log::add(Log::ADMIN, 'fsbcode_log_' . $this->mode, $data['fsbcode_tag']);
		Display::message('adm_fsbcode_well_' . $this->mode, 'index.' . PHPEXT . '?p=posts_fsbcode', 'posts_fsbcode');
	}

	/*
	** Suppression d'un FSBcode
	*/
	public function page_delete_fsbcode()
	{
		if (check_confirm())
		{
			$sql = 'SELECT fsbcode_tag, fsbcode_order
					FROM ' . SQL_PREFIX . 'fsbcode
					WHERE fsbcode_id = ' . $this->id;
			if ($data = Fsb::$db->request($sql))
			{
				// Mise à jour de l'ordre
				Fsb::$db->update('fsbcode', array(
					'fsbcode_order' =>	array('(fsbcode_order - 1)', 'is_field' => TRUE),
				), 'WHERE fsbcode_order > ' . $data['fsbcode_order']);

				$sql = 'DELETE FROM ' . SQL_PREFIX . 'fsbcode
						WHERE fsbcode_id = ' . $this->id;
				Fsb::$db->query($sql);
				Fsb::$db->destroy_cache('fsbcode_');

				Log::add(Log::ADMIN, 'fsbcode_log_delete', $data['fsbcode_tag']);
				Display::message('adm_fsbcode_well_delete', 'index.' . PHPEXT . '?p=posts_fsbcode', 'posts_fsbcode');
			}
			else
			{
				Http::redirect('index.' . PHPEXT . '?p=posts_fsbcode');
			}
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=posts_fsbcode');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('adm_fsbcode_delete_confirm'), 'index.' . PHPEXT . '?p=posts_fsbcode', array('mode' => $this->mode, 'id' => $this->id));
		}


		if ($this->id)
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'censor
						WHERE censor_id = ' . $this->id;
			Fsb::$db->query($sql);
			Fsb::$db->destroy_cache('censor_');
		}

		Log::add(Log::ADMIN, 'censor_log_delete');

		Display::message('adm_censor_well_delete', 'index.' . PHPEXT . '?p=posts_censor', 'posts_censor');
	}
}

/* EOF */