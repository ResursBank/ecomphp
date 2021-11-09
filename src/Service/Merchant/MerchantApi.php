<?php

namespace Resursbank\Ecommerce\Service\Merchant;

use TorneLIB\Exception\ExceptionHandler;

class MerchantApi extends MerchantApiConnector
{
    /**
     * @return array
     * @throws ExceptionHandler
     */
    public function getStores()
    {
        return ($this->getMerchantConnection()->request(
            $this->getRequestUrl('stores')
        )->getParsed())->{'content'};
    }

    /**
     * @param $resource
     * @return string
     */
    private function getRequestUrl($resource)
    {
        return sprintf(
            '%s%s/%s',
            $this->getApiUrl(),
            $this->getMerchantApiUrlService(),
            $resource
        );
    }
}
