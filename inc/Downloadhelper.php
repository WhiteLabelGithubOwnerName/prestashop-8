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
 * This class provides function to download documents from WhiteLabelName
 */
class WhiteLabelMachineNameDownloadhelper
{

    /**
     * Downloads the transaction's invoice PDF document.
     */
    public static function downloadInvoice($order)
    {
        $transactionInfo = WhiteLabelMachineNameHelper::getTransactionInfoForOrder($order);
        if ($transactionInfo != null && in_array(
            $transactionInfo->getState(),
            array(
                \WhiteLabelMachineName\Sdk\Model\TransactionState::COMPLETED,
                \WhiteLabelMachineName\Sdk\Model\TransactionState::FULFILL,
                \WhiteLabelMachineName\Sdk\Model\TransactionState::DECLINE
            )
        )) {
            $service = new \WhiteLabelMachineName\Sdk\Service\TransactionService(
                WhiteLabelMachineNameHelper::getApiClient()
            );
            $document = $service->getInvoiceDocument(
                $transactionInfo->getSpaceId(),
                $transactionInfo->getTransactionId()
            );
            self::download($document);
        }
    }

    /**
     * Downloads the transaction's packing slip PDF document.
     */
    public static function downloadPackingSlip($order)
    {
        $transactionInfo = WhiteLabelMachineNameHelper::getTransactionInfoForOrder($order);
        if ($transactionInfo != null &&
            $transactionInfo->getState() == \WhiteLabelMachineName\Sdk\Model\TransactionState::FULFILL) {
            $service = new \WhiteLabelMachineName\Sdk\Service\TransactionService(
                WhiteLabelMachineNameHelper::getApiClient()
            );
            $document = $service->getPackingSlip($transactionInfo->getSpaceId(), $transactionInfo->getTransactionId());
            self::download($document);
        }
    }

    /**
     * Sends the data received by calling the given path to the browser and ends the execution of the script
     *
     * @param string $path
     */
    protected static function download(\WhiteLabelMachineName\Sdk\Model\RenderedDocument $document)
    {
        header('Pragma: public');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $document->getTitle() . '.pdf"');
        header('Content-Description: ' . $document->getTitle());
        echo WhiteLabelMachineNameTools::base64Decode($document->getData());
        exit();
    }
}
