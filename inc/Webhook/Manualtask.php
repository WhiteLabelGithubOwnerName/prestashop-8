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
 * Webhook processor to handle manual task state transitions.
 */
class WhiteLabelMachineNameWebhookManualtask extends WhiteLabelMachineNameWebhookAbstract
{

    /**
     * Updates the number of open manual tasks.
     *
     * @param WhiteLabelMachineNameWebhookRequest $request
     */
    public function process(WhiteLabelMachineNameWebhookRequest $request)
    {
        $manualTaskService = WhiteLabelMachineNameServiceManualtask::instance();
        $manualTaskService->update();
    }
}
