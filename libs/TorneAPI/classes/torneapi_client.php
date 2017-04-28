<?php

/**
 * Tornevall Networks pluggable API Client for TorneLIB 5.0.0
 * API Extender for TorneLIB, that works with TorneAPI 2.x and various libraries.
 * As this is a (quote) newborn project, there's nothing much added here.
 *
 * @package TorneAPI
 * @author Tomas Tornevall <thorne@tornevall.net>
 * @version 5.0.0
 * @copyright 2017-2019
 * @link http://docs.tornevall.net/x/FoBU A part of TorneLIB v4
 */

namespace TorneLIB;

use Facebook\Facebook;
use TorneLIB\API\CoreAPI;
use TorneLIB\API\LibFacebook;
use TorneLIB\API\LibVbulletin;
use TorneLIB\TorneLIB_Network;
use TorneLIB\Tornevall_cURL;

/**
 * Class API
 * @package TorneLIB
 */
class TorneAPI extends CoreAPI {

    private $curl = null;
    private $LibrariesLoaded = array();
    public function __construct() {
        parent::__construct();
    }
    /**
     * Initialize a supported library
     *
     * @param string $LibName
     * @param array $OptionsIn
     * @return object
     * @throws \Exception
     */
    public function Intialize($LibName = '', $OptionsIn = array()) {
        $setReflector= null;
        if (class_exists('\TorneLIB\API\\' . $LibName)) {
            $setReflector= '\TorneLIB\API\\' . $LibName;
        } else if (class_exists('\TorneLIB\API\\Lib' . $LibName)) {
            $setReflector = '\TorneLIB\API\\Lib' . $LibName;
        }
        if (empty($setReflector)) {
            throw new \Exception("API library $LibName is not supported");
        }
        try {
            $Reflect = new \ReflectionClass($setReflector);
            $Instance = $Reflect->newInstanceArgs($OptionsIn);
            $this->LibrariesLoaded[$LibName] = true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        return $Instance;
    }

    public function getLoadedLibraries($LibraryName= '') {
        if (!empty($LibraryName)) {
            if (isset($this->LibrariesLoaded[$LibraryName])) {
                return true;
            }
        }
        return $this->LibrariesLoaded;
    }
}
