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
 * WhiteLabelMachineName_Service_Method_Configuration Class.
 */
class WhiteLabelMachineNameServiceMethodconfiguration extends WhiteLabelMachineNameServiceAbstract
{

    /**
     * Updates the data of the payment method configuration.
     *
     * @param \WhiteLabelMachineName\Sdk\Model\PaymentMethodConfiguration $configuration
     */
    public function updateData(\WhiteLabelMachineName\Sdk\Model\PaymentMethodConfiguration $configuration)
    {
        $entities = WhiteLabelMachineNameModelMethodconfiguration::loadByConfiguration(
            $configuration->getLinkedSpaceId(),
            $configuration->getId()
        );
        foreach ($entities as $entity) {
            if ($this->hasChanged($configuration, $entity)) {
                $entity->setConfigurationName($configuration->getName());
                $entity->setState($this->getConfigurationState($configuration));
                $entity->setTitle($configuration->getResolvedTitle());
                $entity->setDescription($configuration->getResolvedDescription());
                $entity->setImage($this->getResourcePath($configuration->getResolvedImageUrl()));
                $entity->setImageBase($this->getResourceBase($configuration->getResolvedImageUrl()));
                $entity->setSortOrder($configuration->getSortOrder());
                $entity->save();
            }
        }
    }

    private function hasChanged(
        \WhiteLabelMachineName\Sdk\Model\PaymentMethodConfiguration $configuration,
        WhiteLabelMachineNameModelMethodconfiguration $entity
    ) {
        if ($configuration->getName() != $entity->getConfigurationName()) {
            return true;
        }

        if ($this->getConfigurationState($configuration) != $entity->getState()) {
            return true;
        }

        if ($configuration->getSortOrder() != $entity->getSortOrder()) {
            return true;
        }

        if ($configuration->getResolvedTitle() != $entity->getTitle()) {
            return true;
        }

        if ($configuration->getResolvedDescription() != $entity->getDescription()) {
            return true;
        }

        $image = $this->getResourcePath($configuration->getResolvedImageUrl());
        if ($image != $entity->getImage()) {
            return true;
        }

        $imageBase = $this->getResourceBase($configuration->getResolvedImageUrl());
        if ($imageBase != $entity->getImageBase()) {
            return true;
        }

        return false;
    }

    /**
     * Synchronizes the payment method configurations from WhiteLabelName.
     */
    public function synchronize()
    {
        $existingFound = array();

        $existingConfigurations = WhiteLabelMachineNameModelMethodconfiguration::loadAll();

        $spaceIdCache = array();

        $paymentMethodConfigurationService = new \WhiteLabelMachineName\Sdk\Service\PaymentMethodConfigurationService(
            WhiteLabelMachineNameHelper::getApiClient()
        );

        foreach (Shop::getShops(true, null, true) as $shopId) {
            $spaceId = Configuration::get(WhiteLabelMachineNameBasemodule::CK_SPACE_ID, null, null, $shopId);

            if ($spaceId) {
                if (! array_key_exists($spaceId, $spaceIdCache)) {
                    $spaceIdCache[$spaceId] = $paymentMethodConfigurationService->search(
                        $spaceId,
                        new \WhiteLabelMachineName\Sdk\Model\EntityQuery()
                    );
                }
                $configurations = $spaceIdCache[$spaceId];
                foreach ($configurations as $configuration) {
                    $method = WhiteLabelMachineNameModelMethodconfiguration::loadByConfigurationAndShop(
                        $spaceId,
                        $configuration->getId(),
                        $shopId
                    );
                    if ($method->getId() !== null) {
                        $existingFound[] = $method->getId();
                    }
                    $method->setShopId($shopId);
                    $method->setSpaceId($spaceId);
                    $method->setConfigurationId($configuration->getId());
                    $method->setConfigurationName($configuration->getName());
                    $method->setState($this->getConfigurationState($configuration));
                    $method->setTitle($configuration->getResolvedTitle());
                    $method->setDescription($configuration->getResolvedDescription());
                    $method->setImage($this->getResourcePath($configuration->getResolvedImageUrl()));
                    $method->setImageBase($this->getResourceBase($configuration->getResolvedImageUrl()));
                    $method->setSortOrder($configuration->getSortOrder());
                    $method->save();
                }
            }
        }
        foreach ($existingConfigurations as $existingConfiguration) {
            if (! in_array($existingConfiguration->getId(), $existingFound)) {
                $existingConfiguration->setState(WhiteLabelMachineNameModelMethodconfiguration::STATE_HIDDEN);
                $existingConfiguration->save();
            }
        }
        Cache::clean('whitelabelmachinename_methods');
    }

    /**
     * Returns the payment method for the given id.
     *
     * @param int $id
     * @return \WhiteLabelMachineName\Sdk\Model\PaymentMethod
     */
    protected function getPaymentMethod($id)
    {
        /* @var WhiteLabelMachineName_Provider_Payment_Method */
        $methodProvider = WhiteLabelMachineNameProviderPaymentmethod::instance();
        return $methodProvider->find($id);
    }

    /**
     * Returns the state for the payment method configuration.
     *
     * @param \WhiteLabelMachineName\Sdk\Model\PaymentMethodConfiguration $configuration
     * @return string
     */
    protected function getConfigurationState(\WhiteLabelMachineName\Sdk\Model\PaymentMethodConfiguration $configuration)
    {
        switch ($configuration->getState()) {
            case \WhiteLabelMachineName\Sdk\Model\CreationEntityState::ACTIVE:
                return WhiteLabelMachineNameModelMethodconfiguration::STATE_ACTIVE;
            case \WhiteLabelMachineName\Sdk\Model\CreationEntityState::INACTIVE:
                return WhiteLabelMachineNameModelMethodconfiguration::STATE_INACTIVE;
            default:
                return WhiteLabelMachineNameModelMethodconfiguration::STATE_HIDDEN;
        }
    }
}
