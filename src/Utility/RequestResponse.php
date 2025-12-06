<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 10:48 PM
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
 *
 * ======================================================================================
 * REQUEST RESPONSE - STRUCTURED API RESPONSE CONTAINER
 * ======================================================================================
 *
 * This utility class encapsulates the response from Paymo API calls. It provides a
 * structured container for all response data including status, headers, body, timing,
 * and the processed result ready for hydration into entity objects.
 *
 * KEY FEATURES:
 * -------------
 * - Tracks success/failure status of API calls
 * - Stores raw response body and headers
 * - Captures HTTP response codes and reasons
 * - Records response timing for performance monitoring
 * - Maintains reference to original request
 * - Stores processed/scrubbed results ready for entity hydration
 * - Tracks cache key for cached responses
 *
 * TYPICAL WORKFLOW:
 * -----------------
 * 1. Request is made via Request class methods
 * 2. Paymo::execute() creates RequestResponse and populates from HTTP response
 * 3. Response is potentially cached using the cacheKey
 * 4. Results are scrubbed and stored in the result property
 * 5. Entity classes use result to hydrate resource/collection objects
 *
 * USAGE EXAMPLE:
 * --------------
 * ```php
 * // RequestResponse is typically returned from Request methods
 * $response = Request::fetch($connection, 'projects/123', []);
 *
 * if ($response->success) {
 *     $projectData = $response->result;
 *     // Hydrate into entity...
 * } else {
 *     echo "Error: " . $response->responseReason;
 *     echo "Code: " . $response->responseCode;
 * }
 *
 * // Check if response was cached
 * if ($response->fromCacheKey) {
 *     echo "Response loaded from cache";
 * }
 *
 * // Access response timing
 * echo "Request took " . $response->responseTime . " seconds";
 * ```
 *
 * @package    Jcolombo\PaymoApiPhp\Utility
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        Request Creates and returns RequestResponse objects
 * @see        RequestAbstraction The request that generated this response
 * @see        Paymo::execute() Populates response from HTTP calls
 */

namespace Jcolombo\PaymoApiPhp\Utility;

use stdClass;

/**
 * Container class for Paymo API response data.
 *
 * RequestResponse holds all information about an API response in a structured
 * format. It's created by the Request class and populated by Paymo::execute()
 * after making HTTP calls to the Paymo API.
 *
 * The class tracks both raw response data (body, headers, codes) and processed
 * data (scrubbed results ready for entity hydration). It also supports cache
 * tracking to identify when responses were loaded from cache versus live API.
 *
 * @package Jcolombo\PaymoApiPhp\Utility
 */
class RequestResponse
{
    /**
     * Populate response properties from a raw response object.
     *
     * This method hydrates the RequestResponse instance from a raw object,
     * typically used when loading cached responses. It maps properties from
     * the cached object to this instance's properties.
     *
     * @param string|null $cacheKey          The cache key this response was stored under,
     *                                       or null if not from cache
     * @param object      $rawResponseObject The raw response data object with properties:
     *                                       success, body, headers, responseCode,
     *                                       responseReason, responseTime, request, result
     *
     * @return void
     */
    public function hydrateData(?string $cacheKey, object $rawResponseObject) : void
    {
        $this->success = $rawResponseObject->success ?? false;
        $this->body = $rawResponseObject->body ?? null;
        $this->headers = $rawResponseObject->headers ? (array)$rawResponseObject->headers : null;
        $this->responseCode = $rawResponseObject->responseCode ?? null;
        $this->responseReason = $rawResponseObject->responseReason ?? null;
        $this->responseTime = $rawResponseObject->responseTime ?? null;
        $this->request = $rawResponseObject->request ? (object)$rawResponseObject->request : null;
        $this->result = $rawResponseObject->result ?? null;
        $this->fromCacheKey = $cacheKey;
    }

    /**
     * The cache key if this response was loaded from cache.
     *
     * When responses are retrieved from cache rather than live API calls,
     * this property contains the cache key used. Null indicates the response
     * came from a live API call.
     *
     * @var string|null Cache key string, or null if response was live
     */
    public ?string $fromCacheKey = null;

    /**
     * Whether the API request completed successfully.
     *
     * True indicates the request completed and returned a processable response.
     * This may be from cache or live API. False indicates an error occurred
     * during the request or the response couldn't be processed.
     *
     * @var bool Success status, defaults to false
     */
    public bool $success = false;

    /**
     * The raw JSON body from the API response.
     *
     * Contains the decoded JSON response body as received from the Paymo API.
     * Structure depends on the type of request made:
     * - Single resource fetch: Object with entity key containing single stdClass
     * - List request: Object with entity key containing array of stdClass objects
     * - Error response: Object with error details
     *
     * @var stdClass|null The decoded response body, or null if no body
     */
    public ?stdClass $body = null;

    /**
     * HTTP response headers from the API call.
     *
     * Associative array of headers returned by the Paymo API. Useful for
     * debugging, rate limit checking, and response metadata analysis.
     *
     * @var array<string, string>|null Response headers, or null if unavailable
     */
    public ?array $headers = null;

    /**
     * The HTTP response status code.
     *
     * Standard HTTP status codes returned by the Paymo API:
     * - 200: Success
     * - 201: Created (for POST requests)
     * - 400: Bad request
     * - 401: Unauthorized
     * - 404: Not found
     * - 500: Server error
     *
     * @var int|null HTTP status code, or null if request didn't complete
     */
    public ?int $responseCode = null;

    /**
     * The HTTP response status reason phrase.
     *
     * Human-readable text corresponding to the response code:
     * - "OK" for 200
     * - "Created" for 201
     * - "Not Found" for 404
     * - etc.
     *
     * @var string|null Reason phrase, or null if unavailable
     */
    public ?string $responseReason = null;

    /**
     * The time taken for the API request in seconds.
     *
     * Decimal value representing the elapsed time from request start to
     * response completion. Useful for performance monitoring and optimization.
     *
     * @var float|null Response time in seconds, or null if not measured
     */
    public ?float $responseTime = null;

    /**
     * The original request that generated this response.
     *
     * Reference to the RequestAbstraction object that was sent to the API.
     * Contains the method, URL, data, includes, and where conditions used.
     *
     * @var RequestAbstraction|object|null The original request, or null
     */
    public $request;

    /**
     * The processed result data ready for entity hydration.
     *
     * Contains the scrubbed and processed response data extracted from the
     * body. For single resources, this is a stdClass. For lists, this is
     * an array of stdClass objects. Ready to be passed to entity _hydrate().
     *
     * @var stdClass|stdClass[]|array|null The processed result data
     */
    public $result;

    /**
     * Validate that the response body contains expected data.
     *
     * Checks that the response was successful and contains a body with the
     * specified key containing at least the minimum number of items. Used
     * to validate responses before attempting to process them.
     *
     * VALIDATION LOGIC:
     * -----------------
     * Returns true if ALL of these conditions are met:
     * 1. $this->success is true
     * 2. $this->body is not null/empty
     * 3. $this->body->$key exists AND either:
     *    - Is an array with count >= $minQty
     *    - Is an object (single item)
     *
     * USAGE EXAMPLE:
     * --------------
     * ```php
     * $response = Request::list($connection, 'projects', []);
     *
     * if ($response->validBody('projects', 1)) {
     *     // At least one project returned
     *     foreach ($response->body->projects as $project) {
     *         // Process each project
     *     }
     * }
     *
     * // For single resource
     * $response = Request::fetch($connection, 'projects/123', []);
     * if ($response->validBody('projects')) {
     *     $project = $response->body->projects[0];
     * }
     * ```
     *
     * @param string $key    The property name in the body to check (e.g., 'projects', 'tasks')
     * @param int    $minQty Minimum number of items required under $key. Defaults to 0.
     *
     * @return bool True if body is valid with sufficient items, false otherwise
     */
    public function validBody(string $key, int $minQty = 0) : bool
    {
        return $this->success && $this->body && (
            (is_array($this->body->$key) && count($this->body->$key) >= $minQty)
            || is_object($this->body->$key)
          );
    }
}