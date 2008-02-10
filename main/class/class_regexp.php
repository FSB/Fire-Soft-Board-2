<?php
/*
** +---------------------------------------------------+
** |
** | Name :		~/main/class/class_regexp.php
** | Begin :	17/10/2007
** | Last :		11/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion des expressions régulières principales du forum
*/
class Regexp extends Fsb_model
{
	/*
	** Retourne une expression régulière à partir d'une variable prédéfinie.
	** Par exemple EMAIL retournera l'expression régulière pour un Email.
	** -----
	** $varname ::		Nom de la variable prédéfinie
	** $limit ::		Définit si on retourne l'expression avec les délimiteurs ^ ... $
	** $options ::		Si des options sont passées, on ajoute les délimiteurs ` à la regexp,
	**					ainsi que les options.
	*/
	public static function pattern($varname, $limit = FALSE, $options = NULL)
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
				$pattern = '(8|10|16|20|24)';
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
				if ($options !== NULL)
				{
					$varname = '`' . $varname . '`' . $options;
				}
				return ($varname);
		}

		if ($limit)
		{
			$pattern = '^' . $pattern . '$';
		}

		if ($options !== NULL)
		{
			$pattern = '`' . $pattern . '`' . $options;
		}

		return ($pattern);
	}
}

/* EOF */