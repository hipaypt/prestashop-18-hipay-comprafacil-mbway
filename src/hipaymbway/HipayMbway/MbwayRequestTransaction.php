<?php

namespace HipayMbway;
use HipayMbway\MbwayRequest;
/**
 * Description of MbwayRequestTransaction
 *
 * @author hipay.pt
 */
class MbwayRequestTransaction extends MbwayRequest {

    private $amount;
    private $phone;
    private $description;
    private $clientEmail;
    private $clientVATNumber;
    private $clientExternalReference;
    private $clientName;
    private $categoryId;
    private $callBackURL;

    
    //$order_id,$this->category["woocommerce_hipaymbway_category"],$callback_url);
    
    function __construct($cfMerchantId, $password, $amount, $phone, $clientEmail, $clientExternalReference, $categoryId, $callBackURL, $cfEntityOrType = 1) {
        $this->set_cfMerchantId($cfMerchantId);
        $this->set_password($password);
        $this->set_amount($amount);
        $this->set_phone($phone);
        $this->set_clientEmail($clientEmail);
        $this->set_clientExternalReference($clientExternalReference);
        $this->set_categoryId($categoryId);
        $this->set_callBackURL($callBackURL);
        $this->set_cfEntityOrType($cfEntityOrType);        
    }
            
    function get_amount() {
        return $this->amount;
    }

    function get_phone() {
        return $this->phone;
    }

    function get_description() {
        return $this->description;
    }

    function get_clientEmail() {
        return $this->clientEmail;
    }

    function get_clientVATNumber() {
        return $this->clientVATNumber;
    }

    function get_clientExternalReference() {
        return $this->clientExternalReference;
    }

    function get_clientName() {
        return $this->clientName;
    }

    function get_categoryId() {
        return $this->categoryId;
    }

    function get_callBackURL() {
        return $this->callBackURL;
    }

    function set_amount($amount) {
        $this->amount = $amount;
    }

    function set_phone($phone) {
        $this->phone = $phone;
    }

    function set_description($description) {
        $this->description = $description;
    }

    function set_clientEmail($clientEmail) {
        $this->clientEmail = $clientEmail;
    }

    function set_clientVATNumber($clientVATNumber) {
        $this->clientVATNumber = $clientVATNumber;
    }

    function set_clientExternalReference($clientExternalReference) {
        $this->clientExternalReference = $clientExternalReference;
    }

    function set_clientName($clientName) {
        $this->clientName = $clientName;
    }

    function set_categoryId($categoryId) {
        $this->categoryId = $categoryId;
    }

    function set_callBackURL($callBackURL) {
        $this->callBackURL = $callBackURL;
    }

}
