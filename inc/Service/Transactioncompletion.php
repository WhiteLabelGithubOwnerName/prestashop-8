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
 * This service provides functions to deal with WhiteLabelName transaction completions.
 */
class WhiteLabelMachineNameServiceTransactioncompletion extends WhiteLabelMachineNameServiceAbstract
{

    /**
     * The transaction completion API service.
     *
     * @var \WhiteLabelMachineName\Sdk\Service\TransactionCompletionService
     */
    private $completionService;

    public function executeCompletion($order)
    {
        $currentCompletionJob = null;
        try {
            WhiteLabelMachineNameHelper::startDBTransaction();
            $transactionInfo = WhiteLabelMachineNameHelper::getTransactionInfoForOrder($order);
            if ($transactionInfo === null) {
                throw new Exception(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Could not load corresponding transaction.',
                        'transactioncompletion'
                    )
                );
            }

            WhiteLabelMachineNameHelper::lockByTransactionId(
                $transactionInfo->getSpaceId(),
                $transactionInfo->getTransactionId()
            );
            // Reload after locking
            $transactionInfo = WhiteLabelMachineNameModelTransactioninfo::loadByTransaction(
                $transactionInfo->getSpaceId(),
                $transactionInfo->getTransactionId()
            );
            $spaceId = $transactionInfo->getSpaceId();
            $transactionId = $transactionInfo->getTransactionId();

            if ($transactionInfo->getState() != \WhiteLabelMachineName\Sdk\Model\TransactionState::AUTHORIZED) {
                throw new Exception(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'The transaction is not in a state to be completed.',
                        'transactioncompletion'
                    )
                );
            }

            if (WhiteLabelMachineNameModelCompletionjob::isCompletionRunningForTransaction($spaceId, $transactionId)) {
                throw new Exception(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Please wait until the existing completion is processed.',
                        'transactioncompletion'
                    )
                );
            }

            if (WhiteLabelMachineNameModelVoidjob::isVoidRunningForTransaction($spaceId, $transactionId)) {
                throw new Exception(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'There is a void in process. The order can not be completed.',
                        'transactioncompletion'
                    )
                );
            }

            $completionJob = new WhiteLabelMachineNameModelCompletionjob();
            $completionJob->setSpaceId($spaceId);
            $completionJob->setTransactionId($transactionId);
            $completionJob->setState(WhiteLabelMachineNameModelCompletionjob::STATE_CREATED);
            $completionJob->setOrderId(
                WhiteLabelMachineNameHelper::getOrderMeta($order, 'whiteLabelMachineNameMainOrderId')
            );
            $completionJob->save();
            $currentCompletionJob = $completionJob->getId();
            WhiteLabelMachineNameHelper::commitDBTransaction();
        } catch (Exception $e) {
            WhiteLabelMachineNameHelper::rollbackDBTransaction();
            throw $e;
        }

        try {
            $this->updateLineItems($currentCompletionJob);
            $this->sendCompletion($currentCompletionJob);
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function updateLineItems($completionJobId)
    {
        $completionJob = new WhiteLabelMachineNameModelCompletionjob($completionJobId);
        WhiteLabelMachineNameHelper::startDBTransaction();
        WhiteLabelMachineNameHelper::lockByTransactionId(
            $completionJob->getSpaceId(),
            $completionJob->getTransactionId()
        );
        // Reload completion job;
        $completionJob = new WhiteLabelMachineNameModelCompletionjob($completionJobId);

        if ($completionJob->getState() != WhiteLabelMachineNameModelCompletionjob::STATE_CREATED) {
            // Already updated in the meantime
            WhiteLabelMachineNameHelper::rollbackDBTransaction();
            return;
        }
        try {
            $baseOrder = new Order($completionJob->getOrderId());
            $collected = $baseOrder->getBrother()->getResults();
            $collected[] = $baseOrder;

            $lineItems = WhiteLabelMachineNameServiceLineitem::instance()->getItemsFromOrders($collected);
            WhiteLabelMachineNameServiceTransaction::instance()->updateLineItems(
                $completionJob->getSpaceId(),
                $completionJob->getTransactionId(),
                $lineItems
            );
            $completionJob->setState(WhiteLabelMachineNameModelCompletionjob::STATE_ITEMS_UPDATED);
            $completionJob->save();
            WhiteLabelMachineNameHelper::commitDBTransaction();
        } catch (\WhiteLabelMachineName\Sdk\ApiException $e) {
            if ($e->getResponseObject() instanceof \WhiteLabelMachineName\Sdk\Model\ClientError) {
                $completionJob->setFailureReason(
                    array(
                        'en-US' => sprintf(
                            WhiteLabelMachineNameHelper::getModuleInstance()->l(
                                'Could not update the line items. Error: %s',
                                'transactioncompletion'
                            ),
                            WhiteLabelMachineNameHelper::cleanExceptionMessage($e->getMessage())
                        )
                    )
                );
                $completionJob->setState(WhiteLabelMachineNameModelCompletionjob::STATE_FAILURE);
                $completionJob->save();
                WhiteLabelMachineNameHelper::commitDBTransaction();
            } else {
                $completionJob->save();
                WhiteLabelMachineNameHelper::commitDBTransaction();
                $message = sprintf(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Error updating line items for completion job with id %d: %s',
                        'transactioncompletion'
                    ),
                    $completionJobId,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'WhiteLabelMachineNameModelCompletionjob');
                throw $e;
            }
        } catch (Exception $e) {
            $completionJob->save();
            WhiteLabelMachineNameHelper::commitDBTransaction();
            $message = sprintf(
                WhiteLabelMachineNameHelper::getModuleInstance()->l(
                    'Error updating line items for completion job with id %d: %s',
                    'transactioncompletion'
                ),
                $completionJobId,
                $e->getMessage()
            );
            PrestaShopLogger::addLog($message, 3, null, 'WhiteLabelMachineNameModelCompletionjob');
            throw $e;
        }
    }

    protected function sendCompletion($completionJobId)
    {
        $completionJob = new WhiteLabelMachineNameModelCompletionjob($completionJobId);
        WhiteLabelMachineNameHelper::startDBTransaction();
        WhiteLabelMachineNameHelper::lockByTransactionId(
            $completionJob->getSpaceId(),
            $completionJob->getTransactionId()
        );
        // Reload completion job;
        $completionJob = new WhiteLabelMachineNameModelCompletionjob($completionJobId);

        if ($completionJob->getState() != WhiteLabelMachineNameModelCompletionjob::STATE_ITEMS_UPDATED) {
            // Already sent in the meantime
            WhiteLabelMachineNameHelper::rollbackDBTransaction();
            return;
        }
        try {
            $completion = $this->getCompletionService()->completeOnline(
                $completionJob->getSpaceId(),
                $completionJob->getTransactionId()
            );
            $completionJob->setCompletionId($completion->getId());
            $completionJob->setState(WhiteLabelMachineNameModelCompletionjob::STATE_SENT);
            $completionJob->save();
            WhiteLabelMachineNameHelper::commitDBTransaction();
        } catch (\WhiteLabelMachineName\Sdk\ApiException $e) {
            if ($e->getResponseObject() instanceof \WhiteLabelMachineName\Sdk\Model\ClientError) {
                $completionJob->setFailureReason(
                    array(
                        'en-US' => sprintf(
                            WhiteLabelMachineNameHelper::getModuleInstance()->l(
                                'Could not send the completion to %s. Error: %s',
                                'transactioncompletion'
                            ),
                            'WhiteLabelName',
                            WhiteLabelMachineNameHelper::cleanExceptionMessage($e->getMessage())
                        )
                    )
                );
                $completionJob->setState(WhiteLabelMachineNameModelCompletionjob::STATE_FAILURE);
                $completionJob->save();
                WhiteLabelMachineNameHelper::commitDBTransaction();
            } else {
                $completionJob->save();
                WhiteLabelMachineNameHelper::commitDBTransaction();
                $message = sprintf(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Error sending completion job with id %d: %s',
                        'transactioncompletion'
                    ),
                    $completionJobId,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'WhiteLabelMachineNameModelCompletionjob');
                throw $e;
            }
        } catch (Exception $e) {
            $completionJob->save();
            WhiteLabelMachineNameHelper::commitDBTransaction();
            $message = sprintf(
                WhiteLabelMachineNameHelper::getModuleInstance()->l(
                    'Error sending completion job with id %d: %s',
                    'transactioncompletion'
                ),
                $completionJobId,
                $e->getMessage()
            );
            PrestaShopLogger::addLog($message, 3, null, 'WhiteLabelMachineNameModelCompletionjob');
            throw $e;
        }
    }

    public function updateForOrder($order)
    {
        $transactionInfo = WhiteLabelMachineNameHelper::getTransactionInfoForOrder($order);
        $spaceId = $transactionInfo->getSpaceId();
        $transactionId = $transactionInfo->getTransactionId();
        $completionJob = WhiteLabelMachineNameModelCompletionjob::loadRunningCompletionForTransaction(
            $spaceId,
            $transactionId
        );
        $this->updateLineItems($completionJob->getId());
        $this->sendCompletion($completionJob->getId());
    }

    public function updateCompletions($endTime = null)
    {
        $toProcess = WhiteLabelMachineNameModelCompletionjob::loadNotSentJobIds();
        foreach ($toProcess as $id) {
            if ($endTime !== null && time() + 15 > $endTime) {
                return;
            }
            try {
                $this->updateLineItems($id);
                $this->sendCompletion($id);
            } catch (Exception $e) {
                $message = sprintf(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Error updating completion job with id %d: %s',
                        'transactioncompletion'
                    ),
                    $id,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'WhiteLabelMachineNameModelCompletionjob');
            }
        }
    }

    public function hasPendingCompletions()
    {
        $toProcess = WhiteLabelMachineNameModelCompletionjob::loadNotSentJobIds();
        return ! empty($toProcess);
    }

    /**
     * Returns the transaction completion API service.
     *
     * @return \WhiteLabelMachineName\Sdk\Service\TransactionCompletionService
     */
    protected function getCompletionService()
    {
        if ($this->completionService == null) {
            $this->completionService = new \WhiteLabelMachineName\Sdk\Service\TransactionCompletionService(
                WhiteLabelMachineNameHelper::getApiClient()
            );
        }
        return $this->completionService;
    }
}
