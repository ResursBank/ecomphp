<?php

/**
 * Tornevall Networks pluggable API Client Version 4.x
 * A simple API extender for TorneLIB, built as a standalone client
 *
 * @package TorneAPI
 * @author Tomas Tornevall <thorne@tornevall.net>
 * @version 4.0.0
 * @copyright 2015-2017
 * @link http://docs.tornevall.net/x/FoBU A part of TorneLIB v4
 */

namespace TorneLIB\API;

use TorneLIB\Tornevall_cURL;

/**
 * Class CoreAPI
 * @package TorneLIB\API
 */
class CoreAPI {
    private $Client = "TorneLIB-API";
    private $Version = "5.0.0";
    protected $Curl = null;

    public function __construct($curlOptions = array())
    {
        $this->Curl = new Tornevall_cURL();
        if (version_compare(phpversion(), '5.4.0', '>=')) {$this->incTorneAPI(TORNEAPI_LIBRARY . '/lib_facebook.php');}
        $this->incTorneAPI(TORNEAPI_LIBRARY . '/lib_vbulletin.php');
        $this->incTorneAPI(TORNEAPI_LIBRARY . '/lib_torneapi.php');
    }

    private function incTorneAPI($APIFileInclude='')
    {
        if (file_exists($APIFileInclude)) {
            require_once $APIFileInclude;
        }
    }

    /** Get this client name */
    public function getClientName() {
        return $this->Client;
    }
    /** Get this client version */
    public function getClientVersion() {
        return $this->Version;
    }
}