<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/12/20, 11:07 AM
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
 * WORKFLOW RESOURCE - PAYMO TASK STATUS WORKFLOWS
 * ======================================================================================
 *
 * This resource class represents a Paymo workflow. Workflows define the set of
 * status columns available for Kanban boards and task management. Each workflow
 * contains multiple workflow statuses that tasks can move through.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Status column management
 * - Default workflow designation
 * - Kanban board structure definition
 * - Project workflow assignment
 *
 * WORKFLOW STRUCTURE:
 * -------------------
 * - Workflow: Container with name and default flag
 *   - WorkflowStatus: Individual columns (Backlog, In Progress, Done, etc.)
 *     - Tasks: Assigned to statuses within the workflow
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique workflow identifier (read-only)
 * - name: Workflow name (required)
 * - is_default: Whether this is the default workflow
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Workflow;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\WorkflowStatus;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new workflow
 * $workflow = new Workflow();
 * $workflow->name = 'Agile Sprint Workflow';
 * $workflow->create($connection);
 *
 * // Fetch workflow with all statuses
 * $workflow = Workflow::fetch($connection, 12345, [
 *     'include' => ['workflowstatuses']
 * ]);
 *
 * // Access workflow statuses
 * if ($workflow->hasInclude('workflowstatuses')) {
 *     $statuses = $workflow->getInclude('workflowstatuses');
 *     foreach ($statuses as $status) {
 *         echo $status->name . " (color: " . $status->color . ")\n";
 *     }
 * }
 *
 * // List all workflows
 * $workflows = Workflow::list($connection);
 *
 * // Set as default workflow
 * $workflow->is_default = true;
 * $workflow->update($connection);
 *
 * // Delete workflow
 * $workflow->delete();
 * ```
 *
 * DEFAULT WORKFLOW:
 * -----------------
 * Only one workflow can be the default at a time. Setting is_default=true
 * on one workflow will automatically unset it on others. New projects
 * use the default workflow unless specified.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        WorkflowStatus Status columns within workflows
 * @see        Project Projects using workflows
 * @see        Task Tasks assigned to workflow statuses
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Workflow resource for task status management.
 *
 * Workflows define the status columns for Kanban boards and task management.
 * This class provides full CRUD operations with status column associations.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id         Unique workflow ID (read-only)
 * @property string $name       Workflow name (required)
 * @property bool   $is_default Whether this is the default workflow
 * @property string $created_on Creation timestamp (read-only)
 * @property string $updated_on Last update timestamp (read-only)
 */
class Workflow extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Workflow';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'workflow';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'workflows';

    /**
     * Properties required when creating a new workflow.
     *
     * Only 'name' is required - statuses can be added separately.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name'];

    /**
     * Properties that cannot be modified via API.
     *
     * Standard timestamp fields are read-only.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for workflows.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - workflowstatuses: Status columns in this workflow (collection)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = ['workflowstatuses' => true];

    /**
     * Property type definitions for validation and hydration.
     *
     * Note: workflowstatuses_order is commented out as status reordering
     * behavior is not documented in the Paymo API.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'         => 'integer',
      'created_on' => 'datetime',
      'updated_on' => 'datetime',
      'name'       => 'text',
      'is_default' => 'boolean'
        // 'workflowstatuses_order' => 'collection:workflowstatus' // DOES NOT REORDER STATUSES. Can you? No documentation.
        // Undocumented Props
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for workflows.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}