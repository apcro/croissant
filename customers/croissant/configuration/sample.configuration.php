<?php
// debugging
define('DEBUG', 1);     // 1 = on
define('HOLDING', 0);

define('DATASERVER', 'croissant.dataserver');
define('DATABASE', 'croissant');

// where to get images and files (set to CDN or absolute path)
define('IMAGE_SERVER', '/images');
define('FILE_SERVER', '/files');

// where to write files that are uploaded through user or code action
define('IMAGE_SERVER_WRITE', '/tmp');
define('FILE_SERVER_WRITE', '/tmp');

define('TEMP_PATH', '/tmp');
define('CACHE_PATH', '/tmp');
define('CACHEPATH', '/tmp/croissant');
define('CACHETYPE', 'disk');
// define('CACHETYPE', 'redis');

// how many entries per page when using Pager class
define('PAGE_LIMIT', 10);

define('SESSIONCACHE', CACHEPATH.'/session_cache');

define('CSS_MINIFIED', 0);
define('JS_MINIFIED', 0);

defined('REDIS_SERVER') 	or define('REDIS_SERVER', '127.0.0.1');
defined('REDIS_PORT') 		or define('REDIS_PORT', '6379');
defined('REDIS_DATABASE') 	or define('REDIS_DATABASE', '4');

// Set the locale and country for geo-blocking
// http://www.unc.edu/~rowlett/units/codes/country.htm
define('COUNTRY_NUMBER', 826);
define('COUNTRY_CODE1', 'UK');
define('COUNTRY_CODE2', 'GB');
define('LOCALE', 'en-gb');

define('DEMOUSER', 9999);
