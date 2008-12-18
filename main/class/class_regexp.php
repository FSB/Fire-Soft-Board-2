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
 * Gestion des expressions regulieres principales du forum
 */
class Regexp extends Fsb_model
{
	/**
	 * Retourne une expression reguliere a partir d'une variable predefinie.
	 * Par exemple EMAIL retournera l'expression reguliere pour un Email.
	 *
	 * @param string $varname Nom de la variable predefinie
	 * @param bool $limit Definit si on retourne l'expression avec les delimiteurs ^ ... $
	 * @param string $options Si des options sont passees, on ajoute les delimiteurs ` a la regexp, ainsi que les options.
	 * @return string Expression reguliere
	 */
	public static function pattern($varname, $limit = false, $options = null)
	{
		if (preg_match('#\{[A-Z]*?\}#', $varname))
		{
			$varname = substr($varname, 1, -1);
		}

		switch ($varname)
		{
			case 'COLOR' :
				$pattern = '(\#[a-f0-9]{3}|\#[a-f0-9]{6}|[a-z\-]*?)';
			break;

			case 'NUMBER' :
				$pattern = '([0-9]*?)';
			break;

			case 'SIZE' :
				$pattern = '([0-9]{1,2})';
			break;

			case 'ALIGN' :
				$pattern = '(left|center|right|justify)';
			break;

			case 'URL' :
				$pattern = '([^ \"\t\n\r<]*?)';
			break;

			case 'WEBSITE' :
				$pattern = '(?<=^|[\s])((((http:\/\/|https:\/\/|ftp:\/\/|ftps:\/\/)([^ \"\t\n\r<]{3,}))))';
			break;

			case 'WEBSITE2' :
				$pattern = '(?<=^|[\s])((((http:\/\/|https:\/\/|ftp:\/\/|ftps:\/\/|www\.)([^ \"\t\n\r<]{3,}))))';
			break;

			case 'EMAIL' :
				$pattern = '(\w[-.\w]*@\w[-._\w]*\.[a-zA-Z]{2,}.*)';
			break;

			case 'TEXT' :
				$pattern = '(.*?)';
			break;

			default :
				if (!is_null($options))
				{
					$varname = '`' . $varname . '`' . $options;
				}
				return ($varname);
		}

		if ($limit)
		{
			$pattern = '^' . $pattern . '$';
		}

		if (!is_null($options))
		{
			$pattern = '`' . $pattern . '`' . $options;
		}

		return ($pattern);
	}
}

/* EOF */