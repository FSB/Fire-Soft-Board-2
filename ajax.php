<?php
/*
** +---------------------------------------------------+
** | Name :		~/ajax.php
** | Begin :	26/09/2006
** | Last :		10/02/2008
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestions des diverses taches AJAX.
** Si vous décidez d'utiliser AJAX dans un de vos mods, pointez vos requètes HTTP vers
** cette page. Cette page est située à la racine du forum pour éviter les conflits avec la
** constante ROOT.
*/

define('PHPEXT', substr(strrchr(__FILE__,'.'), 1));
define('ROOT', './');
define('FORUM', TRUE);
include(ROOT . 'main/start.' . PHPEXT);

// Gestion UTF-8 pour les serveurs qui font n'importe quoi
Http::header('Content-Type', 'text/html; charset=UTF-8');

// Pas de mise en cache
Http::no_cache();

Fsb::$session->start('', FALSE);

// Création des évènements AJAX
$ajax = new Ajax();
$ajax->add_event(Ajax::TXT, 'check_email',		'ajax_check_email',		trim(Http::request('email', 'post')));
$ajax->add_event(Ajax::TXT, 'check_login',		'ajax_check_login',		trim(Http::request('login', 'post')));
$ajax->add_event(Ajax::TXT, 'check_nickname',	'ajax_check_nickname',	trim(Http::request('nickname', 'post')));
$ajax->add_event(Ajax::TXT, 'check_password',	'ajax_check_password',	Http::request('password', 'post'));
$ajax->add_event(Ajax::XML, 'edit_post',		'ajax_edit_post',		intval(Http::request('id')));
$ajax->add_event(Ajax::XML, 'submit_post',		'ajax_submit_post',		intval(Http::request('id')));
$ajax->add_event(Ajax::XML, 'show_post',		'ajax_show_post',		intval(Http::request('id')));
$ajax->add_event(Ajax::XML, 'quote_post',		'ajax_quote_post',		intval(Http::request('id')));
$ajax->add_event(Ajax::XML, 'quote_mp',			'ajax_quote_mp',		intval(Http::request('id')));
$ajax->add_event(Ajax::TXT, 'search_user',		'ajax_search_user',		trim(Http::request('nickname')));
$ajax->add_event(Ajax::TXT, 'editor_text',		'ajax_editor_text');
$ajax->add_event(Ajax::TXT, 'editor_wysiwyg',		'ajax_editor_wysiwyg');

$ajax->trigger(Http::request('mode'));

/*
** Vérification de l'email
*/
function ajax_check_email($email)
{
	if ($email)
	{
		// Pas de vérification DNS en AJAX, sinon la réponse est trop lente
		if (!User::email_valid($email, FALSE))
		{
			return ('invalid');
		}
		else if (User::email_exists($email))
		{
			return ('used');
		}
		else
		{
			return ('valid');
		}
	}
	return (NULL);
}

/*
** Vérification du login
*/
function ajax_check_login($login)
{
	if ($login)
	{
		if (User::login_exists($login))
		{
			return ('used');
		}
		else
		{
			return ('valid');
		}
	}
	return (NULL);
}

/*
** Vérification du pseudonyme
*/
function ajax_check_nickname($nickname)
{
	if ($nickname)
	{
		if (strlen($nickname) < 3)
		{
			return ('short');
		}
		else if (strlen($nickname) > 20)
		{
			return ('long');
		}
		else if (User::nickname_exists($nickname))
		{
			return ('used');
		}
		else if (($chars_error = User::nickname_valid($nickname)) !== TRUE)
		{
			return ($chars_error);
		}
		else
		{
			return ('valid');
		}
	}
	return (NULL);
}

/*
** Vérification du mot de passe
*/
function ajax_check_password($password)
{
	if ($password)
	{
		$pwd = new Password();
		$result = $pwd->grade($password);
		if ($result <= 1)
		{
			return ('weak');
		}
		else if ($result == 2)
		{
			return ('normal');
		}
		else
		{
			return ('strong');
		}
	}
	return (NULL);
}

/*
** Edition rapide de message
*/
function ajax_edit_post($id)
{
	$sql = 'SELECT p.p_text, p.u_id, p.p_map, t.f_id, t.t_title, t.t_status, f.f_status
			FROM ' . SQL_PREFIX . 'posts p
			LEFT JOIN ' . SQL_PREFIX . 'topics t
				ON p.t_id = t.t_id
			LEFT JOIN ' . SQL_PREFIX . 'forums f
				ON t.f_id = f.f_id
			WHERE p.p_id = ' . $id;
	if (!$data = Fsb::$db->request($sql))
	{
		return (NULL);
	}

	if (!(Fsb::$session->is_authorized($data['f_id'], 'ga_edit') && Fsb::$session->id() == $data['u_id'] && $data['t_status'] == UNLOCK && $data['f_status'] == UNLOCK || Fsb::$session->is_authorized($data['f_id'], 'ga_moderator')))
	{
		return (NULL);
	}

	if ($data['p_map'] != 'classic')
	{
		return (NULL);
	}

	$xml = new Xml;
	$xml->load_content($data['p_text']);
	$item = $xml->document->createElement('title');
	$item->setData($data['t_title']);
	$xml->document->appendChild($item);

	return ($xml->document->asValidXML());
}

/*
** Soumission du formulaire d'édition rapide
*/
function ajax_submit_post($id)
{
	$sql = 'SELECT p.u_id, p.p_nickname, p.p_map, t.f_id, t.t_first_p_id, t.t_type, t.t_description, t.t_id, t.t_status, f.f_status, u.u_auth
			FROM ' . SQL_PREFIX . 'posts p
			LEFT JOIN ' . SQL_PREFIX . 'topics t
				ON p.t_id = t.t_id
			LEFT JOIN '  . SQL_PREFIX . 'users u
				ON u.u_id = p.u_id
			LEFT JOIN ' . SQL_PREFIX . 'forums f
				ON t.f_id = f.f_id
			WHERE p.p_id = ' . $id;
	if (!$data = Fsb::$db->request($sql))
	{
		return (NULL);
	}

	if (!(Fsb::$session->is_authorized($data['f_id'], 'ga_edit') && Fsb::$session->id() == $data['u_id'] && $data['t_status'] == UNLOCK && $data['f_status'] == UNLOCK || Fsb::$session->is_authorized($data['f_id'], 'ga_moderator')))
	{
		return (NULL);
	}

	// Edition du message
	$_POST['post_map_description'] = utf8_encode(str_replace('&#43;', '+', Http::request('post_map_description', 'post')));
	$content = Map::build_map_content('classic', FALSE);
	$content = String::fsb_utf8_decode($content);

	// Titre du sujet
	$post_title = String::fsb_utf8_decode(utf8_encode(str_replace('&#43;', '+', Http::request('t_title', 'post'))));

	// Soumission du message
	Send::edit_post($id, $content, Fsb::$session->id(), array(
		'update_topic' =>	($data['t_first_p_id'] == $id) ? TRUE : FALSE,
		't_title' =>		$post_title,
		't_type' =>			$data['t_type'],
		't_description' =>	$data['t_description'],
		't_id' =>			$data['t_id'],
	));

	// Log de l'édition du message
	if (Fsb::$session->id() != $data['u_id'])
	{
		Log::add(Log::MODO, 'log_edit', $data['p_nickname']);
	}

	// On regarde si on peut parser les FSBcode et les images dans le texte
	$parser = new Parser();
	$parser->parse_html =		(Fsb::$cfg->get('activate_html') && $data['u_auth'] >= MODOSUP) ? TRUE : FALSE;

	// Informations passées au parseur de message
	$parser_info = array(
		'u_id' =>			$data['u_id'],
		'p_nickname' =>		$data['p_nickname'],
		'u_auth' =>			$data['u_auth'],
		'f_id' =>			$data['f_id'],
		't_id' =>			$data['t_id'],
	);

	// Parse et affichage du message
	$xml = new Xml();
	$xml->document->setTagName('root');

	$item = $xml->document->createElement('content');
	$item->setData($parser->mapped_message($content, 'classic', $parser_info));
	$xml->document->appendChild($item);

	$item = $xml->document->createElement('title');
	$item->setData(Parser::title($post_title), FALSE);
	$xml->document->appendChild($item);

	return ($xml->document->asValidXML());
}

/*
** Affichage basique du message après édition rapide
*/
function ajax_show_post($id)
{
	$sql = 'SELECT p.p_text, p.u_id, p.p_nickname, t.t_id, t.f_id, t.t_first_p_id, u.u_auth
			FROM ' . SQL_PREFIX . 'posts p
			LEFT JOIN ' . SQL_PREFIX . 'topics t
				ON p.t_id = t.t_id
			LEFT JOIN ' . SQL_PREFIX . 'users u
				ON u.u_id = p.u_id
			WHERE p.p_id = ' . $id;
	if (!$data = Fsb::$db->request($sql))
	{
		return (NULL);
	}

	if (!Fsb::$session->is_authorized($data['f_id'], 'ga_moderator') && $data['u_id'] != Fsb::$session->id())
	{
		return (NULL);
	}

	// On regarde si on peut parser les FSBcode et les images dans le texte
	$parser = new Parser();
	$parser->parse_html =		(Fsb::$cfg->get('activate_html') && $data['u_auth'] >= MODOSUP) ? TRUE : FALSE;

	// Informations passées au parseur de message
	$parser_info = array(
		'u_id' =>			$data['u_id'],
		'p_nickname' =>		$data['p_nickname'],
		'u_auth' =>			$data['u_auth'],
		'f_id' =>			$data['f_id'],
		't_id' =>			$data['t_id'],
	);

	// Parse et affichage du message
	$xml = new Xml();
	$xml->document->setTagName('root');

	$item = $xml->document->createElement('content');
	$item->setData($parser->mapped_message($data['p_text'], 'classic', $parser_info));
	$xml->document->appendChild($item);

	return ($xml->document->asValidXML());
}

/*
** Citation du message
*/
function ajax_quote_post($id)
{
	$sql = 'SELECT p_id, p_time, p_text, p_nickname, p_map
			FROM ' . SQL_PREFIX . 'posts
			WHERE p_id = ' . $id;
	if (!$data = Fsb::$db->request($sql))
	{
		return (NULL);
	}

	// Récupération des données de la MAP et du message
	$xml = new Xml();
	$xml->load_content($data['p_text']);
	$post_data = array();
	foreach ($xml->document->line AS $line)
	{
		$post_data[$line->getAttribute('name')] = $line->getData();
	}
	unset($xml);

	$content = '';
	$xml = new Xml();
	$xml->load_file(MAPS_PATH . $data['p_map'] . '.xml', TRUE);
	foreach ($xml->document->body[0]->line AS $line)
	{
		$value = $post_data[$line->getAttribute('name')];
		$type = $line->type[0]->getData();
		if ($type == 'multilist' || $type == 'checkbox')
		{
			$value = implode(($line->option[0]->childExists('separator')) ? $line->option[0]->separator[0]->getData() : ',', $value);
		}
		$content .= sprintf($line->result[0]->getData(), $value) . "\n";
	}
	unset($xml);

	$content = '[quote=' . htmlspecialchars($data['p_nickname']) . ',t=' . $data['p_time'] . ',id=' . $data['p_id'] . ']' . trim($content) . '[/quote]';
	if (Http::request('is_wysiwyg'))
	{
		$content = parser_wysiwyg::decode($content);
	}

	// Génération de l'affichage
	$xml = new Xml();
	$xml->document->setTagName('content');
	$xml->document->setData($content, FALSE);

	return ($xml->document->asValidXML());
}

/*
** Citation de message privé
*/
function ajax_quote_mp($id)
{
	$sql = 'SELECT mp.mp_content, mp.mp_time, u.u_nickname
			FROM ' . SQL_PREFIX . 'mp mp
			INNER JOIN ' . SQL_PREFIX . 'users u
				ON mp.mp_from = u.u_id
			WHERE mp.mp_id = ' . $id . '
				AND mp.mp_to = ' . Fsb::$session->id();
	$result = Fsb::$db->query($sql);
	$data = Fsb::$db->row($result);
	if (!$data)
	{
		return (NULL);
	}
	Fsb::$db->free($result);

	// Récupération des données de la MAP et du message
	$xml = new Xml();
	$xml->load_content($data['mp_content']);
	$content = $xml->document->line[0]->getData();
	unset($xml);

	$content = '[quote=' . htmlspecialchars($data['u_nickname']) . ',t=' . $data['mp_time'] . ']' . trim($content) . '[/quote]';
	if (Http::request('is_wysiwyg'))
	{
		$content = parser_wysiwyg::decode($content);
	}

	// Génération de l'affichage
	$xml = new Xml();
	$xml->document->setTagName('content');
	$xml->document->setData($content);

	return ($xml->document->asValidXML());
}

/*
** Recherche de noms d'utilisateurs
*/
function ajax_search_user($nickname)
{
	if ($nickname)
	{
		$sql = 'SELECT u_nickname
				FROM ' . SQL_PREFIX . 'users
				WHERE u_nickname LIKE \'' . Fsb::$db->escape($nickname) . '%\'
					AND u_id <> ' . VISITOR_ID;
		$result = Fsb::$db->query($sql);
		if ($row = Fsb::$db->row($result))
		{
			$str = '<select onchange="if (this.selectedIndex != 0) {$(\'' . Http::request('jsid') . '\').value = this.value;$(\'' . Http::request('jsid2') . '\').style.visibility=\'hidden\';}"><option value="0">-----</option>';
			do
			{
				$str .= '<option value="' . htmlspecialchars($row['u_nickname']) . '">' . htmlspecialchars($row['u_nickname']) . '</option>';
			}
			while ($row = Fsb::$db->row($result));
			$str .= '</select>';

			return ($str);
		}
	}
	return (NULL);
}

/*
** Transforme du texte issu de l'éditeur de texte en texte pour l'éditeur WYSIWYG
*/
function ajax_editor_text()
{
	if (Fsb::$session->is_logged())
	{
		Fsb::$db->update('users', array(
			'u_activate_wysiwyg' =>		TRUE,
		), 'WHERE u_id = ' . Fsb::$session->id());
	}

	$content = String::fsb_utf8_decode(utf8_encode(str_replace('&#43;', '+', Http::request('content', 'post'))));
	return (Parser_wysiwyg::decode($content));
}

/*
** Transforme du texte issu de l'éditeur WYSIWYG en texte normal
*/
function ajax_editor_wysiwyg()
{
	if (Fsb::$session->is_logged())
	{
		Fsb::$db->update('users', array(
			'u_activate_wysiwyg' =>		FALSE,
		), 'WHERE u_id = ' . Fsb::$session->id());
	}

	$content = String::fsb_utf8_decode(utf8_encode(str_replace('&#43;', '+', Http::request('content', 'post'))));
	return (Parser_wysiwyg::encode($content));
}

exit;

/* EOF */