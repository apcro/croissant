<?php
/*
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

class Comment extends Core {
	static $core;
	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();
		}
		return self::$core;
	}

	private static $comments;
	private static $_error;

	/**
	 * Storage for the results of count
	 *
	 * @var int
	 */
	protected static $_total_count = null;

	/**
	 * @param int $nid
	 * @param int $start
	 * @param int $limit
	 * @return array
	 */
	public static function Comments($nid, $start = 0, $limit = null) {
		if (!is_array(self::$comments)) {
			$list = array();
			$comments = self::LoadComments($nid, $start, $limit);
			$parents = self::findParentComments($comments);
			foreach ($parents as $k => $v) {
				$list[$k] = $v;
				foreach($comments as $key => $value) {
					$tmp = self::findChildren($comments, $v);
					if (!empty($tmp)) {
						asort($tmp);
						$list = array_merge($list, $tmp);
					}
				}
				unset($k);
			}
			unset($comments);
			self::$comments = $list;
		}
		return self::$comments;
	}

	/**
	 * Gets the count of comments after calling LoadComments()
	 * Returns null if LoadComments() has not been called.
	 *
	 * @return int|null
	 */
	public static function GetCount() {
		return self::$_total_count;
	}

	public static function LoadComment($cid) {
		return ds('comment_LoadComment', array('cid' => $cid));
	}


	/**
	 * Add Comment
	 *
	 * Adds a sanitised comment to a given item, identified by the meta id 'nid'
	 *
	 * The input array is expected to have these fields
	 * 		nid			=> the node ID
	 * 		userid		=> the commentor's user ID
	 * 		comment		=> the comment body text
	 * 		subject		=> the subject of the comment
	 *
	 * Additional parameters may be added based on threading or comment subscription requirements
	 *
	 * @param $newcomment array an array of comment data
	 * @return mixed
	 */
	static function AddComment($newcomment = array()) {
		if (!isset($newcomment)) {
			self::$_error = 'No comment made';
			return false;
		}
		if ($newcomment['userid'] == 0) {
			self::$_error = 'No userid associated with comment - cannot store.';
			return false;
		}
		if (empty($newcomment['comment'])) {
			self::$_error = 'Comment field was empty';
			return false;
		}
		if (empty($newcomment['type']) || !isset($newcomment['type'])) {
			$type = 0;	// default to type=COMMENT
		} else {
			$type = $newcomment['type'];
		}

		// we need to know the parent comment ID for threading, if this is a reply
		// so explicitly set to 0 if not passed in.
		$pid = isset($newcomment['pid']) ? $newcomment['pid'] : 0;
		if (!isset($newcomment['discuss_parent'])) {
			$newcomment['discuss_parent'] = $pid;
		}
		// Parse BBCode in comment body
		$newcomment['comment'] = BBCode::Parse($newcomment['comment']);

		// and a last spam check, if the comment has more than 3 HTTP in it, reject it silently
		if (substr_count($newcomment['comment'], 'http') < 2) {
			// save the comment, default unpublished
			$response = ds('comment_AddComment', array(
				'nid' => $newcomment['nid'],
				'pid' => $pid,
				'comment' => nl2br($newcomment['comment']),
				'subject' => $newcomment['subject'],
				'hostname' => $_SERVER['REMOTE_ADDR'],
				'subscribe' => $newcomment['send'],
				'type' => $type,
				'discuss_parent' => $newcomment['discuss_parent']
			));
		} else {
			return false;
		}

		return Core::GenericResponse($response);
	}

	/**
	 * Load Comment Subscribers
	 *
	 * Returns a list of email addresses, and the subject & body of the selcted comment, of all people
	 * who have indicated they wish to be notifed when a new reply is made on a particular comment
	 *
	 * @param integer $cid the comment id
	 * @return array or boolean
	 */
	static function LoadCommentSubscribers($cid = 0) {
		if ($cid == 0)
			return false;

		$response = ds('comment_LoadCommentSubscribers', array('cid' => $cid));
		if ($response['statusCode'] == 0) {
			if (count($response['result']['emails']) == 0) {
				return false;
			} else {
				return $response['result'];
			}
		} else {
			return false;
		}
	}

	/**
	 * Find Child
	 *
	 * @param $comments
	 * @param $currComment
	 * @return result array
	 */
	static function FindChildren(&$comments, $currComment) {
		$tmp = array();
		if (isset($comments) && !empty($comments)) {
			foreach($comments as $k => $v) {
				if($currComment['pthread'] == $v['pthread']) {
					$tmp[$v['cid']] = $v;
					unset($comments[$k]);
				}
			}
		}
		return $tmp;
	}

	static function FindParentComments(&$comments) {
		$tmp = array();
		if (isset($comments) && !empty($comments)) {
			foreach($comments as $k => $v) {
				if ($v['pid'] == 0) {
					$tmp[$k] = $v;
					unset($comments[$k]);
				}
			}
		}
		return $tmp;
	}

	/**
	 * Load Comments for a given node (or programme) ID
	 *
	 * @param nid integer a node or programme ID
	 * @param int $start
	 * @param int $limit
	 * @return array
	 */
	static final public function LoadComments($nid = 0, $start = 0, $limit = null) {
		if ($nid == 0) return false;

		$response = ds('comment_LoadComments', array(
			'nid' => (int)$nid,
			'start' => $start,
			'limit' => $limit
		));

		if (isset($response['statusCode']) && $response['statusCode']==0) {
			// Update the total count
			self::$_total_count = (int)$response['count'];

			return $response['result'];
		} else {
			return array();
		}
	}

	/**
	 * Load Recent Comments
	 *
	 * @param limit integer the number of comments to load
	 * @return mixed - array on success, false on failure
	 */
	static public function LoadRecentComments($limit = 5, $type = 0) {
		$response = ds('comment_LoadRecentComments', array('limit' => (int)$limit, 'type' => $type ));
		return Core::GenericResponse($response);
	}

	/**
	 * Update the status of an individual comment
	 * @param int $cid the new comment id
	 * @param int $status the new status
	 * @return mixed - array on success, false on failure
	 */
	static function UpdateCommentStatus($cid = 0, $status) {
		if ($cid == 0 )
			return false;
		$response = ds('comment_UpdateCommentStatus', array('cid' => $cid, 'status' => $status));

		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Load Comments for a given user ID
	 *
	 * @param nid integer a node or programme ID
	 * @param limit integer if want unlimit assign 0
	 * @return array
	 */
	static final public function LoadCommentsByUser($uid = 0, $limit = 5) {
		if ($uid == 0) {
			return false;
		}

		$response = ds('comment_LoadCommentsByUser', array('uid' => (int)$uid, 'limit' => (int)$limit) );
		return Core::GenericResponse($response);
	}

	static final public function LoadCommentsByUserWithTitleHideDeleted($uid = 0, $limit = 5) {
		if ($uid == 0) {
			return false;
		}

		$response = ds('comment_LoadCommentsByUserWithTitleHideDeleted', array('uid' => (int)$uid, 'limit' => (int)$limit) );
		return Core::GenericResponse($response);
	}

	/**
	 * Comment::CountComments()
	 * 
	 * @param int $nid
	 * @return
	 */
	public static function CountComments($nid) {
		$response = ds('comment_CountComments', array('nid' => $nid));
		return Core::GenericResponse($response);
	}
}