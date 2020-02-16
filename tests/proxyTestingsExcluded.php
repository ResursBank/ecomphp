<?php

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
	require_once(__DIR__ . '/../vendor/autoload.php');
}
if (file_exists(__DIR__ . "/../tornelib.php")) {
	// Work with TorneLIBv5
	/** @noinspection PhpIncludeInspection */
	require_once(__DIR__ . '/../tornelib.php');
}

use PHPUnit\Framework\TestCase;

class proxyTest extends TestCase
{
	// Extended unique tests excluded from the suite.
	/**
	 * @param $addr
	 * @return string|null
	 */
	/*private function getProperIp($addr)
	{
		$not = ['127.0.0.1'];
		if (filter_var(trim($addr), FILTER_VALIDATE_IP) && !in_array(trim($addr), $not)) {
			return trim($addr);
		}
		return null;
	}*/

	/**
	 * @test
	 * @testdox This test is not creating a full order, it just gets the iframe as we need manual interactions by
	 *     customer included..
	 * @throws \Exception
	 */
	/*public function proxyByBookRcoHalfway()
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
	}*/

	/**
	 * @test
	 * @throws \Exception
	 */
	/*public function proxyByHandle()
	{
		$CURL = $this->TEST->ECOM->getCurlHandle();
		$CURL->setProxy('proxytest.resurs.it:80', CURLPROXY_HTTP);
		$CURL->setChain();
		try {
			$request = $CURL->doGet('http://proxytest.resurs.it/ip.php');
			static::assertTrue($this->isProperIp($request->getBody()));
		} catch (\Exception $e) {
			static::markTestSkipped(sprintf('Proxy test skipped (%d): %s', $e->getCode(), $e->getMessage()));
			return;
		}
	}*/

	/**
	 * @test
	 * @testdox Test proxy function. Very internal test though. Ignore if you're on the wrong network.
	 * @throws \Exception
	 */
	/*public function proxyByPaymentMethods()
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
			static::assertTrue(count($this->TEST->ECOM->getPaymentMethods()) > 0);
		} else {
			static::markTestSkipped('Could not complete proxy test');
		}
	}*/

	/**
	 * @test
	 * @testdox Book payment through proxy. Simplified flow.
	 * @throws \Exception
	 */
	/*public function proxyByBookSimplified()
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
			$this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);
			$this->TEST->ECOM->setCustomer('8305147715', "0808080808", "0707070707", "test@test.com", "NATURAL");
			$this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
			$this->TEST->ECOM->addOrderLine("ProxyArtRequest", "My Proxified Product", 800, 25);
			$payment = $this->TEST->ECOM->createPayment($this->getMethodId());
			static::assertTrue(strlen($payment->paymentId) > 5);
		} else {
			static::markTestSkipped('Could not complete proxy test');
		}
	}*/
}
