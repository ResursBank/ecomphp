<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_getAnnuityFactors", false)) 
{
class resurs_getAnnuityFactors
{

    /**
     * @var id $paymentMethodId
     * @access public
     */
    public $paymentMethodId = null;

    /**
     * @param id $paymentMethodId
     * @access public
     */
    public function __construct($paymentMethodId)
    {
      $this->paymentMethodId = $paymentMethodId;
    }

}

}
