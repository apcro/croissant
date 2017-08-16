<?php
/*
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
class Tumblr extends Core {

	public function __construct() {}
	protected function __clone() {}
	public static function initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::initialise();
		}
		self::$_blogname = TUMBLR_BLOGNAME;
		self::$_username = TUMBLR_USERNAME;
		self::$_password = TUMBLR_PASSWORD;
	}

	private static $_blogname;
	private static $_username;
	private static $_password;

	/*
	 * Reads Tumblr blog posts from the named private Tumblr account
	 */
	function Read($start = 0, $limit = 10) {
		$creds = http_build_query(array(
				'email'     => self::$_username,
				'password'  => self::$_password,
				'start'		=> $start,
				'num'		=> $limit
		));
		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, 'http://'.self::$_blogname.'.tumblr.com/api/read');
		curl_setopt($c, CURLOPT_POST, true);
		curl_setopt($c, CURLOPT_USERPWD, self::$_username.':'.self::$_password);
		curl_setopt($c, CURLOPT_POSTFIELDS, $creds);
		$result = curl_exec($c);
		$status = curl_getinfo($c, CURLINFO_HTTP_CODE);
		curl_close($c);
//		dump($result);
//		die();
		if ($status == 200) {
			$data = Core::xml2array($result);
			return $data;
		} else {
			return false;
		}
	}
}