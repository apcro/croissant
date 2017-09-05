<?php
/*
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
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
if (file_exists(BASEPATH.'/libraries/vendor/autoload.php')) {
	require_once(BASEPATH.'/libraries/vendor/autoload.php');
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
				switch($ns_class[0]) {
					case 'JSON':
						include(ROOTPATH.'/libraries/external/JSON.class.php');
						break;
					case 'Smarty':
						include(ROOTPATH.'/libraries/external/Smarty-3.1.13/libs/Smarty.class.php');
						break;
					case 'DOMPDF':
						include(ROOTPATH.'/libraries/external/dompdf/dompdf_config.inc.php');
						break;

					default:
						if (file_exists(ROOTPATH.'/libraries/implements/'.$ns_class[0].'/'.$ns_class[1].'.class.php')) {
							include(ROOTPATH.'/libraries/implements/'.$ns_class[0].'/'.$ns_class[1].'.class.php');
							if (method_exists($class, 'Initialise')) {
								$class::Initialise();
								$_classes_i[$ns_class[1]] = 1;
							}
							$class = $ns_class[1];
						}
						if (file_exists(ROOTPATH.'/libraries/external/'.$ns_class[0].'/'.$ns_class[1].'.class.php')) {
							include(ROOTPATH.'/libraries/implements/'.$ns_class[0].'/'.$ns_class[1].'.class.php');
							$class = $ns_class[1];
							if (method_exists($class, 'Initialise')) {
								$class::Initialise();
								$_classes_i[$ns_class[1]] = 1;
							}
						}
						break;
				}
			}
		}
		$_SESSION['_classes'] = $_classes;
	}
	// only call class initialisation once
	if (!isset($_classes_i[$class])) {
		if (method_exists($class, 'Initialise')) {
			$class::Initialise();
			$_classes_i[$class] = 1;
		}
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
function dump($var, $fb=false) {
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
 * @return
 */
function ds($function, $parameters = array(), $database = '') {
	// if we already have a fatal error, no point in continuing
	if (Error::IsFatal()) {
		return false;
	}

	if (DEBUG) $start = microtime(TRUE);

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
	if ($filepointer = @fopen('http://'.DATASERVER.'/', 'rb', false, $context)) {
		$returndata = stream_get_contents($filepointer);
		$response = unserialize($returndata);
		if (DEBUG) {
			$end = microtime(TRUE);
			$debug = array('func' => $function, 'time' => number_format(($end-$start) * 1000, 2), 'sql' => isset($response['sql'])?$response['sql']:'No sql for function '.$function, 'memory' => isset($response['peak_memory'])?$response['peak_memory']:'');
			Core::SetDebugData($debug);
		}
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
	if (DEBUG != 1) return;
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

/**
 * Get svn version.
 *
 * @param string $file
 * @return
 */
function get_svn_version($file = '') {
	if ($file != '') {
		$check = dirname($file). DIRECTORY_SEPARATOR . '.svn' . DIRECTORY_SEPARATOR . 'entries';
	} else {
		$check = BASEPATH . DIRECTORY_SEPARATOR . '.svn' . DIRECTORY_SEPARATOR . 'entries';
	}
	if (file_exists($check)) {
		$svn = file($check);
		if (is_numeric(trim($svn[3]))) {
			$base_version = (int) trim($svn[3]);
		} else { // pre 1.4 svn used xml for this file
			$parts = explode('"', $svn[4]);
			$base_version = (int) trim($parts[1]);
		}
		if ($file != '') {
			$version = 0;
			$filename = basename($file);
			foreach($svn as $k => $v) {
				if (trim($v) == $filename) {
					if (!empty($svn[$k+2])) {
						$version = $svn[$k+2];
						break;
					}
				}
			}
			if ($version == 0) {
				$version = $base_version;
			}
		} else {
			$version = $base_version;
		}
		unset($svn);
	} else {
		$version = 0;
	}
	return $version;
}

/**
 * Returns either the data call response data or false.
 *
 * @param array $response
 * @return
 */
function genericResponse($response) {
	if (isset($response['statusCode']) && $response['statusCode'] == 0) {
		return $response['result'];
	} else {
		return $response;
		return false;
	}
}
