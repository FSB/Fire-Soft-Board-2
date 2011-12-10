<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// On affiche le module si il a ete active dans l'administration
if (Fsb::$mods->is_active('notepad'))
{
    /**
     * On affiche le module ?
     * 
     * @var bool
     */
    $show_this_module = true;
}

/**
 * Module d'utilisateur permettant de rediger des notes
 */
class Page_user_notepad extends Fsb_model
{  
    /**
     * Mode de la page
     *
     * @var string
     */
    public $mode;
    
    /**
     * Page courante
     *
     * @var int
     */
	public $page;
    
	/**
	 * Identifiant de la note
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Constructeur
     *  */    
    public function __construct()
    {
        $this->mode = Http::request('mode');
        $this->page = intval(Http::request('page'));
        $this->id = intval(Http::request('id'));
        
		if ($this->page <= 0)
		{
			$this->page = 1;
		}
        
		$call = new Call($this);
		$call->post(array(
			'submit' =>	':submit_note',
		));

		$call->functions(array(
			'mode' => array(
                'add' =>        'add_edit_note',
				'edit' =>		'add_edit_note',
				'delete' =>		'delete_note',
				'default' =>	'show_notes',
			),
		));       
    }
      
    /**
     * Liste les notes du membre
     */
    public function show_notes()
    {      
        // Quota
		$sql = 'SELECT COUNT(note_id) AS quota
				FROM ' . SQL_PREFIX . 'users_notes
				WHERE u_id = ' . Fsb::$session->id();
		$quota = intval(Fsb::$db->get($sql, 'quota'));
        
        // Pagination
        $total_page = ceil($quota / Fsb::$cfg->get('notes_per_page'));
		$pagination = Html::pagination($this->page, $total_page, ROOT . 'index.' . PHPEXT . '?p=profile&module=notepad');
		if ($total_page > 1)
		{
			Fsb::$tpl->set_switch('pagination');
		}

        // Parse de variables de template
        Fsb::$tpl->set_file('user/user_notepad.html');
        Fsb::$tpl->set_switch('note_list');
		Fsb::$tpl->set_vars(array(
            'FORUM_NOTES_QUOTA' => Fsb::$session->auth() > MODOSUP ? Fsb::$session->lang('unlimited') : Fsb::$cfg->get('notepad_quota'),
			'USER_NOTES_QUOTA' => $quota,
			'PAGINATION' => $pagination,
        ));
               
        // Liste des notes
        $sql = 'SELECT note_id, note_title, note_time, note_text
				FROM ' . SQL_PREFIX . 'users_notes
				WHERE u_id = ' . Fsb::$session->id() . '
				ORDER BY note_time DESC
                LIMIT ' . ($this->page - 1) * Fsb::$cfg->get('notes_per_page') . ', ' . Fsb::$cfg->get('notes_per_page');
        $result = Fsb::$db->query($sql);
		while ($row = Fsb::$db->row($result))
        {
			Fsb::$tpl->set_blocks('note', array(
				'TITLE' =>		$row['note_title'],
				'TIME' =>		Fsb::$session->print_date($row['note_time']),
                'TEXT' => 		$row['note_text'],

				'U_EDIT' =>		sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=notepad&amp;mode=edit&amp;id=' . $row['note_id']),
				'U_DELETE' =>	sid(ROOT . 'index.' . PHPEXT . '?p=profile&amp;module=notepad&amp;mode=delete&amp;id=' . $row['note_id']),
			));       
        }        
    }
    
    /**
     * Ajout / Edition d'une note
     */
    public function add_edit_note()
    {       
        if ($this->mode == 'edit')
        {
			$sql = 'SELECT *
					FROM ' . SQL_PREFIX . 'users_notes
					WHERE note_id = ' . $this->id;
			$data = Fsb::$db->request($sql);
        }
        else
        {
            $data = array(
                'note_title' => '',
                'note_text' =>  '',
                'note_time' =>  '',                
            );
        }
        
        // Parse de variables de template
        Fsb::$tpl->set_file('user/user_notepad.html');
        Fsb::$tpl->set_switch('note_add');
        Fsb::$tpl->set_vars(array(
            'L_ADD_EDIT' => Fsb::$session->lang('user_notepad_' . $this->mode),
        ));
    }
    
    /**
     * Suppression d'une note
     */
    public function delete_note()
    {
		// On verifie si la note appartient bien a l'utilisateur
		$sql = 'SELECT u_id
				FROM ' . SQL_PREFIX . 'users_notes
				WHERE note_id = ' . $this->id . '
					AND u_id = ' . Fsb::$session->id();
		if (!Fsb::$db->get($sql, 'u_id'))
		{
			Display::message('not_allowed');
		}
        
		// Boite de confirmation
		if (check_confirm())
		{
			$sql = 'DELETE FROM ' . SQL_PREFIX . 'users_notes
					WHERE note_id = ' . $this->id;
			Fsb::$db->query($sql);

			Display::message('user_notepad_well_delete', ROOT . 'index.' . PHPEXT . '?p=profile&module=notepad', 'forum_profil');
		}
		else if (Http::request('confirm_no', 'post'))
		{
			Http::redirect(ROOT . 'index.' . PHPEXT . '?p=profile&module=notepad');
		}
		else
		{
			Display::confirmation(Fsb::$session->lang('user_notepad_confirm_delete'), ROOT . 'index.' . PHPEXT . '?p=profile&module=notepad', array('mode' => 'delete', 'id' => $this->id));
		}        
    }
    
    /**
     * Envoi du formulaire d'ajout / edition d'une note
     */
    public function submit_note()
    {
        
    }
}

/* EOF */