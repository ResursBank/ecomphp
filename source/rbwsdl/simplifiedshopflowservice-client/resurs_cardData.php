<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_cardData", false)) 
{
class resurs_cardData
{

    /**
     * @var string $cardNumber
     * @access public
     */
    public $cardNumber = null;

    /**
     * @var positiveDecimal $amount
     * @access public
     */
    public $amount = null;

    /**
     * @access public
     */
    public function __construct()
    {
    
    }

}

}
