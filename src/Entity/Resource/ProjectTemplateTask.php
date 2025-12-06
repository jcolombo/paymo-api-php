<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/15/20, 11:31 PM
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
 * PROJECT TEMPLATE TASK RESOURCE - PAYMO TEMPLATE TASK DEFINITIONS
 * ======================================================================================
 *
 * This resource class represents a Paymo project template task. Template tasks
 * define individual task items within project templates, including billing rates,
 * time budgets, and default user assignments. When a project is created from the
 * template, these become actual tasks in the new project.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Tasklist association (required)
 * - Billing configuration (hourly/flat rate)
 * - Time budget and estimates
 * - Default user assignments
 * - Sequence ordering
 * - Date offset for scheduling
 *
 * TEMPLATE HIERARCHY:
 * -------------------
 * - ProjectTemplate: Container
 *   - ProjectTemplateTasklist: Sections
 *     - ProjectTemplateTask: Individual tasks (this class)
 *
 * RESPONSE KEY MAPPING:
 * ---------------------
 * This resource uses 'project_templates_tasks' as the API response key
 * instead of the standard 'projecttemplatestasks' endpoint name.
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique task identifier (read-only)
 * - name: Task name (required)
 * - tasklist_id: Parent template tasklist (required)
 * - template_id: Parent template (read-only, derived)
 * - description: Task description
 * - seq: Display order
 *
 * Billing Properties:
 * - billable: Whether task is billable
 * - budget_hours: Time budget in hours
 * - price_per_hour: Hourly rate
 * - flat_billing: Use flat rate (undocumented)
 * - estimated_price: Estimated cost (undocumented)
 * - price: Flat price (undocumented)
 *
 * Scheduling Properties:
 * - duration: Task duration (undocumented)
 * - start_date_offset: Days from project start (undocumented)
 * - users: Default assigned users
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\ProjectTemplateTask;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a billable template task
 * $task = new ProjectTemplateTask();
 * $task->name = 'Initial Design Mockups';
 * $task->tasklist_id = 12345;
 * $task->description = 'Create wireframes and initial design concepts';
 * $task->billable = true;
 * $task->budget_hours = 8;
 * $task->price_per_hour = 100.00;
 * $task->seq = 1;
 * $task->create($connection);
 *
 * // Create task with default assignees
 * $task = new ProjectTemplateTask();
 * $task->name = 'Code Review';
 * $task->tasklist_id = 12345;
 * $task->users = [111, 222]; // User IDs to auto-assign
 * $task->create($connection);
 *
 * // Fetch task with parent references
 * $task = ProjectTemplateTask::fetch($connection, 67890, [
 *     'include' => ['projecttemplate', 'projecttemplatetasklist']
 * ]);
 *
 * // List tasks for a tasklist
 * $tasks = ProjectTemplateTask::list($connection, [
 *     'where' => [
 *         RequestCondition::where('tasklist_id', 12345),
 *     ]
 * ]);
 *
 * // Update task
 * $task->budget_hours = 12;
 * $task->update($connection);
 *
 * // Delete task
 * $task->delete();
 * ```
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        ProjectTemplate Parent template container
 * @see        ProjectTemplateTasklist Parent template tasklist
 * @see        Task Actual tasks in projects
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo ProjectTemplateTask resource for template task definitions.
 *
 * Template tasks define individual task items within project templates.
 * This class provides full CRUD operations with billing and assignment configuration.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id                Unique task ID (read-only)
 * @property string $name              Task name (required)
 * @property int    $tasklist_id       Parent template tasklist ID (required)
 * @property int    $template_id       Parent template ID (read-only)
 * @property string $description       Task description
 * @property int    $seq               Display order
 * @property bool   $billable          Whether task is billable
 * @property float  $budget_hours      Time budget in hours
 * @property float  $price_per_hour    Hourly rate
 * @property array  $users             Default assigned user IDs
 * @property bool   $flat_billing      Use flat rate billing
 * @property float  $estimated_price   Estimated cost
 * @property float  $price             Flat price
 * @property int    $duration          Task duration
 * @property int    $start_date_offset Days from project start
 * @property string $created_on        Creation timestamp (read-only)
 * @property string $updated_on        Last update timestamp (read-only)
 */
class ProjectTemplateTask extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Project Template Task';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'projecttemplatestask';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'projecttemplatestasks';

    /**
     * Alternative response key for processing API results.
     *
     * The API returns 'project_templates_tasks' instead of
     * 'projecttemplatestasks'.
     *
     * @var string
     */
    public const API_RESPONSE_KEY = 'project_templates_tasks';

    /**
     * Properties required when creating a new template task.
     *
     * Both 'name' and 'tasklist_id' are required.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name', 'tasklist_id'];

    /**
     * Properties that cannot be modified via API.
     *
     * template_id is derived from the tasklist and is read-only.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'template_id'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for template tasks.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - projecttemplate: Parent template (single)
     * - projecttemplatetasklist: Parent template tasklist (single)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'projecttemplate'         => false,
      'projecttemplatetasklist' => false
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Many properties are undocumented but functional for complete
     * task template configuration.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'                => 'integer',
      'created_on'        => 'datetime',
      'updated_on'        => 'datetime',
      'name'              => 'text',
      'template_id'       => 'resource:projecttemplate',
      'tasklist_id'       => 'resource:projecttemplatetasklist',
      'seq'               => 'integer',
      'description'       => 'text',
      'billable'          => 'boolean',
      'budget_hours'      => 'decimal',
      'price_per_hour'    => 'decimal',
      'users'             => 'collection:users',
        // Undocumented Props
      'flat_billing'      => 'boolean',
      'estimated_price'   => 'decimal',
      'price'             => 'decimal',
      'duration'          => 'integer',
      'start_date_offset' => 'integer'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for template tasks.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}