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
class HipayMbwayCronjobModuleFrontController extends ModuleFrontController {

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess() {

        $module = Tools::getValue('module');
        if ($module != 'hipaymbway') {
            die($this->module->l('This payment method is not available.'));
        }

		if ($this->module->logs){
			error_log(date('Y-m-d H:i:s') . ": [CRONJOB] STARTING\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
		}

        try {

			$sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'hipaymbway WHERE status =  \'waiting\' LIMIT 10';
			
			if ($results = Db::getInstance()->ExecuteS($sql)){
				foreach ($results as $row){

					if ($this->module->logs){
						error_log(date('Y-m-d H:i:s') . ": [CRONJOB] CHECK ORDER " . $row['order_id'] . "\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
					}

					$transaction_id = $row['reference'];
					$order = new Order((int) $row['order_id']);
					$current_state_id = $order->getCurrentOrderState()->id;			
					
					$mbway = new MbwayClient($this->module->sandbox);
					$mbwayRequestDetails = new MbwayRequestDetails($this->module->username, $this->module->password, $transaction_id, $this->module->entity);
					$mbwayRequestDetailsResult = new MbwayRequestDetailsResponse($mbway->getPaymentDetails($mbwayRequestDetails)->GetPaymentDetailsResult);

					if ($mbwayRequestDetailsResult->get_ErrorCode() <> 0 || !$mbwayRequestDetailsResult->get_Success()) {
						return;
					}
					$detailStatusCode = $mbwayRequestDetailsResult->get_MBWayPaymentDetails()->get_StatusCode();
					$update_description = "No status update required";

					switch ($detailStatusCode) {
						case "c1":
						
							if (Configuration::get('HIPAY_MBWAY_CONFIRM') != $current_state_id) {
								Db::getInstance()->update('hipaymbway', array(
									'notification_date' => date('Y-m-d H:i:s'),
									'status' => 'captured',
									'hipay_status' => $detailStatusCode,
										)
										, 'order_id=' . $row['order_id']);

								$order->setCurrentState(Configuration::get('HIPAY_MBWAY_CONFIRM'));
								$update_description = "Status update requested";

							}

							if ($this->module->logs){
								error_log(date('Y-m-d H:i:s') . ": [CRONJOB] Order: ". $row["order_id"] . " Current Status: " . $current_state_id . " New Status: " . $detailStatusCode . " " . $update_description . "\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
							}
							break;
						case "c3":
						case "c6":
						case "vp1":
							if (Configuration::get('HIPAY_MBWAY_WAITING') == $current_state_id) {
								Db::getInstance()->update('hipaymbway', array(
									'notification_date' => date('Y-m-d H:i:s'),
									'hipay_status' => $detailStatusCode,
										)
										, 'order_id=' . $row['order_id']);
							}
							if ($this->module->logs){
								error_log(date('Y-m-d H:i:s') . ": [CRONJOB] Order: ". $row["order_id"] . " Current Status: " . $current_state_id . " New Status: " . $detailStatusCode . " " . $update_description . "\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
							}
							
							break;
						case "ap1":
							if (Configuration::get('PS_OS_REFUND') != $current_state_id && Configuration::get('HIPAY_MBWAY_CONFIRM') == $current_state_id) {
								Db::getInstance()->update('hipaymbway', array(
									'notification_date' => date('Y-m-d H:i:s'),
									'status' => 'refunded',
									'hipay_status' => $detailStatusCode,
										)
										, 'order_id=' . $row['order_id']);

								$order->setCurrentState(Configuration::get('PS_OS_REFUND'));
								$update_description = "Status update requested";
							}
							if ($this->module->logs){
								error_log(date('Y-m-d H:i:s') . ": [CRONJOB] Order: ". $row["order_id"] . " Current Status: " . $current_state_id . " New Status: " . $detailStatusCode . " " . $update_description . "\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
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
									'hipay_status' => $detailStatusCode,
										)
										, 'order_id=' . $row['order_id']);

								$order->setCurrentState(Configuration::get('HIPAY_MBWAY_CANCELLED'));
								$update_description = "Status update requested";
							}
							if ($this->module->logs){
								error_log(date('Y-m-d H:i:s') . ": [CRONJOB] Order: ". $row["order_id"] . " Current Status: " . $current_state_id . " New Status: " . $detailStatusCode . " " . $update_description . "\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
							}
							break;
					}

				}
			}

        } catch (Exception $e) {
            $error = $e->getMessage();
            echo $error;
    
        }

        exit;
    }

}

