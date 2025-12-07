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
| (none yet) | | | | |

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

## Changelog

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
