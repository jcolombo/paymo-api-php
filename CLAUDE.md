# Paymo API PHP SDK - AI Assistant Guide

**Instructions for Claude and other AI assistants to help developers use this package.**

---

## ⚠️ CRITICAL: Check OVERRIDES.md Before "Fixing" Discrepancies

The Paymo API documentation hasn't been updated since 2022. When you encounter:

- Properties "missing" from API responses
- Properties in API responses not in `PROP_TYPES`
- Type mismatches between code and API
- Any apparent discrepancy

**FIRST check `OVERRIDES.md`** - it documents known deviations that are **intentional**.

### What NOT to Do:
- ❌ Remove properties marked with `@override` comments
- ❌ Add "missing" properties without checking if they're conditional
- ❌ "Fix" discrepancies that are documented overrides

### What to Do:
- ✅ Read `OVERRIDES.md` to understand known API deviations
- ✅ Look for `@override OVERRIDE-XXX` comments in code before modifying
- ✅ Ask before modifying any override-marked code

Code with `@override` comments represents **tested, verified behavior** - do not change without explicit approval.

---

## Quick Reference

### Package Namespace
```php
use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Entity\Resource\{ResourceName};
```

### Essential Files
| Purpose | Location |
|---------|----------|
| Main connection class | `src/Paymo.php` |
| Resource classes | `src/Entity/Resource/*.php` |
| Collection classes | `src/Entity/Collection/*.php` |
| Query conditions | `src/Utility/RequestCondition.php` |
| Configuration | `default.paymoapi.config.json` |
| Official API Docs | https://github.com/paymoapp/api |

---

## 1. Connection Management

### Connection Requirement

A Paymo API connection must exist before any resource operations can be performed. However, **do not assume you need to create a connection** - the application may have already established one via:

- A global bootstrap/loader file
- A wrapper class or service provider
- An earlier script in the same PHP process

**Before writing connection code, verify whether a connection already exists in the codebase.**

### Establishing a Connection

If no connection exists, create one:

```php
use Jcolombo\PaymoApiPhp\Paymo;

// Basic connection with API key
Paymo::connect('YOUR_API_KEY');

// Connection with logging enabled
Paymo::connect('YOUR_API_KEY', true);

// Named connection (useful for debugging multiple connections)
Paymo::connect('YOUR_API_KEY', false, 'MyConnection');
```

### Singleton Behavior

Connections are stored as **singletons per API key**:

```php
// First call creates the connection
Paymo::connect('API_KEY_A');

// Second call with SAME key returns the existing connection (no new connection created)
Paymo::connect('API_KEY_A');

// Call with DIFFERENT key creates a NEW separate connection
Paymo::connect('API_KEY_B');

// Call with NO key returns the first established connection
Paymo::connect();  // Returns API_KEY_A connection
```

### Retrieving Existing Connection

To get an existing connection without creating a new one:

```php
// Returns the first (default) established connection
// Throws exception if no connection exists
$connection = Paymo::connect();
```

---

## 2. Connection State and Settings Persistence

### How Settings Persist

Connection settings like `useCache` and `useLogging` are **properties on the connection instance**. Once set, they persist for all subsequent operations using that connection until explicitly changed.

```php
// Establish connection with logging enabled
$connection = Paymo::connect('API_KEY', true);
// $connection->useLogging = true (persists)
// $connection->useCache = false (from config default)

// ALL subsequent operations use these settings
$project = Project::new()->fetch(12345);  // Logging ON, Cache OFF
$tasks = Task::list()->fetch();           // Logging ON, Cache OFF
$client = Client::new()->fetch(67890);    // Logging ON, Cache OFF
```

### Changing Settings Mid-Flow

You can modify connection settings at any time - changes take effect immediately:

```php
$connection = Paymo::connect('API_KEY');

// Enable caching for bulk operations
$connection->useCache = true;

$projects = Project::list()->fetch();  // Uses cache
$clients = Client::list()->fetch();    // Uses cache

// Disable caching for a fresh data requirement
$connection->useCache = false;

$invoice = Invoice::new()->fetch($id);  // Bypasses cache

// Re-enable for remaining operations
$connection->useCache = true;
```

### Per-Request Cache Override

For one-off cache bypasses without changing the connection setting:

```php
// Connection has caching enabled
$connection->useCache = true;

// Skip cache for THIS request only (connection setting unchanged)
$project = Project::new()->fetch(12345, [], ['skipCache' => true]);

// Next request still uses cache (connection setting was not modified)
$anotherProject = Project::new()->fetch(67890);  // Uses cache
```

### Multiple API Keys in Same Process

When working with multiple Paymo accounts, each API key maintains its own connection with independent settings:

```php
// Connect to Account A with caching
$connA = Paymo::connect('API_KEY_A');
$connA->useCache = true;

// Connect to Account B without caching
$connB = Paymo::connect('API_KEY_B');
$connB->useCache = false;

// Operations use settings from their respective connections
$projectA = new Project($connA);
$projectA->fetch(123);  // Uses cache (Account A settings)

$projectB = new Project($connB);
$projectB->fetch(456);  // No cache (Account B settings)

// Default resources use the FIRST established connection
$task = Task::new()->fetch(789);  // Uses $connA (first connection)
```

### Switching Default Connection

When resources don't specify a connection, they use the first established one. To use a different connection:

```php
// Explicit connection on resource
$project = new Project($connB);
$project->fetch(12345);

// Or pass to static methods
$project = Project::new($connB)->fetch(12345);
```

### Settings Flow Example

```php
// Scenario: Batch import with mixed requirements

$connection = Paymo::connect('API_KEY', true);  // Logging ON

// Phase 1: Cache user list (expensive, rarely changes)
$connection->useCache = true;
$users = User::list()->fetch();

// Phase 2: Fresh project data required
$connection->useCache = false;
$connection->useLogging = false;  // Reduce log noise for bulk

foreach ($projectIds as $id) {
    $project = Project::new()->fetch($id);
    // ... process
}

// Phase 3: Re-enable logging for final operations
$connection->useLogging = true;
$invoice = Invoice::new()->set([...])->create();
```

---

## 3. Available Resources

All resources are located in `src/Entity/Resource/`. Import them as:

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\Resource\Task;
use Jcolombo\PaymoApiPhp\Entity\Resource\Client;
// etc.
```

### Core Resources

| Resource | Class | API Endpoint |
|----------|-------|--------------|
| Project | `Project` | `/projects` |
| Task | `Task` | `/tasks` |
| Subtask | `Subtask` | `/subtasks` |
| Tasklist | `Tasklist` | `/tasklists` |
| Client | `Client` | `/clients` |
| User | `User` | `/users` |
| TimeEntry | `TimeEntry` | `/entries` |

### Financial Resources

| Resource | Class | API Endpoint |
|----------|-------|--------------|
| Invoice | `Invoice` | `/invoices` |
| InvoiceItem | `InvoiceItem` | `/invoiceitems` |
| InvoicePayment | `InvoicePayment` | `/invoicepayments` |
| RecurringProfile | `RecurringProfile` | `/recurringprofiles` |
| RecurringProfileItem | `RecurringProfileItem` | `/recurringprofileitems` |
| Estimate | `Estimate` | `/estimates` |
| EstimateItem | `EstimateItem` | `/estimateitems` |
| Expense | `Expense` | `/expenses` |

### Supporting Resources

| Resource | Class | API Endpoint |
|----------|-------|--------------|
| Milestone | `Milestone` | `/milestones` |
| Booking | `Booking` | `/bookings` |
| TaskAssignment | `TaskAssignment` | `/userstasks` |
| TaskRecurringProfile | `TaskRecurringProfile` | `/taskrecurringprofiles` |
| Workflow | `Workflow` | `/workflows` |
| WorkflowStatus | `WorkflowStatus` | `/workflowstatuses` |
| File | `File` | `/files` |
| Comment | `Comment` | `/comments` |
| Discussion | `Discussion` | `/discussions` |
| ClientContact | `ClientContact` | `/clientcontacts` |
| Webhook | `Webhook` | `/hooks` |
| Report | `Report` | `/reports` |
| Company | `Company` | `/company` |
| Session | `Session` | `/sessions` |
| CommentThread | `CommentThread` | `/threads` |
| ProjectStatus | `ProjectStatus` | `/projectstatuses` |

### Template Resources

| Resource | Class | API Endpoint |
|----------|-------|--------------|
| ProjectTemplate | `ProjectTemplate` | `/projecttemplates` |
| InvoiceTemplate | `InvoiceTemplate` | `/invoicetemplates` |
| EstimateTemplate | `EstimateTemplate` | `/estimatetemplates` |

### Template Detail Resources

| Resource | Class | API Endpoint |
|----------|-------|--------------|
| ProjectTemplateTask | `ProjectTemplateTask` | `/projecttemplatestasks` |
| ProjectTemplateTasklist | `ProjectTemplateTasklist` | `/projecttemplatestasklists` |

### Gallery Resources (Read-Only)

| Resource | Class | API Endpoint |
|----------|-------|--------------|
| EstimateTemplateGallery | `EstimateTemplateGallery` | `/estimatetemplatesgallery` |
| InvoiceTemplateGallery | `InvoiceTemplateGallery` | `/invoicetemplatesgallery` |

---

## 4. CRUD Operations

### Create a Resource

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;

// Method 1: Set properties then create
$project = new Project();
$project->name = 'New Project';
$project->client_id = 12345;
$project->create();

// Method 2: Fluent style with set()
$project = Project::new()
    ->set([
        'name' => 'New Project',
        'client_id' => 12345,
        'description' => 'Project description'
    ])
    ->create();

echo $project->id; // Access the new ID
```

### Fetch a Single Resource

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;

// Fetch by ID
$project = Project::new()->fetch(12345);

// Fetch with included relations
$project = Project::new()->fetch(12345, ['client', 'tasks', 'tasklists']);

// Access data
echo $project->name;
echo $project->client->name; // Access included relation
```

### Fetch a Collection (List)

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\Resource\Task;

// Fetch all
$projects = Project::list()->fetch();

// Iterate results
foreach ($projects as $project) {
    echo $project->name . "\n";
}

// Get count directly (implements Countable)
echo count($projects);  // e.g., 25

// JSON encode directly (implements JsonSerializable)
$json = json_encode($projects);  // Returns array of flattened objects

// Assign to response object - auto-serializes correctly
$response->projects = $projects;  // Will serialize as array in JSON output

// Fetch with specific fields only
$projects = Project::list()->fetch(['id', 'name', 'client_id']);

// Fetch with WHERE conditions (passed to fetch)
$tasks = Task::list()->fetch(
    ['id', 'name', 'complete'],
    [Task::where('complete', false)]
);

// Multiple WHERE conditions (passed to fetch)
$tasks = Task::list()->fetch(
    ['id', 'name', 'due_date'],
    [
        Task::where('project_id', 12345),
        Task::where('complete', false),
        Task::where('due_date', '2024-12-31', '<=')
    ]
);
```

### Fluent WHERE Conditions

The SDK supports a fluent API for building WHERE conditions using the `where()` method
on collections. This provides a more readable query-builder style syntax:

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Task;
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;

// Single condition - fluent style
$incompleteTasks = Task::list()
    ->where(Task::where('complete', false))
    ->fetch();

// Multiple conditions - chained fluently
$urgentTasks = Task::list()
    ->where(Task::where('project_id', 12345))
    ->where(Task::where('complete', false))
    ->where(Task::where('due_date', '2024-12-31', '<='))
    ->fetch(['id', 'name', 'due_date']);

// Combine with limit() for pagination
$tasks = Task::list()
    ->where(Task::where('complete', false))
    ->where(Task::where('priority', 50, '>='))
    ->limit(25)
    ->fetch();

// Active projects with specific users
$projects = Project::list()
    ->where(Project::where('active', true))
    ->where(Project::where('users', [100, 101, 102], 'in'))
    ->fetch(['name', 'client_id']);
```

**Key Points:**
- Conditions are accumulated with implicit AND logic
- `where()` returns `$this` for chaining
- Can be combined with `limit()` in any order
- You can still pass conditions directly to `fetch()` - they are merged
- Use `Resource::where()` to create properly typed conditions

### Pagination (Limiting Results)

> **UNDOCUMENTED API FEATURE** - See `OVERRIDES.md#override-003`
>
> The Paymo API supports pagination via `page` and `page_size` query parameters,
> but this is NOT documented in the official API documentation. Discovered through
> direct communication with Paymo support in December 2024.

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Invoice;
use Jcolombo\PaymoApiPhp\Entity\Resource\Task;

// Fetch only the first 100 results (page 0 implied)
$invoices = Invoice::list()->limit(100)->fetch();

// Fetch page 2 with 50 results per page (results 101-150, 0-indexed)
$invoices = Invoice::list()->limit(2, 50)->fetch();

// Combine with WHERE conditions
$tasks = Task::list()
    ->limit(25)
    ->fetch(['name'], [Task::where('active', true)]);

// Combine with field selection
$clients = Client::list()->limit(10)->fetch(['id', 'name', 'email']);

// Clear pagination (fetch all)
$collection = Project::list();
$collection->limit();  // Now fetches all results

// Manual page iteration (API doesn't return total count)
$page = 0;
$pageSize = 100;
$allInvoices = [];

do {
    $invoices = Invoice::list()->limit($page, $pageSize)->fetch();
    $results = $invoices->raw();
    $count = count($results);

    $allInvoices = array_merge($allInvoices, $results);
    $page++;

} while ($count === $pageSize); // Stop when fewer results than requested
```

**Important:**
- Pages are **0-indexed** (page=0 is the first page)
- Single param: `limit(100)` = page 0, 100 results
- Two params: `limit(2, 50)` = page 2, 50 results per page
- The API does NOT return total count - track pages manually
- WHERE conditions are applied BEFORE pagination
- Paymo support mentioned a possible max page_size of 2500 (unconfirmed)

### Update a Resource

```php
// Fetch, modify, update
$project = Project::new()->fetch(12345);
$project->name = 'Updated Name';
$project->description = 'New description';
$project->update();

// Only changed (dirty) fields are sent to the API
```

### Delete a Resource

```php
// Delete via instance
$project = Project::new()->fetch(12345);
$project->delete();

// Delete by ID
Project::deleteById(12345);
```

### CRUD Restrictions

Not all resources support all CRUD operations. Attempting an unsupported operation throws a `RuntimeException`:

| Resource | Supported Operations | Restricted Operations |
|----------|---------------------|----------------------|
| Company | `fetch()`, `update()` | `list()`, `create()`, `delete()` throw RuntimeException |
| CommentThread | `fetch()`, `list()`, `delete()` | `create()` throws RuntimeException; `update()` not supported (all properties READONLY) |
| Session | `fetch()`, `list()`, `create()`, `delete()` | `update()` throws RuntimeException |
| EstimateTemplateGallery | `fetch()`, `list()` | `create()`, `update()`, `delete()` throw RuntimeException |
| InvoiceTemplateGallery | `fetch()`, `list()` | `create()`, `update()`, `delete()` throw RuntimeException |

---

## 5. WHERE Conditions (Filtering)

Use `Resource::where()` to create filter conditions for list queries.

### Basic Operators

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Task;

// Equality
Task::where('complete', true)
Task::where('project_id', 12345)

// Comparison
Task::where('budget', 1000, '>')
Task::where('due_date', '2024-01-01', '>=')
Task::where('priority', 50, '<=')

// Not equal
Task::where('status', 'closed', '!=')
```

### String Matching

```php
// LIKE pattern matching (% as wildcard)
Client::where('name', '%Acme%', 'like')
Client::where('email', '%@gmail.com', 'like')

// NOT LIKE
Client::where('name', '%test%', 'not like')
```

### Array Operators

```php
// IN - value in set
Project::where('status_id', [1, 2, 3], 'in')
Task::where('user_id', [100, 101, 102], 'in')

// NOT IN - value not in set
Project::where('status_id', [4, 5], 'not in')

// RANGE - value between min and max
Invoice::where('total', [100, 500], 'range')
```

### Complete Example

```php
$tasks = Task::list()->fetch(
    ['id', 'name', 'due_date', 'user_id'],
    [
        Task::where('project_id', 12345),
        Task::where('complete', false),
        Task::where('priority', 50, '>='),
        Task::where('user_id', [100, 101], 'in')
    ]
);
```

---

## 6. HAS Conditions (Relationship Counts)

Use `Resource::has()` to filter by the count of included relationships.

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;

// Projects with at least one task
Project::has('tasks', 0, '>')

// Projects with exactly 5 milestones
Project::has('milestones', 5, '=')

// Projects with no tasks
Project::has('tasks', 0, '=')

// Combined with WHERE
$projects = Project::list()->fetch(
    ['id', 'name', 'tasks'],  // Must include the relation
    [
        Project::where('active', true),
        Project::has('tasks', 0, '>')
    ]
);
```

**Note:** HAS conditions require the relationship to be included in the fetch fields.

---

## 7. Including Related Entities

Load related entities in a single API call using includes:

```php
// Single include
$project = Project::new()->fetch(12345, ['client']);
echo $project->client->name;

// Multiple includes
$project = Project::new()->fetch(12345, ['client', 'tasklists', 'tasks', 'milestones']);

// Access included collections
foreach ($project->tasks as $task) {
    echo $task->name . "\n";
}

// Check resource INCLUDE_TYPES constant for available includes
// See: src/Entity/Resource/{Resource}.php
```

### Common Include Relationships

```php
// Project includes
['client', 'tasklists', 'tasks', 'milestones', 'discussions', 'files', 'workflow']

// Task includes
['project', 'tasklist', 'users', 'entries', 'subtasks', 'comments']

// Client includes
['projects', 'contacts', 'invoices']

// Invoice includes
['client', 'invoiceitems', 'payments']
```

---

## 8. Working with Properties

### Get and Set

```php
$project = Project::new()->fetch(12345);

// Get single property
$name = $project->get('name');

// Get multiple properties
$data = $project->get(['name', 'description', 'active']);

// Set single property
$project->set('name', 'New Name');

// Set multiple properties
$project->set([
    'name' => 'New Name',
    'description' => 'New description'
]);
```

### Dirty Tracking

The SDK tracks which properties have been modified:

```php
$project = Project::new()->fetch(12345);
$project->name = 'Changed Name';

// Check if dirty
if ($project->isDirty()) {
    $project->update(); // Only sends changed fields
}

// Get dirty keys
$dirtyKeys = $project->getDirtyKeys(); // ['name']

// Get dirty values with original and current
$dirtyValues = $project->getDirtyValues();
// ['name' => ['original' => 'Old Name', 'current' => 'Changed Name']]
```

### Flatten to stdClass

Export entity data as a plain object:

```php
$project = Project::new()->fetch(12345, ['client', 'tasks']);

// Get as stdClass (includes relations)
$data = $project->flatten();

// Strip null values
$data = $project->flatten(['stripNull' => true]);
```

### JSON Serialization

Collections implement `JsonSerializable` for direct JSON encoding:

```php
$projects = Project::list()->fetch(['id', 'name']);

// Direct JSON encoding - returns array of flattened objects
echo json_encode($projects);
// Output: [{"id": 123, "name": "Project A"}, {"id": 456, "name": "Project B"}]

// Assign directly to response objects
$response->data = $projects;  // Auto-serializes when response is encoded

// For explicit control, use flatten() on collections
$array = $projects->flatten();  // Returns array keyed by resource ID
$array = array_values($projects->flatten());  // Sequential array
```

---

## 9. Common Patterns

### Get All Projects for a Client

```php
$projects = Project::list()->fetch(
    ['id', 'name', 'active', 'status_id'],
    [
        Project::where('client_id', $clientId),
        Project::where('active', true)
    ]
);
```

### Get Unbilled Time Entries for a Project

```php
$tasks = Task::list()->fetch(
    ['id', 'name', 'billable', 'entries'],
    [Task::where('project_id', $projectId)]
);

foreach ($tasks->flatten() as $task) {
    if ($task->billable) {
        foreach ($task->entries as $entry) {
            if (!$entry->billed) {
                // Process unbilled entry
                $hours = $entry->duration / 3600;
            }
        }
    }
}
```

### Create an Invoice with Items

```php
// Create invoice
$invoice = Invoice::new()->set([
    'client_id' => $clientId,
    'currency' => 'USD',
    'title' => 'Services for December 2024',
    'date' => '2024-12-01',
    'due_date' => '2024-12-31'
])->create();

// Create invoice items
$item = InvoiceItem::new()->set([
    'invoice_id' => $invoice->id,
    'item' => 'Consulting Services',
    'quantity' => 10,
    'price_unit' => 150.00
])->create();
```

### Mark Time Entry as Billed

```php
$timeEntry = TimeEntry::new()->fetch($entryId);
$timeEntry->set([
    'billed' => true,
    'invoice_item_id' => $invoiceItemId
])->update();
```

### Get Cached Users List

```php
// Users are expensive to fetch repeatedly
// Cache them for efficiency
$users = User::list()->fetch();
$userMap = [];
foreach ($users->flatten() as $user) {
    $userMap[$user->id] = [
        'name' => $user->name,
        'email' => $user->email,
        'price_per_hour' => $user->price_per_hour
    ];
}
```

---

## 10. Error Handling

```php
try {
    $project = Project::new()
        ->set(['name' => 'Test'])
        ->create();
} catch (\Exception $e) {
    error_log("Failed to create project: " . $e->getMessage());
}
```

### Common Errors

- **Missing required fields** - Check resource's `REQUIRED_CREATE` constant
- **Invalid property** - Check resource's `PROP_TYPES` constant
- **Read-only property** - Check resource's `READONLY` constant
- **API rate limiting** - Add delays between requests

---

## 11. Resource Class Reference

To understand a resource's capabilities, check these constants in the resource file:

| Constant | Purpose |
|----------|---------|
| `PROP_TYPES` | All available properties and their types |
| `REQUIRED_CREATE` | Properties required for `create()` |
| `READONLY` | Properties that cannot be set |
| `CREATEONLY` | Properties only settable on create |
| `UNSELECTABLE` | Properties that cannot be explicitly selected (see OVERRIDES.md#override-013) |
| `INCLUDE_TYPES` | Available relationships to include |
| `WHERE_OPERATIONS` | Restricted operators for specific properties |

**Example:** To see Project capabilities, read `src/Entity/Resource/Project.php`

---

## 12. Configuration

### Config File Location

Create `paymoapi.config.json` in your project root:

```json
{
  "connection": {
    "url": "https://app.paymoapp.com/api/",
    "timeout": 15.0
  },
  "enabled": {
    "cache": true,
    "logging": false
  },
  "path": {
    "cache": "/path/to/cache",
    "logs": "/path/to/logs"
  }
}
```

### Enable Caching

```php
use Jcolombo\PaymoApiPhp\Cache\Cache;

// Set cache lifespan (seconds)
Cache::lifespan(600); // 10 minutes

// Skip cache for specific request
$project = Project::new()->fetch(12345, [], ['skipCache' => true]);
```

---

## 13. Finding More Information

### Package Files

| Need | Look Here |
|------|-----------|
| Property types for a resource | `src/Entity/Resource/{Resource}.php` - `PROP_TYPES` |
| Required fields for create | `src/Entity/Resource/{Resource}.php` - `REQUIRED_CREATE` |
| Available includes | `src/Entity/Resource/{Resource}.php` - `INCLUDE_TYPES` |
| Entity key mappings | `default.paymoapi.config.json` |
| Base resource methods | `src/Entity/AbstractResource.php` |
| Collection methods | `src/Entity/AbstractCollection.php` |
| WHERE/HAS condition details | `src/Utility/RequestCondition.php` |

### Official Paymo API Documentation

Each resource has a corresponding documentation page:

```
https://github.com/paymoapp/api/blob/master/sections/{resource}.md
```

Examples:
- Projects: `https://github.com/paymoapp/api/blob/master/sections/projects.md`
- Tasks: `https://github.com/paymoapp/api/blob/master/sections/tasks.md`
- Entries: `https://github.com/paymoapp/api/blob/master/sections/entries.md`

### Package Documentation Files

| File | Purpose |
|------|---------|
| `README.md` | User-facing documentation |
| `PACKAGE-DEV.md` | Internal development guide |
| `OVERRIDES.md` | API deviation documentation and undocumented features |
| `CHANGELOG.md` | Version history |

---

## 14. Quick Recipes

### Recipe: List Active Projects with Client Info

```php
$projects = Project::list()->fetch(
    ['id', 'name', 'client'],
    [Project::where('active', true)]
);

foreach ($projects as $project) {
    echo "{$project->name} - Client: {$project->client->name}\n";
}
```

### Recipe: Find Tasks Due This Week

```php
$startOfWeek = date('Y-m-d');
$endOfWeek = date('Y-m-d', strtotime('+7 days'));

$tasks = Task::list()->fetch(
    ['id', 'name', 'due_date', 'project'],
    [
        Task::where('complete', false),
        Task::where('due_date', $startOfWeek, '>='),
        Task::where('due_date', $endOfWeek, '<=')
    ]
);
```

### Recipe: Get Time Entries for Date Range

```php
$entries = TimeEntry::list()->fetch(
    ['id', 'duration', 'user_id', 'task', 'project'],
    [
        TimeEntry::where('project_id', $projectId),
        TimeEntry::where('date', '2024-12-01', '>='),
        TimeEntry::where('date', '2024-12-31', '<=')
    ]
);

$totalHours = 0;
foreach ($entries as $entry) {
    $totalHours += $entry->duration / 3600;
}
```

### Recipe: Create Project with Tasks

```php
// Create project
$project = Project::new()->set([
    'name' => 'New Website',
    'client_id' => $clientId
])->create();

// Create tasklist
$tasklist = Tasklist::new()->set([
    'name' => 'Phase 1',
    'project_id' => $project->id
])->create();

// Create tasks
$task = Task::new()->set([
    'name' => 'Design mockups',
    'tasklist_id' => $tasklist->id,
    'project_id' => $project->id
])->create();
```

---

## Important Notes for AI Assistants

### Connection Awareness

1. **Verify connection exists before writing connection code** - Check if the application has a bootstrap, loader, or wrapper that already establishes the connection
2. **Connections are singletons per API key** - Calling `Paymo::connect()` with the same key returns the existing connection, not a new one
3. **Settings persist on the connection** - `useCache`, `useLogging`, etc. remain in effect until explicitly changed
4. **Multiple API keys = multiple independent connections** - Each maintains its own settings

### When Working with Multiple API Keys

5. **Default connection is the first one established** - Resources without explicit connection use this
6. **Pass connection explicitly when switching accounts** - `new Project($connection)` or `Project::new($connection)`
7. **Each connection has independent settings** - Changing cache on one doesn't affect others

### Resource Operations

8. **Check REQUIRED_CREATE** before creating resources to know required fields
9. **Check PROP_TYPES** to know valid properties for a resource
10. **Use includes efficiently** - only include what you need to reduce API calls
11. **The SDK has rate limiting built in** (200ms minimum delay between requests, configurable via `rateLimit.minDelayMs` in config)
12. **Dirty tracking** means only modified fields are sent on update()
13. **Collections are iterable** - use foreach to process results
14. **flatten()** converts resources to plain stdClass objects
15. **Collections are JSON-serializable** - can be directly assigned to response data
16. **Collections are countable** - use `count($collection)` directly
17. **Pagination is supported** - use `limit()` on collections (UNDOCUMENTED API FEATURE - see OVERRIDES.md#override-003)

### UNSELECTABLE Properties

18. **Some properties are UNSELECTABLE** — they appear in API responses but cannot be explicitly requested via field selection. Attempting to select them returns HTTP 400. Check each resource's `UNSELECTABLE` constant. Affected resources: Client (4 properties), User (20 properties), Task (1), Milestone (1), Expense (3), File (3). See `OVERRIDES.md#override-013` for the full list.

### Filter-Only Properties

19. **Four properties are valid in WHERE clauses but not returned in responses:** `Booking.project_id`, `Booking.task_id`, `Booking.date_interval`, `TimeEntry.time_interval`. These are in PROP_TYPES and READONLY but exist only as filter parameters.

### Response Key Anomalies

20. **Some resources use non-standard response keys** — ProjectTemplate, ProjectTemplateTask, ProjectTemplateTasklist, RecurringProfile, and the gallery resources have response keys that don't follow the standard convention. The SDK handles this automatically. See `OVERRIDES.md#override-009` and `OVERRIDES.md#override-010`.

### Collection Parent Filter Requirements

21. **Some collections require parent filters when listing** — `File` requires `project_id`, `Booking` requires a date range OR a user/task/project ID, `InvoiceItem` requires `invoice_id`, `EstimateItem` requires `estimate_id`. See `OVERRIDES.md#override-005`.

### Settings State

22. **Connection settings are mutable at any time** - Changes take effect on next operation
23. **Use `skipCache` option for one-off bypasses** - Doesn't modify connection state
24. **No automatic settings reset** - If you change a setting, change it back when done if needed

---

*This file is for AI assistants helping developers use the Paymo API PHP SDK.*
