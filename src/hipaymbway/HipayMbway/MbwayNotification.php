<?php
namespace HipayMbway; 

/**
 * Description of MbwayNotification
 *
 * @author hipay.pt
 */

class MbwayNotification {
    private $OperationId;
    private $Amount;
    private $Phone;
    private $Description;
    private $StatusCode;
    private $StatusDescription;
    private $StatusDescriptionDetails;
    private $ClientExternalReference;
    
	function __construct($response) {
        $responseObj = json_decode($response, false);
        if (json_last_error() != JSON_ERROR_NONE) {
            return false;
        }
        $this->set_OperationId($responseObj->OperationId);
        $this->set_Amount($responseObj->Amount);
        $this->set_Phone($responseObj->Phone);
        $this->set_Description($responseObj->Description);
        $this->set_StatusCode($responseObj->StatusCode);
        $this->set_StatusDescription($responseObj->StatusDescription);
        $this->set_StatusDescriptionDetails($responseObj->StatusDescriptionDetails);
        $this->set_ClientExternalReference($responseObj->ClientExternalReference);
    }
    function get_OperationId() {
        return $this->OperationId;
    }
    function get_Amount() {
        return $this->Amount;
    }
    function get_Phone() {
        return $this->Phone;
    }
    function get_Description() {
        return $this->Description;
    }
    function get_StatusCode() {
        return $this->StatusCode;
    }
    function get_StatusDescription() {
        return $this->StatusDescription;
    }
    function get_StatusDescriptionDetails() {
        return $this->StatusDescriptionDetails;
    }
    function get_ClientExternalReference() {
        return $this->ClientExternalReference;
    }
    function set_Amount($Amount) {
        $this->Amount = $Amount;
    }
    function set_OperationId($OperationId) {
        $this->OperationId = $OperationId;
    }
    function set_Phone($Phone) {
        $this->Phone = $Phone;
    }
    function set_Description($Description) {
        $this->Description = $Description;
    }
    function set_StatusCode($StatusCode) {
        $this->StatusCode = $StatusCode;
    }
    function set_StatusDescription($StatusDescription) {
        $this->StatusDescription = $StatusDescription;
    }
    function set_StatusDescriptionDetails($StatusDescriptionDetails) {
        $this->StatusDescriptionDetails = $StatusDescriptionDetails;
    }
    function set_ClientExternalReference($ClientExternalReference) {
        $this->ClientExternalReference = $ClientExternalReference;
    }
}
