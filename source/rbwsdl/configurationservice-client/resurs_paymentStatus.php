<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_paymentStatus", false))
{
class resurs_paymentStatus
{
    const __default = 'DEBITABLE';
    const DEBITABLE = 'DEBITABLE';
    const CREDITABLE = 'CREDITABLE';
    const IS_DEBITED = 'IS_DEBITED';
    const IS_CREDITED = 'IS_CREDITED';
    const IS_ANNULLED = 'IS_ANNULLED';


}

}
