<?php
/*
** +---------------------------------------------------+
** | Name :		~/main/class/parser/parser_wysiwyg.php
** | Begin :	16/07/2007
** | Last :		12/12/2007
** | User :		Genova
** | Project :	Fire-Soft-Board 2 - Copyright FSB group
** | License :	GPL v2.0
** +---------------------------------------------------+
*/

/*
** Gestion de l'encodage / décodage des informations pour le WYSIWYG
*/
class Parser_wysiwyg extends Fsb_model
{
	/*
	** Parse des FSBcode uniquement affichables sur le WYSIWYG
	** -----
	** $str ::		Chaine de caractères à parser
	*/
	public static function decode($str)
	{
		$str = htmlspecialchars($str);
		$str = str_replace(array("\r\n", "\n", "[br]"), array("<br />", "<br />", "<br />"), $str);

		$fsbcode = new Parser_fsbcode();
		$fsbcode->only_wysiwyg = TRUE;
		$str = $fsbcode->parse($str);
		$str = Parser::smilies($str, TRUE);

		return ($str);
	}

	/*
	** Parse une chaine de caractère envoyée via l'éditeur WYSIWYG en chaine de caractère valide pour le forum
	** (remplacement des balises HTML par des balises FSBcode)
	** -----
	** $str ::		Chaine de caractères à parser
	*/
	public static function encode($str)
	{
		//echo nl2br(htmlspecialchars($str)) . '<hr />';

		// On remplace les balises <img> par <img></img>
		$str = preg_replace('#<img (.*?)>#i', '<img \\1></img>', $str);

		// On supprime le Javascript / style
		$str = preg_replace('#<script.*?>.*?</script>#si', '', $str);
		$str = preg_replace('#<style.*?>.*?</style>#si', '', $str);

		// On remplace les &nbsp;
		$str = str_replace('&nbsp;', ' ', $str);

		// Pas de \n en mode wysiwyg, tout est géré en HTML
		$str = str_replace(array("\r", "\n"), array('', ''), $str);

		// On récupère la liste des balises et on les parcourt pour les remplacer de façon logique
		// par leurs bonnes balises de fermetures.
		// Par exemple :
		//		<span style="font-style: italic;"><span style="font-weight: bold;">test</span></span>
		// sera remplacé par
		//		[i][b]test[/b][/i]
		// avec un traitement normal à base de preg_replace() on aurait obtenu le code invalide :
		//		[i][b]test[/i][/b]
		preg_match_all('#<(/)?.*?>#si', $str, $tokens);
		$count_tokens = count($tokens[0]);
		$stack_replace = array();
		$closed_tag = array();
		for ($i = 0; $i < $count_tokens; $i++)
		{
			if ($tokens[1][$i] == '/')
			{
				if ($stack_replace[count($stack_replace) - 1] != 'null')
				{
					$str = preg_replace('#' . preg_quote($tokens[0][$i], '#') . '#i', $stack_replace[count($stack_replace) - 1], $str, 1);
					preg_match_all('#\[/[a-zA-Z0-9]*?\]#', $stack_replace[count($stack_replace) - 1], $reverse);
					for ($j = 0; $j < count($reverse[0]); $j++)
					{
						array_pop($closed_tag);
					}
				}
				array_pop($stack_replace);
			}
			else
			{
				// Parse des propriétés CSS dans les atributs de la balise
				$current_replace = $next_replace = '';

				// <br /> ?
				if (preg_match('#<br( |/|>)#i', $tokens[0][$i]))
				{
					$current_replace .= "\n";
				}

				// On parse tout le style contenut dans style=""
				if (preg_match('#<([a-zA-Z]*?) .*?style="(.*?)"#i', $tokens[0][$i], $match))
				{
					// Si la balise qui contenait style="" est une balise de formatage
					switch (strtolower($match[1]))
					{
						case 'b' :
						case 'strong' :
							$current_replace .= '[b]';
							$next_replace .= '[/b]';
						break;

						case 'em' :
						case 'i' :
							$current_replace .= '[i]';
							$next_replace .= '[/i]';
						break;

						case 'u' :
							$current_replace .= '[u]';
							$next_replace .= '[/u]';
						break;

						case 'strike' :
							$current_replace .= '[strike]';
							$next_replace .= '[/strike]';
						break;
					}

					$split = explode(';', trim($match[2]));
					foreach ($split AS $style)
					{
						// Pour la police
						if (preg_match('#font-family: (.*?)$#i', $style, $submatch))
						{
							$current_replace .= '[font=' . $submatch[1] . ']';
							$next_replace .= '[/font]';
						}
						// Pour la taille
						else if (preg_match('#font-size: ([0-9\-]*?)px$#i', $style, $submatch))
						{
							$current_replace .= '[size=' . $submatch[1] . ']';
							$next_replace .= '[/size]';
						}
						// Pour le text-align
						else if (preg_match('#text-align: (center|left|right|justify)#i', $style, $submatch))
						{
							$current_replace .= '[align=' . $submatch[1] . ']';
							$next_replace .= '[/align]';
						}
						// Pour le color / background-color
						else if (preg_match('#(color|background-color): rgb\(([0-9]{1,3}), ?([0-9]{1,3}), ?([0-9]{1,3})\)#i', $style, $submatch))
						{
							$color_str = ($submatch[1] == 'background-color') ? 'bgcolor' : 'color';
							$hexa = '#' . String::add_zero(dechex($submatch[2]), 2) . String::add_zero(dechex($submatch[3]), 2) . String::add_zero(dechex($submatch[4]), 2);
							$current_replace .= '[' . $color_str . '=' . $hexa . ']';
							$next_replace .= '[/' . $color_str . ']';
						}
						else
						{
							// Styles simple à parser
							switch (trim($style))
							{
								case 'font-weight: bold' :
									$current_replace .= '[b]';
									$next_replace .= '[/b]';
								break;

								case 'font-style: italic' :
									$current_replace .= '[i]';
									$next_replace .= '[/i]';
								break;

								case 'text-decoration: underline' :
									$current_replace .= '[u]';
									$next_replace .= '[/u]';
								break;

								case 'text-decoration: line-through' :
									$current_replace .= '[strike]';
									$next_replace .= '[/strike]';
								break;

								case 'text-decoration: underline line-through' :
								case 'text-decoration: line-through underline' :
									$current_replace .= '[u][strike]';
									$next_replace .= '[/u][/strike]';
								break;
							}
						}
					}
				}

				// Parse des balises "classiques"
				switch (strtolower($tokens[0][$i]))
				{
					case '<b>' :
					case '<strong>' :
						$current_replace .= '[b]';
						$next_replace .= '[/b]';
					break;

					case '<em>' :
					case '<i>' :
						$current_replace .= '[i]';
						$next_replace .= '[/i]';
					break;

					case '<u>' :
						$current_replace .= '[u]';
						$next_replace .= '[/u]';
					break;

					case '<strike>' :
						$current_replace .= '[strike]';
						$next_replace .= '[/strike]';
					break;
				}

				// Parse de l'attribut "size"
				if (preg_match('#size="?([0-9\-]*?)"?#i', $tokens[0][$i], $match))
				{
					$tmp_ary_size = array('1' => '8', '2' => '10', '3' => '16', '5' => '20', '6' => '24');
					if (!isset($tmp_ary_size[$match[1]]))
					{
						$match[1] = 3;
					}
					$size = $tmp_ary_size[$match[1]];
					$current_replace .= '[size=' . $size . ']';
					$next_replace .= '[/size]';
				}

				// Parse de l'attribut "color"
				if (preg_match('#color="([^"]+)"#i', $tokens[0][$i], $match))
				{
					$current_replace .= '[color=' . $match[1] . ']';
					$next_replace .= '[/color]';
				}

				// Parse de l'attribut "align"
				if (preg_match('#align="(left|center|right|justify)"#i', $tokens[0][$i], $match))
				{
					$current_replace .= '[align=' . $match[1] . ']';
					$next_replace .= '[/align]';
				}

				// Parse de l'attribut "face"
				if (preg_match('#face="(.*?)"#i', $tokens[0][$i], $match))
				{
					$current_replace .= '[font=' . $match[1] . ']';
					$next_replace .= '[/font]';
				}

				// Parse des listes
				if (preg_match('#<ul ?#i', $tokens[0][$i]) || preg_match('#<ol [^>]*list-style-type: disc;#i', $tokens[0][$i]))
				{
					$current_replace .= '[list]';
					$next_replace .= '[/list]';
				}
				else if (preg_match('#<ol ?#i', $tokens[0][$i]))
				{
					$current_replace .= '[list=1]';
					$next_replace .= '[/list]';
				}
				else if (preg_match('#<li ?#i', $tokens[0][$i]))
				{
					$current_replace .= '[*]';
					$next_replace .= '';
				}

				// Parse des images
				if (preg_match('#<img #i', $tokens[0][$i]) && preg_match('#src="(.*?)"#i', $tokens[0][$i], $match))
				{
					$img = '[img:';
					$url = $match[1];

					// Apparament Firefox aime bien faire des choses qu'on ne lui demande pas de faire, d'où
					// ce code pour fixer les bons chemins vers les URL locales http://localhost/fsb2/tpl/WhiteSummer/img/logo.gif
					if (preg_match('#realsrc="(.*?)"#i', $tokens[0][$i], $fix))
					{
						$url = $fix[1];
					}

					if (preg_match('#alt="(.*?)"#i', $tokens[0][$i], $match))
					{
						$img .= 'alt=' . $match[1] . ',';
					}

					if (preg_match('#title="(.*?)"#i', $tokens[0][$i], $match))
					{
						$img .= 'title=' . $match[1] . ',';
					}

					if (preg_match('#width(: |=")(.*?)(px|")#i', $tokens[0][$i], $match))
					{
						$img .= 'width=' . $match[2] . ',';
					}

					if (preg_match('#height(: |=")(.*?)(px|")#i', $tokens[0][$i], $match))
					{
						$img .= 'height=' . $match[2] . ',';
					}
					$img = substr($img, 0, -1);
					$current_replace .= $img . ']' . $url . '[/img]';
				}

				// Parse des liens hypertextes
				if (preg_match('#<a #i', $tokens[0][$i]) && preg_match('#href="(.*?)"#i', $tokens[0][$i], $match))
				{
					if (preg_match('#^mailto:#i', $match[1]))
					{
						$current_replace .= '[mail=' . substr($match[1], 7) . ']';
						$next_replace .= '[/mail]';
					}
					else
					{
						// Apparament Firefox aime bien faire des choses qu'on ne lui demande pas de faire, d'où
						// ce code pour fixer les bons chemins vers les URL locales http://localhost/fsb2/tpl/WhiteSummer/img/logo.gif
						if (preg_match('#realsrc="(.*?)"#i', $tokens[0][$i], $fix))
						{
							$match[1] = $fix[1];
						}

						$current_replace .= '[url=' . $match[1] . ']';
						$next_replace .= '[/url]';
					}
				}

				// Parse des citations, code
				if (preg_match('#<div #i', $tokens[0][$i]) && preg_match('#type="(quote|code)"#i', $tokens[0][$i], $match))
				{
					$add = '';
					if (preg_match('#args="(.*?)"#i', $tokens[0][$i], $args) && trim($args[1]))
					{
						$add = '=' . trim($args[1]);
					}
					$current_replace .= '[' . $match[1] . $add . ']';
					$next_replace .= '[/' . $match[1] . ']';
				}

				// En cas de paragraphe on ajoute un saut à la ligne
				$add_ln = '';
				if (preg_match('#<p ?#i', $tokens[0][$i]))
				{
					 $add_ln = "\n";
				}

				if ($current_replace || $add_ln)
				{
					// On inverse les balises dans $next_replace, ainsi [/i][/u] deviendra [/u][/i]
					preg_match_all('#\[/[a-zA-Z0-9]*?\]#', $next_replace, $reverse);
					$reverse = array_reverse($reverse[0]);
					foreach ($reverse AS $tag)
					{
						array_push($closed_tag, $tag);
					}
					$next_replace = implode('', $reverse);
					unset($reverse);

					// On remplace la balise courante par $current_replace
					$str = preg_replace('#' . preg_quote($tokens[0][$i], '#') . '#i', $current_replace . $add_ln, $str, 1);
					if ($next_replace != 'none')
					{
						array_push($stack_replace, $next_replace);
					}

					// Toutes les balises HTML avant la balise actuelle doivent être supprimées
					if (($current_replace_pos = @strpos($str, $current_replace)) !== FALSE)
					{
						$previous = substr($str, 0, $current_replace_pos);
						$previous = preg_replace_multiple('#<p ?.*>#si', "\n", $previous);
						$previous = preg_replace_multiple('#<.*?>#si', '', $previous);
						$str = $previous . substr($str, $current_replace_pos);

					}
				}
				else
				{
					array_push($stack_replace, 'null');
				}
			}
		}

		// On ferme les balises FSBcode restantes
		foreach ($closed_tag AS $tag)
		{
			$str .= $tag;
		}

		// On supprime toutes les autres balides HTML
		$str = preg_replace_multiple('#<.*?>#si', '', $str);
		$str = preg_replace(array('#&amp;#', '#&lt;#', '#&gt;#'), array('&', '<', '>'), $str);

		//echo nl2br(htmlspecialchars($str)) . '<hr />';
		//exit;

		return ($str);
	}
}

/* EOF */