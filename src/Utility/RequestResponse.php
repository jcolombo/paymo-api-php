<?php

namespace Jcolombo\PaymoApiPhp\Utility;

class RequestResponse
{
    public $success = false;
    public $body = null;
    public $responseCode = null;
    public $responseReason = null;

    /**
     * Validates the BODY from the API response to make sure it contains a property of a specific key and minimum number of objects
     *
     * @param string $key The object property from the API response that contains the actual usable data object/array
     * @param int $minQty The minimum number of objects under the $key property to be considered a successful response
     * @return bool
     */
    public function validBody($key, $minQty=0) {
        return $this->success
        && $this->body
        && is_array($this->body->$key)
        && count($this->body->$key) >= $minQty;
    }
}