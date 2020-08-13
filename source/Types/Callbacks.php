<?php

namespace Resursbank\RBEcomPHP\Types;

/**
 * Class Callbacks
 * @package Resursbank\RBEcomPHP\Types
 */
class Callbacks
{
    const NOT_SET = 0;
    const UNFREEZE = 1;
    const ANNULMENT = 2;
    const AUTOMATIC_FRAUD_CONTROL = 4;
    const FINALIZATION = 8;
    const TEST = 16;
    const UPDATE = 32;
    const BOOKED = 64;

    /** @deprecated Use NOT_SET */
    const CALLBACK_TYPE_NOT_SET = 0;
    /** @deprecated Use UNFREEZE */
    const CALLBACK_TYPE_UNFREEZE = 1;
    /** @deprecated Use ANNULMENT */
    const CALLBACK_TYPE_ANNULMENT = 2;
    /** @deprecated Use AUTOMATIC_FRAUD_CONTROL */
    const CALLBACK_TYPE_AUTOMATIC_FRAUD_CONTROL = 4;
    /** @deprecated Use FINALIZATION */
    const CALLBACK_TYPE_FINALIZATION = 8;
    /** @deprecated Use TEST */
    const CALLBACK_TYPE_TEST = 16;
    /** @deprecated Use UPDATE */
    const CALLBACK_TYPE_UPDATE = 32;
    /** @deprecated Use BOOKED */
    const CALLBACK_TYPE_BOOKED = 64;
}
