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
 * Gestion des tags des sujets sur le forum
 */
class Fsb_frame_child extends Fsb_admin_frame
{
    /**
     * Mode de la frame
     *
     * @var string
     */
    public $mode;
	
    /**
     * Identifiant du tag
     *
     * @var int
     */
    public $id;

    /**
     * Constructeur
     */
    public function main()
    {
        $this->mode = Http::request('mode');
        $this->id = intval(Http::request('id'));

        $call = new Call($this);
        $call->post(array(
            'submit' => ':query_add_edit_tag',
        ));

        $call->functions(array(
            'mode' => array(
                'add' => 'page_add_edit_tag',
                'edit' => 'page_add_edit_tag',
                'delete' => 'page_delete_tag',
                'default' => 'page_default_tag',
            ),
        ));
    }

    /**
     * Affiche la page de gestion des tags
     */
    public function page_default_tag()
    {
        Fsb::$tpl->set_switch('tag_list');
        Fsb::$tpl->set_vars(array(
            'U_ADD' => sid('index.' . PHPEXT . '?p=posts_tag&amp;mode=add')
        ));

        // Liste des permissions
        $auth_list = array(
            VISITOR => Fsb::$session->lang('visitor'),
            USER =>	Fsb::$session->lang('user'),
            MODO => Fsb::$session->lang('modo'),
            MODOSUP => Fsb::$session->lang('modosup'),
            ADMIN => Fsb::$session->lang('admin'),            
        );
        
        // On recupere les tags
        $sql = 'SELECT *
                FROM ' . SQL_PREFIX . 'topics_tags
                ORDER BY tag_name';
        $result = Fsb::$db->query($sql, 'tags_');
        while ($row = Fsb::$db->row($result))
        {
            Fsb::$tpl->set_blocks('tag', array(
                'NAME' => $row['tag_name'],
                'STYLE' => $row['tag_style'],
                'PREVIEW' => '<span ' . $row['tag_style'] . '>[' . $row['tag_name'] . ']</span>',
                'AUTH' => $auth_list[$row['tag_auth']],
                'U_EDIT' => sid('index.' . PHPEXT . '?p=posts_tag&amp;mode=edit&amp;id=' . $row['tag_id']),
                'U_DELETE' => sid('index.' . PHPEXT . '?p=posts_tag&amp;mode=delete&amp;id=' . $row['tag_id']),
            ));
        }
        Fsb::$db->free($result);
    }
    
	/**
	 * Affiche la page permettant d'ajouter / editer des tags
	 */
	public function page_add_edit_tag()
	{
        $lg_add_edit = ($this->mode == 'edit') ? Fsb::$session->lang('adm_tag_edit') : Fsb::$session->lang('adm_tag_add');
        $data = array(
            'tag_name' => '',
            'tag_style' => '',
            'tag_auth' => '',
        );

        if ($this->mode == 'edit')
        {
            $sql = 'SELECT tag_name, tag_style, tag_auth
                    FROM ' . SQL_PREFIX . 'topics_tags
                    WHERE tag_id = ' . $this->id;
            $data = Fsb::$db->request($sql);
        }        
        
        // Style
        $style_type = $style_content = '';
        if ($getstyle = Html::get_style($data['tag_style']))
        {
            list($style_type, $style_content) = $getstyle;
        }   
        
        // Liste des permissions
        $auth_list = Html::make_list('tag_auth', $data['tag_auth'], array(
            VISITOR => Fsb::$session->lang('visitor'),
            USER =>	Fsb::$session->lang('user'),
            MODO => Fsb::$session->lang('modo'),
            MODOSUP => Fsb::$session->lang('modosup'),
            ADMIN => Fsb::$session->lang('admin'),     
        ));

        Fsb::$tpl->set_switch('tag_add');
        Fsb::$tpl->set_vars(array(
            'L_ADD_EDIT' =>	$lg_add_edit,
            'TAG_NAME' => $data['tag_name'],
            'TAG_STYLE' => htmlspecialchars($style_content),
            'STYLE_TYPE_NONE' => (!$getstyle) ? 'checked="checked"' : '',
            'STYLE_TYPE_COLOR' => ($style_type == 'style') ? 'checked="checked"' : '',
            'STYLE_TYPE_CLASS' => ($style_type == 'class') ? 'checked="checked"' : '',
            'TAG_AUTH' => $auth_list,
            'U_ACTION' => sid('index.' . PHPEXT . '?p=posts_tag&amp;mode=' . $this->mode . '&amp;id=' . $this->id)
        )); 
    }    

    /**
     * Valide le formulaire d'ajout / edition des tags
     */    
    public function query_add_edit_tag()
    {
        $data = array(
            'tag_name' => Http::request('tag_name', 'post'),
            'tag_style' => Html::set_style(Http::request('tag_style_type', 'post'), trim(Http::request('tag_style', 'post'))),
            'tag_auth' => Http::request('tag_auth', 'post'),
        );
        
        $errstr = array();
       
        if (!$data['tag_name'])
        {
            $errstr[] = Fsb::$session->lang('fields_empty');
        }

        if ($errstr)
        {
            Display::message(Html::make_errstr($errstr));
        }      
        
        if ($this->mode == 'add')
        {
            Fsb::$db->insert('topics_tags', $data);
        }
        else
        {
            Fsb::$db->update('topics_tags', $data, 'WHERE tag_id = ' . $this->id);
        }

        Fsb::$db->destroy_cache('tags_');
        Fsb::$db->destroy_cache('topics_tags_');
        Log::add(Log::ADMIN, 'tag_log_' . $this->mode, $data['tag_name']);
        Display::message('adm_tag_well_' . $this->mode, 'index.' . PHPEXT . '?p=posts_tag', 'posts_tag');
    }
    
    /**
     * Suppression d'un tag
     */
    public function page_delete_tag()
    {
        if (check_confirm())
        {
            $sql = 'SELECT tag_name
                    FROM ' . SQL_PREFIX . 'topics_tags
                    WHERE tag_id = ' . $this->id;
            if ($data = Fsb::$db->request($sql))
            {
                $sql = 'DELETE FROM ' . SQL_PREFIX . 'topics_tags
                        WHERE tag_id = ' . $this->id;
                Fsb::$db->query($sql);
                Fsb::$db->destroy_cache('tags_');
                Fsb::$db->destroy_cache('topics_tags_');
                
                Fsb::$db->update('topics', array(
                    't_tag' =>	0,
                ), 'WHERE t_tag = ' . $this->id);
                                
                Log::add(Log::ADMIN, 'tag_log_delete', $data['tag_name']);
                Display::message('adm_tag_well_delete', 'index.' . PHPEXT . '?p=posts_tag', 'posts_tag');
            }
            else
            {
                Http::redirect('index.' . PHPEXT . '?p=posts_tag');
            }
        }
        else if (Http::request('confirm_no', 'post'))
        {
            Http::redirect('index.' . PHPEXT . '?p=posts_tag');
        }
        else
        {
            Display::confirmation(Fsb::$session->lang('adm_tag_delete_confirm'), 'index.' . PHPEXT . '?p=posts_tag', array('mode' => $this->mode, 'id' => $this->id));
        }
	}
}

/* EOF */