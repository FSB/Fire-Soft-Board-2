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
 * Gestion de la censure dans les messages
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
	 * Identifiant du mot censuré
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
			'submit' => ':query_add_edit_censor',
		));

		$call->functions(array(
			'mode' => array(
				'add' =>		'page_add_edit_censor',
				'edit' =>		'page_add_edit_censor',
				'delete' =>		'page_delete_censor',
				'default' =>	'page_default_censor',
			),
		));
	}

	/**
	 * Affiche la page de gestion de la censure
	 */
	public function page_default_censor()
	{
		Fsb::$tpl->set_switch('censor_list');
		Fsb::$tpl->set_vars(array(
			'U_ADD' =>				sid('index.' . PHPEXT . '?p=posts_censor&amp;mode=add')
		));

		// On recupere les censures
		$sql = 'SELECT censor_id, censor_word, censor_replace
				FROM ' . SQL_PREFIX . 'censor';
		$result = Fsb::$db->query($sql, 'censor_');
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('censor', array(
				'CENSOR_WORD' =>	htmlspecialchars($row['censor_word']),
				'CENSOR_REPLACE' =>	htmlspecialchars($row['censor_replace']),

				'U_EDIT' =>			sid('index.' . PHPEXT . '?p=posts_censor&amp;mode=edit&amp;id=' . $row['censor_id']),
				'U_DELETE' =>		sid('index.' . PHPEXT . '?p=posts_censor&amp;mode=delete&amp;id=' . $row['censor_id'])
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Affiche la page permettant d'ajouter / editer des mots a censurer
	 */
	public function page_add_edit_censor()
	{
		if ($this->mode == 'edit')
		{
			// Donnees de la censure selectionnee
			$sql = 'SELECT censor_word, censor_replace, censor_regexp
					FROM ' . SQL_PREFIX . 'censor
					WHERE censor_id = ' . $this->id;
			$result = Fsb::$db->query($sql);
			$data = Fsb::$db->row($result);
			Fsb::$db->free($result);

			$lg_add_edit = Fsb::$session->lang('adm_censor_edit');
			$cs_word = htmlspecialchars($data['censor_word']);
			$cs_replace = htmlspecialchars($data['censor_replace']);
			$cs_regexp = (bool)$data['censor_regexp'];
		}
		else
		{
			$lg_add_edit = Fsb::$session->lang('adm_censor_add');
			$cs_word = '';
			$cs_replace = '';
			$cs_regexp = false;
		}

		Fsb::$tpl->set_switch('censor_add');
		Fsb::$tpl->set_vars(array(
			'L_ADD_EDIT' =>			$lg_add_edit,

			'CENSOR_WORD' =>		$cs_word,
			'CENSOR_REPLACE' =>		$cs_replace,
			'CENSOR_REGEXP_YES' =>	($cs_regexp) ? 'checked="checked"' : '',
			'CENSOR_REGEXP_NO' =>	(!$cs_regexp) ? 'checked="checked"' : '',

			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=posts_censor&amp;mode=' . $this->mode . '&amp;id=' . $this->id)
		));
	}

	/**
	 * Valide le formulaire d'ajout / edition des mots a censurer
	 */
	public function query_add_edit_censor()
	{
		$cs_word =		trim(Http::request('cs_word', 'post'));
		$cs_replace =	Http::request('cs_replace', 'post');
		$cs_regexp =	Http::request('cs_regexp', 'post');
		$errstr = array();

		if (empty($cs_word))
		{
			$errstr[] = Fsb::$session->lang('fields_empty');
		}

		if ($errstr)
		{
			Display::message(Html::make_errstr($errstr));
		}

		if ($this->mode == 'add')
		{
			Fsb::$db->insert('censor', array(
				'censor_word' =>	$cs_word,
				'censor_replace' =>	$cs_replace,
				'censor_regexp' =>	$cs_regexp,
			));
		}
		else
		{
			Fsb::$db->update('censor', array(
				'censor_word' =>	$cs_word,
				'censor_replace' =>	$cs_replace,
				'censor_regexp' =>	$cs_regexp,
			), 'WHERE censor_id = ' . intval($this->id));
		}

		Fsb::$db->destroy_cache('censor_');
		Log::add(Log::ADMIN, 'censor_log_' . $this->mode);
		Display::message('adm_censor_well_' . $this->mode, 'index.' . PHPEXT . '?p=posts_censor', 'posts_censor');
	}

	/**
	 * Page de suppression d'un mot censure
	 */
	public function page_delete_censor()
	{
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