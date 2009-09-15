<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On affiche ce module
$show_this_module = true;

/**
 * Module de moderation pour la division de sujet
 *
 */
class Page_modo_upload extends Fsb_model
{
	public $page;
	public $per_page = 50;
	public $mode;
	public $id;

	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		$this->mode = Http::request('mode');
		$this->id = intval(Http::request('id'));

		$this->page = Http::request('page');
		if ($this->page < 1)
		{
			$this->page = 1;
		}

		if (Http::request('submit_delete', 'post'))
		{
			$this->delete_uploaded_files();
		}

		if (Http::request('submit_edit', 'post'))
		{
			$this->submit_edit_file();
		}

		if ($this->mode == 'edit')
		{
			$this->edit_file();
		}
		else
		{
			$this->show_uploaded_files();
		}
	}

	/**
	 * Affiche la liste des fichiers uploades sur le forum
	 *
	 */
	public function show_uploaded_files()
	{
		// On recupere l'ordre et la direction d'affichage des fichiers
		$direction = strtoupper(Http::request('direction'));
		if ($direction !== 'ASC' && $direction !== 'DESC')
		{
			$direction = 'DESC';
		}

		$order = strtolower(Http::request('order'));
		if (!in_array($order, array('upload_realname', 'upload_filesize', 'upload_time', 'upload_auth', 'u_nickname')))
		{
			$order = 'upload_time';
		}

		// On compte le nombre de fichiers uploades
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'upload';
		$total = Fsb::$db->get($sql, 'total');
		$total_page = $total / $this->per_page;

		Fsb::$tpl->set_file('modo/modo_upload.html');
		Fsb::$tpl->set_switch('upload_index');
		Fsb::$tpl->set_vars(array(
			'PAGINATION' =>		($total_page > 1) ? Html::pagination($this->page, $total_page, ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;order=' . $order . '&amp;direction=' . $direction) : null,
	
			'U_ORDER_FILENAME' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;order=upload_realname&amp;direction=' . (($order == 'upload_realname' && $direction == 'ASC') ? 'DESC' : 'ASC')),
			'U_ORDER_FILESIZE' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;order=upload_filesize&amp;direction=' . (($order == 'upload_filesize' && $direction == 'ASC') ? 'DESC' : 'ASC')),
			'U_ORDER_FILETIME' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;order=upload_time&amp;direction=' . (($order == 'upload_time' && $direction == 'ASC') ? 'DESC' : 'ASC')),
			'U_ORDER_NICKNAME' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;order=u_nickname&amp;direction=' . (($order == 'u_nickname' && $direction == 'ASC') ? 'DESC' : 'ASC')),
			'U_ORDER_AUTH' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;order=upload_auth&amp;direction=' . (($order == 'upload_auth' && $direction == 'ASC') ? 'DESC' : 'ASC')),
		));

		// Liste des fichiers uploades
		$sql = 'SELECT up.upload_id, up.upload_filename, up.upload_realname, up.upload_filesize, up.upload_time, up.upload_auth, u.u_id, u.u_nickname, u.u_color
				FROM ' . SQL_PREFIX . 'upload up
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON up.u_id = u.u_id
				ORDER BY ' . $order . ' ' . $direction . '
				LIMIT ' . (($this->page - 1) * $this->per_page) . ', ' . $this->per_page;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('upload', array(
				'FILENAME' =>		htmlspecialchars($row['upload_realname']),
				'FILESIZE' =>		convert_size($row['upload_filesize']),
				'FILETIME' =>		Fsb::$session->print_date($row['upload_time']),
				'NICKNAME' =>		Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
				'DOWNLOAD' =>		sid(ROOT . 'index.' . PHPEXT . '?p=download&amp;id=' . $row['upload_id']),
				'AUTH' =>			Fsb::$session->lang($GLOBALS['_auth_level'][$row['upload_auth']]),
				'ID' =>				$row['upload_id'],

				'U_EDIT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;mode=edit&amp;id=' . $row['upload_id']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Edition d'un fichier
	 *
	 */
	public function edit_file()
	{
		$sql = 'SELECT upload_realname, upload_auth
				FROM ' . SQL_PREFIX . 'upload
				WHERE upload_id = ' . $this->id;
		if (!$data = Fsb::$db->request($sql))
		{
			Display::message('attached_file_not_exists');
		}

		// Liste des droits pour le telechargement
		$list_upload_auth = array(
			VISITOR =>	Fsb::$session->lang('visitor'),
			USER =>		Fsb::$session->lang('user'),
			MODO =>		Fsb::$session->lang('modo'),
			MODOSUP =>	Fsb::$session->lang('modosup'),
			ADMIN =>	Fsb::$session->lang('admin'),
		);

		foreach (array_keys($list_upload_auth) AS $k)
		{
			if ($k < Fsb::$session->data['auth']['other']['download_file'][1])
			{
				unset($list_upload_auth[$k]);
			}
		}

		Fsb::$tpl->set_file('modo/modo_upload.html');
		Fsb::$tpl->set_switch('upload_edit');
		Fsb::$tpl->set_vars(array(
			'UPLOAD_NAME' =>	htmlspecialchars($data['upload_realname']),
			'LIST_AUTH' =>		Html::make_list('upload_auth', $data['upload_auth'], $list_upload_auth),

			'U_ACTION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;mode=edit&amp;id=' . $this->id),
		));
	}

	/**
	 * Soumission de l'edition d'un fichier
	 *
	 */
	public function submit_edit_file()
	{
		$sql = 'SELECT upload_id
				FROM ' . SQL_PREFIX . 'upload
				WHERE upload_id = ' . $this->id;
		if (!Fsb::$db->request($sql))
		{
			Display::message('attached_file_not_exists');
		}

		$data = array(
			'upload_realname' =>	trim(Http::request('upload_realname', 'post')),
			'upload_auth' =>		intval(Http::request('upload_auth', 'post')),
		);

		Fsb::$db->update('upload', $data, 'WHERE upload_id = ' . $this->id);

		Log::add(Log::MODO, 'edit_upload', $data['upload_realname']);
		Display::message('modo_upload_well_edit', ROOT . 'index.' . PHPEXT . '?p=modo&module=upload', 'modo_upload');
	}

	/**
	 * Suppression des fichiers uploades
	 *
	 */
	public function delete_uploaded_files()
	{
		// Verification des noms de fichiers
		$action = Http::request('action', 'post');
		$action = array_map('intval', $action);

		if (!$action)
		{
			return ;
		}

		// Verification d'existance des fichiers
		$sql = 'SELECT upload_id, upload_filename
				FROM ' . SQL_PREFIX . 'upload
				WHERE upload_id IN (' . implode(', ', $action) . ')';
		$result = Fsb::$db->query($sql);
		$delete = array();
		while ($row = Fsb::$db->row($result))
		{
			$delete[$row['upload_id']] = $row['upload_filename'];
		}
		Fsb::$db->free($result);

		if (!$delete)
		{
			return ;
		}

		// Boite de confirmation
		if (check_confirm())
		{
			foreach ($delete AS $filename)
			{
				if (file_exists(ROOT . 'upload/' . $filename) && is_file(ROOT . 'upload/' . $filename) && is_writable(ROOT . 'upload/'))
				{
					@unlink(ROOT . 'upload/' . $filename);
				}
			}

			$sql = 'DELETE FROM ' . SQL_PREFIX . 'upload
					WHERE upload_id IN (' . implode(', ', array_keys($delete)) . ')';
			Fsb::$db->query($sql);

			Display::message('modo_upload_well_delete', ROOT . 'index.' . PHPEXT . '?p=modo&module=upload', 'modo_upload');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=upload');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('modo_upload_confirm_delete'), ROOT . 'index.' . PHPEXT . '?p=modo&module=upload', array('submit_delete' => true, 'action' => $action));
		}
	}
}

/* EOF */