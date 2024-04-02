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
 * This service provides functions to deal with WhiteLabelName refunds.
 */
class WhiteLabelMachineNameServiceRefund extends WhiteLabelMachineNameServiceAbstract
{
    private static $refundableStates = array(
        \WhiteLabelMachineName\Sdk\Model\TransactionState::COMPLETED,
        \WhiteLabelMachineName\Sdk\Model\TransactionState::DECLINE,
        \WhiteLabelMachineName\Sdk\Model\TransactionState::FULFILL
    );

    /**
     * The refund API service.
     *
     * @var \WhiteLabelMachineName\Sdk\Service\RefundService
     */
    private $refundService;

    /**
     * Returns the refund by the given external id.
     *
     * @param int $spaceId
     * @param string $externalId
     * @return \WhiteLabelMachineName\Sdk\Model\Refund
     */
    public function getRefundByExternalId($spaceId, $externalId)
    {
        $query = new \WhiteLabelMachineName\Sdk\Model\EntityQuery();
        $query->setFilter($this->createEntityFilter('externalId', $externalId));
        $query->setNumberOfEntities(1);
        $result = $this->getRefundService()->search($spaceId, $query);
        if ($result != null && ! empty($result)) {
            return current($result);
        } else {
            throw new Exception('The refund could not be found.');
        }
    }

    public function executeRefund(Order $order, array $parsedParameters)
    {
        $currentRefundJob = null;
        try {
            WhiteLabelMachineNameHelper::startDBTransaction();
            $transactionInfo = WhiteLabelMachineNameHelper::getTransactionInfoForOrder($order);
            if ($transactionInfo === null) {
                throw new Exception(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Could not load corresponding transaction',
                        'refund'
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

            if (! in_array($transactionInfo->getState(), self::$refundableStates)) {
                throw new Exception(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'The transaction is not in a state to be refunded.',
                        'refund'
                    )
                );
            }

            if (WhiteLabelMachineNameModelRefundjob::isRefundRunningForTransaction($spaceId, $transactionId)) {
                throw new Exception(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Please wait until the existing refund is processed.',
                        'refund'
                    )
                );
            }

            $refundJob = new WhiteLabelMachineNameModelRefundjob();
            $refundJob->setState(WhiteLabelMachineNameModelRefundjob::STATE_CREATED);
            $refundJob->setOrderId($order->id);
            $refundJob->setSpaceId($transactionInfo->getSpaceId());
            $refundJob->setTransactionId($transactionInfo->getTransactionId());
            $refundJob->setExternalId(uniqid($order->id . '-'));
            $refundJob->setRefundParameters($parsedParameters);
            $refundJob->save();
            // validate Refund Job
            $this->createRefundObject($refundJob);
            $currentRefundJob = $refundJob->getId();
            WhiteLabelMachineNameHelper::commitDBTransaction();
        } catch (Exception $e) {
            WhiteLabelMachineNameHelper::rollbackDBTransaction();
            throw $e;
        }
        $this->sendRefund($currentRefundJob);
    }

    protected function sendRefund($refundJobId)
    {
        $refundJob = new WhiteLabelMachineNameModelRefundjob($refundJobId);
        WhiteLabelMachineNameHelper::startDBTransaction();
        WhiteLabelMachineNameHelper::lockByTransactionId($refundJob->getSpaceId(), $refundJob->getTransactionId());
        // Reload refund job;
        $refundJob = new WhiteLabelMachineNameModelRefundjob($refundJobId);
        if ($refundJob->getState() != WhiteLabelMachineNameModelRefundjob::STATE_CREATED) {
            // Already sent in the meantime
            WhiteLabelMachineNameHelper::rollbackDBTransaction();
            return;
        }
        try {
            $executedRefund = $this->refund($refundJob->getSpaceId(), $this->createRefundObject($refundJob));
            $refundJob->setState(WhiteLabelMachineNameModelRefundjob::STATE_SENT);
            $refundJob->setRefundId($executedRefund->getId());

            if ($executedRefund->getState() == \WhiteLabelMachineName\Sdk\Model\RefundState::PENDING) {
                $refundJob->setState(WhiteLabelMachineNameModelRefundjob::STATE_PENDING);
            }
            $refundJob->save();
            WhiteLabelMachineNameHelper::commitDBTransaction();
        } catch (\WhiteLabelMachineName\Sdk\ApiException $e) {
            if ($e->getResponseObject() instanceof \WhiteLabelMachineName\Sdk\Model\ClientError) {
                $refundJob->setFailureReason(
                    array(
                        'en-US' => sprintf(
                            WhiteLabelMachineNameHelper::getModuleInstance()->l(
                                'Could not send the refund to %s. Error: %s',
                                'refund'
                            ),
                            'WhiteLabelName',
                            WhiteLabelMachineNameHelper::cleanExceptionMessage($e->getMessage())
                        )
                    )
                );
                $refundJob->setState(WhiteLabelMachineNameModelRefundjob::STATE_FAILURE);
                $refundJob->save();
                WhiteLabelMachineNameHelper::commitDBTransaction();
            } else {
                $refundJob->save();
                WhiteLabelMachineNameHelper::commitDBTransaction();
                $message = sprintf(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Error sending refund job with id %d: %s',
                        'refund'
                    ),
                    $refundJobId,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'WhiteLabelMachineNameModelRefundjob');
                throw $e;
            }
        } catch (Exception $e) {
            $refundJob->save();
            WhiteLabelMachineNameHelper::commitDBTransaction();
            $message = sprintf(
                WhiteLabelMachineNameHelper::getModuleInstance()->l('Error sending refund job with id %d: %s', 'refund'),
                $refundJobId,
                $e->getMessage()
            );
            PrestaShopLogger::addLog($message, 3, null, 'WhiteLabelMachineNameModelRefundjob');
            throw $e;
        }
    }

    public function applyRefundToShop($refundJobId)
    {
        $refundJob = new WhiteLabelMachineNameModelRefundjob($refundJobId);
        WhiteLabelMachineNameHelper::startDBTransaction();
        WhiteLabelMachineNameHelper::lockByTransactionId($refundJob->getSpaceId(), $refundJob->getTransactionId());
        // Reload refund job;
        $refundJob = new WhiteLabelMachineNameModelRefundjob($refundJobId);
        if ($refundJob->getState() != WhiteLabelMachineNameModelRefundjob::STATE_APPLY) {
            // Already processed in the meantime
            WhiteLabelMachineNameHelper::rollbackDBTransaction();
            return;
        }
        try {
            $order = new Order($refundJob->getOrderId());
            $strategy = WhiteLabelMachineNameBackendStrategyprovider::getStrategy();
            $appliedData = $strategy->applyRefund($order, $refundJob->getRefundParameters());
            $refundJob->setState(WhiteLabelMachineNameModelRefundjob::STATE_SUCCESS);
            $refundJob->save();
            WhiteLabelMachineNameHelper::commitDBTransaction();
            try {
                $strategy->afterApplyRefundActions($order, $refundJob->getRefundParameters(), $appliedData);
            } catch (Exception $e) {
                // We ignore errors in the after apply actions
            }
        } catch (Exception $e) {
            WhiteLabelMachineNameHelper::rollbackDBTransaction();
            WhiteLabelMachineNameHelper::startDBTransaction();
            WhiteLabelMachineNameHelper::lockByTransactionId($refundJob->getSpaceId(), $refundJob->getTransactionId());
            $refundJob = new WhiteLabelMachineNameModelRefundjob($refundJobId);
            $refundJob->increaseApplyTries();
            if ($refundJob->getApplyTries() > 3) {
                $refundJob->setState(WhiteLabelMachineNameModelRefundjob::STATE_FAILURE);
                $refundJob->setFailureReason(array(
                    'en-US' => sprintf($e->getMessage())
                ));
            }
            $refundJob->save();
            WhiteLabelMachineNameHelper::commitDBTransaction();
        }
    }

    public function updateForOrder($order)
    {
        $transactionInfo = WhiteLabelMachineNameHelper::getTransactionInfoForOrder($order);
        $spaceId = $transactionInfo->getSpaceId();
        $transactionId = $transactionInfo->getTransactionId();
        $refundJob = WhiteLabelMachineNameModelRefundjob::loadRunningRefundForTransaction($spaceId, $transactionId);
        if ($refundJob->getState() == WhiteLabelMachineNameModelRefundjob::STATE_CREATED) {
            $this->sendRefund($refundJob->getId());
        } elseif ($refundJob->getState() == WhiteLabelMachineNameModelRefundjob::STATE_APPLY) {
            $this->applyRefundToShop($refundJob->getId());
        }
    }

    public function updateRefunds($endTime = null)
    {
        $toSend = WhiteLabelMachineNameModelRefundjob::loadNotSentJobIds();
        foreach ($toSend as $id) {
            if ($endTime !== null && time() + 15 > $endTime) {
                return;
            }
            try {
                $this->sendRefund($id);
            } catch (Exception $e) {
                $message = sprintf(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Error updating refund job with id %d: %s',
                        'refund'
                    ),
                    $id,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'WhiteLabelMachineNameModelRefundjob');
            }
        }
        $toApply = WhiteLabelMachineNameModelRefundjob::loadNotAppliedJobIds();
        foreach ($toApply as $id) {
            if ($endTime !== null && time() + 15 > $endTime) {
                return;
            }
            try {
                $this->applyRefundToShop($id);
            } catch (Exception $e) {
                $message = sprintf(
                    WhiteLabelMachineNameHelper::getModuleInstance()->l(
                        'Error applying refund job with id %d: %s',
                        'refund'
                    ),
                    $id,
                    $e->getMessage()
                );
                PrestaShopLogger::addLog($message, 3, null, 'WhiteLabelMachineNameModelRefundjob');
            }
        }
    }

    public function hasPendingRefunds()
    {
        $toSend = WhiteLabelMachineNameModelRefundjob::loadNotSentJobIds();
        $toApply = WhiteLabelMachineNameModelRefundjob::loadNotAppliedJobIds();
        return ! empty($toSend) || ! empty($toApply);
    }

    /**
     * Creates a refund request model for the given parameters.
     *
     * @param Order $order
     * @param array $refund
     *            Refund data to be determined
     * @return \WhiteLabelMachineName\Sdk\Model\RefundCreate
     */
    protected function createRefundObject(WhiteLabelMachineNameModelRefundjob $refundJob)
    {
        $order = new Order($refundJob->getOrderId());

        $strategy = WhiteLabelMachineNameBackendStrategyprovider::getStrategy();

        $spaceId = $refundJob->getSpaceId();
        $transactionId = $refundJob->getTransactionId();
        $externalRefundId = $refundJob->getExternalId();
        $parsedData = $refundJob->getRefundParameters();
        $amount = $strategy->getRefundTotal($parsedData);
        $type = $strategy->getWhiteLabelMachineNameRefundType($parsedData);

        $reductions = $strategy->createReductions($order, $parsedData);
        $reductions = $this->fixReductions($amount, $spaceId, $transactionId, $reductions);

        $remoteRefund = new \WhiteLabelMachineName\Sdk\Model\RefundCreate();
        $remoteRefund->setExternalId($externalRefundId);
        $remoteRefund->setReductions($reductions);
        $remoteRefund->setTransaction($transactionId);
        $remoteRefund->setType($type);

        return $remoteRefund;
    }

    /**
     * Returns the fixed line item reductions for the refund.
     *
     * If the amount of the given reductions does not match the refund's grand total, the amount to refund is
     * distributed equally to the line items.
     *
     * @param float $refundTotal
     * @param int $spaceId
     * @param int $transactionId
     * @param \WhiteLabelMachineName\Sdk\Model\LineItemReductionCreate[] $reductions
     * @return \WhiteLabelMachineName\Sdk\Model\LineItemReductionCreate[]
     */
    protected function fixReductions($refundTotal, $spaceId, $transactionId, array $reductions)
    {
        $baseLineItems = $this->getBaseLineItems($spaceId, $transactionId);
        $reductionAmount = WhiteLabelMachineNameHelper::getReductionAmount($baseLineItems, $reductions);

        $configuration = WhiteLabelMachineNameVersionadapter::getConfigurationInterface();
        $computePrecision = $configuration->get('_PS_PRICE_COMPUTE_PRECISION_');

        if (Tools::ps_round($refundTotal, $computePrecision) != Tools::ps_round($reductionAmount, $computePrecision)) {
            $fixedReductions = array();
            $baseAmount = WhiteLabelMachineNameHelper::getTotalAmountIncludingTax($baseLineItems);
            $rate = $refundTotal / $baseAmount;
            foreach ($baseLineItems as $lineItem) {
                $reduction = new \WhiteLabelMachineName\Sdk\Model\LineItemReductionCreate();
                $reduction->setLineItemUniqueId($lineItem->getUniqueId());
                $reduction->setQuantityReduction(0);
                $reduction->setUnitPriceReduction(
                    round($lineItem->getAmountIncludingTax() * $rate / $lineItem->getQuantity(), 8)
                );
                $fixedReductions[] = $reduction;
            }

            return $fixedReductions;
        } else {
            return $reductions;
        }
    }

    /**
     * Sends the refund to the gateway.
     *
     * @param int $spaceId
     * @param \WhiteLabelMachineName\Sdk\Model\RefundCreate $refund
     * @return \WhiteLabelMachineName\Sdk\Model\Refund
     */
    public function refund($spaceId, \WhiteLabelMachineName\Sdk\Model\RefundCreate $refund)
    {
        return $this->getRefundService()->refund($spaceId, $refund);
    }

    /**
     * Returns the line items that are to be used to calculate the refund.
     *
     * This returns the line items of the latest refund if there is one or else of the completed transaction.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @param \WhiteLabelMachineName\Sdk\Model\Refund $refund
     * @return \WhiteLabelMachineName\Sdk\Model\LineItem[]
     */
    protected function getBaseLineItems($spaceId, $transactionId, \WhiteLabelMachineName\Sdk\Model\Refund $refund = null)
    {
        $lastSuccessfulRefund = $this->getLastSuccessfulRefund($spaceId, $transactionId, $refund);
        if ($lastSuccessfulRefund) {
            return $lastSuccessfulRefund->getReducedLineItems();
        } else {
            return $this->getTransactionInvoice($spaceId, $transactionId)->getLineItems();
        }
    }

    /**
     * Returns the transaction invoice for the given transaction.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @throws Exception
     * @return \WhiteLabelMachineName\Sdk\Model\TransactionInvoice
     */
    protected function getTransactionInvoice($spaceId, $transactionId)
    {
        $query = new \WhiteLabelMachineName\Sdk\Model\EntityQuery();

        $filter = new \WhiteLabelMachineName\Sdk\Model\EntityQueryFilter();
        $filter->setType(\WhiteLabelMachineName\Sdk\Model\EntityQueryFilterType::_AND);
        $filter->setChildren(
            array(
                $this->createEntityFilter(
                    'state',
                    \WhiteLabelMachineName\Sdk\Model\TransactionInvoiceState::CANCELED,
                    \WhiteLabelMachineName\Sdk\Model\CriteriaOperator::NOT_EQUALS
                ),
                $this->createEntityFilter('completion.lineItemVersion.transaction.id', $transactionId)
            )
        );
        $query->setFilter($filter);
        $query->setNumberOfEntities(1);
        $invoiceService = new \WhiteLabelMachineName\Sdk\Service\TransactionInvoiceService(
            WhiteLabelMachineNameHelper::getApiClient()
        );
        $result = $invoiceService->search($spaceId, $query);
        if (! empty($result)) {
            return $result[0];
        } else {
            throw new Exception('The transaction invoice could not be found.');
        }
    }

    /**
     * Returns the last successful refund of the given transaction, excluding the given refund.
     *
     * @param int $spaceId
     * @param int $transactionId
     * @param \WhiteLabelMachineName\Sdk\Model\Refund $refund
     * @return \WhiteLabelMachineName\Sdk\Model\Refund
     */
    protected function getLastSuccessfulRefund(
        $spaceId,
        $transactionId,
        \WhiteLabelMachineName\Sdk\Model\Refund $refund = null
    ) {
        $query = new \WhiteLabelMachineName\Sdk\Model\EntityQuery();

        $filter = new \WhiteLabelMachineName\Sdk\Model\EntityQueryFilter();
        $filter->setType(\WhiteLabelMachineName\Sdk\Model\EntityQueryFilterType::_AND);
        $filters = array(
            $this->createEntityFilter('state', \WhiteLabelMachineName\Sdk\Model\RefundState::SUCCESSFUL),
            $this->createEntityFilter('transaction.id', $transactionId)
        );
        if ($refund != null) {
            $filters[] = $this->createEntityFilter(
                'id',
                $refund->getId(),
                \WhiteLabelMachineName\Sdk\Model\CriteriaOperator::NOT_EQUALS
            );
        }

        $filter->setChildren($filters);
        $query->setFilter($filter);

        $query->setOrderBys(
            array(
                $this->createEntityOrderBy('createdOn', \WhiteLabelMachineName\Sdk\Model\EntityQueryOrderByType::DESC)
            )
        );
        $query->setNumberOfEntities(1);

        $result = $this->getRefundService()->search($spaceId, $query);
        if (! empty($result)) {
            return $result[0];
        } else {
            return false;
        }
    }

    /**
     * Returns the refund API service.
     *
     * @return \WhiteLabelMachineName\Sdk\Service\RefundService
     */
    protected function getRefundService()
    {
        if ($this->refundService == null) {
            $this->refundService = new \WhiteLabelMachineName\Sdk\Service\RefundService(
                WhiteLabelMachineNameHelper::getApiClient()
            );
        }

        return $this->refundService;
    }
}
