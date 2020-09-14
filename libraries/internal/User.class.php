<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
namespace Croissant;

class User extends Core {
	static $core;
	public static function Initialise() {
		if (!isset(self::$core)) {
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
		$mail = (string)$mail;
		$password = (string)$password;

		$response = Dataserver::Read('user', 'Login', array('mail' => $mail, 'password' => $password, 'remember' => $remember, 'sessionid' => $sessionid, 'timestamp' => $timestamp, 'override' => $override));
		if (isset($response['status'])) {
			if ($response['statusCode'] == 0) {
				self::$core->_user = $response['result'];

				foreach(Core::$core->_user_session as $k) {
					Session::SetVariable($k, self::$core->_user[$k]);
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
		if (isset(self::$core->_user['id'])) {
			if (empty(self::$core->_user['id'])) {
				return 0;
			}
			return self::$core->_user['id'];
		} else {
			return 0;
		}
	}
	
	static public function Email() {
		return self::$core->_user['mail'];
	}
	
	/**
	 * Get logged in user property
	 *
	 * @param string $attribute
	 * @return
	 */
	static public function GetAttribute($attribute) {
		return self::$core->_user[$attribute];
	}


	/**
	 * Set User Hash
	 */
	static final public function setUserHash() {
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
		$response = Dataserver::Read('user', 'GetSessionID', array('userid' => $userid));
		return Core::GenericResponse($response);
	}

	/**
	 * Load user data by userid.
	 *
	 * @param integer $userid
	 * @return mixed
	 */
	static public function LoadUser($userid = 0) {
		$userid = (int)$userid;
		if ($userid != 0) {
			return Core::GenericResponse(Dataserver::Read('user', 'LoadUser', array('userid' => $userid)));
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
		if ($email != '') {
			return Core::GenericResponse(Dataserver::Read('user', 'LoadUserByEmail', array('email' => $email)));
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
		$userid = self::UserID();
		if ($userid != 0 && empty(self::$core->_user)) {
			self::$core->_user = Core::GenericResponse(Dataserver::Read('user', 'Loaduser', array('userid' => $userid)));
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
	}

	/**
	 * Check Username
	 *
	 * @param $username
	 * @param bool $inverse - invert the response (this would make the presence of NO matching username return TRUE and vice versa)
	 * @return int or boolean The users id or false for failure
	 */
	static final public function CheckUsername($username, $inverse = false) {
		if ($username) {
			$response = Dataserver::Read('user', 'CheckUsername', array('username' => $username));
			if ($response['statusCode']==0) {
				return ($inverse) ? ! $response['result'] : $response['result'];
			} else {
				return false;
			}
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
		if ($email) {
			$response = Dataserver::Read('user', 'CheckEmail', array('email' => $email));
			if ($response['statusCode'] == 0) {
				return ($inverse) ? !$response['result'] : $response['result'];
			} else {
				return $response;
			}
		}
	}

	/**
	 * Check if the password entered is the current user's password
	 *
	 * @param string $password - the string to check against the password
	 * @return bool - true for a match or false
	 */
	static public function CheckPassword($password) {
		Dataserver::Write('passwordhash', 'InitHash', array('iteration_count_log2' => 8, 'portable_hashes' => false));
		$result = Dataserver::Read('user', 'CheckPassword', array('password' => $password, 'stored_hash' => User::Password()));
		return ($password == User::Password() || $result['result']);
	}

	/**
	 * Generate a random string for a temporary password
	 * @param int $length - the length of the created password
	 * @return string
	 */
	public static function CreateTempPassword($length = 10) {
		$characters = "0123456789abcdefghijklmnopqrstuvwxyz";
		$pass = '';
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
		$data = array('data' => array('pass' => $newpassword));
		if ($userid) {
			$data['data']['userid'] = (int)$userid;
		}
		return Dataserver::Write('user', 'UpdateUser', $data);
	}

	/**
	 * Get User Mail
	 *
	 * @param $email
	 * @return array or boolean
	 */
	static final public function GetUserMail($email) {
		if ($email) {
			$response = Dataserver::Read('user', 'GetUserMail', array('email' => $email));
			return Core::GenericResponse($response);
		}
		return false;
	}

	/**
	 * Check banned status on current user
	 *
	 * @return
	 */
	function isBanned() {
		return self::$core->banned;
	}

	/**
	 * Create a new user
	 * @param array $data - the data to use
	 * @return array
	 */
	public static function CreateUser($data) {

		if (isset($data['agree_terms']) && $data['agree_terms']) {

			$password = (string)$data['pass'];
			$data['password'] = $data['pass'];
			$data['pass'] = $password;
			
			$response = Dataserver::Write('user', 'CreateUser', array('data' => $data));
			if (isset($response['statusCode']) && $response['statusCode'] == 0) {
				self::$core->_user['id'] = $response['result']['userid'];
			} else {
				unset($data['password']);
				CroissantError::RecordError('User register issue', array('data' => $data, 'response' => $response));
			}
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
		return ds('user_RemoveUser', array('userid' => $userid, 'soft' => $soft));
	}

	/**
	 * @param array $data
	 * @return array
	 */
	public static function UpdateUser(array $data) {
		if (!isset($data['data'])) {
			$data['data'] = $data['userdata'];
		}
		$response = Dataserver::Writes('user', 'UpdateUser', array(
			'data' => $data
		));
		// we really need to reload user data at this point too
		// if the logged-in user is the same as the just-saved user
		if (isset($data['userid']) && $data['userid'] == User::UserID()) {
			foreach($data as $k => $v) {
				self::$core->_user[$k] = $v;
			}
			self::StoreUserInSession($data);
		}

		if (isset($data['oldpassword']) 
			&& isset($data['newpassword2'])  
			&& isset($data['newpassword']) 
			&& !empty($data['newpassword']) 
			&& ($data['newpassword'] == $data['newpassword2'])) {
				$response = Core::GenericResponse(Dataserver::Write('user', 'UpdateUserPassword', array('data' => $data)));
		}

		return $response;
	}

	public static function ResetUserPassword(array $data): mixed {
		return Core::GenericResponse(Dataserver::Write('user', 'ResetUserPassword', array('data' => $data)));
	}
	
	
	public static function UpdateUserProfile(array $data): mixed{
		return Core::GenericResponse(Dataserver::Write('user', 'UpdateUserProfile', array('data' => $data)));
	}
	
	public static function UpdateUserEmailPreferences(array $data): mixed {
		return Core::GenericResponse(Dataserver::Write('user', 'UpdateUserEmailPreferences', array(
				'data' => $data
		)));
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
		return Core::GenericResponse(Dataserver::Write('user', 'RequestPwdReset', array('userid' => $userid, 'time'=> $time, 'key'=>$key)));
	}

	/**
	 * Check reset key is expired.
	 *
	 * @param string $key
	 * @return bool
	 */
	static function CheckPwdResetExpire($key) {
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
		return array('ec_key' => $key, 'key' => base64_decode($key));
	}

	private static function StoreUserInSession($data) {
		require_once(BASEPATH.'/configuration/user.configuration.php');
		foreach(self::$core->_user_session as $k) {
			if (isset($data[$k])) {
				Session::SetVariable($k, $data[$k]);
			}
		}
	}

	public static function UserStatus() {
		if (self::$core->_user['status'] == 99) {
			return 1;
		}
		return self::$core->_user['status'];
	}
}