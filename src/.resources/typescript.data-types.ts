/*
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 * .
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/19/20, 1:43 PM
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
  hours_per_date: number;
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

// Forward declarations for referenced types (these would be defined elsewhere)
declare interface PaymoProject {}
declare interface PaymoTask {}
declare interface PaymoUser {}
declare interface PaymoClient {}

