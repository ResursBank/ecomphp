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

require_once( '../source/classes/rbapiloader.php' );

/**
 * Class ResursBankTest: Primary test client
 *
 */
class ResursBankTest extends PHPUnit_Framework_TestCase {
	private $CURL;
	private $NETWORK;

	/**
	 * The heart of this unit. To make tests "nicely" compatible with 1.1, this should be placed on top of this class as it looks different there.
	 */
	private function initServices( $overrideUsername = null, $overridePassword = null ) {
		$wsdlPath = __DIR__ . "/../source/rbwsdl/";
		if ( file_exists( realpath( $wsdlPath ) ) ) {
			$this->hasWsdl = true;
		} else {
			$this->hasWsdl = false;
		}
		if ( empty( $overrideUsername ) ) {
			$this->rb = new \ResursBank( $this->username, $this->password );
		} else {
			$this->rb = new \ResursBank( $overrideUsername, $overridePassword );
		}
		/*
		 * If HTTP_HOST is not set, Resurs Checkout will not run properly, since the iFrame requires a valid internet connection (actually browser vs http server).
		 */
		if ( ! isset( $_SERVER['HTTP_HOST'] ) ) {
			$_SERVER['HTTP_HOST'] = "localhost";
		}
	}

	////////// Public variables
	public $ignoreDefaultTests = false;
	public $ignoreBookingTests = false;
	public $ignoreSEKKItests = false;
	public $ignoreUrlExternalValidation = false;
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
	/** @var array Expected payment method count (SE) */
	public $paymentMethodCount = array(
		'mock'    => 5,
		'nonmock' => 5
	);
	/** @var array Expected payment method cound (NO) */
	public $paymentMethodCountNorway = array( 'mock' => 3 );

	/** Before each test, invoke this */
	public function setUp() {}

	/** After each test, invoke this */
	public function tearDown() {}

	////////// Private variables
	/** @var string Defines what environment should be running */
	private $environmentName = "mock";
	/** @var null|ResursBank API Connector */
	private $rb = null;
	private $hasWsdl;
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
	private $zeroSpecLine = false;
	private $zeroSpecLineZeroTax = false;
	private $alwaysUseExtendedCustomer = true;
	private $allowObsoletePHP = false;


	/**
	 * Prepare by initializing API Loader and stubs
	 *
	 */
	public function __construct() {
		$this->CURL = new \TorneLIB\Tornevall_cURL();
		$this->NETWORK = new \TorneLIB\TorneLIB_Network();

		if ( version_compare( PHP_VERSION, '5.3.0', "<" ) ) {
			if ( ! $this->allowObsoletePHP ) {
				throw new ResursException( "PHP 5.3 or later are required for this module to work. If you feel safe with running this with an older version, please see " );
			}
		}

		register_shutdown_function( array( $this, 'shutdownSuite' ) );
		if ( $this->environmentName === "nonmock" ) {
			$this->username = $this->usernameNonmock;
			$this->password = $this->passwordNonmock;
		}

		$this->setupConfig();

		/* Set up default government id for bookings */
		$this->testGovId       = $this->govIdNatural;
		$this->testGovIdNorway = $this->govIdNaturalNorway;
		$this->initServices();
	}

	private function setupConfig() {
		if ( file_exists( 'test.json' ) ) {
			$config = json_decode( file_get_contents( "test.json" ) );
			if ( isset( $config->mock->username ) ) {
				$this->username       = $config->mock->username;
				$this->usernameSweden = $this->username;
			}
			if ( isset( $config->mock->password ) ) {
				$this->password       = $config->mock->password;
				$this->passwordSweden = $this->password;
			}
			if ( isset( $config->sweden->username ) ) {
				$this->username       = $config->sweden->username;
				$this->usernameSweden = $this->username;
			}
			if ( isset( $config->sweden->password ) ) {
				$this->password       = $config->sweden->password;
				$this->passwordSweden = $this->password;
			}
			if ( isset( $config->norway->username ) ) {
				$this->usernameNorway = $config->norway->username;
			}
			if ( isset( $config->norway->password ) ) {
				$this->passwordNorway = $config->norway->password;
			}
			if ( isset( $config->nonmock->username ) ) {
				$this->usernameNonmock = $config->nonmock->username;
			}
			if ( isset( $config->nonmock->password ) ) {
				$this->passwordNonmock = $config->nonmock->password;
			}
			if ( isset( $config->alertReceivers ) && is_array( $config->alertReceivers ) ) {
				$this->alertReceivers = $config->alertReceivers;
			}
			if ( isset( $config->alertFrom ) && is_array( $config->alertFrom ) ) {
				$this->alertFrom = $config->alertFrom;
			}
			if ( isset( $config->availableMethods ) ) {
				foreach ( $config->availableMethods as $methodId => $methodObject ) {
					$this->availableMethods[ $methodId ] = $methodObject;
				}
			}
			if ( isset( $config->availableMethodsNorway ) ) {
				foreach ( $config->availableMethodsNorway as $methodId => $methodObject ) {
					$this->availableMethodsNorway[ $methodId ] = $methodObject;
				}
			}
			if ( isset( $config->callbackUrl ) ) {
				$this->callbackUrl = $config->callbackUrl;
			}
			if ( isset( $config->signUrl ) ) {
				$this->signUrl = $config->signUrl;
			}
		}
	}

	/**
	 * Initialization of environment with ability to change into others.
	 */
	private function checkEnvironment() {
/*		if ( $this->environmentName === "nonmock" ) {
			$this->rb->setNonMock();
		}*/
		$this->initServices();
	}

	/**
	 * Check if environment is working by making a getPaymentMethods-call.
	 *
	 * @return bool If everything works, we get our payment methods and returns true. All exceptions says environment is down.
	 */
	private function isUp() {
		try {
			$paymentMethods = $this->rb->getPaymentMethods();
		} catch ( Exception $e ) {
			return false;
		}
		if ( count( $paymentMethods ) > 0 ) {
			return true;
		}
	}

	/**
	 * Send mail alerts to defined users in case of special errors
	 */
	private function alertSender() {
		$checkMessage = trim( $this->alertMessage );
		if ( ! empty( $checkMessage ) ) {
			//$message = 'Following problems occured during the running of PHPApi TestSuite:' . "\n" . $this->alertMessage;
			$message = trim( $this->alertMessage );
			foreach ( $this->alertReceivers as $receiver ) {
				mail( $receiver, "PHPApi TestSuite Alert [" . $this->environmentName . "]", $message, "From: " . $this->alertFrom . "\nContent-Type: text/plain" );
			}
		}
		//$this->alertMessage = null;
	}

	/**
	 * Prepare a compiled message to send, on errors
	 *
	 * @param string $message A message to render for alerts (experimental)
	 */
	private function alertRender( $message = "" ) {
		if ( ! empty( $message ) ) {
			$this->alertMessage .= $message . "\n";
		}
	}

	/**
	 * Randomize (not hash) code
	 *
	 * @return null|string A standard nonComplex string
	 */
	private function mkpass() {
		$retp               = null;
		$characterListArray = array(
			'abcdefghijklmnopqrstuvwxyz',
			'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
			'0123456789',
		);
		//'!@#$%*?'
		$chars              = array();
		$max                = 10; // This is for now static
		foreach ( $characterListArray as $charListIndex => $charList ) {
			for ( $i = 0; $i <= ceil( $max / sizeof( $characterListArray ) ); $i ++ ) {
				$chars[] = $charList{mt_rand( 0, ( strlen( $charList ) - 1 ) )};
			}
		}
		shuffle( $chars );
		$retp = implode( "", $chars );

		return $retp;
	}

	/**
	 * Randomly pick up a payment method (name) from current representative.
	 *
	 * @return mixed
	 * @throws Exception
	 */
	private function getAMethod() {
		$methods      = null;
		$currentError = null;
		try {
			$methods = $this->rb->getPaymentMethods();
		} catch ( Exception $e ) {
			$currentError = $e->getMessage();
		}
		if ( is_array( $methods ) ) {
			$method = array_pop( $methods );
			$id     = $method->id;

			return $id;
		}
		throw new Exception( "Cannot receive a random payment method from ecommerce" . ( ! empty( $currentError ) ? " ($currentError)" : "" ) );
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
	private function doBookPayment( $setMethod = '', $bookSuccess = true, $forceSigning = false, $signSuccess = true, $country = 'SE', $ownSpecline = array() ) {
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
		/* Set unit amount higher (than 500 as before) so we may pass boundaries in tests */
		//$bookData['type'] = "hosted";
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
			$this->rb->prepareCardData( $this->cardNumber, false );
		}
		if ( isset( $useMethodList['card_new'] ) && $setMethod == $useMethodList['card_new'] ) {
			$useGovId = $this->cardGovId;
			$this->rb->prepareCardData( null, true );
		}
		$bookData['paymentData']['waitForFraudControl'] = $this->waitForFraudControl;
		$bookData['signing']                            = array(
			'successUrl'   => $this->signUrl . '&success=true',
			'failUrl'      => $this->signUrl . "&success=false",
			'forceSigning' => $forceSigning
		);

		if ( $paymentServiceSet !== ResursMethodTypes::METHOD_CHECKOUT ) {
			$res = $this->rb->bookPayment( $setMethod, $bookData );
		} else {
			$res = $this->rb->bookPayment( $this->rb->getPreferredPaymentId(), $bookData );
		}

		/*
		 * bookPaymentStatus is for simplified flows only
		 */
		if ( isset( $res->bookPaymentStatus ) ) {
			$bookStatus = $res->bookPaymentStatus;
		}

		if ( $paymentServiceSet == ResursMethodTypes::METHOD_CHECKOUT ) {
			return $res;
		}

		if ( $bookStatus == "SIGNING" ) {
			if ( $this->environmentName === "mock" ) {
				/* Pick up the signing url */
				$signUrl           = $res->signingUrl;
				$getSigningPage    = file_get_contents( $signUrl );
				$Network           = new \TorneLIB\TorneLIB_Network();
				$signUrlHostInfo   = $Network->getUrlDomain( $signUrl );
				$getUrlHost        = $signUrlHostInfo[1] . "://" . $signUrlHostInfo[0];
				$mockSuccessUrl    = preg_replace("/\/$/", '', $getUrlHost . "/" . preg_replace( '/(.*?)\<a href=\"(.*?)\">(.*?)\>Mock success(.*)/is', '$2', $getSigningPage ));
				$getSuccessContent = $this->CURL->getParsedResponse($this->CURL->doPost($mockSuccessUrl));
				if (isset($getSuccessContent->_GET->success)) {
					if ( $getSuccessContent->_GET->success == "true" ) {
						if ( $signSuccess ) {
							return true;
						} else {
							return false;
						}
					}
					if ( $getSuccessContent->_GET->success == "false" ) {
						if ( ! $signSuccess ) {
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
		} elseif ( $bookStatus == "FROZEN" ) {
			return true;
		} elseif ( $bookStatus == "BOOKED" ) {
			return true;
		} elseif ( $bookStatus == "DENIED" ) {
			if ( $bookSuccess ) {
				return false;
			} else {
				return true;
			}
		}

		return false;
	}

	/** Setup a country for webservices */
	private function setCountry( $country = 'SE' ) {
		if ( $country == "SE" ) {
			$this->username = $this->usernameSweden;
			$this->password = $this->passwordSweden;
		} elseif ( $country == "NO" ) {
			$this->username = $this->usernameNorway;
			$this->password = $this->passwordNorway;
		}
		/* Re-Initialize services if country has changed */
		if ( $this->chosenCountry != $country ) {
			$this->initServices();
		}
		$this->chosenCountry = $country;
	}


	/*********** PUBLICS ************/

	/**
	 * Test the availability of the wsdl sources
	 */
	public function testNoWsdl() {
		if ( $this->hasWsdl === true ) {
			$this->markTestSkipped( "Non-Wsdl tests are skipped since the wsdl files are in place (this test requires the difference)" );
		}
		/*
		 * As we do not have a present version of the wsdl stubs when reaching this point, we will not be able to set up the preferred method engine
		 * as the simplified stream at all. What has to be done is to make those calls independent to the loss of stubs. By doing this, we will be
		 * able to leave the needs of stubs in all future versions where we are calling internal functions.
		 */
		$this->assertTrue( ! $this->hasWsdl );
	}

	public function getSpecLine( $specialSpecline = array() ) {
		if ( count( $specialSpecline ) ) {
			return $specialSpecline;
		}

		return array(
			'artNo'                => 'EcomPHP-testArticle-' . rand( 1, 1024 ),
			'description'          => 'EComPHP Random Test Article number ' . rand( 1, 1024 ),
			'quantity'             => 1,
			'unitAmountWithoutVat' => intval( rand( 1000, 10000 ) ),
			'vatPct'               => 25
		);
	}

	public function getSpecLineZero( $specialSpecline = array(), $zeroTax = false ) {
		if ( count( $specialSpecline ) ) {
			return $specialSpecline;
		}

		return array(
			'artNo'                => 'EcomPHP-testArticle-' . rand( 1, 1024 ),
			'description'          => 'EComPHP Random Test Article number ' . rand( 1, 1024 ),
			'quantity'             => 1,
			'unitAmountWithoutVat' => 0,
			'vatPct'               => $zeroTax ? 0 : 25
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
	public function setObsoletePhp( $activate = false ) {
		$this->allowObsoletePHP = $activate;
	}

	/**
	 * When suite is about to shut down, run a collection of functions before completion.
	 */
	public function shutdownSuite() {
		$this->alertSender();
	}


	/*********** TESTS ************/

	/**
	 * Test if environment is ok
	 */
	public function testGetEnvironment() {
		if ( $this->ignoreDefaultTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$this->assertTrue( $this->isUp() === true );
	}

	/**
	 * Test if payment methods works properly
	 */
	public function testGetPaymentMethods() {
		if ( $this->ignoreDefaultTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$paymentMethods = $this->rb->getPaymentMethods();
		if ( ! count( $paymentMethods ) ) {
			$this->alertRender( "No payment methods received from ecommerce" );
		}
		$this->assertTrue( count( $paymentMethods ) > 0 );
	}

	/**
	 * Make sure that all payment methods set up for the representative is there
	 */
	public function testGetPaymentMethodsAll() {
		if ( $this->ignoreDefaultTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$paymentMethods = $this->rb->getPaymentMethods();
		if ( count( $paymentMethods ) !== $this->paymentMethodCount[ $this->environmentName ] ) {
			$this->alertRender( "Payment method mismatch - got " . count( $paymentMethods ) . ", expected 5." );
		}
		$this->assertTrue( count( $paymentMethods ) === $this->paymentMethodCount[ $this->environmentName ] );
	}

	/**
	 * Just like testGetPaymentMethodsAll, but converted to an array with the internal function objectsIntoArray
	 * @deprecated 1.0.0
	 */
	public function testGetPaymentMethodsArray() {
		if ( $this->ignoreDefaultTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$paymentMethods = $this->rb->objectsIntoArray($this->rb->getPaymentMethods());
		if ( count( $paymentMethods ) !== $this->paymentMethodCount[ $this->environmentName ] ) {
			$this->alertRender( "Payment method mismatch - got " . count( $paymentMethods ) . ", expected 5." );
		}
		$this->assertTrue( count( $paymentMethods ) === $this->paymentMethodCount[ $this->environmentName ] );
	}

	/**
	 * getAddress, NATURAL
	 */
	public function testGetAddressNatural() {
		if ( $this->ignoreDefaultTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$getAddressData = array();
		try {
			$getAddressData = $this->rb->getAddress( $this->govIdNatural, 'NATURAL', '127.0.0.1' );
		} catch ( Exception $e ) {
		}
		$this->assertTrue( ! empty( $getAddressData->fullName ) );
	}

	/**
	 * getAddress, LEGAL, Civic number
	 */
	public function testGetAddressLegalCivic() {
		if ( $this->ignoreDefaultTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$getAddressData = array();
		try {
			$getAddressData = $this->rb->getAddress( $this->govIdLegalCivic, 'LEGAL', '127.0.0.1' );
		} catch ( Exception $e ) {
		}
		$this->assertTrue( ! empty( $getAddressData->fullName ) );
	}

	/**
	 * getAddress, LEGAL, Organization number
	 */
	public function testGetAddressLegalOrg() {
		if ( $this->ignoreDefaultTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$getAddressData = array();
		try {
			$getAddressData = $this->rb->getAddress( $this->govIdLegalOrg, 'LEGAL', '127.0.0.1' );
		} catch ( Exception $e ) {
		}
		$this->assertTrue( ! empty( $getAddressData->fullName ) );
	}

	/**
	 * Testing of annuity factors (if they exist), with the first found payment method
	 */
	public function testGetAnnuityFactors() {
		if ( $this->ignoreDefaultTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$annuity = false;
		$methods = $this->rb->getPaymentMethods();
		if ( is_array( $methods ) ) {
			$method  = array_pop( $methods );
			$id      = $method->id;
			$annuity = $this->rb->getAnnuityFactors( $id );
		}
		$this->assertTrue( count( $annuity ) > 1 );
	}

	/**
	 * Test booking.
	 * Payment Method: Invoice
	 * Customer Type: NATURAL, GRANTED
	 */
	public function testBookSimplifiedPaymentInvoiceNatural() {
		if ( $this->ignoreBookingTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$bookResult = $this->doBookPayment( $this->availableMethods['invoice_natural'], true, false, true );
		$this->assertTrue( $bookResult );
	}

	/**
	 * Test booking and always use extendedCustomer.
	 * Payment Method: Invoice
	 * Customer Type: NATURAL, GRANTED
	 */
	public function testBookPaymentInvoiceExtendedNatural() {
		if ( $this->ignoreBookingTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$this->rb->alwaysUseExtendedCustomer = true;
		$bookResult                          = $this->doBookPayment( $this->availableMethods['invoice_natural'], true, false, true );
		$this->assertTrue( $bookResult );
	}

	/**
	 * Test findPayments()
	 */
	public function testFindPayments() {
		$this->checkEnvironment();
		$paymentList = $this->rb->findPayments();
		$this->assertGreaterThan(0, count($paymentList));
	}

	/**
	 * Book and see if there is a payment registered at Resurs Bank
	 */
	public function testGetPayment() {
		if ( $this->ignoreBookingTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		if (!$this->hasWsdl) {
			// If we are running this test in nonWsdl-mode, we can probably pick up an old order from findPayments
			$paymentList = $this->rb->findPayments();
			if (is_array($paymentList) && count($paymentList)) {
				$existingPayment = array_pop( $paymentList );
				$payment = $this->rb->getPayment($existingPayment->paymentId);
				$this->assertTrue( $payment->id == $existingPayment->paymentId );
			}
		} else {
			$bookResult      = $this->doBookPayment( $this->availableMethods['invoice_natural'], true, false, true );
			$bookedPaymentId = $this->rb->getPreferredPaymentId();
			$payment         = $this->rb->getPayment( $bookedPaymentId );
			$this->assertTrue( $bookResult && $payment->id == $bookedPaymentId );
		}

	}

	/*
	 * Test booking with zero amount
	 * Expected result: Fail.
	 */
	public function testBookPaymentZeroInvoiceNatural() {
		if ( $this->ignoreBookingTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$this->zeroSpecLine = true;
		$hasException       = false;
		try {
			$this->doBookPayment( $this->availableMethods['invoice_natural'], true, false, true );
		} catch ( Exception $exceptionWanted ) {
			$hasException = true;
		}
		$this->assertTrue( $hasException );
	}

	/**
	 * Test booking.
	 * Payment Method: Invoice
	 * Customer Type: NATURAL, DENIED
	 */
	public function testBookPaymentInvoiceNaturalDenied() {
		if ( $this->ignoreBookingTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$this->testGovId = $this->govIdNaturalDenied;
		$bookResult      = $this->doBookPayment( $this->availableMethods['invoice_natural'], false, false, true );
		$this->assertTrue( $bookResult );
	}

	/**
	 * Test booking
	 * Payment Method: Invoice
	 * Customer Type: NATURAL, DENIED
	 */
	public function testBookPaymentInvoiceLegal() {
		if ( $this->ignoreBookingTests ) {
			$this->markTestSkipped();
		}
		$this->username = $this->usernameSweden;
		$this->password = $this->passwordSweden;
		$this->checkEnvironment();
		$this->testGovId = $this->govIdLegalOrg;
		$bookResult      = $this->doBookPayment( $this->availableMethods['invoice_legal'], false, false, true );
		$this->assertTrue( $bookResult );
	}

	/**
	 * Test booking with a card
	 */
	public function testBookPaymentCard() {
		if ( $this->ignoreBookingTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$bookResult = $this->doBookPayment( $this->availableMethods['card'], true, false, true, 'SE' );
		$this->assertTrue( $bookResult === true );
	}

	/**
	 * Test booking with new card
	 */
	public function testBookPaymentNewCard() {
		if ( $this->ignoreBookingTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$bookResult = $this->doBookPayment( $this->availableMethods['card_new'], true, false, true, 'SE' );
		$this->assertTrue( $bookResult === true );
	}

	/**
	 * Test booking (NO).
	 * Payment Method: Invoice
	 * Customer Type: NATURAL, GRANTED
	 */
	public function testBookPaymentInvoiceNaturalNorway() {
		if ( $this->ignoreBookingTests ) {
			$this->markTestSkipped();
		}
		$this->checkEnvironment();
		$bookResult = $this->doBookPayment( $this->availableMethodsNorway['invoice_natural'], true, false, true, 'NO' );
		$this->assertTrue( $bookResult === true );
	}

	/**
	 * Test chosen payment method sekki-generator
	 * @throws Exception
	 */
	public function testSekkiSimple() {
		$this->checkEnvironment();
		if ( $this->ignoreSEKKItests ) {
			$this->markTestSkipped();
		}
		$methodSimple = $this->getAMethod();
		$amount       = rand( 1000, 10000 );
		$sekkiUrls    = $this->rb->getSekkiUrls( $amount, $methodSimple );
		$matches      = 0;
		$appenders    = 0;
		if ( is_array( $sekkiUrls ) ) {
			foreach ( $sekkiUrls as $UrlData ) {
				if ( $UrlData->appendPriceLast ) {
					$appenders ++;
					if ( preg_match( "/amount=$amount/i", $UrlData->url ) ) {
						$matches ++;
					}
				}
			}
		}
		$this->assertTrue( $matches === $appenders );
	}

	/**
	 * Test pre-fetched sekki-url-generator
	 * @throws Exception
	 */
	public function testSekkiArray() {
		$this->checkEnvironment();
		if ( $this->ignoreSEKKItests ) {
			$this->markTestSkipped();
		}
		$methodSimple   = $this->getAMethod();
		$amount         = rand( 1000, 10000 );
		$preparedMethod = $this->rb->getPaymentMethodSpecific( $methodSimple );
		if ( isset( $preparedMethod->legalInfoLinks ) ) {
			$sekkiUrls = $this->rb->getSekkiUrls( $amount, $preparedMethod->legalInfoLinks );
			$matches   = 0;
			$appenders = 0;
			if ( is_array( $sekkiUrls ) ) {
				foreach ( $sekkiUrls as $UrlData ) {
					if ( $UrlData->appendPriceLast ) {
						$appenders ++;
						if ( preg_match( "/amount=$amount/i", $UrlData->url ) ) {
							$matches ++;
						}
					}
				}
			}
			$this->assertTrue( $matches === $appenders );
		}
	}

	/**
	 * Test all payment methods
	 */
	public function testSekkiAll() {
		if ( $this->ignoreSEKKItests ) {
			$this->markTestSkipped();
		}
		$amount    = rand( 1000, 10000 );
		$sekkiUrls = $this->rb->getSekkiUrls( $amount );
		foreach ( $sekkiUrls as $method => $sekkiUrls ) {
			$matches   = 0;
			$appenders = 0;
			if ( is_array( $sekkiUrls ) ) {
				foreach ( $sekkiUrls as $UrlData ) {
					if ( $UrlData->appendPriceLast ) {
						$appenders ++;
						if ( preg_match( "/amount=$amount/i", $UrlData->url ) ) {
							$matches ++;
						}
					}
				}
			}
		}
		$this->assertTrue( $matches === $appenders );
	}

	/**
	 * Test curstom url
	 */
	public function testSekkiCustom() {
		$this->checkEnvironment();
		if ( $this->ignoreSEKKItests ) {
			$this->markTestSkipped();
		}
		$amount    = rand( 1000, 10000 );
		$URL       = "https://test.resurs.com/customurl/index.html?content=true&secondparameter=true";
		$customURL = $this->rb->getSekkiUrls( $amount, null, $URL );
		$this->assertTrue( ( preg_match( "/amount=$amount/i", $customURL ) ? true : false ) );
	}

	/**
	 * This test is incomplete.
	 *
	 * @param bool $returnTheFrame
	 *
	 * @return bool|null
	 */
	private function getCheckoutFrame( $returnTheFrame = false, $returnPaymentReference = false ) {
		$assumeThis = false;
		if ( $returnTheFrame ) {
			$iFrameUrl = false;
		}
		if ( $this->ignoreBookingTests ) {
			$this->markTestSkipped();
		}
		$this->rb->setPreferredPaymentService( ResursMethodTypes::METHOD_CHECKOUT );
		$newReferenceId = $this->rb->generatePreferredId();
		$bookResult = $this->doBookPayment( $newReferenceId, true, false, true );

		if ( is_string( $bookResult ) && preg_match( "/iframe src/i", $bookResult ) ) {
			$iFrameUrl     = $this->rb->getIframeSrc( $bookResult );
			$iframeContent = $this->CURL->doGet( $iFrameUrl );
			if ( ! empty( $iframeContent['body'] ) ) {
				$assumeThis = true;
			}
		}
		if ($returnPaymentReference) {
			return $this->rb->getPreferredPaymentId();
		}
		if ( ! $returnTheFrame ) {
			return $assumeThis;
		} else {
			return $iFrameUrl;
		}
	}

	/**
	 * Try to fetch the iframe (Resurs Checkout). When the iframe url has been received, check if there's content.
	 */
	public function testGetCheckoutFrame() {
		$this->checkEnvironment();
		$getFrameUrl = $this->getCheckoutFrame( true );
		$UrlDomain = $this->NETWORK->getUrlDomain($getFrameUrl);
		// If there is no https defined in the frameUrl, the test might have failed
		if (array_pop($UrlDomain) == "https") {
			$FrameContent = $this->CURL->doGet($getFrameUrl);
			$this->assertTrue($FrameContent['code'] == 200 && strlen($FrameContent['body']) > 1024);
		}
	}

    /**
     * Try to update a payment reference by first creating the iframe
     */
	public function testUpdatePaymentReference() {
        $this->checkEnvironment();
        try {
	        $iFrameUrl = $this->getCheckoutFrame( true );
        } catch (Exception $e) {
        	$this->markTestIncomplete("Exception: " . $e->getMessage());
        }
        $this->CURL->setLocalCookies(true);
		$iframeRequest = $this->CURL->doGet( $iFrameUrl );
		$iframeContent = $iframeRequest['body'];
		$iframePaymentReference = $this->rb->getPreferredPaymentId();
		$Success = false;
        if (!empty($iframePaymentReference) && !empty($iFrameUrl) && !empty($iframeContent) && strlen($iframeContent) > 1024) {
	        $newReference = $this->rb->generatePreferredId();
	        try {
		        $Success = $this->rb->updatePaymentReference( $iframePaymentReference, $newReference );
	        } catch (Exception $e) {
	        	$this->markTestIncomplete("Exception: " . $e->getCode() . ": " . $e->getMessage());
	        }
        }
        $this->assertTrue($Success === true);
    }

	/**
	 * Get all callbacks by a rest call (objects)
	 */
	public function testGetCallbackListByRest() {
		$this->assertGreaterThan(0, count($this->rb->getCallBacksByRest()));
	}

	/**
	 * Get all callbacks by a rest call (key-indexed array)
	 */
	public function testGetCallbackListAsArrayByRest() {
		$this->assertGreaterThan(0, count($this->rb->getCallBacksByRest(true)));
	}

	/**
	 * Testing add metaData, adding random data to a payment
	 */
	public function testAddMetaData() {
		$paymentData = null;
		$chosenPayment = 0;
		$paymentId = null;

		$paymentList = $this->rb->findPayments();
		// For some reason, we not always get a valid order
		$preventLoop = 0;
		while (!isset($paymentList[$chosenPayment]) && $preventLoop++ < 10) {$chosenPayment = rand(0,count($paymentList));}

		if (isset($paymentList[$chosenPayment])) {
			$paymentData = $paymentList[ $chosenPayment ];
			$paymentId   = $paymentData->paymentId;
			$this->assertTrue($this->rb->addMetaData($paymentId, "RandomKey" . rand(1000,1999), "RandomValue" . rand(2000,3000)));
		} else {
			$this->markTestIncomplete("No valid payment found");
		}
	}

	/**
	 * Testing add metaData, with a faulty payment id
	 */
	public function testAddMetaDataFailure() {
		$paymentData = null;
		$chosenPayment = 0;
		$paymentId = null;
		$hasException = false;
		try {
			$this->rb->addMetaData( "UnexistentPaymentId", "RandomKey" . rand( 1000, 1999 ), "RandomValue" . rand( 2000, 3000 ) );
		} catch (\Exception $e) {
			$this->assertTrue(true);
			$hasException = true;
		}
		if (!$hasException) {$this->markTestSkipped("addMetaDataFailure failed since it never got an exception");}
	}

	/**
	 * Test getCostOfPurchase
	 */
	function testGetCostOfPurcase() {
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
	private function renderCallbackData($UseCurl = false, $UseUrlRewrite = false) {
		$this->checkEnvironment();
		$returnCallbackArray = array();
		$parameter = array(
			'ANNULMENT'               => array( 'paymentId' ),
			'FINALIZATION'            => array( 'paymentId' ),
			'UNFREEZE'                => array( 'paymentId' ),
			'AUTOMATIC_FRAUD_CONTROL' => array( 'paymentId', 'result' )
		);
		foreach ( $parameter as $callbackType => $parameterArray ) {
			$digestSaltString = $this->mkpass();
			$digestArray      = array(
				'digestSalt'       => $digestSaltString,
				'digestParameters' => $parameterArray
			);
			if ( $callbackType == "ANNULMENT" ) {
				$setCallbackType = ResursCallbackTypes::ANNULMENT;
			}
			if ( $callbackType == "AUTOMATIC_FRAUD_CONTROL" ) {
				$setCallbackType = ResursCallbackTypes::AUTOMATIC_FRAUD_CONTROL;
			}
			if ( $callbackType == "FINALIZATION" ) {
				$setCallbackType = ResursCallbackTypes::FINALIZATION;
			}
			if ( $callbackType == "UNFREEZE" ) {
				$setCallbackType = ResursCallbackTypes::UNFREEZE;
			}
			$renderArray = array();
			if ( is_array( $parameterArray ) ) {
				foreach ( $parameterArray as $parameterName ) {
					if (!$UseUrlRewrite) {
						$renderArray[] = $parameterName . "={" . $parameterName . "}";
					} else {
						$renderArray[] = $parameterName . "/{" . $parameterName . "}";
					}
				}
			}
			if (!$UseUrlRewrite) {
				$callbackURL = $this->callbackUrl . "?event=" . $callbackType . "&digest={digest}&" . implode( "&", $renderArray ) . "&lastReg=" . strftime( "%y%m%d%H%M%S", time() );
			} else {
				$callbackURL = $this->callbackUrl . "/event/" . $callbackType . "/digest/{digest}/" . implode( "/", $renderArray ) . "/lastReg/" . strftime( "%y%m%d%H%M%S", time() );
			}
			$returnCallbackArray[] = array($setCallbackType, $callbackURL, $digestArray);
		}
		return $returnCallbackArray;
	}

	/**
	 * Register new callback urls via SOAP
	 */
	public function testSetRegisterCallbacksSoap() {
		$this->checkEnvironment();
		$callbackArrayData = $this->renderCallbackData(true);
		$globalDigest = $this->rb->setCallbackDigest($this->mkpass());
		$cResponse = array();
		$this->rb->setRegisterCallbacksViaRest(false);
		foreach ($callbackArrayData as $indexCB => $callbackInfo) {
			$cResponse[$callbackInfo[0]] = $this->rb->setRegisterCallback( $callbackInfo[0], $callbackInfo[1] . "&via=soap", $callbackInfo[2] );
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
	public function testSetRegisterCallbacksSoapUrlRewrite() {
		$this->checkEnvironment();
		$callbackArrayData = $this->renderCallbackData(true, true);
		$globalDigest = $this->rb->setCallbackDigest($this->mkpass());
		$cResponse = array();
		$this->rb->setRegisterCallbacksViaRest(false);
		foreach ($callbackArrayData as $indexCB => $callbackInfo) {
			$cResponse[$callbackInfo[0]] = $this->rb->setRegisterCallback( $callbackInfo[0], $callbackInfo[1] . "&via=soap", $callbackInfo[2] );
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
	public function testSetRegisterCallbacksRest() {
		$callbackArrayData = $this->renderCallbackData(true);
		$cResponse = array();
		$this->checkEnvironment();
		$globalDigest = $this->rb->setCallbackDigest($this->mkpass());
		$this->rb->setRegisterCallbacksViaRest(true);
		foreach ($callbackArrayData as $indexCB => $callbackInfo) {
			$cbResult = $this->rb->setRegisterCallback( $callbackInfo[0], $callbackInfo[1] . "&via=rest", $callbackInfo[2] );
			if ($cbResult) {
				$cResponse[ $callbackInfo[0] ] = $cbResult;
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
	public function testSetRegisterCallbacksRestUrlRewrite() {
		$callbackArrayData = $this->renderCallbackData(true, true);
		$cResponse = array();
		$this->checkEnvironment();
		$globalDigest = $this->rb->setCallbackDigest($this->mkpass());
		$this->rb->setRegisterCallbacksViaRest(true);
		foreach ($callbackArrayData as $indexCB => $callbackInfo) {
			$cbResult = $this->rb->setRegisterCallback( $callbackInfo[0], $callbackInfo[1] . "&via=rest", $callbackInfo[2] );
			if ($cbResult) {
				$cResponse[ $callbackInfo[0] ] = $cbResult;
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

	public function testValidateExternalUrlSuccess() {
        $this->checkEnvironment();
        $callbackArrayData = $this->renderCallbackData(true);
        $this->rb->setValidateExternalCallbackUrl($callbackArrayData[0][1]);
	    $Reachable = $this->rb->validateExternalAddress();
		if ($Reachable !== ResursCallbackReachability::IS_FULLY_REACHABLE) {
			$this->markTestIncomplete("External address validation returned $Reachable instead of " . ResursCallbackReachability::IS_FULLY_REACHABLE . ".\nPlease check your callback url (".$callbackArrayData[0][1].") so that is properly configured and reachable.");
		}
	    $this->assertTrue($Reachable === ResursCallbackReachability::IS_FULLY_REACHABLE);
    }

    /**
     * Register new callback urls
     */
    public function testSetRegisterCallbacksWithValidatedUrlViaRest() {
	    if (!$this->ignoreUrlExternalValidation) {
		    $this->checkEnvironment();
		    $this->rb->setRegisterCallbacksViaRest(true);
		    $callbackArrayData = $this->renderCallbackData( true );
		    $this->rb->setCallbackDigest( $this->mkpass() );
		    $cResponse = array();
		    foreach ( $callbackArrayData as $indexCB => $callbackInfo ) {
			    try {
				    $this->rb->setValidateExternalCallbackUrl( $callbackInfo[1] . "&via=restValidated" );
				    $cResponse[ $callbackInfo[0] ] = $this->rb->setRegisterCallback( $callbackInfo[0], $callbackInfo[1] . "&via=restValidated", $callbackInfo[2] );
			    } catch ( \Exception $e ) {
				    $this->markTestIncomplete( "Exception thrown: URL Validation failed for ".(isset($callbackInfo[1]) && !empty($callbackInfo[1]) ? $callbackInfo[1] . "&via=restValidated" : "??")." during the setRegisterCallback procss (".$e->getMessage().")" );
			    }
		    }
		    $successFulCallbacks = 0;
		    foreach ( $cResponse as $cbType ) {
			    if ( $cbType == "1" ) {
				    $successFulCallbacks ++;
			    }
		    }
		    $this->assertEquals( count( $cResponse ), $successFulCallbacks );
	    } else {
		    $this->markTestSkipped("ignoreUrlExternalValidation is active, skipping test");
	    }
    }

	/**
	 * Testing of unregisterEventCallback via rest calls
	 */
	public function testUnregisterEventCallbackViaRest() {
		$this->checkEnvironment();
		$this->rb->setRegisterCallbacksViaRest(true);
		$this->assertTrue($this->rb->unregisterEventCallback(ResursCallbackTypes::ANNULMENT));
	}

	/**
	 * Testing of unregisterEventCallback via soap calls
	 */
	public function testUnregisterEventCallbackViaSoap() {
		$this->checkEnvironment();
		$this->rb->setRegisterCallbacksViaRest(false);
		$this->assertTrue($this->rb->unregisterEventCallback(ResursCallbackTypes::ANNULMENT));
	}

	/**
	 * Register new callback urls but without the digest key (Fail)
	 */
	public function testSetRegisterCallbacksWithoutDigest() {
		$this->checkEnvironment();
		$callbackArrayData = $this->renderCallbackData(true);
		try {
			foreach ( $callbackArrayData as $indexCB => $callbackInfo ) {
				$cResponse = $this->rb->setRegisterCallback( $callbackInfo[0], $callbackInfo[1], $callbackInfo[2] );
			}
		} catch (\Exception $e) {
			$this->assertTrue(!empty($e->getMessage()));
		}
	}

	/**
	 * Testing of callbacks
	 */
	public function testCallbacks() {
		if ( $this->ignoreDefaultTests ) {
			$this->markTestSkipped("Testing of deprecated callback function is disabled on request");
		}
		/* If disabled */
		if ( $this->disableCallbackRegMock || ( $this->disableCallbackRegNonMock && $this->environmentName === "nonmock" ) ) {
			$this->markTestSkipped("Testing of deprecated callback function is disabled due to special circumstances");
			return;
		}
		$callbackArrayData = $this->renderCallbackData();
		$callbackSetResult = array();
		$this->rb->setCallbackDigest($this->mkpass());
		foreach ($callbackArrayData as $indexCB => $callbackInfo) {
			try {
				$cResponse = $this->rb->setRegisterCallback( $callbackInfo[0], $callbackInfo[1], $callbackInfo[2] );
				$callbackSetResult[] = $callbackInfo[0];
			} catch (\Exception $e) {
				echo $e->getMessage();
			}
		}
		// Registered callbacks must be at least 4 to be successful, as there are at least 4 important callbacks to pass through
		$this->assertGreaterThanOrEqual( 4, count($callbackSetResult));
	}

	function testGetNextInvoiceNumber() {
		$this->assertTrue($this->rb->getNextInvoiceNumber() >= 1);
	}
	function testSetNextInvoiceNumber() {
		$NextInvoiceNumber = $this->rb->getNextInvoiceNumber(true, 1000);
		$this->assertEquals(1000, $this->rb->getNextInvoiceNumber());
	}
	function testReSetNextInvoiceNumber() {
		$NextInvoiceNumber = $this->rb->getNextInvoiceNumber(true, 1);
		print_R($NextInvoiceNumber);
		//$this->assertEquals(1000, $this->rb->getNextInvoiceNumber());
	}

	/// 1.0.2 features
	function testCreatePayment() {
		$this->checkEnvironment();
		try {
			$this->rb->setPreferredPaymentService(ResursMethodTypes::METHOD_CHECKOUT);
			//$this->rb->setBillingByGetAddress($this->rb->getAddress("198305147715", "NATURAL", "127.0.0.1"));
			$this->rb->setBillingAddress("Anders Andersson", "Anders", "Andersson", "Hamngatan 2", null, "12345", "Ingenstans", "SE");
			$this->rb->createPayment("A");
		} catch (\Exception $e) {
			$this->markTestIncomplete($e->getMessage());
		}
	}
	function testCreatePaymentWithPayload() {
		$this->checkEnvironment();
		$bookData['address']  = array(
			'fullName'    => 'Test Testsson',
			'firstName'   => 'Test',
			'lastName'    => 'Testsson',
			'addressRow1' => 'Testgatan 1',
			'postalArea'  => 'Testort',
			'postalCode'  => '12121',
			'country'     => 'SE'
		);
		$this->rb->setPreferredPaymentService(ResursMethodTypes::METHOD_CHECKOUT);
		try {
			$this->rb->createPayment("A", $bookData);
		} catch (\Exception $e) {
			$this->markTestIncomplete($e->getMessage());
		}
	}
}
