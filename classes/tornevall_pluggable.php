<?php

/**
 * TorneLIB plugin loader
 * @package TorneLIB-PHP
 */
namespace TorneLIB;

/**
 * Libraries and plugins that are internally handled by TorneLIB
 *
 * Class TorneLIB_Pluggable
 * @package TorneLIB
 */
class TorneLIB_Pluggable {

    /** @var Parent configuration object */
    protected $config;
    /** @var array Plugin list */
    protected $pluggableList = array();
    /** @var Parent */
    private $parent;

    private $PathList = array();
    protected $PluggableAutoPath = "";

    /**
     * TorneLIB_Pluggable constructor.
     * @param null $jsonConfig
     */
    function __construct($jsonConfig = null) {
        if (is_object($jsonConfig)) {
            $this->setConfig($jsonConfig);
        }
        $this->config = new TorneLIB_Configuration();
        $this->PluggableAutoPath = $this->config->getConfigurationObject()->config->PluggableAutoPath;
    }

    /**
     * Setup plugin config
     * @param array $jsonParsed
     */
    public function setConfig($jsonParsed = array()) {
        $this->config = $jsonParsed;
        $this->initPluggableList();
    }

    /**
     * Initialize plugin list
     */
    private function initPluggableList() {
        $existingPathList = array();
        if (is_object($this->config) && isset($this->config->config->pluggable) && is_object($this->config->config->pluggable)) {
            if (isset($this->config->config->PluggableAutoPath) && file_exists($this->config->config->PluggableAutoPath)) {
                $existingPathList[] = $this->config->config->PluggableAutoPath;
            }
            foreach ($this->config->config->pluggable as $pluggableName => $pluggablePaths) {
                if (is_array($pluggablePaths)) {
                    foreach ($pluggablePaths as $path) {
                        $RealPath = $path;
                        /* On relative path usage, we'll try to find our way back to the primary library */
                        if (preg_match("/^\./", $path)) { $RealPath = realpath(TORNELIB_PATH . "/" . $path . "/"); }
                        if (is_dir($RealPath)) {
                            $existingPathList[] = $RealPath;
                            break;
                        }
                    }
                }
                if (count($existingPathList)) {
                    $this->pluggableList[strtolower($pluggableName)] = $existingPathList;
                }
            }
        }
        $this->PathList = $existingPathList;
    }

    /**
     * Get the correct plugin path
     * @param string $pluggableName
     * @return mixed
     */
    public function getPluggablePath($pluggableName = '') {
        $pluggableLocalLibs = __DIR__ . "/../libs/";
        if (isset($this->pluggableList[$pluggableName]) && is_array($this->pluggableList[$pluggableName])) {
            return array_pop($this->pluggableList[$pluggableName]);
        }
    }

    /**
     * Initialize pluggable applications (plugins)
     * @param string $pluggableName
     * @return mixed
     */
    public function initPluggable($pluggableName = '') {
        $pluggableClassFile = __DIR__ . "/pluggable/tornevall_pluggable_".strtolower($pluggableName).".php";
        if (version_compare(phpversion(), '5.4.32', '>=') || version_compare(phpversion(), '5.6.16', '>=')) {
            $pluggableClassName = 'TorneLIB\TorneLIB_' . ucwords($pluggableName, "\t\r\n\v_");
        } else {
            $splitted = preg_split("/\t|\r|\n|\v|_/", $pluggableName);
            $pluggableName = implode(array_map("ucwords", $splitted));
            $pluggableClassName = 'TorneLIB\TorneLIB_' . ucwords($pluggableName);
        }

        if (($PluggablePathLoader = $this->getPluggablePath($pluggableName)) && file_exists($pluggableClassFile)) {
            require_once($pluggableClassFile);
            if (class_exists($pluggableClassName)) {
                $newPluggableLoader = new $pluggableClassName($PluggablePathLoader);
                return $newPluggableLoader;
            }
        } else {
            if (file_exists($pluggableClassFile)) {
                require_once($pluggableClassFile);
                $newPluggableLoader = new $pluggableClassName(); // Removed from (): $pluggableName
                return $newPluggableLoader;
            }
        }
    }

    protected function GetLibraryPath() {
        //print_R($this->PathList);
    }

}