<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_getInvoiceDataResponse", false))
{
class resurs_getInvoiceDataResponse
{

    /**
     * @var invoiceData $invoiceData
     * @access public
     */
    public $invoiceData = null;

    /**
     * @param invoiceData $invoiceData
     * @access public
     */
    public function __construct($invoiceData)
    {
      $this->invoiceData = $invoiceData;
    }

}

}
