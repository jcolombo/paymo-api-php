<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/6/20, 12:11 PM
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

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