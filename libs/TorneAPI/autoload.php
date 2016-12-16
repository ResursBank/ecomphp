<?php

if (!defined('TORNEAPI_PATH')) {define('TORNEAPI_PATH', __DIR__);}
define('TORNEAPI_LIBRARY', realpath(TORNEAPI_PATH . "/libraries/"));

require_once(TORNEAPI_PATH . "/classes/torneapi_curl.php");
require_once(TORNEAPI_PATH . "/classes/torneapi_core.php");
require_once(TORNEAPI_PATH . "/classes/torneapi_client.php");