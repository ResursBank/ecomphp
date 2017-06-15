<?php

/**
 * @package TorneLIB/API
 */
namespace TorneLIB\API;

use TorneLIB\CURL_POST_AS;

class LibTornevall extends CoreAPI {

    /** @var string Production server */
    private $URL_Production = "https://api.tornevall.net/";

    /** @var string Development server */
    private $URL_Test = "https://api.tornevall.nu/";

    /** @var string Current target environment */
    private $URL;

    /** @var string Current target version */
    private $Version = "2.0";

    public function __construct()
    {
        parent::__construct();
        $this->URL = $this->URL_Production;
    }

    /**
     * Change target environment (Default is TorneAPI Production server)
     *
     * @param bool $productionService
     */
    public function setServiceDestination($productionService = true) {
        if ($productionService) {
            $this->URL = $this->URL_Production;
        } else {
            $this->URL = $this->URL_Test;
        }
    }

    /**
     * Get the current destination of API service
     *
     * @return string
     */
    public function getServiceDestination() {
        return $this->URL;
    }

    /**
     * Return the correct parsed content to the API client
     *
     * @param $verb
     * @param $Response
     * @return null
     * @throws \Exception
     */
    private function Parse($verb, $Response) {
        $ParsedResponse = null;
        if (isset($Response['parsed']->response)) {
            $ParsedResponse = isset($Response['parsed']->response) ? $Response['parsed']->response : null;
            $ErrorControl = isset($Response['parsed']->errors) ? $Response['parsed']->errors : null;
            if (isset($ErrorControl->success)) {
                if ($ErrorControl->success === true || $ErrorControl->success === "1") {
                    $fromVerb = $verb . "Response";
                    $newParsedResponse = null;
                    if (isset($ParsedResponse->$fromVerb)) {
                        $newParsedResponse = $ParsedResponse->$fromVerb;
                    } else if (count($ParsedResponse) == 1) {
                        $keyedParsedResponse = key($ParsedResponse);
                        $newParsedResponse = isset($ParsedResponse->$keyedParsedResponse) ? $ParsedResponse : null;
                        $ParsedResponse = $newParsedResponse;
                    }
                } else {
                    throw new \Exception($ErrorControl->faultstring, $ErrorControl->code);
                }
            } else {
                throw new \Exception("Could not parse API response properly in success control", 404);
            }
        } else {
            throw new \Exception("Could not parse API response properly in response control", 404);
        }
        return $ParsedResponse;
    }

    /**
     * Extract the correct service url
     * @param string $Verb
     * @return string
     */
    private function getServiceUrl($Verb = "") {
        return $this->URL . $this->Version . "/" . $Verb;
    }

    /**
     * Set API version destination
     * @param string $Version
     */
    public function setApiVersion($Version = "2.0") {
        $this->Version = $Version;
    }

    /**
     * Get() something from the API
     *
     * @param string $Verb
     * @param array $PostData
     * @return null
     */
    public function Get($Verb = "", $PostData = array()) {
        $Response = $this->Parse($Verb, $this->Curl->doGet($this->getServiceUrl($Verb)));
        return $Response;
    }

    /**
     * Post() something to the API
     *
     * @param string $Verb
     * @param array $PostData
     * @return null
     */
    public function Post($Verb = "", $PostData = array()) {
        if (is_array($PostData)) {
            $PostData['auth_remote'] = $_SERVER['REMOTE_ADDR'];
        }
        $PostService = $this->Curl->doPost($this->getServiceUrl($Verb), $PostData);
        $Response = $this->Parse($Verb, $PostService);
        return $Response;
    }

}