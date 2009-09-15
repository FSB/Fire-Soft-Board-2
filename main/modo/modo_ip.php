<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On affiche ce module suivant l'autorisation du membre
if (Fsb::$session->is_authorized('auth_ip'))
{
	$show_this_module = true;
}

/**
 * Module de moderation pour la recherche sur les IP
 *
 */
class Page_modo_ip extends Fsb_model
{
	/**
	 * ID du message
	 *
	 * @var int
	 */
	public $id;

	/**
	 * IP
	 *
	 * @var string
	 */
	public $ip;

	/**
	 * ID du membre
	 *
	 * @var int
	 */
	public $u_id;
	
	/**
	 * Pseudonyme
	 *
	 * @var string
	 */
	public $nickname = null;

	/**
	 * Constructeur
	 *
	 */
	public function __construct()
	{
		$this->id =			intval(Http::request('id', 'post|get'));
		$this->u_id =		intval(Http::request('u_id', 'post|get'));
		$this->ip =			Http::request('ip', 'post|get');
		$this->nickname =	Http::request('nickname', 'post|get');

		$this->get_data();
		$this->search_ip_form();
		if ($this->id || $this->ip || $this->u_id)
		{
			$this->print_result_ip();
		}
	}

	/**
	 * Recupere les informations sur le pseudonyme cherche
	 *
	 */
	public function get_data()
	{
		if ($this->u_id)
		{
			$sql = 'SELECT u_nickname
					FROM ' . SQL_PREFIX . 'users
					WHERE u_id = ' . $this->u_id;
			$this->nickname = Fsb::$db->get($sql, 'u_nickname');
		}
		else if ($this->nickname)
		{
			$sql = 'SELECT u_id
					FROM ' . SQL_PREFIX . 'users
					WHERE u_nickname = \'' . Fsb::$db->escape($this->nickname) . '\'';
			$this->u_id = Fsb::$db->get($sql, 'u_id');
		}
	}

	/**
	 * Affiche le formulaire de recherche d'IP
	 *
	 */
	public function search_ip_form()
	{
		Fsb::$tpl->set_file('modo/modo_ip.html');
		Fsb::$tpl->set_vars(array(
			'THIS_ID' =>		$this->id,
			'THIS_IP' =>		$this->ip,
			'THIS_NICKNAME' =>	htmlspecialchars($this->nickname),

			'U_ACTION' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=ip'),
		));
	}

	/**
	 * Affiche le resultat de la recherche sur une IP
	 *
	 */
	public function print_result_ip()
	{
		if ($this->u_id)
		{
			$show_user = true;
			$show_other = false;

			$sql = 'SELECT u_nickname
					FROM ' . SQL_PREFIX . 'users
					WHERE u_id = ' . $this->u_id;
			$result = Fsb::$db->query($sql);
			$data = Fsb::$db->row($result);
			Fsb::$db->free($result);
			$p_nickname = $data['u_nickname'];
		}
		else if ($this->ip)
		{
			$show_user = false;
			$show_other = true;
			$p_nickname = '';
			$this->ip = $this->ip;
		}
		else
		{
			$show_user = true;
			$show_other = true;

			// Donnees du message
			$sql = 'SELECT u_id, u_ip, p_nickname
					FROM ' . SQL_PREFIX . 'posts
					WHERE p_id = ' . $this->id;
			$result = Fsb::$db->query($sql);
			$data = Fsb::$db->row($result);
			Fsb::$db->free($result);

			// Message non existant ?
			if (!$data)
			{
				Display::message('post_not_exists');
			}

			$this->u_id = $data['u_id'];
			$this->ip = $data['u_ip'];
			$p_nickname = $data['p_nickname'];
		}

		Fsb::$tpl->set_switch('show_result');
		Fsb::$tpl->set_vars(array(
			'L_MODO_IP_USER' =>		sprintf(Fsb::$session->lang('modo_ip_user'), htmlspecialchars($p_nickname)),
			'L_MODO_IP_OTHER' =>	sprintf(Fsb::$session->lang('modo_ip_other'), htmlspecialchars($this->ip)),
			'L_MODO_IP_SESSION' =>	sprintf(Fsb::$session->lang('modo_ip_session'), htmlspecialchars($this->ip)),
			'L_MODO_IP_REGISTER' =>	sprintf(Fsb::$session->lang('modo_ip_register'), htmlspecialchars($this->ip)),
		));

		if ($show_user)
		{
			Fsb::$tpl->set_switch('show_result_user');

			// Liste des IP du membre
			$sql = 'SELECT COUNT(u_ip) AS total, u_ip
						FROM ' . SQL_PREFIX . 'posts
						WHERE u_id = ' . $this->u_id . '
						GROUP BY u_ip';
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				Fsb::$tpl->set_blocks('ip', array(
					'IP' =>		$row['u_ip'],
					'TOTAL' =>	$row['total'],

					'U_IP' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=ip&amp;ip=' . $row['u_ip']),
				));
			}
			Fsb::$db->free($result);

			// Liste des IP du membre
			$sql = 'SELECT COUNT(u_ip) AS total, u_ip
						FROM ' . SQL_PREFIX . 'mp
						WHERE mp_from = ' . $this->u_id . '
							AND u_ip <> \'\'
						GROUP BY u_ip';
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				Fsb::$tpl->set_blocks('ip', array(
					'IP' =>		$row['u_ip'],
					'TOTAL' =>	Fsb::$session->lang('mp_panel') . ' - ' . $row['total'],

					'U_IP' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=ip&amp;ip=' . $row['u_ip']),
				));
			}
			Fsb::$db->free($result);
		}

		if ($show_other)
		{
			Fsb::$tpl->set_switch('show_result_other');
			Fsb::$tpl->set_switch('show_result_session');
			Fsb::$tpl->set_switch('show_result_register');

			// Liste des membres utilisant l'IP
			$sql = 'SELECT COUNT(p_id) AS total, u_id, p_nickname
					FROM ' . SQL_PREFIX . 'posts
					WHERE u_ip = \'' . $this->ip . '\'
						' . (($this->u_id) ? ' AND u_id <> ' . $this->u_id : '') . '
					GROUP BY u_id';
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				Fsb::$tpl->set_blocks('other', array(
					'LOGIN' =>		htmlspecialchars($row['p_nickname']),
					'TOTAL' =>		$row['total'],

					'U_LOGIN' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=ip&amp;u_id=' . $row['u_id']),
				));
			}
			Fsb::$db->free($result);

			// Liste des membres connectes utilisant l'IP
			$sql = 'SELECT u.u_id, u.u_nickname
					FROM ' . SQL_PREFIX . 'sessions s
					LEFT JOIN ' . SQL_PREFIX . 'users u
						ON u.u_id = s.s_id
					WHERE s.s_ip = \'' . $this->ip . '\'
						AND s.s_id <> ' . VISITOR_ID . '
					GROUP BY s.s_ip';
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				Fsb::$tpl->set_blocks('session', array(
					'LOGIN' =>		htmlspecialchars($row['u_nickname']),

					'U_LOGIN' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=ip&amp;u_id=' . $row['u_id']),
				));
			}
			Fsb::$db->free($result);

			// Liste des membres connectes enregistres sous l'IP
			$sql = 'SELECT u_id, u_nickname
					FROM ' . SQL_PREFIX . 'users u
					WHERE u_register_ip = \'' . $this->ip . '\'
						AND u_id <> ' . VISITOR_ID;
			$result = Fsb::$db->query($sql);
			while ($row = Fsb::$db->row($result))
			{
				Fsb::$tpl->set_blocks('register', array(
					'LOGIN' =>		htmlspecialchars($row['u_nickname']),

					'U_LOGIN' =>	sid(ROOT . 'index.' . PHPEXT . '?p=modo&amp;module=ip&amp;u_id=' . $row['u_id']),
				));
			}
			Fsb::$db->free($result);
		}
	}
}

/* EOF */