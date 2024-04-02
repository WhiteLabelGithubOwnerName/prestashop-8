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

class AdminWhiteLabelMachineNameOrderController extends ModuleAdminController
{
    public function postProcess()
    {
        parent::postProcess();
        exit();
    }

    public function initProcess()
    {
        parent::initProcess();
        $access = Profile::getProfileAccess(
            $this->context->employee->id_profile,
            (int) Tab::getIdFromClassName('AdminOrders')
        );
        if ($access['edit'] === '1' && ($action = Tools::getValue('action'))) {
            $this->action = $action;
        } else {
            echo json_encode(
                array(
                    'success' => 'false',
                    'message' => $this->module->l(
                        'You do not have permission to edit the order.',
                        'adminwhitelabelmachinenameordercontroller'
                    )
                )
            );
            die();
        }
    }

    public function ajaxProcessUpdateOrder()
    {
        if (Tools::isSubmit('id_order')) {
            try {
                $order = new Order(Tools::getValue('id_order'));
                WhiteLabelMachineNameServiceTransactioncompletion::instance()->updateForOrder($order);
                WhiteLabelMachineNameServiceTransactioncompletion::instance()->updateForOrder($order);
                echo json_encode(array(
                    'success' => 'true'
                ));
                die();
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => 'false',
                    'message' => $e->getMessage()
                ));
                die();
            }
        } else {
            echo json_encode(
                array(
                    'success' => 'false',
                    'message' => $this->module->l('Incomplete Request.', 'adminwhitelabelmachinenameordercontroller')
                )
            );
            die();
        }
    }

    public function ajaxProcessVoidOrder()
    {
        if (Tools::isSubmit('id_order')) {
            try {
                $order = new Order(Tools::getValue('id_order'));
                WhiteLabelMachineNameServiceTransactionvoid::instance()->executeVoid($order);
                echo json_encode(
                    array(
                        'success' => 'true',
                        'message' => $this->module->l(
                            'The order is updated automatically once the void is processed.',
                            'adminwhitelabelmachinenameordercontroller'
                        )
                    )
                );
                die();
            } catch (Exception $e) {
                echo json_encode(
                    array(
                        'success' => 'false',
                        'message' => WhiteLabelMachineNameHelper::cleanExceptionMessage($e->getMessage())
                    )
                );
                die();
            }
        } else {
            echo json_encode(
                array(
                    'success' => 'false',
                    'message' => $this->module->l('Incomplete Request.', 'adminwhitelabelmachinenameordercontroller')
                )
            );
            die();
        }
    }

    public function ajaxProcessCompleteOrder()
    {
        if (Tools::isSubmit('id_order')) {
            try {
                $order = new Order(Tools::getValue('id_order'));
                WhiteLabelMachineNameServiceTransactioncompletion::instance()->executeCompletion($order);
                echo json_encode(
                    array(
                        'success' => 'true',
                        'message' => $this->module->l(
                            'The order is updated automatically once the completion is processed.',
                            'adminwhitelabelmachinenameordercontroller'
                        )
                    )
                );
                die();
            } catch (Exception $e) {
                echo json_encode(
                    array(
                        'success' => 'false',
                        'message' => WhiteLabelMachineNameHelper::cleanExceptionMessage($e->getMessage())
                    )
                );
                die();
            }
        } else {
            echo json_encode(
                array(
                    'success' => 'false',
                    'message' => $this->module->l('Incomplete Request.', 'adminwhitelabelmachinenameordercontroller')
                )
            );
            die();
        }
    }
}
