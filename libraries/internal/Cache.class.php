<?php
/*
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

/**
 * Simplified Cache
 *
 * Stores date to either Redis or Disk (defined in configuration)
 *
 * @author Tom Gordon
 *
 * Usage:
 * 	Per-user caching
 * 		Cache::Store(key, datakey[, cachetype[, cache time]])
 * 		Cache::Retrieve(key[, cachetype])
 *
 * Generic caching
 * 		Cache::RawSet(key, data[, cachetype[, cache time]])
 * 		Cache::RawGet(key[, cachetype]);
 *
 */
class Cache extends Core {

	static $core;

	public static function Initialise() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!isset(self::$core)) {
			self::$core = parent::Initialise();
		}
		// default all caching to disk if not defined
		defined('CACHETYPE') or define('CACHETYPE', 'disk');

		return self::$core;
	}

	/**
	 * Store a value in the selected cache using the actual key provided.
	 *
	 * @param string $key
	 * @param mixed $data
	 * @param string $cachetype
	 * @param int $expiretime
	 * @return boolean
	 */
	static final public function RawSet($key, $data, $cachetype = CACHETYPE, $expiretime = 0) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (empty($key) || empty($data)) {
			return false;
		}
		switch (strtolower($cachetype)) {
			case 'disk':
				return self::_storeDisk($key, $data);
				break;
			case 'redis':
				return self::_storeRedis($key, $data, $expiretime);
				break;
			default:
				return false;
		}
	}

	/**
	 * Return a value from the selected cache using the actual key provided.
	 * New expire time can be set on retrieval
	 *
	 * @param string $key
	 * @param string $cachetype
	 * @param int $expiretime
	 */
	static final public function RawGet($key, $cachetype = CACHETYPE, $expiretime = 0) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (empty($key)) {
			return false;
		}
		switch ($cachetype) {
			case 'disk':
				return self::_retrieveDisk($key, $expiretime);
				break;
			case 'redis':
				return self::_retrieveRedis($key, $expiretime);
				break;
			default:
				return false;
		}
	}

	/**
	 * Store a value to the selected cache using a generated key combining the provided key at the current userid
	 *
	 * The cache key is derived from the provided keyname and the currently logged-in user's ID, allowing keyname reuse.
	 *
	 * @param string $key
	 * @param mixed $data
	 * @param string $cachetype
	 * @param int $expiretime
	 */
	static final public function Store($key, $data, $cachetype = CACHETYPE, $expiretime = 3600) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (empty($key) || empty($data)) {
			return false;
		}
		switch (strtolower($cachetype)) {
			case 'disk':
				return self::_storeDisk($key, $data);
				break;
			case 'redis':
				return self::_storeRedis($key, $data, $expiretime);
				break;
			default:
				return false;
		}
	}

	/**
	 * Retrueve a value to the selected cache using a generated key combining the provided key at the current userid
	 *
	 * The cache key is derived from the provided keyname and the currently logged-in user's ID, allowing keyname reuse.
	 * New expire time can be set on retrieval
	 *
	 * @param string $key
	 * @param string $cachetype
	 * @param int $expiretime
	 */
	static final public function Retrieve($key, $cachetype = CACHETYPE, $expiretime = 3600) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (DEBUG) _log('cachetype: '.$cachetype);
		if (empty($key)) {
			return false;
		}
		switch ($cachetype) {
			case 'disk':
				return self::_retrieveDisk($key, $expiretime);
				break;
			case 'redis':
				return self::_retrieveRedis($key, $expiretime);
				break;
			default:
				return false;
		}

	}

	/**
	 * Expire cache by specific key name.
	 * 
	 * @param string $key
	 * @param mixed $cachetype
	 * @return
	 */
	final static function ExpireKey($key, $cachetype = CACHETYPE) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (empty($key)) {
			return false;
		}
		switch ($cachetype) {
			case 'disk':
				return self::_expireDisk($key);
				break;
			default:
				return false;
		}
	}

	/**
	 * Expire cache from local storage by key name.
	 * 
	 * @param string $key
	 * @return
	 */
	static final private function _expireDisk($key) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$c_key = CACHEPATH.'/'.$key.'.cache';
		if (file_exists($c_key)) {
			@unlink($c_key);
			return true;
		}
		return false;
	}

	/**
	 * Get cacahe data from local storage
	 * 
	 * @param string $key
	 * @param int $expire
	 * @return
	 */
	static final private function _retrieveDisk($key, $expire) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$c_key = CACHEPATH.'/'.$key.'.cache';
		if (file_exists($c_key) && filemtime($c_key) < (time() - $expiretime)) {
			if ($fp = fopen($c_key, 'r')) {
				$data = '';
				while (!feof($fp)) {
					$data .= fread($fp, 1024);
				}
				fclose($fp);
				$elements = unserialize($data);
				return $elements;
			}
		} else {
			@unlink($c_key);
		}
		return false;
	}

	/**
	 * Save cache data to local storage
	 * 
	 * @param string $key
	 * @param mixed $data
	 * @return
	 */
	static final private function _storeDisk($key, $data) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$c_key = CACHEPATH.'/'.$key.'.cache';
		if (file_exists($c_key)) {
			@unlink($c_key);
		}
		if ($fp = fopen($c_key, 'w+')) {
			$data = serialize($data);
			fwrite($fp, $data);
			fclose($fp);
			return true;
		}
		return false;
	}

	/**
	 * Save cache data to redis.
	 * 
	 * @param string $key
	 * @param mixed $data
	 * @param integer $ttl
	 * @return
	 */
	static final private function _storeRedis($key, $data, $ttl = 0) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return Redis::Set($key, $data, $ttl);
	}

	/**
	 * Get cache data froom redis.
	 * 
	 * @param string $key
	 * @param integer $expiretime
	 * @return
	 */
	static final private function _retrieveRedis($key, $expiretime = 3600) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return Redis::Get($key);
	}
}