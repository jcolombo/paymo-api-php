# Codebase & Technical Analysis

## Tech Stack Summary

| Component | Technology | Version |
|-----------|-----------|---------|
| Language | PHP | >=7.4 (target: 8.1 per FR-001) |
| HTTP Client | Guzzle | ^7.8 |
| Configuration | hassankhan/config (Noodlehaus) | ^3.2 |
| Dot-notation access | adbario/php-dot-notation (Adbar\Dot) | ^3.3 |
| Test Framework | Custom (`ResourceTest` base class, `ResourceTestRunner` CLI) | N/A |
| Build/Package | Composer | 2.x |
| Code Style | PSR-4 autoloading, mixed indentation (2-space in some, 4-space in others) | N/A |

**Codebase size:** 64 PHP source files (~25,400 lines), 37 test files, 38 entity resource classes, 8 specialized collection classes.

**No CI pipeline present.** No PHPUnit, PHPStan, or Psalm configuration. No `.github/workflows` or similar.

---

## Architecture Overview

### Pattern: ORM-Style Entity SDK

The package implements an Active Record-inspired pattern where entity classes (resources) encapsulate both data and API operations. The architecture has four primary layers:

```
┌─────────────────────────────────────────────────────────┐
│  Consumer Code                                          │
│  $project = Project::new()->fetch(12345, ['tasks']);    │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│  Entity Layer                                           │
│  AbstractEntity → AbstractResource (single)             │
│                 → AbstractCollection (list)              │
│  38 Resource subclasses, 8 Collection subclasses        │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│  Request Layer                                          │
│  Request (static) → RequestAbstraction (container)      │
│                   → RequestCondition (WHERE/HAS)        │
│                   → RequestResponse (result)             │
└────────────────────┬────────────────────────────────────┘
                     │
┌────────────────────▼────────────────────────────────────┐
│  Connection Layer                                       │
│  Paymo (singleton per API key)                          │
│  → Guzzle HTTP → Rate Limiter → Cache                  │
└─────────────────────────────────────────────────────────┘
```

### Directory Structure

```
src/
├── Paymo.php                      # Main connection class (703 lines)
├── Configuration.php              # Singleton config manager (423 lines)
├── Request.php                    # Static request builder (804 lines)
├── Cache/
│   ├── Cache.php                  # File-based response cache (594 lines)
│   └── ScrubCache.php             # In-memory include validation cache (115 lines)
├── Entity/
│   ├── AbstractEntity.php         # Base entity (~200 lines)
│   ├── AbstractResource.php       # Single resource (~650 lines)
│   ├── AbstractCollection.php     # List/collection (~500 lines)
│   ├── EntityMap.php              # Entity registry (820 lines)
│   ├── Resource/                  # 38 resource classes
│   │   ├── Project.php
│   │   ├── Task.php
│   │   ├── Invoice.php
│   │   └── ... (35 more)
│   └── Collection/                # 8 specialized collections
│       ├── EntityCollection.php   # Default collection
│       ├── FileCollection.php     # Requires parent filters
│       ├── BookingCollection.php  # Requires date/parent filters
│       └── ... (5 more)
└── Utility/
    ├── Converter.php              # Type conversion (241 lines)
    ├── Error.php                  # Error handling singleton (517 lines)
    ├── RateLimiter.php            # Rate limit tracker (386 lines)
    ├── RequestAbstraction.php     # Request container (286 lines)
    ├── RequestCondition.php       # WHERE/HAS builder (579 lines)
    ├── RequestResponse.php        # Response container (288 lines)
    ├── Log.php                    # Logging utility
    ├── Color.php                  # Terminal colors
    └── MetaData.php               # API metadata

tests/
├── ResourceTest.php               # Base test class
├── ResourceTestRunner.php         # CLI test runner
├── TestConfig.php                 # Test configuration
├── TestLogger.php                 # Test logger
├── TestOutput.php                 # Test output formatter
├── TestResult.php                 # Test result container
├── TestOwnershipRegistry.php      # Tracks test-created entities
├── DependencyAnalyzer.php         # Resolves test ordering
├── KnownIssuesRegistry.php        # Known API quirks/failures
├── bootstrap.php                  # Test bootstrap
├── Fixtures/                      # Test fixtures
├── ResourceTests/                 # Per-resource test files
└── validate/                      # Validation scripts

default.paymoapi.config.json       # 453-line JSON config
OVERRIDES.md                       # 13 documented API deviations
CLAUDE.md                          # SDK usage guide for AI
PACKAGE-DEV.md                     # Internal development guide
```

### Key Architectural Decisions

1. **Singleton connections per API key** — `Paymo::$connections` array keyed by API key string. `Paymo::connect()` returns existing or creates new. First connection becomes default.

2. **Static factory methods on entities** — `Project::new()`, `Project::list()`, `Task::where()`, `Task::has()`. Consumer code never directly instantiates `Request` or `RequestAbstraction`.

3. **Static Request class** — `Request::fetch()`, `Request::list()`, `Request::create()`, etc. are all static methods that take a `Paymo` connection instance. No request object reuse.

4. **EntityMap as central registry** — JSON config maps entity keys (e.g., `"projects"`) to PHP class names. `EntityMap` validates and resolves these at runtime. 820 lines including validation logic.

5. **Two distinct cache systems** — `Cache` handles HTTP response caching (file-based or custom callbacks). `ScrubCache` handles in-memory caching of include-validation lookups to avoid redundant EntityMap queries within a single request cycle.

6. **Constants-driven resource definition** — Each resource class defines behavior through class constants (`PROP_TYPES`, `REQUIRED_CREATE`, `READONLY`, `CREATEONLY`, `UNSELECTABLE`, `INCLUDE_TYPES`, `WHERE_OPERATIONS`). No database, no schema files — constants ARE the schema.

---

## Coding Conventions

### Naming Patterns

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | PascalCase | `AbstractResource`, `EntityMap`, `RateLimiter` |
| Methods | camelCase | `fetchEntity()`, `validBody()`, `getDirtyKeys()` |
| Properties | camelCase | `$responseCode`, `$fromCacheKey`, `$paginationPage` |
| Constants | UPPER_SNAKE_CASE | `PROP_TYPES`, `REQUIRED_CREATE`, `API_PATH` |
| Entity keys | lowercase plural | `"projects"`, `"tasklists"`, `"invoiceitems"` |
| API paths | lowercase plural | `/projects`, `/tasklists`, `/invoiceitems` |
| Files | PascalCase matching class | `AbstractResource.php`, `EntityMap.php` |

### Indentation

**Mixed.** Most files use 4-space indentation. `Error.php` uses 2-space indentation. No `.editorconfig` or PHP-CS-Fixer configuration enforcing consistency.

### Import Style

```php
use Jcolombo\PaymoApiPhp\Entity\AbstractEntity;
use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
use stdClass;
```

Fully qualified imports at top of file. No group imports. No aliasing unless collision.

### PHPDoc Style

Verbose file-level license headers on most files (28-line MIT block). Class-level and method-level docblocks present on newer/refactored files. Older files have minimal or no docblocks. `@param`, `@return`, `@var` tags used. No `@throws` annotations on most methods despite throwing exceptions.

### Error Handling Pattern

The codebase uses two parallel error paths:
1. **`Error::throw(severity, error, code, message)`** — for SDK-level errors routed through the configurable handler system
2. **Direct `throw new RuntimeException()`** — for critical contract violations in base classes

Fatal errors in `Error::throw('fatal', ...)` ultimately throw `RuntimeException` after processing through handlers.

### Type Declarations

PHP 7.4-compatible type declarations used on newer code. `?string`, `?int`, `bool`, `void` return types present. Many methods still use no type declarations (legacy from original PHP 7.4 writing). No union types (`string|int`) — blocked by PHP 7.4 minimum.

### Property Visibility

Most entity/utility properties are `public`. Base classes use `protected` for internal arrays (`$props`, `$loaded`, `$included`). Singletons use `private static` for instance storage.

---

## Relevant Existing Code

### Core Classes Relevant to Enhancements

#### `src/Paymo.php` (Connection & Execution)

**Relevant to:** FR-002 (Connection reset), FR-003 (autoload fix), FR-013 (debugging), FR-019 (response metadata), FR-022 (rate limit headers), FR-024 (config reset)

- **`Paymo::connect(?string $apiKey, bool $logging, ?string $label)`** — Singleton factory. Stores in `self::$connections[$apiKey]`.
- **`Paymo::execute(RequestAbstraction $request)`** — Central execution pipeline: checks cache → Guzzle HTTP → rate limit tracking → retry logic → response scrubbing → cache storage.
- **Line 62 bug:** `define('PAYMO_DEVELOPMENT_MODE', true);` hardcoded. Should read from config.
- **Rate limit tracking:** Lines ~350-400. Reads `x-ratelimit-*` headers into `RateLimiter` state. Currently tracks but does not expose limits to consumer.
- **Properties:** `$useCache`, `$useLogging`, `$apiKey`, `$label`, `$client` (Guzzle), `$apiUrl`, `$requestHeaders`.

#### `src/Configuration.php` (Config Management)

**Relevant to:** FR-024 (static reset), FR-001 (PHP version config)

- Extends `Noodlehaus\Config`.
- Singleton: `Configuration::$instance`.
- `get(string $key)`, `set(string $key, $value)`, `has(string $key)`, `all()`.
- Loads `default.paymoapi.config.json` as base, merges user's `paymoapi.config.json` if found.
- **No `reset()` method** that destroys the singleton — FR-024 will add this.
- **No `loadFile()` method** for loading additional config files at runtime.

#### `src/Entity/AbstractResource.php` (Resource Base)

**Relevant to:** FR-005 (type coercion), FR-006 (set validation), FR-007 (toArray/toJson), FR-008 (property access), FR-010 (read-only enforcement), FR-011 (required field validation), FR-012 (dirty tracking), FR-015 (clone/replicate), FR-016 (equality comparison)

- **`$props` array** — Current property values (set by user or hydrated from API).
- **`$loaded` array** — Original values from last API hydration (for dirty comparison).
- **`$unlisted` array** — Properties received from API that aren't in PROP_TYPES.
- **`$included` array** — Hydrated related entities (includes).
- **`wash()` method** — Synchronizes `$loaded` with `$props` after fetch/create (marks clean).
- **`isDirty()`, `getDirtyKeys()`, `getDirtyValues()`** — Compare `$props` vs `$loaded`.
- **`set($key, $value)` / `get($key)`** — Property access. `set()` currently does NO type validation.
- **`flatten(?array $options)`** — Returns stdClass of all props + included entities.
- **`fetch($id, $includes, $options)`, `create()`, `update()`, `delete()`** — CRUD operations delegating to `Request` static methods.
- **`static new(?Paymo $connection)`** — Factory returning new instance.
- **`static list(?Paymo $connection)`** — Factory returning `AbstractCollection` for list queries.
- **`static where(string $prop, $value, string $operator)`** — Creates `RequestCondition` for WHERE.
- **`static has(string $include, int $count, string $operator)`** — Creates `RequestCondition` for HAS.
- **REQUIRED_CONSTANTS validation** — Constructor validates subclass defines all required constants.

#### `src/Entity/AbstractCollection.php` (Collection Base)

**Relevant to:** FR-007 (toArray/toJson), FR-009 (fetchAll pagination), FR-014 (collection filtering/sorting)

- Implements `Iterator`, `ArrayAccess`, `JsonSerializable`, `Countable`.
- **`$data` array** — Hydrated resource instances keyed by ID.
- **`$whereConditions` array** — Accumulated WHERE conditions.
- **`$paginationPage` / `$paginationPageSize`** — Pagination state (null = no pagination).
- **`where(RequestCondition $condition)`** — Fluent condition builder. Returns `$this`.
- **`limit(?int $pageOrSize, ?int $pageSize)`** — Sets pagination. Returns `$this`.
- **`fetch(?array $props, ?array $conditions, ?array $options)`** — Executes list query. Merges fluent conditions with `$conditions` param.
- **`flatten(?array $options)`** — Returns array of flattened resources keyed by ID.
- **`jsonSerialize()`** — Returns `array_values($this->flatten())`.
- **`count()`** — Returns `count($this->data)`.
- **`raw()`** — Returns `$this->data` array directly.
- **No `filter()`, `sort()`, `map()`, `first()`, `pluck()` methods** — FR-014 will add these.
- **No `fetchAll()` method** — FR-009 will add auto-paginating fetch.

#### `src/Entity/EntityMap.php` (Entity Registry)

**Relevant to:** FR-003 (bug fix), general architectural understanding

- Central registry mapping entity keys to PHP classes.
- **Line 260 bug:** `"AbstractResourcce"` (double 'c') — `is_subclass_of` check always fails, disabling overload validation entirely.
- `getResourceClass(string $entityKey)`, `getCollectionClass(string $entityKey)`, `getEntityKey(string $className)`.
- Loaded from `classMap` section of config JSON (38 entity mappings).
- Allows runtime overloading of entity-to-class mappings.

#### `src/Request.php` (Request Builder)

**Relevant to:** FR-009 (fetchAll), FR-019 (response metadata), FR-022 (rate limit exposure)

- All static methods: `fetch()`, `list()`, `create()`, `update()`, `delete()`, `upload()`.
- Compiles WHERE conditions into query string via `Converter::convertOperatorValue()`.
- Compiles includes into comma-separated `include` param.
- Scrubs response bodies: extracts entity data from API response wrapper.
- HAS filtering done POST-response using `Adbar\Dot` for nested traversal.
- Returns `RequestResponse` populated by `Paymo::execute()`.

#### `src/Cache/Cache.php` (Response Cache)

**Relevant to:** FR-017 (cache invalidation), FR-018 (custom cache clear callback), FR-019 (cache hit tracking)

- Singleton: `Cache::$instance`.
- **`registerCacheMethods(?callable $fetch, ?callable $store)`** — Accepts 2 callbacks for custom cache backend. FR-018 will add 3rd `$clear` callback.
- **`fetch(string $key)`** — Retrieves cached response. Checks validity via `isValidCache()`.
- **`store(string $key, RequestResponse $response)`** — Stores response.
- **`lifespan(int $seconds)`** / **`rollbackLifespan()`** — Temporary lifespan override pattern.
- **No `invalidate()` method** — FR-017 will add tag-based and key-based invalidation.
- **No `clear()` method** — FR-017 will add full cache clearing.
- File-based storage: serializes `RequestResponse` to filesystem.

#### `src/Utility/Error.php` (Error Handling)

**Relevant to:** FR-020 (error severity), FR-021 (PSR-3 logging)

- Singleton: `Error::$instance`.
- Three severity levels: `notice`, `warn`, `fatal`.
- Configurable handlers per severity: `log`, `echo`, `silent`.
- **`Error::throw(string $severity, $error, $code, string $message)`** — Static entry point.
- **`handleError()`** — Routes to handlers, pushes to error stack, throws RuntimeException on fatal.
- **`$errorStack` array** — Collects all errors during execution. `getAllErrors(bool $clear)` retrieves.
- **`$context` array** — Custom context data for error enrichment.
- **`$requestUrl` / `$requestData`** — Request context tracking.
- Log format: `[timestamp] SEVERITY: message | Code: code | URL: url`.
- **Already has severity-based routing infrastructure** that FR-020 can formalize and extend.

#### `src/Utility/RateLimiter.php` (Rate Limiting)

**Relevant to:** FR-022 (expose rate limit info), FR-023 (configurable rate limits)

- Static state per API key: `$limits[$apiKey]`.
- **`waitIfNeeded(string $apiKey)`** — Enforces minimum delay between requests.
- **`updateFromHeaders(string $apiKey, array $headers)`** — Reads `x-ratelimit-remaining`, `x-ratelimit-limit`, `x-ratelimit-reset` from response headers.
- **`shouldRetry(string $apiKey, int $statusCode)`** — Checks if 429 should be retried.
- **`waitForRetry(string $apiKey)`** — Exponential backoff with jitter.
- **No public accessor for current limits** — FR-022 will expose remaining/limit/reset to consumers.
- Config keys: `rateLimit.minDelayMs`, `rateLimit.maxRetries`, `rateLimit.backoffMultiplier`.

#### `src/Utility/Converter.php` (Type Conversion)

**Relevant to:** FR-005 (type coercion system)

- **`getPrimitiveType(string $type)`** — Maps PROP_TYPES definitions to PHP primitive types.
  - `resource:X` → `integer`, `collection:X` → `integer`, `enum:a|b` → `string::a|b`, `enumInt:25|50` → `integer::25|50`.
  - `datetime` → `timestamp`, `decimal`/`double` → `double`, `boolean` → `boolean`.
  - Everything else → `string`.
- **`convertOperatorValue(RequestCondition $w)`** — Formats WHERE conditions for API query string.
- **`convertValueForFilter(string $type, $value)`** — Type-casts filter values for API calls.
- **Currently only handles outbound (to-API) conversion.** FR-005 will add three-direction coercion: inbound (API→PHP), outbound (PHP→API), and assignment (user→PHP).

#### `src/Utility/RequestResponse.php` (Response Container)

**Relevant to:** FR-019 (response metadata)

- Properties: `$fromCacheKey`, `$success`, `$body`, `$headers`, `$responseCode`, `$responseReason`, `$responseTime`, `$request`, `$result`.
- **`validBody(string $key, int $minQty)`** — Validates response has expected data.
- **`hydrateData(?string $cacheKey, object $rawResponseObject)`** — Populates from cached data.
- **No `$cacheHit` boolean** — FR-019 will add explicit cache-hit tracking.
- **No metadata accessor** — FR-019 will add structured metadata (timing, cache status, rate limit info).

#### `src/Utility/RequestCondition.php` (WHERE/HAS Conditions)

**Relevant to:** FR-005 (type coercion), filter system understanding

- Encapsulates a single WHERE or HAS condition.
- Properties: `$prop`, `$value`, `$operator`, `$dataType`, `$mode` (where/has).
- Created via `Resource::where()` and `Resource::has()` static methods.
- Validated against resource's `WHERE_OPERATIONS` constant for operator restrictions.

### Resource Class Pattern (Representative: `src/Entity/Resource/Project.php`)

Every resource follows this exact constant structure:

```php
class Project extends AbstractResource
{
    const LABEL = 'Project';
    const API_PATH = 'projects';
    const API_ENTITY = 'projects';

    const REQUIRED_CREATE = ['name'];
    const READONLY = ['id', 'created_on', 'updated_on', ...];
    const CREATEONLY = [];
    const UNSELECTABLE = [];

    const INCLUDE_TYPES = [
        'client' => 'resource:clients',
        'tasks' => 'collection:tasks',
        'tasklists' => 'collection:tasklists',
        ...
    ];

    const PROP_TYPES = [
        'id' => 'integer',
        'name' => 'text',
        'client_id' => 'resource:clients',
        'active' => 'boolean',
        'budget_hours' => 'decimal',
        'created_on' => 'datetime',
        'status_id' => 'resource:workflowstatuses',
        ...
    ];

    const WHERE_OPERATIONS = []; // Most resources have empty
}
```

---

## Data Models

### PROP_TYPES Type System

The SDK's type system maps Paymo API property types to PHP data types:

| PROP_TYPE | PHP Type | API Format | Examples |
|-----------|----------|------------|----------|
| `text` | string | string | `name`, `description` |
| `html` | string | HTML string | `description` (some resources) |
| `integer` | int | integer | `id`, `priority` |
| `decimal` / `double` | float | decimal | `budget_hours`, `price_per_hour` |
| `boolean` | bool | true/false | `active`, `complete` |
| `date` | string | "YYYY-MM-DD" | `due_date`, `start_date` |
| `datetime` | string | ISO 8601 | `created_on`, `updated_on` |
| `datetime[]` | string | ISO 8601 range | Booking intervals |
| `email` | string | email | `email` |
| `url` | string | URL | `image` |
| `enum:val1\|val2` | string | one of values | `status`, `type` |
| `enumInt:25\|50\|75\|100` | int | one of values | `priority` |
| `resource:entitykey` | int | foreign key ID | `client_id`, `project_id` |
| `collection:entitykey` | int[] | array of IDs | `user_ids` |
| `array` | int[] | array | `tag_ids` |

### Entity-Class Mapping (from config JSON)

38 entity keys mapped to resource and collection classes. Sample:

```json
{
  "projects": {
    "resource": "Jcolombo\\PaymoApiPhp\\Entity\\Resource\\Project",
    "collection": "Jcolombo\\PaymoApiPhp\\Entity\\Collection\\EntityCollection"
  },
  "tasks": {
    "resource": "Jcolombo\\PaymoApiPhp\\Entity\\Resource\\Task",
    "collection": "Jcolombo\\PaymoApiPhp\\Entity\\Collection\\EntityCollection"
  },
  "files": {
    "resource": "Jcolombo\\PaymoApiPhp\\Entity\\Resource\\File",
    "collection": "Jcolombo\\PaymoApiPhp\\Entity\\Collection\\FileCollection"
  }
}
```

Most entities use the generic `EntityCollection`. Specialized collections exist for: Booking, CommentThread, EstimateItem, File, InvoiceItem, TaskAssignment, TimeEntry.

### Dirty Tracking Model

```
$props[]   — Current values (user-set or API-hydrated)
$loaded[]  — Snapshot after last API sync (fetch/create)
dirty      — Keys where $props[$key] !== $loaded[$key]
wash()     — Copies $props → $loaded (marks clean)
```

### Include/Relationship Model

```
INCLUDE_TYPES constant:
  'client' => 'resource:clients'     → single related entity
  'tasks'  => 'collection:tasks'     → array of related entities

$included[] array:
  'client' => Client instance         → hydrated resource
  'tasks'  => [Task, Task, Task]       → hydrated array
```

---

## API Structure

### Request Lifecycle

```
1. Consumer calls Resource::new()->fetch(123, ['tasks'])
2. AbstractResource::fetch() calls Request::fetch($connection, $path, $options)
3. Request::fetch() builds RequestAbstraction with:
   - method: GET
   - URL: {apiUrl}/projects/123
   - include: "tasks"
   - where: compiled conditions
   - page/pageSize: pagination params
4. Request::fetch() calls Paymo::execute($requestAbstraction)
5. Paymo::execute():
   a. Checks Cache::fetch($cacheKey) if caching enabled
   b. If not cached: RateLimiter::waitIfNeeded($apiKey)
   c. Guzzle HTTP request to Paymo API
   d. RateLimiter::updateFromHeaders($apiKey, $headers)
   e. Populates RequestResponse
   f. Cache::store($cacheKey, $response) if caching enabled
   g. Returns RequestResponse
6. Request::fetch() scrubs response body (extracts entity data)
7. AbstractResource::fetch() hydrates $props and $included from result
8. wash() synchronizes $loaded = $props
```

### API URL Pattern

```
Base: https://app.paymoapp.com/api/
Auth: HTTP Basic (API key as username, "X" as password)
Endpoints: {base}/{entity_path}[/{id}]

GET    /projects           → List projects
GET    /projects/123       → Fetch project 123
POST   /projects           → Create project
PUT    /projects/123       → Update project 123
DELETE /projects/123       → Delete project 123

Query params:
  ?include=tasks,client              → Include relations
  ?where=active=true and name like "X"  → Filter conditions
  ?page=0&page_size=100             → Pagination (undocumented)
```

### Response Format

```json
{
  "projects": [
    {
      "id": 123,
      "name": "Project Name",
      "client_id": 456,
      "tasks": [
        { "id": 789, "name": "Task 1" }
      ]
    }
  ]
}
```

Response wraps entities under a key matching the entity name. Single-fetch returns array with one element.

### Non-Standard Response Keys (OVERRIDE-009, OVERRIDE-010)

| Resource | Expected Key | Actual Key |
|----------|-------------|------------|
| ProjectTemplate | `projecttemplates` | `projecttemplates` (standard) |
| ProjectTemplateTask | `projecttemplatestasks` | uses non-standard key |
| ProjectTemplateTasklist | `projecttemplatestasklists` | uses non-standard key |
| RecurringProfile | `recurringprofiles` | uses non-standard key |
| Gallery resources | varies | uses non-standard keys |

The SDK handles these via entity-specific key overrides in the config/EntityMap.

---

## State Management

### Connection State (`Paymo` class)

```php
// Singleton registry
private static array $connections = [];  // keyed by API key

// Per-connection state
public bool $useCache = false;
public bool $useLogging = false;
public string $apiKey;
public ?string $label;
private Client $client;         // Guzzle client
private string $apiUrl;
private array $requestHeaders;
```

### Resource State (`AbstractResource`)

```php
protected array $props = [];     // Current property values
protected array $loaded = [];    // Last-synced values (for dirty tracking)
protected array $unlisted = [];  // Unknown properties from API
protected array $included = [];  // Hydrated related entities
protected ?Paymo $connection;    // Connection reference
protected bool $hydrationMode;   // True during API hydration (bypasses validation)
```

### Collection State (`AbstractCollection`)

```php
protected array $data = [];              // Hydrated resources keyed by ID
protected array $whereConditions = [];   // Accumulated WHERE conditions
protected ?int $paginationPage = null;
protected ?int $paginationPageSize = null;
protected ?Paymo $connection;
protected string $entityKey;
protected string $entityClass;
```

### Cache State (`Cache` class)

```php
private static ?self $instance = null;
private ?int $lifespan;           // Cache TTL in seconds
private ?int $previousLifespan;   // For rollbackLifespan()
private ?string $cachePath;       // File cache directory
private ?callable $fetchCallback; // Custom fetch handler
private ?callable $storeCallback; // Custom store handler
```

### Rate Limiter State (`RateLimiter` class)

```php
private static array $limits = [];  // Per API key: remaining, limit, reset, lastRequest
private static int $minDelayMs;
private static int $maxRetries;
private static float $backoffMultiplier;
```

### Error State (`Error` class)

```php
private static ?self $instance = null;
private array $errorStack = [];     // All errors during execution
private array $context = [];        // Custom context data
private string $requestUrl = '';    // Last request URL
private array $requestData = [];    // Last request payload
private array $handlers;            // Severity → handler mode mapping
private bool $triggerPhpErrors;
private bool $handlerEnabled;
```

---

## Integration Points

### Where Enhancements Connect to Existing Code

#### FR-001 (PHP 8.1 Minimum) — Touches Everything
- `composer.json`: Change `"php": ">=7.4"` to `">=8.1"`
- All files: Can use union types, enums, named arguments, readonly properties, fibers
- `src/Paymo.php`: Replace `define()` with typed class constant
- Test framework: Can leverage PHP 8.1 features

#### FR-005 (Type Coercion) — Core Data Path
- **Entry point:** `AbstractResource::set()` (`src/Entity/AbstractResource.php`)
- **Conversion engine:** `Converter::getPrimitiveType()` (`src/Utility/Converter.php`)
- **Type definitions:** Each resource's `PROP_TYPES` constant
- **Hydration path:** `AbstractResource::_hydrate()` (inbound from API)
- **Filter path:** `Converter::convertValueForFilter()` (outbound to API)
- Must not break existing `RequestCondition` type handling

#### FR-007 (toArray/toJson) — Entity Output
- **Add to:** `AbstractResource` (single resource) and `AbstractCollection` (collections)
- **Existing:** `flatten()` on both classes, `jsonSerialize()` on collections
- **Must coordinate with:** `JsonSerializable` interface already on `AbstractCollection`

#### FR-009 (fetchAll Pagination) — Collection Enhancement
- **Add to:** `AbstractCollection` (`src/Entity/AbstractCollection.php`)
- **Uses:** `limit()` method internally for page iteration
- **Relies on:** `Request::list()` and `Paymo::execute()` pipeline
- **Config:** `rateLimit.minDelayMs` affects iteration speed

#### FR-017 (Cache Invalidation) — Cache System
- **Modify:** `Cache` class (`src/Cache/Cache.php`)
- **Interacts with:** `RequestAbstraction::makeCacheKey()` for key generation
- **Interacts with:** `Paymo::execute()` for cache store/fetch calls
- **Must NOT touch:** `ScrubCache` (different purpose entirely)

#### FR-020 (Error Severity) — Error System
- **Modify:** `Error` class (`src/Utility/Error.php`)
- **Already has:** Three severity levels, configurable handlers, error stack
- **Enhancement:** Formalize severity definitions, add error codes catalog, improve context

#### FR-021 (PSR-3 Logging) — Logging System
- **New dependency:** `psr/log` (interface only)
- **Modify:** `Error` class and `Log` utility
- **Integration:** Paymo connection accept PSR-3 logger, pass to Error/Log subsystems

#### FR-022 (Rate Limit Exposure) — Connection Metadata
- **Modify:** `RateLimiter` (`src/Utility/RateLimiter.php`)
- **Expose via:** `Paymo` connection instance or new accessor method
- **Data source:** Already tracked in `RateLimiter::$limits` per API key

#### FR-024 (Configuration Reset) — Config Management
- **Modify:** `Configuration` (`src/Configuration.php`)
- **Add:** `static reset()` method that nullifies `$instance`
- **Cascading resets:** May need to also reset `Error`, `Cache`, `EntityMap` singletons

### Potential Conflicts

1. **`flatten()` vs `toArray()`** — FR-007 adds `toArray()`/`toJson()` which may overlap with existing `flatten()`. Must clearly differentiate purpose (flatten is internal, toArray is public API) or deprecate flatten.

2. **Collection iteration vs filtering** — FR-014 adds `filter()`/`sort()` to collections. Must not conflict with `Iterator`/`ArrayAccess` implementation. Post-fetch in-memory operations vs pre-fetch WHERE conditions.

3. **Cache invalidation vs cache callbacks** — FR-017 invalidation and FR-018 clear callback both modify `Cache`. Must be designed together to avoid conflicting cache state.

4. **Type coercion vs dirty tracking** — FR-005 coercion during `set()` may change values. Must ensure dirty tracking compares coerced values correctly (original raw value vs coerced value).

5. **PHP 8.1 upgrade vs backward compatibility** — FR-001 opens union types, enums, readonly. Must decide: gradual adoption or immediate rewrite of type declarations.

---

## Pattern Catalog

### How New Resource Classes Are Structured

```php
// src/Entity/Resource/NewResource.php
namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class NewResource extends AbstractResource
{
    const LABEL = 'New Resource';
    const API_PATH = 'newresources';        // API endpoint path
    const API_ENTITY = 'newresources';      // Response wrapper key

    const REQUIRED_CREATE = ['name'];        // Required for create()
    const READONLY = ['id', 'created_on', 'updated_on'];
    const CREATEONLY = [];                   // Only settable on create
    const UNSELECTABLE = [];                 // Can't request via field selection

    const INCLUDE_TYPES = [
        'parent' => 'resource:parents',
        'children' => 'collection:children',
    ];

    const PROP_TYPES = [
        'id' => 'integer',
        'name' => 'text',
        'parent_id' => 'resource:parents',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
    ];

    const WHERE_OPERATIONS = [];             // Empty unless operator restrictions exist
}
```

### How New Collection Classes Are Structured

Only needed when a resource requires custom fetch validation (e.g., mandatory parent filters):

```php
// src/Entity/Collection/NewResourceCollection.php
namespace Jcolombo\PaymoApiPhp\Entity\Collection;

class NewResourceCollection extends EntityCollection
{
    protected function validateFetch(): void
    {
        // Check that required parent filters are present
        $hasParent = false;
        foreach ($this->whereConditions as $condition) {
            if ($condition->prop === 'parent_id') {
                $hasParent = true;
                break;
            }
        }
        if (!$hasParent) {
            throw new \RuntimeException('NewResource list requires parent_id filter');
        }
    }
}
```

### How Tests Are Structured

```php
// tests/ResourceTests/ProjectTest.php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;

class ProjectTest extends ResourceTest
{
    protected string $resourceClass = Project::class;

    // Test methods use $this->assert*() from ResourceTest base
    // Tests require live API connection (no mocking)
    // TestConfig provides API key and test data IDs
}
```

**Test runner:** `php tests/ResourceTestRunner.php [--resource=Project] [--verbose]`

### How Configuration Is Managed

```json
// default.paymoapi.config.json (shipped with package)
{
  "connection": {
    "url": "https://app.paymoapp.com/api/",
    "timeout": 15.0,
    "retries": 3
  },
  "rateLimit": {
    "minDelayMs": 200,
    "maxRetries": 3,
    "backoffMultiplier": 2.0
  },
  "error": {
    "handlers": {
      "notice": ["log"],
      "warn": ["log"],
      "fatal": ["log", "echo"]
    },
    "triggerPhpErrors": false,
    "enabled": true
  },
  "enabled": {
    "cache": false,
    "logging": false
  },
  "classMap": { /* 38 entity mappings */ }
}
```

User overrides via `paymoapi.config.json` in project root. Values deep-merged over defaults.

### How Errors Are Handled

```php
// SDK-level errors through handler system
Error::throw('notice', null, 100, 'Cache miss, fetching from API');
Error::throw('warn', ['field' => 'name'], 200, 'Property type mismatch');
Error::throw('fatal', $apiError, 500, 'API request failed');

// Contract violations throw directly
throw new RuntimeException('Resource must define PROP_TYPES constant');
```

### How Cache Is Used

```php
// File-based (default)
Cache::lifespan(600);
$connection->useCache = true;

// Custom backend
Cache::registerCacheMethods(
    function(string $key) { /* fetch from Redis/Memcached */ },
    function(string $key, $data) { /* store to Redis/Memcached */ }
);
```

---

## Reusable Assets

### Code to Reuse (Not Recreate)

| Asset | Location | Reuse For |
|-------|----------|-----------|
| Singleton pattern | `Paymo.php`, `Configuration.php`, `Error.php`, `Cache.php` | Any new singleton services |
| Entity constant validation | `AbstractResource::__construct()` | Extending validation for new constants |
| Dirty tracking system | `AbstractResource::$props/$loaded/wash()/isDirty()` | FR-012 enhancement builds on this |
| Type mapping | `Converter::getPrimitiveType()` | FR-005 type coercion extends this |
| Rate limit header parsing | `RateLimiter::updateFromHeaders()` | FR-022 exposure builds on existing tracking |
| Error severity routing | `Error::handleError()` handler dispatch | FR-020 formalizes existing infrastructure |
| Collection iteration | `AbstractCollection` Iterator/ArrayAccess/Countable | FR-014 adds methods to existing interface |
| Cache key generation | `RequestAbstraction::makeCacheKey()` | FR-017 invalidation needs to understand key structure |
| Config deep-merge | `Configuration` extending Noodlehaus\Config | FR-024 reset must respect merge behavior |
| WHERE condition compilation | `Converter::convertOperatorValue()` | Any new filter features |
| Response scrubbing | `Request::_scrubResult()` | FR-019 metadata extraction |
| `flatten()` method | `AbstractResource`/`AbstractCollection` | FR-007 toArray/toJson should extend or wrap |
| Test framework base | `ResourceTest`, `ResourceTestRunner` | All new tests follow this pattern |
| OVERRIDES documentation | `OVERRIDES.md` with `@override` comments | Any property/behavior changes must check overrides first |

### Patterns to Follow (Not Reinvent)

1. **Static factory pattern** — `Resource::new()`, `Resource::list()`, `Resource::where()`. New resource operations should follow this pattern.
2. **Fluent builder pattern** — `Collection->where()->limit()->fetch()`. New collection methods should return `$this` for chaining.
3. **Constants-as-schema** — Resource behavior defined via class constants. New resource metadata should use constants, not methods.
4. **Config-as-singleton** — `Configuration::get('path.to.key')`. New configurable features should use this.
5. **Error through handler** — `Error::throw('severity', ...)`. New error conditions should use the handler system.

---

## Technical Constraints

### Confirmed Bugs (Must Fix in v1.0.0-alpha)

1. **EntityMap typo** (`src/Entity/EntityMap.php:260`): `"AbstractResourcce"` double-c typo. `is_subclass_of()` check always fails, silently disabling overload validation. Fix: correct to `"AbstractResource"`.

2. **Hardcoded devMode** (`src/Paymo.php:62`): `define('PAYMO_DEVELOPMENT_MODE', true)` always sets dev mode. Should read from config or be removed.

3. **Wrong autoload-dev namespace** (`composer.json`): `autoload-dev` maps `Jcolombo\\PaymoApiPhp\\` to `src/` (should map test namespace to `tests/`). Currently benign but incorrect.

### Technical Debt

1. **No static analysis** — No PHPStan, Psalm, or Phan. Type errors are only caught at runtime.
2. **No CI/CD pipeline** — No automated testing, linting, or build verification.
3. **Mixed indentation** — 2-space in `Error.php`, 4-space elsewhere. No enforcing tool.
4. **Live API tests only** — Test framework requires active API connection. No unit tests with mocks. No offline testing capability.
5. **No return type on many methods** — Legacy PHP 7.4 style with missing type declarations.
6. **Untyped `$request` and `$result` properties** on `RequestResponse` — declared as `public $request` and `public $result` with no type constraint.

### PHP 7.4 Limitations (Removed When FR-001 Targets 8.1)

- No union types (`string|int`)
- No `enum` keyword
- No `readonly` properties
- No named arguments
- No `match` expression
- No fibers
- No intersection types
- No `never` return type

### Backward Compatibility Constraints

- `flatten()` is used by consumers — cannot remove without deprecation
- `jsonSerialize()` on collections returns specific format — changes break JSON consumers
- `set()` currently accepts any value for any property — adding validation may break consumers who set values that get coerced by the API
- WHERE condition string format is consumed by Paymo API — must match API expectations exactly
- Cache key format (`RequestAbstraction::makeCacheKey()`) is MD5-based — changing format invalidates all existing caches

### API Constraints (from OVERRIDES.md)

- **13 active overrides** documenting deviations from stale API docs (last updated 2022)
- **OVERRIDE-003:** Undocumented pagination via `page`/`page_size` query params
- **OVERRIDE-005:** Some collections require parent filters (File→project_id, Booking→date range, InvoiceItem→invoice_id, EstimateItem→estimate_id)
- **OVERRIDE-009/010:** Non-standard response keys for template and recurring resources
- **OVERRIDE-013:** 32 properties across 6 resources are UNSELECTABLE (HTTP 400 if explicitly requested)
- **Four filter-only properties** that exist in WHERE clauses but are never returned in responses: `Booking.project_id`, `Booking.task_id`, `Booking.date_interval`, `TimeEntry.time_interval`

### Dependency Constraints

- **Guzzle ^7.8** — Major version locked. Guzzle 8 would be a separate migration.
- **hassankhan/config ^3.2** — Wraps Noodlehaus. Configuration class extends it directly — tightly coupled.
- **adbario/php-dot-notation ^3.3** — Used in HAS filtering (Request.php). Can be replaced if needed but currently embedded in filter logic.
- **FR-021 will add `psr/log` dependency** — Interface-only package, minimal risk.
- **No other new dependencies planned** for v1.0.0-alpha per requirements NFR-005.

### Resource-Specific Constraints

| Constraint | Resources | Impact |
|-----------|-----------|--------|
| No create() | Company | Company is account-level, not user-created |
| No update() | Session, CommentThread | Read-only or limited API operations |
| No delete() | Gallery resources | Read-only catalog entities |
| Required parent filters | File, Booking, InvoiceItem, EstimateItem | Must pass parent ID in WHERE to list |
| UNSELECTABLE properties | Client(4), User(20), Task(1), Milestone(1), Expense(3), File(3) | Cannot explicitly request these fields |
| Non-standard response keys | ProjectTemplate*, RecurringProfile, Gallery | SDK handles via config mapping |
