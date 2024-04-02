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
 * Webhook processor to handle token state transitions.
 */
class WhiteLabelMachineNameWebhookToken extends WhiteLabelMachineNameWebhookAbstract
{
    public function process(WhiteLabelMachineNameWebhookRequest $request)
    {
        $tokenService = WhiteLabelMachineNameServiceToken::instance();
        $tokenService->updateToken($request->getSpaceId(), $request->getEntityId());
    }
}
