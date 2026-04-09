# Competitive Intelligence — Scoping Document

## Decision Context

**Decision:** Identify proven patterns from peer PHP SDK packages that should be adopted into `paymo-api-php` to improve its architecture, feature completeness, developer experience, testing, and configuration.

**Useful output:** A comparison matrix across all three packages and a prioritized list of adoption candidates — specific features or patterns present in niftyquoter-api-php or leadfeeder-api-php that paymo-api-php is missing (and vice versa). This informs a concrete improvement roadmap.

**Context:** All three packages are built by the same developer/team, wrap third-party REST APIs in PHP using similar patterns, and share the same namespace root (`Jcolombo\*`). The peer packages were built after paymo-api-php and contain iterative improvements marked with explicit "FIX (NQ bug X.Y)" comments in leadfeeder-api-php referencing niftyquoter-api-php issues.

## Market Definition

**Category:** Internal PHP SDK packages wrapping third-party REST APIs
**Scope:** Same team/developer, shared architectural lineage
**Pattern:** AbstractEntity → AbstractResource / AbstractCollection inheritance, Guzzle HTTP, singleton connection pools, file-based caching, custom test frameworks

| Package | API Wrapped | Resources | CRUD Scope | API Format |
|---------|-------------|-----------|------------|------------|
| paymo-api-php | Paymo (project management) | 38 | Full CRUD | Custom JSON |
| niftyquoter-api-php | NiftyQuoter (proposals/quoting) | 10 | Full CRUD | Custom JSON |
| leadfeeder-api-php | Leadfeeder (lead tracking) | 6 | Read-only + Export | JSON:API |

## Known Competitors

### 1. niftyquoter-api-php
- **Location:** `/Users/joel/srv/fission/packages/niftyquoter-api-php`
- **Category:** Direct peer — full CRUD SDK with closest architectural parallel to paymo-api-php
- **Key characteristics:**
  - 10 resources (Proposal, Client, Contact, Comment, Note, Item, PricingTable, ServiceTemplate, EmailTemplate, TextBlock)
  - Three-tier entity hierarchy: AbstractEntity → AbstractResource → Concrete
  - Dual sliding-window rate limiter with 429 retry + exponential backoff
  - Structured error handling with severity levels (NOTICE/WARN/FATAL) and configurable handlers
  - Type coercion via `Converter` class
  - `EntityMap` for config-driven class substitution
  - Custom test framework with ResourceTestRunner, dry-run mode, fixtures
  - `ScrubCache` for mutation-triggered cache invalidation
  - `WRITEONLY` constant for action-trigger properties
  - Specialized collections with `validateFetch()` enforcing parent context constraints
  - 5 runnable examples and 12 AI knowledge base documents

### 2. leadfeeder-api-php
- **Location:** `/Users/joel/srv/fission/packages/leadfeeder-api-php`
- **Category:** Direct peer — read-only SDK with additional subsystems (export, IP enrichment)
- **Key characteristics:**
  - 6 resources (Account, Lead, Visit, CustomFeed, Location, WebsiteTrackingScript)
  - JSON:API format handling via `JsonApiParser` (envelope flattening, include resolution)
  - Multi-scope rate limiting (4 scopes: export, account, token, ipenrich)
  - `ExportManager` for async export lifecycle (create → poll → download)
  - `IpEnrichClient` for separate API endpoint
  - `fromCacheKey` on cache responses for hit detection (explicit fix for NQ bug)
  - `ScrubCache` with full wipe approach (fixed NQ dead code)
  - `INCLUDE_ONLY` flag for resources that only exist as includes
  - Singleton fetch for single-instance resources
  - `dateRange()` fluent method on collections
  - Account scoping on connection (auto-prefixes URLs)
  - Explicit `validateConstants()` in devMode

## Evaluation Dimensions

### 1. Architecture Patterns
**Definition:** How the SDK structures its class hierarchy, manages entity relationships, handles HTTP communication, and organizes code.

**What "good" looks like:** Clean separation of concerns, minimal duplication between resource classes, extensible base classes, clear responsibility boundaries, support for API-specific edge cases without polluting the base architecture.

**Sub-dimensions:**
- Base class hierarchy and responsibility split
- Entity property management (magic methods, type safety)
- HTTP client management and request pipeline
- Response parsing and hydration
- Entity relationship resolution (includes)
- Class registry and extensibility (EntityMap)

### 2. Feature Completeness
**Definition:** Cross-cutting infrastructure features that support reliable API communication.

**What "good" looks like:** Each feature is opt-in, configurable, and doesn't interfere with others. Failure modes are handled gracefully. Features work correctly across all resource types.

**Sub-dimensions:**
- Caching (strategy, invalidation, custom backends, cache-hit detection)
- Logging (conditional logging, structured output, configurability)
- Pagination (auto-pagination, safety caps, page tracking)
- Rate limiting (sliding windows, per-scope, header-aware)
- Error handling (severity levels, configurable handlers, structured errors)
- Retry logic (exponential backoff, max retries, 429 handling)
- Type coercion (PHP ↔ API wire format conversion)

### 3. Developer Experience
**Definition:** How pleasant and productive it is to use the SDK for common operations.

**What "good" looks like:** Operations that developers perform frequently are concise and fluent. The SDK guides correct usage and warns about misuse. Common patterns are documented with examples.

**Sub-dimensions:**
- Fluent API and method chaining
- Query builder (where/has conditions, field selection)
- Include system (eager loading related entities)
- Dirty tracking (change detection, minimal update payloads)
- Static factory methods (::new(), ::list())
- Collection interfaces (Iterator, Countable, JsonSerializable)
- Parent context / scoped resources
- Singleton resource fetching
- Data export (flatten, JSON serialization)

### 4. Testing
**Definition:** How the SDK tests itself and how it enables users to validate their integrations.

**What "good" looks like:** Tests can run against a live API or offline. Test infrastructure is purpose-built for API SDK testing. Results are clear and actionable. Tests cover CRUD operations, filtering, pagination, and edge cases.

**Sub-dimensions:**
- Test framework choice (custom vs. PHPUnit)
- Test modes (dry-run, read-only, verbose, per-resource)
- Fixture support for offline testing
- Test runner with aggregated results
- Test logging and output formatting
- Resource cleanup after test runs
- Assertion helpers

### 5. Configuration
**Definition:** How the SDK manages defaults, user overrides, and runtime settings.

**What "good" looks like:** Sensible defaults ship with the package. Users can override at multiple levels (file, runtime, per-request). Configuration is discoverable and documented.

**Sub-dimensions:**
- Default config file (shipped with package)
- User config file (project-level overrides)
- Runtime overrides (programmatic)
- Dot-notation access
- Class map / entity registry (overridable class bindings)
- Dev mode (validation, warnings)
- Per-request option overrides

## Depth Level

**Focused Analysis** — The competitors are known (2 specific peer packages with source access), the evaluation dimensions are explicitly defined (5 categories from the task description), and the goal is concrete (adoption candidates for paymo-api-php). No market research or competitor discovery needed. Full source code is available for all three packages.

**Justification:** This is an internal codebase comparison, not a market analysis. All packages are accessible locally. The task specifies exact dimensions. Interactive scoping beyond confirming this plan would not add value.

## Open Questions

1. **~~Evolution timeline~~** — **RESOLVED.** Build order confirmed via git history:
   - `pipeline-deals-api`: January 2014 (generation 0 — pre-Composer, PHP 5.3, Zend naming)
   - `paymo-api-php`: March 2020 (generation 1 — Composer, PSR-4, PHP 7.4+, Guzzle)
   - `niftyquoter-api-php`: April 4, 2026 (generation 2 — PHP 8.1+, enhanced patterns)
   - `leadfeeder-api-php`: April 7, 2026 (generation 3 — PHP 8.1+, NQ bugfixes, JSON:API)
   - **Implication:** "Missing" features in paymo-api-php are not-yet-backported improvements from newer packages, not regressions.
2. **API format constraints:** Some features in leadfeeder-api-php (JsonApiParser, ExportManager, IpEnrichClient) are API-specific. The analysis should distinguish API-specific features from transferable patterns.
3. **Resource scale difference:** paymo-api-php has 38 resources vs. 10 and 6. Some architectural choices may be driven by scale. The analysis should note where a pattern works at small scale but may not transfer to 38 resources.

---

## Competitor Inventory

### Identification Methodology

**Source:** Full directory scan of `/Users/joel/srv/fission/packages/` (7 packages total), plus composer.json / package.json analysis and git history verification for all packages by the same author (`Joel Colombo / jcolombo`).

**Lineage confirmed:** All four PHP packages share the same author and evolve the same REST API SDK pattern across generations. Build dates span 2014 to April 2026.

### Primary Competitors (Full Analysis)

These are the direct peer packages specified in the task scope. Both receive full multi-dimensional analysis.

| # | Package | Category | Basis for Inclusion | Analysis Priority |
|---|---------|----------|--------------------|--------------------|
| 1 | **niftyquoter-api-php** | Direct peer | Same author, same architectural lineage (AbstractEntity → AbstractResource → Concrete), same dependencies (Guzzle + php-dot-notation), full CRUD scope, PHP 8.1+. Built immediately after paymo-api-php; represents the first iterative improvement of the pattern. | **Primary** — closest architectural parallel, most transferable patterns |
| 2 | **leadfeeder-api-php** | Direct peer | Same author, same architectural lineage, same dependencies. Built days after niftyquoter with explicit bugfix references ("FIX (NQ bug X.Y)"). Represents the most evolved version of the pattern. Different API format (JSON:API) adds complexity dimension. | **Primary** — latest-generation patterns, most mature implementations |

### Discovered Packages (Noted, Not Analyzed)

Scanning the packages directory revealed two additional packages by the same author. Neither warrants full analysis for this decision, but they provide useful evolutionary context.

| # | Package | Category | Basis for Inclusion | Reason for Exclusion from Full Analysis |
|---|---------|----------|--------------------|-----------------------------------------|
| 3 | **pipeline-deals-api** | Predecessor (adjacent) | Same author's earliest PHP API wrapper (January 2014). Pre-Composer, PHP 5.3, Zend naming conventions, manual `require_once` loading. Wraps PipelineCRM API. Has Connection/Entity/Collection pattern that prefigures the modern packages. | Generation 0 architecture — no Composer, no PSR-4, no Guzzle, no type hints. Patterns are too dated for forward adoption. Historical interest only: proves the Connection → Entity → Collection pattern predates paymo-api-php by 6 years. |
| 4 | **node-paymo-api** | Indirect (cross-language) | Same author, wraps the same Paymo API, but in TypeScript/Node.js (v0.0.4, ~2022). Uses Axios instead of Guzzle. | Different language ecosystem. Patterns cannot be directly ported to PHP. Could inform API coverage decisions (which Paymo endpoints are exposed) but not architecture or DX patterns. |

### Excluded Packages

| Package | Reason for Exclusion |
|---------|---------------------|
| `react-hook-google-one-tap` | React hook package — completely different domain (Google authentication), no PHP, no API SDK pattern. |
| `_tokyo-demo` | Demo/prototype project (January 2023). Not an API SDK package. |

### Landscape Gaps

- **No emerging competitors identified.** This is expected: these are internal packages for specific third-party API integrations, not market-facing products. There is no competitive market to monitor — the "competition" is the developer's own prior work.
- **No external open-source alternatives evaluated.** The task scope is specifically about cross-pollination between the developer's own packages, not comparison against community-maintained alternatives (e.g., generic PHP API SDK frameworks). If external comparison is desired, that would be a separate analysis.
- **No packages from other team members.** All packages in the fission/packages directory are by the same author. There are no competing internal implementations by other developers to evaluate.

### Evolutionary Context Summary

```
pipeline-deals-api (2014) ─── Generation 0
  │  PHP 5.3, Zend naming, manual requires, no Composer
  │  Pattern: Connection → EntityAbstract → plural/singular classes
  │
paymo-api-php (2020) ─── Generation 1 ◄── SUBJECT OF THIS ANALYSIS
  │  PHP 7.4+, PSR-4, Composer, Guzzle 7, hassankhan/config
  │  Pattern: Paymo → AbstractResource/AbstractCollection → Concrete
  │  38 resources, full CRUD, caching, logging, pagination
  │
niftyquoter-api-php (Apr 4, 2026) ─── Generation 2
  │  PHP 8.1+, Guzzle 7, php-dot-notation (replaces hassankhan/config)
  │  Added: sliding-window rate limiter, 429 retry, structured errors,
  │  ScrubCache, EntityMap, Converter, test framework, WRITEONLY
  │
leadfeeder-api-php (Apr 7, 2026) ─── Generation 3
     PHP 8.1+, Guzzle 7, php-dot-notation
     Added: JSON:API parser, multi-scope rate limiting, ExportManager,
     IpEnrichClient, fromCacheKey, INCLUDE_ONLY, dateRange(),
     validateConstants(), singleton fetch, account scoping
     Fixed: NQ ScrubCache dead code, NQ cache-hit detection
```

### Dependency Comparison

| Dependency | paymo-api-php | niftyquoter-api-php | leadfeeder-api-php |
|-----------|:---:|:---:|:---:|
| PHP version | >=7.4 | >=8.1 | >=8.1 |
| guzzlehttp/guzzle | ^7.8 | ^7.8 | ^7.8 |
| adbario/php-dot-notation | ^3.3 | ^3.3 | ^3.3 |
| hassankhan/config | ^3.2 | — | — |
| Composer test scripts | — | test, test:dry-run, test:verbose | test, test:dry-run, test:verbose |
| autoload-dev (tests) | mirrors src | separate Tests namespace | separate Tests namespace |

**Notable:** paymo-api-php still depends on `hassankhan/config` which the newer packages dropped in favor of direct `php-dot-notation` usage. paymo-api-php also lacks Composer test scripts and has its autoload-dev misconfigured (mirrors src namespace instead of mapping a Tests namespace).
