/*
 * Croissant Web Framework
 * 
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 * 
 */

$.croissant = {};
$.lang = {};

;(function($) {
	$.extend($.croissant, {
		absolutePosition : function(el) {
			var sLeft = 0, sTop = 0;
			var isDiv = /^div$/i.test(el.tagName);
			if (isDiv && el.scrollLeft) {
				sLeft = el.scrollLeft;
			}
			if (isDiv && el.scrollTop) {
				sTop = el.scrollTop;
			}
			var r = {
				x : el.offsetLeft - sLeft,
				y : el.offsetTop - sTop
			};
			if (el.offsetParent) {
				var tmp = $.croissant.absolutePosition(el.offsetParent);
				r.x += tmp.x;
				r.y += tmp.y;
			}
			return r;
		},
		dimensions : function(el) {
			return {
				width : el.offsetWidth,
				height : el.offsetHeight
			};
		},
		mousePosition : function(e) {
			return {
				x : e.clientX + document.documentElement.scrollLeft,
				y : e.clientY + document.documentElement.scrollTop
			};
		},
		parseJson : function(data) {
			if ((data.substring(0, 1) != '{') && (data.substring(0, 1) != '[')) {
				return {
					status : 0,
					data : data.length ? data : 'Unspecified error'
				};
			}
			return eval('(' + data + ');');
		},
		encodeURIComponent : function(item, uri) {
			uri = uri || location.href;
			item = encodeURIComponent(item).replace(/%2F/g, '/');
			return (uri.indexOf('?q=') != -1) ? item : item.replace(/%26/g, '%2526').replace(/%23/g, '%2523').replace(/\/\//g, '/%252F');
		},

		URLEncode : function(clearString) {
			var output = '';
			var x = 0;
			clearString = clearString.toString();
			var regex = /(^[a-zA-Z0-9_.]*)/;
			while (x < clearString.length) {
				var match = regex.exec(clearString.substr(x));
				if (match != null && match.length > 1 && match[1] != '') {
					output += match[1];
					x += match[1].length;
				} else {
					if (clearString[x] == ' ')
						output += '+';
					else {
						var charCode = clearString.charCodeAt(x);
						var hexVal = charCode.toString(16);
						output += '%' + (hexVal.length < 2 ? '0' : '') + hexVal.toUpperCase();
					}
					x++;
				}
			}
			return output;
		},
		URLDecode: function(s) {
			var o = s;
			var binVal, t;
			var r = /(%[^%]{2})/;
			while ((m = r.exec(o)) != null && m.length > 1 && m[1] != '') {
				b = parseInt(m[1].substr(1), 16);
				t = String.fromCharCode(b);
				o = o.replace(m[1], t);
			}
			return o;
		},
		isEmail : function(str) {
			if ($.croissantConfig.emailRegex.test(str)) {
				return true;
			} else {
				return false;
			}
		}
	});
})(jQuery);

;(function($) {
	$.croissantConfig = $.croissantConfig || {};
	$.extend($.croissantConfig, {
		emailRegex  	: /^([\'A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/,
		numericRegex	: /^-{0,1}\d*\.{0,1}\d+$/,
		progPoller		: 60000
	});
})(jQuery);
