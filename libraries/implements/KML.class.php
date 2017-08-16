<?php
/*
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 *
 */
namespace Croissant;

Class KML extends Core {

	static $core;

	private static $_file;

	public static $_dom;

	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();
			self::$_dom = new DOMDocument();
		}
		return self::$core;
	}

	/**
	 * attempts to load a named KML file into memory
	 * assumes the full filesystem path is given
	 */
	public static function LoadFile($file) {
		if (empty($file)) return false;
		if (substr(strtolower($file), -3) != 'kml') return false;
		return self::$_dom->load($file);
	}

	/**
	 * Loads data from an XML string
	 */
	public static function LoadString($xml) {
		return self::$_dom->loadXML($xml);
	}

	public function GetPlacemarks() {
		if (empty(self::$_dom)) return false;

		$points = array();
		$folders = self::$_dom->getElementsByTagName('Folder');
		foreach($folders as $folder) {
			$foldername = $folder->getElementsByTagName('name')->item(0)->nodeValue;
			$locations = $folder->getElementsByTagName('Placemark');
			foreach($locations as $location) {
				$name = $location->getElementsByTagName('name')->item(0)->nodeValue;
				$coords = $location->getElementsByTagName('Point');
				$coord = $coords->item(0)->nodeValue;
				$points = self::parseCoord($coord);
				if (!empty($points)) {
					$placemarks[$foldername][] = array('name' => $name, 'points' => $points);
				}
			}
		}
		return $placemarks;

	}

	/**
	 * Return all lat/lon points in a KML file as an array
	 * assumes the KML file has already been loaded
	 */
	static public function GetLatLonPoints() {
		if (empty(self::$_dom)) return false;

		$points = array();
		$locations = self::$_dom->getElementsByTagName('Point');
		foreach($locations as $location) {
			$coords = $location->getElementsByTagName('coordinates');
			$coord = $coords->item(0)->nodeValue;
			$points[] = self::parseCoord($coord);
		}
		return $points;
	}

	static public function ParseCoord($coord) {
		$coordinate = str_replace(' ', '', $coord);
		$regex = "/([-+]{0,1}[0-9]{1,}\.{1}[0-9]{1,})\,([-+]{0,1}[0-9]{1,}\.{1}[0-9]{1,})/";

		$match = preg_match($regex, $coordinate, $result);
		if($match > 0) {
			$lon = $result[1];
			$lat = $result[2];
			$geopoint = array('lat' => $lat, 'lon' => $lon);;
		}
		return $geopoint;
	}

}