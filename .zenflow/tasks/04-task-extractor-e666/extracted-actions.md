# Extracted Actions: paymo-api-php Development Scope

**Date:** 2026-04-09
**Extraction method:** Full extraction from three prior analysis documents
**Total items:** 67 (56 software enhancement tasks, 0 general action items, 11 ideas & suggestions)

---

## Summary & Key Findings

This document contains 67 actionable items extracted from three prior analyses of the **paymo-api-php** SDK:

1. A **Deep Research** inventory of the Paymo REST API surface area (35 resources, 60+ undocumented properties, 82+ include relationships)
2. A **Documentation Deep Dive** gap analysis identifying documentation fixes and new documents needed
3. A **Competitive Intelligence** comparison against two peer SDK packages (niftyquoter-api-php and leadfeeder-api-php)

**Domain:** 100% software development -- PHP SDK for the Paymo REST API. No general business or operational items were identified.

**Category breakdown:**

| Category | Count | Description |
|----------|-------|-------------|
| Software Enhancement Tasks | 56 | Bugs, features, architecture improvements, documentation updates, and investigation work |
| General Action Items | 0 | No non-software operational tasks found |
| Ideas & Suggestions | 11 | Strategic direction, process improvements, and architectural explorations for future consideration |

**Priority highlights:**
- **3 bug fixes** requiring immediate attention (1-character typo, hardcoded dev mode, misconfigured autoload)
- **2 critical architecture gaps** identified by competitive analysis (type coercion system, cache invalidation)
- **19 investigation items** that should precede implementation to avoid rework
- **fetchAll() auto-pagination** identified as highest ROI adoption candidate (~20 lines, removes boilerplate from every consumer)

---

## Input Sources

| Source | Content Type | Origin |
|--------|-------------|--------|
| `research-report.md` (Task 01) | API surface area research report -- exhaustive inventory of 35 resources, undocumented properties, include relationships, and behavioral deviations | Deep Research task |
| `doc-plan.md` (Task 02) | Documentation gap analysis -- itemized fixes for CLAUDE.md, OVERRIDES.md, PACKAGE-DEV.md, README.md, plus two planned new documents | Documentation Deep Dive task |
| `competitive-analysis.md` (Task 03) | Peer package comparison -- architecture, features, DX, testing, and configuration compared against niftyquoter-api-php (Gen 2) and leadfeeder-api-php (Gen 3) | Competitive Intelligence task |

**Note:** Most documentation fixes from Source 2 were already applied during that task's execution. Only follow-up items and unresolved work were extracted.

---

## Software Enhancement Tasks

### Bug Fixes

- **Fix EntityMap::overload() typo bug**
  - Context: Typo checks for "AbstractResourcce" (extra 'c') instead of "AbstractResource" -- overload validation is effectively disabled in all environments
  - System: paymo-api-php -- `EntityMap.php`
  - Scope: 1-character fix
  - Effort: Small | Value: High
  - Source: Competitive analysis

- **Fix hardcoded PAYMO_DEVELOPMENT_MODE**
  - Context: `PAYMO_DEVELOPMENT_MODE` is hardcoded to `true` in `Paymo.php:62`, leaking error details to stdout in all environments. Should respect the `devMode` config key instead
  - System: paymo-api-php -- `Paymo.php`
  - Scope: 1-line fix, standalone from structured error handling work
  - Effort: Small | Value: High
  - Source: Competitive analysis

- **Fix autoload-dev namespace misconfiguration**
  - Context: `composer.json` autoload-dev maps `Jcolombo\\PaymoApiPhp\\` instead of `Jcolombo\\PaymoApiPhp\\Tests\\`. Test classes appear in IDE autocompletion alongside production classes, static analysis processes tests as production code, and `--no-dev` cannot cleanly exclude test code
  - System: paymo-api-php -- `composer.json`, ~30 test files
  - Scope: Namespace edit in composer.json + ~30 test file namespace declarations updated + add Composer test scripts
  - Effort: Medium | Value: High
  - Source: Competitive analysis

### Property & Type System

- **Add undocumented properties to existing SDK resources**
  - Context: 60+ properties discovered via SDK testing are returned by the live API but absent from PROP_TYPES. Major clusters: Company (30+ including email templates, SMTP config, payment gateway fields), Booking (5), Task (6), User (5), plus scattered properties across Client, ClientContact, Estimate, Expense, Invoice, InvoiceItem, EstimateItem, Report, Tasklist, TimeEntry, ProjectTemplateTask, EstimateTemplate, InvoiceTemplate
  - System: paymo-api-php -- 15+ resource classes in `src/Entity/Resource/`
  - Scope: Add properties to PROP_TYPES constants, determine settability (READONLY vs writable) for each. Some are essential foreign keys (InvoiceItem.invoice_id, EstimateItem.estimate_id). Some are sensitive (Company Stripe keys, SMTP config)
  - Effort: Large | Value: High
  - Source: Research report
  - Dependencies: Systematic property discovery (below) would produce the definitive list; SDK vs API comparison (below) is the verification step

- **Implement three-direction type coercion (Converter)**
  - Context: The single largest developer experience gap. Every consumer must manually cast types (`strtotime($project->created_on)` instead of accessing a `DateTimeImmutable`). Both peer SDKs implement three-direction coercion. Must be backwards-compatible -- existing code reading datetime strings cannot break
  - System: paymo-api-php -- new `Converter.php`, hydration pipeline for all 38 resources
  - Scope: Phase 1 -- datetime + boolean coercion on hydration (`convertToPhpValue()`). Phase 2 -- full three-direction coercion for serialization (`convertForRequest()`). Phase 3 -- enum validation in devMode. May require PHP 8.1+ minimum
  - Effort: Large | Value: High
  - Source: Competitive analysis (Critical gap)
  - Dependencies: PHP 8.1 bump for Phase 3 native enums

- **Add devMode enum validation in Converter**
  - Context: Phase 3 of the type coercion work. Validates set values against allowed enum values before API calls, catching invalid values at SDK level instead of surfacing as API 400 errors. Examples: Invoice status (draft/sent/viewed/paid/void), RecurringProfile frequencies
  - System: paymo-api-php -- `Converter.php`, per-resource enum definitions
  - Scope: Enum definitions per resource, validation logic in Converter, devMode gating
  - Effort: Medium | Value: Medium
  - Source: Competitive analysis (Critical gap, Phase 3)
  - Dependencies: Three-direction type coercion Phases 1-2; PHP 8.1 for native enum support

- Verify Session.id PROP_TYPES declares `id` as `text` type (not integer) -- unique hex string token among all resources, already documented as OVERRIDE-004 | Effort: Small | Value: Medium

- Verify deprecated `language` properties on Invoice, Estimate, and RecurringProfile are marked READONLY to prevent consumers from setting them -- already documented as OVERRIDE-012 | Effort: Small | Value: Low

- Add `download_token` property to Expense and Report PROP_TYPES -- needed for downloading expense receipts and report exports | Effort: Small | Value: Medium

- **Add WRITEONLY property constant**
  - Context: Peer SDK (niftyquoter-api-php) introduced WRITEONLY for properties that trigger actions but aren't returned in responses. Whether the Paymo API has such properties needs investigation first
  - System: paymo-api-php -- `AbstractResource.php`, potentially specific resource classes
  - Scope: Constant definition, integration with dirty tracking and serialization, audit for applicable properties
  - Effort: Medium | Value: Low
  - Source: Competitive analysis
  - Dependencies: WRITEONLY property audit (below) determines whether this adds value

- Audit Paymo API for WRITEONLY-applicable properties (action-trigger properties not returned in responses) -- determines whether the WRITEONLY constant is needed or is a no-op | Effort: Small | Value: Medium

### Query System (Includes, Filters, Pagination)

- **Add fetchAll() auto-pagination**
  - Context: Highest ROI adoption candidate. Every consumer needing all records currently writes boilerplate pagination loops. Peer SDKs implement fetchAll() with do/while loop and safety cap. ~20 lines of code
  - System: paymo-api-php -- `AbstractCollection.php`
  - Scope: New `fetchAll()` method with configurable safety cap (default 5,000), result-count < page-size detection for page-end, per-resource `FETCHALL_CAP` constant for override
  - Effort: Small | Value: High
  - Source: Competitive analysis (High gap)

- **Add parent context enforcement (validateFetch)**
  - Context: FileCollection, BookingCollection, InvoiceItemCollection, EstimateItemCollection require parent filters but enforcement is only documented (OVERRIDE-005), not enforced at runtime. Peer SDK's `setParentContext()` + `validateFetch()` pattern validates before API calls
  - System: paymo-api-php -- 4-6 collection classes
  - Scope: ~60 lines across 4-6 classes. New validateFetch() method, parent path URL prefixing, devMode gating for warnings vs errors
  - Effort: Medium | Value: High
  - Source: Competitive analysis

- **Support partial_include syntax in SDK**
  - Context: `?partial_include=key(field1,field2)` allows selecting specific fields from included resources rather than fetching all fields. A documented API feature that reduces response payload size. Per-resource field support is unverified
  - System: paymo-api-php -- query parameter builder, include system
  - Scope: New query parameter builder method, integration with existing include system, per-resource field validation
  - Effort: Medium | Value: Medium
  - Source: Research report

- **Support nested include dot notation in SDK**
  - Context: `?include=tasks.entries` enables multi-level sideloading in a single API call. 2 levels confirmed in documentation; 3+ levels undocumented. The SDK's recursive include hydration is a key advantage, but the query builder doesn't support dot notation for requesting nested includes
  - System: paymo-api-php -- query parameter builder, include system
  - Scope: Query parameter building for dot notation, recursive include request construction, depth validation
  - Effort: Medium | Value: Medium
  - Source: Research report
  - Dependencies: Nested include depth testing (below) informs maximum depth to support

- Ensure the SDK's RequestCondition builder supports or documents the special `in(me)` syntax for filtering tasks by the currently authenticated user | Effort: Small | Value: Low

### Caching System

- **Implement mutation-triggered cache invalidation**
  - Context: Currently relies entirely on TTL-based expiry (300s default). In multi-step workflows (create invoice, add items, fetch invoice), stale cache returns pre-mutation data. Needs resource-scoped invalidation (not full-wipe) given 38 resources. Existing `ScrubCache` class is an in-memory include-validation cache (different concept despite shared name)
  - System: paymo-api-php -- `Paymo.php` execute() method, cache subsystem
  - Scope: Hook into execute() after POST/PUT/DELETE, URL-pattern-based cache key matching, avoid naming collision with existing ScrubCache
  - Effort: Large | Value: High
  - Source: Competitive analysis (Critical gap)
  - Dependencies: Extend registerCacheMethods() to 3 callbacks (below) needed for custom backend clearing

- **Extend registerCacheMethods() to 3 callbacks**
  - Context: Peer SDKs use 3-callback registration ($read, $write, $clear). The $clear callback is needed for mutation-triggered cache invalidation to work with custom cache backends
  - System: paymo-api-php -- cache registration API
  - Scope: Add third callback parameter, use in invalidation path
  - Effort: Small | Value: Medium
  - Source: Competitive analysis
  - Dependencies: Prerequisite for mutation-triggered cache invalidation

- **Add cache-hit detection**
  - Context: No way to distinguish cached vs. fresh responses. Useful for debugging and for consumers needing data freshness awareness
  - System: paymo-api-php -- `RequestResponse.php`, cache fetch path
  - Scope: Property addition to RequestResponse, set during cache fetch path
  - Effort: Small | Value: Medium
  - Source: Competitive analysis

### Error Handling & Logging

- **Implement structured error handling with severity levels**
  - Context: Consumers cannot distinguish recoverable warnings (429 rate limit) from terminal failures (401 authentication). No mechanism to route errors to different handlers. Peer SDKs use ErrorSeverity enum (NOTICE/WARN/FATAL)
  - System: paymo-api-php -- `Error.php`, config file schema
  - Scope: New ErrorSeverity enum, refactor Error.php from flat HTTP-status-to-prefix mapping to severity-based routing, add handler configuration. Requires PHP 8.1+ for native enum (or string constants for 7.4 compat)
  - Effort: Large | Value: High
  - Source: Competitive analysis (High gap)
  - Dependencies: PHP 8.1 bump for native enum support; devMode fix should be applied first

- **Adopt PSR-3 logging interface**
  - Context: All three SDK family packages use custom Log singletons. PSR-3 is the PHP standard for logging interfaces. Allows consumers to plug in Monolog or other PSR-3 compatible loggers
  - System: paymo-api-php -- logging subsystem
  - Scope: LoggerInterface implementation or adapter, config for logger injection, backwards-compatible default (existing Log behavior preserved when no PSR-3 logger injected)
  - Effort: Medium | Value: Medium
  - Source: Competitive analysis (White space)

### Architecture & Dependencies

- **Bump PHP minimum to 8.1**
  - Context: PHP 7.4 reached EOL November 2022 (3+ years ago). Both peer SDKs require >=8.1. Bumping enables native enums (for ErrorSeverity), readonly properties, fibers, intersection types
  - System: paymo-api-php -- `composer.json`, codebase audit
  - Scope: composer.json change, audit for PHP 7.4-only syntax, potential adoption of 8.1 features
  - Effort: Medium | Value: High
  - Source: Competitive analysis (Threat: PHP Version Floor Gap)
  - Dependencies: Prerequisite for type coercion Phase 3, structured error handling enum, enum validation

- **Replace hassankhan/config dependency**
  - Context: hassankhan/config (last updated 2021) wraps php-dot-notation with unused YAML/XML/INI format support. Both peer SDKs use `adbario/php-dot-notation` directly. Risk of incompatibility with future PHP versions
  - System: paymo-api-php -- `Configuration.php`, `composer.json`
  - Scope: Rewrite Configuration.php to use Adbar\Dot directly, swap dependency in composer.json, verify test compatibility
  - Effort: Medium | Value: Medium
  - Source: Competitive analysis (Threat: Dependency Rot)

- **Add devMode validateConstants()**
  - Context: Validates PROP_TYPES, READONLY, CREATEONLY, UNSELECTABLE, REQUIRED_CREATE, and INCLUDE_TYPES constants are consistent at instantiation time (e.g., no READONLY properties in REQUIRED_CREATE, no unknown types in PROP_TYPES)
  - System: paymo-api-php -- `AbstractEntity.php`
  - Scope: ~30 lines. Validation logic, constant cross-referencing, devMode gating
  - Effort: Small | Value: Medium
  - Source: Competitive analysis

- **Refactor rate limiter to support named scopes**
  - Context: Peer SDK (leadfeeder-api-php) has 4 scopes (export 5/min, account 100/min, etc.). Paymo currently has single sliding window with 5 req/5s. Paymo's Retry-After header support is an advantage to preserve. The ideal rate limiter combines scoping with header-awareness
  - System: paymo-api-php -- `RateLimiter.php`
  - Scope: RateLimiter refactor for named scopes with independent rate configs, preserve existing header parsing
  - Effort: Medium | Value: Low
  - Source: Competitive analysis

- Add `Configuration::reset()` method that destroys the singleton instance for clean test isolation between test cases -- ~5 lines | Effort: Small | Value: Low

- Add Composer script shortcuts for test execution (`test`, `test:dry-run`, `test:verbose`) matching peer SDK conventions -- composer.json additions | Effort: Small | Value: Low

### API Feature Support

- **Implement leave management resources in SDK**
  - Context: 4 undocumented endpoints from PR #30 (2017): CompanyDaysException (`/api/companiesdaysexceptions`), UserDaysException (`/api/usersdaysexceptions`), LeaveType (`/api/leavetypes`), StatsReport (`/api/statsreports`). Full CRUD for first three; POST-only for StatsReport. Corresponds to Paymo's Leave Planner feature
  - System: paymo-api-php -- 4 new resource classes in `src/Entity/Resource/`
  - Scope: 4 new resource classes with PROP_TYPES, CRUD restrictions, filter requirements. StatsReport has a unique response structure
  - Effort: Large | Value: Medium
  - Source: Research report
  - Dependencies: Live testing of leave endpoints (below) is a prerequisite -- endpoints may have evolved since 2017

- **Add Report PDF/XLSX export support**
  - Context: The API supports `application/pdf` and `application/vnd.ms-excel` content types for Report, Invoice, and Estimate export. The SDK currently doesn't support binary content type responses or export-specific endpoints
  - System: paymo-api-php -- HTTP response handling, new export methods
  - Scope: Binary response handling, content-type negotiation, file download methods
  - Effort: Medium | Value: Medium
  - Source: Research report

- **Add webhook conditional filtering builder**
  - Context: Webhooks support a `where` parameter for conditional filtering and HMAC-SHA1 signatures via a `secret` parameter. Allows webhooks to fire only for specific conditions
  - System: paymo-api-php -- `Webhook.php`, new builder methods
  - Scope: Fluent builder pattern for webhook WHERE conditions, condition serialization for webhook context
  - Effort: Medium | Value: Low
  - Source: Research report

### Developer Experience Conveniences

- Add `flatten($property)` method to AbstractCollection for single-property extraction from all items (e.g., `$projects->flatten('name')` returns array of names) -- matches peer SDK conventions | Effort: Small | Value: Low

- Add `toArray()` and `toJson()` convenience methods to AbstractResource and AbstractCollection -- wrapper methods around existing `flatten()` and `JsonSerializable` functionality, matching peer SDK conventions | Effort: Small | Value: Low

### Documentation Updates

- **Document or mitigate 2,500-item include truncation**
  - Context: Included resource collections are silently capped at 2,500 items -- no error returned, data silently dropped. Significant data integrity risk for collections like InvoiceItems, TimeEntries, or Tasks. Issue #68 on Paymo API repo is open
  - System: paymo-api-php -- SDK documentation, optionally collection count warning logic
  - Scope: Documentation update in CLAUDE.md/OVERRIDES.md, optional collection count warning when included collections approach the cap, possible pagination-based workaround documentation
  - Effort: Medium | Value: High
  - Source: Research report

- Document that Task and TimeEntry `description` fields may contain HTML tags (`<p>` etc.) when content is entered via the Paymo web interface -- confirmed by Paymo (Issue #50), by design | Effort: Small | Value: Medium

- Document that `retainer_id` appears on Project objects but the Retainer API is explicitly not public -- no endpoint to resolve retainer data (confirmed by Paymo staff, Issue #66) | Effort: Small | Value: Low

- Document that webhook delete payloads only contain `{"id": <ID>}` (no other properties) and update payloads have no changed-fields diff -- API-side limitations (Issues #33, #38) | Effort: Small | Value: Medium

- **Consider handling sensitive Company properties**
  - Context: Company resource returns sensitive data (op_stripe_secret_key, Authorize.net credentials, custom SMTP config) in API responses. SDK consumers should be aware these are present
  - System: paymo-api-php -- SDK documentation, possibly PROP_TYPES annotations
  - Scope: Documentation note and/or annotation in PROP_TYPES indicating sensitive fields
  - Effort: Small | Value: Medium
  - Source: Research report

### Investigation & Verification

These items produce knowledge that informs implementation decisions. Many are prerequisites for items above.

- **Systematic property discovery via live API**
  - Context: Highest priority investigation. Current 60+ undocumented property list reflects only SDK testing encounters. A systematic approach (GET each resource without `?select=` and compare response against known properties) would produce a definitive inventory
  - System: paymo-api-php -- all 38 resource classes
  - Scope: Test script creation, response comparison tooling, per-resource property diff
  - Effort: Medium | Value: High
  - Source: Research report (Priority 1)

- **Live test leave management endpoints**
  - Context: 4 PR #30 endpoints documented from 2017. Paymo's Leave Planner has evolved since then. Live testing is a prerequisite before implementing SDK resource classes
  - System: paymo-api-php -- test scripts for CompanyDaysException, UserDaysException, LeaveType, StatsReport
  - Scope: Test 4 endpoints for current functionality, property comparison against PR #30 documentation
  - Effort: Medium | Value: Medium
  - Source: Research report (Priority 1)
  - Dependencies: Prerequisite for implementing leave management resources

- **Build filter operator validation matrix**
  - Context: Documentation describes general filter syntax but doesn't specify which operators work on which properties for most resources. Only 11 of 38 resources have SDK-defined WHERE_OPERATIONS restrictions
  - System: paymo-api-php -- `RequestCondition.php`, per-resource WHERE_OPERATIONS constants
  - Scope: Systematic test script, per-resource per-property operator testing, results update WHERE_OPERATIONS constants
  - Effort: Large | Value: Medium
  - Source: Research report (Priority 1)

- **Spot-check SDK-only include relationships**
  - Context: SDK INCLUDE_TYPES maps 98 relationships (corrected from 82 by doc audit); documentation alone reveals ~73. The delta of ~25 SDK-only relationships should be verified against live API
  - System: paymo-api-php -- include system verification
  - Scope: Targeted include tests for specific resource/key combinations
  - Effort: Medium | Value: Medium
  - Source: Research report (Priority 2)

- **Verify per-resource HAS condition support**
  - Context: HAS filtering (server-side relationship count filtering) is one of the SDK's three unique advantages over peers. Generic syntax is documented but no per-resource support matrix exists
  - System: paymo-api-php -- filter system
  - Scope: Systematic per-resource HAS tests against known include relationships, results as a support matrix
  - Effort: Medium | Value: Medium
  - Source: Research report

- **Test filter support for undocumented properties**
  - Context: Gap between "properties that exist" and "properties that are filterable" is unknown for undocumented properties like `billing_type`, `cover_file_id`, `files_count`
  - System: paymo-api-php -- filter system
  - Scope: Systematic filter tests across undocumented properties
  - Effort: Medium | Value: Medium
  - Source: Research report

- **Compare SDK resource classes against full API inventory**
  - Context: The systematic verification step connecting research findings to actionable SDK changes. Would produce the definitive list of property additions, corrections, and removals across all 38 resources
  - System: paymo-api-php -- all 38 resource classes
  - Scope: 38-resource comparison, per-resource property diff, output as a change manifest
  - Effort: Large | Value: High
  - Source: Research report (Priority 2)

- **Reconcile include relationship count (82 vs 98)**
  - Context: Research report claims 82 include relationships; doc audit verification found 98 from SDK source code. The 16 missing relationships should be identified
  - System: paymo-api-php -- include system, research report accuracy
  - Scope: Compare the 82-row include map from the research against actual SDK INCLUDE_TYPES constants across all resources
  - Effort: Small | Value: Medium
  - Source: Documentation audit

- Test 3-level and 4-level nested includes (e.g., `?include=tasks.entries.user`) to determine actual maximum nesting depth -- 2 levels confirmed in docs, deeper untested | Effort: Small | Value: Low

- Probe `GET /api/currencies` to determine if this is a live endpoint -- `currencies.md` exists in docs repo but no live evidence. If real, add Currencies resource class | Effort: Small | Value: Low

- Test RecurringProfileItem and other multi-word compound resource names for response key anomalies matching the underscore insertion pattern (OVERRIDE-009) | Effort: Small | Value: Low

- Investigate whether StatsReport supports additional report types beyond `user_annual_leave_stats` and `user_working_days_count` documented in PR #30 | Effort: Small | Value: Low

- Investigate whether the 2,500-item include truncation threshold varies by resource type, account tier, or other factors | Effort: Small | Value: Low

- Determine conditions under which Company properties `apply_tax_to_expenses` and `tax_on_tax` are present in API responses -- currently OVERRIDE-002 with MEDIUM confidence | Effort: Small | Value: Low

- Verify Estimate `delivery_date` property (present on Invoice but unconfirmed on Estimate) -- needs live API check | Effort: Small | Value: Low

- Determine specific rate limit values (requests per time period) for the Paymo API -- response headers exist but actual limits undocumented, SDK uses conservative 5 req/5s default | Effort: Small | Value: Medium

- Verify CommentThread CRUD semantics -- already corrected during doc audit (delete() supported, update() not supported because all properties READONLY). No further action unless live testing reveals different behavior | Effort: Small | Value: Low

- Evaluate whether any Paymo API resources exist only as hydrated includes (never fetched directly) -- if so, add `INCLUDE_ONLY` constant following peer SDK pattern | Effort: Small | Value: Low

- Verify Paymo's retry parameters are optimal and consider making them configurable via config file -- current logic (4 attempts, Retry-After + backoff with jitter) is already more sophisticated than peer SDKs | Effort: Small | Value: Low

---

## General Action Items

No general action items found in the provided materials. All three input sources address software SDK development exclusively -- no operational, staffing, communication, or non-technical tasks were identified.

---

## Ideas & Suggestions

### Process & Maintenance

- Maintain `docs/gap-matrix.md` as a living document -- add a last-audited date field and update whenever resources, properties, or overrides change. Without maintenance, it will become stale like the API docs it was built to address

- Research and adopt documentation linting tools for the project's markdown files -- identified during the doc audit as "unresearched -- recommend manual review"

- Future OVERRIDE updates should include a cross-document consistency check across CLAUDE.md, OVERRIDES.md, and PACKAGE-DEV.md -- learned when PACKAGE-DEV.md's UNSELECTABLE table had to be updated from 5-resource/7-property to 6-resource/32-property to match corrected OVERRIDE-013

- Establish a backporting practice where innovations in new packages (niftyquoter-api-php, leadfeeder-api-php, future Gen 4) are backported to paymo-api-php within the same development cycle to prevent divergence accumulation

### Strategic Direction

- Position paymo-api-php as the reference implementation for the SDK family -- the package where all proven patterns converge and scale is validated at 38 resources vs. 10 and 6 in peers. Frame adoption as "integrating proven innovations" not "fixing a legacy package"

- Consider forward-porting three Paymo advantages to peer SDKs: (1) recursive include hydration (high value for niftyquoter-api-php), (2) server-side HAS filtering (API-dependent), (3) Retry-After header support (universally applicable, low effort)

### Architecture Exploration

- **Investigate adopting PHPUnit (hybrid approach)**
  - Context: Custom test framework is well-designed for API testing but lacks code coverage, CI/CD integration, and standard workflow compatibility. All three SDK family packages use custom frameworks
  - Scope: Hybrid approach preserving custom ResourceTestRunner for integration tests while adding PHPUnit for unit tests and coverage. High effort

- **Investigate async/concurrent request support**
  - Context: All three packages execute requests sequentially. Guzzle Pool/Promise support would enable concurrent HTTP requests for bulk operations. Rate limiter assumes sequential execution and would need redesign. API rate limits may constrain practical benefit
  - Scope: High effort -- Guzzle Pool integration, rate limiter redesign, concurrent request management

- **Consider middleware pipeline for request lifecycle**
  - Context: Request pipeline is currently hardcoded in execute(). Consumer hook points would enable custom headers, audit logging, metrics collection. Low priority -- current pipeline covers actual use cases
  - Scope: Pipeline abstraction, hook registration, execution order management

- **Evaluate singleton fetch pattern**
  - Context: Peer SDK (leadfeeder-api-php) supports `fetchSingleton()` with no ID for single-instance resources, and list() throws for singleton resources. Company is the only Paymo resource that fits this pattern
  - Scope: New method on AbstractResource, Company-specific override, list() restriction

- Consider whether a separate test config file improves test isolation vs. the current embedded `testing.*` approach in the main config -- minor developer experience consideration

---

## Extraction Notes

- **Source overlap:** Sources 1 (Research) and 2 (Doc Audit) overlap heavily -- the doc audit was built directly on research findings. Items from Source 2 that were verified as completed during that task's execution were excluded from this extraction. Only follow-up work and unresolved items were retained.

- **Deduplication:** 1 item was merged during categorization (duplicate devMode bug fix references from two sections of the competitive analysis). All other items were distinct despite topical overlap between sources.

- **Effort/value assessments** are based on scope descriptions from the source documents and priority ratings from the competitive analysis (Critical/High/Medium/Low gaps). Actual effort may vary once investigation items produce definitive findings.

- **Investigation items (19 of 56)** are categorized as Software Enhancement Tasks because they produce artifacts (test scripts, validation matrices, property inventories) that directly inform implementation. Several are prerequisites for other items -- implementing without investigating first risks rework.

- **All source materials were fully readable.** No content was skipped or could not be processed.
