/*
** +---------------------------------------------------+
** | Name :			~/main/javascript/popup.js
** | Begin :		03/10/2006
** | Last :			04/07/2007
** | User :			Genova
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Fichier permettant la creation d'une POPUP en DHTML (pour les MP par exemple)
*/

// Capture de l'evenement de deplacement de souris
if (document.getElementById && !document.all)
{
	document.captureEvents(Event.MOUSEMOVE);
}
document.onmousemove = popup_move;

// Contient les positions de la popup en cours
var popup_left = 0;
var popup_top = 0;

// Contient les positions de la souris
var popup_mouse_x = 0;
var popup_mouse_y = 0;

// Garde en memoire l'ecart entre la position de la souris et la position de la fenetre
var popup_keep_x = 0;
var popup_keep_y = 0;

// true si on click sur la popup (si on lache le click on repasse a false)
var popup_is_clicked = false;

/*
** Ouvre une popup DHTML
** -----
** x ::			Position initiale X de la popup
** y ::			Position initiale Y de la popup
** title ::		Titre de la popup
** content ::	Contenu de la popup
*/
function popup_open(x, y, title, content)
{
	popup_left = x;
	popup_top = y;
	document.write('<div class="dhtml_popup" id="popup_id" style="top: ' + popup_top + 'px; left: ' + popup_left + 'px; z-index: 10;" onmouseup="popup_click(false)" onmousedown="popup_click(true)">');
	document.write('<div class="dhtml_popup_title">' + title + '</div>');
	document.write('<div class="error">' + content + '</div>');
	document.write('</div>');

	setTimeout('popup_position()', 10);
}

/*
** Positionne correctement la popup en fonction du scroll actuel
*/
function popup_position()
{
	if (Browser.Engine.trident)
	{
		var scroll_y = document.body.scrollTop;
	}
	else
	{
		var scroll_y = window.pageYOffset;
	}

	if (scroll_y == 0)
	{
		setTimeout('popup_position()', 100);
	}
	else
	{
		popup_top += scroll_y;
		document.getElementById('popup_id').style.top = popup_top + "px";
	}
}

/*
** Ferme la popup
*/
function popup_close()
{
	document.getElementById('popup_id').style.visibility = 'hidden';
}

/*
** Callback appele lors d'un evenement de click sur la popup
** -----
** state ::		Status du click en cours sur la pop up (true si on click, false si on lache le click)
*/
function popup_click(state)
{
	if (state)
	{
		popup_keep_x = popup_mouse_x - popup_left;
		popup_keep_y = popup_mouse_y - popup_top;
	}
	popup_is_clicked = state;
}

/*
** Callback appele lors d'un evenement de deplacement de souris
** -----
** e ::		Evenement
*/
function popup_move(e)
{
	// On recupere les positions X et Y de la souris
	if (document.getElementById && document.all)
	{
		popup_mouse_x = event.x + document.body.scrollLeft;
		popup_mouse_y = event.y + document.body.scrollTop;
	}
	else if (document.getElementById)
	{
		popup_mouse_x = e.pageX;
		popup_mouse_y = e.pageY;
	}

	// Si la popup est actuellement maintenue (en cliquant dessus), on stoque en permanance la position de la fenetre
	if (popup_is_clicked)
	{
		popup_left = popup_mouse_x - popup_keep_x;
		popup_top = popup_mouse_y - popup_keep_y;
	}
	document.getElementById('popup_id').style.left = popup_left + "px";
  	document.getElementById('popup_id').style.top = popup_top + "px";
}