<?php

/**
 * Resursbank API Loader Tests
 *
 * @package EcomPHPTest
 * @author  Resurs Bank Ecommrece <ecommerce.support@resurs.se>
 * @version 0.18
 * @link    https://test.resurs.com/docs/x/KYM0 Get started - PHP Section
 * @license -
 *
 */

if (file_exists(__DIR__ . "/../vendor/autoload.php")) {
    require_once(__DIR__ . '/../vendor/autoload.php');
} else {
    require_once('../source/classes/rbapiloader.php');
}

use PHPUnit\Framework\TestCase;
use \Resursbank\RBEcomPHP\ResursBank;
use \Resursbank\RBEcomPHP\RESURS_CALLBACK_TYPES;
use \Resursbank\RBEcomPHP\RESURS_PAYMENT_STATUS_RETURNCODES;
use \Resursbank\RBEcomPHP\RESURS_FLOW_TYPES;
use \Resursbank\RBEcomPHP\RESURS_CALLBACK_REACHABILITY;
use \Resursbank\RBEcomPHP\RESURS_AFTERSHOP_RENDER_TYPES;
use \Resursbank\RBEcomPHP\RESURS_ENVIRONMENTS;

// Split library section - Set up the correct curl- and network pointers here depending on release version
use \TorneLIB\MODULE_CURL;
use \TorneLIB\MODULE_NETWORK;

///// ADD ALWAYS SECTION

// Automatically set to test the pushCustomerUserAgent
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = "EComPHP/Test-InternalClient";
}

if (file_exists("/etc/ecomphp.json")) {
    $ecomExt = @json_decode(@file_get_contents("/etc/ecomphp.json"));
    if (isset($ecomExt->skip)) {
        define('SKIP_TEST', $ecomExt->skip);
    }
}

/**
 * Class ecomDeprecatedManual
 *
 * Resurs Bank API Gateway, PHPUnit Test Client
 */
class ecomDeprecatedManual extends TestCase
{
    private $failUrl;
    private $successUrl;

    /**
     * @throws Exception
     */
    public function setUp()
    {
        $this->globalInitialize();
        ecom_event_reset();
    }

    public function tearDown()
    {
        ecom_event_reset();
    }

    /**
     * @param string $skipKey
     * @return bool
     */
    private function isSkip($skipKey = '')
    {
        if (defined('SKIP_TEST')) {
            if (!empty($skipKey) && in_array($skipKey, SKIP_TEST)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Plugin initializer (global as there are functions in the units that re-initializes the module)
     *
     * @throws \Exception
     */
    private function globalInitialize()
    {
        $this->setupConfig();

        $this->CURL = new MODULE_CURL();
        $this->CURL->setFlag("SOAPCHAIN", true);
        //$this->CURL->setChain(false);   // Unchain the module for backward

        $this->NETWORK = new MODULE_NETWORK();
        if (version_compare(PHP_VERSION, '5.3.0', "<")) {
            if (!$this->allowObsoletePHP) {
                throw new \Exception("PHP 5.3 or later are required for this module to work. If you feel safe with running this with an older version, please see ");
            }
        }
        register_shutdown_function([$this, 'shutdownSuite']);
        // Set up default government id for bookings
        $this->testGovId = $this->govIdNatural;

        // If HTTP_HOST is not set, Resurs Checkout will not run properly, since the iFrame requires a valid internet connection (actually browser vs http server).
        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = "localhost";
        }
        if (empty($overrideUsername)) {
            $this->rb = new ResursBank($this->username, $this->password);
        } else {
            //$this->rb = new ResursBank( $overrideUsername, $overridePassword );
            throw new \Exception("User- and pass overriders are deprecated", 500);
        }
        $this->rb->setSoapChain();
        $this->rb->setPushCustomerUserAgent(true);
        $this->rb->setUserAgent("EComPHP/TestSuite");
        $this->rb->setDebug();
    }

    ////////// Public variables
    public $ignoreDefaultTests = false;
    public $ignoreBookingTests = false;
    public $ignoreSEKKItests = false;
    public $ignoreUrlExternalValidation = false;
    /**
     * Expected payment method count (SE)
     *
     * @var array
     * @deprecated 1.1.12
     */
    private $paymentMethodCount = [
        'mock' => 5,
    ];
    private $paymentIdAuthed = "20170519125223-9587503794";

    /** @var $NETWORK MODULE_NETWORK */
    private $NETWORK;

    /** @var $CURL MODULE_CURL */
    private $CURL;

    /** @noinspection PhpUnusedPrivateMethodInspection */
    /**
     * @return bool
     * @throws \Exception
     */
    private function isSpecialAccount()
    {
        $authed = $this->rb->getPayment($this->paymentIdAuthed);
        if (isset($authed->id)) {
            return true;
        }

        return false;
    }

    /**
     * Reset the connection to simulate a true scenario
     *
     * @return bool
     * @throws \Exception
     */
    private function resetConnection()
    {
        $isEmpty = false;
        $this->setUp();
        try {
            $this->rb->getPayload();
        } catch (\Exception $emptyPayloadException) {
            $isEmpty = true;
        }

        return $isEmpty;
    }

    ////////// Private variables

    /** @var string Defines what environment should be running */
    private $environmentName = "mock";
    /** @var ResursBank API Connector */
    private $rb = null;
    /** @var string Username to web services */
    private $username = "ecomphpPipelineTest";
    /** @var string Password to web services */
    private $password = "4Em4r5ZQ98x3891D6C19L96TQ72HsisD";
    /** @var string Used as callback urls */
    private $callbackUrl = "";
    /** @var string Where to redirect signings, when done */
    private $signUrl = "";
    /** @var string Default username for tests (SE) */
    private $usernameSweden = "ecomphpPipelineTest";
    /** @var string Default password for tests (SE) */
    private $passwordSweden = "4Em4r5ZQ98x3891D6C19L96TQ72HsisD";
    /** @var string Selected government id */
    private $testGovId = "";
    /** @var string Test with natural government id */
    private $govIdNatural = "198305147715";
    /** @var string Government id that will fail */
    private $govIdNaturalDenied = "195012026430";
    /** @var string Test with civic number (legal) */
    private $govIdLegalCivic = "198305147715";
    /** @var string getAddress should receive this full name when using LEGAL */
    private $govIdLegalFullname = "Pilsnerbolaget HB";
    /** @var string Used for testing card-bookings  (9000 0000 0002 5000 = 25000) */
    private $cardNumber = "9000000000025000";
    /** @var string Government id for the card */
    private $cardGovId = "194608282333";
    /** @var string Test with organization number (legal) */
    private $govIdLegalOrg = "166997368573";
    /** @var array Available methods for test (SE) */
    private $availableMethods = [];
    /** @var bool Wait for fraud control to take place in a booking */
    private $waitForFraudControl = false;

    private $zeroSpecLine = false;
    private $zeroSpecLineZeroTax = false;
    private $allowObsoletePHP = false;

    private function setupConfig()
    {
        if (file_exists(__DIR__ . '/test.json')) {
            $config = json_decode(file_get_contents(__DIR__ . '/test.json'));
            if (isset($config->mock->username)) {
                $this->username = $config->mock->username;
                $this->usernameSweden = $this->username;
            }
            if (isset($config->mock->password)) {
                $this->password = $config->mock->password;
                $this->passwordSweden = $this->password;
            }
            if (isset($config->sweden->username)) {
                $this->username = $config->sweden->username;
                $this->usernameSweden = $this->username;
            }
            if (isset($config->sweden->password)) {
                $this->password = $config->sweden->password;
                $this->passwordSweden = $this->password;
            }
            if (isset($config->availableMethods)) {
                foreach ($config->availableMethods as $methodId => $methodObject) {
                    $this->availableMethods[$methodId] = $methodObject;
                }
            }
            if (isset($config->callbackUrl)) {
                $this->callbackUrl = $config->callbackUrl;
            }
            if (isset($config->signUrl)) {
                $this->signUrl = $config->signUrl;
            }
            if (isset($config->successUrl)) {
                $this->successUrl = $config->successUrl;
            }
            if (isset($config->failUrl)) {
                $this->failUrl = $config->failUrl;
            }
        }
    }

    /**
     * Randomize (not hash) code
     *
     * @return null|string A standard nonComplex string
     */
    private function mkpass()
    {
        $retp = null;
        $characterListArray = [
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '0123456789',
        ];
        //'!@#$%*?'
        $chars = [];
        $max = 10; // This is for now static
        foreach ($characterListArray as $charListIndex => $charList) {
            for ($i = 0; $i <= ceil($max / sizeof($characterListArray)); $i++) {
                $chars[] = $charList{mt_rand(0, (strlen($charList) - 1))};
            }
        }
        shuffle($chars);
        $retp = implode("", $chars);

        return $retp;
    }

    /**
     * Randomly pick up a payment method (name) from current representative.
     *
     * @return mixed
     * @throws \Exception
     */
    private function getAMethod()
    {
        $methods = null;
        $currentError = null;
        try {
            $methods = $this->rb->getPaymentMethods();
        } catch (\Exception $e) {
            $currentError = $e->getMessage();
        }
        if (is_array($methods)) {
            $method = array_pop($methods);
            $id = $method->id;

            return $id;
        }
        throw new \Exception("Cannot receive a random payment method from ecommerce" . (!empty($currentError) ? " ($currentError)" : ""));
    }

    /**
     * Book a payment, internal function
     *
     * @param string $setMethod The payment method to use
     * @param bool|false $bookSuccess Set to true if booking is supposed to success
     * @param bool|false $forceSigning Set to true if signing is forced
     * @param bool|true $signSuccess True=Successful signing, False=Failed signing
     *
     * @return array|bool
     * @throws Exception
     */
    private function doBookPayment(
        $setMethod = '',
        $bookSuccess = true,
        $forceSigning = false,
        $signSuccess = true
    ) {
        $paymentServiceSet = $this->rb->getPreferredPaymentFlowService();
        $useMethodList = $this->availableMethods;
        $useGovIdLegalCivic = $this->govIdLegalCivic;
        $useGovId = $this->testGovId;
        $usePhoneNumber = "0101010101";
        $bookStatus = null;

        $bookId = null;

        if (!count($this->availableMethods) || empty($this->username)) {
            static::markTestSkipped('No payment methods are available');
        }
        if ($this->zeroSpecLine) {
            if (!$this->zeroSpecLineZeroTax) {
                $bookData['specLine'] = $this->getSpecLineZero();
            } else {
                $bookData['specLine'] = $this->getSpecLineZero([], true);
            }
        } else {
            $bookData['specLine'] = $this->getSpecLine();
        }
        $this->zeroSpecLine = false;
        $bookData['address'] = [
            'fullName' => 'Test Testsson',
            'firstName' => 'Test',
            'lastName' => 'Testsson',
            'addressRow1' => 'Testgatan 1',
            'postalArea' => 'Testort',
            'postalCode' => '12121',
            'country' => 'SE',
        ];
        $bookData['customer'] = [
            'governmentId' => $useGovId,
            'phone' => $usePhoneNumber,
            'email' => 'noreply@resurs.se',
            'type' => 'NATURAL',
        ];
        if (isset($useMethodList['invoice_legal']) && $setMethod == $useMethodList['invoice_legal']) {
            $bookData['customer']['contactGovernmentId'] = $useGovIdLegalCivic;
            $bookData['customer']['type'] = 'LEGAL';
        }
        if (isset($useMethodList['card']) && $setMethod == $useMethodList['card']) {
            $useGovId = $this->cardGovId;
            $this->rb->setCardData($this->cardNumber);
        }
        if (isset($useMethodList['card_new']) && $setMethod == $useMethodList['card_new']) {
            $useGovId = $this->cardGovId;
            $this->rb->setCardData();
        }
        $bookData['paymentData']['waitForFraudControl'] = $this->waitForFraudControl;
        $bookData['signing'] = [
            'successUrl' => $this->signUrl . '&success=true&preferredService=' . $this->rb->getPreferredPaymentFlowService(),
            'failUrl' => $this->signUrl . '&success=false&preferredService=' . $this->rb->getPreferredPaymentFlowService(),
            'backUrl' => $this->signUrl . '&success=backurl&preferredService=' . $this->rb->getPreferredPaymentFlowService(),
            'forceSigning' => $forceSigning,
        ];

        $this->rb->setMetaData('metaKeyTestTime', time());
        $this->rb->setMetaData('metaKeyTestMicroTime', microtime(true));

        if ($paymentServiceSet !== RESURS_FLOW_TYPES::RESURS_CHECKOUT) {
            $res = $this->rb->createPayment($setMethod, $bookData);
            if ($paymentServiceSet == RESURS_FLOW_TYPES::HOSTED_FLOW) {
                $domainInfo = $this->NETWORK->getUrlDomain($res);
                if (preg_match("/^http/i", $domainInfo[1])) {
                    $hostedContent = $this->CURL->getBody($this->CURL->doGet($res));

                    return $hostedContent;
                }
            }
        } else {
            $bookId = $this->rb->getPreferredPaymentId();
            $res = $this->rb->createPayment($bookId, $bookData);
        }

        /*
		 * bookPaymentStatus is for simplified flows only
		 */
        if (isset($res->bookPaymentStatus)) {
            $bookStatus = $res->bookPaymentStatus;
        }

        if ($paymentServiceSet == RESURS_FLOW_TYPES::RESURS_CHECKOUT) {
            return $res;
        }

        if ($bookStatus == 'SIGNING') {
            $bookId = $res->paymentId;
            /* Pick up the signing url */
            /** @noinspection PhpUndefinedFieldInspection */
            $signUrl = $res->signingUrl;
            // Authentication is different to signing
            if (preg_match("/authenticate/i", $signUrl)) {
                $authenticate = $signUrl . "&govId=" . $useGovId;
                $authenticate = preg_replace("/authenticate/i", 'doAuth', $authenticate);
                $authenticateRequest = $this->CURL->doGet($authenticate);
                $getSuccessContent = $this->CURL->getParsed($authenticateRequest);
            } else {
                $getSigningPage = file_get_contents($signUrl);
                $NETWORK = new MODULE_NETWORK();
                $signUrlHostInfo = $NETWORK->getUrlDomain($signUrl, true);
                $getUrlHost = $signUrlHostInfo[1] . "://" . $signUrlHostInfo[0];
                $hostUri = explode("/", isset($signUrlHostInfo[2]) ? $signUrlHostInfo[2] : null);
                $uriPath = "";
                if (is_array($hostUri) && count($hostUri) > 1) {
                    array_shift($hostUri);
                    if (count($hostUri) >= 2) {
                        array_pop($hostUri);
                        $uriPath = implode("/", $hostUri);
                    }
                }
                $mockSuccessUrl = preg_replace("/\/$/", '',
                    $getUrlHost . "/" . $uriPath . "/" . preg_replace('/(.*?)\<a href=\"(.*?)\">(.*?)\>Mock success(.*)/is',
                        '$2', $getSigningPage));
                // Split up in case of test requirements
                $getPostCurlObject = $this->CURL->doPost($mockSuccessUrl);
                $getSuccessContent = $this->CURL->getParsed($getPostCurlObject);
            }
            if (isset($getSuccessContent->_GET->success)) {

                // Make sure the test books the signed payment
                $bookRes = $this->rb->bookSignedPayment($bookId);

                /** @noinspection PhpUndefinedFieldInspection */
                if ($getSuccessContent->_GET->success == "true" && (int)$bookRes->approvedAmount > 0) {
                    if ($signSuccess) {
                        return true;
                    } else {
                        return false;
                    }
                }
                /** @noinspection PhpUndefinedFieldInspection */
                if ($getSuccessContent->_GET->success == "false") {
                    if (!$signSuccess) {
                        return true;
                    } else {
                        return false;
                    }
                }
            } else {
                static::markTestSkipped("\$getSuccessContent does not contain any success-object.");

                return false;
            }
        } elseif ($bookStatus == "FROZEN") {
            return true;
        } elseif ($bookStatus == "BOOKED") {
            return true;
        } elseif ($bookStatus == "DENIED") {
            if ($bookSuccess) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }

    /*********** PUBLICS ***********
     *
     * @param array $specialSpecline
     *
     * @return array
     */

    private function getSpecLine($specialSpecline = [])
    {
        if (count($specialSpecline)) {
            return $specialSpecline;
        }

        return [
            'artNo' => 'EcomPHP-testArticle-' . rand(1, 1024),
            'description' => 'EComPHP Random Test Article number ' . rand(1, 1024),
            'quantity' => 1,
            'unitAmountWithoutVat' => intval(rand(1000, 10000)),
            'vatPct' => 25,
        ];
    }

    private function getSpecLineZero($specialSpecline = [], $zeroTax = false)
    {
        if (count($specialSpecline)) {
            return $specialSpecline;
        }

        return [
            'artNo' => 'EcomPHP-testArticle-' . rand(1, 1024),
            'description' => 'EComPHP Random Test Article number ' . rand(1, 1024),
            'quantity' => 1,
            'unitAmountWithoutVat' => 0,
            'vatPct' => $zeroTax ? 0 : 25,
        ];
    }

    /**
     * Allow older/obsolete PHP Versions (Follows the obsolete php versions rules - see the link for more information).
     * This check is clonsed from the rbapiloader.php to follow standards and prevent tests in older php versions.
     *
     * @param bool $activate
     *
     * @link https://test.resurs.com/docs/x/TYNM#ECommercePHPLibrary-ObsoletePHPversions
     */
    public function setObsoletePhp($activate = false)
    {
        $this->allowObsoletePHP = $activate;
    }

    /**
     * When suite is about to shut down, run a collection of functions before completion.
     */
    public function shutdownSuite()
    {
    }


    /*********** TESTS ************/

    /**
     * Test if environment is ok
     */
    public function testGetEnvironment()
    {
        if ($this->ignoreDefaultTests) {
            static::markTestSkipped();
        }

        try {
            $paymentMethods = $this->rb->getPaymentMethods();
            static::assertTrue(count($paymentMethods) > 0);
        } catch (\Exception $e) {
        }
    }

    /**
     * Test if payment methods works properly
     *
     * @throws \Exception
     */
    public function testGetPaymentMethods()
    {
        if ($this->ignoreDefaultTests) {
            static::markTestSkipped();
        }
        $paymentMethods = $this->rb->getPaymentMethods();
        static::assertTrue(count($paymentMethods) > 0);
    }

    /**
     * @throws \Exception
     */
    public function testGetPaymentMethodsFail()
    {
        $paymentMethods = [];
        if ($this->ignoreDefaultTests) {
            static::markTestSkipped();
        }
        $fail = new ResursBank("fail", "fail");
        try {
            $paymentMethods = $fail->getPaymentMethods();
        } catch (\Exception $e) {
            $prevErr = $e->getPrevious();
            print_r($prevErr);
        }
        static::assertFalse(count($paymentMethods) > 0);
    }

    /**
     * Make sure that all payment methods set up for the representative is there
     *
     * @throws \Exception
     */
    public function testGetPaymentMethodsAll()
    {
        if ($this->ignoreDefaultTests) {
            static::markTestSkipped();
        }
        $paymentMethods = $this->rb->getPaymentMethods();
        /** @noinspection PhpDeprecationInspection */
        static::assertTrue(count($paymentMethods) === $this->paymentMethodCount[$this->environmentName]);
    }


    //// INVOICE SEQUENCE TESTS MUST BE SET FIRST IN THIS TEST FLOW

    /**
     * @throws \Exception
     */
    public function testInvoiceSequenceReset()
    {
        try {
            $this->rb->resetInvoiceNumber();
            $currentInvoiceNumber = $this->rb->getNextInvoiceNumber();
            static::assertTrue($currentInvoiceNumber == 1);
            // Restore invoice sequence to the latest correct so new tests can be initated without problems.
            $this->rb->getNextInvoiceNumberByDebits(5);
        } catch (\Exception $e) {
            static::markTestSkipped("Got unexpected exception from " . __FUNCTION__ . ": " . $e->getCode());
        }
    }

    // testInvoiceSequenceAndFinalize: Function to test invoice sequence and the finalization is no longer necessary as the functions are self healing

    /**
     * @throws \Exception
     */
    function testInvoiceSequenceFindByFind()
    {
        $lastInvoiceNumber = $this->rb->getNextInvoiceNumberByDebits(5);
        static::assertTrue($lastInvoiceNumber > 0);
    }


    /**
     * getAddress, NATURAL
     */
    public function testGetAddressNatural()
    {
        if ($this->ignoreDefaultTests) {
            static::markTestSkipped();
        }
        $getAddressData = [];
        try {
            $getAddressData = $this->rb->getAddress($this->govIdNatural, 'NATURAL', '127.0.0.1');
        } catch (\Exception $e) {
        }
        static::assertTrue(!empty($getAddressData->fullName));
    }

    public function testGetAddressMockIP()
    {
        if ($this->ignoreDefaultTests) {
            static::markTestSkipped();
        }
        $getAddressData = [];
        try {
            $getAddressData = $this->rb->getAddress($this->govIdNatural, 'NATURAL', 'blablabla');
        } catch (\Exception $e) {
        }
        static::assertTrue(!empty($getAddressData->fullName));
    }

    /**
     * getAddress, LEGAL, Civic number
     */
    public function testGetAddressLegalCivic()
    {
        if ($this->ignoreDefaultTests) {
            static::markTestSkipped();
        }
        $getAddressData = [];
        try {
            $getAddressData = $this->rb->getAddress($this->govIdLegalCivic, 'LEGAL', '127.0.0.1');
        } catch (\Exception $e) {
        }
        static::assertTrue(!empty($getAddressData->fullName));
    }

    /**
     * getAddress, LEGAL, Organization number
     */
    public function testGetAddressLegalOrg()
    {
        if ($this->ignoreDefaultTests) {
            static::markTestSkipped();
        }
        $getAddressData = [];
        try {
            $getAddressData = $this->rb->getAddress($this->govIdLegalOrg, 'LEGAL', '127.0.0.1');
        } catch (\Exception $e) {
        }
        static::assertTrue(!empty($getAddressData->fullName));
    }

    /**
     * Testing of annuity factors (if they exist), with the first found payment method
     *
     * @throws \Exception
     * @throws \Exception
     */
    public function testGetAnnuityFactors()
    {
        if ($this->ignoreDefaultTests) {
            static::markTestSkipped();
        }
        $annuity = false;
        $methods = $this->rb->getPaymentMethods();
        if (is_array($methods)) {
            $method = array_pop($methods);
            $id = $method->id;
            $annuity = $this->rb->getAnnuityFactors($id);
        }
        static::assertTrue(count($annuity) > 1);
    }

    /**
     * Test booking.
     * Payment Method: Invoice
     * Customer Type: NATURAL, GRANTED
     *
     * @throws \Exception
     */
    public function testBookSimplifiedPaymentInvoiceNatural()
    {
        if ($this->ignoreBookingTests) {
            static::markTestSkipped();
        }
        $bookResult = $this->doBookPayment($this->availableMethods['invoice_natural'], true, false, true);
        static::assertTrue($bookResult);
    }

    /**
     * Test booking and always use extendedCustomer.
     * Payment Method: Invoice
     * Customer Type: NATURAL, GRANTED
     *
     * @deprecated No longer in effect as extended customer is always in use
     * @throws \Exception
     */
    public function testBookPaymentInvoiceHostedNatural()
    {
        if ($this->ignoreBookingTests) {
            static::markTestSkipped();
        }
        $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::HOSTED_FLOW);
        $bookResult = $this->doBookPayment($this->availableMethods['invoice_natural'], true, false, true);
        // Can't do bookings yet, since this is a forwarder. We would like to emulate browser clicking here, to complete the order.
        static::assertTrue(strlen($bookResult) > 1024);
    }

    /**
     * Test findPayments()
     *
     * @throws \Exception
     */
    public function testFindPayments()
    {
        $paymentList = $this->rb->findPayments();
        static::assertGreaterThan(0, count($paymentList));
    }

    /**
     * Book and see if there is a payment registered at Resurs Bank
     *
     * @throws \Exception
     * @throws \Exception
     */
    public function testGetPayment()
    {
        if ($this->ignoreBookingTests) {
            static::markTestSkipped();
        }
        $paymentList = $this->rb->findPayments();
        if (is_array($paymentList) && count($paymentList)) {
            $existingPayment = array_pop($paymentList);
            $existingPayment->paymentId;
            $payment = $this->rb->getPayment($existingPayment->paymentId);
            static::assertTrue($payment->id == $existingPayment->paymentId);
        } else {
            static::markTestSkipped("No payments available to run with getPayment()");
        }
    }

    public function testGetPaymentFail()
    {
        try {
            $this->rb->getPayment("FAIL_HERE");
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() == 3 || $e->getCode() == 8 || $e->getCode() == 404);
        }
    }

    public function testGetPaymentFailBySoap()
    {
        try {
            $this->rb->setFlag('GET_PAYMENT_BY_SOAP');
            $this->rb->getPayment("FAIL_HERE");
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() === 8);
        }
    }

    /**
     * Book and see if there is a payment registered at Resurs Bank
     */
    public function testGetPaymentInvoices()
    {
        if ($this->ignoreBookingTests) {
            static::markTestSkipped();
        }
        try {
            $invoicesArray = $this->rb->getPaymentInvoices("20170802114006-2638609880");
        } catch (\Exception $e) {
            static::markTestSkipped("This test requires an order that contains one or more debits (it's a very special test) and the payment used to test this does not seem to exist here.");

            return;
        }
        //$invoicesArray = $this->rb->getPaymentInvoices("20170802112051-7018398597");
        $hasInvoices = false;
        if (count($invoicesArray) > 0) {
            $hasInvoices = true;
        }
        if ($hasInvoices) {
            static::assertTrue($hasInvoices);
        } else {
            static::markTestSkipped("No debits available in current test");
        }
    }

    /**
     * @param null $paymentId
     * @param bool $randomize
     *
     * @return array|mixed|null
     * @throws Exception
     */
    private function getAPayment($paymentId = null, $randomize = false)
    {
        $paymentList = $this->rb->findPayments([], 1, 100);
        if (is_null($paymentId)) {
            if (is_array($paymentList) && count($paymentList)) {
                if (!$randomize) {
                    $existingPayment = array_pop($paymentList);
                    $paymentId = $existingPayment->paymentId;
                } else {
                    $paymentIdIndex = rand(0, count($paymentList));
                    if (isset($paymentList[$paymentIdIndex])) {
                        $paymentId = $paymentList[$paymentIdIndex]->paymentId;
                    }
                }
            }
        }

        return $this->rb->getPayment($paymentId);
    }

    /*
	 * Test booking with zero amount
	 * Expected result: Fail.
	 */
    public function testBookPaymentZeroInvoiceNatural()
    {
        if ($this->ignoreBookingTests) {
            static::markTestSkipped();
        }
        $this->zeroSpecLine = true;
        $hasException = false;
        try {
            $this->doBookPayment($this->availableMethods['invoice_natural'], true, false, true);
        } catch (\Exception $exceptionWanted) {
            $hasException = true;
        }
        static::assertTrue($hasException);
    }

    /**
     * Test booking.
     * Payment Method: Invoice
     * Customer Type: NATURAL, DENIED
     *
     * @throws \Exception
     */
    public function testBookPaymentInvoiceNaturalDenied()
    {
        if ($this->ignoreBookingTests) {
            static::markTestSkipped();
        }
        $this->testGovId = $this->govIdNaturalDenied;
        $bookResult = $this->doBookPayment($this->availableMethods['invoice_natural'], false, false, true);
        static::assertTrue($bookResult);
    }

    /**
     * Test booking
     * Payment Method: Invoice
     * Customer Type: NATURAL, DENIED
     *
     * @throws \Exception
     */
    public function testBookPaymentInvoiceLegal()
    {
        if ($this->ignoreBookingTests) {
            static::markTestSkipped();
        }
        $this->username = $this->usernameSweden;
        $this->password = $this->passwordSweden;
        $this->testGovId = $this->govIdLegalOrg;
        $bookResult = $this->doBookPayment($this->availableMethods['invoice_legal'], false, false, true);
        static::assertTrue($bookResult);
    }

    /**
     * Test booking with a card
     *
     * @throws \Exception
     */
    public function testBookPaymentCard()
    {
        if ($this->ignoreBookingTests) {
            static::markTestSkipped();
        }
        $bookResult = $this->doBookPayment($this->availableMethods['card'], true, false, true);
        static::assertTrue($bookResult === true);
    }

    /**
     * Test booking with new card
     *
     * @throws \Exception
     */
    public function testBookPaymentNewCard()
    {
        if ($this->ignoreBookingTests) {
            static::markTestSkipped();
        }
        $bookResult = $this->doBookPayment($this->availableMethods['card_new'], true, false, true);
        static::assertTrue($bookResult === true);
    }

    /**
     * Test chosen payment method sekki-generator
     *
     * @throws \Exception
     */
    public function testSekkiSimple()
    {
        if ($this->ignoreSEKKItests) {
            static::markTestSkipped();
        }
        $methodSimple = $this->getAMethod();
        $amount = rand(1000, 10000);
        $sekkiUrls = $this->rb->getSekkiUrls($amount, $methodSimple);
        $matches = 0;
        $appenders = 0;
        if (is_array($sekkiUrls)) {
            foreach ($sekkiUrls as $UrlData) {
                if ($UrlData->appendPriceLast) {
                    $appenders++;
                    if (preg_match("/amount=$amount/i", $UrlData->url)) {
                        $matches++;
                    }
                }
            }
        }
        static::assertTrue($matches === $appenders);
    }

    /**
     * Test pre-fetched sekki-url-generator
     *
     * @throws \Exception
     */
    public function testSekkiArray()
    {
        if ($this->ignoreSEKKItests) {
            static::markTestSkipped();
        }
        $methodSimple = $this->getAMethod();
        $amount = rand(1000, 10000);
        $preparedMethod = $this->rb->getPaymentMethodSpecific($methodSimple);
        if (isset($preparedMethod->legalInfoLinks)) {
            $sekkiUrls = $this->rb->getSekkiUrls($amount, $preparedMethod->legalInfoLinks);
            $matches = 0;
            $appenders = 0;
            if (is_array($sekkiUrls)) {
                foreach ($sekkiUrls as $UrlData) {
                    if ($UrlData->appendPriceLast) {
                        $appenders++;
                        if (preg_match("/amount=$amount/i", $UrlData->url)) {
                            $matches++;
                        }
                    }
                }
            }
            static::assertTrue($matches === $appenders);
        }
    }

    /**
     * Test all payment methods
     *
     * @throws \Exception
     */
    public function testSekkiAll()
    {
        $matches = 0;
        $appenders = 0;
        if ($this->ignoreSEKKItests) {
            static::markTestSkipped();
        }
        $amount = rand(1000, 10000);
        $sekkiUrls = $this->rb->getSekkiUrls($amount);
        foreach ($sekkiUrls as $method => $sekkiUrlsVal) {
            $matches = 0;
            $appenders = 0;
            if (is_array($sekkiUrlsVal)) {
                foreach ($sekkiUrlsVal as $UrlData) {
                    if ($UrlData->appendPriceLast) {
                        $appenders++;
                        if (preg_match("/amount=$amount/i", $UrlData->url)) {
                            $matches++;
                        }
                    }
                }
            }
        }
        static::assertTrue($matches === $appenders);
    }

    /**
     * Test curstom url
     *
     * @throws \Exception
     */
    public function testSekkiCustom()
    {
        if ($this->ignoreSEKKItests) {
            static::markTestSkipped();
        }
        $amount = rand(1000, 10000);
        $URL = "https://test.resurs.com/customurl/index.html?content=true&secondparameter=true";
        $customURL = $this->rb->getSekkiUrls($amount, null, $URL);
        static::assertTrue((preg_match("/amount=$amount/i", $customURL) ? true : false));
    }

    /** @var string $framePaymentId */
    //private $framePaymentId;

    /**
     * This test is incomplete.
     *
     * @param bool $returnTheFrame
     * @param bool $returnPaymentReference
     *
     * @return bool|null
     * @throws \Exception
     */
    private function getCheckoutFrame($returnTheFrame = false, $returnPaymentReference = false)
    {
        $assumeThis = false;
        $iFrameUrl = null;
        if ($returnTheFrame) {
            $iFrameUrl = false;
        }
        if ($this->ignoreBookingTests) {
            static::markTestSkipped();
        }
        $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $newReferenceId = $this->rb->getPreferredPaymentId();
        $bookResult = $this->doBookPayment($newReferenceId, true, false, true);
        if (is_string($bookResult) && preg_match("/iframe src/i", $bookResult)) {
            $iframeUrls = $this->NETWORK->getUrlsFromHtml($bookResult, 0, 1);
            $iFrameUrl = array_pop($iframeUrls);
            $this->CURL->doGet($iFrameUrl);
            $iframeBody = $this->CURL->getBody();
            if (!empty($iframeBody)) {
                $assumeThis = true;
            }
        }
        //$this->framePaymentId = $newReferenceId;
        if ($returnPaymentReference) {
            return $this->rb->getPreferredPaymentId();
        }
        if (!$returnTheFrame) {
            return $assumeThis;
        } else {
            return $iFrameUrl;
        }
    }

    //private function getCheckoutFrameId() {}

    /**
     * @test
     * @testdox Try to fetch the iframe (Resurs Checkout). When the iframe url has been received, check if there's
     *          content.
     *
     * @throws \Exception
     */
    public function testGetIFrame()
    {
        $getFrameUrl = null;
        try {
            $getFrameUrl = $this->getCheckoutFrame(true);
        } catch (\Exception $e) {
            static::markTestSkipped("getCheckoutFrameException: " . $e->getMessage());
        }
        //$SessionID = $this->rb->getPaymentSessionId();
        $UrlDomain = $this->NETWORK->getUrlDomain($getFrameUrl);
        // If there is no https defined in the frameUrl, the test might have failed
        if ($UrlDomain[1] == "https") {
            $this->CURL->doGet($getFrameUrl);
            static::assertTrue($this->CURL->getCode() == 200 && strlen($this->CURL->getBody()) > 1024);
        }
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testCheckoutAsFromDocs()
    {
        $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $iframePaymentReference = $this->rb->getPreferredPaymentId(30, "CREATE-");
        $this->rb->addOrderLine(
            "HORSE",
            "Stallponny",
            4800,
            25,
            "st",
            null,
            1
        );
        $this->rb->setShopUrl("https://my.iframe.shop.com/test", true);
        $this->rb->setCheckoutUrls("https://google.com/?q=signingSuccessful", "https://google.com/?q=signingFailed");
        $theFrame = $this->rb->createPayment($iframePaymentReference);
        $urls = $this->NETWORK->getUrlsFromHtml($theFrame);
        static::assertTrue(count($urls) == 2);
    }

    /**
     * Try to update a payment reference by first creating the iframe
     *
     * @throws \Exception
     * @throws \Exception
     * @throws \Exception
     */
    public function testUpdatePaymentReference()
    {
        $iframePaymentReference = $this->rb->getPreferredPaymentId(30, "CREATE-");
        $iFrameUrl = null;
        try {
            $iFrameUrl = $this->getCheckoutFrame(true);
        } catch (\Exception $e) {
            static::markTestSkipped("Exception: " . $e->getCode() . ": " . $e->getMessage());
        }

        $this->CURL->setAuthentication($this->username, $this->password);
        $this->CURL->setLocalCookies(true);
        $this->CURL->doGet($iFrameUrl);

        $payload = $this->rb->getPayload();
        $orderLines = ["orderLines" => $payload['orderLines']];

        $iframeContent = $this->CURL->getBody();;
        $Success = false;
        if (!empty($iframePaymentReference) && !empty($iFrameUrl) && !empty($iframeContent) && strlen($iframeContent) > 1024) {
            $newReference = $this->rb->getPreferredPaymentId(30, "UPDATE-", true, true);
            try {
                $Success = $this->rb->updatePaymentReference($iframePaymentReference, $newReference);
            } catch (\Exception $successException) {
                static::markTestSkipped("updatePaymentReferenceException: " . $successException->getCode() . ": " . $successException->getMessage());
            }
            try {
                // Currently, this test always gets a HTTP-200 from ecommerce, regardless of successful or failing updates.
                $updateCart = $this->rb->updateCheckoutOrderLines($newReference, $orderLines);
                static::assertTrue($updateCart);

                return;
            } catch (\Exception $e) {
                static::markTestSkipped("updateCheckoutOrderLinesException: " . $e->getCode() . ": " . $e->getMessage());
            }
        }
        static::assertTrue($Success === true);
    }

    /**
     * Test that fails when updatePaymentReference is successful and the old payment reference gets the cart update
     *
     * @throws \Exception
     * @throws \Exception
     * @throws \Exception
     */
    public function testUpdatePaymentReferenceFail()
    {
        $iframePaymentReference = $this->rb->getPreferredPaymentId(30, "CREATE-");
        $iFrameUrl = null;
        try {
            $iFrameUrl = $this->getCheckoutFrame(true);
        } catch (\Exception $e) {
            static::markTestSkipped("Exception: " . $e->getMessage());
        }
        $this->CURL->setAuthentication($this->username, $this->password);
        $this->CURL->setLocalCookies(true);
        $iframeRequest = $this->CURL->doGet($iFrameUrl);

        $payload = $this->rb->getPayload();
        $orderLines = ["orderLines" => $payload['orderLines']];

        $iframeContent = $this->CURL->getBody($iframeRequest);
        if (!empty($iframePaymentReference) && !empty($iFrameUrl) && !empty($iframeContent) && strlen($iframeContent) > 1024) {
            $newReference = $this->rb->getPreferredPaymentId(30, "UPDATE-", true, true);
            try {
                $this->rb->updatePaymentReference($iframePaymentReference, $newReference);
                $this->rb->updateCheckoutOrderLines($iframePaymentReference, $orderLines);
            } catch (\Exception $e) {
                static::assertTrue($e->getCode() >= 400);

                return;
            }
        }
        static::markTestSkipped(__FUNCTION__ . " failed.");
    }

    /**
     * @throws \Exception
     */
    public function testUpdateWrongPaymentReference()
    {
        $iframePaymentReference = $this->rb->getPreferredPaymentId(30, "CREATE-");
        $iFrameUrl = null;
        try {
            $iFrameUrl = $this->getCheckoutFrame(true);
        } catch (\Exception $e) {
            static::markTestSkipped("Exception: " . $e->getMessage());
        }
        $this->CURL->setAuthentication($this->username, $this->password);
        $this->CURL->setLocalCookies(true);
        $iframeRequest = $this->CURL->doGet($iFrameUrl);
        $iframeContent = $this->CURL->getBody($iframeRequest);
        if (!empty($iframePaymentReference) && !empty($iFrameUrl) && !empty($iframeContent) && strlen($iframeContent) > 1024) {
            $newReference = "#" . $this->rb->getPreferredPaymentId(30, "FAIL-", true, true);
            try {
                $this->rb->updatePaymentReference($iframePaymentReference, $newReference);
            } catch (\Exception $e) {
                static::assertTrue($e->getCode() >= 400);

                return;
            }
        }
        static::markTestSkipped(__FUNCTION__ . " failed.");
    }


    /**
     * Get all callbacks by a rest call (objects)
     *
     * @throws \Exception
     */
    public function testGetCallbackListByRest()
    {
        $cbr = $this->rb->getCallBacksByRest();
        // assert array instead of count, sa callback list can sometime be missing (and the goal is to find out that the array is properly shown)
        static::assertTrue(is_array($cbr));
    }

    /**
     * Get all callbacks by a rest call (objects) - broken
     *
     * @throws \Exception
     */
    public function testGetCallbackListByRestWithWrongCredentials()
    {
        $ownCall = new ResursBank("fail", "fail");
        try {
            $ownCall->getCallBacksByRest();
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() == "401");
        }
    }

    /**
     * Get all callbacks by a rest call (key-indexed array)
     *
     * @throws \Exception
     */
    public function testGetCallbackListAsArrayByRest()
    {
        // assert array instead of count, sa callback list can sometime be missing (and the goal is to find out that the array is properly shown)
        $cbr = $this->rb->getCallBacksByRest(true);
        static::assertTrue(is_array($cbr));
    }

    /**
     * Testing add metaData, adding random data to a payment
     *
     * @throws \Exception
     * @throws \Exception
     */
    public function testAddMetaData()
    {
        $paymentData = null;
        $chosenPayment = 0;
        $paymentId = null;

        $paymentList = $this->rb->findPayments();
        // For some reason, we not always get a valid order
        $preventLoop = 0;
        while (!isset($paymentList[$chosenPayment]) && $preventLoop++ < 10) {
            $chosenPayment = rand(0, count($paymentList));
        }

        if (isset($paymentList[$chosenPayment])) {
            $paymentData = $paymentList[$chosenPayment];
            $paymentId = $paymentData->paymentId;
            static::assertTrue($this->rb->addMetaData($paymentId, "RandomKey" . rand(1000, 1999),
                "RandomValue" . rand(2000, 3000)));
        } else {
            static::markTestSkipped("No valid payment found");
        }
    }

    /**
     * Testing add metaData, with a faulty payment id
     */
    public function testAddMetaDataFailure()
    {
        $paymentData = null;
        $hasException = false;
        try {
            $this->rb->addMetaData("UnexistentPaymentId", "RandomKey" . rand(1000, 1999),
                "RandomValue" . rand(2000, 3000));
        } catch (\Exception $e) {
            static::assertTrue(true);
            $hasException = true;
        }
        if (!$hasException) {
            static::markTestSkipped("addMetaDataFailure failed since it never got an exception");
        }
    }

    /**
     * Test getCostOfPurchase
     *
     * @throws \Exception
     * @throws \Exception
     */
    function testGetCostOfPurchase()
    {
        $PurchaseInfo = $this->rb->getCostOfPurchase($this->getAMethod(), 100);
        static::assertTrue(is_string($PurchaseInfo) && strlen($PurchaseInfo) >= 1024);
    }

    /***
     * VERSION 1.0-1.1 DEPENDENT TESTS
     */

    /**
     * Renders required data to pass to a callback registrator.
     *
     * @param bool $useUrlRewrite Register urls "nicely" with url_rewrite-like parameters
     *
     * @return array
     */
    private function renderCallbackData($useUrlRewrite = false)
    {
        $setCallbackType = null;
        $returnCallbackArray = [];
        $parameter = [
            'ANNULMENT' => ['paymentId'],
            'FINALIZATION' => ['paymentId'],
            'UNFREEZE' => ['paymentId'],
            'UPDATE' => ['paymentId'],
            'AUTOMATIC_FRAUD_CONTROL' => ['paymentId', 'result'],
        ];
        foreach ($parameter as $callbackType => $parameterArray) {
            $digestSaltString = $this->mkpass();
            $digestArray = [
                'digestSalt' => $digestSaltString,
                'digestParameters' => $parameterArray,
            ];
            if ($callbackType == "ANNULMENT") {
                $setCallbackType = RESURS_CALLBACK_TYPES::CALLBACK_TYPE_ANNULMENT;
            }
            if ($callbackType == "AUTOMATIC_FRAUD_CONTROL") {
                $setCallbackType = RESURS_CALLBACK_TYPES::CALLBACK_TYPE_AUTOMATIC_FRAUD_CONTROL;
            }
            if ($callbackType == "FINALIZATION") {
                $setCallbackType = RESURS_CALLBACK_TYPES::CALLBACK_TYPE_FINALIZATION;
            }
            if ($callbackType == "UNFREEZE") {
                $setCallbackType = RESURS_CALLBACK_TYPES::CALLBACK_TYPE_UNFREEZE;
            }
            if ($callbackType == "UPDATE") {
                $setCallbackType = RESURS_CALLBACK_TYPES::CALLBACK_TYPE_UPDATE;
            }
            $renderArray = [];
            if (is_array($parameterArray)) {
                foreach ($parameterArray as $parameterName) {
                    if (!$useUrlRewrite) {
                        $renderArray[] = $parameterName . "={" . $parameterName . "}";
                    } else {
                        $renderArray[] = $parameterName . "/{" . $parameterName . "}";
                    }
                }
            }
            if (!$useUrlRewrite) {
                $callbackURL = $this->callbackUrl . "?event=" . $callbackType . "&digest={digest}&" . implode("&",
                        $renderArray) . "&lastReg=" . strftime("%y%m%d%H%M%S", time());
            } else {
                $callbackURL = $this->callbackUrl . "/event/" . $callbackType . "/digest/{digest}/" . implode("/",
                        $renderArray) . "/lastReg/" . strftime("%y%m%d%H%M%S", time());
            }
            $returnCallbackArray[] = [$setCallbackType, $callbackURL, $digestArray];
        }

        return $returnCallbackArray;
    }

    /**
     * Register new callback urls via SOAP
     *
     * @throws \Exception
     */
    public function testSetRegisterCallbacksSoap()
    {
        $callbackArrayData = $this->renderCallbackData(true);
        $this->rb->setCallbackDigest($this->mkpass());
        $cResponse = [];
        $this->rb->setRegisterCallbacksViaRest(false);
        foreach ($callbackArrayData as $indexCB => $callbackInfo) {
            $cResponse[$callbackInfo[0]] = $this->rb->setRegisterCallback($callbackInfo[0],
                $callbackInfo[1] . "&via=soap", $callbackInfo[2]);
        }
        $successFulCallbacks = 0;
        foreach ($cResponse as $cbType) {
            if ($cbType == "1") {
                $successFulCallbacks++;
            }
        }
        static::assertEquals(count($cResponse), $successFulCallbacks);
    }

    /**
     * Register new callback urls via SOAP
     *
     * @throws \Exception
     */
    public function testSetRegisterCallbacksSoapUrlRewrite()
    {
        $callbackArrayData = $this->renderCallbackData(true);
        $this->rb->setCallbackDigest($this->mkpass());
        $cResponse = [];
        $this->rb->setRegisterCallbacksViaRest(false);
        foreach ($callbackArrayData as $indexCB => $callbackInfo) {
            $cResponse[$callbackInfo[0]] = $this->rb->setRegisterCallback($callbackInfo[0],
                $callbackInfo[1] . "&via=soap", $callbackInfo[2]);
        }
        $successFulCallbacks = 0;
        foreach ($cResponse as $cbType) {
            if ($cbType == "1") {
                $successFulCallbacks++;
            }
        }
        static::assertEquals(count($cResponse), $successFulCallbacks);
    }

    /**
     * Register new callback urls via REST
     *
     * @throws \Exception
     */
    public function testSetRegisterCallbacksRest()
    {
        $callbackArrayData = $this->renderCallbackData(true);
        $cResponse = [];
        $this->rb->setCallbackDigest($this->mkpass());
        $this->rb->setRegisterCallbacksViaRest(true);
        foreach ($callbackArrayData as $indexCB => $callbackInfo) {
            $cbResult = $this->rb->setRegisterCallback($callbackInfo[0], $callbackInfo[1] . "&via=rest",
                $callbackInfo[2]);
            if ($cbResult) {
                $cResponse[$callbackInfo[0]] = $cbResult;
            }
        }
        $successFulCallbacks = 0;
        foreach ($cResponse as $cbType) {
            if ($cbType == "1") {
                $successFulCallbacks++;
            }
        }
        static::assertEquals(count($cResponse), $successFulCallbacks);
    }

    /**
     * Register new callback urls via REST
     *
     * @throws \Exception
     */
    public function testSetRegisterCallbacksRestUrlRewrite()
    {
        $callbackArrayData = $this->renderCallbackData(true);
        $cResponse = [];
        $this->rb->setCallbackDigest($this->mkpass());
        $this->rb->setRegisterCallbacksViaRest(true);
        foreach ($callbackArrayData as $indexCB => $callbackInfo) {
            $cbResult = $this->rb->setRegisterCallback($callbackInfo[0], $callbackInfo[1] . "&via=rest",
                $callbackInfo[2]);
            if ($cbResult) {
                $cResponse[$callbackInfo[0]] = $cbResult;
            }
        }
        $successFulCallbacks = 0;
        foreach ($cResponse as $cbType) {
            if ($cbType == "1") {
                $successFulCallbacks++;
            }
        }
        static::assertEquals(count($cResponse), $successFulCallbacks);
    }

    /**
     * External test not functioning (Bamboo-3rdparty -- using this service requires patching from that server, as it
     * is located in a special OpenVZ-isolation)
     *
     * @throws \Exception
     */
    public function testValidateExternalUrlSuccess()
    {
        $callbackArrayData = $this->renderCallbackData();
        $this->rb->setValidateExternalCallbackUrl($callbackArrayData[0][1]);
        $getReachableStatus = $this->rb->validateExternalAddress();
        if ($getReachableStatus != RESURS_CALLBACK_REACHABILITY::IS_FULLY_REACHABLE) {
            static::fail("External address validation returned $getReachableStatus instead of " . RESURS_CALLBACK_REACHABILITY::IS_FULLY_REACHABLE . ".\nPlease check your callback url (" . $callbackArrayData[0][1] . ") so that is properly configured and reachable.");
        } else {
            static::assertTrue($getReachableStatus === RESURS_CALLBACK_REACHABILITY::IS_FULLY_REACHABLE);
        }
    }

    /**
     * Register new callback urls
     */
    public function testSetRegisterCallbacksWithValidatedUrlViaRest()
    {
        if (!$this->ignoreUrlExternalValidation) {
            $this->rb->setRegisterCallbacksViaRest(true);
            $callbackArrayData = $this->renderCallbackData();
            $this->rb->setCallbackDigest($this->mkpass());
            $cResponse = [];
            foreach ($callbackArrayData as $indexCB => $callbackInfo) {
                try {
                    $this->rb->setValidateExternalCallbackUrl($callbackInfo[1] . "&via=restValidated");
                    $cResponse[$callbackInfo[0]] = $this->rb->setRegisterCallback($callbackInfo[0],
                        $callbackInfo[1] . "&via=restValidated", $callbackInfo[2]);
                } catch (\Exception $e) {
                    static::markTestSkipped("Exception thrown: URL Validation failed for " . (isset($callbackInfo[1]) && !empty($callbackInfo[1]) ? $callbackInfo[1] . "&via=restValidated" : "??") . " during the setRegisterCallback procss (" . $e->getMessage() . ")");
                }
            }
            $successFulCallbacks = 0;
            foreach ($cResponse as $cbType) {
                if ($cbType == "1") {
                    $successFulCallbacks++;
                }
            }
            static::assertEquals(count($cResponse), $successFulCallbacks);
        } else {
            static::markTestSkipped("ignoreUrlExternalValidation is active, skipping test");
        }
    }

    /**
     * Testing of unregisterEventCallback via rest calls
     *
     * @throws \Exception
     */
    public function testUnregisterEventCallbackViaRest()
    {
        $this->rb->setRegisterCallbacksViaRest(true);

        static::assertTrue($this->rb->unregisterEventCallback(RESURS_CALLBACK_TYPES::CALLBACK_TYPE_ANNULMENT));
    }

    /**
     * Testing of unregisterEventCallback via soap calls
     *
     * @throws \Exception
     */
    public function testUnregisterEventCallbackViaSoap()
    {
        $this->rb->setRegisterCallbacksViaRest(false);
        static::assertTrue($this->rb->unregisterEventCallback(RESURS_CALLBACK_TYPES::CALLBACK_TYPE_ANNULMENT));
    }

    /**
     * Register new callback urls but without the digest key (Fail)
     */
    public function testSetRegisterCallbacksWithoutDigest()
    {
        $callbackArrayData = $this->renderCallbackData(true);
        try {
            foreach ($callbackArrayData as $indexCB => $callbackInfo) {
                $this->rb->setRegisterCallback($callbackInfo[0], $callbackInfo[1], $callbackInfo[2]);
            }
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            static::assertTrue(!empty($errorMessage));
        }
    }

    /**
     * Testing of callbacks
     */
    public function testCallbacks()
    {
        if ($this->ignoreDefaultTests) {
            static::markTestSkipped("Testing of deprecated callback function is disabled on request");
        }
        /* If disabled */
        $callbackArrayData = $this->renderCallbackData();
        $callbackSetResult = [];
        $this->rb->setCallbackDigest($this->mkpass());
        foreach ($callbackArrayData as $indexCB => $callbackInfo) {
            try {
                $this->rb->setRegisterCallback($callbackInfo[0], $callbackInfo[1],
                    $callbackInfo[2]);
                $callbackSetResult[] = $callbackInfo[0];
            } catch (\Exception $e) {
                echo __FUNCTION__ . ": " . $e->getMessage() . "\n";
            }
        }
        // Registered callbacks must be at least 4 to be successful, as there are at least 4 important callbacks to pass through
        static::assertGreaterThanOrEqual(4, count($callbackSetResult));
    }

    /**
     * @throws \Exception
     */
    function testGetNextInvoiceNumber()
    {
        static::assertTrue($this->rb->getNextInvoiceNumber() >= 1);
    }

    /*
	function testSetNextInvoiceNumber()
	{
		static::markTestSkipped("This is a special test that should normally not be necessary to run");
		$this->rb->getNextInvoiceNumber(true, 1000);
		static::assertEquals(1000, $this->rb->getNextInvoiceNumber());
	}
	*/

    /**
     * @throws \Exception
     */
    function testSetCustomerNatural()
    {
        $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RES);
        $ReturnedPayload = $this->rb->setBillingByGetAddress($this->govIdNatural);
        static::assertEquals($this->govIdNatural, $ReturnedPayload['customer']['governmentId']);
    }

    /**
     * @throws \Exception
     */
    function testSetCustomerLegal()
    {
        $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $ReturnedPayload = $this->rb->setBillingByGetAddress($this->govIdLegalCivic, "LEGAL");
        static::assertTrue($ReturnedPayload['customer']['governmentId'] == $this->govIdLegalCivic && $ReturnedPayload['customer']['address']['fullName'] == $this->govIdLegalFullname);
    }

    /**
     * @param string $articleNumberOrId
     * @param string $description
     * @param string $unitAmountWithoutVat
     * @param int $vatPct
     * @param null $type
     * @param int $quantity
     */
    private function addRandomOrderLine(
        $articleNumberOrId = "Artikel",
        $description = "Beskrivning",
        $unitAmountWithoutVat = "0.80",
        $vatPct = 25,
        $type = null,
        $quantity = 10
    ) {
        $this->rb->addOrderLine(
            $articleNumberOrId,
            $description,
            $unitAmountWithoutVat,
            $vatPct,
            "st",
            $type,
            $quantity
        );
    }

    /**
     * @param      $URL
     * @param      $govId
     * @param bool $fail
     *
     * @return bool
     * @throws \Exception
     */
    private function doMockSign($URL, $govId, $fail = false)
    {
        $mockFormResponse = $this->CURL->doGet($URL);
        $MockDomain = $this->NETWORK->getUrlDomain($this->CURL->getUrl());
        $MockDomainPath = null;
        $mockDomainPathDir = null;
        if (isset($MockDomain[2])) {
            $MockDomainPath = explode("/", $MockDomain[2]);
            $mockDomainPathDir = isset($MockDomainPath[1]) && !empty($MockDomainPath[1]) ? $MockDomainPath[1] : "";
        }

        $MockForm = $this->CURL->getBody($mockFormResponse);
        if (method_exists($this->CURL, "getParsedDomById")) {
            $this->CURL->setDomContentParser(false);
            $mockFormDom = $this->CURL->getParsedDomById();
            if (isset($mockFormDom['mockForm']['innerhtml']) && !empty($mockFormDom['mockForm']['innerhtml'])) {
                $MockForm = $mockFormDom['mockForm']['innerhtml'];
            }
            $this->CURL->setDomContentParser(true);
        }
        //$SignBody           = $this->CURL->getBody( $this->CURL->doGet( $URL ) );
        $MockFormActionPath = preg_replace("/(.*?)action=\"(.*?)\"(.*)/is", '$2', $MockForm);
        $MockFormToken = preg_replace("/(.*?)resursToken\" value=\"(.*?)\"(.*)/is", '$2', $MockForm);
        $mockFailUrl = preg_replace("/(.*?)\"\/mock\/failAuth(.*?)\"(.*)/is", '$2', $MockForm);
        $prepareMockSuccess = $MockDomain[1] . "://" . $MockDomain[0] . "/" . $mockDomainPathDir . "/" . $MockFormActionPath . "?resursToken=" . $MockFormToken . "&govId=" . $govId;
        $prepareMockFail = $MockDomain[1] . "://" . $MockDomain[0] . "/mock/failAuth" . $mockFailUrl;
        if (!$fail) {
            $ValidateUrl = $this->NETWORK->getUrlDomain($prepareMockSuccess, true);
            if (isset($ValidateUrl[0]) && !empty($ValidateUrl[0])) {
                $mockSuccess = $this->CURL->getParsed($this->CURL->doGet($prepareMockSuccess));
                if (isset($mockSuccess->_GET->success)) {
                    /** @noinspection PhpUndefinedFieldInspection */
                    return $mockSuccess->_GET;
                }
            }
        } else {
            $ValidateUrl = $this->NETWORK->getUrlDomain($prepareMockFail, true);
            if (!empty($ValidateUrl[0])) {
                try {
                    $mockSuccess = $this->CURL->getParsed($this->CURL->doGet($prepareMockFail));
                } catch (\Exception $e) {
                    return false;
                }
                if (isset($mockSuccess->_GET->success)) {
                    /** @noinspection PhpUndefinedFieldInspection */
                    return $mockSuccess->_GET;
                }
            }
        }

        return false;
    }

    /**
     * Basic payment
     */
    function testCreatePaymentPayloadSimplified()
    {
        try {
            $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);
            $this->rb->setBillingByGetAddress("198305147715");
            //$this->rb->setBillingAddress("Anders Andersson", "Anders", "Andersson", "Hamngatan 2", null, "Ingestans", "12345", "SE");
            $this->rb->setCustomer("198305147715", "0808080808", "0707070707", "test@test.com", "NATURAL");
            $this->rb->addOrderLine(
                "HORSE",
                "Stallponny",
                4800,
                25,
                "st",
                null,
                1
            );
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->rb->getPreferredPaymentId();
            // Payload that needs to be appended to the rendered one
            /*$myPayLoad = array(
                'paymentData' => array(
                    'waitForFraudControl' => false,
                    'annulIfFrozen'       => false,
                    'finalizeIfBooked'    => false,
                    'customerIpAddress'   => '127.0.0.2'
                ),
                'metaData'    => array(
                    'key'   => 'CustomerId',
                    'value' => 'l33tCustomer'
                ),
                'customer'    => array(
                    'yourCustomerId' => 'DatL33tCustomer'
                )
            );*/
            $this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
            try {
                // Using myPayLoad will lead tgo FROZEN
                $Payment = $this->rb->createPayment($this->availableMethods['invoice_natural']);

                /** @noinspection PhpUndefinedFieldInspection */
                static::assertTrue($Payment->bookPaymentStatus == "BOOKED");
            } catch (\Exception $e) {
                echo __FUNCTION__ . ": " . $e->getMessage() . "\n";
            }

        } catch (\Exception $e) {
            static::markTestSkipped($e->getMessage());
        }
    }

    function testCreatePaymentPayloadOwnPayLoadIpManipulation1()
    {
        try {
            $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);
            $this->rb->setBillingByGetAddress("198305147715");
            //$this->rb->setBillingAddress("Anders Andersson", "Anders", "Andersson", "Hamngatan 2", null, "Ingestans", "12345", "SE");
            $this->rb->setCustomer("198305147715", "0808080808", "0707070707", "test@test.com", "NATURAL");
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);

            $this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
            try {
                $myPayLoad = [
                    'paymentData' => [
                        'waitForFraudControl' => false,
                        'annulIfFrozen' => false,
                        'finalizeIfBooked' => false,
                        'customerIpAddress' => "1.2.3.4.5.6.7.8.9.0.1.2.3.4.5.6.7.8.9.0.1.2.3.4.5.6.7.8.9.0.1.2.3.4.5.6.7.8.9.0.1.2.3.4.5.6.7.8.9.0.1.2.3.4.5.6.7.8.9.0.1.2.3.4.5.6.7.8.9.0",
                    ],
                    'metaData' => [
                        'key' => 'CustomerId',
                        'value' => 'l33tCustomer',
                    ],
                    'customer' => [
                        'yourCustomerId' => 'DatL33tCustomer',
                    ],
                ];

                // Using myPayLoad will lead tgo FROZEN
                $Payment = $this->rb->createPayment($this->availableMethods['invoice_natural'], $myPayLoad);
                /** @noinspection PhpUndefinedFieldInspection */
                static::assertTrue($Payment->bookPaymentStatus == "FROZEN");
            } catch (\Exception $e) {
                static::assertTrue($e->getCode() > 0);
            }

        } catch (\Exception $e) {
            static::markTestSkipped($e->getMessage());
        }
    }

    function testCreatePaymentPayloadOwnPayLoadIpManipulation2()
    {
        try {
            $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);
            $this->rb->setBillingByGetAddress("198305147715");
            //$this->rb->setBillingAddress("Anders Andersson", "Anders", "Andersson", "Hamngatan 2", null, "Ingestans", "12345", "SE");
            $this->rb->setCustomer("198305147715", "0808080808", "0707070707", "test@test.com", "NATURAL");
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            //$useThisPaymentId = $this->rb->getPreferredPaymentId();

            $this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
            try {
                $myPayLoad = [
                    'paymentData' => [
                        'waitForFraudControl' => false,
                        'annulIfFrozen' => false,
                        'finalizeIfBooked' => false,
                        'customerIpAddress' => "abcdefghijklmno",
                    ],
                    'metaData' => [
                        'key' => 'CustomerId',
                        'value' => 'l33tCustomer',
                    ],
                    'customer' => [
                        'yourCustomerId' => 'DatL33tCustomer',
                    ],
                ];
                // Using myPayLoad will lead tgo FROZEN
                $Payment = $this->rb->createPayment($this->availableMethods['invoice_natural'], $myPayLoad);
                /** @noinspection PhpUndefinedFieldInspection */
                static::assertTrue($Payment->bookPaymentStatus == "FROZEN");
            } catch (\Exception $e) {
                static::assertTrue($e->getCode() > 0);
            }

        } catch (\Exception $e) {
            static::markTestSkipped($e->getMessage());
        }
    }

    function testCreatePaymentPayloadOwnPayLoadSpoofedIpFrozenWithFraudControl()
    {
        try {
            $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);
            $this->rb->setBillingByGetAddress("198305147715");
            //$this->rb->setBillingAddress("Anders Andersson", "Anders", "Andersson", "Hamngatan 2", null, "Ingestans", "12345", "SE");
            $this->rb->setCustomer("198305147715", "0808080808", "0707070707", "test@test.com", "NATURAL");
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            //$useThisPaymentId = $this->rb->getPreferredPaymentId();

            $this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
            try {
                $myPayLoad = [
                    'paymentData' => [
                        'waitForFraudControl' => false,
                        'annulIfFrozen' => false,
                        'finalizeIfBooked' => false,
                        'customerIpAddress' => "ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff",
                    ],
                    'metaData' => [
                        'key' => 'CustomerId',
                        'value' => 'l33tCustomer',
                    ],
                    'customer' => [
                        'yourCustomerId' => 'DatL33tCustomer',
                    ],
                ];
                // Using myPayLoad will lead tgo FROZEN
                $Payment = $this->rb->createPayment($this->availableMethods['invoice_natural'], $myPayLoad);
                /** @noinspection PhpUndefinedFieldInspection */
                static::assertTrue($Payment->bookPaymentStatus == "FROZEN");
            } catch (\Exception $e) {
                static::assertTrue($e->getCode() > 0);
            }

        } catch (\Exception $e) {
            static::markTestSkipped($e->getMessage());
        }
    }

    /**
     * Creating payment with own billing address but happyflow govId
     */
    function testCreatePaymentPayloadForcedSigningSimplified()
    {
        try {
            $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);
            $this->rb->setBillingByGetAddress("198305147715");
            $this->rb->setCustomer(null, "0808080808", "0707070707", "test@test.com", "NATURAL");
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            //$useThisPaymentId = $this->rb->getPreferredPaymentId();
            $this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', true);
            try {
                $Payment = $this->rb->createPayment($this->availableMethods['invoice_natural']);
                /** @noinspection PhpUndefinedFieldInspection */
                if ($Payment->bookPaymentStatus == "SIGNING") {
                    /** @noinspection PhpUndefinedFieldInspection */
                    $signUrl = $Payment->signingUrl;
                    $signData = $this->doMockSign($signUrl, "198305147715");
                    /** @noinspection PhpUndefinedFieldInspection */
                    static::assertTrue($signData->success == "true");
                }
            } catch (\Exception $e) {
                echo __FUNCTION__ . ": " . $e->getMessage() . "\n";
            }

        } catch (\Exception $e) {
            static::markTestSkipped($e->getMessage());
        }
    }

    function testCreatePaymentPayloadForcedSigningMultipleSimplified()
    {
        $signData = [];
        try {
            $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);
            $this->rb->setBillingByGetAddress("198305147715");
            $this->rb->setCustomer(null, "0808080808", "0707070707", "test@test.com", "NATURAL");
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            //$useThisPaymentId = $this->rb->getPreferredPaymentId();
            $this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', true);
            try {
                $Payment = $this->rb->createPayment($this->availableMethods['invoice_natural']);
                /** @noinspection PhpUndefinedFieldInspection */
                if ($Payment->bookPaymentStatus == "SIGNING") {
                    /** @noinspection PhpUndefinedFieldInspection */
                    $signUrl = $Payment->signingUrl;
                    try {
                        $signings = 0;
                        $signData[$signings++] = $this->doMockSign($signUrl, "198305147715");
                        $signData[$signings++] = $this->doMockSign($signUrl, "198305147715");
                        $signData[$signings++] = $this->doMockSign($signUrl, "198305147715");
                        /** @noinspection PhpUnusedLocalVariableInspection */
                        $signData[$signings++] = $this->doMockSign($signUrl, "198305147715");
                    } catch (\Exception $e) {

                    }
                    static::assertCount(4, $signData);
                }
            } catch (\Exception $e) {
                echo __FUNCTION__ . ": " . $e->getMessage() . "\n";
            }

        } catch (\Exception $e) {
            static::markTestSkipped($e->getMessage());
        }
    }

    function testCreatePaymentPayloadForcedSigningReUseMockFailSimplified()
    {
        try {
            $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);
            $this->rb->setBillingByGetAddress("198305147715");
            $this->rb->setCustomer(null, "0808080808", "0707070707", "test@test.com", "NATURAL");
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            //$useThisPaymentId = $this->rb->getPreferredPaymentId();
            $this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', true);
            try {
                $Payment = $this->rb->createPayment($this->availableMethods['invoice_natural']);
                /** @noinspection PhpUndefinedFieldInspection */
                if ($Payment->bookPaymentStatus == "SIGNING") {
                    /** @noinspection PhpUndefinedFieldInspection */
                    $signUrl = $Payment->signingUrl;
                    try {
                        $signings = 0;
                        $signData[$signings++] = $this->doMockSign($signUrl, "198305147715", true);
                        /** @noinspection PhpUnusedLocalVariableInspection */
                        $signData[$signings++] = $this->doMockSign($signUrl, "198305147715", true);
                    } catch (\Exception $e) {
                    }
                    static::assertTrue(empty($signData[1]));
                }
            } catch (\Exception $e) {
                echo __FUNCTION__ . ": " . $e->getMessage() . "\n";
            }

        } catch (\Exception $e) {
            static::markTestSkipped($e->getMessage());
        }
    }
    /*function testCreatePaymentPayloadForcedSigningReUseMockFailNewCardSimplified() {
		try {
			///// card_new

			$this->rb->setPreferredPaymentFlowService( RESURS_FLOW_TYPES::SIMPLIFIED_FLOW );
			$this->rb->setBillingByGetAddress( "198305147715" );
			$this->rb->setCustomer( null, "0808080808", "0707070707", "test@test.com", "NATURAL" );
			$this->addRandomOrderLine( "Art " . rand( 1024, 2048 ), "Beskrivning " . rand( 2048, 4096 ), "0.80", 25, null, 10 );
			$this->addRandomOrderLine( "Art " . rand( 1024, 2048 ), "Beskrivning " . rand( 2048, 4096 ), "0.80", 25, null, 10 );
			$this->addRandomOrderLine( "Art " . rand( 1024, 2048 ), "Beskrivning " . rand( 2048, 4096 ), "0.80", 25, null, 10 );
			$this->addRandomOrderLine( "Art " . rand( 1024, 2048 ), "Beskrivning " . rand( 2048, 4096 ), "0.80", 25, null, 10 );
			$useThisPaymentId = $this->rb->getPreferredPaymentId();
			$this->rb->setSigning( $this->signUrl . '&success=true', $this->signUrl . '&success=false', true );
			try {
				$Payment = $this->rb->createPayment( $this->availableMethods['card_new'] );
				if ( $Payment->bookPaymentStatus == "SIGNING" ) {
					$signUrl  = $Payment->signingUrl;
					try {
						$signings = 0;
						$signData[$signings++] = $this->doMockSign( $signUrl, "198305147715", true );
						$signData[$signings++] = $this->doMockSign( $signUrl, "198305147715", true );
					} catch (\Exception $e) {
					}
					static::assertTrue( empty($signData[1]) );
				}
			} catch ( \Exception $e ) {
				echo __FUNCTION__ . ": " . $e->getMessage() . "\n";
			}

		} catch ( \Exception $e ) {
			static::markTestSkipped( $e->getMessage() );
		}
	}*/

    /**
     * Creating payment with own billing address but happyflow govId
     */
    function testCreatePaymentPayloadUseExecuteSimplified()
    {
        try {
            $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);
            $this->rb->setBillingByGetAddress("198305147715");
            $this->rb->setCustomer(null, "0808080808", "0707070707", "test@test.com", "NATURAL");
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null,
                10);
            //$useThisPaymentId = $this->rb->getPreferredPaymentId();
            $this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
            try {
                $this->rb->createPaymentDelay(true);
                $delayPayment = $this->rb->createPayment($this->availableMethods['invoice_natural']);
                if (isset($delayPayment['status']) && $delayPayment['status'] == "delayed") {
                    //$thePayload = $this->rb->getPayload();
                    $thePayment = $this->rb->Execute(); // $this->availableMethods['invoice_natural']
                    static::assertTrue($thePayment->bookPaymentStatus == "BOOKED");

                    return;
                }
            } catch (\Exception $e) {
                static::markTestSkipped($e->getMessage());
            }
        } catch (\Exception $e) {
            static::markTestSkipped("Outer exception thrown (" . $e->getMessage() . ")");
        }
        static::markTestSkipped("CreatePayment via Delayed create failed - never passed through the payload generation.");
    }

    /**
     * Creating payment with own billing address but happyflow govId
     */
    function testCreatePaymentPayloadUseExecuteResursCheckout()
    {
        try {
            $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
            $this->rb->setBillingByGetAddress("198305147715");
            $this->rb->setCustomer(null, "0808080808", "0707070707", "test@test.com", "NATURAL");
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25,
                'ORDER_LINE', 10);
            $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25,
                'ORDER_LINE', 10);
            //$useThisPaymentId = $this->rb->getPreferredPaymentId();
            $this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
            try {
                $this->rb->createPaymentDelay(true);
                $delayPayment = $this->rb->createPayment($this->availableMethods['invoice_natural']);
                //$paymentId    = $this->rb->getPreferredPaymentId();
                if (isset($delayPayment['status']) && $delayPayment['status'] == "delayed") {
                    //$thePayload = $this->rb->getPayload();
                    $thePayment = $this->rb->Execute(); // $this->availableMethods['invoice_natural']
                    static::assertTrue(preg_match("/iframe src/i", $thePayment) ? true : false);

                    return;
                }
            } catch (\Exception $e) {
                static::markTestSkipped($e->getMessage());
            }
        } catch (\Exception $e) {
            static::markTestSkipped("Outer exception thrown (" . $e->getMessage() . ")");
        }
        static::markTestSkipped("CreatePayment via Delayed create failed - never passed through the payload generation.");
    }


    function testCanDebit()
    {
        try {
            $payment = $this->rb->getPayment("20170519070836-6799421526");
            static::assertTrue($this->rb->canDebit($payment));
        } catch (\Exception $e) {
            static::markTestSkipped("Can not find any debitable snapshot to test.");
        }
    }

    /**
     * @param int $orderLines
     * @param int $quantity
     * @param int $minAmount
     * @param int $maxAmount
     * @param string $govId
     *
     * @return array|null
     * @throws \Exception
     */
    private function generateOrderByClientChoice(
        $orderLines = 8,
        $quantity = 1,
        $minAmount = 1000,
        $maxAmount = 2000,
        $govId = '198305147715'
    ) {
        $Payment = null;
        $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);
        $this->rb->setBillingByGetAddress($govId);
        $this->rb->setCustomer($govId, "0808080808", "0707070707", "test@test.com", "NATURAL");
        if ($orderLines > 0) {
            while ($orderLines-- > 0) {
                $this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096),
                    rand($minAmount, $maxAmount), 25, null, $quantity);
            }
        }
        $this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
        try {
            $Payment = $this->rb->createPayment($this->availableMethods['invoice_natural']);
        } catch (\Exception $e) {
            echo __FUNCTION__ . ": " . $e->getMessage() . "\n";
        }

        //if ( $Payment->bookPaymentStatus == "BOOKED" ) {
        //	return $Payment;
        //}
        // Always return instead of only booked so we can test other than BOOEKD
        return $Payment;
    }

    /**
     * @param int $orderLines
     * @param int $quantity
     * @param int $minAmount
     * @param int $maxAmount
     * @param string $govId
     *
     * @return mixed
     * @throws \Exception
     */
    private function getPaymentIdFromOrderByClientChoice(
        $orderLines = 8,
        $quantity = 1,
        $minAmount = 1000,
        $maxAmount = 2000,
        $govId = '198305147715'
    ) {
        $Payment = $this->generateOrderByClientChoice($orderLines, $quantity, $minAmount, $maxAmount, $govId);
        if (isset($Payment)) {
            /** @noinspection PhpUndefinedFieldInspection */
            return $Payment->paymentId;
        }

        return false;
    }

    /**
     *
     */
    function testHugeQuantity()
    {
        try {
            $hasOrder = $this->generateOrderByClientChoice(2, 16000, 1, 1);
            /** @noinspection PhpUndefinedFieldInspection */
            static::assertTrue($hasOrder->bookPaymentStatus == "BOOKED");
        } catch (\Exception $e) {
        }
    }

    /**
     * @throws \Exception
     */
    function testAdditionalDebit()
    {
        $paymentId = $this->getPaymentIdFromOrderByClientChoice();
        $this->rb->addOrderLine("myExtraOrderLine-1", "One orderline added with additionalDebitOfPayment", 100, 25);
        $this->rb->addOrderLine("myExtraOrderLine-2", "One orderline added with additionalDebitOfPayment", 200, 25);
        static::assertTrue($this->rb->setAdditionalDebitOfPayment($paymentId));
    }

    /**
     * @throws \Exception
     */
    function testAdditionalDebitAnnulled()
    {
        $paymentId = $this->getPaymentIdFromOrderByClientChoice();
        $this->rb->annulPayment($paymentId);
        $this->rb->addOrderLine("myExtraOrderLine-1", "One orderline added with additionalDebitOfPayment", 100, 25);
        $this->rb->addOrderLine("myExtraOrderLine-2", "One orderline added with additionalDebitOfPayment", 200, 25);
        static::assertTrue($this->rb->setAdditionalDebitOfPayment($paymentId));
    }

    /**
     * @throws \Exception
     */
    function testAdditionalDebitResursCheckout()
    {
        $paymentId = $this->getPaymentIdFromOrderByClientChoice();
        $this->rb->annulPayment($paymentId);
        $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::RESURS_CHECKOUT);
        $this->rb->addOrderLine("myExtraOrderLine-1", "One orderline added with additionalDebitOfPayment", 100, 25);
        $this->rb->addOrderLine("myExtraOrderLine-2", "One orderline added with additionalDebitOfPayment", 200, 25);
        static::assertTrue($this->rb->setAdditionalDebitOfPayment($paymentId));
    }

    /**
     * Test wrong, bad and stupid behaviour when orderlines are duplicated
     *
     * @throws \Exception
     */
    function testAdditionalDebitDuplicateLines()
    {
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1);
        try {
            $this->rb->addOrderLine("myExtraOrderLine-1", "One orderline added with additionalDebitOfPayment", 100, 25);
            $this->rb->addOrderLine("myExtraOrderLine-2", "One orderline added with additionalDebitOfPayment", 200, 25);
            $this->rb->setAdditionalDebitOfPayment($paymentId);
            $this->rb->addOrderLine("myExtraOrderLine-1", "One orderline added with additionalDebitOfPayment", 100, 25);
            $this->rb->addOrderLine("myExtraOrderLine-2", "One orderline added with additionalDebitOfPayment", 200, 25);
            $this->rb->setAdditionalDebitOfPayment($paymentId);
        } catch (\Exception $additionalException) {
        }
        $paymentResult = $this->rb->getPaymentSpecCount($paymentId);
        static::assertTrue($paymentResult['AUTHORIZE'] == 5);
    }

    /**
     * @test
     * @testdox
     * @todo 180607: Currently failing due to in-house issues
     * @throws Exception
     */
    function additionalDebitReduceFail()
    {
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1);
        $this->rb->addOrderLine("myExtraOrderLine-1", "One orderline added with additionalDebitOfPayment", 100, 25);
        $this->rb->addOrderLine("myExtraOrderLine-2", "One orderline added with additionalDebitOfPayment", 200, 25);
        $this->rb->setAdditionalDebitOfPayment($paymentId);
        try {
            $this->rb->addOrderLine("myExtraOrderLine-1", "One orderline added with additionalDebitOfPayment", 100, 25,
                null, null, -5);
            $this->rb->addOrderLine("myExtraOrderLine-2", "One orderline added with additionalDebitOfPayment", 200, 25,
                null, null, -5);
            $this->rb->setAdditionalDebitOfPayment($paymentId);
        } catch (\Exception $e) {
            // Exceptions that comes from this part of the system does not seem to generate any exception code.
            static::assertTrue($e->getCode() == 500 || $e->getCode() == \RESURS_EXCEPTIONS::UNKOWN_SOAP_EXCEPTION_CODE_ZERO);

            return;
        }
        static::fail("Test " . __FUNCTION__ . " got no proper assertion. This test SHOULD receive an exception from ecommerce (since negative values should not be allowed) but that never occured.");
    }

    /**
     * Test for ECOMPHP-113
     *
     * @covers ::ResursBank
     * @covers ::MODULE_CURL
     * @throws \Exception
     */
    function testAdditionalDebitNewDoubleDuplicateCheck()
    {
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(2);
        $this->rb->addOrderLine("myAdditionalOrderLineFirst", "One orderline added with additionalDebitOfPayment", 100,
            25);
        $this->rb->setAdditionalDebitOfPayment($paymentId);
        $this->rb->addOrderLine("myAdditionalOrderLineExtended", "One orderline added with additionalDebitOfPayment",
            100, 25);
        $this->rb->setAdditionalDebitOfPayment($paymentId);
        $merged = $this->rb->getPaymentSpecByStatus($paymentId);
        $added = 0;
        foreach ($merged['AUTHORIZE'] as $articles) {
            if ($articles->artNo == "myAdditionalOrderLineFirst") {
                $added++;
            }
            if ($articles->artNo == "myAdditionalOrderLineExtended") {
                $added++;
            }
        }
        static::assertEquals(2, $added);
    }

    /**
     * Test for ECOMPHP-112
     *
     * @throws \Exception
     */
    function testAdditionalDualDebitWithDifferentAmount()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        try {
            $paymentId = $this->getPaymentIdFromOrderByClientChoice();
            $this->rb->finalizePayment($paymentId);
            $this->rb->addOrderLine("myAdditionalOrderLine", "One orderline added with additionalDebitOfPayment", 100,
                25);
            $this->rb->setAdditionalDebitOfPayment($paymentId);
            $this->rb->addOrderLine("myAdditionalOrderLine", "One orderline added with additionalDebitOfPayment", 105,
                25);
            $this->rb->setAdditionalDebitOfPayment($paymentId);
            $merged = $this->rb->getPaymentSpecByStatus($paymentId);
            $quantity = 0;
            foreach ($merged['AUTHORIZE'] as $articles) {
                if ($articles->artNo == "myAdditionalOrderLine") {
                    $quantity += $articles->quantity;
                }
            }
            static::assertEquals(2, $quantity);
        } catch (\Exception $e) {
            if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                if (!defined('SKIP_TEST')) {
                    define('SKIP_TEST', ['skip' => 'aftershop']);
                }
                static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
            } else {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function testRenderSpeclineByObject()
    {
        $payment = $this->getAPayment(null, true);
        if (isset($payment->id)) {
            static::assertTrue(is_array($this->rb->getPaymentSpecByStatus($payment)));
        }
    }

    /**
     * @throws \Exception
     */
    public function testRenderSpeclineByOrderId()
    {
        $payment = $this->getAPayment(null, true);
        if (isset($payment->id)) {
            static::assertTrue(is_array($this->rb->getPaymentSpecByStatus($payment->id)));
        }
    }

    /**
     * @throws \Exception
     */
    function testFinalizeFullDeprecated()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $paymentId = $this->getPaymentIdFromOrderByClientChoice();
        try {
            static::assertTrue($this->rb->finalizePayment($paymentId));
        } catch (\Exception $e) {
            if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                if (!defined('SKIP_TEST')) {
                    define('SKIP_TEST', ['skip' => 'aftershop']);
                }
                static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
            } else {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * @throws \Exception
     */
    function testAfterShopSanitizer()
    {
        $expect = 2;        // When the above works, we expect 2
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(2);
        $saneFinalize = $this->rb->sanitizeAfterShopSpec($paymentId, RESURS_AFTERSHOP_RENDER_TYPES::FINALIZE);
        //$paymentId = '145000153';
        //$saneAnnul = $this->rb->sanitizeAfterShopSpec($paymentId, RESURS_AFTERSHOP_RENDER_TYPES::ANNUL);
        //$saneFinalAnnul = $this->rb->sanitizeAfterShopSpec($paymentId, (RESURS_AFTERSHOP_RENDER_TYPES::ANNUL | RESURS_AFTERSHOP_RENDER_TYPES::FINALIZE));
        static::assertCount($expect, $saneFinalize);
    }

    /**
     * Test: Curl error handling from NetCurl 6.0.5 and above
     *
     * @throws \Exception
     */
    function testSoapError()
    {
        $CURL = new MODULE_CURL();
        //$CURL->setChain(false);
        $wsdl = $CURL->doGet('https://test.resurs.com/ecommerce-test/ws/V4/SimplifiedShopFlowService?wsdl');
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $wsdl->getPaymentMethods();
        } catch (\Exception $e) {
            $previousException = $e->getPrevious();
            // $exceptionMessage = $e->getMessage();
            static::assertTrue(isset($previousException->faultstring) && !empty($previousException->faultstring));
        }
    }

    /**
     * Test: Aftershop finalization, new method
     * Expected result: The order is fully debited
     *
     * @throws \Exception
     */
    function testAftershopFullFinalization()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(2);
        if ($this->resetConnection()) {
            $this->rb->setAfterShopInvoiceExtRef("Test Testsson");
            try {
                $finalizeResult = $this->rb->paymentFinalize($paymentId);
                $testOrder = $this->rb->getPaymentSpecCount($paymentId);
                static::assertTrue(($finalizeResult == 200 && $testOrder['AUTHORIZE'] == 2 && $testOrder['DEBIT'] == 2));
            } catch (\Exception $e) {
                if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                    if (!defined('SKIP_TEST')) {
                        define('SKIP_TEST', ['skip' => 'aftershop']);
                    }
                    static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
                } else {
                    throw new \Exception($e->getMessage(), $e->getCode(), $e);
                }

            }
        }
    }

    /**
     * Test: Aftershop finalization, new method, automated by using addOrderLine
     * Expected result: Two rows, one added row debited
     *
     * @throws \Exception
     */
    function testAftershopPartialAutomatedFinalization()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        // Add one order line to the random one
        $this->rb->addOrderLine("myAdditionalPartialAutomatedOrderLine", "One orderline added with addOrderLine", 100,
            25);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1);
        if ($this->resetConnection()) {
            $this->rb->setAfterShopInvoiceExtRef("Test Testsson");
            // Add the orderLine that should be handled in the finalization
            // id, desc, unitAmoutWithoutVat, vatPct, unitMeasure, ORDER_LINE, quantity
            $this->rb->addOrderLine("myAdditionalPartialAutomatedOrderLine", "One orderline added with addOrderLine",
                100, 25);
            try {
                $finalizeResult = $this->rb->paymentFinalize($paymentId);
                $testOrder = $this->rb->getPaymentSpecCount($paymentId);
                static::assertTrue(($finalizeResult == 200 && $testOrder['AUTHORIZE'] == 2 && $testOrder['DEBIT'] == 1));
            } catch (\Exception $e) {
                if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                    if (!defined('SKIP_TEST')) {
                        define('SKIP_TEST', ['skip' => 'aftershop']);
                    }
                    static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
                } else {
                    throw new \Exception($e->getMessage(), $e->getCode(), $e);
                }

            }
        }
    }

    /**
     * Test: Aftershop finalization, new method, automated by using addOrderLine
     * Expected result: Two rows, the row with 4 in quantity has 2 debited
     *
     * @throws \Exception
     */
    function testAftershopPartialAutomatedQuantityFinalization()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        // Add one order line to the random one, with 4 in quantity
        $this->rb->addOrderLine("myAdditionalAutomatedOrderLine", "One orderline added with addOrderLine", 100, 25,
            'st', 'ORDER_LINE', 4);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1);
        if ($this->resetConnection()) {
            $this->rb->setAfterShopInvoiceExtRef("Test Testsson");
            // Add the orderLine that should be handled in the finalization, but only 2 of the set up above
            $this->rb->addOrderLine("myAdditionalAutomatedOrderLine", "One orderline added with addOrderLine", 100, 25,
                'st', 'ORDER_LINE', 2);
            try {
                $finalizeResult = $this->rb->paymentFinalize($paymentId);
                $countOrder = $this->rb->getPaymentSpecCount($paymentId);
                $testOrder = $this->rb->getPaymentSpecByStatus($paymentId);
                // Also check the quantity on this
                static::assertTrue(($finalizeResult == 200 && $countOrder['AUTHORIZE'] == 2 && $countOrder['DEBIT'] == 1 && (int)$testOrder['DEBIT']['0']->quantity == 2));
            } catch (\Exception $e) {
                if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                    if (!defined('SKIP_TEST')) {
                        define('SKIP_TEST', ['skip' => 'aftershop']);
                    }
                    static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
                } else {
                    throw new \Exception($e->getMessage(), $e->getCode(), $e);
                }

            }
        }
    }

    /**
     * Test: Aftershop finalization, new method, automated by using addOrderLine
     * Expected result: Two rows, one row (the correct one) row debited
     *
     * @throws \Exception
     */
    function testAftershopPartialManualFinalization()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        // Add one order line to the random one
        $this->rb->addOrderLine("myAdditionalManualOrderLine", "One orderline added with addOrderLine", 100, 25);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1);
        if ($this->resetConnection()) {
            $this->rb->setAfterShopInvoiceExtRef("Test Testsson");
            $newArray = [
                'artNo' => 'myAdditionalManualOrderLine',
                'description' => "One orderline added with addOrderLine",
                'unitAmountWithoutVat' => 100,
                'vatPct' => 25,
                'quantity' => 1,
            ];
            try {
                $finalizeResult = $this->rb->paymentFinalize($paymentId, $newArray);
                $testOrder = $this->rb->getPaymentSpecCount($paymentId);
                static::assertTrue(($finalizeResult == 200 && $testOrder['AUTHORIZE'] == 2 && $testOrder['DEBIT'] == 1));
            } catch (\Exception $e) {
                if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                    if (!defined('SKIP_TEST')) {
                        define('SKIP_TEST', ['skip' => 'aftershop']);
                    }
                    static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
                } else {
                    throw new \Exception($e->getMessage(), $e->getCode(), $e);
                }

            }
        }
    }

    /**
     * Test: Aftershop finalization, new method, automated by using addOrderLine
     * Expected result: Two rows, one row (the correct one) row debited
     *
     * @throws \Exception
     */
    function testAftershopPartialMultipleManualFinalization()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        // Add one order line to the random one
        $this->rb->addOrderLine("myAdditionalManualFirstOrderLine", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("myAdditionalManualSecondOrderLine", "One orderline added with addOrderLine", 100, 25);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1);
        if ($this->resetConnection()) {
            $this->rb->setAfterShopInvoiceExtRef("Test Testsson");
            $orderLineArray = [
                [
                    'artNo' => 'myAdditionalManualFirstOrderLine',
                    'description' => "One orderline added with addOrderLine",
                    'unitAmountWithoutVat' => 100,
                    'vatPct' => 25,
                    'quantity' => 1,
                ],
                [
                    'artNo' => 'myAdditionalManualSecondOrderLine',
                    'description' => "One orderline added with addOrderLine",
                    'unitAmountWithoutVat' => 100,
                    'vatPct' => 25,
                    'quantity' => 1,
                ],
            ];
            try {
                $finalizeResult = $this->rb->paymentFinalize($paymentId, $orderLineArray);
                $testOrder = $this->rb->getPaymentSpecCount($paymentId);
                static::assertTrue(($finalizeResult == 200 && $testOrder['AUTHORIZE'] == 3 && $testOrder['DEBIT'] == 2));
            } catch (\Exception $e) {
                if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                    if (!defined('SKIP_TEST')) {
                        define('SKIP_TEST', ['skip' => 'aftershop']);
                    }
                    static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
                } else {
                    throw new \Exception($e->getMessage(), $e->getCode(), $e);
                }

            }
        }
    }

    /**
     * Test: Aftershop finalization, new method, manually added array that mismatches with the first order (This order
     * will have one double debited orderLine) Expected result: Three rows, mismatching row debited
     *
     * @throws \Exception
     */
    function testAftershopPartialManualFinalizationWithMismatchingKeys()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        // Add one order line to the random one
        $this->rb->addOrderLine("myAdditionalManualOrderLine", "One orderline added with addOrderLine", 100, 25);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1);
        if ($this->resetConnection()) {
            $this->rb->setAfterShopInvoiceExtRef("Test Testsson");
            $newArray = [
                'artNo' => 'myAdditionalMismatchingOrderLine',
                'description' => "One orderline added with addOrderLine",
                'unitAmountWithoutVat' => 101,
                'vatPct' => 25,
                'quantity' => 2,
            ];
            try {
                $finalizeResult = $this->rb->paymentFinalize($paymentId, $newArray);
                $countOrder = $this->rb->getPaymentSpecCount($paymentId);
                $testOrder = $this->rb->getPaymentSpecByStatus($paymentId);
                static::assertTrue(($finalizeResult == 200 && $countOrder['AUTHORIZE'] == 2 && $countOrder['DEBIT'] == 1 && (int)$testOrder['DEBIT']['0']->quantity == 2));
            } catch (\Exception $e) {
                if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                    if (!defined('SKIP_TEST')) {
                        define('SKIP_TEST', ['skip' => 'aftershop']);
                    }
                    static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
                } else {
                    throw new \Exception($e->getMessage(), $e->getCode(), $e);
                }

            }
        }
    }

    function testAftershopFullFinalizationFailure()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        define('TEST_OVERRIDE_AFTERSHOP_PAYLOAD',
            'a:9:{s:9:"paymentId";s:19:"unExistentPaymentId";s:9:"orderDate";s:10:"2017-09-28";s:11:"invoiceDate";s:10:"2017-09-28";s:9:"invoiceId";i:1366;s:9:"createdBy";s:14:"EComPHP_010122";s:9:"specLines";a:2:{i:0;a:9:{s:2:"id";i:1;s:5:"artNo";s:8:"Art 1065";s:11:"description";s:16:"Beskrivning 3222";s:8:"quantity";s:7:"1.00000";s:11:"unitMeasure";s:2:"st";s:20:"unitAmountWithoutVat";s:10:"1309.00000";s:6:"vatPct";s:8:"25.00000";s:14:"totalVatAmount";s:19:"327.250000000000000";s:11:"totalAmount";s:20:"1636.250000000000000";}i:1;a:9:{s:2:"id";i:2;s:5:"artNo";s:8:"Art 2022";s:11:"description";s:16:"Beskrivning 4048";s:8:"quantity";s:7:"1.00000";s:11:"unitMeasure";s:2:"st";s:20:"unitAmountWithoutVat";s:10:"1292.00000";s:6:"vatPct";s:8:"25.00000";s:14:"totalVatAmount";s:19:"323.000000000000000";s:11:"totalAmount";s:20:"1615.000000000000000";}}s:11:"totalAmount";d:3251.25;s:14:"totalVatAmount";d:650.25;s:15:"partPaymentSpec";a:3:{s:9:"specLines";a:2:{i:0;a:9:{s:2:"id";i:1;s:5:"artNo";s:8:"Art 1065";s:11:"description";s:16:"Beskrivning 3222";s:8:"quantity";s:7:"1.00000";s:11:"unitMeasure";s:2:"st";s:20:"unitAmountWithoutVat";s:10:"1309.00000";s:6:"vatPct";s:8:"25.00000";s:14:"totalVatAmount";s:19:"327.250000000000000";s:11:"totalAmount";s:20:"1636.250000000000000";}i:1;a:9:{s:2:"id";i:2;s:5:"artNo";s:8:"Art 2022";s:11:"description";s:16:"Beskrivning 4048";s:8:"quantity";s:7:"1.00000";s:11:"unitMeasure";s:2:"st";s:20:"unitAmountWithoutVat";s:10:"1292.00000";s:6:"vatPct";s:8:"25.00000";s:14:"totalVatAmount";s:19:"323.000000000000000";s:11:"totalAmount";s:20:"1615.000000000000000";}}s:11:"totalAmount";d:3251.25;s:14:"totalVatAmount";d:650.25;}}');
        try {
            $this->rb->paymentFinalizeTest();
        } catch (\Exception $paymentFinalizeException) {
            $exceptionCode = $paymentFinalizeException->getCode();
            static::assertTrue($exceptionCode == RESURS_EXCEPTIONS::ECOMMERCEERROR_REFERENCED_DATA_DONT_EXISTS || $exceptionCode >= 500);
        }
    }

    /**
     * Test: Aftershop cancellation, new method
     * Expected result: The order is fully cancelled, independently on what happened to the order earlier
     *
     * @throws \Exception
     */
    function testAftershopFullCancellation()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("authLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("authLine-2", "One orderline added with addOrderLine", 100, 25);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(0);

        try {
            $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
            $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
            $this->rb->paymentFinalize($paymentId);

            $this->rb->setAfterShopInvoiceExtRef("Test Testsson");
            $cancellationResult = $this->rb->paymentCancel($paymentId);
            $result = $this->rb->getPaymentSpecCount($paymentId);
            static::assertTrue($cancellationResult && $result['AUTHORIZE'] == 4 && $result['DEBIT'] == 2 && $result['CREDIT'] == 2 && $result['ANNUL'] == 2);
        } catch (\Exception $e) {
            if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                if (!defined('SKIP_TEST')) {
                    define('SKIP_TEST', ['skip' => 'aftershop']);
                }
                static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
            } else {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

        }
    }

    /**
     * @throws \Exception
     */
    function testAftershopCreditBulk()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $this->rb->addOrderLine("a", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("b", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("c", "One orderline added with addOrderLine", 100, 25);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(0);
        try {
            $this->rb->paymentFinalize($paymentId);
            $this->rb->addOrderLine("z", "One orderline added with addOrderLine", 300, 25);
            $this->rb->paymentCredit($paymentId);
            $result = $this->rb->getPaymentSpecCount($paymentId);
            static::assertTrue($result['AUTHORIZE'] == 3 && $result['DEBIT'] == 3 && $result['CREDIT'] == 1 && $result['ANNUL'] == 0);
        } catch (\Exception $e) {
            if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                if (!defined('SKIP_TEST')) {
                    define('SKIP_TEST', ['skip' => 'aftershop']);
                }
                static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
            } else {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * @throws \Exception
     */
    function testAftershopBuy10Annul20()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $this->rb->addOrderLine("a", "One orderline added with addOrderLine", 100, 25, null, null, 10);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(0);
        $this->rb->addOrderLine("a", "One orderline added with addOrderLine", 100, 25, null, null, 20);
        try {
            $this->rb->paymentAnnul($paymentId);
        } catch (\Exception $negativeException) {
            static::assertTrue($negativeException->getCode() > 0);
        }
    }

    /**
     * @throws \Exception
     */
    function testAftershopBuy10Credit20()
    {
        $skipInvoiceTest = false;
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $this->rb->addOrderLine("a", "One orderline added with addOrderLine", 100, 25, null, null, 10);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(0);
        try {
            $this->rb->paymentFinalize($paymentId);
            $this->rb->addOrderLine("a", "One orderline added with addOrderLine", 100, 25, null, null, 20);
            try {
                $this->rb->paymentCredit($paymentId);
            } catch (\Exception $negativeException) {
                if ($negativeException->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                    $skipInvoiceTest = true;
                    static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
                }
                static::assertTrue($negativeException->getCode() > 0);
            }
        } catch (\Exception $e) {
            if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                $skipInvoiceTest = true;
                static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
            } else {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
        }

        if ($skipInvoiceTest) {
            if (!defined('SKIP_TEST')) {
                define('SKIP_TEST', ['skip' => 'aftershop']);
            }
        }
    }

    /**
     * Test: Aftershop cancellation, new method
     * Expected result: The order is half debited, half credited and half annulled. The invalid article is sanitized as
     * it does not belong to any of the specrows
     *
     * @throws \Exception
     */
    function testAftershopPartialCancellation()
    {
        $cancellationResult = null;
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("authLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("authLine-2", "One orderline added with addOrderLine", 100, 25);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(0);

        $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
        $this->rb->paymentFinalize($paymentId);

        $this->rb->setAfterShopInvoiceExtRef("Test Testsson");
        $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);

        $newArray = [
            [
                'artNo' => 'authLine-2',
                'description' => "One orderline added with addOrderLine",
                'unitAmountWithoutVat' => 100,
                'vatPct' => 25,
                'quantity' => 2,
            ],
            [
                'artNo' => 'nonExistentValidatedAndRemovedArticle',
                'description' => 'This article will never reach the cancellation rowspec',
                'unitAmountWithoutVat' => 200,
                'vatPct' => 25,
                'quantity' => 2,
            ],
        ];

        try {
            $cancellationResult = $this->rb->paymentCancel($paymentId, $newArray);
        } catch (\Exception $somethingWentWrongException) {
            static::markTestSkipped($somethingWentWrongException->getMessage());
        }
        $result = $this->rb->getPaymentSpecCount($paymentId);
        static::assertTrue($cancellationResult && $result['AUTHORIZE'] == 4 && $result['DEBIT'] == 2 && $result['CREDIT'] == 1 && $result['ANNUL'] == 1);
    }

    /**
     * @throws \Exception
     */
    function testBitMaskSanitizer()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("authLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("authLine-2", "One orderline added with addOrderLine", 100, 25);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(3);

        //$this->resetConnection();
        $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
        try {
            $this->rb->paymentFinalize($paymentId);

            //$this->resetConnection();
            $this->rb->addOrderLine("authLine-1", "One orderline added with addOrderLine", 100, 25);
            $this->rb->addOrderLine("authLine-2", "One orderline added with addOrderLine", 100, 25);
            $this->rb->paymentAnnul($paymentId);
        } catch (\Exception $e) {
            if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                if (!defined('SKIP_TEST')) {
                    define('SKIP_TEST', ['skip' => 'aftershop']);
                }
                static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
            } else {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

        }

        $remainArray = $this->rb->sanitizeAfterShopSpec($paymentId,
            (RESURS_AFTERSHOP_RENDER_TYPES::ANNUL + RESURS_AFTERSHOP_RENDER_TYPES::CREDIT));
        static::assertCount(2, $remainArray);
    }

    /**
     * Test: Behave strange
     *
     * Expected result:
     *  Weird actions in the aftershop flow.
     *      - Create order
     *      - Debit two rows
     *      - Credit the same rows that you've just debited (payment admin: moves the debit to credit)
     *      - Now annul the same rows that you've just credited (payment admin: adds an annulment on the same rows)
     *
     * @throws \Exception
     */
    function testAfterShopFaultyDebitAnnul()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("authLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("authLine-2", "One orderline added with addOrderLine", 100, 25);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(0);

        $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
        try {
            $this->rb->paymentFinalize($paymentId);

            $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
            $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
            $this->rb->paymentCredit($paymentId);

            $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
            $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
            $this->rb->paymentAnnul($paymentId);
            $paymentSpecCount = $this->rb->getPaymentSpecCount($paymentId);
            static::assertTrue($paymentSpecCount['AUTHORIZE'] == 4 && $paymentSpecCount['DEBIT'] == 2 && $paymentSpecCount['CREDIT'] == 2 && $paymentSpecCount['ANNUL'] == 2);
        } catch (\Exception $e) {
            if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                if (!defined('SKIP_TEST')) {
                    define('SKIP_TEST', ['skip' => 'aftershop']);
                }
                static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
            } else {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * @throws \Exception
     */
    function testAfterShopFaultyDebitAnnulOldMerge()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $this->rb->setFlag("MERGEBYSTATUS_DEPRECATED_METHOD");
        $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("authLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("authLine-2", "One orderline added with addOrderLine", 100, 25);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(0);

        try {
            $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
            $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
            $this->rb->paymentFinalize($paymentId);

            $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
            $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
            $this->rb->paymentCredit($paymentId);

            $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
            $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
            $this->rb->paymentAnnul($paymentId);
            $paymentSpecCount = $this->rb->getPaymentSpecCount($paymentId);
            static::assertTrue($paymentSpecCount['AUTHORIZE'] == 4 && $paymentSpecCount['DEBIT'] == 2 && $paymentSpecCount['CREDIT'] == 2 && $paymentSpecCount['ANNUL'] == 2);
        } catch (\Exception $e) {
            if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                if (!defined('SKIP_TEST')) {
                    define('SKIP_TEST', ['skip' => 'aftershop']);
                }
                static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
            } else {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * Test: Behave strange
     *
     * Expected result:
     *  Weird actions in the aftershop flow.
     *      - Create order
     *      - Debit two rows
     *      - Annul the same rows that you've just debited (payment admin: moves the debit to credit)
     *      - Now credit the same rows that you've just credited (payment admin: adds an annulment on the same rows)
     *
     * @throws \Exception
     */
    function testAfterShopFaultyContraryDirection()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("authLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("authLine-2", "One orderline added with addOrderLine", 100, 25);
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(0);

        $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
        $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
        try {
            $this->rb->paymentFinalize($paymentId);

            $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
            $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
            $this->rb->paymentAnnul($paymentId);

            $this->rb->addOrderLine("debitLine-1", "One orderline added with addOrderLine", 100, 25);
            $this->rb->addOrderLine("debitLine-2", "One orderline added with addOrderLine", 100, 25);
            $this->rb->paymentCredit($paymentId);

            $paymentSpecCount = $this->rb->getPaymentSpecCount($paymentId);
            static::assertTrue($paymentSpecCount['AUTHORIZE'] == 4 && $paymentSpecCount['DEBIT'] == 2 && $paymentSpecCount['CREDIT'] == 2 && $paymentSpecCount['ANNUL'] == 2);
        } catch (\Exception $e) {
            if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                if (!defined('SKIP_TEST')) {
                    define('SKIP_TEST', ['skip' => 'aftershop']);
                }
                static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
            } else {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

        }
    }

    /**
     * Test returning of payment methods
     *
     * @throws \Exception
     */
    public function testSimplifiedPsp()
    {
        // Get first list of methods - this should return nonPSP methods
        $firstMethodList = $this->rb->getPaymentMethods();
        $this->rb->setSimplifiedPsp(true);

        // Get second list of methods - this should return all methods
        $secondMethodList = $this->rb->getPaymentMethods();

        // First: If the count above is mismatching, the current test account probably don't have the proper set of payment methods
        if (count($firstMethodList) != count($secondMethodList)) {
            // Now, set up so that simplified flow can see everything
            $this->rb->setSimplifiedPsp(true);

            // Now, force EComPHP into strict mode, so that noone can see the payment methods
            $this->rb->setStrictPsp(true);

            // This request should not only return nonPSP-methods
            $thirdMethodList = $this->rb->getPaymentMethods();

            // Assert diff
            static::assertTrue(count($secondMethodList) != count($thirdMethodList));
        } else {
            static::markTestSkipped("Current account does not have any PSP methods");
        }
    }

    /**
     * @throws \Exception
     */
    public function testGitTags()
    {
        $tagVersions = $this->rb->getVersionsByGitTag();
        $currentTag = array_pop($tagVersions);
        $lastTag = array_pop($tagVersions);
        $notCurrent = $this->rb->getIsCurrent($lastTag);
        $perfect = $this->rb->getIsCurrent($currentTag);
        static::assertTrue($notCurrent === false && $perfect === true);
    }

    /**
     * @throws \Exception
     */
    public function testHostedCountryCode()
    {
        $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::HOSTED_FLOW);
        $this->rb->setBillingAddress(
            "Given Name",
            "Given",
            "Name",
            "Address row 1",
            "",
            "Location",
            "12345",
            "SE"
        );
        $this->addRandomOrderLine();
        $payloadResult = $this->rb->getPayload();
        static::assertTrue(isset($payloadResult['customer']['address']['countryCode']) && $payloadResult['customer']['address']['countryCode'] == "SE");
    }

    public function testOldEnvironmentClass()
    {
        static::assertTrue(RESURS_ENVIRONMENTS::ENVIRONMENT_TEST === 1);
    }

    /**
     * @throws \Exception
     */
    public function testBasicOrderStatusFinalizationEvent()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1, 1, 1000, 2000);
        try {
            $this->rb->paymentFinalize($paymentId);
            static::assertTrue($this->rb->getOrderStatusByPayment($paymentId,
                    RESURS_CALLBACK_TYPES::FINALIZATION) === RESURS_PAYMENT_STATUS_RETURNCODES::PAYMENT_COMPLETED);
        } catch (\Exception $e) {
            if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                if (!defined('SKIP_TEST')) {
                    define('SKIP_TEST', ['skip' => 'aftershop']);
                }
                static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
            } else {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function testBasicOrderStatusFinalizationEventSynchronous()
    {
        $this->rb->setFinalizeIfBooked();
        $this->rb->setAnnulIfFrozen();
        $this->rb->setWaitForFraudControl();
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1, 1, 1000, 2000);
        //$this->rb->paymentFinalize( $paymentId );
        static::assertTrue($this->rb->getOrderStatusByPayment($paymentId,
                RESURS_CALLBACK_TYPES::FINALIZATION) === RESURS_PAYMENT_STATUS_RETURNCODES::PAYMENT_COMPLETED);
    }

    /**
     * @throws \Exception
     */
    public function testBasicOrderStatusAnnulEvent()
    {
        if ($this->isSkip('aftershop')) {
            static::markTestSkipped("External configuration marked " . __FUNCTION__ . " this as skippable.");
        }
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1);
        try {
            $this->rb->paymentAnnul($paymentId);
            static::assertTrue($this->rb->getOrderStatusByPayment($paymentId,
                    RESURS_CALLBACK_TYPES::ANNULMENT) === RESURS_PAYMENT_STATUS_RETURNCODES::PAYMENT_ANNULLED);
        } catch (\Exception $e) {
            if ($e->getCode() == RESURS_EXCEPTIONS::ECOMMERCEERROR_ALREADY_EXISTS_INVOICE_ID) {
                if (!defined('SKIP_TEST')) {
                    define('SKIP_TEST', ['skip' => 'aftershop']);
                }
                static::markTestSkipped('Test skipped due to ALREADY_EXISTS_INVOICE_ID (did someone reindex elastic?)');
            } else {
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function testBookedCallbackByDelayedCustomer()
    {
        // Unfreeze after 10m
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1, 1, 1000, 2000, '198101010000');
        static::assertTrue($this->rb->getOrderStatusByPayment($paymentId,
                RESURS_CALLBACK_TYPES::BOOKED) == RESURS_PAYMENT_STATUS_RETURNCODES::PAYMENT_PENDING);
    }

    /**
     * @throws \Exception
     */
    public function testUnfreezeCallbackByHappyFlow()
    {
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1, 1, 1000, 2000, '198305147715');
        static::assertTrue($this->rb->getOrderStatusByPayment($paymentId,
                RESURS_CALLBACK_TYPES::UNFREEZE) == RESURS_PAYMENT_STATUS_RETURNCODES::PAYMENT_PROCESSING);
    }

    /**
     * @throws \Exception
     */
    public function testAutoFraudCallbackByHappyFlow()
    {
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1, 1, 1000, 2000, '198305147715');
        static::assertTrue($this->rb->getOrderStatusByPayment($paymentId,
                RESURS_CALLBACK_TYPES::AUTOMATIC_FRAUD_CONTROL,
                'THAWED') == RESURS_PAYMENT_STATUS_RETURNCODES::PAYMENT_PROCESSING);
    }

    /**
     * @throws \Exception
     */
    public function testAutoFraudCallbackByFrozenFlow()
    {
        $paymentId = $this->getPaymentIdFromOrderByClientChoice(1, 1, 1000, 2000, '198209123705');
        static::assertTrue($this->rb->getOrderStatusByPayment($paymentId,
                RESURS_CALLBACK_TYPES::AUTOMATIC_FRAUD_CONTROL,
                'FROZEN') == RESURS_PAYMENT_STATUS_RETURNCODES::PAYMENT_PENDING);
    }

    /**
     * @throws \Exception
     */
    function testUnAuthorized()
    {
        $newRb = new ResursBank("fail", "fail");
        try {
            print_r($newRb->getPaymentMethods());
        } catch (\Exception $e) {
            static::assertTrue($e->getCode() == 401);
        }
    }

    /**
     * Test setting setFinalizeIfBooked, setAnnulIfFrozen and waitForFraudControl with internal methods (hostedFlow)
     *
     * @throws \Exception
     */
    public function testHostedThreeFlags()
    {
        $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::HOSTED_FLOW);
        $this->rb->setBillingAddress(
            "Given Name",
            "Given",
            "Name",
            "Address row 1",
            "",
            "Location",
            "12345",
            "SE"
        );
        $this->addRandomOrderLine();
        $this->rb->setFinalizeIfBooked();
        $this->rb->setAnnulIfFrozen();
        $this->rb->setWaitForFraudControl();

        $payloadResult = $this->rb->getPayload();
        static::assertTrue(isset($payloadResult['waitForFraudControl']) && isset($payloadResult['annulIfFrozen']) && isset($payloadResult['finalizeIfBooked']));
    }

    /**
     * Test setting setFinalizeIfBooked, setAnnulIfFrozen and waitForFraudControl with internal methods (simplifiedFlow)
     *
     * @throws \Exception
     */
    public function testSimplifiedThreeFlags()
    {
        $this->rb->setPreferredPaymentFlowService(RESURS_FLOW_TYPES::SIMPLIFIED_FLOW);
        $this->rb->setBillingAddress(
            "Given Name",
            "Given",
            "Name",
            "Address row 1",
            "",
            "Location",
            "12345",
            "SE"
        );
        $this->addRandomOrderLine();
        $this->rb->setFinalizeIfBooked();
        $this->rb->setAnnulIfFrozen();
        $this->rb->setWaitForFraudControl();

        $payloadResult = $this->rb->getPayload();
        static::assertTrue(isset($payloadResult['paymentData']['waitForFraudControl']) && isset($payloadResult['paymentData']['annulIfFrozen']) && isset($payloadResult['paymentData']['finalizeIfBooked']));
    }

    /**
     * Test netcurl 6.0.15 SOAPWARNINGS flag
     *
     * @throws \Exception
     */
    public function testCredentialFailure()
    {
        $isolatedRB = new ResursBank("fail", "fail");
        try {
            $isolatedRB->getPaymentMethods();
        } catch (\Exception $failException) {
            $exceptionMessage = $failException->getMessage();
            $authTest = (preg_match("/401 unauthorized/i", $exceptionMessage) ? true : false);
            static::assertTrue($authTest);
        }
    }

    public function testGetSaltKeyDeprecated()
    {
        static::assertTrue(strlen($this->rb->getSaltKey()) > 0);
    }

    public function testGetSaltByCrypto()
    {
        static::assertTrue(strlen($this->rb->getSaltByCrypto(3, 128)) == 128);
    }

    public function testWcMtRandEntropyKeyGen()
    {
        static::assertTrue(strlen(uniqid(mt_rand(), true)) <= 35);
    }

    /**
     * @throws \Exception
     */
    public function testLastPayload()
    {
        $this->getPaymentIdFromOrderByClientChoice(1, 1, 1000, 2000, '198101010000');
        $poppedPayload = $this->rb->getPayload(true);
        static::assertTrue(isset($poppedPayload['Payload']) && isset($poppedPayload['SpecLines']));
    }
}
