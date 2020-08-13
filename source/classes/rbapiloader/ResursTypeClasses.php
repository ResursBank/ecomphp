<?php

/**
 * Deprecated Class Holder. Do not use.
 */

namespace Resursbank\RBEcomPHP;

/**
 * Class RESURS_CHECKOUT_CALL_TYPES
 * @deprecated Never in use
 */
class RESURS_CHECKOUT_CALL_TYPES
{
    const METHOD_PAYMENTS = 0;
    const METHOD_CALLBACK = 1;
}

/**
 * Class RESURS_METAHASH_TYPES
 * @deprecated
 */
class RESURS_METAHASH_TYPES
{
    const HASH_DISABLED = 0;
    const HASH_ORDERLINES = 1;
    const HASH_CUSTOMER = 2;
}

/**
 * Class RESURS_CALLBACK_REACHABILITY External API callback URI control codes
 * @deprecated
 */
class RESURS_CALLBACK_REACHABILITY
{
    const IS_REACHABLE_NOT_AVAILABLE = 0;
    const IS_FULLY_REACHABLE = 1;
    const IS_REACHABLE_WITH_PROBLEMS = 2;
    const IS_NOT_REACHABLE = 3;
    const IS_REACHABLE_NOT_KNOWN = 4;
}
