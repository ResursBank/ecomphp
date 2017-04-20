<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\resurs_unregisterEventCallback", false)) 
{
class resurs_unregisterEventCallback
{

    /**
     * @var id $eventType
     * @access public
     */
    public $eventType = null;

    /**
     * @param id $eventType
     * @access public
     */
    public function __construct($eventType)
    {
      $this->eventType = $eventType;
    }

}

}
