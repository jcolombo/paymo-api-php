# Research Scoping: Paymo REST API Complete Inventory

## Core Research Question

What is the complete, current surface area of the Paymo REST API — every resource, endpoint, CRUD operation, property (with types and constraints), include relationship, and filter capability — including undocumented behavior that the official documentation (last substantially updated ~2022) does not reflect?

## Sub-Questions

1. **Resources & Endpoints**: What is the complete list of API resources, their endpoint paths, and their HTTP verb support (GET single, GET list, POST, PUT, DELETE)?

2. **Properties & Types**: For each resource, what properties does the API expose? What are their data types, which are read-only, which are required for creation, and which are create-only?

3. **Include Relationships**: For each resource, what related entities can be sideloaded via the `include` or `partial_include` parameters? What nesting is supported?

4. **Filtering & WHERE Operations**: What filter operators (`=`, `>`, `<`, `like`, `in`, `not in`, `range`, etc.) are available for list queries? Which properties on each resource support which operators?

5. **Undocumented & Changed Behavior**: What API features exist that are not reflected in the official GitHub documentation? This includes: pagination support, new/removed properties since 2022, changed types, deprecated endpoints, and behavioral quirks discovered through actual API usage.

6. **API Infrastructure**: What are the conventions for authentication, rate limiting, content types, response codes, error handling, date/time formats, and webhooks?

7. **Resource Relationships & Data Model**: How do resources relate to each other (foreign keys, parent-child hierarchies, many-to-many via join resources)? What is the overall data model shape?

## Existing Knowledge

The following is already known or established:

- **SDK codebase exists** with 38 resource classes that implement the API. These were built through a combination of official docs and manual testing against live API responses.
- **OVERRIDES.md** documents known intentional deviations between the SDK and the official API docs — these represent tested, verified behavior from actual API responses.
- **Local API documentation copy** exists at `docs/api-documentation/` — 37 section files covering all documented resources plus `currencies.md` (not referenced in the live repo's README).
- **Pagination** is an undocumented feature discovered through Paymo support communication (December 2024) — supports `page` and `page_size` query parameters.
- **The official GitHub repo** has been renamed from `paymoapp/api` to `paymo-org/api` (GitHub redirects the old URL). Project documentation references the old URL.
- **The API base URL** is `https://app.paymoapp.com/api/` (SSL/TLS 1.2 only).
- **Rate limiting** uses `X-Ratelimit-*` headers with 429 status codes.
- **SDK config** (`default.paymoapi.config.json`) contains a `classMap` registry mapping 50+ entity type keys to PHP classes, which may reveal resources not prominently documented.

## Knowledge Gaps

What specifically needs discovery:

1. **Completeness of the documented property lists** — Are the properties listed in the 2022 docs still accurate? Have new properties been added? Have any been deprecated or removed?
2. **Undocumented endpoints or resources** — Are there API resources that exist but aren't documented in the official GitHub repo?
3. **Filter support per resource** — The docs describe general filter syntax but don't exhaustively list which properties on each resource support which operators.
4. **Include relationship completeness** — The `includes.md` doc provides general syntax, but per-resource include support may not be exhaustive in the docs.
5. **Community-discovered behaviors** — Forum posts, blog articles, or integration guides that reveal API behaviors not in the official docs.
6. **Currencies resource** — Local docs include `currencies.md` but it's not in the live repo's README endpoint listing. Is this an official but unlisted endpoint, or locally authored?
7. **Rate limit specifics** — Exact limits (requests per period) are not specified in the docs.
8. **Webhook event types** — The complete list of events that trigger webhooks.

## Scope Boundaries

### In Scope
- All REST API resources and their full CRUD surface area
- All properties, types, and constraints for every resource
- All include relationships
- All filtering/WHERE capabilities
- Authentication methods and mechanics
- Rate limiting behavior
- Content type support (JSON, XML, PDF, XLS for reports)
- Webhook configuration and events
- Response code conventions and error handling
- Date/time format conventions
- Pagination (documented and undocumented)
- Any behavioral differences between what the docs say and what the API actually does (as noted in OVERRIDES.md and community sources)
- The GitHub organization rename from `paymoapp` to `paymo-org`

### Out of Scope
- SDK implementation details (that's the subsequent comparison step)
- Performance benchmarking or load testing
- Paymo's UI/frontend features not exposed via API
- Pricing, billing, or account management (unless there are API endpoints for these)
- Historical API versions or migration paths
- Third-party integration details beyond what the API directly supports
- Building or testing actual API calls

## Intended Use

This research will produce a **single structured reference document** cataloging everything the Paymo API offers. It will serve as the **authoritative baseline** for a subsequent step that compares the SDK implementation against the API surface area to identify gaps, mismatches, and coverage issues.

The audience is the SDK developer (you, the user) who needs a comprehensive, accurate map of the API to validate and extend the PHP SDK.

## Depth Level

**Full Research** — justified by:
- 37+ documentation files to process systematically
- Need for per-resource, per-property granularity
- Cross-referencing required between official docs, community sources, and known overrides
- The output must be exhaustive enough to serve as a comparison baseline
- Undocumented behavior discovery requires broader source exploration

## Systematic Review Parameters

### Search Strategy
- **Primary source**: Local API documentation (`docs/api-documentation/sections/*.md`) — process every file
- **Secondary source**: Live GitHub repo at `github.com/paymo-org/api` — check for any differences from local copy
- **Tertiary sources**: Web search for Paymo API community discussions, integration guides, forum posts, changelog entries, and blog posts revealing undocumented behavior
- **SDK-adjacent**: `OVERRIDES.md` and `default.paymoapi.config.json` classMap — these hint at API features the docs may not cover

### Extraction Template (per resource)
For each API resource, extract:

| Field | Description |
|-------|-------------|
| Resource name | Official name |
| Endpoint path | URL path (e.g., `/projects`) |
| Supported operations | GET (single), GET (list), POST, PUT, DELETE |
| Properties | Name, type, read-only, required-for-create, create-only |
| Include relationships | What can be sideloaded |
| Filter/WHERE support | Which properties support which operators |
| Notes | Quirks, undocumented behavior, discrepancies |

### Inclusion Criteria
- Any source that describes Paymo API behavior (endpoints, properties, operations)
- Any source published after 2020 (to capture the relevant API era)

### Exclusion Criteria
- Sources about Paymo's UI/UX that don't reference the API
- Sources about other project management APIs
- Marketing content without technical substance

## Source Inventory

### Local Project Files

| Source | Location | Relevance |
|--------|----------|-----------|
| API Documentation (local copy) | `docs/api-documentation/` (37 section files, ~8,450 lines) | **Primary** — complete local mirror of official Paymo API reference |
| API Documentation README | `docs/api-documentation/README.md` (143 lines) | **Primary** — index of all API sections, infrastructure overview |
| OVERRIDES.md | `OVERRIDES.md` (803 lines) | **High** — documents known deviations between docs and actual API behavior |
| SDK Config | `default.paymoapi.config.json` (453 lines) | **Medium** — classMap reveals all entity type keys the SDK recognizes |
| PACKAGE-DEV.md | `PACKAGE-DEV.md` (1,657 lines) | **Medium** — SDK development guide with architectural context |
| CHANGELOG.md | `CHANGELOG.md` (414 lines) | **Medium** — version history may reference API discoveries |
| Resource classes | `src/Entity/Resource/*.php` (38 files) | **Reference** — not primary for this step, but useful to cross-check resource coverage |

### External Sources (to be explored in Broad Sweep)

| Source Type | Where to Look |
|-------------|---------------|
| Official GitHub repo | `github.com/paymo-org/api` — check for updates since local copy was taken |
| GitHub Issues/PRs | `github.com/paymo-org/api/issues` and `/pulls` — community questions about undocumented behavior |
| Paymo community/forums | Web search for Paymo API discussions |
| Integration guides | Blog posts, tutorials about building Paymo integrations |
| Paymo changelog | Official product changelog that may reference API changes |
