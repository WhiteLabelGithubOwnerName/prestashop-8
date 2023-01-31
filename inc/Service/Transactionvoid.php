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
 * This service provides functions to deal with WhiteLabelName transaction voids.
 */
class WhiteLabelMachineNameServiceTransactionvoid extends WhiteLabelMachineNameServiceAbstract
{

    /**
     * The transaction void API service.
     *
     * @var \WhiteLabelMachineName\Sdk\Service\TransactionVoidService
     */
    private $voidService;

    public function executeVoid($order)
    {
        $currentVoidId = null;
        try {
            WhiteLabelMachineNameHelper::startDBTransaction();
            $transactionInfo = WhiteLabelMachineNameHelper::getTransactionInfoForOrder($order);
            if ($transactionInfo === null) {
                throw new Exception(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Could not load corresponding transaction.',
                        'transactionvoid'
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
                        'The transaction is not in a state to be voided.',
                        'transactionvoid'
                    )
                );
            }
            if (WhiteLabelMachineNameModelVoidjob::isVoidRunningForTransaction($spaceId, $transactionId)) {
                throw new Exception(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Please wait until the existing void is processed.',
                        'transactionvoid'
                    )
                );
            }
            if (WhiteLabelMachineNameModelCompletionjob::isCompletionRunningForTransaction($spaceId, $transactionId)) {
                throw new Exception(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'There is a completion in process. The order can not be voided.',
                        'transactionvoid'
                    )
                );
            }

            $voidJob = new WhiteLabelMachineNameModelVoidjob();
            $voidJob->setSpaceId($spaceId);
            $voidJob->setTransactionId($transactionId);
            $voidJob->setState(WhiteLabelMachineNameModelVoidjob::STATE_CREATED);
            $voidJob->setOrderId(
                WhiteLabelMachineNameHelper::getOrderMeta($order, 'whiteLabelMachineNameMainOrderId')
            );
            $voidJob->save();
            $currentVoidId = $voidJob->getId();
            WhiteLabelMachineNameHelper::commitDBTransaction();
        } catch (Exception $e) {
            WhiteLabelMachineNameHelper::rollbackDBTransaction();
            throw $e;
        }
        $this->sendVoid($currentVoidId);
    }

    protected function sendVoid($voidJobId)
    {
        $voidJob = new WhiteLabelMachineNameModelVoidjob($voidJobId);
        WhiteLabelMachineNameHelper::startDBTransaction();
        WhiteLabelMachineNameHelper::lockByTransactionId($voidJob->getSpaceId(), $voidJob->getTransactionId());
        // Reload void job;
        $voidJob = new WhiteLabelMachineNameModelVoidjob($voidJobId);
        if ($voidJob->getState() != WhiteLabelMachineNameModelVoidjob::STATE_CREATED) {
            // Already sent in the meantime
            WhiteLabelMachineNameHelper::rollbackDBTransaction();
            return;
        }
        try {
            $void = $this->getVoidService()->voidOnline($voidJob->getSpaceId(), $voidJob->getTransactionId());
            $voidJob->setVoidId($void->getId());
            $voidJob->setState(WhiteLabelMachineNameModelVoidjob::STATE_SENT);
            $voidJob->save();
            WhiteLabelMachineNameHelper::commitDBTransaction();
        } catch (\WhiteLabelMachineName\Sdk\ApiException $e) {
            if ($e->getResponseObject() instanceof \WhiteLabelMachineName\Sdk\Model\ClientError) {
                $voidJob->setFailureReason(
                    array(
                        'en-US' => sprintf(
                            WhiteLabelMachineNameHelper::getModuleInstance()->l(
                                'Could not send the void to %s. Error: %s',
                                'transactionvoid'
                            ),
                            'WhiteLabelName',
                            WhiteLabelMachineNameHelper::cleanExceptionMessage($e->getMessage())
                        )
                    )
                );
                $voidJob->setState(WhiteLabelMachineNameModelVoidjob::STATE_FAILURE);
                $voidJob->save();
                WhiteLabelMachineNameHelper::commitDBTransaction();
            } else {
                $voidJob->save();
                WhiteLabelMachineNameHelper::commitDBTransaction();
                $message = sprintf(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Error sending void job with id %d: %s',
                        'transactionvoid'
                    ),
                    $voidJobId,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'WhiteLabelMachineNameModelVoidjob');
                throw $e;
            }
        } catch (Exception $e) {
            $voidJob->save();
            WhiteLabelMachineNameHelper::commitDBTransaction();
            $message = sprintf(
                WhiteLabelMachineNameHelper::getModuleInstance()->l(
                    'Error sending void job with id %d: %s',
                    'transactionvoid'
                ),
                $voidJobId,
                $e->getMessage()
            );
            PrestaShopLogger::addLog($message, 3, null, 'WhiteLabelMachineNameModelVoidjob');
            throw $e;
        }
    }

    public function updateForOrder($order)
    {
        $transactionInfo = WhiteLabelMachineNameHelper::getTransactionInfoForOrder($order);
        $spaceId = $transactionInfo->getSpaceId();
        $transactionId = $transactionInfo->getTransactionId();
        $voidJob = WhiteLabelMachineNameModelVoidjob::loadRunningVoidForTransaction($spaceId, $transactionId);
        if ($voidJob->getState() == WhiteLabelMachineNameModelVoidjob::STATE_CREATED) {
            $this->sendVoid($voidJob->getId());
        }
    }

    public function updateVoids($endTime = null)
    {
        $toProcess = WhiteLabelMachineNameModelVoidjob::loadNotSentJobIds();

        foreach ($toProcess as $id) {
            if ($endTime !== null && time() + 15 > $endTime) {
                return;
            }
            try {
                $this->sendVoid($id);
            } catch (Exception $e) {
                $message = sprintf(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Error updating void job with id %d: %s',
                        'transactionvoid'
                    ),
                    $id,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'WhiteLabelMachineNameModelVoidjob');
            }
        }
    }

    public function hasPendingVoids()
    {
        $toProcess = WhiteLabelMachineNameModelVoidjob::loadNotSentJobIds();
        return ! empty($toProcess);
    }

    /**
     * Returns the transaction void API service.
     *
     * @return \WhiteLabelMachineName\Sdk\Service\TransactionVoidService
     */
    protected function getVoidService()
    {
        if ($this->voidService == null) {
            $this->voidService = new \WhiteLabelMachineName\Sdk\Service\TransactionVoidService(
                WhiteLabelMachineNameHelper::getApiClient()
            );
        }

        return $this->voidService;
    }
}
