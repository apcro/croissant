<?php
/*
 * Croissant Web Framework
*
* @author Tom Gordon <tom.gordon@apsumon.com>
* @copyright 2009-2017 Tom Gordon
*
*/
namespace Croissant;

class Core {
	static $core;

	public static $handle;
	public static $_url_alias_list;
	public static $core_debugging;
	var $fatal;
	var $_template_override;

	/**
	 * Initialise.
	 *
	 * @return
	 */
	public static function Initialise() {
		if (!isset(self::$core)) {
			if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
			$c = __CLASS__;
			self::$core = new $c;
			if (DEBUG) _log("\t\t ----> we have a new \$c");

			// Set site version based on accessing device's accept headers
			// We do this before starting the session to cater for devices that don't support cookie-based sessions
			self::$core->_site_version = self::_getDeviceType();

			// start the session
			Session::Start(self::$core->_site_version);
			self::$core->_session = true;
			Session::SetVariable('site_version', self::$core->_site_version);

			// Set up gelocation services. Whether the user is registered in the country is handled by login
			Location::SetLocation();
			self::$core->_configuration = array();
			self::$core->_configuration['parse_type'] = 'CONTROLLER';	// the default
			date_default_timezone_set('UTC');
		}
		return self::$core;
	}

	/**
	 * Load config.
	 *
	 * @return
	 */
	final private static function _loadConfig() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		// check session first
		$configuration = Session::GetVariable('configuration');
		if (!is_array($configuration) || empty($configuration)) {
			// load from database?
			$configuration = self::LoadVariableByKey('configuration');
			$configuration = unserialize($configuration);
			if (!is_array($configuration)) {
				// if nothing has been set, create an empty array
				$configuration = array();
			}
			Session::SetVariable('configuration', $configuration);
		}
		if (is_array($configuration) && !empty($configuration)) {
			foreach($configuration as $k => $v) {
				self::$core->_configuration[$k] = $v;
			}
		}
		return;
	}

	/**
	 * Smarty Initialize
	 *
	 * @return void
	 */
	final static function _smartyInitialise() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		self::$core->smarty = new \Smarty();
		self::$core->smarty->template_dir = SMARTY_TEMPLATE.'/'.self::Markup();
		self::$core->smarty->compile_dir = SMARTY_COMPILE.'/'.self::Markup();
		self::$core->smarty->cache_dir = SMARTY_CACHE.'/'.self::Markup();
	}

	/**
	 * Set page title.
	 *
	 * @param string $title
	 * @return
	 */
	static final public function PageTitle($title = '') {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!empty($title)) {
			self::$core->_page_title = $title;
		} else {
			return isset(self::$core->_page_title)?self::$core->_page_title:'';
		}
	}

	/**
	 * Set page meta.
	 *
	 * @param string $data
	 * @return
	 */
	static final public function PageMeta($data = '') {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!empty($data)) {
			self::$core->_page_meta = $data;
		} else {
			return isset(self::$core->_page_meta)?self::$core->_page_meta:'';
		}
	}

	/**
	 * Set page
	 *
	 * @param string $keywords
	 * @return
	 */
	static final public function PageKeywords($keywords = '') {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!empty($keywords)) {
			self::$core->_page_keywords = $keywords;
		} else {
			return isset(self::$core->_page_keywords)?self::$core->_page_keywords:'';
		}
	}



	/**
	 * XML to array.
	 *
	 * @param string $contents
	 * @param integer $get_attributes
	 * @return
	 */
	static function xml2array($contents, $get_attributes=1) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!$contents) return array();

		if (!function_exists('xml_parser_create')) {
			return array();
		}

		//Get the XML parser of PHP - PHP must have this module for the parser to work
		$parser = xml_parser_create();
		xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
		xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
		xml_parse_into_struct( $parser, $contents, $xml_values );
		xml_parser_free( $parser );

		if (!$xml_values) return; //Hmm...

		//Initializations
		$xml_array = array();
		$parents = array();
		$opened_tags = array();
		$arr = array();

		$current = &$xml_array;

		//Go through the tags.
		foreach ($xml_values as $data) {
			unset($attributes, $value);

			extract($data);

			$result = '';
			if ($get_attributes) {
				$result = array();
				if (isset($value)) {
					$result['value'] = $value;
				}

				if (isset($attributes)) {
					foreach ($attributes as $attr => $val) {
						if ($get_attributes == 1) {
							$result['_attr'][$attr] = $val;
						}
					}
				}
			} elseif (isset($value)) {
				$result = $value;
			}

			if ($type == 'open') {
				$parent[$level-1] = &$current;

				if(!is_array($current) or (!in_array($tag, array_keys($current)))) {
					$current[$tag] = $result;
					$current = &$current[$tag];

				} else {
					if(isset($current[$tag][0])) {
						array_push($current[$tag], $result);
					} else {
						$current[$tag] = array($current[$tag],$result);
					}
					$last = count($current[$tag]) - 1;
					$current = &$current[$tag][$last];
				}

			} elseif ($type == 'complete') {
				if(!isset($current[$tag])) {
					$current[$tag] = $result;

				} else {
					if((is_array($current[$tag]) and $get_attributes == 0) || (isset($current[$tag][0]) and is_array($current[$tag][0]) and $get_attributes == 1)) {
						array_push($current[$tag],$result);
					} else {
						$current[$tag] = array($current[$tag], $result);
					}
				}

			} elseif ($type == 'close') {
				$current = &$parent[$level-1];
			}
		}
		return($xml_array);
	}

	/**
	 * Shortcut for smarty template fetch.
	 *
	 * @param string $template
	 * @return
	 */
	public static function Fetch($template) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!isset(self::$core->smarty)) {
			self::_smartyInitialise();
		}
		if (self::TemplateExists($template)) {
			return self::$core->smarty->fetch($template);
		} else {
			return 'template not found: '.self::$core->smarty->template_dir[0].$template;
		}
	}

	/**
	 * Render.
	 *
	 * @param string $template
	 * @return void
	 */
	public static function Display($template) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		Session::Store();

		$tplHeaders   = array();
		if ($template == 'shared/error/404.tpl') {
			$tplHeaders[] = "HTTP/1.0 404 Not Found";
		} elseif (Error::IsFatal()) {
			$tplHeaders[] = "HTTP/1.0 503 Service Unavailable";
		} else {
			$tplHeaders[] = "HTTP/1.0 200 OK";
		}

		if (USE_CACHING) {
			$tplHeaders[] = 'Cache-Control: max-age=604800, must-revalidate';
		} else {
			$tplHeaders[] = 'Cache-Control: no-cache, must-revalidate';
			$tplHeaders[] = 'Pragma: no-cache';
			$tplHeaders[] = 'Expires: Mon, 26 Jul 1997 05:00:00 GMT';
			$tplHeaders[] = 'Last-Modified: ' . date('D, d M Y H:i:s', time()) . ' GMT';
		}


		switch (self::Markup()) {
			case 'xhtml':
				$tplHeaders[] = "Content-Type: application/xhtml+xml";
			case 'wml':
				$tplHeaders[] = "Content-Type: text/vnd.wap.wml";
				break;
			case 'html':
			case 'html5':
			case 'ipad':
			case 'iphone':
			default:
				$tplHeaders[] = "Content-Type: text/html";
				break;
		}

		foreach ( $tplHeaders as $header ) {
			header($header);
		}

		if (Error::IsFatal()) {
			echo self::Fetch('maintenance_page/maintenance.html');
			if (DEBUG) {
				dump(Error::$core->errors);
			}
			die();
		}

		self::SetBaseTemplate();

		if (CSS_MINIFIED == 1) {
			$css_minified = Minify::GetFile(self::$core->smarty->tpl_vars['css_files']);
			self::Assign('css_minified', $css_minified);
		}

		if (JS_MINIFIED == 1) {
			$js_minified_header = Minify::GetJSFile(self::$core->smarty->tpl_vars['js_files_header']);
			self::Assign('js_minified_header', $js_minified_header);
			$js_minified_footer = Minify::GetJSFile(self::$core->smarty->tpl_vars['js_files_footer']);
			self::Assign('js_minified_header', $js_minified_footer);
		}

		if (self::$core->_template_override == '') {
			if (!self::TemplateExists($template)) {
				$template = NOTEMPLATE;
			}

			self::Assign('controller_template', $template);
			self::Assign('page_title', self::PageTitle());
			self::Assign('page_meta', self::PageMeta());
			self::Assign('page_keywords', self::PageKeywords());
			echo self::$core->smarty->fetch(self::_getBaseTemplate());
		} else {
			self::Assign('template_override', self::$core->_template_override);
			echo self::$core->smarty->fetch(self::$core->smarty->template_dir[0].'/eval.tpl');
		}

	}

	/**
	 * Get display template.
	 *
	 * @return string
	 */
	final static function _getDisplayTemplate() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return self::$core->_template;
	}

	/**
	 * Get base template.
	 *
	 * @return
	 */
	final static function _getBaseTemplate() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return !empty(self::$core->_base_template)?self::$core->_base_template:self::_getDisplayTemplate();
	}

	/**
	 * Set base template.
	 *
	 * @param string $template
	 * @return void
	 */
	static final public function SetBaseTemplate($template = '') {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!isset(self::$core->smarty)) {
			self::_smartyInitialise();
		}

		if (empty($template)) {
			if (defined('BASE_TEMPLATE')) {
				$template = BASE_TEMPLATE;
			} else {
				$template = '';
			}
		} else {
			if (!self::TemplateExists($template)) {
				$template = NOTEMPLATE;
			}
		}
		self::$core->_base_template = $template;
	}

	/**
	 * Check template is exists.
	 *
	 * @param string $template
	 * @return bool
	 */
	static function TemplateExists($template) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return self::$core->smarty->templateExists($template);
	}

	/**
	 * Add css files to be included in the layout.
	 * Css file must be located in /css/ .
	 *
	 * @param string $file_name
	 * @param string $prefix
	 * @return Core
	 */
	final public static function AddCSS($file_name, $prefix = '') {
		if (IS_AJAX_REQUEST) {
			return;
		}
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!isset(self::$core->smarty)) {
			self::_smartyInitialise();
		}

		defined('PREFIX') or define('PREFIX', $prefix);

		if (is_array($file_name)) {
			foreach($file_name as $filename) {
				self::$core->smarty->append('css_files', '/css/'.PREFIX.$filename);
			}
		} else {
			self::$core->smarty->append('css_files', '/css/'.PREFIX.$file_name);
		}
		return self::$core;
	}

	/**
	 * Adds scripts to be included in the layout
	 *
	 * @param string|array $file_name
	 * @param string $scope
	 * @return Core
	 */
	static final public function AddJavascript($file_name, $scope = 'header', $prefix = '') {
		if (IS_AJAX_REQUEST) {
			return self::$core;
		}
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!isset(self::$core->smarty)) {
			self::_smartyInitialise();
		}
		defined('PREFIX') or define('PREFIX', $prefix);

		if (is_array($file_name)) {
			foreach($file_name as $filename) {
				self::$core->smarty->append('js_files_' . $scope, '/js/'.PREFIX.$filename);
			}
		} else {
			self::$core->smarty->append('js_files_' . $scope, '/js/'.PREFIX.$file_name);
		}
		return self::$core;
	}

	/**
	 * Get mark up.
	 *
	 * @return string
	 */
	static final public function Markup() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (isset(self::$core->_site_version)) {
			return self::$core->_site_version;
		} else {
			return self::_getDeviceType();
		}
	}

	/**
	 * Get site version.
	 *
	 * @return string
	 */
	static final private function _getDeviceType() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		// hook support for seperate HTML and HTML5 template sets
		$markup = 'html';
		if (@defined(HTML_VERSION)) {
			$markup .= HTML_VERSION;
		}

		// this is called before the session is started on initial startup, if mobile support is enabled
		if (USE_MOBILE) {
			if (isset(self::$core->_session)) {
				$site_version = Session::GetVariable('site_version');
			} else {
				$site_version = '';
			}
		} else {
			// if mobile is not enabled, skip device inspection
			$site_version = $markup;
		}

		if (empty($site_version)) {

			// final ipad/iphone support check. if not set in local.configuration, set to false
			if (!defined(ENABLE_IPAD)) define('ENABLE_IPAD', false);
			if (!defined(ENABLE_IPHONE)) define('ENABLE_IPHONE', false);
			if (!defined(ENABLE_ANDROID)) define('ENABLE_ANDROID', false);

			// Override switches if we want to force iPad and iPhone to HTML
			if (!ENABLE_IPAD && $site_version == 'ipad') {
				$site_version = $markup;
			}
			if (!ENABLE_IPHONE && $site_version == 'iphone') {
				$site_version = $markup;
			}
		}

		if (isset(self::$core->_session)) {
			Session::SetVariable('site_version', $site_version);
		}

		// seamless support for ipads
		if (ENABLE_IPAD) {
			self::$core->_iPad = (bool)strpos($_SERVER['HTTP_USER_AGENT'], 'iPad');
		}
		if (ENABLE_IPHONE) {
			self::$core->_iPhone = (bool)strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone');
		}
		if (ENABLE_ANDROID) {
			self::$core->_android = (bool)strpos($_SERVER['HTTP_USER_AGENT'], 'Android');
		}

		if (DEBUG) _log("\tSite version: ".$site_version);
		return $site_version;
	}

	static final public function getSefu($id, $sefu) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		defined('SEFU_ENABLED') or define('SEFU_ENABLED', false);
		return ((SEFU_ENABLED === true) && (trim($sefu) != '')) ? $sefu : $id;
	}

	/**
	 * Load alias list and set to self::$_url_alias_list .
	 *
	 * @param bool $force
	 * @return
	 */
	static function _load_alias_list($force=FALSE) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$cachefile = CACHEPATH.'/url_aliases.cache';
		if ($force && file_exists($cachefile)) {
			unlink($cachefile);
		}

		$cachetime = DEBUG==1?1:15*60;

		if (file_exists($cachefile) && filemtime($cachefile)>(time()-$cachetime) ) {   // force cache update every day
			// load alias list from disk cache
			if ($f =  @fopen($cachefile, "r")) {
				$data = fread($f, filesize($cachefile));
			}
			self::$_url_alias_list = unserialize($data);
			return;
		} else {
			// load alias list from database and write to disk
			$response = ds('core_LoadURLAliasList');
			if (isset($response['statusCode']) && $response['statusCode']==0) {
				foreach($response['result'] as $k =>$v) {
					$alias = trim($v['dst']);
					self::$_url_alias_list[$alias] = array('src' =>$v['src'], 'r' => $v['redirect']);
				}
				$data = serialize(self::$_url_alias_list);
				if ($f =  @fopen($cachefile, "w")) {
					$data = fwrite($f, $data);
				}
			} else {
				return false;
			}
		}
	}

	/**
	 * Get alias from name.
	 *
	 * @param string $alias
	 * @return string
	 */
	static function _load_alias($alias = '') {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		// sanity check incoming data
		if ($alias == '') return false;				// if nothing passed, return

		if (empty(self::$_url_alias_list)) {
			self::_load_alias_list(TRUE);  // if not already loaded load the URL alias list
		}
		if (empty(self::$_url_alias_list)) {
			return '';	  // if still empty, return nothing (means we have no defined aliases, or there's an error
		} else {
			// the array _url_alias_list contains strings mapping to the correct cleanurl path
			// keyed on alias name

			// fix for trailing slash issue - we strip the trailing slash if there is one
			$alias = rtrim($alias, '/');
			$alias = trim($alias);

			// fix for aliases where the URL has a capital in it - common with taxonomy terms
			$alias = strtolower($alias);
			return (isset(self::$_url_alias_list[$alias]) && self::$_url_alias_list[$alias] != '') ? self::$_url_alias_list[$alias] : '';
		}
	}

	/**
	 * Load variable by key.
	 *
	 * @param string $key
	 * @param bool $default
	 * @return mixed
	 */
	static function LoadVariableByKey($key, $default = false) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$response = ds('core_LoadVariableByKey', array('key' => $key));
		if($response['statusCode'] == 0) {
			if (is_array($response['result'])) {
				foreach ($response['result'] as $key => $row) {
					$data[$key]  = $row;
					$wieght[$key] = $row['weight'];
				}
				array_multisort($wieght, SORT_ASC, $data, SORT_ASC, $response['result']);
			}
			return $response['result'];
		} else {
			return $default;
		}
	}

	/**
	 * Set variable by key.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return bool
	 */
	static function SetVariableByKey($key, $value) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$response = ds('core_SetVariableByKey', array('key' => $key, 'value' => $value));
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Set debug data.
	 *
	 * @param mixed $data
	 * @return void
	 */
	static function SetDebugData($data) {
// 		if (IS_AJAX_CALL) return;
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		self::$core_debugging[] = $data;
	}

	/**
	 * Get debug data.
	 *
	 * @return mixed
	 */
	static function GetDebugData() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return self::$core_debugging;
	}

	/**
	 * Get current url.
	 *
	 * @param array $options
	 * @return string
	 */
	public static function GetCurrentUrl($options=null) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$protocol = 'http'.(($_SERVER["HTTPS"]) ? 's' : '');
		$domain = $_SERVER['HTTP_HOST'];
		$port = '';
		if ($options) {
			if ($options['protocol']) $protocol = $options['protocol'];
			if ($options['domain']) $domain = $options['domain'];
			if ($options['port'] && $options['port']!=80) $port = ':'.$options['port'];
		}
		$url = $protocol.'://'.$domain.$port.$_SERVER['REQUEST_URI'];
		return self::$core->_current_url = $url;
	}

	/**
	 * Convert entities out.
	 *
	 * @param string $string
	 * @return string
	 */
	static function convert_entities_out($string) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$string = htmlentities($string, ENT_COMPAT, 'UTF-8');
		$string = str_replace("&lt;", "<", $string);
		$string = str_replace("&gt;", ">", $string);
		$string = str_replace("&quot;", '"', $string);
		return $string;
	}

	/**
	 * Get url alias.
	 *
	 * @param string $src
	 * @return string/false
	 */
	static function GetURLAlias($src = '') {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (empty($src)) return false;
		// load only new groups
		$result = ds('core_GetURLAlias', array('src' => $src) );
		if (isset($result['statusCode']) && $result['statusCode']==0) {
			return $result['result'];
		} else {
			return false;
		}
	}

	/**
	 * Get referer url.
	 *
	 * @return string
	 */
	static public function _get_referer_url() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return self::$core->_referer_url = str_replace('http://'.$_SERVER['HTTP_HOST'], '', $_SERVER['HTTP_REFERER']);
	}

	/**
	 * Convert xml url to array.
	 *
	 * @param string $xml_url
	 * @return array/false
	 */
	static function LoadXMLData($xml_url) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if ($xml_url == '') {
			return false;
		}
		if (file_exists($xml_url)) {
			$fp = fopen($xml_url, 'r');
			if ($fp) {
				$data = '';
				$xml = '';
				while(!feof($fp)) {
					$xml .= fread($fp, 1024);
				}
				fclose($fp);
				$data = self::xml2array($xml);
				return $data;
			}
		}
		return false;
	}

	/**
	 * Assign template vars
	 *
	 * @param string|array $var
	 * @param mixed $data
	 * @return Core
	 */
	static public function Assign($var, $data = null) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!isset(self::$core->smarty)) {
			self::_smartyInitialise();
		}
		self::$core->smarty->assign($var, $data);
		if (DEBUG) _log("\t".$var);
		// allows chaining of assign calls
		return self::$core;
	}

	/**
	 * Parse URL .
	 *
	 * @param string $url
	 * @return array
	 */
	static final public function ParseURL($url) {
		if (empty($url)) {
			$response = array();
			$response['redirect'] = 0;
			$response['location'] = '/';
			$response['function'] = 'homepage';
			return $response;
		}
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);

		// strip unwanted characters
		$url = preg_replace('/[^a-zA-Z0-9,-=&?_+\ ]/', '', $url);
		// remove double spaces
		$url = preg_replace('!\s+!', ' ', $url);

		$args = explode('/', $url);

		$args0 = array_shift($args);

		foreach($args as $k => $arg) {
			if (strstr($arg, '[removed]')) {
				unset($args[$k]);
			}
			// detect if the string is URL encoded
			if (strstr($args[$k], '%')) {
				$args[$k] = urldecode($args[$k]);
			}

		}
		array_unshift($args, $args0);
		$url = implode('/', $args);

		switch(self::$core->_configuration['parse_type']) {
			case 'ALIAS':
				return self::_parseURL_Alias($url);
				break;
			case 'CONTROLLER':
			default:
				return self::_parseURL_Controller($url, $args);
				break;
		}
	}


	/**
	 * Parse url to controller.
	 * use pre-split array to save time.
	 *
	 * @param string $url
	 * @param array $args
	 * @return array
	 */
	static final private function _parseURL_Controller($url, $args) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (DEBUG) $point_timer[] = array(__CLASS__.'::'.__FUNCTION__, microtime(true));
		$response = array();
		$response['redirect'] = 0;
		$response['location'] = '/';
		$response['function'] = 'homepage';

		if (strtolower($args[0]) == 'index') {
			$response['redirect'] = 1;
			return $response;
		}
		$response['function'] = array_shift($args);
		if (DEBUG) $response['url_function'] = $response['function'];

		if (!file_exists(BASEPATH.'/workers/'.$response['function'].'.php')) {
			if (DEBUG) $point_timer[] = array('started looking for URL alias', microtime(TRUE));

			// the worker doesn't exist, it might be an alias
			// loads URL alias list from disk or database
			$result = self::_load_alias_list(true);
			if (DEBUG) $point_timer[] = array('Finished loading the alias list', microtime(TRUE));

			$redirect = self::_load_alias($url);
			if (DEBUG) $point_timer[] = array('Finished checking for a matching alias', microtime(TRUE));

			if ($redirect) {
				// we have an aliased URL
				if (is_array($redirect)) {
					if (isset($redirect['r'])) {
						$r = $redirect['r'];
					} else {
						$r = 0;
					}
					if (isset($redirect['src'])) {
						$src = $redirect['src'];
					}
					if ($r == 1) {
						$path = '/'.ltrim($src, '/');
						$response['redirect'] = 1;
						$response['location'] = $path;
						return $response;
					} else {
						$args = explode('/', ltrim($src, '/'));
						$response['old_function'] = $response['function'];
						$response['function'] = array_shift($args);

						if (DEBUG) $response['alias_function'] = ltrim($src, '/');
					}
				}
			}
			self::$_url_alias_list = NULL;
		}
		$response['function'] = !empty($response['function'])?$response['function']:'homepage';
		$response['args'] = $args;
		$response['tp'] = $url;
		$response['point_timer'] = $point_timer;
		return $response;
	}

	/**
	 * Parse url alias
	 *
	 * @param string $url
	 * @param array $args
	 * @return array
	 */
	static final private function _parseURL_Alias($url, $args) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$response = array();
		$response['redirect'] = 0;
		$response['location'] = '/';
		$response['function'] = 'homepage';

		if (strtolower($args[0]) == 'index') {
			$response['redirect'] = 1;
			return $response;
		}
		$response['function'] = array_shift($args);
		if (DEBUG) $response['url_function'] = $response['function'];

		if (substr($response['function'], -3) == '.do') {
			$response['redirect'] = 1;
			$response['code'] = '301 Moved Permanently';
			return $response;
		}

		$result = self::_load_alias_list();
		$redirect = self::_load_alias($url);

		if ($redirect) { // we have an aliased URL
			if (is_array($redirect)) {
				if (isset($redirect['r'])) {
					$r = $redirect['r'];
				} else {
					$r = 0;
				}
				if (isset($redirect['src'])) {
					$src = $redirect['src'];
				}
				if ($r == 1) {
					// this is a header redirect
					$path = '/'.ltrim($src, '/');
					$response['redirect'] = 1;
					$response['location'] = $path;
					return $response;
				} else {
					$args = explode('/', ltrim($src, '/'));
					$response['old_function'] = $response['function'];
					$response['function'] = array_shift($args);
					if (DEBUG) $response['alias_function'] = ltrim($src, '/');
				}
			}
			self::$_url_alias_list = NULL;
		}

		$response['function'] = !empty($response['function'])?$response['function']:'homepage';
		$response['args'] = $args;
		$response['tp'] = $url;
		return $response;
	}

	/**
	 * Get browser
	 *
	 * @param string $user_agent
	 * @return string
	 */
	final public static function GetBrowser($user_agent) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$browsers = array('Opera' => 'Opera',
						  'Mozilla Firefox'		=> '(Firebird)|(Firefox)',
						  'Galeon' 				=> 'Galeon',
						  'Mozilla'				=> 'Gecko',
						  'MyIE'				=> 'MyIE',
						  'Lynx' 				=> 'Lynx',
						  'Netscape' 			=> '(Mozilla/4\.75)|(Netscape6)|(Mozilla/4\.08)|(Mozilla/4\.5)|(Mozilla/4\.6)|(Mozilla/4\.79)',
						  'Konqueror'			=> 'Konqueror',
						  'SearchBot' 			=> '(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp/cat)|(msnbot)|(ia_archiver)',
						  'Internet Explorer 7' => '(MSIE 7\.[0-9]+)',
						  'Internet Explorer 6' => '(MSIE 6\.[0-9]+)',
						  'Internet Explorer 5' => '(MSIE 5\.[0-9]+)',
						  'Internet Explorer 4' => '(MSIE 4\.[0-9]+)');
		foreach($browsers as $browser => $pattern) {
			if (strstr($user_agent, $pattern)) {
				return $browser;
			}
		}
		return 'Unknown';
	}

	/**
	 * Get IP address.
	 *
	 * @return string
	 */
	final public static function GetRealIpAddr() {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) { //check ip from shared internet
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { //to check ip is passed from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

	/**
	 * Get OS.
	 *
	 * @param string $user_agent
	 * @return
	 */
	final public static function GetOs($user_agent) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$oses = array ('Windows 3.11' 	=> 'Win16',
					   'Windows 95' 	=> '(Windows 95)|(Win95)|(Windows_95)',
					   'Windows 98' 	=> '(Windows 98)|(Win98)',
					   'Windows 2000' 	=> '(Windows NT 5.0)|(Windows 2000)',
					   'Windows XP' 	=> '(Windows NT 5.1)|(Windows XP)',
					   'Windows 2003' 	=> '(Windows NT 5.2)',
					   'Windows NT 4.0' => '(Windows NT 4.0)|(WinNT4.0)|(WinNT)|(Windows NT)',
					   'Windows ME' 	=> 'Windows ME',
					   'Open BSD'		=> 'OpenBSD',
					   'Sun OS'			=> 'SunOS',
					   'Linux'			=> '(Linux)|(X11)',
					   'Macintosh'		=> '(Mac_PowerPC)|(Macintosh)',
					   'QNX'			=> 'QNX',
					   'BeOS'			=> 'BeOS',
					   'OS/2'			=> 'OS/2',
					   'Search Bot'		=> '(nuhk)|(Googlebot)|(Yammybot)|(Openbot)|(Slurp/cat)|(msnbot)|(ia_archiver)');
		foreach($oses as $os => $pattern) {
			if(strstr($user_agent, $pattern)) {
				return $os;
			}
		}
		return 'Unknown';
	}

	/**
	 * Return an array of data for use with the Smarty pager template
	 * list of elements to be used to create the pager
	 * first: first element count (used for '{x} - {y} of {n}')
	 * last: last element count (used for '{x} - {y} of {n}')
	 *
	 * Uses a wrapper for access: pager()
	 *
	 * @param int $entries number of entries to paginate
	 * @param int $page currently selected 'page' number
	 * @param int $limit number of 'entries' per 'page'
	 * @param string $extra_query_info Query info to append on each link
	 *
	 * @return array pagelinks
	 */
	static function _pager($entries = 0, $page = 1, $limit = 10, $extra_query_info = '', $bypage = false) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		$page = (int)$page;
		if ($page < 1) $page = 1;
			$curr_page = $page;
			$page--;		// correct offset

			// only show the pager if we have more than one page
			if ($entries > $limit) {

			// basic information
			$pages = ceil($entries / $limit);
			$page = ($page > $pages?$pages:$page);
			$first = ($page == 0? TRUE : FALSE);
			$last = ($curr_page == $pages? TRUE : FALSE);

			// get somewhere in the middle of the list
			$eachway = 2;

			$left_page = $curr_page - $eachway;
			$left_page=($left_page>0? $left_page : 1);

			$right_page = $curr_page + $eachway;
			$right_page = ($right_page<$pages? $right_page : $pages);

			// update for Solr
			if ($bypage == true) {
				$pagelinks[] = array('num' => 'First', 'link' => ($first? '' : '?page=1'.$extra_query_info));
				$pagelinks[] = array('num' => 'Previous', 'link' => ($first? '' : "?page=$page".$extra_query_info));
				for ($i=$left_page; $i<=$right_page; ++$i) {
					$pagelinks[] = array('num' => $i, 'link' => "?page=$i".$extra_query_info);
				}
				$next = $page+2;
				$pagelinks[] = array('num' => 'Next', 'link' => ($last ? '' : "?page=$next".$extra_query_info));
				$pagelinks[] = array('num' => 'Last', 'link' => ($last ? '' : "?page=$pages".$extra_query_info));
			} else {
				$offset = (($page-1) * $limit);
				$pagelinks[] = array('num' => 'First', 'link' => ($first? '' : '?offset=0'.$extra_query_info));
				$pagelinks[] = array('num' => 'Previous', 'link' => ($first? '' : "?offset=$offset".$extra_query_info));
				for ($i=$left_page; $i<=$right_page; ++$i) {
					$thisoffset = (($i-1)*$limit);
					if ($thisoffset < 0) {
						$thisoffset = 0;
					}
					$pagelinks[] = array('num' => $i, 'link' => "?offset=$thisoffset".$extra_query_info);
				}
				$next = (($page+1)*$limit);
				$pagelinks[] = array('num' => 'Next', 'link' => ($last ? '' : "?offset=$next".$extra_query_info));
				$pagelinks[] = array('num' => 'Last', 'link' => ($last ? '' : "?offset=$pages".$extra_query_info));
			}

		} else {
			$pagelinks = array();
		}

		// finish up the data for return
		$first_count = ($entries == 0) ? 0 : ($page * $limit) + 1;
		$last_count = ($page * $limit) + $limit;
		$last_count = ($last_count < $entries)? $last_count : $entries;

		return array('pagerlinks' => $pagelinks, 'first' => $first_count, 'last' => $last_count);

	}

	/**
	 * Setup pager to template.
	 *
	 * @param int $page
	 * @param int $count
	 * @param int $limitperpage
	 * @return void
	 */
	static final public function _pagerData($page, $count, $limitperpage) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		// all pages
		$all_page = ceil($count / $limitperpage);
		if(!isset($page) || strlen($page) == 0) {
			$page = ceil($offset/$limitperpage)+1;
		}
		// start item , end item
		$start_item = $limitperpage*($page-1);

		// check page exits
		if( $page > $all_page && $all_page != 0){
			$page = $all_page;
		}

		if(($all_page) == 1){
			$end_item = $count;
		} elseif($page != $all_page) {
			$end_item = $start_item + $limitperpage;
		} elseif($page == $all_page) {
			$end_item = $count;
		} elseif(($page+1) == $all_page) {
			$end_item = $start_item + $limitperpage;
		}
		self::Assign('all_page', $all_page);
		self::Assign('start_item', number_format($start_item + 1));
		self::Assign('end_item', number_format($end_item));
		self::Assign('all_item', $count);
		self::Assign('page', $page);
	}


	/**
	 * returns either the data in the response, or false
	 * saves repeatedly typing the same code in classes
	 *
	 * @param array $response
	 * @return array/false
	 */
	static function GenericResponse($response) {
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		if (isset($response['statusCode']) && $response['statusCode'] == 0) {
			return $response['result'];
		} else {
			return false;
		}
	}

	/**
	 * Validate UK postcode.
	 *
	 * @param string $str
	 * @return bool
	 */
	static function ValidUKPostcode($str) {
		$str = str_replace(' ', '', $str);
		if (DEBUG) _log(__CLASS__.'::'.__FUNCTION__);
		return ( ! preg_match("/^([Gg][Ii][Rr] 0[Aa]{2})|((([A-Za-z][0-9]{1,2})|(([A-Za-z][A-Ha-hJ-Yj-y][0-9]{1,2})|(([A-Za-z][0-9][A-Za-z])|([A-Za-z][A-Ha-hJ-Yj-y][0-9]?[A-Za-z])))) {0,1}[0-9][A-Za-z]{2})$/", $str)) ? false : true;
	}

	/**
	 * Helper function to pre-register non-core classes by arbitrary URL
	 * obviates need for customised __autload
	 * does not call {class}::Initialise()
	 *
	 * @param string $class
	 * @param string $url
	 * @return void
	 */
	static function UsesClass($class, $url) {
		$_classes = isset($_SESSION['_classes'])?$_SESSION['_classes']:array();
		if (!isset($_classes[$class])) {
			$_classes[$class]['url'] = $url;
			$_SESSION['_classes'] = $_classes;
		}
	}
}