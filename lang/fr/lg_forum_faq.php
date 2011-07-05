<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

$GLOBALS['faq_data'] = array (
  'forum' => 
  array (
    'why_register' => 
    array (
      'question' => 'Pourquoi s\'enregistrer ?',
      'answer' => 'En vous enregistrant sur le forum vous pouvez bénéficier de nombreuses options supplémentaires telles que :
			<ul>
			<li>Lire et poster dans des forums réservés aux inscrits.</li>
			<li>Avoir votre pseudonyme réservé à vous seul.</li>
			<li>Pouvoir envoyer des emails et des messages privés aux autres membres.</li>
			<li>Choisir un avatar (une image personnalisée à côté de votre pseudonyme) et une signature.</li>
			<li>Avoir la possibilité de modifier vos préférences pour le forum (thème, langue, affichage, etc...).</li>
			<li>Avoir la possibilité de surveiller des sujets dans les forums (en étant notifié d\'une réponse par email).</li>
			</ul>',
    ),
    'register' => 
    array (
      'question' => 'Comment s\'inscrire sur le forum',
      'answer' => 'Pour vous inscrire sur le forum il vous suffit de vous rendre sur la 
			<a href="index.php?p=register">page d\'inscription</a> et de
			remplir correctement le formulaire.<br />Vous devrez tout d\'abord <b>entrer un login</b>, il s\'agit en quelque sorte d\'un pseudo
			qui ne servira que pour vous connecter, évitez de le communiquer aux autres. Vous pourrez ensuite choisir <b>un pseudonyme</b>,
			qui lui apparaitra sur le forum et créera votre identité, les autres membres verront ce pseudonyme.
			<br />Veuillez
			noter que si vous laissez ce champ vide, il prendra la même valeur que celui de votre login. Choisissez ensuite
			un <b>mot de passe</b> et votre <b>adresse email</b>. Entrez une adresse email valide car il se peut qu\'un code de
			confirmation vous soit envoyé ! Une fois inscrit, le login et le mot de passe que vous avez entré serviront
			à vous connecter sur le forum, veillez donc à ne pas les oublier.<br /><br />
			Suivant la configuration du forum il se peut qu\'un <b>code de confirmation visuelle</b> vous soit demandé, il s\'agit d\'une image
			avec une série de chiffres / lettres. Vous devrez écrire le mot que vous voyez dans le champ associé, ce système
			évite que des robots s\'inscrivent en boucle sur le forum.
		',
    ),
    'login' => 
    array (
      'question' => 'Comment se connecter sur le forum',
      'answer' => 'Pour vous connecter sur le forum vous devez au préalable vous enregistrer, puis vous rendre sur la <a href="index.php?p=login">page de connexion</a>. Entrez votre <b>login</b> et votre mot de passe.<br />
			L\'option <b>connexion automatique</b> vous permettra d\'être perpétuellement connecté à chaque future visite.<br />
			L\'option <b>connexion en invisible</b> ne vous fera pas apparaitre dans la liste des membres en ligne (si vous ne souhaitez que votre connexion soit sue des autres membres).',
    ),
    'cookies' => 
    array (
      'question' => 'A quoi servent les "cookies", "javascript" et "ajax" sur ce forum ?',
      'answer' => '<b>Les cookies</b> sont des petits fichiers temporaires dans votre navigateur, permettant certaines fonctions utiles (la sauvegarde de la dernière page du profil que vous avez visité afin de vous remettre dessus à la prochaine visite, par exemple, ou bien la possibilité de se connecter automatiquement à chaque visite). En outre les cookies sont importants pour pouvoir rester connecté sur le forum. Sans cookie vous pourrez tout de même vous connecter et naviguer sur ce forum, cependant des risques de déconnexion peuvent survenir. Les cookies ne sont pas dangereux et ne sont pas destinés à des fins de spyware ou autre.<br /><br />
	  <b>Javascript</b> est une technologie de votre navigateur permettant de rendre votre navigation plus interactive et plus agréable. Sans Javascript vous ne pourrez pas utiliser l\'éditeur de texte pour les messages, vous ne pourrez pas utiliser les boutons FSBcodes, etc ...<br /><br />
	  <b>Ajax</b> est une technologie permettant certaines options pratiques sur le forum, comme l\'édition ou la citation instantanée. Vous pouvez le désactiver dans votre profil si besoin. Cette technologie nécessite que votre navigateur supporte Javascript, et plus particulièrement les fonctions XmlHttpRequest.',
    ),
    'profile' => 
    array (
      'question' => 'Comment modifier les données personnelles de mon profil ?',
      'answer' => 'Pour modifier les données de votre profil veuillez vous rendre sur la <a href="index.php?p=profile">page de modification du profil</a>. Le menu à gauche comprend les différentes fonctions du profil :
			<ul>
			<li><b>Préférences:</b>: pour modifier vos préférences du forum comme l\'affichage des images, des avatars, de l\'éditeur de texte, de votre email, etc...</li>
			<li><b>Avatar:</b> pour modifier votre avatar (petite image affichée à côté de votre pseudonyme dans les sujets).</li>
			<li><b>Contact:</b> pour remplir les champs de contact disponibles (adresse MSN, etc ...).</li>
			<li><b>Groupes:</b> pour voir les différents groupes disponibles du forum, et pour voir les groupes auxquels vous appartenez.</li>
			<li><b>Mot de passe:</b> pour modifier votre login et / ou votre mot de passe. Veuillez noter que pour modifier un de ces deux champs vous devrez connaître votre login et votre mot de passe d\'origine.</li>
			<li><b>Informations personnelles:</b> pour modifier vos données personnelles telles que votre adresse email, le thème du forum que vous préférez, votre âge, etc ...</b>
			<li><b>Signature:</b> pour écrire une signature personnalisée, qui apparaîtra en dessous de chacun de vos messages sur le forum.</li>
			<li><b>Fichiers joints:</b> pour voir la liste des fichiers que vous avez uploadé sur ce forum.</li>
			</ul>',
    ),
    'avatar' => 
    array (
      'question' => 'Comment utiliser un avatar sur le forum ?',
      'answer' => 'Vous avez différents moyens d\'utiliser un avatar (petite image affichée à côté de votre pseudonyme dans les sujets) sur le forum. Vous devez pour cela utiliser la page <b>avatar</b> de votre profil personnel :
			<ul>
			<li><b>Galerie d\'avatar:</b> Suivant la configuration du forum, le webmaster aura peut être créé une galerie d\'avatars, auquel cas vous pourrez choisir un avatar parmi la panoplie existante.</li>
			<li><b>Uploader un avatar:</b> Si vous avez une image sur votre ordinateur que vous souhaitez utiliser comme avatar, vous pouvez l\'uploader depuis votre PC en choisissant son emplacement.</li>
			<li><b>Uploader un avatar depuis une URL:</b> Si vous trouvez une image sur le web qui vous intéresse en guise d\'avatar, vous pouvez utiliser ce champ en entrant l\'adresse de l\'image (URL). Le forum uploadera directement l\'image sur le forum.</li>
			<li><b>Lier un avatar:</b> Même système que précédemment, si une image sur le net vous intéresse entrez son URL dans ce champ. A la différence du champ précédent, l\'image sera liée à distance, et son affichage ne dépendra donc pas du forum mais du site hébergeant cette image.</li>
			</ul>
			<br />
			Veuillez noter que suivant la configuration du forum, certains de ces champs pourront être masqués.',
    ),
    'auth' => 
    array (
      'question' => 'Pourquoi ne puis-je pas poster dans tous les forums ?',
      'answer' => 'Suivant la configuration du webmaster, certains forums ne sont pas forcément accessibles à tout le monde. Vérifiez que vous êtes inscrit sur le forum, car généralement les visiteurs n\'ont pas la possibilité de poster (pour des raisons de sécurité).',
    ),
    'post' => 
    array (
      'question' => 'Comment poster un sujet / répondre à un message ?',
      'answer' => 'Pour poster dans un forum vous devez tout d\'abord avoir <b>le droit de poster dans ce forum</b>. Si vous avez l\'autorisation de poster des sujets, un bouton "nouveau sujet" devrait être disponible. De même dans les sujets un bouton "répondre" sera disponible pour vos réponses dans un sujet. Une fois dans la fenêtre pour poster un sujet, remplissez les champs obligatoires (entrez un sujet, un message, votre login si vous êtes un visiteur, etc...) et postez votre message. Vous pouvez utiliser des balises formatage (gras, italique, etc ...) nommées <b>FSBcodes</b>, veuillez lire la FAQ FSBcodes pour plus d\'informations. A noter que si vous utilisez un navigateur commun (Internet Explorer, Firefox, Opéra, Safari, etc...) vous pourrez utiliser <b>l\'éditeur WYSIWYG</b>, un éditeur de texte affichant directement le formatage de vos messages.',
    ),
    'announce' => 
    array (
      'question' => 'Que sont les annonces / notes ?',
      'answer' => 'Les annonces et les notes sont des sujets particulièrement importants affichés en tête de forum. Il est conseillé de lire ces sujets en priorité, ils regroupent généralement des informations importantes pour le forum.',
    ),
    'poll' => 
    array (
      'question' => 'Comment créer un sondage ?',
      'answer' => 'Pour créer un sondage vous devez vous rendre dans la fenêtre de création de nouveaux sujets, et cliquer sur le petit bouton "sondage" en bas. Ensuite veuillez remplir les champs de création de sondage :
			<ul>
			<li><b>Titre du sondage:</b> la question du sondage.</li>
			<li><b>Options du sondage:</b> entrez les différentes réponses possibles du sondage. Chaque réponse doit être séparée d\'un saut de ligne.</li>
			<li><b>Nombre de réponses:</b> définit le nombre de réponses que les membres pourront choisir pour voter. Par défaut un sondage comprend une seule réponse possible.</li>
			</ul>',
    ),
    'upload' => 
    array (
      'question' => 'Comment uploader un fichier sur le forum ?',
      'answer' => 'Vous devez vous rendre dans la fenêtre de création de sujets (ou de réponses), puis cliquer sur le petit bouton "uploader / joindre un fichier" en bas. Entrez ensuite le chemin du fichier sur votre ordinateur, entrez si besoin un commentaire optionnel et soumettez le formulaire. Suivant la taille du fichier la procédure peut être plus ou moins longue. Une fois le fichier uploadé une balise sera ajoutée automatiquement dans le message, vous pouvez déplacer cette balise si besoin ou la réutiliser partout ailleurs sur le forum.<br /><br />
			<b>Veuillez noter</b> que les fichiers que vous uploadez doivent comporter des extensions autorisées par le forum.',
    ),
    'mp' => 
    array (
      'question' => 'A quoi sert la messagerie privée ?',
      'answer' => 'La messagerie privée est un système vous permettant d\'envoyer des messages à d\'autres
			membres par l\'intermédiaire du forum. Vous pouvez ainsi communiquer de façon privée avec d\'autres membres sans
			poster de sujets sur le forum. La messagerie privée est composée de trois boîtes distinctes :
			<ul>
			<li><b>La boîte de réception</b> stocke les messages (nouveaux ou lus) que vous avez reçu.</li>
			<li><b>La boîte d\'envoi</b> stocke les messages que vous avez envoyé (il est indiqué si le récepteur a lu ou non le message).</li>
			<li><b>La boîte d\'archive</b> contient les messages importants que vous avez sauvegardé.</li>
			<li><b>Les options</b> sont décrites ci-dessous (blacklist et répondeur).</li>
			</ul><br /><br />
			<b>La blacklist</b> est un moyen vous permettant d\'empêcher certains utilisateurs de vous envoyer
			des messages privés. En ajoutant un utilisateur à la blacklist les messages privés qu\'il vous enverra seront automatiquement
			effacés de votre boîte de réception. Cependant ils apparaitront toujours dans sa boîte d\'envoi, ainsi il ne pourra pas
			savoir que vous l\'avez blacklisté.<br />
			<b>Le répondeur</b> est un outil permettant d\'envoyer automatiquement un message de réponse à un membre qui vous envoie 
			un message privé. Pour pouvoir utiliser cette fonctionnalité vous devez l\'activer dans le panneau d\'option des
			messages privés, et entrer un message pour le répondeur. Utilisez cette option si par exemple vous partez
			en vacances, afin de le signaler aux membres qui tentent de vous contacter.
		',
    ),
    'search' => 
    array (
      'question' => 'Comment faire une recherche sur le forum',
      'answer' => 'Pour faire une recherche de mots clefs sur le forum rendez vous sur la <a href="index.php?p=search">page de recherche</a>, entrez les mots recherchés, choisissez les forums concernés par votre recherche (afin d\'affiner la pertinence de la recherche), et lancez la recherche. Veuillez noter que les mots de moins de 3 lettres, ainsi que les mots trop courants, ne sont pas pris en compte par la recherche pour éviter de retourner trop de résultats pas forcément pertinents.',
    ),
    'calendar' => 
    array (
      'question' => 'Comment ajouter des événements sur le calendrier ?',
      'answer' => 'Pour ajouter des événements sur le calendrier vous devez vous rendre sur la <a href="index.php?p=calendar">page du calendrier</a>, et cliquer sur le bouton d\'ajout d\'événement en bas de page. Entrez ensuite la date de l\'événement (la date peut être répartie sur plusieurs jours), le titre et la description de l\'événement. Vous pouvez choisir de ne l\'afficher que pour vous même si vous le souhaitez. Si vous choisissez de l\'afficher pour tous les membres l\'événement sera au préalable validé par un modérateur.',
    ),
	'color' => array(
		'question' => 'Pourquoi certains utilisateurs apparaissent de couleurs différentes ?',
		'answer' => 'Ces utilisateurs appartiennent à des groupes spéciaux. Par exemple les administrateurs et les modérateurs ont des couleurs particulières.',
	),
    'auth_level' => 
    array (
      'question' => 'Que sont les modérateurs et les administrateurs ?',
      'answer' => '<b>Les modérateurs</b>, comme leur nom l\'indique, servent à surveiller et à modérer le forum. Ils veillent ainsi au bon déroulement des discussions, peuvent altérer les forums qu\'ils modèrent (supprimer, éditer, déplacer, verrouiller des sujets), et sanctionner d\'avertissements des utilisateurs.<br /><br />
			<b>Les administrateurs</b> ont tous les droits sur le forum. Ils peuvent modérer chacun des forums, et modifier toutes les données du forum: créer / supprimer des forums, gérer les groupes, bannir des utilisateurs, modifier la configuration, etc ...',
    ),
    'groups' => 
    array (
      'question' => 'Qu\'est ce qu\'un "groupe" sur le forum ?',
      'answer' => 'Un groupe est un ensemble de personne réunies, ayant des droits communs sur le forum et pouvant accéder à des forums particuliers suivant la configuration. La liste des groupes est affichée dans votre profil personnel. Pour rejoindre un groupe il vous suffit de vous rendre sur la page du groupe depuis votre profil, et de vous inscrire dans le champ prévu à cet effet si vous en avez l\'autorisation. Un modérateur du groupe validera ou non votre demande d\'adhésion.',
    ),
   'utc' =>
   array (
     'question' => 'Qu\'est ce que l\'UTC ?',
     'answer' => 'L\'UTC (aussi appelée GMT pour Greenwich Mean Time) est une référence pour déterminer l\'heure. Selon l\'endroit où vous habitez, vous n\'avez pas la même heure au même moment.<br />Par exemple, Paris, pendant l\'hiver, est en UTC+1 alors qu\'en été elle est en UTC+2.<br />Si vous ne savez pas quel est votre zone horaire (votre UTC), vous pouvez chercher ici: <a href="http://www.timeanddate.com/worldclock/search.html">Time and Date</a>.',
   ),
  ),
  'fsbcode' => 
  array (
    'b' => 
    array (
      'question' => 'Comment mettre en gras mon texte ?',
      'answer' => 'Il est possible de mettre en gras son texte à l\'aide des balises &#91;b&#93; et &#91;/b&#93;.<br />
			Voici un exemple d\'utilisation:
			[code]Salut je veux [b]mettre ce texte en gras[/b][/code]
			qui affichera
			[quote]Salut je veux [b]mettre ce texte en gras[/b][/quote]
		',
    ),
    'i' => 
    array (
      'question' => 'Comment mettre en italique mon texte ?',
      'answer' => 'Il est possible de mettre en italique son texte à l\'aide des balises &#91;i&#93; et &#91;/i&#93;.<br />
			Voici un exemple d\'utilisation:
			[code]Salut je veux [i]mettre ce texte en italique[/i][/code]
			qui affichera
			[quote]Salut je veux [i]mettre ce texte en italique[/i][/quote]
		',
    ),
    'u' => 
    array (
      'question' => 'Comment mettre en souligné mon texte ?',
      'answer' => 'Il est possible de souligner son texte à l\'aide des balises &#91;u&#93; et &#91;/u&#93;.<br />
			Voici un exemple d\'utilisation:
			[code]Salut je veux [u]mettre ce texte en souligné[/u][/code]
			qui affichera
			[quote]Salut je veux [u]mettre ce texte en souligné[/u][/quote]
		',
    ),
    'url' => 
    array (
      'question' => 'Comment faire un lien vers une page web ?',
      'answer' => 'Pour faire des liens hypertexte, vers une autre page web, utilisez la balise &#91;url&#93;.<br />
			Voici un exemple d\'utilisation:
			[code]Le site officiel de FSB: [url]http://www.fire-soft-board.com[/url][/code]
			qui affichera
			[quote]Le site officiel de FSB: [url]http://www.fire-soft-board.com[/url][/quote]
			
			Il est possible de donner un nom à un lien, et de faire pointer ce nom vers une page. Par exemple :
			[code]Visitez le [url=http://www.fire-soft-board.com]site officiel de FSB[/url][/code]
			qui affichera
			[quote]Visitez le [url=http://www.fire-soft-board.com]site officiel de FSB[/url][/quote]
		',
    ),
    'mail' => 
    array (
      'question' => 'Comment faire un lien vers une adresse email ?',
      'answer' => 'Pour faire un lien vers une adresse email, utilisez la balise &#91;mail&#93;.<br />
			Voici un exemple d\'utilisation:
			[code]Adresse email de l\'administrateur: [mail]no-reply@fire-soft-board.com[/mail][/code]
			qui affichera
			[quote]Adresse email de l\'administrateur: [mail]no-reply@fire-soft-board.com[/mail][/quote]
			
			il est possible de donner un nom à un lien, et de faire pointer ce nom vers une adresse email. Par exemple :
			[code]Pour contacter un administrateur [mail=no-reply@fire-soft-board.com]cliquez ici[/mail][/code]
			qui affichera
			[quote]Pour contacter un administrateur [mail=no-reply@fire-soft-board.com]cliquez ici[/mail][/quote]
		',
    ),
	'img' =>
	array (
		'question' => 'Comment poster une image sur le forum ?',
		'answer' => 'Pour poster une image sur le forum utilisez la balise &#91;img&#93;.<br />
			Voici un exemple d\'utilisation :
			[code][img]http://www.fire-soft-board.com/fsb/tpl/WhiteSummer/img/logo.png[/img][/code]
			qui affichera<br />
			[img]http://www.fire-soft-board.com/fsb/tpl/WhiteSummer/img/logo.png[/img]
			<br /><br />
			Vous pouvez aussi utiliser des options spéciales pour cette balise, tel que le redimensionnement ou la position flottante :
			[code][img:height=50]http://www.fire-soft-board.com/fsb/tpl/WhiteSummer/img/logo.png[/img][/code]
			affichera<br />
			[img:height=50]http://www.fire-soft-board.com/fsb/tpl/WhiteSummer/img/logo.png[/img]
			<br /><br />
			[code][img:height=50,width=300]http://www.fire-soft-board.com/fsb/tpl/WhiteSummer/img/logo.png[/img][/code]
			affichera<br />
			[img:height=50,width=300]http://www.fire-soft-board.com/fsb/tpl/WhiteSummer/img/logo.png[/img]
			<br /><br />
			[code][img:alt=Texte au survol,title=Texte au survol]http://www.fire-soft-board.com/fsb/tpl/WhiteSummer/img/logo.png[/img][/code]
			affichera<br />
			[img:alt=Texte au survol,title=Texte au survol]http://www.fire-soft-board.com/fsb/tpl/WhiteSummer/img/logo.png[/img]
			<br /><br />
			Vous pouvez donner une position flottante à votre image comme ceci :
			[code][img:float=left]http://www.fire-soft-board.com/fsb/tpl/WhiteSummer/img/logo.png[/img]
            Ce texte sera positionné de façon flottante à coté de l\'image, permettant ainsi une mise en page bien plus agréable sur votre forum.[/code]<br />
			[img:float=left]http://www.fire-soft-board.com/fsb/tpl/WhiteSummer/img/logo.png[/img]Ce texte sera positionné de façon flottante à coté de l\'image, permettant ainsi une mise en page bien plus agréable sur votre forum. 
		',
	),
    'list' => 
    array (
      'question' => 'Comment faire une énumération (liste) ?',
      'answer' => 'Utilisez la balise &#91;list&#93; pour les énumérations, par exemple pour une liste basique non numérotée :
			[code][list]
[*]Premier élément
[*]Second élément
[*]Troisième élément
[/list][/code]
			qui affichera
			[list]
			[*]Premier élément
			[*]Second élément
			[*]Troisième élément
			[/list]
			<br />
			Vous pouvez aussi numéroter vos listes, par exemple :
			[code][list=1]
[*]Premier élément
[*]Second élément
[*]Troisième élément
[/list][/code]
			qui affichera
			[list=1]
			[*]Premier élément
			[*]Second élément
			[*]Troisième élément
			[/list]
			<br />
			ou bien
			[code][list=a]
[*]Premier élément
[*]Second élément
[*]Troisième élément
[/list][/code]
			qui affichera
			[list=a]
			[*]Premier élément
			[*]Second élément
			[*]Troisième élément
			[/list]
			<br />
			Veuillez noter que vous pouvez aussi imbriquer les listes :
			[code][list]
[*]Premier élément
[list=1]
	[*]Premier élément seconde liste
	[*]Second élément seconde liste
[/list]
[/list][/code]
			qui affichera
			[list]
			[*]Premier élément
			[list=1]
			[*]Premier élément seconde liste
			[*]Second élément seconde liste
			[/list]
			[/list]
			',
    ),
    'color' => 
    array (
      'question' => 'Comment ajouter des couleurs au texte ?',
      'answer' => 'Pour colorer certaines parties du texte veuillez utiliser la balise &#91;color&#93;, par exemple :
			[code]Texte [color=red]coloré en rouge[/color] par exemple[/code]
			affichera
			[quote]Texte [color=red]coloré en rouge[/color] par exemple[/quote]',
    ),
    'bgcolor' => 
    array (
      'question' => 'Comment surligner en couleur le texte ?',
      'answer' => 'Pour surligner en couleur le texte veuillez utiliser la balise &#91;bgcolor&#93;, par exemple :
			[code]Texte [bgcolor=red]coloré en rouge[/bgcolor] par exemple[/code]
			affichera
			[quote]Texte [bgcolor=red]coloré en rouge[/bgcolor] par exemple[/quote]',
    ),
  ),
  'modo' => 
  array (
	'abuse' => array(
		'question' => 'Que sont les messages abusifs ?',
		'answer' => 'Les messages abusifs sont des messages signalés par un membre, rapportant à priori des propos non conformes aux règles du forum (insultes, flood, etc ...). Dans l\'onglet de modération des messages abusifs vous pouvez voir les messages abusifs signalés sur les forums que vous modérez. Vous pouvez éditer directement ces messages, ou bien vous rendre aux messages en question en cliquant sur le nom du sujet du message. Une fois le message traité, vous pouvez cliquer sur <b>Valider</b> pour valider et supprimer ce rapport de message abusif.',
	),
	'delete' => array(
		'question' => 'Comment supprimer un sujet ?',
		'answer' => 'Pour supprimer un sujet il vous suffit de cliquer sur la petite icone de modération de suppression en bas de ce sujet. Vous pouvez également supprimer plusieurs sujets à la fois en passant par la modération globale d\'un forum.',
	),
	'move' => array(
		'question' => 'Comment déplacer un sujet ?',
		'answer' => 'Pour déplacer un sujet il vous suffit de cliquer sur la petite icone de modération de déplacement en bas de ce sujet. Ensuite choisissez un forum de destination (dans lequel vous avez le droit de poster), choisissez si vous souhaitez ou non qu\'une trace du sujet subsiste dans son forum d\'origine, puis validez le déplacement. Vous pouvez également déplacer plusieurs sujets à la fois en passant par la modération globale d\'un forum.',
	),
	'split' => array(
		'question' => 'Comment diviser un sujet ?',
		'answer' => 'Pour diviser un sujet il vous suffit de cliquer sur la petite icone de modération de division en bas de ce sujet. Vous devez ensuite choisir un titre pour le nouveau sujet, ainsi qu\'un forum de destination, puis vous devez cocher les messages que vous souhaitez déplacer du message d\'origine vers le sujet de destination.',
	),
	'warn' => array(
		'question' => 'Comment donner / supprimer des avertissements à un membre ?',
		'answer' => 'Si vous souhaitez donner un avertissement à un membre, ou au contraire lui en supprimer un il vous suffit de cliquer sur les icones d\'ajout / suppression à côté de la jauge d\'avertissement d\'un utilisateur dans un de ses messages. Vous pouvez aussi passer directement par le panneau de modération. Entrez ensuite une raison à cet avertissement, puis vous pouvez donner (en cas d\'ajout d\'avertissement), ou réduire (en cas de suppression d\'avertissement) les sanctions du membre. Vous pouvez agir sur ses droits d\'écriture (créer des sujets, répondre à des sujets) ou bien sur ses droits de lecture (droit de voir et de lire les forums). Vous pouvez pour finir lui envoyer un message par email ou par messagerie privée pour lui signaler les raisons de cet avertissement.<br />
		Veuillez noter que les utilisateurs n\'ont pas accès à leur niveau d\'avertissement. Vous pouvez consulter les avertissements d\'un membre depuis le panneau de modération.',
	),
  ),
  'admin' => 
  array (
    'auth' => 
    array (
      'question' => 'Les droits des forums',
      'answer' => 'Les droits des forums sont une étape importante dans la création de votre forum. Ce sont eux qui définissent qui a le droit de faire quoi sur chacun des forums.<br /><br />La première partie des droits (la plus importante), est la partie regroupant les onglets <b>Forums, Groupes et Membres</b> :
	  <ul>
	  <li><b>La gestion des droits des forums</b> permet de choisir les droits qu\'auront les différents groupes du forum, en fonction du forum choisi.</li>
	  <li><b>La gestion des droits des groupes</b> permet de définir les droits qu\'aura ce groupe sur les différents forums.</li>
	  <li><b>La gestion des membres</b> permet de donner des droits à un membre particulier sur chacun des forums.</li>
	  </ul>
	  <br />
	  Pour mieux comprendre le fonctionnement nous allons expliquer le fonctionnement des modifications des droits d\'un forum. Il suffit d\'aller dans l\'onglet <b>Forums</b> et de choisir le forum qu\'on veut modifier. On remarque qu\'il y a trois <b>Types d\'interface</b>, permettant chacun de gérer les droits de façon plus ou moins facile. Choisissez l\'interface qui vous convient :
	  <ul>
	  <li><b>Interface très simplifiée:</b> (qui est la même interface que pour <i>FSB version 1</i>), elle permet de donner des droits en fonction du niveau d\'utilisateur. Par exemple si vous donnez le droit <b>Voir le forum</b> au niveau <b>Membre</b>, les membres, modérateurs et administrateurs verront le forum, mais pas les visiteurs. Si vous donnez le droit <b>Créer des annonces</b> au niveau <b>Modérateur</b>, les membres et visiteurs ne pourront pas poster d\'annonce, mais les modérateurs et administrateurs le pourront.</li><br />
	  <li><b>Interface normale:</b> cette interface regroupe les droits en catégories simplifiées: <b>droits de lecture, d\'écriture et de modération</b>. Pour chaque groupe de base (<b>visiteurs, membres, modérateurs, modérateurs globaux et administrateurs</b>) vous pouvez choisir pour chacun de ces catégories de droits ce que ce groupe pourra faire. Si par exemple au niveau des <b>droits d\'écriture</b>, vous donnez le droit <b>Créer des sujets</b> au groupe <b>Membre</b>, tous les membres du forum pourront créer des sujets <b>et</b> répondre à des sujets.</li><br />
	  <li><b>Interface avancée:</b> cette interface permet de modifier très précisément les droits de chaque groupe du forum. Elle est plus complexe car nécessite que vous choisissiez pour chacun des droits si oui ou non le groupe peut l\'avoir. Vous voyez chacun des droits en abscisse et chacun des groupes en ordonnée, avec une liste oui / non pour savoir si le groupe peut avoir ce droit.</li>
	  </ul><br />
	  Pour vous éviter d\'avoir à entrer tout le temps les même droits partout, vous pouvez utiliser l\'option <b>Dupliquer les droits d\'un forum</b> pour donner au forum actuel les droits d\'un autre forum. <b>N\'oubliez pas de valider ensuite le formulaire pour sauver les droits.</b><br /><br />
	  Veuillez noter aussi que les droits sont <b>cumulatifs</b>. Ainsi si nous prenons par exemple un <i>forum A</i>, ainsi qu\'un membre <i>test</i>. Si dans l\'interface des droits du <i>forum A</i> vous ne donnez pas les droits de lecture aux membres (donc aucun membre ne peut lire ce forum), mais que vous donnez le droit de lecture au membre <i>test</i> via l\'interface de gestion des droits du membre, ce membre <i>test</i> pourra lire le forum.
	  <br /><br />Pour finir, un outil est à votre disposition pour vérifier les droits de membres ou de groupes sur l\'ensemble du forum. Il s\'agit de l\'onglet <b>Vérifier</b> qui vous affichera le tout très clairement pour vous aider à ne pas faire d\'erreur de droits.',
    ),
    'auth2' => 
    array (
      'question' => 'Comment gérer les autres droits ?',
      'answer' => 'Outre les droits <b>Forums, Groupes et Membres</b> vous pouvez voir un onglet <b>Autres</b>. Dans cet onglet vous pouvez gérer quelques droits globaux sur le forum, comme par exemple le droit de télécharger des fichiers, ou le droit de créer des sondages. Ces droits sont indépendants des forums.',
    ),
    'use_auths' => 
    array (
      'question' => 'Comment gérer les droits facilement ?',
      'answer' => 'La meilleur façon de gérer les droits le plus simplement possible est de passer par des groupes d\'utilisateurs, il suffit pour cela de distinguer deux types de forums :
		<ul>
		<li><b>Les forums publics:</b> ces forums sont ceux destinés au commun des membres / visiteurs. Il vous suffit de régler leurs droits dans le panneau de gestion des <b>droits des forums</b> en mettant les droits de lecture / écriture aux membres.</li>
		<li><b>Les forums privés:</b> ces forums sont généralement destinés aux modérateurs, ou a un groupe de personnes particulier. Le mieux à faire est de ne donner aucun droit à personne dans l\'interface des <b>droits du forum</b>, puis d\'aller donner les droits de lecture / écriture au groupe pouvant accéder à ce forum dans l\'interface des <b>droits des groupes</b>. Ainsi vous êtes sur de pouvoir régler les droits de ce forum en gérant simplement les droits d\'un seul groupe.
		</ul>',
    ),
    'forums' => 
    array (
      'question' => 'Création de forums',
      'answer' => 'Un forum est un espace de discussion au sein de votre Forum. Chaque forum créé doit être attaché à une
			catégorie, ou bien à un autre forum. Il est possible de créer des sous forums à l\'infini. Voici les différentes options
			disponibles pour la création d\'un forum :
			<ul><li><strong>Options basiques du forum:</strong></li>
			<ul>
			<li><strong>Nom du forum et description du forum:</strong> entrez le nom et la description du forum, vous pouvez y
			mettre du HTML</li>
			<li><strong>Attacher le forum:</strong> Attachez votre forum à une catégorie (il apparaitra sur l\'index) ou bien à un
			autre forum pour en faire un sous-forum</li>
			<li><strong>Verrouiller le forum:</strong> En cochant oui, seuls les administrateurs pourront poster dans le forum<br /><br /></li>
			</ul>
			<li><strong>Options secondaires:</strong></li>
			<ul>
			<li><strong>Type de forum:</strong> Choisissez le type du forum, par défaut un forum normal. Vous pouvez aussi en 
			faire une sous catégorie (un forum faut pour accueillir d\'autre sous-forums, sans pouvoir poster dans le forum même), 
			une URL indirecte (le lien du forum redirige vers un site de votre choix, et incrémente un compteur de clics) ou 
			bien une URL directe (le forum pointe directement vers un site)</li>
			<li><strong>Mot de passe:</strong> Vous pouvez rendre votre forum accessible par un mot de passe. Seul ceux connaissant 
			le mot de passe pourront y accéder</li>
			<li><strong>Thème:</strong> Par défaut le thème du forum sera celui choisi par vos membres, cependant vous pouvez imposer
			un autre thème dans le forum que vous créez</li>
			<li><strong>Délestage:</strong> Le délestage est une suppression automatique des sujets d\'un forum. Une fois par jour
			le forum lance la routine de délestage, et supprime si vous l\'avez défini tous les sujets dépassant une date. Si vous
			souhaitez éviter de voir s\'accumuler des sujets trop anciens (dont la date de dernière réponse dépasse 6 mois par exemple)
			vous pouvez activer cette option (en entrant une date)</li>
			<li><strong>Les MAPS des sujets:</strong> Les MAPS des sujets sont des formulaires permettant aux membres de mettre
			facilement en forme les messages. Pour plus d\'informations consultez la 
			<a href="index.php?p=faq&amp;area=maps&amp;section=admin">FAQ des MAPS</a>. Vous pouvez
			en créant un forum imposer une MAP au forum (non modifiable), et faire en sorte que cette MAP ne soit appliquée qu\'au premier
			message d\'un sujet (les autres messages utiliseront le formulaire classique)</li>
			</ul>
			</ul>
		',
    ),
    'groups' => 
    array (
      'question' => 'Quelle est l\'utilité des groupes ?',
      'answer' => 'Les groupes d\'utilisateurs permettent de donner un ensemble de droits à un groupe de personnes. Ainsi si vous souhaitez nommer des modérateurs sur un ensemble de forum le mieux est de créer un groupe modérant ces forums, et d\'ajouter les utilisateurs que vous souhaitez à ce groupe.',
    ),
    'langs' => 
    array (
      'question' => 'Installer et gérer des langues sur le forum',
      'answer' => 'Pour <b>installer</b> une nouvelle langue sur le forum, vous devez tout d\'abord télécharger le pack de langue qui vous intéresse sur <a href="http://www.fire-soft-board.com">notre forum</a> puis l\'installer via le panneau de gestion des langues. Le répertoire <b>~/lang/</b> de votre forum doit être <b>chmodé en 777</b> pour que le forum ait les droits suffisants pour décompresser la langue automatiquement.<br /><br />
		Vous pouvez aussi modifier la langue de votre forum via le panneau de gestion de langue, en entrant des nouvelles valeurs dans les champs concernés.',
    ),
    'maps' => 
    array (
      'question' => 'Les MAPS, définition, installation',
      'answer' => 'Le système de MAP est un système original vous permettant de créer à l\'infini des formulaires pour vos
			messages. Ainsi vous pourrez par exemple créer un formulaire de présentation avec tous les champs que vous définirez vous
			même. Vous pouvez donc modifier comme vous le voulez le formulaire d\'envoi de message.<br /><br />
			Pour installer de nouvelles MAPS vous devez télécharger le fichier .xml de la MAP, et le mettre dans le répertoire
			<strong>~/main/maps/</strong> de votre forum. Vous trouverez de l\'aide sur la création de MAPS, ainsi que des
			exemples disponibles sur notre <a href="http://www.fire-soft-board.com/maps.php" target="_blank">site web</a>.
		',
    ),
    'tpl' => 
    array (
      'question' => 'Modifier le design d\'un thème',
      'answer' => 'FSB se base sur trois types d\'éléments pour définir le design d\'un thème: les fichiers templates (dans le
			répertoire files/ de votre thème) sont des fichiers HTML avec quelques balises spéciales, ils définissent l\'organisation
			de l\'affichage du forum ; les images (dans le dossier img/ du thème) qui sont à modifier si vous souhaitez modifier 
			le design ; le fichier main.css de votre thème qui contient le principal du style du thème. Cette aide explique les
			fonctions principales de l\'éditeur CSS fourni dans l\'administration de votre thème, afin de modifier facilement ce fichier
			et retoucher votre thème sans forcément maîtriser le langage CSS (il est tout de même conseillé d\'avoir des bases).
			<br /><br />Vous devez d\'abord vous rendre sur l\'édition du style du thème, vous tomberez sur une série de classes.
			Choisissez la classe que vous souhaitez modifier. Vous arriverez ensuite sur un formulaire vous permettant de modifier
			certains aspects (que nous avons jugé utile pour cet éditeur) de la classe :
			<ul>
			<li><strong>Arrière plan:</strong> Modifie le style situé en arrière-plan de la classe (le fond)</li>
			<ul>
			<li><strong>Couleur de fond:</strong> Entrez un nom de couleur ou un code hexadécimal de couleur pour modifier la couleur
			d\'arrière plan</li>
			<li><strong>Image de fond:</strong> Vous pouvez insérer une image en arrière-plan de la classe, elle doit se situer dans le
			répertoire img/ du thème</li>
			<li><strong>Répétition de l\'image:</strong> si vous avez mis une image vous pouvez la répéter suivant un schéma
			<br /><br /></li>
			</ul>
			<li><strong>Premier plan:</strong> Modifie le style du texte de votre classe</li>
			<ul>
			<li><strong>Effet sur le texte:</strong> Vous pouvez rendre votre texte gras / italique / souligné</li>
			<li><strong>Couleur du texte:</strong> Entrez un nom de couleur ou un code hexadécimal de couleur pour modifier la couleur
			du texte</li>
			<li><strong>Taille du texte:</strong> Entrez une valeur, ainsi qu\'une unité, pour modifier la taille du texte
			<br /><br /></li>
			</ul>
			<li><strong>Bordures:</strong> Modifie le style sur les bordures de la classe</li>
			<ul>
			<li><strong>Couleur de la bordure:</strong> Entrez un nom de couleur ou un code hexadécimal de couleur pour modifier la 
			couleur de la bordure</li>
			<li><strong>Style de la bordure:</strong> Vous pouvez choisir un style pour le trait de la bordure</li>
			<li><strong>Largeur de la bordure:</strong> Vous pouvez définir la largeur du trait pour chaque côté de la bordure
			<br /><br /></li>
			</ul>
			<li><strong>Autres styles:</strong> Modification de styles non répertoriés par l\'éditeur</li>
			</ul>
		',
    ),
    'module' => 
    array (
      'question' => 'Comment installer un module sur mon forum ?',
      'answer' => 'Les modules sont des fonctions supplémentaires programmées par l\'équipe du forum ou par des supporters du forum. Pour installer un module qui vous plait il vous suffit de le télécharger sur notre <a href="http://www.fire-soft-board.com">site officiel</a> et de l\'installer via le panneau d\'installation de module. A noter que votre répertoire <b>~/mods/</b> doit être <b>CHMODé en 777</b>.<br />
		Les modules modifient généralement les fichiers de votre forum, aussi plus votre forum a de modules installés, plus il y a de chances que des incompatibilités surviennent. De plus les modules sont écrits à la base pour le thème de base (<b>WhiteSummer</b>), il n\'est pas garanti qu\'ils soient installables automatiquement sur d\'autres thèmes. En cas d\'incompatibilité nous vous suggérons d\'installer manuellement le module (voir la partie MODS sur notre site pour plus d\'informations).',
    ),
    'smilies_pack' => 
    array (
      'question' => 'Les packs de smilies: créer, installer.',
      'answer' => 'FSB2 vous permet de créer et d\'installer très facilement des packs de smilies personnalisés.
			<br />Pour créer un pack de smilies, réunissez vos smilies dans un dossier de votre choix.<br />
			Une fois cela fait et pour rendre votre pack de smilies installable par FSB2, il vous faut créer le fichier smiley.txt
			dont voici la structure:<br />
			image+extension,description,code<br /><br />
			Exemple:<br />
			smiley01.gif,mon_smiley,;-)<br />
			smiley02.gif,mon second smiley,---<br />
			etc...<br /><br />
			Une fois votre fichier smiley.txt créé, vous devez compresser l\'ensemble de vos fichiers images + smiley.txt dans une archive au format ZIP.<br /><br />
			<strong>/!\\</strong>: il est très important pour que votre pack de smilies soit installé correctement par FSB2, de respecter les deux règles suivantes:<br />
			<ul>
			<li><strong>Ne compressez pas le dossier dans lequel sont situés vos smilies !</strong></li>
			<li><strong>Ne jamais insérer de virgule dans la description, le nom de votre smiley ou son code.</strong></li>
			</ul><br /><br />
			Pour installer un pack de smilies, repérez l\'endroit où il est stocké sur votre disque dur. Rendez-vous ensuite dans l\'administration puis dans Messages et enfin Smilies.<br />
			Cliquez sur Ajouter un pack de smilies et ensuite sur Parcourir. Cherchez votre pack et cliquez sur Sélectionner. Cliquez ensuite sur Soumettre et un message vous indiquera si votre pack est bien installé.
		',
    ),
	'portail' => array(
	  'question' => 'Comment placer le portail comme page d\'accueil ?',
	  'answer' => 'Pour placer votre portail en page d\'accueil du forum, vous devez créer un fichier nommé <b>.htaccess</b> à la racine de votre forum, et y ajouter le code suivant :[code]DirectoryIndex index.php?p=portail index.html index.php[/code]Lorsqu\'un visiteur tentera d\'accéder à votre site via l\'url http://www.votresiteweb.ext/forum/ il tombera directement sur le portail.',
	),
    'galery' => 
    array (
      'question' => 'Comment créer une galerie d\'avatar ?',
      'answer' => 'Pour créer une galerie d\'avatar, vous devez vous rendre sur la page <b>Gestion</b> -> <b>Membres</b> de votre administration. Vous y trouverez un onglet <b>Galerie d\'avatars</b> en haut. Une fois dans cet onglet, vous pourrez y créer une ou plusieurs galeries, et y placer des images que vos membres pourront choisir dans leur profil. N\'oubliez pas d\'activer la galerie d\'avatar dans la configuration du forum.',
    ),
    'url_rewriting' => 
    array (
      'question' => 'L\'url rewriting',
      'answer' => 'L\'URL rewriting est une fonction vous permettant de réécrire une URL tout en gardant sa "destination" d\'origine. Ce système permet en
			général de faciliter le référencement de vos pages par les moteurs de recherche.<br />
			Pour pouvoir utiliser l\'URL rewriting sur votre forum, vous devez tout d\'abord activer la fonction dans la gestion des modules, puis vous devez copier
			le fichier <strong>~/programms/forum.htaccess</strong> à la racine du forum en le renommant <strong>.htaccess</strong>. Ce fichier est fait pour marcher
			sur la plupart des serveurs mais certains d\'entre eux demandent quelque changement de syntaxe dans le fichier .htaccess, en cas de problème venez poster sur
			notre forum de support.<br /><br />
			Vous devez ensuite éditer votre fichier afin de renseigner le bon chemin du forum,au niveau de la ligne <b>RewriteBase /fsb2/</b>. Si votre forum se trouve à l\'URL www.monsiteweb.com/forum, vous devez entrer <b>RewriteBase /forum/</b>.<br /><br />
			Afin d\'éviter le <b>duplicat content</b> par les robots (le duplicat content est l\'indexation de la même page, via plusieurs URL différentes, ce qui peut être très nocif pour le référencement), copiez le fichier ~/programms/robot.txt à la racine de votre domaine (et non pas à la racine de votre forum). Vous devez ensuite éditer ce fichier, en ajoutant le chemin vers votre forum devant <b>index.php?</b>. Par exemple si votre forum est dans le dossier <b>monforum/</b> mettez <b>monforum/index.php?</b>. Ce fichier empêchera l\'indexation de toutes les URL de type <b>index.php?quelquechose</b> sur votre forum, laissant ainsi référencer librement vos URL réécrites, sans risque de <b>duplicat content</b>.
		',
    ),
  ),
  'info' => 
  array (
    'fsb' => 
    array (
      'question' => 'Qu\'est ce que le forum "FSB" ?',
      'answer' => 'FSB (<b>Fire Soft Board</b>) est le script de forum sur lequel vous naviguez actuellement. Il a été écrit majoritairement en PHP couplé à une base de données(MySQL, PostgreSQL, SQLite), et en XHTML / Javascript pour l\'affichage. Ce forum est libre et gratuit, sous licence GPL2 (vous pouvez utiliser et modifier le forum à votre guise sous votre nom, à condition de rester sous licence GPL2 et de laisser les copyright dans les fichiers.
			<br /><br />FSB a été écrit et développé par l\'équipe FSB, que vous pourrez contacter sur notre <b><a href="http://www.fire-soft-board.com">site officiel</a></b>. De nombreux contributeurs externes ont aussi participé à l\'élaboration et au débogage du forum.',
    ),
    'copyright' => 
    array (
      'question' => 'Les copyrights sont ils obligatoires ?',
      'answer' => 'Les copyright dans les fichiers du forum sont bien entendu obligatoires. Le thème est soumis au même copyright que l\'ensemble du forum, si vous réutilisez la CSS vous devez laisser le copyright dans la feuille de style.<br /><br />
            Le copyright en bas de page, affiché sur le forum, <b>n\'est pas obligatoire</b>. Le laisser est cependant une preuve de respect pour nous, et contribue à référencer FSB et à le faire connaitre sur la toile. Merci donc de laisser ce copyright si possible, en guise de reconnaissance pour les années et les centaines d\'heures de travail que nous avons passé (et que nous continuons de passer) pour l\'élaboration de ce forum. Si vous supprimez le copyright en bas de page vous ne pourrez pas recevoir de support (support qui est gratuit).',
    ),
    'install' => 
    array (
      'question' => 'Je souhaite installer ce forum, comment faire ?',
      'answer' => 'Pour installer FSB, rien de plus simple, il vous suffit de vous rendre sur notre <b><a href="http://www.fire-soft-board.com">site officiel</a></b>, de télécharger le forum et de suivre les indications d\'installation sur notre site. L\'installation est simple et à portée de tout le monde, si néanmoins vous êtes nouveau dans le domaine du webmastering, ou que vous avez du mal à installer le forum, nous serons heureux de vous aider sur notre forum à travers notre support gratuit.',
    ),
  ),
);

return (array (
  'nav_faq' => 'Aide sur le forum',
  'faq_section_forum' => 'FAQ forum',
  'faq_section_fsbcode' => 'FAQ FSBcodes',
  'faq_section_modo' => 'FAQ modération',
  'faq_section_admin' => 'FAQ administration',
  'faq_section_info' => 'Informations sur le forum',
  'faq_not_allowed' => 'Vous n\'êtes pas autorisé à accéder à cette section de la FAQ',
  'faq_no_result' => 'Aucun résultat pour la recherche',
  'faq_area_no_exists' => 'La question recherchée n\'existe pas',
  'faq_title' => 'FAQ (Foire Aux Questions)',
  'faq_keywords' => 'Mots clefs',
));


/* EOF */
