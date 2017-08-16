<?php
/*
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

class Forum extends Comment {

	static $core;

	/**
	 * Initialise
	 * 
	 * @return
	 */
	public static function Initialise() {
		if (!isset(self::$core)) {
			_log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();
		}
		return self::$core;
	}

	/**
	 * List forums from parent_id
	 * 
	 * @param integer $parent parent_id
	 * @return array
	 */
	static function ListForums($parent = 0) {
		$response = ds('forum_ListForums', array('parent' => $parent));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			$forums = $response['result'];
			foreach($forums as $k => $forum) {
				self::LoadForumPosts($forum['fid']);
				$forums[$k]['posts'] = self::GetCount();
			}
			return $forums;
		} else {
			return false;
		}
	}

	/**
	 * List all forums
	 * 
	 * @return array
	 */
	static function ListAllForums() {
		$response = ds('forum_ListAllForums');
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return $response['result'];
		} else {
			return false;
		}
	}

	/**
	 * @param int $fid
	 * @param int $start
	 * @param int $limit
	 * @return array
	 */
	static function LoadForum($fid, $start = 0, $limit = null) {
		$response = ds('forum_LoadForum', array('fid' => $fid));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			if (!empty($response['result'])) {
				$forum = $response['result'];
				if (empty($forum)) {
					$forum['fid'] = 0;
					$forum['parent_id'] = 0;
					$forum['title'] = 'Discussion Forums';
					$forum['allow_posts'] = 0;
					$forum['group_id'] = 0;
					$forums['order'] = 1;
				}
				if ($forum['parent_id'] != 0) {
					$forum['parent'] = self::LoadForum($forum['parent_id']);
				}
				$forum['subforums'] = self::ListForums($fid);
				$forum['posts'] = self::LoadForumPosts($fid, $start, $limit);
				return $forum;
			} else {
				return false;
			}
		} else {
			if ($fid == 0) {
				$forum['fid'] = 0;
				$forum['parent_id'] = 0;
				$forum['title'] = 'Discussion Forums';
				$forum['allow_posts'] = 0;
				$forum['group_id'] = 0;
				$forums['order'] = 1;
				$forum['subforums'] = self::ListForums($fid);
				$forum['posts'] = self::LoadForumPosts($fid, $start, $limit);
				return $forum;
			}
			return false;
		}
	}

	/**
	 * @param int $fid
	 * @param int $start
	 * @param int $limit
	 * @return mixed
	 */
	public static function LoadForumPosts($fid, $pid = 0, $start = 0, $limit = null) {
		$response = ds('forum_LoadForumPosts', array(
			'nid' => $fid,
			'pid' => $pid,
			'start' => $start,
			'limit' => $limit
		));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			parent::$_total_count = (int)$response['count'];
			return $response['result'];
		} else {
			return false;
		}
	}

 
	/**
	 *  Return all the posts in a thread
	 * 
	 * @param int $forumid
	 * @param int $pid
	 * @param int $start
	 * @param int $limit
	 * @return array
	 */
	static function LoadThreadPosts($forumid, $pid, $start = 0, $limit = null) {
		return ds('forum_LoadThreadPosts', array('forumid' => $forumid, 'pid' => $pid, 'start' => $start, 'limit' => $limit));

	}
 
	/**
	 * Return the number of posts in a thread
	 * 
	 * @param int $pid
	 * @return array
	 */
	static function LoadThreadPostsCount($pid) {
		$response = ds('forum_LoadThreadPostsCount', array('pid' => $pid));
		return $response['result'];
	}

	/**
	 *
	 * @param array $newcomment
	 * @param int $parent_id pid
	 * @return mixed
	 */
	static function Reply($newcomment, $parent_id = 0, $cid = 0) {
		$newcomment['type'] = 1;	// FORUMPOST
		$newcomment['pid'] = (int)$parent_id;
		$newcomment['discuss_parent'] = $cid;

		return Comment::AddComment($newcomment);
	}

	/**
	 * Get last $limit comments
	 * 
	 * @param int $limit
	 * @return array
	 */
	static function LoadRecentDiscussions($limit) {
		return Comment::LoadRecentComments($limit, 1);
	}

	/**
	 * Get last $limot posts
	 * 
	 * @param mixed $limit
	 * @return array
	 */
	static function LoadRecentForumPosts($limit) {
		$response = ds('forum_LoadRecentForumsPosts');
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return $response['result'];
		} else {
			return false;
		}
	}

	/**
	 * List hot topics order by date
	 * 
	 * @return
	 */
	static function GetHotTopics() {
		$response = ds('forum_GetHotTopics');
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return $response['result'];
		} else {
			return false;
		}
	}

	/**
	 * Add new thread
	 * 
	 * @param int $fid
	 * @param int $parent
	 * @param string $title
	 * @param string $description
	 * @param int $group_id
	 * @param bool $allow_posts
	 * @return bool
	 */
	static function AddThread($fid, $parent, $title, $description, $group_id, $allow_posts) {
		//Try to add SEFU slug
		$validSefu = ds('sefu_ValidateSefu', array('title' => $title));

		$response = ds('forum_AddThread', array(	'fid' => $fid,
													'parent_id' => $parent,
													'title' => $title,
													'description' => $description,
													'group_id' => $group_id,
													'allow_posts' => $allow_posts));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			if($validSefu){
				$sefu_slug = strtolower($title);
				$sefu_slug = trim($sefu_slug);
				$sefu_slug = str_replace(" ", "-", $sefu_slug);
				$sefu_slug = str_replace("---", "-", $sefu_slug);
				$response = ds('sefu_AddSefu', array('sefu_slug' => $sefu_slug, 'node_id' => $fid));
				if(!$response['statusCode'] == 0){
					//SEFU wasn't added, display message to user but let the rest go through
					$message = array("type" => "1", "message" => $response['message']);
				}
			}
			//TODO Change what is returned to include possible messages
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Update comment from thread.
	 * 
	 * @param int $threadid
	 * @param string $post
	 * @return bool
	 */
	static function UpdateThreadPost($threadid, $post) {
			$response = ds('forum_UpdateThreadPost', array('threadid' => $threadid, 'post' => $post));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return true;
		} else {
			return false;
		}
	}
}