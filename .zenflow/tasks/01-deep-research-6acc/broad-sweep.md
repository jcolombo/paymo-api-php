# Broad Sweep: Paymo REST API Complete Inventory

## 1. Landscape Overview

The Paymo REST API is a JSON/XML RESTful API serving as the programmatic interface to Paymo's project management, time tracking, invoicing, and resource scheduling platform. The API base URL is `https://app.paymoapp.com/api/` (SSL/TLS 1.2 only).

The official documentation lives at `github.com/paymo-org/api` (formerly `paymoapp/api` — GitHub redirects the old URL). The docs were last substantively updated in mid-2023 (webhooks.md update, July 2023), with the core resource documentation dating from 2021–2022. The API itself continues to evolve with new features in the Paymo product (retainers, leave management, improved billing) that are NOT reflected in the public documentation.

### API Surface Area Summary

| Category | Count | Details |
|----------|-------|---------|
| **Documented resource endpoints** | 30 | Listed in README |
| **Documented + unlisted endpoints** | 31 | +currencies (exists in repo but not README) |
| **Undocumented endpoints (confirmed)** | 4–5 | companiesdaysexceptions, usersdaysexceptions, leavetypes, statsreports, possibly retainers |
| **SDK classMap entity types** | 38+ | Some map to sub-resources or gallery variants |
| **Webhook event types** | 22 | Covering 8 resource types × CRUD + Entry start/stop |
| **Authentication methods** | 3 | Basic Auth, API Keys, Sessions |
| **Content types** | 4 request / 4 response | JSON, XML, form-urlencoded, multipart / JSON, XML, PDF, XLSX |

---

## 2. Key Players & Perspectives

### Official Documentation (Primary Source)
- **37 section files** in `docs/api-documentation/sections/` covering all documented resources
- **README.md** lists 30 endpoint sections (excludes currencies.md, Bookings listed in README, and infrastructure docs)
- Documentation is static markdown on GitHub — no versioning, no changelog, no OpenAPI/Swagger spec
- GitHub issue #62 requests machine-readable docs (Swagger/Postman) — no response from Paymo

### SDK (OVERRIDES.md) — Tested Behavioral Knowledge
- **13 active overrides** documenting verified deviations between docs and actual API behavior
- These represent the most reliable source of "ground truth" for API behavior since they're validated against live responses

### Community (GitHub Issues)
- **70 issues** on the repo (mix of bug reports, feature requests, documentation corrections)
- Key themes: missing properties, undocumented endpoints, documentation errors, feature requests
- Paymo responds selectively — some issues have official responses, many are unanswered

### Third-Party Integrations
- **CData** exposes ~40+ Paymo tables/views through their connector, suggesting awareness of undocumented resources
- **n8n** and **Pipedream** use generic HTTP request nodes (no dedicated Paymo nodes with pre-built resource lists)
- **Skyvia** supports Paymo data import/export with incremental replication

---

## 3. Established Knowledge: Complete Resource Inventory

### 3.1 Resources & Endpoints

#### Core Project Management

| Resource | Endpoint | GET list | GET single | POST | PUT | DELETE | Notes |
|----------|----------|----------|------------|------|-----|--------|-------|
| **Project** | `/api/projects` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **Tasklist** | `/api/tasklists` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **Task** | `/api/tasks` | ✅ | ✅ | ✅ | ✅ | ✅ | Also: `/api/tasks?where=users in(me)` for "my tasks" |
| **Subtask** | `/api/subtasks` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **Milestone** | `/api/milestones` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **Discussion** | `/api/discussions` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **Comment** | `/api/comments` | ✅ | ✅ | ✅ | ✅ | ✅ | Can create via thread_id or directly via task_id/discussion_id/file_id |
| **CommentThread** | `/api/threads` | ✅ | ✅ | ❌ | ❌ | ❌ | Read-only; created implicitly |
| **File** | `/api/files` | ✅ | ✅ | ✅ (upload) | ✅ | ✅ | Multipart upload; also via task file upload |
| **Booking** | `/api/bookings` | ✅ (WHERE required) | ✅ | ✅ | ✅ | ✅ | Must filter by user_id, project_id, or task_id |

#### People & Organization

| Resource | Endpoint | GET list | GET single | POST | PUT | DELETE | Notes |
|----------|----------|----------|------------|------|-----|--------|-------|
| **Client** | `/api/clients` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **ClientContact** | `/api/clientcontacts` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **User** | `/api/users` | ✅ | ✅ | ✅ | ✅ | ✅ | Types: Admin, Employee, Guest |
| **TaskAssignment** | `/api/userstasks` | ✅ (WHERE required) | ✅ | ✅ | ✅ | ✅ | Must filter by user_id or task_id |
| **Company** | `/api/company` | ❌ (singleton) | ✅ | ❌ | ✅ | ❌ | Single company per account; GET and PUT only |

#### Time Tracking

| Resource | Endpoint | GET list | GET single | POST | PUT | DELETE | Notes |
|----------|----------|----------|------------|------|-----|--------|-------|
| **TimeEntry** | `/api/entries` | ✅ | ✅ | ✅ | ✅ | ✅ | Two types: start/end time entries and date/duration (bulk) entries |

#### Financial

| Resource | Endpoint | GET list | GET single | POST | PUT | DELETE | Notes |
|----------|----------|----------|------------|------|-----|--------|-------|
| **Invoice** | `/api/invoices` | ✅ | ✅ | ✅ | ✅ | ✅ | Statuses: draft, sent, viewed, paid, void |
| **InvoiceItem** | `/api/invoiceitems` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **InvoicePayment** | `/api/invoicepayments` | ✅ | ✅ | ✅ | ✅ | ✅ | Auto-updates invoice status when payments = total |
| **Estimate** | `/api/estimates` | ✅ | ✅ | ✅ | ✅ | ✅ | Statuses: draft, sent, viewed, accepted, invoiced, void |
| **EstimateItem** | `/api/estimateitems` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **Expense** | `/api/expenses` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **RecurringProfile** | `/api/recurringprofiles` | ✅ | ✅ | ✅ | ✅ | ✅ | Frequencies: w, 2w, 3w, 4w, m, 2m, 3m, 6m, y |
| **RecurringProfileItem** | `/api/recurringprofileitems` | ✅ | ✅ | ✅ | ✅ | ✅ | |

#### Workflow & Status

| Resource | Endpoint | GET list | GET single | POST | PUT | DELETE | Notes |
|----------|----------|----------|------------|------|-----|--------|-------|
| **Workflow** | `/api/workflows` | ✅ | ✅ | ✅ | ✅ | ✅ | Cannot delete if projects exist |
| **WorkflowStatus** | `/api/workflowstatuses` | ✅ | ✅ | ✅ | ✅ | ✅ | Cannot delete if tasks use it |
| **ProjectStatus** | `/api/projectstatuses` | ✅ | ✅ | ✅ | ✅ | ✅ | |

#### Templates

| Resource | Endpoint | GET list | GET single | POST | PUT | DELETE | Notes |
|----------|----------|----------|------------|------|-----|--------|-------|
| **ProjectTemplate** | `/api/projecttemplates` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **ProjectTemplateTasklist** | `/api/projecttemplatestasklists` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **ProjectTemplateTask** | `/api/projecttemplatestasks` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **InvoiceTemplate** | `/api/invoicetemplates` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **InvoiceTemplateGallery** | `/api/invoicetemplatesgallery` | ✅ | ✅ | ❌ | ❌ | ❌ | Read-only gallery |
| **EstimateTemplate** | `/api/estimatetemplates` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **EstimateTemplateGallery** | `/api/estimatetemplatesgallery` | ✅ | ✅ | ❌ | ❌ | ❌ | Read-only gallery |

#### Recurring & Scheduling

| Resource | Endpoint | GET list | GET single | POST | PUT | DELETE | Notes |
|----------|----------|----------|------------|------|-----|--------|-------|
| **TaskRecurringProfile** | `/api/taskrecurringprofiles` | ✅ | ✅ | ✅ | ✅ | ✅ | Frequency: daily, weekly, monthly |

#### Other

| Resource | Endpoint | GET list | GET single | POST | PUT | DELETE | Notes |
|----------|----------|----------|------------|------|-----|--------|-------|
| **Session** | `/api/sessions` | ✅ | ✅ | ✅ | ❌ | ✅ | String IDs (hex tokens), no PUT |
| **Webhook** | `/api/hooks` | ✅ | ✅ | ✅ | ✅ | ✅ | |
| **Report** | `/api/reports` | ✅ | ✅ | ✅ | ✅ | ✅ | Types: static, live, temp; PDF/XLSX export |
| **Currencies** | `/api/currencies` (?) | ? | ? | ? | ? | ? | Reference list in docs; not listed in README; possibly read-only lookup |

### 3.2 Properties & Types (Per Resource)

#### Project
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| code | text | | | | Custom project code |
| task_code_increment | integer | ✅ | | | Auto-incrementing task code counter |
| description | text | | | | |
| client_id | integer | | | | |
| status_id | integer | | | | Links to ProjectStatus |
| active | boolean | | | | Derived from status_id |
| color | text | | | | Hex color |
| users | array of integers | | | | User IDs assigned |
| managers | array of integers | | | | Manager user IDs |
| billable | boolean | | | | |
| flat_billing | boolean | | | | true = flat rate, false = time & materials |
| price_per_hour | decimal | | | | For time & materials projects |
| price | decimal | | | | For flat rate projects |
| estimated_price | decimal | | | | |
| hourly_billing_mode | text | | | | Billing hierarchy |
| budget_hours | decimal | | | | |
| adjustable_hours | boolean | | | | |
| invoiced | boolean | ✅ | | | |
| invoice_item_id | integer | ✅ | | | |
| workflow_id | integer | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |
| billing_type | text | ? | | | Visible in example JSON but NOT in property table — undocumented |

#### Task
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| code | text | ✅ | | | Auto-generated from project code |
| project_id | integer | | ✅* | | *Required if tasklist_id not provided |
| tasklist_id | integer | | ✅* | | *Required if project_id not provided |
| seq | integer | | | | Position in tasklist |
| description | text | | | | May contain HTML (confirmed in issue #50) |
| complete | boolean | | | | |
| completed_on | datetime | ✅ | | | |
| completed_by | integer | ✅ | | | User ID |
| due_date | date | | | | |
| start_date | date | | | | |
| user_id | integer | ✅ | | | Creator |
| users | array of integers | | | | Assigned users |
| billable | boolean | | | | |
| flat_billing | boolean | | | | |
| price_per_hour | decimal | | | | |
| budget_hours | decimal | | | | |
| estimated_price | decimal | | | | |
| invoiced | boolean | ✅ | | | |
| invoice_item_id | integer | ✅ | | | |
| priority | integer | | | | Values: 25(Low), 50(Normal), 75(High), 100(Critical) |
| status_id | integer | | | | Links to WorkflowStatus |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |
| files_count | integer | ✅ | | | Visible in webhook payload example |
| comments_count | integer | ✅ | | | Visible in webhook payload example |

#### Client
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| address | text | | | | |
| city | text | | | | |
| postal_code | text | | | | |
| country | text | | | | |
| state | text | | | | |
| phone | text | | | | |
| fax | text | | | | |
| email | text | | | | |
| website | text | | | | |
| active | boolean | ✅* | | | *Docs say settable; SDK overrides to read-only (OVERRIDE-006) |
| fiscal_information | text | | | | |
| image | text | | | | Upload path |
| image_thumb_large | text | ✅ | | | Conditional — only when image uploaded (OVERRIDE-001) |
| image_thumb_medium | text | ✅ | | | Conditional (OVERRIDE-001) |
| image_thumb_small | text | ✅ | | | Conditional (OVERRIDE-001) |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### User
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | | | |
| email | text | | ✅ | | |
| type | text | | | | Admin, Employee, Guest |
| active | boolean | | | | |
| timezone | text | | | | |
| phone | text | | | | |
| skype | text | | | | |
| position | text | | | | |
| workday_hours | decimal | | | | |
| price_per_hour | decimal | | | | |
| image | text | | | | |
| image_thumb_large | text | ✅ | | | |
| image_thumb_medium | text | ✅ | | | |
| image_thumb_small | text | ✅ | | | |
| date_format | text | | | | |
| time_format | text | | | | |
| decimal_sep | text | | | | |
| thousands_sep | text | | | | |
| week_start | text | | | | |
| language | text | | | | |
| theme | text | | | | |
| assigned_projects | array | ✅ | | | |
| managed_projects | array | ✅ | | | |
| is_online | boolean | ✅ | | | |
| password | text | | | | Write-only |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### TimeEntry (Entry)
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| project_id | integer | ✅ | | | Derived from task |
| task_id | integer | | ✅ | | |
| user_id | integer | | | | Defaults to authenticated user |
| is_bulk | boolean | ✅ | | | true if date/duration type |
| start_time | datetime | | ✅* | | *For timer/range entries |
| end_time | datetime | | ✅* | | *For timer/range entries; null = running timer |
| date | date | | ✅* | | *For bulk entries |
| duration | integer | | ✅* | | *Seconds; for bulk entries |
| description | text | | | | May contain HTML |
| added_manually | boolean | ✅ | | | |
| billed | boolean | | | | |
| invoice_item_id | integer | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Invoice
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| number | text | | | | Auto-generated if not provided |
| client_id | integer | | ✅ | | |
| template_id | integer | | | | |
| status | text | | | | draft, sent, viewed, paid, void |
| currency | text | | ✅ | | ISO currency code |
| date | date | | | | |
| due_date | date | | | | |
| subtotal | decimal | ✅ | | | |
| total | decimal | ✅ | | | |
| tax | decimal | | | | Tax percentage |
| tax2 | decimal | | | | Second tax percentage |
| tax_amount | decimal | ✅ | | | |
| tax2_amount | decimal | ✅ | | | |
| discount | decimal | | | | |
| discount_amount | decimal | ✅ | | | |
| tax_on_tax | boolean | | | | |
| language | text | | | | Deprecated (OVERRIDE-012) |
| bill_to | text | | | | |
| company_info | text | | | | |
| footer | text | | | | |
| notes | text | | | | Internal notes |
| outstanding | decimal | ✅ | | | |
| tax_text | text | | | | |
| tax2_text | text | | | | |
| discount_text | text | | | | |
| title | text | | | | |
| delivery_date | date | | | | |
| pay_online | boolean | | | | |
| reminder_1_sent | boolean | ✅ | | | |
| reminder_2_sent | boolean | ✅ | | | |
| reminder_3_sent | boolean | ✅ | | | |
| permalink | text | ✅ | | | |
| pdf_link | text | ✅ | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### InvoiceItem
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| invoice_id | integer | | | | Not in docs but exists (OVERRIDE-007) |
| item | text | | | | Line item name |
| description | text | | | | |
| price_unit | decimal | | | | |
| quantity | decimal | | | | |
| expense_id | integer | | | | |
| apply_tax | boolean | | | | |
| seq | integer | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### InvoicePayment
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| invoice_id | integer | | ✅ | | |
| amount | decimal | | ✅ | | |
| date | date | | | | |
| notes | text | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Estimate
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| number | text | | | | |
| client_id | integer | | ✅ | | |
| template_id | integer | | | | |
| status | text | | | | draft, sent, viewed, accepted, invoiced, void |
| currency | text | | ✅ | | |
| date | date | | | | |
| due_date | date | | | | |
| subtotal | decimal | ✅ | | | |
| total | decimal | ✅ | | | |
| tax | decimal | | | | |
| tax2 | decimal | | | | |
| tax_amount | decimal | ✅ | | | |
| tax2_amount | decimal | ✅ | | | |
| discount | decimal | | | | |
| discount_amount | decimal | ✅ | | | |
| tax_on_tax | boolean | | | | |
| language | text | | | | Deprecated |
| bill_to | text | | | | |
| company_info | text | | | | |
| footer | text | | | | |
| notes | text | | | | Internal notes |
| brief_description | text | | | | Shows above/below items (PR #56 confirmed) |
| tax_text | text | | | | |
| tax2_text | text | | | | |
| discount_text | text | | | | |
| title | text | | | | |
| invoice_id | integer | ✅ | | | Created when estimate is invoiced |
| permalink | text | ✅ | | | |
| pdf_link | text | ✅ | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### EstimateItem
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| estimate_id | integer | | | | Not in docs but exists (OVERRIDE-007) |
| item | text | | | | |
| description | text | | | | |
| price_unit | decimal | | | | |
| quantity | decimal | | | | |
| apply_tax | boolean | | | | |
| seq | integer | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Expense
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| client_id | integer | | ✅ | | |
| project_id | integer | | | | |
| user_id | integer | ✅ | | | |
| amount | decimal | | ✅ | | |
| currency | text | | ✅ | | |
| date | date | | | | |
| notes | text | | | | |
| invoiced | boolean | ✅ | | | |
| invoice_item_id | integer | ✅ | | | |
| tags | list | | | | |
| file | text | | | | Upload |
| image_thumb_large | text | ✅ | | | |
| image_thumb_medium | text | ✅ | | | |
| image_thumb_small | text | ✅ | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### RecurringProfile
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| client_id | integer | | ✅ | | |
| template_id | integer | | | | |
| title | text | | | | |
| currency | text | | ✅ | | |
| subtotal | decimal | ✅ | | | |
| total | decimal | ✅ | | | |
| tax | decimal | | | | |
| tax2 | decimal | | | | |
| tax_amount | decimal | ✅ | | | |
| tax2_amount | decimal | ✅ | | | |
| discount | decimal | | | | |
| discount_amount | decimal | ✅ | | | |
| discount_text | text | | | | |
| tax_on_tax | boolean | | | | |
| start_date | date | | ✅ | | |
| frequency | text | | ✅ | | w, 2w, 3w, 4w, m, 2m, 3m, 6m, y |
| occurrences | integer | | | | 0 = unlimited |
| last_created | date | ✅ | | | |
| invoices_created | integer | ✅ | | | |
| autosend | boolean | | | | |
| language | text | | | | Deprecated |
| bill_to | text | | | | |
| company_info | text | | | | |
| footer | text | | | | |
| notes | text | | | | |
| tax_text | text | | | | |
| tax2_text | text | | | | |
| pay_online | boolean | | | | |
| send_attachment | boolean | | | | |
| options | object | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### RecurringProfileItem
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| recurring_profile_id | integer | | | | |
| item | text | | | | |
| description | text | | | | |
| price_unit | decimal | | | | |
| quantity | decimal | | | | |
| apply_tax | boolean | | | | |
| seq | integer | | | | |

#### Tasklist
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| seq | integer | | | | |
| project_id | integer | | ✅ | | |
| milestone_id | integer | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Milestone
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| project_id | integer | | ✅ | | |
| user_id | integer | | | | |
| due_date | date | | ✅ | | |
| send_reminder | boolean | | | | |
| reminder_sent | boolean | ✅ | | | |
| complete | boolean | | | | |
| linked_tasklists | array | | | | Array of tasklist IDs |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Discussion
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| description | text | | | | HTML |
| project_id | integer | | ✅ | | |
| user_id | integer | ✅ | | | Creator |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Comment
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| content | text | | ✅ | | HTML |
| thread_id | integer | | | | One of thread_id/task_id/discussion_id/file_id required |
| user_id | integer | ✅ | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### CommentThread (Thread)
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| project_id | integer | ✅ | | | |
| discussion_id | integer | ✅ | | | |
| task_id | integer | ✅ | | | |
| file_id | integer | ✅ | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### File
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| original_filename | text | ✅ | | | |
| description | text | | | | |
| user_id | integer | ✅ | | | |
| project_id | integer | | | | |
| discussion_id | integer | | | | |
| task_id | integer | | | | |
| comment_id | integer | | | | |
| token | text | ✅ | | | |
| size | integer | ✅ | | | |
| file | text | ✅ | | | URL |
| image_thumb_large | text | ✅ | | | |
| image_thumb_medium | text | ✅ | | | |
| image_thumb_small | text | ✅ | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Booking
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| user_task_id | integer | | ✅ | | References TaskAssignment |
| start_date | date | | ✅ | | |
| end_date | date | | ✅ | | |
| hours_per_day | decimal | | ✅ | | |
| description | text | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### ClientContact
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| client_id | integer | | ✅ | | |
| name | text | | ✅ | | |
| email | text | | | | |
| mobile | text | | | | |
| phone | text | | | | |
| fax | text | | | | |
| skype | text | | | | |
| notes | text | | | | |
| position | text | | | | |
| is_main | boolean | | | | |
| access | boolean | | | | |
| image | text | | | | |
| image_thumb_large | text | ✅ | | | |
| image_thumb_medium | text | ✅ | | | |
| image_thumb_small | text | ✅ | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Subtask
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| complete | boolean | | | | |
| project_id | integer | ✅ | | | Derived from task |
| user_id | integer | | | | |
| task_id | integer | | ✅ | | |
| seq | integer | | | | |
| completed_on | datetime | ✅ | | | |
| completed_by | integer | ✅ | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Workflow
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| is_default | boolean | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### WorkflowStatus
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| workflow_id | integer | | ✅ | | |
| color | text | | ✅ | | Hex RGB |
| seq | integer | ✅ | | | Position |
| action | text | ✅ | | | "backlog" or "complete" for special statuses |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### ProjectStatus
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| active | boolean | | | | Whether projects with this status are active |
| seq | integer | | | | |
| readonly | boolean | ✅ | | | System statuses |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### TaskAssignment (UsersTask)
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| user_id | integer | | ✅ | | |
| task_id | integer | | ✅ | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Session
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | text | ✅ | | | String hex token, not integer (OVERRIDE-004) |
| ip | text | ✅ | | | |
| expires_on | datetime | ✅ | | | |
| user_id | integer | ✅ | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Webhook (Hook)
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| target_url | text | | ✅ | | |
| event | text | | ✅ | | Supports wildcards |
| where | text | | | | Filter condition |
| secret | text | | | | Write-only; used for HMAC-SHA1 signatures |
| last_status_code | integer | ✅ | | | Reset on update |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Report
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | | | |
| user_id | integer | ✅ | | | |
| type | text | | | | static, live, temp |
| start_date | date | | | | |
| end_date | date | | | | |
| date_interval | text | | | | last_month, this_month, etc. |
| projects | array | | | | Project IDs to include |
| clients | array | | | | Client IDs to include |
| users | array | | | | User IDs to include |
| include | object | | | | Complex include configuration |
| extra | object | | | | Extra report options |
| info | object | ✅ | | | Report metadata |
| content | object | ✅ | | | Report data (static only) |
| permalink | text | ✅ | | | |
| shared | boolean | | | | |
| share_client_id | integer | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Company (Singleton)
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | | | |
| address | text | | | | |
| phone | text | | | | |
| email | text | | | | |
| url | text | | | | |
| fiscal_information | text | | | | |
| country | text | | | | |
| image | text | | | | |
| image_thumb_large | text | ✅ | | | |
| image_thumb_medium | text | ✅ | | | |
| image_thumb_small | text | ✅ | | | |
| timezone | text | | | | |
| default_currency | text | | | | |
| default_price_per_hour | decimal | | | | |
| apply_tax_to_expenses | boolean | | | | Possibly deprecated/conditional (OVERRIDE-002) |
| tax_on_tax | boolean | | | | Possibly deprecated/conditional (OVERRIDE-002) |
| date_format | text | | | | |
| time_format | text | | | | |
| decimal_sep | text | | | | |
| thousands_sep | text | | | | |
| week_start | text | | | | |
| working_days | object | | | | |
| workday_hours | decimal | | | | |
| online_payments | boolean | ✅ | | | |
| max_users | integer | ✅ | | | Subscription limit |
| max_projects | integer | ✅ | | | Subscription limit |
| max_invoices | integer | ✅ | | | Subscription limit |
| account_type | text | ✅ | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### Template Resources (InvoiceTemplate, EstimateTemplate)
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| title | text | | | | |
| html | text | | | | Template HTML |
| css | text | | | | Template CSS |
| is_default | boolean | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |
| image | text | ✅ | | | Gallery variants only |

#### ProjectTemplate
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### ProjectTemplateTasklist
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| seq | integer | | | | |
| template_id | integer | | ✅ | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### ProjectTemplateTask
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| template_id | integer | | ✅ | | |
| tasklist_id | integer | | ✅ | | |
| seq | integer | | | | |
| description | text | | | | |
| billable | boolean | | | | |
| budget_hours | decimal | | | | |
| price_per_hour | decimal | | | | |
| users | array | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

#### TaskRecurringProfile
| Property | Type | Read-only | Required (create) | Create-only | Notes |
|----------|------|-----------|-------------------|-------------|-------|
| id | integer | ✅ | | | |
| name | text | | ✅ | | |
| project_id | integer | | ✅* | | *Or task_id |
| tasklist_id | integer | | | | |
| user_id | integer | ✅ | | | Creator |
| task_user_id | integer | | | | |
| company_id | integer | ✅ | | | |
| billable | boolean | | | | |
| flat_billing | boolean | | | | |
| description | text | | | | |
| price_per_hour | decimal | | | | |
| estimated_price | decimal | | | | |
| budget_hours | decimal | | | | |
| users | array | | | | |
| priority | integer | | | | |
| notifications | boolean | | | | |
| frequency | text | | ✅ | | daily, weekly, monthly |
| interval | integer | | ✅ | | |
| on_day | integer | | | | |
| occurrences | integer | | | | |
| until | date | | | | |
| active | boolean | | | | |
| due_date_offset | integer | | | | Days after creation |
| recurring_start_date | date | | ✅ | | |
| generated_count | integer | ✅ | | | |
| last_generated_on | datetime | ✅ | | | |
| next_processing_date | date | ✅ | | | |
| processing_timezone | text | | | | |
| processing_hour | integer | | | | |
| created_on | datetime | ✅ | | | |
| updated_on | datetime | ✅ | | | |

### 3.3 Include Relationships

| Resource | Include Key | Relationship | Type |
|----------|-------------|--------------|------|
| **Project** | client | parent | single |
| **Project** | projectstatus | parent | single |
| **Project** | tasklists | child | collection |
| **Project** | tasks | child (via tasklists) | collection |
| **Project** | milestones | child | collection |
| **Project** | discussions | child | collection |
| **Project** | files | child | collection |
| **Project** | invoiceitem | related | single |
| **Project** | workflow | parent | single |
| **Task** | project | parent | single |
| **Task** | tasklist | parent | single |
| **Task** | user | creator | single |
| **Task** | thread | related | single |
| **Task** | entries | child | collection |
| **Task** | subtasks | child | collection |
| **Task** | invoiceitem | related | single |
| **Task** | workflowstatus | related | single |
| **Tasklist** | project | parent | single |
| **Tasklist** | milestone | parent | single |
| **Tasklist** | tasks | child | collection |
| **Client** | clientcontacts | child | collection |
| **Client** | projects | child | collection |
| **Client** | invoices | child | collection |
| **Client** | recurringprofiles | child | collection |
| **ClientContact** | client | parent | single |
| **User** | comments | child | collection |
| **User** | discussions | child | collection |
| **User** | entries | child | collection |
| **User** | expenses | child | collection |
| **User** | files | child | collection |
| **User** | milestones | child | collection |
| **User** | reports | child | collection |
| **TimeEntry** | task | parent | single |
| **TimeEntry** | invoiceitem | related | single |
| **TimeEntry** | user | parent | single |
| **Invoice** | client | parent | single |
| **Invoice** | invoiceitems | child | collection |
| **Invoice** | invoicepayments | child | collection |
| **Invoice** | invoicetemplate | parent | single |
| **InvoiceItem** | invoice | parent | single |
| **InvoiceItem** | entries | related | collection |
| **InvoiceItem** | expense | related | single |
| **InvoiceItem** | projects | related | collection |
| **InvoiceItem** | tasks | related | collection |
| **InvoicePayment** | invoice | parent | single |
| **Estimate** | client | parent | single |
| **Estimate** | invoice | related | single |
| **Estimate** | estimateitems | child | collection |
| **Estimate** | estimatetemplate | parent | single |
| **EstimateItem** | estimate | parent | single |
| **Expense** | client | parent | single |
| **Expense** | project | parent | single |
| **Expense** | user | parent | single |
| **Expense** | invoiceitems | related | collection |
| **RecurringProfile** | client | parent | single |
| **RecurringProfile** | recurringprofileitems | child | collection |
| **Milestone** | project | parent | single |
| **Milestone** | user | parent | single |
| **Milestone** | tasklists | related | collection |
| **Discussion** | project | parent | single |
| **Discussion** | user | creator | single |
| **Discussion** | thread | child | single |
| **Discussion** | files | child | collection |
| **Comment** | thread | parent | single |
| **Comment** | user | creator | single |
| **Comment** | project | parent (via thread) | single |
| **Comment** | files | child | collection |
| **CommentThread** | project | parent | single |
| **CommentThread** | discussion | parent | single |
| **CommentThread** | task | parent | single |
| **CommentThread** | file | parent | single |
| **CommentThread** | comments | child | collection |
| **File** | project | parent | single |
| **File** | user | parent | single |
| **File** | discussion | parent | single |
| **File** | task | parent | single |
| **File** | comment | parent | single |
| **Subtask** | project | parent (via task) | single |
| **Subtask** | task | parent | single |
| **Subtask** | user | parent | single |
| **Booking** | usertask | parent | single |
| **TaskAssignment** | user | parent | single |
| **TaskAssignment** | task | parent | single |
| **Workflow** | workflowstatuses | child | collection |
| **WorkflowStatus** | workflow | parent | single |
| **ProjectStatus** | project | related | ? |
| **InvoiceTemplate** | invoices | child | collection |
| **EstimateTemplate** | estimates | child | collection |

### 3.4 Filtering & WHERE Operations

#### General WHERE Syntax
- Parameter: `?where=property operator value`
- Multiple conditions: `?where=prop1=val1 and prop2=val2` (also `&&` as separator)
- Operators: `=`, `>`, `>=`, `<`, `<=`, `!=`, `like`, `not like`, `in`, `not in`
- Date filtering: Uses Unix timestamps for datetime properties
- Special: `in(me)` for current user filtering on user-related properties

#### Resources with WHERE Requirements
| Resource | Required WHERE | Notes |
|----------|----------------|-------|
| Booking | user_id, project_id, or task_id | Must specify at least one |
| TaskAssignment | user_id or task_id | Must specify at least one |

#### Known Filterable Properties (from docs and examples)
- **Projects**: client_id, active, status_id, users (in), workflow_id
- **Tasks**: project_id, tasklist_id, complete, user_id, users (in/not in/in(me)), priority, status_id, due_date, start_date
- **Clients**: name (like), email (like), active
- **TimeEntries**: task_id, project_id, user_id, date, start_time, end_time, billed
- **Invoices**: client_id, status, date, due_date, currency
- **Expenses**: client_id, project_id, user_id, date, invoiced
- **WorkflowStatuses**: workflow_id
- **Subtasks**: task_id
- **Milestones**: project_id
- **Discussions**: project_id
- **Comments**: thread_id, task_id, discussion_id, file_id
- **Files**: project_id, task_id, discussion_id, comment_id

### 3.5 Webhook Events

#### Complete Event List (22 events)
| Resource | Insert | Update | Delete | Special |
|----------|--------|--------|--------|---------|
| Client | ✅ | ✅ | ✅ | |
| ClientContact | ✅ | ✅ | ✅ | |
| Project | ✅ | ✅ | ✅ | |
| Tasklist | ✅ | ✅ | ✅ | |
| Task | ✅ | ✅ | ✅ | |
| Invoice | ✅ | ✅ | ✅ | |
| InvoicePayment | ✅ | ✅ | ✅ | |
| Entry | ✅ | ✅ | ✅ | model.start.Entry, model.stop.Entry |
| Milestone | ✅ | ✅ | ✅ | |
| Report | ✅ | ✅ | ✅ | |
| Expense | ✅ | ✅ | ✅ | |
| Estimate | ✅ | ✅ | ✅ | |
| Comment | ✅ | ✅ | ✅ | |
| User | ✅ | ✅ | ✅ | |
| Booking | ✅ | ✅ | ✅ | |

#### Webhook Features
- **Wildcard events**: `*`, `model.insert.*`, `*.Task`
- **Conditional WHERE**: Filter on model properties (e.g., `project_id=123`)
- **HMAC-SHA1 signatures**: Via `secret` param, verified through `X-Paymo-Signature` header
- **Custom headers**: `X-Paymo-Webhook` (webhook ID), `X-Paymo-Event` (event name)
- **Auto-delete**: Webhook deleted when target returns `410 Gone`
- **Notification body**: Full JSON object for insert/update; `{"id": <ID>}` for delete
- **Additional includes**: Webhook notifications include extra data (e.g., project.name, tasklist.name for tasks)

---

## 4. Active Debates & Open Issues

### Documentation Gaps (Community-Raised)

1. **Missing `progress_status` field** (Issue #25) — Community reports this field is not available via API despite being visible in the UI. Tasks can include `workflowstatus` via includes, but there's no direct `progress_status` property. Setting `complete=true` doesn't automatically update the workflow status (Issue #24).

2. **Missing `costs_per_hour` for users** (Issue #70) — The API exposes `price_per_hour` (billing rate) but not `costs_per_hour` (internal cost), making profitability calculations difficult via API alone.

3. **Pagination documentation** (Issue #46) — Community requested pagination. The feature actually exists (undocumented, discovered via Paymo support in Dec 2024 — see OVERRIDE-003) but was never added to the official docs.

4. **No machine-readable API spec** (Issue #62) — No OpenAPI/Swagger or Postman collection exists. All documentation is static markdown.

5. **Project Retainer API not public** (Issue #66) — Paymo confirmed that retainer project information is not accessible via the API. A `retainer_id` appears on projects but there's no endpoint to query retainer details.

6. **Empty invoice items** (Issue #68) — User reports that `include=invoiceitems` sometimes returns empty arrays for invoices that have items in the UI. Potentially a permissions or data issue.

### Documentation Errors (Confirmed/Fixed)

7. **Thread include key** (Issue #55, fixed in docs) — The child include for threads should be `comments` (plural), not `comment`. Singular throws a 500 error.

8. **Time entry HTML descriptions** (Issue #50) — Entry descriptions now contain HTML (`<p>` tags) from the web interface and widget. The API returns raw HTML; consumers must strip tags.

9. **Task webhook fires too often** (Issue #38) — `model.update.Task` webhook triggers more frequently than expected, including for internal state changes.

10. **Missing `project_id` in task webhooks** (Issue #33, open) — Delete task webhook payloads only contain `{"id": <ID>}`, making it impossible to know which project was affected without pre-tracking.

---

## 5. Recent Developments

### Documentation Timeline
| Date | Change |
|------|--------|
| 2023-07-20 | Latest commit: `Update webhooks.md` |
| 2023-06-08 | `Update reports.md` |
| 2023-02-20 | `Update webhooks.md` |
| 2022-11-17 | `Update webhooks.md` |
| 2022-09-27 | Added invoice recurring profiles |
| 2022-08-26 | Update filtering.md |
| 2022-08-12 | Update users.md |
| 2021-10-27 | Added subtasks to tasks dependent objects; rate limit headers |

### Paymo Product Changes (Not Reflected in API Docs)
Based on product blog posts and feature announcements:

- **Leave Planner** — Paymo added vacation and leave day planning. The API endpoints exist (`companiesdaysexceptions`, `usersdaysexceptions`, `leavetypes`) per PR #30, but the PR was never merged into docs.
- **Retainer Projects** — Billing method option added in the product, but retainer API is explicitly not public (Issue #66).
- **Task Priorities** — Added task priorities (25/50/75/100 scale), which IS documented in the API.
- **Paymo Track** — Unified timer launched 2023; uses existing `entries` API with start/stop semantics.

### GitHub Organization Rename
- **Old**: `github.com/paymoapp/api`
- **New**: `github.com/paymo-org/api`
- GitHub automatically redirects. Many references in project docs and external sources still use the old URL.

---

## 6. Emerging Threads (Potential Deep Dive Topics)

### Thread 1: Undocumented Endpoints
**Priority: HIGH**

PR #30 proposes documentation for 4 undocumented endpoints that the API appears to support but that are NOT in the official docs:

| Endpoint | Resource | Purpose |
|----------|----------|---------|
| `/api/companiesdaysexceptions` | CompanyDaysException | Company-level holidays and working day exceptions |
| `/api/usersdaysexceptions` | UserDaysException | Per-user leave days and working exceptions |
| `/api/leavetypes` | LeaveType | Leave type definitions (Vacation, Sick Leave, etc.) |
| `/api/statsreports` | StatsReport | Statistical reports (annual leave stats, working days count) |

Additionally, the CData connector exposes entities not in the official docs, suggesting more undocumented or underdocumented resources may exist (e.g., `ClientTimeEntries`, `ProjectBookings`, `TaskBookings`, etc. — though these may be computed views rather than distinct API endpoints).

### Thread 2: Response Key Anomalies
**Priority: MEDIUM**

OVERRIDE-009 documents that some endpoints return data under unexpected response keys:
- `/api/projecttemplates` returns `{"project_templates": [...]}` (underscore) instead of `{"projecttemplates": [...]}`

OVERRIDE-010 documents gallery endpoint response key anomalies:
- Gallery endpoints return data under colon-prefixed keys (e.g., `":estimatetemplates"`)

### Thread 3: Unselectable Properties
**Priority: MEDIUM**

OVERRIDE-013 identifies properties that exist in API responses but CANNOT be explicitly requested via the `select` query parameter. They appear in full responses but are excluded when you try to `select` specific fields. This affects how the SDK handles field selection.

### Thread 4: SDK ClassMap Entity Coverage
**Priority: MEDIUM**

The SDK config classMap contains 38+ entity type keys. Comparing against the documented API reveals potential gaps:
- `projecttemplatestasklist` and `projecttemplatestask` — documented as sub-resources of project templates
- `thread` (→ CommentThread) — documented but read-only
- `estimatetemplatesgallery` and `invoicetemplatesgallery` — documented as read-only galleries
- Several entity keys like `projectstatus` have no dedicated doc file (OVERRIDE-008)

### Thread 5: Conditional/Deprecated Properties
**Priority: LOW-MEDIUM**

Several properties behave differently than documented:
- `Client.active` — Docs say settable, but SDK treats as read-only (OVERRIDE-006)
- `Company.apply_tax_to_expenses` and `Company.tax_on_tax` — May be deprecated or conditional (OVERRIDE-002)
- `Invoice.language` and `RecurringProfile.language` — Deprecated (OVERRIDE-012)
- `Client.image_thumb_*` — Only present when image is uploaded (OVERRIDE-001)

---

## 7. API Infrastructure Summary

### Authentication
| Method | Mechanism | Use Case |
|--------|-----------|----------|
| **Basic Auth** | `email:password` as HTTP Basic | Quick testing |
| **API Key** | `apikey:X` as HTTP Basic | Third-party integrations (recommended) |
| **Session** | `X-Session: token` header | Web application sessions |

### Rate Limiting
- Headers: `X-Ratelimit-Decay-Period`, `X-Ratelimit-Limit`, `X-Ratelimit-Remaining`
- Exceeded: 429 status + `Retry-After` header
- Specific limits (requests per period) are NOT documented
- SDK config uses: minDelayMs=200, safetyBuffer=1, maxRetries=3, retryDelayMs=1000

### Content Types
- **Request**: `application/json`, `application/xml`, `application/x-www-form-urlencoded`, `multipart/form-data` (file upload)
- **Response**: `application/json` (default via Accept header), `application/xml`
- **Special**: `application/pdf`, `application/vnd.ms-excel` for reports/invoices/estimates

### Response Codes
| Code | Meaning |
|------|---------|
| 200 | Success (GET, PUT, DELETE) |
| 201 | Created (POST) |
| 400 | Bad request |
| 401 | Unauthorized |
| 403 | Forbidden |
| 404 | Not found |
| 429 | Rate limited |
| 500 | Server error |

### Date/Time Formats
- **Response**: ISO 8601 format, UTC (e.g., `2024-01-15T10:30:00Z`)
- **WHERE filtering**: Unix timestamps for datetime properties
- **Date-only fields**: `YYYY-MM-DD` format

### Pagination (Undocumented — OVERRIDE-003)
- `page` parameter: 0-indexed page number
- `page_size` parameter: results per page
- No total count returned by API
- Possible max `page_size` of 2500 (unconfirmed, per Paymo support)

---

## 8. Resource Relationships & Data Model

### Hierarchy
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

### Key Foreign Key Relationships
| From | Property | To | Notes |
|------|----------|----|-------|
| Project | client_id | Client | |
| Project | status_id | ProjectStatus | |
| Project | workflow_id | Workflow | |
| Tasklist | project_id | Project | |
| Tasklist | milestone_id | Milestone | |
| Task | project_id | Project | |
| Task | tasklist_id | Tasklist | |
| Task | status_id | WorkflowStatus | |
| Task | invoice_item_id | InvoiceItem | |
| Subtask | task_id | Task | |
| TimeEntry | task_id | Task | |
| TimeEntry | invoice_item_id | InvoiceItem | |
| Invoice | client_id | Client | |
| Invoice | template_id | InvoiceTemplate | |
| InvoiceItem | invoice_id | Invoice | Undocumented but exists (OVERRIDE-007) |
| InvoiceItem | expense_id | Expense | |
| InvoicePayment | invoice_id | Invoice | |
| Estimate | client_id | Client | |
| Estimate | template_id | EstimateTemplate | |
| Estimate | invoice_id | Invoice | |
| EstimateItem | estimate_id | Estimate | Undocumented but exists (OVERRIDE-007) |
| Expense | client_id | Client | |
| Expense | project_id | Project | |
| Expense | invoice_item_id | InvoiceItem | |
| Milestone | project_id | Project | |
| Discussion | project_id | Project | |
| File | project_id | Project | |
| File | task_id | Task | |
| File | discussion_id | Discussion | |
| File | comment_id | Comment | |
| Comment | thread_id | CommentThread | |
| CommentThread | project_id | Project | |
| CommentThread | task_id | Task | |
| CommentThread | discussion_id | Discussion | |
| CommentThread | file_id | File | |
| Booking | user_task_id | TaskAssignment | |
| TaskAssignment | user_id | User | |
| TaskAssignment | task_id | Task | |
| ClientContact | client_id | Client | |
| WorkflowStatus | workflow_id | Workflow | |
| RecurringProfile | client_id | Client | |
| RecurringProfile | template_id | InvoiceTemplate | |
| RecurringProfileItem | recurring_profile_id | RecurringProfile | |
| TaskRecurringProfile | project_id | Project | |
| TaskRecurringProfile | tasklist_id | Tasklist | |

### Many-to-Many Relationships (via Join Resources)
| Relationship | Join Resource | Endpoint |
|--------------|--------------|----------|
| User ↔ Task | TaskAssignment | `/api/userstasks` |
| Milestone ↔ Tasklist | (via milestone.linked_tasklists array) | N/A — embedded array |

---

## 9. Source Log

### Primary Sources (Local Files — All Processed)

| Source | Lines | Status |
|--------|-------|--------|
| docs/api-documentation/README.md | 143 | ✅ Fully processed |
| docs/api-documentation/sections/authentication.md | ~80 | ✅ Fully processed |
| docs/api-documentation/sections/bookings.md | ~140 | ✅ Fully processed |
| docs/api-documentation/sections/client_contacts.md | ~170 | ✅ Fully processed |
| docs/api-documentation/sections/clients.md | ~200 | ✅ Fully processed |
| docs/api-documentation/sections/comments.md | ~330 | ✅ Fully processed |
| docs/api-documentation/sections/company.md | ~250 | ✅ Fully processed |
| docs/api-documentation/sections/content_types.md | ~80 | ✅ Fully processed |
| docs/api-documentation/sections/currencies.md | ~100 | ✅ Fully processed |
| docs/api-documentation/sections/datetime.md | ~40 | ✅ Fully processed |
| docs/api-documentation/sections/discussions.md | ~160 | ✅ Fully processed |
| docs/api-documentation/sections/entries.md | ~250 | ✅ Fully processed |
| docs/api-documentation/sections/estimate_templates.md | ~200 | ✅ Fully processed |
| docs/api-documentation/sections/estimates.md | ~290 | ✅ Fully processed |
| docs/api-documentation/sections/expenses.md | ~180 | ✅ Fully processed |
| docs/api-documentation/sections/files.md | ~210 | ✅ Fully processed |
| docs/api-documentation/sections/filtering.md | ~80 | ✅ Fully processed |
| docs/api-documentation/sections/includes.md | ~140 | ✅ Fully processed |
| docs/api-documentation/sections/invoice_payments.md | ~150 | ✅ Fully processed |
| docs/api-documentation/sections/invoice_recurring_profiles.md | ~270 | ✅ Fully processed |
| docs/api-documentation/sections/invoice_templates.md | ~180 | ✅ Fully processed |
| docs/api-documentation/sections/invoices.md | ~360 | ✅ Fully processed |
| docs/api-documentation/sections/milestones.md | ~180 | ✅ Fully processed |
| docs/api-documentation/sections/project_statuses.md | ~170 | ✅ Fully processed |
| docs/api-documentation/sections/project_templates.md | ~310 | ✅ Fully processed |
| docs/api-documentation/sections/projects.md | ~350 | ✅ Fully processed |
| docs/api-documentation/sections/reports.md | ~567 | ✅ Fully processed |
| docs/api-documentation/sections/sample_code.md | ~20 | ✅ Fully processed |
| docs/api-documentation/sections/sessions.md | ~120 | ✅ Fully processed |
| docs/api-documentation/sections/subtasks.md | ~170 | ✅ Fully processed |
| docs/api-documentation/sections/task_recurring_profiles.md | ~280 | ✅ Fully processed |
| docs/api-documentation/sections/tasklists.md | ~160 | ✅ Fully processed |
| docs/api-documentation/sections/tasks.md | ~360 | ✅ Fully processed |
| docs/api-documentation/sections/users.md | ~280 | ✅ Fully processed |
| docs/api-documentation/sections/users_tasks.md | ~148 | ✅ Fully processed |
| docs/api-documentation/sections/webhooks.md | 330 | ✅ Fully processed |
| docs/api-documentation/sections/workflow_statuses.md | 169 | ✅ Fully processed |
| docs/api-documentation/sections/workflows.md | 150 | ✅ Fully processed |
| OVERRIDES.md | 803 | ✅ Fully processed |
| default.paymoapi.config.json | 453 | ✅ Fully processed |

### Secondary Sources (External — Checked)

| Source | Status | Findings |
|--------|--------|----------|
| GitHub repo `paymo-org/api` (live) | ✅ Verified | Identical to local copy; last commit 2023-07-20 |
| GitHub Issues (70 total) | ✅ Reviewed all titles, read key issues | Key findings: missing progress_status (#25), costs_per_hour (#70), retainer API not public (#66), pagination feature request (#46), HTML in descriptions (#50), empty invoiceitems bug (#68) |
| GitHub PR #30 (days_exceptions/leave_types) | ✅ Fully read diffs | Documents 4 undocumented endpoints; PR open since creation, never merged |
| CData Paymo connector docs | ✅ Checked | Exposes ~40+ tables; reveals some view-like resources not in official docs |
| Skyvia Paymo connector | ✅ Checked | Supports incremental replication; limited detail available |
| n8n Paymo integration | ✅ Checked | Uses generic HTTP nodes, no dedicated Paymo nodes with resource lists |
| Pipedream Paymo integration | ✅ Checked | Basic API access; "New Task Created" trigger |
| Paymo blog/product updates | ✅ Searched | Leave planner, retainer projects, task priorities — some reflected in API, some not |
| Paymo Help Center | ✅ Searched | Confirms API key auth, basic usage; no additional endpoint details |

### Tertiary Sources (Community)
| Source | Status | Findings |
|--------|--------|----------|
| Web search: Paymo API undocumented | ✅ | No significant community blog posts or tutorials beyond official docs |
| Web search: Paymo API integration guides | ✅ | Mostly points back to official GitHub docs |
| Web search: Paymo retainer/billing_type | ✅ | Confirmed billing mode hierarchy exists; retainer not API-public |

---

## 10. Processing Notes

### Confidence Assessment
- **HIGH confidence**: All documented resource endpoints, properties, CRUD operations, and include relationships. These are directly extracted from official documentation files verified against the live GitHub repo.
- **HIGH confidence**: OVERRIDES.md findings — these represent tested behavior against live API responses.
- **MEDIUM confidence**: Undocumented endpoints from PR #30 (companiesdaysexceptions, usersdaysexceptions, leavetypes, statsreports) — PR exists from a community member who appears to have tested them, but PR was never merged or officially acknowledged.
- **MEDIUM confidence**: WHERE filter support per property — documented via examples but NOT exhaustively specified per-resource in the official docs.
- **LOW confidence**: CData-exposed entities that don't match documented endpoints (e.g., ClientTimeEntries, ProjectBookings) — may be computed views rather than actual API endpoints.
- **LOW confidence**: Currencies endpoint behavior — file exists in docs but not listed in README; may be a read-only reference endpoint or may not be a real API endpoint.

### Coverage Gaps Identified
1. **Per-property WHERE operator support** — Docs describe the general syntax but don't specify which operators work on which properties for each resource. This would require live API testing.
2. **Exact rate limit values** — The decay period, limit, and remaining values are returned in headers but the actual limits are not documented.
3. **Full list of undocumented properties** — OVERRIDE-011 establishes a policy to capture undocumented properties, but the complete list of undocumented properties per resource requires live API response comparison.
4. **Select parameter behavior** — Which properties can and cannot be explicitly selected (OVERRIDE-013) requires live API testing to fully map.
5. **Retainer API** — Confirmed non-public; `retainer_id` appears on projects but no endpoint exists.

### Methodology
1. Systematically read all 37 local API documentation section files
2. Read OVERRIDES.md (13 active overrides) and SDK config classMap (38+ entity types)
3. Compared local docs against live GitHub repo — confirmed identical (same file list, same line counts)
4. Reviewed all 70 GitHub issues for undocumented behavior reports
5. Fully read PR #30 diffs for undocumented leave management endpoints
6. Web searched for community sources, third-party integration guides, and Paymo product updates
7. Checked CData, Skyvia, n8n, and Pipedream connector documentation for additional endpoint discovery
