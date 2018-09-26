<?php

namespace Resursbank\RBEcomPHP;

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



    /////////////////////////// EXTREMELY OUTDATED CODE BEGIN (THAT MIGHT NOT EVEN WORK ANYMORE SINCE WERE OUT OF WSDL)

    /**
     * Prepare a payment by setting it up
     *
     * customerIpAddress has a failover: If we don't receive a proper customer ip, we will try to check if there is a REMOTE_ADDR set by the server. If neither of those values are set, we will finally fail over to 127.0.0.1
     * preferredId is set to a internally generated id instead of null, unless you apply your own (if set to null, Resurs Bank decides what order number to be used)
     *
     * @param $paymentMethodId
     * @param array $paymentDataArray
     *
     * @throws \Exception
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    public function updatePaymentdata($paymentMethodId, $paymentDataArray = array())
    {
        $this->InitializeServices();
        if (empty($this->preferredId)) {
            $this->preferredId = $this->generatePreferredId();
        }
        if (!is_object($this->_paymentData) && (class_exists('Resursbank\RBEcomPHP\resurs_paymentData',
                    ECOM_CLASS_EXISTS_AUTOLOAD) || class_exists('resurs_paymentData', ECOM_CLASS_EXISTS_AUTOLOAD))) {
            $this->_paymentData = new resurs_paymentData($paymentMethodId);
        } else {
            // If there are no wsdl-classes loaded, we should consider a default stdClass as object
            $this->_paymentData = new \stdClass();
        }
        $this->_paymentData->preferredId = isset($paymentDataArray['preferredId']) && !empty($paymentDataArray['preferredId']) ? $paymentDataArray['preferredId'] : $this->preferredId;
        $this->_paymentData->paymentMethodId = $paymentMethodId;
        $this->_paymentData->customerIpAddress = (isset($paymentDataArray['customerIpAddress']) && !empty($paymentDataArray['customerIpAddress']) ? $paymentDataArray['customerIpAddress'] : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1'));
        $this->_paymentData->waitForFraudControl = isset($paymentDataArray['waitForFraudControl']) && !empty($paymentDataArray['waitForFraudControl']) ? $paymentDataArray['waitForFraudControl'] : false;
        $this->_paymentData->annulIfFrozen = isset($paymentDataArray['annulIfFrozen']) && !empty($paymentDataArray['annulIfFrozen']) ? $paymentDataArray['annulIfFrozen'] : false;
        $this->_paymentData->finalizeIfBooked = isset($paymentDataArray['finalizeIfBooked']) && !empty($paymentDataArray['finalizeIfBooked']) ? $paymentDataArray['finalizeIfBooked'] : false;
    }

    /**
     * Creation of specrows lands here
     *
     * @param array $speclineArray
     *
     * @return null
     * @throws \Exception
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    public function updateCart($speclineArray = array())
    {
        if (!$this->isOmniFlow && !$this->isHostedFlow) {
            if (!class_exists('Resursbank\RBEcomPHP\resurs_specLine',
                    ECOM_CLASS_EXISTS_AUTOLOAD) && !class_exists('resurs_specLine', ECOM_CLASS_EXISTS_AUTOLOAD)) {
                throw new Exception(__FUNCTION__ . ": Class specLine does not exist",
                    \RESURS_EXCEPTIONS::UPDATECART_NOCLASS_EXCEPTION);
            }
        }
        $this->InitializeServices();
        $realSpecArray = array();
        if (isset($speclineArray['artNo'])) {
            // If this require parameter is found first in the array, it's a single specrow.
            // In that case, push it out to be a multiple.
            array_push($realSpecArray, $speclineArray);
        } else {
            $realSpecArray = $speclineArray;
        }
        // Handle the specrows as they were many.
        foreach ($realSpecArray as $specIndex => $speclineArray) {
            $quantity = (isset($speclineArray['quantity']) && !empty($speclineArray['quantity']) ? $speclineArray['quantity'] : 1);
            $unitAmountWithoutVat = (is_numeric(floatval($speclineArray['unitAmountWithoutVat'])) ? $speclineArray['unitAmountWithoutVat'] : 0);
            $vatPct = (isset($speclineArray['vatPct']) && !empty($speclineArray['vatPct']) ? $speclineArray['vatPct'] : 0);
            $totalVatAmountInternal = ($unitAmountWithoutVat * ($vatPct / 100)) * $quantity;
            $totalAmountInclTax = round(($unitAmountWithoutVat * $quantity) + $totalVatAmountInternal,
                $this->bookPaymentRoundDecimals);
            $totalAmountInclTaxInternal = $totalAmountInclTax;

            if (!$this->bookPaymentInternalCalculate) {
                if (isset($speclineArray['totalVatAmount']) && !empty($speclineArray['totalVatAmount'])) {
                    $totalVatAmount = $speclineArray['totalVatAmount'];
                    // Controls the totalVatAmount
                    if ($totalVatAmount != $totalVatAmountInternal) {
                        $totalVatAmount = $totalVatAmountInternal;
                        $this->bookPaymentCartFixed = true;
                    }
                    if ($totalAmountInclTax != $totalAmountInclTaxInternal) {
                        $this->bookPaymentCartFixed = true;
                        $totalAmountInclTax = $totalAmountInclTaxInternal;
                    }
                    $totalAmountInclTax = ($unitAmountWithoutVat * $quantity) + $totalVatAmount;
                } else {
                    $totalVatAmount = $totalVatAmountInternal;
                }
            } else {
                $totalVatAmount = $totalVatAmountInternal;
            }
            $this->_specLineID++;
            /*
             * When the class for resurs_SpecLine is missing (e.g. during omni/hosted), the variables below must be set in a different way.
             * In this function we'll let the array right through without any class definition.
             *
             * id
             * artNo
             * description
             * quantity
             * unitMeasure
             * unitAmountWithoutVat
             * vatPct
             * totalVatAmount
             * totalAmount
             */
            if (class_exists('Resursbank\RBEcomPHP\resurs_specLine',
                    ECOM_CLASS_EXISTS_AUTOLOAD) || class_exists('resurs_specLine', ECOM_CLASS_EXISTS_AUTOLOAD)) {
                $this->_paymentSpeclines[] = new resurs_specLine(
                    $this->_specLineID,
                    $speclineArray['artNo'],
                    $speclineArray['description'],
                    $speclineArray['quantity'],
                    (isset($speclineArray['unitMeasure']) && !empty($speclineArray['unitMeasure']) ? $speclineArray['unitMeasure'] : $this->defaultUnitMeasure),
                    $unitAmountWithoutVat,
                    $vatPct,
                    $totalVatAmount,
                    $totalAmountInclTax
                );
            } else {
                if (is_array($speclineArray)) {
                    $this->_paymentSpeclines[] = $speclineArray;
                }
            }
        }

        return $this->_paymentSpeclines;
    }

    /**
     * Update payment specs and prepeare specrows
     *
     * @param array $specLineArray
     *
     * @throws \Exception
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    public function updatePaymentSpec($specLineArray = array())
    {
        $this->InitializeServices();
        if (class_exists('Resursbank\RBEcomPHP\resurs_paymentSpec',
                ECOM_CLASS_EXISTS_AUTOLOAD) || class_exists('resurs_paymentSpec', ECOM_CLASS_EXISTS_AUTOLOAD)) {
            $totalAmount = 0;
            $totalVatAmount = 0;
            if (is_array($specLineArray) && count($specLineArray)) {
                foreach ($specLineArray as $specRow => $specRowArray) {
                    $totalAmount += (isset($specRowArray->totalAmount) ? $specRowArray->totalAmount : 0);
                    $totalVatAmount += (isset($specRowArray->totalVatAmount) ? $specRowArray->totalVatAmount : 0);
                }
            }
            $this->_paymentOrderData = new resurs_paymentSpec($specLineArray, $totalAmount, 0);
            $this->_paymentOrderData->totalVatAmount = floatval($totalVatAmount);
        }
    }

    /**
     * Prepare customer address data
     *
     * Note: Customer types LEGAL needs to be defined as $custeromArray['type'] = "LEGAL", if the booking is about LEGAL customers, since we need to extend the address data for such customers.
     *
     * @param array $addressArray
     * @param array $customerArray
     *
     * @throws \Exception
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    public function updateAddress($addressArray = array(), $customerArray = array())
    {
        $this->InitializeServices();
        $address = null;
        $resursDeliveryAddress = null;
        $customerGovId = isset($customerArray['governmentId']) && !empty($customerArray['governmentId']) ? $customerArray['governmentId'] : "";
        $customerContactGovId = isset($customerArray['contactGovernmentId']) && !empty($customerArray['contactGovernmentId']) ? $customerArray['contactGovernmentId'] : "";
        $customerType = isset($customerArray['type']) && !empty($customerArray['type']) ? $customerArray['type'] : "NATURAL";
        if (count($addressArray)) {
            if (isset($addressArray['address'])) {
                $address = new resurs_address($addressArray['address']['fullName'],
                    $addressArray['address']['firstName'], $addressArray['address']['lastName'],
                    (isset($addressArray['address']['addressRow1']) ? $addressArray['address']['addressRow1'] : null),
                    (isset($addressArray['address']['addressRow2']) ? $addressArray['address']['addressRow2'] : null),
                    $addressArray['address']['postalArea'], $addressArray['address']['postalCode'],
                    $addressArray['address']['country']);
                if (isset($addressArray['deliveryAddress'])) {
                    $resursDeliveryAddress = new resurs_address($addressArray['deliveryAddress']['fullName'],
                        $addressArray['deliveryAddress']['firstName'], $addressArray['deliveryAddress']['lastName'],
                        (isset($addressArray['deliveryAddress']['addressRow1']) ? $addressArray['deliveryAddress']['addressRow1'] : null),
                        (isset($addressArray['deliveryAddress']['addressRow2']) ? $addressArray['deliveryAddress']['addressRow2'] : null),
                        $addressArray['deliveryAddress']['postalArea'], $addressArray['deliveryAddress']['postalCode'],
                        $addressArray['deliveryAddress']['country']);
                }
            } else {
                $address = new resurs_address($addressArray['fullName'], $addressArray['firstName'],
                    $addressArray['lastName'],
                    (isset($addressArray['addressRow1']) ? $addressArray['addressRow1'] : null),
                    (isset($addressArray['addressRow2']) ? $addressArray['addressRow2'] : null),
                    $addressArray['postalArea'], $addressArray['postalCode'], $addressArray['country']);
            }
        }
        if (count($customerArray)) {
            $customer = new resurs_customer($address, $customerArray['phone'], $customerArray['email'],
                $customerArray['type']);
            $this->_paymentAddress = $address;
            if (!empty($customerGovId)) {
                $customer->governmentId = $customerGovId;
            } else {
                if (!empty($customerContactGovId)) {
                    $customer->governmentId = $customerContactGovId;
                }
            }
            $customer->type = $customerType;
            if (!empty($resursDeliveryAddress) || $customerArray['type'] == "LEGAL" || $this->alwaysUseExtendedCustomer === true) {
                if (isset($resursDeliveryAddress) && is_array($resursDeliveryAddress)) {
                    $this->_paymentDeliveryAddress = $resursDeliveryAddress;
                }
                $extendedCustomer = new resurs_extendedCustomer($resursDeliveryAddress, $customerArray['phone'],
                    $customerArray['email'], $customerArray['type']);
                $this->_paymentExtendedCustomer = $extendedCustomer;
                /* #59042 => #59046 (Additionaldata should be empty) */
                if (empty($this->_paymentExtendedCustomer->additionalData)) {
                    unset($this->_paymentExtendedCustomer->additionalData);
                }
                if ($customerArray['type'] == "LEGAL") {
                    $extendedCustomer->contactGovernmentId = $customerArray['contactGovernmentId'];
                }
                if (!empty($customerArray['cellPhone'])) {
                    $extendedCustomer->cellPhone = $customerArray['cellPhone'];
                }
            }
            $this->_paymentCustomer = $customer;
            if (isset($extendedCustomer)) {
                if (!empty($customerGovId)) {
                    $extendedCustomer->governmentId = $customerGovId;
                } else {
                    if (!empty($customerContactGovId)) {
                        $extendedCustomer->governmentId = $customerContactGovId;
                    }
                }
                $extendedCustomer->phone = $customerArray['phone'];
                $extendedCustomer->type = $customerType;
                $extendedCustomer->address = $address;
                $this->_paymentCustomer = $extendedCustomer;
            }
        }
    }

    /**
     * Internal handler for carddata
     * @throws \Exception
     * @deprecated 1.0.2
     * @deprecated 1.1.2
     */
    private function updateCardData()
    {
        $amount = null;
        $this->_paymentCardData = new resurs_cardData();
        if (!isset($this->cardDataCardNumber)) {
            if ($this->cardDataUseAmount && $this->cardDataOwnAmount) {
                $this->_paymentCardData->amount = $this->cardDataOwnAmount;
            } else {
                $this->_paymentCardData->amount = $this->_paymentOrderData->totalAmount;
            }
        } else {
            if (isset($this->cardDataCardNumber) && !empty($this->cardDataCardNumber)) {
                $this->_paymentCardData->cardNumber = $this->cardDataCardNumber;
            }
        }
        if (!empty($this->cardDataCardNumber) && !empty($this->cardDataUseAmount)) {
            throw new Exception(__FUNCTION__ . ": Card number and amount can not be set at the same time",
                \RESURS_EXCEPTIONS::UPDATECARD_DOUBLE_DATA_EXCEPTION);
        }

        return $this->_paymentCardData;
    }

    /////////////////////////// OUTDATED HOSTED FLOW "THINGS"

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
        $bookData = array(),
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
    private function hostedError($hostedObject = array())
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
    private function hostedErrNo($hostedObject = array())
    {
        if (isset($hostedObject) && isset($hostedObject->exception) && isset($hostedObject->status)) {
            return $hostedObject->status;
        }

        return "";
    }

    /////////////////////////// OUTDATED CHECKOUT "THINGS"



    /////////////////////////// EXTREMELY OUTDATED CODE FINISH


}
