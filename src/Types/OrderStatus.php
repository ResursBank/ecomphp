<?php

namespace Resursbank\Ecommerce\Types;

/**
 * Class OrderStatus
 * Extended order status, which includes statuses others than the defaults (1-64) and which could be applied on
 * the full order (like frozen, etc).
 * @package Resursbank\Ecommerce\Types
 */
class OrderStatus extends PaymentStatus
{
    const PENDING = 1;
    const PROCESSING = 2;
    const COMPLETED = 4;
    const ANNULLED = 8;
    const CREDITED = 16;
    const AUTO_DEBITED = 32;
    const MANUAL_INSPECTION = 64;
    const FROZEN = 128;
}
