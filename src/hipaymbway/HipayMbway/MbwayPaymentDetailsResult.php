<?php

namespace HipayMbway;

/**
 * Description of MbwayPaymentDetailsResult
 *
 * @author hipay.pt
 */
class MbwayPaymentDetailsResult {

    private $StatusCode;
    private $StatusDescription;
    private $OperationId;
    private $StatusDescriptionDetail;
    private $Amount;
    private $Phone;
    private $Description;
    private $ValidRequest;
    private $CategoryId;
    private $RequestDate;
    private $UpdateStatusDate;

    function __construct($details) {
        if (isset($details->StatusCode))
            $this->StatusCode = $details->StatusCode;
        if (isset($details->StatusDescription))
            $this->StatusDescription = $details->StatusDescription;
        if (isset($details->OperationId))
            $this->OperationId = $details->OperationId;
        if (isset($details->StatusDescriptionDetail))
            $this->StatusDescriptionDetail = $details->StatusDescriptionDetail;

        if (isset($details->OperationId))
            $this->OperationId = $details->OperationId;
        if (isset($details->OperationId))
            $this->OperationId = $details->OperationId;
        if (isset($details->OperationId))
            $this->OperationId = $details->OperationId;
        if (isset($details->OperationId))
            $this->OperationId = $details->OperationId;
        if (isset($details->OperationId))
            $this->OperationId = $details->OperationId;
        if (isset($details->RequestDate))
            $this->RequestDate = $details->RequestDate;
        if (isset($details->UpdateStatusDate))
            $this->UpdateStatusDate = $details->UpdateStatusDate;
    }

    function get_StatusCode() {
        return $this->StatusCode;
    }

    function get_StatusDescription() {
        return $this->StatusDescription;
    }

    function get_OperationId() {
        return $this->OperationId;
    }

    function get_StatusDescriptionDetail() {
        return $this->StatusDescriptionDetail;
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

    function get_ValidRequest() {
        return $this->ValidRequest;
    }

    function get_CategoryId() {
        return $this->CategoryId;
    }

    function get_RequestDate() {
        return $this->RequestDate;
    }

    function get_UpdateStatusDate() {
        return $this->UpdateStatusDate;
    }

}
