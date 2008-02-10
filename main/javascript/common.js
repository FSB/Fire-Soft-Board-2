/*
** +---------------------------------------------------+
** | Name :			~/main/javascript/common.js
** | Begin :		19/12/2005
** | Last :			21/01/2008
** | User :			Genova
** | Project :		Fire-Soft-Board 2 - Copyright FSB group
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

// Navigateur utilisé
var Nav_Agent =		navigator.userAgent.toLowerCase();
var Nav_IE =		((Nav_Agent.indexOf("msie") != -1)  && (Nav_Agent.indexOf("opera") == -1)) ? true : false;
var Nav_IE7 =		(Nav_IE && (Nav_Agent.indexOf("msie 7") != -1)) ? true : false;
var Nav_IE6 =		(Nav_IE && (Nav_Agent.indexOf("msie 6") != -1)) ? true : false;
var Nav_Moz =		(Nav_Agent.indexOf("firefox") != -1) ? true : false;
var Nav_Opera =		(Nav_Agent.indexOf("opera") != -1 && parseInt(navigator.appVersion) >= 9) ? true : false;
var Nav_Safari =	((Nav_Agent.indexOf('safari') != -1) && (Nav_Agent.indexOf('mac') != -1)) ? true : false;
var Nav_Konqueror = (Nav_Agent.indexOf('konqueror') != -1);

/*
** Lit un cookie
** -----
** name ::		Nom du cookie
*/
function ReadCookie(name)
{
	var result = "";
	var my_cookie = " " + document.cookie + ";";
	var tmpname =  name + "=";
	var begin = my_cookie.indexOf(tmpname);
	var end;
	if (begin != -1)
	{
		begin += tmpname.length;
		end = my_cookie.indexOf(";", begin);
		result = unescape(my_cookie.substring(begin, end));
	}
	return (result);
}
 
/*
** Envoie un cookie
** -----
** name ::		Nom du cookie
** value ::		Valeur du cookie
*/
function SetCookie(name, value, on)
{
	var this_date = new Date();
	if (on)
	{
		this_date.setMonth(this_date.getMonth() + 1);
	}
	else
	{
		this_date.setMonth(this_date.getMonth() - 1);
	}

	var cookie_time = this_date.toGMTString();
	var my_cookie = name + "="+escape(value) + ";expires=" + cookie_time + " path=/;";
	document.cookie = my_cookie;
}

/*
** Coche / décoche un ensemble de checkbox
** -----
** form_name ::			Nom du formulaire
** element_name ::		Nom de la checbox
** is_checked ::		Définit si on coche / décoche la checkbox
*/
function check_boxes(form_name, element_name, is_checked)
{
	var chkboxes = document.forms[form_name].elements[element_name];
	var count = chkboxes.length;

	if (count)
	{
		for (var i = 0; i < count; i++)
		{
			chkboxes[i].checked = is_checked;
		}
	}
	else
	{
		chkboxes.checked = is_checked;
	}
	return true;
}

/*
** Fonction servant a cacher / afficher des éléments
*/
var hide_block = Array();
var blocks_height = new Array();
function hide(id)
{
	hide_block[id] ^= true;
	$(id).style.display = (hide_block[id]) ? 'none' : 'block';
}

function block_cookie_check(id_block, id_img, src_img_open, src_img_close, mooeffect)
{
	if (hide_block[id_block] == undefined)
	{
		cookie_value = ReadCookie(id_block);
		if (cookie_value == 'C')
		{
			hide_block[id_block] = false;
			id_img.src = src_img_close;
		}
		else
		{
			hide_block[id_block] = true;
		}
	}

	block_check(id_block, id_img, src_img_open, src_img_close, mooeffect);
	SetCookie(id_block, (hide_block[id_block]) ? "O" : "C", true);
}

/*
** Lit le contenu d'un cookie et affiche ou non le block
*/
function block_cookie_read(block_name, img_name, img_src, mooeffect)
{
	cookie_value = ReadCookie(block_name);
	if (cookie_value == 'C')
	{
		if (mooeffect)
		{
			blocks_height[block_name] = $(block_name).offsetHeight;
			$(block_name).style.height = '0px';
			$(block_name).style.opacity = 0;
		}
		$(block_name).style.display = 'none';
		$(img_name).src = img_src;
	}
}

function block_check(id_block, id_img, src_img_open, src_img_close, mooeffect)
{
	hide_block[id_block] ^= true;
	if (hide_block[id_block])
	{
		if (!Nav_IE6 && mooeffect)
		{
			$(id_block).setStyle('opacity', 0);
			$(id_block).style.display = 'block';
			$(id_block).effects({duration: 500}).custom(
				{
					'height': [0, blocks_height[id_block]],
					'opacity': [0, 1]
				}
			);
		}
		else
		{
			$(id_block).style.display = 'block';
		}
		$(id_img).src = src_img_open;
	}
	else
	{
		if (!Nav_IE6 && mooeffect)
		{
			blocks_height[id_block] = $(id_block).offsetHeight;
			$(id_block).effects({duration: 500}).custom(
				{
					'height': [blocks_height[id_block], 0],
					'opacity': [1, 0]
				}
			);
			setTimeout('$(\'' + id_block + '\').style.display = \'none\'', 500);
		}
		else
		{
			$(id_block).style.display = 'none';
		}
		$(id_img).src = src_img_close;
	}
}

/*
** Equivalent htmlspecialchars() en php
** -----
** str ::			Chaîne à parser
** protect_amp ::	Parse des &amp;
*/
function htmlspecialchars(str, protect_amp)
{
	if (!protect_amp)
	{
		str = str.replace(/&/g, "&amp;");
	}
	str = str.replace(/</g, "&lt;");
	str = str.replace(/>/g, "&gt;");
	str = str.replace(/\"/g, "&quot;");
	return (str);
}

/*
** Le contraire d'un htmlspecialchars() en php
*/
function unhtmlspecialchars(str)
{
	str = str.replace(/&lt;/g, "<");
	str = str.replace(/&gt;/g, ">");
	str = str.replace(/&quot;/g, "\"");
	str = str.replace(/&amp;/g, "&");
	return (str);
}

/*
** Supprime les espaces à gauche et à droite du mot
** -----
** str ::	Chaine à traiter
*/
function trim(str)
{
	while (str.substr(0, 1) == ' ' || str.substr(0, 1) == "\n")
	{
		str = str.substr(1);
	}

	while (str.substr(str.length - 1, 1) == ' ' || str.substr(str.length - 1, 1) == "\n")
	{
		str = str.substr(0, str.length - 1);
	}
	return (str);
}

/*
** Recherche d'un membre en Ajax
*/
function search_user(value, obj, id, id_field)
{
	var ajax = new Ajax();
	ajax.onload = function(data)
	{
		if (!data)
		{
			$(id).style.visibility = 'hidden';
			return ;
		}

		$(id).innerHTML = data;
		$(id).style.visibility = 'visible';
	}

	// Envoie des requètes http
	ajax.set_arg(AJAX_GET, 'mode', 'search_user');
	ajax.set_arg(AJAX_GET, 'nickname', value);
	ajax.set_arg(AJAX_GET, 'jsid', id_field);
	ajax.set_arg(AJAX_GET, 'jsid2', id);
	ajax.send(FSB_ROOT + 'ajax.php', AJAX_MODE_TXT);
}

/*
** Ajout un champ en DOM
*/
function add_field(t, type, name, value)
{
	tag = document.createElement('input');

	attr = document.createAttribute('type');
	attr.nodeValue = type;
	tag.setAttributeNode(attr);

	attr = document.createAttribute('name');
	attr.nodeValue = name;
	tag.setAttributeNode(attr);

	attr = document.createAttribute('value');
	attr.nodeValue = value;
	tag.setAttributeNode(attr);

	t.appendChild(tag);
}

/*
** Permet de vider un champ une seule fois
** -----
** id ::	ID du champ
*/
var clean_fields = [];
function clean_field(id)
{
	if (!clean_fields[id])
	{
		$(id).value = '';
		clean_fields[id] = true;
	}
}

/*
** Sélection du code source (balise CODE), fonction reprise de phpBB3
*/
function selectCode(a)
{
	// Get ID of code block
	var e = a;

	// Not IE
	if (window.getSelection)
	{
		var s = window.getSelection();

		// Safari
		if (s.setBaseAndExtent)
		{
			s.setBaseAndExtent(e, 0, e, e.innerText.length - 1);
		}
		// Firefox and Opera
		else
		{
			var r = document.createRange();
			r.selectNodeContents(e);
			s.removeAllRanges();
			s.addRange(r);
		}
	}
	// Some older browsers
	else if (document.getSelection)
	{
		var s = document.getSelection();
		var r = document.createRange();
		r.selectNodeContents(e);
		s.removeAllRanges();
		s.addRange(r);
	}
	// IE
	else if (document.selection)
	{
		var r = document.body.createTextRange();
		r.moveToElementText(e);
		r.select();
	}
}