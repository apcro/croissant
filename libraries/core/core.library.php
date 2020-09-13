<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
namespace Croissant;

// Core include paths
set_include_path(get_include_path().PATH_SEPARATOR.ROOTPATH.'/libraries/'.PATH_SEPARATOR.ROOTPATH.'/libraries/external'.PATH_SEPARATOR.ROOTPATH.'/libraries/core'.PATH_SEPARATOR.ROOTPATH.'/libraries/external/Tera-WURFL');

// add customer's workers path
set_include_path(get_include_path().PATH_SEPARATOR.BASEPATH.'/workers/');

// change autoload extension preferences
spl_autoload_extensions('.php, .inc');
spl_autoload_register ('Croissant\__autoload' );

// add static DEFINEs
require_once(ROOTPATH.'/libraries/core/core.defines.php');

// load any composer-based packages
if (file_exists(ROOTPATH.'/libraries/vendor/autoload.php')) {
	require_once(ROOTPATH.'/libraries/vendor/autoload.php');
}

// Start the static object
Core::Initialise();
Error::Initialise();

set_exception_handler(array('Error','ExceptionHandler'));

/**
 * Class autoloader
 *
 * We don't always use all classes, so only load those classes actually used.
 *
 * Support for customer or third-party specific libraries is supported by checking for third party classes first
 * Support for complete replacement is also implemented in this way
 *
 * Support for implementing third-party libraries implements inside /libraries/implements
 *
 * Search order:
 * 		/customers/{customer}/libraries/{class}.class.php
 * 		/libraries/implements/{class}.class.php
 * 		/libraries/internal/{class}.class.php
 * 		/libraries/external/{class} by lookup
 * @param string $class
 */

function __autoload($class) {
	$_classes = isset($_SESSION['_classes'])?$_SESSION['_classes']:array();
	$_classes_i = isset($_SESSION['_classes_i'])?$_SESSION['_classes_i']:array();

	$class = ltrim($class, '\\');
	$filename  = '';
	$namespace = '';
	if ($lastNsPos = strripos($class, '\\')) {
		$namespace = substr($class, 0, $lastNsPos);
		$class = substr($class, $lastNsPos + 1);
		$filename  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
	}
	$filename .= str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

	if (isset($_classes[$class])) {
		include_once($_classes[$class]['url']);
	} else {
		if (file_exists(BASEPATH.'/libraries/'.$class.'.class.php')) {
			$_classes[$class]['url'] = BASEPATH.'/libraries/'.$class.'.class.php';
			include_once($_classes[$class]['url']);
		} elseif (file_exists(ROOTPATH.'/libraries/implements/'.$class.'.class.php')) {
			$_classes[$class]['url'] = ROOTPATH.'/libraries/implements/'.$class.'.class.php';
			include_once($_classes[$class]['url']);
		} elseif (file_exists(ROOTPATH.'/libraries/internal/'.$class.'.class.php')) {
			$_classes[$class]['url'] = ROOTPATH.'/libraries/internal/'.$class.'.class.php';
			include_once($_classes[$class]['url']);
		} else {
			// PSR-0 namespaced implementation
			$class = ltrim($class, '\\');
			$filename  = '';
			$namespace = '';
			if ($lastNsPos = strripos($class, '\\')) {
				$namespace = substr($class, 0, $lastNsPos);
				$class = substr($class, $lastNsPos + 1);
				$filename  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
			}
			$filename .= str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';

			if (file_exists(ROOTPATH.'/libraries/vendor/'.$filename)) {
				include(ROOTPATH.'/libraries/vendor/'.$filename);
			} else {
				// supports both namespaced and non-namespaced third party classes
				// also checks the /implements/ folder if no explicit match is found
				$ns_class = explode('\\', $class);
				if (file_exists(ROOTPATH.'/libraries/implements/'.$ns_class[0].'/'.$ns_class[1].'.class.php')) {
					include(ROOTPATH.'/libraries/implements/'.$ns_class[0].'/'.$ns_class[1].'.class.php');
					if (method_exists($class, 'Initialise')) {
						$class::Initialise();
						$_classes_i[$ns_class[1]] = 1;
					}
					$class = $ns_class[1];
				}
				if (file_exists(ROOTPATH.'/libraries/external/'.$ns_class[0].'/'.$ns_class[1].'.class.php')) {
					include(ROOTPATH.'/libraries/external/'.$ns_class[0].'/'.$ns_class[1].'.class.php');
					$class = $ns_class[1];
					if (method_exists($class, 'Initialise')) {
						$class::Initialise();
						$_classes_i[$ns_class[1]] = 1;
					}
				}
			}
		}
		$_SESSION['_classes'] = $_classes;
	}
	// only call class initialisation once per session
	if (!isset($_classes_i[$class]) && method_exists($class, 'Initialise')) {
		$class::Initialise();
		$_classes_i[$class] = 1;
	}
	$_SESSION['_classes_i'] = $_classes_i;
}

/**
 * Print out varables.
 *
 * @param mixed $var
 * @param bool $fb
 * @return void
 */
function dump($var) {
	echo '<pre>';
	if (empty($var)) {
		echo '(passed variable was empty)';
	} else {
		print_r($var);
	}
	echo '</pre>';
}

/**
 * Data server caller
 *
 * @param string $function
 * @param mixed $parameters
 * @param string $database
 * @return mixed
 */
function ds($function, $parameters = array(), $database = '') {
	// if we already have a fatal error, no point in continuing
	if (Error::IsFatal()) {
		return false;
	}

	if ($database == '') {
		$database = (DATABASE!='DATABASE'?DATABASE:'');
	}

	if ($database == '1') {
		echo 'Mis-implemented dataserver - check the parameters:<br />';
		dump(debug_backtrace());die();
	}

	$response = '';
	$data = array(	'method' => $function, 						// method call
					'params' => serialize($parameters), 		// paramters for actual call
					'database' => $database, 					// which database to use if not default
					'client' => (CLIENT!='CLIENT'?CLIENT:''),	// which client codebase is making the call
					'userid' => User::UserID(),					// the current UserID to allow for content segmentation within the dataserver
				);
	$senddata = serialize($data);

	$headers = array(	'http' => array(
						'method' => 'POST',
						'header' => 'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
						'content' => $senddata
					));
	$context = stream_context_create($headers);

	if ($filepointer = @fopen(DATASERVER, 'rb', false, $context)) {
		$returndata = stream_get_contents($filepointer);
		$response = unserialize($returndata);
		if (isset($response['statusCode']) && $response['statusCode'] != OK) {
			Error::AddError($response['errorMessage'], $response['errorType'], $response['errorData']);
		}
		fclose($filepointer);
	} else {
		$response = array();
		$response['statusCode'] = 1;
		$response['error'] = 'Dataserver not found';
		$response['errorData'] = 'Calling the Dataserver failed. Please check that there is network connectivity and there is no error within the dataserver configuration.';
		Error::AddError('Dataserver not found: '.DATASERVER, DATASERVER_ERROR, '');	// this is a fatal error, and will display the maintenance page
	}
	if (empty($response)) {
		$response = array();
		$response['statusCode'] = 1;
		$response['error'] = 'Data error - see errorData';
		$response['errorData'] = (!empty($returndata)?$returndata:'There is an error in the dataserver somewhere - there was no response at all');
		$response['dataServer'] = DATASERVER;
		$response['method'] = $function;
		Error::AddError($response['error'], DATA_ERROR, $response['errorData']);
		dump('Execution error - best fix this first');
		dump($response);die();
	}
	unset($context);
	return $response;
}

/**
 * Log.
 *
 * @param string $data
 * @return
 */
function _log($data) {
	if (!strpos($_SERVER['QUERY_STRING'], 'images')) {
		if ($data == 'Page end') {
			$extra = "\n\n-------------------------\n\n";
		} elseif ($data == 'Page start') {
			$preextra = "\n\n";
			$extra = " -----------------------------\n";
		} else {
			$extra = "\n";
		}
		$data = print_r($data, true);
		$logfile = TEMP_PATH . '/croissant.debug_logfile.log';
		$data = $preextra.'['.date('d/m/Y H:m:s', time()).';'.number_format((microtime(true) - $_SESSION['last']) * 1000, 2).'ms] TM: '.$data.$extra;
		$fp = fopen($logfile, 'a+');
		fwrite($fp, $data, strlen($data));
		fclose($fp);
	}
}
