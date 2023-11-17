<?php

namespace HipayMbway;
/**
 * Description of MbwayRequestResponse
 *
 * @author hipay.pt
 */
class MbwayRequestResponse {

    protected $Success;
    protected $ErrorCode;
    protected $ErrorDescription;
   
    function get_Success() {
        return $this->Success;
    }

    function get_ErrorDescription() {
        return $this->ErrorDescription;
    }

    function get_ErrorCode() {
        return $this->ErrorCode;
    }

}
