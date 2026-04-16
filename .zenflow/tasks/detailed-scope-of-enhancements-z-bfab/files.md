# File & Change Mapping: paymo-api-php v1.0.0-alpha

---

## Summary

| Action | Count |
|--------|-------|
| CREATE | 6 new source files + ~18 new test files = **24 files** |
| MODIFY | 17 existing source files + 5 documentation files + 2 config files = **24 files** |
| DELETE | 0 files |
| **Total** | **48 files** |

Additionally, Phase 4 will modify up to **38 resource class files** for undocumented property additions (FR-004) and produce **13 investigation artifact files** (FR-040 through FR-052). These are data-driven changes whose exact scope depends on live API investigation results.

---

## Files by Delivery Phase

---

### Phase 1: v0.7.0 — Bug Fixes & Backward-Compatible Additions

**PHP Requirement:** `>=7.4` (unchanged)
**FRs covered:** FR-001, FR-002, FR-003, FR-006, FR-007, FR-008, FR-009, FR-010, FR-011, FR-016, FR-019, FR-024, FR-027, FR-032, FR-033, FR-034, FR-035, FR-036, FR-037, FR-038

---

#### 1. `composer.json`

- **Action:** MODIFY
- **Purpose:** Fix autoload-dev namespace mapping and add Composer test scripts.
- **FRs:** FR-003, FR-027
- **Changes:**
  1. Replace `autoload-dev` PSR-4 mapping from `"Jcolombo\\PaymoApiPhp\\": "src"` to `"Jcolombo\\PaymoApiPhp\\Tests\\": "tests"`.
  2. Add `scripts` section with `test`, `test:dry-run`, and `test:verbose` entries.
- **Code suggestion:**
  ```json
  {
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
- **Dependencies:** None — can be done first.
- **Impact:** Test files already use the `Jcolombo\PaymoApiPhp\Tests\` namespace (verified in all 30+ test files). This change makes `autoload-dev` match reality. No test file modifications needed. IDE autocompletion will stop showing test classes under the main namespace.

---

#### 2. `src/Entity/EntityMap.php`

- **Action:** MODIFY
- **Purpose:** Fix the typo that disables overload validation entirely.
- **FR:** FR-001
- **Changes:** Line 260 — change string literal `"Jcolombo\PaymoApiPhp\Entity\AbstractResourcce"` (double 'c') to `AbstractResource::class` constant reference.
- **Code suggestion:**
  ```php
  // BEFORE (line 260):
  if (!is_subclass_of($resourceClass, "Jcolombo\PaymoApiPhp\Entity\AbstractResourcce")) {

  // AFTER:
  use Jcolombo\PaymoApiPhp\Entity\AbstractResource;
  // ...
  if (!is_subclass_of($resourceClass, AbstractResource::class)) {
  ```
- **Dependencies:** None.
- **Impact:** Re-enables overload validation. If any consumer previously registered an overload class that does NOT extend `AbstractResource`, that overload will now correctly throw an exception. This is the intended behavior — the bug was that validation was silently disabled.

---

#### 3. `src/Paymo.php`

- **Action:** MODIFY
- **Purpose:** Fix hardcoded devMode constant; add cacheHit tracking to execute().
- **FRs:** FR-002, FR-019
- **Changes:**
  1. **FR-002:** Remove `define('PAYMO_DEVELOPMENT_MODE', true);` at line 62. Replace all references to `PAYMO_DEVELOPMENT_MODE` (lines 62, 669, 676) with `(bool)(Configuration::get('devMode') ?? false)`.
  2. **FR-019:** In `execute()`, after cache hit path, set `$response->cacheHit = true` before returning.
- **Code suggestion (FR-002):**
  ```php
  // DELETE line 62:
  // define('PAYMO_DEVELOPMENT_MODE', true);

  // REPLACE usages at lines ~669 and ~676:
  // BEFORE: if (PAYMO_DEVELOPMENT_MODE) {
  // AFTER:  if ((bool)(Configuration::get('devMode') ?? false)) {
  ```
- **Code suggestion (FR-019):**
  ```php
  // In execute(), after the cache-hit branch returns the cached response:
  if ($cached !== null && $cached !== false) {
      $response->hydrateData($cacheKey, $cached);
      $response->cacheHit = true;  // NEW
      return $response;
  }
  ```
- **Dependencies:** Depends on `RequestResponse` having `$cacheHit` property (file #5).
- **Impact:** FR-002 changes default devMode behavior from `true` to `false` (read from config where `devMode` defaults to `false`). This fixes the production leak bug. Code that relied on devMode being always-true will now see the config-driven value. FR-019 is additive — new property, no existing behavior changed.

---

#### 4. `src/Utility/Log.php`

- **Action:** MODIFY
- **Purpose:** Update `PAYMO_DEVELOPMENT_MODE` reference to use Configuration.
- **FR:** FR-002
- **Changes:** Line 102 references `PAYMO_DEVELOPMENT_MODE`. Replace with `(bool)(Configuration::get('devMode') ?? false)`. Add `use Jcolombo\PaymoApiPhp\Configuration;` import.
- **Code suggestion:**
  ```php
  // BEFORE (line 102):
  $forced = (($log->inDevMode || PAYMO_DEVELOPMENT_MODE) && ($FORCE_PAYMOAPI_LOGGING ?? false));

  // AFTER:
  $devMode = (bool)(Configuration::get('devMode') ?? false);
  $forced = (($log->inDevMode || $devMode) && ($FORCE_PAYMOAPI_LOGGING ?? false));
  ```
- **Dependencies:** Depends on FR-002 removing the `define()` constant.
- **Impact:** Minimal — logging forced-mode check now reads from config instead of the always-true constant.

---

#### 5. `src/Utility/RequestResponse.php`

- **Action:** MODIFY
- **Purpose:** Add explicit cache-hit boolean property.
- **FR:** FR-019
- **Changes:** Add `public bool $cacheHit = false;` property alongside existing properties.
- **Code suggestion:**
  ```php
  class RequestResponse
  {
      // Existing properties...
      public ?string $fromCacheKey = null;
      public bool $success = false;
      // ...

      // NEW: Explicit cache-hit indicator (FR-019)
      public bool $cacheHit = false;
  }
  ```
- **Dependencies:** None.
- **Impact:** Additive — new property with default `false`. No existing behavior changed.

---

#### 6. `src/Entity/AbstractResource.php`

- **Action:** MODIFY
- **Purpose:** Add WRITEONLY constant, validateConstants() method, toArray(), and toJson().
- **FRs:** FR-009, FR-011, FR-033
- **Changes:**
  1. **FR-009:** Add `public const WRITEONLY = [];` constant alongside existing READONLY, CREATEONLY, UNSELECTABLE.
  2. **FR-011:** Add `validateConstants()` protected method with static class-level caching. Call it from constructor when devMode is enabled. Validates: READONLY vs REQUIRED_CREATE disjoint, WRITEONLY vs READONLY disjoint, all constant arrays reference valid PROP_TYPES keys, valid type prefixes in PROP_TYPES values.
  3. **FR-033:** Add `toArray(array $options = []): array` and `toJson(int $options = 0): string` public methods.
- **Code suggestion (FR-009):**
  ```php
  // Add alongside existing constants:
  public const WRITEONLY = [];
  ```
- **Code suggestion (FR-011):**
  ```php
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

      // 1. READONLY ∩ REQUIRED_CREATE = ∅
      foreach ($requiredCreate as $req) {
          if (preg_match('/[|&]/', $req)) continue; // skip compound
          if (in_array($req, $readonly, true)) {
              throw new \RuntimeException(
                  "{$class}: '{$req}' in both READONLY and REQUIRED_CREATE."
              );
          }
      }

      // 2. WRITEONLY ∩ READONLY = ∅
      $overlap = array_intersect($writeonly, $readonly);
      if (count($overlap) > 0) {
          throw new \RuntimeException(
              "{$class}: [" . implode(', ', $overlap) . "] in both WRITEONLY and READONLY."
          );
      }

      // 3-6. All constant arrays reference valid PROP_TYPES keys
      $checks = [
          'UNSELECTABLE' => static::UNSELECTABLE,
          'READONLY' => $readonly,
          'CREATEONLY' => static::CREATEONLY,
          'WRITEONLY' => $writeonly,
      ];
      foreach ($checks as $constName => $keys) {
          foreach ($keys as $key) {
              if (!in_array($key, $propTypes, true)) {
                  throw new \RuntimeException(
                      "{$class}: '{$key}' in {$constName} not in PROP_TYPES."
                  );
              }
          }
      }

      // 7. Valid type prefixes
      $validPrefixes = [
          'text', 'integer', 'decimal', 'double', 'boolean', 'date', 'datetime',
          'datetime[]', 'url', 'email', 'html', 'array', 'resource', 'collection',
          'enum', 'enumInt', 'intEnum',
      ];
      foreach (static::PROP_TYPES as $prop => $type) {
          $prefix = explode(':', $type, 2)[0];
          if (!in_array($prefix, $validPrefixes, true)) {
              throw new \RuntimeException(
                  "{$class}: Unknown type prefix '{$prefix}' for '{$prop}'."
              );
          }
      }

      self::$validatedClasses[$class] = true;
  }
  ```
- **Code suggestion (FR-033):**
  ```php
  public function toArray(array $options = []): array
  {
      return (array) $this->flatten($options);
  }

  public function toJson(int $options = 0): string
  {
      return json_encode($this->flatten(), $options);
  }
  ```
- **Dependencies:** None for FR-009/FR-033. FR-011 depends on FR-002 (devMode reading from config).
- **Impact:** FR-009 is a new empty constant — zero runtime effect. FR-011 only fires in devMode, cached per class, ~30 lines of cross-referencing. FR-033 adds new methods — no existing methods changed.

---

#### 7. `src/Entity/AbstractCollection.php`

- **Action:** MODIFY
- **Purpose:** Add flatten(string) pluck overload, toArray(), and toJson().
- **FRs:** FR-032, FR-033
- **Changes:**
  1. **FR-032:** Modify `flatten()` method signature to accept `string|array` parameter. When a string is passed, return a flat array of that property's values from all items (pluck behavior). When an array is passed (or no argument), existing behavior is preserved.
  2. **FR-033:** Add `toArray(array $options = []): array` and `toJson(int $options = 0): string` methods.
- **Code suggestion (FR-032):**
  ```php
  // Modify existing flatten() signature:
  // BEFORE: public function flatten($options = [])
  // AFTER:
  public function flatten($optionsOrProperty = []): array
  {
      if (is_string($optionsOrProperty)) {
          $result = [];
          foreach ($this->data as $resource) {
              $result[] = $resource->$optionsOrProperty;
          }
          return $result;
      }

      // Existing array-mode logic unchanged...
      $options = $optionsOrProperty;
      $items = [];
      foreach ($this->data as $k => $resource) {
          $key = isset($options['array']) && $options['array'] ? count($items) : (int) $k;
          $items[$key] = $resource->flatten($options);
      }
      return $items;
  }
  ```
- **Dependencies:** None.
- **Impact:** Parameter widening is backward compatible. `flatten([])` and `flatten()` calls continue to work identically. New `flatten('name')` form is additive.

---

#### 8. `src/Utility/Converter.php`

- **Action:** MODIFY
- **Purpose:** Support `in(me)` literal in WHERE condition compilation.
- **FR:** FR-016
- **Changes:** In `convertOperatorValue()`, inside the `'in'` / `'not in'` case, add a check for the literal string `'me'` or array `['me']` and output `prop in (me)` without quoting.
- **Code suggestion:**
  ```php
  case 'in':
  case 'not in':
      // NEW: Support 'me' literal for current user filtering (FR-016)
      if ($value === 'me' || $value === ['me']) {
          return "{$prop} {$operator} (me)";
      }
      // Existing array handling continues...
  ```
- **Dependencies:** None.
- **Impact:** Additive — new value handling. Existing filter compilation unchanged for all other values.

---

#### 9. `src/Configuration.php`

- **Action:** MODIFY
- **Purpose:** Add static reset() method for singleton destruction.
- **FR:** FR-024
- **Changes:** Add `public static function reset(): void` that sets `self::$instance = null`. This is a targeted addition — the full Configuration rewrite happens in Phase 2 (FR-023).
- **Code suggestion:**
  ```php
  /**
   * Destroy the singleton. Next get()/load() creates a fresh instance.
   * Primary use case: test isolation.
   */
  public static function reset(): void
  {
      self::$instance = null;
  }
  ```
- **Dependencies:** None.
- **Impact:** New static method. No existing methods changed. Internal-only use case (tests and SDK internals).

---

#### 10. `src/Entity/Resource/Expense.php`

- **Action:** MODIFY
- **Purpose:** Add undocumented `download_token` property.
- **FR:** FR-008
- **Changes:** Add `'download_token' => 'text'` to PROP_TYPES constant. Add `'download_token'` to READONLY constant.
- **Code suggestion:**
  ```php
  const PROP_TYPES = [
      // ... existing properties ...
      'download_token' => 'text',  // Undocumented - used for file download authentication
  ];

  const READONLY = [
      // ... existing readonly properties ...
      'download_token',
  ];
  ```
- **Dependencies:** None.
- **Impact:** Additive — new property available for consumers to read from API responses.

---

#### 11. `src/Entity/Resource/Report.php`

- **Action:** MODIFY
- **Purpose:** Add undocumented `download_token` property.
- **FR:** FR-008
- **Changes:** Same as Expense — add `'download_token' => 'text'` to PROP_TYPES, add to READONLY.
- **Dependencies:** None.
- **Impact:** Same as Expense — additive property addition.

---

#### 12. `default.paymoapi.config.json`

- **Action:** MODIFY
- **Purpose:** Ensure `devMode` key exists in default configuration.
- **FR:** FR-002
- **Changes:** Verify/add `"devMode": false` at root level of the config JSON. This may already be present — if so, no change needed. Also add `"includes": { "maxDepth": 2 }` for Phase 3 forward-compatibility.
- **Dependencies:** None.
- **Impact:** Ensures `Configuration::get('devMode')` returns `false` by default rather than `null`.

---

#### 13. `OVERRIDES.md`

- **Action:** MODIFY
- **Purpose:** Add 5 new documentation entries for undocumented API behaviors.
- **FRs:** FR-034, FR-035, FR-036, FR-037, FR-038
- **Changes:** Append 5 new override entries:
  1. **OVERRIDE-014 (FR-034):** 2,500-item include truncation behavior. When an included collection returns exactly 2,500 items, results may be truncated. SDK adds optional devMode warning.
  2. **OVERRIDE-015 (FR-035):** Task and TimeEntry `description` fields may contain HTML tags (`<p>`, `<br>`, etc.) when content is entered via the Paymo web interface.
  3. **OVERRIDE-016 (FR-036):** `retainer_id` appears on Project objects but the Retainer API is not public/documented.
  4. **OVERRIDE-017 (FR-037):** Webhook delete payloads contain only `{"id": <ID>}`. Webhook update payloads do not include a changed-fields diff.
  5. **OVERRIDE-018 (FR-038):** Company resource contains sensitive properties (Stripe keys, Authorize.net credentials, SMTP configuration) that are returned in API responses.
- **Dependencies:** None — documentation only.
- **Impact:** Documentation only. No code behavior changes.

---

#### 14-15. Verification-Only Items (FR-006, FR-007)

- **FR-006:** Verify Session resource's `id` property type is `'text'` in `src/Entity/Resource/Session.php` PROP_TYPES. Produces a verification note — no code change if already correct.
- **FR-007:** Verify Invoice and Estimate resources have `language` in READONLY. Check `src/Entity/Resource/Invoice.php` and `src/Entity/Resource/Estimate.php`. Produces a verification note — no code change if already correct.

These do not produce file changes but must be verified during Phase 1 implementation.

---

#### Phase 1 Test Files

#### 16. `tests/ResourceTests/EntityMapTest.php`

- **Action:** CREATE
- **Purpose:** Test that EntityMap overload validation works after typo fix.
- **FR:** FR-001
- **Changes:** Test class extending `ResourceTest`. Tests: (1) valid overload class accepted, (2) invalid overload class rejected with exception.
- **Code suggestion:**
  ```php
  namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

  use Jcolombo\PaymoApiPhp\Tests\ResourceTest;

  class EntityMapTest extends ResourceTest
  {
      protected string $resourceClass = ''; // N/A for utility test

      public function testOverloadValidation(): array
      {
          // Test that overload with invalid class throws RuntimeException
          // Test that overload with valid subclass succeeds
      }
  }
  ```
- **Dependencies:** Depends on EntityMap.php fix (file #2).

---

#### 17. `tests/UtilityTests/DevModeTest.php`

- **Action:** CREATE
- **Purpose:** Test that devMode reads from config instead of hardcoded constant.
- **FR:** FR-002
- **Changes:** Tests: (1) devMode defaults to false, (2) devMode can be set to true via config, (3) error details are suppressed when devMode is false.
- **Dependencies:** Depends on Paymo.php fix (file #3) and Configuration reset (file #9).

---

#### 18. `tests/UtilityTests/ConfigurationTest.php`

- **Action:** CREATE
- **Purpose:** Test Configuration::reset() singleton destruction.
- **FR:** FR-024
- **Changes:** Tests: (1) reset() destroys singleton, (2) subsequent get() creates fresh instance, (3) runtime set() values are lost after reset.
- **Dependencies:** Depends on Configuration.php change (file #9).

---

#### 19. `tests/UtilityTests/RequestResponseTest.php`

- **Action:** CREATE
- **Purpose:** Test cacheHit property on RequestResponse.
- **FR:** FR-019
- **Changes:** Tests: (1) default cacheHit is false, (2) cacheHit is true after cache-served response.
- **Dependencies:** Depends on RequestResponse.php change (file #5).

---

#### 20. `tests/UtilityTests/ConverterTest.php`

- **Action:** CREATE
- **Purpose:** Test in(me) filter generation and existing converter behavior.
- **FR:** FR-016
- **Changes:** Tests: (1) `in(me)` generates `prop in (me)`, (2) `not in(me)` generates `prop not in (me)`, (3) existing `in` with arrays still works, (4) existing `in` with scalar still works.
- **Dependencies:** Depends on Converter.php change (file #8).

---

#### 21. `tests/CollectionTests/FlattenPluckTest.php`

- **Action:** CREATE
- **Purpose:** Test collection flatten(string) pluck behavior.
- **FR:** FR-032
- **Changes:** Tests: (1) `flatten('name')` returns array of name values, (2) `flatten()` with no args returns existing behavior, (3) `flatten([])` with array arg returns existing behavior.
- **Dependencies:** Depends on AbstractCollection.php change (file #7).

---

#### 22. `tests/ResourceTests/ConvenienceMethodsTest.php`

- **Action:** CREATE
- **Purpose:** Test toArray() and toJson() on both resources and collections.
- **FR:** FR-033
- **Changes:** Tests: (1) Resource toArray() returns array, (2) Resource toJson() returns valid JSON string, (3) Collection toArray() returns sequential array, (4) Collection toJson() returns valid JSON string.
- **Dependencies:** Depends on AbstractResource.php (file #6) and AbstractCollection.php (file #7) changes.

---

#### 23. `tests/ResourceTests/ConstantValidationTest.php`

- **Action:** CREATE
- **Purpose:** Test validateConstants() catches cross-referencing conflicts in devMode.
- **FR:** FR-011
- **Changes:** Tests: (1) READONLY property in REQUIRED_CREATE throws, (2) WRITEONLY in READONLY throws, (3) property in UNSELECTABLE but not PROP_TYPES throws, (4) unknown type prefix throws, (5) valid resource passes silently, (6) validation caches per class (runs once).
- **Dependencies:** Depends on AbstractResource.php change (file #6).

---

### Phase 2: v0.8.0 — PHP 8.1 + Dependency Modernization

**PHP Requirement:** `>=8.1`
**FRs covered:** FR-020, FR-021, FR-022, FR-023

---

#### 24. `composer.json`

- **Action:** MODIFY (second time — builds on Phase 1 changes)
- **Purpose:** Bump PHP minimum, replace hassankhan/config with psr/log.
- **FRs:** FR-022, FR-023, FR-021
- **Changes:**
  1. Change `"php": ">=7.4"` to `"php": ">=8.1"`.
  2. Remove `"hassankhan/config": "^3.2"` from `require`.
  3. Add `"psr/log": "^2.0 || ^3.0"` to `require`.
- **Code suggestion (target state):**
  ```json
  {
    "require": {
      "php": ">=8.1",
      "ext-json": "*",
      "guzzlehttp/guzzle": "^7.8",
      "adbario/php-dot-notation": "^3.3",
      "psr/log": "^2.0 || ^3.0"
    }
  }
  ```
- **Dependencies:** Phase 1 composer.json changes must be in place.
- **Impact:** Environment-breaking change. PHP 7.4 consumers cannot upgrade past v0.7.x. Removing hassankhan/config may affect consumers who import it directly (unlikely — SDK never exposes this dependency).

---

#### 25. `src/Configuration.php`

- **Action:** MODIFY (rewrite — builds on Phase 1's reset() addition)
- **Purpose:** Replace hassankhan/config with direct Adbar\Dot usage.
- **FR:** FR-023
- **Changes:** Full internal rewrite. The class no longer extends `Noodlehaus\Config`. Instead:
  1. Private constructor loads JSON files via `json_decode(file_get_contents(...))`.
  2. Merged array wrapped in `Adbar\Dot` instance.
  3. Public API preserved: `get()`, `set()`, `has()`, `all()`, `load()`, `overload()`, `reset()`.
  4. Remove `use Noodlehaus\Config` import. Add `use Adbar\Dot`.
- **Code suggestion:** See spec.md lines 604-701 for the complete rewritten class.
- **Dependencies:** Depends on hassankhan/config removal from composer.json (file #24).
- **Impact:** Internal implementation change. All public method signatures and return values are preserved. `Configuration::get('connection.url')` continues to return the same value. The `Noodlehaus\Config` parent class is removed — any code doing `$config instanceof Noodlehaus\Config` will break (this is internal SDK code only, not consumer-facing).

---

#### 26. `src/Utility/ErrorSeverity.php`

- **Action:** CREATE
- **Purpose:** PHP 8.1 backed string enum for error severity classification.
- **FR:** FR-020
- **Changes:** New file containing the `ErrorSeverity` enum with cases `NOTICE`, `WARN`, `FATAL`. Includes `fromHttpStatus(int)` static method for HTTP status code classification.
- **Code suggestion:** See spec.md lines 116-148 for the complete enum definition.
- **Dependencies:** Requires PHP 8.1 (file #24).
- **Impact:** New file — no existing code affected until Error.php integration.

---

#### 27. `src/Utility/PsrLogAdapter.php`

- **Action:** CREATE
- **Purpose:** Bridge between SDK severity-based logging and PSR-3 LoggerInterface.
- **FR:** FR-021
- **Changes:** New class with `log()`, `logRequest()`, `logResponse()`, `getLogger()` methods. Maps SDK severity levels to PSR-3 log levels: `fatal` → `LogLevel::ERROR`, `warn` → `LogLevel::WARNING`, `notice` → `LogLevel::NOTICE`.
- **Code suggestion:** See spec.md lines 211-278 for the complete class.
- **Dependencies:** Requires `psr/log` in composer.json (file #24). Requires `ErrorSeverity` enum (file #26).
- **Impact:** New file — no existing code affected until Paymo.php integration.

---

#### 28. `src/Utility/Error.php`

- **Action:** MODIFY
- **Purpose:** Integrate ErrorSeverity enum; add custom error handler support.
- **FR:** FR-020
- **Changes:**
  1. Modify `throw()` to accept `ErrorSeverity|string` for the severity parameter. Resolve enum to string value internally: `$severityValue = ($severity instanceof ErrorSeverity) ? $severity->value : $severity;`
  2. Add `private ?callable $customHandler = null;` property.
  3. Add `public function setCustomHandler(callable $handler): void` method.
  4. In `handleError()`, before existing handler dispatch, call custom handler if set: `($this->customHandler)(ErrorSeverity::from($severity), $message, $error, $code);`
- **Code suggestion:**
  ```php
  use Jcolombo\PaymoApiPhp\Utility\ErrorSeverity;

  public static function throw(
      ErrorSeverity|string $severity,
      mixed $error = null,
      int|string|null $code = null,
      ?string $message = null
  ): void {
      $severityValue = ($severity instanceof ErrorSeverity) ? $severity->value : $severity;
      self::i()->handleError($severityValue, $error, $code, $message);
  }
  ```
- **Dependencies:** Depends on ErrorSeverity enum (file #26).
- **Impact:** Backward compatible. Existing `Error::throw('fatal', ...)` string calls continue to work. The union type `ErrorSeverity|string` accepts both.

---

#### 29. `src/Paymo.php`

- **Action:** MODIFY (second time — builds on Phase 1 changes)
- **Purpose:** Add PSR-3 logger injection; add setErrorHandler().
- **FRs:** FR-021, FR-020
- **Changes:**
  1. **FR-021:** Add `private ?PsrLogAdapter $psrLogger = null;` property. Add `setLogger(LoggerInterface $logger): void` and `getLogger(): ?PsrLogAdapter` methods. Modify `connect()` to accept optional `?LoggerInterface $logger = null` parameter. In `execute()`, after response, log request/response via PSR-3 adapter if set.
  2. **FR-020:** Add `setErrorHandler(callable $handler): void` method that delegates to `Error::i()->setCustomHandler($handler)`.
- **Code suggestion (FR-021 - connect signature):**
  ```php
  public static function connect(
      string|array|null $apiKeyUser = null,
      ?bool $useLogging = null,
      ?string $connectionName = null,
      ?string $connectionUrl = null,
      ?\Psr\Log\LoggerInterface $logger = null  // NEW
  ): Paymo {
      // ... existing logic ...
      if ($logger !== null) {
          $connection->setLogger($logger);
      }
      return $connection;
  }
  ```
- **Dependencies:** Depends on PsrLogAdapter (file #27) and Error.php changes (file #28).
- **Impact:** New optional parameter at end of `connect()` — backward compatible. New methods are additive. PSR-3 logging in `execute()` only fires if a logger was injected.

---

#### Phase 2 Test Files

#### 30. `tests/UtilityTests/ErrorSeverityTest.php`

- **Action:** CREATE
- **Purpose:** Test ErrorSeverity enum values and HTTP status mapping.
- **FR:** FR-020
- **Changes:** Tests: (1) enum values match existing strings (`ErrorSeverity::FATAL->value === 'fatal'`), (2) `fromHttpStatus(429)` → WARN, (3) `fromHttpStatus(401)` → FATAL, (4) `fromHttpStatus(500)` → FATAL, (5) `fromHttpStatus(400)` → NOTICE.
- **Dependencies:** Depends on ErrorSeverity.php (file #26).

---

#### 31. `tests/UtilityTests/PsrLogAdapterTest.php`

- **Action:** CREATE
- **Purpose:** Test PSR-3 adapter maps SDK severity levels to PSR-3 log levels.
- **FR:** FR-021
- **Changes:** Tests: (1) `log('fatal', ...)` maps to PSR-3 ERROR, (2) `log('warn', ...)` maps to WARNING, (3) `log(ErrorSeverity::NOTICE, ...)` maps to NOTICE, (4) `logRequest()` logs at DEBUG, (5) `logResponse()` logs at DEBUG with cache indicator.
- **Dependencies:** Depends on PsrLogAdapter.php (file #27).

---

#### 32. `tests/UtilityTests/ConfigurationRewriteTest.php`

- **Action:** CREATE
- **Purpose:** Test that rewritten Configuration produces identical behavior.
- **FR:** FR-023
- **Changes:** Tests: (1) `get('connection.url')` returns expected value, (2) `has('classMap')` returns true, (3) `set()` persists for subsequent `get()`, (4) `reset()` clears `set()` changes, (5) `overload()` merges user config file, (6) `all()` returns complete config array.
- **Dependencies:** Depends on Configuration.php rewrite (file #25).

---

### Phase 3: v0.9.0 — Core Feature Development

**PHP Requirement:** `>=8.1`
**FRs covered:** FR-005, FR-012, FR-013, FR-014, FR-015, FR-017, FR-018

---

#### 33. `src/Utility/PaymoDateTime.php`

- **Action:** CREATE
- **Purpose:** DateTimeImmutable subclass preserving original API string for backward compatibility.
- **FR:** FR-005
- **Changes:** New class extending `\DateTimeImmutable` implementing `\JsonSerializable`. Has `readonly string $rawApiValue` property, `__toString()` returning raw value, `jsonSerialize()` returning raw value, `getRawApiValue()` accessor.
- **Code suggestion:** See spec.md lines 152-205 for the complete class.
- **Dependencies:** Requires PHP 8.1 (readonly property).
- **Impact:** New file — used by Converter during hydration.

---

#### 34. `src/Utility/Converter.php`

- **Action:** MODIFY (second time — builds on Phase 1's in(me) change)
- **Purpose:** Add three-direction type coercion methods.
- **FR:** FR-005
- **Changes:**
  1. Add `public static function convertToPhpValue(mixed $value, string $type): mixed` — hydration direction (API → PHP). Handles: datetime/date → PaymoDateTime, boolean coercion, integer/decimal casting.
  2. Add `public static function convertForRequest(mixed $value, string $type): mixed` — serialization direction (PHP → API). Handles: DateTimeInterface → string, boolean → bool, numeric casting.
  3. Add `private static function hydrateDateTime(mixed $value): PaymoDateTime` helper.
  4. Add `private static function hydrateBoolean(mixed $value): bool` helper.
  5. Add `use Jcolombo\PaymoApiPhp\Utility\PaymoDateTime;` import.
- **Code suggestion:** See spec.md lines 318-421 for the complete method implementations.
- **Dependencies:** Depends on PaymoDateTime class (file #33).
- **Impact:** New methods — no existing methods changed. The conversion is invoked from AbstractResource (file #35).

---

#### 35. `src/Entity/AbstractResource.php`

- **Action:** MODIFY (second time — builds on Phase 1 additions)
- **Purpose:** Integrate type coercion into property set/hydration and request body assembly.
- **FR:** FR-005
- **Changes:**
  1. Modify `__set()` to call `Converter::convertToPhpValue($value, $type)` when setting properties (both user-set and hydration). This ensures datetime strings become PaymoDateTime instances, booleans are coerced, etc.
  2. Modify `create()` body assembly to call `Converter::convertForRequest($v, $type)` for each property value being sent to the API.
  3. Modify `update()` body assembly similarly.
  4. Add `use Jcolombo\PaymoApiPhp\Utility\Converter;` import if not already present.
- **Code suggestion (key change in __set):**
  ```php
  public function __set(string $name, mixed $value): void
  {
      if (AbstractEntity::isProp(static::API_ENTITY, $name)) {
          $canSet = ($this->hydrationMode || ...existing logic...);
          if ($canSet) {
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
  ```
- **Dependencies:** Depends on Converter.php coercion methods (file #34).
- **Impact:** This is the highest-impact change in the entire upgrade. All 38 resource types are affected because they all inherit from AbstractResource. Datetime properties now return PaymoDateTime instead of raw strings. Backward compatibility is maintained via `__toString()` on PaymoDateTime — string contexts (`echo`, concatenation, `==`) continue to work. `===` comparisons with strings will break (documented behavior change per A-011).

---

#### 36. `src/Entity/AbstractCollection.php`

- **Action:** MODIFY (second time — builds on Phase 1's flatten/toArray/toJson additions)
- **Purpose:** Add fetchAll() auto-pagination and FETCHALL_CAP constant.
- **FR:** FR-012
- **Changes:**
  1. Add `public const FETCHALL_CAP = 5000;` constant.
  2. Add `public function fetchAll(?array $fields = null, ?array $conditions = null, array $options = []): static` method. Iterates pages by calling `fetch()` with incrementing page numbers, merging results into `$this->data`, stopping when page returns fewer than `$pageSize` results or accumulated count reaches `FETCHALL_CAP`.
- **Code suggestion:** See spec.md lines 916-959 for the complete fetchAll() implementation.
- **Dependencies:** None — uses existing `fetch()` and `limit()` internally.
- **Impact:** New constant and new method — no existing behavior changed. `FETCHALL_CAP` can be overridden in collection subclasses.

---

#### 37. `src/Cache/Cache.php`

- **Action:** MODIFY
- **Purpose:** Add cache invalidation and clear callback support.
- **FRs:** FR-017, FR-018
- **Changes:**
  1. **FR-018:** Add `public $clear_callback = null;` property. Modify `registerCacheMethods()` to accept optional third `?callable $clear_callback = null` parameter. Existing 2-parameter calls continue to work.
  2. **FR-017:** Add `public static function invalidateByResource(string $resourceBase): void` method. Uses `$clear_callback` for custom backends or `glob()` for file-based cache. Add `public static function clearAll(): void` method.
- **Code suggestion:** See spec.md lines 504-598 for the complete implementation.
- **Dependencies:** FR-017 depends on FR-018 (uses clear callback). Both depend on cache key format change in RequestAbstraction (file #38).
- **Impact:** FR-018 is backward compatible — optional third parameter. FR-017 adds new methods called from Paymo::execute() (file #40). Existing cache files with old key format become orphaned and expire via TTL.

---

#### 38. `src/Utility/RequestAbstraction.php`

- **Action:** MODIFY
- **Purpose:** Add partialInclude property; modify cache key format for invalidation targeting.
- **FRs:** FR-014, FR-017
- **Changes:**
  1. **FR-014:** Add `public ?string $partialInclude = null;` property.
  2. **FR-017:** Modify `makeCacheKey()` to embed resource base in key name: from `paymoapi-{md5}` to `paymoapi-{resourceBase}-{md5}`. Extract `$resourceBase` from `$this->resourceUrl`.
  3. Include `$partialInclude` in the cache key string for uniqueness.
- **Code suggestion:** See spec.md lines 451-498 for the complete implementation.
- **Dependencies:** None.
- **Impact:** Cache key format change means existing cached data becomes orphaned (expires via TTL — no migration needed). The `$partialInclude` property is new and additive.

---

#### 39. `src/Entity/AbstractEntity.php`

- **Action:** MODIFY
- **Purpose:** Add include parsing for partial includes and nested dot notation.
- **FRs:** FR-014, FR-015
- **Changes:** In `cleanupForRequest()` (the method that separates includes from field selections and compiles them for the request), add logic to:
  1. Detect parenthesized includes (e.g., `tasks(id,name,due_date)`) and route them to `$partialInclude` instead of `$include`.
  2. Detect dot-notation includes (e.g., `tasks.entries`) and validate depth against configurable max (default 2).
  3. In devMode, validate partial include sub-fields against the included resource's PROP_TYPES.
  4. In devMode, validate nested include levels against parent resource's INCLUDE_TYPES.
- **Code suggestion:** See spec.md lines 1200-1253 for the include parsing logic.
- **Dependencies:** Depends on RequestAbstraction `$partialInclude` property (file #38).
- **Impact:** Additive syntax support. Existing plain include strings (e.g., `['tasks', 'client']`) are handled by the existing `else` branch — zero behavior change.

---

#### 40. `src/Paymo.php`

- **Action:** MODIFY (third time — builds on Phase 1 and Phase 2 changes)
- **Purpose:** Add cache invalidation after mutations; add partial_include query param.
- **FRs:** FR-017, FR-014
- **Changes:**
  1. **FR-017:** In `execute()`, after successful POST/PUT/DELETE response, extract resource base from request URL and call `Cache::invalidateByResource($resourceBase)`.
  2. **FR-014:** In the method that builds query parameters from `RequestAbstraction` (likely `buildRequestProps()` or similar), add `partial_include` query parameter when `$request->partialInclude` is not null.
- **Code suggestion (FR-017):**
  ```php
  // In execute(), after response processing:
  if ($this->useCache && $response->success
      && in_array($request->method, ['POST', 'PUT', 'DELETE'], true))
  {
      $resourceBase = explode('/', $request->resourceUrl ?? '')[0] ?? '';
      if ($resourceBase !== '') {
          Cache::invalidateByResource($resourceBase);
      }
  }
  ```
- **Code suggestion (FR-014):**
  ```php
  // In request properties builder:
  if ($request->partialInclude !== null) {
      $props['query']['partial_include'] = $request->partialInclude;
  }
  ```
- **Dependencies:** Depends on Cache.php invalidation (file #37) and RequestAbstraction changes (file #38).
- **Impact:** Cache invalidation only fires after successful mutations when caching is enabled — no effect on consumers with caching disabled. Partial include query param is additive.

---

#### 41. `src/Entity/Collection/FileCollection.php`

- **Action:** MODIFY
- **Purpose:** Update validateFetch() to also check fluent whereConditions.
- **FR:** FR-013
- **Changes:** Modify `validateFetch()` to merge `$this->whereConditions` with the `$where` parameter before scanning for required parent filters. Currently only checks the `$where` parameter, missing conditions set via the fluent `->where()` API.
- **Code suggestion:**
  ```php
  protected function validateFetch($fields = [], $where = []) : bool
  {
      $allConditions = array_merge($this->whereConditions, $where);  // CHANGED
      $needOne = ['task_id', 'project_id', 'discussion_id', 'comment_id'];
      $foundOne = false;
      foreach ($allConditions as $w) {  // CHANGED: was $where
          if (in_array($w->prop, $needOne, true)) {
              $foundOne = true;
              break;
          }
      }
      // ... rest unchanged ...
  }
  ```
- **Dependencies:** None.
- **Impact:** The behavior change is: fluent `->where(File::where('project_id', $id))` calls now satisfy the parent filter requirement. Previously, only conditions passed directly to `fetch()` were checked. This is a bug fix in the validation logic, not a behavior change in the API.

---

#### 42. `src/Entity/Collection/BookingCollection.php`

- **Action:** MODIFY
- **Purpose:** Same as FileCollection — update validateFetch() to check fluent whereConditions.
- **FR:** FR-013
- **Changes:** Same pattern as FileCollection: merge `$this->whereConditions` with `$where` before scanning.
- **Dependencies:** None.
- **Impact:** Same as FileCollection.

---

#### 43. `src/Entity/Collection/InvoiceItemCollection.php`

- **Action:** MODIFY
- **Purpose:** Same — update validateFetch() to check fluent whereConditions.
- **FR:** FR-013
- **Changes:** Same pattern: merge `$this->whereConditions` with `$where` before scanning.
- **Dependencies:** None.
- **Impact:** Same as FileCollection.

---

#### 44. `src/Entity/Collection/EstimateItemCollection.php`

- **Action:** MODIFY
- **Purpose:** Same — update validateFetch() to check fluent whereConditions.
- **FR:** FR-013
- **Changes:** Same pattern: merge `$this->whereConditions` with `$where` before scanning.
- **Dependencies:** None.
- **Impact:** Same as FileCollection.

---

#### Phase 3 Test Files

#### 45. `tests/UtilityTests/PaymoDateTimeTest.php`

- **Action:** CREATE
- **Purpose:** Test PaymoDateTime backward compatibility.
- **FR:** FR-005
- **Changes:** Tests: (1) `instanceof \DateTimeImmutable` → true, (2) `__toString()` returns original API string, (3) `jsonSerialize()` returns original API string, (4) `getRawApiValue()` returns original string, (5) date arithmetic (diff, modify) works, (6) `(string)$dt == $originalString` → true.
- **Dependencies:** Depends on PaymoDateTime.php (file #33).

---

#### 46. `tests/UtilityTests/ConverterCoercionTest.php`

- **Action:** CREATE
- **Purpose:** Test convertToPhpValue() and convertForRequest() for all PROP_TYPES.
- **FR:** FR-005
- **Changes:** Tests per type:
  - `datetime` string → PaymoDateTime → back to string
  - `date` string → PaymoDateTime → back to `Y-m-d` string
  - `boolean` various inputs (true, false, 1, 0, "true", "false") → bool
  - `integer` string → int
  - `decimal`/`double` string → float
  - `resource:X` → int (ID)
  - `collection:X` → int[] (array of IDs)
  - `text`, `html`, `email`, `url` → unchanged
  - `null` → null (all types)
- **Dependencies:** Depends on Converter.php (file #34) and PaymoDateTime (file #33).

---

#### 47. `tests/ResourceTests/TypeCoercionTest.php`

- **Action:** CREATE
- **Purpose:** Test end-to-end type coercion through resource lifecycle.
- **FR:** FR-005
- **Changes:** Tests: (1) After fetch, datetime properties are PaymoDateTime, (2) `json_encode($resource->flatten())` produces string for datetimes, (3) `set('due_date', new \DateTimeImmutable('2024-06-15'))` → convertForRequest produces `'2024-06-15'`, (4) boolean properties are native bool after hydration, (5) create() serializes typed values correctly.
- **Dependencies:** Depends on AbstractResource.php coercion (file #35).

---

#### 48. `tests/CollectionTests/FetchAllTest.php`

- **Action:** CREATE
- **Purpose:** Test fetchAll() auto-pagination and safety cap.
- **FR:** FR-012
- **Changes:** Tests: (1) fetchAll() returns all records, (2) stops when page returns fewer than pageSize, (3) respects FETCHALL_CAP, (4) pagination state is cleared after fetchAll(), (5) WHERE conditions are applied across all pages.
- **Dependencies:** Depends on AbstractCollection.php fetchAll() (file #36).

---

#### 49. `tests/CacheTests/InvalidationTest.php`

- **Action:** CREATE
- **Purpose:** Test cache invalidation after mutations.
- **FR:** FR-017
- **Changes:** Tests: (1) cache entries cleared after create(), (2) cache entries cleared after update(), (3) cache entries cleared after delete(), (4) only affected resource type is invalidated (not other types), (5) no-op when caching is disabled.
- **Dependencies:** Depends on Cache.php (file #37) and Paymo.php (file #40).

---

#### 50. `tests/CacheTests/CacheCallbackTest.php`

- **Action:** CREATE
- **Purpose:** Test registerCacheMethods() with 2 and 3 callbacks.
- **FR:** FR-018
- **Changes:** Tests: (1) 2-callback registration works (backward compat), (2) 3-callback registration works, (3) clear callback invoked during invalidation, (4) clear callback receives correct resource base string.
- **Dependencies:** Depends on Cache.php (file #37).

---

#### 51. `tests/CollectionTests/ParentFilterTest.php`

- **Action:** CREATE
- **Purpose:** Test parent filter enforcement on specialized collections.
- **FR:** FR-013
- **Changes:** Tests for File, Booking, InvoiceItem, EstimateItem collections: (1) fetch without required filter throws in devMode, (2) fluent `->where()` conditions satisfy requirement, (3) direct `fetch([], [$condition])` satisfies requirement.
- **Dependencies:** Depends on collection modifications (files #41-44).

---

#### 52. `tests/RequestTests/PartialIncludeTest.php`

- **Action:** CREATE
- **Purpose:** Test partial include syntax parsing and query parameter generation.
- **FR:** FR-014
- **Changes:** Tests: (1) `tasks(id,name)` generates `partial_include=tasks(id,name)`, (2) regular includes unaffected, (3) mixed partial and regular includes both present, (4) devMode validates sub-fields against PROP_TYPES.
- **Dependencies:** Depends on AbstractEntity.php (file #39) and RequestAbstraction.php (file #38).

---

#### 53. `tests/RequestTests/NestedIncludeTest.php`

- **Action:** CREATE
- **Purpose:** Test nested include dot notation validation.
- **FR:** FR-015
- **Changes:** Tests: (1) `tasks.entries` passes to include param, (2) 3-level nesting throws with default maxDepth=2, (3) configurable maxDepth override, (4) devMode validates each level against parent INCLUDE_TYPES.
- **Dependencies:** Depends on AbstractEntity.php (file #39).

---

### Phase 4: v1.0.0-alpha — Investigations, Properties & Final Polish

**PHP Requirement:** `>=8.1`
**FRs covered:** FR-004, FR-039, FR-040 through FR-052

---

#### 54-91. All 38 Resource Classes (Potential MODIFY)

- **Action:** MODIFY (count depends on FR-040/FR-046 investigation results)
- **Purpose:** Add undocumented properties discovered through live API testing.
- **FR:** FR-004 (depends on FR-040, FR-046)
- **Files:** All 38 files in `src/Entity/Resource/`:
  ```
  Booking.php, Client.php, ClientContact.php, Comment.php, CommentThread.php,
  Company.php, Discussion.php, Estimate.php, EstimateItem.php,
  EstimateTemplate.php, EstimateTemplateGallery.php, Expense.php, File.php,
  Invoice.php, InvoiceItem.php, InvoicePayment.php, InvoiceTemplate.php,
  InvoiceTemplateGallery.php, Milestone.php, Project.php, ProjectStatus.php,
  ProjectTemplate.php, ProjectTemplateTask.php, ProjectTemplateTasklist.php,
  RecurringProfile.php, RecurringProfileItem.php, Report.php, Session.php,
  Subtask.php, Task.php, TaskAssignment.php, TaskRecurringProfile.php,
  Tasklist.php, TimeEntry.php, User.php, Webhook.php, Workflow.php,
  WorkflowStatus.php
  ```
- **Changes per file:** For each property discovered by FR-040 that is absent from SDK PROP_TYPES:
  1. Add to PROP_TYPES with correct type and `// Undocumented` comment per OVERRIDE-011 policy.
  2. Add to READONLY unless write behavior is confirmed.
  3. Check OVERRIDES.md before adding — do not conflict with documented overrides.
- **Dependencies:** Depends on FR-040 and FR-046 investigation artifact outputs. Cannot proceed until investigation is complete.
- **Impact:** Additive — new properties in existing resources. No existing properties changed.

---

#### 92. `CLAUDE.md`

- **Action:** MODIFY
- **Purpose:** Update SDK usage guide for AI assistants with all v1.0.0-alpha changes.
- **FR:** FR-039
- **Changes:** Update sections for:
  - New methods: `fetchAll()`, `toArray()`, `toJson()`, `flatten('property')`, `Configuration::reset()`, `setLogger()`, `setErrorHandler()`
  - New constants: `WRITEONLY`, `FETCHALL_CAP`
  - New classes: `ErrorSeverity`, `PaymoDateTime`, `PsrLogAdapter`
  - Changed PHP minimum: `>=8.1`
  - Changed dependencies: removed hassankhan/config, added psr/log
  - Type coercion behavior: datetime → PaymoDateTime
  - Cache invalidation behavior
  - Partial include and nested include syntax
  - `in(me)` filter support
  - `cacheHit` response property
- **Dependencies:** All implementation phases complete.
- **Impact:** Documentation only.

---

#### 93. `PACKAGE-DEV.md`

- **Action:** MODIFY
- **Purpose:** Update internal development guide for SDK maintainers.
- **FR:** FR-039
- **Changes:** Update: class inventory, architecture notes, constant documentation, new patterns.
- **Dependencies:** All implementation phases complete.
- **Impact:** Documentation only.

---

#### 94. `README.md`

- **Action:** MODIFY
- **Purpose:** Update user-facing documentation with v1.0.0-alpha features.
- **FR:** FR-039
- **Changes:** Update: installation instructions (PHP 8.1), changelog summary, new feature examples, updated code samples.
- **Dependencies:** All implementation phases complete.
- **Impact:** Documentation only.

---

#### 95. `CHANGELOG.md`

- **Action:** MODIFY
- **Purpose:** Add version history entries for v0.7.0, v0.8.0, v0.9.0, v1.0.0-alpha.
- **FR:** FR-039
- **Changes:** Add structured changelog entries for each version with categorized changes (Added, Changed, Fixed, Deprecated, Removed).
- **Dependencies:** All implementation phases complete.
- **Impact:** Documentation only.

---

#### 96. `OVERRIDES.md`

- **Action:** MODIFY (second time — builds on Phase 1 additions)
- **Purpose:** Add investigation-discovered overrides from FR-040 through FR-052.
- **FR:** FR-004, FR-039
- **Changes:** Add override entries for any new API deviations discovered during investigation.
- **Dependencies:** Depends on investigation artifacts.
- **Impact:** Documentation only.

---

#### Investigation Artifact Files (FR-040 through FR-052)

These are output files produced by the investigation tasks. They are saved to the task artifacts directory and inform FR-004 implementation. They are NOT part of the SDK package.

| File | FR | Content |
|------|-----|---------|
| `investigation-FR040-property-discovery.md` | FR-040 | Per-resource property diff (extra/missing/type mismatch) |
| `investigation-FR041-leave-management.md` | FR-041 | Leave endpoint CRUD behavior, property shapes, filter support |
| `investigation-FR042-filter-operators.md` | FR-042 | Per-resource operator support matrix |
| `investigation-FR043-sdk-only-includes.md` | FR-043 | Verified/unverified include list for ~25 SDK-only relationships |
| `investigation-FR044-has-support.md` | FR-044 | HAS support matrix per resource |
| `investigation-FR045-undocumented-filters.md` | FR-045 | Filterable/non-filterable classification per undocumented property |
| `investigation-FR046-api-inventory.md` | FR-046 | 38-resource change manifest (add/remove/retype per property) |
| `investigation-FR047-include-reconciliation.md` | FR-047 | Reconciliation of 82 vs 98 include count discrepancy |
| `investigation-FR048-nested-include-depth.md` | FR-048 | Confirmed maximum nesting depth |
| `investigation-FR049-currencies-endpoint.md` | FR-049 | Whether `GET /api/currencies` is a live endpoint |
| `investigation-FR050-response-key-anomalies.md` | FR-050 | Confirmed compound resource name anomalies |
| `investigation-FR051-statsreport-types.md` | FR-051 | Additional StatsReport report types (if any) |
| `investigation-FR052-rate-limits.md` | FR-052 | Actual API rate limit values from response headers |

---

## Implementation Order

This is the complete build sequence. Files within a phase are ordered by dependency (what must be done first). Files at the same level can be implemented in parallel.

### Phase 1 (v0.7.0) — Implementation Order

```
1.  composer.json           (FR-003, FR-027)         — no dependencies
2.  EntityMap.php           (FR-001)                 — no dependencies
3.  RequestResponse.php     (FR-019)                 — no dependencies
4.  Configuration.php       (FR-024)                 — no dependencies
5.  Converter.php           (FR-016)                 — no dependencies
6.  Expense.php             (FR-008)                 — no dependencies
7.  Report.php              (FR-008)                 — no dependencies
8.  default.paymoapi.config.json (FR-002)            — no dependencies
    ── [parallel group: items 1-8 have zero interdependencies] ──

9.  Paymo.php               (FR-002, FR-019)         — depends on #3, #8
10. Log.php                 (FR-002)                 — depends on #9 (constant removal)
11. AbstractResource.php    (FR-009, FR-011, FR-033) — depends on #9 (devMode for FR-011)
12. AbstractCollection.php  (FR-032, FR-033)         — no dependencies

13. OVERRIDES.md            (FR-034–FR-038)          — no dependencies (documentation)
14. Verification: Session.php, Invoice.php, Estimate.php (FR-006, FR-007)

    ── [test files after implementation] ──

15. tests/ResourceTests/EntityMapTest.php
16. tests/UtilityTests/DevModeTest.php
17. tests/UtilityTests/ConfigurationTest.php
18. tests/UtilityTests/RequestResponseTest.php
19. tests/UtilityTests/ConverterTest.php
20. tests/CollectionTests/FlattenPluckTest.php
21. tests/ResourceTests/ConvenienceMethodsTest.php
22. tests/ResourceTests/ConstantValidationTest.php
```

**Tag: v0.7.0** after all tests pass.

### Phase 2 (v0.8.0) — Implementation Order

```
23. composer.json            (FR-022, FR-023, FR-021) — depends on Phase 1 tag
24. ErrorSeverity.php        (FR-020)                 — depends on #23 (PHP 8.1)
25. PsrLogAdapter.php        (FR-021)                 — depends on #23, #24
26. Configuration.php        (FR-023)                 — depends on #23 (hassankhan removed)
    ── [#24, #25, #26 can be parallel after #23] ──

27. Error.php                (FR-020)                 — depends on #24
28. Paymo.php                (FR-021, FR-020)         — depends on #25, #27

    ── [test files after implementation] ──

29. tests/UtilityTests/ErrorSeverityTest.php
30. tests/UtilityTests/PsrLogAdapterTest.php
31. tests/UtilityTests/ConfigurationRewriteTest.php
```

**Tag: v0.8.0** after all tests pass.

### Phase 3 (v0.9.0) — Implementation Order

```
32. PaymoDateTime.php        (FR-005)                 — no dependencies
33. RequestAbstraction.php   (FR-014, FR-017)         — no dependencies
    ── [#32, #33 parallel] ──

34. Converter.php            (FR-005)                 — depends on #32
35. Cache.php                (FR-017, FR-018)         — depends on #33 (key format)
    ── [#34, #35 parallel] ──

36. AbstractResource.php     (FR-005)                 — depends on #34
37. AbstractEntity.php       (FR-014, FR-015)         — depends on #33
    ── [#36, #37 parallel] ──

38. AbstractCollection.php   (FR-012)                 — depends on #36 (typed data)
39. Paymo.php                (FR-017, FR-014)         — depends on #35, #33
    ── [#38, #39 parallel] ──

40. FileCollection.php       (FR-013)                 — no dependencies
41. BookingCollection.php    (FR-013)                 — no dependencies
42. InvoiceItemCollection.php (FR-013)                — no dependencies
43. EstimateItemCollection.php (FR-013)               — no dependencies
    ── [#40-43 all parallel, independent of each other and above] ──

    ── [test files after implementation] ──

44. tests/UtilityTests/PaymoDateTimeTest.php
45. tests/UtilityTests/ConverterCoercionTest.php
46. tests/ResourceTests/TypeCoercionTest.php
47. tests/CollectionTests/FetchAllTest.php
48. tests/CacheTests/InvalidationTest.php
49. tests/CacheTests/CacheCallbackTest.php
50. tests/CollectionTests/ParentFilterTest.php
51. tests/RequestTests/PartialIncludeTest.php
52. tests/RequestTests/NestedIncludeTest.php
```

**Tag: v0.9.0** after all tests pass.

### Phase 4 (v1.0.0-alpha) — Implementation Order

```
53. Investigation scripts (FR-040 through FR-052)     — requires live API access
54. All 38 Resource/*.php    (FR-004)                 — depends on #53 investigation results
55. CLAUDE.md                (FR-039)                 — depends on all phases
56. PACKAGE-DEV.md           (FR-039)                 — depends on all phases
57. README.md                (FR-039)                 — depends on all phases
58. CHANGELOG.md             (FR-039)                 — depends on all phases
59. OVERRIDES.md             (FR-004, FR-039)         — depends on #53
```

**Tag: v1.0.0-alpha** after all tests pass and documentation is reviewed.

---

## Dependency Graph

```
Phase 1 (v0.7.0):
  composer.json ──────────────────────────────────────→ [independent]
  EntityMap.php ──────────────────────────────────────→ [independent]
  RequestResponse.php ──────────────────────────────→ [independent]
  Configuration.php (reset) ─────────────────────────→ [independent]
  Converter.php (in me) ─────────────────────────────→ [independent]
  Expense.php, Report.php ───────────────────────────→ [independent]
  default.paymoapi.config.json ──────────────────────→ [independent]

  Paymo.php (devMode, cacheHit) ─────→ [RequestResponse.php, config.json]
  Log.php (devMode) ─────────────────→ [Paymo.php devMode constant removal]
  AbstractResource.php ──────────────→ [Paymo.php devMode for FR-011]
  AbstractCollection.php ────────────→ [independent]

Phase 2 (v0.8.0):
  composer.json (PHP 8.1, deps) ─────→ [Phase 1 tagged]

  ErrorSeverity.php ─────────────────→ [composer.json PHP 8.1]
  PsrLogAdapter.php ─────────────────→ [composer.json psr/log, ErrorSeverity.php]
  Configuration.php (rewrite) ───────→ [composer.json hassankhan removed]

  Error.php ─────────────────────────→ [ErrorSeverity.php]
  Paymo.php (PSR-3, handler) ────────→ [PsrLogAdapter.php, Error.php]

Phase 3 (v0.9.0):
  PaymoDateTime.php ─────────────────→ [Phase 2 tagged, PHP 8.1]
  RequestAbstraction.php ────────────→ [independent within phase]

  Converter.php (coercion) ──────────→ [PaymoDateTime.php]
  Cache.php (invalidation) ──────────→ [RequestAbstraction.php key format]

  AbstractResource.php (coercion) ───→ [Converter.php coercion]
  AbstractEntity.php (includes) ─────→ [RequestAbstraction.php partialInclude]

  AbstractCollection.php (fetchAll) ─→ [AbstractResource.php coercion]
  Paymo.php (invalidation, partial) ─→ [Cache.php, RequestAbstraction.php]

  FileCollection.php ────────────────→ [independent]
  BookingCollection.php ─────────────→ [independent]
  InvoiceItemCollection.php ─────────→ [independent]
  EstimateItemCollection.php ────────→ [independent]

Phase 4 (v1.0.0-alpha):
  Investigations ────────────────────→ [Phase 3 tagged, live API access]
  Resource property additions ───────→ [Investigation artifacts]
  Documentation ─────────────────────→ [All implementation complete]
```

---

## Impact Assessment

### High-Impact Changes (Regression Risk: Medium-High)

| File | Change | Risk | Mitigation |
|------|--------|------|------------|
| `AbstractResource.php` (Phase 3) | Type coercion in `__set()`, `create()`, `update()` | All 38 resources affected. Datetime properties change from string to PaymoDateTime. Consumers doing `=== 'string'` on datetimes will break. | PaymoDateTime `__toString()` preserves string contexts. Run full regression suite. Document `===` behavior change. |
| `Paymo.php` (Phase 1) | devMode constant removal | Code referencing `PAYMO_DEVELOPMENT_MODE` directly will error at runtime. | Grep for all usages (found in Paymo.php and Log.php — both modified). No consumer-facing exposure of this constant. |
| `Configuration.php` (Phase 2) | Full internal rewrite replacing Noodlehaus\Config | All configuration access flows through this class. | Preserve exact public API signatures. Run full regression suite. Targeted test for every Configuration method. |
| `Cache.php` (Phase 3) | Cache key format change | Existing cached data orphaned. | Caches are ephemeral — expire via TTL. No migration needed. |

### Medium-Impact Changes (Regression Risk: Low-Medium)

| File | Change | Risk | Mitigation |
|------|--------|------|------------|
| `EntityMap.php` (Phase 1) | Re-enable overload validation | Overloads with non-AbstractResource classes will now throw. | This is fixing a bug — the validation was always intended. |
| `Error.php` (Phase 2) | Union type on `throw()` | None — accepts both enum and string. | Existing string calls unchanged. |
| `Paymo.php` (Phase 2) | New `$logger` param on `connect()` | None — optional param at end. | Existing calls unchanged. |
| Collection classes (Phase 3) | `validateFetch()` now checks fluent conditions | Calls using `->where()` fluent API that were previously failing validation will now pass. | This is a bug fix — improves behavior. |
| `AbstractCollection.php` (Phase 1) | `flatten()` parameter type widened | None — widening is backward compatible. | Old `flatten([])` and `flatten()` calls unchanged. |

### Low-Impact Changes (Regression Risk: Minimal)

| File | Change | Risk |
|------|--------|------|
| `RequestResponse.php` | New `$cacheHit` property | Additive — default false. |
| `composer.json` autoload-dev | Fix namespace mapping | Test files already use correct namespace. |
| `Converter.php` in(me) | New value handling in `convertOperatorValue()` | Only triggers for literal `'me'` value. |
| `Expense.php`, `Report.php` | New `download_token` property | Additive — new constant entry. |
| All new files (ErrorSeverity, PaymoDateTime, PsrLogAdapter) | New classes | No existing code affected until integration. |
| Test files | All new test files | No production code impact. |
| Documentation files | Content updates | No code impact. |

---

## Configuration Changes

### `default.paymoapi.config.json` Modifications

| Key | Phase | Current | Target | Purpose |
|-----|-------|---------|--------|---------|
| `devMode` | v0.7.0 | May be missing | `false` (explicit) | FR-002: devMode reads from config |
| `includes.maxDepth` | v0.7.0 | N/A | `2` | FR-015: forward-compat for nested include validation |

### `composer.json` Modifications Summary

| Phase | Changes |
|-------|---------|
| v0.7.0 | Fix autoload-dev namespace; add scripts section |
| v0.8.0 | PHP `>=8.1`; remove hassankhan/config; add psr/log |

### Environment Variables

No new environment variables introduced. All configuration is via `paymoapi.config.json` or `default.paymoapi.config.json`.

### Feature Flags

No feature flags. New features are always-on once the version containing them is installed. devMode-gated features (validateConstants, include depth validation, parent filter enforcement errors) use the existing `devMode` config key.

---

## Test File Directory Structure

New test files follow existing conventions with some new directories:

```
tests/
├── ResourceTests/          (existing)
│   ├── EntityMapTest.php           (NEW - Phase 1)
│   ├── ConvenienceMethodsTest.php  (NEW - Phase 1)
│   ├── ConstantValidationTest.php  (NEW - Phase 1)
│   └── TypeCoercionTest.php        (NEW - Phase 3)
├── UtilityTests/           (NEW directory)
│   ├── DevModeTest.php             (NEW - Phase 1)
│   ├── ConfigurationTest.php       (NEW - Phase 1)
│   ├── ConfigurationRewriteTest.php (NEW - Phase 2)
│   ├── RequestResponseTest.php     (NEW - Phase 1)
│   ├── ConverterTest.php           (NEW - Phase 1)
│   ├── ConverterCoercionTest.php   (NEW - Phase 3)
│   ├── ErrorSeverityTest.php       (NEW - Phase 2)
│   ├── PsrLogAdapterTest.php       (NEW - Phase 2)
│   └── PaymoDateTimeTest.php       (NEW - Phase 3)
├── CollectionTests/        (NEW directory)
│   ├── FlattenPluckTest.php        (NEW - Phase 1)
│   ├── FetchAllTest.php            (NEW - Phase 3)
│   └── ParentFilterTest.php        (NEW - Phase 3)
├── CacheTests/             (NEW directory)
│   ├── InvalidationTest.php        (NEW - Phase 3)
│   └── CacheCallbackTest.php       (NEW - Phase 3)
└── RequestTests/           (NEW directory)
    ├── PartialIncludeTest.php      (NEW - Phase 3)
    └── NestedIncludeTest.php       (NEW - Phase 3)
```

All test classes extend `Jcolombo\PaymoApiPhp\Tests\ResourceTest` or a new lightweight base class for non-resource tests. Namespace follows `Jcolombo\PaymoApiPhp\Tests\{SubDirectory}`. The test runner (`tests/validate`) must be updated to discover tests in new subdirectories if it currently only scans `ResourceTests/`.
