<?php
/**
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

class Taxonomy extends Core {
	static $core;

	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();
		}
		return self::$core;
	}

	/**
	 * Return the vocabulary object matching a vocabulary ID.
	 *
	 * @param integer $vid The vocabulary's ID
	 * @return mixed - array on success, false on failure
	 */
	static function GetVocabulary($vid) {
		$response = ds('taxonomy_GetVocabulary', array('vid' => $vid));

		if (isset($response['statusCode']) && $response['statusCode']==0) {
			$node_types = array();
			foreach($response['result'] as $voc) {
				$node_types[] = $voc['type'];
				unset($voc->type);
				$voc['nodes'] = $node_types;
			}
		}
		return $response;
	}

	/**
	 * Get all terms for a given node within one vocabulary
	 * @param $nid the relevant node id
	 * @param $vid the relevant vocabulary id
	 * @param $key unused
	 * @return mixed - array on success, false on failure
	 * @todo check deprecated!
	 */
	static function NodeGetTermsByVocabulary($nid, $vid, $key = 'tid') {
		$response = ds('taxonomy_NodeGetTermsByVocabulary', array('nid' => $nid, 'vid' => $vid));
		$terms = array();
		if (isset($response['statusCode']) && ($response['statusCode'] == 0) && !empty($response['result'])) {
			foreach($response['result'] as $term) {
				$terms[$term[$key]] = $term;
			}
		}
		return $terms;
	}

	/**
	 * Get all terms for a given Taxonomy
	 * By default, this is cached to disk for speed. The Taxonomy cache expires every hour (60*60 seconds)
	 * A full refresh may be forced.
	 * @param int $vid the vocabulary term to search for
	 * @param boolean $refresh - force a refresh from the database if true
	 * @param boolean $all - should the method return all Taxonomy Terms even if no known videos? (default true)
	 * @return array $terms
	 */
	static function GetTermsByVocabulary($vid, $refresh = false, $all = true) {

		$path = CACHEPATH.'/taxonomy/';
		$file = $path.'vocab_'.$vid.'.cache';
		if (!file_exists($path)) {
			mkdir($path);
		}

		if ( ((file_exists($file) && filemtime($file) < (time()-(60*60)) ) || !file_exists($file)) || $refresh) {
			// update the cache
			$response = ds('taxonomy_GetTermsByVocabulary', array('vid' => $vid));
			if (isset($response['statusCode']) && $response['statusCode']==0) {
				foreach($response['result'] as $k => $v) {
					$data = self::GetChildren($v['tid'], true);
					if (is_array($data)) {
						foreach($data as $key => $value) {
							$data[$value['tid']] = $value;
							unset($data[$key]);
						}
					}
					if (!empty($data)) {
						$response['result'][$k]['children'] = $data;
					}
				}
			}

			// now convert to properly indexed array
			$terms = $response['result'];
			foreach($terms as $key => $value) {
				$newterms[$value['tid']] = $value;
			}
			$data = serialize($newterms);
			$fp = fopen($file, 'w');
			fwrite($fp, $data);
			fclose($fp);
			$terms = $newterms;
		} else {
			if ($fp = fopen($file, 'r')) {
				$data = '';
				while (!feof($fp)) {
					$data .= fread($fp, 1024);
				}
				fclose($fp);
				$terms = unserialize($data);
			}
		}

		// if $all == false, remove all taxonomy terms from the return array where there are no known videos tagged with that term
		if (in_array($vid, explode(',', VOCABULARIES)) && !$all) {
			$params['fq'] = '';
			$params['facet'] = 'on';
			$params['facet.field'] = 'tid';
			$params['facet.mincount'] = 1;
			$params['facet.method'] = 'enum';
			$params['alltids'] = true;
			$params['fl'] = 'id,title,capture_image,sefu,content_type,duration';

			$response = Search::Find('', array(), array(), 0, 1, $params);

			foreach($terms as $key => $value) {
				// modify taxonomy array return if this is a display taxonomy
				if (!array_key_exists($key, $response['tidcounts'])) {
					unset($terms[$value['tid']]);
				}
			}
		}
		return $terms;
	}

	/**
	 * Get all terms Associated with a vocabulary ignoring hierarchy	 *
	 * By default, this is cached to disk for speed. The Taxonomy cache expires every hour (60*60 seconds)	 *
	 * A full refresh may be forced.
	 * @param integer $vid the vocabulary term to search for
	 * @param boolean $refresh - force a refresh from the database if true
	 * @param boolean $all - should the method return all Taxonomy Terms even if no known videos? (default true)
	 * @return array $terms
	 */
	static function GetAllTermsByVocabulary($vid, $refresh = false, $all = true) {

		$path = CACHEPATH.'/taxonomy/';
		$file = $path.'all_vocab_'.$vid.'.cache';
		if (!file_exists($path)) {
			mkdir($path);
		}

		// force refresh
		if ($refresh) {
			@unlink($file);
		}
		if ( (file_exists($file) && filemtime($file) < (time()-(60*60)) ) || !file_exists($file)) {
			// update the cache
			$response = ds('taxonomy_GetAllTermsByVocabulary', array('vid' => $vid));
			if (isset($response['statusCode']) && $response['statusCode']==0) {

				// now convert to properly indexed array
				$terms = $response['result'];
				foreach($terms as $key => $value) {
					$newterms[$value['tid']] = $value;
				}
				$terms = $newterms;
				$data = serialize($terms);
				$fp = fopen($file, 'w');
				fwrite($fp, $data);
				fclose($fp);
			} else {
				return false;
			}
		} else {
			if ($fp = fopen($file, 'r')) {
				$data = '';
				while (!feof($fp)) {
					$data .= fread($fp, 1024);
				}
				fclose($fp);
				$terms = unserialize($data);
			}
		}
		if (in_array($vid, explode(',', VOCABULARIES)) && !$all) {
			$params['fq'] = '';
			$params['facet'] = 'on';
			$params['facet.field'] = 'tid';
			$params['facet.mincount'] = 0;
			$params['facet.method'] = 'enum';
			$params['facet.limit'] = -1;
			$params['alltids'] = true;
			$params['fl'] = 'id,title,capture_image,sefu,content_type,duration';

			$response = Search::Find('', array(), array(), 0, 1, $params);

			// if there is a response from Solr, remove unused terms, otherwise show all
			if (isset($response['statusCode']) && $response['statusCode'] == 0) {
				foreach($terms as $key => $value) {
					// modify taxonomy array return if this is a display taxonomy
					if (!array_key_exists($key, $response['tidcounts'])) {
						unset($terms[$value['tid']]);
					}
				}
			}
		}
		return $terms;
	}


	/**
	 * Get all terms that have children by vocabulary $vid
	 * @param integer $vocab the vocabulary to search by
	 * @return mixed - array on success, false on failure
	 */
	static function GetParentsByVocabularyName($vocab) {
		$vid = self::GetVID($vocab);
		$response = ds('taxonomy_GetParentsByVocabulary', array('vid' => $vid));
		return Core::GenericResponse($response);
	}

		/**
	 * Return the term object matching a term ID.
	 *
	 * @param integer $tid A term's ID
	 * @return array
	 */
	static function GetTerm($tid = 0 ) {
		if (!$tid) return array();

		$terms = Session::GetVariable('taxonomy_terms');
		if(empty($terms)) $terms=array();
		if (!isset($terms[$tid])) {
			$response = ds('taxonomy_GetTerm', array('tid' => $tid));
			if (isset($response['statusCode']) && $response['statusCode']==0) {
				$terms[$tid] = $response['result'][0];
			}
		}
		Session::SetVariable('taxonomy_terms', $terms);
		return $terms[$tid];
	}


	/**
	 * Get all terms that have children
	 * @param integer $tid the term id to search by
	 * @return mixed - array on success, false on failure
	 */
	static function GetParents($tid = 0 ) {
		if (!$tid) return array();

		if (!isset($terms[$tid])) {
			$response = ds('taxonomy_GetParents', array('tid' => $tid));
			return Core::GenericResponse($response);
		}
	}

	/**
	 * Get all child terms of a given parent
	 * @param integer $tid the term id to search by
	 * @return mixed - array on success, false on failure
	 */
	static function GetChildren($tid = 0, $refresh = false) {
		if (!$tid) {
			return array();
		}
		$path = CACHEPATH.'/taxonomy/';
		$file = $path.'term_'.$tid.'.cache';
		if (!file_exists($path)) {
			mkdir($path);
		}
		if (((file_exists($file) && filemtime($file) < (time()-(60*60*24)) ) || !file_exists($file)) || $refresh) {
			// update the cache
			$response = ds('taxonomy_GetChildren', array('tid' => $tid));
			if (isset($response['statusCode']) && $response['statusCode']==0) {
				// now convert to properly indexed array
				$terms = $response['result'];
				foreach($terms as $key => $value) {
					$terms[$value['tid']] = $value;
					unset($terms[$key]);
				}
				$fp = fopen($file, 'w');
				fwrite($fp, serialize($terms));
				fclose($fp);
				return $response['result'];
			} else {
				return false;
			}
		} else {
			if ($fp = fopen($file, 'r')) {
				$data = '';
				while (!feof($fp)) {
					$data .= fread($fp, 1024);
				}
				fclose($fp);
				if (strlen($data) > 0) {
					return unserialize($data);
				} else {
					return array();
				}
			}
		}
	}

	/**
	 * Try to map a string to an existing term, as for glossary use.
	 *
	 * Provides a case-insensitive and trimmed mapping, to maximize the
	 * likelihood of a successful match.
	 *
	 * Matches against a cached term_id<->term_name map before calling the database.
	 *
	 * @param string $name Name of the term to search for.
	 * @param mixed The vocabulary to search against. Defaults to Role/Subject/Keystage (23,24,25)
	 * @return mixed - array on success, false on failure
	 */
	static function GetTermByName($name, $vid = array()) {
		if (empty($vid)) {
			$vid = explode(',', VOCABULARIES);
		}
		$response = ds('taxonomy_GetTermByName', array('name' => (string)$name, 'vid' => $vid));
		return Core::GenericResponse($response);
	}

	/**
	 * Identical to TaxonomyGetTermByName but matches against the 'name' column
	 *
	 * @param string $name Name of the term to search for.
	 * @param mixed The vocabulary to search against. Defaults to Role/Subject/Keystage (23,24,25)
	 * @return mixed - array on success, false on failure
	 */
	static function GetTermByNameForSearch($name, $vid = array()) {
		if (empty($vid)) {
			$vid = explode(',', VOCABULARIES);
		}
		$response = ds('taxonomy_GetTermByNameForSearch', array('name' => (string)$name, 'vid' => $vid));
		return Core::GenericResponse($response);
	}

	/**
	 * Identical to TaxonomyGetTermByLabel but matches against the 'label' column
	 * 
	 * @param string $label
	 * @param mixed $vid
	 * @return
	 */
	static function GetTermByLabelForSearch($label, $vid = array()) {
		if (empty($vid)) {
			$vid = explode(',', VOCABULARIES);
		}
		$response = ds('taxonomy_GetTermByLabelForSearch', array('label' => (string)$label));
		return Core::GenericResponse($response);
	}

	/**
	 *  Get first generation children 
	 * 
	 * @param int $vid
	 * @return
	 */
	public function TaxonomyGetFirstGenerationChildren($vid){
		$response = ds('taxonomy_TaxonomyGetFirstGenerationChildren', array('vid' => $vid));
		return Core::GenericResponse($response);
	}

	/**
	 * Find all terms associated with the given node, ordered by vocabulary and term weight.
	 * @param $nid the relevant node id
	 * @param $vid the relevant vocabulary id
	 * @return array
	 * @todo check deprecated!
	 */
	static function NodeGetTerms($nid, $key = 'tid') {
		if (!isset($terms[$nid][$key])) {
			$response = ds('taxonomy_NodeGetTerms', array('nid' => $nid));
			if (isset($response['statusCode']) && $response['statusCode']==0) {
				foreach($response['result'] as $k => $term) {
					if (is_numeric($k)) {
						$terms[$nid][$key][$term[$key]] = $term;
					} else {
						$terms[$nid][$key][$k] = $term;
					}
				}
			}
		}
		Session::SetVariable('taxonomy_terms', $terms);
		return $terms[$nid][$key];
	}

	/**
	 * Return vocabulary data by vocabulary id
	 * @param int $vid the vocabulary id
	 * @return array
	 */
	static function GetNameFromVID($vid) {
		switch($vid) {
			case SUBJECTS:
				return array('name' => 'Subjects', 'link' =>'subjects');
			case STAGES:
				return array('name' => 'Stages', 'link' => 'stages');
			case ROLES:
				return array('name' => 'Roles', 'link' => 'roles');
			case WHOLE_SCHOOL:
				return array('name' => 'Whole School', 'link' => 'whole-school');
			case MY_SCHOOL_LIFE:
				return array('name' => 'My School Life', 'link' => 'my-school-life');
			default:
				return array();
		}
	}

	/**
	 * Return a vocabulary id by name
	 * @param string $vocab the vocabulary name
	 * @return int
	 */
	static function GetVID($vocab) {
		switch(strtolower($vocab)) {
			case 'subjects':
				return SUBJECTS;
			case 'stages':
				return STAGES;
			case 'roles':
				return ROLES;
			case 'whole-school':
				return WHOLE_SCHOOL;
			case 'my-school-life':
				return MY_SCHOOL_LIFE;
			default:
				return 0;
		}
	}

	static function Flatten($termarray) {
		$output = array();
		$temp = array();
		foreach($termarray as $k => $v) {
			$vv = $v;
			unset($vv['children']);
			$temp[] = $vv;
			if (isset($v['children'])) {
				foreach($v['children'] as $ck => $cv) {
					$cvv = $cv;
					$temp[] = $cv;
				}
			}
		}
		foreach($temp as $k => $v) {
			$output[$v['tid']] = $v;
		}
		return $output;
	}
}