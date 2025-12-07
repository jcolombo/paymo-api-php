# Paymo API PHP SDK - Test Suite

A comprehensive test suite for validating all resources, properties, and operations in the Paymo API PHP SDK.

## Overview

This test suite is designed to:

- **Validate all 38 SDK resources** against the live Paymo API
- **Test CRUD operations** safely without damaging production data
- **Verify property definitions** (PROP_TYPES, READONLY, CREATEONLY, REQUIRED_CREATE)
- **Test include relationships** between resources
- **Provide detailed output** for debugging and CI/CD integration

## Safety First

The test suite is built with production safety as the top priority:

- **Creates only test data** with identifiable prefixes (e.g., `[TEST]-123456`)
- **Cleans up all created resources** after tests complete
- **Never modifies existing production data**
- **Requires explicit configuration** to run
- **Supports dry-run mode** for verification without API calls
- **Supports read-only mode** for safe production database testing

## Quick Start

```bash
# Make the validate script executable
chmod +x tests/validate

# Run with help to see options
./tests/validate --help

# List available resources
./tests/validate --list

# Run all resources (interactive mode)
./tests/validate

# Run all resources (non-interactive)
./tests/validate --no-interactive all

# Run specific resources only
./tests/validate Project,Task,Client

# Run in dry-run mode (no API calls)
./tests/validate --dry-run all

# Run in read-only mode (safe for production)
./tests/validate --read-only

# Read-only with custom list limit
./tests/validate --read-only --list-limit 10
```

## Read-Only Mode

Read-only mode is designed for safely testing against **production databases** without any risk of data modification.

### What It Does

- **Skips all Create/Update/Delete operations**
- **Uses pagination** to limit API response sizes (default: 5 items per list)
- **Runs all read-based tests:**
  - Property Discovery (compare API vs SDK definitions)
  - Property Selection (verify field selection works)
  - Fetch (test fetching individual resources)
  - List (test listing resources with pagination)
  - Where Operations (test filtering)
  - Include Relationships (test relationship loading)

### Usage

```bash
# Basic read-only mode (5 items per list)
./tests/validate --read-only

# Custom list limit
./tests/validate --read-only --list-limit 10

# Read-only for specific resources
./tests/validate --read-only Invoice,Client,Project

# Verbose read-only
./tests/validate --read-only -v all

# Non-interactive read-only (for CI/CD)
./tests/validate --read-only --no-interactive all
```

### Configuration

You can also enable read-only mode via config file:

```json
{
  "testing": {
    "modes": {
      "read_only": true,
      "list_limit": 5
    }
  }
}
```

### How Pagination Works

Read-only mode uses the SDK's `limit()` method which leverages an undocumented Paymo API pagination feature (see `OVERRIDES.md#override-003`). This ensures:

- Only the specified number of items are fetched from the API
- Reduces API load and response times
- Still validates property mapping and response structure

## Configuration

Add a `testing` section to your `paymoapi.config.json`:

```json
{
  "testing": {
    "enabled": true,
    "api_key": "your-test-api-key",
    "prefix": "[TEST]",
    "anchors": {
      "client_id": 12345,
      "user_id": 67890,
      "project_template_id": null,
      "invoice_template_id": null,
      "estimate_template_id": null,
      "project_status_id": null,
      "workflow_id": null
    },
    "modes": {
      "dry_run": false,
      "verbose": false,
      "stop_on_failure": false,
      "cleanup_on_failure": true,
      "interactive": true,
      "read_only": false,
      "list_limit": 5
    },
    "resources": {
      "skip": [],
      "only": []
    },
    "timeouts": {
      "per_test": 30,
      "per_group": 300,
      "total": 1800
    },
    "logging": {
      "enabled": true,
      "path": null,
      "level": "info",
      "include_timestamps": true,
      "include_stack_traces": true
    }
  }
}
```

### Required Configuration

| Setting | Description |
|---------|-------------|
| `testing.enabled` | Set to `true` to enable testing |
| `testing.api_key` | Your Paymo API key |
| `testing.anchors.client_id` | ID of a test client for safe CRUD tests |

### Anchor Configuration

| Setting | Description |
|---------|-------------|
| `testing.anchors.client_id` | **Required.** ID of a test client for safe CRUD tests |
| `testing.anchors.user_id` | User ID for time entries, bookings, task assignments |
| `testing.anchors.project_template_id` | Project template ID for template tests |
| `testing.anchors.invoice_template_id` | Invoice template ID for invoice tests |
| `testing.anchors.estimate_template_id` | Estimate template ID for estimate tests |
| `testing.anchors.project_status_id` | Project status ID for status tests |
| `testing.anchors.workflow_id` | Workflow ID for workflow status tests |

### Mode Configuration

| Setting | Default | Description |
|---------|---------|-------------|
| `testing.modes.dry_run` | `false` | Simulate tests without making API calls |
| `testing.modes.verbose` | `false` | Show detailed output during test execution |
| `testing.modes.stop_on_failure` | `false` | Stop test suite on first failure |
| `testing.modes.cleanup_on_failure` | `true` | Clean up created resources even if tests fail |
| `testing.modes.interactive` | `true` | Show interactive startup prompt |

### Resource Filtering

Fine-grained control over which resources to test:

| Setting | Default | Description |
|---------|---------|-------------|
| `testing.resources.skip` | `[]` | Array of resource names to skip (e.g., `["Invoice", "Estimate"]`) |
| `testing.resources.only` | `[]` | If set, ONLY test these resources (e.g., `["Project", "Task"]`) |

**How resource filtering works:**
- If `only` is empty, all resources are tested (except those in `skip`)
- If `only` has values, ONLY those resources are tested
- Resources in `skip` are always skipped, even if in `only`

**Example - Test only Project and Task:**
```jsonc
// In your paymoapi.config.json testing section:
"resources": {
  "skip": [],
  "only": ["Project", "Task"]
}
```

**Example - Skip Invoice and Estimate tests:**
```jsonc
// In your paymoapi.config.json testing section:
"resources": {
  "skip": ["Invoice", "Estimate"],
  "only": []
}
```

### Timeout Configuration

| Setting | Default | Description |
|---------|---------|-------------|
| `testing.timeouts.per_test` | `30` | Maximum seconds per individual test |
| `testing.timeouts.per_group` | `300` | Maximum seconds per test group |
| `testing.timeouts.total` | `1800` | Maximum seconds for entire test run |

**Note:** Timeouts are defined for future implementation. Currently, tests run without timeout enforcement.

### Logging Configuration

The test suite can log detailed results to a file for debugging and diagnosis:

| Setting | Default | Description |
|---------|---------|-------------|
| `testing.logging.enabled` | `true` | Enable/disable file logging |
| `testing.logging.path` | `null` | Log file path (null = `tests/validation-results.log`) |
| `testing.logging.level` | `"info"` | Minimum log level: `debug`, `info`, `warning`, `error` |
| `testing.logging.include_timestamps` | `true` | Include timestamps in log entries |
| `testing.logging.include_stack_traces` | `true` | Include stack traces for errors |

**Example - Custom log file location:**
```jsonc
// In your paymoapi.config.json testing section:
"logging": {
  "enabled": true,
  "path": "/var/log/paymo-tests.log",
  "level": "debug",
  "include_timestamps": true,
  "include_stack_traces": true
}
```

**Log file contents include:**
- Test session header with timestamp
- Configuration used for the test run
- All test results (pass/fail/skip) with timing
- Resource operations (created/updated/deleted)
- Detailed error messages and stack traces
- Final summary with statistics
- Cleanup issues and remaining resources

**Sample log output:**
```
================================================================================
TEST SESSION: 20240115_143052_abc123
Started: 2024-01-15 14:30:52
================================================================================

[2024-01-15 14:30:52] [INFO] Configuration:
  Config File: /path/to/config.json
  API Key: ***97e27f
  Prefix: [TEST]
  ...

------------------------------------------------------------
GROUP: safe_crud - Safe CRUD Resources
------------------------------------------------------------
[2024-01-15 14:30:53] [INFO] [PASS] ClientTest::testCreate (1.234s)
[2024-01-15 14:30:54] [INFO] [PASS] ClientTest::testFetch (0.567s)
[2024-01-15 14:30:55] [ERROR] [FAIL] ClientTest::testUpdate (0.890s)
       Reason: Expected name to be updated

================================================================================
TEST SUMMARY
================================================================================

Results:
  Total:   156
  Passed:  154
  Failed:  2
  Skipped: 5
  Duration: 45.23s

Resource Operations:
  Created: 15
  Updated: 3
  Deleted: 15
  Remaining: 0
  Delete Failures: 0
```

### Other Options

| Setting | Default | Description |
|---------|---------|-------------|
| `testing.prefix` | `"[TEST]"` | Prefix added to all created test resources (e.g., `[TEST]-143052 My Project`) |

## Command Line Options

```
Usage: ./tests/validate [options] [resources...]

Options:
  -h, --help                      Show help message
  -c, --config FILE               Use custom config file
  -k, --api-key KEY               Override API key
  --client-id ID                  Override test client ID
  --user-id ID                    Override test user ID
  --project-template-id ID        Override project template ID
  --invoice-template-id ID        Override invoice template ID
  --estimate-template-id ID       Override estimate template ID
  --project-status-id ID          Override project status ID
  --workflow-id ID                Override workflow ID
  --dry-run                       Simulate without API calls
  -v, --verbose                   Verbose output
  -q, --quiet                     Minimal output
  --stop-on-fail                  Stop on first failure
  --no-cleanup                    Don't cleanup after failure
  --no-interactive                Non-interactive mode (for CI/CD)
  --reset-log                     Clear log file before starting
  --list                          List all available resources
  --validate-config               Validate configuration (check for errors)
  --show-config                   Show all current configuration values
  --json                          Output results as JSON

Resources (Safe CRUD):
  Client, Project, Tasklist, Task, Subtask, TimeEntry, Milestone,
  Discussion, Comment, File, Booking, Invoice, InvoiceItem,
  Estimate, EstimateItem, Expense, Webhook

Resources (Read-Only):
  Company, Session, Workflow

Resources (Configured Anchor - require pre-existing IDs):
  User, ProjectTemplate, InvoiceTemplate, EstimateTemplate, ProjectStatus

Special:
  all                             Run all resources (default)
```

### Command Line Examples

```bash
# Run all resources interactively
./tests/validate

# Run all resources non-interactively (for CI/CD)
./tests/validate --no-interactive all

# Run specific resources
./tests/validate Project,Task,Client

# Run specific resources (space-separated also works)
./tests/validate Project Task Client

# Dry run to see what would happen
./tests/validate --dry-run all

# Verbose output for debugging
./tests/validate -v Project

# Run with different API key
./tests/validate -k "your-api-key" all

# Run with custom config file
./tests/validate -c /path/to/test-config.json all

# Validate configuration (check for errors)
./tests/validate --validate-config

# Show all current configuration values
./tests/validate --show-config

# List available resources
./tests/validate --list

# JSON output for CI/CD parsing
./tests/validate --no-interactive --json all

# Reset log file before running
./tests/validate --reset-log Project
```

## Resource Categories

### Safe CRUD Resources

Full CRUD tests for resources that can be safely created and deleted:

- **Client** - Customer/company records
- **Project** - Project containers
- **Tasklist** - Task groupings within projects
- **Task** - Individual work items
- **Subtask** - Child tasks
- **TimeEntry** - Time tracking records
- **Milestone** - Project milestones
- **Discussion** - Project discussions
- **Comment** - Discussion comments
- **File** - File attachments
- **Booking** - Resource bookings
- **Invoice**, **InvoiceItem** - Billing records
- **Estimate**, **EstimateItem** - Estimates
- **Expense** - Expense records
- **Webhook** - Webhook subscriptions

### Read-Only Resources

Fetch and list tests for resources that cannot be created via API:

- **Company** - Account information (singleton)
- **Session** - Current session info
- **Workflow** - Workflow definitions

### Configured Anchor Resources

Tests for resources requiring pre-configured IDs:

- **User** - Cannot delete via API
- **ProjectTemplate** - Project templates
- **InvoiceTemplate** - Invoice templates
- **EstimateTemplate** - Estimate templates
- **ProjectStatus** - Project status definitions

## Directory Structure

```
tests/
├── validate                    # CLI entry point
├── validate-docker             # Docker wrapper for validate
├── bootstrap.php               # Autoloader setup
├── TestConfig.php              # Configuration management
├── ResourceTestRunner.php      # Resource-centric test orchestration
├── ResourceTest.php            # Base class for resource tests
├── TestResult.php              # Result tracking
├── TestOutput.php              # Console output
├── TestLogger.php              # File logging
├── README.md                   # This file
│
├── ResourceTests/              # Per-resource test classes
│   ├── ClientResourceTest.php
│   ├── ProjectResourceTest.php
│   ├── TaskResourceTest.php
│   ├── TimeEntryResourceTest.php
│   ├── InvoiceResourceTest.php
│   ├── CompanyResourceTest.php
│   ├── UserResourceTest.php
│   ├── WorkflowResourceTest.php
│   └── ... (25 resource tests)
│
└── Fixtures/                   # Test helpers
    ├── TestDataFactory.php
    └── CleanupManager.php
```

## Resource-Centric Testing

This test suite uses a **RESOURCE-CENTRIC** approach. For each resource, all tests run together:

1. **Property Discovery** - Compares API response properties to SDK PROP_TYPES
2. **Property Selection** - Verifies each property can be selected
3. **CRUD Tests** - Create, Fetch, Update, List, Delete operations
4. **Where Operations** - Tests WHERE_OPERATIONS filtering
5. **Include Tests** - Validates INCLUDE_TYPES relationships

This approach provides better visibility into resource-level issues and allows testing
specific resources in isolation.

## CI/CD Integration

For automated testing:

```bash
# Run all resources in non-interactive mode
./tests/validate --no-interactive all

# Run all with JSON output for parsing
./tests/validate --no-interactive --json all

# Stop on first failure
./tests/validate --no-interactive --stop-on-fail all

# Run specific resources
./tests/validate --no-interactive Project,Task,Client

# Run with a fresh log
./tests/validate --no-interactive --reset-log all
```

Exit codes:
- `0` - All tests passed
- `1` - One or more tests failed

## Writing Custom Resource Tests

Extend `ResourceTest` for custom resource tests:

```php
<?php
namespace Jcolombo\PaymoApiPhp\Tests\ResourceTests;

use Jcolombo\PaymoApiPhp\Tests\ResourceTest;
use Jcolombo\PaymoApiPhp\Entity\Resource\YourResource;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

class YourResourceTest extends ResourceTest
{
    public function getResourceClass(): string
    {
        return YourResource::class;
    }

    public function getResourceName(): string
    {
        return 'YourResource';
    }

    public function getResourceCategory(): string
    {
        return 'safe_crud';  // or 'read_only', 'configured_anchor'
    }

    protected function createTestResource(): ?AbstractResource
    {
        // Return a new instance of your resource
        // or null if creation is not supported
        $resource = new YourResource();
        $resource->name = $this->factory->uniqueName('YourResource');
        $resource->create();
        return $resource;
    }
}
```

Then register it in `ResourceTestRunner.php`'s `$resourceRegistry` array.

## Cleanup Strategy

Resources are deleted in dependency order:

1. Subtasks, TimeEntries, Comments, Files
2. Tasks, Bookings, TaskAssignments
3. Tasklists, Milestones, Discussions
4. InvoiceItems, InvoicePayments, EstimateItems
5. Invoices, Estimates, Expenses
6. RecurringProfileItems, TaskRecurringProfiles
7. RecurringProfiles, Webhooks
8. Projects, WorkflowStatuses
9. ClientContacts
10. Clients (if not anchored)

## Self-Correcting Execution

The test suite is designed to **never crash or leave orphaned resources**:

### Guaranteed Cleanup
- Uses `try/finally` blocks to ensure cleanup always runs
- Global exception handler catches all errors
- Shutdown function handles fatal PHP errors
- Resources are tracked globally for emergency cleanup

### Resource Tracking Summary
After each test run, a detailed summary shows:

```
Resource Operations:
  + Created: 15
  ~ Updated: 3
  - Deleted: 15
  ! Remaining: 0 (may need manual cleanup)
  ✗ Delete Failures: 0
```

### Cleanup Issues Report
If resources remain after tests, detailed information is provided:

```
Cleanup Issues:
  Resources NOT cleaned up (may exist in Paymo):
    Project: #12345, #12346
    Task: #67890

  Failed delete attempts:
    Invoice #99999: Cannot delete - has payments

  TIP: Check your Paymo account and manually delete test resources
       with the prefix used during testing.
```

### Error Recovery
Even if a test crashes, the suite will:
1. Attempt to clean up all tracked resources
2. Display the resource tracking summary
3. Show which resources may need manual cleanup
4. Exit with proper status code (1 for failure)

## Troubleshooting

### Tests not running
- Ensure `testing.enabled` is `true` in config
- Verify API key is valid
- Check that required anchors are configured

### Cleanup failures
- Check for resources with dependencies
- Run with `--verbose` for detailed error messages
- Some resources may have been deleted manually

### Permission errors
- Ensure API key has appropriate permissions
- Some operations may be restricted by account plan

## License

MIT License - See LICENSE file for details.
