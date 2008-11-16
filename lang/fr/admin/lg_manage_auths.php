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
  'manage_auths_explain_title' => 'Gestion des permissions sur les forums',
  'manage_auths_explain_desc' => 'Chaque forum possède ses propres permissions, vous pouvez facilement décider qui a le droit de faire quoi. Par exemple vous pouvez empêcher les membres de poster des annonces sur tel forum, empêcher les visiteurs non inscrits de visiter tel autre forum, etc ... Consultez la <b><a href="../index.php?p=faq&amp;section=admin&amp;area=auth">FAQ des droits</a></b> pour plus d\'informations.<br />Veuillez noter que le groupe <b>Modérateur</b> comprend tous les membres ayant un droit de modération quelque part sur le forum, ainsi si vous donnez des droits à ce groupe vous donnez par conséquent des droits à chaque modérateur sur ce forum.',
  'adm_auths_module_forums' => 'Forums',
  'adm_auths_module_groups' => 'Groupes',
  'adm_auths_module_users' => 'Membres',
  'adm_auths_module_others' => 'Autres droits',
  'adm_auths_module_check' => 'Vérifier',
  'adm_auths_title' => 'Gestion des droits des forums',
  'adm_auths_type_easy' => 'Interface très simplifiée',
  'adm_auths_type_simple' => 'Interface normale',
  'adm_auths_type_advanced' => 'Interface avancée',
  'adm_auth_checkbox' => 'Tous',
  'adm_auths_no_level' => 'Aucun droit',
  'adm_auth_well_add' => 'Les droits du forum ont bien été mis à jour',
  'adm_auths_model_forum' => 'Dupliquer les droits d\'un forum',
  'adm_auths_model_groups' => 'Dupliquer les droits d\'un groupe',
  'adm_auths_users_nickname' => 'Pseudonyme du membre',
  'adm_auths_users_status' => 'Modifier le statut du membre',
  'adm_auths_submit_others' => 'Les droits ont bien été modifiés',
  'adm_auths_choose_forum' => 'Choisissez un forum',
  'adm_auths_choose_group' => 'Choisissez un groupe',
  'adm_auths_option' => 'Options de la page',
  'adm_auths_mode' => 'Type d\'interface',
  'adm_auths_check' => 'Vérification des droits',
  'adm_auths_check_groups' => 'Vérifier les droits d\'un groupe',
  'adm_auths_check_user' => 'Vérifier les droits d\'un membre',
  'adm_auths_check_groups_title' => 'Vérification des droits du groupe %s',
  'adm_auths_check_user_title' => 'Vérification des droits du membre %s',
  'adm_auths_o_auth_ip' => 'Voir les adresses IP des membres',
  'adm_auths_e_auth_ip' => 'Les personnes ayant ce droit pourront voir les adresses IP des membres dans les messages et accéder à l\'outil permettant de vérifier les adresses IP utilisées par un membre',
  'adm_auths_o_auth_edit_user' => 'Modération simple des membres',
  'adm_auths_e_auth_edit_user' => 'Les personnes ayant ce droit pourront modérer de façon restreinte un membre, en lui désactivant son avatar ou sa signature par exemple',
  'adm_auths_o_auth_extra_edit_user' => 'Modération avancée des membres',
  'adm_auths_e_auth_extra_edit_user' => 'Les personnes ayant ce droit pourront modérer totalement un membre en modifiant son pseudonyme, son login, son mot de passe, sa signature, etc ...',
  'adm_auths_o_upload_file' => 'Uploader un fichier',
  'adm_auths_e_upload_file' => 'Les personnes ayant ce droit pourront uploader des fichiers sur le serveur du forum et accéder à la liste de leurs fichiers',
  'adm_auths_o_download_file' => 'Télécharger un fichier',
  'adm_auths_e_download_file' => 'Lors de l\'upload d\'un fichier, l\'utilisateur peut déterminer quel droit est necessaire pour télécharger le fichier. En réglant cette liste, vous pouvez déterminer le niveau de droit minimum que le membre pourra choisir dans cette liste (par exemple en choisissant "membres", le membre qui voudra uploader un fichier ne pourra le rendre visible qu\'aux membres au minimum).',
  'adm_auths_o_upload_quota_unlimited' => 'Quota d\'upload infini',
  'adm_auths_e_upload_quota_unlimited' => 'Les personnes ayant ce droit ne seront pas limités par le quota d\'upload lorsqu\'elles uploaderont des fichiers depuis leur PC',
  'adm_auths_o_approve_event' => 'Approuver des événements calendrier',
  'adm_auths_e_approve_event' => 'Les personnes ayant ce droit peuvent valider des événements sur le calendrier. A noter que les événements postés par des personnes ayant les droits de validation seront automatiquement approuvés.',
  'adm_auths_o_warn_user' => 'Mettre un avertissement',
  'adm_auths_e_warn_user' => 'Les personnes ayant ce droit peuvent ajouter / supprimer des avertissements à un utilisateur. Il est possible en donnant des avertissements de donner des sanctions à un membre (impossibilité de poster, bannissement temporaire ou permanent). A noter que seul les modérateurs globaux et administrateurs sont habilités à bannir.',
  'adm_auths_o_procedure' => 'Gestion des procédures',
  'adm_auths_e_procedure' => 'Les personnes ayant ce droit peuvent créer des procédures de modération depuis le panneau de modération. Ces procédures servent à effectuer plusieurs actions de modérations en une.',
  'adm_auths_o_confirm_account' => 'Confirmer les inscriptions',
  'adm_auths_e_confirm_account' => 'Les personnes ayant ce droit pourront confirmer les inscriptions des membres en attente, si vous avez activé la validation des inscriptions par les administrateurs.',
  'adm_auths_o_can_see_memberlist' => 'Voir la liste des membres',
  'adm_auths_e_can_see_memberlist' => 'Les personnes ayant ce droit pourront consulter la liste des membres du forum, et la liste des différents groupes.',
  'adm_auths_o_can_see_profile' => 'Voir les profils des membres',
  'adm_auths_e_can_see_profile' => 'Les personnes ayant ce droit pourront consulter les profils des membres du forum.',
  'adm_auths_o_mass_moderation' => 'Modération de masse',
  'adm_auths_e_mass_moderation' => 'Les personnes ayant ce droit pourront déplacer, supprimer et verrouiller plusieurs sujets d\'un coup.',
  'adm_auths_o_log_hidden' => 'Connexion invisible',
  'adm_auths_e_log_hidden' => 'Les personnes ayant ce droit pourront se connecter en invisible sur le forum. Ils n\'apparaîtront pas dans la liste des membres connectés sur le forum, et seront affichés hors ligne dans les sujets. Cependant les modérateurs globaux et administrateurs pourront les voir dans la liste des connectés du forum (en italique).',
  'adm_auths_o_calendar_read' => 'Lire le calendrier',
  'adm_auths_e_calendar_read' => 'Les personnes ayant ce droit pourront consulter le calendrier et voir les différents événements.',
  'adm_auths_o_calendar_write' => 'Poster des événements dans le calendrier',
  'adm_auths_e_calendar_write' => 'Les personnes ayant ce droit pourront poster des événements dans le calendrier.',
  'adm_auths_o_stats_box' => 'Voir la boîte de statistiques',
  'adm_auths_e_stats_box' => 'Les personnes ayant ce droit pourront voir les statistiques sur l\'index du forum.',
  'adm_auths_o_online_box' => 'Voir la boîte des membres en ligne',
  'adm_auths_e_online_box' => 'Les personnes ayant ce droit pourront voir les membres en ligne sur l\'index du forum.',
));


/* EOF */