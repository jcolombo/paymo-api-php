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
 * TASK ASSIGNMENT RESOURCE - PAYMO USER-TASK ASSIGNMENTS
 * ======================================================================================
 *
 * This resource class represents a Paymo task assignment (also known as "usertask").
 * Task assignments link users to tasks, tracking who is assigned to work on what.
 * They also aggregate time tracking data for the user-task combination.
 *
 * KEY FEATURES:
 * -------------
 * - Full CRUD operations (create, read, update, delete)
 * - User-to-task linking
 * - Tracked time aggregation
 * - Task completion status tracking
 * - Foundation for resource bookings
 *
 * NAMING NOTE:
 * ------------
 * In Paymo's API, this is called "usertask" (API_ENTITY) and uses the endpoint
 * "userstasks" (API_PATH). The class is named TaskAssignment for clarity.
 *
 * AVAILABLE PROPERTIES:
 * ---------------------
 * Core Properties:
 * - id: Unique assignment identifier (read-only)
 * - user_id: Assigned user (required)
 * - task_id: Target task (required)
 *
 * Computed Properties (read-only):
 * - tracked_time: Total time tracked (seconds)
 * - task_complete: Whether the task is completed
 *
 * USAGE EXAMPLES:
 * ---------------
 * ```php
 * use Jcolombo\PaymoApiPhp\Paymo;
 * use Jcolombo\PaymoApiPhp\Entity\Resource\TaskAssignment;
 * use Jcolombo\PaymoApiPhp\Utility\RequestCondition;
 *
 * // Connect to API
 * $connection = Paymo::connect('your-api-key');
 *
 * // Assign a user to a task
 * $assignment = new TaskAssignment();
 * $assignment->user_id = 12345;
 * $assignment->task_id = 67890;
 * $assignment->create($connection);
 *
 * // Fetch assignment with user and task details
 * $assignment = TaskAssignment::fetch($connection, 11111, [
 *     'include' => ['user', 'task']
 * ]);
 *
 * // List assignments for a user (requires TaskAssignmentCollection)
 * use Jcolombo\PaymoApiPhp\Entity\Collection\TaskAssignmentCollection;
 *
 * $assignments = TaskAssignmentCollection::list($connection, [
 *     'where' => [
 *         RequestCondition::where('user_id', 12345),
 *     ]
 * ]);
 *
 * // List incomplete task assignments
 * $incomplete = TaskAssignmentCollection::list($connection, [
 *     'where' => [
 *         RequestCondition::where('task_complete', false),
 *     ]
 * ]);
 *
 * // Check tracked time
 * echo "Time spent: " . ($assignment->tracked_time / 3600) . " hours";
 *
 * // Delete assignment (unassign user)
 * $assignment->delete();
 * ```
 *
 * RELATIONSHIP TO BOOKINGS:
 * -------------------------
 * Task assignments are the foundation for bookings. When creating a booking
 * (resource scheduling), you reference the task assignment ID (user_task_id)
 * rather than separate user_id and task_id.
 *
 * @package    Jcolombo\PaymoApiPhp\Entity\Resource
 * @author     Joel Colombo <jc-dev@360psg.com>
 * @copyright  2020 Joel Colombo
 * @license    MIT
 * @version    0.5.6
 * @link       https://github.com/jcolombo/paymo-api-php
 *
 * @see        AbstractResource Parent resource class
 * @see        Task Tasks being assigned
 * @see        User Users being assigned
 * @see        Booking Resource scheduling via assignments
 * @see        TaskAssignmentCollection Collection with required filters
 */

namespace Jcolombo\PaymoApiPhp\Entity\Resource;

use Jcolombo\PaymoApiPhp\Entity\AbstractResource;

/**
 * Paymo TaskAssignment resource for user-task linking operations.
 *
 * Task assignments (usertasks) connect users to tasks, enabling assignment
 * tracking and time aggregation. This class provides full CRUD operations.
 *
 * @package Jcolombo\PaymoApiPhp\Entity\Resource
 *
 * @property int    $id            Unique assignment ID (read-only)
 * @property int    $user_id       Assigned user ID (required)
 * @property int    $task_id       Target task ID (required)
 * @property int    $tracked_time  Total tracked time in seconds (read-only)
 * @property bool   $task_complete Whether task is completed (read-only)
 * @property string $created_on    Creation timestamp (read-only)
 * @property string $updated_on    Last update timestamp (read-only)
 */
class TaskAssignment extends AbstractResource
{
    /**
     * Human-readable label for error messages and logging.
     *
     * @var string
     */
    public const LABEL = 'Task Assignment';

    /**
     * Entity key for internal references and EntityMap lookups.
     *
     * Note: Paymo API uses 'usertask' as the entity key.
     *
     * @var string
     */
    public const API_ENTITY = 'usertask';

    /**
     * API endpoint path appended to base URL.
     *
     * Note: Paymo API uses 'userstasks' (plural of both).
     *
     * @var string
     */
    public const API_PATH = 'userstasks';

    /**
     * Properties required when creating a new task assignment.
     *
     * Both user_id and task_id are required to create an assignment.
     *
     * @var array<string>
     */
    public const REQUIRED_CREATE = ['user_id', 'task_id'];

    /**
     * Properties that cannot be modified via API.
     *
     * tracked_time and task_complete are computed/derived values.
     *
     * @var array<string>
     */
    public const READONLY = ['id', 'created_on', 'updated_on', 'tracked_time', 'task_complete'];

    /**
     * Properties that can be set during creation but not updated.
     *
     * Currently empty - assignments can be updated after creation.
     *
     * @var array<string>
     */
    public const CREATEONLY = [];

    /**
     * Related entities available for inclusion in API requests.
     *
     * Use with the 'include' option in fetch() or list() calls:
     * - user: Assigned user (single)
     * - task: Target task (single)
     *
     * @var array<string, bool>
     */
    public const INCLUDE_TYPES = [
      'user' => false,
      'task' => false
    ];

    /**
     * Property type definitions for validation and hydration.
     *
     * tracked_time is in seconds (integer).
     * task_complete is undocumented but usable in WHERE filters.
     *
     * @var array<string, string>
     */
    public const PROP_TYPES = [
      'id'            => 'integer',
      'created_on'    => 'datetime',
      'updated_on'    => 'datetime',
      'user_id'       => 'resource:user',
      'task_id'       => 'resource:task',
        // Undocumented Props
      'tracked_time'  => 'integer',
      'task_complete' => 'boolean' // This is not documented anywhere but can be used in WHERE calls
    ];

    /**
     * Allowed WHERE operators for specific properties in list queries.
     *
     * Currently no specific restrictions for task assignments.
     *
     * @var array<string, array<string>>
     */
    public const WHERE_OPERATIONS = [];
}