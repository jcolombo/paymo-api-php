# Paymo API for PHP

A robust, object-oriented PHP SDK for the [Paymo](https://www.paymoapp.com) project management API.

[![Latest Version](https://img.shields.io/packagist/v/jcolombo/paymo-api-php.svg)](https://packagist.org/packages/jcolombo/paymo-api-php)
[![PHP Version](https://img.shields.io/packagist/php-v/jcolombo/paymo-api-php.svg)](https://packagist.org/packages/jcolombo/paymo-api-php)
[![License](https://img.shields.io/github/license/jcolombo/paymo-api-php)](LICENSE)
[![GitHub Issues](https://img.shields.io/github/issues/jcolombo/paymo-api-php)](https://github.com/jcolombo/paymo-api-php/issues)
[![GitHub Stars](https://img.shields.io/github/stars/jcolombo/paymo-api-php)](https://github.com/jcolombo/paymo-api-php/stargazers)

---

## Overview

This independently developed package provides a developer-friendly toolkit to simplify all interactions with the Paymo REST API. It is not affiliated with or endorsed by Paymo.

**Official Paymo API Documentation:** https://github.com/paymoapp/api

---

## Features

- **Full CRUD Operations** - Create, Read, Update, and Delete for all 38 Paymo resource types
- **Fluent Interface** - Chainable methods for clean, readable code
- **Smart Query Building** - WHERE filters, HAS relationship conditions, and INCLUDE for eager loading
- **JSON-Ready Collections** - Collections are directly JSON-serializable for API responses
- **Response Caching** - Built-in file-based caching to reduce API calls and avoid rate limits
- **Request Logging** - Comprehensive logging for debugging and monitoring
- **Type Safety** - Property type validation for each resource type
- **Relationship Support** - Load related entities in a single call
- **File Uploads** - Easy image and file attachment handling

---

## Requirements

- PHP 7.4 or higher
- An active [Paymo](https://www.paymoapp.com) account
- A Paymo API key (found in your Paymo account settings)
- Composer

---

## Installation

```bash
composer require jcolombo/paymo-api-php
```

---

## Quick Start

### Establishing a Connection

```php
use Jcolombo\PaymoApiPhp\Paymo;
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;

// Connect with your API key
$paymo = Paymo::connect('YOUR_API_KEY');

// Alternative: Username/password authentication (not recommended for production)
$paymo = Paymo::connect(['username', 'password']);
```

### Fetching a Single Resource

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;

// Fetch a project by ID
$project = Project::new()->fetch(12345);

// Access properties directly
echo $project->name;
echo $project->description;

// Fetch with related entities included
$project = Project::new()->fetch(12345, ['client', 'tasklists', 'tasks']);
echo $project->client->name; // Access the related client
```

### Fetching Collections (Lists)

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\Resource\Task;

// Get all projects
$projects = Project::list()->fetch();

foreach ($projects as $project) {
    echo $project->name . "\n";
}

// Get count directly
echo "Total projects: " . count($projects);

// JSON encode directly for API responses
$json = json_encode($projects);  // Returns array of flattened objects

// Get all tasks with filters
$tasks = Task::list()
    ->where(Task::where('complete', false))
    ->fetch();
```

### Creating Resources

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\Resource\Task;

// Create a new project
$project = new Project();
$project->name = "My New Project";
$project->description = "Project description here";
$project->create();

// Fluent style creation
$project = Project::new()
    ->set(['name' => 'Another Project', 'description' => 'Created with chaining'])
    ->create();

// Create a task (requires tasklist_id or project_id)
$task = Task::new()
    ->set([
        'name' => 'My First Task',
        'tasklist_id' => 123,
        'description' => 'Task details here'
    ])
    ->create();
```

### Updating Resources

```php
// Fetch, modify, and update
$project = Project::new()->fetch(12345);
$project->name = "Updated Project Name";
$project->description = "New description";
$project->update();

// Only dirty (changed) fields are sent to the API
```

### Deleting Resources

```php
// Delete via instance
$project = Project::new()->fetch(12345);
$project->delete();

// Delete by ID directly
Project::deleteById(12345);
```

---

## Supported Resources

The SDK supports all 38 Paymo API resource types:

| Category | Resources |
|----------|-----------|
| **Projects & Tasks** | `Project`, `ProjectStatus`, `ProjectTemplate`, `Tasklist`, `Task`, `Subtask`, `TaskAssignment` |
| **Time Tracking** | `TimeEntry`, `Booking` |
| **Financial** | `Invoice`, `InvoiceItem`, `InvoicePayment`, `InvoiceTemplate`, `Estimate`, `EstimateItem`, `EstimateTemplate`, `Expense` |
| **Recurring** | `RecurringProfile`, `RecurringProfileItem`, `TaskRecurringProfile` |
| **Users & Clients** | `User`, `Client`, `ClientContact`, `Company` |
| **Collaboration** | `Discussion`, `Comment`, `CommentThread`, `Milestone` |
| **Workflows** | `Workflow`, `WorkflowStatus` |
| **Files & Sessions** | `File`, `Session` |
| **Reports** | `Report` |
| **Templates** | `ProjectTemplateTask`, `ProjectTemplateTasklist`, `EstimateTemplateGallery`, `InvoiceTemplateGallery` |
| **Integrations** | `Webhook` |

---

## Query Building

### WHERE Filters

Filter collections using the static `where()` method on any resource class:

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
use Jcolombo\PaymoApiPhp\Entity\Resource\Task;

// Simple equality
$projects = Project::list()
    ->where(Project::where('active', true))
    ->fetch();

// With operators
$tasks = Task::list()
    ->where(Task::where('complete', false, '='))
    ->where(Task::where('due_date', '2024-12-31', '<='))
    ->fetch();

// Using IN operator
$projects = Project::list()
    ->where(Project::where('users', [1, 2, 3], 'in'))
    ->fetch();
```

### HAS Relationship Filters

Filter by the existence of related entities:

```php
// Projects that have at least one task
$projects = Project::list()
    ->where(Project::has('tasks', 0, '>'))
    ->fetch();

// Projects with more than 5 milestones
$projects = Project::list()
    ->where(Project::has('milestones', 5, '>'))
    ->fetch();
```

### Including Related Entities

Eager-load related entities in a single API call:

```php
// Include single relations
$project = Project::new()->fetch(12345, ['client']);

// Include multiple relations
$project = Project::new()->fetch(12345, ['client', 'tasklists', 'tasks', 'milestones']);

// Access included relations
echo $project->client->name;
foreach ($project->tasks as $task) {
    echo $task->name;
}
```

---

## Configuration

### Configuration File

Create a `paymoapi.config.json` file in your project root to customize behavior:

```json
{
  "connection": {
    "url": "https://app.paymoapp.com/api/",
    "defaultName": "PaymoApi",
    "verify": false,
    "timeout": 15.0
  },
  "path": {
    "cache": "/path/to/cache/directory",
    "logs": "/path/to/logs/directory"
  },
  "enabled": {
    "cache": true,
    "logging": true
  },
  "log": {
    "connections": false,
    "requests": true
  },
  "devMode": false
}
```

### Configuration Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `connection.url` | string | `https://app.paymoapp.com/api/` | Paymo API base URL |
| `connection.timeout` | float | `15.0` | Request timeout in seconds |
| `connection.verify` | bool | `false` | SSL certificate verification |
| `enabled.cache` | bool | `false` | Enable response caching |
| `enabled.logging` | bool | `false` | Enable request/response logging |
| `log.connections` | bool | `false` | Log connection events |
| `log.requests` | bool | `true` | Log API requests |
| `devMode` | bool | `false` | Enable development mode validations |

---

## Caching

The SDK includes a built-in caching system to reduce API calls and help avoid rate limits.

### Enable Caching

```json
{
  "enabled": {
    "cache": true
  },
  "path": {
    "cache": "/path/to/cache/directory"
  }
}
```

Or define the cache path via constant before loading the SDK:

```php
define('PAYMOAPI_REQUEST_CACHE_PATH', '/path/to/cache');
```

### Cache Control

```php
use Jcolombo\PaymoApiPhp\Cache\Cache;

// Set cache lifespan (default: 300 seconds / 5 minutes)
Cache::lifespan(600); // 10 minutes

// Skip cache for a specific request
$project = Project::new()->fetch(12345, [], ['skipCache' => true]);

// Ignore cache on an entity
$project = Project::new()->ignoreCache(true)->fetch(12345);

// Custom cache handlers
Cache::registerCacheMethods(
    function($key, $lifespan) { /* fetch logic */ },
    function($key, $data, $lifespan) { /* store logic */ }
);
```

---

## File Uploads

### Uploading Images

```php
// Upload an image to an existing entity (like a client logo)
$client = Client::new()->fetch(123);
$client->image('/path/to/logo.png');

// Specify the property key if needed
$user = User::new()->fetch(456);
$user->image('/path/to/avatar.jpg', 'image');
```

### Uploading Files

```php
// Attach a file to an entity
$task = Task::new()->fetch(789);
$task->file('/path/to/document.pdf');
```

---

## Working with Properties

### Getting and Setting

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

The SDK tracks which properties have been modified since the last save/load:

```php
$project = Project::new()->fetch(12345);
$project->name = "Changed Name";

// Check if any properties are dirty
if ($project->isDirty()) {
    $project->update(); // Only sends changed fields
}

// Get list of dirty property keys
$dirtyKeys = $project->getDirtyKeys(); // ['name']

// Get dirty values with original and current
$dirtyValues = $project->getDirtyValues();
// ['name' => ['original' => 'Old Name', 'current' => 'Changed Name']]
```

### Flattening to stdClass

Export entity data as a plain PHP object:

```php
$project = Project::new()->fetch(12345, ['client', 'tasks']);

// Get as stdClass (includes relations)
$data = $project->flatten();

// Strip null values
$data = $project->flatten(['stripNull' => true]);
```

### JSON Serialization

Collections can be directly JSON-encoded for API responses:

```php
$projects = Project::list()->fetch(['id', 'name', 'active']);

// Direct JSON encoding - collections implement JsonSerializable
echo json_encode($projects);
// Output: [{"id": 123, "name": "Project A", "active": true}, ...]

// Assign directly to response data structures
$response->projects = $projects;  // Auto-serializes correctly
```

---

## Error Handling

The SDK throws exceptions for common error scenarios:

```php
use Jcolombo\PaymoApiPhp\Entity\Resource\Project;

try {
    // Missing required field
    $project = Project::new()->create(); // Throws: requires 'name'

    // Fetch without ID
    $project = Project::new()->fetch(); // Throws: requires ID

    // Update without ID
    $project = Project::new();
    $project->name = "Test";
    $project->update(); // Throws: requires ID

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Protecting Dirty Data

Prevent accidental overwrites of unsaved changes:

```php
$project = Project::new()->fetch(12345);
$project->name = "Unsaved change";
$project->protectDirtyOverwrites(true);

// This will throw an exception because there are dirty fields
$project->fetch(12345); // Throws exception
```

---

## Rate Limiting

Paymo enforces API rate limits. The SDK includes a built-in 1-second delay between requests to help prevent hitting rate limits. For high-volume operations:

1. **Enable caching** to reduce redundant API calls
2. **Use `skipCache` sparingly** - only when you need fresh data
3. **Batch operations** where possible by including related entities

---

## Advanced Usage

### Multiple Connections

```php
// Connect to multiple Paymo accounts
$connection1 = Paymo::connect('API_KEY_1', null, 'Account1');
$connection2 = Paymo::connect('API_KEY_2', null, 'Account2');

// Use specific connection for entities
$project = new Project($connection1);
$project->fetch(12345);
```

### Resource Property Types

Each resource defines its property types for validation:

```php
// Property types include:
// - 'text', 'integer', 'decimal', 'boolean'
// - 'date', 'datetime'
// - 'resource:entityname' - foreign key reference
// - 'collection:entityname' - array of related entities
// - 'intEnum:25|50|75|100' - enumerated integer values
```

### Read-Only and Create-Only Properties

```php
// READONLY properties (like 'id', 'created_on') cannot be set
// CREATEONLY properties can only be set during create(), not update()

$task = Task::new();
$task->project_id = 123;  // CREATEONLY - can set before create()
$task->name = "My Task";
$task->create();

$task->project_id = 456;  // Ignored - cannot change after creation
$task->update();
```

---

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## Credits

Developed and maintained by [Joel Colombo](mailto:jc-dev@360psg.com) at [360 PSG, Inc.](https://360psg.com)

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed history of changes.
