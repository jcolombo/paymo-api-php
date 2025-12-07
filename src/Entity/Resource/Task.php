<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 9:23 PM
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
 * TASK RESOURCE - PAYMO TASK/WORK ITEM MANAGEMENT
 * ======================================================================================
 *
 * This resource class represents a Paymo task. Tasks are the fundamental work units
 * in Paymo, belonging to tasklists within projects. They track work items, time entries,
 * assignments, priorities, due dates, and billing information.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Tasklist and project association
 * - User assignment and multi-user support
 * - Priority levels (25=Low, 50=Normal, 75=High, 100=Critical)
 * - Due date and completion tracking
 * - Billing configuration (hourly or flat-rate)
 * - Workflow status integration
 * - Time entry associations
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique task identifier (read-only)
 * - name: Task name/title (required for creation)
 * - code: Auto-generated task code based on project prefix
 * - description: Detailed task description
 * - seq: Sequence/order within tasklist
 *
 * Association Properties:
 * - project_id: Parent project ID (create-only)
 * - tasklist_id: Parent tasklist ID
 * - user_id: Primary assigned user
 * - users: Array of assigned user IDs
 * - status_id: Workflow status reference
 *
 * Status Properties:
 * - complete: Whether task is completed
 * - completed_on: Completion timestamp (read-only)
 * - completed_by: User who completed task (read-only)
 * - due_date: Task due date
 * - priority: Priority level (25, 50, 75, 100)
 *
 * Billing Properties:
 * - billable: Whether time is billable
 * - flat_billing: True for flat-rate billing
 * - price_per_hour: Hourly billing rate
 * - budget_hours: Budgeted hours
 * - estimated_price: Estimated task value
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Task;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new task in a tasklist
 * $task = new Task();
 * $task->name = 'Implement login form';
 * $task->description = 'Build secure authentication form';
 * $task->tasklist_id = 12345;
 * $task->user_id = 67890;
 * $task->priority = 75; // High priority
 * $task->due_date = '2024-02-15';
 * $task->billable = true;
 * $task->budget_hours = 8;
 * $task->create($connection);
 *
 * // Create task directly in project (auto-assigns to first tasklist)
 * $task = new Task();
 * $task->name = 'Project Setup';
 * $task->project_id = 11111;
 * $task->create($connection);
 *
 * // Fetch a task with related data
 * $task = Task::fetch($connection, 55555, [
 *     'include' => ['project', 'tasklist', 'entries', 'user']
 * ]);
 *
 * // List incomplete tasks for a project
 * $tasks = Task::list($connection, [
 *     'where' => [
 *         RequestCondition::where('project_id', 12345),
 *         RequestCondition::where('complete', false),
 *     ]
 * ]);
 *
 * // Mark task as complete
 * $task->complete = true;
 * $task->update($connection);
 *
 * // Move task to different tasklist
 * $task->tasklist_id = 22222;
 * $task->update($connection);
 * ```
 *
 * PRIORITY LEVELS:
 * ----------------
 * Tasks support four priority levels as integer values:
 * - 25: Low priority
 * - 50: Normal priority (default)
 * - 75: High priority
 * - 100: Critical/Urgent priority
 *
 * CREATION REQUIREMENTS:
 * ----------------------
 * A task requires 'name' AND either 'tasklist_id' OR 'project_id':
 * - tasklist_id: Task added to specific tasklist
 * - project_id: Task added to project's first/default tasklist
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Tasklist Parent tasklist resource
 * @see        Project Parent project resource
 * @see        TimeEntry Time tracking for tasks
 * @see        User Task assignees
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Task resource for work item management operations.
 *
 * Tasks are the primary work units in Paymo, organized within tasklists and
 * projects. This class provides full CRUD operations and supports related
 * entity includes for comprehensive task data retrieval.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                  Unique task ID (read-only)
 * @property string $name                Task name (required)
 * @property string $code                Auto-generated task code (read-only)
 * @property int    $project_id          Parent project ID (create-only)
 * @property int    $tasklist_id         Parent tasklist ID
 * @property int    $seq                 Sequence order in tasklist
 * @property string $description         Task description
 * @property bool   $complete            Whether task is completed
 * @property string $completed_on        Completion timestamp (read-only)
 * @property int    $completed_by        User ID who completed (read-only)
 * @property string $due_date            Due date (YYYY-MM-DD)
 * @property int    $user_id             Primary assigned user ID
 * @property array  $users               Assigned user IDs
 * @property bool   $billable            Whether time is billable
 * @property bool   $flat_billing        Flat-rate vs hourly
 * @property float  $price_per_hour      Hourly rate
 * @property float  $budget_hours        Budgeted hours
 * @property float  $estimated_price     Estimated value
 * @property int    $priority            Priority (25|50|75|100)
 * @property int    $status_id           Workflow status ID
 * @property string $created_on          Creation timestamp (read-only)
 * @property string $updated_on          Last update timestamp (read-only)
 */
class Task extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Task';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'task';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'tasks';

    /**
     * Properties required when creating a new task.
     *
     * Uses OR syntax: 'tasklist_id||project_id' means AT LEAST ONE must be set.
     * Single pipe '|' would mean EXACTLY ONE must be set.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name', 'tasklist_id||project_id'];

    /**
     * Properties that cannot be modified via API.
     *
     * These are set by the server and returned in responses but cannot
     * be included in update requests.
     *
     * @var array<string>
     */
    public const READONLY = [
      'id',
      'created_on',
      'updated_on',
      'code',       // Auto-generated from project code + task number
      'project_id', // Set by tasklist, can only be set via create with project_id
      'completed_on',
      'completed_by',
      'invoiced',
        // Undocumented props set to readonly
      'cover_file_id',
      'price',
      'start_date',
      'recurring_profile_id',
      'billing_type'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * project_id can only be set at creation time. Once created, a task
     * cannot be moved to a different project (only to different tasklists
     * within the same project).
     *
     * @var array<string>
     */
    public const CREATEONLY = ['project_id'];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls.
     * TRUE indicates a collection (multiple items), FALSE indicates single entity.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'project'        => false,
      'tasklist'       => false,
      'user'           => false,
      'thread'         => false,
      'entries'        => true,
      'subtasks'       => true,
      'invoiceitem'    => false,
      'workflowstatus' => false
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Defines the data type for each property. Special types:
     * - 'intEnum:25|50|75|100': Integer enumeration (priority values)
     * - 'resource:X': Reference to another entity type
     * - 'collection:X': Array of entity references
     * - 'date': Date in YYYY-MM-DD format
     * - 'datetime': ISO 8601 timestamp
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'                   => 'integer',
      'created_on'           => 'datetime',
      'updated_on'           => 'datetime',
      'name'                 => 'text',
      'code'                 => 'text',
      'project_id'           => 'resource:project',
      'tasklist_id'          => 'resource:tasklist',
      'seq'                  => 'integer',
      'description'          => 'text',
      'complete'             => 'boolean',
      'due_date'             => 'date',
      'user_id'              => 'resource:user',
      'users'                => 'collection:users',
      'billable'             => 'boolean',
      'flat_billing'         => 'boolean',
      'price_per_hour'       => 'decimal',
      'budget_hours'         => 'decimal',
      'estimated_price'      => 'decimal',
      'invoiced'             => 'boolean',
      'invoice_item_id'      => 'resource:invoiceitem',
      'priority'             => 'intEnum:25|50|75|100',
      'status_id'            => 'resource:workflowstatus',
      'subtasks_order'       => 'array',   // Array of subtask IDs for reordering
      'completed_on'         => 'datetime',
      'completed_by'         => 'resource:user',
        // Undocumented Props
      'cover_file_id'        => 'resource:file',
      'price'                => 'decimal',
      'start_date'           => 'date',
      'recurring_profile_id' => 'resource:taskrecurringprofile', // Task recurring profile reference
      'billing_type'         => 'text'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no restrictions for tasks - all standard operators allowed.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}