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

use TorneAPIClient\LibFacebook;
use TorneAPIClient\LibVbulletin;


/**
 * Class TorneAPIClient
 * @package TorneAPI
 */
class API extends TorneAPICore {

    private $curl = null;

    /** @var null Standard TorneAPI Interface */
    public $API_TorneAPI = null;
    /** @var null Facebook Interface */
    public $API_Facebook = null;
    /** @var null vBulletin Interface */
    public $API_vB = null;

    /** @var null A default CURL channel */
    public $CurlSession = null;

    private $LibrariesLoaded = array();
    public function __construct() {
        $this->curl = new TorneAPI_CURL();
        /* SSL Ceritificate Healer runs first */
        $this->curl->TestCerts();
        $this->InitializeLibraries();
    }
    private function InitializeLibraries() {
        if (($this->API_Facebook = new LibFacebook())) { $this->LibrariesLoaded['LibFacebook'] = true; }

        /** @var API_vB vBulletin does not need any checks */
        $this->API_vB = new LibVbulletin();
        $this->LibrariesLoaded['LibVbulletin'] = true;
    }
    public function getLoadedLibraries() {
        return $this->LibrariesLoaded;
    }

}
