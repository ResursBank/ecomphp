<?php

namespace TorneLIB;

/**
 * Class Tornevall_API - API Class Connector
 * @package TorneLIB
 */
class TorneLIB_API extends TorneLIB_Pluggable {

    public $TorneAPI;
    private $CURL;

    function __construct()
    {
        parent::__construct();
        $this->GetLibraryPath("TorneAPI");
    }

    public function __call($name, $arguments)
    {

    }
    public function Load() {

    }
}
