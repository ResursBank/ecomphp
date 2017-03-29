<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_withMetaData", false)) 
{
class resurs_withMetaData
{

    /**
     * @var string $withMetaDataKey
     * @access public
     */
    public $withMetaDataKey = null;

    /**
     * @var string $withMetaDataValue
     * @access public
     */
    public $withMetaDataValue = null;

    /**
     * @param string $withMetaDataKey
     * @access public
     */
    public function __construct($withMetaDataKey)
    {
      $this->withMetaDataKey = $withMetaDataKey;
    }

}

}
