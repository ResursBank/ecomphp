<?php

/**
 * Resurs Bank API Wrapper - A silent flow normalizer for Resurs Bank.
 *
 * @package Resursbank
 * @author Resurs Bank <support@resurs.se>
 * @author Tomas Tornevall <tomas.tornevall@resurs.se>
 * @branch 1.3
 * @link https://test.resurs.com/docs/x/KYM0 Get started - PHP Section
 * @link https://test.resurs.com/docs/x/TYNM EComPHP Usage
 * @link https://test.resurs.com/docs/x/KAH1 EComPHP: Bitmasking features
 * @license See LICENSE for license details.
 */

namespace Resursbank\RBEcomPHP;

// This is a global setter but it has to be set before the inclusions. Why?
// It's a result of a legacy project that's not adapted to proper PSR standards.
if (!defined('ECOM_SKIP_AUTOLOAD')) {
    define('ECOM_CLASS_EXISTS_AUTOLOAD', true);
} else {
    define('ECOM_CLASS_EXISTS_AUTOLOAD', false);
    if (!defined('NETCURL_SKIP_AUTOLOAD')) {
        define('NETCURL_SKIP_AUTOLOAD', true);
    }
    if (!defined('CRYPTO_SKIP_AUTOLOAD')) {
        define('CRYPTO_SKIP_AUTOLOAD', true);
    }
    if (!defined('IO_SKIP_AUTOLOAD')) {
        define('IO_SKIP_AUTOLOAD', true);
    }
}

/** @noinspection ClassConstantCanBeUsedInspection */
if (class_exists('ResursBank', ECOM_CLASS_EXISTS_AUTOLOAD) &&
    class_exists('Resursbank\RBEcomPHP\ResursBank', ECOM_CLASS_EXISTS_AUTOLOAD)
) {
    return;
}

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once(__DIR__ . '/../../vendor/autoload.php');
}

use TorneLIB\Utils\Generic;

// Globals starts here. But should be deprecated if version tag can be fetched through their doc-blocks.
if (!defined('ECOMPHP_VERSION')) {
    define('ECOMPHP_VERSION', (new Generic())->getVersionByComposer(__FILE__));
}
if (!defined('ECOMPHP_MODIFY_DATE')) {
    define('ECOMPHP_MODIFY_DATE', '20200611');
}

/**
 * By default Test environment are set. To switch over to production, you explicitly need to tell EComPHP to do
 * this. This a security setup so testings won't be sent into production by mistake.
 */

/**
 * Class ResursBank
 * @package Resursbank\RBEcomPHP
 */
class ResursBank extends Module\ResursBank
{
}
