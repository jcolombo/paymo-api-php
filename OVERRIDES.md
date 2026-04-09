# Paymo API PHP SDK - API Overrides and Undocumented Features

This document tracks deviations between the official Paymo API documentation and the actual API behavior observed through testing. The Paymo API documentation (https://github.com/paymoapp/api) has not been updated since 2022, yet Paymo continues to release new features monthly.

## Purpose

This SDK aims to provide comprehensive coverage of the Paymo API, including:
1. **Documented features** - As described in the official API documentation
2. **Undocumented features** - New fields, endpoints, or behaviors discovered through testing
3. **Documentation corrections** - Where the API behaves differently than documented

## Override Policy

### When to Add an Override

Add an entry to this document when:
- A property exists in API responses that is not in the official documentation
- A property in the documentation does not appear in API responses
- A property has a different type than documented
- An endpoint behaves differently than documented
- New endpoints are discovered that are not documented

### Required Information

Each override entry must include:
- **Resource**: The affected resource type (e.g., Project, Task)
- **Property/Endpoint**: The specific property or endpoint affected
- **Discovery Date**: When this was first observed
- **API Response Evidence**: Example of actual API response showing the behavior
- **Documentation Reference**: Link to relevant official documentation (if any)
- **SDK Handling**: How the SDK handles this deviation

### Code Comments

When implementing an override in the SDK code, add a comment in the following format:

```php
/**
 * @override OVERRIDE-001
 * @see OVERRIDES.md#override-001
 *
 * Property 'new_field' is not documented in the official API but is
 * returned in API responses as of 2024-12.
 */
'new_field' => 'string',
```

This ensures:
1. The override is clearly marked and won't be removed during cleanup
2. There's a traceable reference to the documentation
3. AI assistants and developers understand this is intentional

---

## Active Overrides

### OVERRIDE-001: Client Image Thumbnail Properties (Conditional)

**Resource:** Client
**Type:** Conditional Property
**Discovery Date:** 2024-12-07
**Status:** Active

**Official Documentation:**
> The Paymo API documentation lists `image`, `image_thumb_large`, `image_thumb_medium`, and `image_thumb_small` as properties on the Client resource.

**Actual API Behavior:**

These properties are **conditionally returned** by the API:

1. **Without an image uploaded:** Properties are NOT included in the API response
2. **With an image uploaded:** All 4 properties are returned with URLs

```json
// Client WITHOUT an image (these properties are absent):
{
  "id": 12345,
  "name": "Test Client",
  "email": "test@example.com"
  // image, image_thumb_large, image_thumb_medium, image_thumb_small are NOT present
}

// Client WITH an image uploaded:
{
  "id": 12345,
  "name": "Test Client",
  "email": "test@example.com",
  "image": "https://userfiles.paymoapp.com/xxxxx/clients/full-image.png",
  "image_thumb_large": "https://userfiles.paymoapp.com/xxxxx/clients/thumb-large.png",
  "image_thumb_medium": "https://userfiles.paymoapp.com/xxxxx/clients/thumb-medium.png",
  "image_thumb_small": "https://userfiles.paymoapp.com/xxxxx/clients/thumb-small.png"
}
```

**SDK Implementation:**
```php
// In Client.php PROP_TYPES - these are valid properties
// @override OVERRIDE-001
// @see OVERRIDES.md#override-001
// These properties are CONDITIONAL - only returned when client has an image uploaded
'image'              => 'url',
'image_thumb_large'  => 'url',
'image_thumb_medium' => 'url',
'image_thumb_small'  => 'url',
```

**Test Validation:**
The test suite (`ClientResourceTest.php`) includes an `runImagePropertyDiscovery()` method that:
1. Creates a test client
2. Uploads a test image
3. Re-fetches the client
4. Verifies all 4 image properties are present

**Notes:**
- These properties are **read-only** (cannot be set directly)
- To add an image, use the `$client->image($filepath)` method
- When running property discovery tests, the "missing" image_thumb_* properties are expected if the test client has no image
- This is NOT a bug - it's expected API behavior for conditional properties

---

### OVERRIDE-003: Pagination Support (Undocumented Feature)

**Resource:** All Collection Resources
**Type:** Undocumented API Feature
**Discovery Date:** 2024-12-07
**Status:** Active
**Discovery Source:** Direct communication with Paymo support

**Official Documentation:**
> The official Paymo API documentation (https://github.com/paymoapp/api) makes NO mention of pagination. All documentation examples show fetching complete lists without any page or limit parameters.

**Actual API Behavior:**

The Paymo API **DOES support pagination** via undocumented `page` and `page_size` query parameters:

```bash
# Example API call with pagination
curl -u api_key:random -H 'Accept: application/json' \
  "https://app.paymoapp.com/api/invoices?include=invoiceitems&page=0&page_size=100"
```

**Pagination Parameters:**

| Parameter   | Type    | Description                                  |
|-------------|---------|----------------------------------------------|
| `page`      | integer | Page number (0-indexed, first page is 0)     |
| `page_size` | integer | Number of results per page                   |

**Important Notes:**
- Pages are **0-indexed** (page=0 returns the first page)
- Both `page` AND `page_size` must be provided for pagination to work
- The API does NOT return total count or page metadata in the response
- Without pagination, the API returns ALL matching resources (potentially thousands)
- WHERE conditions are applied BEFORE pagination
- **Possible Maximum:** Paymo support mentioned a maximum page_size of 2500, but this is unconfirmed. The SDK does not enforce this limit - use at your own discretion

**SDK Implementation:**

The SDK provides a fluent `limit()` method on all collections:

```php
// Single parameter: quantity limit (page 0 implied)
$invoices = Invoice::list()->limit(100)->fetch();
// API call: GET /api/invoices?page=0&page_size=100

// Two parameters: explicit page and size
$invoices = Invoice::list()->limit(2, 50)->fetch();
// API call: GET /api/invoices?page=2&page_size=50

// Clear pagination (fetch all)
$collection->limit();
```

**Affected Files:**
- `src/Entity/AbstractCollection.php` - Added `limit()` method, `$paginationPage`, `$paginationPageSize` properties
- `src/Request.php` - Modified `list()` to pass pagination options
- `src/Paymo.php` - Modified `buildRequestProps()` to add query parameters
- `src/Utility/RequestAbstraction.php` - Added `$page`, `$pageSize` properties

**Code References:**

```php
// In AbstractCollection.php
// @override OVERRIDE-003
// @see OVERRIDES.md#override-003
protected ?int $paginationPage = null;
protected ?int $paginationPageSize = null;

public function limit(?int $pageOrSize = null, ?int $pageSize = null) : AbstractCollection
{
    // Single param = page size only (page 0)
    // Two params = page number and page size
}

// In Request.php - list() method
// @override OVERRIDE-003
if (isset($options['page']) && is_int($options['page'])) {
    $request->page = $options['page'];
}

// In Paymo.php - buildRequestProps() method
// @override OVERRIDE-003
if (!is_null($request->page)) {
    $query['page'] = $request->page;
}
if (!is_null($request->pageSize)) {
    $query['page_size'] = $request->pageSize;
}
```

**Manual Iteration Example:**

Since the API doesn't return total count, iterate pages until fewer results are returned:

```php
$page = 0;
$pageSize = 100;
$allInvoices = [];

do {
    $invoices = Invoice::list()->limit($page, $pageSize)->fetch();
    $results = $invoices->raw();
    $count = count($results);

    $allInvoices = array_merge($allInvoices, $results);
    $page++;

} while ($count === $pageSize); // Stop when we get fewer than requested

echo "Total invoices: " . count($allInvoices);
```

**Why This Matters:**

Without pagination, accounts with large datasets (thousands of invoices, tasks, or time entries) would experience:
- Very slow API responses
- High memory usage in PHP
- Potential timeouts
- Rate limiting issues from large response sizes

Pagination enables efficient batch processing and reduces API load.

---

### OVERRIDE-002: Company Tax Properties (Conditional)

**Resource:** Company
**Type:** Conditional Property
**Discovery Date:** 2024-12-07
**Status:** Active

**Official Documentation:**
> Properties `apply_tax_to_expenses` and `tax_on_tax` are listed in SDK PROP_TYPES.

**Actual API Behavior:**

These properties are **not returned** in standard Company API responses. They may be:
- Deprecated properties no longer in use
- Only returned when specific tax configurations are enabled
- Account-type dependent (e.g., only for certain subscription plans)

```json
// Standard Company response does NOT include:
// - apply_tax_to_expenses
// - tax_on_tax
```

**SDK Implementation:**
```php
// In Company.php PROP_TYPES
// @override OVERRIDE-002
// @see OVERRIDES.md#override-002
// These properties may not be returned by API - possibly deprecated or conditional
'apply_tax_to_expenses' => 'text',
'tax_on_tax'            => 'text',
```

**Notes:**
- Keep in SDK for backwards compatibility
- Mark as potentially deprecated
- Do not treat as error when missing in property discovery tests

---

## Conditional Properties Summary

Properties that are conditionally returned by the API based on entity state or configuration.

**IMPORTANT:** When running property discovery tests, these properties may appear as "missing" - this is expected behavior, NOT a bug.

| Resource | Property | Condition | Override ID |
|----------|----------|-----------|-------------|
| Client | `image` | Only present when client has uploaded image | OVERRIDE-001 |
| Client | `image_thumb_large` | Only present when client has uploaded image | OVERRIDE-001 |
| Client | `image_thumb_medium` | Only present when client has uploaded image | OVERRIDE-001 |
| Client | `image_thumb_small` | Only present when client has uploaded image | OVERRIDE-001 |
| Company | `apply_tax_to_expenses` | Possibly deprecated or account-dependent | OVERRIDE-002 |
| Company | `tax_on_tax` | Possibly deprecated or account-dependent | OVERRIDE-002 |

---

### OVERRIDE-004: Session Resource String ID

**Resource:** Session
**Type:** Type Mismatch
**Discovery Date:** 2024-12-07
**Status:** Active

**Official SDK Assumption:**
> Most Paymo resources use integer IDs. The base `AbstractResource` class and related tests assumed `$resource->id` would always be an integer.

**Actual API Behavior:**

The Session resource uses **string tokens** as IDs, not integers:

```json
{
  "sessions": [
    {
      "id": "abc123session-token-string",
      "user_id": 12345,
      "ip": "192.168.1.1",
      "created_on": "2024-12-07T10:00:00Z",
      "expires_on": "2024-12-14T10:00:00Z"
    }
  ]
}
```

**SDK Implementation:**
```php
// In Session.php PROP_TYPES
// @override OVERRIDE-004
// @see OVERRIDES.md#override-004
// Session ID is a TEXT token, not an integer
'id' => 'text',
```

**Test Handling:**
```php
// In ResourceTest.php - logApiResponse() and logCrudOperation()
// @override OVERRIDE-004
// Accept string|int|null for $id to support Session resources
protected function logApiResponse(bool $success, string|int|null $id = null, ...): void
protected function logCrudOperation(string $operation, string $resourceType, string|int|null $id, ...): void
```

**Notes:**
- Sessions are authentication tokens, not database entities
- The Session resource cannot be updated (only created/deleted)
- Other resources continue to use integer IDs

---

### OVERRIDE-005: Resources Requiring Parent Filters (SDK Validation)

**Resources:** File, Booking, InvoiceItem, EstimateItem
**Type:** SDK Validation Requirement
**Discovery Date:** 2024-12-07
**Status:** Active

**Behavior:**

The SDK enforces parent filter requirements for certain resources. These are **SDK-level validations** to prevent overly broad API queries, not API limitations.

| Resource | Required Filter(s) | Collection Class |
|----------|-------------------|------------------|
| File | `task_id`, `project_id`, `discussion_id`, or `comment_id` | FileCollection |
| Booking | Date range (`start_date` AND `end_date`) OR `user_task_id`, `task_id`, `project_id`, `user_id` | BookingCollection |
| InvoiceItem | `invoice_id` | InvoiceItemCollection |
| EstimateItem | `estimate_id` | EstimateItemCollection |

**Exception Messages:**
```
File collections require one of the following be set as a filter : task_id, project_id, discussion_id, comment_id

Booking collections require a start_date and end_date OR at least one of the following be set as a filter : user_task_id, task_id, project_id, user_id

Invoice item collections require a where condition filter set on invoice_id

Estimate item collections require a where condition filter set on estimate_id
```

**SDK Usage:**
```php
// File - requires project_id or similar
$files = File::list()
    ->where(File::where('project_id', $projectId))
    ->fetch();

// Booking - requires date range or parent filter
$bookings = Booking::list()
    ->where(Booking::where('start_date', '2024-01-01', '>='))
    ->where(Booking::where('end_date', '2024-12-31', '<='))
    ->fetch();
// OR
$bookings = Booking::list()
    ->where(Booking::where('project_id', $projectId))
    ->fetch();

// InvoiceItem - requires invoice_id
$items = InvoiceItem::list()
    ->where(InvoiceItem::where('invoice_id', $invoiceId))
    ->fetch();
```

**Test Handling:**

For tests running in read-only mode, these resources require an anchored ID in the test configuration:

```php
// In ResourceTest subclass
public function getRequiredParentFilter(): ?array
{
    return ['invoice_id', 'ensureInvoice'];
}
```

The test framework will:
1. Check for an anchored value in config (e.g., `invoice_id`)
2. Skip tests if no anchor is available in read-only mode
3. In non-read-only mode, create the parent resource dynamically

**Notes:**
- These validations exist to prevent performance issues from unbounded queries
- The API may actually accept these queries, but results could be very large
- Always provide the narrowest filter possible for efficiency

---

## Discovered Undocumented Properties

This section lists properties discovered through API testing that are not in the official documentation. These are automatically detected by the test suite when comparing API responses to documented PROP_TYPES.

### Pending Review

Properties discovered but not yet added to the SDK:

| Resource | Property | Type | Discovered | Notes |
|----------|----------|------|------------|-------|
| (none yet) | | | | |

### Implemented

Properties that have been added to the SDK based on discovery:

| Resource | Property | Type | Added | Override ID |
|----------|----------|------|-------|-------------|
| (none yet) | | | | |

---

## Missing Documented Properties

Properties that are documented but not observed in API responses:

| Resource | Property | Documented Type | Last Checked | Notes |
|----------|----------|-----------------|--------------|-------|
| (none yet) | | | | |

---

## Type Mismatches

Properties where the actual type differs from documentation:

| Resource | Property | Documented | Actual | Override ID |
|----------|----------|------------|--------|-------------|
| Session | `id` | integer (assumed) | text (string token) | OVERRIDE-004 |

---

## Verification Process

The test suite (`./tests/validate`) performs the following discovery checks:

1. **Property Discovery Test**
   - Fetches resources with no field restrictions
   - Compares returned properties against SDK PROP_TYPES
   - Logs any extra or missing properties

2. **Property Selection Test**
   - Attempts to select each defined PROP_TYPE property
   - Verifies the API accepts the selection
   - Logs any properties that fail selection

3. **Where Clause Test**
   - Tests filtering on each property that should be filterable
   - Logs any properties that fail as filter criteria

### Running Discovery

```bash
# Run full property discovery
./tests/validate properties --verbose

# Run with fresh log
./tests/validate properties --verbose --reset-log

# Check the log for discoveries
cat tests/validation-results.log | grep -i "discovered\|mismatch\|missing"
```

---

### OVERRIDE-006: Client.active Property (Intentionally Read-Only)

**Resource:** Client
**Type:** Intentional Design Decision
**Discovery Date:** 2025-12-07
**Status:** Active

**Official Documentation Contradiction:**
The API documentation is self-contradictory about the `active` property:
- Property table explicitly says: `active | boolean | _(read-only)_`
- BUT also says: "To archive a client, make an update request with `{"active": false}`"

**SDK Implementation:**
```php
// In Client.php READONLY
// @override OVERRIDE-006
// @see OVERRIDES.md#override-006
// Intentionally read-only to prevent accidental archive/activate
'active',
```

**Rationale:**
The SDK marks `active` as READONLY which is CORRECT and SAFER behavior because:
1. The API docs explicitly say it's "read-only" in the property schema
2. Archiving/activating is a significant action that should be explicit, not accidental
3. Normal entity updates should NOT accidentally change active status
4. Users who need to archive/activate should make direct API calls

**Alternative for Users:**
To archive/activate a client, users should use the Paymo connection directly:
```php
$connection->update('clients', $clientId, ['active' => false]);
```

---

### OVERRIDE-007: API Documentation Gaps - Missing Critical Properties

**Resources:** EstimateItem, InvoiceItem
**Type:** Documentation Gap
**Discovery Date:** 2025-12-07
**Status:** Active

**Official Documentation:**
The official Paymo API docs OMIT these essential properties from object definitions:
- `EstimateItem.estimate_id` - Not in API docs, but required to link items to estimates
- `InvoiceItem.invoice_id` - Not in API docs, but required to link items to invoices

**Evidence:**
Properties exist and work - verified via live API testing.

**SDK Implementation:**
```php
// In EstimateItem.php and InvoiceItem.php PROP_TYPES
// These properties are undocumented in official API docs but verified via live testing
'estimate_id' => 'resource:estimate',
'invoice_id'  => 'resource:invoice',
```

---

### OVERRIDE-008: API Documentation Gaps - Missing Resources

**Resources:** EstimateTemplate, InvoiceTemplate, InvoicePayment, ProjectStatus
**Type:** Documentation Gap
**Discovery Date:** 2025-12-07
**Status:** Active

**Official Documentation:**
NO documentation files exist in the official Paymo API repo (https://github.com/paymoapp/api) for these endpoints.

**Evidence:**
Endpoints work - verified via live API testing. SDK implements based on direct API inspection.

**SDK Behavior:**
Implemented based on observed API responses. Properties marked appropriately in PROP_TYPES.

---

### OVERRIDE-009: API Response Key Anomalies

**Resources:** ProjectTemplate, ProjectTemplateTasklist, ProjectTemplateTask, RecurringProfile
**Type:** API Behavior Anomaly
**Discovery Date:** 2025-12-07
**Status:** Active

**Issue:**
The API returns JSON with response keys that don't match endpoint names (underscores added).

**Examples:**
| Endpoint | Response Key |
|----------|-------------|
| `/projecttemplates` | `project_templates` |
| `/projecttemplatestasklists` | `project_templates_tasklists` |
| `/projecttemplatestasks` | `project_templates_tasks` |
| `/recurringprofiles` | `recurring_profiles` |

**SDK Implementation:**
```php
// In affected resource files
// @override OVERRIDE-009
// @see OVERRIDES.md#override-009
public const API_RESPONSE_KEY = 'project_templates'; // differs from endpoint
```

---

### OVERRIDE-010: Gallery Response Key Anomalies

**Resources:** EstimateTemplateGallery, InvoiceTemplateGallery
**Type:** API Behavior Anomaly
**Discovery Date:** 2025-12-07
**Status:** Active

**Issue:**
These gallery endpoints return data under unconventional keys prefixed with colons.

**SDK Implementation:**
```php
// In EstimateTemplateGallery.php and InvoiceTemplateGallery.php
// @override OVERRIDE-010
// @see OVERRIDES.md#override-010
public function getResponseKey(): string
{
    return ':estimatetemplates'; // or ':invoicetemplates'
}
```

---

### OVERRIDE-011: Undocumented Properties (Intentionally Captured)

**Affected Resources:** Multiple (Company, Booking, Task, User, Report, Invoice, etc.)
**Type:** SDK Design Decision
**Discovery Date:** 2025-12-07
**Status:** Active

**Policy:**
The SDK intentionally captures ALL properties returned by the API, including undocumented ones, because:
1. The Paymo API documentation hasn't been updated since 2022
2. Paymo continues to release new features monthly
3. Removing properties would break users who rely on them
4. Capturing all data provides complete API coverage

**SDK Implementation:**
Undocumented properties are:
- Added to PROP_TYPES with appropriate type
- Marked with `// Undocumented Props` comment
- Placed in READONLY array (cannot be set by users)

**Examples:**
```php
// In Task.php
  // Undocumented props set to readonly
'cover_file_id',
'price',
'start_date',
'recurring_profile_id',
'billing_type'
```

---

### OVERRIDE-012: Deprecated Properties (Intentionally Included)

**Resource:** RecurringProfile
**Property:** `language`
**Type:** Deprecated Property Retention
**Discovery Date:** 2025-12-07
**Status:** Active

**Official Documentation:**
> `language: _(deprecated)_ Invoice language (Use invoice templates instead)`

**SDK Implementation:**
```php
// In RecurringProfile.php
// @override OVERRIDE-012
// @see OVERRIDES.md#override-012
'language' => 'text'  // Deprecated - use invoice templates instead
```

**Rationale:**
Property included for backwards compatibility despite deprecation. Placed in READONLY to prevent users from setting deprecated values.

---

### OVERRIDE-013: Unselectable Properties (API Query Restriction)

**Affected Resources:** Client, User, Task, Milestone, Expense, File
**Type:** API Query Behavior
**Discovery Date:** 2025-12-07
**Status:** Active

**Issue:**
Certain properties exist in API responses but **cannot be explicitly selected** via the `select` query parameter. Attempting to select these properties returns HTTP 400 "Unknown field or reference: {property}".

**Affected Properties (32 properties across 6 resources):**

| Resource | Properties | Count | Notes |
|----------|-----------|-------|-------|
| Client | `additional_privileges`, `image_thumb_large`, `image_thumb_medium`, `image_thumb_small` | 4 | Internal field + thumbnail URLs |
| User | `additional_privileges`, `date_format`, `time_format`, `decimal_sep`, `thousands_sep`, `has_submitted_review`, `image_thumb_large`, `image_thumb_medium`, `image_thumb_small`, `is_online`, `language`, `theme`, `menu_shortcut`, `user_hash`, `annual_leave_days_number`, `password`, `workflows`, `week_start`, `assigned_projects`, `managed_projects` | 20 | Preferences, internal fields, thumbnails |
| Task | `subtasks_order` | 1 | Write-only field for reordering subtasks |
| Milestone | `linked_tasklists` | 1 | Array of linked tasklist IDs |
| Expense | `image_thumb_large`, `image_thumb_medium`, `image_thumb_small` | 3 | Thumbnail URLs, conditionally returned |
| File | `image_thumb_large`, `image_thumb_medium`, `image_thumb_small` | 3 | Thumbnail URLs, conditionally returned |

**API Behavior:**
```bash
# This FAILS with HTTP 400:
GET /api/clients?select=id,name,additional_privileges
# Response: {"message": "Unknown field or reference: additional_privileges"}

# But the field IS returned when fetching all fields:
GET /api/clients/123
# Response includes: {"additional_privileges": [...]}
```

**SDK Implementation:**

A new `UNSELECTABLE` constant is added to `AbstractResource` and affected resource classes:

```php
// In AbstractResource.php
/**
 * Properties that exist in API responses but CANNOT be requested via select.
 *
 * @override OVERRIDE-013
 * @see OVERRIDES.md#override-013
 *
 * @var string[] Property names that cannot be selected
 */
public const UNSELECTABLE = [];

// In Client.php (4 properties)
public const UNSELECTABLE = ['additional_privileges', 'image_thumb_large', 'image_thumb_medium', 'image_thumb_small'];

// In User.php (20 properties)
public const UNSELECTABLE = ['additional_privileges', 'date_format', 'time_format', 'decimal_sep', 'thousands_sep', 'has_submitted_review', 'image_thumb_large', 'image_thumb_medium', 'image_thumb_small', 'is_online', 'language', 'theme', 'menu_shortcut', 'user_hash', 'annual_leave_days_number', 'password', 'workflows', 'week_start', 'assigned_projects', 'managed_projects'];

// In Task.php
public const UNSELECTABLE = ['subtasks_order'];

// In Milestone.php
public const UNSELECTABLE = ['linked_tasklists'];

// In Expense.php
public const UNSELECTABLE = ['image_thumb_large', 'image_thumb_medium', 'image_thumb_small'];

// In File.php
public const UNSELECTABLE = ['image_thumb_large', 'image_thumb_medium', 'image_thumb_small'];
```

**Test Framework Handling:**

The `ResourceTest::runPropertySelection()` method filters out UNSELECTABLE properties before testing field selection:

```php
// Get UNSELECTABLE properties (if defined)
$unselectable = defined($class . '::UNSELECTABLE') ? $class::UNSELECTABLE : [];

// Filter out UNSELECTABLE properties
$propNames = array_filter($propNames, fn($prop) => !in_array($prop, $unselectable));
```

**Notes:**
- These properties are still valid in PROP_TYPES and should be processed when returned
- They just cannot be explicitly requested via select queries
- The API returns them when no select is specified (full resource fetch)
- This is distinct from READONLY (can't be set) - UNSELECTABLE means can't be queried

---

## Changelog

### 2025-12-07 - Gemini/Codex Audit Response
- Added OVERRIDE-006: Client.active intentional read-only
- Added OVERRIDE-007: EstimateItem/InvoiceItem missing critical properties in docs
- Added OVERRIDE-008: Missing resource documentation
- Added OVERRIDE-009: API response key anomalies
- Added OVERRIDE-010: Gallery response key anomalies
- Added OVERRIDE-011: Undocumented properties policy
- Added OVERRIDE-012: Deprecated property retention
- Added OVERRIDE-013: Unselectable properties (API query restriction)

### 2024-12 - Initial Setup
- Created OVERRIDES.md structure
- Added test suite property discovery capabilities
- Established override policy and code comment standards

---

## Related Files

- `PACKAGE-DEV.md` - Development guidelines including override handling
- `CLAUDE.md` - AI assistant instructions referencing override policy
- `tests/Properties/PropTypesTest.php` - Property validation tests
- `tests/validation-results.log` - Test output with discovery details
