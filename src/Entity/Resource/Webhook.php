<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * ======================================================================================
 * WEBHOOK RESOURCE - PAYMO EVENT NOTIFICATION HOOKS
 * ======================================================================================
 *
 * Official API Documentation:
 * https://github.com/paymoapp/api/blob/master/sections/webhooks.md
 *
 * This resource class represents a Paymo Webhook (also called Hook). Webhooks allow
 * you to receive real-time HTTP notifications when events occur in Paymo, such as
 * when tasks are created, invoices are paid, or time entries are logged.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Subscribe to specific events or use wildcards
 * - Optional HMAC-SHA1 signature verification
 * - Conditional filtering with WHERE clauses
 * - Automatic deletion on HTTP 410 responses
 *
 * AVAILABLE EVENTS:
 * -----------------
 * Events follow the pattern: action.Entity
 *
 * Actions:
 * - model.insert - Entity created
 * - model.update - Entity modified
 * - model.delete - Entity removed
 * - timer.start  - Timer started (Entry only)
 * - timer.stop   - Timer stopped (Entry only)
 *
 * Supported Entities:
 * - Client, ClientContact
 * - Project, Tasklist, Task
 * - Invoice, InvoicePayment
 * - Entry (TimeEntry)
 * - Milestone, Report, Expense
 * - Estimate, Comment
 * - User, Booking
 *
 * EVENT EXAMPLES:
 * ---------------
 * - 'model.insert.Task' - New task created
 * - 'model.update.Invoice' - Invoice modified
 * - 'model.delete.Project' - Project deleted
 * - 'timer.start.Entry' - Timer started
 * - '*' - All events
 * - 'model.insert.*' - All create events
 * - '*.Task' - All task events
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Webhook;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create webhook for new task notifications
 * $webhook = new Webhook();
 * $webhook->target_url = 'https://yoursite.com/webhooks/paymo';
 * $webhook->event = 'model.insert.Task';
 * $webhook->create($connection);
 *
 * // Create webhook with secret for signature verification
 * $webhook = new Webhook();
 * $webhook->target_url = 'https://yoursite.com/webhooks/paymo';
 * $webhook->event = 'model.update.Invoice';
 * $webhook->secret = 'your-webhook-secret';
 * $webhook->create($connection);
 *
 * // Create webhook with WHERE filter
 * $webhook = new Webhook();
 * $webhook->target_url = 'https://yoursite.com/webhooks/project-tasks';
 * $webhook->event = 'model.insert.Task';
 * $webhook->where = 'project_id=12345';
 * $webhook->create($connection);
 *
 * // Create wildcard webhook for all events
 * $webhook = new Webhook();
 * $webhook->target_url = 'https://yoursite.com/webhooks/all';
 * $webhook->event = '*';
 * $webhook->create($connection);
 *
 * // List all webhooks
 * $webhooks = Webhook::list($connection);
 *
 * // Delete a webhook
 * $webhook = Webhook::new()->fetch(55555);
 * $webhook->delete($connection);
 * ```
 *
 * WEBHOOK HEADERS:
 * ----------------
 * When Paymo sends a webhook notification, it includes these headers:
 * - X-Paymo-Webhook: The webhook ID
 * - X-Paymo-Event: The actual event that triggered this notification
 * - X-Paymo-Signature: HMAC-SHA1 signature (only if secret was provided)
 * - Content-Type: application/json
 *
 * SIGNATURE VERIFICATION:
 * -----------------------
 * To verify webhook signatures in your receiving endpoint:
 *
 * ```php
 * $payload = file_get_contents('php://input');
 * $signature = $_SERVER['HTTP_X_PAYMO_SIGNATURE'];
 * $expected = hash_hmac('sha1', $payload, $webhookSecret);
 *
 * if (hash_equals($expected, $signature)) {
 *     // Webhook is authentic
 * }
 * ```
 *
 * SPECIAL BEHAVIORS:
 * ------------------
 * - Webhooks only trigger if the creating user has access to the affected object
 * - If your endpoint returns HTTP 410 (Gone), the webhook is automatically deleted
 * - Update operations reset the last_status_code
 * - Delete event payloads only contain the object ID
 * - The secret field is never returned when listing webhooks
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Webhook resource for event notification management.
 *
 * Webhooks allow real-time HTTP notifications when events occur in Paymo.
 * This class provides full CRUD operations for webhook management.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id               Unique webhook ID (read-only)
 * @property string $target_url       Destination URL for notifications (required)
 * @property string $event            Event type to monitor (required)
 * @property string $where            Optional filter conditions
 * @property string $secret           HMAC signing secret (write-only, never returned)
 * @property int    $last_status_code Last HTTP response status (read-only)
 * @property string $created_on       Creation timestamp (read-only)
 * @property string $updated_on       Last update timestamp (read-only)
 */
class Webhook extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Webhook';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'hook';

    /**
     * API endpoint path appended to base URL.
     *
     * Note: The API uses 'hooks' as the endpoint path.
     *
     * @var string
     */
    public const API_PATH = 'hooks';

    /**
     * Properties required when creating a new webhook.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['target_url', 'event'];

    /**
     * Properties that cannot be modified via API.
     *
     * These are set by the server and returned in responses.
     *
     * @var array<string>
     */
    public const READONLY = [
        'id',
        'created_on',
        'updated_on',
        'last_status_code'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for webhooks - all writable properties can be updated.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Webhooks do not have includable relations.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [];

    /**
     * Property type definitions for validation and hydration.
     *
     * Note: 'secret' is write-only and never returned from the API.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
        'id'               => 'integer',
        'created_on'       => 'datetime',
        'updated_on'       => 'datetime',
        'target_url'       => 'url',
        'event'            => 'text',
        'where'            => 'text',
        'secret'           => 'text',
        'last_status_code' => 'integer'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Webhooks do not support WHERE filtering on list operations.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];

    /**
     * Common event constants for convenience.
     *
     * Use these constants to avoid typos when creating webhooks.
     */
    public const EVENT_ALL = '*';
    public const EVENT_ALL_INSERTS = 'model.insert.*';
    public const EVENT_ALL_UPDATES = 'model.update.*';
    public const EVENT_ALL_DELETES = 'model.delete.*';

    // Task events
    public const EVENT_TASK_INSERT = 'model.insert.Task';
    public const EVENT_TASK_UPDATE = 'model.update.Task';
    public const EVENT_TASK_DELETE = 'model.delete.Task';
    public const EVENT_TASK_ALL = '*.Task';

    // Project events
    public const EVENT_PROJECT_INSERT = 'model.insert.Project';
    public const EVENT_PROJECT_UPDATE = 'model.update.Project';
    public const EVENT_PROJECT_DELETE = 'model.delete.Project';
    public const EVENT_PROJECT_ALL = '*.Project';

    // Invoice events
    public const EVENT_INVOICE_INSERT = 'model.insert.Invoice';
    public const EVENT_INVOICE_UPDATE = 'model.update.Invoice';
    public const EVENT_INVOICE_DELETE = 'model.delete.Invoice';
    public const EVENT_INVOICE_ALL = '*.Invoice';

    // Entry (TimeEntry) events
    public const EVENT_ENTRY_INSERT = 'model.insert.Entry';
    public const EVENT_ENTRY_UPDATE = 'model.update.Entry';
    public const EVENT_ENTRY_DELETE = 'model.delete.Entry';
    public const EVENT_ENTRY_ALL = '*.Entry';
    public const EVENT_TIMER_START = 'timer.start.Entry';
    public const EVENT_TIMER_STOP = 'timer.stop.Entry';

    // Client events
    public const EVENT_CLIENT_INSERT = 'model.insert.Client';
    public const EVENT_CLIENT_UPDATE = 'model.update.Client';
    public const EVENT_CLIENT_DELETE = 'model.delete.Client';
    public const EVENT_CLIENT_ALL = '*.Client';

    // Payment events
    public const EVENT_PAYMENT_INSERT = 'model.insert.InvoicePayment';
    public const EVENT_PAYMENT_UPDATE = 'model.update.InvoicePayment';
    public const EVENT_PAYMENT_DELETE = 'model.delete.InvoicePayment';

    // User events
    public const EVENT_USER_INSERT = 'model.insert.User';
    public const EVENT_USER_UPDATE = 'model.update.User';
    public const EVENT_USER_DELETE = 'model.delete.User';
}
