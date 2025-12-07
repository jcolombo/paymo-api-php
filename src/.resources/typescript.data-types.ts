/*
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 12/7/25
 * .
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * .
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * .
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

// START DATA TYPES for Typescript use if calling PHP SDK via Typescript JSON (for example with React/Redux calls)

export interface PaymoBooking {
  id: number;
  created_on: string;
  updated_on: string;
  user_task_id: number;
  start_date: string;
  end_date: string;
  hours_per_day: number;
  description?: string;
  creator_id: number;
  user_id: number;
  start_time?: string;
  end_time?: string;
  booked_hours: number;
}

/**
 * TypeScript interface for Paymo Subtask entity (checklist item).
 *
 * Corresponds to: src/Entity/Resource/Subtask.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/subtasks.md
 *
 * Subtasks are checklist items within tasks, allowing tasks to be broken
 * down into smaller, trackable steps.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoSubtask {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  project_id: number;
  completed_on?: string;
  completed_by?: number;

  // Required properties
  name: string;
  task_id: number;

  // Optional properties
  complete?: boolean;
  user_id?: number;
  seq?: number;

  // Included relations (optional - only present when requested)
  project?: PaymoProject;
  task?: PaymoTask;
  user?: PaymoUser;
}

/**
 * TypeScript interface for Paymo Recurring Profile entity (Invoice Recurring).
 *
 * Corresponds to: src/Entity/Resource/RecurringProfile.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/invoice_recurring_profiles.md
 *
 * Recurring profiles automate invoice generation on scheduled intervals.
 * Invoices are created daily at 9 AM UTC based on the configured frequency.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoRecurringProfile {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  subtotal: number;
  total: number;
  tax_amount: number;
  tax2_amount: number;
  discount_amount: number;
  invoices_created: number;
  last_created?: string;

  // Required properties
  client_id: number;
  currency: string;
  frequency: 'w' | '2w' | '3w' | '4w' | 'm' | '2m' | '3m' | '6m' | 'y';
  start_date: string;

  // Optional properties
  template_id?: number;
  title?: string;
  tax?: number;
  tax_text?: string;
  tax2?: number;
  tax2_text?: string;
  tax_on_tax?: boolean;
  discount?: number;
  discount_text?: string;
  occurrences?: number;
  autosend?: boolean;
  bill_to?: string;
  company_info?: string;
  footer?: string;
  notes?: string;
  pay_online?: boolean;
  send_attachment?: boolean;
  options?: Record<string, any>;

  // Included relations (optional - only present when requested)
  client?: PaymoClient;
  recurringprofileitems?: PaymoRecurringProfileItem[];
}

/**
 * TypeScript interface for Paymo Recurring Profile Item entity.
 *
 * Corresponds to: src/Entity/Resource/RecurringProfileItem.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/invoice_recurring_profiles.md
 *
 * Line items for recurring profiles. Each item will appear on invoices
 * generated from the parent profile.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoRecurringProfileItem {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;

  // Required properties
  recurring_profile_id: number;
  item: string;
  price_unit: number;
  quantity: number;

  // Optional properties
  description?: string;
  apply_tax?: boolean;
  seq?: number;

  // Included relations (optional - only present when requested)
  recurringprofile?: PaymoRecurringProfile;
}

/**
 * TypeScript interface for Paymo Task Recurring Profile entity.
 *
 * Corresponds to: src/Entity/Resource/TaskRecurringProfile.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/task_recurring_profiles.md
 *
 * Task recurring profiles automate task creation on scheduled intervals
 * (daily, weekly, or monthly).
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoTaskRecurringProfile {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  user_id: number;
  task_user_id?: number;
  generated_count: number;
  last_generated_on?: string;
  next_processing_date?: string;

  // Required properties
  name: string;
  frequency: 'daily' | 'weekly' | 'monthly';
  interval: number;
  recurring_start_date: string;

  // Required at creation (one of)
  project_id?: number;
  task_id?: number;

  // Optional properties
  code?: string;
  tasklist_id?: number;
  company_id?: number;
  billable?: boolean;
  flat_billing?: boolean;
  description?: string;
  price_per_hour?: number;
  estimated_price?: number;
  budget_hours?: number;
  users?: number[];
  priority?: 25 | 50 | 75 | 100;
  notifications?: string;
  on_day?: string;
  occurrences?: number;
  until?: string;
  active?: boolean;
  due_date_offset?: number;
  processing_timezone?: string;
  processing_hour?: string;

  // Included relations (optional - only present when requested)
  project?: PaymoProject;
}

/**
 * TypeScript interface for Paymo Webhook entity.
 *
 * Corresponds to: src/Entity/Resource/Webhook.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/webhooks.md
 *
 * Webhooks allow real-time HTTP notifications when events occur in Paymo.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoWebhook {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  last_status_code?: number;

  // Required properties
  target_url: string;
  event: string;

  // Optional properties
  where?: string;
  secret?: string; // Write-only, never returned from API
}

/**
 * Webhook event constants for TypeScript.
 *
 * Use these when creating webhooks to avoid typos.
 */
export const PAYMO_WEBHOOK_EVENTS = {
  ALL: '*',
  ALL_INSERTS: 'model.insert.*',
  ALL_UPDATES: 'model.update.*',
  ALL_DELETES: 'model.delete.*',

  // Task events
  TASK_INSERT: 'model.insert.Task',
  TASK_UPDATE: 'model.update.Task',
  TASK_DELETE: 'model.delete.Task',
  TASK_ALL: '*.Task',

  // Project events
  PROJECT_INSERT: 'model.insert.Project',
  PROJECT_UPDATE: 'model.update.Project',
  PROJECT_DELETE: 'model.delete.Project',
  PROJECT_ALL: '*.Project',

  // Invoice events
  INVOICE_INSERT: 'model.insert.Invoice',
  INVOICE_UPDATE: 'model.update.Invoice',
  INVOICE_DELETE: 'model.delete.Invoice',
  INVOICE_ALL: '*.Invoice',

  // Entry (TimeEntry) events
  ENTRY_INSERT: 'model.insert.Entry',
  ENTRY_UPDATE: 'model.update.Entry',
  ENTRY_DELETE: 'model.delete.Entry',
  ENTRY_ALL: '*.Entry',
  TIMER_START: 'timer.start.Entry',
  TIMER_STOP: 'timer.stop.Entry',

  // Client events
  CLIENT_INSERT: 'model.insert.Client',
  CLIENT_UPDATE: 'model.update.Client',
  CLIENT_DELETE: 'model.delete.Client',
  CLIENT_ALL: '*.Client',

  // Payment events
  PAYMENT_INSERT: 'model.insert.InvoicePayment',
  PAYMENT_UPDATE: 'model.update.InvoicePayment',
  PAYMENT_DELETE: 'model.delete.InvoicePayment',

  // User events
  USER_INSERT: 'model.insert.User',
  USER_UPDATE: 'model.update.User',
  USER_DELETE: 'model.delete.User',
} as const;

export type PaymoWebhookEvent = typeof PAYMO_WEBHOOK_EVENTS[keyof typeof PAYMO_WEBHOOK_EVENTS];

/**
 * TypeScript interface for Paymo Project entity.
 *
 * Corresponds to: src/Entity/Resource/Project.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/projects.md
 *
 * Projects are the primary containers for organizing work in Paymo,
 * containing tasklists, tasks, discussions, files, and time tracking data.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoProject {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  task_code_increment: number;

  // Required properties
  name: string;

  // Optional properties
  code?: string;
  description?: string;
  client_id?: number;
  status_id?: number;
  active?: boolean;
  color?: string;
  users?: number[];
  managers?: number[];
  billable?: boolean;
  flat_billing?: boolean;
  price_per_hour?: number;
  price?: number;
  estimated_price?: number;
  hourly_billing_mode?: string;
  budget_hours?: number;
  adjustable_hours?: boolean;
  invoiced?: boolean;
  invoice_item_id?: number;
  workflow_id?: number;

  // Included relations (optional - only present when requested)
  client?: PaymoClient;
  projectstatus?: PaymoProjectStatus;
  tasklists?: PaymoTasklist[];
  tasks?: PaymoTask[];
  milestones?: PaymoMilestone[];
  discussions?: PaymoDiscussion[];
  files?: PaymoFile[];
  invoiceitem?: PaymoInvoiceItem;
  workflow?: PaymoWorkflow;
}

/**
 * TypeScript interface for Paymo Task entity.
 *
 * Corresponds to: src/Entity/Resource/Task.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/tasks.md
 *
 * Tasks are the fundamental work units in Paymo, belonging to tasklists
 * within projects.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoTask {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  code?: string;
  project_id: number;
  completed_on?: string;
  completed_by?: number;

  // Required properties
  name: string;

  // Required for creation (one of)
  tasklist_id?: number;
  // project_id is also accepted for creation

  // Optional properties
  seq?: number;
  description?: string;
  complete?: boolean;
  due_date?: string;
  user_id?: number;
  users?: number[];
  billable?: boolean;
  flat_billing?: boolean;
  price_per_hour?: number;
  budget_hours?: number;
  estimated_price?: number;
  invoiced?: boolean;
  invoice_item_id?: number;
  priority?: 25 | 50 | 75 | 100;
  status_id?: number;
  subtasks_order?: number[];

  // Included relations (optional - only present when requested)
  project?: PaymoProject;
  tasklist?: PaymoTasklist;
  user?: PaymoUser;
  thread?: PaymoThread;
  entries?: PaymoTimeEntry[];
  subtasks?: PaymoSubtask[];
  invoiceitem?: PaymoInvoiceItem;
  workflowstatus?: PaymoWorkflowStatus;
}

/**
 * TypeScript interface for Paymo User entity.
 *
 * Corresponds to: src/Entity/Resource/User.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/users.md
 *
 * Users are team members with access to a Paymo account.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoUser {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  image?: string;
  image_thumb_large?: string;
  image_thumb_medium?: string;
  image_thumb_small?: string;
  is_online?: boolean;

  // Required properties
  email: string;

  // Optional properties
  name?: string;
  type?: 'Admin' | 'Employee' | 'Guest';
  active?: boolean;
  timezone?: string;
  phone?: string;
  skype?: string;
  position?: string;
  workday_hours?: number;
  price_per_hour?: number;
  date_format?: string;
  time_format?: string;
  decimal_sep?: string;
  thousands_sep?: string;
  week_start?: number;
  language?: string;
  theme?: string;
  assigned_projects?: number[];
  managed_projects?: number[];
  password?: string; // Write-only

  // Included relations (optional - only present when requested)
  comments?: PaymoComment[];
  discussions?: PaymoDiscussion[];
  entries?: PaymoTimeEntry[];
  expenses?: PaymoExpense[];
  files?: PaymoFile[];
  milestones?: PaymoMilestone[];
  reports?: PaymoReport[];
}

/**
 * TypeScript interface for Paymo Client entity.
 *
 * Corresponds to: src/Entity/Resource/Client.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/clients.md
 *
 * Clients are the entities that projects are billed to.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoClient {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  active?: boolean;
  image?: string;
  image_thumb_large?: string;
  image_thumb_medium?: string;
  image_thumb_small?: string;

  // Required properties
  name: string;

  // Optional properties
  address?: string;
  city?: string;
  postal_code?: string;
  country?: string;
  state?: string;
  phone?: string;
  fax?: string;
  email?: string;
  website?: string;
  fiscal_information?: string;

  // Included relations (optional - only present when requested)
  clientcontacts?: PaymoClientContact[];
  projects?: PaymoProject[];
  invoices?: PaymoInvoice[];
  recurringprofiles?: PaymoRecurringProfile[];
}

/**
 * TypeScript interface for Paymo TimeEntry (Entry) entity.
 *
 * Corresponds to: src/Entity/Resource/TimeEntry.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/entries.md
 *
 * Time entries are records of time logged against tasks.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoTimeEntry {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  project_id: number;
  is_bulk?: boolean;

  // Required properties
  task_id: number;

  // Required for creation (one combination):
  // date + duration (manual entry)
  // start_time + end_time (timed entry)
  // user_id + start_time (running timer)

  // Optional properties
  user_id?: number;
  start_time?: string;
  end_time?: string;
  date?: string;
  duration?: number; // in seconds
  description?: string;
  added_manually?: boolean;
  billed?: boolean;
  invoice_item_id?: number;

  // Included relations (optional - only present when requested)
  task?: PaymoTask;
  invoiceitem?: PaymoInvoiceItem;
  user?: PaymoUser;
}

/**
 * TypeScript interface for Paymo Invoice entity.
 *
 * Corresponds to: src/Entity/Resource/Invoice.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/invoices.md
 *
 * Invoices are billing documents sent to clients.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoInvoice {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  subtotal: number;
  total: number;
  tax_amount?: number;
  tax2_amount?: number;
  discount_amount?: number;
  outstanding?: number;
  permalink?: string;
  pdf_link?: string;
  download_token?: string;
  token?: string;
  reminder_1_sent?: boolean;
  reminder_2_sent?: boolean;
  reminder_3_sent?: boolean;

  // Required properties
  client_id: number;
  currency: string;

  // Optional properties
  number?: string;
  template_id?: number;
  status?: 'draft' | 'sent' | 'viewed' | 'paid' | 'void';
  date?: string;
  due_date?: string;
  delivery_date?: string;
  tax?: number;
  tax_text?: string;
  tax2?: number;
  tax2_text?: string;
  tax_on_tax?: boolean;
  discount?: number;
  discount_text?: string;
  language?: string;
  bill_to?: string;
  company_info?: string;
  footer?: string;
  notes?: string;
  title?: string;
  pay_online?: boolean;

  // Included relations (optional - only present when requested)
  client?: PaymoClient;
  invoiceitems?: PaymoInvoiceItem[];
  invoicepayments?: PaymoInvoicePayment[];
  invoicetemplate?: PaymoInvoiceTemplate;
}

/**
 * TypeScript interface for Paymo InvoiceItem entity.
 *
 * Corresponds to: src/Entity/Resource/InvoiceItem.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/invoices.md
 *
 * Line items on invoices representing services, products, or billable items.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoInvoiceItem {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;

  // Required properties
  item: string;

  // Optional properties
  invoice_id?: number;
  description?: string;
  price_unit?: number;
  quantity?: number;
  expense_id?: number;
  apply_tax?: boolean;
  seq?: number;

  // Included relations (optional - only present when requested)
  invoice?: PaymoInvoice;
  expense?: PaymoExpense;
  entries?: PaymoTimeEntry[];
  projects?: PaymoProject[];
  tasks?: PaymoTask[];
}

/**
 * TypeScript interface for Paymo InvoicePayment entity.
 *
 * Corresponds to: src/Entity/Resource/InvoicePayment.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/invoice_payments.md
 *
 * Payments recorded against invoices.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoInvoicePayment {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  invoice_id: number;

  // Required properties
  amount: number;

  // Optional properties
  date?: string;
  notes?: string;

  // Included relations (optional - only present when requested)
  invoice?: PaymoInvoice;
}

/**
 * TypeScript interface for Paymo Estimate entity.
 *
 * Corresponds to: src/Entity/Resource/Estimate.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/estimates.md
 *
 * Estimates/quotes sent to clients before work begins.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoEstimate {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  subtotal: number;
  total: number;
  tax_amount?: number;
  tax2_amount?: number;
  discount_amount?: number;
  permalink?: string;
  pdf_link?: string;

  // Required properties
  client_id: number;
  currency: string;

  // Optional properties
  number?: string;
  template_id?: number;
  status?: 'draft' | 'sent' | 'viewed' | 'accepted' | 'invoiced' | 'void';
  date?: string;
  tax?: number;
  tax_text?: string;
  tax2?: number;
  tax2_text?: string;
  tax_on_tax?: boolean;
  discount?: number;
  discount_text?: string;
  language?: string;
  bill_to?: string;
  company_info?: string;
  footer?: string;
  notes?: string;
  title?: string;
  brief_description?: string;
  invoice_id?: number;

  // Included relations (optional - only present when requested)
  client?: PaymoClient;
  invoice?: PaymoInvoice;
  estimateitems?: PaymoEstimateItem[];
  estimatetemplate?: PaymoEstimateTemplate;
}

/**
 * TypeScript interface for Paymo EstimateItem entity.
 *
 * Corresponds to: src/Entity/Resource/EstimateItem.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/estimates.md
 *
 * Line items on estimates.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoEstimateItem {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;

  // Required properties
  estimate_id: number;
  item: string;
  price_unit: number;
  quantity: number;

  // Optional properties
  description?: string;
  apply_tax?: boolean;
  seq?: number;

  // Included relations (optional - only present when requested)
  estimate?: PaymoEstimate;
}

/**
 * TypeScript interface for Paymo Expense entity.
 *
 * Corresponds to: src/Entity/Resource/Expense.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/expenses.md
 *
 * Expense records for tracking costs on projects.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoExpense {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  image_thumb_large?: string;
  image_thumb_medium?: string;
  image_thumb_small?: string;

  // Required properties
  amount: number;

  // Optional properties
  client_id?: number;
  project_id?: number;
  user_id?: number;
  currency?: string;
  date?: string;
  notes?: string;
  invoiced?: boolean;
  invoice_item_id?: number;
  tags?: string[];
  file?: string;

  // Included relations (optional - only present when requested)
  client?: PaymoClient;
  project?: PaymoProject;
  user?: PaymoUser;
  invoiceitems?: PaymoInvoiceItem[];
}

/**
 * TypeScript interface for Paymo Tasklist entity.
 *
 * Corresponds to: src/Entity/Resource/Tasklist.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/tasklists.md
 *
 * Task lists are containers for organizing tasks within a project.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoTasklist {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  project_id: number;

  // Required properties
  name: string;

  // Optional properties
  seq?: number;
  milestone_id?: number;

  // Included relations (optional - only present when requested)
  project?: PaymoProject;
  milestone?: PaymoMilestone;
  tasks?: PaymoTask[];
}

/**
 * TypeScript interface for Paymo Milestone entity.
 *
 * Corresponds to: src/Entity/Resource/Milestone.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/milestones.md
 *
 * Milestones are project checkpoints with due dates.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoMilestone {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  reminder_sent?: boolean;

  // Required properties
  name: string;
  project_id: number;

  // Optional properties
  user_id?: number;
  due_date?: string;
  send_reminder?: number;
  complete?: boolean;
  linked_tasklists?: number[];

  // Included relations (optional - only present when requested)
  project?: PaymoProject;
  user?: PaymoUser;
  tasklists?: PaymoTasklist[];
}

/**
 * TypeScript interface for Paymo TaskAssignment (UserTask) entity.
 *
 * Corresponds to: src/Entity/Resource/TaskAssignment.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/users_tasks.md
 *
 * Represents the assignment of a user to a task.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoTaskAssignment {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;

  // Required properties
  user_id: number;
  task_id: number;

  // Included relations (optional - only present when requested)
  user?: PaymoUser;
  task?: PaymoTask;
}

/**
 * TypeScript interface for Paymo Workflow entity.
 *
 * Corresponds to: src/Entity/Resource/Workflow.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/workflows.md
 *
 * Workflows define the status progression for tasks.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoWorkflow {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;

  // Required properties
  name: string;

  // Optional properties
  is_default?: boolean;

  // Included relations (optional - only present when requested)
  workflowstatuses?: PaymoWorkflowStatus[];
}

/**
 * TypeScript interface for Paymo WorkflowStatus entity.
 *
 * Corresponds to: src/Entity/Resource/WorkflowStatus.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/workflow_statuses.md
 *
 * Workflow statuses represent stages in a workflow (e.g., To Do, In Progress, Done).
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoWorkflowStatus {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  seq: number;
  action?: string;

  // Required properties
  name: string;
  workflow_id: number;

  // Optional properties
  color?: string;

  // Included relations (optional - only present when requested)
  workflow?: PaymoWorkflow;
}

/**
 * TypeScript interface for Paymo File entity.
 *
 * Corresponds to: src/Entity/Resource/File.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/files.md
 *
 * Files attached to projects, tasks, discussions, or comments.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoFile {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  user_id: number;
  project_id?: number;
  discussion_id?: number;
  task_id?: number;
  comment_id?: number;
  token?: string;
  size?: number;
  file?: string;
  image_thumb_large?: string;
  image_thumb_medium?: string;
  image_thumb_small?: string;

  // Optional properties
  original_filename?: string;
  description?: string;

  // Included relations (optional - only present when requested)
  project?: PaymoProject;
  user?: PaymoUser;
  discussion?: PaymoDiscussion;
  task?: PaymoTask;
  comment?: PaymoComment;
}

/**
 * TypeScript interface for Paymo Comment entity.
 *
 * Corresponds to: src/Entity/Resource/Comment.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/comments.md
 *
 * Comments on tasks, discussions, or files.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoComment {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  thread_id: number;
  user_id: number;

  // Required properties
  content: string;

  // Create-only properties (specify target for new comment)
  task_id?: number;
  discussion_id?: number;
  file_id?: number;

  // Included relations (optional - only present when requested)
  thread?: PaymoThread;
  user?: PaymoUser;
  project?: PaymoProject;
  files?: PaymoFile[];
}

/**
 * TypeScript interface for Paymo Discussion entity.
 *
 * Corresponds to: src/Entity/Resource/Discussion.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/discussions.md
 *
 * Discussions are project-level conversation threads.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoDiscussion {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  user_id: number;

  // Required properties
  name: string;
  project_id: number;

  // Optional properties
  description?: string;

  // Included relations (optional - only present when requested)
  project?: PaymoProject;
  user?: PaymoUser;
  thread?: PaymoThread;
  files?: PaymoFile[];
}

/**
 * TypeScript interface for Paymo ClientContact entity.
 *
 * Corresponds to: src/Entity/Resource/ClientContact.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/client_contacts.md
 *
 * Contact persons associated with clients.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoClientContact {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  image_thumb_large?: string;
  image_thumb_medium?: string;
  image_thumb_small?: string;

  // Required properties
  client_id: number;
  name: string;

  // Optional properties
  email?: string;
  mobile?: string;
  phone?: string;
  fax?: string;
  skype?: string;
  notes?: string;
  position?: string;
  is_main?: boolean;
  access?: boolean;
  image?: string;

  // Included relations (optional - only present when requested)
  client?: PaymoClient;
}

/**
 * TypeScript interface for Paymo Report entity.
 *
 * Corresponds to: src/Entity/Resource/Report.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/reports.md
 *
 * Time and expense reports.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoReport {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;

  // Required properties
  name: string;

  // Optional properties
  user_id?: number;
  type?: 'static' | 'live' | 'temp';
  start_date?: string;
  end_date?: string;
  date_interval?: string;
  projects?: string;
  clients?: string;
  users?: string;
  include?: Record<string, any>;
  extra?: Record<string, any>;
  info?: Record<string, any>;
  content?: Record<string, any>;
  permalink?: string;
  shared?: boolean;
  share_client_id?: number;

  // Included relations (optional - only present when requested)
  user?: PaymoUser;
  client?: PaymoClient;
}

/**
 * TypeScript interface for Paymo Company entity.
 *
 * Corresponds to: src/Entity/Resource/Company.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/company.md
 *
 * Company settings and account information.
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoCompany {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  image_thumb_large?: string;
  image_thumb_medium?: string;
  image_thumb_small?: string;
  account_type?: string;
  max_users?: number;
  current_users?: number;
  max_projects?: number;
  current_projects?: number;
  max_invoices?: number;
  current_invoices?: number;

  // Optional properties
  name?: string;
  address?: string;
  phone?: string;
  email?: string;
  url?: string;
  fiscal_information?: string;
  country?: string;
  image?: string;
  timezone?: string;
  default_currency?: string;
  default_price_per_hour?: number;
  apply_tax_to_expenses?: boolean;
  tax_on_tax?: boolean;
  currency_position?: string;
  next_invoice_number?: string;
  next_estimate_number?: string;
  online_payments?: boolean;
  date_format?: string;
  time_format?: string;
  decimal_sep?: string;
  thousands_sep?: string;
  week_start?: number;
  workday_start?: string;
  workday_end?: string;
  working_days?: string;
}

/**
 * TypeScript interface for Paymo Session entity.
 *
 * Corresponds to: src/Entity/Resource/Session.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/sessions.md
 *
 * Authentication sessions (for password-based auth, not API keys).
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoSession {
  // All properties are read-only
  id: string; // Note: Session ID is a string, not a number
  created_on: string;
  updated_on: string;
  ip: string;
  expires_on: string;
  user_id: number;
}

/**
 * TypeScript interface for Paymo ProjectStatus entity.
 *
 * Corresponds to: src/Entity/Resource/ProjectStatus.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/project_statuses.md
 *
 * Project statuses for categorizing projects (e.g., Active, Archived).
 *
 * @see PROP_TYPES in the PHP resource class for authoritative property definitions
 */
export interface PaymoProjectStatus {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  readonly?: boolean;

  // Required properties
  name: string;

  // Optional properties
  active?: boolean;
  seq?: number;

  // Included relations (optional - only present when requested)
  projects?: PaymoProject[];
}

/**
 * TypeScript interface for Paymo Thread entity.
 *
 * Threads are containers for comments. They are automatically created
 * when comments are added to tasks, discussions, or files.
 *
 * @see Comment resource for creating comments with thread targets
 */
export interface PaymoThread {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  project_id?: number;
  discussion_id?: number;
  task_id?: number;
  file_id?: number;

  // Included relations (optional - only present when requested)
  comments?: PaymoComment[];
}

/**
 * TypeScript interface for Paymo Project Template entity.
 *
 * Corresponds to: src/Entity/Resource/ProjectTemplate.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/project_templates.md
 *
 * Project templates define reusable project structures including
 * tasklists and tasks that can be applied when creating new projects.
 */
export interface PaymoProjectTemplate {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;

  // Required properties
  name: string;

  // Create-only properties (used when creating from existing project)
  project_id?: number;

  // Included relations (optional - only present when requested)
  projecttemplatestasklists?: PaymoProjectTemplateTasklist[];
  projecttemplatestasks?: PaymoProjectTemplateTask[];
}

/**
 * TypeScript interface for Paymo Project Template Tasklist entity.
 *
 * Corresponds to: src/Entity/Resource/ProjectTemplateTasklist.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/project_templates.md
 *
 * Template tasklists are containers for template tasks within a project template.
 */
export interface PaymoProjectTemplateTasklist {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;

  // Required properties
  name: string;
  template_id: number;

  // Optional properties
  seq?: number;
  milestone_id?: number;

  // Included relations (optional - only present when requested)
  projecttemplate?: PaymoProjectTemplate;
  projecttemplatestasks?: PaymoProjectTemplateTask[];
}

/**
 * TypeScript interface for Paymo Project Template Task entity.
 *
 * Corresponds to: src/Entity/Resource/ProjectTemplateTask.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/project_templates.md
 *
 * Template tasks define task configurations within project templates.
 */
export interface PaymoProjectTemplateTask {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;

  // Required properties
  name: string;
  tasklist_id: number;

  // Optional properties
  template_id?: number;
  seq?: number;
  description?: string;
  billable?: boolean;
  budget_hours?: number;
  price_per_hour?: number;
  users?: number[];
  flat_billing?: boolean;
  estimated_price?: number;
  price?: number;
  duration?: number;
  start_date_offset?: number;

  // Included relations (optional - only present when requested)
  projecttemplate?: PaymoProjectTemplate;
  projecttemplatetasklist?: PaymoProjectTemplateTasklist;
}

/**
 * TypeScript interface for Paymo Invoice Template entity.
 *
 * Corresponds to: src/Entity/Resource/InvoiceTemplate.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/invoice_templates.md
 *
 * Invoice templates define the layout and styling for invoices.
 */
export interface PaymoInvoiceTemplate {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  invoices_count?: number;

  // Required properties
  name: string;

  // Optional properties
  title?: string;
  html?: string;
  css?: string;
  is_default?: boolean;

  // Included relations (optional - only present when requested)
  invoices?: PaymoInvoice[];
}

/**
 * TypeScript interface for Paymo Estimate Template entity.
 *
 * Corresponds to: src/Entity/Resource/EstimateTemplate.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/estimate_templates.md
 *
 * Estimate templates define the layout and styling for estimates.
 */
export interface PaymoEstimateTemplate {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;
  estimates_count?: number;

  // Required properties
  name: string;

  // Optional properties
  title?: string;
  html?: string;
  css?: string;
  is_default?: boolean;

  // Included relations (optional - only present when requested)
  estimates?: PaymoEstimate[];
}

/**
 * TypeScript interface for Paymo Invoice Template Gallery entity.
 *
 * Corresponds to: src/Entity/Resource/InvoiceTemplateGallery.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/invoice_templates.md
 *
 * Gallery templates are pre-made invoice templates provided by Paymo.
 * These are read-only and cannot be created, updated, or deleted.
 */
export interface PaymoInvoiceTemplateGallery {
  // Read-only properties (all properties are read-only)
  id: number;
  created_on: string;
  updated_on: string;
  name: string;
  title: string;
  html: string;
  css: string;
  image: string;
}

/**
 * TypeScript interface for Paymo Estimate Template Gallery entity.
 *
 * Corresponds to: src/Entity/Resource/EstimateTemplateGallery.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/estimate_templates.md
 *
 * Gallery templates are pre-made estimate templates provided by Paymo.
 * These are read-only and cannot be created, updated, or deleted.
 */
export interface PaymoEstimateTemplateGallery {
  // Read-only properties (all properties are read-only)
  id: number;
  created_on: string;
  updated_on: string;
  name: string;
  title: string;
  html: string;
  css: string;
  image: string;
}

/**
 * TypeScript interface for Paymo Comment Thread entity.
 *
 * Corresponds to: src/Entity/Resource/CommentThread.php
 * Official API: https://github.com/paymoapp/api/blob/master/sections/threads.md
 *
 * Comment threads are containers for comments on tasks, discussions, or files.
 * Threads are typically accessed through their parent entity's include.
 */
export interface PaymoCommentThread {
  // Read-only properties
  id: number;
  created_on: string;
  updated_on: string;

  // Parent references (one of these will be set)
  project_id?: number;
  task_id?: number;
  discussion_id?: number;

  // Included relations (optional - only present when requested)
  comments?: PaymoComment[];
}

