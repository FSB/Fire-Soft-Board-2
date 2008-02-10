/*
Script: Moo.js
	My Object Oriented javascript.

License:
	MIT-style license.

MooTools Copyright:
	copyright (c) 2007 Valerio Proietti, <http://mad4milk.net>

MooTools Credits:
	- Class is slightly based on Base.js <http://dean.edwards.name/weblog/2006/03/base/> (c) 2006 Dean Edwards, License <http://creativecommons.org/licenses/LGPL/2.1/>
	- Some functions are inspired by those found in prototype.js <http://prototype.conio.net/> (c) 2005 Sam Stephenson sam [at] conio [dot] net, MIT-style license
	- Documentation by Aaron Newton (aaron.newton [at] cnet [dot] com) and Valerio Proietti.

Modules inclus :
	- Core: Moo, Utility, Common
	- Native: Array, String, Function, Element, Event, Dom
	- Element: Element.Dimensions, Element.Events
	- FX
	- Window: Window.DomReady
	- Drag: Drag.Base
	- Plugins: Color
*/

var MooTools = {
	version: '1.1dev'
};

var Class = function(properties){
	var klass = function(){
		if (arguments[0] !== null && this.initialize && $type(this.initialize) == 'function') return this.initialize.apply(this, arguments);
		else return this;
	};
	$extend(klass, this);
	klass.prototype = properties;
	return klass;
};

Class.empty = function(){};

Class.prototype = {
	extend: function(properties){
		var proto = new this(null);
		for (var property in properties){
			var pp = proto[property];
			proto[property] = $mergeClass(pp, properties[property]);
		}
		return new Class(proto);
	},

	implement: function(properties){
		$extend(this.prototype, properties);
	}

};

function $type(obj){
	if (obj == undefined) return false;
	var type = typeof obj;
	if (type == 'object'){
		if (obj.htmlElement) return 'element';
		if (obj.push) return 'array';
		if (obj.nodeName){
			switch(obj.nodeType){
				case 1: return 'element';
				case 3: return obj.nodeValue.test(/\S/) ? 'textnode' : 'whitespace';
			}
		}
	}
	if ((type == 'object' || type == 'function') && obj.exec) return 'regexp';
	return type;
};

function $merge(){
	var mix = {};
	for (var i = 0; i < arguments.length; i++){
		for (var property in arguments[i]){
			var ap = arguments[i][property];
			var mp = mix[property];
			if (mp && $type(ap) == 'object' && $type(mp) == 'object') mix[property] = $merge(mp, ap);
			else mix[property] = ap;
		}
	}
	return mix;
};

//internal

function $mergeClass(previous, current){
	if (previous && previous != current){
		var ptype = $type(previous);
		var ctype = $type(current);
		if (ptype == 'function' && ctype == 'function'){
			var merged = function(){
				this.parent = arguments.callee.parent;
				return current.apply(this, arguments);
			};
			merged.parent = previous;
			return merged;
		} else if (ptype == 'object' && ctype == 'object'){
			return $merge(previous, current);
		}
	}
	return current;
};

var $extend = Object.extend = function(){
	var args = arguments;
	if (!args[1]) args = [this, args[0]];
	for (var property in args[1]) args[0][property] = args[1][property];
	return args[0];
};

var $native = Object.Native = function(){
	for (var i = 0; i < arguments.length; i++) arguments[i].extend = $native.extend;
};

$native.extend = function(props){
	for (var prop in props){
		if (!this.prototype[prop]) this.prototype[prop] = props[prop];
	}
};

$native(Function, Array, String, Number, Class);

var Abstract = function(obj){
	obj = obj || {};
	obj.extend = $extend;
	return obj;
};

//window, document

var Window = new Abstract(window);
var Document = new Abstract(document);
document.head = document.getElementsByTagName('head')[0];

function $chk(obj){
	return !!(obj || obj === 0);
};

function $pick(obj, picked){
	return (obj != undefined) ? obj : picked;
};

function $random(min, max){
	return Math.floor(Math.random() * (max - min + 1) + min);
};

function $time(){
	return new Date().getTime();
};

function $clear(timer){
	clearTimeout(timer);
	clearInterval(timer);
	return null;
};

if (window.ActiveXObject) window.ie = window[window.XMLHttpRequest ? 'ie7' : 'ie6'] = true;
else if (document.childNodes && !document.all && !navigator.taintEnabled) window.khtml = true;
else if (document.getBoxObjectFor != null) window.gecko = true;
window.xpath = !!(document.evaluate);

//htmlelement

if (typeof HTMLElement == 'undefined'){
	var HTMLElement = Class.empty;
	if (window.khtml) document.createElement("iframe"); //fixes safari
	HTMLElement.prototype = (window.khtml) ? window["[[DOMElement.prototype]]"] : {};
}
HTMLElement.prototype.htmlElement = true;

//enables background image cache for internet explorer 6

if (window.ie6) try {document.execCommand("BackgroundImageCache", false, true);} catch(e){};

var Chain = new Class({
	chain: function(fn){
		this.chains = this.chains || [];
		this.chains.push(fn);
		return this;
	},

	callChain: function(){
		if (this.chains && this.chains.length) this.chains.shift().delay(10, this);
	},

	clearChain: function(){
		this.chains = [];
	}

});

var Events = new Class({
	addEvent: function(type, fn){
		if (fn != Class.empty){
			this.$events = this.$events || {};
			this.$events[type] = this.$events[type] || [];
			this.$events[type].include(fn);
		}
		return this;
	},

	fireEvent: function(type, args, delay){
		if (this.$events && this.$events[type]){
			this.$events[type].each(function(fn){
				fn.create({'bind': this, 'delay': delay, 'arguments': args})();
			}, this);
		}
		return this;
	},

	removeEvent: function(type, fn){
		if (this.$events && this.$events[type]) this.$events[type].remove(fn);
		return this;
	}

});

var Options = new Class({
	setOptions: function(){
		var args = (arguments.length == 1) ? [this.options, arguments[0]] : arguments;
		this.options = $merge.apply(this, args);
		if (this.addEvent){
			for (var option in this.options){
				if (($type(this.options[option]) == 'function') && option.test(/^on[A-Z]/)) this.addEvent(option, this.options[option]);
			}
		}
		return this;
	}

});

//custom methods

Array.extend({
	forEach: function(fn, bind){
		for (var i = 0, j = this.length; i < j; i++) fn.call(bind, this[i], i, this);
	},

	filter: function(fn, bind){
		var results = [];
		for (var i = 0, j = this.length; i < j; i++){
			if (fn.call(bind, this[i], i, this)) results.push(this[i]);
		}
		return results;
	},

	map: function(fn, bind){
		var results = [];
		for (var i = 0, j = this.length; i < j; i++) results[i] = fn.call(bind, this[i], i, this);
		return results;
	},

	every: function(fn, bind){
		for (var i = 0, j = this.length; i < j; i++){
			if (!fn.call(bind, this[i], i, this)) return false;
		}
		return true;
	},

	some: function(fn, bind){
		for (var i = 0, j = this.length; i < j; i++){
			if (fn.call(bind, this[i], i, this)) return true;
		}
		return false;
	},

	indexOf: function(item, from){
		from = from || 0;
		var len = this.length;
		if (from < 0) from = Math.max(0, len + from);
		while (from < len){
			if (this[from] === item) return from;
			from++;
		}
		return -1;
	},

	copy: function(start, length){
		start = start || 0;
		if (start < 0) start = this.length + start;
		length = length || (this.length - start);
		var newArray = [];
		for (var i = 0; i < length; i++) newArray[i] = this[start++];
		return newArray;
	},

	remove: function(item){
		var i = 0;
		var len = this.length;
		while (i < len){
			if (this[i] && this[i] === item) this.splice(i, 1);
			else i++;
		}
		return this;
	},

	contains: function(item, from){
		return this.indexOf(item, from) != -1;
	},

	associate: function(keys){
		var obj = {}, length = Math.min(this.length, keys.length);
		for (var i = 0; i < length; i++) obj[keys[i]] = this[i];
		return obj;
	},

	extend: function(array){
		for (var i = 0, j = array.length; i < j; i++) this.push(array[i]);
		return this;
	},

	merge: function(array){
		for (var i = 0, l = array.length; i < l; i++) this.include(array[i]);
		return this;
	},

	include: function(item){
		if (!this.length || !this.contains(item)) this.push(item);
		return this;
	},

	getRandom: function(){
		return this[$random(0, this.length - 1)];
	},

	getLast: function(){
		return this[this.length - 1];
	}

});

//copies

Array.prototype.each = Array.prototype.forEach;
Array.prototype.test = Array.prototype.contains;
Array.prototype.removeItem = Array.prototype.remove;

function $A(array, start, length){
	return Array.prototype.copy.call(array, start, length);
};



function $each(iterable, fn, bind){
	if (iterable.length != undefined) Array.prototype.forEach.call(iterable, fn, bind);
	else for (var name in iterable) fn.call(bind || iterable, iterable[name], name);
};





String.extend({

	

	test: function(regex, params){
		return ((typeof regex == 'string') ? new RegExp(regex, params) : regex).test(this);
	},

	

	toInt: function(){
		return parseInt(this, 10);
	},

	

	toFloat: function(){
		return parseFloat(this);
	},

	

	camelCase: function(){
		return this.replace(/-\D/g, function(match){
			return match.charAt(1).toUpperCase();
		});
	},

	

	hyphenate: function(){
		return this.replace(/\w[A-Z]/g, function(match){
			return (match.charAt(0) + '-' + match.charAt(1).toLowerCase());
		});
	},

	

	capitalize: function(){
		return this.replace(/\b[a-z]/g, function(match){
			return match.toUpperCase();
		});
	},

	

	trim: function(){
		return this.replace(/^\s+|\s+$/g, '');
	},

	

	clean: function(){
		return this.replace(/\s{2,}/g, ' ').trim();
	},

	

	rgbToHex: function(array){
		var rgb = this.match(/\d{1,3}/g);
		return (rgb) ? rgb.rgbToHex(array) : false;
	},

	

	hexToRgb: function(array){
		var hex = this.match(/^#?(\w{1,2})(\w{1,2})(\w{1,2})$/);
		return (hex) ? hex.slice(1).hexToRgb(array) : false;
	},

	
	
	contains: function(string, s){
		return (s) ? (s + this + s).indexOf(s + string + s) > -1 : this.indexOf(string) > -1;
	},

	

	escapeRegExp: function(){
		return this.replace(/([.*+?^${}()|[\]\/\\])/g, '\\$1');
	}

});

Array.extend({

	

	rgbToHex: function(array){
		if (this.length < 3) return false;
		if (this[3] && (this[3] == 0) && !array) return 'transparent';
		var hex = [];
		for (var i = 0; i < 3; i++){
			var bit = (this[i] - 0).toString(16);
			hex.push((bit.length == 1) ? '0' + bit : bit);
		}
		return array ? hex : '#' + hex.join('');
	},

	

	hexToRgb: function(array){
		if (this.length != 3) return false;
		var rgb = [];
		for (var i = 0; i < 3; i++){
			rgb.push(parseInt((this[i].length == 1) ? this[i] + this[i] : this[i], 16));
		}
		return array ? rgb : 'rgb(' + rgb.join(',') + ')';
	}

});



Number.extend({

	

	toInt: function(){
		return parseInt(this);
	},

	

	toFloat: function(){
		return parseFloat(this);
	}

});





Function.extend({

	

	create: function(options){
		var fn = this;
		options = $merge({
			'bind': fn,
			'event': false,
			'arguments': null,
			'delay': false,
			'periodical': false,
			'attempt': false
		}, options);
		if ($chk(options.arguments) && $type(options.arguments) != 'array') options.arguments = [options.arguments];
		return function(event){
			var args;
			if (options.event){
				event = event || window.event;
				args = [(options.event === true) ? event : new options.event(event)];
				if (options.arguments) args = args.concat(options.arguments);
			}
			else args = options.arguments || arguments;
			var returns = function(){
				return fn.apply($pick(options.bind, fn), args);
			};
			if (options.delay) return setTimeout(returns, options.delay);
			if (options.periodical) return setInterval(returns, options.periodical);
			if (options.attempt) try {return returns();} catch(err){return false;};
			return returns();
		};
	},

	

	pass: function(args, bind){
		return this.create({'arguments': args, 'bind': bind});
	},

	

	attempt: function(args, bind){
		return this.create({'arguments': args, 'bind': bind, 'attempt': true})();
	},

	

	bind: function(bind, args){
		return this.create({'bind': bind, 'arguments': args});
	},

	

	bindAsEventListener: function(bind, args){
		return this.create({'bind': bind, 'event': true, 'arguments': args});
	},

	

	delay: function(delay, bind, args){
		return this.create({'delay': delay, 'bind': bind, 'arguments': args})();
	},

	

	periodical: function(interval, bind, args){
		return this.create({'periodical': interval, 'bind': bind, 'arguments': args})();
	}

});





var Element = new Class({

	

	initialize: function(el, props){
		if ($type(el) == 'string'){
			if (window.ie && props && (props.name || props.type)){
				var name = (props.name) ? ' name="' + props.name + '"' : '';
				var type = (props.type) ? ' type="' + props.type + '"' : '';
				delete props.name;
				delete props.type;
				el = '<' + el + name + type + '>';
			}
			el = document.createElement(el);
		}
		el = $(el);
		if (!props || !el) return el;
		for (var prop in props){
			var val = props[prop];
			switch(prop){
				case 'styles': el.setStyles(val); break;
				case 'events': if (el.addEvents) el.addEvents(val); break;
				case 'properties': el.setProperties(val); break;
				default: el.setProperty(prop, val);
			}
		}
		return el;
	}

});



var Elements = new Class({});

Elements.extend = Class.prototype.implement;



function $(el){
	if (!el) return false;
	if (el.htmlElement) return Garbage.collect(el);
	if ([window, document].contains(el)) return el;
	var type = $type(el);
	if (type == 'string'){
		el = document.getElementById(el);
		type = (el) ? 'element' : false;
	}
	if (type != 'element') return false;
	if (el.htmlElement) return Garbage.collect(el);
	if (['object', 'embed'].contains(el.tagName.toLowerCase())) return el;
	$extend(el, Element.prototype);
	el.htmlElement = true;
	return Garbage.collect(el);
};



document.getElementsBySelector = document.getElementsByTagName;

function $$(){
	if (!arguments) return false;
	var elements = [];
	for (var i = 0, j = arguments.length; i < j; i++){
		var selector = arguments[i];
		switch($type(selector)){
			case 'element': elements.push(selector);
			case 'boolean':
			case false: break;
			case 'string': selector = document.getElementsBySelector(selector, true);
			default: elements = elements.concat((selector.push) ? selector : $A(selector));
		}
	}
	return $$.unique(elements);
};

$$.unique = function(array){
	var elements = [];
	for (var i = 0, l = array.length; i < l; i++){
		if (array[i].$included) continue;
		var element = $(array[i]);
		if (element && !element.$included){
			element.$included = true;
			elements.push(element);
		}
	}
	for (var i = 0, l = elements.length; i < l; i++) elements[i].$included = null;
	return $extend(elements, new Elements);
};

Elements.Multi = function(property){
	return function(){
		var args = arguments;
		var items = [];
		var elements = true;
		for (var i = 0, j = this.length, returns; i < j; i++){
			returns = this[i][property].apply(this[i], args);
			if ($type(returns) != 'element') elements = false;
			items.push(returns);
		};
		return (elements) ? $$.unique(items) : items;
	};
};

Element.extend = function(properties){
	for (var property in properties){
		HTMLElement.prototype[property] = properties[property];
		Element.prototype[property] = properties[property];
		Elements.prototype[property] = Elements.Multi(property);
	}
};



Element.extend({

	inject: function(el, where){
		el = $(el);
		switch(where){
			case 'before': el.parentNode.insertBefore(this, el); break;
			case 'after':
				var next = el.getNext();
				if (!next) el.parentNode.appendChild(this);
				else el.parentNode.insertBefore(this, next);
				break;
			case 'top':
				var first = el.firstChild;
				if (first){
					el.insertBefore(this, first);
					break;
				}
			default: el.appendChild(this);
		}
		return this;
	},

	

	injectBefore: function(el){
		return this.inject(el, 'before');
	},

	

	injectAfter: function(el){
		return this.inject(el, 'after');
	},

	

	injectInside: function(el){
		return this.inject(el, 'bottom');
	},
	
	
	
	injectTop: function(el){
		return this.inject(el, 'top');
	},

	

	adopt: function(){
		$$.unique(arguments).injectInside(this);
		return this;
	},

	

	remove: function(){
		return this.parentNode.removeChild(this);
	},

	

	clone: function(contents){
		return $(this.cloneNode(contents !== false));
	},

	

	replaceWith: function(el){
		el = $(el);
		this.parentNode.replaceChild(el, this);
		return el;
	},

	

	appendText: function(text){
		if (window.ie){
			switch(this.getTag()){
				case 'style': this.styleSheet.cssText = text; return this;
				case 'script': return this.setProperty('text', text);
			}
		}
		this.appendChild(document.createTextNode(text));
		return this;
	},

	

	hasClass: function(className){
		return this.className.contains(className, ' ');
	},

	

	addClass: function(className){
		if (!this.hasClass(className)) this.className = (this.className + ' ' + className).clean();
		return this;
	},

	

	removeClass: function(className){
		this.className = this.className.replace(new RegExp('(^|\\s)' + className + '(?:\\s|$)'), '$1').clean();
		return this;
	},

	

	toggleClass: function(className){
		return this.hasClass(className) ? this.removeClass(className) : this.addClass(className);
	},

	

	setStyle: function(property, value){
		switch(property){
			case 'opacity': return this.setOpacity(parseFloat(value));
			case 'float': property = (window.ie) ? 'styleFloat' : 'cssFloat';
		}
		property = property.camelCase();
		switch($type(value)){
			case 'number': if (!['zIndex', 'zoom'].contains(property)) value += 'px'; break;
			case 'array': value = 'rgb(' + value.join(',') + ')';
		}
		this.style[property] = value;
		return this;
	},

	

	setStyles: function(source){
		switch($type(source)){
			case 'object': Element.setMany(this, 'setStyle', source); break;
			case 'string': this.style.cssText = source;
		}
		return this;
	},

	

	setOpacity: function(opacity){
		if (opacity == 0){
			if (this.style.visibility != "hidden") this.style.visibility = "hidden";
		} else {
			if (this.style.visibility != "visible") this.style.visibility = "visible";
		}
		if (!this.currentStyle || !this.currentStyle.hasLayout) this.style.zoom = 1;
		if (window.ie) this.style.filter = (opacity == 1) ? '' : "alpha(opacity=" + opacity * 100 + ")";
		this.style.opacity = this.$.opacity = opacity;
		return this;
	},

	

	getStyle: function(property){
		property = property.camelCase();
		var result = this.style[property];
		if (!$chk(result)){
			if (property == 'opacity') return this.$.opacity;
			var result = [];
			for (var style in Element.Styles){
				if (property == style){
					Element.Styles[style].each(function(s){
						result.push(this.getStyle(s));
					}, this);
					if (property == 'border'){
						var every = result.every(function(bit){
							return (bit == result[0]);
						});
						return (every) ? result[0] : false;
					}
					return result.join(' ');
				}
			}
			if (Element.Styles.border.contains(property)){
				['Width', 'Color', 'Style'].each(function(p){
					result.push(this.getStyle(property + p));
				}, this);
				return result.join(' ');
			}
			if (document.defaultView) result = document.defaultView.getComputedStyle(this, null).getPropertyValue(property.hyphenate());
			else if (this.currentStyle) result = this.currentStyle[property];
		}
		if (window.ie) result = Element.fixStyle(property, result, this);
		return (result && property.test(/color/i) && result.contains('rgb')) ? result.rgbToHex() : result;
	},

	

	getStyles: function(){
		return Element.getMany(this, 'getStyle', arguments);
	},

	walk: function(brother, start){
		brother += 'Sibling';
		var el = (start) ? this[start] : this[brother];
		while (el && $type(el) != 'element') el = el[brother];
		return $(el);
	},

	

	getPrevious: function(){
		return this.walk('previous');
	},

	

	getNext: function(){
		return this.walk('next');
	},

	

	getFirst: function(){
		return this.walk('next', 'firstChild');
	},

	

	getLast: function(){
		return this.walk('previous', 'lastChild');
	},

	

	getParent: function(){
		return $(this.parentNode);
	},

	

	getChildren: function(){
		return $$(this.childNodes);
	},

	

	hasChild: function(el) {
		return !!$A(this.getElementsByTagName('*')).contains(el);
	},

	

	getProperty: function(property){
		var index = Element.Properties[property];
		return (index) ? this[index] : this.getAttribute(property);
	},

	

	removeProperty: function(property){
		var index = Element.Properties[property];
		if (index) this[index] = '';
		else this.removeAttribute(property);
		return this;
	},

	

	getProperties: function(){
		return Element.getMany(this, 'getProperty', arguments);
	},

	

	setProperty: function(property, value){
		var index = Element.Properties[property];
		if (index) this[index] = value;
		else this.setAttribute(property, value);
		return this;
	},

	

	setProperties: function(source){
		return Element.setMany(this, 'setProperty', source);
	},

	

	setHTML: function(){
		this.innerHTML = $A(arguments).join('');
		return this;
	},

	

	getTag: function(){
		return this.tagName.toLowerCase();
	},

	

	empty: function(){
		Garbage.trash(this.getElementsByTagName('*'));
		return this.setHTML('');
	}

});

Element.fixStyle = function(property, result, element){
	if ($chk(parseInt(result))) return result;
	if (['height', 'width'].contains(property)){
		var values = (property == 'width') ? ['left', 'right'] : ['top', 'bottom'];
		var size = 0;
		values.each(function(value){
			size += element.getStyle('border-' + value + '-width').toInt() + element.getStyle('padding-' + value).toInt();
		});
		return element['offset' + property.capitalize()] - size + 'px';
	} else if (property.test(/border(.+)Width/)){
		return '0px';
	}
	return result;
};

Element.Styles = {'border': [], 'padding': [], 'margin': []};
['Top', 'Right', 'Bottom', 'Left'].each(function(direction){
	for (var style in Element.Styles) Element.Styles[style].push(style + direction);
});

Element.getMany = function(el, method, keys){
	var result = {};
	$each(keys, function(key){
		result[key] = el[method](key);
	});
	return result;
};

Element.setMany = function(el, method, pairs){
	for (var key in pairs) el[method](key, pairs[key]);
	return el;
};

Element.Properties = new Abstract({
	'class': 'className', 'for': 'htmlFor', 'colspan': 'colSpan',
	'rowspan': 'rowSpan', 'accesskey': 'accessKey', 'tabindex': 'tabIndex',
	'maxlength': 'maxLength', 'readonly': 'readOnly', 'value': 'value',
	'disabled': 'disabled', 'checked': 'checked', 'multiple': 'multiple'
});

Element.listenerMethods = {

	addListener: function(type, fn){
		if (this.addEventListener) this.addEventListener(type, fn, false);
		else this.attachEvent('on' + type, fn);
		return this;
	},

	removeListener: function(type, fn){
		if (this.removeEventListener) this.removeEventListener(type, fn, false);
		else this.detachEvent('on' + type, fn);
		return this;
	}

};

window.extend(Element.listenerMethods);
document.extend(Element.listenerMethods);
Element.extend(Element.listenerMethods);

Element.Events = new Abstract({});

var Garbage = {

	elements: [],

	collect: function(el){
		if (!el.$){
			Garbage.elements.push(el);
			el.$ = {'opacity': 1};
		}
		return el;
	},

	trash: function(elements){
		for (var i = 0, j = elements.length, el; i < j; i++){
			if (!(el = elements[i]) || !el.$) return;
			if (el.$events) {
				el.fireEvent('onTrash');
				el.removeEvents();
			}
			for (var p in el.$) el.$[p] = null;
			for (var p in Element.prototype) el[p] = null;
			el.htmlElement = el.$ = null;
			Garbage.elements.remove(el);
		}
	},

	empty: function(){
		Garbage.collect(window);
		Garbage.collect(document);
		Garbage.trash(Garbage.elements);
	}

};

window.addListener('unload', Garbage.empty);





var Event = new Class({

	initialize: function(event){
		event = event || window.event;
		this.event = event;
		this.type = event.type;
		this.target = event.target || event.srcElement;
		if (this.target.nodeType == 3) this.target = this.target.parentNode;
		this.shift = event.shiftKey;
		this.control = event.ctrlKey;
		this.alt = event.altKey;
		this.meta = event.metaKey;
		if (['DOMMouseScroll', 'mousewheel'].contains(this.type)){
			this.wheel = event.wheelDelta ? (event.wheelDelta / (window.opera ? -120 : 120)) : -(event.detail || 0) / 3;
		} else if (this.type.contains('key')){
			this.code = event.which || event.keyCode;
			for (var name in Event.keys){
				if (Event.keys[name] == this.code){
					this.key = name;
					break;
				}
			}
			if (this.type == 'keydown'){
				var fKey = this.code - 111;
				if (fKey > 0 && fKey < 13) this.key = 'f' + fKey;
			}
			this.key = this.key || String.fromCharCode(this.code).toLowerCase();
		} else if (this.type.test(/(click|mouse|menu)/)){
			this.page = {
				'x': event.pageX || event.clientX + document.documentElement.scrollLeft,
				'y': event.pageY || event.clientY + document.documentElement.scrollTop
			};
			this.client = {
				'x': event.pageX ? event.pageX - window.pageXOffset : event.clientX,
				'y': event.pageY ? event.pageY - window.pageYOffset : event.clientY
			};
			this.rightClick = (event.which == 3) || (event.button == 2);
			switch(this.type){
				case 'mouseover': this.relatedTarget = event.relatedTarget || event.fromElement; break;
				case 'mouseout': this.relatedTarget = event.relatedTarget || event.toElement;
			}
			if (this.relatedTarget && this.relatedTarget.nodeType == 3) this.relatedTarget = this.relatedTarget.parentNode;
		}
	},

	

	stop: function() {
		return this.stopPropagation().preventDefault();
	},

	

	stopPropagation: function(){
		if (this.event.stopPropagation) this.event.stopPropagation();
		else this.event.cancelBubble = true;
		return this;
	},

	

	preventDefault: function(){
		if (this.event.preventDefault) this.event.preventDefault();
		else this.event.returnValue = false;
		return this;
	}

});

Event.keys = new Abstract({
	'enter': 13,
	'up': 38,
	'down': 40,
	'left': 37,
	'right': 39,
	'esc': 27,
	'space': 32,
	'backspace': 8,
	'tab': 9,
	'delete': 46
});



Element.Events.extend({

	

	'mouseenter': {
		type: 'mouseover',
		map: function(event){
			event = new Event(event);
			if (event.relatedTarget == this || this.hasChild(event.relatedTarget)) return;
			this.fireEvent('mouseenter', event);
		}
	},
	
	
	
	'mouseleave': {
		type: 'mouseout',
		map: function(event){
			event = new Event(event);
			if (event.relatedTarget == this || this.hasChild(event.relatedTarget)) return;
			this.fireEvent('mouseleave', event);
		}
	}
	
});



Function.extend({

	

	bindWithEvent: function(bind, args){
		return this.create({'bind': bind, 'arguments': args, 'event': Event});
	}

});







function $E(selector, filter){
	return ($(filter) || document).getElement(selector);
};



function $ES(selector, filter){
	return ($(filter) || document).getElementsBySelector(selector);
};

$$.shared = {

	cache: {},

	regexp: /^(\w*|\*)(?:#([\w-]+)|\.([\w-]+))?(?:\[(\w+)(?:([!*^$]?=)["']?([^"'\]]*)["']?)?])?$/,

	getNormalParam: function(selector, items, context, param, i){
		Filters.selector = param;
		if (i == 0){
			if (param[2]){
				var el = context.getElementById(param[2]);
				if (!el || ((param[1] != '*') && (el.tagName.toLowerCase() != param[1]))) return false;
				items = [el];
			} else {
				items = $A(context.getElementsByTagName(param[1]));
			}
		} else {
			items = $$.shared.getElementsByTagName(items, param[1]);
			if (param[2]) items = items.filter(Filters.id);
		}
		if (param[3]) items = items.filter(Filters.className);
		if (param[4]) items = items.filter(Filters.attribute);
		return items;
	},

	getXpathParam: function(selector, items, context, param, i){
		if ($$.shared.cache[selector].xpath){
			items.push($$.shared.cache[selector].xpath);
			return items;
		}
		var temp = context.namespaceURI ? ['xhtml:'] : [];
		temp.push(param[1]);
		if (param[2]) temp.push('[@id="', param[2], '"]');
		if (param[3]) temp.push('[contains(concat(" ", @class, " "), " ', param[3], ' ")]');
		if (param[4]){
			if (param[5] && param[6]){
				switch(param[5]){
					case '*=': temp.push('[contains(@', param[4], ', "', param[6], '")]'); break;
					case '^=': temp.push('[starts-with(@', param[4], ', "', param[6], '")]'); break;
					case '$=': temp.push('[substring(@', param[4], ', string-length(@', param[4], ') - ', param[6].length, ' + 1) = "', param[6], '"]'); break;
					case '=': temp.push('[@', param[4], '="', param[6], '"]'); break;
					case '!=': temp.push('[@', param[4], '!="', param[6], '"]');
				}
			} else temp.push('[@', param[4], ']');
		}
		temp = temp.join('');
		$$.shared.cache[selector].xpath = temp;
		items.push(temp);
		return items;
	},

	getNormalItems: function(items, context, nocash){
		return (nocash) ? items : $$.unique(items);
	},

	getXpathItems: function(items, context, nocash){
		var elements = [];
		var xpath = document.evaluate('.//' + items.join('//'), context, $$.shared.resolver, XPathResult.UNORDERED_NODE_SNAPSHOT_TYPE, null);
		for (var i = 0, j = xpath.snapshotLength; i < j; i++) elements.push(xpath.snapshotItem(i));
		return (nocash) ? elements : $extend(elements.map($), new Elements);
	},

	resolver: function(prefix){
		return (prefix == 'xhtml') ? 'http://www.w3.org/1999/xhtml' : false;
	},

	getElementsByTagName: function(context, tagName){
		var found = [];
		for (var i = 0, j = context.length; i < j; i++) found = found.concat($A(context[i].getElementsByTagName(tagName)));
		return found;
	}

};

if (window.xpath){
	$$.shared.getParam = $$.shared.getXpathParam;
	$$.shared.getItems = $$.shared.getXpathItems;
} else {
	$$.shared.getParam = $$.shared.getNormalParam;
	$$.shared.getItems = $$.shared.getNormalItems;
}



Element.domMethods = {
	getElements: function(selector, nocash){
		var items = [];
		selector = selector.trim().split(' ');
		for (var i = 0, j = selector.length; i < j; i++){
			var sel = selector[i];
			var param;
			if ($$.shared.cache[sel]){
				param = $$.shared.cache[sel].param;
			} else {
				param = sel.match($$.shared.regexp);
				if (!param) break;
				param[1] = param[1] || '*';
				$$.shared.cache[sel] = {'param': param};
			}
			var temp = $$.shared.getParam(sel, items, this, param, i);
			if (!temp) break;
			items = temp;
		}
		return $$.shared.getItems(items, this, nocash);
	},

	

	getElement: function(selector){
		return $(this.getElements(selector, true)[0] || false);
	},

	

	getElementsBySelector: function(selector, nocash){
		var elements = [];
		selector = selector.split(',');
		for (var i = 0, j = selector.length; i < j; i++) elements = elements.concat(this.getElements(selector[i], true));
		return (nocash) ? elements : $$.unique(elements);
	},

	

	getElementsByClassName: function(className){
		return this.getElements('.' + className);
	}

};

Element.extend({

	

	getElementById: function(id){
		var el = document.getElementById(id);
		if (!el) return false;
		for (var parent = el.parentNode; parent != this; parent = parent.parentNode){
			if (!parent) return false;
		}
		return el;
	}

});

document.extend(Element.domMethods);
Element.extend(Element.domMethods);

//dom filters, internal methods.

var Filters = {

	selector: [],

	id: function(el){
		return (el.id == Filters.selector[2]);
	},

	className: function(el){
		return el.className.contains(Filters.selector[3], ' ');
	},

	attribute: function(el){
		var current = Element.prototype.getProperty.call(el, Filters.selector[4]);
		if (!current) return false;
		var operator = Filters.selector[5];
		if (!operator) return true;
		var value = Filters.selector[6];
		switch(operator){
			case '=': return (current == value);
			case '*=': return (current.contains(value));
			case '^=': return (current.test('^' + value));
			case '$=': return (current.test(value + '$'));
			case '!=': return (current != value);
			case '~=': return current.contains(value, ' ');
		}
		return false;
	}

};

Element.extend({
	getValue: function(){
		switch(this.getTag()){
			case 'select':
				var values = [];
				$each(this.options, function(opt){
					if (opt.selected) values.push($pick(opt.value, opt.text));
				});
				return (this.multiple) ? values : values[0];
			case 'input': if (!(this.checked && ['checkbox', 'radio'].contains(this.type)) && !['hidden', 'text', 'password'].contains(this.type)) break;
			case 'textarea': return this.value;
		}
		return false;
	},

	getFormElements: function(){
		return $$(this.getElementsByTagName('input'), this.getElementsByTagName('select'), this.getElementsByTagName('textarea'));
	},

	toQueryString: function(){
		var queryString = [];
		this.getFormElements().each(function(el){
			var name = el.name;
			var value = el.getValue();
			if (value === false || !name || el.disabled) return;
			var qs = function(val){
				queryString.push(name + '=' + encodeURIComponent(val));
			};
			if ($type(value) == 'array') value.each(qs);
			else qs(value);
		});
		return queryString.join('&');
	}

});

Element.extend({
	scrollTo: function(x, y){
		this.scrollLeft = x;
		this.scrollTop = y;
	},

	getSize: function(){
		return {
			'scroll': {'x': this.scrollLeft, 'y': this.scrollTop},
			'size': {'x': this.offsetWidth, 'y': this.offsetHeight},
			'scrollSize': {'x': this.scrollWidth, 'y': this.scrollHeight}
		};
	},

	getPosition: function(overflown){
		overflown = overflown || [];
		var el = this, left = 0, top = 0;
		do {
			left += el.offsetLeft || 0;
			top += el.offsetTop || 0;
			el = el.offsetParent;
		} while (el);
		overflown.each(function(element){
			left -= element.scrollLeft || 0;
			top -= element.scrollTop || 0;
		});
		return {'x': left, 'y': top};
	},
	
	getTop: function(){
		return this.getPosition().y;
	},

	getLeft: function(){
		return this.getPosition().x;
	},

	getCoordinates: function(overflown){
		var position = this.getPosition(overflown);
		var obj = {
			'width': this.offsetWidth,
			'height': this.offsetHeight,
			'left': position.x,
			'top': position.y
		};
		obj.right = obj.left + obj.width;
		obj.bottom = obj.top + obj.height;
		return obj;
	}

});

Element.eventMethods = {
	addEvent: function(type, fn){
		this.$events = this.$events || {};
		this.$events[type] = this.$events[type] || {'keys': [], 'values': []};
		if (this.$events[type].keys.contains(fn)) return this;
		this.$events[type].keys.push(fn);
		var realType = type;
		var bound = false;
		if (Element.Events[type]){
			if (Element.Events[type].add) Element.Events[type].add.call(this, fn);
			if (Element.Events[type].map) bound = Element.Events[type].map.bindAsEventListener(this);
			realType = Element.Events[type].type || type;
		}
		if (!this.addEventListener) bound = bound || fn.bindAsEventListener(this);
		else bound = bound || fn;
		this.$events[type].values.push(bound);
		return this.addListener(realType, bound);
	},

	removeEvent: function(type, fn){
		if (!this.$events || !this.$events[type]) return this;
		var pos = this.$events[type].keys.indexOf(fn);
		if (pos == -1) return this;
		var key = this.$events[type].keys.splice(pos,1)[0];
		var value = this.$events[type].values.splice(pos,1)[0];
		if (Element.Events[type]){
			if (Element.Events[type].remove) Element.Events[type].remove.call(this, fn);
			type = Element.Events[type].type || type;
		}
		return this.removeListener(type, value);
	},

	addEvents: function(source){
		return Element.setMany(this, 'addEvent', source);
	},

	removeEvents: function(type){
		if (!this.$events) return this;
		if (type){
			if (this.$events[type]){
				$A(this.$events[type].keys).each(function(fn, i){
					this.removeEvent(type, fn);
				}, this);
				this.$events[type] = null;
			}
		} else {
			for (var evType in this.$events) this.removeEvents(evType);
			this.$events = null;
		}
		return this;
	},

	fireEvent: function(type, args){
		if (this.$events && this.$events[type]){
			this.$events[type].keys.each(function(fn){
				fn.bind(this, args)();
			}, this);
		}
	}

};

Element.Events.mousewheel = {
	type: (window.gecko) ? 'DOMMouseScroll' : 'mousewheel'
};

window.extend(Element.eventMethods);
document.extend(Element.eventMethods);
Element.extend(Element.eventMethods);

Element.Events.domready = {

	add: function(fn){
		if (window.loaded){
			fn.call(this);
			return;
		}
		var domReady = function(){
			if (window.loaded) return;
			window.loaded = true;
			window.timer = $clear(window.timer);
			this.fireEvent('domready');
		}.bind(this);
		if (document.readyState && window.khtml){
			window.timer = function(){
				if (['loaded','complete'].contains(document.readyState)) domReady();
			}.periodical(50);
		} else if (document.readyState && window.ie){
			if (!$('ie_ready')){
				var src = (window.location.protocol == 'https:') ? '://0' : 'javascript:void(0)';
				document.write('<\/script>');
				$('ie_ready').onreadystatechange = function(){
					if (this.readyState == 'complete') domReady();
				};
			}
		} else {
			window.addListener("load", domReady);
			document.addListener("DOMContentLoaded", domReady);
		}
	}

};

window.onDomReady = function(fn){
	return this.addEvent('domready', fn);
};

window.extend({
	getWidth: function(){
		if (this.khtml) return this.innerWidth;
		if (this.opera) return document.body.clientWidth;
		return document.documentElement.clientWidth;
	},

	getHeight: function(){
		if (this.khtml) return this.innerHeight;
		if (this.opera) return document.body.clientHeight;
		return document.documentElement.clientHeight;
	},

	getScrollWidth: function(){
		if (this.ie) return Math.max(document.documentElement.offsetWidth, document.documentElement.scrollWidth);
		if (this.khtml) return document.body.scrollWidth;
		return document.documentElement.scrollWidth;
	},

	getScrollHeight: function(){
		if (this.ie) return Math.max(document.documentElement.offsetHeight, document.documentElement.scrollHeight);
		if (this.khtml) return document.body.scrollHeight;
		return document.documentElement.scrollHeight;
	},

	getScrollLeft: function(){
		return this.pageXOffset || document.documentElement.scrollLeft;
	},

	getScrollTop: function(){
		return this.pageYOffset || document.documentElement.scrollTop;
	},

	getSize: function(){
		return {
			'size': {'x': this.getWidth(), 'y': this.getHeight()},
			'scrollSize': {'x': this.getScrollWidth(), 'y': this.getScrollHeight()},
			'scroll': {'x': this.getScrollLeft(), 'y': this.getScrollTop()}
		};
	},

	//ignore
	getPosition: function(){return {'x': 0, 'y': 0}}

});

var Fx = {Shared: {}};

Fx.Base = new Class({

	options: {
		onStart: Class.empty,
		onComplete: Class.empty,
		onCancel: Class.empty,
		transition: function(t, c, d){
			return -c / 2 * (Math.cos(Math.PI * t / d) - 1);
		},
		duration: 500,
		unit: 'px',
		wait: true,
		fps: 50
	},

	initialize: function(options){
		this.element = this.element || null;
		this.setOptions(options);
		if (this.options.initialize) this.options.initialize.call(this);
	},

	step: function(){
		var time = $time();
		if (time < this.time + this.options.duration){
			this.cTime = time - this.time;
			this.setNow();
			this.increase();
		} else {
			this.stop(true);
			this.now = this.to;
			this.increase();
			this.fireEvent('onComplete', this.element, 10);
			this.callChain();
		}
	},

	set: function(to){
		this.now = to;
		this.increase();
		return this;
	},

	setNow: function(){
		this.now = this.compute(this.from, this.to);
	},

	compute: function(from, to){
		return this.options.transition(this.cTime, (to - from), this.options.duration) + from;
	},

	start: function(from, to){
		if (!this.options.wait) this.stop();
		else if (this.timer) return this;
		this.from = from;
		this.to = to;
		this.time = $time();
		this.timer = this.step.periodical(Math.round(1000 / this.options.fps), this);
		this.fireEvent('onStart', this.element);
		return this;
	},

	stop: function(end){
		if (!this.timer) return this;
		this.timer = $clear(this.timer);
		if (!end) this.fireEvent('onCancel', this.element);
		return this;
	},

	//compat
	custom: function(from, to){return this.start(from, to)},
	clearTimer: function(end){return this.stop(end)}

});

Fx.Base.implement(new Chain);
Fx.Base.implement(new Events);
Fx.Base.implement(new Options);


Fx.CSS = {
	select: function(property, to){
		if (property.test(/color/i)) return this.Color;
		if (to.contains && to.contains(' ')) return this.Multi;
		return this.Single;
	},
	parse: function(el, property, fromTo){
		if (!fromTo.push) fromTo = [fromTo];
		var from = fromTo[0], to = fromTo[1];
		if (!to && to != 0){
			to = from;
			from = el.getStyle(property);
		}
		var css = this.select(property, to);
		return {from: css.parse(from), to: css.parse(to), css: css};
	}
};

Fx.CSS.Single = {
	parse: function(value){
		return parseFloat(value);
	},
	getNow: function(from, to, fx){
		return fx.compute(from, to);
	},
	getValue: function(value, unit){
		return value + unit;
	}
};

Fx.CSS.Multi = {
	parse: function(value){
		return value.push ? value : value.split(' ').map(function(v){
			return parseFloat(v);
		});
	},
	getNow: function(from, to, fx){
		var now = [];
		for (var i = 0; i < from.length; i++) now[i] = fx.compute(from[i], to[i]);
		return now;
	},
	getValue: function(value, unit){
		return value.join(unit + ' ') + unit;
	}
};

Fx.CSS.Color = {
	parse: function(value){
		return value.push ? value : value.hexToRgb(true);
	},
	getNow: function(from, to, fx){
		var now = [];
		for (var i = 0; i < from.length; i++) now[i] = Math.round(fx.compute(from[i], to[i]));
		return now;
	},
	getValue: function(value){
		return 'rgb(' + value.join(',') + ')';
	}
};

Fx.Style = Fx.Base.extend({
	initialize: function(el, property, options){
		this.element = $(el);
		this.property = property;
		this.parent(options);
	},
	hide: function(){
		return this.set(0);
	},
	setNow: function(){
		this.now = this.css.getNow(this.from, this.to, this);
	},
	set: function(to){
		this.css = Fx.CSS.select(this.property, to);
		return this.parent(this.css.parse(to));
	},
	start: function(from, to){
		if (this.timer && this.options.wait) return this;
		var parsed = Fx.CSS.parse(this.element, this.property, [from, to]);
		this.css = parsed.css;
		return this.parent(parsed.from, parsed.to);
	},
	increase: function(){
		this.element.setStyle(this.property, this.css.getValue(this.now, this.options.unit));
	}
});



Element.extend({
	effect: function(property, options){
		return new Fx.Style(this, property, options);
	}
});

Fx.Styles = Fx.Base.extend({
	initialize: function(el, options){
		this.element = $(el);
		this.parent(options);
	},
	setNow: function(){
		for (var p in this.from) this.now[p] = this.css[p].getNow(this.from[p], this.to[p], this);
	},
	set: function(to){
		var parsed = {};
		this.css = {};
		for (var p in to){
			this.css[p] = Fx.CSS.select(p, to[p]);
			parsed[p] = this.css[p].parse(to[p]);
		}
		return this.parent(parsed);
	},
	start: function(obj){
		if (this.timer && this.options.wait) return this;
		this.now = {};
		this.css = {};
		var from = {}, to = {};
		for (var p in obj){
			var parsed = Fx.CSS.parse(this.element, p, obj[p]);
			from[p] = parsed.from;
			to[p] = parsed.to;
			this.css[p] = parsed.css;
		}
		return this.parent(from, to);
	},
	increase: function(){
		for (var p in this.now) this.element.setStyle(p, this.css[p].getValue(this.now[p], this.options.unit));
	}
});



Element.extend({
	effects: function(options){
		return new Fx.Styles(this, options);
	}
});

Fx.Elements = Fx.Base.extend({
	initialize: function(elements, options){
		this.elements = $$(elements);
		this.parent(options);
	},
	setNow: function(){
		for (var i in this.from){
			var iFrom = this.from[i], iTo = this.to[i], iCss = this.css[i], iNow = this.now[i] = {};
			for (var p in iFrom) iNow[p] = iCss[p].getNow(iFrom[p], iTo[p], this);
		}
	},
	set: function(to){
		var parsed = {};
		this.css = {};
		for (var i in to){
			var iTo = to[i], iCss = this.css[i] = {}, iParsed = parsed[i] = {};
			for (var p in iTo){
				iCss[p] = Fx.CSS.select(p, iTo[p]);
				iParsed[p] = iCss[p].parse(iTo[p]);
			}
		}
		return this.parent(parsed);
	},
	start: function(obj){
		if (this.timer && this.options.wait) return this;
		this.now = {};
		this.css = {};
		var from = {}, to = {};
		for (var i in obj){
			var iProps = obj[i], iFrom = from[i] = {}, iTo = to[i] = {}, iCss = this.css[i] = {};
			for (var p in iProps){
				var parsed = Fx.CSS.parse(this.elements[i], p, iProps[p]);
				iFrom[p] = parsed.from;
				iTo[p] = parsed.to;
				iCss[p] = parsed.css;
			}
		}
		return this.parent(from, to);
	},
	increase: function(){
		for (var i in this.now){
			var iNow = this.now[i], iCss = this.css[i];
			for (var p in iNow) this.elements[i].setStyle(p, iCss[p].getValue(iNow[p], this.options.unit));
		}
	}

});

Fx.Scroll = Fx.Base.extend({
	initialize: function(element, options){
		this.now = [];
		this.element = $(element);
		this.addEvent('onStart', function(){
			this.element.addEvent('mousewheel', this.stop.bind(this, false));
		}.bind(this));
		this.removeEvent('onComplete', function(){
			this.element.removeEvent('mousewheel', this.stop.bind(this, false));
		}.bind(this));
		this.parent(options);
	},
	setNow: function(){
		for (var i = 0; i < 2; i++) this.now[i] = this.compute(this.from[i], this.to[i]);
	},
	scrollTo: function(x, y){
		if (this.timer && this.options.wait) return this;
		var el = this.element.getSize();
		var values = {'x': x, 'y': y};
		for (var z in el.size){
			var max = el.scrollSize[z] - el.size[z];
			if ($chk(values[z])) values[z] = ($type(values[z]) == 'number') ? Math.max(Math.min(values[z], max), 0) : max;
			else values[z] = el.scroll[z];
		}
		return this.start([el.scroll.x, el.scroll.y], [values.x, values.y]);
	},
	toTop: function(){
		return this.scrollTo(false, 0);
	},
	toBottom: function(){
		return this.scrollTo(false, 'full');
	},
	toLeft: function(){
		return this.scrollTo(0, false);
	},
	toRight: function(){
		return this.scrollTo('full', false);
	},
	toElement: function(el){
		var parent = this.element.getPosition();
		var target = $(el).getPosition();
		return this.scrollTo(target.x - parent.x, target.y - parent.y);
	},
	increase: function(){
		this.element.scrollTo(this.now[0], this.now[1]);
	}
});

Fx.Slide = Fx.Base.extend({
	options: {
		mode: 'vertical'
	},
	initialize: function(el, options){
		this.element = $(el);
		this.wrapper = new Element('div', {'styles': $extend(this.element.getStyles('margin'), {'overflow': 'hidden'})}).injectAfter(this.element).adopt(this.element);
		this.element.setStyle('margin', 0);
		this.setOptions(options);
		this.now = [];
		this.parent(this.options);
	},
	setNow: function(){
		for (var i = 0; i < 2; i++) this.now[i] = this.compute(this.from[i], this.to[i]);
	},
	vertical: function(){
		this.margin = 'margin-top';
		this.layout = 'height';
		this.offset = this.element.offsetHeight;
	},
	horizontal: function(){
		this.margin = 'margin-left';
		this.layout = 'width';
		this.offset = this.element.offsetWidth;
	},
	slideIn: function(mode){
		this[mode || this.options.mode]();
		return this.start([this.element.getStyle(this.margin).toInt(), this.wrapper.getStyle(this.layout).toInt()], [0, this.offset]);
	},
	slideOut: function(mode){
		this[mode || this.options.mode]();
		return this.start([this.element.getStyle(this.margin).toInt(), this.wrapper.getStyle(this.layout).toInt()], [-this.offset, 0]);
	},
	hide: function(mode){
		this[mode || this.options.mode]();
		return this.set([-this.offset, 0]);
	},
	show: function(mode){
		this[mode || this.options.mode]();
		return this.set([0, this.offset]);
	},
	toggle: function(mode){
		if (this.wrapper.offsetHeight == 0 || this.wrapper.offsetWidth == 0) return this.slideIn(mode);
		return this.slideOut(mode);
	},
	increase: function(){
		this.element.setStyle(this.margin, this.now[0] + this.options.unit);
		this.wrapper.setStyle(this.layout, this.now[1] + this.options.unit);
	}
});

Fx.Transitions = new Abstract({
	linear: function(t, c, d){
		return c * (t / d);
	}

});

Fx.Shared.CreateTransitionEases = function(transition, type){
	$extend(transition, {
		easeIn: function(t, c, d, x, y, z){
			return c - c * transition((d - t) / d, t, c, d, x, y, z);
		},

		easeOut: function(t, c, d, x, y, z){
			return c * transition(t / d, t, c, d, x, y, z);
		},

		easeInOut: function(t, c, d, x, y, z){
			d /= 2, c /= 2;
			var p = t / d;
			return (p < 1) ? transition.easeIn(t, c, d, x, y, z) : c * (transition(p - 1, t, c, d, x, y, z) + 1);
		}
	});
	//compatibility
	['In', 'Out', 'InOut'].each(function(mode){
		transition['ease' + mode].set = Fx.Shared.SetTransitionValues(transition['ease' + mode]);
		Fx.Transitions[type.toLowerCase() + mode] = transition['ease' + mode];
	});
};

Fx.Shared.SetTransitionValues = function(transition){
	return function(){
		var args = $A(arguments);
		return function(){
			return transition.apply(Fx.Transitions, $A(arguments).concat(args));
		};
	}
};

Fx.Transitions.extend = function(transitions){
	for (var type in transitions){
		if (type.test(/^[A-Z]/)) Fx.Shared.CreateTransitionEases(transitions[type], type);
		else transitions[type].set = Fx.Shared.SetTransitionValues(transitions[type]);
		Fx.Transitions[type] = transitions[type];
	}
};

Fx.Transitions.extend({
	
	

	Sine: function(p){
		return Math.sin(p * (Math.PI / 2));
	},
	
	

	Quad: function(p){
		return -(Math.pow(p - 1, 2) - 1);
	},
	
	

	Cubic: function(p){
		return Math.pow(p - 1, 3) + 1;
	},
	
	

	Quart: function(p){
		return -(Math.pow(p - 1, 4) - 1);
	},
	
	

	Quint: function(p){
		return Math.pow(p - 1, 5) + 1;
	},
	
	

	Expo: function(p){
		return -Math.pow(2, -10 * p) + 1;
	},
	
	

	Circ: function(p){
		return Math.sqrt(1 - Math.pow(p - 1, 2));
	},
	
	

	Bounce: function(p){
		var b = 7.5625;
		if (p < (1 / 2.75)) return b * Math.pow(p, 2);
		else if (p < (2 / 2.75)) return b * (p -= (1.5 / 2.75)) * p + 0.75;
		else if (p < (2.5 / 2.75)) return b * (p -= (2.25 / 2.75)) * p + 0.9375;
		else return b * (p -= (2.625 / 2.75)) * p + 0.984375;
	},
	
	

	Back: function(p, t, c, d, x){
		x = x || 1.70158;
		p -= 1;
		return Math.pow(p, 2) * ((x + 1) * p + x) + 1;
	},
	
	

	Elastic: function(p, t, c, d, x){
		x = d * 0.3 / (x || 1);
		return (c * Math.pow(2, -10 * p) * Math.sin((p * d - x / 4) * (2 * Math.PI) / x) + c) / c;
	}

});



var Drag = {};



Drag.Base = new Class({

	options: {
		handle: false,
		unit: 'px',
		onStart: Class.empty,
		onBeforeStart: Class.empty,
		onComplete: Class.empty,
		onSnap: Class.empty,
		onDrag: Class.empty,
		limit: false,
		modifiers: {x: 'left', y: 'top'},
		grid: false,
		snap: 6
	},

	initialize: function(el, options){
		this.setOptions(options);
		this.element = $(el);
		this.handle = $(this.options.handle) || this.element;
		this.mouse = {'now': {}, 'pos': {}};
		this.value = {'start': {}, 'now': {}};
		this.bound = {
			'start': this.start.bindWithEvent(this),
			'check': this.check.bindWithEvent(this),
			'drag': this.drag.bindWithEvent(this),
			'stop': this.stop.bind(this)
		};
		this.attach();
		if (this.options.initialize) this.options.initialize.call(this);
	},

	attach: function(){
		this.handle.addEvent('mousedown', this.bound.start);
		return this;
	},

	detach: function(){
		this.handle.removeEvent('mousedown', this.bound.start);
		return this;
	},

	start: function(event){
		this.fireEvent('onBeforeStart', this.element);
		this.mouse.start = event.page;
		var limit = this.options.limit;
		this.limit = {'x': [], 'y': []};
		for (var z in this.options.modifiers){
			if (!this.options.modifiers[z]) continue;
			this.value.now[z] = this.element.getStyle(this.options.modifiers[z]).toInt();
			this.mouse.pos[z] = event.page[z] - this.value.now[z];
			if (limit && limit[z]){
				for (var i = 0; i < 2; i++){
					if ($chk(limit[z][i])) this.limit[z][i] = limit[z][i].apply ? limit[z][i].call(this) : limit[z][i];
				}
			}
		}
		if ($type(this.options.grid) == 'number') this.options.grid = {'x': this.options.grid, 'y': this.options.grid};
		document.addListener('mousemove', this.bound.check);
		document.addListener('mouseup', this.bound.stop);
		this.fireEvent('onStart', this.element);
		event.stop();
	},

	check: function(event){
		var distance = Math.round(Math.sqrt(Math.pow(event.page.x - this.mouse.start.x, 2) + Math.pow(event.page.y - this.mouse.start.y, 2)));
		if (distance > this.options.snap){
			document.removeListener('mousemove', this.bound.check);
			document.addListener('mousemove', this.bound.drag);
			this.drag(event);
			this.fireEvent('onSnap', this.element);
		}
		event.stop();
	},

	drag: function(event){
		this.out = false;
		this.mouse.now = event.page;
		for (var z in this.options.modifiers){
			if (!this.options.modifiers[z]) continue;
			this.value.now[z] = this.mouse.now[z] - this.mouse.pos[z];
			if (this.limit[z]){
				if ($chk(this.limit[z][1]) && (this.value.now[z] > this.limit[z][1])){
					this.value.now[z] = this.limit[z][1];
					this.out = true;
				} else if ($chk(this.limit[z][0]) && (this.value.now[z] < this.limit[z][0])){
					this.value.now[z] = this.limit[z][0];
					this.out = true;
				}
			}
			if (this.options.grid[z]) this.value.now[z] -= (this.value.now[z] % this.options.grid[z]);
			this.element.setStyle(this.options.modifiers[z], this.value.now[z] + this.options.unit);
		}
		this.fireEvent('onDrag', this.element);
		event.stop();
	},

	stop: function(){
		document.removeListener('mousemove', this.bound.check);
		document.removeListener('mousemove', this.bound.drag);
		document.removeListener('mouseup', this.bound.stop);
		this.fireEvent('onComplete', this.element);
	}

});

Drag.Base.implement(new Events);
Drag.Base.implement(new Options);



Element.extend({

	

	makeResizable: function(options){
		return new Drag.Base(this, $merge({modifiers: {x: 'width', y: 'height'}}, options));
	}

});





Drag.Move = Drag.Base.extend({

	options: {
		droppables: [],
		container: false,
		overflown: []
	},

	initialize: function(el, options){
		this.setOptions(options);
		this.element = $(el);
		this.position = this.element.getStyle('position');
		this.droppables = $$(this.options.droppables);
		if (!['absolute', 'relative'].contains(this.position)) this.position = 'absolute';
		var top = this.element.getStyle('top').toInt();
		var left = this.element.getStyle('left').toInt();
		if (this.position == 'absolute'){
			top = $chk(top) ? top : this.element.getTop();
			left = $chk(left) ? left : this.element.getLeft();
		} else {
			top = $chk(top) ? top : 0;
			left = $chk(left) ? left : 0;
		}
		this.element.setStyles({
			'top': top,
			'left': left,
			'position': this.position
		});
		this.parent(this.element, this.options);
	},

	start: function(event){
		this.container = $(this.options.container);
		if (this.container){
			var cont = this.container.getCoordinates();
			var el = this.element.getCoordinates();
			if (this.position == 'absolute'){
				this.options.limit = {
					'x': [cont.left, cont.right - el.width],
					'y': [cont.top, cont.bottom - el.height]
				};
			} else {
				var diffx = el.left - this.element.getStyle('left').toInt();
				var diffy = el.top - this.element.getStyle('top').toInt();
				this.options.limit = {
					'y': [-(diffy) + cont.top, cont.bottom - diffy - el.height],
					'x': [-(diffx) + cont.left, cont.right - diffx - el.width]
				};
			}
		}
		this.parent(event);
	},

	drag: function(event){
		this.parent(event);
		if (this.out) return this;
		this.droppables.each(function(drop){
			if (this.checkAgainst($(drop))){
				if (!drop.overing) drop.fireEvent('over', [this.element, this]);
				drop.overing = true;
			} else {
				if (drop.overing) drop.fireEvent('leave', [this.element, this]);
				drop.overing = false;
			}
		}, this);
		return this;
	},

	checkAgainst: function(el){
		el = el.getCoordinates(this.options.overflown);
		return (this.mouse.now.x > el.left && this.mouse.now.x < el.right && this.mouse.now.y < el.bottom && this.mouse.now.y > el.top);
	},

	stop: function(){
		if (!this.out){
			var dropped = false;
			this.droppables.each(function(drop){
				if (this.checkAgainst(drop)){
					drop.fireEvent('drop', [this.element, this]);
					dropped = true;
				}
			}, this);
			if (!dropped) this.element.fireEvent('emptydrop', this);
		}
		this.parent();
		return this;
	}

});

Element.extend({

	

	makeDraggable: function(options){
		return new Drag.Move(this, options);
	}

});

var Hash = new Class({

	length: 0,

	initialize: function(obj){
		this.obj = {};
		this.extend(obj);
	},

	

	get: function(key){
		return this.obj[key];
	},

	

	hasKey: function(key){
		return (key in this.obj);
	},

	

	set: function(key, value){
		if (key in this.obj) this.length++;
		this.obj[key] = value;
		return this;
	},

	

	remove: function(key){
		if (!(key in this.obj)) return this;
		delete this.obj[key];
		this.length--;
		return this;
	},

	

	each: function(fn, bind){
		$each(this.obj, fn, bind);
	},

	

	extend: function(obj){
		for (var key in obj) this.set(key, obj[key]);
		return this;
	},

	

	empty: function(){
		this.obj = {};
		this.length = 0;
		return this;
	},

	

	keys: function(){
		var keys = [];
		for (var property in this.obj) keys.push(property);
		return keys;
	},

	

	values: function(){
		var values = [];
		for (var property in this.obj) values.push(this.obj[property]);
		return values;
	}

});





function $H(obj){
	return new Hash(obj);
};





var Color = new Class({

	initialize: function(color, type){
		type = type || (color.push ? 'rgb' : 'hex');
		var rgb, hsb;
		switch(type){
			case 'rgb':
				rgb = color;
				hsb = rgb.rgbToHsb();
				break;
			case 'hsb':
				rgb = color.hsbToRgb();
				hsb = color;
				break;
			default:
				rgb = color.hexToRgb(true);
				hsb = rgb.rgbToHsb();
		}
		rgb.hsb = hsb;
		rgb.hex = rgb.rgbToHex();
		return $extend(rgb, Color.prototype);
	},

	

	mix: function(){
		var colors = $A(arguments);
		var alpha = ($type(colors[colors.length - 1]) == 'number') ? colors.pop() : 50;
		var rgb = this.copy();
		colors.each(function(color){
			color = new Color(color);
			for (var i = 0; i < 3; i++) rgb[i] = Math.round((rgb[i] / 100 * (100 - alpha)) + (color[i] / 100 * alpha));
		});
		return new Color(rgb, 'rgb');
	},

	

	invert: function(){
		return new Color(this.map(function(value){
			return 255 - value;
		}));
	},

	

	setHue: function(value){
		return new Color([value, this.hsb[1], this.hsb[2]], 'hsb');
	},

	

	setSaturation: function(percent){
		return new Color([this.hsb[0], percent, this.hsb[2]], 'hsb');
	},

	

	setBrightness: function(percent){
		return new Color([this.hsb[0], this.hsb[1], percent], 'hsb');
	}

});


function $RGB(r, g, b){
	return new Color([r, g, b], 'rgb');
};



function $HSB(h, s, b){
	return new Color([h, s, b], 'hsb');
};



Array.extend({
	
	

	rgbToHsb: function(){
		var red = this[0], green = this[1], blue = this[2];
		var hue, saturation, brightness;
		var max = Math.max(red, green, blue), min = Math.min(red, green, blue);
		var delta = max - min;
		brightness = max / 255;
		saturation = (max != 0) ? delta / max : 0;
		if (saturation == 0){
			hue = 0;
		} else {
			var rr = (max - red) / delta;
			var gr = (max - green) / delta;
			var br = (max - blue) / delta;
			if (red == max) hue = br - gr;
			else if (green == max) hue = 2 + rr - br;
			else hue = 4 + gr - rr;
			hue /= 6;
			if (hue < 0) hue++;
		}
		return [Math.round(hue * 360), Math.round(saturation * 100), Math.round(brightness * 100)];
	},

	

	hsbToRgb: function(){
		var br = Math.round(this[2] / 100 * 255);
		if (this[1] == 0){
			return [br, br, br];
		} else {
			var hue = this[0] % 360;
			var f = hue % 60;
			var p = Math.round((this[2] * (100 - this[1])) / 10000 * 255);
			var q = Math.round((this[2] * (6000 - this[1] * f)) / 600000 * 255);
			var t = Math.round((this[2] * (6000 - this[1] * (60 - f))) / 600000 * 255);
			switch(Math.floor(hue / 60)){
				case 0: return [br, t, p];
				case 1: return [q, br, p];
				case 2: return [p, br, t];
				case 3: return [p, q, br];
				case 4: return [t, p, br];
				case 5: return [br, p, q];
			}
		}
		return false;
	}

});

/*
** Palette de couleur avancée
*/
var MooRainbow = new Class({
	options: {
		id: 'mooRainbow',
		prefix: 'moor-',
		imgPath: 'images/moorainbow/',
		startColor: [255, 0, 0],
		wheel: false,
		onStart: Class.empty,
		onComplete: Class.empty,
		onChange: Class.empty
	},
	
	initialize: function(el, options) {
		this.element = $(el); if (!this.element) return;
		this.setOptions(options);

		this.sliderPos = 0;
		this.pickerPos = {x: 0, y: 0};
		this.backupColor = this.options.startColor;
		this.currentColor = this.options.startColor;
		this.sets = {
			rgb: [],
			hsb: [],
			hex: []	
		}
		this.pickerClick = this.sliderClick  = false;
		if (!this.layout) this.doLayout();
		this.OverlayEvents();
		this.sliderEvents();
		this.backupEvent();
		if (this.options.wheel) this.wheelEvents();
		this.element.addEvent('click', function(e) { this.toggle(e); }.bind(this));
				
		this.layout.overlay.setStyle('background-color', this.options.startColor.rgbToHex());
		this.layout.backup.setStyle('background-color', this.backupColor.rgbToHex());

		this.pickerPos.x = this.snippet('curPos').l + this.snippet('curSize', 'int').w;
		this.pickerPos.y = this.snippet('curPos').t + this.snippet('curSize', 'int').h;
		
		this.manualSet(this.options.startColor);
		
		this.pickerPos.x = this.snippet('curPos').l + this.snippet('curSize', 'int').w;
		this.pickerPos.y = this.snippet('curPos').t + this.snippet('curSize', 'int').h;
		this.sliderPos = this.snippet('arrPos') - this.snippet('arrSize', 'int');

		if (window.khtml) this.hide();
	},
	
	toggle: function() {
		this.fireEvent('onStart', [this.sets, this]);
		this[this.visible ? 'hide' : 'show']()
	},
	
	show: function() {
		this.rePosition();
		this.layout.setStyle('display', 'block');
		this.visible = true;
	},
	
	hide: function() {
		this.layout.setStyles({'display': 'none'});
		this.visible = false;
	},
	
	manualSet: function(color, type) {
		if (!type || (type != 'hsb' && type != 'hex')) type = 'rgb';
		var rgb, hsb, hex;

		if (type == 'rgb') { rgb = color; hsb = color.rgbToHsb(); hex = color.rgbToHex(); } 
		else if (type == 'hsb') { hsb = color; rgb = color.hsbToRgb(); hex = rgb.rgbToHex(); }
		else { hex = color; rgb = color.hexToRgb(); hsb = rgb.rgbToHsb(); }
		
		this.setMooRainbow(rgb);
		this.autoSet(hsb);
	},
	
	autoSet: function(hsb) {
		var curH = this.snippet('curSize', 'int').h;
		var curW = this.snippet('curSize', 'int').w;
		var oveH = this.layout.overlay.height;
		var oveW = this.layout.overlay.width;
		var sliH = this.layout.slider.height;
		var arwH = this.snippet('arrSize', 'int');
		var hue;
		
		var posx = Math.round(((oveW * hsb[1]) / 100) - curW);
		var posy = Math.round(- ((oveH * hsb[2]) / 100) + oveH - curH);

		var c = Math.round(((sliH * hsb[0]) / 360)); c = (c == 360) ? 0 : c;
		var position = sliH - c + this.snippet('slider') - arwH;
		hue = [this.sets.hsb[0], 100, 100].hsbToRgb().rgbToHex();
		
		this.layout.cursor.setStyles({'top': posy, 'left': posx});
		this.layout.arrows.setStyle('top', position);
		this.layout.overlay.setStyle('background-color', hue);
		this.sliderPos = this.snippet('arrPos') - arwH;
		this.pickerPos.x = this.snippet('curPos').l + curW;
		this.pickerPos.y = this.snippet('curPos').t + curH;
	},
	
	setMooRainbow: function(color, type) {
		if (!type || (type != 'hsb' && type != 'hex')) type = 'rgb';
		var rgb, hsb, hex;

		if (type == 'rgb') { rgb = color; hsb = color.rgbToHsb(); hex = color.rgbToHex(); } 
		else if (type == 'hsb') { hsb = color; rgb = color.hsbToRgb(); hex = rgb.rgbToHex(); }
		else { hex = color; rgb = color.hexToRgb(); hsb = rgb.rgbToHsb(); }

		this.sets = {
			rgb: rgb,
			hsb: hsb,
			hex: hex
		};

		if (!$chk(this.pickerPos.x))
			this.autoSet(hsb);		

		this.RedInput.value = rgb[0];
		this.GreenInput.value = rgb[1];
		this.BlueInput.value = rgb[2];
		this.HueInput.value = hsb[0];
		this.SatuInput.value =  hsb[1];
		this.BrighInput.value = hsb[2];
		this.hexInput.value = hex;
		
		this.currentColor = rgb;

		this.chooseColor.setStyle('background-color', rgb.rgbToHex());
	},
	
	parseColors: function(x, y, z) {
		var s = Math.round((x * 100) / this.layout.overlay.width);
		var b = 100 - Math.round((y * 100) / this.layout.overlay.height);
		var h = 360 - Math.round((z * 360) / this.layout.slider.height) + this.snippet('slider') - this.snippet('arrSize', 'int');
		h -= this.snippet('arrSize', 'int');
		h = (h >= 360) ? 0 : (h < 0) ? 0 : h;
		s = (s > 100) ? 100 : (s < 0) ? 0 : s;
		b = (b > 100) ? 100 : (b < 0) ? 0 : b;

		return [h, s, b];
	},
	
	OverlayEvents: function() {
		var lim, curH, curW, inputs;
		curH = this.snippet('curSize', 'int').h;
		curW = this.snippet('curSize', 'int').w;
		inputs = this.arrRGB.copy().concat(this.arrHSB, this.hexInput);

		document.addEvent('click', function() { 
			if(this.visible) this.hide(this.layout); 
		}.bind(this));

		inputs.each(function(el) {
			el.addEvent('keydown', this.eventKeydown.bindWithEvent(this, el))
			el.addEvent('keyup', this.eventKeyup.bindWithEvent(this, el));
		}, this);
		[this.element, this.layout].each(function(el) {
			el.addEvents({
				'click': function(e) { new Event(e).stop(); },
				'keyup': function(e) {
					e = new Event(e);
					if(e.key == 'esc' && this.visible) this.hide(this.layout);
				}.bind(this)
			}, this);
		}, this);
		
		lim = {
			x: [0 - curW, (this.layout.overlay.width - curW)],
			y: [0 - curH, (this.layout.overlay.height - curH)]
		}

		this.layout.drag = new Drag.Base(this.layout.cursor, {
			limit: lim,
			onStart: this.overlayDrag.bind(this),
			onDrag: this.overlayDrag.bind(this),
			snap: 0
		});	
		
		this.layout.overlay2.addEvent('mousedown', function(e){
			e = new Event(e);
			this.layout.cursor.setStyles({
				'top': e.page.y - this.layout.overlay.getTop() - curH,
				'left': e.page.x - this.layout.overlay.getLeft() - curW
			});
			this.layout.drag.start(e);
		}.bind(this));
		
		this.okButton.addEvent('click', function() {
			if(this.currentColor == this.options.startColor) {
				this.hide();
				this.fireEvent('onComplete', [this.sets, this]);
			}
			else {
				this.backupColor = this.currentColor;
				this.layout.backup.setStyle('background-color', this.backupColor.rgbToHex());
				this.hide();
				this.fireEvent('onComplete', [this.sets, this]);
			}
		}.bind(this));
	},
	
	overlayDrag: function() {
		var curH = this.snippet('curSize', 'int').h;
		var curW = this.snippet('curSize', 'int').w;
		this.pickerPos.x = this.snippet('curPos').l + curW;
		this.pickerPos.y = this.snippet('curPos').t + curH;
		
		this.setMooRainbow(this.parseColors(this.pickerPos.x, this.pickerPos.y, this.sliderPos), 'hsb');
		this.fireEvent('onChange', [this.sets, this]);
	},
	
	sliderEvents: function() {
		var arwH = this.snippet('arrSize', 'int'), lim;

		lim = [0 + this.snippet('slider') - arwH, this.layout.slider.height - arwH + this.snippet('slider')];
		this.layout.sliderDrag = new Drag.Base(this.layout.arrows, {
			limit: {y: lim},
			modifiers: {x: false},
			onStart: this.sliderDrag.bind(this),
			onDrag: this.sliderDrag.bind(this),
			snap: 0
		});	
	
		this.layout.slider.addEvent('mousedown', function(e){
			e = new Event(e);

			this.layout.arrows.setStyle(
				'top', e.page.y - this.layout.slider.getTop() + this.snippet('slider') - arwH
			);
			this.layout.sliderDrag.start(e);
		}.bind(this));
	},

	sliderDrag: function() {
		var arwH = this.snippet('arrSize', 'int'), hue;
		
		this.sliderPos = this.snippet('arrPos') - arwH;
		this.setMooRainbow(this.parseColors(this.pickerPos.x, this.pickerPos.y, this.sliderPos), 'hsb');
		hue = [this.sets.hsb[0], 100, 100].hsbToRgb().rgbToHex();
		this.layout.overlay.setStyle('background-color', hue);
		this.fireEvent('onChange', [this.sets, this]);
	},
	
	backupEvent: function() {
		this.layout.backup.addEvent('click', function() {
			this.manualSet(this.backupColor);
			this.fireEvent('onChange', [this.sets, this]);
		}.bind(this));
	},
	
	wheelEvents: function() {
		var arrColors = this.arrRGB.copy().extend(this.arrHSB);

		arrColors.each(function(el) {
			el.addEvents({
				'mousewheel': this.eventKeys.bindWithEvent(this, el),
				'keydown': this.eventKeys.bindWithEvent(this, el)
			});
		}, this);
		
		[this.layout.arrows, this.layout.slider].each(function(el) {
			el.addEvents({
				'mousewheel': this.eventKeys.bindWithEvent(this, [this.arrHSB[0], 'slider']),
				'keydown': this.eventKeys.bindWithEvent(this, [this.arrHSB[0], 'slider'])
			});
		}, this);
	},
	
	eventKeys: function(e, el, id) {
		//  var m = (el.id).match(new Regexp(prefix + '(r)?(g)?(b)?Input')).splice(1, 3);
		//  var pass = [m[0] || rgb[0], m[1] || rgb[1], m[2] || rgb[2]];

		var wheel, type;		
		id = (!id) ? el.id : this.arrHSB[0];

		if (e.type == 'keydown') {
			if (e.key == 'up') wheel = 1;
			else if (e.key == 'down') wheel = -1;
			else return;
		} else if (e.type == Element.Events.mousewheel.type) wheel = e.wheel > 0 ? 1 : -1

		if (this.arrRGB.test(el)) type = 'rgb'
		else if (this.arrHSB.test(el)) type = 'hsb'
		else type = 'hsb'

		if (type == 'rgb') {
			var rgb = this.sets.rgb, hsb = this.sets.hsb, prefix = this.options.prefix, pass;
			var value = el.value.toInt() + wheel;
			value = (value > 255) ? 255 : (value < 0) ? 0 : value;

			switch(el.className) {
				case prefix + 'rInput': pass = [value, rgb[1], rgb[2]];	break;
				case prefix + 'gInput': pass = [rgb[0], value, rgb[2]];	break;
				case prefix + 'bInput':	pass = [rgb[0], rgb[1], value];	break;
				default : pass = rgb;
			}
			this.manualSet(pass);
			this.fireEvent('onChange', [this.sets, this]);
		} else {
			var rgb = this.sets.rgb, hsb = this.sets.hsb, prefix = this.options.prefix, pass;
			var value = el.value.toInt() + wheel;

			if (el.className.test(/(HueInput)/)) value = (value > 359) ? 0 : (value < 0) ? 0 : value;
			else value = (value > 100) ? 100 : (value < 0) ? 0 : value;

			switch(el.className) {
				case prefix + 'HueInput': pass = [value, hsb[1], hsb[2]]; break;
				case prefix + 'SatuInput': pass = [hsb[0], value, hsb[2]]; break;
				case prefix + 'BrighInput':	pass = [hsb[0], hsb[1], value]; break;
				default : pass = hsb;
			}
			this.manualSet(pass, 'hsb');
			this.fireEvent('onChange', [this.sets, this]);
		}
		e.stop();
	},
	
	eventKeydown: function(e, el) {
		var n = e.code, k = e.key;

		if 	((!el.className.test(/hexInput/) && !(n >= 48 && n <= 57)) &&
			(k!='backspace' && k!='tab' && k !='delete' && k!='left' && k!='right'))
		e.stop();
	},
	
	eventKeyup: function(e, el) {
		var n = e.code, k = e.key, pass, prefix, chr = el.value.charAt(0);

		if (!$chk(el.value)) return
		if (el.className.test(/hexInput/)) {
			if (chr != "#" && el.value.length != 6) return;
			if (chr == '#' && el.value.length != 7) return
		} else {
			if (!(n >= 48 && n <= 57)	 && (!['backspace', 'tab', 'delete', 'left', 'right'].test(k)) && el.value.length > 3) return
		}
		
		prefix = this.options.prefix;

		if (el.className.test(/(rInput|gInput|bInput)/)) {
			if (el.value  < 0 || el.value > 255) return;
			switch(el.className){
				case prefix + 'rInput': pass = [el.value, this.sets.rgb[1], this.sets.rgb[2]]; break;
				case prefix + 'gInput': pass = [this.sets.rgb[0], el.value, this.sets.rgb[2]]; break;
				case prefix + 'bInput': pass = [this.sets.rgb[0], this.sets.rgb[1], el.value]; break;
				default : pass = this.sets.rgb;
			}
			this.manualSet(pass);
			this.fireEvent('onChange', [this.sets, this]);
		}
		else if (!el.className.test(/hexInput/)) {
			if (el.className.test(/HueInput/) && el.value  < 0 || el.value > 360) return;
			else if (el.className.test(/HueInput/) && el.value == 360) el.value = 0;
			else if (el.className.test(/(SatuInput|BrighInput)/) && el.value  < 0 || el.value > 100) return;
			switch(el.className){
				case prefix + 'HueInput': pass = [el.value, this.sets.hsb[1], this.sets.hsb[2]]; break;
				case prefix + 'SatuInput': pass = [this.sets.hsb[0], el.value, this.sets.hsb[2]]; break;
				case prefix + 'BrighInput': pass = [this.sets.hsb[0], this.sets.hsb[1], el.value]; break;
				default : pass = this.sets.hsb;
			}
			this.manualSet(pass, 'hsb');
			this.fireEvent('onChange', [this.sets, this]);
		} else {
			pass = el.value.hexToRgb(true);
			if (isNaN(pass[0])||isNaN(pass[1])||isNaN(pass[2])) return

			if ($chk(pass)) {
				this.manualSet(pass);
				this.fireEvent('onChange', [this.sets, this]);
			}
		}
			
	},
			
	doLayout: function() {
		var id = this.options.id, prefix = this.options.prefix;
		var idPrefix = id + ' .' + prefix;

		this.layout = new Element('div', {
			'styles': {'display': 'block', 'position': 'absolute'},
			'id': id
		}).inject(document.body);

		// Ajout FSB2
		this.fireEvent('onLayout', [this.sets, this]);

		var box = new Element('div', {
			'styles':  {'position': 'relative'},
			'class': prefix + 'box'
		}).inject(this.layout);
			
		var div = new Element('div', {
			'styles': {'position': 'absolute', 'overflow': 'hidden'},
			'class': prefix + 'overlayBox'
		}).inject(box);
		
		var ar = new Element('div', {
			'styles': {'position': 'absolute', 'zIndex': 1},
			'class': prefix + 'arrows'
		}).inject(box);
		ar.width = ar.getStyle('width').toInt();
		ar.height = ar.getStyle('height').toInt();
		
		var ov = new Element('img', {
			'styles': {'background-color': '#fff', 'position': 'relative', 'zIndex': 2},
			'src': this.options.imgPath + 'moor_woverlay.png',
			'class': prefix + 'overlay'
		}).inject(div);
		
		var ov2 = new Element('img', {
			'styles': {'position': 'absolute', 'top': 0, 'left': 0, 'zIndex': 2},
			'src': this.options.imgPath + 'moor_boverlay.png',
			'class': prefix + 'overlay'
		}).inject(div);
		
		if (window.ie6) {
			div.setStyle('overflow', '');
			var src = ov.src;
			ov.src = this.options.imgPath + 'blank.gif';
			ov.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + src + "', sizingMethod='scale')";
			src = ov2.src;
			ov2.src = this.options.imgPath + 'blank.gif';
			ov2.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + src + "', sizingMethod='scale')";
		}
		ov.width = ov2.width = div.getStyle('width').toInt();
		ov.height = ov2.height = div.getStyle('height').toInt();

		var cr = new Element('div', {
			'styles': {'overflow': 'hidden', 'position': 'absolute', 'zIndex': 2},
			'class': prefix + 'cursor'	
		}).inject(div);
		cr.width = cr.getStyle('width').toInt();
		cr.height = cr.getStyle('height').toInt();
		
		var sl = new Element('img', {
			'styles': {'position': 'absolute', 'z-index': 2},
			'src': this.options.imgPath + 'moor_slider.png',
			'class': prefix + 'slider'
		}).inject(box);
		this.layout.slider = $E('#' + idPrefix + 'slider');
		sl.width = sl.getStyle('width').toInt();
		sl.height = sl.getStyle('height').toInt();

		new Element('div', {
			'styles': {'position': 'absolute'},
			'class': prefix + 'colorBox'
		}).inject(box);

		new Element('div', {
			'styles': {'zIndex': 2, 'position': 'absolute'},
			'class': prefix + 'chooseColor'
		}).inject(box);
			
		this.layout.backup = new Element('div', {
			'styles': {'zIndex': 2, 'position': 'absolute', 'cursor': 'pointer'},
			'class': prefix + 'currentColor'
		}).inject(box);
		
		var R = new Element('label').inject(box).setStyle('position', 'absolute');
		var G = R.clone().inject(box).addClass(prefix + 'gLabel').appendText('G: ');
		var B = R.clone().inject(box).addClass(prefix + 'bLabel').appendText('B: ');
		R.appendText('R: ').addClass(prefix + 'rLabel');
		
		var inputR = new Element('input');
		var inputG = inputR.clone().inject(G).addClass(prefix + 'gInput');
		var inputB = inputR.clone().inject(B).addClass(prefix + 'bInput');
		inputR.inject(R).addClass(prefix + 'rInput');
		
		var HU = new Element('label').inject(box).setStyle('position', 'absolute');
		var SA = HU.clone().inject(box).addClass(prefix + 'SatuLabel').appendText('S: ');
		var BR = HU.clone().inject(box).addClass(prefix + 'BrighLabel').appendText('B: ');
		HU.appendText('H: ').addClass(prefix + 'HueLabel');

		var inputHU = new Element('input');
		var inputSA = inputHU.clone().inject(SA).addClass(prefix + 'SatuInput');
		var inputBR = inputHU.clone().inject(BR).addClass(prefix + 'BrighInput');
		inputHU.inject(HU).addClass(prefix + 'HueInput');
		SA.appendText(' %'); BR.appendText(' %');
		new Element('span', {'styles': {'position': 'absolute'}, 'class': prefix + 'ballino'}).setHTML(" &deg;").injectAfter(HU);

		var hex = new Element('label').inject(box).setStyle('position', 'absolute').addClass(prefix + 'hexLabel').appendText('').adopt(new Element('input').addClass(prefix + 'hexInput'));
		
		var ok = new Element('input', {
			'styles': {'position': 'absolute'},
			'type': 'button',
			'value': 'Select',
			'class': prefix + 'okButton'
		}).inject(box);
		
		this.rePosition();

		var overlays = $$('#' + idPrefix + 'overlay');
		this.layout.overlay = overlays[0];
		this.layout.overlay2 = overlays[1];
		this.layout.cursor = $E('#' + idPrefix + 'cursor');
		this.layout.arrows = $E('#' + idPrefix + 'arrows');
		this.chooseColor = $E('#' + idPrefix + 'chooseColor');
		this.layout.backup = $E('#' + idPrefix + 'currentColor');
		this.RedInput = $E('#' + idPrefix + 'rInput');
		this.GreenInput = $E('#' + idPrefix + 'gInput');
		this.BlueInput = $E('#' + idPrefix + 'bInput');
		this.HueInput = $E('#' + idPrefix + 'HueInput');
		this.SatuInput = $E('#' + idPrefix + 'SatuInput');
		this.BrighInput = $E('#' + idPrefix + 'BrighInput');
		this.hexInput = $E('#' + idPrefix + 'hexInput');

		this.arrRGB = [this.RedInput, this.GreenInput, this.BlueInput];
		this.arrHSB = [this.HueInput, this.SatuInput, this.BrighInput];
		this.okButton = $E('#' + idPrefix + 'okButton');
		
		if (!window.khtml) this.hide();
	},
	rePosition: function() {
		var coords = this.element.getCoordinates();
		this.layout.setStyles({
			'left': coords.left,
			'top': coords.top + coords.height + 1
		});
	},
	
	snippet: function(mode, type) {
		var size; type = (type) ? type : 'none';

		switch(mode) {
			case 'arrPos':
				var t = this.layout.arrows.getStyle('top').toInt();
				size = t;
				break;
			case 'arrSize': 
				var h = this.layout.arrows.height;
				h = (type == 'int') ? (h/2).toInt() : h;
				size = h;
				break;		
			case 'curPos':
				var l = this.layout.cursor.getStyle('left').toInt();
				var t = this.layout.cursor.getStyle('top').toInt();
				size = {'l': l, 't': t};
				break;
			case 'slider':
				var t = this.layout.slider.getStyle('marginTop').toInt();
				size = t;
				break;
			default :
				var h = this.layout.cursor.height;
				var w = this.layout.cursor.width;
				h = (type == 'int') ? (h/2).toInt() : h;
				w = (type == 'int') ? (w/2).toInt() : w;
				size = {w: w, h: h};
		};
		return size;
	}
});

MooRainbow.implement(new Options);
MooRainbow.implement(new Events);