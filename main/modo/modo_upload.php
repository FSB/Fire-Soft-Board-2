<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/modo/modo_upload.php
** | Begin :	25/05/2007
** | Last :		11/09/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

// On affiche ce module
$show_this_module = TRUE;

/*
** Module de modération pour la division de sujet
*/
class Page_modo_upload extends Fsb_model
{
	public $page;
	public $per_page = 50;

	/*
	** Constructeur
	*/
	public function __construct()
	{
		$this->page = Http::request('page');
		if ($this->page < 1)
		{
			$this->page = 1;
		}

		if (Http::request('submit_delete', 'post'))
		{
			$this->delete_uploaded_files();
		}
		$this->show_uploaded_files();
	}

	/*
	** Affiche la liste des fichiers uploadés sur le forum
	*/
	public function show_uploaded_files()
	{
		// On récupère l'ordre et la direction d'affichage des fichiers
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

		// On compte le nombre de fichiers uploadés
		$sql = 'SELECT COUNT(*) AS total
				FROM ' . SQL_PREFIX . 'upload';
		$total = Fsb::$db->get($sql, 'total');
		$total_page = $total / $this->per_page;

		Fsb::$tpl->set_file('modo/modo_upload.html');
		Fsb::$tpl->set_vars(array(
			'PAGINATION' =>		($total_page > 1) ? Html::pagination($this->page, $total_page, ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;order=' . $order . '&amp;direction=' . $direction) : NULL,
	
			'U_ORDER_FILENAME' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;order=upload_realname&amp;direction=' . (($order == 'upload_realname' && $direction == 'ASC') ? 'DESC' : 'ASC')),
			'U_ORDER_FILESIZE' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;order=upload_filesize&amp;direction=' . (($order == 'upload_filesize' && $direction == 'ASC') ? 'DESC' : 'ASC')),
			'U_ORDER_FILETIME' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;order=upload_time&amp;direction=' . (($order == 'upload_time' && $direction == 'ASC') ? 'DESC' : 'ASC')),
			'U_ORDER_NICKNAME' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;order=u_nickname&amp;direction=' . (($order == 'u_nickname' && $direction == 'ASC') ? 'DESC' : 'ASC')),
			'U_ORDER_AUTH' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=upload&amp;order=upload_auth&amp;direction=' . (($order == 'upload_auth' && $direction == 'ASC') ? 'DESC' : 'ASC')),
		));

		// Liste des fichiers uploadés
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
			));
		}
		Fsb::$db->free($result);
	}

	/*
	** Suppression des fichiers uploadés
	*/
	public function delete_uploaded_files()
	{
		// Vérification des noms de fichiers
		$action = Http::request('action', 'post');
		$action = array_map('intval', $action);

		if (!$action)
		{
			return ;
		}

		// Vérification d'existance des fichiers
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
			Display::confirmation(Fsb::$session->lang('modo_upload_confirm_delete'), ROOT . 'index.' . PHPEXT . '?p=modo&module=upload', array('submit_delete' => TRUE, 'action' => $action));
		}
	}
}

/* EOF */