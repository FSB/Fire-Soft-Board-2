<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On affiche ce module si le membre a l'autorisation de creer des procedures
if (Fsb::$session->is_authorized('procedure'))
{
	$show_this_module = true;
}

/**
 * Module permettant la creation de procedures de moderation
 *
 */
class Page_modo_procedure extends Fsb_model
{
	public $mode;
	public $id;

	// Liste des fonctions de l'editeur
	public $fcts = array(
		'var' => array(
			'argv' =>	array(
				'varname' =>			array('select_text', 15, '', 20),
				'value' =>				array('select_text', 30),
				'input' =>				array('select_hidden', 'input()'),
				'field' =>				'select_field',
				'question' =>			array('select_text', 40, 'Raison :', 100),
				'default' =>			array('select_text', 30),
			),
		),
		'lock' => array(
			'argv' =>	array(
				'topicID' =>			array('select_text', 15, '{this.topic_id}', 15),
			),
		),
		'unlock' => array(
			'argv' =>	array(
				'topicID' =>			array('select_text', 15, '{this.topic_id}', 15),
			),
		),
		'move' => array(
			'argv' =>	array(
				'topicID' =>			array('select_text', 15, '{this.topic_id}', 15),
				'forumID' =>			array('select_forum_id', false),
				'trace' =>				'select_boolean',
			),
		),
		'delete_topic' => array(
			'argv' =>	array(
				'topicID' =>			array('select_text', 15, '{this.topic_id}', 20),
			),
		),
		'delete_post' => array(
			'argv' =>	array(
				'postID' =>				array('select_text', 15, '{this.last_post_id}', 20),
			),
		),
		'warn' => array(
			'argv' =>	array(
				'warnType' =>			'select_warn_mode',
				'warnUserID' =>			array('select_text', 30, '{this.user.u_id}'),
				'toID' =>				'select_mp_to',
				'reason' =>				array('select_textarea', 7, 60),
			),
		),
		'ban' => array(
			'argv' =>	array(
				'banType' =>			'select_ban_type',
				'banContent' =>			array('select_text', 35, '', 100),
				'reason' =>				array('select_text', 35, '', 200),
				'banLength' =>			'select_ban_length',
			),
		),
		'send_mp' => array(
			'argv' =>	array(
				'fromID' =>				array('select_text', 30, '{this.user.u_id}'),
				'toID' =>				'select_mp_to',
				'title' =>				array('select_text', 60),
				'content' =>			array('select_textarea', 10, 60),
			),
		),
		'send_post' => array(
			'argv' =>	array(
				'fromID' =>				array('select_text', 30, '{this.user.u_id}'),
				'topicID' =>			array('select_text', 15, '{this.topic_id}', 15),
				'content' =>			array('select_textarea', 10, 60),
			),
		),
		'redirect' => array(
			'argv' =>	array(
				'url' =>				array('select_url'),
			),
		),
		'watch_topic' => array(
			'argv' => array(
				'topicID' =>			array('select_text', 15, '{this.topic_id}', 15),
				'watch' =>				'select_boolean',
			),
		),
	);

	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		$this->mode =	Http::request('mode');
		$this->id =		intval(Http::request('id'));

		if (!Fsb::$session->is_authorized('procedure'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=index');
		}

		$call = new Call($this);
		$call->post(array(
			'submit_function' =>	'proc_new',
			'submit_fct_edit' =>	':submit_fct_edit',
			'submit_edit_full' =>	':submit_edit_full',
			'submit_proc_name' =>	':submit_procedure_name',
		));

		$call->functions(array(
			'mode' => array(
				'new' =>			'new_procedure',
				'edit' =>			'edit_procedure',
				'delete' =>			'delete_procedure',
				'proc_move' =>		'move_line',
				'proc_new' =>		'edit_function',
				'proc_edit' =>		'edit_function',
				'proc_delete' =>	'delete_line',
				'source' =>			'edit_source',
				'default' =>		'list_procedure',
			),
		));
	}

	/**
	 * Affiche la liste des procedures
	 *
	 */
	public function list_procedure()
	{
		Fsb::$tpl->set_file('modo/modo_procedure.html');
		Fsb::$tpl->set_vars(array(
			'U_ADD' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure&amp;mode=new'),
		));

		$sql = 'SELECT procedure_id, procedure_name
				FROM ' . SQL_PREFIX . 'sub_procedure
				ORDER BY procedure_name';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('procedure', array(
				'TITLE' =>			htmlspecialchars($row['procedure_name']),

				'U_EDIT' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure&amp;mode=edit&amp;id=' . $row['procedure_id']),
				'U_DELETE' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure&amp;mode=delete&amp;id=' . $row['procedure_id']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Ajout de procedure
	 *
	 * @param string $name
	 * @param int $auth
	 */
	public function new_procedure($name = '', $auth = MODOSUP)
	{
		// Droits sur la procedure
		$list_auth = array(
			USER =>			Fsb::$session->lang('modo_proc_owner_topic'),
			MODO =>			Fsb::$session->lang('modo'),
			MODOSUP =>		Fsb::$session->lang('modosup'),
			ADMIN =>		Fsb::$session->lang('admin'),
			FONDATOR =>		Fsb::$session->lang('fondator'),
		);

		Fsb::$tpl->set_file('modo/modo_procedure_edit.html');
		Fsb::$tpl->set_switch('proc_new');
		Fsb::$tpl->set_vars(array(
			'PROCEDURE_NAME' =>	htmlspecialchars($name),
			'LIST_AUTH' =>		Html::make_list('procedure_auth', $auth, $list_auth),

			'U_ACTION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure&amp;mode=' . $this->mode . '&amp;id=' . $this->id),
		));
	}

	/**
	 * Soumission de la modification des donnees de la procedure
	 *
	 */
	public function submit_procedure_name()
	{
		$name =	Http::request('procedure_name', 'post');
		$auth =	intval(Http::request('procedure_auth', 'post'));
		if ($this->mode == 'new')
		{
			Fsb::$db->insert('sub_procedure', array(
				'procedure_name' =>		$name,
				'procedure_auth' =>		$auth,
				'procedure_source' =>	'<procedure></procedure>',
			));
			$this->id = Fsb::$db->last_id();
		}
		else
		{
			Fsb::$db->update('sub_procedure', array(
				'procedure_name' =>		$name,
				'procedure_auth' =>		$auth,
			), 'WHERE procedure_id = ' . $this->id);
		}
		Fsb::$db->destroy_cache('procedure_');

		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=procedure&mode=edit&id=' . $this->id);
	}

	/**
	 * Editeur de procedure
	 *
	 */
	public function edit_procedure()
	{
		// Donnees de la procedure
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'sub_procedure
				WHERE procedure_id = ' . $this->id;
		$result = Fsb::$db->query($sql);
		$data = Fsb::$db->row($result);
		Fsb::$db->free($result);

		$this->new_procedure($data['procedure_name'], $data['procedure_auth']);

		Fsb::$tpl->set_switch('proc_source');
		Fsb::$tpl->set_vars(array(
			'U_ADD' =>			sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure&amp;mode=proc_new&amp;id=' . $this->id),
			'U_EDIT_FULL' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure&amp;mode=source&amp;id=' . $this->id),
		));

		// Parse XML de la source
		$xml = new Xml;
		$xml->load_content($data['procedure_source']);

		// On affiche la source ligne par ligne
		if ($xml->document->childExists('function'))
		{
			foreach ($xml->document->function AS $k => $line)
			{
				// Fonction utilisee
				$fct = $line->getAttribute('name');

				Fsb::$tpl->set_blocks('line', array(
					'CODE' =>		'<pre>' . htmlspecialchars($line->asXML()) . '</pre>',
					'EXPLAIN' =>	(Fsb::$session->lang('modo_proc_function_name_' . $fct)) ? Fsb::$session->lang('modo_proc_function_name_' . $fct) : '???',
					'U_EDIT' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure&amp;mode=proc_edit&amp;id=' . $this->id . '&amp;line=' . $k),
					'U_DELETE' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure&amp;mode=proc_delete&amp;id=' . $this->id . '&amp;line=' . $k),
					'U_UP' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure&amp;mode=proc_move&amp;id=' . $this->id . '&amp;line=' . $k . '&amp;move=up'),
					'U_DOWN' =>		sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure&amp;mode=proc_move&amp;id=' . $this->id . '&amp;line=' . $k . '&amp;move=down'),
				));
			}
		}
	}

	/**
	 * Supprime une procedure
	 *
	 */
	public function delete_procedure()
	{
		if (check_confirm())
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'sub_procedure
					WHERE procedure_id = ' . $this->id;
			Fsb::$db->query($sql);
			Fsb::$db->destroy_cache('procedure_');

			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=procedure');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=procedure');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('modo_proc_delete_confirm'), ROOT . 'index.' . PHPEXT . '?p=modo&module=procedure', array('mode' => $this->mode, 'id' => $this->id));
		}
	}

	/**
	 * Deplace une ligne de code
	 *
	 */
	public function move_line()
	{
		$nb =	intval(Http::request('line'));
		$move = (Http::request('move') == 'up') ? -1 : 1;

		// Donnees de la procedure
		$sql = 'SELECT procedure_source
				FROM ' . SQL_PREFIX . 'sub_procedure
				WHERE procedure_id = ' . $this->id;
		$source = Fsb::$db->get($sql, 'procedure_source');

		// Parse XML de la source, le deplacement de ligne pourra directement se faire avec la methode moveChild()
		$xml = new Xml;
		$xml->load_content($source);
		$xml->document->moveChild('function', $nb, $move);

		// Sauvegarde des changements
		Fsb::$db->update('sub_procedure', array(
			'procedure_source' =>		$xml->document->asXML(),
		), 'WHERE procedure_id = ' . $this->id);

		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=procedure&mode=edit&id=' . $this->id);
	}

	/**
	 * Ajout / edition de lignes
	 *
	 */
	public function edit_function()
	{
		$nb = intval(Http::request('line'));
		if ($this->mode == 'proc_edit')
		{
			// Donnees de la procedure
			$sql = 'SELECT procedure_source
					FROM ' . SQL_PREFIX . 'sub_procedure
					WHERE procedure_id = ' . $this->id;
			$source = Fsb::$db->get($sql, 'procedure_source');

			// Parse XML afin de recuperer la ligne editee
			$xml = new Xml;
			$xml->load_content($source);

			if (!isset($xml->document->function[$nb]))
			{
				Display::message('modo_proc_line_not_exists');
			}
			$line = &$xml->document->function[$nb];

			// Fonction utilisee
			$fct = $line->getAttribute('name');

			if (!isset($this->fcts[$fct]))
			{
				Display::message('modo_proc_function_not_exists2');
			}

			$i = 0;
			foreach ($this->fcts[$fct]['argv'] AS $argname => $descriptor)
			{
				if ($fct == 'var' && $argname == 'value' && $line->value[0]->hasChildren())
				{
					$edit['field'] =	($line->value[0]->function[0]->childExists('type')) ? $line->value[0]->function[0]->type[0]->getData() : 'text';
					$edit['question'] = ($line->value[0]->function[0]->childExists('explain')) ? $line->value[0]->function[0]->explain[0]->getData() : '';
					$edit['default'] =	($line->value[0]->function[0]->childExists('default')) ? $line->value[0]->function[0]->default[0]->getData() : '';
					continue ;
				}

				$edit[$argname] = ($line->childExists($argname) && !$line->{$argname}[0]->hasChildren()) ? $line->{$argname}[0]->getData() : ((isset($edit[$argname])) ? $edit[$argname] : '');
			}
			$list_functions = array();
		}
		else
		{
			$list_functions = array(0 => '-----');
			foreach ($this->fcts AS $name => $data)
			{
				$list_functions[$name] = Fsb::$session->lang('modo_proc_function_name_' . $name);
			}

			// Fonction utilisee
			$fct = Http::request('fct_used', 'post');

			Fsb::$tpl->set_switch('show_fcts');
		}

		Fsb::$tpl->set_file('modo/modo_procedure_edit.html');
		Fsb::$tpl->set_switch('proc_function');
		Fsb::$tpl->set_vars(array(
			'LIST_FUNCTIONS' =>			Html::make_list('fct_used', $fct, $list_functions),
			'HIDDEN' =>					Html::hidden('fct_hidden', $fct),

			'U_ACTION' =>				sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure&amp;mode=' . $this->mode . '&amp;id=' . $this->id . '&amp;line=' . $nb),
		));

		// Affichage des parametres de la fonction
		if ($fct)
		{
			if (!isset($this->fcts[$fct]))
			{
				Display::message('modo_proc_function_not_exists');
			}

			Fsb::$tpl->set_switch('show_explain_fct');
			Fsb::$tpl->set_vars(array(
				'EXPLAIN_FCT' =>	(Fsb::$session->lang('modo_proc_function_name_' . $fct)) ? Fsb::$session->lang('modo_proc_function_name_' . $fct) : '???',
			));

			foreach ($this->fcts[$fct]['argv'] AS $argname => $descriptor)
			{
				// Execution de la fonction d'output
				$fctname = 'method_';
				if (is_array($descriptor))
				{
					$fctname .= $descriptor[0];
					$args = $descriptor;
					array_shift($args);
					array_unshift($args, (isset($edit[$argname])) ? $edit[$argname] : '');
					array_unshift($args, $argname);
				}
				else
				{
					$fctname .= $descriptor;
					$args = array($argname, (isset($edit[$argname])) ? $edit[$argname] : '');
				}

				$buffer = '';
				if (method_exists($this, $fctname))
				{
					$buffer = call_user_func_array(array($this, $fctname), $args);
				}

				Fsb::$tpl->set_blocks('args', array(
					'TEXT' =>		Fsb::$session->lang('modo_proc_argv_' . $argname),
					'EXPLAIN' =>	(Fsb::$session->lang('modo_proc_argv_' . $argname . '_explain')) ? Fsb::$session->lang('modo_proc_argv_' . $argname . '_explain') : '',
					'BUFFER' =>		$buffer,
				));
			}
		}
	}

	/**
	 * Soumet les modifications liees a la fonction
	 *
	 */
	public function submit_fct_edit()
	{
		$fct =	Http::request('fct_hidden', 'post');
		$nb =	intval(Http::request('line'));
		if (!isset($this->fcts[$fct]))
		{
			Display::message('modo_proc_function_not_exists');
		}

		// Donnees de la procedure
		$sql = 'SELECT procedure_source
				FROM ' . SQL_PREFIX . 'sub_procedure
				WHERE procedure_id = ' . $this->id;
		$source = Fsb::$db->get($sql, 'procedure_source');

		// Parse XML de la source afin d'ajouter les nouveaux elements
		$xml = new Xml;
		$xml->load_content($source);

		$function = $xml->document->createElement('function');
		$function->setAttribute('name', $fct);
		foreach ($this->fcts[$fct]['argv'] AS $argname => $data)
		{
			// Exeption pour la declaration de variables
			if ($fct == 'var' && $argname == 'value')
			{
				if (Http::request('value'))
				{
					$parse_arg = Http::request('value');
				}
			}

			switch ($argname)
			{
				case 'toID' :
					switch (Http::request($argname, 'post'))
					{
						case 'owner' :
							$parse_arg = '{this.owner_id}';
						break;

						case 'last' :
							$parse_arg = '{this.last_poster_id}';
						break;

						default :
							$parse_arg = Http::request($argname . '_nickname', 'post');

							// On recupere l'ID du membre
							$sql = 'SELECT u_id
									FROM ' . SQL_PREFIX . 'users
									WHERE u_nickname = \'' . Fsb::$db->escape($parse_arg) . '\'
										AND u_id <> ' . VISITOR_ID;
							$get = Fsb::$db->get($sql, 'u_id');
							$parse_arg = ($get) ? $get : $parse_arg;
						break;
					}
				break;

				case 'url' :
					switch (Http::request($argname, 'post'))
					{
						case 'topic' :
							$parse_arg = ROOT . 'index.' . PHPEXT . '?p=topic&t_id={this.topic_id}';
						break;

						case 'forum' :
							$parse_arg = ROOT . 'index.' . PHPEXT . '?p=forum&f_id={this.forum_id}';
						break;

						case 'index' :
							$parse_arg = ROOT . 'index.' . PHPEXT;
						break;

						default :
							$parse_arg = Http::request($argname . '_url', 'post');
						break;
					}
				break;

				case 'trace' :
				case 'watch' :
					$parse_arg = (Http::request($argname, 'post')) ? 'true' : 'false';
				break;

				case 'banLength' :
					$parse_arg = Http::request($argname, 'post') * Http::request($argname . '_unit', 'post');
				break;

				default :
					$parse_arg = Http::request($argname, 'post');
				break;
			}

			$arg = $function->createElement($argname);

			if ($fct == 'var' && $argname == 'value' && !Http::request('value'))
			{
				$sub_function = $arg->createElement('function');
				$sub_function->setAttribute('name', 'input');

				$sub_function->appendXmlChild('<type><![CDATA[' . Http::request('field') . ']]></type>');
				$sub_function->appendXmlChild('<explain><![CDATA[' . Http::request('question') . ']]></explain>');
				$sub_function->appendXmlChild('<default><![CDATA[' . Http::request('default') . ']]></default>');

				$arg->appendChild($sub_function);
				$function->appendChild($arg);
				break;
			}
			else
			{
				$arg->setData($parse_arg);
			}

			$function->appendChild($arg);
		}

		// Calcul de la source
		if ($this->mode == 'proc_new')
		{
			$xml->document->appendChild($function);
		}
		else
		{
			if (!isset($xml->document->function[$nb]))
			{
				Display::message('modo_proc_function_not_exists');
			}
			$xml->document->function[$nb] = $function;
		}

		// Mise a jour de la ligne
		Fsb::$db->update('sub_procedure', array(
			'procedure_source' =>	$xml->document->asXML(),
		), 'WHERE procedure_id = ' . $this->id);

		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=procedure&mode=edit&id=' . $this->id);
	}

	/**
	 * Suppression d'une ligne
	 *
	 */
	public function delete_line()
	{
		$nb = intval(Http::request('line'));

		// Donnees de la procedure
		$sql = 'SELECT procedure_source
				FROM ' . SQL_PREFIX . 'sub_procedure
				WHERE procedure_id = ' . $this->id;
		$source = Fsb::$db->get($sql, 'procedure_source');

		$xml = new Xml;
		$xml->load_content($source);
		$xml->document->deleteChild('function', $nb);

		// Mise a jour de la ligne
		Fsb::$db->update('sub_procedure', array(
			'procedure_source' =>	$xml->document->asXML(),
		), 'WHERE procedure_id = ' . $this->id);

		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=procedure&mode=edit&id=' . $this->id);
	}

	/**
	 * Editeur de source
	 *
	 */
	public function edit_source()
	{
		// Donnees de la procedure
		$sql = 'SELECT procedure_source
				FROM ' . SQL_PREFIX . 'sub_procedure
				WHERE procedure_id = ' . $this->id;
		$source = Fsb::$db->get($sql, 'procedure_source');

		// On formate la source avec la methode asXML()
		$xml = new Xml;
		$xml->load_content($source);

		Fsb::$tpl->set_file('modo/modo_procedure_edit.html');
		Fsb::$tpl->set_switch('proc_source_edit');
		Fsb::$tpl->set_vars(array(
			'SOURCE' =>		htmlspecialchars($xml->document->asXML()),

			'U_ACTION' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=procedure&amp;mode=source&amp;id=' . $this->id),
		));
	}

	/**
	 * Soumet l'edition de la source
	 *
	 */
	public function submit_edit_full()
	{
		Fsb::$db->update('sub_procedure', array(
			'procedure_source' =>		Http::request('source', 'post'),
		), 'WHERE procedure_id = ' . $this->id);

		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=modo&module=procedure&mode=edit&id=' . $this->id);
	}

	//
	// Liste des methodes pour le buffer des arguments
	//

	/**
	 * Champ cache
	 *
	 * @param string $name
	 * @param unknown_type $s
	 * @param unknown_type $default
	 * @return string
	 */
	public function method_select_hidden($name, $s, $default)
	{
		return (Fsb::$session->lang('modo_proc_default_value') . ' : ' . $default . ' ' . Html::hidden($name, $default));
	}

	/**
	 * Input type text
	 *
	 * @param string $name
	 * @param unknown_type $s
	 * @param int $size
	 * @param unknown_type $default
	 * @param int $maxlength
	 * @return string
	 */
	public function method_select_text($name, $s, $size, $default = '', $maxlength = '60')
	{
		$default = ($s) ? $s : $default;
		return ('<input type="text" name="' . $name . '" maxlength="' . $maxlength . '" size="' . $size . '" value="' . $default . '" />');
	}

	/**
	 * Textarea
	 *
	 * @param string $name
	 * @param unknown_type $s
	 * @param int $rows
	 * @param int $cols
	 * @return string
	 */
	public function method_select_textarea($name, $s, $rows, $cols)
	{
		return ('<textarea name="' . $name . '" rows="' . $rows . '" cols="' . $cols . '">' . str_replace('[br]', "\n", $s) . '</textarea>');
	}

	/**
	 * Boolean oui / non
	 *
	 * @param string $name
	 * @param unknown_type $s
	 * @param unknown_type $default
	 * @return string
	 */
	public function method_select_boolean($name, $s, $default = 0)
	{
		$s = ($s) ? $s : $default;
		$html = '<input type="radio" name="' . $name . '" value="1" ' . (($s == 'true') ? 'checked="checked"' : '') . ' /> ' . Fsb::$session->lang('yes') . ' &nbsp; ';
		$html .= '<input type="radio" name="' . $name . '" value="0" ' . (($s != 'true') ? 'checked="checked"' : '') . ' /> ' . Fsb::$session->lang('no');
		return ($html);
	}

	/**
	 * Selection du destinataire pour le message prive
	 *
	 * @param string $name
	 * @param unknown_type $s
	 * @return string
	 */
	public function method_select_mp_to($name, $s)
	{
		$default = '';
		switch ($s)
		{
			case '{this.owner_id}' :
				$selected = 'owner';
			break;

			case '{this.last_poster_id}' :
				$selected = 'last';
			break;

			default :
				$selected = 'manual';
				$default = $s;
			break;
		}

		return (Html::make_list($name, $selected, array(
			'owner' =>		Fsb::$session->lang('modo_proc_to_owner'),
			'last' =>		Fsb::$session->lang('modo_proc_to_last'),
			'manual' =>		Fsb::$session->lang('modo_proc_to_manual'),
		)) . '<br /><br /><input type="text" name="' . $name . '_nickname" size="35" maxlength="20" value="' . $default . '" />');
	}

	/**
	 * Choix d'une URL
	 *
	 * @param string $name
	 * @param unknown_type $s
	 * @return string
	 */
	public function method_select_url($name, $s)
	{
		$default = '';
		switch ($s)
		{
			case ROOT . 'index.' . PHPEXT . '?p=topic&t_id={this.topic_id}' :
				$selected = 'topic';
			break;

			case ROOT . 'index.' . PHPEXT . '?p=forum&f_id={this.forum_id}' :
				$selected = 'forum';
			break;

			case ROOT . 'index.' . PHPEXT :
				$selected = 'index';
			break;

			default :
				$selected = 'manual';
				$default = $s;
			break;
		}

		return (Html::make_list($name, $selected, array(
			'topic' =>		Fsb::$session->lang('modo_proc_url_topic'),
			'forum' =>		Fsb::$session->lang('modo_proc_url_forum'),
			'index' =>		Fsb::$session->lang('modo_proc_url_index'),
			'manual' =>		Fsb::$session->lang('modo_proc_url_manual'),
		)) . '<br /><br /><input type="text" name="' . $name . '_url" size="50" maxlength="100" value="' . $default . '" />');
	}

	/**
	 * Selection du type de champ
	 *
	 * @param string $name
	 * @param unknown_type $s
	 * @return string
	 */
	public function method_select_field($name, $s)
	{
		return (Html::make_list($name, $s, array(
			'text' =>			Fsb::$session->lang('modo_proc_text'),
			'textarea' =>		Fsb::$session->lang('modo_proc_textarea'),
		)));
	}

	/**
	 * Selection d'un forum
	 *
	 * @param string $name
	 * @param unknown_type $s
	 * @param bool $choose_cat
	 * @return string
	 */
	public function method_select_forum_id($name, $s, $choose_cat = false)
	{
		return (Html::list_forums(get_forums(), $s, $name, $choose_cat));
	}

	/**
	 * Type de banissement
	 *
	 * @param string $name
	 * @param unknown_type $s
	 * @return string
	 */
	public function method_select_ban_type($name, $s)
	{
		return (Html::make_list($name, $s, array(
			'login' =>		Fsb::$session->lang('modo_proc_ban_login'),
			'email' =>		Fsb::$session->lang('modo_proc_ban_email'),
			'ip' =>			Fsb::$session->lang('modo_proc_ban_ip'),
		)));
	}

	/**
	 * Duree du banissement
	 *
	 * @param string $name
	 * @param int $s
	 * @return string
	 */
	public function method_select_ban_length($name, $s)
	{
		$html = '<input type="text" name="' . $name . '" size="10" maxlength="10" value="' . (($s) ? $s / ONE_HOUR : 0) . '" /> &nbsp; ';
		$html .= Html::make_list($name . '_unit', ONE_HOUR, array(
			ONE_HOUR =>		Fsb::$session->lang('hour'),
			ONE_DAY =>		Fsb::$session->lang('day'),
			ONE_WEEK =>		Fsb::$session->lang('week'),
			ONE_MONTH =>	Fsb::$session->lang('month'),
			ONE_YEAR =>		Fsb::$session->lang('year'),
		));
		return ($html);
	}

	/**
	 * Mode pour les avertissements
	 *
	 * @param string $name
	 * @param unknown_type $s
	 * @return string
	 */
	public function method_select_warn_mode($name, $s)
	{
		return (Html::make_list($name, $s, array(
			'more' =>		Fsb::$session->lang('modo_proc_warn_more'),
			'less' =>		Fsb::$session->lang('modo_proc_warn_less'),
		)));
	}
}

/* EOF */