<?php

namespace Resursbank\Ecommerce\Service\Merchant;

use Exception;
use TorneLIB\Model\Type\RequestMethod;
use TorneLIB\Module\Network\NetWrapper;

class MerchantApiConnector
{
    /**
     * @var array
     */
    protected $urls = [
        'mock' => 'https://apigw.integration.resurs.com',
        'prod' => 'https://apigw.resurs.com',
    ];
    /**
     * @var string[]
     */
    protected $merchantApiServiceUrls = [
        'mock' => '/api/mock_merchant_api_service',
        'prod' => '??',
    ];
    /**
     * @var Token
     */
    protected $token;
    /**
     * @var string[]
     */
    private $tokenRequest = [
        'mock' => '/api/oauth2/token',
        'prod' => '/api/oauth2/token',
    ];
    private $environmentIsTest = true;
    /**
     * @var string
     */
    private $clientId = '';
    /**
     * @var string
     */
    private $clientSecret = '';
    /**
     * @var string
     */
    private $clientScope = '';

    /**
     * @var string
     */
    private $grantType = '';

    /**
     * @var bool
     */
    private $jwtReady = false;

    /**
     * @var NetWrapper $connection
     */
    private $connection;

    /**
     * @return $this
     */
    public function setProduction()
    {
        $this->environmentIsTest = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function setBearer()
    {
        $this->connection->setHeader('Authorization', sprintf('Bearer %s', $this->getAccessToken()));

        return $this;
    }

    /**
     * @return bool
     */
    public function isJwtReady()
    {
        return $this->jwtReady;
    }

    /**
     * @param $clientId
     * @return $this
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;

        return $this->setPreparedJwtInit();
    }

    /**
     * @return $this
     */
    private function setPreparedJwtInit()
    {
        if (!empty($this->clientId) &&
            !empty($this->clientSecret) &&
            !empty($this->clientScope) &&
            !empty($this->grantType)
        ) {
            $this->jwtReady = true;
        }

        return $this;
    }

    /**
     * @param $clientSecret
     * @return $this
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;

        return $this->setPreparedJwtInit();
    }

    /**
     * @param $clientScope
     * @return $this
     */
    public function setScope($clientScope)
    {
        $this->clientScope = $clientScope;

        return $this->setPreparedJwtInit();
    }

    /**
     * @param $grantType
     * @return $this
     */
    public function setGrantType($grantType)
    {
        $this->grantType = $grantType;

        return $this->setPreparedJwtInit();
    }

    /**
     * @return NetWrapper
     * @throws Exception
     */
    public function getMerchantConnection()
    {
        if (!empty($this->token->getAccessToken()) && !$this->hasConnectionBearer()) {
            $this->getConnection()->setHeader(
                'Authorization',
                sprintf('Bearer %s', $this->getToken()->getAccessToken())
            );
        }

        return $this->connection;
    }

    /**
     * @return bool
     */
    private function hasConnectionBearer()
    {
        $storedHeader = $this->connection->getConfig()->getHeader();
        return isset($storedHeader['Authorization']);
    }

    /**
     * Create and/or return communications wrapper.
     * @return NetWrapper
     */
    public function getConnection()
    {
        if (empty($this->connection)) {
            $this->connection = new NetWrapper();
        }

        return $this->connection;
    }

    /**
     * Set connection from external part. Makes it possible to initialize an instance of NetWrapper/CurlWrapper
     * from another service.
     *
     * @param $connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @throws Exception
     */
    public function getToken()
    {
        if (empty($this->token)) {
            $tokenParsedResponse = $this->getConnection()->request(
                sprintf('%s%s', $this->getApiUrl(), $this->getTokenRequestUrl()),
                $this->getJwtData(),
                RequestMethod::POST
            )->getParsed();

            $this->token = new Token(
                $tokenParsedResponse->access_token,
                $tokenParsedResponse->token_type,
                $tokenParsedResponse->expires_in
            );
            $return = $this->token;
        } else {
            $return = $this->token;
        }

        return $return;
    }

    /***
     * @return string
     */
    protected function getApiUrl()
    {
        return (string)($this->isTest() ? $this->urls['mock'] : $this->urls['prod']);
    }

    /**
     * @return bool
     */
    public function isTest()
    {
        return $this->environmentIsTest;
    }

    /**
     * @return string
     */
    private function getTokenRequestUrl()
    {
        return $this->isTest() ? $this->tokenRequest['mock'] : $this->tokenRequest['prod'];
    }

    /**
     * @return array
     * @throws Exception
     */
    private function getJwtData()
    {
        if (!$this->jwtReady) {
            throw new Exception('JWT credentials not ready.', 500);
        }

        return [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => $this->clientScope,
            'grant_type' => $this->grantType,
        ];
    }

    /**
     * @return string
     */
    protected function getMerchantApiUrlService()
    {
        return $this->isTest() ? $this->merchantApiServiceUrls['mock'] : $this->merchantApiServiceUrls['prod'];
    }
}
