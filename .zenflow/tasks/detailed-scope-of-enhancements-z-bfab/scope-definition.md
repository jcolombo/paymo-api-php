# Scope Definition: paymo-api-php v1.0.0-alpha

---

## 1. Executive Summary

This document is the complete, standalone implementation blueprint for upgrading the paymo-api-php SDK from v0.6.1 to v1.0.0-alpha. The upgrade addresses 3 confirmed bugs, adds 60+ undocumented API properties, implements three-direction type coercion, auto-pagination, cache invalidation on mutation, structured error severity, PSR-3 logging, and modernizes the foundation from PHP 7.4 to PHP 8.1 while replacing a stale dependency.

The upgrade is delivered as a four-phase, four-version progression (v0.7.0 → v0.8.0 → v0.9.0 → v1.0.0-alpha). Each version is a tagged, installable, backward-compatible release serving as a safe rollback point. The sole environment-breaking change — the PHP 7.4 → 8.1 minimum version bump — is isolated to the v0.8.0 boundary, allowing PHP 7.4 consumers to stay on v0.7.x indefinitely. A total of 48 files are affected (24 created, 24 modified, 0 deleted), plus up to 38 resource class modifications and 13 investigation artifacts in Phase 4.

An implementation agent reading only this document has everything needed to build every phase without consulting any other file or asking any clarifying question.

---

## 2. Problem Statement

### What Exists Today

The paymo-api-php SDK (v0.6.1) is a PHP library providing ORM-style access to the Paymo REST API. It covers 38 resources with CRUD operations, includes filtering (WHERE/HAS), server-side pagination, rate limiting, caching, and a custom integration test framework. It requires PHP >= 7.4 and depends on Guzzle 7, hassankhan/config, and adbario/php-dot-notation.

### What Is Wrong or Missing

1. **3 confirmed bugs**: A typo in `EntityMap::overload()` (`"AbstractResourcce"` double-c) disables overload validation entirely, `PAYMO_DEVELOPMENT_MODE` is hardcoded to `true` leaking error details in production, and `composer.json` autoload-dev maps the wrong namespace.

2. **60+ undocumented properties** returned by the live API are absent from SDK resource classes — consumers cannot access data the API already provides.

3. **No type coercion**: Consumers manually cast datetime strings, booleans, and enums. Both peer SDKs (niftyquoter-api-php, leadfeeder-api-php) implement three-direction coercion.

4. **No cache invalidation on mutation**: Create/update/delete operations do not invalidate cached data, causing stale reads in multi-step workflows.

5. **Flat error handling**: No distinction between recoverable warnings (429 rate limit) and terminal failures (401 authentication).

6. **Stale dependency**: hassankhan/config (last updated 2021) wraps php-dot-notation with unused YAML/XML/INI support.

7. **PHP 7.4 floor**: EOL since November 2022. Blocks native enums, readonly properties, union types. Both peer SDKs require >= 8.1.

8. **Missing API features**: No fetchAll() auto-pagination, no parent context enforcement at runtime, no partial_include or nested include dot notation, no `in(me)` filter syntax.

### Cost of Inaction

- The hardcoded devMode leaks error details in production.
- The EntityMap typo means overload validation is silently disabled.
- Every consumer writes boilerplate for pagination loops, datetime parsing, and error classification.
- hassankhan/config has no maintainer activity and may break on future PHP versions.
- 60+ API properties are inaccessible through the SDK.

### Who Is Affected

- **PHP developers** integrating Paymo into backend applications (primary consumers)
- **SDK maintainers** managing the paymo/niftyquoter/leadfeeder SDK family
- **AI coding assistants** operating on the codebase via CLAUDE.md guidance

---

## 3. Requirements

### Functional Requirements

#### Bug Fixes (FR-001 through FR-003)

| ID | Requirement | Phase |
|----|------------|-------|
| FR-001 | When `EntityMap::overload()` is called, the string comparison for the base class name must match `"AbstractResource"` exactly (fix the `"AbstractResourcce"` typo). Change from string literal to `AbstractResource::class` constant. | v0.7.0 |
| FR-002 | The `PAYMO_DEVELOPMENT_MODE` constant in `Paymo.php` must read its value from the `devMode` configuration key instead of being hardcoded to `true`. When `devMode` is `false` (or not set), error details must not be output to stdout. All references to the constant in `Paymo.php` and `Log.php` must be updated. | v0.7.0 |
| FR-003 | The `autoload-dev` section in `composer.json` must map namespace `Jcolombo\\PaymoApiPhp\\Tests\\` to `tests/` (not `Jcolombo\\PaymoApiPhp\\` to `src`). Composer test scripts (`test`, `test:dry-run`, `test:verbose`) must be added. | v0.7.0 |

#### Property & Type System (FR-004 through FR-011)

| ID | Requirement | Phase |
|----|------------|-------|
| FR-004 | All undocumented properties discovered through live API testing (60+ properties across 15+ resource classes) must be added to respective resource `PROP_TYPES` with correct types. Each classified as READONLY or writable. Annotated with `// Undocumented` comments per OVERRIDE-011 policy. **Depends on FR-040/FR-046.** | v1.0.0-alpha |
| FR-005 | A three-direction type coercion system in `Converter.php`: **Phase 1 (hydration):** `convertToPhpValue($value, $type)` — datetime strings → `PaymoDateTime` (extends `\DateTimeImmutable`), boolean strings → native `bool`, integer strings → `int`, float strings → `float`. **Phase 2 (serialization):** `convertForRequest($value, $type)` — `\DateTimeInterface` → ISO 8601 string, `bool` → API-expected boolean. Existing type strings in PROP_TYPES continue to work. | v0.9.0 |
| FR-006 | Verify Session resource `id` is `text` type in PROP_TYPES (per OVERRIDE-004). Verification only — no code change if correct. | v0.7.0 |
| FR-007 | Verify Invoice/Estimate `language` in READONLY to prevent consumer writes. Verification only. | v0.7.0 |
| FR-008 | Add `download_token` property to Expense and Report PROP_TYPES as `text`, READONLY. | v0.7.0 |
| FR-009 | Add `WRITEONLY` property constant to `AbstractResource` (empty array default). Properties in WRITEONLY are always included when set in dirty tracking, never expected in API responses. | v0.7.0 |
| FR-010 | Audit whether any Paymo API properties are WRITEONLY-applicable. If none, FR-009 is implemented but unused (structural parity with peer SDKs). | v0.7.0 |
| FR-011 | In devMode, `AbstractResource` validates constant consistency at instantiation: no READONLY in REQUIRED_CREATE, no WRITEONLY in READONLY, all constant arrays reference valid PROP_TYPES keys, no unknown type prefixes. Cached per class. | v0.7.0 |

#### Query System (FR-012 through FR-016)

| ID | Requirement | Phase |
|----|------------|-------|
| FR-012 | `AbstractCollection::fetchAll()` auto-paginates through all pages. Default safety cap 5,000 records. Per-resource `FETCHALL_CAP` constant for override. Stop when result count < page_size or cap reached. Returns single merged collection. | v0.9.0 |
| FR-013 | Collection classes for File, Booking, InvoiceItem, EstimateItem validate required parent filters before API calls (`validateFetch()`). In devMode: throw exception. In production: log warning. Must check both fluent `whereConditions` and `$where` parameter. | v0.9.0 |
| FR-014 | Query builder supports `partial_include` syntax: `$project->fetch($id, ['tasks(id,name,due_date)'])` → `?partial_include=tasks(id,name,due_date)`. DevMode field validation against included resource's PROP_TYPES. | v0.9.0 |
| FR-015 | Query builder supports nested include dot notation: `$project->fetch($id, ['tasks.entries'])` → `?include=tasks.entries`. Max depth 2 (configurable via `includes.maxDepth`). DevMode validation per level. | v0.9.0 |
| FR-016 | `RequestCondition` builder supports `in(me)` syntax: `Task::where('user_id', 'me', 'in')` → `user_id in (me)`. | v0.7.0 |

#### Caching System (FR-017 through FR-019)

| ID | Requirement | Phase |
|----|------------|-------|
| FR-017 | After successful POST/PUT/DELETE, cache invalidates entries matching the affected resource type. URL-pattern-based key matching. Cache key format changes from `paymoapi-{md5}` to `paymoapi-{resourceBase}-{md5}`. File-based: `glob("paymoapi-{resource}-*")`. Custom backends: `$clear` callback receives resource base string. | v0.9.0 |
| FR-018 | `registerCacheMethods()` accepts optional third `$clear` callback: `function(string $resourceBase): void`. Existing 2-callback registrations continue to work. | v0.9.0 |
| FR-019 | `RequestResponse` includes `$cacheHit` boolean (default `false`). Set `true` when response served from cache. | v0.7.0 |

#### Error Handling & Logging (FR-020 through FR-021)

| ID | Requirement | Phase |
|----|------------|-------|
| FR-020 | `ErrorSeverity` PHP 8.1 backed string enum with cases `NOTICE='notice'`, `WARN='warn'`, `FATAL='fatal'`. Static `fromHttpStatus(int)` method: 401/403/500+ → FATAL, 429 → WARN, 400/404/422 → NOTICE. `Error::throw()` accepts `ErrorSeverity\|string`. `setErrorHandler(callable)` on Paymo connection for consumer routing by severity. Default behavior (throw exceptions) preserved. | v0.8.0 |
| FR-021 | PSR-3 compatible logging via `PsrLogAdapter`. Consumers inject `LoggerInterface` via `Paymo::connect(logger: $logger)` or `$connection->setLogger($logger)`. Maps SDK severities to PSR-3 levels: `fatal` → ERROR, `warn` → WARNING, `notice` → NOTICE. Existing `Log` utility continues as default. `psr/log` added as dependency. | v0.8.0 |

#### Architecture & Dependencies (FR-022 through FR-028)

| ID | Requirement | Phase |
|----|------------|-------|
| FR-022 | Bump PHP minimum from `>=7.4` to `>=8.1`. Adopt PHP 8.1 features where they improve modified code: native enums, readonly properties, union types, `match` expressions. Do NOT rewrite untouched code. | v0.8.0 |
| FR-023 | Replace `hassankhan/config` with direct `Adbar\Dot` usage. Rewrite `Configuration.php` internals. Remove `hassankhan/config` from `composer.json`. All public API methods preserved: `get()`, `set()`, `has()`, `all()`, `load()`, `overload()`. | v0.8.0 |
| FR-024 | Add `Configuration::reset()` static method that nullifies the singleton instance. Next `get()` creates fresh instance from config file. | v0.7.0 |
| FR-027 | Add Composer scripts: `"test": "php tests/validate"`, `"test:dry-run": "php tests/validate --dry-run"`, `"test:verbose": "php tests/validate --verbose"`. | v0.7.0 |
| FR-028 | Fix the `EntityMap::overload()` typo (same as FR-001). | v0.7.0 |

#### Developer Experience (FR-032 through FR-033)

| ID | Requirement | Phase |
|----|------------|-------|
| FR-032 | `flatten($property)` string overload on `AbstractCollection`: returns flat array of that property's values. `flatten('name')` → `['Project A', 'Project B']`. Existing `flatten()` (no args) unchanged. | v0.7.0 |
| FR-033 | `toArray()` and `toJson()` on `AbstractResource` and `AbstractCollection`. `toArray()` wraps `flatten()`. `toJson(int $options = 0)` wraps `json_encode(flatten())`. | v0.7.0 |

#### Documentation (FR-034 through FR-039)

| ID | Requirement | Phase |
|----|------------|-------|
| FR-034 | Document 2,500-item include truncation in OVERRIDES.md (OVERRIDE-014). Optional devMode warning when included collection returns exactly 2,500 items. | v0.7.0 |
| FR-035 | Document Task/TimeEntry `description` HTML content in OVERRIDES.md (OVERRIDE-015). | v0.7.0 |
| FR-036 | Document `retainer_id` on Project in OVERRIDES.md (OVERRIDE-016). | v0.7.0 |
| FR-037 | Document webhook delete/update payload limitations in OVERRIDES.md (OVERRIDE-017). | v0.7.0 |
| FR-038 | Document sensitive Company properties in OVERRIDES.md (OVERRIDE-018). Add `// SENSITIVE` annotation. | v0.7.0 |
| FR-039 | Update CLAUDE.md, PACKAGE-DEV.md, README.md, CHANGELOG.md for all v1.0.0-alpha changes. | v1.0.0-alpha |

#### Investigation Items (FR-040 through FR-052)

These produce artifacts informing implementation decisions. Several are prerequisites for other FRs.

| ID | Requirement | Prerequisite For |
|----|------------|-----------------|
| FR-040 | Property discovery against live API for all 38 resources. Compare response properties against PROP_TYPES. Output: per-resource property diff. | FR-004 |
| FR-041 | Live test 4 leave management endpoints. Confirm CRUD behavior, property shapes, filter support. | Deferred FR-029 |
| FR-042 | Filter operator validation matrix for all 38 resources. Test which operators work on which properties. | WHERE_OPERATIONS updates |
| FR-043 | Spot-check ~25 SDK-only include relationships against live API. | Include accuracy |
| FR-044 | Verify per-resource HAS condition support. | HAS filtering accuracy |
| FR-045 | Test filter support for undocumented properties. | Filterable classification |
| FR-046 | Compare all 38 SDK classes against full API inventory. Output: 38-resource change manifest. | FR-004 |
| FR-047 | Reconcile include count discrepancy (82 from research vs 98 from SDK). | Include accuracy |
| FR-048 | Test 3-level and 4-level nested includes. Determine actual max depth. | FR-015 max depth config |
| FR-049 | Probe `GET /api/currencies` — is it a live endpoint? | Potential new resource |
| FR-050 | Test compound resource name response key anomalies. | OVERRIDE-009 accuracy |
| FR-051 | Investigate StatsReport additional report types. | Future resource expansion |
| FR-052 | Determine actual API rate limit values from response headers. | Rate limiter config |

### Non-Functional Requirements

| ID | Requirement | Threshold |
|----|------------|-----------|
| NFR-001 | Backward Compatibility: every version between v0.6.1 and v1.0.0-alpha is non-breaking relative to previous. Sole exception: PHP version bump at v0.8.0 boundary. Deprecations announced via `@deprecated` PHPDoc; deprecated code continues to function. | Zero breaking API changes per version (excluding PHP requirement) |
| NFR-002 | PHP minimum bumped from 7.4 to 8.1 at v0.8.0 boundary. All code from v0.8.0 forward may use PHP 8.1 features. | v0.8.0 = boundary |
| NFR-003 | All new public methods have test cases in the custom test framework. New resource classes have individual test classes in `tests/ResourceTests/`. | 100% new public method coverage |
| NFR-004 | Type coercion layer adds less than 1ms overhead per resource hydration (30 properties). fetchAll() holds accumulated results + one current page in memory (not all pages simultaneously). | <1ms per hydration; memory = accumulated + 1 page |
| NFR-005 | Only new Composer dependency: `psr/log`. All other features implemented without new external dependencies. | 1 new dependency maximum |
| NFR-006 | Every new public method, constant, or class documented in PACKAGE-DEV.md and CLAUDE.md. User-facing features in README.md. | 100% documentation coverage |
| NFR-007 | SemVer strictly followed. Pre-1.0 versions allow non-breaking additions. `1.0.0-alpha` signifies public API stability. | Strict SemVer compliance |

---

## 4. Target Users & Scenarios

### Primary: PHP developers integrating Paymo into backend applications
- **Skill level**: Intermediate to senior PHP developers comfortable with Composer, PSR standards, ORM-style APIs.
- **Context**: Building internal tools, billing integrations, time tracking dashboards, automation scripts.
- **Need**: Reliable, type-safe access to the full Paymo API surface without boilerplate.

### Secondary: SDK maintainers
- **Skill level**: Senior PHP developers maintaining the SDK family (paymo, niftyquoter, leadfeeder).
- **Need**: Consistent architecture patterns, automated validation, clear extension points.

### Tertiary: AI coding assistants
- **Context**: Guided by CLAUDE.md, OVERRIDES.md, PACKAGE-DEV.md.
- **Need**: Accurate documentation, consistent patterns, clear override policies.

### Usage Scenarios

**Scenario 1: Stale cache after mutation**
- **Trigger**: Developer creates invoice via `Invoice::new()->set([...])->create()`, then fetches list via `Invoice::list()->fetch()`.
- **Action**: `create()` triggers cache invalidation for `invoices` resource scope. Subsequent `list()->fetch()` returns fresh data.
- **Outcome**: New invoice appears in the list immediately.
- **Verification**: Create resource → list same type → confirm new resource present.

**Scenario 2: Auto-pagination**
- **Trigger**: Developer needs all time entries for a date range (potentially thousands).
- **Action**: `TimeEntry::list()->where(...)->fetchAll()` returns all matching records with automatic pagination, bounded by safety cap (default 5,000).
- **Outcome**: One method call replaces 10-15 lines of pagination loop code.
- **Verification**: Call `fetchAll()` on resource with >100 records → confirm all returned → confirm cap prevents runaway.

**Scenario 3: Working with datetime values**
- **Trigger**: Developer fetches project, needs to compare `created_on` to current date.
- **Action**: `$project->created_on` returns `\DateTimeImmutable` instance. `(string)$project->created_on` returns original ISO string. `json_encode($project->flatten())` produces API-format string.
- **Outcome**: Zero manual datetime parsing. Type-safe comparisons.
- **Verification**: Fetch resource → confirm `instanceof \DateTimeImmutable` → confirm JSON produces string → confirm `set('created_on', new \DateTimeImmutable())` serializes correctly.

**Scenario 4: Error severity routing**
- **Trigger**: Batch import encounters 429 rate limit response.
- **Action**: Error carries `ErrorSeverity` classification. Developer's handler routes WARN to retry logic, FATAL to abort-and-alert.
- **Outcome**: Error handling moves from HTTP-status-code guesswork to semantic severity routing.
- **Verification**: Trigger 429 → confirm severity is WARN. Trigger 401 → confirm severity is FATAL.

**Scenario 5: Upgrading from v0.6.x**
- **Trigger**: Production consumer running v0.6.1 wants to upgrade.
- **Action**: Update `composer.json` to require `>=8.1` for PHP and update package version. Existing code works without modification.
- **Outcome**: Zero breaking changes for existing code. New features are additive.
- **Verification**: Run existing v0.6.1 test suite against v1.0.0-alpha → all pass.

---

## 5. Success Criteria

| ID | Criterion | Maps to |
|----|----------|---------|
| SC-001 | A PHP application using v0.6.1 upgrades to v1.0.0-alpha by changing PHP requirement to `>=8.1` and updating package version. All existing CRUD, filtering, caching, pagination code works without modification. | NFR-001 |
| SC-002 | `EntityMap::overload()` correctly validates against `AbstractResource::class`. Overload validation is functional. | FR-001 |
| SC-003 | When `devMode` config is `false`, no error details output to stdout. When `true`, error details shown. | FR-002 |
| SC-004 | `composer dump-autoload --no-dev` produces autoloader without test classes. IDE autocompletion for `Jcolombo\PaymoApiPhp\` does not show test classes. | FR-003 |
| SC-005 | `$project->created_on` returns `\DateTimeImmutable` instance after hydration. `json_encode($project->flatten())` produces original API string format for datetime fields. | FR-005 |
| SC-006 | After `Invoice::new()->set([...])->create()`, immediately subsequent `Invoice::list()->fetch()` returns data including the new invoice (cache invalidated). | FR-017 |
| SC-007 | `Invoice::list()->fetchAll()` returns all invoices across pages without manual pagination. Terminates when fewer results than page_size or safety cap reached. | FR-012 |
| SC-008 | Catching an API error provides `ErrorSeverity` classification. 429 → WARN, 401 → FATAL. | FR-020 |
| SC-009 | `Configuration::reset()` destroys singleton. Subsequent `Configuration::get()` reads from config file afresh. | FR-024 |
| SC-010 | Each intermediate version (v0.7.0, v0.8.0, v0.9.0) is tagged, installable, passes existing + new tests. | NFR-007 |
| SC-011 | `hassankhan/config` absent from `composer.json` and `composer.lock`. `Configuration.php` uses `Adbar\Dot` directly. | FR-023 |
| SC-012 | `registerCacheMethods()` accepts 2 or 3 callbacks. 2-callback registrations unchanged. 3-callback enables mutation-triggered clearing. | FR-018 |
| SC-013 | `$response->cacheHit` is `true` from cache, `false` from live API. | FR-019 |
| SC-014 | PSR-3 logger injected via `$connection->setLogger($monolog)` receives entries for all API requests/responses at appropriate severity. | FR-021 |
| SC-015 | In devMode, instantiating resource whose REQUIRED_CREATE contains a READONLY property throws descriptive exception. | FR-011 |

---

## 6. Scope Boundaries

### In Scope (v1.0.0-alpha)

1. All 3 bug fixes (FR-001 through FR-003)
2. Undocumented property additions (FR-004, depends on FR-040/FR-046)
3. Three-direction type coercion phases 1-2 (FR-005)
4. fetchAll() auto-pagination (FR-012)
5. Cache invalidation on mutation (FR-017, FR-018)
6. Structured error handling with severity (FR-020)
7. PHP 8.1 minimum version bump (FR-022)
8. hassankhan/config dependency replacement (FR-023)
9. Parent context enforcement (FR-013)
10. PSR-3 logging adapter (FR-021)
11. All investigation items (FR-040 through FR-052)
12. All documentation updates (FR-034 through FR-039)
13. Developer experience conveniences (FR-032, FR-033)
14. devMode constant validation (FR-011)
15. WRITEONLY constant (FR-009, FR-010)
16. Cache-hit detection (FR-019)
17. Configuration::reset() (FR-024)
18. Composer test scripts (FR-027)
19. Partial include syntax (FR-014)
20. Nested include dot notation (FR-015)
21. `in(me)` filter syntax (FR-016)

### Out of Scope (deferred)

1. **Type coercion Phase 3 — enum validation** (FR-005 Phase 3): Deferred to 1.1.0. Requires comprehensive enum value discovery.
2. **Leave management resources** (FR-029): Deferred until FR-041 confirms endpoint stability.
3. **PDF/XLSX export** (FR-030): Deferred to 1.1.0. Binary response handling warrants separate testing.
4. **Webhook conditional filtering** (FR-031): Deferred to 1.1.0.
5. **Named rate limiter scopes** (FR-025): Deferred to 1.1.0.
6. **PHPUnit hybrid adoption**: Deferred indefinitely.
7. **Async/concurrent requests**: Deferred indefinitely.
8. **Middleware pipeline**: Deferred indefinitely.

### Future Considerations (post-1.0.0)

- Currencies resource (if FR-049 confirms endpoint exists)
- StatsReport additional report types (if FR-051 discovers them)
- 3+ level nested includes (if FR-048 confirms support)
- Forward-porting Paymo advantages to peer SDKs

---

## 7. Technical Context

### Tech Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| Language | PHP | >=7.4 (target: 8.1 at v0.8.0) |
| HTTP Client | Guzzle | ^7.8 |
| Configuration | hassankhan/config → Adbar\Dot (at v0.8.0) | ^3.2 → ^3.3 |
| Dot-notation | adbario/php-dot-notation | ^3.3 |
| Test Framework | Custom (`ResourceTest` base, `ResourceTestRunner` CLI) | N/A |
| Build/Package | Composer | 2.x |

**Codebase size:** 64 PHP source files (~25,400 lines), 37 test files, 38 entity resource classes, 8 specialized collection classes. No CI pipeline. No static analysis tools.

### Architecture: ORM-Style Entity SDK

```
┌─────────────────────────────────────────────────────────┐
│  Consumer Code                                          │
│  $project = Project::new()->fetch(12345, ['tasks']);    │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│  Entity Layer                                           │
│  AbstractEntity → AbstractResource (single)             │
│                 → AbstractCollection (list)              │
│  38 Resource subclasses, 8 Collection subclasses        │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│  Request Layer                                          │
│  Request (static) → RequestAbstraction (container)      │
│                   → RequestCondition (WHERE/HAS)        │
│                   → RequestResponse (result)             │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│  Connection Layer                                       │
│  Paymo (singleton per API key)                          │
│  → Guzzle HTTP → Rate Limiter → Cache                  │
└─────────────────────────────────────────────────────────┘
```

### Directory Structure

```
src/
├── Paymo.php                      # Main connection class (703 lines)
├── Configuration.php              # Singleton config manager (423 lines)
├── Request.php                    # Static request builder (804 lines)
├── Cache/
│   ├── Cache.php                  # File-based response cache (594 lines)
│   └── ScrubCache.php             # In-memory include validation cache (115 lines)
├── Entity/
│   ├── AbstractEntity.php         # Base entity (~200 lines)
│   ├── AbstractResource.php       # Single resource (~650 lines)
│   ├── AbstractCollection.php     # List/collection (~500 lines)
│   ├── EntityMap.php              # Entity registry (820 lines)
│   ├── Resource/                  # 38 resource classes
│   └── Collection/                # 8 specialized collections
└── Utility/
    ├── Converter.php              # Type conversion (241 lines)
    ├── Error.php                  # Error handling singleton (517 lines)
    ├── RateLimiter.php            # Rate limit tracker (386 lines)
    ├── RequestAbstraction.php     # Request container (286 lines)
    ├── RequestCondition.php       # WHERE/HAS builder (579 lines)
    ├── RequestResponse.php        # Response container (288 lines)
    ├── Log.php                    # Logging utility
    ├── Color.php                  # Terminal colors
    └── MetaData.php               # API metadata
```

### Key Architectural Patterns

1. **Singleton connections per API key** — `Paymo::$connections` array keyed by API key. `Paymo::connect()` returns existing or creates new. First connection is default.

2. **Static factory methods** — `Project::new()`, `Project::list()`, `Task::where()`, `Task::has()`. Consumer never directly instantiates Request.

3. **Constants-as-schema** — Each resource defines behavior via class constants: `PROP_TYPES`, `REQUIRED_CREATE`, `READONLY`, `CREATEONLY`, `UNSELECTABLE`, `INCLUDE_TYPES`, `WHERE_OPERATIONS`.

4. **Two distinct cache systems** — `Cache` = HTTP response cache (file-based or custom callbacks). `ScrubCache` = in-memory include-validation cache. Cache invalidation (FR-017) operates on `Cache` only.

5. **Fluent builder pattern** — `Collection->where()->limit()->fetch()`. Methods return `$this` for chaining.

### PROP_TYPES Type System

| PROP_TYPE | PHP Type | API Format |
|-----------|----------|------------|
| `text` | string | string |
| `html` | string | HTML string |
| `integer` | int | integer |
| `decimal` / `double` | float | decimal |
| `boolean` | bool | true/false |
| `date` | string → PaymoDateTime | "YYYY-MM-DD" |
| `datetime` | string → PaymoDateTime | ISO 8601 |
| `email` | string | email |
| `url` | string | URL |
| `enum:val1\|val2` | string | one of values |
| `enumInt:25\|50\|75\|100` | int | one of values |
| `resource:entitykey` | int | foreign key ID |
| `collection:entitykey` | int[] | array of IDs |
| `array` | int[] | array |

### Request Lifecycle

```
1. Consumer calls Resource::new()->fetch(123, ['tasks'])
2. AbstractResource::fetch() calls Request::fetch($connection, $path, $options)
3. Request::fetch() builds RequestAbstraction (method, URL, include, where, pagination)
4. Request::fetch() calls Paymo::execute($requestAbstraction)
5. Paymo::execute():
   a. Check Cache::fetch($cacheKey) if caching enabled
   b. If not cached: RateLimiter::waitIfNeeded($apiKey)
   c. Guzzle HTTP request to Paymo API
   d. RateLimiter::updateFromHeaders($apiKey, $headers)
   e. Populate RequestResponse
   f. Cache::store($cacheKey, $response) if caching enabled
   g. Return RequestResponse
6. Request::fetch() scrubs response body (extracts entity data)
7. AbstractResource::fetch() hydrates $props and $included
8. wash() synchronizes $loaded = $props
```

### Coding Conventions

- Classes: PascalCase. Methods: camelCase. Constants: UPPER_SNAKE_CASE. Entity keys: lowercase plural.
- Mostly 4-space indentation (Error.php uses 2-space).
- Fully qualified imports. No group imports. No aliasing.
- Two error paths: `Error::throw('severity', ...)` for SDK errors; `throw new RuntimeException()` for contract violations.

### Backward Compatibility Constraints

- `flatten()` used by consumers — cannot remove
- `jsonSerialize()` on collections returns specific format
- `set()` currently accepts any value — adding validation may break consumers
- WHERE condition string format consumed by Paymo API
- Cache key format is MD5-based — changing format orphans existing caches (expire via TTL)

### API Constraints (from OVERRIDES.md)

- 13 active overrides documenting deviations from stale API docs (last updated 2022)
- OVERRIDE-003: Undocumented pagination via `page`/`page_size`
- OVERRIDE-005: Some collections require parent filters
- OVERRIDE-009/010: Non-standard response keys for template/recurring resources
- OVERRIDE-013: 32 UNSELECTABLE properties across 6 resources
- 4 filter-only properties (WHERE valid, never in responses)

---

## 8. Solution Architecture

### High-Level Design

The upgrade extends the existing ORM-style entity SDK pattern. New functionality is added through:
- Method additions on abstract classes (`AbstractResource`, `AbstractCollection`)
- Extensions to `Converter` for three-direction type coercion
- Cache invalidation hooks in `Paymo::execute()` pipeline
- Formalized `ErrorSeverity` enum replacing string-based severity
- `hassankhan/config` replaced with direct `Adbar\Dot`
- `psr/log` added for optional PSR-3 logging

Backward compatibility maintained via:
1. Typed values implementing `__toString()` so string contexts work
2. New method parameters added at end with defaults
3. `registerCacheMethods()` third callback optional
4. ErrorSeverity enum backed values match existing string severity names
5. PSR-3 logging is optional injection on top of existing `Log`

### Architecture Decisions

**AD-001: PHP 8.1 Minimum at v0.8.0 Boundary**
- PHP 7.4 EOL since Nov 2022. Both peer SDKs require >=8.1.
- Isolating at v0.8.0 gives consumers clear upgrade path.
- Alternative rejected: keeping PHP 7.4 — blocks enums, union types, readonly.

**AD-002: Extend Converter Class for Type Coercion**
- Existing Converter handles type mapping and filter conversion.
- Adding hydration/serialization keeps all type logic co-located.
- Alternative rejected: separate TypeCoercion class — fragments type knowledge.

**AD-003: Cache Key Prefixing for Invalidation**
- Format: `paymoapi-{resourceBase}-{md5}` enables `glob("paymoapi-projects-*")`.
- Survives PHP restarts (no in-memory index).
- Existing cache files orphaned, expire via TTL.
- Alternative rejected: JSON manifest — persistence/corruption risk.

**AD-004: ErrorSeverity as PHP 8.1 Backed Enum**
- Backing values (`'notice'`, `'warn'`, `'fatal'`) match existing handler config keys.
- Provides exhaustive match/switch checking.
- Alternative rejected: string constants class — PHP 8.1 enums are superior.

**AD-005: PSR-3 as Optional Adapter Layer**
- PsrLogAdapter wraps LoggerInterface, maps SDK severity to PSR-3 levels.
- Existing `Log` utility continues as default.
- Both systems coexist if both configured.
- Alternative rejected: replace Log entirely — breaks consumers using file logging.

**AD-006: Configuration Singleton Destruction via reset()**
- Static `Configuration::reset()` nullifies singleton.
- Primary use: test isolation.
- Alternative rejected: reload method — destroy is simpler and more thorough.

**AD-007: Replace hassankhan/config with Direct Adbar\Dot**
- hassankhan/config last maintained 2021, wraps Adbar\Dot with unused YAML/XML/INI.
- Direct Adbar\Dot provides same `get()`/`has()`/`set()`/`all()` methods.
- Alternative rejected: keep hassankhan/config — maintenance risk.

**AD-008: PaymoDateTime Extends DateTimeImmutable**
- `instanceof \DateTimeImmutable` returns true.
- `__toString()` returns original API string — preserves string contexts.
- `JsonSerializable` returns original API string.
- `===` with strings breaks (documented behavior change).
- Alternative rejected: wrapper class — `instanceof` would return false.

**AD-009: fetchAll() via Iterative Page Merging**
- Iterates pages, merges into `$this->data` after each.
- Memory: accumulated + one page at any point.
- Safety cap default 5,000, per-resource override via `FETCHALL_CAP`.
- Alternative rejected: generator/streaming — must return populated collection.

**AD-010: Partial Includes via Separate Query Parameter**
- Includes with parentheses routed to `partial_include` param.
- Regular and partial can coexist.
- DevMode validates sub-fields against PROP_TYPES.

**AD-011: Nested Includes Validated at Parse Time**
- Dot notation validated per level against parent's INCLUDE_TYPES.
- Max depth 2 by default (configurable via `includes.maxDepth`).
- Dot string passed directly to API `include` param.

**AD-012: Four-Phase Delivery**
- v0.7.0 (PHP 7.4, bug fixes + additions) → v0.8.0 (PHP 8.1 + modernization) → v0.9.0 (core features) → v1.0.0-alpha (investigations + properties + polish).
- Each version is a tagged rollback point.

### Data Flow: Type Coercion Pipeline

```
API Response (JSON string values)
         │
         ▼
Paymo::execute() → RequestResponse
         │
         ▼
Request::fetch() → scrubs response → extracts entity data
         │
         ▼
AbstractResource::_hydrate() → calls __set() for each property
         │
         ▼
__set() → Converter::convertToPhpValue($value, $type)
         │
         ├── datetime → PaymoDateTime (extends DateTimeImmutable)
         ├── boolean  → native bool
         ├── integer  → int
         ├── decimal  → float
         └── other    → unchanged
         │
         ▼
$this->props[$name] = coerced value
         │
         ▼ (on create/update)
create()/update() body assembly
         │
         ▼
Converter::convertForRequest($value, $type)
         │
         ├── DateTimeInterface → 'c' format (datetime) or 'Y-m-d' (date)
         ├── bool              → native bool
         ├── int/float         → numeric cast
         └── other             → unchanged
         │
         ▼
API Request (correctly typed values)
```

---

## 9. Data Structures & Types

### ErrorSeverity Enum (Phase 2, v0.8.0)

```php
<?php
// src/Utility/ErrorSeverity.php
namespace Jcolombo\PaymoApiPhp\Utility;

/**
 * SDK error severity classification.
 * Backed values match existing string severity names for backward compatibility.
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
 * DateTimeImmutable subclass preserving original API string value.
 *
 * Backward compatibility:
 *   - instanceof \DateTimeImmutable → true
 *   - (string)$paymoDateTime → original API string
 *   - json_encode($paymoDateTime) → quoted original API string
 *   - $paymoDateTime == 'original-string' → true (loose equality)
 *   - $paymoDateTime === 'original-string' → false (DOCUMENTED BREAKING CHANGE)
 */
class PaymoDateTime extends \DateTimeImmutable implements \JsonSerializable
{
    private readonly string $rawApiValue;

    public function __construct(string $datetime = 'now', ?\DateTimeZone $timezone = null)
    {
        parent::__construct($datetime, $timezone);
        $this->rawApiValue = $datetime;
    }

    public function __toString(): string
    {
        return $this->rawApiValue;
    }

    public function jsonSerialize(): string
    {
        return $this->rawApiValue;
    }

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
 * Bridges SDK severity-based logging to PSR-3 LoggerInterface.
 */
class PsrLogAdapter
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

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

    public function logRequest(string $method, string $url, ?array $data = null): void
    {
        $this->logger->debug("API {$method} {$url}", [
            'method' => $method, 'url' => $url, 'data' => $data,
        ]);
    }

    public function logResponse(int $statusCode, string $url, float $responseTime, bool $fromCache = false): void
    {
        $this->logger->debug(
            "API Response {$statusCode} {$url} ({$responseTime}ms)" . ($fromCache ? ' [CACHE]' : ''),
            ['status_code' => $statusCode, 'url' => $url, 'response_time' => $responseTime, 'from_cache' => $fromCache]
        );
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
```

### Converter Method Additions (Phase 3, v0.9.0)

```php
<?php
// Added to src/Utility/Converter.php

class Converter
{
    // Existing methods unchanged:
    //   getPrimitiveType(), convertOperatorValue(), convertValueForFilter()

    /**
     * Convert API response value to typed PHP value (hydration: API → PHP).
     */
    public static function convertToPhpValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof PaymoDateTime && (str_starts_with($type, 'datetime') || $type === 'date')) {
            return $value;
        }

        $prefix = explode(':', $type, 2)[0];
        return match ($prefix) {
            'datetime', 'date' => self::hydrateDateTime($value),
            'boolean'  => self::hydrateBoolean($value),
            'integer'  => is_array($value) ? array_map('intval', $value) : (int) $value,
            'decimal', 'double' => (float) $value,
            'resource' => (int) $value,
            'collection' => is_array($value) ? array_map('intval', $value) : (int) $value,
            'array'    => is_array($value) ? array_map('intval', $value) : (int) $value,
            default    => $value, // text, html, email, url, enum, enumInt, datetime[]
        };
    }

    /**
     * Convert PHP value to API-expected format (serialization: PHP → API).
     */
    public static function convertForRequest(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        $prefix = explode(':', $type, 2)[0];
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

    private static function hydrateDateTime(mixed $value): PaymoDateTime|\DateTimeImmutable
    {
        if ($value instanceof \DateTimeInterface) {
            return ($value instanceof PaymoDateTime) ? $value : new PaymoDateTime($value->format('c'));
        }
        return new PaymoDateTime((string) $value);
    }

    private static function hydrateBoolean(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        if (is_int($value)) return $value !== 0;
        if (is_string($value)) return in_array(strtolower($value), ['true', '1', 'yes'], true);
        return (bool) $value;
    }
}
```

### New/Modified Constants

```php
// AbstractResource: WRITEONLY constant (Phase 1, v0.7.0)
abstract class AbstractResource extends AbstractEntity
{
    // NEW: Properties that trigger API actions when set but are never returned.
    // Empty by default — no Paymo properties currently fit this pattern.
    // Structural parity with peer SDKs.
    public const WRITEONLY = [];
}

// AbstractCollection: FETCHALL_CAP constant (Phase 3, v0.9.0)
abstract class AbstractCollection extends AbstractEntity
{
    // Default safety cap for fetchAll() auto-pagination.
    // Override in collection subclasses for resources with known large datasets.
    public const FETCHALL_CAP = 5000;
}
```

### RequestResponse: cacheHit Property (Phase 1, v0.7.0)

```php
// Added to src/Utility/RequestResponse.php
class RequestResponse
{
    // Existing properties unchanged...
    public ?string $fromCacheKey = null;
    public bool $success = false;
    // ...

    // NEW: Explicit cache-hit indicator
    public bool $cacheHit = false;
}
```

### devMode Constant Validation Logic (Phase 1, v0.7.0)

```php
// Added to AbstractResource

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
    $validPrefixes = [
        'text', 'integer', 'decimal', 'double', 'boolean', 'date', 'datetime',
        'datetime[]', 'url', 'email', 'html', 'array', 'resource', 'collection',
        'enum', 'enumInt', 'intEnum',
    ];

    // 1. READONLY ∩ REQUIRED_CREATE = ∅
    foreach ($requiredCreate as $req) {
        if (preg_match('/[|&]/', $req)) continue; // skip compound
        if (in_array($req, $readonly, true)) {
            throw new \RuntimeException("{$class}: '{$req}' in both READONLY and REQUIRED_CREATE.");
        }
    }

    // 2. WRITEONLY ∩ READONLY = ∅
    $overlap = array_intersect($writeonly, $readonly);
    if (count($overlap) > 0) {
        throw new \RuntimeException("{$class}: [" . implode(', ', $overlap) . "] in both WRITEONLY and READONLY.");
    }

    // 3-6. All constant arrays reference valid PROP_TYPES keys
    $checks = ['UNSELECTABLE' => static::UNSELECTABLE, 'READONLY' => $readonly,
               'CREATEONLY' => static::CREATEONLY, 'WRITEONLY' => $writeonly];
    foreach ($checks as $constName => $keys) {
        foreach ($keys as $key) {
            if (!in_array($key, $propTypes, true)) {
                throw new \RuntimeException("{$class}: '{$key}' in {$constName} not in PROP_TYPES.");
            }
        }
    }

    // 7. Validate type prefixes
    foreach (static::PROP_TYPES as $prop => $type) {
        $prefix = explode(':', $type, 2)[0];
        if (!in_array($prefix, $validPrefixes, true)) {
            throw new \RuntimeException("{$class}: Unknown type prefix '{$prefix}' for '{$prop}'.");
        }
    }

    self::$validatedClasses[$class] = true;
}
```

---

## 10. API Specification

Not applicable as an external API specification — this is a client SDK library consuming the Paymo REST API. The SDK does not define API endpoints. However, the following new SDK methods constitute the "public API" for consumers:

### New Public Methods

| Method | Location | Signature | Phase |
|--------|----------|-----------|-------|
| `fetchAll()` | AbstractCollection | `fetchAll(?array $fields = null, ?array $conditions = null, array $options = []): static` | v0.9.0 |
| `toArray()` | AbstractResource / AbstractCollection | `toArray(array $options = []): array` | v0.7.0 |
| `toJson()` | AbstractResource / AbstractCollection | `toJson(int $options = 0): string` | v0.7.0 |
| `flatten(string)` | AbstractCollection | `flatten(string $property): array` (overload) | v0.7.0 |
| `Configuration::reset()` | Configuration | `static reset(): void` | v0.7.0 |
| `setLogger()` | Paymo | `setLogger(\Psr\Log\LoggerInterface $logger): void` | v0.8.0 |
| `getLogger()` | Paymo | `getLogger(): ?PsrLogAdapter` | v0.8.0 |
| `setErrorHandler()` | Paymo | `setErrorHandler(callable $handler): void` | v0.8.0 |
| `Cache::invalidateByResource()` | Cache | `static invalidateByResource(string $resourceBase): void` | v0.9.0 |
| `Cache::clearAll()` | Cache | `static clearAll(): void` | v0.9.0 |

### Modified Public Methods

| Method | Change | Phase |
|--------|--------|-------|
| `Paymo::connect()` | New optional `?LoggerInterface $logger = null` parameter at end | v0.8.0 |
| `Error::throw()` | Parameter type widened: `ErrorSeverity\|string $severity` | v0.8.0 |
| `Cache::registerCacheMethods()` | Optional third `?callable $clear_callback = null` parameter | v0.9.0 |

---

## 11. Component Architecture (Class Hierarchy)

### New Classes

| Class | Location | Parent | Phase |
|-------|----------|--------|-------|
| `ErrorSeverity` | `src/Utility/ErrorSeverity.php` | (enum) | v0.8.0 |
| `PaymoDateTime` | `src/Utility/PaymoDateTime.php` | `\DateTimeImmutable` | v0.9.0 |
| `PsrLogAdapter` | `src/Utility/PsrLogAdapter.php` | (none) | v0.8.0 |

### Modified Classes Summary

| Class | Modifications | Phase(s) |
|-------|--------------|----------|
| `AbstractResource` | `WRITEONLY` const, `validateConstants()`, `toArray()`, `toJson()`, `__set()` coercion, `create()`/`update()` serialization | v0.7.0, v0.9.0 |
| `AbstractCollection` | `FETCHALL_CAP` const, `fetchAll()`, `flatten()` pluck overload, `toArray()`, `toJson()` | v0.7.0, v0.9.0 |
| `Cache` | `$clear_callback`, `invalidateByResource()`, `clearAll()`, modified `registerCacheMethods()` | v0.9.0 |
| `Configuration` | `reset()` then full rewrite (Adbar\Dot direct) | v0.7.0, v0.8.0 |
| `Converter` | `convertToPhpValue()`, `convertForRequest()`, `hydrateDateTime()`, `hydrateBoolean()`, modified `convertOperatorValue()` (in(me)) | v0.7.0, v0.9.0 |
| `Error` | ErrorSeverity integration, `setCustomHandler()`, modified `throw()` | v0.8.0 |
| `EntityMap` | Typo fix | v0.7.0 |
| `Paymo` | Remove `define()`, `setLogger()`/`getLogger()`, `setErrorHandler()`, modified `connect()`, modified `execute()` (cacheHit, invalidation, PSR-3) | v0.7.0, v0.8.0, v0.9.0 |
| `RequestAbstraction` | `$partialInclude`, modified `makeCacheKey()` | v0.9.0 |
| `RequestResponse` | `$cacheHit` property | v0.7.0 |
| `AbstractEntity` | Modified `cleanupForRequest()` (partial/nested include parsing) | v0.9.0 |

### New/Modified Collection Classes (Phase 3, v0.9.0)

| Collection | Modification |
|-----------|-------------|
| `FileCollection` | Update `validateFetch()` to check fluent whereConditions |
| `BookingCollection` | Same — merge fluent conditions |
| `InvoiceItemCollection` | Same — merge fluent conditions |
| `EstimateItemCollection` | Same — merge fluent conditions |

---

## 12. State Management

### Paymo Connection State

```php
// Added (Phase 2):
private ?PsrLogAdapter $psrLogger = null;
```

### Cache Singleton State

```php
// Added (Phase 3):
public $clear_callback = null;
```

### Configuration Singleton State

```php
// Phase 2: Noodlehaus\Config → Adbar\Dot
private Dot $config;
```

### Error Singleton State

```php
// Added (Phase 2):
private ?callable $customHandler = null;
```

### AbstractResource Static State

```php
// Added (Phase 1):
private static array $validatedClasses = [];
```

---

## 13. Database Changes

Not applicable. This is a client SDK library — no database schema is managed by this package.

---

## 14. File Change Inventory

### Summary

| Action | Count |
|--------|-------|
| CREATE | 6 source + ~18 test = **24 files** |
| MODIFY | 17 source + 5 docs + 2 config = **24 files** |
| DELETE | 0 files |
| **Total** | **48 files** |

Phase 4 additionally modifies up to 38 resource classes (FR-004) and produces 13 investigation artifacts (FR-040–FR-052).

### Phase 1: v0.7.0 Files

| # | File | Action | FRs | Key Changes |
|---|------|--------|-----|-------------|
| 1 | `composer.json` | MODIFY | FR-003, FR-027 | Fix autoload-dev namespace; add scripts section |
| 2 | `src/Entity/EntityMap.php` | MODIFY | FR-001 | Line 260: `"AbstractResourcce"` → `AbstractResource::class` |
| 3 | `src/Paymo.php` | MODIFY | FR-002, FR-019 | Remove hardcoded `define()`; replace `PAYMO_DEVELOPMENT_MODE` with `Configuration::get('devMode')`; add `$response->cacheHit = true` in cache path |
| 4 | `src/Utility/Log.php` | MODIFY | FR-002 | Replace `PAYMO_DEVELOPMENT_MODE` with `Configuration::get('devMode')` |
| 5 | `src/Utility/RequestResponse.php` | MODIFY | FR-019 | Add `public bool $cacheHit = false;` |
| 6 | `src/Entity/AbstractResource.php` | MODIFY | FR-009, FR-011, FR-033 | Add `WRITEONLY=[]`; add `validateConstants()`; add `toArray()`, `toJson()` |
| 7 | `src/Entity/AbstractCollection.php` | MODIFY | FR-032, FR-033 | Modify `flatten()` for string pluck; add `toArray()`, `toJson()` |
| 8 | `src/Utility/Converter.php` | MODIFY | FR-016 | Add `in(me)` handling in `convertOperatorValue()` |
| 9 | `src/Configuration.php` | MODIFY | FR-024 | Add `static reset(): void` |
| 10 | `src/Entity/Resource/Expense.php` | MODIFY | FR-008 | Add `download_token` to PROP_TYPES/READONLY |
| 11 | `src/Entity/Resource/Report.php` | MODIFY | FR-008 | Add `download_token` to PROP_TYPES/READONLY |
| 12 | `default.paymoapi.config.json` | MODIFY | FR-002 | Verify/add `"devMode": false`; add `"includes": {"maxDepth": 2}` |
| 13 | `OVERRIDES.md` | MODIFY | FR-034–038 | 5 new override entries (014–018) |
| 14 | `tests/ResourceTests/EntityMapTest.php` | CREATE | FR-001 | Overload validation tests |
| 15 | `tests/UtilityTests/DevModeTest.php` | CREATE | FR-002 | devMode config reading tests |
| 16 | `tests/UtilityTests/ConfigurationTest.php` | CREATE | FR-024 | reset() singleton destruction tests |
| 17 | `tests/UtilityTests/RequestResponseTest.php` | CREATE | FR-019 | cacheHit property tests |
| 18 | `tests/UtilityTests/ConverterTest.php` | CREATE | FR-016 | in(me) filter tests |
| 19 | `tests/CollectionTests/FlattenPluckTest.php` | CREATE | FR-032 | flatten(string) pluck tests |
| 20 | `tests/ResourceTests/ConvenienceMethodsTest.php` | CREATE | FR-033 | toArray()/toJson() tests |
| 21 | `tests/ResourceTests/ConstantValidationTest.php` | CREATE | FR-011 | validateConstants() tests |

**Verification items** (no file changes): FR-006 (Session.php `id` type), FR-007 (Invoice/Estimate `language` READONLY).

### Phase 2: v0.8.0 Files

| # | File | Action | FRs | Key Changes |
|---|------|--------|-----|-------------|
| 22 | `composer.json` | MODIFY | FR-022, FR-023, FR-021 | PHP `>=8.1`; remove hassankhan/config; add `psr/log` |
| 23 | `src/Configuration.php` | MODIFY | FR-023 | Full rewrite: Adbar\Dot replaces Noodlehaus\Config |
| 24 | `src/Utility/ErrorSeverity.php` | CREATE | FR-020 | PHP 8.1 backed enum |
| 25 | `src/Utility/PsrLogAdapter.php` | CREATE | FR-021 | PSR-3 bridge class |
| 26 | `src/Utility/Error.php` | MODIFY | FR-020 | Union type on `throw()`; `setCustomHandler()` |
| 27 | `src/Paymo.php` | MODIFY | FR-021, FR-020 | `setLogger()`/`getLogger()`; `setErrorHandler()`; PSR-3 in `execute()` |
| 28 | `tests/UtilityTests/ErrorSeverityTest.php` | CREATE | FR-020 | Enum value and HTTP mapping tests |
| 29 | `tests/UtilityTests/PsrLogAdapterTest.php` | CREATE | FR-021 | Severity-to-PSR-3 mapping tests |
| 30 | `tests/UtilityTests/ConfigurationRewriteTest.php` | CREATE | FR-023 | Rewritten Configuration behavior tests |

### Phase 3: v0.9.0 Files

| # | File | Action | FRs | Key Changes |
|---|------|--------|-----|-------------|
| 31 | `src/Utility/PaymoDateTime.php` | CREATE | FR-005 | DateTimeImmutable subclass |
| 32 | `src/Utility/Converter.php` | MODIFY | FR-005 | `convertToPhpValue()`, `convertForRequest()`, helpers |
| 33 | `src/Entity/AbstractResource.php` | MODIFY | FR-005 | `__set()` coercion; `create()`/`update()` serialization |
| 34 | `src/Entity/AbstractCollection.php` | MODIFY | FR-012 | `FETCHALL_CAP`; `fetchAll()` |
| 35 | `src/Cache/Cache.php` | MODIFY | FR-017, FR-018 | `$clear_callback`; `invalidateByResource()`; `clearAll()`; modified `registerCacheMethods()` |
| 36 | `src/Utility/RequestAbstraction.php` | MODIFY | FR-014, FR-017 | `$partialInclude`; modified `makeCacheKey()` |
| 37 | `src/Entity/AbstractEntity.php` | MODIFY | FR-014, FR-015 | Partial/nested include parsing in `cleanupForRequest()` |
| 38 | `src/Paymo.php` | MODIFY | FR-017, FR-014 | Cache invalidation after mutations; `partial_include` query param |
| 39 | `src/Entity/Collection/FileCollection.php` | MODIFY | FR-013 | `validateFetch()` merges fluent conditions |
| 40 | `src/Entity/Collection/BookingCollection.php` | MODIFY | FR-013 | Same |
| 41 | `src/Entity/Collection/InvoiceItemCollection.php` | MODIFY | FR-013 | Same |
| 42 | `src/Entity/Collection/EstimateItemCollection.php` | MODIFY | FR-013 | Same |
| 43 | `tests/UtilityTests/PaymoDateTimeTest.php` | CREATE | FR-005 | instanceof, toString, JSON tests |
| 44 | `tests/UtilityTests/ConverterCoercionTest.php` | CREATE | FR-005 | All type coercion round-trip tests |
| 45 | `tests/ResourceTests/TypeCoercionTest.php` | CREATE | FR-005 | End-to-end resource coercion |
| 46 | `tests/CollectionTests/FetchAllTest.php` | CREATE | FR-012 | Pagination and cap tests |
| 47 | `tests/CacheTests/InvalidationTest.php` | CREATE | FR-017 | Post-mutation invalidation tests |
| 48 | `tests/CacheTests/CacheCallbackTest.php` | CREATE | FR-018 | 2/3 callback registration tests |
| 49 | `tests/CollectionTests/ParentFilterTest.php` | CREATE | FR-013 | Parent filter enforcement tests |
| 50 | `tests/RequestTests/PartialIncludeTest.php` | CREATE | FR-014 | Partial include syntax tests |
| 51 | `tests/RequestTests/NestedIncludeTest.php` | CREATE | FR-015 | Nested include depth tests |

### Phase 4: v1.0.0-alpha Files

| # | File | Action | FRs | Key Changes |
|---|------|--------|-----|-------------|
| 52-89 | All 38 `src/Entity/Resource/*.php` | MODIFY | FR-004 | Add undocumented properties (depends on FR-040/FR-046) |
| 90 | `CLAUDE.md` | MODIFY | FR-039 | All v1.0.0-alpha changes |
| 91 | `PACKAGE-DEV.md` | MODIFY | FR-039 | Architecture/class updates |
| 92 | `README.md` | MODIFY | FR-039 | User-facing docs |
| 93 | `CHANGELOG.md` | MODIFY | FR-039 | Version history entries |
| 94 | `OVERRIDES.md` | MODIFY | FR-004, FR-039 | Investigation-discovered overrides |

**Investigation artifacts** (FR-040 through FR-052): 13 markdown files produced by live API testing. Not part of the SDK package.

---

## 15. Implementation Roadmap

### Phase 1: v0.7.0 — Bug Fixes & Backward-Compatible Additions

**PHP Requirement:** `>=7.4` (unchanged)
**Goal:** Fix all confirmed bugs, add structural foundations, deliver low-risk additions. Any PHP 7.4 consumer upgrades to v0.7.0 with zero code changes.

**Implementation Order:**

```
Parallel group (no interdependencies):
  1.  composer.json           (FR-003, FR-027)
  2.  EntityMap.php           (FR-001)
  3.  RequestResponse.php     (FR-019)
  4.  Configuration.php       (FR-024)
  5.  Converter.php           (FR-016)
  6.  Expense.php             (FR-008)
  7.  Report.php              (FR-008)
  8.  default.paymoapi.config.json (FR-002)

Sequential (dependencies):
  9.  Paymo.php               (FR-002, FR-019)    → depends on #3, #8
  10. Log.php                 (FR-002)             → depends on #9
  11. AbstractResource.php    (FR-009, FR-011, FR-033) → depends on #9
  12. AbstractCollection.php  (FR-032, FR-033)     → independent

Documentation:
  13. OVERRIDES.md            (FR-034–FR-038)

Verification:
  14. Session.php, Invoice.php, Estimate.php (FR-006, FR-007)

Test files:
  15-21. All Phase 1 test files
```

**Completion Criteria:**
- All 3 bugs fixed (FR-001, FR-002, FR-003)
- `composer test` runs via CLI
- `Configuration::reset()` destroys singleton
- `$response->cacheHit` works correctly
- `flatten('name')` plucks values
- `toArray()`/`toJson()` work on resources and collections
- `Task::where('user_id', 'me', 'in')` → `user_id in (me)`
- OVERRIDES.md has entries 014–018
- Full regression suite passes

**Testable after this phase:** Bug fixes, devMode config, cacheHit tracking, convenience methods, in(me) filters, Configuration reset, constant validation.

**Tag:** `v0.7.0`

---

### Phase 2: v0.8.0 — PHP 8.1 + Dependency Modernization

**PHP Requirement:** `>=8.1`
**Goal:** Modernize foundation. Sole "environment-breaking" boundary. PHP 7.4 consumers stay on v0.7.x.

**Implementation Order:**

```
Foundation:
  22. composer.json            (FR-022, FR-023, FR-021)

Parallel group (after composer.json):
  23. ErrorSeverity.php        (FR-020)
  24. PsrLogAdapter.php        (FR-021)
  25. Configuration.php        (FR-023)

Sequential (dependencies):
  26. Error.php                (FR-020)    → depends on #23
  27. Paymo.php                (FR-021, FR-020) → depends on #24, #26

Test files:
  28-30. Phase 2 test files
```

**Completion Criteria:**
- `composer.json` requires `php >= 8.1`
- `hassankhan/config` absent from `composer.json` and `composer.lock`
- `psr/log` present
- `ErrorSeverity::fromHttpStatus(429)` → WARN
- `ErrorSeverity::fromHttpStatus(401)` → FATAL
- PSR-3 logger injection works
- `Configuration::get('connection.url')` returns expected value after rewrite
- Full regression suite passes

**Testable after this phase:** ErrorSeverity enum, PSR-3 logging, Configuration rewrite, PHP 8.1 features.

**Tag:** `v0.8.0`

---

### Phase 3: v0.9.0 — Core Feature Development

**PHP Requirement:** `>=8.1`
**Goal:** Deliver all major new features on the modernized foundation.

**Implementation Order:**

```
Parallel (no dependencies):
  31. PaymoDateTime.php        (FR-005)
  32. RequestAbstraction.php   (FR-014, FR-017)

Parallel (first-level dependencies):
  33. Converter.php            (FR-005)    → depends on #31
  34. Cache.php                (FR-017, FR-018) → depends on #32

Parallel (second-level):
  35. AbstractResource.php     (FR-005)    → depends on #33
  36. AbstractEntity.php       (FR-014, FR-015) → depends on #32

Third-level:
  37. AbstractCollection.php   (FR-012)    → depends on #35
  38. Paymo.php                (FR-017, FR-014) → depends on #34, #32

Independent parallel:
  39-42. Collection classes    (FR-013)

Test files:
  43-51. Phase 3 test files
```

**Completion Criteria:**
- `$project->created_on` returns PaymoDateTime after fetch
- `(string)$project->created_on` returns original API string
- `json_encode($project->flatten())` produces string for datetimes
- `Invoice::list()->fetchAll()` returns all pages; stops at cap
- `File::list()->fetch()` without `project_id` throws in devMode
- Cache invalidated after mutations
- `registerCacheMethods($f, $s, $c)` and `registerCacheMethods($f, $s)` both work
- `fetch($id, ['tasks(id,name)'])` → `partial_include=tasks(id,name)`
- `fetch($id, ['tasks.entries'])` → `include=tasks.entries`
- Full regression suite passes

**Testable after this phase:** Type coercion, fetchAll, cache invalidation, partial includes, nested includes, parent filter enforcement.

**Tag:** `v0.9.0`

---

### Phase 4: v1.0.0-alpha — Investigations, Properties & Final Polish

**PHP Requirement:** `>=8.1`
**Goal:** Complete API surface coverage via live investigation, add undocumented properties, finalize documentation.

**Implementation Order:**

```
Investigation scripts (FR-040–FR-052) → requires live API access
All 38 Resource/*.php  (FR-004) → depends on investigation results
CLAUDE.md, PACKAGE-DEV.md, README.md, CHANGELOG.md (FR-039)
OVERRIDES.md (FR-004, FR-039) → depends on investigations
```

**Completion Criteria:**
- 13 investigation FRs produce artifacts
- Undocumented properties added to resource classes
- All documentation updated
- Full test suite passes
- `composer validate` passes
- `composer dump-autoload` succeeds

**Tag:** `v1.0.0-alpha`

---

## 16. Edge Cases & Error Handling

### Edge Cases

| # | Edge Case | Expected Behavior |
|---|-----------|-------------------|
| EC-001 | `fetchAll()` called on resource with 0 records | Returns empty collection. `count()` → 0. No pages fetched beyond the first empty response. |
| EC-002 | `fetchAll()` exceeds safety cap mid-page | Stops accumulating. Returns accumulated records up to the cap. Does NOT discard the partial last page — includes all records from it up to the cap. |
| EC-003 | `convertToPhpValue()` receives already-typed value (e.g., PaymoDateTime) | Returns the value unchanged. Guard: `if ($value instanceof PaymoDateTime) return $value;` |
| EC-004 | `set('created_on', null)` on datetime property | `convertToPhpValue(null, 'datetime')` returns `null`. Null values pass through without coercion. |
| EC-005 | `flatten('nonexistent_property')` on collection | Accessing `$resource->nonexistent_property` returns `null` for each item. Returns array of `null` values. No exception. |
| EC-006 | Cache invalidation when caching is disabled | No-op. The `if ($this->useCache && ...)` guard prevents invalidation calls. |
| EC-007 | Cache invalidation with custom backend but no `$clear` callback | `invalidateByResource()` checks `$clear_callback !== null`. If null and using custom backend, invalidation is a no-op (cached data expires via TTL). |
| EC-008 | `registerCacheMethods(null, null, $clear)` — clear callback with null fetch/store | Validation unchanged: both fetch and store must be null or both non-null. Clear callback is independent. This sets clear callback but disables custom cache backend. |
| EC-009 | Partial include with invalid sub-field in devMode | Throws `RuntimeException`: "Sub-field '{field}' not found in {IncludedResource} PROP_TYPES". In production mode: passed through to API (API may return 400). |
| EC-010 | Nested include exceeding maxDepth in devMode | Throws `RuntimeException`: "Nested include '{inc}' exceeds maximum depth of {max}." In production mode: passed through to API. |
| EC-011 | `in(me)` combined with array values | `in` with `'me'` string or `['me']` array triggers special handling. `in` with `['me', 123]` falls through to normal array handling: `prop in (me,123)`. |
| EC-012 | `Configuration::reset()` called when no Configuration instance exists | No-op. `self::$instance = null` is safe when already null. |
| EC-013 | PaymoDateTime with malformed date string | `parent::__construct()` throws `\Exception` (DateTimeImmutable behavior). The SDK does not catch this — API responses should always contain valid dates. |
| EC-014 | `validateConstants()` on resource with compound REQUIRED_CREATE (contains `\|` or `&`) | The compound requirement is skipped during READONLY intersection check (via `preg_match('/[|&]/', $req)`). |
| EC-015 | ErrorSeverity::from() with invalid string | Throws `ValueError` (PHP 8.1 backed enum behavior). Only valid values: `'notice'`, `'warn'`, `'fatal'`. |
| EC-016 | PSR-3 logger throws exception during logging | Exception propagates. The SDK does not catch logger exceptions — the consumer's logger is responsible for its own error handling. |
| EC-017 | `fetchAll()` with explicit `limit()` set | `fetchAll()` overrides pagination state internally. Any prior `limit()` call is replaced during iteration and cleared after completion. |
| EC-018 | Old-format cache files (`paymoapi-{md5}`) after cache key format change | Orphaned files never match the new `paymoapi-{resource}-{md5}` pattern. They expire via TTL. No migration action required. |

### Error Handling Strategy

**SDK Layer (Utility/Error.php):**
- Routes through `Error::throw($severity, $error, $code, $message)`
- Three severity levels: NOTICE (log only), WARN (log + optional handler), FATAL (log + throw RuntimeException)
- Custom handler receives `ErrorSeverity` enum instance, message, error data, code
- Default behavior preserved: FATAL throws RuntimeException after handler chain

**Connection Layer (Paymo.php):**
- HTTP errors classified by `ErrorSeverity::fromHttpStatus($statusCode)`
- 401/403 → FATAL (authentication/authorization failure)
- 429 → WARN (rate limited — built-in retry handles this)
- 400/404/422 → NOTICE (client error — bad request, not found, validation)
- 500+ → FATAL (server error)
- Guzzle `ConnectException` → FATAL with message "Connection failed: {url}"

**Type Coercion Layer (Converter.php):**
- Null values → null (no conversion attempted)
- Already-typed values → returned unchanged
- Malformed datetime strings → DateTimeImmutable constructor throws `\Exception`
- Type mismatches during hydration → best-effort cast (PHP's native type casting)

**Cache Layer (Cache.php):**
- Cache read failure → treated as cache miss (fetch from API)
- Cache write failure → silently ignored (data was already returned to consumer)
- Cache invalidation failure (glob returns false) → silently ignored

**Validation Layer (AbstractResource.validateConstants()):**
- Only fires in devMode
- Throws `RuntimeException` with specific message identifying the conflicting property and constants
- Cached per class — fires once per class per process

---

## 17. Security Considerations

### API Key Protection

- API keys are stored in the `Paymo::$connections` array keyed by the key string itself. This is existing behavior and unchanged.
- The `devMode` fix (FR-002) is a **security improvement**: when `devMode` is `false` (the new default), error details are suppressed from stdout. Previously, the hardcoded `true` leaked request details, API URLs, and error context to stdout in production.

### Sensitive Data in Company Resource

- FR-038 documents that the Company resource returns sensitive properties: Stripe keys, Authorize.net credentials, SMTP configuration.
- These properties are added to PROP_TYPES with `// SENSITIVE` annotation comments.
- No runtime redaction is implemented — the API returns these to any authenticated user. This is a Paymo API design decision, not an SDK responsibility.
- PSR-3 logger (FR-021) logs API requests and responses at DEBUG level. If a consumer's logger writes to a shared log file, Company responses containing credentials will be logged. **Recommendation to consumers:** configure logging to exclude Company resource responses or redact sensitive fields.

### Input Validation

- Type coercion (FR-005) provides implicit input validation: setting a datetime property to a non-date string causes `PaymoDateTime::__construct()` to throw `\Exception`.
- `validateConstants()` (FR-011) catches misconfured resource classes at instantiation time in devMode.
- Partial include field validation in devMode (FR-014) prevents requesting non-existent fields.
- Parent filter enforcement (FR-013) prevents unbounded collection fetches.

### No New Attack Surface

- The SDK is a server-side PHP library. It does not serve HTTP requests, render HTML, or accept user input directly.
- All HTTP communication is outbound (SDK → Paymo API) via Guzzle over HTTPS.
- No XSS, CSRF, or SQL injection concerns — the SDK does not produce HTML output or interact with databases.
- The `in(me)` filter (FR-016) passes the literal string `me` to the API. No injection risk — the Paymo API interprets `me` as a keyword.

---

## 18. Performance Considerations

### Type Coercion Overhead

- **Target:** <1ms per resource hydration (30 properties) per NFR-004.
- `Converter::convertToPhpValue()` uses a `match` expression (O(1) dispatch). For each property: one `explode()` call, one `match`, one type cast.
- PaymoDateTime constructor: one `parent::__construct()` call (existing DateTimeImmutable parsing) + one string assignment.
- Boolean hydration: 3 `is_*()` checks + one `in_array()` call.
- No external I/O during conversion.

### fetchAll() Memory

- **Target:** Accumulated results + one current page per NFR-004.
- Each page is fetched via `$this->fetch()`, which replaces `$this->data`. After each page, `$accumulated + $currentPage` merge happens, then `$currentPage` is discarded on next loop iteration.
- Peak memory during pagination: all accumulated records (growing) + one page of raw API response + one page of hydrated entities.
- Default page size: 200 records. Configurable via `$options['pageSize']` (max 500).

### Cache Key Format Change

- New format `paymoapi-{resourceBase}-{md5}` adds ~10-15 bytes to key length. Negligible storage impact.
- `glob()` for invalidation is bounded: Paymo accounts typically have <100 cached files per resource type. Even 1,000 cached files per type: `glob()` returns in <1ms on modern filesystems.
- Orphaned old-format cache files expire via TTL. No performance impact during transition.

### Caching Strategy

- Cache invalidation is resource-scoped, not full-wipe. `invalidateByResource('invoices')` only removes invoice cache entries.
- Invalidation fires once per mutation request, not per entity. A single `create()` call invalidates the resource type once.
- No invalidation for GET requests (unchanged).

### Rate Limiting

- Existing rate limiter (`rateLimit.minDelayMs: 200ms`) is unchanged.
- `fetchAll()` respects rate limiting on each page fetch (each page is a separate `Paymo::execute()` call).
- For a 5,000-record fetchAll at 200 records/page and 200ms minimum delay: 25 pages × 200ms = 5 seconds minimum. Actual time depends on API response latency.

---

## 19. Testing Strategy

### Test Framework

The SDK uses a custom test framework (not PHPUnit). Tests extend `ResourceTest` base class and run via `php tests/validate`. All tests require live API access — no mocking.

### New Test Directory Structure

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

The test runner (`tests/validate`) must be updated to discover tests in new subdirectories.

### Test Coverage per Phase

**Phase 1 (8 new test files):**
- EntityMap overload validation works after typo fix
- devMode reads from config, not hardcoded constant
- Configuration::reset() destroys singleton
- RequestResponse cacheHit tracking
- in(me) filter generation
- Collection flatten(string) pluck
- toArray()/toJson() on resources and collections
- validateConstants() catches cross-referencing conflicts

**Phase 2 (3 new test files):**
- ErrorSeverity enum values match existing strings
- fromHttpStatus() mapping: 429→WARN, 401→FATAL, 500→FATAL, 400→NOTICE
- PSR-3 adapter maps severity correctly
- Rewritten Configuration behavior identical

**Phase 3 (9 new test files):**
- PaymoDateTime instanceof, toString, JSON serialization, date arithmetic
- Converter coercion round-trips for all PROP_TYPES
- End-to-end type coercion through resource lifecycle
- fetchAll() pagination, safety cap, state cleanup
- Cache invalidation after CRUD mutations
- registerCacheMethods() backward compatibility
- Parent filter enforcement on File, Booking, InvoiceItem, EstimateItem
- Partial include syntax parsing and query generation
- Nested include depth validation

### Regression Testing

Every phase: run `php tests/validate` (full existing test suite) to confirm zero regressions.

### Build Checks (All Phases)

```bash
find src/ -name "*.php" -exec php -l {} \;    # PHP syntax check
composer validate --strict                      # Composer validation
composer dump-autoload --no-dev                 # Verify no test classes in production
composer install --no-dev                       # Verify hassankhan/config absent (Phase 2+)
```

---

## 20. Dependencies

### Added

| Package | Version | Justification | Phase |
|---------|---------|---------------|-------|
| `psr/log` | `^2.0 \|\| ^3.0` | PSR-3 LoggerInterface for FR-021. Interface-only, zero runtime overhead. | v0.8.0 |

### Removed

| Package | Version | Justification | Phase |
|---------|---------|---------------|-------|
| `hassankhan/config` | `^3.2` | Replaced by direct Adbar\Dot. Last maintained 2021. Wraps Adbar\Dot with unused YAML/XML/INI. | v0.8.0 |

### Updated

| Package | Current | Target | Justification | Phase |
|---------|---------|--------|---------------|-------|
| `php` | `>=7.4` | `>=8.1` | EOL since 2022. Enables enums, union types, readonly. | v0.8.0 |

### Unchanged

| Package | Version | Reason |
|---------|---------|--------|
| `guzzlehttp/guzzle` | `^7.8` | No changes needed. Supports PHP 8.1+. |
| `adbario/php-dot-notation` | `^3.3` | Already present. Now used directly instead of through hassankhan/config. |
| `ext-json` | `*` | Still required for JSON config parsing. |

### Target composer.json require (Phase 2+)

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

---

## 21. Configuration & Environment

### default.paymoapi.config.json Changes

| Key | Phase | Current | Target | Purpose |
|-----|-------|---------|--------|---------|
| `devMode` | v0.7.0 | May be missing | `false` (explicit) | FR-002: devMode reads from config |
| `includes.maxDepth` | v0.7.0 | N/A | `2` | FR-015: nested include depth validation |

### composer.json Changes Summary

| Phase | Changes |
|-------|---------|
| v0.7.0 | Fix autoload-dev namespace `Jcolombo\\PaymoApiPhp\\Tests\\` → `tests/`; add scripts section |
| v0.8.0 | PHP `>=8.1`; remove hassankhan/config; add psr/log |

### No New Environment Variables

All configuration via `paymoapi.config.json` or `default.paymoapi.config.json`.

### No Feature Flags

New features are always-on once the containing version is installed. devMode-gated features use the existing `devMode` config key.

---

## 22. Migration & Rollback Plan

### Deployment Strategy

The four-version progression is the migration strategy:

| Version | Requires | Safe to deploy? | Rollback target |
|---------|----------|-----------------|-----------------|
| v0.7.0 | PHP >=7.4 | Yes — bug fixes + additive methods only | v0.6.1 |
| v0.8.0 | PHP >=8.1 | Yes — if PHP 8.1 available. Otherwise stay on v0.7.x | v0.7.0 |
| v0.9.0 | PHP >=8.1 | Yes — core features, tested | v0.8.0 |
| v1.0.0-alpha | PHP >=8.1 | Yes — investigations + properties | v0.9.0 |

### Upgrade Path for Consumers

1. Update `composer.json`: `"jcolombo/paymo-api-php": "^0.7.0"` (or specific version).
2. Run `composer update jcolombo/paymo-api-php`.
3. For v0.8.0+: ensure PHP >= 8.1 is installed.
4. Run existing test suite. All tests should pass.
5. Optionally adopt new features (fetchAll, type coercion, PSR-3 logging).

### Rollback Procedure

1. Update `composer.json` to pin the rollback version: `"jcolombo/paymo-api-php": "0.7.0"`.
2. Run `composer update jcolombo/paymo-api-php`.
3. Remove any code using new-version features (fetchAll, PaymoDateTime access, ErrorSeverity, etc.).
4. Run test suite to confirm.

### Cache Migration

- v0.9.0 changes cache key format from `paymoapi-{md5}` to `paymoapi-{resourceBase}-{md5}`.
- Old cache files become orphaned — they are never read by the new key format.
- Old files expire naturally via TTL (default configurable via `Cache::lifespan()`).
- No manual cache clearing required. For immediate cleanup: `rm /path/to/cache/paymoapi-*` (safe).
- Rolling back to v0.8.0: old-format cache files are expired by then. New-format files are harmless (never matched by old-format reader). No action needed.

### Type Coercion Compatibility

- v0.9.0 changes datetime properties from raw strings to PaymoDateTime objects.
- **Works automatically:** String concatenation, `echo`, loose equality (`==`), JSON encoding.
- **Breaks:** Strict equality (`=== 'string'`). This is a documented behavior change.
- **Rollback consideration:** If consumers rely on `===` with datetime strings, they must either update code or stay on v0.8.0.

---

## 23. Assumptions & Decisions Log

| ID | Assumption/Decision | Rationale |
|----|-------------------|-----------|
| A-001 | PHP 8.1 bump is acceptable as the single breaking environment change | PHP 7.4 EOL since Nov 2022. Both peer SDKs require >=8.1. |
| A-002 | Existing Converter.php extended (not replaced) for three-direction coercion | Keeps all type logic co-located. Existing methods unchanged. |
| A-003 | Cache invalidation by URL pattern is sufficient | Simple, covers all cases, survives process restarts, no index needed. |
| A-004 | fetchAll() safety cap of 5,000 records is appropriate | Large enough for typical use. Per-resource override via FETCHALL_CAP. |
| A-005 | PSR-3 logging is additive, not a replacement | Backward compatibility. Existing Log utility consumers unaffected. |
| A-006 | Investigation items (FR-040–052) executed in separate session with API access | Require live API credentials. Separating from implementation prevents blocking. |
| A-007 | 2,500-item include truncation threshold is consistent across resources | No evidence of per-resource variation. Can be made per-resource if discovered. |
| A-008 | Version numbering: 0.7.0 → 0.8.0 → 0.9.0 → 1.0.0-alpha | User requires intermediate rollback points. Minor bumps provide this. |
| A-009 | WRITEONLY constant may be implemented but unused | Structural parity with peer SDKs. Zero cost when empty. |
| A-010 | Backward compatibility = existing code works without modification | Exceptions: (1) PHP 8.1 requirement, (2) datetime properties now return objects with `__toString()`. |
| A-011 | Type coercion backward compatibility via `__toString()` | String contexts (echo, concatenation, `==`) work. `===` with strings breaks — documented. |
| AD-001 | PHP 8.1 minimum at v0.8.0 boundary | Clean upgrade path. v0.7.x for PHP 7.4 consumers. |
| AD-002 | Extend Converter class (not new TypeCoercion class) | Co-locates all type logic. |
| AD-003 | Cache key prefix `paymoapi-{resourceBase}-{md5}` | Enables glob-based invalidation without index. |
| AD-004 | ErrorSeverity as PHP 8.1 backed enum | Type safety. Backed values match existing strings. |
| AD-005 | PSR-3 as optional adapter (not Log replacement) | Both systems coexist. |
| AD-006 | Configuration::reset() destroys singleton | Simpler and more thorough than reload. |
| AD-007 | Replace hassankhan/config with direct Adbar\Dot | Remove stale dependency. SDK only uses JSON configs. |
| AD-008 | PaymoDateTime extends DateTimeImmutable | `instanceof` works. `__toString()` preserves string contexts. |
| AD-009 | fetchAll() iterative page merge | Satisfies NFR-004 memory requirement. |
| AD-010 | Partial includes via separate `partial_include` param | Matches Paymo API parameter design. |
| AD-011 | Nested includes max depth 2 (configurable) | 2-level confirmed. 3+ untested. |
| AD-012 | Four-phase delivery | Each version = tagged rollback point. |

---

## 24. Verification Checklist

This ordered checklist maps to success criteria. An implementation agent should verify each item after completing the build.

### After Phase 1 (v0.7.0)

- [ ] `EntityMap::overload()` with invalid class throws RuntimeException (SC-002)
- [ ] `PAYMO_DEVELOPMENT_MODE` constant no longer exists; devMode reads from config (SC-003)
- [ ] `Configuration::get('devMode')` returns `false` by default
- [ ] Error details suppressed when `devMode` is `false`; shown when `true` (SC-003)
- [ ] `composer dump-autoload --no-dev` excludes test classes (SC-004)
- [ ] `composer test` executes `php tests/validate`
- [ ] `$response->cacheHit` is `true` from cache, `false` from API (SC-013)
- [ ] `Task::where('user_id', 'me', 'in')` generates `user_id in (me)`
- [ ] `$collection->flatten('name')` returns `['Name A', 'Name B', ...]`
- [ ] `$resource->toArray()` returns array; `$resource->toJson()` returns valid JSON string
- [ ] `$collection->toArray()` returns sequential array; `$collection->toJson()` returns valid JSON
- [ ] In devMode: READONLY property in REQUIRED_CREATE throws descriptive exception (SC-015)
- [ ] Session.php `id` type is `text` in PROP_TYPES (FR-006 verification)
- [ ] Invoice.php and Estimate.php `language` in READONLY (FR-007 verification)
- [ ] OVERRIDES.md has entries OVERRIDE-014 through OVERRIDE-018
- [ ] Full regression suite: `php tests/validate` passes

### After Phase 2 (v0.8.0)

- [ ] `composer.json` requires `php >= 8.1`
- [ ] `hassankhan/config` absent from `composer.json` and `composer.lock` (SC-011)
- [ ] `psr/log` present in `composer.json`
- [ ] `ErrorSeverity::fromHttpStatus(429)` returns `ErrorSeverity::WARN` (SC-008)
- [ ] `ErrorSeverity::fromHttpStatus(401)` returns `ErrorSeverity::FATAL` (SC-008)
- [ ] `ErrorSeverity::FATAL->value === 'fatal'`
- [ ] PSR-3 logger injected via `$connection->setLogger($monolog)` receives log entries (SC-014)
- [ ] `Paymo::connect(logger: $logger)` works (named argument)
- [ ] `Configuration::get('connection.url')` returns expected value after rewrite
- [ ] `Configuration::reset()` still works after rewrite (SC-009)
- [ ] Full regression suite passes

### After Phase 3 (v0.9.0)

- [ ] `$project->created_on instanceof \DateTimeImmutable` → `true` (SC-005)
- [ ] `(string)$project->created_on` → original ISO 8601 string (SC-005)
- [ ] `json_encode($project->flatten())` → datetime fields as strings (SC-005)
- [ ] `$project->set('due_date', new \DateTimeImmutable('2024-06-15'))` → serializes to `'2024-06-15'`
- [ ] Boolean properties are native `bool` after hydration
- [ ] After `Invoice::new()->set([...])->create()`, `Invoice::list()->fetch()` includes new invoice (SC-006)
- [ ] `Invoice::list()->fetchAll()` returns all pages (SC-007)
- [ ] `fetchAll()` stops at FETCHALL_CAP
- [ ] `fetchAll()` pagination state cleared after completion
- [ ] `registerCacheMethods($f, $s)` works (2 callbacks) (SC-012)
- [ ] `registerCacheMethods($f, $s, $c)` works (3 callbacks) (SC-012)
- [ ] `File::list()->fetch()` without `project_id` throws in devMode
- [ ] `File::list()->where(File::where('project_id', $id))->fetch()` passes validation
- [ ] `fetch($id, ['tasks(id,name)'])` → `partial_include=tasks(id,name)` in request
- [ ] `fetch($id, ['tasks.entries'])` → `include=tasks.entries` in request
- [ ] 3-level nested include throws in devMode with default maxDepth=2
- [ ] Full regression suite passes

### After Phase 4 (v1.0.0-alpha)

- [ ] 13 investigation FRs (FR-040–052) have artifact files
- [ ] Undocumented properties added per FR-040/FR-046 results
- [ ] CLAUDE.md documents all new methods, constants, classes, behaviors
- [ ] PACKAGE-DEV.md updated with architecture changes
- [ ] README.md updated with PHP 8.1 requirement and new features
- [ ] CHANGELOG.md has entries for v0.7.0, v0.8.0, v0.9.0, v1.0.0-alpha
- [ ] `composer validate --strict` passes
- [ ] A PHP application using v0.6.1 can upgrade with zero code changes (SC-001)
- [ ] Full regression suite passes

---

## 25. Monitoring & Observability

### PSR-3 Logging Integration

With FR-021, consumers can inject any PSR-3 logger to capture SDK activity:

```php
$connection = Paymo::connect('API_KEY', logger: $monologInstance);
```

**Log entries produced:**
- DEBUG: Every API request (method, URL, data)
- DEBUG: Every API response (status code, URL, response time, cache indicator)
- NOTICE: SDK notices (cache misses, parent filter warnings in production)
- WARNING: Rate limiting (429 responses), cache truncation warnings
- ERROR: Authentication failures, server errors, fatal SDK errors

### Include Truncation Warning

FR-034 adds a devMode warning when an included collection returns exactly 2,500 items:

```
"Included collection 'tasks' returned exactly 2,500 items — results may be truncated.
 Use direct list() with pagination for complete data."
```

This warning is logged at WARN severity through both the existing `Log` utility and PSR-3 adapter (if configured).

---

## 26. Documentation Updates

### Files to Update (Phase 4, FR-039)

| File | Updates Required |
|------|-----------------|
| `CLAUDE.md` | New methods (fetchAll, toArray, toJson, flatten(string), Configuration::reset, setLogger, setErrorHandler), new constants (WRITEONLY, FETCHALL_CAP), new classes (ErrorSeverity, PaymoDateTime, PsrLogAdapter), PHP 8.1 requirement, dependency changes, type coercion behavior, cache invalidation, partial/nested include syntax, in(me) filter, cacheHit property |
| `PACKAGE-DEV.md` | New class inventory, architecture diagram update, new constant documentation, new patterns |
| `README.md` | Installation instructions (PHP 8.1), changelog summary, new feature examples, updated code samples |
| `CHANGELOG.md` | Structured entries for v0.7.0, v0.8.0, v0.9.0, v1.0.0-alpha with categorized changes (Added, Changed, Fixed, Removed) |
| `OVERRIDES.md` | Phase 1: entries 014–018. Phase 4: investigation-discovered overrides. |
