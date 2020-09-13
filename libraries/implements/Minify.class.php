<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */

/**
 *
 * Minify CSS & Javascript at runtime and cache the result for reuse
 *
 * Uses elements and code from http://code.google.com/p/minify/
 */
namespace Croissant;

class Minify extends Core {
	static $core;
	static $compressed;
	static $_inHack;

	const ORD_LF            = 10;
	const ORD_SPACE         = 32;
	const ACTION_KEEP_A     = 1;
	const ACTION_DELETE_A   = 2;
	const ACTION_DELETE_A_B = 3;

	static protected $a           = "\n";
	static protected $b           = '';
	static protected $input       = '';
	static protected $inputIndex  = 0;
	static protected $inputLength = 0;
	static protected $lookAhead   = null;
	static protected $output      = '';
	static protected $lastByteOut  = '';

	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();
		}
		return self::$core;
	}

	final static function Digest(array $files) {
		foreach ($files as $file) {
			$digest[] = array($file, get_svn_version(), Core::$core->_function);
		}
		return md5(serialize($digest));
	}

	final static function Cached($file) {
		return file_exists(CACHEPATH.'/'.$file);
	}

	final static function GetFile($files) {
		if (!is_object($files)) {
			return false;
		}
		$digest = self::Digest($files->value);

		if (self::Cached($digest)) {
			return $digest;
		}
		foreach($files->value as $file) {
			$filename = DOCROOT.$file;
			if (file_exists($filename)) {
				$fp = fopen($filename, 'r');
				$css = '';
				while (!feof($fp)) {
					$css .= fread($fp, (1*(1024*1024)));
				}
				fclose($fp);
				self::$compressed .= self::MinifyCSS($css);
			}
		}

		self::Cache($digest);
		return $digest;
	}

	final static function GetJSFile($files) {
		if (!is_object($files)) {
			return false;
		}
		$digest = self::Digest($files->value);

		if (self::Cached($digest)) {
			return $digest;
		}
		foreach($files->value as $file) {
			self::_jsClean();
			$filename = DOCROOT.$file;
			if (file_exists($filename)) {
				$fp = fopen($filename, 'r');
				$js = '';
				while (!feof($fp)) {
					$js .= fread($fp, (1*(1024*1024)));
				}
				fclose($fp);
				if (!strstr($filename, '.min')) {
					self::$input = $js;
					self::MinifyJS();
				} else {
					self::$output = $js;
				}
				self::$compressed .= self::$output;
			}
		}
		self::Cache($digest);
		return $digest;
	}

	/*
	 * Writes the content of self::$compressed to $cache_name
	 */
	final static function Cache($cache_name) {
		$fp = fopen(CACHE_PATH.'/'.$cache_name, 'w');
		fwrite($fp, self::$compressed);
		fclose($fp);
		return;
	}

	/**
	 * Minify a CSS string
	 *
	 * @param string $css
	 *
	 * @return string
	 */
	static protected function MinifyCSS($css) {
		$css = str_replace("\r\n", "\n", $css);

		// preserve empty comment after '>'
		// http://www.webdevout.net/css-hacks#in_css-selectors
		$css = preg_replace('@>/\\*\\s*\\*/@', '>/*keep*/', $css);

		// preserve empty comment between property and value
		// http://css-discuss.incutio.com/?page=BoxModelHack
		$css = preg_replace('@/\\*\\s*\\*/\\s*:@', '/*keep*/:', $css);
		$css = preg_replace('@:\\s*/\\*\\s*\\*/@', ':/*keep*/', $css);

		// apply callback to all valid comments (and strip out surrounding ws
		$css = preg_replace_callback('@\\s*/\\*([\\s\\S]*?)\\*/\\s*@'
			,array(self, '_commentCB'), $css);

		// remove ws around { } and last semicolon in declaration block
		$css = preg_replace('/\\s*{\\s*/', '{', $css);
		$css = preg_replace('/;?\\s*}\\s*/', '}', $css);

		// remove ws surrounding semicolons
		$css = preg_replace('/\\s*;\\s*/', ';', $css);

		// remove ws around urls
		$css = preg_replace('/
				url\\(		# url(
				\\s*
				([^\\)]+?)	# 1 = the URL (really just a bunch of non right parenthesis)
				\\s*
				\\)			# )
			/x', 'url($1)', $css);

		// remove ws between rules and colons
		$css = preg_replace('/
				\\s*
				([{;])				# 1 = beginning of block or rule separator
				\\s*
				([\\*_]?[\\w\\-]+)	# 2 = property (and maybe IE filter)
				\\s*
				:
				\\s*
				(\\b|[#\'"])		# 3 = first character of a value
			/x', '$1$2:$3', $css);

		// remove ws in selectors
		$css = preg_replace_callback('/
				(?:				# non-capture
					\\s*
					[^~>+,\\s]+	# selector part
					\\s*
					[,>+~]		# combinators
				)+
				\\s*
				[^~>+,\\s]+		# selector part
				{				# open declaration block
			/x'
			,array(self, '_selectorsCB'), $css);

		// minimize hex colors
		$css = preg_replace('/([^=])#([a-f\\d])\\2([a-f\\d])\\3([a-f\\d])\\4([\\s;\\}])/i'
			, '$1#$2$3$4$5', $css);

		// remove spaces between font families
		$css = preg_replace_callback('/font-family:([^;}]+)([;}])/'
			,array(self, '_fontFamilyCB'), $css);

		$css = preg_replace('/@import\\s+url/', '@import url', $css);

		// replace any ws involving newlines with a single newline
		$css = preg_replace('/[ \\t]*\\n+\\s*/', "\n", $css);

		// separate common descendent selectors w/ newlines (to limit line lengths)
		$css = preg_replace('/([\\w#\\.\\*]+)\\s+([\\w#\\.\\*]+){/', "$1\n$2{", $css);

		// Use newline after 1st numeric value (to limit line lengths).
		$css = preg_replace('/
			((?:padding|margin|border|outline):\\d+(?:px|em)?) # 1 = prop : 1st numeric value
			\\s+
			/x'
			,"$1\n", $css);

		// prevent triggering IE6 bug: http://www.crankygeek.com/ie6pebug/
		$css = preg_replace('/:first-l(etter|ine)\\{/', ':first-l$1 {', $css);

		return trim($css);
	}

	/**
	 * Replace what looks like a set of selectors
	 *
	 * @param array $m regex matches
	 *
	 * @return string
	 */
	static protected function _selectorsCB($m) {
		// remove ws around the combinators
		return preg_replace('/\\s*([,>+~])\\s*/', '$1', $m[0]);
	}

	/**
	 * Process a comment and return a replacement
	 *
	 * @param array $m regex matches
	 *
	 * @return string
	 */
	static protected function _commentCB($m) {
		$hasSurroundingWs = (trim($m[0]) !== $m[1]);
		$m = $m[1];
		// $m is the comment content w/o the surrounding tokens,
		// but the return value will replace the entire comment.
		if ($m === 'keep') {
			return '/**/';
		}
		if ($m === '" "') {
			// component of http://tantek.com/CSS/Examples/midpass.html
			return '/*" "*/';
		}
		if (preg_match('@";\\}\\s*\\}/\\*\\s+@', $m)) {
			// component of http://tantek.com/CSS/Examples/midpass.html
			return '/*";}}/* */';
		}
		if (self::$_inHack) {
			// inversion: feeding only to one browser
			if (preg_match('@
					^/			   # comment started like /*/
					\\s*
					(\\S[\\s\\S]+?)  # has at least some non-ws content
					\\s*
					/\\*			 # ends like /*/ or /**/
				@x', $m, $n)) {
				// end hack mode after this comment, but preserve the hack and comment content
				self::$_inHack = false;
				return "/*/{$n[1]}/**/";
			}
		}
		if (substr($m, -1) === '\\') { // comment ends like \*/
			// begin hack mode and preserve hack
			self::$_inHack = true;
			return '/*\\*/';
		}
		if ($m !== '' && $m[0] === '/') { // comment looks like /*/ foo */
			// begin hack mode and preserve hack
			self::$_inHack = true;
			return '/*/*/';
		}
		if (self::$_inHack) {
			// a regular comment ends hack mode but should be preserved
			self::$_inHack = false;
			return '/**/';
		}
		// Issue 107: if there's any surrounding whitespace, it may be important, so
		// replace the comment with a single space
		return $hasSurroundingWs // remove all other comments
			? ' '
			: '';
	}

	/**
	 * Process a font-family listing and return a replacement
	 *
	 * @param array $m regex matches
	 *
	 * @return string
	 */
	static protected function _fontFamilyCB($m) {
		$m[1] = preg_replace('/
				\\s*
				(
					"[^"]+"	  # 1 = family in double qutoes
					|\'[^\']+\'  # or 1 = family in single quotes
					|[\\w\\-]+   # or 1 = unquoted family
				)
				\\s*
			/x', '$1', $m[1]);
		return 'font-family:' . $m[1] . $m[2];
	}

	static protected function _jsCLean() {
		self::$a           = "\n";
		self::$b           = '';
		self::$input       = '';
		self::$inputIndex  = 0;
		self::$inputLength = 0;
		self::$lookAhead   = null;
		self::$output      = '';
		self::$lastByteOut  = '';
	}

	static public function MinifyJS() {
		if (self::$output !== '') {
			// min already run
			echo 'aborting!';
			return self::$output;
		}

		$mbIntEnc = null;
		if (function_exists('mb_strlen') && ((int)ini_get('mbstring.func_overload') & 2)) {
			$mbIntEnc = mb_internal_encoding();
			mb_internal_encoding('8bit');
		}
		self::$input = str_replace("\r\n", "\n", self::$input);
		self::$inputLength = strlen(self::$input);

		self::action(self::ACTION_DELETE_A_B);

		while (self::$a !== null) {
			// determine next command
			$command = self::ACTION_KEEP_A; // default
			if (self::$a === ' ') {
				if ((self::$lastByteOut === '+' || self::$lastByteOut === '-')
				&& (self::$b === self::$lastByteOut)) {
					// Don't delete this space. If we do, the addition/subtraction
					// could be parsed as a post-increment
				} elseif (! self::isAlphaNum(self::$b)) {
					$command = self::ACTION_DELETE_A;
				}
			} elseif (self::$a === "\n") {
				if (self::$b === ' ') {
					$command = self::ACTION_DELETE_A_B;
					// in case of mbstring.func_overload & 2, must check for null b,
					// otherwise mb_strpos will give WARNING
				} elseif (self::$b === null
				|| (false === strpos('{[(+-', self::$b)
				&& ! self::isAlphaNum(self::$b))) {
					$command = self::ACTION_DELETE_A;
				}
			} elseif (! self::isAlphaNum(self::$a)) {
				if (self::$b === ' '
				|| (self::$b === "\n"
				&& (false === strpos('}])+-"\'', self::$a)))) {
					$command = self::ACTION_DELETE_A_B;
				}
			}
			self::action($command);
		}
		self::$output = trim(self::$output);

		if ($mbIntEnc !== null) {
			mb_internal_encoding($mbIntEnc);
		}
		return self::$output;
	}

	/**
	 * ACTION_KEEP_A = Output A. Copy B to A. Get the next B.
	 * ACTION_DELETE_A = Copy B to A. Get the next B.
	 * ACTION_DELETE_A_B = Get the next B.
	 *
	 * @param int $command
	 * @throws JSMin_UnterminatedRegExpException|JSMin_UnterminatedStringException
	 */
	static protected function action($command) {
		if ($command === self::ACTION_DELETE_A_B
		&& self::$b === ' '
		&& (self::$a === '+' || self::$a === '-')) {
			// Note: we're at an addition/substraction operator; the inputIndex
			// will certainly be a valid index
			if (self::$input[self::$inputIndex] === self::$a) {
				// This is "+ +" or "- -". Don't delete the space.
				$command = self::ACTION_KEEP_A;
			}
		}
		switch ($command) {
			case self::ACTION_KEEP_A:
				self::$output .= self::$a;
				self::$lastByteOut = self::$a;

				// fallthrough
			case self::ACTION_DELETE_A:
				self::$a = self::$b;
				if (self::$a === "'" || self::$a === '"') {
					// string literal
					$str = self::$a; // in case needed for exception
					while (true) {
						self::$output .= self::$a;
						self::$lastByteOut = self::$a;

						self::$a       = self::get();
						if (self::$a === self::$b) {
							// end quote
							break;
						}
						if (ord(self::$a) <= self::ORD_LF) {
							throw new JSMin_UnterminatedStringException(
	                                "JSMin: Unterminated String at byte "
							. self::$inputIndex . ": {$str}");
						}
						$str .= self::$a;
						if (self::$a === '\\') {
							self::$output .= self::$a;
							self::$lastByteOut = self::$a;

							self::$a       = self::get();
							$str .= self::$a;
						}
					}
				}
				// fallthrough
			case self::ACTION_DELETE_A_B:
				self::$b = self::next();
				if (self::$b === '/' && self::isRegexpLiteral()) {
					// RegExp literal
					self::$output .= self::$a . self::$b;
					$pattern = '/'; // in case needed for exception
					while (true) {
						self::$a = self::get();
						$pattern .= self::$a;
						if (self::$a === '/') {
							// end pattern
							break; // while (true)
						} elseif (self::$a === '\\') {
							self::$output .= self::$a;
							self::$a       = self::get();
							$pattern      .= self::$a;
						} elseif (ord(self::$a) <= self::ORD_LF) {
							throw new JSMin_UnterminatedRegExpException(
	                                "JSMin: Unterminated RegExp at byte "
							. self::$inputIndex .": {$pattern}");
						}
						self::$output .= self::$a;
						self::$lastByteOut = self::$a;
					}
					self::$b = self::next();
				}
				// end case ACTION_DELETE_A_B
		}
	}

	/**
	 * @return bool
	 */
	static protected function isRegexpLiteral() {
		if (false !== strpos("\n{;(,=:[!&|?", self::$a)) {
			// we aren't dividing
			return true;
		}
		if (' ' === self::$a) {
			$length = strlen(self::$output);
			if ($length < 2) {
				// weird edge case
				return true;
			}
			// you can't divide a keyword
			if (preg_match('/(?:case|else|in|return|typeof)$/', self::$output, $m)) {
				if (self::$output === $m[0]) {
					// odd but could happen
					return true;
				}
				// make sure it's a keyword, not end of an identifier
				$charBeforeKeyword = substr(self::$output, $length - strlen($m[0]) - 1, 1);
				if (! self::isAlphaNum($charBeforeKeyword)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Get next char. Convert ctrl char to space.
	 *
	 * @return string
	 */
	static protected function get() {
		$c = self::$lookAhead;
		self::$lookAhead = null;
		if ($c === null) {
			if (self::$inputIndex < self::$inputLength) {
				$c = self::$input[self::$inputIndex];
				self::$inputIndex += 1;
			} else {
				return null;
			}
		}
		if ($c === "\r" || $c === "\n") {
			return "\n";
		}
		if (ord($c) < self::ORD_SPACE) {
			// control char
			return ' ';
		}
		return $c;
	}

	/**
	 * Get next char. If is ctrl character, translate to a space or newline.
	 *
	 * @return string
	 */
	static protected function peek() {
		self::$lookAhead = self::get();
		return self::$lookAhead;
	}

	/**
	 * Is $c a letter, digit, underscore, dollar sign, escape, or non-ASCII?
	 *
	 * @param string $c
	 *
	 * @return bool
	 */
	static protected function isAlphaNum($c) {
		return (preg_match('/^[0-9a-zA-Z_\\$\\\\]$/', $c) || ord($c) > 126);
	}

	/**
	 * @return string
	 */
	static protected function singleLineComment() {
		$comment = '';
		while (true) {
			$get = self::get();
			$comment .= $get;
			if (ord($get) <= self::ORD_LF) {
				// EOL reached
				// if IE conditional comment
				if (preg_match('/^\\/@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
					return "/{$comment}";
				}
				return $get;
			}
		}
	}

	/**
	* @return string
	* @throws JSMin_UnterminatedCommentException
	*/
	static protected function multipleLineComment() {
		self::get();
		$comment = '';
		while (true) {
			$get = self::get();
			if ($get === '*') {
				if (self::peek() === '/') {
					// end of comment reached
					self::get();
					// if comment preserved by YUI Compressor
					if (0 === strpos($comment, '!')) {
						return "\n/*!" . substr($comment, 1) . "*/\n";
					}
					// if IE conditional comment
					if (preg_match('/^@(?:cc_on|if|elif|else|end)\\b/', $comment)) {
						return "/*{$comment}*/";
					}
					return ' ';
				}
			} elseif ($get === null) {
				throw new JSMin_UnterminatedCommentException("JSMin: Unterminated comment at byte " . self::$inputIndex . ": /*{$comment}");
			}
			$comment .= $get;
		}
	}

	/**
	* Get the next character, skipping over comments.
	* Some comments may be preserved.
	*
	* @return string
	*/
	static protected function next() {
		$get = self::get();
		if ($get !== '/') {
			return $get;
		}
		switch (self::peek()) {
			case '/': return self::singleLineComment();
			case '*': return self::multipleLineComment();
			default: return $get;
		}
	}

}