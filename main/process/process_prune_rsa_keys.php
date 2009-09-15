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
 * Regenere les clefs RSA
 *
 */
function prune_rsa_keys()
{
	$rsa = new Rsa();
	$rsa->regenerate_keys();
}
/* EOF */