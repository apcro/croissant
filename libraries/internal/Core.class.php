<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
namespace Croissant;

use \Smarty;

class Core {
	static $core;

	public static $handle;
	public static $_url_alias_list;
	public static $core_debugging;
	public bool $fatal;
	private string $_template_override = '';

	/**
	 * Initialise.
	 *
	 * @return
	 */
	public static function Initialise() {
		if (!isset(self::$core)) {
			$c = __CLASS__;
			self::$core = new $c;

			// start the session
			Session::Start(self::$core->_site_version);
			self::$core->_session = true;
			Session::SetVariable('site_version', self::$core->_site_version);

			self::$core->_configuration = array();
			self::$core->_configuration['parse_type'] = 'CONTROLLER';	// the default
			date_default_timezone_set('UTC');
		}
		return self::$core;
	}

	/**
	 * Smarty Initialize
	 *
	 * @return void
	 */
	final static function _smartyInitialise() {
		self::$core->smarty = new Smarty;
		self::$core->smarty->template_dir = SMARTY_TEMPLATE;
		self::$core->smarty->compile_dir = SMARTY_COMPILE;
		self::$core->smarty->cache_dir = SMARTY_CACHE;
	}

	/**
	 * Set page title.
	 *
	 * @param string $title
	 * @return
	 */
	static final public function PageTitle($title = '') {
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
		if (!empty($keywords)) {
			self::$core->_page_keywords = $keywords;
		} else {
			return isset(self::$core->_page_keywords)?self::$core->_page_keywords:'';
		}
	}


	/**
	 * Shortcut for smarty template fetch.
	 *
	 * @param string $template
	 * @return
	 */
	public static function Fetch($template) {
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

		$tplHeaders[] = "Content-Type: text/html";

		foreach ( $tplHeaders as $header ) {
			header($header);
		}

		if (Error::IsFatal()) {
			echo self::Fetch('maintenance_page/maintenance.html');
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
		return self::$core->_template;
	}

	/**
	 * Get base template.
	 *
	 * @return string
	 */
	final static function _getBaseTemplate() {
		return !empty(self::$core->_base_template)?self::$core->_base_template:self::_getDisplayTemplate();
	}

	/**
	 * Set base template.
	 *
	 * @param string $template
	 * @return void
	 */
	static final public function SetBaseTemplate($template = '') {
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
		if (!IS_AJAX_REQUEST) {
			if (!isset(self::$core->smarty)) {
				self::_smartyInitialise();
			}
			
			defined('PREFIX') || define('PREFIX', $prefix);
			
			if (is_array($file_name)) {
				foreach($file_name as $filename) {
					self::$core->smarty->append('css_files', '/css/'.PREFIX.$filename);
				}
			} else {
				self::$core->smarty->append('css_files', '/css/'.PREFIX.$file_name);
			}
			return self::$core;
		}
	}

	/**
	 * Adds scripts to be included in the layout
	 *
	 * @param string|array $file_name
	 * @param string $scope
	 * @return Core
	 */
	static final public function AddJavascript($file_name, $scope = 'header', $prefix = '') {
		if (!IS_AJAX_REQUEST) {
			if (!isset(self::$core->smarty)) {
				self::_smartyInitialise();
			}
			defined('PREFIX') || define('PREFIX', $prefix);
			
			if (is_array($file_name)) {
				foreach($file_name as $filename) {
					self::$core->smarty->append('js_files_' . $scope, '/js/'.PREFIX.$filename);
				}
			} else {
				self::$core->smarty->append('js_files_' . $scope, '/js/'.PREFIX.$file_name);
			}
			return self::$core;
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
		$response = ds('core_LoadVariableByKey', array('key' => $key));
		if ($response['statusCode'] == 0) {
			if (is_array($response['result'])) {
				foreach ($response['result'] as $key => $row) {
					$data[$key]  = $row;
					$weight[$key] = $row['weight'];
				}
				array_multisort($weight, SORT_ASC, $data, SORT_ASC, $response['result']);
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
		return self::GenericResponse(ds('core_SetVariableByKey', array('key' => $key, 'value' => $value)));
	}

	/**
	 * Set debug data.
	 *
	 * @param mixed $data
	 * @return void
	 */
	static function SetDebugData($data) {
		self::$core_debugging[] = $data;
	}

	/**
	 * Get debug data.
	 *
	 * @return mixed
	 */
	static function GetDebugData() {
		return self::$core_debugging;
	}

	/**
	 * Get current url.
	 *
	 * @param array $options
	 * @return string
	 */
	public static function GetCurrentUrl($options=null) {
		$protocol = 'http'.(($_SERVER["HTTPS"]) ? 's' : '');
		$domain = $_SERVER['HTTP_HOST'];
		$port = '';
		if ($options) {
			if ($options['protocol']) {
				$protocol = $options['protocol'];
			}
			if ($options['domain']) {
				$domain = $options['domain'];
			}
			if ($options['port'] && $options['port']!=80) {
				$port = ':'.$options['port'];
			}
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
		$string = htmlentities($string, ENT_COMPAT, 'UTF-8');
		$string = str_replace("&lt;", "<", $string);
		$string = str_replace("&gt;", ">", $string);
		return str_replace("&quot;", '"', $string);
	}

	/**
	 * Assign template vars
	 *
	 * @param string|array $var
	 * @param mixed $data
	 * @return Core
	 */
	static public function Assign($var, $data = null) {
		if (!isset(self::$core->smarty)) {
			self::_smartyInitialise();
		}
		self::$core->smarty->assign($var, $data);
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
		$response = array();
		$response['redirect'] = 0;
		$response['location'] = '/';
		$response['function'] = 'homepage';

		if (strtolower($args[0]) == 'index') {
			$response['redirect'] = 1;
			return $response;
		}
		$response['function'] = array_shift($args);

		$response['function'] = !empty($response['function'])?$response['function']:'homepage';
		$response['args'] = $args;
		$response['tp'] = $url;
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
		$response = array();
		$response['redirect'] = 0;
		$response['location'] = '/';
		$response['function'] = 'homepage';

		if (strtolower($args[0]) == 'index') {
			$response['redirect'] = 1;
			return $response;
		}
		$response['function'] = array_shift($args);
		$response['function'] = !empty($response['function'])?$response['function']:'homepage';
		$response['args'] = $args;
		$response['tp'] = $url;
		return $response;
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