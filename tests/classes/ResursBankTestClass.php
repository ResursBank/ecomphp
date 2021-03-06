<?php

// Note: Namespaces must be left out for EC-1.0
namespace Resursbank\RBEcomPHP;

use Exception;
use ResursException;

/**
 * Class RESURS_TEST_BRIDGE Primary test class for setting up and simplify standard tests like order booking etc
 */
class RESURS_TEST_BRIDGE
{

    /** @var ResursBank The ECom Class */
    public $ECOM;

    /** @var string Shared data filename */
    private $shareFile;

    /**
     * RESURS_TEST_BRIDGE constructor.
     *
     * @param string $userName
     * @param string $password
     * @throws Exception
     */
    public function __construct($userName = 'ecomphpPipelineTest', $password = '4Em4r5ZQ98x3891D6C19L96TQ72HsisD')
    {
        $this->shareFile = __DIR__ . "/../storage/shared.serialize";
        $this->ECOM = new ResursBank($userName, $password, RESURS_ENVIRONMENTS::TEST, true);
    }

    /**
     * getCredentialControl(): Initiates ECom with proper credentials or failing credentials
     *
     * @param bool $successLogin
     * @return mixed
     * @throws Exception
     * @noinspection ParameterDefaultValueIsNotNullInspection
     */
    public function getCredentialControl($successLogin = true)
    {
        if (!(bool)$successLogin) {
            $this->ECOM = new ResursBank("fail", "fail");
        }

        return $this->ECOM->getPaymentMethods();
    }

    /**
     * Share data between test sessions
     *
     * @param string $key If value is empty, the full content will be shown
     * @param null $value If value is null, the content of $key will displayed
     * @param bool $appendArray If false, reset the key
     *
     * @return mixed
     */
    public function share($key = '', $value = null, $appendArray = true)
    {
        if (!file_exists($this->shareFile)) {
            file_put_contents($this->shareFile, "");
        }
        $shareData = unserialize(file_get_contents($this->shareFile));

        if (!empty($key)) {
            if (!isset($shareData[$key])) {
                if (!is_null($value)) {
                    $shareData[$key] = [$value];
                } else {
                    return null;
                }
            } else {
                if (!is_null($value)) {
                    if ($appendArray) {
                        $shareData[$key][] = $value;
                    } else {
                        $shareData[$key] = [$value];
                    }
                } else {
                    return $shareData[$key];
                }
            }
        }
        file_put_contents($this->shareFile, serialize($shareData));

        return $shareData;
    }

    public function unshare($key = '')
    {
        if (!empty($key)) {
            $currentShare = $this->share();
            unset($currentShare[$key]);
            @file_put_contents($this->shareFile, serialize($currentShare));
            return true;
        }
        return false;
    }

    /**
     * setFlow: Prepare a flow to test, defaults to the simplified
     *
     * @param int $flow
     */
    public function setFlow($flow = RESURS_FLOW_TYPES::FLOW_SIMPLIFIED_FLOW)
    {
        $this->ECOM->setPreferredPaymentFlowService($flow);
    }
}
