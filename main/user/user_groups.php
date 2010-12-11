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
 * On affiche le module
 * 
 * @var bool
 */
$show_this_module = true;

/**
 * Module d'utilisateur affichant les differents groupes
 */
class Page_user_groups extends Fsb_model
{
	/**
	 * Constructeur
	 */
	public function __construct()
	{
		if (Http::request('submit', 'post'))
		{
			$this->submit_default_group();
		}
		$this->show_form();
	}

	/**
	 * Formulaire de la liste des groupes
	 */
	public function show_form()
	{
		Fsb::$tpl->set_file('user/user_groups.html');

		// On recupere les groupes
		$sql = 'SELECT g.g_id, g.g_name, g.g_desc, g.g_type, g.g_hidden, g.g_color, gu.u_id
				FROM ' . SQL_PREFIX . 'groups g
				LEFT JOIN ' . SQL_PREFIX . 'groups_users gu
					ON g.g_id = gu.g_id
						AND gu.u_id = ' . Fsb::$session->id() . '
				WHERE g.g_type <> ' . GROUP_SINGLE . '
				ORDER BY gu.gu_status, g.g_name';
		$result = Fsb::$db->query($sql);

		$list_group = array('special' => array(), 'member' => array(), 'none' => array());
		while ($row = Fsb::$db->row($result))
		{
			if ($row['g_type'] == GROUP_SPECIAL)
			{
				if (!$row['u_id'])
				{
					continue;
				}
				$list_type = 'special';
			}
			else
			{
				$list_type = ($row['u_id']) ? 'member' : 'none';
			}

			if (!$row['g_hidden'] || Fsb::$session->auth() >= MODOSUP || $list_type != 'none')
			{
				$list_group[$list_type][] = $row;
			}
		}
		Fsb::$db->free($result);

		// On affiche les groupes
		foreach ($list_group AS $group_type => $array_group)
		{
			Fsb::$tpl->set_blocks('cat', array(
				'LANG' =>	Fsb::$session->lang('user_groups_' . $group_type),
			));

			foreach ($array_group AS $group)
			{
				Fsb::$tpl->set_blocks('cat.group', array(
					'ID' =>			$group['g_id'],
					'NAME' =>		($group['g_type'] == GROUP_SPECIAL && Fsb::$session->lang($group['g_name'])) ? Fsb::$session->lang($group['g_name']) : $group['g_name'],
					'DESC' =>		$group['g_desc'],
					'STYLE' =>		$group['g_color'],
					'SHOW_RADIO' =>	($group_type != 'none') ? true : false,

					'U_GROUP' =>	sid(ROOT . 'index.' . PHPEXT . '?p=userlist&amp;g_id=' . $group['g_id']),
				));
			}
		}

		Fsb::$tpl->set_vars(array(
			'DEFAULT_GROUP_ID' =>	Fsb::$session->data['u_default_group_id'],
		));
	}

	/**
	 * Soumet le groupe par defaut
	 */
	public function submit_default_group()
	{
		$default_group = intval(Http::request('default_group', 'post'));
		// On verifie que le groupe par defaut existe pour le membre
		$sql = 'SELECT g.g_color, g.g_hidden
				FROM ' . SQL_PREFIX . 'groups g
				INNER JOIN ' . SQL_PREFIX . 'groups_users gu
					ON g.g_id = gu.g_id
				WHERE g.g_id = ' . $default_group . '
					AND gu.u_id = ' . Fsb::$session->id();
		if ($row = Fsb::$db->request($sql))
		{
                        if ($row["g_hidden"] == GROUP_HIDDEN)
                                Display::message('user_groups_no_hidden_group_as_default', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=groups', 'forum_profil');
                        else
                        {
                                Fsb::$db->update('users', array(
                                                         'u_default_group_id' =>		$default_group,
                                ), 'WHERE u_id = ' . Fsb::$session->id());

                                Fsb::$db->update('users', array(
                                                         'u_color' =>	$row['g_color'],
                                ), 'WHERE u_id = ' . Fsb::$session->id());

                                if (Fsb::$session->id() == Fsb::$cfg->get('last_user_id'))
                                {
                                        Fsb::$cfg->update('last_user_color', $row['g_color']);
                                }

                                Display::message('user_profil_submit', ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=groups', 'forum_profil');
                        }
		}
	}
}

/* EOF */