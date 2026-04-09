# Deep Analysis: SDK vs API Documentation Audit

## Analysis Summary

| Metric | Count |
|--------|-------|
| Documentation artifacts examined | 6 (CLAUDE.md, README.md, PACKAGE-DEV.md, OVERRIDES.md, research-report.md, 38 API doc sections) |
| SDK resource classes audited | 38 |
| Total factual claims verified | ~350+ |
| Critical findings | 5 |
| Stale findings | 4 |
| Incomplete findings | 12 |
| Minor findings | 6 |
| Cross-document contradictions | 4 |
| Verification approach | Same-agent re-review (challenge pass on all Critical/Stale) |

---

## Critical Findings

### CRIT-01: Rate Limiting Documentation Is Wrong

**Documentation location:** `CLAUDE.md:992`, `README.md:524`, `src/Paymo.php:77`
**Documentation quote:** "The SDK has rate limiting built in (1-second delay between requests)" / "The SDK includes a built-in 1-second delay between requests"
**Code location:** `src/Utility/RateLimiter.php:81`, `default.paymoapi.config.json:18`
**Code evidence:** `private const MIN_DELAY_MS = 200;` and config `"minDelayMs": 200`

The actual minimum delay between requests is **200 milliseconds**, not 1 second. The 1-second value (`RETRY_DELAY_MS = 1000`) is the *retry backoff after a 429 rate limit response*, not the normal inter-request delay. Three documentation artifacts independently repeat this incorrect claim.

**Verification status:** CONFIRMED — challenge pass found no evidence the 200ms default is overridden.

---

### CRIT-02: CLAUDE.md Missing 10 of 38 Resources

**Documentation location:** `CLAUDE.md:245-282` (Section 3: Available Resources)
**Documentation quote:** Resource tables listing Core, Financial, and Supporting resources
**Code location:** `src/Entity/Resource/*.php` (38 files)
**Code evidence:** CLAUDE.md lists 28 resources. The following 10 are absent:

| Missing Resource | Class | API Path | Notes |
|---|---|---|---|
| Session | `Session` | `/sessions` | Auth token management |
| CommentThread | `CommentThread` | `/threads` | Read-only, auto-created |
| ProjectStatus | `ProjectStatus` | `/projectstatuses` | Workflow display states |
| ProjectTemplate | `ProjectTemplate` | `/projecttemplates` | Response key anomaly (OVERRIDE-009) |
| ProjectTemplateTask | `ProjectTemplateTask` | `/projecttemplatestasks` | Response key anomaly |
| ProjectTemplateTasklist | `ProjectTemplateTasklist` | `/projecttemplatestasklists` | Response key anomaly |
| InvoiceTemplate | `InvoiceTemplate` | `/invoicetemplates` | No API doc page (OVERRIDE-008) |
| EstimateTemplate | `EstimateTemplate` | `/estimatetemplates` | No API doc page (OVERRIDE-008) |
| EstimateTemplateGallery | `EstimateTemplateGallery` | `/estimatetemplatesgallery` | Read-only gallery |
| InvoiceTemplateGallery | `InvoiceTemplateGallery` | `/invoicetemplatesgallery` | Read-only gallery |

This is Critical because CLAUDE.md is the primary AI instruction file and omitting 26% of resources means AI agents will not know these resources exist when helping developers.

**Verification status:** CONFIRMED — README.md correctly lists all 38 resources at lines 170-184.

---

### CRIT-03: OVERRIDES.md OVERRIDE-013 Significantly Understates UNSELECTABLE Scope

**Documentation location:** `OVERRIDES.md:706-757` (OVERRIDE-013 table)
**Documentation quote:** Lists 7 properties across 5 resources (Client, User, Task, Milestone, Expense)
**Code location:** Multiple resource files (see table below)
**Code evidence:**

| Resource | OVERRIDES.md Lists | Actual UNSELECTABLE in Code | Missing from OVERRIDES.md |
|---|---|---|---|
| Client | `additional_privileges` | `additional_privileges`, `image_thumb_large`, `image_thumb_medium`, `image_thumb_small` | 3 thumbnail properties |
| User | `additional_privileges` | 20 properties (see below) | 19 properties |
| Task | `subtasks_order` | `subtasks_order` | — (correct) |
| Milestone | `linked_tasklists` | `linked_tasklists` | — (correct) |
| Expense | `image_thumb_large`, `image_thumb_medium`, `image_thumb_small` | `image_thumb_large`, `image_thumb_medium`, `image_thumb_small` | — (correct) |
| File | *NOT LISTED* | `image_thumb_large`, `image_thumb_medium`, `image_thumb_small` | Entire resource missing |

**User.php UNSELECTABLE (20 properties):** `additional_privileges`, `date_format`, `time_format`, `decimal_sep`, `thousands_sep`, `has_submitted_review`, `image_thumb_large`, `image_thumb_medium`, `image_thumb_small`, `is_online`, `language`, `theme`, `menu_shortcut`, `user_hash`, `annual_leave_days_number`, `password`, `workflows`, `week_start`, `assigned_projects`, `managed_projects`

OVERRIDES.md documents 7 properties across 5 resources; the actual code has **32 properties across 6 resources**. File.php is entirely missing from the OVERRIDES table.

**Verification status:** CONFIRMED — code directly read from all UNSELECTABLE constants.

---

### CRIT-04: TODO-LIST.md Referenced But Does Not Exist

**Documentation location:** `CLAUDE.md:1003` ("Package Documentation Files" table), `PACKAGE-DEV.md:184` (directory structure)
**Documentation quote:** `TODO-LIST.md | Missing features and improvements` / `TODO-LIST.md # Missing features tracker`
**Code location:** Project root directory
**Code evidence:** File does not exist on disk. `ls TODO-LIST.md` returns "No such file or directory". The discovery step (Step 1) already noted this was "previously existed, now removed."

Any AI agent or developer following CLAUDE.md to find the missing features list will hit a dead end.

**Verification status:** CONFIRMED — file verified absent.

---

### CRIT-05: PACKAGE-DEV.md Directory Structure Lists Non-Existent File

**Documentation location:** `PACKAGE-DEV.md:184`
**Documentation quote:** Shows `TODO-LIST.md` and `Cache.php` in directory tree
**Code location:** `src/` directory
**Code evidence:** `TODO-LIST.md` doesn't exist (same as CRIT-04). Also, the structure shows `src/Cache.php` at the `src/` root, but the actual path is `src/Cache/Cache.php` (inside a `Cache/` subdirectory).

**Verification status:** CONFIRMED — `src/Cache.php` does not exist, `src/Cache/Cache.php` does.

---

## Stale Findings

### STALE-01: PACKAGE-DEV.md Version Header

**Documentation location:** `PACKAGE-DEV.md:4`
**Documentation quote:** `Version: 0.5.7`
**Code location:** `CHANGELOG.md:12`
**Code evidence:** Current version is `0.6.1` (released 2025-12-08). PACKAGE-DEV.md is 2 minor versions behind. Also, `src/Entity/AbstractResource.php:13` says `@version 0.5.7`.

**Verification status:** CONFIRMED.

---

### STALE-02: PACKAGE-DEV.md Lists 26 Entity Map Entries; Actual Has More

**Documentation location:** `PACKAGE-DEV.md:566-599` (EntityMap Configuration section)
**Documentation quote:** Shows example entries for the classMap structure
**Code location:** `default.paymoapi.config.json:84+`
**Code evidence:** The config file has entries for all 38 resources (plus their collection variants). PACKAGE-DEV.md provides examples but doesn't list all entries. While this isn't necessarily wrong (it's examples), it references a "26 unique resource type entries" count from the research report that matches, but the examples only show a few.

**Verification status:** CONFIRMED as stale representation, not actively misleading.

---

### STALE-03: PACKAGE-DEV.md "Required Constants" Section Missing FILTER_ONLY

**Documentation location:** `PACKAGE-DEV.md:241-254` (Required Constants section)
**Documentation quote:** Lists 10 required constants: LABEL, API_ENTITY, API_PATH, REQUIRED_CREATE, READONLY, CREATEONLY, UNSELECTABLE, INCLUDE_TYPES, PROP_TYPES, WHERE_OPERATIONS
**Code location:** All resource files
**Code evidence:** While `FILTER_ONLY` is not actually implemented as a constant (no resource defines it), the research report identifies 4 filter-only properties. The PACKAGE-DEV.md doesn't mention the concept of filter-only properties at all. These are handled via READONLY + inline comments, which is an undocumented pattern.

**Verification status:** CONFIRMED — not a constant that exists, but a concept gap.

---

### STALE-04: Paymo.php Docblock Claims "1-second delay"

**Documentation location:** `src/Paymo.php:77`
**Documentation quote:** `Automatic rate limiting (1-second delay between requests)`
**Code location:** `src/Utility/RateLimiter.php:81`
**Code evidence:** Same as CRIT-01. The Paymo.php class docblock repeats the 1-second claim. This is the code's own docblock being wrong about its own behavior.

**Verification status:** CONFIRMED (same evidence as CRIT-01).

---

## Incomplete Findings

### INC-01: CLAUDE.md Does Not Document CRUD Restrictions

CLAUDE.md doesn't mention which resources have restricted CRUD operations:
- **Company**: No list(), create(), delete() — only fetch() and update()
- **CommentThread**: No create() — only fetch() and list()
- **Session**: No update() — only fetch(), list(), create(), delete()
- **EstimateTemplateGallery**: No create(), update(), delete() — read-only
- **InvoiceTemplateGallery**: No create(), update(), delete() — read-only

A developer or AI agent following CLAUDE.md's CRUD examples may attempt operations that throw RuntimeException.

---

### INC-02: CLAUDE.md Does Not Document UNSELECTABLE Concept

CLAUDE.md doesn't mention the UNSELECTABLE constant at all. Developers who include UNSELECTABLE properties in field selection will get HTTP 400 errors. PACKAGE-DEV.md documents this well (`PACKAGE-DEV.md:393-440`), but CLAUDE.md omits it entirely.

---

### INC-03: CLAUDE.md Does Not Document Filter-Only Properties

Four properties are valid in WHERE clauses but not returned in responses:
- `Booking.project_id`, `Booking.task_id`, `Booking.date_interval`
- `TimeEntry.time_interval`

CLAUDE.md doesn't mention this pattern. OVERRIDES.md doesn't have a dedicated override for it either (it's only in inline code comments).

---

### INC-04: CLAUDE.md Does Not Document Response Key Anomalies

OVERRIDES.md documents response key anomalies (OVERRIDE-009, OVERRIDE-010), but CLAUDE.md doesn't mention them. An AI agent helping debug response parsing for ProjectTemplate, ProjectTemplateTask, ProjectTemplateTasklist, RecurringProfile, or the gallery resources would miss this critical context.

---

### INC-05: CLAUDE.md Collection Parent Filter Requirements Incomplete

CLAUDE.md mentions that some collections require parent filters but doesn't list all of them. `OVERRIDES.md:OVERRIDE-005` documents File, Booking, InvoiceItem, EstimateItem. CLAUDE.md's recipes show some filtering patterns but don't warn about the required filters.

---

### INC-06: Research Report Lists Undocumented Properties Not in SDK

The research report identifies several undocumented API properties not yet in any SDK PROP_TYPES:

| Resource | Property | Research Report Source |
|---|---|---|
| ClientContact | `additional_privileges` | Thread 2 |
| Task | `files_count` | Webhook payload examples |
| Task | `comments_count` | Webhook payload examples |
| Tasklist | `tasks_count` | Listed but SDK has it as nested object |
| Estimate | `delivery_date` | "May exist (present on Invoice)" |
| Invoice | `token` | In SDK but not in research report undocumented list |

Note: Several of these (ClientContact.additional_privileges, Task files/comments_count) may be available from the API but aren't tracked in PROP_TYPES yet.

---

### INC-07: Discussion.comments_count Not in Research Report

`Discussion.php` includes `comments_count` (integer, readonly) in PROP_TYPES, but the research report's undocumented properties table doesn't list it for Discussion. It only lists `comments_count` for Task (from webhook payloads).

---

### INC-08: OVERRIDES.md OVERRIDE-005 Missing SDK Collection Requirement for Booking.date_interval

`OVERRIDES.md:OVERRIDE-005` documents required parent filters including Booking needing "Date range (start_date AND end_date) OR user_task_id, task_id, project_id, user_id". But the `BookingCollection.php` validation may also accept `date_interval` as a filter. The override description doesn't mention `date_interval` as an alternative.

---

### INC-09: Report Complex PROP_TYPES Not Documented in CLAUDE.md

Report has nested object types (`include` and `extra` sub-objects with many boolean properties). CLAUDE.md Section 11 says to check resource files but doesn't explain nested/complex PROP_TYPES syntax. PACKAGE-DEV.md does cover this at line 546-558.

---

### INC-10: OVERRIDES.md Does Not Document File.php UNSELECTABLE

As noted in CRIT-03, `File.php` has UNSELECTABLE = `['image_thumb_large', 'image_thumb_medium', 'image_thumb_small']` with `@override OVERRIDE-013`, but OVERRIDES.md's OVERRIDE-013 table at line 706 doesn't include File in the resource list.

---

### INC-11: User.php UNSELECTABLE Massively Expanded Beyond OVERRIDES.md

As noted in CRIT-03, User.php has 20 UNSELECTABLE properties but OVERRIDES.md only lists 1 (`additional_privileges`). The 19 additional entries (date_format, time_format, decimal_sep, thousands_sep, has_submitted_review, image_thumb_large/medium/small, is_online, language, theme, menu_shortcut, user_hash, annual_leave_days_number, password, workflows, week_start, assigned_projects, managed_projects) should be documented in OVERRIDES.md OVERRIDE-013.

---

### INC-12: Client.php UNSELECTABLE Includes Thumbnails Beyond OVERRIDES.md

Client.php has 4 UNSELECTABLE properties but OVERRIDES.md only lists `additional_privileges`. The 3 additional thumbnail entries (`image_thumb_large`, `image_thumb_medium`, `image_thumb_small`) should be documented.

---

## Minor Findings

### MIN-01: PACKAGE-DEV.md Resource Documentation URLs Table Missing Some Resources

`PACKAGE-DEV.md:59-92` lists official API doc URLs for 30 resources. Missing from the table: ProjectTemplateTask, ProjectTemplateTasklist, EstimateTemplateGallery, InvoiceTemplateGallery. While these lack official API docs (OVERRIDE-008), the table should note their absence rather than silently omitting them.

---

### MIN-02: CLAUDE.md Inconsistent Terminology for "Connection"

CLAUDE.md Section 1 uses both "connection" and "Paymo connection" interchangeably. Section 2 refers to "connection instance" and "connection settings." The terminology is consistent enough not to mislead but could be tightened.

---

### MIN-03: README.md "Alternative: Username/password authentication" May Be Outdated

`README.md:65`: `$paymo = Paymo::connect(['username', 'password']);` — The API docs recommend API keys. While the code supports this, Session-based auth is the documented alternative, not basic auth with password.

---

### MIN-04: CLAUDE.md Section Numbers Don't Match Heading Hierarchy

CLAUDE.md uses numbered sections (1-14) but the heading structure mixes `##` and `###` levels. Section 13 "Finding More Information" is `##` level but its subsections use `###`. This is consistent internally but the numbering creates expectations of a flat structure.

---

### MIN-05: PACKAGE-DEV.md Table of Contents Links May Not Resolve

`PACKAGE-DEV.md:26-37` has a numbered ToC with anchor links (`#1-quick-reference`). These anchors use numbers which may not resolve correctly in all markdown renderers since heading IDs typically strip leading numbers.

---

### MIN-06: README.md Configuration Table Missing rateLimit Settings

`README.md:283-292` shows configuration options but omits the `rateLimit` section from `default.paymoapi.config.json` (which includes `enabled`, `minDelayMs`, `safetyBuffer`, `maxRetries`, `retryDelayMs`).

---

## Cross-Document Contradictions

### CROSS-01: Rate Limiting Delay — Three Documents Agree on Wrong Value

| Document | Location | Claim |
|---|---|---|
| `CLAUDE.md` | Line 992 | "1-second delay between requests" |
| `README.md` | Line 524 | "built-in 1-second delay between requests" |
| `src/Paymo.php` | Line 77 | "1-second delay between requests" |
| **Actual code** | `RateLimiter.php:81` | `MIN_DELAY_MS = 200` (200 milliseconds) |

All three non-code documents agree on the wrong value. The code itself uses 200ms.

---

### CROSS-02: Resource Count — README Says 38, CLAUDE.md Only Lists 28

| Document | Location | Resources Listed |
|---|---|---|
| `README.md` | Lines 170-184 | All 38 resources |
| `CLAUDE.md` | Lines 245-282 | 28 resources (missing 10) |
| `PACKAGE-DEV.md` | Lines 59-92 | 30 resources (missing 4 gallery/template variants) |

README.md is correct. CLAUDE.md and PACKAGE-DEV.md are incomplete.

---

### CROSS-03: UNSELECTABLE Properties — OVERRIDES.md vs Actual Code

| Source | Resource Count | Property Count |
|---|---|---|
| `OVERRIDES.md` OVERRIDE-013 | 5 resources | 7 properties |
| Actual code (all UNSELECTABLE constants) | 6 resources | 32 properties |

OVERRIDES.md significantly understates the scope. User.php alone has 20 UNSELECTABLE properties.

---

### CROSS-04: TODO-LIST.md — Referenced in Two Places, Doesn't Exist

| Document | Location | Reference |
|---|---|---|
| `CLAUDE.md` | Line 1003 | `TODO-LIST.md | Missing features and improvements` |
| `PACKAGE-DEV.md` | Line 184 | `TODO-LIST.md # Missing features tracker` |
| **On disk** | Project root | File does not exist |

---

## Missing Documentation

### SDK Features Not Documented Anywhere

| Feature | Location in Code | Notes |
|---|---|---|
| `protectDirtyOverwrites()` method | `AbstractResource.php` | Only in README.md examples, not in CLAUDE.md |
| `ignoreCache()` method | `AbstractResource.php` | Only in README.md |
| `registerCacheMethods()` | `Cache.php` | Only in README.md; allows custom cache backends (Redis, etc.) |
| `rollbackLifespan()` | `Cache.php` | Not documented in any markdown file |
| `flatten(['array' => true])` option | `AbstractCollection.php` | Added in v0.6.1 per CHANGELOG, not in CLAUDE.md or README.md |
| `flatten(['stripNull' => true])` option | `AbstractResource.php` | In README.md and CLAUDE.md |
| `raw()` method on collections | `AbstractCollection.php` | Referenced in pagination examples but not formally documented |
| Error handling configuration | `default.paymoapi.config.json` error section | Not documented in any markdown file |
| `randomColor.workflowstatus` config | `default.paymoapi.config.json` | Auto-color assignment for WorkflowStatus, not documented |
| Complex REQUIRED_CREATE syntax (`||`, `&`) | `PACKAGE-DEV.md:322-336` | Documented in PACKAGE-DEV.md but not CLAUDE.md |

### API Features Not in SDK

| API Feature | Source | SDK Status |
|---|---|---|
| Leave management endpoints (4) | Research report (PR #30) | Not implemented |
| StatsReport endpoint | Research report (PR #30) | Not implemented |
| `partial_include` syntax | Research report Thread 5 | Not implemented |
| Nested include dot notation | API docs | Not implemented in SDK query builder |
| Webhook conditional filtering (`where` on webhook) | API docs | Webhook.php has `where` property but no builder |
| Report PDF/XLSX export | API docs | No dedicated export method |
| Task `progress_status` | GitHub Issue #25 | Not in API (7+ years) |
| User `costs_per_hour` | GitHub Issue #70 | Not in API |

---

## Gap Matrix: SDK vs API

### Per-Resource PROP_TYPES Audit Summary

| Resource | SDK Props | API Doc Props | Undocumented in SDK | Missing from SDK | Override? |
|---|---|---|---|---|---|
| **Booking** | 16 | 9 | 7 (creator_id, user_id, start_time, end_time, booked_hours, project_id, task_id, date_interval) | — | — |
| **Client** | 21 | 15 | 2 (due_interval, additional_privileges) | — | OVERRIDE-001, -006, -013 |
| **ClientContact** | 19 | 15 | 2 (client_id, additional_privileges) | — | — |
| **Comment** | 9 | 7 | — | — | — |
| **CommentThread** | 7 | 7 | — | — | — |
| **Company** | 70+ | 32 | 38+ (email templates, SMTP, payment gateways, limits) | — | OVERRIDE-002, -011 |
| **Discussion** | 8 | 7 | 1 (comments_count) | — | — |
| **Estimate** | 31 | 25 | 3 (brief_description, discount fields, download_token) | — | — |
| **EstimateItem** | 10 | 7 | 1 (estimate_id — OVERRIDE-007) | — | OVERRIDE-007 |
| **EstimateTemplate** | 9 | ~6 | 1 (estimates_count) | — | OVERRIDE-008 |
| **EstimateTemplateGallery** | 8 | ~6 | — | — | OVERRIDE-010 |
| **Expense** | 18 | 13 | 1 (download_token) | — | OVERRIDE-013 |
| **File** | 21 | 14 | 4 (mime, external_url, external_service, download_url) | — | OVERRIDE-013 |
| **Invoice** | 39 | 30 | 4 (delivery_date, download_token, token, active, options) | — | OVERRIDE-012 |
| **InvoiceItem** | 12 | 7 | 1 (invoice_id — OVERRIDE-007) | — | OVERRIDE-007 |
| **InvoicePayment** | 7 | 5 | — | — | OVERRIDE-008 |
| **InvoiceTemplate** | 9 | ~6 | 1 (invoices_count) | — | OVERRIDE-008 |
| **InvoiceTemplateGallery** | 8 | ~6 | — | — | OVERRIDE-010 |
| **Milestone** | 11 | 9 | 1 (linked_tasklists) | — | OVERRIDE-013 |
| **Project** | 25 | 22 | 1 (billing_type) | — | — |
| **ProjectStatus** | 7 | 7 | — | — | OVERRIDE-008 |
| **ProjectTemplate** | 5 | ~4 | — | — | OVERRIDE-009 |
| **ProjectTemplateTask** | 16 | 9 | 5 (flat_billing, estimated_price, price, duration, start_date_offset) | — | OVERRIDE-009 |
| **ProjectTemplateTasklist** | 7 | 5 | 1 (milestone_id) | — | OVERRIDE-009 |
| **RecurringProfile** | 32 | 27 | 2 (options, language-deprecated) | — | OVERRIDE-009, -012 |
| **RecurringProfileItem** | 10 | 8 | — | — | — |
| **Report** | 22+ nested | 20+ nested | 4 (active, share_users_ids, invoice_id, download_token) | — | — |
| **Session** | 6 | 5 | — | — | OVERRIDE-004 |
| **Subtask** | 11 | 8 | 2 (completed_on, completed_by) | — | — |
| **Task** | 29 | 22 | 5 (cover_file_id, price, start_date, recurring_profile_id, billing_type) | 2 (files_count, comments_count from webhooks) | OVERRIDE-011, -013 |
| **TaskAssignment** | 7 | 5 | 2 (tracked_time, task_complete) | — | — |
| **TaskRecurringProfile** | 33 | ~25 | several (code, company_id, processing fields) | — | — |
| **Tasklist** | 8 | 6 | 1 (tasks_count nested object) | — | — |
| **TimeEntry** | 17 | 14 | 2 (client_id, time_interval) | — | — |
| **User** | 34 | 22 | 6 (annual_leave_days_number, has_submitted_review, menu_shortcut, user_hash, workflows, additional_privileges) | — | OVERRIDE-013 |
| **Webhook** | 8 | 7 | — | — | — |
| **Workflow** | 5 | 4 | — | — | — |
| **WorkflowStatus** | 8 | 7 | — | — | — |

### CRUD Operations Audit

| Resource | API Operations | SDK Operations | Discrepancy |
|---|---|---|---|
| Company | GET one, PUT | fetch, update (list/create/delete throw) | Correct |
| CommentThread | GET list, GET one | fetch, list (create throws, update/delete NOT blocked) | **update/delete should throw** |
| Session | GET list, GET one, POST, DELETE | fetch, list, create, delete (update throws) | Correct |
| EstimateTemplateGallery | GET list, GET one | fetch, list (create/update/delete throw) | Correct |
| InvoiceTemplateGallery | GET list, GET one | fetch, list (create/update/delete throw) | Correct |
| All others | Full CRUD | Full CRUD | Correct |

### INCLUDE_TYPES Audit

All 82 include relationships from the research report are present in SDK INCLUDE_TYPES constants. No missing includes found. The SDK correctly maps:
- Single vs collection (boolean flag)
- All include key names match API expectations

### WHERE_OPERATIONS Audit

11 of 38 resources define non-empty WHERE_OPERATIONS restrictions. All match the research report's documented restrictions. No discrepancies found between SDK restrictions and known API filter behavior.

---

## Inline Comment Issues

### Files with Incorrect or Misleading Comments

| File | Line | Issue |
|---|---|---|
| `src/Paymo.php` | 77 | Claims "1-second delay between requests" — actual delay is 200ms |
| `src/Entity/AbstractResource.php` | 13 | `@version 0.5.7` — project is at 0.6.1 |

### Files with Undocumented Override Comments Missing @override Tag

| File | Property | Has OVERRIDE-013 in OVERRIDES.md? | Has @override comment? |
|---|---|---|---|
| `Client.php` UNSELECTABLE | `image_thumb_large/medium/small` | Listed for Expense but not Client thumbnails | Yes (references OVERRIDE-013) |
| `User.php` UNSELECTABLE | 19 properties beyond `additional_privileges` | No | Yes (references OVERRIDE-013) |
| `File.php` UNSELECTABLE | `image_thumb_large/medium/small` | No (File not in OVERRIDE-013 table) | Yes (references OVERRIDE-013) |

These all correctly reference OVERRIDE-013 in code comments, but OVERRIDES.md doesn't reflect the full scope.

---

## Verification Report

**Approach:** Same-agent re-review (challenge pass)

**Process:** After the initial analysis pass, all 5 Critical findings and 4 Stale findings were re-examined with the explicit goal of finding evidence that the documentation IS correct. Each finding was checked by re-reading the relevant code locations and attempting to find alternative interpretations.

**Results:**

| Finding | Challenge Result | Notes |
|---|---|---|
| CRIT-01 (Rate limiting) | CONFIRMED | No override of 200ms default found anywhere. RETRY_DELAY_MS=1000 is retry-only. |
| CRIT-02 (Missing 10 resources) | CONFIRMED | README.md has all 38, CLAUDE.md has 28. Counted twice. |
| CRIT-03 (UNSELECTABLE scope) | CONFIRMED | Read all 6 UNSELECTABLE constants directly from code. |
| CRIT-04 (TODO-LIST.md) | CONFIRMED | ls returns "No such file or directory". |
| CRIT-05 (PACKAGE-DEV.md structure) | CONFIRMED | `src/Cache.php` path is wrong; actual is `src/Cache/Cache.php`. |
| STALE-01 (Version header) | CONFIRMED | CHANGELOG.md shows 0.6.1, PACKAGE-DEV.md shows 0.5.7. |
| STALE-02 (EntityMap entries) | CONFIRMED | Stale but not actively misleading. |
| STALE-03 (Missing FILTER_ONLY) | CONFIRMED | Concept exists in code comments but no constant or documentation. |
| STALE-04 (Paymo.php docblock) | CONFIRMED | Same evidence as CRIT-01. |

**Confirmation rate:** 9/9 (100%) — no findings rejected or modified during re-review.
