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
 * Gestion des sondages sur le forum
 */
class Poll extends Fsb_model
{
	/**
	 * Affiche le formulaire de creation de sondages
	 *
	 * @param string $map_name Nom de la MAP
	 * @param string $poll_name Question du sondage
	 * @param array $poll_values Reponses du sondage
	 * @param int $poll_max_vote Nombre de votes possibles
	 */
	public static function display_form($map_name, $poll_name = '', $poll_values = array(), $poll_max_vote = 1)
	{
		if ($poll_data = Map::load_poll($map_name))
		{
			if (!$poll_name)
			{
				$poll_name = $poll_data['poll_name'];
			}

			if (!$poll_values)
			{
				$poll_values = $poll_data['poll_values'];
			}

			if (!$poll_max_vote)
			{
				$poll_max_vote = $poll_data['poll_max_vote'];
			}
		}

		Fsb::$tpl->set_vars(array(
			'POLL_NAME' =>			htmlspecialchars($poll_name),
			'POLL_VALUES' =>		(!is_array($poll_values)) ? htmlspecialchars($poll_values) : htmlspecialchars(implode("\n", $poll_values)),
			'POLL_MAX_VOTE' =>		intval($poll_max_vote),
		));
	}

	/**
	 * Affiche le sondage
	 *
	 * @param int $t_id ID du sujet du sondage
	 * @param array $data Informations additionelles pour eviter une requete trop lourde
	 */
	public static function show($t_id, $data = array())
	{
		Fsb::$tpl->set_switch('poll');

		$sql_fields = $sql_join = '';
		if (!$data || !isset($data['t_status']) || !isset($data['f_status']))
		{
			$sql_fields = ', t.t_status, f.f_status';
			$sql_join = 'LEFT JOIN ' . SQL_PREFIX . 'topics t ON t.t_id = p.t_id LEFT JOIN ' . SQL_PREFIX . 'forums f ON f.f_id = t.f_id';
		}

		// Donnees du sondage, liste des options
		$sql = 'SELECT p.poll_name, p.poll_total_vote, p.poll_max_vote, po.poll_opt_id, po.poll_opt_name, po.poll_opt_total, pr.poll_result_u_id' . $sql_fields . '
				FROM ' . SQL_PREFIX . 'poll p
				LEFT JOIN ' . SQL_PREFIX . 'poll_options po
					ON p.t_id = po.t_id
				LEFT JOIN ' . SQL_PREFIX . 'poll_result pr
					ON p.t_id = pr.t_id
						AND pr.poll_result_u_id = ' . Fsb::$session->id() . '
				' . $sql_join . '
				WHERE p.t_id = ' . $t_id;
		$result = Fsb::$db->query($sql);
		if ($row = Fsb::$db->row($result))
		{
			Fsb::$tpl->set_vars(array(
				'POLL_NAME' =>			htmlspecialchars($row['poll_name']),
				'POLL_TOTAL_VOTE' =>	$row['poll_total_vote'],
				'POLL_MAX_VOTE' =>		$row['poll_max_vote'],
				'L_TOPIC_MAX_POLL' =>	sprintf(Fsb::$session->lang('topic_max_poll'), $row['poll_max_vote']),

				'U_POLL_ACTION' =>		sid(ROOT . 'index.' . PHPEXT . '?p=topic&amp;t_id=' . $t_id),
			));

			if ($sql_fields)
			{
				$data['t_status'] = $row['t_status'];
				$data['f_status'] = $row['f_status'];
			}

			// Peut voter ?
			if (!$row['poll_result_u_id'] && Fsb::$session->is_logged() && $data['t_status'] == UNLOCK && $data['f_status'] == UNLOCK)
			{
				Fsb::$tpl->set_switch('can_use_poll');
			}

			// Liste des options
			do
			{
				Fsb::$tpl->set_blocks('poll_option', array(
					'ID' =>			$row['poll_opt_id'],
					'NAME' =>		htmlspecialchars($row['poll_opt_name']),
					'TOTAL' =>		$row['poll_opt_total'],
					'INPUT' =>		($row['poll_max_vote'] > 1) ? 'checkbox' : 'radio',
					'WIDTH' =>		($row['poll_total_vote']) ? floor(($row['poll_opt_total'] / $row['poll_total_vote']) * 200) : 0,
					'CALCUL' =>		($row['poll_total_vote']) ? substr(($row['poll_opt_total'] / $row['poll_total_vote']) * 100, 0, 4) : 0,
				));
			}
			while ($row = Fsb::$db->row($result));
		}
		Fsb::$db->free($result);
	}

	/**
	 * Soumission d'un vote a un sondage
	 *
	 * @param int $t_id ID du sujet
	 */
	public static function submit_vote($t_id)
	{
		if (!Fsb::$session->is_logged())
		{
			Display::message('not_allowed');
		}

		// Donnees du sondage
		$sql = 'SELECT p.poll_max_vote, pr.poll_result_u_id
				FROM ' . SQL_PREFIX . 'poll p
				LEFT JOIN ' . SQL_PREFIX . 'poll_result pr
					ON p.t_id = pr.t_id
						AND pr.poll_result_u_id = ' . Fsb::$session->id() . '
						AND pr.poll_result_u_id <> ' . VISITOR_ID . '
				WHERE p.t_id = ' . $t_id;
		$row = Fsb::$db->request($sql);

		// On verifie tout de meme si le membre n'a pas deja vote et que le sondage existe
		if (!$row)
		{
			Display::message('topic_poll_not_exists');
		}
		else if ($row['poll_result_u_id'])
		{
			Display::message('topic_has_submit_poll');
		}

		// On recupere les votes
		$poll_result = Http::request('poll_result', 'post');
		if (!is_array($poll_result))
		{
			$poll_result = array();
		}
		$poll_result = array_map('intval', $poll_result);

		// On verifie qu'il y ai au moins un vote :p
		if (!$poll_result)
		{
			Display::message('topic_poll_need_vote');
		}

		// On verifie qu'il n'y ait pas trop de votes
		if (count($poll_result) > $row['poll_max_vote'])
		{
			Display::message(sprintf(Fsb::$session->lang('topic_poll_too_much_vote'), $row['poll_max_vote']));
		}

		// On met a jour le nombre de vote par option et on signale que le membre a vote
		Fsb::$db->insert('poll_result', array(
			'poll_result_u_id' =>	Fsb::$session->id(),
			't_id' =>				$t_id,
		));

		Fsb::$db->update('poll', array(
			'poll_total_vote' =>	array('(poll_total_vote + ' . count($poll_result) . ')', 'is_field' => true),
		), 'WHERE t_id = ' . $t_id);

		foreach ($poll_result AS $value)
		{
			Fsb::$db->update('poll_options', array(
				'poll_opt_total' =>	array('(poll_opt_total + 1)', 'is_field' => true),
			), 'WHERE poll_opt_id = ' . intval($value) . ' AND t_id = ' . $t_id);
		}

		Http::redirect(ROOT . 'index.' . PHPEXT . '?p=topic&t_id=' . $t_id);
	}

	/**
	 * Ajoute un sondage au sujet
	 *
	 * @param string $poll_name Titre du sondage
	 * @param array $poll_values Options du sondage
	 * @param int $topic_id ID du sujet
	 * @param int $max_vote Nombre de votes par personne pour ce sondage (multi choix)
	 */
	public static function send($poll_name, $poll_values, $topic_id, $max_vote)
	{
		// Creation du sondage
		Fsb::$db->insert('poll', array(
			't_id' =>				$topic_id,
			'poll_name' =>			$poll_name,
			'poll_max_vote' =>		$max_vote,
		));

		// Ajout des differentes options
		foreach ($poll_values AS $value)
		{
			Fsb::$db->insert('poll_options', array(
				't_id' =>			$topic_id,
				'poll_opt_name' =>	$value,
			), 'INSERT', true);
		}
		Fsb::$db->query_multi_insert();
	}

	/**
	 * Edition du sondage (suppression des anciennes options, ajout des nouvelles)
	 *
	 * @param int $t_id ID du sujet
	 * @param string $poll_name Titre du sondage
	 * @param array $poll_values Options du sondage
	 * @param int $max_vote Nombre de votes par personne pour ce sondage (multi choix)
	 */
	public static function edit($t_id, $poll_name, $poll_values, $max_vote)
	{
		// Mise a jour du sondage
		Fsb::$db->update('poll', array(
			'poll_name' =>			$poll_name,
			'poll_max_vote' =>		$max_vote,
		), "WHERE t_id = $t_id");

		// Suppression des options
		$sql = 'DELETE FROM ' . SQL_PREFIX . "poll_options
					WHERE t_id = $t_id";
		$result = Fsb::$db->query($sql);

		foreach ($poll_values AS $value)
		{
			Fsb::$db->insert('poll_options', array(
				't_id' =>				$t_id,
				'poll_opt_name' =>	$value,
			), 'INSERT', true);
		}
		Fsb::$db->query_multi_insert();
	}
}

/* EOF */