# Discovery & Calibration

## Project Overview

**Package:** `jcolombo/paymo-api-php` — PHP SDK for the Paymo project management REST API
**Namespace:** `Jcolombo\PaymoApiPhp`
**Language:** PHP 7.4+
**License:** MIT
**Dependencies:** Guzzle HTTP 7.8+, hassankhan/config 3.2+, adbario/php-dot-notation 3.3+

### Architecture

```
src/
├── Paymo.php                          # Main connection singleton
├── Request.php                        # HTTP request execution
├── Configuration.php                  # Config file loading
├── Cache/                             # File-based response caching
│   ├── Cache.php
│   └── ScrubCache.php
├── Entity/
│   ├── AbstractEntity.php             # Base entity class
│   ├── AbstractResource.php           # Base resource (single item)
│   ├── AbstractCollection.php         # Base collection (list of items)
│   ├── EntityMap.php                  # Resource type → class mapping
│   ├── Resource/                      # 38 resource classes
│   │   ├── Project.php, Task.php, Client.php, ...
│   └── Collection/                    # 8 specialized collection classes
│       ├── BookingCollection.php, TimeEntryCollection.php, ...
└── Utility/
    ├── RequestCondition.php           # WHERE/HAS condition builders
    ├── RequestAbstraction.php         # Request parameter abstraction
    ├── RequestResponse.php            # Response parsing
    ├── RateLimiter.php                # API rate limiting
    ├── MetaData.php                   # Response metadata
    ├── Converter.php                  # Type conversion
    ├── Color.php                      # Color validation
    ├── Error.php                      # Error handling
    └── Log.php                        # Request/response logging
```

### Size Assessment

| Category | Count |
|----------|-------|
| Source PHP files | 64 |
| Resource classes | 38 |
| Collection classes | 8 |
| Test PHP files | 37 |
| API documentation files | 38 sections + README |
| Markdown documentation files | 5 (root-level) |
| Total lines of documentation (estimated) | ~4,000+ across MD files |

---

## Documentation Inventory

### Root-Level Documentation

| File | Size | Description |
|------|------|-------------|
| `README.md` | 15K | User-facing SDK documentation: installation, quick start, features, usage examples |
| `CLAUDE.md` | 27K | AI assistant guide: connection management, CRUD patterns, resource reference, recipes, important notes |
| `OVERRIDES.md` | 26K | 13 active override entries documenting deviations between API docs and actual API behavior |
| `PACKAGE-DEV.md` | 48K | Internal development guide: architecture, resource class structure, constants reference, type system, entity map, collections, testing, file templates |
| `CHANGELOG.md` | 18K | Version history from v0.1.0 through v0.6.1 |

### API Documentation (Local Copy)

| Path | Description |
|------|-------------|
| `docs/api-documentation/README.md` | Mirror of official Paymo API docs main README |
| `docs/api-documentation/sections/` | 38 section files covering authentication, resources, filtering, includes, content types, datetime, sample code |

Resource-specific API doc sections (30):
`authentication.md`, `bookings.md`, `client_contacts.md`, `clients.md`, `comments.md`, `company.md`, `currencies.md`, `discussions.md`, `entries.md`, `estimate_templates.md`, `estimates.md`, `expenses.md`, `files.md`, `invoices.md`, `invoice_payments.md`, `invoice_recurring_profiles.md`, `invoice_templates.md`, `milestones.md`, `project_statuses.md`, `project_templates.md`, `projects.md`, `reports.md`, `sessions.md`, `subtasks.md`, `task_recurring_profiles.md`, `tasklists.md`, `tasks.md`, `users.md`, `users_tasks.md`, `webhooks.md`, `workflow_statuses.md`, `workflows.md`

General API doc sections (8):
`content_types.md`, `currencies.md`, `datetime.md`, `filtering.md`, `includes.md`, `sample_code.md`

### Test Documentation

| File | Description |
|------|-------------|
| `tests/README.md` | Comprehensive test suite guide: resource-centric testing, config, CLI options, safety guarantees |

### Configuration Reference

| File | Description |
|------|-------------|
| `default.paymoapi.config.json` | Default config with connection, cache, logging, rate limiting, dev mode, and testing sections |
| `composer.json` | Package manifest with dependencies |

### Research Artifacts (Prior Task)

| File | Description |
|------|-------------|
| `.zenflow/tasks/01-deep-research-6acc/research-report.md` | Complete API surface area inventory: 31 documented + 4 undocumented endpoints, 60+ undocumented properties, 82 include relationships, 13 verified overrides |
| `.zenflow/tasks/01-deep-research-6acc/broad-sweep.md` | Initial broad research sweep |
| `.zenflow/tasks/01-deep-research-6acc/deep-dives.md` | Deep dive research threads |
| `.zenflow/tasks/01-deep-research-6acc/research-scoping.md` | Research scope definition |
| `.zenflow/tasks/01-deep-research-6acc/research-threads.md` | Research thread tracking |

### AI Instruction Files

| File | Type |
|------|------|
| `CLAUDE.md` | AI assistant guide (project-level, checked into repo) |

### Missing Documentation Artifacts

- No `llms.txt` / `llms-full.txt`
- No `CONTRIBUTING.md`
- No `TODO-LIST.md` (previously existed, now removed)
- No `.cursorrules`, `.github/copilot-instructions.md`, or other AI instruction files beyond CLAUDE.md
- No OpenAPI/Swagger spec (API does not provide one — confirmed in GitHub Issue #62)
- No dedicated `docs/` subfolder structure beyond the API documentation mirror

---

## Documentation State Assessment

### Strengths
- **OVERRIDES.md is excellent** — 13 well-documented overrides with API evidence, SDK implementation details, and code references. This is the single most valuable documentation artifact for SDK accuracy.
- **PACKAGE-DEV.md is thorough** — comprehensive internal development guide covering architecture, constants, type system, entity map, and file templates.
- **CLAUDE.md is detailed** — extensive AI assistant guide with connection management, CRUD patterns, and recipes.
- **Research report is comprehensive** — the prior Deep Research task produced a thorough API surface area inventory that serves as an excellent reference for this audit.
- **Test documentation is solid** — tests/README.md covers the resource-centric testing approach, CLI options, and safety guarantees.

### Concerns
- **API docs are frozen since 2022** — the local copy mirrors GitHub docs that haven't been updated. The SDK has evolved significantly beyond what these docs describe.
- **PACKAGE-DEV.md version is stale** — header says "Version: 0.5.7" but the SDK is at v0.6.1. Content may have drifted.
- **CLAUDE.md accuracy is unverified** — this is the primary AI instruction file and has outsized impact on AI agent behavior. Any inaccuracies here propagate to every AI-assisted interaction.
- **README.md accuracy is unverified** — user-facing documentation that could mislead developers if not in sync with current SDK behavior.
- **Cross-document consistency is unknown** — README.md, CLAUDE.md, PACKAGE-DEV.md, and OVERRIDES.md all describe SDK behavior. They may contradict each other.
- **Inline code comments** — resource classes have `@override` comments but general inline documentation quality is unknown.

---

## Audit Configuration

### Depth Level: Focused Audit

**Justification:** The task description specifies exactly what to compare (PROP_TYPES, CRUD operations, INCLUDE_TYPES, WHERE_OPERATIONS, SDK-only features) and what to produce (a gap matrix). This is a targeted comparison of 38 resource classes against the API reference, not a full documentation rewrite. The research report from the prior task provides the API reference baseline, making this a well-scoped comparison task.

### Focus Areas

The audit will compare each of the 38 resource classes in `src/Entity/Resource/` against:
1. The research report (`research-report.md`) as the primary API reference
2. The local API documentation (`docs/api-documentation/sections/`)
3. `OVERRIDES.md` for documented intentional deviations

For each resource, the audit will check:
- **(a) PROP_TYPES vs API-documented properties** — missing properties, extra properties, type mismatches
- **(b) CRUD operations vs API endpoints** — supported operations, endpoint paths, response key handling
- **(c) INCLUDE_TYPES vs API-documented relationships** — missing includes, extra includes
- **(d) WHERE_OPERATIONS vs API-documented filters** — missing filters, extra filters, operator restrictions
- **(e) SDK features not reflected in API docs** — undocumented features like pagination, unselectable properties, filter-only properties

Additionally, the audit will check:
- **Cross-document consistency** between CLAUDE.md, README.md, PACKAGE-DEV.md, and OVERRIDES.md for the audited resource areas
- **AI instruction accuracy** in CLAUDE.md since this has outsized impact on AI agent behavior

### Documentation Output Location

`docs/` — respecting the existing `docs/` directory. The gap matrix and audit findings go to the artifacts path (`.zenflow/tasks/02-documentation-deep-dive-7fe5/`).

### Verification Approach

**Same-agent re-review** — after the analysis pass, Critical findings will be re-examined with a challenge pass attempting to prove the documentation IS correct before confirming a contradiction.

### Optional Outputs

- `llms.txt` / `llms-full.txt`: **Not requested** — can be recommended in the final report if appropriate.

---

## Priority Areas

### Task-Specified Priorities
1. **Per-resource PROP_TYPES audit** — the core of the gap matrix
2. **CRUD operation coverage** — what the SDK supports vs what the API offers
3. **INCLUDE_TYPES completeness** — relationship loading coverage
4. **WHERE_OPERATIONS completeness** — filter capability coverage
5. **Undocumented API features** — pagination (OVERRIDE-003) and similar cases

### Agent-Identified Priorities
1. **CLAUDE.md accuracy** — this file drives all AI-assisted SDK usage. Inaccuracies here have the highest blast radius.
2. **PACKAGE-DEV.md staleness** — version header says 0.5.7, SDK is at 0.6.1. Resource lists, constants, and patterns may have drifted.
3. **Company resource** — the research report identified 30+ undocumented Company properties. This resource likely has the largest gap between SDK PROP_TYPES and actual API behavior.
4. **Template resources** — ProjectTemplate, InvoiceTemplate, EstimateTemplate and their gallery variants have response key anomalies (OVERRIDE-009, OVERRIDE-010) that require careful verification.
5. **Session resource** — unique string ID type (OVERRIDE-004) and limited CRUD operations.
6. **Cross-document consistency** — README.md, CLAUDE.md, and PACKAGE-DEV.md all describe resource capabilities. Contradictions between these documents would confuse both humans and AI agents.

---

## Assumptions

1. **The research report is the API reference** — as specified in the task description, `.zenflow/tasks/01-deep-research-6acc/research-report.md` serves as the ground truth for "what the API does" since no live API testing is available.
2. **OVERRIDES.md deviations are intentional** — as instructed, documented overrides are flagged but not treated as bugs in the gap matrix.
3. **The audit scope is the 38 resource classes** — supporting classes (AbstractResource, AbstractCollection, utilities) are only examined insofar as they affect resource behavior.
4. **Gap matrix is the primary deliverable** — the analysis step should produce a structured comparison showing API features missing from SDK, SDK features missing from API docs, and discrepancies.
5. **No live API testing** — all findings are based on code reading, documentation comparison, and the research report's verified findings.
6. **Documentation output goes to artifacts path** — the gap matrix and analysis are working artifacts, not user-facing documentation. Any documentation improvements would be planned in Step 3.
