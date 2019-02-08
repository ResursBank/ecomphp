<?php

require_once('../vendor/autoload.php');

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

/**
 * Class RESURS_WEBDRIVER WebDriver Helper functions
 */
class RESURS_WEBDRIVER
{
    /** @var \Facebook\WebDriver\WebDriver */
    public $REMOTE;
    private $HEADLESS;
    private $BROWSER_BINARY;
    private $CAPABILITIES;
    private $initialized = false;

    /**
     * RESURS_WEBDRIVER constructor.
     * @param null $capabilities
     * @param bool $HEADLESS
     * @param string $browserBinary
     */
    public function __construct($capabilities = null, $HEADLESS = false, $browserBinary = '/usr/bin/google-chrome')
    {
        $this->HEADLESS = $HEADLESS;
        $this->BROWSER_BINARY = $browserBinary;
        $this->CAPABILITIES = $capabilities;
    }

    /**
     * @return bool
     */
    public function init()
    {
        $host = 'http://127.0.0.1:4444/wd/hub';

        if (is_null($this->CAPABILITIES)) {
            $options = new ChromeOptions();
            $optionsArray = array(
                '--window-size=1920,1080',
                '--no-sandbox'
            );
            if ($this->HEADLESS) {
                $optionsArray[] = '--headless';
            }
            $options->setBinary($this->BROWSER_BINARY);
            $options->addArguments($optionsArray);
            $capabilities = DesiredCapabilities::chrome();
            $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
            $this->CAPABILITIES = $capabilities;
        }

        $this->REMOTE = RemoteWebDriver::create($host, $this->CAPABILITIES);
        $this->initialized = true;
        return $this->initialized;
    }

    public function waitForElementAndSetData($elementId = '', $elementData = '', $by = "id")
    {
        $elementNotVisible = true;
        while ($elementNotVisible) {
            try {
                if ($by == "id") {
                    $this->REMOTE->findElement(\Facebook\WebDriver\WebDriverBy::id($elementId))->sendKeys($elementData);
                } elseif ($by == "xpath") {
                    $this->REMOTE->findElement(\Facebook\WebDriver\WebDriverBy::xpath($elementId))->sendKeys($elementData);
                } elseif ($by == "class") {
                    $this->REMOTE->findElement(\Facebook\WebDriver\WebDriverBy::className($elementId))->sendKeys($elementData);
                }
                break;
            } catch (\Exception $elementException) {
            }
        }
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->initialized;
    }

    public function getElementByWait($elementId = '', $by = "id")
    {
        $elementNotVisible = true;
        $returnElement = null;
        while ($elementNotVisible) {
            try {
                if ($by == "id") {
                    $returnElement = $this->REMOTE->findElement(\WebDriverBy::id($elementId));
                } elseif ($by == "xpath") {
                    $returnElement = $this->REMOTE->findElement(\WebDriverBy::xpath($elementId));
                } elseif ($by == "class") {
                    $returnElement = $this->REMOTE->findElement(\WebDriverBy::className($elementId));
                }
                break;
            } catch (\Exception $elementException) {

            }
        }

        return $returnElement;
    }

}