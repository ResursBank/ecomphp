<?php

namespace Resursbank\RBEcomPHP\Types;

/**
 * Class PaymentStatus Bitwise status information.
 * @package Resursbank\RBEcomPHP\Types
 * @link https://test.resurs.com/docs/x/QwH1 EComPHP: Instant FINALIZATION / Bitmasking constants
 * @link https://test.resurs.com/docs/x/KAH1 EComPHP: Bitmasking features
 */
class PaymentStatus
{
    /**
     * Skip "not in use" since this off-value may cause flaws in status updates (true when matching false flags).
     */
    const NOT_IN_USE = 0;
    const PAYMENT_PENDING = 1;
    const PAYMENT_PROCESSING = 2;
    const PAYMENT_COMPLETED = 4;
    const PAYMENT_ANNULLED = 8;
    const PAYMENT_CREDITED = 16;
    const PAYMENT_AUTOMATICALLY_DEBITED = 32;
    const PAYMENT_MANUAL_INSPECTION = 64;   // When an order by some reason gets stuck in manual inspections
    const PAYMENT_STATUS_COULD_NOT_BE_SET = 128;  // No flags are set

    /** @deprecated Fallback status only, use PAYMENT_ANNULLED */
    const PAYMENT_CANCELLED = 8;
    /** @deprecated Fallback status only, use PAYMENT_CREDITED */
    const PAYMENT_REFUND = 16;
}
