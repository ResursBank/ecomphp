<?php

/**
 * Class Resursbank_Obsolete_BackupCall Not in use
 */
class Resursbank_Obsolete_BackupCall {

    /**
     * Method calls that should be passed directly to a webservice
     *
     * Unknown calls passed through __call(), so that we may cover functions unsupported by the gateway.
     * This stub-gateway processing is also checking if the methods really exist in the stubs and passing them over is they do.
     *
     * NOTE: If you're going nonWsdl, this method might go deprecated as curl works differently
     *
     * GETTING DATA AS ARRAYS (DEPRECATED)
     * This method takes control of responses and returns the object "return" if it exists.
     * The function also supports array, by adding "Array" to the end of the method).
     *
     * @param null $func
     * @param array $args
     *
     * @return array|null
     * @throws \Exception
     * @deprecated 1.0.8
     * @deprecated 1.1.8
     */
    public function __call($func = null, $args = array())
    {
        // Initializing wsdl if not done is required here
        $this->InitializeServices();

        $returnObject = null;
        $this->serviceReturn = null;
        $returnAsArray = false;
        $classfunc = null;
        $funcArgs = null;
        $returnContent = null;
        //if (isset($args[0]) && is_array($args[0])) {}
        $classfunc = "resurs_" . $func;
        if (preg_match("/Array$/", $func)) {
            $func = preg_replace("/Array$/", '', $func);
            $classfunc = preg_replace("/Array$/", '', $classfunc);
            $returnAsArray = true;
        }
        if ($this->hasWsdl) {
            $useNameSpace = "";
            foreach (get_declared_classes() as $className) {
                if (preg_match("/rbecomphp/i", $className) && preg_match("/resursbank/i", $className)) {
                    $useNameSpace = "\\Resursbank\\RBEcomPHP\\";
                    break;
                }
            }
            try {
                $reflectionClassName = "{$useNameSpace}{$classfunc}";
                $reflection = new \ReflectionClass($reflectionClassName);
                $instance = $reflection->newInstanceArgs($args);
                // Check availability, fetch and stop on first match
                if (!isset($returnObject) && in_array($func,
                        get_class_methods("{$useNameSpace}Resurs_SimplifiedShopFlowService"))) {
                    $this->serviceReturn = "SimplifiedShopFlowService";
                    $returnObject = $this->simplifiedShopFlowService->$func($instance);
                }
                if (!isset($returnObject) && in_array($func,
                        get_class_methods("{$useNameSpace}Resurs_ConfigurationService"))) {
                    $this->serviceReturn = "ConfigurationService";
                    $returnObject = $this->configurationService->$func($instance);
                }
                if (!isset($returnObject) && in_array($func,
                        get_class_methods("{$useNameSpace}Resurs_AfterShopFlowService"))) {
                    $this->serviceReturn = "AfterShopFlowService";
                    $returnObject = $this->afterShopFlowService->$func($instance);
                }
                if (!isset($returnObject) && in_array($func,
                        get_class_methods("{$useNameSpace}Resurs_ShopFlowService"))) {
                    $this->serviceReturn = "ShopFlowService";
                    $returnObject = $this->shopFlowService->$func($instance);
                }
            } catch (\Exception $e) {
                throw new Exception(__FUNCTION__ . "/" . $func . "/" . $classfunc . ": " . $e->getMessage(),
                    \RESURS_EXCEPTIONS::WSDL_PASSTHROUGH_EXCEPTION);
            }
        }
        try {
            if (isset($returnObject) && !empty($returnObject) && isset($returnObject->return) && !empty($returnObject->return)) {
                /* Issue #63127 - make some dataobjects storable */
                if ($this->convertObjectsOnGet && preg_match("/^get/i", $func)) {
                    $returnContent = $this->getDataObject($returnObject->return);
                } else {
                    $returnContent = $returnObject->return;
                }
                if ($returnAsArray) {
                    return $this->parseReturn($returnContent);
                }
            } else {
                /* Issue #62975: Fixes empty responses from requests not containing a return-object */
                if (empty($returnObject)) {
                    if ($returnAsArray) {
                        return array();
                    }
                } else {
                    if ($returnAsArray) {
                        return $this->parseReturn($returnContent);
                    } else {
                        return $returnObject;
                    }
                }
            }

            return $returnContent;
        } catch (\Exception $returnObjectException) {
        }
        if ($returnAsArray) {
            return $this->parseReturn($returnObject);
        }

        return $returnObject;
    }

}