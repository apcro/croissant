<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
namespace Croissant;

Class Error extends Core {

	static $core;
	/**
	 * Initialise 
	 * 
	 * @return
	 */
	public static function Initialise() {
		if (!isset(self::$core)) {
			self::$core = parent::Initialise();
			self::$core->fatal = false;
		}
		return self::$core;
	}

	/**
	 *
	 * Add an error to the list of detected errors
	 */
	final static function AddError($errorMessage, $errorType, $errorData) {
		self::$core->errors[] = array('message' => $errorMessage, 'data' => $errorData, 'type' => $errorType);
		switch ($errorType) {
			case SOLR_ERROR;
			case DATASERVER_ERROR:
			case DATABASE_ERROR:
			case RPC_ERROR:
				self::$core->fatal = true;
				break;
			default:
				self::$core->fatal = false;
		}
	}

	/**
	 * Check for fatal error.
	 * 
	 * @return bool
	 */
	final static function IsFatal() {
		return self::$core->fatal;
	}

	static function ExceptionHandler($e) {
		self::AddError($e->getMessage(), SOLR_ERROR, $e);
	}

}