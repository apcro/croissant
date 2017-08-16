<?php
/*
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
class Mailchimp extends Core {


	public static function initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::initialise();
		}
		if (!isset(self::$mcapi)) {
			self::$mcapi = new MCAPI(MAILCHIMP_API_KEY);
		}
	}

	static $mcapi;
	// a simplistic implementation of Mailchimp's listSubscribe
	//
	static function listSubscribe($listid, $email , $merge_vars ) {
		return self::$mcapi->listSubscribe($listid, $email , $merge_vars);
	}

    static function listUpdateMember($listid, $email , $merge_vars ){
        self::$mcapi->listUpdateMember($listid, $email , $merge_vars);
    }

    static function listUnsubscribe($listid , $email){
        return self::$mcapi->listUnsubscribe($listid, $email);
    }

    static function listMemberInfo($listid , $email){
    	return self::$mcapi->listMemberInfo($listid, $email);
    }

}