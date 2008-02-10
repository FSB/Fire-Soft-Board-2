<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/modo/modo_procedure_exec.php
** | Begin :	11/11/2006
** | Last :		14/08/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

// On cache ce module
$show_this_module = FALSE;

/*
** Module executant les procédures de modération
*/
class Page_modo_procedure_exec extends Fsb_model
{
	/*
	** Constructeur
	*/
	public function __construct()
	{
		$topic_id =		intval(Http::request('id'));
		$proc_id =		intval(Http::request('procedure', 'post'));

		// Données de la procédure
		$sql = 'SELECT *
				FROM ' . SQL_PREFIX . 'sub_procedure
				WHERE procedure_id = ' . $proc_id;
		$result = Fsb::$db->query($sql);
		$data = Fsb::$db->row($result);
		Fsb::$db->free($result);

		if (!$data)
		{
			Display::message('not_allowed');
		}

		// Données du sujet
		$sql = 'SELECT t.t_title, t.t_first_p_id, t.t_last_p_id, t.f_id, t.u_id AS owner_id, p.u_id AS last_poster
				FROM ' . SQL_PREFIX . 'topics t
				INNER JOIN ' . SQL_PREFIX . 'posts p
					ON p.p_id = t.t_last_p_id
				WHERE t.t_id = ' . $topic_id;
		$result = Fsb::$db->query($sql);
		$t = Fsb::$db->row($result);
		Fsb::$db->free($result);

		if (!$t)
		{
			Display::message('topic_not_exists');
		}

		// Droits ?
		if (!(($data['procedure_auth'] == USER && (Fsb::$session->auth() >= MODOSUP || Fsb::$session->is_authorized($t['f_id'], 'ga_moderator') || $t['owner_id'] == Fsb::$session->id()))
			|| ($data['procedure_auth'] == MODO && (Fsb::$session->auth() >= MODOSUP || Fsb::$session->is_authorized($t['f_id'], 'ga_moderator')))
			|| ($data['procedure_auth'] > MODO && Fsb::$session->auth() >= $data['procedure_auth'])))
		{
			Display::message('not_allowed');
		}

		$procedure = new Procedure();

		// ID du sujet
		$procedure->set_var('this.topic_id', $topic_id);

		// ID du forum
		$procedure->set_var('this.forum_id', $t['f_id']);

		// ID du premier message
		$procedure->set_var('this.first_post_id', $t['t_first_p_id']);

		// ID du posteur du sujet
		$procedure->set_var('this.owner_id', $t['owner_id']);

		// ID du dernier posteur dans le sujet
		$procedure->set_var('this.last_poster_id', $t['last_poster']);

		// ID du dernier message
		$procedure->set_var('this.last_post_id', $t['t_last_p_id']);

		// Titre du sujet
		$procedure->set_var('this.topic_title', $t['t_title']);

		// Utilisateur executant la procédure
		$procedure->set_var('this.user', Fsb::$session->data);

		// Informations sur le membre qui a démarré le sujet, celui qui a terminé le sujet
		$userdata = array('owner' => $t['owner_id'], 'last' => $t['last_poster']);
		foreach ($userdata AS $varname => $id)
		{
			$sql = 'SELECT *
					FROM ' . SQL_PREFIX . 'users
					WHERE u_id = ' . $id;
			$result = Fsb::$db->query($sql);
			$procedure->set_var('this.' . $varname, Fsb::$db->row($result));
			Fsb::$db->free($result);
		}

		// Nom de la procédure
		$procedure->name = $data['procedure_name'];
		$procedure->set_var('this.procedure_name', $data['procedure_name']);

		// Parse et execution du pseudo script
		$procedure->parse($data['procedure_source']);

		Log::add(Log::MODO, 'log_procedure', $procedure->name);

		// Si aucune redirection manuelle, on redirige automatiquement vers le sujet
		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=topic&t_id=' . $topic_id);
	}
}

/* EOF */