<?php
/**
 * Serves cached JS files passed in as parameters
 *
 * If requested cache file does not exist, returns a 404
 *
 * if requesting a css.php file from the docroot, serve that instead
 *
 * this controller isn't called if the JS file exists, but will throw a 404 if it doesn't
 */
namespace Croissant;

if (!empty($_REQUEST)) {	// prevent e
	extract($_REQUEST, EXTR_PREFIX_SAME, '__');
}
$args = explode('/', $croissant);

$file = array_pop($args);

$ext = pathinfo($file, PATHINFO_EXTENSION);
if ($ext != 'js') {
	$tplHeaders   = array();
	$filesize=0;
	if ($file != '') {
		$filename = CACHE_PATH.'/'.$file;
		if (file_exists($filename)) {
			$fp = fopen($filename, 'r');
			$filesize = filesize($filename);
			$tplHeaders[] = 'HTTP/1.0 200 OK';
			$tplHeaders[] = 'Expires: Mon, 26 Jul 1997 05:00:00 GMT';
			$tplHeaders[] = 'Last-Modified: ' . date('D, d M Y H:i:s', time()) . ' GMT';
			$tplHeaders[] = 'Cache-Control: public';
			$tplHeaders[] = 'Content-Type: application/x-javascript';
			$tplHeaders[] = 'Content-Length: '.@urldecode($filesize);
		} else {
			$tplHeaders[] = 'HTTP/1.0 404 Not Found';
			$filesize = 0;
		}
	}

	ob_end_clean();
	foreach ( $tplHeaders as $header ) {
		header($header);
	}
	if ($filesize > 0) {
		while (!feof($fp)) {
			echo fread($fp, (1*(1024*1024)));
			@flush();
			@obflush();
		}
		fclose($fp);
	}
	die();
} else {
	// check if the file actually exists - if not, throw a 404
	if (!file_exists(DOCROOT.'/'.$tp)) {
		ob_end_clean();
		header('HTTP/1.0 404 Not Found');
		die();
	}
}