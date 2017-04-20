<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_getPayment", false)) 
{
class resurs_getPayment
{

    /**
     * @var id $paymentId
     * @access public
     */
    public $paymentId = null;

    /**
     * @param id $paymentId
     * @access public
     */
    public function __construct($paymentId)
    {
      $this->paymentId = $paymentId;
    }

}

}
