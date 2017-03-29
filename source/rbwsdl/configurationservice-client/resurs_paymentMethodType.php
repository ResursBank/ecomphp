<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_paymentMethodType", false)) 
{
class resurs_paymentMethodType
{
    const __default = 'INVOICE';
    const INVOICE = 'INVOICE';
    const REVOLVING_CREDIT = 'REVOLVING_CREDIT';
    const CARD = 'CARD';
    const PAYMENT_PROVIDER = 'PAYMENT_PROVIDER';


}

}
