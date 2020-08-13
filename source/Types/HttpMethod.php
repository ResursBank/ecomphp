<?php

namespace Resursbank\RBEcomPHP\Types;

/**
 * Class HttpMethod
 * @package Resursbank\RBEcomPHP\Types
 */
class HttpMethod
{
    const GET = 0;
    const POST = 1;
    const PUT = 2;
    const DELETE = 3;

    /** @deprecated Redundant name */
    const METHOD_GET = 0;
    /** @deprecated Redundant name */
    const METHOD_POST = 1;
    /** @deprecated Redundant name */
    const METHOD_PUT = 2;
    /** @deprecated Redundant name */
    const METHOD_DELETE = 3;
}
