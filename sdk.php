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
 * SDK : Software Developement Kit : Outil de developpement du logiciel
 *
 * Ce fichier contient le SDK de FSB2, c'est a dire une librairie de methodes permettant de
 * faire communiquer votre forum FSB2 avec d'autres applications PHP (votre site web par exemple).
 *
 * L'utilisation est faite pour etre simple : vous devez en premier lieu inclure ce fichier
 * le plus haut possible dans votre site web. C'est a dire que si vous voullez utiliser ce SDK
 * dans votre page web test.php vous devez inclure la librairie au tout debut du programme. En
 * aucun cas vous ne devez inclure ce fichier apres avoir ecrit du HTML (sauf si vous utilisez
 * une bufferisation de sortie).
 *
 * Pour inclure la librairie, procedez comme ceci :
 * <code>
 *		<?php
 *		define('ROOT', 'forum/');
 *		include(ROOT . 'sdk.php');
 * </code>
 *
 * Vous devez remplacer la valeur de la constante ROOT (forum/ par defaut) par le chemin de votre forum.
 * Si par exemple votre page test.php utilisant le SDK se trouve a la racine de votre site web, et que votre
 * forum se situe dans le dossier fsb2/ vous devez mettre :
 * <code>
 *		define('ROOT', 'fsb2/');
 * </code>
 *
 * Si le chemin entre est incorrect, le SDK ne fonctionnera pas. Veuillez noter que si vous voullez utiliser
 * le SDK au sein meme des fichiers du forum, il ne faut pas declarer la constante ROOT (qui existe deja).
 *
 * Une fois le SDK inclu, une variable $fsb est creee (instance de la classe Fsb_sdk). Vous pouvez ainsi
 * utiliser les differentes methodes du SDK. Vous avez aussi acces a l'ensemble de la librairie du forum,
 * c'est a dire aux differentes classes du dossier ~/main/class/ du forum. N'oubliez pas que vous pouvez
 * acceder aux globales principales du forum tel que la base de donnee, la session, la configuration de cette
 * facon : Fsb::$db ou Fsb::$cfg etc ..
 *
 * Voici une liste des principales fonctions (pour faciliter la recherche) :
 *	nickname2id()				Converti un pseudonyme en ID
 *	id2nickname()				Converti une ID en pseudonyme
 *	forumname2id()				Converti un nom de forum en ID
 *	userdata()					Retourne des informations sur le visiteur actuel
 *	is_logged()					Verifie si le membre est connecte
 *	login()						Connexion d'un membre
 *	logout()					Deconnexion d'un membre
 *	nickname()					Retourne le pseudonyme du visiteur courant avec un lien vers son profile et sa couleur
 *	last_user()					Informations sur le dernier membre inscrit
 *	get_users()					Recuperation d'une liste de membres.
 *	get_best_posters()			Recuperation des membres avec le plus de messages
 *	get_worst_posters()			Recuperation des membres avec le moins de messages
 *
 *	get_topics()				Recuperation d'une liste de sujets
 *	get_most_viewed_topics()	Recuperation des sujets les plus lus
 *	get_less_viewed_topics()	Recuperation des sujets les moins lus
 *	get_most_posted_topics()	Recuperation des sujets avec le plus de reponses
 *	get_less_posted_topics()	Recuperation des sujets avec le moins de reponses
 *	get_posts()					Recuperation d'une liste de messages
 *
 *  post_topic()				Creation d'un nouveau sujet
 *  post_reply()				Reponse a un sujet
 *  post_mp()					Envoie un message prive
 *	post_poll()					Creation d'un sondage
 *
 *	who_is_online()				Tableau d'information sur les membres en ligne
 *	who_was_online_today()		Tableau d'information sur les membres qui etaient en ligne aujourd'hui
 *	who_has_birthday_today()	Tableau d'information sur les membres fetant leur anniversaire aujourd'hui
 *
 *	get_calendar_events()		Tableau d'information sur les evenements a venir
 *
 *	get_poll()					Recupere les informations sur un sondage
 *	get_last_poll()				Recupere le dernier sondage cree
 *	get_random_poll()			Recupere un sondage aleatoire
 *	submit_poll()				Soumet le vote
 *
 *	url_topic()					URL vers un sujet
 *	url_post()					URL vers un message
 *	url_forum()					URL vers un forum
 *	url_user()					URL vers un profile
 *	url_index()					URL vers l'index
 *
 *	template_path()				Modifie le chemin du theme
 *
 *	captcha_valid()				Verifie si la chaine passee en parametre est un code Captcha valide
 *	captcha_img()				Retourne une URL pour l'image du Captcha
 *	captcha_output()			Genere l'image du Captcha
 *
 *	rsa_create()				Inialisation du chiffrage RSA, a appeler au tout debut
 *	rsa_encrypt()				Generation du Javascript pour crypter les champs du formulaire
 *	rsa_decrypt()				Decrypte les champs du formulaire chiffres en RSA
 */

if (!defined('FSB_SDK'))
{
	define('FSB_SDK', true);
}

if (!defined('ROOT'))
{
	define('ROOT', './');
}

if (!defined('FORUM'))
{
	// ~/main/start.php est utilise pour initialiser le necessaire au SDK
	define('PHPEXT', substr(strrchr(__FILE__,'.'), 1));
	include(ROOT . 'main/start.' . PHPEXT);

	// Pas d'appel direct de la page, sauf dans le cadre d'applications internes
	$sdkmode = Http::request('sdkmode', 'get');

	if (basename($_SERVER['PHP_SELF']) == 'sdk.'.PHPEXT && !in_array($sdkmode, array('captcha')))
	{
		exit;
	}

	// Si le SDK est desactive, on affiche un message d'erreur
	if(intval(Fsb::$cfg->get('disable_sdk')))
	{
		trigger_error('SDK can\'t be initialized, check your forum configuration.', FSB_ERROR);
	}

	// Session
	Fsb::$session->start('');
}

/**
 * SDK : Software Developement Kit : Outil de developpement du logiciel
 */
class Fsb_sdk extends Fsb_model
{
	/**
	 * Version du SDK
	 *
	 * @var string
	 */
	public $version = '1.1.1';

	/**
	 * Derniere erreur rencontree
	 *
	 * @var string
	 */
	public $errstr = '';

	/**
	 * @var Rsa
	 */
	private $rsa;

	/**
	 * Variables a crypter en RSA
	 *
	 * @var array
	 */
	private $rsa_vars = array();

	/**
	 * Constructeur
	 */
	public function __construct()
	{

	}

	//
	// METHODES LIEES AUX UTILISATEURS DU FORUM
	//

	/**
	 * Converti un pseudonyme en ID
	 *
	 * @param string $nickname Pseudonyme utilisateur
	 * @return int ID de l'utilisateur
	 */
	public function nickname2id($nickname)
	{
		$sql = 'SELECT u_id
				FROM ' . SQL_PREFIX . 'users
				WHERE u_nickname = \'' . Fsb::$db->escape($nickname) . '\'
					AND u_id <> ' . VISITOR_ID;
		return (Fsb::$db->get($sql, 'u_id'));
	}

	/**
	 * Converti une ID en pseudonyme
	 *
	 * @param int $id ID d'utilisateur
	 * @return string Pseudonyme de l'utilisateur
	 */
	public function id2nickname($id)
	{
		$sql = 'SELECT u_nickname
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id = ' . intval($id) . '
					AND u_id <> ' . VISITOR_ID;
		return (Fsb::$db->get($sql, 'u_nickname'));
	}

	/**
	 * Converti un nom de forum en ID
	 *
	 * @param string $forumname Nom du forum
	 * @return int ID du forum
	 */
	public function forumname2id($forumname)
	{
		$sql = 'SELECT f_id
				FROM ' . SQL_PREFIX . 'forums
				WHERE f_name = \'' . Fsb::$db->escape($forumname) . '\'';
		return (Fsb::$db->get($sql, 'f_id'));
	}

	/**
	 * Retourne des informations sur le visiteur actuel (informations tirees de la table fsb2_users)
	 *
	 * @param string $key Information a recuperer (par exemple u_nickname pour le pseudonyme, u_email pour son email, etc ..)
	 * @return mixed
	 */
	public function userdata($key)
	{
		return (Fsb::$session->data[$key]);
	}

	/**
	 * Verifie si le membre est connecte
	 *
	 * @return bool
	 */
	public function is_logged()
	{
		return (Fsb::$session->is_logged());
	}

	/**
	 * Connexion au forum
	 *
	 * @param string $login Login de connexion
	 * @param string $password Mot de passe de connexion
	 * @param bool $is_hidden Connexion invisible
	 * @param bool $use_auto_connexion Connexion automatique
	 * @return bool true si la connexion a reussie, sinon false
	 */
	public function login($login, $password, $is_hidden = false, $use_auto_connexion = false)
	{
		// Necessite le fichier de langue de la page de connexion
		Fsb::$session->load_lang('lg_forum_login');

		// Connexion
		$return = Fsb::$session->log_user($login, $password, $is_hidden, $use_auto_connexion);
		if ($return !== false)
		{
			$this->errstr = $return;
			return (false);
		}
		return (true);
	}

	/**
	 * Deconnexion d'un membre
	 */
	public function logout()
	{
		if (Fsb::$session->is_logged())
		{
			Fsb::$session->logout();
		}
	}

	/**
	 * Retourne le pseudonyme du visiteur courant avec un lien vers son profile et sa couleur
	 *
	 * @return string
	 */
	public function nickname()
	{
		return (Html::nickname($this->userdata('u_nickname'), $this->userdata('u_id'), $this->userdata('u_color')));
	}

	/**
	 * Informations sur le dernier membre inscrit
	 *
	 * @return array
	 */
	public function last_user()
	{
		$info = array(
			'id' =>			Fsb::$cfg->get('last_user_id'),
			'nickname' =>	Fsb::$cfg->get('last_user_login'),
			'color' =>		Fsb::$cfg->get('last_user_color'),
			'html' =>		Html::nickname(Fsb::$cfg->get('last_user_login'), Fsb::$cfg->get('last_user_id'), Fsb::$cfg->get('last_user_color')),
		);

		return ($info);
	}

	/**
	 * Recuperation d'une liste de membres.
	 * Cette fonction retournera un tableau avec pour chaque entree les informations sur un membre.
	 *
	 * @param int $total Nombre de membres a afficher. Un nombre <= 0 ou le joker * aura pour effet d'afficher tous les membres
	 * @param string $order Ordre dans lequel trier les membres
	 * @return array
	 */
	public function get_users($total = 5, $order = 'u_joined DESC')
	{
		$total =	$this->_request_total($total);
		$users =	array();
		$parser =	new Parser();

		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'users
				WHERE u_id <> ' . VISITOR_ID
				. (($order) ? ' ORDER BY ' . $order : '')
				. (($total > 0) ? ' LIMIT ' . $total : '');
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			// Informations passees au parseur de message
			$parser_info = array(
				'u_id' =>			$row['u_id'],
				'p_nickname' =>		$row['u_nickname'],
				'u_auth' =>			$row['u_auth'],
				'is_sig' =>			true,
			);

			// Informations sur le membre
			$row['u_avatar'] =		User::get_avatar($row['u_avatar'], $row['u_avatar_method'], $row['u_can_use_avatar']);
			$row['rank'] =			User::get_rank($row['u_total_post'], $row['u_rank_id']);
			$row['age'] =			User::get_age($row['u_birthday']);
			$row['age'] =			($row['age']) ? sprintf(Fsb::$session->lang('topic_age_format'), $row['age']) : Fsb::$session->lang('topic_age_none');
			$row['sexe'] =			User::get_sexe($row['u_sexe']);
			$row['sexe'] =			($row['sexe'] != '') ? $row['sexe'] : Fsb::$session->lang('topic_sexe_none');
			$row['joined'] =		Fsb::$session->print_date($row['u_joined'], false);
			$row['is_online'] =		($row['u_last_visit'] > (CURRENT_TIME - ONLINE_LENGTH) && !$row['u_activate_hidden']) ? true : false;
			$row['nickname'] =		Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']);
			$row['u_signature'] =	$parser->sig($row['u_signature'], $parser_info);

			$users[] = $row;
		}
		Fsb::$db->free($result);

		return ($users);
	}

	/**
	 * Recuperation des membres avec le plus de messages
	 *
	 * @param int $total Nombre de membres a afficher. Un nombre <= 0 ou le joker * aura pour effet d'afficher tous les membres
	 * @return array
	 */
	public function get_best_posters($total = 15)
	{
		return ($this->get_users($total, 'u_total_post DESC, u_joined DESC'));
	}

	/**
	 * Recuperation des membres avec le moins de messages
	 *
	 * @param int $total Nombre de membres a afficher. Un nombre <= 0 ou le joker * aura pour effet d'afficher tous les membres
	 * @return array
	 */
	public function get_worst_posters($total = 15)
	{
		return ($this->get_users($total, 'u_total_post DESC, u_joined DESC'));
	}

	//
	// METHODES LIEES AUX SUJETS DU FORUM
	//

	/**
	 * Recuperation d'une liste de sujets.
	 * Cette fonction retournera un tableau avec pour chaque entree les informations sur un sujet.
	 *
	 * @param string|array $forums Forums dans lesquels on va chercher les sujets. On peut lui donner un tableau d'ID de forums ou bien lui passer le joker * pour chercher dans tous les forums (en prenant compte des droits bien sur)
	 * @param int $total Nombre de sujets a afficher. Un nombre <= 0 ou le joker * aura pour effet d'afficher tous les sujets
	 * @param string $order Ordre dans lequel trier les sujets
	 * @return array
	 */
	public function get_topics($forums = '*', $total = 15, $order = 't_last_p_time DESC')
	{
		$forums =	$this->_request_forums($forums);
		$total =	$this->_request_total($total);

		// Requete de recuperation des sujets
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'topics
				WHERE 1AND f_id IN (' . implode(', ', $forums) . ') '
				. (($order) ? ' ORDER BY ' . $order : '')
				. (($total > 0) ? ' LIMIT ' . $total : '');
		$result = Fsb::$db->query($sql);

		return (Fsb::$db->rows($result));
	}

	/**
	 * Recuperation des sujets les plus lus
	 *
	 * @param string|array $forums Forums dans lesquels on va chercher les sujets. On peut lui donner un tableau d'ID de forums ou bien lui passer le joker * pour chercher dans tous les forums (en prenant compte des droits bien sur)
	 * @param int $total Nombre de sujets a afficher. Un nombre <= 0 ou le joker * aura pour effet d'afficher tous les sujets
	 * @return array
	 */
	public function get_most_viewed_topics($forums = '*', $total = 15)
	{
		return ($this->get_topics($forums, $total, 't_total_view DESC, t_last_p_time DESC'));
	}

	/**
	 * Recuperation des sujets les moins lus
	 *
	 * @param string|array $forums Forums dans lesquels on va chercher les sujets. On peut lui donner un tableau d'ID de forums ou bien lui passer le joker * pour chercher dans tous les forums (en prenant compte des droits bien sur)
	 * @param int $total Nombre de sujets a afficher. Un nombre <= 0 ou le joker * aura pour effet d'afficher tous les sujets
	 * @return array
	 */
	public function get_less_viewed_topics($forums = '*', $total = 15)
	{
		return ($this->get_topics($forums, $total, 't_total_view ASC, t_last_p_time DESC'));
	}

	/**
	 * Recuperation des sujets avec le plus de reponses
	 *
	 * @param string|array $forums Forums dans lesquels on va chercher les sujets. On peut lui donner un tableau d'ID de forums ou bien lui passer le joker * pour chercher dans tous les forums (en prenant compte des droits bien sur)
	 * @param int $total Nombre de sujets a afficher. Un nombre <= 0 ou le joker * aura pour effet d'afficher tous les sujets
	 * @return array
	 */
	public function get_most_posted_topics($forums = '*', $total = 15)
	{
		return ($this->get_topics($forums, $total, 't_total_post DESC, t_last_p_time DESC'));
	}

	/**
	 * Recuperation des sujets avec le moins de reponses
	 *
	 * @param string|array $forums Forums dans lesquels on va chercher les sujets. On peut lui donner un tableau d'ID de forums ou bien lui passer le joker * pour chercher dans tous les forums (en prenant compte des droits bien sur)
	 * @param int $total Nombre de sujets a afficher. Un nombre <= 0 ou le joker * aura pour effet d'afficher tous les sujets
	 * @return array
	 */
	public function get_less_posted_topics($forums = '*', $total = 15)
	{
		return ($this->get_topics($forums, $total, 't_total_post ASC, t_last_p_time DESC'));
	}

	/**
	 * Recuperation d'une liste de messages.
	 *
	 *
	 * @param string|array $forums Forums dans lesquels on va chercher les sujets. On peut lui donner un tableau d'ID de forums ou bien lui passer le joker * pour chercher dans tous les forums (en prenant compte des droits bien sur)
	 * @param int $total Total de messages a afficher. Un nombre <= 0 ou le joker * aura pour effet d'afficher tous les messages
	 * @param string $order Ordre dans lequel trier les messages
	 * @param bool $gbt (Group By Topics) si true, on ne recherche qu'un message par sujet
	 * @return array
	 */
	public function get_posts($forums = '*', $total = 15, $order = 'p.p_time DESC', $gbt = true)
	{
		// Necessite le fichier de langue de la page des sujets
		Fsb::$session->load_lang('lg_forum_topic');

		$forums =	$this->_request_forums($forums);
		$total =	$this->_request_total($total);
		$posts =	array();
		$parser =	new Parser();

		$sql = 'SELECT p.*, t.*, u.*
				FROM ' . SQL_PREFIX . 'posts p
				INNER JOIN ' . SQL_PREFIX . 'topics t
					ON t.t_id = p.t_id
				INNER JOIN ' . SQL_PREFIX . 'users u
					ON p.u_id = u.u_id
				WHERE p.f_id IN (' . implode(', ', $forums) . ') '
				. (($gbt) ? ' GROUP BY p.t_id' : '')
				. (($order) ? ' ORDER BY ' . $order : '')
				. (($total > 0) ? ' LIMIT ' . $total : '');
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			$parser->parse_html = false;

			// Informations passees au parseur de message
			$parser_sig_info = array(
				'u_id' =>			$row['u_id'],
				'p_nickname' =>		$row['p_nickname'],
				'u_auth' =>			$row['u_auth'],
				'is_sig' =>			true,
			);

			// Informations sur le posteur du message
			$row['u_avatar'] =		User::get_avatar($row['u_avatar'], $row['u_avatar_method'], $row['u_can_use_avatar']);
			$row['rank'] =			User::get_rank($row['u_total_post'], $row['u_rank_id']);
			$row['age'] =			User::get_age($row['u_birthday']);
			$row['age'] =			($row['age']) ? sprintf(Fsb::$session->lang('topic_age_format'), $row['age']) : Fsb::$session->lang('topic_age_none');
			$row['sexe'] =			User::get_sexe($row['u_sexe']);
			$row['sexe'] =			($row['sexe'] != '') ? $row['sexe'] : Fsb::$session->lang('topic_sexe_none');
			$row['joined'] =		Fsb::$session->print_date($row['u_joined'], false);
			$row['is_online'] =		($row['u_last_visit'] > (CURRENT_TIME - ONLINE_LENGTH) && !$row['u_activate_hidden']) ? true : false;
			$row['nickname'] =		Html::nickname($row['p_nickname'], $row['u_id'], $row['u_color']);
			$row['u_signature'] =	$parser->sig($row['u_signature'], $parser_sig_info);

			// Informations passees au parseur de message
			$parser_info = array(
				'u_id' =>			$row['u_id'],
				'p_nickname' =>		$row['p_nickname'],
				'u_auth' =>			$row['u_auth'],
				'f_id' =>			$row['f_id'],
				't_id' =>			$row['t_id'],
			);

			// Informations sur le message
			$parser->parse_html =	(Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;
			$row['p_text'] =		$parser->mapped_message($row['p_text'], $row['p_map'], $parser_info);
			$row['p_timestamp'] =	$row['p_time'];
			$row['p_time'] =		Fsb::$session->print_date($row['p_time'], true, null, true);
			$posts[] = $row;
		}
		Fsb::$db->free($result);

		return ($posts);
	}

	//
	// METHODES DE CREATION DE MESSAGES SUR LE FORUM
	//

	/**
	 * Creation d'un nouveau sujet
	 *
	 * @param string $title Titre du sujet
	 * @param string $content Contenu du message
	 * @param int $forum_id ID du forum dans lequel le sujet sera cree
	 * @param int $user_id ID du membre creant le sujet
	 * @param string $description Description du sujet
	 * @param int $type Type de sujet
	 */
	public function post_topic($title, $content, $forum_id, $user_id = null, $description = '', $type = null)
	{
		// Si l'ID du membre n'est pas donnee on prend celle du membre courrant
		if (is_null($user_id))
		{
			$user_id = Fsb::$session->id();
		}

		// Par defaut on prend le dernier type pour les topics, donc le "sujet de base"
		if (is_null($type))
		{
			$type = count($GLOBALS['_topic_type']) - 1;
		}

		$topic_id = Send::send_topic($forum_id, $user_id, $title, 'classic', $type, array(
			't_description' =>	$description,
		));

		$this->post_reply($topic_id, $content, $user_id);
	}

	/**
	 * Reponse a un sujet deja existant
	 *
	 * @param int $topic_id ID du sujet
	 * @param string $content Contenu du message
	 * @param int $user_id ID du membre postant le message
	 */
	public function post_reply($topic_id, $content, $user_id = null)
	{
		// Formatage XML du message
		$message = new Xml();
		$message->document->setTagName('root');
		$message_line = $message->document->createElement('line');
		$message_line->setAttribute('name', 'description');
		$message_line->setData(htmlspecialchars($content));
		$message->document->appendChild($message_line);
		$content = $message->document->asValidXML();
		unset($message);

		// Si l'ID du membre n'est pas donnee on prend celle du membre courrant
		if (is_null($user_id))
		{
			$user_id = Fsb::$session->id();
			$nickname = Fsb::$session->data['u_nickname'];
		}
		else if ($user_id != Fsb::$session->id())
		{
			$nickname = $this->id2nickname($user_id);
		}
		else
		{
			$nickname = Fsb::$session->data['u_nickname'];
		}

		// Informations sur le sujet
		$sql = 'SELECT f_id, t_total_post, t_title
				FROM ' . SQL_PREFIX . 'topics
				WHERE t_id = ' . $topic_id;
		$data = Fsb::$db->request($sql);

		if (!$data)
		{
			trigger_error('Fsb_sdk :: Le sujet avec l\'ID "' . $topic_id . '" n\'existe pas', FSB_ERROR);
		}

		$is_first_post = ($data['t_total_post'] == 0) ? true : false;

		Send::send_post($user_id, $topic_id, $data['f_id'], $content, $nickname, IS_APPROVED, 'classic', array(
			't_title' =>		$data['t_title'],
		), $is_first_post);
	}

	/**
	 * Envoie un message prive
	 *
	 * @param string $title Titre du message prive
	 * @param string $content Contenu du message prive
	 * @param int $to_id ID du destinataire
	 * @param int $from_id ID de l'envoyeur
	 */
	public function post_mp($title, $content, $to_id, $from_id = null)
	{
		// Si l'ID du membre n'est pas donnee on prend celle du membre courrant
		if (is_null($from_id))
		{
			$from_id = Fsb::$session->id();
		}

		Send::send_mp($from_id, $to_id, $title, $content, 0, false);
	}

	/**
	 * Creation d'un nouveau sondage
	 *
	 * @param string $poll_name Nom du sondage
	 * @param array $poll_values Contient les options du sondage
	 * @param string $title Titre du sujet
	 * @param string $content Contenu du message
	 * @param int $forum_id ID du forum dans lequel le sujet sera cree
	 * @param int $user_id ID du membre creant le sujet
	 * @param int $poll_max_vote Nombre d'option pour lesquelles un membre peut voter
	 * @param string $description Description du sujet
	 */
	public function post_poll($poll_name, $poll_values, $title, $content, $forum_id, $user_id = null, $poll_max_vote = 1, $description = '')
	{
		// Si l'ID du membre n'est pas donnee on prend celle du membre courrant
		if (is_null($user_id))
		{
			$user_id = Fsb::$session->id();
		}

		if (!is_array($poll_values) || count($poll_values) < 2)
		{
			trigger_error('Fsb_sdk :: $poll_values doit etre un tableau pour le sondage', FSB_ERROR);
		}

		// Verification de l'integrite du sondage
		if ($poll_max_vote < 1 || $poll_max_vote > count($poll_values))
		{
			$poll_max_vote = 1;
		}

		$topic_id = Send::send_topic($forum_id, $user_id, $title, 'classic', count($GLOBALS['_topic_type']) - 1, array(
			't_description' =>	$description,
			't_poll' =>			true,
		));

		$this->post_reply($topic_id, $content, $user_id);

		Poll::send($poll_name, $poll_values, $topic_id, $poll_max_vote);
	}

	//
	// METHODES LIEES AUX BOITES STATISTIQUES ET QUI EST EN LIGNE
	//

	/**
	 * Genere un tableau d'information sur les membres en ligne
	 * La clef 'list' contient un tableau listant les membres en ligne (is_hidden determine s'ils sont invisibles)
	 *
	 * @return array
	 */
	public function who_is_online()
	{
		$return = array('list' => array());

		$sql = 'SELECT s.s_id, s.s_ip, u.u_id, u.u_nickname, u.u_color, u.u_activate_hidden, b.bot_id, b.bot_name
				FROM ' . SQL_PREFIX . 'sessions s
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = s.s_id
				LEFT JOIN ' . SQL_PREFIX . 'bots b
					ON s.s_bot = b.bot_id
				WHERE s.s_time > ' . intval(CURRENT_TIME - ONLINE_LENGTH) . '
				ORDER BY u.u_auth DESC, u.u_nickname, s.s_id';
		$result = Fsb::$db->query($sql);

		$total_visitor = 0;
		$total_user = 0;
		$total_hidden = 0;
		$ip_array = array();
		$bot_array = array();
		$id_array = array();
		while ($row = Fsb::$db->row($result))
		{
			// Bot ?
			if (Fsb::$mods->is_active('bot_list') && !is_null($row['bot_id']))
			{
				if (in_array($row['bot_id'], $bot_array))
				{
					continue;
				}
				$bot_array[] = $row['bot_id'];
				$total_visitor++;

				$return['list'][] = array(
					'is_hidden' =>	false,
					'id' =>			null,
					'nickname' =>	null,
					'color' =>		'class="bot"',
					'html' =>		sprintf(Fsb::$session->getStyle('other', 'nickname'), 'class="bot"', $row['bot_name'] . ' (bot)'),
				);
			}
			// Visiteur ?
			else if ($row['s_id'] == VISITOR_ID)
			{
				if (in_array($row['s_ip'], $ip_array))
				{
					continue;
				}
				$ip_ary[] = $row['s_ip'];
				$total_visitor++;
			}
			else
			{
				if (in_array($row['s_id'], $id_array))
				{
					continue;
				}
				$id_array[] = $row['s_id'];

				if ($row['u_activate_hidden'])
				{
					$total_hidden++;
				}
				else
				{
					$total_user++;
				}

				// Autorisation de voir les invites ?
				if (!$row['u_activate_hidden'] || (Fsb::$session->auth() >= MODOSUP || Fsb::$session->id() == $row['s_id']))
				{
					$return['list'][] = array(
						'is_hidden' =>	$row['u_activate_hidden'],
						'id' =>			$row['u_id'],
						'nickname' =>	$row['u_nickname'],
						'color' =>		$row['u_color'],
						'html' =>		Html::nickname($row['u_nickname'], $row['s_id'], $row['u_color']),
					);
				}
			}
		}
		Fsb::$db->free($result);
		unset($ip_array, $id_array);

		$return['total'] =			$total_visitor + $total_user + $total_hidden;
		$return['total_visitor'] =	$total_visitor;
		$return['total_user'] =		$total_user;
		$return['total_hidden'] =	$total_hidden;
		$return['total_lang'] =		sprintf(Fsb::$session->lang('current_user_online'), $return['total'], $total_user, $total_hidden, $total_visitor);

		return ($return);
	}

	/**
	 * Genere un tableau d'information sur les membres qui etaient en ligne aujourd'hui
	 *
	 * @return array
	 */
	public function who_was_online_today()
	{
		$return = array('list' => array());

		$sql = 'SELECT u_id, u_nickname, u_color, u_activate_hidden
				FROM ' . SQL_PREFIX . 'users
				WHERE u_last_visit > ' . mktime(0, 0, 0, date('m', CURRENT_TIME), date('d', CURRENT_TIME), date('Y', CURRENT_TIME)) . '
					AND u_id <> ' . VISITOR_ID . '
				ORDER BY u_auth DESC, u_nickname, u_id';
		$result = Fsb::$db->query($sql);

		$total_user_today = 0;
		$total_hidden_today = 0;
		while ($row = Fsb::$db->row($result))
		{
			if ($row['u_activate_hidden'])
			{
				$total_hidden_today++;
				if (Fsb::$session->auth() < MODOSUP && Fsb::$session->id() != $row['u_id'])
				{
					continue;
				}
			}
			else
			{
				$total_user_today++;
			}

			$return['list'][] = array(
				'is_hidden' =>	$row['u_activate_hidden'],
				'id' =>			$row['u_id'],
				'nickname' =>	$row['u_nickname'],
				'color' =>		$row['u_color'],
				'html' =>		Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
			);
		}
		Fsb::$db->free($result);

		$return['total'] =			$total_user_today + $total_hidden_today;
		$return['total_user'] =		$total_user_today;
		$return['total_hidden'] =	$total_hidden_today;
		$return['total_lang'] =		sprintf(String::plural('today_user_online', $return['total']), $return['total'], $total_hidden_today);

		return ($return);
	}

	/**
	 * Genere un tableau d'information sur les membres fetant leur anniversaire aujourd'hui
	 *
	 * @return array
	 */
	public function who_has_birthday_today()
	{
		$return = array('list' => array());

		$current_day = strval(date('d', CURRENT_TIME));
		if (strlen($current_day) == 1)
		{
			$current_day = '0' . $current_day;
		}

		$current_month = strval(date('m', CURRENT_TIME));
		if (strlen($current_month) == 1)
		{
			$current_month = '0' . $current_month;
		}

		// Mise en cache des anniversaires des membres une fois par jour
		if (Fsb::$cfg->get('cache_birthday') != $current_day)
		{
			Fsb::$cfg->update('cache_birthday', $current_day);
			Fsb::$db->destroy_cache('users_birthday_');
		}

		// Liste des anniversaires des membres
		$sql = 'SELECT u_id, u_nickname, u_birthday, u_color
				FROM ' . SQL_PREFIX . 'users
				WHERE u_birthday ' . Fsb::$db->like() . ' \'' . $current_day . '/' . $current_month . '/%\'
					AND u_id <> ' . VISITOR_ID;
		$result = Fsb::$db->query($sql, 'users_birthday_');
		$total_birthday = 0;
		while ($row = Fsb::$db->row($result))
		{
			$total_birthday++;
			$return['list'][] = array(
				'id' =>			$row['u_id'],
				'nickname' =>	$row['u_nickname'],
				'color' =>		$row['u_color'],
				'html' =>		Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
				'age' =>		intval(date('Y', CURRENT_TIME) - substr($row['u_birthday'], 6)),
			);
		}
		Fsb::$db->free($result);

		$return['total'] =		$total_birthday;
		$return['total_lang'] =	sprintf(String::plural('users_birthday', $total_birthday, true), $total_birthday);

		return ($return);
	}

	//
	// METHODES LIEES AU CALENDRIER
	//

	/**
	 * Genere un tableau d'information sur les evenements a venir
	 *
	 * @param int $calendar_days Afficher les evenements des X prochains jours
	 * @return array
	 */
	public function get_calendar_events($calendar_days = 3)
	{
		$return =		array('list' => array());
		$total_event =	0;
		$parser =		new Parser();

		if (Fsb::$session->is_authorized('calendar_read'))
		{
			$begin_timestamp =	intval(mktime(0, 0, 0, date('m', CURRENT_TIME), date('d', CURRENT_TIME), date('Y', CURRENT_TIME)));
			$end_timestamp =	intval(mktime(23, 59, 59, date('m', CURRENT_TIME + $calendar_days * ONE_DAY), date('d', CURRENT_TIME + $calendar_days * ONE_DAY), date('Y', CURRENT_TIME + $calendar_days * ONE_DAY)));
			$sql = 'SELECT c_begin, c_end, c_title, c_approve, u_id, c_view, c_content
					FROM ' . SQL_PREFIX . 'calendar
					WHERE c_end >= ' . $begin_timestamp . '
						AND c_begin <= ' . $end_timestamp . '
						AND (c_view = -1 OR c_view > 0)
						AND c_approve = 1
						ORDER BY c_begin, c_id';
			$result = Fsb::$db->query($sql, 'calendar_' . date('d_m_Y') . '_');
			while ($row = Fsb::$db->row($result))
			{
				if ($row['c_view'] == -1 || in_array($row['c_view'], Fsb::$session->data['groups']))
				{
					$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;

					$return['list'][] = array(
						'content' =>	$parser->mapped_message($row['c_content'], 'classic'),
						'begin' =>		$row['c_begin'],
						'end' =>		$row['c_end'],
						'url' =>		sid(FSB_PATH . 'index.' . PHPEXT . '?p=calendar&amp;mode=event&amp;time=' . $row['c_begin']),
						'name' =>		htmlspecialchars($row['c_title']),
						'date' =>		($row['c_end'] - ONE_DAY > $row['c_begin']) ? date('d/m/Y', $row['c_begin']) . ' - ' . date('d/m/Y', $row['c_end']) : date('d/m/Y', $row['c_begin']),
					);
					$total_event++;
				}
			}
			Fsb::$db->free($result);
		}

		$return['total'] =		$total_event;
		$return['total_lang'] =	($total_event > 0) ? sprintf(Fsb::$session->lang('calendar_stats'), $total_event, $calendar_days) : sprintf(Fsb::$session->lang('calendar_stats_none'), $calendar_days);

		return ($return);
	}

	//
	// METHODES LIEES AUX SONDAGES
	//

	/**
	 * Recupere les informations sur un sondage
	 *
	 * @param string $name Nom pour le formulaire
	 * @param string $order Tri a appliquer pour recuperer le sondage
	 * @param int $topic_id ID du sujet si on veut un sondage precis
	 * @return array
	 */
	public function get_poll($name = 'fsbpoll', $order = 't.t_last_p_time DESC', $topic_id = null)
	{
		$return = array();

		if (is_null($topic_id))
		{
			$sql = 'SELECT t.t_id
					FROM ' . SQL_PREFIX . 'topics t
					LEFT JOIN ' . SQL_PREFIX . 'posts p
						ON t.t_first_p_id = p.p_id
					WHERE t_poll = 1'
					. (($order) ? ' ORDER BY ' . $order : '')
					. ' LIMIT 1';
			$topic_id = Fsb::$db->get($sql, 't_id');
		}

		if (!$topic_id)
		{
			return (null);
		}

		$sql = 'SELECT p.t_id, p.poll_name, p.poll_total_vote, p.poll_max_vote, po.poll_opt_id, po.poll_opt_name, po.poll_opt_total, pr.poll_result_u_id, t.t_status, f.f_status
				FROM ' . SQL_PREFIX . 'poll p
				LEFT JOIN ' . SQL_PREFIX . 'poll_options po
					ON p.t_id = po.t_id
				LEFT JOIN ' . SQL_PREFIX . 'poll_result pr
					ON p.t_id = pr.t_id
						AND pr.poll_result_u_id = ' . $this->userdata('u_id') . '
				LEFT JOIN ' . SQL_PREFIX . 'topics t
					ON t.t_id = p.t_id
				LEFT JOIN ' . SQL_PREFIX . 'forums f
					ON f.f_id = t.f_id
				WHERE p.t_id = ' . $topic_id;
		$result = Fsb::$db->query($sql);
		if ($row = Fsb::$db->row($result))
		{
			$return = array(
				'topic_id' =>	$row['t_id'],
				'name' =>		$row['poll_name'],
				'total_vote' =>	$row['poll_total_vote'],
				'max_vote' =>	$row['poll_max_vote'],
				'can_vote' =>	(!$row['poll_result_u_id'] && Fsb::$session->is_logged() && $row['t_status'] == UNLOCK && $row['f_status'] == UNLOCK) ? true : false,
				'options' =>	array(),
			);

			do
			{
				$input =	($row['poll_max_vote'] > 1) ? 'checkbox' : 'radio';
				$width =	($row['poll_total_vote']) ? floor(($row['poll_opt_total'] / $row['poll_total_vote']) * 200) : 0;
				$calcul =	($row['poll_total_vote']) ? substr(($row['poll_opt_total'] / $row['poll_total_vote']) * 100, 0, 4) : 0;
				$html =		($return['can_vote']) ? '<input type="' . $input . '" name="' . $name . (($input == 'checkbox') ? '[]' : '') . '" value="' . $row['poll_opt_id'] . '" /> ' : '';
				$html .=	'<img src="' . Fsb::$session->img('poll_result_left') . '" />';
				$html .=	'<img src="' . Fsb::$session->img('poll_result') . '" width="' . $width . '" height="15" />';
				$html .=	'<img src="' . Fsb::$session->img('poll_result_right') . '" /> ';
				$html .=	$calcul . '% (' . $row['poll_opt_total'] . ' / ' . $row['poll_total_vote'] . ')';

				$return['options'][] = array(
					'id' =>			$row['poll_opt_id'],
					'name' =>		$row['poll_opt_name'],
					'total' =>		$row['poll_opt_total'],
					'input' =>		$input,
					'width' =>		$width,
					'calcul' =>		$calcul,
					'html' =>		$html,
				);
			}
			while ($row = Fsb::$db->row($result));
		}
		Fsb::$db->free($result);

		return ($return);
	}

	/**
	 * Recupere le dernier sondage cree
	 *
	 * @param string $name Nom pour le formulaire
	 * @return array
	 */
	public function get_last_poll($name = 'fsbpoll')
	{
		return ($this->get_poll($name, 'p.p_time DESC'));
	}

	/**
	 * Recupere un sondage aleatoire
	 *
	 * @param string $name Nom pour le formulaire
	 * @return array
	 */
	public function get_random_poll($name = 'fsbpoll')
	{
		return ($this->get_poll($name, 'RAND()'));
	}

	/**
	 * Soumet le vote
	 *
	 * @param int $topic_id ID du sujet du sondage
	 * @param string $name Nom pour le formulaire
	 * @return bool Retourne false en cas d'erreur
	 */
	public function submit_poll($topic_id, $name = 'fsbpoll')
	{
		// Necessite le fichier de langue de la page des sujets
		Fsb::$session->load_lang('lg_forum_topic');

		if (!$this->is_logged())
		{
			$this->errstr = Fsb::$session->lang('not_allowed');
			return (false);
		}

		// Donnees du sondage
		$sql = 'SELECT p.poll_max_vote, pr.poll_result_u_id
				FROM ' . SQL_PREFIX . 'poll p
				LEFT JOIN ' . SQL_PREFIX . 'poll_result pr
					ON p.t_id = pr.t_id
						AND pr.poll_result_u_id = ' . $this->userdata('u_id') . '
						AND pr.poll_result_u_id <> ' . VISITOR_ID . '
				WHERE p.t_id = ' . $topic_id;
		$row = Fsb::$db->request($sql);

		// On verifie tout de meme si le membre n'a pas deja vote et que le sondage existe
		if (!$row)
		{
			$this->errstr = Fsb::$session->lang('topic_poll_not_exists');
			return (false);
		}
		else if ($row['poll_result_u_id'])
		{
			$this->errstr = Fsb::$session->lang('topic_has_submit_poll');
			return (false);
		}

		// On recupere les votes
		$poll_result = Http::request($name, 'post');
		if (!is_array($poll_result))
		{
			$poll_result = array();
		}
		$poll_result = array_map('intval', $poll_result);

		// On verifie qu'il y ai au moins un vote :p
		if (!$poll_result)
		{
			$this->errstr = Fsb::$session->lang('topic_poll_need_vote');
			return (false);
		}

		// On verifie qu'il n'y ait pas trop de votes
		if (count($poll_result) > $row['poll_max_vote'])
		{
			$this->errstr = sprintf(Fsb::$session->lang('topic_poll_too_much_vote'), $row['poll_max_vote']);
			return (false);
		}

		// On met a jour le nombre de vote par option et on signale que le membre a vote
		Fsb::$db->insert('poll_result', array(
			'poll_result_u_id' =>	$this->userdata('u_id'),
			't_id' =>				$topic_id,
		));

		Fsb::$db->update('poll', array(
			'poll_total_vote' =>	array('(poll_total_vote + ' . count($poll_result) . ')', 'is_field' => true),
		), 'WHERE t_id = ' . $topic_id);

		foreach ($poll_result AS $value)
		{
			Fsb::$db->update('poll_options', array(
				'poll_opt_total' =>	array('(poll_opt_total + 1)', 'is_field' => true),
			), 'WHERE poll_opt_id = ' . intval($value) . ' AND t_id = ' . $topic_id);
		}

		return (true);
	}

	//
	// METHODES LIEES A LA CREATION D'URL
	//

	/**
	 * Cree une URL vers un sujet
	 *
	 * @param int $topic_id ID du sujet
	 * @return string
	 */
	public function url_topic($topic_id)
	{
		return (sid(FSB_PATH . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $topic_id));
	}

	/**
	 * Cree une URL vers un message
	 *
	 * @param int $post_id ID du message
	 * @return string
	 */
	public function url_post($post_id)
	{
		return (sid(FSB_PATH . 'index.' . PHPEXT . '?p=topic&amp;p_id=' . $post_id . '#' . $post_id));
	}

	/**
	 * Cree une URL vers un forum
	 *
	 * @param int $forum_id ID du forum
	 * @return string
	 */
	public function url_forum($forum_id)
	{
		return (sid(FSB_PATH . 'index.' . PHPEXT . '?p=forum&amp;f_id=' . $forum_id));
	}

	/**
	 * Cree une URL vers le profil d'un membre
	 *
	 * @param int $user_id ID du membre
	 * @return string
	 */
	public function url_user($user_id)
	{
		return (sid(FSB_PATH . 'index.' . PHPEXT . '?p=userprofile&amp;id=' . $user_id));
	}

	/**
	 * Cree une URL vers l'index
	 *
	 * @return string
	 */
	public function url_index()
	{
		return (sid(FSB_PATH . 'index.' . PHPEXT . '?p=index'));
	}

	//
	// METHODES LIEES AUX THEMES
	//

	/**
	 * Change le chemin du theme.
	 * Methode permettant d'utiliser le systeme de template en utilisant un theme independant a celui du forum.
	 *
	 * @param string $dir Nouveau chemin vers le theme
	 */
	public function template_path($dir)
	{
		Fsb::$tpl->set_template($dir);
	}

	//
	// METHODES LIEES A LA MISE EN PLACE D'UN CAPTCHA (CONFIRMATION VISUELLE)
	//

	/**
	 * Verifie si la chaine passee en parametre est un code Captcha valide
	 *
	 * @param string $str Code Captcha a verifier
	 * @return bool
	 */
	public function captcha_valid($str)
	{
		return (check_captcha($str));
	}

	/**
	 * Retourne une URL pour l'image du Captcha. A appeller dans une balise HTML image
	 *
	 * @param string $mode keep gardera la chaine actuelle, new creera une nouvelle chaine
	 * @return string
	 */
	public function captcha_img($mode = 'new')
	{
		if ($mode != 'new' && $mode != 'keep')
		{
			$mode = 'new';
		}

		return (FSB_PATH . 'sdk.' . PHPEXT . '?sdkmode=captcha&amp;mode=' . $mode . '&amp;uniqid=' . md5(rand(1, time())));
	}

	/**
	 * Genere l'image du Captcha
	 *
	 * @param string $mode keep gardera la chaine actuelle, new creera une nouvelle chaine
	 */
	public function captcha_output($mode = 'new')
	{
		if ($mode != 'new' && $mode != 'keep')
		{
			$mode = 'new';
		}

		$captcha = Captcha::factory();
		if ($mode == 'new')
		{
			$captcha->create_str();
		}
		else
		{
			$captcha->set_str(Fsb::$session->data['s_visual_code']);
		}
		$captcha->output();

		Fsb::$db->update('sessions', array(
			's_visual_code' =>	$captcha->store_str,
		), 'WHERE s_sid = \'' . Fsb::$db->escape(Fsb::$session->sid) . '\'');
	}

	//
	// METHODES POUR LE CHIFFRAGE RSA
	//

	/**
	 * Inialisation du chiffrage RSA, a appeler au tout debut
	 * Peut prendre un nombre d'argument variable. Chaque argument correspond a un nom de champ
	 * du formulaire, qu'on veut crypter.
	 */
	public function rsa_create()
	{
		$this->rsa = new Rsa();
		$this->rsa->public_key =	Rsa_key::from_string(Fsb::$cfg->get('rsa_public_key'));
		$this->rsa->private_key =	Rsa_key::from_string(Fsb::$cfg->get('rsa_private_key'));
		if (is_null($this->rsa->public_key) || is_null($this->rsa->private_key))
		{
			$this->rsa->regenerate_keys();
		}

		foreach (func_get_args() AS $arg)
		{
			$this->rsa_vars[$arg] = null;
		}
	}

	/**
	 * Generation du Javascript pour crypter les champs du formulaire
	 * A placer si possible dans les balises <head> .. </head>
	 * Le formulaire HTML doit contenir un evenement onsubmit appelant la fonction submit_rsa(this)
	 *
	 * @return string
	 */
	public function rsa_encrypt()
	{
		// Librairies javascript necessaires
		$html = '<script type="text/javascript" src="' . FSB_PATH . 'main/javascript/biginteger.js"></script>' . "\n";
		$html .= '<script type="text/javascript" src="' . FSB_PATH . 'main/javascript/rsa.js"></script>' . "\n";
		$html .= '<script type="text/javascript"><!--' . "\n";
		$html .= 'function submit_rsa(t) {' . "\n";

		// Ajout du champ hidden final
		$html .= 'tag = document.createElement(\'input\');';
		$html .= 'attr = document.createAttribute(\'type\'); attr.nodeValue = \'hidden\'; tag.setAttributeNode(attr);' . "\n";
		$html .= 'attr = document.createAttribute(\'name\'); attr.nodeValue = \'hidden_rsa\'; tag.setAttributeNode(attr);' . "\n";
		$html .= 'attr = document.createAttribute(\'value\'); attr.nodeValue = \'1\'; tag.setAttributeNode(attr);' . "\n";
		$html .= 't.appendChild(tag);';

		// Cryptage des champs du formulaire, sous forme de champs hidden
		foreach ($this->rsa_vars AS $k => $v)
		{
			$html .= 'tag = document.createElement(\'input\');';
			$html .= 'attr = document.createAttribute(\'type\'); attr.nodeValue = \'hidden\'; tag.setAttributeNode(attr);' . "\n";
			$html .= 'attr = document.createAttribute(\'name\'); attr.nodeValue = \'' . $k . '_rsa\'; tag.setAttributeNode(attr);' . "\n";
			$html .= 'attr = document.createAttribute(\'value\'); attr.nodeValue = encrypt_rsa(document.getElementsByName(\'' . $k . '\').item(0).value, new BigInteger(\'' . $this->rsa->public_key->_get('mod') . '\'), new BigInteger(\'' . $this->rsa->public_key->_get('exp') . '\')); tag.setAttributeNode(attr);' . "\n";
			$html .= 't.appendChild(tag);' . "\n";
			$html .= 'document.getElementsByName(\'' . $k . '\').item(0).value = \'\'' . "\n";
		}
		$html .= "}\n" . '--></script>';

		return ($html);
	}

	/**
	 * Decrypte les champs du formulaire chiffres en RSA
	 *
	 * @return array Champs decryptes
	 */
	public function rsa_decrypt()
	{
		if (Http::request('hidden_rsa', 'post'))
		{
			foreach ($this->rsa_vars AS $k => $v)
			{
				$this->rsa_vars[$k] = $this->rsa->decrypt(Http::request($k . '_rsa', 'post'));
			}
		}
		return ($this->rsa_vars);
	}

	//
	// METHODES PRIVEES
	//

	/**
	 * Filtre les forums a afficher
	 *
	 * @param string|array $forums
	 * @return array
	 */
	private function _request_forums($forums)
	{
		if ($forums == '*')
		{
			$forums = array();
		}
		else if (!is_array($forums))
		{
			$forums = (is_numeric($forums)) ? array($forums) : array();
		}

		if (!$forums)
		{
			$forums = Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read'));
		}
		else
		{
			$forums = array_intersect($forums, Forum::get_authorized(array('ga_view', 'ga_view_topics', 'ga_read')));
		}

		$forums[] = 0;
		return ($forums);
	}

	/**
	 * Filtre un total d'element a afficher
	 *
	 * @param int $total
	 * @return int
	 */
	private function _request_total($total)
	{
		if ($total <= 0 || $total == '*' || !is_numeric($total))
		{
			$total = 0;
		}
		return ($total);
	}
}

// Instance de la classe
$fsb = new Fsb_sdk();

// Captcha ?
if ($sdkmode == 'captcha')
{
	$fsb->captcha_output(Http::request('mode', 'get'));
	exit;
}

?>
