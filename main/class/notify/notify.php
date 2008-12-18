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
 * Gestion d'envoie de messages de notification en differe.
 * Mail / SMTP supportés, MSN et Jabber sont mis de côtés pour le moment et ne fonctionnent pas correctement.
 */
class Notify extends Fsb_model
{
	/**
	 * Nombre maximum d'essaie de renvoie d'une notification si elle echoue
	 */
	const MAX_TRY = 3;

	/**
	 * Methode d'envoie de notification
	 *
	 * @var int
	 */
	private $method =	NOTIFY_MAIL;
	
	/**
	 * Liste des destinataires
	 *
	 * @var array
	 */
	private $bcc =		array();
	
	/**
	 * Sujet de la notification
	 *
	 * @var string
	 */
	private $subject =	'';
	
	/**
	 * Contenu de la notification
	 *
	 * @var string
	 */
	private $body =		'';
	
	/**
	 * Variables a parser pour le message de notification
	 *
	 * @var string
	 */
	private $vars =		array();
	
	/**
	 * Methodes d'envoie de notification
	 *
	 * @var array
	 */
	private $ext =		array(NOTIFY_MAIL => true, NOTIFY_MSN => true, NOTIFY_JABBER => true);

	/**
	 * Constructeur, determine la methode de notification
	 *
	 * @param int $method Choix de la methode de notification
	 */
	public function __construct($method = NOTIFY_MAIL)
	{
		if (!Fsb::$cfg->get('jabber_notify_enabled') || !function_exists('fsockopen'))
		{
			unset($this->ext[NOTIFY_JABBER]);
		}
	
		if (!Fsb::$cfg->get('msn_notify_enabled') || !function_exists('fsockopen') || !extension_loaded('curl'))
		{
			unset($this->ext[NOTIFY_MSN]);
		}
		$this->method = (isset($this->ext[$method])) ? $method : NOTIFY_MAIL;
	}

	/**
	 * Ajout de destinataires
	 *
	 * @param string $addr Adresse du destinataire
	 */
	public function add_bcc($addr)
	{
		$this->bcc[] = $addr;
	}

	/**
	 * Selection du template pour le contenu du message
	 *
	 * @param string $template Chemin vers le template
	 */
	public function set_template($template)
	{
		// On regarde si une modification du template existe
		$updated_template = dirname($template) . '/' . get_file_data(basename($template), 'filename') . '.updated';
		if (file_exists($updated_template))
		{
			$template = $updated_template;
		}

		if (!file_exists($template))
		{
			trigger_error('Le template ' . $template . ' n\'existe pas', FSB_ERROR);
		}
		
		// Recuperation du contenu du template
		$this->body = file_get_contents($template);
	}

	/**
	 * Ajout de variables de template
	 *
	 * @param array $vars Liste des variables de template
	 */
	public function set_vars($vars)
	{
		$this->vars = array_merge($this->vars, $vars);
	}

	/**
	 * Assigne le sujet de la notification
	 *
	 * @param string $subject
	 */
	public function set_subject($subject)
	{
		if (!$subject)
		{
			$subject = Fsb::$session->lang('no_subject');
		}
		$this->subject = $subject;
	}
	
	/**
	 * Parse le contenu du message avec les variables de template
	 */
	public function parse_body()
	{
		foreach ($this->vars AS $key => $value)
		{
			$this->body = str_replace('{' . $key . '}', $value, $this->body);
		}

		// Pas de HTML pour MSN / Jabber
		if ($this->method == NOTIFY_MSN || $this->method == NOTIFY_JABBER)
		{
			$this->body = preg_replace('#</?[^>]+?>#si', '', $this->body);
			$this->body = str_replace(array("\r\n", "\r"), array("\n", "\n"), $this->body);
			$this->body = str_replace("\n", "\r\n", $this->body);

			if ($this->method == NOTIFY_JABBER)
			{
				$this->body = htmlspecialchars($this->body);
			}
		}
	}

	/**
	 * Envoie le message
	 *
	 * @param bool $parse_body true si on doit parser le texte a partir des variables de templates
	 * @return bool Succes ou non de l'envoie du message
	 */
	public function send($parse_body = true)
	{
		if ($parse_body)
		{
			$this->parse_body();
		}
		$result = false;
		
		// Envoie par Email via la classe PHPmailer ...
		if ($this->method == NOTIFY_MAIL)
		{
			$mail = new Notify_mail();
			foreach ($this->bcc AS $bcc)
			{
				if ($bcc)
				{
					$mail->AddBCC($bcc);
				}
			}

			$mail->Subject = $this->subject;
			$mail->Body = $this->body;
			$result = $mail->Send();
			$mail->SmtpClose();
			unset($mail);
		}

		// Envoie via le protocole Jabber ...
		if ($this->method == NOTIFY_JABBER)
		{
			// Connexion au serveur Jabber
			$jabber = new Notify_jabber();
			$jabber->server = Fsb::$cfg->get('jabber_notify_server');
			$jabber->port = Fsb::$cfg->get('jabber_notify_port');
			$jabber->username = Fsb::$cfg->get('jabber_notify_email');
			$jabber->password = Fsb::$cfg->get('jabber_notify_password');
			if ($jabber->Connect() && $jabber->SendAuth())
			{
				$jabber->SendPresence(null, null, "online");

				// Envoie du message
				foreach ($this->bcc AS $bcc)
				{
					$result = $jabber->SendMessage($bcc, 'normal', null, array(
						'body' =>	$this->body,
					)); 
				}
				$jabber->Disconnect();
			}
			else
			{
				$result = false;
			}
		}

		// Envoie via le protocole MSN ...
		if ($this->method == NOTIFY_MSN)
		{
			
			$msn = new Notify_msn(Fsb::$cfg->get('msn_notify_email'), Fsb::$cfg->get('msn_notify_password'));
			$result = $msn->connect();

			// On envoie une notification MSN message, compte par compte
			if ($result)
			{
				foreach ($this->bcc AS $bcc)
				{
					$result = $msn->send_message($this->subject . " :\r\n" . $this->body, $bcc);
				}
			}
			$msn->close();
			unset($msn);
		}
		
		return ($result);
	}
	
	/**
	 * Ajout du message a la liste des notifications
	 */
	public function put()
	{
		$this->parse_body();
		Fsb::$db->insert('notify', array(
			'notify_method' =>	$this->method,
			'notify_time' =>	CURRENT_TIME,
			'notify_subject' =>	$this->subject,
			'notify_body' =>	$this->body,
			'notify_bcc' =>		implode("\n", $this->bcc),
			'notify_try' =>		0,
		));
		Fsb::$db->destroy_cache('notify_');
	}
	
	/**
	 * Remise a zero des informations
	 */
	public function reset()
	{
		$this->method = NOTIFY_MAIL;
		$this->body = '';
		$this->subject = '';
		$this->bcc = array();
		$this->vars = array();
	}
	
	/**
	 * Envoie toutes les notifications en attente
	 */
	public function send_queue()
	{
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'notify
				ORDER BY notify_time';
		$result = Fsb::$db->query($sql, 'notify_');
		if ($row = Fsb::$db->row($result))
		{
			// Suppression des elements directement, afin d'eviter un double envoie
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'notify';
			Fsb::$db->query($sql);
			Fsb::$db->destroy_cache('notify_');

			// Envoie du message
			do
			{
				$this->method = (isset($this->ext[$row['notify_method']])) ? $row['notify_method'] : NOTIFY_MAIL;
				$this->subject = $row['notify_subject'];
				$this->body = $row['notify_body'];
				$this->bcc = explode("\n", $row['notify_bcc']);
				$return = $this->send(false);
				$this->reset();

				// En cas d'echec du message on le reinsere dans la base de donnee
				if (!$return && $row['notify_try'] < Notify::MAX_TRY)
				{
					$row['notify_try']++;
					unset($row['notify_id']);
					foreach ($row AS $k => $v)
					{
						if (is_numeric($k))
						{
							unset($row[$k]);
						}
					}
					Fsb::$db->insert('notify', $row, 'INSERT', true);

				}
			}
			while ($row = Fsb::$db->row($result));
			Fsb::$db->query_multi_insert();
		}
		Fsb::$db->free($result);
	}
}

/* EOF */