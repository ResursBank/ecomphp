<?php

/**
 * Resurs Bank Passthrough API - A pretty silent ShopFlowSimplifier for Resurs Bank.
 * Compatible with simplifiedFlow, hostedFlow and Resurs Checkout.
 * Requirements: WSDL stubs from WSDL2PHPGenerator (deprecated edition)
 * Important notes: As the WSDL files are generated, it is highly important to run tests before release.
 *
 * Last update: See the lastUpdate variable
 * @package RBEcomPHP
 * @author Resurs Bank Ecommerce <ecommerce.support@resurs.se>
 * @version 1.0-beta
 * @branch 1.0
 * @link https://test.resurs.com/docs/x/KYM0 Get started - PHP Section
 * @link https://test.resurs.com/docs/x/TYNM EComPHP Usage
 * @license Apache License
 */

/**
 * Class ResursEnvironments
 */
abstract class ResursEnvironments
{
    const ENVIRONMENT_PRODUCTION = 0;
    const ENVIRONMENT_TEST = 1;
    const ENVIRONMENT_NOT_SET = 2;
}
