<?php

/**
 * Resurs Bank EComPHP - Test suite.
 * Some of the tests in this suite is being made to check that the "share data between tests" works properly.
 * As setUp() resets tests to basic each time it runs, we can not share for example payments that we can make more
 * then one test on, with different kind of exepectations.
 * @package EcomPHPTest
 * @author Resurs Bank AB, Tomas Tornevall <tomas.tornevall@resurs.se>
 * @version 0.2.0
 * @link https://test.resurs.com/docs/x/KYM0 Get started - PHP Section
 * @link https://resursbankplugins.atlassian.net/browse/ECOMPHP-214 Rebuilding!
 * @license Apache 2.0
 */

if (file_exists(__DIR__ . "/../vendor/autoload.php")) {
    require_once(__DIR__ . '/../vendor/autoload.php');
} else {
    require_once(__DIR__ . '/../source/classes/rbapiloader.php');
}

// Usages for v1.0
use PHPUnit\Framework\TestCase;
use Resursbank\MODULE_CURL;
use Resursbank\MODULE_SOAP;
use Resursbank\RBEcomPHP\MODULE_IO;

// Global test configuration section starts here
require_once(__DIR__ . "/classes/ResursBankTestClass.php");
require_once(__DIR__ . "/hooks.php");

// Set up local user agent for identification with webservices
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = "EComPHP/Test-InternalClient";
}
ini_set('memory_limit', -1);
if (file_exists("/etc/ecomphp.json")) {
    $ecomExt = @json_decode(@file_get_contents("/etc/ecomphp.json"));
    if (isset($ecomExt->skip)) {
        define('SKIP_TEST', $ecomExt->skip);
    }
}

/**
 * Class resursBankTest
 * @package Resursbank\RBEcomPHP
 */
class resursBankTest extends TestCase
{
    /**
     * @var ResursBank $API EComPHP
     */
    protected $API;

    /** @var \RESURS_WEBDRIVER */
    protected $WEBDRIVER;

    /** @var RESURS_TEST_BRIDGE $TEST Used for standard tests and simpler flow setup */
    protected $TEST;

    /** @noinspection PhpUnusedPrivateFieldInspection */
    /** @var string Username to web services */
    private $username = "ecomphpPipelineTest";
    /** @noinspection PhpUnusedPrivateFieldInspection */
    /** @var string Password to web services */
    private $password = "4Em4r5ZQ98x3891D6C19L96TQ72HsisD";

    private $flowHappyCustomer = "8305147715";
    private $flowHappyCustomerName = "Vincent Williamsson Alexandersson";
    /** @noinspection PhpUnusedPrivateFieldInspection */
    /** @var string Landing page for callbacks */
    private $callbackUrl = "https://test.resurs.com/signdummy/index.php?isCallback=1";

    /** @var string Landing page for signings */
    private $signUrl = "https://test.resurs.com/signdummy/index.php?isSigningUrl=1";

    /**
     * Exact match of selenium driver we're running with tests.
     *
     * Add to composer: "facebook/webdriver": "dev-master"
     *
     * @var string
     */
    //protected $webdriverFile = 'selenium.jar';

    /**
     * @throws \Exception
     */
    public function setUp()
    {
        $this->API = new ResursBank();
        $this->API->setDebug(true);
        $this->TEST = new RESURS_TEST_BRIDGE($this->username, $this->password);
        $this->WEBDRIVER = new \RESURS_WEBDRIVER();
        if (!empty($this->webdriverFile) && file_exists(__DIR__ . '/' . $this->webdriverFile)) {
            $this->WEBDRIVER->init();
        }
    }

    /**
     * @test
     */
    public function clearStorage()
    {
        @unlink(__DIR__ . "/storage/shared.serialize");
        static::assertTrue(!file_exists(__DIR__ . '/storage/shared.serialize'));
    }

    /**
     * @test
     * @testdox Tests API credentials and getPaymentMethods. Expected result: Approved connection with a specific number of payment methods
     * @throws \Exception
     */
    public function apiPaymentMethodsWithCredentials()
    {
        static::assertTrue(count($this->TEST->getCredentialControl()) > 0);
    }

    /**
     * @test
     * @testdox EComPHP throws \Exceptions on credential failures
     * @throws \Exception
     */
    public function apiPaymentMethodsWithWrongCredentials()
    {
        try {
            $this->TEST->getCredentialControl(false);
        } catch (\Exception $e) {
            if ($e->getCode() >= 500) {
                static::fail("Got internal server error (500) from Resurs Bank. This test usually returns 401 for access denied, but something went wrong this time.");
                return;
            }
            static::assertTrue(($e->getCode() == 401));
        }
    }

    /**
     * @test
     * @testdox Testing this suite's capabilities to share data between tests
     */
    public function shareDataOut()
    {
        $this->TEST->share("outShare", 1);
        $keys = $this->TEST->share("thisKey", "thatValue");
        static::assertTrue(count($keys) > 0 ? true : false);
    }

    /**
     * @test
     * @testdox Testing this suite's capabilites to retreive shared data
     */
    public function shareDataIn()
    {
        $keys = $this->TEST->share("thisKey");
        static::assertTrue(count($keys) > 0 ? true : false);
    }

    /**
     * @test
     * @testdox Testing this suite's capability to remove keys from shared data (necessary to reset things)
     */
    public function shareDataRemove()
    {
        if ($this->TEST->share("outShare")) {
            $this->TEST->unshare("outShare");
            $keys = $this->TEST->share();
            static::assertTrue(is_array($keys));

        } else {
            static::markTestSkipped("Test has been started without shareDataOut");
        }
    }

    /**
     * @test
     * @testdox getCurlHandle (using getAddress)
     * @throws \Exception
     */
    public function getAddressCurlHandle()
    {
        if (!class_exists('\SimpleXMLElement')) {
            static::markTestSkipped("SimpleXMLElement missing");
        }

        $this->TEST->ECOM->getAddress($this->flowHappyCustomer);
        /** @var MODULE_CURL $lastCurlHandle */

        if (defined('TORNELIB_NETCURL_RELEASE') && version_compare(TORNELIB_NETCURL_RELEASE, '6.0.20', '<')) {
            // In versions prior to 6.0.20, you need to first extract the SOAP body from simpleSoap itself (via getLibResponse).
            $lastCurlHandle = $this->TEST->ECOM->getCurlHandle(true);
            /** @var MODULE_SOAP $lastCurlHandle */
            $soapLibResponse = $lastCurlHandle->getSoapResponse();
            $selfParser = new MODULE_IO();
            $byIo = $selfParser->getFromXml($soapLibResponse['body'], true);
            /** @noinspection PhpUndefinedFieldInspection */
            static::assertTrue((
                $byIo->fullName == $this->flowHappyCustomerName ? true : false) &&
                ($soapLibResponse['parsed']->fullName == $this->flowHappyCustomerName ? true : false
                ));

            return;
        }

        // The XML parser in the IO MODULE should give the same response as the direct curl handle
        // From NetCURL 6.0.20 and the IO library, this could be extracted directly from the curl handle
        $selfParser = new MODULE_IO();
        // Get the curl handle without bulk request
        $lastCurlHandle = $this->TEST->ECOM->getCurlHandle();

        $byIo = $selfParser->getFromXml($lastCurlHandle->getBody(), true);
        $byHandle = $lastCurlHandle->getParsed();

        /** @noinspection PhpUndefinedFieldInspection */
        static::assertTrue(
            $byIo->fullName == $this->flowHappyCustomerName &&
            $byHandle->fullName == $this->flowHappyCustomerName
        );
    }

    /**
     * @test
     * @testdox Direct test - Test adding orderlines via the library and extract correct data
     */
    public function addOrderLine()
    {
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $orderLines = $this->TEST->ECOM->getOrderLines();
        static::assertTrue(count($orderLines) > 0 && $orderLines[0]['artNo'] == "Product-1337");
    }

    /**
     * @test
     */
    public function preMetaData()
    {
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue');

        $meta = $this->TEST->ECOM->getMetaData(null, true);
        $metaKey = $this->TEST->ECOM->getMetaData(null, true, true);

        static::assertTrue(count($meta['payloadMetaData']) > 0 && count($metaKey['payloadMetaData']));
    }

    /**
     * @test Avoid duplicate metadata in pre set method.
     * @throws \Exception
     */
    public function setMetaData()
    {
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        try {
            $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue');
            $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue');
            $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue');
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() === 400);
        }
    }

    /**
     * @test Avoid duplicate metadata in pre set method.
     * @throws \Exception
     */
    public function setMetaDataDuplicate()
    {
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue', false);
        $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue', false);
        $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue', false);
        $plm = $this->TEST->ECOM->getMetaData(null, true);
        $allMetas = $plm['payloadMetaData'];

        static::assertTrue(count($allMetas) === 3);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function findPaymentByGovd()
    {
        $payments = $this->TEST->ECOM->findPayments(array('governmentId' => '8305147715'));
        static::assertTrue(is_array($payments) && count($payments));
    }

    /**
     * @test
     * @param bool $noAssert
     * @return array
     * @throws \Exception
     */
    public function generateSimpleSimplifiedInvoiceOrder($noAssert = false)
    {
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer("198305147715", "0808080808", "0707070707", "test@test.com", "NATURAL");
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $this->TEST->ECOM->setMetaData('metaKeyTestTime', time());
        $this->TEST->ECOM->setMetaData('metaKeyTestMicroTime', microtime(true));
        $response = $this->TEST->ECOM->createPayment($this->getMethodId());
        if (!$noAssert) {
            /** @noinspection PhpUndefinedFieldInspection */
            static::assertTrue($response->bookPaymentStatus == 'BOOKED' || $response->bookPaymentStatus == 'SIGNING');
        }

        return $response;
    }

    /**
     * Only run this when emulating colliding orders in the woocommerce plugin.
     *
     * @param bool $noAssert
     * @return array
     * @throws \Exception
     */
    public function wooCommerceCollider($noAssert = false)
    {
        $incremental = 1430;
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer("198305147715", "0808080808", "0707070707", "test@test.com", "NATURAL");
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $this->TEST->ECOM->setMetaData('metaKeyTestTime', time());
        $this->TEST->ECOM->setMetaData('metaKeyTestMicroTime', microtime(true));
        $this->TEST->ECOM->setPreferredId($incremental);
        $response = $this->TEST->ECOM->createPayment($this->getMethodId());
        if (!$noAssert) {
            /** @noinspection PhpUndefinedFieldInspection */
            static::assertTrue($response->bookPaymentStatus == 'BOOKED' || $response->bookPaymentStatus == 'SIGNING');
        }

        return $response;
    }

    /**
     * @test Using PSP during simplified flow (with government id / SSN)
     * @return array
     * @throws \Exception
     */
    public function generateSimpleSimplifiedPspResponse()
    {
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer("198305147715", "0808080808", "0707070707", "test@test.com", "NATURAL");
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $response = $this->TEST->ECOM->createPayment($this->getMethodId('PAYMENT_PROVIDER'));
        // In a perfect world, a booked payment for PSP should generate SIGNING as the payment occurs
        // externally.
        static::assertTrue($response->bookPaymentStatus == 'SIGNING');
        return $response;
    }

    /**
     * @test Using PSP during simplified flow (without government id / SSN)
     * @return array
     * @throws \Exception
     */
    public function generateSimpleSimplifiedPspWithouGovernmentIdCompatibility()
    {
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer(null, "0808080808", "0707070707", "test@test.com", "NATURAL");
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $response = $this->TEST->ECOM->createPayment($this->getMethodId('PAYMENT_PROVIDER'));
        static::assertTrue($response->bookPaymentStatus == 'SIGNING');
        return $response;
    }

    /**
     * @return null
     * @throws \Exception
     */
    private function getHappyCustomerData()
    {
        $lastHappyCustomer = $this->TEST->share('happyCustomer');
        if (empty($lastHappyCustomer)) {
            $this->getAddress(true);
            $lastHappyCustomer = $this->TEST->share('happyCustomer');
        }
        if (isset($lastHappyCustomer[0])) {
            return $lastHappyCustomer[0];
        }
        return null;
    }

    /**
     * @test
     * @testdox Direct test - Basic getAddressTest with caching
     * @param bool $noAssert
     * @return array|mixed|null
     * @throws \Exception
     */
    public function getAddress($noAssert = false)
    {
        $happyCustomer = $this->TEST->ECOM->getAddress($this->flowHappyCustomer);
        $this->TEST->share('happyCustomer', $happyCustomer, false);
        if (!$noAssert) {
            // Call to undefined function mb_strpos() with assertContains in PHP 7.3
            static::assertTrue(preg_match('/' . $this->flowHappyCustomerName . '/i',
                $happyCustomer->fullName) ? true : false);
        }

        return $happyCustomer;
    }

    /**
     * Get the payment method ID from the internal getMethod()
     *
     * @param string $specificType
     * @return mixed
     * @throws \Exception
     */
    public function getMethodId($specificType = 'INVOICE')
    {
        $specificMethod = $this->getMethod($specificType);
        if (isset($specificMethod->id)) {
            return $specificMethod->id;
        }
        return null;
    }

    /**
     * Get a method that suites our needs of TYPE or SPECIFIC TYPE (not method ID), with the help from getPaymentMethods
     *
     * @param string $specificType
     * @param string $customerType
     * @return mixed
     * @throws \Exception
     */
    public function getMethod($specificType = 'INVOICE', $customerType = 'NATURAL')
    {
        $return = null;
        $this->getPaymentMethods(false);
        $prePop = $this->TEST->share('paymentMethods');
        $methodGroup = array_pop($prePop);
        foreach ($methodGroup as $curMethod) {
            if (($curMethod->specificType === $specificType || $curMethod->type === $specificType) && in_array($customerType,
                    (array)$curMethod->customerType)) {
                $this->TEST->share('METHOD_' . $specificType);
                $return = $curMethod;
                break;
            }
        }

        return $return;
    }

    /**
     * @test
     * @testdox Test if getPaymentMethods work and in the same time cache it for future use
     * @param bool $noAssert
     * @throws \Exception
     */
    public function getPaymentMethods($noAssert = false)
    {
        $methodList = $this->TEST->share('paymentMethods');
        if (is_array($methodList) && !count($methodList) || !is_array($methodList)) {
            $this->TEST->ECOM->setSimplifiedPsp(true);
            $paymentMethods = $this->TEST->ECOM->getPaymentMethods(array(), true);
            foreach ($paymentMethods as $method) {
                $this->TEST->share('METHOD_' . $method->id, $method, false);
            }
            $this->TEST->share('paymentMethods', $paymentMethods, false);
        } else {
            $paymentMethods = is_array($methodList) ? array_pop($methodList) : $methodList;
        }
        if (!$noAssert) {
            static::assertGreaterThan(1, $paymentMethods);
        }
    }

    /**
     * @test Direct test - Extract orderdata from library
     * @testdox
     * @throws \Exception
     */
    public function getOrderData()
    {
        $this->TEST->ECOM->setBillingByGetAddress($this->flowHappyCustomer);
        $this->TEST->ECOM->addOrderLine("RDL-1337", "One simple orderline", 800, 25);
        $orderData = $this->TEST->ECOM->getOrderData();
        static::assertTrue($orderData['totalAmount'] == "1000");
    }

    /**
     * @test
     * @testdox Make sure the current version of ECom is not 1.0.0 and getCurrentRelease() says something
     * @throws \Exception
     */
    public function getCurrentReleaseTests()
    {
        $currentReleaseShouldNotBeEmpty = $this->TEST->ECOM->getCurrentRelease();  // php 5.5
        static::assertFalse($this->TEST->ECOM->getIsCurrent("1.0.0") && !empty($currentReleaseShouldNotBeEmpty));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function getAnnuityMethods()
    {
        $annuityObjectList = $this->TEST->ECOM->getPaymentMethodsByAnnuity();
        $annuityIdList = $this->TEST->ECOM->getPaymentMethodsByAnnuity(true);
        static::assertTrue(count($annuityIdList) >= 1 && count($annuityObjectList) >= 1);
    }

    /**
     * @todo Countable issue linked to an IO event
     * @throws \Exception
     */
    public function findPaymentsXmlBody()
    {
        $paymentScanList = $this->TEST->ECOM->findPayments(array('statusSet' => array('IS_DEBITED')), 1, 10, array(
            'ascending' => false,
            'sortColumns' => array('FINALIZED_TIME', 'MODIFIED_TIME', 'BOOKED_TIME'),
        ));

        $handle = $this->TEST->ECOM->getCurlHandle();
        $requestBody = $handle->getRequestBody();
        static::assertTrue(strlen($requestBody) > 100 && count($paymentScanList));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function updateStrangePaymentReference()
    {
        $showFrames = false;

        // Using NO_RESET_PAYLOAD in the test suite may lead to unexpected faults, so
        // have it disabled, unless you need something very specific out of this test.

        //$this->TEST->ECOM->setFlag('NO_RESET_PAYLOAD');
        $this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);

        // First update.
        $this->TEST->ECOM->addOrderLine("Product-1337", "", 800, 25);
        $id = $this->TEST->ECOM->getPreferredPaymentId();
        $fIframe = $this->TEST->ECOM->createPayment($id);
        $renameToFirst = microtime(true);
        $this->TEST->ECOM->updatePaymentReference($id, $renameToFirst);

        // Second update.
        $this->TEST->ECOM->addOrderLine("Product-1337-OverWriteMe", "", 1200, 25);
        $sIframe = $this->TEST->ECOM->createPayment($id);
        // Update this reference to the above payment id.
        $this->TEST->ECOM->updatePaymentReference($id, $renameToFirst);
        //$this->TEST->ECOM->deleteFlag('NO_RESET_PAYLOAD');

        if ($showFrames) {
            echo $fIframe . "\n";
            echo $sIframe . "\n";
        }

        // Making above steps will give two different iframe-sessions that can be updated
        // to the same final order id. However, there can be only one winner of the final payment session.
        // To figure out which, there are different articles and final sums in the order.
        // For the default ecom behaviour, the payload will reset after each "createPayment"
        // so there won't be any refills.
        static::assertTrue(!empty($renameToFirst) && $fIframe !== $sIframe);
    }

    /**
     * @test
     * @throws \Exception
     * @throws \Exception
     */
    public function hookExperiment1()
    {
        if (!function_exists('ecom_event_register')) {
            static::markTestIncomplete('ecomhooks does not exist');

            return;
        }
        ecom_event_register('update_store_id', 'inject_test_storeid');
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer("198305147715", "0808080808", "0707070707", "test@test.com", "NATURAL");
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        ecom_event_unregister('update_store_id');
        $myPayLoad = $this->TEST->ECOM->getPayload();
        static::assertTrue(isset($myPayLoad['storeId']) && $myPayLoad['storeId'] >= 0);
    }

    /**
     * @test
     * @throws \Exception
     * @throws \Exception
     */
    public function hookExperiment2()
    {
        if (!function_exists('ecom_event_register')) {
            static::markTestIncomplete('ecomhooks does not exist');

            return;
        }
        ecom_event_register('update_payload', 'ecom_inject_payload');
        $customerData = $this->getHappyCustomerData();
        $errorCode = 0;
        $this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer(null, "0808080808", "0707070707", "test@test.com", "NATURAL");
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        try {
            $this->TEST->ECOM->createPayment($this->getMethodId());
        } catch (\Exception $e) {
            $errorCode = $e->getCode();
        }
        $myPayLoad = $this->TEST->ECOM->getPayload();
        ecom_event_unregister('update_payload');
        static::assertTrue(
            isset($myPayLoad['add_a_problem_into_payload']) &&
            !isset($myPayLoad['signing']) &&
            (int)$errorCode > 0
        );
    }

    /**
     * @test
     *
     * How to use this in a store environment like RCO:
     *   - During interceptor mode, store the getOrderLineHash in a _SESSION variable.
     *   - When interceptor handle is sent over to backend in store, run getOrderLineHash again.
     *   - Match the old stored _SESSION variable with the new getOrderLineHash-response.
     *   - If they mismatch, the cart has been updated while still in the checkout.
     *
     * @throws \Exception
     */
    public function hashedSpecLines()
    {
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline, red", 800, 25);
        $this->TEST->ECOM->addOrderLine("Product-1337", "Second simple orderline, blue", 900, 25);
        $this->TEST->ECOM->addOrderLine("Product-1338", "Third simple orderline", 1000, 25, 'st', 'ORDER_LINE', 3);
        $this->TEST->ECOM->addOrderLine("Product-1339", "Our fee", 45, 25, 'st', 'FEE', 3);
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer(null, "0808080808", "0707070707", "test@test.com", "NATURAL");
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $orderLineHash = $this->TEST->ECOM->getOrderLineHash();
        $this->TEST->ECOM->addOrderLine(
            "Hacked Product",
            "Article added after first orderline hash",
            1000,
            25,
            'st',
            'ORDER_LINE',
            3
        );
        $newOrderLineHash = $this->TEST->ECOM->getOrderLineHash();
        static::assertTrue($orderLineHash !== $newOrderLineHash);
    }

    /**
     * @test
     * @testdox Expect arrays regardless of response
     * @throws \Exception
     */
    public function getEmptyCallbacksList()
    {
        /**
         * Standard request returns:
         *   array(
         *      [index-1] => stdObject
         *      [index-2] => stdObject
         *   )
         *   asArrayRequest returns:
         *   array(
         *      [keyCallbackName1] => URL
         *      [keyCallbackName2] => URL
         *   )
         * Standard request when empty should return array()
         */

        try {
            $this->TEST->ECOM->unregisterEventCallback(255, true);
        } catch (\Exception $e) {
        }
        $callbacks = $this->TEST->ECOM->getCallBacksByRest(true);
        static::assertTrue(is_array($callbacks) && !count($callbacks) ? true : false);
    }

    /**
     * @test
     */
    public function bitMaskControl()
    {
        static::assertTrue(
            (255 & RESURS_CALLBACK_TYPES::FINALIZATION) ? true : false &&
            (8 & RESURS_CALLBACK_TYPES::FINALIZATION) ? true : false &&
            (24 & RESURS_CALLBACK_TYPES::TEST) ? true : false &&
            (12 & RESURS_CALLBACK_TYPES::FINALIZATION && RESURS_CALLBACK_TYPES::AUTOMATIC_FRAUD_CONTROL) ? true : false &&
            (56 & RESURS_CALLBACK_TYPES::FINALIZATION && RESURS_CALLBACK_TYPES::AUTOMATIC_FRAUD_CONTROL) ? true : false &&
                (RESURS_CALLBACK_TYPES::FINALIZATION | RESURS_CALLBACK_TYPES::AUTOMATIC_FRAUD_CONTROL | RESURS_CALLBACK_TYPES::TEST) === 28
        );
    }

    /**
     * @test
     * @testdox The normal way
     * @throws \Exception
     */
    public function getEmptyCallbacksListSecond()
    {
        try {
            $this->TEST->ECOM->unregisterEventCallback(255, true);
        } catch (\Exception $e) {
        }
        $callbacks = $this->TEST->ECOM->getCallBacksByRest();
        static::assertTrue(is_array($callbacks) && !count($callbacks) ? true : false);
    }

    /**
     * @return null
     * @throws \Exception
     */
    private function getPaymentMethodsData()
    {
        $paymentMethods = $this->TEST->share('paymentMethods');
        if (empty($paymentMethods)) {
            $this->getPaymentMethods();
            $paymentMethods = $this->TEST->share('paymentMethods');
        }
        if (isset($paymentMethods[0])) {
            return $paymentMethods[0];
        }
        return null;
    }

    /**
     * @test
     */
    public function getPaymentWrong()
    {
        try {
            $this->TEST->ECOM->getPayment("FAIL_HERE");
        } catch (\Exception $e) {
            $code = (int)$e->getCode();
            // Code 3 = REST, Code 8 = SOAP (180914)
            static::assertTrue($code === 8 || $code === 404);
        }
    }

    /**
     * @test
     */
    public function getPaymentWrongRest()
    {
        try {
            $this->TEST->ECOM->setFlag('GET_PAYMENT_BY_REST');
            $this->TEST->ECOM->getPayment('FAIL_HERE');
        } catch (\Exception $e) {
            $code = (int)$e->getCode();
            // Code 3 = REST, Code 8 = SOAP (180914)
            static::assertTrue($code === 3 || $code === 404);
        }
        $this->TEST->ECOM->deleteFlag('GET_PAYMENT_BY_REST');
    }

    /**
     * @test
     */
    public function getPaymentUnexistentSoap()
    {
        try {
            $this->TEST->ECOM->getPayment('FAIL_HERE');
        } catch (\Exception $e) {
            // This should NEVER throw anything else than 3 (REST) or 8 (SOAP)
            $code = $e->getCode();
            static::assertTrue($code === 8);
        }
    }

    /**
     * For the future edition of EC where __get is a helper.
     *
     * @test
     */
    /*public function newGet()
    {
        try {
            $failable = $this->TEST->ECOM->nonExistent && $this->TEST->ECOM['nonExistent'] ? true : false;
        } catch (\Exception $e) {
            $failable = true;
        }

        try {
            $protectedVariable = $this->TEST->ECOM->version;
        } catch (\Exception $e) {
            $protectedVariable = true;
        }

        $reachableVariable = $this->TEST->ECOM->current_environment;
        $unsetButReachableVariable = $this->TEST->ECOM->test;

        static::assertTrue($failable && $protectedVariable && $reachableVariable === 1 && $unsetButReachableVariable);
    }*/

    /**
     * Special test case where we just create an iframe and then sending updatePaymentReferences via API to see
     * if any errors are traceable
     */
    public function ordersWithoutDescription()
    {
        ecom_event_register('ecom_article_data', 'destroy_ecom_article_data');
        $this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $this->TEST->ECOM->addOrderLine("Product-1337", "", 800, 25);
        $hasErrors = false;
        try {
            $paymentId = "nodesc_" . sha1(microtime(true));
            //$newPaymentId = 'PROPER_' . $paymentId;
            $this->TEST->ECOM->createPayment($paymentId);
        } catch (\Exception $e) {
            $hasErrors = true;
        }
        ecom_event_unregister('ecom_article_data');

        // Current expectation: Removing description totally from an order still renders
        // the iframe, even if the order won't be handlable.
        static::assertFalse($hasErrors);
    }


    /**
     * @test
     * @throws \Exception
     */
    public function validateCredentials()
    {
        $isNotValid = $this->TEST->ECOM->validateCredentials(RESURS_ENVIRONMENTS::TEST, 'fail', 'fail');
        $isValid = $this->TEST->ECOM->validateCredentials(RESURS_ENVIRONMENTS::TEST, $this->username, $this->password);
        $onInit = new ResursBank();
        // Using this function on setAuthentication should immediately throw exception if not valid.
        $onInitOk = $onInit->setAuthentication($this->username, $this->password, true);

        $initAndValidate = new ResursBank($this->username, $this->password);
        $justValidated = $initAndValidate->validateCredentials();
        static::assertTrue($isValid && !$isNotValid && $onInitOk && $justValidated);
    }

    /**
     * @test
     */
    public function stringExceptions()
    {
        try {
            throw new \ResursException('Fail', 0, null, 'TEST_ERROR_CODE_AS_STRING', __FUNCTION__);
        } catch (\Exception $e) {
            $firstCode = $e->getCode();
        }
        try {
            throw new \ResursException('Fail', 0, null, 'TEST_ERROR_CODE_AS_STRING_WITHOUT_CONSTANT', __FUNCTION__);
        } catch (\Exception $e) {
            $secondCode = $e->getCode();
        }

        static::assertTrue($firstCode === 1007 && $secondCode === 'TEST_ERROR_CODE_AS_STRING_WITHOUT_CONSTANT');
    }

    /**
     * @test
     */
    public function failUpdatePaymentReference()
    {
        try {
            $this->TEST->ECOM->updatePaymentReference('not_this', 'not_that');
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() === 700);
        }
    }


    /**
     * @test
     * @testdox Clean up special test data from share file
     */
    public function finalTest()
    {
        static::assertTrue($this->TEST->unshare("thisKey"));
    }
}
