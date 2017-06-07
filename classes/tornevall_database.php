<?php

/**
 * Tornevall Networks TorneLIB-PHP - Database utilizer
 *
 * @package TorneLIB-PHP
 * @author Tomas Tornevall <thorne@tornevall.net>
 * @version 4.0
 * @copyright 2015-2017
 * @link http://docs.tornevall.net/x/BAAQ TorneLIB
 * @link http://docs.tornevall.net/x/FoBU TorneLIB-PHP
 * @license -
 */

namespace TorneLIB;

/**
 * Class TorneLIB_Database
 * @package TorneLIB
 */

class TorneLIB_Database
{
    /**
     * @var Parent
     */
    private $parent;

    /** @var Database source */
    private $db = null;

    /** @var null Keeping PDO in memory as an own component. If this is set, we know there's a PDO driver running */
    private $PDO = null;

    /** @var string Current connection name */
    private $current = null;

    /** @var array Overriders sent to connectors, etc */
    private $overriders = array();

    /** @var bool Determine if our database connection is ready */
    private $dbServerConnection = false;
    /** @var bool Determine if our schema is ready */
    private $dbDataConnection = false;

    /** @var array Connection configuration */
    private $dbconfig = array();

    /** @var null Sets the proper mysql connection type (improved vs deprecated) */
    private $mysqlType = null;

    /** @var bool Is the current used mysql driver running in failover-mode? */
    private $mysqlFailover = false;

    /** @var array Options to pass through */
    private $connectorOptions = array();

    /** @var array|null Private connector setup */
    private $privateConnector = null;

    /** @var array Allowed options to pick up from config - the rest is discarded */
    private $dbOptionsAllow = array('user', 'password', 'type', 'server', 'db', 'exceptions', 'charset');

    /** @var bool Force all queries to run through a parser? (May be deprecated) */
    private $preventRawQueries = false;

    /** @var array Keep a list of available existing drivers to use */
    private $existingDrivers = array();

    /** @var null Keep track of the last insert id if available */
    public $last_insert_id = null;
    public $affected_rows = 0;

    /**
     * The real last_insert_id since the public one can be manipulated
     * @var null
     */
    private $real_last_insert_id = null;
    private $real_affected_rows = 0;

    /**
     * TorneLIB_Database constructor - Initialize the class (Overriders may not work properly if choosing to connect immediately on init)
     *
     * @param array $connectorOptions Any options that should be passed into this class (TODO)
     * @param null $connectorHost Hostname for a manually created connection
     * @param null $connectorUser Username for a manually created connection
     * @param null $connectorPassword Password for a manually created connection
     * @param string $connectorType Connection type for a manually created connection if anything else than mysql
     * @param int $connectorPort Port for a manually created connection
     * @param null $databaseName
     * @throws TorneLIB_Exception
     */
    public function __construct($connectorOptions = array(), $connectorHost = null, $connectorUser = null, $connectorPassword = null, $connectorType = 'mysql', $connectorPort = 3306, $databaseName = null)
    {
        $this->setConnectorOptions($connectorOptions);

        /* Skip the configuration and set up a connector directly on init */
        if (!empty($connectorHost)) {
            $this->privateConnector = array(
                'user' => $connectorUser,
                'server' => $connectorHost,
                'password' => $connectorPassword,
                'type' => $connectorType,
                'port' => (isset($connectorPort) || !empty($connectorPort)) ? $connectorPort : 3306,
                'db' => $databaseName
            );
        }

        $this->ConfigInit();
        if ($this->hasPrivateConnector()) {
            $connectionResult = $this->Connect($this->getPrivateConnectorName());
            $dbCredentials = $this->getServerCredentials("override", "db");
            if ($connectionResult && $this->hasPrivateConnector() && !empty($dbCredentials)) {
                $this->db($this->getServerCredentials("override", "db"));
            }
            if (!$connectionResult && $this->canThrow($this->current)) {
                throw new TorneLIB_Exception("Database initialization failure. Can not connect to $connectorHost", TORNELIB_EXCEPTIONS::TORNELIB_DB_INITIALIZATION_ERROR, __FUNCTION__);
            }
        }
    }

    /**
     * Set up connector options on fly
     * @param array $connectorOptions
     */
    public function setConnectorOptions($connectorOptions = array())
    {
        if (is_array($connectorOptions) && count($connectorOptions)) {
            foreach ($connectorOptions as $connectorOption => $connectorValue) {
                $this->connectorOptions[$connectorOption] = $connectorValue;
            }
        }
    }


    /**
     * Initialize configuration and stuff that is needed to find a connection if not defined from the constructor
     * TODO: Call configuration from Core if possible
     *
     * @param bool $overrideConfig
     */
    private function ConfigInit($overrideConfig = false)
    {
        /* Read only once */
        if (!$overrideConfig && count($this->dbconfig) && isset($this->dbconfig->database) && isset($this->dbconfig->database->config)) {
            return;
        }

        /**
         * If /etc/tornevall_config exists, this file will get the highest priority.
         */
        $etcFile = '/etc/tornevall_config';
        if (defined('TE_DATABASES')) {
            $etcFile = TE_DATABASES;
        }
        $jsonCfg = '';
        if (file_exists($etcFile . ".json") && !file_exists($etcFile)) {
            $etcFile .= ".json";
        }
        if (file_exists($etcFile)) {
            /* JSON-style config */
            $jsonCfg = trim(file_get_contents($etcFile));
            if (!empty($jsonCfg)) {
                $dbAssoc = json_decode($jsonCfg, true);
                if (isset($dbAssoc['database']['servers']['override'])) {
                    unset($dbAssoc['database']['servers']['override']);
                }
            }
        }
        if (!empty($this->privateConnector) && is_array($this->privateConnector) && count($this->privateConnector) >= 5) {
            /* Always make overriders throw exceptions if user has not disabled it */
            if (!isset($this->privateConnector['exceptions'])) {
                $this->privateConnector['exceptions'] = true;
            }
            $dbAssoc['database']['servers']['override'] = $this->privateConnector;
            $jsonCfg = json_encode($dbAssoc);
        }
        if (!empty($jsonCfg)) {
            $etcConfig = json_decode($jsonCfg);
            if (isset($etcConfig)) {
                if (is_object($etcConfig)) {
                    $this->dbconfig = $etcConfig;
                }
            }
        }
    }

    /**
     * Find out if there is a connector initated which is not defined in the configuration
     *
     * @return bool
     */
    private function hasPrivateConnector()
    {
        $this->ConfigInit();
        $privateConnectorName = $this->getPrivateConnectorName();
        if (!empty($privateConnectorName) && is_array($this->privateConnector) && count($this->privateConnector) >= 5) {
            try {
                $credentials = $this->getServerCredentials('override', 'server');
                if (!empty($credentials)) {
                    return true;
                } else {
                    return false;
                }
            } catch (TorneLIB_Exception $e) {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Get the server name of the connection initiated in the constructor, if any.
     *
     * This function always returns the string "override" if a connection has been initiated at the constructor
     *
     * @return string
     */
    private function getPrivateConnectorName()
    {
        $serverName = null;
        if (!empty($this->privateConnector) && is_array($this->privateConnector)) {
            $serverName = "override";
        }
        return $serverName;
    }

    /**
     * Get the name of the current connection
     *
     * @return string
     */
    public function getCurrentConnection()
    {
        return $this->current;
    }

    /**
     * Connect to a database. If connectordata is set to anything but null (it's enough with the host actually), we will try to set that connector as first priority
     *
     * @param string $serverName
     * @param array $connectorOptions
     * @param null $connectorHost
     * @param null $connectorUser
     * @param null $connectorPassword
     * @param string $connectorType
     * @param int $connectorPort
     * @return bool
     * @throws TorneLIB_Exception
     */
    public function Connect($serverName = '', $connectorOptions = array(), $connectorHost = null, $connectorUser = null, $connectorPassword = null, $connectorType = 'mysql', $connectorPort = 3306, $databaseName = '')
    {
        $connectionResult = false;
        if (!empty($connectorHost)) {
            $this->privateConnector = array(
                'user' => $connectorUser,
                'server' => $connectorHost,
                'password' => $connectorPassword,
                'type' => $connectorType,
                'port' => (isset($connectorPort) || !empty($connectorPort)) ? $connectorPort : 3306,
                'db' => $databaseName
            );
            $this->setConnectorOptions($connectorOptions);
            $this->ConfigInit(true);        // Finding private connectors requires rereading of configuration
        }

        if (empty($serverName)) {
            $serverName = "localhost";
        }
        if ($this->hasPrivateConnector()) {
            $serverName = $this->getPrivateConnectorName();
        }

        $credentials = $this->getServerCredentials($serverName, "server");
        if (!empty($credentials)) {
            $this->current = $serverName;
            $dbType = $this->getDbType($serverName);
            if ($dbType == TORNEVALL_DATABASE_TYPES::MYSQL) {
                $connectionResult = $this->Connect_MySQL($serverName, $connectorOptions);
            } elseif ($dbType == TORNEVALL_DATABASE_TYPES::PDO) {
                $connectionResult = $this->Connect_PDO($serverName, $connectorOptions);
            }
        } else {
            throw new TorneLIB_Exception($serverName . " does not have a host defined", TORNELIB_EXCEPTIONS::TORNELIB_DB_NO_HOST, __FUNCTION__);
        }
        $dbCredentials = $this->getServerCredentials("override", "db");
        if ($connectionResult && $this->hasPrivateConnector() && !empty($dbCredentials)) {
            if ($this->db($this->getServerCredentials("override", "db"))) {
                $this->dbDataConnection = true;
            }
        }
        $this->hasServerConnection = $connectionResult;
        return $connectionResult;
    }

    /**
     * Connect to a chosen database/schema
     *
     * @param string $DatabaseName
     * @return null
     * @throws TorneLIB_Exception
     */
    public function db($DatabaseName = '')
    {
        $choiceResult = false;
        if (empty($DatabaseName)) {
            return null;
        }

        if (empty($this->current)) {
            throw new TorneLIB_Exception("You need a connection before selecting database", TORNELIB_EXCEPTIONS::TORNELIB_DB_NO_CONNECTION, __FUNCTION__);
        }
        $dbType = $this->getDbType($this->current);
        if ($dbType == TORNEVALL_DATABASE_TYPES::MYSQL) {
            if ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_IMPROVED) {
                $choiceResult = mysqli_select_db($this->db, $DatabaseName);
            } elseif ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_DEPRECATED) {
                mysql_select_db($DatabaseName, $this->db);
            } elseif ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_PDO) {
                /* Try to select a db if the method exists, otherwise skip this part */
                if (method_exists($this->db, "select_db")) {
                    $choiceResult = $this->db->select_db($DatabaseName);
                }
            }
        } elseif ($dbType == TORNEVALL_DATABASE_TYPES::PGSQL) {

        } elseif ($dbType == TORNEVALL_DATABASE_TYPES::SQLITE3) {

        }
        $this->dbDataConnection = $choiceResult;
        return $choiceResult;
    }


    /**
     * Fetch configuration from configarray (located in the config-file)
     * @param string $connectName
     */
    private function getConfig($connectName = 'localhost')
    {
        if (!isset($this->tdbconfig[$connectName])) {
            return;
        }

        /** Pick upp configuration and make it useful */
        if ($this->getv3Compatibility()) {
            /* TODO: Convert this part to the new format */
            if (is_array($this->tdbconfig) && count($this->tdbconfig)) {
                foreach ($this->tdbconfig as $tdbParameter => $tdbValue) {
                    if (in_array($tdbParameter, $this->dbOptionsAllow)) {
                        $this->dbconfig[$tdbParameter] = $tdbValue;
                    }
                }
                /** Reverse compatibility */
                if (isset($tdbconfig['noexception'])) {
                    $this->dbconfig['exceptions'] = ($tdbconfig['noexception'] === true ? false : true);
                }
            }
        }
    }

    /**
     * Get the id of a database connection type
     * @param string $serverType
     * @return int
     */
    private function getDbTypeId($serverType = 'mysql')
    {
        $serverType = strtolower($serverType);
        if ($serverType == "mysql") {
            return TORNEVALL_DATABASE_TYPES::MYSQL;
        }
        if ($serverType == "pgsql") {
            return TORNEVALL_DATABASE_TYPES::PGSQL;
        }
        if ($serverType == "pdo") {
            return TORNEVALL_DATABASE_TYPES::PDO;
        }
        return TORNEVALL_DATABASE_TYPES::NONE;
    }

    /**
     * Get connector type, returns mysql as default if nothing else is set.
     *
     * If connector type is mysql, this method determines what kind of mysql-connection we're trying to use.
     *
     * @param string $serverName
     * @return int|null (Default TORNEVALL_DATABASE_TYPES::MYSQL)
     * @throws TorneLIB_Exception
     */
    private function getDbType($serverName = '', $returnAsString = false)
    {
        $returnType = null;
        $returnString = null;
        try {
            $returnType = $this->getDbTypeId($this->getServerCredentials($serverName, "type"));
        } catch (\Exception $e) {
        }
        if (!$returnType) {
            $returnType = TORNEVALL_DATABASE_TYPES::MYSQL;
            $returnString = 'mysql';
        }

        if ($returnType == TORNEVALL_DATABASE_TYPES::MYSQL) {
            if ($this->getMysqlDriverPriority($serverName)) {
                if (!$returnAsString) {
                    return $returnType;
                } else {
                    return $returnString;
                }
            }
            if (function_exists('mysqli_connect')) {
                /**
                 * Connector overrider
                 */
                if (function_exists('mysql_connect') && $this->getServerCredentials($serverName, 'mysql_deprecated')) {
                    $this->mysqlType = TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_DEPRECATED;
                } elseif (function_exists('mysql_connect') && isset($this->overriders['mysql_deprecated']) && $this->overriders['mysql_deprecated'] === true) {
                    $this->mysqlType = TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_DEPRECATED;
                } else {
                    $this->mysqlType = TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_IMPROVED;
                }
            } elseif (function_exists('mysql_connect')) {
                $this->mysqlType = TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_DEPRECATED;
            } elseif (class_exists('PDO')) {
                $this->mysqlType = TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_PDO;
                $returnType = TORNEVALL_DATABASE_TYPES::PDO;
                $returnString = "mysql";
            } else {
                throw new TorneLIB_Exception("No MySQL drivers found", TORNELIB_EXCEPTIONS::TORNELIB_DB_NO_DRIVER_MYSQL, __FUNCTION__);
            }
        }

        if (!$returnAsString) {
            return $returnType;
        } else {
            return $returnString;
        }
    }

    /**
     * MySQL driver priority configurator.
     *
     * @param string $serverName
     * @return bool
     * @throws TorneLIB_Exception
     */
    private function getMysqlDriverPriority($serverName = '')
    {
        $this->existingDrivers = $this->getCurrentDriverSet($serverName);
        $prioList = $this->getOverride('mysqldriverpriority', $serverName);
        $pickExistingDriver = array_reverse($this->existingDrivers);
        if (!count($pickExistingDriver)) {
            throw new TorneLIB_Exception("There are no MySQL drivers available", TORNELIB_EXCEPTIONS::TORNELIB_DB_NO_DRIVER_MYSQL);
        }

        $nextAvailable = array_pop($pickExistingDriver);
        $hasPriorityDriver = false;

        if (is_array($prioList) && count($prioList)) {
            foreach ($prioList as $priorityDriver) {
                if (in_array($priorityDriver, $this->existingDrivers)) {
                    $hasPriorityDriver = true;
                    break;
                }
            }
            if (!$hasPriorityDriver) {
                /* Mark the driver as failover if prioritydriver is not the same as next available */
                if ($priorityDriver !== $nextAvailable) {
                    $this->mysqlFailover = true;
                }
                $priorityDriver = $nextAvailable;
            }

            if ($priorityDriver == "mysqli" && in_array("mysqli", $this->existingDrivers)) {
                $this->mysqlType = TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_IMPROVED;
            } elseif ($priorityDriver == "deprecated" && in_array("deprecated", $this->existingDrivers)) {
                $this->mysqlType = TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_DEPRECATED;
            } elseif ($priorityDriver == "pdo" && in_array("pdo", $this->existingDrivers)) {
                $this->mysqlType = TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_PDO;
            } else {
                $this->mysqlType = TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_NONE;
            }
            if ($this->mysqlType !== TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_NONE) {
                return true;
            }
        }
        return false;
    }

    public function getCurrentDriverSet($serverName = '')
    {
        $this->existingDrivers = array();
        if (function_exists('mysqli_connect')) {
            $this->existingDrivers[] = "mysqli";
        }
        if (function_exists('mysql_connect')) {
            $this->existingDrivers[] = "deprecated";
        }
        if (class_exists('PDO')) {
            $this->existingDrivers[] = 'pdo';
        }
        return $this->existingDrivers;
    }

    /**
     * Find out if we are running in mysql-failover-mode (which occurs when the prioritized mysql driver can not be found)
     * @return bool
     */
    public function isMySqlFailover()
    {
        return $this->mysqlFailover;
    }

    /**
     * Call this function to override connector defaults or connector configuration
     *
     * @param string $variable
     * @param string $value
     * @throws TorneLIB_Exception
     */
    public function setOverride($variable = '', $value = '')
    {
        $blockers = $this->getServerCredentials(null, 'blockoverride');
        if (is_array($blockers) && in_array($variable, $blockers)) {
            throw new TorneLIB_Exception("Database overriders not allowed", TORNELIB_EXCEPTIONS::TORNELIB_DB_OVERRIDE_DENIED, __FUNCTION__);
        }
        if (!empty($variable)) {
            $this->overriders[$variable] = $value;
            // isset($this->dbconfig->database->config->$variable)
            if (isset($this->dbconfig->database->config)) {
                $this->dbconfig->database->config->$variable = $value;
            }
        }
    }

    /**
     * Get override variables
     * @param string $variable
     * @param string $serverName
     * @return null
     * @throws TorneLIB_Exception
     */
    private function getOverride($variable = '', $serverName = '')
    {
        return $this->getServerCredentials($serverName, $variable);
    }

    /**
     * MySQL connection handled
     * @param string $serverName
     * @return bool
     * @throws TorneLIB_Exception
     */
    private function Connect_MySQL($serverName = '', $connectorOptions = array())
    {
        if (strtolower($this->getServerCredentials($serverName, 'driver')) === "pdo" && $this->hasPDO('mysql')) {
            $this->mysqlType = TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_PDO;
        }
        /** Find out what connector to use */
        if ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_IMPROVED) {
            /**
             * Use mysqli as primary connector if exists
             * Old function (mysql_connect) is deprecated as of PHP 5.5.0 - http://se1.php.net/mysql_connect - so if this one exists, then go for it.
             */
            $this->db = @mysqli_connect($this->getServerCredentials($serverName, "server"), $this->getServerCredentials($serverName, "user"), $this->getServerCredentials($serverName, "password"), $this->getServerCredentials($serverName, "database"), $this->getServerCredentials($serverName, "port"));
            if (is_object($this->db)) {
                if (($setTimeout = $this->getServerCredentials($serverName, "timeout")) > 0) {
                    @mysqli_options($this->db, MYSQLI_OPT_CONNECT_TIMEOUT, $setTimeout);
                }
                if (mysqli_errno($this->db)) {
                    throw new TorneLIB_Exception("PHPException: " . mysqli_error($this->db), mysqli_errno($this->db), __FUNCTION__);
                }
                $this->dbServerConnection = true;
                return true;
            } else {
                if ($this->canThrow($serverName)) {
                    if (mysqli_connect_errno()) {
                        $ErrorMessage = mysqli_connect_error();
                        $ErrorCode = mysqli_connect_errno();
                        if ($ErrorCode == 1045 && !$this->getServerCredentials($serverName, "authdetails")) {
                            $ErrorMessage = 'Access denied (Username not exposed, use authdetails to enable this)';
                        }
                        throw new TorneLIB_Exception("PHPException: Connection error [Servername $serverName]: " . $ErrorMessage, $ErrorCode, __FUNCTION__);
                    } else {
                        throw new TorneLIB_Exception("Connection error [Servername $serverName]: Exception not known", TORNELIB_EXCEPTIONS::TORNELIB_DB_GENERAL, __FUNCTION__);
                    }
                }
            }
        } else if ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_DEPRECATED) {
            /* Fall back to the old connector */
            $this->db = @mysql_connect($this->getServerCredentials($serverName, "server"), $this->getServerCredentials($serverName, "user"), $this->getServerCredentials($serverName, "password"), $this->getServerCredentials($serverName, "forcenew"));
            if (is_object($this->db) || is_resource($this->db)) {
                $this->dbServerConnection = true;
                return true;
            } else {
                if ($this->canThrow($serverName)) {
                    /* If any message */
                    if (mysql_error()) {
                        $ErrorMessage = mysql_error();
                        $ErrorCode = mysql_errno();
                        throw new TorneLIB_Exception("Connection error [$serverName]: " . $ErrorMessage, TORNELIB_EXCEPTIONS::TORNELIB_DB_CONNECT_MYSQL_FAIL, __FUNCTION__);
                    }

                    throw new TorneLIB_Exception("Connection error [$serverName]: Exception not known", TORNELIB_EXCEPTIONS::TORNELIB_DB_CONNECT_MYSQL_FAIL, __FUNCTION__);
                }
            }
        } elseif ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_PDO) {
            if ($this->Connect_PDO($serverName, $connectorOptions)) {
                $this->dbServerConnection = true;
                return true;
            } else {
                return false;
            }
        } else {
            /** Confirm the failure */
            return false;
        }
    }

    private function Connect_PGSQL()
    {

    }

    private function Connect_sqlite()
    {

    }

    /**
     * Find out if there is a PDO driver available
     * @param string $pdoDriver
     * @return bool
     */
    private function hasPDO($pdoDriver = '')
    {
        if (class_exists('PDO')) {
            $pdoDrivers = \PDO::getAvailableDrivers();
            if (is_array($pdoDrivers)) {
                /* Always return false if the driver list is empty. It's quite natural to do that, since that indicates on unavailable drivers... */
                if (!count($pdoDrivers)) {
                    return false;
                }
            }
            if (empty($pdoDriver)) {
                return true;
            } else {
                if (in_array($pdoDriver, $pdoDrivers)) {
                    return true;
                } else {
                    return false;
                }
            }
        }
    }

    private function Connect_PDO($serverName = '', $connectorOptions = array())
    {
        if (!$this->hasPDO()) {
            throw new TorneLIB_Exception("There are no PDO drivers available", TORNELIB_EXCEPTIONS::TORNELIB_DB_NO_DRIVER_PDO, __FUNCTION__);
        }
        if (class_exists('PDO')) {
            $pdoOptions = $connectorOptions;
            $dsnSet = $this->getDbType($serverName, true) . ':dbname=' . $this->getServerCredentials($serverName, "db") . ';host=' . $this->getServerCredentials($serverName, "server");
            try {
                $this->PDO = new \PDO($dsnSet, $this->getServerCredentials($serverName, "user"), $this->getServerCredentials($serverName, "password"), $pdoOptions);
                $this->db = $this->PDO;
            } catch (\Exception $PDOException) {
                if ($PDOException->getMessage()) {
                    $ErrorMessage = $PDOException->getMessage();
                    $ErrorCode = $PDOException->getCode();
                    /*
                     * If this is a basic installation of PHP and no drivers are installed, you may be quite sure to get to this point with this exception or similar:
                     * TorneLIB-PHP Connect_PDOException 0: PHPException/PDO: could not find driver
                     */
                    if ($this->canThrow($serverName)) {
                        throw new TorneLIB_Exception("PHPException/PDO: " . $ErrorMessage, $ErrorCode, __FUNCTION__);
                    }
                }
            }
            if (is_object($this->PDO)) {
                if ($this->canThrow($serverName)) {
                    $this->PDO->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                }
                $ErrorCode = $this->PDO->errorCode();
                $ErrorMessage = $this->PDO->errorInfo();
            } else {
                if ($this->canThrow($serverName)) {
                    throw new TorneLIB_Exception("Connection error [$serverName]: Exception not known", TORNELIB_EXCEPTIONS::TORNELIB_DB_CONNECT_PDO_FAIL, __FUNCTION__);
                } else {
                    return false;
                }
            }
            if ($ErrorCode > 0) {
                if ($this->canThrow($serverName)) {
                    throw new TorneLIB_Exception("PHPEXception/PDO: Connection error [$serverName]: " . $ErrorMessage, $ErrorCode, __FUNCTION__);
                }
                return false;
            }
            return true;
        } else {
            throw new TorneLIB_Exception("PDO Driver does not exist", TORNELIB_EXCEPTIONS::TORNELIB_DB_NO_DRIVER_PDO, __FUNCTION__);
        }
        return false;
    }


    /**
     * Figure out if the client should handle exceptions or silently pass through errors.
     *
     * @param string $serverName
     * @return bool
     * @throws TorneLIB_Exception
     */
    private function canThrow($serverName = '')
    {
        $returnValue = false;
        /* Get the global value */
        if (isset($this->dbconfig->database->config->exceptions)) {
            $returnValue = $this->dbconfig->database->config->exceptions;
        }
        /* Get the local value */
        if (!empty($serverName)) {
            $returnValue = $this->getServerCredentials($serverName, "exceptions");
        } elseif (!empty($this->current)) {
            $returnValue = $this->getServerCredentials($this->current, "exceptions");
        } elseif ($serverName == "override") {
            return true;
        }
        return $returnValue;
    }

    /**
     * Get credentials from configuration
     *
     * This function also handles global overriders.
     *
     * @param string $serverName
     * @param string $return
     * @return null
     * @throws TorneLIB_Exception
     */
    private function getServerCredentials($serverName = 'localhost', $return = '')
    {
        $this->ConfigInit();

        /* Look for a global overrider */
        if (isset($this->overriders[$return])) {
            return $this->overriders[$return];
        }
        if (empty($serverName) && $this->current) {
            $serverName = $this->current;
        }
        if (!is_null($serverName)) {
            if (!empty($serverName)) {
                if (isset($this->dbconfig) && is_object($this->dbconfig)) {
                    if (!empty($return)) {
                        if (!is_null($serverName)) {
                            if (isset($this->dbconfig->database->servers->$serverName)) {
                                if (isset($this->dbconfig->database->servers->$serverName->$return)) {
                                    return $this->dbconfig->database->servers->$serverName->$return;
                                } else {
                                    if (isset($this->dbconfig->database->config->$return)) {
                                        return $this->dbconfig->database->config->$return;
                                    }
                                    if ($return == "server" || $return == "user") {
                                        throw new TorneLIB_Exception(ucfirst($return) . " for $serverName not set", TORNELIB_EXCEPTIONS::TORNELIB_DB_CREDENTIALS_PARAMETER_NOT_SET, __FUNCTION__);
                                    }
                                    return null;
                                }
                            } else {
                                throw new TorneLIB_Exception("Database server name '$serverName' has not been set up properly", TORNELIB_EXCEPTIONS::TORNELIB_DB_CREDENTIALS_GENERAL, __FUNCTION__);
                            }
                        }
                    }
                }
            } else {
                throw new TorneLIB_Exception("serverName is not set", TORNELIB_EXCEPTIONS::TORNELIB_DB_CREDENTIALS_PARAMETER_NOT_SET, __FUNCTION__);
            }
        }
        if (isset($this->dbconfig->database->config->$return)) {
            return $this->dbconfig->database->config->$return;
        } else {
            return null;
        }
    }

    /**
     * Database column parser
     *
     * @param array $parameterArray
     * @throws \Exception
     */
    private function parseParameters($parameterArray = array())
    {
        if (is_array($parameterArray)) {
            foreach ($parameterArray as $parameter => $value) {
                $parameterName = '`' . $parameter . '`';
                $parameterValue = "''" . $this->injection($value) . "''";
                $updateValue = "`" . $this->injection($parameter) . "` = '" . $this->injection($value) . "'";
            }
        }
    }

    /**
     * Compatibility mode for magic_quotes - DEPRECATED as of PHP 5.3.0 and REMOVED as of PHP 5.4.0
     *
     * This method will be passed only if necessary
     *
     * @link http://php.net/manual/en/security.magicquotes.php Security Magic Quotes
     * @param null $injectionString
     * @return null|string
     */
    private function escape_deprecated($injectionString = null)
    {
        if (version_compare(phpversion(), '5.3.0', '<=')) {
            if (function_exists('get_magic_quotes_gpc')) {
                if (get_magic_quotes_gpc()) {
                    $injectionString = stripslashes($injectionString);
                }
            }
        }
        return $injectionString;
    }

    /**
     * SQL Injection parser
     * @param null $injectionString
     * @return mixed|string
     * @throws TorneLIB_Exception
     */
    public function injection($injectionString = null)
    {
        $isPDO = false;
        try {
            if ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_IMPROVED) {
                $returnString = @mysqli_real_escape_string($this->db, $this->escape_deprecated($injectionString));
            } else if ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_DEPRECATED) {
                $returnString = @mysql_real_escape_string($this->db, $this->escape_deprecated($injectionString));
            } else if ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_PDO) {
                $isPDO = true;
            }

            if ($isPDO) {
                /* The weakest form of protection */
                $returnString = $this->db->quote($injectionString);
                $returnString = substr(substr($returnString, 0, strlen($returnString) - 1), 1);
                // TODO: Fix this
                throw new TorneLIBException("Stupid developer has not finished function", TORNELIB_EXCEPTIONS::TORNELIB_GENERAL, __FUNCTION__);
                //echo $returnString;
                //exit;
            }

            if ($this->getDbType() == "pgsql") {
                $returnString = pg_escape_literal($this->escape_deprecated($injectionString));
            }
            /* This may be the dumbest thing ever except for the PDO-quotation above */
            if ($this->getDbType() == "mssql") {
                $returnString = preg_replace("[']", "''", $this->escape_deprecated($injectionString));
            }
        } catch (\Exception $injectError) {
            throw new TorneLIB_Exception("PHPException: " . $injectError->getMessage(), $injectError->getCode(), __FUNCTION__);
        }
        return $returnString;
    }

    /**
     * Configure module to not accept direct calls to the Query()-function (Set this to false, only if you are sure on what you're doing)
     * @param bool|true $preventEnabled
     */
    public function setPreventRaw($preventEnabled = true)
    {
        $this->preventRawQueries = $preventEnabled;
    }


    /**
     * The real query function, in case raw queries should be blocked from clients
     * @param null $queryString
     * @param bool|true $ColumnArray Currently only affects PDO-queries, where returning data is made as objects or arrays
     * @return bool|null|resource
     * @throws TorneLIB_Exception
     */
    private function Query_Raw($queryString = null, $ColumnArray = true)
    {
        if (is_null($this->db)) {
            throw new TorneLIB_Exception("No database connection has been established", TORNELIB_EXCEPTIONS::TORNELIB_DB_QUERY_NO_CONNECTION, __FUNCTION__);
        }

        $QueryResponse = null;
        if ($this->preventRawQueries) {
            if ($this->canThrow($this->current)) {
                throw new TorneLIB_Exception("Permission to raw database queries are limited", TORNELIB_EXCEPTIONS::TORNELIB_DB_QUERY_RAW_DENIED, __FUNCTION__);
            }
            return null;
        }

        /*
         * The query may start here, if anything above passed the test. 
         */

        $ErrorMessage = null;
        $ErrorCode = 0;
        $dbType = $this->getDbType($this->current);

        /*
         * TODO: Check priority and failover to another available method if possible
         */

        try {
            // we would want to catch all errors here so we can rethrow them after analyze, eventually

            $isPdoQuery = false;

            if ($dbType == TORNEVALL_DATABASE_TYPES::MYSQL) {
                if ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_IMPROVED) {
                    //$QueryResponse = mysqli_multi_query($this->db, $queryString);
                    $QueryResponse = mysqli_query($this->db, $queryString);
                    if (mysqli_errno($this->db) && $this->canThrow()) {
                        $errNo = mysqli_errno($this->db);
                        $errMsg = mysqli_error($this->db);
                        throw new TorneLIB_Exception("PHPException/MySQLi: " . $errMsg, $errNo, __FUNCTION__);
                    }
                    $this->setLastInsertId(mysqli_insert_id($this->db));
                } elseif ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_DEPRECATED) {
                    $QueryResponse = mysql_query($queryString, $this->db);
                    $this->setLastInsertId(mysql_insert_id($this->db));
                } elseif ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_PDO) {
                    $isPdoQuery = true;
                }
            }

            /* All queries received in PDO-mode passes here instead in separate calls*/
            if ($isPdoQuery) {
                if (method_exists($this->db, "query")) {
                    if ($ColumnArray) {
                        $QueryResponse = $this->db->query($queryString, \PDO::FETCH_ASSOC);
                    } else {
                        $QueryResponse = $this->db->query($queryString, \PDO::FETCH_OBJ);
                    }
                    $this->setLastInsertId($this->db->lastInsertId());
                } else {
                    if ($this->canThrow($this->current)) {
                        throw new TorneLIB_Exception("Database driver PDO has no query method", TORNELIB_EXCEPTIONS::TORNELIB_DB_QUERY_PDO_METHOD_FAIL, __FUNCTION__);
                    }
                }
            }

        } catch (\Exception $queryException) {
            if ($queryException->getMessage()) {
                $ErrorMessage = $queryException->getMessage();
                $ErrorCode = $queryException->getCode();
            }
        }
        if ($this->canThrow($this->current) && null !== $ErrorMessage) {
            throw new TorneLIB_Exception($ErrorMessage . " ($ErrorCode)", TORNELIB_EXCEPTIONS::TORNELIB_DB_QUERY_GENERAL, __FUNCTION__);
        }
        return $QueryResponse;
    }

    /**
     * The the last insert_id from database driver
     *
     * @return null
     */
    public function getLastInsertId()
    {
        return (!empty($this->real_last_insert_id) ? $this->real_last_insert_id : 0);
    }

    /**
     * Sets last insert id from database driver if exists
     * @param int $insertID
     */
    private function setLastInsertId($insertID = 0)
    {
        $this->last_insert_id = $insertID;
        $this->real_last_insert_id = $insertID;
    }

    /**
     * TODO: Fix affected rows issues
     * @param int $affectedRows
     */
    private function setAffectedRows($affectedRows = 0)
    {
        $this->affected_rows = $affectedRows;
    }

    /**
     * Send query to database driver
     *
     * The drivers differs a bit in their behaviour. For example, a PDO driver does not return true or false on an insert, it returns the query string sent from the client. We are not touching the returned query so be aware of this when using failover- or another priority order on the drivers.
     *
     * @param null $queryString
     * @return bool
     * @throws TorneLIB_Exception
     */
    public function Query($queryString = null, $ColumnArray = true)
    {
        $Resource = $this->Query_Raw($queryString, $ColumnArray);
        return $Resource;
    }

    /**
     * Get last function called in this stream
     * @return mixed
     */
    private function GetLastFunction()
    {
        if (function_exists('debug_backtrace')) {
            $backTrace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 3);
            try {
                if (isset($backTrace[2]['function'])) {
                    return $backTrace[2]['function'];
                }
            } catch (\Exception $e) {
            }
        }
        return null;
    }

    /**
     * Query the first row from a table only
     *
     * @param null $queryString
     * @param bool $ColumnArray
     * @return array|null|object|\stdClass
     */
    public function Query_First($queryString = null, $ColumnArray = true)
    {
        $Resource = $this->Query($queryString, $ColumnArray);
        $FirstRow = $this->Fetch($Resource, $ColumnArray);
        return $FirstRow;
    }

    public function Query_Prepare_First($queryString = null, $parameters = array(), $ColumnArray = true)
    {
        $TheQuery = $this->Query_Prepare($queryString, $parameters);
        return $this->Fetch($TheQuery, $ColumnArray);
    }

    /**
     * Fetch a row
     * @param $Resource
     * @param bool $ColumnArray
     * @return array|null|object|\stdClass
     * @throws TorneLIB_Exception
     */
    public function Fetch($Resource, $ColumnArray = true)
    {
        $dbType = $this->getDbType($this->current);
        if ($dbType == TORNEVALL_DATABASE_TYPES::MYSQL) {
            if ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_IMPROVED) {
                if ($ColumnArray) {
                    return mysqli_fetch_array($Resource, MYSQLI_ASSOC);
                } else {
                    return mysqli_fetch_object($Resource);
                }
            } elseif ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_DEPRECATED) {
                if ($ColumnArray) {
                    return mysql_fetch_array($Resource, MYSQL_ASSOC);
                } else {
                    return mysql_fetch_object($Resource);
                }
            } elseif ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_PDO) {
                return $Resource->fetch();
            }
        }
    }

    /**
     * Database prepared statement query.
     *
     * Usage example: Query_Prepare("INSERT INTO user SET userid = ?", array('userid' => 1)
     *
     * @param $queryString
     * @param array $parameters
     * @throws TorneLIB_Exception
     */
    public function Query_Prepare($queryString, $parameters = array())
    {
        $dbType = $this->getDbType($this->current);
        $parameterTypes = str_pad("", count($parameters), "s");
        if ($dbType == TORNEVALL_DATABASE_TYPES::MYSQL) {
            if ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_IMPROVED) {
                $preparedStatement = mysqli_prepare($this->db, $queryString);
                if (!empty($preparedStatement)) {
                    $refArgs = array($preparedStatement, $parameterTypes);
                    foreach ($parameters as $key => $value) {
                        $refArgs[] =& $parameters[$key];
                    }
                    if (version_compare(phpversion(), "5.3.0", ">=")) {
                        if (count($parameters)) {
                            call_user_func_array("mysqli_stmt_bind_param", $refArgs);
                        }
                        if (mysqli_stmt_execute($preparedStatement)) {
                            $returnResult = array();
                            if (method_exists($preparedStatement, "get_result")) {
                                // Method that works only with mysqlnd
                                $returnResult = $preparedStatement->get_result();
                            } else {
                                // TODO: Think this over, if it actually should be a multirow supported query
                                $meta = $preparedStatement->result_metadata();
                                $resultArray = array();
                                $dataArray = array();
                                // If there is no meta, there is probably nothing to fetch either.
                                if (isset($meta) && !empty($meta)) {
                                    while ($field = $meta->fetch_field()) {
                                        $resultArray[] = &$dataArray[$field->name];
                                    }
                                    call_user_func_array(array($preparedStatement, 'bind_result'), $resultArray);
                                    $i = 0;
                                    while ($preparedStatement->fetch()) {
                                        $array[$i] = array();
                                        foreach ($dataArray as $dataKey => $dataValue) {
                                            $array[$i][$dataKey] = $dataValue;
                                            $i++;
                                        }
                                    }
                                }
                                if (count($dataArray)) {
                                    $returnResult = $dataArray;
                                }
                            }
                            // TODO: Last insert id

                            return $returnResult;
                        }
                    } else {
                        // TODO: Finish this on a 5.3 platform
                        //mysqli_bind_param($parameterTypes, $parameters);
                    }
                } else {
                    $errNo = mysqli_errno($this->db);
                    $errMsg = "";
                    if ($errNo) {
                        $errMsg = mysqli_error($this->db);
                        throw new TorneLIB_Exception("PHPException/MySQLi: " . $errMsg, $errNo, __FUNCTION__);
                    }
                }
            } elseif ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_DEPRECATED) {
                throw new TorneLIB_Exception("Prepared statements are not supported in this driver", TORNELIB_EXCEPTIONS::TORNELIB_DB_QUERY_MYSQL_PREPARE_EXCEPTION, __FUNCTION__);
            } elseif ($this->mysqlType == TORNEVALL_MYSQL_TYPES::MYSQL_TYPE_PDO) {
                // TODO: Add support for this driver.
                throw new TorneLIB_Exception("PDO in prepared statement mode are not yet implemented", TORNELIB_EXCEPTIONS::TORNELIB_DB_QUERY_MYSQLPDO_PREPARE_EXCEPTION, __FUNCTION__);
            }
        }
    }

    public function hasConnection()
    {
        return $this->hasServerConnection;
    }

    /**
     * Tells the client whether we are connected or not
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->dbServerConnection;
    }

    /**
     * Tells the client whether we have a schema link
     *
     * @return bool
     */
    public function hasDbConnection()
    {
        return $this->dbDataConnection;
    }
}

/**
 * Class TORNEVALL_MYSQL_TYPES
 * @package TorneLIB
 */
abstract class TORNEVALL_MYSQL_TYPES
{
    const MYSQL_TYPE_NONE = 0;
    const MYSQL_TYPE_IMPROVED = 1;
    const MYSQL_TYPE_PDO = 2;
    const MYSQL_TYPE_DEPRECATED = 3;
}

/**
 * Class TORNEVALL_IMPLODE_TYPES
 * @package TorneLIB
 */
abstract class TORNEVALL_IMPLODE_TYPES
{
    const IMPLODE_INSERT = 0;
    const IMPLODE_UPDATE = 1;
    const IMPLODE_AND = 2;
    const IMPLODE_OR = 4;
}

/**
 * Class TORNEVALL_DATABASE_TYPES
 * @package TorneLIB
 */
abstract class TORNEVALL_DATABASE_TYPES
{
    const NONE = 0;
    const MYSQL = 1;
    const SQLITE3 = 2;
    const PGSQL = 3;
    const ODBC = 4;
    const MSSQL = 5;
    const PDO = 6;
}
