<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_getAnnuityFactorsResponse", false)) 
{
class resurs_getAnnuityFactorsResponse
{

    /**
     * @var annuityFactor[] $return
     * @access public
     */
    public $return = null;

    /**
     * @param annuityFactor[] $return
     * @access public
     */
    public function __construct($return)
    {
      $this->return = $return;
    }

}

}
