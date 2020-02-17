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
    require_once('../source/classes/rbapiloader.php');
}
if (file_exists(__DIR__ . '/webdriver.php')) {
    require_once(__DIR__ . '/webdriver.php');
}

// Usages for v1.1
use PHPUnit\Framework\TestCase;
use Resursbank\RBEcomPHP\MODULE_CURL;
use Resursbank\RBEcomPHP\MODULE_IO;
use Resursbank\RBEcomPHP\RESURS_TEST_BRIDGE;
use Resursbank\RBEcomPHP\ResursBank;
use Resursbank\RBEcomPHP\RESURS_CALLBACK_TYPES;
use Resursbank\RBEcomPHP\RESURS_FLOW_TYPES;
use Resursbank\RBEcomPHP\RESURS_ENVIRONMENTS;

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
 *
 * @package Resursbank\RBEcomPHP
 */
class resursBankTest extends TestCase {
    /**
     * @var ResursBank $API EComPHP
     */
    protected $API;

    /** @var \RESURS_WEBDRIVER */
    protected $WEBDRIVER;

    /** @var RESURS_TEST_BRIDGE $TEST Used for standard tests and simpler flow setup */
    protected $TEST;

    /** @var string Username to web services. */
    private $username = "ecomphpPipelineTest";
    /** @var string Password to web services. */
    private $password = "4Em4r5ZQ98x3891D6C19L96TQ72HsisD";

    /** @var string Username to internal RCO. */
    private $usernameNG = "checkoutwebse";
    /** @var string Password to internal RCO. */
    private $passwordNG = "gO9UaWH38D";

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
    public function __setUp() {
        $this->API = new ResursBank();
        $this->API->setDebug(true);
        $this->TEST      = new RESURS_TEST_BRIDGE($this->username, $this->password);
        $this->WEBDRIVER = new \RESURS_WEBDRIVER();
        if ( ! empty($this->webdriverFile) && file_exists(__DIR__ . '/' . $this->webdriverFile)) {
            $this->WEBDRIVER->init();
        }
    }

    /**
     * @test
     */
    public function clearStorage() {
        $this->__setUp();
        @unlink(__DIR__ . "/storage/shared.serialize");
        static::assertTrue(! file_exists(__DIR__ . '/storage/shared.serialize'));
    }

    /**
     * @test
     * @testdox Tests API credentials and getPaymentMethods.
     * @throws \Exception
     */
    public function apiPaymentMethodsWithCredentials() {
        $this->__setUp();
        static::assertTrue(count($this->TEST->getCredentialControl()) > 0);
    }

    /**
     * @test
     * @testdox EComPHP throws \Exceptions on credential failures
     * @throws \Exception
     */
    public function apiPaymentMethodsWithWrongCredentials() {
        $this->__setUp();
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
    public function shareDataOut() {
        $this->__setUp();
        $this->TEST->share("outShare", 1);
        $keys = $this->TEST->share("thisKey", "thatValue");
        static::assertTrue(count($keys) > 0 ? true : false);
    }

    /**
     * @test
     * @testdox Testing this suite's capabilites to retreive shared data
     */
    public function shareDataIn() {
        $this->__setUp();
        $keys = $this->TEST->share("thisKey");
        static::assertTrue(count($keys) > 0 ? true : false);
    }

    /**
     * @test
     * @testdox Testing this suite's capability to remove keys from shared data (necessary to reset things)
     */
    public function shareDataRemove() {
        $this->__setUp();
        if ($this->TEST->share("outShare")) {
            $this->TEST->unshare("outShare");
            $keys = $this->TEST->share();
            static::assertTrue(is_array($keys));

        } else {
            static::markTestSkipped("Test has been started without shareDataOut.");
        }
    }

    /**
     * @test
     * @testdox getCurlHandle (using getAddress)
     * @throws \Exception
     */
    public function getAddressCurlHandle() {
        $this->__setUp();
        if ( ! class_exists('\SimpleXMLElement')) {
            static::markTestSkipped("SimpleXMLElement missing");
        }

        $this->TEST->ECOM->getAddress($this->flowHappyCustomer);
        /** @var MODULE_CURL $lastCurlHandle */

        if (defined('TORNELIB_NETCURL_RELEASE') && version_compare(TORNELIB_NETCURL_RELEASE, '6.0.20', '<')) {
            // In versions prior to 6.0.20, you need to first extract the SOAP body from simpleSoap itself (via getLibResponse).
            $lastCurlHandle = $this->TEST->ECOM->getCurlHandle(true);
            /** @var MODULE_SOAP $lastCurlHandle */
            $soapLibResponse = $lastCurlHandle->getSoapResponse();
            $selfParser      = new MODULE_IO();
            $byIo            = $selfParser->getFromXml($soapLibResponse['body'], true);
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

        $byIo     = $selfParser->getFromXml($lastCurlHandle->getBody(), true);
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
    public function addOrderLine() {
        $this->__setUp();
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $orderLines = $this->TEST->ECOM->getOrderLines();
        static::assertTrue(count($orderLines) > 0 && $orderLines[0]['artNo'] == "Product-1337");
    }

    /**
     * @test
     */
    public function preMetaData() {
        $this->__setUp();
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue');

        $meta    = $this->TEST->ECOM->getMetaData(null, true);
        $metaKey = $this->TEST->ECOM->getMetaData(null, true, true);

        static::assertTrue(count($meta['payloadMetaData']) > 0 && count($metaKey['payloadMetaData']));
    }

    /**
     * @test Avoid duplicate metadata in pre set method.
     * @throws \Exception
     */
    public function setMetaData() {
        $this->__setUp();
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
    public function setMetaDataDuplicate() {
        $this->__setUp();
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue', false);
        $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue', false);
        $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue', false);
        $plm      = $this->TEST->ECOM->getMetaData(null, true);
        $allMetas = $plm['payloadMetaData'];

        static::assertTrue(count($allMetas) === 3);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function findPaymentByGovId() {
        $this->__setUp();
        $payments = $this->TEST->ECOM->findPayments(['governmentId' => '8305147715']);
        static::assertTrue(is_array($payments) && count($payments));
    }

    /**
     * @test
     *
     * @param bool $noAssert
     * @param string $govId
     *
     * @return array
     * @throws \Exception
     */
    public function generateSimpleSimplifiedInvoiceOrder($noAssert = false, $govId = '198305147715') {
        $this->__setUp();
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer($govId, "0808080808", "0707070707", "test@test.com", "NATURAL");
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $this->TEST->ECOM->setMetaData('metaKeyTestTime', time());
        $this->TEST->ECOM->setMetaData('metaKeyTestMicroTime', microtime(true));
        $response = $this->TEST->ECOM->createPayment($this->getMethodId());
        if ( ! $noAssert) {
            /** @noinspection PhpUndefinedFieldInspection */
            static::assertTrue($response->bookPaymentStatus == 'BOOKED' || $response->bookPaymentStatus == 'SIGNING');
        }

        return $response;
    }

    public function getProductPrice($static = false) {
        if ( ! $static) {
            return rand(30, 90);
        }

        return 90;
    }

    /**
     * @test
     *
     * @param string $govId
     *
     * @return array
     * @throws \Exception
     */
    public function generateSimpleSimplifiedInvoiceQuantityOrder($govId = '198305147715', $staticProductPrice = false) {
        $this->__setUp();
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine(
            "PR01",
            "PR01",
            $this->getProductPrice($staticProductPrice),
            25,
            'st',
            'ORDER_LINE',
            100
        );
        $this->TEST->ECOM->addOrderLine(
            "PR02",
            "PR02",
            $this->getProductPrice($staticProductPrice),
            25,
            'st',
            'ORDER_LINE',
            100
        );
        $this->TEST->ECOM->addOrderLine(
            "PR03",
            "PR03",
            $this->getProductPrice($staticProductPrice),
            25,
            'st',
            'ORDER_LINE',
            100
        );
        $this->TEST->ECOM->addOrderLine(
            "PR04",
            "PR04",
            $this->getProductPrice($staticProductPrice),
            25,
            'st',
            'ORDER_LINE',
            100
        );
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer($govId, "0808080808", "0707070707", "test@test.com", "NATURAL");
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $this->TEST->ECOM->setMetaData('metaKeyTestTime', time());
        $this->TEST->ECOM->setMetaData('metaKeyTestMicroTime', microtime(true));
        $response = $this->TEST->ECOM->createPayment($this->getMethodId());

        return $response;
    }

    /**
     * @test Finalize frozen orders - ECom should prevent this before Resurs Bank to save performance.
     *
     * @throws \Exception
     */
    public function finalizeFrozen() {
        $this->__setUp();
        $payment = $this->generateSimpleSimplifiedInvoiceOrder(true, '198101010000');
        if (isset($payment->paymentId) && $payment->bookPaymentStatus === 'FROZEN') {
            // Verified frozen.
            try {
                $this->TEST->ECOM->finalizePayment($payment->paymentId);
            } catch (\Exception $e) {
                static::assertTrue(
                    $e->getCode() === \RESURS_EXCEPTIONS::ECOMMERCEERROR_NOT_ALLOWED_IN_CURRENT_STATE,
                    'Finalization properly prohibited by current state'
                );
            }
        }
    }

    /**
     * @test
     * @throws \Exception
     */
    public function getPaymentCached() {
        $this->__setUp();
        $apiWithoutCache = new ResursBank($this->username, $this->password, null, false, ['setApiCache' => false]);
        $hasCache        = $apiWithoutCache->getApiCache();
        $hasCacheDefault = $this->TEST->ECOM->getApiCache();
        $req             = [];

        $this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);

        // Guarantee two different payment ids in this test.
        $this->TEST->ECOM->setPreferredId($this->TEST->ECOM->getPreferredPaymentId(25, '', true, true));
        $firstPayment = $this->generateSimpleSimplifiedInvoiceOrder(true);
        $this->TEST->ECOM->setPreferredId($this->TEST->ECOM->getPreferredPaymentId(25, '', true, true));
        $secondPayment = $this->generateSimpleSimplifiedInvoiceOrder(true);
        if (isset($firstPayment->paymentId)) {
            $req[] = $this->TEST->ECOM->getPayment($firstPayment->paymentId);
            $req[] = $this->TEST->ECOM->getPayment($secondPayment->paymentId);

            $requestEnd   = 0;        // Should end when this reaches 3.
            $requestStart = time(); // When requests started.
            $timeTotal    = 0;

            // Loop until 4 sec or more.
            while ($requestEnd < 3) {
                $requestEnd = time() - $requestStart;

                $currentRequestStartMillis = microtime(true);
                $req[]                     = $this->TEST->ECOM->getPayment($firstPayment->paymentId);
                $req[]                     = $this->TEST->ECOM->getPayment($secondPayment->paymentId);
                $currentRequestStopMillis  = microtime(true);
                $currentRequestTimeSpped   = $currentRequestStopMillis - $currentRequestStartMillis;
                $timeTotal                 += $currentRequestTimeSpped;
            }
            $timeMed = $timeTotal / count($req);

            /*
             * Required test result:
             *   - The cache should be able to request AT LEAST 10 getPayment in a period of three seconds.
             *      Initial tests shows that we could make at least 179 requests. NOTE: Pipelines just counted 6 calls.
             *   - Each request should be able to respond under 1 seccond.
             *   - The first $hasCache was initially disabled via __construct and should be false.
             *   - The second hasCache is untouched and should be true.
             */

            // >= 5 for pipelines.
            // >= 10 for own tests.
            static::assertTrue(
                count($req) >= 5 ? true : false &&
                    floatval($timeMed) < 1 &&
                    ! $hasCache
                    && $hasCacheDefault
            );

        }
    }

    /**
     * Only run this when emulating colliding orders in the woocommerce plugin.
     *
     * @param bool $noAssert
     *
     * @return array
     * @throws \Exception
     */
    public function wooCommerceCollider($noAssert = false) {
        $this->__setUp();
        $incremental  = 1430;
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine("Product-1337", "One simple orderline", 800, 25);
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer("198305147715", "0808080808", "0707070707", "test@test.com", "NATURAL");
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $this->TEST->ECOM->setMetaData('metaKeyTestTime', time());
        $this->TEST->ECOM->setMetaData('metaKeyTestMicroTime', microtime(true));
        $this->TEST->ECOM->setPreferredId($incremental);
        $response = $this->TEST->ECOM->createPayment($this->getMethodId());
        if ( ! $noAssert) {
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
    public function generateSimpleSimplifiedPspResponse() {
        $this->__setUp();
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
    public function generateSimpleSimplifiedPspWithouGovernmentIdCompatibility() {
        $this->__setUp();
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
    private function getHappyCustomerData() {
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
     *
     * @param bool $noAssert
     *
     * @return array|mixed|null
     * @throws \Exception
     */
    public function getAddress($noAssert = false) {
        $this->__setUp();
        $happyCustomer = $this->TEST->ECOM->getAddress($this->flowHappyCustomer);
        $this->TEST->share('happyCustomer', $happyCustomer, false);
        if ( ! $noAssert) {
            // Call to undefined function mb_strpos() with assertContains in PHP 7.3
            static::assertTrue(
                preg_match('/' . $this->flowHappyCustomerName . '/i', $happyCustomer->fullName) ? true : false
            );
        }

        return $happyCustomer;
    }

    /**
     * Get the payment method ID from the internal getMethod()
     *
     * @param string $specificType
     *
     * @return mixed
     * @throws \Exception
     */
    public function getMethodId($specificType = 'INVOICE') {
        $this->__setUp();
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
     *
     * @return mixed
     * @throws \Exception
     */
    public function getMethod($specificType = 'INVOICE', $customerType = 'NATURAL') {
        $this->__setUp();
        $return = null;
        $this->getPaymentMethods(false);
        $prePop      = $this->TEST->share('paymentMethods');
        $methodGroup = array_pop($prePop);
        foreach ($methodGroup as $curMethod) {
            if ((
                    $curMethod->specificType === $specificType ||
                    $curMethod->type === $specificType
                ) &&
                in_array($customerType, (array) $curMethod->customerType)
            ) {
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
     *
     * @param bool $noAssert
     *
     * @throws \Exception
     */
    public function getPaymentMethods($noAssert = false) {
        $this->__setUp();
        $methodList = $this->TEST->share('paymentMethods');
        if (is_array($methodList) && ! count($methodList) || ! is_array($methodList)) {
            $this->TEST->ECOM->setSimplifiedPsp(true);
            $paymentMethods = $this->TEST->ECOM->getPaymentMethods([], true);
            foreach ($paymentMethods as $method) {
                $this->TEST->share('METHOD_' . $method->id, $method, false);
            }
            $this->TEST->share('paymentMethods', $paymentMethods, false);
        } else {
            $paymentMethods = is_array($methodList) ? array_pop($methodList) : $methodList;
        }
        if ( ! $noAssert) {
            static::assertGreaterThan(1, $paymentMethods);
        }
    }

    /**
     * @test Direct test - Extract orderdata from library
     * @testdox
     * @throws \Exception
     */
    public function getOrderData() {
        $this->__setUp();
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
    public function getCurrentReleaseTests() {
        $this->__setUp();
        $currentReleaseShouldNotBeEmpty = $this->TEST->ECOM->getCurrentRelease();  // php 5.5
        static::assertFalse($this->TEST->ECOM->getIsCurrent("1.0.0") && ! empty($currentReleaseShouldNotBeEmpty));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function getAnnuityMethods() {
        $this->__setUp();
        $annuityObjectList = $this->TEST->ECOM->getPaymentMethodsByAnnuity();
        $annuityIdList     = $this->TEST->ECOM->getPaymentMethodsByAnnuity(true);
        static::assertTrue(count($annuityIdList) >= 1 && count($annuityObjectList) >= 1);
    }

    /**
     * @todo Countable issue linked to an IO event
     * @throws \Exception
     */
    public function findPaymentsXmlBody() {
        $this->__setUp();
        $paymentScanList = $this->TEST->ECOM->findPayments(['statusSet' => ['IS_DEBITED']], 1, 10, [
            'ascending'   => false,
            'sortColumns' => ['FINALIZED_TIME', 'MODIFIED_TIME', 'BOOKED_TIME'],
        ]);

        $handle      = $this->TEST->ECOM->getCurlHandle();
        $requestBody = $handle->getRequestBody();
        static::assertTrue(strlen($requestBody) > 100 && count($paymentScanList));
    }

    /**
     * @test
     * @throws \Exception
     */
    public function updateStrangePaymentReference() {
        $this->__setUp();
        $showFrames = false;
        $this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);

        // First update.
        $this->TEST->ECOM->addOrderLine("Product-1337", "", 800, 25);
        $id            = $this->TEST->ECOM->getPreferredPaymentId();
        $fIframe       = $this->TEST->ECOM->createPayment($id);
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
        static::assertTrue(! empty($renameToFirst) && $fIframe !== $sIframe);
    }

    /**
     * @test
     * @testdox Disabling this for now as it is extremely annoying during tests.
     * @throws \Exception
     */
    public function getCostOfPurchase() {
        $this->__setUp();
        $result = $this->TEST->ECOM->getCostOfPurchase('PARTPAYMENT', '10000');
        //$result = $this->TEST->ECOM->getCostOfPurchase($this->getMethodId(), '10000');

        static::assertTrue(strlen($result) > 1000);
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
    public function hashedSpecLines() {
        $this->__setUp();
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
    public function getEmptyCallbacksList() {
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
        $this->__setUp();

        try {
            //$this->TEST->ECOM->setRegisterCallbacksViaRest(false);
            $this->TEST->ECOM->unregisterEventCallback(255, true, true);
        } catch (\Exception $e) {
        }
        $callbacks = $this->TEST->ECOM->getCallBacksByRest(true);
        static::assertTrue(is_array($callbacks) && ! count($callbacks) ? true : false);
    }

    /**
     * @test
     */
    public function bitMaskControl() {
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
    public function getEmptyCallbacksListSecond() {
        $this->__setUp();
        try {
            $this->TEST->ECOM->unregisterEventCallback(255, true);
        } catch (\Exception $e) {
        }
        $callbacks = $this->TEST->ECOM->getCallBacksByRest();
        static::assertTrue(is_array($callbacks) && ! count($callbacks) ? true : false);
    }

    /**
     * @test Test registration of callbacks in three different ways - including backward compatibility.
     *
     * Note: We can not check whether the salt keys are properly set in realtime, but during our own
     * tests, it is confirmed that all salt keys are different after this test.
     *
     * @throws \Exception
     */
    public function setRegisterCallback() {
        $this->__setUp();
        $this->TEST->ECOM->setCallbackDigestSalt(
            uniqid(sha1(microtime(true))),
            RESURS_CALLBACK_TYPES::BOOKED
        );

        // Set "all global" key. If nothing are predefined in the call of registration
        $this->TEST->ECOM->setCallbackDigestSalt(uniqid(md5(microtime(true))));

        $cbCount     = 0;
        $templateUrl = "https://test.resurs.com/callbacks/";

        // Phase 1: Register callback with local salt key.
        if ($this->TEST->ECOM->setRegisterCallback(
            RESURS_CALLBACK_TYPES::FINALIZATION,
            $templateUrl . "type/finalization",
            [
                'digestAlgorithm' => 'md5',
                'digestSalt'      => uniqid(microtime(true)),
            ],
            'testuser',
            'testpass'
        )) {
            $cbCount ++;
        }

        // Phase 2: Register callback with the globally stored type-based key (see above).
        if ($this->TEST->ECOM->setRegisterCallback(
            RESURS_CALLBACK_TYPES::BOOKED,
            $templateUrl . "type/booked",
            [],
            'testuser',
            'testpass'
        )) {
            $cbCount ++;
        }

        // Phase 3: Register callback with the absolute global stored key (see above).
        if ($this->TEST->ECOM->setRegisterCallback(
            RESURS_CALLBACK_TYPES::AUTOMATIC_FRAUD_CONTROL,
            $templateUrl . "type/automatic_fraud_control",
            [],
            'testuser',
            'testpass'
        )) {
            $cbCount ++;
        }

        // Phase 4: Make sure this works for UPDATE also.
        if ($this->TEST->ECOM->setRegisterCallback(
            RESURS_CALLBACK_TYPES::UPDATE,
            $templateUrl . "type/finalization",
            [
                'digestAlgorithm' => 'md5',
                'digestSalt'      => uniqid(sha1(md5(microtime(true)))),
            ],
            'testuser',
            'testpass'
        )) {
            $cbCount ++;
        }

        // Phase 5: Include ANNULLMENT
        if ($this->TEST->ECOM->setRegisterCallback(
            RESURS_CALLBACK_TYPES::ANNULMENT,
            $templateUrl . "type/annul",
            [],
            'testuser',
            'testpass'
        )) {
            $cbCount ++;
        }

        static::assertTrue($cbCount === 5);
    }

    /**
     * @return null
     * @throws \Exception
     */
    private function getPaymentMethodsData() {
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
    public function getPaymentWrong() {
        $this->__setUp();
        try {
            $this->TEST->ECOM->getPayment("FAIL_HERE");
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            // Code 3 = REST, Code 8 = SOAP (180914)
            static::assertTrue($code === 8 || $code === 404);
        }
    }

    /**
     * @test
     */
    public function getPaymentWrongRest() {
        $this->__setUp();
        try {
            $this->TEST->ECOM->setFlag('GET_PAYMENT_BY_REST');
            $this->TEST->ECOM->getPayment('FAIL_HERE');
        } catch (\Exception $e) {
            $code = (int) $e->getCode();
            // Code 3 = REST, Code 8 = SOAP (180914)
            static::assertTrue($code === 3 || $code === 404);
        }
        $this->TEST->ECOM->deleteFlag('GET_PAYMENT_BY_REST');
    }

    /**
     * @test
     */
    public function getPaymentUnexistentSoap() {
        $this->__setUp();
        try {
            $this->TEST->ECOM->getPayment('FAIL_HERE');
        } catch (\Exception $e) {
            // This should NEVER throw anything else than 3 (REST) or 8 (SOAP)
            $code = $e->getCode();
            static::assertTrue($code === 8);
        }
    }

    /**
     * @param $addr
     *
     * @return bool
     */
    private function isProperIp($addr) {
        $not = ['127.0.0.1'];
        if (filter_var(trim($addr), FILTER_VALIDATE_IP) && ! in_array(trim($addr), $not)) {
            return true;
        }

        return false;
    }

    /**
     * @test
     * @throws \Exception
     */
    public function validateCredentials() {
        $this->__setUp();
        $isNotValid = $this->TEST->ECOM->validateCredentials(RESURS_ENVIRONMENTS::TEST, 'fail', 'fail');
        $isValid    = $this->TEST->ECOM->validateCredentials(RESURS_ENVIRONMENTS::TEST, $this->username,
            $this->password);
        $onInit     = new ResursBank();
        // Using this function on setAuthentication should immediately throw exception if not valid.
        $onInitOk = $onInit->setAuthentication($this->username, $this->password, true);

        $initAndValidate = new ResursBank($this->username, $this->password);
        $justValidated   = $initAndValidate->validateCredentials();
        static::assertTrue($isValid && ! $isNotValid && $onInitOk && $justValidated);
    }

    /**
     * @test
     *
     * Put order with quantity 100. Annul 50, debit 50. Using old arrayed method.
     *
     * @throws \Exception
     */
    public function annulAndDebitPaymentQuantityOldMethod() {
        $this->__setUp();
        try {
            $payment   = $this->generateSimpleSimplifiedInvoiceQuantityOrder();
            $paymentid = $payment->paymentId;

            $this->TEST->ECOM->annulPayment($paymentid, [['artNo' => 'PR01', 'quantity' => 50]]);
            $this->TEST->ECOM->finalizePayment($paymentid, [['artNo' => 'PR01', 'quantity' => 50]]);
        } catch (\Exception $e) {
            if ($e->getCode() >= 500) {
                static::markTestSkipped(
                    sprintf('Test %s failed due to code >= 500 (%s). Skipped!', __FUNCTION__, $e->getCode())
                );

                return;
            }
        }

        static::assertTrue(
            $this->getPaymentStatusQuantity(
                $paymentid,
                [
                    'AUTHORIZE' => [
                        'PR01',
                        100,
                    ],
                    'ANNUL'     => [
                        'PR01',
                        50,
                    ],
                    'DEBIT'     => [
                        'PR01',
                        50,
                    ],
                ]
            )
        );
    }

    /**
     * @test
     *
     * Put order with quantity 100. Annul 50, debit 50. Using addOrderLine.
     *
     * @throws \Exception
     */
    public function annulAndDebitedPaymentQuantityProperMethod() {
        $this->__setUp();
        try {
            // Four orderlines are normally created here.
            $payment   = $this->generateSimpleSimplifiedInvoiceQuantityOrder('8305147715', true);
            $paymentid = $payment->paymentId;

            // Annul 50 of PR01.
            $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 50);
            $this->TEST->ECOM->annulPayment($paymentid);
            // Debit the rest of PR01.
            $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 50);
            $this->TEST->ECOM->finalizePayment($paymentid);
        } catch (\Exception $e) {
            if ($e->getCode() >= 500) {
                static::markTestSkipped(
                    sprintf('Test %s failed due to code >= 500 (%s). Skipped!', __FUNCTION__, $e->getCode())
                );

                return;
            }
        }

        static::assertTrue(
            $this->getPaymentStatusQuantity(
                $paymentid,
                [
                    'AUTHORIZE' => [
                        'PR01',
                        100,
                    ],
                    'ANNUL'     => [
                        'PR01',
                        50,
                    ],
                    'DEBIT'     => [
                        'PR01',
                        50,
                    ],
                ]
            )
        );
    }

    /**
     * @test
     *
     * Put order with quantity 100. Annul 50, debit 50, credit 25.
     *
     * @throws \Exception
     */
    public function annulDebitAndCreditPaymentQuantityProperMethod() {
        $this->__setUp();
        $payment   = $this->generateSimpleSimplifiedInvoiceQuantityOrder('8305147715', true);
        $paymentid = $payment->paymentId;

        $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 50);
        $this->TEST->ECOM->annulPayment($paymentid);
        $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 50);
        $this->TEST->ECOM->finalizePayment($paymentid);
        $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 25);
        $this->TEST->ECOM->creditPayment($paymentid);

        static::assertTrue(
            $this->getPaymentStatusQuantity(
                $paymentid,
                [
                    'AUTHORIZE' => [
                        'PR01',
                        100,
                    ],
                    'ANNUL'     => [
                        'PR01',
                        50,
                    ],
                    'CREDIT'    => [
                        'PR01',
                        25,
                    ],
                    'DEBIT'     => [
                        'PR01',
                        50,
                    ],
                ]
            )
        );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function creditSomethingElse() {
        try {
            $this->__setUp();
            $payment   = $this->generateSimpleSimplifiedInvoiceQuantityOrder('8305147715', true);
            $paymentid = $payment->paymentId;

            $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 100);
            $this->TEST->ECOM->finalizePayment($paymentid);

            $this->TEST->ECOM->addOrderLine('Rabatt', 'Rabatt', 120, 25, 'st', 'ORDER_LINE', 25);
            $this->TEST->ECOM->creditPayment($paymentid, null, false, true);

            $this->TEST->ECOM->addOrderLine('Rabatt', 'Rabatt', 120, 25, 'st', 'ORDER_LINE', 25);
            $this->TEST->ECOM->creditPayment($paymentid, null, false, true);

            // Wait a sec.
            sleep(1);

            $paymentStatusQuantity = $this->getPaymentStatusQuantity(
                $paymentid,
                [
                    'DEBIT'  => [
                        'PR01',
                        100,
                    ],
                    'CREDIT' => [
                        'Rabatt',
                        50,
                    ],
                ]
            );
        } catch (\Exception $e) {
            if ($e->getCode() >= 500) {
                static::markTestSkipped(
                    sprintf('Test %s failed due to code >= 500 (%s). Skipped!', __FUNCTION__, $e->getCode())
                );

                return;
            }
        }

        // The new creditec object does not seem to be reflected in its state.
        static::assertTrue(
            $paymentStatusQuantity
        );
    }

    /**
     * @test
     *
     * Put order with quantity 100. Annul 50, debit 50, credit 25. And then kill the full order.
     * Expected result is:
     *
     * Part 1: one row has 50 annulled, 25 debited and 25 credited.
     * Part 2: one row has 50 annulled, 50 credited, and the resut of the order should be annulled.
     *
     *
     * @throws \Exception
     */
    public function cancelMixedPayment() {
        try {
            $this->__setUp();
            $payment   = $this->generateSimpleSimplifiedInvoiceQuantityOrder('8305147715', true);
            $paymentid = $payment->paymentId;

            // Annul 50
            $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 50);
            $this->TEST->ECOM->annulPayment($paymentid);

            // Finalize 50
            $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 50);
            $this->TEST->ECOM->finalizePayment($paymentid);

            // Credit 25
            $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 25);
            $this->TEST->ECOM->creditPayment($paymentid);

            // Annul the rest (which gives us another 25 credits on PR01. Credited should at this point be 50.).
            $this->TEST->ECOM->cancelPayment($paymentid);
        } catch (\Exception $e) {
            if ($e->getCode() >= 500) {
                static::markTestSkipped(
                    sprintf('Test %s failed due to code >= 500 (%s). Skipped!', __FUNCTION__, $e->getCode())
                );

                return;
            }
        }

        static::assertTrue(
            $this->getPaymentStatusQuantity(
                $paymentid,
                [
                    'AUTHORIZE' => [
                        'PR01',
                        100,
                    ],
                    'ANNUL'     => [
                        'PR02',
                        100,
                    ],
                    'CREDIT'    => [
                        'PR01',
                        50,
                    ],
                    'DEBIT'     => [
                        'PR01',
                        50,
                    ],
                ]
            )
        );
    }

    /**
     * Get mathching result from payment.
     *
     * @param $paymentId
     * @param array $requestFor
     *
     * @return bool
     * @throws \Exception
     */
    private function getPaymentStatusQuantity($paymentId, $requestFor = []) {
        // This is from newer releases arrays instead of objects (unfortunately).
        // Mostly because some objects can't be copied as their key values are manipulated
        // in some foreach loops (which is very unwelcome).
        $statusList      = $this->TEST->ECOM->getPaymentDiffByStatus($paymentId);
        $statusListTable = $this->TEST->ECOM->getPaymentDiffAsTable($statusList);
        $expectedMatch   = count($requestFor);
        $matches         = 0;

        foreach ($requestFor as $type => $reqList) {
            if (isset($reqList[1])) {
                $setArt      = $reqList[0];
                $setQuantity = $reqList[1];
                foreach ($statusListTable as $article) {
                    if ($article['artNo'] === $setArt && (int) $article[$type] === (int) $setQuantity) {
                        $matches ++;
                    }
                }
            }
        }

        return $expectedMatch === $matches ? true : false;
    }

    /**
     * @test
     */
    public function stringExceptions() {
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
    public function failUpdatePaymentReference() {
        $this->__setUp();
        try {
            $this->TEST->ECOM->updatePaymentReference('not_this', 'not_that');
        } catch (\Exception $e) {
            // Faultcode 700 = ECom Exception PAYMENT_SESSION_NOT_FOUND.
            // Faultcode 14 = Payment session does not exist.
            static::assertTrue($e->getCode() === 700 || $e->getCode() === 14);
        }
    }

    /**
     * @test
     * @testdox Reverse thinking in purgerverse.
     */
    public function keyPurging() {
        $this->__setUp();
        $purgableByResurs      = $this->TEST->ECOM->setGetPaymentMatchKeys('tiny');
        $purgableByWooCommerce = $this->TEST->ECOM->setGetPaymentMatchKeys(['artNo', 'description', 'unitMeasure']);

        static::assertTrue(
            count($purgableByResurs) === 7 &&
            count($purgableByWooCommerce) === 6 ? true : false
        );
    }

    /**
     * @test
     * @throws \Exception
     */
    public function getPaymentMethodsCache() {
        $this->__setUp();
        $methodArray = [];
        $counter     = 0;
        $this->TEST->ECOM->getPaymentMethods();
        $startTime = microtime(true);
        while ($counter ++ <= 20) {
            $methodArray[] = $this->TEST->ECOM->getPaymentMethods();
        }
        $endTime = microtime(true);
        // Above setup should finish before 5 seconds passed.
        $diff = $endTime - $startTime;
        $this->TEST->ECOM->getPaymentMethods(['customerType' => 'NATURAL']);
        $invoiceCache = $this->TEST->ECOM->getPaymentMethodSpecific('NATURALINVOICE');

        static::assertTrue(
            ($diff < 5 ? true : false) &&
            (isset($invoiceCache->id) && $invoiceCache->id === 'NATURALINVOICE')
        );
    }

    /**
     * @test
     */
    public function getPriceInfo() {
        $this->__setUp();
        $myMethods = $this->TEST->ECOM->getPaymentMethods();

        // Normal one method.
        $getCostOfPriceInfoUrl = $this->TEST->ECOM->getCostOfPriceInformation($this->getMethodId(), 1000);
        // Fetched one method.
        $getCostOfPriceInfoData = $this->TEST->ECOM->getCostOfPriceInformation($this->getMethodId(), 1000, true);
        // Tabbed all methods.
        $priceInfoHtml = $this->TEST->ECOM->getCostOfPriceInformation($myMethods, 1000);

        // Priceinfo is fetchable too, but will destroy the layout as the CSS is located at Resurs Bank, not locally
        // stored.
        //$priceInfoHtmlFetched = $this->TEST->ECOM->getCostOfPriceInformation($myMethods, 1000, true);

        static::assertTrue(
            preg_match('/^http/', $getCostOfPriceInfoUrl) ? true : false &&
            preg_match('/\<html\>/is',
                $getCostOfPriceInfoData) ? true : false,
            preg_match('/\<html\>/is', $priceInfoHtml) ? true : false
        );
    }

    /**
     * Put this at the lowest row level in the tests reset and play with invoice numbers
     * IF you need to bring it to autotesting. As it consumes an enormous amount of time,
     * we exclude it per default as long as we runs on a free pipeline.
     *
     * Test are running four times:
     *  - Default: Set invoice number only if there is no number (null).
     *  - Legacy: Run legacy mode, statically set (detected) invoice id. Increment when necessary.
     *  - Legacy: Run legacy in error mode, statically set where incremental invoices fail (Expect legacy exception).
     *
     *  Always require ECom Instance Reset in this one to secure that no conflicts occur.
     *
     * @throws \Exception
     */
    public function finalizeWithoutInvoiceId() {
        $this->__setUp();
        $noErrorDynamic      = false;
        $noErrorStatic       = false;
        $noErrorStaticRepeat = false;

        $finalizationResponseNoInvoice           = false;
        $finalizationResponseYesInvoice          = false;
        $finalizationResponseYesInvoiceFailTwice = false;

        // Default: Attempt to debit with no invoice set.
        $this->TEST->ECOM->resetInvoiceNumber();

        $payment = [];
        for ($paymentIndex = 1; $paymentIndex <= 4; $paymentIndex ++) {
            $this->TEST->ECOM->setPreferredId(uniqid(microtime(true)));
            try {
                $payment[$paymentIndex] = $this->generateSimpleSimplifiedInvoiceQuantityOrder('8305147715');
            } catch (\Exception $bookPaymentException) {
                if ($bookPaymentException->getCode() >= 500) {
                    static::markTestSkipped(
                        sprintf(
                            'Error >= 500 occurred in %s. Skip the rest (state: %s).',
                            __FUNCTION__,
                            'generateSimpleSimplifiedInvoiceQuantityOrder'
                        )
                    );

                    return;
                }
            }
        }

        $this->TEST = new RESURS_TEST_BRIDGE($this->username, $this->password);
        try {
            $finalizationResponseNoInvoice = $this->TEST->ECOM->finalizePayment($payment[1]->paymentId);
        } catch (\Exception $noInvoiceException) {
            $noErrorDynamic = true;
            if ($noInvoiceException->getCode() >= 500) {
                static::markTestSkipped(
                    sprintf(
                        'Error >= 500 occurred in %s. Skip the rest (state: %s).',
                        __FUNCTION__,
                        'finalizePayment[1]'
                    )
                );

                return;
            }
        }

        // Legacy: Run legacy mode, statically set (detected) invoice id. Increment when necessary.
        $this->TEST = new RESURS_TEST_BRIDGE($this->username, $this->password);
        $this->TEST->ECOM->setFlag('AFTERSHOP_STATIC_INVOICE');
        try {
            $finalizationResponseYesInvoice = $this->TEST->ECOM->finalizePayment($payment[2]->paymentId);
        } catch (\Exception $yesInvoceException) {
            $noErrorStatic = true;
            if ($yesInvoceException->getCode() >= 500) {
                static::markTestSkipped(
                    sprintf(
                        'Error >= 500 occurred in %s. Skip the rest (state: %s).',
                        __FUNCTION__,
                        'finalizePayment[1]'
                    )
                );

                return;
            }
        }

        // Legacy: Run legacy in error mode, statically set where incremental invoices fail (Expect legacy exception).
        $this->TEST = new RESURS_TEST_BRIDGE($this->username, $this->password);
        $this->TEST->ECOM->setFlag('AFTERSHOP_STATIC_INVOICE');
        $this->TEST->ECOM->setFlag('TEST_INVOICE');
        try {
            $finalizationResponseYesInvoiceFailTwice = $this->TEST->ECOM->finalizePayment($payment[3]->paymentId);
        } catch (\Exception $failTwiceException) {
            $noErrorStaticRepeat = true;
            if ($failTwiceException->getCode() >= 500) {
                static::markTestSkipped(
                    sprintf(
                        'Error >= 500 occurred in %s. Skip the rest (state: %s).',
                        __FUNCTION__,
                        'finalizePayment[1]'
                    )
                );

                return;
            }
        }

        $expectedAssertResult = (
            (bool) $finalizationResponseNoInvoice &&
            (bool) $finalizationResponseYesInvoice &&
            (bool) ! $finalizationResponseYesInvoiceFailTwice &&
            (bool) ! $noErrorDynamic &&
            (bool) ! $noErrorStatic &&
            (bool) $noErrorStaticRepeat
        ) ? true : false;

        if ( ! $expectedAssertResult) {
            // "Debug mode" required for this assertion part as it tend to fail sometimes and sometimes not.
            $assertList = [
                '$finalizationResponseNoInvoice ?true?'            => $finalizationResponseNoInvoice,
                '$finalizationResponseYesInvoice ?true?'           => $finalizationResponseYesInvoice,
                '$finalizationResponseYesInvoiceFailTwice ?false?' => $finalizationResponseYesInvoiceFailTwice,
                '$noErrorDynamic ?false?'                          => $noErrorDynamic,
                '$noErrorStatic ?false?'                           => $noErrorStatic,
                '$noErrorStaticRepeat ?true?'                      => $noErrorStaticRepeat,
            ];
            print_r($assertList);
        }

        static::assertTrue($expectedAssertResult);

        // Final reset.
        $this->TEST = new RESURS_TEST_BRIDGE($this->username, $this->password);
        $this->TEST->ECOM->getNextInvoiceNumberByDebits();
    }

    /**
     * @test
     * @testdox Quicktest of the iframe.
     * @throws \Exception
     */
    public function getRcoFrame() {
        $this->__setUp();
        $this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);

        // First update.
        $this->TEST->ECOM->addOrderLine("Product-1337", "", 800, 25);
        $id          = $this->TEST->ECOM->getPreferredPaymentId();
        $fIframe     = $this->TEST->ECOM->createPayment($id);
        $rcoResponse = $this->TEST->ECOM->getFullCheckoutResponse();
        static::assertTrue((
        preg_match('/<iframe/is', $fIframe) ? true : false &&
        count($rcoResponse) >= 3 ? true : false &&
            (isset($rcoResponse->script) && ! empty($rcoResponse->script))
        ));
    }

    /**
     * @test
     * @testdox Clean up special test data from share file
     */
    public function finalTest() {
        $this->__setUp();
        $this->TEST->ECOM->resetInvoiceNumber();
        static::assertTrue($this->TEST->unshare("thisKey"));
    }
}
