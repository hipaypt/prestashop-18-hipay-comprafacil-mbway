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
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class HipayMbway extends PaymentModule {

    protected $_html = '';
    protected $_postErrors = array();
    public $sandbox;
    public $entity;
    public $username;
    public $password;
    public $category;
    public $logs;

    public function __construct() {
        $this->name = 'hipaymbway';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = array('min' => '1.8.0.0', 'max' => _PS_VERSION_);
        $this->author = 'HiPay Portugal';
        $this->controllers = array('payment', 'validation');
        $this->is_eu_compatible = 1;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $config = Configuration::getMultiple(array('HIPAY_MBWAY_SANDBOX', 'HIPAY_MBWAY_ENTITY', 'HIPAY_MBWAY_USERNAME', 'HIPAY_MBWAY_PASSWORD', 'HIPAY_MBWAY_CATEGORY', 'HIPAY_MBWAY_LOGS'));
        if (!empty($config['HIPAY_MBWAY_SANDBOX'])) {
            $this->sandbox = $config['HIPAY_MBWAY_SANDBOX'];
        }
        if (!empty($config['HIPAY_MBWAY_ENTITY'])) {
            $this->entity = $config['HIPAY_MBWAY_ENTITY'];
        }
        if (!empty($config['HIPAY_MBWAY_USERNAME'])) {
            $this->username = $config['HIPAY_MBWAY_USERNAME'];
        }
        if (!empty($config['HIPAY_MBWAY_PASSWORD'])) {
            $this->password = $config['HIPAY_MBWAY_PASSWORD'];
        }
        if (!empty($config['HIPAY_MBWAY_CATEGORY'])) {
            $this->category = $config['HIPAY_MBWAY_CATEGORY'];
        }
        if (!empty($config['HIPAY_MBWAY_LOGS'])) {
            $this->logs = $config['HIPAY_MBWAY_LOGS'];
        }
        
        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('MB WAY');
        $this->description = $this->l('Accept payments with MB WAY.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');

        if (!isset($this->username) || !isset($this->password) || !isset($this->category)) {
            $this->warning = $this->l('Account details must be configured before using this module.');
        }
        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
    }

    public function install() {

        if (extension_loaded('soap') == false) {
            $this->_errors[] = $this->l('You have to enable the SOAP extension on your server to install this module');
            return false;
        } elseif (!parent::install() || !$this->registerHook('paymentReturn') || !$this->registerHook('paymentOptions') || !$this->registerHook('displayAdminOrder')) {
            return false;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . $this->name . "`(
            `id_hipaymbway` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
            `reference` VARCHAR(256) NOT NULL,
            `amount` VARCHAR(15) NOT NULL,
            `cart` VARCHAR(256) NOT NULL,
            `phone` VARCHAR(56) NOT NULL,
            `order_id` VARCHAR(256) NOT NULL DEFAULT '0',
            `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `status` VARCHAR(25) NOT NULL DEFAULT 'waiting',
            `hipay_status` VARCHAR(5) NOT NULL DEFAULT '',
            `notification_date` datetime DEFAULT NULL
             )";

        if (!$result = Db::getInstance()->Execute($sql))
            return false;

        $this->createOrderStatus();

        return true;
    }

    public function uninstall() {

        if (!Configuration::deleteByName('HIPAY_MBWAY_LOGS') || !Configuration::deleteByName('HIPAY_MBWAY_ENTITY') || !Configuration::deleteByName('HIPAY_MBWAY_SANDBOX') || !Configuration::deleteByName('HIPAY_MBWAY_USERNAME') || !Configuration::deleteByName('HIPAY_MBWAY_PASSWORD') || !Configuration::deleteByName('HIPAY_MBWAY_CATEGORY') || !parent::uninstall()) {
            return false;
        }
        return true;
    }

    protected function _postValidation() {
        if (Tools::isSubmit('btnSubmit')) {

            if (!Tools::getValue('HIPAY_MBWAY_ENTITY')) {
                $this->_postErrors[] = $this->l('Account details are required.');
            } elseif (!Tools::getValue('HIPAY_MBWAY_USERNAME')) {
                $this->_postErrors[] = $this->l('Account username is required.');
            } elseif (!Tools::getValue('HIPAY_MBWAY_PASSWORD')) {
                $this->_postErrors[] = $this->l('Account password is required.');
            } elseif (!Tools::getValue('HIPAY_MBWAY_CATEGORY')) {
                $this->_postErrors[] = $this->l('Account category is required.');
            }
        }
    }

    protected function _postProcess() {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('HIPAY_MBWAY_SANDBOX', 	Tools::getValue('HIPAY_MBWAY_SANDBOX'));
            Configuration::updateValue('HIPAY_MBWAY_ENTITY', 	Tools::getValue('HIPAY_MBWAY_ENTITY'));
            Configuration::updateValue('HIPAY_MBWAY_USERNAME', 	Tools::getValue('HIPAY_MBWAY_USERNAME'));
            Configuration::updateValue('HIPAY_MBWAY_PASSWORD', 	Tools::getValue('HIPAY_MBWAY_PASSWORD'));
            Configuration::updateValue('HIPAY_MBWAY_CATEGORY', 	Tools::getValue('HIPAY_MBWAY_CATEGORY'));
            Configuration::updateValue('HIPAY_MBWAY_LOGS', 		Tools::getValue('HIPAY_MBWAY_LOGS'));
           
       		if ( Tools::getValue('HIPAY_MBWAY_LOGS') && is_writable( dirname(__FILE__)  ) ){
				if ( !is_file( dirname(__FILE__) . '/logs') ){
					mkdir( dirname(__FILE__) . '/logs');
					copy( dirname(__FILE__) . '/index.php' , dirname(__FILE__) . '/logs/index.php');
				}
				error_log(date('Y-m-d H:i:s') . ": [ADMIN] Save configurations.\n\n",3,dirname(__FILE__) . '/logs/' . date('Y-m-d') . '.log');
			}
        }
        $this->_html .= $this->displayConfirmation($this->l('Settings updated'));
    }

    protected function _displayHipayMbway() {
        return $this->display(__FILE__, 'infos.tpl');
    }

    public function createOrderStatus() {

        if (!Configuration::get('HIPAY_MBWAY_WAITING')) {
            $new_order_state = new OrderState();
            $new_order_state->name = array();
            foreach (Language::getLanguages() as $language) {
                $new_order_state->name[$language['id_lang']] = $this->l('MB WAY waiting payment.');
            }

            $new_order_state->module_name = $this->name;
            $new_order_state->send_email = false;
            $new_order_state->color = '#4169E1';
            $new_order_state->hidden = false;
            $new_order_state->delivery = false;
            $new_order_state->logable = false;
            $new_order_state->invoice = false;
            $new_order_state->unremovable = true;

            if ($new_order_state->add()) {
                $source = dirname(__FILE__) . '/views/images/waiting.gif';
                $destination = dirname(__FILE__) . '/../../img/os/' . (int) $new_order_state->id . '.gif';
                copy($source, $destination);
            }

            Configuration::updateValue('HIPAY_MBWAY_WAITING', (int) $new_order_state->id);
        }

        if (!Configuration::get('HIPAY_MBWAY_CONFIRM')) {
            $new_order_state = new OrderState();
            $new_order_state->name = $new_order_state->name = array();
            foreach (Language::getLanguages() as $language) {
                $new_order_state->name[$language['id_lang']] = $this->l('MB WAY payment confirmation.');
            }

            $new_order_state->module_name = $this->name;
            $new_order_state->send_email = true;
            $new_order_state->template = "payment";
            $new_order_state->color = '#32CD32';
            $new_order_state->hidden = false;
            $new_order_state->delivery = false;
            $new_order_state->logable = false;
            $new_order_state->invoice = true;
            $new_order_state->unremovable = true;

            if ($new_order_state->add()) {
                $source = dirname(__FILE__) . '/views/images/ok.gif';
                $destination = dirname(__FILE__) . '/../../img/os/' . (int) $new_order_state->id . '.gif';
                copy($source, $destination);
            }

            Configuration::updateValue('HIPAY_MBWAY_CONFIRM', (int) $new_order_state->id);
        }

        if (!Configuration::get('HIPAY_MBWAY_CANCELLED')) {
            $new_order_state = new OrderState();
            $new_order_state->name = array();
            foreach (Language::getLanguages() as $language) {
                $new_order_state->name[$language['id_lang']] = $this->l('MB WAY payment error.');
            }

            $new_order_state->module_name = $this->name;
            $new_order_state->send_email = true;
            $new_order_state->template = "payment_error";
            $new_order_state->color = '#DC143C';
            $new_order_state->hidden = false;
            $new_order_state->delivery = false;
            $new_order_state->logable = false;
            $new_order_state->invoice = false;
            $new_order_state->unremovable = true;

            if ($new_order_state->add()) {
                $source = dirname(__FILE__) . '/views/images/cancel.gif';
                $destination = dirname(__FILE__) . '/../../img/os/' . (int) $new_order_state->id . '.gif';
                copy($source, $destination);
            }

            Configuration::updateValue('HIPAY_MBWAY_CANCELLED', (int) $new_order_state->id);
        }

        return true;
    }

    public function hookDisplayAdminOrder($params) {

        if (!$this->active) {
            return;
        }

        $order_id = $params['id_order'];
        $sql = 'SELECT * FROM ' . _DB_PREFIX_ . 'hipaymbway WHERE order_id = ' . $order_id;
        if ($row = Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql)) {

            if ($row['notification_date'] == "")
                $row['notification_date'] = $this->l('No notification');
            $this->context->smarty->assign(array(
                'phone'         => $row['phone'],
                'reference'     => $row['reference'],
                'amount'        => $row['amount'],
                'order_id'      => $row['order_id'],
                'status'        => strtoupper($row['status']),
                'notification'  => $row['notification_date'],
                'mbway_logo'    => $this->_path . DIRECTORY_SEPARATOR . "logo.png",
            ));
            return $this->display(__FILE__, 'views/templates/admin/admin_order.tpl');
        }
        return;
    }

    public function getContent() {

        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<p></p>';
        }

        $this->_html .= $this->_displayHipayMbway();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    public function hookPaymentOptions($params) {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $this->smarty->assign(
                $this->getTemplateVarInfos()
        );

        $newOption = new PaymentOption();
        $newOption->setModuleName($this->name)
                ->setCallToActionText($this->l('MB WAY'))
                ->setAction($this->context->link->getModuleLink($this->name, 'validation', array(), true))
                ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/payment.jpg'))
                ->setInputs($this->getTemplateVarInfos())
                ->setForm($this->generateForm());
        $payment_options = [
            $newOption,
        ];

        return $payment_options;
    }

    protected function generateForm() {
        $this->context->smarty->assign([
            'action' => $this->context->link->getModuleLink($this->name, 'validation', array(), true),
        ]);
        return $this->context->smarty->fetch('module:hipaymbway/views/templates/front/payment_form.tpl');
    }

    public function checkCurrency($cart) {
        $currency_order = new Currency($cart->id_currency);
        if ($currency_order->iso_code == "EUR")
            return true;
        return false;
    }

    public function renderForm() {

        $this->createOrderStatus();

		if ( is_writable( dirname(__FILE__)  ) ){
			$hipay_logs_desc = $this->l('If enabled, check the logs folder on the module folder for daily logs.');
		} else {
			$hipay_logs_desc = $this->l('Unable to create logs folder. Please check the module folder permissions.');	
		}
		
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Account details'),
                    'icon' => 'icon-envelope'
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Sandbox'),
                        'name' => 'HIPAY_MBWAY_SANDBOX',
                        'is_bool' => true,
                        'hint' => $this->l('Activate to use test platform.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            )
                        ),
                    ),
                    array(
                        'type' => 'select',
                        'required' => true,
                        'label' => $this->l('Entity'),
                        'name' => 'HIPAY_MBWAY_ENTITY',
                        'options' => array(
                            'query' => $options = array(
                        array(
                            'id_option' => 11249, // The value of the 'value' attribute of the <option> tag.
                            'name' => '11249', // The value of the text content of the  <option> tag.
                        ),
                        array(
                            'id_option' => 10241,
                            'name' => '10241/12029',
                        ),
                            ),
                            'id' => 'id_option',
                            'name' => 'name',
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Account Username'),
                        'name' => 'HIPAY_MBWAY_USERNAME',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Account Password'),
                        'name' => 'HIPAY_MBWAY_PASSWORD',
                        'required' => true
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Account Category'),
                        'name' => 'HIPAY_MBWAY_CATEGORY',
                        'desc' => $this->l('Category ID provided by Hipay.'),
                        'required' => true
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable Log'),
                        'name' => 'HIPAY_MBWAY_LOGS',
                        'is_bool' => true,
                        'hint' => $this->l('Enable module logs.'),
                        'desc' => $hipay_logs_desc,
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            )
                        ),
                    ),                    
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;
        $this->fields_form = array();
        $helper->id = (int) Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure='
                . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form));
    }

    public function getConfigFieldsValues() {

        return array(
            'HIPAY_MBWAY_SANDBOX' 	=> Tools::getValue('HIPAY_MBWAY_SANDBOX', 	Configuration::get('HIPAY_MBWAY_SANDBOX')),
            'HIPAY_MBWAY_USERNAME' 	=> Tools::getValue('HIPAY_MBWAY_USERNAME', 	Configuration::get('HIPAY_MBWAY_USERNAME')),
            'HIPAY_MBWAY_PASSWORD' 	=> Tools::getValue('HIPAY_MBWAY_PASSWORD', 	Configuration::get('HIPAY_MBWAY_PASSWORD')),
            'HIPAY_MBWAY_CATEGORY' 	=> Tools::getValue('HIPAY_MBWAY_CATEGORY', 	Configuration::get('HIPAY_MBWAY_CATEGORY')),
            'HIPAY_MBWAY_ENTITY' 	=> Tools::getValue('HIPAY_MBWAY_ENTITY', 	Configuration::get('HIPAY_MBWAY_ENTITY')),
            'HIPAY_MBWAY_LOGS' 		=> Tools::getValue('HIPAY_MBWAY_LOGS', 		Configuration::get('HIPAY_MBWAY_LOGS')),
        );
    }

    public function getTemplateVarInfos() {

        $cart = $this->context->cart;
        $total = sprintf(
                $this->l('%1$s (tax incl.)'), Tools::displayPrice($cart->getOrderTotal(true, Cart::BOTH))
        );

        return array(
            'total' => $total,
            'cart' => $cart,
        );
    }
}
