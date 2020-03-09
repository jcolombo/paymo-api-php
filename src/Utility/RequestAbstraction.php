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
 * Class RequestAbstraction
 *
 * @package Jcolombo\PaymoApiPhp\Utility
 */
class RequestAbstraction
{

    /**
     * The HTTP method used in the API remote call. Valid values are GET, POST, PUT, DELETE
     *
     * @var string
     */
    public $method = 'GET';

    /**
     * The path of the resource to tack on to the end of the connections base URL.
     * For example if 'projects' is passed... it will end up calling https://www.paymoapp.com/api/projects
     * Do not include anything after the path string. Those values are added by the application
     *
     * @var string | null
     */
    public $resourceUrl = null;

    /**
     * The string property of all the pre-scrubbed ?include= parameter that will be passed to the API
     *
     * @var string | null
     */
    public $include = null;

    /**
     * The string property of all the pre-scrubbed ?where= parameter that will be passed to the API
     *
     * @var string | null
     */
    public $where = null;
}