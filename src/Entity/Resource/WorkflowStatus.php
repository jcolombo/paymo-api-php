<?php
/**
 * PHP SDK for the PaymoApp API
 * Package Source Code: https://github.com/jcolombo/paymo-api-php
 * Paymo API Documentation : https://github.com/paymoapp/api
 *
 * MIT License
 * Copyright (c) 2020 - Joel Colombo <jc-dev@360psg.com>
 * Last Updated : 3/18/20, 10:48 PM
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
 * WORKFLOW STATUS RESOURCE - PAYMO KANBAN BOARD COLUMNS
 * ======================================================================================
 *
 * This resource class represents a Paymo workflow status. Workflow statuses are
 * the individual columns in a Kanban board (e.g., "To Do", "In Progress", "Done").
 * They belong to a workflow and tasks move between them.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Color-coded status columns
 * - Automatic color assignment via Configuration
 * - Sequence ordering for column position
 * - Special actions (backlog, complete)
 *
 * AUTOMATIC COLOR ASSIGNMENT:
 * ---------------------------
 * If no color is specified and Configuration has 'randomColor.workflowstatus' set,
 * a color will be automatically assigned during creation:
 * - true: Random color from the Color utility
 * - string: Named color palette
 * - array: [palette, shade] for specific color selection
 *
 * SPECIAL ACTIONS:
 * ----------------
 * The 'action' property defines special behavior:
 * - 'backlog': Tasks in this status are considered backlog/not started
 * - 'complete': Tasks in this status are marked as completed
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique status identifier (read-only)
 * - name: Status name (required)
 * - color: Hex color code (required)
 * - workflow_id: Parent workflow (required)
 * - seq: Column order position (read-only)
 * - action: Special action (read-only)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\WorkflowStatus;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new workflow status
 * $status = new WorkflowStatus();
 * $status->name = 'In Review';
 * $status->color = '#FFA500'; // Orange
 * $status->workflow_id = 12345;
 * $status->create($connection);
 *
 * // Create with automatic color (requires Configuration)
 * Configuration::set('randomColor.workflowstatus', 'blue');
 * $status = new WorkflowStatus();
 * $status->name = 'Testing';
 * $status->workflow_id = 12345;
 * $status->create($connection); // Color auto-assigned
 *
 * // Fetch status with parent workflow
 * $status = WorkflowStatus::fetch($connection, 67890, [
 *     'include' => ['workflow']
 * ]);
 *
 * // List statuses for a workflow
 * $statuses = WorkflowStatus::list($connection, [
 *     'where' => [
 *         RequestCondition::where('workflow_id', 12345),
 *     ]
 * ]);
 *
 * // Update status color
 * $status->color = '#00FF00';
 * $status->update($connection);
 *
 * // Check special action
 * if ($status->action === 'complete') {
 *     echo "This is a completion status";
 * }
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
 * @see        Workflow Parent workflow container
 * @see        Task Tasks assigned to statuses
 * @see        Color Color utility for auto-assignment
 * @see        Configuration Configuration for auto-color settings
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Exception;
use Jcolombo\PaymoApiPhp\Configuration;
use Jcolombo\PaymoApiPhp\Entity\AbstractResource;
use Jcolombo\PaymoApiPhp\Utility\Color;

/**
 * Paymo WorkflowStatus resource for Kanban board columns.
 *
 * Workflow statuses represent the columns in a Kanban board. This class
 * provides full CRUD operations with automatic color assignment support.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id          Unique status ID (read-only)
 * @property string $name        Status name (required)
 * @property string $color       Hex color code (required)
 * @property int    $workflow_id Parent workflow ID (required)
 * @property int    $seq         Column order position (read-only)
 * @property string $action      Special action: backlog|complete (read-only)
 * @property string $created_on  Creation timestamp (read-only)
 * @property string $updated_on  Last update timestamp (read-only)
 */
class WorkflowStatus extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Workflow Status';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'workflowstatus';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'workflowstatuses';

    /**
     * Properties required when creating a new workflow status.
     *
     * Name, color, and workflow_id are all required. However, color
     * can be auto-assigned via Configuration settings.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name', 'color', 'workflow_id'];

    /**
     * Properties that cannot be modified via API.
     *
     * Sequence and action are managed by the server.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'seq', 'action'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty for workflow statuses.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - workflow: Parent workflow (single)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = ['workflow' => false];

    /**
     * Property type definitions for validation and hydration.
     *
     * Action uses enum type for the special status actions.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'          => 'integer',
      'created_on'  => 'datetime',
      'updated_on'  => 'datetime',
      'name'        => 'text',
      'workflow_id' => 'resource:workflow',
      'color'       => 'text',
      'seq'         => 'integer',
      'action'      => 'enum:backlog|complete'
        // Undocumented Props
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for workflow statuses.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];

    /**
     * Create a new workflow status with automatic color assignment.
     *
     * Overrides parent create() to check for color configuration.
     * If no color is set and Configuration has 'randomColor.workflowstatus',
     * a color will be automatically assigned before creation.
     *
     * Configuration options for 'randomColor.workflowstatus':
     * - true: Random color from Color::random()
     * - string: Named palette via Color::byName($palette)
     * - array: [palette, shade] via Color::byName($palette, $shade)
     *
     * @param array $options Create options passed to parent
     *
     * @throws Exception
     * @return static|null Returns the created resource or null on failure
     *
     * @see Color::byName() Named color selection
     * @see Color::random() Random color generation
     * @see AbstractResource::create() Parent implementation
     */
    public function create($options = []) : ?WorkflowStatus
    {
        $config = Configuration::get('randomColor.workflowstatus');
        if ($config && !$this->get('color')) {
            if (is_array($config)) {
                $this->set('color', Color::byName($config[0], $config[1]));
            } elseif (is_string($config)) {
                $this->set('color', Color::byName($config));
            } else {
                $this->set('color', Color::random());
            }
        }

        return parent::create($options);
    }
}