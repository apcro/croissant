<?php
/**
 * Serves cached CSS files passed in as parameters
 *
 * If requested cache file does not exist, returns a 404
 *
 * if requesting a css.php file from the docroot, serve that instead
 */
namespace Croissant;

extract($_REQUEST, EXTR_PREFIX_SAME, '__');    // stops overwriting existing variable values
$args = explode('/', $croissant);
$file = isset($args[1])?$args[1]:'';

$ext = pathinfo($file, PATHINFO_EXTENSION);
if ($ext != 'php') {
	$tplHeaders   = array();
	$tplHeaders[] = 'HTTP/1.0 404 Not Found';
	$filesize=0;
	if ($file != '') {
		$filename = CACHE_PATH.'/'.$file;
		if (file_exists($filename)) {
			$fp = fopen($filename, 'r');
			$filesize = filesize($filename);
			$tplHeaders   = array();
			$tplHeaders[] = 'HTTP/1.0 200 OK';
			$tplHeaders[] = 'Expires: Mon, 26 Jul 1997 05:00:00 GMT';
			$tplHeaders[] = 'Last-Modified: ' . date('D, d M Y H:i:s', time()) . ' GMT';
			$tplHeaders[] = 'Cache-Control: public';
			$tplHeaders[] = 'Content-Type: text/css';
			$tplHeaders[] = 'Content-Length: '.@urldecode($filesize);

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
}