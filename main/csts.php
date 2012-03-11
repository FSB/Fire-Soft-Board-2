<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

// Donnees en fonction du systeme d'exploitation : les retours a la ligne et les caracteres
// de separation de dossier dans les urls
define('OS_SERVER', (preg_match('/^WIN/', PHP_OS)) ? 'windows' : 'unix');
define('EOF', (OS_SERVER == 'windows') ? "\r\n" : "\n");
define('OS_SLASH', (OS_SERVER == 'windows') ? "\\" : "/");
define('IS_LOCALHOST', $_SERVER['SERVER_ADDR'] === '127.0.0.1' || $_SERVER['SERVER_ADDR'] === '::1');

// Extensions PHP importantes pour FSB2
define('PHP_EXTENSION_GD', (extension_loaded('gd')) ? true : false);
define('PHP_EXTENSION_MBSTRING', (extension_loaded('mbstring')) ? true : false);

// Erreurs manuelles
define('FSB_ERROR', E_USER_ERROR);
define('FSB_MESSAGE', E_USER_NOTICE);

// ID du membre special, visiteur
define('VISITOR_ID', 1);

// Duree a partir duquel un visiteur n'apparait plus dans la liste des membres actuellement en ligne
define('ONLINE_LENGTH', 300);

// Niveaux d'autorisation
define('VISITOR', 0);
define('USER', 1);
define('MODO', 2);
define('MODOSUP', 3);
define('ADMIN', 4);
define('FONDATOR', 5);

// Verouille / Deverouille
define('LOCK', 1);
define('UNLOCK', 0);

// Temps au demarage de la page
define('CURRENT_TIME', time());
define('ONE_HOUR', 3600);
define('ONE_DAY', (24 * ONE_HOUR));
define('ONE_WEEK', (7 * ONE_DAY));
define('ONE_MONTH', (30 * ONE_DAY));
define('ONE_YEAR', (365 * ONE_DAY));

// Temps au dela duquel les messages non lus ne sont plus pris en compte
define('MAX_UNREAD_TOPIC_TIME', CURRENT_TIME - (3 * ONE_MONTH));

// Types de groupes
define('GROUP_SPECIAL', 1);
define('GROUP_NORMAL', 2);
define('GROUP_SINGLE', 3);

// Status des membres du groupe
define('GROUP_MODO', 1);
define('GROUP_USER', 2);
define('GROUP_WAIT', 3);

// Groupe cache
define('GROUP_HIDDEN', 1);

// IDX des groupes speciaux
define('GROUP_SPECIAL_ADMIN', 5);
define('GROUP_SPECIAL_MODOSUP', 4);
define('GROUP_SPECIAL_MODO', 3);
define('GROUP_SPECIAL_USER', 2);
define('GROUP_SPECIAL_VISITOR', 1);

// Type de lecture / ecriture du cache
define('CACHE_TYPE_FILE', 1);
define('CACHE_TYPE_SQL', 2);

// lu / pas lu
define('READ', 1);
define('NOTREAD', 0);

// Modes pour l'edition des droits
define('MODE_TYPE_EASY', 1);
define('MODE_TYPE_SIMPLE', 2);
define('MODE_TYPE_ADVANCED', 3);

// Sexe de l'utilisateur
define('SEXE_NONE', 0);
define('SEXE_MALE', 1);
define('SEXE_FEMALE', 2);

// Methodes pour les avatars
define('AVATAR_METHOD_UPLOAD', 1);
define('AVATAR_METHOD_LINK', 2);
define('AVATAR_METHOD_GALLERY', 3);

// Types de page pour la table profil_fields
define('PROFIL_FIELDS_CONTACT', 1);
define('PROFIL_FIELDS_PERSONAL', 2);

// Types de messages prives
define('MP_INBOX', 0);
define('MP_OUTBOX', 1);
define('MP_SAVE_INBOX', 2);
define('MP_SAVE_OUTBOX', 3);
define('MP_UNREAD', 0);
define('MP_READ', 1);

// Post abusif
define('POST_UNABUSE', 0);
define('POST_ABUSE', 1);

// Sondage
define('TOPIC_NO_POLL', 0);
define('TOPIC_POLL', 1);

// Type de forum
define('FORUM_TYPE_NORMAL', 0);
define('FORUM_TYPE_SUBCAT', 1);
define('FORUM_TYPE_DIRECT_URL', 2);
define('FORUM_TYPE_INDIRECT_URL', 3);

// Comportement des MAPS sur le forum
define('MAP_FP_ONLY', 1);
define('MAP_ALL_POST', 2);
define('MAP_FREE', 3);

// Annonce globale
define('GLOBAL_ANNOUNCE', 0);

// Notification
define('IS_NOTIFIED', 0);
define('IS_NOT_NOTIFIED', 1);
define('NOTIFICATION_AUTO', 1);
define('NOTIFICATION_EMAIL', 2);

// Flags pour la fonction de pagination
define('PAGINATION_PREV', 2);
define('PAGINATION_NEXT', 4);
define('PAGINATION_FIRST', 8);
define('PAGINATION_LAST', 16);
define('PAGINATION_ALL', PAGINATION_PREV|PAGINATION_NEXT|PAGINATION_FIRST|PAGINATION_LAST);

// Ajouter / supprimer un avertissement
define('WARN_MORE', 1);
define('WARN_LESS', 0);

// Messages approuves
define('IS_APPROVED', 0);
define('IS_NOT_APPROVED', 1);

// Notifications
define('NOTIFY_MAIL', 1);
define('NOTIFY_JABBER', 2);
define('NOTIFY_MSN', 4);

// Type de liste des membres
define('USERLIST_ADVANCED', 1);
define('USERLIST_SIMPLE', 2);

// Informations sur le serveur FSB a utiliser pour les informations
define('FSB_REQUEST_SERVER', 'http://www.fire-soft-board.com');
define('FSB_REQUEST_VERSION', '/stream/fsb2version.php');
define('FSB_REQUEST_MODS_VERSION', '/stream/mods_version.php');
define('FSB_REQUEST_TPL_NEWS', '/stream/tpl.php');
define('FSB_REQUEST_MODS_LAST', '/stream/last_mods.php');
define('FSB_REQUEST_MODS_CAT_LIST', '/stream/cat_mods.php');
define('FSB_REQUEST_MODS_CAT', '/stream/cat_mods_content.php?id=%d');
define('FSB_REQUEST_MODS_CONTENT', '/stream/mods_content.php?id=%d');
define('FSB_REQUEST_ROOT_SUPPORT', '/stream/root_support.php?pwd=%s');

// Fichiers divers a nettoyer des archives lorsque l'on les rencontres
// afin de ne pas perturber les processus en cours
define('FSB_TOCLEAN_FILES', 'Thumbs.db,thumbs.db,.DS_Store');

/* EOF */
