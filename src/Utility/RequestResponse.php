<?php

namespace Jcolombo\PaymoApiPhp\Utility;

class RequestResponse
{
    public $success = false;
    public $body = null;
    public $responseCode = null;
    public $responseReason = null;

    public function validBody($key, $minQty=0) {
        return $this->success
        && $this->body
        && is_array($this->body->$key)
        && count($this->body->$key) >= $minQty;
    }
}