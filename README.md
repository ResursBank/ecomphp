# EComPHP - PHP Gateway for Resurs Bank ECommerce Services #

Resurs EComPHP Gateway is a simplifier for our webservices, with functionality enough to getting started fast. It communicates with the Simplified Flow API for booking payments, Configuration Service and the After Shop Service API for finalizing, crediting and annulments etc. This full version of the gateway communicates with Hosted Payment Flow and Resurs Checkout (supporting both REST and SOAP). A PHP-reference for EComPHP is located at https://test.resurs.com/autodocs/ecomphp-apigen/, if you want to take a look at our automatically generated documentation.

As EComPHP is continuously developed, you should take a look at our bitbucket repo to keep this information updated. It can be found at https://bitbucket.org/resursbankplugins/resurs-ecomphp


## Regular requirements and dependencies

* For EComPHP 1.0 (With no namespaces) at least PHP 5.2
* For EComPHP 1.1 (With namespaces) at least PHP 5.3
* OpenSSL: For reaching Resurs webservices that is restricted to https only
* curl: php-curl and php-xml (For the SOAP parts)
* EComPHP will, as of v1.0.1 and v1.1.1, uses [this curl library](https://bitbucket.tornevall.net/projects/LIB/repos/tornelib-php/browse/classes/tornevall_network.php) (bundled) for most of the webcalls that is not provided through the WSDL package.

As this module uses [curl](https://curl.haxx.se) and [SoapClient](http://php.net/manual/en/class.soapclient.php) to work, there are dependencies in curl and xml libraries (as shown above). For Ubuntu, you can quickly fetch those with apt-get (apt-get install php-curl php-xml) if they do not already exists in your system. There might be a slight chance that you also need openssl or similar, as our services runs on https-only (normally openssl are delivered automatically, but sometimes they do not - apt-get install openssl might work in those cases if you have access to the server).

## PHP 7.2

As of 3 september 2017, there PHP 7.2RC1 has been released. Does the library work with PHP 7.2?

- Yes, the tests are confirmed to run with 7.2RC1.


## What this library do and do not

* Implementations with EComPHP no longer requires the full ecom-package (meaning, you do not need to include the WSDL file structure) in your plugins. The WSDL packages is only required if you plan to speak with ecommerce methods in the simplified flow that is not included in EComPHP. 
* If you are used to work with the simplified flow and wish to use Hosted/Checkout, you can stick to the use of the older SimplifiedFlow variables, as this library converts what's missing between the different flows.
* The EComPHP-library honors a kind of developer sloppiness - if something is forgotten in the payload that used to be required standard fields, the library will fills it in (as good as possible) for you, as you send your payload to it
* Both SOAP and REST is supported
* The WSDL stub libraries are deprecated and should not normally be needed for you to work with ecommerce (The WSDL stub maker, that writes the files are also deprecated and probably not recommended to use)
* It is still possible to communicate via the stubs but that is not recommended and our plans is to no longer support them

[Take a look on our documentation for details and getting started](https://test.resurs.com/docs/x/TYNM)
