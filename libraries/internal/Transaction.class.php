<?php
/**
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

Class Transaction extends Core {

	static $core;
	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();
		}
		return self::$core;
	}

	static protected $transactions = array();
 
	/**
	 * Add a transaction whose response is ultimately directly assigned for output
	 * 
	 * @param string $method
	 * @param mixed $return
	 * @param array $params
	 * @param string $client
	 * @param string $database
	 * @return void
	 */
	static function ToTemplate($method, $return, $params = '', $client = '', $database = '') {
		if (!empty($method) && !empty($return)) {
			self::$transactions[] = array('method' => $method, 'params' => $params, 'return' => $return, 'client' => $client, 'database' => $database);
		}
	} 
	/**
	 * Processes all stored transaction as one batch transaction
	 * 
	 * @return
	 */
	static function Process() {
		if (empty(self::$transactions)) {
			return;
		}

		$response = ds('transactions_Process', array('transactions' => self::$transactions));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			$transaction = $response['result'];
			foreach ($transaction as $transaction) {
				if (isset($transaction['statusCode']) && $transaction['statusCode'] == 0) {
					Core::Assign($transaction['returnvar'], $transaction['result']);
				}
			}
		}
	}

}
?>