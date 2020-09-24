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

Core::Setup();

extract($_REQUEST, EXTR_PREFIX_SAME, '__');    // stops overwriting existing variable values
unset($_REQUEST);unset($_POST);unset($_GET);

$croissant = isset($croissant)?$croissant:''; // from .httaccess
Debug::PointTime('ParseURL');
$response = Core::ParseURL($croissant);

$function = $response['function'];
Debug::SetFunction($function);


if ($response['redirect'] == 1) {
	if (isset($response['code'])) {
		header('Location: '.$response['location'], true, $response['code']);
	} else {
		header('Location: '.$response['location']);
	}
	die();
} else {
	Debug::PointTime('Assign Variables');
	foreach($response as $k => $v) {
		${$k} = $v;
		Core::Assign($k, $v);
	}
}

Debug::PointTime('Bootstrap complete');
if (file_exists(BASEPATH.'/workers/'.$function.'.php')) {
	try {
		Debug::ControllerStart();
		include(BASEPATH.'/workers/'.$function.'.php');
		Debug::ControllerEnd();
	} catch (Exception $e) {
		$e_code = $e->getCode();
		switch ($e_code) {
			case 103:
				Core::Assign('errorMessage', 'Communication error with the data server.');
				Core::Display('errorpages/showerror.tpl');
				die();
			case 7997:
				Core::Display_override('errorpages/nodataserver.tpl');
				die();
			default:
				dump($e);
				die();
		}
	}
	/* ****************************************************************************************************
	 * Include client-specific code
	 ******************************************************************************************************/
	include(DOCROOT.'/'.CLIENT.'.php');

} else {
	Core::Template(NOTFOUND);
	include(BASEPATH.'/workers/404.php');
}

/* ****************************************************************************************************
 * Debugging output
 ******************************************************************************************************/
Debug::DebugOut();

/* ****************************************************************************************************
* This is the final call made by every page - display the selected template.
******************************************************************************************************/
Core::Display();
