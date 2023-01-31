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
 * Webhook processor to handle delivery indication state transitions.
 */
class WhiteLabelMachineNameWebhookDeliveryindication extends WhiteLabelMachineNameWebhookOrderrelatedabstract
{

    /**
     *
     * @see WhiteLabelMachineNameWebhookOrderrelatedabstract::loadEntity()
     * @return \WhiteLabelMachineName\Sdk\Model\DeliveryIndication
     */
    protected function loadEntity(WhiteLabelMachineNameWebhookRequest $request)
    {
        $deliveryIndicationService = new \WhiteLabelMachineName\Sdk\Service\DeliveryIndicationService(
            WhiteLabelMachineNameHelper::getApiClient()
        );
        return $deliveryIndicationService->read($request->getSpaceId(), $request->getEntityId());
    }

    protected function getOrderId($deliveryIndication)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\DeliveryIndication $deliveryIndication */
        return $deliveryIndication->getTransaction()->getMerchantReference();
    }

    protected function getTransactionId($deliveryIndication)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\DeliveryIndication $delivery_indication */
        return $deliveryIndication->getLinkedTransaction();
    }

    protected function processOrderRelatedInner(Order $order, $deliveryIndication)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\DeliveryIndication $deliveryIndication */
        switch ($deliveryIndication->getState()) {
            case \WhiteLabelMachineName\Sdk\Model\DeliveryIndicationState::MANUAL_CHECK_REQUIRED:
                $this->review($order);
                break;
            default:
                break;
        }
    }

    protected function review(Order $sourceOrder)
    {
        WhiteLabelMachineNameBasemodule::startRecordingMailMessages();
        $manualStatusId = Configuration::get(WhiteLabelMachineNameBasemodule::CK_STATUS_MANUAL);
        WhiteLabelMachineNameHelper::updateOrderMeta($sourceOrder, 'manual_check', true);
        $orders = $sourceOrder->getBrother();
        $orders[] = $sourceOrder;
        foreach ($orders as $order) {
            $order->setCurrentState($manualStatusId);
            $order->save();
        }
        WhiteLabelMachineNameBasemodule::stopRecordingMailMessages();
    }
}
