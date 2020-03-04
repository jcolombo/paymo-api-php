<?php

namespace Jcolombo\PaymoApiPhp;

use Exception;
use GuzzleHttp\Client as PaymoGuzzleClient;
use Jcolombo\PaymoApiPhp\Utility\RequestAbstraction;
use Jcolombo\PaymoApiPhp\Utility\RequestResponse;

define('PAYMO_API_CACHE_PATH', null);
define('PAYMO_API_LOG_PATH', null);
define('PAYMO_API_DEFAULT_CONNECTION_URL', 'https://app.paymoapp.com/api/');
define('PAYMO_API_DEFAULT_CONNECTION_NAME', 'PaymoConnection');
define('PAYMO_API_RUN_CONNECTION_CHECK', false);
define('PAYMO_DEVELOPMENT_MODE', true);


/**
*  Paymo
*
*  Base class for connecting to the Paymo App
*
*  @author Joel Colombo
*/
class Paymo {

    /**
     * @var array Singleton collection of established API connections (one per apiKey)
     */
    private static $connections = array();

    /**
     * @var String|null
     */
    protected $apiKey = null;
    /**
     * @var String|null
     */
    protected $connectionUrl = null;

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
     * The connection execution method, called by the Request objects
     *
     * @param $request RequestAbstraction An instance of the standardized object to insure all values exist for proper request
     * @param $options array Set of options to configure request and response handling
     *
     * @return RequestResponse
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     */
    public function execute(RequestAbstraction $request, $options=[]) {
        $client = new PaymoGuzzleClient([
            'base_uri' => $this->connectionUrl,
            'timeout'  => 3.0,
        ]);
        $props = []; $query = [];
        $props['auth'] = [$this->apiKey, 'apiKey'];
        if (!is_null($request->includeEntities)) {
            $query['include'] = $request->includeEntities;
        }
        if (count($query) >0) { $props['query'] = $query; }

        //var_dump($props); exit;

        $guzzleResponse = $client->request(
            $request->method,
            $request->resourceUrl,
            $props
        );

        $response = new RequestResponse();
        $response -> success = true;
        $response -> body = json_decode($guzzleResponse->getBody()->getContents());

        //var_dump($response); exit;

        return $response;

//        foreach ($response->getHeaders() as $name => $values) {
//            echo $name . ': ' . implode(', ', $values) . "\r\n";
//        }
//        var_dump($response->getStatusCode(), $response->getReasonPhrase(), $response->getBody());

        //$body = json_decode($response->getBody()->getContents());
        //var_dump($body);
    }

    /**
     * Static method to create or retrieve a connection
     *
     * @param $apiKey String | null The API key for this connection
     * @param $useLogging Boolean | null to determine if this connection will write to the log
     * @param $connectionName String | null An optional friendly name for the connection (mostly for logging)
     * @param $connectionUrl String | null An alternative base URL for the Paymo API (if null, uses default)
     *
     * @throws Exception If no connection was previously setup with an apiKey
     *
     * @return Paymo
     */
    static public function connect($apiKey=null, $useLogging=null, $connectionName=null, $connectionUrl=null) {
        if (is_null($apiKey)) {
            if (count(self::$connections)<1) {
                throw new Exception("'NULL API KEY : Cannot get connection that has not been established yet. Please insure at least one API KEY connection has been established first.'");
            }
            // If key was not sent, default to using the first connection that exists
            $apiKey = array_shift(array_keys(self::$connections));
        }
        if(!isset(self::$connections) || !is_array(self::$connections)) {
            self::$connections = array();
        }
        if (is_null($connectionUrl)) {
            $connectionUrl = PAYMO_API_DEFAULT_CONNECTION_URL;
        }
        if (is_null($connectionName)) {
            $connectionName = PAYMO_API_DEFAULT_CONNECTION_NAME;
        }
        if(!isset(self::$connections[$apiKey])) {
            self::$connections[$apiKey] = new static($apiKey, $connectionUrl, $connectionName);
            self::$connections[$apiKey]->useLogging = !!$useLogging;
            if (PAYMO_API_RUN_CONNECTION_CHECK) {
                // Run a simple call to the API to get a valid response
                // Throw an Exception if it fails
                //self::$connections[$apiKey]->executeRequest('account', 'head');
            }
        }
        return self::$connections[$apiKey];
    }

    /**
     * Private constructor for new connection instances from the singleton connect calls
     *
     * @param $apiKey String The API key for creating this connection instance
     * @param $connectionName String The connection name
     * @param $connectionUrl String The base URL for the Paymo API
     */
    private function __construct($apiKey, $connectionUrl, $connectionName)
    {
        $this->apiKey = $apiKey;
        $this->connectionName = $connectionName;
        $this->connectionUrl = $connectionUrl;
        //$bar = "*****************************************************************************************************\n";
        //Log::getLog()->log($this->logging, "NEW CONNECTION TO PAYMO {$this->connectionName} : {$connectionUrl}", "\n".$bar, $bar);
    }



}