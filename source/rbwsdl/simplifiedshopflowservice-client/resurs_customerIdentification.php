<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_customerIdentification", false)) 
{
class resurs_customerIdentification
{

    /**
     * @var identificationToken $token
     * @access public
     */
    public $token = null;

    /**
     * @var customerCard $customerAccount
     * @access public
     */
    public $customerAccount = null;

    /**
     * @param identificationToken $token
     * @param customerCard $customerAccount
     * @access public
     */
    public function __construct($token, $customerAccount)
    {
      $this->token = $token;
      $this->customerAccount = $customerAccount;
    }

}

}
