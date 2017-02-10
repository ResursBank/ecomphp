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
        $this->Urls = array(
            'simple' => 'http://identifier.tornevall.net/',
            'tests' => 'developer.tornevall.net/tests/tornevall_network/index.php'
        );
    }

    private function simpleGet() {
        return $this->CURL->doGet($this->Urls['simple']);
    }
    private function urlGet($parameters = '', $protocol = "http") {
        $theUrl = $protocol . "://" . $this->Urls['tests'] . "?" . $parameters;
        return $this->CURL->doGet($theUrl);
    }
    private function urlPost($parameters = array(), $protocol = "http") {
        $theUrl = $protocol . "://" . $this->Urls['tests'];
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


    /**
     * Runs a simple test to see if there is a container as it should
     */
    function testSimpleGet() {
        $container = $this->simpleGet();
        $this->assertTrue($this->hasBody($container));
    }
    function testValidBody() {
        $container = $this->simpleGet();
        $this->assertTrue(!empty($this->getBody($container)));
    }
    function testSimple200() {
        $simpleContent = $this->simpleGet();
        $this->assertTrue(is_array($simpleContent) && isset($simpleContent['code']) && $simpleContent['code'] == 200);
    }
    function testSslUrl() {
        $container = $this->urlGet("ssl&bool", "https");
        $this->assertTrue($this->getBody($container) && !empty($this->getBody($container)));
    }

    function testGetJson() {
        $container = $this->urlGet("ssl&bool&o=json&method=get");
        $this->assertTrue(is_object($container['parsed']->methods->_GET));
    }
    function testGetSerialize() {
        $container = $this->urlGet("ssl&bool&o=serialize&method=get");
        $this->assertTrue(is_array($container['parsed']['methods']['_GET']));
    }
    function testGetXmlSerializer() {
        // XML_Serializer
        $container = $this->urlGet("ssl&bool&o=xml&method=get");
        $this->assertTrue(is_object($container['parsed']->using) && $container['parsed']->using['0'] == "XML/Serializer");
    }
    function testGetSimpleXml() {
        // SimpleXMLElement
        $container = $this->urlGet("ssl&bool&o=xml&method=get&using=SimpleXMLElement");
        $this->assertTrue(is_object($container['parsed']->using) && $container['parsed']->using == "SimpleXMLElement");
    }
}
