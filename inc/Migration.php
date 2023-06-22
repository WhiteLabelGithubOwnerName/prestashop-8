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

class WhiteLabelMachineNameMigration extends WhiteLabelMachineNameAbstractmigration
{
    protected static function getMigrations()
    {
        return array(
            '1.0.0' => 'initializePlugin',
        );
    }

    public static function initializePlugin()
    {
        static::installTableBase();
        static::orderStatusUpdate();
    }

    public static function orderStatusUpdate()
    {
        static::installOrderStatusConfigBase();
        static::installOrderPaymentSaveHookBase();
    }
}
