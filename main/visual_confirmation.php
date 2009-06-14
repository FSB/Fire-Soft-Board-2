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
 * Genere une image aleatoire pour le code de confirmation visuel.
 * Ce code est ensuite sauve dans la session du membre et verifie lors du traitement du formulaire d'inscription.
 */

define('PHPEXT', substr(strrchr(__FILE__,'.'), 1));
define('ROOT', '../');
define('FORUM', true);
include(ROOT . 'main/start.' . PHPEXT);

Fsb::$session->start('', false);

// Page accessible uniquement aux invites
if (Fsb::$session->is_logged())
{
	exit;
}

// Nouvelle image captcha
$captcha = Captcha::factory();

// On recupere le mode de l'image, suivant ce mode on regenere ou non l'image (sachant que chaque regeneration d'image ajoute +1
// dans le nombre de tentative)
$mode = Http::request('mode');
if ($mode == null)
{
	$mode = 'generate';
}

// Si on est en creation de messages
if ($mode == 'post_captcha' || $mode == 'contact_captcha')
{
	$captcha->create_str();
}
else
{
	// On genere une liste de caractere si on est en mode de generation
	if ($mode == 'generate' || $mode == 'refresh')
	{
		$captcha->create_str();
	}
	// Sinon si on garde la meme image ...
	else
	{
		if (Fsb::$session->data['s_visual_code'])
		{
			$captcha->set_str(Fsb::$session->data['s_visual_code']);
		}
		else
		{
			$captcha->create_str();
		}
	}
}

// On affiche l'image
$captcha->output();

// On met a jour la session du membre si l'image est regeneree
if ($mode == 'generate')
{
	Fsb::$db->update('sessions', array(
		's_visual_code' =>	$captcha->store_str,
		's_visual_try' =>	intval(Fsb::$session->data['s_visual_try']) + 1,
	), 'WHERE s_sid = \'' . Fsb::$db->escape(Fsb::$session->sid) . '\'');
}
else if ($mode == 'post_captcha' || $mode == 'contact_captcha' || $mode == 'refresh')
{
	Fsb::$db->update('sessions', array(
		's_visual_code' =>	$captcha->store_str,
	), 'WHERE s_sid = \'' . Fsb::$db->escape(Fsb::$session->sid) . '\'');
}

exit;

/* EOF */