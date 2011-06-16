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
	 */    
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
		// On recupere le quota de notes du membre
		$sql = 'SELECT COUNT(*) AS quota
				FROM ' . SQL_PREFIX . 'users_notes
				WHERE u_id = ' . Fsb::$session->id();
		$data = Fsb::$db->request($sql);
		$quota = $data['quota'];
        
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
        
		// On affiche les notes du membre
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
		Fsb::$db->free($result);        
    }
    
    /**
     * Ajout / Edition d'une note
     */
    public function add_edit_note()
    {
        
    }
    
    /**
     * Suppression d'une note
     */
    public function delete_note()
    {
        
    }
    
    /**
     * Envoi du formulaire d'ajout / edition d'une note
     */
    public function submit_note()
    {
        
    }
}

/* EOF */