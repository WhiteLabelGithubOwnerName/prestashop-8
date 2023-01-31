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
 * This service handles webhooks.
 */
class WhiteLabelMachineNameServiceWebhook extends WhiteLabelMachineNameServiceAbstract
{

    /**
     * The webhook listener API service.
     *
     * @var \WhiteLabelMachineName\Sdk\Service\WebhookListenerService
     */
    private $webhookListenerService;

    /**
     * The webhook url API service.
     *
     * @var \WhiteLabelMachineName\Sdk\Service\WebhookUrlService
     */
    private $webhookUrlService;

    private $webhookEntities = array();

    /**
     * Constructor to register the webhook entites.
     */
    public function __construct()
    {
        $this->webhookEntities[1487165678181] = new WhiteLabelMachineNameWebhookEntity(
            1487165678181,
            'Manual Task',
            array(
                \WhiteLabelMachineName\Sdk\Model\ManualTaskState::DONE,
                \WhiteLabelMachineName\Sdk\Model\ManualTaskState::EXPIRED,
                \WhiteLabelMachineName\Sdk\Model\ManualTaskState::OPEN
            ),
            'WhiteLabelMachineNameWebhookManualtask'
        );
        $this->webhookEntities[1472041857405] = new WhiteLabelMachineNameWebhookEntity(
            1472041857405,
            'Payment Method Configuration',
            array(
                \WhiteLabelMachineName\Sdk\Model\CreationEntityState::ACTIVE,
                \WhiteLabelMachineName\Sdk\Model\CreationEntityState::DELETED,
                \WhiteLabelMachineName\Sdk\Model\CreationEntityState::DELETING,
                \WhiteLabelMachineName\Sdk\Model\CreationEntityState::INACTIVE
            ),
            'WhiteLabelMachineNameWebhookMethodconfiguration',
            true
        );
        $this->webhookEntities[1472041829003] = new WhiteLabelMachineNameWebhookEntity(
            1472041829003,
            'Transaction',
            array(
                \WhiteLabelMachineName\Sdk\Model\TransactionState::AUTHORIZED,
                \WhiteLabelMachineName\Sdk\Model\TransactionState::DECLINE,
                \WhiteLabelMachineName\Sdk\Model\TransactionState::FAILED,
                \WhiteLabelMachineName\Sdk\Model\TransactionState::FULFILL,
                \WhiteLabelMachineName\Sdk\Model\TransactionState::VOIDED,
                \WhiteLabelMachineName\Sdk\Model\TransactionState::COMPLETED
            ),
            'WhiteLabelMachineNameWebhookTransaction'
        );
        $this->webhookEntities[1472041819799] = new WhiteLabelMachineNameWebhookEntity(
            1472041819799,
            'Delivery Indication',
            array(
                \WhiteLabelMachineName\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED
            ),
            'WhiteLabelMachineNameWebhookDeliveryindication'
        );

        $this->webhookEntities[1472041831364] = new WhiteLabelMachineNameWebhookEntity(
            1472041831364,
            'Transaction Completion',
            array(
                \WhiteLabelMachineName\Sdk\Model\TransactionCompletionState::FAILED,
                \WhiteLabelMachineName\Sdk\Model\TransactionCompletionState::SUCCESSFUL
            ),
            'WhiteLabelMachineNameWebhookTransactioncompletion'
        );

        $this->webhookEntities[1472041867364] = new WhiteLabelMachineNameWebhookEntity(
            1472041867364,
            'Transaction Void',
            array(
                \WhiteLabelMachineName\Sdk\Model\TransactionVoidState::FAILED,
                \WhiteLabelMachineName\Sdk\Model\TransactionVoidState::SUCCESSFUL
            ),
            'WhiteLabelMachineNameWebhookTransactionvoid'
        );

        $this->webhookEntities[1472041839405] = new WhiteLabelMachineNameWebhookEntity(
            1472041839405,
            'Refund',
            array(
                \WhiteLabelMachineName\Sdk\Model\RefundState::FAILED,
                \WhiteLabelMachineName\Sdk\Model\RefundState::SUCCESSFUL
            ),
            'WhiteLabelMachineNameWebhookRefund'
        );
        $this->webhookEntities[1472041806455] = new WhiteLabelMachineNameWebhookEntity(
            1472041806455,
            'Token',
            array(
                \WhiteLabelMachineName\Sdk\Model\CreationEntityState::ACTIVE,
                \WhiteLabelMachineName\Sdk\Model\CreationEntityState::DELETED,
                \WhiteLabelMachineName\Sdk\Model\CreationEntityState::DELETING,
                \WhiteLabelMachineName\Sdk\Model\CreationEntityState::INACTIVE
            ),
            'WhiteLabelMachineNameWebhookToken'
        );
        $this->webhookEntities[1472041811051] = new WhiteLabelMachineNameWebhookEntity(
            1472041811051,
            'Token Version',
            array(
                \WhiteLabelMachineName\Sdk\Model\TokenVersionState::ACTIVE,
                \WhiteLabelMachineName\Sdk\Model\TokenVersionState::OBSOLETE
            ),
            'WhiteLabelMachineNameWebhookTokenversion'
        );
    }

    /**
     * Installs the necessary webhooks in WhiteLabelName.
     */
    public function install()
    {
        $spaceIds = array();
        foreach (Shop::getShops(true, null, true) as $shopId) {
            $spaceId = Configuration::get(WhiteLabelMachineNameBasemodule::CK_SPACE_ID, null, null, $shopId);
            if ($spaceId && ! in_array($spaceId, $spaceIds)) {
                $webhookUrl = $this->getWebhookUrl($spaceId);
                if ($webhookUrl == null) {
                    $webhookUrl = $this->createWebhookUrl($spaceId);
                }
                $existingListeners = $this->getWebhookListeners($spaceId, $webhookUrl);
                foreach ($this->webhookEntities as $webhookEntity) {
                    /* @var WhiteLabelMachineNameWebhookEntity $webhookEntity */
                    $exists = false;
                    foreach ($existingListeners as $existingListener) {
                        if ($existingListener->getEntity() == $webhookEntity->getId()) {
                            $exists = true;
                        }
                    }
                    if (! $exists) {
                        $this->createWebhookListener($webhookEntity, $spaceId, $webhookUrl);
                    }
                }
                $spaceIds[] = $spaceId;
            }
        }
    }

    /**
     *
     * @param int|string $id
     * @return WhiteLabelMachineNameWebhookEntity
     */
    public function getWebhookEntityForId($id)
    {
        if (isset($this->webhookEntities[$id])) {
            return $this->webhookEntities[$id];
        }
        return null;
    }

    /**
     * Create a webhook listener.
     *
     * @param WhiteLabelMachineNameWebhookEntity $entity
     * @param int $spaceId
     * @param \WhiteLabelMachineName\Sdk\Model\WebhookUrl $webhookUrl
     * @return \WhiteLabelMachineName\Sdk\Model\WebhookListenerCreate
     */
    protected function createWebhookListener(
        WhiteLabelMachineNameWebhookEntity $entity,
        $spaceId,
        \WhiteLabelMachineName\Sdk\Model\WebhookUrl $webhookUrl
    ) {
        $webhookListener = new \WhiteLabelMachineName\Sdk\Model\WebhookListenerCreate();
        $webhookListener->setEntity($entity->getId());
        $webhookListener->setEntityStates($entity->getStates());
        $webhookListener->setName('Prestashop ' . $entity->getName());
        $webhookListener->setState(\WhiteLabelMachineName\Sdk\Model\CreationEntityState::ACTIVE);
        $webhookListener->setUrl($webhookUrl->getId());
        $webhookListener->setNotifyEveryChange($entity->isNotifyEveryChange());
        return $this->getWebhookListenerService()->create($spaceId, $webhookListener);
    }

    /**
     * Returns the existing webhook listeners.
     *
     * @param int $spaceId
     * @param \WhiteLabelMachineName\Sdk\Model\WebhookUrl $webhookUrl
     * @return \WhiteLabelMachineName\Sdk\Model\WebhookListener[]
     */
    protected function getWebhookListeners($spaceId, \WhiteLabelMachineName\Sdk\Model\WebhookUrl $webhookUrl)
    {
        $query = new \WhiteLabelMachineName\Sdk\Model\EntityQuery();
        $filter = new \WhiteLabelMachineName\Sdk\Model\EntityQueryFilter();
        $filter->setType(\WhiteLabelMachineName\Sdk\Model\EntityQueryFilterType::_AND);
        $filter->setChildren(
            array(
                $this->createEntityFilter('state', \WhiteLabelMachineName\Sdk\Model\CreationEntityState::ACTIVE),
                $this->createEntityFilter('url.id', $webhookUrl->getId())
            )
        );
        $query->setFilter($filter);
        return $this->getWebhookListenerService()->search($spaceId, $query);
    }

    /**
     * Creates a webhook url.
     *
     * @param int $spaceId
     * @return \WhiteLabelMachineName\Sdk\Model\WebhookUrlCreate
     */
    protected function createWebhookUrl($spaceId)
    {
        $webhookUrl = new \WhiteLabelMachineName\Sdk\Model\WebhookUrlCreate();
        $webhookUrl->setUrl($this->getUrl());
        $webhookUrl->setState(\WhiteLabelMachineName\Sdk\Model\CreationEntityState::ACTIVE);
        $webhookUrl->setName('Prestashop');
        return $this->getWebhookUrlService()->create($spaceId, $webhookUrl);
    }

    /**
     * Returns the existing webhook url if there is one.
     *
     * @param int $spaceId
     * @return \WhiteLabelMachineName\Sdk\Model\WebhookUrl
     */
    protected function getWebhookUrl($spaceId)
    {
        $query = new \WhiteLabelMachineName\Sdk\Model\EntityQuery();
        $filter = new \WhiteLabelMachineName\Sdk\Model\EntityQueryFilter();
        $filter->setType(\WhiteLabelMachineName\Sdk\Model\EntityQueryFilterType::_AND);
        $filter->setChildren(
            array(
                $this->createEntityFilter('state', \WhiteLabelMachineName\Sdk\Model\CreationEntityState::ACTIVE),
                $this->createEntityFilter('url', $this->getUrl())
            )
        );
        $query->setFilter($filter);
        $query->setNumberOfEntities(1);
        $result = $this->getWebhookUrlService()->search($spaceId, $query);
        if (! empty($result)) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Returns the webhook endpoint URL.
     *
     * @return string
     */
    protected function getUrl()
    {
        $link = Context::getContext()->link;

        $shopIds = Shop::getShops(true, null, true);
        asort($shopIds);
        $shopId = reset($shopIds);

        $languageIds = Language::getLanguages(true, $shopId, true);
        asort($languageIds);
        $languageId = reset($languageIds);

        $url = $link->getModuleLink('whitelabelmachinename', 'webhook', array(), true, $languageId, $shopId);
        // We have to parse the link, because of issue http://forge.prestashop.com/browse/BOOM-5799
        $urlQuery = parse_url($url, PHP_URL_QUERY);
        if (stripos($urlQuery, 'controller=module') !== false && stripos($urlQuery, 'controller=webhook') !== false) {
            $url = str_replace('controller=module', 'fc=module', $url);
        }
        return $url;
    }

    /**
     * Returns the webhook listener API service.
     *
     * @return \WhiteLabelMachineName\Sdk\Service\WebhookListenerService
     */
    protected function getWebhookListenerService()
    {
        if ($this->webhookListenerService == null) {
            $this->webhookListenerService = new \WhiteLabelMachineName\Sdk\Service\WebhookListenerService(
                WhiteLabelMachineNameHelper::getApiClient()
            );
        }
        return $this->webhookListenerService;
    }

    /**
     * Returns the webhook url API service.
     *
     * @return \WhiteLabelMachineName\Sdk\Service\WebhookUrlService
     */
    protected function getWebhookUrlService()
    {
        if ($this->webhookUrlService == null) {
            $this->webhookUrlService = new \WhiteLabelMachineName\Sdk\Service\WebhookUrlService(
                WhiteLabelMachineNameHelper::getApiClient()
            );
        }
        return $this->webhookUrlService;
    }
}
