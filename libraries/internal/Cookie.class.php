<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
namespace Croissant;

Class Cookie {

	/**
	 * Get cookie by name.
	 * 
	 * @param string $name
	 * @param string $format
	 * @return
	 */
	static public function GetCookie($name, $format = 'int') {
		if (isset($_COOKIE[$name])) {
			if (strstr($_COOKIE[$name], ':')) {
				list($data, $series, $token) = explode(':', $_COOKIE[$name]);
			} else {
				$data = $_COOKIE[$name];
			}
			return ($format == 'int') ? (int)$data : (string)$data;
		} else {
			return 0;
		}
	}

	/**
	 * Set cookie by name.
	 * 
	 * @param string $name
	 * @param string $data
	 * @param integer $expires
	 * @param bool $persist
	 * @return
	 */
	static public function SetCookie($name, $data, $expires = 0, $persist = false) {
		if ($persist) {
			$token = md5(uniqid(mt_rand()));
			$series = md5(uniqid(mt_rand()));
			$cookie_data = $data.':'.$series.':'.$token;
		} else {
			$cookie_data = $data;
		}
		$cookie_domain = ini_get('session.cookie_domain');
		return setcookie($name, $cookie_data, $expires, '/', $cookie_domain, false, true);
	}

	/**
	 * Cookie setup.
	 * 
	 * @return void
	 */
	final static function Setup() {
		if (!isset($cookie_domain)) {
			$cookie_domain = ini_get('session.cookie_domain');
		}
		if ($cookie_domain) {
			$session_name = $cookie_domain;
		} else {
			$session_name = $_SERVER['HTTP_HOST'];
			if (!empty($_SERVER['HTTP_HOST'])) {
				$cookie_domain = $_SERVER['HTTP_HOST'];
			}
		}
		$cookie_domain = explode(':', $cookie_domain);
		$cookie_domain = $cookie_domain[0];
		if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
			ini_set('session.cookie_domain', $cookie_domain);
		}
		session_name('SESS'. md5($session_name));
		session_start();
	}
}