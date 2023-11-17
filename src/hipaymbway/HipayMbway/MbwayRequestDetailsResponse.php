<?php

namespace HipayMbway;

use HipayMbway\MbwayRequestResponse;
use HipayMbway\MbwWayPaymentDetailsResult;

/**
 * Description of MbwayRequestDetailsResponse
 *
 * @author hipay.pt
 */
class MbwayRequestDetailsResponse extends MbwayRequestResponse {

    private $MBWayPaymentDetails;

    function __construct($response) {
        $this->ErrorCode = $response->ErrorCode;
        $this->Success = $response->Success;
        $this->ErrorDescription = $response->ErrorDescription;
        $this->MBWayPaymentDetails = new MbwayPaymentDetailsResult($response->MBWayPaymentDetails);
    }

    function get_MBWayPaymentDetails() {
        return $this->MBWayPaymentDetails;
    }

}
