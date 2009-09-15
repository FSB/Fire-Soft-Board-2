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
 * Liste des droits
 * 
 * @var array
 */
$GLOBALS['_auth_type'] = array(
	'ga_view',
	'ga_view_topics',
	'ga_read',
	'ga_answer_post',
	'ga_create_post',
	'ga_answer_announce',
	'ga_create_announce',
	'ga_answer_global_announce',
	'ga_create_global_announce',
	'ga_edit',
	'ga_delete',
	'ga_moderator',
);

/**
 * Liste des droits reorganises
 * 
 * @var array
 */
$GLOBALS['_auth_type_format'] = array(
	'read' =>		array('ga_nothing', 'ga_view', 'ga_view_topics', 'ga_read'),
	'write' =>		array('ga_nothing', 'ga_answer_post', 'ga_create_post', 'ga_answer_announce', 'ga_create_announce', 'ga_answer_global_announce', 'ga_create_global_announce'),
	'moderator' =>	array('ga_nothing', 'ga_edit', 'ga_delete', 'ga_moderator'),
);

/**
 * Liste des types de sujets
 * Ne pas supprimer / deplacer global_announce - sous peine de bugs - qui est de toute facon le type le plus important de sujets
 * 
 * @var array
 */
$GLOBALS['_topic_type'] = array(
	'global_announce',
	'announce',
	'post',
);

/**
 * Niveaux des droits associes aux clefs de langues
 * 
 * @var array
 */
$GLOBALS['_auth_level'] = array(
	VISITOR =>	'visitor',
	USER =>		'user',
	MODO =>		'modo',
	MODOSUP =>	'modosup',
	ADMIN =>	'admin',
	FONDATOR =>	'fondator',
);

/**
 * Liste des boites dans la messagerie privee
 * 
 * @var array
 */
$GLOBALS['_list_box'] = array(
	'inbox',
	'outbox',
	'save_inbox',
	'save_outbox',
	'options'
);

/**
 * Patterns pour l'URL rewriting
 * 
 * @var array
 */
$GLOBALS['_rewrite'] = array(
	'#index.' . PHPEXT . '\?p=portail$#i' => 'portail.html',
	'#index.' . PHPEXT . '\?(p=index&(amp;)?)?cat=([0-9]+)$#i' => 'cat-\\3.html',
	'#index.' . PHPEXT . '\?p=forum&(amp;)?f_id=([0-9]+)$#i' => 'forum-\\2-1.html',
	'#index.' . PHPEXT . '\?p=forum&(amp;)?f_id=([0-9]+)&(amp;)?page=([0-9]+)$#i' => 'forum-\\2-\\4.html',
	'#index.' . PHPEXT . '\?p=topic&(amp;)?t_id=([0-9]+)$#i' => 'topic-\\2-1.html',
	'#index.' . PHPEXT . '\?p=topic&(amp;)?t_id=([0-9]+)&(amp;)?page=([0-9]+)$#i' => 'topic-\\2-\\4.html',
	'#index.' . PHPEXT . '\?p=userprofile&(amp;)?id=([0-9]+)$#i' => 'profile-\\2.html',
	'#index.' . PHPEXT . '\?p=search&(amp;)?mode=author&(amp;)?id=([0-9]+)$#i' => 'author-\\3-1.html',
	'#index.' . PHPEXT . '\?p=search&(amp;)?mode=author&(amp;)?id=([0-9]+)&(amp;)?(keywords=([^&]*))?&(amp;)?page=([0-9]+)$#i' => 'author-\\3-\\8.html',
	'#index.' . PHPEXT . '\?p=search&(amp;)?mode=author_topic&(amp;)?id=([0-9]+)$#i' => 'topic-author-\\3-1.html',
	'#index.' . PHPEXT . '\?p=search&(amp;)?mode=author_topic&(amp;)?id=([0-9]+)&(amp;)?(keywords=([^&]*))?&(amp;)?page=([0-9]+)$#i' => 'topic-author-\\3-\\8.html',
	'#index.' . PHPEXT . '\?p=rss&(amp;)?mode=index&(amp;)?cat=([0-9]+)#i' => 'rss-\\3.xml',
	'#index.' . PHPEXT . '\?p=rss&(amp;)?mode=([a-z_]+)&(amp;)?id=([0-9]+)#i' => 'rss-\\2-\\4.xml',
);

/**
 * Liste des fuseaux horraires standards UTC
 * 
 * @var array
 */
$GLOBALS['_utc'] = array(
	'-11' =>	'- 1',
	'-10' =>	'- 10',
	'-9.5' =>	'- 9:30',
	'-9' =>		'- 9',
	'-8' =>		'- 8',
	'-7' =>		'- 7',
	'-6' =>		'- 6',
	'-5' =>		'- 5',
	'-4' =>		'- 4',
	'-3.5' =>	'- 3:30',
	'-2' =>		'- 2',
	'-1' =>		'- 1',
	'0' =>		' - ',
	'1' =>		'+ 1',
	'2' =>		'+ 2',
	'3' =>		'+ 3',
	'3.5' =>	'+ 3:30',
	'4' =>		'+ 4',
	'4.5' =>	'+ 4:30',
	'5' =>		'+ 5',
	'5.5' =>	'+ 5:30',
	'5.75' =>	'+ 5:45',
	'6' =>		'+ 6',
	'6.5' =>	'+ 6.5',
	'7' =>		'+ 7',
	'8' =>		'+ 8',
	'9' =>		'+ 9',
	'9.5' =>	'+ 9:30',
	'10' =>		'+ 10',
	'10.5' =>	'+ 10:30',
	'11' =>		'+ 11',
	'12' =>		'+ 12',
	'12.75' =>	'+ 12:45',
	'13' =>		'+ 13',
	'14' =>		'+ 14',
);

/**
 * Longueur minimale et maximale des mots pour la recherche (fulltext_fsb, like)
 * 
 * @var array
 */
$GLOBALS['_search_min_len'] = 3;
$GLOBALS['_search_max_len'] = 40;

/**
 * Caracteres UTF8 majuscules et leur equivalent minuscule
 * 
 * @var array
 */
$GLOBALS['UTF8_UPPER_TO_LOWER'] = array(
	"\x41" => "\x61", "\x42" => "\x62", "\x43" => "\x63", "\x44" => "\x64",
	"\x45" => "\x65", "\x46" => "\x66", "\x47" => "\x67", "\x48" => "\x68",
	"\x49" => "\x69", "\x4A" => "\x6A", "\x4B" => "\x6B", "\x4C" => "\x6C",
	"\x4D" => "\x6D", "\x4E" => "\x6E", "\x4F" => "\x6F", "\x50" => "\x70",
	"\x51" => "\x71", "\x52" => "\x72", "\x53" => "\x73", "\x54" => "\x74",
	"\x55" => "\x75", "\x56" => "\x76", "\x57" => "\x77", "\x58" => "\x78",
	"\x59" => "\x79", "\x5A" => "\x7A", "\xC3\x80" => "\xC3\xA0", "\xC3\x81" => "\xC3\xA1",
	"\xC3\x82" => "\xC3\xA2", "\xC3\x83" => "\xC3\xA3", "\xC3\x84" => "\xC3\xA4", "\xC3\x85" => "\xC3\xA5",
	"\xC3\x86" => "\xC3\xA6", "\xC3\x87" => "\xC3\xA7", "\xC3\x88" => "\xC3\xA8", "\xC3\x89" => "\xC3\xA9",
	"\xC3\x8A" => "\xC3\xAA", "\xC3\x8B" => "\xC3\xAB", "\xC3\x8C" => "\xC3\xAC", "\xC3\x8D" => "\xC3\xAD",
	"\xC3\x8E" => "\xC3\xAE", "\xC3\x8F" => "\xC3\xAF", "\xC3\x90" => "\xC3\xB0", "\xC3\x91" => "\xC3\xB1",
	"\xC3\x92" => "\xC3\xB2", "\xC3\x93" => "\xC3\xB3", "\xC3\x94" => "\xC3\xB4", "\xC3\x95" => "\xC3\xB5",
	"\xC3\x96" => "\xC3\xB6", "\xC3\x98" => "\xC3\xB8", "\xC3\x99" => "\xC3\xB9", "\xC3\x9A" => "\xC3\xBA",
	"\xC3\x9B" => "\xC3\xBB", "\xC3\x9C" => "\xC3\xBC", "\xC3\x9D" => "\xC3\xBD", "\xC3\x9E" => "\xC3\xBE",
	"\xC4\x80" => "\xC4\x81", "\xC4\x82" => "\xC4\x83", "\xC4\x84" => "\xC4\x85", "\xC4\x86" => "\xC4\x87",
	"\xC4\x88" => "\xC4\x89", "\xC4\x8A" => "\xC4\x8B", "\xC4\x8C" => "\xC4\x8D", "\xC4\x8E" => "\xC4\x8F",
	"\xC4\x90" => "\xC4\x91", "\xC4\x92" => "\xC4\x93", "\xC4\x96" => "\xC4\x97", "\xC4\x98" => "\xC4\x99",
	"\xC4\x9A" => "\xC4\x9B", "\xC4\x9C" => "\xC4\x9D", "\xC4\x9E" => "\xC4\x9F", "\xC4\xA0" => "\xC4\xA1",
	"\xC4\xA2" => "\xC4\xA3", "\xC4\xA4" => "\xC4\xA5", "\xC4\xA6" => "\xC4\xA7", "\xC4\xA8" => "\xC4\xA9",
	"\xC4\xAA" => "\xC4\xAB", "\xC4\xAE" => "\xC4\xAF", "\xC4\xB4" => "\xC4\xB5", "\xC4\xB6" => "\xC4\xB7",
	"\xC4\xB9" => "\xC4\xBA", "\xC4\xBB" => "\xC4\xBC", "\xC4\xBD" => "\xC4\xBE", "\xC5\x81" => "\xC5\x82",
	"\xC5\x83" => "\xC5\x84", "\xC5\x85" => "\xC5\x86", "\xC5\x87" => "\xC5\x88", "\xC5\x8A" => "\xC5\x8B",
	"\xC5\x8C" => "\xC5\x8D", "\xC5\x90" => "\xC5\x91", "\xC5\x94" => "\xC5\x95", "\xC5\x96" => "\xC5\x97",
	"\xC5\x98" => "\xC5\x99", "\xC5\x9A" => "\xC5\x9B", "\xC5\x9C" => "\xC5\x9D", "\xC5\x9E" => "\xC5\x9F",
	"\xC5\xA0" => "\xC5\xA1", "\xC5\xA2" => "\xC5\xA3", "\xC5\xA4" => "\xC5\xA5", "\xC5\xA6" => "\xC5\xA7",
	"\xC5\xA8" => "\xC5\xA9", "\xC5\xAA" => "\xC5\xAB", "\xC5\xAC" => "\xC5\xAD", "\xC5\xAE" => "\xC5\xAF",
	"\xC5\xB0" => "\xC5\xB1", "\xC5\xB2" => "\xC5\xB3", "\xC5\xB4" => "\xC5\xB5", "\xC5\xB6" => "\xC5\xB7",
	"\xC5\xB8" => "\xC3\xBF", "\xC5\xB9" => "\xC5\xBA", "\xC5\xBB" => "\xC5\xBC", "\xC5\xBD" => "\xC5\xBE",
	"\xC6\xA0" => "\xC6\xA1", "\xC6\xAF" => "\xC6\xB0", "\xC8\x98" => "\xC8\x99", "\xC8\x9A" => "\xC8\x9B",
	"\xCE\x86" => "\xCE\xAC", "\xCE\x88" => "\xCE\xAD", "\xCE\x89" => "\xCE\xAE", "\xCE\x8A" => "\xCE\xAF",
	"\xCE\x8C" => "\xCF\x8C", "\xCE\x8E" => "\xCF\x8D", "\xCE\x8F" => "\xCF\x8E", "\xCE\x91" => "\xCE\xB1",
	"\xCE\x92" => "\xCE\xB2", "\xCE\x93" => "\xCE\xB3", "\xCE\x94" => "\xCE\xB4", "\xCE\x95" => "\xCE\xB5",
	"\xCE\x96" => "\xCE\xB6", "\xCE\x97" => "\xCE\xB7", "\xCE\x98" => "\xCE\xB8", "\xCE\x99" => "\xCE\xB9",
	"\xCE\x9A" => "\xCE\xBA", "\xCE\x9B" => "\xCE\xBB", "\xCE\x9C" => "\xCE\xBC", "\xCE\x9D" => "\xCE\xBD",
	"\xCE\x9E" => "\xCE\xBE", "\xCE\x9F" => "\xCE\xBF", "\xCE\xA0" => "\xCF\x80", "\xCE\xA1" => "\xCF\x81",
	"\xCE\xA3" => "\xCF\x83", "\xCE\xA4" => "\xCF\x84", "\xCE\xA5" => "\xCF\x85", "\xCE\xA6" => "\xCF\x86",
	"\xCE\xA7" => "\xCF\x87", "\xCE\xA8" => "\xCF\x88", "\xCE\xA9" => "\xCF\x89", "\xCE\xAA" => "\xCF\x8A",
	"\xCE\xAB" => "\xCF\x8B", "\xD0\x81" => "\xD1\x91", "\xD0\x82" => "\xD1\x92", "\xD0\x83" => "\xD1\x93",
	"\xD0\x84" => "\xD1\x94", "\xD0\x85" => "\xD1\x95", "\xD0\x86" => "\xD1\x96", "\xD0\x87" => "\xD1\x97",
	"\xD0\x88" => "\xD1\x98", "\xD0\x89" => "\xD1\x99", "\xD0\x8A" => "\xD1\x9A", "\xD0\x8B" => "\xD1\x9B",
	"\xD0\x8C" => "\xD1\x9C", "\xD0\x8E" => "\xD1\x9E", "\xD0\x8F" => "\xD1\x9F", "\xD0\x90" => "\xD0\xB0",
	"\xD0\x91" => "\xD0\xB1", "\xD0\x92" => "\xD0\xB2", "\xD0\x93" => "\xD0\xB3", "\xD0\x94" => "\xD0\xB4",
	"\xD0\x95" => "\xD0\xB5", "\xD0\x96" => "\xD0\xB6", "\xD0\x97" => "\xD0\xB7", "\xD0\x98" => "\xD0\xB8",
	"\xD0\x99" => "\xD0\xB9", "\xD0\x9A" => "\xD0\xBA", "\xD0\x9B" => "\xD0\xBB", "\xD0\x9C" => "\xD0\xBC",
	"\xD0\x9D" => "\xD0\xBD", "\xD0\x9E" => "\xD0\xBE", "\xD0\x9F" => "\xD0\xBF", "\xD0\xA0" => "\xD1\x80",
	"\xD0\xA1" => "\xD1\x81", "\xD0\xA2" => "\xD1\x82", "\xD0\xA3" => "\xD1\x83", "\xD0\xA4" => "\xD1\x84",
	"\xD0\xA5" => "\xD1\x85", "\xD0\xA6" => "\xD1\x86", "\xD0\xA7" => "\xD1\x87", "\xD0\xA8" => "\xD1\x88",
	"\xD0\xA9" => "\xD1\x89", "\xD0\xAA" => "\xD1\x8A", "\xD0\xAB" => "\xD1\x8B", "\xD0\xAC" => "\xD1\x8C",
	"\xD0\xAD" => "\xD1\x8D", "\xD0\xAE" => "\xD1\x8E", "\xD0\xAF" => "\xD1\x8F", "\xD2\x90" => "\xD2\x91",
	"\xE1\xB8\x82" => "\xE1\xB8\x83", "\xE1\xB8\x8A" => "\xE1\xB8\x8B", "\xE1\xB8\x9E" => "\xE1\xB8\x9F", "\xE1\xB9\x80" => "\xE1\xB9\x81",
	"\xE1\xB9\x96" => "\xE1\xB9\x97", "\xE1\xB9\xA0" => "\xE1\xB9\xA1", "\xE1\xB9\xAA" => "\xE1\xB9\xAB", "\xE1\xBA\x80" => "\xE1\xBA\x81",
	"\xE1\xBA\x82" => "\xE1\xBA\x83", "\xE1\xBA\x84" => "\xE1\xBA\x85", "\xE1\xBB\xB2" => "\xE1\xBB\xB3"
);
$GLOBALS['UTF8_LOWER_TO_UPPER'] = array_flip($GLOBALS['UTF8_UPPER_TO_LOWER']);

/* EOF */