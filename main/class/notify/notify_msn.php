<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

/*
** Implementation du protocole MSNP9 pour se connecter a MSN live messenger.
** Un grand merci a la classe MSNP9.class (http://flumpcakes.co.uk/php/msn-messenger) et au site
** http://www.hypothetic.org/docs/msn/ qui sont deux bonnes mines de diamants dans la comprehension du
** protocole MSNP9.
*/
class Notify_msn extends Fsb_model
{
	// Serveur de connexion
	public $server = 'messenger.hotmail.com';

	// Port de connexion
	public $port = 1863;
	
	// File descriptor du socket de connexion au serveur
	private $socket;
	
	// Id incrementee a chaque commande, pour identifier chaque message
	private $id = 1;
	
	// Dernier buffer lu
	private $buffer = '';

	// Serveur pour recuperer le ticket
	public $ssh_login = 'login.live.com/login2.srf';

	// Identifiants de connexion
	public $email = '';
	public $password = '';

	// Debugage
	public $verbose = false;

	/*
	** Constructeur
	** -----
	** $email ::		Email de connexion (fait office de login)
	** $password ::		Mot de passe
	*/
	public function __construct($email, $password)
	{
		if ($this->verbose)
		{
			ob_implicit_flush();
		}

		$this->email = $email;
		$this->password = $password;
	}

	/*
	** Connection au serveur
	*/
	public function connect()
	{
		// Connexion au serveur
		$errno = 0;
		$errstr = '';
		$this->socket = @fsockopen($this->server, $this->port, $errno, $errstr, 5);
		if (!$this->socket)
		{
			return (false);
		}
		
		// Envoie de la version du protocole
		$this->put('VER ' . $this->id . ' MSNP9 CVR0');
		
		// Reception de la version du protocole
		if (!$this->read() || $this->buffer == 'VER 0 0')
		{
			return (false);
		}
		
		// Envoie des informations sur le client
		$os = (preg_match('/^WIN/', PHP_OS)) ? 'win' : 'unix';
		$this->put('CVR ' . $this->id . ' 0x0409 ' . $os . ' 4.10 i386 FSB 2.0.0 MSMSGS ' . $this->email);
		
		// Reponse du serveur ...
		if (!$this->read())
		{
			return (false);
		}

		// Initialisation de la connexion utilisateur
		$this->put('USR ' . $this->id . ' TWN I ' . $this->email);
		
		// Reponse du serveur ...
		if (!$this->read())
		{
			return (false);
		}

		// Demande de transfert vers une autre IP ?
		if (substr($this->buffer, 0, 3) == 'XFR')
		{
			list(,,,$server_ip) = explode(' ', $this->buffer);
			list($new_ip, $new_port) = explode(':', $server_ip);
			
			// Fermeture de la connection
			$this->close();
			
			// Connection au nouveau serveur
			$this->id = 1;
			$this->server = $new_ip;
			$this->port = $new_port;
			$this->connect();
			return (true);
		}

		// Le serveur nous a accepte, et a envoyer une chaine d'authentification
		list(,,,,$hash) = explode(' ', $this->buffer);

		// On recupere un ticket de connexion
		if (!$ticket = $this->get_ticket($hash))
		{
			return (false);
		}

		// Authentification
		$this->put('USR ' . $this->id . ' TWN S ' . $ticket);

		// Affichage de la presence
		$this->put('SYN ' . $this->id . ' 0');
		$this->put('CHG ' . $this->id . ' NLN');

		// On zappe les reponses :p
		while ($this->read());

		return (true);
	}
	
	/*
	** Recuperation d'un ticket de connexion
	** -----
	** $hash ::		Chaine d'authentification
	*/
	private function get_ticket($hash)
	{
		if (!$this->ssh_login)
		{
			// Connexion au site https://nexus.passport.com/rdr/pprdr.asp pour la recuperation d'une adresse de serveur
			// permettant la creation d'un ticket
			$ch = curl_init('https://nexus.passport.com/rdr/pprdr.asp');
			curl_setopt($ch, CURLOPT_HEADER, 1);
			curl_setopt($ch, CURLOPT_NOBODY, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$header = curl_exec($ch);
			curl_close($ch);

			// Adresse du serveur
			preg_match ('/DALogin=(.*?),/', $header, $match);

			if (isset($match[1]))
			{
				$slogin = $match[1];
			}
			else
			{
				die('Impossible de recuperer un ticket');
			}
		}
		else
		{
			$slogin = $this->ssh_login;
		}

		$ch = curl_init('https://' . $slogin);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Authorization: Passport1.4 OrgVerb=GET,OrgURL=http%3A%2F%2Fmessenger%2Emsn%2Ecom,sign-in=' . $this->email . ',pwd=' . $this->password . ',' . $hash,
			'Host: login.passport.com'
		));
		curl_setopt($ch, CURLOPT_HEADER, 1);
		curl_setopt($ch, CURLOPT_NOBODY, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$header = curl_exec($ch);
		curl_close($ch);

		// On obtiens enfin le ticket de connexion
		preg_match ("/from-PP='(.*?)'/", $header, $match);

		if (isset($match[1]))
		{
			return ($match[1]);
		}
		return (false);
	}

	/*
	** Envoie un message a un destinataire
	** -----
	** $content ::		Contenu du message
	** $to_email ::		Destinataire
	*/
	public function send_message($content, $to_email)
	{
		$this->put('ADD ' . $this->id . ' AL ' . $to_email . ' test');

		$errno = 0;
		$errstr = '';
		$state = false;
		$step = 0;
		while (!feof($this->socket))
		{
			$this->read(false);
			if (!$this->buffer)
			{
				$step++;
			}
			else
			{
				$step = 0;
			}

			// On est definitivement bloque .. Le message n'a pas pu etre envoye.
			if ($step == 20000)
			{
				return (false);
			}

			switch (substr($this->buffer, 0, 3))
			{
				case 'ILN':
					// Envoie du message au client
					$stop_cycle = 0;
					do
					{
						$this->put('XFR ' . $this->id . ' SB');
						$this->read();
						if (substr($this->buffer, 0, 3) == 'XFR')
						{
							break;
						}
						$this->put('CHG ' . $this->id . ' NLN');
						$stop_cycle++;
					}
					while ($stop_cycle < 10);

					// Erreur de preparation du message
					if ($stop_cycle == 10)
					{
						return (false);
					}

					// Connection au switchboard
					list(,,,$ip,,$hash) = explode(' ', $this->buffer);
					list($server_ip, $server_port) = explode(':', $ip);
					$msn = new Msn('', '');
					$msn->socket = fsockopen($server_ip, $server_port, $errno, $errstr, 5);
					$msn->put('USR ' . $msn->id . ' ' . $this->email . ' ' . $hash);
					$msn->read();

					// Invitation du destinataire
					$msn->put('CAL ' . $msn->id . ' ' . $to_email);
					$msn->read();
					$msn->read();

					// Envoie du message
					$command = "\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nX-MMS-IM-Format: FN=MS%20Sans%20Serif; EF=; CO=0; CS=0; PF=0\r\n\r\n$content";
					$add = 'MSG ' . $msn->id . " N ";
					$command = $add . (strlen($add) + strlen($command) - 10) . $command;
					$msn->put($command);

					// Fermeture du switchboard
					$msn->close();
					unset($msn);

					$state = true;
				break 2;
			}
		}

		return ($state);
	}
	
	/*
	** Ferme la connexion au serveur
	*/
	public function close()
	{
		fclose($this->socket);
	}
	
	/*
	** Ecrit des donnees sur le socket du serveur
	** -----
	** $str ::	Donnees a envoyer
	*/
	private function put($str)
	{
		if ($this->verbose)
		{
			echo '<span style="color: green">' . $str . '</span><br />';
		}

		fwrite($this->socket, $str . "\r\n");
		$this->id++;
	}
	
	/*
	** Lecture du socket
	*/
	private function read($block_mode = true)
	{
		// Mode non bloquant
		stream_set_blocking($this->socket, $block_mode);

		// Lecture de la socket
		$this->buffer = fgets($this->socket, 4096);
		if ($this->buffer)
		{
			$this->buffer = trim($this->buffer);
		}

		if ($this->verbose && $this->buffer)
		{
			echo '<span style="color: red">' . $this->buffer . '</span><br />';
		}

		// Mode bloquant
		if (!$block_mode)
		{
			stream_set_blocking($this->socket, true);
		}

		return (($this->buffer) ? true : false);
	}
}

/* EOF */