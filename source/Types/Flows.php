<?php

namespace Resursbank\RBEcomPHP\Types;

/**
 * Class Flows
 * @package Resursbank\RBEcomPHP\Types
 */
class Flows
{
    const NOT_SET = 0;
    const SIMPLIFIED_FLOW = 1;
    const HOSTED_FLOW = 2;
    const RESURS_CHECKOUT = 3;

    /** @var int You lazy? */
    const RCO = 3;

    /** @var int Absolutely minimalistic flow with data necessary to render anything at all (and matching data) */
    const MINIMALISTIC = 98;

    /** @deprecated Redundant name */
    const FLOW_NOT_SET = 0;
    /** @deprecated Redundant name */
    const FLOW_SIMPLIFIED_FLOW = 1;
    /** @deprecated Redundant name */
    const FLOW_HOSTED_FLOW = 2;
    /** @deprecated Redundant name */
    const FLOW_RESURS_CHECKOUT = 3;

    /** @deprecated Redundant name */
    const FLOW_MINIMALISTIC = 98;
}
