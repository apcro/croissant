<?php
/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
namespace Croissant;

// Core defines
defined('DATA_ERROR')			|| define('NORMAL_ERROR', 0);
defined('NODATA_ERROR')			|| define('NODATA_ERROR', 1);
defined('SYSTEM_ERROR')			|| define('SYSTEM_ERROR', 2);
defined('FATAL_ERROR')			|| define('FATAL_ERROR', 3);
defined('SUBSCRIPTION_ERROR')	|| define('SUBSCRIPTION_ERROR', 4);

defined('RPC_ERROR')			|| define('RPC_ERROR', 96);
defined('METHOD_ERROR')			|| define('METHOD_ERROR', 97);
defined('DATABASE_ERROR')		|| define('DATABASE_ERROR', 98);
defined('DATASERVER_ERROR')		|| define('DATASERVER_ERROR', 99);
