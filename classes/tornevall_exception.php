<?php

namespace TorneLIB;

/**
 * Class TorneLIB_Exception, for exceptionhandling
 * @package TorneLIB
 */
class TorneLIB_Exception extends \Exception {
    private $exceptionFunctionName = null;
    public function __construct($message = 'Unknown exception', $code = 0, $exceptionFunctionName = null, \Exception $previous = null) {
        $this->exceptionFunctionName = $exceptionFunctionName;
        parent::__construct($message, $code, $previous);
    }
    public function __toString() {
        if (null === $this->exceptionFunctionName) {
            return "TorneLIB-PHP Exception: [{$this->code}]: {$this->message}";
        } else {
            return "TorneLIB-PHP {$this->exceptionFunctionName}Exception {$this->code}: {$this->message}";
        }
    }
    public function getFunctionName() {
        return $this->exceptionFunctionName;
    }
}

/**
 * Class TorneLIBException
 * 
 * Inherited from TorneLIB_Exception. For backwards compatibility, it has been placed here.
 * 
 * @package TorneLIB
 */
class TorneLIBException extends TorneLIB_Exception {}

/**
 * Class API_Error_Handler - ErrorCodes for TorneLIB
 *
 * @package TorneLIB
 */
class TORNELIB_EXCEPTIONS {
    const TORNELIB_NO_ERROR = 0;
    
    /** Any error that can not defined in another way than this */
    const TORNELIB_GENERAL = 1;
    
    const TORNELIB_CORE_TEMPLATE_GENERAL = 1000;
    const TORNELIB_CORE_TEMPLATE_LIB_EXCEPTION = 1001;
    const TORNELIB_CURL_COOKIE_PATH_FAIL = 1002;
    const TORNELIB_CURL_IPLIST_GENERAL = 1003;
    const TORNELIB_CURL_RETRIES_FAIL = 1004;
    const TORNELIB_DNSBL_EXCEPTION_GENERAL = 1005;
    const TORNELIB_CORE_TEMPLATE_NOT_FOUND = 1006;
    const TORNELIB_CRYPTO_KEY_EXCEPTION = 1007;
    
    /**
     * Database component errors
     */
    const TORNELIB_DB_GENERAL = 3000;
    const TORNELIB_DB_INITIALIZATION_ERROR = 3001;
    const TORNELIB_DB_NO_HOST = 3002;
    const TORNELIB_DB_NO_CONNECTION = 3003;
    const TORNELIB_DB_NO_DRIVER = 3004;
    const TORNELIB_DB_NO_DRIVER_MYSQL = 3005;
    const TORNELIB_DB_OVERRIDE_DENIED = 3006;
    const TORNELIB_DB_CONNECT_MYSQL_FAIL = 3007;
    const TORNELIB_DB_CONNECT_PDO_FAIL = 3008;
    const TORNELIB_DB_NO_DRIVER_PDO = 3009;
    const TORNELIB_DB_CREDENTIALS_GENERAL = 3010;
    const TORNELIB_DB_CREDENTIALS_PARAMETER_NOT_SET = 3011;
    const TORNELIB_DB_QUERY_NO_CONNECTION = 3012;
    const TORNELIB_DB_QUERY_RAW_DENIED = 3013;
    const TORNELIB_DB_QUERY_PDO_METHOD_FAIL = 3014;
    const TORNELIB_DB_QUERY_GENERAL = 3015;
    const TORNELIB_DB_QUERY_MYSQLI_PREPARE_EXCEPTION = 3016;
    const TORNELIB_DB_QUERY_MYSQL_PREPARE_EXCEPTION = 3017;
    const TORNELIB_DB_QUERY_MYSQLPDO_PREPARE_EXCEPTION = 3018;
}