<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_calculateResultSizeResponse", false))
{
class resurs_calculateResultSizeResponse
{

    /**
     * @var int $return
     * @access public
     */
    public $return = null;

    /**
     * @param int $return
     * @access public
     */
    public function __construct($return)
    {
      $this->return = $return;
    }

}

}
