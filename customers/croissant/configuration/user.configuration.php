<?php
/**
 * 
 * User Session data configuration
 * Defines keys used in the user session object, retrieved by User::Login() and used in Session::Start()
 * Should match the data keys returned from the database, but reassignment is available
 * 
 * if the key is an array, the following subkeys are available:
 * - type: defines the data type for explicit typecasting on storage, either 'int' or 'string'
 * - name: new keyname to store data against. May be duplicated
 */

namespace Croissant;

Core::$core->_user_session = array(
		'id',		// required
		'mail',
		'username',
		'modified',
		'status',
	);