<?php
/*
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

class Friend extends Core {
	static $core;
	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();
		}
		return self::$core;
	}

	/**
	 * Get friend status by friend id 
	 * 
	 * @param int $friendid
	 * @return array
	 */
	public static function GetFriendStatus($friendid){
		return Core::GenericResponse(ds('friend_GetStatus', array('friendid' => $friendid)));
	}

	/**
	 * Get friend statuses by friend ids 
	 * 
	 * @param array
	 * @return array
	 */
	public static function GetFriendStatuses($friendids){
		return Core::GenericResponse(ds('friend_GetStatuses', array('friendids' => $friendids)));
	}

	/**
	 * Add friend to login user.
	 * 
	 * @param int $friendid
	 * @return bool
	 */
	static function AddFriend($friendid) {
		return Core::GenericResponse(ds('friend_AddFriend', array('friendid' => $friendid)));
	}

	/**
	 * Remove friend to login user.
	 * 
	 * @param int $friendid
	 * @return bool
	 */
	static function RemoveFriend($friendid) {
		return Core::GenericResponse(ds('friend_RemoveFriend', array('friendid' => $friendid)));
	}

	/**
	 * Accept friend request.
	 * 
	 * @param int $messageid
	 * @param int $friendid
	 * @return bool
	 */
	static function AcceptFriend($messageid, $friendid) {
		$approve = ds('friend_ApproveFriendRequest', array('friendid' => $friendid));
		if ($approve['statusCode'] == 0) {
			$approve = true;
		} else {
			$approve = false;
		}
		$delete = ds('friend_DeleteMessage', array('messageid' => $messageid));
		return $approve;
	}

	/**
	 * Reject friend request
	 * 
	 * @param int $messageid
	 * @return bool
	 */
	static function RejectFriend($messageid) {
		return Core::GenericResponse(ds('friend_RejectFriend', array('messageid' => $messageid)));
	}

	/**
	 * Block user from login user.
	 * 
	 * @param int $blockid friend id that login user request to block
	 * @return bool
	 */
	static function BlockUser($blockid) {
		return Core::GenericResponse(ds('friend_BlockUser', array('blockid' => $blockid)));
	}

	/**
	 * List all blocked users ( from login account)  
	 * 
	 * @return array
	 */
	static function LoadBlockedUsers() {
	    return Core::GenericResponse(ds('friend_LoadBlockedUsers'));
	}

	/**
	 * Unblock user from login account
	 * 
	 * @param int $blockid
	 * @return bool
	 */
	static function UnblockUser($blockid) {
	    return Core::GenericResponse(ds('friend_UnblockUser', array('blockid' => $blockid)));
	}

	/**
	 * Create new user group
	 * 
	 * @param string $title group title
	 * @param string $description group description
	 * @return bool
	 */
	static function AddUserGroup($title, $description) {
		$response = ds('friend_AddUserGroup', array('title' => $title, 'description' => $description));
		return Core::GenericResponse($response);
	}

	/**
	 * Update user group by group id
	 * 
	 * @param int $gid group id
	 * @param string $title group title
	 * @param strubg $description group description
	 * @return bool
	 */
	static function UpdateUserGroup($gid, $title, $description) {
		$response = ds('friend_UpdateUserGroup', array('gid' => $gid, 'title' => $title, 'description' => $description));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Remove user group by group id ( delete from database )
	 * 
	 * @param int $gid
	 * @return bool
	 */
	static function RemoveUserGroup($gid) {
		$response = ds('friend_RemoveUserGroup', array('gid' => $gid));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Get login user groups.
	 * 
	 * @return bool
	 */
	static function LoadMyGroups() {
		$response = ds('friend_LoadMyGroups');
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return $response['result'];
		} else {
			return false;
		}
	}

	/**
	 * Same as load my group.
	 * 
	 * @return
	 */
	static function LoadGroupsIn() {
		$response = ds('friend_LoadGroupsIn');
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return $response['result'];
		} else {
			return false;
		}
	}

	/**
	 * Add user ($uid) to select group ($gid)
	 * 
	 * @param int $gid group id
	 * @param int $uid user id
	 * @return bool
	 */
	static function AddUserToGroup($gid, $uid) {
		$response = ds('friend_AddUserToGroup', array('gid' => $gid, 'uid' => $uid));
		if (isset($response['statusCode'])) {
			if ($response['statusCode'] == 0) {
				return 'OK';
			} else {
				if (substr($response['errorData'], 0, 15) == 'Duplicate entry') {
					return 'EXISTS';
				} else {
					return 'NOTOK';
				}
			}
		}
	}


	/**
	 * Remove user from group
	 * 
	 * @param int $gid group id
	 * @param int $uid user id
	 * @return bool
	 */
	static function RemoveUserFromGroup($gid, $uid) {
		$response = ds('friend_RemoveUserFromGroup', array('gid' => $gid, 'uid' => $uid));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Remove login user from select group ($gid)
	 * 
	 * @param int $gid
	 * @return bool
	 */
	static function LeaveUserGroup($gid) {
		$response = ds('friend_LeaveUserGroup', array('gid' => $gid));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return true;
		} else {
			return false;
		}
	}

	static function IsFriend() {}

	/**
	 * Get total unread message(friend request) from select user.
	 * 
	 * @param int $recipientid user id
	 * @return bool
	 */
	public static function GetFriendRequestUnreadMessageCount($recipientid){
		return Core::GenericResponse(ds('friend_GetFriendRequestUnreadMessageCount', array('recipientid' => $recipientid)));
	}

	/**
	 * LoadInbox
	 * Loads the inbox for the current user
	 *
	 * @return array
	 */
	static function LoadInbox($page, $limit = 10) {
		$response = ds('friend_LoadInbox', array('page' => $page, 'limit' => $limit));
		return Core::GenericResponse($response);
	}


	/**
	 * GetInboxCount
	 * Retrieves the number of messages in a user's Inbox, by type
	 */
	static function GetInboxCount() {
		return Core::GenericResponse(ds('friend_GetInboxCount'));
	}

	/**
	 * Load send box
	 * 
	 * @param int $page
	 * @param int $limit
	 * @return array
	 */
	static function LoadSentbox($page, $limit = 10) {
		$response = ds('friend_LoadSentbox', array('page' => $page, 'limit' => $limit));
		return Core::GenericResponse($response);
	}

	static function GetSentboxCount() {}

	/**
	 * List friend request.
	 * 
	 * @param int $count limit 
	 * @return array
	 */
	static function LoadFriendRequests($count = 5) {
		$response = ds('friend_LoadFriendRequests', array('count' => $count));
		return Core::GenericResponse($response);
	}

	/**
	 * List message by type
	 * 
	 * @param int $type type of message
	 * @param bool $sent sent or not send
	 * @return array
	 */
	static function LoadAllMessagesByType($type, $sent = false) {
		$response = ds('friend_LoadAllMessages', array('type' => $type, 'sent' => $sent));
		return Core::GenericResponse($response);
	}

	/**
	 * Load user messages
	 *
	 * @param int $messageid
	 * @param bool $sent Allows us to load a sent message
	 * @return array
	 */
	static function LoadMessage($messageid, $sent = false) {
		$response = ds('friend_LoadMessage', array(
			'messageid' => $messageid,
			'sent' => $sent
		));

		return Core::GenericResponse($response);
	}

	/**
	 * SendMessage can be used to send different types of messages
	 * set $message['type'] to define the type of message
	 *
	 * @param int $recipientid
	 * @param array $message
	 * @return mixed
	 */
	static function SendMessage($recipientid, $message) {
		if (!is_array($message) || $recipientid == 0) {
			return false;
		}

		if ($message['type'] == 0 && is_string($message['message'])) {
			$message['message'] = BBCode::Parse($message['message']);
		}
		$response = ds('friend_SendMessage', array('recipientid' => $recipientid, 'message' => $message));
		return Core::GenericResponse($response);
	}

	/**
	 * Delete message
	 * @param int $messageid
	 * @return mixed
	 */
	static function DeleteMessage($messageid) {
		$response = ds('friend_DeleteMessage', array('messageid' => $messageid));
		return Core::GenericResponse($response);
	}

	/**
	 * List users by select group($gid)
	 * 
	 * @param int $gid group id
	 * @return array
	 */
	static function LoadGroupUsers($gid) {
		$response = ds('friend_LoadGroupUsers', array('gid' => $gid));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return $response['result'];
		} else {
			return false;
		}
	}

	/**
	 * List message from user group
	 * 
	 * @param int $gid group id
	 * @param int $limit
	 * @return array
	 */
	static function LoadGroupMessages($gid, $limit = 0) {
		$response = ds('friend_LoadGroupMessages', array('gid' => $gid, 'limit' => $limit));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return $response['result'];
		} else {
			return false;
		}
	}

	/**
	 * Get group detail
	 * 
	 * @param int $gid group id
	 * @return array
	 */
	static function LoadGroupDetails($gid) {
		$response = ds('friend_LoadGroupDetails', array('gid' => $gid));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return $response['result'];
		} else {
			return false;
		}
	}

	/**
	 * Get total friend
	 * 
	 * @param int $userid ( 0 - for login user)
	 * @return int
	 */
	static function GetFriendCount($userid = 0) {
		if ($userid == 0) {
			$userid = User::UserID();
		}
		if ($userid == 0) {
			return false;
		}
		$fr = ds('friend_GetFriendCount', array('userid' => $userid));
		return Core::GenericResponse($fr);
	}

	/**
	 * List friend by user
	 * 
	 * @param int $userid ( 0 - for login user)
	 * @param int $start
	 * @param int $limit
	 * @return array
	 */
	static function LoadFriends($userid = 0, $start = 0, $limit = 10) {
		if ($userid == 0) {
			$userid = User::UserID();
		}
		$response = ds('friend_LoadFriends', array('userid' => $userid, 'start' => $start, 'limit' => $limit));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return $response['result'];
		} else {
			return false;
		}
	}

	/**
	 * Change message status 
	 * 
	 * @param int $msgid
	 * @param int $status 
	 * @return bool
	 */
	static function UpdateMessageStatus($msgid, $status) {
		return ds('friend_UpdateMessageStatus', array('msgid' => $msgid, 'status' => $status));
	}

 
	/**
	 * Searches user's friend list for matches
	 * 
	 * @param string $name
	 * @return array
	 */
	static function Search($name) {
		$friends = self::LoadFriends();
		if(isset($friends['friends']) && is_array($friends['friends']))
		foreach($friends['friends'] as $k => $v) {
			if (stristr($v['name'], $name)) {
				$foundfriends[] = $v;
			}
		}
		return $foundfriends;
	}
}