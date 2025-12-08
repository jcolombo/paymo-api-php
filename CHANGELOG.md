# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

---

## [0.6.0] - 2025-12-08

This is a major feature release with new resources, comprehensive test framework, server-side pagination, rate limiting, and extensive API verification fixes.

### Added

#### New Resources
- **Subtask** (`src/Entity/Resource/Subtask.php`) - Full CRUD support for task subtasks
- **RecurringProfile** (`src/Entity/Resource/RecurringProfile.php`) - Invoice recurring profile management
- **RecurringProfileItem** (`src/Entity/Resource/RecurringProfileItem.php`) - Recurring profile line items
- **TaskRecurringProfile** (`src/Entity/Resource/TaskRecurringProfile.php`) - Task recurrence scheduling
- **Webhook** (`src/Entity/Resource/Webhook.php`) - Webhook endpoint management for event notifications

#### Server-Side Pagination
- Added `pagination()` method to `AbstractCollection` for automatic handling of paginated API responses
- Supports configurable page size and automatic page traversal
- Respects API rate limits during pagination

#### Rate Limiting
- **RateLimiter** (`src/Utility/RateLimiter.php`) - Intelligent rate limit handling
  - Automatic retry with exponential backoff on 429 responses
  - Configurable maximum retries and delay strategies
  - Rate limit header parsing and tracking

#### UNSELECTABLE Property Handling
- Added `UNSELECTABLE` constant support to resource classes for fields that exist in API responses but cause HTTP 400 when explicitly selected
- **Client** - Added `image_thumb_large`, `image_thumb_medium`, `image_thumb_small`
- **File** - Added `image_thumb_large`, `image_thumb_medium`, `image_thumb_small`
- **User** - Added comprehensive list including `date_format`, `time_format`, `decimal_sep`, `thousands_sep`, `has_submitted_review`, `image_thumb_*`, `is_online`, `language`, `theme`, `week_start`, `menu_shortcut`, `user_hash`, `annual_leave_days_number`, `password`, `workflows`, `assigned_projects`, `managed_projects`
- **Task** - Added `subtasks_order`
- **Milestone** - Added `linked_tasklists`
- **Expense** - Added `image_thumb_large`, `image_thumb_medium`, `image_thumb_small`

#### Comprehensive Test Framework
- **ResourceTest** base class for standardized resource testing
- **ResourceTestRunner** for orchestrating test execution across all resources
- **TestConfig** for flexible test configuration via JSON
- **TestOutput** for formatted console output with color support
- **TestResult** for tracking test outcomes and statistics
- **TestLogger** for detailed API call logging
- **CleanupManager** for automatic test resource cleanup
- **TestDataFactory** for generating test fixtures
- **DependencyAnalyzer** for determining resource test order
- **TestOwnershipRegistry** for mutation safety (prevents accidental modification of non-test resources)
- **KnownIssuesRegistry** for tracking expected API behaviors and suppressing known issue output
- Individual resource test classes for all 25 testable resources
- Interactive CLI test runner (`tests/validate`)

#### TypeScript Interface Definitions
- **PaymoProjectTemplate** - Project template interface
- **PaymoProjectTemplateTasklist** - Template tasklist interface
- **PaymoProjectTemplateTask** - Template task interface
- **PaymoInvoiceTemplate** - Invoice template interface
- **PaymoEstimateTemplate** - Estimate template interface
- **PaymoInvoiceTemplateGallery** - Invoice template gallery interface
- **PaymoEstimateTemplateGallery** - Estimate template gallery interface
- **PaymoCommentThread** - Comment thread interface
- **PaymoSubtask** - Subtask interface
- **PaymoRecurringProfile** - Recurring profile interface
- **PaymoRecurringProfileItem** - Recurring profile item interface
- **PaymoTaskRecurringProfile** - Task recurring profile interface
- **PaymoWebhook** - Webhook interface

#### Documentation
- **OVERRIDES.md** - Comprehensive documentation of intentional SDK deviations from API:
  - OVERRIDE-006: Client.active intentionally read-only
  - OVERRIDE-007: EstimateItem/InvoiceItem critical property documentation gaps
  - OVERRIDE-008: Missing resource documentation (templates, payments, status)
  - OVERRIDE-009: API response key anomalies (underscores)
  - OVERRIDE-010: Gallery response key anomalies (colon prefix)
  - OVERRIDE-011: Undocumented properties policy
  - OVERRIDE-012: Deprecated property retention (RecurringProfile.language)
  - OVERRIDE-013: Unselectable property handling
- **tests/README.md** - Complete test framework documentation

### Changed

- **AbstractCollection** - Major refactoring to support server-side pagination and improved fetch handling
- **Paymo.php** - Enhanced with rate limit detection, retry logic, and improved error handling
- **RequestAbstraction** - Added pagination parameter support
- **BookingCollection** - Added `date_interval` to allowed filters per official API documentation
- **README.md** - Updated resource count from 33 to 38, added new resources to documentation table

### Fixed

#### Property Type Corrections
- **Invoice.php** - Removed erroneous `invoice_id` from READONLY (copy-paste error from Estimate.php)
- **RecurringProfile.php** - Added missing `language` property to PROP_TYPES and READONLY
- **RecurringProfile.php** - Added `API_RESPONSE_KEY` for underscore response key handling
- **ProjectTemplate.php** - Added missing `project_id` to PROP_TYPES
- **Report.php** - Fixed `start_date`/`end_date` types from `?` to `integer` (API expects Unix timestamps)
- **Report.php** - Removed invalid `||resource:workflowstatus` from `projects` filter type
- **TaskRecurringProfile.php** - Changed `collection:user` to `collection:users` for consistency

#### TypeScript Definition Fixes
- **typescript.data-types.ts** - Fixed `hours_per_date` typo to `hours_per_day` in PaymoBooking
- **typescript.data-types.ts** - Added missing `Guest` option to PaymoUser.type enum

#### EntityMap Configuration Fixes
- **default.paymoapi.config.json** - Removed duplicate entries for projecttemplatetask/projecttemplatetasklist
- **default.paymoapi.config.json** - Fixed gallery collection keys to match API (singular form)
- **default.paymoapi.config.json** - Removed circular collectionKey references from gallery entries
- **default.paymoapi.config.json** - Added `collection: true` to gallery entries to enable list() operations

#### Code Cleanup
- **Estimate.php** - Removed outdated "Undocumented" comments from `discount_amount`, `download_token`
- **EstimateItem.php** - Removed outdated "Undocumented" comment from `estimate_id`
- **Invoice.php** - Reorganized PROP_TYPES to separate documented from undocumented properties
- **InvoiceItem.php** - Removed outdated "Undocumented" comment from `invoice_id`
- **Task.php** - Reorganized READONLY and PROP_TYPES for proper categorization
- **Expense.php** - Fixed PHPDoc: removed non-existent `name` property, corrected read-only annotations
- **User.php** - Updated PHPDoc to include `Guest` in user type descriptions

### Removed

- **TODO-LIST.md** - Removed as functionality has been implemented and tracked via test suite

---

## [0.5.7] - 2025-12-06

### Added
- **CLAUDE.md** - AI assistant guide for developers using the package
  - Connection management and singleton behavior documentation
  - Connection state and settings persistence patterns
  - Complete CRUD operation examples
  - WHERE/HAS condition filtering reference
  - Common usage patterns and recipes
- **PACKAGE-DEV.md** - Comprehensive internal development guide
  - Resource class structure and required constants
  - Property type system reference
  - EntityMap configuration guide
  - Resource-specific behaviors documentation
  - TypeScript definitions maintenance rules
  - Development checklists for new/modified resources
  - File templates for new resources
- **TODO-LIST.md** - Detailed analysis of missing features vs official Paymo API
  - Missing resources: Subtask, RecurringProfile, TaskRecurringProfile, Webhook
  - Missing properties per resource
  - Missing include relationships
  - Bug tracking (typos in constants)

### Changed
- **PHP 7.4 minimum requirement** - Updated from PHP 7.2 to PHP 7.4
- **Method signature flexibility** - Removed strict `int` type hints from `$id` parameters in `Request.php` methods (`fetch()`, `update()`, `upload()`, `delete()`) to accept string IDs from databases
- **Collection fetch flexibility** - Removed `array` type hint from `$where` parameter in `AbstractCollection::fetch()` to allow single `RequestCondition` objects
- Updated Guzzle dependency to `^7.8`
- Added `#[\ReturnTypeWillChange]` attribute for PHP 8.1+ compatibility

### Fixed
- Type compatibility issues when passing non-strict scalar types to methods
- Backward compatibility for existing code that passes string IDs or single conditions

### Documentation
- Enhanced PHPDoc blocks across all resource classes with comprehensive descriptions
- Added official Paymo API documentation links to resource file headers
- Improved code examples in class documentation
- Added property documentation with `@property` annotations

---

## [0.5.6] - 2025-12-05

### Added
- New API route support for public endpoints in `.htaccess`
- Route validation and authorization handling in loader
- Error handling system implementation

### Changed
- Refactored `simplexml_load_file` to `simplexml_load_string` for improved compatibility
- Updated function loading to support v1 functions path
- Replaced `PF_API_V2_METHOD` with `PF_API_METHOD` globally
- Updated PHP version requirement

### Removed
- Unused `error.php` functions and refactored utility usage

### Fixed
- Corrected file and function inclusion paths for consistency

---

## [0.5.5] - 2024-11-20

### Added
- Ability to disable connection logging via configuration

### Changed
- Reformatted start request header to remove prefix for cleaner output

---

## [0.5.4] - 2024-11-20

### Added
- **Logging System**: Comprehensive logging capability for API requests and responses
  - New `Log` utility class (`src/Utility/Log.php`) for structured logging
  - New `MetaData` utility class (`src/Utility/MetaData.php`) for request metadata tracking
  - Configurable log levels and output destinations
  - Logging configuration in `default.paymoapi.config.json`
- `skipCache` option for individual API requests to bypass caching when needed

### Changed
- Refactored `Cache` class with significant improvements for better logging integration
- Updated `Configuration` class to support new logging settings
- Enhanced `Paymo` main class with logging hooks

---

## [0.5.3] - 2023-12-07

### Fixed
- Removed an invalid conditional that caused incorrect behavior
- Replaced incorrect use of `count()` with `strlen()` for string length operations

---

## [0.5.2] - 2023-11-26

### Added
- **Caching System**: Basic in-memory and file-based caching for API responses
  - New `Cache` class (`src/Cache/Cache.php`) for response caching
  - Cache configuration options for TTL and storage method
- `skipCache` option on `fetch()` method to bypass cache for specific requests
- Activity Feed resource support (initial implementation)
- `project_id` parameter support for Tasklist creation

### Changed
- Updated Guzzle dependency to `~7.8`
- Updated all Composer packages to latest compatible versions
- Added request delay support to prevent API rate limiting
- Major refactoring of `Paymo.php` for improved caching integration

### Fixed
- Typo on `invoiced` field in resource definitions
- Property call issues in resource handling

---

## [0.5.1] - 2020-03-28

### Added
- `client_id` filter support for TimeEntry resource queries

### Fixed
- Error with hydrating included/related objects in API responses

---

## [0.5.0] - 2020-03-19

This is the first major feature-complete release of the Paymo API PHP library.

### Added

#### Core Framework
- **Entity System**: Complete object-oriented entity framework
  - `AbstractEntity` base class with property management
  - `EntityCollection` for handling lists of entities
  - `EntityMap` singleton for entity class registration and lookup
- **Request System**: Fluent API request builder
  - `RequestAbstraction` for building complex queries
  - `RequestCondition` for WHERE and HAS filtering
  - `RequestResponse` for standardized response handling
- **Configuration Management**: Flexible configuration system
  - `Configuration` class with file-based and runtime config
  - Support for `hassankhan/config` package for config file parsing
  - `default.paymoapi.config.json` template
- **Data Type Conversion**: Automatic type handling
  - `Converter` utility with support for: `string`, `int`, `float`, `bool`, `date`, `datetime`, `html`, `enum`, `enum_int_list`

#### API Resources (33 Total)
- **Projects & Tasks**
  - `Project` - Full CRUD with all Paymo project properties
  - `ProjectStatus` - Project status management
  - `ProjectTemplate` - Project template handling
  - `ProjectTemplateTask` - Template task definitions
  - `ProjectTemplateTasklist` - Template tasklist definitions
  - `Task` - Complete task management with custom WHERE clause validation
  - `Tasklist` - Tasklist CRUD with `project_id` support
  - `TaskAssignment` (userstasks) - Task-user assignment management
- **Time Tracking**
  - `TimeEntry` - Time entry management with variable create clause requirements
  - `Booking` - Resource booking management
- **Financial**
  - `Invoice` - Invoice creation and management
  - `InvoiceItem` - Invoice line items
  - `InvoicePayment` - Payment tracking
  - `InvoiceTemplate` - Invoice template definitions
  - `InvoiceTemplateGallery` - Invoice template assets
  - `Estimate` - Estimate management
  - `EstimateItem` - Estimate line items
  - `EstimateTemplate` - Estimate templates
  - `EstimateTemplateGallery` - Estimate template assets
  - `Expense` - Expense tracking
- **Users & Clients**
  - `User` - User management with extensive property mapping
  - `Client` - Client entity management
  - `ClientContact` - Client contact records
  - `Company` - Company settings with comprehensive undocumented property mapping
- **Collaboration**
  - `Discussion` - Project discussions
  - `Comment` - Comment entities
  - `CommentThread` - Threaded comment support
  - `Milestone` - Project milestone tracking with `reminder_sent` property
- **Workflows**
  - `Workflow` - Workflow definitions
  - `WorkflowStatus` - Workflow status states with color support
- **Files & Sessions**
  - `File` - File upload and management
  - `Session` - API session handling
- **Reports**
  - `Report` - Basic report generation (read-only)

#### API Operations
- **CRUD Operations**: Full support for Create, Read, Update, Delete
  - `::fetch($id)` - Retrieve single entity by ID
  - `::list()` - Retrieve entity collections with filtering
  - `->save()` - Create new or update existing entities
  - `->delete()` - Remove entities
- **Query Building**
  - `WHERE` clause filtering with validation per entity type
  - `HAS` relationship filtering for deep relation queries
  - `INCLUDE` for eager loading related entities
  - `SELECT` for field limiting
- **Property Features**
  - `CREATEONLY` properties - Fields settable only during creation
  - `READONLY` properties - Server-managed fields
  - In-memory field scrubbing cache for validation performance

#### Utilities
- File and image upload methods via `uploadFile()` and `uploadImage()`
- `flatten()` method on resources and collections for raw `stdClass` export
- Stock color options from Paymo's default color picker
- Request time tracking for performance monitoring

#### Authentication
- API key authentication (primary method)
- Username/password authentication support
- Guzzle HTTP client integration with proper headers

#### Error Handling
- Guzzle `ClientException` handling for 4xx responses
- Guzzle `ServerException` handling for 5xx responses
- Response body validation
- Configurable connection timeout

### Changed
- Reorganized `WHERE` and `HAS` static methods to attach directly to entity classes
- Moved Entity class map into static singleton pattern
- Extracted configuration from constants to dedicated config handler
- Connection timeout now configurable via settings

### Fixed
- Color setting issues on workflow status random colors
- WHERE filter glitches on deep relations
- Copyright formatting in source files

---

## [0.0.1] - 2020-03-02

### Added
- Initial project setup and scaffolding
- Composer package configuration (`jcolombo/paymo-api-php`)
- PSR-4 autoloading under `Jcolombo\PaymoApiPhp` namespace
- MIT license
- Basic library framework structure
- Developer contact information and metadata

### Dependencies
- PHP >= 7.2
- `guzzlehttp/guzzle` - HTTP client
- `hassankhan/config` - Configuration file parsing
- `adbario/php-dot-notation` - Dot notation array access
- `ext-json` - JSON extension

---

[Unreleased]: https://github.com/jcolombo/paymo-api-php/compare/v0.6.0...HEAD
[0.6.0]: https://github.com/jcolombo/paymo-api-php/compare/v0.5.7...v0.6.0
[0.5.7]: https://github.com/jcolombo/paymo-api-php/compare/v0.5.6...v0.5.7
[0.5.6]: https://github.com/jcolombo/paymo-api-php/compare/v0.5.5...v0.5.6
[0.5.5]: https://github.com/jcolombo/paymo-api-php/compare/v0.5.4...v0.5.5
[0.5.4]: https://github.com/jcolombo/paymo-api-php/compare/v0.5.3...v0.5.4
[0.5.3]: https://github.com/jcolombo/paymo-api-php/compare/v0.5.2...v0.5.3
[0.5.2]: https://github.com/jcolombo/paymo-api-php/compare/v0.5.1...v0.5.2
[0.5.1]: https://github.com/jcolombo/paymo-api-php/compare/v0.5.0...v0.5.1
[0.5.0]: https://github.com/jcolombo/paymo-api-php/compare/v0.0.1...v0.5.0
[0.0.1]: https://github.com/jcolombo/paymo-api-php/releases/tag/v0.0.1
