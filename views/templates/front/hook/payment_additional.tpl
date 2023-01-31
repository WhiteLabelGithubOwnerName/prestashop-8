{*
 * WhiteLabelName Prestashop
 *
 * This Prestashop module enables to process payments with WhiteLabelName (https://whitelabel-website.com).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2023 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 *}
<div sytle="display:none" class="whitelabelmachinename-method-data" data-method-id="{$methodId|escape:'html':'UTF-8'}" data-configuration-id="{$configurationId|escape:'html':'UTF-8'}"></div>
<section>
  {if !empty($description)}
    {* The description has to be unfiltered to dispaly html correcty. We strip unallowed html tags before we assign the variable to smarty *}
    <p>{whitelabelmachinename_clean_html text=$description}</p>
  {/if}
  {if !empty($surchargeValues)}
	<span class="whitelabelmachinename-surcharge whitelabelmachinename-additional-amount"><span class="whitelabelmachinename-surcharge-text whitelabelmachinename-additional-amount-test">{l s='Minimum Sales Surcharge:' mod='whitelabelmachinename'}</span>
		<span class="whitelabelmachinename-surcharge-value whitelabelmachinename-additional-amount-value">
			{if $priceDisplayTax}
				{Tools::displayPrice($surchargeValues.surcharge_total)|escape:'html':'UTF-8'} {l s='(tax excl.)' mod='whitelabelmachinename'}
	        {else}
	        	{Tools::displayPrice($surchargeValues.surcharge_total_wt)|escape:'html':'UTF-8'} {l s='(tax incl.)' mod='whitelabelmachinename'}
	        {/if}
       </span>
   </span>
  {/if}
  {if !empty($feeValues)}
	<span class="whitelabelmachinename-payment-fee whitelabelmachinename-additional-amount"><span class="whitelabelmachinename-payment-fee-text whitelabelmachinename-additional-amount-test">{l s='Payment Fee:' mod='whitelabelmachinename'}</span>
		<span class="whitelabelmachinename-payment-fee-value whitelabelmachinename-additional-amount-value">
			{if ($priceDisplayTax)}
	          	{Tools::displayPrice($feeValues.fee_total)|escape:'html':'UTF-8'} {l s='(tax excl.)' mod='whitelabelmachinename'}
	        {else}
	          	{Tools::displayPrice($feeValues.fee_total_wt)|escape:'html':'UTF-8'} {l s='(tax incl.)' mod='whitelabelmachinename'}
	        {/if}
       </span>
   </span>
  {/if}
  
</section>
