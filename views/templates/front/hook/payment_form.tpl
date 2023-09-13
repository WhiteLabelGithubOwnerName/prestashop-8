{*
 * WhiteLabelName Prestashop
 *
 * This Prestashop module enables to process payments with WhiteLabelName (https://whitelabel-website.com).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2023 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
<form action="{$orderUrl|escape:'html':'UTF-8'}" class="whitelabelmachinename-payment-form" data-method-id="{$methodId|escape:'html':'UTF-8'}">
	<div id="whitelabelmachinename-{$methodId|escape:'html':'UTF-8'}">
		<input type="hidden" id="whitelabelmachinename-iframe-possible-{$methodId|escape:'html':'UTF-8'}" name="whitelabelmachinename-iframe-possible-{$methodId|escape:'html':'UTF-8'}" value="{$iframe|escape:'html':'UTF-8'}" />
		<div id="whitelabelmachinename-loader-{$methodId|escape:'html':'UTF-8'}" class="whitelabelmachinename-loader"></div>
	</div>
</form>
