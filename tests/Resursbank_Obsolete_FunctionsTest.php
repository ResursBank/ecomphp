<?php

use Resursbank\RBEcomPHP\Resursbank_Obsolete_Functions;

if (file_exists(__DIR__ . "/../vendor/autoload.php")) {
    require_once(__DIR__ . '/../vendor/autoload.php');
} else {
    require_once('../source/classes/rbapiloader.php');
}

class Resursbank_Obsolete_FunctionsTest extends PHPUnit_Framework_TestCase
{
    private $ECOM_BASE;

    function setUp()
    {
        $this->ECOM_BASE = new Resursbank\RBEcomPHP\ResursBank();
    }

    /**
     * @test
     */
    function justThis()
    {
        static::assertTrue(is_object($this->ECOM_BASE->testThis()));
    }

    /**
     * @test
     */
    function deprecationFailure()
    {
        try {
            $this->ECOM_BASE->fail_here();
        } catch (Exception $e) {
            static::assertTrue($e->getCode() === 400);
        }
    }

    /**
     * @test Test deprecated method getPreferredId() which is replaced by getPreferredPaymentId()
     */
    function getPreferredId()
    {
        static::assertTrue($this->ECOM_BASE->getPreferredId() !== '');
    }
}
