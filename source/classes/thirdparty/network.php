<?php

/**
 * Copyright 2018 Tomas Tornevall & Tornevall Networks
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Tornevall Networks netCurl library - Yet another http- and network communicator library
 * Each class in this library has its own version numbering to keep track of where the changes are. However, there is a
 * major version too.
 *
 * @package TorneLIB
 * @version 6.0.24RC1
 */

namespace Resursbank\RBEcomPHP;

// Library Release Information
if (!defined('NETCURL_RELEASE')) {
    define('NETCURL_RELEASE', '6.0.24RC1');
}
if (!defined('NETCURL_MODIFY')) {
    define('NETCURL_MODIFY', '20180822');
}
if (!defined('TORNELIB_NETCURL_RELEASE')) {
    // Compatibility constant
    define('TORNELIB_NETCURL_RELEASE', NETCURL_RELEASE);
}
if (!defined('NETCURL_SKIP_AUTOLOAD')) {
    define('NETCURL_CLASS_EXISTS_AUTOLOAD', true);
} else {
    define('NETCURL_CLASS_EXISTS_AUTOLOAD', false);
    // If the autoloader prevention is set for this module, we probably want to do the same for our
    // relative CRYPTO/IO
    if (!defined('CRYPTO_SKIP_AUTOLOAD')) {
        define('CRYPTO_SKIP_AUTOLOAD', true);
    }
    if (!defined('IO_SKIP_AUTOLOAD')) {
        define('IO_SKIP_AUTOLOAD', true);
    }
}

if (defined('NETCURL_REQUIRE')) {
    if (!defined('NETCURL_REQUIRE_OPERATOR')) {
        define('NETCURL_REQUIRE_OPERATOR', '==');
    }
    define('NETCURL_ALLOW_AUTOLOAD',
        version_compare(NETCURL_RELEASE, NETCURL_REQUIRE, NETCURL_REQUIRE_OPERATOR) ? true : false);
} else {
    if (!defined('NETCURL_ALLOW_AUTOLOAD')) {
        define('NETCURL_ALLOW_AUTOLOAD', true);
    }
}

if (file_exists(__DIR__ . '/../../vendor/autoload.php') && (defined('NETCURL_ALLOW_AUTOLOAD') && NETCURL_ALLOW_AUTOLOAD === true)) {
    require_once(__DIR__ . '/../../vendor/autoload.php');
}

if (!interface_exists('NETCURL_DRIVERS_INTERFACE', NETCURL_CLASS_EXISTS_AUTOLOAD) && !interface_exists('TorneLIB\NETCURL_DRIVERS_INTERFACE', NETCURL_CLASS_EXISTS_AUTOLOAD)) {
	interface NETCURL_DRIVERS_INTERFACE {

		public function __construct( $parameters = null );

		public function setDriverId( $driverId = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET );

		public function setParameters( $parameters = array() );

		public function setContentType( $setContentTypeString = 'application/json; charset=utf-8' );

		public function getContentType();

		public function setAuthentication( $Username = null, $Password = null, $AuthType = NETCURL_AUTH_TYPES::AUTHTYPE_BASIC );

		public function getAuthentication();

		public function getWorker();

		public function getRawResponse();

		public function getStatusCode();

		public function getStatusMessage();

		public function executeNetcurlRequest( $url = '', $postData = array(), $postMethod = NETCURL_POST_METHODS::METHOD_GET, $postDataType = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET );

	}
}
if ( ! class_exists( 'NETCURL_DRIVER_GUZZLEHTTP', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\NETCURL_DRIVER_GUZZLEHTTP', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class NETCURL_DRIVER_GUZZLEHTTP Network communications driver detection
     *
     * Inspections for classes and namespaces is ignored as they are dynamically loaded when they do exist.
	 *
	 * @package TorneLIB
	 */
	class NETCURL_DRIVER_GUZZLEHTTP implements NETCURL_DRIVERS_INTERFACE {

		/** @var NETCURL_NETWORK_DRIVERS $DRIVER_ID */
		private $DRIVER_ID = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET;

		/** @var array Inbound parameters in the format array, object or whatever this driver takes */
		private $PARAMETERS = array();

        /** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedNamespaceInspection */
        /** @var \GuzzleHttp\Client $DRIVER The class for where everything happens */
		private $DRIVER;

		/** @var MODULE_NETWORK $NETWORK Network driver for using exceptions, etc */
		private $NETWORK;

		/** @var string $POST_CONTENT_TYPE Content type */
		private $POST_CONTENT_TYPE = '';

		/** @var string $REQUEST_URL */
		private $REQUEST_URL = '';

		/** @var NETCURL_POST_METHODS */
		private $POST_METHOD = NETCURL_POST_METHODS::METHOD_GET;

		/** @var array $POST_DATA ... or string, or object, etc */
		private $POST_DATA;

		/** @var NETCURL_POST_DATATYPES */
		private $POST_DATA_TYPE = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET;

		/** @var $WORKER_DATA */
		private $WORKER_DATA = array();

		/** @var int $HTTP_STATUS */
		private $HTTP_STATUS = 0;

		/** @var string $HTTP_MESSAGE */
		private $HTTP_MESSAGE = '';

		/** @var bool $HAS_AUTHENTICATION Set if there's authentication configured */
		private $HAS_AUTHENTICATION = false;

		/**
		 * @var array $POST_AUTH_DATA
		 */
		private $POST_AUTH_DATA = array();

		/** @var string $RESPONSE_RAW */
		private $RESPONSE_RAW = '';

		/** @var array $GUZZLE_POST_OPTIONS Post options for Guzzle */
		private $GUZZLE_POST_OPTIONS;


		public function __construct( $parameters = null ) {
			$this->NETWORK = new MODULE_NETWORK();
			if ( ! is_null( $parameters ) ) {
				$this->setParameters( $parameters );
			}
		}

		public function setDriverId( $driverId = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET ) {
			$this->DRIVER_ID = $driverId;
		}

		public function setParameters( $parameters = array() ) {
			$this->PARAMETERS = $parameters;
		}


		private function initializeClass() {
			if ( $this->DRIVER_ID == NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP ) {
				if ( class_exists( 'GuzzleHttp\Client', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
                    /** @noinspection PhpUndefinedClassInspection */
                    /** @noinspection PhpUndefinedNamespaceInspection */
                    $this->DRIVER = new \GuzzleHttp\Client;
				}
			} else if ( $this->DRIVER_ID === NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP_STREAM ) {
				if ( class_exists( 'GuzzleHttp\Handler\StreamHandler', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
                    /** @noinspection PhpUndefinedClassInspection */
                    /** @noinspection PhpUndefinedNamespaceInspection */
                    /** @var \GuzzleHttp\Handler\StreamHandler $streamHandler */
					$streamHandler = new \GuzzleHttp\Handler\StreamHandler();
                    /** @noinspection PhpUndefinedClassInspection */
                    /** @noinspection PhpUndefinedNamespaceInspection */
                    /** @var \GuzzleHttp\Client */
					$this->DRIVER = new \GuzzleHttp\Client( array( 'handler' => $streamHandler ) );
				}
			}
		}

		public function getContentType() {
			return $this->POST_CONTENT_TYPE;
		}

		public function setContentType( $setContentTypeString = 'application/json; charset=utf-8' ) {
			$this->POST_CONTENT_TYPE = $setContentTypeString;
		}

		/**
		 * @param null $Username
		 * @param null $Password
		 * @param int $AuthType
		 */
		public function setAuthentication( $Username = null, $Password = null, $AuthType = NETCURL_AUTH_TYPES::AUTHTYPE_BASIC ) {
			$this->POST_AUTH_DATA['Username'] = $Username;
			$this->POST_AUTH_DATA['Password'] = $Password;
			$this->POST_AUTH_DATA['Type']     = $AuthType;
		}

		/**
		 * @return array
		 */
		public function getAuthentication() {
			return $this->POST_AUTH_DATA;
		}

		/**
		 * @return array
		 */
		public function getWorker() {
			return $this->WORKER_DATA;
		}

		/**
		 * @return int
		 */
		public function getStatusCode() {
			return $this->HTTP_STATUS;
		}

		/**
		 * @return string
		 */
		public function getStatusMessage() {
			return $this->HTTP_MESSAGE;
		}

		/**
		 * Guzzle Renderer
		 * @return $this|NETCURL_DRIVER_GUZZLEHTTP
		 * @throws \Exception
		 */
		private function getGuzzle() {
            /** @noinspection PhpUndefinedClassInspection */
            /** @noinspection PhpUndefinedNamespaceInspection */
            /** @var $gResponse \GuzzleHttp\Psr7\Response */
			$gResponse          = null;
			$this->RESPONSE_RAW = null;
			$gBody              = null;

			$this->GUZZLE_POST_OPTIONS = $this->getPostOptions();

			$gRequest = $this->getGuzzleRequest();
			if ( ! is_null( $gRequest ) ) {
				$this->getRenderedGuzzleResponse( $gRequest );
			} else {
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " streams for guzzle is probably missing as I can't find the request method in the current class", $this->NETWORK->getExceptionCode( 'NETCURL_GUZZLESTREAM_MISSING' ) );
			}

			return $this;
		}

        /**
         * @param $gRequest
         *
         * @return NETCURL_DRIVER_GUZZLEHTTP
         * @throws \Exception
         */
		private function getRenderedGuzzleResponse($gRequest) {
			$this->WORKER_DATA  = array( 'worker' => $this->DRIVER, 'request' => $gRequest );
			if (method_exists($gRequest, 'getHeaders')) {
                $gHeaders           = $gRequest->getHeaders();
                /** @noinspection PhpUndefinedMethodInspection */
                $gBody              = $gRequest->getBody()->getContents();
                /** @noinspection PhpUndefinedMethodInspection */
                $this->HTTP_STATUS  = $gRequest->getStatusCode();
                /** @noinspection PhpUndefinedMethodInspection */
                $this->HTTP_MESSAGE = $gRequest->getReasonPhrase();
                /** @noinspection PhpUndefinedMethodInspection */
                $this->RESPONSE_RAW .= "HTTP/" . $gRequest->getProtocolVersion() . " " . $this->HTTP_STATUS . " " . $this->HTTP_MESSAGE . "\r\n";
                $this->RESPONSE_RAW .= "X-NetCurl-ClientDriver: " . $this->DRIVER_ID . "\r\n";
                if (is_array($gHeaders)) {
                    foreach ($gHeaders as $hParm => $hValues) {
                        $this->RESPONSE_RAW .= $hParm . ": " . implode("\r\n", $hValues) . "\r\n";
                    }
                }
                $this->RESPONSE_RAW .= "\r\n" . $gBody;

                // Prevent problems during authorization. Unsupported media type checks defaults to application/json
                if ($this->HAS_AUTHENTICATION && $this->HTTP_STATUS == 415) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $contentTypeRequest = $gRequest->getHeader('content-type');
                    if (empty($contentTypeRequest)) {
                        $this->setContentType();
                    } else {
                        $this->setContentType($contentTypeRequest);
                    }

                    return $this->getGuzzle();
                }
            } else {
                throw new \Exception( NETCURL_CURL_CLIENTNAME . "-".__FUNCTION__." exception: Guzzle driver missing proper methods like getHeaders(), can not render response", $this->NETWORK->getExceptionCode( 'NETCURL_GUZZLE_RESPONSE_EXCEPTION' ) );
            }
			return $this;
		}

		/**
		 * Render postdata
		 */
		private function getPostOptions() {
			$postOptions            = array();
			$postOptions['headers'] = array();
			$contentType            = $this->getContentType();

			if ( $this->POST_DATA_TYPE == NETCURL_POST_DATATYPES::DATATYPE_JSON ) {
				$postOptions['headers']['Content-Type'] = 'application/json; charset=utf-8';
				if ( is_string( $this->POST_DATA ) ) {
					$jsonPostData = @json_decode( $this->POST_DATA );
					if ( is_object( $jsonPostData ) ) {
						$this->POST_DATA = $jsonPostData;
					}
				}
				$postOptions['json'] = $this->POST_DATA;
			} else {
				if ( is_array( $this->POST_DATA ) ) {
					$postOptions['form_params'] = $this->POST_DATA;
				}
			}

			if ( isset( $this->POST_AUTH_DATA['Username'] ) ) {
				$this->HAS_AUTHENTICATION = true;
				if ( $this->POST_AUTH_DATA['Type'] == NETCURL_AUTH_TYPES::AUTHTYPE_BASIC ) {
					$postOptions['headers']['Accept'] = '*/*';
					if ( ! empty( $contentType ) ) {
						$postOptions['headers']['Content-Type'] = $contentType;
					}
					$postOptions['auth'] = array(
						$this->POST_AUTH_DATA['Username'],
						$this->POST_AUTH_DATA['Password']
					);
				}
			}
			return $postOptions;
		}

		/** @noinspection PhpUndefinedClassInspection */
        /** @noinspection PhpUndefinedNamespaceInspection */
        /**
         * @return \Psr\Http\Message\ResponseInterface
         * @throws \Exception
         */
        private function getGuzzleRequest() {
            /** @noinspection PhpUndefinedClassInspection */
            /** @noinspection PhpUndefinedNamespaceInspection */
            /** @var \Psr\Http\Message\ResponseInterface $gRequest */
			$gRequest = null;
			if ( method_exists( $this->DRIVER, 'request' ) ) {
				if ( $this->POST_METHOD == NETCURL_POST_METHODS::METHOD_GET ) {
					$gRequest = $this->DRIVER->request( 'GET', $this->REQUEST_URL, $this->GUZZLE_POST_OPTIONS );
				} else if ( $this->POST_METHOD == NETCURL_POST_METHODS::METHOD_POST ) {
					$gRequest = $this->DRIVER->request( 'POST', $this->REQUEST_URL, $this->GUZZLE_POST_OPTIONS );
				} else if ( $this->POST_METHOD == NETCURL_POST_METHODS::METHOD_PUT ) {
					$gRequest = $this->DRIVER->request( 'PUT', $this->REQUEST_URL, $this->GUZZLE_POST_OPTIONS );
				} else if ( $this->POST_METHOD == NETCURL_POST_METHODS::METHOD_DELETE ) {
					$gRequest = $this->DRIVER->request( 'DELETE', $this->REQUEST_URL, $this->GUZZLE_POST_OPTIONS );
				} else if ( $this->POST_METHOD == NETCURL_POST_METHODS::METHOD_HEAD ) {
					$gRequest = $this->DRIVER->request( 'HEAD', $this->REQUEST_URL, $this->GUZZLE_POST_OPTIONS );
				}
			} else {
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " streams for guzzle is probably missing as I can't find the request method in the current class", $this->NETWORK->getExceptionCode( 'NETCURL_GUZZLESTREAM_MISSING' ) );
			}
			return $gRequest;
		}

		/**
		 * @return string
		 */
		public function getRawResponse() {
			return $this->RESPONSE_RAW;
		}

        /**
         * @param string $url
         * @param array  $postData
         * @param int    $postMethod
         * @param int    $postDataType
         *
         * @return NETCURL_DRIVER_GUZZLEHTTP
         * @throws \Exception
         */
        public function executeNetcurlRequest( $url = '', $postData = array(), $postMethod = NETCURL_POST_METHODS::METHOD_GET, $postDataType = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$this->REQUEST_URL    = $url;
			$this->POST_DATA      = $postData;
			$this->POST_METHOD    = $postMethod;
			$this->POST_DATA_TYPE = $postDataType;

			$this->initializeClass();
			if ( is_null( $this->DRIVER ) ) {
				throw new \Exception( $this->ModuleName . " setDriverException: Classes for GuzzleHttp does not exists (DriverIdMissing: " . $this->DRIVER_ID . ")", $this->NETWORK->getExceptionCode( 'NETCURL_EXTERNAL_DRIVER_MISSING' ) );
			}

			return $this->getGuzzle();
		}

	}
}
if ( ! class_exists( 'NETCURL_DRIVER_WORDPRESS', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\NETCURL_DRIVER_WORDPRESS', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class NETCURL_DRIVERS Network communications driver detection
	 *
	 * @package TorneLIB
	 * @since 6.0.20
	 */
	class NETCURL_DRIVER_WORDPRESS implements NETCURL_DRIVERS_INTERFACE {

		/** @var MODULE_NETWORK $NETWORK */
		private $NETWORK;

		/** @var MODULE_IO */
		private $IO;

		/** @var NETCURL_NETWORK_DRIVERS $DRIVER_ID */
		private $DRIVER_ID = NETCURL_NETWORK_DRIVERS::DRIVER_WORDPRESS;

		/** @var array Inbound parameters in the format array, object or whatever this driver takes */
		private $PARAMETERS = array();

        /** @noinspection PhpUndefinedClassInspection */
        /** @var \WP_Http $DRIVER When this class exists, it should be referred to WP_Http */
		private $DRIVER;

		/** @var \stdClass $TRANSPORT Wordpress transport layer */
		private $TRANSPORT;

		/** @var string $POST_CONTENT_TYPE Content type */
		private $POST_CONTENT_TYPE = '';

		/**
		 * @var array $POST_AUTH_DATA
		 */
		private $POST_AUTH_DATA = array();

		/** @var $WORKER_DATA */
		private $WORKER_DATA = array();

		/** @var int $HTTP_STATUS */
		private $HTTP_STATUS = 0;

		/** @var string $HTTP_MESSAGE */
		private $HTTP_MESSAGE = '';

		/** @var string $RESPONSE_RAW */
		private $RESPONSE_RAW = '';

		/** @var string $REQUEST_URL */
		private $REQUEST_URL = '';

		/** @var NETCURL_POST_METHODS */
		private $POST_METHOD = NETCURL_POST_METHODS::METHOD_GET;

		/** @var array $POST_DATA ... or string, or object, etc */
		private $POST_DATA;

		/** @var NETCURL_POST_DATATYPES */
		private $POST_DATA_TYPE = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET;


		public function __construct( $parameters = null ) {
			$this->NETWORK = new MODULE_NETWORK();
			$this->IO      = new MODULE_IO();
		}

		public function setDriverId( $driverId = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET ) {
			$this->DRIVER_ID = $driverId;
		}

		public function setParameters( $parameters = array() ) {
			$this->PARAMETERS = $parameters;
		}

		public function setContentType( $setContentTypeString = 'application/json; charset=utf-8' ) {
			$this->POST_CONTENT_TYPE = $setContentTypeString;
		}

		public function getContentType() {
			return $this->POST_CONTENT_TYPE;
		}

		/**
		 * @param null $Username
		 * @param null $Password
		 * @param int $AuthType
		 */
		public function setAuthentication( $Username = null, $Password = null, $AuthType = NETCURL_AUTH_TYPES::AUTHTYPE_BASIC ) {
			$this->POST_AUTH_DATA['Username'] = $Username;
			$this->POST_AUTH_DATA['Password'] = $Password;
			$this->POST_AUTH_DATA['Type']     = $AuthType;
		}

		public function getAuthentication() {
			return $this->POST_AUTH_DATA;
		}

		public function getWorker() {
			return $this->WORKER_DATA;
		}

		public function getRawResponse() {
			return $this->RESPONSE_RAW;
		}

		public function getStatusCode() {
			return $this->HTTP_STATUS;
		}

		public function getStatusMessage() {
			return $this->HTTP_MESSAGE;
		}

        /**
         * @throws \Exception
         */
        private function initializeClass()
        {
            /** @noinspection PhpUndefinedClassInspection */
            $this->DRIVER = new \WP_Http();
            if (method_exists($this->DRIVER, '_get_first_available_transport')) {
                $this->TRANSPORT = $this->DRIVER->_get_first_available_transport(array());
            }
            if (empty($this->TRANSPORT)) {
                throw new \Exception(NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: Could not find any available transport for WordPress Driver",
                    $this->NETWORK->getExceptionCode('NETCURL_WP_TRANSPORT_ERROR'));
            }
        }

        /**
         * @return $this
         * @throws \Exception
         */
		private function getWp() {
			$postThis = array( 'body' => $this->POST_DATA );
			if ( $this->POST_DATA_TYPE == NETCURL_POST_DATATYPES::DATATYPE_JSON ) {
				$postThis['headers'] = array( "content-type" => "application-json" );
				$postThis['body']    = $this->IO->renderJson( $this->POST_DATA );
			}

			$wpResponse = $this->getWpResponse($postThis);
            /** @noinspection PhpUndefinedClassInspection */

            /** @var $httpResponse \WP_HTTP_Requests_Response */
			$httpResponse = $wpResponse['http_response'];

			if (method_exists($httpResponse, 'get_response_object')) {
                /** @noinspection PhpUndefinedClassInspection */
                /** @var $httpReponseObject \Requests_Response */
                $httpResponseObject = $httpResponse->get_response_object();
                $this->RESPONSE_RAW = isset($httpResponseObject->raw) ? $httpResponseObject->raw : null;
            } else {
                throw new \Exception(NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: Wordpress driver seem to miss get_response_object",
                    $this->NETWORK->getExceptionCode('NETCURL_WP_REQUEST_ERROR'));
            }

			return $this;
		}

        /**
         * @param $postData
         *
         * @return null
         */
        private function getWpResponse($postData)
        {
            $wpResponse = null;
            if ($this->POST_METHOD == NETCURL_POST_METHODS::METHOD_HEAD) {
                if (method_exists($this->DRIVER, 'head')) {
                    $wpResponse = $this->DRIVER->head($this->REQUEST_URL, $postData);
                }
            } elseif ($this->POST_METHOD == NETCURL_POST_METHODS::METHOD_POST) {
                if (method_exists($this->DRIVER, 'post')) {
                    $wpResponse = $this->DRIVER->post($this->REQUEST_URL, $postData);
                }
            } elseif ($this->POST_METHOD == NETCURL_POST_METHODS::METHOD_REQUEST) {
                if (method_exists($this->DRIVER, 'request')) {
                    $wpResponse = $this->DRIVER->request($this->REQUEST_URL, $postData);
                }
            } else {
                if (method_exists($this->DRIVER, 'get')) {
                    $wpResponse = $this->DRIVER->get($this->REQUEST_URL, $postData);
                }
            }

            return $wpResponse;
        }

        /**
         * @param string $url
         * @param array  $postData
         * @param int    $postMethod
         * @param int    $postDataType
         *
         * @return NETCURL_DRIVER_WORDPRESS
         * @throws \Exception
         */
		public function executeNetcurlRequest( $url = '', $postData = array(), $postMethod = NETCURL_POST_METHODS::METHOD_GET, $postDataType = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$this->REQUEST_URL    = $url;
			$this->POST_DATA      = $postData;
			$this->POST_METHOD    = $postMethod;
			$this->POST_DATA_TYPE = $postDataType;

			$this->initializeClass();

			return $this->getWp();
		}
	}
}
if ( ! class_exists( 'NETCURL_PARSER', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\NETCURL_PARSER', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class NETCURL_PARSER Network communications driver detection
	 *
	 * @package TorneLIB
	 */
	class NETCURL_PARSER {

		private $PARSE_CONTAINER = '';
		private $PARSE_CONTENT_TYPE = '';
		private $PARSE_CONTENT_OUTPUT = '';

		/**
		 * @var bool
		 */
		private $NETCURL_CONTENT_IS_DOMCONTENT = false;

		/**
		 * Do not include Dom content in the basic parser (default = true, as it might destroy output data in legacy products)
		 *
		 * @var bool $NETCURL_PROHIBIT_DOMCONTENT_PARSE
		 * @since 6.0.3
		 */
		private $NETCURL_PROHIBIT_DOMCONTENT_PARSE = true;


		/** @var MODULE_IO $IO */
		private $IO;

		/** @var MODULE_NETWORK */
		private $NETWORK;

		/**
		 * NETCURL_PARSER constructor.
		 *
		 * @param string $htmlContent
		 * @param string $contentType
		 * @param array  $flags
		 *
		 * @throws \Exception
		 * @since 6.0.0
		 */
		public function __construct( $htmlContent = '', $contentType = '', $flags = array() ) {
			$this->NETWORK = new MODULE_NETWORK();
			$this->IO      = new MODULE_IO();

			if (isset($flags['NETCURL_PROHIBIT_DOMCONTENT_PARSE'])) {
				$this->NETCURL_PROHIBIT_DOMCONTENT_PARSE = $flags['NETCURL_PROHIBIT_DOMCONTENT_PARSE'];
			}

			/*if (is_null($this->IO)) {
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " is missing MODULE_IO for rendering post data content", $this->NETWORK->getExceptionCode( 'NETCURL_PARSE_XML_FAILURE' ) );
			}*/
			$this->PARSE_CONTAINER      = $htmlContent;
			$this->PARSE_CONTENT_TYPE   = $contentType;
			$this->PARSE_CONTENT_OUTPUT = $this->getContentByTest();
		}

        /**
         * @param bool $returnAsIs
         *
         * @return null|string
         * @since 6.0.0
         */
		public function getContentByJson( $returnAsIs = false ) {
			try {
				if ( $returnAsIs ) {
					return $this->IO->getFromJson( $this->PARSE_CONTAINER );
				}

				return $this->getNull( $this->IO->getFromJson( $this->PARSE_CONTAINER ) );
			} catch ( \Exception $e ) {

			}

			return null;
		}

		/**
		 * Enable/disable the parsing of Dom content
		 *
		 * @param bool $domContentProhibit
		 *
		 * @since 6.0.3
		 */
		public function setDomContentParser( $domContentProhibit = false ) {
			$this->NETCURL_PROHIBIT_DOMCONTENT_PARSE = $domContentProhibit;
		}

		/**
		 * Get the status of dom content parser mode
		 *
		 * @return bool
		 * @since 6.0.3
		 */
		public function getDomContentParser() {
			return $this->NETCURL_PROHIBIT_DOMCONTENT_PARSE;
		}

		/**
		 * @param bool $returnAsIs
		 *
		 * @return null|string
		 * @since 6.0.0
		 */
		public function getContentByXml( $returnAsIs = false ) {
			try {
				if ( $returnAsIs ) {
					return $this->IO->getFromXml( $this->PARSE_CONTAINER );
				}

				return $this->getNull( $this->IO->getFromXml( $this->PARSE_CONTAINER ) );
			} catch ( \Exception $e ) {

			}

			return null;
		}

		/**
		 * @param bool $returnAsIs
		 *
		 * @return null|string
		 * @since 6.0.0
		 */
		public function getContentByYaml( $returnAsIs = false ) {
			try {
				if ( $returnAsIs ) {
					$this->IO->getFromYaml( $this->PARSE_CONTAINER );
				}

				return $this->getNull( $this->IO->getFromYaml( $this->PARSE_CONTAINER ) );
			} catch ( \Exception $e ) {

			}

			return null;
		}

		/**
		 * @param bool $returnAsIs
		 *
		 * @return null|string
		 * @since 6.0.0
		 */
		public function getContentBySerial( $returnAsIs = false ) {
			try {
				if ( $returnAsIs ) {
					return $this->IO->getFromSerializerInternal( $this->PARSE_CONTAINER );
				}

				return $this->getNull( $this->IO->getFromSerializerInternal( $this->PARSE_CONTAINER ) );
			} catch ( \Exception $e ) {

			}

			return null;
		}

		/**
		 * @param string $testData
		 *
		 * @return null|string
		 * @since 6.0.0
		 */
		private function getNull( $testData = '' ) {
			if ( is_array( $testData ) || is_object( $testData ) ) {
				return $testData;
			}

			return empty( $testData ) ? null : $testData;
		}

        /**
         * @return array|null|string
         * @throws \Exception
         * @since 6.0.0
         */
		private function getContentByTest() {
			$returnNonNullValue = null;

			if ( ! is_null( $respond = $this->getContentByJson() ) ) {
				$returnNonNullValue = $respond;
			} else if ( ! is_null( $respond = $this->getContentBySerial() ) ) {
				$returnNonNullValue = $respond;
			} else if ( ! is_null( $respond = $this->getContentByXml() ) ) {
				$returnNonNullValue = $respond;
			} else if ( ! is_null( $respond = $this->getContentByYaml() ) ) {
				$returnNonNullValue = $respond;
			} else if ( ! $this->NETCURL_PROHIBIT_DOMCONTENT_PARSE && ! is_null( $response = $this->getDomElements() ) ) {
				return $response;
			}

			return $returnNonNullValue;
		}


		/**
		 * Experimental: Convert DOMDocument to an array
		 *
		 * @param array  $childNode
		 * @param string $getAs
		 *
		 * @return array
		 * @since 6.0.0
		 */
		private function getChildNodes( $childNode = array(), $getAs = '' ) {
			$childNodeArray      = array();
			$childAttributeArray = array();
			$childIdArray        = array();
			$returnContext       = "";
			if ( is_object( $childNode ) ) {
				/** @var \DOMElement $nodeItem */
                foreach ( $childNode as $nodeItem ) {
					if ( is_object( $nodeItem ) ) {
						if ( isset( $nodeItem->tagName ) ) {
							if ( strtolower( $nodeItem->tagName ) == "title" ) {
								$elementData['pageTitle'] = $nodeItem->nodeValue;
							}

							$elementData            = array( 'tagName' => $nodeItem->tagName );
							$elementData['id']      = $nodeItem->getAttribute( 'id' );
							$elementData['name']    = $nodeItem->getAttribute( 'name' );
							$elementData['context'] = $nodeItem->nodeValue;
							/** @since 6.0.20 Saving innerhtml */
							$elementData['innerhtml'] = $nodeItem->ownerDocument->saveHTML( $nodeItem );
							if ( $nodeItem->hasChildNodes() ) {
								$elementData['childElement'] = $this->getChildNodes( $nodeItem->childNodes, $getAs );
							}
							$identificationName = $nodeItem->tagName;
							if ( empty( $identificationName ) && ! empty( $elementData['name'] ) ) {
								$identificationName = $elementData['name'];
							}
							if ( empty( $identificationName ) && ! empty( $elementData['id'] ) ) {
								$identificationName = $elementData['id'];
							}
							$childNodeArray[] = $elementData;
							if ( ! isset( $childAttributeArray[ $identificationName ] ) ) {
								$childAttributeArray[ $identificationName ] = $elementData;
							} else {
								$childAttributeArray[ $identificationName ][] = $elementData;
							}

							$idNoName = $nodeItem->tagName;
							// Forms without id namings will get the tagname. This will open up for reading forms and other elements without id's.
							// NOTE: If forms are not tagged with an id, the form will not render "properly" and the form fields might pop outside the real form.
							if ( empty( $elementData['id'] ) ) {
								$elementData['id'] = $idNoName;
							}

							if ( ! empty( $elementData['id'] ) ) {
								if ( ! isset( $childIdArray[ $elementData['id'] ] ) ) {
									$childIdArray[ $elementData['id'] ] = $elementData;
								} else {
									$childIdArray[ $elementData['id'] ][] = $elementData;
								}
							}
						}
					}
				}
			}
			if ( empty( $getAs ) || $getAs == "domnodes" ) {
				$returnContext = $childNodeArray;
			} else if ( $getAs == "tagnames" ) {
				$returnContext = $childAttributeArray;
			} else if ( $getAs == "id" ) {
				$returnContext = $childIdArray;
			}

			return $returnContext;
		}

		/**
		 * @return bool
		 * @since 6.0.1
		 */
		public function getIsDomContent() {
			return $this->NETCURL_CONTENT_IS_DOMCONTENT;
		}

		/**
		 * @return array
		 * @throws \Exception
		 * @since 6.0.0
		 */
		private function getDomElements() {
			$domContent                 = array();
			$domContent['ByNodes']      = array();
			$domContent['ByClosestTag'] = array();
			$domContent['ById']         = array();
			$hasContent                 = false;
			if ( class_exists( 'DOMDocument', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
				if ( ! empty( $this->PARSE_CONTAINER ) ) {
					$DOM = new \DOMDocument();
					libxml_use_internal_errors( true );
					$DOM->loadHTML( $this->PARSE_CONTAINER );
					if ( isset( $DOM->childNodes->length ) && $DOM->childNodes->length > 0 ) {
						$this->NETCURL_CONTENT_IS_DOMCONTENT = true;

						$elementsByTagName = $DOM->getElementsByTagName( '*' );
						$childNodeArray    = $this->getChildNodes( $elementsByTagName );
						$childTagArray     = $this->getChildNodes( $elementsByTagName, 'tagnames' );
						$childIdArray      = $this->getChildNodes( $elementsByTagName, 'id' );
						if ( is_array( $childNodeArray ) && count( $childNodeArray ) ) {
							$domContent['ByNodes'] = $childNodeArray;
							$hasContent            = true;
						}
						if ( is_array( $childTagArray ) && count( $childTagArray ) ) {
							$domContent['ByClosestTag'] = $childTagArray;
						}
						if ( is_array( $childIdArray ) && count( $childIdArray ) ) {
							$domContent['ById'] = $childIdArray;
						}
					}
				}
			} else {
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " HtmlParse exception: Can not parse DOMDocuments without the DOMDocuments class", $this->NETWORK->getExceptionCode( "NETCURL_DOMDOCUMENT_CLASS_MISSING" ) );
			}

			if ( ! $hasContent ) {
				return null;
			}

			return $domContent;
		}


		public function getParsedResponse() {
			return $this->PARSE_CONTENT_OUTPUT;
		}
	}
}
if ( ! class_exists( 'NETCURL_DRIVER_CONTROLLER', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\NETCURL_DRIVER_CONTROLLER', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class NETCURL_DRIVERS Network communications driver detection
	 *
	 * @package TorneLIB
	 * @since   6.0.20
	 */
	class NETCURL_DRIVER_CONTROLLER {

		function __construct() {
			$this->NETWORK = new MODULE_NETWORK();
			$this->getDisabledFunctions();
			$this->getInternalDriver();
			$this->getAvailableClasses();
		}

		/**
		 * Class drivers supported by NETCURL
		 *
		 * @var array
		 */
		private $DRIVERS_SUPPORTED = array(
			'GuzzleHttp\Client'                => NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP,
			'GuzzleHttp\Handler\StreamHandler' => NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP_STREAM,
			'WP_Http'                          => NETCURL_NETWORK_DRIVERS::DRIVER_WORDPRESS
		);

		private $DRIVERS_BRIDGED = array(
			'GuzzleHttp\Client'                => 'NETCURL_DRIVER_GUZZLEHTTP',
			'GuzzleHttp\Handler\StreamHandler' => 'NETCURL_DRIVER_GUZZLEHTTP',
			'WP_Http'                          => 'NETCURL_DRIVER_WORDPRESS'
		);

/*		private $DRIVERS_STREAMABLE = array(
			'GuzzleHttp\Handler\StreamHandler' => 'NETCURL_DRIVER_GUZZLEHTTP'
		);*/

		/** @var array $DRIVERS_AVAILABLE */
		private $DRIVERS_AVAILABLE = array();

		/** @var array $FUNCTIONS_DISABLED List of functions disabled via php.ini, arrayed */
		private $FUNCTIONS_DISABLED = array();

		/** @var NETCURL_DRIVERS_INTERFACE $DRIVER Preloaded driver when setDriver is used */
		private $DRIVER = null;

		/** @var int $DRIVER_ID */
		private $DRIVER_ID = 0;

		/**
		 * @var MODULE_NETWORK $NETWORK Handles exceptions
		 */
		private $NETWORK;

		/**
		 * @return string
		 */
		public function getDisabledFunctions() {
			$disabledFunctions        = @ini_get( 'disable_functions' );
			$disabledArray            = array_map( "trim", explode( ",", $disabledFunctions ) );
			$this->FUNCTIONS_DISABLED = is_array( $disabledArray ) ? $disabledArray : array();

			return $this->FUNCTIONS_DISABLED;
		}

		/**
		 * @return bool
		 */
		public function hasCurl() {
			if ( isset( $this->DRIVERS_AVAILABLE[ NETCURL_NETWORK_DRIVERS::DRIVER_CURL ] ) ) {
				return true;
			}

			return false;
		}

		/**
		 * @return NETCURL_DRIVER_CONTROLLER
		 */
		private static function getStatic() {
			return new NETCURL_DRIVER_CONTROLLER();
		}

		/**
		 * @return bool
		 */
		public static function getCurl() {
			return self::getStatic()->hasCurl();
		}


		/**
		 * Checks if it is possible to use the standard setup
		 *
		 * @return bool
		 */
		private function getInternalDriver() {
			if ( function_exists( 'curl_init' ) && function_exists( 'curl_exec' ) ) {
				$this->DRIVERS_AVAILABLE[ NETCURL_NETWORK_DRIVERS::DRIVER_CURL ] = NETCURL_NETWORK_DRIVERS::DRIVER_CURL;

				return true;
			}

			return false;
		}

		private function getAvailableClasses() {
			$DRIVERS_AVAILABLE = array();
			foreach ( $this->DRIVERS_SUPPORTED as $driverClass => $driverClassId ) {
				if ( class_exists( $driverClass, NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
					$DRIVERS_AVAILABLE[ $driverClassId ] = $driverClass;
					// Guzzle supports both curl and stream so include it here
					if ( $driverClassId == NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP ) {
						if ( ! $this->hasCurl() ) {
							unset( $DRIVERS_AVAILABLE[ NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP ] );
						}
						$DRIVERS_AVAILABLE [ NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP_STREAM ] = $driverClass;
					}
				}
			}
			$this->DRIVERS_AVAILABLE += $DRIVERS_AVAILABLE;

			return $DRIVERS_AVAILABLE;
		}

		/**
		 * @return int|NETCURL_DRIVERS_INTERFACE
		 * @throws \Exception
		 */
		public function getAutodetectedDriver() {
			if ( $this->hasCurl() ) {
				$this->DRIVER = NETCURL_NETWORK_DRIVERS::DRIVER_CURL;

				return $this->DRIVER;
			} else {
				if ( is_array( $this->DRIVERS_AVAILABLE ) && count( $this->DRIVERS_AVAILABLE ) ) {
					$availableDriverIds = array_keys( $this->DRIVERS_AVAILABLE );
					$nextDriver         = array_pop( $availableDriverIds );
					$this->setDriver( $nextDriver );

					return $this->DRIVER;
				} else {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " NetCurlDriverException: No communication drivers are currently available (not even curl).", $this->NETWORK->getExceptionCode( 'NETCURL_NO_DRIVER_AVAILABLE' ) );
				}
			}
		}

        /**
         * @return int|NETCURL_DRIVERS_INTERFACE
         * @throws \Exception
         */
		public static function setAutoDetect() {
			return self::getStatic()->getAutodetectedDriver();
		}

		/**
		 * Get list of available drivers
		 *
		 * @return array
		 */
		public function getSystemWideDrivers() {
			return $this->DRIVERS_AVAILABLE;
		}

		/**
		 * Get status of disabled function
		 *
		 * @param string $functionName
		 *
		 * @return bool
		 */
		public function getIsDisabled( $functionName = '' ) {
			if ( is_string( $functionName ) ) {
				if ( preg_match( "/,/", $functionName ) ) {
					$findMultiple = array_map( "trim", explode( ",", $functionName ) );

					return $this->getIsDisabled( $findMultiple );
				}
				if ( in_array( $functionName, $this->FUNCTIONS_DISABLED ) ) {
					return true;
				}
			} else if ( is_array( $functionName ) ) {
				foreach ( array_map( "strtolower", $functionName ) as $findFunction ) {
					if ( in_array( $findFunction, $this->FUNCTIONS_DISABLED ) ) {
						return true;
					}
				}
			}

			return false;
		}

        /**
         * Set up driver by class name
         *
         * @param int   $driverId
         * @param array $parameters
         * @param null  $ownClass Defines own class to use
         *
         * @return NETCURL_DRIVERS_INTERFACE
         */
		private function getDriverByClass( $driverId = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET, $parameters = null, $ownClass = null ) {
			$driverClass = isset( $this->DRIVERS_AVAILABLE[ $driverId ] ) ? $this->DRIVERS_AVAILABLE[ $driverId ] : null;
			/** @var NETCURL_DRIVERS_INTERFACE $newDriver */
			$newDriver       = null;
			$bridgeClassName = "";

			// Guzzle primary driver is based on curl, so we'll check if curl is available
			if ( $driverId == NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP && ! $this->hasCurl() ) {
				// If curl is unavailable, we'll fall  back to guzzleStream
				$driverId = NETCURL_NETWORK_DRIVERS::DRIVER_GUZZLEHTTP_STREAM;
			}

			if ( ! is_null( $ownClass ) && class_exists( $ownClass, NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
				if ( is_null( $parameters ) ) {
					$newDriver = new $ownClass();
				} else {
					$newDriver = new $ownClass( $parameters );
				}

				return $newDriver;
			}

			if ( class_exists( $driverClass, NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
				if ( isset( $this->DRIVERS_BRIDGED[ $driverClass ] ) ) {
					if ( class_exists( $this->DRIVERS_BRIDGED[ $driverClass ], NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
						$bridgeClassName = $this->DRIVERS_BRIDGED[ $driverClass ];
					} else if ( class_exists( '\\TorneLIB\\' . $this->DRIVERS_BRIDGED[ $driverClass ], NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
						$bridgeClassName = '\\TorneLIB\\' . $this->DRIVERS_BRIDGED[ $driverClass ];
					}
					if ( is_null( $parameters ) ) {
						$newDriver = new $bridgeClassName();
					} else {
						$newDriver = new $bridgeClassName( $parameters );
					}
				} else {
					if ( is_null( $parameters ) ) {
						$newDriver = new $driverClass();
					} else {
						$newDriver = new $driverClass( $parameters );
					}
				}
				// Follow standards for internal bridges if method exists, otherwise skip this part. By doing this, we'd be able to import and directly use external drivers.
				if ( ! is_null( $newDriver ) && method_exists( $newDriver, 'setDriverId' ) ) {
					$newDriver->setDriverId( $driverId );
				}
			}

			$this->DRIVER = $newDriver;

			return $newDriver;
		}

		/**
		 * @param int $driverNameConstans
		 *
		 * @return bool
		 */
		public function getIsDriver( $driverNameConstans = NETCURL_NETWORK_DRIVERS::DRIVER_CURL ) {
			if ( isset( $this->DRIVERS_AVAILABLE[ $driverNameConstans ] ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Initialize driver
		 *
		 * @param int  $netDriver
		 * @param null $parameters
		 * @param null $ownClass
		 *
		 * @return int|NETCURL_DRIVERS_INTERFACE
		 * @throws \Exception
		 */
		public function setDriver( $netDriver = NETCURL_NETWORK_DRIVERS::DRIVER_CURL, $parameters = null, $ownClass = null ) {
			$this->DRIVER = null;

			return $this->getDriver( $netDriver, $parameters, $ownClass );
		}

		/**
		 * @param int  $netDriver
		 * @param null $parameters
		 * @param null $ownClass
		 *
		 * @return int|NETCURL_DRIVERS_INTERFACE
		 * @throws \Exception
		 */
		public function getDriver( $netDriver = NETCURL_NETWORK_DRIVERS::DRIVER_CURL, $parameters = null, $ownClass = null ) {

			if ( is_object( $this->DRIVER ) ) {
				return $this->DRIVER;
			}

			if ( ! is_null( $ownClass ) && class_exists( $ownClass, NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
				$this->DRIVER    = $this->getDriverByClass( $netDriver, $parameters, $ownClass );
				$this->DRIVER_ID = $netDriver;

				return $this->DRIVER;
			}

			if ( $this->getIsDriver( $netDriver ) ) {
				if ( is_string( $this->DRIVERS_AVAILABLE[ $netDriver ] ) && ! is_numeric( $this->DRIVERS_AVAILABLE[ $netDriver ] ) ) {
					/** @var NETCURL_DRIVERS_INTERFACE DRIVER */
					$this->DRIVER = $this->getDriverByClass( $netDriver, $parameters, $ownClass );
				} else if ( is_numeric( $this->DRIVERS_AVAILABLE[ $netDriver ] ) && $this->DRIVERS_AVAILABLE[ $netDriver ] == $netDriver ) {
					$this->DRIVER = $netDriver;
				}
				$this->DRIVER_ID = $netDriver;

			} else {
				if ( $this->hasCurl() ) {
					$this->DRIVER    = NETCURL_NETWORK_DRIVERS::DRIVER_CURL;
					$this->DRIVER_ID = NETCURL_NETWORK_DRIVERS::DRIVER_CURL;
				} else {
					// Last resort: Check if there is any other driver available if this fails
					$testDriverAvailability = $this->getAutodetectedDriver();
					if ( is_object( $testDriverAvailability ) ) {
						$this->DRIVER = $testDriverAvailability;
					} else {
						throw new \Exception( NETCURL_CURL_CLIENTNAME . " NetCurlDriverException: No communication drivers are currently available (not even curl).", $this->NETWORK->getExceptionCode( 'NETCURL_NO_DRIVER_AVAILABLE' ) );
					}
				}
			}

			return $this->DRIVER;
		}

		public function getDriverById() {
			return $this->DRIVER_ID;
		}

		/**
		 * Check if SOAP exists in system
		 *
		 * @param bool $extendedSearch Extend search for SOAP (unsafe method, looking for constants defined as SOAP_*)
		 *
		 * @return bool
		 */
		public function hasSoap( $extendedSearch = false ) {
			$soapClassBoolean = false;
			if ( ( class_exists( 'SoapClient', NETCURL_CLASS_EXISTS_AUTOLOAD ) || class_exists( '\SoapClient', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) ) {
				$soapClassBoolean = true;
			}
			$sysConst = get_defined_constants();
			if ( in_array( 'SOAP_1_1', $sysConst ) || in_array( 'SOAP_1_2', $sysConst ) ) {
				$soapClassBoolean = true;
			} else {
				if ( $extendedSearch ) {
					foreach ( $sysConst as $constantKey => $constantValue ) {
						if ( preg_match( '/^SOAP_/', $constantKey ) ) {
							$soapClassBoolean = true;
						}
					}
				}
			}

			return $soapClassBoolean;
		}


	}
}
if ( ! class_exists( 'MODULE_SSL', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\MODULE_SSL', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {

	if ( ! defined( 'NETCURL_SSL_RELEASE' ) ) {
		define( 'NETCURL_SSL_RELEASE', '6.0.0' );
	}
	if ( ! defined( 'NETCURL_SSL_MODIFY' ) ) {
		define( 'NETCURL_SSL_MODIFY', '20180325' );
	}
	if ( ! defined( 'NETCURL_SSL_CLIENTNAME' ) ) {
		define( 'NETCURL_SSL_CLIENTNAME', 'MODULE_SSL' );
	}

	/**
	 * Class MODULE_SSL SSL Helper class
	 *
	 * @package TorneLIB
	 */
	class MODULE_SSL {
		/** @var array Default paths to the certificates we are looking for */
		private $sslPemLocations = array( '/etc/ssl/certs' );
		/** @var array Files to look for in sslPemLocations */
		private $sslPemFiles = array( 'cacert.pem', 'ca-certificates.crt' );
		/** @var string Location of the SSL certificate bundle */
		private $sslRealCertLocation;
		/** @var bool Strict verification of the connection (sslVerify) */
		private $SSL_STRICT_VERIFICATION = true;
		/** @var null|bool Allow self signed certificates */
		private $SSL_STRICT_SELF_SIGNED = true;
		/** @var bool Allowing fallback/failover to unstict verification */
		private $SSL_STRICT_FAILOVER = false;

		/** @var MODULE_CURL $PARENT */
		private $PARENT;
		/** @var MODULE_NETWORK $NETWORK */
		private $NETWORK;

		private $sslopt = array();

		/**
		 * MODULE_SSL constructor.
		 *
		 * @param MODULE_CURL $MODULE_CURL
		 */
		function __construct( $MODULE_CURL = null ) {
			if ( is_object( $MODULE_CURL ) ) {
				$this->PARENT = $MODULE_CURL;
			}
			$this->NETWORK = new MODULE_NETWORK();
		}

		/**
		 * @return array
		 * @since 6.0.0
		 */
		public static function getCurlSslAvailable() {
			// Common ssl checkers (if they fail, there is a sslDriverError to recall

			$sslDriverError = array();
			$streamWrappers = @stream_get_wrappers();
			if ( ! is_array( $streamWrappers ) ) {
				$streamWrappers = array();
			}
			if ( ! in_array( 'https', array_map( "strtolower", $streamWrappers ) ) ) {
				$sslDriverError[] = "SSL Failure: HTTPS wrapper can not be found";
			}
			if ( ! extension_loaded( 'openssl' ) ) {
				$sslDriverError[] = "SSL Failure: HTTPS extension can not be found";
			}

			if ( function_exists( 'curl_version' ) ) {
				$curlVersionRequest = curl_version();
				if ( defined( 'CURL_VERSION_SSL' ) ) {
					if ( isset( $curlVersionRequest['features'] ) ) {
						$CURL_SSL_AVAILABLE = ( $curlVersionRequest['features'] & CURL_VERSION_SSL ? true : false );
						if ( ! $CURL_SSL_AVAILABLE ) {
							$sslDriverError[] = 'SSL Failure: Protocol "https" not supported or disabled in libcurl';
						}
					} else {
						$sslDriverError[] = "SSL Failure: CurlVersionFeaturesList does not return any feature (this should not be happen)";
					}
				}
			}

			return $sslDriverError;
		}

		/**
		 * Returns true if no errors occured in the control
		 *
		 * @return bool
		 */
		public static function hasSsl() {
			if ( ! count( self::getCurlSslAvailable() ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Make sure that we are allowed to do things
		 *
		 * @param bool $checkSafeMode If true, we will also check if safe_mode is active
		 * @param bool $mockSafeMode  If true, NetCurl will pretend safe_mode is true (for testing)
		 *
		 * @return bool If true, PHP is in secure mode and won't allow things like follow-redirects and setting up different paths for certificates, etc
		 * @since 6.0.20
		 */
		public function getIsSecure( $checkSafeMode = true, $mockSafeMode = false ) {
			$currentBaseDir = trim( ini_get( 'open_basedir' ) );
			if ( $checkSafeMode ) {
				if ( $currentBaseDir == '' && ! $this->getSafeMode( $mockSafeMode ) ) {
					return false;
				}

				return true;
			} else {
				if ( $currentBaseDir == '' ) {
					return false;
				}

				return true;
			}
		}

		/**
		 * Get safe_mode status (mockable)
		 *
		 * @param bool $mockedSafeMode When active, this always returns true
		 *
		 * @return bool
		 */
		private function getSafeMode( $mockedSafeMode = false ) {
			if ( $mockedSafeMode ) {
				return true;
			}

			// There is no safe mode in PHP 5.4.0 and above
			if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {
				return false;
			}

			return ( filter_var( ini_get( 'safe_mode' ), FILTER_VALIDATE_BOOLEAN ) );
		}

        /**
         * openssl_guess rewrite
         *
         * @param bool $forceChecking
         * @return string
         * @since 6.0.0
         */
		public function getSslCertificateBundle( $forceChecking = false ) {
			// Assume that sysadmins can handle this, if open_basedir is set as things will fail if we proceed here
			if ( $this->getIsSecure( false ) && ! $forceChecking ) {
				return null;
			}

			foreach ( $this->sslPemLocations as $filePath ) {
				if ( is_dir( $filePath ) && ! in_array( $filePath, $this->sslPemLocations ) ) {
					$this->sslPemLocations[] = $filePath;
				}
			}

			// If PHP >= 5.6.0, the OpenSSL module has its own way getting certificate locations
			if ( version_compare( PHP_VERSION, "5.6.0", ">=" ) && function_exists( "openssl_get_cert_locations" ) ) {
				$internalCheck = openssl_get_cert_locations();
				if ( isset( $internalCheck['default_cert_dir'] ) && is_dir( $internalCheck['default_cert_dir'] ) && ! empty( $internalCheck['default_cert_file'] ) ) {
					$certFile = basename( $internalCheck['default_cert_file'] );
					if ( ! in_array( $internalCheck['default_cert_dir'], $this->sslPemLocations ) ) {
						$this->sslPemLocations[] = $internalCheck['default_cert_dir'];
					}
					if ( ! in_array( $certFile, $this->sslPemFiles ) ) {
						$this->sslPemFiles[] = $certFile;
					}
				}
			}

			// get first match
			foreach ( $this->sslPemLocations as $location ) {
				foreach ( $this->sslPemFiles as $file ) {
					$fullCertPath = $location . "/" . $file;
					if ( file_exists( $fullCertPath ) && empty( $this->sslRealCertLocation ) ) {
						$this->sslRealCertLocation = $fullCertPath;
					}
				}
			}

			return $this->sslRealCertLocation;
		}

		/**
		 * @param array $pemLocationData
		 *
		 * @return bool
		 * @throws \Exception
		 * @since 6.0.20
		 */
		public function setPemLocation( $pemLocationData = array() ) {
			$failAdd = false;
			if ( is_string( $pemLocationData ) ) {
				$pemLocationData = array( $pemLocationData );
			}
			if ( is_array( $pemLocationData ) && is_array( $pemLocationData ) ) {
				foreach ( $pemLocationData as $pemDataRow ) {
					$pemDataRow = trim( preg_replace( "/\/$/", '', $pemDataRow ) );
					$pemFile    = $pemDataRow;
					$pemDir     = dirname( $pemDataRow );
					if ( $pemFile != $pemDir && is_file( $pemFile ) ) {
						$this->sslPemFiles[]     = $pemFile;
						$this->sslPemLocations[] = $pemDir;
					} else {
						$failAdd = true;
					}
				}
			}
			if ( $failAdd ) {
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: The format of pemLocationData is not properly set", $this->NETWORK->getExceptionCode( 'NETCURL_PEMLOCATIONDATA_FORMAT_ERROR' ) );
			}

			return true;
		}

		public function getPemLocations() {
			return $this->sslPemLocations;
		}

		/**
		 * Set the rules of how to verify SSL certificates
		 *
		 * @param bool $strictCertificateVerification
		 * @param bool $prohibitSelfSigned This only covers streams
		 *
		 * @since 6.0.0
		 */
		public function setStrictVerification( $strictCertificateVerification = true, $prohibitSelfSigned = true ) {
			$this->SSL_STRICT_VERIFICATION = $strictCertificateVerification;
			$this->SSL_STRICT_SELF_SIGNED  = $prohibitSelfSigned;
		}

		/**
		 * Returns the mode of strict verification set up. If true, netcurl will be very strict with all certificate verifications.
		 *
		 * @return bool
		 * @since 6.0.0
		 */
		public function getStrictVerification() {
			return $this->SSL_STRICT_VERIFICATION;
		}

		/**
		 *
		 * @return bool|null
		 */
		public function getStrictSelfSignedVerification() {
			// If this is not set, assume we want the value hardened
			return $this->SSL_STRICT_SELF_SIGNED;
		}

		/**
		 * Allow NetCurl to make failover (fallback) to unstrict SSL verification after a strict call has been made
		 *
		 * Replacement for allowSslUnverified setup
		 *
		 * @param bool $sslFailoverEnabled *
		 *
		 * @since 6.0.0
		 */
		public function setStrictFallback( $sslFailoverEnabled = false ) {
			$this->SSL_STRICT_FAILOVER = $sslFailoverEnabled;
		}

		/**
		 * @return bool
		 * @since 6.0.0
		 */
		public function getStrictFallback() {
			return $this->SSL_STRICT_FAILOVER;
		}

		/**
		 * Prepare context stream for SSL
		 *
		 * @return array
		 *
		 * @since 6.0.0
		 */
		public function getSslStreamContext() {
			$sslCaBundle = $this->getSslCertificateBundle();
			/** @var array $contextGenerateArray Default stream context array, does not contain a ca bundle */
			$contextGenerateArray = array(
				'verify_peer'       => $this->SSL_STRICT_VERIFICATION,
				'verify_peer_name'  => $this->SSL_STRICT_VERIFICATION,
				'verify_host'       => $this->SSL_STRICT_VERIFICATION,
				'allow_self_signed' => $this->SSL_STRICT_SELF_SIGNED,
			);
			// During tests, this bundle might disappear depending on what happens in tests. If something fails, that might render
			// strange false alarms, so we'll just add the file into the array if it's set. Many tests in a row can strangely have this effect.
			if ( ! empty( $sslCaBundle ) ) {
				$contextGenerateArray['cafile'] = $sslCaBundle;
			}

			return $contextGenerateArray;
		}

		/**
		 * Put the context into stream for SSL
		 *
		 * @param array $optionsArray
		 * @param array $addonContextData
		 *
		 * @return array
		 * @since 6.0.0
		 */
		public function getSslStream( $optionsArray = array(), $addonContextData = array() ) {
			$streamContextOptions = array();
			if ( is_object( $this->PARENT ) ) {
				$this->PARENT->setUserAgent( NETCURL_SSL_CLIENTNAME . "-" . NETCURL_SSL_RELEASE );
				$streamContextOptions['http'] = array(
					"user_agent" => $this->PARENT->getUserAgent()
				);
			}
			$sslCorrection = $this->getSslStreamContext();
			if ( count( $sslCorrection ) ) {
				$streamContextOptions['ssl'] = $this->getSslStreamContext();
			}
			if ( is_array( $addonContextData ) && count( $addonContextData ) ) {
				foreach ( $addonContextData as $contextKey => $contextValue ) {
					$streamContextOptions[ $contextKey ] = $contextValue;
				}
			}
			$optionsArray['stream_context'] = stream_context_create( $streamContextOptions );
			$this->sslopt                   = $optionsArray;

			return $optionsArray;

		}
	}
}
if ( ! defined( 'NETCURL_NETBITS_RELEASE' ) ) {
	define( 'NETCURL_NETBITS_RELEASE', '6.0.1' );
}
if ( ! defined( 'NETCURL_NETBITS_MODIFY' ) ) {
	define( 'NETCURL_NETBITS_MODIFY', '20180320' );
}

// Check if there is a packagist release already loaded, since this network standalone release is deprecated as of 20180320.
if ( ! class_exists( 'MODULE_NETBITS', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\MODULE_NETBITS', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class TorneLIB_NetBits Netbits Library for calculations with bitmasks
	 *
	 * @package TorneLIB
	 * @version 6.0.1
	 */
	class MODULE_NETBITS {
		/** @var array Standard bitmask setup */
		private $BIT_SETUP;
		private $maxBits = 8;

		function __construct( $bitStructure = array() ) {
			$this->BIT_SETUP = array(
				'OFF'     => 0,
				'BIT_1'   => 1,
				'BIT_2'   => 2,
				'BIT_4'   => 4,
				'BIT_8'   => 8,
				'BIT_16'  => 16,
				'BIT_32'  => 32,
				'BIT_64'  => 64,
				'BIT_128' => 128
			);
			if ( is_array($bitStructure) && count( $bitStructure ) ) {
				$this->BIT_SETUP = $this->validateBitStructure( $bitStructure );
			}
		}

		public function setMaxBits( $maxBits = 8 ) {
			$this->maxBits = $maxBits;
			$this->validateBitStructure( $maxBits );
		}

		public function getMaxBits() {
			return $this->maxBits;
		}

		private function getRequiredBits( $maxBits = 8 ) {
			$requireArray = array();
			if ( $this->maxBits != $maxBits ) {
				$maxBits = $this->maxBits;
			}
			for ( $curBit = 0; $curBit <= $maxBits; $curBit ++ ) {
				$requireArray[] = (int) pow( 2, $curBit );
			}

			return $requireArray;
		}

		private function validateBitStructure( $bitStructure = array() ) {
			if ( is_numeric( $bitStructure ) ) {
				$newBitStructure = array(
					'OFF' => 0
				);
				for ( $bitIndex = 0; $bitIndex <= $bitStructure; $bitIndex ++ ) {
					$powIndex                              = pow( 2, $bitIndex );
					$newBitStructure[ "BIT_" . $powIndex ] = $powIndex;
				}
				$bitStructure    = $newBitStructure;
				$this->BIT_SETUP = $bitStructure;
			}
			$require                  = $this->getRequiredBits( count( $bitStructure ) );
			$validated                = array();
			$newValidatedBitStructure = array();
			$valueKeys                = array();
			foreach ( $bitStructure as $key => $value ) {
				if ( in_array( $value, $require ) ) {
					$newValidatedBitStructure[ $key ] = $value;
					$valueKeys[ $value ]              = $key;
					$validated[]                      = $value;
				}
			}
			foreach ( $require as $bitIndex ) {
				if ( ! in_array( $bitIndex, $validated ) ) {
					if ( $bitIndex == "0" ) {
						$newValidatedBitStructure["OFF"] = $bitIndex;
					} else {
						$bitIdentificationName                              = "BIT_" . $bitIndex;
						$newValidatedBitStructure[ $bitIdentificationName ] = $bitIndex;
					}
				} else {
					if ( isset( $valueKeys[ $bitIndex ] ) && ! empty( $valueKeys[ $bitIndex ] ) ) {
						$bitIdentificationName                              = $valueKeys[ $bitIndex ];
						$newValidatedBitStructure[ $bitIdentificationName ] = $bitIndex;
					}
				}
			}
			asort( $newValidatedBitStructure );
			$this->BIT_SETUP = $newValidatedBitStructure;

			return $newValidatedBitStructure;
		}

		public function setBitStructure( $bitStructure = array() ) {
			$this->validateBitStructure( $bitStructure );
		}

		public function getBitStructure() {
			return $this->BIT_SETUP;
		}

		/**
		 * Finds out if a bitmasked value is located in a bitarray
		 *
		 * @param int $requestedExistingBit
		 * @param int $requestedBitSum
		 *
		 * @return bool
		 */
		public function isBit( $requestedExistingBit = 0, $requestedBitSum = 0 ) {
			$return = false;
			if ( is_array( $requestedExistingBit ) ) {
				foreach ( $requestedExistingBit as $bitKey ) {
					if ( ! $this->isBit( $bitKey, $requestedBitSum ) ) {
						return false;
					}
				}

				return true;
			}

			// Solution that works with unlimited bits
			for ( $bitCount = 0; $bitCount < count( $this->getBitStructure() ); $bitCount ++ ) {
				if ( $requestedBitSum & pow( 2, $bitCount ) ) {
					if ( $requestedExistingBit == pow( 2, $bitCount ) ) {
						$return = true;
					}
				}
			}

			// Solution that works with bits up to 8
			/*
			$sum = 0;
			preg_match_all("/\d/", sprintf("%08d", decbin( $requestedBitSum)), $bitArray);
			for ($bitCount = count($bitArray[0]); $bitCount >= 0; $bitCount--) {
				if (isset($bitArray[0][$bitCount])) {
					if ( $requestedBitSum & pow(2, $bitCount)) {
						if ( $requestedExistingBit == pow(2, $bitCount)) {
							$return = true;
						}
					}
				}
			}
			*/

			return $return;
		}

		/**
		 * Get active bits in an array
		 *
		 * @param int $bitValue
		 *
		 * @return array
		 */
		public function getBitArray( $bitValue = 0 ) {
			$returnBitList = array();
			foreach ( $this->BIT_SETUP as $key => $value ) {
				if ( $this->isBit( $value, $bitValue ) ) {
					$returnBitList[] = $key;
				}
			}

			return $returnBitList;
		}

	}
}

if ( ! class_exists( 'TorneLIB_NetBits', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\TorneLIB_NetBits', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class TorneLIB_NetBits
	 *
	 * @package    TorneLIB
	 * @deprecated Use MODULE_NETBITS
	 */
	class TorneLIB_NetBits extends MODULE_NETBITS {
		function __construct( array $bitStructure = array() ) {
			parent::__construct( $bitStructure );
		}
	}
}

if ( ! class_exists( 'TorneLIB_NETCURL_EXCEPTIONS', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\TorneLIB_NETCURL_EXCEPTIONS', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class NETCURL_EXCEPTIONS
	 *
	 * @package TorneLIB
	 */
	abstract class NETCURL_EXCEPTIONS {
		const NETCURL_NO_ERROR = 0;
		const NETCURL_EXCEPTION_IT_WORKS = 1;
        const NETCURL_EXCEPTION_IT_DOESNT_WORK = 500;


		/**
		 * @deprecated
		 */
		const NETCURL_CURL_MISSING = 1000;
		const NETCURL_SETFLAG_KEY_EMPTY = 1001;

		/**
		 * @deprecated
		 */
		const NETCURL_COOKIEPATH_SETUP_FAIL = 1002;
		const NETCURL_IPCONFIG_NOT_VALID = 1003;

		/**
		 * @deprecated
		 */
		const NETCURL_SETSSLVERIFY_UNVERIFIED_NOT_SET = 1004;
		const NETCURL_DOMDOCUMENT_CLASS_MISSING = 1005;
		const NETCURL_GETPARSEDVALUE_KEY_NOT_FOUND = 1006;
		const NETCURL_SOAPCLIENT_CLASS_MISSING = 1007;
		const NETCURL_SIMPLESOAP_GETSOAP_CREATE_FAIL = 1008;
		const NETCURL_WP_TRANSPORT_ERROR = 1009;
		const NETCURL_CURL_DISABLED = 1010;

		/**
		 * @deprecated
		 */
		const NETCURL_NOCOMM_DRIVER = 1011;
		/**
		 * @deprecated
		 */
		const NETCURL_EXTERNAL_DRIVER_MISSING = 1012;

		const NETCURL_GUZZLESTREAM_MISSING = 1013;
		const NETCURL_HOSTVALIDATION_FAIL = 1014;
		const NETCURL_PEMLOCATIONDATA_FORMAT_ERROR = 1015;
		const NETCURL_DOMDOCUMENT_EMPTY = 1016;
		const NETCURL_NO_DRIVER_AVAILABLE_NOT_EVEN_CURL = 1017;
		const NETCURL_UNEXISTENT_FUNCTION = 1018;
		const NETCURL_PARSE_XML_FAILURE = 1019;
		const NETCURL_IO_PARSER_MISSING = 1020;
        const NETCURL_GUZZLE_RESPONSE_EXCEPTION = 1021;
        const NETCURL_WP_REQUEST_ERROR = 1022;
	}
}

if ( ! class_exists( 'TorneLIB_NETCURL_EXCEPTIONS', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\TorneLIB_NETCURL_EXCEPTIONS', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class TORNELIB_NETCURL_EXCEPTIONS
	 *
	 * @package    TorneLIB
	 * @deprecated Use NETCURL_EXCEPTIONS
	 */
	abstract class TORNELIB_NETCURL_EXCEPTIONS extends NETCURL_EXCEPTIONS {
	}
}
if ( ! class_exists( 'NETCURL_AUTH_TYPES', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\NETCURL_AUTH_TYPES', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class CURL_AUTH_TYPES Available authentication types for use with password protected sites
	 *
	 * The authentication types listed in this section defines what is fully supported by the module. In other cases you might be on your own.
	 *
	 * @package TorneLIB
	 * @since   6.0.20
	 */
	abstract class NETCURL_AUTH_TYPES {
		const AUTHTYPE_NONE = 0;
		const AUTHTYPE_BASIC = 1;
	}
}
if ( ! class_exists( 'CURL_AUTH_TYPES', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\CURL_AUTH_TYPES', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * @package    TorneLIB
	 * @deprecated 6.0.20 Use NETCURL_AUTH_TYPES
	 */
	abstract class CURL_AUTH_TYPES extends NETCURL_AUTH_TYPES {
	}
}
if ( ! class_exists( 'NETCURL_HTTP_OBJECT', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\NETCURL_HTTP_OBJECT', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class NETCURL_CURLOBJECT
	 *
	 * @package TorneLIB
	 * @since   6.0.20
	 */
	class NETCURL_HTTP_OBJECT {

		private $NETCURL_HEADER;
		private $NETCURL_BODY;
		private $NETCURL_CODE;
		private $NETCURL_PARSED;
		private $NETCURL_URL;
		private $NETCURL_IP;

		public function __construct( $header = array(), $body = '', $code = 0, $parsed = '', $url = '', $ip = '' ) {
			$this->NETCURL_HEADER = $header;
			$this->NETCURL_BODY   = $body;
			$this->NETCURL_CODE   = $code;
			$this->NETCURL_PARSED = $parsed;
			$this->NETCURL_URL    = $url;
			$this->NETCURL_IP     = $ip;
		}

		public function getHeader() {
			return $this->NETCURL_HEADER;
		}

		public function getBody() {
			return $this->NETCURL_BODY;
		}

		public function getCode() {
			return $this->NETCURL_CODE;
		}

		public function getParsed() {
			return $this->NETCURL_PARSED;
		}

		public function getUrl() {
			$this->NETCURL_URL;
		}

		public function getIp() {
			return $this->NETCURL_IP;
		}

	}
}
if ( ! class_exists( 'TORNELIB_CURLOBJECT', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\TORNELIB_CURLOBJECT', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class TORNELIB_CURLOBJECT
	 *
	 * @package    TorneLIB
	 * @deprecated 6.0.20 Use NETCURL_HTTP_OBJECT
	 */
	class TORNELIB_CURLOBJECT extends NETCURL_HTTP_OBJECT {
		public $header;
		public $body;
		public $code;
		public $parsed;
		public $url;
		public $ip;
	}
}
if ( ! class_exists( 'NETCURL_POST_DATATYPES', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\NETCURL_POST_DATATYPES', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class NETCURL_POST_DATATYPES Prepared formatting for POST-content in this library (Also available from for example PUT)
	 *
	 * @package TorneLIB
	 * @since   6.0.20
	 */
	abstract class NETCURL_POST_DATATYPES {
		const DATATYPE_NOT_SET = 0;
		const DATATYPE_JSON = 1;
		const DATATYPE_SOAP = 2;
		const DATATYPE_XML = 3;
		const DATATYPE_SOAP_XML = 4;
	}
}
if ( ! class_exists( 'CURL_POST_AS', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\CURL_POST_AS', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * @package    TorneLIB
	 * @deprecated 6.0.20 Use NETCURL_POST_DATATYPES
	 */
	abstract class CURL_POST_AS extends NETCURL_POST_DATATYPES {
		/**
		 * @deprecated Use NETCURL_POST_DATATYPES::DATATYPE_DEFAULT
		 */
		const POST_AS_NORMAL = 0;
		/**
		 * @deprecated Use NETCURL_POST_DATATYPES::DATATYPE_JSON
		 */
		const POST_AS_JSON = 1;
		/**
		 * @deprecated Use NETCURL_POST_DATATYPES::DATATYPE_SOAP
		 */
		const POST_AS_SOAP = 2;
	}
}
if ( ! class_exists( 'NETCURL_NETWORK_DRIVERS', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\NETCURL_NETWORK_DRIVERS', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class NETCURL_NETWORK_DRIVERS Supported network Addons
	 *
	 * @package TorneLIB
	 * @since   6.0.20
	 */
	abstract class NETCURL_NETWORK_DRIVERS {
		const DRIVER_NOT_SET = 0;
		const DRIVER_CURL = 1;
		const DRIVER_WORDPRESS = 1000;
		const DRIVER_GUZZLEHTTP = 1001;
		const DRIVER_GUZZLEHTTP_STREAM = 1002;

		/**
		 * @deprecated Internal driver should be named DRIVER_CURL
		 */
		const DRIVER_INTERNAL = 1;
		const DRIVER_SOAPCLIENT = 2;

		/** @var int Using the class itself */
		const DRIVER_OWN_EXTERNAL = 100;

	}
}
if ( ! class_exists( 'TORNELIB_CURL_DRIVERS', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\TORNELIB_CURL_DRIVERS', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class TORNELIB_CURL_DRIVERS
	 *
	 * @package    TorneLIB
	 * @deprecated .0.20 Use NETCURL_NETWORK_DRIVERS
	 */
	abstract class TORNELIB_CURL_DRIVERS extends NETCURL_NETWORK_DRIVERS {
	}
}
if ( ! class_exists('NETCURL_ENVIRONMENT', NETCURL_CLASS_EXISTS_AUTOLOAD) && ! class_exists('TorneLIB\NETCURL_ENVIRONMENT', NETCURL_CLASS_EXISTS_AUTOLOAD)) {
    /**
     * Class NETCURL_ENVIRONMENT Unittest helping class
     *
     * @package    TorneLIB
     * @since      6.0.0
     * @deprecated 6.0.20 Not in use
     */
    abstract class NETCURL_ENVIRONMENT
    {
        const ENVIRONMENT_PRODUCTION = 0;
        const ENVIRONMENT_TEST = 1;
    }
}

if ( ! class_exists('TORNELIB_CURL_ENVIRONMENT', NETCURL_CLASS_EXISTS_AUTOLOAD) && ! class_exists('TorneLIB\TORNELIB_CURL_ENVIRONMENT', NETCURL_CLASS_EXISTS_AUTOLOAD)) {
    /** @noinspection PhpDeprecationInspection */

    /**
     * Class TORNELIB_CURL_ENVIRONMENT
     *
     * @package    TorneLIB
     * @deprecated Use NETCURL_ENVIRONMENT
     * @since      6.0.0
     * @deprecated 6.0.20 Not in use
     */
    abstract class TORNELIB_CURL_ENVIRONMENT extends NETCURL_ENVIRONMENT
    {
    }
}
if ( ! class_exists('NETCURL_IP_PROTOCOLS', NETCURL_CLASS_EXISTS_AUTOLOAD) && ! class_exists('TorneLIB\NETCURL_IP_PROTOCOLS', NETCURL_CLASS_EXISTS_AUTOLOAD)) {
    /**
     * Class NETCURL_IP_PROTOCOLS IP Address Types class
     *
     * @package TorneLIB
     * @since   6.0.20
     */
    abstract class NETCURL_IP_PROTOCOLS
    {
        const PROTOCOL_NONE = 0;
        const PROTOCOL_IPV4 = 4;
        const PROTOCOL_IPV6 = 6;
    }

}
if ( ! class_exists('TorneLIB_Network_IP', NETCURL_CLASS_EXISTS_AUTOLOAD) && ! class_exists('TorneLIB\TorneLIB_Network_IP', NETCURL_CLASS_EXISTS_AUTOLOAD)) {
    /**
     * Class TorneLIB_Network_IP
     *
     * @package    TorneLIB
     * @deprecated 6.0.20 Use NETCURL_IP_PROTOCOLS
     */
    abstract class TorneLIB_Network_IP extends NETCURL_IP_PROTOCOLS
    {
        const IPTYPE_NONE = 0;
        const IPTYPE_V4 = 4;
        const IPTYPE_V6 = 6;
    }
}

if ( ! class_exists('TorneLIB_Network_IP_Protocols', NETCURL_CLASS_EXISTS_AUTOLOAD) && ! class_exists('TorneLIB\TorneLIB_Network_IP_Protocols', NETCURL_CLASS_EXISTS_AUTOLOAD)) {
    /** @noinspection PhpDeprecationInspection */

    /**
     * Class TorneLIB_Network_IP_Protocols
     *
     * @package    TorneLIB
     * @deprecated 6.0.20 Use NETCURL_IP_PROTOCOLS
     */
    abstract class TorneLIB_Network_IP_Protocols extends TorneLIB_Network_IP
    {
    }
}
if ( ! class_exists( 'NETCURL_POST_METHODS', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\NETCURL_POST_METHODS', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class NETCURL_POST_METHODS List of methods available in this library
	 *
	 * @package TorneLIB
	 * @since   6.0.20
	 */
	abstract class NETCURL_POST_METHODS {
		const METHOD_GET = 0;
		const METHOD_POST = 1;
		const METHOD_PUT = 2;
		const METHOD_DELETE = 3;
		const METHOD_HEAD = 4;
		const METHOD_REQUEST = 5;
	}
}

if ( ! class_exists( 'CURL_METHODS', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\CURL_METHODS', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * @package    TorneLIB
	 * @deprecated 6.0.20 Use NETCURL_POST_METHODS
	 */
	abstract class CURL_METHODS extends NETCURL_POST_METHODS {
	}
}
if ( ! class_exists( 'NETCURL_RESOLVER', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\NETCURL_RESOLVER', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class NETCURL_RESOLVER Class definitions on how to resolve things on lookups
	 *
	 * @package TorneLIB
	 * @since   6.0.20
	 */
	abstract class NETCURL_RESOLVER {
		const RESOLVER_DEFAULT = 0;
		const RESOLVER_IPV4 = 1;
		const RESOLVER_IPV6 = 2;
	}
}

if ( ! class_exists( 'CURL_RESOLVER', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\CURL_RESOLVER', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * @package    TorneLIB
	 * @deprecated 6.0.20 Use NETCURL_RESOLVER
	 */
	abstract class CURL_RESOLVER extends NETCURL_RESOLVER {
	}
}
if ( ! class_exists( 'NETCURL_RESPONSETYPE', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\NETCURL_RESPONSETYPE', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class NETCURL_RESPONSETYPE Assoc or object?
	 *
	 * @package TorneLIB
	 * @since   6.0.20
	 */
	abstract class NETCURL_RESPONSETYPE {
		const RESPONSETYPE_ARRAY = 0;
		const RESPONSETYPE_OBJECT = 1;
	}

	if ( ! class_exists( 'TORNELIB_CURL_RESPONSETYPE', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\TORNELIB_CURL_RESPONSETYPE', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {

		/**
		 * Class TORNELIB_CURL_RESPONSETYPE
		 *
		 * @package    TorneLIB
		 * @deprecated 6.0.20 Use NETCURL_RESPONSETYPE
		 */
		abstract class TORNELIB_CURL_RESPONSETYPE extends NETCURL_RESPONSETYPE {
		}
	}
}
if ( ! class_exists( 'MODULE_NETWORK', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\MODULE_NETWORK', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	if ( ! defined( 'NETCURL_NETWORK_RELEASE' ) ) {
		define( 'NETCURL_NETWORK_RELEASE', '6.0.7RC1' );
	}
	if ( ! defined( 'NETCURL_NETWORK_MODIFY' ) ) {
		define( 'NETCURL_NETWORK_MODIFY', '20180822' );
	}

	/**
	 * Library for handling network related things (currently not sockets). A conversion of a legacy PHP library called "TorneEngine" and family.
	 *
	 * Class MODULE_NETWORK
	 *
	 * @link    https://phpdoc.tornevall.net/TorneLIBv5/class-TorneLIB.TorneLIB_Network.html PHPDoc/Staging - TorneLIB_Network
	 * @link    https://docs.tornevall.net/x/KQCy TorneLIB (PHP) Landing documentation
	 * @link    https://bitbucket.tornevall.net/projects/LIB/repos/tornelib-php/browse Sources of TorneLIB
	 *
	 * @package TorneLIB
	 */
	class MODULE_NETWORK {
		/** @var array Headers from the webserver that may contain potential proxies */
		private $proxyHeaders = array(
			'HTTP_VIA',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED',
			'HTTP_CLIENT_IP',
			'HTTP_FORWARDED_FOR_IP',
			'VIA',
			'X_FORWARDED_FOR',
			'FORWARDED_FOR',
			'X_FORWARDED',
			'FORWARDED',
			'CLIENT_IP',
			'FORWARDED_FOR_IP',
			'HTTP_PROXY_CONNECTION'
		);

		/** @var array Stored list of what the webserver revealed */
		private $clientAddressList = array();
		private $cookieDefaultPath = "/";
		private $cookieUseSecure;
		private $cookieDefaultDomain;
		private $cookieDefaultPrefix;
		private $alwaysResolveHostvalidation = false;

		/** @var TorneLIB_NetBits BitMask handler with 8 bits as default */
		public $BIT;

		/**
		 * TorneLIB_Network constructor.
		 */
		function __construct() {
			// Initiate and get client headers.
			$this->renderProxyHeaders();
			$this->BIT = new MODULE_NETBITS();
		}

		/**
		 * Get an exception code from internal abstract
		 *
		 * If the exception constant name does not exist, or the abstract class is not included in this package, a generic unknown error, based on internal server error, will be returned (500)
		 *
		 * @param string $exceptionConstantName Constant name (make sure it exists before use)
		 *
		 * @return int
		 */
		public function getExceptionCode( $exceptionConstantName = 'NETCURL_NO_ERROR' ) {
			// Make sure that nothing goes wrong here.
			try {
				if ( empty( $exceptionConstantName ) ) {
					$exceptionConstantName = 'NETCURL_NO_ERROR';
				}
				if ( ! class_exists( 'TorneLIB\TORNELIB_NETCURL_EXCEPTIONS', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
					if ( $exceptionConstantName == 'NETCURL_NO_ERROR' ) {
						return 0;
					} else {
						return 500;
					}
				} else {
					$exceptionCode = @constant( 'TorneLIB\TORNELIB_NETCURL_EXCEPTIONS::' . $exceptionConstantName );
					if ( empty( $exceptionCode ) || ! is_numeric( $exceptionCode ) ) {
						return 500;
					} else {
						return (int) $exceptionCode;
					}
				}
			} catch ( \Exception $e ) {
				// If anything goes wrong in this internal handler, return with 501 instead
				return 501;
			}
		}

        /**
         * Uses version_compare with the operators >= (from) and <= (to) to pick up the right version range form a git repository tag list
         * @param $gitUrl
         * @param $fromVersionCompare
         * @param $toVersionCompare
         * @param bool $cleanNonNumerics
         * @param bool $sanitizeNumerics
         * @param bool $keepCredentials
         * @return array
         * @throws \Exception
         */
		public function getGitTagsByVersion($gitUrl, $fromVersionCompare, $toVersionCompare, $cleanNonNumerics = false, $sanitizeNumerics = false, $keepCredentials = true ) {
		    $return = array();
            $versionList = $this->getGitTagsByUrl($gitUrl, $cleanNonNumerics, $sanitizeNumerics, $keepCredentials);
            if (is_array($versionList) && count($versionList)) {
                foreach ($versionList as $versionNum) {
                    if (version_compare($versionNum, $fromVersionCompare, '>=') && version_compare($versionNum, $toVersionCompare, '<=') && !in_array($versionNum, $return)) {
                        $return[] = $versionNum;
                    }
                }
            }
            return $return;
        }

        /**
         * Try to fetch git tags from git URLS
         * @param string $gitUrl
         * @param bool $cleanNonNumerics Normally you do not want to strip anything. This boolean however, decides if we will include non numerical version data in the returned array
         * @param bool $sanitizeNumerics If we decide to not include non numeric values from the version tag array (by $cleanNonNumerics), the tags will be sanitized in a preg_replace filter that will the keep numerics in the content only (with $cleanNonNumerics set to false, this boolen will have no effect)
         * @param $keepCredentials
         * @return array
         * @throws \Exception
         * @since 6.0.4
         */
		public function getGitTagsByUrl($gitUrl, $cleanNonNumerics = false, $sanitizeNumerics = false, $keepCredentials = true ) {
			$fetchFail = true;
			$tagArray  = array();
			$gitUrl    .= "/info/refs?service=git-upload-pack";
			// Clean up all user auth data in URL if exists
            if (!$keepCredentials) {
                $gitUrl = preg_replace("/\/\/(.*?)@/", '//', $gitUrl);
            }
			/** @var $CURL MODULE_CURL */
			$CURL = new MODULE_CURL();

			/** @noinspection PhpUnusedLocalVariableInspection */
			$code             = 0;
			$exceptionMessage = "";
			try {
				$gitGet  = $CURL->doGet( $gitUrl );
				$code    = intval( $CURL->getCode() );
				$gitBody = $CURL->getBody( $gitGet );
				if ( $code >= 200 && $code <= 299 && ! empty( $gitBody ) ) {
					$fetchFail = false;
					preg_match_all( "/refs\/tags\/(.*?)\n/s", $gitBody, $tagMatches );
					if ( isset( $tagMatches[1] ) && is_array( $tagMatches[1] ) ) {
						$tagList = $tagMatches[1];
						foreach ( $tagList as $tag ) {
							if ( ! preg_match( "/\^/", $tag ) ) {
								if ( (bool)$cleanNonNumerics ) {
									$exTag              = explode( ".", $tag );
									$tagArrayUncombined = array();
									foreach ( $exTag as $val ) {
										if ( is_numeric( $val ) ) {
											$tagArrayUncombined[] = $val;
										} else {
											if ( (bool)$sanitizeNumerics ) {
												$vNum                 = preg_replace( "/[^0-9$]/is", '', $val );
												$tagArrayUncombined[] = $vNum;
											}
										}
									}
									$tag = implode( ".", $tagArrayUncombined );
								}
								// Fill the list here,if it has not already been added
								if ( ! isset( $tagArray[ $tag ] ) ) {
									$tagArray[ $tag ] = $tag;
								}
							}
						}
					}
				} else {
					$exceptionMessage = "Request failure, got $code from URL";
				}
				if ( count( $tagArray ) ) {
					asort( $tagArray, SORT_NATURAL );
					$newArray = array();
					foreach ( $tagArray as $arrayKey => $arrayValue ) {
						$newArray[] = $arrayValue;
					}
					$tagArray = $newArray;
				}
			} catch ( \Exception $gitGetException ) {
				$exceptionMessage = $gitGetException->getMessage();
				$code             = $gitGetException->getCode();
			}
			if ( $fetchFail ) {
				throw new \Exception( $exceptionMessage, $code );
			}

			return $tagArray;
		}

		/**
		 * @param string $myVersion
		 * @param string $gitUrl
		 *
		 * @return array
		 * @throws \Exception
		 * @since 6.0.4
		 */
		public function getMyVersionByGitTag( $myVersion = '', $gitUrl = '' ) {
			$versionArray   = $this->getGitTagsByUrl( $gitUrl, true, true );
			$versionsHigher = array();
			foreach ( $versionArray as $tagVersion ) {
				if ( version_compare( $tagVersion, $myVersion, ">" ) ) {
					$versionsHigher[] = $tagVersion;
				}
			}

			return $versionsHigher;
		}

		/**
		 * Find out if your internal version is older than the tag releases in a git repo
		 *
		 * @param string $myVersion
		 * @param string $gitUrl
		 *
		 * @return bool
		 * @throws \Exception
		 * @since 6.0.4
		 */
		public function getVersionTooOld( $myVersion = '', $gitUrl = '' ) {
			if ( count( $this->getMyVersionByGitTag( $myVersion, $gitUrl ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Extract domain from URL-based string.
		 *
		 * To make a long story short: This is a very unclever function from the birth of the developer (in a era when documentation was not "necessary" to read and stupidity ruled the world).
		 * As some functions still uses this, we chose to keep it, but do it "right".
		 *
		 * @param string $requestedUrlHost
		 * @param bool   $validateHost Validate that the hostname do exist
		 *
		 * @return array
		 * @throws \Exception
		 */
		public function getUrlDomain( $requestedUrlHost = '', $validateHost = false ) {
			// If the scheme is forgotten, add it to keep normal hosts validatable too.
			if ( ! preg_match( "/\:\/\//", $requestedUrlHost ) ) {
				$requestedUrlHost = "http://" . $requestedUrlHost;
			}
			$urlParsed = parse_url( $requestedUrlHost );
			if ( ! isset( $urlParsed['host'] ) || ! $urlParsed['scheme'] ) {
				return array( null, null, null );
			}
			if ( $validateHost || $this->alwaysResolveHostvalidation === true ) {
				// Make sure that the host is not invalid
				if ( filter_var( $requestedUrlHost, FILTER_VALIDATE_URL ) ) {
					$hostRecord = @dns_get_record( $urlParsed['host'], DNS_ANY );
					if ( ! count( $hostRecord ) ) {
						//return array( null, null, null );
						throw new \Exception( NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: Host validation failed", $this->getExceptionCode( 'NETCURL_HOSTVALIDATION_FAIL' ) );
					}
				}
			}

			return array(
				isset( $urlParsed['host'] ) ? $urlParsed['host'] : null,
				isset( $urlParsed['scheme'] ) ? $urlParsed['scheme'] : null,
				isset( $urlParsed['path'] ) ? $urlParsed['path'] : null
			);
		}

		/**
		 * Extract urls from a text string and return as array
		 *
		 * @param       $stringWithUrls
		 * @param int   $offset
		 * @param int   $urlLimit
		 * @param array $protocols
		 * @param bool  $preventDuplicates
		 *
		 * @return array
		 */
		public function getUrlsFromHtml( $stringWithUrls, $offset = - 1, $urlLimit = - 1, $protocols = array( "http" ), $preventDuplicates = true ) {
			$returnArray = array();

			// Pick up all urls by protocol (adding http will include https too)
			foreach ( $protocols as $protocol ) {
				$regex = "@[\"|\']$protocol(.*?)[\"|\']@is";
				preg_match_all( $regex, $stringWithUrls, $matches );
				$urls = array();
				if ( isset( $matches[1] ) && count( $matches[1] ) ) {
					$urls = $matches[1];
				}
				if ( count( $urls ) ) {
					foreach ( $urls as $url ) {
						$trimUrl = trim( $url );
						if ( ! empty( $trimUrl ) ) {
							$prependUrl = $protocol . $url;
							if ( ! $preventDuplicates ) {
								$returnArray[] = $prependUrl;
							} else {
								if ( ! in_array( $prependUrl, $returnArray ) ) {
									$returnArray[] = $prependUrl;
								}
							}
						}
					}
				}
			}
			// Start at a specific offset if defined
			if ( count( $returnArray ) && $offset > - 1 && $offset <= $returnArray ) {
				$allowedOffset  = 0;
				$returnNewArray = array();
				$urlCount       = 0;
				for ( $offsetIndex = 0; $offsetIndex < count( $returnArray ); $offsetIndex ++ ) {
					if ( $offsetIndex == $offset ) {
						$allowedOffset = true;
					}
					if ( $allowedOffset ) {
						// Break when requested limit has beenreached
						$urlCount ++;
						if ( $urlLimit > - 1 && $urlCount > $urlLimit ) {
							break;
						}
						$returnNewArray[] = $returnArray[ $offsetIndex ];
					}
				}
				$returnArray = $returnNewArray;
			}

			return $returnArray;
		}

		/**
		 * Set a cookie
		 *
		 * @param string $name
		 * @param string $value
		 * @param string $expire
		 *
		 * @return bool
		 */
		public function setCookie( $name = '', $value = '', $expire = '' ) {
			$this->setCookieParameters();
			$defaultExpire = time() + 60 * 60 * 24 * 1;
			if ( empty( $expire ) ) {
				$expire = $defaultExpire;
			} else if ( is_string( $expire ) ) {
				$expire = strtotime( $expire );
			}

			return setcookie( $this->cookieDefaultPrefix . $name, $value, $expire, $this->cookieDefaultPath, $this->cookieDefaultDomain, $this->cookieUseSecure );
		}

		/**
		 * Prepare addon parameters for setting a cookie
		 *
		 * @param string $path
		 * @param null   $prefix
		 * @param null   $domain
		 * @param null   $secure
		 */
		public function setCookieParameters( $path = "/", $prefix = null, $domain = null, $secure = null ) {
			$this->cookieDefaultPath = $path;
			if ( empty( $this->cookieDefaultDomain ) ) {
				if ( is_null( $domain ) ) {
					$this->cookieDefaultDomain = "." . $_SERVER['HTTP_HOST'];
				} else {
					$this->cookieDefaultDomain = $domain;
				}
			}
			if ( is_null( $secure ) ) {
				if ( isset( $_SERVER['HTTPS'] ) ) {
					if ( $_SERVER['HTTPS'] == "true" ) {
						$this->cookieUseSecure = true;
					} else {
						$this->cookieUseSecure = false;
					}
				} else {
					$this->cookieUseSecure = false;
				}
			} else {
				$this->cookieUseSecure = $secure;
			}
			if ( ! is_null( $prefix ) ) {
				$this->cookieDefaultPrefix = $prefix;
			}
		}

		/**
		 * Render a list of client ip addresses (if exists). This requires that the server exposes the REMOTE_ADDR
		 *
		 * @return bool If successful, this is true
		 */
		private function renderProxyHeaders() {
			if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$this->clientAddressList = array( 'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] );
				foreach ( $this->proxyHeaders as $proxyVar ) {
					if ( isset( $_SERVER[ $proxyVar ] ) ) {
						$this->clientAddressList[ $proxyVar ] = $_SERVER[ $proxyVar ];
					}
				}

				return true;
			}

			return false;
		}

		/**
		 * Returns a list of header where the browser client might reveal anything about proxy usage.
		 *
		 * @return array
		 */
		public function getProxyHeaders() {
			return $this->clientAddressList;
		}

		/**
		 * Return correct data on https-detection
		 *
		 * @param bool $returnProtocol
		 *
		 * @return bool|string
		 * @since 6.0.3
		 */
		public function getProtocol( $returnProtocol = false ) {
			if ( isset( $_SERVER['HTTPS'] ) ) {
				if ( $_SERVER['HTTPS'] == "on" ) {
					if ( ! $returnProtocol ) {
						return true;
					} else {
						return "https";
					}
				} else {
					if ( ! $returnProtocol ) {
						return false;
					} else {
						return "http";
					}
				}
			}
			if ( ! $returnProtocol ) {
				return false;
			} else {
				return "http";
			}
		}

		/**
		 * Make sure we always return a "valid" http-host from HTTP_HOST. If the variable is missing, this will fall back to localhost.
		 *
		 * @return string
		 * @sice 6.0.15
		 */
		public function getHttpHost() {
			$httpHost = ( isset( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : "" );
			if ( empty( $httpHost ) ) {
				$httpHost = "localhost";
			}

			return $httpHost;
		}

		/**
		 * @param bool $returnProtocol
		 *
		 * @return bool|string
		 * @since 6.0.15
		 */
		public static function getCurrentServerProtocol( $returnProtocol = false ) {
			if ( isset( $_SERVER['HTTPS'] ) ) {
				if ( $_SERVER['HTTPS'] == "on" ) {
					if ( ! $returnProtocol ) {
						return true;
					} else {
						return "https";
					}
				} else {
					if ( ! $returnProtocol ) {
						return false;
					} else {
						return "http";
					}
				}
			}
			if ( ! $returnProtocol ) {
				return false;
			} else {
				return "http";
			}
		}

		/**
		 * Extract domain name (zone name) from hostname
		 *
		 * @param string $useHost Alternative hostname than the HTTP_HOST
		 *
		 * @return string
		 * @throws \Exception
		 * @since 5.0.0
		 */
		public function getDomainName( $useHost = "" ) {
			$currentHost = "";
			if ( empty( $useHost ) ) {
				if ( isset( $_SERVER['HTTP_HOST'] ) ) {
					$currentHost = $_SERVER['HTTP_HOST'];
				}
			} else {
				$extractHost = $this->getUrlDomain( $useHost );
				$currentHost = $extractHost[0];
			}
			// Do this, only if it's a real domain (if scripts are running from console, there might be a loss of this hostname (or if it is a single name, like localhost)
			if ( ! empty( $currentHost ) && preg_match( "/\./", $currentHost ) ) {
				$thisdomainArray = explode( ".", $currentHost );
				if ( is_array( $thisdomainArray ) ) {
					$thisdomain = $thisdomainArray[ count( $thisdomainArray ) - 2 ] . "." . $thisdomainArray[ count( $thisdomainArray ) - 1 ];
				}
			}

			return ( ! empty( $thisdomain ) ? $thisdomain : null );
		}

		/**
		 * base64_encode
		 *
		 * @param $data
		 *
		 * @return string
		 */
		public function base64url_encode( $data ) {
			return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
		}

		/**
		 * base64_decode
		 *
		 * @param $data
		 *
		 * @return string
		 */
		public function base64url_decode( $data ) {
			return base64_decode( str_pad( strtr( $data, '-_', '+/' ), strlen( $data ) % 4, '=', STR_PAD_RIGHT ) );
		}


		/**
		 * Get reverse octets from ip address
		 *
		 * @param string $ipAddr
		 * @param bool   $returnIpType
		 *
		 * @return int|string
		 */
		public function getArpaFromAddr( $ipAddr = '', $returnIpType = false ) {
			if ( filter_var( $ipAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) !== false ) {
				if ( $returnIpType === true ) {
					$vArpaTest = $this->getArpaFromIpv6( $ipAddr );    // PHP 5.3
					if ( ! empty( $vArpaTest ) ) {
						return NETCURL_IP_PROTOCOLS::PROTOCOL_IPV6;
					} else {
						return NETCURL_IP_PROTOCOLS::PROTOCOL_NONE;
					}
				} else {
					return $this->getArpaFromIpv6( $ipAddr );
				}
			} else if ( filter_var( $ipAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false ) {
				if ( $returnIpType ) {
					return NETCURL_IP_PROTOCOLS::PROTOCOL_IPV4;
				} else {
					return $this->getArpaFromIpv4( $ipAddr );
				}
			} else {
				if ( $returnIpType ) {
					return NETCURL_IP_PROTOCOLS::PROTOCOL_NONE;
				}
			}

			return "";
		}

		/**
		 * Get IP range from netmask
		 *
		 * @param null $mask
		 *
		 * @return array
		 */
		function getRangeFromMask( $mask = null ) {
			$addresses = array();
			@list( $ip, $len ) = explode( '/', $mask );
			if ( ( $min = ip2long( $ip ) ) !== false ) {
				$max = ( $min | ( 1 << ( 32 - $len ) ) - 1 );
				for ( $i = $min; $i < $max; $i ++ ) {
					$addresses[] = long2ip( $i );
				}
			}

			return $addresses;
		}

		/**
		 * Test if the given ip address is in the netmask range (not ipv6 compatible yet)
		 *
		 * @param $IP
		 * @param $CIDR
		 *
		 * @return bool
		 */
		public function isIpInRange( $IP, $CIDR ) {
			list ( $net, $mask ) = explode( "/", $CIDR );
			$ip_net    = ip2long( $net );
			$ip_mask   = ~( ( 1 << ( 32 - $mask ) ) - 1 );
			$ip_ip     = ip2long( $IP );
			$ip_ip_net = $ip_ip & $ip_mask;

			return ( $ip_ip_net == $ip_net );
		}

		/**
		 * Translate ipv6 address to reverse octets
		 *
		 * @param string $ipAddr
		 *
		 * @return string
		 */
		public function getArpaFromIpv6( $ipAddr = '::' ) {
			if ( filter_var( $ipAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) === false ) {
				return null;
			}
			$unpackedAddr = @unpack( 'H*hex', inet_pton( $ipAddr ) );
			$hex          = $unpackedAddr['hex'];

			return implode( '.', array_reverse( str_split( $hex ) ) );
		}

		/**
		 * Translate ipv4 address to reverse octets
		 *
		 * @param string $ipAddr
		 *
		 * @return string
		 */
		public function getArpaFromIpv4( $ipAddr = '127.0.0.1' ) {
			if ( filter_var( $ipAddr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) !== false ) {
				return implode( ".", array_reverse( explode( ".", $ipAddr ) ) );
			}

			return null;
		}

		/**
		 * Translate ipv6 reverse octets to ipv6 address
		 *
		 * @param string $arpaOctets
		 *
		 * @return string
		 */
		public function getIpv6FromOctets( $arpaOctets = '0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0.0' ) {
			return @inet_ntop( pack( 'H*', implode( "", array_reverse( explode( ".", preg_replace( "/\.ip6\.arpa$|\.ip\.int$/", '', $arpaOctets ) ) ) ) ) );
		}

		function Redirect( $redirectToUrl = '', $replaceHeader = false, $responseCode = 301 ) {
			header( "Location: $redirectToUrl", $replaceHeader, $responseCode );
			exit;
		}

		/**
		 * When active: Force this libray to always validate hosts with a DNS resolve during a getUrlDomain()-call.
		 *
		 * @param bool $activate
		 */
		public function setAlwaysResolveHostvalidation( $activate = false ) {
			$this->alwaysResolveHostvalidation = $activate;
		}

		/**
		 * Return the current boolean value for alwaysResolveHostvalidation.
		 */
		public function getAlwaysResolveHostvalidation() {
			$this->alwaysResolveHostvalidation;
		}
	}
}

if ( ! class_exists( 'TorneLIB_Network', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\TorneLIB_Network', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	/**
	 * Class MODULE_CURL
	 *
	 * @package    TorneLIB
	 * @deprecated 6.0.20 Use MODULE_NETWORK
	 */
	class TorneLIB_Network extends MODULE_NETWORK {
		function __construct() {
			parent::__construct();
		}
	}
}

if (! class_exists( 'MODULE_CURL', NETCURL_CLASS_EXISTS_AUTOLOAD) && ! class_exists( 'Resursbank\RBEcomPHP\MODULE_CURL', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
	if ( ! defined( 'NETCURL_CURL_RELEASE' ) ) {
		define( 'NETCURL_CURL_RELEASE', '6.0.23RC1' );
	}
	if ( ! defined( 'NETCURL_CURL_MODIFY' ) ) {
		define( 'NETCURL_CURL_MODIFY', '20180822' );
	}
	if ( ! defined( 'NETCURL_CURL_CLIENTNAME' ) ) {
		define( 'NETCURL_CURL_CLIENTNAME', 'MODULE_CURL' );
	}

	/**
	 * Class MODULE_CURL
	 *
	 * @package TorneLIB
	 * @link    https://docs.tornevall.net/x/KQCy TorneLIBv5
	 * @link    https://bitbucket.tornevall.net/projects/LIB/repos/tornelib-php-netcurl/browse Sources of TorneLIB
	 * @link    https://docs.tornevall.net/x/KwCy Network & Curl v5 and v6 Library usage
	 * @link    https://docs.tornevall.net/x/FoBU TorneLIB Full documentation
	 * @since   6.0.20
	 */
	class MODULE_CURL {

		//// PUBLIC VARIABLES
		/**
		 * Default settings when initializing our curlsession.
		 *
		 * Since v6.0.2 no urls are followed by default, it is set internally by first checking PHP security before setting this up.
		 * The reason of the change is not only the security, it is also about inheritage of options to SOAPClient.
		 *
		 * @var array
		 */
		private $curlopt = array(
			CURLOPT_CONNECTTIMEOUT => 6,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => 1,
			CURLOPT_SSL_VERIFYHOST => 2,
			CURLOPT_ENCODING       => 1,
			CURLOPT_TIMEOUT        => 10,
			CURLOPT_USERAGENT      => 'TorneLIB-PHPcURL',
			CURLOPT_POST           => true,
			CURLOPT_SSLVERSION     => 4,
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_HTTPHEADER     => array( 'Accept-Language: en' ),
		);
		/** @var array User set SSL Options */
		private $sslopt = array();

		/** @var string $netCurlUrl Where to find NetCurl */
		private $netCurlUrl = 'http://www.netcurl.org/';

		/** @var array $NETCURL_POST_DATA Could also be a string */
		private $NETCURL_POST_DATA = array();
		private $NETCURL_POST_PREPARED_XML = '';
		/** @var NETCURL_POST_METHODS $NETCURL_POST_METHOD */
		private $NETCURL_POST_METHOD = NETCURL_POST_METHODS::METHOD_GET;
		/** @var NETCURL_POST_DATATYPES $NETCURL_POST_DATA_TYPE */
		private $NETCURL_POST_DATA_TYPE = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET;

		private $NETCURL_ERRORHANDLER_HAS_ERRORS = false;
		private $NETCURL_ERRORHANDLER_RERUN = false;

		//// PUBLIC CONFIG THAT SHOULD GO PRIVATE
		/** @var array Interfaces to use */
		public $IpAddr = array();
		/** @var bool If more than one ip is set in the interfaces to use, this will make the interface go random */
		public $IpAddrRandom = true;
		/** @var null Sets a HTTP_REFERER to the http call */
		private $NETCURL_HTTP_REFERER;

		/** @var $POST_DATA_HANDLED */
		private $POST_DATA_HANDLED;
		/** @var $POSTDATACONTAINER */
		private $POSTDATACONTAINER;
		/** @var string $POST_DATA_REAL Post data as received from client */
		private $POST_DATA_REAL;

		/** @var array $NETCURL_RESPONSE_CONTAINER */
		protected $NETCURL_RESPONSE_CONTAINER;
		protected $NETCURL_RESPONSE_CONTAINER_PARSED;
		protected $NETCURL_RESPONSE_CONTAINER_BODY;
		protected $NETCURL_RESPONSE_CONTAINER_CODE;
		protected $NETCURL_RESPONSE_CONTAINER_HTTPMESSAGE;
		protected $NETCURL_RESPONSE_CONTAINER_HEADER;
		protected $NETCURL_RESPONSE_RAW;
		protected $NETCURL_REQUEST_HEADERS;
		protected $NETCURL_REQUEST_BODY;

		/**
		 * When you just need output responses and nothing else (except for exceptions)
		 *
		 * @var bool $NETCURL_SIMPLIFY_RESPONSES
		 * @since 6.0.21
		 */
		protected $NETCURL_SIMPLIFY_RESPONSES = false;

		/**
		 * Allow domcontent to be parsed in simplified mode
		 *
		 * @var bool
		 * @since 6.0.21
		 */
		protected $NETCURL_SIMPLIFY_DOMCONTENT = false;

		/**
		 * Do not include Dom content in the basic parser (default = true, as it might destroy output data in legacy products)
		 *
		 * @var bool $NETCURL_PROHIBIT_DOMCONTENT_PARSE
		 * @since 6.0.22
		 */
		private $NETCURL_PROHIBIT_DOMCONTENT_PARSE = true;

		/**
		 * Will be set to true if the parser passed DOM-content analyze
		 *
		 * @var bool
		 * @since 6.0.21
		 */
		protected $NETCURL_CONTENT_IS_DOMCONTENT = false;

		private $userAgents = array(
			'Mozilla' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0;)'
		);

		/**
		 * Die on use of proxy/tunnel on first try (Incomplete).
		 *
		 * This function is supposed to stop if the proxy fails on connection, so the library won't continue looking for a preferred exit point, since that will reveal the current unproxified address.
		 *
		 * @var bool
		 */
		private $DIE_ON_LOST_PROXY = true;

		//// PRIVATE AND PROTECTED VARIABLES VARIABLES
		/**
		 * Prepare MODULE_NETWORK class if it exists (as of the november 2016 it does).
		 *
		 * @var MODULE_NETWORK
		 */
		private $NETWORK;
		/**
		 * @var NETCURL_DRIVER_CONTROLLER $DRIVER Communications driver controller
		 */
		private $DRIVER;
		/**
		 * @var MODULE_IO $IO
		 */
		private $IO;

		/** @var MODULE_SSL */
		private $SSL;
		private $TRUST_SSL_BUNDLES = false;

		/** @var null Our communication channel */
		private $NETCURL_CURL_SESSION = null;
		/** @var null URL that was set to communicate with */
		private $CURL_STORED_URL = null;
		/**
		 * @var array $internalFlags Flags controller to change behaviour on internal function
		 * Chaining should eventually be default active in future (6.1?+)
		 */
		private $internalFlags = array( 'CHAIN' => true, 'SOAPCHAIN' => true );

		/**
		 * @var string $contentType Pre-Set content type, when installed modules needs to know in what format we are sending data
		 */
		private $contentType = '';

		/**
		 * @var array $DEBUG_DATA Debug data stored from session
		 */
		private $DEBUG_DATA = array(
			'data'     => array(
				'info' => array()
			),
			'soapData' => array(
				'info' => array()
			),
			'calls'    => 0
		);
		/**
		 * @var array Storage of invisible errors
		 * @since 6.0.20
		 */
		private $NETCURL_ERROR_CONTAINER = array();

		//// SSL AUTODETECTION CAPABILITIES
		/// DEFAULT: Most of the settings are set to be disabled, so that the system handles this automatically with defaults
		/// If there are problems reaching wsdl or connecting to https-based URLs, try set $testssl to true

		/**
		 * @var bool If SSL has been compiled in CURL, this will transform to true
		 * @since 6.0.20
		 */
		private $CURL_SSL_AVAILABLE = false;

		//// IP AND PROXY CONFIG
		private $CURL_IP_ADDRESS = null;
		private $CURL_IP_ADDRESS_TYPE = null;
		/** @var null CurlProxy, if set, we will try to proxify the traffic */
		private $CURL_PROXY_GATEWAY = null;
		/** @var null, if not set, but CurlProxy is, we will use HTTP as proxy (See CURLPROXY_* for more information) */
		private $CURL_PROXY_TYPE = null;
		/** @var bool Enable tunneling mode */
		private $CURL_TUNNEL = false;

		//// URL REDIRECT
		/** @var bool Decide whether the curl library should follow an url redirect or not */
		private $FOLLOW_LOCATION_ENABLE = true;
		/**
		 * @var array $REDIRECT_URLS List of redirections during curl calls
		 */
		private $REDIRECT_URLS = array();

		//// POST-GET-RESPONSE
		/**
		 * @var null A tempoary set of the response from the url called
		 * @deprecated 6.0.20
		 */
		private $TemporaryResponse = null;
		/**
		 * @var null Temporary response from external driver
		 * @deprecated 6.0.20
		 */
		private $TemporaryExternalResponse = null;

		/**
		 * @var NETCURL_POST_DATATYPES $FORCE_POST_TYPE What post type to use when using POST (Enforced)
		 */
		private $FORCE_POST_TYPE = null;
		/**
		 * @var string Current encoding
		 */
		public $HTTP_CHARACTER_ENCODING = null;
		/**
		 * @var array $CURL_RETRY_TYPES Counter for how many tries that has been done in a call
		 */
		private $CURL_RETRY_TYPES = array( 'resolve' => 0, 'sslunverified' => 0 );
		/** @var string Custom User-Agent sent in the HTTP-HEADER */
		private $HTTP_USER_AGENT;
		/**
		 * @var array Custom User-Agent Memory
		 */
		private $CUSTOM_USER_AGENT = array();
		/**
		 * @var bool Try to automatically parse the retrieved body content. Supports, amongst others json, serialization, etc
		 * @deprecated 6.0.20
		 */
		public $CurlAutoParse = true;
		private $NETCURL_RETURN_RESPONSE_TYPE = NETCURL_RESPONSETYPE::RESPONSETYPE_ARRAY;
		/** @var array Authentication */
		private $AuthData = array(
			'Username' => null,
			'Password' => null,
			'Type'     => NETCURL_AUTH_TYPES::AUTHTYPE_NONE
		);
		/** @var array Adding own headers to the HTTP-request here */
		private $NETCURL_HTTP_HEADERS = array();
		private $NETCURL_HEADERS_SYSTEM_DEFINED = array();
		private $NETCURL_HEADERS_USER_DEFINED = array();
		private $allowCdata = false;
		private $useXmlSerializer = false;
		/**
		 * Store information about the URL call and if the SSL was unsafe (disabled)
		 *
		 * @var bool
		 */
		protected $unsafeSslCall = false;

		//// COOKIE CONFIGS
		private $useLocalCookies = false;
		/**
		 * To which path we store cookies
		 *
		 * @var string $COOKIE_PATH
		 */
		private $COOKIE_PATH = '';
		/**
		 * Allow saving cookies
		 *
		 * @var bool
		 */
		private $SaveCookies = false;
		/**
		 * @var string $CookieFile The name of the file to save cookies in
		 */
		private $CookieFile = '';
		/**
		 * @var bool $UseCookieExceptions
		 * @deprecated 6.0.20
		 */
		private $UseCookieExceptions = false;
		/**
		 * @var bool $CurlUseCookies
		 * @deprecated 6.0.20
		 */
		public $CurlUseCookies = true;
		/**
		 * @var bool
		 * @since 6.0.20
		 */
		private $NETCURL_USE_COOKIES = true;

		//// RESOLVING AND TIMEOUTS

		/**
		 * @var NETCURL_RESOLVER $CURL_RESOLVE_TYPE
		 * @since 6.0.20
		 */
		private $CURL_RESOLVE_TYPE = NETCURL_RESOLVER::RESOLVER_DEFAULT;
		/**
		 * @var bool
		 */
		private $CURL_RESOLVER_FORCED = false;

		/** @var string Sets another timeout in seconds when curl_exec should finish the current operation. Sets both TIMEOUT and CONNECTTIMEOUT */
		private $NETCURL_CURL_TIMEOUT;

		//// EXCEPTION HANDLING
		/** @var array Throwable http codes */
		private $throwableHttpCodes;
		/** @var bool By default, this library does not store any curl_getinfo during exceptions */
		private $canStoreSessionException = false;
		/** @var array An array that contains each curl_exec (curl_getinfo) when an exception are thrown */
		private $sessionsExceptions = array();
		/** @var bool The soapTryOnce variable */
		private $SoapTryOnce = true;
		private $curlConstantsOpt = array();
		private $curlConstantsErr = array();

		/**
		 * Set up if this library can throw exceptions, whenever it needs to do that.
		 *
		 * Note: This does not cover everything in the library. It was set up for handling SoapExceptions.
		 *
		 * @var bool
		 * @deprecated 6.0.20
		 */
		public $canThrow = true;

		/**
		 * @var bool
		 * @since 6.0.20
		 */
		private $NETCURL_CAN_THROW = true;

		/**
		 * MODULE_CURL constructor.
		 *
		 * @param string $requestUrl
		 * @param array  $requestPostData
		 * @param int    $requestPostMethod
		 * @param array  $requestFlags
		 *
		 * @throws \Exception
		 */
		public function __construct( $requestUrl = '', $requestPostData = array(), $requestPostMethod = null, $requestFlags = array() ) {
			register_shutdown_function( array( $this, 'netcurl_terminate' ) );

			if ( ! is_null( $requestPostData ) ) {
				$requestPostData = array();
			}

			// PHP versions not supported to chaining gets the chaining parameter disabled by default.
			if ( version_compare( PHP_VERSION, "5.4.0", "<" ) ) {
				// Something really magic happens in PHP 5.3 with default request method, so instead we default this to GET
				// instead of POST if running lower versions.
				if ( is_null( $requestPostMethod ) ) {
					$requestPostMethod = NETCURL_POST_METHODS::METHOD_GET;
				}

				try {
					$this->setFlag( 'NOCHAIN', true );
				} catch ( \Exception $ignoreEmptyException ) {
					// This will never occur
				}
			}
			if ( is_null( $requestPostMethod ) ) {
				$requestPostMethod = NETCURL_POST_METHODS::METHOD_POST;
			}
			if ( is_array( $requestFlags ) && count( $requestFlags ) ) {
				$this->setFlags( $requestFlags );
			}

			$this->NETWORK = new MODULE_NETWORK();
			$this->DRIVER  = new NETCURL_DRIVER_CONTROLLER();
			if ( class_exists( 'TorneLIB\MODULE_IO', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
				$this->IO = new MODULE_IO();
			}
			$this->setConstantsContainer();
			$this->setPreparedAuthentication();
			$this->CURL_RESOLVE_TYPE  = NETCURL_RESOLVER::RESOLVER_DEFAULT;
			$this->throwableHttpCodes = array();
			$this->getSslDriver();

			$this->HTTP_USER_AGENT = $this->userAgents['Mozilla'] . ' ' . NETCURL_CURL_CLIENTNAME . '-' . NETCURL_RELEASE . "/" . __CLASS__ . "-" . NETCURL_CURL_RELEASE . ' (' . $this->netCurlUrl . ')';
			if ( ! empty( $requestUrl ) ) {
				$this->CURL_STORED_URL = $requestUrl;
				$InstantResponse       = null;
				if ( $requestPostMethod == NETCURL_POST_METHODS::METHOD_GET ) {
					$InstantResponse = $this->doGet( $requestUrl );
				} else if ( $requestPostMethod == NETCURL_POST_METHODS::METHOD_POST ) {
					$InstantResponse = $this->doPost( $requestUrl, $requestPostData );
				} else if ( $requestPostMethod == NETCURL_POST_METHODS::METHOD_PUT ) {
					$InstantResponse = $this->doPut( $requestUrl, $requestPostData );
				} else if ( $requestPostMethod == NETCURL_POST_METHODS::METHOD_DELETE ) {
					$InstantResponse = $this->doDelete( $requestUrl, $requestPostData );
				}

				return $InstantResponse;
			}

			return null;
		}

		/**
		 * @deprecated 6.0.20
		 * @throws \Exception
		 */
		public function init() {
			$this->initializeNetCurl();
		}

		/**
		 * Termination Controller
		 *
		 * As of 6.0.20 cookies will be only stored if there is a predefined cookiepath or if system tempdir is allowed
		 *
		 * @since 5.0
		 */
		function netcurl_terminate() {
		}

		/**
		 * Initialize NetCURL module and requirements
		 *
		 * @return resource
		 * @throws \Exception
		 * @since 6.0.20
		 */
		public function initializeNetCurl() {
			$this->initCookiePath();
			if ( ! $this->isFlag( 'NOTHROWABLES' ) ) {
				$this->setThrowableHttpCodes();
			}
			if ( ! is_object( $this->DRIVER->getDriver() ) && $this->DRIVER->getDriver() == NETCURL_NETWORK_DRIVERS::DRIVER_CURL ) {
				$this->initCurl();
			}

			return $this->NETCURL_CURL_SESSION;
		}

		/**
		 * Store constants of curl errors and curlOptions
		 *
		 * @since 6.0.20
		 */
		private function setConstantsContainer() {
			try {
				$constants = @get_defined_constants();
				foreach ( $constants as $constKey => $constInt ) {
					if ( preg_match( "/^curlopt/i", $constKey ) ) {
						$this->curlConstantsOpt[ $constInt ] = $constKey;
					}
					if ( preg_match( "/^curle/i", $constKey ) ) {
						$this->curlConstantsErr[ $constInt ] = $constKey;
					}
				}
			} catch ( \Exception $constantException ) {
			}
			unset( $constants );
		}

		/**
		 * Set up authentication
		 *
		 * @since 6.0.20
		 * @throws \Exception
		 */
		private function setPreparedAuthentication() {
			$authFlags = $this->getFlag( 'auth' );
			if ( is_array( $authFlags ) && isset( $authFlags['username'] ) && isset( $authFlags['password'] ) ) {
				$this->setAuthentication( $authFlags['username'], $authFlags['password'], isset( $authFlags['type'] ) ? $authFlags['type'] : NETCURL_AUTH_TYPES::AUTHTYPE_BASIC );
			}
		}

		/**
		 * Initialize SSL driver and prepare
		 *
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function getSslDriver() {
			$curlSslDriver = MODULE_SSL::getCurlSslAvailable();
			// If no errors occurs here, we'll say that SSL is available on the system
			if ( ! count( $curlSslDriver ) ) {
				$this->CURL_SSL_AVAILABLE = true;
			}
			$this->SSL = new MODULE_SSL( $this );
		}

		/**
		 * Ask this module whether there are available modules for use with http calls or not. Can also be set up to return a complete list of modules
		 *
		 * @return bool|array
		 * @since      6.0.14
		 * @deprecated Use NETCURL_DRIVER_CONTROLLER::
		 */
		public function getAvailableDrivers() {
			return $this->DRIVER->getSystemWideDrivers();
		}

		/**
		 * Get a list of all available and supported Addons for the module
		 *
		 * @return array
		 * @throws \Exception
		 * @since 6.0.14
		 * @deprecated
		 */
		public function getSupportedDrivers() {
			return $this->DRIVER->getSystemWideDrivers();
		}

		/**
		 * Is internal curl configured?
		 *
		 * @return bool
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function isCurl() {
			try {
				if ( ! is_object( $this->DRIVER->getDriver() ) && $this->DRIVER->getDriver() == NETCURL_NETWORK_DRIVERS::DRIVER_CURL ) {
					return true;
				}
			} catch ( \Exception $e ) {

			}

			return false;
		}

		/**
		 * Automatically find the best suited driver for communication IF curl does not exist. If curl exists, internal driver will always be picked as first option
		 *
		 * @return int|null|string
		 * @throws \Exception
		 * @since 6.0.14
		 */
		public function setDriverAuto() {
			return $this->DRIVER->getAutodetectedDriver();
		}

		/**
		 * @return array
		 * @since 6.0
		 */
		public function getDebugData() {
			return $this->DEBUG_DATA;
		}

		/**
		 * @param bool $useCookies
		 *
		 * @since 6.0.20
		 */
		public function setUseCookies( $useCookies = true ) {
			$this->NETCURL_USE_COOKIES = $useCookies;
		}

		/**
		 * @return bool
		 * @since 6.0.20
		 */
		public function getUseCookies() {
			return $this->NETCURL_USE_COOKIES;
		}

		/**
		 * @param int $curlResolveType
		 *
		 * @since 6.0.20
		 */
		public function setCurlResolve( $curlResolveType = NETCURL_RESOLVER::RESOLVER_DEFAULT ) {
			$this->CURL_RESOLVE_TYPE = $curlResolveType;
		}

		/**
		 * @return NETCURL_RESOLVER
		 * @since 6.0.20
		 */
		public function getCurlResove() {
			return $this->CURL_RESOLVE_TYPE;
		}

		/**
		 * Enable or disable the ability to let netcurl throw exceptions on places where it is not always necessary.
		 *
		 * This function has minor effects on newer netcurls since throwing exxceptions should be considered necessary in many situations to handle errors.
		 *
		 * @param bool $netCurlCanThrow
		 *
		 * @since 6.0.20
		 */
		public function setThrowable( $netCurlCanThrow = true ) {
			$this->NETCURL_CAN_THROW = $netCurlCanThrow;
		}

		/**
		 * Is netcurl allowed to throw exceptions on places where it is not always necessary?
		 *
		 * @return bool
		 * @since 6.0.20
		 */
		public function getThrowable() {
			return $this->NETCURL_CAN_THROW;
		}

		/**
		 * When you just need responses and nothing else (except for exceptions)
		 *
		 * Activation means you will always get a proper response back, on http requests (defaults to parsed content, but if the parse is empty, we will fall back on the body parts and if bodyparts is empty netcurl will fall back to an array called simplifiedContainer).
		 *
		 * @param bool $simplifyResponses
		 * @param bool $allowDomTree
		 */
		public function setSimplifiedResponse( $simplifyResponses = true, $allowDomTree = false ) {
			$this->NETCURL_SIMPLIFY_RESPONSES  = $simplifyResponses;
			$this->NETCURL_SIMPLIFY_DOMCONTENT = $allowDomTree;
		}

		/**
		 * Get the status of the simplified responses setting
		 *
		 * @return bool
		 * @since 6.0.21
		 */
		public function getSimplifiedResponse() {
			return $this->NETCURL_SIMPLIFY_RESPONSES;
		}

		/**
		 * Enable/disable the parsing of Dom content
		 *
		 * @param bool $domContentProhibit
		 *
		 * @since 6.0.22
		 */
		public function setDomContentParser( $domContentProhibit = false ) {
			$this->NETCURL_PROHIBIT_DOMCONTENT_PARSE = $domContentProhibit;
		}

		/**
		 * Get the status of dom content parser mode
		 *
		 * @return bool
		 * @since 6.0.22
		 */
		public function getDomContentParser() {
			return $this->NETCURL_PROHIBIT_DOMCONTENT_PARSE;
		}

		/**
		 * @param array $arrayData
		 *
		 * @return bool
		 * @since 6.0
		 */
		function isAssoc( array $arrayData ) {
			if ( array() === $arrayData ) {
				return false;
			}

			return array_keys( $arrayData ) !== range( 0, count( $arrayData ) - 1 );
		}

		/**
		 * Set multiple flags
		 *
		 * @param array $flags
		 *
		 * @throws \Exception
		 * @since 6.0.10
		 */
		private function setFlags( $flags = array() ) {
			if ( $this->isAssoc( $flags ) ) {
				foreach ( $flags as $flagKey => $flagData ) {
					$this->setFlag( $flagKey, $flagData );
				}
			} else {
				foreach ( $flags as $flagKey ) {
					$this->setFlag( $flagKey, true );
				}
			}
			if ( $this->isFlag( 'NOCHAIN' ) ) {
				$this->unsetFlag( 'CHAIN' );
			}
		}

		/**
		 * Return all flags
		 *
		 * @return array
		 *
		 * @since 6.0.10
		 */
		public function getFlags() {
			return $this->internalFlags;
		}

		/**
		 * @param string $setContentTypeString
		 *
		 * @since 6.0.17
		 */
		public function setContentType( $setContentTypeString = 'application/json; charset=utf-8' ) {
			$this->contentType = $setContentTypeString;
		}

		/**
		 * @since 6.0.17
		 */
		public function getContentType() {
			return $this->contentType;
		}

		/**
		 * @param int   $driverId
		 * @param array $parameters
		 * @param null  $ownClass
		 *
		 * @return int|NETCURL_DRIVERS_INTERFACE
		 * @throws \Exception
		 * @since 6.0.20
		 */
		public function setDriver( $driverId = NETCURL_NETWORK_DRIVERS::DRIVER_NOT_SET, $parameters = array(), $ownClass = null ) {
			return $this->DRIVER->setDriver( $driverId, $parameters, $ownClass );
		}

		/**
		 * Returns current chosen driver (if none is preset and curl exists, we're trying to use internals)
		 *
		 * @param bool $byId
		 *
		 * @return int|NETCURL_DRIVERS_INTERFACE
		 * @throws \Exception
		 * @since 6.0.15
		 */
		public function getDriver( $byId = false ) {
			if ( ! $byId ) {
				$this->currentDriver = $this->DRIVER->getDriver();
			} else {
				if ( $this->isFlag( 'IS_SOAP' ) ) {
					return NETCURL_NETWORK_DRIVERS::DRIVER_SOAPCLIENT;
				}

				return $this->DRIVER->getDriverById();
			}

			return $this->currentDriver;
		}

		/**
		 * @return int|NETCURL_DRIVERS_INTERFACE
		 * @throws \Exception
		 * @since 6.0.20
		 */
		public function getDriverById() {
			return $this->getDriver( true );
		}

		/**
		 * Get current configured http-driver
		 *
		 * @return mixed
		 * @throws \Exception
		 * @since      6.0.14
		 * @deprecated 6.0.20
		 */
		public function getDrivers() {
			/** @noinspection PhpDeprecationInspection */
			return $this->getAvailableDrivers();
		}

		/**
		 * Set timeout for CURL, normally we'd like a quite short timeout here. Default: CURL default
		 *
		 * Affects connect and response timeout by below values:
		 *   CURLOPT_CONNECTTIMEOUT = ceil($timeout/2)    - How long a request is allowed to wait for conneciton, curl default = 300
		 *   CURLOPT_TIMEOUT = ceil($timeout)             - How long a request is allowed to take, curl default = never timeout (0)
		 *
		 * @param int $timeout
		 *
		 * @since 6.0.13
		 */
		public function setTimeout( $timeout = 6 ) {
			$this->NETCURL_CURL_TIMEOUT = $timeout;
		}

		/**
		 * Get current timeout setting
		 *
		 * @return array
		 * @since 6.0.13
		 */
		public function getTimeout() {
			$returnTimeouts = array(
				'connecttimeout' => ceil( $this->NETCURL_CURL_TIMEOUT / 2 ),
				'requesttimeout' => ceil( $this->NETCURL_CURL_TIMEOUT )
			);
			if ( empty( $this->NETCURL_CURL_TIMEOUT ) ) {
				$returnTimeouts = array(
					'connecttimeout' => 300,
					'requesttimeout' => 0
				);
			}

			return $returnTimeouts;
		}

		/**
		 * Initialize cookie handler
		 *
		 * @return bool
		 * @since 6.0
		 */
		private function initCookiePath() {
			// Method rewrite as of NetCurl 6.0.20
			if ( $this->isFlag( 'NETCURL_DISABLE_CURL_COOKIES' ) || ! $this->useLocalCookies ) {
				return false;
			}

			try {
				$ownCookiePath = $this->getFlag( 'NETCURL_COOKIE_LOCATION' );
				if ( ! empty( $ownCookiePath ) ) {
					return $this->setCookiePathUserDefined( $ownCookiePath );
				}

				return $this->setCookiePathBySystem();

			} catch ( \Exception $e ) {
				// Something happened, so we won't try this again
				return false;
			}
		}

		/**
		 * Sets, if defined by user, up a cookie directory storage
		 *
		 * @param $ownCookiePath
		 *
		 * @return bool
		 * @since 6.0.20
		 */
		private function setCookiePathUserDefined( $ownCookiePath ) {
			if ( is_dir( $ownCookiePath ) ) {
				$this->COOKIE_PATH = $ownCookiePath;

				return true;
			} else {
				@mkdir( $ownCookiePath );
				if ( is_dir( $ownCookiePath ) ) {
					$this->COOKIE_PATH = $ownCookiePath;

					return true;
				}

				return false;
			}

		}

		/**
		 * Sets up cookie path if allowed, to system default storage path
		 *
		 * @return bool
		 * @since 6.0.20
		 */
		private function setCookiePathBySystem() {
			$sysTempDir = sys_get_temp_dir();
			if ( empty( $this->COOKIE_PATH ) ) {
				if ( $this->isFlag( 'NETCURL_COOKIE_TEMP_LOCATION' ) ) {
					if ( ! empty( $sysTempDir ) ) {
						if ( is_dir( $sysTempDir ) ) {
							$this->COOKIE_PATH = $sysTempDir;
							@mkdir( $sysTempDir . "/netcurl/" );
							if ( is_dir( $sysTempDir . "/netcurl/" ) ) {
								$this->COOKIE_PATH = $sysTempDir . "/netcurl/";
							}

							return true;
						} else {
							return false;
						}
					}
				}
			}

			return false;
		}

		/**
		 * Set internal flag parameter.
		 *
		 * @param string $flagKey
		 * @param string $flagValue Nullable since 6.0.10 = If null, then it is considered a true boolean, set setFlag("key") will always be true as an activation key
		 *
		 * @return bool If successful
		 * @throws \Exception
		 * @since 6.0.9
		 */
		public function setFlag( $flagKey = '', $flagValue = null ) {
			if ( ! empty( $flagKey ) ) {
				if ( is_null( $flagValue ) ) {
					$flagValue = true;
				}
				$this->internalFlags[ $flagKey ] = $flagValue;

				return true;
			}
			throw new \Exception( "Flags can not be empty", $this->NETWORK->getExceptionCode( 'NETCURL_SETFLAG_KEY_EMPTY' ) );
		}

		/**
		 * @param string $flagKey
		 *
		 * @return bool
		 * @since 6.0.10
		 */
		public function unsetFlag( $flagKey = '' ) {
			if ( $this->hasFlag( $flagKey ) ) {
				unset( $this->internalFlags[ $flagKey ] );

				return true;
			}

			return false;
		}

		/**
		 * @param string $flagKey
		 *
		 * @return bool
		 * @since 6.0.13 Consider using unsetFlag
		 */
		public function removeFlag( $flagKey = '' ) {
			return $this->unsetFlag( $flagKey );
		}

		/**
		 * @param string $flagKey
		 *
		 * @return bool
		 * @since 6.0.13 Consider using unsetFlag
		 */
		public function deleteFlag( $flagKey = '' ) {
			return $this->unsetFlag( $flagKey );
		}

		/**
		 * @since 6.0.13
		 */
		public function clearAllFlags() {
			$this->internalFlags = array();
		}

		/**
		 * Get internal flag
		 *
		 * @param string $flagKey
		 *
		 * @return mixed|null
		 * @since 6.0.9
		 */
		public function getFlag( $flagKey = '' ) {
			if ( isset( $this->internalFlags[ $flagKey ] ) ) {
				return $this->internalFlags[ $flagKey ];
			}

			return null;
		}

		/**
		 * Check if flag is set and true
		 *
		 * @param string $flagKey
		 *
		 * @return bool
		 * @since 6.0.9
		 */
		public function isFlag( $flagKey = '' ) {
			if ( $this->hasFlag( $flagKey ) ) {
				return ( $this->getFlag( $flagKey ) === 1 || $this->getFlag( $flagKey ) === true ? true : false );
			}

			return false;
		}

		/**
		 * Check if there is an internal flag set with current key
		 *
		 * @param string $flagKey
		 *
		 * @return bool
		 * @since 6.0.9
		 */
		public function hasFlag( $flagKey = '' ) {
			if ( ! is_null( $this->getFlag( $flagKey ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Enable chained mode ($Module->doGet(URL)->getParsedResponse()"
		 *
		 * @param bool $enable
		 *
		 * @throws \Exception
		 * @since 6.0.14
		 */
		public function setChain( $enable = true ) {
			if ( $enable ) {
				$this->setFlag( 'CHAIN' );
			} else {
				$this->unsetFlag( 'CHAIN' );
			}
		}

		/**
		 * @return bool
		 * @since 6.0
		 */
		public function getIsChained() {
			return $this->isFlag( 'CHAIN' );
		}

		//// EXCEPTION HANDLING

		/**
		 * Throw on any code that matches the store throwableHttpCode (use with setThrowableHttpCodes())
		 *
		 * @param string $message
		 * @param string $code
		 *
		 * @throws \Exception
		 * @since 6.0.6
		 */
		private function throwCodeException( $message = '', $code = '' ) {
			if ( ! is_array( $this->throwableHttpCodes ) ) {
				$this->throwableHttpCodes = array();
			}
			foreach ( $this->throwableHttpCodes as $codeListArray => $codeArray ) {
				if ( isset( $codeArray[1] ) && $code >= intval( $codeArray[0] ) && $code <= intval( $codeArray[1] ) ) {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " HTTP Response Exception: " . $message, $code );
				}
			}
		}

		//// SESSION

		/**
		 * Returns an ongoing cUrl session - Normally you may get this from initSession (and normally you don't need this at all)
		 *
		 * @return null
		 * @since 6.0
		 */
		public function getCurlSession() {
			return $this->NETCURL_CURL_SESSION;
		}


		//// PUBLIC SETTERS & GETTERS

		/**
		 * Allow fallback tests in SOAP mode
		 *
		 * Defines whether, when there is a SOAP-call, we should try to make the SOAP initialization twice.
		 * This is a kind of fallback when users forget to add ?wsdl or &wsdl in urls that requires this to call for SOAP.
		 * It may happen when setting NETCURL_POST_DATATYPES to a SOAP-call but, the URL is not defined as one.
		 * Setting this to false, may suppress important errors, since this will suppress fatal errors at first try.
		 *
		 * @param bool $enabledMode
		 *
		 * @since 6.0.9
		 */
		public function setSoapTryOnce( $enabledMode = true ) {
			$this->SoapTryOnce = $enabledMode;
		}

		/**
		 * Get the state of soapTryOnce
		 *
		 * @return bool
		 * @since 6.0.9
		 */
		public function getSoapTryOnce() {
			return $this->SoapTryOnce;
		}


		/**
		 * Set the curl libraray to die, if no proxy has been successfully set up (Currently not active in module)
		 *
		 * @param bool $dieEnabled
		 *
		 * @since 6.0.9
		 */
		public function setDieOnNoProxy( $dieEnabled = true ) {
			$this->DIE_ON_LOST_PROXY = $dieEnabled;
		}

		/**
		 * Get the state of whether the library should bail out if no proxy has been successfully set
		 *
		 * @return bool
		 * @since 6.0.9
		 */
		public function getDieOnNoProxy() {
			return $this->DIE_ON_LOST_PROXY;
		}

		/**
		 * Set up a list of which HTTP error codes that should be throwable (default: >= 400, <= 599)
		 *
		 * @param int $throwableMin Minimum value to throw on (Used with >=)
		 * @param int $throwableMax Maxmimum last value to throw on (Used with <)
		 *
		 * @since 6.0.6
		 */
		public function setThrowableHttpCodes( $throwableMin = 400, $throwableMax = 599 ) {
			$throwableMin               = intval( $throwableMin ) > 0 ? $throwableMin : 400;
			$throwableMax               = intval( $throwableMax ) > 0 ? $throwableMax : 599;
			$this->throwableHttpCodes[] = array( $throwableMin, $throwableMax );
		}

		/**
		 * Return the list of throwable http error codes (if set)
		 *
		 * @return array
		 * @since 6.0.6
		 */
		public function getThrowableHttpCodes() {
			return $this->throwableHttpCodes;
		}

		/**
		 * When using soap/xml fields returned as CDATA will be returned as text nodes if this is disabled (default: diabled)
		 *
		 * @param bool $enabled
		 *
		 * @since 5.0.0
		 */
		public function setCdata( $enabled = true ) {
			$this->allowCdata = $enabled;
		}

		/**
		 * Get current state of the setCdata
		 *
		 * @return bool
		 * @since 5.0.0
		 */
		public function getCdata() {
			return $this->allowCdata;
		}

		/**
		 * Enable the use of local cookie storage
		 *
		 * Use this only if necessary and if you are planning to cookies locally while, for example, needs to set a logged in state more permanent during get/post/etc
		 *
		 * @param bool $enabled
		 *
		 * @since 5.0.0
		 */
		public function setLocalCookies( $enabled = false ) {
			$this->useLocalCookies = $enabled;
		}

		/**
		 * Returns the current setting whether to use local cookies or not
		 *
		 * @return bool
		 * @since 6.0.6
		 */
		public function getLocalCookies() {
			return $this->useLocalCookies;
		}

		/**
		 * @return string
		 * @since 6.0.20
		 */
		public function getCookiePath() {
			$this->initCookiePath();

			return $this->COOKIE_PATH;
		}

		/**
		 * Enforce a response type if you're not happy with the default returned array.
		 *
		 * @param int $NETCURL_RETURN_RESPONSE_TYPE
		 *
		 * @since 5.0.0
		 */
		public function setResponseType( $NETCURL_RETURN_RESPONSE_TYPE = NETCURL_RESPONSETYPE::RESPONSETYPE_ARRAY ) {
			$this->NETCURL_RETURN_RESPONSE_TYPE = $NETCURL_RETURN_RESPONSE_TYPE;
		}

		/**
		 * Return the value of how the responses are returned
		 *
		 * @return int
		 * @since 6.0.6
		 */
		public function getResponseType() {
			return $this->NETCURL_RETURN_RESPONSE_TYPE;
		}

		/**
		 * Enforce a specific type of post method
		 *
		 * To always send PostData, even if it is not set in the doXXX-method, you can use this setting to enforce - for example - JSON posts
		 * $myLib->setPostTypeDefault(NETCURL_POST_DATATYPES::DATATYPE_JSON)
		 *
		 * @param int $postType
		 *
		 * @since 6.0.6
		 */
		public function setPostTypeDefault( $postType = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$this->FORCE_POST_TYPE = $postType;
		}

		/**
		 * Returns what to use as post method (NETCURL_POST_DATATYPES) on default. Returns null if none are set (= no overrides will be made)
		 *
		 * @return NETCURL_POST_DATATYPES
		 * @since 6.0.6
		 */
		public function getPostTypeDefault() {
			return $this->FORCE_POST_TYPE;
		}

		/**
		 * Enforces CURLOPT_FOLLOWLOCATION to act different if not matching with the internal rules
		 *
		 * @param bool $setEnabledState
		 *
		 * @since 5.0
		 */
		public function setEnforceFollowLocation( $setEnabledState = true ) {
			$this->FOLLOW_LOCATION_ENABLE = $setEnabledState;
		}

		/**
		 * Returns the boolean value of followLocationSet (see setEnforceFollowLocation)
		 *
		 * @return bool
		 * @since 6.0.6
		 */
		public function getEnforceFollowLocation() {
			return $this->FOLLOW_LOCATION_ENABLE;
		}

		/**
		 * Allow the initCookie-function to throw exceptions if the local cookie store can not be created properly
		 *
		 * Exceptions are invoked, normally when the function for initializing cookies can not create the storage directory. This is something you should consider disabled in a production environment.
		 *
		 * @param bool $enabled
		 *
		 * @deprecated 6.0.20 No longer in use
		 */
		public function setCookieExceptions( $enabled = false ) {
			/** @noinspection PhpDeprecationInspection */
			$this->UseCookieExceptions = $enabled;
		}

		/**
		 * Returns the boolean value set (eventually) from setCookieException
		 *
		 * @return bool
		 * @since      6.0.6
		 * @deprecated 6.0.20 No longer in use
		 */
		public function getCookieExceptions() {
			/** @noinspection PhpDeprecationInspection */
			return $this->UseCookieExceptions;
		}

		/**
		 * Set up whether we should allow html parsing or not
		 *
		 * @param bool $enabled
		 *
		 * @since      6.0
		 * @deprecated 6.0.22 Use setDomContentParser and getDomContentParser
		 */
		public function setParseHtml( $enabled = false ) {
			$this->setDomContentParser($enabled ? false : true);
		}

		/**
		 * Return the boolean of the setParseHtml
		 *
		 * @return bool
		 * @since 6.0.20
		 * @deprecated 6.0.22 Use setDomContentParser and getDomContentParser
		 */
		public function getParseHtml() {
			return $this->getDomContentParser();
		}

		/**
		 * Set up a different user agent for this library
		 *
		 * To make proper identification of the library we are always appending TorbeLIB+cUrl to the chosen user agent string.
		 *
		 * @param string $CustomUserAgent
		 * @param array  $inheritAgents Updates an array that might have lost some data
		 *
		 * @since 6.0
		 */
		public function setUserAgent( $CustomUserAgent = "", $inheritAgents = array() ) {

			if ( is_array( $inheritAgents ) && count( $inheritAgents ) ) {
				foreach ( $inheritAgents as $inheritedAgentName ) {
					if ( ! in_array( trim( $inheritedAgentName ), $this->CUSTOM_USER_AGENT ) ) {
						$this->CUSTOM_USER_AGENT[] = trim( $inheritedAgentName );
					}
				}
			}

			if ( ! empty( $CustomUserAgent ) ) {
				$this->mergeUserAgent( $CustomUserAgent );
			} else {
				$this->HTTP_USER_AGENT = $this->userAgents['Mozilla'] . ' +TorneLIB-NetCURL-' . NETCURL_RELEASE . " +" . NETCURL_CURL_CLIENTNAME . "+-" . NETCURL_CURL_RELEASE . ' (' . $this->netCurlUrl . ')';
			}
		}

		/**
		 * @param string $CustomUserAgent
		 *
		 * @since 6.0.20
		 */
		private function mergeUserAgent( $CustomUserAgent = "" ) {
			$trimmedUserAgent = trim( $CustomUserAgent );
			if ( ! in_array( $trimmedUserAgent, $this->CUSTOM_USER_AGENT ) ) {
				$this->CUSTOM_USER_AGENT[] = $trimmedUserAgent;
			}

			// NETCURL_CURL_CLIENTNAME . '-' . NETCURL_RELEASE . "/" . __CLASS__ . "-" . NETCURL_CURL_RELEASE
			$this->HTTP_USER_AGENT = implode( " ", $this->CUSTOM_USER_AGENT ) . " +TorneLIB-NETCURL-" . NETCURL_RELEASE . " +" . NETCURL_CURL_CLIENTNAME . "-" . NETCURL_CURL_RELEASE . " (" . $this->netCurlUrl . ")";
		}

		/**
		 * Returns the current set user agent
		 *
		 * @return string
		 * @since 6.0
		 */
		public function getUserAgent() {
			return $this->HTTP_USER_AGENT;
		}

		/**
		 * Get the value of customized user agent
		 *
		 * @return array
		 * @since 6.0.6
		 */
		public function getCustomUserAgent() {
			return $this->CUSTOM_USER_AGENT;
		}

		/**
		 * @param string $refererString
		 *
		 * @since 6.0.9
		 */
		public function setReferer( $refererString = "" ) {
			$this->NETCURL_HTTP_REFERER = $refererString;
		}

		/**
		 * @return null
		 * @since 6.0.9
		 */
		public function getReferer() {
			return $this->NETCURL_HTTP_REFERER;
		}

		/**
		 * If XML/Serializer exists in system, use that parser instead of SimpleXML
		 *
		 * @param bool $useIfExists
		 */
		public function setXmlSerializer( $useIfExists = true ) {
			$this->useXmlSerializer = $useIfExists;
		}

		/**
		 * Get the boolean value of whether to try to use XML/Serializer functions when fetching XML data
		 *
		 * @return bool
		 * @since 6.0.6
		 */
		public function getXmlSerializer() {
			return $this->useXmlSerializer;
		}

		/**
		 * Customize the curlopt configuration
		 *
		 * @param array|string $curlOptArrayOrKey If arrayed, there will be multiple options at once
		 * @param null         $curlOptValue      If not null, and the first parameter is not an array, this is taken as a single update value
		 *
		 * @throws \Exception
		 * @since 6.0
		 */
		public function setCurlOpt( $curlOptArrayOrKey = array(), $curlOptValue = null ) {
			if ( $this->DRIVER->hasCurl() ) {
				if ( is_null( $this->NETCURL_CURL_SESSION ) ) {
					$this->initializeNetCurl();
				}
				if ( is_array( $curlOptArrayOrKey ) ) {
					foreach ( $curlOptArrayOrKey as $key => $val ) {
						$this->curlopt[ $key ] = $val;
						curl_setopt( $this->NETCURL_CURL_SESSION, $key, $val );
					}
				}
				if ( ! is_array( $curlOptArrayOrKey ) && ! empty( $curlOptArrayOrKey ) && ! is_null( $curlOptValue ) ) {
					$this->curlopt[ $curlOptArrayOrKey ] = $curlOptValue;
					curl_setopt( $this->NETCURL_CURL_SESSION, $curlOptArrayOrKey, $curlOptValue );
				}
			}
		}

		/**
		 * curlops that can be overridden
		 *
		 * @param array|string $curlOptArrayOrKey
		 * @param null         $curlOptValue
		 *
		 * @throws \Exception
		 * @since 6.0
		 */
		private function setCurlOptInternal( $curlOptArrayOrKey = array(), $curlOptValue = null ) {
			if ( $this->DRIVER->hasCurl() ) {
				if ( is_null( $this->NETCURL_CURL_SESSION ) ) {
					$this->initializeNetCurl();
				}
				if ( ! is_array( $curlOptArrayOrKey ) && ! empty( $curlOptArrayOrKey ) && ! is_null( $curlOptValue ) ) {
					if ( ! isset( $this->curlopt[ $curlOptArrayOrKey ] ) ) {
						$this->curlopt[ $curlOptArrayOrKey ] = $curlOptValue;
						curl_setopt( $this->NETCURL_CURL_SESSION, $curlOptArrayOrKey, $curlOptValue );
					}
				}
			}
		}

		/**
		 * @return array
		 * @since 6.0.9
		 */
		public function getCurlOpt() {
			return $this->curlopt;
		}

		/**
		 * Easy readable curlopts
		 *
		 * @return array
		 * @since 6.0.10
		 */
		public function getCurlOptByKeys() {
			$return = array();
			if ( is_array( $this->curlConstantsOpt ) ) {
				$currentCurlOpt = $this->getCurlOpt();
				foreach ( $currentCurlOpt as $curlOptKey => $curlOptValue ) {
					if ( isset( $this->curlConstantsOpt[ $curlOptKey ] ) ) {
						$return[ $this->curlConstantsOpt[ $curlOptKey ] ] = $curlOptValue;
					} else {
						$return[ $curlOptKey ] = $curlOptValue;
					}
				}
			}

			return $return;
		}

		/**
		 * Set up special SSL option array for communicators
		 *
		 * @param array $sslOptArray
		 *
		 * @since 6.0.9
		 */
		public function setSslOpt( $sslOptArray = array() ) {
			foreach ( $sslOptArray as $key => $val ) {
				$this->sslopt[ $key ] = $val;
			}
		}

		/**
		 * Get current setup for SSL options
		 *
		 * @return array
		 * @since 6.0.9
		 */
		public function getSslOpt() {
			return $this->sslopt;
		}


		//// SINGLE PUBLIC GETTERS

		/**
		 * Get the current version of the module
		 *
		 * @param bool $fullRelease
		 *
		 * @return string
		 * @since 5.0
		 */
		public function getVersion( $fullRelease = false ) {
			if ( ! $fullRelease ) {
				return NETCURL_CURL_RELEASE;
			} else {
				return NETCURL_CURL_RELEASE . "-" . NETCURL_CURL_MODIFY;
			}
		}

		/**
		 * Get this internal release version
		 *
		 * @return string
		 * @throws \Exception
		 * @deprecated 6.0.0 Use tag control
		 */
		public function getInternalRelease() {
			if ( $this->isFlag( 'NETCURL_ALLOW_VERSION_REQUESTS' ) ) {
				return NETCURL_CURL_RELEASE . "," . NETCURL_CURL_MODIFY;
			}
			throw new \Exception( NETCURL_CURL_CLIENTNAME . " internalReleaseException [" . __CLASS__ . "]: Version requests are not allowed in current state (permissions required)", 403 );
		}

		/**
		 * Get store exceptions
		 *
		 * @return array
		 * @since 6.0
		 */
		public function getStoredExceptionInformation() {
			return $this->sessionsExceptions;
		}

		/// SPECIAL FEATURES

		/**
		 * @return bool
		 * @since 6.0.20
		 */
		public function hasErrors() {
			if ( is_array( $this->NETCURL_ERROR_CONTAINER ) && ! count( $this->NETCURL_ERROR_CONTAINER ) ) {
				return false;
			}

			return true;
		}

		/**
		 * @return array
		 * @since 6.0
		 */
		public function getErrors() {
			return $this->NETCURL_ERROR_CONTAINER;
		}

		/**
		 * Check against Tornevall Networks API if there are updates for this module
		 *
		 * @param string $libName
		 *
		 * @return string
		 * @throws \Exception
		 * @deprecated 6.0.20
		 */
		public function hasUpdate( $libName = 'tornelib_curl' ) {
			if ( ! $this->isFlag( 'NETCURL_ALLOW_VERSION_REQUESTS' ) ) {
				$this->setFlag( 'NETCURL_ALLOW_VERSION_REQUESTS', true );
			}

			/** @noinspection PhpDeprecationInspection */
			return $this->getHasUpdateState( $libName );
		}

		/**
		 * @param string $libName
		 *
		 * @return string
		 * @throws \Exception
		 * @deprecated 6.0.20
		 */
		private function getHasUpdateState( $libName = 'tornelib_curl' ) {
			// Currently only supporting this internal module (through $myRelease).
			$myRelease  = NETCURL_RELEASE;
			$libRequest = ( ! empty( $libName ) ? "lib/" . $libName : "" );
			$getInfo    = $this->doGet( "https://api.tornevall.net/2.0/libs/getLibs/" . $libRequest . "/me/" . $myRelease );
			if ( isset( $getInfo['parsed']->response->getLibsResponse->you ) ) {
				$currentPublicVersion = $getInfo['parsed']->response->getLibsResponse->you;
				if ( $currentPublicVersion->hasUpdate ) {
					if ( isset( $getInfo['parsed']->response->getLibsResponse->libs->tornelib_curl ) ) {
						return $getInfo['parsed']->response->getLibsResponse->libs->tornelib_curl;
					}
				}
			}

			return "";
		}

		/**
		 * Returns true if SSL verification was unset during the URL call
		 *
		 * @return bool
		 * @since 6.0.10
		 */
		public function getSslIsUnsafe() {
			return $this->unsafeSslCall;
		}


		/// CONFIGURATORS

		/**
		 * Generate a corrected stream context
		 *
		 * @return void
		 * @link  https://phpdoc.tornevall.net/TorneLIBv5/source-class-TorneLIB.Tornevall_cURL.html sslStreamContextCorrection() is a part of TorneLIB 5.0, described here
		 * @since 6.0
		 */
		public function sslStreamContextCorrection() {
			$this->SSL->getSslStreamContext();
		}

		/**
		 * Automatically generates stream_context and appends it to whatever you need it for.
		 *
		 * Example:
		 *  $addonContextData = array('http' => array("user_agent" => "MyUserAgent"));
		 *  $this->soapOptions = sslGetDefaultStreamContext($this->soapOptions, $addonContextData);
		 *
		 * @param array $optionsArray
		 * @param array $addonContextData
		 *
		 * @return array
		 * @throws \Exception
		 * @link  http://developer.tornevall.net/apigen/TorneLIB-5.0/class-TorneLIB.Tornevall_cURL.html sslGetOptionsStream() is a part of TorneLIB 5.0, described here
		 * @since 6.0
		 */
		public function sslGetOptionsStream( $optionsArray = array(), $addonContextData = array() ) {
			return $this->SSL->getSslStream( $optionsArray, $addonContextData );
		}

		/**
		 * Set and/or append certificate bundle locations to current configuration
		 *
		 * @param array $locationArrayOrString
		 *
		 * @return bool
		 * @throws \Exception
		 * @since 6.0
		 */
		public function setSslPemLocations( $locationArrayOrString = array() ) {
			$this->setTrustedSslBundles( true );

			return $this->SSL->setPemLocation( $locationArrayOrString );
		}

		/**
		 * Get current certificate bundle locations
		 *
		 * @return array
		 * @deprecated 6.0.20 Use MODULE_SSL
		 */
		public function getSslPemLocations() {
			return $this->SSL->getPemLocations();
		}

		/**
		 * Enable/disable SSL Certificate autodetection (and/or host/peer ssl verications)
		 *
		 * The $hostVerification-flag can also be called manually with setSslVerify()
		 *
		 * @param bool $enabledFlag
		 *
		 * @deprecated 6.0.20 Use setSslVerify
		 */
		public function setCertAuto( $enabledFlag = true ) {
			$this->SSL->setStrictVerification( $enabledFlag );
		}

		/**
		 * Allow fallbacks of SSL verification if Peer/Host checking fails. This is actually kind of another way to disable strict checking of certificates. THe difference, however, is that NetCurl will first try to make a proper call, before fallback.
		 *
		 * @param bool $strictCertificateVerification
		 * @param bool $prohibitSelfSigned
		 *
		 * @return void
		 * @since 6.0
		 */
		public function setSslVerify( $strictCertificateVerification = true, $prohibitSelfSigned = true ) {
			$this->SSL->setStrictVerification( $strictCertificateVerification, $prohibitSelfSigned );
		}

		/**
		 * Return the boolean value set in setSslVerify
		 *
		 * @return bool
		 * @since 6.0.6
		 */
		public function getSslVerify() {
			return $this->SSL->getStrictVerification();
		}

		/**
		 * @param bool $sslFailoverEnabled
		 *
		 * @since 6.0.22
		 */
		public function setSslStrictFallback( $sslFailoverEnabled = false ) {
			$this->SSL->setStrictFallback( $sslFailoverEnabled );
		}

		/**
		 * @param bool $sslFailoverEnabled
		 *
		 * @since      6.0.20
		 * @deprecated 6.0.22 Use setSslStrictFallback as it is better described
		 */
		public function setStrictFallback( $sslFailoverEnabled = false ) {
			$this->SSL->setStrictFallback( $sslFailoverEnabled );
		}


		/**
		 * @return bool
		 * @since      6.0.20
		 * @deprecated 6.0.22 Use getSslStrictFallback as it is better described
		 */
		public function getStrictFallback() {
			return $this->SSL->getStrictFallback();
		}

		/**
		 * @return bool
		 * @since 6.0.22
		 */
		public function getSslStrictFallback() {
			return $this->SSL->getStrictFallback();
		}

		/**
		 * While doing SSL calls, and SSL certificate verifications is failing, enable the ability to skip SSL verifications.
		 *
		 * Normally, we want a valid SSL certificate while doing https-requests, but sometimes the verifications must be disabled. One reason of this is
		 * in cases, when crt-files are missing and PHP can not under very specific circumstances verify the peer. To allow this behaviour, the client
		 * must use this function.
		 *
		 * @param bool $allowStrictFallback
		 *
		 * @since      5.0
		 * @deprecated 6.0.20 Use setStrictFallback
		 */
		public function setSslUnverified( $allowStrictFallback = false ) {
			$this->SSL->setStrictFallback( $allowStrictFallback );
		}

		/**
		 * Return the boolean value set from setSslUnverified
		 *
		 * @return bool
		 * @since      6.0.6
		 * @deprecated 6.0.20 Use getStrictFallback
		 */
		public function getSslUnverified() {
			return $this->SSL->getStrictFallback();
		}

		/**
		 * TestCerts - Test if your webclient has certificates available (make sure the $testssldeprecated are enabled if you want to test older PHP-versions - meaning older than 5.6.0)
		 *
		 * Note: This function also forces full ssl certificate checking.
		 *
		 * @return bool
		 * @throws \Exception
		 * @deprecated 6.0.20
		 */
		public function TestCerts() {
			$certificateBundleData = $this->SSL->getSslCertificateBundle();

			return ( ! empty( $certificateBundleData ) ? true : false );
		}

		/**
		 * Return the current certificate bundle file, chosen by autodetection
		 *
		 * @return string
		 * @deprecated 6.0.20
		 */
		public function getCertFile() {
			return $this->SSL->getSslCertificateBundle();
		}

		/**
		 * Returns true if the autodetected certificate bundle was one of the defaults (normally fetched from openssl_get_cert_locations()). Used for testings.
		 *
		 * @return bool
		 * @throws \Exception
		 * @deprecated 6.0.20
		 */
		public function hasCertDefault() {
			/** @noinspection PhpDeprecationInspection */
			return $this->TestCerts();
		}

		/**
		 * @return bool
		 * @since 6.0.20
		 */
		public function hasSsl() {
			return MODULE_SSL::hasSsl();
		}

		//// IP SETUP

		/**
		 * Making sure the $IpAddr contains valid address list
		 * Pick up externally selected outgoing ip if any requested
		 *
		 * @throws \Exception
		 * @since 5.0
		 * @todo  Split code (try to fix all if/elses)
		 */
		private function handleIpList() {
			$this->CURL_IP_ADDRESS = null;
			$UseIp                 = "";
			if ( is_array( $this->IpAddr ) ) {
				if ( count( $this->IpAddr ) == 1 ) {
					$UseIp = ( isset( $this->IpAddr[0] ) && ! empty( $this->IpAddr[0] ) ? $this->IpAddr[0] : null );
				} else if ( count( $this->IpAddr ) > 1 ) {
					if ( ! $this->IpAddrRandom ) {
						// If we have multiple ip addresses in the list, but the randomizer is not active, always use the first address in the list.
						$UseIp = ( isset( $this->IpAddr[0] ) && ! empty( $this->IpAddr[0] ) ? $this->IpAddr[0] : null );
					} else {
						$IpAddrNum = rand( 0, count( $this->IpAddr ) - 1 );
						$UseIp     = $this->IpAddr[ $IpAddrNum ];
					}
				}
			} else if ( ! empty( $this->IpAddr ) ) {
				$UseIp = $this->IpAddr;
			}

			$ipType = $this->NETWORK->getArpaFromAddr( $UseIp, true );
			// Bind interface to specific ip only if any are found
			if ( $ipType == "0" ) {
				// If the ip type is 0 and it shows up there is something defined here, throw an exception.
				if ( ! empty( $UseIp ) ) {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: " . $UseIp . " is not a valid ip-address", $this->NETWORK->getExceptionCode( 'NETCURL_IPCONFIG_NOT_VALID' ) );
				}
			} else {
				$this->CURL_IP_ADDRESS = $UseIp;
				curl_setopt( $this->NETCURL_CURL_SESSION, CURLOPT_INTERFACE, $UseIp );
				if ( $ipType == 6 ) {
					curl_setopt( $this->NETCURL_CURL_SESSION, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6 );
					$this->CURL_IP_ADDRESS_TYPE = 6;
				} else {
					curl_setopt( $this->NETCURL_CURL_SESSION, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
					$this->CURL_IP_ADDRESS_TYPE = 4;
				}
			}
		}

		/**
		 * Set up a proxy
		 *
		 * @param     $ProxyAddr
		 * @param int $ProxyType
		 *
		 * @throws \Exception
		 * @since 6.0
		 */
		public function setProxy( $ProxyAddr, $ProxyType = CURLPROXY_HTTP ) {
			$this->CURL_PROXY_GATEWAY = $ProxyAddr;
			$this->CURL_PROXY_TYPE    = $ProxyType;
			// Run from proxy on request
			$this->setCurlOptInternal( CURLOPT_PROXY, $this->CURL_PROXY_GATEWAY );
			if ( isset( $this->CURL_PROXY_TYPE ) && ! empty( $this->CURL_PROXY_TYPE ) ) {
				$this->setCurlOptInternal( CURLOPT_PROXYTYPE, $this->CURL_PROXY_TYPE );
			}
		}

		/**
		 * Get proxy settings
		 *
		 * @return array
		 * @since 6.0.11
		 */
		public function getProxy() {
			return array(
				'curlProxy'     => $this->CURL_PROXY_GATEWAY,
				'curlProxyType' => $this->CURL_PROXY_TYPE
			);
		}

		/**
		 * Enable curl tunneling
		 *
		 * @param bool $curlTunnelEnable
		 *
		 * @throws \Exception
		 * @since 6.0.11
		 */
		public function setTunnel( $curlTunnelEnable = true ) {
			// Run in tunneling mode
			$this->CURL_TUNNEL = $curlTunnelEnable;
			$this->setCurlOptInternal( CURLOPT_HTTPPROXYTUNNEL, $curlTunnelEnable );
		}

		/**
		 * Return state of curltunneling
		 *
		 * @return bool
		 * @since 6.0
		 */
		public function getTunnel() {
			return $this->CURL_TUNNEL;
		}


		/**
		 * @param string $byWhat
		 *
		 * @return array
		 * @since 6.0.20
		 */
		private function extractParsedDom( $byWhat = 'Id' ) {
			$validElements = array( 'Id', 'ClosestTag', 'Nodes' );
			if ( in_array( $byWhat, $validElements ) && isset( $this->NETCURL_RESPONSE_CONTAINER_PARSED[ 'By' . $byWhat ] ) ) {
				return $this->NETCURL_RESPONSE_CONTAINER_PARSED[ 'By' . $byWhat ];
			}

			return array();
		}

        /**
         * @param null $rawInput
         * @param bool $internalRaw
         *
         * @return $this|array|null|NETCURL_HTTP_OBJECT
         * @throws \Exception
         */
		public function netcurl_split_raw( $rawInput = null, $internalRaw = false ) {
			$rawDataTest = $this->getRaw();
			if ( $internalRaw && is_null( $rawInput ) && ! empty( $rawDataTest ) ) {
				$this->netcurl_split_raw( $rawDataTest );

				return $this;
			}

			// explodeRaw usages - header and body
			$explodeRaw        = explode( "\r\n\r\n", $rawInput . "\r\n", 2 );
			$header            = isset( $explodeRaw[0] ) ? $explodeRaw[0] : "";
			$body              = isset( $explodeRaw[1] ) ? $explodeRaw[1] : "";
			$rows              = explode( "\n", $header );
			$response          = explode( " ", isset( $rows[0] ) ? $rows[0] : null );
			$shortCodeResponse = explode( " ", isset( $rows[0] ) ? $rows[0] : null, 3 );
			$httpMessage       = isset( $shortCodeResponse[2] ) ? $shortCodeResponse[2] : null;
			$code              = isset( $response[1] ) ? $response[1] : null;

			// If the first row of the body contains a HTTP/-string, we'll try to reparse it
			if ( preg_match( "/^HTTP\//", $body ) ) {
				$this->netcurl_split_raw( $body );
				$header = $this->getHeader();
				$body   = $this->getBody();
				$rows   = explode( "\n", $header );
			}

			$headerInfo = $this->GetHeaderKeyArray( $rows );

			// If response code starts with 3xx, this is probably a redirect
			if ( preg_match( "/^3/", $code ) ) {
				$this->REDIRECT_URLS[] = $this->CURL_STORED_URL;
				$redirectArray[]       = array(
					'header' => $header,
					'body'   => $body,
					'code'   => $code
				);
				if ( $this->isFlag( 'FOLLOWLOCATION_INTERNAL' ) ) {
					//$transferByLocation = array( 300, 301, 302, 307, 308 );
					if ( isset( $headerInfo['Location'] ) ) {
						$newLocation = $headerInfo['Location'];
						if ( ! preg_match( "/^http/i", $newLocation ) ) {
							$this->CURL_STORED_URL .= $newLocation;
						} else {
							$this->CURL_STORED_URL = $newLocation;
						}
						/** @var MODULE_CURL $newRequest */
						$newRequest = $this->doRepeat();
						// Make sure getRaw exists (this might fail from PHP 5.3)
						if ( method_exists( $newRequest, 'getRaw' ) ) {
							$rawRequest = $newRequest->getRaw();

							return $this->netcurl_split_raw( $rawRequest );
						}
					}
				}
			}
			$arrayedResponse       = array(
				'header' => array( 'info' => $headerInfo, 'full' => $header ),
				'body'   => $body,
				'code'   => $code
			);
			$returnResponse['URL'] = $this->CURL_STORED_URL;
			$returnResponse['ip']  = isset( $this->CURL_IP_ADDRESS ) ? $this->CURL_IP_ADDRESS : null;  // Will only be filled if there is custom address set.
			$contentType           = isset( $headerInfo['Content-Type'] ) ? $headerInfo['Content-Type'] : null;
			$arrayedResponse['ip'] = $this->CURL_IP_ADDRESS;

			// Store data that can be stored before tryiing to handle the parsed parts
			$this->NETCURL_RESPONSE_RAW                   = $rawInput;
			$this->NETCURL_RESPONSE_CONTAINER             = $arrayedResponse;
			$this->NETCURL_RESPONSE_CONTAINER_CODE        = trim( $code );
			$this->NETCURL_RESPONSE_CONTAINER_HTTPMESSAGE = trim( $httpMessage );
			$this->NETCURL_RESPONSE_CONTAINER_BODY        = $body;
			$this->NETCURL_RESPONSE_CONTAINER_HEADER      = $header;

			// Check if there is any exception to take care of and throw - or continue.
			$this->throwCodeException( trim( $httpMessage ), $code );

			if ( $this->isFlag( 'IS_SOAP' ) && ! $this->isFlag( 'ALLOW_PARSE_SOAP' ) ) {
				$arrayedResponse['parsed'] = null;

				return $arrayedResponse;
			}

			$flags = array('NETCURL_PROHIBIT_DOMCONTENT_PARSE' => $this->NETCURL_PROHIBIT_DOMCONTENT_PARSE);

			// php 5.3 compliant
			$NCP = new NETCURL_PARSER( $arrayedResponse['body'], $contentType, $flags );

			$parsedContent                           = $NCP->getParsedResponse();
			$arrayedResponse['parsed']               = $parsedContent;
			$this->NETCURL_RESPONSE_CONTAINER_PARSED = $parsedContent;

			$this->NETCURL_CONTENT_IS_DOMCONTENT = $NCP->getIsDomContent();


			if ( $this->NETCURL_RETURN_RESPONSE_TYPE == NETCURL_RESPONSETYPE::RESPONSETYPE_OBJECT ) {
				return new NETCURL_HTTP_OBJECT( $arrayedResponse['header'], $arrayedResponse['body'], $arrayedResponse['code'], $arrayedResponse['parsed'], $this->CURL_STORED_URL, $this->CURL_IP_ADDRESS );
			}

			if ( $this->NETCURL_SIMPLIFY_RESPONSES ) {
				return $this->getSimplifiedResponseReturnData();
			}

			if ( $this->isFlag( 'CHAIN' ) && ! $this->isFlag( 'IS_SOAP' ) ) {
				return $this;
			}

			return $arrayedResponse;
		}

		/**
		 * @return array|null
		 * @since 6.0.21
		 */
		private function getSimplifiedResponseReturnData() {

			// If domcontent is detected it us usually parsed as a domtree object. This defines if domtrees are allowed to be dumped out
			// or if the body should be use primarily
			if ( $this->NETCURL_CONTENT_IS_DOMCONTENT ) {
				if ( $this->NETCURL_SIMPLIFY_DOMCONTENT ) {
					return $this->NETCURL_RESPONSE_CONTAINER_PARSED;
				} else {
					return $this->NETCURL_RESPONSE_CONTAINER_BODY;
				}
			}

			if ( ! empty( $this->NETCURL_RESPONSE_CONTAINER_PARSED ) ) {
				return $this->NETCURL_RESPONSE_CONTAINER_PARSED;
			} else if ( ! empty( $this->NETCURL_RESPONSE_CONTAINER_BODY ) ) {
				return $this->NETCURL_RESPONSE_CONTAINER_BODY;
			}

			return array(
				'simplifiedContainer' => array(
					'NETCURL_RESPONSE_RAW'                   => $this->NETCURL_RESPONSE_RAW,
					'NETCURL_RESPONSE_CONTAINER'             => $this->NETCURL_RESPONSE_CONTAINER,
					'NETCURL_RESPONSE_CONTAINER_CODE'        => $this->NETCURL_RESPONSE_CONTAINER_CODE,
					'NETCURL_RESPONSE_CONTAINER_HTTPMESSAGE' => $this->NETCURL_RESPONSE_CONTAINER_HTTPMESSAGE,
					'NETCURL_RESPONSE_CONTAINER_BODY'        => $this->NETCURL_RESPONSE_CONTAINER_BODY,
					'NETCURL_RESPONSE_CONTAINER_HEADER'      => $this->NETCURL_RESPONSE_CONTAINER_HEADER,
				)
			);
		}

		/**
		 * @param string $netCurlResponse
		 *
		 * @return array|string|MODULE_CURL|NETCURL_HTTP_OBJECT
		 * @throws \Exception
		 */
		private function netcurl_parse( $netCurlResponse = '' ) {
			if ( $this->isFlag( 'NOCHAIN' ) ) {
				$this->unsetFlag( 'CHAIN' );
			}

			if ( ! is_string( $netCurlResponse ) ) {
				// This method exists in external drivers interface. Do not mistakenly consider it the internal getRaw()
				if ( method_exists( $netCurlResponse, 'getRawResponse' ) ) {
					$htmlResponseData = $netCurlResponse->getRawResponse();
				} else {
					return $netCurlResponse;
				}
			} else {
				$htmlResponseData = $netCurlResponse;
			}

			$parsedResponse = $this->netcurl_split_raw( $htmlResponseData );

			return $parsedResponse;
		}

		/**
		 * @return mixed
		 * @since 6.0.20
		 */
		public function getRaw() {
			return $this->NETCURL_RESPONSE_RAW;
		}

		/**
		 * Get head and body from a request parsed
		 *
		 * @param string $content
		 *
		 * @return array
		 * @throws \Exception
		 * @since 6.0
		 */
		public function getHeader( $content = "" ) {
			if ( ! empty( $content ) ) {
				$this->netcurl_split_raw( $content );
			}

			return $this->NETCURL_RESPONSE_CONTAINER_HEADER;
		}

		/**
		 * @return array
		 * @since 6.0.20
		 */
		public function getDomByNodes() {
			return $this->extractParsedDom( 'Nodes' );
		}

		/**
		 * @return array
		 * @since 6.0.20
		 */
		public function getDomById() {
			return $this->extractParsedDom( 'Id' );
		}

		/**
		 * @return array
		 * @since 6.0.20
		 */
		public function getDomByClosestTag() {
			return $this->extractParsedDom( 'ClosestTag' );
		}

		/**
		 * Extract a parsed response from a webrequest
		 *
		 * @param null $inputResponse
		 *
		 * @return null
		 * @throws \Exception
		 * @since 6.0.20
		 */
		public function getParsed( $inputResponse = null ) {
			$returnThis = null;

			$this->getParsedExceptionCheck( $inputResponse );

			// When curl is disabled or missing, this might be returned chained
			if ( is_object( $inputResponse ) ) {
				$returnThis = $this->getParsedByObjectMethod( $inputResponse );
				if ( ! is_null( $returnThis ) ) {
					return $returnThis;
				}
			}
			if ( is_null( $inputResponse ) && ! empty( $this->NETCURL_RESPONSE_CONTAINER_PARSED ) ) {
				return $this->NETCURL_RESPONSE_CONTAINER_PARSED;
			} else if ( is_array( $inputResponse ) ) {
				return $this->getParsedByDeprecated( $inputResponse );
			}

			$returnThis = $this->getParsedUntouched( $inputResponse );

			return $returnThis;
		}

		/**
		 * @param $inputResponse
		 *
		 * @return bool
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function getParsedExceptionCheck( $inputResponse ) {
			// If the input response is an array and contains the deprecated editon of an error code
			if ( is_array( $inputResponse ) ) {
				if ( isset( $inputResponse['code'] ) && $inputResponse['code'] >= 400 ) {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " parseResponse exception - Unexpected response code from server: " . $inputResponse['code'], $inputResponse['code'] );
				}
			}

			return false;
		}

		/**
		 * @param $inputResponse
		 *
		 * @return null
		 * @since 6.0.20
		 */
		private function getParsedByObjectMethod( $inputResponse ) {
			if ( method_exists( $inputResponse, "getParsedResponse" ) ) {
				return $inputResponse->getParsedResponse();
			} else if ( isset( $inputResponse->NETCURL_RESPONSE_CONTAINER_PARSED ) ) {
				return $inputResponse->NETCURL_RESPONSE_CONTAINER_PARSED;
			}

			return null;
		}

		/**
		 * @param $inputResponse
		 *
		 * @return mixed
		 * @since 6.0.20
		 */
		private function getParsedByDeprecated( $inputResponse ) {
			// Return a deprecated answer
			if ( isset( $inputResponse['parsed'] ) ) {
				return $inputResponse['parsed'];
			}

			return null;
		}

		/**
		 * @param $inputResponse
		 *
		 * @return null
		 * @since 6.0.20
		 */
		private function getParsedUntouched( $inputResponse ) {
			if ( is_array( $inputResponse ) ) {
				// This might already be parsed, if the array reaches this point
				return $inputResponse;
			} else if ( is_object( $inputResponse ) ) {
				// This is an object. Either it is ourselves or it is an already parsed object
				return $inputResponse;
			}

			return null;
		}

		/**
		 * @param null $ResponseContent
		 *
		 * @return int
		 * @since 6.0.20
		 */
		public function getCode( $ResponseContent = null ) {
			if ( method_exists( $ResponseContent, "getCode" ) ) {
				return $ResponseContent->getCode();
			}

			if ( is_null( $ResponseContent ) && ! empty( $this->NETCURL_RESPONSE_CONTAINER_CODE ) ) {
				return (int) $this->NETCURL_RESPONSE_CONTAINER_CODE;
			} else if ( isset( $ResponseContent['code'] ) ) {
				return (int) $ResponseContent['code'];
			}

			return 0;
		}

		/**
		 * @param null $ResponseContent
		 *
		 * @return int
		 * @since 6.0.20
		 */
		public function getMessage( $ResponseContent = null ) {
			if ( method_exists( $ResponseContent, "getMessage" ) ) {
				return $ResponseContent->getMessage();
			}

			if ( is_null( $ResponseContent ) && ! empty( $this->NETCURL_RESPONSE_CONTAINER_HTTPMESSAGE ) ) {
				return (string) $this->NETCURL_RESPONSE_CONTAINER_HTTPMESSAGE;
			}

			return null;
		}


		/**
		 * @param null $ResponseContent
		 *
		 * @return null
		 * @since 6.0.20
		 */
		public function getBody( $ResponseContent = null ) {
			if ( method_exists( $ResponseContent, "getResponseBody" ) ) {
				return $ResponseContent->getResponseBody();
			}

			if ( is_null( $ResponseContent ) && ! empty( $this->NETCURL_RESPONSE_CONTAINER_BODY ) ) {
				return $this->NETCURL_RESPONSE_CONTAINER_BODY;
			} else if ( isset( $ResponseContent['body'] ) ) {
				return $ResponseContent['body'];
			}

			return null;
		}

		/**
		 * @return mixed
		 * @since 6.0.20
		 */
		public function getRequestHeaders() {
			return $this->NETCURL_REQUEST_CONTAINER;
		}

		/**
		 * @return mixed
		 * @since 6.0.20
		 */
		public function getRequestBody() {
			return $this->NETCURL_REQUEST_BODY;
		}

		/**
		 * @param null $ResponseContent
		 *
		 * @return null|string
		 * @since 6.0.20
		 */
		public function getUrl( $ResponseContent = null ) {
			if ( method_exists( $ResponseContent, "getResponseUrl" ) ) {
				return $ResponseContent->getResponseUrl();
			}

			if ( is_null( $ResponseContent ) && ! empty( $this->CURL_STORED_URL ) ) {
				return $this->CURL_STORED_URL;
			} else if ( isset( $ResponseContent['URL'] ) ) {
				return $ResponseContent['URL'];
			}

			return '';
		}


		/**
		 * Extract a specific key from a parsed webrequest
		 *
		 * @param      $keyName
		 * @param null $responseContent
		 *
		 * @return mixed|null
		 * @throws \Exception
		 * @since 6.0.20
		 */
		public function getValue( $keyName = null, $responseContent = null ) {
			$testInternalParsed = $this->getParsed();
			if ( is_null( $responseContent ) && ! empty( $testInternalParsed ) ) {
				$responseContent = $testInternalParsed;
			}

			if ( is_string( $keyName ) ) {
				$ParsedValue = $this->getParsed( $responseContent );
				if ( is_array( $ParsedValue ) && isset( $ParsedValue[ $keyName ] ) ) {
					return $ParsedValue[ $keyName ];
				}
				if ( is_object( $ParsedValue ) && isset( $ParsedValue->$keyName ) ) {
					return $ParsedValue->{$keyName};
				}
			} else {
				if ( is_null( $responseContent ) && ! empty( $this->NETCURL_RESPONSE_CONTAINER ) ) {
					$responseContent = $this->NETCURL_RESPONSE_CONTAINER;
				}
				$Parsed       = $this->getParsed( $responseContent );
				$hasRecursion = false;
				if ( is_array( $keyName ) ) {
					$TheKeys  = array_reverse( $keyName );
					$Eternity = 0;
					while ( count( $TheKeys ) || $Eternity ++ <= 20 ) {
						$hasRecursion = false;
						$CurrentKey   = array_pop( $TheKeys );
						if ( is_array( $Parsed ) ) {
							if ( isset( $Parsed[ $CurrentKey ] ) ) {
								$hasRecursion = true;
							}
						} else if ( is_object( $Parsed ) ) {
							if ( isset( $Parsed->{$CurrentKey} ) ) {
								$hasRecursion = true;
							}
						} else {
							// If there are still keys to scan, all tests above has failed
							if ( count( $TheKeys ) ) {
								$hasRecursion = false;
							}
							break;
						}
						if ( $hasRecursion ) {
							$Parsed = $this->getValue( $CurrentKey, array( 'parsed' => $Parsed ) );
							// Break if this was the last one
							if ( ! count( $TheKeys ) ) {
								break;
							}
						}
					}
					if ( $hasRecursion ) {
						return $Parsed;
					} else {
						throw new \Exception( NETCURL_CURL_CLIENTNAME . " getParsedValue exception: Requested key was not found in parsed response", $this->NETWORK->getExceptionCode( 'NETCURL_GETPARSEDVALUE_KEY_NOT_FOUND' ) );
					}
				}
			}

			return null;
		}

		/**
		 * @return array
		 * @since 6.0
		 */
		public function getRedirectedUrls() {
			return $this->REDIRECT_URLS;
		}

		/**
		 * Create an array of a header, with keys and values
		 *
		 * @param array $HeaderRows
		 *
		 * @return array
		 * @since 6.0
		 */
		private function GetHeaderKeyArray( $HeaderRows = array() ) {
			$headerInfo = array();
			if ( is_array( $HeaderRows ) ) {
				foreach ( $HeaderRows as $headRow ) {
					$colon = array_map( "trim", explode( ":", $headRow, 2 ) );
					if ( isset( $colon[1] ) ) {
						$headerInfo[ $colon[0] ] = $colon[1];
					} else {
						$rowSpc = explode( " ", $headRow );
						if ( isset( $rowSpc[0] ) ) {
							$headerInfo[ $rowSpc[0] ] = $headRow;
						} else {
							$headerInfo[ $headRow ] = $headRow;
						}
					}
				}
			}

			return $headerInfo;
		}

		/**
		 * Check if SOAP exists in system
		 *
		 * @param bool $extendedSearch Extend search for SOAP (unsafe method, looking for constants defined as SOAP_*)
		 *
		 * @return bool
		 * @since 6.0
		 */
		public function hasSoap( $extendedSearch = false ) {
			return $this->DRIVER->hasSoap( $extendedSearch );
		}

		/**
		 * Return number of tries, arrayed, that different parts of netcurl has been trying to make a call
		 *
		 * @return array
		 * @since 6.0.8
		 */
		public function getRetries() {
			return $this->CURL_RETRY_TYPES;
		}

		/**
		 * Defines if this library should be able to store the curl_getinfo() for each curl_exec that generates an exception
		 *
		 * @param bool $Activate
		 *
		 * @since 6.0.6
		 */
		public function setStoreSessionExceptions( $Activate = false ) {
			$this->canStoreSessionException = $Activate;
		}

		/**
		 * Returns the boolean value of whether exceptions can be stored in memory during calls
		 *
		 * @return bool
		 * @since 6.0.6
		 */
		public function getStoreSessionExceptions() {
			return $this->canStoreSessionException;
		}

		/**
		 * @return array|null|string|MODULE_CURL|NETCURL_HTTP_OBJECT
		 * @throws \Exception
		 * @since 6.0.20
		 */
		function doRepeat() {
			if ( $this->NETCURL_POST_METHOD == NETCURL_POST_METHODS::METHOD_POST ) {
				return $this->doPost( $this->CURL_STORED_URL, $this->POST_DATA_REAL, $this->NETCURL_POST_DATA_TYPE );
			} else if ( $this->NETCURL_POST_METHOD == NETCURL_POST_METHODS::METHOD_PUT ) {
				return $this->doPost( $this->CURL_STORED_URL, $this->POST_DATA_REAL, $this->NETCURL_POST_DATA_TYPE );
			} else if ( $this->NETCURL_POST_METHOD == NETCURL_POST_METHODS::METHOD_DELETE ) {
				return $this->doPost( $this->CURL_STORED_URL, $this->POST_DATA_REAL, $this->NETCURL_POST_DATA_TYPE );
			} else {
				// Go GET by deault ($this->NETCURL_POST_METHOD == NETCURL_POST_METHODS::METHOD_GET)
				return $this->doGet( $this->CURL_STORED_URL, $this->NETCURL_POST_DATA_TYPE );
			}
		}

		/**
		 * Make POST request
		 *
		 * @param string $url
		 * @param array  $postData
		 * @param int    $postAs
		 *
		 * @return array|null|string|MODULE_CURL|NETCURL_HTTP_OBJECT
		 * @throws \Exception
		 * @since 5.0
		 */
		public function doPost( $url = '', $postData = array(), $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$response = null;
			if ( ! empty( $url ) ) {
				$content  = $this->executeUrlCall( $url, $postData, NETCURL_POST_METHODS::METHOD_POST, $postAs );
				$response = $this->netcurl_parse( $content );
			}

			return $response;
		}

		/**
		 * Make PUT request
		 *
		 * @param string $url
		 * @param array  $postData
		 * @param int    $postAs
		 *
		 * @return array|null|string|MODULE_CURL|NETCURL_HTTP_OBJECT
		 * @throws \Exception
		 * @since 5.0
		 */
		public function doPut( $url = '', $postData = array(), $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$response = null;
			if ( ! empty( $url ) ) {
				$content  = $this->executeUrlCall( $url, $postData, NETCURL_POST_METHODS::METHOD_PUT, $postAs );
				$response = $this->netcurl_parse( $content );
			}

			return $response;
		}

		/**
		 * Make DELETE request
		 *
		 * @param string $url
		 * @param array  $postData
		 * @param int    $postAs
		 *
		 * @return array|null|string|MODULE_CURL|NETCURL_HTTP_OBJECT
		 * @throws \Exception
		 * @since 5.0
		 */
		public function doDelete( $url = '', $postData = array(), $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$response = null;
			if ( ! empty( $url ) ) {
				$content  = $this->executeUrlCall( $url, $postData, NETCURL_POST_METHODS::METHOD_DELETE, $postAs );
				$response = $this->netcurl_parse( $content );
			}

			return $response;
		}

		/**
		 * Make GET request
		 *
		 * @param string $url
		 * @param int    $postAs
		 *
		 * @return array|null|string|MODULE_CURL|NETCURL_HTTP_OBJECT
		 * @throws \Exception
		 * @since 5.0
		 */
		public function doGet( $url = '', $postAs = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$response = null;
			if ( ! empty( $url ) ) {
				$content  = $this->executeUrlCall( $url, array(), NETCURL_POST_METHODS::METHOD_GET, $postAs );
				$response = $this->netcurl_parse( $content );
			}

			return $response;
		}

		/**
		 * Configure authentication
		 *
		 * @param null $Username
		 * @param null $Password
		 * @param int  $AuthType Falls back on CURLAUTH_ANY if none are given. NETCURL_AUTH_TYPES are minimalistic since it follows the standards of CURLAUTH_
		 *
		 * @throws \Exception
		 * @since 6.0
		 */
		public function setAuthentication( $Username = null, $Password = null, $AuthType = NETCURL_AUTH_TYPES::AUTHTYPE_BASIC ) {
			$this->AuthData['Username'] = $Username;
			$this->AuthData['Password'] = $Password;
			$this->AuthData['Type']     = $AuthType;
			if ( $AuthType !== NETCURL_AUTH_TYPES::AUTHTYPE_NONE ) {
				// Default behaviour on authentications via SOAP should be to catch authfail warnings
				$this->setFlag( "SOAPWARNINGS", true );
			}
		}

		/**
		 * Fix problematic header data by converting them to proper outputs.
		 *
		 * @param array $headerList
		 *
		 * @since 6.0
		 */
		private function fixHttpHeaders( $headerList = array() ) {
			if ( is_array( $headerList ) && count( $headerList ) ) {
				foreach ( $headerList as $headerKey => $headerValue ) {
					$testHead = explode( ":", $headerValue, 2 );
					if ( isset( $testHead[1] ) ) {
						$this->NETCURL_HTTP_HEADERS[] = $headerValue;
					} else {
						if ( ! is_numeric( $headerKey ) ) {
							$this->NETCURL_HTTP_HEADERS[] = $headerKey . ": " . $headerValue;
						}
					}
				}
			}
		}

		/**
		 * Add extra curl headers
		 *
		 * @param string $key
		 * @param string $value
		 *
		 * @since 6.0
		 */
		public function setCurlHeader( $key = '', $value = '' ) {
			if ( ! empty( $key ) ) {
				$this->NETCURL_HEADERS_USER_DEFINED[ $key ] = $value;
			}
		}

		/**
		 * Return user defined headers
		 *
		 * @return array
		 * @since 6.0.6
		 */
		public function getCurlHeader() {
			return $this->NETCURL_HEADERS_USER_DEFINED;
		}

		/**
		 * Make sure that postdata is correctly rendered to interfaces before sending it
		 *
		 * @return string
		 * @throws \Exception
		 * @since 6.0.15
		 */
		private function executePostData() {
			$this->POST_DATA_REAL = $this->NETCURL_POST_DATA;
			$postDataContainer    = $this->NETCURL_POST_DATA;
			$POST_AS_DATATYPE     = $this->NETCURL_POST_DATA_TYPE;

			// Enforce postAs: If you'd like to force everything to use json you can for example use: $myLib->setPostTypeDefault(NETCURL_POST_DATATYPES::DATATYPE_JSON)
			if ( ! is_null( $this->FORCE_POST_TYPE ) ) {
				$POST_AS_DATATYPE = $this->FORCE_POST_TYPE;
			}
			$parsedPostData = $this->NETCURL_POST_DATA;
			if ( is_array( $this->NETCURL_POST_DATA ) || is_object( $this->NETCURL_POST_DATA ) ) {
				$postDataContainer = http_build_query( $this->NETCURL_POST_DATA );
			}
			$this->POSTDATACONTAINER = $postDataContainer;

			if ( $POST_AS_DATATYPE == NETCURL_POST_DATATYPES::DATATYPE_JSON ) {
				$parsedPostData = $this->transformPostDataJson();
			} else if ( ( $POST_AS_DATATYPE == NETCURL_POST_DATATYPES::DATATYPE_XML || $POST_AS_DATATYPE == NETCURL_POST_DATATYPES::DATATYPE_SOAP_XML ) ) {
				$parsedPostData = $this->transformPostDataXml();
			}

			$this->POST_DATA_HANDLED = $parsedPostData;

			return $parsedPostData;
		}

		/**
		 * @return array|null|string
		 * @since 6.0.20
		 */
		private function transformPostDataJson() {
			// Using $jsonRealData to validate the string
			$jsonRealData = null;
			if ( ! is_string( $this->NETCURL_POST_DATA ) ) {
				$jsonRealData = json_encode( $this->NETCURL_POST_DATA );
			} else {
				$testJsonData = json_decode( $this->NETCURL_POST_DATA );
				if ( is_object( $testJsonData ) || is_array( $testJsonData ) ) {
					$jsonRealData = $this->NETCURL_POST_DATA;
				}
			}

			return $jsonRealData;
		}

		/**
		 * @return mixed|null|string
		 * @since 6.0.20
		 */
		private function transformPostDataXml() {
			$this->setContentType( 'text/xml' ); // ; charset=utf-8
			$this->setCurlHeader( 'Content-Type', $this->getContentType() );
			$parsedPostData = null;
			if ( ! empty( $this->NETCURL_POST_PREPARED_XML ) ) {
				$parsedPostData = $this->NETCURL_POST_PREPARED_XML;
			} else {
				try {
					if ( is_array( $this->NETCURL_POST_DATA ) && count( $this->NETCURL_POST_DATA ) ) {
						if ( ! is_null( $this->IO ) ) {
							$parsedPostData = $this->IO->renderXml( $this->NETCURL_POST_DATA );
						} else {
							throw new \Exception( NETCURL_CURL_CLIENTNAME . " can not render XML data properly, since the IO library is not initialized", $this->NETWORK->getExceptionCode( 'NETCURL_PARSE_XML_FAILURE' ) );
						}
					}
				} catch ( \Exception $e ) {
					// Silently fail and return nothing if prepared data is failing
				}
			}

			return $parsedPostData;
		}

		/**
		 * Make sure that we are allowed to do things
		 *
		 * @param bool $checkSafeMode If true, we will also check if safe_mode is active
		 * @param bool $mockSafeMode  If true, NetCurl will pretend safe_mode is true (for testing)
		 *
		 * @return bool If true, PHP is in secure mode and won't allow things like follow-redirects and setting up different paths for certificates, etc
		 * @since 6.0.20
		 */
		public function getIsSecure( $checkSafeMode = true, $mockSafeMode = false ) {
			$currentBaseDir = trim( ini_get( 'open_basedir' ) );
			if ( $checkSafeMode ) {
				if ( $currentBaseDir == '' && ! $this->getSafeMode( $mockSafeMode ) ) {
					return false;
				}

				return true;
			} else {
				if ( $currentBaseDir == '' ) {
					return false;
				}

				return true;
			}

		}

		/**
		 * Get safe_mode status (mockable)
		 *
		 * @param bool $mockedSafeMode When active, this always returns true
		 *
		 * @return bool
		 * @since 6.0.20
		 */
		private function getSafeMode( $mockedSafeMode = false ) {
			if ( $mockedSafeMode ) {
				return true;
			}

			// There is no safe mode in PHP 5.4.0 and above
			if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {
				return false;
			}

			return ( filter_var( ini_get( 'safe_mode' ), FILTER_VALIDATE_BOOLEAN ) );
		}

		/**
		 * Trust the pems defined from SSL_MODULE
		 *
		 * @param bool $iTrustBundlesSetBySsl If this is false, NetCurl will trust internals (PHP + Curl) rather than pre-set pem bundles
		 *
		 * @since 6.0.20
		 */
		public function setTrustedSslBundles( $iTrustBundlesSetBySsl = false ) {
			$this->TRUST_SSL_BUNDLES = $iTrustBundlesSetBySsl;
			if ( $iTrustBundlesSetBySsl ) {
				$this->setSslUserAgent();
			}
		}

		/**
		 * The current status of trusted pems
		 *
		 * @return bool
		 * @since 6.0.20
		 */
		public function getTrustedSslBundles() {
			return $this->TRUST_SSL_BUNDLES;
		}

		/**
		 * @since 6.0.20
		 */
		private function setSslUserAgent() {
			$this->setUserAgent( NETCURL_SSL_CLIENTNAME . "-" . NETCURL_SSL_RELEASE );
		}

		/**
		 * @throws \Exception
		 */
		private function internal_curl_configure_ssl() {
			$certificateBundle = $this->SSL->getSslCertificateBundle();
			// Change default behaviour for SSL certificates only if PHP is not in a secure mode (checking open_basedir only).
			if ( ! $this->getIsSecure( false ) ) {
				$this->setSslUserAgent();
				// If strict certificate verification is disabled, we will push some curlopts into unsafe mode.
				if ( ! $this->SSL->getStrictVerification() ) {
					$this->setCurlOpt( CURLOPT_SSL_VERIFYHOST, 0 );
					$this->setCurlOpt( CURLOPT_SSL_VERIFYPEER, 0 );
					$this->unsafeSslCall = true;
				} else {
					// From libcurl 7.28.1 CURLOPT_SSL_VERIFYHOST is deprecated. However, using the value 1 can be used
					// as of PHP 5.4.11, where the deprecation notices was added. The deprecation has started before libcurl
					// 7.28.1 (this was discovered on a server that was running PHP 5.5 and libcurl-7.22). In full debug
					// even libcurl-7.22 was generating this message, so from PHP 5.4.11 we are now enforcing the value 2
					// for CURLOPT_SSL_VERIFYHOST instead. The reason of why we are using the value 1 before this version
					// is actually a lazy thing, as we don't want to break anything that might be unsupported before this version.

					// Those settings are probably default in CURL.
					if ( version_compare( PHP_VERSION, '5.4.11', ">=" ) ) {
						$this->setCurlOptInternal( CURLOPT_SSL_VERIFYHOST, 2 );
					} else {
						$this->setCurlOptInternal( CURLOPT_SSL_VERIFYHOST, 1 );
					}
					$this->setCurlOptInternal( CURLOPT_SSL_VERIFYPEER, 1 );

					try {
						if ( $this->getTrustedSslBundles() ) {
							if ( $this->getFlag( 'OVERRIDE_CERTIFICATE_BUNDLE' ) ) {
								$certificateBundle = $this->getFlag( 'OVERRIDE_CERTIFICATE_BUNDLE' );
							}
							$this->setCurlOptInternal( CURLOPT_CAINFO, $certificateBundle );
							$this->setCurlOptInternal( CURLOPT_CAPATH, dirname( $certificateBundle ) );
						}
					} catch ( \Exception $e ) {
						// Silently ignore errors
					}

				}
			}
		}

		/**
		 * Initializes internal curl driver
		 *
		 * @param bool $reinitialize
		 *
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function initCurl( $reinitialize = false ) {
			if ( is_null( $this->NETCURL_CURL_SESSION ) || $reinitialize ) {
				$this->NETCURL_CURL_SESSION = curl_init( $this->CURL_STORED_URL );
			}
			$this->NETCURL_HTTP_HEADERS = array();
			// CURL CONDITIONAL SETUP
			$this->internal_curl_configure_cookies();
			$this->internal_curl_configure_ssl();
			$this->internal_curl_configure_follow();
			$this->internal_curl_configure_postdata();
			$this->internal_curl_configure_timeouts();
			$this->internal_curl_configure_resolver();
			$this->internal_curl_confiure_proxy_tunnels();
			$this->internal_curl_configure_clientdata();
			$this->internal_curl_configure_userauth();

			// CURL UNCONDITIONAL SETUP
			$this->setCurlOptInternal( CURLOPT_VERBOSE, false );

			// This curlopt makes it possible to make a call to a specific ip address and still use the HTTP_HOST (Must override)
			$this->setCurlOpt( CURLOPT_URL, $this->CURL_STORED_URL );

			// Things that should be overwritten if set by someone else
			$this->setCurlOpt( CURLOPT_HEADER, true );
			$this->setCurlOpt( CURLOPT_RETURNTRANSFER, true );
			$this->setCurlOpt( CURLOPT_AUTOREFERER, true );
			$this->setCurlOpt( CURLINFO_HEADER_OUT, true );
		}

		/**
		 * Set up rules of follow for curl
		 *
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function internal_curl_configure_follow() {
			// Find out if CURLOPT_FOLLOWLOCATION can be set by user/developer or not.
			//
			// Make sure the safety control occurs even when the enforcing parameter is false.
			// This should prevent problems when $this->>followLocationSet is set to anything else than false
			// and security settings are higher for PHP. From v6.0.2, the in this logic has been simplified
			// to only set any flags if the security levels of PHP allows it, and only if the follow flag is enabled.
			//
			// Refers to http://php.net/manual/en/ini.sect.safe-mode.php
			if ( ! $this->getIsSecure( true ) ) {
				// To disable the default behaviour of this function, use setEnforceFollowLocation([bool]).
				if ( $this->FOLLOW_LOCATION_ENABLE ) {
					// Since setCurlOptInternal is not an overrider, using the overrider here, will have no effect on the curlopt setting
					// as it has already been set from our top defaults. This has to be pushed in, by force.
					$this->setCurlOpt( CURLOPT_FOLLOWLOCATION, $this->FOLLOW_LOCATION_ENABLE );
				}
			}
		}

		/**
		 * Prepare postdata for curl
		 *
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function internal_curl_configure_postdata() {
			// Lazysession: Sets post data if any found and sends it even if the curl-method is GET or any other than POST
			// The postdata section must overwrite others, since the variables are set more than once depending on how the data
			// changes or gets converted. The internal curlOpt setter don't overwrite variables if they are alread set.
			if ( ! empty( $this->POSTDATACONTAINER ) ) {
				$this->setCurlOpt( CURLOPT_POSTFIELDS, $this->POSTDATACONTAINER );
			}
			if ( $this->NETCURL_POST_METHOD == NETCURL_POST_METHODS::METHOD_POST || $this->NETCURL_POST_METHOD == NETCURL_POST_METHODS::METHOD_PUT || $this->NETCURL_POST_METHOD == NETCURL_POST_METHODS::METHOD_DELETE ) {
				if ( $this->NETCURL_POST_METHOD == NETCURL_POST_METHODS::METHOD_PUT ) {
					$this->setCurlOpt( CURLOPT_CUSTOMREQUEST, 'PUT' );
				} else if ( $this->NETCURL_POST_METHOD == NETCURL_POST_METHODS::METHOD_DELETE ) {
					$this->setCurlOpt( CURLOPT_CUSTOMREQUEST, 'DELETE' );
				} else {
					$this->setCurlOpt( CURLOPT_POST, true );
				}

				if ( $this->NETCURL_POST_DATA_TYPE == NETCURL_POST_DATATYPES::DATATYPE_JSON ) {

                    // Use standard content type if nothing else is set
				    $useContentType = "application/json; charset=utf-8";
				    $testContentType = $this->getContentType();
				    // Test user input, if setContentType has changed the content type, use this instead, with conditions that
                    // it is still json. This should patch away a strange "bug" at packagist.org amongst others.
				    if (preg_match("/json/i", $testContentType)) {
				        $useContentType = $testContentType;
                    }

					// Using $jsonRealData to validate the string
					$this->NETCURL_HEADERS_SYSTEM_DEFINED['Content-Type']   = $useContentType;
					$this->NETCURL_HEADERS_SYSTEM_DEFINED['Content-Length'] = strlen( $this->POST_DATA_HANDLED );
					$this->setCurlOpt( CURLOPT_POSTFIELDS, $this->POST_DATA_HANDLED );  // overwrite old
				} else if ( ( $this->NETCURL_POST_DATA_TYPE == NETCURL_POST_DATATYPES::DATATYPE_XML || $this->NETCURL_POST_DATA_TYPE == NETCURL_POST_DATATYPES::DATATYPE_SOAP_XML ) ) {
					$this->NETCURL_HEADERS_SYSTEM_DEFINED['Content-Type']   = 'text/xml'; // ; charset=utf-8
					$this->NETCURL_HEADERS_SYSTEM_DEFINED['Content-Length'] = is_string( $this->NETCURL_POST_DATA ) ? strlen( $this->NETCURL_POST_DATA ) : 0;
					$this->setCurlOpt( CURLOPT_CUSTOMREQUEST, 'POST' );
					$this->setCurlOpt( CURLOPT_POSTFIELDS, $this->POST_DATA_HANDLED );
				}
			}
		}

		/**
		 * Configure curltimeouts
		 *
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function internal_curl_configure_timeouts() {
			// Self set timeouts, making sure the timeout set in the public is an integer over 0. Otherwise this falls back to the curldefauls.
            if (isset($this->NETCURL_CURL_TIMEOUT) && $this->NETCURL_CURL_TIMEOUT > 0) {
				$this->setCurlOpt( CURLOPT_CONNECTTIMEOUT, ceil( $this->NETCURL_CURL_TIMEOUT / 2 ) );
				$this->setCurlOpt( CURLOPT_TIMEOUT, ceil( $this->NETCURL_CURL_TIMEOUT ) );
			}
		}

		/**
		 * Configure how to handle DNS resolver
		 *
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function internal_curl_configure_resolver() {
			if ( isset( $this->CURL_RESOLVE_TYPE ) && $this->CURL_RESOLVE_TYPE !== NETCURL_RESOLVER::RESOLVER_DEFAULT ) {
				if ( $this->CURL_RESOLVE_TYPE == NETCURL_RESOLVER::RESOLVER_IPV4 ) {
					$this->setCurlOptInternal( CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
				}
				if ( $this->CURL_RESOLVE_TYPE == NETCURL_RESOLVER::RESOLVER_IPV6 ) {
					$this->setCurlOptInternal( CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V6 );
				}
			}
		}

		/**
		 * Prepare proxy and tunneling mode
		 *
		 * @since 6.0.20
		 */
		private function internal_curl_confiure_proxy_tunnels() {
			// Tunnel and proxy setup. If this is set, make sure the default IP setup gets cleared out.
			if ( ! empty( $this->CURL_PROXY_GATEWAY ) && ! empty( $this->CURL_PROXY_TYPE ) ) {
				unset( $this->CURL_IP_ADDRESS );
			}
			if ( $this->getTunnel() ) {
				unset( $this->CURL_IP_ADDRESS );
			}
		}

		/**
		 * Prepare user agent and referers
		 *
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function internal_curl_configure_clientdata() {
			if ( isset( $this->NETCURL_HTTP_REFERER ) && ! empty( $this->NETCURL_HTTP_REFERER ) ) {
				$this->setCurlOptInternal( CURLOPT_REFERER, $this->NETCURL_HTTP_REFERER );
			}
			if ( isset( $this->HTTP_USER_AGENT ) && ! empty( $this->HTTP_USER_AGENT ) ) {
				$this->setCurlOpt( CURLOPT_USERAGENT, $this->HTTP_USER_AGENT ); // overwrite old
			}
			if ( isset( $this->HTTP_CHARACTER_ENCODING ) && ! empty( $this->HTTP_CHARACTER_ENCODING ) ) {
				$this->setCurlOpt( CURLOPT_ENCODING, $this->HTTP_CHARACTER_ENCODING ); // overwrite old
			}
		}

		/**
		 * Prepare cookies if requested
		 *
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function internal_curl_configure_cookies() {
			if ( file_exists( $this->COOKIE_PATH ) && $this->getUseCookies() && ! empty( $this->CURL_STORED_URL ) ) {
				$domainArray = $this->NETWORK->getUrlDomain( $this->CURL_STORED_URL );
				$domainHash  = '';
				if ( isset( $domainArray[0] ) ) {
					$domainHash = sha1( $domainArray[0] );
				}

				@file_put_contents( $this->COOKIE_PATH . "/tmpcookie", "test" );
				if ( ! file_exists( $this->COOKIE_PATH . "/tmpcookie" ) ) {
					$this->SaveCookies = true;
					$this->CookieFile  = $domainHash;
					$this->setCurlOptInternal( CURLOPT_COOKIEFILE, $this->COOKIE_PATH . "/" . $this->CookieFile );
					$this->setCurlOptInternal( CURLOPT_COOKIEJAR, $this->COOKIE_PATH . "/" . $this->CookieFile );
					$this->setCurlOptInternal( CURLOPT_COOKIE, 1 );
				} else {
					if ( file_exists( $this->COOKIE_PATH . "/tmpcookie" ) ) {
						unlink( $this->COOKIE_PATH . "/tmpcookie" );
					}
					$this->SaveCookies = false;
				}
			} else {
				$this->SaveCookies = false;
			}
		}

		/**
		 * Prepare http-headers
		 *
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function internal_curl_configure_headers() {
			if ( $this->isCurl() ) {
				if ( isset( $this->NETCURL_HTTP_HEADERS ) && is_array( $this->NETCURL_HTTP_HEADERS ) && count( $this->NETCURL_HTTP_HEADERS ) ) {
					$this->setCurlOpt( CURLOPT_HTTPHEADER, $this->NETCURL_HTTP_HEADERS ); // overwrite old
				}
			}
		}

		/**
		 * Set up authentication data
		 *
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function internal_curl_configure_userauth() {
			if ( ! empty( $this->AuthData['Username'] ) ) {
				$useAuth = $this->AuthData['Type'];
				if ( $this->AuthData['Type'] != NETCURL_AUTH_TYPES::AUTHTYPE_NONE ) {
					$useAuth = CURLAUTH_ANY;
					if ( $this->AuthData['Type'] == NETCURL_AUTH_TYPES::AUTHTYPE_BASIC ) {
						$useAuth = CURLAUTH_BASIC;
					}
				}
				$this->setCurlOptInternal( CURLOPT_HTTPAUTH, $useAuth );
				$this->setCurlOptInternal( CURLOPT_USERPWD, $this->AuthData['Username'] . ':' . $this->AuthData['Password'] );
			}
		}

		/**
		 * Add debug data
		 *
		 * @param $returnContent
		 *
		 * @since 6.0.20
		 */
		private function internal_curl_execute_add_debug( $returnContent ) {
			if ( curl_errno( $this->NETCURL_CURL_SESSION ) ) {
				$this->DEBUG_DATA['data']['url'][] = array(
					'url'       => $this->CURL_STORED_URL,
					'opt'       => $this->getCurlOptByKeys(),
					'success'   => false,
					'exception' => curl_error( $this->NETCURL_CURL_SESSION )
				);

				if ( $this->canStoreSessionException ) {
					$this->sessionsExceptions[] = array(
						'Content'     => $returnContent,
						'SessionInfo' => curl_getinfo( $this->NETCURL_CURL_SESSION )
					);
				}
			}
		}

		/**
		 * Handle curl-errors
		 *
		 * @return bool
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function internal_curl_errors() {
			$this->NETCURL_ERRORHANDLER_HAS_ERRORS = false;
			$this->NETCURL_ERRORHANDLER_RERUN      = false;

			$errorCode    = curl_errno( $this->NETCURL_CURL_SESSION ) > 0 ? curl_errno( $this->NETCURL_CURL_SESSION ) : null;
			$errorMessage = curl_error( $this->NETCURL_CURL_SESSION ) != '' ? curl_error( $this->NETCURL_CURL_SESSION ) : null;

			if ( ! is_null( $errorCode ) || ! is_null( $errorMessage ) ) {
				$this->NETCURL_ERRORHANDLER_HAS_ERRORS = true;
				$this->internal_curl_error_ssl( $errorCode, $errorMessage );

				// Special case: Resolver failures
				if ( $this->CURL_RESOLVER_FORCED && $this->CURL_RETRY_TYPES['resolve'] >= 2 ) {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " exception in " . __FUNCTION__ . ": The maximum tries of curl_exec() for " . $this->CURL_STORED_URL . " has been reached without any successful response. Normally, this happens after " . $this->CURL_RETRY_TYPES['resolve'] . " CurlResolveRetries and might be connected with a bad URL or similar that can not resolve properly.\nCurl error message follows: " . $errorMessage, $errorCode );
				}
				$this->internal_curl_error_resolver( $errorCode, $errorMessage );
			}

			if ( $this->NETCURL_ERRORHANDLER_HAS_ERRORS && ! $this->NETCURL_ERRORHANDLER_RERUN ) {
				throw new \Exception( NETCURL_CURL_CLIENTNAME . " exception from PHP/CURL at " . __FUNCTION__ . ": " . curl_error( $this->NETCURL_CURL_SESSION ), curl_errno( $this->NETCURL_CURL_SESSION ) );
			}

			return $this->NETCURL_ERRORHANDLER_HAS_ERRORS;
		}

		/**
		 * @param $errorCode
		 * @param $errorMessage
		 *
		 * @since 6.0.20
		 */
		private function internal_curl_error_resolver( $errorCode, $errorMessage ) {
			if ( $errorCode == CURLE_COULDNT_RESOLVE_HOST || $errorCode === 45 ) {
				$this->NETCURL_ERROR_CONTAINER[] = array( 'code' => $errorCode, 'message' => $errorMessage );
				unset( $this->CURL_IP_ADDRESS );
				$this->CURL_RESOLVER_FORCED = true;
				if ( $this->CURL_IP_ADDRESS_TYPE == 6 ) {
					$this->setCurlResolve( NETCURL_RESOLVER::RESOLVER_IPV4 );
					$this->CURL_IP_ADDRESS_TYPE = 4;
				} else if ( $this->CURL_IP_ADDRESS_TYPE == 4 ) {
					$this->setCurlResolve( NETCURL_RESOLVER::RESOLVER_IPV6 );
					$this->CURL_IP_ADDRESS_TYPE = 6;
				} else {
					$this->CURL_IP_ADDRESS_TYPE = 4;
					$this->setCurlResolve( NETCURL_RESOLVER::RESOLVER_IPV4 );
				}
				if ( $this->CURL_RETRY_TYPES['resolve'] <= 2 ) {
					$this->NETCURL_ERRORHANDLER_RERUN = true;
				}
				$this->CURL_RETRY_TYPES['resolve'] ++;
			}
		}

		/**
		 * Redirects to sslVerificationAdjustment
		 *
		 * @param $errorCode
		 * @param $errorMessage
		 *
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function internal_curl_error_ssl( $errorCode, $errorMessage ) {
			$this->sslVerificationAdjustment( $errorCode, $errorMessage );
		}

		/**
		 * @param $errorCode
		 * @param $errorMessage
		 *
		 * @throws \Exception
		 */
		private function sslVerificationAdjustment( $errorCode, $errorMessage ) {
			// Special case: SSL failures (CURLE_SSL_CACERT = 60)
			if ( $this->SSL->getStrictFallback() ) {
				if ( $errorCode == CURLE_SSL_CACERT ) {
					if ( $this->CURL_RETRY_TYPES['sslunverified'] >= 2 ) {
						throw new \Exception( NETCURL_CURL_CLIENTNAME . " exception in " . __FUNCTION__ . ": The maximum tries of curl_exec() for " . $this->CURL_STORED_URL . ", during a try to make a SSL connection to work, has been reached without any successful response. This normally happens when allowSslUnverified is activated in the library and " . $this->CURL_RETRY_TYPES['resolve'] . " tries to fix the problem has been made, but failed.\nCurl error message follows: " . $errorMessage, $errorCode );
					} else {
						$this->NETCURL_ERROR_CONTAINER[] = array( 'code' => $errorCode, 'message' => $errorMessage );
						$this->setSslVerify( false, false );
						$this->unsafeSslCall = true;
						$this->CURL_RETRY_TYPES['sslunverified'] ++;
						$this->NETCURL_ERRORHANDLER_RERUN = true;
					}
				}
				if ( false === strpos( $errorMessage, '14090086' ) && false === strpos( $errorMessage, '1407E086' ) ) {
					$this->NETCURL_ERROR_CONTAINER[] = array( 'code' => $errorCode, 'message' => $errorMessage );
					$this->setSslVerify( false, false );
					$this->unsafeSslCall = true;
					$this->CURL_RETRY_TYPES['sslunverified'] ++;
					$this->NETCURL_ERRORHANDLER_RERUN = true;
				}

			}
		}

		/**
		 * Check if NetCurl is allowed to rerun curl-call
		 *
		 * @return bool
		 * @since 6.0.20
		 */
		private function internal_curl_can_rerun() {
			return $this->NETCURL_ERRORHANDLER_RERUN;
		}

		/**
		 * @return mixed
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function internal_curl_execute() {
			$returnContent = curl_exec( $this->NETCURL_CURL_SESSION );
			$this->internal_curl_execute_add_debug( $returnContent );

			if ( $this->internal_curl_errors() ) {
				if ( $this->internal_curl_can_rerun() ) {
					return $this->executeUrlCall( $this->CURL_STORED_URL, $this->POST_DATA_HANDLED, $this->NETCURL_POST_METHOD );
				}
			}

			return $returnContent;
		}

		/**
		 * Run SOAP calls if any
		 *
		 * @return null|MODULE_SOAP
		 * @throws \Exception
		 * @since 6.0.20
		 */
		private function internal_soap_checker() {

			$isSoapRequest = false;

			if ( $this->NETCURL_POST_DATA_TYPE == NETCURL_POST_DATATYPES::DATATYPE_SOAP ) {
				$isSoapRequest = true;
			}
			if ( preg_match( "/\?wsdl$|\&wsdl$/i", $this->CURL_STORED_URL ) && $this->NETCURL_POST_DATA_TYPE == NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
				$isSoapRequest = true;
			}

			// SOAP HANDLER: Override with SoapClient just before the real curl_exec is the most proper way to handle inheritages
			if ( $isSoapRequest ) {
				if ( ! $this->hasSoap() ) {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " " . __FUNCTION__ . " exception: SoapClient is not available in this system", $this->NETWORK->getExceptionCode( 'NETCURL_SOAPCLIENT_CLASS_MISSING' ) );
				}
				if ( ! $this->isFlag( 'NOSOAPWARNINGS' ) ) {
					$this->setFlag( "SOAPWARNINGS", true );
				} else {
					$this->unsetFlag( 'SOAPWARNINGS' );
				}

				return $this->executeHttpSoap( $this->CURL_STORED_URL, $this->NETCURL_POST_DATA, $this->NETCURL_POST_DATA_TYPE );
			}
			$this->unsetFlag( 'IS_SOAP' );
			if ( $this->isFlag( 'WAS_SOAP_CHAIN' ) ) {
				// Enable chaining if flags was reset by SOAP
				$this->setChain( true );
				$this->unsetFlag( 'WAS_SOAP_CHAIN' );
			}

			return null;

		}

		/**
		 * cURL data handler, sets up cURL in what it believes is the correct set for you.
		 *
		 * @param string $url
		 * @param array  $postData
		 * @param int    $postMethod
		 * @param int    $postDataType
		 *
		 * @return mixed
		 * @throws \Exception
		 * @since 6.0
		 */
		private function executeUrlCall( $url = '', $postData = array(), $postMethod = NETCURL_POST_METHODS::METHOD_GET, $postDataType = NETCURL_POST_DATATYPES::DATATYPE_NOT_SET ) {
			$currentDriver = $this->getDriver();
			$returnContent = null;

			if ( ! empty( $url ) ) {
				$this->CURL_STORED_URL = $url;
			}
			$this->NETCURL_POST_DATA      = $postData;
			$this->NETCURL_POST_METHOD    = $postMethod;
			$this->NETCURL_POST_DATA_TYPE = $postDataType;
			$this->DEBUG_DATA['calls'] ++;

			// Initialize drivers
			$this->executePostData();
			$this->initializeNetCurl();
			$this->handleIpList();

			// Headers used by any
			$this->fixHttpHeaders( $this->NETCURL_HEADERS_USER_DEFINED );
			$this->fixHttpHeaders( $this->NETCURL_HEADERS_SYSTEM_DEFINED );
			// This must run after http headers fix
			$this->internal_curl_configure_headers();
			$soapResponseTest = $this->internal_soap_checker();

			if ( ! is_null( $soapResponseTest ) ) {
				return $soapResponseTest;
			}

			if ( $currentDriver === NETCURL_NETWORK_DRIVERS::DRIVER_CURL ) {
				try {
					$returnContent = $this->internal_curl_execute();

					$this->DEBUG_DATA['data']['url'][] = array(
						'url'       => $this->CURL_STORED_URL,
						'opt'       => $this->getCurlOptByKeys(),
						'success'   => true,
						'exception' => null
					);
				} catch ( \Exception $e ) {
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " exception from PHP/CURL at " . __FUNCTION__ . ": " . $e->getMessage(), $e->getCode(), $e );
				}
			} else {
				if ( is_object( $currentDriver ) && method_exists( $currentDriver, 'executeNetcurlRequest' ) ) {
					$returnContent = $currentDriver->executeNetcurlRequest( $this->CURL_STORED_URL, $this->POST_DATA_HANDLED, $this->NETCURL_POST_METHOD, $this->NETCURL_POST_DATA_TYPE );
				}
			}

			return $returnContent;
		}

		/**
		 * SOAPClient detection method (moved from primary curl executor to make it possible to detect soapcalls from other Addons)
		 *
		 * @param string $url
		 * @param array  $postData
		 * @param int    $CurlMethod
		 *
		 * @return MODULE_SOAP
		 * @throws \Exception
		 * @since 6.0.14
		 */
		private function executeHttpSoap( $url = '', $postData = array(), $CurlMethod = NETCURL_POST_METHODS::METHOD_GET ) {
			$Soap = new MODULE_SOAP( $this->CURL_STORED_URL, $this );

			// Proper inherits
			foreach ( $this->getFlags() as $flagKey => $flagValue ) {
				$this->setFlag( $flagKey, $flagValue );
				$Soap->setFlag( $flagKey, $flagValue );
			}

			$this->setFlag( 'WAS_SOAP_CHAIN', $this->getIsChained() );
			$Soap->setFlag( 'WAS_SOAP_CHAIN', $this->getIsChained() );
			$this->setChain( false );
			$Soap->setFlag( 'IS_SOAP' );
			$this->setFlag( 'IS_SOAP' );

			/** @since 6.0.20 */
			$Soap->setChain( false );
			if ( $this->hasFlag( 'SOAPCHAIN' ) ) {
				$Soap->setFlag( 'SOAPCHAIN', $this->getFlag( 'SOAPCHAIN' ) );
			}
			$Soap->setCustomUserAgent( $this->CUSTOM_USER_AGENT );
			$Soap->setThrowableState( $this->NETCURL_CAN_THROW );
			$Soap->setSoapAuthentication( $this->AuthData );
			$Soap->setSoapTryOnce( $this->SoapTryOnce );
			try {
				$getSoapResponse                       = $Soap->getSoap();
				$this->DEBUG_DATA['soapdata']['url'][] = array(
					'url'       => $this->CURL_STORED_URL,
					'opt'       => $this->getCurlOptByKeys(),
					'success'   => true,
					'exception' => null,
					'previous'  => null
				);
			} catch ( \Exception $getSoapResponseException ) {

				$this->sslVerificationAdjustment( $getSoapResponseException->getCode(), $getSoapResponseException->getMessage() );

				$this->DEBUG_DATA['soapdata']['url'][] = array(
					'url'       => $this->CURL_STORED_URL,
					'opt'       => $this->getCurlOptByKeys(),
					'success'   => false,
					'exception' => $getSoapResponseException,
					'previous'  => $getSoapResponseException->getPrevious()
				);

				if ( $this->NETCURL_ERRORHANDLER_RERUN ) {
					return $this->executeHttpSoap( $url, $postData, $CurlMethod );
				}

				switch ( $getSoapResponseException->getCode() ) {
					default:
						throw new \Exception( NETCURL_CURL_CLIENTNAME . " exception from SoapClient: [" . $getSoapResponseException->getCode() . "] " . $getSoapResponseException->getMessage(), $getSoapResponseException->getCode() );
				}

			}

			return $getSoapResponse;

		}



		/// DEPRECATIONS TO MOVE

		//////// LONG TIME DEPRECATIONS

		/**
		 * @param null $responseInData
		 *
		 * @return int
		 * @since      6.0
		 * @deprecated 6.0.20 Use getCode
		 */
		public function getResponseCode( $responseInData = null ) {
			return $this->getCode( $responseInData );
		}

		/**
		 * @param null $responseInData
		 *
		 * @return null
		 * @since      6.0
		 * @deprecated 6.0.20 Use getBody
		 */
		public function getResponseBody( $responseInData = null ) {
			return $this->getBody( $responseInData );
		}

		/**
		 * @param null $responseInData
		 *
		 * @return string
		 * @since      6.0.16
		 * @deprecated 6.0.20 Use getUrl
		 */
		public function getResponseUrl( $responseInData = null ) {
			return $this->getUrl( $responseInData );
		}

		/**
		 * @param null $inputResponse
		 *
		 * @return null
		 * @throws \Exception
		 * @since      6.0
		 * @deprecated 6.0.20
		 */
		public function getParsedResponse( $inputResponse = null ) {
			return $this->getParsed( $inputResponse );
		}

		/**
		 * @param null $keyName
		 * @param null $responseContent
		 *
		 * @return mixed|null
		 * @throws \Exception
		 * @since      6.0
		 * @deprecated 6.0.20
		 */
		public function getParsedValue( $keyName = null, $responseContent = null ) {
			return $this->getValue( $keyName, $responseContent );
		}



		//////// DEPRECATED FUNCTIONS BEGIN /////////

		/**
		 * Get what external driver see
		 *
		 * @return null
		 * @since      6.0
		 * @deprecated 6.0.20
		 */
		public function getExternalDriverResponse() {
			/** @noinspection PhpDeprecationInspection */
			return $this->TemporaryExternalResponse;
		}

		/**
		 * @return array
		 *
		 * @since      6.0.16
		 * @deprecated 6.0.20
		 */
		public function getTemporaryResponse() {
			/** @noinspection PhpDeprecationInspection */
			return $this->TemporaryResponse;
		}

		// Created for future use
		/*public function __call( $name, $arguments ) {

			// WARNING: Experimental
			if ( $this->isFlag( 'XMLSOAP' ) && $this->IO->getHasXmlSerializer() && $this->NETCURL_POST_DATA_TYPE == NETCURL_POST_DATATYPES::DATATYPE_SOAP_XML ) {
				$this->setContentType( 'text/xml' ); // ; charset=utf-8
				$this->setCurlHeader( 'Content-Type', $this->getContentType() );
				$soapifyArray = array(
					'Body' => array(
						$name => array()
					)
				);
				$this->IO->setXmlSimple( true );
				$this->IO->setSoapXml( true );
				$this->NETCURL_POST_PREPARED_XML = $this->IO->renderXml( $soapifyArray, false, TORNELIB_CRYPTO_TYPES::TYPE_NONE, $name, 'SOAP-ENV' );

				return $this->doPost( $this->CURL_STORED_URL, $this->NETCURL_POST_PREPARED_XML, NETCURL_POST_DATATYPES::DATATYPE_XML );
			}

			throw new \Exception( NETCURL_CURL_CLIENTNAME . " exception: Function " . $name . " does not exist!", $this->NETWORK->getExceptionCode( "NETCURL_UNEXISTENT_FUNCTION" ) );
		}*/


	}

	if ( ! class_exists( 'Tornevall_cURL', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\Tornevall_cURL', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
		/**
		 * Class MODULE_CURL
		 *
		 * @package    TorneLIB
		 * @throws \Exception
		 * @deprecated 6.0.20
		 */
		class Tornevall_cURL extends MODULE_CURL {
			function __construct( $requestUrl = '', $requestPostData = array(), $requestPostMethod = NETCURL_POST_METHODS::METHOD_POST, $requestFlags = array() ) {
				return parent::__construct( $requestUrl, $requestPostData, $requestPostMethod );
			}
		}
	}
}

if ( ! class_exists( 'MODULE_SOAP', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\MODULE_SOAP', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {

	if ( ! defined( 'NETCURL_SIMPLESOAP_RELEASE' ) ) {
		define( 'NETCURL_SIMPLESOAP_RELEASE', '6.0.6' );
	}
	if ( ! defined( 'NETCURL_SIMPLESOAP_MODIFY' ) ) {
		define( 'NETCURL_SIMPLESOAP_MODIFY', '20180325' );
	}
	if ( ! defined( 'NETCURL_SIMPLESOAP_CLIENTNAME' ) ) {
		define( 'NETCURL_SIMPLESOAP_CLIENTNAME', 'SimpleSOAP' );
	}

	/**
	 * Class TorneLIB_SimpleSoap Simple SOAP client.
	 *
	 * Making no difference of a SOAP call and a regular GET/POST
	 *
	 * @package TorneLIB
	 * @since   6.0.20
	 */
	class MODULE_SOAP extends MODULE_CURL {
		protected $soapClient;
		protected $soapOptions = array();
		protected $addSoapOptions = array(
			'exceptions' => true,
			'trace'      => true,
			'cache_wsdl' => 0       // Replacing WSDL_CACHE_NONE (WSDL_CACHE_BOTH = 3)
		);
		private $soapUrl;
		private $AuthData;
		private $soapRequest;
		private $soapRequestHeaders;
		private $soapResponse;
		private $soapResponseHeaders;
		private $libResponse;
		private $canThrowSoapFaults = true;
		private $soapFaultExceptionObject;
		/** @var MODULE_CURL */
		private $PARENT;

		private $SoapFaultString = null;
		private $SoapFaultCode = 0;
		private $SoapTryOnce = true;

		private $soapInitException = array( 'faultstring' => '', 'code' => 0 );

		/**
		 * MODULE_SOAP constructor.
		 *
		 * @param      $Url
		 * @param null $that
		 *
		 * @throws \Exception
		 */
		function __construct( $Url, $that = null ) {
			// Inherit parent
			parent::__construct();

			/** @var MODULE_CURL */
			$this->PARENT      = $that;      // Get the parent instance from parent, when parent gives wrong information
			$this->soapUrl     = $Url;
			$this->soapOptions = $this->PARENT->getCurlOpt();
			foreach ( $this->addSoapOptions as $soapKey => $soapValue ) {
				if ( ! isset( $this->soapOptions[ $soapKey ] ) ) {
					$this->soapOptions[ $soapKey ] = $soapValue;
				}
			}
			$this->configureInternals();
		}

		/**
		 * Configure internal data
		 *
		 * @since 6.0.3
		 */
		private function configureInternals() {
			$proxySettings = $this->PARENT->getProxy();

			// SOCKS is currently unsupported by SoapClient
			if ( ! empty( $proxySettings['curlProxy'] ) ) {
				$proxyConfig = explode( ":", $proxySettings['curlProxy'] );
				if ( isset( $proxyConfig[1] ) && ! empty( $proxyConfig[0] ) && $proxyConfig[1] > 0 ) {
					$this->soapOptions['proxy_host'] = $proxyConfig[0];
					$this->soapOptions['proxy_port'] = $proxyConfig[1];
				}
			}
		}

		/**
		 * Prepare authentication for SOAP calls
		 *
		 * @param array $AuthData
		 */
		public function setSoapAuthentication( $AuthData = array() ) {
			$this->AuthData = $AuthData;
			if ( ! empty( $this->AuthData['Username'] ) && ! empty( $this->AuthData['Password'] ) && ! isset( $this->soapOptions['login'] ) && ! isset( $this->soapOptions['password'] ) ) {
				$this->soapOptions['login']    = $this->AuthData['Username'];
				$this->soapOptions['password'] = $this->AuthData['Password'];
			}
		}

		/**
		 * @param $userAgentString
		 *
		 * @throws \Exception
		 */
		public function setCustomUserAgent( $userAgentString ) {
			$this->setUserAgent( NETCURL_SIMPLESOAP_CLIENTNAME . "-" . NETCURL_SIMPLESOAP_RELEASE, $userAgentString );
			$this->sslGetOptionsStream();
		}

		/**
		 * Set up this class so that it can throw exceptions
		 *
		 * @param bool $throwable Setting this to false, we will suppress some errors
		 */
		public function setThrowableState( $throwable = true ) {
			$this->canThrowSoapFaults = $throwable;
		}

		/**
		 * Generate the SOAP
		 *
		 * @return $this
		 * @throws \Exception
		 */
		public function getSoap() {
			$this->soapClient = null;
            $throwErrorMessage = null;
            $throwErrorCode    = null;
            $throwBackCurrent  = null;
            $soapFaultOnInit = false;
            //$throwPrevious     = null;
			$sslOpt           = $this->getSslOpt();
			//$optionsStream    = $this->sslGetOptionsStream();
			$optionsStream = $this->PARENT->sslGetOptionsStream();

			if ( is_array( $optionsStream ) && count( $optionsStream ) ) {
				foreach ( $optionsStream as $optionKey => $optionValue ) {
					$this->soapOptions[ $optionKey ] = $optionValue;
				}
			}

			if ( isset( $sslOpt['stream_context'] ) ) {
				if ( gettype( $sslOpt['stream_context'] ) == "resource" ) {
					$this->soapOptions['stream_context'] = $sslOpt['stream_context'];
				}
			}

			$this->soapOptions['exceptions'] = true;
			$this->soapOptions['trace']      = true;

			$parentFlags = $this->PARENT->getFlags();
			foreach ( $parentFlags as $flagKey => $flagValue ) {
				$this->setFlag( $flagKey, $flagValue );
			}

			if ( $this->SoapTryOnce ) {
				try {
					$this->soapClient = @new \SoapClient( $this->soapUrl, $this->soapOptions );
				} catch ( \Exception $soapException ) {
					$soapCode = $soapException->getCode();
					if ( ! $soapCode ) {
						$soapCode = 500;
					}
					$throwErrorMessage = NETCURL_CURL_CLIENTNAME . " (internal/simplesoap) exception from SoapClient: " . $soapException->getMessage();
					$throwErrorCode    = $soapCode;
					$throwBackCurrent  = $soapException;
					//$throwPrevious     = $soapException->getPrevious();
					if ( isset( $parentFlags['SOAPWARNINGS'] ) && $parentFlags['SOAPWARNINGS'] === true ) {
						$soapFaultOnInit = true;
					}
				}

				// If we get an error immediately on the first call, lets find out if there are any warnings we need to know about...
				if ( $soapFaultOnInit ) {
					set_error_handler( function ( $errNo, $errStr ) {
						$throwErrorMessage = $errStr;
						$throwErrorCode    = $errNo;
						if ( empty( $this->soapInitException['faultstring'] ) ) {
							$this->soapInitException['faultstring'] = $throwErrorMessage;
						}
						if ( empty( $this->soapInitException['code'] ) ) {
							$this->soapInitException['code'] = $throwErrorCode;
						}
					}, E_ALL );
					try {
						$this->soapClient = @new \SoapClient( $this->soapUrl, $this->soapOptions );
					} catch ( \Exception $e ) {
						if ( $this->soapInitException['faultstring'] !== $e->getMessage() ) {
							$throwErrorMessage = $this->soapInitException['faultstring'] . "\n" . $e->getMessage();
							$throwErrorCode    = $this->soapInitException['code'];
							if ( preg_match( "/http request failed/i", $throwErrorMessage ) && preg_match( "/http\/(.*?) \d+ (.*?)/i", $throwErrorMessage ) ) {
								preg_match_all( "/! (http\/\d+\.\d+ \d+ (.*?))\n/is", $throwErrorMessage, $outInfo );
								if ( isset( $outInfo[1] ) && isset( $outInfo[1][0] ) && preg_match( "/^HTTP\//", $outInfo[1][0] ) ) {
									$httpError      = $outInfo[1][0];
									$httpSplitError = explode( " ", $httpError, 3 );
									if ( isset( $httpSplitError[1] ) && intval( $httpSplitError[1] ) > 0 ) {
										$throwErrorCode = $httpSplitError[1];
										if ( isset( $httpSplitError[2] ) && is_string( $httpSplitError[2] ) && ! empty( $httpSplitError[2] ) ) {
											if ( ! isset( $parentFlags['SOAPWARNINGS_EXTEND'] ) ) {
												unset( $throwErrorMessage );
											}
                                            $throwErrorMessage = "HTTP-Request exception (" . $throwErrorCode . "): " . $httpSplitError[1] . " " . trim($httpSplitError[2]) . (isset($throwErrorMessage) ? ("\n" . $throwErrorMessage) : null);
										}
									}
								}
							}
						}
					}
					restore_error_handler();
				}

				if ( ! is_object( $this->soapClient ) && is_null( $throwErrorCode ) ) {
					$throwErrorMessage = NETCURL_CURL_CLIENTNAME . " exception from SimpleSoap->getSoap(): Could not create SoapClient. Make sure that all settings and URLs are correctly configured.";
					$throwErrorCode    = 500;
				}
				if ( ! is_null( $throwErrorMessage ) || ! is_null( $throwErrorCode ) ) {
					throw new \Exception( $throwErrorMessage, $throwErrorCode, $throwBackCurrent );
				}
			} else {
				try {
					// FailoverMethod is active per default, trying to parry SOAP-sites that requires ?wsdl in the urls
					$this->soapClient = @new \SoapClient( $this->soapUrl, $this->soapOptions );
				} catch ( \Exception $soapException ) {
					if ( isset( $soapException->faultcode ) && $soapException->faultcode == "WSDL" ) {
						// If an exception has been invoked, check if the url contains a ?wsdl or &wsdl - if not, it may be the problem. In that case, retry the call and throw an exception if we fail twice.
						if ( ! preg_match( "/\?wsdl|\&wsdl/i", $this->soapUrl ) ) {
							// Try to determine how the URL is built before trying this.
							if ( preg_match( "/\?/", $this->soapUrl ) ) {
								$this->soapUrl .= "&wsdl";
							} else {
								$this->soapUrl .= "?wsdl";
							}
							$this->SoapTryOnce = true;
							$this->getSoap();
						}
					}
				}
				if ( ! is_object( $this->soapClient ) ) {
					// NETCURL_SIMPLESOAP_GETSOAP_CREATE_FAIL
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " exception from SimpleSoap->getSoap(): Could not create SoapClient. Make sure that all settings and URLs are correctly configured.", 1008 );
				}
			}

			return $this;
		}

		/**
		 * @param bool $enabledState
		 */
		public function setSoapTryOnce( $enabledState = true ) {
			$this->SoapTryOnce = $enabledState;
		}

		/**
		 * @return bool
		 */
		public function getSoapTryOnce() {
			return $this->SoapTryOnce;
		}

        /**
         * @param $name
         * @param $arguments
         *
         * @return array|null
         * @throws \Exception
         */
		function __call( $name, $arguments ) {
			$returnResponse = array(
				'header' => array( 'info' => null, 'full' => null ),
				'body'   => null,
				'code'   => null
			);

			$SoapClientResponse = null;
			try {
				if ( isset( $arguments[0] ) ) {
					$SoapClientResponse = $this->soapClient->$name( $arguments[0] );
				} else {
					$SoapClientResponse = $this->soapClient->$name();
				}
			} catch ( \Exception $e ) {
				/** @noinspection PhpUndefinedMethodInspection */
				$this->soapRequest = $this->soapClient->__getLastRequest();
				/** @noinspection PhpUndefinedMethodInspection */
				$this->soapRequestHeaders = $this->soapClient->__getLastRequestHeaders();
				/** @noinspection PhpUndefinedMethodInspection */
				$this->soapResponse = $this->soapClient->__getLastResponse();
				/** @noinspection PhpUndefinedMethodInspection */
				$this->soapResponseHeaders = $this->soapClient->__getLastResponseHeaders();
				//$parsedHeader              = $this->getHeader( $this->soapResponseHeaders );
				$this->netcurl_split_raw( $this->soapResponseHeaders );
				$returnResponse['header']       = $this->getHeader();
				$returnResponse['code']         = $this->getCode();
				$returnResponse['body']         = $this->soapResponse;
				$returnResponse['parsed']       = $SoapClientResponse;
				$this->libResponse              = $returnResponse;
				$this->soapFaultExceptionObject = $e;
				if ( $this->canThrowSoapFaults ) {
					$exceptionCode = $e->getCode();
					if ( ! $exceptionCode && $this->getCode() > 0 ) {
						$exceptionCode = $this->getCode();
					}
					throw new \Exception( NETCURL_CURL_CLIENTNAME . " exception from soapClient: " . $e->getMessage(), $exceptionCode, $e );
				}
				$this->SoapFaultString = $e->getMessage();
				$this->SoapFaultCode   = $e->getCode();
			}

			/** @noinspection PhpUndefinedMethodInspection */
			$this->soapRequest = $this->soapClient->__getLastRequest();
			/** @noinspection PhpUndefinedMethodInspection */
			$this->soapRequestHeaders = $this->soapClient->__getLastRequestHeaders();
			/** @noinspection PhpUndefinedMethodInspection */
			$this->soapResponse = $this->soapClient->__getLastResponse();
			/** @noinspection PhpUndefinedMethodInspection */
			$this->soapResponseHeaders = $this->soapClient->__getLastResponseHeaders();
			$headerAndBody             = $this->soapResponseHeaders . "\r\n" . $this->soapResponse; // Own row for debugging

			$this->getHeader( $headerAndBody );
			$returnResponse['parsed'] = $SoapClientResponse;
			if ( isset( $SoapClientResponse->return ) ) {
				$returnResponse['parsed'] = $SoapClientResponse->return;
			}
			$returnResponse['header'] = $this->getHeader();
			$returnResponse['code']   = $this->getCode();
			$returnResponse['body']   = $this->getBody();
			$this->libResponse        = $returnResponse;

			$this->NETCURL_RESPONSE_RAW              = $headerAndBody;
			$this->NETCURL_RESPONSE_CONTAINER_PARSED = $returnResponse['parsed'];
			$this->NETCURL_RESPONSE_CONTAINER_CODE   = $this->getCode();
			$this->NETCURL_RESPONSE_CONTAINER_BODY   = $this->getBody();
			$this->NETCURL_RESPONSE_CONTAINER_HEADER = $this->getHeader();
			$this->NETCURL_RESPONSE_CONTAINER        = $returnResponse;
			$this->NETCURL_REQUEST_HEADERS           = $this->soapRequestHeaders;
			$this->NETCURL_REQUEST_BODY              = $this->soapRequest;

			if ( ! is_null( $this->PARENT ) ) {
				$this->PARENT->NETCURL_RESPONSE_RAW              = $this->NETCURL_RESPONSE_RAW;
				$this->PARENT->NETCURL_RESPONSE_CONTAINER_PARSED = $this->NETCURL_RESPONSE_CONTAINER_PARSED;
				$this->PARENT->NETCURL_RESPONSE_CONTAINER_CODE   = $this->NETCURL_RESPONSE_CONTAINER_CODE;
				$this->PARENT->NETCURL_RESPONSE_CONTAINER_BODY   = $this->NETCURL_RESPONSE_CONTAINER_BODY;
				$this->PARENT->NETCURL_RESPONSE_CONTAINER_HEADER = $this->NETCURL_RESPONSE_CONTAINER_HTTPMESSAGE;
				$this->PARENT->NETCURL_RESPONSE_CONTAINER        = $this->NETCURL_RESPONSE_CONTAINER;
				$this->PARENT->NETCURL_REQUEST_HEADERS           = $this->soapRequestHeaders;
				$this->PARENT->NETCURL_REQUEST_BODY              = $this->soapRequest;
			}

			// HTTPMESSAGE is not applicable for this section
			//$this->NETCURL_RESPONSE_CONTAINER_HTTPMESSAGE = trim( $httpMessage );

			if ( $this->isFlag( 'SOAPCHAIN' ) && isset( $returnResponse['parsed'] ) && ! empty( $returnResponse['parsed'] ) ) {
				return $returnResponse['parsed'];
			}

			return $returnResponse;
		}

		/**
		 * Get the SOAP response independently on exceptions or successes
		 *
		 * @return mixed
		 * @since      5.0.0
		 * @deprecated 6.0.5 Use getSoapResponse()
		 */
		public function getLibResponse() {
			return $this->libResponse;
		}

		public function getSoapFaultString() {
			return $this->SoapFaultString;
		}

		public function getSoapFaultCode() {
			return $this->SoapFaultCode;
		}


		/**
		 * Get the SOAP response independently on exceptions or successes
		 *
		 * @return mixed
		 * @since 6.0.5
		 */
		public function getSoapResponse() {
			return $this->libResponse;
		}

		/**
		 * Get the last thrown soapfault object
		 *
		 * @return mixed
		 * @since 6.0.5
		 */
		public function getSoapFault() {
			return $this->soapFaultExceptionObject;
		}
	}

	if ( ! class_exists( 'Tornevall_SimpleSoap', NETCURL_CLASS_EXISTS_AUTOLOAD ) && ! class_exists( 'Resursbank\RBEcomPHP\Tornevall_SimpleSoap', NETCURL_CLASS_EXISTS_AUTOLOAD ) ) {
		/**
		 * Class MODULE_CURL
		 *
		 * @package    TorneLIB
		 * @deprecated 6.0.20 Use MODULE_SOAP
		 */
		class Tornevall_SimpleSoap extends MODULE_SOAP {
			function __construct( string $Url, $that = null ) {
				parent::__construct( $Url, $that );
			}
		}
	}
}
if ( ! defined('TORNELIB_CRYPTO_RELEASE')) {
    define('TORNELIB_CRYPTO_RELEASE', '6.0.19');
}
if ( ! defined('TORNELIB_CRYPTO_MODIFY')) {
    define('TORNELIB_CRYPTO_MODIFY', '20180822');
}
if ( ! defined('TORNELIB_CRYPTO_CLIENTNAME')) {
    define('TORNELIB_CRYPTO_CLIENTNAME', 'MODULE_CRYPTO');
}
if (!defined('CRYPTO_SKIP_AUTOLOAD')) {
    define('CRYPTO_CLASS_EXISTS_AUTOLOAD', true);
} else {
    define('CRYPTO_CLASS_EXISTS_AUTOLOAD', false);
}
if (defined('TORNELIB_CRYPTO_REQUIRE')) {
    if ( ! defined('TORNELIB_CRYPTO_REQUIRE_OPERATOR')) {
        define('TORNELIB_CRYPTO_REQUIRE_OPERATOR', '==');
    }
    define('TORNELIB_CRYPTO_ALLOW_AUTOLOAD', version_compare(TORNELIB_CRYPTO_RELEASE, TORNELIB_CRYPTO_REQUIRE,
        TORNELIB_CRYPTO_REQUIRE_OPERATOR) ? true : false);
} else {
    if ( ! defined('TORNELIB_CRYPTO_ALLOW_AUTOLOAD')) {
        define('TORNELIB_CRYPTO_ALLOW_AUTOLOAD', true);
    }
}

if ( ! class_exists('MODULE_CRYPTO', CRYPTO_CLASS_EXISTS_AUTOLOAD) && ! class_exists('TorneLIB\MODULE_CRYPTO', CRYPTO_CLASS_EXISTS_AUTOLOAD) && defined('TORNELIB_CRYPTO_ALLOW_AUTOLOAD') && TORNELIB_CRYPTO_ALLOW_AUTOLOAD === true) {

    /**
     * Class TorneLIB_Crypto
     */
    class MODULE_CRYPTO
    {

        private $ENCRYPT_AES_KEY = "";
        private $ENCRYPT_AES_IV = "";

        /**
         * @var $OPENSSL_CIPHER_METHOD
         * @since 6.0.15
         */
        private $OPENSSL_CIPHER_METHOD;

        /**
         * @var $OPENSSL_IV_LENGTH
         * @since 6.0.15
         */
        private $OPENSSL_IV_LENGTH;
        private $COMPRESSION_LEVEL;

        private $USE_MCRYPT = false;

        /**
         * TorneLIB_Crypto constructor.
         */
        function __construct()
        {
            $this->setAesIv(md5("TorneLIB Default IV - Please Change this"));
            $this->setAesKey(md5("TorneLIB Default KEY - Please Change this"));
        }

        /**
         * Set and override compression level
         *
         * @param int $compressionLevel
         *
         * @since 6.0.6
         */
        function setCompressionLevel($compressionLevel = 9)
        {
            $this->COMPRESSION_LEVEL = $compressionLevel;
        }

        /**
         * Get current compressionlevel
         *
         * @return mixed
         * @since 6.0.6
         */
        public function getCompressionLevel()
        {
            return $this->COMPRESSION_LEVEL;
        }

        /**
         * @param bool $enable
         *
         * @since 6.0.15
         */
        public function setMcrypt($enable = false)
        {
            $this->USE_MCRYPT = $enable;
        }

        /**
         * @return bool
         * @since 6.0.15
         */
        public function getMcrypt()
        {
            return $this->USE_MCRYPT;
        }

        /**
         * Create a password or salt with different kind of complexity
         *
         * 1 = A-Z
         * 2 = A-Za-z
         * 3 = A-Za-z0-9
         * 4 = Full usage
         * 5 = Full usage and unrestricted $setMax
         * 6 = Complexity uses full charset of 0-255
         *
         * @param int  $complexity
         * @param int  $setMax      Max string length to use
         * @param bool $webFriendly Set to true works best with the less complex strings as it only removes characters that could be mistaken by another character (O,0,1,l,I etc)
         *
         * @return string
         * @deprecated 6.0.4 Still here for people who needs it
         */
        function mkpass_deprecated($complexity = 4, $setMax = 8, $webFriendly = false)
        {
            $returnString       = null;
            $characterListArray = array(
                'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                'abcdefghijklmnopqrstuvwxyz',
                '0123456789',
                '!@#$%*?'
            );
            // Set complexity to no limit if type 6 is requested
            if ($complexity == 6) {
                $characterListArray = array('0' => '');
                for ($unlim = 0; $unlim <= 255; $unlim++) {
                    $characterListArray[0] .= chr($unlim);
                }
                if ($setMax == null) {
                    $setMax = 15;
                }
            }
            // Backward-compatibility in the complexity will still give us captcha-capabilities for simpler users
            $max = 8;       // Longest complexity
            if ($complexity == 1) {
                unset($characterListArray[1], $characterListArray[2], $characterListArray[3]);
                $max = 6;
            }
            if ($complexity == 2) {
                unset($characterListArray[2], $characterListArray[3]);
                $max = 10;
            }
            if ($complexity == 3) {
                unset($characterListArray[3]);
                $max = 10;
            }
            if ($setMax > 0) {
                $max = $setMax;
            }
            $chars    = array();
            $numchars = array();
            //$equalityPart = ceil( $max / count( $characterListArray ) );
            for ($i = 0; $i < $max; $i++) {
                $charListId = rand(0, count($characterListArray) - 1);
                if ( ! isset($numchars[$charListId])) {
                    $numchars[$charListId] = 0;
                }
                $numchars[$charListId]++;
                $chars[] = $characterListArray[$charListId]{mt_rand(0, (strlen($characterListArray[$charListId]) - 1))};
            }
            shuffle($chars);
            $returnString = implode("", $chars);
            if ($webFriendly) {
                // The lazyness
                $returnString = preg_replace("/[+\/=IG0ODQR]/i", "", $returnString);
            }

            return $returnString;
        }

        /**
         * Returns a string with a chosen character list
         *
         * @param string $type
         *
         * @return mixed
         * @since 6.0.4
         */
        private function getCharacterListArray($type = 'upper')
        {
            $compiledArray = array(
                'upper'    => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                'lower'    => 'abcdefghijklmnopqrstuvwxyz',
                'numeric'  => '0123456789',
                'specials' => '!@#$%*?',
                'table'    => ''
            );
            for ($i = 0; $i <= 255; $i++) {
                $compiledArray['table'] .= chr($i);
            }

            switch ($type) {
                case 'table':
                    return $compiledArray['table'];
                case 'specials':
                    return $compiledArray['specials'];
                case 'numeric':
                    return $compiledArray['numeric'];
                case "lower":
                    return $compiledArray['lower'];
                default:
                    return $compiledArray['upper'];
            }
        }

        /**
         * Returns a selected character list array string as a new array
         *
         * @param string $type
         *
         * @return array|false|string[]
         * @since 6.0.4
         */
        private function getCharactersFromList($type = 'upper')
        {
            return preg_split("//", $this->getCharacterListArray($type), -1, PREG_SPLIT_NO_EMPTY);
        }

        /**
         * Returns a random character from a selected character list
         *
         * @param array $type
         * @param bool  $ambigous
         *
         * @return mixed|string
         * @since 6.0.4
         */
        private function getRandomCharacterFromArray($type = array('upper'), $ambigous = false)
        {
            if (is_string($type)) {
                $type = array($type);
            }
            $getType         = $type[rand(0, count($type) - 1)];
            $characterArray  = $this->getCharactersFromList($getType);
            $characterLength = count($characterArray) - 1;
            $chosenCharacter = $characterArray[rand(0, $characterLength)];
            $ambigousList    = array(
                '+',
                '/',
                '=',
                'I',
                'G',
                '0',
                'O',
                'D',
                'Q',
                'R'
            );
            if (in_array($chosenCharacter, $ambigousList)) {
                $chosenCharacter = $this->getRandomCharacterFromArray($type, $ambigous);
            }

            return $chosenCharacter;
        }

        /**
         * Returns a random character based on complexity selection
         *
         * @param int  $complexity
         * @param bool $ambigous
         *
         * @return mixed|string
         * @since 6.0.4
         */
        private function getCharacterFromComplexity($complexity = 4, $ambigous = false)
        {
            switch ($complexity) {
                case 1:
                    return $this->getRandomCharacterFromArray(array('upper'), $ambigous);
                case 2:
                    return $this->getRandomCharacterFromArray(array('upper', 'lower'), $ambigous);
                case 3:
                    return $this->getRandomCharacterFromArray(array('upper', 'lower', 'numeric'), $ambigous);
                case 4:
                    return $this->getRandomCharacterFromArray(array(
                        'upper',
                        'lower',
                        'numeric',
                        'specials'
                    ), $ambigous);
                case 5:
                    return $this->getRandomCharacterFromArray(array('table'));
                case 6:
                    return $this->getRandomCharacterFromArray(array('table'));
                default:
                    return $this->getRandomCharacterFromArray('upper', $ambigous);
            }
        }

        /**
         * Refactored generator to create a random password or string
         *
         * @param int  $complexity  1=UPPERCASE, 2=UPPERCASE+lowercase, 3=UPPERCASE+lowercase+numerics, 4=UPPERCASE,lowercase+numerics+specialcharacters, 5/6=Full character set
         * @param int  $totalLength Length of the string
         * @param bool $ambigous    Exclude what we see as ambigous characters (this has no effect in complexity > 4)
         *
         * @return string
         * @since 6.0.4
         */
        public function mkpass($complexity = 4, $totalLength = 16, $ambigous = false)
        {
            $pwString = "";
            for ($charIndex = 0; $charIndex < $totalLength; $charIndex++) {
                $pwString .= $this->getCharacterFromComplexity($complexity, $ambigous);
            }

            return $pwString;
        }

        /**
         * @param int  $complexity
         * @param int  $totalLength
         * @param bool $ambigous
         *
         * @return string
         * @since 6.0.7
         */
        public static function getRandomSalt($complexity = 4, $totalLength = 16, $ambigous = false)
        {
            $selfInstance = new TorneLIB_Crypto();

            return $selfInstance->mkpass($complexity, $totalLength, $ambigous);
        }

        /**
         * Set up key for aes encryption.
         *
         * @param      $useKey
         * @param bool $noHash
         *
         * @since 6.0.0
         */
        public function setAesKey($useKey, $noHash = false)
        {
            if ( ! $noHash) {
                $this->ENCRYPT_AES_KEY = md5($useKey);
            } else {
                $this->ENCRYPT_AES_KEY = $useKey;
            }
        }

        /**
         * Set up ip for aes encryption
         *
         * @param      $useIv
         * @param bool $noHash
         *
         * @since 6.0.0
         */
        public function setAesIv($useIv, $noHash = false)
        {
            if ( ! $noHash) {
                $this->ENCRYPT_AES_IV = md5($useIv);
            } else {
                $this->ENCRYPT_AES_IV = $useIv;
            }
        }

        /**
         * @return string
         * @since 6.0.15
         */
        public function getAesKey()
        {
            return $this->ENCRYPT_AES_KEY;
        }

        /**
         * @param bool $adjustLength
         *
         * @return string
         * @since 6.0.15
         */
        public function getAesIv($adjustLength = true)
        {
            if ($adjustLength) {
                if ((int)$this->OPENSSL_IV_LENGTH >= 0) {
                    if (strlen($this->ENCRYPT_AES_IV) > $this->OPENSSL_IV_LENGTH) {
                        $this->ENCRYPT_AES_IV = substr($this->ENCRYPT_AES_IV, 0, $this->OPENSSL_IV_LENGTH);
                    }
                }
            }

            return $this->ENCRYPT_AES_IV;
        }

        /**
         * @param bool $throwOnProblems
         *
         * @return bool
         * @throws \Exception
         * @since 6.0.15
         */
        private function getOpenSslEncrypt($throwOnProblems = true)
        {
            if (function_exists('openssl_encrypt')) {
                return true;
            }
            if ($throwOnProblems) {
                throw new \Exception("openssl_encrypt does not exist in this system. Do you have it installed?");
            }

            return false;
        }

        /**
         * Encrypt content to RIJNDAEL/AES-encryption (Deprecated from PHP 7.1, removed in PHP 7.2)
         *
         * @param string $decryptedContent
         * @param bool   $asBase64
         * @param bool   $forceUtf8
         *
         * @return string
         * @throws \Exception
         * @since 6.0.0
         */
        public function aesEncrypt($decryptedContent = "", $asBase64 = true, $forceUtf8 = true)
        {

            if ( ! $this->USE_MCRYPT) {
                $this->setSslCipher('AES-256-CBC');

                return $this->getEncryptSsl($decryptedContent, $asBase64, $forceUtf8);
            }

            $contentData = $decryptedContent;
            if ( ! function_exists('mcrypt_encrypt')) {
                throw new \Exception("mcrypt does not exist in this system - it has been deprecated since PHP 7.1");
            }
            if ($this->ENCRYPT_AES_KEY == md5(md5("TorneLIB Default IV - Please Change this")) || $this->ENCRYPT_AES_IV == md5(md5("TorneLIB Default IV - Please Change this"))) {
                // TODO: TORNELIB_EXCEPTIONS::TORNELIB_CRYPTO_KEY_EXCEPTION
                throw new \Exception("Current encryption key and iv is not allowed to use.");
            }
            if (is_string($decryptedContent) && $forceUtf8) {
                $contentData = utf8_encode($decryptedContent);
            }
            /** @noinspection PhpDeprecationInspection */
            $binEnc      = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->ENCRYPT_AES_KEY, $contentData, MCRYPT_MODE_CBC,
                $this->ENCRYPT_AES_IV);
            $baseEncoded = $this->base64url_encode($binEnc);
            if ($asBase64) {
                return $baseEncoded;
            } else {
                return $binEnc;
            }
        }

        /**
         * @param $cipherConstant
         *
         * @return mixed
         * @throws \Exception
         * @since 6.0.15
         */
        private function setUseCipher($cipherConstant)
        {
            $this->getOpenSslEncrypt();
            if (in_array($cipherConstant, openssl_get_cipher_methods())) {
                $this->OPENSSL_CIPHER_METHOD = $cipherConstant;
                $this->OPENSSL_IV_LENGTH     = $this->getIvLength($cipherConstant);

                return $cipherConstant;
            }
            throw new \Exception("Cipher does not exists in this openssl module");
        }

        /** @noinspection PhpUnusedPrivateMethodInspection */
        /**
         * @return mixed
         * @since 6.0.15
         */
        private function getUseCipher()
        {
            return $this->OPENSSL_CIPHER_METHOD;
        }

        /**
         * @param $cipherConstant
         *
         * @return int
         * @throws \Exception
         * @since 6.0.15
         */
        private function getIvLength($cipherConstant)
        {
            $this->getOpenSslEncrypt();
            if ( ! empty($cipherConstant)) {
                return openssl_cipher_iv_length($cipherConstant);
            }

            return openssl_cipher_iv_length($this->OPENSSL_CIPHER_METHOD);
        }

        /**
         * Get the cipher method name by comparing them (for testing only)
         *
         * @param string $encryptedString
         * @param string $decryptedString
         *
         * @return null
         * @throws \Exception
         * @since 6.0.15
         */
        public function getCipherTypeByString($encryptedString = "", $decryptedString = "")
        {
            $this->getOpenSslEncrypt();
            $cipherMethods  = openssl_get_cipher_methods();
            $skippedMethods = array();
            $originalKey    = $this->ENCRYPT_AES_KEY;
            $originalIv     = $this->ENCRYPT_AES_IV;
            foreach ($cipherMethods as $method) {
                if ( ! in_array($method, $skippedMethods)) {
                    //$skippedMethods[] = strtoupper($method);
                    try {
                        $this->ENCRYPT_AES_KEY = $originalKey;
                        $this->ENCRYPT_AES_IV  = $originalIv;
                        $this->setSslCipher($method);
                        $result = $this->getEncryptSsl($decryptedString);
                        if ( ! empty($result) && $result == $encryptedString) {
                            return $method;
                        }
                    } catch (\Exception $e) {
                    }
                }
            }

            return null;
        }

        /**
         * @param string $decryptedContent
         * @param bool   $asBase64
         * @param bool   $forceUtf8
         *
         * @return string
         * @throws \Exception
         */
        public function getEncryptSsl($decryptedContent = "", $asBase64 = true, $forceUtf8 = true)
        {
            if ($this->ENCRYPT_AES_KEY == md5(md5("TorneLIB Default IV - Please Change this")) || $this->ENCRYPT_AES_IV == md5(md5("TorneLIB Default IV - Please Change this"))) {
                throw new \Exception("Current encryption key and iv is not allowed to use.");
            }

            if ($forceUtf8 && is_string($decryptedContent)) {
                $contentData = utf8_encode($decryptedContent);
            } else {
                $contentData = $decryptedContent;
            }

            if (empty($this->OPENSSL_CIPHER_METHOD)) {
                $this->setSslCipher();
            } else {
                $this->setUseCipher($this->OPENSSL_CIPHER_METHOD);
            }

            // TODO: openssl_random_pseudo_bytes
            $binEnc = openssl_encrypt($contentData, $this->OPENSSL_CIPHER_METHOD, $this->getAesKey(), OPENSSL_RAW_DATA,
                $this->getAesIv(true));

            $baseEncoded = $this->base64url_encode($binEnc);
            if ($asBase64) {
                return $baseEncoded;
            } else {
                return $binEnc;
            }

        }

        /**
         * @param      $encryptedContent
         * @param bool $asBase64
         *
         * @return string
         * @throws \Exception
         * @since 6.0.15
         */
        public function getDecryptSsl($encryptedContent, $asBase64 = true)
        {
            $contentData = $encryptedContent;
            if ($asBase64) {
                $contentData = $this->base64url_decode($encryptedContent);
            }
            if (empty($this->OPENSSL_CIPHER_METHOD)) {
                $this->setSslCipher();
            } else {
                $this->setUseCipher($this->OPENSSL_CIPHER_METHOD);
            }

            // TODO: openssl_random_pseudo_bytes
            return openssl_decrypt($contentData, $this->OPENSSL_CIPHER_METHOD, $this->getAesKey(), OPENSSL_RAW_DATA,
                $this->getAesIv(true));
        }

        /**
         * @param string $cipherConstant
         *
         * @throws \Exception
         * @since 6.0.15
         */
        public function setSslCipher($cipherConstant = 'AES-256-CBC')
        {
            $this->setUseCipher($cipherConstant);
        }

        /**
         * Decrypt content encoded with RIJNDAEL/AES-encryption
         *
         * @param string $encryptedContent
         * @param bool   $asBase64
         *
         * @return string
         * @throws \Exception
         * @since 6.0.0
         */
        public function aesDecrypt($encryptedContent = "", $asBase64 = true)
        {

            if ( ! $this->USE_MCRYPT) {
                return $this->getDecryptSsl($encryptedContent, $asBase64);
            }

            $useKey = $this->ENCRYPT_AES_KEY;
            $useIv  = $this->ENCRYPT_AES_IV;
            if ($useKey == md5(md5("TorneLIB Default IV - Please Change this")) || $useIv == md5(md5("TorneLIB Default IV - Please Change this"))) {
                // TODO: TORNELIB_EXCEPTIONS::TORNELIB_CRYPTO_KEY_EXCEPTION
                throw new \Exception("Current encryption key and iv is not allowed to use.");
            }
            $contentData = $encryptedContent;
            if ($asBase64) {
                $contentData = $this->base64url_decode($encryptedContent);
            }
            /** @noinspection PhpDeprecationInspection */
            $decryptedOutput = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $useKey, $contentData, MCRYPT_MODE_CBC,
                $useIv));

            return $decryptedOutput;
        }

        /**
         * Compress data with gzencode and encode to base64url
         *
         * @param string $data
         * @param int    $compressionLevel
         *
         * @return string
         * @throws \Exception
         * @since 6.0.0
         */
        public function base64_gzencode($data = '', $compressionLevel = -1)
        {

            if ( ! empty($this->COMPRESSION_LEVEL)) {
                $compressionLevel = $this->COMPRESSION_LEVEL;
            }

            if ( ! function_exists('gzencode')) {
                throw new \Exception("Function gzencode is missing");
            }
            $gzEncoded = gzencode($data, $compressionLevel);

            return $this->base64url_encode($gzEncoded);
        }

        /**
         * Decompress gzdata that has been encoded with base64url
         *
         * @param string $data
         *
         * @return string
         * @throws \Exception
         * @since 6.0.0
         */
        public function base64_gzdecode($data = '')
        {
            $gzDecoded = $this->base64url_decode($data);

            return $this->gzDecode($gzDecoded);
        }

        /**
         * Compress data with bzcompress and base64url-encode it
         *
         * @param string $data
         *
         * @return string
         * @throws \Exception
         * @since 6.0.0
         */
        public function base64_bzencode($data = '')
        {
            if ( ! function_exists('bzcompress')) {
                throw new \Exception("bzcompress is missing");
            }
            $bzEncoded = bzcompress($data);

            return $this->base64url_encode($bzEncoded);
        }

        /**
         * Decompress bzdata that has been encoded with base64url
         *
         * @param $data
         *
         * @return mixed
         * @throws \Exception
         * @since 6.0.0
         */
        public function base64_bzdecode($data)
        {
            if ( ! function_exists('bzdecompress')) {
                throw new \Exception("bzdecompress is missing");
            }
            $bzDecoded = $this->base64url_decode($data);

            return bzdecompress($bzDecoded);
        }

        /**
         * Compress and encode data with best encryption
         *
         * @param string $data
         *
         * @return mixed
         * @throws \Exception
         * @since 6.0.0
         */

        public function base64_compress($data = '')
        {
            $results         = array();
            $bestCompression = null;
            $lengthArray     = array();
            if (function_exists('gzencode')) {
                $results['gz0'] = $this->base64_gzencode("gz0:" . $data, 0);
                $results['gz9'] = $this->base64_gzencode("gz9:" . $data, 9);
            }
            if (function_exists('bzcompress')) {
                $results['bz'] = $this->base64_bzencode("bz:" . $data);
            }
            foreach ($results as $type => $compressedString) {
                $lengthArray[$type] = strlen($compressedString);
            }
            asort($lengthArray);
            foreach ($lengthArray as $compressionType => $compressionLength) {
                $bestCompression = $compressionType;
                break;
            }

            return $results[$bestCompression];
        }

        /**
         * Decompress data that has been compressed with base64_compress
         *
         * @param string $data
         * @param bool   $getCompressionType
         *
         * @return string
         * @throws \Exception
         * @since 6.0.0
         */
        public function base64_decompress($data = '', $getCompressionType = false)
        {
            $results       = array();
            $results['gz'] = $this->base64_gzdecode($data);
            if (function_exists('bzdecompress')) {
                $results['bz'] = $this->base64_bzdecode($data);
            }
            $acceptedString = "";
            foreach ($results as $result) {
                $resultExploded = explode(":", $result, 2);
                if (isset($resultExploded[0]) && isset($resultExploded[1])) {
                    if ($resultExploded[0] == "gz0" || $resultExploded[0] == "gz9") {
                        $acceptedString = $resultExploded[1];
                        if ($getCompressionType) {
                            $acceptedString = $resultExploded[0];
                        }
                        break;
                    }
                    if ($resultExploded[0] == "bz") {
                        $acceptedString = $resultExploded[1];
                        if ($getCompressionType) {
                            $acceptedString = $resultExploded[0];
                        }
                        break;
                    }
                }
            }

            return $acceptedString;
        }

        /**
         * Decode gzcompressed data. If gzdecode is actually missing (which has happened in early version of PHP), there will be a fallback to gzinflate instead
         *
         * @param $data
         *
         * @return string
         * @throws \Exception
         * @since 6.0.0
         */
        private function gzDecode($data)
        {
            if (function_exists('gzdecode')) {
                return gzdecode($data);
            }
            if ( ! function_exists('gzinflate')) {
                throw new \Exception("Function gzinflate and gzdecode is missing");
            }
            // Inhherited from TorneEngine-Deprecated
            $flags     = ord(substr($data, 3, 1));
            $headerlen = 10;
            //$extralen    = 0;
            //$filenamelen = 0;
            if ($flags & 4) {
                $extralen  = unpack('v', substr($data, 10, 2));
                $extralen  = $extralen[1];
                $headerlen += 2 + $extralen;
            }
            if ($flags & 8) // Filename
            {
                $headerlen = strpos($data, chr(0), $headerlen) + 1;
            }
            if ($flags & 16) // Comment
            {
                $headerlen = strpos($data, chr(0), $headerlen) + 1;
            }
            if ($flags & 2) // CRC at end of file
            {
                $headerlen += 2;
            }
            $unpacked = gzinflate(substr($data, $headerlen));
            if ($unpacked === false) {
                $unpacked = $data;
            }

            return $unpacked;
        }

        /**
         * URL compatible base64_encode
         *
         * @param $data
         *
         * @return string
         * @since 6.0.0
         */
        public function base64url_encode($data)
        {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        }

        /**
         * URL compatible base64_decode
         *
         * @param $data
         *
         * @return string
         * @since 6.0.0
         */
        public function base64url_decode($data)
        {
            return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
        }
    }
}

if ( ! class_exists('TORNELIB_CRYPTO_TYPES', CRYPTO_CLASS_EXISTS_AUTOLOAD) && ! class_exists('TorneLIB\TORNELIB_CRYPTO_TYPES', CRYPTO_CLASS_EXISTS_AUTOLOAD)) {
    abstract class TORNELIB_CRYPTO_TYPES
    {
        const TYPE_NONE = 0;
        const TYPE_GZ = 1;
        const TYPE_BZ2 = 2;
    }
}

if ( ! class_exists('TorneLIB_Crypto', CRYPTO_CLASS_EXISTS_AUTOLOAD) && ! class_exists('TorneLIB\TorneLIB_Crypto', CRYPTO_CLASS_EXISTS_AUTOLOAD)) {
    class TorneLIB_Crypto extends MODULE_CRYPTO
    {
    }
}
if ( ! defined('TORNELIB_IO_RELEASE')) {
    define('TORNELIB_IO_RELEASE', '6.0.14');
}
if ( ! defined('TORNELIB_IO_MODIFY')) {
    define('TORNELIB_IO_MODIFY', '20180822');
}
if ( ! defined('TORNELIB_IO_CLIENTNAME')) {
    define('TORNELIB_IO_CLIENTNAME', 'MODULE_IO');
}
if (!defined('IO_SKIP_AUTOLOAD')) {
    define('IO_CLASS_EXISTS_AUTOLOAD', true);
} else {
    define('IO_CLASS_EXISTS_AUTOLOAD', false);
}
if (defined('TORNELIB_IO_REQUIRE')) {
    if ( ! defined('TORNELIB_IO_REQUIRE_OPERATOR')) {
        define('TORNELIB_IO_REQUIRE_OPERATOR', '==');
    }
    define('TORNELIB_IO_ALLOW_AUTOLOAD',
        version_compare(TORNELIB_IO_RELEASE, TORNELIB_IO_REQUIRE, TORNELIB_IO_REQUIRE_OPERATOR) ? true : false);
} else {
    if ( ! defined('TORNELIB_IO_ALLOW_AUTOLOAD')) {
        define('TORNELIB_IO_ALLOW_AUTOLOAD', true);
    }
}

if ( ! class_exists('MODULE_IO', IO_CLASS_EXISTS_AUTOLOAD) && ! class_exists('TorneLIB\MODULE_IO', IO_CLASS_EXISTS_AUTOLOAD) && defined('TORNELIB_IO_ALLOW_AUTOLOAD') && TORNELIB_IO_ALLOW_AUTOLOAD === true) {

    /**
     * Class MODULE_IO
     *
     * @package TorneLIB
     */
    class MODULE_IO
    {

        /** @var TorneLIB_Crypto $CRYPTO */
        private $CRYPTO;
        /** @var bool Enforce usage SimpleXML objects even if XML_Serializer is present */
        private $ENFORCE_SIMPLEXML = false;

        /** @var bool $ENFORCE_SERIALIZER */
        private $ENFORCE_SERIALIZER = false;

        /** @var bool $ENFORCE_CDATA */
        private $ENFORCE_CDATA = false;

        /** @var bool $SOAP_ATTRIBUTES_ENABLED */
        private $SOAP_ATTRIBUTES_ENABLED = false;

        /** @var int $XML_TRANSLATE_ENTITY_RERUN */
        private $XML_TRANSLATE_ENTITY_RERUN = 0;

        public function __construct()
        {
        }

        function setCrypto()
        {
            if (empty($this->CRYPTO)) {
                $this->CRYPTO = new TorneLIB_Crypto();
            }
        }

        /**
         * Set and override compression level
         *
         * @param int $compressionLevel
         *
         * @since 6.0.3
         */
        function setCompressionLevel($compressionLevel = 9)
        {
            $this->setCrypto();
            $this->CRYPTO->setCompressionLevel($compressionLevel);
        }

        /**
         * Get current compressionlevel
         *
         * @return mixed
         * @since 6.0.3
         */
        public function getCompressionLevel()
        {
            $this->setCrypto();

            return $this->CRYPTO->getCompressionLevel();
        }

        /**
         * Force the use of SimpleXML before XML/Serializer
         *
         * @param bool $enforceSimpleXml
         *
         * @since 6.0.3
         */
        public function setXmlSimple($enforceSimpleXml = true)
        {
            $this->ENFORCE_SIMPLEXML = $enforceSimpleXml;
        }

        /**
         * @return bool
         *
         * @since 6.0.3
         */
        public function getXmlSimple()
        {
            return $this->ENFORCE_SIMPLEXML;
        }

        /**
         * Enforce use of XML/Unserializer before SimpleXML-decoding
         *
         * @param bool $activationBoolean
         *
         * @since 6.0.5
         */
        public function setXmlUnSerializer($activationBoolean = true)
        {
            $this->ENFORCE_SERIALIZER = $activationBoolean;
        }

        /**
         * Figure out if user has enabled overriding default XML parser (SimpleXML => XML/Unserializer)
         *
         * @return bool
         * @since 6.0.5
         */
        public function getXmlUnSerializer()
        {
            return $this->ENFORCE_SERIALIZER;
        }

        /**
         * Enable the use of CDATA-fields in XML data
         *
         * @param bool $activationBoolean
         *
         * @since 6.0.5
         */
        public function setCdataEnabled($activationBoolean = true)
        {
            $this->ENFORCE_CDATA = $activationBoolean;
        }

        /**
         * Figure out if user has enabled the use of CDATA in XML data
         *
         * @return bool
         * @since 6.0.5
         */
        public function getCdataEnabled()
        {
            return $this->ENFORCE_CDATA;
        }

        /**
         * Figure out whether we can use XML/Unserializer as XML parser or not
         *
         * @return bool
         * @since 6.0.5
         */
        public function getHasXmlSerializer()
        {
            $serializerPath = stream_resolve_include_path('XML/Unserializer.php');
            if ( ! empty($serializerPath)) {
                return true;
            }

            return false;
        }

        /**
         * @param bool $soapAttributes
         *
         * @since 6.0.6
         */
        public function setSoapXml($soapAttributes = true)
        {
            $this->SOAP_ATTRIBUTES_ENABLED = $soapAttributes;
            $this->setXmlSimple(true);
        }

        /**
         * @return bool
         * @since 6.0.6
         */
        public function getSoapXml()
        {
            return $this->SOAP_ATTRIBUTES_ENABLED;
        }

        /**
         * Convert object to a data object (used for repairing __PHP_Incomplete_Class objects)
         *
         * This function are written to work with WSDL2PHPGenerator, where serialization of some objects sometimes generates, as described, __PHP_Incomplete_Class objects.
         * The upgraded version are also supposed to work with protected values.
         *
         * @param array $objectArray
         * @param bool  $useJsonFunction
         *
         * @return object
         * @since 6.0.0
         */
        public function arrayObjectToStdClass($objectArray = array(), $useJsonFunction = false)
        {
            /**
             * If json_decode and json_encode exists as function, do it the simple way.
             * http://php.net/manual/en/function.json-encode.php
             */
            if ((function_exists('json_decode') && function_exists('json_encode')) || $useJsonFunction) {
                return json_decode(json_encode($objectArray));
            }
            $newArray = array();
            if (is_array($objectArray) || is_object($objectArray)) {
                foreach ($objectArray as $itemKey => $itemValue) {
                    if (is_array($itemValue)) {
                        $newArray[$itemKey] = (array)$this->arrayObjectToStdClass($itemValue);
                    } elseif (is_object($itemValue)) {
                        $newArray[$itemKey] = (object)(array)$this->arrayObjectToStdClass($itemValue);
                    } else {
                        $newArray[$itemKey] = $itemValue;
                    }
                }
            }

            return $newArray;
        }

        /**
         * Convert objects to arrays
         *
         * @param       $arrObjData
         * @param array $arrSkipIndices
         *
         * @return array
         * @since 6.0.0
         */
        public function objectsIntoArray($arrObjData, $arrSkipIndices = array())
        {
            $arrData = array();
            // if input is object, convert into array
            if (is_object($arrObjData)) {
                $arrObjData = get_object_vars($arrObjData);
            }
            if (is_array($arrObjData)) {
                foreach ($arrObjData as $index => $value) {
                    if (is_object($value) || is_array($value)) {
                        $value = $this->objectsIntoArray($value, $arrSkipIndices); // recursive call
                    }
                    if (@in_array($index, $arrSkipIndices)) {
                        continue;
                    }
                    $arrData[$index] = $value;
                }
            }

            return $arrData;
        }

        /**
         * @param array            $dataArray
         * @param SimpleXMLElement $xml
         *
         * @return mixed
         * @since 6.0.3
         */
        private function array_to_xml($dataArray = array(), $xml)
        {
            foreach ($dataArray as $key => $value) {
                $key = is_numeric($key) ? 'item' : $key;
                if (is_array($value)) {
                    $this->array_to_xml($value, $xml->addChild($key));
                } else {
                    $xml->addChild($key, $value);
                }
            }

            return $xml;
        }

        /**
         * Convert all data to utf8
         *
         * @param array $dataArray
         *
         * @return array
         * @since 6.0.0
         */
        private function getUtf8($dataArray = array())
        {
            $newArray = array();
            if (is_array($dataArray)) {
                foreach ($dataArray as $p => $v) {
                    if (is_array($v) || is_object($v)) {
                        $v            = $this->getUtf8($v);
                        $newArray[$p] = $v;
                    } else {
                        $v            = utf8_encode($v);
                        $newArray[$p] = $v;
                    }

                }
            }

            return $newArray;
        }

        /**
         * @param array $arrayData
         *
         * @return bool
         * @since 6.0.2
         */
        function isAssoc(array $arrayData)
        {
            if (array() === $arrayData) {
                return false;
            }

            return array_keys($arrayData) !== range(0, count($arrayData) - 1);
        }

        /**
         * @param string $contentString
         * @param int    $compression
         * @param bool   $renderAndDie
         *
         * @return string
         * @throws \Exception
         * @since 6.0.3
         */
        private function compressString(
            $contentString = '',
            $compression = TORNELIB_CRYPTO_TYPES::TYPE_NONE,
            $renderAndDie = false
        ) {
            if ($compression == TORNELIB_CRYPTO_TYPES::TYPE_GZ) {
                $this->setCrypto();
                $contentString = $this->CRYPTO->base64_gzencode($contentString);
            } elseif ($compression == TORNELIB_CRYPTO_TYPES::TYPE_BZ2) {
                $this->setCrypto();
                $contentString = $this->CRYPTO->base64_bzencode($contentString);
            }

            if ($renderAndDie) {
                if ($compression == TORNELIB_CRYPTO_TYPES::TYPE_GZ) {
                    $contentString = array('gz' => $contentString);
                } elseif ($compression == TORNELIB_CRYPTO_TYPES::TYPE_BZ2) {
                    $contentString = array('bz2' => $contentString);
                }
            }

            return $contentString;
        }

        /**
         * ServerRenderer: Render JSON data
         *
         * @param array $contentData
         * @param bool  $renderAndDie
         * @param int   $compression
         *
         * @return string
         * @throws \Exception
         * @since 6.0.1
         */
        public function renderJson(
            $contentData = array(),
            $renderAndDie = false,
            $compression = TORNELIB_CRYPTO_TYPES::TYPE_NONE
        ) {
            $objectArrayEncoded = $this->getUtf8($this->objectsIntoArray($contentData));

            if (is_string($contentData)) {
                $objectArrayEncoded = $this->objectsIntoArray($this->getFromJson($contentData));
            }

            $contentRendered = $this->compressString(@json_encode($objectArrayEncoded, JSON_PRETTY_PRINT), $compression,
                $renderAndDie);

            if ($renderAndDie) {
                header("Content-type: application/json; charset=utf-8");
                echo $contentRendered;
                die;
            }

            return $contentRendered;
        }

        /**
         * ServerRenderer: PHP serialized
         *
         * @param array $contentData
         * @param bool  $renderAndDie
         * @param int   $compression
         *
         * @return string
         * @throws \Exception
         * @since 6.0.1
         */
        public function renderPhpSerialize(
            $contentData = array(),
            $renderAndDie = false,
            $compression = TORNELIB_CRYPTO_TYPES::TYPE_NONE
        ) {
            $contentRendered = $this->compressString(serialize($contentData), $compression, $renderAndDie);

            if ($renderAndDie) {
                header("Content-Type: text/plain");
                echo $contentRendered;
                die;
            }

            return $contentRendered;
        }

        /**
         * @param string $serialInput
         * @param bool   $assoc
         *
         * @return mixed
         * @since 6.0.5
         */
        public function getFromSerializerInternal($serialInput = '', $assoc = false)
        {
            if ( ! $assoc) {
                return @unserialize($serialInput);
            } else {
                return $this->arrayObjectToStdClass(@unserialize($serialInput));
            }
        }

        /**
         * ServerRenderer: Render yaml data
         *
         * Install:
         *  apt-get install libyaml-dev
         *  pecl install yaml
         *
         * @param array $contentData
         * @param bool  $renderAndDie
         * @param int   $compression
         *
         * @return string
         * @throws \Exception
         * @since 6.0.1
         */
        public function renderYaml(
            $contentData = array(),
            $renderAndDie = false,
            $compression = TORNELIB_CRYPTO_TYPES::TYPE_NONE
        ) {
            $objectArrayEncoded = $this->getUtf8($this->objectsIntoArray($contentData));
            if (function_exists('yaml_emit')) {
                $contentRendered = $this->compressString(yaml_emit($objectArrayEncoded), $compression, $renderAndDie);
                if ($renderAndDie) {
                    header("Content-Type: text/plain");
                    echo $contentRendered;
                    die;
                }

                return $contentRendered;
            } else {
                throw new \Exception("yaml_emit not supported - ask your admin to install the driver", 404);
            }
        }

        /**
         * @param array  $contentData
         * @param bool   $renderAndDie
         * @param int    $compression
         * @param string $initialTagName
         * @param string $rootName
         *
         * @return mixed
         * @throws \Exception
         * @since 6.0.1
         */
        public function renderXml(
            $contentData = array(),
            $renderAndDie = false,
            $compression = TORNELIB_CRYPTO_TYPES::TYPE_NONE,
            $initialTagName = 'item',
            $rootName = 'XMLResponse'
        ) {
            $serializerPath = stream_resolve_include_path('XML/Serializer.php');
            if ( ! empty($serializerPath)) {
                /** @noinspection PhpIncludeInspection */
                require_once('XML/Serializer.php');
            }
            $objectArrayEncoded = $this->getUtf8($this->objectsIntoArray($contentData));
            $options            = array(
                'indent'         => '    ',
                'linebreak'      => "\n",
                'encoding'       => 'UTF-8',
                'rootName'       => $rootName,
                'defaultTagName' => $initialTagName
            );
            if (class_exists('XML_Serializer', IO_CLASS_EXISTS_AUTOLOAD) && ! $this->ENFORCE_SIMPLEXML) {
                $xmlSerializer = new \XML_Serializer($options);
                $xmlSerializer->serialize($objectArrayEncoded);
                $contentRendered = $xmlSerializer->getSerializedData();
            } else {
                // <data></data>
                if ($this->SOAP_ATTRIBUTES_ENABLED) {
                    $soapNs = 'http://schemas.xmlsoap.org/soap/envelope/';
                    $xml    = new \SimpleXMLElement('<?xml version="1.0"?>' . '<' . $rootName . '></' . $rootName . '>',
                        0, false, $soapNs, false);
                    $xml->addAttribute($rootName . ':xmlns', $soapNs);
                    $xml->addAttribute($rootName . ':xsi', 'http://www.w3.org/2001/XMLSchema-instance');
                    $xml->addAttribute($rootName . ':xsd', 'http://www.w3.org/2001/XMLSchema');
                } else {
                    $xml = new \SimpleXMLElement('<?xml version="1.0"?>' . '<' . $rootName . '></' . $rootName . '>');
                }
                $this->array_to_xml($objectArrayEncoded, $xml);
                $contentRendered = $xml->asXML();
            }

            $contentRendered = $this->compressString($contentRendered, $compression, $renderAndDie);

            if ($renderAndDie) {
                header("Content-Type: application/xml");
                echo $contentRendered;
                die;
            }

            return $contentRendered;
        }

        /**
         * @param string $dataIn
         *
         * @return mixed|string
         * @since 6.0.5
         */
        public function getFromJson($dataIn = '')
        {
            if (is_string($dataIn)) {
                return @json_decode($dataIn);
            } elseif (is_object($dataIn)) {
                return null;
            } elseif (is_array($dataIn)) {
                return null;
            } else {
                // Fail.
                return null;
            }
        }

        /**
         * Convert XML string into an object or array
         *
         * @param string $dataIn
         * @param bool   $normalize Normalize objects (convert to stdClass)
         *
         * @return \SimpleXMLElement
         * @since 6.0.5
         */
        public function getFromXml($dataIn = '', $normalize = false)
        {
            set_error_handler( function ( $errNo, $errStr ) {
                throw new \Exception($errStr, $errNo);
            }, E_ALL );

            $dataIn = trim($dataIn);

            // Run entity checker only if there seems to be no initial tags located in the input string, as this may cause bad loops
            // for PHP (in older versions this also cause SEGFAULTs)
            if ( ! preg_match("/^\</", $dataIn) && preg_match("/&\b(.*?)+;(.*)/is", $dataIn)) {
                $dataEntity = trim(html_entity_decode($dataIn));
                if (preg_match("/^\</", $dataEntity)) {

                    restore_error_handler();
                    return $this->getFromXml($dataEntity, $normalize);
                }

                if ($this->XML_TRANSLATE_ENTITY_RERUN >= 0) {
                    // Fail on too many loops
                    $this->XML_TRANSLATE_ENTITY_RERUN++;
                    if ($this->XML_TRANSLATE_ENTITY_RERUN >= 2) {
                        return null;
                    }

                    restore_error_handler();
                    return $this->getFromXml($dataEntity, $normalize);
                }

                return null;
            }

            if ($this->getXmlUnSerializer() && $this->getHasXmlSerializer()) {
                if (is_string($dataIn) && preg_match("/\<(.*?)\>/s", $dataIn)) {
                    /** @noinspection PhpIncludeInspection */
                    require_once('XML/Unserializer.php');
                    $xmlSerializer = new \XML_Unserializer();
                    $xmlSerializer->unserialize($dataIn);

                    if ( ! $normalize) {
                        restore_error_handler();
                        return $xmlSerializer->getUnserializedData();
                    } else {
                        restore_error_handler();
                        return $this->arrayObjectToStdClass($xmlSerializer->getUnserializedData());
                    }
                }
            } else {
                if (class_exists('SimpleXMLElement', IO_CLASS_EXISTS_AUTOLOAD)) {
                    if (is_string($dataIn) && preg_match("/\<(.*?)\>/s", $dataIn)) {
                        if ($this->ENFORCE_CDATA) {
                            $simpleXML = new \SimpleXMLElement($dataIn, LIBXML_NOCDATA);
                        } else {
                            $simpleXML = new \SimpleXMLElement($dataIn);
                        }
                        if (isset($simpleXML) && (is_object($simpleXML) || is_array($simpleXML))) {
                            if ( ! $normalize) {
                                restore_error_handler();
                                return $simpleXML;
                            } else {
                                $objectClass = $this->arrayObjectToStdClass($simpleXML);
                                if ( ! count((array)$objectClass)) {
                                    $xmlExtractedPath = $this->extractXmlPath($simpleXML);
                                    if ( ! is_null($xmlExtractedPath)) {
                                        if (is_object($xmlExtractedPath) || (is_array($xmlExtractedPath) && count($xmlExtractedPath))) {
                                            restore_error_handler();
                                            return $xmlExtractedPath;
                                        }
                                    }
                                }

                                restore_error_handler();
                                return $objectClass;
                            }
                        }
                    } else {
                        restore_error_handler();
                    }
                }
            }

            return null;
        }

        /**
         * Check if there is something more than just an empty object hidden behind a SimpleXMLElement
         *
         * @param null $simpleXML
         *
         * @return array|mixed|null
         * @since 6.0.8
         */
        private function extractXmlPath($simpleXML = null)
        {
            $canReturn       = false;
            $xmlXpath        = null;
            $xmlPathReturner = null;
            if (method_exists($simpleXML, 'xpath')) {
                try {
                    $xmlXpath = $simpleXML->xpath("*/*");
                } catch (\Exception $ignoreErrors) {

                }
                if (is_array($xmlXpath)) {
                    if (count($xmlXpath) == 1) {
                        $xmlPathReturner = array_pop($xmlXpath);
                        $canReturn       = true;
                    } elseif (count($xmlXpath) > 1) {
                        $xmlPathReturner = $xmlXpath;
                        $canReturn       = true;
                    }
                    if (isset($xmlPathReturner->return)) {
                        return $this->arrayObjectToStdClass($xmlPathReturner)->return;
                    }
                }
            }
            if ($canReturn) {
                return $xmlPathReturner;
            }

            return null;
        }

        /**
         * @param string $yamlString
         * @param bool   $getAssoc
         *
         * @return array|mixed|object
         * @throws \Exception
         * @since 6.0.5
         */
        public function getFromYaml($yamlString = '', $getAssoc = true)
        {
            if (function_exists('yaml_parse')) {
                $extractYaml = @yaml_parse($yamlString);
                if ($getAssoc) {
                    if (empty($extractYaml)) {
                        return null;
                    }

                    return $extractYaml;
                } else {
                    if (empty($extractYaml)) {
                        return null;
                    }

                    return $this->arrayObjectToStdClass($extractYaml);
                }
            } else {
                throw new \Exception("yaml_parse not supported - ask your admin to install the driver", 404);
            }
        }

    }
}

if ( ! class_exists('TorneLIB_IO', IO_CLASS_EXISTS_AUTOLOAD) && ! class_exists('TorneLIB\TorneLIB_IO', IO_CLASS_EXISTS_AUTOLOAD)) {
    class TorneLIB_IO extends MODULE_IO
    {
    }
}
