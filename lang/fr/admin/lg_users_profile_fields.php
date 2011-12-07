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
  'users_profile_fields_explain_title' => 'Gestion des champs du profil',
  'users_profile_fields_explain_desc' => 'Sur cette page vous pourrez créer / modifier / supprimer des champs pour le profil des membres. Deux types de champs sont paramétrables: les champs personnels (pouvant apparaître dans les sujets et le profil du membre) et les champs de contact (apparaissant dans le profil public du membre).',
  'adm_profile_fields_personal' => 'Informations personnelles',
  'adm_profile_fields_contact' => 'Champs de contact',
  'adm_pf_well_add' => 'Le champ personnel a bien été créé',
  'adm_pf_well_edit' => 'Le champ personnel a bien été modifié',
  'adm_pf_well_delete' => 'Le champ personnel a bien été supprimé',
  'adm_pf_not_exists' => 'Ce champ n\'existe pas',
  'adm_pf_confirm_delete' => 'Etes-vous sûr de vouloir supprimer ce champ ?',
  'adm_pf_personal' => 'Champs personnels',
  'adm_pf_contact' => 'Champs de contact',
  'adm_pf_add_personal' => 'Ajout / édition de champ personnel',
  'adm_pf_add_contact' => 'Ajout / édition de champ de contact',
  'adm_pf_up' => 'Monter',
  'adm_pf_down' => 'Descendre',
  'adm_pf_add' => 'Ajouter un champ de profil',
  'adm_pf_type_text' => 'Texte court',
  'adm_pf_type_textarea' => 'Texte long',
  'adm_pf_type_radio' => 'Boutons',
  'adm_pf_type_select' => 'Liste',
  'adm_pf_type_multiple' => 'Liste à plusieurs choix',
  'adm_pf_print' => 'Affichage du champ sur le forum',
  'adm_pf_need_lang' => 'Vous devez donner un nom au champ personnel',
  'adm_pf_need_list' => 'Vous devez au moins entrer un élément à lister',
  'adm_pf_bad_maxlength' => 'La taille maximale doit être comprise entre %d et %d',
  'adm_pf_bad_sizelist' => 'La hauteur de la liste doit être supérieure ou égale à 0',
  'adm_pf_bad_regexp' => 'La regexp entrée est syntaxiquement invalide',
  'adm_pf_name' => 'Nom du champ de profil',
  'adm_pf_desc' => 'Description du champ de profil',
  'adm_pf_type' => 'Type de champ',
  'adm_pf_change_type' => 'Changer de type',
  'adm_pf_name_explain' => 'Le champ qui s\'affichera dans les profils pour ce formulaire personnel. Vous pouvez utiliser une clef de langue en mettant le texte suivant: {LG_CLEF_DE_LANGUE} (et donc en créant une clef de langue nommée "clef_de_langue" dans l\'administration des langues).',
  'adm_pf_desc_explain' => 'La description du champ qui s\'affichera dans les profils pour ce formulaire personnel. Vous pouvez utiliser une clef de langue en mettant le texte suivant: {LG_CLEF_DE_LANGUE} (et donc en créant une clef de langue nommée "clef_de_langue" dans l\'administration des langues).',
  'adm_pf_regexp' => 'Expression de vérification du champ',
  'adm_pf_regexp_explain' => 'N\'utilisez ce champ que si vous savez ce que vous faîtes ! Il permet de vérifier la validité des champs personnels de type "texte court" à l\'aide d\'une <a href="http://fr.php.net/manual/fr/ref.pcre.php">expression régulière</a>. Par exemple pour vérifier la validité d\'une adresse email entrez: <strong>^.*?@.*?\\..{2,4}$</strong>',
  'adm_pf_maxlength' => 'Nombre maximum de caractères',
  'adm_pf_maxlength_explain' => 'Définit le nombre maximum de caractères qu\'on peut entrer dans ce champ. Ce nombre doit être compris entre %d et %d',
  'adm_pf_sizelist' => 'Hauteur de la liste',
  'adm_pf_sizelist_explain' => 'Définit le nombre d\'éléments de liste affichés en même temps sur la hauteur. Ce nombre doit être supérieur à 0. Si vous laissez 0, la liste aura comme hauteur le nombre d\'éléments qu\'elle contient',
  'adm_pf_textarea_explain' => 'Définit le nombre maximum de caractères que le membre pourra entrer dans la zone de texte. Si vous entrez 0 il pourra en saisie autant qu\'il le souhaite',
  'adm_pf_list' => 'Liste de valeurs',
  'adm_pf_list_explain' => 'Entrez une liste de valeurs, en séparant chaque élément par un saut de ligne',
  'adm_pf_groups' => 'Groupes pouvant voir le champ',
  'adm_pf_groups_explain' => 'Sélectionnez les groupes qui pourront voir la valeur du champ dans le profil des membres. Si vous ne sélectionnez aucun groupe, tous pourront le voir',
  'adm_pf_topic' => 'Afficher dans les sujets',
  'adm_pf_topic_explain' => 'Permet de voir la valeur du champ dans les données personnelles du membre sous son avatar dans les sujets',
  'adm_pf_register' => 'Afficher lors de l\'inscription',
  'adm_pf_register_explain' => 'Affiche ce champ dans le formulaire d\'inscription des nouveaux membres',
  'adm_pf_output' => 'Formatage d\'affichage du champ',
  'adm_pf_output_explain' => 'Vous pouvez déterminer la façon dont sera affiché la valeur du champ de profil ici. La variable {TEXT} représente le choix fait par le membre du forum. Vous pouvez donc par exemple faire un formatage de ce type pour une URL: <br />&lt;a href="{TEXT}"&gt;{TEXT}&lt;/a&gt;<br />Si le champ est vide, le forum considèrera qu\'il contient {TEXT} par défaut. Si vous le remplissez, attention à bien y entrer la variable {TEXT} pour afficher le choix du membre.',
));


/* EOF */
