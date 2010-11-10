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
 * Gestion du systeme de MAPS (affichage de la creation de messages a partir d'un modele XML)
 */
class Map extends Fsb_model
{
	/**
	 * Mise en cache des donnees XML
	 *
	 * @var array
	 */
	private static $cache_xml = array();

	/**
	 * Construit le formulaire de post de messages a partir de la MAP XML
	 *
	 * @param string $content Contenu du message, pour l'edition
	 * @param string $post_map MAP utilisee
	 * @param string $tmp_map MAP de formatage a utiliser sur les champs (par exemple pour les citations [quote=xxx]%s[/quote])
	 * @param string $onupload_str Contenu en cas d'upload
	 */
	public static function create_form($content, $post_map, $tmp_map, $onupload_str)
	{
		$parser = new Parser();

		// En cas d'edition on charge le XML du message
		$show_form_value = false;
		if ($content)
		{
			$post_xml = new Xml();
			$post_xml->load_content($content);
			$post_cache = array();
			foreach ($post_xml->document->line AS $line)
			{
				$post_cache[$line->getAttribute('name')] = $line->getData();
			}
			unset($post_xml);
			$show_form_value = true;
		}

		// Instance de l'objet $xml
		$xml = new Xml();
		$xml->load_file(MAPS_PATH . $post_map . '.xml', true);

		// Titre de la MAP
		Fsb::$tpl->set_vars(array(
			'MAP_TITLE' =>		$xml->document->head[0]->title[0]->getData(),
		));

		// Evenement onUpload ?
		$onupload_set = '';
		$onupload_append = 'true';
		if ($xml->document->head[0]->childExists('onUpload'))
		{
			$onupload_set = $xml->document->head[0]->onUpload[0]->getAttribute('set');
			$onupload_append = $xml->document->head[0]->onUpload[0]->getAttribute('append');
		}

		// Affichage ligne par ligne de la MAP
		foreach ($xml->document->body[0]->line AS $line)
		{
			$description = $line->lang[0]->getData();
			$description = preg_replace('#\{LG_([A-Z0-9_]*?)\}#e', 'Fsb::$session->lang(strtolower(\'$1\'))', $description);

			$name = $line->getAttribute('name');

			// On recupere les options
			$option = $line->option[0];

			// Champ par defaut
			$default = ($option->childExists('default')) ? $option->default[0]->getData() : null;

			// Valeur par defaut
			if ($tmp_map)
			{
				$value = sprintf($tmp_map, $post_cache['description']);
			}
			else
			{
				$value = ($show_form_value && isset($post_cache[$name])) ? $post_cache[$name] : $default;
			}

			$block = array(
				'LANG' =>			(Fsb::$session->lang('post_map_lang_' . $description)) ? Fsb::$session->lang('post_map_lang_' . $description) : $description,
				'TYPE' =>			$line->type[0]->getData(),
				'NAME' =>			'post_map_' . $name,
				'VALUE' =>			$value,
				'POS_ITERATOR' =>	0,

				'U_BOX_COLOR' =>	sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=color&amp;map_name=post_map_' . $name . '&amp;color_type=color&amp;frame=true'),
				'U_BOX_BGCOLOR' =>	sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=color&amp;map_name=post_map_' . $name . '&amp;color_type=bgcolor&amp;frame=true'),
				'U_BOX_SMILIES' =>	sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=smilies&amp;map_name=post_map_' . $name . '&amp;frame=true'),
				'U_BOX_UPLOAD' =>	sid(ROOT . 'index.' . PHPEXT . '?p=post&amp;mode=upload&amp;map_name=post_map_' . $name . '&amp;frame=true'),
			);

			// onUpload ?
			if (($block['TYPE'] == 'text' || $block['TYPE'] == 'textarea') && $onupload_set == $name && $onupload_str)
			{
				if ($block['TYPE'] == 'text')
				{
					$onupload_str = str_replace(array("\r\n", "\r", "\n"), array('[br]', '[br]', '[br]'), $onupload_str);
					$onupload_str = htmlspecialchars($onupload_str);
				}
				$block['VALUE'] = ($onupload_append == 'true') ? $block['VALUE'] . "\n" . $onupload_str : $onupload_str;
			}

			// Options du champ
			switch ($block['TYPE'])
			{
				case 'text' :
					$block['SIZE'] = ($option->childExists('size')) ? $option->size[0]->getData() : 35;
					$block['MAXLENGTH'] = ($option->childExists('maxlength')) ? $option->maxlength[0]->getData() : 0;
					Fsb::$tpl->set_blocks('map', $block);
				break;

				case 'textarea' :
					$block['ROWS'] = (Http::request('map_textarea_post_map_' . $name . '_rows', 'post')) ? intval(Http::request('map_textarea_post_map_' . $name . '_rows', 'post')) : (($option->childExists('rows')) ? $option->rows[0]->getData() : 10);
					$block['COLS'] = (Http::request('map_textarea_post_map_' . $name . '_cols', 'post')) ? intval(Http::request('map_textarea_post_map_' . $name . '_cols', 'post')) : (($option->childExists('cols')) ? $option->cols[0]->getData() : 60);
					$block['USE_WYSIWYG'] = (Fsb::$session->data['u_activate_wysiwyg'] && Fsb::$mods->is_active('wysiwyg')) ? true : false;
					$block['ONUPLOAD'] = ($onupload_set == $name) ? true : false;
					$block['ONUPLOAD_APPEND'] = ($onupload_append == 'true') ? true : false;

					// On ajoute des fonctions javascript a charger au demarage de la page
					Fsb::$tpl->set_blocks('onload', array(
						'CODE' =>		'textEditor[\'map_textarea_' . $block['NAME']. '\'] = new FSB_editor_interface(\'map_textarea_post_map_' . $name . '\', \'' . (($block['USE_WYSIWYG']) ? 'wysiwyg' : 'text') . '\', ' . intval(Fsb::$mods->is_active('wysiwyg')) . ')',
					));

					Fsb::$tpl->set_blocks('wysiwyg', array(
						'CLASS_NAME' =>		'post_map_' . $name,
					));

					// Ajout du onclick lors de la soumission ?
					if ($block['USE_WYSIWYG'])
					{
						// Avec l'editeur wysiwyg on utilise pas les smileys / fsbcodes / couleurs
						unset($block['USE_FSBCODE'], $block['USE_SMILIES'], $block['USE_COLOR']);

						// Transformation des FSBcode en HTML
						$block['VALUE'] = Parser_wysiwyg::decode($block['VALUE']);

						Fsb::$tpl->set_switch('use_wysiwyg');
					}
					Fsb::$tpl->set_blocks('map', $block);
				break;

				case 'radio' :
					$block['DIRECTION'] = ($option->childExists('direction')) ? $option->direction[0]->getData() : null;
					Fsb::$tpl->set_blocks('map', $block);

					foreach ($option->list[0]->elem AS $elem)
					{
						Fsb::$tpl->set_blocks('map.radio', array(
							'CHECKED' =>	($elem->getData() == $block['VALUE']) ? true : false,
							'ELEM' =>		$elem->getData(),
						));
					}
				break;

				case 'checkbox' :
					$block['DIRECTION'] = ($option->childExists('direction')) ? $option->direction[0]->getData() : null;
					Fsb::$tpl->set_blocks('map', $block);

					// En cas d'edition ou d'erreur
					if ($show_form_value)
					{
						$ary_checkbox = explode(($option->childExists('separator')) ? $option->separator[0]->getData() : ',', (isset($post_cache[$name])) ? $post_cache[$name] : '');
					}
					else if ($option->childExists('default'))
					{
						$ary_checkbox = array();
						foreach ($option->default[0]->elem AS $elem) 
						{
							$ary_checkbox[] = $elem->getData();
						}
					}
					else
					{
						$ary_checkbox = array();
					}

					foreach ($option->list[0]->elem AS $elem)
					{
						$value = $elem->getData();
						Fsb::$tpl->set_blocks('map.checkbox', array(
							'CHECKED' =>	(in_array($value, $ary_checkbox)) ? true : false,
							'ELEM' =>		$value,
						));
					}
				break;

				case 'list' :
					Fsb::$tpl->set_blocks('map', $block);

					foreach ($option->list[0]->elem AS $elem)
					{
						Fsb::$tpl->set_blocks('map.list', array(
							'SELECTED' =>	($elem->getData() == $block['VALUE']) ? true : false,
							'ELEM' =>		$elem->getData(),
						));
					}
				break;

				case 'multilist' :
					$block['SIZE'] = ($option->childExists('size')) ? $option->size[0]->getData() : null;
					Fsb::$tpl->set_blocks('map', $block);

					// En cas d'edition ou d'erreur
					if ($show_form_value)
					{
						$ary_multilist = explode(($option->childExists('separator')) ? $option->separator[0]->getData() : ',', (isset($post_cache[$name])) ? $post_cache[$name] : '');
					}
					else if ($option->childExists('default'))
					{
						$ary_multilist = array();
						foreach ($option->default[0]->elem AS $elem)
						{
							$ary_multilist[] = $elem->getData();
						}
					}
					else
					{
						$ary_multilist = array();
					}

					foreach ($option->list[0]->elem AS $elem)
					{
						$value = $elem->getData();
						Fsb::$tpl->set_blocks('map.multilist', array(
							'SELECTED' =>	(in_array($value, $ary_multilist)) ? true : false,
							'ELEM' =>		$value,
						));
					}
				break;
			}
		}
		unset($xml);
	}

	/**
	 * Construit le XML du message qui sera stocke en base
	 *
	 * @param string $map MAP du message
	 * @param bool $is_wysiwyg Si le contenu est passe par l'editeur wysiwyg (les balises HTML en trop sont supprimees)
	 * @return string XML
	 */
	public static function build_map_content($map, $is_wysiwyg = false)
	{
		$parser = new Parser();

		// Instance de l'objet $xml
		$xml = new Xml();
		$xml->load_file(MAPS_PATH . $map . '.xml', true);

		$message = new Xml();
		$message->document->setTagName('root');
		foreach ($xml->document->body[0]->line AS $line)
		{
			$attr_name = $line->getAttribute('name');
			foreach ($_POST AS $key => $value)
			{
				if (preg_match('#^post_map_(.*?)$#i', $key, $match) && $attr_name == $match[1])
				{
					// On recupere les options et le type
					$option = $line->option[0];
					$type = $line->type[0]->getData();

					// On remplace les entites HTML de $value pour eviter des problemes au parseur XML
					if ($type == 'multilist' || $type == 'checkbox')
					{
						$value = implode(($option->childExists('separator')) ? $option->separator[0]->getData() : ',', $value);
					}

					// En cas d'editeur WYSIWYG on parse le code HTML genere
					if ($type == 'textarea' && ($is_wysiwyg || Http::request($match[0] . '_hidden', 'post')))
					{
						$value = Parser_wysiwyg::encode($value);
					}

					// Filtre applique sur le champ
					$value = $parser->prefilter($value);

					$message_line = $message->document->createElement('line');
					$message_line->setAttribute('name', $match[1]);
					$message_line->setData(trim(htmlspecialchars($value)));
					$message->document->appendChild($message_line);
				}
			}
		}
		unset($xml);

		return ($message->document->asValidXML());
	}

	/**
	 * Retourne la liste des MAPS
	 *
	 * @return array
	 */
	public static function get_list()
	{
		$fd = opendir(MAPS_PATH);
		$list_map = array();
		while ($file = readdir($fd))
		{
			$extension = get_file_data($file, 'extension');
			if ($extension == 'xml')
			{
				$filename = get_file_data($file, 'filename');
				$list_map[$filename] = $filename;
			}
		}
		closedir($fd);

		return ($list_map);
	}

	/**
	 * Charge les informations de sondage d'une MAP
	 *
	 * @param string $map_name Nom de la MAP
	 * @return array
	 */
	public static function load_poll($map_name)
	{
		$xml = new Xml();
		$xml->load_file(MAPS_PATH . $map_name . '.xml', true);

		$poll_data = array();
		if ($xml->document->head[0]->childExists('poll'))
		{
			$poll = &$xml->document->head[0]->poll[0];

			$poll_data['poll_name'] = ($poll->childExists('question')) ? $poll->question[0]->getData() : '';
			$poll_data['poll_max_vote'] = ($poll->childExists('answer') && $poll->answer[0]->attributeExists('total')) ? $poll->answer[0]->getattribute('total') : 1;

			$poll_data['poll_values'] = array();
			if ($xml->document->head[0]->poll[0]->childExists('answer') && $xml->document->head[0]->poll[0]->answer[0]->childExists('item'))
			{
				foreach ($xml->document->head[0]->poll[0]->answer[0]->item AS $item)
				{
					$poll_data['poll_values'][] = $item->getData();
				}
			}
		}

		return ($poll_data);
	}

	/**
	 * Parse un message qui depend d'une MAP XML
	 *
	 * @param string $str Message
	 * @param string $map_name Nom de la MAP
	 * @return string
	 */
	public static function parse_message($str, $map_name)
	{
		// Mise en cache du parse XML de la MAP
		if (!isset(self::$cache_xml[$map_name]))
		{
			$xml = new Xml();
			$xml->load_file(MAPS_PATH . $map_name . '.xml', true);

			self::$cache_xml[$map_name] = array(
				'line' =>		array(),
				'template' =>	($xml->document->head[0]->childExists('template')) ? $xml->document->head[0]->template[0]->getData() : null,
			);

			foreach ($xml->document->body[0]->line AS $line)
			{
				self::$cache_xml[$map_name]['line'][$line->getAttribute('name')]['str'] = ($line->childExists('result')) ? $line->result[0]->getData() : '%s';
				self::$cache_xml[$map_name]['line'][$line->getAttribute('name')]['ifempty'] = ($line->childExists('ifEmpty')) ? $line->ifEmpty[0]->getData() : null;
			}
		}

		// Parse XML du message
		$new_str = '';
		$xml = new Xml();
		$xml->load_content($str);
		foreach ($xml->document->line AS $line)
		{
			$value = $line->getData();
			if ($value || is_null(self::$cache_xml[$map_name]['line'][$line->getAttribute('name')]['ifempty']))
			{
				$sprintf = self::$cache_xml[$map_name]['line'][$line->getAttribute('name')]['str'];
			}
			else
			{
				$sprintf = self::$cache_xml[$map_name]['line'][$line->getAttribute('name')]['ifempty'];
				
			}
			$sprintf = String::parse_lang($sprintf);
			$new_str .= sprintf($sprintf, $value);
		}

		// Utilisation du template sur la chaine generee
		$new_str = sprintf((self::$cache_xml[$map_name]['template']) ? self::$cache_xml[$map_name]['template'] : '%s', $new_str);

		return ($new_str);
	}
}

/* EOF */
