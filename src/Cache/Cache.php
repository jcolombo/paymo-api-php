<?php
/**
 * Paymo API PHP SDK - Response Caching System
 *
 * Provides file-based or custom caching for API responses to reduce redundant
 * network requests and help avoid rate limiting.
 *
 * @package    Jcolombo\PaymoApiPhp\Cache
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

namespace Jcolombo\PaymoApiPhp\Cache;

use Exception;
use RuntimeException;

/**
 * API Response Cache Manager
 *
 * Singleton class that manages caching of Paymo API responses. By default, uses
 * file-based caching but can be extended with custom cache handlers for Redis,
 * Memcached, or other storage backends.
 *
 * ## Why Use Caching?
 *
 * - **Rate Limiting**: Paymo API limits requests to 5 per 5 seconds
 * - **Performance**: Avoid network latency for repeated requests
 * - **Reliability**: Continue working during brief API outages
 * - **Cost**: Reduce server load and bandwidth usage
 *
 * ## Enabling Caching
 *
 * ### Method 1: Define Constant (Recommended)
 *
 * ```php
 * // Define before autoloading the SDK
 * define('PAYMOAPI_REQUEST_CACHE_PATH', '/var/cache/paymo');
 *
 * // Then enable in configuration
 * Configuration::set('enabled.cache', true);
 * ```
 *
 * ### Method 2: Configuration File
 *
 * ```json
 * {
 *   "enabled": {"cache": true},
 *   "path": {"cache": "/var/cache/paymo"}
 * }
 * ```
 *
 * ## Cache Lifespan
 *
 * Default cache lifespan is 300 seconds (5 minutes). You can adjust this:
 *
 * ```php
 * // Set to 10 minutes
 * Cache::lifespan(600);
 *
 * // Reset to default (5 minutes)
 * Cache::lifespan();
 *
 * // Temporarily change, then restore
 * $previous = Cache::lifespan(30);  // Very short for this operation
 * // ... do operation ...
 * Cache::rollbackLifespan();        // Restore previous lifespan
 * ```
 *
 * ## Custom Cache Handlers
 *
 * For Redis, Memcached, or other backends:
 *
 * ```php
 * Cache::registerCacheMethods(
 *     // Fetch callback: function($key, $lifespan) -> string|null
 *     function($key, $lifespan) {
 *         return $redis->get("paymo:{$key}");
 *     },
 *     // Store callback: function($key, $data, $lifespan) -> bool
 *     function($key, $data, $lifespan) {
 *         return $redis->setex("paymo:{$key}", $lifespan, $data);
 *     }
 * );
 * ```
 *
 * ## Skipping Cache for Specific Requests
 *
 * ```php
 * // Entity method - always get fresh data
 * $project = Project::new()->fetch(123, [], ['skipCache' => true]);
 *
 * // Or ignore cache on the entity instance
 * $project = Project::new();
 * $project->ignoreCache(true);
 * $project->fetch(123);
 * ```
 *
 * @package Jcolombo\PaymoApiPhp\Cache
 * @author  Joel Colombo <jc-dev@360psg.com>
 * @since   0.5.2
 *
 * @see     Configuration For enabling cache via config
 * @see     Paymo::execute() Where caching is applied
 */
class Cache
{

    /**
     * Default cache lifespan in seconds (5 minutes).
     *
     * This value is used when Cache::lifespan() is called without arguments
     * or when resetting to defaults. Can be changed at runtime but this
     * constant defines the baseline.
     *
     * @var int Cache lifespan in seconds
     */
    public const DEFAULT_LIFESPAN = 300;

    /**
     * Singleton instance of the Cache class.
     *
     * Ensures only one cache manager exists per PHP process.
     *
     * @var Cache|null The singleton instance
     */
    private static ?Cache $cache_instance = null;

    /**
     * Current system daylight saving time status.
     *
     * Used to adjust file modification time comparisons when the cache file
     * was created during a different DST period than the current check.
     *
     * @var bool TRUE if system is currently observing DST
     */
    protected bool $system_dst = false;

    /**
     * Directory path for storing cache files.
     *
     * Set via the PAYMOAPI_REQUEST_CACHE_PATH constant. A subdirectory
     * named "paymoapi-cache" is created within this path.
     *
     * @var string|null Absolute path to cache directory, or NULL if not configured
     */
    protected $request_cache_path;

    /**
     * Current cache lifespan in seconds.
     *
     * Determines how long cached responses are considered valid.
     * Can be changed via Cache::lifespan().
     *
     * @var int Cache lifespan in seconds
     */
    public int $lifespan;

    /**
     * Previous cache lifespan before last change.
     *
     * Stored for rollback functionality via Cache::rollbackLifespan().
     *
     * @var int Previous lifespan in seconds
     */
    public int $previousLifespan;

    /**
     * Custom callback for fetching cached data.
     *
     * When set, bypasses file-based caching and uses this callback instead.
     * Signature: function(string $cacheKey, int $lifespan): string|null
     *
     * @var callable|null Custom fetch callback
     */
    public $fetch_callback;

    /**
     * Custom callback for storing cached data.
     *
     * When set, bypasses file-based caching and uses this callback instead.
     * Signature: function(string $cacheKey, string $data, int $lifespan): bool
     *
     * @var callable|null Custom store callback
     */
    public $store_callback;

    /**
     * Retrieve cached data by key.
     *
     * Looks up a cached API response using the provided cache key. If custom
     * callbacks are registered, uses those; otherwise reads from cache files.
     *
     * ## Example
     *
     * ```php
     * // Usually called internally by Paymo::execute()
     * $cachedResponse = Cache::fetch('GET_projects_12345_include_client');
     *
     * if ($cachedResponse !== null) {
     *     // Use cached data
     *     $data = unserialize($cachedResponse);
     * }
     * ```
     *
     * @param string $cache_key Unique cache key generated by RequestAbstraction
     *
     * @return mixed|null Cached data if valid cache exists, NULL otherwise
     *
     * @see RequestAbstraction::makeCacheKey() For cache key generation
     */
    public static function fetch(string $cache_key)
    {
        $c = self::getCache();
        if (!$c) {
            return null;
        }

        $value = null;

        // 1. Get the raw value, from callback or file
        if ($c->fetch_callback !== null) {
            $value = call_user_func($c->fetch_callback, $cache_key, $c->lifespan);
        } else {
            $cache_file = $c->request_cache_path."/$cache_key";
            if (static::isValidCache($cache_file)) {
                $value = file_get_contents($cache_file);
                if ($value === false) {
                    $value = null;
                }
            }
        }

        // Nothing or non string, just return it as is
        if (!is_string($value)) {
            return $value;
        }

        // 2. Try to unserialize if it looks like serialized content
        $maybe = @unserialize($value, ['allowed_classes' => true]);

        // unserialize returns false on error and for serialized false
        // only treat it as unserialized if it is not false, or the original was 'b:0;'
        if ($maybe !== false || $value === 'b:0;') {
            return $maybe;
        }

        // 3. Not serialized, return raw string
        return $value;
    }

    /**
     * Store data in cache with the specified key.
     *
     * Saves an API response to cache for later retrieval. If custom callbacks
     * are registered, uses those; otherwise writes to cache files.
     *
     * ## Example
     *
     * ```php
     * // Usually called internally by Paymo::execute()
     * $success = Cache::store('GET_projects_12345', serialize($response));
     *
     * if (!$success) {
     *     // Cache storage failed - check permissions
     * }
     * ```
     *
     * @param string $cache_key     Unique cache key to store data under
     * @param mixed  $response_data Serialized API response data
     *
     * @return bool TRUE on successful storage, FALSE on failure
     */
    public static function store(string $cache_key, $response_data) : bool
    {
        $c = self::getCache();
        if ($c && !is_null($c->store_callback)) {
            return call_user_func($c->store_callback, $cache_key, $response_data, $c->lifespan);
        }
        $cache_file = $c->request_cache_path."/$cache_key";
        if (!is_string($response_data)) {
            $response_data = serialize($response_data);
        }

        return (file_put_contents($cache_file, $response_data) !== false);
    }

    /**
     * Check if a cache file is still valid (not expired).
     *
     * Compares the file's modification time against the current lifespan
     * setting to determine if the cached data should still be used.
     * Includes daylight saving time adjustment to prevent 1-hour invalidation
     * issues during DST transitions.
     *
     * ## Validation Logic
     *
     * 1. File must exist
     * 2. File must have a modification time
     * 3. File modification time + lifespan > current time
     *
     * @param string $file Absolute path to cache file
     *
     * @return bool TRUE if cache is valid and usable, FALSE if expired or missing
     */
    public static function isValidCache(string $file) : bool
    {
        $c = self::getCache();
        if (!$c) {
            return false;
        }
        $lifespan = $c->lifespan;
        $system_dst = $c->system_dst;
        if (!file_exists($file)) {
            return false;
        }
        $filetime = filemtime($file);
        if (!$filetime) {
            return false;
        }
        // Adjust for daylight saving time transitions
        $file_dst = ((int)date('I', $filetime) === 1);
        $adjustment = 0;
        if (!$file_dst && $system_dst) {
            $adjustment = 3600;
        } elseif ($file_dst && !$system_dst) {
            $adjustment = -3600;
        }
        $filetime += $adjustment;
        $expire_time = time() - $lifespan;

        return $filetime > $expire_time;
    }

    /**
     * Set or reset the cache lifespan.
     *
     * Changes how long cached responses are considered valid. Returns the
     * previous lifespan value for potential restoration.
     *
     * ## Examples
     *
     * ```php
     * // Set cache lifespan to 10 minutes
     * Cache::lifespan(600);
     *
     * // Reset to default (5 minutes)
     * Cache::lifespan();
     * // or
     * Cache::lifespan(0);
     *
     * // Temporary change with restoration
     * $previous = Cache::lifespan(30);  // Short lifespan for fresh data
     * $project = Project::new()->fetch(123);  // Fresh fetch
     * Cache::lifespan($previous);  // Restore
     * ```
     *
     * @param int $seconds New lifespan in seconds. Use 0 or omit to reset to default.
     *
     * @return int The previous lifespan value (before this call)
     */
    public static function lifespan(int $seconds = 0) : int
    {
        $c = self::getCache();
        if (!$c) {
            return 0;
        }
        $c->previousLifespan = $c->lifespan;
        if ($seconds > 0) {
            $c->lifespan = $seconds;

            return $c->previousLifespan;
        }
        $c->lifespan = static::DEFAULT_LIFESPAN;

        return $c->previousLifespan;
    }

    /**
     * Restore the previous cache lifespan.
     *
     * Rolls back to the lifespan value that was in effect before the last
     * call to Cache::lifespan(). Useful for temporary lifespan changes.
     *
     * ## Example
     *
     * ```php
     * Cache::lifespan(600);     // 10 minutes
     * Cache::lifespan(30);      // 30 seconds (for fresh data)
     * Cache::rollbackLifespan(); // Back to 10 minutes
     * ```
     *
     * @return void
     */
    public static function rollbackLifespan() : void
    {
        $c = self::getCache();
        if (!$c) {
            return;
        }
        $c->lifespan = $c->previousLifespan;
    }

    /**
     * Format a duration in seconds to human-readable string.
     *
     * Converts seconds into a readable format like "5 Mins 30 Secs" for
     * logging and debugging purposes.
     *
     * ## Examples
     *
     * ```php
     * Cache::formatDuration(90);     // "1 Min 30 Secs"
     * Cache::formatDuration(3661);   // "1 Hour 1 Min 1 Sec"
     * Cache::formatDuration(86400);  // "1 Day"
     * Cache::formatDuration(0);      // "-----"
     * ```
     *
     * @param int $seconds Duration in seconds
     *
     * @return string Human-readable duration string
     */
    public static function formatDuration(int $seconds) : string
    {
        if ($seconds < 1) {
            return '-----';
        }

        $time_units = [
          'Day'  => 86400,
          'Hour' => 3600,
          'Min'  => 60,
          'Sec'  => 1
        ];

        $output = [];

        foreach ($time_units as $unit => $value) {
            $count = (int)($seconds / $value);
            $seconds %= $value;

            if ($count > 0) {
                $suffix = ($count !== 1) ? 's' : '';
                $output[] = "$count $unit$suffix";
            }
        }

        return implode(' ', $output) ?: '0 Seconds';
    }

    /**
     * Register custom cache handler callbacks.
     *
     * Allows replacing the default file-based caching with custom implementations
     * like Redis, Memcached, database, or any other storage backend.
     *
     * Both callbacks must be provided together, or both must be NULL to clear
     * custom handlers and revert to file-based caching.
     *
     * ## Callback Signatures
     *
     * **Fetch Callback**: `function(string $cacheKey, int $lifespan): ?string`
     * - `$cacheKey`: The cache key to look up
     * - `$lifespan`: Maximum age in seconds for valid cache
     * - Returns: Cached data string or NULL if not found/expired
     *
     * **Store Callback**: `function(string $cacheKey, string $data, int $lifespan): bool`
     * - `$cacheKey`: The cache key to store under
     * - `$data`: The data to cache
     * - `$lifespan`: TTL in seconds
     * - Returns: TRUE on success, FALSE on failure
     *
     * ## Example: Redis Integration
     *
     * ```php
     * $redis = new Redis();
     * $redis->connect('127.0.0.1', 6379);
     *
     * Cache::registerCacheMethods(
     *     function($key, $lifespan) use ($redis) {
     *         $data = $redis->get("paymo:{$key}");
     *         return $data !== false ? $data : null;
     *     },
     *     function($key, $data, $lifespan) use ($redis) {
     *         return $redis->setex("paymo:{$key}", $lifespan, $data);
     *     }
     * );
     * ```
     *
     * ## Clear Custom Handlers
     *
     * ```php
     * Cache::registerCacheMethods(null, null);
     * ```
     *
     * @param callable|null $fetch_callback Fetch callback or NULL to clear
     * @param callable|null $store_callback Store callback or NULL to clear
     *
     * @throws Exception If only one callback is provided (both or neither required)
     */
    public static function registerCacheMethods(?callable $fetch_callback, ?callable $store_callback) : void
    {
        if ((is_null($fetch_callback) || is_null($store_callback)) && !(is_null($fetch_callback) && is_null(
              $store_callback
            ))) {
            throw new RuntimeException("Both callbacks must be defined or both must be NULL. One is NULL, one is not");
        }
        $c = self::getCache();
        if (!$c) {
            return;
        }
        $c->fetch_callback = $fetch_callback;
        $c->store_callback = $store_callback;
    }

    /**
     * Get or create the singleton Cache instance.
     *
     * Returns the existing Cache instance or creates one if it doesn't exist.
     * This ensures only one cache manager exists per PHP process.
     *
     * ## Example
     *
     * ```php
     * // Usually called internally
     * $cache = Cache::getCache();
     * echo $cache->lifespan;  // Current lifespan
     * ```
     *
     * @return Cache The singleton Cache instance
     */
    public static function getCache() : ?Cache
    {
        if (!isset(self::$cache_instance)) {
            self::$cache_instance = new static();
        }

        return self::$cache_instance;
    }

    /**
     * Private constructor for singleton pattern.
     *
     * Initializes the cache manager by:
     * 1. Detecting current DST status for file time comparisons
     * 2. Setting default lifespan values
     * 3. Creating cache directory if PAYMOAPI_REQUEST_CACHE_PATH is defined
     *
     * @internal Use Cache::getCache() to access the instance
     */
    private function __construct()
    {
        $this->system_dst = ((int)date('I') === 1);
        $this->lifespan = static::DEFAULT_LIFESPAN;
        $this->previousLifespan = static::DEFAULT_LIFESPAN;
        $this->request_cache_path = null;
        if (defined('PAYMOAPI_REQUEST_CACHE_PATH')) {
            $this->request_cache_path = constant('PAYMOAPI_REQUEST_CACHE_PATH');
            if (!file_exists($this->request_cache_path.'/paymoapi-cache') && !mkdir(
                $concurrentDirectory = $this->request_cache_path.'/paymoapi-cache'
              ) && !is_dir(
                $concurrentDirectory
              )) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
            $this->request_cache_path .= '/paymoapi-cache';
        }
    }

}
