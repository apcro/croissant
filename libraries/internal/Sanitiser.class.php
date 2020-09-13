<?php
/**
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

/**
 * sanitiser.class.php
 *
 * This implements basic sanitisation as one class.
 *
 * @author Cat Le-Huy
 *
 */

define("HTML", "HTML");

class Sanitiser extends Core {

	static $core;
	public static function Initialise() {
		if (!isset(self::$core)) {
			self::$core = parent::Initialise();
		}
		return self::$core;
	}

	static private $_waste = array();

	/* 	For security purposes, define a list of elements where we allow limited html
	All input post or get data has html aggressively stripped prior to passing to
	any controller through the application
	*/
	private static $_html_allowed = array('comment', 'txt_aboutme', 'description', 'preliminary', 'reflective');

	private static $never_allowed_str = array('document.cookie', "document['cookie']", 'document[cookie]', 'document.write', '.parentNode', '.innerHTML', 'window.location', '-moz-binding', '<!--', '-->', '<![CDATA[', 'alert(', "alert']", 'alert]'); //, 'cookie'); - we need to allow these normal english words
	private static $never_allowed_regex = array("javascript\s*:" 									=> '[removed]',
			"expression\s*(\(|&\#40;)" 						=> '[removed]', // CSS and IE
			"vbscript\s*:" 									=> '[removed]', // IE, surprise!
			"Redirect\s+302" 									=> '[removed]',
			"from[C|c]har[C|c]ode\s*" 							=> '[removed]',
			"[L|l][O|o][C|c][A|a][T|t][I|i][O|o][N|n]\s*\="	=> '[removed]', // prevent location= attackes in the REQUEST string
			"[C|c][O|o][N|n][C|c][A|a][T|t]\s*\("				=> '[removed]', // remove any concat commands in the REQUEST string
	);
	private static $js_on_attributes = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavaible', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragdrop', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterupdate', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmoveout', 'onmouseover', 'onmouseout', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');

	private static $xss_hash;

	/**
	 * Ingests array one at a time. Useful if we have an ajaxy thing that adds fields as the user progresses through the form
	 *
	 * @param $toxic
	 * @param $overwrite
	 */
	static public function Slurp($toxic, $overwrite = false) {
		$temp = array();
		if(is_array($toxic )) {
			if($overwrite || empty(self::$_waste )) {
				unset(self::$_waste);
				self::$_waste = $toxic;
				return true;
			} else {
				if(isset(self::$_waste['content'])) {
					$temp = self::$_waste;
					unset(self::$_waste);
					self::$_waste[] = $temp;
				}
				if(isset($toxic ['content'])) {
					self::$_waste[] = $toxic;
				} else {
					foreach($toxic as $dirt) {
						self::$_waste[] = $dirt;
					}
				}
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Dirt
	 *
	 * @return returns the filth
	 */
	static public function Dirt() {
		return self::$_waste;
	}

	/**
	 * WashMe
	 * Call to do the cleaning on the content
	 *
	 * @return waste
	 */
	static public function WashMe() {
		foreach(self::$_waste as $item => $value) {
			self::$_waste[$item]['clean'] = self::_EngageCleaners(self::$_waste[$item]['content'], self::$_waste[$item]['filter']);
		}
		return self::$_waste;
	}

	/**
	 * Engage Cleaners
	 * process stacked filters if necessary otehrwise clean and spit out
	 *
	 * @param $content
	 * @param $filters
	 * @return content
	 */
	static private function _EngageCleaners($content, $filters) {
		$chain = array();
		$chain = explode(',', $filters);

		foreach($chain as $cleaner) {
			$content = self::_SpinCycle($content, $cleaner);
		}
		return $content;
	}

	/**
	 * SpinCycle
	 * Does the actual cleaning
	 *
	 * @param $content
	 * @param $filter
	 */
	static public function _SpinCycle($content, $filter) {
		call_user_func_array(array(&$this, $filter), array("content" => $content));
		return $content;
	}

	/**
	 *
	 *  Borrowed Liberally ^H^H^H^H^H^H^H^H^H^H^H^H  Copied directly from CI 1.7.1
	 *
	 * XSS Clean
	 *
	 * Sanitizes data so that Cross Site Scripting Hacks can be
	 * prevented.  This function does a fair amount of work but
	 * it is extremely thorough, designed to prevent even the
	 * most obscure XSS attempts.  Nothing is ever 100% foolproof,
	 * of course, but I haven't been able to get anything passed
	 * the filter.
	 *
	 * Note: This function should only be used to deal with data
	 * upon submission.  It's not something that should
	 * be used for general runtime processing.
	 *
	 * This function was based in part on some code and ideas I
	 * got from Bitflux: http://blog.bitflux.ch/wiki/XSS_Prevention
	 *
	 * To help develop this script I used this great list of
	 * vulnerabilities along with a few other hacks I've
	 * harvested from examining vulnerabilities in other programs:
	 * http://ha.ckers.org/xss.html
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	static private function xss_clean($str, $is_image = FALSE, $key = null) {
		/*
		* Is $str an array?
		*/
	    if (is_array($str)) {
	        foreach($str as $key => $value) {
	            $str[$key] = self::xss_clean($value, false, $key);
	        }
	        return $str;
	    }

		// Strip out the obvious stuff first
		$search = array('@<script[^>]*?>.*?</script>@si',	// Strip out javascript
				'@<style[^>]*?>.*?</style>@siU',	// Strip style tags properly
				'@<![\s\S]*?–[ \t\n\r]*>@'			// Strip multi-line comments
		);
		$str = preg_replace($search, '', $str);

		// bit hacky - strip (go crazy, actually) inline style attributes bug #1589
		// strip everything but alpha numeric, hyphens & spaces
		if(strstr($str, 'style=')) {
			$str = preg_replace('/[^a-zA-Z 0-9-\s]/', '', $str); // don't screw with me. Grrr!
		}

		if(($key != null) && (in_array($key, self::$_html_allowed))) {
			$str = strip_tags($str, '<a><em><strong><cite><code><ul><ol><li><dl><dt><dd><p>');
		} else {
			$str = strip_tags($str);
		}

		/*
		* Remove Invisible Characters
		*/
		$str = self::_remove_invisible_characters($str);

		$str = preg_replace('|\&([a-z\_0-9]+)\=([a-z\_0-9]+)|i', self::xss_hash()."\\1=\\2", $str);

		/*
		* Validate standard character entities
		*
		* Add a semicolon if missing.  We do this to enable
		* the conversion of entities to ASCII later.
		*
		*/
		$str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);

		/*
		* Validate UTF16 two byte encoding(x00)
		*
		* Just as above, adds a semicolon if missing.
		*
		*/
		$str = preg_replace('#(&\#x?)([0-9A-F]+);?#i', "\\1\\2;", $str);

		/*
		* Un-Protect GET variables in URLs
		*/
		$str = str_replace(self::xss_hash(), '&', $str);

		/*
		* URL Decode
		*
		* Just in case stuff like this is submitted:
		*
		* <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		*
		* Note: Use rawurldecode() so it does not remove plus signs
		*
		*/
		$str = rawurldecode($str);

		/*
		* Convert character entities to ASCII
		*
		* This permits our tests below to work reliably.
		* We only convert entities that are within tags since
		* these are the ones that will pose security problems.
		*
		*/

		$str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array('self', '_convert_attribute' ), $str);

		$str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", array('self', '_html_entity_decode_callback' ), $str);

		/*
		* Remove Invisible Characters Again!
		*/
		$str = self::_remove_invisible_characters($str);

		/*
		* Convert all tabs to spaces
		*
		* This prevents strings like this: ja	vascript
		* NOTE: we deal with spaces between characters later.
		* NOTE: preg_replace was found to be amazingly slow here on large blocks of data,
		* so we use str_replace.
		*
		*/
		if(strpos($str, "\t" ) !== FALSE) {
			$str = str_replace("\t", ' ', $str);
		}

		/*
		* Capture converted string for later comparison
		*/
		$converted_string = $str;

		/*
		* Not Allowed Under Any Conditions
		*/
		$found = false;
		foreach(self::$never_allowed_str as $key) {
			if(strstr($str, $key)) {
				$str = str_replace($key, '', $str);
				$found = true;
			}
		}
		// we've detected suspicious data, so send the boys 'round
		if($found) {
			$str = preg_replace("/[^a-zA-Z0-9\\040\\.\\-\\_\\/]/i", '', $str);
		}
		foreach(self::$never_allowed_regex as $key => $val) {
			$str = preg_replace("#".$key."#i", $val, $str);
		}

		/*
		* Makes PHP tags safe
		*
		*  Note: XML tags are inadvertently replaced too:
		*
		*	<?xml
		*
		* But it doesn't seem to pose a problem.
		*
		*/
		if($is_image === TRUE) {
			// Images have a tendency to have the PHP short opening and closing tags every so often
			// so we skip those and only do the long opening tags.
			$str = str_replace(array('<?php', '<?PHP' ), array('&lt;?php', '&lt;?PHP' ), $str);
		} else {
			$str = str_replace(array('<?php', '<?PHP', '<?', '?' . '>' ), array('&lt;?php', '&lt;?PHP', '&lt;?', '?&gt;' ), $str);
		}

		/*
		* Compact any exploded words
		*
		* This corrects words like:  j a v a s c r i p t
		* These words are compacted back to their correct state.
		*
		*/
		$words = array('javascript', 'expression', 'vbscript', 'script', 'applet', 'alert', 'document', 'write', 'cookie', 'window');
		foreach($words as $word) {
			$temp = '';
			for($i = 0, $wordlen = strlen($word); $i < $wordlen; $i ++) {
				$temp .= substr($word, $i, 1 ) . "\s*";
			}
			// We only want to do this when it is followed by a non-word character
			// That way valid stuff like "dealer to" does not become "dealerto"
			$str = preg_replace_callback('#(' . substr($temp, 0, - 3 ) . ')(\W)#is', array('self', '_compact_exploded_words' ), $str);
		}

		/*
		* Remove disallowed Javascript in links or img tags
		* We used to do some version comparisons and use of stripos for PHP5, but it is dog slow compared
		* to these simplified non-capturing preg_match(), especially if the pattern exists in the string
		*/
		do {
			$original = $str;
			if(preg_match("/<a/i", $str )) {
				$str = preg_replace_callback("#<a\s+([^>]*?)(>|$)#si", array('self', '_js_link_removal' ), $str);
			}
			if(preg_match("/<img/i", $str )) {
				$str = preg_replace_callback("#<img\s+([^>]*?)(\s?/?>|$)#si", array('self', '_js_img_removal' ), $str);
			}
			if(preg_match("/script/i", $str ) or preg_match("/xss/i", $str )) {
				$str = preg_replace("#<(/*)(script|xss)(.*?)\>#si", '[removed]', $str);
			}
		} while($original != $str);
		unset($original);

		/*
		* Remove JavaScript Event Handlers
		*
		* Note: This code is a little blunt.  It removes
		* the event handler and anything up to the closing >,
		* but it's unlikely to be a problem.
		*
		*/
		$event_handlers = array('[^a-z_\-]on\w*', 'xmlns');

		if($is_image === TRUE) {
			/*
			* Adobe Photoshop puts XML metadata into JFIF images, including namespacing,
			* so we have to allow this for images. -Paul
			*/
			unset($event_handlers [array_search('xmlns', $event_handlers)]);
		}

		$str = preg_replace("#<([^><]+?)(".implode('|', $event_handlers).")(\s*=\s*[^><]*)([><]*)#i", "<\\1\\4", $str);

		/*
		* Sanitize naughty HTML elements
		*
		* If a tag containing any of the words in the list
		* below is found, the tag gets converted to entities.
		*
		* So this: <blink>
		* Becomes: &lt;blink&gt;
		*
		*/
		$naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
		$str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array('self', '_sanitize_naughty_html' ), $str);

		/*
		* Sanitize naughty scripting elements
		*
		* Similar to above, only instead of looking for
		* tags it looks for PHP and JavaScript commands
		* that are disallowed.  Rather than removing the
		* code, it simply converts the parenthesis to entities
		* rendering the code un-executable.
		*
		* For example:	eval('some code')
		* Becomes:		eval&#40;'some code'&#41;
		*
		*/
		$str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#40;\\3&#41;", $str);

		// realign javascript href to onclick
		$str = preg_replace("/href=(['\"]).*?javascript:(.*)?\\1/i", "onclick=' $2 '", $str);

		//remove javascript from tags
		while(preg_match("/<(.*)?javascript.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", $str)) {
			$str = preg_replace("/<(.*)?javascript.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", "<$1$3$4$5>", $str);
		}
		// dump expressions from contibuted content
		$str = preg_replace("/:expression\(.*?((?>[^(.*?)]+)|(?R)).*?\)\)/i", "", $str);

		while(preg_match("/<(.*)?:expr.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", $str)) {
			$str = preg_replace("/<(.*)?:expr.*?\(.*?((?>[^()]+)|(?R)).*?\)?\)(.*)?>/i", "<$1$3$4$5>", $str);
		}
		// remove all on* events
		while(preg_match("/<(.*)?\s?on.+?=?\s?.+?(['\"]).*?\\2\s?(.*)?>/i", $str)) {
			$str = preg_replace("/<(.*)?\s?on.+?=?\s?.+?(['\"]).*?\\2\s?(.*)?>/i", "<$1$3>", $str);
		}

		/* at this point, if we have any 'on' type attributes, they are not part of a complete tag
		   brute force required: we'll simply strip them */
		foreach(self::$js_on_attributes as $attr) {
			$str = str_replace($attr, '', $str);
		}
		/*
		* Final clean up
		*
		* This adds a bit of extra precaution in case
		* something got through the above filters
		*
		*/
		foreach(self::$never_allowed_str as $key) {
			$str = str_replace($key, '', $str);
		}
		foreach(self::$never_allowed_regex as $key => $val ) {
			$str = preg_replace("#".$key."#i", $val, $str);
		}
		/*
		*  Images are Handled in a Special Way
		*  - Essentially, we want to know that after all of the character conversion is done whether
		*  any unwanted, likely XSS, code was found.  If not, we return TRUE, as the image is clean.
		*  However, if the string post-conversion does not matched the string post-removal of XSS,
		*  then it fails, as there was unwanted XSS code found and removed/changed during processing.
		*/
		if($is_image === TRUE) {
			if($str == $converted_string) {
				return TRUE;
			} else {
				return FALSE;
			}
		}
		//log_message('debug', "XSS Filtering completed");
		return $str;
	}

	/**
	 * Remove Invisible Characters
	 *
	 * This prevents sandwiching null characters
	 * between ascii characters, like Java\0script.
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	static function _remove_invisible_characters($str) {
		static $non_displayables;
		if(!isset($non_displayables)) {
			// every control character except newline(dec 10), carriage return(dec 13), and horizontal tab(dec 09),
			$non_displayables = array('/%0[0-8bcef]/', 		// url encoded 00-08, 11, 12, 14, 15
					'/%1[0-9a-f]/', 		// url encoded 16-31
					'/[\x00-\x08]/', 		// 00-08
					'/\x0b/', '/\x0c/', 	// 11, 12
					'/[\x0e-\x1f]/'); 	// 14-31
		}
		do {
			$cleaned = $str;
			$str = preg_replace($non_displayables, '', $str);
		} while($cleaned != $str);
		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Compact Exploded Words
	 *
	 * Callback function for xss_clean() to remove whitespace from
	 * things like j a v a s c r i p t
	 *
	 * @access	public
	 * @param	type
	 * @return	type
	 */
	static function _compact_exploded_words($matches) {
		return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
	}

	// --------------------------------------------------------------------

	/**
	 * Sanitize Naughty HTML
	 *
	 * Callback function for xss_clean() to remove naughty HTML elements
	 *
	 * @access	private
	 * @param	array
	 * @return	string
	 */
	static function _sanitize_naughty_html($matches) {
		// encode opening brace
		$str = '&lt;'.$matches[1].$matches[2].$matches[3];
		// encode captured opening or closing brace to prevent recursive vectors
		$str .= str_replace(array('>', '<'), '', $matches[4]);
		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * JS Link Removal
	 *
	 * Callback function for xss_clean() to sanitize links
	 * This limits the PCRE backtracks, making it more performance friendly
	 * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
	 * PHP 5.2+ on link-heavy strings
	 *
	 * @access	private
	 * @param	array
	 * @return	string
	 */
	static function _js_link_removal($match) {
		$attributes = self::_filter_attributes(str_replace(array('<', '>'), '', $match[1]));
		return str_replace($match[1], preg_replace("#href=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes), $match[0]);
	}

	/**
	 * JS Image Removal
	 *
	 * Callback function for xss_clean() to sanitize image tags
	 * This limits the PCRE backtracks, making it more performance friendly
	 * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
	 * PHP 5.2+ on image tag heavy strings
	 *
	 * @access	private
	 * @param	array
	 * @return	string
	 */
	static function _js_img_removal($match) {
		$attributes = self::_filter_attributes(str_replace(array('<', '>'), '', $match[1]));
		return str_replace($match[1], preg_replace("#src=.*?(alert\(|alert&\#40;|javascript\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes), $match[0]);
	}

	// --------------------------------------------------------------------

	/**
	 * Attribute Conversion
	 *
	 * Used as a callback for XSS Clean
	 *
	 * @access	public
	 * @param	array
	 * @return	string
	 */
	static function _convert_attribute($match) {
		return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
	}

	// --------------------------------------------------------------------

	/**
	 * HTML Entity Decode Callback
	 *
	 * Used as a callback for XSS Clean
	 *
	 * @access	public
	 * @param	array
	 * @return	string
	 */
	static function _html_entity_decode_callback($match) {
		return html_entity_decode($match[0], ENT_COMPAT, 'UTF-8');
	}

	/**
	 * Filter Attributes
	 *
	 * Filters tag attributes for consistency and safety
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	static function _filter_attributes($str) {
		$out = '';
		if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches)) {
			foreach ($matches[0] as $match) {
				$out .= preg_replace("#/\*.*?\*/#s", '', $match);
			}
		}
		return $out;
	}

	/**
	 * Random Hash for protecting URLs
	 *
	 * @access	public
	 * @return	string
	 */
	static private function xss_hash() {
		if(self::$xss_hash == '') {
			if(phpversion() >= 4.2) {
				mt_srand();
			} else {
				mt_srand(hexdec(substr(md5(microtime()), - 8)) & 0x7fffffff);
			}
			self::$xss_hash = md5(time() + mt_rand(0, 1999999999));
		}
		return self::$xss_hash;
	}

	/**
	 *  ADD NEW FILTERS BELOW
	 *	public function FILTER_{NAME_NAME_NAME}($content) {
	 *		return $content;
	 *	}
	 */
	static public function FILTER_SAFE_URL($content) {
		// stub code to clean a provided URL
	}

	/**
	 * FILTER_HTML_ENTITIES
	 *
	 * @param $content
	 * @param html entities of the content
	 */
	static public function FILTER_HTML_ENTITIES($content) {
		return htmlentities($content);
	}

	/**
	 * FILTER_XSS_CLEAN
	 *
	 * @param $content
	 * @param xss clean of the content
	 */
	static public function FILTER_XSS_CLEAN($content) {
		return self::xss_clean($content);
	}

	/**
	 * FILTER_FILENAME_SECURITY
	 * Borrowed from CI 1.7.1 from Input.php
	 *
	 * @param $content
	 * @return strip slashes of the content
	 */
	static private function FILTER_FILENAME_SECURITY($content) {
		$bad = array("../", "./", "<!--", "-->", "<", ">", "'", '"', '&', '$', '#', '{', '}', '[', ']', '=', ';', '?', "%20", "%22", "%3c", // <
				"%253c", 	// <
				"%3e", 	// >
				"%0e", 	// >
				"%28", 	// (
				"%29", 	// )
				"%2528", 	// (
				"%26", 	// &
				"%24", 	// $
				"%3f", 	// ?
				"%3b", 	// ;
				"%3d"); 	// =
		return stripslashes(str_replace($bad, '', $content));
	}
}
