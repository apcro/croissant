<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
namespace Croissant;

Class Email extends Core {

	static $core;
	/**
	 * Initialise 
	 * 
	 * @return
	 */
	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();
		}
		return self::$core;
	}

	/**
	 * Mail format
	 * @param $email
	 * @return boolean
	 */
	static final public function Mailformat($email) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$email = (string)$email;
		if (preg_match("/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)*(\.[a-zA-Z]{2,3})$/", $email)){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Send an e-mail message, using default settings.
	 *
	 * @param string $to The mail address or addresses where the message will be send to.
	 * @param string $subject Subject of the e-mail to be sent.
	 * @param string $body The message to be sent.
	 * @param string $from Sets From, Reply-To, Return-Path and Error-To to this value, if given.
	 * @param array $headers Associative array containing the headers to add. This is typically used to add extra headers (From, Cc, and Bcc).
	 * @return bool Returns TRUE if the mail was successfully accepted for delivery, FALSE otherwise.
	 */
	static final public function Send($to, $subject, $body, $from = NULL, $headers = array()) {

		$defaults = array(	'MIME-Version' => '1.0',
							'Content-Type' => 'text/plain; charset=UTF-8; format=flowed',
							'Content-Transfer-Encoding' => '8Bit',
							'X-Mailer' => ''
						);
		// To prevent e-mail from looking like spam, the addresses in the Sender and
		// Return-Path headers should have a domain authorized to use the originating
		// SMTP server.  Errors-To is redundant, but shouldn't hurt.
		$default_from = defined('CONTACT_US_EMAIL') ? CONTACT_US_EMAIL : false;
		if ($default_from) {
			$defaults['From'] = $defaults['Reply-To'] = $defaults['Sender'] = $defaults['Return-Path'] = $defaults['Errors-To'] = $default_from;
		}
		if ($from) {
			$defaults['From'] = $defaults['Reply-To'] = $from;
		}
		$headers = array_merge($defaults, $headers);
		// According to RFC 2646, it's quite rude to not wrap your e-mails:
		//
		// "The Text/Plain media type is the lowest common denominator of
		// Internet e-mail, with lines of no more than 997 characters (by
		// convention usually no more than 80), and where the CRLF sequence
		// represents a line break [MIME-IMT]."
		//
		// CRLF === \r\n
		//
		// http://www.rfc-editor.org/rfc/rfc2646.txt

		$mimeheaders = array();
		foreach ($headers as $name => $value) {
			$mimeheaders[] = $name .': '. self::MimeHeaderEncode($value);
		}

		return mail(
			$to,
			self::MimeHeaderEncode($subject),
			str_replace("\r", '', $body),
			join("\n", $mimeheaders)
		);
	}

	/**
	 * Encodes MIME/HTTP header values that contain non-ASCII, UTF-8 encoded
	 * characters.
	 *
	 * For example, mime_header_encode('tést.txt') returns "=?UTF-8?B?dMOpc3QudHh0?=".
	 *
	 * See http://www.rfc-editor.org/rfc/rfc2047.txt for more information.
	 *
	 * Notes:
	 * - Only encode strings that contain non-ASCII characters.
	 * - We progressively cut-off a chunk with truncate_utf8(). This is to ensure
	 *   each chunk starts and ends on a character boundary.
	 * - Using \n as the chunk separator may cause problems on some systems and may
	 *   have to be changed to \r\n or \r.
	 */
	static function MimeHeaderEncode($string) {
		if (preg_match('/[^\x20-\x7E]/', $string)) {
			$chunk_size = 47; // floor((75 - strlen("=?UTF-8?B??=")) * 0.75);
			$len = strlen($string);
			$output = '';
			while ($len > 0) {
				$chunk = self::_truncate_utf8($string, $chunk_size);
				$output .= ' =?UTF-8?B?'. base64_encode($chunk) ."?=\n";
				$c = strlen($chunk);
				$string = substr($string, $c);
				$len -= $c;
			}
			return trim($output);
		}
		return $string;
	}

	/**
	 * Complement to mime_header_encode
	 *
	 * @param $header
	 * @return string
	 */
	static function MimeHeaderDecode($header) {
		// First step: encoded chunks followed by other encoded chunks (need to collapse whitespace)
		$header = preg_replace_callback('/=\?([^?]+)\?(Q|B)\?([^?]+|\?(?!=))\?=\s+(?==\?)/', 'self::_mime_header_decode', $header);
		// Second step: remaining chunks (do not collapse whitespace)
		return preg_replace_callback('/=\?([^?]+)\?(Q|B)\?([^?]+|\?(?!=))\?=/', 'self::_mime_header_decode', $header);
	}

	/**
	 * Helper function to mime_header_decode
	 *
	 * @param $matches
	 * return data
	 */
	static function _mime_header_decode($matches) {
		// Regexp groups:
		// 1: Character set name
		// 2: Escaping method (Q or B)
		// 3: Encoded data
		$data = ($matches[2] == 'B') ? base64_decode($matches[3]) : str_replace('_', ' ', quoted_printable_decode($matches[3]));
		if (strtolower($matches[1]) != 'utf-8') {
			$data = self::_convert_to_utf8($data, $matches[1]);
		}
		return $data;
	}

	/**
	 * Convert data to UTF-8
	 *
	 * @param string $data The data to be converted.
	 * @param string $encoding The encoding that the data is in
	 * @return mixed The converted data or FALSE.
	 */
	static function _convert_to_utf8($data, $encoding) {
		if (function_exists('mb_convert_encoding')) {
			$out = @mb_convert_encoding($data, 'utf-8', $encoding);
		} else {
			_log('Unsupported encoding called decoding mail header. Please install iconv, GNU recode or mbstring for PHP.');
			return FALSE;
		}
		return $out;
	}

	/**
	 * Validate email
	 * 
	 * @param string $str email
	 * @return
	 */
	static function ValidEmail($str) {
		return ( ! preg_match("/^([\'A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/", $str)) ? false : true;
	}

	static function truncate_utf8($string, $max_length, $wordsafe = FALSE, $add_ellipsis = FALSE, $min_wordsafe_length = 1) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$ellipsis = '';
		$max_length = max($max_length, 0);
		$min_wordsafe_length = max($min_wordsafe_length, 0);

		if (strlen($string) <= $max_length) {
			return $string;
		}

		if ($add_ellipsis) {
			$ellipsis = substr('...', 0, $max_length);
			$max_length -= strlen($ellipsis);
			$max_length = max($max_length, 0);
		}

		if ($max_length <= $min_wordsafe_length) {
			$wordsafe = FALSE;
		}

		if ($wordsafe) {
			$matches = array();
			$found = preg_match('/^(.{' . $min_wordsafe_length . ',' . $max_length . '})[' . PREG_CLASS_UNICODE_WORD_BOUNDARY . ']/u', $string, $matches);
			if ($found) {
				$string = $matches[1];
			} else {
				$string = substr($string, 0, $max_length);
			}
		} else {
			$string = substr($string, 0, $max_length);
		}

		if ($add_ellipsis) {
			$string .= $ellipsis;
		}

		return $string;
	}
}