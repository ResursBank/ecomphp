<?php

/**
 * Resurs Bank EComPHP - Test suite, rebuild. This test suite is as of ECOMPHP-214 (https://resursbankplugins.atlassian.net/browse/ECOMPHP-214) under construction
 * to better match our current needs.
 *
 * @package EcomPHPTest
 * @author Resurs Bank AB, Tomas Tornevall <tomas.tornevall@resurs.se>
 * @version 0.2.0
 * @link https://test.resurs.com/docs/x/KYM0 Get started - PHP Section
 * @license Apache 2.0
 *
 */

if (file_exists(__DIR__ . "/../vendor/autoload.php")) {
	require_once(__DIR__ . '/../vendor/autoload.php');
} else {
	require_once('../source/classes/rbapiloader.php');
}

// Usages for v1.2
use PHPUnit\Framework\TestCase;
use \Resursbank\RBEcomPHP\ResursBank;
use \Resursbank\RBEcomPHP\RESURS_CALLBACK_TYPES;
use \Resursbank\RBEcomPHP\RESURS_PAYMENT_STATUS_RETURNCODES;
use \Resursbank\RBEcomPHP\RESURS_FLOW_TYPES;
use \Resursbank\RBEcomPHP\RESURS_CALLBACK_REACHABILITY;
use \Resursbank\RBEcomPHP\RESURS_AFTERSHOP_RENDER_TYPES;

// Split library section - Set up the correct curl- and network pointers here depending on release version
use \Resursbank\RBEcomPHP\Tornevall_cURL;
use \Resursbank\RBEcomPHP\TorneLIB_Network;

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

class ResursBankTest extends TestCase {

	/**
	 * @var ResursBank $API EComPHP
	 */
	protected $API;

	/** @var string Username to web services */
	private $username = "ecomphpPipelineTest";
	/** @var string Password to web services */
	private $password = "4Em4r5ZQ98x3891D6C19L96TQ72HsisD";

	function setUp() {
		$this->API = new ResursBank();
	}
	function tearDown() {

	}
	function testApiCredentials() {
		$this->API = new ResursBank($this->username, $this->password);
		$this->assertTrue(count($this->API->getPaymentMethods()) > 0);
	}

}
