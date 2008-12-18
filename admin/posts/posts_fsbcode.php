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
 * Gestion des FSBcode sur le forum
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
	 * Identifiant du FSBcode
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Constructeur
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
				'up' =>			'page_move_fsbcode',
				'down' =>		'page_move_fsbcode',
				'default' =>	'page_default_fsbcode',
			),
		));
	}

	/**
	 * Affiche la page de gestion des FSBcodes
	 */
	public function page_default_fsbcode()
	{
		Fsb::$tpl->set_switch('fsbcode_list');
		Fsb::$tpl->set_vars(array(
			'U_ADD' =>				sid('index.' . PHPEXT . '?p=posts_fsbcode&amp;mode=add')
		));

		// On recupere les FSBcodes
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'fsbcode
				ORDER BY fsbcode_order';
		$result = Fsb::$db->query($sql, 'fsbcode_');
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('fsbcode', array(
				'TAG' =>			'[' . $row['fsbcode_tag'] . ']',
				'ACTIVATED' =>		($row['fsbcode_activated']) ? true : false,
				'SIG_ACTIVATED' =>	($row['fsbcode_activated_sig']) ? true : false,

				'U_EDIT' =>			sid('index.' . PHPEXT . '?p=posts_fsbcode&amp;mode=edit&amp;id=' . $row['fsbcode_id']),
				'U_DELETE' =>		sid('index.' . PHPEXT . '?p=posts_fsbcode&amp;mode=delete&amp;id=' . $row['fsbcode_id']),
				'U_UP' =>			sid('index.' . PHPEXT . '?p=posts_fsbcode&amp;mode=up&amp;id=' . $row['fsbcode_id']),
				'U_DOWN' =>			sid('index.' . PHPEXT . '?p=posts_fsbcode&amp;mode=down&amp;id=' . $row['fsbcode_id']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Affiche la page permettant d'ajouter / editer des FSBcode
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
				'fsbcode_activated' =>		true,
				'fsbcode_activated_sig' =>	true,
				'fsbcode_menu' =>			true,
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
			'FSBCODE_ACTIVATED' =>		($data['fsbcode_activated']) ? true : false,
			'FSBCODE_ACTIVATED_SIG' =>	($data['fsbcode_activated_sig']) ? true : false,
			'FSBCODE_FCT' =>			$data['fsbcode_fct'],
			'FSBCODE_MENU' =>			($data['fsbcode_menu']) ? true : false,

			'U_ACTION' =>				sid('index.' . PHPEXT . '?p=posts_fsbcode&amp;mode=' . $this->mode . '&amp;id=' . $this->id)
		));
	}

	/**
	 * Valide le formulaire d'ajout / edition des mots a censurer
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
			'fsbcode_menu' =>			intval(Http::request('fsbcode_menu', 'post')),
		);

		$errstr = array();

		$sql = 'SELECT fsbcode_fct
				FROM ' . SQL_PREFIX . 'fsbcode
				WHERE fsbcode_id = ' . $this->id;
		$f = Fsb::$db->request($sql);

		if (!$data['fsbcode_tag'] || (!$f['fsbcode_fct'] && !$data['fsbcode_search']))
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

	/**
	 * Suppression d'un FSBcode
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
				// Mise a jour de l'ordre
				Fsb::$db->update('fsbcode', array(
					'fsbcode_order' =>	array('(fsbcode_order - 1)', 'is_field' => true),
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

	/**
	 * Deplace un FSBcode avec un autre
	 */
	public function page_move_fsbcode()
	{
		$move = ($this->mode == 'up') ? -1 : 1;

		// Position du FSBcode courant
		$sql = 'SELECT fsbcode_order
				FROM ' . SQL_PREFIX . 'fsbcode
				WHERE fsbcode_id = ' . $this->id;
		$d = Fsb::$db->request($sql);

		if ($d)
		{
			// ID du FSBcode a switcher
			$sql = 'SELECT fsbcode_id
					FROM ' . SQL_PREFIX . 'fsbcode
					WHERE fsbcode_order = ' . ($d['fsbcode_order'] + $move);
			$swap_fsbcode_id = Fsb::$db->get($sql, 'fsbcode_id');

			if ($swap_fsbcode_id)
			{
				// Mise a jour de la position des deux FSBcodes
				Fsb::$db->update('fsbcode', array(
					'fsbcode_order' =>	($d['fsbcode_order'] + $move),
				), 'WHERE fsbcode_id = ' . intval($this->id));

				Fsb::$db->update('fsbcode', array(
					'fsbcode_order' =>	$d['fsbcode_order'],
				), 'WHERE fsbcode_id = ' . $swap_fsbcode_id);

				Fsb::$db->destroy_cache('fsbcode_');
			}
		}
		Http::redirect('index.' . PHPEXT . '?p=posts_fsbcode');
	}
}

/* EOF */