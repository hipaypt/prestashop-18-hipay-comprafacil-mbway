<?php

namespace HipayMbway;

/**
 * Description of MbwayRequest
 *
 * @author hipay.pt
 */
class MbwayRequest {

    protected $cfMerchantId;
    protected $password;
    protected $cfEntityOrType;

    function get_cfMerchantId() {
        return $this->cfMerchantId;
    }

    function get_password() {
        return $this->password;
    }

    function get_cfEntityOrType() {
        return $this->cfEntityOrType;
    }

    function set_cfMerchantId($cfMerchantId) {
        $this->cfMerchantId = $cfMerchantId;
    }

    function set_password($password) {
        $this->password = $password;
    }

    function set_cfEntityOrType($cfEntityOrType) {
        $this->cfEntityOrType = $cfEntityOrType;
    }

}
