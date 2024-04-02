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
 * This provider allows to create a WhiteLabelMachineName_ShopRefund_IStrategy.
 * The implementation of
 * the strategy depends on the actual prestashop version.
 */
class WhiteLabelMachineNameBackendStrategyprovider
{
    /**
     * Returns the refund strategy to use
     *
     * @return WhiteLabelMachineNameBackendIstrategy
     */
    public static function getStrategy()
    {
        return new WhiteLabelMachineNameBackendDefaultstrategy();
    }
}
