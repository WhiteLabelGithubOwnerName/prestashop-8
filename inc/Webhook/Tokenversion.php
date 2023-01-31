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
 * Webhook processor to handle token version state transitions.
 */
class WhiteLabelMachineNameWebhookTokenversion extends WhiteLabelMachineNameWebhookAbstract
{
    public function process(WhiteLabelMachineNameWebhookRequest $request)
    {
        $tokenService = WhiteLabelMachineNameServiceToken::instance();
        $tokenService->updateTokenVersion($request->getSpaceId(), $request->getEntityId());
    }
}
