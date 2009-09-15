<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

$GLOBALS['use_register_shutdown'] = false;

/**
 * Effectue un delestage sur les forums concernes
 *
 */
function prune_forums()
{
	Forum::auto_prune();
}
/* EOF */