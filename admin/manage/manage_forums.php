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
 * Page de gestion des categories / forums
 */
class Fsb_frame_child extends Fsb_admin_frame
{
	/**
	 * Identifiant du forum
	 *
	 * @var int
	 */
	public $id;
	
	/**
	 * Mode de la page
	 *
	 * @var string
	 */
	public $mode;

	/**
	 * Liste des forums
	 *
	 * @var array
	 */
	public $forums = array();
	
	/**
	 * Donnees envoyees par formulaire lors de la creation de forums
	 *
	 * @var array
	 */
	public $data = array();
	
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

		$this->forums = get_forums('', false);

		$call = new Call($this);
		$call->post(array(
			'submit_delete' =>			 'delete_all',
			'submit_lock' =>			 'lock_all',
			'submit_unlock' =>			 'unlock_all',
			'submit_add_c' =>			 'add_c',
			'submit_operation_move' =>	 ':operation_move',
            'submit_operation_delete' => ':operation_delete',
		));

		$call->functions(array(
			'mode' => array(
				'add_f' =>		'page_add_edit_forum',
				'edit_f' =>		'page_add_edit_forum',
				'moveup_f' =>	'page_move_forum',
				'movedown_f' =>	'page_move_forum',
				'delete_f' =>	'page_delete_forum',
				'delete_c' =>	'page_delete_forum',
				'add_c' =>		'page_add_edit_categorie',
				'edit_c' =>		'page_add_edit_categorie',
				'moveup_c' =>	'page_move_cat',
				'movedown_c' =>	'page_move_cat',
				'delete_all' =>	'page_delete_all',
				'lock_all' =>	'page_status_all',
				'unlock_all' =>	'page_status_all',
				'operation' =>	'page_operation',
				'default' =>	'page_default_forum',
			),
		));
	}

	/**
	 * Affiche la page par defaut de la gestion des forums
	 */
	public function page_default_forum()
	{
		Fsb::$tpl->set_switch('forums_management');
		Fsb::$tpl->set_vars(array(
			'U_ADD_FORUM' =>	sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=add_f'),
			'U_ADD_CAT' =>		sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=add_c'),
			'U_ACTION' =>		sid('index.' . PHPEXT . '?p=manage_forums'),
		));

		foreach ($this->forums AS $value)
		{
			if ($value['f_parent'] == 0)
			{
				Fsb::$tpl->set_switch('can_add_forum');
				$this->page_put_categories($value);
			}
			else
			{
				$this->page_put_forums($value);
			}
		}
	}

	/**
	 * Affiche la categorie
	 *
	 * @param array $cat Categorie a afficher
	 */
	public function page_put_categories(&$cat)
	{
		Fsb::$tpl->set_blocks('cat', array(
			'CAT_ID' =>			$cat['f_id'],
			'CAT_NAME' =>		$cat['f_name'],

			'U_CAT_EDIT' =>		sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=edit_c&amp;id=' . $cat['f_id']),
			'U_CAT_DELETE' =>	sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=delete_c&amp;id=' . $cat['f_id']),
			'U_CAT_UP' =>		sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=moveup_c&amp;id=' . $cat['f_id']),
			'U_CAT_DOWN' =>		sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=movedown_c&amp;id=' . $cat['f_id']),
			'U_ADD_FORUM' =>	sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=add_f&amp;default_cat=' . $cat['f_id']),
		));
	}

	/**
	 * Affiche recursivement les forums et ses sous forums,
	 * pour une categorie donnee.
	 *
	 * @param array $forum Forum a afficher
	 */
	public function page_put_forums(&$forum)
	{
		// Type de forum
		$forum_type = '';
		switch ($forum['f_type'])
		{
			case FORUM_TYPE_SUBCAT :
				$forum_type = '[' . Fsb::$session->lang('adm_forum_cat') . ']';
			break;

			case FORUM_TYPE_DIRECT_URL :
			case FORUM_TYPE_INDIRECT_URL :
				$forum_type = '[' . Fsb::$session->lang('adm_forum_url') . ']';
			break;
		}

		// Forum verrouille ?
		if ($forum['f_status'] == LOCK)
		{
			$forum_type .= '[' . Fsb::$session->lang('forum_locked') . ']';
		}

		Fsb::$tpl->set_blocks('cat.forum', array(
			'FORUM_ID' =>			$forum['f_id'],
			'FORUM_NAME' =>			$forum['f_name'],
			'FORUM_TYPE' =>			$forum_type,
			'WIDTH' =>				(20 * ($forum['f_level'] - 1)) + 10,
			'TOTAL_TOPIC' =>		sprintf(String::plural('adm_forum_total_topic', $forum['f_total_topic']), $forum['f_total_topic']),
			'TOTAL_POST' =>			sprintf(String::plural('adm_forum_total_post', $forum['f_total_post']), $forum['f_total_post']),

			'U_FORUM_ADD' =>		sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=add_f&amp;default_cat=' . $forum['f_id']),
			'U_FORUM_EDIT' =>		sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=edit_f&amp;id=' . $forum['f_id']),
			'U_FORUM_DELETE' =>		sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=delete_f&amp;id=' . $forum['f_id']),
			'U_FORUM_OPERATION' =>	sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=operation&amp;id=' . $forum['f_id']),
			'U_UP_FORUM' =>			sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=moveup_f&amp;id=' . $forum['f_id']),
			'U_DOWN_FORUM' =>		sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=movedown_f&amp;id=' . $forum['f_id']),
		));
	}

	/**
	 * Affiche la page permettant d'ajouter / editer des forums
	 */
	public function page_add_edit_forum()
	{
		if (Http::request('submit', 'post'))
		{
			$this->query_add_edit_forum();
			if (!count($this->errstr))
			{
				return ;
			}
		}

		// On ne peux ajouter de forum que si au moins une categorie existe
		if (!$this->forums)
		{
			Display::message('adm_f_no_cat');
		}

		$list_topic_type = array();
		foreach ($GLOBALS['_topic_type'] AS $type)
		{
			$list_topic_type[] = Fsb::$session->lang('topic_type_' . $type . 's');
		}

		if (count($this->errstr))
		{
			// Donnees recuperees en cas d'erreur dans l'envoie du formulaire
			Fsb::$tpl->set_switch('error');
			$this->data['f_prune_time'] = floor($this->data['f_prune_time'] / 3600);
			$this->data['f_parent'] = Http::request('f_parent');
			$default_topic_type = (strlen($this->data['f_prune_topic_type'])) ? explode(',', $this->data['f_prune_topic_type']) : array();
		}
		else if ($this->mode == 'edit_f')
		{
			// Donnees lors de l'edition
			$sql = 'SELECT *
					FROM ' . SQL_PREFIX . 'forums
					WHERE f_id = ' . $this->id;
			$this->data = Fsb::$db->request($sql);

			$this->data['f_prune_time'] =		floor($this->data['f_prune_time'] / 3600);
			$default_topic_type =				(strlen($this->data['f_prune_topic_type'])) ? explode(',', $this->data['f_prune_topic_type']) : array();
		}
		else
		{
			// Donnees par defaut
			$this->data['f_name'] =				'';
			$this->data['f_text'] =				'';
			$this->data['f_parent'] =			Http::request('default_cat');
			$this->data['f_status'] =			UNLOCK;
			$this->data['f_tpl'] =				'0';
			$this->data['f_type'] =				FORUM_TYPE_NORMAL;
			$this->data['f_prune_time'] =		'';
			$this->data['f_prune_topic_type'] = '';
			$this->data['f_location'] =			'';
			$this->data['f_password'] =			'';
			$this->data['f_global_announce'] =	true;
			$this->data['f_map_default'] =		'classic';
			$this->data['f_map_first_post'] =	MAP_FP_ONLY;
			$this->data['f_rules'] =			'';
			$this->data['f_approve'] =			IS_APPROVED;
			$this->data['f_display_moderators'] = true;
			$this->data['f_display_subforums'] = true;
			$this->data['f_color'] =			'';
			$default_topic_type =				array(count($list_topic_type) - 1);
		}

		// Style
		$style_type = $style_content = '';
		if ($getstyle = Html::get_style($this->data['f_color']))
		{
			list($style_type, $style_content) = $getstyle;
		}

		// Affichage des permissions par defaut
		$list_forum_auth = '';
		if ($this->mode == 'add_f')
		{
			Fsb::$tpl->set_switch('add_default_auth');
			$list_forum_auth = Html::list_forums($this->forums, $this->data['f_parent'], 'f_default_auth', false, '', false, '<option value="-1">' . Fsb::$session->lang('adm_forum_no_auth') . '</option>');
		}

		// Liste des temps pour la duree du delestage
		$list_prune_time = Html::make_list('f_prune_time_unit', ONE_HOUR, array(
			ONE_HOUR =>		Fsb::$session->lang('hour'),
			ONE_DAY =>		Fsb::$session->lang('day'),
			ONE_WEEK =>		Fsb::$session->lang('week'),
			ONE_MONTH =>	Fsb::$session->lang('month'),
			ONE_YEAR =>		Fsb::$session->lang('year'),
		));
		
		// Liste des types de sujets
		$list_prune_type = Html::make_list('f_prune_topic_type[]', $default_topic_type, $list_topic_type, array(
			'multiple' =>	'multiple',
			'size' =>		3,
		));

		// Liste des themes
		$list_tpl = Html::list_dir('f_tpl', $this->data['f_tpl'], ROOT . 'tpl/', array(), true, '<option value="0">' . Fsb::$session->lang('adm_forum_tpl_none') . '</option>');

		// Liste des MAPS
		$list_map = array('0' => Fsb::$session->lang('adm_forum_map_none')) + Map::get_list();
		$list_map = Html::make_list('f_map_default', $this->data['f_map_default'], $list_map);

		Fsb::$tpl->set_switch('forums_add');
		Fsb::$tpl->set_vars(array(
			'L_ADD_EDIT' =>				($this->mode == 'edit_f') ? Fsb::$session->lang('adm_edit_forum') : Fsb::$session->lang('adm_add_forum'),

			'FORUM_NAME' =>				htmlspecialchars($this->data['f_name']),
			'FORUM_DESC' =>				htmlspecialchars($this->data['f_text']),
			'FORUM_RULES' =>			htmlspecialchars($this->data['f_rules']),
			'FORUM_STATUS' =>			($this->data['f_status'] == LOCK) ? true : false,
			'FORUM_TYPE' =>				$this->data['f_type'],
			'FORUM_LOCATION' =>			htmlspecialchars($this->data['f_location']),
			'FORUM_PASSWORD' =>			htmlspecialchars($this->data['f_password']),
			'FORUM_GLOBAL_ANNOUNCE' =>	($this->data['f_global_announce']) ? true : false,
			'FORUM_APPROVE' =>			($this->data['f_approve']) ? true : false,
		    'FORUM_DISPLAY_MODERATORS' => ($this->data['f_display_moderators']) ? true : false,
		    'FORUM_DISPLAY_SUBFORUMS' => ($this->data['f_display_subforums']) ? true : false,
			'FORUM_PRUNE_TIME' =>		$this->data['f_prune_time'],
			'FORUM_MAP_FP_ONLY' =>		($this->data['f_map_first_post'] == MAP_FP_ONLY) ? true : false,
			'FORUM_MAP_ALL_POST' =>		($this->data['f_map_first_post'] == MAP_ALL_POST) ? true : false,
			'FORUM_MAP_FREE' =>			($this->data['f_map_first_post'] == MAP_FREE) ? true : false,
			'STYLE' =>					htmlspecialchars($style_content),
			'STYLE_TYPE_NONE' =>		(!$getstyle) ? 'checked="checked"' : '',
			'STYLE_TYPE_COLOR' =>		($style_type == 'style') ? 'checked="checked"' : '',
			'STYLE_TYPE_CLASS' =>		($style_type == 'class') ? 'checked="checked"' : '',
			'CONTENT' =>				Html::make_errstr($this->errstr),
			
			'LIST_PRUNE_TIME' =>		$list_prune_time,
			'LIST_PRUNE_TYPE' =>		$list_prune_type,
			'LIST_TPL' =>				$list_tpl,
			'LIST_MAP' =>				$list_map,
			'LIST_PARENT' =>			Html::list_forums($this->forums, $this->data['f_parent'], 'f_parent'),
			'LIST_FORUMS_AUTH' =>		$list_forum_auth,

			'U_ACTION' =>				sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=' . $this->mode . '&amp;id=' . $this->id),
		));

		if (!empty($errstr))
		{
			Fsb::$tpl->set_switch('errstr');
		}
	}

	/**
	 * Valide le formulaire d'ajout / edition de forums
	 */
	public function query_add_edit_forum()
	{
		$this->data['f_map_default'] =			Http::request('f_map_default', 'post');
		$this->data['f_location'] =				Http::request('f_location', 'post');
		$this->data['f_password'] =				Http::request('f_password', 'post');
		$this->data['f_tpl'] =					Http::request('f_tpl', 'post');
		$this->data['f_name'] =					trim(Http::request('f_name', 'post'));
		$this->data['f_text'] =					trim(Http::request('f_text', 'post'));
		$this->data['f_rules'] =				trim(Http::request('f_rules', 'post'));
		$this->data['f_prune_topic_type'] =		implode(',', (array) Http::request('f_prune_topic_type', 'post'));
		$this->data['f_prune_time'] =			intval(Http::request('f_prune_time', 'post')) * intval(Http::request('f_prune_time_unit', 'post'));
		$this->data['f_status'] =				intval(Http::request('f_status', 'post'));
		$this->data['f_type'] =					intval(Http::request('f_type', 'post'));
		$this->data['f_map_first_post'] =		intval(Http::request('f_map_first_post', 'post'));
		$this->data['f_global_announce'] =		intval(Http::request('f_global_announce', 'post'));
		$this->data['f_approve'] =				intval(Http::request('f_approve', 'post'));
		$this->data['f_display_moderators'] =	intval(Http::request('f_display_moderators', 'post'));
		$this->data['f_display_subforums'] =	intval(Http::request('f_display_subforums', 'post'));		
		$this->data['f_color'] =				Html::set_style(Http::request('f_style_type', 'post'), trim(Http::request('f_style', 'post')), 'class="forum"');
		$parent =								intval(Http::request('f_parent', 'post'));

		if (empty($this->data['f_name']))
		{
			$this->errstr[] = Fsb::$session->lang('adm_f_name_empty');
		}

		if ($this->mode == 'edit_f' && $parent == $this->id)
		{
			$this->errstr[] = Fsb::$session->lang('adm_cant_attach_same');
		}

		if (count($this->errstr))
		{
			return ;
		}

		if ($this->mode == 'edit_f')
		{
			// Mise a jour du forum
			Forum::update($this->id, $parent, $this->data);

			Log::add(Log::ADMIN, 'forum_log_edit', $this->data['f_name']);
			Display::message('adm_forum_well_edit', 'index.' . PHPEXT . '?p=manage_forums', 'manage_forums');
		}
		else
		{
			// Creation du forum
			$last_id = Forum::add($parent, $this->data);

			// Permissions par defaut ?
			if (($default_auth_id = intval(Http::request('f_default_auth', 'post'))) && $default_auth_id != -1)
			{
				Forum::set_default_auth($last_id, $default_auth_id);
				Sync::signal(Sync::SESSION);
			}

			Log::add(Log::ADMIN, 'forum_log_add', $this->data['f_name']);
			Display::message('adm_forum_well_add', 'index.' . PHPEXT . '?p=manage_auths&amp;mode=view&amp;this_id=' . $last_id . '&amp;mode_type=' . MODE_TYPE_SIMPLE, 'manage_forums_auths', 'index.' . PHPEXT . '?p=manage_forums', 'manage_forums');
		}
	}

	/**
	 * Deplace un forum avec un autre en prenant en compte le niveau sur lequel il
	 * est. (Le niveau etant ce a quoi il est attache).
	 */
	public function page_move_forum()
	{
		$move = ($this->mode == 'moveup_f') ? 'left' : 'right';
		$f_name = Forum::move($this->id, $move);

		Log::add(Log::ADMIN, 'forum_log_move_f', $f_name);
		Http::redirect('index.' . PHPEXT . '?p=manage_forums');
	}

	/**
	 * Page de suppression d'un forum / categorie
	 */
	public function page_delete_forum()
	{
		if (check_confirm())
		{
			$tmp = array_select($this->forums, 'f_id', $this->id);
			if ($tmp != null)
			{
				$f_name = $tmp['f_name'];
				Forum::delete($this->id);

				Log::add(Log::ADMIN, 'forum_log_' . $this->mode, $f_name);
				Display::message('adm_forum_well_' . $this->mode, 'index.' . PHPEXT . '?p=manage_forums', 'manage_forums');
			}
			else
			{
				Display::message('forum_not_exists');
			}
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=manage_forums');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('adm_forum_delete_confirm' . (($this->mode == 'delete_c') ? '_cat' : '')), 'index.' . PHPEXT . '?p=manage_forums', array('mode' => $this->mode, 'id' => $this->id));
		}
	}

	/**
	 * Affiche la page permettant d'ajouter / editer des categories
	 */
	public function page_add_edit_categorie()
	{
		if (Http::request('submit', 'post'))
		{
			$errstr = '';
			$this->query_add_edit_categorie($errstr);
			if (empty($errstr))
			{
				return ;
			}
		}

		if ($this->mode == 'edit_c')
		{
			$sql = 'SELECT f_name
					FROM ' . SQL_PREFIX . 'forums
					WHERE f_id = ' . $this->id;
			$result = Fsb::$db->query($sql);
			$cat = Fsb::$db->row($result);
			Fsb::$db->free($result);

			$lg_add_edit = Fsb::$session->lang('adm_edit_cat');
			$c_name = $cat['f_name'];
		}
		else
		{
			$lg_add_edit = Fsb::$session->lang('adm_add_cat');
			$c_name = '';
		}

		Fsb::$tpl->set_switch('forums_cat_add');
		Fsb::$tpl->set_vars(array(
			'L_ADD_EDIT' =>			$lg_add_edit,
			'CAT_NAME' =>			$c_name,
			'U_ACTION' =>			sid('index.' . PHPEXT . '?p=manage_forums&mode=' . $this->mode . '&id=' . $this->id),
		));
	}
	
	/**
	 * Valide le formulaire d'ajout / edition de forums
	 *
	 * @param array $errstr Chaine de caractere contenant les erreurs recensees durant
	 *				la validation du formulaire.
	 */
	public function query_add_edit_categorie(&$errstr)
	{
		$c_name = Http::request('c_name', 'post');

		if (empty($c_name))
		{
			$errstr .= Fsb::$session->lang('adm_c_name_empty') . '<br />';
		}

		if (!empty($errstr))
		{
			return ;
		}

		if ($this->mode == 'edit_c')
		{
			Fsb::$db->update('forums', array(
				'f_name' =>		$c_name,
			), 'WHERE f_id = ' . $this->id . ' AND f_parent = 0');
			Fsb::$db->destroy_cache('forums_');

			Log::add(Log::ADMIN, 'cat_log_edit', $c_name);
			Display::message('adm_cat_well_edit', 'index.' . PHPEXT . '?p=manage_forums', 'manage_forums');
		}
		else
		{
			Sql_interval::put(0, array(
				'f_name' =>		$c_name,
			));
			Fsb::$db->destroy_cache('forums_');

			Log::add(Log::ADMIN, 'cat_log_add', $c_name);
			Display::message('adm_cat_well_add', 'index.' . PHPEXT . '?p=manage_forums', 'manage_forums');
		}
	}

	/**
	 * Deplace une categorie avec une autre
	 */
	public function page_move_cat()
	{
		$move = ($this->mode == 'moveup_c') ? 'left' : 'right';
		$current = Forum::move($this->id, $move);

		Log::add(Log::ADMIN, 'forum_log_move_c', $current['f_name']);
		Http::redirect('index.' . PHPEXT . '?p=manage_forums');
	}

	/**
	 * Supprime tous les forums / categories coches
	 */
	public function page_delete_all()
	{
		$action = Http::request('action', 'post');
		if (check_confirm())
		{
			if (!is_array($action))
			{
				$action = array();
			}

			foreach ($action AS $id)
			{
				Forum::delete($id);
			}

			Log::add(Log::ADMIN, 'all_log_delete');
			Display::message('adm_all_well_delete', 'index.' . PHPEXT . '?p=manage_forums', 'manage_forums');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=manage_forums');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('adm_forum_delete_all_confirm'), 'index.' . PHPEXT . '?p=manage_forums', array('mode' => $this->mode, 'action' => $action));
		}
	}

	/**
	 * Change le status des forums / categories sellectionnees
	 */
	public function page_status_all()
	{
		$action = Http::request('action', 'post');
		if (!is_array($action))
		{
			$action = array();
		}

		if ($action)
		{
			// On recupere les ID des forums et des sous forums
			$ary = Sql_interval::get_childs($action);

			if ($ary)
			{
				Fsb::$db->update('forums', array(
					'f_status' =>	($this->mode == 'lock_all') ? LOCK : UNLOCK,
				), 'WHERE f_id IN(' . implode(',', array_unique($ary)) . ')');
			}
		}

		Log::add(Log::ADMIN, 'all_log_status');
		Display::message(Fsb::$session->lang('adm_well_' . $this->mode), 'index.' . PHPEXT . '?p=manage_forums', 'manage_forums');
	}

	/**
	 * Gestion d'operations sur les forums
	 */
	public function page_operation()
	{
		Fsb::$tpl->set_switch('forums_operation');
		Fsb::$tpl->set_vars(array(
			'LIST_FORUM_TARGET' =>		Html::list_forums($this->forums, $this->id, 'move_target', false),

			'U_ACTION' =>				sid('index.' . PHPEXT . '?p=manage_forums&amp;mode=operation&amp;id=' . $this->id),
		));
	}

	/**
	 * Deplacement de sujets vers un forum cible
	 */
	public function operation_move()
	{
		$to_id = intval(Http::request('move_target', 'post'));

		// Verification du forum
		$sql = 'SELECT f_id
				FROM ' . SQL_PREFIX . 'forums
				WHERE f_id = ' . $to_id . '
					AND f_id <> ' . $this->id . '
					AND f_parent <> 0';
		if (!Fsb::$db->request($sql))
		{
			Http::redirect('index.' . PHPEXT . '?p=manage_forums&mode=operation&id=' . $this->id);
		}

		if (check_confirm())
		{
			$tmp = array_select($this->forums, 'f_id', $this->id);
			if ($tmp != null && array_select($this->forums, 'f_id', $to_id))
			{
				$sql = 'SELECT t_id
						FROM ' . SQL_PREFIX . 'topics
						WHERE f_id = ' . $this->id;
				$result = Fsb::$db->query($sql);
				$idx = array();
				while ($row = Fsb::$db->row($result))
				{
					$idx[] = $row['t_id'];
				}
				Fsb::$db->free($result);

				Moderation::move_topics($idx, $this->id, $to_id, false);

				Log::add(Log::ADMIN, 'forum_log_operation_move', $tmp['f_name']);
				Display::message('adm_forum_operation_move_well', 'index.' . PHPEXT . '?p=manage_forums', 'manage_forums');
			}
			else
			{
				Display::message('forum_not_exists');
			}
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=manage_forums&mode=operation&id=' . $this->id);
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('adm_forum_confirm_operation_move'), 'index.' . PHPEXT . '?p=manage_forums', array('mode' => $this->mode, 'id' => $this->id, 'move_target' => $to_id, 'submit_operation_move' => true));
		}
	}
	/**
	 * Suppression de tous les sujets du forum courant
	 */
	public function operation_delete()
	{
		if (check_confirm())
		{   
			$tmp = array_select($this->forums, 'f_id', $this->id);
			if ($tmp != null)
			{
                $sql = 'SELECT t_id
                        FROM ' . SQL_PREFIX . 'topics
                        WHERE f_id = ' . $this->id;
                $result = Fsb::$db->query($sql);
                $idx = array();
                while ($row = Fsb::$db->row($result))
                {
                    $idx[] = $row['t_id'];
                }
                Fsb::$db->free($result);
                
                if(count($idx) > 0)
                {
                    Moderation::delete_topics('t_id IN (' . implode(', ', $idx) . ')'); 
                   
                    Log::add(Log::ADMIN, 'forum_log_operation_delete', $tmp['f_name']);
                    Display::message('adm_forum_operation_delete_well', 'index.' . PHPEXT . '?p=manage_forums', 'manage_forums');
                }
                
                Display::message('adm_forum_operation_delete_no_topics', 'index.' . PHPEXT . '?p=manage_forums', 'manage_forums');
			}
			else
			{
				Display::message('forum_not_exists');
			}
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect('index.' . PHPEXT . '?p=manage_forums&mode=operation&id=' . $this->id);
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('adm_forum_confirm_operation_delete'), 'index.' . PHPEXT . '?p=manage_forums', array('mode' => $this->mode, 'id' => $this->id, 'submit_operation_delete' => true));
		}
    }
}

/* EOF */