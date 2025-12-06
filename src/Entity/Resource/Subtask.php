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
 * SUBTASK RESOURCE - PAYMO CHECKLIST ITEM MANAGEMENT
 * ======================================================================================
 *
 * Official API Documentation:
 * https://github.com/paymoapp/api/blob/master/sections/subtasks.md
 *
 * This resource class represents a Paymo subtask (checklist item). Subtasks are
 * individual checklist items that belong to a parent task. They provide a way to
 * break down tasks into smaller, trackable steps.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - Belongs to a parent task
 * - Completion tracking
 * - Sequence/ordering within task
 * - User assignment for individual subtask items
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique subtask identifier (read-only)
 * - name: Subtask name/description (required for creation)
 * - complete: Whether the subtask is completed
 * - seq: Sequence/order within the parent task
 *
 * Association Properties:
 * - task_id: Parent task ID (required for creation)
 * - project_id: Parent project ID (read-only, derived from task)
 * - user_id: Creator/assigned user ID
 *
 * Completion Properties:
 * - completed_on: Timestamp when subtask was completed (read-only)
 * - completed_by: User ID who marked subtask complete (read-only)
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\Subtask;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Create a new subtask (checklist item)
 * $subtask = new Subtask();
 * $subtask->name = 'Review code changes';
 * $subtask->task_id = 12345;
 * $subtask->create($connection);
 *
 * // Create multiple subtasks for a task
 * $items = ['Write tests', 'Update documentation', 'Deploy to staging'];
 * foreach ($items as $index => $item) {
 *     $subtask = new Subtask();
 *     $subtask->name = $item;
 *     $subtask->task_id = 12345;
 *     $subtask->seq = $index + 1;
 *     $subtask->create($connection);
 * }
 *
 * // Fetch subtasks for a specific task
 * $subtasks = Subtask::list($connection, [
 *     'where' => [
 *         RequestCondition::where('task_id', 12345),
 *     ]
 * ]);
 *
 * // Fetch only incomplete subtasks
 * $incomplete = Subtask::list($connection, [
 *     'where' => [
 *         RequestCondition::where('task_id', 12345),
 *         RequestCondition::where('complete', false),
 *     ]
 * ]);
 *
 * // Mark a subtask as complete
 * $subtask = Subtask::new()->fetch(55555);
 * $subtask->complete = true;
 * $subtask->update($connection);
 * ```
 *
 * REORDERING SUBTASKS:
 * --------------------
 * Subtask order is managed through the parent Task resource using the
 * `subtasks_order` property. To reorder subtasks:
 *
 * ```php
 * $task = Task::new()->fetch($taskId);
 * $task->subtasks_order = [3, 1, 2]; // New order of subtask IDs
 * $task->update($connection);
 * ```
 *
 * Note: The subtasks_order array only needs to contain the subtasks you want
 * to reorder; others will maintain their relative positions.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.7
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Task Parent task resource
 * @see        Project Project that contains the parent task
 * @see        User User who created or is assigned to subtask
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo Subtask resource for checklist item management operations.
 *
 * Subtasks are checklist items within tasks, allowing tasks to be broken
 * down into smaller, trackable steps. This class provides full CRUD operations
 * and supports related entity includes for comprehensive subtask data retrieval.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id           Unique subtask ID (read-only)
 * @property string $name         Subtask name/description (required)
 * @property bool   $complete     Whether subtask is completed
 * @property int    $task_id      Parent task ID (required, create-only)
 * @property int    $project_id   Parent project ID (read-only)
 * @property int    $user_id      Creator/assigned user ID
 * @property int    $seq          Sequence order within task
 * @property string $completed_on Completion timestamp (read-only)
 * @property int    $completed_by User ID who completed (read-only)
 * @property string $created_on   Creation timestamp (read-only)
 * @property string $updated_on   Last update timestamp (read-only)
 */
class Subtask extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Subtask';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * @var string
     */
    public const API_ENTITY = 'subtask';

    /**
     * API endpoint path appended to base URL.
     *
     * @var string
     */
    public const API_PATH = 'subtasks';

    /**
     * Properties required when creating a new subtask.
     *
     * A subtask requires a name and must belong to a task.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['name', 'task_id'];

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
        'project_id',     // Derived from parent task
        'completed_on',   // Set by server when complete=true
        'completed_by'    // Set by server when complete=true
    ];

    /**
     * Properties that can be set during creation but not updated.
     *
     * task_id establishes the parent relationship and cannot be changed
     * after the subtask is created.
     *
     * @var array<string>
     */
    public const CREATEONLY = ['task_id'];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls.
     * TRUE indicates a collection (multiple items), FALSE indicates single entity.
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
        'project' => false,
        'task'    => false,
        'user'    => false
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * Defines the data type for each property. Special types:
     * - 'resource:X': Reference to another entity type
     * - 'datetime': ISO 8601 timestamp
     * - 'boolean': True/false value
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
        'id'           => 'integer',
        'created_on'   => 'datetime',
        'updated_on'   => 'datetime',
        'name'         => 'text',
        'complete'     => 'boolean',
        'task_id'      => 'resource:task',
        'project_id'   => 'resource:project',
        'user_id'      => 'resource:user',
        'seq'          => 'integer',
        'completed_on' => 'datetime',
        'completed_by' => 'resource:user'
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Subtasks support filtering by task_id and complete status.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [
        'task_id'  => ['='],
        'complete' => ['=']
    ];
}
