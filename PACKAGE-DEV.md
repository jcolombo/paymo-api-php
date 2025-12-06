# Paymo API PHP SDK - Package Development Guide

**Internal Development Documentation**
Version: 0.5.6+
Last Updated: December 2025

---

## Table of Contents

1. [Quick Reference](#1-quick-reference)
2. [Official API Documentation](#2-official-api-documentation)
3. [Package Architecture Overview](#3-package-architecture-overview)
4. [Resource Class Structure](#4-resource-class-structure)
5. [Required Constants Reference](#5-required-constants-reference)
6. [Property Type System](#6-property-type-system)
7. [EntityMap Configuration](#7-entitymap-configuration)
8. [Collection Classes](#8-collection-classes)
9. [Resource-Specific Behaviors](#9-resource-specific-behaviors)
10. [TypeScript Definitions](#10-typescript-definitions)
11. [Development Checklists](#11-development-checklists)
12. [Testing Requirements](#12-testing-requirements)
13. [Common Mistakes & Pitfalls](#13-common-mistakes--pitfalls)
14. [File Templates](#14-file-templates)

---

## 1. Quick Reference

### Critical Rule

**NEVER modify or create ANY resource without first consulting the official Paymo API documentation for that specific resource.**

### Official API Repository

```
https://github.com/paymoapp/api
```

### Resource Documentation URLs

Each resource has its own documentation page:

| Resource | Official API Doc URL |
|----------|---------------------|
| Projects | https://github.com/paymoapp/api/blob/master/sections/projects.md |
| Tasks | https://github.com/paymoapp/api/blob/master/sections/tasks.md |
| Clients | https://github.com/paymoapp/api/blob/master/sections/clients.md |
| Users | https://github.com/paymoapp/api/blob/master/sections/users.md |
| Time Entries | https://github.com/paymoapp/api/blob/master/sections/entries.md |
| Invoices | https://github.com/paymoapp/api/blob/master/sections/invoices.md |
| Invoice Items | https://github.com/paymoapp/api/blob/master/sections/invoiceitems.md |
| Invoice Payments | https://github.com/paymoapp/api/blob/master/sections/invoicepayments.md |
| Estimates | https://github.com/paymoapp/api/blob/master/sections/estimates.md |
| Estimate Items | https://github.com/paymoapp/api/blob/master/sections/estimateitems.md |
| Expenses | https://github.com/paymoapp/api/blob/master/sections/expenses.md |
| Reports | https://github.com/paymoapp/api/blob/master/sections/reports.md |
| Tasklists | https://github.com/paymoapp/api/blob/master/sections/tasklists.md |
| Milestones | https://github.com/paymoapp/api/blob/master/sections/milestones.md |
| Bookings | https://github.com/paymoapp/api/blob/master/sections/bookings.md |
| Task Assignments | https://github.com/paymoapp/api/blob/master/sections/users_tasks.md |
| Workflows | https://github.com/paymoapp/api/blob/master/sections/workflows.md |
| Workflow Statuses | https://github.com/paymoapp/api/blob/master/sections/workflow_statuses.md |
| Files | https://github.com/paymoapp/api/blob/master/sections/files.md |
| Comments | https://github.com/paymoapp/api/blob/master/sections/comments.md |
| Discussions | https://github.com/paymoapp/api/blob/master/sections/discussions.md |
| Client Contacts | https://github.com/paymoapp/api/blob/master/sections/client_contacts.md |
| Company | https://github.com/paymoapp/api/blob/master/sections/company.md |
| Project Templates | https://github.com/paymoapp/api/blob/master/sections/project_templates.md |
| Project Statuses | https://github.com/paymoapp/api/blob/master/sections/project_statuses.md |
| Invoice Templates | https://github.com/paymoapp/api/blob/master/sections/invoice_templates.md |
| Estimate Templates | https://github.com/paymoapp/api/blob/master/sections/estimate_templates.md |
| Sessions | https://github.com/paymoapp/api/blob/master/sections/sessions.md |
| Webhooks | https://github.com/paymoapp/api/blob/master/sections/hooks.md |
| Subtasks | https://github.com/paymoapp/api/blob/master/sections/subtasks.md |
| Recurring Profiles | https://github.com/paymoapp/api/blob/master/sections/recurring_profiles.md |

---

## 2. Official API Documentation

### Before ANY Development Work

1. **ALWAYS** fetch the latest version of the official API docs
2. **ALWAYS** read the specific resource page for the entity you're working on
3. **ALWAYS** compare our implementation against the official docs
4. **NEVER** assume our implementation is correct without verification
5. **NEVER** add properties, includes, or behaviors not documented in the official API

### API Documentation Structure

Each resource page in the official docs contains:

```markdown
# Resource Name

[Description]

## Listing [resources]
GET /api/[resources]

## Getting a [resource]
GET /api/[resources]/[id]

## Creating a [resource]
POST /api/[resources]
Required fields: [list]
[example JSON]

## Updating a [resource]
PUT /api/[resources]/[id]

## Deleting a [resource]
DELETE /api/[resources]/[id]

## The [resource] object
[table of all fields with types and descriptions]

## Include related resources
include=[list of includable relations]
```

### Extracting Information from Official Docs

When reading a resource page, extract:

1. **Endpoint Path** → `API_PATH` constant
2. **Field Table** → `PROP_TYPES` constant
3. **Required Fields for Create** → `REQUIRED_CREATE` constant
4. **Read-only Fields** → `READONLY` constant
5. **Include Relations** → `INCLUDE_TYPES` constant
6. **WHERE Restrictions** (if documented) → `WHERE_OPERATIONS` constant

---

## 3. Package Architecture Overview

### Directory Structure

```
paymo-api-php/
├── src/
│   ├── .resources/                   # Supplemental development resources
│   │   └── typescript.data-types.ts  # TypeScript interface definitions
│   ├── Entity/
│   │   ├── AbstractEntity.php        # Base class for all entities
│   │   ├── AbstractResource.php      # Base class for single resources
│   │   ├── AbstractCollection.php    # Base class for resource lists
│   │   ├── EntityMap.php             # Entity key → Class name registry
│   │   ├── Resource/                 # Individual resource classes
│   │   │   ├── Project.php
│   │   │   ├── Task.php
│   │   │   ├── Client.php
│   │   │   └── ...
│   │   └── Collection/               # Specialized collection classes
│   │       ├── EntityCollection.php  # Default collection
│   │       ├── TaskAssignmentCollection.php
│   │       └── ...
│   ├── Utility/
│   │   ├── RequestCondition.php      # WHERE/HAS condition builders
│   │   ├── Color.php                 # Color utility
│   │   └── ...
│   ├── Paymo.php                     # Main connection class
│   ├── Request.php                   # HTTP request handler
│   ├── Configuration.php             # Config management
│   └── Cache.php                     # Caching system
├── default.paymoapi.config.json      # Default configuration
├── TODO-LIST.md                      # Missing features tracker
├── PACKAGE-DEV.md                    # This file (development guide)
└── composer.json                     # Package dependencies
```

### Class Inheritance Chain

```
AbstractEntity
    ├── AbstractResource (single entity operations)
    │   ├── Project
    │   ├── Task
    │   ├── Client
    │   └── ...
    └── AbstractCollection (list operations)
        ├── EntityCollection (default)
        ├── TaskAssignmentCollection
        ├── BookingCollection
        └── ...
```

### Key Relationships

1. **EntityMap** maps entity keys to class names
2. **Configuration** stores EntityMap data from JSON
3. **AbstractResource** reads constants from child classes
4. **AbstractCollection** instantiates resources via EntityMap

---

## 4. Resource Class Structure

### Required File Location

```
src/Entity/Resource/{ResourceName}.php
```

### Namespace

```php
namespace Jcolombo\PaymoApiPhp\Entity\Resource;
```

### Class Declaration

```php
class {ResourceName} extends AbstractResource
```

### Required Imports

```php
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;
```

### Required Constants (ALL MUST BE PRESENT)

```php
public const LABEL = '';              // Human-readable name
public const API_ENTITY = '';         // Entity key (lowercase)
public const API_PATH = '';           // API endpoint (usually plural)
public const REQUIRED_CREATE = [];    // Required props for create()
public const READONLY = [];           // Props that cannot be set
public const CREATEONLY = [];         // Props only settable on create
public const INCLUDE_TYPES = [];      // Includable relations
public const PROP_TYPES = [];         // All property definitions
public const WHERE_OPERATIONS = [];   // Restricted operators per prop
```

---

## 5. Required Constants Reference

### LABEL

Human-readable name for error messages.

```php
public const LABEL = 'Project';
public const LABEL = 'Task Assignment';
public const LABEL = 'Invoice Item';
```

**Rules:**
- Use proper casing (Title Case)
- Use spaces for multi-word names
- Match the conceptual name, not the API name

---

### API_ENTITY

The entity key used for EntityMap lookups.

```php
public const API_ENTITY = 'project';
public const API_ENTITY = 'usertask';      // Note: Different from class name
public const API_ENTITY = 'invoiceitem';
```

**Rules:**
- Always lowercase
- No spaces or special characters
- Must match the key in `default.paymoapi.config.json`
- May differ from class name (e.g., TaskAssignment uses 'usertask')

---

### API_PATH

The API endpoint path (appended to base URL).

```php
public const API_PATH = 'projects';
public const API_PATH = 'userstasks';      // Note: Unusual plural
public const API_PATH = 'invoiceitems';
public const API_PATH = 'company';         // Singleton: singular
```

**Rules:**
- Usually the plural form of the entity
- Must match the official API endpoint exactly
- Check official docs for unusual pluralizations
- Singleton resources use singular form

---

### REQUIRED_CREATE

Properties that MUST be provided when creating a new entity.

```php
// Simple required props
public const REQUIRED_CREATE = ['name', 'client_id'];

// OR logic (at least one required)
public const REQUIRED_CREATE = ['content', 'thread_id||task_id||discussion_id||file_id'];

// AND logic (both required together)
public const REQUIRED_CREATE = ['type', 'date_interval||start_date&end_date'];

// XOR logic (exactly one required)
// Use || for "any one of these"
```

**Operators:**
- `||` - OR: At least one of these must be provided
- `|` - Simple OR: Any one of these
- `&` - AND: All of these must be provided together

**Rules:**
- Only include props that the API actually requires
- Verify against official docs "Creating a [resource]" section
- Don't include props with default values

---

### READONLY

Properties that cannot be set by the user.

```php
public const READONLY = [
    'id',
    'created_on',
    'updated_on',
    // Add any computed/server-set fields
];
```

**Common Read-only Props:**
- `id` - Always read-only
- `created_on` - Always read-only
- `updated_on` - Always read-only
- Image URLs (use `upload()` method instead)
- Computed totals, counts, etc.
- Server-generated tokens

**Rules:**
- These props are silently ignored when setting
- They CAN be set during hydration from API response
- Check official docs for which fields are returned but not accepted

---

### CREATEONLY

Properties that can be set during create but not update.

```php
public const CREATEONLY = [
    'file',          // File path for upload
    'project_id',    // Can't move to different project after creation
    'user_id',       // Can't change owner after creation
];
```

**Rules:**
- Props that establish entity relationships
- Props that determine entity placement
- Props that the API doesn't allow in PUT requests

---

### INCLUDE_TYPES

Related entities that can be loaded with `include=` parameter.

```php
public const INCLUDE_TYPES = [
    'client'     => false,  // Single entity (1:1 or N:1)
    'tasks'      => true,   // Collection (1:N)
    'tasklists'  => true,   // Collection (1:N)
    'user'       => false,  // Single entity
];
```

**Format:** `'include_name' => bool`
- `true` = Returns a collection (array of entities)
- `false` = Returns a single entity

**Rules:**
- Key must match the include parameter name from official docs
- Boolean indicates if it's a collection or single entity
- Verify against official docs "Include related resources" section

---

### PROP_TYPES

Complete property definitions with data types.

```php
public const PROP_TYPES = [
    // Standard props
    'id'          => 'integer',
    'created_on'  => 'datetime',
    'updated_on'  => 'datetime',
    'name'        => 'text',
    'description' => 'text',
    'active'      => 'boolean',
    'budget'      => 'decimal',
    'priority'    => 'integer',

    // Resource references (foreign keys)
    'client_id'   => 'resource:client',
    'project_id'  => 'resource:project',
    'user_id'     => 'resource:user',

    // Collection references (for ordering, etc.)
    'task_ids'    => 'collection:task',

    // Enums
    'status'      => 'enum:active|completed|archived',
    'priority'    => 'intEnum:25|50|75|100',

    // Dates
    'due_date'    => 'date',
    'start_time'  => 'datetime',

    // Other
    'config'      => 'object',
    'tags'        => 'array',
    'email'       => 'email',
    'url'         => 'url',
    'content'     => 'html',
];
```

---

## 6. Property Type System

### Available Types

| Type | Description | PHP Type | Example |
|------|-------------|----------|---------|
| `integer` | Whole numbers | `int` | `123` |
| `decimal` | Floating point numbers | `float` | `123.45` |
| `text` | Plain text strings | `string` | `"Hello"` |
| `html` | HTML formatted text | `string` | `"<p>Hello</p>"` |
| `boolean` | True/false values | `bool` | `true` |
| `date` | Date only (Y-m-d) | `string` | `"2024-01-15"` |
| `datetime` | Full datetime | `string` | `"2024-01-15T10:30:00Z"` |
| `email` | Email address | `string` | `"user@example.com"` |
| `url` | URL/web address | `string` | `"https://example.com"` |
| `array` | Generic array | `array` | `["a", "b"]` |
| `object` | Generic object | `stdClass` | `{...}` |

### Reference Types

| Type | Format | Description |
|------|--------|-------------|
| `resource:entity` | `resource:client` | Foreign key to another entity |
| `collection:entity` | `collection:task` | Array of entity IDs |

### Enum Types

| Type | Format | Description |
|------|--------|-------------|
| `enum:val1\|val2` | `enum:active\|paused\|stopped` | String enum |
| `intEnum:1\|2\|3` | `intEnum:25\|50\|75\|100` | Integer enum |

### Complex Types

For nested objects with their own structure:

```php
public const PROP_TYPES = [
    'include' => [
        'days'      => 'boolean',
        'projects'  => 'boolean',
        'tasks'     => 'boolean',
    ],
    'extra' => [
        'display_charts' => 'boolean',
        'rounding_step'  => 'integer',
    ],
];
```

---

## 7. EntityMap Configuration

### Configuration File

```
default.paymoapi.config.json
```

### Entity Entry Format

```json
{
    "classMap": {
        "entity": {
            "entitykey": {
                "type": "resource",
                "resource": "Jcolombo\\PaymoApiPhp\\Entity\\Resource\\ClassName",
                "collectionKey": "entitykeys"
            },
            "entitykeys": {
                "type": "collection",
                "resourceKey": "entitykey",
                "collection": true
            }
        }
    }
}
```

### Entry Types

**Resource Entry:**
```json
"project": {
    "type": "resource",
    "resource": "Jcolombo\\PaymoApiPhp\\Entity\\Resource\\Project",
    "collectionKey": "projects"
}
```

**Collection Entry (default collection):**
```json
"projects": {
    "type": "collection",
    "resourceKey": "project",
    "collection": true
}
```

**Collection Entry (custom collection):**
```json
"bookings": {
    "type": "collection",
    "resourceKey": "booking",
    "collection": "Jcolombo\\PaymoApiPhp\\Entity\\Collection\\BookingCollection"
}
```

### Adding a New Entity

1. Create the resource class in `src/Entity/Resource/`
2. Add BOTH entries to `default.paymoapi.config.json`:
   - Singular (resource) entry
   - Plural (collection) entry
3. The collection entry should reference the singular via `resourceKey`

---

## 8. Collection Classes

### When to Create a Custom Collection

Create a custom collection class when:
- The endpoint requires specific filter parameters
- The collection has special list behavior
- You need custom methods on the collection

### Custom Collection Location

```
src/Entity/Collection/{ResourceName}Collection.php
```

### Custom Collection Template

```php
<?php
namespace Jcolombo\PaymoApiPhp\Entity\Collection;

use Jcolombo\PaymoApiPhp\Entity\AbstractCollection;

class BookingCollection extends AbstractCollection
{
    /**
     * Fetch with required filters for this endpoint.
     */
    public function fetch($fields = [], $where = [], array $options = []) : AbstractCollection
    {
        // Add any required conditions
        // Then call parent
        return parent::fetch($fields, $where, $options);
    }
}
```

### Registering Custom Collection

In `default.paymoapi.config.json`:

```json
"bookings": {
    "type": "collection",
    "resourceKey": "booking",
    "collection": "Jcolombo\\PaymoApiPhp\\Entity\\Collection\\BookingCollection"
}
```

---

## 9. Resource-Specific Behaviors

### Overview

Some Paymo API resources have unique capabilities, requirements, or limitations that go beyond the standard CRUD operations. **ALWAYS check the official documentation for special behaviors.**

### What to Look For in Official Docs

When reviewing a resource page, look for:

1. **Special Endpoints** - Non-standard actions (send, convert, mark_as_*)
2. **Required Query Parameters** - Filters required for listing
3. **Conditional Requirements** - Fields required only in certain situations
4. **Rate Limits** - Resource-specific throttling
5. **Subscription Restrictions** - Features limited by plan level
6. **Bulk Operations** - Batch create/update endpoints
7. **File Uploads** - Special handling for attachments
8. **Computed Fields** - Server-calculated values

### Special Endpoint Actions

Some resources support action endpoints beyond CRUD:

```
POST /api/invoices/{id}/send         - Send invoice email
POST /api/invoices/{id}/remind       - Send payment reminder
POST /api/invoices/{id}/mark_as_sent - Mark as sent
POST /api/invoices/{id}/mark_as_paid - Mark as fully paid

POST /api/estimates/{id}/send        - Send estimate email
POST /api/estimates/{id}/convert     - Convert to invoice
POST /api/estimates/{id}/accept      - Mark as accepted

POST /api/projects/{id}/archive      - Archive project
POST /api/projects/{id}/unarchive    - Restore project
```

**Implementation Pattern:**

```php
class Invoice extends AbstractResource
{
    /**
     * Send this invoice via email.
     *
     * @param array $options Email options (to, cc, message, etc.)
     * @return bool Success status
     */
    public function send(array $options = []) : bool
    {
        // Implement special endpoint call
        $response = Request::action(
            $this->connection,
            static::API_PATH,
            $this->id,
            'send',
            $options
        );
        return $response->success;
    }
}
```

### Required Query Parameters

Some list endpoints REQUIRE specific filters:

```php
// Time Entries require a date filter
// Official docs: "time_interval is required"
GET /api/entries?time_interval=this_month

// Bookings require date range
// Official docs: "start_date and end_date are required"
GET /api/bookings?start_date=2024-01-01&end_date=2024-01-31
```

**Implementation Pattern:**

Create a custom collection that enforces requirements:

```php
class BookingCollection extends AbstractCollection
{
    public function fetch($fields = [], $where = [], array $options = []) : AbstractCollection
    {
        // Verify required parameters exist
        $hasDateRange = false;
        foreach ($where as $condition) {
            if (in_array($condition->prop, ['start_date', 'end_date'])) {
                $hasDateRange = true;
            }
        }

        if (!$hasDateRange) {
            throw new RuntimeException(
                "Booking list requires start_date and end_date WHERE conditions"
            );
        }

        return parent::fetch($fields, $where, $options);
    }
}
```

### Conditional Field Requirements

Some fields are required only in specific situations:

```php
// Comments: Need ONE of these parent references
public const REQUIRED_CREATE = [
    'content',
    'thread_id||task_id||discussion_id||file_id'  // At least one
];

// Time Entries: Need either task_id OR project_id
public const REQUIRED_CREATE = [
    'start_time',
    'task_id||project_id'  // At least one
];

// Reports: Different requirements by type
// Check official docs for type-specific requirements
```

### Resource-Specific Validation Methods

When a resource has complex validation needs:

```php
class Report extends AbstractResource
{
    /**
     * Validate report-specific requirements before create.
     */
    protected function validateForCreate() : array
    {
        $errors = parent::validateForCreate();

        // Reports require specific field combinations based on type
        if ($this->type === 'time') {
            if (empty($this->date_interval) &&
                (empty($this->start_date) || empty($this->end_date))) {
                $errors[] = "Time reports require date_interval OR start_date+end_date";
            }
        }

        return $errors;
    }
}
```

### Singleton Resources

Some resources have only one instance per account:

```php
// Company is a singleton - no list, no create, no delete
public const API_PATH = 'company';

// Usage:
$company = Company::new()->fetch();  // No ID needed
$company->name = 'New Name';
$company->update();  // PUT /api/company (no ID in URL)
```

### Checklist: Identifying Special Behaviors

When reviewing any resource's official docs:

- [ ] Does the docs page mention special endpoints beyond CRUD?
- [ ] Are there required query parameters for listing?
- [ ] Are there conditional field requirements ("if X then Y required")?
- [ ] Are there subscription/plan restrictions?
- [ ] Is this a singleton resource?
- [ ] Are there bulk operation endpoints?
- [ ] Does it support file uploads?
- [ ] Are there computed/virtual fields?
- [ ] Are there rate limit warnings?
- [ ] Are there deprecation notices?

### Document Special Behaviors

When implementing special behaviors, document them:

```php
/**
 * Paymo Invoice resource.
 *
 * SPECIAL BEHAVIORS:
 * - Supports send() action to email invoice
 * - Supports remind() action to send payment reminder
 * - Supports markAsSent() to update status without email
 * - Supports markAsPaid() to record full payment
 * - total_amount is computed server-side from items
 * - pdf_url is generated server-side
 *
 * Official API Documentation:
 * https://github.com/paymoapp/api/blob/master/sections/invoices.md
 */
class Invoice extends AbstractResource
{
    // ...
}
```

---

## 10. TypeScript Definitions

### Purpose

The TypeScript definitions file provides type information for applications that consume the PHP SDK's JSON output via TypeScript/JavaScript frontends.

### File Location

```
src/.resources/typescript.data-types.ts
```

### Maintenance Requirement

**IMPORTANT:** When modifying ANY resource's `PROP_TYPES`, the corresponding TypeScript interface MUST be updated to match.

### Synchronization Rules

1. **Every resource class MUST have a TypeScript interface**
2. **Interface properties MUST match `PROP_TYPES` exactly**
3. **Types MUST be correctly mapped from PHP to TypeScript**

### Type Mapping Reference

| PHP Type (PROP_TYPES) | TypeScript Type |
|-----------------------|-----------------|
| `integer` | `number` |
| `decimal` | `number` |
| `text` | `string` |
| `html` | `string` |
| `boolean` | `boolean` |
| `date` | `string` |
| `datetime` | `string` |
| `email` | `string` |
| `url` | `string` |
| `array` | `any[]` or specific type |
| `object` | `Record<string, any>` or specific type |
| `resource:entity` | `number` (the ID) |
| `collection:entity` | `number[]` (array of IDs) |
| `enum:a\|b\|c` | `'a' \| 'b' \| 'c'` |
| `intEnum:1\|2\|3` | `1 \| 2 \| 3` |

### Interface Naming Convention

```typescript
// Format: Paymo{ResourceName}
export interface PaymoProject { ... }
export interface PaymoTask { ... }
export interface PaymoClient { ... }
export interface PaymoInvoice { ... }
```

### Interface Template

```typescript
/**
 * TypeScript interface for Paymo {ResourceName} entity.
 *
 * Corresponds to: src/Entity/Resource/{ResourceName}.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/{resource}.md
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface Paymo{ResourceName} {
    // Read-only properties
    id: number;
    created_on: string;
    updated_on: string;

    // Required properties
    name: string;

    // Optional properties
    description?: string;

    // Foreign keys (resource references)
    client_id?: number;
    project_id?: number;

    // Collections (when included)
    tasks?: PaymoTask[];

    // Enums
    status?: 'active' | 'paused' | 'completed';
    priority?: 25 | 50 | 75 | 100;
}
```

### Optional vs Required Properties

- Properties in `REQUIRED_CREATE` → Required in interface (no `?`)
- Properties in `READONLY` → Still included (they're returned from API)
- All other properties → Optional (use `?`)

### Include Types in TypeScript

When a resource has `INCLUDE_TYPES`, add optional properties for included data:

```php
// PHP INCLUDE_TYPES
public const INCLUDE_TYPES = [
    'client'    => false,  // Single entity
    'tasks'     => true,   // Collection
    'tasklists' => true,   // Collection
];
```

```typescript
// TypeScript interface
export interface PaymoProject {
    id: number;
    name: string;
    client_id?: number;

    // Included relations (optional - only present when requested)
    client?: PaymoClient;        // Single entity
    tasks?: PaymoTask[];         // Collection (array)
    tasklists?: PaymoTasklist[]; // Collection (array)
}
```

### Updating Process

When modifying a PHP resource:

1. **Update `PROP_TYPES`** in the PHP resource class
2. **Immediately update** `src/.resources/typescript.data-types.ts`
3. **Verify types match** using the mapping table above
4. **Check includes** - update included entity references

### Checklist: TypeScript Sync

- [ ] Does the interface exist for this resource?
- [ ] Do all `PROP_TYPES` properties have corresponding interface properties?
- [ ] Are types correctly mapped (integer→number, text→string, etc.)?
- [ ] Are required properties marked required (no `?`)?
- [ ] Are optional properties marked optional (with `?`)?
- [ ] Are `INCLUDE_TYPES` represented as optional properties?
- [ ] Is the file header documentation URL correct?

### Current Status

The TypeScript definitions file currently only has `PaymoBooking` defined. **All other resources need TypeScript interfaces added.** This is tracked in `TODO-LIST.md`.

### Adding Missing Interfaces

Priority order for adding interfaces:

1. **Core entities** - Project, Task, Client, User
2. **Time tracking** - TimeEntry, Tasklist, Milestone
3. **Financial** - Invoice, InvoiceItem, Estimate, EstimateItem, Expense
4. **Supporting** - Workflow, WorkflowStatus, File, Comment, Discussion
5. **Templates** - ProjectTemplate, InvoiceTemplate, EstimateTemplate
6. **Other** - All remaining entities

---

## 11. Development Checklists

### Pre-Development Checklist

Before making ANY changes to a resource:

- [ ] Have I fetched the latest official API docs?
- [ ] Have I read the specific resource page for this entity?
- [ ] Have I compared our current implementation to the official docs?
- [ ] Have I noted all discrepancies between our code and official docs?
- [ ] Have I identified what the official docs say about:
  - [ ] Endpoint path
  - [ ] All available fields
  - [ ] Required fields for creation
  - [ ] Read-only fields
  - [ ] Includable relationships
  - [ ] WHERE filter restrictions

### New Resource Checklist

When creating a NEW resource class:

- [ ] Official API page URL documented in file header
- [ ] `LABEL` constant matches conceptual name
- [ ] `API_ENTITY` matches EntityMap key (lowercase, no spaces)
- [ ] `API_PATH` matches official API endpoint exactly
- [ ] `REQUIRED_CREATE` matches official "Creating" section
- [ ] `READONLY` includes `id`, `created_on`, `updated_on` minimum
- [ ] `READONLY` includes all server-computed fields
- [ ] `CREATEONLY` includes relationship fields that can't change
- [ ] `INCLUDE_TYPES` matches official "Include related resources"
- [ ] `INCLUDE_TYPES` booleans correct (false=single, true=collection)
- [ ] `PROP_TYPES` includes ALL fields from official docs
- [ ] `PROP_TYPES` uses correct type for each field
- [ ] `WHERE_OPERATIONS` restricts operators where needed
- [ ] EntityMap entries added (both singular and plural)
- [ ] File uses correct namespace
- [ ] Class extends `AbstractResource`
- [ ] File location is `src/Entity/Resource/{Name}.php`
- [ ] TypeScript interface added to `src/.resources/typescript.data-types.ts`
- [ ] Checked for special behaviors (see Section 9)

### Modifying Resource Checklist

When modifying an EXISTING resource:

- [ ] Have I re-verified against current official API docs?
- [ ] Have I checked if the official docs have changed?
- [ ] Am I only adding/changing things that match official docs?
- [ ] Have I tested the change doesn't break existing functionality?
- [ ] Have I updated `TODO-LIST.md` if fixing a known issue?
- [ ] Have I updated TypeScript interface to match changes?
- [ ] Have I checked for any new special behaviors?

### Property Type Checklist

When defining `PROP_TYPES`:

- [ ] Every field from official docs is included
- [ ] Type matches official API data type
- [ ] Foreign keys use `resource:entityname` format
- [ ] Dates use `date` (Y-m-d) or `datetime` (ISO8601)
- [ ] Booleans use `boolean` not `text`
- [ ] Numbers use `integer` or `decimal` appropriately
- [ ] Enums list ALL valid values
- [ ] Undocumented but observed fields are marked with comment

### Include Types Checklist

When defining `INCLUDE_TYPES`:

- [ ] Every include from official docs is present
- [ ] Include names match API parameter names exactly
- [ ] Boolean is `true` for collections (1:N relationships)
- [ ] Boolean is `false` for single entities (N:1 or 1:1)
- [ ] No includes are invented that don't exist in API

---

## 12. Testing Requirements

### Manual Verification

After any resource changes:

1. **Create Test:**
   ```php
   $entity = new ResourceName();
   $entity->requiredProp = 'value';
   $entity->create();
   echo $entity->id; // Should have ID
   ```

2. **Fetch Test:**
   ```php
   $entity = ResourceName::new()->fetch($id);
   echo $entity->someProp; // Should have value
   ```

3. **Update Test:**
   ```php
   $entity = ResourceName::new()->fetch($id);
   $entity->editableProp = 'new value';
   $entity->update();
   ```

4. **Include Test:**
   ```php
   $entity = ResourceName::new()->fetch($id, ['includeName']);
   echo $entity->includeName->someValue; // Should work
   ```

5. **List Test:**
   ```php
   $list = ResourceName::list()->fetch();
   foreach ($list as $entity) {
       echo $entity->id;
   }
   ```

### Verification Against API

When testing, verify:

- [ ] API returns expected fields
- [ ] Our PROP_TYPES matches returned data
- [ ] Includes return correct structure (single vs collection)
- [ ] Create actually creates in Paymo
- [ ] Update actually updates in Paymo
- [ ] Read-only fields cannot be set

---

## 13. Common Mistakes & Pitfalls

### Mistake: Assuming API Behavior

**Wrong:**
```php
// Assuming a field exists because it "makes sense"
'budget_remaining' => 'decimal',  // Not in API!
```

**Right:**
```php
// Only include fields documented in official API
'budget' => 'decimal',  // Verified in official docs
```

### Mistake: Wrong Include Boolean

**Wrong:**
```php
// Tasks is a collection, not a single entity!
'tasks' => false,
```

**Right:**
```php
// Collections use true
'tasks' => true,
// Single entities use false
'client' => false,
```

### Mistake: Wrong Resource Reference

**Wrong:**
```php
// Using class name instead of entity key
'client_id' => 'resource:Client',
```

**Right:**
```php
// Use lowercase entity key
'client_id' => 'resource:client',
```

### Mistake: Missing EntityMap Entry

**Wrong:**
```php
// Only added singular entry
"invoice": { ... }
// Missing plural entry!
```

**Right:**
```php
// Both entries required
"invoice": {
    "type": "resource",
    "resource": "...\\Invoice",
    "collectionKey": "invoices"
},
"invoices": {
    "type": "collection",
    "resourceKey": "invoice",
    "collection": true
}
```

### Mistake: API_PATH vs API_ENTITY

**Wrong:**
```php
// Using plural for entity key
public const API_ENTITY = 'invoices';
```

**Right:**
```php
// Entity key is singular
public const API_ENTITY = 'invoice';
// Path is plural
public const API_PATH = 'invoices';
```

### Mistake: Typos in Constants

**Wrong:**
```php
// Typo will cause silent failures
'miletstone' => false,  // Should be 'milestone'
'interger',              // Should be 'integer'
```

**Right:**
```php
// Spell check all constants!
'milestone' => false,
'integer',
```

### Mistake: Not Marking Undocumented Fields

**Wrong:**
```php
'some_field' => 'text',  // Where did this come from?
```

**Right:**
```php
// Undocumented Props
'some_field' => 'text',  // Observed in API response but not in docs
```

---

## 14. File Templates

### New Resource Template

```php
<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 *
 * ======================================================================================
 * {RESOURCE_NAME} RESOURCE - {BRIEF_DESCRIPTION}
 * ======================================================================================
 *
 * Official API Documentation:
 * https://github.com/paymoapp/api/blob/master/sections/{api_page}.md
 *
 * [Detailed description of what this resource represents]
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo {ResourceName} resource.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 */
class {ResourceName} extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     */
    public const LABEL = '{Resource Name}';

    /**
     * Entity key for internal references and EntityMap lookups.
     */
    public const API_ENTITY = '{entitykey}';

    /**
     * API endpoint path appended to base URL.
     */
    public const API_PATH = '{entitykeys}';

    /**
     * Properties required when creating a new entity.
     */
    public const REQUIRED_CREATE = [];

    /**
     * Properties that cannot be modified via API.
     */
    public const READONLY = ['id', 'created_on', 'updated_on'];

    /**
     * Properties that can be set during creation but not updated.
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     */
    public const INCLUDE_TYPES = [];

    /**
     * Property type definitions for validation and hydration.
     */
    public const PROP_TYPES = [
        'id'         => 'integer',
        'created_on' => 'datetime',
        'updated_on' => 'datetime',
        // Add all properties from official API docs
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     */
    public const WHERE_OPERATIONS = [];
}
```

### EntityMap JSON Template

```json
"{entitykey}": {
    "type": "resource",
    "resource": "Jcolombo\\PaymoApiPhp\\Entity\\Resource\\{ResourceName}",
    "collectionKey": "{entitykeys}"
},
"{entitykeys}": {
    "type": "collection",
    "resourceKey": "{entitykey}",
    "collection": true
}
```

---

## Final Notes

### Golden Rules

1. **The official API docs are the source of truth**
2. **Never add what isn't documented**
3. **Never assume - verify**
4. **Mark undocumented observations with comments**
5. **Test against live API before committing**

### When In Doubt

1. Read the official API docs again
2. Test against the live API
3. Check the TODO-LIST.md for known issues
4. Compare with similar existing resources in the package

### Keeping Up To Date

- Periodically check official API for changes
- Update TODO-LIST.md when new features appear
- Run verification tests after Paymo updates

---

*This document is for internal package development only. For end-user documentation, see README.md.*
