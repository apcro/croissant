<?php
namespace Croissant;

if (User::UserID() != 0) {
	header('Location: /');
	die();
}

$template = 'user/login.tpl';
$page_title = 'Log in';
if (isset($submit) && $submit == 'Log in'){
	$data = User::Login($mail, $pass, isset($remember)?$remember:FALSE);
	if (!$data) {
		Core::Assign('login_message','Login Failed. Please enter a valid email address and password.');
	} else {
		$dest = Session::GetVariable('dest');
		Session::SetVariable('dest', '');
		if (isset($dest) && !empty($dest)) {
			header('Location: '.$dest);
		} else {
			header('Location: /');
		}
		die();
	}
}