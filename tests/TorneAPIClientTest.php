<?php

namespace TorneAPIClient;

use TorneLIB\API;
use TorneLIB\TorneAPI;

require_once('../libs/TorneAPI/autoload.php');

class TorneAPIClientTest extends \PHPUnit_Framework_TestCase
{
    private $Facebook;
    private $TorneAPI;

    function __construct()
    {
        $this->API = new TorneAPI();
    }

    public function testInit() {
        $this->assertTrue(is_object($this->API));
    }
    public function testInitFacebook() {
        $this->Facebook = $this->API->Intialize("LibFacebook", array('992647120850599', '21e8b18c0de8a4873cb08cd03b1e6720'));
        $this->assertTrue(is_object($this->Facebook));
        $this->assertTrue(in_array('LibFacebook',$this->API->getLoadedLibraries()));
    }
    public function testInitSimpleFacebook() {
        $this->Facebook = $this->API->Intialize("LibFacebook", array('992647120850599', '21e8b18c0de8a4873cb08cd03b1e6720'));
        $this->assertTrue($this->API->getLoadedLibraries('LibFacebook'));
    }
    public function testInitTornevall() {
        $this->TorneAPI = $this->API->Intialize("Tornevall");
    }
    public function testTornevallGet() {
        $this->TorneAPI = $this->API->Intialize("Tornevall");
        $this->assertTrue(!empty($this->TorneAPI->Get("test")->version));
    }
    public function testTornevallPost() {
        $this->TorneAPI = $this->API->Intialize("Tornevall");
        $this->assertGreaterThan(0, $this->TorneAPI->Post("test/returnRandom")->returnRandomResponse);
    }
    public function testDnsBl() {
        $this->TorneAPI = $this->API->Intialize("Tornevall");
        $this->TorneAPI->setServiceDestination(false);
        $PostData = array(
            "bulk"=>"255.255.255.255",
            "authKey" => "null",
            "application" => "null"
        );
        $this->assertContains("mockdata", $this->TorneAPI->Post("dnsbl/ip", $PostData)->ipResponse->{"255.255.255.255"}->blacklist->typestring);
    }
    public function testUrlTester() {
        $this->TorneAPI = $this->API->Intialize("Tornevall");
        $ConnectTo = base64_encode("https://api.tornevall.net/2.0");
        $this->assertTrue(is_object($this->TorneAPI->Post("urltest/isavailable", array("link"=> base64_encode("https://api.tornevall.net/2.0/")))->isAvailableResponse));
    }
}
