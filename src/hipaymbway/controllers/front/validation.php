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
class HipayMbwayValidationModuleFrontController extends ModuleFrontController {

    /**
     * @see FrontController::postProcess()
     */
    public function postProcess() {
        $cart = $this->context->cart;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
			if ($this->context->controller->module->logs){
				error_log(date('Y-m-d H:i:s') . ": [CREATE] No customer, no address or module not active.\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
			}		
            Tools::redirect('index.php?controller=order&step=1');
        }

        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'hipaymbway') {
                $authorized = true;
                break;
            }
        }
        if (!$authorized) {
			if ($this->context->controller->module->logs){
				error_log(date('Y-m-d H:i:s') . ": [CREATE] Not authorized to use this module.\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
			}					
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $this->context->smarty->assign([
            'params' => $_REQUEST,
        ]);

        $customer_email = $this->context->customer->email;

        $mbway_phone = Tools::getValue('altPhoneNumber');
        if (strlen($mbway_phone) == 0) {
            $id_address = $this->context->cart->id_address_invoice;
            $address = new Address($id_address);
            if ($address->phone_mobile != "")
                $mbway_phone = $address->phone_mobile;
            elseif ($address->phone != "")
                $mbway_phone = $address->phone;
        }

        $mbway_phone = preg_replace('/\D/', '', $mbway_phone);
        if (strlen($mbway_phone) > 9) {
            $mbway_phone = substr($mbway_phone, -9);
        }

        $customer = new Customer($cart->id_customer);
        if (!Validate::isLoadedObject($customer))
            Tools::redirect('index.php?controller=order&step=1');

        $currency = $this->context->currency;
        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        $shop_id = $this->context->cart->id_shop;
        $shop = new Shop($shop_id);
        Shop::setContext(Shop::CONTEXT_SHOP, $shop_id);

        $callback_url = $this->context->link->getModuleLink($this->module->name, 'confirmation', array("cart_id" => $cart->id), true);

        $mbway = new MbwayClient($this->context->controller->module->sandbox);

        $mbwayRequestTransaction = new MbwayRequestTransaction($this->context->controller->module->username, $this->context->controller->module->password, $total, $mbway_phone, $customer_email, $cart->id, $this->context->controller->module->category, $callback_url, $this->context->controller->module->entity);
        $mbwayRequestTransaction->set_description($cart->id);
        $mbwayRequestTransaction->set_clientVATNumber("");
        $mbwayRequestTransaction->set_clientName("");
        $mbwayRequestTransactionResult = new MbwayRequestTransactionResponse($mbway->createPayment($mbwayRequestTransaction)->CreatePaymentResult);

        if ($mbwayRequestTransactionResult->get_Success() && $mbwayRequestTransactionResult->get_ErrorCode() == "0") {
            switch ($mbwayRequestTransactionResult->get_MBWayPaymentOperationResult()->get_StatusCode()) {
                case "vp1":
                    $reference = $mbwayRequestTransactionResult->get_MBWayPaymentOperationResult()->get_OperationId();
                    $this->module->validateOrder($cart->id, Configuration::get('HIPAY_MBWAY_WAITING'), $total, $this->module->displayName, $reference, array(), (int) $currency->id, false, $customer->secure_key, $shop);

                    Db::getInstance()->insert('hipaymbway', array(
                        'reference' => $reference,
                        'cart' => $cart->id,
                        'phone' => $mbway_phone,
                        'order_id' => $this->module->currentOrder,
                        'amount' => $total,
                    ));

					if ($this->context->controller->module->logs){
						error_log(date('Y-m-d H:i:s') . ": [CREATE] Created reference " .$reference. " for cart " . $cart->id . " and order " . $this->module->currentOrder . ".\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
					}					

                    Tools::redirect('index.php?controller=order-confirmation&id_cart=' . $cart->id . '&id_module=' . $this->module->id . '&id_order=' . $this->module->currentOrder . '&key=' . $customer->secure_key);
                    break;
                case "vp2":
					if ($this->context->controller->module->logs){
						error_log(date('Y-m-d H:i:s') . ": [CREATE] Error for cart " . $cart->id . ". Operation refused. Please try again or choose another payment method.\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
					}					
                    $error = $this->module->l("Operation refused. Please try again or choose another payment method.");
					break;
                case "vp3":
					if ($this->context->controller->module->logs){
						error_log(date('Y-m-d H:i:s') . ": [CREATE] Error for cart " . $cart->id . ". Operation refused. Limit exceeded. Please try again or choose another payment method.\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
					}	
                    $error = $this->module->l("Operation refused. Limit exceeded. Please try again or choose another payment method.");
                    break;
                case "er1":
					if ($this->context->controller->module->logs){
						error_log(date('Y-m-d H:i:s') . ": [CREATE] Error for cart " . $cart->id . ". Operation refused. Invalid phone number. Please try again with another phone number or choose another payment method.\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
					}	
                    $error = $this->module->l("Operation refused. Invalid phone number. Please try again with another phone number or choose another payment method.");
                    break;
                case "er2":
					if ($this->context->controller->module->logs){
						error_log(date('Y-m-d H:i:s') . ": [CREATE] Error for cart " . $cart->id . ". Operation refused. Unassigned phone number. Please try again with another phone number or choose another payment method.\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
					}	
                    $error = $this->module->l("Operation refused. Unassigned phone number. Please try again with another phone number or choose another payment method.");
                    break;
                default:

                    $error = $this->module->l("Operation refused. Please try again or choose another payment method.");
            }
        } else {
            $error = $mbwayRequestTransactionResult->get_ErrorDescription();
			if ($this->context->controller->module->logs){
				error_log(date('Y-m-d H:i:s') . ": [CREATE] Error for cart " . $cart->id . ". " . $error . "\n\n",3,dirname(__FILE__) . '/../../logs/' . date('Y-m-d') . '.log');
			}	
        }

        $this->context->smarty->assign(array(
            'error' => $error,
        ));
        return $this->setTemplate('module:' . $this->module->name . '/views/templates/front/error.tpl');
    }

}
