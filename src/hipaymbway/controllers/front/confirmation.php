<?php

/**
 * 2019 HiPay
 *
 * NOTICE OF LICENSE
 *
 * @author    HiPay Portugal <portugal@hipay.com>
 * @copyright 2019 HiPay Portugal
 * @license   https://github.com/hipaypt/hipaypt-mbway-prestashop/blob/master/LICENSE
 */
require_once(_PS_MODULE_DIR_ . 'hipaymbway' . DIRECTORY_SEPARATOR . 'HipayMbway/MbwayClient.php');
require_once(_PS_MODULE_DIR_ . 'hipaymbway' . DIRECTORY_SEPARATOR . 'HipayMbway/MbwayRequest.php');
require_once(_PS_MODULE_DIR_ . 'hipaymbway' . DIRECTORY_SEPARATOR . 'HipayMbway/MbwayRequestTransaction.php');
require_once(_PS_MODULE_DIR_ . 'hipaymbway' . DIRECTORY_SEPARATOR . 'HipayMbway/MbwayRequestDetails.php');
require_once(_PS_MODULE_DIR_ . 'hipaymbway' . DIRECTORY_SEPARATOR . 'HipayMbway/MbwayRequestResponse.php');
require_once(_PS_MODULE_DIR_ . 'hipaymbway' . DIRECTORY_SEPARATOR . 'HipayMbway/MbwayRequestDetailsResponse.php');
require_once(_PS_MODULE_DIR_ . 'hipaymbway' . DIRECTORY_SEPARATOR . 'HipayMbway/MbwayRequestTransactionResponse.php');
require_once(_PS_MODULE_DIR_ . 'hipaymbway' . DIRECTORY_SEPARATOR . 'HipayMbway/MbwayPaymentDetailsResult.php');
require_once(_PS_MODULE_DIR_ . 'hipaymbway' . DIRECTORY_SEPARATOR . 'HipayMbway/MbwayRequestRefund.php');
require_once(_PS_MODULE_DIR_ . 'hipaymbway' . DIRECTORY_SEPARATOR . 'HipayMbway/MbwayNotification.php');

use HipayMbway\MbwayClient;
use HipayMbway\MbwayRequest;
use HipayMbway\MbwayRequestTransaction;
use HipayMbway\MbwayRequestDetails;
use HipayMbway\MbwayRequestResponse;
use HipayMbway\MbwayRequestDetailsResponse;
use HipayMbway\MbwayRequestTransactionResponse;
use HipayMbway\MbwayPaymentDetailsResult;
use HipayMbway\MbwayRequestRefund;
use HipayMbway\MbwayNotification;

/**
 * @since 1.5.0
 */
class HipayMbwayConfirmationModuleFrontController extends ModuleFrontController {

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess() {

        $module = Tools::getValue('module');
        if ($module != 'hipaymbway') {
            die($this->module->l('This payment method is not available.'));
        }

        $cart_id = (int) Tools::getValue('cart_id');

        try {
            //Notification
            $entityBody = file_get_contents('php://input');
			if ($this->module->logs){
				error_log(date('Y-m-d H:i:s') . ": [NOTIFICATION] ". $entityBody .".\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
			}
            $notification = new MbwayNotification($entityBody);
            if ($notification === false)
                exit;

            $notification_cart_id = $notification->get_ClientExternalReference();
            $transaction_id = $notification->get_OperationId();
            $transaction_amount = $notification->get_Amount();
            $transaction_status = $notification->get_StatusCode();

            if ($notification_cart_id != $cart_id)
                return;

			$sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'hipaymbway WHERE reference = \''.$transaction_id.'\' and cart = ' . $cart_id;
			$row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
			if (!$row)
				return;

			$order = new Order((int) $row['order_id']);
			$order_total = (float) $order->total_paid;
			$current_state_id = $order->getCurrentOrderState()->id;

            if ($row["order_id"] == "")
                return;

            if ($row["reference"] != $transaction_id) {
                return;
            }

            if ($order_total <> $transaction_amount) {
                return;
            }

            $mbway = new MbwayClient($this->module->sandbox);
            $mbwayRequestDetails = new MbwayRequestDetails($this->module->username, $this->module->password, $transaction_id, $this->module->entity);
            $mbwayRequestDetailsResult = new MbwayRequestDetailsResponse($mbway->getPaymentDetails($mbwayRequestDetails)->GetPaymentDetailsResult);

            if ($mbwayRequestDetailsResult->get_ErrorCode() <> 0 || !$mbwayRequestDetailsResult->get_Success()) {
                return;
            }
            $detailStatusCode = $mbwayRequestDetailsResult->get_MBWayPaymentDetails()->get_StatusCode();
            if ($detailStatusCode != $transaction_status) {
                return;
            }

			$update_description = "No status update required";
			
            switch ($transaction_status) {
                case "c1":
                
                    if (Configuration::get('HIPAY_MBWAY_CONFIRM') != $current_state_id) {
                        Db::getInstance()->update('hipaymbway', array(
                            'notification_date' => date('Y-m-d H:i:s'),
                            'status' => 'captured',
                            'hipay_status' => $transaction_status,
                                )
                                , 'order_id=' . $row['order_id']);

                        $order->setCurrentState(Configuration::get('HIPAY_MBWAY_CONFIRM'));
               			$update_description = "Status update requested";

                    }

					if ($this->module->logs){
						error_log(date('Y-m-d H:i:s') . ": [NOTIFICATION] Order: ". $row["order_id"] . " Current Status: " . $current_state_id . " New Status: " . $transaction_status . " " . $update_description . "\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
					}
                    break;
                case "c3":
                case "c6":
                case "vp1":
                    if (Configuration::get('HIPAY_MBWAY_WAITING') == $current_state_id) {
                        Db::getInstance()->update('hipaymbway', array(
                            'notification_date' => date('Y-m-d H:i:s'),
                            'hipay_status' => $transaction_status,
                                )
                                , 'order_id=' . $row['order_id']);
                    }
					if ($this->module->logs){
						error_log(date('Y-m-d H:i:s') . ": [NOTIFICATION] Order: ". $row["order_id"] . " Current Status: " . $current_state_id . " New Status: " . $transaction_status . " " . $update_description . "\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
					}
                    
                    break;
                case "ap1":
                    if (Configuration::get('PS_OS_REFUND') != $current_state_id && Configuration::get('HIPAY_MBWAY_CONFIRM') == $current_state_id) {
                        Db::getInstance()->update('hipaymbway', array(
                            'notification_date' => date('Y-m-d H:i:s'),
                            'status' => 'refunded',
                            'hipay_status' => $transaction_status,
                                )
                                , 'order_id=' . $row['order_id']);

                        $order->setCurrentState(Configuration::get('PS_OS_REFUND'));
                        $update_description = "Status update requested";
                    }
					if ($this->module->logs){
						error_log(date('Y-m-d H:i:s') . ": [NOTIFICATION] Order: ". $row["order_id"] . " Current Status: " . $current_state_id . " New Status: " . $transaction_status . " " . $update_description . "\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
					}
                    break;
                case "c2":
                case "c4":
                case "c5":
                case "c7":
                case "c8":
                case "c9":
                case "vp2":
                    if (Configuration::get('HIPAY_MBWAY_CANCELLED') != $current_state_id && Configuration::get('HIPAY_MBWAY_CONFIRM') != $current_state_id && Configuration::get('PS_OS_REFUND') != $current_state_id ) {
                        Db::getInstance()->update('hipaymbway', array(
                            'notification_date' => date('Y-m-d H:i:s'),
                            'status' => 'error',
                            'hipay_status' => $transaction_status,
                                )
                                , 'order_id=' . $row['order_id']);

                        $order->setCurrentState(Configuration::get('HIPAY_MBWAY_CANCELLED'));
                        $update_description = "Status update requested";
                    }
					if ($this->module->logs){
						error_log(date('Y-m-d H:i:s') . ": [NOTIFICATION] Order: ". $row["order_id"] . " Current Status: " . $current_state_id . " New Status: " . $transaction_status . " " . $update_description . "\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
					}
                    break;
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
            echo $error;
			if ($this->module->logs){
				error_log(date('Y-m-d H:i:s') . ": [NOTIFICATION] Error: ". $error . "\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
			}
            
            return false;
        }

        return true;
    }

}
