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
 * Class ResursOmniCallTypes
 * Omnicheckout callback types
 */
abstract class ResursOmniCallTypes {
    const METHOD_PAYMENTS = 0;
    const METHOD_CALLBACK = 1;
}
