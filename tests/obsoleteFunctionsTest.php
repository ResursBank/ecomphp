<?php

namespace Resursbank\RBEcomPHP;

if (file_exists(__DIR__ . "/../vendor/autoload.php")) {
    require_once(__DIR__ . '/../vendor/autoload.php');
} else {
    require_once('../source/classes/rbapiloader.php');
}

ini_set('memory_limit', -1);
use PHPUnit\Framework\TestCase;
use \Exception;

class Resursbank_Obsolete_FunctionsTest extends TestCase
{
    private $ECOM_BASE;

    function setUp()
    {
        $this->ECOM_BASE = new ResursBank();
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
            static::assertTrue($e->getCode() === 501);
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
