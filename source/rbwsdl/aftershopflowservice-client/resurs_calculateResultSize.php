<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_calculateResultSize", false)) 
{
class resurs_calculateResultSize
{

    /**
     * @var searchCriteria $searchCriteria
     * @access public
     */
    public $searchCriteria = null;

    /**
     * @param searchCriteria $searchCriteria
     * @access public
     */
    public function __construct($searchCriteria)
    {
      $this->searchCriteria = $searchCriteria;
    }

}

}
