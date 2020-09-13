<?php
namespace Croissant;

$os = strtolower(php_uname());
$pos = strpos($os, 'windows');

if ($pos === false) {
	$seperator = '/';
} else {
	$seperator = '\\';
}

$path = explode($seperator, dirname(__FILE__));

// lose the current path - /configuration
array_pop($path);

// this is the actual customer
$client = array_pop($path);
define('CLIENT', $client);

// this is the subfolder we're storing customer code ine
$store = array_pop($path);

// reset the path
$rootpath = implode($seperator, $path);
$path = $rootpath.$seperator.$store.$seperator.$client;
require_once('local.configuration.php');

define('ROOTPATH', $rootpath);
define('BASEPATH', $path);

define('DOCROOT', BASEPATH.'/docroot');

define('SMARTY_TEMPLATE',	BASEPATH.'/templates');
define('SMARTY_COMPILE',	CACHEPATH.'/templates_c');
define('SMARTY_CACHE',		CACHEPATH.'/smarty_cache');

define('NOTFOUND',		'shared/error/404.tpl');

// should we use browser caching?
defined('USE_CACHING') || define('USE_CACHING', false);

const OK	= 0;
const NOTOK	= 1;

// File caching definitions
define('FILECACHE', CACHEPATH);

define('STATELESS', 0); // 0 == normal, 1 = load session data from the dataserver

// Default Text for META data
define('DEFAULT_PAGE_TITLE',	'');
define('DEFAULT_PAGE_META',		'');
define('DEFAULT_PAGE_KEYWORDS',	'');

// Base template
defined('BASE_TEMPLATE') or define('BASE_TEMPLATE', 'shared/default.tpl');

define('IS_AJAX_REQUEST', (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'));

// now we get can the core library, if not a CSS file
if (!strpos($_SERVER['SCRIPT_NAME'], 'css') && !strpos($_SERVER['SCRIPT_NAME'], 'images') && !strpos($_SERVER['SCRIPT_NAME'], 'js') && !strpos($_SERVER['REQUEST_URI'], 'css') ) {
	require_once(ROOTPATH.'/libraries/core/core.library.php');
}
