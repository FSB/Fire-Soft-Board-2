<?php
/**
 * Fire-Soft-Board version 2
 * 
 * @package FSB2
 * @author Genova <genova@fire-soft-board.com>
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-2.0.php GNU GPL 2
 */

class Profil_fields extends Fsb_model
{
	const TEXT = 1;
	const TEXTAREA = 2;
	const RADIO = 3;
	const SELECT = 4;
	const MULTIPLE = 5;

	public static $type = array(
		self::TEXT =>		array(
			'name' =>		'text',
			'maxlength' =>	array('min' => 0, 'max' => 255),
			'regexp' =>		TRUE,
			'output' =>		TRUE,
		),
		self::TEXTAREA =>	array(
			'name' =>		'textarea',
			'maxlength' =>	array('min' => 0, 'max' => 65535),
			'output' =>		TRUE,
		),
		self::RADIO =>		array(
			'name' =>		'radio',
			'list' =>		TRUE,
			'output' =>		TRUE,
		),
		self::SELECT =>		array(
			'name' =>		'select',
			'list' =>		TRUE,
			'output' =>		TRUE,
		),
		self::MULTIPLE =>	array(
			'name' =>		'multiple',
			'list' =>		TRUE,
			'height' =>		TRUE,
			'output' =>		TRUE,
		),
	);

	/*
	** Formate l'affichage de la valeur
	** -----
	** $str ::		Chaine a formater
	** $output ::	Chaine de formatage
	*/
	public static function parse_value($str, $output)
	{
		if (trim($output) == '')
		{
			$output = '{TEXT}';
		}
		$output = str_replace('{TEXT}', $str, $output);

		return ($output);
	}
}

/* EOF */