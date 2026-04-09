# Competitive Analysis — paymo-api-php vs. Peer SDK Packages

**Subject:** paymo-api-php (Generation 1, March 2020)
**Competitors:** niftyquoter-api-php (Generation 2, April 4 2026), leadfeeder-api-php (Generation 3, April 7 2026)
**Analysis Date:** 2026-04-09
**Depth:** Focused Analysis
**Analyst:** Autonomous competitive intelligence agent

---

## Summary

paymo-api-php is a mature, feature-rich PHP SDK (38 resources, full CRUD) that predates two peer packages built by the same developer. The peer packages — niftyquoter-api-php (10 resources) and leadfeeder-api-php (6 resources) — represent iterative improvements on the same architectural pattern, introducing structured error handling, three-direction type coercion, mutation-triggered cache invalidation, auto-pagination, and improved developer safeguards. paymo-api-php retains three unique advantages (recursive include hydration, server-side HAS filtering, Retry-After header support) but has 13 identified gaps where peer package patterns should be adopted, ranging from trivial bug fixes to a high-effort type coercion overhaul.

**Key findings:**

- **2 Critical gaps:** No type coercion on hydration/serialization (~570 typed properties stored as raw strings); no mutation-triggered cache invalidation (stale reads after CRUD operations)
- **3 High gaps:** No auto-pagination (`fetchAll()`), no structured error handling with severity levels, misconfigured autoload-dev namespace
- **5 Medium gaps:** No parent context enforcement, no WRITEONLY constant, single-scope rate limiter, outdated config dependency, hardcoded devMode
- **3 Low gaps:** No devMode constant validation, no Configuration::reset(), EntityMap typo bug
- **3 Paymo advantages to preserve:** Recursive include hydration, server-side HAS filtering, Retry-After header support
- **5 cross-package white spaces:** No PSR-3 logging, no PHPUnit, no async requests, no middleware pipeline, no batch operations
- **Recommended adoption order:** Bug fixes first (trivial), then quick wins (low effort, high cumulative value), then medium-term architectural improvements, then long-term type system overhaul

---

## Key Findings

### [Critical] No Type Coercion on Hydration or Serialization

- **Category:** Gap > Feature Parity
- **Evidence:** NQ/LF `Converter` implements three-direction coercion: `convertToPhpValue()` (API to PHP on hydration), `convertForRequest()` (PHP to API on create/update), `convertForFilter()` (query params). Paymo's `Converter` handles filter conversion only (`convertValueForFilter()`, `convertOperatorValue()`). All 38 resources store API values as-is — datetime strings remain strings, booleans may be `0`/`1`, enums are unchecked. [Source: direct code comparison of `src/Utility/Converter.php` across all three packages]
- **Impact:** Every consumer must manually cast types (`strtotime($project->created_on)` instead of accessing a `DateTimeImmutable`). No validation that set values match expected types before API calls — type errors surface as 400 responses, not SDK exceptions. With ~570 typed properties across 38 resources, the manual casting burden is the single largest DX gap.
- **Recommendation:** Extend Converter with `convertToPhpValue()` and `convertForRequest()`. Phase 1: datetime + boolean coercion on hydration. Phase 2: full three-direction coercion. Phase 3: enum validation in devMode.
- **Confidence:** High — direct source code comparison; NQ/LF implementation is proven and stable.
- **Effort:** High — touches hydration pipeline for all 38 resources. Must be backwards-compatible.

### [Critical] No Mutation-Triggered Cache Invalidation

- **Category:** Gap > Feature Parity
- **Evidence:** NQ introduced `ScrubCache::invalidate()` called after every POST/PUT/DELETE. LF refined it (removed NQ's dead `buildPattern()` code). Paymo relies entirely on TTL-based expiry — cached GET responses remain valid for up to 300s after underlying data is mutated. Paymo's existing `ScrubCache` class is an in-memory include-validation cache (completely different concept despite the shared name). [Source: `src/Cache/ScrubCache.php` in all three packages; `NiftyQuoter::execute()` and `Leadfeeder::execute()` mutation hooks]
- **Impact:** In multi-step workflows (create invoice, add items, fetch invoice to verify), stale cache returns pre-mutation data. With 38 full-CRUD resources, the window for stale reads is wide. `skipCache` workaround exists but requires manual use after every mutation.
- **Recommendation:** Adopt LF's refined ScrubCache pattern but implement resource-scoped invalidation (invalidate keys matching the mutated resource's URL pattern) rather than full-wipe. Full wipe at 38 resources would effectively defeat caching.
- **Confidence:** High — direct source code analysis; cache behavior verified across all three implementations.
- **Effort:** Medium — hook into `Paymo::execute()` after mutations, implement URL-pattern-based key matching.

### [High] No Auto-Pagination (fetchAll)

- **Category:** Gap > Feature Parity
- **Evidence:** NQ implements `fetchAll()` with do/while loop. LF adds `FETCHALL_CAP = 10000` safety cap and `links.next` page-end detection. Paymo has manual `limit($page, $pageSize)` only — consumers must implement their own pagination loops. [Source: `src/Entity/AbstractCollection.php` in all three packages]
- **Impact:** Every consumer needing all records writes boilerplate pagination code. No safety cap means naive loops could run indefinitely on large collections (e.g., all time entries). Highest-value low-effort adoption candidate.
- **Recommendation:** Add `fetchAll()` to `AbstractCollection` with configurable `FETCHALL_CAP` constant (default 5000). Use result-count < page-size detection (Paymo API doesn't provide `links.next`).
- **Confidence:** High — proven in both NQ and LF; Paymo's pagination API behavior is documented.
- **Effort:** Low — single method, ~20 lines. Highest ROI adoption candidate.

### [High] No Structured Error Handling with Severity Levels

- **Category:** Gap > Feature Parity
- **Evidence:** NQ introduced `ErrorSeverity` enum (NOTICE/WARN/FATAL) with configurable per-severity handler dispatch. LF adopted the same pattern. Paymo has flat error handling: HTTP status code mapping to prefix strings, `echo` in dev mode, no handler dispatch. Additionally, `PAYMO_DEVELOPMENT_MODE` is hardcoded to `true` in `Paymo.php:62`, leaking error details to stdout in all environments. [Source: `src/Utility/Error.php` and `src/Utility/ErrorSeverity.php` in NQ/LF; `src/Paymo.php` line 62]
- **Impact:** Consumers cannot programmatically distinguish recoverable warnings (429 rate limit) from terminal failures (401 authentication). No mechanism to route errors to different handlers. The hardcoded devMode is a standalone bug.
- **Recommendation:** (1) Adopt ErrorSeverity enum and per-severity handler dispatch. (2) Fix hardcoded `PAYMO_DEVELOPMENT_MODE` to respect `devMode` config key (independent bug fix). (3) Add handler configuration to config file.
- **Confidence:** High — direct source code analysis.
- **Effort:** Medium — new enum, refactor Error.php, add handler config. DevMode fix is trivial (one line).

### [High] Misconfigured autoload-dev Namespace

- **Category:** Gap > Quality
- **Evidence:** Paymo's `composer.json` `autoload-dev` maps `Jcolombo\\PaymoApiPhp\\` to `tests/` — the same namespace as production `src/`. NQ and LF correctly use `Jcolombo\\{Package}\\Tests\\` to `tests/`. [Source: `composer.json` across all three packages]
- **Impact:** Test classes appear in IDE autocompletion alongside production classes. Static analysis tools process tests as production code. Cannot use `--no-dev` Composer flag to cleanly exclude test code.
- **Recommendation:** Change autoload-dev to `Jcolombo\\PaymoApiPhp\\Tests\\` to `tests/`. Update ~30 test file namespace declarations. Add Composer test scripts (`test`, `test:dry-run`, `test:verbose`) matching NQ/LF.
- **Confidence:** High — direct composer.json comparison.
- **Effort:** Low — namespace rename + `composer dump-autoload`.

---

## Competitive Context

**Decision:** Identify proven patterns from peer PHP SDK packages that should be adopted into paymo-api-php to improve its architecture, feature completeness, developer experience, testing, and configuration.

**Market:** Internal PHP SDK packages wrapping third-party REST APIs, all by the same developer (Joel Colombo / jcolombo), sharing the `Jcolombo\*` namespace root and the same AbstractEntity architectural lineage.

**Evaluation Criteria:**
1. Architecture Patterns — base classes, entity structure, collection handling
2. Feature Completeness — caching, logging, pagination, rate limiting, error handling, retry logic
3. Developer Experience — fluent API, query builders, includes, dirty tracking
4. Testing — test coverage approach and infrastructure
5. Configuration — config file handling, defaults, overrides

**Scope:** 2 direct peer packages analyzed via full source code access. Focused Analysis depth — known competitors, source-code-level evidence.

**Evolutionary Context:**
```
pipeline-deals-api (2014) --- Generation 0 (PHP 5.3, pre-Composer, excluded)
paymo-api-php (2020)      --- Generation 1 <-- SUBJECT
niftyquoter-api-php (Apr 4, 2026) --- Generation 2 (PHP 8.1+, enhanced patterns)
leadfeeder-api-php (Apr 7, 2026)  --- Generation 3 (PHP 8.1+, NQ bugfixes, JSON:API)
```

"Missing" features in paymo-api-php are not-yet-backported improvements from newer packages, not regressions.

---

## Competitor Profiles

### 1. niftyquoter-api-php (NQ) — Generation 2

**Overview:**
- Package: `jcolombo/niftyquoter-api-php` v0.x (pre-release)
- PHP >=8.1 | Guzzle ^7.8 | adbario/php-dot-notation ^3.3
- API: NiftyQuoter (proposals/quoting) | 10 resources | Full CRUD | Custom JSON
- Built immediately after paymo-api-php with the benefit of production hindsight

**Strengths:**
- Dual sliding-window rate limiter with per-minute AND per-hour windows plus minimum inter-request delay [Source: `src/Utility/RateLimiter.php` — `waitIfNeeded()`]
- Structured error handling with `ErrorSeverity` enum (NOTICE/WARN/FATAL) and configurable per-severity handler dispatch [Source: `src/Utility/Error.php`, `src/Utility/ErrorSeverity.php`]
- Three-direction type coercion via Converter: hydration, serialization, and filter conversion including `DateTimeImmutable` and devMode enum validation [Source: `src/Utility/Converter.php`]
- Mutation-triggered cache invalidation via ScrubCache [Source: `src/Cache/ScrubCache.php`]
- WRITEONLY constant for action-trigger properties [Source: `Proposal.php` WRITEONLY constant]
- Exponential backoff retry on 429 (2000ms base, 2^n multiplier, 3 retries) [Source: `src/Utility/RateLimiter.php`]
- `fetchAll()` auto-pagination [Source: `src/Entity/AbstractCollection.php`]
- Custom test framework with dry-run mode, fixtures, cleanup manager [Source: `tests/` directory]
- Parent context enforcement via `validateFetch()` on nested collections [Source: `CommentCollection.php`]

**Weaknesses:**
- No recursive include hydration — included data goes to `$unlisted`, not typed entities [Source: `AbstractResource.php`]
- 10 resources vs. 38 — architectural decisions untested at scale
- ScrubCache uses full cache wipe with dead code for granular approach (`buildPattern()` never called) [Source: `src/Cache/ScrubCache.php`]
- No server-side HAS filtering — `has()` is client-side post-filter only [Source: `AbstractCollection.php`]
- No Retry-After header support — goes straight to calculated backoff
- Pre-release maturity (v0.x, no tagged releases)

**Target Market:** Same developer/team — internal package for NiftyQuoter API integration.

**Positioning:** Generation 2 evolution. Introduces infrastructure features (structured errors, type coercion, ScrubCache, test framework) absent from paymo-api-php. The most architecturally parallel peer — closest to a 1:1 pattern match for adoption.

**SWOT Summary (relative to paymo-api-php):**

| | |
|---|---|
| **Strengths** | Structured error handling, three-direction Converter, ScrubCache, WRITEONLY, dual-window rate limiter, fetchAll(), parent context enforcement, Composer test scripts, correct autoload-dev |
| **Weaknesses** | No recursive include hydration (Paymo's key advantage), no server-side HAS filtering, no Retry-After header support, full-wipe ScrubCache won't scale to 38 resources, untested at Paymo's resource count |
| **Opportunities for Paymo** | Adopt ErrorSeverity, Converter extensions, ScrubCache (adapted), fetchAll(), validateFetch(), WRITEONLY, validateConstants(), test script conventions, autoload-dev fix |
| **Threats to Paymo** | NQ patterns become the team's standard; if NQ eventually gets include hydration, Paymo's last unique advantage narrows. Divergence accumulates as NQ evolves further. |

---

### 2. leadfeeder-api-php (LF) — Generation 3

**Overview:**
- Package: `jcolombo/leadfeeder-api-php` v0.1.0
- PHP >=8.1 | Guzzle ^7.8 | adbario/php-dot-notation ^3.3
- API: Leadfeeder/Dealfront (B2B lead tracking) | 6 resources | Read-only + Export | JSON:API
- Built days after NQ with explicit bugfix references (`FIX (NQ bug X.Y)`)

**Strengths:**
- JSON:API parser with full envelope flattening and O(1) include resolution via lookup maps [Source: `src/Utility/JsonApiParser.php`]
- Multi-scope rate limiting (4 scopes: export 5/min, account 100/min, token 100/min, ipenrich 60/min) [Source: `src/Utility/RateLimiter.php`]
- ExportManager for async create-poll-download lifecycle [Source: `src/Export/ExportManager.php`]
- IpEnrichClient demonstrating clean multi-API-endpoint architecture [Source: `src/IpEnrich/IpEnrichClient.php`]
- Cache-hit detection via `fromCacheKey` on RequestResponse (fix for NQ bug) [Source: `src/Cache/Cache.php`]
- INCLUDE_ONLY resources that exist only as hydrated includes [Source: `src/Entity/Resource/Location.php`]
- Singleton fetch for single-instance resources [Source: `src/Entity/Resource/WebsiteTrackingScript.php`]
- Account scoping on connection (`setAccount()` auto-prefixes URLs) [Source: `src/Leadfeeder.php`]
- `dateRange()` fluent method with devMode warnings when not set [Source: `src/Entity/AbstractCollection.php`]
- `validateConstants()` in every constructor [Source: `src/Entity/AbstractEntity.php`]
- `fetchAll()` with `FETCHALL_CAP = 10000` hard safety cap [Source: `src/Entity/AbstractCollection.php`]
- `Configuration::reset()` for test isolation [Source: `src/Configuration.php`]
- ScrubCache refined — NQ dead code removed [Source: `src/Cache/ScrubCache.php`]

**Weaknesses:**
- Read-only CRUD — no create/update/delete. Write-path patterns (dirty tracking, WRITEONLY, CREATEONLY) are inherited but not exercised [Source: `AbstractResource.php`]
- 6 resources only — smallest API surface, cannot validate patterns at scale
- ExportManager is tightly coupled to Leadfeeder's export workflow — not directly transferable
- No Retry-After header support (same as NQ)

**Target Market:** Same developer/team — internal package for Leadfeeder/Dealfront API integration.

**Positioning:** Generation 3, most evolved version. Fixes NQ bugs, adds JSON:API handling, multi-scope rate limiting, async export, and developer safeguards. Represents current best-practice within this SDK family.

**SWOT Summary (relative to paymo-api-php):**

| | |
|---|---|
| **Strengths** | All of NQ's advantages plus: multi-scope rate limiter, cache-hit detection, INCLUDE_ONLY resources, singleton fetch, Configuration::reset(), fetchAll() safety cap, NQ bug fixes applied |
| **Weaknesses** | Read-only (write patterns untested), no recursive include hydration, no server-side HAS filtering, no Retry-After header support, smallest resource count |
| **Opportunities for Paymo** | Adopt refined ScrubCache (cleaner than NQ), fetchAll() with safety cap, cache-hit detection, INCLUDE_ONLY pattern (if applicable), Configuration::reset(), validateConstants() pattern, multi-scope rate limiter architecture |
| **Threats to Paymo** | LF becomes the reference implementation for new packages — patterns adopted from LF become the expected standard. If the team builds a Generation 4 package, Paymo falls further behind. |

---

## Comparison Matrix

All cells based on direct source code analysis, April 2026. Every dimension evaluated consistently across all three packages.

### Architecture Patterns

| Sub-Dimension | paymo-api-php (Gen 1) | niftyquoter-api-php (Gen 2) | leadfeeder-api-php (Gen 3) |
|---|---|---|---|
| **Base class hierarchy** | AbstractEntity to AbstractResource + AbstractCollection. 7 specialized collections. | Same pattern. 5 specialized collections. Adds `validateConstants()` in devMode. | Same pattern. `validateConstants()` in every constructor. INCLUDE_ONLY and singleton patterns. |
| **Entity property management** | PROP_TYPES, READONLY, CREATEONLY, UNSELECTABLE, REQUIRED_CREATE, WHERE_OPERATIONS, INCLUDE_TYPES. Magic __get/__set. Dual storage: $props + $loaded + $unlisted + $included. | Same constants plus WRITEONLY. Same dual storage. No UNSELECTABLE. | Same as NQ plus INCLUDE_ONLY, API_TYPE. REQUIRED_CONSTANTS enforced by validateConstants(). |
| **HTTP client management** | Centralized execute(). HTTP Basic auth. Guzzle. Pipeline: cache check, rate limit, execute, retry, log. | Same pipeline. Adds 429 retry with exponential backoff, ScrubCache after mutations. | Same pipeline. Adds account-scoping URL prefix and multi-scope rate limit selection. |
| **Response parsing** | RequestResponse VO. `_hydrate()` with hydrationMode flag for READONLY writes. Full recursive include hydration via `_hydrateInclude()`. | RequestResponse VO. Converter type coercion on hydration. Includes go to $unlisted (no entity hydration). | RequestResponse VO with fromCacheKey. JsonApiParser envelope flattening. Converter coercion + recursive include hydration. |
| **Include resolution** | Full recursive hydration creates typed resource/collection instances. `$project->client->name` returns typed Client. | `include()` fluent method (server-side query param). No client-side entity hydration. | JsonApiParser resolves includes via type:id lookup map. INCLUDE_ONLY for include-only resources. |
| **Class registry** | EntityMap from JSON config. `overload()` for runtime class substitution. 40+ entity keys. | Same pattern. `overload()` with devMode validation. 10 keys. | Same pattern. 6 keys. |

### Feature Completeness

| Sub-Dimension | paymo-api-php (Gen 1) | niftyquoter-api-php (Gen 2) | leadfeeder-api-php (Gen 3) |
|---|---|---|---|
| **Caching strategy** | File-based, MD5 key, GET only, TTL 300s. | Same pattern. | Same pattern. |
| **Cache invalidation** | Time-based only (TTL expiry). No mutation-triggered invalidation. | ScrubCache: full cache wipe on POST/PUT/DELETE. Dead code for granular approach. | ScrubCache: full cache wipe. NQ dead code removed. Cleaner implementation. |
| **Cache custom backends** | `registerCacheMethods($fetch, $store)` — 2 callbacks. | `registerCacheMethods($read, $write, $clear)` — 3 callbacks (adds $clear). | Same as NQ (3 callbacks). |
| **Cache-hit detection** | None. Cannot distinguish cached vs. fresh response. | None. (Bug documented in LF.) | `fromCacheKey` on RequestResponse. Fix for NQ bug. |
| **Logging** | Log singleton. File-append. `Log::onlyIf($bool)` conditional. | Same pattern. JSON context in format string. | Same as NQ. |
| **Pagination** | `limit($page, $pageSize)` fluent. 0-indexed. No fetchAll(). Manual iteration. | `limit($page, $pageSize)` fluent. 1-indexed. `fetchAll()` auto-paginates. Per-resource PAGE_SIZE. | `page($n)` and `pageSize($n)` separate methods. 1-indexed. `fetchAll()` with FETCHALL_CAP = 10000. |
| **Rate limiting** | Single sliding window. Reads x-ratelimit-* response headers. 5 req/5s. minDelayMs 200ms. | Dual sliding window (per-minute + per-hour). No header parsing. minDelayMs 200ms. | Multi-scope (4 scopes with independent limits). No header parsing. minDelayMs 200ms. |
| **Error handling** | Flat: HTTP status code to prefix string mapping. echo in dev mode. No severity levels. | Severity-based: ErrorSeverity enum (NOTICE/WARN/FATAL). Per-severity handler dispatch. Configurable handlers. | Same as NQ. Adds isIpEnrich param for alternate error body parsing. |
| **Retry logic** | Loop up to 4 attempts. 429 only. Retry-After header support + exponential backoff with jitter. | 429 retry via recursive execute(). Max 3 retries. Exponential backoff 2000ms * 2^n. No Retry-After. | Same as NQ. Separate retry path for IpEnrichClient. |
| **Type coercion** | Filter values only: convertValueForFilter(), convertOperatorValue(). No hydration/serialization coercion. | Three directions: convertToPhpValue() (hydration), convertForRequest() (serialization), convertForFilter(). DateTimeImmutable. Enum validation in devMode. | Same as NQ. Adds array, object, array:object type support. |

### Developer Experience

| Sub-Dimension | paymo-api-php (Gen 1) | niftyquoter-api-php (Gen 2) | leadfeeder-api-php (Gen 3) |
|---|---|---|---|
| **Fluent API** | set(), ignoreCache(), protectDirtyOverwrites(), wash(), clear() on resource. options(), limit(), where() on collection. | set() on resource. where(), has(), include(), limit(), fields(), options() on collection. Parent-context shortcuts (forProposal()). | Same as NQ plus page(), pageSize() (separate from limit), dateRange(). Parent shortcuts (forFeed(), forLead()). |
| **WHERE conditions** | Resource::where($prop, $value, $op) to RequestCondition. Full operator set (=, !=, <, <=, >, >=, like, not like, in, not in, range). WHERE_OPERATIONS per-resource validation. | where() on collection. Same operators. WHERE_OPERATIONS validation in devMode only. | Same as NQ. Bracket-notation for JSON:API compatibility. |
| **HAS conditions** | Resource::has() compiles to **server-side** API-level relationship count filtering. Requires include in fetch fields. RANGE operator supported. | has() on collection. **Client-side** post-filtering only. | Same as NQ (client-side only). |
| **Include system** | Full recursive hydration. _hydrateInclude() creates typed entity/collection instances. Dot-notation for property selection within includes. | include() fluent. Server-side query param. No client-side entity hydration (data in $unlisted). | include() fluent. JSON:API include resolution via JsonApiParser. Typed entity hydration for known include keys. INCLUDE_ONLY resources. |
| **Dirty tracking** | isDirty($checkRelations), getDirtyKeys(), getDirtyValues(), wash(), protectDirtyOverwrites(). update() sends only dirty fields. | isDirty(?$property), getDirty(). update() sends only dirty fields intersected with getWritableData(). | Not exercised (read-only API). Inherited from NQ but unused. |
| **Static factory methods** | Resource::new(), Resource::list(), Resource::where(), Resource::has(), Resource::deleteById(). | AbstractEntity::new(), AbstractEntity::list(). Delegates to EntityMap. | Same as NQ. |
| **Collection interfaces** | Iterator, ArrayAccess, JsonSerializable, Countable. Data keyed by ID. flatten(), raw(). | Iterator, ArrayAccess, Countable, JsonSerializable. raw(), flatten($property) for single-property extraction. toArray(), toJson(). | Same as NQ. |
| **Parent context** | Connection inheritance via getConfiguration(). No parent-path URL prefixing. No validateFetch(). | setParentContext() with entity+id. getParentPath() returns path. URL prefixed in buildUrl(). validateFetch() enforces parent on nested collections. | Same as NQ. Account scoping on connection (setAccount() auto-prefixes URLs). validateFetch() emits warnings for missing dateRange. |
| **Singleton fetch** | Not supported. All fetch() calls require ID. | Not supported. | Supported. fetchSingleton() with no ID. list() throws for singleton resources. |
| **Data export** | flatten() on resource and collection. jsonSerialize(). stripNull option. | toArray(), toJson() on resource. flatten($property) on collection. jsonSerialize(). | Same as NQ. |

### Testing

| Sub-Dimension | paymo-api-php (Gen 1) | niftyquoter-api-php (Gen 2) | leadfeeder-api-php (Gen 3) |
|---|---|---|---|
| **Framework** | Custom (no PHPUnit). CLI runner tests/validate. | Custom. CLI runner. Composer scripts: test, test:dry-run, test:verbose. | Custom. Same Composer scripts. |
| **Test modes** | dry_run, verbose, stop_on_failure, cleanup_on_failure, interactive, reset_log. CLI: --read-only, --list-limit N. | dry_run, verbose, stop_on_failure. CLI: --dry-run, --resource=name. | Same as NQ. Adds AccountSelector for test account resolution. |
| **Fixtures** | TestDataFactory with [TEST]-123456 prefixes. Binary fixture (test-image.png). | TestDataFactory with [TEST] + 4-digit random suffix. Factory for all 10 resources. | No write fixtures (read-only). Live API data. |
| **Test runner** | ResourceTestRunner. 27 resource test classes. Ordered execution. Aggregated TestResult. | ResourceTestRunner. 10 test classes. --resource filter. TestLogger to test-results.log. | ResourceTestRunner. 8 test classes. AccountSelector. TestLogger with dated log files. |
| **Cleanup** | CleanupManager with register_shutdown_function. Dependency-ordered deletion. | CleanupManager. Explicit dependency order (children to parents). | Not needed (read-only). |
| **Assertions** | Custom assertions in ResourceTest base. | assertEqual, assertNotNull, assertTrue, assertInstanceOf. | Same as NQ plus assertThrows. |
| **autoload-dev** | **Misconfigured**: mirrors src namespace (Jcolombo\PaymoApiPhp\ maps to tests/). | Correct: separate Tests namespace (Jcolombo\NiftyQuoterApiPhp\Tests\). | Correct: separate Tests namespace. |

### Configuration

| Sub-Dimension | paymo-api-php (Gen 1) | niftyquoter-api-php (Gen 2) | leadfeeder-api-php (Gen 3) |
|---|---|---|---|
| **Config library** | hassankhan/config (Noodlehaus). Wraps php-dot-notation with unused YAML/XML/INI support. Last updated 2021. | adbario/php-dot-notation (Adbar\Dot). Direct usage. | Same as NQ. |
| **Default config** | default.paymoapi.config.json at package root. | default.niftyquoterapi.config.json. | default.leadfeederapi.config.json. |
| **User config** | paymoapi.config.json via Configuration::load($path). | niftyquoterapi.config.json via Configuration::overload($path). Also separate test config file. | leadfeederapi.config.json via Configuration::overload($path). |
| **Test config** | No separate test config file. Settings embedded in main config under testing.*. | Separate test config file: niftyquoterapi.config.test.json as third layer. | No separate file. Settings via testing.* keys. |
| **Runtime overrides** | Configuration::set(), get(), has(). In-memory only. | Same API. | Same. Adds Configuration::reset() for singleton destruction (test isolation). |
| **EntityMap** | JSON config under classMap.entity.*. EntityMap::overload() for runtime substitution. 40+ keys. **Typo bug in validation**: checks "AbstractResourcce" (extra c). | Same pattern. overload() with devMode validation. 10 keys. | Same. 6 keys. |
| **Dev mode** | devMode config key + hardcoded PAYMO_DEVELOPMENT_MODE = true (stuck on). Enables echo of error details. No constructor constant validation. | devMode config key (default false). Enables: validateConstants(), WHERE_OPERATIONS checks, enum validation, EntityMap validation. | Same as NQ. validateConstants() called in every constructor. |
| **Per-request overrides** | skipCache option on fetch/list. ignoreCache() and protectDirtyOverwrites() on entities. | options() on collections. | Same options pattern. |

---

## Gap & Opportunity Analysis

### Gap Inventory

Ordered by priority. Each maps to evidence in the Comparison Matrix.

| Priority | Gap | Effort | Category | Source |
|----------|-----|--------|----------|--------|
| **Critical** | Three-direction type coercion (Converter) | High | Feature Parity | NQ/LF |
| **Critical** | Mutation-triggered cache invalidation | Medium | Feature Parity | NQ/LF (adapted) |
| **High** | Auto-pagination (fetchAll()) | Low | Feature Parity | NQ/LF |
| **High** | Structured error handling with severity | Medium | Feature Parity | NQ/LF |
| **High** | Misconfigured autoload-dev namespace | Low | Quality | NQ/LF |
| **Medium** | Parent context enforcement (validateFetch) | Low | Feature Parity | NQ/LF |
| **Medium** | WRITEONLY property constant | Low | Feature Parity | NQ |
| **Medium** | Multi-scope rate limiter architecture | Medium | Feature Parity | LF |
| **Medium** | Replace hassankhan/config dependency | Medium | Quality | NQ/LF |
| **Low** | devMode validateConstants() | Low | Feature Parity | NQ/LF |
| **Low** | Configuration::reset() for test isolation | Trivial | Feature Parity | LF |
| **Low** | Fix EntityMap::overload() typo bug | Trivial | Quality (Bug) | Bug fix |
| **Low** | Fix hardcoded PAYMO_DEVELOPMENT_MODE | Trivial | Quality (Bug) | Bug fix |

### Paymo Advantages to Preserve

These features are unique to paymo-api-php and represent superior implementations absent from both peer packages.

| Feature | Description | Why It Matters |
|---------|-------------|----------------|
| **Recursive include hydration** | `_hydrateInclude()` creates typed resource/collection instances for included data. `$project->client` returns a Client with CRUD methods, dirty tracking, and type safety. | NQ puts includes in $unlisted (raw data). LF has partial hydration. Paymo's depth is unmatched. |
| **Server-side HAS filtering** | `Resource::has()` compiles to API-level relationship count filters, avoiding over-fetching. | NQ/LF use client-side post-filtering only. Paymo's approach is a significant performance advantage. |
| **Retry-After header support** | `waitForRetry()` reads Retry-After header before falling back to exponential backoff. Also reads x-ratelimit-* headers for proactive throttling. | NQ/LF go straight to calculated backoff. Paymo is more responsive to actual API rate-limit state. |

### White Space (Cross-Package Gaps)

Areas where all three packages are weak or absent.

| White Space | Assessment | Priority |
|-------------|-----------|----------|
| **PSR-3 logging** | All use custom Log singletons. No PSR-3 LoggerInterface support. Consumers cannot plug in Monolog etc. Genuine opportunity. | Low-Medium effort |
| **Standard testing (PHPUnit)** | All use custom test frameworks. No code coverage, no CI/CD integration. Custom framework is well-designed for API testing but not compatible with standard workflows. | High effort (hybrid approach needed) |
| **Async/concurrent requests** | All execute HTTP requests sequentially. No Guzzle Pool/Promise support. Rate limiter assumes sequential execution. | High effort (rate limiter redesign) |
| **Middleware pipeline** | Request pipeline is hardcoded in execute(). No consumer hook points for custom headers, audit logging, metrics. | Low priority (current pipeline covers actual use cases) |
| **Batch operations** | No batch create/update/delete. API-constrained — underlying APIs likely don't support batch operations. | Low priority (API limitation) |

### Threat Assessment

These are architectural risks, not competitive threats (all packages serve the same team).

**Threat 1: Dependency Rot (hassankhan/config).** Paymo depends on a library last updated 2021. NQ/LF dropped it. If it becomes incompatible with future PHP versions, Paymo is uniquely exposed. Mitigation: replace with direct adbario/php-dot-notation usage (already a recommended adoption).

**Threat 2: PHP Version Floor Gap.** Paymo requires PHP >=7.4 (EOL November 2022). NQ/LF require >=8.1. Paymo cannot use enums, readonly properties, fibers, or intersection types. Some adoption candidates (ErrorSeverity as native enum) may require PHP 8.1+ as a prerequisite. Mitigation: bump minimum to PHP 8.1 as part of improvement roadmap.

**Threat 3: Divergence Accumulation.** Each new package (NQ, then LF days later) widens the gap. LF already fixes NQ bugs that Paymo doesn't have yet. The longer backporting is deferred, the larger the cumulative effort. Mitigation: establish a backporting practice — when a new package introduces a pattern improvement, backport to Paymo in the same development cycle.

---

## Strategic Recommendations

### Positioning: Paymo as the Reference Implementation

paymo-api-php should be positioned as the **reference implementation** for this SDK family — the package where all proven patterns converge and scale is validated. It has the largest resource count (38 vs. 10 and 6), full CRUD scope, and three unique features absent from both peers. The peer packages are innovation labs; Paymo is the production standard.

**What to avoid:** Treating Paymo as "the old one" that needs to catch up. The peer packages have features Paymo lacks, but Paymo has the most comprehensive API coverage and the most sophisticated include hydration. The adoption should be framed as "integrating proven innovations" not "fixing a legacy package."

### Differentiation Strategy

paymo-api-php's sustainable differentiation rests on three pillars:

1. **Scale-proven patterns.** At 38 resources, any pattern adopted into Paymo is validated at 4-6x the resource count of the peer packages. Adaptations needed for scale (e.g., scoped cache invalidation instead of full-wipe) become improvements the peer packages can later adopt.

2. **Deep include hydration.** Recursive typed entity hydration for includes is Paymo's most significant DX advantage. This should be preserved and highlighted. If NQ adopts this pattern (backport opportunity identified in the analysis), Paymo should be the source.

3. **Header-aware rate limiting.** Paymo's approach of reading actual API rate-limit headers is technically superior to NQ/LF's hardcoded limits. The ideal rate limiter combines Paymo's header-awareness with LF's multi-scope architecture.

### Adoption Roadmap

Ordered by recommended implementation sequence. Each phase can be completed independently.

**Phase 1: Immediate Bug Fixes (Trivial effort, zero risk)**
- Fix `EntityMap::overload()` typo: `AbstractResourcce` to `AbstractResource` (1 character)
- Fix hardcoded `PAYMO_DEVELOPMENT_MODE = true` to respect `devMode` config key (1 line)
- These are bugs, not feature requests. No behavioral change for correct configurations.

**Phase 2: Quick Wins (Low effort, high cumulative value)**
- Add `fetchAll()` auto-pagination to AbstractCollection (~20 lines, highest ROI adoption candidate)
- Add `validateFetch()` to FileCollection, BookingCollection, InvoiceItemCollection, EstimateItemCollection (~60 lines across 4-6 classes)
- Add `validateConstants()` in AbstractEntity constructor, gated by devMode (~30 lines)
- Add `Configuration::reset()` for test isolation (~5 lines)
- Fix autoload-dev namespace to `Jcolombo\PaymoApiPhp\Tests\` (namespace rename + ~30 file updates)
- Add Composer test scripts (test, test:dry-run, test:verbose) matching NQ/LF conventions

**Phase 3: Medium-Term Architecture (Medium effort, high impact)**
- Adopt ErrorSeverity enum and per-severity handler dispatch. Requires PHP 8.1+ for native enum (or use string constants for PHP 7.4 compatibility). Add handler configuration to config file.
- Implement mutation-aware caching with resource-scoped invalidation. Hook into Paymo::execute() after POST/PUT/DELETE. Invalidate cache keys matching the mutated resource's URL pattern (not full-wipe — Paymo's 38 resources need scoped invalidation).
- Add WRITEONLY property constant to AbstractResource. Audit Paymo API documentation for action-trigger properties.
- Replace hassankhan/config with direct adbario/php-dot-notation usage, rewriting Configuration.php to match NQ/LF pattern.

**Phase 4: Long-Term Type System (High effort, highest DX impact)**
- Extend Converter with three-direction type coercion. Phased rollout:
  - Phase 4a: `convertToPhpValue()` for datetime and boolean types only (highest-frequency pain points)
  - Phase 4b: `convertForRequest()` for serialization validation before API calls
  - Phase 4c: Enum validation in devMode
- Must be backwards-compatible: existing code that reads datetime strings cannot break
- Consider bumping PHP minimum to 8.1 before this phase to enable native enums and typed properties
- Refactor RateLimiter to support named scopes with independent configurations while preserving header-awareness (combines Paymo's header-responsiveness with LF's scope flexibility)

### Competitive Response Considerations

Since all packages serve the same team, "competitive response" means: how do changes to Paymo affect the other packages?

**Forward propagation:** Innovations in Paymo (e.g., scoped cache invalidation vs. full-wipe) become candidates for adoption by NQ/LF. Paymo's larger resource count serves as a proving ground for patterns that benefit all packages.

**Backport pipeline:** When Generation 4 is built, the gap analysis from this report should be refreshed. Establish the practice: new package innovations get backported to Paymo within the same development cycle, preventing the divergence accumulation identified as Threat 3.

**Bidirectional opportunities:** Three Paymo advantages should be considered for forward-porting to NQ/LF:
1. Recursive include hydration (high value for NQ, which currently puts includes in $unlisted)
2. Server-side HAS filtering (API-dependent — only viable if the target API supports it)
3. Retry-After header support (universally applicable, low effort)

---

## Methodology Notes

### Sources Consulted

- **Primary:** Direct source code analysis of all three packages via local filesystem access (full source code available for every file referenced)
- **Files analyzed:** composer.json, all AbstractEntity/AbstractResource/AbstractCollection base classes, all utility classes (Cache, ScrubCache, Log, RateLimiter, Error, Converter, Configuration, EntityMap, RequestCondition, RequestResponse), all resource classes, all collection classes, test framework infrastructure, default config files, OVERRIDES.md
- **Git history:** Used to establish build timeline and evolutionary relationship (pipeline-deals-api 2014, paymo 2020, NQ April 4 2026, LF April 7 2026)

### Sources Unavailable

- No external web research was performed. All analysis is based on source code inspection.
- No performance benchmarks. Claims about scale impact (e.g., "full-wipe ScrubCache won't scale to 38 resources") are inferred from code structure, not measured.
- No end-user feedback. Developer experience assessments are based on API surface analysis, not user interviews.

### Data Recency

All data reflects codebase state as of April 8-9, 2026. NQ and LF are both pre-release (v0.x and v0.1.0) — their patterns may evolve before stable release.

### Analysis Limitations

1. **Scale inference, not measurement.** Claims that patterns "don't scale to 38 resources" are based on algorithmic analysis (full cache wipe = O(n) invalidation), not benchmarks against real data volumes.
2. **Write-path gaps in LF.** LF is read-only, so dirty tracking, WRITEONLY, and CREATEONLY assessments for LF are theoretical — the code exists but isn't exercised in production paths.
3. **Paymo Error.php depth.** Error handling assessment is based on the execute() pipeline and RequestResponse, not the full Error class internals. Additional functionality may exist.
4. **NQ include hydration depth.** Assessment that NQ has no recursive include hydration is based on absence of `_hydrateInclude()` equivalent in explored code. If NQ handles includes in an unexplored method, this finding would need revision.
5. **WRITEONLY applicability.** Whether the Paymo API has action-trigger properties (making WRITEONLY valuable vs. theoretical) was not verified against the Paymo API documentation.

### Competitor Categories

| Category | Count | Packages |
|----------|-------|----------|
| Direct peers (full analysis) | 2 | niftyquoter-api-php, leadfeeder-api-php |
| Adjacent (noted, not analyzed) | 1 | pipeline-deals-api (Generation 0, too dated for adoption) |
| Indirect (noted, not analyzed) | 1 | node-paymo-api (TypeScript, different ecosystem) |
| Excluded | 2 | react-hook-google-one-tap (different domain), _tokyo-demo (prototype) |

### Confidence Assessment

| Dimension | Confidence | Basis |
|---|---|---|
| Architecture Patterns | High | Complete source code access. All base classes read in full. |
| Feature Completeness | High | All utility classes read across all three packages. |
| Developer Experience | High | Public APIs verified against implementations. |
| Testing | High | Test directories fully inventoried. Runners and base classes read. |
| Configuration | High | All config files and Configuration classes compared. |
