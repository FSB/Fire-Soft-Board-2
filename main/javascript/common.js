/**
* +---------------------------------------------------+
* | Name :			~/main/javascript/common.js
* | Begin :			19/12/2005
* | Last :			19/06/2011
* | User :			Genova
* | Project :		Fire-Soft-Board 2 - Copyright FSB group
* | License :		GPL v2.0
* +---------------------------------------------------+
*/

/**
 * Coche / décoche un ensemble de checkbox
 * @param string form_name Nom du formulaire
 * @param string element_name Nom du checkbox
 * @param bool is_checked Définit si on coche / décoche le checkbox
 */
function check_boxes(form_name, element_name, is_checked)
{
	var chkboxes = document.forms[form_name].elements[element_name];
	return _check_boxes(chkboxes, is_checked);
}

/**
 * Coche / décoche un ensemble de checkbox dans un block container
 * @param string id_block Id du block container des checkboxs
 * @param string class_name Nom de la class du checkbox
 * @param bool is_checked Définit si on coche / décoche le checkbox
 */
function check_boxes_byid(id_block, class_name, is_checked)
{
	var chkboxes = document.id(id_block).getElements('input.' + class_name + '[type=checkbox]');
	return _check_boxes(chkboxes, is_checked);
}

/**
 * Coche / décoche un ensemble de checkbox dans un block
 * @param array|element chkboxes éléments ou  ensemble d'élements a coché/décoché
 * @param bool is_checked Définit si on coche / décoche le checkbox
 */
function _check_boxes(chkboxes, is_checked)
{
	var count = chkboxes.length;

	if (count)
	{
		for (var i = 0; i < count; i++)
		{
			if (!chkboxes[i].disabled)
			{
				chkboxes[i].checked = is_checked;
			}
		}
	}
	else
	{
		if (!chkboxes.disabled)
		{
			chkboxes.checked = is_checked;
		}
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
		cookie_value = Cookie.read(id_block);
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
	Cookie.write(id_block, (hide_block[id_block]) ? "O" : "C", {duration: 31});
}

/*
** Lit le contenu d'un cookie et affiche ou non le block
*/
function block_cookie_read(block_name, img_name, img_src, mooeffect)
{
	cookie_value = Cookie.read(block_name);
	if (cookie_value == 'C')
	{
		if (!(Browser.Engine.trident && Browser.Engine.version == 4) && mooeffect)
		{
			blocks_height[block_name] = $(block_name).getCoordinates().height;
			$(block_name).style.height = '0px';
			$(block_name).style.opacity = 0;
		}
		else
		{
			$(block_name).style.display = 'none';
		}
		$(img_name).src = img_src;
	}
}

var fxBlocks = {};
function block_check(id_block, id_img, src_img_open, src_img_close, mooeffect)
{
	if ($defined(fxBlocks[id_block]))
	{
		fxBlocks[id_block].cancel();
	}
	else
	{
		fxBlocks[id_block] = new Fx.Morph(id_block,
		{
			duration: 500,
			transition: Fx.Transitions.linear
		});
	}

	hide_block[id_block] ^= true;
	if (hide_block[id_block])
	{
		if (!(Browser.Engine.trident && Browser.Engine.version == 4) && mooeffect)
		{
			fxBlocks[id_block].start({
				height: [$(id_block).getStyle('height'), blocks_height[id_block]],
				opacity: [$(id_block).getStyle('opacity'), 1]
			});
		}
		else
		{
			$(id_block).style.display = 'block';
		}
		$(id_img).src = src_img_open;
	}
	else
	{
		if (!(Browser.Engine.trident && Browser.Engine.version == 4) && mooeffect)
		{
			if (!$defined(blocks_height[id_block]))
			{
				blocks_height[id_block] = $(id_block).getCoordinates().height;
			}

			fxBlocks[id_block].start({
				height: [$(id_block).getStyle('height'), 0],
				opacity: [$(id_block).getStyle('opacity'), 0]
			});
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
    var ajax = new Request(
    {
        url: FSB_ROOT + 'ajax.' + FSB_PHPEXT + '?mode=search_user',
        onSuccess: function(txt, xml)
        {
            if (!txt)
            {
                $(id).style.visibility = 'hidden';
                return ;
            }
	
            $(id).innerHTML = txt;
            $(id).style.visibility = 'visible';
        }
    });

    ajax.send({
        mode: 'get',
        data: 'nickname=' + value + '&jsid=' + id_field + '&jsid2=' + id
    });
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
** Selection du code source (balise CODE), fonction reprise de phpBB3
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

/*
** Ouvre la fenêtre d'attente ajax
*/
function ajax_waiter_open()
{
	if (Browser.Engine.trident)
	{
		var scroll_y = document.body.scrollTop;
	}
	else
	{
		var scroll_y = window.pageYOffset;
	}
	$('ajax_waiter').style.top = scroll_y + 'px';
	$('ajax_waiter').style.left = '0px';
	$('ajax_waiter').innerHTML = '<img src="'+FSB_TPL+'img/ajax-loader.gif" />';
	$('ajax_waiter').style.display = 'block';
}

/*
** Ferme la fenête d'attendre ajax
*/
function ajax_waiter_close()
{
	$('ajax_waiter').style.display = 'none';
	$('ajax_waiter').innerHTML = '';
}