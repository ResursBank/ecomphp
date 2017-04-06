<?php

namespace TorneLIB;

require_once("../classes/tornevall_network.php");


class Tornevall_cURLTest extends \PHPUnit_Framework_TestCase
{
    private $CURL;

    private $Urls;

    function __construct()
    {
        $this->CURL = new \TorneLIB\Tornevall_cURL();

        /*
         * Enable test mode
         */
        $this->CURL->setTestEnabled();

        /*
         * Set up testing URLS
         */
        $this->Urls = array(
            'simple' => 'http://identifier.tornevall.net/',
            'tests' => 'developer.tornevall.net/tests/tornevall_network/'
        );
    }

    private function simpleGet() {
        return $this->CURL->doGet($this->Urls['simple']);
    }

    /**
     * Make sure we always get a protocol
     * @param string $protocol
     * @return string
     */
    private function getProtocol($protocol = 'http') {
        if (empty($protocol)) {
            $protocol = "http";
        }
        return $protocol;
    }
    private function urlGet($parameters = '', $protocol = "http", $indexFile = 'index.php') {
        $theUrl = $this->getProtocol($protocol) . "://" . $this->Urls['tests'] . $indexFile . "?" . $parameters;
        return $this->CURL->doGet($theUrl);
    }
    private function urlPost($parameters = array(), $protocol = "http", $indexFile = 'index.php') {
        $theUrl = $this->getProtocol($protocol) . "://" . $this->Urls['tests'] . $indexFile;
        return $this->CURL->doPost($theUrl, $parameters);
    }
    private function hasBody($container) {
        if (is_array($container) && isset($container['body'])) {
            return true;
        }
    }
    private function getBody($container) {
        if ($this->hasBody($container)) {
            return $container['body'];
        }
    }
    private function getParsed($container) {
        if ($this->hasBody($container)) {
            return $container['parsed'];
        }
        return null;
    }
    private function pemDefault() {
        $this->CURL->_DEBUG_TCURL_UNSET_PEM_LOCATION = false;
    }


    /**
     * Runs a simple test to see if there is a container as it should
     */
    function testSimpleGet() {
        $this->pemDefault();
        $container = $this->simpleGet();
        $this->assertTrue($this->hasBody($container));
    }

    /**
     * Fetch a response and immediately pick up the parsed response, from the internally stored last response
     */
    function testGetParsedSelf() {
        $this->pemDefault();
        $this->urlGet("ssl&bool&o=json&method=get");
        $ParsedResponse = $this->CURL->getParsedResponse();
        $this->assertTrue(is_object($ParsedResponse));
    }

    /**
     * Fetch a response and immediately pick up the parsed response, from own content
     */
    function testGetParsedFromResponse() {
        $this->pemDefault();
        $container = $this->urlGet("ssl&bool&o=json&method=get");
        $ParsedResponse = $this->CURL->getParsedResponse($container);
        $this->assertTrue(is_object($ParsedResponse));
    }

    /**
     * Request a specific value from a parsed response
     */
    function testGetParsedValue() {
        $this->pemDefault();
        $this->urlGet("ssl&bool&o=json&method=get");
        $this->CURL->getParsedResponse();
        $ValueFrom = $this->CURL->getParsedValue('methods');
        $this->assertTrue(is_object($ValueFrom->_REQUEST));
    }

    /**
     * Request a nested value from a parsed response
     */
    function testGetParsedSubValue() {
        $this->pemDefault();
        $this->urlGet("ssl&bool&o=json&method=get");
        $ValueFrom = $this->CURL->getParsedValue(array('nesting', 'subarr4', 'child4'));
        $this->assertTrue(count($ValueFrom) === 3);
    }

    /**
     * Request a value by sending wrong value into the parser (crash test)
     */
    function testGetParsedSubValueNoArray() {
        $this->pemDefault();
        $this->urlGet("ssl&bool&o=json&method=get");
        $ValueFrom = $this->CURL->getParsedValue(new \stdClass());
        $this->assertTrue(empty($ValueFrom));
    }

    /**
     * Request a value that does not exist in a parsed response (Receive an exception)
     */
    function testGetParsedSubValueFail() {
        $this->pemDefault();
        $this->urlGet("ssl&bool&o=json&method=get");
        $ExpectFailure = false;
        try {
            $this->CURL->getParsedValue(array('nesting', 'subarrfail'));
        } catch (\Exception $parseException) {
            $ExpectFailure = true;
        }
        $this->assertTrue($ExpectFailure);
    }

    /**
     * Test if a web request has a valid body
     */
    function testValidBody() {
        $this->pemDefault();
        $container = $this->simpleGet();
        $this->assertTrue(!empty($this->getBody($container)));
    }

    /**
     * Receive a standard 200 code
     */
    function testSimple200() {
        $this->pemDefault();
        $simpleContent = $this->simpleGet();
        $this->assertTrue(is_array($simpleContent) && isset($simpleContent['code']) && $simpleContent['code'] == 200);
    }

    /**
     * Test SSL based web request
     */
    function testSslUrl() {
        $this->pemDefault();
        $container = $this->urlGet("ssl&bool", "https");
        $this->assertTrue($this->getBody($container) && !empty($this->getBody($container)));
    }

    /**
     * Test parsed json response
     */
    function testGetJson() {
        $this->pemDefault();
        $container = $this->urlGet("ssl&bool&o=json&method=get");
        $this->assertTrue(is_object($container['parsed']->methods->_GET));
    }

    /**
     * Check if we can parse a serialized response
     */
    function testGetSerialize() {
        $this->pemDefault();
        $container = $this->urlGet("ssl&bool&o=serialize&method=get");
        $this->assertTrue(is_array($container['parsed']['methods']['_GET']));
    }

    /**
     * Test if XML/Serializer are parsed correctly
     */
    function testGetXmlSerializer() {
        $this->pemDefault();
        // XML_Serializer
        $container = $this->getParsed($this->urlGet("ssl&bool&o=xml&method=get"));
        $this->assertTrue(is_object($container->using) && $container->using['0'] == "XML/Serializer");
    }

    /**
     * Test if SimpleXml are parsed correctly
     */
    function testGetSimpleXml() {
        $this->pemDefault();
        // SimpleXMLElement
        $container = $this->getParsed($this->urlGet("ssl&bool&o=xml&method=get&using=SimpleXMLElement"));
        $this->assertTrue(is_object($container->using) && $container->using == "SimpleXMLElement");
    }

    /**
     * Test if a html response are converted to a proper array
     */
    function testGetSimpleDom() {
        $this->pemDefault();
        $this->CURL->setParseHtml(true);
        $container = $this->getParsed($this->urlGet("ssl&bool&o=xml&method=get&using=SimpleXMLElement", null, "simple.html"));
        // ByNodes, ByClosestTag, ById
        $this->assertTrue(count($container['ById']) > 0);
    }


    /***************
     *  SSL TESTS  *
     **************/

    /**
     * Test: SSL Certificates at custom location
     * Expected Result: Successful lookup with verified peer
     */
    function testSslCertLocation() {
        $this->CURL->_DEBUG_TCURL_UNSET_PEM_LOCATION = true;
        $successfulVerification = false;
        try {
            $this->CURL->sslPemLocations = array(__DIR__ . "/ca-certificates.crt");
            $container = $this->getParsed($this->urlGet("ssl&bool&o=json", "https"));
            $successfulVerification = true;
        } catch (\Exception $e) {
        }
        $this->assertTrue($successfulVerification);
    }

    /**
     * Test: SSL Certificates at default location
     * Expected Result: Successful lookup with verified peer
     */
    function testSslDefaultCertLocation() {
        $this->pemDefault();

        $successfulVerification = false;
        try {
            $container = $this->getParsed($this->urlGet("ssl&bool&o=json", "https"));
            $successfulVerification = true;
        } catch (\Exception $e) {
        }
        $this->assertTrue($successfulVerification);
    }

    /**
     * Test: SSL Certificates are missing and certificate location is mismatching
     * Expected Result: Failing the url call
     */
    function testFailingSsl() {
        $this->CURL->_DEBUG_TCURL_UNSET_PEM_LOCATION = true;
        $successfulVerification = true;
        try {
            $this->CURL->sslPemLocations = array("NULL");
            $container = $this->getParsed($this->urlGet("ssl&bool&o=json", "https"));
        } catch (\Exception $e) {
            $successfulVerification = false;
        }
        $this->assertFalse($successfulVerification);
    }

    /**
     * Test: SSL Certificates are missing and peer verification is disabled
     * Expected Result: Successful lookup with unverified peer
     */
    function testUnverifiedSsl() {
        $this->CURL->_DEBUG_TCURL_UNSET_PEM_LOCATION = true;
        $successfulVerification = false;
        try {
            $this->CURL->setSslUnverified(true);
            $this->CURL->sslPemLocations = array("NULL");
            $container = $this->getParsed($this->urlGet("ssl&bool&o=json", "https"));
            $successfulVerification = true;
        } catch (\Exception $e) {
        }
        $this->assertTrue($successfulVerification);
    }
}
