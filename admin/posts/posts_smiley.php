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
 * Gestion des smilies
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
	 * Identifiant du smilie
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
			'submit' =>				':query_add_edit_smiley',
			'submit_cat' =>			':query_add_edit_cat',
			'submit_pack' =>		':query_pack_smiley',
			'submit_pack_export' =>	':query_pack_export',
		));

		$call->functions(array(
			'mode' => array(
				'add' =>			'page_add_edit_smiley',
				'edit' =>			'page_add_edit_smiley',
				'add_cat' =>		'page_add_edit_cat',
				'edit_cat' =>		'page_add_edit_cat',
				'pack' =>			'page_pack_smiley',
				'pack_export' =>	'page_pack_export_smiley',
				'delete' =>			'page_delete_smiley',
				'delete_cat' =>		'page_delete_cat',
				'up' =>				'page_move_smiley',
				'down' =>			'page_move_smiley',
				'up_cat' =>			'page_move_cat',
				'down_cat' =>		'page_move_cat',
				'default' =>		'page_default_smiley',
			),
		));
	}

	/**
	 * Affiche la page de gestion des smileys
	 */
	public function page_default_smiley()
	{
		Fsb::$tpl->set_switch('smileys_list');
		Fsb::$tpl->set_vars(array(
			'U_PACK' =>			sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=pack'),
			'U_PACK_EXPORT' =>	sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=pack_export'),
			'U_ADD' =>			sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=add'),
			'U_CAT' =>			sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=add_cat'),
		));

		// On recupere les smileys et on les affiche
		$sql = 'SELECT sc.*, s.*
				FROM ' . SQL_PREFIX . 'smilies_cat sc
				LEFT JOIN ' . SQL_PREFIX . 'smilies s
					ON sc.cat_id = s.smiley_cat
				ORDER BY sc.cat_order, s.smiley_order';
		$result = Fsb::$db->query($sql, 'smilies_');
		$last = null;
		while ($row = Fsb::$db->row($result))
		{
			if (is_null($last) || $row['smiley_cat'] != $last)
			{
				Fsb::$tpl->set_blocks('smiley_cat', array(
					'CAT_NAME' =>		htmlspecialchars($row['cat_name']),

					'U_EDIT' =>			sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=edit_cat&amp;id=' . $row['cat_id']),
					'U_DELETE' =>		sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=delete_cat&amp;id=' . $row['cat_id']),
					'U_UP' =>			sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=up_cat&amp;id=' . $row['cat_id']),
					'U_DOWN' =>			sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=down_cat&amp;id=' . $row['cat_id']),
				));
				$last = $row['smiley_cat'];
			}

			if (!is_null($row['smiley_tag']))
			{
				Fsb::$tpl->set_blocks('smiley_cat.smiley', array(
					'SMILEY_TAG' =>		htmlspecialchars($row['smiley_tag']),
					'SMILEY_IMG' =>		SMILEY_PATH . $row['smiley_name'],
					'SMILEY_ID' =>		$row['smiley_id'],

					'U_EDIT' =>			sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=edit&amp;id=' . $row['smiley_id']),
					'U_DELETE' =>		sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=delete&amp;id=' . $row['smiley_id']),
					'U_UP' =>			sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=up&amp;id=' . $row['smiley_id'] . '#sid' . $row['smiley_id']),
					'U_DOWN' =>			sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=down&amp;id=' . $row['smiley_id'] . '#sid' . $row['smiley_id']),
				));
			}
		}
		Fsb::$db->free($result);
	}

	/**
	 * Affiche la page permettant d'ajouter / editer les smileys
	 */
	public function page_add_edit_smiley()
	{
		if ($this->mode == 'edit')
		{
			$sql = 'SELECT *
					FROM ' . SQL_PREFIX . 'smilies
					WHERE smiley_id = ' . intval($this->id);
			$result = Fsb::$db->query($sql);
			$data = Fsb::$db->row($result);
			Fsb::$db->free($result);

			$lg_add_edit = Fsb::$session->lang('adm_smiley_edit');
			$s_tag = htmlspecialchars($data['smiley_tag']);
			$s_img = $data['smiley_name'];
			$s_cat = $data['smiley_cat'];
		}
		else
		{
			$lg_add_edit = Fsb::$session->lang('adm_smiley_add');
			$s_tag = '';
			$s_img = '';
			$s_cat = '';
		}

		// Liste des categories de smileys
		$sql = 'SELECT cat_id, cat_name
				FROM ' . SQL_PREFIX . 'smilies_cat
				ORDER BY cat_order';
		$result = Fsb::$db->query($sql, 'smilies_');
		$list_cat = array();
		while ($row = Fsb::$db->row($result))
		{
			$list_cat[$row['cat_id']] = $row['cat_name'];
		}
		Fsb::$db->free($result);

		$list_smiley = Html::list_dir('s_img', $s_img, SMILEY_PATH, Upload::$img, false, '', 'id="select_smiley_image" onchange="show_smiley_image()"');

		Fsb::$tpl->set_switch('smileys_add');
		Fsb::$tpl->set_vars(array(
			'L_ADD_EDIT' =>				$lg_add_edit,
			'L_ADM_SMILEY_UPLOAD_EXP' =>sprintf(Fsb::$session->lang('adm_smiley_upload_exp'), SMILEY_PATH),
			'SMILEY_TAG' =>				$s_tag,
			'LIST_SMILEY' =>			$list_smiley,
			'LIST_CAT' =>				Html::make_list('smiley_cat', $s_cat, $list_cat),

			'U_ACTION' =>				sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=' . $this->mode . '&amp;id=' . $this->id)
		));
	}

	/**
	 * Valide le formulaire d'ajout / edition des smileys
	 */
	public function query_add_edit_smiley()
	{
		$s_tag =	Http::request('s_tag', 'post');
		$s_img =	Http::request('s_img', 'post');
		$s_cat =	Http::request('smiley_cat', 'post');
		$errstr = array();

		// On doit specifier un tag et une image ...
		if (empty($s_tag) || empty($s_tag))
		{
			$errstr[] = Fsb::$session->lang('fields_empty');
		}

		// ... ainsi qu'un tag non utilise bien sur
		$sql = 'SELECT smiley_id
				FROM ' . SQL_PREFIX . 'smilies
				WHERE smiley_tag = \'' . Fsb::$db->escape($s_tag) . '\'';
		$row = Fsb::$db->request($sql);

		if (($this->mode == 'add' && $row) || ($this->mode == 'edit' && $row && $row['smiley_id'] != $this->id))
		{
			$errstr[] = Fsb::$session->lang('adm_smiley_bad_tag');
		}

		// Erreur ?
		if ($errstr)
		{
			Display::message(Html::make_errstr($errstr));
		}

		if (!empty($_FILES['upload_smiley']['name']))
		{
			$upload = new Upload('upload_smiley');
			$upload->only_img();
			$s_img = $upload->store(SMILEY_PATH);
		}

		if ($this->mode == 'add')
		{
			// On calcule l'ordre maximal des smilies
			$sql = 'SELECT MAX(smiley_order) AS max
					FROM ' . SQL_PREFIX . 'smilies
					WHERE smiley_cat = ' . $s_cat;
			$max = Fsb::$db->get($sql, 'max');

			// Insertion d'un nouveau smiley
			Fsb::$db->insert('smilies', array(
				'smiley_tag' =>			$s_tag,
				'smiley_name' =>		$s_img,
				'smiley_order' =>		$max + 1,
				'smiley_cat' =>			$s_cat,
			));
			Fsb::$db->destroy_cache('smilies_');
		}
		else
		{
			// Donnees du smiley
			$sql = 'SELECT smiley_cat, smiley_order
					FROM ' . SQL_PREFIX . 'smilies
					WHERE smiley_id = ' . $this->id;
			$data = Fsb::$db->request($sql);

			if ($data['smiley_cat'] != $s_cat)
			{
				// On calcule l'ordre maximal des smilies
				$sql = 'SELECT MAX(smiley_order) AS max
						FROM ' . SQL_PREFIX . 'smilies
						WHERE smiley_cat = ' . $s_cat;
				$max = Fsb::$db->get($sql, 'max');
			}

			// Mise a jour su smiley
			Fsb::$db->update('smilies', array(
				'smiley_tag' =>			$s_tag,
				'smiley_name' =>		$s_img,
				'smiley_cat' =>			$s_cat,
				'smiley_order' =>		($data['smiley_cat'] != $s_cat) ? $max + 1 : $data['smiley_order'],
			), 'WHERE smiley_id = ' . $this->id);
			Fsb::$db->destroy_cache('smilies_');
		}

		Log::add(Log::ADMIN, 'smiley_log_' . $this->mode);
		Display::message('adm_smiley_well_' . $this->mode, 'index.' . PHPEXT . '?p=posts_smiley', 'posts_smiley');
	}

	/**
	 * Page de suppression d'un smiley
	 */
	public function page_delete_smiley()
	{
		if ($this->id > 0)
		{
			// On recupere l'ordre du smiley pour mettre a jour les ordres
			$sql = 'SELECT smiley_order
					FROM ' . SQL_PREFIX . 'smilies
					WHERE smiley_id = ' . $this->id;
			$smiley_order = Fsb::$db->get($sql, 'smiley_order');

			Fsb::$db->update('smilies', array(
				'smiley_order' =>	array('(smiley_order - 1)', 'is_field' => true),
			), 'WHERE smiley_order > ' . $smiley_order);

			// Suppression du smiley
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'smilies
						WHERE smiley_id = ' . $this->id;
			Fsb::$db->query($sql);
			Fsb::$db->destroy_cache('smilies_');
		}

		Log::add(Log::ADMIN, 'smiley_log_delete');
		Display::message('adm_smiley_well_delete', 'index.' . PHPEXT . '?p=posts_smiley', 'posts_smiley');
	}

	/**
	 * Deplace un smiley avec un autre
	 */
	public function page_move_smiley()
	{
		$move = ($this->mode == 'up') ? -1 : 1;

		// Position du smiley courant
		$sql = 'SELECT smiley_order, smiley_cat
				FROM ' . SQL_PREFIX . 'smilies
				WHERE smiley_id = ' . intval($this->id);
		$d = Fsb::$db->request($sql);

		if ($d)
		{
			// ID du smiley a switcher
			$sql = 'SELECT smiley_id
					FROM ' . SQL_PREFIX . 'smilies
					WHERE smiley_cat = ' . $d['smiley_cat'] . '
						AND smiley_order = ' . ($d['smiley_order'] + $move);
			$swap_smiley_id = Fsb::$db->get($sql, 'smiley_id');

			if ($swap_smiley_id)
			{
				// Mise a jour de la position des deux smilies
				Fsb::$db->update('smilies', array(
					'smiley_order' =>	($d['smiley_order'] + $move),
				), 'WHERE smiley_id = ' . intval($this->id));

				Fsb::$db->update('smilies', array(
					'smiley_order' =>	$d['smiley_order'],
				), 'WHERE smiley_id = ' . $swap_smiley_id);

				Fsb::$db->destroy_cache('smilies_');
			}
		}
		Http::redirect('index.' . PHPEXT . '?p=posts_smiley');
	}

	/**
	 * Affiche la page permettant d'ajouter / editer une categorie de smiley
	 */
	public function page_add_edit_cat()
	{
		// Edition
		$cat_name = '';
		if ($this->mode == 'edit_cat')
		{
			$sql = 'SELECT cat_name
					FROM ' . SQL_PREFIX . 'smilies_cat
					WHERE cat_id = ' . $this->id;
			$result = Fsb::$db->query($sql);
			$data = Fsb::$db->row($result);
			if (!$data)
			{
				$this->mode = 'add_cat';
			}
			else
			{
				Fsb::$db->free($result);
				$cat_name = $data['cat_name'];
			}
		}

		Fsb::$tpl->set_switch('smileys_add_cat');
		Fsb::$tpl->set_vars(array(
			'L_ADD_EDIT' =>		($this->mode == 'add_cat') ? Fsb::$session->lang('adm_smiley_add_cat') : Fsb::$session->lang('adm_smiley_edit_cat'),
			'CAT_NAME' =>		$cat_name,

			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=' . $this->mode . '&amp;id=' . $this->id),
		));
	}

	/**
	 * Soumission du formulaire d'ajout de categorie
	 */
	public function query_add_edit_cat()
	{
		$cat_name = trim(Http::request('cat_name', 'post'));
		if ($this->mode == 'add_cat')
		{
			$sql = 'SELECT MAX(cat_order) AS max_order
					FROM ' . SQL_PREFIX . 'smilies_cat';
			$max_order = Fsb::$db->get($sql, 'max_order');

			Fsb::$db->insert('smilies_cat', array(
				'cat_name' =>	$cat_name,
				'cat_order' =>	$max_order + 1,
			));
			Fsb::$db->destroy_cache('smilies_');

			Display::message('adm_smiley_well_cat_add', 'index.' . PHPEXT . '?p=posts_smiley', 'posts_smiley');
		}
		else
		{
			Fsb::$db->update('smilies_cat', array(
				'cat_name' =>	$cat_name,
			), 'WHERE cat_id = ' . $this->id);
			Fsb::$db->destroy_cache('smilies_');

			Display::message('adm_smiley_well_cat_edit', 'index.' . PHPEXT . '?p=posts_smiley', 'posts_smiley');
		}
	}

	/**
	 * Deplace une categorie de smiley
	 */
	public function page_move_cat()
	{
		$move = ($this->mode == 'up_cat') ? -1 : 1;

		// Position de la categorie
		$sql = 'SELECT cat_order
				FROM ' . SQL_PREFIX . 'smilies_cat
				WHERE cat_id = ' . intval($this->id);
		$current_cat_order = Fsb::$db->get($sql, 'cat_order');

		if ($current_cat_order)
		{
			// ID de la categorie a switcher
			$sql = 'SELECT cat_id
					FROM ' . SQL_PREFIX . 'smilies_cat
					WHERE cat_order = ' . ($current_cat_order + $move);
			$swap_cat_id = Fsb::$db->get($sql, 'cat_id');

			if ($swap_cat_id)
			{
				// Mise a jour de la position des deux smilies
				Fsb::$db->update('smilies_cat', array(
					'cat_order' =>	($current_cat_order + $move),
				), 'WHERE cat_id = ' . intval($this->id));

				Fsb::$db->update('smilies_cat', array(
					'cat_order' =>	$current_cat_order,
				), 'WHERE cat_id = ' . $swap_cat_id);

				Fsb::$db->destroy_cache('smilies_');
			}
		}
		Http::redirect('index.' . PHPEXT . '?p=posts_smiley');
	}

	/**
	 * Page de suppression d'un smiley
	 */
	public function page_delete_cat()
	{
		if (check_confirm())
		{
			if ($this->id > 0)
			{
				// On met a jour l'ordre des categories
				$sql = 'SELECT cat_order
						FROM ' . SQL_PREFIX . 'smilies_cat
						WHERE cat_id = ' . $this->id;
				$cat_order = Fsb::$db->get($sql, 'cat_order');
				if (!$cat_order)
				{
					Display::message('no_result');
				}

				Fsb::$db->update('smilies_cat', array(
					'cat_order' =>	array('(cat_order - 1)', 'is_field' => true),
				), 'WHERE cat_order > ' . $cat_order);

				// Suppression de la categorie
				$sql = 'DELETE FROM ' . SQL_PREFIX . 'smilies_cat
						WHERE cat_id = ' . $this->id;
				Fsb::$db->query($sql);

				$sql = 'DELETE FROM ' . SQL_PREFIX . 'smilies
						WHERE smiley_cat = ' . $this->id;
				Fsb::$db->query($sql);
				Fsb::$db->destroy_cache('smilies_');
			}

			Log::add(Log::ADMIN, 'log_smiley_delete_cat');
			Display::message('adm_smiley_well_cat_delete', 'index.' . PHPEXT . '?p=posts_smiley', 'posts_smiley');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=posts_smiley');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('adm_smiley_delete_cat_confirm'), 'index.' . PHPEXT . '?p=posts_smiley', array('mode' => $this->mode, 'id' => $this->id));
		}
	}

	/**
	 * Affiche la page permettant d'ajouter un pack de smileys
	 */
	public function page_pack_smiley()
	{
		Fsb::$tpl->set_switch('smileys_import');
		Fsb::$tpl->set_vars(array(
			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=posts_smiley&amp;mode=pack'),
		));
	}

	/**
	 * Valide le formulaire d'ajout de pack de smileys
	 */
	public function query_pack_smiley()
	{
		$errstr = array();

		$pack = '';
		if (!empty($_FILES['upload_pack_smiley']['name']))
		{
			$upload = new Upload('upload_pack_smiley');
			$upload->allow_ext(array('zip', 'tar', 'gz'));
			$pack = $upload->store(SMILEY_PATH);
			$filename = get_file_data($pack, 'filename');

			// Decompression du pack de smilies
			$compress = new Compress('images/smileys/' . $pack);
			$compress->extract('images/smileys/', $filename . '/');

			// On supprime le zip du pack de smiley
			@unlink(SMILEY_PATH . $pack);

			// On verifie la presence du fichier smiley.txt listant les smileys du pack
			if (file_exists(SMILEY_PATH . 'smiley.txt'))
			{
				// Chargement du fichier
				$lines = file(SMILEY_PATH . 'smiley.txt');
				
				// On recupere les tags deja utilises. Si le tag est deja utilise on renomme automatiquement le smiley
				$sql = 'SELECT smiley_tag 
						FROM ' . SQL_PREFIX . 'smilies';
				$result = Fsb::$db->query($sql);
				$tags = Fsb::$db->rows($result, 'assoc', 'smiley_tag');

				// On prepare les donnees pou la mise a jour de la bdd
				$cat_id = null;
				$max = 1;
				foreach ($lines as $line)
				{
					$line = trim($line);
					if ($line)
					{
						if (is_null($cat_id))
						{
							$sql = 'SELECT MAX(cat_order) AS max_order
									FROM ' . SQL_PREFIX . 'smilies_cat';
							$result = Fsb::$db->query($sql);
							$data = Fsb::$db->row($result);
							Fsb::$db->free($result);

							Fsb::$db->insert('smilies_cat', array(
								'cat_name' =>	$line,
								'cat_order' =>	$data['max_order'] + 1,
							));
							$cat_id = Fsb::$db->last_id();
						}
						else
						{
							list($smiley_img, $smiley_tag) = explode(',', $line);

							// On empeche les smileys d'avoir le meme tag
							while (isset($tags[$smiley_tag]))
							{
								$smiley_tag = ':' . substr(md5(rand(0, time())), 0, 10) . ':';
							}
							$tags[$smiley_tag] = $smiley_tag;

							Fsb::$db->insert('smilies', array(
								'smiley_tag' =>				$smiley_tag,
								'smiley_name' =>			$smiley_img,
								'smiley_order' =>			$max,
								'smiley_cat' =>				$cat_id,
							), 'INSERT', true);
							$max++;
						}
					}
				}
				Fsb::$db->query_multi_insert();
				Fsb::$db->destroy_cache('smilies_');

				// Maintenant que le fichier est traite, on le supprime
				@unlink(SMILEY_PATH . 'smiley.txt');
			}
			else
			{
				$errstr[] = Fsb::$session->lang('adm_smiley_file_dont_exist');
			}
		}
		else
		{
			$errstr[] = Fsb::$session->lang('adm_smiley_pack_dont_exist');
		}

		// Erreur ?
		if ($errstr)
		{
			Display::message(Html::make_errstr($errstr));
		}

		Log::add(Log::ADMIN, 'smiley_log_pack');

		Display::message('adm_smiley_well_pack', 'index.' . PHPEXT . '?p=posts_smiley', 'posts_smiley');
	}

	/**
	 * Affiche l'exportation de packs de smilies
	 */
	public function page_pack_export_smiley()
	{
		// Liste des categories de smilies
		$sql = 'SELECT cat_id, cat_name
				FROM ' . SQL_PREFIX . 'smilies_cat
				ORDER BY cat_order';
		$result = Fsb::$db->query($sql);
		$list_cat = array();
		while ($row = Fsb::$db->row($result))
		{
			$list_cat[$row['cat_id']] = $row['cat_name'];
		}
		Fsb::$db->free($result);

		Fsb::$tpl->set_switch('smileys_export');
		Fsb::$tpl->set_vars(array(
			'LIST_CAT' =>		Html::make_list('pack_cat', '', $list_cat),
		));
	}

	/**
	 * Exportation de smilies
	 */
	public function query_pack_export()
	{
		$pack_name =	trim(Http::request('pack_name', 'post'));
		$pack_cat =		intval(Http::request('pack_cat', 'post'));
		$pack_ext =		Http::request('pack_ext', 'post');

		if ($pack_cat)
		{
			// On verifie si le nom du pack ne contient que des caracteres [a-z0-9\-_]
			if (!preg_match('#^[a-z0-9\-_]+$#i', $pack_name))
			{
				Display::message('adm_smiley_pack_bad_name');
			}

			// Instance de la classe de compression
			$compress = new Compress('.' . $pack_ext);

			// On liste les smilies et on les ajoute dans l'archive
			$sql = 'SELECT s.smiley_name, s.smiley_tag, sc.cat_name
					FROM ' . SQL_PREFIX . 'smilies s
					LEFT JOIN ' . SQL_PREFIX . 'smilies_cat sc
						ON sc.cat_id = s.smiley_cat
					WHERE s.smiley_cat = ' . $pack_cat . '
					ORDER BY s.smiley_order';
			$result = Fsb::$db->query($sql);
			if ($row = Fsb::$db->row($result))
			{
				$content = $row['cat_name'] . "\n";
				do
				{
					$content .= $row['smiley_name'] . ',' . str_replace(',', '', $row['smiley_tag']) . "\n";
					$compress->add_file('images/smileys/' . $row['smiley_name'], 'images/smileys/', $pack_name . '/');
				}
				while ($row = Fsb::$db->row($result));
			}
			else
			{
				Display::message('adm_smiley_bad_cat');
			}
			Fsb::$db->free($result);

			// Ajout du fichier smiley.txt
			$compress->file->write('upload/smiley.txt', $content);
			$compress->add_file('upload/smiley.txt', 'upload/', $pack_name . '/');
			$compress->file->unlink('upload/smiley.txt');

			// On lance le telechargement
			Http::download($pack_name . '.' . $pack_ext, $compress->write(true));
		}
	}
}

/* EOF */