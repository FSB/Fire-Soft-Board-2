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
 * Creation et verification des mots de passe
 */
class Password extends Fsb_model
{
	/**
	 * Contient les donnees graduees du mot de passe
	 *
	 * @var array
	 */
	public $grade_data = array();

	/**
	 * Lettres minuscules
	 */
	const LOWCASE = 1;
	
	/**
	 * Lettres majuscules
	 */
	const UPPCASE = 2;
	
	/**
	 * Nombres
	 */
	const NUMERIC = 4;
	
	/**
	 * Caracteres speciaux
	 */
	const SPECIAL = 8;
	
	/**
	 * Majuscules, minuscules, nombres et caracteres speciaux
	 */
	const ALL = 255;

	/**
	 * Hash un mot de passe a partir des parametres passes
	 *
	 * @param string $password Mot de passe
	 * @param string $algorithm Algorithme utilise
	 * @param bool $use_salt Si on concatene un grain au mot de passe
	 * @return string
	 */
	public static function hash($password, $algorithm, $use_salt = true)
	{
		if (!$algorithm)
		{
			$algorithm = 'sha1';
		}

		// Si le champ algorithme contient un nom de fonction et un grain ...
		if (preg_match('#^fct=([a-zA-Z0-9_]+?)(;salt=(.*?))?$#', $algorithm, $m))
		{
			$algorithm = $m[1]; 
			if (!function_exists($algorithm))
			{
				$algorithm = 'sha1';
			}

			if (isset($m[3]))
			{
				$password .= $m[3];
			}
		}
		// Sinon le cas classique
		else if ($use_salt)
		{
			$password .= Fsb::$cfg->get('fsb_hash');
		}

		return ($algorithm($password));
	}
	
	/**
	 * Cree une clef de connexion automatique
	 *
	 * @param string $prefix Chaine qui peut etre utilisee pour la generation de la clef
	 * @return string
	 */
	public function generate_autologin_key($prefix = null)
	{
		if (!$prefix)
		{
			$prefix = self::generate(20);
		}
		
		do
		{
			$key = sha1($prefix . rand(0, time()) . microtime( true ));
			$sql = 'SELECT u_id
					FROM ' . SQL_PREFIX . 'users_password
					WHERE u_autologin_key = \'' . Fsb::$db->escape($key) . '\'';
			$row = Fsb::$db->request($sql);
		}
		while ($row);
		
		return ($key);
	}

	/**
	 * Generation d'un mot de passe aleatoire
	 *
	 * @param int $length Longueur du mot de passe
	 * @param int $type Types de caracteres utilises
	 * @return string
	 */
	public static function generate($length = 8, $type = self::ALL)
	{
		$chars = '';
		$list = array(
			self::LOWCASE =>	'abcdefghijklmnopqrstuvwxyz',
			self::UPPCASE =>	'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
			self::NUMERIC =>	'0123456789',
			self::SPECIAL =>	'&#@{}[]()-+=_!?;:$%*',
		);

		foreach ($list AS $key => $value)
		{
			if ($type & $key)
			{
				$chars .= $value;
			}
		}

		$password = '';
		$max = strlen($chars) - 1;
		for ($i = 0; $i <= $length; $i++)
		{
			$password .= $chars[mt_rand(0, $max)];
		}

		return ($password);
	}

	/**
	 * Test la robustesse d'un mot de passe
	 *
	 * @param string $password_str Mot de passe a verifier
	 * @return int Retourne 1, 2, 3 ou 4 en fonction de cette robustesse
	 */
	public function grade($password_str)
	{
		$this->grade_data = array('len' => 1, 'char_type' => 1, 'average' => 0);

		// Premiere graduation, la longueur du mot de passe
		$len = strlen($password_str);
		$len_step = array(6, 8, 11, 15);
		$this->grade_data['len'] = 0;
		foreach ($len_step AS $k => $v)
		{
			if ($len >= $v)
			{
				$this->grade_data['len'] = $k + 1;
			}
		}

		// Seconde graduation, on verifie le type de caractere du mot de passe, lettres, chiffres, caracteres speciaux
		$char_type = array('alpha_min' => 0, 'alpha_maj' => 0, 'number' => 0, 'other' => 0);
		for ($i = 0; $i < $len; $i++)
		{
			if ($this->is_alpha_min($password_str[$i]))
			{
				$char_type['alpha_min']++;
			}
			else if ($this->is_alpha_maj($password_str[$i]))
			{
				$char_type['alpha_maj']++;
			}
			else if ($this->is_number($password_str[$i]))
			{
				$char_type['number']++;
			}
			else
			{
				$char_type['other']++;
			}
		}

		$number_type = 0;
		foreach ($char_type AS $type)
		{
			if ($type > 0)
			{
				$number_type++;
			}
		}
		$this->grade_data['char_type'] = $number_type;
		
		// Troisieme graduation, on verifie le pourcentage de ce type de caractere dans le mot
		$average = ceil($len / 4 / 1.5);
		foreach ($char_type AS $type)
		{
			if ($type >= $average)
			{
				$this->grade_data['average']++;
			}
		}
		return (round(($this->grade_data['len'] + $this->grade_data['char_type'] + $this->grade_data['average']) / 3));
	}

	/**
	 * Verifie si le caractere est une lettre minuscule
	 *
	 * @param string $char Caractere
	 * @return bool
	 */
	private function is_alpha_min($char)
	{
		if ($char >= 'a' && $char <= 'z')
		{
			return (true);
		}
		return (false);
	}
	
	/**
	 * Verifie si le caractere est une lettre majuscule
	 *
	 * @param string $char Caractere
	 * @return bool
	 */
	private function is_alpha_maj($char)
	{
		if ($char >= 'A' && $char <= 'Z')
		{
			return (true);
		}
		return (false);
	}

	/**
	 * Verifie si le caractere est un nombre
	 *
	 * @param string $char Caractere
	 * @return bool
	 */
	private function is_number($char)
	{
		if ($char >= '0' && $char <= '9')
		{
			return (true);
		}
		return (false);
	}
}

/* EOF */