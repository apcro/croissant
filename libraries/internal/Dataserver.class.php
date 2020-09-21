<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
namespace Croissant;

class Dataserver extends Core {
	static $core;

	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();

		}
		return self::$core;
	}

	/**
	 * Data server caller
	 *
	 * @param string $function
	 * @param mixed $parameters
	 * @param string $database
	 * @return mixed
	 */
	public static function Write($library, $function, $parameters = array(), $database = '', $readonly = false) {
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
		$data = array(	'method' => $library.'_'.$function, 						// method call
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

		$dataserver = $readonly?DATASERVER_READONLY:DATASERVER;
		
		if ($filepointer = @fopen($dataserver, 'rb', false, $context)) {
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
			Error::AddError('Dataserver not found: '.$dataserver, DATASERVER_ERROR, '');	// this is a fatal error, and will display the maintenance page
		}
		if (empty($response)) {
			$response = array();
			$response['statusCode'] = 1;
			$response['error'] = 'Data error - see errorData';
			$response['errorData'] = (!empty($returndata)?$returndata:'There is an error in the dataserver somewhere - there was no response at all');
			$response['dataServer'] = $dataserver;
			$response['method'] = $function;
			Error::AddError($response['error'], DATA_ERROR, $response['errorData']);
			dump('Execution error - best fix this first');
			dump($response);die();
		}
		unset($context);
		return $response;
	}

	/**
	 * Data server caller - specific Read Only version
	 *
	 * @param string $function
	 * @param mixed $parameters
	 * @param string $database
	 * @return
	 */
	function Read($library, $function, $parameters = array(), $database = '') {
		return self::Write($library, $function, $parameters, $database, true);
	}

}