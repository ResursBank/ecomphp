<?php

/**
 * TorneAPI simple FacebookSDK 5x bridge.
 * @package TorneAPIv2
 */

/*
 * Written as an addon since Facebook API's are constantly upgrading and changes behaviour. This bridge should protect us from such problems (eventually),
 * starting with Facebook SDK 5.0.0 - alternatively making the usage even easier at a TornevallWEB v4 point of view.
 *
 * Information
 *      Initially belongs to http://tracker.tornevall.net/browse/TAPIB-33
 *
 * Graph API Deprecation Schedule (https://developers.facebook.com/docs/apps/changelog)
 * Graph Versioning: https://developers.facebook.com/docs/apps/versions
 *
 * Graph API EOL
 *
 *      2.3 - July 8, 2017
 *      2.4 - October 7, 2017
 *      2.5 - April 12, 2018
 *      2.6 - July 13, 2018
 *      2.7 - October 5, 2018
 *      2.8 - April 18, 2019
 *      2.9 - October 2019
 *
 */

namespace TorneLIB\API;

    use Facebook\Exceptions\FacebookResponseException;
    use Facebook\Exceptions\FacebookSDKException;

    /**
     * Class FacebookBridge
     * @package TorneAPI-FacebookBridge
     */
    class LibFacebook
    {

        /** @var null|string Path where to find the SDK */
        public $SDK_PATH = null;
        /** @var bool Tells us wheter the SDK has been found or not */
        public $SDK_FOUND = false;
        /** @var bool Tells us wheter the SDK has been properly loaded or not */
        public $SDK_LOADED = false;
        /** @var \Facebook\Facebook|null The Facebook Interface. As long as it's not loaded, this one is null */
        public $Facebook = null;
        /** @var string What SDK we currently communicating with. Reserved for future use */
        public $SDK_CURRENT = "5.5";
        /** @var string Our version */
        private $version = "1.0.0";

        private $facebookHandlerUrl;

        /** @var array Default permissions on login */
        public $permissions = array('user_about_me', 'email');
        private $requestPermissions = array();

        /** @var array Precompiled permission restrictions, decides what api-version we should use, depending on permission setup. This variable has been invented to be able to use more than one API version in the same time. For example user_groups permissions are only available in 2.3 or lesser. */
        private $permission_restrictions = array(
            'user_groups' => '2.3'
        );

        /** @var null Facebook application id */
        private $appId = null;

        /** @var null Facebook application secret */
        private $appSecret = null;

        /** @var null|string Last error message generated from the bridge or SDK */
        private $lastException = null;

        /** @var null oAuth2Client */
        private $oAuth2Client = null;

        /** @var null Current helper */
        private $currentHelper = null;
        private $currentHelperType;
        private $LoginURL;

        /** @var string Decide what graph version that should be used as default */
        public $useGraph = "2.9";

        /** @var null Facebook user access token, also set in _SESSION */
        private $accessToken = null;

        /** @var null|object Information about current user */
        public $userInfo = null;

        /** @var array What fields to retrieve by default from GetUserInfo. Setting this value to null, will fetch all fields available from Facebook. Note: In newer graph-interfaces, fields must be defined to return info. In 2.2, null will return all fields */
        public $userInfoFields = array('id', 'name', 'email');

        /**
         * The constructor where initializing the API is being made. If string is empty, we're trying to autodetect the API in some default paths. Otherwise, we'll try the path you give us.
         * @param string $SDK_PATH
         *
         */
        function __construct($appId = 0, $appSecret = null, $SDK_PATH = '')
        {
            if (version_compare(phpversion(), '5.4.0', '<')) {
                $this->SDK_LOADED = false;
                $this->lastException = "PHP 5.4 or greater is required for this SDK to work";
                return;
            }
            if (!intval($appId) && is_null($appSecret)) {
                return;
            }
            $this->appId = $appId;
            $this->appSecret = $appSecret;

            if (!is_null($SDK_PATH) && file_exists($SDK_PATH . "/autoload.php")) {
                $this->SDK_PATH = $SDK_PATH;
            } else {
                // For SDK 5 and TorneAPI package it should be easy to find the SDK if not touching defaults
                if (file_exists(__DIR__ . "/Facebook/autoload.php")) {
                    $this->SDK_PATH = __DIR__ . "/Facebook/";
                } else if (file_exists(__DIR__ . "/src/Facebook/autoload.php")) {
                    $this->SDK_PATH = __DIR__ . "/Facebook/";
                }
            }
            if (!empty($this->SDK_PATH)) {
                /* Silently require the API */
                @require $this->SDK_PATH . "/autoload.php";
                try {
                    $this->Facebook = new \Facebook\Facebook([
                        'app_id' => $this->appId,
                        'app_secret' => $this->appSecret,
                        'default_graph_version' => 'v' . $this->useGraph,
                    ]);
                    $this->SDK_LOADED = true;
                    $this->oAuth2Client = $this->Facebook->getOAuth2Client();
                } catch (FacebookSDKException $SDKException) {
                    $this->SDK_LOADED = false;
                    $this->lastException = $SDKException->getMessage();
                }
            } else {
                $this->lastException = "SDK not found";
            }
            return $this->SDK_LOADED;
        }
        public function setCallbackUrl($callbackUrl = "") {
            $this->facebookHandlerUrl = $callbackUrl;
        }

        /**
         * @param string $callbackUrl
         */
        public function Initialize($callbackUrl = "", $permissions = array()) {
           $this->GetCurrentHelper();
           if (!empty($callbackUrl)) {
               $this->setCallbackUrl($callbackUrl);
           }
           $this->setPermissions($permissions);
           if (!$this->HasSession()) {
               $this->GetSession();
               $this->LoginURL = $this->setLoginUrl();
           }
        }
        private function PrepareRequest($endPoint = "/me?fields=id,name,email", $parameters = array(), $accessToken = '', $RequestType = "post")
        {
            $FacebookResponse = null;
            if (empty($accessToken)) {
                if (!empty($this->ExtendSession())) {
                    $accessToken = $this->ExtendSession();
                } else if (!empty($this->GetSession())) {
                    $accessToken = $this->GetSession();
                } else {
                    throw new \Exception("Access token missing", 403);
                }
            }
            try {
                if ($RequestType == "post") {
                    $Response = $this->Facebook->post($endPoint, $parameters, $accessToken);
                } else {
                    $Response = $this->Facebook->get($endPoint, $accessToken);
                }
            } catch (FacebookResponseException $e) {
                return null;
            } catch (FacebookSDKException $e) {
                return null;
            }
            $DecodedBody = $Response->getDecodedBody();
            if (isset($DecodedBody['data'])) {
                return $DecodedBody['data'];
            } else {
                return $DecodedBody;
            }
        }
        public function Get($endPoint = "/me?fields=id,name,email", $parameters = array(), $accessToken = '') {
            return $this->PrepareRequest($endPoint, $parameters, $accessToken, "get");
        }
        public function Post($endPoint = "/me?fields=id,name,email", $parameters = array(), $accessToken = '') {
            return $this->PrepareRequest($endPoint, $parameters, $accessToken, "post");
        }

        public function setPermissions($userPermissionRequest = array()) {
            $this->requestPermissions = array();
            foreach ($this->permissions as $defaults) {
                $this->requestPermissions[] = $defaults;
            }
            if (count($userPermissionRequest)) {
                foreach ($userPermissionRequest as $additional) {
                    $this->requestPermissions[] = $additional;
                }
            }
        }

        /**
         * Autodetect helper to use $this->currentHelper
         *
         * @return \Facebook\Helpers\FacebookRedirectLoginHelper|\Facebook\Helpers\FacebookCanvasHelper|null
         * @throws \Exception
         */
        public function GetCurrentHelper()
        {
            $this->Facebook->getCanvasHelper();
            $nonCanvasHelper = $this->Facebook->getRedirectLoginHelper();
            try {
                $canvasAccessToken = $nonCanvasHelper->getAccessToken();
                $this->currentHelper = $nonCanvasHelper;
                $this->currentHelperType = "redirect";
            } catch (FacebookResponseException $canvasResponseException) {
            } catch (FacebookSDKException $SDKException) {
                throw new \Exception($SDKException->getMessage());
            }
            // Continue looking on failure
            if (is_null($this->currentHelper)) {
                try {
                    $canvasAccessToken = $canvasHelper->getAccessToken();
                    $this->currentHelper = $canvasHelper;
                    $this->currentHelperType = "canvas";
                } catch (FacebookResponseException $canvasResponseException) {
                } catch (FacebookSDKException $SDKException) {
                    throw new \Exception($SDKException->getMessage());
                }
            }
            return $this->currentHelper;
        }
        /**
         * Get an access token if available
         *
         * @param bool $returnAsObject
         * @return \Facebook\Authentication\AccessToken|null
         * @throws \Exception
         */
        public function GetSession($returnAsObject = false)
        {
            $canvasHelper = $this->Facebook->getCanvasHelper();
            $canvasAccessToken = null;
            $nonCanvasHelper = $this->Facebook->getRedirectLoginHelper();
            $nonCanvasAccessToken = null;
            /* If session is already set, use the token from there*/
            if (isset($_SESSION['facebook_access_token']) && !empty($_SESSION['facebook_access_token'])) {
                if (!$returnAsObject) {
                    $this->accessToken = $_SESSION['facebook_access_token'];
                    return $this->accessToken;
                }
            }
            try {
                $nonCanvasAccessToken = $nonCanvasHelper->getAccessToken();
                if ($returnAsObject && is_object($nonCanvasAccessToken)) {
                    return $nonCanvasAccessToken;
                }
                $this->accessToken = $nonCanvasAccessToken;
            } catch (FacebookResponseException $nonCanvasResponseException) {
            } catch (FacebookSDKException $SDKException) {
                throw new \Exception($SDKException->getMessage());
            }
            try {
                $canvasAccessToken = $canvasHelper->getAccessToken();
                if ($returnAsObject && is_object($canvasAccessToken)) {
                    return $canvasAccessToken;
                }
                $this->accessToken = $canvasAccessToken;
            } catch (FacebookResponseException $canvasResponseException) {
            } catch (FacebookSDKException $SDKException) {
                throw new \Exception($SDKException->getMessage());
            }
            if (!is_null($nonCanvasAccessToken)) {
                $this->currentHelper = $nonCanvasHelper;
                $_SESSION['facebook_access_token'] = (string)$nonCanvasAccessToken;
                return $nonCanvasAccessToken;
            } elseif (!is_null($canvasAccessToken)) {
                $this->currentHelper = $canvasHelper;
                $_SESSION['facebook_access_token'] = (string)$canvasAccessToken;
                return $canvasAccessToken;
            } else {
                return null;
            }
        }
        /**
         * Internal method to make sure that we have a proper user session before doing anything with the API
         * @return bool|void
         * @throws \Exception
         */
        private function HasSession()
        {
            if (is_null($this->accessToken)) {
                if (is_null($this->GetSession())) {
                    return;
                }
            }
            return true;
        }
        /**
         * Retrieve correct login url for Facebook by testing both canvas- and redirecthelper
         *
         * @param $baseCallbackURL
         * @param array $permissions
         * @param string $helper
         * @return string
         */
        public function setLoginUrl($baseCallbackURL = null, $permissions = array())
        {
            $UseURL = $this->facebookHandlerUrl;
            if (empty($UseURL) && is_null($baseCallbackURL)) {
                throw new \Exception("No callback URL defined", 404);
            }
            $baseCallbackURL = $UseURL;
            die($baseCallbackURL);
            if ($this->currentHelperType != "redirect") {
                $helper = $this->Facebook->getCanvasHelper();
            } else {
                $helper = $this->Facebook->getRedirectLoginHelper();
            }
            /*
             * Graph version requirement checker. This is function only goes backwards, so if you request for permissions that requires both high and low graph versions, the lowest version will win the tests
             * Used to make user_groups permissions available if that's requested. Otherwise, we will use the highest version set in $useGraph.
             */
            $graphVersionTest = $this->useGraph;
            foreach ($this->requestPermissions as $permission) {
                if (isset($this->permission_restrictions[$permission])) {
                    if (version_compare($this->useGraph, $this->permission_restrictions[$permission], ">") && version_compare($graphVersionTest, $this->permission_restrictions[$permission], ">")) {
                        $graphVersionTest = $this->permission_restrictions[$permission];
                    }
                }
            }
            return $helper->getLoginUrl($baseCallbackURL, $this->requestPermissions);
        }
        /**
         * Extending Facebook session.
         * @return \Facebook\Authentication\AccessToken
         */
        public function ExtendSession()
        {
            if ($this->HasSession()) {
                return $this->oAuth2Client->getLongLivedAccessToken($this->accessToken);
            }
            return false;
        }
        /**
         * Get the initialized loginurl for the authentication
         *
         * @return mixed
         */
        public function getLoginUrl() {
            return $this->LoginURL;
        }

        /**
         * Current access token if any, null if not - same as getSession
         * @return
         */
        public function GetAccessToken()
        {
            if (!$this->accessToken) {
                return null;
            }
            return $this->accessToken;
        }

        /**
         * Get last error from SDK or API Bridge
         * @return null|string
         */
        public function GetLastError()
        {
            return $this->lastException;
        }

        /**
         * Prepare for logging out
         * @param $baseCallbackURL
         * @return mixed
         * @throws \Exception
         */
        public function GetLogoutURL($baseCallbackURL)
        {
            if ($this->accessToken) {
                $this->GetCurrentHelper();
            }
            if (!is_null($this->currentHelper) && $this->accessToken) {
                return $this->currentHelper->getLogoutUrl($this->accessToken, array('next' => $baseCallbackURL));
            }
        }

        /**
         * Get information about the current user from Facebook
         * @param null $getUserInfoFields
         * @param bool|true $returnAsArray Makes this function return userinfo as a dynamic keyed array
         * @return array|\Facebook\GraphNodes\GraphUser|null|object|void
         */
        public function GetUserInfo($getUserInfoFields = null, $returnAsArray = true)
        {
            if (!is_null($this->userInfo)) {
                return $this->userInfo;
            }
            if (!$this->HasSession()) {
                return;
            }
            $useFields = (is_array($getUserInfoFields) && count($getUserInfoFields) ? $getUserInfoFields : $this->userInfoFields);

            try {
                if (!is_null($useFields)) {
                    $response = $this->Facebook->get('/me?fields=' . implode(",", $useFields), $this->accessToken);
                } else {
                    $response = $this->Facebook->get('/me', $this->accessToken);
                }
            } catch (FacebookResponseException $e) {
                return null;
            } catch (FacebookSDKException $e) {
                return null;
            }
            $this->userInfo = $response->getGraphUser();
            $userDataArray = array();
            $fieldNames = $this->userInfo->getFieldNames();
            foreach ($fieldNames as $fieldName) {
                $userDataArray[$fieldName] = $this->userInfo->getField($fieldName);
            }
            if ($returnAsArray) {
                return $userDataArray;
            } else {
                return $this->userInfo;
            }
        }

        /**
         * Get an array of current granted permissions
         * @return array
         */
        public function GetPermissions()
        {
            if (!$this->HasSession()) {
                return;
            }
            try {
                $response = $this->Facebook->get('/me/permissions', $this->accessToken);
                $responseObject = $response->getGraphEdge()->asArray();
                $permissionArray = array();
                if (is_array($responseObject) && count($responseObject)) {
                    foreach ($responseObject as $responseArray) {
                        if ($responseArray['status'] == "granted") {
                            $permissionArray[] = $responseArray['permission'];
                        }
                    }
                }
            } catch (FacebookResponseException $e) {
                return null;
            } catch (FacebookSDKException $e) {
                return null;
            }
            return $permissionArray;
        }

        /**
         * Find out if user is permitted to do a specific request
         * @param string $permissionName
         * @return bool
         */
        public function HasPermission($permissionName = null)
        {
            $permissionList = $this->GetPermissions();
            if (is_array($permissionList) && in_array($permissionName, $permissionList)) {
                return true;
            }
        }

        /**
         * Get the expiration time of the user token. This is being made by extending session, since we can't fetch the timestamp in a regular way.
         * @return int Returned in unix timestamp
         */
        public function GetExpire()
        {
            try {
                $getExtendedSessionData = $this->ExtendSession();
                $getDateObject = $getExtendedSessionData->getExpiresAt();
                return $getDateObject->getTimestamp();
            } catch (\Exception $e) {
            } catch (FacebookSDKException $sE) {
            } catch (FacebookResponseException $fE) {
            }
        }

        /** Get the current version of FacebookBridge */
        public function GetCurrentVersion()
        {
            return $this->version;
        }
    }
