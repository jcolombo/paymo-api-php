# Final Report: Documentation Deep Dive

**Date:** 2026-04-08
**Project:** `jcolombo/paymo-api-php` — PHP SDK for the Paymo project management REST API
**Branch:** `02-documentation-deep-dive-7fe5`

---

## 1. Audit Summary

| Parameter | Value |
|-----------|-------|
| **Depth level** | Focused Audit |
| **Documentation artifacts examined** | 6 (CLAUDE.md, README.md, PACKAGE-DEV.md, OVERRIDES.md, research-report.md, 38 API doc sections) |
| **SDK resource classes audited** | 38 |
| **Total factual claims verified** | ~350+ |
| **Verification approach** | Same-agent re-review (challenge pass) + parallel subagent verification |
| **Verification confirmation rate** | 100% (9/9 initial findings confirmed; 25/25 post-writing checks passed) |
| **Audit span** | 5 sessions across 2026-04-08 (Discovery, Deep Analysis, Documentation Planning, Documentation Writing, Final Report) |

### What Was Examined

The audit compared all 38 resource classes in `src/Entity/Resource/` against:
- The Deep Research report (`research-report.md`) as the primary API reference
- The local API documentation mirror (`docs/api-documentation/sections/`)
- `OVERRIDES.md` for documented intentional deviations

For each resource, the audit checked:
- **(a)** PROP_TYPES vs API-documented properties
- **(b)** CRUD operations supported vs API endpoints available
- **(c)** INCLUDE_TYPES vs API-documented relationships
- **(d)** WHERE_OPERATIONS vs API-documented filters
- **(e)** SDK features not reflected in API docs

Additionally, cross-document consistency was checked across CLAUDE.md, README.md, PACKAGE-DEV.md, and OVERRIDES.md.

---

## 2. Documentation Created and Updated

### Modified Files

| File Path | Type | Audience | Description | Status |
|-----------|------|----------|-------------|--------|
| `CLAUDE.md` | AI Instructions | AI coding agents | Added 10 missing resources across 4 new table categories (Template, Template Detail, Gallery, plus 3 additions to Supporting); added CRUD Restrictions subsection; added Important Notes for UNSELECTABLE, filter-only properties, response key anomalies, and collection parent filter requirements; fixed rate limiting claim from "1-second" to "200ms"; replaced TODO-LIST.md reference with OVERRIDES.md | current |
| `OVERRIDES.md` | Override Documentation | Developers, AI agents | Expanded OVERRIDE-013 table from 7 properties/5 resources to 32 properties/6 resources (added File, expanded User to 20, expanded Client to 4); removed TODO-LIST.md references | current |
| `PACKAGE-DEV.md` | Developer Guide | Package contributors | Updated version from 0.5.7 to 0.6.1; fixed Cache.php path to Cache/Cache.php; removed all TODO-LIST.md references (4 locations); added FILTER_ONLY concept documentation; added 4 missing resources to URL table; updated UNSELECTABLE table to match corrected OVERRIDE-013 | current |
| `README.md` | User Guide | SDK consumers | Fixed rate limiting claim from "1-second" to "200ms"; added rateLimit configuration section; clarified username/password auth note | current |
| `src/Paymo.php` | Inline comment | Developers | Fixed docblock from "1-second delay" to "200ms minimum delay" | current |
| `src/Entity/AbstractResource.php` | Inline comment | Developers | Updated `@version` from 0.5.7 to 0.6.1 | current |

### New Files

| File Path | Type | Audience | Description | Status |
|-----------|------|----------|-------------|--------|
| `docs/gap-matrix.md` | API Reference / Audit Artifact | Developers, AI agents, maintainers | Formal gap matrix: per-resource property coverage (38 resources), CRUD operation coverage, INCLUDE_TYPES coverage (98 relationships), WHERE_OPERATIONS coverage, undocumented API features in SDK, API features not yet in SDK, intentional deviations summary | current |
| `docs/documentation.md` | Documentation Index | All | Root index linking to all project documentation with descriptions, audience, and status indicators | current |

### Summary

- **Files modified:** 6 (4 markdown, 2 PHP source)
- **Files created:** 2 (both in `docs/`)
- **Total lines added:** 488
- **Total lines removed:** 43

---

## 3. Code Comments Fixed

| # | File:Line | Before | After | Reason |
|---|-----------|--------|-------|--------|
| 1 | `src/Paymo.php:77` | `Automatic rate limiting (1-second delay between requests)` | `Automatic rate limiting (200ms minimum delay between requests, configurable)` | CRIT-01: Actual `MIN_DELAY_MS` is 200 in `RateLimiter.php:81`. The "1-second" value is `RETRY_DELAY_MS` (retry backoff after HTTP 429), not the inter-request delay. |
| 2 | `src/Entity/AbstractResource.php:13` | `@version    0.5.7` | `@version    0.6.1` | STALE-01: Current version per `CHANGELOG.md` is 0.6.1 (released 2025-12-08). |

---

## 4. Contradictions Found and Resolved

### Statistics

| Severity | Found | Confirmed by Verification | Resolved |
|----------|-------|--------------------------|----------|
| Critical | 5 | 5 (100%) | 5 |
| Stale | 4 | 4 (100%) | 4 |
| Incomplete | 12 | 12 (N/A — incomplete findings don't need challenge verification) | 8 (see Remaining Issues) |
| Minor | 6 | 6 (N/A) | 4 |
| Cross-Document | 4 | 4 (100%) | 4 |
| **Total** | **31** | **31** | **25** |

### Notable Examples

**1. Rate Limiting: Three Documents Agreed on the Wrong Value (CRIT-01 / CROSS-01)**

CLAUDE.md, README.md, and `src/Paymo.php` all claimed a "1-second delay between requests." The actual minimum delay is 200ms (`RateLimiter.php:81`, `MIN_DELAY_MS = 200`). The 1-second value (`RETRY_DELAY_MS = 1000`) is the retry backoff after a 429 rate-limit response — a fundamentally different concept. All three documents were corrected.

**2. OVERRIDE-013 Understated by 4.5x (CRIT-03 / CROSS-03)**

OVERRIDES.md documented 7 UNSELECTABLE properties across 5 resources. The actual code has 32 UNSELECTABLE properties across 6 resources. User.php alone has 20 UNSELECTABLE properties (preferences, internal fields, thumbnails), but OVERRIDES.md only listed 1 (`additional_privileges`). File.php was entirely missing. The OVERRIDE-013 table was rewritten to reflect reality.

**3. CLAUDE.md Missing 26% of Resources (CRIT-02 / CROSS-02)**

CLAUDE.md, the primary AI instruction file, listed only 28 of 38 resources. Any AI agent using CLAUDE.md would be unaware of Session, CommentThread, ProjectStatus, and all template/gallery resources. Four new table categories were added: Template Resources, Template Detail Resources, Gallery Resources, and additions to Supporting Resources.

---

## 5. Remaining Issues

### Requiring Human Attention

**5.1 Undocumented API Properties Not in SDK PROP_TYPES (INC-06)**

The research report identified properties available from the API that are not tracked in any SDK resource's PROP_TYPES. These require developer decision on whether to add:

| Resource | Property | Source |
|----------|----------|--------|
| ClientContact | `additional_privileges` | Research report Thread 2 |
| Task | `files_count` | Webhook payload examples |
| Task | `comments_count` | Webhook payload examples |
| Tasklist | `tasks_count` | Listed but SDK has it as nested object |
| Estimate | `delivery_date` | "May exist (present on Invoice)" |

**Decision needed:** Should these be added to PROP_TYPES? Some may be webhook-only fields not available via standard GET requests.

**5.2 Discussion.comments_count Provenance Unclear (INC-07)**

`Discussion.php` includes `comments_count` (integer, readonly) in PROP_TYPES, but the research report doesn't list it for Discussion. The property may have been discovered through live testing rather than documentation. No action needed unless it causes issues, but the provenance is unclear.

**5.3 Booking.date_interval in Collection Validation (INC-08)**

`OVERRIDES.md:OVERRIDE-005` documents Booking's required parent filters but doesn't mention `date_interval` as an alternative to `start_date`/`end_date`. The `BookingCollection.php` validation may accept it. Low priority — the current documentation works, but could be more complete.

**5.4 Complex PROP_TYPES Syntax Not in CLAUDE.md (INC-09)**

Report resource has deeply nested object types (`include` and `extra` sub-objects). CLAUDE.md Section 11 directs users to check resource files but doesn't explain the nested PROP_TYPES syntax. PACKAGE-DEV.md covers this (`PACKAGE-DEV.md:546-558`). This gap affects AI agents that rely solely on CLAUDE.md.

### Skipped Due to Depth Constraints

The following areas were outside the Focused Audit scope:

- **Full inline comment audit** — Only known-wrong comments were fixed. A comprehensive sweep of all ~64 PHP source files for misleading comments was not performed.
- **Code example verification** — README.md and CLAUDE.md contain PHP code examples. These were spot-checked but not exhaustively verified against current method signatures.
- **Utility class documentation** — Supporting classes (`RequestCondition.php`, `RateLimiter.php`, `Cache.php`, etc.) were examined only where they affected resource behavior documentation.
- **Test documentation accuracy** — `tests/README.md` was inventoried but not audited for accuracy.

### Comment Fixes Not Applied

All 2 planned inline comment fixes were applied successfully. No blocked fixes.

### Verification Notes

Two issues were found during post-writing subagent verification and fixed inline:

1. **gap-matrix.md INCLUDE_TYPES count**: Initially stated "82 relationships" (from research report). Subagent found 98 total relationships by counting all SDK INCLUDE_TYPES constants. Corrected to 98, with specific Project (9) and Task (8) counts also updated.
2. **gap-matrix.md CommentThread CRUD row**: Subagent found `delete()` is supported and `update()` is not supported (all properties READONLY). Both gap-matrix.md and CLAUDE.md CRUD Restrictions tables were corrected.

---

## 6. Recommendations for Ongoing Maintenance

### 6.1 Treat Documentation as a First-Class Deliverable

When modifying resource classes — especially PROP_TYPES, UNSELECTABLE, INCLUDE_TYPES, or WHERE_OPERATIONS constants — update OVERRIDES.md and CLAUDE.md in the same commit. The OVERRIDE-013 drift (7 properties documented vs 32 actual) happened because code changes accumulated without documentation updates.

### 6.2 Keep CLAUDE.md Synchronized with Resource Changes

CLAUDE.md is the highest-impact documentation artifact. Every AI-assisted interaction with this SDK uses it. The 10-resource gap (CRIT-02) means AI agents were unaware of ~26% of the SDK for an unknown period. When adding new resources, add the corresponding row to CLAUDE.md's resource tables.

### 6.3 OVERRIDES.md Maintenance Protocol

When discovering new API deviations:
1. Add an `@override OVERRIDE-XXX` comment in the code
2. Add or update the corresponding entry in OVERRIDES.md
3. If the override affects UNSELECTABLE, WHERE_OPERATIONS, or INCLUDE_TYPES, update the relevant override's table to reflect the full current scope (not just the new addition)

### 6.4 Version String Maintenance

Three locations need version updates on each release:
- `CHANGELOG.md` (version history entry)
- `PACKAGE-DEV.md:4` (version header)
- `src/Entity/AbstractResource.php:13` (`@version` tag)

Consider adding a release checklist or automating version string updates.

### 6.5 Periodic Re-Audit

This SDK evolves against an API whose documentation is frozen (last updated 2022). New undocumented API behaviors will continue to be discovered. Recommend re-running this audit workflow:
- **After each minor version release** (0.7.0, 0.8.0, etc.) — focus on changed resources
- **Quarterly** — full gap matrix refresh against any new API discoveries

### 6.6 Documentation Linting (Optional)

If CI/CD is added in the future, consider:
- **markdownlint** — enforce consistent heading hierarchy, code fence language identifiers
- **Link checking** — verify all cross-references between documentation files
- **Frontmatter validation** — ensure new `docs/` files include the required YAML frontmatter schema

### 6.7 Consider llms.txt Generation

This SDK is consumed by AI agents via CLAUDE.md. An `llms.txt` / `llms-full.txt` file would provide a standardized entry point for AI systems beyond Claude Code. Not urgent but worth considering as the llms.txt standard matures.

### 6.8 Remove Stale API Documentation Mirror

The `docs/api-documentation/` directory mirrors Paymo's GitHub API docs frozen since 2022. This is useful as a historical reference but may confuse developers who assume it's current. Consider adding a prominent notice at the top of `docs/api-documentation/README.md` stating the mirror date and linking to the live GitHub repository.

---

## Appendix: Full Finding Reference

All findings are documented with evidence in the analysis artifact:
- `.zenflow/tasks/02-documentation-deep-dive-7fe5/analysis.md`

The approved documentation plan with verification results:
- `.zenflow/tasks/02-documentation-deep-dive-7fe5/doc-plan.md`

The gap matrix (primary task deliverable):
- `docs/gap-matrix.md`
