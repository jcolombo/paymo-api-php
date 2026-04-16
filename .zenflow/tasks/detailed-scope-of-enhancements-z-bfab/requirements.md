# Requirements Discovery: paymo-api-php v1.0.0-alpha

---

## Intent

Upgrade the paymo-api-php SDK from v0.6.1 to v1.0.0-alpha by implementing 56 software enhancement tasks and selectively incorporating 11 ideas/suggestions identified across three prior analyses (deep research, documentation audit, competitive intelligence). The upgrade must be delivered in backward-compatible phases, each stamped as a safe rollback point, so that production consumers can pin to any intermediate version if the full plan breaks down.

The core problem: the SDK has accumulated a significant gap between what the Paymo API actually provides and what the SDK exposes, alongside architectural deficiencies (no type coercion, no cache invalidation, no structured error handling, flat error model, stale dependency) identified by comparison with two peer SDKs in the same family (niftyquoter-api-php, leadfeeder-api-php).

---

## Problem Statement

### What exists today

The paymo-api-php SDK (v0.6.1) is a PHP library providing ORM-style access to the Paymo REST API. It covers 38 resources with CRUD operations, includes filtering (WHERE/HAS), server-side pagination, rate limiting, caching, and a custom integration test framework. It currently requires PHP >= 7.4 and depends on Guzzle 7, hassankhan/config, and adbario/php-dot-notation.

### What is wrong or missing

1. **3 confirmed bugs**: A typo in `EntityMap::overload()` disables overload validation entirely, `PAYMO_DEVELOPMENT_MODE` is hardcoded to `true` leaking error details in production, and `composer.json` autoload-dev maps the wrong namespace polluting IDE autocompletion and static analysis.

2. **60+ undocumented properties** returned by the live API are absent from SDK resource classes, meaning consumers cannot access data the API already provides.

3. **No type coercion**: Consumers manually cast datetime strings, booleans, and enums. Both peer SDKs implement three-direction coercion (hydration, serialization, validation). This is the single largest developer experience gap.

4. **No cache invalidation on mutation**: Create/update/delete operations do not invalidate cached data, causing stale reads in multi-step workflows.

5. **Flat error handling**: No distinction between recoverable warnings (429 rate limit) and terminal failures (401 authentication). No handler routing.

6. **Stale dependency**: hassankhan/config (last updated 2021) wraps php-dot-notation with unused YAML/XML/INI support.

7. **PHP 7.4 floor**: EOL since November 2022 (3+ years). Blocks native enums, readonly properties, and intersection types. Both peer SDKs require >= 8.1.

8. **Missing API features**: No fetchAll() auto-pagination, no parent context enforcement at runtime, no partial_include or nested include dot notation support, no leave management resources, no PDF/XLSX export, no webhook conditional filtering.

9. **19 investigation items** that must be resolved before certain implementations to avoid rework.

### Cost of inaction

- **Bugs in production**: The hardcoded devMode leaks error details. The EntityMap typo means overload validation is silently disabled.
- **Consumer friction**: Every consumer writes boilerplate for pagination loops, datetime parsing, and error classification. This friction compounds across all production uses.
- **Divergence from peers**: As niftyquoter-api-php (Gen 2) and leadfeeder-api-php (Gen 3) advance, paymo-api-php falls further behind, despite having the most resources (38 vs 10 and 6) and the most sophisticated include hydration.
- **Dependency risk**: hassankhan/config has no maintainer activity and may break on future PHP versions.
- **Missing data**: 60+ API properties are inaccessible through the SDK, requiring consumers to make raw API calls or fork the SDK.

---

## Target Users

### Primary: PHP developers integrating Paymo into backend applications

- **Skill level**: Intermediate to senior PHP developers comfortable with Composer packages, PSR standards, and ORM-style APIs.
- **Context**: Building internal tools, billing integrations, time tracking dashboards, or automation scripts that read/write Paymo data.
- **Key need**: Reliable, type-safe access to the full Paymo API surface without workaround boilerplate.

### Secondary: SDK maintainers (the author and contributors)

- **Skill level**: Senior PHP developers maintaining the SDK family (paymo, niftyquoter, leadfeeder).
- **Context**: Backporting innovations across packages, adding new resources, verifying API changes.
- **Key need**: Consistent architecture patterns, automated validation, clear extension points.

### Tertiary: AI coding assistants operating on the codebase

- **Context**: Guided by CLAUDE.md, OVERRIDES.md, PACKAGE-DEV.md to make changes.
- **Key need**: Accurate documentation, consistent patterns, clear override policies so assistants don't "fix" intentional deviations.

---

## User Scenarios

### Scenario 1: Consumer encounters stale cache after mutation

**Trigger**: A developer creates an invoice via `Invoice::new()->set([...])->create()`, then immediately fetches the invoice list via `Invoice::list()->fetch()`.
**Action (current)**: The list returns cached data that does not include the new invoice. The developer must manually disable cache or wait for TTL expiry.
**Action (after v1.0.0-alpha)**: The create() call triggers cache invalidation for the `invoices` resource scope. The subsequent list() fetch returns fresh data including the new invoice.
**Outcome**: Zero stale-read bugs in multi-step create-then-read workflows.
**Verification**: Create a resource, immediately list the same resource type, confirm the new resource appears in the list.

### Scenario 2: Consumer needs all records without pagination boilerplate

**Trigger**: A developer needs all time entries for a date range (potentially thousands of records).
**Action (current)**: The developer writes a manual do/while pagination loop, tracking page numbers and comparing result counts to page size.
**Action (after v1.0.0-alpha)**: The developer calls `TimeEntry::list()->where(...)->fetchAll()` and receives all matching records with automatic pagination, bounded by a configurable safety cap (default 5,000).
**Outcome**: Pagination boilerplate eliminated. One method call replaces 10-15 lines of loop code.
**Verification**: Call `fetchAll()` on a resource with > 100 records. Confirm all records are returned. Confirm the safety cap prevents runaway requests when records exceed the cap.

### Scenario 3: Consumer working with datetime values

**Trigger**: A developer fetches a project and needs to compare `created_on` to the current date.
**Action (current)**: `$project = Project::new()->fetch($id); $created = new \DateTime($project->created_on);` — manual string-to-DateTime conversion on every datetime property.
**Action (after v1.0.0-alpha)**: `$project->created_on` returns a `\DateTimeImmutable` instance. For backward compatibility, `(string)$project->created_on` returns the original ISO string. Serialization for API requests converts back to the API's expected string format automatically.
**Outcome**: Zero manual datetime parsing. Type-safe comparisons and calculations.
**Verification**: Fetch a resource with datetime properties. Confirm `created_on` is a `\DateTimeImmutable` instance. Confirm `json_encode($resource)` produces the original string format. Confirm `set('created_on', new \DateTimeImmutable('now'))` serializes correctly for the API.

### Scenario 4: Consumer needs to distinguish error severity

**Trigger**: A developer's batch import encounters a 429 rate limit response mid-process.
**Action (current)**: The SDK's built-in retry handles 429 automatically (up to 4 attempts). But if the developer has a custom error handler, they cannot distinguish "retriable rate limit" from "terminal authentication failure" — both surface as exceptions with HTTP status codes.
**Action (after v1.0.0-alpha)**: The error carries an `ErrorSeverity` classification (NOTICE/WARN/FATAL). The developer's error handler routes WARN-level errors to retry logic and FATAL-level errors to abort-and-alert logic.
**Outcome**: Error handling moves from HTTP-status-code guesswork to semantic severity routing.
**Verification**: Trigger a 429 response. Confirm the error severity is WARN. Trigger a 401 response. Confirm the error severity is FATAL. Confirm custom error handlers receive the severity classification.

### Scenario 5: Upgrading from v0.6.x to v1.0.0-alpha

**Trigger**: A production consumer running v0.6.1 wants to upgrade.
**Action**: The consumer updates `composer.json` to require `^1.0.0-alpha`. They run `composer update`. Existing code continues to work without modification — all breaking changes are opt-in via new methods, new configuration, or new PHP version requirements.
**Outcome**: Zero breaking changes for existing code. New features are additive.
**Verification**: Run the existing test suite of a v0.6.1 consumer against v1.0.0-alpha. All tests pass. No deprecation warnings unless the consumer explicitly opts into new features that deprecate old patterns.

---

## Functional Requirements

### Bug Fixes (FR-001 through FR-003)

- **FR-001**: When `EntityMap::overload()` is called, the string comparison for the base class name must match `"AbstractResource"` exactly (fix the `"AbstractResourcce"` typo).
- **FR-002**: The `PAYMO_DEVELOPMENT_MODE` constant in `Paymo.php` must read its value from the `devMode` configuration key instead of being hardcoded to `true`. When `devMode` is `false` (or not set), error details must not be output to stdout.
- **FR-003**: The `autoload-dev` section in `composer.json` must map the namespace `Jcolombo\\PaymoApiPhp\\Tests\\` to the `tests` directory (not `Jcolombo\\PaymoApiPhp\\` to `src`). All test files (~30) must update their namespace declarations to `Jcolombo\PaymoApiPhp\Tests\{...}`. Composer test scripts (`test`, `test:dry-run`, `test:verbose`) must be added.

### Property & Type System (FR-004 through FR-011)

- **FR-004**: All undocumented properties discovered through live API testing (60+ properties across 15+ resource classes) must be added to the respective resource's `PROP_TYPES` constant with correct types. Each must be classified as READONLY or writable based on API behavior. Properties must be annotated with `// Undocumented` comments per OVERRIDE-011 policy.
- **FR-005**: A `Converter` class must implement three-direction type coercion:
  - **Phase 1 (hydration)**: `convertToPhpValue($value, $type)` converts API response values to PHP types. Datetime strings become `\DateTimeImmutable`. Boolean strings (`"true"`/`"false"`, `1`/`0`) become native `bool`. Integer strings become `int`. Float strings become `float`.
  - **Phase 2 (serialization)**: `convertForRequest($value, $type)` converts PHP values to API-expected formats. `\DateTimeImmutable` becomes ISO 8601 string. `bool` becomes API-expected representation.
  - **Phase 3 (validation)**: When devMode is enabled, `validateValue($value, $type, $enumValues)` validates set values against allowed enum values before API calls, throwing a descriptive exception for invalid values.
  - **Backward compatibility**: The existing `Converter.php` utility (which handles `string`, `int`, `float`, `bool`, `date`, `datetime`, `html`, `enum`, `enum_int_list`) must be extended, not replaced. Existing type strings in PROP_TYPES must continue to work.
- **FR-006**: Session resource's `id` property must be verified as `text` type in PROP_TYPES (per OVERRIDE-004). (Verification only — already implemented.)
- **FR-007**: Deprecated `language` properties on Invoice, Estimate, and RecurringProfile must be verified as present in READONLY to prevent consumers from setting them. (Verification only for Invoice and Estimate; RecurringProfile already implemented per OVERRIDE-012.)
- **FR-008**: `download_token` property must be added to Expense and Report PROP_TYPES as `text` type, READONLY.
- **FR-009**: A `WRITEONLY` property constant must be added to `AbstractResource` (empty array default). Properties in WRITEONLY trigger actions on the API but are not returned in responses. The constant must integrate with dirty tracking (always included when set) and deserialization (never expected in responses).
- **FR-010**: An audit must determine whether any Paymo API properties are WRITEONLY-applicable. If none exist, FR-009 is implemented but unused (structural parity with peer SDKs).
- **FR-011**: In devMode, `AbstractEntity` (or `AbstractResource`) must validate constant consistency at instantiation: no READONLY properties in REQUIRED_CREATE, no unknown types in PROP_TYPES, no UNSELECTABLE properties missing from PROP_TYPES, no WRITEONLY properties in READONLY, etc. Validation fires once per class (cached result). ~30 lines of cross-referencing logic.

### Query System (FR-012 through FR-016)

- **FR-012**: `AbstractCollection` must implement a `fetchAll()` method that auto-paginates through all pages of results. Configurable safety cap with default of 5,000 records. Per-resource `FETCHALL_CAP` constant for override. Stop condition: result count < page size. Returns a single merged collection. ~20 lines of core logic.
- **FR-013**: Collection classes for File, Booking, InvoiceItem, and EstimateItem must validate that required parent filters are present before executing API calls (`validateFetch()` method). In devMode, throw an exception with a message specifying which filter(s) are required. In production mode, log a warning but allow the call. Currently enforced only by documentation (OVERRIDE-005). ~60 lines across 4-6 classes.
- **FR-014**: The query builder must support `partial_include` syntax: `$project->fetch($id, ['tasks(id,name,due_date)'])` generates `?partial_include=tasks(id,name,due_date)`. Field validation against the included resource's PROP_TYPES in devMode.
- **FR-015**: The query builder must support nested include dot notation: `$project->fetch($id, ['tasks.entries'])` generates `?include=tasks.entries`. Depth validation (maximum 2 levels confirmed, 3+ untested — enforce max 2 with a configurable override). Support in both fetch() and list().
- **FR-016**: The SDK's `RequestCondition` builder must support the `in(me)` syntax for filtering tasks by the currently authenticated user, documented in the API.

### Caching System (FR-017 through FR-019)

- **FR-017**: After any successful POST, PUT, PATCH, or DELETE request, the cache must invalidate entries matching the affected resource type. Invalidation is resource-scoped (not full cache wipe). Implementation hooks into `Paymo::execute()` after mutation responses. URL-pattern-based cache key matching (e.g., a POST to `/api/invoices` invalidates all cached keys matching `/api/invoices*`). The existing `ScrubCache` class (in-memory include-validation cache) must not be renamed or conflated — the new invalidation logic operates on the request/response cache in `Cache.php`.
- **FR-018**: `registerCacheMethods()` must accept a third callback parameter `$clear` (callable) alongside existing `$read` and `$write`. The `$clear` callback signature: `function(string $pattern): void`. Used by FR-017 to clear cache entries matching a URL pattern. Default implementation uses file glob deletion for file-based cache. The third parameter is optional for backward compatibility — existing 2-callback registrations continue to work.
- **FR-019**: `RequestResponse` must include a `cacheHit` boolean property (default `false`). Set to `true` when the response was served from cache. Accessible via `$response->cacheHit` after any fetch operation.

### Error Handling & Logging (FR-020 through FR-021)

- **FR-020**: Implement an `ErrorSeverity` classification system. If PHP >= 8.1 is the minimum (see FR-025), use a native enum with cases `NOTICE`, `WARN`, `FATAL`. If PHP 7.4 compatibility is retained for this phase, use string constants. Refactor `Error.php` from flat HTTP-status-to-prefix mapping to severity-based classification: 401/403 = FATAL, 429 = WARN, 400/404/422 = NOTICE, 500+ = FATAL. Provide a `setErrorHandler(callable $handler)` method on the Paymo connection for routing errors by severity. Default behavior: current behavior (throw exceptions) is preserved.
- **FR-021**: Implement a PSR-3 compatible logging adapter. Consumers can inject any PSR-3 `LoggerInterface` via `Paymo::connect($key, logger: $logger)` or `$connection->setLogger($logger)`. When no PSR-3 logger is injected, the existing `Log` utility class continues to function as the default. The PSR-3 adapter maps SDK log calls to PSR-3 severity levels. `psr/log` is added as a Composer dependency.

### Architecture & Dependencies (FR-022 through FR-028)

- **FR-022**: Bump PHP minimum version in `composer.json` from `>=7.4` to `>=8.1`. Audit codebase for PHP 7.4-only patterns. Adopt PHP 8.1 features where they improve the code: native enums for ErrorSeverity, readonly properties for immutable value objects, named arguments in internal calls where clarity improves.
- **FR-023**: Replace `hassankhan/config` dependency with direct use of `adbario/php-dot-notation`. Rewrite `Configuration.php` to use `Adbar\Dot` directly. Remove `hassankhan/config` from `composer.json`. Verify all configuration access patterns continue to work.
- **FR-024**: Add `Configuration::reset()` static method that destroys the singleton instance, enabling clean test isolation. ~5 lines.
- **FR-025**: Refactor `RateLimiter` to support named scopes with independent rate configurations. Default scope: `global` with current 5 req/5s settings. Additional scopes configurable via `rateLimit.scopes` in config. Preserve existing Retry-After header support. Each scope tracks its own sliding window.
- **FR-026**: Add `validateConstants()` method (same as FR-011, cross-referenced here for the architecture section). Fires in devMode at resource instantiation.
- **FR-027**: Add Composer script shortcuts: `"test": "php tests/validate"`, `"test:dry-run": "php tests/validate --dry-run"`, `"test:verbose": "php tests/validate --verbose"`.
- **FR-028**: Fix the `EntityMap::overload()` typo (same as FR-001, cross-referenced here for the architecture section).

### API Feature Support (FR-029 through FR-031)

- **FR-029**: Implement 4 new resource classes for leave management: `CompanyDaysException`, `UserDaysException`, `LeaveType`, `StatsReport`. Each with PROP_TYPES, CRUD restrictions, and filter requirements based on live API testing results. `StatsReport` is POST-only with a unique response structure. **Prerequisite**: Live testing of endpoints (investigation item) must be completed first to confirm current API behavior.
- **FR-030**: Implement PDF/XLSX export support for Report, Invoice, and Estimate resources. New `export($format)` method on applicable resources. Binary response handling in `Paymo::execute()`. Content-type negotiation (`application/pdf`, `application/vnd.ms-excel`). Return value: raw binary content or file path if save option is specified.
- **FR-031**: Implement webhook conditional filtering builder. Fluent `->condition($field, $value, $operator)` method on Webhook resource. Serializes to the API's `where` parameter format. Support for HMAC-SHA1 `secret` parameter.

### Developer Experience (FR-032 through FR-033)

- **FR-032**: Add `flatten($property)` method overload to `AbstractCollection`. When called with a string argument, returns a flat array of that property's values from all items. Example: `$projects->flatten('name')` returns `['Project A', 'Project B', ...]`. The existing `flatten()` (no arguments) behavior is unchanged.
- **FR-033**: Add `toArray()` and `toJson()` convenience methods to `AbstractResource` and `AbstractCollection`. `toArray()` wraps `flatten()` result. `toJson(int $options = 0)` wraps `json_encode(flatten(), $options)`. These are convenience aliases — no new logic.

### Documentation (FR-034 through FR-039)

- **FR-034**: Document the 2,500-item include truncation behavior in OVERRIDES.md (new override entry). Add optional runtime warning when an included collection returns exactly 2,500 items (log a warning: "Included collection '{key}' returned exactly 2,500 items — results may be truncated. Use direct list() with pagination for complete data."). Warning gated behind devMode.
- **FR-035**: Document that Task and TimeEntry `description` fields may contain HTML tags (`<p>`, etc.) when content is entered via the Paymo web interface. Add note to OVERRIDES.md and to the resource PHPDoc.
- **FR-036**: Document that `retainer_id` appears on Project objects but the Retainer API is not public. Add note to OVERRIDES.md.
- **FR-037**: Document webhook delete/update payload limitations (delete = `{"id": <ID>}` only, update = no changed-fields diff). Add note to Webhook resource PHPDoc and OVERRIDES.md.
- **FR-038**: Document sensitive Company properties (Stripe keys, Authorize.net credentials, SMTP config) in OVERRIDES.md. Add `// SENSITIVE` annotation comment to affected PROP_TYPES entries.
- **FR-039**: Update CLAUDE.md, PACKAGE-DEV.md, and README.md to reflect all v1.0.0-alpha changes: new methods, new constants, new resources, changed PHP minimum, changed dependencies, new configuration options.

### Investigation Items (FR-040 through FR-052)

These produce artifacts (property inventories, validation matrices, test scripts) that inform implementation decisions. Several are prerequisites for other FRs.

- **FR-040**: Execute systematic property discovery against live API for all 38 resources. For each resource, GET without `?select=` and compare response properties against PROP_TYPES. Output: per-resource property diff (extra, missing, type mismatch). This is the prerequisite for FR-004.
- **FR-041**: Live test the 4 leave management endpoints (CompanyDaysException, UserDaysException, LeaveType, StatsReport). Confirm current CRUD behavior, property shapes, and filter support. Output: endpoint test report. Prerequisite for FR-029.
- **FR-042**: Build a filter operator validation matrix. For all 38 resources, test which filter operators (=, !=, <, >, <=, >=, like, not like, in, not in, range) work on which properties. Output: per-resource operator support matrix. Update WHERE_OPERATIONS constants where restrictions exist.
- **FR-043**: Spot-check SDK-only include relationships. The SDK maps ~98 include relationships; documentation reveals ~73. Verify the ~25 SDK-only relationships against the live API. Output: verified/unverified include list.
- **FR-044**: Verify per-resource HAS condition support. Test HAS filtering against known include relationships for each resource. Output: HAS support matrix.
- **FR-045**: Test filter support for undocumented properties (billing_type, cover_file_id, files_count, etc.). Output: filterable/non-filterable classification per property.
- **FR-046**: Compare all 38 SDK resource classes against the full API inventory. Output: 38-resource change manifest (add, remove, retype per property). Prerequisite for FR-004.
- **FR-047**: Reconcile the include relationship count discrepancy (82 from research vs 98 from SDK source). Identify the 16 delta relationships. Output: reconciliation report.
- **FR-048**: Test 3-level and 4-level nested includes to determine actual maximum nesting depth. Output: max confirmed depth.
- **FR-049**: Probe `GET /api/currencies` to determine if it's a live endpoint. If real, create a Currencies resource class.
- **FR-050**: Test RecurringProfileItem and other multi-word compound resource names for response key anomalies (OVERRIDE-009 pattern). Output: confirmed anomalies list.
- **FR-051**: Investigate whether StatsReport supports additional report types beyond `user_annual_leave_stats` and `user_working_days_count`.
- **FR-052**: Determine actual rate limit values for the Paymo API. Check response headers for rate limit information. Output: confirmed rate limits or "undocumented" classification.

---

## Non-Functional Requirements

- **NFR-001 (Backward Compatibility)**: Every intermediate version between v0.6.1 and v1.0.0-alpha must be a non-breaking change relative to the previous version. Existing public API signatures must not change. New features are additive. Deprecations are announced via `@deprecated` PHPDoc annotations and optional runtime warnings in devMode, but deprecated code continues to function. The sole exception is the PHP minimum version bump (FR-022), which is an environment requirement change, not an API change — this must be isolated to a specific minor version boundary.
- **NFR-002 (PHP Version)**: The PHP minimum version must be bumped from 7.4 to 8.1 at a clearly marked version boundary. All code from that version forward may use PHP 8.1 features.
- **NFR-003 (Test Coverage)**: All new public methods must have corresponding test cases in the custom test framework. New resource classes must have individual test classes following the existing pattern in `tests/ResourceTests/`.
- **NFR-004 (Performance)**: The type coercion layer (FR-005) must add less than 1ms overhead per resource hydration (measured on a resource with 30 properties). fetchAll() (FR-012) must not hold more than 2 pages of results in memory simultaneously during pagination (streaming merge).
- **NFR-005 (Dependency Minimalism)**: The only new Composer dependency permitted is `psr/log` (for FR-021). All other features must be implemented without new external dependencies.
- **NFR-006 (Documentation Parity)**: Every new public method, constant, or class must be documented in PACKAGE-DEV.md (for SDK maintainers) and CLAUDE.md (for AI assistants). User-facing features must be documented in README.md.
- **NFR-007 (Semantic Versioning)**: Version numbers must follow SemVer strictly. Pre-1.0 versions (0.7.x, 0.8.x, etc.) may introduce non-breaking additions. The 1.0.0-alpha tag signifies API stability for the public surface.

---

## Constraints

### Technical Constraints

- **TC-001**: The SDK is a Composer library consumed by third-party PHP applications. Changes must not require consumers to modify existing working code (except for the PHP version bump).
- **TC-002**: The Paymo API documentation has not been updated since 2022. All API behavior must be verified against live API responses, not documentation alone.
- **TC-003**: The SDK uses a custom test framework (not PHPUnit). New tests must follow the existing `ResourceTest` pattern and integrate with the `tests/validate` CLI runner.
- **TC-004**: The SDK uses a singleton connection pattern (`Paymo::connect()`). Architectural changes must preserve this pattern.
- **TC-005**: Existing `@override OVERRIDE-XXX` code comments mark intentional deviations and must not be modified without explicit approval.
- **TC-006**: The existing `Converter.php` utility handles basic type conversions. FR-005 extends this class rather than replacing it.
- **TC-007**: The `ScrubCache` class is an in-memory include-validation cache, distinct from the request/response cache in `Cache.php`. Cache invalidation (FR-017) operates on the latter only.

### Business Constraints

- **BC-001**: The SDK is used in production applications. No version may be published that breaks existing consumers.
- **BC-002**: The SDK is one of three packages in a family (paymo, niftyquoter, leadfeeder). Architectural patterns should be compatible with backporting to/from peer SDKs.
- **BC-003**: Investigation items (FR-040 through FR-052) require a valid Paymo API key and live API access. These cannot be executed in CI/CD without API credentials.

---

## Scope Boundaries

### Explicitly In Scope (v1.0.0-alpha)

1. All 3 bug fixes (FR-001 through FR-003)
2. Undocumented property additions (FR-004, dependent on FR-040/FR-046)
3. Three-direction type coercion phases 1-2 (FR-005)
4. fetchAll() auto-pagination (FR-012)
5. Cache invalidation on mutation (FR-017, FR-018)
6. Structured error handling with severity (FR-020)
7. PHP 8.1 minimum version bump (FR-022)
8. hassankhan/config dependency replacement (FR-023)
9. Parent context enforcement (FR-013)
10. PSR-3 logging adapter (FR-021)
11. All investigation items (FR-040 through FR-052) — completed as research phases producing artifacts
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

### Explicitly Out of Scope (deferred to post-1.0.0 or future versions)

1. **Type coercion Phase 3 — enum validation in devMode** (FR-005 Phase 3): Deferred to 1.1.0. Requires comprehensive enum value discovery across all resources.
2. **Leave management resources** (FR-029): Deferred until investigation (FR-041) confirms endpoint stability. If investigation completes during development and endpoints are confirmed stable, may be pulled into 1.0.0-alpha.
3. **PDF/XLSX export support** (FR-030): Deferred to 1.1.0. Requires binary response handling changes that warrant separate testing.
4. **Webhook conditional filtering** (FR-031): Deferred to 1.1.0. Low value, medium effort.
5. **Named rate limiter scopes** (FR-025): Deferred to 1.1.0. Current single-scope limiter is functional.
6. **PHPUnit hybrid adoption**: Deferred indefinitely. The custom test framework is well-suited for API integration testing.
7. **Async/concurrent requests**: Deferred indefinitely. Requires rate limiter redesign and has uncertain benefit given API rate limits.
8. **Middleware pipeline**: Deferred indefinitely. Current request pipeline covers actual use cases.
9. **Singleton fetch pattern**: Deferred. Only applies to Company resource.
10. **Gap matrix maintenance process**: Process/practice item, not a code change.
11. **Documentation linting tools**: Process item.
12. **Cross-document consistency checks**: Process item for future override updates.
13. **Backporting practice establishment**: Process item.
14. **Separate test config file evaluation**: Minor DX consideration, not blocking.

### Future Considerations (post-1.0.0)

- Currencies resource (if FR-049 confirms the endpoint exists)
- StatsReport additional report types (if FR-051 discovers them)
- 3+ level nested includes (if FR-048 confirms support)
- Forward-porting Paymo advantages (recursive include hydration, HAS filtering, Retry-After) to peer SDKs

---

## Success Criteria

1. **SC-001**: A PHP application using paymo-api-php v0.6.1 can upgrade to v1.0.0-alpha by changing `composer.json` to require `>=8.1` for PHP and updating the package version. All existing code (CRUD operations, filtering, caching, pagination) continues to work without modification.
2. **SC-002**: `EntityMap::overload()` correctly validates against the string `"AbstractResource"` (not `"AbstractResourcce"`). Overload validation is functional.
3. **SC-003**: When `devMode` configuration is `false`, no error details are output to stdout. When `devMode` is `true`, error details are shown.
4. **SC-004**: `composer dump-autoload --no-dev` produces an autoloader that does not include test classes. IDE autocompletion for `Jcolombo\PaymoApiPhp\` does not show test classes.
5. **SC-005**: `$project->created_on` returns a `\DateTimeImmutable` instance after hydration. `json_encode($project->flatten())` produces the original API string format for datetime fields.
6. **SC-006**: After `Invoice::new()->set([...])->create()`, an immediately subsequent `Invoice::list()->fetch()` returns data that includes the newly created invoice (cache was invalidated).
7. **SC-007**: `Invoice::list()->fetchAll()` returns all invoices across all pages without manual pagination. The call terminates when fewer results than `page_size` are returned or when the safety cap is reached.
8. **SC-008**: Catching an API error provides an `ErrorSeverity` classification. A 429 response produces severity `WARN`. A 401 response produces severity `FATAL`.
9. **SC-009**: `Configuration::reset()` destroys the singleton. A subsequent `Configuration::get()` call reads from the config file afresh.
10. **SC-010**: Each intermediate version (0.7.0, 0.8.0, 0.9.0, etc.) is a tagged, installable release that passes the existing test suite plus new tests for features added in that version.
11. **SC-011**: The `hassankhan/config` package is no longer in `composer.json` or `composer.lock`. `Configuration.php` uses `Adbar\Dot` directly.
12. **SC-012**: `registerCacheMethods()` accepts 2 or 3 callbacks. Existing 2-callback registrations continue to work. 3-callback registrations enable mutation-triggered cache clearing.
13. **SC-013**: `$response->cacheHit` is `true` when data was served from cache, `false` when served from a live API call.
14. **SC-014**: A PSR-3 logger injected via `$connection->setLogger($monolog)` receives log entries for all API requests and responses at appropriate severity levels.
15. **SC-015**: In devMode, instantiating a resource whose REQUIRED_CREATE contains a READONLY property throws a descriptive exception identifying the conflicting property and constants.

---

## Assumptions

### A-001: PHP 8.1 bump is acceptable as a breaking environment change

**Decision**: The PHP version bump from 7.4 to 8.1 is treated as the single permitted breaking change, isolated to a specific version boundary.
**Justification**: PHP 7.4 has been EOL since November 2022. Both peer SDKs require >= 8.1. No reasonable production PHP deployment should still be on 7.4 in 2026.

### A-002: The existing Converter.php can be extended for three-direction coercion

**Decision**: FR-005 extends the existing `Converter.php` rather than creating a new class.
**Justification**: The existing Converter already handles basic type mapping. Extending it preserves existing behavior and avoids a parallel conversion path.

### A-003: Cache invalidation by URL pattern is sufficient

**Decision**: Cache invalidation after mutations uses URL-pattern matching (e.g., invalidate all cache keys containing `/api/invoices`) rather than object-level tracking.
**Justification**: URL-pattern matching is simple, covers all cases (list and individual fetches), and doesn't require tracking object IDs through the cache layer.

### A-004: fetchAll() safety cap of 5,000 records is appropriate

**Decision**: Default cap of 5,000 records. Per-resource override via `FETCHALL_CAP` constant.
**Justification**: 5,000 records is large enough for typical use cases (most Paymo accounts have < 5,000 of any single resource type) while preventing runaway pagination against very large datasets. The per-resource override handles edge cases.

### A-005: PSR-3 logging is additive, not a replacement for the existing Log utility

**Decision**: The existing `Log.php` utility continues to function as the default logger. PSR-3 is an optional injection.
**Justification**: Backward compatibility. Existing consumers using log file output via the current system should not be disrupted.

### A-006: Investigation items can be executed in a separate session with API access

**Decision**: FR-040 through FR-052 are treated as executable research tasks that produce artifacts (markdown reports, test scripts). These artifacts feed into implementation tasks.
**Justification**: These require live API access which is environment-dependent. Separating investigation from implementation prevents blocking.

### A-007: The 2,500-item include truncation threshold is consistent across all resources

**Decision**: The warning threshold for include truncation (FR-034) is set at 2,500 items for all resources.
**Justification**: No evidence exists that the threshold varies by resource type. If FR-034's investigation (mentioned as a deferred investigation) reveals variation, the threshold can be made per-resource.

### A-008: Version numbering follows 0.7.0 → 0.8.0 → 0.9.0 → 1.0.0-alpha progression

**Decision**: Each major phase of work produces a minor version bump. The final phase tags 1.0.0-alpha.
**Justification**: The user explicitly requires intermediate rollback points. Minor version bumps with tags provide this. Pre-1.0 SemVer allows additions without major bumps.

### A-009: The WRITEONLY constant may be implemented but unused

**Decision**: FR-009 implements the WRITEONLY constant regardless of whether FR-010's audit finds applicable properties.
**Justification**: Structural parity with peer SDKs has value even if no Paymo properties currently fit the pattern. The constant is zero-cost when empty.

### A-010: Backward compatibility means "existing code continues to work without modification"

**Decision**: Backward compatibility is defined as: any code written against v0.6.1's public API continues to produce the same results against v1.0.0-alpha, with two exceptions: (1) PHP 8.1 is now required (environment change), and (2) properties that previously returned raw strings may now return typed objects that implement `__toString()` for transparent string coercion.
**Justification**: The user explicitly requires backward compatibility "in phases that let us stamp it at a safe non-breaking point." The `__toString()` implementation on typed values preserves string comparison and concatenation behavior.

### A-011: Type coercion backward compatibility via __toString()

**Decision**: Datetime values returned as `\DateTimeImmutable` objects implement `__toString()` returning the original ISO 8601 string. Code that does `echo $project->created_on` or `$project->created_on === '2024-01-01T...'` continues to work.
**Justification**: This is the established pattern in PHP for introducing typed values without breaking string consumers. Strict equality (`===`) with strings will break, but loose equality (`==`) and string contexts will not. This is documented as a known behavior change.

---

## Complexity Assessment

**Overall: Complex**

Rationale:
- 56 software enhancement tasks spanning bug fixes, architecture changes, new features, new resources, and investigations
- Cross-cutting concerns: type coercion affects all 38 resources, cache invalidation affects the entire request lifecycle, PHP version bump affects the entire codebase
- Backward compatibility constraints require careful phasing and testing at every boundary
- 19 investigation items that may alter implementation decisions for dependent features
- Multiple dependency chains (investigation → implementation, PHP bump → enum usage, cache callbacks → cache invalidation)
- Production deployment concerns require safe rollback points

Subsequent steps should follow the **Full Path** for all analysis categories.
