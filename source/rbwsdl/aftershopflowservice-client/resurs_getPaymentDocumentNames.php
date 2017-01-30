<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_getPaymentDocumentNames", false))
{
class resurs_getPaymentDocumentNames
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
