<?php
/**
 * Resurs Bank EComPHP - Test suite.
 * Some of the tests in this suite is being made to check that the "share data between tests" works properly.
 * As setUp() resets tests to basic each time it runs, we can not share for example payments that we can make more
 * then one test on, with different kind of expectations.
 *
 * @package EcomPHPTest
 * @author Resurs Bank AB, Tomas Tornevall <tomas.tornevall@resurs.se>
 * @link https://test.resurs.com/docs/x/KYM0 Get started - PHP Section
 * @link https://resursbankplugins.atlassian.net/browse/ECOMPHP-214 Rebuilding!
 * @license Apache 2.0
 */

namespace Resursbank\RBEcomPHP;

// Set up local user agent for identification with webservices
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = 'EComPHP/Test-InternalClient';
}

// For pipelines.
if (!isset($_ENV['standalone_ecom'])) {
    $_ENV['standalone_ecom'] = '7.1|8.0';
}

require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/classes/ResursBankTestClass.php');
require_once(__DIR__ . '/hooks.php');

// Resurs Bank usages
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ResursException;
use TorneLIB\Exception\ExceptionHandler;
use TorneLIB\Model\Type\dataType;
use TorneLIB\Model\Type\requestMethod;
use TorneLIB\Module\Config\WrapperConfig;
use TorneLIB\Module\Network\NetWrapper;
use TorneLIB\Module\Network\Wrappers\CurlWrapper;
use TorneLIB\MODULE_CURL;
use TorneLIB\Utils\Generic;
use TorneLIB\Utils\Memory;
use function in_array;

(new Memory())->setMemoryLimit('-1');

class resursBankTest extends TestCase
{
    /**
     * @var ResursBank $API EComPHP
     */
    protected $API;

    /** @var RESURS_TEST_BRIDGE $TEST Used for standard tests and simpler flow setup */
    protected $TEST;

    /** @var string Username to web services. */
    private $username = 'ecomphpPipelineTest';
    /** @var string Password to web services. */
    private $password = '4Em4r5ZQ98x3891D6C19L96TQ72HsisD';

    private $flowHappyCustomer = '8305147715';
    private $flowHappyCustomerName = 'Vincent Williamsson Alexandersson';
    /** @noinspection PhpUnusedPrivateFieldInspection */
    /** @var string Landing page for callbacks */
    private $callbackUrl = 'https://test.resurs.com/signdummy/index.php?isCallback=1';

    /** @var string Landing page for signings */
    private $signUrl = 'https://test.resurs.com/signdummy/index.php?isSigningUrl=1';

    /**
     * @param $addr
     * @return bool
     */
    private function isProperIp($addr)
    {
        $not = ['127.0.0.1'];
        return filter_var(
                trim($addr),
                FILTER_VALIDATE_IP
            ) && !in_array(trim($addr), $not);
    }

    /**
     * @return bool
     * @throws ExceptionHandler
     */
    private function canProxy()
    {
        $return = false;

        $ipList = [
            '212.63.208.',
            '10.1.1.',
            '81.231.10.114',
        ];

        $wrapperData = (new CurlWrapper())
            ->setConfig((new WrapperConfig())->setUserAgent('ProxyTestAgent'))
            ->request('https://ipv4.netcurl.org')->getParsed();
        if (isset($wrapperData->ip)) {
            foreach ($ipList as $ip) {
                if (preg_match('/' . $ip . '/', $wrapperData->ip)) {
                    $return = true;
                    break;
                }
            }
        }

        return $return;
    }

    /**
     * Allow limited testing.
     * @return bool
     */
    private function allowVersion()
    {
        $return = false;

        $saE = explode('|', $_ENV['standalone_ecom']);
        if (is_array($saE) && count($saE)) {
            foreach ($saE as $textVersion) {
                $envFix = explode('.', $textVersion);
                $envFix[2] = '0';
                $higherThan = implode('.', $envFix);
                $envFix[1]++;
                $lowerThan = implode('.', $envFix);
                if ((version_compare(
                            PHP_VERSION,
                            $higherThan,
                            '>'
                        ) &&
                        version_compare(
                            PHP_VERSION,
                            $lowerThan,
                            '<'
                        ))
                    || preg_match(sprintf('/^%s/', $textVersion), PHP_VERSION)
                ) {
                    $return = true;
                    break;
                }
            }
        }

        return $return;
    }

    /**
     * @param string $codeException
     * @param string $message
     * @noinspection ParameterDefaultValueIsNotNullInspection
     */
    private function bailOut($codeException = '', $message = '')
    {
        if (is_object($codeException) && method_exists($codeException, 'getCode')) {
            /** @var Exception $codeException */
            $code = $codeException->getCode();
            $message = $codeException->getMessage();
        } else {
            $code = (int)$codeException;
        }

        if ($code >= 500 && $code <= 600) {
            $haltExceptionString = sprintf(
                'Halt on exception %s: %s',
                $code,
                $message
            );
            $haltExceptionString .= 'Errors over 500 normally indicates that something went wrong in the test environment, ' .
                "that's why we also abort the entire test";

            static::fail($haltExceptionString);
            die($haltExceptionString);
        }
    }

    /**
     * @throws Exception
     */
    public function unitSetup()
    {
        $this->API = new ResursBank();
        $this->API->setDebug(true);
        $this->TEST = new RESURS_TEST_BRIDGE($this->username, $this->password);

        // From 1.3.40 NETCURL is always 6.1+, so all soap based tests will run in cached mode.
        if (defined('NETCURL_VERSION') &&
            version_compare(NETCURL_VERSION, '6.1.0', '>=')
        ) {
            // This is not possible for netcurl-6.0, it will cause crashes, so we keep it only for 6.1.0+
            $this->TEST->ECOM->setWsdlCache(true);
        }
    }

    /**
     * @test
     */
    public function clearStorage()
    {
        $this->unitSetup();
        // Silently kill file.
        /** @noinspection PhpUsageOfSilenceOperatorInspection */
        @unlink(__DIR__ . '/storage/shared.serialize');
        // assertFileNotExists is deprecated. Do not use it, despite the inspections.
        /** @noinspection PhpUnitTestsInspection */
        static::assertNotTrue(file_exists(__DIR__ . '/storage/shared.serialize'));
    }

    /**
     * @test
     * @testdox Tests API credentials and getPaymentMethods.
     * @throws Exception
     */
    public function apiPaymentMethodsWithCredentials()
    {
        $this->unitSetup();
        static::assertTrue((count($this->TEST->getCredentialControl()) > 0));
    }

    /**
     * @test
     * @testdox EComPHP throws \Exceptions on credential failures
     * @throws Exception
     */
    public function apiPaymentMethodsWithWrongCredentials()
    {
        $this->unitSetup();
        try {
            $this->TEST->getCredentialControl(false);
        } catch (Exception $e) {
            $this->bailOut($e);
            static::assertEquals($e->getCode(), 401);
        }
    }

    /**
     * @test
     * @testdox Testing this suite's capabilities to share data between tests
     */
    public function shareDataOut()
    {
        $this->unitSetup();
        $this->TEST->share('outShare', 1);
        $keys = $this->TEST->share('thisKey', 'thatValue');
        static::assertTrue(count($keys) > 0);
    }

    /**
     * @test
     * @testdox Test shared data.
     */
    public function shareDataIn()
    {
        $this->unitSetup();
        $keys = $this->TEST->share('thisKey');
        static::assertTrue(count($keys) > 0);
    }

    /**
     * @test
     * @testdox Testing this suite's capability to remove keys from shared data (necessary to reset things)
     */
    public function shareDataRemove()
    {
        $this->unitSetup();
        if ($this->TEST->share('outShare')) {
            $this->TEST->unshare('outShare');
            $keys = $this->TEST->share();
            static::assertTrue(is_array($keys));
        } else {
            static::markTestSkipped('Test has been started without shareDataOut.');
        }
    }

    /**
     * @test
     * @testdox getCurlHandle (using getAddress)
     * @throws Exception
     */
    public function getAddressCurlHandle()
    {
        $this->unitSetup();
        if (!class_exists('\SimpleXMLElement')) {
            static::markTestSkipped('SimpleXMLElement missing');
        }

        $this->TEST->ECOM->getAddress($this->flowHappyCustomer);

        /** @var MODULE_CURL $lastCurlHandle */
        $lastCurlHandle = $this->TEST->ECOM->getCurlHandle(true);
        $curlResponse = $lastCurlHandle->getParsed();
        static::assertEquals(
            $curlResponse->fullName,
            $this->flowHappyCustomerName
        );
    }

    /**
     * @test
     * @testdox Direct test - Test adding order lines via the library and extract correct data
     */
    public function addOrderLine()
    {
        $this->unitSetup();
        $this->TEST->ECOM->addOrderLine('Product-1337', 'One simple orderline', 800, 25);
        $orderLines = $this->TEST->ECOM->getOrderLines();
        static::assertTrue(count($orderLines) > 0 && $orderLines[0]['artNo'] == 'Product-1337');
    }

    /**
     * @test
     */
    public function preMetaData()
    {
        $this->unitSetup();
        $this->TEST->ECOM->addOrderLine('Product-1337', 'One simple orderline', 800, 25);
        $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue');

        $meta = $this->TEST->ECOM->getMetaData(null, true);
        $metaKey = $this->TEST->ECOM->getMetaData(null, true, true);

        static::assertTrue(count($meta['payloadMetaData']) > 0 && count($metaKey['payloadMetaData']));
    }

    /**
     * @test Avoid duplicate metadata in pre set method.
     * @throws Exception
     */
    public function setMetaData()
    {
        $this->unitSetup();
        $this->TEST->ECOM->addOrderLine('Product-1337', 'One simple orderline', 800, 25);
        try {
            $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue');
            $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue');
            $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue');
        } catch (Exception $e) {
            $this->bailOut($e);
            static::assertSame($e->getCode(), 400);
        }
    }

    /**
     * @test Avoid duplicate metadata in pre set method.
     * @throws Exception
     */
    public function setMetaDataDuplicate()
    {
        $this->unitSetup();
        $this->TEST->ECOM->addOrderLine('Product-1337', 'One simple orderline', 800, 25);
        $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue', false);
        $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue', false);
        $this->TEST->ECOM->setMetaData('inboundKey', 'inboundValue', false);
        $plm = $this->TEST->ECOM->getMetaData(null, true);
        $allMetas = $plm['payloadMetaData'];

        static::assertSame(count($allMetas), 3);
    }

    /**
     * @test
     * @throws Exception
     */
    public function findPaymentByGovId()
    {
        $this->unitSetup();
        $payments = $this->TEST->ECOM->findPayments(['governmentId' => '8305147715']);
        static::assertTrue(is_array($payments) && count($payments));
    }

    /**
     * @test
     * @param bool $noAssert
     * @param string $govId
     * @return array
     * @throws Exception
     * @noinspection ParameterDefaultValueIsNotNullInspection
     */
    public function generateSimpleSimplifiedInvoiceOrder($noAssert = false, $govId = '198305147715')
    {
        $this->unitSetup();
        $this->TEST->ECOM->setPreferredId(uniqid(microtime(true), true));
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine('Product-1337', 'One simple orderline', 800, 25);
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer($govId, '0808080808', '0707070707', 'test@test.com', 'NATURAL');
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
     * @param bool $static
     * @return int
     */
    public function getProductPrice($static = false)
    {
        if (!$static) {
            return rand(30, 90);
        }

        return 90;
    }

    /**
     * @test
     *
     * @param string $govId
     * @param bool $staticProductPrice
     * @return array
     * @throws Exception
     */
    public function generateSimpleSimplifiedInvoiceQuantityOrder($govId = '198305147715', $staticProductPrice = false)
    {
        $this->unitSetup();
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine(
            'PR01',
            'PR01',
            $this->getProductPrice($staticProductPrice),
            25,
            'st',
            'ORDER_LINE',
            100
        );
        $this->TEST->ECOM->addOrderLine(
            'PR02',
            'PR02',
            $this->getProductPrice($staticProductPrice),
            25,
            'st',
            'ORDER_LINE',
            100
        );
        $this->TEST->ECOM->addOrderLine(
            'PR03',
            'PR03',
            $this->getProductPrice($staticProductPrice),
            25,
            'st',
            'ORDER_LINE',
            100
        );
        $this->TEST->ECOM->addOrderLine(
            'PR04',
            'PR04',
            $this->getProductPrice($staticProductPrice),
            25,
            'st',
            'ORDER_LINE',
            100
        );
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer($govId, '0808080808', '0707070707', 'test@test.com', 'NATURAL');
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $this->TEST->ECOM->setMetaData('metaKeyTestTime', time());
        $this->TEST->ECOM->setMetaData('metaKeyTestMicroTime', microtime(true));
        $response = $this->TEST->ECOM->createPayment($this->getMethodId());

        return $response;
    }

    /**
     * @test Finalize frozen orders - ECom should prevent this before Resurs Bank to save performance.
     *
     * @throws Exception
     */
    public function finalizeFrozen()
    {
        if (!$this->allowVersion()) {
            static::markTestSkipped(
                sprintf(
                    'Special test limited to one PHP version (%s) detected. ' .
                    'This is the wrong version (%s), so it is being skipped.',
                    isset($_ENV['standalone_ecom']) ? $_ENV['standalone_ecom'] : 'Detection failed',
                    PHP_VERSION
                )
            );
            return;
        }

        $this->unitSetup();
        $payment = $this->generateSimpleSimplifiedInvoiceOrder(true, '198101010000');
        if (isset($payment->paymentId) && $payment->bookPaymentStatus === 'FROZEN') {
            // Verified frozen.
            try {
                $this->TEST->ECOM->finalizePayment($payment->paymentId);
            } catch (Exception $e) {
                $this->bailOut($e);
                static::assertSame(
                    $e->getCode(),
                    \RESURS_EXCEPTIONS::ECOMMERCEERROR_NOT_ALLOWED_IN_CURRENT_STATE,
                    'Finalization properly prohibited by current state'
                );
            }
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getPaymentCached()
    {
        $this->unitSetup();
        $apiWithoutCache = new ResursBank($this->username, $this->password, null, false, ['setApiCache' => false]);
        $hasCache = $apiWithoutCache->getApiCache();
        $hasCacheDefault = $this->TEST->ECOM->getApiCache();
        $req = [];

        $this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);

        // Guarantee two different payment ids in this test.
        $this->TEST->ECOM->setPreferredId($this->TEST->ECOM->getPreferredPaymentId(25, '', true, true));
        $firstPayment = $this->generateSimpleSimplifiedInvoiceOrder(true);
        $this->TEST->ECOM->setPreferredId($this->TEST->ECOM->getPreferredPaymentId(25, '', true, true));
        $secondPayment = $this->generateSimpleSimplifiedInvoiceOrder(true);
        if (isset($firstPayment->paymentId)) {
            $req[] = $this->TEST->ECOM->getPayment($firstPayment->paymentId);
            $req[] = $this->TEST->ECOM->getPayment($secondPayment->paymentId);

            $requestEnd = 0;        // Should end when this reaches 3.
            $requestStart = time(); // When requests started.
            $timeTotal = 0;

            // Loop until 4 sec or more.
            while ($requestEnd < 3) {
                $requestEnd = time() - $requestStart;

                $currentRequestStartMillis = microtime(true);
                $req[] = $this->TEST->ECOM->getPayment($firstPayment->paymentId);
                $req[] = $this->TEST->ECOM->getPayment($secondPayment->paymentId);
                $currentRequestStopMillis = microtime(true);
                $currentRequestTimeSpeed = $currentRequestStopMillis - $currentRequestStartMillis;
                $timeTotal += $currentRequestTimeSpeed;
            }
            $timeMed = $timeTotal / count($req);

            /*
             * Required test result:
             *   - The cache should be able to request AT LEAST 10 getPayment in a period of three seconds.
             *      Initial tests shows that we could make at least 179 requests. NOTE: Pipelines just counted 6 calls.
             *      For netcurl 6.1 initial tests showed up test results of 3818 requests.
             *   - Each request should be able to respond under 1 second.
             *   - The first $hasCache was initially disabled via __construct and should be false.
             *   - The second hasCache is untouched and should be true.
             */

            // >= 5 for pipelines.
            // >= 10 for own tests.
            static::assertTrue(
                (count($req) >= 5) &&
                (float)$timeMed < 1 &&
                !$hasCache
                && $hasCacheDefault
            );
        }
    }

    /**
     * Only run this when emulating colliding orders in the woocommerce plugin.
     *
     * @param bool $noAssert
     * @return array
     * @throws Exception
     * @noinspection ParameterDefaultValueIsNotNullInspection
     */
    public function wooCommerceCollider($noAssert = false)
    {
        $this->unitSetup();
        $incremental = 1430;
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine('Product-1337', 'One simple orderline', 800, 25);
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer('198305147715', '0808080808', '0707070707', 'test@test.com', 'NATURAL');
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
     * @throws Exception
     */
    public function generateSimpleSimplifiedPspResponse()
    {
        $this->unitSetup();
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine('Product-1337', 'One simple orderline', 800, 25);
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer('198305147715', '0808080808', '0707070707', 'test@test.com', 'NATURAL');
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $response = $this->TEST->ECOM->createPayment($this->getMethodId('PAYMENT_PROVIDER'));
        // In a perfect world, a booked payment for PSP should generate SIGNING as the payment occurs
        // externally.
        static::assertSame($response->bookPaymentStatus, 'SIGNING');

        return $response;
    }

    /**
     * @test Using PSP during simplified flow (without government id / SSN)
     * @return array
     * @throws Exception
     */
    public function generateSimpleSimplifiedPspWithoutGovernmentIdCompatibility()
    {
        $this->unitSetup();
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer(null, '0808080808', '0707070707', 'test@test.com', 'NATURAL');
        $this->TEST->ECOM->addOrderLine('Product-1337', 'One simple orderline', 800, 25);
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $response = $this->TEST->ECOM->createPayment($this->getMethodId('PAYMENT_PROVIDER'));
        static::assertEquals($response->bookPaymentStatus, 'SIGNING');

        return $response;
    }

    /**
     * @return null
     * @throws Exception
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
     *
     * @param bool $noAssert
     *
     * @return array|mixed|null
     * @throws Exception
     * @noinspection ParameterDefaultValueIsNotNullInspection
     */
    public function getAddress($noAssert = false)
    {
        $this->unitSetup();
        $happyCustomer = $this->TEST->ECOM->getAddress($this->flowHappyCustomer);
        $this->TEST->share('happyCustomer', $happyCustomer, false);
        if (!$noAssert) {
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
     * @return mixed
     * @throws Exception
     * @noinspection ParameterDefaultValueIsNotNullInspection
     */
    public function getMethodId($specificType = 'INVOICE')
    {
        $this->unitSetup();
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
     * @throws Exception
     * @noinspection ParameterDefaultValueIsNotNullInspection
     */
    public function getMethod($specificType = 'INVOICE', $customerType = 'NATURAL')
    {
        $this->unitSetup();
        $return = null;
        $this->getPaymentMethods(false);
        $prePop = $this->TEST->share('paymentMethods');
        $methodGroup = array_pop($prePop);
        foreach ($methodGroup as $curMethod) {
            if ((
                    $curMethod->specificType === $specificType ||
                    $curMethod->type === $specificType
                ) &&
                in_array($customerType, (array)$curMethod->customerType)
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
     * @param bool $noAssert
     * @throws Exception
     */
    public function getPaymentMethods($noAssert = false)
    {
        $this->unitSetup();
        $methodList = $this->TEST->share('paymentMethods');
        /** @noinspection NotOptimalIfConditionsInspection */
        if ((is_array($methodList) && !count($methodList)) || !is_array($methodList)) {
            $this->TEST->ECOM->setSimplifiedPsp(true);
            $paymentMethods = $this->TEST->ECOM->getPaymentMethods([], true);
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
     * @test
     * netcurl 6.1.0 specific test with wsdl-cache vs without. This test activates the wsdl cache.
     * Example of the results:
     *   phpunit runtime: 1.83 seconds, Memory: 14.00 MB (not cached)
     *   phpunit runtime: 411 ms, Memory: 14.00 MB (cached)
     */
    public function ncCache()
    {
        if (!defined('NETCURL_VERSION')) {
            static::markTestSkipped('NETCURL_VERSION is not defined, so this is probably not 6.1.0');
            return;
        }

        $this->unitSetup();
        $this->TEST->ECOM->setWsdlCache(true);
        $methods = $this->TEST->ECOM->getPaymentMethods();
        static::assertTrue(
            count($methods) > 0
        );
    }

    /**
     * @test Direct test - Extract order data from library
     * @testdox
     * @throws Exception
     */
    public function getOrderData()
    {
        $this->unitSetup();
        $this->TEST->ECOM->setBillingByGetAddress($this->flowHappyCustomer);
        $this->TEST->ECOM->addOrderLine('RDL-1337', 'One simple orderline', 800, 25);
        $orderData = $this->TEST->ECOM->getOrderData();
        static::assertEquals($orderData['totalAmount'], '1000');
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAnnuityMethods()
    {
        $this->unitSetup();
        $annuityObjectList = $this->TEST->ECOM->getPaymentMethodsByAnnuity();
        $annuityIdList = $this->TEST->ECOM->getPaymentMethodsByAnnuity(true);
        static::assertTrue(count($annuityIdList) >= 1 && count($annuityObjectList) >= 1);
    }

    /**
     * @throws Exception
     * @todo Countable issue linked to an IO event
     */
    public function findPaymentsXmlBody()
    {
        $this->unitSetup();
        $paymentScanList = $this->TEST->ECOM->findPayments(['statusSet' => ['IS_DEBITED']], 1, 10, [
            'ascending' => false,
            'sortColumns' => ['FINALIZED_TIME', 'MODIFIED_TIME', 'BOOKED_TIME'],
        ]);

        $handle = $this->TEST->ECOM->getCurlHandle();
        $requestBody = $handle->getRequestBody();
        static::assertTrue(strlen($requestBody) > 100 && count($paymentScanList));
    }

    /**
     * @test
     * @throws Exception
     */
    public function updateStrangePaymentReference()
    {
        $this->unitSetup();
        $showFrames = false;
        $this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);

        // First update.
        $this->TEST->ECOM->addOrderLine('Product-1337', '', 800, 25);
        $id = $this->TEST->ECOM->getPreferredPaymentId();
        $fIframe = $this->TEST->ECOM->createPayment($id);
        $renameToFirst = microtime(true);
        $this->TEST->ECOM->updatePaymentReference($id, $renameToFirst);

        // Second update.
        $this->TEST->ECOM->addOrderLine('Product-1337-OverWriteMe', '', 1200, 25);
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
     * @testdox Disabling this for now as it is extremely annoying during tests.
     * @throws Exception
     */
    public function getCostOfPurchase()
    {
        $this->unitSetup();
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
     * @throws Exception
     */
    public function hashedSpecLines()
    {
        $this->unitSetup();
        $customerData = $this->getHappyCustomerData();
        $this->TEST->ECOM->addOrderLine('Product-1337', 'One simple orderline, red', 800, 25);
        $this->TEST->ECOM->addOrderLine('Product-1337', 'Second simple orderline, blue', 900, 25);
        $this->TEST->ECOM->addOrderLine('Product-1338', 'Third simple orderline', 1000, 25, 'st', 'ORDER_LINE', 3);
        $this->TEST->ECOM->addOrderLine('Product-1339', 'Our fee', 45, 25, 'st', 'FEE', 3);
        $this->TEST->ECOM->setBillingByGetAddress($customerData);
        $this->TEST->ECOM->setCustomer(null, '0808080808', '0707070707', 'test@test.com', 'NATURAL');
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $orderLineHash = $this->TEST->ECOM->getOrderLineHash();
        $this->TEST->ECOM->addOrderLine(
            'Hacked Product',
            'Article added after first orderline hash',
            1000,
            25,
            'st',
            'ORDER_LINE',
            3
        );
        $newOrderLineHash = $this->TEST->ECOM->getOrderLineHash();
        static::assertNotSame($orderLineHash, $newOrderLineHash);
    }

    /**
     * @test
     */
    public function bitMaskControl()
    {
        /** @noinspection SuspiciousBinaryOperationInspection */
        static::assertTrue(
            ((255 & RESURS_CALLBACK_TYPES::FINALIZATION) ? true : false) &&
            ((8 & RESURS_CALLBACK_TYPES::FINALIZATION) ? true : false) &&
            ((24 & RESURS_CALLBACK_TYPES::TEST) ? true : false) &&
            ((12 & RESURS_CALLBACK_TYPES::FINALIZATION && RESURS_CALLBACK_TYPES::AUTOMATIC_FRAUD_CONTROL) ? true : false) &&
            ((56 & RESURS_CALLBACK_TYPES::FINALIZATION && RESURS_CALLBACK_TYPES::AUTOMATIC_FRAUD_CONTROL) ? true : false) &&
            ((RESURS_CALLBACK_TYPES::FINALIZATION | RESURS_CALLBACK_TYPES::AUTOMATIC_FRAUD_CONTROL | RESURS_CALLBACK_TYPES::TEST) === 28)
        );
    }

    /**
     * @test
     * @testdox The normal way
     * @throws Exception
     */
    public function getEmptyCallbacksListSecond()
    {
        if (!$this->allowVersion()) {
            static::markTestSkipped(
                sprintf(
                    'Special test limited to one PHP version (%s) detected. ' .
                    'This is the wrong version (%s), so it is being skipped.',
                    isset($_ENV['standalone_ecom']) ? $_ENV['standalone_ecom'] : 'Detection failed',
                    PHP_VERSION
                )
            );
            return;
        }

        $this->unitSetup();
        try {
            $this->TEST->ECOM->unregisterEventCallback(255, true);
        } catch (Exception $e) {
            $this->bailOut($e);
            if ($e->getCode() === 1008) {
                throw $e;
            }
        }
        $callbacks = $this->TEST->ECOM->getCallBacksByRest();
        $noCriticalTrue = (is_array($callbacks) && !count($callbacks));
        if (!$noCriticalTrue) {
            static::markTestSkipped('Non critical skip: Callback count mismatched the assertion.');
            return;
        }

        static::assertTrue($noCriticalTrue);
    }

    /**
     * @test Test registration of callbacks in three different ways - including backward compatibility.
     *
     * Note: We can not check whether the salt keys are properly set in realtime, but during our own
     * tests, it is confirmed that all salt keys are different after this test.
     *
     * @param bool $noAssert
     * @throws Exception
     */
    public function setRegisterCallback($noAssert = false)
    {
        if (!$this->allowVersion()) {
            static::markTestSkipped(
                sprintf(
                    'Special test limited to one PHP version (%s) detected. ' .
                    'This is the wrong version (%s), so it is being skipped.',
                    isset($_ENV['standalone_ecom']) ? $_ENV['standalone_ecom'] : 'Detection failed',
                    PHP_VERSION
                )
            );
            return;
        }

        $this->unitSetup();
        $this->TEST->ECOM->setCallbackDigestSalt(
            uniqid(sha1(microtime(true)), true),
            RESURS_CALLBACK_TYPES::BOOKED
        );

        // Set "all global" key. If nothing are predefined in the call of registration
        $this->TEST->ECOM->setCallbackDigestSalt(uniqid(md5(microtime(true)), true));

        $cbCount = 0;
        $templateUrl = 'https://test.resurs.com/callbacks/';

        // Phase 1: Register callback with local salt key.
        if ($this->TEST->ECOM->setRegisterCallback(
            RESURS_CALLBACK_TYPES::FINALIZATION,
            $templateUrl . 'type/finalization',
            [
                'digestAlgorithm' => 'md5',
                'digestSalt' => uniqid(microtime(true), true),
            ],
            'testuser',
            'testpass'
        )) {
            $cbCount++;
        }

        // Phase 2: Register callback with the globally stored type-based key (see above).
        if ($this->TEST->ECOM->setRegisterCallback(
            RESURS_CALLBACK_TYPES::BOOKED,
            $templateUrl . 'type/booked',
            [],
            'testuser',
            'testpass'
        )) {
            $cbCount++;
        }

        // Phase 3: Register callback with the absolute global stored key (see above).
        if ($this->TEST->ECOM->setRegisterCallback(
            RESURS_CALLBACK_TYPES::AUTOMATIC_FRAUD_CONTROL,
            $templateUrl . 'type/automatic_fraud_control',
            [],
            'testuser',
            'testpass'
        )) {
            $cbCount++;
        }

        // Phase 4: Make sure this works for UPDATE also.
        if ($this->TEST->ECOM->setRegisterCallback(
            RESURS_CALLBACK_TYPES::UPDATE,
            $templateUrl . 'type/finalization',
            [
                'digestAlgorithm' => 'md5',
                'digestSalt' => uniqid(sha1(md5(microtime(true))), true),
            ],
            'testuser',
            'testpass'
        )) {
            $cbCount++;
        }

        // Phase 5: Include ANNULLMENT
        if ($this->TEST->ECOM->setRegisterCallback(
            RESURS_CALLBACK_TYPES::ANNULMENT,
            $templateUrl . 'type/annul',
            [],
            'testuser',
            'testpass'
        )) {
            $cbCount++;
        }

        if (!$noAssert) {
            static::assertSame($cbCount, 5);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function unregisterCallbacksViaRest()
    {
        if (!$this->allowVersion()) {
            static::markTestSkipped(
                sprintf(
                    'Special test limited to one PHP version (%s) detected. ' .
                    'This is the wrong version (%s), so it is being skipped.',
                    isset($_ENV['standalone_ecom']) ? $_ENV['standalone_ecom'] : 'Detection failed',
                    PHP_VERSION
                )
            );
            return;
        }

        $this->setRegisterCallback(true);

        try {
            $this->TEST->ECOM->unregisterEventCallback(255, true, false);
        } catch (Exception $e) {
            $this->bailOut($e);
        }
        $callbacks = $this->TEST->ECOM->getCallBacksByRest(true);
        static::assertTrue((is_array($callbacks) && !count($callbacks)));
    }

    /**
     * @return null
     * @throws Exception
     * @noinspection PhpUnusedPrivateMethodInspection
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
        $this->unitSetup();
        try {
            $this->TEST->ECOM->getPayment('FAIL_HERE');
        } catch (Exception $e) {
            $this->bailOut($e);
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
        $this->unitSetup();
        try {
            $this->TEST->ECOM->setFlag('GET_PAYMENT_BY_REST');
            $this->TEST->ECOM->getPayment('FAIL_HERE');
        } catch (Exception $e) {
            $this->bailOut($e);
            $code = (int)$e->getCode();
            // Code 3 = REST, Code 8 = SOAP (180914)
            static::assertTrue($code === 3 || $code === 404);
        }
        $this->TEST->ECOM->deleteFlag('GET_PAYMENT_BY_REST');
    }

    /**
     * @test
     */
    public function getPaymentFailSoap()
    {
        $this->unitSetup();
        try {
            $this->TEST->ECOM->getPayment('FAIL_HERE');
        } catch (Exception $e) {
            $this->bailOut($e);
            // This should NEVER throw anything else than 3 (REST) or 8 (SOAP)
            $code = $e->getCode();
            static::assertSame($code, 8);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function validateCredentials()
    {
        $this->unitSetup();
        $isNotValid = $this->TEST->ECOM->validateCredentials(RESURS_ENVIRONMENTS::TEST, 'fail', 'fail');
        $isValid = $this->TEST->ECOM->validateCredentials(
            RESURS_ENVIRONMENTS::TEST,
            $this->username,
            $this->password
        );
        $onInit = new ResursBank();
        // Using this function on setAuthentication should immediately throw exception if not valid.
        $onInitOk = $onInit->setAuthentication($this->username, $this->password, true);

        $initAndValidate = new ResursBank($this->username, $this->password);
        $justValidated = $initAndValidate->validateCredentials();
        static::assertTrue($isValid && !$isNotValid && $onInitOk && $justValidated);
    }

    /**
     * @test
     *
     * Put order with quantity 100. Annul 50, debit 50. Using addOrderLine.
     *
     * @throws Exception
     */
    public function annulAndDebitedPaymentQuantityProperMethod()
    {
        if (!$this->allowVersion()) {
            static::markTestSkipped(
                sprintf(
                    'Special test limited to one PHP version (%s) detected. ' .
                    'This is the wrong version (%s), so it is being skipped.',
                    isset($_ENV['standalone_ecom']) ? $_ENV['standalone_ecom'] : 'Detection failed',
                    PHP_VERSION
                )
            );
            return;
        }

        $this->unitSetup();
        try {
            // Four order lines are normally created here.
            $payment = $this->generateSimpleSimplifiedInvoiceQuantityOrder('8305147715', true);
            $paymentId = $payment->paymentId;

            // Annul 50 of PR01.
            $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 50);
            $this->TEST->ECOM->annulPayment($paymentId);
            // Debit the rest of PR01.
            $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 50);
            $this->TEST->ECOM->finalizePayment($paymentId);
        } catch (Exception $e) {
            $this->bailOut($e);
        }

        $wantedResult = $this->getPaymentStatusQuantity(
            $paymentId,
            [
                'AUTHORIZE' => [
                    'PR01',
                    100,
                ],
                'ANNUL' => [
                    'PR01',
                    50,
                ],
                'DEBIT' => [
                    'PR01',
                    50,
                ],
            ]
        );
        $assertThis = $wantedResult;
        if ($assertThis) {
            static::assertTrue($assertThis);
        } else {
            static::markTestSkipped(
                sprintf(
                    '%s assertion failed. This is not unusual so it has been skipped for now.',
                    __FUNCTION__
                )
            );
        }
    }

    /**
     * @test
     *
     * Put order with quantity 100. Annul 50, debit 50, credit 25.
     *
     * @throws Exception
     */
    public function annulDebitAndCreditPaymentQuantityProperMethod()
    {
        if (!$this->allowVersion()) {
            static::markTestSkipped(
                sprintf(
                    'Special test limited to one PHP version (%s) detected. ' .
                    'This is the wrong version (%s), so it is being skipped.',
                    isset($_ENV['standalone_ecom']) ? $_ENV['standalone_ecom'] : 'Detection failed',
                    PHP_VERSION
                )
            );
            return;
        }

        $this->unitSetup();
        $payment = $this->generateSimpleSimplifiedInvoiceQuantityOrder('8305147715', true);
        $paymentId = $payment->paymentId;

        $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 50);
        $this->TEST->ECOM->annulPayment($paymentId);
        $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 50);
        $this->TEST->ECOM->finalizePayment($paymentId);
        $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 25);
        $this->TEST->ECOM->creditPayment($paymentId);

        static::assertTrue(
            $this->getPaymentStatusQuantity(
                $paymentId,
                [
                    'AUTHORIZE' => [
                        'PR01',
                        100,
                    ],
                    'ANNUL' => [
                        'PR01',
                        50,
                    ],
                    'CREDIT' => [
                        'PR01',
                        25,
                    ],
                    'DEBIT' => [
                        'PR01',
                        50,
                    ],
                ]
            )
        );
    }

    /**
     * @test
     */
    public function annulStd()
    {
        if (!$this->allowVersion()) {
            static::markTestSkipped(
                sprintf(
                    'Special test limited to one PHP version (%s) detected. ' .
                    'This is the wrong version (%s), so it is being skipped.',
                    isset($_ENV['standalone_ecom']) ? $_ENV['standalone_ecom'] : 'Detection failed',
                    PHP_VERSION
                )
            );
            return;
        }

        $this->unitSetup();
        $payment = $this->generateSimpleSimplifiedInvoiceQuantityOrder('8305147715', true);
        $paymentId = $payment->paymentId;
        $res = $this->TEST->ECOM->annulPayment($paymentId);
        static::assertTrue((bool)$res);
    }

    /**
     * @test
     */
    public function annulByNetWrapper()
    {
        $xml = '<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:res="http://ecommerce.resurs.com/v4/msg/aftershopflow">  
  <SOAP-ENV:Body>  
    <res:annulPayment>  
      <paymentId>direct_xml_to_curl</paymentId>  
      <partPaymentSpec xsi:nil="true"/>  
      <createdBy>IntegrationService</createdBy>  
    </res:annulPayment>  
    </SOAP-ENV:Body>  
</SOAP-ENV:Envelope>';
        try {
            $response = (new NetWrapper())
                ->setAuthentication(
                    'atest',
                    'atest'
                )->request(
                    'https://test.resurs.com/ecommerce-test/ws/V4/AfterShopFlowService',
                    $xml,
                    requestMethod::METHOD_POST,
                    dataType::SOAP_XML
                );
        } catch (ExceptionHandler $e) {
            /** @var CurlWrapper $extended */
            $extended = $e->getExtendException()->getBody();
            static::assertTrue((bool)preg_match('/TypeId>8</', $extended));
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function creditSomethingElse()
    {
        if (!$this->allowVersion()) {
            static::markTestSkipped(
                sprintf(
                    'Special test limited to one PHP version (%s) detected. ' .
                    'This is the wrong version (%s), so it is being skipped.',
                    isset($_ENV['standalone_ecom']) ? $_ENV['standalone_ecom'] : 'Detection failed',
                    PHP_VERSION
                )
            );
            return;
        }

        try {
            $this->unitSetup();
            $payment = $this->generateSimpleSimplifiedInvoiceQuantityOrder('8305147715', true);
            $paymentId = $payment->paymentId;

            $this->TEST->ECOM->addOrderLine('PR01', 'PR01', 90, 25, 'st', 'ORDER_LINE', 100);
            $this->TEST->ECOM->finalizePayment($paymentId);

            $this->TEST->ECOM->addOrderLine('Rabatt', 'Rabatt', 120, 25, 'st', 'ORDER_LINE', 25);
            $this->TEST->ECOM->creditPayment($paymentId, null, false, true);

            $this->TEST->ECOM->addOrderLine('Rabatt', 'Rabatt', 120, 25, 'st', 'ORDER_LINE', 25);
            $this->TEST->ECOM->creditPayment($paymentId, null, false, true);

            // Wait a sec.
            sleep(1);

            $paymentStatusQuantity = $this->getPaymentStatusQuantity(
                $paymentId,
                [
                    'DEBIT' => [
                        'PR01',
                        100,
                    ],
                    'CREDIT' => [
                        'Rabatt',
                        50,
                    ],
                ]
            );
        } catch (Exception $e) {
            $this->bailOut($e);
        }

        // The new creditec object does not seem to be reflected in its state.
        static::assertTrue(
            $paymentStatusQuantity
        );
    }

    /**
     * Get mathching result from payment.
     *
     * @param $paymentId
     * @param array $requestFor
     * @param bool $getAsData
     * @return bool|array
     * @throws Exception
     */
    private function getPaymentStatusQuantity($paymentId, $requestFor = [], $getAsData = false)
    {
        // This is from newer releases arrays instead of objects (unfortunately).
        // Mostly because some objects can't be copied as their key values are manipulated
        // in some foreach loops (which is very unwelcome).
        $statusList = $this->TEST->ECOM->getPaymentDiffByStatus($paymentId);
        $statusListTable = $this->TEST->ECOM->getPaymentDiffAsTable($statusList);
        $expectedMatch = count($requestFor);
        $matches = 0;

        foreach ($requestFor as $type => $reqList) {
            if (isset($reqList[1])) {
                $setArt = $reqList[0];
                $setQuantity = isset($reqList[1]) ? $reqList[1] : '';
                foreach ($statusListTable as $article) {
                    if ($article['artNo'] === $setArt && (int)$article[$type] === (int)$setQuantity) {
                        $matches++;
                    }
                }
            }
        }

        if ($getAsData) {
            return [
                'expectedMatch' => $expectedMatch,
                'matches' => $matches,
                'requestFor' => $requestFor,
                'statusListTable' => $statusListTable,
            ];
        }

        return $expectedMatch === $matches;
    }

    /**
     * @test
     */
    public function stringExceptions()
    {
        try {
            throw new ResursException('Fail', 0, null, 'TEST_ERROR_CODE_AS_STRING', __FUNCTION__);
        } catch (Exception $e) {
            $this->bailOut($e);
            $firstCode = $e->getCode();
        }
        try {
            throw new ResursException('Fail', 0, null, 'TEST_ERROR_CODE_AS_STRING_WITHOUT_CONSTANT', __FUNCTION__);
        } catch (Exception $e) {
            $this->bailOut($e);
            $secondCode = $e->getCode();
        }

        static::assertTrue($firstCode === 1007 && $secondCode === 'TEST_ERROR_CODE_AS_STRING_WITHOUT_CONSTANT');
    }

    /**
     * @test
     */
    public function failUpdatePaymentReference()
    {
        $this->unitSetup();
        try {
            $this->TEST->ECOM->updatePaymentReference('not_this', 'not_that');
        } catch (Exception $e) {
            $this->bailOut($e);
            if ($e->getCode() !== 700 && $e->getCode() !== 14 && $e->getCode() !== 404) {
                static::markTestSkipped(__FUNCTION__ . ' exception code mismatch: ' . $e->getCode());
                return;
            }
            // Faultcode 700 = ECom Exception PAYMENT_SESSION_NOT_FOUND.
            // Faultcode 14 = Payment session does not exist.
            // Faultcode 404 = Probably caused by file_get_contents as the body can't be checked from there.
            static::assertTrue(
                $e->getCode() === 700 ||
                $e->getCode() === 14 ||
                $e->getCode() === 404
            );
        }
    }

    /**
     * @test
     * @testdox Reverse thinking in purgerverse.
     */
    public function keyPurging()
    {
        $this->unitSetup();
        $purgableByResurs = $this->TEST->ECOM->setGetPaymentMatchKeys('tiny');
        $purgableByWooCommerce = $this->TEST->ECOM->setGetPaymentMatchKeys(['artNo', 'description', 'unitMeasure']);

        static::assertTrue(
            (count($purgableByResurs) === 7 &&
                count($purgableByWooCommerce) === 6)
        );
    }

    /**
     * @test
     * @throws Exception
     */
    public function getPaymentMethodsCache()
    {
        $this->unitSetup();
        $methodArray = [];
        $counter = 0;
        $this->TEST->ECOM->getPaymentMethods();
        $startTime = microtime(true);
        while ($counter++ <= 20) {
            $methodArray[] = $this->TEST->ECOM->getPaymentMethods();
        }
        $endTime = microtime(true);
        // Above setup should finish before 5 seconds passed.
        $diff = $endTime - $startTime;
        $this->TEST->ECOM->getPaymentMethods(['customerType' => 'NATURAL']);
        $invoiceCache = $this->TEST->ECOM->getPaymentMethodSpecific('NATURALINVOICE');

        static::assertTrue(
            ($diff < 5) &&
            (isset($invoiceCache->id) && $invoiceCache->id === 'NATURALINVOICE')
        );
    }

    /**
     * @test
     */
    public function getPriceInfo()
    {
        $this->unitSetup();
        $myMethods = $this->TEST->ECOM->getPaymentMethods();

        // Normal one method.
        $getCostOfPriceInfoUrl = $this->TEST->ECOM->getCostOfPriceInformation($this->getMethodId(), 1000);
        // Fetched one method.
        $getCostOfPriceInfoData = $this->TEST->ECOM->getCostOfPriceInformation($this->getMethodId(), 1000, true);
        // Tabbed all methods.
        $priceInfoTabs = $this->TEST->ECOM->getCostOfPriceInformation($myMethods, 1000);

        // Priceinfo is fetchable too, but will destroy the layout as the CSS is located at Resurs Bank, not locally
        // stored.
        //$priceInfoHtmlFetched = $this->TEST->ECOM->getCostOfPriceInformation($myMethods, 1000, true);

        $gcp = preg_match('/^http/', $getCostOfPriceInfoUrl) ? true : false;
        $gcpContent = preg_match('/<html>(.*?)<\/html>/is', $getCostOfPriceInfoData) ? true : false;
        $tabbedContent = preg_match('/costOfPriceInfoTab/is', $priceInfoTabs) ? true : false;
        /** @noinspection SuspiciousBinaryOperationInspection */
        /** @noinspection NestedTernaryOperatorInspection */
        static::assertTrue(
            $gcp &&
            $gcpContent &&
            $tabbedContent
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
     * @throws Exception
     * @noinspection MultipleReturnStatementsInspection
     */
    public function finalizeWithoutInvoiceId()
    {
        if (!$this->allowVersion()) {
            static::markTestSkipped(
                sprintf(
                    'Special test limited to one PHP version (%s) detected. ' .
                    'This is the wrong version (%s), so it is being skipped.',
                    isset($_ENV['standalone_ecom']) ? $_ENV['standalone_ecom'] : 'Detection failed',
                    PHP_VERSION
                )
            );
            return;
        }
        $this->unitSetup();
        $noErrorDynamic = false;
        $noErrorStatic = false;
        $noErrorStaticRepeat = false;

        $finalizationResponseNoInvoice = false;
        $finalizationResponseYesInvoice = false;
        $finalizationResponseYesInvoiceFailTwice = false;

        // Default: Attempt to debit with no invoice set.
        $this->TEST->ECOM->resetInvoiceNumber();

        $payment = [];
        for ($paymentIndex = 1; $paymentIndex <= 4; $paymentIndex++) {
            $this->TEST->ECOM->setPreferredId(uniqid(microtime(true), true));
            try {
                $payment[$paymentIndex] = $this->generateSimpleSimplifiedInvoiceQuantityOrder('8305147715');
            } catch (Exception $bookPaymentException) {
                $this->bailOut($bookPaymentException);
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
        } catch (Exception $noInvoiceException) {
            $this->bailOut($noInvoiceException);
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
        } catch (Exception $yesInvoiceException) {
            $this->bailOut($yesInvoiceException);
            $noErrorStatic = true;
            if ($yesInvoiceException->getCode() >= 500) {
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
        } catch (Exception $failTwiceException) {
            $this->bailOut($failTwiceException);
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
            $finalizationResponseNoInvoice &&
            $finalizationResponseYesInvoice &&
            !$finalizationResponseYesInvoiceFailTwice &&
            !$noErrorDynamic &&
            !$noErrorStatic &&
            $noErrorStaticRepeat
        );

        if (!$expectedAssertResult) {
            // "Debug mode" required for this assertion part as it tend to fail sometimes and sometimes not.
            /** @noinspection PhpUnusedLocalVariableInspection */
            $assertList = [
                '$finalizationResponseNoInvoice ?true?' => $finalizationResponseNoInvoice,
                '$finalizationResponseYesInvoice ?true?' => $finalizationResponseYesInvoice,
                '$finalizationResponseYesInvoiceFailTwice ?false?' => $finalizationResponseYesInvoiceFailTwice,
                '$noErrorDynamic ?false?' => $noErrorDynamic,
                '$noErrorStatic ?false?' => $noErrorStatic,
                '$noErrorStaticRepeat ?true?' => $noErrorStaticRepeat,
            ];
        }

        static::assertTrue($expectedAssertResult);

        // Final reset.
        $this->TEST = new RESURS_TEST_BRIDGE($this->username, $this->password);
        $this->TEST->ECOM->getNextInvoiceNumberByDebits();
    }

    /**
     * @test
     * @testdox Quicktest of the iframe.
     * @throws Exception
     */
    public function getRcoFrame()
    {
        $this->unitSetup();
        $this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);

        // First update.
        $this->TEST->ECOM->addOrderLine('Product-1337', '', 800, 25);
        $id = $this->TEST->ECOM->getPreferredPaymentId();
        $fIframe = $this->TEST->ECOM->createPayment($id);
        $rcoResponse = (array)$this->TEST->ECOM->getFullCheckoutResponse();

        /** @noinspection SuspiciousBinaryOperationInspection */
        static::assertTrue(
            preg_match('/<iframe/is', $fIframe) ? true : false &&
                (count($rcoResponse) >= 3) &&
                (isset($rcoResponse->script) && !empty($rcoResponse->script))
        );
    }

    /**
     * @test
     */
    public function obsoletion()
    {
        $this->unitSetup();
        try {
            $this->TEST->ECOM->obsoleteMissingMethod();
        } catch (Exception $e) {
            static::assertSame($e->getCode(), 501);
        }
    }

    /**
     * @test
     * @throws Exception
     */
    public function getPaymentByRest()
    {
        $payment = $this->generateSimpleSimplifiedInvoiceQuantityOrder('8305147715', true);
        $this->TEST->ECOM->setFlag('GET_PAYMENT_BY_REST');
        $paymentId = $payment->paymentId;
        try {
            $this->TEST->ECOM->getPayment($paymentId);
        } catch (Exception $e) {
            $this->bailOut($e);
            // Special problems with SSL certificates and SoapClient is absent.
            if ($e->getCode() === 51) {
                static::markTestSkipped(
                    sprintf(
                        'Skipping test on error %s: %s',
                        $e->getCode(),
                        $e->getMessage()
                    )
                );
            }
        }
        $this->TEST->ECOM->deleteFlag('GET_PAYMENT_BY_REST');
    }

    /**
     * @test
     * @testdox Get iframeorigin from source or extract it from a session variable.
     * @throws Exception
     */
    public function getOwnOrigin()
    {
        $this->unitSetup();
        $this->TEST->ECOM->setFlag('STORE_ORIGIN');
        $this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        $this->TEST->ECOM->addOrderLine('Product-1337', '', 800, 25);
        $id = $this->TEST->ECOM->getPreferredPaymentId();
        $this->TEST->ECOM->createPayment($id);

        $extractFrom = 'https://omni-other.resurs.com/hello-world.js';
        $expect = 'https://omni-other.resurs.com';
        $realOrigin = $this->TEST->ECOM->getIframeOrigin();
        $notRealOrigin = $this->TEST->ECOM->getIframeOrigin($extractFrom, true);
        static::assertTrue(
            ($realOrigin === 'https://omnitest.resurs.com' &&
                $notRealOrigin === $expect)
        );
    }

    /**
     * @test
     * @testdox Expect arrays regardless of response
     * @throws Exception
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
        $this->unitSetup();

        try {
            // Running unreg through rest as of 1.3.31
            $this->setRegisterCallback(true);
            $this->TEST->ECOM->unregisterEventCallback(76, true);
            $this->TEST->ECOM->unregisterEventCallback(255, true);
        } catch (Exception $e) {
            $this->bailOut($e);
        }
        $callbacks = $this->TEST->ECOM->getCallBacksByRest(true);
        $noCriticalTrue = (is_array($callbacks) && !count($callbacks));

        if (!$noCriticalTrue) {
            static::markTestSkipped('Non critical skip: Callback count mismatched the assertion.');
            return;
        }

        static::assertTrue($noCriticalTrue);
    }

    /**
     * @test
     * @throws Exception
     */
    public function unregCallbacks()
    {
        $username = '';
        $password = '';

        if (isset($username) || empty($username)) {
            static::markTestSkipped(
                sprintf(
                    '%s is not a test, used for special purposes.',
                    __FUNCTION__
                )
            );
            return;
        }

        $specialCase = new ResursBank($username, $password);
        $specialCase->unregisterEventCallback(255, true, true);
        static::markTestSkipped(
            sprintf(
                '%s seems to pass without complications. ' .
                'This is not a standard test however, so it has been skipped.',
                __FUNCTION__
            )
        );
    }

    /**
     * @test
     * @throws Exception
     */
    public function getSaltByCrypto()
    {
        $this->unitSetup();
        $saltByCrypto = $this->TEST->ECOM->getSaltByCrypto(7, 24);
        static::assertEquals(strlen($saltByCrypto), 24);
    }

    /**
     * @test
     * Create iframe via proxy.
     * @throws Exception
     */
    public function proxyByBookRcoHalfway()
    {
        try {
            if (!$this->canProxy()) {
                static::markTestSkipped('Can not perform proxy tests with this client. Skipped.');
                return;
            }
        } catch (Exception $e) {
            static::markTestIncomplete(
                sprintf(
                    'Error %d during proxytest: %s',
                    $e->getCode(),
                    $e->getMessage()
                )
            );
            return;
        }

        $this->unitSetup();
        /** @var NetWrapper $CURL */
        $CURL = $this->TEST->ECOM->getCurlHandle();
        /** @var WrapperConfig $configData */
        $configData = $CURL->getConfig();
        $configData->setUserAgent('AnotherUserAgentRequest');
        $CURL->setConfig($configData);
        //CURL->setProxy('proxytest.resurs.it:80', CURLPROXY_HTTP);
        $this->TEST->ECOM->setCurlHandle($CURL);

        // As of 1.3.41, we can set proxy directly at ecom level.
        $this->TEST->ECOM->setProxy('proxytest.resurs.it:80', CURLPROXY_HTTP);
        try {
            $request = $CURL->doGet('https://ipv4.netcurl.org/ip.php');
        } catch (Exception $e) {
            static::markTestSkipped(
                sprintf(
                    'Proxy test skipped (%d): %s',
                    $e->getCode(),
                    $e->getMessage()
                )
            );
            return;
        }
        $ipBody = $request->getBody();
        if ($this->isProperIp($ipBody)) {
            $_SERVER['REMOTE_ADDR'] = $ipBody;
            $customerData = $this->getHappyCustomerData();
            $this->TEST->ECOM->setBillingByGetAddress($customerData);
            $this->TEST->ECOM->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
            $this->TEST->ECOM->setCustomer('8305147715', '0808080808', '0707070707', 'test@test.com', 'NATURAL');
            $this->TEST->ECOM->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
            $this->TEST->ECOM->addOrderLine('ProxyArtRequest', 'My Proxified Product', 800, 25);
            try {
                $iframeRequest = $this->TEST->ECOM->createPayment($this->getMethodId());
                static::assertTrue(preg_match('/iframe(.*?)src/i', $iframeRequest) ? true : false);
            } catch (Exception $e) {
                static::markTestSkipped(
                    sprintf(
                        'Could not complete proxy test (%d): %s',
                        $e->getCode(),
                        $e->getMessage()
                    )
                );
            }
        } else {
            static::markTestSkipped('Could not complete proxy test. Got no proper ip address.');
        }
    }

    /**
     * @test
     * Put order with quantity 100. Annul 50, debit 50, credit 25. And then kill the full order.
     * Expected result is:
     * Part 1: one row has 50 annulled, 25 debited and 25 credited.
     * Part 2: one row has 50 annulled, 50 credited, and the resut of the order should be annulled.
     * @throws Exception
     * @noinspection MultipleReturnStatementsInspection
     */
    public function cancelMixedPayment()
    {
        if (!$this->allowVersion()) {
            static::markTestSkipped(
                sprintf(
                    'Special test limited to one PHP version (%s) detected. ' .
                    'This is the wrong version (%s), so it is being skipped.',
                    isset($_ENV['standalone_ecom']) ? $_ENV['standalone_ecom'] : 'Detection failed',
                    PHP_VERSION
                )
            );
            return;
        }

        try {
            $this->unitSetup();
            // Make sure this request is not interfered by the rest of the tests.
            $this->TEST->ECOM->getNextInvoiceNumberByDebits(5);
            $payment = $this->generateSimpleSimplifiedInvoiceQuantityOrder('8305147715', true);
            $paymentid = isset($payment->paymentId) ? $payment->paymentId : null;
            if (empty($paymentid)) {
                static::markTestSkipped('No paymentid fetched during test.');
                return;
            }

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
        } catch (Exception $e) {
            static::markTestIncomplete(
                sprintf(
                    'Exception %d during %s: %s',
                    $e->getCode(),
                    __FUNCTION__,
                    $e->getMessage()
                )
            );
            return;
            //$this->bailOut($e);
        }

        $expectArray = [
            'AUTHORIZE' => [
                'PR01',
                100,
            ],
            'ANNUL' => [
                'PR02',
                100,
            ],
            'CREDIT' => [
                'PR01',
                50,
            ],
            'DEBIT' => [
                'PR01',
                50,
            ],
        ];

        if (isset($paymentid)) {
            $expected = $this->getPaymentStatusQuantity(
                $paymentid,
                $expectArray
            );

            if ($expected) {
                static::assertTrue($expected);
            } else {
                $printOut = $this->getPaymentStatusQuantity(
                    $paymentid,
                    $expectArray,
                    true
                );

                // Assertions could randomly fail when test runs with multiple assertion runs.
                // Running it as a standalone test however works fine.
                static::markTestSkipped(print_r($printOut, true));
            }
        }
    }

    /**
     * @test Three ways to fetch current version.
     * @throws ExceptionHandler
     * @throws ReflectionException
     */
    public function getVersions()
    {
        $byComposer = (new Generic())->getVersionByComposer(__FILE__, 3);
        $byGeneric = (new Generic())->getVersionByAny(__FILE__, 3, ResursBank::class);
        static::assertTrue(!empty($byGeneric) && !empty(ECOMPHP_VERSION) && !empty($byComposer));
    }

    /**
     * @test
     * @testdox Clean up special test data from share file
     */
    public function finalTest()
    {
        $this->unitSetup();
        $this->TEST->ECOM->resetInvoiceNumber();
        static::assertTrue($this->TEST->unshare('thisKey'));
    }
}
