<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
namespace Croissant;

class Image2 extends Core {
	static $core;

	public static function Initialise() {
		if (!isset(self::$core)) {
			self::$core = parent::Initialise();
		}
		return self::$core;
	}

	protected static $_width;
	protected static $_height;
	protected static $type;
	protected static $image;
	protected static $imageResized;

	public static function resize($file, $destination, $width, $height, $option = "auto") {
		self::init($file);

		list($opWidth,$opHeight) = self::getDimensions($width, $height, strtolower($option));

		self::$imageResized = imagecreatetruecolor($opWidth, $opHeight);
		imagecopyresampled(self::$imageResized,self::$image,0,0,0,0,$opWidth,$opHeight,self::$_width,self::$_height);

		if ($option == 'crop') {
			self::crop($opWidth,$opHeight,$width,$height);
		}

		return self::save($destination);
	}

	protected static function init($file) {
		self::$image = self::open($file);
		self::$_width = imagesx(self::$image);
		self::$_height = imagesy(self::$image);
	}

	protected static function open($image) {
		$fileInfo = pathinfo($image);
		$extension = strtolower($fileInfo['extension']);

		switch($extension) {
			case "png":
				$img = imagecreatefrompng($image);
				break;
			case "jpg":
			case "jpeg":
				$img = imagecreatefromjpeg($image);
				break;
			case "gif":
				$img = imagecreatefromgif($image);
				break;
			case "bmp":
				$img = imagecreatefrombmp($image);
				break;
			default:
				//throw expception
				$img = false;
				break;
		}

		return $img;
	}

	protected static function getDimensions($width, $height, $option) {
		switch($option) {
			case 'portrait':
				return array(self::getSizeByFixedHeight($height), $height);
			case 'landscape':
				return array($width, self::getSizeByFixedWidth($width));
			case 'auto':
				return self::getSizeByAuto($width, $height);
			case 'crop':
				return self::getOptimalCrop($width, $height);
			case 'exact':
			default:
				return array($width, $height);
		}
	}

	protected static function getSizeByFixedHeight($height) {
		$ratio = self::$_width / self::$_height;
		return $height * $ratio;
	}

	protected static function getSizeByFixedWidth($width) {
		$ratio = self::$_height / self::$_width;
		return $width * $ratio;
	}

	protected static function getSizeByAuto($width, $height) {
		switch(true) {
			case (self::$_height > self::$_width):
				//landscape
				return array($width, self::getSizeByFixedWidth($width));
				break;
			case (self::$_height < self::$_width):
				//portrait
				return array(self::getSizeByFixedHeight($height), $height);
				break;
			case (self::$_height == self::$_width):
			default:
				//square
				switch(true){
					case ($height < $width):
						return array($width, self::getSizeByFixedWidth($width));
						break;
					case ($height > $width):
						return array(self::getSizeByFixedHeight($height), $height);
						break;
					default:
						return array($width, $height);
						break;
				}
				break;
		}
	}

	protected static function getOptimalCrop($width,$height) {
		$heightRatio = self::$_height / $width;
		$widthRatio = self::$_width / $height;

		$opRatio = min($heightRatio,$widthRatio);

		$opHeight = self::$_height / $opRatio;
		$opWidth = self::$_width / $opRatio;

		return array($opWidth,$opHeight);
	}

	protected static function crop($opWidth, $opHeight, $width, $height) {
		$cropStartX = ($opWidth/2) - ($width/2);
		$cropStartY = ($opHeight/2) - ($height/2);

		$crop = self::$imageResized;

		self::$imageResized = imagecreatetruecolor($width, $height);
		imagecopyresampled(self::$imageResized, $crop, 0, 0, $cropStartX, $cropStartY, $width, $height, $width, $height);
	}

	protected static function save($path, $quality = 100, $destroy = false) {

		$extension = pathinfo($path, PATHINFO_EXTENSION);
		$extension = strtolower($extension);

		$destpath = pathinfo($path, PATHINFO_DIRNAME);
		@mkdir($destpath, 0777, true);

		switch($extension){
			case 'jpg':
			case 'jpeg':
				if (imagetypes() && IMG_JPG) {
					self::$type = "image/jpeg";
					$result = imagejpeg(self::$imageResized, $path, $quality);
				}
				break;
			case 'gif':
				if (imagetypes() && IMG_GIF) {
					self::$type = "image/gif";
					$result = imagegif(self::$imageResized, $path);
				}
				break;
			case 'png':
				
				if (imagetypes() && IMG_PNG) {
					$quality = 9 - round(($quality/100) * 9);
					self::$type = "image/png";
					$result = imagepng(self::$imageResized, $path, $quality);
				}
				break;
			 default:
				 //no accepted extension provided
				 $result = false;
				 break;
		}

		($destroy) ? imagedestroy(self::$imageResized) : null;
		return $result;
	}

	protected static function destroy() {
		imagedestroy(self::$imageResized);
	}
	
	public static function correctImageOrientation($filename) {
		if (function_exists('exif_read_data')) {
			$exif = exif_read_data($filename);
			if ($exif && isset($exif['Orientation'])) {
				$orientation = $exif['Orientation'];
				if ($orientation != 1) {
					$img = imagecreatefromjpeg($filename);
					$deg = 0;
					switch ($orientation) {
						case 3:
							$deg = 180;
							break;
						case 6:
							$deg = 270;
							break;
						case 8:
							$deg = 90;
							break;
						default:
							$deg = 0;
							break;
					}
					if ($deg) {
						$img = imagerotate($img, $deg, 0);
					}
					imagejpeg($img, $filename, 95);
				}
			}
		}
	}
}