<?php
/**
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

Class Session extends Core {
	static $core;
	public static function Initialise() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!isset(self::$core)) {
			self::$core = parent::Initialise();
		}

		if (session_save_path() != SESSIONCACHE) {
			@mkdir(SESSIONCACHE, 0777, true);
			session_save_path(SESSIONCACHE);
		}

		return self::$core;
	}

	/**
	 * Load session
	 * 
	 * @return void
	 */
	static final private function _loadSession() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		// we reference this before the autoloader has been initialised,
		// so we can't use Cookie::GetCookie()
		$sesskey = isset($_COOKIE['sesskey'])?$_COOKIE['sesskey']:'';
		if (DEBUG) _log('sesskey::'.$sesskey);
		// are we getting sessions from $_SESSION or from the dataserver?
		if (empty($sesskey)) {
			// set up a new session key - this is a first load or cookies have been cleared
			$rand1 = rand()+time();
			$rand2 = rand()-time();
			$rand3 = rand()-time();
			$rand = time()+$rand1-$rand2+$rand3;
			$rand = md5($rand);
			$rand = str_rot13($rand);
			$sesskey = md5($rand);

			// set the cookie to expire in 60 minutes
			if (Cookie::SetCookie('sesskey', $sesskey, 0) === true) {
				_log('Setting cookie');
			} else {
				_log('Failed to set a cookie');
			}
			self::SetVariable('sesskey', $sesskey);
		} else {
			if ($_SESSION['sesskey'] != $sesskey) {
				// we've moved to a new webserver - reload the session here from the database
				$response = ds('session_ReadData', array('id' => $sesskey, 'type' => Core::Markup()));
				if (isset($response['statusCode']) && $response['statusCode'] == 0) {
					$data = unserialize($response['result']);
					// this is the WHOLE session
					$_SESSION = $data;
				}
				self::SetVariable('sesskey', $sesskey);
			}
		}
	}

	/**
	 * Starts the session handling code, loading data as necessary based on the type of device
	 * 
	 * @param int $start
	 * @return
	 */
	static final public function Start($markup = 'html') {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		switch ($markup) {
			case 'wml';
				break;
			case 'html':
			default:
				// set up the cookie domain for this site
				if (!isset($cookie_domain)) {
					$cookie_domain = ini_get('session.cookie_domain');
				}
				if (DEBUG) _log('cookie_domain::'.$cookie_domain);
				if ($cookie_domain) {
					$session_name = $cookie_domain;
				} else {
					$session_name = $_SERVER['HTTP_HOST'];
					if (!empty($_SERVER['HTTP_HOST'])) {
						$cookie_domain = $_SERVER['HTTP_HOST'];
					}
				}
				if (DEBUG) _log('session_name::'.$session_name);
				$cookie_domain = explode(':', $cookie_domain);
				$cookie_domain = $cookie_domain[0];
				if (count(explode('.', $cookie_domain)) > 2 && !is_numeric(str_replace('.', '', $cookie_domain))) {
					ini_set('session.cookie_domain', $cookie_domain);
				}

				session_name('SESS'. md5($session_name));
				if (DEBUG) _log('starting session');
				session_start();
				break;
		}
		$_SESSION['markup'] = $markup;
		if (STATELESS == 1) {
			// load session data from the dataserver if needed and if running in stateless mode
			self::_loadSession();
		}
		self::$core->_session = true;

		// get the user configuration, if any. We need it at this point, but can't load it earlier
		require_once(BASEPATH.'/configuration/user.configuration.php');
		foreach(Core::$core->_user_session as $k) {
			self::$core->_user[$k] = Session::Setvariable($k);
		}

	}

	/**
	 * Stores the current $_SESSION variable to disk
	 *
	 * This should be truncated if it's too big
	 */
	static final public function Store() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (STATELESS) {
			$response = ds('session_WriteData', array('key' => $_SESSION['sesskey'], 'data' => $_SESSION));
			if (isset($response['statusCode']) && $response['statusCode'] == 0) {
				return true;
			} else {
				return false;
			}
		}
		return;
	}

	/**
	 * Set session variable
	 * 
	 * @param string $var
	 * @param mixed $data
	 * @return
	 */
	static final public function SetVariable($var, $data) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		switch($_SESSION['markup']) {
			case 'wml':
				break;
			case 'html':
			default:
				if (!empty($data)) {
					$_SESSION[$var] = $data;
				} else {
					unset($_SESSION[$var]);
				}
				break;
		}
		return;
	}

	/**
	 * Get session variable
	 * 
	 * @param string $var
	 * @return
	 */
	static final public function GetVariable($var) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (DEBUG) _log("\tmarkup: ".$_SESSION['markup'].', var: '.$var);
		switch($_SESSION['markup']) {
			case 'wml':
				break;
			case 'html':
			default:
				$data = isset($_SESSION[$var])?$_SESSION[$var]:'';
				break;
		}
		return $data;
	}
}