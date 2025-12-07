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
