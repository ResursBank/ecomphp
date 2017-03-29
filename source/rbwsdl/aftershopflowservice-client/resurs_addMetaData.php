<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_addMetaData", false)) 
{
class resurs_addMetaData
{

    /**
     * @var id $paymentId
     * @access public
     */
    public $paymentId = null;

    /**
     * @var string $key
     * @access public
     */
    public $key = null;

    /**
     * @var string $value
     * @access public
     */
    public $value = null;

    /**
     * @param id $paymentId
     * @param string $key
     * @access public
     */
    public function __construct($paymentId, $key)
    {
      $this->paymentId = $paymentId;
      $this->key = $key;
    }

}

}
