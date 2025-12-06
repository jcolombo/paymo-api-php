# Paymo API PHP SDK - TODO List

**Comprehensive Analysis vs Official Paymo API Documentation**
Source: https://github.com/paymoapp/api
Last Updated: December 2025

---

## Table of Contents

1. [Missing Resources (Not Implemented)](#1-missing-resources-not-implemented)
2. [Project Resource](#2-project-resource)
3. [Task Resource](#3-task-resource)
4. [TimeEntry Resource](#4-timeentry-resource)
5. [Client Resource](#5-client-resource)
6. [Invoice Resource](#6-invoice-resource)
7. [InvoiceItem Resource](#7-invoiceitem-resource)
8. [Estimate Resource](#8-estimate-resource)
9. [EstimateItem Resource](#9-estimateitem-resource)
10. [User Resource](#10-user-resource)
11. [Expense Resource](#11-expense-resource)
12. [Tasklist Resource](#12-tasklist-resource)
13. [Milestone Resource](#13-milestone-resource)
14. [Booking Resource](#14-booking-resource)
15. [TaskAssignment Resource](#15-taskassignment-resource)
16. [Workflow Resource](#16-workflow-resource)
17. [WorkflowStatus Resource](#17-workflowstatus-resource)
18. [File Resource](#18-file-resource)
19. [Comment Resource](#19-comment-resource)
20. [Discussion Resource](#20-discussion-resource)
21. [ClientContact Resource](#21-clientcontact-resource)
22. [Report Resource](#22-report-resource)
23. [Company Resource](#23-company-resource)
24. [Templates](#24-templates)
25. [Missing WHERE/Filter Operations](#25-missing-wherefilter-operations)
26. [Missing Include Relations](#26-missing-include-relations)
27. [Utility/Helper Features](#27-utilityhelper-features)
28. [Architecture Improvements](#28-architecture-improvements)

---

## 1. Missing Resources (Not Implemented)

### HIGH PRIORITY - Completely Missing Entities

#### 1.1 Subtask Resource
**API Endpoint:** `subtasks`
**Status:** NOT IMPLEMENTED
**Priority:** Medium

The Paymo API supports subtasks (checklist items within tasks). Our package has no `Subtask` resource.

**Required Properties:**
- `id` (integer, read-only)
- `name` (text, required)
- `task_id` (resource:task, required)
- `complete` (boolean)
- `seq` (integer) - order within task
- `due_date` (date)
- `user_id` (resource:user) - assigned user
- `created_on` (datetime, read-only)
- `updated_on` (datetime, read-only)

**Required Includes:**
- `task` (false) - parent task
- `user` (false) - assigned user

---

#### 1.2 Invoice Recurring Profile Resource
**API Endpoint:** `recurringprofiles`
**Status:** NOT IMPLEMENTED
**Priority:** High

Recurring invoice profiles for automated billing. Referenced in Client includes but no resource exists.

**Required Properties:**
- `id` (integer, read-only)
- `name` (text)
- `client_id` (resource:client, required)
- `template_id` (resource:invoicetemplate)
- `currency` (text, required)
- `status` (enum: active|paused|stopped)
- `start_date` (date, required)
- `end_date` (date)
- `occurrences` (integer) - how many invoices to create
- `occurrences_count` (integer, read-only) - created so far
- `frequency` (enum: weekly|biweekly|monthly|bimonthly|quarterly|biannually|annually)
- `bill_to` (text)
- `company_info` (text)
- `footer` (text)
- `notes` (text)
- `tax` (decimal)
- `tax_text` (text)
- `tax2` (decimal)
- `tax2_text` (text)
- `discount` (decimal)
- `discount_text` (text)
- `tax_on_tax` (boolean)
- `next_date` (date, read-only) - next invoice date
- `items` (array) - line items template
- `created_on` (datetime, read-only)
- `updated_on` (datetime, read-only)

**Required Includes:**
- `client` (false)
- `invoicetemplate` (false)
- `invoices` (true) - generated invoices

---

#### 1.3 Task Recurring Profile Resource
**API Endpoint:** `taskrecurringprofiles`
**Status:** NOT IMPLEMENTED
**Priority:** Medium

Referenced in Task resource (`recurring_profile_id`) but no resource exists.

**Required Properties:**
- `id` (integer, read-only)
- `name` (text)
- `project_id` (resource:project, required)
- `tasklist_id` (resource:tasklist)
- `status` (enum: active|paused|stopped)
- `start_date` (date, required)
- `end_date` (date)
- `frequency` (enum: daily|weekly|biweekly|monthly|bimonthly|quarterly|annually)
- `task_name` (text, required) - template for created tasks
- `task_description` (text)
- `task_users` (collection:users)
- `task_billable` (boolean)
- `task_budget_hours` (decimal)
- `task_priority` (intEnum: 25|50|75|100)
- `created_on` (datetime, read-only)
- `updated_on` (datetime, read-only)

---

#### 1.4 Webhook/Hook Resource
**API Endpoint:** `hooks`
**Status:** NOT IMPLEMENTED
**Priority:** Medium

For receiving real-time notifications of Paymo events.

**Required Properties:**
- `id` (integer, read-only)
- `target_url` (url, required) - webhook endpoint
- `event` (text, required) - event type (e.g., 'model.insert.Task')
- `where` (object) - filter conditions
- `created_on` (datetime, read-only)

**Available Events:**
- `model.insert.{Entity}` - when entity is created
- `model.update.{Entity}` - when entity is updated
- `model.delete.{Entity}` - when entity is deleted
- `timer.start` - when timer starts
- `timer.stop` - when timer stops

---

### MEDIUM PRIORITY - Partially Implemented

#### 1.5 Session Resource
**API Endpoint:** `sessions`
**Status:** EXISTS BUT MINIMAL
**File:** `src/Entity/Resource/Session.php`

The Session resource exists but needs review for completeness.

**Verify Properties Match API:**
- `id` (integer)
- `ip` (text)
- `browser` (text)
- `os` (text)
- `expires_on` (datetime)
- `created_on` (datetime)

---

## 2. Project Resource

**File:** `src/Entity/Resource/Project.php`
**Status:** Mostly Complete

### 2.1 Missing Properties

| Property | Type | Notes |
|----------|------|-------|
| `start_date` | date | Project start date - NOT IN PROP_TYPES |
| `end_date` | date | Project end date - NOT IN PROP_TYPES |
| `budget` | decimal | Money budget (not just hours) - NOT IN PROP_TYPES |
| `budget_value` | decimal | Budget value - NOT IN PROP_TYPES |
| `progress` | integer | Project completion percentage (0-100) - NOT IN PROP_TYPES |
| `completed_on` | datetime | When project was completed - NOT IN PROP_TYPES |

### 2.2 Incorrect Type Mappings

| Property | Current | Should Be |
|----------|---------|-----------|
| `workflow_id` | `resource:milestone` | `resource:workflow` |

### 2.3 Missing Includes

| Include | Is Collection | Notes |
|---------|---------------|-------|
| `entries` | true | Time entries for project |
| `expenses` | true | Expenses for project |
| `bookings` | true | Resource bookings |
| `comments` | true | Project comments |

### 2.4 Missing WHERE Operations

```php
// Add to WHERE_OPERATIONS
'client_id' => ['=', '!=', 'in', 'not in'],
'status_id' => ['=', '!=', 'in', 'not in'],
'workflow_id' => ['=', '!='],
'created_on' => ['=', '!=', '>', '<', '>=', '<='],
'updated_on' => ['=', '!=', '>', '<', '>=', '<='],
```

---

## 3. Task Resource

**File:** `src/Entity/Resource/Task.php`
**Status:** Good, Minor Gaps

### 3.1 Missing Properties

| Property | Type | Notes |
|----------|------|-------|
| `time_estimate` | integer | Estimated time in seconds |
| `progress` | integer | Task completion percentage (0-100) |
| `subtasks_complete` | integer | Count of completed subtasks (read-only) |
| `subtasks_total` | integer | Total subtasks count (read-only) |
| `files_count` | integer | Attached files count (read-only) |
| `comments_count` | integer | Comments count (read-only) |

### 3.2 Missing Includes

| Include | Is Collection | Notes |
|---------|---------------|-------|
| `subtasks` | true | Task subtasks/checklist |
| `files` | true | Attached files |
| `comments` | true | Task comments |
| `bookings` | true | Resource bookings |

### 3.3 Missing WHERE Operations

```php
'tasklist_id' => ['=', '!=', 'in', 'not in'],
'user_id' => ['=', '!=', 'in', 'not in'],
'status_id' => ['=', '!=', 'in', 'not in'],
'due_date' => ['=', '!=', '>', '<', '>=', '<='],
'complete' => ['=', '!='],
'priority' => ['=', '!=', '>', '<', '>=', '<=', 'in', 'not in'],
```

---

## 4. TimeEntry Resource

**File:** `src/Entity/Resource/TimeEntry.php`
**Status:** Good

### 4.1 Missing Properties

| Property | Type | Notes |
|----------|------|-------|
| `billable` | boolean | Whether entry is billable (separate from task billable) |

### 4.2 Missing Includes

| Include | Is Collection | Notes |
|---------|---------------|-------|
| `project` | false | Parent project |

### 4.3 WHERE Operations Improvements

```php
// More operations for time_interval
'time_interval' => ['in', '='],
'user_id' => ['=', '!=', 'in', 'not in'],
'task_id' => ['=', '!=', 'in', 'not in'],
'project_id' => ['=', '!=', 'in', 'not in'],
'billed' => ['=', '!='],
```

---

## 5. Client Resource

**File:** `src/Entity/Resource/Client.php`
**Status:** Good, Minor Gaps

### 5.1 Missing Properties

| Property | Type | Notes |
|----------|------|-------|
| `currency` | text | Client default currency |
| `vat_id` | text | VAT identification number |
| `language` | text | Preferred language |

### 5.2 Missing Includes

| Include | Is Collection | Notes |
|---------|---------------|-------|
| `entries` | true | Time entries for client |
| `expenses` | true | Expenses for client |
| `estimates` | true | Estimates for client |

---

## 6. Invoice Resource

**File:** `src/Entity/Resource/Invoice.php`
**Status:** Good

### 6.1 Missing Properties

| Property | Type | Notes |
|----------|------|-------|
| `viewed_on` | datetime | When client first viewed (read-only) |
| `sent_on` | datetime | When invoice was sent (read-only) |
| `paid_on` | datetime | When invoice was fully paid (read-only) |
| `last_reminder_sent_on` | datetime | Last reminder date (read-only) |

### 6.2 Missing Special Actions

The API supports special actions for invoices that we don't implement:

```
POST /api/invoices/{id}/send - Send invoice email
POST /api/invoices/{id}/remind - Send payment reminder
POST /api/invoices/{id}/mark_as_sent - Mark as sent without email
POST /api/invoices/{id}/mark_as_paid - Mark as fully paid
```

**TODO:** Add helper methods for these actions.

---

## 7. InvoiceItem Resource

**File:** `src/Entity/Resource/InvoiceItem.php`
**Status:** Good, Minor Gaps

### 7.1 Missing Properties

| Property | Type | Notes |
|----------|------|-------|
| `project_id` | resource:project | For project-based items |
| `task_id` | resource:task | For task-based items |
| `apply_tax2` | boolean | Apply second tax |
| `line_total` | decimal | Calculated total (read-only) |

---

## 8. Estimate Resource

**File:** `src/Entity/Resource/Estimate.php`
**Status:** Good

### 8.1 Missing Special Actions

```
POST /api/estimates/{id}/send - Send estimate email
POST /api/estimates/{id}/convert - Convert to invoice
POST /api/estimates/{id}/mark_as_sent - Mark as sent
POST /api/estimates/{id}/accept - Mark as accepted
```

---

## 9. EstimateItem Resource

**File:** `src/Entity/Resource/EstimateItem.php`
**Status:** Needs Review

Same missing properties as InvoiceItem.

---

## 10. User Resource

**File:** `src/Entity/Resource/User.php`
**Status:** Good

### 10.1 Missing Properties

| Property | Type | Notes |
|----------|------|-------|
| `last_active_on` | datetime | Last activity timestamp (read-only) |
| `two_factor_auth_enabled` | boolean | 2FA status (read-only) |

### 10.2 Missing Includes

| Include | Is Collection | Notes |
|---------|---------------|-------|
| `tasks` | true | Assigned tasks |
| `projects` | true | Assigned projects |
| `bookings` | true | User's bookings |

---

## 11. Expense Resource

**File:** `src/Entity/Resource/Expense.php`
**Status:** Good

### 11.1 Missing Properties

| Property | Type | Notes |
|----------|------|-------|
| `billable` | boolean | Whether expense is billable |
| `category` | text | Expense category |

---

## 12. Tasklist Resource

**File:** `src/Entity/Resource/Tasklist.php`
**Status:** Good

### 12.1 Typo in INCLUDE_TYPES

```php
// Current (typo):
'miletstone' => false

// Should be:
'milestone' => false
```

---

## 13. Milestone Resource

**File:** `src/Entity/Resource/Milestone.php`
**Status:** Good

No significant missing items identified.

---

## 14. Booking Resource

**File:** `src/Entity/Resource/Booking.php`
**Status:** Good

### 14.1 Missing Includes

| Include | Is Collection | Notes |
|---------|---------------|-------|
| `user` | false | Booked user |
| `task` | false | Associated task |
| `project` | false | Associated project |

---

## 15. TaskAssignment Resource

**File:** `src/Entity/Resource/TaskAssignment.php`
**Status:** Good

### 15.1 Missing Includes

| Include | Is Collection | Notes |
|---------|---------------|-------|
| `project` | false | Parent project |
| `bookings` | true | Associated bookings |

---

## 16. Workflow Resource

**File:** `src/Entity/Resource/Workflow.php`
**Status:** Good

No significant missing items identified.

---

## 17. WorkflowStatus Resource

**File:** `src/Entity/Resource/WorkflowStatus.php`
**Status:** Good

### 17.1 Missing Includes

| Include | Is Collection | Notes |
|---------|---------------|-------|
| `tasks` | true | Tasks in this status |

---

## 18. File Resource

**File:** `src/Entity/Resource/File.php`
**Status:** Good

### 18.1 Missing Properties

| Property | Type | Notes |
|----------|------|-------|
| `version` | integer | File version number |
| `versions` | array | All file versions (read-only) |

### 18.2 Missing Includes

| Include | Is Collection | Notes |
|---------|---------------|-------|
| `thread` | false | Comment thread |
| `comments` | true | File comments |

---

## 19. Comment Resource

**File:** `src/Entity/Resource/Comment.php`
**Status:** Good

### 19.1 Missing Properties in PROP_TYPES

| Property | Type | Notes |
|----------|------|-------|
| `task_id` | resource:task | For creating task comments |
| `discussion_id` | resource:discussion | For creating discussion comments |
| `file_id` | resource:file | For creating file comments |

These are in REQUIRED_CREATE but not in PROP_TYPES.

---

## 20. Discussion Resource

**File:** `src/Entity/Resource/Discussion.php`
**Status:** Good

No significant missing items identified.

---

## 21. ClientContact Resource

**File:** `src/Entity/Resource/ClientContact.php`
**Status:** Good

No significant missing items identified.

---

## 22. Report Resource

**File:** `src/Entity/Resource/Report.php`
**Status:** Good

### 22.1 Missing Properties

| Property | Type | Notes |
|----------|------|-------|
| `tags` | array | Report tags/labels |
| `description` | text | Report description |

---

## 23. Company Resource

**File:** `src/Entity/Resource/Company.php`
**Status:** Good (Singleton)

### 23.1 Typo in PROP_TYPES

```php
// Current (typo):
'max_estimates' => 'interger'

// Should be:
'max_estimates' => 'integer'
```

---

## 24. Templates

### 24.1 InvoiceTemplate Resource
**File:** `src/Entity/Resource/InvoiceTemplate.php`
**Status:** EXISTS - needs verification

### 24.2 EstimateTemplate Resource
**File:** `src/Entity/Resource/EstimateTemplate.php`
**Status:** EXISTS - needs verification

### 24.3 ProjectTemplate Resource
**File:** `src/Entity/Resource/ProjectTemplate.php`
**Status:** EXISTS - needs verification

---

## 25. Missing WHERE/Filter Operations

Several resources need expanded WHERE operations for better filtering:

### 25.1 Date Range Filters
Many resources should support date filtering:
```php
'created_on' => ['=', '!=', '>', '<', '>=', '<=', 'in'],
'updated_on' => ['=', '!=', '>', '<', '>=', '<=', 'in'],
```

### 25.2 Parent Entity Filters
Resources should allow filtering by parent:
```php
// Example for Tasks
'project_id' => ['=', '!=', 'in', 'not in'],
'tasklist_id' => ['=', '!=', 'in', 'not in'],
```

---

## 26. Missing Include Relations

### 26.1 Cross-Entity Includes

Some includes that would be useful but may not be in API:

| Resource | Missing Include | Type |
|----------|-----------------|------|
| Project | `entries` | collection |
| Project | `expenses` | collection |
| Task | `subtasks` | collection |
| Task | `files` | collection |
| Client | `estimates` | collection |
| User | `bookings` | collection |

---

## 27. Utility/Helper Features

### 27.1 Invoice Actions Helper
Create helper methods for invoice operations:
- `send()` - Send invoice email
- `remind()` - Send payment reminder
- `markAsSent()` - Mark as sent
- `markAsPaid()` - Mark as fully paid

### 27.2 Estimate Actions Helper
Create helper methods for estimate operations:
- `send()` - Send estimate email
- `convertToInvoice()` - Convert to invoice
- `markAsAccepted()` - Mark as accepted

### 27.3 Time Formatting Utility
Add helper for converting seconds to hours/formatted time:
```php
TimeEntry::formatDuration($seconds) // Returns "2h 30m"
TimeEntry::toHours($seconds) // Returns 2.5
```

### 27.4 Currency Formatting Utility
For invoice/estimate amounts with proper currency display.

---

## 28. Architecture Improvements

### 28.1 Batch Operations
Consider implementing batch create/update/delete for efficiency:
```php
Task::batchCreate($connection, $tasks);
Task::batchUpdate($connection, $tasks);
```

### 28.2 Pagination Helpers
Improve pagination handling for large datasets:
```php
$collection->paginate($perPage, $page);
$collection->hasNextPage();
$collection->getTotalCount();
```

### 28.3 Caching Layer
Consider optional caching for frequently accessed resources:
```php
Client::withCache($ttl)->list();
```

### 28.4 Event Hooks
Local hooks for pre/post operations:
```php
Task::beforeCreate(function($task) { ... });
Task::afterCreate(function($task) { ... });
```

### 28.5 Rate Limiting Handler
Add automatic rate limit handling with retry logic.

---

## Priority Summary

### Immediate (High Priority)
1. [ ] Create `RecurringProfile` resource (Invoice recurring)
2. [ ] Create `Subtask` resource
3. [ ] Create `Webhook` resource
4. [ ] Fix `workflow_id` type in Project (currently `resource:milestone`)
5. [ ] Fix typo `miletstone` -> `milestone` in Tasklist
6. [ ] Fix typo `interger` -> `integer` in Company

### Short Term (Medium Priority)
7. [ ] Create `TaskRecurringProfile` resource
8. [ ] Add missing properties to Project (`start_date`, `end_date`, `budget`)
9. [ ] Add missing properties to Task (`time_estimate`, `progress`)
10. [ ] Add Invoice action methods (`send()`, `remind()`, etc.)
11. [ ] Add Estimate action methods
12. [ ] Add missing Comment PROP_TYPES (`task_id`, `discussion_id`, `file_id`)

### Long Term (Low Priority)
13. [ ] Add all missing includes across resources
14. [ ] Add all missing WHERE operations
15. [ ] Implement utility helpers (time formatting, currency)
16. [ ] Consider batch operations
17. [ ] Consider pagination improvements
18. [ ] Consider caching layer

---

## Notes

- Properties marked "Undocumented" in our code should be verified against current API behavior
- The Paymo API occasionally adds new properties - periodic review recommended
- Some includes may not work depending on Paymo subscription level
- Test all changes against live API before committing

---

*Generated by comprehensive analysis of official Paymo API documentation at https://github.com/paymoapp/api compared against our package implementation.*
