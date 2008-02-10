<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/visual_confirmation.php
** | Begin :	20/09/2005
** | Last :		13/07/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Génère une image aléatoire pour le code de confirmation visuel.
** Ce code est ensuite sauvé dans la session du membre et vérifié lors du traitement du formulaire d'inscription.
*/

define('PHPEXT', substr(strrchr(__FILE__,'.'), 1));
define('ROOT', '../');
define('FORUM', TRUE);
include(ROOT . 'main/start.' . PHPEXT);

Fsb::$session->start('', FALSE);

// Page accessible uniquement aux invités
if (Fsb::$session->is_logged())
{
	exit;
}

// Nouvelle image captcha
$captcha = Captcha::factory();

// On récupère le mode de l'image, suivant ce mode on régénère ou non l'image (sachant que chaque régénération d'image ajoute +1
// dans le nombre de tentative)
$mode = Http::request('mode');
if ($mode == NULL)
{
	$mode = 'generate';
}

// Si on est en création de messages
if ($mode == 'post_captcha' || $mode == 'contact_captcha')
{
	$captcha->create_str();
}
else
{
	// On génère une liste de caractère si on est en mode de génération
	if ($mode == 'generate' || $mode == 'refresh')
	{
		$captcha->create_str();
	}
	// Sinon si on garde la même image ...
	else
	{
		$captcha->set_str(Fsb::$session->data['s_visual_code']);
	}
}

// On affiche l'image
$captcha->output();

// On met à jour la session du membre si l'image est régénérée
if ($mode == 'generate')
{
	Fsb::$db->update('sessions', array(
		's_visual_code' =>	$captcha->store_str,
		's_visual_try' =>	intval(Fsb::$session->data['s_visual_try'] + 1),
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