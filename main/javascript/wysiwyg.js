/*
** +---------------------------------------------------+
** | Name :			~/main/javascript/wysiwyg.js
** | Begin :		13/08/2005
** | Last :			13/12/2007
** | User :			Genova
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

// Variables globales
var color_palet_pos = new Array;
var color_palet_list = new Array;
var editor = new Array;
var editor_window = new Array;
var use_wysiwyg = new Array;
var open_current_box = '';
var rainbow_box = new Array;
var rainbow_i = 0;

/*
** Initialise l'éditeur WYSIWYG
*/
function init_wysiwyg(id)
{
	if (Nav_IE || Nav_Opera)
	{
		editor[id] = window.frames[id].document;
		editor_window[id] = window.frames[id];
		editor[id].designMode = 'On';
	}
	else if (Nav_Moz)
	{
		editor[id] = $(id).contentDocument;
		editor_window[id] = $(id).contentWindow;
	}
	else
	{
		return ;
	}

	if(!editor[id].body)
	{
		setTimeout('init_wysiwyg(\'' + id + '\')', 20);
	}
	else
	{
		editor[id].body.innerHTML = format_wysiwyg_text($(id + '_wysiwyg').value);
		if (Nav_Moz)
		{
			editor[id].designMode = 'On';
		}
		else if (Nav_IE)
		{
			set_wysiwyg_command(id, 'b', '', '', '');
		}
	}
}

/*
** Alterne l'image d'arrière plan des FSBcodes
** -----
** current ::		Objet de l'image
** mode ::			TRUE si on est en hover, sinon FALSE
** template_path ::	Chemin du thème
** extended ::		En passant true on considère qu'il s'agit d'un FSBcode
**						sans image (les images de background changent donc)
*/
function fsbcode_background(current, mode, template_path, extended)
{
	if (!extended)
	{
		current.style.backgroundImage = "url(" + template_path + (mode ? "/img/fsbcode_bg_hover.gif)" : "/img/fsbcode_bg_default.gif)");
	}
	else
	{
		if (mode)
		{
			current.childNodes[0].style.backgroundImage = 'url(' + template_path + '/img/fsbcode_custom_hover.gif)';
			current.childNodes[1].style.backgroundImage = 'url(' + template_path + '/img/fsbcode_custom_hover_right.gif)';
		}
		else
		{
			current.childNodes[0].style.backgroundImage = 'url(' + template_path + '/img/fsbcode_custom.gif)';
			current.childNodes[1].style.backgroundImage = 'url(' + template_path + '/img/fsbcode_custom_right.gif)';
		}
	}
}

/*
** Lance la procédure d'insertion de FSBcode
** -----
** id ::			ID du textarea
** name ::			Fonction en argument
** open ::			Ouverture
** defaultText ::	Texte central
** close ::			Fermeture
** code ::			Code du FSBcode concerné
** args ::			Arguments potentiels pour l'éditeur WYSIWYG
*/
function insert_text(id, fct, open, defaultText, close, code, args)
{
	// Appels de procédures complexes pour l'insertion de la balise de mise en forme
	if (fct)
	{
		eval("var back = " + fct + "(id, args);");
		if (back && !use_wysiwyg[id])
		{
			launch_insert_text(id, open, defaultText, close);
		}
	}
	// Appel de procédures simples (gras, italique, souligné, etc..)
	else
	{
		if (use_wysiwyg[id])
		{
			set_wysiwyg_command(id, code, args, open, close);
		}
		else
		{
			launch_insert_text(id, open, defaultText, close);
		}
	}
}

/*
** Insertion de texte dans un textarea
** -----
** id ::			ID du textarea
** open ::			Ouverture
** defaultText ::	Texte central
** close ::			Fermeture
*/
function launch_insert_text(id, open, defaultText, close)
{
	var txtarea = $(id);

	txtarea.focus();

	// IE support
	if (document.selection) 
	{
		insert_ie(open, defaultText, close, txtarea);
	}
	// MOZILLA support
	else if (txtarea.selectionStart || txtarea.selectionStart == '0')
	{
		insert_mozilla(open, defaultText, close, txtarea);
	}
	else
	{
		txtarea.value += open + defaultText + close;
	}
}

function insert_ie(open, defaultText, close, txtarea)
{
	if (txtarea.createTextRange)
	{
		txtarea.focus(txtarea.caretPos);
		txtarea.caretPos = document.selection.createRange().duplicate();
		if (txtarea.caretPos.text.length > 0  && open != '')
		{
			defaultText = txtarea.caretPos.text;
		}
		txtarea.caretPos.text = open + defaultText + close;
	}
}

function insert_mozilla(open, defaultText, close, txtarea)
{
	var x = txtarea.scrollTop;
 	var selStart = txtarea.selectionStart;
	var selEnd = txtarea.selectionEnd;
	var selLength = selEnd - selStart;
	var textStart = txtarea.value.substring(0,selStart);
	var textEnd = txtarea.value.substring(selEnd, txtarea.textLength);

	if (selLength != 0 && open != '')
	{
		defaultText = (txtarea.value).substring(selStart, selEnd)
	}

	if (x == 0 && (txtarea.textLength == selStart))
	{
		 x = txtarea.textLength + 200;
	}
	txtarea.value = textStart + open + defaultText + close + textEnd;
	var txt = open + defaultText + close;
 	var cur_pos = selStart + txt.length;
	txtarea.scrollTop = x;

	if (!(selLength != 0 && open != ''))
	{
		txtarea.selectionStart = selStart + open.length;
		txtarea.selectionEnd = txtarea.selectionStart + defaultText.length;
	}
	else
	{
		txtarea.selectionStart = selStart + open.length;
		txtarea.selectionEnd = selEnd + open.length;
	}
}

/*
** Renvoie la position d'un coté d'un élément
** -----
** id ::		ID de l'élément
** pos ::		Nom de la position (Left, Top, etc ...)
*/
function getPos(id, pos)
{
	eval("var Offset = id.offset" + pos + ";");
	var ParentOffset = id.offsetParent;
	var i = get_pos_iterator;

	while (i > 0)
	{
		eval("Offset += ParentOffset.offset" + pos + ";");
		ParentOffset = ParentOffset.offsetParent;
		i--;
	}

	return (Offset);
}

/*
** Modifie la taille du champ texte
*/
function change_textarea_size(id, size)
{	
	size_col = size;
	if (size < 0 && parseInt($(id).style.height) <= 75)
	{
		size = 0;
	}
	$(id).style.height = (parseInt($(id).style.height) + 15 * size) + "px";

	$(id + '_rows').value = parseInt($(id).style.height);
}

/*
** Compte le nombre de caractère du textarea en permanance
** -----
** id ::			ID du textarea
** id_show_box ::	ID de la boite dans laquelle on affiche le nombre de caractères
*/
function count_char(id, id_show_box)
{
	if (!use_wysiwyg[id])
	{
		var len = ($(id)) ? $(id).value.length : 0;
		$(id_show_box).innerHTML = ((max_chars > 0) ? lg_max_chars + ((len) ? len + ' / ' : '0 / ') + max_chars : '') + '&nbsp; &nbsp; &nbsp;' + ((max_line > 0) ? lg_max_line + max_line : '');
	}
}

/*
** Lance une commande pour l'éditeur WYSIWYG
** -----
** id ::		ID de la frame
** tag_open ::	Tag d'ouverture
** tag_close ::	Tag de fermeture
** code ::		Code du FSBcode
** args ::		Arguments
*/
function set_wysiwyg_command(id, code, args, tag_open, tag_close)
{
	switch (code)
	{
		case 'b' :
			editor[id].execCommand('bold', false, null);
		break;

		case 'i' :
			editor[id].execCommand('italic', false, null);
		break;

		case 'u' :
			editor[id].execCommand('underline', false, null);
		break;

		case 'strike' :
			editor[id].execCommand('Strikethrough', false, null);
		break;

		case 'size' :
			var tmp_ary_size = new Array();
				tmp_ary_size['8'] = '1';
				tmp_ary_size['10'] = '2';
				tmp_ary_size['16'] = '3';
				tmp_ary_size['20'] = '5';
				tmp_ary_size['24'] = '6';
			args = tmp_ary_size[args];
			editor[id].execCommand('fontsize', false, args);
		break;

		case 'align' :
			switch (args)
			{
				case 'left' :
					editor[id].execCommand('justifyleft', false, null);
				break;

				case 'center' :
					editor[id].execCommand('justifycenter', false, null);
				break;

				case 'right' :
					editor[id].execCommand('justifyright', false, null);
				break;

				case 'justify' :
					editor[id].execCommand('justifyfull', false, null);
				break;
			}
		break;

		case 'font' :
			editor[id].execCommand('fontname', false, args);
		break;

		case 'undo' :
			editor[id].execCommand('undo', false, null);
		break;

		case 'redo' :
			editor[id].execCommand('redo', false, null);
		break;

		default :
			insert_wysiwyg_text(id, tag_open + select_text(id) + tag_close);
		break;
	}
	editor_window[id].focus();
}

/*
** Remplace le code selectioné courament par un texte passé en paramètre
** Concerne uniquement l'éditeur WYSIWYG
** -----
** id ::		ID du textarea
** text ::		Code remplacement
** use_html ::	Peut utiliser du HTML ?
*/
function insert_wysiwyg_text(id, text, use_html)
{
	if (!use_html)
	{
		text = htmlspecialchars(text);
	}
	text = format_wysiwyg_text(text);
	if (Nav_IE)
	{
		var sel = editor[id].selection;
		editor_window[id].focus();
		if (sel != null)
		{
			var rang = sel.createRange();
			rang.select();
			rang.pasteHTML(text);
		}

	}
	else if (Nav_Moz || Nav_Opera)
	{
		editor[id].execCommand('InsertHTML', false, text);
	}
}

/*
** Formate le texte en mettant correctement les espaces
*/
function format_wysiwyg_text(text)
{
	text = text.replace(/\r\n/g, '<br />');
	text = text.replace(/\n/g, '<br />');
	text = text.replace(/\t/g, '&nbsp; &nbsp;');
	text = text.replace(/  /g, '&nbsp; ');
	text = text.replace(/  /g, ' &nbsp;');
	return (text);
}

/*
** Renvoie le texte courament selectionné
** Concerne uniquement l'éditeur WYSIWYG
*/
function select_text(id)
{
	if (use_wysiwyg[id])
	{
		if (Nav_IE)
		{
			var sel = editor[id].selection;
			if (sel != null)
			{
				var rang = sel.createRange()
			}
			rang.select();
			return (rang.text);
		}
		else
		{
			return (editor_window[id].getSelection());
		}
	}
	else
	{
		var txtarea = $(id);
		if (Nav_IE)
		{
			if (txtarea.createTextRange)
			{
				txtarea.focus(txtarea.caretPos);
				txtarea.caretPos = document.selection.createRange().duplicate();
				if (txtarea.caretPos.text.length > 0)
				{
					defaultText = txtarea.caretPos.text;
				}
			}
		}
		else
		{
			var selStart = txtarea.selectionStart;
			var selEnd = txtarea.selectionEnd;
			var selLength = selEnd - selStart;
			defaultText = (txtarea.value).substring(selStart, selEnd);
		}
		return (defaultText);
	}
}

/*
** Envoie le contenu de l'iframe vers un textarea
*/
function send_wysiwyg(id)
{
	$(id + '_wysiwyg').value = editor[id].body.innerHTML;
	$(id + '_wysiwyg').value.replace('/&amp;/', '&');
}

/*
** ===== Cas particuliers de FSBcode (URL, images, etc ...)
*/

/*
** Fonction appelée lors du click sur le fsbcode QUOTE
*/
function fct_fsbcode_quote(id, args)
{
	if (use_wysiwyg[id])
	{
		var defaultText = select_text(id);
		if (!defaultText.length)
		{
			defaultText = '&nbsp;';
		}
		insert_wysiwyg_text(id, '<div type="quote" style="border: 1px dashed #000000; margin: 3px; padding: 3px">' + defaultText + '</div>', true);
	}
	else
	{
		launch_insert_text(id, '[quote]', '', '[/quote]');
	}
}

/*
** Fonction appelée lors du click sur le fsbcode CODE
*/
function fct_fsbcode_code(id, args)
{
	if (!args)
	{
		args = 'none';
	}

	if (use_wysiwyg[id])
	{
		var defaultText = select_text(id);
		if (!defaultText.length)
		{
			defaultText = '&nbsp;';
		}
		insert_wysiwyg_text(id, '<div type="code" args="' + args + '" style="border: 1px dashed #000000; margin: 3px; padding: 3px">' + defaultText + '</div>', true);
	}
	else
	{
		launch_insert_text(id, '[code=' + args + ']', '', '[/code]');
	}
}

/*
** Fonction appelée lors du click sur une URL
*/
function fct_fsbcode_url(id, args)
{
	if (use_wysiwyg[id])
	{
		// En mode WYSIWYG
		var defaultText = select_text(id);
		var url = prompt('Entrez votre URL (adresse) :', 'http://');

		if (url != null)
		{
			if (defaultText == '')
			{
				defaultText = prompt('Donnez un nom au lien :', '');
			}

			if (defaultText != '')
			{
				insert_wysiwyg_text(id, '<a href="' + url + '" realsrc="' + url + '">' + defaultText + '</a>', true);
			}
			else
			{
				insert_wysiwyg_text(id, '<a href="' + url + '" realsrc="' + url + '">' + url + '</a>', true);
			}
			editor_window[id].focus();
		}
	}
	else
	{
		// En mode normal
		launch_insert_text(id, '[url]', '', '[/url]');
	}

	return (false);
}

/*
** Fonction appelée lors du click sur une image
*/

function fct_fsbcode_img(id, args)
{
	if (use_wysiwyg[id])
	{
		// En mode WYSIWYG
		var url = prompt('Entrez l\'URL (adresse) de l\'image :', 'http://');
		
		if (url != null)
		{
			insert_wysiwyg_text(id, '<img src="' + url + '" realsrc="' + url + '" />', true);
			editor_window[id].focus();
		}
	}
	else
	{
		// En mode normal
		launch_insert_text(id, '[img]', '', '[/img]');
	}

	return (false);
}


/*
** Fonction appelée lors du click sur l'adresse E-mail
*/
function fct_fsbcode_mail(id, args)
{
	if (use_wysiwyg[id])
	{
		// En mode WYSIWYG
		var defaultText = select_text(id);
		var url = prompt('Entrez l\'adresse Email :', '');

		if (url != null)
		{
			if (defaultText == '')
			{
				defaultText = prompt('Entrez le nom du corespondant :', '');
			}

			if (defaultText != '')
			{
				insert_wysiwyg_text(id, '<a href="mailto:' + url + '">' + defaultText + '</a>', true);
			}
			else
			{
				insert_wysiwyg_text(id, '<a href="mailto:' + url + '">' + url + '</a>', true);
			}
			editor_window[id].focus();
		}
	}
	else
	{
		// En mode normal
		launch_insert_text(id, '[mail]', '', '[/mail]');
	}

	return (false);
}

/*
** Fonction appelée lors du click sur la liste
*/
function fct_fsbcode_list(id, args)
{
	if (use_wysiwyg[id])
	{
		// En mode WYSIWYG
		editor[id].execCommand('insertunorderedlist', false, null);
		editor_window[id].focus();
	}
	else
	{
		// En mode normal
		var elem = '';
		var add_elem = '';
		var iterator = 1;
		var defaultText = select_text(id);

		if (defaultText == '')
		{
			while (elem = prompt("Element numéro " + iterator + " de la liste :", ''))
			{
				add_elem = add_elem + ((add_elem) ? "\n" : "") + "[*]" + elem;
				iterator++;
			}
		}
		else
		{
			add_elem = defaultText;
		}

		if (add_elem)
		{
			launch_insert_text(id, '[list]', add_elem, '[/list]');
		}
	}

	return (false);
}

/*
** Fonction appelée lors du click sur la liste numéroté
*/
function fct_fsbcode_list_num(id, args)
{
	if (use_wysiwyg[id])
	{
		// En mode WYSIWYG
		editor[id].execCommand('insertorderedlist', false, null);
		editor_window[id].focus();
	}
	else
	{
		// En mode normal
		var elem = '';
		var add_elem = '';
		var iterator = 1;
		var defaultText = select_text(id);

		if (defaultText == '')
		{
			while (elem = prompt("Element numéro " + iterator + " de la liste :", ''))
			{
				add_elem = add_elem + ((add_elem) ? "\n" : "") + "[*]" + elem;
				iterator++;
			}
		}
		else
		{
			add_elem = defaultText;
		}

		if (add_elem)
		{
			launch_insert_text(id, '[list=1]', add_elem, '[/list]');
		}
	}

	return (false);
}

/*
** ===== Fonctions javascripts touchants aux boites (palette, smilies, etc ...)
*/

/*
** Affiche la palette de couleur
** -----
** id ::		ID pour le textarea
** box_type ::	color ou bgcolor
** flag ::		Si true, on ferme forcément la boite
*/
function show_color_box(id, box_type, flag)
{
	var box_id = id + "_box_" + box_type;
	var box_type_id = $(id + "_id_" + box_type);


	$(box_id).style.left = box_type_id.getLeft() + "px";
	$(box_id).style.top  = (box_type_id.getTop() + 25) + "px";

	// On ajoute l'ID de la box à la liste
	if (color_palet_pos[box_id] == undefined)
	{
		color_palet_list.push(box_id);
	}

	if (!flag && !color_palet_pos[box_id])
	{
		$(box_id).style.visibility = 'visible';
		$(box_id).style.display = 'inline';
		color_palet_pos[box_id] = true;
	}
	else
	{
		$(box_id).style.visibility = 'hidden';
		$(box_id).style.display = 'none';
		color_palet_pos[box_id] = false;
	}
}

/*
** Ferme toutes les box ouvertes (palettes de couleur, smilies, etc ...)
** -----
** exept ::		ID qu'on ne souhaite pas fermer
*/
function close_fsbcode_box(exept)
{
	for (var i = 0; i < color_palet_list.length; i++)
	{
		if (color_palet_list[i] != exept && color_palet_list[i] != open_current_box)
		{
			$(color_palet_list[i]).style.visibility = 'hidden';
			$(color_palet_list[i]).style.display = 'none';
			color_palet_pos[color_palet_list[i]] = false;
		}
	}
	open_current_box = '';
}

/*
** Fonction appelée lors du click sur le choix de couleur
*/
function fct_fsbcode_color(id, args)
{
	return (false);
}

/*
** Fonction appelée lors du click sur le choix de couleur d'arrière plan
*/
function fct_fsbcode_bgcolor(id, args)
{
	return (false);
}

/*
** Fonction appelée lors du click sur le choix d'un smiley
*/
function fct_smiley(id, args)
{
	close_fsbcode_box(id + '_box_smiley');
	open_current_box = id + '_box_smiley';
	show_color_box(id, "smiley");

	if (window.frames[id + '_box_smiley_name'])
	{
		window.frames[id + '_box_smiley_name'].$(id + "_more").style.display = 'none';
	}
	return (false);
}

/*
** Fonction appelée lors du click sur la boite des fichiers joints
*/
function fct_upload(id, args)
{
	close_fsbcode_box(id + '_box_attach');
	open_current_box = id + '_box_attach';
	show_color_box(id, "attach");
	return (false);
}

/*
** Ferme la palette de couleur
*/
function fct_close_box_color(id, args)
{
	if (use_wysiwyg[id] && args !== true)
	{
		// Mode WYSIWYG
		editor[id].execCommand('forecolor', false, args);
		editor_window[id].focus();
	}
	return (true);
}

/*
** Ferme la palette de couleur
*/
function fct_close_box_bgcolor(id, args)
{
	if (use_wysiwyg[id] && args !== true)
	{
		// Mode WYSIWYG
		if (Nav_IE)
		{
			editor[id].execCommand('backcolor', false, args);
		}
		else if (Nav_Moz || Nav_Opera)
		{
			editor[id].execCommand('hilitecolor', false, args);
		}
		editor_window[id].focus();
	}
	return (true);
}

/*
** Ferme la boite de smiley
*/
function fct_close_box_smiley(id, args)
{
	if (use_wysiwyg[id] && args !== true)
	{
		insert_wysiwyg_text(id, '<img src="' + args + '" realsrc="' + args + '" />', true);
		editor_window[id].focus();
	}
	close_fsbcode_box('');
	return (true);
}

/*
** Charge MooRainbow
*/
function load_rainbow(name)
{
	window.addEvent('load', function() {
		rainbow_box[rainbow_i++] = instantiate_rainbow('textarea_' + name, 'color');
		rainbow_box[rainbow_i++] = instantiate_rainbow('textarea_' + name, 'bgcolor');
	});
}

function instantiate_rainbow(id, tag)
{
	return (new MooRainbow('map_' + id + '_id_' + tag, {
			'startColor': [58, 142, 246],
			'id': id + '_id_' + tag,
			'textareaId': id,
			'tag': tag,
			'onStart': function(color)
			{
				for (var i = 0; i < rainbow_i; i++)
				{
					if (rainbow_box[i] != this)
					{
						rainbow_box[i].hide(rainbow_box[i].layout);
					}
				}
			},
			'onComplete': function(color)
			{
				insert_text('map_' + id, 'fct_close_box_' + tag, '[' + tag + '=' + color.hex + ']', '', '[/' + tag + ']', tag, color.hex);
			},
			'onLayout': function()
			{
				var colors = [
					['#000000', '#ff0000', '#ffff00', '#00ff00', '#00ffff', '#0000ff', '#ff00ff'],
					['#202020', '#800000', '#808000', '#008000', '#008080', '#000080', '#800080'],
					['#404040', '#c00000', '#c0c000', '#00c000', '#00c0c0', '#0000c0', '#c000c0'],
					['#808080', '#ff4040', '#ffff40', '#40ff40', '#40ffff', '#4040ff', '#ff40ff'],
					['#c0c0c0', '#ff8080', '#ffff80', '#80ff80', '#80ffff', '#8080ff', '#ff80ff'],
					['#ffffff', '#ffc0c0', '#ffffc0', '#c0ffc0', '#c0ffff', '#c0c0ff', '#ffc0ff']
				];

				var html = '';
				html += '<table cellspacing="1" cellpadding="0" style="width: 95px; border-spacing: 1px">';
				for (var i = 0; i < 7; i++)
				{
					html += '<tr>';
					for (var j = 0; j < 6; j++)
					{
						attr = "insert_text('map_" + this.options.textareaId + "', 'fct_close_box_" + this.options.tag + "', '[" + this.options.tag + "=" + colors[j][i] + "]', '', '[/" + this.options.tag + "]', '" + this.options.tag + "', '" + colors[j][i] + "');";
						attr += 'for (var i = 0; i < rainbow_i; i++){rainbow_box[i].hide(rainbow_box[i].layout);}';
						html += '<td onclick="' + attr + '" onmouseover="this.style.cursor=\'pointer\';" style="width: 15px; height: 15px; background: ' + colors[j][i] + ';"></td>';
					}
					html += '</tr>';
				}
				html += '</table>';

				this.layout.innerHTML = this.layout.innerHTML + '<div class="moor-defaultColors">' + html + '</div>';
			}
		}));
}

// Fermeture automatique des box en cas de clique
document.onclick = close_fsbcode_box;