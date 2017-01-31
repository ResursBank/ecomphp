<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_fraudControlStatus", false)) 
{
class resurs_fraudControlStatus
{
    const __default = 'FROZEN';
    const FROZEN = 'FROZEN';
    const NOT_FROZEN = 'NOT_FROZEN';
    const CONTROL_IN_PROGRESS = 'CONTROL_IN_PROGRESS';


}

}
