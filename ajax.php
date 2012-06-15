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
 * Gestions des diverses taches AJAX.
 * Si vous decidez d'utiliser AJAX dans un de vos mods, pointez vos requetes HTTP vers
 * cette page. Cette page est situee a la racine du forum pour eviter les conflits avec la
 * constante ROOT.
 */

define('PHPEXT', substr(strrchr(__FILE__,'.'), 1));
define('ROOT', './');
define('FORUM', true);
include(ROOT . 'main/start.' . PHPEXT);

// Gestion UTF-8 pour les serveurs qui font n'importe quoi
Http::header('Content-Type', 'text/html; charset=UTF-8');

// Pas de mise en cache
Http::no_cache();

Fsb::$session->start('', false);

// Creation des evenements AJAX
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

/**
 * Verification de l'email
 * 
 * @param string $email Adresse email a verifier
 * @return string
 */
function ajax_check_email($email)
{
	if ($email)
	{
		// Pas de verification DNS en AJAX, sinon la reponse est trop lente
		if (!User::email_valid($email, false))
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
	return (null);
}

/**
 * Verification du login
 * 
 * @param string $login Login a verifier
 * @return string
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
	return (null);
}

/**
 * Verification du pseudonyme
 * 
 * @param string $nickname Pseudonyme a verifier
 * @return string
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
		else if (($chars_error = User::nickname_valid($nickname)) !== true)
		{
			return ($chars_error);
		}
		else
		{
			return ('valid');
		}
	}
	return (null);
}

/**
 * Verification du mot de passe
 * 
 * @param string $password Mot de passe a verifier
 * @return string
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
	return (null);
}

/**
 * Edition rapide de message
 * 
 * @param int $id ID du message a editer
 * @return Message edite sous format XML
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
		return (null);
	}

	if (!(Fsb::$session->is_authorized($data['f_id'], 'ga_edit') && Fsb::$session->id() == $data['u_id'] && $data['t_status'] == UNLOCK && $data['f_status'] == UNLOCK || Fsb::$session->is_authorized($data['f_id'], 'ga_moderator')))
	{
		return (null);
	}

	if ($data['p_map'] != 'classic')
	{
		return (null);
	}

	$xml = new Xml;
	$xml->load_content($data['p_text']);
	$item = $xml->document->createElement('title');
	$item->setData($data['t_title']);
	$xml->document->appendChild($item);

	return ($xml->document->asValidXML());
}

/**
 * Soumission du formulaire d'edition rapide
 * 
 * @param int $id ID du message a mettre a jour
 * @return string Nouveau contenu du message sous format XML
 */
function ajax_submit_post($id)
{
	$sql = 'SELECT p.u_id, p.p_nickname, p.p_map, t.f_id, t.t_first_p_id, t.t_last_p_id, t.t_type, t.t_description, t.t_id, t.t_status, f.f_status, u.u_auth
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
		return (null);
	}

	if (!(Fsb::$session->is_authorized($data['f_id'], 'ga_edit') && Fsb::$session->id() == $data['u_id'] && $data['t_status'] == UNLOCK && $data['f_status'] == UNLOCK || Fsb::$session->is_authorized($data['f_id'], 'ga_moderator')))
	{
		return (null);
	}

	// Edition du message
	$_POST['post_map_description'] = str_replace('&#43;', '+', Http::request('post_map_description', 'post'));
	$content = Map::build_map_content('classic', false);

	// Titre du sujet
	$post_title = Send::truncate_title(str_replace('&#43;', '+', Http::request('t_title', 'post')));

	// Soumission du message
	Send::edit_post($id, $content, Fsb::$session->id(), array(
		'update_topic' =>	($data['t_first_p_id'] == $id) ? true : false,
		'is_last' =>		$data['t_last_p_id'] == $id,
		't_title' =>		$post_title,
		't_type' =>			$data['t_type'],
		't_description' =>	$data['t_description'],
		't_id' =>			$data['t_id'],
	));

	// Log de l'edition du message
	if (Fsb::$session->id() != $data['u_id'])
	{
		Log::add(Log::MODO, 'log_edit', $data['p_nickname']);
	}

	// On regarde si on peut parser les FSBcode et les images dans le texte
	$parser = new Parser();
	$parser->parse_html =		(Fsb::$cfg->get('activate_html') && $data['u_auth'] >= MODOSUP) ? true : false;

	// Informations passees au parseur de message
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
	$item->setData(Parser::title($post_title), false);
	$xml->document->appendChild($item);

	return ($xml->document->asValidXML());
}

/**
 * Affichage d'un message
 * 
 * @param int $id ID du message a afficher
 * @return string Contenu du message a afficher sous format XML
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
		return (null);
	}

	if (!Fsb::$session->is_authorized($data['f_id'], 'ga_moderator') && $data['u_id'] != Fsb::$session->id())
	{
		return (null);
	}

	// On regarde si on peut parser les FSBcode et les images dans le texte
	$parser = new Parser();
	$parser->parse_html =		(Fsb::$cfg->get('activate_html') && $data['u_auth'] >= MODOSUP) ? true : false;

	// Informations passees au parseur de message
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

/**
 * Citation du message
 * 
 * @param int $id ID du message a citer
 * @return string Message cite sous format XML
 */
function ajax_quote_post($id)
{
	$sql = 'SELECT p_id, p_time, p_text, p_nickname, p_map
			FROM ' . SQL_PREFIX . 'posts
			WHERE p_id = ' . $id;
	if (!$data = Fsb::$db->request($sql))
	{
		return (null);
	}

	// Recuperation des donnees de la MAP et du message
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
	$xml->load_file(MAPS_PATH . $data['p_map'] . '.xml', true);
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

	// On inhibe le ] du pseudonyme.
	$nickname = str_replace(']', '&#93;', htmlspecialchars($data['p_nickname']));

	$content = '[quote=' . $nickname . ',t=' . $data['p_time'] . ',id=' . $data['p_id'] . ']' . htmlspecialchars(trim($content)) . '[/quote]';
	if (Http::request('is_wysiwyg'))
	{
		$content = parser_wysiwyg::decode($content);
	}

	// Generation de l'affichage
	$xml = new Xml();
	$xml->document->setTagName('content');
	$xml->document->setData($content, false);

	return ($xml->document->asValidXML());
}

/**
 * Citation de message prive
 * 
 * @param int $id ID du message prive a citer
 * @return string Contenu du message prive sous format XML
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
		return (null);
	}
	Fsb::$db->free($result);

	// Recuperation des donnees de la MAP et du message
	$xml = new Xml();
	$xml->load_content($data['mp_content']);
	$content = $xml->document->line[0]->getData();
	unset($xml);

	$content = '[quote=' . htmlspecialchars($data['u_nickname']) . ',t=' . $data['mp_time'] . ']' . trim($content) . '[/quote]';
	if (Http::request('is_wysiwyg'))
	{
		$content = parser_wysiwyg::decode($content);
	}

	// Generation de l'affichage
	$xml = new Xml();
	$xml->document->setTagName('content');
	$xml->document->setData($content);

	return ($xml->document->asValidXML());
}

/**
 * Recherche de noms d'utilisateurs
 * 
 * @param string $nickname Partie du pseudonyme a rechercher
 * @return string Liste HTML des pseudonymes trouves
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
	return (null);
}

/**
 * Transforme du texte issu de l'editeur de texte en texte pour l'editeur WYSIWYG
 * 
 * @return string Texte affichable dans l'editeur WYSIWYG
 */
function ajax_editor_text()
{
	if (Fsb::$session->is_logged())
	{
		Fsb::$db->update('users', array(
			'u_activate_wysiwyg' =>		true,
		), 'WHERE u_id = ' . Fsb::$session->id());
	}

	$content = str_replace('&#43;', '+', Http::request('content', 'post'));
	return (Parser_wysiwyg::decode($content));
}

/**
 * Transforme du texte issu de l'editeur WYSIWYG en texte normal
 * 
 * @return string Texte avec FSBcodes
 */
function ajax_editor_wysiwyg()
{
	if (Fsb::$session->is_logged())
	{
		Fsb::$db->update('users', array(
			'u_activate_wysiwyg' =>		false,
		), 'WHERE u_id = ' . Fsb::$session->id());
	}

	$content = str_replace('&#43;', '+', Http::request('content', 'post'));
	return (Parser_wysiwyg::encode($content));
}

exit;

/* EOF */
