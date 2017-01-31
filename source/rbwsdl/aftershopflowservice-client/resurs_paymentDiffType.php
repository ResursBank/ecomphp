<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_paymentDiffType", false))
{
class resurs_paymentDiffType
{
    const __default = 'AUTHORIZE';
    const AUTHORIZE = 'AUTHORIZE';
    const DEBIT = 'DEBIT';
    const CREDIT = 'CREDIT';
    const ANNUL = 'ANNUL';


}

}
