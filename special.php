<?php

namespace Resursbank\RBEcomPHP;

use PHPUnit\Framework\TestCase;

class special extends TestCase
{
    /**
     * @test
     */
    public function oneSpecial()
    {
        static::assertTrue(
            isset($_ENV['standalone_ecom']) ? true : false
        );
    }
}
