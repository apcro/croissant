<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
namespace Croissant;

$page_end = microtime(TRUE);
Core::Assign('debug_totaltime', number_format(($page_end-$page_start) * 1000, 2).'ms');
$debugout = '';
$debugout .= '<strong>Total execution time:</strong> '.number_format(($page_end-$page_start) * 1000, 2).'ms.<br />';
$debugout .= '<strong>Controller execution time:</strong> '.number_format(($controller_end-$controller_start) * 1000, 2).'ms.<br />';
$debugout .= '<strong>Point timers:</strong><br />';
foreach ($point_timer as $k => $v) {
	$debugout .= '&raquo;&nbsp;'.$v[0].' at ';
	$debugout .= number_format(($v[1] - $page_start) * 1000, 2).'ms.<br />';
}

$debugout.= '<strong>Last set template file:</strong> '.print_r(Core::$core->smarty->template_dir, true).'/'.$template.'<br />';
$debugout.= '<strong>Worker file:</strong> '.$worker.'<br />';
$debugout.= '<strong>URL Function (controller):</strong> '.$function.'<br />';
$debugout.= '<strong>Alias redirect to (if any):</strong> '.$response['alias_function'].'<br />';
$debugout.= '<strong>Peak memory usage: </strong>'.number_format(memory_get_peak_usage(TRUE), 0).' bytes<br />';
$debugout .= '<strong>SESSION</strong>';
$debugout .= '<pre>'.print_r($_SESSION, true).'</pre>';

Core::Assign('debugout', $debugout);
Core::Assign('fulldebug', Core::GetDebugData());
Core::Assign('memused', number_format(memory_get_peak_usage(TRUE)/1024/1024, 2));
Core::AddJavascript('debug.js');
Core::AddCSS('debug.css');