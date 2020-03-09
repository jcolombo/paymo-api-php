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

namespace Jcolombo\PaymoApiPhp;

use Exception;
use GuzzleHttp\Client as PaymoGuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use Jcolombo\PaymoApiPhp\Utility\RequestAbstraction;
use Jcolombo\PaymoApiPhp\Utility\RequestResponse;

/**
 * When true, some additional development checks, validation, debugging output, etc will be enabled
 * This should be FALSE in production to avoid any accidental key exposure or lag/slowdown
 */
define('PAYMO_DEVELOPMENT_MODE', true);

/**
 *  Paymo
 *  Base class for connecting to the Paymo App API and managing the related objects/data
 *
 * @author Joel Colombo
 */
class Paymo
{

    /**
     * @var array Singleton collection of established API connections (one per apiKey)
     */
    private static $connections = [];

    /**
     * @var bool
     */
    public $useCache = false;

    /**
     * @var bool
     */
    public $useLogging = false;

    /**
     * @var String|null
     */
    public $connectionName = null;

    /**
     * @var String|null
     */
    protected $apiKey = null;

    /**
     * @var String|null
     */
    protected $connectionUrl = null;

    /**
     * Private constructor for new connection instances from the singleton connect calls
     *
     * @param string $apiKey         The API key for creating this connection instance
     * @param string $connectionName The connection name
     * @param string $connectionUrl  The base URL for the Paymo API
     *
     * @throws Exception
     */
    private function __construct($apiKey, $connectionUrl, $connectionName)
    {
        if (!$connectionUrl) {
            throw new Exception("No Paymo API connection.url is set in the configuration file");
        }
        $this->apiKey = $apiKey;
        $this->connectionName = $connectionName;
        $this->connectionUrl = $connectionUrl;
        //$bar = "*****************************************************************************************************\n";
        //Log::getLog()->log($this->logging, "NEW CONNECTION TO PAYMO {$this->connectionName} : {$connectionUrl}", "\n".$bar, $bar);
    }

    /**
     * Static method to create or retrieve a connection
     *
     * @param string|string[]|null $apiKeyUser     The API key for this connection, An array with 2 values
     *                                             [username,password] can be passed for direct login (not recommended)
     * @param bool|null            $useLogging     to determine if this connection will write to the log
     * @param string|null          $connectionName An optional friendly name for the connection (mostly for logging)
     * @param string|null          $connectionUrl  An alternative base URL for the Paymo API (if null, uses default)
     *
     * @throws Exception If no connection was previously setup with an apiKey
     * @return Paymo
     */
    public static function connect($apiKeyUser = null, $useLogging = null, $connectionName = null, $connectionUrl = null
    ) {
        if (is_array($apiKeyUser)) {
            if (count($apiKeyUser) === 2) {
                $apiKey = "{$apiKeyUser[0]}::{$apiKeyUser[1]}";
            } else {
                throw new Exception("An invalid username/password array was sent to the connection");
            }
        } else {
            $apiKey = $apiKeyUser;
        }
        if (is_null($apiKey)) {
            if (count(self::$connections) < 1) {
                throw new Exception("'NULL API KEY : Cannot get connection that has not been established yet. Please insure at least one API KEY connection has been established first.'");
            }
            // If key was not sent, default to using the first connection that exists
            $apiKey = array_shift(array_keys(self::$connections));
        }
        if (!isset(self::$connections) || !is_array(self::$connections)) {
            self::$connections = [];
        }
        if (is_null($connectionUrl)) {
            $connectionUrl = Configuration::get('connection.url');
        }
        if (is_null($connectionName)) {
            $connectionName = Configuration::get('connection.defaultName').'-'.rand(100000, 999999).'-'.microtime(true);
        }
        if (!isset(self::$connections[$apiKey])) {
            self::$connections[$apiKey] = new static($apiKey, $connectionUrl, $connectionName);
            self::$connections[$apiKey]->useLogging = !!$useLogging;
            if (Configuration::get('connection.verify')) {
                // @todo Run connection check call to API to test credentials and API up-status, throw error on fail
                // Run a simple call to the API to get a valid response
                // Throw an Exception if it fails
                //self::$connections[$apiKey]->executeRequest('account', 'head');
            }
        }

        return self::$connections[$apiKey];
    }

    /**
     * The connection execution method, called by the Request objects
     *
     * @param RequestAbstraction $request An instance of the standardized object to insure all values exist for proper
     *                                    request
     * @param array              $options Set of options to configure request and response handling
     *
     * @throws GuzzleException
     * @return RequestResponse
     */
    public function execute(RequestAbstraction $request, $options = [])
    {
        $client = new PaymoGuzzleClient([
                                            'base_uri' => $this->connectionUrl,
                                            'timeout' => 5.0,
                                        ]);
        $headers = [];
        $props = [];
        $query = [];

        // Define Headers to send to Paymo
        $headers[] = ['Accept' => 'application/json'];
        $props['headers'] = $headers;

        // Define Auth property (apiKey or user/password)
        $aP = explode('::', $this->apiKey, 2);
        $props['auth'] = [$aP[0], isset($aP[1]) ? $aP[1] : 'apiKeyUsed'];

        // Compile the query string options
        if (!is_null($request->include)) {
            $query['include'] = $request->include;
        }
        if (!is_null($request->where)) {
            $query['where'] = $request->where;
        }
        if (count($query) > 0) {
            $props['query'] = $query;
        }

        //var_dump($props); //exit;

        // Run the GUZZLE request to the live API
        $request_start = microtime(true);
        $guzzleResponse = $client->request(
            $request->method,
            $request->resourceUrl,
            $props
        );
        $request_end = microtime(true);
        $request_time = $request_end - $request_start;
        //var_dump($request_time); exit;

        // Construct normalized response for returning to the caller for processing (REQUEST object)
        $response = new RequestResponse();
        $response->request = $request;
        $response->responseCode = $guzzleResponse->getStatusCode();
        $response->responseReason = $guzzleResponse->getReasonPhrase();
        $response->responseTime = $request_time;
        $response->body = json_decode($guzzleResponse->getBody()->getContents());
        $response->result = null;
        $response->success = ($response->responseCode >= 200 && $response->responseCode <= 299);

        //var_dump($response); exit;

        return $response;

//        foreach ($response->getHeaders() as $name => $values) {
//            echo $name . ': ' . implode(', ', $values) . "\r\n";
//        }
//        var_dump($response->getStatusCode(), $response->getReasonPhrase(), $response->getBody());

        //$body = json_decode($response->getBody()->getContents());
        //var_dump($body);
    }

}