<?php

/***************************************************************************

	Class.Jabber.PHP v0.4.2
	(c) 2004 Nathan "Fritzy" Fritz
	http://cjphp.netflint.net *** fritzy@netflint.net

	This is a bugfix version, specifically for those who can't get 
	0.4 to work on Jabberd2 servers. 

	last modified: 24.03.2004 13:01:53 

 ***************************************************************************/

/***************************************************************************
 *

 *
 ***************************************************************************/

/*
	Jabber::Connect()
	Jabber::Disconnect()
	Jabber::SendAuth()
	Jabber::AccountRegistration($reg_email {string}, $reg_name {string})

	Jabber::Listen()
	Jabber::SendPacket($xml {string})

	Jabber::RosterUpdate()
	Jabber::RosterAddUser($jid {string}, $id {string}, $name {string})
	Jabber::RosterRemoveUser($jid {string}, $id {string})
	Jabber::RosterExistsJID($jid {string})

	Jabber::Subscribe($jid {string})
	Jabber::Unsubscribe($jid {string})

	Jabber::CallHandler($message {array})
	Jabber::CruiseControl([$seconds {number}])

	Jabber::SubscriptionApproveRequest($to {string})
	Jabber::SubscriptionDenyRequest($to {string})

	Jabber::GetFirstFromQueue()
	Jabber::GetFromQueueById($packet_type {string}, $id {string})

	Jabber::SendMessage($to {string}, $id {number}, $type {string}, $content {array}[, $payload {array}])
 	Jabber::SendIq($to {string}, $type {string}, $id {string}, $xmlns {string}[, $payload {string}])
	Jabber::SendPresence($type {string}[, $to {string}[, $status {string}[, $show {string}[, $priority {number}]]]])

	Jabber::SendError($to {string}, $id {string}, $error_number {number}[, $error_message {string}])

	Jabber::TransportRegistrationDetails($transport {string})
	Jabber::TransportRegistration($transport {string}, $details {array})

	Jabber::GetvCard($jid {string}[, $id {string}])	-- EXPERIMENTAL --

	Jabber::GetInfoFromMessageFrom($packet {array})
	Jabber::GetInfoFromMessageType($packet {array})
	Jabber::GetInfoFromMessageId($packet {array})
	Jabber::GetInfoFromMessageThread($packet {array})
	Jabber::GetInfoFromMessageSubject($packet {array})
	Jabber::GetInfoFromMessageBody($packet {array})
	Jabber::GetInfoFromMessageError($packet {array})

	Jabber::GetInfoFromIqFrom($packet {array})
	Jabber::GetInfoFromIqType($packet {array})
	Jabber::GetInfoFromIqId($packet {array})
	Jabber::GetInfoFromIqKey($packet {array})
 	Jabber::GetInfoFromIqError($packet {array})

	Jabber::GetInfoFromPresenceFrom($packet {array})
	Jabber::GetInfoFromPresenceType($packet {array})
	Jabber::GetInfoFromPresenceStatus($packet {array})
	Jabber::GetInfoFromPresenceShow($packet {array})
	Jabber::GetInfoFromPresencePriority($packet {array})

	Jabber::AddToLog($string {string})
	Jabber::PrintLog()

	MakeXML::AddPacketDetails($string {string}[, $value {string/number}])
	MakeXML::BuildPacket([$array {array}])
*/



class Notify_jabber
{
	public $server;
	public $port;
	public $username;
	public $password;
	public $resource;
	public $jid;

	public $connection;
	public $delay_disconnect;

	public $stream_id;
	public $roster;

	public $enable_logging;
	public $log_array;
	public $log_filename;
	public $log_filehandler;

	public $iq_sleep_timer;
	public $last_ping_time;

	public $packet_queue;
	public $subscription_queue;

	public $iq_version_name;
	public $iq_version_os;
	public $iq_version_version;

	public $error_codes;

	public $connected;
	public $keep_alive_id;
	public $returned_keep_alive;
	public $txnid;

	public $CONNECTOR;



	public function __construct()
	{
		$this->server				= "localhost";
		$this->port					= "5222";

		$this->username				= "larry";
		$this->password				= "curly";
		$this->resource				= null;

		$this->enable_logging		= false;
		$this->log_array			= array();
		$this->log_filename			= '';
		$this->log_filehandler		= false;

		$this->packet_queue			= array();
		$this->subscription_queue	= array();

		$this->iq_sleep_timer		= 1;
		$this->delay_disconnect		= 1;

		$this->returned_keep_alive	= true;
		$this->txnid				= 0;

		$this->iq_version_name		= "Class.Jabber.PHP -- http://cjphp.netflint.net -- by Nathan 'Fritzy' Fritz, fritz@netflint.net";
		$this->iq_version_version	= "0.4";
		$this->iq_version_os		= $_SERVER['SERVER_SOFTWARE'];

		$this->connection_class		= "CJP_StandardConnector";

		$this->error_codes			= array(400 => "Bad Request",
											401 => "Unauthorized",
											402 => "Payment Required",
											403 => "Forbidden",
											404 => "Not Found",
											405 => "Not Allowed",
											406 => "Not Acceptable",
											407 => "Registration Required",
											408 => "Request Timeout",
											409 => "Conflict",
											500 => "Internal Server Error",
											501 => "Not Implemented",
											502 => "Remove Server Error",
											503 => "Service Unavailable",
											504 => "Remove Server Timeout",
											510 => "Disconnected");
	}



	public function Connect()
	{
		$this->_create_logfile();

		$this->CONNECTOR = new $this->connection_class;

		if ($this->CONNECTOR->OpenSocket($this->server, $this->port))
		{
			$this->SendPacket("<?xml version='1.0' encoding='UTF-8' ?" . ">\n");
			$this->SendPacket("<stream:stream to='{$this->server}' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>\n");

			sleep(2);

			if ($this->_check_connected())
			{
				$this->connected = true;	// Nathan Fritz
				return true;
			}
			else
			{
				$this->AddToLog("ERROR: Connect() #1");
				return false;
			}
		}
		else
		{
			$this->AddToLog("ERROR: Connect() #2");
			return false;
		}
	}



	public function Disconnect()
	{
		if (is_int($this->delay_disconnect))
		{
			sleep($this->delay_disconnect);
		}

		$this->SendPacket("</stream:stream>");
		$this->CONNECTOR->CloseSocket();

		$this->_close_logfile();
		$this->PrintLog();
	}



	public function SendAuth()
	{
		$this->auth_id	= "auth_" . md5(time() . $_SERVER['REMOTE_ADDR']);

		$this->resource	= ($this->resource != null) ? $this->resource : ("Class.Jabber.PHP " . md5($this->auth_id));
		$this->jid		= "{$this->username}@{$this->server}/{$this->resource}";

		// request available authentication methods
		$payload	= "<username>{$this->username}</username>";
		$packet		= $this->SendIq(null, 'get', $this->auth_id, "jabber:iq:auth", $payload);

		// was a result returned?
		if ($this->GetInfoFromIqType($packet) == 'result' && $this->GetInfoFromIqId($packet) == $this->auth_id)
		{
			// yes, now check for auth method availability in descending order (best to worst)

			if (!function_exists("mhash"))
			{
				$this->AddToLog("ATTENTION: SendAuth() - mhash() is not available; screw 0k and digest method, we need to go with plaintext auth");
			}

			// auth_0k
			if (function_exists("mhash") && isset($packet['iq']['#']['query'][0]['#']['sequence'][0]["#"]) && isset($packet['iq']['#']['query'][0]['#']['token'][0]["#"]))
			{
				return $this->_sendauth_0k($packet['iq']['#']['query'][0]['#']['token'][0]["#"], $packet['iq']['#']['query'][0]['#']['sequence'][0]["#"]);
			}
			// digest
			elseif (function_exists("mhash") && isset($packet['iq']['#']['query'][0]['#']['digest']))
			{
				return $this->_sendauth_digest();
			}
			// plain text
			elseif ($packet['iq']['#']['query'][0]['#']['password'])
			{
				return $this->_sendauth_plaintext();
			}
			// dude, you're fucked
			{
				$this->AddToLog("ERROR: SendAuth() #2 - No auth method available!");
				return false;
			}
		}
		else
		{
			// no result returned
			$this->AddToLog("ERROR: SendAuth() #1");
			return false;
		}
	}



	public function AccountRegistration($reg_email = null, $reg_name = null)
	{
		$packet = $this->SendIq($this->server, 'get', 'reg_01', 'jabber:iq:register');

		if ($packet)
		{
			$key = $this->GetInfoFromIqKey($packet);	// just in case a key was passed back from the server
			unset($packet);

			$payload = "<username>{$this->username}</username>
						<password>{$this->password}</password>
						<email>$reg_email</email>
						<name>$reg_name</name>\n";

			$payload .= ($key) ? "<key>$key</key>\n" : '';

			$packet = $this->SendIq($this->server, 'set', "reg_01", "jabber:iq:register", $payload);

			if ($this->GetInfoFromIqType($packet) == 'result')
			{
				if (isset($packet['iq']['#']['query'][0]['#']['registered'][0]['#']))
				{
					$return_code = 1;
				}
				else
				{
					$return_code = 2;
				}

				if ($this->resource)
				{
					$this->jid = "{$this->username}@{$this->server}/{$this->resource}";
				}
				else
				{
					$this->jid = "{$this->username}@{$this->server}";
				}

			}
			elseif ($this->GetInfoFromIqType($packet) == 'error' && isset($packet['iq']['#']['error'][0]['#']))
			{
				// "conflict" error, i.e. already registered
				if ($packet['iq']['#']['error'][0]['@']['code'] == '409')
				{
					$return_code = 1;
				}
				else
				{
					$return_code = "Error " . $packet['iq']['#']['error'][0]['@']['code'] . ": " . $packet['iq']['#']['error'][0]['#'];
				}
			}

			return $return_code;

		}
		else
		{
			return 3;
		}
	}



	public function SendPacket($xml)
	{
		$xml = trim($xml);

		if ($this->CONNECTOR->WriteToSocket($xml))
		{
			$this->AddToLog("SEND: $xml");
			return true;
		}
		else
		{
			$this->AddToLog('ERROR: SendPacket() #1');
			return false;
		}
	}



	public function Listen()
	{
		unset($incoming);

		$incoming = '';
		while ($line = $this->CONNECTOR->ReadFromSocket(4096))
		{
			$incoming .= $line;
		}

		$incoming = trim($incoming);

		if ($incoming != "")
		{
			$this->AddToLog("RECV: $incoming");
		}

		if ($incoming != "")
		{
			$temp = $this->_split_incoming($incoming);

			for ($a = 0; $a < count($temp); $a++)
			{
				$this->packet_queue[] = $this->xmlize($temp[$a]);
			}
		}

		return true;
	}



	public function StripJID($jid = null)
	{
		preg_match("/(.*)\/(.*)/Ui", $jid, $temp);
		return ($temp[1] != "") ? $temp[1] : $jid;
	}



	public function SendMessage($to, $type = "normal", $id = null, $content = null, $payload = null)
	{
		if ($to && is_array($content))
		{
			if (!$id)
			{
				$id = $type . "_" . time();
			}

			$content = $this->_array_htmlspecialchars($content);

			$xml = "<message to='$to' type='$type' id='$id'>\n";

			if (isset($content['subject']))
			{
				$xml .= "<subject>" . $content['subject'] . "</subject>\n";
			}

			if (isset($content['thread']))
			{
				$xml .= "<thread>" . $content['thread'] . "</thread>\n";
			}

			$xml .= "<body>" . $content['body'] . "</body>\n";
			$xml .= $payload;
			$xml .= "</message>\n";


			if ($this->SendPacket($xml))
			{
				return true;
			}
			else
			{
				$this->AddToLog("ERROR: SendMessage() #1");
				return false;
			}
		}
		else
		{
			$this->AddToLog("ERROR: SendMessage() #2");
			return false;
		}
	}



	public function SendPresence($type = null, $to = null, $status = null, $show = null, $priority = null)
	{
		$xml = "<presence";
		$xml .= ($to) ? " to='$to'" : '';
		$xml .= ($type) ? " type='$type'" : '';
		$xml .= ($status || $show || $priority) ? ">\n" : " />\n";

		$xml .= ($status) ? "	<status>$status</status>\n" : '';
		$xml .= ($show) ? "	<show>$show</show>\n" : '';
		$xml .= ($priority) ? "	<priority>$priority</priority>\n" : '';

		$xml .= ($status || $show || $priority) ? "</presence>\n" : '';

		if ($this->SendPacket($xml))
		{
			return true;
		}
		else
		{
			$this->AddToLog("ERROR: SendPresence() #1");
			return false;
		}
	}



	public function SendError($to, $id = null, $error_number, $error_message = null)
	{
		$xml = "<iq type='error' to='$to'";
		$xml .= ($id) ? " id='$id'" : '';
		$xml .= ">\n";
		$xml .= "	<error code='$error_number'>";
		$xml .= ($error_message) ? $error_message : $this->error_codes[$error_number];
		$xml .= "</error>\n";
		$xml .= "</iq>";

		$this->SendPacket($xml);
	}



	public function RosterUpdate()
	{
		$roster_request_id = "roster_" . time();

		$incoming_array = $this->SendIq(null, 'get', $roster_request_id, "jabber:iq:roster");

		if (is_array($incoming_array))
		{
			if ($incoming_array['iq']['@']['type'] == 'result'
				&& $incoming_array['iq']['@']['id'] == $roster_request_id
				&& $incoming_array['iq']['#']['query']['0']['@']['xmlns'] == "jabber:iq:roster")
			{
				$number_of_contacts = count($incoming_array['iq']['#']['query'][0]['#']['item']);
				$this->roster = array();

				for ($a = 0; $a < $number_of_contacts; $a++)
				{
					$this->roster[$a] = array(	"jid"			=> strtolower($incoming_array['iq']['#']['query'][0]['#']['item'][$a]['@']['jid']),
												"name"			=> $incoming_array['iq']['#']['query'][0]['#']['item'][$a]['@']['name'],
												"subscription"	=> $incoming_array['iq']['#']['query'][0]['#']['item'][$a]['@']['subscription'],
												"group"			=> $incoming_array['iq']['#']['query'][0]['#']['item'][$a]['#']['group'][0]['#']
											);
				}

				return true;
			}
			else
			{
				$this->AddToLog("ERROR: RosterUpdate() #1");
				return false;
			}
		}
		else
		{
			$this->AddToLog("ERROR: RosterUpdate() #2");
			return false;
		}
	}



	public function RosterAddUser($jid = null, $id = null, $name = null)
	{
		$id = ($id) ? $id : "adduser_" . time();

		if ($jid)
		{
			$payload = "		<item jid='$jid'";
			$payload .= ($name) ? " name='" . htmlspecialchars($name) . "'" : '';
			$payload .= "/>\n";

			$packet = $this->SendIq(null, 'set', $id, "jabber:iq:roster", $payload);

			if ($this->GetInfoFromIqType($packet) == 'result')
			{
				$this->RosterUpdate();
				return true;
			}
			else
			{
				$this->AddToLog("ERROR: RosterAddUser() #2");
				return false;
			}
		}
		else
		{
			$this->AddToLog("ERROR: RosterAddUser() #1");
			return false;
		}
	}



	public function RosterRemoveUser($jid = null, $id = null)
	{
		$id = ($id) ? $id : 'deluser_' . time();

		if ($jid && $id)
		{
			$packet = $this->SendIq(null, 'set', $id, "jabber:iq:roster", "<item jid='$jid' subscription='remove'/>");

			if ($this->GetInfoFromIqType($packet) == 'result')
			{
				$this->RosterUpdate();
				return true;
			}
			else
			{
				$this->AddToLog("ERROR: RosterRemoveUser() #2");
				return false;
			}
		}
		else
		{
			$this->AddToLog("ERROR: RosterRemoveUser() #1");
			return false;
		}
	}



	public function RosterExistsJID($jid = null)
	{
		if ($jid)
		{
			if ($this->roster)
			{
				for ($a = 0; $a < count($this->roster); $a++)
				{
					if ($this->roster[$a]['jid'] == strtolower($jid))
					{
						return $a;
					}
				}
			}
			else
			{
				$this->AddToLog("ERROR: RosterExistsJID() #2");
				return false;
			}
		}
		else
		{
			$this->AddToLog("ERROR: RosterExistsJID() #1");
			return false;
		}
	}



	public function GetFirstFromQueue()
	{
		return array_shift($this->packet_queue);
	}



	public function GetFromQueueById($packet_type, $id)
	{
		$found_message = false;

		foreach ($this->packet_queue as $key => $value)
		{
			if ($value[$packet_type]['@']['id'] == $id)
			{
				$found_message = $value;
				unset($this->packet_queue[$key]);

				break;
			}
		}

		return (is_array($found_message)) ? $found_message : false;
	}



	public function CallHandler($packet = null)
	{
		$packet_type	= $this->_get_packet_type($packet);

		if ($packet_type == "message")
		{
			$type		= $packet['message']['@']['type'];
			$type		= ($type != "") ? $type : "normal";
			$funcmeth	= "Handler_message_$type";
		}
		elseif ($packet_type == "iq")
		{
			$namespace	= $packet['iq']['#']['query'][0]['@']['xmlns'];
			$namespace	= str_replace(":", "_", $namespace);
			$funcmeth	= "Handler_iq_$namespace";
		}
		elseif ($packet_type == "presence")
		{
			$type		= $packet['presence']['@']['type'];
			$type		= ($type != "") ? $type : "available";
			$funcmeth	= "Handler_presence_$type";
		}


		if ($funcmeth != '')
		{
			if (function_exists($funcmeth))
			{
				call_user_func($funcmeth, $packet);
			}
			elseif (method_exists($this, $funcmeth))
			{
				call_user_func(array(&$this, $funcmeth), $packet);
			}
			else
			{
				$this->Handler_NOT_IMPLEMENTED($packet);
				$this->AddToLog("ERROR: CallHandler() #1 - neither method nor function $funcmeth() available");
			}
		}
	}



	public function CruiseControl($seconds = -1)
	{
		$count = 0;

		while ($count != $seconds)
		{
			$this->Listen();

			do {
				$packet = $this->GetFirstFromQueue();

				if ($packet) {
					$this->CallHandler($packet);
				}

			} while (count($this->packet_queue) > 1);

			$count += 1;
			sleep(1);
			
			if ($this->last_ping_time + 180 < time())
			{
				// Modified by Nathan Fritz
				if ($this->returned_keep_alive == false)
				{
					$this->connected = false;
					$this->AddToLog('EVENT: Disconnected');
				}
				if ($this->returned_keep_alive == true)
				{
					$this->connected = true;
				}

				$this->returned_keep_alive = false;
				$this->keep_alive_id = 'keep_alive_' . time();
				//$this->SendPacket("<iq id='{$this->keep_alive_id}'/>", 'CruiseControl');
				$this->SendPacket("<iq type='get' from='" . $this->username . "@" . $this->server . "/" . $this->resource . "' to='" . $this->server . "' id='" . $this->keep_alive_id . "'><query xmlns='jabber:iq:time' /></iq>");
				// **

				$this->last_ping_time = time();
			}
		}

		return true;
	}



	public function SubscriptionAcceptRequest($to = null)
	{
		return ($to) ? $this->SendPresence("subscribed", $to) : false;
	}



	public function SubscriptionDenyRequest($to = null)
	{
		return ($to) ? $this->SendPresence("unsubscribed", $to) : false;
	}



	public function Subscribe($to = null)
	{
		return ($to) ? $this->SendPresence("subscribe", $to) : false;
	}



	public function Unsubscribe($to = null)
	{
		return ($to) ? $this->SendPresence("unsubscribe", $to) : false;
	}



	public function SendIq($to = null, $type = 'get', $id = null, $xmlns = null, $payload = null, $from = null)
	{
		if (!preg_match("/^(get|set|result|error)$/", $type))
		{
			unset($type);

			$this->AddToLog("ERROR: SendIq() #2 - type must be 'get', 'set', 'result' or 'error'");
			return false;
		}
		elseif ($id && $xmlns)
		{
			$xml = "<iq type='$type' id='$id'";
			$xml .= ($to) ? " to='" . htmlspecialchars($to) . "'" : '';
			$xml .= ($from) ? " from='$from'" : '';
			$xml .= ">
						<query xmlns='$xmlns'>
							$payload
						</query>
					</iq>";

			$this->SendPacket($xml);
			sleep($this->iq_sleep_timer);
			$this->Listen();

			return (preg_match("/^(get|set)$/", $type)) ? $this->GetFromQueueById("iq", $id) : true;
		}
		else
		{
			$this->AddToLog("ERROR: SendIq() #1 - to, id and xmlns are mandatory");
			return false;
		}
	}



	// get the transport registration fields
	// method written by Steve Blinch, http://www.blitzaffe.com 
	public function TransportRegistrationDetails($transport)
	{
		$this->txnid++;
		$packet = $this->SendIq($transport, 'get', "reg_{$this->txnid}", "jabber:iq:register", null, $this->jid);

		if ($packet)
		{
			$res = array();

			foreach ($packet['iq']['#']['query'][0]['#'] as $element => $data)
			{
				if ($element != 'instructions' && $element != 'key')
				{
					$res[] = $element;
				}
			}

			return $res;
		}
		else
		{
			return 3;
		}
	}
	


	// register with the transport
	// method written by Steve Blinch, http://www.blitzaffe.com 
	public function TransportRegistration($transport, $details)
	{
		$this->txnid++;
		$packet = $this->SendIq($transport, 'get', "reg_{$this->txnid}", "jabber:iq:register", null, $this->jid);

		if ($packet)
		{
			$key = $this->GetInfoFromIqKey($packet);	// just in case a key was passed back from the server
			unset($packet);
		
			$payload = ($key) ? "<key>$key</key>\n" : '';
			foreach ($details as $element => $value)
			{
				$payload .= "<$element>$value</$element>\n";
			}
		
			$packet = $this->SendIq($transport, 'set', "reg_{$this->txnid}", "jabber:iq:register", $payload);
		
			if ($this->GetInfoFromIqType($packet) == 'result')
			{
				if (isset($packet['iq']['#']['query'][0]['#']['registered'][0]['#']))
				{
					$return_code = 1;
				}
				else
				{
					$return_code = 2;
				}
			}
			elseif ($this->GetInfoFromIqType($packet) == 'error')
			{
				if (isset($packet['iq']['#']['error'][0]['#']))
				{
					$return_code = "Error " . $packet['iq']['#']['error'][0]['@']['code'] . ": " . $packet['iq']['#']['error'][0]['#'];
					$this->AddToLog('ERROR: TransportRegistration()');
				}
			}

			return $return_code;
		}
		else
		{
			return 3;
		}
	}



	public function GetvCard($jid = null, $id = null)
	{
		if (!$id)
		{
			$id = "vCard_" . md5(time() . $_SERVER['REMOTE_ADDR']);
		}

		if ($jid)
		{
			$xml = "<iq type='get' to='$jid' id='$id'>
						<vCard xmlns='vcard-temp'/>
					</iq>";

			$this->SendPacket($xml);
			sleep($this->iq_sleep_timer);
			$this->Listen();

			return $this->GetFromQueueById("iq", $id);
		}
		else
		{
			$this->AddToLog("ERROR: GetvCard() #1 - to and id are mandatory");
			return false;
		}
	}



	public function PrintLog()
	{
		if ($this->enable_logging)
		{
			if ($this->log_filehandler)
			{
				echo "<h2>Logging enabled, logged events have been written to the file {$this->log_filename}.</h2>\n";
			}
			else
			{
				echo "<h2>Logging enabled, logged events below:</h2>\n";
				echo "<pre>\n";
				echo (count($this->log_array) > 0) ? implode("\n\n\n", $this->log_array) : "No logged events.";
				echo "</pre>\n";
			}
		}
	}



	// ======================================================================
	// private methods
	// ======================================================================



	public function _sendauth_0k($zerok_token, $zerok_sequence)
	{
		// initial hash of password
		$zerok_hash = mhash(MHASH_SHA1, $this->password);
		$zerok_hash = bin2hex($zerok_hash);

		// sequence 0: hash of hashed-password and token
		$zerok_hash = mhash(MHASH_SHA1, $zerok_hash . $zerok_token);
		$zerok_hash = bin2hex($zerok_hash);

		// repeat as often as needed
		for ($a = 0; $a < $zerok_sequence; $a++)
		{
			$zerok_hash = mhash(MHASH_SHA1, $zerok_hash);
			$zerok_hash = bin2hex($zerok_hash);
		}

		$payload = "<username>{$this->username}</username>
					<hash>$zerok_hash</hash>
					<resource>{$this->resource}</resource>";

		$packet = $this->SendIq(null, 'set', $this->auth_id, "jabber:iq:auth", $payload);

		// was a result returned?
		if ($this->GetInfoFromIqType($packet) == 'result' && $this->GetInfoFromIqId($packet) == $this->auth_id)
		{
			return true;
		}
		else
		{
			$this->AddToLog("ERROR: _sendauth_0k() #1");
			return false;
		}
	}



	public function _sendauth_digest()
	{
		$payload = "<username>{$this->username}</username>
					<resource>{$this->resource}</resource>
					<digest>" . bin2hex(mhash(MHASH_SHA1, $this->stream_id . $this->password)) . "</digest>";

		$packet = $this->SendIq(null, 'set', $this->auth_id, "jabber:iq:auth", $payload);

		// was a result returned?
		if ($this->GetInfoFromIqType($packet) == 'result' && $this->GetInfoFromIqId($packet) == $this->auth_id)
		{
			return true;
		}
		else
		{
			$this->AddToLog("ERROR: _sendauth_digest() #1");
			return false;
		}
	}



	public function _sendauth_plaintext()
	{
		$payload = "<username>{$this->username}</username>
					<password>{$this->password}</password>
					<resource>{$this->resource}</resource>";

		$packet = $this->SendIq(null, 'set', $this->auth_id, "jabber:iq:auth", $payload);

		// was a result returned?
		if ($this->GetInfoFromIqType($packet) == 'result' && $this->GetInfoFromIqId($packet) == $this->auth_id)
		{
			return true;
		}
		else
		{
			$this->AddToLog("ERROR: _sendauth_plaintext() #1");
			return false;
		}
	}



	public function _listen_incoming()
	{
		unset($incoming);

		$incoming = '';
		while ($line = $this->CONNECTOR->ReadFromSocket(4096))
		{
			$incoming .= $line;
		}

		$incoming = trim($incoming);

		if ($incoming != "")
		{
			$this->AddToLog("RECV: $incoming");
		}

		return $this->xmlize($incoming);
	}



	public function _check_connected()
	{
		$incoming_array = $this->_listen_incoming();

		if (is_array($incoming_array))
		{
			if ($incoming_array["stream:stream"]['@']['from'] == $this->server
				&& $incoming_array["stream:stream"]['@']['xmlns'] == "jabber:client"
				&& $incoming_array["stream:stream"]['@']["xmlns:stream"] == "http://etherx.jabber.org/streams")
			{
				$this->stream_id = $incoming_array["stream:stream"]['@']['id'];

				if ($incoming_array["stream:stream"]["#"]["stream:features"][0]["#"]["starttls"][0]["@"]["xmlns"] == "urn:ietf:params:xml:ns:xmpp-tls")
				{
					return $this->_starttls();
				}
				else
				{
					return true;
				}
			}
			else
			{
				$this->AddToLog("ERROR: _check_connected() #1");
				return false;
			}
		}
		else
		{
			$this->AddToLog("ERROR: _check_connected() #2");
			return false;
		}
	}



	public function _starttls()
	{
		if (!function_exists("stream_socket_enable_crypto"))
		{
			$this->AddToLog("WARNING: TLS is not available");
			return true;
		}

		$this->SendPacket("<starttls xmlns='urn:ietf:params:xml:ns:xmpp-tls'/>\n");
		sleep(2);
		$incoming_array = $this->_listen_incoming();

		if (!is_array($incoming_array))
		{
			$this->AddToLog("ERROR: _starttls() #1");
			return false;
		}

		if ($incoming_array["proceed"]["@"]["xmlns"] != "urn:ietf:params:xml:ns:xmpp-tls")
		{
			$this->AddToLog("ERROR: _starttls() #2");
			return false;
		}

		$meta = stream_get_meta_data($this->CONNECTOR->active_socket);
		socket_set_blocking($this->CONNECTOR->active_socket, 1);
		if (!@stream_socket_enable_crypto($this->CONNECTOR->active_socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT))
		{
			socket_set_blocking($this->CONNECTOR->active_socket, $meta["blocked"]);
			$this->AddToLog("ERROR: _starttls() #3");
			return false;
		}
		socket_set_blocking($this->CONNECTOR->active_socket, $meta["blocked"]);

		$this->SendPacket("<?xml version='1.0' encoding='UTF-8' ?" . ">\n");
		$this->SendPacket("<stream:stream to='{$this->server}' xmlns='jabber:client' xmlns:stream='http://etherx.jabber.org/streams' version='1.0'>\n");
		sleep(2);

		if (!$this->_check_connected())
		{
			$this->AddToLog("ERROR: _starttls() #4");
			return false;
		}

		return true;
	}



	public function _get_packet_type($packet = null)
	{
		if (is_array($packet))
		{
			reset($packet);
			$packet_type = key($packet);
		}

		return ($packet_type) ? $packet_type : false;
	}



	public function _split_incoming($incoming)
	{
		$temp = preg_split("/<(message|iq|presence|stream)/", $incoming, -1, PREG_SPLIT_DELIM_CAPTURE);
		$array = array();

		for ($a = 1; $a < count($temp); $a = $a + 2)
		{
			$array[] = "<" . $temp[$a] . $temp[($a + 1)];
		}

		return $array;
	}



	public function _create_logfile()
	{
		if ($this->log_filename != '' && $this->enable_logging)
		{
			$this->log_filehandler = fopen($this->log_filename, 'w');
		}
	}



	public function AddToLog($string)
	{
		if ($this->enable_logging)
		{
			if ($this->log_filehandler)
			{
				#fwrite($this->log_filehandler, $string . "\n\n");
				print "$string \n\n";
			}
			else
			{
				$this->log_array[] = htmlspecialchars($string);
			}
		}
	}



	public function _close_logfile()
	{
		if ($this->log_filehandler)
		{
			fclose($this->log_filehandler);
		}
	}



	// _array_htmlspecialchars()
	// applies htmlspecialchars() to all values in an array

	public function _array_htmlspecialchars($array)
	{
		if (is_array($array))
		{
			foreach ($array as $k => $v)
			{
				if (is_array($v))
				{
					$v = $this->_array_htmlspecialchars($v);
				}
				else
				{
					$v = htmlspecialchars($v);
				}
			}
		}

		return $array;
	}



	// ======================================================================
	// <message/> parsers
	// ======================================================================



	public function GetInfoFromMessageFrom($packet = null)
	{
		return (is_array($packet)) ? $packet['message']['@']['from'] : false;
	}



	public function GetInfoFromMessageType($packet = null)
	{
		return (is_array($packet)) ? $packet['message']['@']['type'] : false;
	}



	public function GetInfoFromMessageId($packet = null)
	{
		return (is_array($packet)) ? $packet['message']['@']['id'] : false;
	}



	public function GetInfoFromMessageThread($packet = null)
	{
		return (is_array($packet)) ? $packet['message']['#']['thread'][0]['#'] : false;
	}



	public function GetInfoFromMessageSubject($packet = null)
	{
		return (is_array($packet)) ? $packet['message']['#']['subject'][0]['#'] : false;
	}



	public function GetInfoFromMessageBody($packet = null)
	{
		return (is_array($packet)) ? $packet['message']['#']['body'][0]['#'] : false;
	}

	public function GetInfoFromMessageXMLNS($packet = null)
	{
		return (is_array($packet)) ? $packet['message']['#']['x'] : false;
	}



	public function GetInfoFromMessageError($packet = null)
	{
		$error = preg_replace("/^\/$/", "", ($packet['message']['#']['error'][0]['@']['code'] . "/" . $packet['message']['#']['error'][0]['#']));
		return (is_array($packet)) ? $error : false;
	}



	// ======================================================================
	// <iq/> parsers
	// ======================================================================



	public function GetInfoFromIqFrom($packet = null)
	{
		return (is_array($packet)) ? $packet['iq']['@']['from'] : false;
	}



	public function GetInfoFromIqType($packet = null)
	{
		return (is_array($packet)) ? $packet['iq']['@']['type'] : false;
	}



	public function GetInfoFromIqId($packet = null)
	{
		return (is_array($packet)) ? $packet['iq']['@']['id'] : false;
	}



	public function GetInfoFromIqKey($packet = null)
	{
		return (is_array($packet)) ? $packet['iq']['#']['query'][0]['#']['key'][0]['#'] : false;
	}



	public function GetInfoFromIqError($packet = null)
	{
		$error = preg_replace("/^\/$/", "", ($packet['iq']['#']['error'][0]['@']['code'] . "/" . $packet['iq']['#']['error'][0]['#']));
		return (is_array($packet)) ? $error : false;
	}



	// ======================================================================
	// <presence/> parsers
	// ======================================================================



	public function GetInfoFromPresenceFrom($packet = null)
	{
		return (is_array($packet)) ? $packet['presence']['@']['from'] : false;
	}



	public function GetInfoFromPresenceType($packet = null)
	{
		return (is_array($packet)) ? $packet['presence']['@']['type'] : false;
	}



	public function GetInfoFromPresenceStatus($packet = null)
	{
		return (is_array($packet)) ? $packet['presence']['#']['status'][0]['#'] : false;
	}



	public function GetInfoFromPresenceShow($packet = null)
	{
		return (is_array($packet)) ? $packet['presence']['#']['show'][0]['#'] : false;
	}



	public function GetInfoFromPresencePriority($packet = null)
	{
		return (is_array($packet)) ? $packet['presence']['#']['priority'][0]['#'] : false;
	}



	// ======================================================================
	// <message/> handlers
	// ======================================================================



	public function Handler_message_normal($packet)
	{
		$from = $packet['message']['@']['from'];
		$this->AddToLog("EVENT: Message (type normal) from $from");
	}



	public function Handler_message_chat($packet)
	{
		$from = $packet['message']['@']['from'];
		$this->AddToLog("EVENT: Message (type chat) from $from");
	}



	public function Handler_message_groupchat($packet)
	{
		$from = $packet['message']['@']['from'];
		$this->AddToLog("EVENT: Message (type groupchat) from $from");
	}



	public function Handler_message_headline($packet)
	{
		$from = $packet['message']['@']['from'];
		$this->AddToLog("EVENT: Message (type headline) from $from");
	}



	public function Handler_message_error($packet)
	{
		$from = $packet['message']['@']['from'];
		$this->AddToLog("EVENT: Message (type error) from $from");
	}



	// ======================================================================
	// <iq/> handlers
	// ======================================================================



	// application version updates
	public function Handler_iq_jabber_iq_autoupdate($packet)
	{
		$from	= $this->GetInfoFromIqFrom($packet);
		$id		= $this->GetInfoFromIqId($packet);

		$this->SendError($from, $id, 501);
		$this->AddToLog("EVENT: jabber:iq:autoupdate from $from");
	}



	// interactive server component properties
	public function Handler_iq_jabber_iq_agent($packet)
	{
		$from	= $this->GetInfoFromIqFrom($packet);
		$id		= $this->GetInfoFromIqId($packet);

		$this->SendError($from, $id, 501);
		$this->AddToLog("EVENT: jabber:iq:agent from $from");
	}



	// method to query interactive server components
	public function Handler_iq_jabber_iq_agents($packet)
	{
		$from	= $this->GetInfoFromIqFrom($packet);
		$id		= $this->GetInfoFromIqId($packet);

		$this->SendError($from, $id, 501);
		$this->AddToLog("EVENT: jabber:iq:agents from $from");
	}



	// simple client authentication
	public function Handler_iq_jabber_iq_auth($packet)
	{
		$from	= $this->GetInfoFromIqFrom($packet);
		$id		= $this->GetInfoFromIqId($packet);

		$this->SendError($from, $id, 501);
		$this->AddToLog("EVENT: jabber:iq:auth from $from");
	}



	// out of band data
	public function Handler_iq_jabber_iq_oob($packet)
	{
		$from	= $this->GetInfoFromIqFrom($packet);
		$id		= $this->GetInfoFromIqId($packet);

		$this->SendError($from, $id, 501);
		$this->AddToLog("EVENT: jabber:iq:oob from $from");
	}



	// method to store private data on the server
	public function Handler_iq_jabber_iq_private($packet)
	{
		$from	= $this->GetInfoFromIqFrom($packet);
		$id		= $this->GetInfoFromIqId($packet);

		$this->SendError($from, $id, 501);
		$this->AddToLog("EVENT: jabber:iq:private from $from");
	}



	// method for interactive registration
	public function Handler_iq_jabber_iq_register($packet)
	{
		$from	= $this->GetInfoFromIqFrom($packet);
		$id		= $this->GetInfoFromIqId($packet);

		$this->SendError($from, $id, 501);
		$this->AddToLog("EVENT: jabber:iq:register from $from");
	}



	// client roster management
	public function Handler_iq_jabber_iq_roster($packet)
	{
		$from	= $this->GetInfoFromIqFrom($packet);
		$id		= $this->GetInfoFromIqId($packet);

		$this->SendError($from, $id, 501);
		$this->AddToLog("EVENT: jabber:iq:roster from $from");
	}



	// method for searching a user database
	public function Handler_iq_jabber_iq_search($packet)
	{
		$from	= $this->GetInfoFromIqFrom($packet);
		$id		= $this->GetInfoFromIqId($packet);

		$this->SendError($from, $id, 501);
		$this->AddToLog("EVENT: jabber:iq:search from $from");
	}



	// method for requesting the current time
	public function Handler_iq_jabber_iq_time($packet)
	{
		if ($this->keep_alive_id == $this->GetInfoFromIqId($packet))
		{
			$this->returned_keep_alive = true;
			$this->connected = true;
			$this->AddToLog('EVENT: Keep-Alive returned, connection alive.');
		}
		$type	= $this->GetInfoFromIqType($packet);
		$from	= $this->GetInfoFromIqFrom($packet);
		$id		= $this->GetInfoFromIqId($packet);
		$id		= ($id != "") ? $id : "time_" . time();

		if ($type == 'get')
		{
			$payload = "<utc>" . gmdate("Ydm\TH:i:s") . "</utc>
						<tz>" . date("T") . "</tz>
						<display>" . date("Y/d/m h:i:s A") . "</display>";

			$this->SendIq($from, 'result', $id, "jabber:iq:time", $payload);
		}

		$this->AddToLog("EVENT: jabber:iq:time (type $type) from $from");
	}

	public function Handler_iq_error($packet)
	{
		// We'll do something with these later.  This is a placeholder so that errors don't bounce back and forth.
	}



	// method for requesting version
	public function Handler_iq_jabber_iq_version($packet)
	{
		$type	= $this->GetInfoFromIqType($packet);
		$from	= $this->GetInfoFromIqFrom($packet);
		$id		= $this->GetInfoFromIqId($packet);
		$id		= ($id != "") ? $id : "version_" . time();

		if ($type == 'get')
		{
			$payload = "<name>{$this->iq_version_name}</name>
						<os>{$this->iq_version_os}</os>
						<version>{$this->iq_version_version}</version>";

			#$this->SendIq($from, 'result', $id, "jabber:iq:version", $payload);
		}

		$this->AddToLog("EVENT: jabber:iq:version (type $type) from $from -- DISABLED");
	}



	// keepalive method, added by Nathan Fritz
	/*
	public function Handler_jabber_iq_time($packet)
	{
		if ($this->keep_alive_id == $this->GetInfoFromIqId($packet))
		{
			$this->returned_keep_alive = true;
			$this->connected = true;
			$this->AddToLog('EVENT: Keep-Alive returned, connection alive.');
		}
	}
	*/
	
	
	// ======================================================================
	// <presence/> handlers
	// ======================================================================



	public function Handler_presence_available($packet)
	{
		$from = $this->GetInfoFromPresenceFrom($packet);

		$show_status = $this->GetInfoFromPresenceStatus($packet) . " / " . $this->GetInfoFromPresenceShow($packet);
		$show_status = ($show_status != " / ") ? " ($addendum)" : '';

		$this->AddToLog("EVENT: Presence (type: available) - $from is available $show_status");
	}



	public function Handler_presence_unavailable($packet)
	{
		$from = $this->GetInfoFromPresenceFrom($packet);

		$show_status = $this->GetInfoFromPresenceStatus($packet) . " / " . $this->GetInfoFromPresenceShow($packet);
		$show_status = ($show_status != " / ") ? " ($addendum)" : '';

		$this->AddToLog("EVENT: Presence (type: unavailable) - $from is unavailable $show_status");
	}



	public function Handler_presence_subscribe($packet)
	{
		$from = $this->GetInfoFromPresenceFrom($packet);
		$this->SubscriptionAcceptRequest($from);
		$this->RosterUpdate();

		$this->log_array[] = "<b>Presence:</b> (type: subscribe) - Subscription request from $from, was added to \$this->subscription_queue, roster updated";
	}



	public function Handler_presence_subscribed($packet)
	{
		$from = $this->GetInfoFromPresenceFrom($packet);
		$this->RosterUpdate();

		$this->AddToLog("EVENT: Presence (type: subscribed) - Subscription allowed by $from, roster updated");
	}



	public function Handler_presence_unsubscribe($packet)
	{
		$from = $this->GetInfoFromPresenceFrom($packet);
		$this->SendPresence("unsubscribed", $from);
		$this->RosterUpdate();

		$this->AddToLog("EVENT: Presence (type: unsubscribe) - Request to unsubscribe from $from, was automatically approved, roster updated");
	}



	public function Handler_presence_unsubscribed($packet)
	{
		$from = $this->GetInfoFromPresenceFrom($packet);
		$this->RosterUpdate();

		$this->AddToLog("EVENT: Presence (type: unsubscribed) - Unsubscribed from $from's presence");
	}



	// Added By Nathan Fritz
	public function Handler_presence_error($packet)
	{
		$from = $this->GetInfoFromPresenceFrom($packet);
		$this->AddToLog("EVENT: Presence (type: error) - Error in $from's presence");
	}
	
	
	
	// ======================================================================
	// Generic handlers
	// ======================================================================



	// Generic handler for unsupported requests
	public function Handler_NOT_IMPLEMENTED($packet)
	{
		$packet_type	= $this->_get_packet_type($packet);
		$from			= call_user_func(array(&$this, "GetInfoFrom" . ucfirst($packet_type) . "From"), $packet);
		$id				= call_user_func(array(&$this, "GetInfoFrom" . ucfirst($packet_type) . "Id"), $packet);

		$this->SendError($from, $id, 501);
		$this->AddToLog("EVENT: Unrecognized <$packet_type/> from $from");
	}



	// ======================================================================
	// Third party code
	// m@d pr0ps to the coders ;)
	// ======================================================================



	// xmlize()
	// (c) Hans Anderson / http://www.hansanderson.com/php/xml/

	public function xmlize($data, $WHITE=1, $encoding='UTF-8') {

		$data = trim($data);
		$vals = $index = $array = array();
		$parser = xml_parser_create($encoding);
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, $WHITE);
		xml_parse_into_struct($parser, $data, $vals, $index);
		xml_parser_free($parser);

		$i = 0;

		$tagname = $vals[$i]['tag'];
		if ( isset ($vals[$i]['attributes'] ) )
		{
			$array[$tagname]['@'] = $vals[$i]['attributes'];
		} else {
			$array[$tagname]['@'] = array();
		}

		$array[$tagname]["#"] = $this->_xml_depth($vals, $i);


		return $array;
	}



	// _xml_depth()
	// (c) Hans Anderson / http://www.hansanderson.com/php/xml/

	public function _xml_depth($vals, &$i) {
		$children = array();

		if ( isset($vals[$i]['value']) )
		{
			array_push($children, $vals[$i]['value']);
		}

		while (++$i < count($vals)) {

			switch ($vals[$i]['type']) {

				case 'open':

					if ( isset ( $vals[$i]['tag'] ) )
					{
						$tagname = $vals[$i]['tag'];
					} else {
						$tagname = '';
					}

					if ( isset ( $children[$tagname] ) )
					{
						$size = sizeof($children[$tagname]);
					} else {
						$size = 0;
					}

					if ( isset ( $vals[$i]['attributes'] ) ) {
						$children[$tagname][$size]['@'] = $vals[$i]["attributes"];

					}

					$children[$tagname][$size]['#'] = $this->_xml_depth($vals, $i);

					break;


				case 'cdata':
					array_push($children, $vals[$i]['value']);
					break;

				case 'complete':
					$tagname = $vals[$i]['tag'];

					if( isset ($children[$tagname]) )
					{
						$size = sizeof($children[$tagname]);
					} else {
						$size = 0;
					}

					if( isset ( $vals[$i]['value'] ) )
					{
						$children[$tagname][$size]["#"] = $vals[$i]['value'];
					} else {
						$children[$tagname][$size]["#"] = array();
					}

					if ( isset ($vals[$i]['attributes']) ) {
						$children[$tagname][$size]['@']
						= $vals[$i]['attributes'];
					}

					break;

				case 'close':
					return $children;
					break;
			}

		}

		return $children;


	}



	// TraverseXMLize()
	// (c) acebone@f2s.com, a HUGE help!

	public function TraverseXMLize($array, $arrName = "array", $level = 0) {

		if ($level == 0)
		{
			echo "<pre>";
		}

		foreach($array as $key=>$val)
		{
			if ( is_array($val) )
			{
				$this->TraverseXMLize($val, $arrName . "[" . $key . "]", $level + 1);
			} else {
				$GLOBALS['traverse_array'][] = '$' . $arrName . '[' . $key . '] = "' . $val . "\"\n";
			}
		}

		if ($level == 0)
		{
			echo "</pre>";
		}

		return 1;

	}
}



class MakeXML extends Jabber
{
	public $nodes;


	public function MakeXML()
	{
		$nodes = array();
	}



	public function AddPacketDetails($string, $value = null)
	{
		if (preg_match("/\(([0-9]*)\)$/i", $string))
		{
			$string .= "/[\"#\"]";
		}

		$temp = @explode("/", $string);

		for ($a = 0; $a < count($temp); $a++)
		{
			$temp[$a] = preg_replace("/^[@]{1}([a-z0-9_]*)$/i", "[\"@\"][\"\\1\"]", $temp[$a]);
			$temp[$a] = preg_replace("/^([a-z0-9_]*)\(([0-9]*)\)$/i", "[\"\\1\"][\\2]", $temp[$a]);
			$temp[$a] = preg_replace("/^([a-z0-9_]*)$/i", "[\"\\1\"]", $temp[$a]);
		}

		$node = implode("", $temp);

		// Yeahyeahyeah, I know it's ugly... get over it. ;)
		echo "\$this->nodes$node = \"" . htmlspecialchars($value) . "\";<br/>";
		eval("\$this->nodes$node = \"" . htmlspecialchars($value) . "\";");
	}



	public function BuildPacket($array = null)
	{

		if (!$array)
		{
			$array = $this->nodes;
		}

		if (is_array($array))
		{
			array_multisort($array, SORT_ASC, SORT_STRING);

			foreach ($array as $key => $value)
			{
				if (is_array($value) && $key == "@")
				{
					foreach ($value as $subkey => $subvalue)
					{
						$subvalue = htmlspecialchars($subvalue);
						$text .= " $subkey='$subvalue'";
					}

					$text .= ">\n";

				}
				elseif ($key == "#")
				{
					$text .= htmlspecialchars($value);
				}
				elseif (is_array($value))
				{
					for ($a = 0; $a < count($value); $a++)
					{
						$text .= "<$key";

						if (!$this->_preg_grep_keys("/^@/", $value[$a]))
						{
							$text .= ">";
						}

						$text .= $this->BuildPacket($value[$a]);

						$text .= "</$key>\n";
					}
				}
				else
				{
					$value = htmlspecialchars($value);
					$text .= "<$key>$value</$key>\n";
				}
			}

			return $text;
		}
	}



	public function _preg_grep_keys($pattern, $array)
	{
		while (list($key, $val) = each($array))
		{
			if (preg_match($pattern, $key))
			{
				$newarray[$key] = $val;
			}
		}
		return (is_array($newarray)) ? $newarray : false;
	}
}



class CJP_StandardConnector
{
	public $active_socket;

	public function OpenSocket($server, $port)
	{
		if (function_exists("dns_get_record"))
		{
			$record = dns_get_record("_xmpp-client._tcp.$server", DNS_SRV);
			if (!empty($record))
			{
				$server = $record[0]["target"];
				$port = $record[0]["port"];
			}
		}

		if ($this->active_socket = fsockopen($server, $port))
		{
			socket_set_blocking($this->active_socket, 0);
			socket_set_timeout($this->active_socket, 31536000);

			return true;
		}
		else
		{
			return false;
		}
	}



	public function CloseSocket()
	{
		return fclose($this->active_socket);
	}



	public function WriteToSocket($data)
	{
		if (false)
		{
			echo '>>> <pre>' . htmlspecialchars($data) . '</pre>';
		}
		return fwrite($this->active_socket, $data);
	}



	public function ReadFromSocket($chunksize)
	{
		set_magic_quotes_runtime(0);
		$buffer = fread($this->active_socket, $chunksize);
		set_magic_quotes_runtime(get_magic_quotes_gpc());

		return $buffer;
	}
}



/* EOF */
