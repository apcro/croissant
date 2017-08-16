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
 * Croisssant Web Framework
 * @copyright 2009-2017 Tom Gordon
 *
 * @author Tom Gordon
 *
 * Provides a wrapper for accessing redis key/value server
 *
 * Native implementation using sockets
 *
 */
class Redis extends Core {
	static $core;
	private static $_redis;
	private static $_redis_alive;

	public function __destruct() {
		if (self::$_redis) {
			fclose(self::$_redis);
		}
	}

	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();

		}
		if (!isset(self::$_redis)) {
			self::$_redis = @fsockopen(REDIS_SERVER, REDIS_PORT, $error, $message);
			if (self::$_redis === false) {
	 			self::$_redis_alive = false;
			} else {
	 			self::$_redis_alive = true;
	 			self::_write('select', array(REDIS_DATABASE));
			}
		}
		return self::$core;
	}

	/**
	 * (private) save data to redis
	 * 
	 * @param string $method
	 * @param array $params
	 * @return array
	 */
	static final private function _write($method, $params) {
		$command  = '*'.(count($params) + 1)."\r\n";
		$command .= '$'.strlen($method)."\r\n";
		$command .= strtoupper($method)."\r\n";
		foreach ($params as $param) {
			$command .= '$'.strlen($param)."\r\n".$param."\r\n";
		}
		fwrite(self::$_redis, $command);

		$response = trim(fgets(self::$_redis, 512));
		switch (substr($response, 0, 1)) {
			case '-':
				throw new \Exception('Redis error: '.substr(trim($response), 4));
			case '+':
			case ':':
				return substr(trim($response), 1);
			case '$':
				return self::_row($response);
			case '*':
				return self::_multirow($response);
			default:
				throw new \Exception("Unknown Redis response: ".substr($response, 0, 1));
		}
	}

	/**
	 * Get single row data
	 * 
	 * @param string $data
	 * @return string
	 */
	static final private function _row($data) {
		if ($data == '$-1') return;
		list($read, $response, $size) = array(0, '', substr($data, 1));
		do {
			// Calculate and read the appropriate bytes off of the Redis response.
			// We'll read off the response in 1024 byte chunks until the entire
			// response has been read from the database.
			$block = (($remaining = $size - $read) < 1024) ? $remaining : 1024;
			$response .= fread(self::$_redis, $block);
			$read += $block;
		} while ($read < $size);

		// The response ends with a trailing CRLF. So, we need to read that off
		// of the end of the file stream to get it out of the way of the next
		// command that is issued to the database.
		fread(self::$_redis, 2);
		return $response;
	}

	/**
	 * Get multi row data
	 * 
	 * @param string $data
	 * @return array
	 */
	static final private function _multirow($data) {
		if (($count = substr($data, 1)) == '-1') {
			return;
		}
		$response = array();
		for ($i = 0; $i < $count; $i++) {
			$response[] = self::_row(trim(fgets(self::$_redis, 512)));
		}
		return $response;
	}

	/**
	 * Set redis
	 * 
	 * @param mixed $key
	 * @param mixed $value
	 * @param integer $ttl
	 * @param bool $overwrite
	 * @return bool
	 */
	static final public function Set($key, $value, $ttl = 0, $overwrite = false) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (self::$_redis_alive) {
			if (is_array($value)) {
				$value = serialize($value);
			}
			if ($ttl != 0) {
				self::_write('setex', array($key, $ttl, $value));
			} else {
				if ($overwrite) {
					self::_write('setnx', array($key, $value));
				} else {
					self::_write('set', array($key, $value));
				}
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get redis.
	 * 
	 * @param string $key
	 * @return string
	 */
	static final public function Get($key) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (DEBUG) _log("\t".'key: '.$key);
		if (self::$_redis_alive) {
			$value = self::_write('get', array($key));
			if (@unserialize($value) || $value == 'b:0;') {
				return unserialize($value);
			} else {
				return $value;
			}
		} else {
			return false;
		}
	}

	/**
	 * Delete 
	 * 
	 * @param string $key
	 * @return
	 */
	static final public function Delete($key) {
		if (self::$_redis_alive) {
			return self::_write('del', array($key));
		} else {
			return false;
		}
	}

	/**
	 * Delete all the keys of the currently selected DB. This command never fails.
	 * 
	 * @return
	 */
	static final public function Flush() {
		return self::_write('flushdb', array());
	}


	/**
	 * Returns the value associated with field in the hash stored at key.
	 * 
	 * @param string $key
	 * @param string $field
	 * @return
	 */
	static final public function HashGet($key, $field) {
		return self::_write('hget', array($key, $field));
	}

	/**
	 * Sets field in the hash stored at key to value. If key does not exist, a new key holding a hash is created. If field already exists in the hash, it is overwritten.
	 * 
	 * @param string $key
	 * @param string $field
	 * @param string $value
	 * @return
	 */
	static final public function HashSet($key, $field, $value) {
		return self::_write('hset', array($key, $field, $value));
	}

	/**
	 * Returns all fields and values of the hash stored at key. In the returned value, every field name is followed by its value, so the length of the reply is twice the size of the hash.
	 * 
	 * @param string $key
	 * @return array
	 */
	static final public function HashGetAll($key) {
		return self::_write('hgetall', array($key));
	}

	/**
	 * Returns all field names in the hash stored at key.
	 * 
	 * @param string $key
	 * @return array
	 */
	static final public function HashKeys($key) {
		return self::_write('hkeys', array($key));
	}

	/**
	 * Returns the number of fields contained in the hash stored at key.
	 * 
	 * @param string $key
	 * @return int
	 */
	static final public function HashLength($key) {
		return self::_write('hlen', array($key));
	}

	/**
	 * Multiple sets field in the hash stored at key to value.
	 * 
	 * @param string $key
	 * @param mixed $values
	 * @return
	 */
	static final public function HashMultiSet($key, $values) {
		$fields = '';
		foreach ($values as $k => $v) {
			$v['item'] = $k;
			self::_write('hmset', array($key.':'.$k, $v));
		}
		return;
	}

	/**
	 * Returns the specified elements of the list stored at key. The offsets start and stop are zero-based indexes, with 0 being the first element of the list (the head of the list), 1 being the next element and so on.
	 * 
	 * @param string $key
	 * @param int $start
	 * @param int $end
	 * @return
	 */
	static final public function ListRange($key, $start, $end) {
		return self::_write('lrange', array($key, $start, $end));
	}

	/**
	 * Trim an existing list so that it will contain only the specified range of elements specified.
	 * 
	 * @param string $key
	 * @param int $start
	 * @param int $end
	 * @return
	 */
	static final public function ListTrim($key, $start, $end) {
		return self::_write('ltrim', array($key, $start, $end));
	}

	/**
	 * Returns the element at index index in the list stored at key.
	 * 
	 * @param string $key
	 * @param int $start
	 * @return
	 */
	static final public function ListIndex($key, $start) {
		return self::_write('lindex', array($key, $start));
	}

	/**
	 * Insert all the specified values at the head of the list stored at key. If key does not exist, it is created as empty list before performing the push operations.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	static final public function ListPush($key, $value) {
		if (is_array($value)) {
			foreach($value as $v) {
				self::_write('lpush', array($key, $v));
			}
		} else {
			self::_write('lpush', array($key, $value));
		}
	}

	/**
	 * Removes and returns the first element of the list stored at key.
	 * 
	 * @param string $key
	 * @return
	 */
	static final public function ListPop($key) {
		return self::_write('lpop', array($key));
	}

	/**
	 * Returns the length of the list stored at key. If key does not exist, it is interpreted as an empty list and 0 is returned. 
	 * 
	 * @param string $key
	 * @return int
	 */
	static final public function ListLength($key) {
		return self::_write('llen', array($key));
	}
}