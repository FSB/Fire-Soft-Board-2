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
 * Gestion des affichages sur le forum (erreur, confirmation, etc.)
 *
 */
class Display extends Fsb_model
{
	/**
	 * Fonction de callback appelle par trigger_error()
	 *
	 * @param int $errno Code d'erreur
	 * @param string $errstr Contenu de l'erreur
	 * @param string $errfile Fichier d'erreur
	 * @param int $errline Ligne d'erreur
	 */
	public static function error_handler($errno, $errstr, $errfile, $errline)
	{
		if (!(error_reporting() & $errno))
		{
			return ;
		}

		switch ($errno)
		{
			case E_NOTICE :
				echo '<b>FSB Notice : <i>' . $errstr . '</i> in file <i>' . $errfile . '</i> (<i>' . $errline . '</i>)</b><br />';
			break;

			case E_WARNING :
				echo '<b>FSB Warning : <i>' . $errstr . '</i> in file <i>' . $errfile . '</i> (<i>' . $errline . '</i>)</b><br />';
			break;

			case FSB_ERROR :
				// Affichage de l'erreur fatale
				echo "<html><head><title>FSB2 :: Erreur</title><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" /></head><body>
				<style>pre { border: 1px #000000 dashed; background-color: #EEEEEE; padding: 10px; }</style>
				Une erreur a ete rencontree durant l'execution du script.";

				// Debugage possible ?
				if (Fsb::$debug->can_debug)
				{
					// Affichage plus precis de certaines erreurs
					if (preg_match('#^error_sql #i', $errstr) || preg_match('#^Call to undefined method #i', $errstr))
					{
						$sql_backtrace = debug_backtrace();
						if (isset($sql_backtrace[3]))
						{
							$index = 3;
							if (!isset($sql_backtrace[3]['line']) && isset($sql_backtrace[4]))
							{
								$index = 4;
							}
							$errline = $sql_backtrace[$index]['line'];
							$errfile = $sql_backtrace[$index]['file'];
						}
						unset($sql_backtrace);
					}

					echo " L'erreur rencontree est :<pre>" . $errstr . "</pre>
					a la ligne <i><b>$errline</b></i> du fichier <i><b>$errfile</b></i>";

					$fsb_path = (Fsb::$cfg) ? Fsb::$cfg->get('fsb_path') : './';
					if (file_exists(ROOT . fsb_basename($errfile, $fsb_path)))
					{
						echo "<br /><br />Voici la zone ou se situe l'erreur dans le script :";
						$content = file(ROOT . fsb_basename($errfile, Fsb::$cfg->get('fsb_path')));
						$count_content = count($content);
						$begin = ($errline < 7) ? 0 : $errline - 7;
						echo '<pre>';
						for ($i = $begin; $i < $count_content && $i <= ($begin + 14); $i++)
						{
							echo '<b>Ligne ' . $i . ' :</b> ' . htmlspecialchars($content[$i]);
						}
						echo '</pre>';
					}

					// Debugage avance via un trace des fonctions / methodes appelees
					echo '<br />Trace des fonctions / methodes appelees :<br /><pre>';
					$back = debug_backtrace();
					$count_back = count($back);
					for ($i = ($count_back - 1); $i > 0; $i--)
					{
						echo  ((isset($back[$i]['class'])) ? "<b>Methode :</b>\t" . $back[$i]['class'] . $back[$i]['type'] : "<b>Fonction :</b>\t") . $back[$i]['function'] . "()\n";
						echo (isset($back[$i]['file'])) ? "<b>Fichier :</b>\t" . fsb_basename($back[$i]['file'], $fsb_path) . "\n" : '';
						echo (isset($back[$i]['line'])) ? "<b>Ligne :</b>\t\t" . $back[$i]['line'] : '';
						if ($i > 1)
						{
							echo "\n\n\n";
						}
					}
					echo '</pre>';
				}
				else
				{
					echo "<br /><br /><b>Le mode DEBUG est desactive, veuillez contacter l'administrateur du forum.<br />
					DEBUG mode is disactivated, please contact the forum's administrator.</b>";
				}
				echo '</body></html>';

				if (defined('FSB_INSTALL'))
				{
					//Si il s'agit d'une erreur SQL, on log différement car ça peut etre long
					if(preg_match('#^error_sql #i', $errstr))
					{
						Log::add_custom(Log::ERROR, 'Erreur SQL', array($errstr), $errline, $errfile);
					}
					else
					{	
						Log::add_custom(Log::ERROR, $errstr, array(), $errline, $errfile);
					}
				}
				exit;
			break;

			case FSB_MESSAGE :
				// Message d'information
				Fsb::$tpl->set_file('error_handler.html');
				Fsb::$tpl->set_vars(array(
					'HANDLER_USE_FOOTER' =>	true,
					'CONTENT' =>			(Fsb::$session->lang($errstr)) ? Fsb::$session->lang($errstr) : $errstr,
				));

				$GLOBALS['_show_page_header_nav'] = true;
				$GLOBALS['_show_page_footer_nav'] = false;
				$GLOBALS['_show_page_stats'] = false;

				if (Fsb::$frame)
				{
					if (defined('FORUM'))
					{
						Fsb::$frame->frame_header();
					}
					Fsb::$frame->frame_footer();
				}

				exit;
			break;
		}
	}

	/**
	 * Affiche un message d'information, suivit potentiellement d'un message de redirection.
	 * 
	 * @param string $message Message a afficher
	 */
	public static function message($message)
	{
		$str = $message;
		$str_add = '';
		if (func_num_args() > 1)
		{
			$url = func_get_arg(1);
			$str_add = '';

			// Redirection apres le message d'erreur ?
			if (Fsb::$session->data['u_activate_redirection'] & 4)
			{
				Http::redirect(str_replace('&amp;', '&', $url), 0);
			}
			else if (Fsb::$session->data['u_activate_redirection'] & 8)
			{
				Http::redirect($url, 3);
				$str_add = '<br /><br />' . sprintf(Fsb::$session->lang('you_will_be_redirected'), 3);
			}
		}

		if (!defined('FSB_INSTALL'))
		{
			trigger_error($str, FSB_ERROR);
		}

		$content = '';
		for ($i = 1; $i < func_num_args(); $i += 2)
		{
			$arg1 = func_get_arg($i);
			$arg2 = func_get_arg($i + 1);
			$content .= return_to($arg1, $arg2);
		}

		// Affichage du message d'erreur
		trigger_error(((Fsb::$session->lang($str)) ? Fsb::$session->lang($str) : $str) . $content . $str_add, FSB_MESSAGE);
	}

	/**
	 * Affiche une boite de confirmation oui / non
	 *
	 * @param string $str Question de confirmation
	 * @param string $url URL de redirection de la confirmation
	 * @param array $hidden Tableau de champs HIDDEN a passer au formulaire
	 */
	public static function confirmation($str, $url, $hidden = array())
	{		
		Fsb::$tpl->set_file('confirmation.html');
		Fsb::$tpl->set_vars(array(
			'STR_CONFIRM' =>	$str,

			'U_ACTION' =>		sid($url),
		));

		// On ajoute dans le champs hidden une variable fsb_check_sid, qui contiendra la SID du membre
		// appelant cette page de confirmation. La reussite de la confirmation doit ensuite se faire
		// avec la fonction check_confirm(), qui verifiera si la SID est bonne. Le but etant de proteger
		// la confirmation en evitant a des scripts malicieux de forcer l'administrateur a confirmer
		// des actions automatiquements.
		$hidden['fsb_check_sid'] = Fsb::$session->sid;

		// On cree le code HTML des champs hidden	
		foreach ($hidden AS $name => $value)
		{
			if (is_array($value))
			{
				foreach ($value AS $subvalue)
				{
					Fsb::$tpl->set_blocks('hidden', array(
						'NAME' =>		$name . '[]',
						'VALUE' =>		$subvalue,
					));
				}
			}
			else
			{
				Fsb::$tpl->set_blocks('hidden', array(
					'NAME' =>		$name,
					'VALUE' =>		$value,
				));
			}
		}

		if (Fsb::$frame && (defined('FORUM') || defined('IN_ADM')))
		{
			Fsb::$frame->frame_footer();
		}
		exit;
	}

	/**
	 * Affiche un formulaire pour entrer les identifiants FTP
	 *
	 * @return array Informations entrees pour la connexion FTP
	 */
	public static function check_ftp()
	{
		// Si on a entre les identifiants dans la configuration
		if (Fsb::$cfg->get('ftp_default'))
		{
			return (array(
				'host' =>		Fsb::$cfg->get('ftp_host'),
				'login' =>		Fsb::$cfg->get('ftp_login'),
				'password' =>	Fsb::$cfg->get('ftp_password'),
				'port' =>		Fsb::$cfg->get('ftp_port'),
				'path' =>		Fsb::$cfg->get('ftp_path'),
			));
		}

		// Si les identifiants ont ete envoyes on les retourne
		if (Http::request('ftp_submit', 'post'))
		{
			$password = trim(Http::request('ftp_password', 'post'));
			$data = array(
				'host' =>		trim(Http::request('ftp_host', 'post')),
				'login' =>		trim(Http::request('ftp_login', 'post')),
				'path' =>		trim(Http::request('ftp_path', 'post')),
				'port' =>		intval(Http::request('ftp_port', 'post')),
			);

			// Si la case ftp_remind a ete cochee on garde l'hote, le login et le port en memoire. Pour des raisons de
			// securite on ne gardera pas le mot de passe en memoire
			if (Http::request('ftp_remind', 'post'))
			{
				Http::cookie('ftp', serialize($data), CURRENT_TIME + ONE_YEAR);
			}
			else
			{
				Http::cookie('ftp', '', CURRENT_TIME);
			}

			$data['password'] = $password;
			return ($data);
		}

		// Sinon on affiche le formulaire
		Fsb::$tpl->set_file('handler_ftp.html');

		// On met les anciennes valeurs de POST en champs hidden
		foreach ($_POST AS $key => $value)
		{
			if (is_array($value))
			{
				foreach ($value AS $subkey => $subvalue)
				{
					Fsb::$tpl->set_blocks('ftp_hidden', array(
						'NAME' =>	$key . '[' . $subkey . ']',
						'VALUE' =>	htmlspecialchars($subvalue),
					));
				}
			}
			else
			{
				Fsb::$tpl->set_blocks('ftp_hidden', array(
					'NAME' =>	$key,
					'VALUE' =>	htmlspecialchars($value),
				));
			}
		}

		$ftp_host = $ftp_login = '';
		$ftp_port = '21';
		$ftp_path = dirname($_SERVER['SCRIPT_NAME']);
		if (defined('IN_ADM'))
		{
			// Dans l'administration on supprime le repertoire admin/
			$ftp_path = dirname($ftp_path);
		}

		// Valeur gardees en memoires dans un cookie ?
		if ($cookie = Http::getcookie('ftp'))
		{
			$cookie = unserialize($cookie);
			if (is_array($cookie))
			{
				$ftp_host = $cookie['host'];
				$ftp_login = $cookie['login'];
				$ftp_port = $cookie['port'];
				$ftp_path = $cookie['path'];
			}
		}

		// Variables normales
		Fsb::$tpl->set_vars(array(
			'FTP_HOST' =>		$ftp_host,
			'FTP_LOGIN' =>		$ftp_login,
			'FTP_PORT' =>		$ftp_port,
			'FTP_PATH' =>		$ftp_path,
			'FTP_REMIND' =>		(is_array($cookie)) ? true : false,

			'U_ACTION' =>		sid(((defined('FORUM')) ? ROOT : '') . 'index.' . PHPEXT . '?' . htmlspecialchars($_SERVER['QUERY_STRING'])),
		));

		Fsb::$frame->frame_footer();
		exit;
	}

	/**
	 * Genere l'affichage des FSBcodes
	 *
	 * @param bool $in_sig true si on est dans l'edition de signature
	 * @param string $where Clause WHERE alternative
	 */
	public static function fsbcode($in_sig = false, $where = null)
	{
		if (is_null($where))
		{
			$where = 'WHERE fsbcode_activated' . (($in_sig) ? '_sig' : '') . ' = 1 AND fsbcode_menu = 1';
		}

		$sql = 'SELECT fsbcode_tag, fsbcode_img, fsbcode_description, fsbcode_list
				FROM ' . SQL_PREFIX . 'fsbcode
				' . $where . '
				ORDER BY fsbcode_order';
		$result = Fsb::$db->query($sql, 'fsbcode_');
		while ($row = Fsb::$db->row($result))
		{
			$list = trim($row['fsbcode_list']);
			$code = $row['fsbcode_tag'];

			// Si on empeche les colorateurs de syntaxe d'etre utilises, la liste CODE n'a plus
			// aucune sens donc on le converti en fsbcode normal
			if ($code == 'code' && !Fsb::$mods->is_active('highlight_code'))
			{
				$list = '';
			}

			// Upload activee ?
			if ($code == 'attach' && (!Fsb::$session->is_authorized('upload_file') || !Fsb::$mods->is_active('upload')))
			{
				continue ;
			}

			// Simple Fsbcode ...
			if (!$list)
			{
				Fsb::$tpl->set_blocks('fsbcode', array(
					'CODE' =>		$code,
					'IMG' =>		($row['fsbcode_img']) ? ROOT . 'tpl/' . Fsb::$session->data['u_tpl'] . '/img/fsbcode/' . $row['fsbcode_img'] : '',
					'TEXT' =>		($row['fsbcode_description']) ? htmlspecialchars($row['fsbcode_description']) : Fsb::$session->lang('fsbcode_' . $code),
				));
			}
			// ... ou liste ?
			else
			{
				Fsb::$tpl->set_blocks('fsbcode_list', array(
					'CODE' =>		$code,
					'TEXT' =>		($row['fsbcode_description']) ? htmlspecialchars($row['fsbcode_description']) : Fsb::$session->lang('fsbcode_text_' . $code),
				));
			
				Fsb::$tpl->set_blocks('fsbcode_list.item', array(
					'VALUE' =>		0,
					'LANG' =>		(Fsb::$session->lang('fsbcode_' . $code)) ? Fsb::$session->lang('fsbcode_' . $code) : $code,
				));

				$style = null;
				foreach (explode("\n", $list) AS $i => $line)
				{
					$line = trim($line);
					if ($i == 0 && preg_match('#^style=(.*?)$#i', $line, $m))
					{
						$style = $m[1];
					}
					else
					{
						Fsb::$tpl->set_blocks('fsbcode_list.item', array(
							'VALUE' =>		$line,
							'STYLE' =>		($style) ? sprintf('style="%s"', sprintf($style, $line)) : '',
							'LANG' =>		(Fsb::$session->lang('fsbcode_item_' . $code . '_' . $line)) ? Fsb::$session->lang('fsbcode_item_' . $code . '_' . $line) : $line,
						));
					}
				}
			}
		}
		Fsb::$db->free($result);
	}

	/**
	 * Genere l'affichage des smilies
	 */
	public static function smilies()
	{
		$sql = 'SELECT sc.*, s.*
					FROM ' . SQL_PREFIX . 'smilies_cat sc
					LEFT JOIN ' . SQL_PREFIX . 'smilies s
						ON sc.cat_id = s.smiley_cat
					ORDER BY sc.cat_order, s.smiley_order';
		$result = Fsb::$db->query($sql, 'smilies_');
		$last = null;
		while ($row = Fsb::$db->row($result))
		{
			if (is_null($last) || $row['cat_id'] != $last['cat_id'])
			{
				Fsb::$tpl->set_blocks('smiley_cat', array(
					'CAT_ID' =>		$row['cat_id'],
					'CAT_NAME' =>	htmlspecialchars($row['cat_name']),
				));
			}

			if (!is_null($row['smiley_id']))
			{
				Fsb::$tpl->set_blocks('smiley_cat.smiley', array(
					'URL' =>		substr(SMILEY_PATH, strlen(ROOT)) . addslashes($row['smiley_name']),
					'TEXT' =>		addslashes(htmlspecialchars($row['smiley_tag'])),
					'TAG' =>		addslashes(addslashes(htmlspecialchars($row['smiley_tag']))),
				));
			}
			$last = $row;
		}
		Fsb::$db->free($result);

		Fsb::$tpl->set_vars(array(
			'CFG_FSB_PATH' =>	addslashes(Fsb::$cfg->get('fsb_path')) . '/',
		));
	}

	/**
	 * Verifie si le membre a acces au forum protege par un mot de passe, et affiche ou non le formulaire
	 *
	 * @param int $f_id ID du forum
	 * @param string $password Mot de passe du forum
	 * @param string $action Action pour le formulaire
	 * @return bool Acces autorise ou non
	 */
	public static function forum_password($f_id, $password, $action)
	{
		if (!Fsb::$session->data['s_forum_access'] || !in_array($f_id, (array)explode(',', Fsb::$session->data['s_forum_access'])))
		{
			// Verification du mot de passe entre
			if (Http::request('submit_forum_password', 'post'))
			{
				$submited_password = trim(Http::request('forum_password', 'post'));
				if ($submited_password && (string)$submited_password === (string)$password)
				{
					// Mot de passe corect, mise a jour de la session
					Fsb::$db->update('sessions', array(
						's_forum_access' =>		Fsb::$session->data['s_forum_access'] . ((Fsb::$session->data['s_forum_access']) ? ',' : '') . $f_id,
					), 'WHERE s_sid = \'' . Fsb::$db->escape(Fsb::$session->sid) . '\'');

					return (true);
				}

				// Mot de passe incorect ...
				Fsb::$tpl->set_switch('bad_password');
			}

			// Formulaire de mot de passe
			Fsb::$tpl->set_file('display_password.html');
			Fsb::$tpl->set_vars(array(
				'U_ACTION' =>		sid($action),
			));

			return (false);
		}
		else
		{
			// Acces autorise
			return (true);
		}
	}

	/**
	 * Ajoute un systeme d'onglet sur une page de l'administration
	 *
	 * @param array $module_list Liste des onglets
	 * @param string $current_module Onglet selectionne
	 * @param string $url URL du lien
	 * @param string $prefix_lang Prefixe pour la clef de langue
	 */
	public static function header_module($module_list, $current_module, $url, $prefix_lang = '')
	{
		$width = floor(100 / count($module_list));
		foreach ($module_list AS $module)
		{
			Fsb::$tpl->set_blocks('module_header', array(
				'WIDTH' =>			$width,
				'SELECTED' =>		($module == $current_module) ? true : false,
				'URL' =>			sid($url . '&amp;module=' . $module),
				'NAME' =>			Fsb::$session->lang($prefix_lang . $module),
			));
		}
		Fsb::$tpl->set_switch('use_module_page');
	}

	/**
	 * Affiche le header de la messagerie prive
	 *
	 * @param string $box Boite courante
	 */
	public static function header_mp($box)
	{
		Fsb::$tpl->set_switch('show_mp_header');
		Fsb::$tpl->set_vars(array(
			'MENU_HEADER_TITLE' =>		Fsb::$session->lang('mp_panel'),
		));

		foreach ($GLOBALS['_list_box'] AS $box_name)
		{
			Fsb::$tpl->set_switch('show_menu_panel');
			Fsb::$tpl->set_blocks('module', array(
				'IS_SELECT' =>	($box == $box_name) ? true : false,
				'NAME' =>		Fsb::$session->lang('mp_box_' . $box_name),
				'URL' =>		sid(ROOT . 'index.' . PHPEXT . '?p=mp&amp;box=' . $box_name),
			));
		}
	}
}

/* EOF */