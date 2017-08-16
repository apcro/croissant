<?php
// User Session data configuration
// Defines keys used in the user session object, retrieved by User::Login() and used in Session::Start()
// Should match the data keys returned from the database
namespace Croissant;

Core::$core->_user_session = array(
		'userid',		// required
		'userstatus',
		'useremail',
		'userfirstname',
		'userlastname',
		'userphone',
	);

