# Technical Specification: paymo-api-php v1.0.0-alpha

---

## Solution Overview

The paymo-api-php SDK upgrade from v0.6.1 to v1.0.0-alpha is delivered as a four-phase, four-version progression (v0.7.0 → v0.8.0 → v0.9.0 → v1.0.0-alpha). Each version is a tagged, installable, backward-compatible release that serves as a safe rollback point for production consumers. The sole environment-breaking change — the PHP 7.4 → 8.1 minimum version bump — is isolated to the v0.8.0 boundary, allowing PHP 7.4 consumers to stay on v0.7.x indefinitely.

The architecture extends the existing ORM-style entity SDK pattern rather than replacing it. New functionality is added through method additions on existing abstract classes (`AbstractResource`, `AbstractCollection`), extensions to the `Converter` utility for three-direction type coercion, cache invalidation hooks in the `Paymo::execute()` pipeline, and a formalized `ErrorSeverity` enum replacing the string-based severity system. The `hassankhan/config` dependency is replaced with direct use of the already-present `adbario/php-dot-notation`, and `psr/log` is added as the only new dependency.

Backward compatibility is maintained through: (1) typed values implementing `__toString()` so string contexts continue to work, (2) new method parameters added at the end with default values, (3) the `registerCacheMethods()` third callback being optional, (4) ErrorSeverity enum values matching existing string severity names, and (5) PSR-3 logging being an optional injection layered on top of the existing `Log` utility. No existing public method signature changes its behavior — all changes are additive.

---

## Architecture Decisions

### AD-001: PHP 8.1 Minimum at v0.8.0 Boundary

**Decision:** The PHP version bump from `>=7.4` to `>=8.1` occurs at the v0.8.0 release, isolated from all other changes in that version.

**Justification:** PHP 7.4 reached EOL November 2022 (3+ years ago). Both peer SDKs (niftyquoter-api-php, leadfeeder-api-php) require `>=8.1`. Isolating the bump at a version boundary gives consumers a clear upgrade path: stay on v0.7.x for PHP 7.4, upgrade to v0.8.0+ for PHP 8.1+.

**Alternative considered:** Keeping PHP 7.4 throughout — rejected because it blocks native enums, union types, readonly properties, and `match` expressions, all of which improve code quality in subsequent phases.

### AD-002: Extend Converter Class for Type Coercion

**Decision:** Add `convertToPhpValue()` and `convertForRequest()` as new static methods on the existing `Converter` class rather than creating a separate `TypeCoercion` class.

**Justification:** The existing `Converter` already handles type mapping (`getPrimitiveType()`) and filter value conversion (`convertValueForFilter()`). Adding hydration and serialization methods to the same class keeps all type conversion logic co-located. The three conversion methods serve distinct pipeline positions: hydration (API→PHP), serialization (PHP→API), and filtering (PHP→API query string).

**Alternative considered:** New `TypeCoercion` class — rejected because it would create a parallel conversion path and fragment type knowledge across two classes.

### AD-003: Cache Key Prefixing for Invalidation

**Decision:** Modify cache key format from `paymoapi-{md5}` to `paymoapi-{resourceBase}-{md5}` so that `glob()` can identify all cache entries for a given resource type.

**Justification:** The current MD5-only key makes pattern-based invalidation impossible without a separate index. Embedding the resource base (e.g., `projects`, `invoices`) in the key name enables `glob("paymoapi-projects-*")` for file-based cache invalidation. For custom cache backends, the `$clear` callback receives the resource base string. This approach survives PHP process restarts (no in-memory index needed) and adds zero complexity for custom backends.

**Alternative considered:** Separate JSON manifest file mapping resource paths to cache keys — rejected because it introduces a persistence/corruption risk and requires file locking for concurrent access. Directory-per-resource approach — rejected because it changes directory structure more invasively.

**Migration:** Existing cache files with the old format become orphaned and expire naturally via TTL. No migration step required.

### AD-004: ErrorSeverity as PHP 8.1 Backed Enum

**Decision:** Implement `ErrorSeverity` as a native PHP 8.1 backed string enum with cases `NOTICE`, `WARN`, `FATAL`, where the backing values match the existing string severity names (`'notice'`, `'warn'`, `'fatal'`).

**Justification:** The existing `Error` class already uses these three string severity levels. A backed enum provides type safety while maintaining backward compatibility — `ErrorSeverity::FATAL->value === 'fatal'` matches existing handler configuration keys. Native enums are PHP 8.1+ only, which is why this change is in Phase 2 (v0.8.0).

**Alternative considered:** String constants class — rejected because PHP 8.1 is available and enums provide exhaustive match/switch checking.

### AD-005: PSR-3 as Optional Adapter Layer

**Decision:** PSR-3 logging is injected via `Paymo::connect(logger: $psr3Logger)` or `$connection->setLogger($psr3Logger)`. A `PsrLogAdapter` wraps the PSR-3 `LoggerInterface` and maps SDK severity levels to PSR-3 log levels. When no PSR-3 logger is injected, the existing `Log` utility continues as the default.

**Justification:** Backward compatibility. Existing consumers using file-based logging via the `Log` class are not disrupted. The adapter pattern allows both systems to coexist — PSR-3 receives log entries alongside the existing `Log` output if both are configured.

**Alternative considered:** Replace `Log` class entirely with PSR-3 — rejected because it would force all consumers to inject a PSR-3 logger or lose logging entirely.

### AD-006: Configuration Singleton Destruction via Static reset()

**Decision:** Replace the existing instance method `Configuration->reset()` (which reloads paths) with a static method `Configuration::reset()` that nullifies the singleton instance. The next `Configuration::get()` call creates a fresh instance from the default config file.

**Justification:** The primary use case is test isolation — tests need to start with a clean config state. Destroying the singleton is simpler and more thorough than resetting paths. Any runtime `Configuration::set()` changes and `overload()` additions are discarded, which is the desired behavior.

**Breaking change note:** Code calling `$instance->reset()` (instance method) must change to `Configuration::reset()` (static). This affects only internal SDK code, not consumer code (consumers access Configuration via static methods exclusively).

### AD-007: Replace hassankhan/config with Direct Adbar\Dot

**Decision:** Remove the `hassankhan/config` dependency. Rewrite `Configuration.php` to load JSON files with `json_decode(file_get_contents(...))` and wrap the merged array in an `Adbar\Dot` instance for dot-notation access.

**Justification:** `hassankhan/config` (last updated 2021) wraps `adbario/php-dot-notation` with unused YAML/XML/INI support and adds complexity. The SDK only loads JSON config files. `Adbar\Dot` provides the same `get()`, `has()`, `set()`, `all()` methods that `Configuration` delegates to. Direct use eliminates the wrapper and removes a stale dependency.

**Alternative considered:** Keep hassankhan/config — rejected due to maintenance risk (no activity since 2021) and unnecessary abstraction layer.

### AD-008: PaymoDateTime Extends DateTimeImmutable

**Decision:** Create `PaymoDateTime extends \DateTimeImmutable` with a `__toString()` method returning the original API string value. This class is used for all `datetime` and `date` type properties during hydration.

**Justification:** Extending `\DateTimeImmutable` ensures `$project->created_on instanceof \DateTimeImmutable` returns `true`. Implementing `__toString()` preserves backward compatibility for string contexts: `echo $project->created_on` and string concatenation continue to produce the original API string. Strict equality (`===`) with strings breaks (documented as known behavior change per A-011), but loose equality (`==`) and string interpolation work.

**Alternative considered:** Wrapper class implementing `DateTimeInterface` — rejected because `instanceof \DateTimeImmutable` would return `false`, breaking more consumer code. Raw `\DateTimeImmutable` without `__toString()` — rejected because it breaks string contexts entirely.

### AD-009: fetchAll() via Iterative Page Merging

**Decision:** `AbstractCollection::fetchAll()` iterates pages by calling `Request::list()` directly for each page, merging results into `$this->data` after each iteration. At any point during pagination, memory holds the accumulated result plus one current page of data.

**Justification:** NFR-004 requires not holding more than 2 pages simultaneously. By merging each page into `$this->data` immediately and discarding the page array, peak memory overhead during pagination is one page worth of temporary data plus the growing accumulated result. The safety cap (default 5,000 records, overridable per resource via `FETCHALL_CAP` constant) prevents runaway pagination.

**Alternative considered:** Streaming/generator approach — rejected because the return type must be a single populated collection, not a lazy iterator, to match the existing `fetch()` API contract.

### AD-010: Partial Includes via Separate Query Parameter

**Decision:** Include entries containing parentheses (e.g., `tasks(id,name,due_date)`) are compiled to the `partial_include` query parameter instead of `include`. Regular includes and partial includes can be mixed in a single fetch call.

**Justification:** The Paymo API uses `partial_include` as a separate parameter from `include`. The SDK detects partial syntax by checking for parentheses in the include key and routes accordingly. In devMode, the sub-fields inside parentheses are validated against the included resource's `PROP_TYPES`.

### AD-011: Nested Includes Validated at Parse Time

**Decision:** Nested include dot notation (e.g., `tasks.entries`) is validated during include compilation in `AbstractEntity::cleanupForRequest()`. Each level is validated against the parent resource's `INCLUDE_TYPES`. Maximum depth is 2 levels (configurable via `config.includes.maxDepth`). The dot-notation string is passed directly to the API's `include` parameter.

**Justification:** The Paymo API supports at least 2-level nesting (confirmed). 3+ levels are untested (FR-048 will investigate). Enforcing a max depth of 2 by default prevents untested API behavior. The validation at parse time catches errors early in devMode.

### AD-012: Four-Phase Delivery with SemVer Rollback Points

**Decision:** Deliver in four tagged versions: v0.7.0 (bug fixes + additions, PHP 7.4), v0.8.0 (PHP 8.1 + modernization), v0.9.0 (core features), v1.0.0-alpha (investigations + properties + final polish).

**Justification:** The user explicitly requires "backward compatible in phases that let us stamp it at a safe non-breaking point so we can roll back to it on production uses." Four versions provide four rollback points with increasing functionality. Pre-1.0 SemVer allows non-breaking additions at each minor version. The `1.0.0-alpha` tag signifies API surface stability for the public interface.

---

## Data Structures & Types

### ErrorSeverity Enum (Phase 2, v0.8.0)

```php
<?php
// src/Utility/ErrorSeverity.php
namespace Jcolombo\PaymoApiPhp\Utility;

/**
 * SDK error severity classification.
 * Backed values match existing string severity names for backward compatibility.
 *
 * HTTP status mapping:
 *   401, 403, 500+ → FATAL
 *   429             → WARN
 *   400, 404, 422   → NOTICE
 */
enum ErrorSeverity: string
{
    case NOTICE = 'notice';
    case WARN = 'warn';
    case FATAL = 'fatal';

    /**
     * Classify an HTTP status code into a severity level.
     */
    public static function fromHttpStatus(int $statusCode): self
    {
        return match (true) {
            $statusCode === 429 => self::WARN,
            $statusCode === 401, $statusCode === 403 => self::FATAL,
            $statusCode >= 500 => self::FATAL,
            $statusCode === 400, $statusCode === 404, $statusCode === 422 => self::NOTICE,
            default => self::NOTICE,
        };
    }
}
```

### PaymoDateTime Class (Phase 3, v0.9.0)

```php
<?php
// src/Utility/PaymoDateTime.php
namespace Jcolombo\PaymoApiPhp\Utility;

/**
 * DateTimeImmutable subclass that preserves the original API string value.
 * Returned by type coercion for 'datetime' and 'date' PROP_TYPES.
 *
 * Backward compatibility:
 *   - instanceof \DateTimeImmutable → true
 *   - (string)$paymoDateTime → original API string
 *   - echo $paymoDateTime → original API string
 *   - json_encode($paymoDateTime) → quoted original API string
 *   - $paymoDateTime == 'original-string' → true (loose equality)
 *   - $paymoDateTime === 'original-string' → false (strict equality — DOCUMENTED BREAKING CHANGE)
 */
class PaymoDateTime extends \DateTimeImmutable implements \JsonSerializable
{
    private readonly string $rawApiValue;

    public function __construct(string $datetime = 'now', ?\DateTimeZone $timezone = null)
    {
        parent::__construct($datetime, $timezone);
        $this->rawApiValue = $datetime;
    }

    /**
     * Returns the original API string value.
     * Preserves backward compatibility for string contexts.
     */
    public function __toString(): string
    {
        return $this->rawApiValue;
    }

    /**
     * JSON serialization returns the original API string.
     * Ensures json_encode($resource->flatten()) produces API-compatible strings.
     */
    public function jsonSerialize(): string
    {
        return $this->rawApiValue;
    }

    /**
     * Returns the raw API string value.
     */
    public function getRawApiValue(): string
    {
        return $this->rawApiValue;
    }
}
```

### PsrLogAdapter Class (Phase 2, v0.8.0)

```php
<?php
// src/Utility/PsrLogAdapter.php
namespace Jcolombo\PaymoApiPhp\Utility;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Bridges the SDK's severity-based logging to a PSR-3 LoggerInterface.
 * Maps SDK severity levels to PSR-3 log levels.
 */
class PsrLogAdapter
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Log a message using the PSR-3 logger.
     *
     * @param ErrorSeverity|string $severity SDK severity level
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function log(ErrorSeverity|string $severity, string $message, array $context = []): void
    {
        $severityValue = ($severity instanceof ErrorSeverity) ? $severity->value : $severity;

        $psrLevel = match ($severityValue) {
            'fatal'  => LogLevel::ERROR,
            'warn'   => LogLevel::WARNING,
            'notice' => LogLevel::NOTICE,
            default  => LogLevel::DEBUG,
        };

        $this->logger->log($psrLevel, $message, $context);
    }

    /**
     * Log an API request at DEBUG level.
     */
    public function logRequest(string $method, string $url, ?array $data = null): void
    {
        $this->logger->debug("API {$method} {$url}", [
            'method' => $method,
            'url' => $url,
            'data' => $data,
        ]);
    }

    /**
     * Log an API response at DEBUG level.
     */
    public function logResponse(int $statusCode, string $url, float $responseTime, bool $fromCache = false): void
    {
        $this->logger->debug("API Response {$statusCode} {$url} ({$responseTime}ms)" . ($fromCache ? ' [CACHE]' : ''), [
            'status_code' => $statusCode,
            'url' => $url,
            'response_time' => $responseTime,
            'from_cache' => $fromCache,
        ]);
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
```

### New/Modified Constants

```php
// --- AbstractResource: WRITEONLY constant (Phase 1, v0.7.0) ---
// Added to AbstractResource alongside existing READONLY, CREATEONLY, UNSELECTABLE

abstract class AbstractResource extends AbstractEntity
{
    // Existing constants (unchanged)
    public const REQUIRED_CONSTANTS = [
        'LABEL', 'API_PATH', 'API_ENTITY', 'REQUIRED_CREATE',
        'READONLY', 'CREATEONLY', 'INCLUDE_TYPES', 'PROP_TYPES',
        'WHERE_OPERATIONS'
    ];
    public const UNSELECTABLE = [];

    // NEW: Properties that trigger API actions when set but are never returned in responses.
    // Always included in dirty tracking when set. Never expected during deserialization.
    // Empty by default — per FR-010 audit, no Paymo API properties currently fit this pattern.
    // Structural parity with peer SDKs (niftyquoter-api-php, leadfeeder-api-php).
    public const WRITEONLY = [];
}


// --- AbstractCollection: FETCHALL_CAP constant (Phase 3, v0.9.0) ---
// Defined on AbstractCollection as a class constant. Override in resource-specific collections.

abstract class AbstractCollection extends AbstractEntity
{
    // Default safety cap for fetchAll() auto-pagination.
    // Override in collection subclasses for resources with known large datasets.
    public const FETCHALL_CAP = 5000;
}
```

### Converter Method Additions (Phase 3, v0.9.0)

```php
<?php
// Added to src/Utility/Converter.php

class Converter
{
    // --- Existing methods (unchanged) ---
    // public static function getPrimitiveType(string $type): string
    // public static function convertOperatorValue(RequestCondition $w): ?string
    // public static function convertValueForFilter(string $type, $value)

    /**
     * Convert an API response value to a typed PHP value (hydration direction).
     * Called during resource hydration (_hydrate → __set).
     *
     * @param mixed $value Raw value from API response
     * @param string $type PROP_TYPES type definition (e.g., 'datetime', 'boolean', 'resource:projects')
     * @return mixed Typed PHP value
     */
    public static function convertToPhpValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        // Already a typed object (e.g., re-hydrating from cached typed data)
        if ($value instanceof PaymoDateTime && str_starts_with($type, 'datetime') || $type === 'date') {
            return $value;
        }

        $parts = explode(':', $type, 2);
        $prefix = $parts[0];

        return match ($prefix) {
            'datetime' => self::hydrateDateTime($value),
            'date'     => self::hydrateDateTime($value),
            'boolean'  => self::hydrateBoolean($value),
            'integer'  => is_array($value) ? array_map('intval', $value) : (int) $value,
            'decimal', 'double' => (float) $value,
            'resource' => (int) $value,
            'collection' => is_array($value) ? array_map('intval', $value) : (int) $value,
            'array'    => is_array($value) ? array_map('intval', $value) : (int) $value,
            // text, html, email, url, enum, enumInt, datetime[] — keep as-is
            default    => $value,
        };
    }

    /**
     * Convert a PHP value to API-expected format for request bodies (serialization direction).
     * Called during create()/update() body assembly.
     *
     * @param mixed $value PHP-typed value from resource props
     * @param string $type PROP_TYPES type definition
     * @return mixed API-compatible value
     */
    public static function convertForRequest(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        $parts = explode(':', $type, 2);
        $prefix = $parts[0];

        return match ($prefix) {
            'datetime' => ($value instanceof \DateTimeInterface) ? $value->format('c') : (string) $value,
            'date'     => ($value instanceof \DateTimeInterface) ? $value->format('Y-m-d') : (string) $value,
            'boolean'  => is_bool($value) ? $value : (bool) $value,
            'integer', 'resource' => (int) $value,
            'decimal', 'double'   => (float) $value,
            'collection' => is_array($value) ? array_map('intval', $value) : (int) $value,
            'array'      => is_array($value) ? array_map('intval', $value) : $value,
            default      => $value,
        };
    }

    /**
     * Hydrate a datetime/date string from API to PaymoDateTime.
     */
    private static function hydrateDateTime(mixed $value): PaymoDateTime|\DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return ($value instanceof PaymoDateTime) ? $value : new PaymoDateTime($value->format('c'));
        }
        return new PaymoDateTime((string) $value);
    }

    /**
     * Hydrate a boolean from various API representations.
     */
    private static function hydrateBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value !== 0;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes'], true);
        }
        return (bool) $value;
    }
}
```

### RequestResponse: cacheHit Property (Phase 1, v0.7.0)

```php
// Added to src/Utility/RequestResponse.php
class RequestResponse
{
    // Existing properties (unchanged)
    public ?string $fromCacheKey = null;
    public bool $success = false;
    public ?stdClass $body = null;
    public ?array $headers = null;
    public ?int $responseCode = null;
    public ?string $responseReason = null;
    public ?float $responseTime = null;
    public $request;
    public $result;

    // NEW: Explicit cache-hit indicator
    // Set to true when the response was served from cache.
    // Distinct from $fromCacheKey which stores the key string (may be set but stale).
    public bool $cacheHit = false;
}
```

### RequestAbstraction: partialInclude and Modified Cache Key (Phase 3, v0.9.0)

```php
// Modifications to src/Utility/RequestAbstraction.php
class RequestAbstraction
{
    // Existing properties (unchanged)
    public ?string $cacheKey = null;
    public string $method = 'GET';
    public string $mode = 'json';
    public ?string $resourceUrl = null;
    public ?array $data = null;
    public ?string $include = null;
    public ?string $where = null;
    public ?int $page = null;
    public ?int $pageSize = null;
    public ?array $files = null;

    // NEW: Partial include parameter (FR-014)
    public ?string $partialInclude = null;

    /**
     * Generate cache key with resource base prefix for invalidation targeting.
     * Format changes from 'paymoapi-{md5}' to 'paymoapi-{resourceBase}-{md5}'.
     */
    public function makeCacheKey(bool $force = true): RequestAbstraction
    {
        if ($this->method !== 'GET') {
            $this->cacheKey = null;
            return $this;
        }

        if (!$force && !is_null($this->cacheKey)) {
            return $this;
        }

        $cacheString = $this->resourceUrl
            . ':include=' . $this->include
            . '&partial_include=' . $this->partialInclude
            . '&where=' . $this->where
            . '&page=' . $this->page
            . '&page_size=' . $this->pageSize;

        // Extract resource base for glob-based invalidation
        $resourceBase = explode('/', $this->resourceUrl)[0] ?? 'unknown';

        $this->cacheKey = 'paymoapi-' . $resourceBase . '-' . md5($cacheString);

        return $this;
    }
}
```

### Cache Invalidation Methods (Phase 3, v0.9.0)

```php
// Additions to src/Cache/Cache.php
class Cache
{
    // Existing properties (unchanged)
    private static ?Cache $cache_instance = null;
    public int $lifespan;
    public int $previousLifespan;
    public $fetch_callback;
    public $store_callback;

    // NEW: Optional clear callback for custom cache backends (FR-018)
    public $clear_callback = null;

    /**
     * Register custom cache methods.
     * Third parameter is optional for backward compatibility.
     *
     * @param callable|null $fetch_callback function(string $key, int $lifespan): mixed
     * @param callable|null $store_callback function(string $key, mixed $data, int $lifespan): bool
     * @param callable|null $clear_callback function(string $resourceBase): void — NEW FR-018
     */
    public static function registerCacheMethods(
        ?callable $fetch_callback,
        ?callable $store_callback,
        ?callable $clear_callback = null
    ): void {
        // Existing validation: both fetch and store must be null or both non-null
        if (($fetch_callback === null) !== ($store_callback === null)) {
            throw new \RuntimeException(
                'Cache registerCacheMethods requires both fetch and store callbacks, or both null.'
            );
        }

        $c = self::getCache();
        $c->fetch_callback = $fetch_callback;
        $c->store_callback = $store_callback;
        $c->clear_callback = $clear_callback;
    }

    /**
     * Invalidate all cached entries for a given resource type.
     * Called after successful POST/PUT/DELETE operations.
     *
     * @param string $resourceBase Base entity path (e.g., 'projects', 'invoices')
     */
    public static function invalidateByResource(string $resourceBase): void
    {
        $c = self::getCache();
        if ($c === null) {
            return;
        }

        // Custom cache backend: delegate to clear callback
        if ($c->clear_callback !== null) {
            ($c->clear_callback)($resourceBase);
            return;
        }

        // File-based cache: glob for matching files
        if ($c->request_cache_path) {
            $pattern = $c->request_cache_path . '/paymoapi-' . $resourceBase . '-*';
            $files = glob($pattern);
            if ($files !== false) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * Clear all cached entries.
     */
    public static function clearAll(): void
    {
        $c = self::getCache();
        if ($c === null) {
            return;
        }

        if ($c->clear_callback !== null) {
            ($c->clear_callback)('*');
            return;
        }

        if ($c->request_cache_path) {
            $files = glob($c->request_cache_path . '/paymoapi-*');
            if ($files !== false) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }
    }
}
```

### Configuration Rewrite (Phase 2, v0.8.0)

```php
<?php
// src/Configuration.php — rewritten to use Adbar\Dot directly (FR-023)
namespace Jcolombo\PaymoApiPhp;

use Adbar\Dot;

class Configuration
{
    public const DEFAULT_CONFIGURATION_PATH = __DIR__ . '/../default.paymoapi.config.json';

    private static ?Configuration $instance = null;
    private Dot $config;
    private array $paths;

    private function __construct()
    {
        $defaultPath = realpath(self::DEFAULT_CONFIGURATION_PATH);
        $this->paths = $defaultPath ? [$defaultPath] : [];
        $this->reloadConfig();
    }

    /**
     * Load JSON files and merge into Dot instance.
     */
    private function reloadConfig(): void
    {
        $merged = [];
        foreach ($this->paths as $path) {
            $content = file_get_contents($path);
            $data = json_decode($content, true);
            if (is_array($data)) {
                $merged = array_replace_recursive($merged, $data);
            }
        }
        $this->config = new Dot($merged);
    }

    /**
     * Destroy the singleton. Next get()/load() call creates a fresh instance.
     * Primary use case: test isolation.
     */
    public static function reset(): void
    {
        self::$instance = null;
    }

    public static function load(?string $path = null): ?Configuration
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        if ($path !== null) {
            self::$instance->overload($path);
        }
        return self::$instance;
    }

    public function overload(?string $path = null): void
    {
        if ($path === null) {
            return;
        }
        // If path is a directory, look for paymoapi.config.json in it
        if (is_dir($path)) {
            $path = rtrim($path, '/') . '/paymoapi.config.json';
        }
        $realPath = realpath($path);
        if ($realPath === false || in_array($realPath, $this->paths, true)) {
            return;
        }
        $this->paths[] = $realPath;
        $this->reloadConfig();
    }

    public static function get(string $key): mixed
    {
        $instance = self::load();
        return $instance->config->get($key);
    }

    public static function has(string $key): bool
    {
        $instance = self::load();
        return $instance->config->has($key);
    }

    public static function set(string $key, mixed $value): void
    {
        $instance = self::load();
        $instance->config->set($key, $value);
    }

    public static function all(): array
    {
        $instance = self::load();
        return $instance->config->all();
    }
}
```

### devMode Constant Validation Logic (Phase 1, v0.7.0)

```php
// Added to AbstractResource::__construct() or a new validateConstants() method

/**
 * Cross-reference resource constants for internal consistency.
 * Only runs in devMode. Result cached per class (runs once per class per process).
 *
 * Validations:
 *   1. No READONLY property in REQUIRED_CREATE
 *   2. No WRITEONLY property in READONLY
 *   3. All UNSELECTABLE properties exist in PROP_TYPES
 *   4. All READONLY properties exist in PROP_TYPES
 *   5. All CREATEONLY properties exist in PROP_TYPES
 *   6. All WRITEONLY properties exist in PROP_TYPES
 *   7. No unknown type prefixes in PROP_TYPES values
 */
private static array $validatedClasses = [];

protected function validateConstants(): void
{
    $class = static::class;
    if (isset(self::$validatedClasses[$class])) {
        return;
    }

    $propTypes = array_keys(static::PROP_TYPES);
    $readonly = static::READONLY;
    $writeonly = static::WRITEONLY;
    $requiredCreate = static::REQUIRED_CREATE;
    $unselectable = static::UNSELECTABLE;
    $createonly = static::CREATEONLY;
    $validPrefixes = [
        'text', 'integer', 'decimal', 'double', 'boolean', 'date', 'datetime',
        'datetime[]', 'url', 'email', 'html', 'array', 'resource', 'collection',
        'enum', 'enumInt', 'intEnum',
    ];

    // 1. READONLY ∩ REQUIRED_CREATE = ∅ (excluding compound operators)
    foreach ($requiredCreate as $req) {
        // Skip compound requirements (contain ||, |, &)
        if (preg_match('/[|&]/', $req)) {
            continue;
        }
        if (in_array($req, $readonly, true)) {
            throw new \RuntimeException(
                "{$class}: Property '{$req}' is in both READONLY and REQUIRED_CREATE."
            );
        }
    }

    // 2. WRITEONLY ∩ READONLY = ∅
    $overlap = array_intersect($writeonly, $readonly);
    if (count($overlap) > 0) {
        throw new \RuntimeException(
            "{$class}: Properties [" . implode(', ', $overlap) . "] are in both WRITEONLY and READONLY."
        );
    }

    // 3-6. All constant arrays reference valid PROP_TYPES keys
    $checks = [
        'UNSELECTABLE' => $unselectable,
        'READONLY' => $readonly,
        'CREATEONLY' => $createonly,
        'WRITEONLY' => $writeonly,
    ];
    foreach ($checks as $constName => $keys) {
        foreach ($keys as $key) {
            if (!in_array($key, $propTypes, true)) {
                throw new \RuntimeException(
                    "{$class}: Property '{$key}' in {$constName} is not defined in PROP_TYPES."
                );
            }
        }
    }

    // 7. Validate type prefixes
    foreach (static::PROP_TYPES as $prop => $type) {
        $prefix = explode(':', $type, 2)[0];
        if (!in_array($prefix, $validPrefixes, true)) {
            throw new \RuntimeException(
                "{$class}: Unknown type prefix '{$prefix}' for property '{$prop}' in PROP_TYPES."
            );
        }
    }

    self::$validatedClasses[$class] = true;
}
```

---

## Database Changes

Not applicable. This is a client SDK library — no database schema is managed by this package.

---

## Public API Specification (New/Modified Methods)

### AbstractResource Modifications

```php
// --- Modified: __set() with type coercion integration (Phase 3, v0.9.0) ---

public function __set(string $name, mixed $value): void
{
    if (AbstractEntity::isProp(static::API_ENTITY, $name)) {
        $canSet = (
            $this->hydrationMode
            || !in_array($name, static::READONLY, true)
            || ($this->props['id'] ?? null) === null && in_array($name, static::CREATEONLY, true)
        );
        if ($canSet) {
            // Type coercion: convert to typed PHP value
            if ($value !== null) {
                $type = static::PROP_TYPES[$name] ?? null;
                if ($type !== null) {
                    $value = Converter::convertToPhpValue($value, $type);
                }
            }
            $this->props[$name] = $value;
        }
    } else {
        $this->unlisted[$name] = $value;
    }
}


// --- Modified: create() body assembly with serialization (Phase 3, v0.9.0) ---
// In the section that builds $createWith from $this->props:

// Before: $createWith[$k] = $v;
// After:  $createWith[$k] = Converter::convertForRequest($v, static::PROP_TYPES[$k] ?? 'text');


// --- Modified: update() body assembly with serialization (Phase 3, v0.9.0) ---
// In the section that builds $update from dirty keys:

// Before: $update[$k] = $this->props[$k];
// After:  $update[$k] = Converter::convertForRequest($this->props[$k], static::PROP_TYPES[$k] ?? 'text');


// --- Modified: flatten() handles PaymoDateTime JSON serialization (Phase 3, v0.9.0) ---
// No change needed — PaymoDateTime implements JsonSerializable.
// json_decode(json_encode($data)) in flatten() will produce strings from PaymoDateTime.


// --- NEW: toArray() convenience method (Phase 1, v0.7.0) ---

/**
 * Returns a plain associative array of all properties and included relations.
 * Wraps flatten() for consumers who prefer arrays over stdClass.
 */
public function toArray(array $options = []): array
{
    return (array) $this->flatten($options);
}


// --- NEW: toJson() convenience method (Phase 1, v0.7.0) ---

/**
 * Returns a JSON string of all properties and included relations.
 *
 * @param int $options json_encode options (e.g., JSON_PRETTY_PRINT)
 */
public function toJson(int $options = 0): string
{
    return json_encode($this->flatten(), $options);
}
```

### AbstractCollection Modifications

```php
// --- Modified: flatten() overload with property plucking (Phase 1, v0.7.0) ---
// PHP 7.4 compatible: remove array type hint to accept string|array

/**
 * Flatten collection to an array.
 *
 * When called with no arguments or an array: returns array of flattened stdClass objects keyed by ID.
 * When called with a string: returns flat array of that property's values (pluck behavior).
 *
 * @param string|array $optionsOrProperty Property name to pluck, or options array
 * @return array
 */
public function flatten($optionsOrProperty = []): array
{
    // NEW: string argument → pluck single property values
    if (is_string($optionsOrProperty)) {
        $property = $optionsOrProperty;
        $result = [];
        foreach ($this->data as $resource) {
            $result[] = $resource->$property;
        }
        return $result;
    }

    // EXISTING: array argument → flatten all resources
    $options = $optionsOrProperty;
    $items = [];
    foreach ($this->data as $k => $resource) {
        $key = isset($options['array']) && $options['array'] ? count($items) : (int) $k;
        $items[$key] = $resource->flatten($options);
    }
    return $items;
}


// --- NEW: fetchAll() auto-pagination (Phase 3, v0.9.0) ---

/**
 * Fetch all records across all pages via automatic pagination.
 *
 * Iterates pages until fewer results than page_size are returned or the safety cap is reached.
 * Memory: at any point, holds accumulated results + one current page (satisfies NFR-004).
 *
 * @param array|null $fields Field names and includes to select
 * @param array|null $conditions RequestCondition objects for WHERE filtering
 * @param array $options Request options. Additional key: 'pageSize' (default 200, max 500)
 * @return static This collection with all merged results
 */
public function fetchAll(?array $fields = null, ?array $conditions = null, array $options = []): static
{
    $cap = static::FETCHALL_CAP;
    $pageSize = min($options['pageSize'] ?? 200, 500);
    $page = 0;
    $accumulated = [];

    do {
        // Set pagination for this page
        $this->paginationPage = $page;
        $this->paginationPageSize = $pageSize;

        // fetch() clears $this->data via _hydrate() then populates with current page
        $this->fetch($fields, $conditions, $options);

        $currentPage = $this->data;
        $pageCount = count($currentPage);

        // Merge current page into accumulated results (keyed by ID, no duplicates)
        $accumulated = $accumulated + $currentPage;

        $page++;
    } while ($pageCount >= $pageSize && count($accumulated) < $cap);

    // Set final accumulated data and clear pagination state
    $this->data = $accumulated;
    $this->paginationPage = null;
    $this->paginationPageSize = null;

    return $this;
}


// --- NEW: toArray() convenience method (Phase 1, v0.7.0) ---

public function toArray(array $options = []): array
{
    return array_values($this->flatten($options));
}


// --- NEW: toJson() convenience method (Phase 1, v0.7.0) ---

public function toJson(int $options = 0): string
{
    return json_encode($this->flatten(), $options);
}


// --- Modified: validateFetch() for parent context enforcement (Phase 3, v0.9.0) ---
// Override in FileCollection, BookingCollection, InvoiceItemCollection, EstimateItemCollection

// Example for FileCollection:
protected function validateFetch(array $fields = [], array $where = []): void
{
    $allConditions = array_merge($this->whereConditions, $where);
    $hasParentFilter = false;
    foreach ($allConditions as $condition) {
        if ($condition->prop === 'project_id') {
            $hasParentFilter = true;
            break;
        }
    }

    if (!$hasParentFilter) {
        $devMode = Configuration::get('devMode') ?? false;
        $message = 'File collection list requires a project_id WHERE condition. '
            . 'Use File::list()->where(File::where(\'project_id\', $id))->fetch()';
        if ($devMode) {
            throw new \RuntimeException($message);
        }
        // Production: log warning but allow the call
        Error::throw('warn', null, null, $message);
    }
}
```

### Paymo Class Modifications

```php
// --- Modified: connect() with PSR-3 logger parameter (Phase 2, v0.8.0) ---

/**
 * @param string|array|null $apiKeyUser API key string, or [username, password] array
 * @param bool|null $useLogging Override logging setting
 * @param string|null $connectionName Human-readable connection label
 * @param string|null $connectionUrl Override API base URL
 * @param \Psr\Log\LoggerInterface|null $logger PSR-3 logger instance (NEW: FR-021)
 */
public static function connect(
    string|array|null $apiKeyUser = null,
    ?bool $useLogging = null,
    ?string $connectionName = null,
    ?string $connectionUrl = null,
    ?\Psr\Log\LoggerInterface $logger = null
): Paymo {
    // ... existing connection logic ...

    // NEW: Set PSR-3 logger if provided
    if ($logger !== null) {
        $connection->setLogger($logger);
    }

    return $connection;
}


// --- NEW: PSR-3 logger property and methods (Phase 2, v0.8.0) ---

private ?PsrLogAdapter $psrLogger = null;

public function setLogger(\Psr\Log\LoggerInterface $logger): void
{
    $this->psrLogger = new PsrLogAdapter($logger);
}

public function getLogger(): ?PsrLogAdapter
{
    return $this->psrLogger;
}


// --- Modified: execute() with cache-hit tracking and post-mutation invalidation (Phase 1/3) ---

public function execute(RequestAbstraction $request, array $options = []): RequestResponse
{
    $response = new RequestResponse();
    $skipCache = $options['skipCache'] ?? false;

    // Cache check (existing logic, enhanced with cacheHit tracking)
    $cacheKey = $request->makeCacheKey()->cacheKey;
    if ($this->useCache && $cacheKey && !$skipCache) {
        $cached = Cache::fetch($cacheKey);
        if ($cached !== null && $cached !== false) {
            $response->hydrateData($cacheKey, $cached);
            $response->cacheHit = true;    // NEW: FR-019
            return $response;
        }
    }

    // ... existing HTTP execution logic ...

    // Cache store (existing)
    if ($this->useCache && $response->success && $cacheKey) {
        Cache::store($cacheKey, $response);
    }

    // PSR-3 logging (NEW: FR-021)
    if ($this->psrLogger !== null) {
        $this->psrLogger->logRequest($request->method, $request->resourceUrl, $request->data);
        $this->psrLogger->logResponse(
            $response->responseCode ?? 0,
            $request->resourceUrl ?? '',
            $response->responseTime ?? 0,
            $response->cacheHit
        );
    }

    // Cache invalidation after successful mutations (NEW: FR-017, Phase 3)
    if ($this->useCache && $response->success
        && in_array($request->method, ['POST', 'PUT', 'DELETE'], true)
    ) {
        $resourceBase = explode('/', $request->resourceUrl ?? '')[0] ?? '';
        if ($resourceBase !== '') {
            Cache::invalidateByResource($resourceBase);
        }
    }

    return $response;
}


// --- Modified: devMode constant (FR-002) --- Phase 1, v0.7.0
// REMOVE the global define() at top of Paymo.php:
//   define('PAYMO_DEVELOPMENT_MODE', true);  // DELETE THIS LINE
// REPLACE all usages of PAYMO_DEVELOPMENT_MODE with:
//   (bool)(Configuration::get('devMode') ?? false)
// The default.paymoapi.config.json already has "devMode": false
```

### Error Class Modifications (Phase 2, v0.8.0)

```php
// --- Modified: throw() accepts ErrorSeverity enum or string ---

/**
 * @param ErrorSeverity|string $severity Error severity level
 * @param mixed $error Error data (array, object, or null)
 * @param int|string|null $code Error code
 * @param string|null $message Error message
 */
public static function throw(
    ErrorSeverity|string $severity,
    mixed $error = null,
    int|string|null $code = null,
    ?string $message = null
): void {
    $severityValue = ($severity instanceof ErrorSeverity) ? $severity->value : $severity;
    self::i()->handleError($severityValue, $error, $code, $message);
}


// --- NEW: setErrorHandler() on Paymo connection for consumer routing ---
// Added to Paymo class, delegates to Error singleton

/**
 * Register a custom error handler that receives errors by severity.
 *
 * @param callable $handler function(ErrorSeverity $severity, string $message, mixed $error, int|string|null $code): void
 */
public function setErrorHandler(callable $handler): void
{
    Error::i()->setCustomHandler($handler);
}


// --- NEW: setCustomHandler() on Error class ---

private ?callable $customHandler = null;

public function setCustomHandler(callable $handler): void
{
    $this->customHandler = $handler;
}

// In handleError(), before existing handler dispatch:
if ($this->customHandler !== null) {
    ($this->customHandler)(
        ErrorSeverity::from($severity),
        $message ?? '',
        $error,
        $code
    );
}
```

### EntityMap: Typo Fix (Phase 1, v0.7.0)

```php
// src/Entity/EntityMap.php, line ~260
// BEFORE:
if (!is_subclass_of($className, 'AbstractResourcce')) {
// AFTER:
if (!is_subclass_of($className, AbstractResource::class)) {
```

Note: Also change from string class name to `::class` constant for type safety.

### RequestCondition: in(me) Support (Phase 1, v0.7.0)

```php
// Modification to Converter::convertOperatorValue()
// In the 'in' / 'not in' case:

case 'in':
case 'not in':
    // NEW: Support 'me' literal for current user filtering
    if ($value === 'me' || $value === ['me']) {
        return "{$prop} {$operator} (me)";
    }
    // Existing array handling...
    if (is_array($value)) {
        $v = implode(',', $value);
        return "{$prop} {$operator} ({$v})";
    }
    return "{$prop} {$operator} ({$value})";
```

### AbstractEntity: Include Parsing for Partial and Nested (Phase 3, v0.9.0)

```php
// Modifications to AbstractEntity::cleanupForRequest()
// In the section that separates includes from regular field selections:

$regularIncludes = [];
$partialIncludes = [];

foreach ($includes as $inc) {
    if (str_contains($inc, '(')) {
        // Partial include: tasks(id,name,due_date)
        $partialIncludes[] = $inc;

        // DevMode validation: extract key and sub-fields
        if (Configuration::get('devMode') ?? false) {
            preg_match('/^(\w+)\(([^)]+)\)$/', $inc, $matches);
            if ($matches) {
                $incKey = $matches[1];
                $subFields = array_map('trim', explode(',', $matches[2]));
                // Validate $incKey exists in INCLUDE_TYPES
                // Validate each $subField exists in the included resource's PROP_TYPES
            }
        }
    } elseif (str_contains($inc, '.')) {
        // Nested include: tasks.entries
        $parts = explode('.', $inc);
        $maxDepth = (int)(Configuration::get('includes.maxDepth') ?? 2);

        if (count($parts) > $maxDepth) {
            if (Configuration::get('devMode') ?? false) {
                throw new \RuntimeException(
                    "Nested include '{$inc}' exceeds maximum depth of {$maxDepth}. "
                    . "Configure 'includes.maxDepth' to increase the limit."
                );
            }
        }

        // DevMode: validate each level against parent's INCLUDE_TYPES
        if (Configuration::get('devMode') ?? false) {
            // Level 1: $parts[0] must be in current resource's INCLUDE_TYPES
            // Level 2: $parts[1] must be in the level-1 resource's INCLUDE_TYPES
        }

        $regularIncludes[] = $inc; // Dot notation passed directly to API
    } else {
        $regularIncludes[] = $inc;
    }
}

// Compile to RequestAbstraction
$requestAbstraction->include = count($regularIncludes) > 0
    ? implode(',', $regularIncludes)
    : null;
$requestAbstraction->partialInclude = count($partialIncludes) > 0
    ? implode(',', $partialIncludes)
    : null;
```

### Paymo::buildRequestProps() — Partial Include Query Param (Phase 3, v0.9.0)

```php
// In buildRequestProps(), add partial_include to query params:

if ($request->partialInclude !== null) {
    $props['query']['partial_include'] = $request->partialInclude;
}
```

---

## Component Architecture (Class Hierarchy)

### New Classes

| Class | Location | Parent | Phase |
|-------|----------|--------|-------|
| `ErrorSeverity` | `src/Utility/ErrorSeverity.php` | (enum, no parent) | v0.8.0 |
| `PaymoDateTime` | `src/Utility/PaymoDateTime.php` | `\DateTimeImmutable` | v0.9.0 |
| `PsrLogAdapter` | `src/Utility/PsrLogAdapter.php` | (none) | v0.8.0 |

### Modified Classes

| Class | Modifications | Phase |
|-------|--------------|-------|
| `AbstractResource` | `WRITEONLY` constant, `validateConstants()`, `toArray()`, `toJson()`, modified `__set()` (coercion), modified `create()`/`update()` (serialization) | v0.7.0 (constants), v0.9.0 (coercion) |
| `AbstractCollection` | `FETCHALL_CAP` constant, `fetchAll()`, modified `flatten()` (pluck overload), `toArray()`, `toJson()` | v0.7.0 (flatten/toArray/toJson), v0.9.0 (fetchAll) |
| `Cache` | `$clear_callback`, `invalidateByResource()`, `clearAll()`, modified `registerCacheMethods()` (3rd param) | v0.9.0 |
| `Configuration` | Rewrite: `Adbar\Dot` replaces `Noodlehaus\Config`, static `reset()` | v0.7.0 (reset), v0.8.0 (rewrite) |
| `Converter` | `convertToPhpValue()`, `convertForRequest()`, `hydrateDateTime()`, `hydrateBoolean()`, modified `convertOperatorValue()` (in(me)) | v0.7.0 (in(me)), v0.9.0 (coercion) |
| `Error` | `ErrorSeverity` integration, `setCustomHandler()`, modified `throw()` (accepts enum) | v0.8.0 |
| `EntityMap` | Typo fix (`AbstractResourcce` → `AbstractResource::class`) | v0.7.0 |
| `Paymo` | Remove `define()`, `setLogger()`/`getLogger()`, `setErrorHandler()`, modified `connect()` (logger param), modified `execute()` (cacheHit, invalidation, PSR-3 logging) | v0.7.0 (devMode), v0.8.0 (PSR-3), v0.9.0 (invalidation) |
| `RequestAbstraction` | `$partialInclude` property, modified `makeCacheKey()` (resource-prefixed keys) | v0.9.0 |
| `RequestResponse` | `$cacheHit` property | v0.7.0 |
| `AbstractEntity` | Modified `cleanupForRequest()` (partial/nested include parsing) | v0.9.0 |

### Modified Resource Classes (Phase 1, v0.7.0)

| Resource | Modification |
|----------|-------------|
| `Expense` | Add `'download_token' => 'text'` to PROP_TYPES, add `'download_token'` to READONLY |
| `Report` | Add `'download_token' => 'text'` to PROP_TYPES, add `'download_token'` to READONLY |
| All 38 resources | Add `WRITEONLY = []` constant (inherited default from AbstractResource if not overridden) |

### Modified Collection Classes (Phase 3, v0.9.0)

| Collection | Modification |
|-----------|-------------|
| `FileCollection` | Add `validateFetch()` enforcing `project_id` WHERE condition |
| `BookingCollection` | Add `validateFetch()` enforcing date range OR user/task/project ID |
| `InvoiceItemCollection` (new) | Create class with `validateFetch()` enforcing `invoice_id` |
| `EstimateItemCollection` (new) | Create class with `validateFetch()` enforcing `estimate_id` |

Note: `InvoiceItemCollection` and `EstimateItemCollection` may need to be created as new classes if they don't already exist (currently using generic `EntityCollection`). The config `classMap` entries for `invoiceitems` and `estimateitems` must be updated to reference the new collection classes.

---

## State Management (Singleton State Changes)

### Paymo Connection State

```php
// Added property (Phase 2, v0.8.0):
private ?PsrLogAdapter $psrLogger = null;
```

### Cache Singleton State

```php
// Added property (Phase 3, v0.9.0):
public $clear_callback = null;
```

### Configuration Singleton State

```php
// Phase 2, v0.8.0: Internal property changes
// Before: private $config;  (Noodlehaus\Config instance)
// After:  private Dot $config;  (Adbar\Dot instance)
```

### Error Singleton State

```php
// Added property (Phase 2, v0.8.0):
private ?callable $customHandler = null;
```

### AbstractResource Static State

```php
// Added property (Phase 1, v0.7.0):
private static array $validatedClasses = [];  // FR-011 cached validation results
```

---

## New Dependencies

### Added

| Package | Version Constraint | Justification | Phase |
|---------|-------------------|---------------|-------|
| `psr/log` | `^2.0 \|\| ^3.0` | PSR-3 `LoggerInterface` for FR-021 (optional logger injection). Interface-only package — zero runtime overhead. v2 supports PHP 8.0+, v3 supports PHP 8.0+ with string-backed log level enums. Supporting both maximizes consumer compatibility. | v0.8.0 |

### Removed

| Package | Current Version | Justification | Phase |
|---------|----------------|---------------|-------|
| `hassankhan/config` | `^3.2` | Replaced by direct use of `adbario/php-dot-notation` (already a transitive dependency). Last maintained 2021. Wraps Adbar\Dot with unused YAML/XML/INI support. See AD-007. | v0.8.0 |

### Updated

| Package | Current | Target | Justification | Phase |
|---------|---------|--------|---------------|-------|
| `php` | `>=7.4` | `>=8.1` | See AD-001. Enables enums, union types, readonly, match, named args. | v0.8.0 |

### Unchanged

| Package | Version | Reason |
|---------|---------|--------|
| `guzzlehttp/guzzle` | `^7.8` | No changes needed. Guzzle 7 supports PHP 8.1+. |
| `adbario/php-dot-notation` | `^3.3` | Already present. Now used directly by Configuration instead of through hassankhan/config. |
| `ext-json` | `*` | Still required for JSON config parsing. |

### composer.json Target State (Phase 2, v0.8.0)

```json
{
    "require": {
        "php": ">=8.1",
        "ext-json": "*",
        "guzzlehttp/guzzle": "^7.8",
        "adbario/php-dot-notation": "^3.3",
        "psr/log": "^2.0 || ^3.0"
    },
    "autoload": {
        "psr-4": {
            "Jcolombo\\PaymoApiPhp\\": "src"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Jcolombo\\PaymoApiPhp\\Tests\\": "tests"
        }
    },
    "scripts": {
        "test": "php tests/validate",
        "test:dry-run": "php tests/validate --dry-run",
        "test:verbose": "php tests/validate --verbose"
    }
}
```

---

## Delivery Phases

### Phase 1: v0.7.0 — Bug Fixes & Backward-Compatible Additions

**PHP Requirement:** `>=7.4` (unchanged)
**Goal:** Fix all confirmed bugs, add structural foundations, and deliver low-risk backward-compatible additions. Any PHP 7.4 consumer can upgrade to v0.7.0 with zero code changes.

**Functional Requirements:**

| FR | Summary | Risk |
|----|---------|------|
| FR-001 | Fix EntityMap `"AbstractResourcce"` typo → `AbstractResource::class` | Minimal — fixes broken validation |
| FR-002 | Replace hardcoded `define('PAYMO_DEVELOPMENT_MODE', true)` with `Configuration::get('devMode')` | Minimal — default false matches production expectation |
| FR-003 | Fix autoload-dev namespace: `Jcolombo\\PaymoApiPhp\\Tests\\` → `tests/` | Minimal — affects dev only |
| FR-006 | Verify Session `id` type is `text` in PROP_TYPES | Verification only |
| FR-007 | Verify Invoice/Estimate `language` in READONLY | Verification only |
| FR-008 | Add `download_token` to Expense/Report PROP_TYPES as `text`, READONLY | Additive — new property |
| FR-009 | Add `WRITEONLY = []` constant to AbstractResource | Additive — empty default |
| FR-010 | Audit for WRITEONLY-applicable properties (documentation artifact) | Research only |
| FR-011 | Add `validateConstants()` in devMode (cached per class) | Additive — devMode only |
| FR-016 | Support `in(me)` literal in WHERE conditions | Additive — new filter value |
| FR-019 | Add `$cacheHit` boolean to RequestResponse | Additive — new property |
| FR-024 | Add `Configuration::reset()` static method (singleton destruction) | Additive — new method |
| FR-027 | Add Composer `test`, `test:dry-run`, `test:verbose` scripts | Additive — scripts section |
| FR-032 | Add `flatten($property)` string overload on AbstractCollection | Additive — parameter widening |
| FR-033 | Add `toArray()` and `toJson()` on AbstractResource and AbstractCollection | Additive — new methods |
| FR-034 | Document 2,500-item include truncation + optional devMode warning | Documentation + small warning |
| FR-035 | Document Task/TimeEntry HTML in description fields | Documentation only |
| FR-036 | Document retainer_id on Project | Documentation only |
| FR-037 | Document webhook delete/update payload limitations | Documentation only |
| FR-038 | Document sensitive Company properties | Documentation only |

**Completion Criteria:**
- All 3 bugs fixed (FR-001, FR-002, FR-003)
- `composer test` runs the custom test framework via CLI
- `Configuration::reset()` destroys singleton; subsequent `get()` creates fresh instance
- `$response->cacheHit` is `true` when served from cache, `false` when from API
- `flatten('name')` on a collection returns `['Project A', 'Project B', ...]`
- `toArray()` and `toJson()` work on both resources and collections
- `Task::where('user_id', 'me', 'in')` generates `user_id in (me)` in the query
- OVERRIDES.md has 5 new documentation entries (FR-034 through FR-038)
- Existing test suite passes with zero regressions

**Tag:** `v0.7.0`

---

### Phase 2: v0.8.0 — PHP 8.1 + Dependency Modernization

**PHP Requirement:** `>=8.1`
**Goal:** Modernize the foundation. This is the sole "environment-breaking" boundary. Consumers who cannot upgrade PHP stay on v0.7.x.

**Functional Requirements:**

| FR | Summary | Risk |
|----|---------|------|
| FR-022 | Bump `composer.json` PHP minimum from `>=7.4` to `>=8.1` | Environment change — only breaking change |
| FR-023 | Replace hassankhan/config with direct Adbar\Dot in Configuration | Medium — rewrites Configuration internals |
| FR-020 | Implement ErrorSeverity enum; integrate with Error class | Low — backed enum values match existing strings |
| FR-021 | Add PSR-3 logging adapter; add `psr/log` dependency; `setLogger()` on Paymo | Low — optional, additive |

**Implementation notes for FR-022:**
- Change `composer.json` `"php"` requirement
- Audit codebase for PHP 7.4-only patterns (none expected — existing code is PHP 7.4 compatible upward)
- Adopt PHP 8.1 features where they improve clarity:
  - `match` expressions replacing verbose switch blocks (Error.php, Converter.php)
  - `readonly` on immutable value properties (PaymoDateTime)
  - Union type hints on public method parameters
  - Named arguments in internal calls where parameter order is unclear
  - `enum` for ErrorSeverity
- Do NOT rewrite the entire codebase to use PHP 8.1 features — only adopt where the change improves the specific code being modified

**Implementation notes for FR-023:**
- Create new `Configuration` class (see Data Structures section above)
- Verify all `Configuration::get()` return values match previous behavior
- Remove `hassankhan/config` from `composer.json`
- Remove the `class Config extends OriginalConfig {}` wrapper class
- Run full test suite to verify no configuration regressions

**Completion Criteria:**
- `composer.json` requires `php >= 8.1`
- `hassankhan/config` is absent from `composer.json` and `composer.lock`
- `psr/log` is present in `composer.json`
- `ErrorSeverity::fromHttpStatus(429)` returns `ErrorSeverity::WARN`
- `ErrorSeverity::fromHttpStatus(401)` returns `ErrorSeverity::FATAL`
- Injecting a PSR-3 logger via `Paymo::connect(logger: $monolog)` results in log entries for API calls
- `$connection->setLogger($monolog)` works post-construction
- `Configuration::get('connection.url')` returns the expected value after rewrite
- Existing test suite passes with zero regressions

**Tag:** `v0.8.0`

---

### Phase 3: v0.9.0 — Core Feature Development

**PHP Requirement:** `>=8.1`
**Goal:** Deliver all major new features that leverage the modernized foundation.

**Functional Requirements:**

| FR | Summary | Risk |
|----|---------|------|
| FR-005 (Ph1+Ph2) | Three-direction type coercion: hydration (`convertToPhpValue`) + serialization (`convertForRequest`) | High — affects all 38 resources. Mitigated by `__toString()` on PaymoDateTime |
| FR-012 | `fetchAll()` auto-pagination on AbstractCollection | Medium — new pagination loop logic |
| FR-013 | Parent context enforcement in FileCollection, BookingCollection, InvoiceItemCollection, EstimateItemCollection | Medium — may reject previously-allowed calls |
| FR-017 | Cache invalidation after POST/PUT/DELETE via `Cache::invalidateByResource()` | Medium — changes cache key format |
| FR-018 | `registerCacheMethods()` 3rd `$clear` callback parameter | Low — optional parameter, backward compatible |
| FR-014 | Partial include syntax: `tasks(id,name,due_date)` → `partial_include` query param | Low — additive syntax |
| FR-015 | Nested include dot notation: `tasks.entries` → `include=tasks.entries` | Low — additive syntax |

**Dependency chain within this phase:**
1. FR-017 depends on FR-018 (invalidation uses clear callback)
2. FR-017 requires modified cache key format in `RequestAbstraction::makeCacheKey()`
3. FR-005 must be implemented before FR-012 (fetchAll returns typed data)
4. FR-013 depends on existing collection classes being identified for enforcement
5. FR-014 and FR-015 can be implemented in parallel, both modify `cleanupForRequest()`

**Implementation order:**
1. FR-018 (cache clear callback) — foundation
2. FR-017 (cache invalidation) — uses FR-018
3. FR-005 (type coercion) — core feature
4. FR-012 (fetchAll) — uses typed data from FR-005
5. FR-013 (parent context enforcement) — independent
6. FR-014 (partial includes) — independent
7. FR-015 (nested includes) — independent

**Completion Criteria:**
- `$project->created_on` returns a `PaymoDateTime` instance after `fetch()`
- `(string)$project->created_on` returns the original API string
- `json_encode($project->flatten())` produces API-compatible string for datetime fields
- `$project->set('due_date', new \DateTimeImmutable('2024-06-15'))` → `convertForRequest()` produces `'2024-06-15'`
- `Invoice::list()->fetchAll()` returns all invoices across pages; stops at safety cap or last page
- `File::list()->fetch()` without `project_id` WHERE throws `RuntimeException` in devMode
- After `Invoice::new()->set([...])->create()`, cached invoice lists are invalidated
- `registerCacheMethods($fetch, $store, $clear)` works; `registerCacheMethods($fetch, $store)` still works
- `$project->fetch($id, ['tasks(id,name)'])` generates `partial_include=tasks(id,name)`
- `$project->fetch($id, ['tasks.entries'])` generates `include=tasks.entries`
- Existing test suite passes with zero regressions

**Tag:** `v0.9.0`

---

### Phase 4: v1.0.0-alpha — Investigations, Properties & Final Polish

**PHP Requirement:** `>=8.1`
**Goal:** Complete API surface coverage via live investigation, add undocumented properties, and finalize all documentation for the 1.0.0-alpha release.

**Functional Requirements:**

| FR | Summary | Type |
|----|---------|------|
| FR-040 | Property discovery against live API for all 38 resources | Investigation → artifact |
| FR-041 | Live test 4 leave management endpoints | Investigation → artifact |
| FR-042 | Filter operator validation matrix for all resources | Investigation → artifact |
| FR-043 | Spot-check SDK-only include relationships (~25 relationships) | Investigation → artifact |
| FR-044 | Verify per-resource HAS condition support | Investigation → artifact |
| FR-045 | Test filter support for undocumented properties | Investigation → artifact |
| FR-046 | Compare all 38 SDK classes against full API inventory | Investigation → artifact |
| FR-047 | Reconcile include relationship count discrepancy (82 vs 98) | Investigation → artifact |
| FR-048 | Test 3-level and 4-level nested include depth | Investigation → artifact |
| FR-049 | Probe `GET /api/currencies` endpoint | Investigation → artifact |
| FR-050 | Test compound resource name response key anomalies | Investigation → artifact |
| FR-051 | Investigate StatsReport additional report types | Investigation → artifact |
| FR-052 | Determine actual API rate limit values | Investigation → artifact |
| FR-004 | Add undocumented properties to resource PROP_TYPES | Implementation (uses FR-040/FR-046 artifacts) |
| FR-039 | Update CLAUDE.md, PACKAGE-DEV.md, README.md for v1.0.0-alpha | Documentation |

**Investigation artifact format:** Each investigation produces a markdown file in the task artifacts directory:
- `investigation-{FR-number}-{description}.md`
- Structured with: Methodology, Findings, Recommendations, Raw Data

**FR-004 implementation (depends on FR-040 + FR-046):**
- For each property discovered in FR-040 that is absent from SDK PROP_TYPES:
  - Add to resource's `PROP_TYPES` with correct type
  - Add to `READONLY` unless write behavior is confirmed
  - Add `// Undocumented` comment per OVERRIDE-011 policy
  - If the property conflicts with existing override documentation, consult OVERRIDES.md before adding

**Completion Criteria:**
- All 13 investigation FRs produce artifacts with findings
- FR-004: undocumented properties added to resource classes (count determined by FR-040/FR-046 results)
- CLAUDE.md updated with: new methods, new constants, new resources (if any), PHP 8.1 requirement, changed dependencies, new configuration options, type coercion behavior
- PACKAGE-DEV.md updated with: new class inventory, updated architecture diagram, new constant documentation
- README.md updated with: v1.0.0-alpha changelog summary, updated installation instructions (PHP 8.1), new feature examples
- Full test suite passes
- `composer validate` passes
- `composer dump-autoload` succeeds without warnings

**Tag:** `v1.0.0-alpha`

---

## Verification Plan

### Testing Strategy per Phase

#### Phase 1 (v0.7.0) Tests

| Test | Type | Location | Verifies |
|------|------|----------|----------|
| EntityMap overload validation works | Unit | `tests/ResourceTests/EntityMapTest.php` | FR-001 |
| devMode reads from config | Unit | `tests/UtilityTests/DevModeTest.php` | FR-002 |
| Composer test scripts execute | CLI | `composer test --dry-run` | FR-003/FR-027 |
| Configuration::reset() destroys singleton | Unit | `tests/UtilityTests/ConfigurationTest.php` | FR-024 |
| RequestResponse cacheHit tracking | Unit | `tests/UtilityTests/RequestResponseTest.php` | FR-019 |
| in(me) filter generates correct query | Unit | `tests/UtilityTests/ConverterTest.php` | FR-016 |
| Collection flatten('property') plucks values | Unit | `tests/CollectionTests/FlattenPluckTest.php` | FR-032 |
| Resource toArray()/toJson() | Unit | `tests/ResourceTests/ConvenienceMethodsTest.php` | FR-033 |
| validateConstants() catches conflicts | Unit | `tests/ResourceTests/ConstantValidationTest.php` | FR-011 |
| Full regression suite | Integration | `php tests/validate` | All existing functionality |

#### Phase 2 (v0.8.0) Tests

| Test | Type | Location | Verifies |
|------|------|----------|----------|
| ErrorSeverity enum values match strings | Unit | `tests/UtilityTests/ErrorSeverityTest.php` | FR-020 |
| ErrorSeverity::fromHttpStatus() mapping | Unit | `tests/UtilityTests/ErrorSeverityTest.php` | FR-020 |
| PSR-3 logger receives API call entries | Integration | `tests/UtilityTests/PsrLogAdapterTest.php` | FR-021 |
| Configuration uses Adbar\Dot directly | Unit | `tests/UtilityTests/ConfigurationTest.php` | FR-023 |
| hassankhan/config not in autoloader | CLI | `composer dump-autoload --no-dev` | FR-023 |
| Full regression suite | Integration | `php tests/validate` | Backward compatibility |

#### Phase 3 (v0.9.0) Tests

| Test | Type | Location | Verifies |
|------|------|----------|----------|
| Converter::convertToPhpValue() for all types | Unit | `tests/UtilityTests/ConverterTest.php` | FR-005 |
| PaymoDateTime __toString() returns raw string | Unit | `tests/UtilityTests/PaymoDateTimeTest.php` | FR-005 |
| PaymoDateTime instanceof DateTimeImmutable | Unit | `tests/UtilityTests/PaymoDateTimeTest.php` | FR-005 |
| json_encode(resource->flatten()) produces strings | Unit | `tests/ResourceTests/TypeCoercionTest.php` | FR-005 |
| Converter::convertForRequest() for all types | Unit | `tests/UtilityTests/ConverterTest.php` | FR-005 |
| fetchAll() returns all pages | Integration | `tests/CollectionTests/FetchAllTest.php` | FR-012 |
| fetchAll() respects safety cap | Unit | `tests/CollectionTests/FetchAllTest.php` | FR-012 |
| Cache invalidation after create() | Integration | `tests/CacheTests/InvalidationTest.php` | FR-017 |
| Cache invalidation after delete() | Integration | `tests/CacheTests/InvalidationTest.php` | FR-017 |
| registerCacheMethods() 2 callbacks (backward compat) | Unit | `tests/CacheTests/CacheTest.php` | FR-018 |
| registerCacheMethods() 3 callbacks | Unit | `tests/CacheTests/CacheTest.php` | FR-018 |
| File collection rejects missing project_id | Unit | `tests/CollectionTests/ParentFilterTest.php` | FR-013 |
| Partial include generates partial_include param | Unit | `tests/RequestTests/PartialIncludeTest.php` | FR-014 |
| Nested include validates depth | Unit | `tests/RequestTests/NestedIncludeTest.php` | FR-015 |
| Full regression suite | Integration | `php tests/validate` | Backward compatibility |

#### Phase 4 (v1.0.0-alpha) Tests

| Test | Type | Location | Verifies |
|------|------|----------|----------|
| Per-resource property coverage | Integration | Investigation scripts | FR-040/FR-046 |
| New undocumented properties accessible | Integration | `tests/ResourceTests/*Test.php` | FR-004 |
| Documentation accuracy | Manual | Review CLAUDE.md, README.md | FR-039 |
| Final regression suite | Integration | `php tests/validate` | Full v1.0.0-alpha quality |

### Lint/Build Checks (All Phases)

```bash
# PHP syntax check (all modified files)
find src/ -name "*.php" -exec php -l {} \;

# Composer validation
composer validate --strict

# Autoloader generation (verify no test classes in production)
composer dump-autoload --no-dev
# Verify: no Jcolombo\PaymoApiPhp\Tests\* classes in autoloader

# Dependency check
composer install --no-dev
# Verify: hassankhan/config not installed (Phase 2+)
# Verify: psr/log installed (Phase 2+)
```

### Manual Verification Steps

1. **Cache invalidation end-to-end (Phase 3):**
   - Enable caching: `$connection->useCache = true; Cache::lifespan(600);`
   - Fetch invoice list: `$invoices = Invoice::list()->fetch();`
   - Create invoice: `Invoice::new()->set([...])->create();`
   - Fetch invoice list again: verify new invoice appears (cache was invalidated)

2. **Type coercion round-trip (Phase 3):**
   - Fetch a project: `$project = Project::new()->fetch($id);`
   - Verify: `$project->created_on instanceof \DateTimeImmutable` → `true`
   - Verify: `echo $project->created_on` → outputs ISO 8601 string
   - Verify: `json_encode($project->flatten())` → datetime is a string, not an object

3. **PSR-3 logging integration (Phase 2):**
   - Create a Monolog logger writing to a test file
   - Connect: `Paymo::connect($key, logger: $monolog)`
   - Execute any API call
   - Verify: test file contains log entries with API request/response details

4. **Backward compatibility smoke test (All Phases):**
   - Take a known consumer script from v0.6.1
   - Run against the new version
   - Verify: identical output, no errors, no deprecation warnings

---

## Appendix: FR Cross-Reference by Phase

| Phase | FRs | Count |
|-------|-----|-------|
| v0.7.0 | FR-001, FR-002, FR-003, FR-006, FR-007, FR-008, FR-009, FR-010, FR-011, FR-016, FR-019, FR-024, FR-027, FR-032, FR-033, FR-034, FR-035, FR-036, FR-037, FR-038 | 20 |
| v0.8.0 | FR-020, FR-021, FR-022, FR-023 | 4 |
| v0.9.0 | FR-005, FR-012, FR-013, FR-014, FR-015, FR-017, FR-018 | 7 |
| v1.0.0-alpha | FR-004, FR-039, FR-040–FR-052 | 15 |
| **Total** | | **46** |

Note: 6 FRs from the 52 in-scope items are cross-references (FR-026 = FR-011, FR-028 = FR-001) or verification-only items (FR-006, FR-007) counted above under their primary assignment.

---

## Appendix: Backward Compatibility Matrix

| Change | Category | Impact | Mitigation |
|--------|----------|--------|------------|
| PHP 7.4 → 8.1 | Environment | Consumers on PHP 7.4 cannot upgrade past v0.7.x | Isolated at v0.8.0 boundary |
| `PAYMO_DEVELOPMENT_MODE` constant removed | Internal | Code referencing this constant directly will error | Replace with `Configuration::get('devMode')` |
| `Configuration->reset()` → `Configuration::reset()` | Internal | Instance method callers error | Method is internal, not consumer-facing |
| hassankhan/config removed | Dependency | Consumers explicitly using hassankhan/config won't have it | SDK never exposed this dependency |
| Cache key format change | Cache | Existing cached data becomes orphaned | Caches are ephemeral, expire via TTL |
| `datetime` props return `PaymoDateTime` | Type | `=== 'string'` comparisons fail | `__toString()`, `==`, string contexts work |
| `flatten()` parameter type widened | Signature | None — widening is backward compatible | Old `flatten([])` calls unchanged |
| `registerCacheMethods()` new 3rd param | Signature | None — optional parameter at end | Existing 2-param calls unchanged |
| `connect()` new `$logger` param | Signature | None — optional parameter at end | Existing calls unchanged |
| `Error::throw()` accepts enum | Signature | None — also accepts string | Existing string calls unchanged |
| Parent filter enforcement | Behavior | `File::list()->fetch()` without `project_id` now throws in devMode | Production mode: logs warning only |
| `in(me)` filter support | Additive | None | New feature, no existing behavior changed |
| `fetchAll()` | Additive | None | New method, no existing behavior changed |
| `toArray()`/`toJson()` | Additive | None | New methods, no existing behavior changed |
