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
 * Gestion de l'encodage / decodage des messages pour le WYSIWYG
 */
class Parser_wysiwyg extends Fsb_model
{
	/**
	 * Tag HTML ouvert
	 */
	const TAG_OPEN = 1;
	
	/**
	 * Tag HTML ferme
	 */
	const TAG_CLOSE = 2;
	
	/**
	 * Texte entre deux tags HTML
	 */
	const TEXT = 3;

	/**
	 * Parse des FSBcode uniquement affichables sur le WYSIWYG
	 *
	 * @param string $str
	 * @return string
	 */
	public static function decode($str)
	{
		$str = htmlspecialchars($str);
		$str = str_replace(array("\r\n", "\n", "[br]"), array("<br />", "<br />", "<br />"), $str);

		$fsbcode = new Parser_fsbcode();
		$fsbcode->only_wysiwyg = true;
		$str = $fsbcode->parse($str);
		$str = Parser::smilies($str, true);

		return ($str);
	}

	/**
	 * Parse une chaine de caractere HTML pour la transformer en FSBcode
	 *
	 * @param string $str
	 * @return string
	 */
	public static function encode($str)
	{
		//echo "<xmp>$str</xmp><hr />";

		// On ferme quelques balises
		$str = preg_replace('#<img(.*?)>#i', '<img\\1></img>', $str);

		// On remplace les &nbsp; par des espaces
		$str = str_replace('&nbsp;', ' ', $str);

		// Represente la pile dans laquelle sera mis le texte a reconstruire
		$stack = array();

		// On recupere l'ensemble des balises HTML de la chaine
		preg_match_all('#<(/)?([a-zA-Z_][a-zA-Z0-9_]*?)( .*?)?>#s', $str, $tokens, PREG_OFFSET_CAPTURE);
		$count_tokens = count($tokens[0]);
		for ($i = 0, $offset = 0, $id = 1; $i < $count_tokens; $i++)
		{
			$state =	($tokens[1][$i][0] == '/') ? self::TAG_CLOSE : self::TAG_OPEN;
			$name =		strtolower($tokens[2][$i][0]);
			$length =	strlen($tokens[0][$i][0]);
			$text =		substr($str, $offset, $tokens[0][$i][1] - $offset);

			// Ajout du texte a la pile de donnees			
			if ($text)
			{
				$stack[] = array(
					'type' =>		self::TEXT,
					'content' =>	$text,
				);
			}

			switch ($state)
			{
				// Parse des tags fermes
				case self::TAG_CLOSE :
					// On parcourt la pile a la recherche de la premiere balise ouverte du meme type qu'on trouve (et non fermee)
					$count_stack = count($stack);
					for ($j = $count_stack - 1, $find_id = 0; $j >= 0; $j--)
					{
						// On ajoute la fermeture du tag a la pile si : on tombe sur le premier tag ouvert ayant le meme nom que le tag qu'on ferme
						// actuellement ou si on tombe sur un tag ouvert avec une ID similaire a celle du tag qu'on ferme
						if ($stack[$j]['type'] == self::TAG_OPEN && $stack[$j]['tag'] == $name && ($find_id == 0 || $find_id == $stack[$j]['id']) && isset($stack[$j]['close']))
						{
							$find_id = $stack[$j]['id'];
							$stack[] = array(
								'type' =>		self::TAG_CLOSE,
								'content' =>	$stack[$j]['close'],
							);

							// On supprime l'indice 'close' pour montrer que ces tags ont ete fermes (on pourra ainsi les afficher)
							unset($stack[$j]['close']);
						}

						// Petite optimisation pour ne pas parcourir le reste de la pile inutilement, puisqu'on a recupere ce dont on avait besoin
						if ($find_id != 0 && (!isset($stack[$j]['id']) || $find_id != $stack[$j]['id']))
						{
							break;
						}
					}
				break;

				// Parse des tags ouverts
				case self::TAG_OPEN :
					// Recuperation des attributs dans un tableau
					$attr = array();
					if (is_array($tokens[3][$i]))
					{
						// Merci IE pour ton code XHTML si propre ...
						if (preg_match('#size=([0-9]+?)#i', $tokens[3][$i][0], $m))
						{
							$attr['size'] = $m[1];
							$tokens[3][$i] = preg_replace('#size=([0-9]+?)#i', '', $tokens[3][$i][0]);
						}

						preg_match_all('#\s([a-zA-Z_]+?)=\\\"([^"]*?)\\\"#', $tokens[3][$i][0], $m);
						$count_attr = count($m[0]);
						for ($j = 0; $j < $count_attr; $j++)
						{
							$attr[strtolower($m[1][$j])] = $m[2][$j];
						}
					}
					

					
					

					// Ici on empile les balises qui seront ajoute a $stack
					$s = array();

					// Transformation des tags en style
					switch ($name)
					{
						// Gras
						case 'b' :
						case 'strong' :
							$s[] = 'b';
						break;

						// Italique
						case 'i' :
						case 'em' :
							$s[] = 'i';
						break;

						// Souligne
						case 'u' :
							$s[] = 'u';
						break;

						// Barre
						case 'strike' :
							$s[] = 'strike';
						break;

						// Code informatique
						case 'code' :
							$s[] = 'code' . ((isset($attr['args'])) ? '=' . $attr['args'] : '');
						break;

						// Citation
						case 'blockquote' :
							$s[] = 'quote';
						break;

						// Liste
						case 'ul' :
							$s[] = 'list';
						break;

						// Liste ordonnee
						case 'ol' :
							if (isset($attr['style']) && preg_match('#list-style-type: disc;#i', $attr['style']))
							{
								$s[] = 'list';
							}
							else
							{
								$s[] = 'list=1';
							}
						break;

						// Puce de liste
						case 'li' :
							$stack[] = array(
								'type' =>		self::TAG_OPEN,
								'content' =>	'[*]',
								'tag' =>		$name,
								'id' =>			$id,
							);
						break;

						// Images
						case 'img' :
							if (!isset($attr['src']))
							{
								break;
							}

							$url = $attr['src'];
							$args = ':';

							// Apparament Firefox aime bien faire des choses qu'on ne lui demande pas de faire, d'ou
							// ce code pour fixer les bons chemins vers les URL locales http://localhost/fsb2/tpl/WhiteSummer/img/logo.gif
							if (isset($attr['realsrc']))
							{
								$url = $attr['realsrc'];
							}

							if (isset($attr['alt']))
							{
								$args .= 'alt=' . $attr['alt'] . ',';
							}

							if (isset($attr['title']))
							{
								$args .= 'title=' . $attr['title'] . ',';
							}

							if (isset($attr['width']))
							{
								$args .= 'width=' . $attr['width'] . ',';
							}
							else if (isset($attr['style']) && preg_match('#width: ([0-9]+)px#i', $attr['style'], $m))
							{
								$args .= 'width=' . $m[1] . ',';
							}

							if (isset($attr['height']))
							{
								$args .= 'height=' . $attr['height'] . ',';
							}
							else if (isset($attr['style']) && preg_match('#height: ([0-9]+)px#i', $attr['style'], $m))
							{
								$args .= 'height=' . $m[1] . ',';
							}

							$args = substr($args, 0, -1);

							$s[] = 'img' . $args;
							$s[] = '__text__=' . $url;
						break;

						// Liens hypertexte
						case 'a' :
							if (!isset($attr['href']))
							{
								break;
							}
							$url = $attr['href'];

							// Apparament Firefox aime bien faire des choses qu'on ne lui demande pas de faire, d'ou
							// ce code pour fixer les bons chemins vers les URL locales http://localhost/fsb2/tpl/WhiteSummer/img/logo.gif
							if (isset($attr['realsrc']))
							{
								$url = $attr['realsrc'];
							}

							// Adresse email ?
							if (preg_match('#^mailto:#i', $url))
							{
								$s[] = 'mail=' . substr($url, 7);
							}
							else
							{
								$s[] = 'url=' . $url;
							}
						break;

						// Sauts de ligne
						case 'br' :
							$stack[] = array(
								'type' =>		self::TAG_OPEN,
								'content' =>	"\n",
								'tag' =>		'',
								'id' =>			$id,
							);
						break;

						// Fichiers joints
						case 'div' :
							if (isset($attr['type']) && $attr['type'] == 'attach')
							{
								$s[] = 'attach' . ((isset($attr['args'])) ? '=' . $attr['args'] : '');
							}
						break;
					}

					// Parse du style
					if (isset($attr['style']))
					{
						$split = explode(';', $attr['style']);
						foreach ($split AS $style)
						{
							$split2 = explode(':', $style);
							if (count($split2) == 2)
							{
								$property = strtolower(trim($split2[0]));
								$value = trim($split2[1]);
								switch ($property)
								{
									case 'font-weight' :
										if ($value == 'bold')
										{
											$s[] = 'b';
										}
									break;

									case 'font-style' :
										if ($value == 'italic')
										{
											$s[] = 'i';
										}
									break;

									case 'text-decoration' :
										switch ($value)
										{
											case 'underline' :
												$s[] = 'u';
											break;

											case 'line-through' :
												$s[] = 'strike';
											break;

											case 'underline line-through' :
											case 'line-through underline' :
												$s[] = 'u';
												$s[] = 'strike';
											break;
										}
									break;

									case 'color' :
									case 'background-color' :
										$color = $value;
										if (preg_match('#^rgb\(([0-9]{1,3}), ?([0-9]{1,3}), ?([0-9]{1,3})\)#i', $value, $m))
										{
											$color = '#' . String::add_zero(dechex($m[1]), 2) . String::add_zero(dechex($m[2]), 2) . String::add_zero(dechex($m[3]), 2);
										}

										$tagname = ($property == 'color') ? 'color' : 'bgcolor';
										$s[] = $tagname . '=' . $color;
									break;

									case 'font-family' :
										$s[] = 'font=' . $value;
									break;

									case 'font-size' :
										if (preg_match('#^([0-9]+)px$#i', $value, $m))
										{
											$s[] = 'size=' . $m[1];
										}
									break;

									case 'text-align' :
										if (in_array(strtolower($value), array('center', 'left', 'right', 'justify')))
										{
											$s[] = 'align=' . $value;
										}
									break;
								}
							}
						}
					}

					// Gestion des couleurs
					if (isset($attr['color']))
					{
						$s[] = 'color=' . $attr['color'];
					}

					// Gestion de la taille de police
					if (isset($attr['size']))
					{
						$size = intval($attr['size']);
						$tmp_size = array('1' => '8', '2' => '10', '3' => '16', '5' => '20', '6' => '24');
						$size = (!isset($tmp_size[$size])) ? $tmp_size[3] : $tmp_size[$size];
						$s[] = 'size=' . $size;
					}

					// Gestion de l'alignement du texte
					if (isset($attr['align']) && preg_match('#^(left|center|right|justify)$#i', $attr['align'], $m))
					{
						$s[] = 'align=' . $m[1];
					}

					// Gestion de la police
					if (isset($attr['face']))
					{
						$s[] = 'font=' . $attr['face'];
					}

					// Ajout de $s a $stack
					foreach ($s AS $v)
					{
						$close = $v;
						if (preg_match('#^([a-z_]+?)(:|=)(.*?)$#i', $close, $m))
						{
							$close = $m[1];
						}

						if ($close == '__text__')
						{
							$stack[] = array(
								'type' =>		self::TEXT,
								'content' =>	$m[3],
							);
						}
						else
						{
							$stack[] = array(
								'type' =>		self::TAG_OPEN,
								'content' =>	'[' . $v . ']',
								'tag' =>		$name,
								'id' =>			$id,
								'close' =>		'[/' . $close . ']',
							);
						}
					}

					$id++;
				break;
			}

			// On deplace l'offset de lecture du texte a la fin de la balise
			$offset = $tokens[0][$i][1] + $length;
		}

		// Ajout de la derniere partie du message
		$stack[] = array(
			'type' =>		self::TEXT,
			'content' =>	substr($str, $offset),
		);

		// Reconstruction du message
		$return = '';
		foreach ($stack AS $line)
		{
			// Les tags ouverts et non fermes ne sont pas mis dans le texte
			if ($line['type'] == self::TAG_OPEN && isset($line['close']))
			{
				continue;
			}

			$return .= $line['content'];
		}

		$return = String::unhtmlspecialchars($return);

		//echo "<xmp>$return</xmp><hr />";
		//exit;

		return ($return);
	}
}

/* EOF */
