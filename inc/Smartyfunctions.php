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

class WhiteLabelMachineNameSmartyfunctions
{
    public static function translate($params, $smarty)
    {
        $text = $params['text'];
        return WhiteLabelMachineNameHelper::translate($text);
    }

    /**
     * Returns the URL to the refund detail view in WhiteLabelName.
     *
     * @return string
     */
    public static function getRefundUrl($params, $smarty)
    {
        $refundJob = $params['refund'];
        return WhiteLabelMachineNameHelper::getRefundUrl($refundJob);
    }

    public static function getRefundAmount($params, $smarty)
    {
        $refundJob = $params['refund'];
        return WhiteLabelMachineNameBackendStrategyprovider::getStrategy()->getRefundTotal(
            $refundJob->getRefundParameters()
        );
    }

    public static function getRefundType($params, $smarty)
    {
        $refundJob = $params['refund'];
        return WhiteLabelMachineNameBackendStrategyprovider::getStrategy()->getWhiteLabelMachineNameRefundType(
            $refundJob->getRefundParameters()
        );
    }

    /**
     * Returns the URL to the completion detail view in WhiteLabelName.
     *
     * @return string
     */
    public static function getCompletionUrl($params, $smarty)
    {
        $completionJob = $params['completion'];
        return WhiteLabelMachineNameHelper::getCompletionUrl($completionJob);
    }

    /**
     * Returns the URL to the void detail view in WhiteLabelName.
     *
     * @return string
     */
    public static function getVoidUrl($params, $smarty)
    {
        $voidJob = $params['void'];
        return WhiteLabelMachineNameHelper::getVoidUrl($voidJob);
    }
    
    /**
     * Returns the URL to the void detail view in WhiteLabelName.
     *
     * @return string
     */
    public static function cleanHtml($params, $smarty)
    {
        return strip_tags($params['text'], '<a><b><strong><i><img><span><div>');
    }
    
    /**
     * Returns the URL to the void detail view in WhiteLabelName.
     *
     * @return string
     */
    public static function outputMethodForm($params, $smarty)
    {
        return $params['form'];
    }
}
