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
 * Affiche le formulaire permettant de creer des sujets, repondre a des messages, envoyer
 * des messages prives etc ...
 *
 */
class Fsb_frame_child extends Fsb_frame
{
	/**
	 * Affichage de la barre de navigation du header
	 *
	 * @var bool
	 */
	public $_show_page_header_nav = true;
	
	/**
	 * Affichage de la barre de navigation du footer
	 *
	 * @var bool
	 */
	public $_show_page_footer_nav = false;
	
	/**
	 * Affichage de la boite des stats
	 *
	 * @var bool
	 */
	public $_show_page_stats = false;

	/**
	 * Navigation
	 *
	 * @var array
	 */
	public $nav = array();

	/**
	 * Mode de la page
	 *
	 * @var string
	 */
	public $mode;

	/**
	 * ID passee a la page (message prive, message, sujet, forum)
	 *
	 * @var int
	 */
	public $id;

	/**
	 * ID de membre passee a la page (message prive)
	 *
	 * @var int
	 */
	public $u_id;
	
	/**
	 * ID du message à l'origine (message prive)
	 *
	 * @var int
	 */
	public $mp_parent = 0;

	/**
	 * Definit si on quote le message
	 *
	 * @var bool
	 */
	public $quote;

	/**
	 * Login
	 * 
	 */
	public $post_login_to;
	
	/**
	 * Pseudo
	 * 
	 */
	public $nickname;
	
	/**
	 * Titre
	 * 
	 */
	public $title;
	
	/**
	 * Contenu
	 * 
	 */
	public $content;
	
	/**
	 * Description
	 * 
	 */
	public $description;
	
	/**
	 * Id
	 * 
	 */
	public $to_id = array();
	
	/**
	 * Type
	 * 
	 */
	public $type;
	
	/**
	 * Question vote
	 * 
	 */
	public $poll_name;
	
	/**
	 * Options vote
	 * 
	 */
	public $poll_values;
	
	/**
	 * Votes max
	 * 
	 */
	public $poll_max_vote;
	
	/**
	 * Texte upload
	 * 
	 */
	public $upload_comment;

	/**
	 * Erreurs
	 *
	 * @var array
	 */
	public $errstr = array();

	/**
	 * Donnees du MP en cas de reponse
	 *
	 * @var array
	 */
	public $mp_data = array();

	/**
	 * Donnees du forum / sujet / message
	 *
	 * @var array
	 */
	public $data;

	/**
	 * Schema du sujet
	 *
	 * @var string
	 */
	public $post_map;

	/**
	 * En mode preview
	 *
	 * @var bool
	 */
	public $preview = false;

	/**
	 * Utilisation du code de confirmation visuelle
	 *
	 * @var bool
	 */
	public $use_captcha = false;

	/**
	 * Le message devra etre approuve ?
	 *
	 * @var bool
	 */
	public $approve = IS_APPROVED;

	/**
	 * Texte uploade
	 *
	 * @var string
	 */
	public $onupload = '';

	/**
	 * Titre de la page
	 *
	 * @var string
	 */
	public $tag_title = '';

	/**
	 * Constructeur
	 *
	 */
	public function main()
	{
		$this->quote =			Http::request('quote');
		$this->type =			Http::request('post_type', 'post');
		$this->poll_max_vote =	Http::request('poll_max_vote', 'post');
		$this->mode =			htmlspecialchars(Http::request('mode'));
		$this->post_map =		htmlspecialchars(Http::request('post_map', 'post|get'));
		$this->post_login_to =	trim(Http::request('post_login_to', 'post'));
		$this->nickname =		trim(Http::request('post_login', 'post'));
		$this->title =			trim(Http::request('post_title', 'post'));
		$this->description =	trim(Http::request('post_description', 'post'));
		$this->content =		trim(Http::request('post_content', 'post'));
		$this->poll_name =		trim(Http::request('poll_name', 'post'));
		$this->poll_values =	trim(Http::request('poll_values', 'post'));
		$this->upload_comment = trim(Http::request('upload_comment', 'post'));
		$this->id =				intval(Http::request('id'));
		$this->u_id =			intval(Http::request('u_id'));
		$this->mp_parent =		intval(Http::request('mp_parent', 'post'));

		if ($this->poll_max_vote < 1)
		{
			$this->poll_max_vote = 1;
		}

		if ($this->mode == 'upload' && Fsb::$mods->is_active('upload'))
		{
			$this->show_upload();
			return ;
		}

		// MAP par defaut
		if (!$this->post_map || !file_exists(MAPS_PATH . $this->post_map . '.xml'))
		{
			$this->post_map = 'classic';
		}

		// Donnees du sujet
		if (!$this->get_data())
		{
			return ;
		}

		// Captcha ?
		if (Fsb::$mods->is_active('post_captcha') && !Fsb::$session->is_logged())
		{
			$this->use_captcha = true;
		}


		// Formulaire de post
		if (Http::request('submit_post', 'post'))
		{
			$this->check_form();
			if (!count($this->errstr))
			{
				$this->send_data();
			}
		}
		else if (Http::request('preview_post', 'post'))
		{
			$this->preview = true;
		}

		$this->show_post_form();
	}

	/**
	 * Recupere des donnees sur le forum / sujet / message actuel
	 *
	 * @return bool
	 */
	public function get_data()
	{
		switch ($this->mode)
		{
			case 'mp' :
			case 'edit_mp' :
				$this->post_map = 'classic';

				// Le membre a le droit d'acceder a cette page ?
				if (!Fsb::$session->is_logged())
				{
					Http::redirect(ROOT . 'index.' . PHPEXT . '?p=login&redirect=post&mode=mp&u_id=' . $this->u_id);
				}
			break;

			case 'reply' :
				// On recupere les donnees du topic, ainsi que l'ID du forum
				$sql = 'SELECT t.t_title, t.t_id, t.t_map, t.t_type, t.t_first_p_id, t.t_map_first_post, t.t_status, f.f_id, f.f_password, f.f_tpl, f.f_status, f.f_rules, f.f_approve
						FROM ' . SQL_PREFIX . 'topics t
						LEFT JOIN ' . SQL_PREFIX . 'forums f
							ON t.f_id = f.f_id
						WHERE t.t_id = ' . $this->id;
				$result = Fsb::$db->query($sql);
				$this->data = Fsb::$db->row($result);
				Fsb::$db->free($result);

				if (!$this->data)
				{
					Display::message('topic_not_exists');
				}

				if ($this->data['t_map_first_post'] != MAP_FREE)
				{
					$this->post_map = ($this->data['t_map_first_post'] == MAP_ALL_POST) ? $this->data['t_map'] : 'classic';
				}

				// Peut repondre a ce type de sujet ?
				if (!Fsb::$session->is_authorized($this->data['f_id'], 'ga_answer_' . $GLOBALS['_topic_type'][$this->data['t_type']]))
				{
					if (!Fsb::$session->is_logged())
					{
						Http::redirect(ROOT . 'index.' . PHPEXT . '?p=login&redirect=post&mode=reply&id=' . $this->id);
					}
					else
					{
						Display::message('not_allowed_reply');
					}
				}

				// Forum avec mot de passe ?
				if ($this->data['f_password'] && !Display::forum_password($this->data['f_id'], $this->data['f_password'], ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=reply&amp;id=' . $this->id))
				{
					// L'acces est refuse, on affiche le formulaire du mot de passe et on doit donc quitter la classe
					return (false);
				}

				// On verifie si le message devra etre approuve
				if (($this->data['f_approve'] == IS_NOT_APPROVED || Fsb::$session->data['u_approve'] == IS_NOT_APPROVED) && Fsb::$session->auth() < MODOSUP && !Fsb::$session->is_authorized($this->data['f_id'], 'ga_moderator'))
				{
						$this->approve = IS_NOT_APPROVED;
				}

				// Citation ?
				$this->content = $this->data['_quote_map'] = '';
				if ($this->quote)
				{
					$sql = 'SELECT p_id, p_time, p_text, p_nickname
								FROM ' . SQL_PREFIX . 'posts
								WHERE p_id = ' . intval($this->quote) . '
									AND t_id = ' . $this->id;
					$result = Fsb::$db->query($sql);
					$row = Fsb::$db->row($result);
					Fsb::$db->free($result);

					if ($row && ($this->data['t_map'] == 'classic' || $row['p_id'] != $this->data['t_first_p_id']))
					{
						$this->content = $row['p_text'];
						
						$nickname = str_replace(']', '&#93;', $row['p_nickname']);
						$this->data['_quote_map'] = '[quote=' .  htmlspecialchars($nickname) . ',t=' . $row['p_time'] . ',id=' . $row['p_id'] . ']%s[/quote]';
					}
					else
					{
						$this->quote = false;
					}
				}
			break;

			case 'edit' :
				$sql = 'SELECT p.p_id, p.f_id, p.t_id, p.p_text, p.u_id, p.p_nickname, p.p_map, t.t_title, t.t_map, t.t_type, t.t_first_p_id, t.t_last_p_id, t.t_poll, t.t_map_first_post, t.t_description, t.t_status, f.f_id, f.f_map_default, f.f_password, f.f_tpl, f.f_status, f.f_rules, po.poll_name, po.poll_total_vote, po.poll_max_vote
						FROM ' . SQL_PREFIX . 'posts p
						INNER JOIN ' . SQL_PREFIX . 'topics t
							ON p.t_id = t.t_id
						LEFT JOIN ' . SQL_PREFIX . 'forums f
							ON p.f_id = f.f_id
						LEFT JOIN ' . SQL_PREFIX . 'poll po
							ON po.t_id = p.t_id
						WHERE p_id = ' . $this->id;
				$result = Fsb::$db->query($sql);
				$this->data = Fsb::$db->row($result);
				Fsb::$db->free($result);

				if (!$this->data)
				{
					Display::message('post_not_exists');
				}

				// Peut editer le message ?
				if (!(Fsb::$session->is_authorized($this->data['f_id'], 'ga_edit') && Fsb::$session->id() == $this->data['u_id'] && $this->data['t_status'] == UNLOCK && $this->data['f_status'] == UNLOCK || Fsb::$session->is_authorized($this->data['f_id'], 'ga_moderator')))
				{
					Display::message('not_allowed');
				}

				// Forum avec mot de passe ?
				if ($this->data['f_password'] && !Display::forum_password($this->data['f_id'], $this->data['f_password'], ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=edit&amp;id=' . $this->id))
				{
					// L'acces est refuse, on affiche le formulaire du mot de passe et on doit donc quitter la classe
					return (false);
				}

				// Map du message
				$this->post_map = $this->data['p_map'];
			break;

			case 'topic' :
				// Donnees du forum
				$sql = 'SELECT f_id, f_map_default, f_password, f_tpl, f_map_first_post, f_status, f_rules, f_approve
						FROM ' . SQL_PREFIX . 'forums
						WHERE f_id = ' . $this->id;
				$result = Fsb::$db->query($sql, 'forums_' . $this->id . '_');
				$this->data = Fsb::$db->row($result);
				Fsb::$db->free($result);

				// Map par defaut ?
				if ($this->data['f_map_default'])
				{
					$this->post_map = $this->data['f_map_default'];
				}

				// On verifie si le message devra etre approuve
				if (($this->data['f_approve'] == IS_NOT_APPROVED || Fsb::$session->data['u_approve'] == IS_NOT_APPROVED) && Fsb::$session->auth() < MODOSUP && !Fsb::$session->is_authorized($this->data['f_id'], 'ga_moderator'))
				{
						$this->approve = IS_NOT_APPROVED;
				}

				// On verifie si l'utilisateur peut poster au minimum un type de sujet (sinon erreur)
				$can_post = false;
				foreach ($GLOBALS['_topic_type'] AS $value)
				{
					if (Fsb::$session->is_authorized($this->data['f_id'], 'ga_create_' . $value))
					{
						$can_post = true;
						break;
					}
				}

				if (!$can_post)
				{
					Display::message('not_allowed_create');
				}

				// Forum avec mot de passe ?
				if ($this->data['f_password'] && !Display::forum_password($this->data['f_id'], $this->data['f_password'], ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=topic&amp;id=' . $this->id))
				{
					// L'acces est refuse, on affiche le formulaire du mot de passe et on doit donc quitter la classe
					return (false);
				}
			break;

			case 'calendar_add' :
				$this->post_map = 'classic';

				if (!Fsb::$session->is_authorized('calendar_write'))
				{
					Display::message('not_allowed');
				}
			break;

			case 'calendar_edit' :
				$sql = 'SELECT c_begin, c_end, c_title, c_content, c_view, u_id
						FROM ' . SQL_PREFIX . 'calendar
						WHERE c_id = ' . $this->id;
				$result = Fsb::$db->query($sql);
				$this->data = Fsb::$db->row($result);
				Fsb::$db->free($result);

				if (!Fsb::$session->is_authorized('approve_event') && (Fsb::$session->id() != $this->data['u_id'] || !Fsb::$session->is_logged()))
				{
					Display::message('not_allowed');
				}

				$this->post_map = 'classic';
			break;

			default :
				Display::message('not_allowed');
			break;
		}

		// Theme pour le forum ?
		if (isset($this->data['f_tpl']) && $this->data['f_tpl'])
		{
			$set_tpl = ROOT . 'tpl/' . $this->data['f_tpl'];
			Fsb::$session->data['u_tpl'] = $this->data['f_tpl'];
			Fsb::$tpl->set_template($set_tpl . '/files/', $set_tpl . '/cache/');
		}

		// Peut acceder en lecture a ce forum ?
		if (isset($this->data['f_id']) && (!Fsb::$session->is_authorized($this->data['f_id'], 'ga_view') || !Fsb::$session->is_authorized($this->data['f_id'], 'ga_view_topics') || !Fsb::$session->is_authorized($this->data['f_id'], 'ga_read')))
		{
			if (!Fsb::$session->is_logged())
			{
				Http::redirect(ROOT . 'index.' . PHPEXT . '?p=login&redirect=post&mode=' . $this->mode . '&id=' . $this->id);
			}
			else
			{
				Display::message('not_allowed');
			}
		}

		// Forum verrouille ?
		if (isset($this->data['f_id']) && !Fsb::$session->is_authorized($this->data['f_id'], 'ga_moderator') && ($this->data['f_status'] == LOCK || (isset($this->data['t_status']) && $this->data['t_status'] == LOCK)))
		{
			Display::message('forum_cant_post_is_locked');
		}

		return (true);
	}
	
	/**
	 * Affiche le formulaire permettant de poster des messages
	 *
	 */
	public function show_post_form()
	{		
		Fsb::$tpl->set_file('forum/forum_post.html');
		
		if (count($this->errstr))
		{
			Fsb::$tpl->set_switch('error');
		}

		// Upload de fichier ?
		if (Http::request('submit_upload', 'post') && $_FILES['upload_path']['name'] && Fsb::$session->is_authorized('upload_file') && Fsb::$mods->is_active('upload'))
		{
			$this->upload_file();
		}

		switch ($this->mode)
		{
			// Envoie de message prive
			case 'mp' :
			case 'edit_mp' :
				$page_name = Fsb::$session->lang('post_page_' . $this->mode);

				if ($this->mode == 'mp')
				{
					Fsb::$tpl->set_switch('post_login_to');
				}

				Fsb::$tpl->set_switch('post_title');
				$this->get_mp_data();

				// Envoie de MP a plusieurs destinataires ?
				if (Fsb::$cfg->get('mp_allow_multiple'))
				{
					Fsb::$tpl->set_switch('mp_allow_multiple');
				}
			break;

			// Creation d'un nouveau sujet
			case 'topic' :
				$page_name = Fsb::$session->lang('post_page_topic');

				Fsb::$tpl->set_switch('post_title');
				Fsb::$tpl->set_switch('post_type');
				Fsb::$tpl->set_switch('post_description');
				Fsb::$tpl->set_switch('post_poll');
				if (!Fsb::$session->is_logged())
				{
					Fsb::$tpl->set_switch('post_login');
				}

				// Captcha ?
				if ($this->use_captcha)
				{
					Fsb::$tpl->set_switch('use_captcha');
				}

				// On active la gestion des shemas de sujets
				if (Fsb::$mods->is_active('post_map') && $this->data['f_map_default'] == '0')
				{
					Fsb::$tpl->set_switch('can_change_map');
					$list_shema = $this->list_maps();
				}
				else if (!$this->post_map)
				{
					$this->post_map = 'classic';
				}
			break;

			// Reponse a un sujet existant
			case 'reply' :
				if (!Fsb::$session->is_logged())
				{
					Fsb::$tpl->set_switch('post_login');
				}

				// On active la gestion des shemas de sujets
				if (Fsb::$mods->is_active('post_map') && $this->data['t_map_first_post'] == MAP_FREE)
				{
					Fsb::$tpl->set_switch('can_change_map');
					$list_shema = $this->list_maps();
				}
				else if (!$this->post_map)
				{
					$this->post_map = 'classic';
				}

				// Captcha ?
				if ($this->use_captcha)
				{
					Fsb::$tpl->set_switch('use_captcha');
				}

				// Revue des autres messages du sujet
				$this->topic_review();

				$page_name = Fsb::$session->lang('post_page_reply');
			break;

			// Edition d'un message
			case 'edit' :
				$page_name = Fsb::$session->lang('post_page_edit');

				// Edition du premier message du sujet ?
				if ($this->data['t_first_p_id'] == $this->data['p_id'])
				{
					Fsb::$tpl->set_switch('post_title');
					Fsb::$tpl->set_switch('post_type');
					Fsb::$tpl->set_switch('post_description');

					$this->title = $this->data['t_title'];
					$this->description = $this->data['t_description'];

					// On verifie si le sondage a deja recu des reponses, si ce n'est pas le cas
					// on permet son edition
					if ($this->data['t_poll'] && !$this->data['poll_total_vote'])
					{
						Fsb::$tpl->set_switch('post_poll');
						$this->poll_name = $this->data['poll_name'];
						$this->poll_max_vote = $this->data['poll_max_vote'];

						// Valeurs du sondage
						$sql = 'SELECT poll_opt_name
									FROM ' . SQL_PREFIX . 'poll_options
									WHERE t_id = ' . $this->data['t_id'] . '
									ORDER BY poll_opt_id';
						$result = Fsb::$db->query($sql);

						$this->poll_values = '';
						while ($row = Fsb::$db->row($result))
						{
							$this->poll_values .= $row['poll_opt_name'] . "\n";
						}
						Fsb::$db->free($result);
					}
				}
				
				$this->topic_review($this->id);

				// Contenu du message
				$this->content = $this->data['p_text'];
			break;

			case 'calendar_add' :
			case 'calendar_edit' :
				$page_name = Fsb::$session->lang('post_nav_calendar_add');

				// Titre pour l'evenement
				Fsb::$tpl->set_switch('post_title');

				// Captcha ?
				if ($this->use_captcha)
				{
					Fsb::$tpl->set_switch('use_captcha');
				}

				// Creation des listes
				$list_day = array();
				for ($i = 1; $i <= 31; $i++)
				{
					$list_day[$i] = String::add_zero($i, 2);
				}

				$list_month = array();
				for ($i = 1; $i <= 12; $i++)
				{
					$list_month[$i] = Fsb::$session->lang('month_' . $i);
				}

				$list_year = array();
				for ($i = 1980; $i <= 2030; $i++)
				{
					$list_year[$i] = $i;
				}

				$list_hour = array();
				for ($i = 0; $i <= 23; $i++)
				{
					$list_hour[$i] = String::add_zero($i, 2);
				}
			
				$list_min = array();
				for ($i = 0; $i <= 59; $i++)
				{
					$list_min[$i] = String::add_zero($i, 2);
				}

				// Valeurs par defaut
				if ($this->errstr)
				{
					list($current_begin_day, $current_begin_month, $current_begin_year, $current_begin_hour, $current_begin_min) = explode(' ', date('d n Y H i', $this->calendar['timestamp_begin']));
					list($current_end_day, $current_end_month, $current_end_year, $current_end_hour, $current_end_min) = explode(' ', date('d n Y H i', $this->calendar['timestamp_end']));
					$calendar_print = ($this->calendar['print'] > 0) ? 1 : $this->calendar['print'];
					$current_group = ($this->calendar['print'] > 0) ? $this->calendar['print'] : '';
				}
				else if ($this->preview)
				{
					$current_begin_day =	intval(Http::request('begin_day', 'post'));
					$current_begin_month =	intval(Http::request('begin_month', 'post'));
					$current_begin_year =	intval(Http::request('begin_year', 'post'));
					$current_begin_hour =	intval(Http::request('begin_hour', 'post'));
					$current_begin_min =	intval(Http::request('end_min', 'post'));
					$current_end_day =		intval(Http::request('end_day', 'post'));
					$current_end_month =	intval(Http::request('end_month', 'post'));
					$current_end_year =		intval(Http::request('end_year', 'post'));
					$current_end_hour =		intval(Http::request('end_hour', 'post'));
					$current_end_min =		intval(Http::request('end_min', 'post'));
					$calendar_print =		intval(Http::request('calendar_print', 'post'));
					$current_group =		intval(Http::request('c_groups', 'post'));
				}
				else if ($this->mode == 'calendar_edit')
				{
					list($current_begin_day, $current_begin_month, $current_begin_year, $current_begin_hour, $current_begin_min) = explode(' ', date('d n Y H i', $this->data['c_begin']));
					list($current_end_day, $current_end_month, $current_end_year, $current_end_hour, $current_end_min) = explode(' ', date('d n Y H i', $this->data['c_end']));
					$calendar_print =	($this->data['c_view'] > 0) ? 1 : $this->data['c_view'];
					$current_group =	($this->data['c_view'] > 0) ? $this->data['c_view'] : '';
					$this->title =		$this->data['c_title'];
					$this->content =	$this->data['c_content'];
				}
				else
				{
					list($current_begin_day, $current_begin_month, $current_begin_year) = explode(' ', date('d n Y', CURRENT_TIME));
					$current_begin_hour =	0;
					$current_begin_min =		0;
					$current_end_day =		$current_begin_day;
					$current_end_month =		$current_begin_month;
					$current_end_year =		$current_begin_year;
					$current_end_hour =		$current_begin_hour;
					$current_end_min =		$current_begin_min;
					$calendar_print =		-1;
					$this->content =		$this->title = '';
					$current_group =			'';
				}

				// Donnees pour l'ajout d'evenements
				Fsb::$tpl->set_switch('post_calendar');
				Fsb::$tpl->set_switch('calendar_print');

				Fsb::$tpl->set_vars(array(
					'LIST_CALENDAR_BEGIN_DAY' =>		Html::make_list('begin_day', $current_begin_day, $list_day, array('id' => 'calendar_begin_id')),
					'LIST_CALENDAR_BEGIN_MONTH' =>		Html::make_list('begin_month', $current_begin_month, $list_month),
					'LIST_CALENDAR_BEGIN_YEAR' =>		Html::make_list('begin_year', $current_begin_year, $list_year),
					'LIST_CALENDAR_BEGIN_HOUR' =>		Html::make_list('begin_hour', $current_begin_hour, $list_hour),
					'LIST_CALENDAR_BEGIN_MIN' =>		Html::make_list('begin_min', $current_begin_min, $list_min),
					'LIST_CALENDAR_END_DAY' =>			Html::make_list('end_day', $current_end_day, $list_day, array('id' => 'calendar_end_id')),
					'LIST_CALENDAR_END_MONTH' =>		Html::make_list('end_month', $current_end_month, $list_month),
					'LIST_CALENDAR_END_YEAR' =>			Html::make_list('end_year', $current_end_year, $list_year),
					'LIST_CALENDAR_END_HOUR' =>			Html::make_list('end_hour', $current_end_hour, $list_hour),
					'LIST_CALENDAR_END_MIN' =>			Html::make_list('end_min', $current_end_min, $list_min),
					'LIST_CALENDAR_GROUPS' =>			Html::list_groups('c_groups', GROUP_NORMAL|GROUP_SPECIAL, $current_group),
					'CALENDAR_PRINT' =>					$calendar_print,
				));
			break;
		}

		// Liste des smilies
		Display::smilies();

		// Liste des FSBcode
		Display::fsbcode();

		// Liste des types de sujets
		$this->generate_post_type();

		// Message qui devra etre approuve ?
		if ($this->approve == IS_NOT_APPROVED)
		{
			Fsb::$tpl->set_switch('need_approve');
		}

		// Previsualisation
		if ($this->preview)
		{
			$this->content =		Map::build_map_content($this->post_map);
			$this->title =			trim(Http::request('post_title', 'post'));
			$this->description =	trim(Http::request('post_description', 'post'));
			$this->login = trim(Http::request('post_login', 'post'));

			$parser = new Parser();
			$parser->parse_html = (Fsb::$cfg->get('activate_html') && Fsb::$session->auth() >= MODOSUP) ? true : false;

			// Informations passees au parseur de message
			$parser_info = array(
				'u_id' =>			Fsb::$session->id(),
				'p_nickname' =>		Fsb::$session->data['u_nickname'],
				'u_auth' =>			Fsb::$session->auth(),
			);

			if (isset($this->data['f_id']))
			{
				$parser_info['f_id'] = $this->data['f_id'];
			}

			if (isset($this->data['t_id']))
			{
				$parser_info['t_id'] = $this->data['t_id'];
			}

			if (Http::request('from_quick_reply', 'post') === null)
			{
				Fsb::$tpl->set_switch('preview');
			}

			Fsb::$tpl->set_vars(array(
				'PREVIEW' =>	$parser->mapped_message($this->content, $this->post_map, $parser_info),
				'AVATAR_WIDTH' =>			Fsb::$cfg->get('avatar_width'),
				'AVATAR_HEIGHT' =>			Fsb::$cfg->get('avatar_height'),
				'POST_LOGIN' => $this->login
			));
		}
		else if (Http::request('submit_upload', 'post') && Fsb::$mods->is_active('upload'))
		{
			// En cas d'upload de fichier on reproduit aussi le formulaire
			$this->content = Map::build_map_content($this->post_map);
		}

		// Formulaire de la MAP
		Map::create_form($this->content, $this->post_map, (isset($this->data['_quote_map'])) ? $this->data['_quote_map'] : '', $this->onupload);

		// Navigation de la page
		if (isset($this->data['f_id']))
		{
			$nav_ary = array();

			// En cas de reponse a un sujet on ajoute le titre du sujet dans la barre de navigation, mais on le tronque pour eviter les
			// longueurs execessives. Idem pour l'edition
			if ($this->mode == 'reply' || $this->mode == 'edit')
			{
				$nav_ary[] = array(
					'url' =>	sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $this->data['t_id']),
					'name' =>	(strlen($this->data['t_title']) > 40) ? Parser::title(String::substr($this->data['t_title'], 0, 30) . '(...)') : Parser::title($this->data['t_title']),
				);
			}

			$nav_ary[] = array(
				'url' =>	'',
				'name' =>	$page_name,
			);

			$this->nav = Forum::nav($this->data['f_id'], $nav_ary, $this);
		}
		else
		{
			$this->nav[] = array(
				'url' =>		'',
				'name' =>		$page_name,
			);
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

		// On affiche le formulaire d'upload si le membre a le droit necessaire
		if (Fsb::$session->is_authorized('upload_file') && Fsb::$mods->is_active('upload'))
		{
			Fsb::$tpl->set_switch('can_upload');
		}

		// Regles du forums ?
		$forum_rules = '';
		if (isset($this->data['f_rules']) && !empty($this->data['f_rules']))
		{
			$parser = new Parser();
			$forum_rules = $parser->message($this->data['f_rules']);
			Fsb::$tpl->set_switch('forum_rules');
		}

		// Affichage de la fenetre de creation de sondages
		Poll::display_form($this->post_map, $this->poll_name, $this->poll_values, $this->poll_max_vote);

		Fsb::$tpl->set_vars(array(
			'POST_PAGE_NAME' =>		$page_name,
			'POST_LOGIN_TO' =>		$this->post_login_to,
			'POST_TITLE' =>			Parser::title($this->title),
			'POST_DESCRIPTION' =>	htmlspecialchars($this->description),
			'AVATAR_WIDTH' =>			Fsb::$cfg->get('avatar_width'),
			'AVATAR_HEIGHT' =>			Fsb::$cfg->get('avatar_height'),
			'LIST_SHEMA' =>			(isset($list_shema)) ? $list_shema : '',
			'CONTENT' =>			Html::make_errstr($this->errstr),
			'FORUM_RULES' =>		$forum_rules,
			'CAPTCHA_IMG' =>		sid(ROOT . 'main/visual_confirmation.' . PHPEXT . '?mode=post_captcha&amp;uniqd=' . md5(rand(1, time()))),
			'HIDDEN' =>				Html::hidden('mp_parent', $this->mp_parent),
			'UPLOAD_EXPLAIN' =>		sprintf(Fsb::$session->lang('post_max_filesize'), convert_size(Fsb::$cfg->get('upload_max_filesize')), implode(', ', explode(',', Fsb::$cfg->get('upload_extensions')))),
			'CAN_QUICK_QUOTE' =>	($this->post_map == 'classic') ? true : false,
			'LIST_UPLOAD_AUTH' =>	Html::make_list('upload_auth', Fsb::$session->data['auth']['other']['download_file'][1], $list_upload_auth),

			'U_ACTION' =>			sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=' . $this->mode . '&amp;id=' . $this->id),
		));
	}

	/**
	 * Revue des anciens messages du sujet
	 *
	 * @param int $post_id
	 */
	public function topic_review($post_id = null)
	{
		Fsb::$tpl->set_switch('topic_review');

		$parser = new Parser();

		// On selectionne les anciens messages
		$sql = 'SELECT p.p_id, p.f_id, p.t_id, p.p_text, p.p_time, p.u_id, p.p_nickname, p.p_map, u.u_color, u.u_auth, u.u_avatar, u.u_avatar_method, u.u_activate_avatar
				FROM ' . SQL_PREFIX . 'posts p
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON p.u_id = u.u_id
				WHERE p.t_id = ' . $this->data['t_id'] . '
					' . (($post_id) ? ' AND p.p_id <= ' . $post_id : '') . '
				ORDER BY p.p_time DESC
				LIMIT 15';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			// Parse du HTML ?
			$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;

			// Informations passees au parseur de message
			$parser_info = array(
				'u_id' =>			$row['u_id'],
				'p_nickname' =>		$row['p_nickname'],
				'u_auth' =>			$row['u_auth'],
				'f_id' =>			$row['f_id'],
				't_id' =>			$row['t_id'],
			);

			Fsb::$tpl->set_blocks('post', array(
				'ID' =>				$row['p_id'],
				'NICKNAME' =>		Html::nickname($row['p_nickname'], $row['u_id'], $row['u_color']),
				'DATE' =>			Fsb::$session->print_date($row['p_time']),
				'CONTENT' =>		$parser->mapped_message($row['p_text'], $row['p_map'], $parser_info),
				'USER_AVATAR' =>	sprintf(Fsb::$session->lang('user_avatar'), htmlspecialchars($row['p_nickname'])),

				'U_AVATAR' =>		User::get_avatar($row['u_avatar'], $row['u_avatar_method'], $row['u_activate_avatar']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Revue des precedentes reponses du sujet
	 *
	 * @param int $mp_id ID du message prive auquel on repond
	 * @param int $mp_parent Parent des messages
	 * @param int $mp_time Date du message auquel on repond
	 */
	public function mp_review($mp_id, $mp_parent, $mp_time)
	{
		if ($mp_parent > 0)
		{
			$sql_parent = '(mp.mp_parent = ' . $mp_parent . ' OR mp.mp_id IN (' . $mp_parent . ', ' . ($mp_parent + 1) . '))';
		}
		else
		{
			$sql_parent = 'mp.mp_id = ' . $mp_id;
		}

		$parser = new Parser();

		// On selectionne les anciens messages
		$sql = 'SELECT mp.*, u.u_id, u.u_nickname, u.u_color, u.u_auth, u.u_avatar, u.u_avatar_method, u.u_activate_avatar
				FROM ' . SQL_PREFIX . 'mp mp
				LEFT JOIN ' . SQL_PREFIX . 'users u
					ON u.u_id = mp.mp_from
				WHERE mp.mp_time <= ' . $mp_time . '
					AND ((mp.mp_type = ' . MP_INBOX . ' AND mp.mp_to = ' . Fsb::$session->id() . ')
						OR (mp.mp_type = ' . MP_OUTBOX . ' AND mp.mp_from = ' . Fsb::$session->id() . '))
					AND ' . $sql_parent . '
				ORDER BY mp.mp_time DESC, mp.mp_id ASC';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			// Parse du HTML ?
			$parser->parse_html = (Fsb::$cfg->get('activate_html') && $row['u_auth'] >= MODOSUP) ? true : false;

			// Informations passees au parseur de message
			$parser_info = array(
				'u_id' =>			$row['u_id'],
				'p_nickname' =>		$row['u_nickname'],
				'u_auth' =>			$row['u_auth'],
				'mp_id' =>			$row['mp_id'],
			);

			Fsb::$tpl->set_switch('topic_review');
			Fsb::$tpl->set_blocks('post', array(
				'ID' =>				$row['mp_id'],
				'NICKNAME' =>		Html::nickname($row['u_nickname'], $row['u_id'], $row['u_color']),
				'DATE' =>			Fsb::$session->print_date($row['mp_time']),
				'CONTENT' =>		$parser->mapped_message($row['mp_content'], 'classic', $parser_info),
				'USER_AVATAR' =>	sprintf(Fsb::$session->lang('user_avatar'), htmlspecialchars($row['u_nickname'])),
				'U_AVATAR' =>		User::get_avatar($row['u_avatar'], $row['u_avatar_method'], $row['u_activate_avatar']),
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Genere la liste des types de sujets
	 *
	 */
	public function generate_post_type()
	{
		if (isset($this->data['f_id']))
		{
			krsort($GLOBALS['_topic_type']);
			foreach ($GLOBALS['_topic_type'] AS $key => $value)
			{
				if (Fsb::$session->is_authorized($this->data['f_id'], 'ga_create_' . $value) || ($this->mode == 'edit' && $this->data['t_type'] == $key))
				{
					Fsb::$tpl->set_blocks('topic_type', array(
						'CHECKED' =>	(($this->mode == 'topic' && ((!is_null($this->type) && $this->type == $key) || !isset($GLOBALS['_topic_type'][$key + 1]))) || ($this->mode == 'edit' && $this->data['t_type'] == $key)) ? true : false,
						'VALUE' =>		$key,
						'LANG' =>		Fsb::$session->lang('topic_type_' . $value),
					));
				}
			}
		}
	}
	
	/**
	 * Verification des donnees du formulaire
	 *
	 */
	public function check_form()
	{
		// On recupere sous forme XML le message
		$this->content = Map::build_map_content($this->post_map);

		// Contenu de la page vide, ou inferieur au nombre de caracteres par message
		$classic_content = trim(Http::request('post_map_description', 'post'));
		if ($this->post_map == 'classic' && strlen($classic_content) < Fsb::$cfg->get('post_min_length'))
		{
			$this->errstr[] = sprintf(Fsb::$session->lang('post_need_content'), Fsb::$cfg->get('post_min_length'));
		}

		// Trop de caracteres : le message ne pourra etre insere dans la base de donnee. On limite donc a 65535
		if (strlen($this->content) >= 65535)
		{
			$this->errstr[] = Fsb::$session->lang('post_message_too_big');
		}

		// Message prive
		if ($this->mode == 'mp')
		{
			// On verifie la liste des pseudonymes
			$split = explode("\n", trim($this->post_login_to));
			$to_nickname = array();
			foreach ($split AS $p)
			{
				if ($p)
				{
					$to_nickname[] = Fsb::$db->escape(trim($p));
				}
			}
			$to_nickname = array_flip($to_nickname);

			if ($to_nickname)
			{
				$sql = 'SELECT u_id, u_nickname AS u_nickname
						FROM ' . SQL_PREFIX . 'users
						WHERE u_nickname IN (\'' . implode('\', \'', array_keys($to_nickname)) . '\')';
				$result = Fsb::$db->query($sql);
				$to_nickname = array_flip(array_map(array('String', 'strtolower'), array_flip($to_nickname)));
				while ($row = Fsb::$db->row($result))
				{
					$nick = Fsb::$db->escape(String::strtolower($row['u_nickname']));
					if (isset($to_nickname[$nick]))
					{
						unset($to_nickname[$nick]);
					}
					$this->to_id[] = $row['u_id'];
				}
				Fsb::$db->free($result);

				// Pseudonymes incorects
				foreach (array_keys($to_nickname) AS $nickname)
				{
					$this->errstr[] = sprintf(Fsb::$session->lang('post_nickname_not_exists'), htmlspecialchars($nickname));
				}
			}
			else
			{
				$this->errstr[] = Fsb::$session->lang('post_need_to');
			}

			if (empty($this->title))
			{
				$this->errstr[] = Fsb::$session->lang('post_need_title');
			}
		}
		// Edition MP
		else if ($this->mode == 'edit_mp')
		{
			$sql = 'SELECT mp_id
					FROM ' . SQL_PREFIX . 'mp
					WHERE mp_id = ' . $this->id . '
						AND mp_from = ' . Fsb::$session->id() . '
						AND mp_read = ' . MP_UNREAD . '
						AND mp_type IN (' . MP_OUTBOX . ', ' . MP_SAVE_OUTBOX . ')';
			if (!Fsb::$db->get($sql, 'mp_id'))
			{
				Display::message('not_allowed');
			}

			if (empty($this->title))
			{
				$this->errstr[] = Fsb::$session->lang('post_need_title');
			}
		}
		// Nouveau sujet
		else if ($this->mode == 'topic')
		{
			if (empty($this->title))
			{
				$this->errstr[] = Fsb::$session->lang('post_need_title');
			}

			if ($this->data['f_map_default'])
			{
				$this->post_map = $this->data['f_map_default'];
			}
		}
		// Edition de sujet
		else if ($this->mode == 'edit')
		{
			if ($this->data['t_first_p_id'] == $this->data['p_id'] && empty($this->title))
			{
				$this->errstr[] = Fsb::$session->lang('post_need_title');
			}
		}
		// Calendrier
		else if ($this->mode == 'calendar_add' || $this->mode == 'calendar_edit')
		{
			$begin_day =	intval(Http::request('begin_day', 'post'));
			$begin_month =	intval(Http::request('begin_month', 'post'));
			$begin_year =	intval(Http::request('begin_year', 'post'));
			$begin_hour =	intval(Http::request('begin_hour', 'post'));
			$begin_min =	intval(Http::request('begin_min', 'post'));
			$end_day =		intval(Http::request('end_day', 'post'));
			$end_month =	intval(Http::request('end_month', 'post'));
			$end_year =		intval(Http::request('end_year', 'post'));
			$end_hour =		intval(Http::request('end_hour', 'post'));
			$end_min =		intval(Http::request('end_min', 'post'));

			$this->calendar['timestamp_begin'] = mktime($begin_hour, $begin_min, 0, $begin_month, $begin_day, $begin_year);
			$this->calendar['timestamp_end'] = mktime($end_hour, $end_min, 0, $end_month, $end_day, $end_year);
			$this->calendar['print'] = intval(Http::request('calendar_print', 'post'));
			if ($this->calendar['print'] == 1)
			{
				$this->calendar['print'] = intval(Http::request('c_groups', 'post'));
			}

			if (empty($this->title))
			{
				$this->errstr[] = Fsb::$session->lang('post_need_title');
			}

			// On compare les deux timestamp
			if ($this->calendar['timestamp_begin'] > $this->calendar['timestamp_end'])
			{
				$this->errstr[] = Fsb::$session->lang('post_calendar_bad_timestamp');
			}
		}

		// Type de sujet
		if ($this->mode == 'topic' || ($this->mode == 'edit' && $this->data['t_first_p_id'] == $this->data['p_id']))
		{
			if (is_null($this->type))
			{
				for ($i = count($GLOBALS['_topic_type']) - 1; $i >= 0; $i--)
				{
					if (Fsb::$session->is_authorized($this->data['f_id'], 'ga_create_' . $GLOBALS['_topic_type'][$i]))
					{
						$this->type = $i;
						break;
					}
				}
			}

			if (is_null($this->type))
			{
				Display::message('not_allowed_reply');
			}
		}

		// On tronque le titre s'il est trop grand
		$this->title = Send::truncate_title($this->title);

		// On verifie le code de confirmation visuelle
		if ($this->use_captcha && !check_captcha(Http::request('captcha_code', 'post')))
		{
			$this->errstr[] = Fsb::$session->lang('post_bad_captcha');
		}

		// On verifie qu'il y ait au moins deux reponses pour le sondage, et que chaque reponse
		// n'exede pas 70 caracteres (sinon on la tronque)
		$exp = explode("\n", $this->poll_values);
		$this->poll_values = array();
		foreach ($exp AS $v)
		{
			$v = trim($v);
			if (strlen($v))
			{
				$this->poll_values[] = String::substr($v, 0, 70);
			}
		}
		
		if ($this->poll_name && count($this->poll_values) < 2)
		{
			$this->errstr[] = Fsb::$session->lang('post_poll_more_answer');
		}
		
		// Si le membre est connecte son pseudonyme pour le message sera son vrai pseudonyme
		if (Fsb::$session->is_logged())
		{
			$this->nickname = Fsb::$session->data['u_nickname'];
		}
		// S'il a oublie d'entrer un pseudonyme
		else if (!Fsb::$session->is_logged() && empty($this->nickname))
		{
			$this->nickname = Fsb::$session->lang('visitor');
		}

		// Verification du flood
		if (($this->mode == 'topic' || $this->mode == 'reply') && Fsb::$session->is_logged() && Fsb::$session->auth() < MODOSUP && Fsb::$session->data['u_flood_post'] > CURRENT_TIME - Fsb::$cfg->get('flood_post'))
		{
			$this->errstr[] = sprintf(Fsb::$session->lang('post_flood'), Fsb::$cfg->get('flood_post') - (CURRENT_TIME - Fsb::$session->data['u_flood_post']));
		}
	}
	
	/**
	 * Envoie les donnees
	 *
	 */
	public function send_data()
	{
		switch ($this->mode)
		{
			case 'mp' :
				// On envoie le message prive
				Send::send_mp(Fsb::$session->id(), $this->to_id, $this->title, $this->content, $this->mp_parent, true);

				Display::message('post_mp_well', ROOT . 'index.' . PHPEXT . '?p=mp&amp;box=inbox', 'forum_mp');
			break;

			case 'edit_mp' :
				// On edite le message prive
				Send::edit_mp($this->id, $this->title, $this->content);
				Send::edit_mp($this->id - 1, $this->title, $this->content);

				Display::message('post_mp_well_edit', ROOT . 'index.' . PHPEXT . '?p=mp&amp;id=' . $this->id, 'forum_mp');
			break;

			case 'topic' :
				// Sondage ?
				$poll_exists = ($this->poll_name && $this->poll_values) ? true : false;

				// On poste le nouveau sujet
				$topic_id = Send::send_topic($this->id, Fsb::$session->id(), $this->title, $this->post_map, $this->type, array(
					't_description' =>		$this->description,
					't_poll' =>				(int) $poll_exists,
					't_map_first_post' =>	($this->data['f_map_default'] && $this->data['f_map_first_post'] == MAP_FREE) ? MAP_ALL_POST : $this->data['f_map_first_post'],
					't_approve' =>			$this->approve,
				));

				// On poste le premier message de ce sujet
				Send::send_post(Fsb::$session->id(), $topic_id, $this->id, $this->content, $this->nickname, $this->approve, $this->post_map, array(
					't_type' => (int) $this->type,
					't_title' => $this->title,
				), true);

				// Creation du sondage
				if ($poll_exists)
				{
					Poll::send($this->poll_name, $this->poll_values, $topic_id, $this->poll_max_vote);
				}

				// Surveillance automatique ?
				$this->auto_notification($topic_id);

				Display::message('post_topic_well', ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $topic_id, 'forum_topic', ROOT . '?p=forum&amp;f_id=' . $this->data['f_id'], 'forum_forum');
			break;

			case 'reply' :
				// On poste une reponse dans le sujet
				$post_id = Send::send_post(Fsb::$session->id(), $this->id, $this->data['f_id'], $this->content, $this->nickname, $this->approve, $this->post_map, array(
					't_title' => $this->data['t_title'],
				), false);

				// Surveillance automatique ?
				$this->auto_notification($this->data['t_id']);

				$request_uri = ($this->approve == IS_APPROVED) ? 'p_id=' . $post_id . '#p' . $post_id : 't_id=' . $this->id;
				Display::message('post_topic_create_well', ROOT . 'index.' . PHPEXT . '?p=topic&amp;' . $request_uri, 'forum_post', ROOT . '?p=forum&amp;f_id=' . $this->data['f_id'], 'forum_forum');
			break;

			case 'edit' :
				// On met a jour le message
				$args = array('t_title' => $this->title);

				if ($this->data['p_id'] == $this->data['t_first_p_id'])
				{
					$args = array_merge($args, array(
						'update_topic' =>	true,
						't_type' =>			(int) $this->type,
						't_id' =>			(int) $this->data['t_id'],
						't_description' =>	$this->description
					));
				}

				if ($this->data['p_id'] == $this->data['t_last_p_id'])
				{
					$args = array_merge($args, array(
						'is_last' => true,
						't_id' => (int) $this->data['t_id']
					));
				}

				Send::edit_post($this->id, $this->content, Fsb::$session->id(), $args);

				// Edition du sondage
				if ($this->data['t_poll'] && !$this->data['poll_total_vote'])
				{
					Poll::edit($this->data['t_id'], $this->poll_name, $this->poll_values, $this->poll_max_vote);
				}

				// Log de l'edition du message
				if (Fsb::$session->id() != $this->data['u_id'])
				{
					Log::add(Log::MODO, 'log_edit', $this->data['p_nickname']);
				}

				Display::message('post_topic_edit_well', ROOT . 'index.' . PHPEXT . '?p=topic&amp;p_id=' . $this->id . '#p' . $this->id, 'forum_post_edit', ROOT . '?p=forum&amp;f_id=' . $this->data['f_id'], 'forum_forum');
			break;

			case 'calendar_add' :
				// Ajout du message pour le calendrier
				$event_id = Send::calendar_add_event($this->title, $this->content, $this->calendar['timestamp_begin'], $this->calendar['timestamp_end'], $this->calendar['print']);

				Display::message('post_calendar_well_add', ROOT . 'index.' . PHPEXT . '?p=calendar&amp;time=' . $this->calendar['timestamp_begin'], 'forum_event');
			break;

			case 'calendar_edit' :
				// Edition du message pour le calendrier
				Send::calendar_edit_event($this->id, $this->title, $this->content, $this->calendar['timestamp_begin'], $this->calendar['timestamp_end'], $this->calendar['print']);

				Display::message('post_calendar_well_edit', ROOT . 'index.' . PHPEXT . '?p=calendar&amp;time=' . $this->calendar['timestamp_begin'], 'forum_event');
			break;
		}
	}
	
	/**
	 * Recupere les donnees du message prive en cas de citation / reponse
	 *
	 */
	public function get_mp_data()
	{
		switch ($this->mode)
		{
			case 'edit_mp' :
				// Edition du message prive
				$mp_box = 'outbox';

				$sql = 'SELECT mp_title, mp_content
						FROM ' . SQL_PREFIX . 'mp
						WHERE mp_id = ' . $this->id . '
							AND mp_from = ' . Fsb::$session->id() . '
							AND mp_read = ' . MP_UNREAD . '
							AND mp_type IN (' . MP_OUTBOX . ', ' . MP_SAVE_OUTBOX . ')';
				$result = Fsb::$db->query($sql);
				if (!$this->mp_data = Fsb::$db->row($result))
				{
					Display::message('not_allowed');
				}
				Fsb::$db->free($result);

				$this->title = $this->mp_data['mp_title'];
				$this->content = $this->mp_data['mp_content'];
			break;

			case 'mp' :
				// On recupere les donnees du message prive en cas de reponse
				$mp_box = 'inbox';
				if ($this->id)
				{
					$sql = 'SELECT mp.mp_title, mp.mp_content, mp.mp_type, mp.mp_parent, mp.mp_time, u.u_id, u.u_nickname
							FROM ' . SQL_PREFIX . 'mp mp
							INNER JOIN ' . SQL_PREFIX . 'users u
								ON mp.mp_from = u.u_id
							WHERE mp.mp_id = ' . $this->id . '
								AND mp.mp_to = ' . Fsb::$session->id() . '
								AND mp.mp_type IN (' . MP_INBOX . ', ' . MP_SAVE_INBOX . ')';
					$result = Fsb::$db->query($sql);
					$this->mp_data = Fsb::$db->row($result);
					Fsb::$db->free($result);

					// Si le MP n'a pas ete trouve on ignore la reponse
					if (!$this->mp_data)
					{
						$this->id = null;
					}
					else
					{
						$this->title = ((!preg_match('#^RE ?:#i', $this->mp_data['mp_title'])) ? 'RE : ' : '') . $this->mp_data['mp_title'];
						$this->content = ($this->quote) ? $this->mp_data['mp_content'] : '';
						$this->data['_quote_map'] = ($this->quote) ? '[quote=' . htmlspecialchars($this->mp_data['u_nickname']) . ',t=' . $this->mp_data['mp_time'] . ']%s[/quote]' : '';
						$this->post_login_to = htmlspecialchars($this->mp_data['u_nickname']);
						$this->mp_parent = (!$this->mp_data['mp_parent']) ? $this->id : $this->mp_data['mp_parent'];
						$mp_box = ($this->mp_data['mp_type'] == MP_SAVE_INBOX) ? 'save_inbox' : 'inbox';

						// Visualisation des anciens messages
						$this->mp_review($this->id, $this->mp_data['mp_parent'], $this->mp_data['mp_time']);
					}
				}
				else if ($this->u_id)
				{
					$sql = 'SELECT u_nickname FROM ' . SQL_PREFIX . 'users
							WHERE u_id = ' . $this->u_id . '
								AND u_id <> ' . VISITOR_ID;
					$result = Fsb::$db->query($sql);
					$this->mp_data = Fsb::$db->row($result);
					Fsb::$db->free($result);

					if (!$this->mp_data)
					{
						Display::message('user_not_exists');
					}
					
					$this->post_login_to = htmlspecialchars($this->mp_data['u_nickname']);
				}
			break;
		}

		Display::header_mp($mp_box);
	}

	/**
	 * Upload un fichier sur le serveur
	 *
	 */
	public function upload_file()
	{
		// Upload du fichier
		$upload = new Upload('upload_path');
		$upload_auth = Http::request('upload_auth', 'post');
		if ($upload_auth < Fsb::$session->data['auth']['other']['download_file'][1])
		{
			$upload_auth = Fsb::$session->data['auth']['other']['download_file'][1];
		}
		$id = $upload->attach_file(Fsb::$session->id(), $upload_auth);

		// Code a attacher. S'il s'agit d'une image on l'affiche
		if ($upload->is_img)
		{
			$attach_code = '[attach=' . $id . '] [img:alt=' . strip_tags($upload->basename) . ',title=' . strip_tags($upload->basename) . ']' . Fsb::$cfg->get('fsb_path') . '/index.' . PHPEXT . '?p=download&nocount&id=' . $id . "[/img]\n" . htmlspecialchars($this->upload_comment) . '[/attach]';

			// Editeur wysiwyg ?
			if ($this->post_map == 'classic' && Http::request('post_map_description_hiden', 'post'))
			{
				$attach_code = Parser_wysiwyg::decode($attach_code);
			}
		}
		else
		{
			$attach_code = '[attach=' . $id . ']' . htmlspecialchars($this->upload_comment) . '[/attach]';
		}

		$this->onupload = $attach_code;
	}

	/**
	 * Mise a jour du membre, suivant s'il souhaite ou non utiliser un editeur WYSIWYG
	 *
	 */
	public function update_wysiwyg()
	{
		if (Fsb::$session->is_logged())
		{
			Fsb::$session->data['u_activate_wysiwyg'] = (!is_null(Http::request('set_wysiwyg_on', 'post'))) ? true : false;
			Fsb::$db->update('users', array(
				'u_activate_wysiwyg' =>		Fsb::$session->data['u_activate_wysiwyg'],
			), 'WHERE u_id = ' . Fsb::$session->id());
		}
	}

	/**
	 * Surveillance automatiquement du sujet ?
	 *
	 * @param int $topic_id ID du sujet
	 */
	public function auto_notification($topic_id)
	{
		if (Fsb::$session->data['u_activate_auto_notification'] & NOTIFICATION_AUTO)
		{
			Fsb::$db->insert('topics_notification', array(
				'u_id' =>		array(Fsb::$session->id(), true),
				't_id' =>		array($topic_id, true),
				'tn_status' =>	(Fsb::$session->data['u_activate_auto_notification'] & NOTIFICATION_EMAIL) ? IS_NOT_NOTIFIED : IS_NOTIFIED,
			), 'REPLACE');
		}
	}

	/**
	 * Affiche la liste des fichiers uploades
	 *
	 */
	public function show_upload()
	{
		Fsb::$tpl->set_file('handler_upload.html');
		Fsb::$tpl->set_vars(array(
			'MAP_ID' =>		'map_textarea_' . htmlspecialchars(Http::request('map_name')),
		));

		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'upload
				WHERE u_id = ' . Fsb::$session->id() . '
				ORDER BY upload_filename';
		$result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_blocks('upload', array(
				'ID' =>			$row['upload_id'],
				'REALNAME' =>	$row['upload_realname'],
				'FILESIZE' =>	convert_size($row['upload_filesize']),
				'TIME' =>		Fsb::$session->print_date($row['upload_time']),
				'IS_IMG' =>		(strpos($row['upload_mimetype'], 'image/') !== false) ? 'true' : 'false',
				'PATH' =>		ROOT . 'index.' . PHPEXT . '?p=download&amp;nocount&amp;id=' . $row['upload_id'],
			));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Genere une liste des MAPS
	 *
	 * @return string
	 */
	public function list_maps()
	{
		$list = Html::make_list('post_map', $this->post_map, Map::get_list(), array(
			'onchange' =>	'location.href=\'' . sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=' . $this->mode . '&amp;id=' . $this->id) . '&amp;post_map=' . '\' + this.value',
		));
		return ($list);
	}
}



/* EOF */
