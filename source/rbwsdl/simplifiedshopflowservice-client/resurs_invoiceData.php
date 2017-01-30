<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_invoiceData", false)) 
{
class resurs_invoiceData
{

    /**
     * @var id $invoiceId
     * @access public
     */
    public $invoiceId = null;

    /**
     * @var date $invoiceDate
     * @access public
     */
    public $invoiceDate = null;

    /**
     * @var invoiceDeliveryTypeEnum $invoiceDeliveryType
     * @access public
     */
    public $invoiceDeliveryType = null;

    /**
     * @access public
     */
    public function __construct()
    {
    
    }

}

}
