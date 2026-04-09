# Documentation Plan

**Audit depth:** Focused Audit
**Documentation output location:** `docs/`
**Date:** 2026-04-08

---

## Document Set

### 1. CLAUDE.md Updates (Existing — AI Instruction File)

**Type:** AI Instructions
**Audience:** AI coding agents
**Priority:** Highest — errors here propagate to every AI-assisted interaction

**Planned fixes:**

| ID | Finding | Location | Fix Description |
|---|---|---|---|
| CRIT-01 | Rate limiting claim is wrong | Line 992 | Change "1-second delay between requests" to "200ms minimum delay between requests (configurable via `rateLimit.minDelayMs` in config)" |
| CRIT-02 | 10 of 38 resources missing from tables | Lines 244-282 | Add Session, CommentThread, ProjectStatus, ProjectTemplate, ProjectTemplateTask, ProjectTemplateTasklist, InvoiceTemplate, EstimateTemplate, EstimateTemplateGallery, InvoiceTemplateGallery to appropriate resource tables |
| CRIT-04 | TODO-LIST.md in documentation files table | Line 893 | Remove TODO-LIST.md row, add OVERRIDES.md row (`OVERRIDES.md \| API deviation documentation and undocumented features`) |
| INC-01 | No CRUD restriction documentation | After Section 4 | Add subsection "CRUD Restrictions" listing: Company (fetch/update only), CommentThread (fetch/list only), Session (no update), EstimateTemplateGallery (read-only), InvoiceTemplateGallery (read-only) |
| INC-02 | UNSELECTABLE concept not documented | After Section 11 or in "Important Notes" | Add note: "Some properties are UNSELECTABLE — they appear in responses but cannot be explicitly requested via field selection. Attempting to select them returns HTTP 400. Check each resource's `UNSELECTABLE` constant." |
| INC-03 | Filter-only properties not documented | In "Important Notes" or new subsection | Add note: "Four properties are valid in WHERE clauses but not returned in responses: `Booking.project_id`, `Booking.task_id`, `Booking.date_interval`, `TimeEntry.time_interval`" |
| INC-04 | Response key anomalies not mentioned | In "Important Notes" | Add note referencing OVERRIDES.md OVERRIDE-009/010 for resources with non-standard response keys (ProjectTemplate, ProjectTemplateTask, ProjectTemplateTasklist, RecurringProfile, gallery resources) |
| INC-05 | Collection parent filter requirements incomplete | In "Important Notes" or Section 5 | Add note: "Some collections require parent filters: File (project_id), Booking (date range OR user/task/project ID), InvoiceItem (invoice_id), EstimateItem (estimate_id). See OVERRIDES.md OVERRIDE-005." |

**Outline of new CLAUDE.md sections/additions:**

- Section 3 "Available Resources" → expand tables with 4 new categories:
  - Add "Template Resources" table (ProjectTemplate, InvoiceTemplate, EstimateTemplate)
  - Add "Template Detail Resources" table (ProjectTemplateTask, ProjectTemplateTasklist)
  - Add "Gallery Resources" table (EstimateTemplateGallery, InvoiceTemplateGallery)
  - Add Session, CommentThread, ProjectStatus to "Supporting Resources" table
- Section 4 "CRUD Operations" → add new subsection "### CRUD Restrictions" after the Delete section
- Section 14 "Important Notes" → add items for UNSELECTABLE, filter-only, response key anomalies, parent filter requirements

**Source material:** analysis.md CRIT-01, CRIT-02, CRIT-04, INC-01 through INC-05, CROSS-01, CROSS-02, CROSS-04

---

### 2. OVERRIDES.md Updates (Existing — Override Documentation)

**Type:** API Reference / Override Documentation
**Audience:** Developers, AI agents
**Priority:** High — OVERRIDE-013 significantly understates UNSELECTABLE scope

**Planned fixes:**

| ID | Finding | Location | Fix Description |
|---|---|---|---|
| CRIT-03 | OVERRIDE-013 table incomplete | Lines 706-714 | Replace the 7-row table with the full 32-property table across 6 resources (Client: 4, User: 20, Task: 1, Milestone: 1, Expense: 3, File: 3) |
| CRIT-04a | TODO-LIST.md reference in OVERRIDE-007 evidence | Line 556 | Reword: remove "TODO-LIST.md confirms these as verified properties (lines 279, 316)" — replace with "Verified via live API testing" |
| CRIT-04b | TODO-LIST.md reference in OVERRIDE-007 code comment | Line 561 | Remove comment line `// These properties ARE documented in TODO-LIST.md despite API doc gap` |
| INC-10 | File.php missing from OVERRIDE-013 | Lines 706-714 | Add File resource row: `File \| image_thumb_large, image_thumb_medium, image_thumb_small \| Thumbnail URLs, conditionally returned` |
| INC-11 | User.php UNSELECTABLE massively understated | Lines 706-714 | Expand User row from 1 property to 20 properties, grouped by category |
| INC-12 | Client.php thumbnails missing | Lines 706-714 | Expand Client row from 1 property to 4 properties |

**Replacement OVERRIDE-013 table structure:**

```
| Resource | Properties | Count | Notes |
|----------|-----------|-------|-------|
| Client | additional_privileges, image_thumb_large, image_thumb_medium, image_thumb_small | 4 | Internal field + thumbnails |
| User | additional_privileges, date_format, time_format, decimal_sep, thousands_sep, has_submitted_review, image_thumb_large, image_thumb_medium, image_thumb_small, is_online, language, theme, menu_shortcut, user_hash, annual_leave_days_number, password, workflows, week_start, assigned_projects, managed_projects | 20 | Preferences, internal fields, thumbnails |
| Task | subtasks_order | 1 | Write-only field for reordering |
| Milestone | linked_tasklists | 1 | Array of linked tasklist IDs |
| Expense | image_thumb_large, image_thumb_medium, image_thumb_small | 3 | Thumbnail URLs, conditionally returned |
| File | image_thumb_large, image_thumb_medium, image_thumb_small | 3 | Thumbnail URLs, conditionally returned |
```

**Source material:** analysis.md CRIT-03, CRIT-04, INC-10, INC-11, INC-12, CROSS-03

---

### 3. PACKAGE-DEV.md Updates (Existing — Internal Development Guide)

**Type:** Developer Guide
**Audience:** Package contributors
**Priority:** Medium — stale but less impactful than CLAUDE.md

**Planned fixes:**

| ID | Finding | Location | Fix Description |
|---|---|---|---|
| STALE-01 | Version header outdated | Line 4 | Change `Version: 0.5.7` to `Version: 0.6.1` |
| CRIT-05a | Directory structure wrong path | Line 182 | Change `Cache.php` to `Cache/Cache.php` (add Cache/ subdirectory) |
| CRIT-05b | Directory structure lists deleted file | Line 184 | Remove `TODO-LIST.md` line from directory tree |
| STALE-03 | FILTER_ONLY concept undocumented | Near UNSELECTABLE docs (~line 393) | Add brief subsection explaining filter-only properties pattern: properties in PROP_TYPES + READONLY that are valid in WHERE but not returned in responses. List the 4 known instances. |
| MIN-01 | Resource URLs table missing entries | Lines 59-92 | Add rows for ProjectTemplateTask, ProjectTemplateTasklist, EstimateTemplateGallery, InvoiceTemplateGallery with note "No official API documentation page (see OVERRIDE-008)" |
| CRIT-04c | TODO-LIST.md in TypeScript section | Line 1068 | Remove sentence "This is tracked in `TODO-LIST.md`." |
| CRIT-04d | TODO-LIST.md in modification checklist | Line 1134 | Remove checklist item `- [ ] Have I updated \`TODO-LIST.md\` if fixing a known issue?` |
| CRIT-04e | TODO-LIST.md in "When In Doubt" | Line 1644 | Remove item `4. Check TODO-LIST.md for known issues` and renumber |
| CRIT-04f | TODO-LIST.md in "Keeping Up To Date" | Line 1651 | Remove item `- Update TODO-LIST.md when new features appear` |

**Source material:** analysis.md STALE-01, CRIT-05, STALE-03, MIN-01, CRIT-04, CROSS-04

---

### 4. README.md Updates (Existing — User-Facing Documentation)

**Type:** User Guide
**Audience:** SDK consumers (PHP developers)
**Priority:** Medium

**Planned fixes:**

| ID | Finding | Location | Fix Description |
|---|---|---|---|
| CRIT-01 | Rate limiting claim wrong | Line 524 | Change "built-in 1-second delay between requests" to "built-in 200ms minimum delay between requests" |
| MIN-03 | Username/password auth unclear | Line 65 | Add clarifying note that Session-based auth via API key is recommended; username/password support exists but Session resource is the documented alternative |
| MIN-06 | rateLimit config missing from table | Lines 283-292 | Add `rateLimit` configuration section with keys: `enabled`, `minDelayMs`, `safetyBuffer`, `maxRetries`, `retryDelayMs` |

**Source material:** analysis.md CRIT-01, CROSS-01, MIN-03, MIN-06

---

### 5. `docs/gap-matrix.md` (New — Primary Task Deliverable)

**Type:** API Reference / Audit Artifact
**Filename:** `docs/gap-matrix.md`
**Audience:** Developers, AI agents, package maintainers
**Description:** Formal gap matrix comparing SDK coverage against Paymo API documentation

**Outline:**

```
# Paymo API PHP SDK — Gap Matrix
  (frontmatter: title, description, audience, lastUpdated, lastAuditedAgainst, status)

## How to Read This Document
  - Column definitions, override notation, data sources

## Per-Resource Property Coverage
  - Table: Resource | SDK Props | API Doc Props | SDK-Only Props | Missing from SDK | Override References
  - 38 rows (one per resource)
  - Notes column for significant gaps (Company: 38+ undocumented, User: 20 UNSELECTABLE)

## CRUD Operation Coverage
  - Table: Resource | API Operations | SDK Operations | Restrictions | Notes
  - Highlight restricted resources (Company, CommentThread, Session, galleries)

## INCLUDE_TYPES Coverage
  - Summary: all 82 relationships present, no gaps
  - Brief table of include counts per resource

## WHERE_OPERATIONS Coverage
  - Summary: 11 resources with non-empty restrictions, all match API
  - Table of resources with WHERE restrictions

## Undocumented API Features Supported by SDK
  - Pagination (OVERRIDE-003)
  - UNSELECTABLE properties (OVERRIDE-013)
  - Filter-only properties (Booking, TimeEntry)
  - Response key anomalies (OVERRIDE-009, OVERRIDE-010)
  - Parent filter requirements (OVERRIDE-005)
  - Undocumented properties per resource (Company: 38+, User: 6, etc.)

## API Features Not Yet in SDK
  - Leave management endpoints (4)
  - StatsReport endpoint
  - partial_include syntax
  - Nested include dot notation
  - Report PDF/XLSX export
  - Webhook conditional filtering builder

## Discrepancies Between SDK and API Docs
  - Summary of intentional deviations (13 active overrides)
  - Cross-reference to OVERRIDES.md for each
```

**Source material:** analysis.md Gap Matrix section, PROP_TYPES audit, CRUD audit, INCLUDE_TYPES audit, WHERE_OPERATIONS audit, Missing Documentation section

---

### 6. `docs/documentation.md` (New — Documentation Index)

**Type:** Index / Navigation
**Filename:** `docs/documentation.md`
**Audience:** All
**Description:** Root index linking to all documentation with descriptions and status

**Outline:**

```
# Paymo API PHP SDK — Documentation Index
  (frontmatter: title, description, audience, lastUpdated, status)

## User-Facing Documentation
  - README.md — Installation, quick start, features, usage examples (status: current)

## AI Assistant Guide
  - CLAUDE.md — Connection management, CRUD patterns, resource reference, recipes (status: current)

## Developer Documentation
  - PACKAGE-DEV.md — Architecture, resource class structure, constants, type system, templates (status: current)
  - CHANGELOG.md — Version history (status: current)

## API Reference
  - OVERRIDES.md — API deviations, undocumented features, SDK handling (status: current)
  - docs/gap-matrix.md — SDK vs API coverage matrix (status: current)
  - docs/api-documentation/ — Mirror of official Paymo API docs (status: stale — frozen since 2022)

## Test Documentation
  - tests/README.md — Test suite guide, CLI options, safety guarantees (status: current)

## Configuration Reference
  - default.paymoapi.config.json — Default config (status: current)
```

**Source material:** discovery.md Documentation Inventory

---

## Inline Comment Fixes

| # | File | Line | Current Text | Proposed Text | Reason |
|---|------|------|-------------|---------------|--------|
| 1 | `src/Paymo.php` | 77 | `Automatic rate limiting (1-second delay between requests)` | `Automatic rate limiting (200ms minimum delay between requests, configurable)` | CRIT-01: Actual `MIN_DELAY_MS` is 200 in `RateLimiter.php:81`, not 1000. The 1-second value is `RETRY_DELAY_MS` (retry backoff after 429), not the inter-request delay. |
| 2 | `src/Entity/AbstractResource.php` | 13 | `@version    0.5.7` | `@version    0.6.1` | STALE-01: Current version per `CHANGELOG.md` is 0.6.1 (released 2025-12-08). |

---

## Documentation Index Plan

The `docs/documentation.md` file will serve as the root index for all project documentation:

- **Project name:** Paymo API PHP SDK
- **One-line description:** PHP SDK for the Paymo project management REST API
- **Last audit date:** 2026-04-08
- **Grouping:** User-Facing, AI Instructions, Developer, API Reference, Tests, Configuration
- **Per-entry info:** File path (relative), one-line description, audience, status indicator
- **Status indicators:** `current` (verified in this audit), `stale` (known outdated, e.g., API docs mirror), `needs-review` (partially verified)

---

## Best Practices Applied

| Practice | Source |
|----------|--------|
| YAML frontmatter on new documents (title, description, audience, lastUpdated, lastAuditedAgainst, status) | Workflow Output Quality Rules |
| Single H1 per document, strict H1 > H2 > H3 hierarchy | Workflow Output Quality Rules |
| Self-contained semantic sections (no "as mentioned above") | Workflow AI-readiness standards |
| Code examples fenced with language identifier, verified against codebase | Workflow Output Quality Rules |
| All file paths and cross-references verified before finalizing | Workflow Output Quality Rules |
| Evidence-based: every claim traces to specific code location | Workflow Output Quality Rules |
| Consistent terminology across all documents | Workflow Output Quality Rules |
| Documentation linting best practices | Unresearched — recommend manual review (web search not used) |

---

## User Decisions

- **Plan approved:** 2026-04-08 (single-pass approval, no modifications requested)
- **Depth level:** Focused Audit (confirmed in discovery step)
- **llms.txt:** Not requested
- **Verification approach:** Same-agent re-review
- **Additional recommendation accepted:** Add OVERRIDES.md to CLAUDE.md "Package Documentation Files" table (replacing TODO-LIST.md)

---

## Verification Results

**Verification approach:** Same-agent re-review (4-part self-verification + parallel subagent verification)
**Date:** 2026-04-08

### 1. Accuracy Check

| Verification Target | Method | Result |
|---|---|---|
| Rate limiting 200ms claim | Read `RateLimiter.php:81` → `MIN_DELAY_MS = 200` | CORRECT |
| 10 added resource files exist | Checked all 10 `.php` files in `src/Entity/Resource/` | CORRECT (all exist) |
| UNSELECTABLE 32 properties / 6 resources | Read all 6 UNSELECTABLE constants from source | CORRECT |
| TODO-LIST.md removed from live docs | Searched all `.md` files — only in CHANGELOG.md (historical) and audit artifacts | CORRECT |
| rateLimit config values in README.md | Cross-referenced against `default.paymoapi.config.json:16-21` | CORRECT (enabled=true, minDelayMs=200, safetyBuffer=1, maxRetries=3, retryDelayMs=1000) |
| PACKAGE-DEV.md Cache path | Verified `src/Cache/Cache.php` exists on disk | CORRECT |
| Documentation index file links | Verified all 9 linked files exist on disk | CORRECT |

### 2. Internal Consistency Check

| Check | Result |
|---|---|
| Rate limiting terminology ("200ms minimum delay") consistent across CLAUDE.md, README.md, Paymo.php | CONSISTENT |
| UNSELECTABLE counts match across CLAUDE.md, OVERRIDES.md, PACKAGE-DEV.md | CONSISTENT (32 props, 6 resources) |
| TODO-LIST.md references removed from all live documentation | CONSISTENT |
| Resource count: CLAUDE.md now lists all 38 resources across 7 tables | CONSISTENT with README.md's resource list |

### 3. Completeness Check

All planned items from doc-plan.md were applied:
- CLAUDE.md: 8 fixes applied (CRIT-01, CRIT-02, CRIT-04, INC-01 through INC-05) + UNSELECTABLE added to constants table
- OVERRIDES.md: 4 fixes applied (CRIT-03, CRIT-04b, INC-10/11/12 via expanded table)
- PACKAGE-DEV.md: 9 fixes applied (STALE-01, CRIT-05a/b, STALE-03, MIN-01, CRIT-04c/d/e/f) + UNSELECTABLE table updated to match
- README.md: 3 fixes applied (CRIT-01, MIN-03, MIN-06)
- docs/gap-matrix.md: Written (new file, primary deliverable)
- docs/documentation.md: Written (new file, documentation index)
- Inline fixes: 2 applied (Paymo.php:77, AbstractResource.php:13)

### 4. Cross-Reference Check

| Link Source | Target | Status |
|---|---|---|
| docs/documentation.md → README.md | `../README.md` | EXISTS |
| docs/documentation.md → CLAUDE.md | `../CLAUDE.md` | EXISTS |
| docs/documentation.md → PACKAGE-DEV.md | `../PACKAGE-DEV.md` | EXISTS |
| docs/documentation.md → CHANGELOG.md | `../CHANGELOG.md` | EXISTS |
| docs/documentation.md → OVERRIDES.md | `../OVERRIDES.md` | EXISTS |
| docs/documentation.md → gap-matrix.md | `gap-matrix.md` | EXISTS |
| docs/documentation.md → api-documentation/ | `api-documentation/` | EXISTS |
| docs/documentation.md → tests/README.md | `../tests/README.md` | EXISTS |
| docs/documentation.md → config | `../default.paymoapi.config.json` | EXISTS |

### Additional Fixes (Not in Original Plan)

- **PACKAGE-DEV.md UNSELECTABLE table**: Updated from 5-resource/7-property table to 6-resource/32-property table to match corrected OVERRIDES.md OVERRIDE-013. This wasn't in the original doc-plan but was necessary for cross-document consistency after the OVERRIDES.md update.

### Parallel Subagent Verification

4 verification subagents dispatched to cross-check all changes against source code:

| Agent | Checks | Result |
|---|---|---|
| CLAUDE.md verification | 6 checks (CRIT-01, CRIT-02, CRIT-04, INC-01, INC-02–05, UNSELECTABLE constant) | All 6 CORRECT |
| OVERRIDES/PACKAGE-DEV/README verification | 11 checks across 3 files | All 11 CORRECT |
| Inline comment fixes | 2 checks (Paymo.php, AbstractResource.php) | Both APPLIED CORRECTLY |
| Gap matrix + documentation index | 6 checks | 4 CORRECT, 2 INCORRECT (fixed inline) |

**Issues found and fixed during verification:**
1. **gap-matrix.md INCLUDE_TYPES count**: Agent found 98 total relationships (not 82 as claimed from research report). Corrected to "98 include relationships." Also fixed Project include count (9, not 7) and Task include count (8, not 6).
2. **gap-matrix.md CommentThread CRUD row**: Agent found `delete()` is supported per class docblock, and `update()` is not supported (all properties READONLY). Corrected both gap-matrix.md and CLAUDE.md CRUD Restrictions table.

**Final verification status:** All changes verified correct after inline fixes. 0 unresolved issues.
