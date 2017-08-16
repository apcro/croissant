<?php
/*
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2013 Tom Gordon
 *
 */
class Solr extends Core {

	public static function initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::initialise();
		}
		if (!isset(self::$_solr)) {
			if (!defined('SOLR_HOST')) {
				define('SOLR_HOST', 'localhost');
			}
			if (!defined('SOLR_PORT')) {
				define('SOLR_PORT', '8080');
			}
			if (!defined('SOLR_CORE')) {
				define('SOLR_CORE', '');
			}
			if (SOLR_HOST != '') {
				self::$_solr = new Apache_Solr_Service(SOLR_HOST, SOLR_PORT, '/solr'.SOLR_CORE);
			}
		}
		return self::$core;
	}

	private static $_solr;

	/*
	 * Check if the Solr server is running
	 */
	static public function Ping() {
		return self::$_solr->ping();
	}

	/*
	 * Adds a document array for indexing
	 *
	 * @param $document array
	 * @param $update bool default=false
	 */
	static public function IndexDocument($source) {
		$document = new Apache_Solr_Document();
		if (is_array($source)) {
		}

		if (is_array($source)) {
			foreach($source as $sub_item => $sub_data) {
				if (is_array($sub_data)) {
					foreach($sub_data as $sub_array_key => $sub_array_data) {
						$document->setMultiValue($sub_item, $sub_array_data);
					}
				} else {
					$document->setMultiValue($sub_item, $sub_data);
				}
			}
		} else {
			$document->$item = $source;
		}
		try {
			$res = self::$_solr->addDocument($document);
			unset($document);
			return true;
		} catch (Exception $e) {
			echo 'Could not index '.$source['id'].'<br />';
			dump($e);
			ob_flush();
			return false;
		}
	}

	/*
	 * Explicitly remove a document from the index, without calling Optimise() and Commit()
	 */
	static public function DeleteDocument($documentid) {
		self::$_solr->deleteById($documentid);
		return;
	}

	/*
	 * Search
	 *
	 * Searchs the Solr index based on the parameters passed in to the Search method
	 *
	 * extension params
	 * ['extended'] - use $q as individual search elements as well as whole match
	 */
	static public function Search($q, $offset = 0, $limit = 10, $params = array() ) {

		// we'd best clean up $q first - this is a special case
		if (!empty($q)) {
			$q = str_replace(':', '\:', $q);
		}
		// sort out the various parameters and convert to Solr-ready params
		if (!empty($params)) {
			if (!empty($q)) {
				if (isset($params['wild']) && $params['wild'] == true) {
					unset($params['wild']);
					$q = '*'.$q.'*';
				}
				if (isset($params['extended']) && $params['extended'] == true) {
					$keys = "(title:'".$q."'^5 ";
					$words = explode(' ', $q);
					$keys .= ' text:'.implode(' text:', $words).')';
					unset($params['extended']);
				} else {
					$keys = "text:'".$q."'";
				}
				$q2[] = $keys;
			} else {
				// if we have no keyword, get everything
				$q2[] = "title:*";
			}
			if (in_array('classroom', $params['types'])) {
				// extract classroom tag, and add as a tid
				$params['terms'][] = 1098;
				foreach($params['types'] as $k => $v) {
					if ($v == 'classroom') {
						unset($paras['types'][$k]);
					}
				}
			}
			if (isset($params['alltids']) && $params['alltids'] == 'OR') {
				$terms = self::_makeQueryFromParams($params['terms'], 'tid', 'OR');
			} else {
				$terms = self::_makeQueryFromParams($params['terms'], 'tid', 'AND');
			}
			$types = self::_makeQueryFromParams($params['types'], 'content_type', 'OR');

			if (isset($params['dateRange'])) {
				$q2[] = $params['dateRange'];
				unset($params['dateRange']);
			}

			if (isset($params['exclude'])) {
				$q2[] = $params['exclude'];
				unset($params['exclude']);
			}

// 			if (isset($params['sort'])) {
// 				$q2[] = 'sort='.$params['sort'];
// 				unset($params['sort']);
// 			}
			// clear out specific params, leaving generics
			unset($params['types']);
			unset($params['terms']);
			unset($params['alltids']);

			if (!empty($terms)) {
				$q2[] = $terms;
			}
			if (!empty($types)) {
				$q2[] = $types;
			}
		}
		// reset to rebuild query
		$q = implode(' AND ', $q2);

// dump($q);
		// make lat/lon params, if any
		if (isset($params['latitude']) && isset($params['longitude'])) {
			$latlon[] = 'latitude:'.$params['latitude'];
			$latlon[] = 'longitude:'.$params['longitude'];
			$q .= ' AND ('.implode(' AND ', $latlon).')';
			unset($params['latitude']);
			unset($params['longitude']);
		}
// 		dump($q);
// 		dump($params);
// 		die();

		// make the actual call to search
		try {
			if (isset(self::$_solr)) {
				$response = self::$_solr->search($q, $offset, $limit, $params);
				$data = json_decode($response->getRawResponse());
// 				dump($data);
				return $data;
			}
		} catch (Exception $e) {
			Error::ExceptionHandler($e);
// 			echo '2';die();
// 			dump($e);die();
			return false;
		}
	}

	static private function _makeQueryFromParams($data, $field, $joiner) {
		if (is_array($data) && !empty($data)) {
			$data = array_unique($data);
			$query = array();
			foreach($data as $k => $v) {
				if (!empty($v)) {
					$query[] = $field.':'.$v;
				}
			}
		}
		if (!empty($query)) {
			return '('.implode(' '.$joiner.' ', $query).')';
		} else {
			return '';
		}
	}

	static public function Optimise($waitFlush = true, $waitSearcher = true, $timeout = 3600) {
		return self::$_solr->optimize($waitFlush, $waitSearcher, $timeout);
	}

	static public function Commit($expungeDeletes = false, $waitFlush = true, $waitSearcher = true, $timeout = 3600) {
		return self::$_solr->commit($expungeDeletes, $waitFlush, $waitSearcher, $timeout);
	}

	static public function GetHost() {
		return self::$_solr->GetHost();
	}

	static public function DeleteById($id) {
		self::$_solr->deleteById($id);
		self::Commit();
		self::Optimise();
		return;
	}


}