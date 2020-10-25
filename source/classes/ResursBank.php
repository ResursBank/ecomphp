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

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once(__DIR__ . '/../../vendor/autoload.php');
}

use Resursbank\Module\ResursApi;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Utils\Generic;

// Globals starts here. But should be deprecated if version tag can be fetched through their doc-blocks.
if (!defined('ECOMPHP_VERSION')) {
    try {
        define('ECOMPHP_VERSION', (new Generic())->getVersionByComposer(__FILE__));
    } catch (ExceptionHandler $e) {
    }
}
if (!defined('ECOMPHP_MODIFY_DATE')) {
    define('ECOMPHP_MODIFY_DATE', '20201025');
}

/**
 * By default Test environment are set. To switch over to production, you explicitly need to tell EComPHP to do
 * this. This a security setup so testings won't be sent into production by mistake.
 */

/**
 * Class ResursApi
 * @package Resursbank\RBEcomPHP
 * @version 1.4.0
 */
class ResursBank extends ResursApi
{
}
