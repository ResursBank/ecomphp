<?php

if (!defined('TORNEAPI_PATH')) {define('TORNEAPI_PATH', __DIR__);}
define('TORNEAPI_LIBRARY', realpath(TORNEAPI_PATH . "/libraries/"));

// Load self contained network class only if not already exists (when this is running from the TorneLIB package, this is not needed)
if (!class_exists('TorneLIB\TorneLIB_Network')) {require_once(TORNEAPI_PATH . "/classes/tornevall_network.php");}
require_once(TORNEAPI_PATH . "/classes/torneapi_core.php");
require_once(TORNEAPI_PATH . "/classes/torneapi_client.php");