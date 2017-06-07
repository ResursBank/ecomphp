# Testing EComPHP

***Do you have plans running this suite in production mode? PLEASE, DO NOT!***

The EComPHP testing suite is a bunch of function that is being runned every time Resurs Bank are releasing the library patches. In this way, we can be assured that the most common function in the library also works properly. To assure that things also works properly for our merchant developers (if they are utilizing with EComPHP), we also do release the test suite for external testings. As soon as we can we will completely leave 1.0 as the way it has been developed is obsolete.
 
**Do EComPHP have an EOL?**

Yes, it has. But it's not defined yet.

## Configuration and credentials

To set up the test suite properly, you should take a look at test.json.sample and when you do have credentials to our test environment, edit the file as test.json, and change the values in the file to the data that fits you best.

## Merging 1.0.x with 1.1.0

Currently, to get things in sync, we build from EComPHP 1.0 (that is actually deprecated). When functions works in 1.0 we transfer the same data to 1.1 and run tests.

###Classes in v1.0 must be updated to keep compatibility with 1.1

The list below covers the classes that must be translated, when merging into v1.1 - In a close future, it will also cover the classes that must be translated from v1.1 to v1.0, as long as v1.0 do have maintenance support.

ResursCallbackTypes::    \Resursbank\RBEcomPHP\ResursCallbackTypes::
ResursMethodTypes::       \Resursbank\RBEcomPHP\ResursMethodTypes::
