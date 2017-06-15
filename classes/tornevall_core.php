<?php

namespace TorneLIB;

/**
 * Class TorneLIB
 * @package TorneLIB
 */
class TorneLIB {

    /**
     * @package TorneLIB
     * @subpackage TorneLIB-Core
     *
     * The simplifier
     */
    /** @var Tornevall_CURL */
    public $curl;

    /** @var TorneLIB_Database */
    public $Database;

    /** @var TorneLIB_Pluggable */
    public $pluggable;
    public $API;

    public $Network;
    /** @var TorneLIB_Crypto Encryption and encoding  */
    public $Crypto;

    /** @var null Smarty template library */
    private $Smarty = null;

    /** @var TorneLIB_Configuration */
    protected $config;
    private $version = "5.0.0";
    private $library = "";

    /**
     * TorneLIB constructor. Loads all vital functions
     * @param array $options
     */
    public function __construct($options = array()) {
        /* Load primary configuration */
        $this->config = new TorneLIB_Configuration();

        /* Set up core data */
        $this->library = $this->config->getCoreName();

        /* Initialize database driver without connecting somewhere */
        $this->database = new TorneLIB_Database();

        /* Initialize networking driver */
        $this->Network = new TorneLIB_Network();
        
        /* Initialize crypto driver */
        $this->Crypto = new TorneLIB_Crypto();

        /* Initialize CURL-driver */
        $this->curl = new Tornevall_cURL();

        /* Initialize API connector */
        $this->API = new TorneAPI();

        /* Initialize the plugin loader */
        $this->pluggable = new TorneLIB_Pluggable($this->config->getConfigurationObject());
    }
    public function getCurrentVersion() { return $this->version; }
    public function getLibraryName() {
        return TorneLIB_Configuration::CORE_NAME;
    }

    /**
     * A historically important function for most of the web projects running from TornevallWEB. Will still be available from core
     *
     * @param string $fileName
     * @param array $assignedVariables
     * @param bool $isFile
     * @return mixed
     * @throws TorneLIB_Exception
     */
    public function evaltemplate($fileName = "", $assignedVariables = array(), $isFile = true) {
        /* Try initialization separately and only once */
        try {
            if (is_null($this->Smarty)) { $this->Smarty = $this->pluggable->initPluggable('smarty'); }
        } catch (\Exception $e) { throw new TorneLIB_Exception("PHPException: " . $e->getMessage(), $e->getCode(), __FUNCTION__); }

        if (defined('USE_TORNEVALL_SITE_VARS') && USE_TORNEVALL_SITE_VARS === true) {
            global $site;
            if (isset($site) && is_array($site)) { $assignedVariables['site'] = $site; }
        }
        try {
            if (method_exists($this->Smarty, "EvalTemplate")) {
                if (file_exists($fileName)) {
                    return $this->Smarty->EvalTemplate($fileName, $assignedVariables, $isFile);
                } else {
                    throw new TorneLIB_Exception("Template '$fileName' not found!'", TORNELIB_EXCEPTIONS::TORNELIB_CORE_TEMPLATE_NOT_FOUND, __FUNCTION__);
                }
            } else {
                throw new TorneLIB_Exception("Template library not properly loader (EvalTemplate is non existent)", TORNELIB_EXCEPTIONS::TORNELIB_CORE_TEMPLATE_GENERAL, __FUNCTION__);
            }
        } catch (\Exception $e) {
            throw new TorneLIB_Exception("PHPException: " . $e->getMessage(), $e->getCode(), __FUNCTION__);
        }
    }

    /**
     * Convert object to a data object (used for repairing __PHP_Incomplete_Class objects)
     *
     * This function are written to work with WSDL2PHPGenerator, where serialization of some objects sometimes generates, as described, __PHP_Incomplete_Class objects.
     * The upgraded version are also supposed to work with protected values.
     *
     * @param array $objectArray
     * @param bool $useJsonFunction
     * @param string $findMethodPrefix
     * @return array|mixed|object
     * @link http://stackoverflow.com/questions/965611/forcing-access-to-php-incomplete-class-object-properties/35863054#35863054 StackOverflow Reference
     * @link http://tracker.tornevall.net/browse/RESURSLIB-2 ResursLIB Reference
     */
    function arrayObjectToStdClass($objectArray = array(), $useJsonFunction = false, $findMethodPrefix = 'get')
    {
        $newArray = array();
        /**
         * If json_decode and json_encode exists as function, do it the simple way.
         * Note: If you use a newer version of WSDL2PHPGenerator, this will fail since the exporter will save output data as protected variables.
         * From ResursLIB this is only secondary.
         * http://php.net/manual/en/function.json-encode.php
         */
        if ($useJsonFunction && function_exists('json_decode') && function_exists('json_encode')) {
            return json_decode(json_encode($objectArray));
        }
        if (is_array($objectArray) || is_object($objectArray)) {
            $hasObject = false;
            foreach ($objectArray as $itemKey => $itemValue) {
                if (is_array($itemValue)) {
                    $newArray[$itemKey] = (array)$this->NormalizeObject($itemValue);
                } elseif (is_object($itemValue)) {
                    $objectMethods = @get_class_methods($itemValue);
                    if (is_array($objectMethods) && count($objectMethods)) {
                        $hasObject = true;
                        $newArray = array();
                        foreach ($objectMethods as $objectMethodName) {
                            if (!empty($objectMethodName) && preg_match("/^".$findMethodPrefix."/i", $objectMethodName)) {
                                $prefixFirst = substr($objectMethodName,0,1);
                                $itemKey = preg_replace("/^".$findMethodPrefix."/", '', $objectMethodName);
                                if ($prefixFirst == strtolower($prefixFirst)) {
                                    $itemKey = lcfirst($itemKey);
                                } else if ($prefixFirst == strtoupper($prefixFirst)) {
                                    $itemKey = ucfirst($itemKey);
                                }
                                $newArray[$itemKey] = $itemValue->$objectMethodName();
                            }
                        }
                        if ($hasObject) {
                            /*
                             * If this is an object from the beginning, we'll cast it back as on object
                             */
                            $newArray = (object)$newArray;
                        }
                    } else {
                        $newArray[$itemKey] = (object)(array)$this->NormalizeObject($itemValue);
                    }
                } else {
                    $newArray[$itemKey] = $itemValue;
                }
            }
        }
        return $newArray;
    }

    public function objectsIntoArray($arrObjData, $arrSkipIndices = array())
    {
        $arrData = array();
        // if input is object, convert into array
        if (is_object($arrObjData)) {$arrObjData = get_object_vars($arrObjData);}
        if (is_array($arrObjData))
        {
            foreach ($arrObjData as $index => $value)
            {
                if (is_object($value) || is_array($value))
                {
                    $value = $this->objectsIntoArray($value, $arrSkipIndices); // recursive call
                }
                if (@in_array($index, $arrSkipIndices))
                {
                    continue;
                }
                $arrData[$index] = $value;
            }
        }
        return $arrData;
    }

    /**
     * base64_encode
     *
     * @param $data
     * @return string
     */
    public function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * base64_decode
     *
     * @param $data
     * @return string
     */
    public function base64url_decode($data)
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * Finds out if a bitmasked value is located in a bitarray
     *
     * @param int $requestedBitValue
     * @param int $matchWith
     * @return bool
     */
    public function isBit($requestedBitValue = 0, $matchWith = 0)
    {
        preg_match_all("/\d/", sprintf("%08d", decbin($matchWith)), $bitArray);
        for ($bitCount = count($bitArray[0]); $bitCount >= 0; $bitCount--) {
            if (isset($bitArray[0][$bitCount])) {
                if ($matchWith & pow(2, $bitCount)) {
                    if ($requestedBitValue == pow(2, $bitCount)) { return true; }
                }
            }
        }
        return false;
    }
}
