<?php


namespace Resursbank\RBEcomPHP;

if (!@class_exists('Bit') && @class_exists('MODULE_NETBITS')) {
    /**
     * Class Bit
     * @package Resursbank\RBEcomPHP
     * @deprecated Avoid this.
     */
    class Bit extends MODULE_NETBITS
    {
    }
}
