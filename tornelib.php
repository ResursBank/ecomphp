<?php

/**
 * TorneLIB 5.0
 */

/**
 * Tornevall Networks PHP Library version 5
 *
 * Yet another PHP-library.
 * Compatibiliy: 5.3 (Confirmed) and above
 *
 * Last update: 20160704
 * @package TorneLIB
 * @author Tomas Tornevall <thorne@tornevall.net>
 * @version 5.0 (alpha-core)
 * @branch 5.0
 * @link http://docs.tornevall.net/x/FoBU TorneLIB v5 Docs
 * @license Apache License 2.0
 */

namespace TorneLIB;

if (defined('TORNELIB_PATH')) {
    if (!defined('TORNELIB_LIBS')) {
        define('TORNELIB_LIBS', TORNELIB_PATH . "/libs");
    }
}

/* Preventing empty zones to fail */
if (version_compare(phpversion(), '5.3.0', '>=')) {
    $testTimeZone = ini_get('date.timezone');
    if (empty($testTimeZone)) {
        @date_default_timezone_set(date_default_timezone_get());
    }
}

function TorneLIB_Require($filename = '') {
	if (file_exists($filename)) {
		require_once($filename);
		return true;
	} else {
		return false;
	}
}

/**
 * Only load the addons for TorneLIB if they really do exist and leave errors completely suppressed.
 * This makes TorneLIB able to live with only parts of the component library.
 */
TorneLIB_Require(__DIR__ . '/classes/tornevall_configuration.php');
TorneLIB_Require(__DIR__ . '/classes/tornevall_core.php');
TorneLIB_Require(__DIR__ . '/classes/tornevall_crypto.php');
TorneLIB_Require(__DIR__ . '/classes/tornevall_exception.php');
TorneLIB_Require(__DIR__ . '/classes/tornevall_database.php');
TorneLIB_Require(__DIR__ . '/classes/tornevall_network.php');
TorneLIB_Require(__DIR__ . '/classes/tornevall_dnsbl.php');
TorneLIB_Require(__DIR__ . '/classes/tornevall_pluggable.php');
TorneLIB_Require(__DIR__ . '/classes/tornevall_api.php');


