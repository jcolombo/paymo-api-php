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
 * TIME ENTRY RESOURCE - PAYMO TIME TRACKING
 * ======================================================================================
 *
 * This resource class represents a Paymo time entry. Time entries are the fundamental
 * time tracking records that log work performed on tasks. They can be created either
 * as timed entries (with start/end times) or as manual entries (with date and duration).
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Support for both timed and manual time entries
 * - Task and user association
 * - Billing status tracking
 * - Invoice item linkage
 * - Duration in seconds for precise calculations
 *
 * ENTRY TYPES:
 * ------------
 * Time entries can be created in three ways:
 *
 * 1. Manual Entry (date + duration):
 *    - Specify date and duration in seconds
 *    - added_manually = true
 *
 * 2. Timed Entry (start_time + end_time):
 *    - Specify exact start and end timestamps
 *    - Duration is calculated automatically
 *
 * 3. Running Timer (user_id + start_time):
 *    - Start a running timer for a user
 *    - End time is set when timer stops
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique entry identifier (read-only)
 * - task_id: Associated task (required)
 * - user_id: User who logged time
 * - description: Work description/notes
 *
 * Time Properties:
 * - date: Date of the work (YYYY-MM-DD)
 * - duration: Time in seconds
 * - start_time: Start timestamp (for timed entries)
 * - end_time: End timestamp (for timed entries)
 * - added_manually: Whether entry was manual vs timed
 *
 * Billing Properties:
 * - billed: Whether the entry has been billed
 * - invoice_item_id: Associated invoice line item
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\TimeEntry;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a manual time entry (2 hours)
 * $entry = new TimeEntry();
 * $entry->task_id = 12345;
 * $entry->user_id = 67890;
 * $entry->date = '2024-01-15';
 * $entry->duration = 7200; // 2 hours in seconds
 * $entry->description = 'Implemented new feature';
 * $entry->create($connection);
 *
 * // Create a timed entry
 * $entry = new TimeEntry();
 * $entry->task_id = 12345;
 * $entry->start_time = '2024-01-15T09:00:00Z';
 * $entry->end_time = '2024-01-15T11:30:00Z';
 * $entry->description = 'Code review session';
 * $entry->create($connection);
 *
 * // Fetch entries for a project
 * $entries = TimeEntry::list($connection, [
 *     'where' => [
 *         RequestCondition::where('project_id', 11111),
 *     ]
 * ]);
 *
 * // Fetch entries in a date range
 * $entries = TimeEntry::list($connection, [
 *     'where' => [
 *         RequestCondition::where('time_interval', '2024-01-01,2024-01-31', 'in'),
 *     ]
 * ]);
 *
 * // Calculate total hours
 * $totalSeconds = 0;
 * foreach ($entries as $entry) {
 *     $totalSeconds += $entry->duration;
 * }
 * $totalHours = $totalSeconds / 3600;
 * ```
 *
 * DURATION HANDLING:
 * ------------------
 * Duration is stored in seconds for precision. Common conversions:
 * - 1 hour = 3600 seconds
 * - 1 minute = 60 seconds
 * - To get hours: $duration / 3600
 * - To get minutes: ($duration % 3600) / 60
 *
 * TIME_INTERVAL FILTER:
 * ---------------------
 * The time_interval property is a special filter-only property used for
 * fetching entries within a date range. Format: "YYYY-MM-DD,YYYY-MM-DD"
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Task Tasks that entries are logged against
 * @see        User Users who log time
 * @see        InvoiceItem Invoice billing association
 * @see        TimeEntryCollection Collection with required filters
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo TimeEntry resource for time tracking operations.
 *
 * Time entries record work performed on tasks, supporting both manual
 * entries (date + duration) and timed entries (start + end times).
 * This class provides full CRUD operations with task/user associations.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id              Unique entry ID (read-only)
 * @property int    $project_id      Parent project ID (read-only)
 * @property int    $task_id         Associated task ID (required)
 * @property int    $user_id         User who logged time
 * @property bool   $is_bulk         Whether part of bulk entry (read-only)
 * @property string $start_time      Start timestamp (timed entries)
 * @property string $end_time        End timestamp (timed entries)
 * @property string $date            Date (YYYY-MM-DD, manual entries)
 * @property int    $duration        Duration in seconds
 * @property string $description     Work description/notes
 * @property bool   $added_manually  Manual vs timed entry flag
 * @property bool   $billed          Whether entry has been billed
 * @property int    $invoice_item_id Associated invoice item ID
 * @property string $created_on      Creation timestamp (read-only)
 * @property string $updated_on      Last update timestamp (read-only)
 */
class TimeEntry extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'TimeEntry';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * Note: API uses 'entry' as the entity key, not 'timeentry'.
     *
     * @var string
     */
    public const API_ENTITY = 'entry';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'entries';

    /**
     * Properties required when creating a new time entry.
     *
     * Requires task_id AND one of these time specifications:
     * - date & duration (manual entry)
     * - start_time & end_time (timed entry)
     * - user_id & start_time (running timer)
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['task_id', 'date&duration||start_time&end_time||user_id&start_time'];

    /**
     * Properties that cannot be modified via API.
     *
     * These are set by the server and returned in responses but cannot
     * be included in create or update requests.
     *
     * @var array<string>
     */
    public const READONLY = [
      'id',
      'created_on',
      'updated_on',
      'project_id',
      'is_bulk', // Based on entry type, not manually set
      'time_interval', // Filter-only property, not readable
      'client_id'
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for time entries.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'task'        => false,
      'invoiceitem' => false,
      'user'        => false
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Duration is stored in seconds (integer).
     * Dates use 'date' type (YYYY-MM-DD).
     * Timestamps use 'datetime' type (ISO 8601).
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'              => 'integer',
      'created_on'      => 'datetime',
      'updated_on'      => 'datetime',
      'project_id'      => 'resource:project',
      'task_id'         => 'resource:task',
      'user_id'         => 'resource:user',
      'is_bulk'         => 'boolean',
      'start_time'      => 'datetime',
      'end_time'        => 'datetime',
      'date'            => 'date',
      'duration'        => 'integer', // seconds
      'description'     => 'text',
      'added_manually'  => 'boolean', // true if added without a timer
      'billed'          => 'boolean',
      'invoice_item_id' => 'resource:invoiceitem',
        // Undocumented Props
      'client_id'       => 'resource:client',
      'time_interval'   => 'datetime[]'   // Special filter-only property
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * time_interval uses 'in' operator with comma-separated date range.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [
      'time_interval' => ['in']
    ];
}