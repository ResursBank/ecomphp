<?php
/**
 * Resurs Bank Passthrough API - A pretty silent ShopFlowSimplifier for Resurs Bank.
 * Last update: See the lastUpdate variable
 * @package RBEcomPHP
 * @author Resurs Bank Ecommerce <ecommerce.support@resurs.se>
 * @version 1.0-beta
 * @branch 1.0
 * @link https://test.resurs.com/docs/x/KYM0 Get started - PHP Section
 * @link https://test.resurs.com/docs/x/TYNM EComPHP Usage
 * @license Not set
 */

namespace Resursbank\RBEcomPHP;

/**
 * Class ResursCurlMethods
 *
 * How CURL should handle calls
 */
abstract class ResursCurlMethods {
    const METHOD_GET = 0;
    const METHOD_POST = 1;
    const METHOD_PUT = 2;
    const METHOD_DELETE = 3;
}
