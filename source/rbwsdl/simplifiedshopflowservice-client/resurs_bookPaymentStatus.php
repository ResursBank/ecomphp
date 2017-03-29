<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_bookPaymentStatus", false)) 
{
class resurs_bookPaymentStatus
{
    const __default = 'FINALIZED';
    const FINALIZED = 'FINALIZED';
    const BOOKED = 'BOOKED';
    const FROZEN = 'FROZEN';
    const DENIED = 'DENIED';
    const SIGNING = 'SIGNING';


}

}
