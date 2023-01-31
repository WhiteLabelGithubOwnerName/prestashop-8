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
 * Provider of label descriptor information from the gateway.
 */
class WhiteLabelMachineNameProviderLabeldescription extends WhiteLabelMachineNameProviderAbstract
{
    protected function __construct()
    {
        parent::__construct('whitelabelmachinename_label_description');
    }

    /**
     * Returns the label descriptor by the given code.
     *
     * @param int $id
     * @return \WhiteLabelMachineName\Sdk\Model\LabelDescriptor
     */
    public function find($id)
    {
        return parent::find($id);
    }

    /**
     * Returns a list of label descriptors.
     *
     * @return \WhiteLabelMachineName\Sdk\Model\LabelDescriptor[]
     */
    public function getAll()
    {
        return parent::getAll();
    }

    protected function fetchData()
    {
        $labelDescriptorService = new \WhiteLabelMachineName\Sdk\Service\LabelDescriptionService(
            WhiteLabelMachineNameHelper::getApiClient()
        );
        return $labelDescriptorService->all();
    }

    protected function getId($entry)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\LabelDescriptor $entry */
        return $entry->getId();
    }
}
