<?php
/**
 * WhiteLabelName Prestashop
 *
 * This Prestashop module enables to process payments with WhiteLabelName (https://whitelabel-website.com).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2024 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

/**
 * Provider of language information from the gateway.
 */
class WhiteLabelMachineNameProviderLanguage extends WhiteLabelMachineNameProviderAbstract
{
    protected function __construct()
    {
        parent::__construct('whitelabelmachinename_languages');
    }

    /**
     * Returns the language by the given code.
     *
     * @param string $code
     * @return \WhiteLabelMachineName\Sdk\Model\RestLanguage
     */
    public function find($code)
    {
        return parent::find($code);
    }

    /**
     * Returns the primary language in the given group.
     *
     * @param string $code
     * @return \WhiteLabelMachineName\Sdk\Model\RestLanguage
     */
    public function findPrimary($code)
    {
        $code = Tools::substr($code, 0, 2);
        foreach ($this->getAll() as $language) {
            if ($language->getIso2Code() == $code && $language->getPrimaryOfGroup()) {
                return $language;
            }
        }

        return false;
    }

    /**
     * Returns a list of language.
     *
     * @return \WhiteLabelMachineName\Sdk\Model\RestLanguage[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $languageService = new \WhiteLabelMachineName\Sdk\Service\LanguageService(
            WhiteLabelMachineNameHelper::getApiClient()
        );
        return $languageService->all();
    }

    protected function getId($entry)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\RestLanguage $entry */
        return $entry->getIetfCode();
    }
}
