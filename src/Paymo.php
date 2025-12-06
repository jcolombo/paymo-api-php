<?php
/**
 * Paymo API PHP SDK
 *
 * A comprehensive PHP SDK for interacting with the Paymo project management API.
 * This package provides an object-oriented interface for all Paymo API operations
 * including projects, tasks, time entries, invoices, and more.
 *
 * @package    Jcolombo\PaymoApiPhp
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020-2025 Joel Colombo / 360 PSG, Inc.
 * @license    MIT License
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 * @see        https://github.com/paymoapp/api Official Paymo API Documentation
 *
 * MIT License
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
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\ServerException;
use Jcolombo\PaymoApiPhp\Cache\Cache;
use Jcolombo\PaymoApiPhp\Utility\Log;
use Jcolombo\PaymoApiPhp\Utility\RequestAbstraction;
use Jcolombo\PaymoApiPhp\Utility\RequestResponse;
use JsonException;
use RuntimeException;

/**
 * Global development mode flag.
 *
 * When set to TRUE, enables additional validation, debugging output, and development-only
 * checks throughout the SDK. This should be set to FALSE in production environments to
 * avoid performance overhead and prevent accidental exposure of sensitive information.
 *
 * @const bool PAYMO_DEVELOPMENT_MODE
 */
define('PAYMO_DEVELOPMENT_MODE', true);

/**
 * Paymo Connection Manager
 *
 * The primary entry point for the Paymo API PHP SDK. This class manages API connections
 * and handles all HTTP communication with the Paymo REST API. It implements a singleton
 * pattern per API key, allowing multiple connections to different Paymo accounts within
 * the same application.
 *
 * ## Features
 * - Singleton pattern per API key (prevents duplicate connections)
 * - Support for API key and username/password authentication
 * - Built-in request caching to reduce API calls
 * - Comprehensive request/response logging
 * - Automatic rate limiting (1-second delay between requests)
 * - Guzzle HTTP client integration with proper error handling
 *
 * ## Basic Usage
 *
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
 *
 * // Establish a connection with your API key
 * $paymo = Paymo::connect('your-api-key-here');
 *
 * // Now you can use entity classes to interact with the API
 * $projects = Project::list()->fetch();
 * ```
 *
 * ## Multiple Connections
 *
 * ```php
 * // Connect to multiple Paymo accounts
 * $account1 = Paymo::connect('api-key-for-account-1', null, 'Account1');
 * $account2 = Paymo::connect('api-key-for-account-2', null, 'Account2');
 *
 * // Use specific connection for operations
 * $project = new Project($account1);
 * $project->fetch(12345);
 * ```
 *
 * ## Authentication Methods
 *
 * ```php
 * // Method 1: API Key (Recommended)
 * $paymo = Paymo::connect('your-api-key');
 *
 * // Method 2: Username/Password (Not recommended for production)
 * $paymo = Paymo::connect(['username@example.com', 'password']);
 * ```
 *
 * @package Jcolombo\PaymoApiPhp
 * @author  Joel Colombo <jc-dev@360psg.com>
 * @since   0.1.0
 */
class Paymo
{

  /**
   * Singleton collection of established API connections.
   *
   * Stores one Paymo connection instance per unique API key. This prevents
   * creating duplicate connections to the same account and allows retrieval
   * of existing connections without re-authentication.
   *
   * The array key is the API key (or "username::password" for user auth),
   * and the value is the Paymo connection instance.
   *
   * @var array<string, Paymo> Associative array of API key => Paymo instance
   */
  private static array $connections = [];

  /**
   * Cache enablement flag for this connection.
   *
   * When TRUE and caching is enabled globally in configuration, API responses
   * will be cached to reduce redundant API calls. Individual requests can
   * override this with the 'skipCache' option.
   *
   * @var bool TRUE if caching should be used for this connection
   * @see Cache For cache configuration and management
   */
  public bool $useCache = false;

  /**
   * Logging enablement flag for this connection.
   *
   * When TRUE and logging is enabled globally in configuration, API requests
   * and responses will be logged for debugging and monitoring purposes.
   *
   * @var bool TRUE if logging should be used for this connection
   * @see Log For logging configuration and output
   */
  public bool $useLogging = false;

  /**
   * Human-readable identifier for this connection.
   *
   * Used in logging output to distinguish between multiple connections.
   * Auto-generated if not provided during connect(), using format:
   * "PaymoApi-***{last5chars}-#{random6digits}"
   *
   * @var string|null The connection name or NULL if not set
   */
  public ?string $connectionName = null;

  /**
   * The API key used for authentication with Paymo.
   *
   * For API key auth: Contains the raw API key string.
   * For user/pass auth: Contains "username::password" format.
   *
   * @var string|null The authentication credential string
   */
  protected ?string $apiKey = null;

  /**
   * The base URL for all API requests.
   *
   * Defaults to "https://app.paymoapp.com/api/" but can be overridden
   * via configuration for testing or custom deployments.
   *
   * @var string|null The Paymo API base URL
   */
  protected ?string $connectionUrl = null;

  /**
   * Private constructor - use Paymo::connect() to create instances.
   *
   * Initializes a new Paymo connection with the provided credentials and configuration.
   * This is called internally by the connect() method and should not be called directly.
   *
   * @param string $apiKey         The API key or "username::password" string
   * @param string $connectionUrl  The base URL for the Paymo API
   * @param string $connectionName Human-readable name for this connection
   *
   * @throws Exception If no connection URL is configured
   *
   * @internal Use Paymo::connect() instead of direct instantiation
   */
  private function __construct(string $apiKey, string $connectionUrl, string $connectionName) {
    if (!$connectionUrl) {
      throw new RuntimeException("No Paymo API connection.url is set in the configuration file");
    }
    $this->apiKey = $apiKey;
    $this->connectionName = $connectionName;
    $this->connectionUrl = $connectionUrl;
  }

  /**
   * Create or retrieve a Paymo API connection.
   *
   * This is the primary method for establishing connections to the Paymo API.
   * It implements a singleton pattern per API key - calling connect() with the
   * same API key returns the existing connection rather than creating a new one.
   *
   * ## Usage Examples
   *
   * ```php
   * // Create a new connection with API key
   * $paymo = Paymo::connect('your-api-key');
   *
   * // Retrieve the default (first) connection
   * $paymo = Paymo::connect();
   *
   * // Create connection with username/password (not recommended)
   * $paymo = Paymo::connect(['user@example.com', 'password']);
   *
   * // Create named connection with logging enabled
   * $paymo = Paymo::connect('api-key', true, 'ProductionAccount');
   *
   * // Create connection with custom API URL
   * $paymo = Paymo::connect('api-key', false, 'Test', 'https://test.paymoapp.com/api/');
   * ```
   *
   * @param string|string[]|null $apiKeyUser     Authentication credential:
   *                                             - string: API key (recommended)
   *                                             - array: ['username', 'password'] (not recommended)
   *                                             - null: Returns the first existing connection
   * @param bool|null            $useLogging     Override logging for this connection:
   *                                             - true: Enable logging
   *                                             - false: Disable logging
   *                                             - null: Use configuration default
   * @param string|null          $connectionName Human-readable name for logging/debugging.
   *                                             If null, auto-generates a unique name.
   * @param string|null          $connectionUrl  Override the API base URL.
   *                                             If null, uses configuration default.
   *
   * @throws Exception If called with null when no connections exist
   * @throws Exception If username/password array doesn't have exactly 2 elements
   *
   * @return Paymo The connected Paymo instance (new or existing)
   *
   * @since 0.1.0
   */
  public static function connect($apiKeyUser = null, bool $useLogging = null, string $connectionName = null, string $connectionUrl = null
  ) : Paymo {
    if (is_array($apiKeyUser)) {
      if (count($apiKeyUser) === 2) {
        $apiKey = "$apiKeyUser[0]::$apiKeyUser[1]";
      } else {
        Log::getLog()->log(null,
                           'ERROR: API Connection attempted user/pass key that did not contain exactly two elements');
        throw new RuntimeException("An invalid username/password array was sent to the connection");
      }
    } else {
      $apiKey = $apiKeyUser;
    }
    if (is_null($apiKey)) {
      if (count(self::$connections) < 1) {
        Log::getLog()->log(null, 'ERROR: API Key was NULL and could not be established');
        throw new RuntimeException("'NULL API KEY : Cannot get connection that has not been established yet. Please insure at least one API KEY connection has been established first.'");
      }
      // If key was not sent, default to using the first connection that exists
      $connectionKeys = array_keys(self::$connections);
      $apiKey = array_shift($connectionKeys);
    }
    if (!isset(self::$connections) || !is_array(self::$connections)) {
      self::$connections = [];
    }
    if (is_null($connectionUrl)) {
      $connectionUrl = Configuration::get('connection.url');
    }
    if (is_null($connectionName)) {
      $connectionSuffix = '***'.substr($apiKey, -5, 5).'-#'.random_int(100000, 999999);
      $connectionName = Configuration::get('connection.defaultName').'-'.$connectionSuffix;
    }
    if (!isset(self::$connections[$apiKey])) {
      self::$connections[$apiKey] = new static($apiKey, $connectionUrl, $connectionName);
      self::$connections[$apiKey]->useLogging = (bool)Configuration::get('enabled.logging');
      if (!is_null($useLogging)) {
        self::$connections[$apiKey]->useLogging = !!$useLogging;
      }
      self::$connections[$apiKey]->useCache = !!Configuration::get('enabled.cache');
      Log::getLog()->onlyIf(Configuration::get('log.connections'))->log(self::$connections[$apiKey], Log::obj('NEW_CONNECTION', null));
//      if (Configuration::get('connection.verify')) {
//        // @todo Run connection check call to API to test credentials and API up-status, throw error on fail
//        // Run a simple call to the API to get a valid response
//        // Throw an Exception if it fails
//        //self::$connections[$apiKey]->executeRequest('account', 'head');
//      }
    }

    Log::getLog()->onlyIf(Configuration::get('log.connections'))->log(self::$connections[$apiKey], Log::obj('USE_CONNECTION', null));

    return self::$connections[$apiKey];
  }

    /**
     * Execute an API request through this connection.
     *
     * This is the core method that handles all HTTP communication with the Paymo API.
     * It is called internally by the Request class methods (fetch, create, update, delete)
     * and should not typically be called directly by application code.
     *
     * ## Request Flow
     * 1. Check cache for existing response (if caching enabled and not skipped)
     * 2. Build Guzzle request with authentication and parameters
     * 3. Apply 1-second rate limiting delay
     * 4. Execute HTTP request to Paymo API
     * 5. Parse response and handle errors
     * 6. Store successful responses in cache (if caching enabled)
     * 7. Return structured RequestResponse object
     *
     * ## Options
     * - `skipCache` (bool): When TRUE, bypasses cache lookup but still stores response
     *
     * ## Error Handling
     * - 4xx errors: Captured as failed response with error message
     * - 5xx errors: Captured as failed response with server error details
     * - Network errors: Throws GuzzleException (currently exits - needs improvement)
     *
     * @param RequestAbstraction $request Structured request object containing:
     *                                    - method: HTTP method (GET, POST, PUT, DELETE)
     *                                    - resourceUrl: API endpoint path
     *                                    - include: Related entities to include
     *                                    - where: Filter conditions
     *                                    - data: Request body data (for POST/PUT)
     *                                    - files: File uploads (for multipart requests)
     * @param array              $options Request options:
     *                                    - skipCache (bool): Bypass cache lookup
     *
     * @throws JsonException
     * @return RequestResponse Structured response containing:
     *                         - success: Whether request succeeded (2xx status)
     *                         - responseCode: HTTP status code
     *                         - responseReason: Status message or error description
     *                         - responseTime: Request duration in seconds
     *                         - headers: Response headers
     *                         - body: Parsed JSON response body
     *                         - result: Extracted result data (populated by caller)
     *
     * @see      Request For high-level API operations
     * @see      RequestAbstraction For request structure details
     * @see      RequestResponse For response structure details
     * @internal Called by Request class methods, not for direct application use
     */
  public function execute(RequestAbstraction $request, array $options = []) : RequestResponse
  {
    Log::getLog()->log($this, Log::obj('START_REQUEST', $request));

    // Check cache before making API call (if caching is enabled)
    $skipCache = isset($options['skipCache']) && $options['skipCache'];
    $cacheKey = null;
    if ($this->useCache) {
      $cacheKey = $request->makeCacheKey()->cacheKey;
      if ($cacheKey && !$skipCache) {
        Log::getLog()->log($this, 'CHECK CACHE KEY: '.$cacheKey);
        $cachedResponse = Cache::fetch($cacheKey);
        if ($cachedResponse) {
          if (is_object($cachedResponse) && $cachedResponse->isCacheMeta) {
            $timeLeft = Cache::formatDuration($cachedResponse->lifeLeft);
            Log::getLog()->log($this, "-- RETURN VALID CACHE (Still Good For $timeLeft)");

            return $cachedResponse->content;
          }
          Log::getLog()->log($this, '-- RETURN VALID CACHE');

          return $cachedResponse;
        }

          Log::getLog()->log($this, '-- NO CACHE FOUND');
      }
    }

    // Initialize Guzzle HTTP client
    $client = new PaymoGuzzleClient(
      [
        'base_uri' => $this->connectionUrl,
        'timeout'  => Configuration::get('connection.timeout'),
      ]);
    $headers = [];
    $props = [];
    $query = [];

    // Rate limiting: 1-second delay between requests to prevent API overload
    // Paymo API allows 5 requests per 5 seconds, this ensures compliance
    sleep(1); // @todo Implement smarter queue mechanism for burst requests

    // Set request headers
    $headers[] = ['Accept' => 'application/json'];
    $props['headers'] = $headers;

    // Set authentication (API key or username/password)
    $aP = explode('::', $this->apiKey, 2);
    $props['auth'] = [$aP[0], $aP[1] ?? 'apiKeyUsed'];

    // Build query string parameters
    if (!is_null($request->include)) {
      $query['include'] = $request->include;
    }
    if (!is_null($request->where)) {
      $query['where'] = $request->where;
    }
    if (count($query) > 0) {
      $props['query'] = $query;
    }

    // Handle JSON body for POST/PUT requests
    if ($request->mode === 'json' && ($request->method === 'POST' || $request->method === 'PUT') && is_array($request->data) && count($request->data) > 0) {
      $props['json'] = $request->data;
    }

    // Handle multipart/form-data for file uploads
    if ($request->mode === 'multipart' || ($request->method === 'POST' && is_array($request->files) && count($request->files) > 0)) {
      $props['multipart'] = [];
      $openFiles = [];
      foreach ($request->files as $k => $file) {
        $fHandler = fopen($file, 'rb');
        $openFiles[] = $fHandler;
        $props['multipart'][] = ['name' => $k, 'contents' => $fHandler];
      }
      if ($request->data && is_array($request->data)) {
        foreach ($request->data as $k => $v) {
          $props['multipart'][] = ['name' => $k, 'contents' => $v];
        }
      }
    }

    // Execute the HTTP request
    $request_start = microtime(true);
    $response = new RequestResponse();
    $response->request = $request;
    try {
      $guzzleResponse = $client->request(
        $request->method,
        $request->resourceUrl,
        $props
      );
      $response->responseCode = $guzzleResponse->getStatusCode();
      $response->responseReason = $guzzleResponse->getReasonPhrase();
      $response->headers = $guzzleResponse->getHeaders();
      $response->body = json_decode($guzzleResponse->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
    } catch (ServerException|ClientException $e) {
      // Handle 4xx and 5xx HTTP errors
      $msg = $e->getResponse()->getBody()->getContents();
      if ($msg) {
        $msg = json_decode($msg, false, 512, JSON_THROW_ON_ERROR)->message;
      }
      $response->body = null;
      $response->responseCode = $e->getCode();
      $response->responseReason = $msg;
      $response->headers = $e->getResponse()->getHeaders();
    } catch (GuzzleException $e) {
      // Handle network and other Guzzle errors
      Log::getLog()->log($this, Log::obj('GUZZLE_ERROR', $e));
      // @todo Implement proper error handler class
      echo "UNKNOWN EXCEPTION...\n";
      var_dump($e);
      exit;
    } finally {
      // Clean up and finalize response
      $request_end = microtime(true);
      $request_time = $request_end - $request_start;
      if (isset($openFiles) && is_array($openFiles)) {
        foreach ($openFiles as $fH) {
          fclose($fH);
        }
      }
      $response->responseTime = $request_time;
      $response->result = null;
      $response->success = ($response->responseCode >= 200 && $response->responseCode <= 299);

      Log::getLog()->log($this, Log::obj('RESPONSE_DONE', [
        'code'    => $response->responseCode,
        'reason'  => $response->responseReason,
        'time'    => $response->responseTime,
        'headers' => json_encode($response->headers, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
      ]));

      // Store successful responses in cache
      if ($this->useCache && $response->success && $cacheKey) {
        Log::getLog()->log($this, '-- STORE VALID CACHE');
        Cache::store($cacheKey, $response);
      }

      // Log failed responses (debugging)
      if (!$response->success) {
        echo "FAILED PAYMO RESPONSE...\n";
        var_dump($response);
      }

      return $response;
    }
  }

    /**
     * Destructor - logs connection termination when enabled.
     *
     * Called automatically when the Paymo instance is destroyed (end of script
     * or when explicitly unset). Logs the connection closure for debugging.
     *
     * @throws JsonException
     */
  public function __destruct() {
    Log::getLog()->log($this, Log::obj('KILL_CONNECTION', null));
  }

}
