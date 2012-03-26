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
            $style = Html::get_style($row['tag_style']);
            Fsb::$tpl->set_blocks('tag', array(
                'NAME' => $row['tag_name'],
                'STYLE' => $style[1],
                'PREVIEW' => '<span ' . $row['tag_style'] . '>[' . $row['tag_name'] . ']</span>',
                'AUTH' => $auth_list[$row['tag_auth']],
                'U_EDIT' => sid('index.' . PHPEXT . '?p=posts_tag&amp;mode=edit&amp;id=' . $row['tag_id']),
                'U_DELETE' => sid('index.' . PHPEXT . '?p=posts_tag&amp;mode=delete&amp;id=' . $row['tag_id']),
            ));
        }
        Fsb::$db->free($result);
    }
}

/* EOF */