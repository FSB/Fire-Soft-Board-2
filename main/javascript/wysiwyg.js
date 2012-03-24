/*
** +---------------------------------------------------+
** | Name :			~/main/javascript/wysiwyg.js
** | Begin :		13/08/2005
** | Last :			02/03/2008
** | User :			Genova
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

// Variables globales
var rainbow_box = new Array;
var rainbow_i = 0;
var editor_box = [];

/*
** Interface permettant de passer d'un objet de type FSB_editor_text a un objet
** FSB_editor_wysiwyg tres facilement.
** -----
** id ::			ID du WYSIWYG
** type ::			text ou wysiwyg
** use_wysiwyg ::	permet l'utilisation du wysiwyg ou non
*/
var FSB_editor_interface = new Class(
{
	w: null,
	
	initialize: function(id, type, use_wysiwyg)
	{
		if (!(Browser.Engine.trident && Browser.Engine.version == 5) && !Browser.Engine.gecko && !Browser.Engine.presto && !(Browser.Engine.webkit && Browser.Engine.version == 420))
		{
			type = 'text';
		}

		if (!use_wysiwyg)
		{
			type = 'text';
		}

		if (type == 'text')
		{
			new FSB_editor_text(id, this, use_wysiwyg);
		}
		else
		{
			new FSB_editor_wysiwyg(id, this, use_wysiwyg);
		}
	},

	add: function(type)
	{
		this.w.add(type);
	},

	insert: function(str, html)
	{
		this.w._insert(str, html);
	},

	smiley: function(name, src)
	{
		this.w._smiley(name, src);
	},

	send: function()
	{
		this.w._send();
	},

	change_mode: function(tab)
	{
		this.w.change_mode(tab);
	},

	get_type: function()
	{
		return (this.w.current);
	}
});

/*
** Editeur de texte
*/
var FSB_editor = new Class(
{
	/*
	** Constructeur
	** -----
	** id ::		ID du WYSIWYG
	** iface ::		Pointeur vers l'interface
	** show_tabs ::	Affiche les onglets pour passer d'un mode à l'autre
	*/
	initialize: function(id, iface, show_tabs)
	{
		this.iface = iface;
		this.iface.w = this;
		this.id = id;
		this.edit = $(this.id);

		this.load_rainbow();
		if (show_tabs)
		{
			this.load_tabs();
		}
		this._start_editor();
	},

	/*
	** Charge MooRainbow pour les palettes de couleur
	*/
	load_rainbow: function()
	{
		load_rainbow(this.id);
	},

	/*
	** Charge les onglets dans l'éditeur
	*/
	load_tabs: function()
	{
		// Si les onglets sont déjà chargés, on passe cette étape
		if ($(this.id + '_tabs'))
		{
			return ;
		}

		if (!(Browser.Engine.trident && Browser.Engine.version == 5) && !Browser.Engine.gecko && !Browser.Engine.presto && !(Browser.Engine.webkit && Browser.Engine.version == 420))
		{
			return ;
		}

		// Ajout des onglets pour le WYSIWYG
		var tabs = document.createElement('div');
		tabs.style.display = 'none';
		tabs.style.height = '19px';
		tabs.setAttribute('id', this.id + '_tabs');

		var ltab = document.createElement('div');
		ltab.setAttribute('id', this.id + '_tabs_ltab');
		ltab.setAttribute('class', 'editor_tabs');
		ltab.setAttribute('title', FSB_editor_lg['ltab_explain']);
		if (Browser.Engine.trident)
		{
			var add = (this.current == 'text') ? 3 : 0;
			ltab.style.position = 'absolute';
			ltab.style.backgroundImage = 'url(tpl/WhiteSummer/img/wysiwyg_tab.png)';
			ltab.style.width = '100px';
			ltab.style.height = '16px';
			ltab.style.paddingTop = '3px';
			ltab.style.marginLeft = '2px';
			ltab.style.marginTop = (this.current == 'text') ? (add + 2) + 'px' : (add + 1) + 'px';
			ltab.style.fontWeight = 'bold';
			ltab.style.textAlign = 'center';
		}
		else
		{
			ltab.style.top = (this.current == 'text') ? '3px' : '2px';
		}

		ltab.innerHTML = FSB_editor_lg['ltab'];
		ltab.onmouseover = function()
		{
			this.style.cursor = 'pointer';
		}

		ltab.iface = this.iface;
		ltab.onclick = function()
		{
			this.iface.change_mode(this);
		}
		tabs.appendChild(ltab);

		var rtab = document.createElement('div');
		rtab.setAttribute('class', 'editor_tabs');
		rtab.setAttribute('id', this.id + '_tabs_rtab');
		rtab.setAttribute('title', FSB_editor_lg['rtab_explain']);
		if (Browser.Engine.trident)
		{
			rtab.style.position = 'absolute';
			rtab.style.backgroundImage = 'url(tpl/WhiteSummer/img/wysiwyg_tab.png)';
			rtab.style.width = '100px';
			rtab.style.height = '16px';
			rtab.style.paddingTop = '3px';
			rtab.style.marginLeft = '104px';
			rtab.style.marginTop = (this.current == 'wysiwyg') ? (add + 2) + 'px' : (add + 1) + 'px';
			rtab.style.fontWeight = 'bold';
			rtab.style.textAlign = 'center';
		}
		else
		{
			rtab.style.top = (this.current == 'wysiwyg') ? '3px' : '2px';
		}
		rtab.innerHTML = FSB_editor_lg['rtab'];
		rtab.onmouseover = function()
		{
			this.style.cursor = 'pointer';
		}

		rtab.iface = this.iface;
		rtab.onclick = function()
		{
			this.iface.change_mode(this);
		}
		tabs.appendChild(rtab);

		$(this.id).parentNode.appendChild(tabs);
		$(this.id + '_tabs').injectBefore(this.id);
		$(this.id + '_tabs').style.display = 'block';
	},

	/*
	** Insert la mise en forme dans le texte, au niveau de la sélection
	** ------
	** type ::		Type de mise en forme
	*/
	add: function(type)
	{
		this._add(type);
	},

	/*
	** Parse des variables dans la chaîne
	** -----
	** src ::		Chaîne de caractère à parser
	** replace ::	Chaîne de remplacement
	*/
	parse_vars: function(src, replace)
	{
		// On remplace {TEXT} par replace
		reg = new RegExp('\{TEXT\}', 'g');
		src = src.replace(reg, replace);

		// On remplace {TEXTNOTNULL} par replace s'il y a un contenu, sinon par &nbsp;
		reg = new RegExp('\{TEXTNOTNULL\}', 'g');
		if (!replace.length)
		{
			src = src.replace(reg, '&nbsp;');
		}
		else
		{
			src = src.replace(reg, replace);
		}
		return (src);
	},

	/*
	** Modifie la taille du champ texte
	*/
	change_textarea_size: function(size)
	{	
		size_col = size;
		if (size < 0 && parseInt($(this.id).style.height) <= 75)
		{
			size = 0;
		}
		$(this.id).style.height = (parseInt($(this.id).style.height) + 15 * size) + "px";

		$(this.id + '_rows').value = parseInt($(this.id).style.height);
	},

	/*
	** Change le mode d'édition courrant
	** -----
	** tab ::	Pointeur vers l'onglet courrant
	*/
	change_mode: function(tab)
	{
		if (tab.id == tab.parentNode.id + '_ltab')
		{
			var next = tab.parentNode.id + '_rtab';
			var type = 'text';
		}
		else
		{
			var next = tab.parentNode.id + '_ltab';
			var type = 'wysiwyg';
		}

		if (this.current == type)
		{
			return ;
		}

		// Modification graphique des onglets
		if (Browser.Engine.trident)
		{
			var add = (this.current == 'wysiwyg') ? 2 : 0;
			tab.style.marginTop = (add + 2) + 'px';
			$(next).style.marginTop = (add + 1) + 'px';
		}
		else
		{
			tab.style.top = '3px';
			$(next).style.top = '2px';
		}

		this.send_ajax();
	},

	/*
	** Envoie le message en AJAX afin qu'il soit correctement parsé
	*/
	send_ajax: function()
	{
		ajax_waiter_open();
		ajax_wait_response = true;

		this._send();

		obj = {
			mode: 'editor_' + this.current
		}

        var ajax = new Request(
        {
            url: FSB_ROOT + 'ajax.' + FSB_PHPEXT + '?' + Hash.toQueryString(obj),
            onSuccess: function(txt, xml)
            {
                ajax_waiter_close();
                if (this.current == 'wysiwyg')
                {
                    $(this.id + '_wysiwyg').value = txt;
                    new FSB_editor_text(this.id, this.iface);
                }
                else
                {
                    $(this.id).style.display = 'none';
                    $(this.id).value = txt;
                    new FSB_editor_wysiwyg(this.id, this.iface);
                }
            }.bind(this)
        });

		if (this.current == 'wysiwyg')
		{
			var content = $(this.id + '_wysiwyg').value;
		}
		else
		{
			var content = $(this.id).value;
		}
		
        ajax.send({
            mode: 'post',
            data: 'content=' + content
        });
        
		$(this.id).value = '';
	},

	/*
	** Affiche une boite flotante
	** -----
	** box_type ::	nom de la boite
	** flag ::		Si true, on ferme forcément la boite
	*/
	show_box: function(box_type, flag)
	{
		var box_id = this.id + "_box_" + box_type;
		var box_type_id = $(this.id + "_id_" + box_type);

		$(box_id).style.left = box_type_id.getLeft() + "px";
		$(box_id).style.top  = (box_type_id.getTop() + 25) + "px";

		// On ajoute l'ID de la box à la liste
		if (editor_box[box_id] == undefined)
		{
			editor_box.push(box_id);
		}

		if (!flag && !editor_box[box_id])
		{
			$(box_id).style.visibility = 'visible';
			$(box_id).style.display = 'inline';
			editor_box[box_id] = true;
		}
		else
		{
			$(box_id).style.visibility = 'hidden';
			$(box_id).style.display = 'none';
			editor_box[box_id] = false;
		}
	},

	close_box: function()
	{
		editor_box.each(function(key, value)
		{
			editor_box[key] = false;
			$(key).style.display = 'none';
			$(key).style.visibility = 'hidden';
		});
	}
});

/*
** Editeur de texte classique
*/
var FSB_editor_text = new Class(
{
    Extends: FSB_editor,
	current: 'text',

	/*
	** Initialise l'éditeur
	*/
	_start_editor: function()
	{
		// On était en mode WYSIWYG ?
		if ($(this.id + '_hidden'))
		{
			// On renomme correctement les ID
			$(this.id).id = this.id + '_tmp';
			$(this.id + '_wysiwyg').id = this.id;
			this.edit = $(this.id);

			// Style du textarea
			$(this.id).style.width = $(this.id + '_tmp').style.width;
			$(this.id).style.height = $(this.id + '_tmp').style.height;

			// Suppression de l'Iframe
			$(this.id).parentNode.removeChild($(this.id + '_tmp'));

			// Visibilité textarea
			$(this.id).style.display = 'block';
			$(this.id + '_hidden').value = '0';
		}

		this.doc = this.edit;
	},

	/*
	** Ecrit dans l'éditeur WYSIWYG
	** -----
	** type ::		Identifiant de la commande (par exemple b, u, align=center, size=16)
	*/
	_add: function(type)
	{
		var close = type;

		// Gestion des commandes du type align=center
		var reg = new RegExp('^([a-zA-Z0-9_]+)=(.*)$', '');
		if (reg.test(type))
		{
			var m = reg.exec(type);
			var close = m[1];
		}

		switch (type)
		{
			// Liste d'éléments
			case 'list' :
				var elem = '';
				var add_elem = '';
				var iterator = 1;
				var str = this._get_selection();

				if (str == '')
				{
					while (elem = prompt("Element numéro " + iterator + " de la liste :", ''))
					{
						add_elem = add_elem + ((add_elem) ? "\n" : "") + "[*]" + elem;
						iterator++;
					}
				}
				else
				{
					add_elem = str;
				}

				if (add_elem)
				{
					this._insert_text(add_elem, '[list]', '[/list]');
				}
			break;

			// Lien
			case 'url' :
				var url = prompt('Entrez votre URL (adresse) :', 'http://');
				var str = this._get_selection();

				if (url != null)
				{
					// On inhibe le caractere ] de l'url
					var escaped_url = '';
					for (var i = 0; i < url.length; i++)
					{
						if (url.charAt(i) == ']')
						{
							escaped_url += '%5D';
						}
						else
						{
							escaped_url += url.charAt(i);
						}
					}
					url = escaped_url;
					
					if (str == '')
					{
						str = prompt('Donnez un nom au lien :', '');
					}

					if (str != '')
					{
						this._insert_text(str, '[url=' + url + ']', '[/url]');
					}
					else
					{
						this._insert_text(url, '[url]', '[/url]');
					}
				}
			break;

			case 'attach' :
				this.show_box('attach');
			break;

			case 'color' :
			case 'bgcolor' :
			break;

			default :	
				this._insert_text('{TEXT}', '[' + type + ']', '[/' + close + ']');
			break;
		}
	},

	/*
	** Ajoute un smiley
	** -----
	** name ::		Nom du smiley
	** src ::		Adresse du smiley
	*/
	_smiley: function(name, src)
	{
		this._insert_text('', ' ' + name + ' ', '');
	},

	/*
	** Retourne le texte sélectionné
	*/
	_get_selection: function()
	{
		if (Browser.Engine.trident)
		{
			return (this.doc.selection.createRange().text);
		}
		else
		{
			return (this.edit.value.substring(this.edit.selectionStart, this.edit.selectionEnd));
		}
	},

	/*
	** Insert du texte à la position courante
	** -----
	** str ::		Chaîne de caractère à afficher. Les occurences de {TEXT} seront remplacées par la sélection
	** open ::		Paramètre optionel, chaîne de caractère à ajouter au début
	** close ::		Paramètre optionel, chaîne de caractère à ajouter à la fin
	*/
	_insert_text: function(str, open, close)
	{
		this.edit.focus();

		if (!open) open = '';
		if (!close) close = '';

		// Internet explorer
		if (document.selection && this.edit.createTextRange)
		{
			this.edit.focus(this.edit.caretPos);
			this.edit.caretPos = document.selection.createRange().duplicate();
			this.edit.caretPos.text = this.parse_vars(open + str + close, this.edit.caretPos.text);
		}
		// Mozilla
		else if (this.edit.selectionStart || this.edit.selectionStart == '0')
		{
			var x =			this.edit.scrollTop;
			var selStart =	this.edit.selectionStart;
			var selEnd =	this.edit.selectionEnd;
			var selLength = selEnd - selStart;
			var textStart = this.edit.value.substring(0, selStart);
			var textEnd =	this.edit.value.substring(selEnd, this.edit.textLength);

			// Valeur de la chaîne centrale
			if (selLength != 0 && open != '')
			{
				str = this.parse_vars(str, (this.edit.value).substring(selStart, selEnd));
			}
			else
			{
				str = this.parse_vars(str, '');
			}

			// Gestion du scroll dans le textarea
			if (x == 0 && (this.edit.textLength == selStart))
			{
				 x = this.edit.textLength + 200;
			}

			// Modifications dans le texte
			this.edit.value = textStart + open + str + close + textEnd;
			var txt = open + str + close;
			var cur_pos = selStart + txt.length;
			this.edit.scrollTop = x;

			// Gestion du texte sélectionné après insertions
			if (!(selLength != 0 && open != ''))
			{
				this.edit.selectionStart = selStart + open.length;
				this.edit.selectionEnd = this.edit.selectionStart + str.length;
			}
			else
			{
				this.edit.selectionStart = selStart + open.length;
				this.edit.selectionEnd = selEnd + open.length;
			}
		}
		else
		{
			this.edit.value += open + str + close;
		}
	},

	/*
	** Ajoute du texte
	** -----
	** str ::	Chaîne de caractère
	** html ::	Utilisation du HTML ?
	*/
	_insert: function(str, html)
	{
		this._insert_text('', str, '');
	},

	/*
	** Méthode appelée lors de la soumission du formulaire
	*/
	_send: function()
	{
	}
});

/*
** Editeur WYSIWYG
*/
var FSB_editor_wysiwyg = new Class(
{
    Extends: FSB_editor,
	current: 'wysiwyg',

	/*
	** Initialise l'éditeur
	*/
	_start_editor: function()
	{
		// Création de l'iframe
		var f = new Element('iframe', {
			'id': this.id + '_tmp',
			'class': 'wysiwyg_frame',
			'styles': {
				'width':	$(this.id).getStyle('width'),
				'height':	$(this.id).getStyle('height'),
				'display':	'none'
			}
		});
		$(this.id).getParent().adopt(f);

		// Modification des ID : le textarea devient ID_tmp et l'iframe prend l'ID dans this.id
		$(this.id).id = this.id + '_wysiwyg'
		$(this.id + '_tmp').id = this.id;

		// Dissimulation du textarea, et affichage de l'iframe
		$(this.id + '_wysiwyg').style.display = 'none';
		$(this.id).style.display = 'block';

		// Création d'un input hidden pour le wysiwyg
		var input = document.createElement('input');
		input.setAttribute('type', 'hidden');
		input.setAttribute('name', $(this.id + '_wysiwyg').name + '_hidden');
		input.setAttribute('id', this.id + '_hidden');
		input.setAttribute('value', '1');
		$(this.id).parentNode.appendChild(input);

		// Initialisation du designMode
		if (Browser.Engine.trident)
		{
			this.doc = window.frames[this.id].document;
			this.win = window.frames[this.id];
			this.doc.designMode = 'On';
		}
		else if (Browser.Engine.presto)
		{
			this.doc = $(this.id).contentDocument;
			this.win = $(this.id);
			this.doc.designMode = 'On';
		}
		else if (Browser.Engine.gecko || (Browser.Engine.webkit && Browser.Engine.version == 420))
		{
			this.doc = $(this.id).contentDocument;
			this.win = $(this.id).contentWindow;
			
			if (Browser.Engine.webkit && Browser.Engine.version == 420)
			{
				this.doc.designMode = 'On';
			}
		}
		else
		{
			return ;
		}

		if (!this.doc)
		{
			setTimeout('window.frames[\'' + this.id + '\'].document.designMode = \'On\';', 20);
		}
		else
		{
			// On créé le contenu de l'iframe
			this.doc.open();
			this.doc.write('<html><head><style type="text/css">body{margin:1px;font-size: 12px;font-family: Verdana, Arial, Helvetica, sans-serif;};p{margin:0px;}</style></head><body>' + this._format_text($(this.id + '_wysiwyg').value) + '</body></html>');
			this.doc.close();

			if (Browser.Engine.gecko)
			{
				this.doc.designMode = 'On';
			}
		}

		this.win.focus();
	},

	/*
	** Ecrit dans l'éditeur WYSIWYG
	** -----
	** type ::		Identifiant de la commande (par exemple b, u, align=center, size=16)
	*/
	_add: function(type)
	{
		// Gestion des commandes du type align=center
		var reg = new RegExp('^([a-z0-9_]+)=(.*)$', '');
		var args = null;
		if (reg.test(type))
		{
			var m = reg.exec(type);
			type = m[1];
			args = m[2];
		}

		// Execution des commandes
		switch (type)
		{
			// Texte gras
			case 'b' :
				this.doc.execCommand('bold', false, null);
			break;

			// Texte italique
			case 'i' :
				this.doc.execCommand('italic', false, null);
			break;

			// Texte souligné
			case 'u' :
				this.doc.execCommand('underline', false, null);
			break;

			// Texte barré
			case 'strike' :
				this.doc.execCommand('Strikethrough', false, null);
			break;

			// Type de police
			case 'font' :
				this.doc.execCommand('fontname', false, args);
			break;

			case 'list' :
				this.doc.execCommand('insertunorderedlist', false, null);
			break;

			// Annuler l'action
			case 'undo' :
				this.doc.execCommand('undo', false, null);
			break;

			// Rétablir l'action
			case 'redo' :
				this.doc.execCommand('redo', false, null);
			break;

			// Taille du texte
			case 'size' :
				var tmp_ary_size = new Array();
					tmp_ary_size['8'] = '1';
					tmp_ary_size['10'] = '2';
					tmp_ary_size['16'] = '3';
					tmp_ary_size['20'] = '5';
					tmp_ary_size['24'] = '6';
				args = tmp_ary_size[args];
				this.doc.execCommand('fontsize', false, args);
			break;

			// Alignement du texte
			case 'align' :
				switch (args)
				{
					case 'left' :
						this.doc.execCommand('justifyleft', false, null);
					break;

					case 'center' :
						this.doc.execCommand('justifycenter', false, null);
					break;

					case 'right' :
						this.doc.execCommand('justifyright', false, null);
					break;

					case 'justify' :
						this.doc.execCommand('justifyfull', false, null);
					break;
				}
			break;

			// Citation
			case 'quote' :
				this._insert_text('<blockquote style="border: 1px dashed #000000; margin: 3px; padding: 3px">{TEXTNOTNULL}</blockquote><br />', true);
			break;

			// Code informatique
			case 'code' :
				if (!args)
				{
					args = 'none';
				}
				this._insert_text('<code args="' + args + '" style="display: block; border: 1px dashed #000000; margin: 3px; padding: 3px">{TEXTNOTNULL}</code><br />', true);
			break;

			// Lien hypertexte
			case 'url' :
				var url = prompt('Entrez votre URL (adresse) :', 'http://');
				var str = this._get_selection();

				if (url != null)
				{
					if (str == '')
					{
						str = prompt('Donnez un nom au lien :', '');
					}

					if (str != '')
					{
						this._insert_text('<a href="' + url + '" realsrc="' + url + '">' + str + '</a>', true);
					}
					else
					{
						this._insert_text('<a href="' + url + '" realsrc="' + url + '">' + url + '</a>', true);
					}
				}
			break;

			// Adresse email
			case 'mail' :
				var url = prompt('Entrez l\'adresse Email :', '');
				var str = this._get_selection();

				if (url != null)
				{
					if (str == '')
					{
						str = prompt('Entrez le nom du corespondant :', '');
					}

					if (str != '')
					{
						this._insert_text('<a href="mailto:' + url + '">' + str + '</a>', true);
					}
					else
					{
						this._insert_text('<a href="mailto:' + url + '">' + url + '</a>', true);
					}
				}
			break;

			// Image
			case 'img' :
				var url = prompt('Entrez l\'URL (adresse) de l\'image :', 'http://');
				
				if (url != null)
				{
					this._insert_text('<img src="' + url + '" realsrc="' + url + '" />', true);
				}
			break;
			
			// Couleur du texte
			case 'color' :
				if (args)
				{
					this.doc.execCommand('forecolor', false, args);
				}
			break;

			// Couleur d'arrière plan du texte
			case 'bgcolor' :
				if (args)
				{
					if (Browser.Engine.trident)
					{
						this.doc.execCommand('backcolor', false, args);
					}
					else
					{
						this.doc.execCommand('hilitecolor', false, args);
					}
				}
			break;

			// Fichiers joints
			case 'attach' :
				this.show_box('attach');
			break;

			// Par défaut sinon, on insère le texte avec des FSBcode en dur
			default :
				this._insert_text('[' + type + ']{TEXT}[/' + type + ']');
			break;
		}

		this.win.focus();
	},

	/*
	** Ajoute un smiley
	** -----
	** name ::		Nom du smiley
	** src ::		Adresse du smiley
	*/
	_smiley: function(name, src)
	{
		this._insert_text(' <img src="' + src + '" realsrc="' + src + '" /> ', true);
		this.win.focus();
	},

	/*
	** Retourne le texte sélectionné
	*/
	_get_selection: function()
	{
		if (Browser.Engine.trident)
		{
			return (this.doc.selection.createRange().text);
		}
		else
		{
			return (this.doc.getSelection());
		}
	},

	/*
	** Insert du texte à la position courante
	** -----
	** str ::		Chaîne de caractère à afficher. Les occurences de {TEXT} seront remplacées par la sélection
	** html ::		Si on passe true, on autorise le HTML
	*/
	_insert_text: function(str, html)
	{
		str = this.parse_vars(str, this._get_selection());
		if (!html) str = htmlspecialchars(str);
		str = this._format_text(str);
		if (Browser.Engine.trident)
		{
			var sel = this.doc.selection;
			this.win.focus();
			if (sel != null)
			{
				var rang = sel.createRange();
				rang.select();
				rang.pasteHTML(str);
			}

		}
		else if (Browser.Engine.gecko)
		{
			var fragment = this.doc.createDocumentFragment();
			var div = this.doc.createElement('div');
			div.innerHTML = str;
			while (div.firstChild)
			{
				fragment.appendChild(div.firstChild);
			}
			this._insertNodeAtSelection(fragment);
		}
		else if (Browser.Engine.presto)
		{
			this.doc.execCommand('InsertHTML', false, str);
		}
	},

	/*
	** Insère une node DOM à la sélection du texte
	** (code repris du RTE libre Htmlarea)
	*/
	_insertNodeAtSelection: function(toBeInserted)
	{
		if (!Browser.Engine.trident)
		{
			var sel = this.win.getSelection();
			var range = this._createRange(sel);
			sel.removeAllRanges();
			range.deleteContents();
			var node = range.startContainer;
			var pos = range.startOffset;
			switch (node.nodeType)
			{
				case 3 :
					if (toBeInserted.nodeType == 3)
					{
						node.insertData(pos, toBeInserted.data);
						range = this._createRange();
						range.setEnd(node, pos + toBeInserted.length);
						range.setStart(node, pos + toBeInserted.length);
						sel.addRange(range);
					}
					else
					{
						node = node.splitText(pos);
						var selnode = toBeInserted;
						if (toBeInserted.nodeType == 11)
						{
							selnode = selnode.firstChild;
						}
						node.parentNode.insertBefore(toBeInserted, node);
						this._selectNodeContents(selnode);
					}
				break;

				case 1 :
					var selnode = toBeInserted;
					if (toBeInserted.nodeType == 11)
					{
						selnode = selnode.firstChild;
					}
					node.insertBefore(toBeInserted, node.childNodes[pos]);
					this._selectNodeContents(selnode);
				break;
			}
		}
		else
		{
			return (null);
		}
	},

	/*
	** Sélectionne la node
	** (code repris du RTE libre Htmlarea)
	*/
	_selectNodeContents: function(node, pos)
	{
		this.win.focus();
		var range;
		var collapsed = (typeof pos != "undefined");
		if (Browser.Engine.trident)
		{
			range = this.doc.body.createTextRange();
			range.moveToElementText(node);
			(collapsed) && range.collapse(pos);
			range.select();
		}
		else
		{
			var sel = this.win.getSelection();
			range = this.doc.createRange();
			range.selectNodeContents(node);
			(collapsed) && range.collapse(pos);
			sel.removeAllRanges();
			sel.addRange(range);
		}
	},

	/*
	** (code repris du RTE libre Htmlarea)
	*/
	_createRange: function(sel)
	{
		if (Browser.Engine.trident)
		{
			return sel.createRange();
		}
		else
		{
			this.win.focus();
			if (typeof sel != "undefined")
			{
				try
				{
					return sel.getRangeAt(0);
				}
				catch(e)
				{
					return (this.doc.createRange());
				}
			}
			else
			{
				return this.doc.createRange();
			}
		}
	},

	/*
	** Ajoute du texte
	** -----
	** str ::	Chaîne de caractère
	** html ::	Utilisation du HTML ?
	*/
	_insert: function(str, html)
	{
		this._insert_text(str, html);
	},

	/*
	** Remplaces les espaces et tabulations par leur équivalent HTML
	** -----
	** str ::		Chaîne à parser
	*/
	_format_text: function(str)
	{
		str = str.replace(/\r\n/g, '<br />');
		str = str.replace(/\n/g, '<br />');
		str = str.replace(/\t/g, '&nbsp; &nbsp;');
		str = str.replace(/  /g, '&nbsp; ');
		str = str.replace(/  /g, ' &nbsp;');
		return (str);
	},

	/*
	** Méthode appelée lors de la soumission du formulaire
	*/
	_send: function()
	{
		$(this.id + '_wysiwyg').value = this.doc.body.innerHTML;
		$(this.id + '_wysiwyg').value.replace('/&amp;/', '&');
	}
});

/*
** Alterne l'image d'arrière plan des FSBcodes
** -----
** current ::		Objet de l'image
** mode ::			true si on est en hover, sinon false
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
** Charge MooRainbow
*/
function load_rainbow(name)
{
	var tmp = instantiate_rainbow(name, 'color');
	if (tmp)
	{
		rainbow_box[rainbow_i++] = tmp;
	}

	tmp = instantiate_rainbow(name, 'bgcolor');
	if (tmp)
	{
		rainbow_box[rainbow_i++] = tmp;
	}
}

function instantiate_rainbow(id, tag)
{
	if (!$(id + '_id_' + tag))
	{
		return null;
	}

	return (new MooRainbow(id + '_id_' + tag, {
		'startColor': [58, 142, 246],
		'id': 'my' + id + '_id_' + tag,
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
			textEditor['' + this.options.textareaId].add(tag + '=' + color.hex);
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
					attr = "textEditor['" + this.options.textareaId + "'].add('" + this.options.tag + "=" + colors[j][i] + "');";
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

/*
** Fermeture des popups ouvertes (couleur de texte, couleur de fond, etc.)
*/
function close_box()
{
	for (var i = 0; i < rainbow_i; i++)
	{
		rainbow_box[i].hide(rainbow_box[i].layout);
	}
}

document.onclick = close_box;
