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
 * Provider of payment method information from the gateway.
 */
class WhiteLabelMachineNameProviderPaymentmethod extends WhiteLabelMachineNameProviderAbstract
{
    protected function __construct()
    {
        parent::__construct('whitelabelmachinename_methods');
    }

    /**
     * Returns the payment method by the given id.
     *
     * @param int $id
     * @return \WhiteLabelMachineName\Sdk\Model\PaymentMethod
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Returns a list of payment methods.
     *
     * @return \WhiteLabelMachineName\Sdk\Model\PaymentMethod[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $methodService = new \WhiteLabelMachineName\Sdk\Service\PaymentMethodService(
            WhiteLabelMachineNameHelper::getApiClient()
        );
        return $methodService->all();
    }

    protected function getId($entry)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\PaymentMethod $entry */
        return $entry->getId();
    }
}
