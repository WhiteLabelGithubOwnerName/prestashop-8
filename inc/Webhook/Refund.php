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
 * Webhook processor to handle refund state transitions.
 */
class WhiteLabelMachineNameWebhookRefund extends WhiteLabelMachineNameWebhookOrderrelatedabstract
{

    /**
     * Processes the received order related webhook request.
     *
     * @param WhiteLabelMachineNameWebhookRequest $request
     */
    public function process(WhiteLabelMachineNameWebhookRequest $request)
    {
        parent::process($request);
        $refund = $this->loadEntity($request);
        $refundJob = WhiteLabelMachineNameModelRefundjob::loadByExternalId(
            $refund->getLinkedSpaceId(),
            $refund->getExternalId()
        );
        if ($refundJob->getState() == WhiteLabelMachineNameModelRefundjob::STATE_APPLY) {
            WhiteLabelMachineNameServiceRefund::instance()->applyRefundToShop($refundJob->getId());
        }
    }

    /**
     *
     * @see WhiteLabelMachineNameWebhookOrderrelatedabstract::loadEntity()
     * @return \WhiteLabelMachineName\Sdk\Model\Refund
     */
    protected function loadEntity(WhiteLabelMachineNameWebhookRequest $request)
    {
        $refundService = new \WhiteLabelMachineName\Sdk\Service\RefundService(
            WhiteLabelMachineNameHelper::getApiClient()
        );
        return $refundService->read($request->getSpaceId(), $request->getEntityId());
    }

    protected function getOrderId($refund)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\Refund $refund */
        return $refund->getTransaction()->getMerchantReference();
    }

    protected function getTransactionId($refund)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\Refund $refund */
        return $refund->getTransaction()->getId();
    }

    protected function processOrderRelatedInner(Order $order, $refund)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\Refund $refund */
        switch ($refund->getState()) {
            case \WhiteLabelMachineName\Sdk\Model\RefundState::FAILED:
                $this->failed($refund, $order);
                break;
            case \WhiteLabelMachineName\Sdk\Model\RefundState::SUCCESSFUL:
                $this->refunded($refund, $order);
                break;
            default:
                // Nothing to do.
                break;
        }
    }

    protected function failed(\WhiteLabelMachineName\Sdk\Model\Refund $refund, Order $order)
    {
        $refundJob = WhiteLabelMachineNameModelRefundjob::loadByExternalId(
            $refund->getLinkedSpaceId(),
            $refund->getExternalId()
        );
        if ($refundJob->getId()) {
            $refundJob->setState(WhiteLabelMachineNameModelRefundjob::STATE_FAILURE);
            $refundJob->setRefundId($refund->getId());
            if ($refund->getFailureReason() != null) {
                $refundJob->setFailureReason($refund->getFailureReason()
                    ->getDescription());
            }
            $refundJob->save();
        }
    }

    protected function refunded(\WhiteLabelMachineName\Sdk\Model\Refund $refund, Order $order)
    {
        $refundJob = WhiteLabelMachineNameModelRefundjob::loadByExternalId(
            $refund->getLinkedSpaceId(),
            $refund->getExternalId()
        );
        if ($refundJob->getId()) {
            $refundJob->setState(WhiteLabelMachineNameModelRefundjob::STATE_APPLY);
            $refundJob->setRefundId($refund->getId());
            $refundJob->save();
        }
    }
}
