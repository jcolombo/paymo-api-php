# Raw Extractions

**Date:** 2026-04-09
**Extraction Depth:** Full Extraction
**Sources Processed:** 3 of 3

---

## Summary

- **Total items extracted:** 68
- **Breakdown by source:**
  - Source 1 (research-report.md): 30 items
  - Source 2 (doc-plan.md): 5 items
  - Source 3 (competitive-analysis.md): 33 items
- **Breakdown by preliminary domain signal:**
  - Software Enhancement: 57
  - Idea/Suggestion: 11
- **Breakdown by complexity signal:**
  - Structured block: 48
  - One-liner: 20

---

## Extracted Items

### Source 1: research-report.md (Deep Research — Task 01)

#### S1-01: Implement leave management resources in SDK

- **Item text:** Add 4 undocumented leave management resource classes to the SDK: CompanyDaysException (`/api/companiesdaysexceptions`), UserDaysException (`/api/usersdaysexceptions`), LeaveType (`/api/leavetypes`), and StatsReport (`/api/statsreports`). Full CRUD for the first three; POST-only for StatsReport. Properties, required fields, and filter requirements are documented in PR #30.
- **Source reference:** Thread 1 "Undocumented Resources" section (lines 190-212)
- **Context:** These endpoints correspond to Paymo's Leave Planner feature. PR #30 from 2017 documents them but they were never merged into official API docs. SDK currently has no resource classes for these. The endpoints may have evolved since 2017 — live API verification is a prerequisite.
- **Complexity signal:** Structured block — 4 new resource classes with properties, CRUD restrictions, filter requirements, and a unique response structure (StatsReport)
- **Domain signal:** Software Enhancement

#### S1-02: Add undocumented properties to existing SDK resources

- **Item text:** Add 60+ undocumented properties discovered via SDK testing to the appropriate resource PROP_TYPES constants. Major clusters: Company (30+ properties including email templates, SMTP config, payment gateway fields, subscription limits), Booking (5 properties: creator_id, user_id, start_time, end_time, booked_hours), Task (6 properties: cover_file_id, price, start_date, recurring_profile_id, billing_type, files_count, comments_count), User (5 properties: annual_leave_days_number, has_submitted_review, menu_shortcut, user_hash, workflows), and scattered properties across Client, ClientContact, Estimate, Expense, Invoice, InvoiceItem, EstimateItem, Report, Tasklist, TimeEntry, ProjectTemplateTask, EstimateTemplate, InvoiceTemplate.
- **Source reference:** Thread 2 "Undocumented Properties by Resource" table (lines 222-319)
- **Context:** These properties are returned by the live API but absent from official documentation. All verified via SDK testing (HIGH confidence). Some are essential foreign keys (InvoiceItem.invoice_id, EstimateItem.estimate_id — already in OVERRIDE-007). Some are sensitive (Company Stripe keys, SMTP config). Adding them to PROP_TYPES enables proper type checking, dirty tracking, and field selection.
- **Complexity signal:** Structured block — touches 15+ resource classes, requires settability determination for each property
- **Domain signal:** Software Enhancement

#### S1-03: Fix Session.id type mismatch

- **Item text:** Session.id is a hex string token, not an integer. This is unique among all resources and is already documented as OVERRIDE-004. Verify the SDK's PROP_TYPES for Session correctly declares `id` as `text` type, not `integer`.
- **Source reference:** Thread 2 "Type Mismatches" table (line 332)
- **Context:** All other resources use integer IDs. This type mismatch affects any code that assumes numeric IDs. Already documented as OVERRIDE-004 in the SDK.
- **Complexity signal:** One-liner — verification/fix of a single constant value
- **Domain signal:** Software Enhancement

#### S1-04: Handle deprecated language properties

- **Item text:** Three resources have deprecated `language` properties: Invoice, Estimate, and RecurringProfile. These are already documented as OVERRIDE-012. Verify the SDK marks these as READONLY to prevent consumers from setting them.
- **Source reference:** Thread 2 "Deprecated Properties" table (lines 336-342)
- **Context:** The `language` property was deprecated across financial resources. No removal timeline from Paymo. SDK should treat as read-only to prevent consumers from relying on settability.
- **Complexity signal:** One-liner — verification of existing READONLY constant entries
- **Domain signal:** Software Enhancement

#### S1-05: Support partial_include syntax in SDK

- **Item text:** Add support for `?partial_include=key(field1,field2)` syntax, which allows selecting specific fields from included resources rather than fetching all fields of the included entity.
- **Source reference:** Thread 5 "Include Syntax" section (lines 632-636)
- **Context:** Currently the SDK supports full includes via `?include=key1,key2` but not partial includes. This is a documented API feature that reduces response payload size. Per-resource field support is unverified.
- **Complexity signal:** Structured block — new query parameter builder, integration with existing include system, per-resource field validation
- **Domain signal:** Software Enhancement

#### S1-06: Support nested include dot notation in SDK

- **Item text:** Add support for nested include dot notation (`?include=tasks.entries`) to allow multi-level sideloading in a single API call. 2 levels of nesting are confirmed in documentation; 3+ levels are undocumented.
- **Source reference:** Thread 5 "Include Syntax" section (lines 632-636)
- **Context:** The SDK's recursive include hydration is a key advantage, but the query builder doesn't currently support the dot notation for requesting nested includes. Adding this would leverage the existing hydration infrastructure.
- **Complexity signal:** Structured block — query parameter building, recursive include request construction, depth validation
- **Domain signal:** Software Enhancement

#### S1-07: Document or mitigate 2,500-item include truncation

- **Item text:** Included resource collections are silently capped at 2,500 items — no error is returned, data is silently dropped. The SDK should document this limitation and optionally warn consumers when included collections approach the cap.
- **Source reference:** Thread 5 "Known Include Issues" (line 643), Thread 3 (line 411), Key Findings (line 38)
- **Context:** This is a significant data integrity risk for collections like InvoiceItems, TimeEntries, or Tasks where 2,500+ records are realistic. The silent truncation means consumers may unknowingly process incomplete data. Issue #68 on the Paymo API repo is open.
- **Complexity signal:** Structured block — documentation update, optional collection count warning, possible pagination-based workaround
- **Domain signal:** Software Enhancement

#### S1-08: Add Report PDF/XLSX export support

- **Item text:** Add support for Report, Invoice, and Estimate export in PDF and XLSX formats. The API supports `application/pdf` and `application/vnd.ms-excel` content types for export endpoints.
- **Source reference:** Thread 1 "Report" row (line 188), API Infrastructure "Content Types" (lines 756-758)
- **Context:** The API can export reports, invoices, and estimates as PDF or Excel files. The SDK currently doesn't support binary content type responses or export-specific endpoints.
- **Complexity signal:** Structured block — binary response handling, content-type negotiation, file download methods
- **Domain signal:** Software Enhancement

#### S1-09: Add webhook conditional filtering builder

- **Item text:** Add a builder for webhook `where` parameter to support property-based conditional filtering when creating webhooks. This allows webhooks to fire only for specific conditions.
- **Source reference:** API Infrastructure "Webhooks" section (lines 792-798)
- **Context:** Webhooks support a `where` parameter for conditional filtering and HMAC-SHA1 signatures via a `secret` parameter. The SDK's Webhook resource class could provide fluent methods for configuring these.
- **Complexity signal:** Structured block — fluent builder pattern, WHERE condition serialization for webhook context
- **Domain signal:** Software Enhancement

#### S1-10: Systematic property discovery via live API

- **Item text:** For each of the 38 SDK resources, issue a GET request without `?select=` parameters and compare the full response against documented + SDK-known property lists. This would close the property completeness gap and reveal any additional undocumented properties.
- **Source reference:** Recommendations "Priority 1" item 1 (lines 923-924)
- **Context:** The current 60+ undocumented property list reflects only what the SDK has encountered during testing. A systematic approach would provide a definitive property inventory. This is the highest priority investigation item.
- **Complexity signal:** Structured block — test script creation, response comparison tooling, per-resource property diff
- **Domain signal:** Software Enhancement

#### S1-11: Live test leave management endpoints

- **Item text:** Test all 4 PR #30 leave management endpoints (CompanyDaysException, UserDaysException, LeaveType, StatsReport) against the current live API to verify they still work, check for property changes, and confirm CRUD operations.
- **Source reference:** Recommendations "Priority 1" item 2 (lines 925-926)
- **Context:** These endpoints are documented from 2017 (MEDIUM confidence). Paymo's Leave Planner feature has evolved in the product since then. Live testing is a prerequisite before implementing SDK resource classes (S1-01).
- **Complexity signal:** Structured block — test scripts for 4 endpoints, property comparison against PR #30 documentation
- **Domain signal:** Software Enhancement

#### S1-12: Build filter operator validation matrix

- **Item text:** For each resource's filterable properties, systematically test each WHERE operator to build a verified operator support matrix. Start with resources that have the most SDK WHERE_OPERATIONS restrictions (Project, Client, TimeEntry). Currently only 11 of 38 resources have SDK-defined restrictions.
- **Source reference:** Recommendations "Priority 1" item 3 (lines 927-928)
- **Context:** Documentation describes general filter syntax but doesn't specify which operators work on which properties for most resources. SDK's WHERE_OPERATIONS constants may be incomplete or inaccurate.
- **Complexity signal:** Structured block — systematic test script, per-resource per-property operator testing, results → WHERE_OPERATIONS constant updates
- **Domain signal:** Software Enhancement

#### S1-13: Spot-check SDK-only include relationships

- **Item text:** Spot-check the 9 include relationships that the SDK has beyond what documentation shows, to confirm they still work. Also test whether undocumented resources (leave endpoints) support any includes.
- **Source reference:** Recommendations "Priority 2" item 5 (lines 933-934)
- **Context:** SDK INCLUDE_TYPES maps 82 relationships (corrected to 98 per doc-plan verification); documentation alone reveals ~73. The delta should be verified against live API.
- **Complexity signal:** Structured block — targeted include tests for specific resource/key combinations
- **Domain signal:** Software Enhancement

#### S1-14: Test include nesting depth limits

- **Item text:** Test 3-level and 4-level nested includes (e.g., `?include=tasks.entries.user`) to determine the actual maximum nesting depth supported by the API.
- **Source reference:** Recommendations "Priority 3" item 6 (line 937)
- **Context:** 2 levels confirmed in documentation. SDK users would benefit from knowing the actual limit for deeply nested data fetching.
- **Complexity signal:** One-liner — a few targeted API calls
- **Domain signal:** Software Enhancement

#### S1-15: Probe currencies endpoint

- **Item text:** Issue a single `GET /api/currencies` call to determine whether this is a real, usable API endpoint. A `currencies.md` file exists in the docs repo but no evidence confirms a live endpoint.
- **Source reference:** Recommendations "Priority 3" item 7 (line 939), Thread 1 (line 198)
- **Context:** LOW confidence that this endpoint exists. If it does, a Currencies resource class should be added to the SDK.
- **Complexity signal:** One-liner — single API call
- **Domain signal:** Software Enhancement

#### S1-16: Test RecurringProfileItem response key pattern

- **Item text:** Test `RecurringProfileItem` and other multi-word compound resource names for response key anomalies matching the underscore insertion pattern (OVERRIDE-009).
- **Source reference:** Recommendations "Priority 3" item 8 (line 941), Thread 3 (lines 362-380)
- **Context:** RecurringProfile uses `recurring_profiles` response key (underscore inserted). RecurringProfileItem may follow the same pattern (`recurring_profile_items`?) but this is unverified.
- **Complexity signal:** One-liner — targeted API call and response inspection
- **Domain signal:** Software Enhancement

#### S1-17: Verify per-resource HAS condition support

- **Item text:** Verify which resources actually support HAS conditions (server-side relationship count filtering). Generic syntax is documented but no per-resource support matrix exists. Should work with any relationship listed in INCLUDE_TYPES, but untested.
- **Source reference:** Thread 4 "HAS Conditions" (lines 512-514), Knowledge Gaps item 4 (lines 895-896)
- **Context:** HAS filtering is one of Paymo SDK's three unique advantages over peer packages. Documenting which resources support it strengthens this advantage.
- **Complexity signal:** Structured block — systematic per-resource HAS tests against known include relationships
- **Domain signal:** Software Enhancement

#### S1-18: Investigate StatsReport additional report types

- **Item text:** Investigate whether StatsReport supports additional report types beyond the two documented in PR #30 (`user_annual_leave_stats` and `user_working_days_count`).
- **Source reference:** Thread 1 "StatsReport" (line 206), Knowledge Gaps item 7 (line 901)
- **Context:** StatsReport is a POST-only endpoint with a unique response structure. Additional report types may have been added since 2017 as the product evolved.
- **Complexity signal:** One-liner — exploratory API testing
- **Domain signal:** Software Enhancement

#### S1-19: Document HTML content in text fields

- **Item text:** Document that Task and TimeEntry `description` fields may contain HTML tags (`<p>` etc.) when content is entered via the Paymo web interface. Consumers should be aware they may need to strip or parse HTML.
- **Source reference:** Thread 3 "Other Format Edge Cases" (line 412)
- **Context:** Confirmed by Paymo (Issue #50). This is by design — the web interface uses a rich text editor. SDK documentation should note this for consumers processing description fields.
- **Complexity signal:** One-liner — documentation note
- **Domain signal:** Software Enhancement

#### S1-20: Document retainer_id as unresolvable

- **Item text:** Document that `retainer_id` appears on Project objects but the Retainer API is explicitly not public — there is no endpoint to resolve retainer data.
- **Source reference:** Thread 1 "Retainer API" (line 208)
- **Context:** Confirmed by Paymo staff in Issue #66. The property exists but is effectively useless to SDK consumers. This should be noted in OVERRIDES.md or CLAUDE.md to prevent confusion.
- **Complexity signal:** One-liner — documentation note
- **Domain signal:** Software Enhancement

#### S1-21: Investigate include truncation threshold variability

- **Item text:** Investigate whether the 2,500-item include truncation threshold varies by resource type, account tier, or other factors.
- **Source reference:** Knowledge Gaps item 8 (line 903)
- **Context:** The 2,500 limit is reported from Issue #68 but may not be uniform. If it varies, the SDK's documentation and any warning logic should account for different thresholds.
- **Complexity signal:** One-liner — targeted testing with different resource types
- **Domain signal:** Software Enhancement

#### S1-22: Determine Company tax property trigger conditions

- **Item text:** Determine the conditions under which Company properties `apply_tax_to_expenses` and `tax_on_tax` are present in API responses. Currently documented as "possibly deprecated or conditional" (OVERRIDE-002) with MEDIUM confidence.
- **Source reference:** Thread 6 "Category A: Conditional Properties" (lines 661-662), Knowledge Gaps item 11 (line 911)
- **Context:** These properties may depend on account configuration or tax settings. Understanding the trigger conditions would improve OVERRIDE-002 documentation accuracy.
- **Complexity signal:** One-liner — investigation/testing task
- **Domain signal:** Software Enhancement

#### S1-23: Investigate filter support for undocumented properties

- **Item text:** Test whether undocumented properties like `billing_type`, `cover_file_id`, `files_count`, etc. are usable in WHERE filter clauses.
- **Source reference:** Thread 4 "Remaining gaps" (line 518)
- **Context:** The gap between "properties that exist" and "properties that are filterable" is unknown for undocumented properties. Some may be useful filter targets.
- **Complexity signal:** Structured block — systematic filter tests across undocumented properties
- **Domain signal:** Software Enhancement

#### S1-24: Handle `in(me)` filter syntax

- **Item text:** Ensure the SDK's RequestCondition builder supports or documents the special `in(me)` syntax for filtering tasks by the currently authenticated user (e.g., `?where=users in(me)`).
- **Source reference:** Thread 4 "Filter Edge Cases" (line 505)
- **Context:** This is a documented shorthand in the Task API for "my tasks." If the SDK's WHERE builder doesn't support this syntax, consumers must construct it manually.
- **Complexity signal:** One-liner — verify support or add to RequestCondition
- **Domain signal:** Software Enhancement

#### S1-25: Compare SDK resource classes against full API inventory

- **Item text:** Map every finding from the research report against the corresponding SDK implementation. Flag: (a) properties the API returns that the SDK doesn't capture in PROP_TYPES, (b) SDK-implemented features not supported by the API, (c) type mismatches between the inventory and SDK PROP_TYPES.
- **Source reference:** Recommendations "Priority 2" item 4 (lines 931-932)
- **Context:** This is the systematic verification step that connects the research findings to actionable SDK changes. It would produce the definitive list of property additions, corrections, and removals.
- **Complexity signal:** Structured block — 38-resource comparison, per-resource property diff, output as a change manifest
- **Domain signal:** Software Enhancement

#### S1-26: Investigate webhook update payload limitations

- **Item text:** Document that webhook delete payloads only contain `{"id": <ID>}` (no other properties) and update payloads have no changed-fields diff. Consider whether SDK webhook handling should note these limitations.
- **Source reference:** Thread 6 "Category F" (lines 704-705), API Infrastructure "Webhooks" (line 798)
- **Context:** Issues #33 and #38 on the Paymo API repo. These are API-side limitations, not SDK bugs, but SDK documentation should inform consumers.
- **Complexity signal:** One-liner — documentation note
- **Domain signal:** Software Enhancement

#### S1-27: Investigate Estimate delivery_date property

- **Item text:** Verify whether Estimate has a `delivery_date` property (present on Invoice but unconfirmed on Estimate). Add to PROP_TYPES if confirmed.
- **Source reference:** Thread 2 "Undocumented Properties" (line 286)
- **Context:** Marked with "?" type — may exist based on pattern from Invoice. Needs live API verification.
- **Complexity signal:** One-liner — single API check
- **Domain signal:** Software Enhancement

#### S1-28: Add download_token properties

- **Item text:** Add `download_token` property to Expense and Report PROP_TYPES. These text properties provide file download tokens for associated files.
- **Source reference:** Thread 2 "Undocumented Properties" (lines 288, 302)
- **Context:** These are functional properties needed for downloading expense receipts and report exports. Currently absent from PROP_TYPES.
- **Complexity signal:** One-liner — add properties to two resource classes
- **Domain signal:** Software Enhancement

#### S1-29: Investigate rate limit specific values

- **Item text:** Determine the specific rate limit values (requests per time period) for the Paymo API. Response headers (X-Ratelimit-*) are returned but the actual limits are not documented anywhere.
- **Source reference:** Knowledge Gaps item 9 (line 907), API Infrastructure "Rate Limiting" (lines 748-750)
- **Context:** Knowing the actual limits would allow the SDK's RateLimiter to be more accurately configured. Currently, the SDK uses a conservative 5 req/5s default.
- **Complexity signal:** One-liner — observation from response headers during testing
- **Domain signal:** Software Enhancement

#### S1-30: Handle sensitive Company properties

- **Item text:** Consider whether sensitive Company properties (op_stripe_secret_key, custom SMTP credentials, payment gateway fields) should be excluded from default responses or flagged in SDK documentation as sensitive data.
- **Source reference:** Thread 2 "Undocumented Properties" (lines 251-270)
- **Context:** The Company resource returns sensitive financial and authentication data (Stripe keys, Authorize.net credentials, SMTP config). SDK consumers should be aware these are in API responses.
- **Complexity signal:** One-liner — documentation/PROP_TYPES annotation consideration
- **Domain signal:** Software Enhancement

---

### Source 2: doc-plan.md (Documentation Deep Dive — Task 02)

**Note:** The verification section of this source confirms that all 28+ planned documentation fixes were applied and verified. Items below are follow-up work implied by the completed documentation audit, NOT re-extractions of already-completed fixes.

#### S2-01: Maintain gap-matrix.md as a living document

- **Item text:** The newly created `docs/gap-matrix.md` documents SDK vs API coverage and should be updated whenever resources, properties, or overrides change. Consider adding a maintenance note or last-audited date field.
- **Source reference:** Document Set 5 (lines 120-174), Verification Results (lines 297)
- **Context:** gap-matrix.md was created as the primary deliverable of the documentation audit. It contains per-resource property coverage, CRUD operations, INCLUDE_TYPES, WHERE_OPERATIONS, undocumented features, and API features not yet in SDK. Without maintenance, it will become stale like the API docs.
- **Complexity signal:** One-liner — process/maintenance item
- **Domain signal:** Idea/Suggestion

#### S2-02: Adopt documentation linting tools

- **Item text:** Research and adopt documentation linting best practices/tools for the project's markdown files. This was identified during the audit as "unresearched — recommend manual review."
- **Source reference:** Best Practices Applied table (line 250)
- **Context:** The documentation audit applied several quality practices (YAML frontmatter, heading hierarchy, semantic sections, code fencing) but did not research automated linting tools.
- **Complexity signal:** One-liner — research task
- **Domain signal:** Idea/Suggestion

#### S2-03: Reconcile include relationship count (82 vs 98)

- **Item text:** The research report (Source 1) claims 82 include relationships; the documentation audit verification (Source 2) found 98 when counting from SDK source code. The gap-matrix.md was corrected to 98. Investigate which 16 relationships were missed in the research report.
- **Source reference:** Verification Results issue 1 (lines 331-332)
- **Context:** This discrepancy suggests the research report's include map (Thread 5) is incomplete. The 16 missing relationships should be identified and the research findings updated for accuracy.
- **Complexity signal:** Structured block — requires comparing the 82-row include map against actual SDK INCLUDE_TYPES constants
- **Domain signal:** Software Enhancement

#### S2-04: Verify CommentThread CRUD semantics

- **Item text:** Verification found that CommentThread supports `delete()` (per class docblock) and that `update()` is not supported because all properties are READONLY — not because the resource restricts it. Both gap-matrix.md and CLAUDE.md CRUD Restrictions were corrected during the audit.
- **Source reference:** Verification Results issue 2 (lines 332-333)
- **Context:** This was a correction applied during verification. No further action needed unless live API testing reveals different behavior. Noting for completeness.
- **Complexity signal:** One-liner — verification item, already corrected
- **Domain signal:** Software Enhancement

#### S2-05: Update PACKAGE-DEV.md UNSELECTABLE table consistency

- **Item text:** During verification, PACKAGE-DEV.md's UNSELECTABLE table was updated from 5-resource/7-property to 6-resource/32-property to match the corrected OVERRIDES.md OVERRIDE-013. This fix was not in the original plan but was applied for cross-document consistency.
- **Source reference:** Additional Fixes section (lines 315-317)
- **Context:** This demonstrates the importance of cross-document consistency when OVERRIDES change. Future OVERRIDE updates should include a consistency check across CLAUDE.md, OVERRIDES.md, and PACKAGE-DEV.md.
- **Complexity signal:** One-liner — already applied; noted as a process learning
- **Domain signal:** Idea/Suggestion

---

### Source 3: competitive-analysis.md (Competitive Intelligence — Task 03)

#### S3-01: Implement three-direction type coercion (Converter)

- **Item text:** Extend the SDK's `Converter` utility with `convertToPhpValue()` (API → PHP on hydration) and `convertForRequest()` (PHP → API on create/update) to complement the existing `convertValueForFilter()`. This adds automatic type coercion for ~570 typed properties across 38 resources. Phased rollout: Phase 1 — datetime + boolean coercion on hydration. Phase 2 — full three-direction coercion for serialization. Phase 3 — enum validation in devMode.
- **Source reference:** Key Findings "[Critical] No Type Coercion" (lines 29-36), Adoption Roadmap Phase 4 (lines 361-367)
- **Context:** NQ and LF both implement three-direction coercion. Currently every consumer must manually cast types (`strtotime($project->created_on)` instead of accessing a `DateTimeImmutable`). This is the single largest DX gap. Must be backwards-compatible — existing code that reads datetime strings cannot break. May require PHP 8.1+ minimum for native enums.
- **Complexity signal:** Structured block — touches hydration pipeline for all 38 resources, phased implementation, backwards-compatibility requirement
- **Domain signal:** Software Enhancement

#### S3-02: Implement mutation-triggered cache invalidation

- **Item text:** Implement resource-scoped cache invalidation that fires after every POST/PUT/DELETE operation. Hook into `Paymo::execute()` after mutations to invalidate cache keys matching the mutated resource's URL pattern. Use LF's refined ScrubCache pattern but with scoped invalidation (not full-wipe — Paymo's 38 resources need scoped invalidation to avoid defeating caching).
- **Source reference:** Key Findings "[Critical] No Mutation-Triggered Cache Invalidation" (lines 38-45), Adoption Roadmap Phase 3 (line 356)
- **Context:** Currently the SDK relies entirely on TTL-based expiry (300s default). In multi-step workflows (create invoice, add items, fetch invoice), stale cache returns pre-mutation data. NQ does full-wipe; LF refines it. Paymo needs resource-scoped invalidation for 38 resources. Existing `ScrubCache` class in Paymo is an in-memory include-validation cache (different concept despite shared name).
- **Complexity signal:** Structured block — hook into execute(), URL-pattern-based cache key matching, avoid naming collision with existing ScrubCache
- **Domain signal:** Software Enhancement

#### S3-03: Add fetchAll() auto-pagination

- **Item text:** Add a `fetchAll()` method to `AbstractCollection` that auto-paginates through all result pages with a configurable safety cap (default 5,000). Use result-count < page-size detection for page-end (Paymo API doesn't provide `links.next`). Add a `FETCHALL_CAP` constant per resource for override.
- **Source reference:** Key Findings "[High] No Auto-Pagination" (lines 47-54), Adoption Roadmap Phase 2 (line 347)
- **Context:** Highest ROI adoption candidate. ~20 lines of code. NQ implements fetchAll() with do/while loop; LF adds FETCHALL_CAP = 10000 safety cap. Every consumer needing all records currently writes boilerplate pagination loops. No safety cap means naive loops could run indefinitely.
- **Complexity signal:** Structured block — single method on AbstractCollection, safety cap constant, page-end detection logic
- **Domain signal:** Software Enhancement

#### S3-04: Implement structured error handling with severity levels

- **Item text:** Adopt the `ErrorSeverity` enum (NOTICE/WARN/FATAL) pattern from NQ/LF with configurable per-severity handler dispatch. Refactor `Error.php` from flat HTTP-status-to-prefix-string mapping to severity-based routing. Add handler configuration to the config file.
- **Source reference:** Key Findings "[High] No Structured Error Handling" (lines 56-63), Adoption Roadmap Phase 3 (line 355)
- **Context:** Consumers cannot currently distinguish recoverable warnings (429 rate limit) from terminal failures (401 authentication). No mechanism to route errors to different handlers. Requires PHP 8.1+ for native enum, or use string constants for PHP 7.4 compatibility.
- **Complexity signal:** Structured block — new ErrorSeverity enum, Error.php refactor, handler config, config file schema update
- **Domain signal:** Software Enhancement

#### S3-05: Fix autoload-dev namespace misconfiguration

- **Item text:** Change `composer.json` autoload-dev from `Jcolombo\\PaymoApiPhp\\` → `tests/` to `Jcolombo\\PaymoApiPhp\\Tests\\` → `tests/`. Update ~30 test file namespace declarations. Add Composer test scripts (`test`, `test:dry-run`, `test:verbose`) matching NQ/LF conventions.
- **Source reference:** Key Findings "[High] Misconfigured autoload-dev" (lines 65-72), Adoption Roadmap Phase 2 (lines 351-352)
- **Context:** Current misconfiguration causes test classes to appear in IDE autocompletion alongside production classes. Static analysis tools process tests as production code. Cannot use `--no-dev` Composer flag to cleanly exclude test code.
- **Complexity signal:** Structured block — composer.json edit + ~30 file namespace renames + new Composer scripts
- **Domain signal:** Software Enhancement

#### S3-06: Add parent context enforcement (validateFetch)

- **Item text:** Add `validateFetch()` method to collection classes that require parent filters: FileCollection, BookingCollection, InvoiceItemCollection, EstimateItemCollection. This validates at the SDK level that required parent context (project_id, invoice_id, etc.) is provided before executing the API call, emitting a warning or error in devMode.
- **Source reference:** Gap Inventory "Parent context enforcement" (line 276), Adoption Roadmap Phase 2 (line 348)
- **Context:** NQ introduced `setParentContext()` with entity+id and `validateFetch()` enforcement. Currently Paymo's SDK relies on OVERRIDE-005 documentation but doesn't enforce at runtime. ~60 lines across 4-6 classes.
- **Complexity signal:** Structured block — new method on 4-6 collection classes, parent path URL prefixing, devMode gating
- **Domain signal:** Software Enhancement

#### S3-07: Add WRITEONLY property constant

- **Item text:** Add a `WRITEONLY` constant to `AbstractResource` for properties that trigger actions but aren't returned in responses (e.g., action-trigger properties). Audit Paymo API documentation to identify applicable properties.
- **Source reference:** Gap Inventory "WRITEONLY property constant" (line 277), Adoption Roadmap Phase 3 (line 357)
- **Context:** NQ introduced WRITEONLY for properties like Proposal action triggers. Whether the Paymo API has similar action-trigger properties needs investigation. If none exist, this is a no-op.
- **Complexity signal:** Structured block — constant definition, audit for applicable properties, integration with dirty tracking and serialization
- **Domain signal:** Software Enhancement

#### S3-08: Refactor rate limiter to support named scopes

- **Item text:** Refactor `RateLimiter` to support named scopes with independent rate configurations while preserving Paymo's existing Retry-After header awareness. This combines Paymo's header-responsiveness with LF's scope flexibility.
- **Source reference:** Gap Inventory "Multi-scope rate limiter" (line 278), Adoption Roadmap Phase 4 (line 367)
- **Context:** LF has 4 scopes (export 5/min, account 100/min, token 100/min, ipenrich 60/min). Paymo currently has single sliding window with 5 req/5s. Paymo's Retry-After header support is an advantage to preserve. The ideal rate limiter is scoped + header-aware.
- **Complexity signal:** Structured block — RateLimiter refactor, scope configuration, preserve header parsing
- **Domain signal:** Software Enhancement

#### S3-09: Replace hassankhan/config dependency

- **Item text:** Replace `hassankhan/config` (Noodlehaus — last updated 2021) with direct `adbario/php-dot-notation` (Adbar\Dot) usage, rewriting `Configuration.php` to match the NQ/LF pattern. This removes an unmaintained dependency with unused YAML/XML/INI support.
- **Source reference:** Gap Inventory "Replace hassankhan/config" (line 279), Threat Assessment "Dependency Rot" (lines 311-312), Adoption Roadmap Phase 3 (line 358)
- **Context:** hassankhan/config wraps php-dot-notation with unused format support. NQ/LF both use adbario/php-dot-notation directly. If hassankhan/config becomes incompatible with future PHP versions, Paymo is uniquely exposed.
- **Complexity signal:** Structured block — Configuration.php rewrite, dependency swap in composer.json, test compatibility
- **Domain signal:** Software Enhancement

#### S3-10: Add devMode validateConstants()

- **Item text:** Add `validateConstants()` call in `AbstractEntity` constructor, gated by devMode, that validates PROP_TYPES, READONLY, CREATEONLY, UNSELECTABLE, REQUIRED_CREATE, and INCLUDE_TYPES constants are consistent (e.g., no READONLY properties in REQUIRED_CREATE, no unknown types in PROP_TYPES).
- **Source reference:** Gap Inventory "devMode validateConstants()" (line 280), Adoption Roadmap Phase 2 (line 349)
- **Context:** NQ introduced this in devMode; LF calls it in every constructor. Catches resource class definition errors at instantiation time rather than at API call time. ~30 lines.
- **Complexity signal:** Structured block — validation logic, constant cross-referencing, devMode gating
- **Domain signal:** Software Enhancement

#### S3-11: Add Configuration::reset() for test isolation

- **Item text:** Add a `reset()` method to `Configuration` that destroys the singleton instance, enabling clean test isolation between test cases.
- **Source reference:** Gap Inventory "Configuration::reset()" (line 281), Adoption Roadmap Phase 2 (line 350)
- **Context:** LF added this to fix test isolation issues. ~5 lines. Trivial effort.
- **Complexity signal:** One-liner — single method addition
- **Domain signal:** Software Enhancement

#### S3-12: Fix EntityMap::overload() typo bug

- **Item text:** Fix typo in `EntityMap::overload()` validation that checks for "AbstractResourcce" (extra 'c') instead of "AbstractResource". This is a silent bug — validation never matches, so overload validation is effectively disabled.
- **Source reference:** Gap Inventory "Fix EntityMap typo bug" (line 282), Adoption Roadmap Phase 1 (line 343), Comparison Matrix "EntityMap" (line 257)
- **Context:** One-character fix. Zero risk. This is a bug, not a feature request.
- **Complexity signal:** One-liner — 1 character change
- **Domain signal:** Software Enhancement

#### S3-13: Fix hardcoded PAYMO_DEVELOPMENT_MODE

- **Item text:** Fix `PAYMO_DEVELOPMENT_MODE` which is hardcoded to `true` in `Paymo.php:62`. It should respect the `devMode` config key instead. Currently leaks error details to stdout in all environments.
- **Source reference:** Gap Inventory "Fix hardcoded PAYMO_DEVELOPMENT_MODE" (line 283), Adoption Roadmap Phase 1 (line 344), Key Findings "[High] Structured Error Handling" (line 59)
- **Context:** One-line fix. Zero risk for correct configurations. This is a standalone bug independent of the structured error handling feature.
- **Complexity signal:** One-liner — 1 line change
- **Domain signal:** Software Enhancement

#### S3-14: Add Composer test scripts

- **Item text:** Add Composer script shortcuts for test execution: `test`, `test:dry-run`, `test:verbose`, matching NQ/LF conventions.
- **Source reference:** Adoption Roadmap Phase 2 (line 352), Comparison Matrix "Testing" (line 240)
- **Context:** NQ/LF both define these in composer.json. Standardizes test invocation across the SDK family.
- **Complexity signal:** One-liner — composer.json additions
- **Domain signal:** Software Enhancement

#### S3-15: Add cache-hit detection

- **Item text:** Add `fromCacheKey` property to `RequestResponse` to indicate whether a response was served from cache. This allows consumers to distinguish cached vs. fresh responses.
- **Source reference:** Comparison Matrix "Cache-hit detection" (line 213), LF Profile (line 160)
- **Context:** LF added this as a fix for a documented NQ bug. Paymo currently has no way to detect cache hits. Useful for debugging and for consumers who need to know data freshness.
- **Complexity signal:** One-liner — property addition to RequestResponse, set during cache fetch path
- **Domain signal:** Software Enhancement

#### S3-16: Add 3-callback registerCacheMethods

- **Item text:** Extend `registerCacheMethods()` from 2 callbacks ($fetch, $store) to 3 callbacks ($read, $write, $clear) to support custom cache backend clearing, needed for mutation-triggered cache invalidation.
- **Source reference:** Comparison Matrix "Cache custom backends" (line 212)
- **Context:** NQ/LF both use 3-callback registration. The $clear callback is needed for S3-02 (mutation-triggered cache invalidation) to work with custom cache backends.
- **Complexity signal:** One-liner — add third callback parameter, use in invalidation path
- **Domain signal:** Software Enhancement

#### S3-17: Bump PHP minimum to 8.1

- **Item text:** Raise the minimum PHP version from >=7.4 to >=8.1. PHP 7.4 reached EOL in November 2022. Bumping enables native enums (for ErrorSeverity), readonly properties, fibers, intersection types, and aligns with NQ/LF.
- **Source reference:** Threat Assessment "PHP Version Floor Gap" (lines 313-314), Adoption Roadmap Phase 4 (line 366)
- **Context:** Some adoption candidates (ErrorSeverity as native enum) require PHP 8.1+ as a prerequisite. NQ/LF both require >=8.1. PHP 7.4 has been EOL for 3+ years.
- **Complexity signal:** Structured block — composer.json change, audit for PHP 7.4-only syntax, potential use of 8.1 features
- **Domain signal:** Software Enhancement

#### S3-18: Adopt PSR-3 logging interface

- **Item text:** Replace or wrap the custom Log singleton with PSR-3 LoggerInterface support, allowing consumers to plug in Monolog or other PSR-3 compatible loggers.
- **Source reference:** White Space "PSR-3 logging" (line 301)
- **Context:** All three packages use custom Log singletons. This is a cross-package gap. PSR-3 is the PHP standard for logging interfaces. Low-Medium effort.
- **Complexity signal:** Structured block — LoggerInterface implementation/adapter, config for logger injection, backwards-compatible default
- **Domain signal:** Software Enhancement

#### S3-19: Consider adopting PHPUnit (hybrid approach)

- **Item text:** Investigate adopting PHPUnit alongside or as a wrapper for the existing custom test framework. The custom framework is well-designed for API testing but lacks code coverage, CI/CD integration, and standard workflow compatibility.
- **Source reference:** White Space "Standard testing (PHPUnit)" (line 302)
- **Context:** High effort — requires hybrid approach preserving the custom ResourceTestRunner for integration tests while adding PHPUnit for unit tests and coverage. All three packages use custom frameworks.
- **Complexity signal:** Structured block — hybrid test architecture design, PHPUnit setup, CI/CD pipeline creation
- **Domain signal:** Idea/Suggestion

#### S3-20: Consider async/concurrent request support

- **Item text:** Investigate adding Guzzle Pool/Promise support for concurrent HTTP requests. All three packages currently execute requests sequentially. The rate limiter assumes sequential execution and would need redesign.
- **Source reference:** White Space "Async/concurrent requests" (line 303)
- **Context:** High effort. Rate limiter redesign needed. Would benefit bulk operations (fetching many resources in parallel). API rate limits may constrain practical benefit.
- **Complexity signal:** Structured block — Guzzle Pool integration, rate limiter redesign, concurrent request management
- **Domain signal:** Idea/Suggestion

#### S3-21: Consider middleware pipeline for request lifecycle

- **Item text:** Consider adding consumer hook points to the request pipeline (currently hardcoded in `execute()`) for custom headers, audit logging, metrics collection, and other cross-cutting concerns.
- **Source reference:** White Space "Middleware pipeline" (line 304)
- **Context:** Low priority — current pipeline covers actual use cases. Would add extensibility for advanced consumers.
- **Complexity signal:** Structured block — pipeline abstraction, hook registration, execution order management
- **Domain signal:** Idea/Suggestion

#### S3-22: Establish backporting practice across SDK family

- **Item text:** Establish a practice where innovations in new packages (NQ, LF, future Gen 4) are backported to Paymo within the same development cycle to prevent divergence accumulation.
- **Source reference:** Threat Assessment "Divergence Accumulation" (lines 315-316), Strategic Recommendations "Backport pipeline" (line 375)
- **Context:** Each new package widens the gap. LF already fixes NQ bugs that Paymo doesn't have yet. The longer backporting is deferred, the larger the cumulative effort.
- **Complexity signal:** One-liner — process/practice establishment
- **Domain signal:** Idea/Suggestion

#### S3-23: Position Paymo as reference implementation

- **Item text:** Position paymo-api-php as the reference implementation for the SDK family — the package where all proven patterns converge and scale is validated at 38 resources vs. 10 and 6 in peers.
- **Source reference:** Strategic Recommendations "Positioning" (lines 322-325)
- **Context:** The peer packages are innovation labs; Paymo is the production standard. The adoption should be framed as "integrating proven innovations" not "fixing a legacy package."
- **Complexity signal:** One-liner — strategic framing
- **Domain signal:** Idea/Suggestion

#### S3-24: Forward-port Paymo advantages to peer packages

- **Item text:** Consider forward-porting three Paymo advantages to NQ/LF: (1) recursive include hydration (high value for NQ), (2) server-side HAS filtering (API-dependent), (3) Retry-After header support (universally applicable, low effort).
- **Source reference:** Strategic Recommendations "Bidirectional opportunities" (lines 377-381)
- **Context:** Paymo's advantages represent improvements that would benefit the entire SDK family. Forward-porting strengthens the ecosystem and establishes Paymo patterns as the standard.
- **Complexity signal:** Structured block — per-package assessment, implementation in separate codebases
- **Domain signal:** Idea/Suggestion

#### S3-25: Consider INCLUDE_ONLY resource pattern

- **Item text:** Evaluate whether any Paymo API resources exist only as hydrated includes (never fetched directly). If so, add an `INCLUDE_ONLY` constant following LF's pattern (e.g., LF's `Location` resource).
- **Source reference:** LF Profile "INCLUDE_ONLY resources" (line 161), Comparison Matrix "Include resolution" (line 203)
- **Context:** LF introduced this for resources that exist only within include responses. If no Paymo resources fit this pattern, this is a no-op.
- **Complexity signal:** One-liner — investigation + possible constant addition
- **Domain signal:** Software Enhancement

#### S3-26: Consider singleton fetch pattern

- **Item text:** Evaluate whether the SDK should support `fetchSingleton()` for single-instance resources like Company (which returns a single object, not an array). Currently Company uses the standard fetch pattern.
- **Source reference:** LF Profile "Singleton fetch" (line 162), Comparison Matrix "Singleton fetch" (line 233)
- **Context:** LF supports fetchSingleton() with no ID for single-instance resources, and list() throws for singleton resources. Company is the only Paymo resource that fits this pattern.
- **Complexity signal:** Structured block — new method on AbstractResource, Company-specific override, list() restriction
- **Domain signal:** Idea/Suggestion

#### S3-27: Add hardcoded devMode as standalone bug fix

- **Item text:** The `PAYMO_DEVELOPMENT_MODE = true` hardcoding should be fixed immediately as a Phase 1 bug fix, independent of the structured error handling feature (S3-04). This is a separate concern: error detail leakage in production.
- **Source reference:** Adoption Roadmap Phase 1 (line 344), Key Findings (line 59)
- **Context:** This is a re-emphasis of S3-13 in the roadmap context. The analysis specifically calls out fixing this independently of the larger error handling refactor because it's a standalone bug with zero risk.
- **Complexity signal:** One-liner — already captured as S3-13, noting roadmap positioning
- **Domain signal:** Software Enhancement

#### S3-28: Audit for WRITEONLY-applicable Paymo properties

- **Item text:** Review Paymo API documentation for action-trigger properties that would benefit from the WRITEONLY constant. Examples from NQ include proposal status actions. If no Paymo properties qualify, the WRITEONLY constant adds no value.
- **Source reference:** Adoption Roadmap Phase 3 (line 357), Analysis Limitations item 5 (lines 408-409)
- **Context:** Whether WRITEONLY is useful depends entirely on whether the Paymo API has properties that trigger server-side actions without being returned in responses.
- **Complexity signal:** One-liner — documentation review
- **Domain signal:** Software Enhancement

#### S3-29: Add devMode enum validation in Converter

- **Item text:** As part of the type coercion phase (S3-01 Phase 3), add enum-type validation in devMode that checks set values against allowed enum values before API calls. NQ/LF validate enums in Converter during devMode.
- **Source reference:** Key Findings "[Critical] No Type Coercion" (line 34), Comparison Matrix "Type coercion" (line 219)
- **Context:** This is Phase 3 of the Converter extension. Enum validation catches invalid values at SDK level instead of surfacing as API 400 errors. Examples: Invoice status values (draft/sent/viewed/paid/void), RecurringProfile frequencies (w/2w/3w/4w/m/2m/3m/6m/y).
- **Complexity signal:** Structured block — enum definitions per resource, validation in Converter, devMode gating
- **Domain signal:** Software Enhancement

#### S3-30: Add exponential backoff improvements to retry logic

- **Item text:** The SDK already has Retry-After header support and exponential backoff. NQ uses 2000ms base with 2^n multiplier and 3 retries. Verify Paymo's retry parameters are optimal and consider making them configurable via the config file.
- **Source reference:** Comparison Matrix "Retry logic" (line 218)
- **Context:** Paymo's retry logic (4 attempts, Retry-After + backoff with jitter) is already more sophisticated than NQ/LF. This is a minor tuning opportunity, not a gap.
- **Complexity signal:** One-liner — configuration exposure for existing parameters
- **Domain signal:** Software Enhancement

#### S3-31: Consider separate test config file

- **Item text:** NQ uses a separate `niftyquoterapi.config.test.json` as a third configuration layer for test settings. Paymo embeds test settings in the main config under `testing.*`. Consider whether a separate test config file improves test isolation.
- **Source reference:** Comparison Matrix "Test config" (line 255)
- **Context:** Minor DX consideration. The current embedded approach works. Separate file would be cleaner for CI/CD environments.
- **Complexity signal:** One-liner — optional enhancement
- **Domain signal:** Idea/Suggestion

#### S3-32: Add flatten($property) for single-property extraction

- **Item text:** NQ/LF collections support `flatten($property)` for extracting a single property from all items (e.g., `$projects->flatten('name')` returns array of names). Consider adding to Paymo's AbstractCollection.
- **Source reference:** Comparison Matrix "Data export" (line 234)
- **Context:** Convenience method for common patterns like extracting all IDs or names from a collection. NQ/LF also add `toArray()` and `toJson()` methods.
- **Complexity signal:** One-liner — single method addition
- **Domain signal:** Software Enhancement

#### S3-33: Add toArray() and toJson() convenience methods

- **Item text:** Add `toArray()` and `toJson()` convenience methods to AbstractResource and AbstractCollection, matching NQ/LF conventions. These complement the existing `flatten()` and `jsonSerialize()` methods.
- **Source reference:** Comparison Matrix "Data export" (line 234)
- **Context:** NQ/LF provide these for explicit serialization control. Paymo has `flatten()` + `JsonSerializable` but not the explicit named methods.
- **Complexity signal:** One-liner — wrapper methods around existing functionality
- **Domain signal:** Software Enhancement

---

## Completeness Notes

### Re-scan Results

After the initial extraction pass, the following sections were re-scanned for missed items:

1. **Source 1 — Knowledge Gaps & Unresolved Questions:** All 13 knowledge gaps checked. Items 1-3 (critical gaps) were extracted as S1-10, S1-11, S1-12. Items 4-8 (significant gaps) extracted as S1-17, S1-14, S1-05, S1-18, S1-21. Items 9-13 (minor gaps) extracted as S1-29, S1-15, S1-22, S1-04 (implicit), S1-16.

2. **Source 1 — Recommendations for Further Investigation:** All 8 recommendations checked. Items 1-3 (Priority 1) extracted as S1-10, S1-11, S1-12. Items 4-5 (Priority 2) extracted as S1-25, S1-13. Items 6-8 (Priority 3) extracted as S1-14, S1-15, S1-16.

3. **Source 2 — Verification Results:** Re-scanned for items that verification revealed beyond the original plan. Found S2-03 (include count discrepancy), S2-04 (CommentThread correction), S2-05 (PACKAGE-DEV consistency fix).

4. **Source 3 — Gap Inventory table:** All 13 gaps extracted (S3-01 through S3-13). Adoption Roadmap re-scanned for items not covered by the gap inventory — found S3-14 (Composer scripts), S3-17 (PHP version bump). White Spaces all extracted (S3-18 through S3-21). Threats all extracted (embedded in relevant items plus S3-22).

### Ambiguous Items

- **S1-27 (Estimate.delivery_date):** Marked with "?" type in the source. Included because even uncertain properties should be verified.
- **S3-07 (WRITEONLY):** Value depends on whether Paymo API has action-trigger properties. Included with the audit dependency noted.
- **S3-25 (INCLUDE_ONLY):** May be a no-op for Paymo. Included as an investigation item.
- **S3-26 (fetchSingleton):** Only Company fits this pattern. Included as a design consideration.

### Gaps in Coverage

- **Source 1 per-resource property tables** contain bulk data (60+ undocumented properties) that were aggregated into S1-02 rather than extracted individually. Each property addition is a micro-task, but extracting 60+ individual items would not add actionable granularity — the systematic comparison (S1-25) will produce the definitive list.
- **Source 1 include relationship map** (82 rows) was not extracted row-by-row. The relationships themselves are reference data, not action items. The include count discrepancy (S2-03) and spot-check recommendation (S1-13) capture the actionable aspects.
- **Source 2 completed fixes** (28+ items) were not re-extracted as they are documented as applied and verified. Only follow-up work was extracted.

---

## Cross-Source Items

### Pagination (Sources 1 + 3)

- **Source 1** documents pagination as an undocumented API feature (page + page_size params, 0-indexed, OVERRIDE-003) and notes the SDK has `limit($page, $pageSize)` but no auto-pagination.
- **Source 3** identifies missing `fetchAll()` as a High gap with Low effort — highest ROI adoption candidate.
- **Extracted as:** S3-03 (fetchAll implementation, richest context). Source 1 provides the API behavior details that inform the implementation.

### Rate Limiting (Sources 1 + 2 + 3)

- **Source 1** documents API behavior (X-Ratelimit-* headers, Retry-After, specific limits undocumented).
- **Source 2** fixed incorrect "1-second delay" claim to "200ms minimum delay" across CLAUDE.md, README.md, Paymo.php.
- **Source 3** identifies Paymo's header-awareness as an advantage to preserve, and recommends combining it with LF's multi-scope architecture.
- **Extracted as:** S3-08 (scoped rate limiter, richest architectural context), S1-29 (investigate specific limit values). Source 2 fixes are completed.

### Type Coercion / Type Mismatches (Sources 1 + 3)

- **Source 1** identifies specific type mismatches: Session.id (hex string vs integer), Company booleans (text vs boolean), and ~570 typed properties stored as raw strings.
- **Source 3** identifies missing three-direction type coercion as the #1 Critical gap, with full implementation roadmap.
- **Extracted as:** S3-01 (three-direction Converter, richest context), S1-03 (Session.id specific fix). Source 1 provides the property-level evidence; Source 3 provides the architectural solution.

### Caching (Sources 1 + 3)

- **Source 1** notes TTL-based expiry with skipCache option.
- **Source 3** identifies missing mutation-triggered cache invalidation as Critical gap.
- **Extracted as:** S3-02 (mutation-triggered invalidation), S3-15 (cache-hit detection), S3-16 (3-callback registerCacheMethods). All from Source 3, which provides the architectural solution.

### OVERRIDES Documentation (Sources 1 + 2)

- **Source 1** catalogs 13 active behavioral deviations with evidence.
- **Source 2** prescribed and applied specific fixes to OVERRIDES.md (OVERRIDE-013 expansion, TODO-LIST.md reference removal).
- **Extracted as:** Source 2 fixes are completed. Source 1 findings feed into ongoing OVERRIDES.md maintenance (not a standalone action item since documentation was just audited).

### Testing / Autoload (Sources 2 + 3)

- **Source 2** fixed autoload-dev namespace reference in documentation.
- **Source 3** identifies misconfigured autoload-dev as a High gap and prescribes the namespace fix.
- **Extracted as:** S3-05 (autoload-dev fix, richest context including Composer scripts). Source 2 fixed the documentation reference; the actual code fix is still needed.

### Undocumented Properties (Sources 1 + 2)

- **Source 1** catalogs 60+ undocumented properties with per-resource breakdown.
- **Source 2** documented UNSELECTABLE properties (32 across 6 resources) in OVERRIDES.md and CLAUDE.md.
- **Extracted as:** S1-02 (add undocumented properties to PROP_TYPES), S1-10 (systematic property discovery). Source 2 handled the documentation side; Source 1 identifies the SDK implementation work.

### Include System (Sources 1 + 2 + 3)

- **Source 1** maps 82 relationships (corrected to 98 by Source 2), documents partial_include and dot notation syntax, and identifies 2,500-item truncation.
- **Source 2** corrected include count to 98 during verification.
- **Source 3** notes recursive include hydration as a Paymo advantage to preserve.
- **Extracted as:** S1-05 (partial_include), S1-06 (dot notation), S1-07 (truncation), S1-13 (spot-check SDK-only includes), S1-14 (nesting depth), S2-03 (count discrepancy). Source 3's advantage preservation is a strategic note, not a separate action item.

### Filter System (Sources 1 + 3)

- **Source 1** provides per-resource WHERE capability matrix and documents filter-only properties.
- **Source 3** notes server-side HAS filtering as a Paymo advantage.
- **Extracted as:** S1-12 (filter operator validation), S1-17 (HAS condition verification), S1-23 (undocumented property filtering), S1-24 (in(me) syntax). Source 1 is the primary source for filter system work.

### Leave Management (Source 1 only)

- Only Source 1 addresses leave management endpoints. Neither Source 2 nor Source 3 reference them.
- **Extracted as:** S1-01 (implement resources), S1-11 (live testing), S1-18 (StatsReport types).
