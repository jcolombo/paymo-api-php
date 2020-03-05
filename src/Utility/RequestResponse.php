<?php

namespace Jcolombo\PaymoApiPhp\Utility;

/**
 * Class RequestResponse
 *
 * @package Jcolombo\PaymoApiPhp\Utility
 */
class RequestResponse
{
    /**
     * @var bool
     */
    public $success = false;

    /**
     * @var null
     */
    public $body = null;

    /**
     * @var null
     */
    public $responseCode = null;

    /**
     * @var null
     */
    public $responseReason = null;

    /**
     * @var null
     */
    public $responseTime = null;

    /**
     * Validates the BODY from the API response to make sure it contains a property of a specific key and minimum
     * number of objects
     *
     * @param string $key    The object property from the API response that contains the actual usable data
     *                       object/array
     * @param int    $minQty The minimum number of objects under the $key property to be considered a successful
     *                       response
     *
     * @return bool
     */
    public function validBody($key, $minQty = 0)
    {
        return $this->success
            && $this->body
            && is_array($this->body->$key)
            && count($this->body->$key) >= $minQty;
    }
}