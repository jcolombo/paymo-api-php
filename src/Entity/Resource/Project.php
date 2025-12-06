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
 * PROJECT RESOURCE - PAYMO PROJECT MANAGEMENT
 * ======================================================================================
 *
 * This resource class represents a Paymo project. Projects are the primary containers
 * for organizing work in Paymo, containing tasklists, tasks, discussions, files, and
 * time tracking data. They can be associated with clients and include billing/budgeting
 * configuration.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Client association for billing
 * - Multiple billing modes (time & materials, flat rate, non-billable)
 * - Budget tracking (hours and estimated price)
 * - Color-coded project identification
 * - User and manager assignments
 * - Workflow integration for task status management
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique project identifier (read-only)
 * - name: Project name (required for creation)
 * - code: Optional short code for task prefixes
 * - description: Detailed project description
 * - color: Hex color code for visual identification
 * - active: Whether the project is active or archived
 *
 * Association Properties:
 * - client_id: Associated client resource
 * - status_id: Project status reference
 * - workflow_id: Associated workflow for task statuses
 * - users: Array of user IDs assigned to project
 * - managers: Array of user IDs with manager privileges
 *
 * Billing Properties:
 * - billable: Whether the project tracks billable time
 * - flat_billing: True for flat-rate, false for hourly
 * - price_per_hour: Hourly rate for time & materials
 * - price: Flat-rate price
 * - estimated_price: Estimated project value
 * - hourly_billing_mode: Rate source (null, task, project, company)
 * - budget_hours: Budgeted hours for the project
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Project;
 * use Jcolombo\PaymoApiPhp\Utility\Color;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new billable project
 * $project = new Project();
 * $project->name = 'Website Redesign';
 * $project->description = 'Complete website overhaul';
 * $project->client_id = 12345;
 * $project->color = Color::byName('blue', 0);
 * $project->billable = true;
 * $project->price_per_hour = 150.00;
 * $project->budget_hours = 100;
 * $project->create($connection);
 *
 * // Fetch a project with related data
 * $project = Project::fetch($connection, 67890, [
 *     'include' => ['client', 'tasklists', 'tasks']
 * ]);
 *
 * // List active billable projects
 * $projects = Project::list($connection, [
 *     'where' => [
 *         RequestCondition::where('active', true),
 *         RequestCondition::where('billable', true),
 *     ]
 * ]);
 *
 * // Update project
 * $project->budget_hours = 120;
 * $project->update($connection);
 *
 * // Archive a project
 * $project->active = false;
 * $project->update($connection);
 * ```
 *
 * BILLING MODES:
 * --------------
 * Projects support three billing configurations:
 *
 * 1. Time & Materials (hourly billing):
 *    - billable = true, flat_billing = false
 *    - Set price_per_hour and hourly_billing_mode
 *
 * 2. Flat Rate (fixed price):
 *    - billable = true, flat_billing = true
 *    - Set price for the total project cost
 *
 * 3. Non-Billable:
 *    - billable = false
 *    - Other billing fields are ignored
 *
 * SPECIAL PROPERTIES:
 * -------------------
 * - tasklists_order: Array of tasklist IDs to reorder (update only)
 * - template_id: Copy structure from template project (create/update)
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Client Associated client resource
 * @see        Tasklist Project task lists
 * @see        Task Project tasks
 * @see        Color Utility for project colors
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Project resource for project management operations.
 *
 * Projects are the core organizational unit in Paymo, containing all work items,
 * time tracking, and billing information. This class provides full CRUD operations
 * and supports related entity includes for comprehensive project data retrieval.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                  Unique project ID (read-only)
 * @property string $name                Project name (required)
 * @property string $code                Short project code for task prefixes
 * @property int    $task_code_increment Auto-incrementing task number (read-only)
 * @property string $description         Project description
 * @property int    $client_id           Associated client ID
 * @property int    $status_id           Project status ID
 * @property bool   $active              Whether project is active
 * @property string $color               Hex color code (without #)
 * @property array  $users               User IDs assigned to project
 * @property array  $managers            Manager user IDs
 * @property bool   $billable            Whether project is billable
 * @property bool   $flat_billing        True for flat-rate billing
 * @property float  $price_per_hour      Hourly billing rate
 * @property float  $price               Flat-rate project price
 * @property float  $estimated_price     Estimated total value
 * @property string $hourly_billing_mode Rate source (null|task|project|company)
 * @property float  $budget_hours        Budgeted hours
 * @property bool   $adjustable_hours    Whether hours budget is adjustable
 * @property bool   $invoiced            Whether project has been invoiced
 * @property int    $invoice_item_id     Associated invoice item ID
 * @property int    $workflow_id         Associated workflow ID
 * @property string $created_on          Creation timestamp (read-only)
 * @property string $updated_on          Last update timestamp (read-only)
 */
class Project extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Project';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'project';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'projects';

    /**
     * Properties required when creating a new project.
     *
     * Only 'name' is required to create a project.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name'];

    /**
     * Properties that cannot be modified via API.
     *
     * These are set by the server and returned in responses but cannot
     * be included in create or update requests.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'task_code_increment', 'created_on', 'updated_on', 'billing_type'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for projects - all writable properties can be updated.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls.
     * TRUE indicates a collection (multiple items), FALSE indicates single entity.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'client'        => false,
      'projectstatus' => false,
      'tasklists'     => true,
      'tasks'         => true,
      'milestones'    => true,
      'discussions'   => true,
      'files'         => true,
      'invoiceitem'   => false,
      'workflow'      => false
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Defines the data type for each property:
     * - 'integer': Whole numbers
     * - 'text': String values
     * - 'boolean': True/false
     * - 'decimal': Floating point numbers
     * - 'datetime': ISO 8601 timestamp
     * - 'resource:X': Reference to another entity type
     * - 'collection:X': Array of entity references
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'                  => 'integer',
      'name'                => 'text',
      'code'                => 'text',
      'task_code_increment' => 'integer',
      'description'         => 'text',
      'client_id'           => 'resource:client',
      'status_id'           => 'resource:projectstatus',
      'active'              => 'boolean',
      'color'               => 'text',
      'users'               => 'collection:users',
      'managers'            => 'collection:managers',
      'billable'            => 'boolean',
      'flat_billing'        => 'boolean',
      'price_per_hour'      => 'decimal',
      'price'               => 'decimal',
      'estimated_price'     => 'decimal',
      'hourly_billing_mode' => 'text',
      'budget_hours'        => 'decimal',
      'adjustable_hours'    => 'boolean',
      'invoiced'            => 'boolean',
      'invoice_item_id'     => 'resource:invoiceitem',
      'workflow_id'         => 'resource:workflow',
      'created_on'          => 'datetime',
      'updated_on'          => 'datetime',
        // Undocumented Props
      'billing_type'        => 'text',
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Restricts which comparison operators can be used with certain properties.
     * Properties prefixed with '!' define operators that are NOT allowed.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [
      'active'   => ['=', '!='],
      '!active'  => ['like', 'not like'],
      'users'    => ['=', 'in', 'not in'],
      'managers' => ['=', 'in', 'not in'],
      'billable' => ['=', '!='],
    ];

    // SPECIAL PROPS (set via update only):
    // tasklists_order = [int,int,int,...] Reorder tasklists attached to project
    // template_id = INT. Copy structure from template project (create/update)

    // FUTURE: Special Methods for billing mode configuration
    // useTimeAndMaterials($useMode=null) - Set hourly billing
    // useFlatRate($price=null) - Set flat-rate billing
    // useNonBillable() - Disable billing
}