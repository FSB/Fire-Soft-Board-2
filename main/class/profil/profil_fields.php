<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/profil/profil_fields.php
** | Begin :	08/10/2007
** | Last :		05/11/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
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