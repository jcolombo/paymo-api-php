<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/9/20, 12:50 PM
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
     * If the API request was successful or not (could be from cache or live, etc) but it came back with a valid result
     * that can be processed by the package
     *
     * @var bool
     */
    public $success = false;

    /**
     * The JSON body response from the request to be processed by the package based on the type of call made
     * This should always be an array of stdClass objects (it may be empty, contain just one item if a single entity
     * was requested, or it could be multiple entries of the requested resource type
     *
     * @var stdClass[] | null
     */
    public $body = null;

    /**
     * The response code from the API remote call (Will be 200 on normal success)
     *
     * @var null
     */
    public $responseCode = null;

    /**
     * The plain text response text for the particular responseCode sent back (Will be "OK" on a normal success)
     *
     * @var null
     */
    public $responseReason = null;

    /**
     * The amount of time in decimal number form from the start of the remote call until the response came back from
     * the API
     *
     * @var null
     */
    public $responseTime = null;

    /**
     * The original RequestAbstraction object that was sent to the execution handler for processing the request
     *
     * @var RequestAbstraction | null
     */
    public $request = null;

    /**
     * The cleaned up, scrubbed, and processed result stdClass or stdClass[] to be used by the caller on success
     *
     * @var null | array | object
     */
    public $result = null;

    /**
     * Validates the BODY from the API response to make sure it contains a property of a specific key and minimum
     * number of objects that are expected to be considered successful (Defaults to 0).
     * Returns True : If it has a success of true, a body exists, and the body is an array (of 0 or more items) and it
     * meets the minimum count needed for the caller to consider it successful
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