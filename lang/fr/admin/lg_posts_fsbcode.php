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
  'posts_fsbcode_explain_title' => 'Gestion des FSBcodes',
  'posts_fsbcode_explain_desc' => 'Les FSBcodes sont les balises de mise en forme disponibles dans les messages pour formater le texte (gras, italique, citations, etc ..)..',
  'adm_fsbcode_manage' => 'Liste des FSBcodes',
  'adm_fsbcode_tag' => 'TAG du FSBcode',
  'adm_fsbcode_state' => 'Statut général',
  'adm_fsbcode_sig' => 'Dans les signatures',
  'adm_fsbcode_is_activated' => 'Activé',
  'adm_fsbcode_not_activated' => 'Désactivé',
  'adm_fsbcode_add' => 'Ajouter un FSBcode',
  'adm_fsbcode_edit' => 'Editer un FSBcode',
  'adm_fsbcode_search' => 'Masque de recherche',
  'adm_fsbcode_search_explain' => 'Vous devez entrer ici les informations qui devront être recherchées dans le message, afin de rendre actif le FSBcode. Par exemple si vous créez un FSBcode nommé "test": <b>[test]{TEXT}[/test]</b> ou bien <b>[test={URL}]{TEXT}[/test]</b>. Les mots clefs en majuscules dans les accolades sont des variables. Voici les différentes variables:<ul><li>{TEXT}: recherche n\'importe quel texte.</li><li>{NUMBER}: recherche un nombre.</li><li>{COLOR}: recherche une couleur hexadécimale (#f3f3f3) ou CSS (black, red, etc..)</li><li>{URL}: recherche une adresse internet, commençant par http:// ou bien www. par exemple.</li><li>{EMAIL}: recherche une adresse email.</li></ul>Veuillez noter que vous pouvez numéroter vos variables si vous souhaitez en utiliser plusieurs du même type, par exemple {TEXT1}, {TEXT2}, etc.',
  'adm_fsbcode_replace' => 'Masque de remplacement',
  'adm_fsbcode_replace_explain' => 'Vous devez entrer ici le code HTML par lequel sera remplacé le masque de recherche du FSBcode. Par exemple <b>&lt;span style="border: 1px solid #000000"&gt;{TEXT}&lt;/span&gt;</b> ou bien <b>&lt;a href="{URL}"&gt;{TEXT}&lt;/a&gt;</b>. Les variables entre les accolades correspondent aux variables recherchées dans votre masque de recherche.',
  'adm_fsbcode_activated' => 'FSBcode activé sur le forum',
  'adm_fsbcode_activated_sig' => 'FSBcode activé dans les signatures',
  'adm_fsbcode_fct' => 'Fonction PHP assignée',
  'adm_fsbcode_bad_tag' => 'Le tag du FSBcode ne doit contenir que des caractères alphabétiques',
  'adm_fsbcode_well_add' => 'Le FSBcode a bien été ajouté',
  'adm_fsbcode_well_edit' => 'Le FSBcode a bien été édité',
  'adm_fsbcode_well_delete' => 'Le FSBcode a bien été supprimé',
  'adm_fsbcode_delete_confirm' => 'Etes-vous sur de vouloir supprimer ce FSBcode ?',
  'adm_fsbcode_img' => 'Image du FSBcode',
  'adm_fsbcode_img_explain' => 'Image devant être située dans le dossier img/fsbcode/ de votre thème (dimensions de l\'image: 23x22). Si vous laissez ce champ vide, le tag du FSBcode sera utilisé par défaut.',
  'adm_fsbcode_list' => 'Contenu de la liste',
  'adm_fsbcode_list_explain' => 'Ne renseignez ce champ que s\'il s\'agit d\'une liste (comme pour les FSBcode Police ou Taille). Entrez les éléments de votre liste en les séparant par un saut de ligne.',
  'adm_fsbcode_description' => 'Description du FSBcode',
  'adm_fsbcode_description_explain' => 'La description du FSBcode apparaît en laissant la souris sur le bouton dans la fenêtre de réponse. En laissant ce champ vide, le forum regardera s\'il existe une clef de langue fsbcode_TAG pour les FSBcodes simple et fsbcode_text_TAG pour les listes.',
  'adm_fsbcode_up' => 'Monter le FSBcode',
  'adm_fsbcode_down' => 'Descendre le FSBcode',
  'adm_fsbcode_menu' => 'Afficher une icone du FSBcode',
  'adm_fsbcode_menu_explain' => 'En cochant OUI, si le FSBcode est activé, son image (ou le cas échéant son icone) apparaîtra dans la liste des FSBcode au dessus du forumulaire d\'envoie de messages.',
));


/* EOF */