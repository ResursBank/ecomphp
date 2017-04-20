<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_bookingResult", false)) 
{
class resurs_bookingResult
{

    /**
     * @var id $paymentId
     * @access public
     */
    public $paymentId = null;

    /**
     * @var fraudControlStatus $fraudControlStatus
     * @access public
     */
    public $fraudControlStatus = null;

    /**
     * @param id $paymentId
     * @param fraudControlStatus $fraudControlStatus
     * @access public
     */
    public function __construct($paymentId, $fraudControlStatus)
    {
      $this->paymentId = $paymentId;
      $this->fraudControlStatus = $fraudControlStatus;
    }

}

}
