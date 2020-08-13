<?php

namespace Resursbank\RBEcomPHP\Types;

/**
 * Class Aftershop
 * @package Resursbank\RBEcomPHP\Types
 */
class Aftershop
{
    const NONE = 0;
    const FINALIZE = 1;
    const CREDIT = 2;
    const ANNUL = 4;
    const AUTHORIZE = 8;

    /** @deprecated Redundant name */
    const AFTERSHOP_NO_CHOICE = 0;
    /** @deprecated Redundant name */
    const AFTERSHOP_FINALIZE = 1;
    /** @deprecated Redundant name */
    const AFTERSHOP_CREDIT = 2;
    /** @deprecated Redundant name */
    const AFTERSHOP_ANNUL = 4;
    /** @deprecated Redundant name */
    const AFTERSHOP_AUTHORIZE = 8;
}
