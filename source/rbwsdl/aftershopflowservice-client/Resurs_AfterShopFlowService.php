<?php

namespace Resursbank\RBEcomPHP;

if (!class_exists("\\Resursbank\\RBEcomPHP\\Resurs_AfterShopFlowService", false))
{
include_once('resurs_customer.php');
include_once('resurs_address.php');
include_once('resurs_mapEntry.php');
include_once('resurs_countryCode.php');
include_once('resurs_language.php');
include_once('resurs_paymentSpec.php');
include_once('resurs_specLine.php');
include_once('resurs_paymentStatus.php');
include_once('resurs_limit.php');
include_once('resurs_limitDecision.php');
include_once('resurs_customerType.php');
include_once('resurs_paymentMethodType.php');
include_once('resurs_invoiceDeliveryTypeEnum.php');
include_once('resurs_payment.php');
include_once('resurs_paymentDiff.php');
include_once('resurs_paymentDiffType.php');
include_once('resurs_pdf.php');
include_once('resurs_searchCriteria.php');
include_once('resurs_withMetaData.php');
include_once('resurs_sortOrder.php');
include_once('resurs_sortAlternative.php');
include_once('resurs_basicPayment.php');
include_once('resurs_bonus.php');
include_once('resurs_additionalDebitOfPayment.php');
include_once('resurs_additionalDebitOfPaymentResponse.php');
include_once('resurs_finalizePayment.php');
include_once('resurs_finalizePaymentResponse.php');
include_once('resurs_annulPayment.php');
include_once('resurs_annulPaymentResponse.php');
include_once('resurs_creditPayment.php');
include_once('resurs_creditPaymentResponse.php');
include_once('resurs_addMetaData.php');
include_once('resurs_addMetaDataResponse.php');
include_once('resurs_getPayment.php');
include_once('resurs_getPaymentResponse.php');
include_once('resurs_getPaymentDocumentNames.php');
include_once('resurs_getPaymentDocumentNamesResponse.php');
include_once('resurs_getPaymentDocument.php');
include_once('resurs_getPaymentDocumentResponse.php');
include_once('resurs_calculateResultSize.php');
include_once('resurs_calculateResultSizeResponse.php');
include_once('resurs_findPayments.php');
include_once('resurs_findPaymentsResponse.php');
include_once('resurs_insertBonusPoints.php');
include_once('resurs_insertBonusPointsResponse.php');
include_once('resurs_withdrawBonusPoints.php');
include_once('resurs_withdrawBonusPointsResponse.php');
include_once('resurs_getCustomerBonus.php');
include_once('resurs_getCustomerBonusResponse.php');
include_once('resurs_ECommerceError.php');

class Resurs_AfterShopFlowService extends \SoapClient
{

    /**
     * @var array $classmap The defined classes
     * @access private
     */
    private static $classmap = array(
      'customer' => 'Resursbank\RBEcomPHP\resurs_customer',
      'address' => 'Resursbank\RBEcomPHP\resurs_address',
      'mapEntry' => 'Resursbank\RBEcomPHP\resurs_mapEntry',
      'paymentSpec' => 'Resursbank\RBEcomPHP\resurs_paymentSpec',
      'specLine' => 'Resursbank\RBEcomPHP\resurs_specLine',
      'limit' => 'Resursbank\RBEcomPHP\resurs_limit',
      'payment' => 'Resursbank\RBEcomPHP\resurs_payment',
      'paymentDiff' => 'Resursbank\RBEcomPHP\resurs_paymentDiff',
      'pdf' => 'Resursbank\RBEcomPHP\resurs_pdf',
      'searchCriteria' => 'Resursbank\RBEcomPHP\resurs_searchCriteria',
      'withMetaData' => 'Resursbank\RBEcomPHP\resurs_withMetaData',
      'sortOrder' => 'Resursbank\RBEcomPHP\resurs_sortOrder',
      'basicPayment' => 'Resursbank\RBEcomPHP\resurs_basicPayment',
      'bonus' => 'Resursbank\RBEcomPHP\resurs_bonus',
      'additionalDebitOfPayment' => 'Resursbank\RBEcomPHP\resurs_additionalDebitOfPayment',
      'additionalDebitOfPaymentResponse' => 'Resursbank\RBEcomPHP\resurs_additionalDebitOfPaymentResponse',
      'finalizePayment' => 'Resursbank\RBEcomPHP\resurs_finalizePayment',
      'finalizePaymentResponse' => 'Resursbank\RBEcomPHP\resurs_finalizePaymentResponse',
      'annulPayment' => 'Resursbank\RBEcomPHP\resurs_annulPayment',
      'annulPaymentResponse' => 'Resursbank\RBEcomPHP\resurs_annulPaymentResponse',
      'creditPayment' => 'Resursbank\RBEcomPHP\resurs_creditPayment',
      'creditPaymentResponse' => 'Resursbank\RBEcomPHP\resurs_creditPaymentResponse',
      'addMetaData' => 'Resursbank\RBEcomPHP\resurs_addMetaData',
      'addMetaDataResponse' => 'Resursbank\RBEcomPHP\resurs_addMetaDataResponse',
      'getPayment' => 'Resursbank\RBEcomPHP\resurs_getPayment',
      'getPaymentResponse' => 'Resursbank\RBEcomPHP\resurs_getPaymentResponse',
      'getPaymentDocumentNames' => 'Resursbank\RBEcomPHP\resurs_getPaymentDocumentNames',
      'getPaymentDocumentNamesResponse' => 'Resursbank\RBEcomPHP\resurs_getPaymentDocumentNamesResponse',
      'getPaymentDocument' => 'Resursbank\RBEcomPHP\resurs_getPaymentDocument',
      'getPaymentDocumentResponse' => 'Resursbank\RBEcomPHP\resurs_getPaymentDocumentResponse',
      'calculateResultSize' => 'Resursbank\RBEcomPHP\resurs_calculateResultSize',
      'calculateResultSizeResponse' => 'Resursbank\RBEcomPHP\resurs_calculateResultSizeResponse',
      'findPayments' => 'Resursbank\RBEcomPHP\resurs_findPayments',
      'findPaymentsResponse' => 'Resursbank\RBEcomPHP\resurs_findPaymentsResponse',
      'insertBonusPoints' => 'Resursbank\RBEcomPHP\resurs_insertBonusPoints',
      'insertBonusPointsResponse' => 'Resursbank\RBEcomPHP\resurs_insertBonusPointsResponse',
      'withdrawBonusPoints' => 'Resursbank\RBEcomPHP\resurs_withdrawBonusPoints',
      'withdrawBonusPointsResponse' => 'Resursbank\RBEcomPHP\resurs_withdrawBonusPointsResponse',
      'getCustomerBonus' => 'Resursbank\RBEcomPHP\resurs_getCustomerBonus',
      'getCustomerBonusResponse' => 'Resursbank\RBEcomPHP\resurs_getCustomerBonusResponse',
      'ECommerceError' => 'Resursbank\RBEcomPHP\resurs_ECommerceError');

    /**
     * @param array $options A array of config values
     * @param string $wsdl The wsdl file to use
     * @access public
     */
    public function __construct(array $options = array(), $wsdl = 'https://test.resurs.com/ecommerce-test/ws/V4/AfterShopFlowService?wsdl')
    {
      foreach (self::$classmap as $key => $value) {
        if (!isset($options['classmap'][$key])) {
          $options['classmap'][$key] = $value;
        }
      }
      
      parent::__construct($wsdl, $options);
    }

    /**
     * Makes a new, additional debit on an existing payment. This reserves the amount on the customer's
     *                 account.
     *
     *                 NB:If it is a credit payment, there must be room for the additional
     *                 debit within the limit.
     *
     * @param resurs_additionalDebitOfPayment $parameters
     * @access public
     * @return additionalDebitOfPaymentResponse
     */
    public function additionalDebitOfPayment($parameters)
    {
      return $this->__soapCall('additionalDebitOfPayment', array($parameters));
    }

    /**
     * Finalizes a payment. When a payment is finalized, the amount will be transferred from the customer's
     *                 account to that of the representative.
     *
     *                 NB:For a payment to be finalized, it must be booked and it cannot be frozen.
     *
     * @param resurs_finalizePayment $parameters
     * @access public
     * @return finalizePaymentResponse
     */
    public function finalizePayment($parameters)
    {
      return $this->__soapCall('finalizePayment', array($parameters));
    }

    /**
     * Annuls the payment or part of it. This removes the reservation on the customer's account.
     *
     *                 NB:For a payment to be annulled, it must be booked. If it has been finalized,
     *                 it can no longer be annulled. (Finalized payments have to be credited.)
     *
     * @param resurs_annulPayment $parameters
     * @access public
     * @return annulPaymentResponse
     */
    public function annulPayment($parameters)
    {
      return $this->__soapCall('annulPayment', array($parameters));
    }

    /**
     * Credits the payment or part of it. This returns payment amount from the representative
     *                 to the customer's account.
     *                 To remove a part
     *
     *                 NB:For a payment to be credited, it must be finalized.
     *                 (Non-finalized payments have to be annulled.)
     *
     * @param resurs_creditPayment $parameters
     * @access public
     * @return creditPaymentResponse
     */
    public function creditPayment($parameters)
    {
      return $this->__soapCall('creditPayment', array($parameters));
    }

    /**
     * Adds meta data to the payment. The meta data can be used to register additional
     *                 information about the payment, and they may also be used for searching.
     *
     *                 NB:Currently, meta data cannot be removed from a payment.
     *                 However, existing values can be over-written.
     *
     * @param resurs_addMetaData $parameters
     * @access public
     * @return addMetaDataResponse
     */
    public function addMetaData($parameters)
    {
      return $this->__soapCall('addMetaData', array($parameters));
    }

    /**
     * Retrieves detailed information about the payment.
     *
     * @param resurs_getPayment $parameters
     * @access public
     * @return getPaymentResponse
     */
    public function getPayment($parameters)
    {
      return $this->__soapCall('getPayment', array($parameters));
    }

    /**
     * Retrieves the names of all documents associated with the payments. These
     *                 include, but are not necessarily limited to, previously generated invoices
     *                 and credit notes sent to the customer.
     *
     * @param resurs_getPaymentDocumentNames $parameters
     * @access public
     * @return getPaymentDocumentNamesResponse
     */
    public function getPaymentDocumentNames($parameters)
    {
      return $this->__soapCall('getPaymentDocumentNames', array($parameters));
    }

    /**
     * Retrieves a specified document from the payment.
     *
     * @param resurs_getPaymentDocument $parameters
     * @access public
     * @return getPaymentDocumentResponse
     */
    public function getPaymentDocument($parameters)
    {
      return $this->__soapCall('getPaymentDocument', array($parameters));
    }

    /**
     * Returns the number of payments that match the specified requirements. Can be
     *                 used for paging of the results.
     *
     * @param resurs_calculateResultSize $parameters
     * @access public
     * @return calculateResultSizeResponse
     */
    public function calculateResultSize($parameters)
    {
      return $this->__soapCall('calculateResultSize', array($parameters));
    }

    /**
     * Searches for payments that match the specified requirements. The result may be a couple of
     *                 minutes old. Do not use this function to locate just booked payments, and prefer getPayment
     *                 if a paymentId is present.
     *
     * @param resurs_findPayments $parameters
     * @access public
     * @return findPaymentsResponse
     */
    public function findPayments($parameters)
    {
      return $this->__soapCall('findPayments', array($parameters));
    }

    /**
     * Insert bonus points on a specific customer.
     *
     * @param resurs_insertBonusPoints $parameters
     * @access public
     * @return insertBonusPointsResponse
     */
    public function insertBonusPoints($parameters)
    {
      return $this->__soapCall('insertBonusPoints', array($parameters));
    }

    /**
     * Withdraw bonus points on a specific customer.
     *
     * @param resurs_withdrawBonusPoints $parameters
     * @access public
     * @return withdrawBonusPointsResponse
     */
    public function withdrawBonusPoints($parameters)
    {
      return $this->__soapCall('withdrawBonusPoints', array($parameters));
    }

    /**
     * Fetches the bonus the customer have, if any.
     *                 Read more about bonus
     *
     * @param resurs_getCustomerBonus $parameters
     * @access public
     * @return getCustomerBonusResponse
     */
    public function getCustomerBonus($parameters)
    {
      return $this->__soapCall('getCustomerBonus', array($parameters));
    }

}

}
