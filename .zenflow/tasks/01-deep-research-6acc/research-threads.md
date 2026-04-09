# Research Threads: Paymo REST API Complete Inventory

## Thread Summary

All 6 threads are prioritized for deep dives. The goal is an acutely accurate mapping of the complete API surface for SDK comparison.

| # | Thread | One-line Description | Priority |
|---|--------|---------------------|----------|
| 1 | Undocumented Endpoints & Hidden Resources | Verify and catalog all API endpoints beyond official documentation | HIGH |
| 2 | Per-Resource Property Audit | Cross-reference every property across docs, OVERRIDES, examples, and community reports | HIGH |
| 3 | Response Format & Parsing Anomalies | Catalog all response key mismatches, unselectable properties, and format edge cases | HIGH |
| 4 | Filter/WHERE Capability Matrix | Build exhaustive per-resource, per-property operator support matrix | HIGH |
| 5 | Include Relationship Verification | Verify complete include map with nesting, partial includes, and edge cases | HIGH |
| 6 | Conditional Properties & Behavioral Quirks | Consolidate all known behavioral deviations between docs and actual API | HIGH |

---

## Per-Thread Detail

### Thread 1: Undocumented Endpoints & Hidden Resources

**Rationale:** The broad sweep identified 4-5 undocumented endpoints (companiesdaysexceptions, usersdaysexceptions, leavetypes, statsreports) plus an ambiguous currencies endpoint. CData exposes ~40+ tables, some of which don't match documented endpoints. Without knowing the full API surface, the SDK comparison will have blind spots.

**Current understanding:**
- PR #30 documents leave management endpoints (companiesdaysexceptions, usersdaysexceptions, leavetypes, statsreports) but was never merged
- Currencies.md exists in docs repo but isn't listed in the README endpoint index
- CData connector exposes entities like ClientTimeEntries, ProjectBookings, TaskBookings that may be computed views or real endpoints
- Retainer projects exist in the UI but retainer API is explicitly not public (Issue #66)
- SDK classMap contains 38+ entity type keys — some may map to undocumented endpoints

**Research questions:**
1. What are the complete properties, operations (CRUD), and relationships for each undocumented endpoint from PR #30?
2. Is `/api/currencies` a real endpoint? What operations does it support? What does it return?
3. Which CData entities correspond to real API endpoints vs computed views?
4. Are there any other endpoints discoverable through the SDK classMap, community sources, or third-party integrations?
5. What is the status of the retainer API — is `retainer_id` on projects the only surface?

**Estimated effort:** Medium — PR #30 diffs provide substantial detail, CData docs and classMap provide additional leads

**Priority:** HIGH — directly addresses sub-question 5 (undocumented behavior) and sub-question 1 (complete resource list)

---

### Thread 2: Per-Resource Property Audit

**Rationale:** The docs are frozen ~2022. Properties visible in webhook payloads (files_count, comments_count on Task), example JSON (billing_type on Project), and OVERRIDES (invoice_id on InvoiceItem/EstimateItem) aren't in the official property tables. The SDK comparison needs an accurate property baseline — every property that exists, its actual type, and its actual constraints.

**Current understanding:**
- Broad sweep extracted all documented properties per resource from the 37 section files
- OVERRIDES.md documents 13 known deviations (read-only overrides, missing properties, type corrections)
- GitHub issues report additional missing properties (costs_per_hour #70, progress_status #25)
- Webhook payload examples in the docs show properties not listed in property tables (files_count, comments_count)
- Example JSON responses in the docs sometimes include properties absent from the property tables (billing_type)

**Research questions:**
1. For each OVERRIDE, what exactly is the discrepancy between docs and reality? Build a precise mapping.
2. Which properties appear in doc examples/webhook payloads but are absent from property tables?
3. Which GitHub issues report missing, incorrect, or changed properties? What was the resolution?
4. Are there type mismatches between what docs say and what OVERRIDES/community report?
5. Which properties are documented as settable but are actually read-only (or vice versa)?
6. Which properties have been deprecated or conditionally present?

**Estimated effort:** Medium-High — requires detailed cross-referencing of OVERRIDES against docs, reading GitHub issues for property-related reports, and scanning all doc examples for undocumented properties

**Priority:** HIGH — directly addresses sub-question 2 (properties & types) — the core of the inventory

---

### Thread 3: Response Format & Parsing Anomalies

**Rationale:** OVERRIDES 009 (response key mismatches for project templates), 010 (colon-prefixed gallery keys), and 013 (unselectable properties) reveal that the API's response format isn't always predictable from documentation conventions. The SDK must handle these parsing edge cases, and the inventory must document them precisely.

**Current understanding:**
- OVERRIDE-009: `/api/projecttemplates` returns `{"project_templates": [...]}` (with underscore) instead of expected `{"projecttemplates": [...]}`
- OVERRIDE-010: Gallery endpoints return data under colon-prefixed keys (e.g., `":estimatetemplates"`)
- OVERRIDE-013: Certain properties exist in full API responses but cannot be explicitly requested via the `select` query parameter — they silently disappear from responses when you try to select them
- The general convention is that response keys match the endpoint path segment (e.g., `/api/projects` returns `{"projects": [...]}`)

**Research questions:**
1. What is the complete list of response key anomalies? Which endpoints deviate from the standard convention?
2. Is the colon-prefix pattern consistent across all gallery endpoints?
3. What is the complete list of unselectable properties per resource?
4. Are there other parsing edge cases documented in OVERRIDES or community reports?
5. What patterns exist — are anomalies random or systematic (e.g., all template-related endpoints)?

**Estimated effort:** Low-Medium — OVERRIDES.md is the primary source; needs systematic cataloging and pattern analysis

**Priority:** HIGH — critical for SDK response parsing accuracy

---

### Thread 4: Filter/WHERE Capability Matrix

**Rationale:** The docs describe general WHERE syntax but don't exhaustively list which operators work on which properties per resource. The broad sweep extracted filter support from documentation examples and text, but this is incomplete — most resources lack explicit filter specifications. For the SDK comparison, we need to know what filters the API actually supports.

**Current understanding:**
- General WHERE syntax is documented: operators `=`, `>`, `>=`, `<`, `<=`, `!=`, `like`, `not like`, `in`, `not in`
- Two resources have documented WHERE requirements: Booking (must filter by user_id, project_id, or task_id) and TaskAssignment (must filter by user_id or task_id)
- Partial filter lists extracted from doc examples for ~12 resources (Projects, Tasks, Clients, TimeEntries, Invoices, Expenses, WorkflowStatuses, Subtasks, Milestones, Discussions, Comments, Files)
- The SDK has a `WHERE_OPERATIONS` constant per resource that may restrict operators for specific properties
- HAS conditions (filtering by relationship count) are documented generically but per-resource support is unspecified

**Research questions:**
1. For each resource, which properties are explicitly documented as filterable (from examples, text, and property tables)?
2. Which resources have NO documented filter examples at all?
3. Are there properties where specific operators are restricted (e.g., only `=` allowed, not `like`)?
4. What HAS conditions are documented per resource?
5. Is there community evidence of filter support beyond what's documented?
6. What filter edge cases exist (e.g., date format requirements for WHERE, `in(me)` syntax)?

**Estimated effort:** Medium — requires re-reading all resource docs with filter-specific focus, plus cross-referencing SDK WHERE_OPERATIONS

**Priority:** HIGH — directly addresses sub-question 4 (filtering capabilities)

---

### Thread 5: Include Relationship Verification

**Rationale:** The include system (sideloading related entities) is a key SDK feature. The broad sweep mapped ~70 include relationships from per-resource doc files, but the includes.md doc provides only general syntax — not per-resource exhaustive lists. Some include behaviors have known issues (thread include key singular/plural bug, issue #55). The inventory must capture every valid include key per resource.

**Current understanding:**
- ~70 include relationships mapped across all resources in the broad sweep
- General include syntax documented: `?include=key1,key2` and `?partial_include=key1(field1,field2)`
- Known issue: Thread include key must be `comments` (plural), not `comment` — singular throws 500 error (Issue #55, fixed in docs)
- Includes can be nested: e.g., `?include=tasks.entries` to get task entries when fetching a project
- The SDK has per-resource `INCLUDE_TYPES` constants
- Some includes are documented in the resource files but others may only be discoverable through the SDK or testing

**Research questions:**
1. Is the broad sweep's include map complete? Are there includes documented in one place but missed in the sweep?
2. What partial_include fields are supported per resource?
3. What nesting depth is supported? Can you nest 3+ levels deep?
4. Are there community-reported include issues beyond the thread key bug?
5. Do all documented includes actually work, or are some broken/deprecated?
6. Are there undocumented includes that the SDK supports but the docs don't mention?

**Estimated effort:** Low-Medium — broad sweep captured most includes; verification requires targeted re-reading

**Priority:** HIGH — directly addresses sub-question 3 (include relationships)

---

### Thread 6: Conditional Properties & Behavioral Quirks

**Rationale:** Several properties behave differently than documented. The inventory must distinguish between what the docs say and what the API actually does, with precise categorization. This thread consolidates all known behavioral deviations into a single, categorized reference.

**Current understanding:**
- **Read-only overrides:** Client.active (docs say settable, SDK overrides to read-only — OVERRIDE-006)
- **Deprecated properties:** Invoice.language, RecurringProfile.language, Estimate.language (OVERRIDE-012)
- **Conditional properties:** Client.image_thumb_* only present when image uploaded (OVERRIDE-001); Company.apply_tax_to_expenses and Company.tax_on_tax may be deprecated/conditional (OVERRIDE-002)
- **Undocumented but present:** InvoiceItem.invoice_id, EstimateItem.estimate_id (OVERRIDE-007); Project.billing_type visible in examples
- **HTML in text fields:** Task and TimeEntry descriptions contain HTML from web interface (Issue #50)
- **Webhook behavioral quirks:** Task webhook fires too often (Issue #38); delete payloads only contain ID (Issue #33)
- **Empty includes:** InvoiceItems sometimes return empty arrays when items exist in UI (Issue #68)

**Research questions:**
1. What is the complete categorized list of behavioral deviations? Categories: read-only override, deprecated, conditional, undocumented-present, type mismatch, behavioral quirk
2. For each deviation, what is the confidence level (tested via SDK OVERRIDES vs community report vs inferred)?
3. Are there patterns — do deviations cluster around certain resource types or property types?
4. Which deviations are confirmed fixed vs still active?
5. Are there behavioral quirks reported in GitHub issues that aren't captured in OVERRIDES?

**Estimated effort:** Low-Medium — primarily consolidation and categorization of existing findings from OVERRIDES and GitHub issues

**Priority:** HIGH — essential for SDK accuracy; the gap between docs and reality IS the value of this audit

---

## User Prioritization Notes

User requested full audit with maximum accuracy:
- All 6 threads elevated to HIGH priority
- No threads removed or combined — distinct threads maintained for thoroughness
- All threads receive deep dives
- User deferred prioritization decisions to the research process — the goal is comprehensive accuracy, not selective depth

---

## Deep Dive Plan

All 6 threads will receive deep dives, ordered by dependency and data flow:

| Order | Thread | Rationale for Order |
|-------|--------|-------------------|
| 1 | Thread 1: Undocumented Endpoints & Hidden Resources | Must know the full resource list before auditing properties, includes, and filters on those resources |
| 2 | Thread 2: Per-Resource Property Audit | Properties must be established before mapping which are filterable or includable |
| 3 | Thread 6: Conditional Properties & Behavioral Quirks | Categorizes deviations discovered during the property audit; feeds into response format analysis |
| 4 | Thread 3: Response Format & Parsing Anomalies | Builds on property knowledge to map parsing edge cases |
| 5 | Thread 4: Filter/WHERE Capability Matrix | Requires complete property list to build exhaustive matrix |
| 6 | Thread 5: Include Relationship Verification | Can proceed in parallel with filters; placed last because broad sweep was most thorough here |

**Approach:** Each deep dive will cross-reference official docs, OVERRIDES.md, GitHub issues, PR #30, SDK classMap/constants, CData connector docs, and any community sources. Findings will be cataloged with confidence levels (HIGH = tested via SDK/OVERRIDES, MEDIUM = community-reported, LOW = inferred/unconfirmed).
