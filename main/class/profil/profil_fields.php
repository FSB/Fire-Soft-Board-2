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
 * Champs de profils dynamiques
 */
class Profil_fields extends Fsb_model
{
	/**
	 * Type champ texte
	 */
	const TEXT = 1;
	
	/**
	 * Type zone textarea
	 */
	const TEXTAREA = 2;
	
	/**
	 * Type boutons radio
	 */
	const RADIO = 3;
	
	/**
	 * Type liste
	 */
	const SELECT = 4;
	
	/**
	 * Type liste a choix multiples
	 */
	const MULTIPLE = 5;

	/**
	 * Informations sur les differents types de champs
	 *
	 * @var array
	 */
	public static $type = array(
		self::TEXT =>		array(
			'name' =>		'text',
			'desc' =>		'text',
			'maxlength' =>	array('min' => 0, 'max' => 255),
			'regexp' =>		true,
			'output' =>		true,
		),
		self::TEXTAREA =>	array(
			'name' =>		'textarea',
			'maxlength' =>	array('min' => 0, 'max' => 65535),
			'output' =>		true,
		),
		self::RADIO =>		array(
			'name' =>		'radio',
			'list' =>		true,
			'output' =>		true,
		),
		self::SELECT =>		array(
			'name' =>		'select',
			'list' =>		true,
			'output' =>		true,
		),
		self::MULTIPLE =>	array(
			'name' =>		'multiple',
			'list' =>		true,
			'height' =>		true,
			'output' =>		true,
		),
	);

	/**
	 * Formate l'affichage de la valeur d'un champ dynamique
	 *
	 * @param string $str Chaine de remplacement
	 * @param string $output Chaine a parser
	 * @return string
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
