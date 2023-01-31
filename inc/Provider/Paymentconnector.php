<?php
/**
 * WhiteLabelName Prestashop
 *
 * This Prestashop module enables to process payments with WhiteLabelName (https://whitelabel-website.com).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2023 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

/**
 * Provider of payment connector information from the gateway.
 */
class WhiteLabelMachineNameProviderPaymentconnector extends WhiteLabelMachineNameProviderAbstract
{
    protected function __construct()
    {
        parent::__construct('whitelabelmachinename_connectors');
    }

    /**
     * Returns the payment connector by the given id.
     *
     * @param int $id
     * @return \WhiteLabelMachineName\Sdk\Model\PaymentConnector
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Returns a list of payment connectors.
     *
     * @return \WhiteLabelMachineName\Sdk\Model\PaymentConnector[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $connectorService = new \WhiteLabelMachineName\Sdk\Service\PaymentConnectorService(
            WhiteLabelMachineNameHelper::getApiClient()
        );
        return $connectorService->all();
    }

    protected function getId($entry)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\PaymentConnector $entry */
        return $entry->getId();
    }
}
