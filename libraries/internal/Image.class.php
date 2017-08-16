<?php
/*
 * Croissant Web Framework
 *
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;

class Image extends Core {
	static $core;

	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			self::$core = parent::Initialise();
		}
		return self::$core;
	}
	/**
     * image resource identifier
     *
     * @var resource
     */
	private static $image;
	/**
     * image extension
     *
     * @var string
     */
	private static $extension;
	/**
     * image size
     *
     * @var int
     */
	private static $size;
	/**
     * image destination
     *
     * @var string
     */
	private static $destination;

	/**
	 * Create image by using static params ( self::$image , self::$extension , self::size , self::destination ) 
	 * 
	 * @param int $width
	 * @param int $height
	 * @return resource
	 */
	final private static function _createImage($width, $height) {
		$new_image = imagecreatetruecolor($width, $height);
		if (self::$extension == 'gif') {
			$transparent = imagecolortransparent(self::$image);
			if ($transparent >= 0) {
				$transparent_color = imagecolorsforindex(self::$image, $transparent);
				$transparent = imagecolorallocate($image, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
				imagefill($new_image, 0, 0, $transparent);
				imagecolortransparent($new_image, $transparent);
			}
		} elseif (self::$extension == 'png') {
			imagealphablending($new_image, FALSE);
			$transparency = imagecolorallocatealpha($image, 0, 0, 0, 127);
			imagefill($new_image, 0, 0, $transparency);
			imagealphablending($new_image, TRUE);
			imagesavealpha($new_image, TRUE);
		} else {
			imagefill($new_image, 0, 0, imagecolorallocate($new_image, 255, 255, 255));
		}
		return $new_image;
	}

	/**
	 * Load image from file
	 * 
	 * @param string $source
	 * @return resource
	 */
	final private static function _loadImage($source) {
		self::$extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
		if (self::$extension == 'jpg') {
			self::$extension = 'jpeg';
		}
		$gd_function = 'imagecreatefrom' . self::$extension;
		self::$image = $gd_function($source);
		self::$size = getimagesize($source);
		return self::$image;
	}

	/**
	 * Output image to browser or file
	 * 
	 * @param resource $image
	 * @return bool
	 */
	final private static function _saveImage($image) {
		$gd_function = 'image'.self::$extension;
		return ($gd_function($image, self::$destination));
	}

	/**
	 * Get image ratio   
	 * 
	 * @param int $width
	 * @param int $height
	 * @return array
	 */
	final private static function _ratioCalc($width, $height)   {
		$new_width = 0;
		$new_height = 0;
		if ((self::$size[0]/$width) >=  (self::$size[1]/$height)) {
			if($width < self::$size[0]) {
				$new_width = $width;
			}else{
				$new_width = self::$size[0];
			}

			$new_height = ($new_width/self::$size[0])  * self::$size[1];
		} else {
			if($height < self::$size[1]) {
				$new_height = $height;
			}else{
				$new_height = self::$size[1];
			}

			$new_width = ($new_height/self::$size[1])  * self::$size[0];
 		}

 		return array($new_width, $new_height);
	}

	/**
	 * Resize image to specific location.
	 * 
	 * @param string $source
	 * @param string $destination
	 * @param int $width
	 * @param int $height
	 * @param bool $keepratio
	 * @return bool
	 */
	final static public function Resize($source, $destination, $width, $height, $keepratio=false) {
		if ($source_image = self::_loadImage($source)) {
			self::$destination = $destination;
			if($keepratio) {
				list($width, $height) = self::_ratioCalc($width, $height);
			}
			if ($new_image = self::_createImage($width, $height)) {
				imagecopyresampled($new_image, $source_image, 0, 0, 0, 0, $width, $height, self::$size[0], self::$size[1]);
				imagedestroy($source_image);
				if (self::_saveImage($new_image)) {
					imagedestroy($new_image);
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Crop image to specific location
	 * 
	 * @param string $source
	 * @param string $destination
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 * @return bool
	 */
	final static public function Crop($source, $destination, $x, $y, $width, $height) {
		if ($source_image = self::_loadImage($source)) {
			self::$destination = $destination;
			if ($new_image = self::_createImage($width, $height)) {
				imagecopyresampled($new_image, $source_image, 0, 0, $x, $y, $width, $height, $width, $height);
				imagedestroy($new_image);
				imagedestroy($source_image);
				if (self::_saveImage($new_image)) {
					return true;
				}
			}
		}
		return false;
	}

}
