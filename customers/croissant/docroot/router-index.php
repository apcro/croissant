<?php
/**
 * Croisssant Web Framework
 *
 * @author Tom Gordon
 * @copyright 2009-2017 Tom Gordon
 *
 */
namespace Croissant;


error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE & ~E_STRICT);
// error_reporting(E_ALL & ~E_NOTICE);
// error_reporting(E_ALL);

require_once('../configuration/configuration.php');

Core::Setup();

extract($_REQUEST, EXTR_PREFIX_SAME, '__');    // stops overwriting existing variable values
unset($_REQUEST);unset($_POST);unset($_GET);

$croissant = isset($croissant)?$croissant:''; // from .httaccess
Debug::PointTime('ParseURL');

$router = new \Bramus\Router\Router();

// add your routes here



// Core::Display() needs to be the last method called to display the required template
// This example calls Core::Display() within the Bramus router's After Router Middleware.
$router->run(function() {
	/* ****************************************************************************************************
	 * Debugging output
	 ******************************************************************************************************/
	Debug::DebugOut();
	
	/* ****************************************************************************************************
	* This is the final call made by every page - display the selected template.
	******************************************************************************************************/
	Core::Display();
});

