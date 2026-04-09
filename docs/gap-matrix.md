---
title: "Paymo API PHP SDK — Gap Matrix"
description: "Comprehensive comparison of SDK coverage against Paymo API documentation"
audience: "developers, ai-agents, package maintainers"
lastUpdated: "2026-04-08"
lastAuditedAgainst: "06cebb9c (v0.6.1)"
status: "current"
---

# Paymo API PHP SDK — Gap Matrix

Formal comparison of SDK resource coverage against the Paymo API documentation and live API behavior. Produced as part of the Documentation Deep Dive audit (task 02).

## How to Read This Document

- **SDK Props**: Number of properties in the resource's `PROP_TYPES` constant
- **API Doc Props**: Number of properties listed in the official Paymo API documentation (frozen since 2022)
- **SDK-Only Props**: Properties in SDK but not in API docs (undocumented API features discovered through live testing)
- **Missing from SDK**: Known API properties not yet in SDK `PROP_TYPES`
- **Override References**: Links to `OVERRIDES.md` entries explaining intentional deviations
- **Data sources**: SDK code (`src/Entity/Resource/*.php`), research report (`.zenflow/tasks/01-deep-research-6acc/research-report.md`), local API docs mirror (`docs/api-documentation/sections/`)

---

## Per-Resource Property Coverage

| Resource | SDK Props | API Doc Props | SDK-Only Props | Missing from SDK | Override References |
|----------|-----------|---------------|----------------|------------------|---------------------|
| Booking | 16 | 9 | 7 | — | — |
| Client | 21 | 15 | 2 | — | OVERRIDE-001, -006, -013 |
| ClientContact | 19 | 15 | 2 | — | — |
| Comment | 9 | 7 | — | — | — |
| CommentThread | 7 | 7 | — | — | — |
| Company | 70+ | 32 | 38+ | — | OVERRIDE-002, -011 |
| Discussion | 8 | 7 | 1 | — | — |
| Estimate | 31 | 25 | 3 | — | — |
| EstimateItem | 10 | 7 | 1 | — | OVERRIDE-007 |
| EstimateTemplate | 9 | ~6 | 1 | — | OVERRIDE-008 |
| EstimateTemplateGallery | 8 | ~6 | — | — | OVERRIDE-010 |
| Expense | 18 | 13 | 1 | — | OVERRIDE-013 |
| File | 21 | 14 | 4 | — | OVERRIDE-013 |
| Invoice | 39 | 30 | 4 | — | OVERRIDE-012 |
| InvoiceItem | 12 | 7 | 1 | — | OVERRIDE-007 |
| InvoicePayment | 7 | 5 | — | — | OVERRIDE-008 |
| InvoiceTemplate | 9 | ~6 | 1 | — | OVERRIDE-008 |
| InvoiceTemplateGallery | 8 | ~6 | — | — | OVERRIDE-010 |
| Milestone | 11 | 9 | 1 | — | OVERRIDE-013 |
| Project | 25 | 22 | 1 | — | — |
| ProjectStatus | 7 | 7 | — | — | OVERRIDE-008 |
| ProjectTemplate | 5 | ~4 | — | — | OVERRIDE-009 |
| ProjectTemplateTask | 16 | 9 | 5 | — | OVERRIDE-009 |
| ProjectTemplateTasklist | 7 | 5 | 1 | — | OVERRIDE-009 |
| RecurringProfile | 32 | 27 | 2 | — | OVERRIDE-009, -012 |
| RecurringProfileItem | 10 | 8 | — | — | — |
| Report | 22+ | 20+ | 4 | — | — |
| Session | 6 | 5 | — | — | OVERRIDE-004 |
| Subtask | 11 | 8 | 2 | — | — |
| Task | 29 | 22 | 5 | 2 (files_count, comments_count from webhooks) | OVERRIDE-011, -013 |
| TaskAssignment | 7 | 5 | 2 | — | — |
| TaskRecurringProfile | 33 | ~25 | several | — | — |
| Tasklist | 8 | 6 | 1 | — | — |
| TimeEntry | 17 | 14 | 2 | — | — |
| User | 34 | 22 | 6 | — | OVERRIDE-013 |
| Webhook | 8 | 7 | — | — | — |
| Workflow | 5 | 4 | — | — | — |
| WorkflowStatus | 8 | 7 | — | — | — |

### Notable Gaps

- **Company**: 38+ undocumented properties covering email templates, SMTP settings, payment gateway configuration, and account limits. The API returns these properties but the official documentation lists only 32.
- **User**: 20 UNSELECTABLE properties (OVERRIDE-013) — preferences, internal fields, and thumbnails that appear in full responses but cannot be explicitly selected.
- **Task**: 2 properties (`files_count`, `comments_count`) observed in webhook payloads but not yet in SDK PROP_TYPES.

---

## CRUD Operation Coverage

| Resource | API Operations | SDK Operations | Restrictions |
|----------|---------------|----------------|--------------|
| Booking | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| Client | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| ClientContact | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| Comment | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| CommentThread | GET list, GET one, DELETE | fetch, list, delete | create() throws RuntimeException; update() not supported (all properties READONLY) |
| Company | GET one, PUT | fetch, update | list(), create(), delete() throw RuntimeException |
| Discussion | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| Estimate | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| EstimateItem | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| EstimateTemplate | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| EstimateTemplateGallery | GET list, GET one | fetch, list | create(), update(), delete() throw RuntimeException |
| Expense | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| File | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| Invoice | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| InvoiceItem | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| InvoicePayment | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| InvoiceTemplate | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| InvoiceTemplateGallery | GET list, GET one | fetch, list | create(), update(), delete() throw RuntimeException |
| Milestone | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| Project | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| ProjectStatus | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| ProjectTemplate | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| ProjectTemplateTask | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| ProjectTemplateTasklist | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| RecurringProfile | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| RecurringProfileItem | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| Report | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| Session | GET list, GET one, POST, DELETE | fetch, list, create, delete | update() throws RuntimeException |
| Subtask | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| Task | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| TaskAssignment | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| TaskRecurringProfile | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| Tasklist | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| TimeEntry | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| User | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| Webhook | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| Workflow | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |
| WorkflowStatus | GET list, GET one, POST, PUT, DELETE | fetch, list, create, update, delete | — |

---

## INCLUDE_TYPES Coverage

All 98 include relationships defined in SDK `INCLUDE_TYPES` constants are present and functional. No gaps found between documented API includes and SDK support.

| Resource | Include Count | Example Includes |
|----------|--------------|------------------|
| Project | 9 | client, projectstatus, tasklists, tasks, milestones, discussions, files, invoiceitem, workflow |
| Task | 8 | project, tasklist, user, thread, entries, subtasks, invoiceitem, workflowstatus |
| Client | 3 | projects, contacts, invoices |
| Invoice | 3 | client, invoiceitems, payments |
| Estimate | 3 | client, estimateitems, project |
| User | 2 | assigned_projects, managed_projects |
| Discussion | 1 | comments |
| Comment | 1 | thread |
| Milestone | 1 | project |
| Tasklist | 1 | project |

Resources with no includes: Booking, ClientContact, Company, CommentThread, Expense, File, InvoiceItem, InvoicePayment, InvoiceTemplate, InvoiceTemplateGallery, EstimateItem, EstimateTemplate, EstimateTemplateGallery, ProjectStatus, ProjectTemplate, ProjectTemplateTask, ProjectTemplateTasklist, RecurringProfileItem, Report, Session, Subtask, TaskAssignment, TaskRecurringProfile, TimeEntry, Webhook, Workflow, WorkflowStatus.

---

## WHERE_OPERATIONS Coverage

11 of 38 resources define non-empty `WHERE_OPERATIONS` restrictions. All match the research report's documented API filter behavior. No discrepancies found.

Resources with WHERE restrictions limit which operators can be used on specific properties. For example, a property might only support `=` (equality) rather than the full set of operators (`=`, `!=`, `>`, `<`, `>=`, `<=`, `like`, `not like`, `in`, `not in`, `range`).

Resources with no `WHERE_OPERATIONS` restrictions accept the default operator set for all filterable properties.

---

## Undocumented API Features Supported by SDK

These features work in the live Paymo API but are not described in the official API documentation (frozen since 2022).

### Pagination (OVERRIDE-003)

The API accepts `page` and `page_size` query parameters for paginated responses. Discovered through direct communication with Paymo support in December 2024. The SDK exposes this via the `limit()` method on collections.

```php
$invoices = Invoice::list()->limit(100)->fetch();         // page 0, 100 results
$invoices = Invoice::list()->limit(2, 50)->fetch();       // page 2, 50 per page
```

### UNSELECTABLE Properties (OVERRIDE-013)

32 properties across 6 resources appear in API responses but cannot be explicitly selected via the `select` query parameter (HTTP 400). The SDK's `UNSELECTABLE` constant on each affected resource prevents these properties from being included in select queries.

### Filter-Only Properties

4 properties are valid in WHERE clauses but not returned in API responses:
- `Booking.project_id`, `Booking.task_id`, `Booking.date_interval`
- `TimeEntry.time_interval`

### Response Key Anomalies (OVERRIDE-009, OVERRIDE-010)

Several resources use non-standard response keys in the API JSON response. The SDK's `EntityMap` configuration handles the mapping automatically:
- ProjectTemplate, ProjectTemplateTask, ProjectTemplateTasklist use keys that differ from their API path
- RecurringProfile uses `recurringprofiles` (not `recurring_profiles`)
- Gallery resources use `estimatetemplatesgallery` / `invoicetemplatesgallery`

### Parent Filter Requirements (OVERRIDE-005)

Some collections require parent filters when listing:
- `File` requires `project_id`
- `Booking` requires a date range (`start_date` AND `end_date`) OR a user/task/project ID
- `InvoiceItem` requires `invoice_id`
- `EstimateItem` requires `estimate_id`

### Undocumented Properties Per Resource

The SDK includes properties discovered through live API testing that are not in the official documentation:
- **Company**: 38+ properties (email templates, SMTP config, payment gateways, limits)
- **User**: 6 additional properties (annual_leave_days_number, has_submitted_review, menu_shortcut, user_hash, workflows, additional_privileges)
- **Booking**: 7 additional properties (creator_id, user_id, start_time, end_time, booked_hours, project_id, task_id)
- **Client**: 2 additional properties (due_interval, additional_privileges)
- **Invoice**: 4 additional properties (delivery_date, download_token, token, active)
- **Task**: 5 additional properties (cover_file_id, price, start_date, recurring_profile_id, billing_type)

---

## API Features Not Yet in SDK

| Feature | Source | Description |
|---------|--------|-------------|
| Leave management endpoints (4) | Research report (Paymo PR #30) | Annual leave, sick leave, and leave type management |
| StatsReport endpoint | Research report (Paymo PR #30) | Statistical reporting endpoint |
| `partial_include` syntax | Research report Thread 5 | Request partial fields from included relationships |
| Nested include dot notation | API docs (includes section) | Include nested relationships like `tasks.entries` |
| Report PDF/XLSX export | API docs (reports section) | Export reports to PDF or XLSX format via dedicated endpoint |
| Webhook conditional filtering builder | API docs (webhooks section) | SDK has `where` property on Webhook but no builder for constructing webhook filter conditions |

---

## Discrepancies Between SDK and API Docs

All known discrepancies are intentional and documented in `OVERRIDES.md`. The SDK currently has 13 active override entries:

| Override | Summary |
|----------|---------|
| OVERRIDE-001 | Client `due_interval` property exists in API but not in docs |
| OVERRIDE-002 | Company has 38+ undocumented properties |
| OVERRIDE-003 | Pagination via `page`/`page_size` is undocumented |
| OVERRIDE-004 | Session uses string IDs (not integer) |
| OVERRIDE-005 | Collection parent filter requirements |
| OVERRIDE-006 | Client `fax` property deprecated by API |
| OVERRIDE-007 | EstimateItem/InvoiceItem parent ID properties missing from docs |
| OVERRIDE-008 | Four resources have no API documentation pages |
| OVERRIDE-009 | Response key anomalies for template resources |
| OVERRIDE-010 | Gallery resource response key anomalies |
| OVERRIDE-011 | Task/Company undocumented properties |
| OVERRIDE-012 | Invoice/RecurringProfile `options` JSON field |
| OVERRIDE-013 | 32 UNSELECTABLE properties across 6 resources |

Each override entry in `OVERRIDES.md` contains the discovery date, evidence, SDK implementation details, and affected code locations.
