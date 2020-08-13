<?php

namespace Resursbank\RBEcomPHP;

/**
 * Class RESURS_URL_ENCODE_TYPES How to encode urls.
 * This class of encoding rules are based on emergency solutions if something went wrong with
 * the standard [unencoded] urls.
 * @package Resursbank\RBEcomPHP
 * @since 1.3.16
 * @since 1.1.44
 * @since 1.0.44
 * @deprecated Do not use this unless you see url encoding problems in your environment.
 */
class RESURS_URL_ENCODE_TYPES
{
    const NONE = 0;
    const PATH_ONLY = 1;
    const FULL = 2;
    const SUCCESSURL = 4;
    const BACKURL = 8;
    const FAILURL = 16;
    const LEAVE_FIRST_PART = 32;
}
