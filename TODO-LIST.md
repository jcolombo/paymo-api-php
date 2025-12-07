# Paymo API PHP SDK - TODO List

**Verified Against Official Paymo API Documentation**
Source: https://github.com/paymoapp/api
Last Verified: December 6, 2025

**All 38 Resource Files Verified** - Full audit completed against live API documentation.

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
15. [TaskAssignment (UserTask) Resource](#15-taskassignment-usertask-resource)
16. [Workflow Resource](#16-workflow-resource)
17. [WorkflowStatus Resource](#17-workflowstatus-resource)
18. [File Resource](#18-file-resource)
19. [Comment Resource](#19-comment-resource)
20. [Discussion Resource](#20-discussion-resource)
21. [ClientContact Resource](#21-clientcontact-resource)
22. [Report Resource](#22-report-resource)
23. [Company Resource](#23-company-resource)
24. [Session Resource](#24-session-resource)
25. [InvoicePayment Resource](#25-invoicepayment-resource)
26. [ProjectStatus Resource](#26-projectstatus-resource)
27. [TypeScript Interfaces](#27-typescript-interfaces)
28. [Utility/Helper Features](#28-utilityhelper-features)
29. [Architecture Improvements](#29-architecture-improvements)

---

## 1. Missing Resources (Not Implemented)

### Completed in v0.6.0

#### 1.1 Subtask Resource
**Status:** IMPLEMENTED (v0.6.0)
**File:** `src/Entity/Resource/Subtask.php`

**Verified Properties (from API):**
- `id`, `name`, `complete`, `project_id`, `user_id`, `task_id`, `seq`
- `completed_on`, `completed_by`, `created_on`, `updated_on`

**Verified Includes:** project, task, user

---

#### 1.2 Invoice Recurring Profile Resource
**Status:** IMPLEMENTED (v0.6.0)
**Files:** `src/Entity/Resource/RecurringProfile.php`, `src/Entity/Resource/RecurringProfileItem.php`

**Verified Properties (from API):**
- Profile: `id`, `client_id`, `template_id`, `currency`, `start_date`, `frequency`, `occurrences`, `last_created`, `invoices_created`, `autosend`, `subtotal`, `total`, `tax`, `tax_amount`, `tax2`, `tax2_amount`, `discount`, `discount_amount`, `tax_on_tax`, `language`, `bill_to`, `company_info`, `footer`, `notes`, `tax_text`, `tax2_text`, `discount_text`, `title`, `pay_online`, `created_on`, `updated_on`
- Item: `id`, `recurring_profile_id`, `item`, `description`, `price_unit`, `quantity`, `apply_tax`, `seq`, `created_on`, `updated_on`

**Verified Frequency Values:** w, 2w, 3w, 4w, m, 2m, 3m, 6m, y

**Verified Includes:** client, recurringprofileitems, recurringprofile (for items)

---

#### 1.3 Task Recurring Profile Resource
**Status:** IMPLEMENTED (v0.6.0)
**File:** `src/Entity/Resource/TaskRecurringProfile.php`

**Recent Fixes (v0.6.0):**
- [x] Added `code` to READONLY (read-only per API)

**Verified Properties (from API):**
- `id`, `name`, `code`, `project_id`, `tasklist_id`, `user_id`, `task_user_id`, `company_id`
- `billable`, `flat_billing`, `description`, `price_per_hour`, `estimated_price`, `budget_hours`
- `users`, `priority`, `notifications`, `frequency`, `interval`, `on_day`, `occurrences`, `until`
- `active`, `due_date_offset`, `recurring_start_date`, `generated_count`, `last_generated_on`
- `next_processing_date`, `processing_timezone`, `processing_hour`, `created_on`, `updated_on`

**Verified Frequency Values:** daily, weekly, monthly

**Verified Includes:** project

---

#### 1.4 Webhook/Hook Resource
**Status:** IMPLEMENTED (v0.6.0)
**File:** `src/Entity/Resource/Webhook.php`

**Verified Properties (from API):**
- `id`, `target_url`, `last_status_code`, `event`, `where`, `created_on`, `updated_on`
- `secret` (write-only, never returned in responses)

**Verified Event Types:**
- model.insert.{Entity}, model.update.{Entity}, model.delete.{Entity}
- model.start.Entry, model.stop.Entry
- Wildcards: *, model.insert.*, *.Task

**Entities:** Client, ClientContact, Project, Tasklist, Task, Invoice, InvoicePayment, Entry, Milestone, Report, Expense, Estimate, Comment, User, Booking

---

### 1.5 Session Resource
**Status:** EXISTS - Verify Completeness
**File:** `src/Entity/Resource/Session.php`

**Verified Properties (from API):**
- `id`, `ip`, `expires_on`, `created_on`, `updated_on`, `user_id`

**Note:** No browser/os properties in API - verify if SDK has incorrect properties.

---

## 2. Project Resource

**File:** `src/Entity/Resource/Project.php`
**Status:** COMPLETE

### 2.1 Verified Properties (from API)
All these properties exist in the official API:
- `id`, `name`, `code`, `task_code_increment`, `description`, `client_id`, `status_id`
- `active`, `color`, `users`, `managers`, `billable`, `flat_billing`, `price_per_hour`
- `price`, `estimated_price`, `hourly_billing_mode`, `budget_hours`, `adjustable_hours`
- `invoiced`, `invoice_item_id`, `workflow_id`, `created_on`, `updated_on`

### 2.2 Fixed Issues
- [x] `workflow_id` type was `resource:milestone`, now `resource:workflow` (FIXED v0.6.0)

### 2.3 Verified Includes (from API)
- client, projectstatus, tasklists, tasks, milestones, discussions, files, invoiceitem, workflow

**Note:** The API also mentions `tasks.entries` for nested time entries through tasks.

### 2.4 Properties NOT in API (Do NOT add)
The following were previously listed as missing but DO NOT exist in the official API:
- ~~start_date~~ - NOT IN API
- ~~end_date~~ - NOT IN API
- ~~budget~~ - NOT IN API (budget_hours exists)
- ~~budget_value~~ - NOT IN API
- ~~progress~~ - NOT IN API
- ~~completed_on~~ - NOT IN API

---

## 3. Task Resource

**File:** `src/Entity/Resource/Task.php`
**Status:** COMPLETE - VERIFIED December 2025

### 3.0 Recent Fixes (v0.6.0)
- [x] Added `code` to READONLY (auto-generated, read-only per API)

### 3.1 Verified Properties (from API)
All these properties exist in the official API:
- `id`, `name`, `code`, `project_id`, `tasklist_id`, `seq`, `description`
- `complete`, `completed_on`, `completed_by`, `due_date`, `user_id`, `users`
- `billable`, `flat_billing`, `price_per_hour`, `budget_hours`, `estimated_price`
- `invoiced`, `invoice_item_id`, `priority`, `status_id`, `created_on`, `updated_on`

### 3.2 Recent Updates (v0.6.0)
- [x] Added `subtasks` to INCLUDE_TYPES
- [x] Added `subtasks_order` to PROP_TYPES for reordering subtasks

### 3.3 Verified Includes (from API)
- project, tasklist, user, thread, entries, subtasks, invoiceitem, workflowstatus

### 3.4 Properties NOT in API (Do NOT add)
The following were previously listed as missing but DO NOT exist in the official API:
- ~~time_estimate~~ - NOT IN API
- ~~progress~~ - NOT IN API
- ~~subtasks_complete~~ - NOT IN API
- ~~subtasks_total~~ - NOT IN API
- ~~files_count~~ - NOT IN API
- ~~comments_count~~ - NOT IN API

### 3.5 Includes NOT in API (Do NOT add)
- ~~files~~ - NOT IN API
- ~~comments~~ - NOT IN API (use thread instead)
- ~~bookings~~ - NOT IN API

---

## 4. TimeEntry Resource

**File:** `src/Entity/Resource/TimeEntry.php`
**Status:** COMPLETE

### 4.1 Verified Properties (from API)
- `id`, `project_id`, `task_id`, `user_id`, `is_bulk`, `start_time`, `end_time`
- `date`, `duration`, `description`, `added_manually`, `billed`, `invoice_item_id`
- `created_on`, `updated_on`

### 4.2 Verified Includes (from API)
- task, invoiceitem, user

### 4.3 Properties NOT in API (Do NOT add)
- ~~billable~~ - NOT IN API (entries inherit billable from their parent task)

### 4.4 Includes NOT in API (Do NOT add)
- ~~project~~ - NOT IN API (get project through task include)

---

## 5. Client Resource

**File:** `src/Entity/Resource/Client.php`
**Status:** COMPLETE

### 5.1 Verified Properties (from API)
- `id`, `name`, `address`, `city`, `postal_code`, `country`, `state`
- `phone`, `fax`, `email`, `website`, `active`, `fiscal_information`
- `created_on`, `updated_on`, `image`, `image_thumb_large`, `image_thumb_medium`, `image_thumb_small`

### 5.2 Verified Includes (from API)
- clientcontacts, projects, invoices, recurringprofiles

### 5.3 Properties NOT in API (Do NOT add)
- ~~currency~~ - NOT IN API
- ~~vat_id~~ - NOT IN API
- ~~language~~ - NOT IN API

### 5.4 Includes NOT in API (Do NOT add)
- ~~entries~~ - NOT IN API
- ~~expenses~~ - NOT IN API
- ~~estimates~~ - NOT IN API

---

## 6. Invoice Resource

**File:** `src/Entity/Resource/Invoice.php`
**Status:** COMPLETE

### 6.1 Verified Properties (from API)
- `id`, `number`, `client_id`, `template_id`, `status`, `currency`, `date`, `due_date`
- `subtotal`, `total`, `tax`, `tax_amount`, `tax2`, `tax2_amount`, `discount`, `discount_amount`
- `tax_on_tax`, `language`, `bill_to`, `company_info`, `footer`, `notes`, `outstanding`
- `tax_text`, `tax2_text`, `discount_text`, `title`, `delivery_date`, `pay_online`
- `reminder_1_sent`, `reminder_2_sent`, `reminder_3_sent`, `permalink`, `pdf_link`
- `download_token`, `token`, `created_on`, `updated_on`

### 6.2 Verified Includes (from API)
- client, invoiceitems, invoicepayments, invoicetemplate

**Additional includes from API:** entries, expense, projects, tasks (for invoice items)

### 6.3 Properties NOT in API (Do NOT add)
- ~~viewed_on~~ - NOT IN API
- ~~sent_on~~ - NOT IN API
- ~~paid_on~~ - NOT IN API
- ~~last_reminder_sent_on~~ - NOT IN API

### 6.4 Special Actions NOT in API
The following endpoints are NOT documented in the current API:
- ~~POST /api/invoices/{id}/send~~ - NOT DOCUMENTED
- ~~POST /api/invoices/{id}/remind~~ - NOT DOCUMENTED
- ~~POST /api/invoices/{id}/mark_as_sent~~ - NOT DOCUMENTED
- ~~POST /api/invoices/{id}/mark_as_paid~~ - NOT DOCUMENTED

**Note:** Invoice status changes are done via standard update with `status` field.

---

## 7. InvoiceItem Resource

**File:** `src/Entity/Resource/InvoiceItem.php`
**Status:** COMPLETE

### 7.1 Verified Properties (from API)
- `id`, `invoice_id`, `item`, `description`, `price_unit`, `quantity`
- `expense_id`, `apply_tax`, `seq`, `entries`, `created_on`, `updated_on`

### 7.2 Properties NOT in API (Do NOT add)
- ~~project_id~~ - NOT IN API
- ~~task_id~~ - NOT IN API
- ~~apply_tax2~~ - NOT IN API
- ~~line_total~~ - NOT IN API (calculate: price_unit * quantity)

---

## 8. Estimate Resource

**File:** `src/Entity/Resource/Estimate.php`
**Status:** COMPLETE

### 8.1 Verified Properties (from API)
- `id`, `number`, `client_id`, `template_id`, `status`, `currency`, `date`
- `subtotal`, `total`, `tax`, `tax_amount`, `tax2`, `tax2_amount`, `tax_on_tax`
- `tax_text`, `tax2_text`, `language`, `bill_to`, `company_info`, `footer`, `notes`
- `title`, `brief_description`, `discount`, `discount_amount`, `discount_text`
- `invoice_id`, `permalink`, `pdf_link`, `created_on`, `updated_on`, `download_token`

### 8.2 Verified Status Values
- draft, sent, viewed, accepted, invoiced, void

### 8.3 Verified Includes (from API)
- client, invoice, estimateitems, estimatetemplate

---

## 9. EstimateItem Resource

**File:** `src/Entity/Resource/EstimateItem.php`
**Status:** VERIFY

### 9.1 Verified Properties (from API)
- `id`, `estimate_id`, `item`, `description`, `price_unit`, `quantity`
- `apply_tax`, `seq`, `created_on`, `updated_on`

---

## 10. User Resource

**File:** `src/Entity/Resource/User.php`
**Status:** COMPLETE - VERIFIED December 2025

### 10.0 Recent Fixes (v0.6.0)
- [x] Added `Guest` to type enum (API supports Admin|Employee|Guest)

### 10.1 Verified Properties (from API)
- `id`, `name`, `email`, `type`, `active`, `timezone`, `phone`, `skype`, `position`
- `workday_hours`, `price_per_hour`, `created_on`, `updated_on`
- `image`, `image_thumb_large`, `image_thumb_medium`, `image_thumb_small`
- `date_format`, `time_format`, `decimal_sep`, `thousands_sep`, `week_start`
- `language`, `theme`, `assigned_projects`, `managed_projects`, `is_online`, `password`

### 10.2 Verified Includes (from API)
- comments, discussions, entries, expenses, files, milestones, reports

### 10.3 Properties NOT in API (Do NOT add)
- ~~last_active_on~~ - NOT IN API
- ~~two_factor_auth_enabled~~ - NOT IN API

### 10.4 Includes NOT in API (Do NOT add)
- ~~tasks~~ - NOT IN API
- ~~projects~~ - NOT IN API (use assigned_projects property)
- ~~bookings~~ - NOT IN API

---

## 11. Expense Resource

**File:** `src/Entity/Resource/Expense.php`
**Status:** COMPLETE - VERIFIED December 2025

### 11.0 Recent Fixes (v0.6.0)
- [x] Moved `user_id` from undocumented to documented (IS in API docs)
- [x] Removed `invoice_item_id` from READONLY (NOT read-only per API)
- [x] Removed incorrect `name` property (NOT in API - use `notes` for description)

### 11.1 Verified Properties (from API)
- `id`, `client_id`, `project_id`, `user_id`, `amount`, `currency`, `date`
- `notes`, `invoiced`, `invoice_item_id`, `tags`, `file`
- `image_thumb_large`, `image_thumb_medium`, `image_thumb_small`
- `created_on`, `updated_on`

### 11.2 Verified Includes (from API)
- client, project, user, invoiceitems

### 11.3 Properties NOT in API (Do NOT add)
- ~~billable~~ - NOT IN API
- ~~category~~ - NOT IN API

---

## 12. Tasklist Resource

**File:** `src/Entity/Resource/Tasklist.php`
**Status:** COMPLETE

### 12.1 Verified Properties (from API)
- `id`, `name`, `seq`, `project_id`, `milestone_id`, `created_on`, `updated_on`

### 12.2 Fixed Issues
- [x] Typo `miletstone` -> `milestone` in INCLUDE_TYPES (FIXED v0.6.0)

### 12.3 Verified Includes (from API)
- project, milestone, tasks

---

## 13. Milestone Resource

**File:** `src/Entity/Resource/Milestone.php`
**Status:** COMPLETE

### 13.1 Verified Properties (from API)
- `id`, `name`, `project_id`, `user_id`, `due_date`, `send_reminder`
- `reminder_sent`, `complete`, `linked_tasklists`, `created_on`, `updated_on`

### 13.2 Verified Includes (from API)
- project, user, tasklists

---

## 14. Booking Resource

**File:** `src/Entity/Resource/Booking.php`
**Status:** VERIFY INCLUDES

### 14.1 Verified Properties (from API)
- `id`, `user_task_id`, `start_date`, `end_date`, `hours_per_day`
- `description`, `created_on`, `updated_on`

### 14.2 Verified Includes (from API)
- usertask

### 14.3 Includes NOT in API (Do NOT add)
- ~~user~~ - NOT IN API (access via usertask)
- ~~task~~ - NOT IN API (access via usertask)
- ~~project~~ - NOT IN API (access via usertask)

---

## 15. TaskAssignment (UserTask) Resource

**File:** `src/Entity/Resource/TaskAssignment.php`
**Status:** VERIFY

### 15.1 Verified Properties (from API)
- `id`, `user_id`, `task_id`, `created_on`, `updated_on`

### 15.2 Verified Includes (from API)
- user, task

### 15.3 Includes NOT in API (Do NOT add)
- ~~project~~ - NOT IN API
- ~~bookings~~ - NOT IN API

---

## 16. Workflow Resource

**File:** `src/Entity/Resource/Workflow.php`
**Status:** COMPLETE

### 16.1 Verified Properties (from API)
- `id`, `name`, `is_default`, `created_on`, `updated_on`

### 16.2 Verified Includes (from API)
- workflowstatuses

---

## 17. WorkflowStatus Resource

**File:** `src/Entity/Resource/WorkflowStatus.php`
**Status:** COMPLETE

### 17.1 Verified Properties (from API)
- `id`, `name`, `workflow_id`, `color`, `seq`, `action`, `created_on`, `updated_on`

### 17.2 Verified Includes (from API)
- workflow

### 17.3 Includes NOT in API (Do NOT add)
- ~~tasks~~ - NOT IN API

---

## 18. File Resource

**File:** `src/Entity/Resource/File.php`
**Status:** VERIFY

### 18.1 Verified Properties (from API)
- `id`, `original_filename`, `description`, `user_id`, `project_id`
- `discussion_id`, `task_id`, `comment_id`, `token`, `size`, `file`
- `image_thumb_large`, `image_thumb_medium`, `image_thumb_small`
- `created_on`, `updated_on`, `mime`, `tags`

### 18.2 Verified Includes (from API)
- project, user, discussion, task, comment

### 18.3 Properties NOT in API (Do NOT add)
- ~~version~~ - NOT IN API
- ~~versions~~ - NOT IN API

### 18.4 Includes NOT in API (Do NOT add)
- ~~thread~~ - NOT IN API
- ~~comments~~ - NOT IN API

---

## 19. Comment Resource

**File:** `src/Entity/Resource/Comment.php`
**Status:** COMPLETE

### 19.1 Verified Properties (from API)
- `id`, `content`, `thread_id`, `user_id`, `created_on`, `updated_on`

### 19.2 Create-Only Properties (COMPLETED v0.6.0)
The following are now in PROP_TYPES and CREATEONLY:

| Property | Type | Notes |
|----------|------|-------|
| `task_id` | resource:task | For creating task comments (create-only) |
| `discussion_id` | resource:discussion | For creating discussion comments (create-only) |
| `file_id` | resource:file | For creating file comments (create-only) |

**Note:** These are write-only for creation - specify target for new comment.
`thread_id` and `user_id` are now marked read-only (set by server).

### 19.3 Verified Includes (from API)
- thread, user, project, files

---

## 20. Discussion Resource

**File:** `src/Entity/Resource/Discussion.php`
**Status:** COMPLETE

### 20.1 Verified Properties (from API)
- `id`, `name`, `description`, `project_id`, `user_id`, `created_on`, `updated_on`

### 20.2 Verified Includes (from API)
- project, user, thread, files

**Note:** API supports `thread.comments` for nested comment retrieval.

---

## 21. ClientContact Resource

**File:** `src/Entity/Resource/ClientContact.php`
**Status:** VERIFY

### 21.1 Verified Properties (from API)
- `id`, `client_id`, `name`, `email`, `mobile`, `phone`, `fax`, `skype`
- `notes`, `image`, `is_main`, `position`, `access`
- `created_on`, `updated_on`, `image_thumb_large`, `image_thumb_medium`, `image_thumb_small`

### 21.2 Verified Includes (from API)
- client

---

## 22. Report Resource

**File:** `src/Entity/Resource/Report.php`
**Status:** VERIFY

### 22.1 Verified Properties (from API)
- `id`, `name`, `user_id`, `type`, `start_date`, `end_date`, `date_interval`
- `projects`, `clients`, `users`, `include`, `extra`, `info`, `content`
- `permalink`, `shared`, `share_client_id`, `created_on`, `updated_on`, `download_token`

### 22.2 Verified Includes (from API)
- user, client

### 22.3 Properties NOT in API (Do NOT add)
- ~~tags~~ - NOT IN API
- ~~description~~ - NOT IN API

---

## 23. Company Resource

**File:** `src/Entity/Resource/Company.php`
**Status:** VERIFY

### 23.1 Verified Properties (from API)
- `id`, `name`, `address`, `phone`, `email`, `url`, `fiscal_information`, `country`
- `image`, `image_thumb_large`, `image_thumb_medium`, `image_thumb_small`
- `created_on`, `updated_on`, `timezone`, `default_currency`, `default_price_per_hour`
- `apply_tax_to_expenses`, `tax_on_tax`, `currency_position`, `next_invoice_number`, `next_estimate_number`
- `online_payments`, `date_format`, `time_format`, `decimal_sep`, `thousands_sep`
- `week_start`, `workday_start`, `workday_end`, `working_days`
- `account_type`, `max_users`, `current_users`, `max_projects`, `current_projects`
- `max_invoices`, `current_invoices`, `default_tax`, `default_tax2`, `default_tax2_text`
- `default_tax_text`, `due_interval`, `estimate_format`, `hide_tax_field`, `invoice_format`
- `language`, `payment_reminder_1`, `payment_reminder_2`, `payment_reminder_3`, `remove_paymo_branding`

### 23.2 Fixed Issues
- [x] Typo `interger` -> `integer` for `max_estimates` (FIXED v0.6.0)

---

## 24. Session Resource

**File:** `src/Entity/Resource/Session.php`
**Status:** VERIFY

### 24.1 Verified Properties (from API)
- `id`, `ip`, `expires_on`, `created_on`, `updated_on`, `user_id`

**Note:** API docs do NOT include `browser` or `os` properties.

---

## 25. InvoicePayment Resource

**File:** `src/Entity/Resource/InvoicePayment.php`
**Status:** EXISTS - VERIFY

### 25.1 Verified Properties (from API)
- `id`, `invoice_id`, `amount`, `date`, `notes`, `created_on`, `updated_on`

### 25.2 Verified Includes (from API)
- invoice

---

## 26. ProjectStatus Resource

**File:** `src/Entity/Resource/ProjectStatus.php`
**Status:** COMPLETE - VERIFIED December 2025

### 26.0 Recent Fixes (v0.6.0)
- [x] Fixed INCLUDE_TYPES from `projects => true` to `project => false` (API uses singular `project`)

### 26.1 Verified Properties (from API)
- `id`, `name`, `active`, `seq`, `readonly`, `created_on`, `updated_on`

### 26.2 Verified Includes (from API)
- project (singular, not plural)

---

## 27. TypeScript Interfaces

**File:** `src/.resources/typescript.data-types.ts`
**Status:** COMPLETE

### 27.1 All Interfaces (39 total)
- [x] PaymoBooking (fixed hours_per_day typo Dec 2025)
- [x] PaymoSubtask (added v0.6.0)
- [x] PaymoRecurringProfile (added v0.6.0)
- [x] PaymoRecurringProfileItem (added v0.6.0)
- [x] PaymoTaskRecurringProfile (added v0.6.0)
- [x] PaymoWebhook (added v0.6.0)
- [x] PaymoProject (added v0.6.0)
- [x] PaymoTask (added v0.6.0)
- [x] PaymoClient (added v0.6.0)
- [x] PaymoUser (added v0.6.0, fixed Guest type Dec 2025)
- [x] PaymoTimeEntry (added v0.6.0)
- [x] PaymoInvoice (added v0.6.0)
- [x] PaymoInvoiceItem (added v0.6.0)
- [x] PaymoInvoicePayment (added v0.6.0)
- [x] PaymoEstimate (added v0.6.0)
- [x] PaymoEstimateItem (added v0.6.0)
- [x] PaymoExpense (added v0.6.0)
- [x] PaymoTasklist (added v0.6.0)
- [x] PaymoMilestone (added v0.6.0)
- [x] PaymoTaskAssignment (added v0.6.0)
- [x] PaymoWorkflow (added v0.6.0)
- [x] PaymoWorkflowStatus (added v0.6.0)
- [x] PaymoFile (added v0.6.0)
- [x] PaymoComment (added v0.6.0)
- [x] PaymoDiscussion (added v0.6.0)
- [x] PaymoClientContact (added v0.6.0)
- [x] PaymoReport (added v0.6.0)
- [x] PaymoCompany (added v0.6.0)
- [x] PaymoSession (added v0.6.0)
- [x] PaymoProjectStatus (added v0.6.0)
- [x] PaymoThread (added v0.6.0)
- [x] PaymoProjectTemplate (added Dec 2025)
- [x] PaymoProjectTemplateTasklist (added Dec 2025)
- [x] PaymoProjectTemplateTask (added Dec 2025)
- [x] PaymoInvoiceTemplate (added Dec 2025)
- [x] PaymoEstimateTemplate (added Dec 2025)
- [x] PaymoInvoiceTemplateGallery (added Dec 2025)
- [x] PaymoEstimateTemplateGallery (added Dec 2025)
- [x] PaymoCommentThread (added Dec 2025)
- [x] PAYMO_WEBHOOK_EVENTS constants (added v0.6.0)

**Note:** All interfaces verified against official Paymo API documentation.

---

## 28. Utility/Helper Features

### 28.1 Time Formatting Utility
Add helper for converting seconds to hours/formatted time:
```php
TimeEntry::formatDuration($seconds) // Returns "2h 30m"
TimeEntry::toHours($seconds) // Returns 2.5
```

### 28.2 Currency Formatting Utility
For invoice/estimate amounts with proper currency display.

---

## 29. Architecture Improvements

### 29.1 Batch Operations
Consider implementing batch create/update/delete for efficiency:
```php
Task::batchCreate($connection, $tasks);
Task::batchUpdate($connection, $tasks);
```

### 29.2 Pagination Helpers
Improve pagination handling for large datasets:
```php
$collection->paginate($perPage, $page);
$collection->hasNextPage();
$collection->getTotalCount();
```

### 29.3 Caching Layer
Consider optional caching for frequently accessed resources:
```php
Client::withCache($ttl)->list();
```

### 29.4 Rate Limiting Handler
Add automatic rate limit handling with retry logic.

---

## Priority Summary

### Completed (v0.6.0) - Full API Verification Audit

**New Resources:**
1. [x] Create `Subtask` resource
2. [x] Create `RecurringProfile` resource (Invoice recurring)
3. [x] Create `RecurringProfileItem` resource
4. [x] Create `TaskRecurringProfile` resource
5. [x] Create `Webhook` resource

**Bug Fixes (existing resources):**
6. [x] Fix `workflow_id` type in Project
7. [x] Fix typo `miletstone` -> `milestone` in Tasklist
8. [x] Fix typo `interger` -> `integer` in Company
9. [x] Add `subtasks` to Task INCLUDE_TYPES
10. [x] Add `subtasks_order` to Task PROP_TYPES

**December 2025 Verification Fixes:**
11. [x] Task: Added `code` to READONLY (auto-generated, read-only per API)
12. [x] User: Added `Guest` to type enum (API supports Admin|Employee|Guest)
13. [x] Expense: Moved `user_id` from undocumented to documented
14. [x] Expense: Removed `invoice_item_id` from READONLY (NOT read-only per API)
15. [x] Expense: Removed incorrect `name` property (NOT in API)
16. [x] ProjectStatus: Fixed INCLUDE_TYPES from `projects => true` to `project => false`
17. [x] TaskRecurringProfile: Added `code` to READONLY (read-only per API)
18. [x] InvoiceItem: Added `entries` property (array of entry IDs for billing)
19. [x] Estimate: Moved `brief_description`, `discount`, `discount_amount`, `discount_text` from undocumented to documented
20. [x] Invoice: Moved `delivery_date` from undocumented to documented

### Previously Completed (v0.6.0)
21. [x] Add Comment PROP_TYPES: `task_id`, `discussion_id`, `file_id` (create-only)
22. [x] Verify Session resource matches API (no changes needed)
23. [x] Add high-priority TypeScript interfaces (Project, Task, Client, User, TimeEntry, Invoice)
24. [x] Add medium-priority TypeScript interfaces (InvoiceItem, InvoicePayment, Estimate, EstimateItem, Expense, Tasklist, Milestone, TaskAssignment)
25. [x] Add remaining TypeScript interfaces (Workflow, WorkflowStatus, File, Comment, Discussion, ClientContact, Report, Company, Session, ProjectStatus, Thread)

### TODO - Future Enhancements (Low Priority)
26. [ ] Implement utility helpers (time formatting, currency)
27. [ ] Consider batch operations
28. [ ] Consider pagination improvements
29. [ ] Consider caching layer

---

## Notes

- All properties and includes in this document have been verified against the official Paymo API documentation at https://github.com/paymoapp/api as of December 2025
- Properties marked as "undocumented" in SDK code may exist in API responses but are not in official docs - use with caution
- The Paymo API may add new properties - periodic review recommended
- Some includes may have limitations based on Paymo subscription level
- Test all changes against live API before committing

---

*Verified against official Paymo API documentation on December 6, 2025. All 38 resource files audited. DO NOT add properties or includes that are not in the official API documentation.*
