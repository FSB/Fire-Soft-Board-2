<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/notify/notify_msn.php
** | Begin :	16/11/2006
** | Last :		16/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Implémentation du protocole MSNP9 pour se connecter à MSN live messenger.
** Un grand merci à la classe MSNP9.class (http://flumpcakes.co.uk/php/msn-messenger) et au site
** http://www.hypothetic.org/docs/msn/ qui sont deux bonnes mines de diamants dans la compréhension du
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
	
	// Id incrémentée à chaque commande, pour identifier chaque message
	private $id = 1;
	
	// Dernier buffer lu
	private $buffer = '';

	// Serveur pour récupérer le ticket
	public $ssh_login = 'login.live.com/login2.srf';

	// Identifiants de connexion
	public $email = '';
	public $password = '';

	// Débugage
	public $verbose = FALSE;

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
			return (FALSE);
		}
		
		// Envoie de la version du protocole
		$this->put('VER ' . $this->id . ' MSNP9 CVR0');
		
		// Reception de la version du protocole
		if (!$this->read() || $this->buffer == 'VER 0 0')
		{
			return (FALSE);
		}
		
		// Envoie des informations sur le client
		$os = (preg_match('/^WIN/', PHP_OS)) ? 'win' : 'unix';
		$this->put('CVR ' . $this->id . ' 0x0409 ' . $os . ' 4.10 i386 FSB 2.0.0 MSMSGS ' . $this->email);
		
		// Réponse du serveur ...
		if (!$this->read())
		{
			return (FALSE);
		}

		// Initialisation de la connexion utilisateur
		$this->put('USR ' . $this->id . ' TWN I ' . $this->email);
		
		// Réponse du serveur ...
		if (!$this->read())
		{
			return (FALSE);
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
			return (TRUE);
		}

		// Le serveur nous a accepté, et a envoyer une chaine d'authentification
		list(,,,,$hash) = explode(' ', $this->buffer);

		// On récupère un ticket de connexion
		if (!$ticket = $this->get_ticket($hash))
		{
			return (FALSE);
		}

		// Authentification
		$this->put('USR ' . $this->id . ' TWN S ' . $ticket);

		// Affichage de la présence
		$this->put('SYN ' . $this->id . ' 0');
		$this->put('CHG ' . $this->id . ' NLN');

		// On zappe les réponses :p
		while ($this->read());

		return (TRUE);
	}
	
	/*
	** Récupération d'un ticket de connexion
	** -----
	** $hash ::		Chaine d'authentification
	*/
	private function get_ticket($hash)
	{
		if (!$this->ssh_login)
		{
			// Connexion au site https://nexus.passport.com/rdr/pprdr.asp pour la récupération d'une adresse de serveur
			// permettant la création d'un ticket
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
				die('Impossible de récupérer un ticket');
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
		return (FALSE);
	}

	/*
	** Envoie un message à un destinataire
	** -----
	** $content ::		Contenu du message
	** $to_email ::		Destinataire
	*/
	public function send_message($content, $to_email)
	{
		$this->put('ADD ' . $this->id . ' AL ' . $to_email . ' test');

		$errno = 0;
		$errstr = '';
		$state = FALSE;
		$step = 0;
		while (!feof($this->socket))
		{
			$this->read(FALSE);
			if (!$this->buffer)
			{
				$step++;
			}
			else
			{
				$step = 0;
			}

			// On est définitivement bloqué .. Le message n'a pas pu être envoyé.
			if ($step == 20000)
			{
				return (FALSE);
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

					// Erreur de préparation du message
					if ($stop_cycle == 10)
					{
						return (FALSE);
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

					$state = TRUE;
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
	** Ecrit des données sur le socket du serveur
	** -----
	** $str ::	Données à envoyer
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
	private function read($block_mode = TRUE)
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
			stream_set_blocking($this->socket, TRUE);
		}

		return (($this->buffer) ? TRUE : FALSE);
	}
}

/* EOF */