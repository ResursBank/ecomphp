<?php

namespace TorneAPIClient;

require_once('../torneapi.php');

class TorneAPIClientTest extends \PHPUnit_Framework_TestCase
{
    function __construct()
    {
        $this->API = new TorneAPIClient();
    }

    public function testInit() {
        $this->assertTrue(is_object($this->API));
    }
}
