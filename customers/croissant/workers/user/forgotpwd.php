<?php
namespace Croissant;

$msg['data'] = array();
$msg['type'] = 'error';
if ($action) {
	if (isset($mail) && !empty($mail) && Email::ValidEmail($mail)) {
		$result = User::LoadUserByMail($mail);
		if ($result != false) {
			$reqpwd_userid = $result['id'];
			$reqpwd_username = $result['name'];
			$key = User::GenKeyForPwdReset();
			$link_resetpwd = "http://".$_SERVER['HTTP_HOST']."/user/resetpwd/".$key['ec_key'];
			if (User::RequestPwdReset($reqpwd_userid, time(), $key['key'])) {
				$subject = "Reset your password";
				$sender = "";
				Core::Assign('name', $reqpwd_username);
				Core::Assign('link', $link_resetpwd);
				$email_data = Core::Fetch("user/forgot_password.tpl");
				$header = array(
					'MIME-Version'				=> '1.0',
					'Content-Type'				=> 'text/html; charset=UTF-8; format=flowed',
					'Content-Transfer-Encoding' => '8Bit',
					'X-Mailer'					=> 'Great Little place',
					'From'						=> 'team'
				);
				Email::Send('', $result['mail'], $subject, $email_data, NULL, $header);
				$template = "user/forgotpwd_completed.tpl";
			} else {
				$msg['data'][] = 'Invalid Email';
				$template="user/forgotpwd.tpl";
			}
		} else {
			$msg['data'][] = 'Invalid Email';
			$template="user/forgotpwd.tpl";
		}
	} else {
		$msg['data'][] = 'Invalid Email';
		$template="user/forgotpwd.tpl";
	}
} else {
	$template="user/forgotpwd.tpl";
}
Core::Assign('msg', $msg);
Core::Assign('dest', $dest);