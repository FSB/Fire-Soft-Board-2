/*
** +---------------------------------------------------+
** | Name :			~/main/javascript/highlight.js
** | Begin :		21/12/2005
** | Last :			11/06/2006
** | User :			Genova
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Fichier contenant les fonctions javascript utiles pour les colorateurs syntaxiques
*/

var IB=new Object;
var posX=0;posY=0;
var xOffset=10;yOffset=10;
function AffBulle(color)
{
	contenu = '<table cellpadding="2" cellspacing="0" style="border: 2px #000000 solid" bgcolor="' + color + '" width="50" height="20"><tr><td></td></tr></table>';
	var finalPosX=posX-xOffset;
	if (finalPosX<0) finalPosX=0;
	if (document.layers)
	{
		document.layers["bulle"].document.write(contenu);
		document.layers["bulle"].document.close();
		document.layers["bulle"].top=posY+yOffset;
		document.layers["bulle"].left=finalPosX;
		document.layers["bulle"].visibility="show";
	}

	if (document.all) 
	{
		//var f=window.event;
		//doc=document.body.scrollTop;
		bulle.innerHTML = contenu;
		document.all["bulle"].style.top= posY + yOffset + 85;
		document.all["bulle"].style.left= finalPosX;//f.x-xOffset;
		document.all["bulle"].style.visibility = "visible";
	}
	else if (document.getElementById)
	{
		document.getElementById("bulle").innerHTML = contenu;
		document.getElementById("bulle").style.top = posY+yOffset + "px";
		document.getElementById("bulle").style.left = finalPosX + "px";
		document.getElementById("bulle").style.visibility = "visible";
	}
}

function getMousePos(e)
{
	if (document.all)
	{
		posX=event.x+document.body.scrollLeft; //modifs CL 09/2001 - IE : regrouper l'évènement
		posY=event.y+document.body.scrollTop;
	}
	else
	{
		posX=e.pageX; //modifs CL 09/2001 - NS6 : celui-ci ne supporte pas e.x et e.y
		posY=e.pageY; 
	}
}

function HideBulle()
{
	if (document.layers)
	{
		document.layers["bulle"].visibility="hide";
	}

	if (document.all) 
	{
		document.all["bulle"].style.visibility="hidden";
	}
	else if (document.getElementById)
	{
		document.getElementById("bulle").style.visibility="hidden";
	}
}

function InitBulle()
{
	if (document.layers)
	{
		window.captureEvents(Event.MOUSEMOVE);window.onMouseMove=getMousePos;
	}

	if (document.all || document.getElementById)
	{
		document.onmousemove=getMousePos;
	}
}