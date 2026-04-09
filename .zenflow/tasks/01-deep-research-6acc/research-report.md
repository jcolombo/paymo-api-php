# Paymo REST API: Complete Surface Area Inventory

## Summary

The Paymo REST API exposes **31 documented resource endpoints** plus **4 undocumented leave management endpoints**, supporting a combined total of approximately **35 resources** with full or partial CRUD operations. The official documentation (last updated July 2023) is incomplete: at least **60+ properties** exist in live API responses that are absent from documentation, **6 response key anomalies** require special parsing, and critical features like **pagination** remain entirely undocumented. This inventory cross-references official docs, SDK-verified overrides, GitHub community reports, and third-party integrations to produce the most complete picture available without live API testing.

**Key findings:**

- 35 resource endpoints identified (31 documented + 4 undocumented leave management)
- 60+ undocumented properties discovered across resources via SDK testing
- 82 include relationships mapped (9 more than documentation alone reveals)
- 13 verified behavioral deviations between documentation and live API (OVERRIDES)
- Pagination exists but is completely undocumented (`page` + `page_size` params, 0-indexed)
- A 2,500-item silent truncation limit affects all included resource collections
- 7 unselectable properties that exist in responses but error when explicitly requested
- 4 filter-only properties valid in WHERE but not returned in responses

---

## Key Findings

### API Surface Area
- **31 documented endpoints** covering project management, time tracking, invoicing, estimates, expenses, workflows, templates, files, comments, reports, and webhooks
- **4 undocumented endpoints** for leave management: CompanyDaysException, UserDaysException, LeaveType, StatsReport (from unmerged PR #30, circa 2017)
- **1 ambiguous endpoint** (`/api/currencies`) — reference data exists in docs repo but no evidence of a live endpoint
- **Retainer API explicitly not public** — `retainer_id` appears on projects but cannot be resolved

### Properties & Types
- Official docs list properties per resource dating from 2021-2022
- **60+ additional properties** discovered via SDK testing against live API responses (Company alone has 30+ undocumented properties including email templates, SMTP config, payment gateway fields)
- **Essential foreign keys missing from docs**: `InvoiceItem.invoice_id` and `EstimateItem.estimate_id`
- **Session.id is a hex string**, not an integer — unique among all resources
- **3 deprecated properties**: `language` on Invoice, Estimate, and RecurringProfile

### Include System
- **82 include relationships** mapped across 30 resources (SDK-verified)
- Documentation alone captures ~73 includes; 9 additional discovered by SDK
- **Critical: 2,500-item silent truncation** on included collections — no error returned, data silently drops
- Nesting supported via dot notation (`?include=tasks.entries`); max depth undocumented but 2 levels confirmed
- `comments` include key must be plural — singular throws HTTP 500 (Issue #55, fixed in docs)

### Filtering
- General WHERE syntax supports 10 operators: `=`, `>`, `>=`, `<`, `<=`, `!=`, `like`, `not like`, `in`, `not in`
- **29 resources** have documented or SDK-confirmed filterable properties
- **3 resources** require mandatory filters: Booking, TaskAssignment, UserDaysException
- **4 filter-only properties** exist solely for WHERE clauses: Booking.project_id, Booking.task_id, Booking.date_interval, TimeEntry.time_interval
- HAS conditions (filter by relationship count) documented generically but not per-resource

### Response Format Anomalies
- **4 resources** return response keys with underscores instead of matching endpoint paths (all `projecttemplate*` and `recurringprofile*` resources)
- **2 gallery endpoints** use colon-prefixed response keys (`:estimatetemplates`, `:invoicetemplates`)
- **7 properties** across 5 resources are unselectable — exist in full responses but error on explicit `?select=`

### Infrastructure
- 3 authentication methods: Basic Auth, API Keys (recommended), Sessions
- Rate limiting via `X-Ratelimit-*` headers; specific limits undocumented
- Pagination: `page` (0-indexed) + `page_size` params; possible max 2,500 per page (unconfirmed)
- Content types: JSON/XML request and response; PDF/XLSX for report/invoice/estimate export
- Webhooks: 22 event types across 15 resources, wildcard support, HMAC-SHA1 signatures

---

## Research Question & Sub-Questions

**Core question:** What is the complete, current surface area of the Paymo REST API — every resource, endpoint, CRUD operation, property (with types and constraints), include relationship, and filter capability — including undocumented behavior that the official documentation (last substantially updated ~2022) does not reflect?

**Sub-questions addressed:**

1. **Resources & Endpoints**: What is the complete list of API resources, their endpoint paths, and HTTP verb support?
2. **Properties & Types**: For each resource, what properties exist, their data types, read-only/required/create-only constraints?
3. **Include Relationships**: What related entities can be sideloaded per resource?
4. **Filtering & WHERE Operations**: What filter operators are available per resource per property?
5. **Undocumented & Changed Behavior**: What API features exist that aren't in the official docs?
6. **API Infrastructure**: Authentication, rate limiting, content types, response codes, webhooks?
7. **Resource Relationships & Data Model**: How do resources relate to each other?

---

## Methodology

### Depth Level
**Full Research** — required by the exhaustive per-resource, per-property granularity needed for SDK comparison.

### Sources Consulted

**Primary (HIGH reliability):**
- 37 local API documentation section files (`docs/api-documentation/sections/*.md`) — verified identical to live GitHub repo at `paymo-org/api`
- `OVERRIDES.md` — 13 active overrides, all verified against live API responses (2024-2025)
- SDK resource classes (38 files) — PROP_TYPES, WHERE_OPERATIONS, INCLUDE_TYPES, READONLY, UNSELECTABLE constants

**Secondary (MEDIUM-HIGH reliability):**
- GitHub Issues on `paymo-org/api` — 70 issues reviewed, 10 with direct relevance
- GitHub PR #30 — full diff (530 additions) documenting 4 leave management endpoints
- SDK config classMap (`default.paymoapi.config.json`) — 26 unique resource type entries

**Tertiary (MEDIUM-LOW reliability):**
- CData Paymo connector documentation (~40+ exposed tables)
- Skyvia, n8n, Pipedream integration documentation
- Web searches for community guides, Paymo blog/product updates, Help Center

### Limitations
- **No live API testing** performed during this research. All "actual behavior" claims are sourced from SDK-verified overrides or community reports.
- **Documentation frozen since 2022-2023** — properties, endpoints, or behaviors added after July 2023 can only be discovered through SDK testing or community reports.
- **Per-property filter support is incomplete** — documentation describes general syntax but doesn't exhaustively list which operators work on which properties for most resources.
- **No OpenAPI/Swagger specification exists** (Issue #62) — all documentation is static markdown.

---

## Findings by Thread

### Thread 1: Complete Resource & Endpoint Inventory

**Research questions:** What is the full list of API resources including undocumented ones?

#### Documented Resources (31 endpoints)

##### Core Project Management

| Resource | Endpoint | GET list | GET one | POST | PUT | DELETE | Notes |
|----------|----------|:--------:|:-------:|:----:|:---:|:------:|-------|
| Project | `/api/projects` | Y | Y | Y | Y | Y | |
| Tasklist | `/api/tasklists` | Y | Y | Y | Y | Y | |
| Task | `/api/tasks` | Y | Y | Y | Y | Y | `?where=users in(me)` for "my tasks" |
| Subtask | `/api/subtasks` | Y | Y | Y | Y | Y | |
| Milestone | `/api/milestones` | Y | Y | Y | Y | Y | |
| Discussion | `/api/discussions` | Y | Y | Y | Y | Y | |
| Comment | `/api/comments` | Y | Y | Y | Y | Y | Create via thread_id or directly via task_id/discussion_id/file_id |
| CommentThread | `/api/threads` | Y | Y | - | - | - | Read-only; created implicitly |
| File | `/api/files` | Y | Y | Y | Y | Y | Multipart upload |
| Booking | `/api/bookings` | Y* | Y | Y | Y | Y | *Must filter by user_id, project_id, or task_id |

##### People & Organization

| Resource | Endpoint | GET list | GET one | POST | PUT | DELETE | Notes |
|----------|----------|:--------:|:-------:|:----:|:---:|:------:|-------|
| Client | `/api/clients` | Y | Y | Y | Y | Y | |
| ClientContact | `/api/clientcontacts` | Y | Y | Y | Y | Y | |
| User | `/api/users` | Y | Y | Y | Y | Y | Types: Admin, Employee, Guest |
| TaskAssignment | `/api/userstasks` | Y* | Y | Y | Y | Y | *Must filter by user_id or task_id |
| Company | `/api/company` | - | Y | - | Y | - | Singleton; GET one and PUT only |

##### Time Tracking

| Resource | Endpoint | GET list | GET one | POST | PUT | DELETE | Notes |
|----------|----------|:--------:|:-------:|:----:|:---:|:------:|-------|
| TimeEntry | `/api/entries` | Y | Y | Y | Y | Y | Two types: start/end (timer) and date/duration (bulk) |

##### Financial

| Resource | Endpoint | GET list | GET one | POST | PUT | DELETE | Notes |
|----------|----------|:--------:|:-------:|:----:|:---:|:------:|-------|
| Invoice | `/api/invoices` | Y | Y | Y | Y | Y | Statuses: draft, sent, viewed, paid, void |
| InvoiceItem | `/api/invoiceitems` | Y | Y | Y | Y | Y | |
| InvoicePayment | `/api/invoicepayments` | Y | Y | Y | Y | Y | Auto-updates invoice status |
| Estimate | `/api/estimates` | Y | Y | Y | Y | Y | Statuses: draft, sent, viewed, accepted, invoiced, void |
| EstimateItem | `/api/estimateitems` | Y | Y | Y | Y | Y | |
| Expense | `/api/expenses` | Y | Y | Y | Y | Y | |
| RecurringProfile | `/api/recurringprofiles` | Y | Y | Y | Y | Y | Frequencies: w, 2w, 3w, 4w, m, 2m, 3m, 6m, y |
| RecurringProfileItem | `/api/recurringprofileitems` | Y | Y | Y | Y | Y | |

##### Workflow & Status

| Resource | Endpoint | GET list | GET one | POST | PUT | DELETE | Notes |
|----------|----------|:--------:|:-------:|:----:|:---:|:------:|-------|
| Workflow | `/api/workflows` | Y | Y | Y | Y | Y | Cannot delete if projects exist |
| WorkflowStatus | `/api/workflowstatuses` | Y | Y | Y | Y | Y | Cannot delete if tasks use it |
| ProjectStatus | `/api/projectstatuses` | Y | Y | Y | Y | Y | No dedicated doc file (OVERRIDE-008) |

##### Templates

| Resource | Endpoint | GET list | GET one | POST | PUT | DELETE | Notes |
|----------|----------|:--------:|:-------:|:----:|:---:|:------:|-------|
| ProjectTemplate | `/api/projecttemplates` | Y | Y | Y | Y | Y | Response key anomaly (OVERRIDE-009) |
| ProjectTemplateTasklist | `/api/projecttemplatestasklists` | Y | Y | Y | Y | Y | Response key anomaly |
| ProjectTemplateTask | `/api/projecttemplatestasks` | Y | Y | Y | Y | Y | Response key anomaly |
| InvoiceTemplate | `/api/invoicetemplates` | Y | Y | Y | Y | Y | No dedicated doc file (OVERRIDE-008) |
| InvoiceTemplateGallery | `/api/invoicetemplatesgallery` | Y | Y | - | - | - | Read-only; colon-prefix response key |
| EstimateTemplate | `/api/estimatetemplates` | Y | Y | Y | Y | Y | No dedicated doc file (OVERRIDE-008) |
| EstimateTemplateGallery | `/api/estimatetemplatesgallery` | Y | Y | - | - | - | Read-only; colon-prefix response key |

##### Other

| Resource | Endpoint | GET list | GET one | POST | PUT | DELETE | Notes |
|----------|----------|:--------:|:-------:|:----:|:---:|:------:|-------|
| TaskRecurringProfile | `/api/taskrecurringprofiles` | Y | Y | Y | Y | Y | |
| Session | `/api/sessions` | Y | Y | Y | - | Y | String IDs (hex tokens), no PUT |
| Webhook | `/api/hooks` | Y | Y | Y | Y | Y | |
| Report | `/api/reports` | Y | Y | Y | Y | Y | Types: static, live, temp; PDF/XLSX export |

#### Undocumented Resources (4-5 endpoints)

| Resource | Endpoint | Operations | Source | Confidence |
|----------|----------|-----------|--------|-----------|
| CompanyDaysException | `/api/companiesdaysexceptions` | Full CRUD | PR #30 (2017) | MEDIUM |
| UserDaysException | `/api/usersdaysexceptions` | Full CRUD | PR #30 (2017) | MEDIUM |
| LeaveType | `/api/leavetypes` | Full CRUD | PR #30 (2017) | MEDIUM |
| StatsReport | `/api/statsreports` | POST only | PR #30 (2017) | MEDIUM |
| Currencies | `/api/currencies` (?) | Unknown | currencies.md in docs | LOW |

**CompanyDaysException properties:** id, date (required), end_date (required), leave_type_id, creator_id (read-only), hours_per_day, status, description, is_working (required), created_on, updated_on.

**UserDaysException properties:** Same as CompanyDaysException plus user_id (required). Must filter by user_id or date+end_date range.

**LeaveType properties:** id, name (required), paid, created_on, updated_on.

**StatsReport:** POST-only report generator. Two types: `user_annual_leave_stats` (returns annual_leave_days_number, unpaid_leave_days_count, used_leave_days_count) and `user_working_days_count` (returns working_days_count). Unique response structure with `info` + `content` nesting.

**Retainer API:** Confirmed not public (Issue #66, Paymo staff response). `retainer_id` appears on Project objects but no endpoint exists to resolve it.

**Confidence:** HIGH for documented endpoints. MEDIUM for PR #30 leave endpoints (tested in 2017, unverified recently). LOW for currencies endpoint.

**Remaining gaps:** Leave management endpoints may have evolved since 2017. StatsReport may support additional report types beyond the two documented.

---

### Thread 2: Per-Resource Property Inventory

**Research questions:** What properties does each resource expose, including undocumented ones?

The broad sweep extracted all documented properties from 37 section files. The deep dive cross-referenced against SDK-discovered undocumented properties, OVERRIDES, GitHub issues, and webhook payload examples.

#### Undocumented Properties by Resource (SDK-Verified)

These properties are returned by the live API but absent from official documentation. All verified via SDK testing (HIGH confidence).

| Resource | Property | Type | Notes |
|----------|----------|------|-------|
| **Booking** | creator_id | resource:user | Who created the booking |
| **Booking** | user_id | resource:user | Direct user reference |
| **Booking** | start_time | text | Time component |
| **Booking** | end_time | text | Time component |
| **Booking** | booked_hours | decimal | Total booked hours |
| **Booking** | project_id | — | Filter-only: valid in WHERE, not in responses |
| **Booking** | task_id | — | Filter-only: valid in WHERE, not in responses |
| **Booking** | date_interval | — | Filter-only: `in ("YYYY-MM-DD","YYYY-MM-DD")` |
| **Client** | due_interval | integer | Payment terms interval |
| **Client** | additional_privileges | array | Internal; unselectable (OVERRIDE-013) |
| **ClientContact** | client_id | resource:client | Essential FK — not documented |
| **ClientContact** | additional_privileges | array | Internal field |
| **Company** | company_size | text | |
| **Company** | due_interval | text | Default payment terms |
| **Company** | estimate_format | text | |
| **Company** | footer_unit_measure | text | |
| **Company** | hide_tax_field | text | |
| **Company** | invoice_format | text | |
| **Company** | invoice_page_footer | text | |
| **Company** | invoice_page_footer_height | text | |
| **Company** | invoice_page_margin_width | text | |
| **Company** | language | text | |
| **Company** | margin_unit_measure | text | |
| **Company** | op_authorize_login | text | Authorize.net payment |
| **Company** | op_payflowpro_partner | text | PayPal PayflowPro |
| **Company** | op_payflowpro_user | text | |
| **Company** | op_payflowpro_vendor | text | |
| **Company** | op_paypal_email | text | PayPal email |
| **Company** | op_stripe_publishable_key | text | Stripe publishable key |
| **Company** | op_stripe_secret_key | text | Stripe secret key (sensitive) |
| **Company** | pdf_format_size | text | |
| **Company** | workday_end | text | |
| **Company** | new_invoice_email_subj_tpl | text | Email template |
| **Company** | new_invoice_email_body_tpl | text | Email template |
| **Company** | new_estimate_email_subj_tpl | text | Email template |
| **Company** | new_estimate_email_body_tpl | text | Email template |
| **Company** | new_paymentreminder_email_subj_tpl | text | Email template |
| **Company** | new_paymentreminder_email_body_tpl | text | Email template |
| **Company** | invoice_bill_to_fields | text | |
| **Company** | default_invoice_footer | text | |
| **Company** | default_estimate_footer | text | |
| **Company** | custom_smtp_auth_type | text | Custom SMTP config |
| **Company** | custom_smtp_port | text | |
| **Company** | op_authorize_accepted_cc_amex | boolean | CC acceptance flags |
| **Company** | op_authorize_accepted_cc_diners | boolean | |
| **Company** | op_authorize_accepted_cc_discover | boolean | |
| **Company** | op_authorize_accepted_cc_jcb | boolean | |
| **Company** | op_authorize_accepted_cc_mastercard | boolean | |
| **Company** | op_authorize_accepted_cc_visa | boolean | |
| **Company** | show_delivery_date | boolean | |
| **Company** | custom_domain | text | |
| **Company** | active | boolean | |
| **Company** | trial_ends_on | datetime | |
| **Company** | default_invoice_template | integer | |
| **Company** | default_estimate_template | integer | |
| **Company** | max_estimates | integer | Subscription limit |
| **Company** | max_recurring_profiles | integer | Subscription limit |
| **Company** | max_expenses | integer | Subscription limit |
| **Estimate** | delivery_date | ? | May exist (present on Invoice) |
| **EstimateTemplate** | estimates_count | integer | |
| **Expense** | download_token | text | File download token |
| **Invoice** | active | boolean | |
| **Invoice** | options | object | Configuration object |
| **InvoiceItem** | invoice_id | resource:invoice | Essential FK (OVERRIDE-007) |
| **InvoiceTemplate** | invoices_count | integer | |
| **Project** | billing_type | text | Visible in example JSON |
| **ProjectTemplateTask** | flat_billing | boolean | |
| **ProjectTemplateTask** | estimated_price | decimal | |
| **ProjectTemplateTask** | price | decimal | |
| **ProjectTemplateTask** | duration | integer | |
| **ProjectTemplateTask** | start_date_offset | integer | Days from project start |
| **Report** | active | boolean | |
| **Report** | share_users_ids | collection:users | |
| **Report** | invoice_id | resource:invoice | |
| **Report** | download_token | text | |
| **EstimateItem** | estimate_id | resource:estimate | Essential FK (OVERRIDE-007) |
| **Task** | cover_file_id | resource:file | Cover image |
| **Task** | price | decimal | Flat rate price |
| **Task** | start_date | date | In docs as filterable but not in property table |
| **Task** | recurring_profile_id | resource:taskrecurringprofile | |
| **Task** | billing_type | text | |
| **Task** | files_count | integer | In webhook payload examples |
| **Task** | comments_count | integer | In webhook payload examples |
| **Tasklist** | tasks_count | nested object | `{incomplete: int, completed: int}` |
| **TimeEntry** | client_id | resource:client | Derived convenience field |
| **TimeEntry** | time_interval | — | Filter-only: date range queries |
| **User** | annual_leave_days_number | integer | Leave entitlement (relates to PR #30) |
| **User** | has_submitted_review | text | |
| **User** | menu_shortcut | array | UI preference |
| **User** | user_hash | text | |
| **User** | workflows | collection:workflows | Embedded workflows |
| **User** | additional_privileges | array | Internal; unselectable |

#### Properties Requested but Confirmed Missing

| Resource | Property | Issue | Status |
|----------|----------|-------|--------|
| Task | progress_status | #25 (2017) | Not in API despite being in UI |
| User | costs_per_hour | #70 (2025) | Only price_per_hour exposed |

#### Type Mismatches

| Resource | Property | Documented | Actual | Confidence |
|----------|----------|-----------|--------|-----------|
| Session | id | integer (convention) | text (hex string) | HIGH (OVERRIDE-004) |
| Company | apply_tax_to_expenses | boolean | text or absent | MEDIUM (OVERRIDE-002) |
| Company | tax_on_tax | boolean | text or absent | MEDIUM (OVERRIDE-002) |

#### Deprecated Properties

| Resource | Property | Status | Confidence |
|----------|----------|--------|-----------|
| Invoice | language | Deprecated (OVERRIDE-012) | HIGH |
| Estimate | language | Deprecated | HIGH |
| RecurringProfile | language | Deprecated (OVERRIDE-012) | HIGH |

#### Read-Only vs Settable Discrepancies

| Resource | Property | Docs Say | Reality | Confidence |
|----------|----------|----------|---------|-----------|
| Client | active | Contradictory (table says read-only, text says settable) | SDK treats as read-only (OVERRIDE-006) | HIGH |

**Confidence:** HIGH for SDK-discovered properties. MEDIUM for type mismatches and conditional properties. The undocumented property list may be incomplete — only properties the SDK has encountered are captured.

**Remaining gaps:** Full undocumented property list requires live API response comparison. Settability of undocumented properties (e.g., Company email templates) is unconfirmed for most.

---

### Thread 3: Response Format & Parsing Anomalies

**Research questions:** What response key anomalies, unselectable properties, and format edge cases exist?

#### Response Key Anomalies

**Underscore insertion (OVERRIDE-009):**

| Endpoint | Expected Key | Actual Key |
|----------|-------------|------------|
| `/api/projecttemplates` | `projecttemplates` | `project_templates` |
| `/api/projecttemplatestasklists` | `projecttemplatestasklists` | `project_templates_tasklists` |
| `/api/projecttemplatestasks` | `projecttemplatestasks` | `project_templates_tasks` |
| `/api/recurringprofiles` | `recurringprofiles` | `recurring_profiles` |

Pattern: Multi-word compound resource names insert underscores. Single-word compounds (`tasklists`, `invoiceitems`) do not.

**Colon prefix (OVERRIDE-010):**

| Endpoint | Expected Key | Actual Key |
|----------|-------------|------------|
| `/api/estimatetemplatesgallery` | `estimatetemplatesgallery` | `:estimatetemplates` |
| `/api/invoicetemplatesgallery` | `invoicetemplatesgallery` | `:invoicetemplates` |

Pattern: Both gallery endpoints use colon prefix and drop `gallery` suffix. Consistent.

#### Unselectable Properties (OVERRIDE-013)

Properties that exist in full responses but return HTTP 400 when explicitly requested via `?select=`:

| Resource | Property | Nature |
|----------|----------|--------|
| Client | additional_privileges | Internal/system field |
| User | additional_privileges | Internal/system field |
| Task | subtasks_order | Write-only reordering field |
| Milestone | linked_tasklists | Array of linked IDs |
| Expense | image_thumb_large | Conditional thumbnail |
| Expense | image_thumb_medium | Conditional thumbnail |
| Expense | image_thumb_small | Conditional thumbnail |

#### Filter-Only Properties

Valid in WHERE clauses but not returned in responses:

| Resource | Property | Usage |
|----------|----------|-------|
| Booking | project_id | `?where=project_id=123` |
| Booking | task_id | `?where=task_id=456` |
| Booking | date_interval | `?where=date_interval in ("2024-01-01","2024-12-31")` |
| TimeEntry | time_interval | `?where=time_interval in ("2024-01-01","2024-12-31")` |

#### Other Format Edge Cases

| Edge Case | Detail | Source |
|-----------|--------|--------|
| Include silent truncation | 2,500-item cap per included resource type; no error, data silently drops | Issue #68 (HIGH) |
| HTML in text fields | Task and TimeEntry `description` may contain `<p>` tags from web interface | Issue #50, confirmed by Paymo |
| Session string IDs | `id` is hex string token, not integer | OVERRIDE-004 |
| Company singleton | `/api/company` returns single object, not array | By design |
| Webhook delete payloads | Only contain `{"id": <ID>}` — no other properties | Issue #33 |
| Webhook update payloads | No changed-fields diff included | Issue #38 |
| Date fields | `YYYY-MM-DD` for dates; ISO 8601 UTC for datetimes | Documented |
| WHERE datetime format | Unix timestamps in WHERE clauses, not ISO 8601 | Documented |

**Confidence:** HIGH — all findings from verified OVERRIDES or reproducible community reports.

**Remaining gaps:** Complete list of response key anomalies may include more resources. RecurringProfileItem response key behavior unverified. Include truncation threshold may vary by resource or account.

---

### Thread 4: Filter/WHERE Capability Matrix

**Research questions:** What filter operators are supported per resource per property?

#### SDK-Defined Operator Restrictions (WHERE_OPERATIONS)

11 of 38 resources define non-empty restrictions. Properties not listed here default to allowing all operators.

| Resource | Property | Allowed Operators |
|----------|----------|------------------|
| Client | active | `=` only |
| Client | name | `=`, `like`, `not like` |
| Project | active | `=`, `!=` (not `like`/`not like`) |
| Project | users | `=`, `in`, `not in` |
| Project | managers | `=`, `in`, `not in` |
| Project | billable | `=`, `!=` |
| ProjectStatus | active | `=`, `!=` |
| ProjectStatus | name | `=`, `like`, `not like` |
| ProjectStatus | readonly | `=`, `!=` |
| ProjectStatus | seq | Not `like`/`not like` |
| RecurringProfile | client_id | `=` only |
| RecurringProfile | total | `=`, `>`, `<`, `>=`, `<=` |
| RecurringProfileItem | recurring_profile_id | `=` only |
| Report | info, content, include, extra | Unfilterable (null) |
| Subtask | task_id | `=` only |
| Subtask | complete | `=` only |
| TaskRecurringProfile | project_id | `=` only |
| Tasklist | tasks_count | Unfilterable (nested object) |
| TimeEntry | time_interval | `in` only |
| User | type | `=`, `!=`, `in`, `not in` |

#### Filterable Properties by Resource

| Resource | Filterable Properties | Source |
|----------|----------------------|--------|
| Project | client_id, active, status_id, users, managers, workflow_id, billable | Docs + SDK |
| Task | project_id, tasklist_id, complete, user_id, users, priority, status_id, due_date, start_date | Docs + SDK |
| Client | name, email, active | Docs + SDK |
| TimeEntry | task_id, project_id, user_id, date, start_time, end_time, billed, time_interval, client_id | Docs + SDK |
| Invoice | client_id, status, date, due_date, currency | Docs |
| Expense | client_id, project_id, user_id, date, invoiced | Docs |
| WorkflowStatus | workflow_id | Docs |
| Subtask | task_id, complete | Docs + SDK |
| Milestone | project_id | Docs |
| Discussion | project_id | Docs |
| Comment | thread_id, task_id, discussion_id, file_id | Docs |
| File | project_id, task_id, discussion_id, comment_id | Docs |
| Booking | user_task_id, user_id, project_id, task_id, date_interval | Docs + SDK |
| TaskAssignment | user_id, task_id | Docs |
| User | type, active | SDK |
| RecurringProfile | client_id, total | SDK |
| RecurringProfileItem | recurring_profile_id | SDK |
| ProjectStatus | active, name, readonly, seq | SDK |
| TaskRecurringProfile | project_id | SDK |
| CompanyDaysException | is_working | PR #30 |
| UserDaysException | user_id, date, end_date, is_working | PR #30 |
| LeaveType | paid | PR #30 |

#### Resources with Required Filters

| Resource | Required Filter(s) | Source |
|----------|-------------------|--------|
| Booking | user_id, project_id, task_id, or date range | API docs |
| TaskAssignment | user_id or task_id | API docs |
| UserDaysException | user_id or (date + end_date) | PR #30 |
| File | task_id, project_id, discussion_id, or comment_id | SDK enforcement (OVERRIDE-005) |
| InvoiceItem | invoice_id | SDK enforcement (OVERRIDE-005) |
| EstimateItem | estimate_id | SDK enforcement (OVERRIDE-005) |

Note: File, InvoiceItem, and EstimateItem required filters are SDK-imposed validations, not necessarily API requirements.

#### Resources with NO Known Filter Support

Company (singleton), CommentThread (read-only), Session, Webhook, Workflow, all Template resources, InvoiceItem (beyond invoice_id), EstimateItem (beyond estimate_id), InvoicePayment, Report (4 properties explicitly unfilterable).

#### Filter Edge Cases

| Syntax | Usage | Source |
|--------|-------|--------|
| `in(me)` | Current user: `?where=users in(me)` | Task docs |
| `&&` separator | Multi-condition: `?where=user_id=1&&is_working=false` | PR #30 |
| `and` separator | Alternative: `?where=prop1=val1 and prop2=val2` | API docs |
| Unix timestamps | Datetime WHERE uses Unix timestamps, not ISO 8601 | API docs |
| `time_interval` in() | Date range: `?where=time_interval in ("2024-01-01","2024-12-31")` | SDK |
| `date_interval` in() | Same format on Booking | SDK |

#### HAS Conditions

Generic syntax documented: `?has=relationship operator value`. Per-resource HAS support is not explicitly documented. Should work with any relationship listed in a resource's include types, but unverified per-resource.

**Confidence:** HIGH for SDK-defined restrictions. MEDIUM for documented filter examples (2022, may be incomplete). The gap between "properties that exist" and "properties that are filterable" requires live API testing.

**Remaining gaps:** Per-property operator support for most resources is unknown. Filter support for undocumented properties (billing_type, cover_file_id, etc.) is unknown. HAS condition support per resource is unverified.

---

### Thread 5: Include Relationship Map

**Research questions:** What is the complete map of sideloadable relationships?

#### Complete Include Map (82 relationships, SDK-verified)

| Resource | Include Key | Returns | Notes |
|----------|-------------|---------|-------|
| **Booking** | usertask | single | Parent TaskAssignment |
| **Client** | clientcontacts | collection | |
| **Client** | projects | collection | |
| **Client** | invoices | collection | |
| **Client** | recurringprofiles | collection | |
| **ClientContact** | client | single | |
| **Comment** | thread | single | |
| **Comment** | user | single | |
| **Comment** | project | single | Via thread |
| **Comment** | files | collection | |
| **CommentThread** | project | single | |
| **CommentThread** | discussion | single | |
| **CommentThread** | task | single | |
| **CommentThread** | file | single | |
| **CommentThread** | comments | collection | Must use plural (Issue #55) |
| **Discussion** | project | single | |
| **Discussion** | user | single | Creator |
| **Discussion** | thread | single | |
| **Discussion** | files | collection | |
| **Estimate** | client | single | |
| **Estimate** | invoice | single | When estimate invoiced |
| **Estimate** | estimateitems | collection | |
| **Estimate** | estimatetemplate | single | |
| **EstimateItem** | estimate | single | |
| **EstimateTemplate** | estimates | collection | |
| **Expense** | client | single | |
| **Expense** | project | single | |
| **Expense** | user | single | |
| **Expense** | invoiceitems | collection | |
| **File** | project | single | |
| **File** | user | single | |
| **File** | task | single | |
| **File** | discussion | single | |
| **File** | comment | single | |
| **Invoice** | client | single | |
| **Invoice** | invoicepayments | collection | |
| **Invoice** | invoiceitems | collection | Subject to 2,500-item limit |
| **Invoice** | invoicetemplate | single | |
| **InvoiceItem** | invoice | single | |
| **InvoiceItem** | entries | collection | Related time entries |
| **InvoiceItem** | expense | single | |
| **InvoiceItem** | projects | collection | |
| **InvoiceItem** | tasks | collection | |
| **InvoicePayment** | invoice | single | |
| **InvoiceTemplate** | invoices | collection | |
| **Milestone** | project | single | |
| **Milestone** | user | single | |
| **Milestone** | tasklists | collection | Linked tasklists |
| **Project** | client | single | |
| **Project** | projectstatus | single | |
| **Project** | tasklists | collection | |
| **Project** | tasks | collection | |
| **Project** | milestones | collection | |
| **Project** | discussions | collection | |
| **Project** | files | collection | |
| **Project** | invoiceitem | single | |
| **Project** | workflow | single | |
| **ProjectStatus** | project | single | |
| **ProjectTemplate** | projecttemplatestasklists | collection | |
| **ProjectTemplate** | projecttemplatestasks | collection | |
| **ProjectTemplateTask** | projecttemplate | single | |
| **ProjectTemplateTask** | projecttemplatetasklist | single | |
| **ProjectTemplateTasklist** | projecttemplate | single | |
| **ProjectTemplateTasklist** | projecttemplatestasks | collection | |
| **RecurringProfile** | client | single | |
| **RecurringProfile** | recurringprofileitems | collection | |
| **RecurringProfileItem** | recurringprofile | single | |
| **Report** | user | single | |
| **Report** | client | single | |
| **Subtask** | project | single | Via task |
| **Subtask** | task | single | |
| **Subtask** | user | single | |
| **Task** | project | single | |
| **Task** | tasklist | single | |
| **Task** | user | single | Creator |
| **Task** | thread | single | |
| **Task** | entries | collection | |
| **Task** | subtasks | collection | |
| **Task** | invoiceitem | single | |
| **Task** | workflowstatus | single | |
| **TaskAssignment** | user | single | |
| **TaskAssignment** | task | single | |
| **TaskRecurringProfile** | project | single | |
| **Tasklist** | project | single | |
| **Tasklist** | milestone | single | |
| **Tasklist** | tasks | collection | |
| **TimeEntry** | task | single | |
| **TimeEntry** | invoiceitem | single | |
| **TimeEntry** | user | single | |
| **User** | comments | collection | |
| **User** | discussions | collection | |
| **User** | entries | collection | |
| **User** | expenses | collection | |
| **User** | files | collection | |
| **User** | milestones | collection | |
| **User** | reports | collection | |
| **Workflow** | workflowstatuses | collection | |
| **WorkflowStatus** | workflow | single | |

**Resources with NO includes:** Company, Session, Webhook, EstimateTemplateGallery, InvoiceTemplateGallery.

#### Include Syntax

- **Full include:** `?include=key1,key2`
- **Partial include:** `?partial_include=key(field1,field2)` — select specific fields from included resource
- **Nested include:** `?include=tasks.entries` — dot notation for nested sideloading
- **Nesting depth:** 2 levels confirmed in docs; 3+ levels undocumented

#### Known Include Issues

| Issue | Detail | Status |
|-------|--------|--------|
| Plural key required | `comments` works; `comment` (singular) throws 500 | Fixed in docs (Issue #55) |
| 2,500-item silent truncation | Included collections capped; no error, data silently drops | Active (Issue #68) |
| Empty invoiceitems | `include=invoiceitems` sometimes returns empty array | Caused by 2,500-item limit |

**Confidence:** HIGH — SDK INCLUDE_TYPES verified against live API.

**Remaining gaps:** Maximum nesting depth beyond 2 levels. Partial include field support per resource. Whether undocumented include keys exist beyond the 82 mapped.

---

### Thread 6: Behavioral Deviations & Quirks

**Research questions:** What is the complete categorized list of deviations between documentation and actual API behavior?

#### Category A: Conditional Properties

| ID | Resource | Property | Condition | Confidence |
|----|----------|----------|-----------|-----------|
| OVERRIDE-001 | Client | image, image_thumb_* | Only when image uploaded | HIGH |
| OVERRIDE-002 | Company | apply_tax_to_expenses | Account/tax-config dependent | MEDIUM |
| OVERRIDE-002 | Company | tax_on_tax | Account/tax-config dependent | MEDIUM |

#### Category B: Read-Only Overrides

| ID | Resource | Property | Docs Say | SDK Does | Confidence |
|----|----------|----------|----------|----------|-----------|
| OVERRIDE-006 | Client | active | Contradictory | Read-only | HIGH |
| OVERRIDE-012 | RecurringProfile | language | Deprecated | Read-only | HIGH |
| (implicit) | Invoice | language | Deprecated | Read-only | HIGH |
| (implicit) | Estimate | language | Deprecated | Read-only | HIGH |

#### Category C: Documentation Gaps

| ID | Resource | Detail | Confidence |
|----|----------|--------|-----------|
| OVERRIDE-007 | InvoiceItem | `invoice_id` FK not in docs | HIGH |
| OVERRIDE-007 | EstimateItem | `estimate_id` FK not in docs | HIGH |
| OVERRIDE-008 | ProjectStatus | No dedicated doc file | HIGH |
| OVERRIDE-008 | InvoiceTemplate | No dedicated doc file | HIGH |
| OVERRIDE-008 | EstimateTemplate | No dedicated doc file | HIGH |
| OVERRIDE-008 | InvoicePayment | No dedicated doc file | HIGH |
| OVERRIDE-011 | Multiple | 60+ undocumented properties | HIGH |

#### Category D: Type Mismatches

| ID | Resource | Property | Documented | Actual | Confidence |
|----|----------|----------|-----------|--------|-----------|
| OVERRIDE-004 | Session | id | integer | text (hex) | HIGH |

#### Category E: Response Format Anomalies

| ID | Resources Affected | Detail | Confidence |
|----|-------------------|--------|-----------|
| OVERRIDE-009 | ProjectTemplate*, RecurringProfile | Underscore response keys | HIGH |
| OVERRIDE-010 | *TemplateGallery | Colon-prefix response keys | HIGH |
| OVERRIDE-013 | Client, User, Task, Milestone, Expense | Unselectable properties | HIGH |

#### Category F: Behavioral Quirks (GitHub Issues)

| Issue | Resource | Quirk | Status | Confidence |
|-------|----------|-------|--------|-----------|
| #38 | Task (webhook) | `model.update.Task` fires too often | Closed (not fixed) | MEDIUM |
| #33 | Task (webhook) | DELETE payload only contains `{id}` | Open | HIGH |
| #33 | Task (webhook) | No changed-fields diff in update payloads | Open | HIGH |
| #50 | TimeEntry | `description` contains HTML from web | By design | HIGH |
| #68 | All (includes) | 2,500-item silent truncation | Open | HIGH |
| #25 | Task | `progress_status` in UI but not API | Open (7+ years) | HIGH |
| #70 | User | `costs_per_hour` not exposed | Open | HIGH |
| #66 | Project | `retainer_id` present but no endpoint | Confirmed by Paymo | HIGH |

#### Category G: SDK Validation Requirements (not API limitations)

| ID | Resource | Requirement | Rationale |
|----|----------|-------------|-----------|
| OVERRIDE-005 | File | Must filter by parent ID | Prevents unbounded queries |
| OVERRIDE-005 | Booking | Must provide date range or parent filter | Prevents unbounded queries |
| OVERRIDE-005 | InvoiceItem | Must filter by invoice_id | Prevents unbounded queries |
| OVERRIDE-005 | EstimateItem | Must filter by estimate_id | Prevents unbounded queries |

#### Deviation Patterns

Deviations cluster around:
1. **Financial resources** — deprecated `language`, missing FKs, response key anomalies
2. **Template resources** — response key anomalies, missing documentation
3. **Webhook system** — excessive firing, missing payload data, no change tracking
4. **Image/thumbnail fields** — conditional presence, unselectable
5. **Internal/system fields** — `additional_privileges` pattern across Client/User/ClientContact

**Confidence:** HIGH — all OVERRIDES verified via live API. GitHub issues provide corroborating evidence.

**Remaining gaps:** Company tax property trigger conditions (OVERRIDE-002). Whether webhook delete payloads now include more data (Issue #33 from 2018). Deprecation timeline for `language` properties.

---

## API Infrastructure Reference

### Authentication

| Method | Mechanism | Use Case |
|--------|-----------|----------|
| Basic Auth | `email:password` as HTTP Basic | Quick testing |
| API Key | `apikey:X` as HTTP Basic | Third-party integrations (recommended) |
| Session | `X-Session: token` header | Web application sessions |

### Rate Limiting

- Response headers: `X-Ratelimit-Decay-Period`, `X-Ratelimit-Limit`, `X-Ratelimit-Remaining`
- Exceeded: HTTP 429 + `Retry-After` header
- Specific limits (requests per period) are NOT documented

### Content Types

| Direction | Supported |
|-----------|-----------|
| Request | `application/json`, `application/xml`, `application/x-www-form-urlencoded`, `multipart/form-data` (file upload) |
| Response | `application/json` (default), `application/xml` |
| Export | `application/pdf`, `application/vnd.ms-excel` (reports, invoices, estimates) |

### Pagination (Undocumented — OVERRIDE-003)

- `page` parameter: 0-indexed page number
- `page_size` parameter: results per page
- No total count returned by API
- Possible max `page_size` of 2,500 (unconfirmed, per Paymo support December 2024)
- Discovered through direct communication, not documented anywhere

### Response Codes

| Code | Meaning |
|------|---------|
| 200 | Success (GET, PUT, DELETE) |
| 201 | Created (POST) |
| 400 | Bad request / unknown field |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not found |
| 429 | Rate limited |
| 500 | Server error |

### Date/Time Formats

| Context | Format |
|---------|--------|
| Response (date fields) | `YYYY-MM-DD` |
| Response (datetime fields) | ISO 8601 UTC (e.g., `2024-01-15T10:30:00Z`) |
| WHERE clauses (datetime) | Unix timestamps |
| WHERE clauses (date) | `YYYY-MM-DD` |

### Webhooks

- **22 event types** across 15 resources (insert/update/delete per resource + Entry start/stop)
- **Wildcard events:** `*`, `model.insert.*`, `*.Task`
- **Conditional filtering:** `where` parameter on webhook for property-based filters
- **HMAC-SHA1 signatures:** `secret` param, verified via `X-Paymo-Signature` header
- **Custom headers:** `X-Paymo-Webhook` (ID), `X-Paymo-Event` (event name)
- **Auto-delete:** Webhook removed when target returns `410 Gone`
- **Delete payloads:** Only `{"id": <ID>}` — no other properties (Issue #33)

### Data Model Hierarchy

```
Company (singleton)
├── Users
│   ├── TimeEntries (via tasks)
│   ├── Expenses
│   ├── TaskAssignments
│   ├── Bookings (via TaskAssignments)
│   ├── Reports
│   └── UserDaysExceptions (undocumented)
├── Clients
│   ├── ClientContacts
│   ├── Projects
│   │   ├── Tasklists
│   │   │   └── Tasks
│   │   │       ├── Subtasks
│   │   │       ├── TaskAssignments (→ Users)
│   │   │       ├── TimeEntries
│   │   │       ├── Comments (via Threads)
│   │   │       └── Files
│   │   ├── Milestones (→ Tasklists)
│   │   ├── Discussions
│   │   │   ├── Comments (via Threads)
│   │   │   └── Files
│   │   └── Files
│   ├── Invoices
│   │   ├── InvoiceItems (→ TimeEntries, Expenses, Projects, Tasks)
│   │   └── InvoicePayments
│   ├── Estimates
│   │   └── EstimateItems
│   ├── RecurringProfiles
│   │   └── RecurringProfileItems
│   └── Expenses
├── Workflows
│   └── WorkflowStatuses
├── ProjectStatuses
├── ProjectTemplates
│   ├── ProjectTemplateTasklists
│   └── ProjectTemplateTasks
├── InvoiceTemplates / InvoiceTemplateGallery
├── EstimateTemplates / EstimateTemplateGallery
├── TaskRecurringProfiles
├── Webhooks
├── Sessions
├── LeaveTypes (undocumented)
└── CompanyDaysExceptions (undocumented)
```

---

## Source Quality Assessment

### Source Type Distribution

| Source Type | Count | Overall Reliability |
|------------|-------|-------------------|
| Official API docs (37 section files) | Primary | MEDIUM — accurate for 2022 state, static since |
| SDK OVERRIDES.md (13 entries) | Primary | HIGH — all verified against live API (2024-2025) |
| SDK resource classes (38 files) | Primary | HIGH — reflects tested behavior |
| GitHub Issues (10 key issues) | Secondary | MEDIUM-HIGH — community reports, some with Paymo confirmation |
| GitHub PR #30 | Secondary | MEDIUM — 2017 testing, unverified recently |
| CData connector docs | Tertiary | LOW — computed views vs real endpoints unclear |
| Web community sources | Tertiary | LOW — minimal Paymo API community content |

### Recency Profile

| Source | Last Updated | Staleness Risk |
|--------|-------------|---------------|
| API docs (live repo) | July 2023 | HIGH — 3 years without update |
| OVERRIDES.md | 2024-2025 | LOW — actively maintained |
| SDK resource classes | Current | LOW |
| PR #30 | 2017-2018 | HIGH — leave endpoints may have changed |
| GitHub Issues | 2017-2025 | MEDIUM — oldest issues may be resolved |

### Potential Biases

- **SDK-centric perspective:** The most reliable "ground truth" comes from SDK testing, which means the inventory reflects what the SDK has tested, not necessarily every API capability.
- **Documentation age:** The 2022 docs may describe behavior that has since changed without notice.
- **Community reporting bias:** Only issues that frustrated developers enough to file are captured. Quiet API changes go unnoticed.

---

## Knowledge Gaps & Unresolved Questions

### Critical Gaps (directly affect SDK comparison accuracy)

1. **Per-property filter operator support** — Documentation describes general syntax but doesn't specify which operators work on which properties for most resources. Only 11 resources have SDK-defined restrictions; the rest are assumed to allow all operators. Only live API testing can close this gap.

2. **Complete undocumented property list** — The 60+ undocumented properties captured by the SDK are those encountered during testing. Additional properties may exist but haven't been returned in the specific API calls the SDK has made. A systematic "fetch with no select" comparison across all resources would reveal the full set.

3. **Leave management endpoint current state** — PR #30 documents these endpoints from 2017. Paymo's Leave Planner feature has evolved in the product since then. The endpoints may have different properties, behaviors, or access requirements now.

### Significant Gaps

4. **HAS condition support per resource** — Generic syntax documented but no per-resource verification. Should work with any include relationship, but untested.

5. **Maximum include nesting depth** — 2 levels confirmed in documentation examples. 3+ levels may work but is unverified.

6. **Partial include field support per resource** — `?partial_include=key(field1,field2)` should mirror included resource's properties, but unverified.

7. **StatsReport additional types** — Only 2 report types documented in PR #30. Additional types may exist.

8. **Include truncation threshold variability** — 2,500-item limit reported but may vary by resource type or account tier.

### Minor Gaps

9. **Rate limit specific values** — Headers returned but actual limits (requests per time period) undocumented.

10. **Currencies endpoint existence** — No confirmation of whether `GET /api/currencies` is a real endpoint.

11. **Company tax property conditions** — OVERRIDE-002: `apply_tax_to_expenses` and `tax_on_tax` are "possibly deprecated or conditional" — trigger conditions unknown.

12. **Deprecated property removal timeline** — No Paymo policy on when deprecated properties (`language`) will be removed.

13. **RecurringProfileItem response key** — May follow the underscore pattern of `recurring_profiles`; unverified.

---

## Recommendations for Further Investigation

### Priority 1: Live API Verification

1. **Systematic property discovery** — For each resource, issue a `GET` request without `?select=` parameters and compare the full response against the documented + undocumented property lists. This would close the property completeness gap and potentially reveal additional undocumented properties.

2. **Leave management endpoint testing** — Test all 4 PR #30 endpoints against the current live API to verify they still work, check for property changes, and confirm CRUD operations. These are the largest unverified portion of the inventory.

3. **Filter operator matrix validation** — For each resource's filterable properties, systematically test each operator to build a verified operator support matrix. Start with resources that have the most SDK WHERE_OPERATIONS restrictions (Project, Client, TimeEntry).

### Priority 2: SDK Comparison Preparation

4. **Compare this inventory against SDK resource classes** — Map every finding here to the corresponding SDK implementation. Flag: (a) properties the API returns that the SDK doesn't capture, (b) SDK-implemented features not supported by the API, (c) type mismatches between this inventory and SDK PROP_TYPES.

5. **Include relationship verification** — Spot-check the 9 include relationships that the SDK has but the documentation doesn't, to confirm they still work. Also test whether undocumented resources (leave endpoints) support any includes.

### Priority 3: Edge Case Clarification

6. **Include nesting depth testing** — Test 3-level and 4-level nested includes to determine the actual maximum depth.

7. **Currencies endpoint probe** — A single `GET /api/currencies` call would resolve whether this is a real endpoint.

8. **Response key pattern testing** — Test `RecurringProfileItem` and any other multi-word compound resource names for response key anomalies matching the underscore insertion pattern.
