<?php
/*
** +---------------------------------------------------+
** | Name :		~/lang/fr/admin/lg_tools_optimize.php
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** |
** | Ce fichier est un fichier langue g?r?utomatiquement
** +---------------------------------------------------+
*/

return (array (
  'tools_optimize_explain_title' => 'Optimisation du forum',
  'tools_optimize_explain_desc' => 'Depuis cette page vous pouvez lancer plusieurs procédures visant à optimiser / resynchroniser votre forum',
  'optimize_menu_chmod' => 'CHMOD',
  'optimize_menu_search' => 'Recherche',
  'optimize_menu_process' => 'Procédures',
  'optimize_menu_replace' => 'Remplacements',
  'optimize_chmod' => 'Vérification des CHMOD',
  'optimize_chmod_explain' => 'Pour bénéficier de certaines fonctionnalités du forum (upload d\'images, accélération du forum), certains dossiers doivent être CHMODés, c\'est à dire posséder un certain nombre de droits d\'accès (écriture et lecture dans la plupart des cas)',
  'optimize_chmod_cache_sql' => 'Ce dossier garde en cache de nombreuses requêtes SQL afin d\'accélérer le forum et de limiter au maximum les accès au serveur SQL',
  'optimize_chmod_cache_xml' => 'Ce dossier garde en cache le calcul du parse des fichiers XML (pour les maps par exemple)',
  'optimize_chmod_avatars' => 'Ce dossier contient les avatars uploadés par les membres',
  'optimize_chmod_ranks' => 'Ce dossier contient les rangs du forum',
  'optimize_chmod_smilies' => 'Ce dossier contient les smilies du forum',
  'optimize_chmod_upload' => 'Ce dossier contient les fichiers uploadés par les membres',
  'optimize_chmod_save' => 'Ce dossier contient les sauvegardes de votre forum en cas de backup ou d\'installation de module',
  'optimize_chmod_adm_tpl' => 'Ce dossier garde en cache le calcul des fichiers du thème de l\'administration',
  'optimize_chmod_tpl' => 'Ce dossier garde en cache le calcul des fichiers du thème <strong>%s</strong>',
  'optimize_is_writable' => 'Est inscriptible',
  'optimize_is_not_writable' => 'N\'est pas inscriptible',
  'optimize_show_tpl' => 'Afficher les dossiers cache/ des thèmes',
  'optimize_hide_tpl' => 'Cacher les dossiers cache/ des thèmes',
  'optimize_chmod_well' => 'Les CHMOD ont correctement été mis à jour',
  'optimize_search' => 'Reconstruire les tables de recherche pour la recherche FULLTEXT FSB',
  'optimize_search_explain' => 'La méthode de recherche que vous utilisez actuellement (fulltext fsb) nécessite que tous les mots des messages soient indexés dans la base de données. Cette procédure d\'optimisation vide les tables de recherche et indexe à nouveau les messages. Plus votre forum est grand, plus cette procédure prendra du temps.',
  'optimize_search_bad' => 'Vous n\'utilisez pas la méthode FULLTEXT FSB pour vos recherches, cette procédure vous est donc inutile',
  'optimize_search_percent' => 'Indexation des messages terminée à <strong>%d</strong>%%',
  'return_to_optimize_search' => 'Cliquez <a href="%s">ici</a> pour continuer l\'indexation',
  'optimize_search_well' => 'L\'indexation des messages a bien été terminée',
  'optimize_process_date' => 'Dernier lancement',
  'optimize_process_next' => 'Prochain lancement',
  'optimize_process_step' => 'Période (jours)',
  'optimize_process_launch' => 'Exécuter',
  'optimize_process' => 'Liste des procédures programmées',
  'optimize_process_explain' => 'Les procédures sont des taches programmées par le forum pour s\'exécuter à certains moments précis. Ces procédures ont pour but de nettoyer le forum, de délester les messages trop vieux sur les forums concernés, etc ...',
  'optimize_process_submit' => 'Les modifications sur les procédures ont bien été enregistrées',
  'optimize_process_prune_forums' => 'Auto délestage des forums',
  'optimize_process_prune_forums_explain' => 'Si l\'auto délestage (suppression automatique de sujets) a été activée sur un forum, cette procédure ira supprimer les sujets "dépassés"',
  'optimize_process_prune_sessions' => 'Nettoyage des sessions',
  'optimize_process_prune_sessions_explain' => 'Supprime les sessions inutilisées et périmées',
  'optimize_process_prune_cache' => 'Nettoyage du cache SQL',
  'optimize_process_prune_cache_explain' => 'Supprime les requêtes SQL mises en cache, afin de recalculer à nouveau automatiquement le résultat de ces requêtes et être sur que le forum soit régulièrement mis à jour',
  'optimize_process_prune_config' => 'Cache de configuration',
  'optimize_process_prune_config_explain' => 'Recalcule certaines données du forum mises en cache dans la configuration (par exemple le nombre de membres inscrits, le nombre de messages sur le forum, etc...)',
  'optimize_process_prune_database' => 'Optimisation de la base de données',
  'optimize_process_prune_database_explain' => 'Pour MySQL: lance des requêtes OPTIMIZE, ANALYZE et REPAIR sur les tables de la base de données.<br />Pour PostgreSQL: lance des requêtes VACUUM sur les tables de la base de données.',
  'optimize_process_prune_pm' => 'Nettoyage des messages privés',
  'optimize_process_prune_pm_explain' => 'Supprime régulièrement les messages privés datant de plus de 6 mois.',
  'optimize_process_check_fsb_version' => 'Vérification de la version du forum',
  'optimize_process_check_fsb_version_explain' => 'Récupère sur le serveur de FSB la dernière version du forum en cours, afin de vous prévenir si le forum a subi une mise à jour',
  'optimize_process_prune_topics_reads' => 'Nettoyage des messages lus',
  'optimize_process_prune_topics_reads_explain' => 'Supprime les entrées de la table topics_read datant de plus 6 mois (valeur par défaut, modifiable dans ~/main/csts.php) afin de libérer de l\'espace dans la base de données.',
  'optimize_process_prune_moved_topics' => 'Suppression des marqueurs de sujets déplacés',
  'optimize_process_prune_moved_topics_explain' => 'Supprime les marqueurs [Déplacé] sur les sujets déplacés, dont le dernier message remonte à 15 jours au moins.',
  'optimize_process_prune_bad_data' => 'Données inutiles',
  'optimize_process_prune_bad_data_explain' => 'Supprime les informations inutiles dans la base de données (messages sans sujets, sujets sans messages, etc ..)',
  'optimize_process_prune_rsa_keys' => 'Renouvellement des clefs RSA',
  'optimize_process_prune_rsa_keys_explain' => 'Renouvelle les clefs de chiffrage de RSA.',
  'optimize_replace_title' => 'Remplacements de mots dans les messages',
  'optimize_replace_explain' => 'Permet de remplacer un mot par un autre dans les messages, les descriptions des sujets et les titres des sujets. Cette page peut être utile si vous changez par exemple de nom de domaine, et que vous souhaitez remplacer toutes les occurrences des anciens liens dans vos messages par le nouveau lien.',
  'optimize_replace_from' => 'Chaîne de caractères recherchée',
  'optimize_replace_to' => 'A remplacer par',
  'optimize_replace_submit' => 'Les chaînes de caractères ont bien été remplacées dans la base de données',
));


/* EOF */
