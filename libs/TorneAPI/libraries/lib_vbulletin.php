<?php

/**
 * @package TorneAPI
 */

namespace TorneAPIClient;

    /**
     * @package TorneAPI-vBulletinBridge
     */

    use TorneAPIClient\TorneAPICore;

    /**
     * Class LibVbulletin
     *
     * vBulletin Bridge Alpha. Supposed to work with vBulletin 5 Connect and the current API.
     * Based on vBulletin 4 Mobile API, so it probably works with vB4 also. API calls in vB 5 are currently not used.
     *
     * @package TorneAPILibs
     */
class LibVbulletin extends TorneAPICore
{

    /** @var string The vBAPI key stored through API calls */
    private $APIKey = null;
    /** @var string Location URL for vBAPI */
    private $endPoint = null;
    /** @var array Parameters for where our calls are being made */
    private $requestParams = array();
    /** @var object The cURL resource initialized from TorneAPI-Client */
    private $curlSession = null;

    /** @var string vBAPI Specific data */
    private $apiaccesstoken = null;
    /** @var string vBAPI Specific data */
    private $apiclientid = null;
    /** @var string vBAPI Specific data */
    private $vapisecret = null;
    /** @var string vBAPI What API version we're using in primary calls */
    private $vapiversion = null;
    /** @var string vBAPI Specific data */
    private $vbulletinversion = null;

    /** @var string Unique ID for vbulletin sessions */
    private $vbulletinUniq = null;

    private $vbulletinInit = false;

    /**
     * LibVbulletin constructor.
     * @param string $endPoint URL for where the API is located
     * @param string $APIKey API key, used to communicate with vBAPI.
     */
    function __construct($endPoint = '', $APIKey = '')
    {
        $this->endPoint = $endPoint;
        $this->APIKey = $APIKey;
        /* Init now or init later */
        if (!empty($endPoint) && !empty($APIKey)) {
            return $this->API_Init();
        }
    }

    public function init($endPoint = '', $APIKey = '')
    {
        $this->endPoint = $endPoint;
        $this->APIKey = $APIKey;
        return $this->API_Init();
    }

    private function setUniq()
    {
        $this->vbulletinUniq = sha1(uniqid(sha1(microtime()), true));
    }

    /**
     * Primary API parameter collector. This should always be sent in our post parameters. Current API is using vBulletin4 Mobile API as seen below
     * @return array
     */
    private function initRequestParams()
    {
        return array('clientname' => "TorneAPI",
            'clientversion' => parent::getClientVersion(),
            'platformname' => "TorneAPIBridge-PHP",
            'platformversion' => parent::getClientVersion(),
            'uniqueid' => $this->vbulletinUniq,
            'api_v' => 4
        );
    }

    /**
     * Initialize the API, get the token, and continue.
     *
     * @return bool
     * @throws \Exception
     */
    private function API_Init()
    {
        if (empty($this->endPoint)) {
            throw new \Exception("No vBulletin endpoint set");
        }
        $this->setUniq();
        $this->requestParams = $this->initRequestParams();
        $this->requestParams['api_m'] = "api_init";
        $this->CurlSession = $this->initCurlSession($this->endPoint);
        $initSession = $this->CurlSession->doPost($this->endPoint, $this->requestParams);
        $this->requestParams = array();
        if (!empty($initSession) && !empty($initSession['parsed'])) {
            //$initSessionResponse = json_decode($initSession);
            $initSessionResponse = $initSession['parsed'];
            $this->apiaccesstoken = isset($initSessionResponse->apiaccesstoken) ? $initSessionResponse->apiaccesstoken : null;
            $this->apiclientid = isset($initSessionResponse->apiclientid) ? $initSessionResponse->apiclientid : null;
            $this->bbtitle = isset($initSessionResponse->bbtitle) ? $initSessionResponse->bbtitle : null;
            $this->vbulletinversion = isset($initSessionResponse->vbulletinversion) ? $initSessionResponse->vbulletinversion : null;
            $this->forumhome = isset($initSessionResponse->forumhome) ? $initSessionResponse->forumhome : null;
            $this->vapiversion = isset($initSessionResponse->apiversion) ? $initSessionResponse->apiversion : null;
            $this->vapisecret = isset($initSessionResponse->secret) ? $initSessionResponse->secret : null;
            $this->vbulletinInit = true;
            return true;
        } else {
            $this->vbulletinInit = false;
            return false;
        }
    }

    /**
     * Primary API post function. Using the POST-method only and all calls are being made by the centralized curl-initializer in the TorneAPI-Client.
     * @param string $methodName
     * @param array $postParams
     * @return array|mixed|null
     */
    private function vbPost($methodName = '', $postParams = array())
    {
        if (!$this->vbulletinInit) {
            $this->API_Init();
        }
        $this->requestParams = $this->initRequestParams();
        $this->requestParams['api_m'] = $methodName;
        $simpleSignature = array('api_m' => $methodName);

        if (is_array($postParams) && count($postParams)) {
            foreach ($postParams as $postParam => $postValue) {
                $this->requestParams[$postParam] = $postValue;
            }
        }
        ksort($this->requestParams);

        /* Debugging related*/
        //$signature = md5(http_build_query($this->requestParams, '', '&') . $this->apiaccesstoken . $this->apiclientid . $this->vapisecret . $this->APIKey);
        //$signature = md5(http_build_query($simpleSignature, '', '&') . $this->apiaccesstoken . $this->apiclientid . $this->apisecret . $this->APIKey);

        /* How to debug: [...vBulletin-path...]/core/vb/session/api.php */
        $signature = md5(http_build_query(array(), '', '&') . $this->apiaccesstoken . $this->apiclientid . $this->vapisecret . $this->APIKey);
        $this->requestParams['api_c'] = $this->apiclientid;
        $this->requestParams['api_secret'] = $this->vapisecret;
        $this->requestParams['api_s'] = $this->apiaccesstoken;
        $this->requestParams['api_sig'] = $signature;

        //$this->curlSession = parent::initcurl($this->endPoint, $this->requestParams);
        //$this->CurlSession = parent::initCurlSession($this->endPoint, $this->requestParams);
        $response = $this->CurlSession->doPost($this->endPoint, $this->requestParams);

        if (!empty($response) && !empty($response['parsed'])) {
            return $response['parsed'];
        } else {
            return null;
        }
    }

    /*
     * API Calls Section.
     */

    /**
     * List threads
     * @param int $forumid
     * @return array|mixed|null
     */
    public function forumdisplay($forumid = 0)
    {
        if (!is_numeric($forumid)) {
            return null;
        }
        $vBResponse = $this->vbPost('forumdisplay', array('forumid' => $forumid));
        return $vBResponse;
    }

    /**
     * Get the thread
     *
     * @param int $threadid
     * @return array|mixed|null
     */
    public function showthread($threadid = 0)
    {
        if (!is_numeric($threadid)) {
            return null;
        }
        $vBResponse = $this->vbPost('showthread', array('threadid' => $threadid));
        return $vBResponse;
    }

    /**
     * TO DO Section
     *
     * Currently for support with Votech, we need the functions listed below, so they are in extra priority.
     *
     * For postings between wordpress and forums:
     * - newthread
     * - newreply
     *
     * For registrations between TornevallWEB v4 and vBulletin:
     * - login
     * - register
     *
     */
}

