/*
** +---------------------------------------------------+
** | Name :			~/main/javascript/ajax.js
** | Begin :		20/10/2006
** | Last :			16/01/2008
** | User :			Genova
** | License :		GPL v2.0
** +---------------------------------------------------+
*/

/*
** Basé sur la classe Datarequestor 1.5 (http://mikewest.org/archive/datarequestor) sous licence GPL.
** Téléchargez cette classe pour davantage d'informations techniques.
*/

// Constantes utiles
var AJAX_GET = 0;
var AJAX_POST = 1;
var AJAX_MODE_TXT = 0;
var AJAX_MODE_XML = 1;

/*
** Classe de gestion AJAX
*/
function Ajax()
{
	var self = this;
	
	/*
	** Charge un objet XMLHttpRequest
	*/
	this.xmlhttp = function()
	{
		try
		{
			self.httpRequest = new XMLHttpRequest();
		}
		catch (e)
		{
			try
			{
				self.httpRequest = new ActiveXObject("Msxml2.XMLHTTP")
			}
			catch(e)
			{
				var success = false;
				var MSXML_XMLHTTP_PROGIDS = new Array('Microsoft.XMLHTTP', 'MSXML2.XMLHTTP', 'MSXML2.XMLHTTP.5.0', 'MSXML2.XMLHTTP.4.0', 'MSXML2.XMLHTTP.3.0');
				for (var i = 0; i < MSXML_XMLHTTP_PROGIDS.length && !success; i++)
				{
					try
					{
						self.httpRequest = new ActiveXObject(MSXML_XMLHTTP_PROGIDS[i]);
						success = true;
					}
					catch (e)
					{
						self.httpRequest = null;
					}
				}
			}

		}

		return (self.httpRequest);
	}
	
	/*
	** Ajoute un argument à la requète
	** -----
	** type ::	Méthode d'envoie de l'argument (AJAX_GET | AJAX_POST)
	** name ::	Nom de l'argument
	** value ::	Valeur de l'argument
	*/
	this.set_arg = function(type, name, value)
	{
		self.args[type].push(new Array(name, escape(value)));
	}
	
	/*
	** Envoie les entetes HTTP
	** -----
	** url ::	URL de destination
	** mode ::	Type d'objet de retour (AJAX_MODE_TXT | AJAX_MODE_XML)
	*/
	this.send = function(url, mode)
	{
		// Mode d'envoie de la requète
		self.mode = mode;

		if ((typeof self.httpRequest.abort) != "undefined" && self.httpRequest.readyState != 0)
		{
			self.httpRequest.abort();
		}

		// Fonction appelée lorsque l'état de la transaction http change
		self.httpRequest.onreadystatechange = self.callback;

		// Construction des URL pour les méthodes GET et POST
		requestType = "GET";
		var urlGet = (url.indexOf("?") != -1) ? "&" : "?";
		for (var i = 0; i < self.args[AJAX_GET].length; i++)
		{
			urlGet += self.args[AJAX_GET][i][0] + "=" + self.args[AJAX_GET][i][1] + "&";
		}

		// En cas d'argument POST, on force l'envoie de la requète en POST (indispensable, sinon seules les données en GET sont receptionnées)
		var urlPost = "";
		for (var i = 0; i < self.args[AJAX_POST].length; i++)
		{
			requestType = "POST";
			urlPost += self.args[AJAX_POST][i][0] + "=" + self.args[AJAX_POST][i][1] + "&";
		}

		// Envoie des requètes HTTP
		self.httpRequest.open(requestType, url + urlGet, true);
		if ((typeof self.httpRequest.setRequestHeader) != "undefined")
		{
			// On force un header text/xml si on souhaite récupérer sous forme d'objet DOM les données
			if (mode == AJAX_MODE_XML && (typeof self.httpRequest.overrideMimeType) == "function")
			{
				self.httpRequest.overrideMimeType('text/xml');
			}
			self.httpRequest.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		}
		self.httpRequest.send(urlPost);

		return (true);
	}
	
	/*
	** Callback appelée lors que l'état de la requète HTTP change
	*/
	this.callback = function()
	{
		if ((self.httpRequest.readyState == 4 && self.httpRequest.status == 200) || (self.httpRequest.readyState == 4 && self.httpRequest.status == 0))
		{
			if (self.onload)
			{
				switch (self.mode)
				{
					case AJAX_MODE_TXT :
						self.onload(self.httpRequest.responseText);
					break;
	                    
					case AJAX_MODE_XML :
						self.onload(self.normalizeWhitespace(self.httpRequest.responseXML));
					break;
				}
			}
		}
		else if (self.httpRequest.readyState == 3)
		{
			if (self.onprogress && !document.all)
			{
				var contentLength = 0;
				try
				{
					contentLength = self.httpRequest.getResponseHeader("Content-Length");
				}
				catch (e)
				{
					contentLength = -1;
				}
				self.onprogress(self.httpRequest.responseText.length, contentLength);
			}

		}
		else if (self.httpRequest.readyState == 4)
		{
			if (self.onfail)
			{
				self.onfail(self.httpRequest.status);
			}
			else
			{
				throw new Error("La requete HTTP a echouer avec le status " + self.httpRequest.status + "\nReponse recue : " + self.httpRequest.responseText);
			}
		}
	}
	
	/*
	** Supprime les espaces totalement blancs entre les balises
	** -----
	** domObj ::	Objet DOM
	*/
	this.normalizeWhitespace = function (domObj)
	{
		if (document.createTreeWalker)
		{
			var filter = {
				acceptNode: function(node)
				{
					if (/\S/.test(node.nodeValue))
					{
						return NodeFilter.FILTER_SKIP;
					}
					return NodeFilter.FILTER_ACCEPT;
				}
			}

			// Safari semble ne pas support correctement DOM ...
			if (!Nav_Safari)
			{
				var treeWalker = document.createTreeWalker(domObj, NodeFilter.SHOW_TEXT, filter, true);
				while (treeWalker.nextNode())
				{
					treeWalker.currentNode.parentNode.removeChild(treeWalker.currentNode);
					treeWalker.currentNode = domObj;
				}
			}

			return (domObj);
		}
		else
		{
			return (domObj);
		}
	}

	// Propriétés
	self.args = new Array();
	self.args[AJAX_GET] = new Array();
	self.args[AJAX_POST] = new Array();
	self.httpRequest = null;
	self.mode = AJAX_MODE_TXT;
	self.onload = null;

	if (!this.xmlhttp())
	{
		throw new Error("Impossible de charger un objet XMLHttpRequest");
	}
}

/*
** Ouvre la fenêtre d'attente ajax
*/
function ajax_waiter_open()
{
	if (Nav_IE)
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