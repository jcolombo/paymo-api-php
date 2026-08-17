<?php
/**
 * MIT License — part of the Paymo API PHP SDK (see LICENSE).
 *
 * ======================================================================================
 * REQUEST HEALTH — the silent-failure telltale
 * ======================================================================================
 *
 * The SDK deliberately does not throw on HTTP failure: a 4xx/5xx/network error
 * sets $response->body = null and hydration is skipped, so at the application
 * layer A FAILED LIST IS INDISTINGUISHABLE FROM AN EMPTY LIST. Consumers that
 * treat "no rows" as meaningful (absence-driven deletion, watermark
 * advancement, derived-value computation) silently corrupt state on any
 * transient failure — measured in production as 10,853 records falsely
 * tombstoned by one poisoned batch.
 *
 * This class is the minimal, dependency-free fix: a per-process telltale the
 * request layer RAISES on every non-success response and consumers
 * CHECK-AND-CLEAR around any fetch whose emptiness they intend to act on.
 *
 * Usage:
 *   RequestHealth::reset();
 *   $rows = Task::list()->fetch(...)->flatten(['array' => true]);
 *   if (RequestHealth::failed()) {
 *       throw new \RuntimeException('Paymo fetch failed: ' . RequestHealth::lastReason());
 *   }
 *
 * Deliberately NOT thread/request-scoped state beyond the process: the SDK's
 * RateLimiter already follows the same pattern.
 */

namespace Jcolombo\PaymoApiPhp\Utility;

class RequestHealth
{
    /** @var bool True when any request since reset() completed non-2xx */
    protected static $failed = false;

    /** @var string|null Human-readable reason for the most recent failure */
    protected static $lastReason = null;

    /** @var int|null HTTP code of the most recent failure (0 = network) */
    protected static $lastCode = null;

    /**
     * Clear the telltale before a fetch (or batch of fetches) whose emptiness
     * the caller intends to treat as meaningful.
     */
    public static function reset(): void
    {
        static::$failed = false;
        static::$lastReason = null;
        static::$lastCode = null;
    }

    /**
     * Raised by the request layer on every non-success response. Never
     * cleared implicitly — only reset() clears.
     */
    public static function flagFailure(?int $httpCode, ?string $reason): void
    {
        static::$failed = true;
        static::$lastCode = $httpCode;
        static::$lastReason = $reason;
    }

    public static function failed(): bool
    {
        return static::$failed;
    }

    public static function lastReason(): ?string
    {
        return static::$lastReason;
    }

    public static function lastCode(): ?int
    {
        return static::$lastCode;
    }

    /**
     * Convenience guard: throw when the telltale is raised.
     *
     * @throws \RuntimeException
     */
    public static function assertHealthy(string $context): void
    {
        if (static::$failed) {
            throw new \RuntimeException(
                'Paymo fetch failed silently ('.$context.'): HTTP '
                .var_export(static::$lastCode, true).' '.(static::$lastReason ?? 'unknown')
            );
        }
    }
}
