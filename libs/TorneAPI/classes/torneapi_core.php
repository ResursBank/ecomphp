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

namespace TorneAPIClient;

if (version_compare(phpversion(), '5.4.0', '>=')) {require_once TORNEAPI_LIBRARY . '/lib_facebook.php';}
require_once TORNEAPI_LIBRARY . '/lib_vbulletin.php';

/**
 * Class TorneAPI_Core
 * @package TorneAPIv4
 */
class TorneAPICore {

    private $Client = "TorneAPI Bridge";
    private $Version = "4.0.0";
    public $CurlSession = null;

    public function __construct()
    {
        $this->CurlSession = new TorneAPI_CURL();
    }

    public function getClientName() {
        return $this->Client;
    }
    public function getClientVersion() {
        return $this->Version;
    }
    public function initCurlSession($curlOptions = array()) {
        $curlSession = new TorneAPI_CURL($curlOptions);
        return $curlSession;
    }

}