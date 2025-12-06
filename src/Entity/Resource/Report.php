<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/19/20, 1:27 PM
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
 * REPORT RESOURCE - PAYMO TIME AND PROJECT REPORTS
 * ======================================================================================
 *
 * This resource class represents a Paymo report. Reports aggregate time tracking,
 * project, and billing data for analysis and client presentation. They support
 * various date ranges, filtering options, and display configurations.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Multiple report types (static, live, temp)
 * - Flexible date range selection
 * - Project/client/user filtering
 * - Customizable display options
 * - Shareable permalinks
 * - Invoice integration
 *
 * REPORT TYPES:
 * -------------
 * - static: Snapshot report with fixed data
 * - live: Dynamic report that updates with new data
 * - temp: Temporary report for quick views
 *
 * DATE RANGE OPTIONS:
 * -------------------
 * Either specify date_interval OR both start_date and end_date:
 * - date_interval: today, yesterday, this_month, last_month,
 *                  this_week, last_week, this_year, last_year, all_time
 * - start_date + end_date: Custom date range
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique report identifier (read-only)
 * - name: Report name
 * - type: Report type (required)
 * - date_interval OR start_date/end_date: Date range (required)
 *
 * Filter Properties:
 * - projects: Projects to include
 * - clients: Clients to include
 * - users: Users to include
 *
 * Display Options (include object):
 * - days, clients, users, projects, tasklists, tasks, billed, entries
 *
 * Extra Options (extra object):
 * - Various exclusion, rounding, and display settings
 *
 * Output Properties (read-only):
 * - info: Report metadata
 * - content: Report data
 * - permalink: Shareable URL
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Report;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a monthly time report
 * $report = new Report();
 * $report->name = 'March Time Report';
 * $report->type = 'static';
 * $report->date_interval = 'last_month';
 * $report->projects = 'all_active';
 * $report->include = [
 *     'days' => true,
 *     'projects' => true,
 *     'tasks' => true,
 *     'entries' => true
 * ];
 * $report->create($connection);
 *
 * // Create a custom date range report
 * $report = new Report();
 * $report->type = 'live';
 * $report->start_date = '2024-01-01';
 * $report->end_date = '2024-01-31';
 * $report->clients = [12345, 67890]; // Specific clients
 * $report->create($connection);
 *
 * // Fetch report with full data
 * $report = Report::fetch($connection, 11111, [
 *     'include' => ['user', 'client']
 * ]);
 *
 * // Access report content
 * $info = $report->info;      // Metadata
 * $content = $report->content; // Actual report data
 * $link = $report->permalink;  // Shareable URL
 *
 * // Share report
 * $report->shared = true;
 * $report->share_client_id = 12345;
 * $report->update($connection);
 * ```
 *
 * COMPLEX PROPERTY TYPES:
 * -----------------------
 * Reports have nested object properties for configuration:
 * - 'include': Object with boolean flags for what to include
 * - 'extra': Object with display and filtering options
 * These complex objects cannot be used in WHERE operations.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        TimeEntry Time tracking data
 * @see        Project Project filtering
 * @see        Client Client filtering
 * @see        User User filtering
 * @see        Invoice Invoice integration
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Report resource for time and project analysis.
 *
 * Reports aggregate time tracking and project data. This class provides
 * full CRUD operations with extensive filtering and display options.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id              Unique report ID (read-only)
 * @property string $name            Report name
 * @property int    $user_id         Report creator ID
 * @property string $type            Report type: static|live|temp (required)
 * @property string $start_date      Custom range start date
 * @property string $end_date        Custom range end date
 * @property string $date_interval   Preset date range
 * @property mixed  $projects        Projects filter
 * @property mixed  $clients         Clients filter
 * @property mixed  $users           Users filter
 * @property array  $include         What to include in report
 * @property array  $extra           Extra display/filter options
 * @property object $info            Report metadata (read-only)
 * @property object $content         Report data (read-only)
 * @property string $permalink       Shareable URL
 * @property bool   $shared          Whether report is shared
 * @property int    $share_client_id Client to share with
 * @property string $created_on      Creation timestamp (read-only)
 * @property string $updated_on      Last update timestamp (read-only)
 */
class Report extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Report';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'report';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'reports';

    /**
     * Properties required when creating a new report.
     *
     * Requires 'type' AND either:
     * - date_interval (preset range)
     * - OR both start_date AND end_date (custom range)
     *
     * The '||' syntax means at least one option, '&' means both together.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['type', 'date_interval||start_date&end_date'];

    /**
     * Properties that cannot be modified via API.
     *
     * Info and content are computed/generated by the server.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'info', 'content'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for reports.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - user: Report creator (single)
     * - client: Shared client (single)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'user'   => false,
      'client' => false
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Note: This resource has complex nested object properties ('include', 'extra')
     * that contain multiple boolean configuration options.
     *
     * @var array<string, string|array>
     */
    public const PROP_TYPES = [
      'id'              => 'integer',
      'created_on'      => 'datetime',
      'updated_on'      => 'datetime',
      'name'            => 'text',
      'user_id'         => 'resource:user',
      'type'            => 'enum:static|live|temp',
      'start_date'      => '?',
      'end_date'        => '?',
      'date_interval'   => 'enum:today|yesterday|this_month|last_month|this_week|last_week|this_year|last_year|all_time',
      'projects'        => 'collection:projects||enum:all|all_active|all_archived||resource:workflowstatus',
      'clients'         => 'collection:clients||enum:all|all_active',
      'users'           => 'collection:users||enum:all|all_active|all_archived',
      'include'         => [
        'days'      => 'boolean',
        'clients'   => 'boolean',
        'users'     => 'boolean',
        'projects'  => 'boolean',
        'tasklists' => 'boolean',
        'tasks'     => 'boolean',
        'billed'    => 'boolean',
        'entries'   => 'boolean'
      ],
      'extra'           => [
        'exclude_billed_entries'               => 'boolean',
        'exclude_unbilled_entries'             => 'boolean',
        'exclude_billable_tasks'               => 'boolean',
        'exclude_nonbillable_tasks'            => 'boolean',
        'exclude_flat_rate_tasks'              => 'boolean',
        'exclude_flats'                        => 'boolean',
        'enable_time_rounding'                 => 'boolean',
        'rounding_step'                        => 'integer',
        'display_charts'                       => 'boolean',
        'display_costs'                        => 'boolean',
        'display_entries_descriptions'         => 'boolean',
        'display_seconds'                      => 'boolean',
        'display_tasks_codes'                  => 'boolean',
        'display_tasks_descriptions'           => 'boolean',
        'display_tasks_complete_status'        => 'boolean',
        'display_tasks_remaining_time_budgets' => 'boolean',
        'display_tasks_time_budget'            => 'boolean',
        'display_projects_budgets'             => 'boolean',
        'display_projects_codes'               => 'boolean',
        'display_projects_descriptions'        => 'boolean',
        'display_projects_remaining_budgets'   => 'boolean',
        'display_users_positions'              => 'boolean',
        'order'                                => 'array',
      ],
      'info'            => 'object',
      'content'         => 'object',
      'permalink'       => 'url',
      'shared'          => 'boolean',
      'share_client_id' => 'resource:client',
        // Undocumented Props
      'active'          => 'boolean',
      'share_users_ids' => 'collection:users',
      'invoice_id'      => 'resource:invoice',
      'download_token'  => 'text'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Complex object properties (info, content, include, extra) cannot
     * be used in WHERE clauses and are set to null.
     *
     * @var array<string, null>
     */
    public const WHERE_OPERATIONS = [
      'info'    => null,
      'content' => null,
      'include' => null,
      'extra'   => null
    ];
}