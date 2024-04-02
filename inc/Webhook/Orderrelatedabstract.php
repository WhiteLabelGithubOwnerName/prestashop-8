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
 * Abstract webhook processor for order related entities.
 */
abstract class WhiteLabelMachineNameWebhookOrderrelatedabstract extends WhiteLabelMachineNameWebhookAbstract
{

    /**
     * Processes the received order related webhook request.
     *
     * @param WhiteLabelMachineNameWebhookRequest $request
     */
    public function process(WhiteLabelMachineNameWebhookRequest $request)
    {
        WhiteLabelMachineNameHelper::startDBTransaction();
        $entity = $this->loadEntity($request);
        try {
            $order = new Order($this->getOrderId($entity));
            if (Validate::isLoadedObject($order)) {
                $ids = WhiteLabelMachineNameHelper::getOrderMeta($order, 'mappingIds');
                if ($ids['transactionId'] != $this->getTransactionId($entity)) {
                    return;
                }
                // We never have an employee on webhooks, but the stock magement sometimes needs one
                if (Context::getContext()->employee == null) {
                    $employees = Employee::getEmployeesByProfile(_PS_ADMIN_PROFILE_, true);
                    $employeeArray = reset($employees);
                    Context::getContext()->employee = new Employee($employeeArray['id_employee']);
                }
                WhiteLabelMachineNameHelper::lockByTransactionId(
                    $request->getSpaceId(),
                    $this->getTransactionId($entity)
                );
                $order = new Order($this->getOrderId($entity));
                $this->processOrderRelatedInner($order, $entity);
            }
            WhiteLabelMachineNameHelper::commitDBTransaction();
        } catch (Exception $e) {
            WhiteLabelMachineNameHelper::rollbackDBTransaction();
            throw $e;
        }
    }

    /**
     * Loads and returns the entity for the webhook request.
     *
     * @param WhiteLabelMachineNameWebhookRequest $request
     * @return object
     */
    abstract protected function loadEntity(WhiteLabelMachineNameWebhookRequest $request);

    /**
     * Returns the order's increment id linked to the entity.
     *
     * @param object $entity
     * @return string
     */
    abstract protected function getOrderId($entity);

    /**
     * Returns the transaction's id linked to the entity.
     *
     * @param object $entity
     * @return int
     */
    abstract protected function getTransactionId($entity);

    /**
     * Actually processes the order related webhook request.
     *
     * This must be implemented
     *
     * @param Order $order
     * @param Object $entity
     */
    abstract protected function processOrderRelatedInner(Order $order, $entity);
}
