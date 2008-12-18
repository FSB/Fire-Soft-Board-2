<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On affiche le module si le membre a le droit d'uploader des fichiers
if (Fsb::$mods->is_active('upload') && Fsb::$session->is_authorized('upload_file'))
{
	/**
	 * On affiche le module ?
	 * 
	 * @var bool
	 */
	$show_this_module = true;
}

/**
 * Module d'utilisateur redirigeant vers son propre profil public
 */
class Page_user_upload extends Fsb_model
{
	/**
	 * Mode de la page
	 *
	 * @var string
	 */
	public $mode;
	
	/**
	 * Identifiant du fichier uploader
	 *
	 * @var unknown_type
	 */
	public $id;

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		$this->mode = Http::request('mode');
		$this->id = intval(Http::request('id'));

		$call = new Call($this);
		$call->module(array(
			'list' =>		array('tpl', 'extern', 'diff'),
			'url' =>		'index.' . PHPEXT . '?p=general_tpl',
			'lang' =>		'adm_tpl_',
			'default' =>	'tpl',
		));

		$call->post(array(
			'submit_edit' =>	':submit_edit_file',
		));

		$call->functions(array(
			'mode' => array(
				'edit' =>		'edit_file',
				'delete' =>		'delete_file',
				'default' =>	'show_files',
			),
		));
	}

	/**
	 * Liste les fichiers joints du membre
	 */
	public function show_files()
	{
		// On recupere l'ordre et la direction d'affichage des fichiers
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

		// On recupere le quota du membre
		$sql = 'SELECT SUM(upload_filesize) AS total_filesize
				FROM ' . SQL_PREFIX . 'upload
				WHERE u_id = ' . Fsb::$session->id();
		$data = Fsb::$db->request($sql);
		$upload_quota = intval($data['total_filesize']);

		// Parse de variables de template
		Fsb::$tpl->set_file('user/user_upload.html');
		Fsb::$tpl->set_switch('upload_index');
		Fsb::$tpl->set_vars(array(
			'FORUM_UPLOAD_QUOTA' =>	(Fsb::$session->is_authorized('upload_quota_unlimited')) ? Fsb::$session->lang('unlimited') : convert_size(Fsb::$cfg->get('upload_quota')),
			'UPLOAD_QUOTA' =>		convert_size($upload_quota),
			'U_ORDER_FILENAME' =>	$this->check_order('upload_filename', $order, $direction),
			'U_ORDER_FILESIZE' =>	$this->check_order('upload_filesize', $order, $direction),
			'U_ORDER_FILETIME' =>	$this->check_order('upload_time', $order, $direction),
			'U_ORDER_TOTAL' =>		$this->check_order('upload_total', $order, $direction),
		));

		// On affiche les fichiers uploades
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

				'U_EDIT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=upload&amp;mode=edit&amp;id=' . $row['upload_id']),
				'U_DELETE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=upload&amp;mode=delete&amp;id=' . $row['upload_id']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Edition d'un fichier
	 */
	public function edit_file()
	{
		Fsb::$tpl->set_file('user/user_upload.html');
		Fsb::$tpl->set_switch('upload_edit');

		$sql = 'SELECT upload_realname, upload_auth
				FROM ' . SQL_PREFIX . 'upload
				WHERE upload_id = ' . $this->id . '
					AND u_id = ' . Fsb::$session->id();
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

		Fsb::$tpl->set_vars(array(
			'UPLOAD_NAME' =>	htmlspecialchars($data['upload_realname']),
			'LIST_AUTH' =>		Html::make_list('upload_auth', $data['upload_auth'], $list_upload_auth),

			'U_ACTION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=upload&amp;mode=edit&amp;id=' . $this->id),
		));
	}

	/**
	 * Soumission de l'edition d'un fichier
	 */
	public function submit_edit_file()
	{
		$sql = 'SELECT upload_id
				FROM ' . SQL_PREFIX . 'upload
				WHERE upload_id = ' . $this->id . '
					AND u_id = ' . Fsb::$session->id();
		if (!Fsb::$db->request($sql))
		{
			Display::message('attached_file_not_exists');
		}

		$data = array(
			'upload_realname' =>	trim(Http::request('upload_realname', 'post')),
			'upload_auth' =>		intval(Http::request('upload_auth', 'post')),
		);

		Fsb::$db->update('upload', $data, 'WHERE upload_id = ' . $this->id);

		Display::message('user_upload_well_edit', ROOT . 'index.' . PHPEXT . '?p=profile&module=upload', 'forum_profil');
	}

	/**
	 * Suppression d'un fichier
	 */
	public function delete_file()
	{
		$id = intval(Http::request('id'));

		// On verifie si le fichier existe
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

	/**
	 * Retourne l'URL correcte pour trier selon un critere
	 *
	 * @param string $name Critere de tri
	 * @param string $order Ordre actuel
	 * @param string $direction Direction actuelle
	 * @return string URL
	 */
	public function check_order($name, $order, $direction)
	{
		return (sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=upload&amp;order=' . $name . '&amp;direction=' . (($order == $name && $direction == 'ASC') ? 'DESC' : 'ASC')));
	}
}

/* EOF */