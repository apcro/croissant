<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
namespace Croissant;

class Debug extends Core {
	static $core;

	private static $_page_start;
	private static $_page_end;
	private static $_controller_start;
	private static $_controller_end;
	private static $_point_timers;
	private static $_worker;
	private static $_function;
	private static $_alias;
	private static $_template;

	public static function Initialise() {
		if (!isset(self::$core)) {
			self::$core = parent::Initialise();

		}
		return self::$core;
	}

	public static function PageStart() {
		self::$_page_start = microtime(TRUE);
	}

	public static function PageEnd() {
		self::$_page_end = microtime(TRUE);
	}

	public static function ControllerStart() {
		self::$_controller_start = microtime(TRUE);
	}

	public static function ControllerEnd() {
		self::$_controller_end = microtime(TRUE);
	}

	public static function PointTime($description) {
		self::$_point_timers[] = array($description, microtime(TRUE));
	}

	public static function SetFunction($func) {
		self::$_function = $func;
	}

	public static function SetWorker($worker) {
		self::$_worker = $worker;
	}

	public static function SetAlias($alias) {
		self::$_alias = $alias;
	}

	public static function Template($template) {
		self::$_template = $template;
	}

	public static function DebugOut($session = false) {
		if (DEBUG) {

			self::$_page_end = microtime(TRUE);
			Core::Assign('debug_totaltime', number_format((self::$_page_end-self::$_page_start) * 1000, 2).'ms');
			$debugout = '';
			$debugout .= '<strong>Total execution time:</strong> '.number_format((self::$_page_end-self::$_page_start) * 1000, 2).'ms.<br />';
			$debugout .= '<strong>Controller execution time:</strong> '.number_format((self::$_controller_end-self::$_controller_start) * 1000, 2).'ms.<br />';
			if (self::$_point_timers) {

				$debugout .= '<strong>Point timers:</strong><br />';
				foreach (self::$_point_timers as $k => $v) {
					$debugout .= '&raquo;&nbsp;'.$v[0].' at ';
					$debugout .= number_format(($v[1] - self::$_page_start) * 1000, 2).'ms.<br />';
				}
			}
			
			$debugout.= '<strong>Last set template file:</strong> '.self::$_template.'<br />';
			$debugout.= '<strong>Worker file:</strong> '.self::$_worker.'<br />';
			$debugout.= '<strong>URL Function (controller):</strong> '.self::$_function.'<br />';
			if (self::$_alias) {
				$debugout.= '<strong>Alias redirect to:</strong> '.self::$_alias.'<br />';
			}
			$debugout.= '<strong>Peak memory usage: </strong>'.number_format(memory_get_peak_usage(TRUE), 0).' bytes<br />';
			if ($session) {
				$debugout .= '<strong>SESSION</strong>';
				$debugout .= '<pre>'.print_r($_SESSION, true).'</pre>';
			}
			
			Core::Assign('debugout', $debugout);
			Core::Assign('fulldebug', Core::GetDebugData());
			Core::Assign('memused', number_format(memory_get_peak_usage(TRUE)/1024/1024, 2));
			Core::AddJavascript('debug.js');
			Core::AddCSS('debug.css');
		}
		
	}
}