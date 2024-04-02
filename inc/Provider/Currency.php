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
 * Provider of currency information from the gateway.
 */
class WhiteLabelMachineNameProviderCurrency extends WhiteLabelMachineNameProviderAbstract
{
    protected function __construct()
    {
        parent::__construct('whitelabelmachinename_currencies');
    }

    /**
     * Returns the currency by the given code.
     *
     * @param string $code
     * @return \WhiteLabelMachineName\Sdk\Model\RestCurrency
     */
    public function find($code)
    {
        return parent::find($code);
    }

    /**
     * Returns a list of currencies.
     *
     * @return \WhiteLabelMachineName\Sdk\Model\RestCurrency[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $currencyService = new \WhiteLabelMachineName\Sdk\Service\CurrencyService(
            WhiteLabelMachineNameHelper::getApiClient()
        );
        return $currencyService->all();
    }

    protected function getId($entry)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\RestCurrency $entry */
        return $entry->getCurrencyCode();
    }
}
