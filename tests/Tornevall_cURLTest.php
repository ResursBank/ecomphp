<?php

namespace TorneLIB;

require_once("../classes/tornevall_network.php");


class Tornevall_cURLTest extends \PHPUnit_Framework_TestCase
{
    private $CURL;
    private $Urls;
    private $TorSetupAddress = "127.0.0.1:9050";
    private $TorSetupType = CURLPROXY_SOCKS4;

    function __construct()
    {
        $this->CURL = new \TorneLIB\Tornevall_cURL();
        $this->NET = new \TorneLIB\TorneLIB_Network();

        /*
         * Enable test mode
         */
        $this->CURL->setTestEnabled();

        /*
         * Set up testing URLS
         */
        $this->Urls = array(
            'simple' => 'http://identifier.tornevall.net/',
            'simplejson' => 'http://identifier.tornevall.net/?json',
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

    function testGetArpaLocalhost4() {
        $this->assertTrue($this->NET->getArpaFromIpv4("127.0.0.1") === "1.0.0.127");
    }
    function testGetArpaLocalhost6() {
        $this->assertTrue($this->NET->getArpaFromIpv6("::1") === "1.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0");
    }
    function testGetArpaLocalhost4Second() {
        $this->assertTrue($this->NET->getArpaFromIpv4("192.168.12.36") === "36.12.168.192");
    }
    function testGetArpaLocalhost6Second() {
        $this->assertTrue($this->NET->getArpaFromIpv6("2a01:299:a0:ff:10:128:255:2") === "2.0.0.0.5.5.2.0.8.2.1.0.0.1.0.0.f.f.0.0.0.a.0.0.9.9.2.0.1.0.a.2");
    }
    function testGetArpaLocalhost4Nulled() {
        $this->assertEmpty($this->NET->getArpaFromIpv4(null));
    }
    function testGetArpaLocalhost6Nulled() {
        $this->assertEmpty($this->NET->getArpaFromIpv6(null));
    }
    function testGetArpaLocalhost4String() {
        $this->assertEmpty($this->NET->getArpaFromIpv4("fail here"));
    }
    function testGetArpaLocalhost6String() {
        $this->assertEmpty($this->NET->getArpaFromIpv6("fail here"));
    }
    function testGetArpaLocalhost6CorruptString1() {
        $this->assertEmpty($this->NET->getArpaFromIpv6("a : b \\"));
    }
    function testGetArpaLocalhost6CorruptString2() {
        $badString = "";
        for ($i = 0 ; $i < 255 ; $i++) {
            $badString .= chr($i);
        }
        $this->assertEmpty($this->NET->getArpaFromIpv6($badString));
    }
    function testOctetV6() {
        $this->assertTrue($this->NET->getIpv6FromOctets("2.0.0.0.5.5.2.0.8.2.1.0.0.1.0.0.f.f.0.0.0.a.0.0.9.9.2.0.1.0.a.2") === "2a01:299:a0:ff:10:128:255:2");
    }
    function testGetArpaAuto4() {
        $this->assertTrue($this->NET->getArpaFromAddr("172.16.12.3") === "3.12.16.172");
    }
    function testGetArpaAuto6() {
        $this->assertTrue($this->NET->getArpaFromAddr("2a00:1450:400f:802::200e") === "e.0.0.2.0.0.0.0.0.0.0.0.0.0.0.0.2.0.8.0.f.0.0.4.0.5.4.1.0.0.a.2");
    }
    function testGetIpType4() {
        $this->assertTrue($this->NET->getArpaFromAddr("172.22.1.83", true) === 4);
    }
    function testGetIpType6() {
        $this->assertTrue($this->NET->getArpaFromAddr("2a03:2880:f113:83:face:b00c:0:25de", true) === 6);
    }
    function testGetIpTypeFail() {
        $this->assertTrue($this->NET->getArpaFromAddr("This.Aint.An.Address", true) === TorneLIB_Network_IP::IPTYPE_NONE);
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

    private function getIpListByIpRoute() {
        // Don't fetch 127.0.0.1
        exec("ip addr|grep \"inet \"|sed 's/\// /'|awk '{print $2}'|grep -v ^127", $returnedExecResponse);
        return $returnedExecResponse;
    }

    /**
     * Test the customized ip address
     */
    function testCustomIpAddrSimple() {
        $this->pemDefault();
        $returnedExecResponse = $this->getIpListByIpRoute();
        // Probably a bad shortcut for some systems, but it works for us in tests
        if (!empty($returnedExecResponse) && is_array($returnedExecResponse)) {
            $NETWORK = new TorneLIB_Network();
            $ipArray = array();
            foreach ($returnedExecResponse as $ip) {
                if ($NETWORK->getArpaFromAddr($ip, true) > 0) {
                    $ipArray[] = $ip;
                }
            }
            $this->CURL->IpAddr = $ipArray;
            $CurlJson = $this->CURL->doGet($this->Urls['simplejson']);
            $this->assertNotEmpty($CurlJson['parsed']->ip);
        }
    }

    /**
     * Test custom ip address setup (if more than one ip is set on the interface)
     */
    function testCustomIpAddrAllString() {
        $this->pemDefault();
        $ipArray = array();
        $responses = array();
        $returnedExecResponse = $this->getIpListByIpRoute();
        if (!empty($returnedExecResponse) && is_array($returnedExecResponse)) {
            $NETWORK = new TorneLIB_Network();
            foreach ($returnedExecResponse as $ip) {
                if ($NETWORK->getArpaFromAddr($ip, true) > 0) {
                    $ipArray[] = $ip;
                }
            }
            if (is_array($ipArray) && count($ipArray) > 1) {
                foreach ($ipArray as $ip) {
                    $this->CURL->IpAddr = $ip;
                    try {
                        $CurlJson = $this->CURL->doGet($this->Urls['simplejson']);
                    } catch (\Exception $e) {

                    }
                    if (isset($CurlJson['parsed']->ip) && $this->NET->getArpaFromAddr($CurlJson['parsed']->ip, true) > 0) {
                        $responses[$ip] = $CurlJson['parsed']->ip;
                    }
                }
            } else {
                $this->markTestIncomplete("ip address array is too short to be tested (".print_R($ipArray, true).")");
            }
        }
        $this->assertTrue(count($responses) === count($ipArray));
    }

    /**
     * Test proxy by using Tor Network (Requires Tor)
     * @link https://www.torproject.org/ Required application
     */
    function testTorNetwork() {
        $this->pemDefault();
        exec("service tor status", $ubuntuService);
        $serviceFound = false;
        foreach ($ubuntuService as $row) {
            // Unsafe control
            if (preg_match("/loaded: loaded/i", $row)) {
                $serviceFound = true;
            }
        }
        if (!$serviceFound) {
            $this->markTestIncomplete("Service not found in the current control");
        } else {
            $this->CURL->setProxy($this->TorSetupAddress, $this->TorSetupType);
            $CurlJson = $this->CURL->doGet($this->Urls['simplejson']);
            $parsedIp = $this->NET->getArpaFromAddr($CurlJson['parsed']->ip, true);
            $this->assertTrue($parsedIp > 0);
        }
    }
}
