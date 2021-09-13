<?php

namespace Resursbank\Ecommerce\Helpers;

use Exception;
use TorneLIB\IO\Data\Content;

class Helper
{
    const TRANSLATION_SWEDISH = 'sv';
    const TRANSLATION_NORWEGIAN = 'no';
    const TRANSLATION_DANISH = 'da';
    const TRANSLATION_FINNISH = 'fi';

    private $languageContainer;
    private $languageSet = 'sv';
    private $preloadedLanguage;
    private $languageExtensions = ['json', 'xml'];
    private $allowedLanguages = ['sv', 'no', 'da', 'fi'];

    public function __construct()
    {
        $this->languageContainer = __DIR__ . '/Container';
    }

    private function getPreloadedLanguage()
    {
        foreach ($this->languageExtensions as $extension) {
            $languageFile = sprintf('%s/lang.common.%s.%s', $this->languageContainer, $this->languageSet, $extension);
            if (file_exists($languageFile)) {
                $content = file_get_contents($languageFile);
                switch ($extension) {
                    case 'json':
                        $this->preloadedLanguage = json_decode($content, true, 512);
                        break;
                    case 'xml':
                        $this->preloadedLanguage = (new Content())->getFromXml(
                            $content,
                            Content::XML_NORMALIZE + Content::XML_NO_PATH
                        );
                        break;
                    default:
                }
                break;
            }
        }
    }

    /**
     * Set language country code. Resurs Bank usually partially uses country codes based on ISO 639-1 here.
     * (Partially = Finland is considered 'fin' according to ISO 639-1)
     *
     * @param string $useLanguage
     * @link https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     */
    public function setLanguage($useLanguage = self::TRANSLATION_SWEDISH)
    {
        switch (strtolower($useLanguage)) {
            case 'se':
                $configureLanguage = 'sv';
                break;
            case 'fin':
                $configureLanguage = 'fi';
                break;
            case 'dk':
                $configureLanguage = 'da';
                break;
            default:
                $configureLanguage = $useLanguage;
        }

        if (!in_array($configureLanguage, $this->allowedLanguages, true)) {
            throw new Exception(sprintf('%s is not an allowed country code.', $configureLanguage), 403);
        }

        $this->languageSet = $configureLanguage;

        $this->getPreloadedLanguage();
        return $this;
    }

    public function getLanguage()
    {
        return $this->preloadedLanguage;
    }
}
