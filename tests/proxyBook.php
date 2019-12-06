<?php

// Tests excluded from the suite.

/**
 * @test
 * @testdox This test is not creating a full order, it just gets the iframe as we need manual interactions by
 *     customer included..
 * @throws \Exception
 */
public function proxyByBookRcoHalfway()
{
    $CURL = $this->TEST->ECOM->getCurlHandle();
    $CURL->setProxy('proxytest.resurs.it:80', CURLPROXY_HTTP);
    $this->TEST->ECOM->setCurlHandle($CURL);

    try {
        $request = $CURL->doGet('http://proxytest.resurs.it/ip.php');
    } catch (\Exception $e) {
        static::markTestSkipped(sprintf('Proxy test skipped (%d): %s', $e->getCode(), $e->getMessage()));
        return;
    }

    if ($this->isProperIp($request['body'])) {
        $_SERVER['REMOTE_ADDR'] = $this->getProperIp($request['body']);
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $this->TEST->ECOM->setCustomer('8305147715', "0808080808", "0707070707", "test@test.com", "NATURAL");
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $this->TEST->ECOM->addOrderLine("ProxyArtRequest", "My Proxified Product", 800, 25);
        $iframeRequest = $this->TEST->ECOM->createPayment($this->getMethodId());

        static::assertTrue(preg_match('/iframe src/i', $iframeRequest) ? true : false);
    } else {
        static::markTestSkipped('Could not complete proxy test');
    }
}