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
 * TASK RECURRING PROFILE RESOURCE - PAYMO TASK RECURRING AUTOMATION
 * ======================================================================================
 *
 * Official API Documentation:
 * https://github.com/paymoapp/api/blob/master/sections/task_recurring_profiles.md
 *
 * This resource class represents a Paymo Task Recurring Profile. Recurring profiles
 * allow you to automate task creation on a scheduled basis (daily, weekly, or monthly).
 * Tasks are generated based on the configured frequency, interval, and start date.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Flexible frequency options (daily, weekly, monthly)
 * - Configurable intervals (every N days/weeks/months)
 * - Optional occurrence limits or end date
 * - Can import settings from existing tasks
 * - Support for task assignment to multiple users
 * - Budget hours and billing configuration
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique profile identifier (read-only)
 * - name: Task name template (required)
 * - description: Task description
 * - code: Task code
 *
 * Association Properties:
 * - project_id: Parent project ID (required unless task_id provided)
 * - tasklist_id: Target tasklist ID
 * - task_id: Source task to copy settings from (create only)
 * - company_id: Company ID
 *
 * Schedule Properties:
 * - frequency: 'daily', 'weekly', or 'monthly' (required)
 * - interval: Every N periods (required)
 * - recurring_start_date: Start date for calculations (required)
 * - on_day: Specific day for monthly generation
 * - occurrences: Maximum tasks to create (null = unlimited)
 * - until: End date for generation
 * - active: Whether profile is currently generating tasks
 *
 * Processing Properties:
 * - processing_timezone: Timezone for task generation
 * - processing_hour: Time of day to generate (HH:MM:SS)
 * - due_date_offset: Days until task due date
 *
 * Billing Properties:
 * - billable: Whether generated tasks are billable
 * - flat_billing: Flat rate vs hourly
 * - price_per_hour: Hourly rate
 * - estimated_price: Estimated task value
 * - budget_hours: Budgeted hours
 *
 * FREQUENCY VALUES:
 * -----------------
 * - 'daily': Generate tasks daily (or every N days)
 * - 'weekly': Generate tasks weekly (or every N weeks)
 * - 'monthly': Generate tasks monthly (or every N months)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\TaskRecurringProfile;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a weekly recurring task profile
 * $profile = new TaskRecurringProfile();
 * $profile->name = 'Weekly Team Standup';
 * $profile->project_id = 12345;
 * $profile->tasklist_id = 67890;
 * $profile->frequency = 'weekly';
 * $profile->interval = 1; // Every week
 * $profile->recurring_start_date = '2024-01-01';
 * $profile->users = [101, 102, 103]; // Assign to team
 * $profile->due_date_offset = 0; // Due same day
 * $profile->create($connection);
 *
 * // Create monthly recurring task with occurrence limit
 * $profile = new TaskRecurringProfile();
 * $profile->name = 'Monthly Report';
 * $profile->project_id = 12345;
 * $profile->frequency = 'monthly';
 * $profile->interval = 1;
 * $profile->recurring_start_date = '2024-01-01';
 * $profile->on_day = '15'; // 15th of each month
 * $profile->occurrences = 12; // Generate 12 tasks total
 * $profile->create($connection);
 *
 * // Create profile from existing task
 * $profile = new TaskRecurringProfile();
 * $profile->task_id = 55555; // Copy settings from this task
 * $profile->frequency = 'weekly';
 * $profile->interval = 2; // Every 2 weeks
 * $profile->recurring_start_date = '2024-01-01';
 * $profile->create($connection);
 *
 * // List recurring profiles for a project
 * $profiles = TaskRecurringProfile::list($connection, [
 *     'where' => [
 *         RequestCondition::where('project_id', 12345),
 *     ]
 * ]);
 *
 * // Pause a recurring profile
 * $profile = TaskRecurringProfile::new()->fetch(55555);
 * $profile->active = false;
 * $profile->update($connection);
 * ```
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Task Generated task instances
 * @see        Project Parent project
 * @see        Tasklist Target tasklist for generated tasks
 * @see        User Users assigned to generated tasks
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Task Recurring Profile resource for automated task generation.
 *
 * Recurring profiles define schedules for automatic task creation.
 * This class provides full CRUD operations and supports related entity
 * includes for comprehensive recurring profile management.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                     Unique profile ID (read-only)
 * @property string $name                   Task name template (required)
 * @property string $code                   Task code
 * @property int    $project_id             Parent project ID
 * @property int    $tasklist_id            Target tasklist ID
 * @property int    $task_id                Source task for settings (create-only)
 * @property int    $user_id                Creator user ID (read-only)
 * @property int    $task_user_id           Task creator if from task (read-only)
 * @property int    $company_id             Company ID
 * @property bool   $billable               Whether tasks are billable
 * @property bool   $flat_billing           Flat rate vs hourly
 * @property string $description            Task description
 * @property float  $price_per_hour         Hourly rate
 * @property float  $estimated_price        Estimated task value
 * @property float  $budget_hours           Budgeted hours
 * @property array  $users                  Assigned user IDs
 * @property int    $priority               Task priority level
 * @property string $notifications          JSON alert configurations
 * @property string $frequency              daily|weekly|monthly (required)
 * @property int    $interval               Every N periods (required)
 * @property string $on_day                 Day of month for monthly
 * @property int    $occurrences            Max tasks to generate
 * @property string $until                  End date for generation
 * @property bool   $active                 Whether actively generating
 * @property int    $due_date_offset        Days until due date
 * @property string $recurring_start_date   Start date (required)
 * @property int    $generated_count        Tasks created (read-only)
 * @property string $last_generated_on      Last generation date (read-only)
 * @property string $next_processing_date   Next generation date (read-only)
 * @property string $processing_timezone    Timezone for generation
 * @property string $processing_hour        Time to generate (HH:MM:SS)
 * @property string $created_on             Creation timestamp (read-only)
 * @property string $updated_on             Last update timestamp (read-only)
 */
class TaskRecurringProfile extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Task Recurring Profile';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'taskrecurringprofile';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'taskrecurringprofiles';

    /**
     * Properties required when creating a new task recurring profile.
     *
     * Requires: name + (project_id OR task_id), frequency, interval, recurring_start_date
     * When using task_id, all task settings are imported from the source task.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name', 'project_id||task_id', 'frequency', 'interval', 'recurring_start_date'];

    /**
     * Properties that cannot be modified via API.
     *
     * These are calculated by the server or set automatically.
     *
     * @var array<string>
     */
    public const READONLY = [
        'id',
        'created_on',
        'updated_on',
        'user_id',
        'task_user_id',
        'generated_count',
        'last_generated_on',
        'next_processing_date'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * task_id is used to import settings from an existing task at creation time.
     *
     * @var array<string>
     */
    public const CREATEONLY = ['task_id'];

    /**
     * Related entities available for inclusion in API requests.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
        'project' => false
    ];

    /**
     * Property type definitions for validation and hydration.
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
        'task_id'              => 'resource:task',
        'user_id'              => 'resource:user',
        'task_user_id'         => 'resource:user',
        'company_id'           => 'resource:company',
        'billable'             => 'boolean',
        'flat_billing'         => 'boolean',
        'description'          => 'text',
        'price_per_hour'       => 'decimal',
        'estimated_price'      => 'decimal',
        'budget_hours'         => 'decimal',
        'users'                => 'collection:user',
        'priority'             => 'intEnum:25|50|75|100',
        'notifications'        => 'text',
        'frequency'            => 'enum:daily|weekly|monthly',
        'interval'             => 'integer',
        'on_day'               => 'text',
        'occurrences'          => 'integer',
        'until'                => 'date',
        'active'               => 'boolean',
        'due_date_offset'      => 'integer',
        'recurring_start_date' => 'date',
        'generated_count'      => 'integer',
        'last_generated_on'    => 'date',
        'next_processing_date' => 'date',
        'processing_timezone'  => 'text',
        'processing_hour'      => 'text'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [
        'project_id' => ['=']
    ];
}
