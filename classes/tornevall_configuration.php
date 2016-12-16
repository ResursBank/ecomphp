<?php

namespace TorneLIB;

/**
 * Class TorneLIB_Configuration
 * @package TorneLIB
 */
class TorneLIB_Configuration {

    /**
     * @package TorneLIB
     * @subpackage TorneLIB-ConfigurationCore
     *
     * Contains configuration (defaults) internally instead of in /etc
     */

    const CORE_NAME = 'TorneLIB-PHP';

    /** @var bool If set to true, allow the usage of the sample configuration that follows the package */
    public $allowExperimental = false;

    /** @var string Path to best preferrably configruation */
    private $Config_Location = "/etc/tornevall_config.json";
    protected $_isExperimental = false;
    protected $_hasConfiguration = false;
    protected $Configuration = null;

    /**
     * TorneLIB_Configuration constructor.
     * @param string $Config_Location Path to config
     */
    public function __construct($Config_Location = '') {
        /*
         * The most default we get here, is the subdirectory under each library, so if you put up everything in the same path there should be no problems at all and that's
         * the first thing to check against.
         *
         */
        $jsonCfg = null;
        $hasConfigFile = false;
        if (file_exists($Config_Location)) {
            $this->Config_Location = $Config_Location;
            $hasConfigFile = true;
        }
        if (file_exists($this->Config_Location)) {
            $jsonCfg = trim(file_get_contents($this->Config_Location));
            $this->_hasConfiguration = true;
        }
        /**
         * As we still want some defaults, we'll try to failover on our local structure.
         * To remember: W should not corner this library, with external config files. It should be runnable without a configuration file and fail over to
         * major internal defaults whatsover.
         */
        if ($this->allowExperimental) {
            if (file_exists(__DIR__ . "/etc/tornevall_config.sample.json") && !$hasConfigFile) {
                $this->Config_Location = __DIR__ . "/etc/tornevall_config.sample.json";
                $this->_isExperimental = true;
                $jsonCfg = trim(file_get_contents($this->Config_Location));
            }
        }
        if (!isset($jsonCfg) || (empty($jsonCfg) || is_null($jsonCfg))) {
            // Running without configuration?
        } else {
            if ($this->_hasConfiguration) {
                $this->Configuration = json_decode($jsonCfg);
            }
        }
    }
    private $_CORE_NAME = '';

    /**
     * Returns true if there is an available configuration at this level
     * @return bool
     */
    public function hasConfiguration() {
        return $this->_hasConfiguration;
    }

    /**
     * Returns true if the loaded configuration comes from the sample file
     * @return bool
     */
    public function isExperimental() {
        return $this->_isExperimental;
    }

    /**
     * The the core name of this library
     * @return string
     */
    public function getCoreName() { return (!empty($this->_CORE_NAME) ? $this->_CORE_NAME : TorneLIB_Configuration::CORE_NAME); }

    /**
     * Set the core name of this library
     * @param string $newCoreName
     */
    public function setCoreName($newCoreName = '') { $this->_CORE_NAME = $newCoreName; }

    /**
     * Get the confiruation object from this class
     * @return mixed|null
     */
    public function getConfigurationObject() { return $this->Configuration; }
}
