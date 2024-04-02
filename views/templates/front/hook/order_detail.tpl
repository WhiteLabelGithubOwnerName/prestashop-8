{*
 * WhiteLabelName Prestashop
 *
 * This Prestashop module enables to process payments with WhiteLabelName (https://whitelabel-website.com).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2024 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
<div id="whitelabelmachinename_documents" style="display:none">
{if !empty($whiteLabelMachineNameInvoice)}
	<a target="_blank" href="{$whiteLabelMachineNameInvoice|escape:'html':'UTF-8'}">{l s='Download your %name% invoice as a PDF file.' sprintf=['%name%' => 'WhiteLabelName'] mod='whitelabelmachinename'}</a>
{/if}
{if !empty($whiteLabelMachineNamePackingSlip)}
	<a target="_blank" href="{$whiteLabelMachineNamePackingSlip|escape:'html':'UTF-8'}">{l s='Download your %name% packing slip as a PDF file.' sprintf=['%name%' => 'WhiteLabelName'] mod='whitelabelmachinename'}</a>
{/if}
</div>
