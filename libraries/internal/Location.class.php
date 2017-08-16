<?php
/*
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

class Location extends Core { 
	/**
	 * Return the distance (as the crow flies) between two latitude/longitude pairs. Implements HaverSine.
	 * places have array(latitude, longitude)
	 * type = 'm' for miles, 'km' for kilometers (default)
	 * 
	 * @param float $place1
	 * @param float $place2
	 * @param string $type
	 * @return float
	 */
	static function Distance($place1, $place2, $type = 'km') { // lon lat
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!is_array($lon) || !is_array($lat)) {
			return false;
		}
		$r = 6371; // km
		$dLat = deg2rad($place2[0]-$place1[0]);
		$dLon = deg2rad($place2[1]-$place1[1]);
		$a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat[0])) * cos(deg2rad($lat[1])) * sin($dLon/2) * sin($dLon/2);
		$c = 2 * atan2(sqrt($a), sqrt(1-$a));
		$d = round($r * $c, 2);
		if ($type == 'm') {	// returns miles instead of kilometers
			$d = round($d * 0.621371192, 2);
		}
		return $d;
	}

	/**
	 * Get lat lng from uk post code.
	 * 
	 * @param string $postcode UK post code
	 * @param string $provider
	 * @return array
	 */
	static function UKGetLonLat($postcode, $provider = 'google') {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!Core::ValidUKPostcode($postcode)) {
			return false;
		}
		switch ($provider) {
			case 'google':
				return self::_google_UKGetLonLat($postcode);
				break;
			default:
				return false;
		}
	}

	/**
	 * Get address from post code.
	 * 
	 * @param mixed $postcode UK post code
	 * @param string $name (optional)
	 * @param string $provider (optional)
	 * @return array
	 */
	static function UKGetAddress($postcode, $name = '', $provider = 'google') {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!Core::ValidUKPostcode($postcode)) {
			return false;
		}
		switch ($provider) {
			case 'google':
				return self::_google_UKGetAddress($postcode, $name);
				break;
			default:
				return false;
		}
	} 
 
	/**
	 * returns the latitude and longitude from a google services call
	 * 
	 * @param string $postcode
	 * @return array
	 */ 
	static function _google_UKGetLonLat($postcode) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$postcode = str_replace(' ', '', $postcode);
		$url = 'http://maps.googleapis.com/maps/api/geocode/json?address='.$postcode.',+UK&sensor=false';
		$data = @file_get_contents($url);
		$data = json_decode($data, true);
		return array($data['results'][0]['geometry']['location']['lat'], $data['results'][0]['geometry']['location']['lng']);
	}

	/**
	 * return address from a google services call.
	 * 
	 * @param string $postcode
	 * @param string $name
	 * @return string
	 */
	static function _google_UKGetAddress($postcode, $name) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$postcode = str_replace(' ', '', $postcode);
		$latlon = self::_google_UKGetLonLat($postcode);
		$url = 'https://maps.googleapis.com/maps/api/place/search/json?key='.PLACES_API_KEY;
		$url .= '&location='.$latlon[0].','.$latlon[1];
		$url .= '&radius=25';
		$url .= '&sensor=false';
		$url .= '&types=establishment';
		if (!empty($name)) {
			$url .= '&name='.$name;
		}

		$data = @file_get_contents($url);
		return $data;	// JSON object
	}

	/**
	 * Calculate new lat lng. 
	 * 
	 * @param float $latitude
	 * @param float $longitude
	 * @param float $bearing
	 * @param float $distance
	 * @param string $du
	 * @param bool $return_as_array
	 * @return mixed
	 */
	static function GetDistanceCoords($latitude, $longitude, $bearing, $distance, $du = 'm', $return_as_array = false) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if ($du == 'm') {
			// Distance is in miles.
			$radius = 3963.1676;
		} else {
			// distance is in km.
			$radius = 6378.1;
		}

		//	New latitude in degrees.
		$new_latitude = rad2deg(asin(sin(deg2rad($latitude)) * cos($distance / $radius) + cos(deg2rad($latitude)) * sin($distance / $radius) * cos(deg2rad($bearing))));

		//	New longitude in degrees.
		$new_longitude = rad2deg(deg2rad($longitude) + atan2(sin(deg2rad($bearing)) * sin($distance / $radius) * cos(deg2rad($latitude)), cos($distance / $radius) - sin(deg2rad($latitude)) * sin(deg2rad($new_latitude))));

		if ($return_as_array) {
			//  Assign new latitude and longitude to an array to be returned to the caller.
			$coord = array();
			$coord['lat'] = $new_latitude;
			$coord['lon'] = $new_longitude;
		} else {
			$coord = $new_latitude . "," . $new_longitude;
		}
		return $coord;
	}

	/**
	 * Get first result lat lng from address
	 * 
	 * @param string $addr
	 * @param string $region
	 * @return array
	 */
	public static function GeoLocate($addr, $region = 'uk') {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$geoapi = 'http://maps.googleapis.com/maps/api/geocode/json';
		$params = array('address' => $addr, 'sensor' => 'false', 'region' => $region);
		$response = self::_get($geoapi, $params);
		$json = json_decode($response);
		if ($json->status === "ZERO_RESULTS") {
			return NULL;
		} else {
			return array($json->results[0]->geometry->location->lat,$json->results[0]->geometry->location->lng);
		}
	}

	/**
	 * Get all lat lng from address
	 * 
	 * @param string $addr
	 * @return array
	 */
	public static function GeoLocateAll($addr){
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$geoapi = 'http://maps.googleapis.com/maps/api/geocode/json';
		$params = array('address' => $addr, 'sensor' => 'false');
		$response = self::_get($geoapi, $params);
		$json = json_decode($response);
		if ($json->status === "ZERO_RESULTS") {
			return NULL;
		} else {
			return $json;
		}
	}

	/**
	 * Curl (This must be placed somewhere else).
	 * 
	 * @param string $url
	 * @param bool $params
	 * @return array
	 */
	private static function _get($url, $params = false){
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		// Populate data for the GET request
		$url = self::_makeUrl($url, $params);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		if ( isset($_SERVER['HTTP_USER_AGENT']) ) {
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT'] );
		} else {
			// Handle the useragent like we are Google Chrome
			curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/525.13 (KHTML, like Gecko) Chrome/0.X.Y.Z Safari/525.13.');
		}
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		// Populate the data for POST
		if($type == HTTP_POST){
			curl_setopt($ch, CURLOPT_POST, 1);
			if ($params) curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		}

		$result = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		return $result;
	}

	/**
	 * Generate url from get parameters(This must be placed somewhere else).
	 * 
	 * @param string $url
	 * @param array $params
	 * @return string
	 */
	private static function _makeUrl($url, $params){
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if(!empty($params) && $params) {
			foreach($params as $k=>$v) $kv[] = "$k=$v";
			$url_params = str_replace(" ","+",implode('&',$kv));
			$url = trim($url) . '?' . $url_params;
		}
		return $url;
	} 
	/**
	 * Provided for Geoblocking purposes
	 * 
	 * @return boolean descriptor of current user's registered location
	 */
	static public function IsCountry() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return self::$core->is_country;
	} 
	/**
	 * Provided for Geoblocking purposes
	 * 
	 * @return boolean descriptor of current user's physical location as identified by IP
	 */
	static public function InCountry() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return self::$core->in_country;
	}

	/**
	 * Init function for Geoblocking purposes
	 * 
	 * @return void
	 */
	static final public function SetLocation() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		self::_in_country();
		self::_is_country();
	}

	/**
	 * Init function for Geoblocking purposes
	 * 
	 * @return void
	 */
	static final private function _in_country() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$incountry = Session::GetVariable('in_country');
		if ($incountry == '' || empty($incountry) ) {
			$ip = $_SERVER['REMOTE_ADDR'];
			$ip2cc = self::_ip2cc_get_country($ip);
			if (isset($ip2cc['country_code'])) {
				if ($ip2cc['country_code'] == COUNTRY_CODE1 || $ip2cc['country_code'] == COUNTRY_CODE2) {
					$incountry = 1;
				} else {
					$incountry = 0;
				}
				// override for being in the UK to include Jersey and Guernsey
				if (COUNTRY_CODE1 == 'UK' && COUNTRY_CODE2 == 'GB' && ($ip2cc['country_code'] == 'GG' || $ip2cc['country_code'] == 'JE')) {
					$incountry = 1;
				}
			} else {
				$incountry = 0;
			}
			Session::SetVariable('in_country', $incountry);
		}
		self::$core->in_country = $incountry;
	}

	/**
	 * Init function for Geoblocking purposes
	 * 
	 * @return void
	 */
	static final private function _is_country() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$iscountry = (int)Session::GetVariable('is_country');
		if ($iscountry == '' || empty($iscountry)) {
			if (User::UserID() > 0) {
				User::LoadCurrentUser();
				// @TODO replace $core reference
				$iscountry = (User::$core->_user['profile']['country_number'] == COUNTRY_NUMBER)?1:0;
			} else {
				$iscountry = 0;
			}
			Session::SetVariable('is_country', $iscountry);
		}
		self::$core->is_country = $iscountry;
	}

	/**
	 * ipinfodb get ip info from address.
	 * 
	 * @param string $addr ip address
	 * @return
	 */
	static private function _ip2cc_get_country($addr) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (defined('IPINFODB_KEY')) {
			$hostname = gethostbyname($addr);
			$ip = sprintf("%u", ip2long($hostname));
			$response = ds('user_GetCountryCode', array('ip' => $ip));
			if (isset($response['statusCode']) && $response['statusCode'] == 1) {
				$timeout = ini_get('default_socket_timeout');
				ini_set('default_socket_timeout', 1);
				if ($fp = fopen('http://api.ipinfodb.com/v3/ip-country/?ip='.$addr.'&key='.IPINFODB_KEY.'&format=xml', 'r')) {
					$r = fread($fp, 1024);
					fclose($fp);
					$r = self::xml2array($r);
					$response = array('country_code' => $r['Response']['countryCode']['value'], 'country_code2' => $r['Response']['countryCode']['value'], 'country_name' => $r['Response']['countryName']['value']);
					return $response;
				} else {
					return false;
				}
				ini_set('default_socket_timeout', $timeout);
			} elseif (isset($response['statusCode']) && $response['statusCode'] == 0) {
				return $response['result'][0];
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}