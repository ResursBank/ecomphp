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
 * Class ResursMethodTypes
 * Preferred payment method types if called.
 */
abstract class ResursMethodTypes {
    /** Default method */
    const METHOD_UNDEFINED = 0;
    const METHOD_SIMPLIFIED = 1;
    const METHOD_HOSTED = 2;
    const METHOD_OMNI = 3;
}
