<?php

namespace Resursbank\RBEcomPHP;

if (class_exists('Resursbank_Obsolete_Functions',
        ECOM_CLASS_EXISTS_AUTOLOAD) && class_exists('Resursbank\RBEcomPHP\Resursbank_Obsolete_Functions',
        ECOM_CLASS_EXISTS_AUTOLOAD)) {
    return;
}

/**
 * Class Resursbank_Obsolete_Functions Functions that is obsolete and should no longer be used
 *
 * NOTES: Do NOT keep $this from the base module, as there are data that most probably want to be inherited
 * from the methods still supported. For example, using getPreferredId() needs to get its id from a inheritage
 * unless you don't want a brand new id from ECom. Which you probably do not want.
 *
 * All deprecated public variables will from this version of EComPHP be reset.
 *
 * @package Resursbank\RBEcomPHP
 */
class Resursbank_Obsolete_Functions
{

    /**
     * Last error received
     * @var
     * @deprecated 1.0.1
     * @deprecated 1.0.1
     */
    public $lastError;
    /**
     * If set to true, EComPHP will throw on wsdl initialization (default is false, since snapshot 20160405, when Omni got implemented )
     *
     * @var bool
     * @deprecated 1.0.1
     * @deprecated 1.0.1
     */
    public $throwOnInit = false;

    /// PHP Support
    /**
     * User activation flag
     * @var bool
     * @deprecated Removed in 1.2
     */
    private $allowObsoletePHP = false;

    /**
     * If set to true, we're trying to convert received object data to standard object classes so they don't get incomplete on serialization.
     *
     * Only a few calls are dependent on this since most of the objects don't need this.
     * Related to issue #63127
     *
     * @var bool
     * @deprecated 1.0.1
     * @deprecated 1.0.1
     */

    public $convertObjects = false;
    /**
     * Converting objects when a getMethod is used with ecommerce. This is only activated when convertObjects are active
     *
     * @var bool
     * @deprecated 1.0.1
     * @deprecated 1.0.1
     */
    public $convertObjectsOnGet = true;

    /**
     * Auto configuration loader
     *
     * This API was set to primary use Resurs simplified shopflow. By default, this service is loaded automatically, together with the configurationservice which is used for setting up callbacks, etc. If you need other services, like aftershop, you should add it when your API is loading like this for example:<br>
     * $API->Include[] = 'AfterShopFlowService';
     *
     * @var array Simple array with a list of which interfaces that should be automatically loaded on init. Default: ConfigurationService, SimplifiedShopFlowService
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    public $Include = ['ConfigurationService', 'SimplifiedShopFlowService', 'AfterShopFlowService'];
    /**
     * @var \stdClass $configurationService
     * @deprecated Calls to this service class are no longer in use
     */
    public $configurationService = null;
    /**
     * @var \stdClass $developerWebService
     * @deprecated Calls to this service class are no longer in use
     */
    public $developerWebService = null;
    /**
     * @var \stdClass $simplifiedShopFlowService
     * @deprecated Calls to this service are no longer in use
     */
    public $simplifiedShopFlowService = null;
    /**
     * @var \stdClass $afterShopFlowService
     * @deprecated Calls to this service class are no longer in use
     */
    public $afterShopFlowService = null;
    /**
     * @var \stdClass $shopFlowService
     * @deprecated Calls to this service class are no longer in use
     */
    public $shopFlowService = null;
    /** @var \stdClass What the service has returned (debug) */
    public $serviceReturn;
    ///// Public SSL handlers
    /**
     * Autodetecting of SSL capabilities section
     *
     * Default settings: Always disabled, to let the system handle this automatically.
     * If there are problems reaching wsdl or connecting to https://test.resurs.com, set $testssl to true
     *
     * @deprecated 1.0.1 CURL library handles most of it
     * @deprecated 1.1.1 CURL library handles most of it
     */
    /**
     * PHP 5.6.0 or above only: If defined, try to guess if there is valid certificate bundles when using for example https links (used with openssl).
     * This function tries to detect whether sslVerify should be used or not. The default value of this setting is normally false, since there should be no problems in a correctly installed environment.
     * @var bool
     * @deprecated 1.0.1 CURL library handles most of it
     * @deprecated 1.1.1 CURL library handles most of it
     */
    public $testssl = false;

    /**
     * Sets "verify SSL certificate in production required" if true (and if true, unverified SSL certificates will throw an error in production) - for auto-testing certificates only
     *
     * @var bool
     * @deprecated 1.0.1 CURL library handles most of it
     * @deprecated 1.1.1 CURL library handles most of it
     */
    public $sslVerifyProduction = true;
    /**
     * Do not test certificates on older PHP-version (< 5.6.0) if this is false
     *
     * @var bool
     * @deprecated 1.0.1 CURL library handles most of it
     * @deprecated 1.1.1 CURL library handles most of it
     */
    public $testssldeprecated = false;
    /**
     * Default paths to the certificates we are looking for
     *
     * @var array
     * @deprecated 1.0.1 CURL library handles most of it
     * @deprecated 1.1.1 CURL library handles most of it
     */
    public $sslPemLocations = [
        '/etc/ssl/certs/cacert.pem',
        '/etc/ssl/certs/ca-certificates.crt',
        '/usr/local/ssl/certs/cacert.pem',
    ];
    /**
     * In tests being made with newer wsdl stubs, extended customer are surprisingly always best practice. It is however settable from here if we need to avoid this.
     *
     * @var bool
     * @deprecated 1.0.2 No longer in use as everything uses extended customer
     * @deprecated 1.1.2 No longer in use as everything uses extended customer
     */
    public $alwaysUseExtendedCustomer = true;
    /**
     * For backwards compatibility - If this extension are being used in an environment where namespaces are set up, this will be flagged true here
     * @var bool
     * @deprecated 1.0.8
     * @deprecated 1.1.8
     */
    private $hasNameSpace = false;

    /**
     * For backwards compatibility - If this extension has the full wsdl package included, this will be flagged true here
     * @var bool
     * @deprecated 1.0.8
     * @deprecated 1.1.8
     */
    private $hasWsdl = false;

    /**
     * Simple web engine built on CURL, used for hosted flow
     * @var null
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    private $simpleWebEngine;
    /** @var string The current chosen URL for omnicheckout after initiation */
    private $env_omni_current = "";
    /** @var string The current chosen URL for hosted flow after initiation */
    private $env_hosted_current = "";
    /**
     * JSON string generated by toJsonByType (hosted flow)
     * @var string
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $jsonHosted = "";
    /**
     * JSON string generated by toJsonByType (Resurs Checkout flow)
     * @var string
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    // A service that has been enforced, as the client already knows about the flow (It has rather been set through the setPreferredPaymentService()-API instead of in the regular payload)
    private $jsonOmni = "";
    /** @var null Omnicheckout payment data container */
    private $omniFrame = null;

    ///// Private SSL handlers
    /**
     * Marks if ssl controls indicates that we have a valid SSL certificate bundle available
     * @var bool
     * @deprecated 1.0.1 CURL library handles most of it
     * @deprecated 1.1.1 CURL library handles most of it
     */
    private $hasCertFile = false;
    /**
     * Marks which file that is used as certificate bundle
     * @var string
     * @deprecated 1.0.1 CURL library handles most of it
     * @deprecated 1.1.1 CURL library handles most of it
     */
    private $useCertFile = "";
    /**
     * Marks if the SSL certificates found has been discovered internally or by user
     * @var bool
     * @deprecated 1.0.1 CURL library handles most of it
     * @deprecated 1.1.1 CURL library handles most of it
     */
    private $hasDefaultCertFile = false;
    /**
     * Marks if SSL certificate bundles-checker has been runned
     * @var bool
     * @deprecated 1.0.1 CURL library handles most of it
     * @deprecated 1.1.1 CURL library handles most of it
     */
    private $openSslGuessed = false;
    /**
     * During tests this will be set to true if certificate directory is found
     *
     * @var bool
     * @deprecated 1.0.1 CURL library handles most of it
     * @deprecated 1.1.1 CURL library handles most of it
     */
    private $hasCertDir = false;
    /**
     * SSL Certificate verification setting. Setting this to false, we will ignore certificate errors
     *
     * @var bool
     * @deprecated 1.0.1 CURL library handles most of it
     * @deprecated 1.1.1 CURL library handles most of it
     */
    private $sslVerify = true;
    /**
     * @var string The current directory of RB Classes
     * @deprecated Removed in 1.2
     */
    private $classPath = "";

    /**
     * @var array Files to look for in class directories, to find RB
     * @deprecated Removed in 1.2
     */
    private $classPathFiles = [
        '/simplifiedshopflowservice-client/Resurs_SimplifiedShopFlowService.php',
        '/configurationservice-client/Resurs_ConfigurationService.php',
        '/aftershopflowservice-client/Resurs_AfterShopFlowService.php',
        '/shopflowservice-client/Resurs_ShopFlowService.php',
    ];

    /**
     * Stored array for booked payments
     * @var array
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $bookData = [];
    /**
     * bookedCallbackUrl that may be set in runtime on bookpayments - has to be null or a string with 1 character or more
     * @var string
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $_bookedCallbackUrl = null;
    /**
     * EComPHP will set this value to true, if the library "interfered" with the cart
     * @var bool
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $bookPaymentCartFixed = false;
    /**
     * Last booked payment state
     * @var string
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $lastBookPayment = null;
    /**
     * Payment data object
     * @var array|object
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $_paymentData = null;
    /**
     * Object for speclines/specrows
     * @var array|object
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $_paymentSpeclines = null;
    /**
     * Counter for a specline
     * @var int
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $_specLineID = null;
    /**
     * Order data for the payment
     * @var array|object
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $_paymentOrderData = null;
    /**
     * Address data for the payment
     * @var array|object
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $_paymentAddress = null;
    /**
     * Normally used if billing and delivery differs (Sent to the gateway clientside)
     * @var array|object
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $_paymentDeliveryAddress = null;
    /**
     * Customer data for the payment
     * @var array|object
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $_paymentCustomer = null;
    /**
     * Customer data, extended, for the payment. For example when delivery address is set
     * @var array|object
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $_paymentExtendedCustomer = null;
    /**
     * Card data for the payment
     * @var array|object
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $_paymentCardData = null;
    /**
     * Card data object: Card number
     * @var array|object
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $cardDataCardNumber = null;
    /**
     * Card data object: The amount applied for the customer
     * @var bool
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $cardDataUseAmount = false;
    /**
     * Card data object: If set, you can set up your own amount to apply for
     * @var int
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private $cardDataOwnAmount = null;
    /// Internal handlers for bookings -- Finish here

    ///// Template rules (Which is a clone from Resurs Bank deprecated shopflow that defines what customer data fields that is required while running simplified flow)
    ///// In the deprecated shopFlow, this was the default behaviour.
    /** @var array Form template rules handled */
    private $formTemplateRuleArray = [];
    /**
     * Array rules set, baed by getTemplateFieldsByMethodType()
     * @var array
     */
    private $templateFieldsByMethodResponse = [];


    ///// Configuration system (deprecated)
    /**
     * Configuration array
     * @var array
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    private $config;
    /**
     * Set to true if you want to try to use internally cached data. This is disabled by default since it may, in a addition to performance, also be a security issue since the configuration file needs read- and write permissions
     * @var bool
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    private $configurationInternal = false;
    /**
     * For internal handling of cache, etc
     * @var string
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    private $configurationSystem = "configuration";
    /**
     * Usage of Configuration file
     * @var string
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    private $configurationStorage = "";
    /**
     * Configuration, settings, payment methods etc
     * @var array
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    private $configurationArray = [];
    /**
     * Time in seconds when cache should be considered outdated and needs to get updated with new fresh data
     * @var int
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    private $configurationCacheTimeout = 3600;


    protected $parent;

    /**
     * Resursbank_Obsolete_Functions constructor.
     *
     * @param $parent
     */
    public function __construct($parent)
    {
        $this->parent = $parent;
    }

    /**
     * Testing function
     * @return $this
     */
    public function testThis()
    {
        return $this->parent;
    }

    /**
     * Generates a unique "preferredId" out of a datestamp
     *
     * @param int $maxLength The maximum recommended length of a preferred id is currently 25.
     *                       The order numbers may be shorter (the minimum length is 14, but in that case only the
     *                       timestamp will be returned)
     * @param string $prefix Prefix to prepend at unique id level
     * @param bool $dualUniq Be paranoid and sha1-encrypt the first random uniq id first.
     *
     * @return string
     * @since 1.0.0
     * @since 1.1.0
     * @deprecated 1.0.13 Will be replaced with getPreferredPaymentId
     * @deprecated 1.1.13 Will be replaced with getPreferredPaymentId
     */
    public function getPreferredId($maxLength = 25, $prefix = "", $dualUniq = true)
    {
        return $this->parent->getPreferredPaymentId($maxLength, $prefix, $dualUniq);
    }

    /**
     * Prepare API for cards. Make sure only one of the parameters are used. Cardnumber cannot be combinad with amount.
     *
     * @param null $cardNumber
     * @param bool|false $useAmount Set to true when using new cards
     * @param bool|false $setOwnAmount If customer applies for a new card specify the credit amount that is applied for. If $setOwnAmount is not null, this amount will be used instead of the specrow data
     *
     * @throws \Exception
     * @deprecated 1.0.2 Use setCardData instead
     * @deprecated 1.1.2 Use setCardData instead
     */
    public function prepareCardData($cardNumber = null, $useAmount = false, $setOwnAmount = null)
    {
        $this->setCardData($cardNumber, $setOwnAmount);
    }

    /**
     * Simplifed callback registrator. Also handles re-registering of callbacks in case of already found in system.
     *
     * @param int $callbackType
     * @param string $callbackUriTemplate
     * @param array $callbackDigest If no parameters are set, this will be handled automatically.
     * @param null $basicAuthUserName
     * @param null $basicAuthPassword
     *
     * @return bool
     * @throws \Exception
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    public function setCallback(
        $callbackType = RESURS_CALLBACK_TYPES::CALLBACK_TYPE_NOT_SET,
        $callbackUriTemplate = "",
        $callbackDigest = [],
        $basicAuthUserName = null,
        $basicAuthPassword = null
    ) {
        return $this->parent->setRegisterCallback($callbackType, $callbackUriTemplate, $callbackDigest,
            $basicAuthUserName,
            $basicAuthPassword);
    }

    /**
     * Simplifies removal of callbacks even when they does not exist at first.
     *
     * @param int $callbackType
     *
     * @return bool
     * @throws \Exception
     * @deprecated 1.0.1 Use unregisterEventCallback instead
     * @deprecated 1.1.1 Use unregisterEventCallback instead
     */
    public function unSetCallback($callbackType = RESURS_CALLBACK_TYPES::CALLBACK_TYPE_NOT_SET)
    {
        return $this->parent->unregisterEventCallback($callbackType);
    }

    /**
     * Trigger registered callback event TEST
     *
     * @return bool
     * @throws \Exception
     * @deprecated 1.0.1 Use triggerCallback() instead
     * @deprecated 1.1.1 Use triggerCallback() instead
     */
    public function testCallback()
    {
        return $this->parent->triggerCallback();
    }

    /**
     * Get the preferred method for a service (GET or POST baed)
     *
     * @param string $ServiceName
     *
     * @return string
     * @since 1.0.2
     * @since 1.1.2
     * @deprecated 1.0.40
     * @deprecated 1.1.40
     */
    private function getServiceMethod($ServiceName = '')
    {
        $ReturnMethod = "GET";
        if (isset($this->parent->ServiceRequestMethods[$ServiceName])) {
            $ReturnMethod = $this->parent->ServiceRequestMethods[$ServiceName];
        }

        return strtolower($ReturnMethod);
    }

    /**
     * bookPayment - Compiler for bookPayment.
     *
     * This is the entry point of the simplified version of bookPayment. The normal action here is to send a bulked array with settings for how the payment should be handled (see https://test.resurs.com/docs/x/cIZM)
     * Minor notice: We are currently preparing support for hosted flow by sending array('type' => 'hosted'). It is however not ready to run yet.
     *
     * @param string $paymentMethodIdOrPaymentReference
     * @param array $bookData
     * @param bool $getReturnedObjectAsStd Returning a stdClass instead of a Resurs class
     * @param bool $keepReturnObject Making EComPHP backwards compatible when a webshop still needs the complete object, not only $bookPaymentResult->return
     * @param array $externalParameters External parameters
     *
     * @return object
     * @throws \Exception
     * @link https://test.resurs.com/docs/x/cIZM bookPayment EComPHP Reference
     * @link https://test.resurs.com/docs/display/ecom/bookPayment bookPayment reference
     * @deprecated 1.1.2
     * @deprecated 1.0.2
     */
    public function bookPayment(
        $paymentMethodIdOrPaymentReference = '',
        $bookData = [],
        $getReturnedObjectAsStd = true,
        $keepReturnObject = false,
        $externalParameters = []
    ) {
        return $this->parent->createPayment($paymentMethodIdOrPaymentReference, $bookData);
    }

    /**
     * Booking payments as a bulk (bookPaymentBuilder)
     *
     * This is where the priary payment booking renderer resides, where all required data are precompiled for the booking.
     * Needs an array that is built in a similar way that is documented in the simplifiedShopFlow-reference at test.resurs.com.
     *
     * @link https://test.resurs.com/docs/x/cIZM bookPayment EComPHP Reference
     * @link https://test.resurs.com/docs/display/ecom/bookPayment bookPayment reference
     *
     * @param string $paymentMethodId For Resurs Checkout, you should pass the reference ID here
     * @param array $bookData
     * @param bool $getReturnedObjectAsStd Returning a stdClass instead of a Resurs class
     * @param bool $keepReturnObject Making EComPHP backwards compatible when a webshop still needs the complete object, not only $bookPaymentResult->return
     * @param array $externalParameters External parameters
     *
     * @return array|mixed|null This normally returns an object depending on your platform request
     * @throws \Exception
     * @deprecated 1.1.2
     * @deprecated 1.0.2
     */
    private function bookPaymentBulk(
        $paymentMethodId = '',
        $bookData = [],
        $getReturnedObjectAsStd = true,
        $keepReturnObject = false,
        $externalParameters = []
    ) {
        if (empty($paymentMethodId)) {
            return new \stdClass();
        }
        if ($this->enforceService == RESURS_FLOW_TYPES::METHOD_OMNI) {
            $bookData['type'] = "omni";
        } else {
            if (isset($bookData['type']) == "omni") {
                $this->enforceService = RESURS_FLOW_TYPES::METHOD_OMNI;
                $this->isOmniFlow = true;
            }
        }
        if ($this->enforceService == RESURS_FLOW_TYPES::FLOW_HOSTED_FLOW) {
            $bookData['type'] = "hosted";
        } else {
            if (isset($bookData['type']) == "hosted") {
                $this->enforceService = RESURS_FLOW_TYPES::FLOW_HOSTED_FLOW;
                $this->isHostedFlow = true;
            }
        }
        $skipSteps = [];
        /* Special rule preparation for Resurs Bank hosted flow */
        if ($this->getBookParameter('type',
                $externalParameters) == "hosted" || (isset($bookData['type']) && $bookData['type'] == "hosted")) {
            $this->isHostedFlow = true;
        }
        /* Special rule preparation for Resurs Bank Omnicheckout */
        if ($this->getBookParameter('type',
                $externalParameters) == "omni" || (isset($bookData['type']) && $bookData['type'] == "omni")) {
            $this->isOmniFlow = true;
            /*
			 * In omnicheckout the first variable is not the payment method, it is the preferred order id
			 */
            if (empty($this->preferredId)) {
                $this->preferredId = $paymentMethodId;
            }
        }
        /* Make EComPHP ignore some steps that is not required in an omni checkout */
        if ($this->isOmniFlow) {
            $skipSteps['address'] = true;
        }
        /* Prepare for a simplified flow */
        if (!$this->isOmniFlow && !$this->isHostedFlow) {
            // Do not use wsdl stubs if we are targeting rest services
            $this->InitializeServices();
        }
        $this->updatePaymentdata($paymentMethodId,
            isset($bookData['paymentData']) && is_array($bookData['paymentData']) && count($bookData['paymentData']) ? $bookData['paymentData'] : []);
        if (isset($bookData['specLine']) && is_array($bookData['specLine'])) {
            $this->updateCart(isset($bookData['specLine']) ? $bookData['specLine'] : []);
        } else {
            // For omni and hosted flow, if specLine is not set
            if (isset($bookData['orderLines']) && is_array($bookData['orderLines'])) {
                $this->updateCart(isset($bookData['orderLines']) ? $bookData['orderLines'] : []);
            }
        }
        $this->updatePaymentSpec($this->_paymentSpeclines);
        /* Prepare address data for hosted flow and simplified, ignore if we're on omni, where this data is not required */
        if (!isset($skipSteps['address'])) {
            if (isset($bookData['deliveryAddress'])) {
                $addressArray = [
                    'address' => $bookData['address'],
                    'deliveryAddress' => $bookData['deliveryAddress'],
                ];
                $this->updateAddress(isset($addressArray) ? $addressArray : [],
                    isset($bookData['customer']) ? $bookData['customer'] : []);
            } else {
                $this->updateAddress(isset($bookData['address']) ? $bookData['address'] : [],
                    isset($bookData['customer']) ? $bookData['customer'] : []);
            }
        }
        /* Prepare and collect data for a bookpayment - if the flow is simple */
        if ((!$this->isOmniFlow && !$this->isHostedFlow) && (class_exists('Resursbank\RBEcomPHP\resurs_bookPayment',
                    ECOM_CLASS_EXISTS_AUTOLOAD) || class_exists('resurs_bookPayment', ECOM_CLASS_EXISTS_AUTOLOAD))) {
            /* Only run this if it exists, and the plans is to go through simplified flow */
            $bookPaymentInit = new resurs_bookPayment($this->_paymentData, $this->_paymentOrderData,
                $this->_paymentCustomer, $this->_bookedCallbackUrl);
        } else {
            /*
			 * If no "new flow" are detected during the handle of payment here, and the class also exists so no booking will be possible, we should
			 * throw an execption here.
			 */
            if (!$this->isOmniFlow && !$this->isHostedFlow) {
                throw new Exception(__FUNCTION__ . ": bookPaymentClass not found, and this is neither an omni nor hosted flow",
                    \RESURS_EXCEPTIONS::BOOKPAYMENT_NO_BOOKPAYMENT_CLASS);
            }
        }
        if (!empty($this->cardDataCardNumber) || $this->cardDataUseAmount) {
            $bookPaymentInit->card = $this->updateCardData();
        }
        if (!empty($this->_paymentDeliveryAddress) && is_object($this->_paymentDeliveryAddress)) {
            $bookPaymentInit->customer->deliveryAddress = $this->_paymentDeliveryAddress;
        }
        /* If the preferredId is set, check if there is a request for this varaible in the signing urls */
        if (isset($this->_paymentData->preferredId)) {
            // Make sure that the search and replace really works for unique id's
            if (!isset($bookData['uniqueId'])) {
                $bookData['uniqueId'] = "";
            }
            if (isset($bookData['signing']['successUrl'])) {
                $bookData['signing']['successUrl'] = str_replace('$preferredId', $this->_paymentData->preferredId,
                    $bookData['signing']['successUrl']);
                $bookData['signing']['successUrl'] = str_replace('%24preferredId', $this->_paymentData->preferredId,
                    $bookData['signing']['successUrl']);
                if (isset($bookData['uniqueId'])) {
                    $bookData['signing']['successUrl'] = str_replace('$uniqueId', $bookData['uniqueId'],
                        $bookData['signing']['successUrl']);
                    $bookData['signing']['successUrl'] = str_replace('%24uniqueId', $bookData['uniqueId'],
                        $bookData['signing']['successUrl']);
                }
            }
            if (isset($bookData['signing']['failUrl'])) {
                $bookData['signing']['failUrl'] = str_replace('$preferredId', $this->_paymentData->preferredId,
                    $bookData['signing']['failUrl']);
                $bookData['signing']['failUrl'] = str_replace('%24preferredId', $this->_paymentData->preferredId,
                    $bookData['signing']['failUrl']);
                if (isset($bookData['uniqueId'])) {
                    $bookData['signing']['failUrl'] = str_replace('$uniqueId', $bookData['uniqueId'],
                        $bookData['signing']['failUrl']);
                    $bookData['signing']['failUrl'] = str_replace('%24uniqueId', $bookData['uniqueId'],
                        $bookData['signing']['failUrl']);
                }
            }
        }
        /* If this request actually belongs to an omni flow, let's handle the incoming data differently */
        if ($this->isOmniFlow) {
            /* Prepare a frame for omni checkout */
            try {
                $preOmni = $this->prepareOmniFrame($bookData, $paymentMethodId,
                    RESURS_CHECKOUT_CALL_TYPES::METHOD_PAYMENTS);
                if (isset($preOmni->html)) {
                    $this->omniFrame = $preOmni->html;
                }
            } catch (\Exception $omniFrameException) {
                throw new Exception(__FUNCTION__ . "/prepareOmniFrame: " . $omniFrameException->getMessage(),
                    $omniFrameException->getCode());
            }
            if (isset($this->omniFrame->faultCode)) {
                throw new Exception(__FUNCTION__ . "/prepareOmniFrame-bookPaymentOmniFrame: " . (isset($this->omniFrame->description) ? $this->omniFrame->description : "Unknown error received from Resurs Bank OmniAPI"),
                    $this->omniFrame->faultCode);
            }

            return $this->omniFrame;
        }
        /* Now, if this is a request for hosted flow, handle the completed data differently */
        if ($this->isHostedFlow) {
            $bookData['orderData'] = $this->objectsIntoArray($this->_paymentOrderData);
            try {
                $hostedResult = $this->bookPaymentHosted($paymentMethodId, $bookData, $getReturnedObjectAsStd,
                    $keepReturnObject);
            } catch (\Exception $hostedException) {
                throw new Exception(__FUNCTION__ . ": " . $hostedException->getMessage(), $hostedException->getCode());
            }
            if (isset($hostedResult->location)) {
                return $hostedResult->location;
            } else {
                throw new Exception(__FUNCTION__ . "/bookPaymentHosted: Can not find location in hosted flow", 404);
            }
        }
        /* If this request was not about an omni flow, let's continue prepare the signing data */
        if (isset($bookData['signing'])) {
            $bookPaymentInit->signing = $bookData['signing'];
        }
        try {
            $bookPaymentResult = $this->simplifiedShopFlowService->bookPayment($bookPaymentInit);
        } catch (\Exception $bookPaymentException) {
            if (isset($bookPaymentException->faultstring)) {
                throw new Exception($bookPaymentException->faultstring, 500);
            }
            throw new Exception(__FUNCTION__ . ": " . $bookPaymentException->getMessage(),
                $bookPaymentException->getCode());
        }
        if ($getReturnedObjectAsStd) {
            if (isset($bookPaymentResult->return)) {
                /* Set up a globally reachable result for the last booked payment */
                $this->lastBookPayment = $bookPaymentResult->return;
                if (!$keepReturnObject) {
                    return $this->getDataObject($bookPaymentResult->return);
                } else {
                    return $this->getDataObject($bookPaymentResult);
                }
            } else {
                throw new Exception(__FUNCTION__ . ": bookPaymentResult does not contain a return object", 500);
            }
        }

        return $bookPaymentResult;
    }


    /**
     * Adds your client name to the current client name string which is used as User-Agent when communicating with ecommerce.
     *
     * @param string $clientNameString
     *
     * @throws Exception
     * @deprecated 1.0.2 Use setUserAgent
     * @deprecated 1.1.2 Use setUserAgent
     */
    public function setClientName($clientNameString = "")
    {
        if (!empty($clientNameString)) {
            $this->parent->setUserAgent($clientNameString);
        }
    }


    /////////////////////////// EXTREMELY OUTDATED CODE BEGIN (THAT MIGHT NOT EVEN WORK ANYMORE SINCE WERE OUT OF WSDL)


    /////////////////////////// OUTDATED JSON METHODS

    /**
     * dataContainer array to JSON converter
     *
     * This part of EComPHP only makes sure, if the customer are using the simplifiedFlow structure in a payment method
     * that is not simplified, that the array gets converted to the right format. This part is ONLY needed if the plugin of the
     * representative doesn't do it properly.
     *
     * @param array $dataContainer
     * @param int $paymentMethodType
     * @param bool $updateCart Defines if this a cart upgrade only
     *
     * @return array|mixed|string|void
     * @throws \Exception
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    public function toJsonByType(
        $dataContainer = [],
        $paymentMethodType = RESURS_FLOW_TYPES::FLOW_SIMPLIFIED_FLOW,
        $updateCart = false
    ) {
        // We need the content as is at this point since this part normally should be received as arrays
        $newDataContainer = $this->getDataObject($dataContainer, false, true);
        if (!isset($newDataContainer['type']) || empty($newDataContainer['type'])) {
            if ($paymentMethodType == RESURS_FLOW_TYPES::FLOW_HOSTED_FLOW) {
                $newDataContainer['type'] = 'hosted';
            } else {
                if ($paymentMethodType == RESURS_FLOW_TYPES::METHOD_OMNI) {
                    $newDataContainer['type'] = 'omni';
                }
            }
        }
        if (isset($newDataContainer['type']) && !empty($newDataContainer['type'])) {
            /**
             * Hosted flow ruleset
             */
            if (strtolower($newDataContainer['type']) == "hosted") {
                /* If the specLines are defined as simplifiedFlowSpecrows, we need to convert them to hosted speclines */
                $hasSpecLines = false;
                /* If there is an old array containing specLines, this has to be renamed to orderLines */
                if (isset($newDataContainer['orderData']['specLines'])) {
                    $newDataContainer['orderData']['orderLines'] = $newDataContainer['orderData']['specLines'];
                    unset($newDataContainer['orderData']['specLines']);
                    $hasSpecLines = true;
                }
                /* If there is a specLine defined in the parent array ... */
                if (isset($newDataContainer['specLine'])) {
                    /* ... then check if we miss orderLines ... */
                    if (!$hasSpecLines) {
                        /* ... and add them on demand */
                        $newDataContainer['orderData']['orderLines'] = $newDataContainer['specLine'];
                    }
                    /* Then unset the old array */
                    unset($newDataContainer['specLine']);
                }
                /* If there is an address array on first level, we need to move the array to the customerArray*/
                if (isset($newDataContainer['address'])) {
                    $newDataContainer['customer']['address'] = $newDataContainer['address'];
                    unset($newDataContainer['address']);
                }
                /* The same rule as in the address case applies to the deliveryAddress */
                if (isset($newDataContainer['deliveryAddress'])) {
                    $newDataContainer['customer']['deliveryAddress'] = $newDataContainer['deliveryAddress'];
                    unset($newDataContainer['deliveryAddress']);
                }
                /* Now, let's see if there is a simplifiedFlow country applied to the customer data. In that case, we need to convert it to at countryCode. */
                if (isset($newDataContainer['customer']['address']['country'])) {
                    $newDataContainer['customer']['address']['countryCode'] = $newDataContainer['customer']['address']['country'];
                    unset($newDataContainer['customer']['address']['country']);
                }
                /* The same rule applied to the deliveryAddress */
                if (isset($newDataContainer['customer']['deliveryAddress']['country'])) {
                    $newDataContainer['customer']['deliveryAddress']['countryCode'] = $newDataContainer['customer']['deliveryAddress']['country'];
                    unset($newDataContainer['customer']['deliveryAddress']['country']);
                }
                if (isset($newDataContainer['signing'])) {
                    if (!isset($newDataContainer['successUrl']) && isset($newDataContainer['signing']['successUrl'])) {
                        $newDataContainer['successUrl'] = $newDataContainer['signing']['successUrl'];
                    }
                    if (!isset($newDataContainer['failUrl']) && isset($newDataContainer['signing']['failUrl'])) {
                        $newDataContainer['failUrl'] = $newDataContainer['signing']['failUrl'];
                    }
                    if (!isset($newDataContainer['forceSigning']) && isset($newDataContainer['signing']['forceSigning'])) {
                        $newDataContainer['forceSigning'] = $newDataContainer['signing']['forceSigning'];
                    }
                    unset($newDataContainer['signing']);
                }
                $this->jsonHosted = $this->getDataObject($newDataContainer, true);
            }

            /**
             * OmniCheckout Ruleset
             */
            if (strtolower($newDataContainer['type']) == "omni") {
                if (isset($newDataContainer['specLine'])) {
                    $newDataContainer['orderLines'] = $newDataContainer['specLine'];
                    unset($newDataContainer['specLine']);
                }
                if (isset($newDataContainer['specLines'])) {
                    $newDataContainer['orderLines'] = $newDataContainer['specLines'];
                    unset($newDataContainer['specLines']);
                }
                /*
                 * OmniFrameJS helper.
                 */
                if (!isset($newDataContainer['shopUrl'])) {
                    $newDataContainer['shopUrl'] = $this->checkoutShopUrl;
                }

                $orderlineProps = [
                    "artNo",
                    "vatPcs",
                    "vatPct",
                    "unitMeasure",
                    "quantity",
                    "description",
                    "unitAmountWithoutVat",
                ];
                /**
                 * Sanitizing orderlines in case it's an orderline conversion from a simplified shopflow.
                 */
                if (isset($newDataContainer['orderLines']) && is_array($newDataContainer['orderLines'])) {
                    $orderLineClean = [];
                    /*
                     * Single Orderline Compatibility: When an order line is not properly sent to the handler, it has to be converted to an indexed array first,
                     */
                    if ($newDataContainer['type'] == "omni") {
                        unset($newDataContainer['paymentData'], $newDataContainer['customer']);
                    }
                    if (isset($newDataContainer['orderLines']['artNo'])) {
                        $singleOrderLine = $newDataContainer['orderLines'];
                        $newDataContainer['orderLines'] = [$singleOrderLine];
                    }
                    unset($newDataContainer['customer'], $newDataContainer['paymentData']);
                    foreach ($newDataContainer['orderLines'] as $orderLineId => $orderLineArray) {
                        if (is_array($orderLineArray)) {
                            foreach ($orderLineArray as $orderLineArrayKey => $orderLineArrayValue) {
                                if (!in_array($orderLineArrayKey, $orderlineProps)) {
                                    unset($orderLineArray[$orderLineArrayKey]);
                                }
                            }
                            $orderLineClean[] = $orderLineArray;
                        }
                    }
                    $newDataContainer['orderLines'] = $orderLineClean;
                }
                if (isset($newDataContainer['address'])) {
                    unset($newDataContainer['address']);
                }
                if (isset($newDataContainer['uniqueId'])) {
                    unset($newDataContainer['uniqueId']);
                }
                if (isset($newDataContainer['signing'])) {
                    if (!isset($newDataContainer['successUrl']) && isset($newDataContainer['signing']['successUrl'])) {
                        $newDataContainer['successUrl'] = $newDataContainer['signing']['successUrl'];
                    }
                    if (!isset($newDataContainer['backUrl']) && isset($newDataContainer['signing']['failUrl'])) {
                        $newDataContainer['backUrl'] = $newDataContainer['signing']['failUrl'];
                    }
                    unset($newDataContainer['signing']);
                }
                if (isset($newDataContainer['customer']['phone'])) {
                    if (!isset($newDataContainer['customer']['mobile']) || (isset($newDataContainer['customer']['mobile']) && empty($newDataContainer['customer']['mobile']))) {
                        $newDataContainer['customer']['mobile'] = $newDataContainer['customer']['phone'];
                    }
                    unset($newDataContainer['customer']['phone']);
                }
                if ($updateCart) {
                    /*
                     * Return orderLines only, if this function is called as an updateCart.
                     */
                    $newDataContainer = [
                        'orderLines' => is_array($newDataContainer['orderLines']) ? $newDataContainer['orderLines'] : [],
                    ];
                }
                $this->jsonOmni = $newDataContainer;
            }
        }
        if (isset($newDataContainer['type'])) {
            unset($newDataContainer['type']);
        }
        if (isset($newDataContainer['uniqueId'])) {
            unset($newDataContainer['uniqueId']);
        }
        $returnJson = $this->toJson($newDataContainer);

        return $returnJson;
    }

    /**
     * @param int $method
     *
     * @return stdClass|string
     * @deprecated 1.0.8
     * @deprecated 1.1.8
     */
    public function getBookedJsonObject($method = RESURS_FLOW_TYPES::NOT_SET)
    {
        $returnObject = new \stdClass();
        if ($method == RESURS_FLOW_TYPES::SIMPLIFIED_FLOW) {
            return $returnObject;
        } elseif ($method == RESURS_FLOW_TYPES::HOSTED_FLOW) {
            return $this->jsonHosted;
        } else {
            return $this->jsonOmni;
        }
    }

    /**
     * Convert array to json
     *
     * @param array $jsonData
     *
     * @return array|mixed|string|void
     * @throws \Exception
     * @deprecated 1.0.8
     * @deprecated 1.1.8
     */
    private function toJson($jsonData = [])
    {
        if (is_array($jsonData) || is_object($jsonData)) {
            $jsonData = json_encode($jsonData);
            if (json_last_error()) {
                throw new Exception(__FUNCTION__ . ": " . json_last_error_msg(), json_last_error());
            }
        }

        return $jsonData;
    }

    /**
     * Create a simple engine for cURL, for use with for example hosted flow.
     *
     * @param string $url
     * @param string $jsonData
     * @param int $curlMethod POST, GET, DELETE, etc
     *
     * @return mixed
     * @throws \Exception
     * @deprecated 1.0.1 As this is a posting function, this has been set to go through the CURL library
     * @deprecated 1.1.1 As this is a posting function, this has been set to go through the CURL library
     */
    private function createJsonEngine($url = '', $jsonData = "", $curlMethod = RESURS_CURL_METHODS::METHOD_POST)
    {
        if (empty($this->parent)) {
            $this->InitializeServices();
        }
        $CurlLibResponse = null;
        $this->parent->CURL->setAuthentication($this->parent->username, $this->parent->password);
        $this->parent->CURL->setUserAgent($this->parent->myUserAgent);

        if ($curlMethod == RESURS_CURL_METHODS::POST) {
            $CurlLibResponse = $this->parent->CURL->doPost($url, $jsonData, NETCURL_POST_DATATYPES::DATATYPE_JSON);
        } else {
            if ($curlMethod == RESURS_CURL_METHODS::PUT) {
                $CurlLibResponse = $this->parent->CURL->doPut($url, $jsonData, NETCURL_POST_DATATYPES::DATATYPE_JSON);
            } else {
                $CurlLibResponse = $this->parent->CURL->doGet($url, NETCURL_POST_DATATYPES::DATATYPE_JSON);
            }
        }
        $curlCode = $this->parent->getResponseCode($CurlLibResponse);
        if ($curlCode >= 400) {
            $useResponseCode = $curlCode;
            if (is_object($CurlLibResponse['parsed'])) {
                $ResursResponse = $CurlLibResponse['parsed'];
                if (isset($ResursResponse->error)) {
                    if (isset($ResursResponse->status)) {
                        $useResponseCode = $ResursResponse->status;
                    }
                    throw new Exception($ResursResponse->error, $useResponseCode);
                }
                /*
                 * Must handle ecommerce errors too.
                 */
                if (isset($ResursResponse->errorCode)) {
                    if ($ResursResponse->errorCode > 0) {
                        throw new Exception(isset($ResursResponse->description) && !empty($ResursResponse->description) ? $ResursResponse->description : "Unknown error in " . __FUNCTION__,
                            $ResursResponse->errorCode);
                    } else {
                        if ($curlCode >= 500) {
                            /*
                         * If there are any internal server errors returned, the errorCode tend to be unset (0) and therefore not trigged. In this case, as the server won't do anything good anyway, we should throw an exception
                         */
                            throw new Exception(isset($ResursResponse->description) && !empty($ResursResponse->description) ? $ResursResponse->description : "Unknown error in " . __FUNCTION__,
                                $ResursResponse->errorCode);
                        }
                    }
                }
            } else {
                $theBody = $this->parent->getResponseBody($CurlLibResponse);
                throw new Exception(!empty($theBody) ? $theBody : "Unknown error from server in " . __FUNCTION__,
                    $curlCode);
            }
        } else {
            /*
             * Receiving code 200 here is flawless
             */
            return $CurlLibResponse;
        }
    }

    /////////////////////////// OUTDATED SSL METHODS

    /**
     * Generate a correctified stream context depending on what happened in openssl_guess(), which also is running in this operation.
     *
     * Function created for moments when ini_set() fails in openssl_guess() and you don't want to "recalculate" the location of a valid certificates.
     * This normally occurs in improper configured environments (where this bulk of functions actually also has been tested in).
     * Recommendation of Usage: Do not copy only those functions, use the full version of tornevall_curl.php since there may be dependencies in it.
     *
     * @return array
     * @link http://developer.tornevall.net/apigen/TorneLIB-5.0/class-TorneLIB.Tornevall_cURL.html sslStreamContextCorrection() is a part of TorneLIB 5.0, described here
     * @deprecated 1.0.8
     * @deprecated 1.1.8
     */
    public function sslStreamContextCorrection()
    {
        if (!$this->openSslGuessed) {
            $this->openssl_guess(true);
        }
        $caCert = $this->getCertFile();
        $sslVerify = true;
        $sslSetup = [];
        if (isset($this->sslVerify)) {
            $sslVerify = $this->sslVerify;
        }
        if (!empty($caCert)) {
            $sslSetup = [
                'cafile' => $caCert,
                'verify_peer' => $sslVerify,
                'verify_peer_name' => $sslVerify,
                'verify_host' => $sslVerify,
                'allow_self_signed' => true,
            ];
        }

        return $sslSetup;
    }

    /**
     * Automatically generates stream_context and appends it to whatever you need it for.
     *
     * Example:
     *  $appendArray = array('http' => array("user_agent" => "MyUserAgent"));
     *  $this->soapOptions = sslGetDefaultStreamContext($this->soapOptions, $appendArray);
     *
     * @param array $optionsArray
     * @param array $selfContext
     *
     * @return array
     * @link http://developer.tornevall.net/apigen/TorneLIB-5.0/class-TorneLIB.Tornevall_cURL.html sslGetOptionsStream() is a part of TorneLIB 5.0, described here
     * @deprecated 1.0.8
     * @deprecated 1.1.8
     */
    public function sslGetOptionsStream($optionsArray = [], $selfContext = [])
    {
        $streamContextOptions = [];
        $sslCorrection = $this->sslStreamContextCorrection();
        if (count($sslCorrection)) {
            $streamContextOptions['ssl'] = $this->sslStreamContextCorrection();
        }
        foreach ($selfContext as $contextKey => $contextValue) {
            $streamContextOptions[$contextKey] = $contextValue;
        }
        $optionsArray['stream_context'] = stream_context_create($streamContextOptions);

        return $optionsArray;
    }

    /**
     * SSL Cerificate Handler
     *
     * This method tries to handle SSL Certification locations where PHP can't handle that part itself. In some environments (normally customized), PHP sometimes have
     * problems with finding certificates, in case for example where they are not placed in standard locations. When running the testing, we will also try to set up
     * a correct location for the certificates, if any are found somewhere else.
     *
     * The default configuration for this method is to not run any test, since there should be no problems of running in properly installed environments.
     * If there are known problems in the environment that is being used, you can try to set $testssl to true.
     *
     * At first, the variable $testssl is used to automatically try to find out if there is valid certificate bundles installed on the running system. In PHP 5.6.0 and higher
     * this procedure is simplified with the help of openssl_get_cert_locations(), which gives us a default path to installed certificates. In this case we will first look there
     * for the certificate bundle. If we do fail there, or if your system is running something older, the testing are running in guessing mode.
     *
     * The method is untested in Windows server environments when using OpenSSL.
     *
     * @param bool $forceTesting Force testing even if $testssl is disabled
     *
     * @link https://phpdoc.tornevall.net/TorneLIBv5/class-TorneLIB.Tornevall_cURL.html openssl_guess() is a part of TorneLIB 5.0, described here
     * @return bool
     * @deprecated 1.0.8
     * @deprecated 1.1.8
     */
    private function openssl_guess($forceTesting = false)
    {
        $pemLocation = "";
        if (ini_get('open_basedir') == '') {
            if ($this->testssl || $forceTesting) {
                $this->openSslGuessed = true;
                if (version_compare(PHP_VERSION, "5.6.0", ">=") && function_exists("openssl_get_cert_locations")) {
                    $locations = openssl_get_cert_locations();
                    if (is_array($locations)) {
                        if (isset($locations['default_cert_file'])) {
                            /* If it exists don't bother */
                            if (file_exists($locations['default_cert_file'])) {
                                $this->hasCertFile = true;
                                $this->useCertFile = $locations['default_cert_file'];
                                $this->hasDefaultCertFile = true;
                            }
                            if (file_exists($locations['default_cert_dir'])) {
                                $this->hasCertDir = true;
                            }
                            /* Sometimes certificates are located in a default location, which is /etc/ssl/certs - this part scans through such directories for a proper cert-file */
                            if (!$this->hasCertFile && is_array($this->sslPemLocations) && count($this->sslPemLocations)) {
                                /* Loop through suggested locations and set a cafile if found */
                                foreach ($this->sslPemLocations as $pemLocation) {
                                    if (file_exists($pemLocation)) {
                                        ini_set('openssl.cafile', $pemLocation);
                                        $this->useCertFile = $pemLocation;
                                        $this->hasCertFile = true;
                                    }
                                }
                            }
                        }
                    }
                    /* On guess, disable verification if failed */
                    if (!$this->hasCertFile) {
                        $this->setSslVerify(false);
                    }
                } else {
                    /* If we run on other PHP versions than 5.6.0 or higher, try to fall back into a known directory */
                    if ($this->testssldeprecated) {
                        if (!$this->hasCertFile && is_array($this->sslPemLocations) && count($this->sslPemLocations)) {
                            /* Loop through suggested locations and set a cafile if found */
                            foreach ($this->sslPemLocations as $pemLocation) {
                                if (file_exists($pemLocation)) {
                                    ini_set('openssl.cafile', $pemLocation);
                                    $this->useCertFile = $pemLocation;
                                    $this->hasCertFile = true;
                                }
                            }
                        }
                        if (!$this->hasCertFile) {
                            $this->setSslVerify(false);
                        }
                    }
                }
            }
        } else {
            // Assume there is a valid certificate if jailed by open_basedir
            $this->hasCertFile = true;

            return true;
        }

        return $this->hasCertFile;
    }

    /**
     * Return the current certificate bundle file, chosen by autodetection
     * @return string
     * @deprecated 1.0.8
     * @deprecated 1.1.8
     */
    public function getCertFile()
    {
        return $this->useCertFile;
    }


    /////////////////////////// OUTDATED DATA STUFF

    /**
     * Convert a object to a data object
     *
     * @param array $d
     * @param bool $forceConversion
     * @param bool $preventConversion
     *
     * @return array|mixed|null
     * @deprecated 1.0.8
     * @deprecated 1.1.8
     */
    private function getDataObject($d = [], $forceConversion = false, $preventConversion = false)
    {
        if ($preventConversion) {
            return $d;
        }
        if ($this->convertObjects || $forceConversion) {
            /**
             * If json_decode and json_encode exists as function, do it the simple way.
             * http://php.net/manual/en/function.json-encode.php
             */
            if (function_exists('json_decode') && function_exists('json_encode')) {
                return json_decode(json_encode($d));
            }
            $newArray = [];
            if (is_array($d) || is_object($d)) {
                foreach ($d as $itemKey => $itemValue) {
                    if (is_array($itemValue)) {
                        $newArray[$itemKey] = (array)$this->getDataObject($itemValue);
                    } elseif (is_object($itemValue)) {
                        $newArray[$itemKey] = (object)(array)$this->getDataObject($itemValue);
                    } else {
                        $newArray[$itemKey] = $itemValue;
                    }
                }
            }
        } else {
            return $d;
        }

        return $newArray;
    }

    /**
     * ResponseObjectArrayParser. Translates a return-object to a clean array
     *
     * @param null $returnObject
     *
     * @return array
     * @deprecated 1.0.8
     * @deprecated 1.1.8
     */
    public function parseReturn($returnObject = null)
    {
        $hasGet = false;
        if (is_array($returnObject)) {
            $parsedArray = [];
            foreach ($returnObject as $arrayName => $objectArray) {
                $classMethods = get_class_methods($objectArray);
                if (is_array($classMethods)) {
                    foreach ($classMethods as $classMethodId => $classMethod) {
                        if (preg_match("/^get/i", $classMethod)) {
                            $hasGet = true;
                            $field = lcfirst(preg_replace("/^get/i", '', $classMethod));
                            $objectContent = $objectArray->$classMethod();
                            if (is_array($objectContent)) {
                                $parsedArray[$arrayName][$field] = $this->parseReturn($objectContent);
                            } else {
                                $parsedArray[$arrayName][$field] = $objectContent;
                            }
                        }
                    }
                }
            }
            /* Failver test */
            if (!$hasGet && !count($parsedArray)) {
                return $this->objectsIntoArray($returnObject);
            }

            return $parsedArray;
        }

        return []; /* Fail with empty array, if there is no recursive array  */
    }


    /// OTHER STUFF

    /**
     * Generates a unique "preferredId" out of a datestamp
     *
     * @param int $maxLength The maximum recommended length of a preferred id is currently 25. The order numbers may be shorter (the minimum length is 14, but in that case only the timestamp will be returned)
     * @param string $prefix Prefix to prepend at unique id level
     * @param bool $dualUniq Be paranoid and sha1-encrypt the first random uniq id first.
     *
     * @return string
     * @deprecated 1.0.2 Use getPreferredId directly instead
     * @deprecated 1.1.2 Use getPreferredId directly instead
     */
    public function generatePreferredId($maxLength = 25, $prefix = "", $dualUniq = true)
    {
        return $this->getPreferredId($maxLength, $prefix, $dualUniq);
    }

    /**
     * Check if there is a parameter send through externals, during a bookPayment
     *
     * @param string $parameter
     * @param array $externalParameters
     * @param bool $getValue
     *
     * @return bool|null
     *
     * @deprecated 1.0.1 Switching over to a more fresh API
     * @deprecated 1.1.1 Switching over to a more fresh API
     */
    private function bookHasParameter($parameter = '', $externalParameters = [], $getValue = false)
    {
        if (is_array($externalParameters)) {
            if (isset($externalParameters[$parameter])) {
                if ($getValue) {
                    return $externalParameters[$parameter];
                } else {
                    return true;
                }
            }
        }
        if ($getValue) {
            return null;
        }

        return false;
    }



    //// OLD CART GENERATING STUFF

    /**
     * Get extra parameters during a bookPayment
     *
     * @param string $parameter
     * @param array $externalParameters
     *
     * @return bool|null
     *
     * @deprecated 1.0.1 Switching over to a more fresh API
     * @deprecated 1.1.1 Switching over to a more fresh API
     */
    private function getBookParameter($parameter = '', $externalParameters = [])
    {
        return $this->bookHasParameter($parameter, $externalParameters);
    }

    /**
     * Prepare bookedCallbackUrl (Resurs Checkout)
     *
     * @param string $bookedCallbackUrl
     *
     * @deprecated 1.0.1 Never used, since we preferred to use the former callbacks instead (Recommended)
     * @deprecated 1.1.1 Never used, since we preferred to use the former callbacks instead (Recommended)
     */
    public function setBookedCallbackUrl($bookedCallbackUrl = "")
    {
        if (!empty($bookedCallbackUrl)) {
            $this->_bookedCallbackUrl = $bookedCallbackUrl;
        }
    }

    /**
     * Returns true if updateCart has interfered with the specRows (this is a good way to indicate if something went wrong with the handling)
     *
     * @return bool
     * @deprecated 1.0.1 Never used
     * @deprecated 1.1.1 Never used
     */
    public function isCartFixed()
    {
        return $this->bookPaymentCartFixed;
    }


    /////////////////////////// OUTDATED HOSTED FLOW METHODS

    /**
     * Book payment through hosted flow
     *
     * A bookPayment method that utilizes the data we get from a regular bookPayment and converts it to hostedFlow looking data.
     * Warning: This method is not yet finished.
     *
     * @param string $paymentMethodId
     * @param array $bookData
     * @param bool $getReturnedObjectAsStd Returning a stdClass instead of a Resurs class
     * @param bool $keepReturnObject Making EComPHP backwards compatible when a webshop still needs the complete object, not only $bookPaymentResult->return
     *
     * @return array|mixed|object
     * @throws \Exception
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private function bookPaymentHosted(
        $paymentMethodId = '',
        $bookData = [],
        $getReturnedObjectAsStd = true,
        $keepReturnObject = false
    ) {
        if ($this->current_environment == RESURS_ENVIRONMENTS::ENVIRONMENT_TEST) {
            $this->env_hosted_current = $this->env_hosted_test;
        } else {
            $this->env_hosted_current = $this->env_hosted_prod;
        }
        /**
         * Missing fields may be caused by a conversion of the simplified flow, so we'll try to fill that in here
         */
        if (empty($this->preferredId)) {
            $this->preferredId = $this->generatePreferredId();
        }
        if (!isset($bookData['paymentData']['paymentMethodId'])) {
            $bookData['paymentData']['paymentMethodId'] = $paymentMethodId;
        }
        if (!isset($bookData['paymentData']['preferredId']) || (isset($bookData['paymentData']['preferredId']) && empty($bookData['paymentData']['preferredId']))) {
            $bookData['paymentData']['preferredId'] = $this->preferredId;
        }
        /**
         * Some of the paymentData are not located in the same place as simplifiedShopFlow. This part takes care of that part.
         */
        if (isset($bookData['paymentData']['waitForFraudControl'])) {
            $bookData['waitForFraudControl'] = $bookData['paymentData']['waitForFraudControl'];
        }
        if (isset($bookData['paymentData']['annulIfFrozen'])) {
            $bookData['annulIfFrozen'] = $bookData['paymentData']['annulIfFrozen'];
        }
        if (isset($bookData['paymentData']['finalizeIfBooked'])) {
            $bookData['finalizeIfBooked'] = $bookData['paymentData']['finalizeIfBooked'];
        }
        $jsonBookData = $this->toJsonByType($bookData, RESURS_FLOW_TYPES::FLOW_HOSTED_FLOW);
        $this->simpleWebEngine = $this->createJsonEngine($this->env_hosted_current, $jsonBookData);
        $hostedErrorResult = $this->hostedError($this->simpleWebEngine);
        // Compatibility fixed for PHP 5.3
        if (!empty($hostedErrorResult)) {
            $hostedErrNo = $this->hostedErrNo($this->simpleWebEngine);
            throw new Exception(__FUNCTION__ . ": " . $hostedErrorResult, $hostedErrNo);
        }

        return $this->simpleWebEngine['parsed'];
    }

    /**
     * Return a string containing the last error for the current session. Returns null if no errors occured
     *
     * @param array $hostedObject
     *
     * @return string
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private function hostedError($hostedObject = [])
    {
        if (isset($hostedObject) && isset($hostedObject->exception) && isset($hostedObject->message)) {
            return $hostedObject->message;
        }

        return "";
    }

    /**
     * @param array $hostedObject
     *
     * @return string
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private function hostedErrNo($hostedObject = [])
    {
        if (isset($hostedObject) && isset($hostedObject->exception) && isset($hostedObject->status)) {
            return $hostedObject->status;
        }

        return "";
    }


    /// OMNI AND SIMPLIFIED RELATED

    /**
     * @param string $parameter
     * @param null $object
     *
     * @return object
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private function getBookedParameter($parameter = '', $object = null)
    {
        if (is_null($object) && is_object($this->lastBookPayment)) {
            $object = $this->lastBookPayment;
        }
        if (isset($object->return)) {
            $object = $object->return;
        }
        if (is_object($object) || is_array($object)) {
            if (isset($object->$parameter)) {
                return $object->$parameter;
            }
        }

        return null;
    }

    /**
     * Get the booked payment status
     *
     * @param null $lastBookPayment
     *
     * @return string
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    public function getBookedStatus($lastBookPayment = null)
    {
        $bookStatus = $this->getBookedParameter('bookPaymentStatus', $lastBookPayment);
        if (!empty($bookStatus)) {
            return $bookStatus;
        }

        return null;
    }

    /**
     * Get the booked payment id out of a payment
     *
     * @param null $lastBookPayment
     *
     * @return string
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    public function getBookedPaymentId($lastBookPayment = null)
    {
        $paymentId = $this->getBookedParameter('paymentId', $lastBookPayment);
        if (!empty($paymentId)) {
            return $paymentId;
        } else {
            $id = $this->getBookedParameter('id', $lastBookPayment);
            if (!empty($id)) {
                return $id;
            }
        }

        return null;
    }

    /**
     * Extract the signing url from the booking
     *
     * @param null $lastBookPayment
     *
     * @return string
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    public function getBookedSigningUrl($lastBookPayment = null)
    {
        return $this->getBookedParameter('signingUrl', $lastBookPayment);
    }

    /**
     * @param array $bookData
     * @param string $orderReference
     * @param int $omniCallType
     *
     * @return mixed
     * @throws \Exception
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    public function prepareOmniFrame(
        $bookData = [],
        $orderReference = "",
        $omniCallType = RESURS_CHECKOUT_CALL_TYPES::METHOD_PAYMENTS
    ) {
        if (empty($this->preferredId)) {
            $this->preferredId = $this->generatePreferredId();
        }
        if ($this->current_environment == RESURS_ENVIRONMENTS::ENVIRONMENT_TEST) {
            $this->env_omni_current = $this->env_omni_test;
        } else {
            $this->env_omni_current = $this->env_omni_prod;
        }
        if (empty($orderReference) && !isset($bookData['orderReference'])) {
            throw new Exception(__FUNCTION__ . ": You must proved omnicheckout with a orderReference", 500);
        }
        if (empty($orderReference) && isset($bookData['orderReference'])) {
            $orderReference = $bookData['orderReference'];
        }
        if ($omniCallType == RESURS_CHECKOUT_CALL_TYPES::METHOD_PAYMENTS) {
            $omniSubPath = "/checkout/payments/" . $orderReference;
        }
        if ($omniCallType == RESURS_CHECKOUT_CALL_TYPES::METHOD_CALLBACK) {
            $omniSubPath = "/callbacks/";
            throw new Exception(__FUNCTION__ . ": METHOD_CALLBACK for OmniCheckout is not yet implemented");
        }
        $omniReferenceUrl = $this->env_omni_current . $omniSubPath;
        try {
            $bookDataJson = $this->toJsonByType($bookData, RESURS_FLOW_TYPES::FLOW_RESURS_CHECKOUT);
            $this->simpleWebEngine = $this->createJsonEngine($omniReferenceUrl, $bookDataJson);
            $omniErrorResult = $this->omniError($this->simpleWebEngine);
            // Compatibility fixed for PHP 5.3
            if (!empty($omniErrorResult)) {
                $omniErrNo = $this->omniErrNo($this->simpleWebEngine);
                throw new Exception(__FUNCTION__ . ": " . $omniErrorResult, $omniErrNo);
            }
        } catch (\Exception $jsonException) {
            throw new Exception(__FUNCTION__ . ": " . $jsonException->getMessage(), $jsonException->getCode());
        }

        return $this->simpleWebEngine['parsed'];
    }

    /**
     * getOmniFrame: Only used to fix ocShop issues
     *
     * This can also be done directly from bookPayment by the use of booked payment result (response->html).
     *
     * @param array $omniResponse
     * @param bool $ocShopInternalHandle Make EComPHP will try to find and strip the script tag for the iframe resizer, if this is set to true
     *
     * @return mixed|null|string
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    public function getOmniFrame($omniResponse = [], $ocShopInternalHandle = false)
    {
        /*
		 * As we are using TorneLIB Curl Library, the Resurs Checkout iframe will be loaded properly without those checks.
		 */
        if (is_string($omniResponse) && !empty($omniResponse)) {
            if (isset($omniResponse)) {
                return $this->clearOcShop($this->omniFrame, $ocShopInternalHandle);
            }
        }

        return null;
    }

    /**
     * Remove script from the iframe-source
     *
     * Normally, ecommerce in OmniCheckout mode, returns an iframe-tag with a link to the payment handler.
     * ECommerce also appends a script-tag for which the iframe are resized from. Sometimes, we want to strip
     * this tag from the iframe and separate them. This is where we do that.
     *
     * @param string $htmlString
     * @param bool $ocShopInternalHandle
     *
     * @return mixed|string
     * @since 1.0.1
     * @since 1.1.1
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    private function clearOcShop($htmlString = "", $ocShopInternalHandle = false)
    {
        if ($ocShopInternalHandle) {
            preg_match_all("/\<script(.*?)\/script>/", $htmlString, $scriptStringArray);
            if (is_array($scriptStringArray) && isset($scriptStringArray[0][0]) && !empty($scriptStringArray[0][0])) {
                $scriptString = $scriptStringArray[0][0];
                preg_match_all("/src=\"(.*?)\"/", $scriptString, $getScriptSrc);
                if (is_array($getScriptSrc) && isset($getScriptSrc[1][0])) {
                    $this->ocShopScript = $getScriptSrc[1][0];
                }
            }
            $htmlString = preg_replace("/\<script(.*?)\/script>/", '', $htmlString);
        }

        return $htmlString;
    }


    /**
     * @param string $iframeString
     *
     * @return null
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    public function getIframeSrc($iframeString = "")
    {
        if (is_string($iframeString) && preg_match("/iframe src=\"(.*?)/i", $iframeString)) {
            preg_match_all("/iframe src=\"(.*?)\"/", $iframeString, $iframeData);
            if (isset($iframeData[1]) && isset($iframeData[1][0])) {
                return $iframeData[1][0];
            }
        }

        return null;
    }


    /**
     * Retrieve the correct omnicheckout url depending chosen environment
     *
     * @param int $EnvironmentRequest
     * @param bool $getCurrentIfSet
     *
     * @return string
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    public function getOmniUrl($EnvironmentRequest = RESURS_ENVIRONMENTS::ENVIRONMENT_TEST, $getCurrentIfSet = true)
    {
        return $this->getCheckoutUrl($EnvironmentRequest, $getCurrentIfSet);
    }

    /**
     * Return a string containing the last error for the current session. Returns null if no errors occured
     *
     * @param array $omniObject
     *
     * @return string
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private function omniError($omniObject = [])
    {
        if (isset($omniObject) && isset($omniObject->exception) && isset($omniObject->message)) {
            return $omniObject->message;
        } else {
            if (isset($omniObject) && isset($omniObject->error) && !empty($omniObject->error)) {
                return $omniObject->error;
            }
        }

        return "";
    }

    /**
     * @param array $omniObject
     *
     * @return string
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private function omniErrNo($omniObject = [])
    {
        if (isset($omniObject) && isset($omniObject->exception) && isset($omniObject->status)) {
            return $omniObject->status;
        } else {
            if (isset($omniObject) && isset($omniObject->error) && !empty($omniObject->error)) {
                if (isset($omniObject->status)) {
                    return $omniObject->status;
                }
            }
        }

        return "";
    }


    /**
     * @param $jsonData
     * @param string $paymentId
     *
     * @return mixed
     * @throws \Exception
     * @deprecated 1.0.8
     * @deprecated 1.1.8
     */
    public function omniUpdateOrder($jsonData, $paymentId = '')
    {
        return $this->setCheckoutFrameOrderLines($paymentId, $jsonData);
    }

    /**
     * Update the Checkout iframe
     *
     * @param string $paymentId
     * @param array $orderLines
     *
     * @return bool
     * @throws \Exception
     * @since 1.0.8
     * @since 1.1.8
     * @deprecated Use updateCheckoutOrderLines() instead
     */
    public function setCheckoutFrameOrderLines($paymentId = '', $orderLines = [])
    {
        return $this->parent->updateCheckoutOrderLines($paymentId, $orderLines);
    }



    /// SSL RELATED

    /**
     * Function to enable/disabled SSL Peer/Host verification, if problems occur with certificates
     *
     * @param bool|true $enabledFlag
     *
     * @deprecated 1.0.1
     * @deprecated 1.1.1
     */
    public function setSslVerify($enabledFlag = true)
    {
        $this->sslVerify = $enabledFlag;
    }

    /**
     * @return mixed
     */
    public function getSslVerify()
    {
        return $this->sslVerify;
    }

    /**
     * Allow older/obsolete PHP Versions (Follows the obsolete php versions rules - see the link for more information).
     *
     * @param bool $activate
     *
     * @link https://test.resurs.com/docs/x/TYNM#ECommercePHPLibrary-ObsoletePHPversions
     * @deprecated Removed in 1.2
     */
    public function setObsoletePhp($activate = false)
    {
        $this->allowObsoletePHP = $activate;
    }
}
