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

class AdminWhiteLabelMachineNameDocumentsController extends ModuleAdminController
{
    public function postProcess()
    {
        parent::postProcess();
        // We want to be sure that displaying PDF is the last thing this controller will do
        exit();
    }

    public function initProcess()
    {
        parent::initProcess();
        $access = Profile::getProfileAccess(
            $this->context->employee->id_profile,
            (int) Tab::getIdFromClassName('AdminOrders')
        );
        if ($access['view'] === '1' && ($action = Tools::getValue('action'))) {
            $this->action = $action;
        } else {
            die(
                Tools::displayError(
                    $this->module->l(
                        'You do not have permission to view this.',
                        'adminwhitelabelmachinenamedocumentscontroller'
                    )
                )
            );
        }
    }

    public function processWhiteLabelMachineNameInvoice()
    {
        if (Tools::isSubmit('id_order')) {
            try {
                $order = new Order(Tools::getValue('id_order'));
                WhiteLabelMachineNameDownloadhelper::downloadInvoice($order);
            } catch (Exception $e) {
                die(
                    Tools::displayError(
                        $this->module->l(
                            'Could not fetch the document.',
                            'adminwhitelabelmachinenamedocumentscontroller'
                        )
                    )
                );
            }
        } else {
            die(
                Tools::displayError(
                    $this->module->l('The order Id is missing.', 'adminwhitelabelmachinenamedocumentscontroller')
                )
            );
        }
    }

    public function processWhiteLabelMachineNamePackingSlip()
    {
        if (Tools::isSubmit('id_order')) {
            try {
                $order = new Order(Tools::getValue('id_order'));
                WhiteLabelMachineNameDownloadhelper::downloadPackingSlip($order);
            } catch (Exception $e) {
                die(
                    Tools::displayError(
                        $this->module->l(
                            'Could not fetch the document.',
                            'adminwhitelabelmachinenamedocumentscontroller'
                        )
                    )
                );
            }
        } else {
            die(
                Tools::displayError(
                    $this->module->l('The order Id is missing.', 'adminwhitelabelmachinenamedocumentscontroller')
                )
            );
        }
    }
}
