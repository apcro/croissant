<?php
/**
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

class User extends Core {
	static $core;
	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();
		}
		return self::$core;
	}

	/**
	 * Login process
	 * 
	 * @param string $mail
	 * @param string $password
	 * @param bool $remember
	 * @param string $sessionid
	 * @param integer $timestamp
	 * @param integer $override
	 * @return
	 */
	static public function Login($mail, $password, $remember = false, $sessionid = '', $timestamp = 0, $override = 0) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$mail = (string)$mail;
		$password = (string)$password;
		if (strlen($password) != 32) {
			$password = md5($password);
		}

		$response = ds('user_Login', array('mail' => $mail, 'password' => $password, 'remember' => $remember, 'sessionid' => $sessionid, 'timestamp' => $timestamp, 'override' => $override));

		if (isset($response['status'])) {
			if ($response['statusCode'] == 0) {
				self::$core->_user  = $response['result'];
				foreach(Core::$core->_user_session as $k) {
					Session::Setvariable($k, self::$core->_user[$k]);
				}

				return $response['result'];
			} else {
				if (isset($response['errorData']) && $response['errorData'] == 'loggedin') {
					return $response;
				} else {
					return false;
				}
			}
		}
	}

	/**
	 * Get logged in user id
	 * 
	 * @return
	 */
	static public function UserID() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return self::$core->_user['id'];
	}

	/**
	 * Get logged in user property
	 * 
	 * @param string $attribute
	 * @return
	 */
	static public function GetAttribute($attribute) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return self::$core->_user[$attribute];
	}


	/**
	 * Set User Hash
	 */
	static final public function setUserHash() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		Session::SetVariable('user_hash', md5(uniqid(rand(), true)));
	}

	/**
	 * Get User Hash
	 *
	 * @return user hash
	 */
	static final public function getUserHash() {
		return Session::GetVariable('user_hash');
	}

	/**
	 * Get User session id
	 * 
	 * @param mixed $userid
	 * @return
	 */
	static function GetSessionID($userid) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$response = ds('user_GetSessionID', array('userid' => $userid));
		return Core::GenericResponse($response);
	}

	/**
	 * Load user data by userid . If userid is 0 then load from logged in user.
	 * 
	 * @param integer $userid
	 * @return
	 */
	static public function LoadUser($userid = 0) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$userid = (int)$userid;
		if ($userid != 0) {
			$response = ds('user_LoadUser', array('userid' => $userid));
			return Core::GenericResponse($response);
		} else {
			return false;
		}
	}

	/**
	 * Load user by email.
	 * 
	 * @param string $email
	 * @return
	 */
	static public function LoadUserByMail($email = '') {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if ($email != '') {
			$response = ds('user_LoadUserByEmail', array('email' => $email));
			return Core::GenericResponse($response);
		} else {
			return false;
		}
	}

	/**
	 * Load current user
	 * 
	 * @return
	 */
	static public function LoadCurrentUser() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$userid = self::UserID();
		if ($userid != 0) {
			if (empty(self::$core->_user)) {
				$response = ds('user_Loaduser', array('userid' => $userid));
				if (isset($response['statusCode']) && $response['statusCode'] == 0) {
					self::$core->_user = $response['result'];
				}
			}
		}

		return self::$core->_user;
	}

	/**
	 * Logout
	 * 
	 * @param bool $local
	 * @return
	 */
	static final public function Logout($local = false) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if ($local === false) {
			ds('user_Logout');
		}
		foreach(Core::$core->_user_session as $k) {
			Session::Setvariable($k, '');
		}

		setcookie(PERSISTENT_LOGIN_COOKIE, -1);
		unset(Core::$core->_user);

		$cookie_domain = ini_get('session.cookie_domain');
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
		Cookie::SetCookie('SESS'.md5($session_name), '', -1);
		session_destroy();
		return;
	}

	/**
	 * Check Username
	 *
	 * @param $username
	 * @param bool $inverse - invert the response (this would make the presence of NO matching username return TRUE and vice versa)
	 * @return int or boolean The users id or false for failure
	 */
	static final public function CheckUsername($username, $inverse = false) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!$username) return false;

		$response = ds('user_CheckUsername', array('username' => $username));
		if ($response['statusCode']==0) {
			return ($inverse) ? ! $response['result'] : $response['result'];
		} else {
			return false;
		}
	}

	/**
	 * Check Email
	 *
	 * @param $email
	 * @param bool $inverse - invert the response (this would make the presence of NO matching email return TRUE and vice versa)
	 * @return array or boolean
	 */
	static final public function CheckEmail($email, $inverse = false) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!$email) return false;

		$response = ds('user_CheckEmail', array('email' => $email));
		if ($response['statusCode']==0) {
			return ($inverse) ? !$response['result'] : $response['result'];
		} else {
			return false;
		}
	}

	/**
	 * Check if the password entered is the current user's password
	 *
	 * @param string $password - the string to check against the password
	 * @return bool - true for a match or false
	 */
	static public function CheckPassword($password) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		ds('passwordhash_InitHash', array('iteration_count_log2' => 8, 'portable_hashes' => false));
		$password = md5($password);
		$result = ds('user_CheckPassword', array('password' => $password, 'stored_hash' => User::Password()));
		if ($password == User::Password() || $result['result']) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Generate a random string for a temporary password
	 * @param int $length - the length of the created password
	 * @return string
	 */
	public static function CreateTempPassword($length = 10) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$characters = "0123456789abcdefghijklmnopqrstuvwxyz";
		for ($p = 0; $p < $length; $p++) {
			$pass .= $characters[mt_rand(0, strlen($characters))];
		}
		return $pass;
	}

	/**
	 * Change user password.
	 * 
	 * @param string $newpassword
	 * @param bool $userid
	 * @return
	 */
	public static function ChangePassword($newpassword, $userid = false) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$data = array('data' => array('pass' => $newpassword));
		if ($userid) {
			$data['data']['userid'] = (int)$userid;
		}
		$result = ds('user_UpdateUser', $data);
		return $result;
	}

	/**
	 * Get User Mail
	 *
	 * @param $email
	 * @return array or boolean
	 */
	static final public function GetUserMail($email) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!$email) {
			return false;
		}
		$response = ds('user_GetUserMail', array('email' => $email));
		return Core::GenericResponse($response);
	}


	/**
	 * Check banned on current user
	 * 
	 * @return
	 */
	function isBanned() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return self::$core->banned;
	}

	/**
	 * Create a new user
	 * @param array $data - the data to use
	 * @return array
	 */
	public static function CreateUser(array $data) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (isset($data['agree_terms']) && $data['agree_terms']) {
			$response = ds('user_CreateUser', array(
				'firstname'	=> isset($data['firstname']) ? $data['firstname'] : '',
				'lastname'	=> isset($data['lastname']) ? $data['lastname'] : '',
				'mail'		=> isset($data['mail']) ? $data['mail'] : '',
				'telephone'	=> isset($data['telephone']) ? $data['telephone'] : '',
				'cellphone'	=> isset($data['cellphone']) ? $data['cellphone'] : '',
				'facebook_id' => isset($data['facebook_id']) ? $data['facebook_id'] : '',
				'location'	=> isset($data['location']) ? $data['location'] : '',
				'country_number' => isset($data['country_number']) ? $data['country_number'] : '',
				'name'		=> isset($data['name']) ? $data['name'] : '',
				'pass'		=> isset($data['pass']) ? $data['pass'] : '',
				'license_key' => isset($data['license_key']) ? $data['license_key'] : '',
				'entry_url'	=> isset($data['entry_url']) ? $data['entry_url'] : '',
				'user_agent'=> isset($data['user_agent']) ? $data['user_agent'] : '',
				'allow_email' => isset($data['allow_email']) ? $data['allow_email'] : 0,
				'agree_terms' => isset($data['agree_terms']) ? $data['agree_terms'] : 0,
				'allow_thirdparty' => isset($data['allow_thirdparty']) ? $data['allow_thirdparty'] : 0,
                'gender'        => isset($data['gender']) ? $data['gender'] : '',
                'dob'           => isset($data['dob']) ? $data['dob'] : '',
                'postcode'      => isset($data['postcode']) ? $data['postcode'] : ''
			));
		}

		return $response;
	}

	/**
	 * Remove user
	 * 
	 * @param int $userid
	 * @param bool $soft
	 * @return
	 */
	public static function RemoveUser($userid, $soft = false) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$response = ds('user_RemoveUser', array('userid' => $userid, 'soft' => $soft));
		return $response;
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public static function UpdateUser(array $data) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!isset($data['data'])) {
			$data['data'] = $data['userdata'];
		}
		$response = ds('user_UpdateUser', array(
			'data' => $data
		));
		if (isset($data['name']))
			Session::SetVariable('username', $data['name']);

		if (isset($data['firstname']))
			Session::SetVariable('firstname', $data['firstname']);

		if (isset($data['lastname']))
			Session::SetVariable('lastname', $data['lastname']);

		if (isset($data['facebook_id']))
			Session::SetVariable('facebook_id', $data['facebook_id']);

		if (isset($data['mail']))
			Session::SetVariable('mail', $data['mail']);

		if (isset($data['userdata']))
			Session::SetVariable('userdata', $data['data']);

		if (isset($data['postcode']))
			Session::SetVariable('postcode', $data['postcode']);

		if (isset($data['dob']))
			Session::SetVariable('dob', $data['dob']);

		if (isset($data['gender']))
			Session::SetVariable('gender', $data['gender']);

		return $response;
	}

	/**
	 * Send request for reset password
	 * 
	 * @param int $userid
	 * @param int $time
	 * @param string $key
	 * @return
	 */
	static function RequestPwdReset($userid, $time, $key) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$result = ds('user_RequestPwdReset', array('userid' => $userid, 'time'=> $time, 'key'=>$key));
		return Core::GenericResponse($response);
	}

	/**
	 * Check reset key is expired.
	 * 
	 * @param string $key
	 * @return bool
	 */
	static function CheckPwdResetExpire($key) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$result = ds('user_CheckPwdResetExpire', array($key));
		if (isset($result['statusCode']) && $result['statusCode'] == 0) {
			$time = $result['result']['pwdreset_time'];
			$expire_time = $time+(60*60*24*5); // 5 days
			if (time() < $expire_time) {
				return $result['result']['id'];
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Generate password reset
	 * 
	 * @return array
	 */
	static function GenKeyForPwdReset() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$key = md5(uniqid(mt_rand(), true));
		return array('key' => $key, 'ec_key' => base64_encode($key));
	}

	/**
	 * Generate key for password reset
	 * 
	 * @param string $key
	 * @return array
	 */
	static function GetKeyForPwdReset($key) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return array('ec_key' => $key, 'key' => base64_decode($key));
	}

}