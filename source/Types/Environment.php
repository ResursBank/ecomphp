<?php


namespace Resursbank\RBEcomPHP\Types;

/**
 * Class Environment
 * @package Resursbank\RBEcomPHP\Types
 * @deprecated Use boolean values.
 */
class Environment
{
    /**
     * @var int
     */
    const PRODUCTION = 0;

    /**
     * Test (default).
     * @var int
     */
    const TEST = 1;

    /**
     * Not set by anyone.
     * @var int
     * @deprecated Not in use
     */
    const NOT_SET = 2;

    /**
     * @var int
     * @deprecated Redundant name.
     */
    const ENVIRONMENT_PRODUCTION = 0;

    /**
     * @var int
     * @deprecated Redundant name.
     */
    const ENVIRONMENT_TEST = 1;

    /**
     * @var int
     * @deprecated Redundant name.
     */
    const ENVIRONMENT_NOT_SET = 2;
}
