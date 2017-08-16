<?php
namespace Croissant;

$key = isset($key)? $key : $args[1];
if ($key == ''){
	session_write_close();
	header('Location: /');
	die();
} else {
	Core::Assign('key', $key);
}
$req_key = User::GetKeyForPwdReset($key);
$chk_expire = User::CheckPwdResetExpire($req_key['key']);

if (!$chk_expire) {
	session_write_close();
	header('Location: /user/forgotpwd');
	die();
} else {
	$res_userid = $chk_expire;
}
$profile = User::LoadUser($res_userid);
if ($action) {
	if (isset($mail) && !empty($mail)
	&& isset($passpwd) && !empty($passpwd)
	&& isset($confirm_passpwd) && !empty($confirm_passpwd)
	&& $passpwd == $confirm_passpwd
	&& Email::ValidEmail($mail)
	&& $profile['mail'] == $mail) {
		$data = array(
			'pwdreset_time' => ' ',
			'pwdreset_key'  => ' ',
			'pass'			=> $passpwd,
			'uid'			=> $res_userid
		);
		$result = User::UpdateUser($data);
		if ($result){
			$template = "user/resetpwd_completed.tpl";
		} else {
			$msg['data'][] = 'Password reset incompleted';
			$template="user/resetpwd.tpl";
		}

	} else {
		$msg['data'][] = 'Invalid Email [OR] New Password do not match';
		$template="user/resetpwd.tpl";
	}
} else {
	$template="user/resetpwd.tpl";
}

Core::Assign('msg', $msg);
Core::Assign('dest', $dest);