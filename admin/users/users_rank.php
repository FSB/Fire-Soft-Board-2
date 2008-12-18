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
 * Gestion des rangs
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
	 * Identifiant du rang
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
			'submit' =>	':page_submit_rank',
		));

		$call->functions(array(
			'mode' => array(
				'add' =>		'page_add_edit_rank',
				'edit' =>		'page_add_edit_rank',
				'delete' =>		'page_delete_rank',
				'default' =>	'page_default_rank',
			),
		));
	}

	/**
	 * Page par defaut d'affichage des rangs
	 */
	public function page_default_rank()
	{		
		Fsb::$tpl->set_switch('ranks_list');
		
		Fsb::$tpl->set_vars(array(
			'U_ADD' =>					sid('index.' . PHPEXT . '?p=users_rank&amp;mode=add'),
			'RANK_PATH' =>				RANK_PATH,
		));
		
		// On recupere et on affiche les rangs
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'ranks
				ORDER BY rank_special DESC, rank_quota';
		$result = Fsb::$db->query($sql, 'ranks_');
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('rank', array(
				'RANK_IMG' =>			$row['rank_img'],
				'RANK_NAME' =>			$row['rank_name'],
				'RANK_COLOR' =>			$row['rank_color'],
				'RANK_QUOTA' =>			$row['rank_quota'],
				'RANK_SPECIAL' =>		$row['rank_special'],
				
				'U_EDIT' =>			sid('index.' . PHPEXT . '?p=users_rank&amp;mode=edit&amp;id=' . $row['rank_id']),
				'U_DELETE' =>		sid('index.' . PHPEXT . '?p=users_rank&amp;mode=delete&amp;id=' . $row['rank_id']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Page permettant d'editer / ajouter un rang
	 */
	public function page_add_edit_rank()
	{		
		if ($this->mode == 'edit')
		{
			$sql = 'SELECT *
					FROM ' . SQL_PREFIX . 'ranks
					WHERE rank_id = ' . $this->id;
			$current = Fsb::$db->request($sql);

			$rank_name = $current['rank_name'];
			$rank_img = $current['rank_img'];
			$rank_quota = $current['rank_quota'];
			$rank_special = $current['rank_special'];

			// Style du rang
			$style_type = $style_content = '';
			if ($getstyle = Html::get_style($current['rank_color']))
			{
				list($style_type, $style_content) = $getstyle;
			}
		}
		else
		{
			$rank_name = '';
			$rank_img = '0';
			$rank_quota = 0;
			$rank_special = false;
			$style_type = '';
			$style_content = '';
		}
		
		Fsb::$tpl->set_switch('ranks_add');
		
		Fsb::$tpl->set_vars(array(
			'L_ADD_EDIT' =>				($this->mode == 'add') ? Fsb::$session->lang('adm_rank_add') : Fsb::$session->lang('adm_rank_edit'),
			
			'ADM_RANK_UPLOAD_EXP' =>	sprintf(Fsb::$session->lang('adm_rank_upload_exp'), RANK_PATH),
			'RANK_NAME' =>				$rank_name,
			'RANK_QUOTA' =>				$rank_quota,
			'RANK_SPECIAL_YES' =>		($rank_special) ? 'checked="checked"' : '',
			'RANK_SPECIAL_NO' =>		(!$rank_special) ? 'checked="checked"' : '',
			'RANK_STYLE_CONTENT' =>		$style_content,
			'RANK_STYLE_TYPE' =>		$style_type,
		
			'LIST_RANK' =>				Html::list_dir('rank_img', $rank_img, RANK_PATH, Upload::$img, false, '<option value="0">-----</option>', 'id="select_rank_image" onchange="show_rank_image()"'),
			
			'U_ACTION' =>				sid('index.' . PHPEXT . '?p=users_rank&amp;mode=' . $this->mode . '&amp;id=' . $this->id),
		));
	}

	/**
	 * Valide le formulaire d'ajout / edition de rangs
	 */
	public function page_submit_rank()
	{
		$rank_name =		trim(Http::request('rank_name', 'post'));
		$rank_img =			trim(Http::request('rank_img', 'post'));
		$rank_quota =		intval(Http::request('rank_quota', 'post'));
		$rank_special =		intval(Http::request('rank_special', 'post'));
		$rank_color =		Html::set_style(trim(Http::request('rank_style_type', 'post')), trim(Http::request('rank_style', 'post')));

		if (!empty($_FILES['upload_rank']['name']))
		{
			$upload = new Upload('upload_rank');
			$upload->only_img();
			$rank_img = $upload->store(RANK_PATH);
		}
		else if ($rank_img == '0')
		{
			$rank_img = '';
		}

		if ($this->mode == 'add')
		{
			Fsb::$db->insert('ranks', array(
				'rank_name' =>			$rank_name,
				'rank_img' =>			$rank_img,
				'rank_quota' =>			$rank_quota,
				'rank_special' =>		$rank_special,
				'rank_color' =>			$rank_color,
			));
			Fsb::$db->destroy_cache('ranks_');
		}
		else
		{
			Fsb::$db->update('ranks', array(
				'rank_name' => 			$rank_name,
				'rank_img' => 			$rank_img,
				'rank_quota' => 		$rank_quota,
				'rank_special' => 		$rank_special,
				'rank_color' => 		$rank_color,
			), 'WHERE rank_id = ' . $this->id);
			Fsb::$db->destroy_cache('ranks_');
		}

		Log::add(Log::ADMIN, 'rank_log_' . $this->mode, $rank_name);
		Display::message('adm_rank_well_' . $this->mode, 'index.' . PHPEXT . '?p=users_rank', 'users_rank');
	}

	/**
	 * Page de suppression des rangs
	 */
	public function page_delete_rank()
	{
		// Nom du rang
		$sql = 'SELECT rank_name
				FROM ' . SQL_PREFIX . 'ranks
				WHERE rank_id = ' . $this->id;
		$rank_name = Fsb::$db->get($sql, 'rank_name');

		// Mise a jour de la tables des utilisateurs
		Fsb::$db->update('users', array(
			'u_rank_id' =>		0,
		), 'WHERE u_rank_id = ' . $this->id);

		$sql = 'DELETE FROM ' . SQL_PREFIX . 'ranks
					WHERE rank_id = ' . $this->id;
		Fsb::$db->query($sql);
		Fsb::$db->destroy_cache('ranks_');

		Log::add(Log::ADMIN, 'rank_log_delete', $rank_name);
		Display::message('adm_rank_well_delete', 'index.' . PHPEXT . '?p=users_rank', 'users_rank');
	}
}

/* EOF */
