<?php
/**
 * Paymo API PHP SDK - Rate Limiter
 *
 * Manages API rate limiting to prevent exceeding Paymo's rate limits.
 * Tracks rate limit state from response headers and dynamically adjusts
 * request timing to avoid 429 errors.
 *
 * @package    Jcolombo\PaymoApiPhp\Utility
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020-2025 Joel Colombo / 360 PSG, Inc.
 * @license    MIT License
 * @version    0.6.0
 * @link       https://github.com/jcolombo/paymo-api-php
 */

namespace Jcolombo\PaymoApiPhp\Utility;

use Jcolombo\PaymoApiPhp\Configuration;

/**
 * Rate Limiter for Paymo API Requests
 *
 * Implements intelligent rate limiting that:
 * - Tracks remaining requests from API response headers (x-ratelimit-remaining)
 * - Automatically delays requests when approaching rate limits
 * - Handles 429 (Too Many Requests) responses with exponential backoff
 * - Maintains state across requests within a single PHP process
 *
 * ## Paymo API Rate Limits
 *
 * The Paymo API allows 5 requests per 5 seconds per API key.
 * Response headers provide real-time rate limit information:
 * - `x-ratelimit-limit`: Maximum requests allowed in the window (5)
 * - `x-ratelimit-remaining`: Requests remaining in current window
 * - `x-ratelimit-decay-period`: Seconds until the window resets (5)
 *
 * ## Usage
 *
 * The RateLimiter is used internally by Paymo::execute() and should not
 * typically be called directly by application code.
 *
 * ```php
 * // Internal usage in Paymo class:
 * RateLimiter::waitIfNeeded($this->apiKey);  // Before making request
 * // ... make request ...
 * RateLimiter::updateFromHeaders($this->apiKey, $response->headers);  // After response
 * ```
 *
 * ## Configuration
 *
 * Rate limiting can be configured via `paymoapi.config.json`:
 *
 * ```json
 * {
 *   "rateLimit": {
 *     "enabled": true,
 *     "minDelayMs": 200,
 *     "safetyBuffer": 1,
 *     "maxRetries": 3,
 *     "retryDelayMs": 1000
 *   }
 * }
 * ```
 *
 * @package Jcolombo\PaymoApiPhp\Utility
 * @since   0.6.0
 */
class RateLimiter
{
    /**
     * Default rate limit values (Paymo API defaults)
     */
    private const DEFAULT_LIMIT = 5;
    private const DEFAULT_DECAY_PERIOD = 5;

    /**
     * Minimum delay between requests in milliseconds
     * Even when rate limits are healthy, maintain a small delay
     */
    private const MIN_DELAY_MS = 200;

    /**
     * Safety buffer - start throttling when remaining requests drops to this level
     */
    private const SAFETY_BUFFER = 1;

    /**
     * Maximum retries for 429 responses
     */
    private const MAX_RETRIES = 3;

    /**
     * Base delay for retry backoff in milliseconds
     */
    private const RETRY_DELAY_MS = 1000;

    /**
     * Rate limit state per API key
     *
     * Stores the current rate limit status for each API key in use.
     * This enables multi-connection scenarios where different API keys
     * have independent rate limits.
     *
     * @var array<string, array{
     *   remaining: int,
     *   limit: int,
     *   decayPeriod: int,
     *   lastRequestTime: float,
     *   windowStart: float
     * }>
     */
    private static array $state = [];

    /**
     * Wait if needed before making a request
     *
     * Checks the current rate limit state and delays if necessary
     * to avoid exceeding the API rate limit.
     *
     * @param string $apiKey The API key to check rate limits for
     * @return void
     */
    public static function waitIfNeeded(string $apiKey): void
    {
        $state = self::getState($apiKey);
        $now = microtime(true);

        // Calculate time since last request
        $timeSinceLastRequest = ($now - $state['lastRequestTime']) * 1000; // in ms

        // Always maintain minimum delay between requests
        $minDelay = self::getConfigValue('minDelayMs', self::MIN_DELAY_MS);
        if ($timeSinceLastRequest < $minDelay) {
            $sleepMs = $minDelay - $timeSinceLastRequest;
            usleep((int)($sleepMs * 1000));
        }

        // Check if we need to wait based on remaining rate limit
        $safetyBuffer = self::getConfigValue('safetyBuffer', self::SAFETY_BUFFER);
        if ($state['remaining'] <= $safetyBuffer) {
            // Calculate time until window resets
            $windowElapsed = $now - $state['windowStart'];
            $timeUntilReset = max(0, $state['decayPeriod'] - $windowElapsed);

            if ($timeUntilReset > 0) {
                Log::getLog()->log(null, sprintf(
                    'RATE_LIMIT: Approaching limit (remaining=%d), waiting %.2fs for window reset',
                    $state['remaining'],
                    $timeUntilReset
                ));

                // Sleep until window resets, plus a small buffer
                usleep((int)(($timeUntilReset + 0.1) * 1000000));

                // Reset the window after sleeping
                self::resetWindow($apiKey);
            }
        }

        // Update last request time
        self::$state[$apiKey]['lastRequestTime'] = microtime(true);
    }

    /**
     * Update rate limit state from response headers
     *
     * Parses the x-ratelimit-* headers from the API response
     * and updates the internal state accordingly.
     *
     * @param string $apiKey  The API key the request was made with
     * @param array  $headers Response headers from the API
     * @return void
     */
    public static function updateFromHeaders(string $apiKey, array $headers): void
    {
        $state = self::getState($apiKey);

        // Parse rate limit headers (headers may be arrays or strings)
        $remaining = self::getHeaderValue($headers, 'x-ratelimit-remaining');
        $limit = self::getHeaderValue($headers, 'x-ratelimit-limit');
        $decayPeriod = self::getHeaderValue($headers, 'x-ratelimit-decay-period');

        if ($remaining !== null) {
            $newRemaining = (int)$remaining;

            // If remaining increased or reset, we're in a new window
            if ($newRemaining > $state['remaining']) {
                self::$state[$apiKey]['windowStart'] = microtime(true);
            }

            self::$state[$apiKey]['remaining'] = $newRemaining;
        }

        if ($limit !== null) {
            self::$state[$apiKey]['limit'] = (int)$limit;
        }

        if ($decayPeriod !== null) {
            self::$state[$apiKey]['decayPeriod'] = (int)$decayPeriod;
        }

        Log::getLog()->log(null, sprintf(
            'RATE_LIMIT: Updated state - remaining=%d, limit=%d, decay=%ds',
            self::$state[$apiKey]['remaining'],
            self::$state[$apiKey]['limit'],
            self::$state[$apiKey]['decayPeriod']
        ));
    }

    /**
     * Check if a retry should be attempted for a 429 response
     *
     * @param string $apiKey      The API key
     * @param int    $attemptNum  Current attempt number (1-based)
     * @return bool TRUE if retry should be attempted
     */
    public static function shouldRetry(string $apiKey, int $attemptNum): bool
    {
        $maxRetries = self::getConfigValue('maxRetries', self::MAX_RETRIES);
        return $attemptNum <= $maxRetries;
    }

    /**
     * Wait before retrying after a 429 response
     *
     * Uses exponential backoff with jitter to prevent thundering herd.
     *
     * @param string $apiKey      The API key
     * @param int    $attemptNum  Current attempt number (1-based)
     * @param array  $headers     Response headers (may contain Retry-After)
     * @return void
     */
    public static function waitForRetry(string $apiKey, int $attemptNum, array $headers = []): void
    {
        // Check for Retry-After header first
        $retryAfter = self::getHeaderValue($headers, 'retry-after');

        if ($retryAfter !== null) {
            $waitSeconds = (float)$retryAfter;
        } else {
            // Exponential backoff: baseDelay * 2^(attempt-1) + jitter
            $baseDelay = self::getConfigValue('retryDelayMs', self::RETRY_DELAY_MS);
            $waitMs = $baseDelay * pow(2, $attemptNum - 1);

            // Add jitter (0-25% of wait time)
            $jitter = $waitMs * (mt_rand(0, 25) / 100);
            $waitMs += $jitter;

            $waitSeconds = $waitMs / 1000;
        }

        Log::getLog()->log(null, sprintf(
            'RATE_LIMIT: 429 received, waiting %.2fs before retry %d',
            $waitSeconds,
            $attemptNum
        ));

        usleep((int)($waitSeconds * 1000000));

        // Reset window after waiting for rate limit
        self::resetWindow($apiKey);
    }

    /**
     * Get the current rate limit state for an API key
     *
     * Returns an array with the current rate limit status.
     * Useful for debugging or displaying rate limit info.
     *
     * @param string $apiKey The API key to check
     * @return array{remaining: int, limit: int, decayPeriod: int, lastRequestTime: float, windowStart: float}
     */
    public static function getState(string $apiKey): array
    {
        if (!isset(self::$state[$apiKey])) {
            self::$state[$apiKey] = [
                'remaining' => self::DEFAULT_LIMIT,
                'limit' => self::DEFAULT_LIMIT,
                'decayPeriod' => self::DEFAULT_DECAY_PERIOD,
                'lastRequestTime' => 0.0,
                'windowStart' => microtime(true),
            ];
        }

        return self::$state[$apiKey];
    }

    /**
     * Reset the rate limit window
     *
     * Called when the decay period has passed or after waiting for a 429.
     *
     * @param string $apiKey The API key to reset
     * @return void
     */
    public static function resetWindow(string $apiKey): void
    {
        $state = self::getState($apiKey);
        self::$state[$apiKey]['remaining'] = $state['limit'];
        self::$state[$apiKey]['windowStart'] = microtime(true);
    }

    /**
     * Get a header value from response headers
     *
     * Headers may be arrays (Guzzle format) or strings.
     *
     * @param array  $headers    Response headers
     * @param string $headerName Header name (case-insensitive)
     * @return string|null Header value or null if not found
     */
    private static function getHeaderValue(array $headers, string $headerName): ?string
    {
        // Headers are typically case-insensitive, check various cases
        $variations = [
            $headerName,
            strtolower($headerName),
            ucfirst(strtolower($headerName)),
            str_replace('-', '_', strtolower($headerName)),
        ];

        foreach ($variations as $name) {
            if (isset($headers[$name])) {
                $value = $headers[$name];
                // Guzzle returns headers as arrays
                if (is_array($value)) {
                    return $value[0] ?? null;
                }
                return (string)$value;
            }
        }

        return null;
    }

    /**
     * Get a configuration value with fallback to default
     *
     * @param string $key     Config key under 'rateLimit.*'
     * @param mixed  $default Default value if not configured
     * @return mixed Configuration value
     */
    private static function getConfigValue(string $key, $default)
    {
        $value = Configuration::get("rateLimit.{$key}");
        return $value ?? $default;
    }

    /**
     * Check if rate limiting is enabled
     *
     * @return bool TRUE if rate limiting is enabled
     */
    public static function isEnabled(): bool
    {
        $enabled = Configuration::get('rateLimit.enabled');
        return $enabled !== false; // Default to enabled if not explicitly disabled
    }

    /**
     * Get remaining requests in current window
     *
     * @param string $apiKey The API key to check
     * @return int Number of requests remaining
     */
    public static function getRemaining(string $apiKey): int
    {
        return self::getState($apiKey)['remaining'];
    }

    /**
     * Clear rate limit state (useful for testing)
     *
     * @param string|null $apiKey Specific API key to clear, or null for all
     * @return void
     */
    public static function clearState(?string $apiKey = null): void
    {
        if ($apiKey === null) {
            self::$state = [];
        } else {
            unset(self::$state[$apiKey]);
        }
    }
}
