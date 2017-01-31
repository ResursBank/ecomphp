<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_issueCustomerIdentificationTokenResponse", false)) 
{
class resurs_issueCustomerIdentificationTokenResponse
{

    /**
     * @var customerIdentificationResponse $return
     * @access public
     */
    public $return = null;

    /**
     * @param customerIdentificationResponse $return
     * @access public
     */
    public function __construct($return)
    {
      $this->return = $return;
    }

}

}
