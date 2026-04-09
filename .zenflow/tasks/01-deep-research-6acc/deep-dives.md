# Deep Dives: Paymo REST API Complete Inventory

---

## Thread 1: Undocumented Endpoints & Hidden Resources

### Research Questions
1. What are the complete properties, operations (CRUD), and relationships for each undocumented endpoint from PR #30?
2. Is `/api/currencies` a real endpoint? What operations does it support?
3. Which CData entities correspond to real API endpoints vs computed views?
4. Are there any other endpoints discoverable through the SDK classMap, community sources, or third-party integrations?
5. What is the status of the retainer API?

### Findings

#### 1.1 Leave Management Endpoints (PR #30)

PR #30 (opened 2017-07-14, still open/unmerged) documents 4 endpoints related to Paymo's Leave Planner feature. The PR was authored by a community contributor who appears to have tested these endpoints against the live API. Despite being open for 7+ years, it was never merged into official docs. All 4 endpoints support full CRUD.

##### CompanyDaysException (`/api/companiesdaysexceptions`)

**Operations:** GET list, GET single, POST, PUT, DELETE

**Properties:**

| Property | Type | Read-only | Required (create) | Notes |
|----------|------|-----------|-------------------|-------|
| id | integer | Yes | | |
| date | datetime | | Yes | Interval start date |
| end_date | datetime | | Yes | Interval end date |
| leave_type_id | integer | | | FK to LeaveType (optional) |
| creator_id | integer | Yes | | User who created |
| hours_per_day | integer | | | Hours per day in interval |
| status | text | | | "approved", "pending" observed |
| description | text | | | |
| is_working | boolean | | Yes | true = extra working day, false = leave |
| created_on | datetime | Yes | | |
| updated_on | datetime | Yes | | |

**Filter support:** `?where=is_working=false` (leaves), `?where=is_working=true` (extra working days)

**Response key:** `companiesdaysexceptions` (matches endpoint)

##### UserDaysException (`/api/usersdaysexceptions`)

**Operations:** GET list, GET single, POST, PUT, DELETE

**Properties:** Same as CompanyDaysException plus:

| Property | Type | Read-only | Required (create) | Notes |
|----------|------|-----------|-------------------|-------|
| user_id | integer | | Yes | FK to User |

**Filter requirement:** Must supply at least one WHERE condition: `user_id` OR (`date` AND `end_date`). Unfiltered GET will fail.

**Filter syntax note:** Uses `&&` as multi-condition separator: `?where=user_id=[ID]&&is_working=false`

**Type inconsistency:** PR #30 examples show `user_id` as string `"11"` in list response but integer `21` in single-item response. This may be a documentation error or an actual API inconsistency.

**Response key:** `usersdaysexceptions` (matches endpoint)

##### LeaveType (`/api/leavetypes`)

**Operations:** GET list, GET single, POST, PUT, DELETE

**Properties:**

| Property | Type | Read-only | Required (create) | Notes |
|----------|------|-----------|-------------------|-------|
| id | integer | Yes | | |
| name | text | | Yes | e.g., "Vacation", "Sick Leave" |
| paid | boolean | | | Payable status |
| created_on | datetime | Yes | | |
| updated_on | datetime | Yes | | |

**Filter support:** `?where=paid=true`

**Response key:** `leavetypes` (matches endpoint)

##### StatsReport (`/api/statsreports`)

**Operations:** POST only (report generation, not CRUD)

**Two report types:**

**Type 1: `user_annual_leave_stats`**
- Request: `POST /api/statsreports` with body `{"type": "user_annual_leave_stats", "params": {"user_id": ID, "date_interval": "this_year"|"last_year"}}`
- Response fields: `annual_leave_days_number`, `unpaid_leave_days_count`, `used_leave_days_count`
- Prerequisite: `annual_leave_days_number` must be set on Company or User first

**Type 2: `user_working_days_count`**
- Request: `POST /api/statsreports` with body `{"type": "user_working_days_count", "params": {"user_id": ID, "start_date": "YYYY-MM-DD", "end_date": "YYYY-MM-DD"}}`
- Response fields: `working_days_count` (integer)
- Note: PR #30 examples contain a JSON syntax error (unquoted keys) in the `user_working_days_count` example

**Response key:** `statsreports`

**Response structure is unique** - uses `info` (request echo) + `content` (results array) nested objects, unlike standard resource responses.

#### 1.2 Currencies (`/api/currencies`)

**Status: Reference list only, NOT a standard API endpoint**

The `currencies.md` file in the docs repo contains only a static table of 93 currency codes with symbols and descriptions. It is NOT listed in the README's endpoint index. There are no CRUD examples, no property tables, no response format documentation.

**Assessment:** This is a reference document, not an API endpoint specification. The Paymo API uses ISO currency codes for Invoice/Estimate/Expense `currency` fields, and this document lists accepted values. There is no evidence of a `/api/currencies` endpoint supporting GET requests.

**Confidence:** MEDIUM. It's possible a read-only `GET /api/currencies` exists as a lookup endpoint, but there is zero documentation or community evidence of it.

#### 1.3 CData Connector Entities

CData exposes ~40+ Paymo tables/views. Cross-referencing against documented endpoints:

**Confirmed matches (real endpoints):** Projects, Tasks, Tasklists, Clients, Users, Invoices, InvoiceItems, Estimates, EstimateItems, Expenses, TimeEntries, Bookings, Milestones, Comments, Files, Discussions, Subtasks, Webhooks, Workflows, WorkflowStatuses, Reports

**Likely computed views (NOT real endpoints):** ClientTimeEntries, ProjectBookings, TaskBookings — these appear to be CData-constructed views that join standard resources (e.g., Bookings + TaskAssignments + Tasks to produce "TaskBookings"). No evidence of corresponding API endpoints.

**Confidence:** MEDIUM. CData may have proprietary knowledge of undocumented endpoints, but the computed-view pattern is more likely for the unmatched entities.

#### 1.4 SDK ClassMap Coverage

The SDK `default.paymoapi.config.json` classMap defines entries for **26 unique resource types** (excluding collection entries):

| ClassMap Key | SDK Class | Documented? | Notes |
|-------------|-----------|-------------|-------|
| project | Project | Yes | |
| client | Client | Yes | |
| projectstatus | ProjectStatus | **Partial** | No dedicated doc file (OVERRIDE-008), but works |
| user | User | Yes | |
| company | Company | Yes | |
| clientcontact | ClientContact | Yes | |
| workflow | Workflow | Yes | |
| workflowstatus | WorkflowStatus | Yes | |
| milestone | Milestone | Yes | |
| discussion | Discussion | Yes | |
| tasklist | Tasklist | Yes | |
| task | Task | Yes | |
| usertask | TaskAssignment | Yes | |
| booking | Booking | Yes | |
| entry | TimeEntry | Yes | |
| projecttemplate | ProjectTemplate | Yes | |
| projecttemplatestasklist | ProjectTemplateTasklist | Yes | |
| projecttemplatestask | ProjectTemplateTask | Yes | |
| thread | CommentThread | Yes | Read-only |
| comment | Comment | Yes | |
| expense | Expense | Yes | |
| estimatetemplatesgallery | EstimateTemplateGallery | Yes | Read-only |
| estimatetemplate | EstimateTemplate | **Partial** | No dedicated doc file (OVERRIDE-008) |
| estimate | Estimate | Yes | |
| estimateitem | EstimateItem | Yes | |
| file | File | Yes | |
| session | Session | Yes | |
| invoicetemplatesgallery | InvoiceTemplateGallery | Yes | Read-only |
| invoicetemplate | InvoiceTemplate | **Partial** | No dedicated doc file (OVERRIDE-008) |
| invoice | Invoice | Yes | |
| invoiceitem | InvoiceItem | Yes | |
| invoicepayment | InvoicePayment | **Partial** | No dedicated doc file (OVERRIDE-008) |
| report | Report | Yes | |
| subtask | Subtask | Yes | |
| recurringprofile | RecurringProfile | Yes | |
| recurringprofileitem | RecurringProfileItem | Yes | |
| taskrecurringprofile | TaskRecurringProfile | Yes | |
| hook | Webhook | Yes | |

**Not in classMap (no SDK support):** CompanyDaysException, UserDaysException, LeaveType, StatsReport (the 4 PR #30 endpoints)

#### 1.5 Retainer API Status

Per GitHub Issue #66 (2023):
- `retainer_id` appears on Project objects returned by the API
- **No public endpoint exists** for retainer details
- Paymo officially confirmed: "Project Retainer API is not public at the moment"
- A follow-up request in January 2024 received no response
- The `retainer_id` property is available on projects but cannot be resolved to anything meaningful via the API

**Confidence:** HIGH (confirmed by Paymo)

### Source Quality Assessment

| Source | Type | Reliability | Recency | Bias |
|--------|------|-------------|---------|------|
| PR #30 diffs | Community documentation | MEDIUM — author tested against live API, but PR unmerged for 7+ years | 2017 (created), 2018 (last updated) | None apparent |
| GitHub Issue #66 | Official response | HIGH — Paymo staff confirmed | 2023 | None |
| GitHub Issue #68 | Community bug report | HIGH — demonstrates undocumented pagination | 2024 | None |
| currencies.md | Official docs repo | HIGH for currency list, LOW for endpoint status | 2022 | None |
| CData connector | Third-party integration | LOW — computed views vs real endpoints unclear | 2024 | Commercial interest |
| SDK classMap | First-party SDK code | HIGH — represents tested integrations | Current | Developer-authored |

### Knowledge Gaps
1. **Leave management endpoints current status** — PR #30 is from 2017. Do these endpoints still work identically? The Leave Planner feature has evolved in the product since then.
2. **StatsReport additional types** — Only 2 report types documented. Are there others?
3. **Currencies endpoint existence** — No confirmation either way of whether `GET /api/currencies` is a valid endpoint.
4. **annual_leave_days_number property** — PR #30 mentions setting this on Company and User, but it's not in the documented property tables for either resource. The SDK does capture it as undocumented on User.

### Confidence Level: MEDIUM-HIGH
HIGH for documented endpoints and their properties. MEDIUM for undocumented leave endpoints (community-sourced, untested recently). LOW for CData-only entities and currencies endpoint.

---

## Thread 2: Per-Resource Property Audit

### Research Questions
1. For each OVERRIDE, what exactly is the discrepancy between docs and reality?
2. Which properties appear in doc examples/webhook payloads but are absent from property tables?
3. Which GitHub issues report missing, incorrect, or changed properties?
4. Are there type mismatches between what docs say and what OVERRIDES/community report?
5. Which properties are documented as settable but are actually read-only (or vice versa)?
6. Which properties have been deprecated or conditionally present?

### Findings

#### 2.1 Complete Undocumented Properties by Resource (SDK-Discovered)

The SDK captures properties returned by the live API that are not in the official documentation. These are marked with `// Undocumented Props` in the source code and placed in READONLY arrays.

| Resource | Undocumented Property | Type in SDK | Notes |
|----------|----------------------|-------------|-------|
| **Booking** | `creator_id` | resource:user | Who created the booking |
| **Booking** | `user_id` | resource:user | Direct user reference (docs only show user_task_id) |
| **Booking** | `start_time` | text | Time component (exact format unknown) |
| **Booking** | `end_time` | text | Time component (exact format unknown) |
| **Booking** | `booked_hours` | decimal | Total booked hours |
| **Booking** | `project_id` | resource:project | Filter-only: valid for WHERE but not returned in response |
| **Booking** | `task_id` | resource:task | Filter-only: valid for WHERE but not returned in response |
| **Booking** | `date_interval` | text | Filter-only: format `in ("YYYY-MM-DD","YYYY-MM-DD")` |
| **Client** | `due_interval` | integer | Payment terms interval |
| **Client** | `additional_privileges` | array | Internal field (UNSELECTABLE per OVERRIDE-013) |
| **ClientContact** | `client_id` | resource:client | Essential FK — why is this not documented? |
| **ClientContact** | `additional_privileges` | array | Internal field |
| **Company** | `company_size` | text | |
| **Company** | `due_interval` | text | Default payment terms |
| **Company** | `estimate_format` | text | |
| **Company** | `footer_unit_measure` | text | |
| **Company** | `hide_tax_field` | text | |
| **Company** | `invoice_format` | text | |
| **Company** | `invoice_page_footer` | text | |
| **Company** | `invoice_page_footer_height` | text | |
| **Company** | `invoice_page_margin_width` | text | |
| **Company** | `language` | text | |
| **Company** | `margin_unit_measure` | text | |
| **Company** | `op_authorize_login` | text | Online payment: Authorize.net |
| **Company** | `op_payflowpro_partner` | text | Online payment: PayPal PayflowPro |
| **Company** | `op_payflowpro_user` | text | |
| **Company** | `op_payflowpro_vendor` | text | |
| **Company** | `op_paypal_email` | text | Online payment: PayPal |
| **Company** | `op_stripe_publishable_key` | text | Online payment: Stripe |
| **Company** | `op_stripe_secret_key` | text | Online payment: Stripe (sensitive!) |
| **Company** | `pdf_format_size` | text | |
| **Company** | `workday_end` | text | |
| **Company** | `new_invoice_email_subj_tpl` | text | Email template |
| **Company** | `new_invoice_email_body_tpl` | text | Email template |
| **Company** | `new_estimate_email_subj_tpl` | text | Email template |
| **Company** | `new_estimate_email_body_tpl` | text | Email template |
| **Company** | `new_paymentreminder_email_subj_tpl` | text | Email template |
| **Company** | `new_paymentreminder_email_body_tpl` | text | Email template |
| **Company** | `invoice_bill_to_fields` | text | |
| **Company** | `default_invoice_footer` | text | |
| **Company** | `default_estimate_footer` | text | |
| **Company** | `custom_smtp_auth_type` | text | Custom SMTP config |
| **Company** | `custom_smtp_port` | text | |
| **Company** | `op_authorize_accepted_cc_amex` | boolean | CC type acceptance flags |
| **Company** | `op_authorize_accepted_cc_diners` | boolean | |
| **Company** | `op_authorize_accepted_cc_discover` | boolean | |
| **Company** | `op_authorize_accepted_cc_jcb` | boolean | |
| **Company** | `op_authorize_accepted_cc_mastercard` | boolean | |
| **Company** | `op_authorize_accepted_cc_visa` | boolean | |
| **Company** | `show_delivery_date` | boolean | |
| **Company** | `custom_domain` | text | |
| **Company** | `active` | boolean | |
| **Company** | `trial_ends_on` | datetime | |
| **Company** | `default_invoice_template` | integer | |
| **Company** | `default_estimate_template` | integer | |
| **Company** | `max_estimates` | integer | Subscription limit |
| **Company** | `max_recurring_profiles` | integer | Subscription limit |
| **Company** | `max_expenses` | integer | Subscription limit |
| **Estimate** | `delivery_date` | ? | Present in Invoice but missing from Estimate docs — may exist |
| **EstimateTemplate** | `estimates_count` | integer | Count of estimates using this template |
| **Expense** | `download_token` | text | Token for file download |
| **Invoice** | `active` | boolean | |
| **Invoice** | `options` | object | Configuration object |
| **InvoiceItem** | `invoice_id` | resource:invoice | OVERRIDE-007: Essential FK omitted from docs |
| **InvoiceTemplate** | `invoices_count` | integer | Count of invoices using this template |
| **Project** | `billing_type` | text | Visible in example JSON, not in property table |
| **ProjectTemplateTask** | `flat_billing` | boolean | Billing config on template tasks |
| **ProjectTemplateTask** | `estimated_price` | decimal | |
| **ProjectTemplateTask** | `price` | decimal | |
| **ProjectTemplateTask** | `duration` | integer | |
| **ProjectTemplateTask** | `start_date_offset` | integer | Days offset from project start |
| **Report** | `active` | boolean | |
| **Report** | `share_users_ids` | collection:users | Users the report is shared with |
| **Report** | `invoice_id` | resource:invoice | Linked invoice |
| **Report** | `download_token` | text | Token for report download |
| **EstimateItem** | `estimate_id` | resource:estimate | OVERRIDE-007: Essential FK omitted from docs |
| **Task** | `cover_file_id` | resource:file | Cover image file reference |
| **Task** | `price` | decimal | Flat rate price |
| **Task** | `start_date` | date | Start date (in docs as filterable but not in property table) |
| **Task** | `recurring_profile_id` | resource:taskrecurringprofile | Link to recurring profile |
| **Task** | `billing_type` | text | |
| **Tasklist** | `tasks_count` | nested object | `{incomplete: int, completed: int}` |
| **TimeEntry** | `client_id` | resource:client | Derived FK — convenience field |
| **TimeEntry** | `time_interval` | datetime[] | Filter-only property for date range queries |
| **User** | `annual_leave_days_number` | integer | Leave entitlement (relates to PR #30 leave feature) |
| **User** | `has_submitted_review` | text | |
| **User** | `menu_shortcut` | array | UI preference data |
| **User** | `user_hash` | text | |
| **User** | `workflows` | collection:workflows | Embedded workflows collection |
| **User** | `additional_privileges` | array | Internal field (UNSELECTABLE per OVERRIDE-013) |

#### 2.2 Properties in Examples/Webhooks but NOT in Property Tables

| Resource | Property | Where Found | Notes |
|----------|----------|-------------|-------|
| Task | `files_count` | Webhook payload examples | Count of attached files |
| Task | `comments_count` | Webhook payload examples | Count of comments |
| Project | `billing_type` | Example JSON in docs | Billing mode indicator |
| Task | `start_date` | Documented as filterable in examples | Present in filter examples but not property table |

#### 2.3 GitHub Issues Reporting Property Problems

| Issue | Resource | Property | Status | Detail |
|-------|----------|----------|--------|--------|
| #25 | Task | `progress_status` | OPEN (2017) | Field visible in UI (todo/in progress/done/backlog) but NOT exposed via API |
| #70 | User | `costs_per_hour` | OPEN (2025) | `price_per_hour` exists but `costs_per_hour` (internal cost rate) does not. Needed for profitability calculations |
| #66 | Project | `retainer_id` | CLOSED | Property exists in responses but no endpoint to resolve it |

#### 2.4 Type Mismatches

| Resource | Property | Documented Type | Actual Type | Override |
|----------|----------|----------------|-------------|---------|
| Session | `id` | integer (assumed by convention) | text (hex string token) | OVERRIDE-004 |
| Company | `apply_tax_to_expenses` | boolean (in SDK) | text (in SDK PROP_TYPES) or absent | OVERRIDE-002 |
| Company | `tax_on_tax` | boolean (in docs) | text (in SDK PROP_TYPES) or absent | OVERRIDE-002 |

#### 2.5 Read-Only vs Settable Discrepancies

| Resource | Property | Docs Say | SDK Implements | Override | Rationale |
|----------|----------|----------|---------------|----------|-----------|
| Client | `active` | Contradictory: property table says "read-only", text says "update with `active: false`" | Read-only | OVERRIDE-006 | Safety: prevents accidental archive |

### Source Quality Assessment

| Source | Reliability | Recency |
|--------|-------------|---------|
| SDK PROP_TYPES with `// Undocumented Props` | HIGH — tested against live API | Current (2025) |
| OVERRIDES.md | HIGH — verified through testing | Current |
| GitHub Issues | MEDIUM — community reports without official resolution | Various (2017-2025) |
| API docs property tables | MEDIUM — accurate for 2022 state, may be stale | 2022 |
| Webhook payload examples in docs | MEDIUM — may show properties not in property tables | 2022-2023 |

### Knowledge Gaps
1. **Complete undocumented property list for all resources** — Only the SDK-discovered properties are captured. There may be more that the SDK hasn't yet encountered.
2. **Property settability for undocumented properties** — SDK marks most as read-only, but some (like the Company email templates) might actually be settable.
3. **Task `progress_status`** — Confirmed not in API (#25), but the request is unanswered 7+ years. May have been added since.
4. **User `costs_per_hour`** — Explicitly requested but not confirmed to exist in responses (#70).

### Confidence Level: HIGH
SDK-discovered properties have been verified against live API responses. Type information is from direct observation.

---

## Thread 3: Response Format & Parsing Anomalies

### Research Questions
1. What is the complete list of response key anomalies?
2. Is the colon-prefix pattern consistent across all gallery endpoints?
3. What is the complete list of unselectable properties per resource?
4. Are there other parsing edge cases?
5. What patterns exist?

### Findings

#### 3.1 Response Key Anomalies (OVERRIDE-009)

The standard convention is: endpoint path segment = response JSON key. These resources deviate:

| Endpoint | Expected Key | Actual Key | Pattern |
|----------|-------------|------------|---------|
| `/api/projecttemplates` | `projecttemplates` | `project_templates` | Underscore insertion |
| `/api/projecttemplatestasklists` | `projecttemplatestasklists` | `project_templates_tasklists` | Underscore insertion |
| `/api/projecttemplatestasks` | `projecttemplatestasks` | `project_templates_tasks` | Underscore insertion |
| `/api/recurringprofiles` | `recurringprofiles` | `recurring_profiles` | Underscore insertion |

**Pattern analysis:** The anomaly affects multi-word resource names where the API internally uses underscored compound names. Specifically, all `projecttemplate*` resources and `recurringprofile*` resources insert underscores. Single-word compounds (e.g., `tasklists`, `invoiceitems`, `workflowstatuses`) do NOT have this issue.

#### 3.2 Gallery Response Key Anomalies (OVERRIDE-010)

| Endpoint | Expected Key | Actual Key | Pattern |
|----------|-------------|------------|---------|
| `/api/estimatetemplatesgallery` | `estimatetemplatesgallery` | `:estimatetemplates` | Colon prefix + shortened |
| `/api/invoicetemplatesgallery` | `invoicetemplatesgallery` | `:invoicetemplates` | Colon prefix + shortened |

**Pattern:** Both gallery endpoints use a colon (`:`) prefix and drop the `gallery` suffix from the response key. This is consistent across both gallery resources.

#### 3.3 Unselectable Properties (OVERRIDE-013)

Properties that exist in full API responses but return HTTP 400 "Unknown field or reference" when explicitly requested via `?select=`:

| Resource | Property | Nature |
|----------|----------|--------|
| Client | `additional_privileges` | Internal/system field |
| User | `additional_privileges` | Internal/system field |
| Task | `subtasks_order` | Write-only field for reordering subtasks |
| Milestone | `linked_tasklists` | Array of linked tasklist IDs |
| Expense | `image_thumb_large` | Conditional thumbnail URL |
| Expense | `image_thumb_medium` | Conditional thumbnail URL |
| Expense | `image_thumb_small` | Conditional thumbnail URL |

**Pattern:** Two categories: (1) internal fields that the API returns but doesn't officially expose (`additional_privileges`), and (2) conditional or computed properties that can't be individually selected (`image_thumb_*`, `linked_tasklists`, `subtasks_order`).

#### 3.4 Filter-Only Properties

Some properties exist only for filtering — they're valid in WHERE clauses but are NOT returned in response data:

| Resource | Property | Filter Usage |
|----------|----------|-------------|
| Booking | `project_id` | `?where=project_id=123` |
| Booking | `task_id` | `?where=task_id=456` |
| Booking | `date_interval` | `?where=date_interval in ("2024-01-01","2024-12-31")` |
| TimeEntry | `time_interval` | `?where=time_interval in ("2024-01-01","2024-12-31")` |

#### 3.5 Include Hard Limit (Issue #68)

**Critical discovery:** When using `?include=` to sideload related resources, the API silently caps at **2,500 items per resource type**. Beyond this:
- No error is returned
- The included array is silently empty or truncated
- The only fix is to paginate with `page` + `page_size` parameters

This affects any account with large datasets (e.g., many invoiceitems, entries).

#### 3.6 Other Format Edge Cases

| Edge Case | Detail | Source |
|-----------|--------|--------|
| HTML in text fields | Task and TimeEntry `description` fields may contain HTML (`<p>` tags) from web interface | Issue #50 (confirmed by Paymo) |
| Webhook delete payloads | DELETE event payloads only contain `{"id": <ID>}` — no other properties | Issue #33 |
| Webhook update payloads | No changed-fields diff included in update payloads | Issue #38 |
| Session string IDs | Session `id` is a hex string token, not an integer like all other resources | OVERRIDE-004 |
| Singleton response | Company (`/api/company`) returns as single object, not array | By design |
| Date field formats | Date-only fields use `YYYY-MM-DD`, datetime fields use ISO 8601 UTC | Documented |
| WHERE datetime format | Datetime properties in WHERE clauses use Unix timestamps | Documented |

### Source Quality Assessment

| Source | Reliability | Recency |
|--------|-------------|---------|
| OVERRIDES 009, 010, 013 | HIGH — verified via live API testing | 2025 |
| Issue #68 (include limit) | HIGH — reproducible bug report with workaround | 2024 |
| Issue #50 (HTML in descriptions) | HIGH — confirmed by Paymo | 2019 |
| Issue #33, #38 (webhook payloads) | MEDIUM — reported but not officially addressed | 2018 |

### Knowledge Gaps
1. **Complete list of resources with response key anomalies** — Only 6 resources confirmed. Other multi-word resources may have similar issues.
2. **RecurringProfileItem response key** — Does it follow `recurring_profiles` underscore pattern? (SDK classMap shows `recurringprofileitems` as collection key — needs verification)
3. **Include limit exact threshold** — 2,500 reported but may vary by account or resource type.

### Confidence Level: HIGH
All findings are from verified sources (OVERRIDES tested against live API, or reproducible community reports).

---

## Thread 4: Filter/WHERE Capability Matrix

### Research Questions
1. For each resource, which properties are explicitly documented as filterable?
2. Which resources have NO documented filter examples?
3. Are there properties where specific operators are restricted?
4. What HAS conditions are documented per resource?
5. What filter edge cases exist?

### Findings

#### 4.1 SDK WHERE_OPERATIONS Restrictions

The SDK defines `WHERE_OPERATIONS` constants that restrict which operators can be used with specific properties. Only 11 of 38 resources define non-empty restrictions:

| Resource | Property | Allowed Operators | Notes |
|----------|----------|------------------|-------|
| **Client** | `active` | `=` only | Cannot use !=, >, < etc. |
| **Client** | `name` | `=`, `like`, `not like` | Text search supported |
| **Project** | `active` | `=`, `!=` | |
| **Project** | `active` (negative) | NOT `like`, NOT `not like` | `!active` syntax means these operators are forbidden |
| **Project** | `users` | `=`, `in`, `not in` | Array matching |
| **Project** | `managers` | `=`, `in`, `not in` | Array matching |
| **Project** | `billable` | `=`, `!=` | Boolean only |
| **ProjectStatus** | `active` | `=`, `!=` | |
| **ProjectStatus** | `name` | `=`, `like`, `not like` | |
| **ProjectStatus** | `readonly` | `=`, `!=` | |
| **ProjectStatus** | `seq` (negative) | NOT `like`, NOT `not like` | Numeric, text ops forbidden |
| **RecurringProfile** | `client_id` | `=` only | Exact match required |
| **RecurringProfile** | `total` | `=`, `>`, `<`, `>=`, `<=` | Numeric comparison |
| **RecurringProfileItem** | `recurring_profile_id` | `=` only | |
| **Report** | `info` | null (unfilterable) | Complex object |
| **Report** | `content` | null (unfilterable) | Complex object |
| **Report** | `include` | null (unfilterable) | Complex object |
| **Report** | `extra` | null (unfilterable) | Complex object |
| **Subtask** | `task_id` | `=` only | |
| **Subtask** | `complete` | `=` only | |
| **TaskRecurringProfile** | `project_id` | `=` only | |
| **Tasklist** | `tasks_count` | null (unfilterable) | Nested object property |
| **TimeEntry** | `time_interval` | `in` only | Special date range: `in ("2024-01-01","2024-12-31")` |
| **User** | `type` | `=`, `!=`, `in`, `not in` | User type: Admin, Employee, Guest |

#### 4.2 Known Filterable Properties by Resource (from documentation + SDK)

| Resource | Filterable Properties | Source |
|----------|----------------------|--------|
| **Project** | client_id, active, status_id, users, managers, workflow_id, billable | Docs + SDK |
| **Task** | project_id, tasklist_id, complete, user_id, users, priority, status_id, due_date, start_date | Docs + SDK |
| **Client** | name, email, active | Docs + SDK |
| **TimeEntry** | task_id, project_id, user_id, date, start_time, end_time, billed, time_interval, client_id | Docs + SDK |
| **Invoice** | client_id, status, date, due_date, currency | Docs |
| **Expense** | client_id, project_id, user_id, date, invoiced | Docs |
| **WorkflowStatus** | workflow_id | Docs |
| **Subtask** | task_id, complete | Docs + SDK |
| **Milestone** | project_id | Docs |
| **Discussion** | project_id | Docs |
| **Comment** | thread_id, task_id, discussion_id, file_id | Docs |
| **File** | project_id, task_id, discussion_id, comment_id | Docs |
| **Booking** | user_task_id, user_id, project_id, task_id, date_interval | Docs + SDK |
| **TaskAssignment** | user_id, task_id | Docs |
| **User** | type, active | SDK |
| **RecurringProfile** | client_id, total | SDK |
| **RecurringProfileItem** | recurring_profile_id | SDK |
| **ProjectStatus** | active, name, readonly, seq | SDK |
| **TaskRecurringProfile** | project_id | SDK |
| **CompanyDaysException** | is_working | PR #30 |
| **UserDaysException** | user_id, date, end_date, is_working | PR #30 |
| **LeaveType** | paid | PR #30 |

#### 4.3 Resources with NO Documented Filter Examples

These resources have no documented filterable properties and no SDK WHERE_OPERATIONS restrictions:

- Company (singleton — no list endpoint, so no filtering)
- CommentThread (read-only)
- Session
- Webhook
- Workflow
- ProjectTemplate / ProjectTemplateTasklist / ProjectTemplateTask
- InvoiceTemplate / InvoiceTemplateGallery
- EstimateTemplate / EstimateTemplateGallery
- InvoiceItem (SDK enforces `invoice_id` filter, but no documented per-property operators)
- EstimateItem (SDK enforces `estimate_id` filter, but no documented per-property operators)
- InvoicePayment
- Report (4 properties explicitly marked unfilterable — `info`, `content`, `include`, `extra`)

#### 4.4 Resources with Required Filters

| Resource | Required Filter(s) | Source |
|----------|-------------------|--------|
| Booking | `user_id`, `project_id`, `task_id`, or date range | API docs |
| TaskAssignment | `user_id` or `task_id` | API docs |
| File | `task_id`, `project_id`, `discussion_id`, or `comment_id` | SDK enforcement (OVERRIDE-005) |
| InvoiceItem | `invoice_id` | SDK enforcement (OVERRIDE-005) |
| EstimateItem | `estimate_id` | SDK enforcement (OVERRIDE-005) |
| UserDaysException | `user_id` or (`date` + `end_date`) | PR #30 |

Note: File, InvoiceItem, and EstimateItem required filters are SDK-level validations (OVERRIDE-005), not necessarily API requirements. The API may accept unfiltered queries but return very large result sets.

#### 4.5 HAS Conditions

The API supports `has` conditions for filtering by relationship count. The general syntax is documented in `includes.md`:
```
?has=relationship operator value
```

Per-resource HAS support is NOT explicitly documented. The general mechanism works with any included relationship, so HAS conditions should be possible for any relationship listed in a resource's include types. However, this is unverified on a per-resource basis.

#### 4.6 Filter Edge Cases

| Edge Case | Detail | Source |
|-----------|--------|--------|
| `in(me)` syntax | Special value for current user: `?where=users in(me)` | Task docs |
| `&&` separator | Multi-condition separator: `?where=user_id=1&&is_working=false` | PR #30 |
| `and` separator | Alternative: `?where=prop1=val1 and prop2=val2` | API docs |
| Unix timestamps for datetime | WHERE on datetime fields uses Unix timestamps, not ISO 8601 | API docs |
| `time_interval` special format | `?where=time_interval in ("2024-01-01","2024-12-31")` — comma-separated date range in `in()` | SDK |
| `date_interval` on Booking | Same format as `time_interval` | SDK |

### Source Quality Assessment

| Source | Reliability | Recency |
|--------|-------------|---------|
| SDK WHERE_OPERATIONS | HIGH — tested against live API | Current |
| API docs filter examples | MEDIUM — from 2022, may be incomplete | 2022 |
| PR #30 filter examples | MEDIUM — 2017, may have changed | 2017 |

### Knowledge Gaps
1. **Per-property operator support for most resources** — The docs describe general syntax but don't specify which operators work on which properties for most resources. Only 11 resources have SDK-defined restrictions; the rest use default (all operators allowed).
2. **HAS condition support per resource** — General syntax documented but no per-resource verification.
3. **Whether all documented properties are truly filterable** — Some properties may be in property tables but not actually filterable. Only live testing can confirm.
4. **Filter support for undocumented properties** — Can you filter on `billing_type`, `cover_file_id`, etc.? Unknown.

### Confidence Level: MEDIUM-HIGH
SDK WHERE_OPERATIONS are HIGH confidence (tested). Documented filter examples are MEDIUM (may be incomplete). The gap between "all properties that exist" and "all properties that are filterable" requires live API testing to close.

---

## Thread 5: Include Relationship Verification

### Research Questions
1. Is the broad sweep's include map complete?
2. What partial_include fields are supported per resource?
3. What nesting depth is supported?
4. Are there community-reported include issues?
5. Do all documented includes actually work?
6. Are there undocumented includes?

### Findings

#### 5.1 Complete Include Map (SDK INCLUDE_TYPES)

The SDK defines INCLUDE_TYPES for every resource. The boolean value indicates whether the include returns a collection (`true`) or a single object (`false`).

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
| **Estimate** | invoice | single | Created when estimate invoiced |
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

**Resources with NO includes:** Company, Session, Webhook, EstimateTemplateGallery, InvoiceTemplateGallery

**Total include relationships:** 82 (across 30 resources)

#### 5.2 Broad Sweep vs SDK Comparison

Comparing the broad sweep's include map (Section 3.3, ~73 entries) against the SDK's INCLUDE_TYPES (82 entries):

**In SDK but NOT in broad sweep:**
- ProjectStatus → project
- ProjectTemplate → projecttemplatestasklists, projecttemplatestasks
- ProjectTemplateTask → projecttemplate, projecttemplatetasklist
- ProjectTemplateTasklist → projecttemplate, projecttemplatestasks
- RecurringProfileItem → recurringprofile
- TaskRecurringProfile → project
- Report → client

**Assessment:** The broad sweep captured the primary documentation but missed some includes that the SDK discovered through testing. The SDK's INCLUDE_TYPES is the more complete source.

#### 5.3 Nesting Support

The docs confirm nesting syntax: `?include=tasks.entries` (fetch project with tasks, and each task with its entries). However:

- **Maximum depth:** Not explicitly documented. The docs show 2-level nesting in examples. 3+ levels may work but is unconfirmed.
- **Syntax:** Dot notation: `?include=tasks.entries.user`

#### 5.4 Partial Include Support

`?partial_include=key(field1,field2)` allows selecting specific fields from included resources.

Example: `?partial_include=client(id,name)` — only fetch client ID and name when including.

Per-resource partial_include field support is NOT documented — it should mirror the included resource's property list, but this is unverified per-resource.

#### 5.5 Known Include Issues

| Issue | Detail | Status |
|-------|--------|--------|
| Thread include key must be plural | `comments` (plural) works; `comment` (singular) throws 500 | Fixed in docs (Issue #55) |
| 2,500-item include limit | Silent truncation/empty array when included resource count exceeds ~2,500 | Active (Issue #68) |
| Empty invoiceitems | `include=invoiceitems` sometimes returns empty array for invoices that have items | Caused by 2,500-item limit |

### Source Quality Assessment

| Source | Reliability | Recency |
|--------|-------------|---------|
| SDK INCLUDE_TYPES | HIGH — tested against live API | Current |
| API docs include examples | MEDIUM — from 2022, may be incomplete | 2022 |
| Issue #55 (plural key) | HIGH — confirmed and fixed in docs | 2020 |
| Issue #68 (include limit) | HIGH — reproducible | 2024 |

### Knowledge Gaps
1. **Maximum nesting depth** — Only 2-level nesting is documented in examples.
2. **Partial include field support per resource** — Not documented.
3. **Undocumented include keys** — The SDK may not capture all valid include keys, only the ones tested.
4. **Include performance** — No documentation on how includes affect API response time or rate limiting.

### Confidence Level: HIGH
SDK INCLUDE_TYPES are verified against the live API. The 82 include relationships represent the best available map.

---

## Thread 6: Conditional Properties & Behavioral Quirks

### Research Questions
1. What is the complete categorized list of behavioral deviations?
2. For each deviation, what is the confidence level?
3. Are there patterns?
4. Which deviations are confirmed fixed vs still active?
5. Are there behavioral quirks in GitHub issues not captured in OVERRIDES?

### Findings

#### 6.1 Complete Categorized Deviation List

##### Category A: Conditional Properties (present only under certain conditions)

| ID | Resource | Property | Condition | Status | Confidence |
|----|----------|----------|-----------|--------|-----------|
| OVERRIDE-001 | Client | image, image_thumb_large, image_thumb_medium, image_thumb_small | Only when image uploaded | Active | HIGH (tested) |
| OVERRIDE-002 | Company | apply_tax_to_expenses | Possibly deprecated, account-dependent, or tax-config dependent | Active | MEDIUM |
| OVERRIDE-002 | Company | tax_on_tax | Possibly deprecated, account-dependent, or tax-config dependent | Active | MEDIUM |

##### Category B: Read-Only Overrides (documented as settable, but SDK treats as read-only)

| ID | Resource | Property | Docs Say | SDK Does | Reason | Confidence |
|----|----------|----------|----------|----------|--------|-----------|
| OVERRIDE-006 | Client | active | Contradictory: "read-only" in table, "update with active:false" in text | Read-only | Safety: prevents accidental archive | HIGH (intentional design) |
| OVERRIDE-012 | RecurringProfile | language | Deprecated | Read-only | Backwards compatibility | HIGH |
| (implicit) | Invoice | language | Deprecated | Read-only | Same deprecation pattern | HIGH |
| (implicit) | Estimate | language | Deprecated | Read-only | Same deprecation pattern | HIGH |

##### Category C: Documentation Gaps (properties that exist but aren't documented)

| ID | Resource | Property | Detail | Confidence |
|----|----------|----------|--------|-----------|
| OVERRIDE-007 | InvoiceItem | invoice_id | Essential FK, not in docs | HIGH (verified) |
| OVERRIDE-007 | EstimateItem | estimate_id | Essential FK, not in docs | HIGH (verified) |
| OVERRIDE-008 | ProjectStatus | (entire resource) | No dedicated doc file | HIGH (endpoint works) |
| OVERRIDE-008 | InvoiceTemplate | (entire resource) | No dedicated doc file | HIGH (endpoint works) |
| OVERRIDE-008 | EstimateTemplate | (entire resource) | No dedicated doc file | HIGH (endpoint works) |
| OVERRIDE-008 | InvoicePayment | (entire resource) | No dedicated doc file | HIGH (endpoint works) |
| OVERRIDE-011 | Multiple | ~60+ properties | Undocumented properties captured by SDK (see Thread 2) | HIGH (verified) |

##### Category D: Type Mismatches

| ID | Resource | Property | Documented | Actual | Confidence |
|----|----------|----------|-----------|--------|-----------|
| OVERRIDE-004 | Session | id | integer (convention) | text (hex token) | HIGH (verified) |

##### Category E: Response Format Anomalies

| ID | Resource | Detail | Confidence |
|----|----------|--------|-----------|
| OVERRIDE-009 | ProjectTemplate, ProjectTemplateTasklist, ProjectTemplateTask, RecurringProfile | Response keys use underscores not matching endpoint | HIGH (verified) |
| OVERRIDE-010 | EstimateTemplateGallery, InvoiceTemplateGallery | Response keys use colon prefix | HIGH (verified) |
| OVERRIDE-013 | Client, User, Task, Milestone, Expense | Certain properties unselectable via `?select=` | HIGH (verified) |

##### Category F: Behavioral Quirks (from GitHub issues, not in OVERRIDES)

| Issue | Resource | Quirk | Status | Confidence |
|-------|----------|-------|--------|-----------|
| #38 | Task (webhook) | `model.update.Task` fires too often — UI view open/close triggers webhook | Closed (not fixed) | MEDIUM |
| #33 | Task (webhook) | DELETE webhook payload only contains `{id}`, no project_id or tasklist_id | Open (unresolved) | HIGH (confirmed behavior) |
| #33 | Task (webhook) | No changed-fields diff in any webhook update payload | Open | HIGH (confirmed by Paymo) |
| #50 | TimeEntry | `description` field contains HTML from web interface | Closed (by design) | HIGH (confirmed by Paymo) |
| #68 | All (includes) | 2,500-item silent truncation on included resources | Open | HIGH (reproducible) |
| #25 | Task | `progress_status` in UI but not in API | Open (7+ years) | HIGH (confirmed missing) |
| #70 | User | `costs_per_hour` not exposed (only `price_per_hour`) | Open | HIGH (confirmed missing) |
| #66 | Project | `retainer_id` present but no endpoint to resolve it | Closed | HIGH (confirmed by Paymo) |

##### Category G: SDK Validation Requirements (not API limitations)

| ID | Resource | Requirement | Rationale | Confidence |
|----|----------|-------------|-----------|-----------|
| OVERRIDE-005 | File | Must filter by task_id, project_id, discussion_id, or comment_id | Prevents unbounded queries | HIGH (SDK-imposed) |
| OVERRIDE-005 | Booking | Must provide date range OR parent filter | Prevents unbounded queries | HIGH (SDK-imposed) |
| OVERRIDE-005 | InvoiceItem | Must filter by invoice_id | Prevents unbounded queries | HIGH (SDK-imposed) |
| OVERRIDE-005 | EstimateItem | Must filter by estimate_id | Prevents unbounded queries | HIGH (SDK-imposed) |

#### 6.2 Pattern Analysis

**Deviations cluster around:**
1. **Financial resources** — Invoice, Estimate, RecurringProfile have deprecated `language` properties, missing essential FKs (invoice_id, estimate_id), response key anomalies
2. **Template resources** — All template resources (Project, Invoice, Estimate templates) have response key anomalies or missing documentation
3. **Webhook system** — Multiple issues: excessive firing, missing properties in payloads, no change tracking
4. **Image/thumbnail fields** — Conditional presence across Client, Expense; unselectable on Expense
5. **Internal/system fields** — `additional_privileges` on Client/User/ClientContact is a pattern of internal fields being returned but not officially supported

**Deviations by severity:**
- **Breaking (affects parsing):** OVERRIDE-009, 010 (response keys), OVERRIDE-004 (Session string ID)
- **Data completeness:** OVERRIDE-007 (missing FKs), OVERRIDE-008 (missing docs), OVERRIDE-011 (undocumented properties), #33 (webhook gaps)
- **Safety/correctness:** OVERRIDE-006 (active read-only), OVERRIDE-012 (deprecated retention), OVERRIDE-013 (unselectable)
- **Behavioral:** #38 (webhook frequency), #50 (HTML in text), #68 (include truncation)

#### 6.3 Fixed vs Active Status

| ID/Issue | Fixed? | Current Status |
|----------|--------|---------------|
| Issue #55 (plural include key) | YES | Docs updated |
| Issue #50 (HTML in descriptions) | N/A | By design — will not change |
| Issue #38 (webhook firing) | NO | Closed without fix confirmation |
| Issue #33 (webhook payload gaps) | NO | Still open, unresolved |
| Issue #68 (include truncation) | NO | Still open |
| All OVERRIDES (001-013) | N/A | Active — represent current API behavior |

### Source Quality Assessment

| Source | Reliability | Recency |
|--------|-------------|---------|
| OVERRIDES.md (13 entries) | HIGH — all verified via live API | 2024-2025 |
| GitHub issues (10 relevant) | MEDIUM-HIGH — community reports, some with Paymo confirmation | 2017-2025 |
| PR #30 | MEDIUM — 2017 documentation, endpoints may have evolved | 2017 |

### Knowledge Gaps
1. **Company tax properties** — OVERRIDE-002 says "possibly deprecated or conditional" — the actual trigger condition remains unknown.
2. **Webhook comprehensive behavioral map** — Only a few issues reported. There may be more undiscovered quirks.
3. **Whether Task webhook now includes project_id** — Issue #33 from 2018 is still open, but Paymo may have silently fixed it.
4. **Whether deprecated properties will be removed** — No timeline or policy from Paymo on deprecation lifecycle.

### Confidence Level: HIGH
All OVERRIDES are verified. GitHub issues provide corroborating evidence. The categorization is comprehensive for known deviations.

---

## Cross-Thread Connections

1. **Thread 1 → Thread 2:** The undocumented leave endpoints (PR #30) reveal properties not captured in the broad sweep property tables. Specifically, `annual_leave_days_number` on User (found in SDK undocumented props, Thread 2) directly connects to the leave system's stats reports (Thread 1).

2. **Thread 2 → Thread 4:** Filter-only properties (`project_id`, `task_id`, `date_interval` on Booking; `time_interval` on TimeEntry) are in Thread 2's property audit but are critical for Thread 4's filter matrix. These properties are valid for WHERE but not returned in responses.

3. **Thread 3 → Thread 5:** The 2,500-item include limit (Thread 3) directly affects include behavior (Thread 5). Pagination is the mitigation — connecting Thread 3's format anomalies to Thread 1's undocumented pagination feature.

4. **Thread 4 → Thread 6:** SDK WHERE_OPERATIONS restrictions (Thread 4) are SDK-imposed behavioral decisions, related to Thread 6's category G (SDK validation requirements).

5. **Thread 5 → Thread 3:** The `comments` vs `comment` plural/singular issue (Thread 5) is a response format quirk (Thread 3 territory) that affects include behavior.

6. **Thread 6 → Thread 2:** Every conditional property (OVERRIDE-001, 002) and every deprecated property (OVERRIDE-012) affects the property audit baseline. The categories in Thread 6 provide the interpretive framework for Thread 2's raw property data.

---

## Processing Notes

### Threads Completed
All 6 threads were completed in full.

### Sources Consulted
- **OVERRIDES.md** — 13 active overrides, fully processed
- **SDK Resource files** — 38 resource classes, scanned for WHERE_OPERATIONS, INCLUDE_TYPES, UNSELECTABLE, and undocumented properties
- **SDK classMap** — `default.paymoapi.config.json`, 26 unique resource type entries
- **GitHub PR #30** — Full diff (530 additions), 4 new endpoint documentation files
- **GitHub Issues** — Issues #25, #33, #38, #46, #50, #55, #62, #66, #68, #70 fully reviewed
- **currencies.md** — Local docs copy, static reference list
- **Broad sweep** — All prior findings used as baseline

### Context Limitations
None. All threads were completed within a single pass. All sources were accessible and fully processed.

### Confidence Summary

| Thread | Overall Confidence | Notes |
|--------|-------------------|-------|
| 1. Undocumented Endpoints | MEDIUM-HIGH | Documented endpoints HIGH; PR #30 leave endpoints MEDIUM (2017, unverified recently) |
| 2. Property Audit | HIGH | SDK-discovered properties verified against live API |
| 3. Response Format | HIGH | All OVERRIDES verified via testing |
| 4. Filter/WHERE Matrix | MEDIUM-HIGH | SDK restrictions HIGH; per-property support incomplete (requires live testing) |
| 5. Include Relationships | HIGH | SDK INCLUDE_TYPES verified; 82 relationships mapped |
| 6. Behavioral Quirks | HIGH | All categorized deviations verified or sourced |
