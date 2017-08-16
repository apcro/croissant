<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-2017 Tom Gordon
 * @author Tom Gordon
 * @version 0.1 09-05-2011
 */
namespace Croissant;

// Core defines
// Mostly used for error handling

defined('DATA_ERROR')			or define('NORMAL_ERROR', 0);
defined('NODATA_ERROR')			or define('NODATA_ERROR', 1);
defined('SYSTEM_ERROR')			or define('SYSTEM_ERROR', 2);
defined('FATAL_ERROR')			or define('FATAL_ERROR', 3);
defined('SUBSCRIPTION_ERROR')	or define('SUBSCRIPTION_ERROR', 4);

defined('RPC_ERROR')			or define('RPC_ERROR', 96);
defined('METHOD_ERROR')			or define('METHOD_ERROR', 97);
defined('DATABASE_ERROR')		or define('DATABASE_ERROR', 98);
defined('DATASERVER_ERROR')		or define('DATASERVER_ERROR', 99);

// Subscription constants
define('SUBSCRIPTION_TYPE_FREE', 'free');
define('SUBSCRIPTION_TYPE_REGISTERED', 'registered');
define('SUBSCRIPTION_TYPE_SUBSCRIBER', 'subscriber');

// device support
defined('USE_MOBILE')			or define('USE_MOBILE', false);
defined('USE_HTML5')			or define('USE_HTML5', false);

// Message defines (used in Friend.class and friend.library
define('MESSAGE',			0);
define('FRIEND_REQUEST',	1);
define('FRIEND_ACCEPT',		2);
define('FRIEND_REJECT',		3);
define('SHARE', 			4);

const UNPUBLISHED	= 0;
const PUBLISHED		= 1;
const DELETED		= 2;
const FLAGGED		= 3;
const USER_BLOCKED	= 4;
const USER_BANNED	= 5;
const DELIVERED		= 6;
const EDIT			= 7;
const REVIEW		= 8;
const AWAITING_MODERATION	= 8;
const READY			= 9;
