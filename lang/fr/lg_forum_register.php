<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

return (array (
  'forum_rules' => 'Merci de vous enregistrer sur le forum. Avant de continuer vous devez accepter les quelques points énoncés ci-contre:
 <ul><li>Vous êtes seul responsable de ce que vous dites, ainsi si vous tenez des propos pédophiles, pornographiques, racistes, diffamatoires,
 ou contraire aux lois internationales et de votre pays, vous en serez entièrement responsable.</li><li>L\'équipe du forum ne peut en aucun cas être
 tenue responsable de vos messages.</li><li>L\'équipe du forum peut à tout moment prendre des sanctions de modération contre vous si elle estime que
 les règles ne sont pas respectées.</li></ul><br/>La procédure d\'inscription est gratuite et ne requiert aucune information personnelle si ce n\'est
 votre adresse mail pour des raisons de sécurité. Vous pourrez choisir de cacher votre adresse mail par la suite. Il est conseillé d\'accepter les
 cookies pour accéder au forum, en effet si vous ne les acceptez pas, il se peut que vous ne puissiez pas vous connecter.<br /><br />Cordialement, l\'équipe du forum.',
  'register_menu_new' => 'Inscription sur le forum',
  'register_menu_fsbcard' => 'Importer un profil',
  'register_data' => 'Informations pour l\'enregistrement',
  'accept' => 'Accepter',
  'accept_rules_explain' => 'En cochant cette case vous certifiez être en accord avec les règles ci dessus',
  'register_login' => 'Login de connexion',
  'register_login_explain' => 'Cet identifiant servira à vous connecter sur le forum',
  'register_nickname' => 'Pseudonyme sur le forum',
  'register_nickname_explain' => 'Cet identifiant sera votre pseudonyme sur le forum, laissez ce champ vide pour prendre la valeur du login par défaut',
  'password_confirm' => 'Confirmer le mot de passe',
  'password_test' => 'Tester le mot de passe',
  'password_generate' => 'Générer un mot de passe',
  'password_generate_result' => 'Voici le mot de passe généré automatiquement : %s',
  'register_ok_normal' => 'Votre compte a été créé avec succès. Vous pouvez vous connecter avec votre login et votre mot de passe',
  'register_ok_confirm' => 'Votre compte a été créé avec succès. Un email vous a été envoyé, contenant un lien de confirmation. Vous devrez cliquer sur ce lien pour valider votre compte et pouvoir vous connecter',
  'register_ok_both' => 'Votre compte a été créé avec succès. Un email vous a été envoyé, contenant un lien de confirmation. Vous devrez cliquer sur ce lien pour valider votre compte. Une fois votre compte activé, un administrateur validera ce dernier, et vous pourrez vous connecter avec votre login / mot de passe.',
  'register_ok_admin' => 'Votre compte a été créé avec succès. Cependant avant de pouvoir vous connecter vous devrez attendre qu\'un administrateur le valide. Vous serez prévenu par email',
  'register_ko' => 'Votre compte a été créé avec succès. Cependant l\'envoi d\'email a échoué. Si votre compte n\'est pas activé et que vous n\'avez pu recevoir l\'email, veuillez contacter <a href="index.php?p=contact">l\'administrateur du forum</a>.',
  'email_valid' => 'Entrez une adresse email valide',
  'visual_confirmation' => 'Code de confirmation visuelle - Si vous ne pouvez pas voir l\'image veuillez contacter <a href="index.php?p=contact">l\'administrateur du forum</a>.',
  'visual_code' => 'Code',
  'register_personal' => 'Informations supplémentaires',
  'register_password_explain' => 'Il est conseillé d\'utiliser un mot de passe long et complexe (avec des caractères spéciaux), pour votre sécurité',
  'register_email_explain' => 'Veuillez entrer un email valide, car un code de confirmation pourra vous être envoyé par email. Une fois connecté vous pourrez cacher votre adresse email si vous le souhaitez',
  'register_code_explain' => '<b>Les majuscules / minuscules ne sont pas importantes</b>. Si vous êtes visuellement déficient, ou que vous ne parvenez pas à lire le code, veuillez contacter <a href="index.php?p=contact">l\'administrateur du forum</a>.',
  'register_test_low' => 'Votre mot de passe est <b>faiblement robuste</b>, pour le rendre plus efficace:',
  'register_test_middle' => 'Votre mot de passe est <b>moyennement robuste</b>, pour le rendre plus efficace:',
  'register_test_high' => 'Votre mot de passe est <b>robuste</b>',
  'register_test_len' => 'Augmentez la longueur du mot de passe',
  'register_test_type' => 'Utiliser plusieurs types de caractères (lettres minuscules, majuscules, chiffres, caractères spéciaux)',
  'register_test_average' => 'Dispersez bien les lettres de votre mot de passe suivant les différents types',
  'register_need_accept' => 'Vous devez accepter les règles avant de pouvoir continuer',
  'register_need_login' => 'Vous devez entrer un login pour vous enregistrer',
  'register_need_password' => 'Vous devez entrer un mot de passe et le confirmer',
  'register_password_dif' => 'Votre mot de passe est différent de celui de confirmation',
  'register_email_format' => 'Adresse email invalide',
  'register_login_exists' => 'Ce login est déjà pris',
  'register_nickname_exists' => 'Ce pseudonyme est déjà pris',
  'register_email_exists' => 'Cette adresse email existe déjà',
  'register_ban_login' => 'Ce login est banni, vous ne pouvez l\'utiliser',
  'register_ban_mail' => 'Cette adresse email est bannie, vous ne pouvez l\'utiliser',
  'register_bad_visual_code' => 'Le code de confirmation visuelle entré est mauvais, il vous reste %s essais',
  'register_too_much_try' => 'Vous ne pouvez pas vous inscrire car vous avez dépassé le nombre d\'essais autorisé pour l\'inscription (5)',
  'register_ajax_email_invalid' => 'Email invalide',
  'register_ajax_email_used' => 'Email déjà pris',
  'register_ajax_email_valid' => 'Email valide et libre',
  'register_short_nickname' => 'Votre pseudonyme est trop court (3 lettres minimum)',
  'register_long_nickname' => 'Votre pseudonyme est trop long (%d lettres maximum)',
  'register_ajax_nickname_short' => 'Pseudonyme trop court',
  'register_ajax_nickname_used' => 'Pseudonyme déjà pris',
  'register_ajax_nickname_valid' => 'Pseudonyme disponible',
  'register_ajax_login_used' => 'Login déjà pris',
  'register_ajax_login_valid' => 'Login disponible',
  'register_ajax_nickname_long' => 'Pseudonyme trop long (il sera tronqué)',
  'register_ajax_password_weak' => 'Mot de passe faiblement sécurisé',
  'register_ajax_password_normal' => 'Mot de passe moyennement sécurisé',
  'register_ajax_password_strong' => 'Mot passe correctement sécurisé',
  'register_are_disabled' => 'Les inscriptions sont actuellement fermées',
  'register_password_diff_nickname' => 'Vous ne pouvez pas mettre votre pseudonyme comme mot de passe (question de sécurité)',
  'register_refresh' => 'Rafraîchir l\'image',
  'register_fsbcard' => 'Importer une FSBcard',
  'register_fsbcard_explain' => 'Si vous possédez une FSBcard contenant vos informations de connexion, vous pouvez l\'importer via ce formulaire pour vous inscrire automatiquement.',
  'register_need_fsbcard' => 'Vous devez choisir une FSBcard depuis votre PC',
  'register_fsbcard_invalid' => 'Votre FSBcard est invalide ou ne contient pas vos informations de connexion au forum.',
));


/* EOF */