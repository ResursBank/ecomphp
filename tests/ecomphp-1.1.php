<?php

/**
 * Resursbank API Loader Tests
 *
 * @package EcomPHPTest
 * @author Resurs Bank Ecommrece <ecommerce.support@resurs.se>
 * @version 0.3
 * @link https://test.resurs.com/docs/x/KYM0 Get started - PHP Section
 * @license -
 *
 */

require_once('../source/classes/rbapiloader.php');

use PHPUnit\Framework\TestCase;
use \Resursbank\RBEcomPHP\ResursBank;
use \Resursbank\RBEcomPHP\ResursAfterShopRenderTypes;
use \Resursbank\RBEcomPHP\ResursCallbackTypes;
use \Resursbank\RBEcomPHP\ResursMethodTypes;
use \Resursbank\RBEcomPHP\ResursCallbackReachability;

// Automatically set to test the pushCustomerUserAgent
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
	$_SERVER['HTTP_USER_AGENT'] = "EComPHP/Test-InternalClient";
}

/**
 * Class ResursBankTest: Primary test client
 */
class ResursBankTest extends TestCase
{
	/**
	 * Resurs Bank API Gateway, PHPUnit Test Client
	 *
	 * @subpackage EcomPHPClient
	 */

	/**
	 * The heart of this unit. To make tests "nicely" compatible with 1.1, this should be placed on top of this class as it looks different there.
	 */
	private function initServices($overrideUsername = null, $overridePassword = null) {
		if ( empty( $overrideUsername ) ) {
			$this->rb = new ResursBank( $this->username, $this->password );
		} else {
			$this->rb = new ResursBank( $overrideUsername, $overridePassword );
		}
		$this->rb->setPushCustomerUserAgent(true);
		$this->rb->setUserAgent("EComPHP/TestSuite");
		$this->rb->setDebug();
		/*
		 * If HTTP_HOST is not set, Resurs Checkout will not run properly, since the iFrame requires a valid internet connection (actually browser vs http server).
		 */
		if (!isset($_SERVER['HTTP_HOST'])) {
			$_SERVER['HTTP_HOST'] = "localhost";
		}
	}

	////////// Public variables
	public $ignoreDefaultTests = false;
	public $ignoreBookingTests = false;
	public $ignoreSEKKItests = false;
	public $ignoreUrlExternalValidation = false;
	/** @var array Alerts: Array of mail-receivers */
	public $alertReceivers = array();
	/** @var string Alerts: Name of sender */
	public $alertFrom = array();
	/** @var null Ignore this */
	public $alertMessage = null;
	/**
	 * Expected payment method count (SE)
	 * @var array
	 * @deprecated 1.1.12
	 */
	private $paymentMethodCount = array(
		'mock' => 5,
		'nonmock' => 5
	);
	/**
	 * Expected payment method cound (NO)
	 * @var array
	 * @deprecated 1.1.12
	 */
	private $paymentMethodCountNorway = array('mock' => 3);

	private $paymentIdAuthed = "20170519125223-9587503794";
	private $paymentIdAuthAnnulled = "20170519125725-8589567180";
	private $paymentIdDebited = "20170519125216-8830457943";

	private function isSpecialAccount() {
		$authed = $this->rb->getPayment($this->paymentIdAuthed);
		if (isset($authed->id)) {
			return true;
		}
		return false;
	}

	/** Before each test, invoke this */
	public function setUp()
	{
		$this->CURL = new \Resursbank\RBEcomPHP\Tornevall_cURL();
		$this->NETWORK = new \Resursbank\RBEcomPHP\TorneLIB_Network();

		if (version_compare(PHP_VERSION, '5.3.0', "<")) {
			if (!$this->allowObsoletePHP) {
				throw new \Exception("PHP 5.3 or later are required for this module to work. If you feel safe with running this with an older version, please see ");
			}
		}

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

	/** After each test, invoke this */
	public function tearDown()
	{
	}

	////////// Private variables

	/** @var string Defines what environment should be running */
	private $environmentName = "mock";
	/** @var null|ResursBank API Connector */
	private $rb = null;
	/** @var string Username to web services */
	private $username = "";
	/** @var string Similar username, but for nonmock */
	private $usernameNonmock = "";
	/** @var string Password to web services */
	private $password = "";
	/** @var string Similar password, but for nonmock */
	private $passwordNonmock = "";
	/** @var string Used as callback urls */
	private $callbackUrl = "";
	/** @var string Where to redirect signings, when done */
	private $signUrl = "";
	/** @var string Default username for tests (SE) */
	private $usernameSweden = "";
	/** @var string Default password for tests (SE) */
	private $passwordSweden = "";
	/** @var string Default username for tests (NO) */
	private $usernameNorway = "";
	/** @var string Default password for tests (NO) */
	private $passwordNorway = "";
	private $chosenCountry = "SE";
	/** @var string Selected government id */
	private $testGovId = "";
	/** @var string Selected government id for norway */
	private $testGovIdNorway = "";
	/** @var string Test with natural government id */
	private $govIdNatural = "198305147715";
	/** @var string Test with natural government id/Norway */
	private $govIdNaturalNorway = "180872-48794";
	/** @var string Government id that will fail */
	private $govIdNaturalDenied = "195012026430";
	/** @var string Test with civic number (legal) */
	private $govIdLegalCivic = "198305147715";
	/** @var string getAddress should receive this full name when using LEGAL */
	private $govIdLegalFullname = "Pilsnerbolaget HB";
	/** @var string Test with civic number (legal/Norway) */
	private $govIdLegalCivicNorway = "180872-48794";
	/** @var string Used for testing card-bookings  (9000 0000 0002 5000 = 25000) */
	private $cardNumber = "9000000000025000";
	/** @var string Government id for the card */
	private $cardGovId = "194608282333";
	/** @var string Test with organization number (legal) */
	private $govIdLegalOrg = "166997368573";
	/** @var string Test with denied organization number (legal) */
	private $govIdLegalOrgDenied = "169468958195";
	/** @var null If none, use natural. If legal, enter LEGAL */
	private $customerType = null;
	/** @var array Available methods for test (SE) */
	private $availableMethods = array();
	/** @var array Available methods for test (NO) */
	private $availableMethodsNorway = array();
	/** @var bool Wait for fraud control to take place in a booking */
	private $waitForFraudControl = false;
	/**
	 * Disabling of callback registrations. Created during issues in nonMock where "ombudsadmin" broke down. To avoid issues with current autotest environments
	 * we created the ability to disable callbacks, which is a key function for orders to receive callbacks properly (due to the salt keys). With those two functions
	 * we are able to disable new registrations of callbacks, so that we can borrow a different representative id, during errors that actually requires functioning callbacks.
	 */
	/** @var bool Disable callback registration in mocked environment */
	private $disableCallbackRegMock = false;
	/** @var bool Disable callback registration in nonmocked environment */
	private $disableCallbackRegNonMock = false;

	private $zeroSpecLine = false;
	private $zeroSpecLineZeroTax = false;
	private $alwaysUseExtendedCustomer = true;
	private $allowObsoletePHP = false;

	private function setupConfig()
	{
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
	 * Initialization of environment with ability to change into others.
	 */
	private function checkEnvironment()
	{
		$this->initServices();
	}

	/**
	 * Check if environment is working by making a getPaymentMethods-call.
	 *
	 * @return bool If everything works, we get our payment methods and returns true. All exceptions says environment is down.
	 */
	private function isUp()
	{
		try {
			$paymentMethods = $this->rb->getPaymentMethods();
		} catch (\Exception $e) {
			return false;
		}
		if (count($paymentMethods) > 0) {
			return true;
		}
	}

	/**
	 * Send mail alerts to defined users in case of special errors
	 */
	private function alertSender()
	{
		$checkMessage = trim($this->alertMessage);
		if (!empty($checkMessage)) {
			//$message = 'Following problems occured during the running of PHPApi TestSuite:' . "\n" . $this->alertMessage;
			$message = trim($this->alertMessage);
			foreach ($this->alertReceivers as $receiver) {
				mail($receiver, "PHPApi TestSuite Alert [" . $this->environmentName . "]", $message, "From: " . $this->alertFrom . "\nContent-Type: text/plain");
			}
		}
		//$this->alertMessage = null;
	}

	/**
	 * Prepare a compiled message to send, on errors
	 *
	 * @param string $message A message to render for alerts (experimental)
	 */
	private function alertRender($message = "")
	{
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
		);
		//'!@#$%*?'
		$chars = array();
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
	 * @throws Exception
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
	 * @return bool Returning true if booking went as you expected
	 */
	private function doBookPayment($setMethod = '', $bookSuccess = true, $forceSigning = false, $signSuccess = true, $country = 'SE', $ownSpecline = array()) {
		$this->setCountry( $country );
		$paymentServiceSet = $this->rb->getPreferredPaymentService();
		//$this->checkEnvironment();
		$useMethodList      = $this->availableMethods;
		$useGovIdLegalCivic = $this->govIdLegalCivic;
		$useGovId           = $this->testGovId;
		$usePhoneNumber     = "0101010101";
		$bookStatus         = null;

		if ( $country == "NO" ) {
			if ( count( $this->availableMethodsNorway ) && ! empty( $this->usernameNorway ) ) {
				$useMethodList      = $this->availableMethodsNorway;
				$useGovIdLegalCivic = $this->govIdLegalCivicNorway;
				$useGovId           = $this->testGovIdNorway;
				$usePhoneNumber     = "+4723456789";
			} else {
				$this->markTestIncomplete();
			}
		} else if ( $country == "SE" ) {
			if ( ! count( $this->availableMethods ) || empty( $this->username ) ) {
				$this->markTestIncomplete();
			}
		}
		if ( $this->zeroSpecLine ) {
			if ( ! $this->zeroSpecLineZeroTax ) {
				$bookData['specLine'] = $this->getSpecLineZero();
			} else {
				$bookData['specLine'] = $this->getSpecLineZero( array(), true );
			}
		} else {
			$bookData['specLine'] = $this->getSpecLine();
		}
		$this->zeroSpecLine   = false;
		$bookData['address']  = array(
			'fullName'    => 'Test Testsson',
			'firstName'   => 'Test',
			'lastName'    => 'Testsson',
			'addressRow1' => 'Testgatan 1',
			'postalArea'  => 'Testort',
			'postalCode'  => '12121',
			'country'     => 'SE'
		);
		$bookData['customer'] = array(
			'governmentId' => $useGovId,
			'phone'        => $usePhoneNumber,
			'email'        => 'noreply@resurs.se',
			'type'         => 'NATURAL'
		);
		if ( isset( $useMethodList['invoice_legal'] ) && $setMethod == $useMethodList['invoice_legal'] ) {
			$bookData['customer']['contactGovernmentId'] = $useGovIdLegalCivic;
			$bookData['customer']['type']                = 'LEGAL';
		}
		if ( isset( $useMethodList['card'] ) && $setMethod == $useMethodList['card'] ) {
			$useGovId = $this->cardGovId;
			//$this->rb->prepareCardData( $this->cardNumber, false );
			$this->rb->setCardData( $this->cardNumber );
		}
		if ( isset( $useMethodList['card_new'] ) && $setMethod == $useMethodList['card_new'] ) {
			$useGovId = $this->cardGovId;
			//$this->rb->prepareCardData( null, true );
			$this->rb->setCardData();
		}
		$bookData['paymentData']['waitForFraudControl'] = $this->waitForFraudControl;
		$bookData['signing']                            = array(
			'successUrl'   => $this->signUrl . '&success=true&preferredService=' . $this->rb->getPreferredPaymentService(),
			'failUrl'      => $this->signUrl . '&success=false&preferredService=' . $this->rb->getPreferredPaymentService(),
			'backUrl'      => $this->signUrl . '&success=backurl&preferredService=' . $this->rb->getPreferredPaymentService(),
			'forceSigning' => $forceSigning
		);
	
		if ($paymentServiceSet !== ResursMethodTypes::METHOD_CHECKOUT) {
			$res = $this->rb->createPayment($setMethod, $bookData);
			if ($paymentServiceSet == ResursMethodTypes::METHOD_HOSTED) {
				$domainInfo = $this->NETWORK->getUrlDomain($res);
				if (preg_match("/^http/i", $domainInfo[1])) {
					$hostedContent = $this->CURL->getResponseBody($this->CURL->doGet($res));
					return $hostedContent;
				}
			}
		} else {
			$res = $this->rb->createPayment($this->rb->getPreferredPaymentId(), $bookData);
		}

		/*
		 * bookPaymentStatus is for simplified flows only
		 */
		if (isset($res->bookPaymentStatus)) {
			$bookStatus = $res->bookPaymentStatus;
		}

		if ($paymentServiceSet == ResursMethodTypes::METHOD_CHECKOUT) {
			return $res;
		}

		if ($bookStatus == "SIGNING") {
			if ($this->environmentName === "mock") {
				/* Pick up the signing url */
				$signUrl = $res->signingUrl;
				$getSigningPage = file_get_contents($signUrl);
				$Network = new \Resursbank\RBEcomPHP\TorneLIB_Network();
				$signUrlHostInfo = $Network->getUrlDomain($signUrl);
				$getUrlHost = $signUrlHostInfo[1] . "://" . $signUrlHostInfo[0];
				$mockSuccessUrl = preg_replace("/\/$/", '', $getUrlHost . preg_replace('/(.*?)\<a href=\"(.*?)\">(.*?)\>Mock success(.*)/is', '$2', $getSigningPage));
				// Split up in case of test requirements
				$getPostCurlObject = $this->CURL->doPost($mockSuccessUrl);
				$getSuccessContent = $this->CURL->getParsedResponse($getPostCurlObject);
				if (isset($getSuccessContent->_GET->success)) {
					if ($getSuccessContent->_GET->success == "true") {
						if ($signSuccess) {
							return true;
						} else {
							return false;
						}
					}
					if ($getSuccessContent->_GET->success == "false") {
						if (!$signSuccess) {
							return true;
						} else {
							return false;
						}
					}
				} else {
					$this->markTestIncomplete("\$getSuccessContent does not contain any success-object.");
					return false;
				}
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
	private function setCountry($country = 'SE')
	{
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


	/*********** PUBLICS ************/

	private function getSpecLine($specialSpecline = array())
	{
		if (count($specialSpecline)) {
			return $specialSpecline;
		}
		return array(
			'artNo' => 'EcomPHP-testArticle-' . rand(1, 1024),
			'description' => 'EComPHP Random Test Article number ' . rand(1, 1024),
			'quantity' => 1,
			'unitAmountWithoutVat' => intval(rand(1000, 10000)),
			'vatPct' => 25
		);
	}

	private function getSpecLineZero($specialSpecline = array(), $zeroTax = false)
	{
		if (count($specialSpecline)) {
			return $specialSpecline;
		}
		return array(
			'artNo' => 'EcomPHP-testArticle-' . rand(1, 1024),
			'description' => 'EComPHP Random Test Article number ' . rand(1, 1024),
			'quantity' => 1,
			'unitAmountWithoutVat' => 0,
			'vatPct' => $zeroTax ? 0 : 25
		);
	}

	/**
	 * Allow older/obsolete PHP Versions (Follows the obsolete php versions rules - see the link for more information). This check is clonsed from the rbapiloader.php
	 * to follow standards and prevent tests in older php versions.
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
		$this->alertSender();
	}


	/*********** TESTS ************/

	/**
	 * Test if environment is ok
	 */
	public function testGetEnvironment()
	{
		if ($this->ignoreDefaultTests) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$this->assertTrue($this->isUp() === true);
	}

	/**
	 * Test if payment methods works properly
	 */
	public function testGetPaymentMethods()
	{
		if ($this->ignoreDefaultTests) {
			$this->markTestSkipped();
		}
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
	public function testGetPaymentMethodsAll()
	{
		if ($this->ignoreDefaultTests) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$paymentMethods = $this->rb->getPaymentMethods();
		if (count($paymentMethods) !== $this->paymentMethodCount[$this->environmentName]) {
			$this->alertRender("Payment method mismatch - got " . count($paymentMethods) . ", expected 5.");
		}
		$this->assertTrue(count($paymentMethods) === $this->paymentMethodCount[$this->environmentName]);
	}

	/**
	 * getAddress, NATURAL
	 */
	public function testGetAddressNatural()
	{
		if ($this->ignoreDefaultTests) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$getAddressData = array();
		try {
			$getAddressData = $this->rb->getAddress($this->govIdNatural, 'NATURAL', '127.0.0.1');
		} catch (\Exception $e) {
		}
		$this->assertTrue(!empty($getAddressData->fullName));
	}

	/**
	 * getAddress, LEGAL, Civic number
	 */
	public function testGetAddressLegalCivic()
	{
		if ($this->ignoreDefaultTests) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$getAddressData = array();
		try {
			$getAddressData = $this->rb->getAddress($this->govIdLegalCivic, 'LEGAL', '127.0.0.1');
		} catch (\Exception $e) {
		}
		$this->assertTrue(!empty($getAddressData->fullName));
	}

	/**
	 * getAddress, LEGAL, Organization number
	 */
	public function testGetAddressLegalOrg()
	{
		if ($this->ignoreDefaultTests) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$getAddressData = array();
		try {
			$getAddressData = $this->rb->getAddress($this->govIdLegalOrg, 'LEGAL', '127.0.0.1');
		} catch (\Exception $e) {
		}
		$this->assertTrue(!empty($getAddressData->fullName));
	}

	/**
	 * Testing of annuity factors (if they exist), with the first found payment method
	 */
	public function testGetAnnuityFactors()
	{
		if ($this->ignoreDefaultTests) {
			$this->markTestSkipped();
		}
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
	public function testBookSimplifiedPaymentInvoiceNatural()
	{
		if ($this->ignoreBookingTests) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$bookResult = $this->doBookPayment($this->availableMethods['invoice_natural'], true, false, true);
		$this->assertTrue($bookResult);
	}

	/**
	 * Test booking and always use extendedCustomer.
	 * Payment Method: Invoice
	 * Customer Type: NATURAL, GRANTED
	 * @deprecated No longer in effect as extended customer is always in use
	 */
	public function testBookPaymentInvoiceHostedNatural()
	{
		if ($this->ignoreBookingTests) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$this->rb->setPreferredPaymentService(ResursMethodTypes::METHOD_HOSTED);
		$bookResult = $this->doBookPayment($this->availableMethods['invoice_natural'], true, false, true);
		// Can't do bookings yet, since this is a forwarder. We would like to emulate browser clicking here, to complete the order.
		$this->assertTrue(strlen($bookResult) > 1024);
	}

	/**
	 * Test findPayments()
	 */
	public function testFindPayments()
	{
		$this->checkEnvironment();
		$paymentList = $this->rb->findPayments();
		$this->assertGreaterThan(0, count($paymentList));
	}

	/**
	 * Book and see if there is a payment registered at Resurs Bank
	 */
	public function testGetPayment()
	{
		if ($this->ignoreBookingTests) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$paymentList = $this->rb->findPayments();
		if (is_array($paymentList) && count($paymentList)) {
			$existingPayment = array_pop($paymentList);
			$existingPayment->paymentId;
			$payment = $this->rb->getPayment($existingPayment->paymentId);
			$this->assertTrue($payment->id == $existingPayment->paymentId);
		} else {
			$this->markTestSkipped("No payments available to run with getPayment()");
		}
	}
	/**
	 * Book and see if there is a payment registered at Resurs Bank
	 */
	public function testGetPaymentInvoices()
	{
		if ($this->ignoreBookingTests) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		try {
			$invoicesArray = $this->rb->getPaymentInvoices( "20170802114006-2638609880" );
		} catch (\Exception $e) {
			$this->markTestSkipped("This test requires an order that contains one or more debits (it's a very special test) and the payment used to test this does not seem to exist here.");
			return;
		}
		//$invoicesArray = $this->rb->getPaymentInvoices("20170802112051-7018398597");
		$hasInvoices = false;
		if (count($invoicesArray)>0) {
			$hasInvoices = true;
		}
		if ($hasInvoices) {
			$this->assertTrue($hasInvoices);
		} else {
			$this->markTestSkipped("No debits available in current test");
		}
	}

	private function getAPayment($paymentId = null, $randomize = false, $paymentType = null)
	{
		$this->checkEnvironment();
		$paymentList = $this->rb->findPayments(array(), 1, 100);
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
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$this->zeroSpecLine = true;
		$hasException = false;
		try {
			$this->doBookPayment($this->availableMethods['invoice_natural'], true, false, true);
		} catch (\Exception $exceptionWanted) {
			$hasException = true;
		}
		$this->assertTrue($hasException);
	}

	/**
	 * Test booking.
	 * Payment Method: Invoice
	 * Customer Type: NATURAL, DENIED
	 */
	public function testBookPaymentInvoiceNaturalDenied()
	{
		if ($this->ignoreBookingTests) {
			$this->markTestSkipped();
		}
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
	public function testBookPaymentInvoiceLegal()
	{
		if ($this->ignoreBookingTests) {
			$this->markTestSkipped();
		}
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
	public function testBookPaymentCard()
	{
		if ($this->ignoreBookingTests) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$bookResult = $this->doBookPayment($this->availableMethods['card'], true, false, true, 'SE');
		$this->assertTrue($bookResult === true);
	}

	/**
	 * Test booking with new card
	 */
	public function testBookPaymentNewCard()
	{
		if ($this->ignoreBookingTests) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$bookResult = $this->doBookPayment($this->availableMethods['card_new'], true, false, true, 'SE');
		$this->assertTrue($bookResult === true);
	}

	/**
	 * Test booking (NO).
	 * Payment Method: Invoice
	 * Customer Type: NATURAL, GRANTED
	 */
	public function testBookPaymentInvoiceNaturalNorway()
	{
		if ($this->ignoreBookingTests) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$bookResult = $this->doBookPayment($this->availableMethodsNorway['invoice_natural'], true, false, true, 'NO');
		$this->assertTrue($bookResult === true);
	}

	/**
	 * Test chosen payment method sekki-generator
	 * @throws Exception
	 */
	public function testSekkiSimple()
	{
		$this->checkEnvironment();
		if ($this->ignoreSEKKItests) {
			$this->markTestSkipped();
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
		$this->assertTrue($matches === $appenders);
	}

	/**
	 * Test pre-fetched sekki-url-generator
	 * @throws Exception
	 */
	public function testSekkiArray()
	{
		$this->checkEnvironment();
		if ($this->ignoreSEKKItests) {
			$this->markTestSkipped();
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
			$this->assertTrue($matches === $appenders);
		}
	}

	/**
	 * Test all payment methods
	 */
	public function testSekkiAll()
	{
		if ($this->ignoreSEKKItests) {
			$this->markTestSkipped();
		}
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
		$this->checkEnvironment();
		if ($this->ignoreSEKKItests) {
			$this->markTestSkipped();
		}
		$amount = rand(1000, 10000);
		$URL = "https://test.resurs.com/customurl/index.html?content=true&secondparameter=true";
		$customURL = $this->rb->getSekkiUrls($amount, null, $URL);
		$this->assertTrue((preg_match("/amount=$amount/i", $customURL) ? true : false));
	}

	/**
	 * This test is incomplete.
	 *
	 * @param bool $returnTheFrame
	 *
	 * @return bool|null
	 */
	private function getCheckoutFrame($returnTheFrame = false, $returnPaymentReference = false)
	{
		$assumeThis = false;
		if ($returnTheFrame) {
			$iFrameUrl = false;
		}
		if ($this->ignoreBookingTests) {
			$this->markTestSkipped();
		}
		$this->rb->setPreferredPaymentService(ResursMethodTypes::METHOD_CHECKOUT);
		$newReferenceId = $this->rb->getPreferredPaymentId();
		$bookResult = $this->doBookPayment($newReferenceId, true, false, true);
		if (is_string($bookResult) && preg_match("/iframe src/i", $bookResult)) {
			$iframeUrls = $this->NETWORK->getUrlsFromHtml($bookResult,0,1);
			$iFrameUrl = array_pop($iframeUrls);
			$iframeContent = $this->CURL->doGet($iFrameUrl);
			if (!empty($iframeContent['body'])) {
				$assumeThis = true;
			}
		}
		if ($returnPaymentReference) {
			return $this->rb->getPreferredPaymentId();
		}
		if (!$returnTheFrame) {
			return $assumeThis;
		} else {
			return $iFrameUrl;
		}
	}

	/**
	 * Try to fetch the iframe (Resurs Checkout). When the iframe url has been received, check if there's content.
	 */
	public function testGetIFrame()
	{
		$this->checkEnvironment();
		try {
			$getFrameUrl = $this->getCheckoutFrame(true);
		} catch (\Exception $e) {
			$this->markTestIncomplete("getCheckoutFrameException: " . $e->getMessage());
		}
		//$SessionID = $this->rb->getPaymentSessionId();
		$UrlDomain = $this->NETWORK->getUrlDomain($getFrameUrl);
		// If there is no https defined in the frameUrl, the test might have failed
		if ($UrlDomain[1] == "https") {
			$FrameContent = $this->CURL->doGet($getFrameUrl);
			$this->assertTrue($FrameContent['code'] == 200 && strlen($FrameContent['body']) > 1024);
		}
	}

	/**
	 * Try to update a payment reference by first creating the iframe
	 */
	public function testUpdatePaymentReference()
	{
		$this->checkEnvironment();
		$iframePaymentReference = $this->rb->getPreferredPaymentId(30, "CREATE-");
		try {
			$iFrameUrl = $this->getCheckoutFrame(true);
		} catch (\Exception $e) {
			$this->markTestIncomplete("Exception: " . $e->getMessage());
		}
		$this->CURL->setAuthentication( $this->username, $this->password );
		$this->CURL->setLocalCookies(true);
		$iframeRequest = $this->CURL->doGet($iFrameUrl);

		$payload = $this->rb->getPayload();
		$orderLines = array("orderLines" => $payload['orderLines']);

		$iframeContent = $iframeRequest['body'];
		$Success = false;
		if (!empty($iframePaymentReference) && !empty($iFrameUrl) && !empty($iframeContent) && strlen($iframeContent) > 1024) {
			$newReference = $this->rb->getPreferredPaymentId(30, "UPDATE-", true, true);
			$firstCheckoutUrl = $this->rb->getCheckoutUrl() . "/checkout/payments/" . $iframePaymentReference;
			$secondCheckoutUrl = $this->rb->getCheckoutUrl() . "/checkout/payments/" . $newReference;
			try {
				// Currently, this test always gets a HTTP-200 from ecommerce, regardless of successful or failing updates.
				$Success = $this->rb->updatePaymentReference($iframePaymentReference, $newReference);
				// TODO: When exceptions are properly implemented in ecom/checkout this should be included
				// TODO: Create test suite for testing failing updates when ecom/checkout starts to throw exceptions
				// Update the new order id with new products
				//$updateOrderReq = $this->CURL->doPut($secondCheckoutUrl, $orderLines, 1);
				//$responseOnFirstUpdate = $this->CURL->getResponseCode($updateOrderReq);

			} catch (\Exception $e) {
				$this->markTestIncomplete("Exception: " . $e->getCode() . ": " . $e->getMessage());
			}
		}
		$this->assertTrue($Success === true);
	}



	/**
	 * Get all callbacks by a rest call (objects)
	 */
	public function testGetCallbackListByRest()
	{
		$cbr = $this->rb->getCallBacksByRest();
		$this->assertGreaterThan(0, count($cbr));
	}

	/**
	 * Get all callbacks by a rest call (key-indexed array)
	 */
	public function testGetCallbackListAsArrayByRest()
	{
		$cbr = $this->rb->getCallBacksByRest(true);
		$this->assertGreaterThan(0, count($cbr));
	}

	/**
	 * Testing add metaData, adding random data to a payment
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
			$this->assertTrue($this->rb->addMetaData($paymentId, "RandomKey" . rand(1000, 1999), "RandomValue" . rand(2000, 3000)));
		} else {
			$this->markTestIncomplete("No valid payment found");
		}
	}

	/**
	 * Testing add metaData, with a faulty payment id
	 */
	public function testAddMetaDataFailure()
	{
		$paymentData = null;
		$chosenPayment = 0;
		$paymentId = null;
		$hasException = false;
		try {
			$this->rb->addMetaData("UnexistentPaymentId", "RandomKey" . rand(1000, 1999), "RandomValue" . rand(2000, 3000));
		} catch (\Exception $e) {
			$this->assertTrue(true);
			$hasException = true;
		}
		if (!$hasException) {
			$this->markTestSkipped("addMetaDataFailure failed since it never got an exception");
		}
	}

	/**
	 * Test getCostOfPurchase
	 */
	function testGetCostOfPurchase()
	{
		$PurchaseInfo = $this->rb->getCostOfPurchase($this->getAMethod(), 100);
		$this->assertTrue(is_string($PurchaseInfo) && strlen($PurchaseInfo) >= 1024);
	}

	/***
	 * VERSION 1.0-1.1 DEPENDENT TESTS
	 */

	/**
	 * Renders required data to pass to a callback registrator.
	 *
	 * @param bool $UseCurl Using the curl library, will render this data differently
	 * @param bool $UseUrlRewrite Register urls "nicely" with url_rewrite-like parameters
	 *
	 * @return array
	 */
	private function renderCallbackData($UseCurl = false, $UseUrlRewrite = false)
	{
		$this->checkEnvironment();
		$returnCallbackArray = array();
		$parameter = array(
			'ANNULMENT' => array('paymentId'),
			'FINALIZATION' => array('paymentId'),
			'UNFREEZE' => array('paymentId'),
			'UPDATE' => array('paymentId'),
			'AUTOMATIC_FRAUD_CONTROL' => array('paymentId', 'result')
		);
		foreach ($parameter as $callbackType => $parameterArray) {
			$digestSaltString = $this->mkpass();
			$digestArray = array(
				'digestSalt' => $digestSaltString,
				'digestParameters' => $parameterArray
			);
			if ($callbackType == "ANNULMENT") {
				$setCallbackType = ResursCallbackTypes::ANNULMENT;
			}
			if ($callbackType == "AUTOMATIC_FRAUD_CONTROL") {
				$setCallbackType = ResursCallbackTypes::AUTOMATIC_FRAUD_CONTROL;
			}
			if ($callbackType == "FINALIZATION") {
				$setCallbackType = ResursCallbackTypes::FINALIZATION;
			}
			if ($callbackType == "UNFREEZE") {
				$setCallbackType = ResursCallbackTypes::UNFREEZE;
			}
			if ($callbackType == "UPDATE") {
				$setCallbackType = ResursCallbackTypes::UPDATE;
			}
			$renderArray = array();
			if (is_array($parameterArray)) {
				foreach ($parameterArray as $parameterName) {
					if (!$UseUrlRewrite) {
						$renderArray[] = $parameterName . "={" . $parameterName . "}";
					} else {
						$renderArray[] = $parameterName . "/{" . $parameterName . "}";
					}
				}
			}
			if (!$UseUrlRewrite) {
				$callbackURL = $this->callbackUrl . "?event=" . $callbackType . "&digest={digest}&" . implode("&", $renderArray) . "&lastReg=" . strftime("%y%m%d%H%M%S", time());
			} else {
				$callbackURL = $this->callbackUrl . "/event/" . $callbackType . "/digest/{digest}/" . implode("/", $renderArray) . "/lastReg/" . strftime("%y%m%d%H%M%S", time());
			}
			$returnCallbackArray[] = array($setCallbackType, $callbackURL, $digestArray);
		}
		return $returnCallbackArray;
	}

	/**
	 * Register new callback urls via SOAP
	 */
	public function testSetRegisterCallbacksSoap()
	{
		$this->checkEnvironment();
		$callbackArrayData = $this->renderCallbackData(true);
		$globalDigest = $this->rb->setCallbackDigest($this->mkpass());
		$cResponse = array();
		$this->rb->setRegisterCallbacksViaRest(false);
		foreach ($callbackArrayData as $indexCB => $callbackInfo) {
			$cResponse[$callbackInfo[0]] = $this->rb->setRegisterCallback($callbackInfo[0], $callbackInfo[1] . "&via=soap", $callbackInfo[2]);
		}
		$successFulCallbacks = 0;
		foreach ($cResponse as $cbType) {
			if ($cbType == "1") {
				$successFulCallbacks++;
			}
		}
		$this->assertEquals(count($cResponse), $successFulCallbacks);
	}

	/**
	 * Register new callback urls via SOAP
	 */
	public function testSetRegisterCallbacksSoapUrlRewrite()
	{
		$this->checkEnvironment();
		$callbackArrayData = $this->renderCallbackData(true, true);
		$globalDigest = $this->rb->setCallbackDigest($this->mkpass());
		$cResponse = array();
		$this->rb->setRegisterCallbacksViaRest(false);
		foreach ($callbackArrayData as $indexCB => $callbackInfo) {
			$cResponse[$callbackInfo[0]] = $this->rb->setRegisterCallback($callbackInfo[0], $callbackInfo[1] . "&via=soap", $callbackInfo[2]);
		}
		$successFulCallbacks = 0;
		foreach ($cResponse as $cbType) {
			if ($cbType == "1") {
				$successFulCallbacks++;
			}
		}
		$this->assertEquals(count($cResponse), $successFulCallbacks);
	}

	/**
	 * Register new callback urls via REST
	 */
	public function testSetRegisterCallbacksRest()
	{
		$callbackArrayData = $this->renderCallbackData(true);
		$cResponse = array();
		$this->checkEnvironment();
		$globalDigest = $this->rb->setCallbackDigest($this->mkpass());
		$this->rb->setRegisterCallbacksViaRest(true);
		foreach ($callbackArrayData as $indexCB => $callbackInfo) {
			$cbResult = $this->rb->setRegisterCallback($callbackInfo[0], $callbackInfo[1] . "&via=rest", $callbackInfo[2]);
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
		$this->assertEquals(count($cResponse), $successFulCallbacks);
	}

	/**
	 * Register new callback urls via REST
	 */
	public function testSetRegisterCallbacksRestUrlRewrite()
	{
		$callbackArrayData = $this->renderCallbackData(true, true);
		$cResponse = array();
		$this->checkEnvironment();
		$globalDigest = $this->rb->setCallbackDigest($this->mkpass());
		$this->rb->setRegisterCallbacksViaRest(true);
		foreach ($callbackArrayData as $indexCB => $callbackInfo) {
			$cbResult = $this->rb->setRegisterCallback($callbackInfo[0], $callbackInfo[1] . "&via=rest", $callbackInfo[2]);
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
		$this->assertEquals(count($cResponse), $successFulCallbacks);
	}

	public function testValidateExternalUrlSuccess()
	{
		$this->checkEnvironment();
		$callbackArrayData = $this->renderCallbackData(true);
		$this->rb->setValidateExternalCallbackUrl($callbackArrayData[0][1]);
		$Reachable = $this->rb->validateExternalAddress();
		if ($Reachable !== ResursCallbackReachability::IS_FULLY_REACHABLE) {
			$this->markTestIncomplete("External address validation returned $Reachable instead of " . ResursCallbackReachability::IS_FULLY_REACHABLE . ".\nPlease check your callback url (" . $callbackArrayData[0][1] . ") so that is properly configured and reachable.");
		}
		$this->assertTrue($Reachable === ResursCallbackReachability::IS_FULLY_REACHABLE);
	}

	/**
	 * Register new callback urls
	 */
	public function testSetRegisterCallbacksWithValidatedUrlViaRest()
	{
		if (!$this->ignoreUrlExternalValidation) {
			$this->checkEnvironment();
			$this->rb->setRegisterCallbacksViaRest(true);
			$callbackArrayData = $this->renderCallbackData(true);
			$this->rb->setCallbackDigest($this->mkpass());
			$cResponse = array();
			foreach ($callbackArrayData as $indexCB => $callbackInfo) {
				try {
					$this->rb->setValidateExternalCallbackUrl($callbackInfo[1] . "&via=restValidated");
					$cResponse[$callbackInfo[0]] = $this->rb->setRegisterCallback($callbackInfo[0], $callbackInfo[1] . "&via=restValidated", $callbackInfo[2]);
				} catch (\Exception $e) {
					$this->markTestIncomplete("Exception thrown: URL Validation failed for " . (isset($callbackInfo[1]) && !empty($callbackInfo[1]) ? $callbackInfo[1] . "&via=restValidated" : "??") . " during the setRegisterCallback procss (" . $e->getMessage() . ")");
				}
			}
			$successFulCallbacks = 0;
			foreach ($cResponse as $cbType) {
				if ($cbType == "1") {
					$successFulCallbacks++;
				}
			}
			$this->assertEquals(count($cResponse), $successFulCallbacks);
		} else {
			$this->markTestSkipped("ignoreUrlExternalValidation is active, skipping test");
		}
	}

	/**
	 * Testing of unregisterEventCallback via rest calls
	 */
	public function testUnregisterEventCallbackViaRest()
	{
		$this->checkEnvironment();
		$this->rb->setRegisterCallbacksViaRest(true);
		
		$this->assertTrue($this->rb->unregisterEventCallback(ResursCallbackTypes::ANNULMENT));
	}

	/**
	 * Testing of unregisterEventCallback via soap calls
	 */
	public function testUnregisterEventCallbackViaSoap()
	{
		$this->checkEnvironment();
		$this->rb->setRegisterCallbacksViaRest(false);
		$this->assertTrue($this->rb->unregisterEventCallback(ResursCallbackTypes::ANNULMENT));
	}

	/**
	 * Register new callback urls but without the digest key (Fail)
	 */
	public function testSetRegisterCallbacksWithoutDigest()
	{
		$this->checkEnvironment();
		$callbackArrayData = $this->renderCallbackData(true);
		try {
			foreach ($callbackArrayData as $indexCB => $callbackInfo) {
				$cResponse = $this->rb->setRegisterCallback($callbackInfo[0], $callbackInfo[1], $callbackInfo[2]);
			}
		} catch (\Exception $e) {
			$this->assertTrue(!empty($e->getMessage()));
		}
	}

	/**
	 * Testing of callbacks
	 */
	public function testCallbacks()
	{
		if ($this->ignoreDefaultTests) {
			$this->markTestSkipped("Testing of deprecated callback function is disabled on request");
		}
		/* If disabled */
		if ($this->disableCallbackRegMock || ($this->disableCallbackRegNonMock && $this->environmentName === "nonmock")) {
			$this->markTestSkipped("Testing of deprecated callback function is disabled due to special circumstances");
			return;
		}
		$callbackArrayData = $this->renderCallbackData();
		$callbackSetResult = array();
		$this->rb->setCallbackDigest($this->mkpass());
		foreach ($callbackArrayData as $indexCB => $callbackInfo) {
			try {
				$cResponse = $this->rb->setRegisterCallback($callbackInfo[0], $callbackInfo[1], $callbackInfo[2]);
				$callbackSetResult[] = $callbackInfo[0];
			} catch (\Exception $e) {
				echo $e->getMessage();
			}
		}
		// Registered callbacks must be at least 4 to be successful, as there are at least 4 important callbacks to pass through
		$this->assertGreaterThanOrEqual(4, count($callbackSetResult));
	}

	function testGetNextInvoiceNumber()
	{
		$this->assertTrue($this->rb->getNextInvoiceNumber() >= 1);
	}

	/*
	function testSetNextInvoiceNumber()
	{
		$this->markTestSkipped("This is a special test that should normally not be necessary to run");
		$this->rb->getNextInvoiceNumber(true, 1000);
		$this->assertEquals(1000, $this->rb->getNextInvoiceNumber());
	}
	*/

	/// 1.0.2 features
	function testSetCustomerNatural()
	{
		$this->checkEnvironment();

		$this->rb->setPreferredPaymentService(ResursMethodTypes::METHOD_CHECKOUT);
		$ReturnedPayload = $this->rb->setBillingByGetAddress($this->govIdNatural);
		$this->assertEquals($this->govIdNatural, $ReturnedPayload['customer']['governmentId']);
	}

	function testSetCustomerLegal()
	{
		$this->checkEnvironment();
		$this->rb->setPreferredPaymentService(ResursMethodTypes::METHOD_CHECKOUT);
		$ReturnedPayload = $this->rb->setBillingByGetAddress($this->govIdLegalCivic, "LEGAL");
		$this->assertTrue($ReturnedPayload['customer']['governmentId'] == $this->govIdLegalCivic && $ReturnedPayload['customer']['address']['fullName'] == $this->govIdLegalFullname);
	}

	private function addRandomOrderLine($articleNumberOrId = "Artikel", $description = "Beskrivning", $unitAmountWithoutVat = "0.80", $vatPct = 25, $type = null, $quantity = 10)
	{
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

	private function doMockSign($URL, $govId)
	{
		$MockFormResponse = $this->CURL->doGet($URL);
		$MockDomain = $this->NETWORK->getUrlDomain($MockFormResponse['URL']);
		$SignBody = $this->CURL->getResponseBody($this->CURL->doGet($URL));
		$MockForm = $this->CURL->getResponseBody($MockFormResponse);
		$MockFormActionPath = preg_replace("/(.*?)action=\"(.*?)\"(.*)/is", '$2', $MockForm);
		$MockFormToken = preg_replace("/(.*?)resursToken\" value=\"(.*?)\"(.*)/is", '$2', $MockForm);
		$prepareMockSuccess = $MockDomain[1] . "://" . $MockDomain[0] . $MockFormActionPath . "?resursToken=" . $MockFormToken . "&govId=" . $govId;
		$ValidateUrl = $this->NETWORK->getUrlDomain($prepareMockSuccess, true);
		if (!empty($ValidateUrl[0])) {
			$mockSuccess = $this->CURL->getParsedResponse($this->CURL->doGet($prepareMockSuccess));
			if (isset($mockSuccess->_GET->success)) {
				return $mockSuccess->_GET;
			}
		}
		return;
	}

	/**
	 * Basic payment
	 */
	function testCreatePaymentPayloadSimplified()
	{
		$this->checkEnvironment();
		try {
			$this->rb->setPreferredPaymentService(ResursMethodTypes::METHOD_SIMPLIFIED);
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
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null, 10);
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null, 10);
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null, 10);
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null, 10);
			$useThisPaymentId = $this->rb->getPreferredPaymentId();
			// Payload that needs to be appended to the rendered one
			$myPayLoad = array(
				'paymentData' => array(
					'waitForFraudControl' => false,
					'annulIfFrozen' => false,
					'finalizeIfBooked' => false
				),
				'metaData' => array(
					'key' => 'CustomerId',
					'value' => 'l33tCustomer'
				),
				'customer' => array(
					'yourCustomerId' => 'DatL33tCustomer'
				)
			);
			$this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
			//$Payment = $this->rb->createPayment($this->availableMethods['invoice_natural'], $myPayLoad);
			try {
				$Payment = $this->rb->createPayment($this->availableMethods['invoice_natural']);
				$this->assertTrue($Payment->bookPaymentStatus == "BOOKED");
			} catch (\Exception $e) {
				echo "Fail: " . $e->getMessage();
			}

		} catch (\Exception $e) {
			$this->markTestIncomplete($e->getMessage());
		}
	}

	/**
	 * Creating payment with own billing address but happyflow govId
	 */
	function testCreatePaymentPayloadForcedSigningSimplified()
	{
		$this->checkEnvironment();
		try {
			$this->rb->setPreferredPaymentService(ResursMethodTypes::METHOD_SIMPLIFIED);
			$this->rb->setBillingByGetAddress("198305147715");
			$this->rb->setCustomer(null, "0808080808", "0707070707", "test@test.com", "NATURAL");
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null, 10);
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null, 10);
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null, 10);
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null, 10);
			$useThisPaymentId = $this->rb->getPreferredPaymentId();
			$this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', true);
			try {
				$Payment = $this->rb->createPayment($this->availableMethods['invoice_natural']);
				if ($Payment->bookPaymentStatus == "SIGNING") {
					$signUrl = $Payment->signingUrl;
					$signData = $this->doMockSign($signUrl, "198305147715");
					$this->assertTrue($signData->success == "true");
				}
			} catch (\Exception $e) {
				echo "Fail: " . $e->getMessage();
			}

		} catch (\Exception $e) {
			$this->markTestIncomplete($e->getMessage());
		}
	}

	/**
	 * Creating payment with own billing address but happyflow govId
	 */
	function testCreatePaymentPayloadUseExecuteSimplified()
	{
		$this->checkEnvironment();
		try {
			$this->rb->setPreferredPaymentService(ResursMethodTypes::METHOD_SIMPLIFIED);
			$this->rb->setBillingByGetAddress("198305147715");
			$this->rb->setCustomer(null, "0808080808", "0707070707", "test@test.com", "NATURAL");
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null, 10);
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, null, 10);
			$useThisPaymentId = $this->rb->getPreferredPaymentId();
			$this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
			try {
				$this->rb->setRequiredExecute(true);
				$delayPayment = $this->rb->createPayment($this->availableMethods['invoice_natural']);
				if (isset($delayPayment['status']) && $delayPayment['status'] == "delayed") {
					//$thePayload = $this->rb->getPayload();
					$thePayment = $this->rb->Execute($this->availableMethods['invoice_natural']);
					$this->assertTrue($thePayment->bookPaymentStatus == "BOOKED");
					return;
				}
			} catch (\Exception $e) {
				$this->markTestIncomplete($e->getMessage());
			}
		} catch (\Exception $e) {
			$this->markTestIncomplete("Outer exception thrown (" . $e->getMessage() . ")");
		}
		$this->markTestIncomplete("CreatePayment via Delayed create failed - never passed through the payload generation.");
	}

	/**
	 * Creating payment with own billing address but happyflow govId
	 */
	function testCreatePaymentPayloadUseExecuteResursCheckout()
	{
		$this->checkEnvironment();
		try {
			$this->rb->setPreferredPaymentService(ResursMethodTypes::METHOD_CHECKOUT);
			$this->rb->setBillingByGetAddress("198305147715");
			$this->rb->setCustomer(null, "0808080808", "0707070707", "test@test.com", "NATURAL");
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, 'ORDER_LINE', 10);
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), "0.80", 25, 'ORDER_LINE', 10);
			$useThisPaymentId = $this->rb->getPreferredPaymentId();
			$this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
			try {
				$this->rb->setRequiredExecute(true);
				$delayPayment = $this->rb->createPayment($this->availableMethods['invoice_natural']);
				$paymentId = $this->rb->getPreferredPaymentId();
				if (isset($delayPayment['status']) && $delayPayment['status'] == "delayed") {
					$thePayload = $this->rb->getPayload();
					$thePayment = $this->rb->Execute($this->availableMethods['invoice_natural']);
					$this->assertTrue(preg_match("/iframe src/i", $thePayment)? true:false);
					return;
				}
			} catch (\Exception $e) {
				$this->markTestIncomplete($e->getMessage());
			}
		} catch (\Exception $e) {
			$this->markTestIncomplete("Outer exception thrown (" . $e->getMessage() . ")");
		}
		$this->markTestIncomplete("CreatePayment via Delayed create failed - never passed through the payload generation.");
	}


	function testCanDebit()
	{
		$payment = $this->getAPayment(null, true);
		// Make sure we don't step over this magic number
		$maxLoops = 10;
		$loopWatch = 0;
		while ($payment == "20170519070836-6799421526" && isset($payment->status) && $payment->status != "DEBITABLE") {
			if ($loopWatch++ > $maxLoops) {
				$this->markTestSkipped("Can not find any debitable snapshot to test.");
				break;
			}
			$payment = $this->getAPayment(true);
		}
		if ($loopWatch++ > $maxLoops) {
			return;
		}
		$this->assertTrue($this->rb->canDebit($payment));
	}

	private function generateOrderByClientChoice($orderLines = 8, $quantity = 1, $minAmount = 1000, $maxAmount = 2000)
	{
		$this->rb->setPreferredPaymentService(ResursMethodTypes::METHOD_SIMPLIFIED);
		$this->rb->setBillingByGetAddress("198305147715");
		$this->rb->setCustomer("198305147715", "0808080808", "0707070707", "test@test.com", "NATURAL");
		while ($orderLines-- > 0) {
			$this->addRandomOrderLine("Art " . rand(1024, 2048), "Beskrivning " . rand(2048, 4096), rand($minAmount, $maxAmount), 25, null, $quantity);
		}
		$this->rb->setSigning($this->signUrl . '&success=true', $this->signUrl . '&success=false', false);
		try {
			$Payment = $this->rb->createPayment($this->availableMethods['invoice_natural']);
		} catch (\Exception $e) {
			echo $e->getMessage();
		}
		if ($Payment->bookPaymentStatus == "BOOKED") {
			return $Payment;
		}
	}
	private function getPaymentIdFromOrderByClientChoice($orderLines = 8, $quantity = 1, $minAmount = 1000, $maxAmount = 2000) {
		$Payment = $this->generateOrderByClientChoice($orderLines, $quantity, $minAmount, $maxAmount);
		return $Payment->paymentId;
	}

	function testHugeQuantity()
	{
		$this->checkEnvironment();
		try {
			$hasOrder = $this->generateOrderByClientChoice(2, 16000, 1, 1);
			$this->assertTrue($hasOrder->bookPaymentStatus == "BOOKED");
		} catch (\Exception $e) {
		}
	}

	function testAdditionalDebit() {
		$paymentId = $this->getPaymentIdFromOrderByClientChoice();
		$this->rb->annulPayment($paymentId);
		$this->rb->addOrderLine("myExtraOrderLine-1", "One orderline added with additionalDebitOfPayment", 100, 25);
		$this->rb->addOrderLine("myExtraOrderLine-2", "One orderline added with additionalDebitOfPayment", 200, 25);
		$this->assertTrue($this->rb->setAdditionalDebitOfPayment($paymentId));
	}
	function testAdditionalDebitResursCheckout() {
		$paymentId = $this->getPaymentIdFromOrderByClientChoice();
		$this->rb->annulPayment($paymentId);
		$this->rb->setPreferredPaymentService(ResursMethodTypes::METHOD_CHECKOUT);
		$this->rb->addOrderLine("myExtraOrderLine-1", "One orderline added with additionalDebitOfPayment", 100, 25);
		$this->rb->addOrderLine("myExtraOrderLine-2", "One orderline added with additionalDebitOfPayment", 200, 25);
		$this->assertTrue($this->rb->setAdditionalDebitOfPayment($paymentId));
	}

	/**
	 * Test for ECOMPHP-113
	 */
	function testAdditionalDebitNewDoubleDuplicateCheck() {
		$paymentId = $this->getPaymentIdFromOrderByClientChoice(2);
		$this->rb->addOrderLine("myAdditionalOrderLineFirst", "One orderline added with additionalDebitOfPayment", 100, 25);
		$this->rb->setAdditionalDebitOfPayment($paymentId);
		$this->rb->addOrderLine("myAdditionalOrderLineExtended", "One orderline added with additionalDebitOfPayment", 100, 25);
		$this->rb->setAdditionalDebitOfPayment($paymentId);
		$merged = $this->rb->getPaymentSpecByStatus($paymentId);
		$added = 0;
		foreach ($merged['AUTHORIZE'] as $articles) {
			if ($articles->artNo == "myAdditionalOrderLineFirst") {$added ++;}
			if ($articles->artNo == "myAdditionalOrderLineExtended") {$added ++;}
		}
		$this->assertEquals(2, $added);
	}

	/**
	 * Test for ECOMPHP-112
	 */
	function testAdditionalDualDebitWithDifferentAmount() {
		$paymentId = $this->getPaymentIdFromOrderByClientChoice();
		$this->rb->finalizePayment($paymentId);
		$this->rb->addOrderLine("myAdditionalOrderLine", "One orderline added with additionalDebitOfPayment", 100, 25);
		$this->rb->setAdditionalDebitOfPayment($paymentId);
		$this->rb->addOrderLine("myAdditionalOrderLine", "One orderline added with additionalDebitOfPayment", 105, 25);
		$this->rb->setAdditionalDebitOfPayment($paymentId);
		$merged = $this->rb->getPaymentSpecByStatus($paymentId);
		$quantity = 0;
		foreach ($merged['AUTHORIZE'] as $articles) {
			if ($articles->artNo == "myAdditionalOrderLine") {
				$quantity += $articles->quantity;
			}
		}
		$this->assertEquals(2, $quantity);
	}

	public function testRenderSpeclineByObject() {
		$payment = $this->getAPayment(null, true);
		if (isset($payment->id)) {
			$this->assertTrue(is_array($this->rb->getPaymentSpecByStatus($payment)));
		}
	}
	public function testRenderSpeclineByOrderId() {
		$payment = $this->getAPayment(null, true);
		if (isset($payment->id)) {
			$this->assertTrue(is_array($this->rb->getPaymentSpecByStatus($payment->id)));
		}
	}
	public function testRenderSpecBulk() {
		if (!$this->isSpecialAccount()) {
			$this->markTestSkipped("RenderSpecBulk skipped: Wrong credential account");
		}
		$this->assertCount(2, $this->rb->getPaymentSpecByStatus($this->paymentIdAuthAnnulled));
	}

	function testFinalizeFull() {
		$paymentId = $this->getPaymentIdFromOrderByClientChoice();
		$this->assertTrue($this->rb->finalizePayment($paymentId));
	}

	/**
	 * Test: Annull full payment (deprecated method)
	 */
	function testAnullFullPaymentDeprecated() {
		$paymentId = $this->getPaymentIdFromOrderByClientChoice();
		$this->assertTrue($this->rb->annulPayment($paymentId));
	}
	/**
	 * Test: Finalize full payment (deprecated method)
	 */
	function testFinalizeFullPaymentDeprecatedWithSpecialInformation() {
		$this->rb->setAfterShopYourReference("YourReference TestSuite");
		$this->rb->setAfterShopOurReference("OurReference TestSuite");
		$paymentId = $this->getPaymentIdFromOrderByClientChoice();
		$this->assertTrue($this->rb->finalizePayment($paymentId));
	}
	/**
	 * Test: Credit full payment (deprecated method)
	 */
	function testCreditFullPaymentDeprecated() {
		$paymentId = $this->getPaymentIdFromOrderByClientChoice();
		$this->rb->finalizePayment($paymentId);
		$this->assertTrue($this->rb->creditPayment($paymentId));
	}
	/**
	 * Test: Cancel full payment (deprecated method)
	 */
	function testCancelFullPaymentDeprecated() {
		$paymentId = $this->getPaymentIdFromOrderByClientChoice();
		$this->rb->finalizePayment($paymentId);
		$this->assertTrue($this->rb->cancelPayment($paymentId));
	}

	function testAfterShopSanitizer() {
		$paymentId = $this->getPaymentIdFromOrderByClientChoice(2);
		$sanitizedShopSpec = $this->rb->sanitizeAfterShopSpec($paymentId, ResursAfterShopRenderTypes::FINALIZE);
		$this->assertCount(2, $sanitizedShopSpec);
	}

	/**
	 * Reset the connection to simulate a true scenario
	 * @return bool
	 */
	private function resetConnection() {
		$isEmpty = false;
		$this->setUp();
		try { $this->rb->getPayload(); } catch (\Exception $emptyPayloadException) { $isEmpty = true; }
		return $isEmpty;
	}

	/**
	 * Test: Aftershop finalization, new method
	 * Expected result: The order is fully debited
	 */
	function testAftershopFullFinalization() {
		$paymentId = $this->getPaymentIdFromOrderByClientChoice(2);
		if ($this->resetConnection()) {
			$this->rb->setAfterShopInvoiceExtRef( "Test Testsson" );
			$finalizeResult = $this->rb->paymentFinalize( $paymentId );
			$testOrder = $this->rb->getPaymentSpecCount($paymentId);
			$this->assertTrue(($finalizeResult == 200 && $testOrder['AUTHORIZE'] == 2 && $testOrder['DEBIT'] == 2));
		}
	}

	/**
	 * Test: Aftershop finalization, new method, automated by using addOrderLine
	 * Expected result: Two rows, one added row debited
	 */
	function testAftershopPartialAutomatedFinalization() {
		// Add one order line to the random one
		$this->rb->addOrderLine( "myAdditionalPartialAutomatedOrderLine", "One orderline added with addOrderLine", 100, 25 );
		$paymentId = $this->getPaymentIdFromOrderByClientChoice(1);
		if ($this->resetConnection()) {
			$this->rb->setAfterShopInvoiceExtRef( "Test Testsson" );
			// Add the orderLine that should be handled in the finalization
			// id, desc, unitAmoutWithoutVat, vatPct, unitMeasure, ORDER_LINE, quantity
			$this->rb->addOrderLine( "myAdditionalPartialAutomatedOrderLine", "One orderline added with addOrderLine", 100, 25 );
			$finalizeResult = $this->rb->paymentFinalize( $paymentId );
			$testOrder = $this->rb->getPaymentSpecCount($paymentId);
			$this->assertTrue(($finalizeResult == 200 && $testOrder['AUTHORIZE'] == 2 && $testOrder['DEBIT'] == 1));
		}
	}

	/**
	 * Test: Aftershop finalization, new method, automated by using addOrderLine
	 * Expected result: Two rows, the row with 4 in quantity has 2 debited
	 */
	function testAftershopPartialAutomatedQuantityFinalization() {
		// Add one order line to the random one, with 4 in quantity
		$this->rb->addOrderLine( "myAdditionalAutomatedOrderLine", "One orderline added with addOrderLine", 100, 25, 'st', 'ORDER_LINE', 4 );
		$paymentId = $this->getPaymentIdFromOrderByClientChoice(1);
		if ($this->resetConnection()) {
			$this->rb->setAfterShopInvoiceExtRef( "Test Testsson" );
			// Add the orderLine that should be handled in the finalization, but only 2 of the set up above
			$this->rb->addOrderLine( "myAdditionalAutomatedOrderLine", "One orderline added with addOrderLine", 100, 25, 'st', 'ORDER_LINE', 2 );
			$finalizeResult = $this->rb->paymentFinalize( $paymentId );
			$countOrder = $this->rb->getPaymentSpecCount($paymentId);
			$testOrder = $this->rb->getPaymentSpecByStatus($paymentId);
			// Also check the quantity on this
			$this->assertTrue(($finalizeResult == 200 && $countOrder['AUTHORIZE'] == 2 && $countOrder['DEBIT'] == 1 && (int)$testOrder['DEBIT']['0']->quantity == 2));
		}
	}

	/**
	 * Test: Aftershop finalization, new method, automated by using addOrderLine
	 * Expected result: Two rows, one row (the correct one) row debited
	 */
	function testAftershopPartialManualFinalization() {
		// Add one order line to the random one
		$this->rb->addOrderLine( "myAdditionalManualOrderLine", "One orderline added with addOrderLine", 100, 25 );
		$paymentId = $this->getPaymentIdFromOrderByClientChoice(1);
		if ($this->resetConnection()) {
			$this->rb->setAfterShopInvoiceExtRef( "Test Testsson" );
			$newArray = array(
				'artNo' => 'myAdditionalManualOrderLine',
				'description' => "One orderline added with addOrderLine",
				'unitAmountWithoutVat' => 100,
				'vatPct' => 25,
				'quantity' => 1
			);
			$finalizeResult = $this->rb->paymentFinalize( $paymentId, $newArray );
			$testOrder = $this->rb->getPaymentSpecCount($paymentId);
			$this->assertTrue(($finalizeResult == 200 && $testOrder['AUTHORIZE'] == 2 && $testOrder['DEBIT'] == 1));
		}
	}
	/**
	 * Test: Aftershop finalization, new method, manually added array that mismatches with the first order (This order will have one double debited orderLine)
	 * Expected result: Three rows, mismatching row debited
	 */
	function testAftershopPartialManualFinalizationWithMismatchingKeys() {
		// Add one order line to the random one
		$this->rb->addOrderLine( "myAdditionalManualOrderLine", "One orderline added with addOrderLine", 100, 25 );
		$paymentId = $this->getPaymentIdFromOrderByClientChoice(1);
		if ($this->resetConnection()) {
			$this->rb->setAfterShopInvoiceExtRef( "Test Testsson" );
			$newArray = array(
				'artNo' => 'myAdditionalMismatchingOrderLine',
				'description' => "One orderline added with addOrderLine",
				'unitAmountWithoutVat' => 101,
				'vatPct' => 25,
				'quantity' => 2
			);
			$finalizeResult = $this->rb->paymentFinalize( $paymentId, $newArray );
			$countOrder = $this->rb->getPaymentSpecCount($paymentId);
			$testOrder = $this->rb->getPaymentSpecByStatus($paymentId);
			$this->assertTrue(($finalizeResult == 200 && $countOrder['AUTHORIZE'] == 2 && $countOrder['DEBIT'] == 1 && (int)$testOrder['DEBIT']['0']->quantity == 2));
		}
	}

}
