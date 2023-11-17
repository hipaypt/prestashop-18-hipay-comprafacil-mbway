<?php

namespace HipayMbway;

use HipayMbway\MbwayRequestResponse;
use HipayMbway\MbwWayPaymentDetailsResult;

/**
 * Description of MbwayRequestTransactionResponse
 *
 * @author hipay.pt
 */
class MbwayRequestTransactionResponse extends MbwayRequestResponse {

    private $MBWayPaymentOperationResult;

    function __construct($response) {
        $this->ErrorCode = $response->ErrorCode;
        $this->Success = $response->Success;
        $this->ErrorDescription = $response->ErrorDescription;
        $this->MBWayPaymentOperationResult = new MbwayPaymentDetailsResult($response->MBWayPaymentOperationResult);
    }

    function get_MBWayPaymentOperationResult() {
        return $this->MBWayPaymentOperationResult;
    }

}
