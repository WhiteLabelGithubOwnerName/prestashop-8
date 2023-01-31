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
 * Webhook processor to handle transaction state transitions.
 */
class WhiteLabelMachineNameWebhookTransaction extends WhiteLabelMachineNameWebhookOrderrelatedabstract
{

    /**
     *
     * @see WhiteLabelMachineNameWebhookOrderrelatedabstract::loadEntity()
     * @return \WhiteLabelMachineName\Sdk\Model\Transaction
     */
    protected function loadEntity(WhiteLabelMachineNameWebhookRequest $request)
    {
        $transactionService = new \WhiteLabelMachineName\Sdk\Service\TransactionService(
            WhiteLabelMachineNameHelper::getApiClient()
        );
        return $transactionService->read($request->getSpaceId(), $request->getEntityId());
    }

    protected function getOrderId($transaction)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\Transaction $transaction */
        return $transaction->getMerchantReference();
    }

    protected function getTransactionId($transaction)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\Transaction $transaction */
        return $transaction->getId();
    }

    protected function processOrderRelatedInner(Order $order, $transaction)
    {
        /* @var \WhiteLabelMachineName\Sdk\Model\Transaction $transaction */
        $transactionInfo = WhiteLabelMachineNameModelTransactioninfo::loadByOrderId($order->id);
        if ($transaction->getState() != $transactionInfo->getState()) {
            switch ($transaction->getState()) {
                case \WhiteLabelMachineName\Sdk\Model\TransactionState::AUTHORIZED:
                    $this->authorize($transaction, $order);
                    break;
                case \WhiteLabelMachineName\Sdk\Model\TransactionState::DECLINE:
                    $this->decline($transaction, $order);
                    break;
                case \WhiteLabelMachineName\Sdk\Model\TransactionState::FAILED:
                    $this->failed($transaction, $order);
                    break;
                case \WhiteLabelMachineName\Sdk\Model\TransactionState::FULFILL:
                    $this->authorize($transaction, $order);
                    $this->fulfill($transaction, $order);
                    break;
                case \WhiteLabelMachineName\Sdk\Model\TransactionState::VOIDED:
                    $this->voided($transaction, $order);
                    break;
                case \WhiteLabelMachineName\Sdk\Model\TransactionState::COMPLETED:
                    $this->waiting($transaction, $order);
                    break;
                default:
                    // Nothing to do.
                    break;
            }
        }
    }

    protected function authorize(\WhiteLabelMachineName\Sdk\Model\Transaction $transaction, Order $sourceOrder)
    {
        if (WhiteLabelMachineNameHelper::getOrderMeta($sourceOrder, 'authorized')) {
            return;
        }
        // Do not send emails for this status update
        WhiteLabelMachineNameBasemodule::startRecordingMailMessages();
        WhiteLabelMachineNameHelper::updateOrderMeta($sourceOrder, 'authorized', true);
        $authorizedStatusId = Configuration::get(WhiteLabelMachineNameBasemodule::CK_STATUS_AUTHORIZED);
        $orders = $sourceOrder->getBrother();
        $orders[] = $sourceOrder;
        foreach ($orders as $order) {
            $order->setCurrentState($authorizedStatusId);
            $order->save();
        }
        WhiteLabelMachineNameBasemodule::stopRecordingMailMessages();
        if (Configuration::get(WhiteLabelMachineNameBasemodule::CK_MAIL, null, null, $sourceOrder->id_shop)) {
            // Send stored messages
            $messages = WhiteLabelMachineNameHelper::getOrderEmails($sourceOrder);
            if (count($messages) > 0) {
                if (method_exists('Mail', 'sendMailMessageWithoutHook')) {
                    foreach ($messages as $message) {
                        Mail::sendMailMessageWithoutHook($message, false);
                    }
                }
            }
        }
        WhiteLabelMachineNameHelper::deleteOrderEmails($order);
        // Cleanup carts
        $originalCartId = WhiteLabelMachineNameHelper::getOrderMeta($order, 'originalCart');
        if (! empty($originalCartId)) {
            $cart = new Cart($originalCartId);
            $cart->delete();
        }
        WhiteLabelMachineNameServiceTransaction::instance()->updateTransactionInfo($transaction, $sourceOrder);
    }

    protected function waiting(\WhiteLabelMachineName\Sdk\Model\Transaction $transaction, Order $sourceOrder)
    {
        WhiteLabelMachineNameBasemodule::startRecordingMailMessages();
        $waitingStatusId = Configuration::get(WhiteLabelMachineNameBasemodule::CK_STATUS_COMPLETED);
        if (! WhiteLabelMachineNameHelper::getOrderMeta($sourceOrder, 'manual_check')) {
            $orders = $sourceOrder->getBrother();
            $orders[] = $sourceOrder;
            foreach ($orders as $order) {
                $order->setCurrentState($waitingStatusId);
                $order->save();
            }
        }
        WhiteLabelMachineNameBasemodule::stopRecordingMailMessages();
        WhiteLabelMachineNameServiceTransaction::instance()->updateTransactionInfo($transaction, $sourceOrder);
    }

    protected function decline(\WhiteLabelMachineName\Sdk\Model\Transaction $transaction, Order $sourceOrder)
    {
        if (! Configuration::get(WhiteLabelMachineNameBasemodule::CK_MAIL, null, null, $sourceOrder->id_shop)) {
            // Do not send email
            WhiteLabelMachineNameBasemodule::startRecordingMailMessages();
        }

        $canceledStatusId = Configuration::get(WhiteLabelMachineNameBasemodule::CK_STATUS_DECLINED);
        $orders = $sourceOrder->getBrother();
        $orders[] = $sourceOrder;
        foreach ($orders as $order) {
            $order->setCurrentState($canceledStatusId);
            $order->save();
        }
        WhiteLabelMachineNameBasemodule::stopRecordingMailMessages();
        WhiteLabelMachineNameServiceTransaction::instance()->updateTransactionInfo($transaction, $sourceOrder);
    }

    protected function failed(\WhiteLabelMachineName\Sdk\Model\Transaction $transaction, Order $sourceOrder)
    {
        // Do not send email
        WhiteLabelMachineNameBasemodule::startRecordingMailMessages();
        $errorStatusId = Configuration::get(WhiteLabelMachineNameBasemodule::CK_STATUS_FAILED);
        $orders = $sourceOrder->getBrother();
        $orders[] = $sourceOrder;
        foreach ($orders as $order) {
            $order->setCurrentState($errorStatusId);
            $order->save();
        }
        WhiteLabelMachineNameBasemodule::stopRecordingMailMessages();
        WhiteLabelMachineNameHelper::deleteOrderEmails($sourceOrder);
        WhiteLabelMachineNameServiceTransaction::instance()->updateTransactionInfo($transaction, $sourceOrder);
    }

    protected function fulfill(\WhiteLabelMachineName\Sdk\Model\Transaction $transaction, Order $sourceOrder)
    {
        if (! Configuration::get(WhiteLabelMachineNameBasemodule::CK_MAIL, null, null, $sourceOrder->id_shop)) {
            // Do not send email
            WhiteLabelMachineNameBasemodule::startRecordingMailMessages();
        }
        $payedStatusId = Configuration::get(WhiteLabelMachineNameBasemodule::CK_STATUS_FULFILL);
        $orders = $sourceOrder->getBrother();
        $orders[] = $sourceOrder;
        foreach ($orders as $order) {
            $order->setCurrentState($payedStatusId);
            if (empty($order->invoice_date) || $order->invoice_date == '0000-00-00 00:00:00') {
                // Make sure invoice date is set, otherwise prestashop ignores the order in the statistics
                $order->invoice_date = date('Y-m-d H:i:s');
            }
            $order->save();
        }
        WhiteLabelMachineNameBasemodule::stopRecordingMailMessages();
        WhiteLabelMachineNameServiceTransaction::instance()->updateTransactionInfo($transaction, $sourceOrder);
    }

    protected function voided(\WhiteLabelMachineName\Sdk\Model\Transaction $transaction, Order $sourceOrder)
    {
        if (! Configuration::get(WhiteLabelMachineNameBasemodule::CK_MAIL, null, null, $sourceOrder->id_shop)) {
            // Do not send email
            WhiteLabelMachineNameBasemodule::startRecordingMailMessages();
        }
        $canceledStatusId = Configuration::get(WhiteLabelMachineNameBasemodule::CK_STATUS_VOIDED);
        $orders = $sourceOrder->getBrother();
        $orders[] = $sourceOrder;
        foreach ($orders as $order) {
            $order->setCurrentState($canceledStatusId);
            $order->save();
        }
        WhiteLabelMachineNameBasemodule::stopRecordingMailMessages();
        WhiteLabelMachineNameServiceTransaction::instance()->updateTransactionInfo($transaction, $sourceOrder);
    }
}
