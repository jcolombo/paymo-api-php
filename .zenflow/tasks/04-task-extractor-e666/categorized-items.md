# Categorized Items

**Date:** 2026-04-09
**Extraction Depth:** Full Extraction
**Input:** 68 raw items from `raw-extractions.md`

---

## Summary

- **Total categorized items:** 67 (68 raw - 1 deduplication merge)
- **Software Enhancement Tasks:** 56 items
- **General Action Items:** 0 items
- **Ideas & Suggestions:** 11 items
- **Items deduplicated:** 1 (S3-27 merged into S3-13 — same devMode bug fix)
- **Items that shifted from preliminary domain signal:** 0

### Subgroups Used

**Software Enhancement Tasks** (10 subgroups):
| Subgroup | Count |
|----------|-------|
| Bug Fixes | 3 |
| Property & Type System | 8 |
| Query System (Includes, Filters, Pagination) | 5 |
| Caching System | 3 |
| Error Handling & Logging | 2 |
| Architecture & Dependencies | 6 |
| API Feature Support | 3 |
| Developer Experience Conveniences | 2 |
| Documentation Updates | 5 |
| Investigation & Verification | 19 |

**Ideas & Suggestions** (3 subgroups):
| Subgroup | Count |
|----------|-------|
| Process & Maintenance | 4 |
| Strategic Direction | 2 |
| Architecture Exploration | 5 |

---

## Software Enhancement Tasks

### Bug Fixes

- **Fix EntityMap::overload() typo bug** *(S3-12)*
  - Context: Typo checks for "AbstractResourcce" (extra 'c') instead of "AbstractResource" — validation never matches, so overload validation is effectively disabled
  - System: paymo-api-php — `EntityMap.php`
  - Scope: 1-character fix
  - Source: Competitive analysis — Gap Inventory, Adoption Roadmap Phase 1

- **Fix hardcoded PAYMO_DEVELOPMENT_MODE** *(S3-13 + S3-27 merged)*
  - Context: `PAYMO_DEVELOPMENT_MODE` is hardcoded to `true` in `Paymo.php:62`, leaking error details to stdout in all environments. Should respect the `devMode` config key instead
  - System: paymo-api-php — `Paymo.php`
  - Scope: 1-line fix. The competitive analysis roadmap specifically calls this out as a standalone Phase 1 fix independent of structured error handling (S3-04)
  - Source: Competitive analysis — Gap Inventory, Adoption Roadmap Phase 1; re-emphasized in roadmap positioning (S3-27)

- **Fix autoload-dev namespace misconfiguration** *(S3-05)*
  - Context: `composer.json` autoload-dev maps `Jcolombo\\PaymoApiPhp\\` → `tests/` instead of `Jcolombo\\PaymoApiPhp\\Tests\\` → `tests/`. Test classes appear in IDE autocompletion alongside production classes, static analysis processes tests as production code, and `--no-dev` Composer flag cannot cleanly exclude test code
  - System: paymo-api-php — `composer.json`, ~30 test files
  - Scope: composer.json namespace edit + ~30 test file namespace declarations updated + add Composer test scripts (`test`, `test:dry-run`, `test:verbose`)
  - Source: Competitive analysis — Gap Inventory, Adoption Roadmap Phase 2
  - Dependencies: Partially overlaps with S3-14 (Composer test scripts)

### Property & Type System

- **Add undocumented properties to existing SDK resources** *(S1-02)*
  - Context: 60+ properties discovered via SDK testing are returned by the live API but absent from PROP_TYPES. Major clusters: Company (30+ including email templates, SMTP config, payment gateway fields), Booking (5), Task (6), User (5), plus scattered properties across Client, ClientContact, Estimate, Expense, Invoice, InvoiceItem, EstimateItem, Report, Tasklist, TimeEntry, ProjectTemplateTask, EstimateTemplate, InvoiceTemplate
  - System: paymo-api-php — 15+ resource classes in `src/Entity/Resource/`
  - Scope: Add properties to PROP_TYPES constants, determine settability (READONLY vs writable) for each. Some are essential foreign keys (InvoiceItem.invoice_id, EstimateItem.estimate_id — already in OVERRIDE-007). Some are sensitive (Company Stripe keys, SMTP config)
  - Source: Research report — Thread 2 "Undocumented Properties by Resource"
  - Dependencies: S1-10 (systematic property discovery) would produce a definitive list; S1-25 (SDK vs API comparison) is the verification step

- **Fix Session.id type mismatch** *(S1-03)*
  - Context: Session.id is a hex string token, not an integer — unique among all resources. Already documented as OVERRIDE-004
  - System: paymo-api-php — `Session.php`
  - Scope: Verify PROP_TYPES declares `id` as `text` type
  - Source: Research report — Thread 2 "Type Mismatches"

- **Handle deprecated language properties** *(S1-04)*
  - Context: Invoice, Estimate, and RecurringProfile have deprecated `language` properties (OVERRIDE-012). No removal timeline from Paymo
  - System: paymo-api-php — `Invoice.php`, `Estimate.php`, `RecurringProfile.php`
  - Scope: Verify these are marked READONLY to prevent consumers from setting them
  - Source: Research report — Thread 2 "Deprecated Properties"

- Add `download_token` property to Expense and Report PROP_TYPES — needed for downloading expense receipts and report exports *(S1-28)*

- **Implement three-direction type coercion (Converter)** *(S3-01)*
  - Context: The single largest DX gap. Every consumer must manually cast types (`strtotime($project->created_on)` instead of accessing a `DateTimeImmutable`). NQ and LF both implement three-direction coercion. Must be backwards-compatible — existing code reading datetime strings cannot break
  - System: paymo-api-php — `Converter.php`, hydration pipeline for all 38 resources
  - Scope: Phase 1 — datetime + boolean coercion on hydration (`convertToPhpValue()`). Phase 2 — full three-direction coercion for serialization (`convertForRequest()`). Phase 3 — enum validation in devMode (see S3-29). May require PHP 8.1+ minimum (see S3-17)
  - Source: Competitive analysis — Key Findings [Critical], Adoption Roadmap Phase 4
  - Dependencies: S3-17 (PHP 8.1 bump) for native enums in Phase 3; S3-29 (enum validation) is Phase 3 of this item

- **Add devMode enum validation in Converter** *(S3-29)*
  - Context: Phase 3 of the type coercion work. Validates set values against allowed enum values before API calls, catching invalid values at SDK level instead of surfacing as API 400 errors. Examples: Invoice status (draft/sent/viewed/paid/void), RecurringProfile frequencies
  - System: paymo-api-php — `Converter.php`, per-resource enum definitions
  - Scope: Enum definitions per resource, validation logic in Converter, devMode gating
  - Source: Competitive analysis — Key Findings [Critical], Comparison Matrix
  - Dependencies: S3-01 (three-direction type coercion) Phase 1-2 should be complete first; S3-17 (PHP 8.1) for native enum support

- **Add WRITEONLY property constant** *(S3-07)*
  - Context: NQ introduced WRITEONLY for properties that trigger actions but aren't returned in responses (e.g., action-trigger properties). Whether the Paymo API has such properties needs investigation (see S3-28)
  - System: paymo-api-php — `AbstractResource.php`, potentially specific resource classes
  - Scope: Constant definition in AbstractResource, integration with dirty tracking and serialization, audit for applicable Paymo properties
  - Source: Competitive analysis — Gap Inventory, Adoption Roadmap Phase 3
  - Dependencies: S3-28 (audit for applicable properties) determines whether this adds value

- **Audit for WRITEONLY-applicable Paymo properties** *(S3-28)*
  - Context: Whether the WRITEONLY constant (S3-07) is useful depends entirely on whether the Paymo API has properties that trigger server-side actions without being returned in responses
  - System: paymo-api-php — Paymo API documentation review
  - Scope: Documentation review to identify action-trigger properties. If none qualify, S3-07 is a no-op
  - Source: Competitive analysis — Adoption Roadmap Phase 3, Analysis Limitations

### Query System (Includes, Filters, Pagination)

- **Support partial_include syntax in SDK** *(S1-05)*
  - Context: `?partial_include=key(field1,field2)` allows selecting specific fields from included resources rather than fetching all fields. A documented API feature that reduces response payload size. Per-resource field support is unverified
  - System: paymo-api-php — query parameter builder, include system
  - Scope: New query parameter builder method, integration with existing include system, per-resource field validation
  - Source: Research report — Thread 5 "Include Syntax"

- **Support nested include dot notation in SDK** *(S1-06)*
  - Context: `?include=tasks.entries` enables multi-level sideloading in a single API call. 2 levels confirmed in documentation; 3+ levels undocumented. The SDK's recursive include hydration is a key advantage, but the query builder doesn't support dot notation for requesting nested includes
  - System: paymo-api-php — query parameter builder, include system
  - Scope: Query parameter building for dot notation, recursive include request construction, depth validation
  - Source: Research report — Thread 5 "Include Syntax"
  - Dependencies: S1-14 (test nesting depth limits) informs the maximum depth to support

- Ensure the SDK's RequestCondition builder supports or documents the special `in(me)` syntax for filtering tasks by the currently authenticated user *(S1-24)*

- **Add fetchAll() auto-pagination** *(S3-03)*
  - Context: Highest ROI adoption candidate. Every consumer needing all records currently writes boilerplate pagination loops. NQ implements fetchAll() with do/while loop; LF adds FETCHALL_CAP safety cap. ~20 lines of code
  - System: paymo-api-php — `AbstractCollection.php`
  - Scope: New `fetchAll()` method with configurable safety cap (default 5,000), result-count < page-size detection for page-end, per-resource `FETCHALL_CAP` constant for override
  - Source: Competitive analysis — Key Findings [High], Adoption Roadmap Phase 2

- **Add parent context enforcement (validateFetch)** *(S3-06)*
  - Context: FileCollection, BookingCollection, InvoiceItemCollection, EstimateItemCollection require parent filters but enforcement is only documented (OVERRIDE-005), not enforced at runtime. NQ's `setParentContext()` + `validateFetch()` pattern validates before API calls
  - System: paymo-api-php — 4-6 collection classes
  - Scope: ~60 lines across 4-6 classes. New validateFetch() method, parent path URL prefixing, devMode gating for warnings vs errors
  - Source: Competitive analysis — Gap Inventory, Adoption Roadmap Phase 2

### Caching System

- **Implement mutation-triggered cache invalidation** *(S3-02)*
  - Context: Currently relies entirely on TTL-based expiry (300s default). In multi-step workflows (create invoice, add items, fetch invoice), stale cache returns pre-mutation data. Needs resource-scoped invalidation (not full-wipe) given 38 resources. Existing `ScrubCache` class is an in-memory include-validation cache (different concept despite shared name)
  - System: paymo-api-php — `Paymo.php` execute() method, cache subsystem
  - Scope: Hook into execute() after POST/PUT/DELETE, URL-pattern-based cache key matching, avoid naming collision with existing ScrubCache
  - Source: Competitive analysis — Key Findings [Critical], Adoption Roadmap Phase 3
  - Dependencies: S3-16 (3-callback registerCacheMethods) needed for custom backend clearing

- **Add cache-hit detection** *(S3-15)*
  - Context: No way to distinguish cached vs. fresh responses. LF added `fromCacheKey` property to fix a documented NQ bug. Useful for debugging and for consumers needing data freshness awareness
  - System: paymo-api-php — `RequestResponse.php`, cache fetch path
  - Scope: Property addition to RequestResponse, set during cache fetch path
  - Source: Competitive analysis — Comparison Matrix, LF Profile

- **Extend registerCacheMethods() to 3 callbacks** *(S3-16)*
  - Context: NQ/LF use 3-callback registration ($read, $write, $clear). The $clear callback is needed for mutation-triggered cache invalidation (S3-02) to work with custom cache backends
  - System: paymo-api-php — cache registration API
  - Scope: Add third callback parameter, use in invalidation path
  - Source: Competitive analysis — Comparison Matrix
  - Dependencies: Prerequisite for S3-02 (mutation-triggered invalidation)

### Error Handling & Logging

- **Implement structured error handling with severity levels** *(S3-04)*
  - Context: Consumers cannot distinguish recoverable warnings (429 rate limit) from terminal failures (401 authentication). No mechanism to route errors to different handlers. NQ/LF use ErrorSeverity enum (NOTICE/WARN/FATAL)
  - System: paymo-api-php — `Error.php`, config file schema
  - Scope: New ErrorSeverity enum, refactor Error.php from flat HTTP-status-to-prefix mapping to severity-based routing, add handler configuration to config file. Requires PHP 8.1+ for native enum (or string constants for 7.4 compat)
  - Source: Competitive analysis — Key Findings [High], Adoption Roadmap Phase 3
  - Dependencies: S3-17 (PHP 8.1 bump) for native enum support; S3-13 (devMode fix) should be applied first

- **Adopt PSR-3 logging interface** *(S3-18)*
  - Context: All three SDK family packages use custom Log singletons. PSR-3 is the PHP standard for logging interfaces. Replacing or wrapping the custom singleton allows consumers to plug in Monolog or other PSR-3 compatible loggers
  - System: paymo-api-php — logging subsystem
  - Scope: LoggerInterface implementation or adapter, config for logger injection, backwards-compatible default (existing Log behavior preserved when no PSR-3 logger injected)
  - Source: Competitive analysis — White Space "PSR-3 logging"

### Architecture & Dependencies

- **Refactor rate limiter to support named scopes** *(S3-08)*
  - Context: LF has 4 scopes (export 5/min, account 100/min, token 100/min, ipenrich 60/min). Paymo currently has single sliding window with 5 req/5s. Paymo's Retry-After header support is an advantage to preserve. The ideal rate limiter combines scoping with header-awareness
  - System: paymo-api-php — `RateLimiter.php`
  - Scope: RateLimiter refactor for named scopes with independent rate configs, preserve existing header parsing
  - Source: Competitive analysis — Gap Inventory, Adoption Roadmap Phase 4

- **Replace hassankhan/config dependency** *(S3-09)*
  - Context: hassankhan/config (Noodlehaus — last updated 2021) wraps php-dot-notation with unused YAML/XML/INI format support. NQ/LF both use `adbario/php-dot-notation` directly. If hassankhan/config becomes incompatible with future PHP versions, Paymo is uniquely exposed
  - System: paymo-api-php — `Configuration.php`, `composer.json`
  - Scope: Rewrite Configuration.php to use Adbar\Dot directly, swap dependency in composer.json, verify test compatibility
  - Source: Competitive analysis — Gap Inventory, Threat Assessment "Dependency Rot", Adoption Roadmap Phase 3

- **Add devMode validateConstants()** *(S3-10)*
  - Context: Validates PROP_TYPES, READONLY, CREATEONLY, UNSELECTABLE, REQUIRED_CREATE, and INCLUDE_TYPES constants are consistent at instantiation time (e.g., no READONLY properties in REQUIRED_CREATE, no unknown types in PROP_TYPES). NQ introduced this in devMode; LF calls it in every constructor
  - System: paymo-api-php — `AbstractEntity.php`
  - Scope: ~30 lines. Validation logic, constant cross-referencing, devMode gating
  - Source: Competitive analysis — Gap Inventory, Adoption Roadmap Phase 2

- Add `Configuration::reset()` method that destroys the singleton instance for clean test isolation between test cases — ~5 lines, trivial effort *(S3-11)*

- **Bump PHP minimum to 8.1** *(S3-17)*
  - Context: PHP 7.4 reached EOL November 2022 (3+ years ago). NQ/LF both require >=8.1. Bumping enables native enums (for ErrorSeverity), readonly properties, fibers, intersection types
  - System: paymo-api-php — `composer.json`, codebase audit
  - Scope: composer.json change, audit for PHP 7.4-only syntax, potential adoption of 8.1 features
  - Source: Competitive analysis — Threat Assessment "PHP Version Floor Gap", Adoption Roadmap Phase 4
  - Dependencies: Prerequisite for S3-01 Phase 3 (enum coercion), S3-04 (ErrorSeverity enum), S3-29 (enum validation)

- Add Composer script shortcuts for test execution (`test`, `test:dry-run`, `test:verbose`) matching NQ/LF conventions — composer.json additions *(S3-14)*

### API Feature Support

- **Implement leave management resources in SDK** *(S1-01)*
  - Context: 4 undocumented endpoints from PR #30 (2017): CompanyDaysException (`/api/companiesdaysexceptions`), UserDaysException (`/api/usersdaysexceptions`), LeaveType (`/api/leavetypes`), StatsReport (`/api/statsreports`). Full CRUD for first three; POST-only for StatsReport. Corresponds to Paymo's Leave Planner feature
  - System: paymo-api-php — 4 new resource classes in `src/Entity/Resource/`
  - Scope: 4 new resource classes with PROP_TYPES, CRUD restrictions, filter requirements. StatsReport has a unique response structure
  - Source: Research report — Thread 1 "Undocumented Resources"
  - Dependencies: S1-11 (live testing) is a prerequisite — endpoints may have evolved since 2017

- **Add Report PDF/XLSX export support** *(S1-08)*
  - Context: The API supports `application/pdf` and `application/vnd.ms-excel` content types for Report, Invoice, and Estimate export. The SDK currently doesn't support binary content type responses or export-specific endpoints
  - System: paymo-api-php — HTTP response handling, new export methods
  - Scope: Binary response handling, content-type negotiation, file download methods
  - Source: Research report — Thread 1 "Report", API Infrastructure "Content Types"

- **Add webhook conditional filtering builder** *(S1-09)*
  - Context: Webhooks support a `where` parameter for conditional filtering and HMAC-SHA1 signatures via a `secret` parameter. Allows webhooks to fire only for specific conditions
  - System: paymo-api-php — `Webhook.php`, new builder methods
  - Scope: Fluent builder pattern for webhook WHERE conditions, condition serialization for webhook context
  - Source: Research report — API Infrastructure "Webhooks"

### Developer Experience Conveniences

- Add `flatten($property)` method to AbstractCollection for single-property extraction from all items (e.g., `$projects->flatten('name')` returns array of names) — matches NQ/LF conventions *(S3-32)*

- Add `toArray()` and `toJson()` convenience methods to AbstractResource and AbstractCollection — wrapper methods around existing `flatten()` and `JsonSerializable` functionality, matching NQ/LF conventions *(S3-33)*

### Documentation Updates

- **Document or mitigate 2,500-item include truncation** *(S1-07)*
  - Context: Included resource collections are silently capped at 2,500 items — no error returned, data silently dropped. Significant data integrity risk for collections like InvoiceItems, TimeEntries, or Tasks. Issue #68 on Paymo API repo is open
  - System: paymo-api-php — SDK documentation, optionally collection count warning logic
  - Scope: Documentation update in CLAUDE.md/OVERRIDES.md, optional collection count warning when included collections approach the cap, possible pagination-based workaround documentation
  - Source: Research report — Thread 5 "Known Include Issues", Thread 3, Key Findings

- Document that Task and TimeEntry `description` fields may contain HTML tags (`<p>` etc.) when content is entered via the Paymo web interface — confirmed by Paymo (Issue #50), by design *(S1-19)*

- Document that `retainer_id` appears on Project objects but the Retainer API is explicitly not public — no endpoint to resolve retainer data (confirmed by Paymo staff, Issue #66) *(S1-20)*

- Document that webhook delete payloads only contain `{"id": <ID>}` (no other properties) and update payloads have no changed-fields diff — API-side limitations (Issues #33, #38) *(S1-26)*

- **Consider handling sensitive Company properties** *(S1-30)*
  - Context: Company resource returns sensitive data (op_stripe_secret_key, Authorize.net credentials, custom SMTP config) in API responses. SDK consumers should be aware these are present
  - System: paymo-api-php — SDK documentation, possibly PROP_TYPES annotations
  - Scope: Documentation note and/or annotation in PROP_TYPES indicating sensitive fields
  - Source: Research report — Thread 2 "Undocumented Properties"

### Investigation & Verification

- **Systematic property discovery via live API** *(S1-10)*
  - Context: Highest priority investigation. Current 60+ undocumented property list reflects only SDK testing encounters. A systematic approach (GET each resource without `?select=` and compare response against known properties) would produce a definitive inventory
  - System: paymo-api-php — all 38 resource classes
  - Scope: Test script creation, response comparison tooling, per-resource property diff
  - Source: Research report — Recommendations Priority 1

- **Live test leave management endpoints** *(S1-11)*
  - Context: 4 PR #30 endpoints documented from 2017 (MEDIUM confidence). Paymo's Leave Planner has evolved since then. Live testing is a prerequisite before implementing SDK resource classes (S1-01)
  - System: paymo-api-php — test scripts for CompanyDaysException, UserDaysException, LeaveType, StatsReport
  - Scope: Test 4 endpoints for current functionality, property comparison against PR #30 documentation
  - Source: Research report — Recommendations Priority 1
  - Dependencies: Prerequisite for S1-01 (implement leave resources)

- **Build filter operator validation matrix** *(S1-12)*
  - Context: Documentation describes general filter syntax but doesn't specify which operators work on which properties for most resources. Only 11 of 38 resources have SDK-defined WHERE_OPERATIONS restrictions. Starting with Project, Client, TimeEntry (most restrictions)
  - System: paymo-api-php — `RequestCondition.php`, per-resource WHERE_OPERATIONS constants
  - Scope: Systematic test script, per-resource per-property operator testing, results update WHERE_OPERATIONS constants
  - Source: Research report — Recommendations Priority 1

- **Spot-check SDK-only include relationships** *(S1-13)*
  - Context: SDK INCLUDE_TYPES maps 98 relationships (corrected from 82 by doc audit); documentation alone reveals ~73. The delta of ~25 SDK-only relationships should be verified against live API. Also test whether undocumented leave resources support includes
  - System: paymo-api-php — include system verification
  - Scope: Targeted include tests for specific resource/key combinations
  - Source: Research report — Recommendations Priority 2

- Test 3-level and 4-level nested includes (e.g., `?include=tasks.entries.user`) to determine actual maximum nesting depth — 2 levels confirmed in docs, deeper untested *(S1-14)*

- Probe `GET /api/currencies` to determine if this is a live endpoint — `currencies.md` exists in docs repo but no live evidence (LOW confidence). If real, add Currencies resource class *(S1-15)*

- Test RecurringProfileItem and other multi-word compound resource names for response key anomalies matching the underscore insertion pattern (OVERRIDE-009) *(S1-16)*

- **Verify per-resource HAS condition support** *(S1-17)*
  - Context: HAS filtering (server-side relationship count filtering) is one of Paymo SDK's three unique advantages over peers. Generic syntax is documented but no per-resource support matrix exists
  - System: paymo-api-php — filter system
  - Scope: Systematic per-resource HAS tests against known include relationships, results as a support matrix
  - Source: Research report — Thread 4 "HAS Conditions", Knowledge Gaps

- Investigate whether StatsReport supports additional report types beyond `user_annual_leave_stats` and `user_working_days_count` documented in PR #30 *(S1-18)*

- Investigate whether the 2,500-item include truncation threshold varies by resource type, account tier, or other factors *(S1-21)*

- Determine conditions under which Company properties `apply_tax_to_expenses` and `tax_on_tax` are present in API responses — currently OVERRIDE-002 with MEDIUM confidence *(S1-22)*

- **Test filter support for undocumented properties** *(S1-23)*
  - Context: Gap between "properties that exist" and "properties that are filterable" is unknown for undocumented properties like `billing_type`, `cover_file_id`, `files_count`
  - System: paymo-api-php — filter system
  - Scope: Systematic filter tests across undocumented properties
  - Source: Research report — Thread 4 "Remaining gaps"

- **Compare SDK resource classes against full API inventory** *(S1-25)*
  - Context: The systematic verification step connecting research findings to actionable SDK changes. Would produce the definitive list of property additions, corrections, and removals across all 38 resources
  - System: paymo-api-php — all 38 resource classes
  - Scope: 38-resource comparison, per-resource property diff, output as a change manifest flagging: (a) API properties missing from SDK, (b) SDK features not in API, (c) type mismatches
  - Source: Research report — Recommendations Priority 2

- Verify whether Estimate has a `delivery_date` property (present on Invoice but unconfirmed on Estimate) — marked "?" in research, needs live API check *(S1-27)*

- Determine specific rate limit values (requests per time period) for the Paymo API — response headers exist but actual limits undocumented, SDK uses conservative 5 req/5s default *(S1-29)*

- **Reconcile include relationship count (82 vs 98)** *(S2-03)*
  - Context: Research report claims 82 include relationships; doc audit verification found 98 from SDK source code. The 16 missing relationships should be identified and research findings updated
  - System: paymo-api-php — include system, research report accuracy
  - Scope: Compare the 82-row include map from the research against actual SDK INCLUDE_TYPES constants across all resources
  - Source: Doc audit — Verification Results

- Verify CommentThread CRUD semantics — already corrected during doc audit (delete() supported, update() not supported because all properties READONLY). No further action unless live testing reveals different behavior *(S2-04)*

- Evaluate whether any Paymo API resources exist only as hydrated includes (never fetched directly) — if so, add `INCLUDE_ONLY` constant following LF's pattern *(S3-25)*

- Verify Paymo's retry parameters are optimal and consider making them configurable via config file — current logic (4 attempts, Retry-After + backoff with jitter) is already more sophisticated than NQ/LF *(S3-30)*

---

## General Action Items

No general action items found in the provided materials. All input sources address software SDK development exclusively — no operational, staffing, communication, or non-technical tasks were identified.

---

## Ideas & Suggestions

### Process & Maintenance

- Maintain `docs/gap-matrix.md` as a living document — add a last-audited date field and update whenever resources, properties, or overrides change. Without maintenance, it will become stale like the API docs it was built to address *(S2-01)*

- Research and adopt documentation linting tools for the project's markdown files — identified during the doc audit as "unresearched — recommend manual review" *(S2-02)*

- Future OVERRIDE updates should include a cross-document consistency check across CLAUDE.md, OVERRIDES.md, and PACKAGE-DEV.md — learned when PACKAGE-DEV.md's UNSELECTABLE table had to be updated from 5-resource/7-property to 6-resource/32-property to match corrected OVERRIDE-013 *(S2-05)*

- Establish a backporting practice where innovations in new packages (NQ, LF, future Gen 4) are backported to Paymo within the same development cycle to prevent divergence accumulation — each new package currently widens the gap *(S3-22)*

### Strategic Direction

- Position paymo-api-php as the reference implementation for the SDK family — the package where all proven patterns converge and scale is validated at 38 resources vs. 10 and 6 in peers. Frame adoption as "integrating proven innovations" not "fixing a legacy package" *(S3-23)*

- Consider forward-porting three Paymo advantages to NQ/LF: (1) recursive include hydration (high value for NQ), (2) server-side HAS filtering (API-dependent), (3) Retry-After header support (universally applicable, low effort) *(S3-24)*

### Architecture Exploration

- **Investigate adopting PHPUnit (hybrid approach)** *(S3-19)*
  - Context: Custom test framework is well-designed for API testing but lacks code coverage, CI/CD integration, and standard workflow compatibility. All three SDK family packages use custom frameworks
  - Scope: Hybrid approach preserving custom ResourceTestRunner for integration tests while adding PHPUnit for unit tests and coverage. High effort
  - Source: Competitive analysis — White Space "Standard testing (PHPUnit)"

- **Investigate async/concurrent request support** *(S3-20)*
  - Context: All three packages execute requests sequentially. Guzzle Pool/Promise support would enable concurrent HTTP requests for bulk operations. Rate limiter assumes sequential execution and would need redesign. API rate limits may constrain practical benefit
  - Scope: High effort — Guzzle Pool integration, rate limiter redesign, concurrent request management
  - Source: Competitive analysis — White Space "Async/concurrent requests"

- **Consider middleware pipeline for request lifecycle** *(S3-21)*
  - Context: Request pipeline is currently hardcoded in execute(). Consumer hook points would enable custom headers, audit logging, metrics collection, and other cross-cutting concerns. Low priority — current pipeline covers actual use cases
  - Scope: Pipeline abstraction, hook registration, execution order management
  - Source: Competitive analysis — White Space "Middleware pipeline"

- **Evaluate singleton fetch pattern** *(S3-26)*
  - Context: LF supports `fetchSingleton()` with no ID for single-instance resources, and list() throws for singleton resources. Company is the only Paymo resource that fits this pattern
  - Scope: New method on AbstractResource, Company-specific override, list() restriction
  - Source: Competitive analysis — LF Profile, Comparison Matrix

- Consider whether a separate test config file (like NQ's `niftyquoterapi.config.test.json`) improves test isolation vs. the current embedded `testing.*` approach in the main config — minor DX consideration *(S3-31)*

---

## Categorization Decisions

All items categorized cleanly according to the rules. No ambiguous Software-vs-General decisions were needed since all input sources are 100% software-focused.

### Near-ambiguous items resolved:

| Item | Considered | Placed In | Rationale |
|------|-----------|-----------|-----------|
| S2-01 (gap-matrix maintenance) | Software Enhancement vs Idea | Idea | Process recommendation with no defined trigger or owner — "consider adding a maintenance note" language |
| S2-05 (cross-doc consistency) | Software Enhancement vs Idea | Idea | Process learning, not an actionable item — "future updates should include" is advisory |
| S3-22 (backporting practice) | Software Enhancement vs Idea | Idea | Organizational practice, not a code change — "establish a practice" is process-level |
| S3-23 (reference implementation positioning) | Software Enhancement vs Idea | Idea | Strategic framing, not actionable code work |
| S3-26 (singleton fetch) | Software Enhancement vs Idea | Idea | Only one resource (Company) fits; source uses "evaluate" and "consider" language |
| S3-30 (retry tuning) | Idea vs Software Enhancement | Software Enhancement | Source describes a concrete action (expose config for existing parameters), not an open question |
| S1-30 (sensitive properties) | Idea vs Software Enhancement | Software Enhancement | Regardless of approach chosen (documentation or annotation), concrete output is needed |

---

## Deduplication Log

### Merged Items

| Merged Into | Absorbed From | Rationale |
|-------------|---------------|-----------|
| S3-13 (Fix hardcoded PAYMO_DEVELOPMENT_MODE) | S3-27 (Add hardcoded devMode as standalone bug fix) | S3-27 explicitly states it is "a re-emphasis of S3-13 in the roadmap context." Same bug, same 1-line fix. Merged S3-27's roadmap positioning context (Phase 1 standalone fix, independent of structured error handling) into S3-13's entry |

### Related but Distinct Items (Kept Separate)

| Item A | Item B | Relationship | Why Kept Separate |
|--------|--------|-------------|-------------------|
| S3-01 (three-direction type coercion) | S3-29 (devMode enum validation) | S3-29 is Phase 3 of S3-01 | Different implementation phases with different prerequisites; S3-29 depends on S3-01 Phases 1-2 being complete |
| S3-07 (WRITEONLY constant) | S3-28 (Audit WRITEONLY properties) | S3-28 determines if S3-07 is needed | S3-28 is a prerequisite investigation; S3-07 is the implementation. S3-07 may be a no-op if audit finds nothing |
| S3-05 (autoload-dev fix) | S3-14 (Composer test scripts) | S3-05 scope includes test scripts | The competitive analysis mentions them together but they are independent concerns (namespace vs scripts). S3-05's scope note includes scripts for convenience but S3-14 is listed separately in the gap inventory |
| S1-02 (add undocumented properties) | S1-10 (systematic property discovery) | S1-10 would produce S1-02's definitive input | S1-10 is the investigation; S1-02 is the implementation. S1-02 can begin with known properties before S1-10 completes |
| S1-01 (implement leave resources) | S1-11 (live test leave endpoints) | S1-11 is prerequisite for S1-01 | Different scopes: testing vs. implementation. S1-11 may reveal S1-01's scope is different than expected |
