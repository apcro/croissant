<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
namespace Croissant;

if (User::UserID() == 0) {
	switch($args[0]) {
		case 'register':
			include('user/register.php');
			break;
		case 'login':
			include('user/login.php');
			break;
		case 'resetpwd':
			include('user/resetpwd.php');
			break;
		case 'forgotpwd':
			include('user/forgotpwd.php');
			break;
		default:
			break;

	}
} else {
	switch($args[0]) {
		case 'logout':
			include('user/logout.php');
			break;
		default:
			header('Location: /');
			die();
	}
}