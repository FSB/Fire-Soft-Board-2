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
 * Parse et execute des procedures de moderation (decrites par du XML)
 */
class Procedure extends Fsb_model
{
	/**
	 * Numero de l'instruction en cours
	 *
	 * @var int
	 */
	private $line_number = 1;

	/**
	 * Variables definies
	 *
	 * @var array
	 */
	private $vars = array();

	/**
	 * Contient la liste des instructions avec les parametres obligatoires
	 *
	 * @var array
	 */
	private $fcts = array(
		'var' =>			array('varname', 'value'),
		'input' =>			array('explain', 'default'),
		'lock' =>			array('topicID' => 'intval'),
		'unlock' =>			array('topicID' => 'intval'),
		'move' =>			array('topicID' => 'intval', 'forumID' => 'intval', 'trace'),
		'delete_topic' =>	array('topicID' => 'intval'),
		'delete_post' =>	array('postID' => 'intval'),
		'warn' =>			array('warnType', 'warnUserID' => 'intval', 'toID' => 'intval', 'reason'),
		'ban' =>			array('banType', 'banContent', 'reason', 'banLength' => 'intval'),
		'send_mp' =>		array('fromID' => 'intval', 'toID' => 'intval', 'title', 'content'),
		'send_post' =>		array('fromID' => 'intval', 'topicID' => 'intval', 'content'),
		'redirect' =>		array('url'),
		'global' =>			array('varname'),
		'userdata' =>		array(),
		'watch_topic' =>	array('topicID' => 'intval', 'watch'),
	);

	/**
	 * Constructeur
	 */
	public function __construct()
	{
		$this->set_var('this', array());
	}

	/**
	 * Assigne une variable
	 *
	 * @param string $name Nom de la variable
	 * @param string $value Valeur de la variable
	 */
	public function set_var($name, $value = null)
	{
		$split = explode('.', $name);
		$count = count($split) - 1;
		$ref = &$this->vars;
		for ($i = 0; $i < $count; $i++)
		{
			if (!isset($ref[$split[$i]]))
			{
				$ref = array();
			}
			$ref = &$ref[$split[$i]];
		}
		$ref[$split[$i]] = $value;
	}

	/**
	 * Parse et execute une procedure de moderation
	 *
	 * @param string $code Code XML a parser
	 */
	public function parse($code)
	{
		// Chargement des variables
		if (isset($_POST['save']))
		{
			foreach ($_POST['save'] AS $k => $v)
			{
				$this->set_var($k, unserialize($v));
			}
		}

		// Parse XML de la procedure
		$xml = new Xml;
		$xml->load_content($code);

		foreach ($xml->document->function AS $line)
		{
			// Recuperation des arguments
			$argv = array();
			$function = $line->getAttribute('name');
			foreach ($line->children() AS $child)
			{
				$tagname = $child[0]->getTagName();
				if ($child[0]->hasChildren())
				{
					// Argument qui prendra le retour d'une fonction
					$sub_function = $child[0]->function[0]->getAttribute('name');
					$sub_argv = array();
					foreach ($child[0]->function[0]->children() AS $sub_child)
					{
						$sub_argv[$sub_child[0]->getTagName()] = $this->parse_vars($sub_child[0]->getData());
					}

					$argv[$tagname] = $this->call_method($sub_function, $sub_argv);
				}
				else
				{
					// Argument qui prendra une valeur
					$argv[$tagname] = $this->parse_vars($child[0]->getData());
				}
			}

			// Execution de la fonction
			$this->call_method($function, $argv);

			$this->line_number++;
		}
	}

	/**
	 * Remplace les variables dans la chaine de caractere
	 *
	 * @param string $str
	 * @return string
	 */
	private function parse_vars($str)
	{
		preg_match_all('#\{([a-zA-Z0-9_\.]+)\}#i', $str, $match);
		$count = count($match[0]);
		for ($i = 0; $i < $count; $i++)
		{
			$split = explode('.', $match[1][$i]);
			$var = &$this->vars;
			foreach ($split AS $varname)
			{
				if (!isset($var[$varname]))
				{
					$this->error('La variable ' . $match[1][$i] . ' n\'existe pas');
				}
				$var = &$var[$varname];
			}

			$str = str_replace($match[0][$i], $var, $str);
		}
		return ($str);
	}

	/**
	 * Affiche une erreur
	 *
	 * @param string $errstr
	 */
	private function error($errstr)
	{
		die('<b>FSB Fatal error : <i>' . $errstr . '</i> a la ligne ' . $this->line_number . ' de la procedure<br />');
	}

	/**
	 * Met les variables en champs caches
	 *
	 * @param array $node Liste de variables a sauver
	 * @param string $varname
	 */
	private function save_vars(&$node, $varname = '')
	{
		foreach ($node AS $k => $v)
		{
			if (is_array($v))
			{
				$this->save_vars($v, $varname . $k . '.');
			}
			else
			{
				Fsb::$tpl->set_blocks('hidden', array(
					'NAME' =>		'save[' . $varname . $k . ']',
					'VALUE' =>		htmlspecialchars(serialize($v)),
				));
			}
		}
	}

	/**
	 * Met les variables GP en champs caches
	 *
	 * @param array $var
	 * @param string $varname
	 */
	private function save_gp_vars(&$var, $varname = '')
	{
		foreach ($var AS $k => $v)
		{
			if (is_array($v))
			{
				$this->save_gp_vars($v, (!$varname) ? $k : $varname . '[' . $k . ']');
			}
			else
			{
				Fsb::$tpl->set_blocks('hidden', array(
					'NAME' =>		(!$varname) ? $k : $varname . '[' . $k . ']',
					'VALUE' =>		htmlspecialchars($v),
				));
			}
		}
	}

	/**
	 * Appel une methode des procedures de moderation
	 *
	 * @param string $method Nom de la methode
	 * @param array $argv Arguments
	 * @return mixed
	 */
	private function call_method($method, $argv)
	{
		// Existance de la methode
		$call = 'process_' . $method;
		if (!method_exists($this, $call) || !isset($this->fcts[$method]))
		{
			$this->error('La methode ' . $call . ' n\'existe pas');
		}

		// Verification des arguments
		foreach ($this->fcts[$method] AS $key => $value)
		{
			if (is_int($key))
			{
				if (!isset($argv[$value]))
				{
					$this->error('Il manque l\'argument ' . $value . ' pour la fonction ' . $method);
				}
			}
			else
			{
				if (!isset($argv[$key]))
				{
					$this->error('Il manque l\'argument ' . $key . ' pour la fonction ' . $method);
				}

				if (!function_exists($value))
				{
					$this->error('La fonction de filtre ' . $value . ' n\'existe pas');
				}
				$argv[$key] = $value($argv[$key]);
			}
		}

		return ($this->$call($argv));
	}

	/**
	 * Transforme un pseudonyme en ID
	 *
	 * @param string $str
	 * @return string
	 */
	private function nickname_to_id($str)
	{
		if (!is_numeric($str))
		{
			$sql = 'SELECT u_id
					FROM ' . SQL_PREFIX . 'users
					WHERE u_nickname = \'' . Fsb::$db->escape($str) . '\'';
			return (Fsb::$db->get($sql, 'u_id'));
		}
		return ($str);
	}

	//
	// Fonctions utilisables dans les procedures
	//

	/**
	 * Assigne une variable
	 *
	 * @param array $argv
	 */
	private function process_var($argv)
	{
		$this->set_var($argv['varname'], $argv['value']);
	}

	/**
	 * Affiche un formulaire et recupere la valeur de la variable
	 *
	 * @param array $argv
	 */
	private function process_input($argv)
	{
		$identifier = 'submit_process_input_' . $this->line_number;
		if (Http::request($identifier, 'post'))
		{
			$value = Http::request($identifier . '_value', 'post');
			return ($value);
		}
		else
		{
			Fsb::$tpl->set_file('handler_process.html');
			if ($argv['type'] == 'textarea')
			{
				Fsb::$tpl->set_switch('input_textarea');
			}

			Fsb::$tpl->set_vars(array(
				'PROCESS_TEXT' =>		$argv['explain'],
				'DEFAULT_VALUE' =>		(isset($argv['default'])) ? $argv['default'] : '',
				'INPUT_IDENTIFIER' =>	$identifier,
			));

			// On met les variables actuelles en champs caches
			$this->save_vars($this->vars);
			$this->save_gp_vars($_POST);
			$this->save_gp_vars($_GET);

			Fsb::$frame->frame_footer();
			exit;
		}
	}

	/**
	 * Verrouille un sujet
	 *
	 * @param array $argv
	 */
	private function process_lock($argv)
	{
		Moderation::lock_topic($argv['topicID'], LOCK);
	}

	/**
	 * Deverrouille un sujet
	 *
	 * @param array $argv
	 */
	private function process_unlock($argv)
	{
		Moderation::lock_topic($argv['topicID'], UNLOCK);
	}

	/**
	 * Envoie un message prive
	 *
	 * @param array $argv
	 */
	private function process_send_mp($argv)
	{
		$argv['fromID'] =	$this->nickname_to_id($argv['fromID']);
		$argv['toID'] =		$this->nickname_to_id($argv['toID']);

		Send::send_mp($argv['fromID'], $argv['toID'], $argv['title'], $argv['content']);
	}

	/**
	 * Redirige
	 *
	 * @param array $argv
	 */
	private function process_redirect($argv)
	{
		// On log l'action de moderation, car la procedure fini la
		Log::add(Log::MODO, 'log_procedure', $this->name);

		Http::redirect($argv['url']);
	}

	/**
	 * Ajoute un message au sujet
	 *
	 * @param array $argv
	 */
	private function process_send_post($argv)
	{
		// Donnees du sujet
		$sql = 'SELECT f_id, t_title
				FROM ' . SQL_PREFIX . 'topics
				WHERE t_id = ' . $argv['topicID'];
		$topic_data = Fsb::$db->request($sql);

		// Donnees du membre
		if (intval($argv['fromID']) != Fsb::$session->id())
		{
			$sql = 'SELECT u_nickname
					FROM ' . SQL_PREFIX . 'users
					WHERE u_id = ' . $argv['fromID'];
			$user_data = Fsb::$db->request($sql);
		}
		else
		{
			$user_data = Fsb::$session->data;
		}

		// Contenu en XML
		$message = new Xml();
		$message->document->setTagName('root');
		$message_line = $message->document->createElement('line');
		$message_line->setAttribute('name', 'description');
		$message_line->setData(htmlspecialchars($argv['content']));
		$message->document->appendChild($message_line);

		Send::send_post($argv['fromID'], $argv['topicID'], $topic_data['f_id'], $message->document->asValidXML(), $user_data['u_nickname'], IS_APPROVED, 'classic', array(
			't_title' =>	$topic_data['t_title'],
		), false);
	}

	/**
	 * Deplace un sujet
	 *
	 * @param array $argv
	 */
	private function process_move($argv)
	{
		// Donnees du sujet
		$sql = 'SELECT f_id
				FROM ' . SQL_PREFIX . 'topics
				WHERE t_id = ' . $argv['topicID'];
		$topic_data = Fsb::$db->request($sql);

		Moderation::move_topics($argv['topicID'], $topic_data['f_id'], $argv['forumID'], ($argv['trace'] == 'true') ? true : false);
	}

	/**
	 * Banissement
	 *
	 * @param array $argv
	 */
	private function process_ban($argv)
	{
		Moderation::ban($argv['banType'], $argv['banContent'], $argv['reason'], $argv['banLength'], false);
	}

	/**
	 * Donne un avertissement
	 *
	 * @param array $argv
	 */
	private function process_warn($argv)
	{
		$argv['warnType'] = ($argv['warnType'] != 'less') ? 'more' : $argv['warnType'];

		// Donnees du membre
		$sql = 'SELECT u_id, u_warn_post, u_warn_read, u_total_warning
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id = ' . $argv['toID'] . '
					AND u_id <> ' . VISITOR_ID;
		if ($to = Fsb::$db->request($sql))
		{
			if (($argv['warnType'] == 'more' && $to['u_total_warning'] < 5) || ($argv['warnType'] == 'less' && $to['u_total_warning'] > 0))
			{
				Moderation::warn_user($argv['warnType'], $argv['warnUserID'], $to['u_id'], $argv['reason'], $to['u_warn_post'], $to['u_warn_read'], array(
					'post_check' =>		false,
					'read_check' =>		false,
				));
			}
		}
	}

	/**
	 * Supprime un message
	 *
	 * @param array $argv
	 */
	private function process_delete_post($argv)
	{
		Moderation::delete_posts('p_id = ' . $argv['postID']);
	}

	/**
	 * Supprime un sujet
	 *
	 * @param array $argv
	 */
	private function process_delete_topic($argv)
	{
		Moderation::delete_topics('t_id = ' . $argv['topicID']);
	}

	/**
	 * Recupere les informations sur un utilisateur
	 *
	 * @param array $argv
	 */
	private function process_userdata($argv)
	{
		$where = '';
		if (isset($argv['userID']))
		{
			$where = 'WHERE u_id = ' . $argv['userID'];
		}
		else if (isset($argv['username']))
		{
			$where = 'WHERE u_nickname = \'' . Fsb::$db->escape($argv['username']) . '\'';
		}

		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'users
				' . $where;
		$result = Fsb::$db->query($sql);
		$return = Fsb::$db->row($result);
		Fsb::$db->free($result);

		if (!$return)
		{
			Display::message('user_not_exists');
		}

		return ((isset($argv['return'])) ? $return[$argv['return']] : $return);
	}

	/**
	 * Recupere le contenu d'une variable globale PHP
	 *
	 * @param array $argv
	 */
	private function process_global()
	{
		return ($GLOBALS[$argv['varname']]);
	}

	/**
	 * Surveille un sujet
	 *
	 * @param array $argv
	 */
	private function process_watch_topic($argv)
	{
		if ($argv['watch'] == 'true')
		{
			Fsb::$db->insert('topics_notification', array(
				't_id' =>		array($argv['topicID'], true),
				'u_id' =>		array(Fsb::$session->id(), true),
				'tn_status' =>	IS_NOT_NOTIFIED,
			), 'REPLACE');
		}
		else
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'topics_notification
					WHERE t_id = ' . $argv['topicID'] . '
						AND u_id = ' . Fsb::$session->id();
			Fsb::$db->query($sql);
		}
	}
}

/* EOF */