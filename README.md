# EComPHP - PHP Gateway for Resurs Bank ECommerce Services #

Resurs EComPHP Gateway is a simplifier library for Resurs Bank services API, with functionality for getting started fast. It communicates with all of the available flows at Resurs Bank, except the deprecated older ShopFlow. The library was primary build for the booking payment abilities, but has also come to support Hosted Flow, Resurs Checkout and the other flows for handling callbacks, aftershop (DEBIT/CREDIT/ANULL) and so on.

Current version: Not tagged here. Please look at our repo, as the version are continuously increasing.

## What this library do and do not

* If you are used to work with the simplified flow and wish to use Hosted/Checkout, you can stick to the use of the older SimplifiedFlow variables, as this library converts what's missing between the different flows.
* The EComPHP-library honors a kind of developer sloppiness - if there are forgotten, but required standard fields, the library will fill it in for you as you send your payload to it
* Both SOAP and Rest in different kinds - As we started building this library for pure soap services, the delivered stub files can handle most of your cases. However, since Resus Bank also has hosted flow and a checkout, there is a curl library (one single file) than handles this communication
* In a near future, the WSDL stub collection might be deprecated unless you require functions from the simplifed flow
* The WSDL stub maker is deprecated, and in WSDL2PHPGenerator v3.x the behaviour of content is completely changed. We tend to stop using this completely in a near version (unless you still don't want to use it while running simplified in WSDL mode)


[Take a look on our documentation for full references](https://test.resurs.com/docs/x/TYNM)
