<?php
/**
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

class Page extends Core {

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
	 * @todo remove this function as deprecated
	 *
	 * @param integer $nid the id of the node to load
	 * @return array
	 */
	static function LoadPage($nid, $table = 'page') {
		if ($nid == 0)
			return false;

		$response = ds('page_LoadPage', array( 'nid' => $nid, 'table' => $table) );
		if (isset($response['statusCode']) && $response['statusCode']==0) {
			// we get back the whole page, even if unpublished
			// and choose not to return unpublished pages using this method
			if ($response['result']['status'] == 1) {
				if ($response['result']['template'] != '') {
					Core::$core->_template_override = $response['result']['template'];
					unset($response['result']['template']);
				}
				self::$core->_page = $response['result'];
				$images = Page::GetNodeImages($nid);
				self::$core->_page['images'] = $images;
				$comments = Comment::LoadComments($nid);
				self::$core->_page['comments'] = $comments;
				self::$core->_page['comment_count'] = count($comments);
				return self::$core->_page;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Load page by sefu
	 * 
	 * @param string $sefu
	 * @return array
	 */
	static function LoadPageBySefu($sefu) {
		if ($sefu == '')
			return false;

		$response = ds('page_LoadPageBySefu', array( 'sefu' => $sefu) );
		if (isset($response['statusCode']) && $response['statusCode']==0) {
			// we get back the whole page, even if unpublished
			// and choose not to return unpublished pages using this method
			if ($response['result']['status'] == 1) {
				if ($response['result']['template'] != '') {
					Core::$core->_template_override = $response['result']['template'];
					unset($response['result']['template']);
				}
				self::$core->_page = $response['result'];
				$images = Page::GetNodeImages($nid);
				self::$core->_page['images'] = $images;
				$comments = Comment::LoadComments($nid);
				self::$core->_page['comments'] = $comments;
				self::$core->_page['comment_count'] = count($comments);
				return self::$core->_page;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
 
	/**
	 * Loads basic information - enough for placeholder display
	 * 
	 * @param integer $nid
	 * @param string $table
	 * @return array
	 */
	static function LoadPageMinimal($nid, $table = 'page') {
		if ($nid == 0) {
			return false;
		}
		$response = ds('page_LoadPageMinimal', array( 'nid' => $nid, 'table' => $table) );
 
		if (isset($response['statusCode']) && $response['statusCode']==0) {
			// we get back the whole page, even if unpublished
			// and choose not to return unpublished pages using this method
			if ($response['result']['status'] == 1) {
				if ($response['result']['template'] != '') {
					Core::$core->_template_override = $response['result']['template'];
					unset($response['result']['template']);
				}
				self::$core->_page = $response['result'];
				$images = Page::GetNodeImages($nid);
				self::$core->_page['images'] = $images;
				return self::$core->_page;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Get current paege id
	 * 
	 * @return int
	 */
	final static function ID() {
		return self::PageNID();
	}

	/**
	 * Get current page id
	 * 
	 * @return int
	 */
	final static function PageNID() {
		if (isset(self::$core->_page)) {
			return self::$core->_page['nid'];
		} else {
			return 0;
		}
	}

	/**
	 * Get current page title
	 * 
	 * @return string
	 */
	final static function Title() {
		if (isset(self::$core->_page)) {
			return self::$core->_page['title'];
		} else {
			return 0;
		}
	}

	/**
	 * Get cuurent page body
	 * 
	 * @return string
	 */
	final static function Body() {
		if (isset(self::$core->_page)) {
			return Viewhandler::Process(self::$core->_page['body'], 3);
		} else {
			return 0;
		}
	}

	/**
	 * Get current page status
	 * 
	 * @return int
	 */
	static function Status() {
		if (isset(self::$core->_page)) {
			return self::$core->_page['status'];
		} else {
			return 0;
		}
	}

	/**
	 * Get current page taxonomy
	 * 
	 * @return array
	 */
	final static function Taxonomy() {
		return Taxonomy::NodeGetTerms(self::$core->_page['nid']);
	 
	}

	/**
	 * List all page
	 * 
	 * @param string $table
	 * @param integer $start
	 * @param mixed $limit
	 * @param bool $count_only
	 * @param string $term
	 * @return array
	 */
	static final public function LoadAllPages($table='page', $start = 0, $limit = null, $count_only = false, $term = '') {
		$response = ds('page_LoadAllPages', array(
			'table'		 => $table,
		 	'start'	  	 => $start,
		 	'limit'		 => $limit,
		 	'count_only' => $count_only,
			'term' 		 => $term
		));
		return Core::GenericResponse($response);
	}

	/**
	 * List all page with less detail
	 * 
	 * @param string $table
	 * @param integer $start
	 * @param mixed $limit
	 * @param bool $count_only
	 * @param string $term
	 * @return array
	 */
	static final public function LoadAllPagesMinimal($table='page', $start = 0, $limit = null, $count_only = false, $term = '') {
		$response = ds('page_LoadAllPages', array(
				'table'		 => $table,
				'start'	  	 => $start,
				'limit'		 => $limit,
				'count_only' => $count_only,
				'term' 		 => $term
		));
		return Core::GenericResponse($response);
	}

	/**
	 * List page images by #nid(page id)
	 * 
	 * @param int $nid
	 * @return array
	 */
	static function GetNodeImages($nid) {
		$response = ds('page_GetNodeImages', array('nid' => $nid));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return $response['result'];
		} else {
			return false;
		}
	}
 
	/** 
	 * Returns the first paragraph of a block of text, and highlights selected words if any
	 * 
	 * @param string $inputText
	 * @param string $highlight
	 * @param integer $characterLimit
	 * @param bool $useEllipsis
	 * @return string
	 */
	static function FirstParagraph($inputText = '', $highlight = '', $characterLimit = 259, $useEllipsis = true) {
		if ($inputText == '') {
			return false;
		}

		// Get First Paragraph
		$p_split_inc_tag = preg_split("(</?[p]*>)", $inputText, 0, PREG_SPLIT_NO_EMPTY);

		if (count($p_split_inc_tag) <=1) {
			return false;
		}
		// array_shift($p_split_inc_tag);
		if ( strpos($p_split_inc_tag[0], '<div class="warning') !== false ) {
			array_shift($p_split_inc_tag);
		}
		if ( $p_split_inc_tag[1] == "</div>" ) {
			array_shift($p_split_inc_tag);
			array_shift($p_split_inc_tag);
		}

		foreach($p_split_inc_tag as $k=>$v) {
			$nv = trim($v);
			if(!empty($nv)) {
				$p_split_inc_tag[$k] = "<p>".$v."</p>";
			}else {
				unset($p_split_inc_tag[$k]);
			}
		}
		$usePara = array_shift($p_split_inc_tag);

		// strip HTML
		$firstPara = strip_tags($usePara);

		if (strlen($firstPara) < $characterLimit) {
			$useEllipsis = false;
			$croppedText = $firstPara;
		} else {
			// get first '$characterLimit' characters
			$croppedText = substr($firstPara, 0, $characterLimit);
			// look for last space
			$croppedTextPos = strpos(strrev($croppedText), ' ');
			// crop string to that level, including last space
			$croppedText = strrev(substr(strrev($croppedText), ($croppedTextPos + 1)));
		}
		// set up word highlighting based on query string
		if (!empty($highlight)) {
			$highlight_words = explode(' ', $highlight);
			$highlight_word_replacements = array();
			foreach($highlight_words as $wk => $wv) {
				if (!empty($wv)) {
					$highlight_words[$wk] = '/('.$wv.')\b/i';
				}
			}
			$croppedText = preg_replace($highlight_words, '<b>'.$i.'</b>', $croppedText);
		}
		if ($useEllipsis == true) {
			return $croppedText . ' ...';
		} else {
			return $croppedText;
		}
	}

 
	/**
	 * Load popular pages, based on page views.
	 * 
	 * @param integer $start
	 * @param integer $limit
	 * @return
	 */
	static function LoadPopularPages($start = 0, $limit = 10) {
		$response = ds('page_LoadPopularPages', array('start' => $start, 'limit' => $limit)); 
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return $response['result'];
		} else {
			return false;
		}
	}
}