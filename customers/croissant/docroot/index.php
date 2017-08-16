<?php
/**
 * Croisssant Web Framework
 *
 * @author Tom Gordon
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;


error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_STRICT);
// error_reporting(E_ALL & ~E_NOTICE);
// error_reporting(E_ALL);

require_once('../configuration/configuration.php');

if (strpos($_SERVER['REQUEST_URI'], 'css')) {
	include(BASEPATH.'/workers/css.php');
	die();
}

if (strpos($_SERVER['REQUEST_URI'], 'js')) {
	include(BASEPATH.'/workers/js.php');
	die();
}

if (isset($_SERVER['REQUEST_URI'])) {
	if (strstr($_SERVER['REQUEST_URI'], 'croissant=')) {
		header('location: /');
		die();
	}
}

$_REQUEST = Sanitiser::FILTER_XSS_CLEAN($_REQUEST);
$_POST = Sanitiser::FILTER_XSS_CLEAN($_POST);
$_GET = Sanitiser::FILTER_XSS_CLEAN($_GET);
$_COOKIE = Sanitiser::FILTER_XSS_CLEAN($_COOKIE);

extract($_REQUEST, EXTR_PREFIX_SAME, '__');    // stops overwriting existing variable values
unset($_REQUEST);unset($_POST);unset($_GET);

// if the browser is IE6, drop out completely
if (strstr($_SERVER['HTTP_USER_AGENT'], 'MSIE 6') || $ie6 == 1) {
	$template = 'shared/error/ie6.tpl';
	Core::Assign('ie6', true);
	Core::Assign('nocookie', 1);
	Core::Display($template);
	die();
} else {
	Core::Assign('ie6', false);
}

if (DEBUG) {
	$page_start = microtime(TRUE);
	$point_timer = array();
	$point_timer[] = array('start execution', microtime(TRUE));
	if (DEBUG) $point_timer[] = array('Started parsing URL', microtime(TRUE));
}

$croissant = isset($croissant)?$croissant:''; // from .httaccess
$response = Core::ParseURL($croissant);

$function = $response['function'];
if (DEBUG) $point_timer[] = array('Ended parsing URL', microtime(TRUE));

if ($response['redirect'] == 1) {
	if (isset($response['code'])) {
		header('Location: '.$response['location'], true, $response['code']);
	} else {
		header('Location: '.$response['location']);
	}
	die();
} else {
	foreach($response as $k => $v) {
		${$k} = $v;
		Core::Assign($k, $v);
	}
}

$template = NOTEMPLATE;
if (DEBUG) $point_timer[] = array('Calling function', microtime(TRUE));
if (file_exists(BASEPATH.'/workers/'.$function.'.php')) {
	try {
		// we're going to load a worker, so even if we don't display a page,
		// push this onto the top of the JS output list
		Core::AddJavascript('croissant.js');

		if (DEBUG) $controller_start = microtime(TRUE);
		include(BASEPATH.'/workers/'.$function.'.php');
		if (DEBUG) $controller_end = microtime(TRUE);
		Core::PageTitle(!empty($page_title)?$page_title:DEFAULT_PAGE_TITLE);
		Core::PageMeta(!empty($page_meta)?$page_meta:DEFAULT_PAGE_META);
		Core::PageKeywords(!empty($page_keywords)?$page_keywords:DEFAULT_PAGE_KEYWORDS);
		Core::Assign('page_og_url' , Core::GetCurrentUrl()) ;
	} catch (Exception $e) {
		$e_code = $e->getCode();
		switch ($e_code) {
			case 103:
				Core::Assign('errorMessage', 'Communication error with the data server.');
				if (DEBUG) {
					Core::Assign('errorData', nl2br($e));
				}
				Core::Display('errorpages/showerror.tpl');
				die();
				break;
			case 7997:
				Core::Display_override('errorpages/nodataserver.tpl');
				die();
				break;
			default:
				dump($e);
				die();
				break;
		}
	}
} else {
	$template = NOTFOUND;
}
if (DEBUG) $point_timer[] = array('Finished function', microtime(TRUE));

/* ****************************************************************************************************
 * Include client-specific code
 ******************************************************************************************************/
include(DOCROOT.'/'.CLIENT.'.php');

/* ****************************************************************************************************
 * Include iPad support, if needed
******************************************************************************************************/
if (Core::$core->_iPad || Core::$core->_iPhone) {
	Core::Assign('iPad', 1);
	Core::AddJavascript('iPad.js');
} else {
	Core::Assign('iPad', 0);
}

// Include the 404 worker
if ($template == NOTFOUND) {
	include(BASEPATH.'/workers/404.php');

	// we need to override the previously set data
	Core::PageTitle(!empty($page_title)?$page_title:DEFAULT_PAGE_TITLE);
	Core::PageMeta(!empty($page_meta)?$page_meta:DEFAULT_PAGE_META);
	Core::PageKeywords(!empty($page_keywords)?$page_keywords:DEFAULT_PAGE_KEYWORDS);
}


/* ****************************************************************************************************
 * Debugging output
 ******************************************************************************************************/
if (DEBUG) include('debug.php');

/* ****************************************************************************************************
 * This is the final call made by every page - display the selected template.
 ******************************************************************************************************/
Core::Display($template);
