<?php
/**
 * This is a class that is used to parse a string and format it
 * for BBCode. This class implements the use of a unique
 * identifier, for the purpose of saving resources, post-database.
 *
 * @author        Matt Carroll <admin@develogix.com>
 * @copyright     Copyright 2004, 2005, 2006, 2007, 2008, 2009, 2010, Matt Carroll
 *                http://gnu.org/copyleft/gpl.html GNU GPL
 * @version       $Id: bbcode.class.php,v 3.0.2 2010/11/12 19:10:00 logi Exp $
 *
 * This version updates the class to PHP5, as well as implementing a new method of parsing.
 */
class BBCode {

	/**
	 * Parses a BBCode string
	 *
	 * @param  str	string to be parsed
	 * @param bool $simple
	 * @param bool $url
	 * @return parsed string
	 */
	static public function Parse($str, $simple = true, $url = false) {
		$uid = self::_makeUID();

		if ($simple)
			$str = self::_bbSimple($str, $uid);

		if ($url)
			$str = self::_bbUrl($str, $uid);

		// Leaving out the extra features since the aren't needed for now
		$str = self::_bbQuote($str, $uid);
		/*$str = self::_bbList($str, $uid);
		$str = self::_bbMail($str, $uid);
		$str = self::_bbImg($str, $uid);
		*/
		$str = self::_bbSize($str, $uid);
// 		dump($str);
		return $str;
	}

	/**
	 * @return uid generated unique identifier with a length of 8 characters
	 */
	private static function _makeUID() {
		return substr(md5(mt_rand()), 0, 8);
	}

	/**
	 * parses string for [list], [*]
	 *
	 * @param string str       string to be parsed
	 * @param string uid       unique identifier with a length of 8 characters
	 * @return string
	 */
	private static function _bbList($str, $uid) {

		$match	 = array(
				'#\[list\](.*?)\[/list\]#si',
				'#\[\*\](.*?)\[/\*\]#si'
			);
		$replace   = array(
				'[list:'.$uid.']$1[/list:'.$uid.']',
				'[*:'.$uid.']$1[/*:'.$uid.']'
			);
		$str = preg_replace($match, $replace, $str);

		$match	 = array(
				'[list:'.$uid.']',
				'[/list:'.$uid.']',
				'[*:'.$uid.']',
				'[/*:'.$uid.']'
			);
		$replace   = array(
				'<ul>',
				'</ul>',
				'<li>',
				'</li>'
			);
		$str = str_replace($match, $replace, $str);

		return $str;
	}

	/**
	 * parses string for [b], [i], [u], [s], [em], [sup], and [sub]
	 *
	 * @param string str       string to be parsed
	 * @param string uid       unique identifier with a length of 8 characters
	 * @return string
	 */
	private static function _bbSimple($str, $uid) {
		$match = array(
				'#\[b\](.*?)\[/b\]#si',
				'#\[i\](.*?)\[/i\]#si',
				'#\[u\](.*?)\[/u\]#si',
				'#\[s\](.*?)\[/s\]#si',
				'#\[em\](.*?)\[/em\]#si',
				'#\[sup\](.*?)\[/sup\]#si',
				'#\[sub\](.*?)\[/sub\]#si'
			);
		$replace = array(
				'[b:'.$uid.']$1[/b:'.$uid.']',
				'[i:'.$uid.']$1[/i:'.$uid.']',
				'[u:'.$uid.']$1[/u:'.$uid.']',
				'[s:'.$uid.']$1[/s:'.$uid.']',
				'[em:'.$uid.']$1[/em:'.$uid.']',
				'[sup:'.$uid.']$1[/sup:'.$uid.']',
				'[sub:'.$uid.']$1[/sub:'.$uid.']'
			);
		$str = preg_replace($match, $replace, $str);

		$match = array(
				'[b:'.$uid.']',
				'[/b:'.$uid.']',
				'[i:'.$uid.']',
				'[/i:'.$uid.']',
				'[u:'.$uid.']',
				'[/u:'.$uid.']',
				'[s:'.$uid.']',
				'[/s:'.$uid.']',
				'[em:'.$uid.']',
				'[/em:'.$uid.']',
				'[sup:'.$uid.']',
				'[/sup:'.$uid.']',
				'[sub:'.$uid.']',
				'[/sub:'.$uid.']'
			);
		$replace = array(
				'<strong>',
				'</strong>',
				'<em>',
				'</em>',
				'<span style="text-decoration: underline;">',
				'</span>',
				'<del>',
				'</del>',
				'<em>',
				'</em>',
				'<sup>',
				'</sup>',
				'<sub>',
				'</sub>'
			);

		$str = str_replace($match, $replace, $str);

		return $str;
	}

	/**
	 * parses string for [quote=*] and [quote]
	 *
	 * @param string str       string to be parsed
	 * @param string uid       unique identifier with a length of 8 characters
	 * @return string
	 */
	private static function _bbQuote($str, $uid) {
		$match	 = array(
				'#\[quote=(.*?)\](.*?)\[/quote\]#si',
				'#\[quote\](.*?)\[/quote\]#si'
			);
		$replace   = array('
				[quote=$1:'.$uid.']$2[/quote:'.$uid.']',
				'[quote:'.$uid.']$1[/quote:'.$uid.']'
			);
		$str = preg_replace($match, $replace, $str);

		$match	 = array(
				'#\[quote=(.*?):'.$uid.'\](.*?)\[/quote:'.$uid.'\]#si',
				'#\[quote:'.$uid.'\](.*?)\[/quote:'.$uid.'\]#si'
			);
		$replace   = array(
				'<span class="quoteStyle"><strong>Quoted from <em>$1</em></strong><br />$2</span>',
				'<span class="quoteStyle"><strong>Quote</strong><br />$1</span>'
			);
		$str = preg_replace($match, $replace, $str);

		return $str;
	}


	/**
	 * parses string for [mail=*] and [mail]
	 *
	 * @param string str       string to be parsed
	 * @param string uid       unique identifier with a length of 8 characters
	 * @return string
	 */
	private static function _bbMail($str, $uid) {
		$match	 = array(
				'#\[mail=([a-z0-9\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\](.*?)\[/mail\]#si',
				'#\[mail\]([a-z0-9\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+)\[/mail\]#si'
			);
		$replace   = array(
				'[mail=$1:'.$uid.']$2[/mail:'.$uid.']',
				'[mail=$1:'.$uid.']$1[/mail:'.$uid.']'
			);
		$str = preg_replace($match, $replace, $str);

		$match     = '#\[mail=([a-z0-9\-_.]+?@[\w\-]+\.([\w\-\.]+\.)?[\w]+):'.$uid.'\](.*?)\[/quote\]#si';
		$replace   = '<a href="mailto:$1">$2</a>';
		$str = preg_replace($match, $replace, $str);

		return $str;
	}

	/**
	 * parses string for [url=*], [url], and unformatted URLs
	 *
	 * @param string str       string to be parsed
	 * @param string uid       unique identifier with a length of 8 characters
	 * @return string
	 */
	private static function _bbUrl($str, $uid) {
		$match	= array(
				'#(?<!(\]|=|\/))((http|https|ftp|irc|telnet|gopher|afs)\:\/\/|www\.)(.+?)( |\n|\r|\t|\[|$)#si',
				'#\[url\]([a-z0-9]+?://){1}([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^ \"\n\r\t<]*)?)\[/url\]#is',
				'#\[url\]((www|ftp)\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^ \"\n\r\t<]*?)?)\[/url\]#si',
				'#\[url=([a-z0-9]+://)([\w\-]+\.([\w\-]+\.)*[\w]+(:[0-9]+)?(/[^ \"\n\r\t<]*?)?)\](.*?)\[/url\]#si',
				'#\[url=(([\w\-]+\.)*?[\w]+(:[0-9]+)?(/[^ \"\n\r\t<]*)?)\](.*?)\[/url\]#si'
			);
		$replace   = array(
				'[url:'.$uid.']$1$2$4[/url:'.$uid.']$5',
				'[url:'.$uid.']$1$2[/url:'.$uid.']',
				'[url:'.$uid.']http://$1[/url:'.$uid.']',
				'[url=$1$2:'.$uid.']$6[/url:'.$uid.']',
				'[url=http://$1:'.$uid.']$5[/url:'.$uid.']'
			);
		$str = preg_replace($match, $replace, $str);

		$match = array(
				'#\[url:'.$uid.'\](.*?)\[/url:'.$uid.'\]#si',
				'#\[url=(.*?):'.$uid.'\](.*?)\[/url:'.$uid.'\]#si'
			);
		$replace   = array('<a href="$1">$1</a>', '<a href="$1">$2</a>');
		$str = preg_replace($match, $replace, $str);

		return $str;
	}

	/**
	 * parses string for [img], limited to $this->imgLimit amount of times
	 *
	 * @param string str       string to be parsed
	 * @param string uid       unique identifier with a length of 8 characters
	 * @return string
	 */
	private static function _bbImg($str, $uid) {
		$match     = '#\[img\](.*?)\[\/img\]#si';
		$replace   = '[img:'.$uid.']$1[/img:'.$uid.']';
		$str = preg_replace($match, $replace, $str, $this->imgLimit);

		$match     = '#\[img:'.$uid.'\](.*?)\[/img:'.$uid.'\]#si';
		$replace   = '<img src="$1" />';
		$str = preg_replace($match, $replace, $str, $this->imgLimit);

		return $str;
	}

	/**
	* parses string for [size], limited to $this->imgLimit amount of times
	*
	* @param string str       string to be parsed
	* @param string uid       unique identifier with a length of 8 characters
	* @return string
	*/
	private static function _bbSize($str, $uid) {
		$match     = '#\[size=(.*?)\](.*?)\[/size\]#si';
		$replace   = "<span style=\"font-size:$1\">$2</span>";
		preg_match_all($match, $str, $m);
		$str = preg_replace($match, $replace, $str);
		return $str;
	}
}