<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/user/user_upload.php
** | Begin :	03/01/2006
** | Last :		11/09/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

// On affiche le module si le membre a le droit d'uploader des fichiers
if (Fsb::$mods->is_active('upload') && Fsb::$session->is_authorized('upload_file'))
{
	$show_this_module = TRUE;
}

/*
** Module d'utilisateur redirigeant vers son propre profil public
*/
class Page_user_upload extends Fsb_model
{
	/*
	** Constructeur
	*/
	public function __construct()
	{
		$mode = Http::request('mode');

		if ($mode == 'delete')
		{
			$this->delete_file();
		}
		else
		{
			$this->show_files();
		}
	}

	/*
	** Liste les fichiers joints du membre
	*/
	public function show_files()
	{
		// On récupère l'ordre et la direction d'affichage des fichiers
		$direction = strtoupper(Http::request('direction'));
		if ($direction !== 'ASC' && $direction !== 'DESC')
		{
			$direction = 'DESC';
		}

		$order = strtolower(Http::request('order'));
		if (!in_array($order, array('upload_filename', 'upload_filesize', 'upload_time', 'upload_total')))
		{
			$order = 'upload_time';
		}

		// On récupère le quota du membre
		$sql = 'SELECT SUM(upload_filesize) AS total_filesize
				FROM ' . SQL_PREFIX . 'upload
				WHERE u_id = ' . Fsb::$session->id();
		$result = Fsb::$db->query($sql);
		$row = Fsb::$db->row($result);
		Fsb::$db->free($result);
		$upload_quota = intval($row['total_filesize']);

		// Parse de variables de template
		Fsb::$tpl->set_file('user/user_upload.html');
		Fsb::$tpl->set_vars(array(
			'FORUM_UPLOAD_QUOTA' =>	(Fsb::$session->is_authorized('upload_quota_unlimited')) ? Fsb::$session->lang('unlimited') : convert_size(Fsb::$cfg->get('upload_quota')),
			'UPLOAD_QUOTA' =>		convert_size($upload_quota),
			'U_ORDER_FILENAME' =>	$this->check_order('upload_filename', $order, $direction),
			'U_ORDER_FILESIZE' =>	$this->check_order('upload_filesize', $order, $direction),
			'U_ORDER_FILETIME' =>	$this->check_order('upload_time', $order, $direction),
			'U_ORDER_TOTAL' =>		$this->check_order('upload_total', $order, $direction),
		));

		// On affiche les fichiers uploadés
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'upload
				WHERE u_id = ' . Fsb::$session->id() . '
				ORDER BY ' . $order . ' ' . $direction;
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('upload', array(
				'FILENAME' =>		htmlspecialchars($row['upload_realname']),
				'FILESIZE' =>		convert_size($row['upload_filesize']),
				'FILETIME' =>		Fsb::$session->print_date($row['upload_time']),
				'TOTAL' =>			$row['upload_total'],
				'DOWNLOAD' =>		sid(ROOT . 'index.' . PHPEXT . '?p=download&amp;id=' . $row['upload_id']),

				'U_DELETE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=upload&amp;mode=delete&amp;id=' . $row['upload_id']),
			));
		}
		Fsb::$db->free($result);
	}

	/*
	** Suppression d'un fichier
	*/
	public function delete_file()
	{
		$id = intval(Http::request('id'));

		// On vérifie si le fichier existe
		$sql = 'SELECT upload_filename
				FROM ' . SQL_PREFIX . 'upload
				WHERE upload_id = ' . $id . '
					AND u_id = ' . Fsb::$session->id();
		if (!$filename = Fsb::$db->get($sql, 'upload_filename'))
		{
			Display::message('user_download_not_exists');
		}

		// Boite de confirmation
		if (check_confirm())
		{
			if (file_exists(ROOT . 'upload/' . $filename) && is_file(ROOT . 'upload/' . $filename) && is_writable(ROOT . 'upload/'))
			{
				@unlink(ROOT . 'upload/' . $filename);
			}

			$sql = 'DELETE FROM ' . SQL_PREFIX . 'upload
					WHERE upload_id = ' . $id . '
						AND u_id = ' . Fsb::$session->id();
			Fsb::$db->query($sql);

			Display::message('user_upload_well_delete', ROOT . 'index.' . PHPEXT . '?p=profile&module=upload', 'forum_profil');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=profile&module=upload');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('user_upload_confirm_delete'), ROOT . 'index.' . PHPEXT . '?p=profile&module=upload', array('mode' => 'delete', 'id' => $id));
		}
	}

	/*
	** Retourne l'URL correcte pour trier selon un critère
	** -----
	** $name ::			Critère de tri
	** $order ::		Ordre actuel
	** $direction ::	Direction actuelle
	*/
	public function check_order($name, $order, $direction)
	{
		return (sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=upload&amp;order=' . $name . '&amp;direction=' . (($order == $name && $direction == 'ASC') ? 'DESC' : 'ASC')));
	}
}

/* EOF */