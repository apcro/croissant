<?php
/**
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

class Node extends Core {

	static $core;

	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();
		}
		return self::$core;
	}

	/**
	 * Load the details of a single 'node' from the database
	 *
	 * @param integer $nid the id of the node to load
	 * @return array
	 */
	static function Load($nid = 0, $store = false) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$nid = (int)$nid;

		// we store the last-loaded node data in the class so we can reference it easily
		if ($nid == 0) {
			if (self::$core->_nid == 0) {
				return false;
			} else {
				return self::$core->_node;
			}
		}

		$result = ds('node_LoadNode', array('nid' => $nid) );
		if (isset($result['statusCode']) && $result['statusCode'] == 0) {
			if ($store) {
				self::$core->_node = $result['result'];
				self::$core->_nid = $result['result']['nid'];
			}
			return $result['result'];
		} else {
			return false;
		}
	}

	/**
	 * Get node type from $nid
	 * 
	 * @param integer $nid
	 * @return string
	 */
	final static function Type($nid = 0) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$node = self::GetNode($nid);
		return $node['type'];
	}

	/**
	 * Get node from $nid
	 * 
	 * @param integer $nid
	 * @return array
	 */
	final static function GetNode($nid = 0) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if ($nid == 0) {
			if (self::$core->_nid != 0) {
				return self::$core->_node;
			} else {
				return false;
			}
		} else {
			if (self::$core->_nid == $nid) {
				return self::$core->_node;
			} else {
				$node = self::Load($nid);
				self::$core->_node = $node;
				return $node;
			}
		}
	}

	/**
	 * Adds a new node of the indicated type
	 *
	 * @param string $type node type
	 * @return node ID
	 */
	final static function AddNode($type = 'node') {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$response = ds('node_addNode', array('type'  => $type));
		return Core::GenericResponse($response);
	}

	/**
	 * List node by type
	 * 
	 * @param string $type
	 * @return array
	 */
	static function GetNodesByType($type) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (empty($type)) {
			return false;
		}
		$response = ds('node_GetNodesByType', array('type' => $type));
		return Core::GenericResponse($response);
	}
}