<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_getPaymentDocumentResponse", false)) 
{
class resurs_getPaymentDocumentResponse
{

    /**
     * @var pdf $return
     * @access public
     */
    public $return = null;

    /**
     * @param pdf $return
     * @access public
     */
    public function __construct($return)
    {
      $this->return = $return;
    }

}

}
