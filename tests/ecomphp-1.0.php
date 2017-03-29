<?php

/**
 * Resursbank API Loader Tests
 *
 * @package EcomPHPTest
 * @author Resurs Bank Ecommrece <ecommerce.support@resurs.se>
 * @version 0.2alpha
 * @link https://test.resurs.com/docs/x/KYM0 Get started - PHP Section
 * @license -
 *
 */

/**
 * Load EcomPHP
 */
require_once('../source/classes/rbapiloader.php');

/**
 * Class ResursBankTest: Primary test client
 *
 */
class ResursBankTest extends PHPUnit_Framework_TestCase
{

    /**
     * Resurs Bank API Gateway, PHPUnit Test Client
     *
     * @subpackage EcomPHPClient
     */

    public $ignoreDefaultTests = false;
    public $ignoreBookingTests = false;
    public $ignoreSEKKItests = false;

    private $alwaysUseExtendedCustomer = true;

    /** @var string Username to web services */
    public $username = "";
    /** @var string Similar username, but for nonmock */
    public $usernameNonmock = "";

    /** @var string Password to web services */
    public $password = "";
    /** @var string Similar password, but for nonmock */
    public $passwordNonmock = "";

    /** @var string Used as callback urls */
    public $callbackUrl = "http://mock.phpapi.cte.loc/";

    /** @var string Where to redirect signings, when done */
    public $signUrl = "http://mock.phpapi.cte.loc/signdummy.php";

    /** @var string Default username for tests (SE) */
    public $usernameSweden = "";
    /** @var string Default password for tests (SE) */
    public $passwordSweden = "";

    /** @var string Default username for tests (NO) */
    public $usernameNorway = "";
    /** @var string Default password for tests (NO) */
    public $passwordNorway = "";

    /** @var string Test with natural government id */
    public $govIdNatural = "198305147715";
    /** @var string Test with natural government id/Norway */
    public $govIdNaturalNorway = "180872-48794";
    /** @var string Government id that will fail */
    public $govIdNaturalDenied = "195012026430";

    /** @var string Test with civic number (legal) */
    public $govIdLegalCivic = "198305147715";
    /** @var string Test with civic number (legal/Norway) */
    public $govIdLegalCivicNorway = "180872-48794";

    /** @var string Used for testing card-bookings  (9000 0000 0002 5000 = 25000) */
    public $cardNumber = "9000000000025000";
    /** @var string Government id for the card */
    public $cardGovId = "194608282333";

    /** @var string Test with organization number (legal) */
    public $govIdLegalOrg = "166997368573";

    /** @var string Test with denied organization number (legal) */
    public $govIdLegalOrgDenied = "169468958195";

    /** @var null If none, use natural. If legal, enter LEGAL */
    public $customerType = null;

    private $chosenCountry = "SE";

    /** @var string Selected government id */
    private $testGovId = "";

    /** @var string Selected government id for norway */
    private $testGovIdNorway = "";

    private $zeroSpecLine = false;
    private $zeroSpecLineZeroTax = false;

    /** @var array Available methods for test (SE) */
    public $availableMethods = array();

    /** @var array Available methods for test (NO) */
    public $availableMethodsNorway = array();

    /** @var bool Wait for fraud control to take place in a booking */
    public $waitForFraudControl = false;


    /**
     * Disabling of callback registrations. Created during issues in nonMock where "ombudsadmin" broke down. To avoid issues with current autotest environments
     * we created the ability to disable callbacks, which is a key function for orders to receive callbacks properly (due to the salt keys). With those two functions
     * we are able to disable new registrations of callbacks, so that we can borrow a different representative id, during errors that actually requires functioning callbacks.
     */

    /** @var bool Disable callback registration in mocked environment */
    public $disableCallbackRegMock = false;
    /** @var bool Disable callback registration in nonmocked environment */
    public $disableCallbackRegNonMock = false;

    /** @var array Alerts: Array of mail-receivers */
    public $alertReceivers = array();
    /** @var string Alerts: Name of sender */
    public $alertFrom = array();
    /** @var null Ignore this */
    public $alertMessage = null;
    /** @var string Defines what environment should be running */
    public $environmentName = "mock";
    /** @var array Expected payment method count (SE) */
    public $paymentMethodCount = array(
        'mock' => 5,
        'nonmock' => 5
    );
    /** @var array Expected payment method cound (NO) */
    public $paymentMethodCountNorway = array(
        'mock' => 3
    );

    // This password is for cte/nonmock but works perfectly if you need to test failures
    //public $password = "cz84Hl6DxQ";

    /** @var null|ResursBank API Connector */
    private $rb = null;

    /** Before each test, invoke this */
    public function setUp(){ }
    /** After each test, invoke this */
    public function tearDown() {}

    /**
     * Prepare by initializing API Loader and stubs
     *
     */
    public function __construct() {
        register_shutdown_function(array($this, 'shutdownSuite'));
        if ($this->environmentName === "nonmock") {
            $this->username = $this->usernameNonmock;
            $this->password = $this->passwordNonmock;
        }

        $this->setupConfig();

        /* Set up default government id for bookings */
        $this->testGovId = $this->govIdNatural;
        $this->testGovIdNorway = $this->govIdNaturalNorway;
        $this->initServices();
    }

    private function setupConfig() {
        if (file_exists('test.json')) {
            $config = json_decode(file_get_contents("test.json"));
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
            if (isset($config->norway->username)) {
                $this->usernameNorway = $config->norway->username;
            }
            if (isset($config->norway->password)) {
                $this->passwordNorway = $config->norway->password;
            }
            if (isset($config->nonmock->username)) {
                $this->usernameNonmock = $config->nonmock->username;
            }
            if (isset($config->nonmock->password)) {
                $this->passwordNonmock = $config->nonmock->password;
            }
            if (isset($config->alertReceivers) && is_array($config->alertReceivers)) {
                $this->alertReceivers = $config->alertReceivers;
            }
            if (isset($config->alertFrom) && is_array($config->alertFrom)) {
                $this->alertFrom = $config->alertFrom;
            }
            if (isset($config->availableMethods)) {
                foreach ($config->availableMethods as $methodId => $methodObject) {
                     $this->availableMethods[$methodId] = $methodObject;
                }
            }
            if (isset($config->availableMethodsNorway)) {
                foreach ($config->availableMethodsNorway as $methodId => $methodObject) {
                     $this->availableMethodsNorway[$methodId] = $methodObject;
                }
            }
        }
    }

    private function initServices() {
        $this->rb = new \ResursBank($this->username, $this->password);
    }

    /** Switchover abilities for unit */
    private function checkEnvironment() {
        if ($this->environmentName === "nonmock") { $this->rb->setNonMock(); }
    }

    /**
     * When suite is about to shut down, run a collection of functions before completion.
     */
    public function shutdownSuite() {
        $this->alertSender();
    }

    /**
     * Check if environment is working by making a getPaymentMethods-call.
     *
     * @return bool If everything works, we get our payment methods and returns true. All exceptions says environment is down.
     */
    private function isUp() {
        $hasError = true;
        try {
            $paymentMethods = $this->rb->getPaymentMethods();
        } catch (Exception $e) {
            $hasError = false;
        }
        if (count($paymentMethods) > 0) {
            return true;
        }
        return $hasError;
    }

    /**
     * Send mail alerts to defined users in case of special errors
     */
    private function alertSender() {
        $checkMessage = trim($this->alertMessage);
        if (!empty($checkMessage)) {
            //$message = 'Following problems occured during the running of PHPApi TestSuite:' . "\n" . $this->alertMessage;
            $message = trim($this->alertMessage);
            foreach ($this->alertReceivers as $receiver) {
                mail($receiver, "PHPApi TestSuite Alert [".$this->environmentName."]", $message, "From: " . $this->alertFrom . "\nContent-Type: text/plain");
            }
        }
        //$this->alertMessage = null;
    }

    /**
     * Prepare a compiled message to send, on errors
     *
     * @param string $message A message to render for alerts (experimental)
     */
    private function alertRender($message = "") {
        if (!empty($message)) {
            $this->alertMessage .= $message . "\n";
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
        $characterListArray = array(
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '0123456789',
            '!@#$%*?'
        );
        $chars = array();
        $max = 10; // This is for now static
        foreach ($characterListArray as $charListIndex => $charList) {for ($i = 0 ; $i <= ceil($max/sizeof($characterListArray)) ; $i++) {$chars[] = $charList{mt_rand(0, (strlen($charList) - 1))};}}
        shuffle($chars);
        $retp = implode("", $chars);
        return $retp;
    }

    /**
     * Randomly pick up a payment method (name) from current representative.
     *
     * @return mixed
     * @throws Exception
     */
    private function getAMethod() {
        $methods= null;
        $currentError = null;
        try {
            $methods = $this->rb->getPaymentMethods();
        } catch (Exception $e) {
            $currentError = $e->getMessage();
        }
        if (is_array($methods)) {
            $method = array_pop($methods);
            $id = $method->id;
            return $id;
        }
        throw new Exception("Cannot receive a random payment method from ecommerce" . (!empty($currentError) ? " ($currentError)":""));
    }

    public function getSpecLine($specialSpecline = array()) {
        if (count($specialSpecline)) {
            return $specialSpecline;
        }
        return array(
            'artNo' => 'EcomPHP-testArticle-' . rand(1,1024),
            'description' => 'EComPHP Random Test Article number ' . rand(1,1024),
            'quantity' => 1,
            'unitAmountWithoutVat' => intval(rand(1000,10000)),
            'vatPct' => 25
        );
    }
    public function getSpecLineZero($specialSpecline = array(), $zeroTax = false) {
        if (count($specialSpecline)) {
            return $specialSpecline;
        }
        return array(
            'artNo' => 'EcomPHP-testArticle-' . rand(1,1024),
            'description' => 'EComPHP Random Test Article number ' . rand(1,1024),
            'quantity' => 1,
            'unitAmountWithoutVat' => 0,
            'vatPct' => $zeroTax ? 0 : 25
        );
    }

    /**
     * Book a payment, internal function
     *
     * @param string $setMethod The payment method to use
     * @param bool|false $bookSuccess Set to true if booking is supposed to success
     * @param bool|false $forceSigning Set to true if signing is forced
     * @param bool|true $signSuccess True=Successful signing, False=Failed signing
     * @return bool Returning true if booking went as you expected
     */
    private function doBookPayment($setMethod = '', $bookSuccess = true, $forceSigning = false, $signSuccess = true, $country = 'SE', $ownSpecline = array()) {
        $this->setCountry($country);
        $this->checkEnvironment();
        $useMethodList = $this->availableMethods;
        $useGovIdLegalCivic = $this->govIdLegalCivic;
        $useGovId = $this->testGovId;
        $usePhoneNumber = "0101010101";

        if ($country == "NO") {
            if (count($this->availableMethodsNorway) && !empty($this->usernameNorway)) {
                $useMethodList = $this->availableMethodsNorway;
                $useGovIdLegalCivic = $this->govIdLegalCivicNorway;
                $useGovId = $this->testGovIdNorway;
                $usePhoneNumber = "+4723456789";
            } else {
                $this->markTestIncomplete();
            }
        } else if ($country == "SE") {
            if (!count($this->availableMethods) || empty($this->username)) {
                $this->markTestIncomplete();
            }
        }

        /* Set unit amount higher (than 500 as before) so we may pass boundaries in tests */
        //$bookData['type'] = "hosted";
        if ($this->zeroSpecLine) {
            if (!$this->zeroSpecLineZeroTax) {
                $bookData['specLine'] = $this->getSpecLineZero();
            } else {
                $bookData['specLine'] = $this->getSpecLineZero(array(), true);
            }
        } else {
            $bookData['specLine'] = $this->getSpecLine();
        }
        $this->zeroSpecLine = false;
        $bookData['address'] = array(
            'fullName' => 'Test Testsson',
            'firstName' => 'Test',
            'lastName' => 'Testsson',
            'addressRow1' => 'Testgatan 1',
            'postalArea' => 'Testort',
            'postalCode' => '12121',
            'country' => 'SE'
        );
        $bookData['customer'] = array(
            'governmentId' => $useGovId,
            'phone' => $usePhoneNumber,
            'email' => 'noreply@resurs.se',
            'type' => 'NATURAL'
        );
        if (isset($useMethodList['invoice_legal']) && $setMethod == $useMethodList['invoice_legal']) {
            $bookData['customer']['contactGovernmentId'] = $useGovIdLegalCivic;
            $bookData['customer']['type'] = 'LEGAL';
        }
        if (isset($useMethodList['card']) && $setMethod == $useMethodList['card']) {
            $useGovId = $this->cardGovId;
            $this->rb->prepareCardData($this->cardNumber, false);
        }
        if (isset($useMethodList['card_new']) && $setMethod == $useMethodList['card_new']) {
            $useGovId = $this->cardGovId;
            $this->rb->prepareCardData(null, true);
        }
        $bookData['paymentData']['waitForFraudControl'] = $this->waitForFraudControl;
        $bookData['signing'] = array(
            'successUrl' => $this->signUrl . '/?success=true',
            'failUrl' => $this->signUrl . "/?success=false",
            'forceSigning' => $forceSigning
        );

        /* keepReturnObject is false by default */
        //$bookStatus = $res->return->bookPaymentStatus;

        $res = $this->rb->bookPayment($setMethod, $bookData);
        $bookStatus = $res->bookPaymentStatus;

        if ($bookStatus == "SIGNING") {

            if ($this->environmentName === "mock") {

                /* Pick up the signing url */
                $signUrl = $res->signingUrl;
                $getSigningPage = file_get_contents($signUrl);
                $Network = new \TorneLIB\TorneLIB_Network();
                $signUrlHostInfo = $Network->getUrlDomain($signUrl);
                $getUrlHost = $signUrlHostInfo[1] . "://" . $signUrlHostInfo[0];
                $mockSuccessUrl = $getUrlHost . "/" . preg_replace('/(.*?)\<a href=\"(.*?)\">(.*?)\>Mock success(.*)/is', '$2', $getSigningPage);
                $getSuccessContent = json_decode(file_get_contents($mockSuccessUrl));
                if ($getSuccessContent->success == "true") { if ($signSuccess) { return true; } else {return false;} }
                if ($getSuccessContent->success == "false") { if (!$signSuccess) { return true; } else {return false;} }
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

    /** Setup a country for webservices */
    private function setCountry($country = 'SE') {
        if ($country == "SE") {
            $this->username = $this->usernameSweden;
            $this->password = $this->passwordSweden;
        } elseif ($country == "NO") {
            $this->username = $this->usernameNorway;
            $this->password = $this->passwordNorway;
        }
        /* Re-Initialize services if country has changed */
        if ($this->chosenCountry != $country) {
            $this->initServices();
        }
        $this->chosenCountry = $country;
    }


    /*********** TESTS ************/

    /**
     * Test if environment is ok
     */
    public function testGetEnvironment() {
        if ($this->ignoreDefaultTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $this->assertTrue($this->isUp() === true);
    }

    /**
     * Testing of callbacks
     */
    public function testCallbacks() {
        if ($this->ignoreDefaultTests) { $this->markTestIncomplete(); }
        /* If disabled */
        if ($this->disableCallbackRegMock || ($this->disableCallbackRegNonMock && $this->environmentName === "nonmock")) {
            $this->assertTrue(1==1);
            return;
        }
        $this->checkEnvironment();

        $parameter = array(
            'ANNULMENT' => array('paymentId'),
            'AUTOMATIC_FRAUD_CONTROL' => array('paymentId', 'result'),
            'FINALIZATION' => array('paymentId'),
            'UNFREEZE' => array('paymentId')
        );

        foreach ($parameter as $callbackType => $parameterArray) {
            $digestSaltString = $this->mkpass();
            $digestArray = array(
                'digestSalt' => $digestSaltString,
                'digestParameters' => $parameterArray
            );
            if ($callbackType == "ANNULMENT") {$setCallbackType = ResursCallbackTypes::ANNULMENT;}
            if ($callbackType == "AUTOMATIC_FRAUD_CONTROL") {$setCallbackType = ResursCallbackTypes::AUTOMATIC_FRAUD_CONTROL;}
            if ($callbackType == "FINALIZATION") {$setCallbackType = ResursCallbackTypes::FINALIZATION;}
            if ($callbackType == "UNFREEZE") {$setCallbackType = ResursCallbackTypes::UNFREEZE;}
            $renderArray = array();
            if (is_array($parameterArray)) {
                foreach ($parameterArray as $parameterName) {
                    $renderArray[] = $parameterName . "={".$parameterName."}";
                }
            }
            $callbackURL = $this->callbackUrl . "?event=".$callbackType."&digest={digest}&" . implode("&", $renderArray);
            try {
                $callbackSetResult = $this->rb->setCallback($setCallbackType, $callbackURL, $digestArray);
                if (!empty($this->rb->lastError)) { continue; }
                if ($callbackSetResult) { $callbackSaveData[$callbackType] = array('salt' => $digestSaltString); }
            }
            catch (Exception $regCallbackException)
            {

            }
        }
        // Registered callbacks must be as many as the above parameters (preferrably 4)
        $this->assertTrue(count($callbackSaveData) == count($parameter));
    }

    /**
     * Test if payment methods works properly
     */
    public function testGetPaymentMethods()
    {
        if ($this->ignoreDefaultTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $paymentMethods = $this->rb->getPaymentMethods();
        if (!count($paymentMethods)) {
            $this->alertRender("No payment methods received from ecommerce");
        }
        $this->assertTrue(count($paymentMethods) > 0);
    }

    /**
     * Make sure that all payment methods set up for the representative is there
     */
    public function testGetPaymentMethodsAll() {
        if ($this->ignoreDefaultTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $paymentMethods = $this->rb->getPaymentMethods();
        if (count($paymentMethods) !== $this->paymentMethodCount[$this->environmentName]) {
            $this->alertRender("Payment method mismatch - got " . count($paymentMethods) . ", expected 5.");
        }
        $this->assertTrue(count($paymentMethods) === $this->paymentMethodCount[$this->environmentName]);
    }

    /**
     * Just like testGetPaymentMethodsAll, but converted to an array with the internal function objectsIntoArray
     */
    public function testGetPaymentMethodsArray() {
        if ($this->ignoreDefaultTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $paymentMethods = $this->rb->getPaymentMethodsArray();
        if (count($paymentMethods) !== $this->paymentMethodCount[$this->environmentName]) {
            $this->alertRender("Payment method mismatch - got " . count($paymentMethods) . ", expected 5.");
        }
        $this->assertTrue(count($paymentMethods) === $this->paymentMethodCount[$this->environmentName]);
    }
    /**
     * getAddress, NATURAL
     */
    public function testGetAddressNatural() {
        if ($this->ignoreDefaultTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $getAddressData = array();
        try {
            $getAddressData = $this->rb->getAddress($this->govIdNatural, 'NATURAL', '127.0.0.1');
        } catch (Exception $e) {}
        $this->assertTrue(!empty($getAddressData->fullName));
    }
    /**
     * getAddress, LEGAL, Civic number
     */
    public function testGetAddressLegalCivic() {
        if ($this->ignoreDefaultTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $getAddressData = array();
        try {
            $getAddressData = $this->rb->getAddress($this->govIdLegalCivic, 'LEGAL', '127.0.0.1');
        } catch (Exception $e) {}
        $this->assertTrue(!empty($getAddressData->fullName));
    }
    /**
     * getAddress, LEGAL, Organization number
     */
    public function testGetAddressLegalOrg() {
        if ($this->ignoreDefaultTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $getAddressData = array();
        try {
            $getAddressData = $this->rb->getAddress($this->govIdLegalOrg, 'LEGAL', '127.0.0.1');
        } catch (Exception $e) {}
        $this->assertTrue(!empty($getAddressData->fullName));
    }
    /**
     * Testing of annuity factors (if they exist), with the first found payment method
     */
    public function testGetAnnuityFactors() {
        if ($this->ignoreDefaultTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $annuity = false;
        $methods = $this->rb->getPaymentMethods();
        if (is_array($methods)) {
            $method = array_pop($methods);
            $id = $method->id;
            $annuity = $this->rb->getAnnuityFactors($id);
        }
        $this->assertTrue(count($annuity) > 1);
    }

    /**
     * Test booking.
     * Payment Method: Invoice
     * Customer Type: NATURAL, GRANTED
     */
    public function testBookPaymentInvoiceNatural() {
        if ($this->ignoreBookingTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $bookResult = $this->doBookPayment($this->availableMethods['invoice_natural'], true, false, true);
        $this->assertTrue($bookResult);
    }

    /**
     * Test booking and always use extendedCustomer.
     * Payment Method: Invoice
     * Customer Type: NATURAL, GRANTED
     */
    public function testBookPaymentInvoiceExternalNatural() {
        if ($this->ignoreBookingTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $this->rb->alwaysUseExtendedCustomer = true;
        $bookResult = $this->doBookPayment($this->availableMethods['invoice_natural'], true, false, true);
        $this->assertTrue($bookResult);
    }

    /**
     * Book and see if there is a payment registered at Resurs Bank
     */
    public function testGetPayment() {
        if ($this->ignoreBookingTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $bookResult = $this->doBookPayment($this->availableMethods['invoice_natural'], true, false, true);
        $bookedPaymentId = $this->rb->getPreferredPaymentId();
        $payment = $this->rb->getPayment($bookedPaymentId);
        $this->assertTrue($bookResult && $payment->id == $bookedPaymentId);
    }

    /*
     * Test booking with zero amount
     * Expected result: Fail.
     */
    public function testBookPaymentZeroInvoiceNatural() {
        if ($this->ignoreBookingTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $this->zeroSpecLine = true;
        $hasException = false;
        try {
            $this->doBookPayment($this->availableMethods['invoice_natural'], true, false, true);
        }
        catch (Exception $exceptionWanted) {
            $hasException = true;
        }
        $this->assertTrue($hasException);
    }
    /**
     * Test booking.
     * Payment Method: Invoice
     * Customer Type: NATURAL, DENIED
     */
    public function testBookPaymentInvoiceNaturalDenied() {
        if ($this->ignoreBookingTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $this->testGovId = $this->govIdNaturalDenied;
        $bookResult = $this->doBookPayment($this->availableMethods['invoice_natural'], false, false, true);
        $this->assertTrue($bookResult);
    }

    /**
     * Test booking
     * Payment Method: Invoice
     * Customer Type: NATURAL, DENIED
     */
    public function testBookPaymentInvoiceLegal() {
        if ($this->ignoreBookingTests) { $this->markTestIncomplete(); }
        $this->username = $this->usernameSweden;
        $this->password = $this->passwordSweden;
        $this->checkEnvironment();
        $this->testGovId = $this->govIdLegalOrg;
        $bookResult = $this->doBookPayment($this->availableMethods['invoice_legal'], false, false, true);
        $this->assertTrue($bookResult);
    }

    /**
     * Test booking with a card
     */
    public function testBookPaymentCard() {
        if ($this->ignoreBookingTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $bookResult = $this->doBookPayment($this->availableMethods['card'], true, false, true, 'SE');
        $this->assertTrue($bookResult === true);
    }

    /**
     * Test booking with new card
     */
    public function testBookPaymentNewCard() {
        if ($this->ignoreBookingTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $bookResult = $this->doBookPayment($this->availableMethods['card_new'], true, false, true, 'SE');
        $this->assertTrue($bookResult === true);
    }

    /**
     * Test booking (NO).
     * Payment Method: Invoice
     * Customer Type: NATURAL, GRANTED
     */
    public function testBookPaymentInvoiceNaturalNorway() {
        if ($this->ignoreBookingTests) { $this->markTestIncomplete(); }
        $this->checkEnvironment();
        $bookResult = $this->doBookPayment($this->availableMethodsNorway['invoice_natural'], true, false, true, 'NO');
        $this->assertTrue($bookResult === true);
    }

    /**
     * Test chosen payment method sekki-generator
     * @throws Exception
     */
    public function testSekkiSimple() {
        if ($this->ignoreSEKKItests) { $this->markTestIncomplete(); }
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
        $this->assertTrue($matches === $appenders);
    }

    /**
     * Test pre-fetched sekki-url-generator
     * @throws Exception
     */
    public function testSekkiArray()
    {
        if ($this->ignoreSEKKItests) { $this->markTestIncomplete(); }
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
            $this->assertTrue($matches === $appenders);
        }
    }

    /**
     * Test all payment methods
     */
    public function testSekkiAll() {
        if ($this->ignoreSEKKItests) { $this->markTestIncomplete(); }
        $amount = rand(1000, 10000);
        $sekkiUrls = $this->rb->getSekkiUrls($amount);
        foreach ($sekkiUrls as $method => $sekkiUrls) {
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
        }
        $this->assertTrue($matches === $appenders);
    }

    /**
     * Test curstom url
     */
    public function testSekkiCustom()
    {
        if ($this->ignoreSEKKItests) { $this->markTestIncomplete(); }
        $amount = rand(1000, 10000);
        $URL = "https://test.resurs.com/customurl/index.html?content=true&secondparameter=true";
        $customURL = $this->rb->getSekkiUrls($amount, null, $URL);
        $this->assertTrue((preg_match("/amount=$amount/i", $customURL)? true:false));
    }
}

